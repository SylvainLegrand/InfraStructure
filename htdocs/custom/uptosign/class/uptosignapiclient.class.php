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
	 * @var DoliDB Database handler
	 */
	private $db;

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
	 * Build common HTTP headers for API requests
	 *
	 * @param bool $withBearer Include Authorization Bearer header
	 * @param bool $isJson     Include Content-Type application/json header
	 * @return array           Array of header strings
	 */
	private function buildHeaders($withBearer = true, $isJson = true)
	{
		$headers = array();
		$headers[] = 'User-Agent: ' . uptosignuserAgent();
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

		dol_syslog("UptoSignAPIClient::request $method $path");

		// Temporarily reduce log verbosity during HTTP calls
		$savedLogLevel = utsbackports_getDolGlobalString('SYSLOG_LEVEL', '');
		if (utsbackports_getDolGlobalString('UPTOSIGN_DISABLE_GETURL_DEBUG', '') != '') {
			$conf->global->SYSLOG_LEVEL = LOG_ERR;
		}

		$result = getURLContent($url, $method, $body, 1, $this->buildHeaders($withBearer));

		$conf->global->SYSLOG_LEVEL = $savedLogLevel;

		$response = array(
			'http_code' => 0,
			'content' => '',
			'data' => null,
			'curl_error' => '',
		);

		if (!is_array($result)) {
			dol_syslog("UptoSignAPIClient: getURLContent returned non-array for $method $path", LOG_ERR);
			return $response;
		}

		$response['http_code'] = (int) ($result['http_code'] ?? 0);
		$response['content'] = $result['content'] ?? '';

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
