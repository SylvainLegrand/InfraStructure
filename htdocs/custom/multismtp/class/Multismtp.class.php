<?php
/**
 * Copyright © 2015-2016 Marcos García de La Fuente <hola@marcosgdf.com>
 * Copyright © 2026 	 Open-Dsi					<support@open-dsi.fr>
 *
 * This file is part of Multismtp.
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
 */

require_once DOL_DOCUMENT_ROOT . '/includes/OAuth/bootstrap.php';
dol_include_once('/multismtp/class/MultismtpImap.class.php');

use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;


class Multismtp
{
	/**
	 * User
	 * @var int
	 */
	public $fk_user;

	/**
	 * SMTP server
	 * @var string
	 */
	public $smtp_server;

	/**
	 * SMTP port
	 * @var string
	 */
	public $smtp_port;

	/**
	 * SMTP TLS
	 * @var bool
	 */
	public $smtp_tls;

	/**
	 * SMTP STARTTLS
	 * @var bool
	 */
	public $smtp_starttls;

	/**
	 * SMTP username
	 * @var string
	 */
	public $smtp_id;

	/**
	 * SMTP auth type (LOGIN or XOAUTH2)
	 * @var string
	 */
	public $smtp_auth_type;

	/**
	 * SMTP password
	 * @var string
	 */
	public $smtp_pw;

	/**
	 * SMTP oauth service
	 * @var string
	 */
	public $smtp_oauth_service;

	/**
	 * SMTP oauth provider
	 * @var string
	 */
	public $smtp_oauth_provider;

	/**
	 * SMTP oauth id
	 * @var string
	 */
	public $smtp_oauth_id;

	/**
	 * SMTP oauth secret
	 * @var string
	 */
	public $smtp_oauth_secret;

	/**
	 * SMTP oauth url authorize
	 * @var string
	 */
	public $smtp_oauth_url_authorize;

	/**
	 * SMTP oauth scope
	 * @var string
	 */
	public $smtp_oauth_scope;

	/**
	 * SMTP oauth tenant
	 * @var string
	 */
	public $smtp_oauth_tenant;

	/**
	 * IMAP server
	 * @var string
	 */
	public $imap_server;

	/**
	 * IMAP port
	 * @var string
	 */
	public $imap_port;

	/**
	 * IMAP TLS
	 * @var bool
	 */
	public $imap_tls;

	/**
	 * IMAP username
	 * @var string
	 */
	public $imap_id;

	/**
	 * IMAP auth type (LOGIN or XOAUTH2)
	 * @var string
	 */
	public $imap_auth_type;

	/**
	 * IMAP password
	 * @var string
	 */
	public $imap_pw;

	/**
	 * IMAP oauth service
	 * @var string
	 */
	public $imap_oauth_service;

	/**
	 * IMAP oauth provider
	 * @var string
	 */
	public $imap_oauth_provider;

	/**
	 * IMAP oauth id
	 * @var string
	 */
	public $imap_oauth_id;

	/**
	 * IMAP oauth secret
	 * @var string
	 */
	public $imap_oauth_secret;

	/**
	 * IMAP oauth url authorize
	 * @var string
	 */
	public $imap_oauth_url_authorize;

	/**
	 * IMAP oauth scope
	 * @var string
	 */
	public $imap_oauth_scope;

	/**
	 * IMAP oauth tenant
	 * @var string
	 */
	public $imap_oauth_tenant;

	/**
	 * IMAP folder
	 * @var string
	 */
	public $imap_folder;

	/**
	 * Database handler
	 * @var DoliDB
	 */
	public $db;
	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;
	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();
	/**
	 * @var string 		CRON output string
	 */
	public $output;
	/**
	 * Imap manager handler
	 * @var MultismtpImap
	 */
	public $imap;


	/**
	 * Multismtp constructor.
	 * Cannot use typehinting because of 3.4 compatibility
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->imap = new MultismtpImap($this->db);
	}

	/**
	 * Method to output saved errors
	 *
	 * @return	string		String with errors
	 */
	public function errorsToString()
	{
		return $this->error.(is_array($this->errors) ? (($this->error != '' ? ', ' : '').join(', ', $this->errors)) : '');
	}

	/**
	 * Retrieves SMTP configuration for the given user
	 *
	 * @param User $user User to retrieve data from
	 * @return bool true if everything was OK, false if no records found
	 * @throws Exception When DB error
	 */
	public function fetch(User $user)
	{
		$this->fk_user = $user->id;

		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'user2smtp WHERE fk_user = '.(int) $this->fk_user;

		$query = $this->db->query($sql);

		if (!$query) {
			throw new Exception($this->db->error());
		}

		if (!$this->db->num_rows($query)) {
			return false;
		}

		$resql = $this->db->fetch_object($query);

		$this->smtp_id = $resql->smtp_id ?? null;
		$this->smtp_auth_type = empty($resql->smtp_auth_type ?? null) ? 'LOGIN' : $resql->smtp_auth_type;
		$this->smtp_pw = !empty($resql->smtp_pw) ? dolDecrypt($resql->smtp_pw) : ($resql->smtp_pw ?? null);	// InfraS change : was stored/read in clear ; dolDecrypt() is a no-op on old plaintext rows (upgraded to encrypted on next save)
		$this->smtp_oauth_service = $resql->smtp_oauth_service ?? null;
		$this->smtp_oauth_provider = $resql->smtp_oauth_provider ?? null;
		$this->smtp_oauth_id = $resql->smtp_oauth_id ?? null;
		$this->smtp_oauth_secret = !empty($resql->smtp_oauth_secret) ? dolDecrypt($resql->smtp_oauth_secret) : ($resql->smtp_oauth_secret ?? null);	// InfraS change : same as smtp_pw above
		$this->smtp_oauth_url_authorize = $resql->smtp_oauth_url_authorize ?? null;
		$this->smtp_oauth_scope = $resql->smtp_oauth_scope ?? null;
		$this->smtp_oauth_tenant = $resql->smtp_oauth_tenant ?? null;
		$this->smtp_server = $resql->smtp_server ?? null;
		$this->smtp_port = $resql->smtp_port ?? null;
		$this->smtp_tls = (bool) ($resql->smtp_tls ?? false);
		$this->smtp_starttls = (bool) ($resql->smtp_starttls ?? false);
		$this->imap_id = $resql->imap_id ?? null;
		$this->imap_auth_type = empty($resql->imap_auth_type ?? null) ? 'LOGIN' : $resql->imap_auth_type;
		$this->imap_pw = !empty($resql->imap_pw) ? dolDecrypt($resql->imap_pw) : ($resql->imap_pw ?? null);	// InfraS change : was stored/read in clear ; dolDecrypt() is a no-op on old plaintext rows (upgraded to encrypted on next save)
		$this->imap_oauth_service = $resql->imap_oauth_service ?? null;
		$this->imap_oauth_provider = $resql->imap_oauth_provider ?? null;
		$this->imap_oauth_id = $resql->imap_oauth_id ?? null;
		$this->imap_oauth_secret = !empty($resql->imap_oauth_secret) ? dolDecrypt($resql->imap_oauth_secret) : ($resql->imap_oauth_secret ?? null);	// InfraS change : same as imap_pw above
		$this->imap_oauth_url_authorize = $resql->imap_oauth_url_authorize ?? null;
		$this->imap_oauth_scope = $resql->imap_oauth_scope ?? null;
		$this->imap_oauth_tenant = $resql->imap_oauth_tenant ?? null;
		$this->imap_server = $resql->imap_server ?? null;
		$this->imap_port = $resql->imap_port ?? null;
		$this->imap_tls = (bool) ($resql->imap_tls ?? false);
		$this->imap_folder = $resql->imap_folder ?? null;

		return true;
	}

	/**
	 * Updates the data
	 *
	 * @return bool
	 * @throws Exception When a DB error happens
	 * @throws BadMethodCallException When fk_user is not set
	 */
	public function update()
	{
		global $conf;

		if (!$this->fk_user) {
			throw new BadMethodCallException();
		}

		$smtp_server = null;
		$smtp_port = null;
		$smtp_tls = null;
		$smtp_starttls = null;
		$imap_server = null;
		$imap_port = null;
		$imap_tls = null;

		if (getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER') == 1) { // InfraS change
			$smtp_server = $this->smtp_server;

			if ($this->smtp_port !== null) {
				$smtp_port = (int) $this->smtp_port;
			}
			if ($this->smtp_tls !== null) {
				$smtp_tls = (int) $this->smtp_tls;
			}

			if ($this->smtp_starttls !== null) {
				$smtp_starttls = (int) $this->smtp_starttls;
			}
		}

		// if (!$conf->global->MULTISMTP_IMAP_CONF_SERVER) {
		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_SERVER'))) { // InfraS change
			$imap_server = $this->imap_server;

			if ($this->imap_tls !== null) {
				$imap_tls = (int) $this->imap_tls;
			}
		}

		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_PORT')) && $this->imap_port !== null) { // InfraS change
			$imap_port = (int) $this->imap_port;
		}

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "user2smtp (
			smtp_id, smtp_auth_type, smtp_pw, smtp_oauth_service, smtp_oauth_provider, smtp_oauth_id, smtp_oauth_secret, smtp_oauth_url_authorize, smtp_oauth_scope, smtp_oauth_tenant, smtp_server, smtp_port, smtp_tls, smtp_starttls,
			imap_id, imap_auth_type, imap_pw, imap_oauth_service, imap_oauth_provider, imap_oauth_id, imap_oauth_secret, imap_oauth_url_authorize, imap_oauth_scope, imap_oauth_tenant, imap_server, imap_port, imap_tls, imap_folder,
			fk_user
			) VALUES (
			" . ($this->smtp_id ? "'" . $this->db->escape($this->smtp_id) . "'" : "null") . ",
			" . ($this->smtp_auth_type ? "'" . $this->db->escape($this->smtp_auth_type) . "'" : "null") . ",
			" . ($this->smtp_pw ? "'" . $this->db->escape(dolEncrypt($this->smtp_pw)) . "'" : "null") . ",
			" . ($this->smtp_oauth_service ? "'" . $this->db->escape($this->smtp_oauth_service) . "'" : "null") . ",
			" . ($this->smtp_oauth_provider ? "'" . $this->db->escape($this->smtp_oauth_provider) . "'" : "null") . ",
			" . ($this->smtp_oauth_id ? "'" . $this->db->escape($this->smtp_oauth_id) . "'" : "null") . ",
			" . ($this->smtp_oauth_secret ? "'" . $this->db->escape(dolEncrypt($this->smtp_oauth_secret)) . "'" : "null") . ",
			" . ($this->smtp_oauth_url_authorize ? "'" . $this->db->escape($this->smtp_oauth_url_authorize) . "'" : "null") . ",
			" . ($this->smtp_oauth_scope ? "'" . $this->db->escape($this->smtp_oauth_scope) . "'" : "null") . ",
			" . ($this->smtp_oauth_tenant ? "'" . $this->db->escape($this->smtp_oauth_tenant) . "'" : "null") . ",
			" . ($smtp_server ? "'" . $this->db->escape($smtp_server) . "'" : "null") . ",
			" . ($smtp_port ?: "null") . ",
			" . ($smtp_tls ?: "null") . ",
			" . ($smtp_starttls ?: "null") . ",
			" . ($this->imap_id ? "'" . $this->db->escape($this->imap_id) . "'" : "null") . ",
			" . ($this->imap_auth_type ? "'" . $this->db->escape($this->imap_auth_type) . "'" : "null") . ",
			" . ($this->imap_pw ? "'" . $this->db->escape(dolEncrypt($this->imap_pw)) . "'" : "null") . ",
			" . ($this->imap_oauth_service ? "'" . $this->db->escape($this->imap_oauth_service) . "'" : "null") . ",
			" . ($this->imap_oauth_provider ? "'" . $this->db->escape($this->imap_oauth_provider) . "'" : "null") . ",
			" . ($this->imap_oauth_id ? "'" . $this->db->escape($this->imap_oauth_id) . "'" : "null") . ",
			" . ($this->imap_oauth_secret ? "'" . $this->db->escape(dolEncrypt($this->imap_oauth_secret)) . "'" : "null") . ",
			" . ($this->imap_oauth_url_authorize ? "'" . $this->db->escape($this->imap_oauth_url_authorize) . "'" : "null") . ",
			" . ($this->imap_oauth_scope ? "'" . $this->db->escape($this->imap_oauth_scope) . "'" : "null") . ",
			" . ($this->imap_oauth_tenant ? "'" . $this->db->escape($this->imap_oauth_tenant) . "'" : "null") . ",
			" . ($imap_server ? "'" . $this->db->escape($imap_server) . "'" : "null") . ",
			" . ($imap_port ?: "null") . ",
			" . ($imap_tls ?: "null") . ",
			" . ($this->imap_folder ? "'" . $this->db->escape($this->imap_folder) . "'" : "null") . ",
			" . (int) $this->fk_user . ")";

		if (!$this->db->query($sql)) {
			if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$sql = "UPDATE " . MAIN_DB_PREFIX . "user2smtp SET
		smtp_id = " . ($this->smtp_id ? "'" . $this->db->escape($this->smtp_id) . "'" : "null") . ",
		smtp_auth_type = " . ($this->smtp_auth_type ? "'" . $this->db->escape($this->smtp_auth_type) . "'" : "null") . ",
		smtp_pw = " . ($this->smtp_pw ? "'" . $this->db->escape(dolEncrypt($this->smtp_pw)) . "'" : "null") . ",
		smtp_oauth_service = " . ($this->smtp_oauth_service ? "'" . $this->db->escape($this->smtp_oauth_service) . "'" : "null") . ",
		smtp_oauth_provider = " . ($this->smtp_oauth_provider ? "'" . $this->db->escape($this->smtp_oauth_provider) . "'" : "null") . ",
		smtp_oauth_id = " . ($this->smtp_oauth_id ? "'" . $this->db->escape($this->smtp_oauth_id) . "'" : "null") . ",
		smtp_oauth_secret = " . ($this->smtp_oauth_secret ? "'" . $this->db->escape(dolEncrypt($this->smtp_oauth_secret)) . "'" : "null") . ",
		smtp_oauth_url_authorize = " . ($this->smtp_oauth_url_authorize ? "'" . $this->db->escape($this->smtp_oauth_url_authorize) . "'" : "null") . ",
		smtp_oauth_scope = " . ($this->smtp_oauth_scope ? "'" . $this->db->escape($this->smtp_oauth_scope) . "'" : "null") . ",
		smtp_oauth_tenant = " . ($this->smtp_oauth_tenant ? "'" . $this->db->escape($this->smtp_oauth_tenant) . "'" : "null") . ",
		smtp_server = " . ($smtp_server ? "'" . $this->db->escape($smtp_server) . "'" : "null") . ",
		smtp_port = " . ($smtp_port ?: "null") . ",
		smtp_tls = " . ($smtp_tls ?: "null") . ",
		smtp_starttls = " . ($smtp_starttls ?: "null") . ",
		imap_id = " . ($this->imap_id ? "'" . $this->db->escape($this->imap_id) . "'" : "null") . ",
		imap_auth_type = " . ($this->imap_auth_type ? "'" . $this->db->escape($this->imap_auth_type) . "'" : "null") . ",
		imap_pw = " . ($this->imap_pw ? "'" . $this->db->escape(dolEncrypt($this->imap_pw)) . "'" : "null") . ",
		imap_oauth_service = " . ($this->imap_oauth_service ? "'" . $this->db->escape($this->imap_oauth_service) . "'" : "null") . ",
		imap_oauth_provider = " . ($this->imap_oauth_provider ? "'" . $this->db->escape($this->imap_oauth_provider) . "'" : "null") . ",
		imap_oauth_id = " . ($this->imap_oauth_id ? "'" . $this->db->escape($this->imap_oauth_id) . "'" : "null") . ",
		imap_oauth_secret = " . ($this->imap_oauth_secret ? "'" . $this->db->escape(dolEncrypt($this->imap_oauth_secret)) . "'" : "null") . ",
		imap_oauth_url_authorize = " . ($this->imap_oauth_url_authorize ? "'" . $this->db->escape($this->imap_oauth_url_authorize) . "'" : "null") . ",
		imap_oauth_scope = " . ($this->imap_oauth_scope ? "'" . $this->db->escape($this->imap_oauth_scope) . "'" : "null") . ",
		imap_oauth_tenant = " . ($this->imap_oauth_tenant ? "'" . $this->db->escape($this->imap_oauth_tenant) . "'" : "null") . ",
		imap_server = " . ($imap_server ? "'" . $this->db->escape($imap_server) . "'" : "null") . ",
		imap_port = " . ($imap_port ?: "null") . ",
		imap_tls = " . ($imap_tls ?: "null") . ",
		imap_folder = " . ($this->imap_folder ? "'" . $this->db->escape($this->imap_folder) . "'" : "null") . "
		WHERE fk_user = " . (int) $this->fk_user;

				if (!$this->db->query($sql)) {
					throw new Exception($this->db->error());
				}
			} else {
				throw new Exception($this->db->error());
			}
		}

		return true;
	}

	/**
	 * Checks if IMAP configuration is set
	 * @return bool
	 */
	public function checkImapConfig()
	{
		$credentials = $this->getImapCredentials();

		return $credentials['id']
			&& $credentials['port']
			&& $credentials['server']
			&& ($credentials['auth_type'] === "XOAUTH2" ?
				($credentials['oauth_service']
					|| ($credentials['oauth_provider'] && $credentials['oauth_id'] && $credentials['oauth_secret'])
				)
				: $credentials['pw']
			);
	}

	/**
	 * Checks IMAP credentials
	 * @return bool
	 */
	public function checkImap()
	{
		$result = $this->openImapHandler();
		if ($result < 0) {
			return false;
		}

		$this->imap->disconnect();
		return true;
	}

	/**
	 * Checks if SMTP configuration is set
	 * @return bool
	 */
	public function checkSmtpConfig()
	{
		$credentials = $this->getSmtpCredentials();

		return $credentials['id']
			&& $credentials['port']
			&& $credentials['server']
			&& ($credentials['auth_type'] === "XOAUTH2" ?
				($credentials['oauth_service']
					|| ($credentials['oauth_provider'] && $credentials['oauth_id'] && $credentials['oauth_secret'])
				)
				: $credentials['pw']
			);
	}

	/**
	 * Checks SMTP credentials
	 *
	 * @param User $user Current user checking the server
	 * @return true|string In case of failure, a description of the error is returned
	 */
	public function checkSmtp(User $user)
	{
		global $conf;

		if (getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER') != 1) { // InfraS change
			return true;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

		$credentials = $this->getSmtpCredentials();

		$server = $credentials['server'];

		// If we use SSL/TLS
		// Since 4.0, an ssl:// is automatically added in CMailFile::check_server_port
		// therefore we have to call replaceConfiguration because of it
		if (versioncompare(versiondolibarrarray(), array(4,0,-5)) <= 0) {
			if (($credentials['tls'] || $credentials['starttls']) && function_exists('openssl_open')) {
				$server = 'ssl://'.$server;
			}
		} else {
			if (!self::replaceConfiguration($this->db, $user)) {
				return false;
			}
		}

		$mail = new CMailFile('', '', '', '');

		if (!$mail->check_server_port($server, $credentials['port'])) {
			return $mail->error;
		}

		return true;
	}

	/**
	 * Returns IMAP folders
	 * @return array|false
	 */
	public function getImapFolders()
	{
		$result = $this->openImapHandler();
		if ($result < 0) {
			return false;
		}

		$result = $this->imap->getImapFolders();
		if (!isset($result)) {
			$this->error = $this->imap->error;
			$this->errors = $this->imap->errors;
			return false;
		}

		$this->imap->disconnect();
		return $result;
	}

	/**
	 * Saves the email to the configured mailbox
	 * @param CMailFile $mailfile Emailing class
	 * @return bool
	 */
	public function saveMail(CMailFile $mailfile)
	{
		global $conf;

		$result = $this->openImapHandler();
		if ($result < 0) {
			return false;
		}

		if (getDolGlobalString('MAIN_MAIL_SENDMODE') == 'smtps') { // InfraS change
			$header = $mailfile->smtps->getHeader();
			$body = $mailfile->smtps->getBodyContent();

			$string = $header . $body;
		} elseif (getDolGlobalString('MAIN_MAIL_SENDMODE') == 'swiftmailer') { // InfraS change
			$string = $mailfile->message->toString();
		} else {
			$header = $mailfile->headers;
			$body = $mailfile->message;

			//Adding missing headers
			$header .= $mailfile->eol . 'To: ' . $mailfile->getValidAddress($mailfile->addr_to, 0, 1);
			$header .= $mailfile->eol . 'Subject: ' . $mailfile->encodetorfc2822($mailfile->subject);

			$string = $header . $mailfile->eol . $mailfile->eol . $body;
		}

		$result = $this->imap->saveMail($this->imap_folder, $string);
		if ($result < 0) {
			$this->error = $this->imap->error;
			$this->errors = $this->imap->errors;
			return false;
		}

		$this->imap->disconnect();
		return true;
	}

	/**
	 * Removes all IMAP credentials from the database
	 * @return bool
	 */
	public static function removeAllImapCredentials()
	{
		global $db;

		$sql = "UPDATE ".MAIN_DB_PREFIX."user2smtp SET imap_server = NULL,
imap_port  = NULL,
imap_tls  = NULL,
imap_id = NULL,
imap_auth_type = NULL,
imap_pw = NULL,
imap_oauth_service = NULL,
imap_oauth_provider = NULL,
imap_oauth_id = NULL,
imap_oauth_secret = NULL,
imap_oauth_url_authorize = NULL,
imap_oauth_scope = NULL,
imap_oauth_tenant = NULL,
imap_folder = NULL";

		if (!$db->query($sql)) {
			return false;
		}

		return true;
	}

	/**
	 * Removes all IMAP servers, ports and tls info
	 * @return bool
	 */
	public static function removeAllImapServerInfo()
	{
		global $db;

		$sql = "UPDATE ".MAIN_DB_PREFIX."user2smtp SET imap_server = NULL,
imap_port  = NULL,
imap_tls  = NULL";

		if (!$db->query($sql)) {
			return false;
		}

		return true;
	}

	/**
	 * Removes all IMAP credentials from the database
	 * @return bool
	 */
	public static function removeAllSmtpCredentials()
	{
		global $db;

		$sql = "UPDATE ".MAIN_DB_PREFIX."user2smtp SET smtp_server = NULL,
smtp_port  = NULL,
smtp_tls  = NULL,
smtp_starttls  = NULL,
smtp_id = NULL,
smtp_auth_type = NULL,
smtp_pw = NULL,
smtp_oauth_service = NULL,
smtp_oauth_provider = NULL,
smtp_oauth_id = NULL,
smtp_oauth_secret = NULL,
smtp_oauth_url_authorize = NULL,
smtp_oauth_scope = NULL,
smtp_oauth_tenant = NULL";

		if (!$db->query($sql)) {
			return false;
		}

		return true;
	}

	/**
	 * Returns an array with the SMTP credentials
	 * @return array
	 */
	public function getSmtpCredentials()
	{
		global $user, $conf;

		$user_id = !empty($this->fk_user) ? $this->fk_user : $user->id;

		$array = array(
			'server' => getDolGlobalString('MAIN_MAIL_SMTP_SERVER'), // InfraS change
			'port' => getDolGlobalInt('MAIN_MAIL_SMTP_PORT'), // InfraS change
			'tls' => getDolGlobalInt('MAIN_MAIL_EMAIL_TLS'), // InfraS change
			'starttls' => getDolGlobalInt('MAIN_MAIL_EMAIL_STARTTLS'), // InfraS change
			'id' => $this->smtp_id,
			'auth_type' => getDolGlobalString('MAIN_MAIL_SMTPS_AUTH_TYPE'), // InfraS change
			'pw' => $this->smtp_pw,
			'oauth_service' => getDolGlobalString('MAIN_MAIL_SMTPS_OAUTH_SERVICE'), // InfraS change
			'oauth_service_user' => '',
			'oauth_provider' => '',
			'oauth_id' => '',
			'oauth_secret' => '',
			'oauth_url_authorize' => '',
			'oauth_scope' => '',
			'oauth_tenant' => ''
		);

		if (empty($array['auth_type'])) $array['auth_type'] = 'LOGIN';

		if (getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER') == 1) { // InfraS change
			if ($this->smtp_port !== null) {
				$array['port'] = $this->smtp_port;
			}

			if ($this->smtp_tls !== null) {
				$array['tls'] = (int) $this->smtp_tls;
			}

			if ($this->smtp_starttls !== null) {
				$array['starttls'] = (int) $this->smtp_starttls;
			}

			if ($this->smtp_server) {
				$array['server'] = $this->smtp_server;
			}

			if ($this->smtp_auth_type) {
				$array['auth_type'] = $this->smtp_auth_type;
			}

			if ($this->smtp_oauth_service) {
				$array['oauth_service'] = $this->smtp_oauth_service;
				$array['oauth_provider'] = $this->smtp_oauth_provider;
				$array['oauth_id'] = $this->smtp_oauth_id;
				$array['oauth_secret'] = $this->smtp_oauth_secret;
				$array['oauth_url_authorize'] = $this->smtp_oauth_url_authorize;
				$array['oauth_scope'] = $this->smtp_oauth_scope;
				$array['oauth_tenant'] = $this->smtp_oauth_tenant;
			}
		}
		if ($array['auth_type'] == 'XOAUTH2') {
			if (empty($array['oauth_service'])) {
				$provider = str_replace('OAUTH_', '', strtoupper($array['oauth_provider']));
			} else {
				$provider = preg_replace('/-.*$/', '', $array['oauth_service']);
			}
			$array['oauth_service_user'] = $provider . '-MultiSmtpUser' . $user_id . 'Smtp';
		}

		return $array;
	}

	/**
	 * Returns an array with IMAP credentials
	 * @return array
	 */
	public function getImapCredentials()
	{
		global $user, $conf;

		$user_id = !empty($this->fk_user) ? $this->fk_user : $user->id;

		$array = array(
			'server' => $this->imap_server,
			'port' => $this->imap_port,
			'tls' => $this->imap_tls,
			'id' => $this->imap_id,
			'auth_type' => $this->imap_auth_type,
			'pw' => $this->imap_pw,
			'oauth_service' => $this->imap_oauth_service,
			'oauth_service_user' => '',
			'oauth_provider' => $this->imap_oauth_provider,
			'oauth_id' => $this->imap_oauth_id,
			'oauth_secret' => $this->imap_oauth_secret,
			'oauth_url_authorize' => $this->imap_oauth_url_authorize,
			'oauth_scope' => $this->imap_oauth_scope,
			'oauth_tenant' => $this->imap_oauth_tenant,
			'folder' => $this->imap_folder
		);

		if (empty($array['auth_type'])) $array['auth_type'] = 'LOGIN';

		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_SERVER'))) { // InfraS change
			$array['server'] = getDolGlobalString('MULTISMTP_IMAP_CONF_SERVER'); // InfraS change
			$array['tls'] = getDolGlobalInt('MULTISMTP_IMAP_CONF_TLS'); // InfraS change
		}

		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_PORT'))) { // InfraS change
			$array['port'] = getDolGlobalString('MULTISMTP_IMAP_CONF_PORT'); // InfraS change
		}

		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_SERVER'))) { // InfraS change
			$array['auth_type'] = getDolGlobalString('MULTISMTP_IMAP_CONF_AUTH_TYPE');
		}

		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_OAUTH_SERVICE'))) { // InfraS change
			$array['oauth_service'] = getDolGlobalString('MULTISMTP_IMAP_CONF_OAUTH_SERVICE'); // InfraS change
			$array['oauth_provider'] = '';
			$array['oauth_id'] = '';
			$array['oauth_secret'] = '';
			$array['oauth_url_authorize'] = '';
			$array['oauth_scope'] = '';
			$array['oauth_tenant'] = '';
		}
		if ($array['auth_type'] == 'XOAUTH2') {
			if (empty($array['oauth_service'])) {
				$provider = str_replace('OAUTH_', '', strtoupper($array['oauth_provider']));
			} else {
				$provider = preg_replace('/-.*$/', '', $array['oauth_service']);
			}
			$array['oauth_service_user'] = $provider . '-MultiSmtpUser' . $user_id . 'Imap';
		}

		return $array;
	}

	/**
	 * Opens an IMAP connection
	 * @return int			Result <0 if KO, >0 if OK
	 */
	private function openImapHandler()
	{
		global $langs;

		$credentials = $this->getImapCredentials();

		// Get password
		if ($credentials['auth_type'] == 'XOAUTH2' && getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			// Mode OAUth2 with PHP-IMAP
			$password = $this->getTokenOAuth2($credentials['oauth_service_user']);
			if (!isset($password)) {
				return -1;
			}
		} elseif ($credentials['auth_type'] == 'LOGIN' || empty($credentials['auth_type'])) {
			// Mode login/pass with PHP-IMAP
			$password = $credentials['pw'];
		} else {
			$this->error = $langs->trans("MultismtpErrorAuthTypeNotSupported", $credentials['auth_type']);
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
			return -1;
		}

		$result = $this->imap->connect($credentials['server'], $credentials['port'], $credentials['id'], $credentials['auth_type'], $password, $credentials['tls'] ? 'ssl' : '');
		if ($result < 0) {
			$this->error = $this->imap->error;
			$this->errors = $this->imap->errors;
			return -1;
		}

		return 1;
	}

	/**
	 * Get access token for OAuth2
	 *
	 * @param	string			$oauth_service		Imap oauth service
	 * @return	string|null							null if KO otherwise the access token
	 */
	public function getTokenOAuth2($oauth_service)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/oauth.lib.php';
		dol_include_once('/multismtp/lib/oauth.lib.php');
		$supportedoauth2array = getSupportedOauth2Array();
		$keyforsupportedoauth2array = $oauth_service;
		if (preg_match('/^.*-/', $keyforsupportedoauth2array)) {
			$keyforprovider = preg_replace('/^.*-/', '', $keyforsupportedoauth2array);
		} else {
			$keyforprovider = '';
		}
		$keyforsupportedoauth2array = preg_replace('/-.*$/', '', $keyforsupportedoauth2array);
		$keyforsupportedoauth2array = 'OAUTH_' . $keyforsupportedoauth2array . '_NAME';

		$OAUTH_SERVICENAME = (empty($supportedoauth2array[$keyforsupportedoauth2array]['name']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['name'] . ($keyforprovider ? '-' . $keyforprovider : ''));

		$storage = new DoliStorage($this->db, $conf, $keyforprovider);
		try {
			$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);

			$expire = true;
			// Is token expired or will token expire in the next 30 seconds
			if (is_object($tokenobj)) {
				$expire = ($tokenobj->getEndOfLife() !== -9002 && $tokenobj->getEndOfLife() !== -9001 && time() > ($tokenobj->getEndOfLife() - 30));
			}
			// Token expired so we refresh it
			if (is_object($tokenobj) && $expire) {
				$credentials = new Credentials(
					getDolGlobalString('OAUTH_' . $oauth_service . '_ID'),
					getDolGlobalString('OAUTH_' . $oauth_service . '_SECRET'),
					getDolGlobalString('OAUTH_' . $oauth_service . '_URLAUTHORIZE')
				);
				$serviceFactory = new \OAuth\ServiceFactory();
				$oauthname = explode('-', $OAUTH_SERVICENAME);
				// ex service is Google-Emails we need only the first part Google
				$apiService = $serviceFactory->createService($oauthname[0], $credentials, $storage, array());
				// We have to save the token because Google give it only once
				$refreshtoken = $tokenobj->getRefreshToken();
				$tokenobj = $apiService->refreshAccessToken($tokenobj);
				$tokenobj->setRefreshToken($refreshtoken);
				$storage->storeAccessToken($OAUTH_SERVICENAME, $tokenobj);
			}
			$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);
			if (is_object($tokenobj)) {
				$token = $tokenobj->getAccessToken();
			} else {
				$this->error = "Token not found";
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Retrieve access token - Error : " . $this->error, LOG_ERR);
				return null;
			}
		} catch (Exception $e) {
			// Return an error if token not found
			$this->error = $e->getMessage();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . " Retrieve access token - Error : " . $this->error, LOG_ERR);
			return null;
		}

		return $token;
	}

	/**
	 * Replaces Dolibarr email configuration with the provided one
	 * Error exception will be logged to Syslog
	 *
	 * @param	DoliDB		 $db		Database handler
	 * @param	User		 $user		Logged user
	 * @return	bool
	 */
	public static function replaceConfiguration(DoliDB $db, User $user)
	{
		global $conf;

		$multismtp = new Multismtp($db);

		try {
			/*
				Explication : plusieurs clients ont souhaité des comportements différents.
				L'un souhaitait autoriser les envois uniquement depuis les cartes. L'autre souhaitait une utilisation également pour les notifications.
				Pour cette raison nous avons introduit MULTISMTP_SENT_ONLY_FROM_CARD.

				Ce fix est vite fait. Si d'autres comportements fautifs se présentent, voici une autre option envisagée :
				Solution envisagée : déplacer dans la hook avant l'envoi des emails, puis tester si l'email de l'émetteur est identique à l'email de l'utilisateur courant.
			*/
			$act = GETPOST('action', 'alphanohtml');
			$send_from_card_by_user = $act == 'send' && GETPOST('fromtype', 'alphanohtml') == 'user';
			$not_sent_from_card = empty(getDolGlobalInt('MULTISMTP_SENT_ONLY_FROM_CARD')) && $act != 'send'; // InfraS change
			$result = $multismtp->fetch($user);
			if ($result > 0) {
				$smtpConfigCheck = $multismtp->checkSmtpConfig();
				$smtpCredentials = $multismtp->getSmtpCredentials();
				$imapConfigCheck = $multismtp->checkImapConfig();
				$imapCredentials = $multismtp->getImapCredentials();

				if ($smtpConfigCheck && ($send_from_card_by_user || $not_sent_from_card) && !self::currentPageInList([
						'/admin/mails.php',
						'/multismtp/admin/setup.php',
						'/multismtp/user.php',
					])) {
					$conf->global->MAIN_MAIL_SMTP_SERVER = $smtpCredentials['server'];
					$conf->global->MAIN_MAIL_SMTP_PORT = $smtpCredentials['port'];
					$conf->global->MAIN_MAIL_EMAIL_TLS = $smtpCredentials['tls'];
					$conf->global->MAIN_MAIL_EMAIL_STARTTLS = $smtpCredentials['starttls'];
					$conf->global->MAIN_MAIL_SMTPS_ID = $smtpCredentials['id'];
					$conf->global->MAIN_MAIL_SMTPS_AUTH_TYPE = $smtpCredentials['auth_type'];
					$conf->global->MAIN_MAIL_SMTPS_PW = $smtpCredentials['pw'];
					$conf->global->MAIN_MAIL_SMTPS_OAUTH_SERVICE = $smtpCredentials['oauth_service_user'];

					if (!empty(getDolGlobalString('MULTISMTP_REPLACE_MAIL_EMAIL_FROM'))) $conf->global->MAIN_MAIL_EMAIL_FROM = $smtpCredentials['id']; // InfraS change
				}

				// Manage Oauth2 globals for user
				if (!self::currentPageInList([
					'/admin/oauth.php',
					'/admin/oauthlogintokens.php',
					'/admin/mails.php',
					'/multismtp/admin/setup.php',
				])) {
					foreach (['smtp', 'imap'] as $type) {
						$credentials = ${$type . 'Credentials'};
						if ($credentials['auth_type'] == 'XOAUTH2' && ${$type . 'ConfigCheck'}) {
							if (!empty($credentials['oauth_service'])) {
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_ID'} = getDolGlobalString('OAUTH_' . $credentials['oauth_service'] . '_ID');
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_SECRET'} = getDolGlobalString('OAUTH_' . $credentials['oauth_service'] . '_SECRET');
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_URLAUTHORIZE'} = getDolGlobalString('OAUTH_' . $credentials['oauth_service'] . '_URLAUTHORIZE');
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_TENANT'} = getDolGlobalString('OAUTH_' . $credentials['oauth_service'] . '_TENANT');
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_SCOPE'} = getDolGlobalString('OAUTH_' . $credentials['oauth_service'] . '_SCOPE');
							} else {
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_ID'} = $credentials['oauth_id'];
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_SECRET'} = $credentials['oauth_secret'];
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_URLAUTHORIZE'} = $credentials['oauth_url_authorize'];
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_TENANT'} = $credentials['oauth_tenant'];
								$conf->global->{'OAUTH_' . $credentials['oauth_service_user'] . '_SCOPE'} = $credentials['oauth_scope'];
							}
						}
					}
				}
			}
		} catch (Exception $e) {
			dol_syslog('[multismtp] ' . $e->getMessage(), LOG_ERR);
			return false;
		}

		return true;
	}

	/**
	 * Test if current page is in the provided list
	 *
	 * @param	array		$pages		List of relative url page
	 * @return	bool
	 */
	public static function currentPageInList($pages)
	{
		foreach ($pages as $page) {
			if (preg_match('/' . preg_quote(dol_buildpath($page, 1), '/'). '$/i', $_SERVER['PHP_SELF'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 *  Refresh all expired or soon-to-expire OAuth2 tokens (cron)
	 *  Handles both official Dolibarr tokens and Multismtp per-user tokens.
	 *
	 *  @return	int				0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function cronRefreshOAuth2Tokens()
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/oauth.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';
		dol_include_once('/multismtp/lib/oauth.lib.php');

		$langs->load('multismtp@multismtp');

		$days = getDolGlobalInt('MULTISMTP_CRON_REFRESH_TOKEN_DAYS', 30);
		$now = dol_now();
		$threshold = $days * 86400;

		$sql = "SELECT rowid, service, token FROM " . MAIN_DB_PREFIX . "oauth_token";
		$sql .= " WHERE entity IN (" . getEntity('oauth_token') . ")";
		$sql .= " AND token IS NOT NULL AND token != ''";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
			return -1;
		}

		$atleastoneerror = 0;
		$output = '';

		while ($obj = $this->db->fetch_object($resql)) {
			$service = $obj->service;

			// Decrypt and unserialize token
			$tokendata = dolDecrypt($obj->token);
			if (empty($tokendata)) {
				continue;
			}
			$tokenobj = unserialize($tokendata);
			if (!($tokenobj instanceof \OAuth\Common\Token\TokenInterface)) {
				continue;
			}

			// Skip if no refresh token
			$refreshtoken = $tokenobj->getRefreshToken();
			if (empty($refreshtoken)) {
				continue;
			}

			// Skip if never expires or unknown
			$endOfLife = $tokenobj->getEndOfLife();
			if ($endOfLife == \OAuth\Common\Token\TokenInterface::EOL_NEVER_EXPIRES || $endOfLife == \OAuth\Common\Token\TokenInterface::EOL_UNKNOWN) {
				continue;
			}

			// Skip if not yet close to expiration
			if ($endOfLife + $threshold > $now) {
				continue;
			}

			// Parse service field: "Google-MultiSmtpUser42Smtp" → provider="Google", keyforprovider="MultiSmtpUser42Smtp"
			if (preg_match('/^(.*?)-(.+)$/', $service, $matches)) {
				$provider = $matches[1];
				$keyforprovider = $matches[2];
			} else {
				$provider = $service;
				$keyforprovider = '';
			}

			dol_syslog(__METHOD__ . " Refreshing token for service=" . $service . " (endOfLife=" . $endOfLife . ")", LOG_INFO);

			// For Multismtp tokens, load OAuth credentials into $conf->global via replaceConfiguration
			if (preg_match('/^MultiSmtpUser(\d+)(Smtp|Imap)$/', $keyforprovider, $usermatches)) {
				$userId = (int) $usermatches[1];
				$fuser = new User($this->db);
				$result = $fuser->fetch($userId);
				if ($result <= 0) {
					$output .= '<span style="color: red;">' . $langs->trans('MultismtpCronRefreshTokenErrorFetchUser', $service, $userId) . '</span><br>';
					dol_syslog(__METHOD__ . " Error: Could not fetch user ID " . $userId . " for service " . $service, LOG_ERR);
					$atleastoneerror++;
					continue;
				}
				Multismtp::replaceConfiguration($this->db, $fuser);
			}
			// For official Dolibarr tokens, credentials are already in $conf->global

			// Build credential key: uppercase provider + keyforprovider (matches const naming convention)
			$oauthServiceKey = strtoupper($provider) . ($keyforprovider ? '-' . $keyforprovider : '');

			// Skip tokens from other modules (not Multismtp, not official Dolibarr) that have no credentials configured
			if (!getDolGlobalString('OAUTH_' . $oauthServiceKey . '_ID')) {
				continue;
			}

			try {
				$storage = new DoliStorage($this->db, $conf, $keyforprovider);

				$credentials = new Credentials(
					getDolGlobalString('OAUTH_' . $oauthServiceKey . '_ID'),
					getDolGlobalString('OAUTH_' . $oauthServiceKey . '_SECRET'),
					getDolGlobalString('OAUTH_' . $oauthServiceKey . '_URLAUTHORIZE')
				);

				$serviceFactory = new \OAuth\ServiceFactory();
				$oauthname = explode('-', $service);
				// ex: service is "Google-MultiSmtpUser42Smtp", we need only the first part "Google"
				$apiService = $serviceFactory->createService($oauthname[0], $credentials, $storage, array());
				if (!$apiService) {
					$output .= '<span style="color: red;">' . $langs->trans('MultismtpCronRefreshTokenErrorCreateService', $service) . '</span><br>';
					dol_syslog(__METHOD__ . " Error: Could not create OAuth service for " . $service, LOG_ERR);
					$atleastoneerror++;
					continue;
				}

				// We have to save the refresh token because some providers (Google) give it only once
				$tokenobj = $storage->retrieveAccessToken($service);
				$refreshtoken = $tokenobj->getRefreshToken();
				$tokenobj = $apiService->refreshAccessToken($tokenobj);
				$tokenobj->setRefreshToken($refreshtoken);
				$storage->storeAccessToken($service, $tokenobj);

				$output .= '<span style="color: green;">' . $langs->trans('MultismtpCronRefreshTokenSuccess', $service) . '</span><br>';
				dol_syslog(__METHOD__ . " Token refreshed successfully for service=" . $service, LOG_INFO);
			} catch (Exception $e) {
				$output .= '<span style="color: red;">' . $langs->trans('MultismtpCronRefreshTokenError', $service, $e->getMessage()) . '</span><br>';
				dol_syslog(__METHOD__ . " Error refreshing token for service=" . $service . ": " . $e->getMessage(), LOG_ERR);
				$atleastoneerror++;
			}
		}
		$this->db->free($resql);

		if ($atleastoneerror) {
			$this->error = $output;
			return -1;
		}

		$this->error = "";
		$this->output = $output;

		return 0;
	}
}
