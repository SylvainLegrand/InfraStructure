<?php
/* Copyright 2022-2023 Éric Seigne <eric.seigne@cap-rel.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/uptosignapiclient.class.php
 * \ingroup     uptosign
 * \brief       HTTP client for UptoSign remote API
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';

/**
 * Class UptoSignAPIClient
 *
 * Centralizes all HTTP calls to the UptoSign remote API.
 * Handles endpoint resolution, authentication headers, log level management,
 * and base response parsing.
 */
class UptoSignAPIClient
{
	/**
	 * Number of CONSECUTIVE authentication failures (401, or 403 for an
	 * invalid/expired token) after which the client stops sending requests for the
	 * rest of the current process. Any other real HTTP answer (404, 500, ...)
	 * resets the counter, so the breaker only trips on a real run of auth failures.
	 * Prevents a configuration error (missing/wrong API key) from turning into
	 * a burst of rejected calls, and then into an IP ban server side.
	 */
	const MAX_AUTH_FAILURES = 3;

	/**
	 * Constant holding, per entity, the timestamp before which no request must be
	 * sent. Stored in database on purpose: the in-memory breaker below only lives
	 * for one PHP process, and a webhook storm or a page refresh gives every new
	 * process its own fresh quota of rejected calls. That is precisely how an
	 * instance keeps hammering a server that already answered 429.
	 */
	const BACKOFF_CONST_NAME = 'UPTOSIGN_API_BACKOFF_UNTIL';

	/**
	 * Constant holding how many times in a row the backoff had to be armed. Indexes
	 * BACKOFF_DELAYS, so an API that keeps refusing is questioned less and less.
	 */
	const BACKOFF_LEVEL_CONST_NAME = 'UPTOSIGN_API_BACKOFF_LEVEL';

	/**
	 * Backoff durations in seconds, one per consecutive failure. The last one is
	 * kept for every failure beyond the length of the list.
	 */
	const BACKOFF_DELAYS = array(300, 900, 1800, 3600);

	/**
	 * Hard ceiling for the pause between two calls of a loop, in milliseconds.
	 * A mistyped UPTOSIGN_API_SLEEP_MS must not turn a nightly cron into a job
	 * that never ends.
	 */
	const MAX_SLEEP_MS = 5000;

	/**
	 * @var DoliDB Database handler
	 */
	private $db;

	/**
	 * @var int Consecutive authentication failures (401/403) seen in this process
	 */
	private static $authFailures = 0;

	/**
	 * @var bool True once the breaker has tripped in this process
	 */
	private static $circuitOpen = false;

	/**
	 * @var int Timestamp before which no request is sent, 0 when no backoff is armed
	 */
	private static $backoffUntil = 0;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Reset the authentication circuit breaker
	 *
	 * Called when a new API key is stored: the previous failures are no longer
	 * meaningful.
	 *
	 * @param  DoliDB|null $db Database handler used to clear the stored backoff
	 * @return void
	 */
	public static function resetCircuit($db = null)
	{
		self::$authFailures = 0;
		self::$circuitOpen = false;
		self::$backoffUntil = 0;
		self::clearBackoff($db);
	}

	/**
	 * Forget the backoff stored in database
	 *
	 * @param DoliDB|null $db Database handler, nothing is written without one
	 * @return void
	 */
	public static function clearBackoff($db = null)
	{
		global $conf;

		self::$backoffUntil = 0;

		if (!is_object($db) || !function_exists('dolibarr_set_const')) {
			return;
		}
		// Reading before writing: the constants are absent on the vast majority of
		// calls, and a successful answer must not cost two writes every time.
		if ((int) utsbackports_getDolGlobalString(self::BACKOFF_CONST_NAME, '0') == 0
			&& (int) utsbackports_getDolGlobalString(self::BACKOFF_LEVEL_CONST_NAME, '0') == 0) {
			return;
		}

		$entity = isset($conf->entity) ? (int) $conf->entity : 1;
		dolibarr_set_const($db, self::BACKOFF_CONST_NAME, '0', 'chaine', 0, '', $entity);
		dolibarr_set_const($db, self::BACKOFF_LEVEL_CONST_NAME, '0', 'chaine', 0, '', $entity);
		$conf->global->{self::BACKOFF_CONST_NAME} = '0';
		$conf->global->{self::BACKOFF_LEVEL_CONST_NAME} = '0';
		dol_syslog("UptoSignAPIClient: the API answers again, backoff cleared");
	}

	/**
	 * Timestamp before which no request must be sent
	 *
	 * Takes the latest of what this process knows and what another process stored,
	 * so a backoff armed by a webhook also holds back the cron.
	 *
	 * @return int Timestamp, 0 when no backoff is armed
	 */
	public static function backoffUntil()
	{
		$stored = (int) utsbackports_getDolGlobalString(self::BACKOFF_CONST_NAME, '0');
		return max(self::$backoffUntil, $stored);
	}

	/**
	 * Arm the backoff after the server refused to answer
	 *
	 * @param DoliDB|null $db       Database handler, the delay is not persisted without one
	 * @param string      $reason   What tripped the breaker, for the log
	 * @return int                  Number of seconds the client will stay quiet
	 */
	public static function armBackoff($db = null, $reason = '')
	{
		global $conf;

		$level = (int) utsbackports_getDolGlobalString(self::BACKOFF_LEVEL_CONST_NAME, '0');
		$level++;
		$delays = self::BACKOFF_DELAYS;
		$delay = $delays[min($level, count($delays)) - 1];
		$until = dol_now() + $delay;

		self::$backoffUntil = $until;
		self::$circuitOpen = true;

		if (is_object($db) && function_exists('dolibarr_set_const')) {
			$entity = isset($conf->entity) ? (int) $conf->entity : 1;
			dolibarr_set_const($db, self::BACKOFF_CONST_NAME, (string) $until, 'chaine', 0, '', $entity);
			dolibarr_set_const($db, self::BACKOFF_LEVEL_CONST_NAME, (string) $level, 'chaine', 0, '', $entity);
			$conf->global->{self::BACKOFF_CONST_NAME} = (string) $until;
			$conf->global->{self::BACKOFF_LEVEL_CONST_NAME} = (string) $level;
		} else {
			dol_syslog("UptoSignAPIClient: no database handler, the backoff only holds for this process", LOG_WARNING);
		}

		dol_syslog("UptoSignAPIClient: " . $reason . ", no request will be sent for " . $delay . "s (until " . dol_print_date($until, '%Y-%m-%d %H:%M:%S') . ", failure #" . $level . ")", LOG_ERR);

		return $delay;
	}

	/**
	 * Pause between two calls of a loop
	 *
	 * Downloading a batch of documents as fast as the network allows is what turns
	 * a legitimate cron into something an anti abuse filter reads as an attack.
	 *
	 * @return void
	 */
	public static function pauseBetweenCalls()
	{
		$ms = (int) utsbackports_getDolGlobalString('UPTOSIGN_API_SLEEP_MS', '250');
		if ($ms <= 0) {
			return;
		}
		usleep(min($ms, self::MAX_SLEEP_MS) * 1000);
	}

	/**
	 * Tell if the circuit breaker is open (no more request will be sent)
	 *
	 * @return bool
	 */
	public static function isCircuitOpen()
	{
		return self::$circuitOpen;
	}

	/**
	 * Number of consecutive authentication failures seen in this process
	 *
	 * @return int
	 */
	public static function getAuthFailures()
	{
		return self::$authFailures;
	}

	/**
	 * Register the HTTP status returned by an authenticated call
	 *
	 * Opens the circuit breaker after MAX_AUTH_FAILURES CONSECUTIVE authentication
	 * failures. A 401 is always an authentication failure. A 403 is also counted
	 * here (the remote API returns 403 for an invalid/expired token, and the unit
	 * tests explicitly require 403 to count toward the breaker).
	 *
	 * Any other answer with a real HTTP status (http_code > 0), for example 404 or
	 * 500, is NOT an authentication failure and RESETS the counter, so the breaker
	 * only ever trips on truly consecutive auth failures. A curl-level failure
	 * (http_code == 0) is left untouched: it says nothing about authentication.
	 *
	 * A 429 (rate limit) or a 503 trips the breaker on its own, without waiting for
	 * MAX_AUTH_FAILURES: the server is explicitly asking to stop, and a second
	 * question is already one too many.
	 *
	 * @param int $httpCode HTTP status code
	 * @return void
	 */
	public static function registerHttpResult($httpCode)
	{
		$httpCode = (int) $httpCode;

		if ($httpCode == 429 || $httpCode == 503) {
			self::$circuitOpen = true;
			return;
		}

		if ($httpCode == 401 || $httpCode == 403) {
			self::$authFailures++;
			if (self::$authFailures >= self::MAX_AUTH_FAILURES && !self::$circuitOpen) {
				self::$circuitOpen = true;
				dol_syslog("UptoSignAPIClient: " . self::$authFailures . " consecutive authentication failures, stop sending requests for this process (check UPTOSIGN_KEY_API of the current entity)", LOG_ERR);
			}
			return;
		}

		// Any non-auth answer with a real HTTP status breaks the "consecutive" chain.
		if ($httpCode > 0) {
			self::$authFailures = 0;
		}
	}

	/**
	 * Build common HTTP headers for API requests
	 *
	 * @param bool $withBearer Include Authorization Bearer header
	 * @param bool $isJson     Include Content-Type application/json header
	 * @return array           Array of header strings
	 */
	private function buildHeaders($withBearer = true, $isJson = true)
	{
		$headers = array();
		$headers[] = 'User-Agent: ' . $this->userAgent();
		$headers[] = 'Accept: application/json';
		if ($withBearer) {
			$headers[] = 'Authorization: Bearer ' . utsbackports_getDolGlobalString('UPTOSIGN_KEY_API', '');
		}
		if ($isJson) {
			$headers[] = 'Content-Type: application/json';
		}
		return $headers;
	}

	/**
	 * Build the User-Agent sent to the API
	 *
	 * Isolated in its own method: it needs the module descriptor and the instance
	 * UUID (so a real $db and DOL_DATA_ROOT), which tests do not always provide.
	 *
	 * @return string
	 */
	protected function userAgent()
	{
		return uptosignuserAgent();
	}

	/**
	 * Execute an HTTP request to the UptoSign API
	 *
	 * Handles endpoint resolution, header construction, log level management,
	 * and base response normalization.
	 *
	 * @param string     $method     HTTP method (GET, POST, DELETE)
	 * @param string     $path       API path (e.g. /api/profile)
	 * @param array|null $data       Request body data (will be JSON-encoded)
	 * @param bool       $withBearer Include Authorization header (default true)
	 * @return array     Normalized response: [http_code, content, data, curl_error]
	 */
	public function request($method, $path, $data = null, $withBearer = true)
	{
		global $conf;

		$url = UptoSign::getEndPoint() . $path;
		$body = ($data !== null) ? json_encode($data) : '';

		$response = array(
			'http_code' => 0,
			'content' => '',
			'data' => null,
			'curl_error' => '',
		);

		dol_syslog("UptoSignAPIClient::request $method $path");

		if ($withBearer) {
			// Without a key the call can only end up in a 401. Sending it anyway is
			// how a whole instance gets its IP banned by the anti abuse of the server.
			if (trim(utsbackports_getDolGlobalString('UPTOSIGN_KEY_API', '')) === '') {
				dol_syslog("UptoSignAPIClient: UPTOSIGN_KEY_API is empty for entity " . (isset($conf->entity) ? $conf->entity : '?') . ", request $method $path not sent", LOG_ERR);
				$response['curl_error'] = 'UPTOSIGN_KEY_API is empty';
				return $response;
			}

			if (self::$circuitOpen) {
				dol_syslog("UptoSignAPIClient: circuit breaker is open after " . self::$authFailures . " authentication failures, request $method $path not sent", LOG_ERR);
				$response['curl_error'] = 'Too many consecutive authentication failures';
				return $response;
			}

			// A backoff armed by another process (webhook, previous cron run) also
			// applies here, otherwise the quiet period is only ever one process deep.
			$until = self::backoffUntil();
			if ($until > dol_now()) {
				dol_syslog("UptoSignAPIClient: backoff active until " . dol_print_date($until, '%Y-%m-%d %H:%M:%S') . ", request $method $path not sent", LOG_WARNING);
				$response['curl_error'] = 'API backoff active until ' . dol_print_date($until, '%Y-%m-%d %H:%M:%S');
				return $response;
			}
		}

		// Temporarily reduce log verbosity during HTTP calls
		$savedLogLevel = utsbackports_getDolGlobalString('SYSLOG_LEVEL', '');
		if (utsbackports_getDolGlobalString('UPTOSIGN_DISABLE_GETURL_DEBUG', '') != '') {
			$conf->global->SYSLOG_LEVEL = LOG_ERR;
		}

		$result = $this->httpCall($url, $method, $body, $this->buildHeaders($withBearer));

		$conf->global->SYSLOG_LEVEL = $savedLogLevel;

		if (!is_array($result)) {
			dol_syslog("UptoSignAPIClient: getURLContent returned non-array for $method $path", LOG_ERR);
			return $response;
		}

		$response['http_code'] = (int) ($result['http_code'] ?? 0);
		$response['content'] = $result['content'] ?? '';

		if ($withBearer) {
			$wasOpen = self::$circuitOpen;
			self::registerHttpResult($response['http_code']);

			if (!$wasOpen && self::$circuitOpen) {
				// Persist the quiet period the moment the breaker trips, so the next
				// process does not start a fresh burst of refused calls.
				self::armBackoff($this->db, 'HTTP ' . $response['http_code'] . ' on ' . $method . ' ' . $path);
			} elseif ($response['http_code'] >= 200 && $response['http_code'] < 300) {
				self::clearBackoff($this->db);
			}
		}

		if (!empty($result['curl_error_msg'])) {
			$response['curl_error'] = $result['curl_error_msg'];
			dol_syslog("UptoSignAPIClient CURL error: " . $result['curl_error_msg'], LOG_ERR);
		}

		// Try to decode JSON content (will be null for binary responses like PDF downloads)
		$decoded = json_decode($response['content'], true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
			$response['data'] = $decoded;
		}

		return $response;
	}

	/**
	 * Send the HTTP request itself
	 *
	 * Isolated in its own method so tests can replace the network layer without
	 * duplicating the logic of request().
	 *
	 * @param string $url     Full URL
	 * @param string $method  HTTP method
	 * @param string $body    Request body
	 * @param array  $headers HTTP headers
	 * @return array|string   Raw getURLContent() result
	 */
	protected function httpCall($url, $method, $body, $headers)
	{
		return getURLContent($url, $method, $body, 1, $headers);
	}

	/**
	 * Verify API key validity (POST /api/profile)
	 *
	 * @return array API response
	 */
	public function getProfile()
	{
		$data = array('json' => array('email' => utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', '')));
		return $this->request('POST', '/api/profile', $data);
	}

	/**
	 * Create an account on the remote server (POST /api/register)
	 *
	 * @param string $firstname User first name
	 * @param string $lastname  User last name
	 * @param string $email     User email
	 * @param string $password  User password
	 * @return array API response
	 */
	public function createAccount($firstname, $lastname, $email, $password)
	{
		$data = array(
			'firstname' => $firstname,
			'lastname' => $lastname,
			'email' => $email,
			'password' => $password,
			'password_confirmation' => $password,
		);
		return $this->request('POST', '/api/register', $data, false);
	}

	/**
	 * Login with email and password (POST /api/login)
	 *
	 * @param string $email    User email
	 * @param string $password User password
	 * @return array API response
	 */
	public function login($email, $password)
	{
		$data = array(
			'email' => $email,
			'password' => $password,
		);
		return $this->request('POST', '/api/login', $data, false);
	}

	/**
	 * Health check on remote service (GET /api/ruok)
	 *
	 * @param string $email    Account email
	 * @param string $protocol Protocol version
	 * @return array API response
	 */
	public function healthCheck($email, $protocol)
	{
		$data = array('json' => array('email' => $email, 'protocol' => $protocol));
		return $this->request('GET', '/api/ruok', $data);
	}

	/**
	 * Create a seal or sign procedure (POST /api/seals or /api/documents)
	 *
	 * @param array  $data      Procedure data (pdf, members, hooks, etc.)
	 * @param string $procedure "seal" or "sign"
	 * @return array API response
	 */
	public function createProcedure($data, $procedure = 'seal')
	{
		$path = ($procedure == 'seal') ? '/api/seals' : '/api/documents';
		return $this->request('POST', $path, $data);
	}

	/**
	 * Get document status (GET /api/documents/{signId})
	 *
	 * @param string $signId Remote document ID
	 * @return array API response
	 */
	public function getDocumentStatus($signId)
	{
		return $this->request('GET', '/api/documents/' . $signId);
	}

	/**
	 * Download signed document (GET /api/documents/{signId}/download)
	 *
	 * @param string $signId Remote document ID
	 * @return array API response (content contains binary PDF)
	 */
	public function downloadDocument($signId)
	{
		$data = array('email' => utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', ''));
		return $this->request('GET', '/api/documents/' . $signId . '/download', $data);
	}

	/**
	 * Download proof document (GET /api/documents/{signId}/downloadProof)
	 *
	 * @param string $signId Remote document ID
	 * @return array API response (content contains binary PDF)
	 */
	public function downloadProof($signId)
	{
		$data = array('email' => utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', ''));
		return $this->request('GET', '/api/documents/' . $signId . '/downloadProof', $data);
	}

	/**
	 * Delete a remote document (DELETE /api/documents/{signId})
	 *
	 * @param string $signId Remote document ID
	 * @return array API response
	 */
	public function deleteDocument($signId)
	{
		return $this->request('DELETE', '/api/documents/' . $signId);
	}
}
