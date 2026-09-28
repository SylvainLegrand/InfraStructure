<?php
/* Copyright (C) 2005-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis.houssin@inodbox.com>
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
 *	\file       htdocs/core/modules/mailings/pomme.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets of users.
 */
dol_include_once('/uptosign/core/modules/uptosignlist/modules_mailings.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');


/**
 *	Class to offer a selector of emailing targets with Rule 'Pomme'.
 */
class uptosignlist_uts_users extends UptosignListTargets
{
	public $name = 'DolibarrUsersWithMailAndPhone'; // Identifier of the selector
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $desc = 'Dolibarr users with emails and mobile phone';
	public $require_module = array(); // Selector offered only when these modules are enabled
	public $require_admin = 1; // Selector reserved to admin users or not

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'user';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;


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
	 *	@return		string[]		Array with SQL requests
	 */
	public function getSqlArrayForStats()
	{
		global $conf, $langs;

		$langs->load("users");

		$statssql = array();
		$sql = "SELECT '".$this->db->escape($langs->trans("DolibarrUsers"))."' as label,";
		$sql .= " count(distinct(u.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
		$sql .= " AND u.entity IN (0,".$conf->entity.")";

		$statssql[0] = $sql;

		return $statssql;
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *	@param		string			$sql		SQL request to use to count
	 *  @return     int|string      			Nb of recipient, or <0 if error, or '' if NA
	 */
	public function getNbOfRecipients($sql = '')
	{
		global $conf;

		$sql = "SELECT count(distinct(u.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.email != ''"; // u.email IS NOT NULL est implicite dans ce test
		$sql .= " AND u.entity IN (0,".$conf->entity.")";

		// La requete doit retourner un champ "nb" pour etre comprise par parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
	}


	/**
	 *  Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
	 *
	 *  @return     string      Retourne zone select
	 */
	public function formFilter()
	{
		global $langs;

		$langs->loadLangs(array("users", "uptosign@uptosign"));

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		$form = new Form($this->db);

		$s = '';
		$s .= '<select id="filter_uts_users" name="filter" class="flat minwidth100">';
		$s .= '<option value="-1">'.$langs->trans("Status").'</option>';
		$s .= '<option value="1">'.$langs->trans("Enabled").'</option>';
		$s .= '<option value="0">'.$langs->trans("Disabled").'</option>';
		$s .= '</select>';
		$s .= ajax_combobox("filter_uts_users");

		$s .= ' ';
		$s .= '<select id="filteremployee_uts_users" name="filteremployee" class="flat minwidth100">';
		$s .= '<option value="-1">'.$langs->trans("Employee").'</option>';
		$s .= '<option value="1">'.$langs->trans("Yes").'</option>';
		$s .= '<option value="0">'.$langs->trans("No").'</option>';
		$s .= '</select>';
		$s .= ajax_combobox("filteremployee_uts_users");

		$s .= '<br>';

		// Filter by group: only employees of the chosen group will be added
		$s .= '<span class="opacitymedium paddingright">'.$langs->trans("UptoSignFilterEmployeesByGroup").'</span>';
		$s .= $form->select_dolgroups(GETPOSTINT('filterusergroup'), 'filterusergroup', 1, '', 0, '', '', '0', false, 'minwidth200');
		$s .= ' ';

		// Search a single employee - only employees having BOTH a mobile and an email are proposed
		$morefilter = "AND u.employee = 1 AND u.email <> '' AND u.user_mobile <> ''";
		$s .= '<span class="opacitymedium paddingright">'.$langs->trans("UptoSignSearchEmployeeWithMobile").'</span>';
		$s .= $form->select_dolusers(GETPOSTINT('filteruserid'), 'filteruserid', 1, null, 0, '', '', '0', 0, 0, $morefilter, 0, '', 'minwidth200');

		return $s;
	}


	/**
	 *  Renvoie url lien vers fiche de la source du destinataire du mailing
	 *
	 *  @param	int		$id		ID
	 *  @return     string      Url lien
	 */
	public function url($id)
	{
		return '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$id.'">'.img_object('', "user").'</a>';
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Ajoute destinataires dans table des cibles
	 *
	 *  @param	int		$uptosignlist_id    	Id of emailing
	 *  @return int           			< 0 si erreur, nb ajout si ok
	 */
	public function add_to_target($uptosignlist_id)
	{
		dol_syslog("uptosign: " . get_class($this)."::list id is ".$uptosignlist_id . "==============================", LOG_INFO);

		// phpcs:enable
		global $conf, $langs;
		$langs->load("companies");

		$cibles = array();

		// La requete doit retourner: id, email, fk_contact, lastname, firstname, mobile
		$sql = "SELECT u.rowid as id, u.email as email, null as fk_contact,";
		$sql .= " u.lastname, u.firstname as firstname, u.civility as civility_id, u.login, u.user_mobile, u.personal_mobile, u.office_phone,";
		$sql .= " c.code as country_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON u.fk_country = c.rowid";
		$sql .= " WHERE u.email <> ''"; // u.email IS NOT NULL est implicite dans ce test
		$sql .= " AND u.user_mobile <> ''"; // signing needs an SMS-verifiable mobile
		$sql .= " AND u.entity IN (0,".$conf->entity.")";
		$sql .= " AND u.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."uptosign_uptosignlistmembers WHERE fk_uptosignlist=".((int) $uptosignlist_id).")";
		if (GETPOSTISSET("filter") && GETPOST("filter") == '1') {
			$sql .= " AND u.statut=1";
		}
		if (GETPOSTISSET("filter") && GETPOST("filter") == '0') {
			$sql .= " AND u.statut=0";
		}
		if (GETPOSTISSET("filteremployee") && GETPOST("filteremployee") == '1') {
			$sql .= " AND u.employee=1";
		}
		if (GETPOSTISSET("filteremployee") && GETPOST("filteremployee") == '0') {
			$sql .= " AND u.employee=0";
		}
		// Restrict to a single employee when one has been picked in the search selector
		if (GETPOSTINT("filteruserid") > 0) {
			$sql .= " AND u.rowid = ".GETPOSTINT("filteruserid");
		}
		// Restrict to the members of the chosen group
		if (GETPOSTINT("filterusergroup") > 0) {
			$sql .= " AND u.rowid IN (SELECT fk_user FROM ".MAIN_DB_PREFIX."usergroup_user WHERE fk_usergroup = ".GETPOSTINT("filterusergroup").")";
		}
		$sql .= " ORDER BY u.email";
		// dol_syslog(get_class($this)."::sql is ".$sql);

		// Stocke destinataires dans cibles
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;

			dol_syslog("uptosign: " . get_class($this)."::add_to_target mailing ".$num." targets found");

			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$mobile = uptoSignFixMobile($obj->personal_mobile, $obj->country_code);
				if (empty($mobile)) {
					$mobile = uptoSignFixMobile($obj->user_mobile, $obj->country_code);
				}
				if (!empty($mobile)) {
					dol_syslog("uptosign: " . get_class($this)."::add_to_target mailing obj=".json_encode($obj));
					$cibles[] = array(
						'firstname' => $obj->firstname,
						'lastname' => $obj->lastname,
						'email' => $obj->email,
						'mobile' => $mobile,
						'source_type' => 'user',
						'source_id' => $obj->id,
						'other' =>
							($langs->transnoentities("Login").'='.$obj->login).';'.
							($langs->transnoentities("UserTitle").'='.$obj->civility_id).';'.
							($langs->transnoentities("PhonePro").'='.$obj->office_phone),
						'source_url' => $this->url($obj->id),
					);
				}
				$i++;
			}
		} else {
			dol_syslog("uptosign: " . $this->db->error());
			$this->error = $this->db->error();
			return -1;
		}
		dol_syslog("uptosign: " . get_class($this)."::add_to_target mailing cibles=".json_encode($cibles));

		return parent::addTargetsToDatabase($uptosignlist_id, $cibles);
	}
}
