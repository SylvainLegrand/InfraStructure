<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright 2022-2023 ---Eric Seigne <eric.seigne@cap-rel.fr>---
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
 * \file        class/uptosign.class.php
 * \ingroup     uptosign
 * \brief       This file is a CRUD class file for UptoSign (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';

dol_include_once('/uptosign/class/uptosignconfig.class.php');

require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';
dol_include_once('/uptosign/lib/uptosign.lib.php');
dol_include_once('/uptosign/class/uptosignapiclient.class.php');
dol_include_once('/uptosign/class/uptosignsignatoryresolver.class.php');
dol_include_once('/uptosign/lib/backports.lib.php');

/**
 * Class for UptoSign
 */
class UptoSign extends CommonObject
{
	private const BASE_URL_DEMO = 'https://demo.uptosign.org';
	private const BASE_URL_PROD = 'https://app.uptosign.com';
	private const BASE_URL_DEV  = 'https://dev.uptosign.com';

	public $socid;
	public $labelStatusShort;
	public $labelStatus;
	public $output;
	public $user_validation;
	public $contactToSignID;

	/**
	 * @var string ID of module.
	 */
	public $module = 'uptosign';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'uptosign';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'uptosign';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for uptosign. Must be the part after the 'object_' into object_uptosign.png
	 */
	public $picto = 'uptosign@uptosign';

	public const STATUS_NOTHING = -100; 	//aucune procédure en cours pour cet objet
	public const STATUS_DRAFT = -10; 		//brouillon

	public const STATUS_EXPIRED = -4; 		//expiré
	public const STATUS_REFUSED = -3; 		//refusé
	public const STATUS_ERROR = -2; 		//erreur
	public const STATUS_CANCELED = -1;		//annulé
	public const STATUS_WAITING = 0;		//en attente
	public const STATUS_SIGNED = 1;			//signé
	public const STATUS_SEALED = 2;			//scellé
	public const STATUS_FILE_FETCHED = 3;	//téléchargé


	public const ROLE_LEVEL_DISABLED = 1;
	public const ROLE_LEVEL_USER = 10;
	public const ROLE_LEVEL_RESELLER = 1000;

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'index' => 1, 'comment' => "Id"),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 2, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "Reference of object", 'csslist' => ''),
		'fk_object' => array('type' => 'integer', 'label' => 'ObjectId', 'enabled' => '1', 'position' => 3, 'notnull' => -1, 'visible' => 1, 'index' => 1,),
		'fk_contact_sign' => array('type' => 'integer:Contact:contact/class/contact.class.php', 'label' => 'ContactSignId', 'enabled' => '1', 'position' => 5, 'notnull' => -1, 'visible' => 1,),
		'fk_user_sign' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserSignId', 'enabled' => '1', 'position' => 6, 'notnull' => -1, 'visible' => 1,),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'visible' => -1, 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'index' => 1,),
		'object_type' => array('type' => 'varchar(32)', 'label' => 'ObjectType', 'enabled' => '1', 'position' => 22, 'notnull' => -1, 'visible' => 1, 'comment' => "key"),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => -1, 'visible' => -1, 'searchall' => 1, 'help' => "Help text",),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => '1', 'position' => 50, 'notnull' => -1, 'visible' => 1, 'index' => 1, 'searchall' => 1, 'help' => "LinkToThirdparty",),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 60, 'notnull' => -1, 'visible' => -1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 490, 'notnull' => 1, 'visible' => 1,),
		'date_sign' => array('type' => 'datetime', 'label' => 'DateSign', 'enabled' => '1', 'position' => 500, 'notnull' => -1, 'visible' => 1,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 1, 'visible' => -2,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => 1,),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -1,),
		'hash_file' => array('type' => 'varchar(255)', 'label' => 'UptoSignHashFile', 'enabled' => '1', 'position' => 605, 'notnull' => -1, 'visible' => -1,),
		'hash_file_signed' => array('type' => 'varchar(255)', 'label' => 'UptoSignHashFileSigned', 'enabled' => '1', 'position' => 606, 'notnull' => -1, 'visible' => -1,),
		'path_file' => array('type' => 'varchar(512)', 'label' => 'PathFile', 'enabled' => '1', 'position' => 610, 'notnull' => -1, 'visible' => -1,),
		'path_file_signed' => array('type' => 'varchar(512)', 'label' => 'PathFileSigned', 'enabled' => '1', 'position' => 611, 'notnull' => -1, 'visible' => -1,),
		'sign_status' => array('type' => 'varchar(128)', 'label' => 'SignStatus', 'enabled' => '1', 'position' => 620, 'notnull' => -1, 'visible' => 1,),
		'sign_id' => array('type' => 'varchar(64)', 'label' => 'SignId', 'enabled' => '1', 'position' => 640, 'notnull' => -1, 'visible' => -1,),
		'sign_history' => array('type' => 'text', 'label' => 'History', 'enabled' => '1', 'position' => 645, 'notnull' => -1, 'visible' => 0,),
		'api_name' => array('type' => 'varchar(64)', 'label' => 'ApiName', 'enabled' => '1', 'position' => 650, 'notnull' => -1, 'visible' => -1,),
		'hook_key' => array('type' => 'varchar(255)', 'label' => 'Hook API hash', 'enabled' => '1', 'position' => 660, 'notnull' => -1, 'visible' => -1,),
		'fk_uptosignlist' => array('type' => 'integer', 'label' => 'UptoSignList', 'enabled' => '1', 'position' => 670, 'notnull' => -1, 'visible' => -1, 'index' => 1, 'foreignkey' => 'uptosign_uptosignlist.rowid'),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 1000, 'notnull' => -1, 'visible' => -2,),
		'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 1001, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array('-4' => 'uptosignStatusExpired', '-3' => 'uptosignStatusRefused', '-2' => 'uptosignStatusError', '-1' => 'uptosignStatusCancelled', '0' => 'uptosignStatusWaiting', '1' => 'uptosignStatusSigned', '2' => 'uptosignStatusSealed', '3' => 'uptosignStatusDownloaded')),
	);
	public $rowid;
	public $ref;
	public $fk_object;
	public $label;
	public $entity;
	public $fk_soc;
	public $description;
	public $date_creation;
	public $date_sign;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	public $object_type;
	public $sign_status;
	public $sign_id;
	public $sign_history;
	public $api_name;
	public $hook_key;
	public $fk_contact_sign;
	public $fk_user_sign;
	public $hash_file;
	public $hash_file_signed;
	public $path_file;
	public $path_file_signed;
	public $fk_uptosignlist;
	// END MODULEBUILDER PROPERTIES

	public $sign_link;
	public $redirect_sign;
	public $endRedirect; //where to redirect sign people after sign process
	public $hideMailAndPhone; //activate pseudo anonymous sign ? with non delivered proof file
	public $disableSms; // mauvaise idee mais parfois necessaire de desactiver le SMS

	/**
	 * @var UptoSignAPIClient API client for remote calls
	 */
	public $apiClient;

	/**
	 * @var UptoSignSignatoryResolver Signatory resolver
	 */
	public $signatoryResolver;

	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'uptosignline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_uptosign';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'UptoSignline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('uptosigndet');

	// /**
	//  * @var UptoSignLine[]     Array of subtable lines
	//  */
	// public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->apiClient = new UptoSignAPIClient($db);
		$this->signatoryResolver = new UptoSignSignatoryResolver($db);
		$this->redirect_sign = false;
		$this->endRedirect = utsbackports_getDolGlobalString('UPTOSIGN_REDIRECT_PAGE_AFTER_SIGN', 'https://uptosign.com/');
		$this->hideMailAndPhone = utsbackports_getDolGlobalString('UPTOSIGN_HIDE_MAIL_AND_PHONE', '0');
		$this->disableSms = utsbackports_getDolGlobalString('UPTOSIGN_DISABLE_SMS_GLOBAL_SELECT', '0');

		if ((utsbackports_getDolGlobalString('MAIN_SHOW_TECHNICAL_ID', '') == '') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->uptosign->read) {
			$this->fields['myfield']['visible'] = 1;
			$this->fields['myfield']['noteditable'] = 0;
		}*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		//remove full path
		$f = uptosign_relative_path($this->path_file);
		$this->path_file = $f;

		$f = uptosign_relative_path($this->path_file_signed);
		$this->path_file_signed = $f;


		// dol_syslog("uptosign: create user =" . json_encode($user), LOG_DEBUG);
		$user = $this->findUserToUse($user, $this);

		/** @phpstan-ignore-next-line */
		$resultcreate = $this->createCommon($user, $notrigger ? 1 : 0);

		return $resultcreate;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		$object->import_key = null;

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_" . $object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$user = $this->findUserToUse($user, $this);
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (!empty($object->socid) && property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * @param object    $object         Objet Dolibarr
	 *
	 * @return int < 0 = KO, 1 = OK
	 */
	public function createEvent($object, $forceMessage = null)
	{
		global $user, $conf, $langs;
		include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
		// dol_syslog("++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ createEvent ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++");

		$message = $title = "";

		dol_syslog("createEvent lang default = " . $langs->getDefaultLang());
		if ($langs->getDefaultLang() == 'auto' || $langs->getDefaultLang() == 'en_US') {
			dol_syslog("createEvent lang = " . $langs->getDefaultLang());
		}
		$langs->loadLangs(array("uptosign@uptosign", "main", "other", "companies", "errors"));

		//Note: si le meme event a été fait qq minutes avant, il faut "concaténer" sur le meme evenement
		$signOrSeal = '';
		if (isset($object->signOrSeal)) {
			$testCode = strtolower($object->signOrSeal);
			if ($testCode == 'sign' || $testCode == 'uptosign') {
				$signOrSeal = 'uptosign';
				$message = $langs->trans("UptoSignProcessStarted");
				$title = $langs->trans("UptoSignProcessTitle");
			} elseif ($testCode == 'seal' || $testCode == 'uptoseal') {
				$signOrSeal = 'uptoseal';
				$message = $langs->trans("UptoSealProcessStarted");
				$title = $langs->trans("UptoSealProcessTitle");
			} else {
				$signOrSeal = $object->signOrSeal;
				dol_syslog("createEvent strange situation signorseal is not sign not seal but " . $signOrSeal);
			}
		}

		// print json_encode($object);exit;
		$now = dol_now();
		$date = dol_print_date(dol_now(), "%d/%m/%Y %H:%M:%S");
		$evt = new ActionComm($this->db);
		$mustCreate = true;

		if (!empty($object->uptosignTitle)) {
			$title = $langs->trans($object->uptosignTitle);
		}
		if (!empty($object->uptosignMessage)) {
			$message = $langs->trans($object->uptosignMessage);
		}
		if (!empty($forceMessage)) {
			$message = $forceMessage;
		}

		//$res = $evt->fetch('', '', $object->ref); ne marche pas si on a plusieurs evenements

		//do not create event in case of uptosign/uptoseal, feed existant one
		$sql  = "SELECT id FROM " . MAIN_DB_PREFIX . "actioncomm WHERE ";
		$sql .= "(ref_ext = '" . $this->db->escape($object->ref . ':' . $signOrSeal) . "') ";
		$sql .= "ORDER BY tms DESC LIMIT 1";
		$resql = $this->db->query($sql);
		dol_syslog("uptosign: " . json_encode($sql), LOG_DEBUG);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$res = $evt->fetch($obj->id);
				if ($res) {
					dol_syslog("uptosign: Un évènement est déjà ouvert pour cet objet: " . json_encode($evt), LOG_DEBUG);
					if ($evt->datef > $now - (10 * 60)) {
						dol_syslog("uptosign: ... de moins de 10 minutes, ajout des infos, note existante=" . json_encode($evt->note_private) . " ajout de message=" . $message, LOG_DEBUG);
						if (!empty($message)) {
							$notes = [];
							if (!empty($evt->note_private)) {
								//remove empty lines and trim each lines
								$notesNoBr = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $evt->note_private);
								$notes = array_filter(array_map('trim', explode("\n", $notesNoBr)), function($s) { return $s !== ''; });
							}
							dol_syslog("uptosign: ... de moins de 10 minutes, avant push notes= " . json_encode($notes), LOG_DEBUG);
							array_push($notes, $date . ": " . trim($langs->trans($message)));
							dol_syslog("uptosign: ... de moins de 10 minutes, apres push notes= " . json_encode($notes), LOG_DEBUG);
							$unique = array_unique($notes);
							arsort($unique);
							dol_syslog("uptosign: ... de moins de 10 minutes, apres ajout note unique= " . json_encode($unique), LOG_DEBUG);
							$evt->note_private = nl2br(implode("\n", $unique));
							$evt->datef = $now;
							$user = $this->findUserToUse($user, $this);
							$evt->update($user, 1);
							dol_syslog("uptosign: ... de moins de 10 minutes, apres ajout notes= " . json_encode($notes), LOG_DEBUG);
						} else {
							dol_syslog("uptosign: ... de moins de 10 minutes, ajout des infos annulée, message vide", LOG_DEBUG);
							return 0;
						}
						$mustCreate = false;
					}
				}
			}
		}

		if ($mustCreate && !empty($signOrSeal)) {
			dol_syslog("uptosign: Creation d'un evenement", LOG_DEBUG);
			$evt->type_code   = 'AC_OTH_AUTO'; //
			$evt->code        = 'AC_' . strtoupper($signOrSeal);
			$evt->label = $object->ref . ": " . $title;
			$evt->datep = $now;
			$evt->datef = $now;
			$evt->percentage = -1;
			$evt->socid = $object->socid;
			$evt->contact_id    = 0;
			$evt->authorid    = $user->id; // User saving action
			$evt->userownerid = $user->id;
			$evt->note_private = $date . ": " . trim($langs->trans($message));
			$evt->fk_element = $object->id;
			$evt->elementtype = $object->element;

			$evt->ref_ext = $object->ref . ':' . $signOrSeal;
			$evt->userassigned = $user->id;
			$evt->fulldayevent = 0;
			$evt->transparency = 1;
			$evt->socpeopleassigned = array();
			$evt->userassigned = array();

			if (!in_array($object->element, array('societe', 'contact', 'project'))) {
				$evt->fk_element  = $object->id;
				$evt->elementtype = $object->element;
			}

			if (isset($object->listMembers) && is_array($object->listMembers)) {
				foreach ($object->listMembers as $arr) {
					$lid = $arr['dolid'];
					//Associé à un contact client
					if ($arr['doltype'] == 'contact') {
						$evt->socpeopleassigned[$lid] = $lid;
					}
					//ou a un utilisateur dolibarr local
					elseif ($arr['doltype'] == 'user') {
						$evt->userassigned[$lid] = array('id' => $lid, 'transparency' => 0);
					}
				}
			}

			//si spécifié sera pris en priorité ?
			// if (property_exists($object, 'sendtouserid') && is_array($object->sendtouserid) && count($object->sendtouserid) > 0) {
			//     $evt->userassigned = $object->sendtouserid;
			// }

			// print json_encode($evt);        exit;
			if (!is_object($user) || !isset($user->id)) {
				$user = $this->findUserToUse($user, $object);
			}
			$evt->create($user, 1);
		}
		if (!empty($evt->error)) {
			dol_syslog("uptosign:  error on create event " . json_encode($evt->errors), LOG_DEBUG);
			setEventMessages($evt->error, $evt->errors, 'errors');
			return -1;
		} else {
			if (!empty($evt)) {
				setEventMessage($langs->trans('UptoSignEventAdded'));
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   uptosign id
	 * @param string $ref  uptosign ref
	 * @param int    $objectId    related object id (example propal id)
	 * @param string $objectType  related object type
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK, array if more than one
	 */
	public function fetch($id, $ref = null, $objectId = null, $objectType = '')
	{
		global $langs;

		if ($objectType == 'uptosign') {
			$id = $objectId;
			$objectType = null;
			$objectId = null;
		}
		if (null === $id && null === $ref) {
			$result = $this->fetchWhereObjectLinked($objectId, $objectType);
		} else {
			$result = $this->fetchCommon($id, $ref);
		}
		// if ($result > 0 && !empty($this->table_element_line)) {
		//     $this->fetchLines();
		// }

		return $result;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $objectID   Id object
	 * @param string    $objectType object type
	 *
	 * @return int         <0 if KO, 0 not found, > 0 id
	 */
	public function fetchWhereObjectLinked($objectID, $objectType)
	{
		$sql = 'SELECT ';
		if (((int) DOL_VERSION) < 14) {
			$sql .= utsbackports_getFieldList($this);
		} else {
			/** @phpstan-ignore-next-line */
			$sql .= $this->getFieldList('t');
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE t.fk_object = '" .  $this->db->escape($objectID) . "'";
		if ($objectType != '') {
			$sql .= " AND t.object_type = '" .  $this->db->escape(uptosign_unify_object_type($objectType)) . "'";
		}
		$sql .= " AND t.hash_file IS NOT NULL";
		$sql .= " ORDER BY t.tms DESC";
		//print $sql;
		$res = $this->db->query($sql);
		if ($res) {
			$obj = $this->db->fetch_object($res);
			if ($obj) {
				$this->setVarsFromFetchObj($obj);
				return $this->id;
			} else {
				return 0;
			}
		} else {
			array_push($this->errors, $this->db->lasterror());
			return -1;
		}
	}


	/**
	 * Search entry from filename
	 *
	 * @param string $filename name of file to search
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchWhereFileName($filename)
	{
		return $this->fetchWhere('path_file', $filename);
	}

	/**
	 * Search entry from signed filename
	 *
	 * @param string $filename name of file to search
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchWhereFileNameSigned($filename)
	{
		return $this->fetchWhere('path_file_signed', $filename);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string $key  where search key
	 * @param string $value  value to search
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchWhere($key, $value)
	{
		$sql = 'SELECT ';
		if (((int) DOL_VERSION) < 14) {
			$sql .= utsbackports_getFieldList($this);
		} else {
			/** @phpstan-ignore-next-line */
			$sql .= $this->getFieldList('t');
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE " . $this->db->escape($key) . " = '" .  $this->db->escape($value) . "'";

		$res = $this->db->query($sql);
		if ($res) {
			$obj = $this->db->fetch_object($res);
			if ($obj) {
				$this->setVarsFromFetchObj($obj);
				return $this->id;
			} else {
				return 0;
			}
		} else {
			array_push($this->errors, $this->db->lasterror());
			return -1;
		}
	}


	/**
	 * Load object in memory from the database
	 *
	 * @param string $key  where search key
	 * @param string $value  value to search
	 * @return array list of uptosign objects matching search
	 */
	public function fetchWhereLike($key, $value)
	{
		$ret = [];
		$sql = 'SELECT ';
		if (((int) DOL_VERSION) < 14) {
			$sql .= utsbackports_getFieldList($this);
		} else {
			/** @phpstan-ignore-next-line */
			$sql .= $this->getFieldList('t');
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE " . $this->db->escape($key) . " LIKE '" .  $this->db->escape($value) . "%'";
		// print "<p>sql=$sql</p>";
		$res = $this->db->query($sql);
		if ($res) {
			while ($obj = $this->db->fetch_object($res)) {
				$ret[] = $obj;
			}
		}
		return $ret;
	}


	/**
	 * Load object in memory from the database
	 *
	 * @param string $uuid  uuid object
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchWhereUuidSign($uuid)
	{
		return $this->fetchWhere('sign_id', $uuid);
	}


	/**
	 * Load list of linked objects
	 *
	 * @param   int     $objectId   id object
	 * @param   string  $objectType object type
	 * @param   string  $signOrSeal uptosign|uptoseal
	 * @return  array               <0 if KO, array of child objects
	 */
	public function fetchChilds($objectId, $objectType, $signOrSeal = '')
	{
		$arrayResult = array();

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . $this->table_element . " AS t";
		$sql .= " WHERE fk_object = " . ((int) $objectId);
		if (isset($objectType)) {
			$sql .= " AND object_type = '" . $this->db->escape(uptosign_unify_object_type($objectType)) . "'";
		}
		if ($signOrSeal != "") {
			$api_name = uptosign_unify_api_name($signOrSeal);
			$sql .= " AND api_name='" . $this->db->escape($api_name) . "'";
		}
		// $maxts = date('Y-m-d H:i:s');
		$sql .= " ORDER BY tms DESC";
		// print $sql; exit;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$child = new UptoSign($this->db);
				$obj = $this->db->fetch_object($resql);
				$resChild = $child->fetch($obj->rowid);
				if ($resChild) {
					$arrayResult[$i] = $child;
				}
				$i++;
			}
			$this->db->free($resql);
			return $arrayResult;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetchListId " . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		if (((int) DOL_VERSION) < 14) {
			$sql .= utsbackports_getFieldList($this);
		} else {
			/** @phpstan-ignore-next-line */
			$sql .= $this->getFieldList('t');
		}

		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (" . getEntity($this->element) . ")";
		} else {
			$sql .= " WHERE 1 = 1";
		}

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . " = " . ((int) $value);
				} elseif (array_key_exists($key, $this->fields) && in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . " IN (" . $this->db->sanitize($this->db->escape($value)) . ")";
				} else {
					$sqlwhere[] = $key . " LIKE '%" . $this->db->escape($value) . "%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (" . implode(" " . $filtermode . " ", $sqlwhere) . ")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		// print "<p>$sql</p>";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			array_push($this->errors, $this->db->lasterror());
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Fetch UptoSign records by business object with optional filters
	 *
	 * @param  int    $fkObject   Business object ID
	 * @param  string $objectType Object type (unified)
	 * @param  array  $filters    Optional filters: api_name, sign_status, path_file, hash_file_signed, hash_file_null (bool)
	 * @return array|int          Array of UptoSign records indexed by ID, or <0 on error
	 */
	public function fetchByObject($fkObject, $objectType, $filters = array())
	{
		$sql = "SELECT t.rowid";
		foreach ($this->fields as $key => $val) {
			if ($key != 'rowid') {
				$sql .= ", t." . $key;
			}
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (" . getEntity($this->element) . ")";
		} else {
			$sql .= " WHERE 1 = 1";
		}

		$sql .= " AND t.fk_object = " . ((int) $fkObject);
		$sql .= " AND t.object_type = '" . $this->db->escape($objectType) . "'";

		if (!empty($filters['api_name'])) {
			$sql .= " AND t.api_name = '" . $this->db->escape($filters['api_name']) . "'";
		}
		if (!empty($filters['sign_status'])) {
			$sql .= " AND t.sign_status = '" . $this->db->escape($filters['sign_status']) . "'";
		}
		if (!empty($filters['path_file'])) {
			$sql .= " AND t.path_file = '" . $this->db->escape($filters['path_file']) . "'";
		}
		if (!empty($filters['hash_file_signed'])) {
			$sql .= " AND t.hash_file_signed = '" . $this->db->escape($filters['hash_file_signed']) . "'";
		}
		if (!empty($filters['hash_file_null'])) {
			$sql .= " AND t.hash_file IS NULL";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$records = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);
				$records[$record->id] = $record;
				$i++;
			}
			$this->db->free($resql);
			return $records;
		} else {
			array_push($this->errors, $this->db->lasterror());
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		//remove full path
		$f = uptosign_relative_path($this->path_file);
		$this->path_file = $f;

		$f = uptosign_relative_path($this->path_file_signed);
		$this->path_file_signed = $f;

		$user = $this->findUserToUse($user, $this);
		/** @phpstan-ignore-next-line */
		return $this->updateCommon($user, $notrigger ? 1 : 0);
	}

	/**
	 * Get API endpoint URI
	 *
	 * @return  string URI
	 */
	public static function getEndPoint($forcedefault = '')
	{
		global $conf;
		if ($forcedefault) {
			$env = $forcedefault;
		} else {
			$env = utsbackports_getDolGlobalString('UPTOSIGN_ENVIRONMENT', '');
		}

		if ($env == "uptosign-prod") {
			$defaultURI = UptoSign::BASE_URL_PROD;
		} elseif ($env == "uptosign-dev") {
			$defaultURI = UptoSign::BASE_URL_DEV;
		} else {
			$defaultURI = UptoSign::BASE_URL_DEMO;
		}
		return $defaultURI;
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog("uptosign: call delete function");
		$user = $this->findUserToUse($user, $this);
		$this->createEvent($this, "Call delete id #" . $this->sign_id ?? '');

		//effacer le fichier
		$fullFileName = uptosign_full_path($this->path_file_signed);
		if (is_file($fullFileName)) {
			dol_delete_file($fullFileName);
		}

		/** @phpstan-ignore-next-line */
		return $this->deleteCommon($user, $notrigger ? 1 : 0);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function deleteRemote(User $user, $notrigger = false)
	{
		dol_syslog("uptosign: call delete function");
		$error = 0;
		$user = $this->findUserToUse($user, $this);
		$this->createEvent($this, "Call delete id #" . $this->sign_id ?? '');

		$notr = $notrigger ? 1 : 0;

		if ($this->sign_id != '') {
			$response = $this->apiClient->deleteDocument($this->sign_id);

			dol_syslog("uptosign : remote delete returns code " . $response['http_code']);
			if ($response['http_code'] > 0) {
				$resultContent = $response['data'];
				if (in_array($response['http_code'], [200, 404])) {
					dol_syslog("uptosign: signDelete ok");

					//effacer le fichier
					$fullFileName = uptosign_full_path($this->path_file_signed);
					if (is_file($fullFileName)) {
						dol_delete_file($fullFileName);
					}

					//note: il faut supprimer le fichier de preuve ...
					$resProof = $this->fetchByObject((int) $this->fk_object, uptosign_unify_object_type($this->object_type), array('api_name' => 'uptoseal', 'hash_file_null' => true));
					if (is_array($resProof)) {
						$uts = reset($resProof);
						if (is_object($uts)) {
							dol_syslog("uptosign: signDelete try to delete proof file ...");
							$uts->delete($user);
						}
					}

					// dol_syslog("uptosign: delete call common delete");
					/** @phpstan-ignore-next-line */
					$error = $this->deleteCommon($user, $notr);
					// dol_syslog("uptosign: delete call common delete end");
				} else {
					dol_syslog("uptosign: signDelete error : "  . json_encode($resultContent['message'] ?? ''));
					array_push($this->errors, 'UptoSignApiError signDelete : ' . $response['http_code']);
					array_push($this->errors, json_encode($resultContent['message'] ?? ''));
					$error--;
				}
			}
		} elseif (null === $this->hash_file) {
			//cas d'un proof
			dol_syslog("uptosign: delete proof, error is = $error");

			//effacer le fichier
			$fullFileName = uptosign_full_path($this->path_file_signed);
			if (is_file($fullFileName)) {
				dol_delete_file($fullFileName);
			}

			/** @phpstan-ignore-next-line */
			$error = $this->deleteCommon($user, $notr);

			dol_syslog("uptosign: delete proof, error is = $error");
		}
		return $error;
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		$user = $this->findUserToUse($user, $this);
		/** @phpstan-ignore-next-line */
		return $this->deleteLineCommon($user, $idline, $notrigger ? 1 : 0);
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("UptoSign") . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$url = dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (utsbackports_getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER', '') != '') {
				$label = $langs->trans("ShowUptoSign");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else {
			$linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="' . $url . '"';
		}
		$linkstart .= $linkclose . '>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip userphotosmall"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files", 0, '\.pdf$', ['(\.meta|_preview.*\.png)$']);
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
					if (utsbackports_getDolGlobalString(strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS', '') == '') {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('uptosigndao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return the available status
	 *
	 *  @return array 			       	Label of status
	 */
	public function getStatusList()
	{
		$list = array();
		$status = 0;
		while (is_string($this->LibStatut($status))) {
			$list[$status] = $this->LibStatut($status);
			$status++;
		}
		return $list;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{

		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("uptosign@uptosign");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('UptoSignDraft');
			$this->labelStatus[self::STATUS_EXPIRED] = $langs->transnoentitiesnoconv('UptoSignExpired');
			$this->labelStatus[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('UptoSignRefused');
			$this->labelStatus[self::STATUS_FILE_FETCHED] = $langs->transnoentitiesnoconv('UptoSignFetchedSign');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('UptoSignCanceled');
			$this->labelStatus[self::STATUS_SIGNED] = $langs->transnoentitiesnoconv('UptoSignSigned');
			$this->labelStatus[self::STATUS_WAITING] = $langs->transnoentitiesnoconv('WaitingCommonSign');
			$this->labelStatus[self::STATUS_ERROR] = $langs->transnoentitiesnoconv('UptoSignError');
			$this->labelStatus[self::STATUS_SEALED] = $langs->transnoentitiesnoconv('UptoSignSealed');

			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('UptoSignDraft');
			$this->labelStatusShort[self::STATUS_EXPIRED] = $langs->transnoentitiesnoconv('UptoSignExpired');
			$this->labelStatusShort[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('UptoSignRefused');
			$this->labelStatusShort[self::STATUS_FILE_FETCHED] = $langs->transnoentitiesnoconv('UptoSignFetchedSign');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('UptoSignCanceled');
			$this->labelStatusShort[self::STATUS_SIGNED] = $langs->transnoentitiesnoconv('UptoSignSigned');
			$this->labelStatusShort[self::STATUS_WAITING] = $langs->transnoentitiesnoconv('WaitingCommonSign');
			$this->labelStatusShort[self::STATUS_ERROR] = $langs->transnoentitiesnoconv('UptoSignError');
			$this->labelStatusShort[self::STATUS_SEALED] = $langs->transnoentitiesnoconv('UptoSignSealed');
		}

		$statusType = 'status' . $status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}
		// print "<p>On demande le libelle de $status ...</p>";
		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT rowid, date_creation as datec, tms as datem,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
		$sql .= " WHERE t.rowid = " . ((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if (!empty($obj->fk_user_author)) {
					$this->fk_user_creat = $obj->fk_user_author;
				} else {
					$this->fk_user_creat = utsbackports_getDolGlobalString('UPTOSIGN_DEFAULT_USER');
				}

				if (!empty($obj->fk_user_valid)) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if (!empty($obj->fk_user_cloture)) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					// $this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		$this->initAsSpecimenCommon();
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new UptoSignLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('t.fk_uptosign' => (int) $this->id));

		if (is_numeric($result)) {
			$this->errors = $objectline->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("uptosign@uptosign");

		if (empty($conf->global->UPTOSIGN_UPTOSIGN_ADDON)) {
			$conf->global->UPTOSIGN_UPTOSIGN_ADDON = 'mod_uptosign_standard';
		}

		if (utsbackports_getDolGlobalString('UPTOSIGN_UPTOSIGN_ADDON', '')  != '') {
			$mybool = false;

			$file = $conf->global->UPTOSIGN_UPTOSIGN_ADDON . ".php";
			$classname = utsbackports_getDolGlobalString('UPTOSIGN_UPTOSIGN_ADDON', '');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/uptosign/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir . $file;
			}

			if ($mybool === false) {
				dol_print_error($this->db, "Failed to include file " . $file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					dol_print_error($this->db, get_class($this) . "::getNextNumRef " . $obj->error);
					return "";
				}
			} else {
				array_push($this->errors, $langs->trans("Error") . " " . $langs->trans("ClassNotFound") . ' ' . $classname);
				return "";
			}
		} else {
			array_push($this->errors, $langs->trans("ErrorNumberingModuleNotSetup", $this->element));
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;
		dol_syslog("uptosign generateDocument");

		$result = 0;
		$includedocgeneration = 0;

		$langs->load("uptosign@uptosign");

		if (!dol_strlen($modele)) {
			$modele = 'standard_uptosign';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (utsbackports_getDolGlobalString('UPTOSIGN_ADDON_PDF', '')  != '') {
				$modele = utsbackports_getDolGlobalString('UPTOSIGN_ADDON_PDF', '');
			}
		}

		$modelpath = "core/modules/uptosign/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs, $user, $mysoc, $action;
		dol_syslog("uptosign doScheduledJob cron start");

		if (empty($conf->global->UPTOSIGN_RVD_AUTO_CRON)) {
			dol_syslog("uptosign doScheduledJob cron is not enabled in admin panel");
			return 0;
		}

		$job = new Cronjob($this->db);
		$res = $job->fetch('', 'Uptosign', 'doScheduledJob');
		if ($res) {
			$lastrun = $job->datelastrun;
			$check = dol_now() - (24 * 3600);
			if ($lastrun > $check) {
				//lancé manuellement -> on force le lastrun a vide
				if ($action == "confirm_execute") {
					dol_syslog("uptosign doScheduledJob cron start manually, force run (lastrun=$lastrun, check=$check)");
				} else {
					dol_syslog("uptosign doScheduledJob cron max one per day, thanks for uptosign infra");
					return 0;
				}
			}
		}


		// $day = date('d');
		// if($day != '1') {
		// 	$this->output = "uptosign doScheduledJob : only the first day of month";
		// 	dol_syslog($this->output);
		// 	return 0;
		// }

		$uptosignAccount = uptosignApiCheckResellerMode();
		if ($uptosignAccount['main_role_level'] != UptoSign::ROLE_LEVEL_RESELLER) {
			$this->output = "uptosign doScheduledJob : your uptosign account is not a reseller one";
			$this->error = $this->output;
			dol_syslog($this->output);
			return -1;
		}

		dol_syslog("uptosign doScheduledJob, " . json_encode($uptosignAccount));

		//prev month
		$debut_prev = dol_get_first_day((int) date('Y'), (int) date('m') - 1);
		$fin_prev = dol_get_last_day((int) date('Y'), (int) date('m') - 1);

		//current month
		$debut_curr = dol_get_first_day((int) date('Y'), (int) date('m'));
		$fin_curr = dol_get_last_day((int) date('Y'), (int) date('m'));

		foreach ($uptosignAccount['customers'] as $customer) {
			$email = $customer['email'];
			$nbSeal = $uptosignAccount['details'][$email]['-1']['seal'];
			$nbSign = $uptosignAccount['details'][$email]['-1']['sign'];
			dol_syslog("uptosign doScheduledJob, customer=$email, seal=$nbSeal, sign=$nbSign");
			//then update contract & invoice
			$doliTiers = uptosignSearchThirdpartWithEmail($customer['email']);
			if ($doliTiers) {
				$tiersContrat = uptosignSearchUptoSignContract($doliTiers->id);
				if ($tiersContrat) {
					dol_syslog("uptosign doScheduledJob, contrat=" . json_encode($tiersContrat->id));
					foreach ($tiersContrat->lines as $line) {
						if ($line->fk_product == utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_SIGN', '')) {
							$line->qty = $nbSign;
							$line->date_start = $debut_curr;
							$line->date_end = $fin_curr;
							$line->update($user, 1);
						} elseif ($line->fk_product == utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_SEAL', '')) {
							$line->qty = $nbSeal;
							$line->date_start = $debut_curr;
							$line->date_end = $fin_curr;
							$line->update($user, 1);
						}
					}

					$factRec = uptosignSearchUptoSignFactureRec($tiersContrat);
					if ($factRec) {
						dol_syslog("uptosign doScheduledJob, factureRec"); //no debug . json_encode($factRec));
						foreach ($factRec->lines as $line) {
							$invoicerecline = new FactureLigneRec($this->db);
							$invoicerecline->fetch($line->rowid);

							$invoicerecline->tva_tx = get_default_tva($mysoc, $doliTiers, $invoicerecline->fk_product);

							if ($invoicerecline->fk_product == utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_SIGN', '')) {
								$invoicerecline->date_start_fill = false;
								$invoicerecline->date_end_fill = false;
								$invoicerecline->desc = $langs->trans("uptosignAutoInvoicePeriod", dol_print_date($debut_prev), dol_print_date($fin_prev));

								$tabprice = calcul_price_total($nbSign, $invoicerecline->subprice, $invoicerecline->remise_percent, $invoicerecline->tva_tx, $invoicerecline->localtax1_tx, $invoicerecline->txlocaltax2, 0, 'HT', $invoicerecline->info_bits, $invoicerecline->product_type, $mysoc, array(), 100);

								$invoicerecline->total_ht  = $tabprice[0];
								$invoicerecline->total_tva = $tabprice[1];
								$invoicerecline->total_ttc = $tabprice[2];
								$invoicerecline->total_localtax1 = $tabprice[9];
								$invoicerecline->total_localtax2 = $tabprice[10];

								$result = $invoicerecline->update($user, 1);
							} elseif ($invoicerecline->fk_product == utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_SEAL', '')) {
								$invoicerecline->qty = $nbSeal;
								$invoicerecline->date_start_fill = false;
								$invoicerecline->date_end_fill = false;
								$invoicerecline->desc = $langs->trans("uptosignAutoInvoicePeriod", dol_print_date($debut_prev), dol_print_date($fin_prev));

								$tabprice = calcul_price_total($nbSeal, $invoicerecline->subprice, $invoicerecline->remise_percent, $invoicerecline->tva_tx, $invoicerecline->localtax1_tx, $invoicerecline->txlocaltax2, 0, 'HT', $invoicerecline->info_bits, $invoicerecline->product_type, $mysoc, array(), 100);

								$invoicerecline->total_ht  = $tabprice[0];
								$invoicerecline->total_tva = $tabprice[1];
								$invoicerecline->total_ttc = $tabprice[2];
								$invoicerecline->total_localtax1 = $tabprice[9];
								$invoicerecline->total_localtax2 = $tabprice[10];

								$result = $invoicerecline->update($user, 1);
							}
						}
					}
				}
			}
			//and invoicerec
		}

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';
		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	/**
	 * Scheduled job to archive signed/sealed documents.
	 * Creates backup copies of signed files and re-downloads missing ones from the API.
	 *
	 * @return int 0 if OK, <>0 if KO
	 */
	public function doScheduledArchive()
	{
		global $conf;

		dol_syslog("uptosign doScheduledArchive cron start");

		if (empty($conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON)) {
			dol_syslog("uptosign doScheduledArchive cron is not enabled");
			return 0;
		}

		$sql = "SELECT rowid, ref, sign_id, status, path_file, path_file_signed,";
		$sql .= " hash_file_signed, date_sign, tms, object_type, fk_object";
		$sql .= " FROM " . MAIN_DB_PREFIX . "uptosign";
		$sql .= " WHERE status IN (" . self::STATUS_SIGNED . ", " . self::STATUS_SEALED . ", " . self::STATUS_FILE_FETCHED . ")";
		$sql .= " AND sign_id IS NOT NULL AND sign_id != ''";
		$sql .= " AND entity IN (" . getEntity('uptosign') . ")";
		$sql .= " ORDER BY rowid ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog("uptosign doScheduledArchive SQL error: " . $this->error, LOG_ERR);
			return -1;
		}

		$archived = 0;
		$errors = 0;
		$num = $this->db->num_rows($resql);
		dol_syslog("uptosign doScheduledArchive found $num procedures to check");

		while ($obj = $this->db->fetch_object($resql)) {
			if (empty($obj->path_file)) {
				dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " no path_file, skip");
				continue;
			}

			$fullPathFile = uptosign_full_path($obj->path_file);
			$dirname = dirname($fullPathFile);
			$basename = pathinfo($fullPathFile, PATHINFO_FILENAME);

			$dateValue = !empty($obj->date_sign) ? $obj->date_sign : $obj->tms;
			$archiveTs = $this->db->jdate($dateValue);
			// jdate should return int, but some DB drivers (SQLite) may return string
			if (!is_int($archiveTs)) {
				$archiveTs = strtotime((string) $dateValue) ?: time();
			}
			$dateStr = date('Ymd', $archiveTs);
			$archiveFile = $dirname . '/' . $basename . '_archive_uptosign_' . $dateStr . '.pdf';

			if (file_exists($archiveFile)) {
				continue;
			}

			// Try to archive from local signed file
			if (!empty($obj->path_file_signed)) {
				$fullSignedFile = uptosign_full_path($obj->path_file_signed);
				if (file_exists($fullSignedFile)) {
					if (copy($fullSignedFile, $archiveFile)) {
						dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " archived from local file");
						$archived++;
					} else {
						dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " copy failed", LOG_ERR);
						$errors++;
					}
					continue;
				}
			}

			// Signed file missing or never downloaded → re-download from API
			$response = $this->apiClient->downloadDocument($obj->sign_id);

			if ($response['http_code'] == 200 && strlen($response['content']) > 1024) {
				$fp = fopen($archiveFile, 'w');
				if (!$fp) {
					dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " cannot open archive file for writing", LOG_ERR);
					$errors++;
					continue;
				}
				fwrite($fp, $response['content']);
				fclose($fp);
				dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " archived from API re-download");
				$archived++;

				// Also update DB record if signed file was missing
				$needUpdate = empty($obj->path_file_signed) || !file_exists(uptosign_full_path($obj->path_file_signed));
				if ($needUpdate) {
					$hash = hash_file('sha256', $archiveFile);
					$relativePath = uptosign_relative_path($archiveFile);
					$sqlUpdate = "UPDATE " . MAIN_DB_PREFIX . "uptosign SET";
					$sqlUpdate .= " path_file_signed = '" . $this->db->escape($relativePath) . "'";
					$sqlUpdate .= ", hash_file_signed = '" . $this->db->escape($hash) . "'";
					$sqlUpdate .= ", status = " . self::STATUS_FILE_FETCHED;
					$sqlUpdate .= " WHERE rowid = " . ((int) $obj->rowid);
					$this->db->query($sqlUpdate);
					dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " updated path_file_signed to archive");
				}
			} elseif ($response['http_code'] == 404) {
				dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " file expired on server (404)", LOG_WARNING);
			} else {
				dol_syslog("uptosign doScheduledArchive rowid=" . $obj->rowid . " API error http_code=" . $response['http_code'], LOG_ERR);
				$errors++;
			}
		}

		$this->db->free($resql);

		$this->output = "doScheduledArchive: $archived files archived, $errors errors ($num procedures checked)";
		dol_syslog("uptosign " . $this->output);

		return 0;
	}

	/**
	 * Surchage uniquement pour capturer le champ fk_object
	 * tous les autres champs sont traités par le code principal dolibarr
	 *
	 * @param  array   $val		       Array of properties of field to show
	 * @param  string  $key            Key of attribute
	 * @param  string  $object         list object with preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $showsize       Value for css to define size. May also be a numeric.
	 * @param  int     $hiddenref      1 to return the reference field
	 * @return string                  String with html field
	 */
	public function showOutputField($val, $key, $object, $moreparam = '', $keysuffix = '', $keyprefix = '', $showsize = 0, $hiddenref = 0)
	{
		global $db;
		//
		$value = "";
		if ($key == "fk_object") {
			$objectType = $this->object_type;
			$id = $this->fk_object;
			$hallobj = uptosign_handle_all_type_of_objects($objectType, $id);
			$object = $hallobj['object'];
			if (method_exists($object, 'getNomUrl')) {
				return $object->getNomUrl();
			}
		} elseif ($key == 'object_type') {
			//due to bug #10789
			$value = uptosign_translate_object_type($object);
		}

		if ($value != "") {
			return $value;
		}

		return parent::showOutputField($val, $key, $object, $moreparam, $keysuffix, $keyprefix, $showsize);
	}


	/**
	 * Return origin object
	 *
	 * @param int $origintype Type origin
	 *
	 * @return string
	 */
	public function getOrigin($origintype)
	{
		$origin = '';
		switch ($origintype) {
			case 'commande':
				require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
				$origin = new Commande($this->db);
				break;
			case 'shipping':
				require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
				$origin = new Expedition($this->db);
				break;
			case 'order_supplier':
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
				$origin = new CommandeFournisseur($this->db);
				break;
			case 'product':
				require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
				$origin = new Product($this->db);
				break;
			case 'propal':
				require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
				$origin = new Propal($this->db);
				break;
			case 'member':
				require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
				$origin = new Adherent($this->db);
				break;
			case 'facture':
				require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
				$origin = new Facture($this->db);
				break;
			case 'contrat':
				require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
				$origin = new Contrat($this->db);
				break;
			case 'expensereport':
				require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
				$origin = new ExpenseReport($this->db);
				break;
			case 'fichinter':
				require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
				$origin = new Fichinter($this->db);
				break;
			case 'invoice_supplier':
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
				$origin = new FactureFournisseur($this->db);
				break;
			case 'supplier_proposal':
				require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
				$origin = new SupplierProposal($this->db);
				break;
			case 'user':
				require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
				$origin = new User($this->db);
				break;
			case 'contact':
				require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
				$origin = new Contact($this->db);
				break;
			case 'sepamandate':
				require_once DOL_DOCUMENT_ROOT . '/societe/class/companypaymentmode.class.php';
				$origin = new CompanyPaymentMode($this->db);
				break;
			case 'bankaccount':
			case 'bank':
				require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
				$origin = new CompanyBankAccount($this->db);
				break;
			case 'societe':
				require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
				$origin = new Societe($this->db);
				break;
			default:
				break;
		}

		if (empty($origin) || !is_object($origin)) {
			return null;
		}
		return $origin;
	}


	/**
	 * Return Url link of origin object
	 *
	 * @param int $fk_origin  Id origin
	 * @param int $origintype Type origin
	 *
	 * @return string
	 */
	public function getOriginUrl($fk_origin, $origintype)
	{
		$origin = $this->getOrigin($origintype);

		if (empty($origin) || !is_object($origin)) {
			return '';
		}

		if ($origin->fetch($fk_origin) > 0) {
			return $origin->getNomUrl(1);
		}

		return '';
	}


	/**
	 * save hash of hook key
	 *
	 * @param   string  $key  key
	 *
	 * @return  int     id if > 0 error < 0
	 */
	public function updateHookKey($key)
	{
		$error = 0;
		//Sauvegarde locale de dol_hash($hookKEY);
		//Et utilisation de dol_verifyHash
		dol_syslog("uptosign document set hook_key to " . $key . " for doc id " . $this->id);
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET hook_key='" . $this->db->escape($key) . "' WHERE rowid=" . ((int) $this->id);

		$this->db->begin();
		$res = $this->db->query($sql);
		if ($res === false) {
			$error++;
			array_push($this->errors, $this->db->lasterror());
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}



	/**
	 * Init to Uptosign signature
	 *
	 * @param object    $user               user who initiates signing
	 * @param object    $object             dolibarr object to sign
	 * @param string    $dir                dir where pdf file is stored
	 *
	 * @return int 0 = OK, < 0 NOK
	 */
	public function signInit($user, $object, $dir)
	{
		global $mysoc, $conf, $langs;
		$user = $this->findUserToUse($user, $this);
		dol_syslog("uptosign : signInit 1, dir=$dir"); // . json_encode($object));

		$fileToSign = array();
		$coordinates = array();
		$listMembers = array();
		$positions = array();
		$contactToSign = new ArrayObject();
		$error = 0;
		//pour eviter les multisignatures qui se recouvrent
		$posXposYused = array();

		if (!is_object($object)) {
			dol_syslog("uptosign: signInit Error object var is not an object", LOG_ERR);
			array_push($this->errors, "signInit (object is not an object !)");
			return -1;
		}

		if (isset($object->redirectSign)) {
			$this->redirect_sign = true;
		}
		dol_syslog("uptosign : signInit 2, redirect_sign=" . json_encode($this->redirect_sign)); // . json_encode($object));

		if (isset($object->contactToSignID)) {
			dol_syslog("signInit, object->contactToSignID is defined = " . $object->contactToSignID);
			$contact = new Contact($this->db);
			if ($result = $contact->fetch($object->contactToSignID) > 0) {
				$contactToSign->append($contact);
				dol_syslog("signInit, object->contactToSignID found, email is " . $contact->email);
			} else {
				dol_syslog("signInit, object->contactToSignID NOT found");
			}
		}

		$config = new UptoSignConfig($this->db);

		//Si on a passé un path direct vers un fichier
		if (dol_is_file($dir)) {
			$file = $dir;
		} else {
			$dolDirList = dol_dir_list($dir, "files", 0, '\.pdf$', ['(\.meta|_preview.*\.png)$']);
			dol_syslog("uptosign: signInit search file into $dir :: (" . json_encode($dolDirList) . ")", LOG_ERR);
			$file = null;
			foreach ($dolDirList as $dolDir) {
				if ($dolDir['type'] == 'file' && basename($dolDir['name'], '.pdf') == $object->ref) {
					$file = $dolDir['fullname'];
					break;
				} elseif ($dolDir['type'] == 'file' && preg_match("/" . $object->ref . "/", basename($dolDir['name'], '.pdf'))) {
					$file = $dolDir['fullname'];
					break;
				} elseif ($dolDir['type'] == 'file' && strpos($dolDir['name'], '.pdf') !== false) {
					$file = $dolDir['fullname'];
				}
			}
		}
		if (null === $file) {
			dol_syslog("uptosign: signInit Error there is no file linked to that object (or file doest not have share link)", LOG_ERR);
			array_push($this->errors, "UptoSignNoPdfFilesAssociated");
			return -3;
		}

		//mots clés magiques ?
		$action = "presign";
		$arrMagicReturn = [];
		$autopositionSeal = $autopositionSign = false; //new way: seal is distinct of sign
		$positionsSeal = $positionsSign = array();
		if (uptosign_auto_position_magic_keywords($file, $arrMagicReturn, $action)) {
			dol_syslog('uptosign: auto position detect (a) :' . json_encode($arrMagicReturn));

			if (isset($arrMagicReturn['STAMP'])) {
				foreach ($arrMagicReturn['STAMP'] as $page => $value) {
					$positionsSeal[$page] = array(
						'defaultSealX' => (!empty($value['x']) ? $value['x'] : 0),
						'defaultSealY' => (!empty($value['y']) ? $value['y'] : 0),
						'defaultSealPage' => (!empty($value['p']) ? $value['p'] : 0)
					);
					$autopositionSeal = true;
				}
			}
			for ($idn = 0; $idn < 10; $idn++) {
				$tag = sprintf("SIGN_%'02d", $idn);
				if (isset($arrMagicReturn[$tag])) {
					foreach ($arrMagicReturn[$tag] as $page => $value) {
						dol_syslog("uptosign: auto position detect (sign debug) : $tag // $idn // page=$page :: " . $value['p']);
						$positionsSign[$page][$tag] = array(
							'defaultSignContactX' => (!empty($value['x']) ? $value['x'] : 0),
							'defaultSignContactY' => (!empty($value['y']) ? $value['y'] : 0),
							'defaultSignContactPage' => (!empty($value['p']) ? $value['p'] : 0)
						);
					}
					$autopositionSign = true;
				}
			}
			for ($idn = 0; $idn < 10; $idn++) {
				$tag = sprintf("FROM_%'02d", $idn);
				if (isset($arrMagicReturn[$tag])) {
					foreach ($arrMagicReturn[$tag] as $page => $value) {
						$positionsSign[$page][$tag] = array(
							'defaultSignUserX' => (!empty($value['x']) ? $value['x'] : 0),
							'defaultSignUserY' => (!empty($value['y']) ? $value['y'] : 0),
							'defaultSignUserPage' => (!empty($value['p']) ? $value['p'] : 0)
						);
					}
					$autopositionSign = true;
				}
			}

			dol_syslog('uptosign: auto position detect (b), sign :' . json_encode($positionsSign));
			dol_syslog('uptosign: auto position detect (b), seal :' . json_encode($positionsSeal));
		}

		// note: partial fail possible: autoposition ok pour signature mais pas pour le sceau .. il faut donc quand meme passer sur ce bloc de code
		if ($autopositionSeal == false || $autopositionSign == false) {
			//configuration automatique via les modeles de signature ?
			$model_pdf = uptosignModel($object);
			$configIds = $config->fetchListId($model_pdf, $object->element, 'sign');
			if ($configIds) {
				// print json_encode($configIds);
				if (is_array($configIds)) {
					dol_syslog("uptosign: signInit configIds : " . json_encode($configIds));
					$config->fetch($configIds[0]);

					//C'est là qu'on evite de prendre l'info si le autoposition a retourné qqchose : ces données ont été renseignées par autoconf
					if (!$autopositionSeal) {
						$d = explode(',', $config->seal_coordinate);
						$positionsSeal[$config->page_seal]['STAMP']['defaultSealX'] = $d[0];
						$positionsSeal[$config->page_seal]['STAMP']['defaultSealY'] = $d[1];
						$positionsSeal[$config->page_seal]['STAMP']['defaultSealPage'] = $config->page_seal;
						$autopositionSeal = true;
					}
					//Une seule signature "client" préconfigurée => pour plus de sugnatures utiliser les mots 'magiques'
					if (!$autopositionSign) {
						if (!empty($config->page_sign)) {	// test si la signature n'est pas désactivée pour ce type de document
							$d = explode(',', $config->sign_coordinate);
							$positionsSign[$config->page_sign]['SIGN_00']['defaultSignContactX'] = $d[0];
							$positionsSign[$config->page_sign]['SIGN_00']['defaultSignContactY'] = $d[1];
							$positionsSign[$config->page_sign]['SIGN_00']['defaultSignContactPage'] = $config->page_sign;
							$autopositionSign = true;
						} else {
							$noSign = 1;
						}
					}

					dol_syslog('uptosign: signInit: position via profil de doc :' . json_encode($positions));
				}
			} else {
				dol_syslog("uptosign: signInit Error configIds var is not an id", LOG_ERR);
				array_push($this->errors, "signInit Error configIds var is not an id");
				array_push($this->errors, $config->errors);
				return -2;
			}
			dol_syslog("uptosign : signInit config is " . json_encode($config));
		}

		//a cette etape il y a forcément une position, si ce n'est pas le cass -> erreur propre
		$listofPositions = implode('', $positionsSign);
		if (empty($listofPositions)) {
			dol_syslog("uptosign : signInit there is no positions for that document !", LOG_ERR);
			array_push($this->errors, "signInit Error there is no sign positions for that document !");
			return -4;
		}

		// dol_syslog("uptosign : signInit positions is " . json_encode($positions));
		// print "<p>uptosign : signInit config is " . json_encode($configIds) . "</p>";
		// dol_syslog("uptosign : signInit config is " . json_encode($configIds));

		$title = uptosign_translate_object_type($object->element);
		if (isset($object->ref_client)) {
			$title .= " " . $object->ref_client;
		}

		dol_syslog('uptosign: auto position detect (c), sign :' . json_encode($positionsSign));
		dol_syslog('uptosign: auto position detect (c), seal :' . json_encode($positionsSeal));

		//defautl sign page = first
		$signOnPage = 1;
		$indexMember = 0;
		$fileToSign = null;

		foreach ($positionsSeal as $page => $position) {
			//1er tour -> initialisation du fileToSign
			if (null === $fileToSign) {
				dol_syslog('uptosign: seal position :' . json_encode($position));
				$fileToSign = array(
					'filename' => basename($file),
					'fullname' => $file,
					'title' =>  $title . " (" . $object->ref . ")",
					'content' => base64_encode(file_get_contents($file)),
					'posx' => $position['STAMP']['defaultSealX'],
					'posy' => $position['STAMP']['defaultSealY'],
					'signonpage' => $position['STAMP']['defaultSealPage'],
					'stampnumber' => '1',
				);
			}
		}

		if (!empty($positionsSign)) {
			foreach ($positionsSign as $page => $position) {
				// print json_encode($config->label);

				dol_syslog("uptosign : config label is " . json_encode($config->label));
				dol_syslog("uptosign : position is " . json_encode($position));

				if (isset($position['SIGN_00']['defaultSignContactX'])) {
					//contacts liés au document
					$contacts = new ArrayObject();

					if (!empty($contactToSign)) {
						dol_syslog("uptosign : contactToSign is not empty, use it as local contacts " . json_encode($contactToSign));
						$contacts = $contactToSign;
					} else {
						dol_syslog("uptosign : contactToSign is empty, call whocansign" . json_encode($contactToSign));
						$this->whoCanSign($object, 'external', $config->label, $contacts);
					}

					dol_syslog("uptosign : contactToSign contacts is now " . json_encode($contacts));
					// print "<p>contacts : " . json_encode($contacts) . "</p>";

					foreach ($contacts as $contact) {
						if (empty($contact->firstname) && empty($contact->lastname)) {
							dol_syslog("uptosign : contactToSign contacts supprimé car nom et prénoms vides !");
							array_push($this->errors, "signInit firstname AND lastname empty, ignore that people " . $contact->email);
							continue 1;
						}
						if (empty($contact->country_code)) {
							$contact->country_code = 'FR';
						}
						$contact->phone_mobile = uptoSignSearchMobile($contact->phone_mobile, $contact->phone_pro, $contact->country_code);

						$posXY = $position['SIGN_00']['defaultSignContactX'] . ':' . $position['SIGN_00']['defaultSignContactY'];
						if (in_array($posXY, $posXposYused)) {
							$position['defaultSignContactY'] += 30;
							$posXY = $position['SIGN_00']['defaultSignContactX'] . ':' . $position['SIGN_00']['defaultSignContactY'];
							$posXposYused[] = $posXY;
						}
						$posXposYused[] = $posXY;
						$listMembers[] = array(
							'dolid' => $contact->id,
							'doltype' => 'contact',
							'firstname' => $contact->firstname,
							'lastname' => $contact->lastname,
							'societe' => $contact->socname,
							'email' => $contact->email,
							'mobile' => $contact->phone_mobile,
							'signPosX' => $position['SIGN_00']['defaultSignContactX'],
							'signPosY' => $position['SIGN_00']['defaultSignContactY'],
							'signPage' => $position['SIGN_00']['defaultSignContactPage'] ?? $signOnPage,
						);
						$indexMember++;
					}
				} else {
					$users = new ArrayObject();
					$this->whoCanSign($object, 'internal', $config->label, $users);
					foreach ($users as $oneuser) {
						if (empty($oneuser->country_code)) {
							$oneuser->country_code = 'FR';
						}
						$mobile = uptoSignSearchMobile($oneuser->user_mobile, $oneuser->office_phone, $oneuser->country_code);

						$posXY = $position['SIGN_00']['defaultSignUserX'] . ':' . $position['SIGN_00']['defaultSignUserY'];
						if (in_array($posXY, $posXposYused)) {
							$position['SIGN_00']['defaultSignUserY'] += 30;
							$posXY = $position['SIGN_00']['defaultSignUserX'] . ':' . $position['SIGN_00']['defaultSignUserY'];
							$posXposYused[] = $posXY;
						}
						$posXposYused[] = $posXY;

						$listMembers[] = array(
							'dolid' => $oneuser->id,
							'doltype' => 'user',
							'firstname' => $oneuser->firstname,
							'lastname' => $oneuser->lastname,
							'societe' => $mysoc->name,
							'email' => $oneuser->email,
							'mobile' => $mobile,
							'signPosX' => $position['SIGN_00']['defaultSignUserX'],
							'signPosY' => $position['SIGN_00']['defaultSignUserY'],
							'signPage' => $position['SIGN_00']['defaultSignUserPage'] ?? $signOnPage,
						);
						$indexMember++;
					}
				}
			}
		}
		dol_syslog("uptosign call listMembers is " . json_encode($listMembers));

		// print json_encode($listMembers);
		// exit;

		if (!is_array($listMembers) || count($listMembers) <= 0) {
			dol_syslog("uptosign UptoSignContactMissing");

			array_push($this->errors, $langs->trans("UptoSignContactMissing"));
			if ($this->redirect_sign == 'true') {
				array_push($this->errors, $langs->trans("UptoSignContactMissingHelpPublic"));
			} else {
				array_push($this->errors, $langs->trans("UptoSignContactMissingHelp"));
			}
			return -9;
		}

		$object->listMembers = $listMembers;

		//le message pour l'évènement dans l'agenda
		$object->uptosignTitle = $langs->trans("UptoSignProcessTitle");
		$object->uptosignMessage = $langs->trans("UptoSignProcessStarted");

		$res = $this->sealOrSignInitLight($user, $object, $fileToSign, $listMembers, "sign");
		dol_syslog("uptosign Retour de signInit appel de sealOrSignInitLight (a) = " . json_encode($res));

		return $res;
	}



	/**
	 * Version légère et complètement neuve de la procédure
	 *
	 * @param   User  $user  [$user description]
	 *
	 * @return  int         [return description]
	 */
	public function sealOrSignInitLight($user, $object, $fileToSign, $listMembers, $procedure = "seal")
	{
		global $mysoc, $conf, $langs;
		$error = 0;

		dol_syslog("sealOrSignInitLight started with user=" . ($user->login ?? "(login empty)"));

		$resultContent = $this->initProcedureLight($fileToSign, $listMembers, $procedure);
		// dol_syslog("sealOrSignInitLight retour de initProcedureLight = " . json_encode($resultContent));
		// dol_syslog("sealOrSignInitLight fileToSign = " . json_encode($fileToSign));

		// dol_syslog("sealOrSignInitLight procedure = $procedure et retour de initProcedureLight = " . json_encode($resultContent));
		// dol_syslog("uptosign : sealOrSignInitLight listMembers=" . json_encode($listMembers));
		dol_syslog("uptosign sealOrSignInitLight : user is = " . json_encode($user));

		if (count($this->errors) > 0) {
			dol_syslog("uptosign : sealOrSignInitLight return errors");
			return --$error;
		}

		if ($resultContent['action'] == $procedure) {
			$object->signOrSeal = $procedure;
			$rest = $this->createEvent($object);
			// print $rest; exit;

			$contactID = $forcelement_type = $forcelement_id = null;
			if (isset($listMembers) && is_array($listMembers)) {
				foreach ($listMembers as $member) {
					if ($member['doltype'] == 'contact') {
						$contactID = $member['dolid'];
						dol_syslog("uptosign sealOrSignInitLight contactID = $contactID");
						break;
					}
				}
			}


			$fksoc = $object->socid;
			if ($object->element == "societe") {
				$fksoc = $object->id;
			}

			//list of sign for massive sign
			if ($object->element == "uptosignlist") {
				if (count($listMembers) == 1) {
					$forcelement_type = $listMembers[0]['doltype'];
					$forcelement_id   = $listMembers[0]['dolid'];
				}
			}

			// dol_syslog("uptosign seal " . json_encode($user));
			// dol_syslog("uptosign seal " . json_encode($object));
			// create uptosign object
			$this->ref = $this->getNextNumRef();
			$this->hash_file = hash_file('sha256', $fileToSign['fullname']);
			$this->label = $fileToSign['title'] ?? '';
			$this->sign_id = $resultContent['id'];
			$this->sign_status = $resultContent['status'];
			$this->status = UptoSign::STATUS_WAITING;
			$dateCreate = new DateTime();
			$this->date_creation = $dateCreate->getTimestamp();
			$this->fk_object = $forcelement_id ?? $object->id;
			$this->object_type = $forcelement_type ?? uptosign_unify_object_type($object->element);
			$this->fk_user_sign = $this->findUserToUse($user, $object, true);
			$this->fk_user_creat = $this->findUserToUse($user, $object, true);
			$this->fk_soc = $fksoc;
			$this->fk_contact_sign = $contactID;
			if ($object->element == "uptosignlist") {
				$this->fk_uptosignlist = $object->id;
			}
			$this->sign_link = $resultContent['url'];
			$this->disableSms = $this->_searchDisableSMS($fksoc);

			dol_syslog("uptosign sealOrSignInitLight : ============================================= 1", LOG_DEBUG);

			//NOTE: to be close to dolibarr core, signed files are suffixed with _signed + date
			$file = $fileToSign['fullname'];
			if ($procedure == "seal") {
				$this->api_name = 'uptoseal';
				$this->description = $langs->trans("UptoSignDocumentSeal") . ' - ' . $object->ref;
				if ($this->label == '') {
					$this->label = $langs->trans("UptoSignDocumentSeal");
				}
			} else {
				$this->api_name = 'uptosign';
				$this->description = $langs->trans("UptoSignDocumentSign") . ' - ' . $object->ref;
			}
			$this->path_file = $file;

			// dol_syslog("uptosign sealOrSignInitLight user = " . json_encode($user));
			dol_syslog("uptosign sealOrSignInitLight : ============================================= user is = " . json_encode($user), LOG_DEBUG);
			$res = $this->create($user);
			dol_syslog("uptosign sealOrSignInitLight : ============================= end, res=$res", LOG_DEBUG);
			if ($res < 0) {
				return $res;
			}

			//test rebond automatique
			dol_syslog("uptosign redirect_sign=" . $this->redirect_sign . ", redirect=" . $resultContent['redirect'] . ", resultContent url= " . $resultContent['url']);
			if ($this->redirect_sign == 'true' && $resultContent['redirect'] == 'available' && $resultContent['url'] != "") {
				ob_clean();
				header("Location: " . $resultContent['url']);
				ob_flush();
				exit;
			}
		}
		return 0;
	}

	/**
	 * Cette méthode est utilisée pour initialiser une demande de signature.
	 * Vous passerez en paramètre une liste de fichiers à signer ainsi qu'une liste d'informations des signataires.
	 * procedure = sign|seal
	 * return < 0 in case of error
	 */
	public function initProcedureLight(&$fileToSign, $listMembers, $procedure)
	{
		global $user, $conf, $langs;
		$error = 0;

		// ============== version light test proto archive probante -> 1 seul fichier à signer faire le concatpdf avant si nécessaire
		// dol_syslog("uptosign: initProcedureLight fileToSign = " . json_encode($fileToSign));
		dol_syslog("uptosign: initProcedureLight ($procedure) listMembers = " . json_encode($listMembers));
		if ($procedure == "sign" && !is_array($listMembers)) {
			setEventMessages($langs->trans('UptoSignContactMissing'), [], 'warnings');
			array_push($this->errors, "UptoSignContactMissing");
			array_push($this->errors, "UptoSignContactMissingHelp");
			return --$error;
		}

		if ($fileToSign == "") {
			setEventMessages($langs->trans('UptoSignFileMissing'), [], 'warnings');
			array_push($this->errors, "UptoSignFileMissing");
			array_push($this->errors, "UptoSignFileMissingHelp");
			return --$error;
		}

		//force https even if there is nothing (implicit uri is like "//")
		$hookURI = dol_buildpath('/custom/uptosign/public/hook.php', 3);
		if (substr($hookURI, 0, 2) == "//") {
			$hookURI = "https:" . dol_buildpath('/custom/uptosign/public/hook.php', 3);
		}
		$hookKEY = uptosignStrRand(64);
		$this->hook_key = $hookKEY;

		$endRedirect = $this->endRedirect ?? '';

		//backward compatible
		$migrate = ['sign_page' => 'signPage', 'sign_pos_x' => 'signPosX', 'sign_pos_y' => 'signPosY'];
		if (isset($listMembers) && is_array($listMembers)) {
			foreach ($listMembers as $member) {
				foreach ($migrate as $new => $old) {
					if (isset($member[$new])) {
						$member[$old] = $member[$new];
					}
				}
			}
		}

		$data = [
			"test" => "ok",
			"pdf" => $fileToSign,
			"to" => ['multiSign' => $listMembers],
			"alerts" => $user->email,
			"infos" => utsbackports_getDolGlobalString('UPTOSIGN_SEND_NOTIF_MAIL_CC', ''),
			"hook" => [
				"uri" => $hookURI,
				"key" => $hookKEY
			],
			"conf" => [
				"endRedirect" => $endRedirect,
				"hideMailAndPhone"   => $this->hideMailAndPhone ?? 0,
				"disableSms"   => $this->disableSms ?? 0
			]
		];
		if ($this->redirect_sign) {
			$data['process'] = 'now';
		}

		$dataDebug = $data;
		if(isset($dataDebug["pdf"]["content"])) {
			unset($dataDebug["pdf"]["content"]);
		}
		dol_syslog("uptosign: initProcedureLight send data (without dump pdf) = " . json_encode($dataDebug));
		dol_syslog("uptosign: initProcedureLight send data TITLE = " . json_encode($fileToSign['title']));
		dol_syslog("uptosign: initProcedureLight send redirect to = " . $endRedirect);
		// debug
		// return 0;

		$response = $this->apiClient->createProcedure($data, $procedure);
		$resultContent = $response['data'];

		if ($response['http_code'] == 200 && $resultContent !== null) {
			dol_syslog("uptosign: initProcedureLight resultContent 2 : " . json_encode($resultContent));
		} elseif ($response['http_code'] == 403) {
			dol_syslog("UptoSignApiError initProcedureLight error 403 : " . json_encode($resultContent['message'] ?? ''));
			array_push($this->errors, 'UptoSignApiError 403 : ' . ($resultContent['message'] ?? ''));
			return --$error;
		} else {
			dol_syslog("UptoSignApiError initProcedureLight 3 : " . json_encode($resultContent['message'] ?? ''));
			array_push($this->errors, 'UptoSignApiError 3a : ' . $response['http_code']);
			array_push($this->errors, json_encode($resultContent['message'] ?? ''));
			return --$error;
		}

		return $resultContent;
	}


	/**
	 * Init to Uptosign seal only
	 *
	 * @param object    $user               user who initiates signing
	 * @param object    $object             dolibarr object to sign
	 * @param string    $dir                dir or fullfile name
	 *
	 * @return int 0 = OK, < 0 NOK
	 */
	public function sealInit($user, $object, $dir)
	{
		global $mysoc, $conf, $langs;
		// dol_syslog("uptosign : sealInit 1");

		$fileToSign = array();
		$coordinates = array(0, 0);
		$signOnPage = $error = 0;

		$config = new UptoSignConfig($object->db);
		if (!is_object($object)) {
			dol_syslog("uptosign sealInit error, object is not an object !", LOG_ERR);
			array_push($this->errors, "ConfigInitError (object or ids)");
			return --$error;
		}

		//Si on a passé un path direct vers un fichier
		if (dol_is_file($dir)) {
			$file = $dir;
		} else {
			$dolDirList = dol_dir_list($dir, "files", 0, '\.pdf$', ['(\.meta|_preview.*\.png)$']);
			dol_syslog("uptosign: sealInit search file into $dir :: (" . json_encode($dolDirList) . ")", LOG_ERR);
			$file = null;
			foreach ($dolDirList as $dolDir) {
				if ($dolDir['type'] == 'file' && basename($dolDir['name'], '.pdf') == $object->ref) {
					$file = $dolDir['fullname'];
					break;
				} elseif ($dolDir['type'] == 'file' && preg_match("/" . $object->ref . "/", basename($dolDir['name'], '.pdf'))) {
					$file = $dolDir['fullname'];
					break;
				} elseif ($dolDir['type'] == 'file' && strpos($dolDir['name'], '.pdf') !== false) {
					$file = $dolDir['fullname'];
				}
			}
		}
		if (null === $file) {
			dol_syslog("uptosign: sealInit (0) Error there is no file linked to that object (or file doest not have share link)", LOG_ERR);
			array_push($this->errors, "UptoSignNoPdfFilesAssociated");
			return -3;
		}

		$action = "preseal";
		$arrMagicReturn = [];
		$autopositionSeal = false;
		$positionsSeal = array();
		if (uptosign_auto_position_magic_keywords($file, $arrMagicReturn, $action)) {
			dol_syslog('uptosign: auto position detect (a) :' . json_encode($arrMagicReturn));

			if (isset($arrMagicReturn['STAMP'])) {
				foreach ($arrMagicReturn['STAMP'] as $page => $value) {
					$positionsSeal['STAMP'] = array(
						'defaultSealX' => (!empty($value['x']) ? $value['x'] : 0),
						'defaultSealY' => (!empty($value['y']) ? $value['y'] : 0),
						'defaultSealPage' => (!empty($value['p']) ? $value['p'] : 0)
					);
					$autopositionSeal = true;
					//only first
					break;
				}
			}
			dol_syslog('uptosign: auto position detect (b), seal :' . json_encode($positionsSeal));
		}

		if ($autopositionSeal == false) {
			//configuration automatique du scellement par les modèles à l'ancienne
			$model_pdf = uptosignModel($object);
			$configIds = $config->fetchListId($model_pdf, $object->element, 'seal');
			// dol_syslog("uptosign : sealInit 2 : " . json_encode($configIds));
			if (!is_array($configIds)) {
				dol_syslog("uptosign sealInit error, there is no configuration for that document ($object->element > $model_pdf)", LOG_ERR);
				array_push($this->errors, "There is no seal configuration for that document ($object->element > $model_pdf)");
				array_push($this->errors, $config->errors);
				return --$error;
			}

			dol_syslog("uptosign sealInit, utilisation de la configuration #" . $configIds[0]);
			//Une seule réponse possible
			$config->fetch($configIds[0]);

			dol_syslog("uptosign sealInit, utilisation de la configuration details=" . json_encode($config));

			$signOnPage = 1;
			if ($config->page_seal != 0) {
				$signOnPage = $config->page_seal;
				dol_syslog("uptosign sealInit configuration #" . $configIds[0] . " has pageNumber = " . $signOnPage);
			}

			if ($config->seal_coordinate != "") {
				$coordinates = explode(',', $config->seal_coordinate);
				$positionsSeal['STAMP'] = array(
					'defaultSealX' => $coordinates[0],
					'defaultSealY' => $coordinates[1],
					'defaultSealPage' => $signOnPage
				);
			}
		}

		$fileToSign = array(
			'filename' => basename($file),
			'fullname' => $file,
			'content' => base64_encode(file_get_contents($file)),
			'posx' => $positionsSeal['STAMP']['defaultSealX'],
			'posy' => $positionsSeal['STAMP']['defaultSealY'],
			'signonpage' => $positionsSeal['STAMP']['defaultSealPage'],
			'stampnumber' => '1',
		);
		dol_syslog("uptosign : sealInit fileToSign signPage=$signOnPage, coordinates = " . json_encode($coordinates));

		// var_dump($fileToSign, $listMembers, $fileObjects, $procedure, $email, $redirectSign, $reminders);exit;
		// var_dump($procedure, $reminders); exit;
		// $procedure = "seal";

		//le message pour l'évènement dans l'agenda
		$object->uptosignTitle = $langs->trans("UptoSealProcessTitle");
		$object->uptosignMessage = $langs->trans("UptoSealProcessStarted");

		$resultContent = $this->sealOrSignInitLight($user, $object, $fileToSign, null, "seal");

		dol_syslog("uptosign/seal Retour de initProcedure (b) = " . json_encode($resultContent));

		if (count($this->errors) > 0) {
			return --$error;
		}
		return 0;
	}

	/**
	 * get info on Uptosign process
	 *
	 * @param object    $user               user who gets signing info
	 * @param object    $object             dolibarr object
	 * @param string    $mode               'sync' or 'info' or 'local'(do not make network requests)
	 * 										'synchistory' to make refresh on activity list
	 *
	 * @return int with null = no info, lowest status = OK, < 0 if NOK
	 */
	public function signInfo($user, $object, $mode = 'sync')
	{
		global $langs, $conf, $mysoc;
		dol_syslog("uptosign::signInfo $mode");

		$langs->loadLangs(array("uptosign@uptosign", "main", "other", "companies", "errors"));
		$error = 0;
		$objectId = $object->id;
		$objectType = $object->element;
		$status = null;

		//a detailler le coup des childs
		$children = $this->fetchChilds($objectId, $objectType);
		if ($children == -1) {
			dol_syslog("uptosign::signInfo fetch childs returns -1, early return"); // . json_encode($children));
			return UptoSign::STATUS_NOTHING;
		}
		dol_syslog("uptosign::signInfo get children for $objectId / type $objectType"); // . json_encode($children));
		// if (is_array($children) && count($children) > 0) {
		//On ne cherche que sur un seul ... ça evite les galeres quand il y a eu des f5 / refresh
		//mais a voir si ça ne pose pas de soucis pour des objets sur lesquels on lancerait volontairement
		//plusieurs actions de scellement / signatures
		//TODO
		// $child = reset($children);
		foreach ($children as $child) {
			$status = $child->status;
			dol_syslog("uptosign::signInfo get children for $objectId / type $objectType, status=$status");

			//si les 30 jours sont passés
			if (($child->tms + (30 * 24 * 60 * 60)) < dol_now() && in_array($child->status, [UptoSign::STATUS_NOTHING, UptoSign::STATUS_DRAFT, UptoSign::STATUS_REFUSED, UptoSign::STATUS_ERROR, UptoSign::STATUS_CANCELED, UptoSign::STATUS_WAITING])) {
				$child->status = UptoSign::STATUS_EXPIRED;
				$child->update($user);
				$status = $child->status;
				dol_syslog("uptosign::signInfo set children status=$status");
				// continue;
			}

			if ($mode == 'local') {
				dol_syslog("uptosign::signInfo mode is local, do not make network requests");
				continue;
			}

			//si le fichier est marqué "fetched" mais qu'il n'est pas la ... on le repasse en waiting
			if ($child->status > UptoSign::STATUS_WAITING) {
				// print json_encode($child);
				//path_file_signed
				$fullFileName = uptosign_full_path($child->path_file_signed);
				if (!is_file($fullFileName)) {
					$child->status = UptoSign::STATUS_WAITING;
					$child->update($user);
				}
			}

			if ($child->status == UptoSign::STATUS_WAITING || $child->status == UptoSign::STATUS_ERROR || $child->status == UptoSign::STATUS_DRAFT || $mode == 'info' || $mode == 'synchistory') {
				dol_syslog("uptosign::signInfo refresh data from server");
				$response = $this->apiClient->getDocumentStatus($child->sign_id);

				if ($response['http_code'] > 0) {
					$resultContent = $response['data'];
					if (isset($resultContent['data'])) {
						$resultContent = $resultContent['data'];
					}
					if ($response['http_code'] == 200) {
						dol_syslog("uptosign::signInfo initProcedure resultContent 3 : " . json_encode($resultContent));
						if (isset($resultContent['status'])) {
							$signStatus = $resultContent['status'];
						} else {
							$signStatus = 'deleted';
						}
						$child->sign_status = $signStatus;
						$child->sign_history = json_encode($resultContent['history']);
						$child->fk_user_modif = $user->id;
						$res = $child->update($user);
						$status = 0;
					} elseif ($response['http_code'] == 204) {
						dol_syslog("uptosign::signInfo initProcedure resultContent 204, " . json_encode($resultContent));
						array_push($this->errors, 'UptoSignApiError 204 : document not yet ready');
						array_push($this->errors, "Document not yet ready, please wait ...");
						$signStatus = 'active';
						$status = UptoSign::STATUS_WAITING;
						$child->sign_history = json_encode($resultContent['history']);
						$child->sign_status = $signStatus;
						$child->fk_user_modif = $user->id;
						$res = $child->update($user);
						return UptoSign::STATUS_WAITING;
					} else {
						dol_syslog("uptosign::signInfo UptoSignApiError (4) : " . $resultContent['message']);
						array_push($this->errors, 'UptoSignApiError 4 : ' . $response['http_code']);
						array_push($this->errors, $resultContent['message']);

						if (isset($resultContent['status'])) {
							$signStatus = $resultContent['status'];
						} else {
							$signStatus = 'deleted';
						}
						$child->sign_status = $signStatus;
						$child->fk_user_modif = $user->id;
						$res = $child->update($user);

						return --$error;
					}

					//a detailler -> rester sur child ...
					$res = $this->fetch($child->id);
					if ($res < 0) {
						dol_syslog("uptosign::signInfo res negative, return $res");
						return $res;
					} else {
						$signStatus = $resultContent['status'];
						dol_syslog("uptosign::signInfo res ok, resultContent status = $signStatus");
						$child->sign_status = $signStatus;
						if ($signStatus == 'draft') {
							$child->status = UptoSign::STATUS_DRAFT;
						} elseif ($signStatus == 'active' || $signStatus == 'waiting_for_sign_server') {
							$child->status = UptoSign::STATUS_WAITING;
						} elseif ($signStatus == 'finished' || $signStatus == 'done' || $signStatus == 'signed') {
							//Signé ou scellé ?
							if ($child->api_name == "uptosign") {
								$child->status = UptoSign::STATUS_SIGNED;
								$object->uptosignMessage = $langs->trans("DocumentSigned");
							} else {
								$child->status = UptoSign::STATUS_SEALED;
								$object->uptosignMessage = $langs->trans("DocumentSealed");
							}
						} elseif ($signStatus == 'cancelled') {
							$child->status = UptoSign::STATUS_CANCELED;
							$object->uptosignMessage = $langs->trans("UptoSignCanceled");
						} elseif ($signStatus == 'expired') {
							$child->status = UptoSign::STATUS_EXPIRED;
						} elseif ($signStatus == 'refused') {
							$child->status = UptoSign::STATUS_REFUSED;
							$object->uptosignMessage = $langs->trans("UptoSignRefused");
						} else {
							//error_on_sign_server | sms_error
							$child->status = UptoSign::STATUS_ERROR;
						}

						//pour la suite
						$status = $child->status;

						$dateCreate = new DateTime($resultContent['createdAt']);
						$child->date_creation = $dateCreate->getTimestamp();
						$dateCreate = null;

						//date de signature = date de dernière modification du document sur le serveur
						if (isset($resultContent['updatedAt'])) {
							$dateSign = new DateTime($resultContent['updatedAt']);
							if (!empty($dateSign)) {
								$child->date_sign = $dateSign->getTimestamp();
								$dateSign = null;
							}
						}

						$child->fk_user_modif = $user->id;
						$res = $child->update($user);
						if ($res < 0) {
							return $res;
						}

						$object->signOrSeal = $child->api_name;
					}
				}
			} else {
				dol_syslog("uptosign::signInfo do not refresh data from server due to document status");
			}
			dol_syslog("uptosign::signInfo step 20");

			if (isset($object->uptosignMessage) && $object->uptosignMessage != '') {
				$child->createEvent($object);
			}

			if ($objectType == 'propal' && $status == UptoSign::STATUS_SIGNED) {
				if (! $child->fk_object > 0 && $child->id > 0) {
					$child->fetch($child->id);
				}
				$propal = new Propal($child->db);
				$res = $propal->fetch($child->fk_object);
				if ($res > 0 && $propal->status == Propal::STATUS_VALIDATED) {
					// surtout ne pas re-générer le PDF !
					$conf->global->MAIN_DISABLE_PDF_AUTOUPDATE = 1;
					if (((int) DOL_VERSION) < 14) {
						$propal->cloture($user, Propal::STATUS_SIGNED, $langs->trans('SignedByUptoSign'), 1);
					} else {
						$propal->closeProposal($user, Propal::STATUS_SIGNED, $langs->trans('SignedByUptoSign'), 1);
					}
				}
				if ($res < 0) {
					$child->errors = $propal->errors;
					return $res;
				}
			}
		}
		dol_syslog("uptosign::signInfo apres update, return status=$status");
		return $status;
	}


	/**
	 * cancel Uptosign signature
	 *
	 * @param object    $user               user who cancels signing
	 *
	 * @return int 0 = OK, < 0 if NOK
	 */
	public function signCancel($user)
	{
		dol_syslog("uptosign : signCancel");
		return $this->delete($user);
	}


	/**
	 * fetch signed document from Uptosign service
	 *
	 * @param User    		$user               user who gets document
	 * @param CommonObject  $object             dolibarr object
	 * @param string    	$signOrSeal         uptosign|uptoseal
	 *
	 * @return int 0 = OK, < 0 if NOK
	 */
	public function signFetch($user, $object, $signOrSeal = '')
	{
		global $conf, $langs;
		$langs->loadLangs(array("uptosign@uptosign", "main", "other", "companies", "errors"));
		dol_syslog("uptosign signFetch signOrSeal = $signOrSeal");
		// print json_encode($object);exit;
		// print "signOrSeal = $signOrSeal";		exit;
		// print '<pre>';
		// print_r($this);
		// print '</pre>';
		// exit;

		$result = 0;
		$error = 0;
		$message = "";

		// si on est déjà sur un objet uptosign on reste dessus
		if ($object->element == "uptosign") {
			$children = [$object];
		} else {
			//question pourquoi ? on est déjà sur l'objet à fetcher puisqu'on arrive par la signature sur le hook
			//scorie de l'ancien module a reflechir
			$children = $this->fetchChilds($object->id, $object->element, $signOrSeal);
			if (! (is_array($children) && count($children) > 0)) {
				return -1;
			}
		}

		dol_syslog("uptosign signFetch, num children : " . count($children));
		foreach ($children as $child) {
			dol_syslog("uptosign signFetch child #" . $child->id); //  . " :: json=" . json_encode($child));

			$suffixForMassSignProcess = "";
			//add one file signed by X diff process -- must add a suffix
			if (strpos($child->label, 'UPTOSIGNLIST') !== false) {
				$suffixForMassSignProcess = $child->object_type . "-" . $child->fk_object;
			}

			//dans quels cas ne faut-il pas télécharger le fichier ?
			//todo ajouter ,UptoSign::STATUS_EXPIRED
			if (in_array($child->status, [UptoSign::STATUS_DRAFT, UptoSign::STATUS_REFUSED, UptoSign::STATUS_CANCELED, UptoSign::STATUS_EXPIRED])) {
				dol_syslog("uptosign signFetch status is " . $child->status . ", continue");
				$result = 1;
				continue;
			}

			if (empty($child->sign_id)) {
				dol_syslog("uptosign sign_id is empty, continue");
				continue;
			}

			//do not re-download file if already here
			if (!empty($child->hash_file_signed) && !empty($child->path_file_signed)) {
				$fullFileName = uptosign_full_path($child->path_file_signed);
				if (file_exists($fullFileName)) {
					dol_syslog("uptosign signed file name already downloaded ($fullFileName)");
					$result = 1;
					continue;
				} else {
					dol_syslog("uptosign signed file name is missing ($fullFileName)");
				}
			}

			$response = $this->apiClient->downloadDocument($child->sign_id);
			dol_syslog("uptosign signFetch :: /api/documents/" . $child->sign_id . "/download");

			if ($response['http_code'] == 0) {
				dol_syslog("uptosign result from server is not an array");
				$error = -1;
				continue;
			}

			if ($response['http_code'] == 404) {
				array_push($this->errors, $langs->trans('WaitingUptoSign'));
				$error = -1;
				continue;
			}

			if ($response['http_code'] != 200) {
				array_push($this->errors, 'UptoSignApiError 6b : ' . $response['http_code']);
				if (!empty($response['data']['message'])) {
					array_push($this->errors, $response['data']['message']);
				}
				$error = -1;
				continue;
			}

			$result = $response;

			$ts = $child->date_sign;
			//todo check
			//filename if signed already exists in priority, else original one (path_file)
			$filename = (trim($child->path_file_signed) != '') ? $child->path_file_signed :  $child->path_file;

			$suffix = utsbackports_getDolGlobalString('UPTOSIGN_FILENAME_SUFFIX_UPTOSEAL', '');
			if ($signOrSeal == "uptosign") {
				$suffix = utsbackports_getDolGlobalString('UPTOSIGN_FILENAME_SUFFIX_UPTOSIGN', '');
			}
			$fullSignFile = uptosign_rename_file_dolibarr_guidelines(uptosign_full_path($filename), $suffixForMassSignProcess . $suffix);

			// print "<p>Filename = $fullSignFile</p>";// exit;
			dol_syslog("uptosign signFetch download file $fullSignFile...");

			$resultContent = $result['content'];
			if (strlen($resultContent) < 1024) {
				dol_syslog("download resultContent is less than 1Ko octets ... error");
				$child->status = UptoSign::STATUS_EXPIRED;
				$child->fk_user_modif = $user->id;
				$res = $child->update($user);
				if ($res < 0) {
					dol_syslog("uptosign signFetch file status update to expired error");
				}
				continue;
			}

			dol_syslog("uptosign sizeof file = " . strlen($resultContent));
			if ($fp = fopen($fullSignFile, 'w')) {
				fwrite($fp, $resultContent);
				fclose($fp);
			} else {
				dol_syslog("uptosign signFetch file write error");
				array_push($this->errors, $langs->trans("UptoSignCreateFileError") . " : " . $fullSignFile);
				continue;
			}

			$child->hash_file_signed = hash_file('sha256', $fullSignFile);
			$child->path_file_signed = uptosign_relative_path($fullSignFile);
			dol_syslog("uptosign signFetch file write ok");

			$object->uptosignMessage = $langs->trans('UptoSignFileDownloaded');
			$message = $langs->trans('UptoSignFileDownloaded');
			$child->status = UptoSign::STATUS_FILE_FETCHED;
			$child->fk_user_modif = $user->id;
			$res = $child->update($user);
			if ($res < 0) {
				dol_syslog("uptosign signFetch error on update", LOG_ERR);
				array_push($this->errors, "Error while updating object");
				$error = $res;
			}

			// for the moment cas
			$objectType = $child->object_type;
			dol_syslog("uptosign signFetch object type is " . $objectType);
			if ($objectType == 'invoice' || $objectType == 'facture') {
				//$result = $object->call_trigger('INVOICE_SEALED', $user);
				if (method_exists($object, 'call_trigger')) {
					$result = $object->call_trigger('INVOICE_SEALED', $user);
					if ($result < 0) {
						dol_syslog("uptosign signFetch error on call_trigger", LOG_ERR);
						++$error;
					}
				}
			}
		}

		if (!empty($message)) {
			dol_syslog("uptosign signFetch message to put on event is $message");
			$object->signOrSeal = $signOrSeal;
			//todo check
			$this->createEvent($object, $message);
		}

		//try to download proof even if there is errors
		dol_syslog("uptosign what about proof file ? signOrSeal=$signOrSeal");
		if (in_array($signOrSeal, ["sign", "uptosign"])) {
			dol_syslog("uptosign signFetch call signFetchProof...");
			$res = $this->signFetchProof($user, $object);
		}

		if ($error) {
			$result = $error;
		}
		dol_syslog("uptosign signFetch return " . json_encode($result));

		return $result;
	}


	/**
	 * fetch proof signed document from Uptosign service
	 *
	 * @param object    $user               user who gets document
	 * @param object    $object             dolibarr object
	 *
	 * @return int 0 = OK, < 0 if NOK
	 */
	public function signFetchProof($user, $object)
	{
		dol_syslog("uptosign signFetchProof");
		// print '<pre>';
		// print_r($this);
		// print '</pre>';
		// exit;
		global $conf, $langs;
		$result = 0;
		$error = 0;

		$children = $this->fetchChilds($object->id, $object->element, 'uptosign');
		if (! (is_array($children) && count($children) > 0)) {
			return -1;
		}

		dol_syslog("uptosign signFetchProof, children "); // . json_encode($children));
		foreach ($children as $child) {
			dol_syslog("uptosign signFetchProof child"); // . json_encode($child));
			if (!in_array($child->status, [UptoSign::STATUS_SIGNED, UptoSign::STATUS_SEALED, UptoSign::STATUS_FILE_FETCHED])) {
				dol_syslog("uptosign signFetchProof status is not signed,sealed or fetched, return");
				return -1;
			}

			$res = $this->fetch($child->id);
			if ($res < 0) {
				dol_syslog("uptosign signFetchProof res < 0");
				return $res;
			}
			if (empty($child->sign_id)) {
				dol_syslog("uptosign sign_id is empty");
				return -1;
			}

			if (in_array($child->status, [UptoSign::STATUS_DRAFT, UptoSign::STATUS_REFUSED, UptoSign::STATUS_CANCELED, UptoSign::STATUS_EXPIRED])) {
				dol_syslog("uptosign signFetchProof status is " . $child->status . ", continue");
				continue;
			}

			//do not re-download file if already here
			if (!empty($child->hash_file_signed) && !empty($child->path_file_signed)) {
				$fullFileName = str_replace($conf->global->UPTOSIGN_FILENAME_SUFFIX_UPTOSIGN, $conf->global->UPTOSIGN_FILENAME_SUFFIX_PROOF, uptosign_full_path($child->path_file_signed));
				if (file_exists($fullFileName)) {
					dol_syslog("uptosign signFetchProof file name already downloaded ($fullFileName)");
					continue;
				} else {
					dol_syslog("uptosign signFetchProof file name is missing ($fullFileName)");
				}
			} else {
				dol_syslog("uptosign signFetchProof child->hash_file_signed or child->path_file_signed is empty");
			}

			$response = $this->apiClient->downloadProof($child->sign_id);
			dol_syslog("uptosign signFetchProof :: /api/documents/" . $child->sign_id . "/downloadProof");

			$hashProof = $proofFile = "";
			if ($response['http_code'] == 0) {
				dol_syslog("uptosign result from server is not an array");
				return -1;
			}

			$resultContent = $response['data'];
			dol_syslog("signFetchProof 6b");
			if ($response['http_code'] != 200) {
				dol_syslog("signFetchProof 6c");
				array_push($this->errors, 'UptoSignApiError 6b : ' . $response['http_code']);
				array_push($this->errors, $resultContent['message'] ?? '');
				return -1;
			}

			$ts = $child->date_sign;
			$suffix = utsbackports_getDolGlobalString('UPTOSIGN_FILENAME_SUFFIX_PROOF', '');
			$signfile = uptosign_rename_file_dolibarr_guidelines($this->path_file, $suffix, $ts);
			$fullSignFile = uptosign_full_path($signfile);

			dol_syslog("uptosign signFetchProof download file $fullSignFile...");

			//in case of mutiple proof files with same base name
			if (file_exists($fullSignFile)) {
				$fullSignFile = str_replace($suffix, "-" . rand(1000, 9999) . $suffix, $fullSignFile);
			}

			if ($fp = fopen($fullSignFile, 'w')) {
				fwrite($fp, $response['content']);
				fclose($fp);
			} else {
				array_push($this->errors, $langs->trans("UptoSignCreateFileError") . " : " . $proofFile);
				return -1;
			}

			$hashProof = hash_file('sha256', $fullSignFile);

			dol_syslog("signFetchProof 7");
			$object->uptosignMessage = $langs->trans('UptoSignProofFileDownloaded');
			$this->createEvent($object);

			$this->status = UptoSign::STATUS_FILE_FETCHED;
			$this->fk_user_modif = $user->id;
			$res = $this->update($user);
			if ($res < 0) {
				array_push($this->errors, $langs->trans("UptoSignCreateFileError") . " : " . $proofFile);
				return -1;
			}

			dol_syslog("signFetchProof 8");
			//Creation d'un objet supplémentaire UptoSign pour avoir l'historique et la possibilité
			//de savoir que le fichier proof est scellé - uniquement si l'objet en cours n'est pas
			//déjà un "proof" : un proof file n'a pas de hash_file car il n'a pas été créé dans dolibarr
			if (null === $this->hash_file) {
				dol_syslog("signFetchProof hash_file is null");
				return -1;
			}
			//Evite le F5 sur le download du fichie de preuves
			$resDup = $this->fetchByObject((int) $this->fk_object, uptosign_unify_object_type($this->object_type), array('hash_file_null' => true));
			if (count($resDup) != 0) {
				dol_syslog("signFetchProof uProof object already downloaded");
				return -1;
			}

			$uProof = new UptoSign($this->db);
			$uProof->ref = $uProof->getNextNumRef();
			$uProof->hash_file = '';
			$uProof->sign_id = $resultContent['id'];
			$uProof->hash_file_signed = $hashProof;
			$uProof->path_file_signed = uptosign_relative_path($fullSignFile);
			$uProof->label = $langs->trans("UptoSignDocumentProof");
			$uProof->description = $langs->trans('UptoSignDocumentProof') . " (" . $langs->trans('UptoSignProof') . ")";
			$dateCreate = new DateTime();
			$uProof->date_creation = $dateCreate->getTimestamp();
			$uProof->object_type = uptosign_unify_object_type($this->element);
			$uProof->api_name = 'uptoseal';
			$uProof->status = UptoSign::STATUS_FILE_FETCHED;
			$copyProp = ['fk_soc', 'fk_object', 'object_type', 'sign_status', 'fk_user_sign'];
			foreach ($copyProp as $prop) {
				$uProof->$prop = $this->$prop;
			}

			$res = $uProof->create($user);
			if ($res < 0) {
				dol_syslog("signFetchProof error saving uProof object : " . json_encode($uProof));
			} else {
				dol_syslog("signFetchProof uProof object saved");
			}
		}
		if (!$error) {
			return $result;
		}
		return $error;
	}

	/**
	 * get list of contacts for an object
	 *
	 * @param   CommonObject  $object
	 *
	 * @return  aray  [return description]
	 */
	public function getListContacts($object)
	{
		$listMembers = [];
		$indexMember = 0;

		$config = new UptoSignConfig($object->db);
		$model_pdf = uptosignModel($object);
		$configIds = $config->fetchListId($model_pdf, $object->element, 'sign');
		if (is_numeric($configIds) && $configIds == -2) {
			array_push($this->errors, "UptoSignConfigMissing");
			array_push($this->errors, "UptoSignConfigMissingHelp");
			return -1;
		}
		$typeContacts = $config->getTypeContactCode($object->element, 'all');
		if (is_object($object) && is_array($configIds)) {
			foreach ($configIds as $configFile => $configId) {
				$res = $config->fetch($configId);
				if ($res < 0) {
					array_push($this->errors, $config->errors);
					return -3;
				}
				if ($res == 0) {
					array_push($this->errors, "UptoSignContactMissing");
					array_push($this->errors, "UptoSignContactMissingHelp");
					return -4;
				}

				if (!empty($config->page_sign)) {	// test si la signature n'est pas désactivée pour ce type de document
					$contactCode = $typeContacts[$config->fk_c_type_contact];
					$contactIds = $object->getIdContact('external', $contactCode['code']);
					$userIds = $object->getIdContact('internal', $contactCode['code']);

					//Les contacts
					if (count($contactIds) > 0) {
						foreach ($contactIds as $key => $contactId) {
							$contact = new Contact($object->db);
							if ($result = $contact->fetch($contactId) > 0) {
								if (empty($contact->email)) {
									array_push($this->errors, "UptoSignContactEmailMissing");
									return -5;
								}
								$mobile = uptoSignSearchMobile($contact->phone_mobile, $contact->phone_pro, $contact->country_code);

								if (empty($mobile)) {
									array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
									return -6;
								}
								$listMembers[] = array(
									'dolid' => $contact->id,
									'doltype' => 'contact',
									'firstname' => $contact->firstname,
									'lastname' => $contact->lastname,
									'societe' => $contact->socname,
									'email' => $contact->email,
									'mobile' => $mobile,
								);
								$indexMember++;
							}
						}
					}

					//Les utilisateurs dolibarr
					if (count($userIds) > 0) {
						foreach ($userIds as $key => $userId) {
							$oneuser = new User($object->db);
							if ($oneuser->fetch($userId) > 0) {
								if (empty($oneuser->email)) {
									array_push($this->errors, "UptoSignUserEmailMissing");
									return -7;
								}
								// if ($authMode == 'sms') {
								$mobile = uptoSignSearchMobile($oneuser->user_mobile, $oneuser->office_phone, $oneuser->country_code);

								if (empty($mobile)) {
									array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
									return -8;
								}
								$listMembers[] = array(
									'dolid' => $oneuser->id,
									'doltype' => 'user',
									'firstname' => $oneuser->firstname,
									'lastname' => $oneuser->lastname,
									'email' => $oneuser->email,
									'mobile' => $mobile,
								);
								$indexMember++;
							}
						}
					}
				} else {
					$listMembers['noSign']++;
				}
			}
		}
		// var_dump($listMembers);exit;
		return $listMembers;
	}


	/**
	 * return true if file is the same
	 *
	 * @return  boolean                 [return description]
	 */
	public function checkFile()
	{
		global $langs, $user;
		$error = 0;
		//Verification que ce qu'on retourne correspond à une réalité (fichier scellé bien scellé etc.)
		if (empty($this->path_file_signed)) {
			array_push($this->errors, $langs->trans("UptoSignNoSignEntry"));
			--$error;
			$this->status = UptoSign::STATUS_WAITING;
			$this->sign_status = 'active';
			//expired
		}

		//check if file on disk is ok
		$full = uptosign_full_path($this->path_file_signed);
		if (!file_exists($full) || !is_file($full)) {
			array_push($this->errors, $langs->trans("UptoSignFileNotFound"));
			--$error;
			$this->status = UptoSign::STATUS_WAITING;
			$this->sign_status = 'active';
		}

		if (!$error && hash_file('sha256', $full) == $this->hash_file_signed) {
			return true;
		} else {
			if (!$error) {
				array_push($this->errors, "UptoSignChecksumError");
			}
			$this->update($user);
			return false;
		}
	}


	/**
	 * dolibarr 10 function setVarsFromFetchObj is protected !
	 *
	 * @param   CommonObject  $obj  [$obj description]
	 *
	 * @return  void         [return description]
	 */
	public function uts_setVarsFromFetchObj(&$obj)
	{
		$this->setVarsFromFetchObj($obj);
	}


	/**
	 * [displayHistory description]
	 *
	 * @return  string  html code
	 */
	public function displayHistory()
	{
		global $langs;

		$history = json_decode($this->sign_history);
		if (empty($history)) {
			$html = "<div class='uptosignHistory'>\n";
			$html .= "<p>" . $langs->trans("uptosignTitleNoHistory") . "</p>\n";
			$html .= "</div>\n";
			return $html;
		}


		$html = "";
		$html .= "<div class='uptosignHistory'>\n";
		$html .= "<p>" . $langs->trans("uptosignTitleHistory") . "</p>\n";
		$html .= "<ol id='uptosignHistory'>\n";

		foreach ($history as $people => $actions) {
			$qrdata = uptosignQRCode($actions->url);

			$html .= "<li><h3>" . $langs->trans("uptosignTitleHistorySignPeople", $people) . "</h3>\n";
			$html .= "<ul>\n";
			$html .= "<li>" . $langs->transnoentitiesnoconv("uptosignLinkToSignPeople", "<a href='" . $actions->url . "'>", "</a>");
			$html .= "<img style='float: right;' src='data:image/png;base64," . base64_encode($qrdata) . "' /> <br />";
			$html .= "</li>\n";

			foreach ($actions as $action) {
				$html .= "<li>\n";
				$html .= "<span>" . $action->created_at . "</span>\n";
				$html .= "<div class='content'>\n";
				$html .= "<h4>" . $action->step . "</h4>\n";
				$html .= "<p>\n";
				$html .= $action->description . "\n";
				$html .= "</p>\n";
				$html .= "</div>\n";
				$html .= "</li>\n";
			}
			$html .= "</ul>\n";
			$html .= "</li>\n";
		}

		$html .= "</ol>\n";
		$html .= "</div>\n";
		return $html;
	}

	/**
	 * whoCanSign - delegates to UptoSignSignatoryResolver::resolveSigners()
	 *
	 * @param   User|Societe|Contact  $object            Object to sign
	 * @param   string  $internalExternal  'external' or 'internal'
	 * @param   string  $configLabel        "CustomerSign" or "VendorSign" or "UserSign"
	 * @param   ArrayObject  $storeArray	Result array (modified by reference)
	 * @return  void|int  -1 on error
	 */
	public function whoCanSign($object, $internalExternal, $configLabel, ArrayObject &$storeArray = null)
	{
		$result = $this->signatoryResolver->resolveSigners($object, $internalExternal, $configLabel, $storeArray);
		$this->errors = array_merge($this->errors, $this->signatoryResolver->errors);
		$this->signatoryResolver->errors = array();
		return $result;
	}

	/**
	 * anonyze fields tel and email to be clear with GDPR
	 *
	 * @param   string  $str   [$str description]
	 * @param  string  $type  [$type description]
	 *
	 * @return  string         [return description]
	 */
	public function anonyzeField($str, $type)
	{
		if ($type == 'mail') {
			$s = explode('@', $str);
			$str = substr($s[0], 0, strlen($s[0]) / 2);
			for ($i = 0; $i < strlen($s[0]) / 2; $i++) {
				$str .= "*";
			}
			$str .= "@";
			for ($i = 0; $i < strlen($s[1]) / 2; $i++) {
				$str .= "*";
			}
			$str .= substr($s[1], strlen($s[1]) / 2);
		}
		if ($type == 'tel') {
			$s = substr($str, 0, 3);
			for ($i = 0; $i < strlen($str) / 3; $i++) {
				$s .= "*";
			}
			$s .= substr($str, 3 + strlen($str) / 3);
			$str = $s;
		}

		return $str;
	}

	/**
	 * make concat of name / firstname / mail / tel
	 *
	 * @param   Contact  $contact  [$contact description]
	 *
	 * @return  string            [return description]
	 */
	public function getShortContactNameMailTel($contact)
	{
		$str = $contact->firstname . " " . substr($contact->lastname, 0, 1);

		if (utsbackports_getDolGlobalString('UPTOSIGN_ADD_CONTACT_POSTE_FUNCTION') != '') {
			if (isset($contact->poste)) {
				$str .= ", " . $contact->poste;
			}
		}
		$str .= " &lt;" . $this->anonyzeField($contact->email, 'mail') . "&gt; tel:" . $this->anonyzeField($contact->phone_mobile, 'tel');

		return $str;
	}

	/**
	 * Assign all signing roles to a contact - delegates to UptoSignSignatoryResolver
	 *
	 * @param   CommonObject  $object  Contact object with thirdparty loaded
	 * @return  int                    Result of updateRoles(), or -1 if not customer/supplier
	 */
	public function giveAllRolesToContact($object)
	{
		return $this->signatoryResolver->giveAllRolesToContact($object);
	}

	/**
	 * fetch user if not set
	 *
	 * @param   User  $user    [$user description]
	 * @param   CommonObject  $object  [$object description]
	 *
	 * @return  User|int           [return description]
	 */
	public function findUserToUse($user, $object, $returnid = false)
	{
		if (!is_object($user) || empty($user->login) || $user->element != 'user') {
			dol_syslog("uptosign: findUserToUse create new user");
			$user = new User($this->db);
			if (is_object($object)) {
				$keys = ['user_valid_id', 'fk_user_valid', 'user_validation_id', 'user_author_id', 'user_creation', 'user_creation_id'];
				foreach ($keys as $key) {
					if (isset($object->$key)) {
						$res = $user->fetch($object->$key);
						if ($res > 0) {
							dol_syslog("uptosign: findUserToUse found for key=$key, res=$res, userid=" . $user->id);
							break 1;
						}
					}
				}
			}
		}
		if (empty($user->login)) {
			$user = new User($this->db);
			dol_syslog("uptosign: findUserToUse get default UPTOSIGN_DEFAULT_USER");
			$res = $user->fetch(utsbackports_getDolGlobalString('UPTOSIGN_DEFAULT_USER'));
			if ($res <= 0) {
				dol_syslog("uptosign: findUserToUse error fetching default UPTOSIGN_DEFAULT_USER user", LOG_ERR);
			}
		}
		if ($returnid) {
			return $user->id;
		}
		return $user;
	}

	private function _searchDisableSMS($socid)
	{
		$soc = new Societe($this->db);
		$res = $soc->fetch($socid);
		$ret = 0;
		if ($res) {
			$soc->fetch_optionals();
			if (isset($soc->array_options) && isset($soc->array_options['options_digitalsign_disable_sms'])) {
				$ret = (int) $soc->array_options['options_digitalsign_disable_sms'];
			}
		}
		//if not specific value for thirdpart, apply default global one
		if ($ret == 0) {
			$this->disableSms = (int) utsbackports_getDolGlobalString('UPTOSIGN_DISABLE_SMS_GLOBAL_SELECT', '0');
		}
		return (int) $ret;
	}
}

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';

/**
 * Class UptoSignLine. You can also remove this and generate a CRUD class for lines objects.
 */
class UptoSignLine extends CommonObjectLine
{
	// To complete with content of an object UptoSignLine
	// We should have a field rowid, fk_uptosign and position

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}
