<?php
/* Copyright (C) 2005-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2025 Eric Seigne <eric.seigne@cap-rel.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       core/modules/uptosignlist/xinputuser.modules.php
 *	\ingroup    uptosign
 *	\brief      Selector module for manual input of recipients (multi-line, paste from spreadsheet)
 */
dol_include_once('/uptosign/core/modules/uptosignlist/modules_mailings.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Class to offer a selector of signing targets with multi-line manual input.
 *	Supports copy/paste from spreadsheets (tab-separated) and semicolon-separated values.
 */
class uptosignlist_uts_inputmanual extends UptosignListTargets
{
	public $name = 'EmailsFromUser';
	public $desc = 'EMails input by user';
	public $require_module = array();
	public $require_admin = 0;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'generic';
	public $tooltip = 'UseFormatInputEmailToTarget';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array		Array with SQL requests
	 */
	public function getSqlArrayForStats()
	{
		return array();
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *
	 *  @param      string			$sql   		Sql request to count
	 *  @return     int|string      			Nb of recipient, or <0 if error, or '' if NA
	 */
	public function getNbOfRecipients($sql = '')
	{
		return '';
	}


	/**
	 *  Return URL link to source record
	 *
	 *  @param	int		$id		ID
	 *  @return string      	Url link
	 */
	public function url($id)
	{
		return '';
	}


	/**
	 *   Display filter form on the target selection page.
	 *   Shows a textarea for multi-line input (paste from spreadsheet).
	 *
	 *   @return     string      HTML form zone
	 */
	public function formFilter()
	{
		global $langs;

		$langs->load("uptosign@uptosign");

		$s = '';
		$s .= '<textarea name="xinputuser" class="flat" rows="6" cols="80" placeholder="'
			.dol_escape_htmltag($langs->trans("UptoSignXInputUserPlaceholder"))
			.'">'.dol_escape_htmltag(GETPOST("xinputuser", 'restricthtml')).'</textarea>';

		return $s;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add recipients to target table from multi-line textarea input.
	 *  Each line is one recipient. Auto-detects separator (tab or semicolon).
	 *  Expected columns: firstname ; lastname ; mobile ; email
	 *
	 *  @param	int		$uptosignlist_id    	Id of uptosignlist
	 *  @return int           			< 0 if error, nb added if ok
	 */
	public function add_to_target($uptosignlist_id)
	{
		// phpcs:enable
		global $langs;

		$raw = GETPOST('xinputuser', 'restricthtml');
		if (empty($raw)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldRequired", $langs->trans("Recipients"));
			return -1;
		}

		$lines = preg_split('/\r\n|\r|\n/', $raw);
		$cibles = array();
		$errors = 0;
		$linenum = 0;

		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '') {
				continue;
			}
			$linenum++;

			// Auto-detect separator: tab has priority (spreadsheet paste), fallback to semicolon
			if (strpos($line, "\t") !== false) {
				$parts = explode("\t", $line, 4);
			} else {
				$parts = explode(';', $line, 4);
			}

			// Column order: firstname ; lastname ; mobile ; email
			// Backward-friendly shortcut: a single field that is a valid email is treated as an email-only line
			if (count($parts) == 1 && isValidEmail(trim(dol_string_nohtmltag($parts[0])))) {
				$firstname = $lastname = $mobile = '';
				$email = trim(dol_string_nohtmltag($parts[0]));
			} else {
				$firstname = trim(dol_string_nohtmltag(isset($parts[0]) ? $parts[0] : ''));
				$lastname = trim(dol_string_nohtmltag(isset($parts[1]) ? $parts[1] : ''));
				$mobile = trim(dol_string_nohtmltag(isset($parts[2]) ? $parts[2] : ''));
				$email = trim(dol_string_nohtmltag(isset($parts[3]) ? $parts[3] : ''));
			}

			if (!isValidEmail($email)) {
				$errors++;
				$langs->load("errors");
				$msg = $langs->trans("ErrorFoundBadEmailInFile", $errors, $linenum, $email);
				if (empty($msg)) {
					$msg = 'ErrorFoundBadEmailInFile '.$errors.' '.$linenum.' '.$email;
				}
				$this->error = $msg;
				continue;
			}

			$cibles[] = array(
				'email' => $email,
				'lastname' => $lastname,
				'firstname' => $firstname,
				'other' => '',
				'source_url' => '',
				'source_id' => '',
				'source_type' => 'manual',
				'mobile' => $mobile
			);
		}

		if ($errors > 0) {
			return -$errors;
		}

		if (empty($cibles)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldRequired", $langs->trans("Recipients"));
			return -1;
		}

		return parent::addTargetsToDatabase($uptosignlist_id, $cibles);
	}
}
