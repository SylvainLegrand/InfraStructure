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
 * \file        class/uptosignconfig.class.php
 * \ingroup     uptosign
 * \brief       This file is a CRUD class file for UptoSignConfig (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/class/uptosignsignatoryresolver.class.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');
dol_include_once('/uptosign/lib/backports.lib.php');

/**
 * Class for UptoSignConfig
 */
class UptoSignConfig extends CommonObject
{
	public $labelStatusShort;
	public $labelStatus;
	public $output;
	public $user_validation;

	/**
	 * @var string ID of module.
	 */
	public $module = 'uptosign';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'uptosignconfig';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'uptosign_uptosignconfig';

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
	 * @var string String with name of icon for uptosignconfig. Must be the part after the 'object_' into object_uptosignconfig.png
	 */
	public $picto = 'uptosignconfig@uptosign';


	public const STATUS_DISABLED = -1;
	public const STATUS_DRAFT = 0;
	public const STATUS_VALIDATED = 1;
	public const STATUS_CANCELED = 9;


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *         'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
	 *         'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
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

	//note: pas de 'foreignkey'=>'document_model.rowid' sinon le module facture ne peut pas désactiver un modele qui serait referencé ici !
	//        'model_pdf' => array('type'=>'varchar(64)', 'label'=>'ModelePdf', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, ),
	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'visible' => 0, 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'index' => 1,),
		'model_pdf' => array('type'=>'sellist:document_model:nom,type,rowid:rowid::nom <> \'\' AND nom NOT LIKE \'%_odt\':nom', 'label'=>'ModelePdf', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, ),
		'label' => array('type'=>'varchar(255)', 'label'=>'uptosignAction', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'searchall'=>1,'arrayofkeyval'=>array('VendorSign'=>'UptoSignVendorSign', 'CustomerSign'=>'UptoSignCustomerSign', 'DocumentSeal' => 'UptoSignDocumentSeal')),
		'sign_or_seal' => array('type'=>'varchar(8)', 'label'=>'SignOrSeal', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'help'=>"SignOrSealTooltip",'arrayofkeyval'=>array('sign'=>'UptoSignChoosesign', 'seal'=>'UptoSignChooseseal')),
		'seal_coordinate' => array('type'=>'varchar(64)', 'label'=>'SealCoordinate', 'enabled'=>'1', 'position'=>80, 'notnull'=>1, 'visible'=>1, 'help'=>"SealCoordinateToolTip",),
		'page_seal' => array('type'=>'integer', 'label'=>'SealPage', 'enabled'=>'1', 'position'=>90, 'notnull'=>1, 'visible'=>1, 'default'=>'1', 'help'=>"SealPageTooltip",),
		'sign_coordinate' => array('type'=>'varchar(64)', 'label'=>'SignCoordinate', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>1, 'help'=>"SignCoordinateTooltip",),
		'page_sign' => array('type'=>'integer', 'label'=>'SignPage', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>1, 'help'=>"SignPageTooltip",),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2, 'default'=>'NOW()',),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>1, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>1100, 'notnull'=>1, 'visible'=>1, 'default'=>'1', 'index'=>1, 'arrayofkeyval'=>array('0'=>'D&eacute;sactiver', '1'=>'Activer'),),
	);
	public $rowid;
	public $entity;
	public $label;
	public $sign_or_seal; //'seal' or 'sign' pour savoir à quoi s'applique cette configuration
	public $sign_coordinate; //coordonnées x;y de la signature
	public $page_sign; //page où placer la signature
	public $seal_coordinate; //coordonnées x;y où placer le scean
	public $page_seal; //numero de la page sur laquelle poser le sceau
	public $model_pdf; //Nom combiné du type de modele + nom du modele de document pdf auxquel cette configuration s'applique ex. propal:azur
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	// END MODULEBUILDER PROPERTIES

	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'uptosign_uptosignconfigline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_uptosignconfig';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'UptoSignConfigline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('uptosign_uptosignconfigdet');

	// /**
	//  * @var UptoSignConfigLine[]     Array of subtable lines
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

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 1;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		$langs->load("orders");
		$langs->load("contracts");
		$langs->load("projects");
		$langs->load("propal");
		$langs->load("bills");
		$langs->load("interventions");
		$langs->load("shippings");
		$this->fields['seal_coordinate']['visible'] = 1;

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->uptosign->uptosignconfig->read) {
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
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
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
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		return $result;
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
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $withdisabled = 0)
	{
		global $conf;
		dol_syslog("UptoSignConfig::fetchAll sortorder=$sortorder, sortfield=$sortfield, limit=$limit, offset=$offset, filtermode=$filtermode, withdisabled=$withdisabled, filter=" . json_encode($filter), LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		if (((int) DOL_VERSION) < 14) {
			$sql .= utsbackports_getFieldList($this);
		} else {
			/** @phpstan-ignore-next-line */
			$sql .= $this->getFieldList('t');
		}

		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		if ($withdisabled == 0) {
			$sql .= " AND status != '" . $this::STATUS_DISABLED . "'";
		}

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key." = ".((int) $value);
				} elseif (array_key_exists($key, $this->fields) && in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
				} elseif (strpos($value, '%') !== false) {
					$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
				} elseif (is_numeric($value)) {
					$sqlwhere[] = $key." = ".((int) $value);
				} else {
					$sqlwhere[] = $key." = '".$this->db->escape($value)."'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

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
			array_push($this->errors, 'Error '.$this->db->lasterror());
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load list of object id's in memory from the database
	 *
	 * @param   string          $modelpdf model pdf, ex strato or azur
	 * @param   string          $type type of document model, ex contrat or propal
	 * @param   string          $signOrSeal sign|seal
	 * @return  int|array       <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchListId($modelpdf = '', $type = '', $signOrSeal = '')
	{
		global $langs;

		$idList = array();
		// print "<p>Recherche de $modelpdf :: $type</p>";
		//Decoupage eventuel si modelpdf=azur:propal
		if (strpos($modelpdf, ":")) {
			$modelpdf = uptosign_unify_object_name($modelpdf);
		}

		$sql = "SELECT rowid, model_pdf";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE status = 1";
		if (isset($modelpdf)) {
			if ($type != '') {
				$type = uptosign_unify_object_type($type);
				$sql .= " AND model_pdf = '" . $this->db->escape($type . ':' . $modelpdf) . "'";
			} else {
				$sql .= " AND model_pdf = '" . $this->db->escape($modelpdf) . "'";
			}
		}
		if ($signOrSeal != '') {
			$sql .= " AND sign_or_seal = '" . $this->db->escape($signOrSeal) . "'";
		}
		$sql .= " AND status != '" . $this::STATUS_DISABLED . "'";

		// print "<p>$sql</p>";
		//exit;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$i = 0;
				// $this->dataset = null;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$idList[$i] = $obj->rowid;
					$i++;
				}
				$this->db->free($resql);
				if (!empty($idList)) {
					return $idList;
				} else {
					$this->db->free($resql);
					return -2;
				}
			} else {
				array_push($this->errors, $langs->transnoentitiesnoconv("UptoSignErrorThereIsNoConfig", $modelpdf . ' (' . $type . ')', "<a href='" . dol_buildpath("/uptosign/uptosignconfig_list.php", 1) . "'>", "</a>"));
				return -1;
			}
		} else {
			array_push($this->errors, "Error " . $this->db->lasterror());
			dol_syslog(get_class($this) . "::fetchListId " .join(',', $this->errors), LOG_ERR);
			return -1;
		}
		return 0;
	}

	/**
	 * validate
	 * @return bool
	 */
	public function validate(User $user)
	{
		$result = false;
		// if (preg_match('/^[0-9]*,[0-9]*/', $this->sign_coordinate)) {
		//     $result = true;
		// } else {
		//     array_push($this->errors, "UptoSignInvalidCoordinates");
		//     $result = false;
		// }

		if (preg_match('/^[0-9]*,[0-9]*/', $this->seal_coordinate)) {
			if (((int) DOL_VERSION) < 11) {
				dol_syslog("uptosign, setStatusCommon is available on dolibarr > 10.0, let use old setStatut...", LOG_WARNING);
				/** @phpstan-ignore-next-line */
				$result = $this->setStatut(self::STATUS_VALIDATED, $this->id, $this->element);
			} else {
				/** @phpstan-ignore-next-line */
				$result = $this->setStatusCommon($user, self::STATUS_VALIDATED);
			}
		} else {
			array_push($this->errors, "UptoSignInvalidSealCoordinates");
			$result = false;
		}
		return $result;
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
		// Set import_key to null so it won't be saved (for backup/restore module process)
		// Note: using null instead of unset() for PHP 8.x compatibility
		$this->import_key = null;

		/** @phpstan-ignore-next-line */
		return $this->updateCommon($user, $notrigger ? 1 : 0);
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
		/** @phpstan-ignore-next-line */
		return $this->deleteCommon($user, $notrigger ? 1 : 0);
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
		/** @phpstan-ignore-next-line */
		return $this->deleteLineCommon($user, $idline, $notrigger ? 1 : 0);
	}


	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		dol_syslog("uptosign, setDraft called, current status=".$this->status);

		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->uptosign->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->uptosign->uptosign_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		if (((int) DOL_VERSION) < 11) {
			dol_syslog("uptosign, setStatusCommon is available on dolibarr > 10.0, let use old setStatut...", LOG_WARNING);
			/** @phpstan-ignore-next-line */
			return $this->setStatut(self::STATUS_DRAFT, $this->id, $this->element);
		} else {
			/** @phpstan-ignore-next-line */
			return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'UPTOSIGNCONFIG_UNVALIDATE');
		}
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->uptosign->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->uptosign->uptosign_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/
		if (((int) DOL_VERSION) < 11) {
			dol_syslog("uptosign, setStatusCommon is available on dolibarr > 10.0, let use old setStatut...", LOG_WARNING);
			/** @phpstan-ignore-next-line */
			return $this->setStatut(self::STATUS_CANCELED, $this->id, $this->element);
		} else {
			/** @phpstan-ignore-next-line */
			return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'UPTOSIGNCONFIG_CANCEL');
		}
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->uptosign->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->uptosign->uptosign_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/
		if (((int) DOL_VERSION) < 11) {
			dol_syslog("uptosign, setStatusCommon is available on dolibarr > 10.0, let use old setStatut...", LOG_WARNING);
			/** @phpstan-ignore-next-line */
			return $this->setStatut(self::STATUS_VALIDATED, $this->id, $this->element);
		} else {
			/** @phpstan-ignore-next-line */
			return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'UPTOSIGNCONFIG_REOPEN');
		}
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

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("UptoSignConfig").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/uptosign/uptosignconfig_card.php', 1).'?id='.$this->id;

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
			if (utsbackports_getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER', '')  != '') {
				$label = $langs->trans("ShowUptoSignConfig");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files", 0, '\.pdf$', ['(\.meta|_preview.*\.png)$']);

				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('uptosignconfigdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
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
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatus[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');

			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

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
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

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

		$objectline = new UptoSignConfigLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('t.fk_uptosignconfig' => (int) $this->id));

		if (is_numeric($result)) {
			$this->error = $objectline->error;
			array_push($this->errors, $objectline->errors);
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
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

		$result = 0;
		$includedocgeneration = 0;

		$langs->load("uptosign@uptosign");

		if (!dol_strlen($modele)) {
			$modele = 'standard_uptosignconfig';

			if (!empty($this->model_pdf)) {
				$tab = explode(':', $this->model_pdf);
				$modele = $tab[0];
			} elseif (utsbackports_getDolGlobalString('UPTOSIGNCONFIG_ADDON_PDF', '')  != '') {
				$modele = utsbackports_getDolGlobalString('UPTOSIGNCONFIG_ADDON_PDF', '');
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
		global $conf, $langs;

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
	 *  Return array with list of possible values for type of contacts
	 *
	 *      @param	string	$element    element
	 *      @param	string	$source     'internal', 'external' or 'all'
	 *      @param	string	$order		Sort order by : 'position', 'code', 'rowid'...
	 *      @return array       		Array list of type of contacts (id->label if option=0, code->label if option=1)
	 */
	public function getTypeContactLabel($element, $source = 'external', $order = 'position')
	{
		global $langs;

		if (empty($order)) {
			$order = 'position';
		}
		if ($order == 'position') {
			$order .= ',code';
		}
		if ($element == 'expedition' || $element == 'shipping') {
			$element = 'commande';
		}
		$tab = array();
		$sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle, tc.position";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
		$sql .= " WHERE tc.element='" . $this->db->escape($element) . "'";
		$sql .= " AND tc.active=1"; // only the active types
		if (!empty($source) && $source != 'all') {
			$sql .= " AND tc.source='" . $this->db->escape($source) . "'";
		}
		$sql .= $this->db->order($order, 'ASC');

		//print "sql=".$sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$transkey = "TypeContact_" . $this->element . "_" . $source . "_" . $obj->code;
				$libelle_type = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->libelle);
				$tab[$obj->rowid] = $libelle_type;
				$i++;
			}
			return $tab;
		} else {
			array_push($this->errors, "Error " . $this->db->lasterror());
			dol_syslog(get_class($this) . "::getTypeContactLabel " .join(',', $this->errors), LOG_ERR);
			return null;
		}
	}

	/**
	 * Return array with list of possible values for type of contacts - delegates to UptoSignSignatoryResolver
	 *
	 * @param  string     $element Object element
	 * @param  string     $source  'internal', 'external' or 'all'
	 * @param  string     $order   Sort order by: 'position', 'code', 'rowid'...
	 * @param  array|null $filter  Associative array of additional filters
	 * @return array|null          Array of contact types or null on error
	 */
	public function getTypeContactCode($element, $source = 'external', $order = 'position', $filter = null)
	{
		$resolver = new UptoSignSignatoryResolver($this->db);
		return $resolver->getTypeContactCode($element, $source, $order, $filter);
	}

	/**
	 * Return array with list of possible contact sources for an element - delegates to UptoSignSignatoryResolver
	 *
	 * @param  string     $element Object element name
	 * @return array|null          Array of sources or null on error/empty
	 */
	public function getSourceContactCode($element)
	{
		$resolver = new UptoSignSignatoryResolver($this->db);
		return $resolver->getSourceContactCode($element);
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
			case 'project':
				require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
				$origin = new Project($this->db);
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
				require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
				$origin = new CompanyPaymentMode($this->db);
				break;
			case 'bankaccount':
			case 'bank':
				require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
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
			return '';
		}

		if ($origin->fetch($fk_origin) > 0) {
			return $origin->getNomUrl(1);
		}

		return '';
	}


	/**
	 * Surchage uniquement pour capturer les champs qu'on ne peut pas décrire
	 * dans la définition de la bdd
	 *
	 * @param  array   $val		       Array of properties of field to show
	 * @param  string  $key            Key of attribute
	 * @param  string  $object         list object with preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $showsize       Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputField($val, $key, $object, $moreparam = '', $keysuffix = '', $keyprefix = '', $showsize = 0)
	{
		global $langs;
		$value = "";
		if ($key == 'rowid' && method_exists($this, 'getNomUrl')) {
			$value = $this->getNomUrl(0, '', 1, '', 1);
		} elseif ($key == 'label') {
			$value = $langs->trans("UptoSign".$object);
		} elseif ($key == 'sign_or_seal') {
			$value = $langs->trans("Choose".$object);
		} elseif ($key == 'sign_coordinate' || $key == 'seal_coordinate') {
			if ($object != "") {
				$t = explode(',', $object);
				$value = "x=" . $t[0] . ", y=" . $t[1];
			} else {
				$value = "";
			}
		} elseif ($key == 'model_pdf') {
			//due to bug #10789
			$a = $this->getDocumentModelDetails($object);
			$value = uptosign_translate_object_type($a['type']) . " : " . $a['nom'];
		} elseif ($key == 'status' && method_exists($this, 'getLibStatut')) {
			$value = $this->getLibStatut(3);
		}
		if ($value != "") {
			return $value;
		}

		return parent::showOutputField($val, $key, $object, $moreparam, $keysuffix, $keyprefix, $showsize);
	}

	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param  array   		$val	       Array of properties for field to show (used only if ->fields not defined)
	 * @param  string  		$key           Key of attribute
	 * @param  string|array	$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param  string  		$moreparam     To add more parameters on html input tag
	 * @param  string  		$keysuffix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$morecss       Value for css to define style/length of field. May also be a numeric.
	 * @param  int			$nonewbutton   Force to not show the new button on field that are links to object
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form;
		$array = [];

		//due to bug, convert sellist to arrayofkeyval
		if ($key == "model_pdf") {
			if (preg_match('/^sellist:(.*):(.*):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
				//'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]'
				unset($val['arrayofkeyval']);
				$table  = $reg[1];
				$label  = $reg[2];
				$lakey  = $reg[3];
				$parent = $reg[4];
				$filter = $reg[5];
				$sortfield   = $reg[6];
				// print_r($reg);

				$sql = "SELECT $lakey,$label,type FROM ".MAIN_DB_PREFIX.$table." as t";
				if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
					$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
				} else {
					$sql .= " WHERE 1 = 1";
				}
				if ($filter != "") {
					$sql .= " AND " . $filter;
				}
				$sql .= " GROUP BY $label";
				if (!empty($sortfield)) {
					$sql .= $this->db->order($sortfield, 'ASC');
				}
				// dol_syslog("uptosign: SQL = $sql");
				// print $sql;

				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						$k = uptosign_unify_object_type($obj->type) . ':' . $obj->nom;
						$val['arrayofkeyval'][$k] = uptosign_translate_object_type($obj->type) . " : " . $obj->nom;
						$i++;
					}
					$this->db->free($resql);
					//transformer en {"type":"integer","label":"Status","enabled":"1","position":1100,"notnull":1,"visible":1,"default":"1","index":1,"arrayofkeyval":["D&eacute;sactiver","Activer"]}
					$val['type'] = 'arrayofkeyval';
					$this->fields[$key] = $val;
					// print_r($this->fields);
				}
				// dol_syslog("uptosign::showInputField table=$table et " . json_encode($val));
			}
			//selon version de dolibarr ...
			// if (((int) DOL_VERSION) < 14) {
			// }
		}
		dol_syslog("uptosign: avant l'appel à parent::showInputField key=$key,val=" . json_encode($val));
		if (((int) DOL_VERSION) < 11) {
			return parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);
		} else {
			/** @phpstan-ignore-next-line */
			return parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
		}
	}

	/**
	 * get details from document_model
	 *
	 * @param   string  $search  [$search description]
	 *
	 * @return  array           [return description]
	 */
	public function getDocumentModelDetails($search)
	{
		if (strpos($search, ':')) {
			$tab = explode(':', $search);
			$res['type'] = $tab[0];
			$res['nom'] = $tab[1];
		} else {
			$res['type'] = "undef";
			$res['nom'] = $search;
		}

		// global $conf;
		// $res = array();
		// $sql = "SELECT nom,libelle,type";
		// $sql .= " FROM ".MAIN_DB_PREFIX."document_model";
		// $sql .= " WHERE rowid = '".$this->db->escape($search)."'";
		// // $sql .= " AND entity = ".$conf->entity;
		// // print $sql;
		// $resql = $this->db->query($sql);
		// if ($resql) {
		//     $res = $this->db->fetch_array($resql);
		// } else {
		//     dol_print_error($this->db);
		// }
		return $res;
	}


	/**
	 * dolibarr 10 function setVarsFromFetchObj is protected !
	 *
	 * @param   CommonObject  $obj  [$obj description]
	 *
	 */
	public function uts_setVarsFromFetchObj(&$obj)
	{
		$this->setVarsFromFetchObj($obj);
	}
}


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class UptoSignConfigLine. You can also remove this and generate a CRUD class for lines objects.
 */
class UptoSignConfigLine extends CommonObjectLine
{
	// To complete with content of an object UptoSignConfigLine
	// We should have a field rowid, fk_uptosignconfig and position

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
