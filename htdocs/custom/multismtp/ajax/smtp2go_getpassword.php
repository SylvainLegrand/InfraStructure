<?php
	/**************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This file is part of Multismtp.
	* File added by InfraS (2026-08) : on-demand, single-username retrieval of a SMTP2GO user's decrypted password (admin only).
	* Used by the edit form to prefill the password field for the one user actually being edited, instead of embedding every
	* SMTP2GO user's decrypted password in the page HTML/JSON on every render of admin/smtp2go.php.
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
	**************************************************/

	/************************************************
	*	\file		./custom/multismtp/ajax/smtp2go_getpassword.php
	*	\ingroup	multismtp
	*	\brief		AJAX endpoint returning the decrypted password of a single SMTP2GO SMTP user (admin only)
	************************************************/

	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);	// Disables token renewal
	}
	if (!defined('NOREQUIREMENU')) {
		define('NOREQUIREMENU', 1);
	}
	if (!defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', 1);
	}
	if (!defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', 1);
	}
	if (!defined('NOREQUIRESOC')) {
		define('NOREQUIRESOC', 1);
	}

	// Load Dolibarr environment
	$res = 0;
	// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
	if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
		$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
	}
	// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
	$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
	while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
		$i--; $j--;
	}
	if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
		$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
	}
	if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
		$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
	}
	// Try main.inc.php using relative path
	if (!$res && file_exists("../../main.inc.php")) {
		$res = @include "../../main.inc.php";
	}
	if (!$res && file_exists("../../../main.inc.php")) {
		$res = @include "../../../main.inc.php";
	}
	if (!$res && file_exists("../../../../main.inc.php")) {
		$res = @include "../../../../main.inc.php";
	}
	if (!$res && file_exists("../../../../../main.inc.php")) {
		$res = @include "../../../../../main.inc.php";
	}
	if (!$res) {
		die("Include of main fails");
	}

	// Libraries ************************************
	dol_include_once('/multismtp/lib/smtp2go.lib.php');

	header('Content-Type: application/json');

	// Access control *******************************
	if (!$user->admin) {
		echo json_encode(array('error' => 'ErrorForbidden'));
		$db->close();
		exit;
	}
	if (GETPOST('token', 'alpha') !== newToken()) {
		echo json_encode(array('error' => 'InvalidToken'));
		$db->close();
		exit;
	}

	// View ****************************************
	$username	= GETPOST('username', 'alphanohtml');
	$password	= '';
	if (!empty($username)) {
		$encrypted	= multismtp_smtp2go_get_user_pwd($username);
		$password	= !empty($encrypted) ? dolDecrypt($encrypted) : '';
	}

	echo json_encode(array('password' => $password));
	$db->close();
