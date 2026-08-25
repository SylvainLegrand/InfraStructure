<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This file is part of Multismtp.
	* File added by InfraS (2026-08) : SMTP2GO API helper functions.
	*
	* Multismtp is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* Multismtp is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with Multismtp.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	*	\file		./custom/multismtp/lib/smtp2go.lib.php
	*	\ingroup	Multismtp
	*	\brief		Functions used by Multismtp module
	************************************************/

	// Libraries************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';	// InfraS add : dol_cache_refresh() / dol_filecache() / dol_readcachefile()


	if (!defined('MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS')) {
		define('MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS', 64);
	}
	if (!defined('MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES')) {
		define('MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES', 3);	// InfraS add : minimum number of character classes (lower/upper/digit/symbol) required, out of 4
	}

	/**
	* Directory of the local file cache used to avoid repeating SMTP2GO API read calls on every page render
	*
	* @return	string	Full path of the cache directory
	*/
	function multismtp_smtp2go_cache_dir()	// InfraS add
	{
		return DOL_DATA_ROOT.'/multismtp/temp/smtp2go';
	}

	/**
	* Clear the local cache of the SMTP users list of a given scope, so the next page render reflects a change immediately
	* instead of waiting for MULTISMTP_SMTP2GO_CACHE_TTL to elapse. Call after a successful add/edit/remove of a SMTP user.
	*
	* @param	string	$subaccount_id	Subaccount ID whose users list cache to invalidate ('' = main account)
	* @return	void
	*/
	function multismtp_smtp2go_cache_clear_users($subaccount_id = '')	// InfraS add
	{
		global $conf;

		$filename	= '/users-e'.$conf->entity.'-'.(!empty($subaccount_id) ? $subaccount_id : 'main').'.cache';
		@unlink(multismtp_smtp2go_cache_dir().$filename);
	}

	/**
	* Mirror a SMTP2GO SMTP user's identifier/password into the native per-user SMTP config (llx_user2smtp, used by
	* Dolibarr's own mail sending, see Multismtp::replaceConfiguration()), for the Dolibarr user linked to that
	* SMTP2GO account. Call after every create/edit/remove of a SMTP2GO SMTP user that has a fk_user link, so the
	* two tables never drift apart. Pass empty $username/$password (ex: after a removal) to clear the native fields.
	*
	* @param	int|null	$fk_user	Dolibarr user id linked to the SMTP2GO account (empty/null = nothing to do)
	* @param	string		$username	SMTP2GO username, mirrored into MAIN_MAIL_SMTPS_ID for that user ('' clears it)
	* @param	string		$password	Plaintext SMTP2GO password, mirrored into MAIN_MAIL_SMTPS_PW for that user ('' clears it) — stored encrypted like every other value of that table
	* @return	void
	*/
	function multismtp_smtp2go_sync_native_smtp_credentials($fk_user, $username, $password)	// InfraS add
	{
		global $db;

		if (empty($fk_user)) {
			return;
		}
		dol_include_once('/multismtp/class/Multismtp.class.php');
		$fuser	= new User($db);
		if ($fuser->fetch($fk_user) <= 0) {
			dol_syslog(__FUNCTION__.': Dolibarr user '.$fk_user.' not found, cannot sync native SMTP credentials', LOG_ERR);
			return;
		}
		$multismtp	= new Multismtp($db);
		try {
			$multismtp->fetch($fuser);	// load whatever native SMTP/IMAP settings already exist for this user, so update() below does not wipe them
			$multismtp->fk_user	= $fk_user;
			$multismtp->smtp_id	= $username;
			$multismtp->smtp_pw	= $password;
			$multismtp->update();
		} catch (Exception $e) {
			dol_syslog(__FUNCTION__.': '.$e->getMessage(), LOG_ERR);
		}
	}
 
	/**
	* Estimate the entropy of a password in bits, based on its length and the number of distinct characters used.
	*
	* @param	string	$password	Password to evaluate
	* @return	float				Estimated entropy in bits (0.0 for an empty string)
	*/
	function multismtp_smtp2go_password_entropy_bits($password)
	{
		$password = (string) $password;
		if ($password === '') {
			return 0.0;
		}
		$chars		= preg_split('//u', $password, -1, PREG_SPLIT_NO_EMPTY);
		if (!is_array($chars) || empty($chars)) {
			$chars	= str_split($password);	// preg_split can return false on invalid UTF-8 ; fall back to a byte-based split
		}
		$length		= count($chars);
		$distinct	= max(count(array_unique($chars)), 2);	// avoid log2(1) = 0 for a repeated-char string
		return $length * (log($distinct) / log(2));
	}

	/**
	* Count how many of the 4 character classes (lowercase, uppercase, digit, symbol) are present in a password.
	* Used together with multismtp_smtp2go_password_entropy_bits() so a long low-diversity repeating string
	* (ex: 64x the same 2 alternating characters) cannot pass the strength check on entropy alone.
	*
	* @param	string	$password	Password to evaluate
	* @return	int					Number of classes present (0 to 4)
	*/
	function multismtp_smtp2go_password_classes_count($password)	// InfraS add
	{
		$password	= (string) $password;
		$count		= 0;
		$count		+= preg_match('/[a-z]/', $password) ? 1 : 0;
		$count		+= preg_match('/[A-Z]/', $password) ? 1 : 0;
		$count		+= preg_match('/[0-9]/', $password) ? 1 : 0;
		$count		+= preg_match('/[^a-zA-Z0-9]/', $password) ? 1 : 0;
		return $count;
	}

	/**
	* Check whether a password is built around a common/predictable base (dictionary word, keyboard walk,
	* product name...) or a purely sequential/repeated digit run. Complements the entropy and character-class
	* checks above, which measure character diversity but do not catch predictable patterns such as
	* "Password2026!" (see security review 2026-08-20 : NIST SP 800-63B recommends a blocklist check).
	*
	* @param	string	$password	Password to evaluate
	* @return	bool				true if the password matches a common/predictable pattern
	*/
	function multismtp_smtp2go_password_is_common($password)	// InfraS add
	{
		$password	= (string) $password;
		if ($password === '') {
			return false;
		}

		// Purely sequential or single-digit-repeated runs (ex: "123456", "000000", "987654321") : not caught
		// by the word blocklist below since leetspeak folding scrambles digits into unrelated letters.
		foreach (array('0123456789', '9876543210') as $sequence) {
			for ($i = 0; $i <= dol_strlen($sequence) - 6; $i++) {
				if (strpos($password, substr($sequence, $i, 6)) !== false) {
					return true;
				}
			}
		}
		for ($digit = 0; $digit <= 9; $digit++) {
			if (strpos($password, str_repeat((string) $digit, 6)) !== false) {
				return true;
			}
		}

		// Fold to lowercase letters only (leetspeak substitutions folded to their letter, everything else
		// stripped) so "P@ssw0rd2026!" reduces to a string still containing the blocklisted base "password".
		$normalized	= dol_strtolower($password);
		$normalized	= strtr($normalized, array('0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's', '!' => 'i'));
		$normalized	= preg_replace('/[^a-z]/', '', $normalized);

		dol_include_once('/multismtp/lib/smtp2go_password_blocklist.php');
		$blocklist	= multismtp_smtp2go_get_password_blocklist();
		foreach ($blocklist as $commonword) {
			if (dol_strlen($commonword) >= 4 && strpos($normalized, $commonword) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	* Call a SMTP2GO API v3 endpoint (JSON POST, authentication by API key header)
	*
	* @param	string	$endpoint	Endpoint relative to base url (ex: 'users/smtp/add', 'subaccount/add')
	* @param	array	$params		Request body parameters
	* @return	array				array('success' => bool, 'error' => string, 'error_code' => string, 'data' => array|null)
	*/
	function multismtp_smtp2go_request($endpoint, $params = array())
	{
		global $langs;		

		$apikey	= getDolGlobalString('MULTISMTP_SMTP2GO_API_KEY');
		if (empty($apikey)) {
			return array('success' => false, 'error' => $langs->trans('Smtp2goErrorNoApiKey'), 'error_code' => '', 'data' => null);	// InfraS change
		}
		$url		= 'https://api.smtp2go.com/v3/'.ltrim($endpoint, '/');
		$headers	= array('Content-Type: application/json',
							'Accept: application/json',
							'X-Smtp2go-Api-Key: '.$apikey,
						);
		$result		= getURLContent($url, 'POSTALREADYFORMATED', json_encode((object) $params), 1, $headers);
		if (!empty($result['curl_error_no'])) {
			dol_syslog(__FUNCTION__.' curl error on '.$endpoint.': '.$result['curl_error_msg'], LOG_ERR);
			return array('success' => false, 'error' => $result['curl_error_msg'], 'error_code' => '', 'data' => null);	// InfraS change
		}
		$json		= json_decode((string) $result['content'], true);
		$data		= (is_array($json) && isset($json['data'])) ? $json['data'] : null;
		if ($result['http_code'] != 200) {
			$error		= '';
			$errorcode	= (is_array($data) && !empty($data['error_code'])) ? $data['error_code'] : '';	// InfraS add : kept apart from $error so callers can act on it without parsing the message text
			if (is_array($data)) {
				if (!empty($data['error'])) {
					$error	= $data['error'];
				}
				if (!empty($errorcode)) {
					$error	.= ($error ? ' ' : '').'('.$errorcode.')';	// InfraS change
				}if (!empty($data['field_validation_errors'])) {
					$fieldvalidationerrors	= $data['field_validation_errors'];
					if (is_array($fieldvalidationerrors) && (isset($fieldvalidationerrors['fieldname']) || isset($fieldvalidationerrors['message']))) {
						$fieldvalidationerrors	= array($fieldvalidationerrors);
					}
					if (is_array($fieldvalidationerrors)) {
						foreach ($fieldvalidationerrors as $fielderror) {
							if (is_array($fielderror) && $fielderror['fieldname'] == 'email_password') {
								$error	= $fielderror['message'];
							}
						}
					}
				}
			}
			if (empty($error)) {
				$error	= 'HTTP '.$result['http_code'];
			}
			dol_syslog(__FUNCTION__.' API error on '.$endpoint.': '.$error, LOG_ERR);
			return array('success' => false, 'error' => $error, 'error_code' => $errorcode, 'data' => $data);	// InfraS change
		}
		return array('success' => true, 'error' => '', 'error_code' => '', 'data' => $data);	// InfraS change
	}

	/**
	* Extract the list of records from a SMTP2GO API "data" payload (list key name varies by endpoint)
	*
	* @param	array|null	$data	'data' part of the API response
	* @param	array		$keys	Candidate keys holding the list (tried in order)
	* @return	array				List of records (empty array if none)
	*/
	function multismtp_smtp2go_extract_list($data, $keys)
	{
		if (!is_array($data)) {
			return array();
		}
		foreach ($keys as $key) {
			if (!empty($data[$key]) && is_array($data[$key])) {
				return $data[$key];
			}
		}
		// Some endpoints return the list directly as a numeric array
		if (array_keys($data) === range(0, count($data) - 1)) {
			return $data;
		}
		return array();
	}

	/**
	* Get the list of SMTP users of the SMTP2GO account (or of one of its subaccounts)
	*
	* @param	string	$subaccount_id	Subaccount ID to make the call on behalf of ('' = main account)
	* @return	array					array('success' => bool, 'error' => string, 'users' => array)
	*/
	function multismtp_smtp2go_get_smtp_users($subaccount_id = '')
	{
		global $conf;

		// InfraS add begin : local file cache to avoid repeating this API call on every page render
		$cachedir	= multismtp_smtp2go_cache_dir();
		$cachetime	= getDolGlobalInt('MULTISMTP_SMTP2GO_CACHE_TTL', 60);
		$filename	= '/users-e'.$conf->entity.'-'.(!empty($subaccount_id) ? $subaccount_id : 'main').'.cache';
		if (!dol_cache_refresh($cachedir, $filename, $cachetime)) {
			return dol_readcachefile($cachedir, $filename);
		}
		// InfraS add end

		$params	= array();
		if (!empty($subaccount_id)) {
			$params['subaccount_id']	= $subaccount_id;
		}
		$result	= multismtp_smtp2go_request('users/smtp/view', $params);
		$users	= $result['success'] ? multismtp_smtp2go_extract_list($result['data'], array('results', 'users', 'smtp_users')) : array();
		$return	= array('success' => $result['success'], 'error' => $result['error'], 'users' => $users);
		dol_filecache($cachedir, $filename, $return);	// InfraS add
		return $return;
	}

	/**
	* Get email activity of the last N days, aggregated per day (like the SMTP2GO dashboard sending graph).
	* The SMTP2GO API has no ready-made per-day statistics endpoint, so this fetches the individual
	* events via activity/search (paginated) and buckets them by calendar day (UTC).
	*
	* @param	int		$days		Number of days to cover, ending today (UTC)
	* @return	array				array('success' => bool, 'error' => string, 'days' => array('YYYY-MM-DD' => array('sent','opens','clicks','bounces')), 'truncated' => bool)
	*/
	function multismtp_smtp2go_get_daily_activity($days = 7)
	{
		global $conf;

		// InfraS add begin : local file cache to avoid repeating up to $maxpages paginated API calls on every page render
		$cachedir	= multismtp_smtp2go_cache_dir();
		$cachetime	= getDolGlobalInt('MULTISMTP_SMTP2GO_CACHE_TTL', 60);
		$filename	= '/activity-e'.$conf->entity.'-'.((int) $days).'d.cache';
		if (!dol_cache_refresh($cachedir, $filename, $cachetime)) {
			return dol_readcachefile($cachedir, $filename);
		}
		// InfraS add end

		$now		= dol_now();
		$events		= array();
		$truncated	= false;
		$maxpages	= 10;
		$params		= array('start_date'	=> dol_print_date($now - ($days * 86400), '%Y-%m-%dT%H:%M:%SZ', 'gmt'),
							'end_date'		=> dol_print_date($now, '%Y-%m-%dT%H:%M:%SZ', 'gmt'),
							'event_types'	=> array('processed', 'opened', 'clicked', 'soft-bounced', 'hard-bounced'),
							'limit'			=> 1000,
						);
		// Safety backstop (10 x 1000 events) against a runaway pagination loop
		for ($page = 0; $page < $maxpages; $page++) {
			$result	= multismtp_smtp2go_request('activity/search', $params);
			if (!$result['success']) {
				$return	= array('success' => false, 'error' => $result['error'], 'days' => array(), 'truncated' => false);
				dol_filecache($cachedir, $filename, $return);	// InfraS add
				return $return;
			}
			$data			= $result['data'];
			$events			= array_merge($events, is_array($data) && !empty($data['events']) ? $data['events'] : array());
			$continuetoken	= (is_array($data) && !empty($data['continue_token'])) ? $data['continue_token'] : '';
			if (empty($continuetoken)) {
				break;
			}
			if ($page == $maxpages - 1) {
				$truncated	= true;	// More pages remained but the safety backstop was reached
			}
			$params['continue_token']	= $continuetoken;
		}

		// Init one bucket per day (oldest to newest) so days without activity still show as 0
		$daybuckets	= array();
		for ($i = $days - 1; $i >= 0; $i--) {
			$daykey					= dol_print_date($now - ($i * 86400), '%Y-%m-%d', 'gmt');
			$daybuckets[$daykey]	= array('sent' => 0, 'opens' => 0, 'clicks' => 0, 'bounces' => 0);
		}

		foreach ($events as $event) {
			if (!is_array($event) || empty($event['date']) || empty($event['event'])) {
				continue;
			}
			$daykey	= substr($event['date'], 0, 10);	// 'YYYY-MM-DD' prefix of the ISO date
			if (!isset($daybuckets[$daykey])) {
				continue;
			}
			switch ($event['event']) {
				case 'processed':
					$daybuckets[$daykey]['sent']++;
					break;
				case 'opened':
					$daybuckets[$daykey]['opens']++;
					break;
				case 'clicked':
					$daybuckets[$daykey]['clicks']++;
					break;
				case 'soft-bounced':
				case 'hard-bounced':
					$daybuckets[$daykey]['bounces']++;
					break;
			}
		}
		$return	= array('success' => true, 'error' => '', 'days' => $daybuckets, 'truncated' => $truncated);
		dol_filecache($cachedir, $filename, $return);	// InfraS add
		return $return;
	}

	/**
	* Get the list of subaccounts of the SMTP2GO account
	*
	* @return	array	array('success' => bool, 'error' => string, 'subaccounts' => array)
	*/
	function multismtp_smtp2go_get_subaccounts()
	{
		$result			= multismtp_smtp2go_request('subaccounts/search', array('states' => 'all', 'page_size' => 100));
		$subaccounts	= $result['success'] ? multismtp_smtp2go_extract_list($result['data'], array('results', 'subaccounts')) : array();
		return array('success' => $result['success'], 'error' => $result['error'], 'subaccounts' => $subaccounts);
	}

	/**
	* Find a subaccount by its exact name (case insensitive)
	*
	* @param	string	$name	Subaccount name to search for
	* @return	array			array('success' => bool, 'error' => string, 'error_code' => string, 'subaccount' => array|null)
	*/
	function multismtp_smtp2go_find_subaccount($name)
	{
		$result			= multismtp_smtp2go_request('subaccounts/search', array('fuzzy_search' => true, 'search_terms' => array($name), 'states' => 'all', 'page_size' => 100));
		$subaccounts	= $result['success'] ? multismtp_smtp2go_extract_list($result['data'], array('results', 'subaccounts')) : array();
		$found			= null;
		foreach ($subaccounts as $subaccount) {
			if (is_array($subaccount) && !empty($subaccount['name']) && dol_strtolower(trim($subaccount['name'])) == dol_strtolower(trim($name))) {
				$found	= $subaccount;
				break;
			}
		}
		return array('success' => $result['success'], 'error' => $result['error'], 'error_code' => $result['error_code'], 'subaccount' => $found);	// InfraS change
	}

	/**
	* Get the identifier linked with user ID. Ask to the DB what is the username and password of the user ID.
	*
	* @param	string	$user_id	User ID to search for
	* @return	array				array('username' => '', 'password' => '') — password is returned already decrypted, ready to use
	*/
	function multismtp_smtp2go_get_user_credentials($user_id)
	{
		global $db;

		$sql	= 'SELECT username, password FROM '.$db->prefix().'user_smtp2go WHERE fk_user = '.$db->escape($user_id);
		$resql	= $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				$obj = $db->fetch_object($resql);
				return array('username' => $obj->username, 'password' => !empty($obj->password) ? dolDecrypt($obj->password) : '');	// InfraS change : was returned still encrypted, breaking every caller expecting a usable password
			}
		}
		return array('username' => '', 'password' => '');
	}

	/**
	* Get the identifier linked with user ID. Ask to the DB what is the username and password of the user ID.
	*
	* @param	string	$username	Username to search for
	* @return	string				Password of the user, or empty string if not found
	*/
	function multismtp_smtp2go_get_user_pwd($username)
	{
		global $db;

		$sql	= 'SELECT username, password FROM '.$db->prefix().'user_smtp2go WHERE username = "'.$db->escape($username).'"';
		$resql	= $db->query($sql);
		if ($resql) {
			if ($db->num_rows($resql) > 0) {
				$obj = $db->fetch_object($resql);
				return $obj->password;
			}
		}
		return '';
	}

	/**
	* Get the email summary statistics of the SMTP2GO account owning the configured API key
	* (quota/cycle usage, opens, clicks, bounces, spam, unsubscribes). Not usable with a
	* subaccount_id from a master account key : the API always returns the stats of the
	* account the key belongs to.
	*
	* @return	array	array('success' => bool, 'error' => string, 'stats' => array|null)
	*/
	function multismtp_smtp2go_get_email_stats()
	{
		global $conf;

		// InfraS add begin : local file cache to avoid repeating this API call on every page render
		$cachedir	= multismtp_smtp2go_cache_dir();
		$cachetime	= getDolGlobalInt('MULTISMTP_SMTP2GO_CACHE_TTL', 60);
		$filename	= '/stats-e'.$conf->entity.'.cache';
		if (!dol_cache_refresh($cachedir, $filename, $cachetime)) {
			return dol_readcachefile($cachedir, $filename);
		}
		// InfraS add end

		$result	= multismtp_smtp2go_request('stats/email_summary', array());
		$stats	= ($result['success'] && is_array($result['data'])) ? $result['data'] : null;
		$return	= array('success' => $result['success'], 'error' => $result['error'], 'stats' => $stats);
		dol_filecache($cachedir, $filename, $return);	// InfraS add
		return $return;
	}

	/**
	*	Get a title with picto
	*
	*	@param	string	$title				Title to show (HTML sanitized content). Can be a string with a <br> as a substring.
	*	@param	string	$morehtmlright		Added message to show on right
	*	@param	string	$morehtmlcenter		Added message to show on center
	* 	@return	string
	*/
	function multismtp_smtp2go_title($title, $morehtmlright = '', $morehtmlcenter = '')
	{
		$out = '';
		$out .= '<table class="centpercent table-fiche-title">
					<tr class="toptitle">
						<td class="nobordernopadding valignmiddle col-title">
							<div class="titre inline-block">
								<span class="inline-block uppercase valignmiddle">'.$title.'</span>
							</div>
						</td>';
		if (dol_strlen($morehtmlcenter)) {
			$out .= '	<td class="nobordernopadding center valignmiddle col-center">'.$morehtmlcenter.'</td>';
		}
		if (dol_strlen($morehtmlright)) {
			$out .= '	<td class="nobordernopadding titre_right wordbreakimp right valignmiddle col-right">'.$morehtmlright.'</td>';
		}
		$out .= '	</tr>
				</table>';
		return $out;
	}
