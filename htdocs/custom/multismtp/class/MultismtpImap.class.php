<?php
/* Copyright (C) 2025		Open-Dsi	<support@open-dsi.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *     \file        htdocs/custom/multismtp/class/MultismtpImap.class.php
 *     \ingroup     multismtp
 *     \brief       This file is a class to manage Imap connection
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\InvalidWhereQueryCriteriaException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;
use Webklex\PHPIMAP\Support\FolderCollection;


/**
 * Class for MultismtpImap
 */
class MultismtpImap extends CommonObject
{
	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
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
	 * Imap handler
	 * @var resource|IMAP\Connection
	 */
	public $imap;
	/**
	 * Imap client handler
	 * @var Client
	 */
	public $imap_client;

	/**
	 * Imap host
	 * @var string
	 */
	public $host;
	/**
	 * Imap port
	 * @var int
	 */
	public $port;
	/**
	 * Imap login
	 * @var string
	 */
	public $login;
	/**
	 * Imap password
	 * @var string
	 */
	public $password;
	/**
	 * Imap authentication type
	 * @var string
	 */
	public $authentication_type;
	/**
	 * Imap encryption
	 * @var string
	 */
	public $imap_encryption;
	/**
	 * Imap no rsh
	 * @var bool
	 */
	public $norsh;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * Checks if the IMAP function is enabled
	 *
	 * @param	bool	$only_library	Test only if the imap function or library exist
	 * @return	bool
	 */
	public static function isEnabled($only_library = false)
	{
		require_once DOL_DOCUMENT_ROOT.'/includes/webklex/php-imap/vendor/autoload.php';
		return ($only_library || getDolGlobalString('MULTISMTP_IMAP_ENABLED')) && (function_exists('imap_open') || (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP') && class_exists("Webklex\\PHPIMAP\\ClientManager")));
	}

	/**
	 * Opens an IMAP connection
	 *
	 * @param	string	$host				Address
	 * @param	int		$port				Port
	 * @param	string	$login				Login
	 * @param	string	$auth_type			Authentication type ('LOGIN' or 'XOAUTH2')
	 * @param	string	$password			Password (When authentication type = 'LOGIN') or Token (When authentication type = 'XOAUTH2')
	 * @param	string	$imap_encryption	Imap encryption (tls, ssl, ...)
	 * @param	bool	$norsh				No rsh
	 * @return	int							Result <0 if KO, >0 if OK
	 */
	public function connect($host, $port, $login, $auth_type, $password = '', $imap_encryption = '', $norsh = false)
	{
		global $conf, $langs;
		$langs->load('multismtp@multismtp');
		$this->errors = array();
		$this->error = '';

		if (!self::isEnabled()) {
			$this->error = $langs->trans("IMAPNotAvailable");
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
			return -1;
		}

		// Close previous connection
		$this->disconnect();

		$this->host = $host;
		$this->port = $port;
		$this->login = $login;
		$this->authentication_type = $auth_type;
		$this->password = $password;
		$this->imap_encryption = $imap_encryption;
		$this->norsh = $norsh;

		if ($this->authentication_type == 'XOAUTH2' && getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			// Mode OAUth2 with PHP-IMAP
			$authentication = 'oauth';
		} elseif ($this->authentication_type == 'LOGIN' || empty($this->authentication_type)) {
			// Mode login/pass with PHP-IMAP
			$authentication = 'login';
		} else {
			$this->error = $langs->trans("MultismtpErrorAuthTypeNotSupported", $this->authentication_type);
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
			return -1;
		}

		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			try {
				$cm = new ClientManager();
				$this->imap_client = $cm->make([
					'host' => $this->host,
					'port' => $this->port,
					'encryption' => !empty($this->imap_encryption) ? $this->imap_encryption : false,
					'validate_cert' => true,
					'protocol' => 'imap',
					'username' => $this->login,
					'password' => $this->password,
					'authentication' => $authentication,
				]);

				$this->imap_client->connect();
			} catch (ConnectionFailedException $e) {
				$this->error = $e->getMessage();
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Connect - Error : " . $this->error, LOG_ERR);
				return -1;
			}
		} else {
			$connectstringserver = $this->getConnectStringIMAP();

			$this->imap = imap_open($connectstringserver, $this->login, $password, 0, 1);
			if (!$this->imap) {
				$this->error = 'Failed to open IMAP connection ' . $connectstringserver . ' ' . imap_last_error();
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Connect - Error : " . $this->error, LOG_ERR);
				imap_errors(); // Clear stack of errors.
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Close an IMAP connection
	 * @return void
	 */
	public function disconnect()
	{
		if (isset($this->imap)) imap_close($this->imap);
		if (isset($this->imap_client)) $this->imap_client->disconnect();
		$this->imap = null;
		$this->imap_client = null;
	}

	/**
	 * Return the connectstring to use with IMAP connection function
	 *
	 * @return string
	 */
	public function getConnectStringIMAP()
	{
		global $conf;

		// Connect to IMAP
		$flags = '/service=imap'; // IMAP
		if (!empty($conf->global->IMAP_FORCE_TLS)) {
			$flags .= '/tls';
		} elseif (empty($this->imap_encryption) || ($this->imap_encryption == 'ssl' && !empty($conf->global->IMAP_FORCE_NOSSL))) {
			$flags .= '';
		} else {
			$flags .= '/' . $this->imap_encryption;
		}

		if (getDolGlobalString('MULTISMTP_IMAP_NOVALIDATECERT')) {
			$flags .= '/novalidate-cert';
		}
		//$flags.='/readonly';
		//$flags.='/debug';
		if (!empty($this->norsh) || !empty($conf->global->IMAP_FORCE_NORSH)) {
			$flags .= '/norsh';
		}
		//Used in shared mailbox from Office365
		if (strpos($this->login, '/') != false) {
			$partofauth = explode('/', $this->login);
			$flags .= '/authuser=' . $partofauth[0] . '/user=' . $partofauth[1];
		}

		$connectstringserver = '{' . $this->host . ':' . $this->port . $flags . '}';

		return $connectstringserver;
	}

	/**
	 * Returns IMAP folders
	 * @return array|null		null if KO otherwise list of folders
	 */
	public function getImapFolders()
	{
		$return = array();

		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			if (!isset($this->imap_client)) {
				$this->error = 'Not connected to IMAP server';
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				return null;
			}

			try {
				$folders = $this->imap_client->getFolders();
				$flatFolders = $this->convertHierarchicalToFlatFoldersList($folders);
				foreach ($flatFolders as $key => $folder) {
					$return[$key] = $folder->full_name;
				}
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				return null;
			}
		} else {
			if (!isset($this->imap)) {
				$this->error = 'Not connected to IMAP server';
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				return null;
			}

			$connectStringServer = $this->getConnectStringIMAP();
			if ($list = imap_list($this->imap, $connectStringServer, '*')) {
				foreach ($list as $mailbox) {
					$return[$mailbox] = str_replace($connectStringServer, '', $mailbox);
				}
			} else {
				$this->error = 'Failed to get folders list ' . $connectStringServer . ' ' . imap_last_error();
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				imap_errors(); // Clear stack of errors.
				return null;
			}
		}

		return $return;
	}

	/**
	 * Convert hierarchical to flat folders list
	 * @param	FolderCollection	$folders	List of folder
	 * @return	array							Flat folders list
	 */
	public function convertHierarchicalToFlatFoldersList($folders)
	{
		$list = array();

		foreach ($folders as $folder) {
			if (!empty($folder->children)) {
				$list += $this->convertHierarchicalToFlatFoldersList($folder->children);
			} else {
				$list[$folder->path] = $folder;
			}
		}

		return $list;
	}

	/**
	 * Saves the email into the folder
	 *
	 * @param	string	$folderPath		Folder to save into
	 * @param	string	$mail		Mail to save
	 * @return	int					Result <0 if KO, >0 if OK
	 */
	public function saveMail($folderPath, $mail)
	{
		if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
			if (!isset($this->imap_client)) {
				$this->error = 'Not connected to IMAP server';
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				return -1;
			}

			try {
				$folders = $this->imap_client->getFolders();
				$flatFolders = $this->convertHierarchicalToFlatFoldersList($folders);
				if (!isset($flatFolders[$folderPath])) {
					throw new Exception('Folder ' . $folderPath . ' not found');
				}
				$flatFolders[$folderPath]->appendMessage($mail);
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				return -1;
			}
		} else {
			if (!isset($this->imap)) {
				$this->error = 'Not connected to IMAP server';
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				return -1;
			}

			//http://runnable.com/UnZFxM5V3x9TAABX/send-an-email-using-imap-and-save-it-to-the-sent-folder-for-php
			if (!imap_append($this->imap, $folderPath, $mail)) {
				$this->error = 'Failed to save mail into ' . $folderPath . ' ' . imap_last_error();
				$this->errors[] = $this->error;
				dol_syslog(__METHOD__ . " Error : " . $this->error, LOG_ERR);
				imap_errors(); // Clear stack of errors.
				return -1;
			}
		}

		return 1;
	}
}
