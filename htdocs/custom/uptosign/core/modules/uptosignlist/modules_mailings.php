<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
 *	    \file       htdocs/core/modules/mailings/modules_mailings.php
 *		\ingroup    mailing
 *		\brief      File with parent class of emailing target selectors modules
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 *		Parent class of emailing target selectors modules
 */
class UptosignListTargets // This can't be abstract as it is used for some method
{
	/**
	 * @var array<int, string> Error messages
	 */
	public $errors;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string	Condition to be enabled
	 */
	public $enabled;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Help text shown next to the selector name
	 */
	public $tooltip = '';

	/**
	 * @var string The SQL string used to find the recipients
	 */
	public $sql;

	/**
	 * @var string Description of the selector
	 */
	public $desc;

	/**
	 * @var string Name of the selector
	 */
	public $name;

	/**
	 * @var int Set this to 1 if you want to flag you also want to include email in target that has opt-out.
	 */
	public $evenunsubscribe = 0;

	/**
	 * @var array<int, string> Modules that must be enabled for this selector to be offered
	 */
	public $require_module = array();

	/**
	 * @var int Set to 1 when the selector is reserved to admin users
	 */
	public $require_admin = 0;

	/**
	 * @var string Name of the icon shown next to the selector
	 */
	public $picto = '';


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
	 * Return description of email selector
	 *
	 * @return     string      Return translation of module label. Try translation of $this->name then translation of 'MailingModuleDesc'.$this->name, or $this->desc if not found
	 */
	public function getDesc()
	{
		global $langs, $form;

		$langs->load("mails");
		$transstring = "MailingModuleDesc".$this->name;
		$s = '';

		if ($langs->trans($this->name) != $this->name) {
			$s = $langs->trans($this->name);
		} elseif ($langs->trans($transstring) != $transstring) {
			$s = $langs->trans($transstring);
		} else {
			$s = $this->desc;
		}

		if ($this->tooltip && is_object($form)) {
			$s .= ' '.$form->textwithpicto('', $langs->trans($this->tooltip), 1, 1);
		}
		return $s;
	}

	/**
	 *	Return number of records for email selector
	 *
	 *  @return     integer      Example
	 */
	public function getNbOfRecords()
	{
		return 0;
	}

	/**
	 * Retourne nombre de destinataires
	 *
	 * @param      string		$sql        Sql request to count
	 * @return     int|string      			Nb of recipient, or <0 if error, or '' if NA
	 */
	public function getNbOfRecipients($sql)
	{
		$result = $this->db->query($sql);
		if ($result) {
			$total = 0;
			while ($obj = $this->db->fetch_object($result)) {
				$total += $obj->nb;
			}
			return $total;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Affiche formulaire de filtre qui apparait dans page de selection
	 * des destinataires de mailings
	 *
	 * @return     string      Retourne zone select
	 */
	public function formFilter()
	{
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Met a jour nombre de destinataires
	 *
	 * @param	int		$uptosignlist_id          Id of emailing
	 * @return  int			                 < 0 si erreur, nb destinataires si ok
	 */
	public function update_nb($uptosignlist_id)
	{
		// phpcs:enable
		// Mise a jour nombre de destinataire dans table des mailings
		$sql = "SELECT COUNT(*) nb FROM ".MAIN_DB_PREFIX."uptosign_uptosignlistmembers";
		$sql .= " WHERE fk_uptosignlist = ".((int) $uptosignlist_id);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$nb = $obj->nb;
		} else {
			$nb = -1;
		}
		return $nb;
	}

	/**
	 * Add a list of targets into the database
	 *
	 * @param	int		$uptosignlist_id    Id of emailing
	 * @param   array	$cibles        Array with targets
	 * @return  int      			   < 0 if error, nb added if OK
	 */
	public function addTargetsToDatabase($uptosignlist_id, $cibles)
	{
		global $conf;

		$this->db->begin();

		// Insert emailing targets from array into database
		$j = 0;
		$num = count($cibles);
		foreach ($cibles as $targetarray) {
			if (!empty($targetarray['email'])) { // avoid empty email address
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."uptosign_uptosignlistmembers";
				$sql .= " (fk_uptosignlist,";
				$sql .= " lastname, firstname, email, mobile, other, source_url, source_id,";
				$sql .= " source_type, status)";
				$sql .= " VALUES (".((int) $uptosignlist_id).",";
				// Tolerant like the neighbouring fields: a selector forgetting one of the
				// two names must not raise a PHP warning in the middle of an INSERT
				$sql .= "'".$this->db->escape($targetarray['lastname'] ?? '')."',";
				$sql .= "'".$this->db->escape($targetarray['firstname'] ?? '')."',";
				$sql .= "'".$this->db->escape($targetarray['email'])."',";
				$sql .= "'".$this->db->escape(isset($targetarray['mobile']) ? $targetarray['mobile'] : '')."',";
				$sql .= "'".$this->db->escape(isset($targetarray['other']) ? $targetarray['other'] : '')."',";
				$sql .= "'".$this->db->escape(isset($targetarray['source_url']) ? $targetarray['source_url'] : '')."',";
				$sql .= (empty($targetarray['source_id']) ? 'null' : "'".$this->db->escape($targetarray['source_id'])."'").",";
				$sql .= "'".$this->db->escape(isset($targetarray['source_type']) ? $targetarray['source_type'] : '')."',";
				$sql .= "'".UptoSignList::STATUS_DRAFT."')";
				dol_syslog("uptosign: " . __METHOD__, LOG_DEBUG);
				$result = $this->db->query($sql);
				if ($result) {
					$j++;
				} else {
					if ($this->db->errno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
						// Si erreur autre que doublon
						dol_syslog("uptosign: " . $this->db->error().' : '.$targetarray['email']);
						$this->error = $this->db->error().' : '.$targetarray['email'];
						$this->db->rollback();
						return -1;
					}
				}
			}
		}

		dol_syslog("uptosign: " . __METHOD__.": mailing ".$j." targets added");

		// Update nb of recipient into emailing record
		$this->update_nb($uptosignlist_id);

		$this->db->commit();

		return $j;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Supprime tous les destinataires de la table des cibles
	 *
	 *  @param  int		$uptosignlist_id        Id of emailing
	 *  @return	void
	 */
	public function clear_target($uptosignlist_id)
	{
		// phpcs:enable
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."uptosign_uptosignlistmembers";
		$sql .= " WHERE fk_uptosignlist = ".((int) $uptosignlist_id);

		if (!$this->db->query($sql)) {
			dol_syslog("uptosign: " . $this->db->error());
		}

		$this->update_nb($uptosignlist_id);
	}
}
