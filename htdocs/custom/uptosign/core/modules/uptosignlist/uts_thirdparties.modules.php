<?php
/* Copyright (C) 2005-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis.houssin@inodbox.com>
*
* This file is an example to follow to add your own email selector inside
* the Dolibarr email tool.
* Follow instructions given in README file to know what to change to build
* your own emailing list selector.
* Code that need to be changed in this file are marked by "CHANGE THIS" tag.
*/

/**
 *	\file       htdocs/core/modules/mailings/advthirdparties.modules.php
 *	\ingroup    mailing
 *	\brief      Example file to provide a list of recipients for mailing module
 */

dol_include_once('/uptosign/core/modules/uptosignlist/modules_mailings.php');
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';


/**
 *	Class to manage a list of personalised recipients for mailing feature
 */
class uptosignlist_uts_thirdparties extends UptosignListTargets
{
	public $name = 'ThirdPartyAdvancedTargeting';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $desc = "Third parties";
	public $require_admin = 0;

	public $require_module = array("none"); // This module should not be displayed as Selector in mailling

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'company';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $enabled = 'isModEnabled("societe")';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    This is the main function that returns the array of emails
	 *
	 *    @param	int		$uptosignlist_id    	Id of mailing. No need to use it.
	 *    @param	array	$socid  		Array of id soc to add
	 *    @param	int		$type_of_target	Defined in advtargetemailing.class.php
	 *    @param	array	$contactid 		Array of contact id to add
	 *    @return   int 					<0 if error, number of emails added if ok
	 */
	public function add_to_target_spec($uptosignlist_id, $socid, $type_of_target, $contactid)
	{
		// phpcs:enable
		global $conf, $langs;

		dol_syslog("uptosign: " . get_class($this)."::add_to_target_spec socid=".var_export($socid, true).' contactid='.var_export($contactid, true));

		$cibles = array();

		if (($type_of_target == 1) || ($type_of_target == 3)) {
			// Select the third parties from category
			if (count($socid) > 0) {
				// The phone is needed: signing sends an SMS code to the target. A company
				// only carries a single 'phone' column, so it is read as a mobile candidate
				// and normalized against its country like every other selector does.
				$sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact,";
				$sql .= " s.phone as phone, cc.code as country_code";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe as s LEFT OUTER JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object=s.rowid";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON s.fk_pays = cc.rowid";
				$sql .= " WHERE s.entity IN (".getEntity('societe').")";
				$sql .= " AND s.rowid IN (".$this->db->sanitize(implode(',', $socid)).")";
				$sql .= " ORDER BY email";

				// Stock recipients emails into targets table
				$result = $this->db->query($sql);
				if ($result) {
					$num = $this->db->num_rows($result);
					$i = 0;

					dol_syslog("uptosign: " . get_class($this)."::add_to_target_spec mailing ".$num." targets found", LOG_DEBUG);

					while ($i < $num) {
						$obj = $this->db->fetch_object($result);

						if (!empty($obj->email) && filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {
							if (!array_key_exists($obj->email, $cibles)) {
								$mobile = uptoSignFixMobile($obj->phone, $obj->country_code);
								if (empty($mobile)) {
									// Kept as a target anyway: advanced targeting is an explicit
									// choice, and a list sent with disableSms needs no mobile
									dol_syslog("uptosign: " . get_class($this)."::add_to_target_spec third party ".$obj->id." has no usable mobile, target kept without one", LOG_WARNING);
								}
								$cibles[$obj->email] = array(
									'email' => $obj->email,
									'mobile' => $mobile,
									'fk_contact' => $obj->fk_contact,
									// A company is a legal person: its name goes to lastname, since
									// addTargetsToDatabase() only reads lastname/firstname. The former
									// 'name' key was never read and 'firstname' pointed to a column
									// absent from the SELECT: every third party target used to land in
									// base with both names empty.
									'lastname' => $obj->name,
									'firstname' => '',
									'other' => '',
									'source_url' => $this->url($obj->id, 'thirdparty'),
									'source_id' => $obj->id,
									'source_type' => 'thirdparty'
								);
							}
						}

						$i++;
					}
				} else {
					dol_syslog("uptosign: " . $this->db->error());
					$this->error = $this->db->error();
					return -1;
				}
			}
		}

		if (($type_of_target == 1) || ($type_of_target == 2) || ($type_of_target == 4)) {
			// Select the third parties from category
			if (count($socid) > 0 || count($contactid) > 0) {
				$sql = "SELECT socp.rowid as id, socp.email as email, socp.lastname as lastname, socp.firstname as firstname,";
				// Same phone fields as uptoSignSearchMobileContact(), in the same order
				$sql .= " socp.phone_mobile, socp.phone_perso, socp.phone as phone_pro, cc.code as country_code";
				$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as socp";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON socp.fk_pays = cc.rowid";
				$sql .= " WHERE socp.entity IN (".getEntity('contact').")";
				if (count($contactid) > 0) {
					$sql .= " AND socp.rowid IN (".$this->db->sanitize(implode(',', $contactid)).")";
				}
				if (count($socid) > 0) {
					$sql .= " AND socp.fk_soc IN (".$this->db->sanitize(implode(',', $socid)).")";
				}
				$sql .= " ORDER BY email";

				// Stock recipients emails into targets table
				$result = $this->db->query($sql);
				if ($result) {
					$num = $this->db->num_rows($result);
					$i = 0;

					dol_syslog("uptosign: " . get_class($this)."::add_to_target_spec mailing ".$num." targets found");

					while ($i < $num) {
						$obj = $this->db->fetch_object($result);

						if (!empty($obj->email) && filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {
							if (!array_key_exists($obj->email, $cibles)) {
								// Same order as uptoSignSearchMobileContact(): mobile, then pro, then perso
								$mobile = uptoSignSearchMobile($obj->phone_mobile, $obj->phone_pro, $obj->country_code);
								if (empty($mobile) && !empty($obj->phone_perso)) {
									$mobile = uptoSignFixMobile($obj->phone_perso, $obj->country_code);
								}
								if (empty($mobile)) {
									// See the third party branch: the target is kept anyway
									dol_syslog("uptosign: " . get_class($this)."::add_to_target_spec contact ".$obj->id." has no usable mobile, target kept without one", LOG_WARNING);
								}
								$cibles[$obj->email] = array(
									'email' => $obj->email,
									'mobile' => $mobile,
									'fk_contact' =>$obj->id,
									'lastname' => $obj->lastname,
									'firstname' => $obj->firstname,
									'other' => '',
									'source_url' => $this->url($obj->id, 'contact'),
									'source_id' => $obj->id,
									'source_type' => 'contact'
								);
							}
						}

						$i++;
					}
				} else {
					dol_syslog("uptosign: " . $this->db->error());
					$this->error = $this->db->error();
					return -1;
				}
			}
		}


		dol_syslog("uptosign: " . get_class($this)."::add_to_target_spec mailing cibles=".var_export($cibles, true), LOG_DEBUG);

		return parent::addTargetsToDatabase($uptosignlist_id, $cibles);
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
		// CHANGE THIS: Optionnal

		//var $statssql=array();
		//$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";
		return array();
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *  @param		string			$sql 		Not use here
	 * 	@return     int|string      			Nb of recipient, or <0 if error, or '' if NA
	 */
	public function getNbOfRecipients($sql = '')
	{
		global $conf;

		$sql = "SELECT count(distinct(s.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE s.email != ''";
		$sql .= " AND s.entity IN (".getEntity('societe').")";

		// La requete doit retourner un champ "nb" pour etre comprise par parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
	}

	/**
	 *  This is to add a form filter to provide variant of selector
	 *	If used, the HTML select must be called "filter"
	 *
	 *  @return     string      A html select zone
	 */
	public function formFilter()
	{
		global $conf, $langs;

		$langs->load("companies");

		$s = '';
		$s .= '<select name="filter" class="flat">';

		// Show categories
		$sql = "SELECT rowid, label, type, visible";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql .= " WHERE type in (1,2)"; // We keep only categories for suppliers and customers/prospects
		// $sql.= " AND visible > 0";	// We ignore the property visible because third party's categories does not use this property (only products categories use it).
		$sql .= " AND entity = ".$conf->entity;
		$sql .= " ORDER BY label";

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if (!isModEnabled("categorie")) {
				$num = 0; // Force empty list if category module is not enabled
			}

			if ($num) {
				$s .= '<option value="0">&nbsp;</option>';
			} else {
				$s .= '<option value="0">'.$langs->trans("ContactsAllShort").'</option>';
			}

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$type = '';
				if ($obj->type == 1) {
					$type = $langs->trans("Supplier");
				}
				if ($obj->type == 2) {
					$type = $langs->trans("Customer");
				}
				$s .= '<option value="'.$obj->rowid.'">'.dol_trunc($obj->label, 38, 'middle');
				if ($type) {
					$s .= ' ('.$type.')';
				}
				$s .= '</option>';
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}

		$s .= '</select>';
		return $s;
	}


	/**
	 *  Can include an URL link on each record provided by selector shown on target page.
	 *
	 *  @param	int		$id		ID
	 *  @param	string		$type	type
	 *  @return string      	Url link
	 */
	public function url($id, $type)
	{
		if ($type == 'thirdparty') {
			$companystatic = new Societe($this->db);
			$companystatic->fetch($id);
			return $companystatic->getNomUrl(0, '', 0, 1);
		} elseif ($type == 'contact') {
			$contactstatic = new Contact($this->db);
			$contactstatic->fetch($id);
			return $contactstatic->getNomUrl(0, '', 0, '', -1, 1);
		}
		return "";
	}
}
