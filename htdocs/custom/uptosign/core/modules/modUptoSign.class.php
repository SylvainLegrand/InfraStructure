<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 Éric Seigne <eric.seigne@cap-rel.fr>
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
 * 	\defgroup   uptosign     Module UptoSign
 *  \brief      UptoSign module descriptor.
 *
 *  \file       htdocs/uptosign/core/modules/modUptoSign.class.php
 *  \ingroup    uptosign
 *  \brief      Description and activation file for module UptoSign
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
dol_include_once('/uptosign/lib/uptosign.lib.php');

/**
 *  Description and activation class for module UptoSign
 */
class modUptoSign extends DolibarrModules
{
	public $url_last_version;
	public $tabs;
	public $dictionaries;

	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 471040; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'uptosign';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleUptoSignName' not found (UptoSign is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleUptoSignDesc' not found (UptoSign is name of module).
		$this->description = "UptoSignDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "UptoSignDescription";

		// Author
		$this->editor_name = 'CAP-REL';
		$this->editor_url = 'https://cap-rel.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '2.4.1';
		// Url to the file with your last numberversion of this module
		$this->url_last_version = "https://cap-rel.fr/dolibarr/ver.php?m=" . $this->rights_class . "&v=" . $this->version;

		// Key used in llx_const table to save module status enabled/disabled (where UPTOSIGN is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'uptosign@uptosign';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 1,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
					'/uptosign/css/uptosign.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				'/uptosign/js/uptosign.js',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				'data' => array(
					'admin',
					'emailtemplates',
					'propalcard',
					'propallist',
					'interventioncard',
					'interventionlist',
					'ordercard',
					'orderlist',
					'contractcard',
					'contractlist',
					'expeditioncard',
					'shipmentlist',
					'invoicecard',
					'invoicelist',
					'projectcard',
					'projectlist',
					'uptosignnewonlinesign',
					'uptosigncard',
					'uptosigncustomcard', //pour doliletter et autres
					'usercard',
					'infrassalariescontractscard', // pour le module infrassalariescontracts
					'formfile',   //pour ajouter proprement l'icone seal/sign dans la liste des fichiers joints
					'createFrom', //pour capturer le createfromclone et affecter eventuelement uptosign si config
					'contactcard', //pour ajouter le bouton "uptosign sign all docs"
					'ordersuppliercard' //pour signer les commandes fournisseur
				),
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/uptosign/temp","/uptosign/subdir");
		$this->dirs = array("/uptosign/sql");

		// Config pages. Put here list of php page, stored into uptosign/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@uptosign");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array('always1' => 'modECM'); //todo, 'always2' => 'modArchivesPdf');
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("uptosign@uptosign");

		// Prerequisites
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(15, -3); // Minimum version of Dolibarr required by module
		//version 15 pour trigger PROPAL_REOPEN

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'UptoSignWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('UPTOSIGN_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('UPTOSIGN_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->uptosign) || !isset($conf->uptosign->enabled)) {
			$conf->uptosign = new stdClass();
			$conf->uptosign->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		$this->tabs[] = array('data'=>'propal:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=propal&id=__ID__');
		$this->tabs[] = array('data'=>'order:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=commande&id=__ID__');
		$this->tabs[] = array('data'=>'supplier_order:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=supplier_order&id=__ID__');
		$this->tabs[] = array('data'=>'invoice:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=invoice&id=__ID__');
		$this->tabs[] = array('data'=>'contract:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=contract&id=__ID__');
		$this->tabs[] = array('data'=>'intervention:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=intervention&id=__ID__');
		$this->tabs[] = array('data'=>'project:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=project&id=__ID__');
		$this->tabs[] = array('data'=>'thirdparty:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=societe&id=__ID__');
		$this->tabs[] = array('data'=>'user:+tabUptoSign:UptoSignTab:uptosign@uptosign:$user->rights->uptosign->create:/uptosign/uptosign_tab.php?objectType=user&id=__ID__');


		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@uptosign:$user->rights->uptosign->read:/uptosign/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@uptosign:$user->rights->othermodule->read:/uptosign/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view

		// Dictionaries
		// $this->dictionaries = array();
		/* Example: */
		$this->dictionaries=array(
			'langs'=>'uptosign@uptosign',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."c_digitalsign"),
			// Label of tables
			'tablib'=>array("DigitalSign"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active, f.module FROM '.MAIN_DB_PREFIX.'c_digitalsign as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,label,module"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->uptosign->enabled),
			// Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
			'tabhelp'=>array(array('code' => 'Code', 'label' => 'Label')),
		);
		/* */

		// Boxes/Widgets
		// Add here list of php file(s) stored in uptosign/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'uptosignwidget1.php@uptosign',
			//      'note' => 'Widget provided by UptoSign',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$arraydate = dol_getdate(dol_now());
		$datestart = dol_mktime(rand(0, 6), rand(0, 59), 0, $arraydate['mon'], $arraydate['mday'], $arraydate['year']);
		$this->cronjobs = array(
			 0 => array(
				 'label' => 'UptoSignResellerCron',
				 'jobtype' => 'method',
				 'class' => '/uptosign/class/uptosign.class.php',
				 'objectname' => 'Uptosign',
				 'method' => 'doScheduledJob',
				 'parameters' => '',
				 'comment' => 'UptoSignResellerCronComments',
				 'frequency' => 1,
				 'unitfrequency' => 86400,
				 'status' => 0,
				 'test' => '$conf->uptosign->enabled',
				 'priority' => 50,
				 'datenextrun' => $datestart,
			 ),
			 1 => array(
				 'label' => 'UptoSignArchiveCron',
				 'jobtype' => 'method',
				 'class' => '/uptosign/class/uptosign.class.php',
				 'objectname' => 'Uptosign',
				 'method' => 'doScheduledArchive',
				 'parameters' => '',
				 'comment' => 'UptoSignArchiveCronComments',
				 'frequency' => 1,
				 'unitfrequency' => 86400,
				 'status' => 0,
				 'test' => '$conf->uptosign->enabled',
				 'priority' => 50,
				 'datenextrun' => $datestart,
			 ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->uptosign->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->uptosign->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
		$this->rights[$r][1] = 'ReadUptoSign';     // Permission label
		$this->rights[$r][3] = 1;                   // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';              // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)
		$this->rights[$r][5] = '';                  // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;      // Permission id (must not be already used)
		$this->rights[$r][1] = 'CreateUptoSign'; // Permission label
		$this->rights[$r][3] = 1;                       // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'create';                // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)
		$this->rights[$r][5] = '';                      // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
		$this->rights[$r][1] = 'DeleteUptoSign';   // Permission label
		$this->rights[$r][3] = 0;                   // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';            // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)
		$this->rights[$r][5] = '';                  // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
		$this->rights[$r][1] = 'UptoSignUserCanSign';   // Permission label
		$this->rights[$r][3] = 0;                   // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'sign';              // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)
		$this->rights[$r][5] = '';                  // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
		$this->rights[$r][1] = 'UptoSignEmployeeRead';   // Permission label
		$this->rights[$r][3] = 0;                   // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'employee';              // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)
		$this->rights[$r][5] = 'read';                  // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;  // Permission id (must not be already used)
		$this->rights[$r][1] = 'UptoSignEmployeeCreate';   // Permission label
		$this->rights[$r][3] = 0;                   // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'employee';              // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)
		$this->rights[$r][5] = 'create';                  // In php code, permission will be checked by test if ($user->rights->uptosign->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'ReadUptoSignConfig';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'uptosignconfig';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'CreateUptoSignConfig';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'uptosignconfig';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'DeleteUptoSignConfig';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'uptosignconfig';
		$this->rights[$r][5] = 'delete';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'ReadUptoSignList';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'uptosignlist';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'CreateUptoSignList';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'uptosignlist';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'DeleteUptoSignList';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'uptosignlist';
		$this->rights[$r][5] = 'delete';

		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		// $this->menu[$r++] = array(
		// 	'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'type'=>'top', // This is a Top menu entry
		// 	'titre'=>'ModuleUptoSignName',
		// 	'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
		// 	'mainmenu'=>'uptosign',
		// 	'leftmenu'=>'',
		// 	'url'=>'/uptosign/index.php',
		// 	'langs'=>'uptosign@uptosign', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'position'=>1000 + $r,
		// 	'enabled'=>'$conf->uptosign->enabled', // Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled.
		// 	'perms'=>'1', // Use 'perms'=>'$user->rights->uptosign->read' if you want your menu with a permission rules
		// 	'target'=>'',
		// 	'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		// );
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU UPTOSIGN
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=uptosign',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Left menu entry
			'titre'=>'Uptosign',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'uptosign',
			'leftmenu'=>'uptosign',
			'url'=>'/uptosign/index.php',
			'langs'=>'uptosign@uptosign',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->uptosign->enabled',  // Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->uptosign->read',			                // Use 'perms'=>'$user->rights->uptosign->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=uptosign,fk_leftmenu=uptosign',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'List_Uptosign',
			'mainmenu'=>'uptosign',
			'leftmenu'=>'uptosign_list',
			'url'=>'/uptosign/uptosign_list.php',
			'langs'=>'uptosign@uptosign',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->uptosign->enabled',  // Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->uptosign->read',			                // Use 'perms'=>'$user->rights->uptosign->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=uptosign,fk_leftmenu=uptosign',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New_Uptosign',
			'mainmenu'=>'uptosign',
			'leftmenu'=>'uptosign_new',
			'url'=>'/uptosign/uptosign_card.php?action=create',
			'langs'=>'uptosign@uptosign',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->uptosign->enabled',  // Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->uptosign->write',			                // Use 'perms'=>'$user->rights->uptosign->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		*/

		$this->menu[$r++]=array(
			// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'fk_menu'=>'fk_mainmenu=ecm',
			// This is a Left menu entry
			'type'=>'left',
			'titre'=>'UpToSign',
			'mainmenu'=>'ecm',
			'leftmenu'=>'uptosign',
			'prefix' => img_picto('', 'object_uptosign@uptosign', 'class="paddingright pictofixedwidth"'),
			'url'=>'/uptosign/uptosign_list.php',
			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'langs'=>'uptosign@uptosign',
			'position'=>1100+$r,
			// Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'enabled'=>'$conf->uptosign->enabled',
			// Use 'perms'=>'$user->rights->uptosign->level1->level2' if you want your menu with a permission rules
			'perms'=>'1',
			'target'=>'',
			// 0=Menu for internal users, 1=external users, 2=both
			'user'=>2,
		);

		//TODO uptosignlist a venir
		$this->menu[$r++]=array(
			// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'fk_menu'=>'fk_mainmenu=ecm,fk_leftmenu=uptosign',
			// This is a Left menu entry
			'type'=>'left',
			'titre'=>'uptosignlistMenu',
			'mainmenu'=>'ecm',
			'leftmenu'=>'uptosignlistMenu',
			'prefix' => img_picto('', 'object_uptosign@uptosign', 'class="paddingright pictofixedwidth"'),
			'url'=>'/uptosign/uptosignlist_list.php',
			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'langs'=>'uptosign@uptosign',
			'position'=>1100+$r,
			// Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'enabled'=>'$conf->uptosign->enabled',
			// Use 'perms'=>'$user->rights->uptosign->level1->level2' if you want your menu with a permission rules
			'perms'=>'1',
			'target'=>'',
			// 0=Menu for internal users, 1=external users, 2=both
			'user'=>2,
		);

		//TODO dev en cours
		// $this->menu[$r++]=array(
		// 	// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		// 	'fk_menu'=>'fk_mainmenu=hrm',
		// 	// This is a Left menu entry
		// 	'type'=>'left',
		// 	'titre'=>'UptoSign',
		// 	'mainmenu'=>'hrm',
		// 	'leftmenu'=>'uptosign',
		// 	'prefix' => img_picto('', 'object_uptosign@uptosign', 'class="paddingright pictofixedwidth"'),
		// 	'url'=>'/uptosign/employee_list.php',
		// 	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		// 	'langs'=>'uptosign@uptosign',
		// 	'position'=>1100+$r,
		// 	// Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		// 	'enabled'=>'$conf->uptosign->enabled',
		// 	// Use 'perms'=>'$user->rights->uptosign->level1->level2' if you want your menu with a permission rules
		// 	'perms' => '$user->rights->uptosign->employee->read',
		// 	'target'=>'',
		// 	// 0=Menu for internal users, 1=external users, 2=both
		// 	'user'=>2,
		// );


		// $this->menu[$r++] = array(
		// 	'fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=uptosign',
		// 	'type' => 'left',
		// 	'titre' => 'UptoSignEmployeeNew',
		// 	'mainmenu' => 'hrm',
		// 	'leftmenu' => 'uptosign',
		// 	'url' => '/uptosign/employee_card.php',
		// 	'langs' => 'uptosign@uptosign',
		// 	'position' => 1100 + $r,
		// 	'enabled' => '$conf->uptosign->enabled',
		// 	'perms' => '$user->rights->uptosign->employee->create',
		// 	'target' => '',
		// 	'user' => 2,
		// );

		// $this->menu[$r++]=array(
		//     // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		//     'fk_menu'=>'fk_mainmenu=ecm,fk_leftmenu=uptosign',
		//     // This is a Left menu entry
		//     'type'=>'left',
		//     'titre'=>'New Uptosign',
		//     'mainmenu'=>'ecm',
		//     'leftmenu'=>'uptosign',
		//     'url'=>'/uptosign/uptosign_card.php?action=create',
		//     // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//     'langs'=>'uptosign@uptosign',
		//     'position'=>1100+$r,
		//     // Define condition to show or hide menu entry. Use '$conf->uptosign->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//     'enabled'=>'$conf->uptosign->enabled',
		//     // Use 'perms'=>'$user->rights->uptosign->level1->level2' if you want your menu with a permission rules
		//     'perms'=>'1',
		//     'target'=>'',
		//     // 0=Menu for internal users, 1=external users, 2=both
		//     'user'=>2
		// );

		/* END MODULEBUILDER LEFTMENU UPTOSIGN */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT UPTOSIGN */
		/*
		$langs->load("uptosign@uptosign");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='UptosignLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='uptosign@uptosign';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'Uptosign'; $keyforclassfile='/uptosign/class/uptosign.class.php'; $keyforelement='uptosign@uptosign';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'UptosignLine'; $keyforclassfile='/uptosign/class/uptosign.class.php'; $keyforelement='uptosignline@uptosign'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='uptosign'; $keyforaliasextra='extra'; $keyforelement='uptosign@uptosign';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='uptosignline'; $keyforaliasextra='extraline'; $keyforelement='uptosignline@uptosign';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('uptosignline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'uptosign as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'uptosign_line as tl ON tl.fk_uptosign = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('uptosign').')';
		$r++; */
		/* END MODULEBUILDER EXPORT UPTOSIGN */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT UPTOSIGN */
		/*
		 $langs->load("uptosign@uptosign");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='UptosignLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='uptosign@uptosign';
		 $keyforclass = 'Uptosign'; $keyforclassfile='/uptosign/class/uptosign.class.php'; $keyforelement='uptosign@uptosign';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='uptosign'; $keyforaliasextra='extra'; $keyforelement='uptosign@uptosign';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'uptosign as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('uptosign').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT UPTOSIGN */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $db, $user;
		$sql = array();
		dol_include_once('/uptosign/lib/uptosign_upgrades.lib.php');
		dol_include_once('/uptosign/lib/uptosign_uptosignconfig.lib.php');

		//$result = $this->_load_tables('/install/mysql/tables/', 'uptosign');
		$result = $this->_load_tables('/uptosign/sql/');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		//Migration du module 1.x -> 2.x ?
		if (isset($conf->global->UPTOSIGN_MODULE_VERSION) && substr($conf->global->UPTOSIGN_MODULE_VERSION, 0, 1) == '1') {
			dol_syslog("uptosign module migrate from version 1.x", LOG_DEBUG);
			//cas particulier : la base de données n'a pas été nettoyée lors du désactivate pour qu'on puisse faire la migration ici
			//1. migration des clés de configuration
			uptosign_migrate_conf_v1_to_v2();

			//2. migration de l'historique des transactions
			$sql = array_merge($sql, array(
				"INSERT INTO ".MAIN_DB_PREFIX."uptosign(rowid,ref,entity,label,fk_soc,description,date_creation,date_sign,tms,fk_user_creat,fk_user_modif,import_key,status,fk_object,object_type,sign_status,sign_id,fk_contact_sign,fk_user_sign,hash_file,path_file,api_name,hook_key) SELECT rowid,ref,entity,label,fk_soc,description,date_creation,date_sign,tms,fk_user_creat,fk_user_modif,import_key,status,fk_object,object_type,sign_status,sign_id,fk_contact_sign,fk_user_sign,hash_file,path_file,api_name,hook_key FROM ".MAIN_DB_PREFIX."uptosign_old",
				"DROP TABLE IF EXISTS ".MAIN_DB_PREFIX."uptosign_old",
				"DROP TABLE IF EXISTS ".MAIN_DB_PREFIX."uptosign_config_old"
			));

			//3. migration des configurations de documents / position automatique des signatures ?
			//TODO ?
		} else {
			//reprise normale de la vie
			uptosign_restore_module(strtolower($this->name));
		}

		//cleanup #28
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		if (is_dir(DOL_DATA_ROOT.'/UptoSign')) {
			@dol_delete_dir_recursive(DOL_DATA_ROOT.'/UptoSign');
		}

		// force re-read data because of prev sql make insert of $conf->global->UPTOSIGN_MODULE_VERSION
		$sqlMig = "SELECT value as ver FROM ".MAIN_DB_PREFIX."const WHERE name='UPTOSIGN_MODULE_VERSION'";
		$resqlMig = $db->query($sqlMig);
		if ($resqlMig) {
			$obj = $db->fetch_object($resqlMig);
			//Migration des données file_path (passage de la 2.0.38 à 2.0.40)
			//donc si UPTOSIGN_MODULE_VERSION était < 2.0.40
			if (isset($obj->ver) && (version_compare($obj->ver, "2.0.40") == -1)) {
				uptosign_migrate_data_before_2_0_38();
			}
		}

		//full disable non available templates
		uptosignDisableAllImpossibleTemplates();

		// $version = uptosign_bkup_get_version($this->rights_class);
		// if ($version != -1) {
		//     //Si le backup était d'une version < 2.0.0
		//     if (version_compare($version, '2.0.0', '<')) {
		//     } else {
		// }

		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		// note via llx_c_digitalsign mais je ne trouve pas comment faire pour avoir une valeur par défaut qui marche
		// $result = $extrafields->addExtraField('digitalsign',    $langs->trans('DigitalSign'),    'sellist', 1100, 100, 'propal',   0, 0, 2, array('options' => array('c_digitalsign:label:rowid::active=1' => null)), 1, '', 1, '', '', '', 'uptosign@uptosign', '$conf->uptosign->enabled', 0, 0);
		// $result = $extrafields->addExtraField('digitalsign',    $langs->trans('DigitalSign'),    'sellist', 1100, 100, 'commande', 0, 0, 2, array('options' => array('c_digitalsign:label:rowid::active=1' => null)), 1, '', 1, '', '', '', 'uptosign@uptosign', '$conf->uptosign->enabled', 0, 0);
		// $result = $extrafields->addExtraField('digitalsign',    $langs->trans('DigitalSign'),    'sellist', 1100, 100, 'contrat',  0, 0, 2, array('options' => array('c_digitalsign:label:rowid::active=1' => null)), 1, '', 1, '', '', '', 'uptosign@uptosign', '$conf->uptosign->enabled', 0, 0);

		//donc en attendant, passage sur un dropdown classique
		// show function uptosign_list_of_elements_with_extrafield()
		$result = $extrafields->addExtraField('digitalsign', $langs->trans('DigitalSign'), 'select', 1100, 100, 'propal', 0, 0, 'uptosign', array('options' => array('dolibarr' =>"DolibarrNative", 'uptosign' => "UpToSignCertified")), 1, '', 1, 'DigitalSignTooltip', '', '', 'uptosign@uptosign', '$conf->uptosign->enabled', 0, 0);
		$result = $extrafields->addExtraField('digitalsign', $langs->trans('DigitalSign'), 'select', 1100, 100, 'commande', 0, 0, 'uptosign', array('options' => array('dolibarr' =>"DolibarrNative", 'uptosign' => "UpToSignCertified")), 1, '', 1, 'DigitalSignTooltip', '', '', 'uptosign@uptosign', '$conf->uptosign->enabled', 0, 0);
		$result = $extrafields->addExtraField('digitalsign', $langs->trans('DigitalSign'), 'select', 1100, 100, 'contrat', 0, 0, 'uptosign', array('options' => array('dolibarr' =>"DolibarrNative", 'uptosign' => "UpToSignCertified")), 1, '', 1, 'DigitalSignTooltip', '', '', 'uptosign@uptosign', '$conf->uptosign->enabled', 0, 0);
		$result = $extrafields->addExtraField('digitalsign', $langs->trans('DigitalSign'), 'select', 1100, 100, 'projet', 0, 0, 'uptosign', array('options' => array('dolibarr' =>"DolibarrNative", 'uptosign' => "UpToSignCertified")), 1, '', 1, 'DigitalSignTooltip', '', '', 'uptosign@uptosign', '$conf->uptosign->enabled', 0, 0);
		$result = $extrafields->addExtraField('digitalsign_disable_sms', $langs->trans('DigitalSignCodeBy'), 'select', 1200, 1, 'thirdparty', 0, 0, '0', array('options' => array('0' =>"DigitalSignCodeBySMS", '1' => "DigitalSignCodeByEmail")), 1, '', 1, 'DigitalSignCodeByTooltip', '', '', 'uptosign@uptosign', 'getDolGlobalString("UPTOSIGN_DISABLE_SMS_SELECT_THIRDPART")', 0, 0);

		// UPTOSIGN_DISABLE_SMS_SELECT_THIRDPART
		// Document templates
		// $moduledir = dol_sanitizeFileName('uptosign');
		// $myTmpObjects = array();
		// $myTmpObjects['Uptosign'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		// foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
		// 	if ($myTmpObjectKey == 'Uptosign') {
		// 		continue;
		// 	}
		// 	if ($myTmpObjectArray['includerefgeneration']) {
		// 		$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_uptosigns.odt';
		// 		$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
		// 		$dest = $dirodt.'/template_uptosigns.odt';

		// 		if (file_exists($src) && !file_exists($dest)) {
		// 			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		// 			dol_mkdir($dirodt);
		// 			$result = dol_copy($src, $dest, 0, 0);
		// 			if ($result < 0) {
		// 				$langs->load("errors");
		// 				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
		// 				return 0;
		// 			}
		// 		}

		// 		$sql = array_merge($sql, array(
		// 			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
		// 			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")",
		// 			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
		// 			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
		// 		));
		// 	}
		// }

		//open md5file and remove all old files
		// $modulepath	= DOL_DOCUMENT_ROOT.'/custom/'.$this->rights_class;
		// if (!is_dir($modulepath)) {
		//     $modulepath	= DOL_DOCUMENT_ROOT.'/'.$this->rights_class;
		// }
		// if (!is_dir($modulepath)) {
		//     dol_syslog("uptosign module init, can't find module path", LOG_ERR);
		// }
		// if (file_exists($modulepath . '/content.md5')) {
		//     dol_syslog("uptosign module init, content.md5 find, use it to clean up old install", LOG_DEBUG);
		//     $csvData = file_get_contents($modulepath . '/content.md5');
		//     $lines = explode(PHP_EOL, $csvData);
		//     $md5files = array();
		//     foreach ($lines as $line) {
		// 		$l = explode(';',$line);
		//         $md5files[$l[0]] = $l[1];
		//     }
		//     uptosign_cleanupModulePath($modulepath, $modulepath, $md5files);
		// }

		if (utsbackports_getDolGlobalString('PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN', '') == '') {
			$rand = dol_hash(uniqid((string) mt_rand(), false), 'md5');
			dolibarr_set_const($db, 'PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN', $rand, 'chaine', 0, 'Set sign security token', $conf->entity);
		}

		if (utsbackports_getDolGlobalString('CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN', '') == '') {
			$rand = dol_hash(uniqid((string) mt_rand(), false), 'md5');
			dolibarr_set_const($db, 'CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN', $rand, 'chaine', 0, 'Set sign security token', $conf->entity);
		}

		if (utsbackports_getDolGlobalString('FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN', '') == '') {
			$rand = dol_hash(uniqid((string) mt_rand(), false), 'md5');
			dolibarr_set_const($db, 'FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN', $rand, 'chaine', 0, 'Set sign security token', $conf->entity);
		}

		if (utsbackports_getDolGlobalString('PROJECT_ONLINE_SIGNATURE_SECURITY_TOKEN', '') == '') {
			$rand = dol_hash(uniqid((string) mt_rand(), false), 'md5');
			dolibarr_set_const($db, 'PROJECT_ONLINE_SIGNATURE_SECURITY_TOKEN', $rand, 'chaine', 0, 'Set sign security token', $conf->entity);
		}

		if (utsbackports_getDolGlobalString('COMMANDE_ONLINE_SIGNATURE_SECURITY_TOKEN', '') == '') {
			$rand = dol_hash(uniqid((string) mt_rand(), false), 'md5');
			dolibarr_set_const($db, 'COMMANDE_ONLINE_SIGNATURE_SECURITY_TOKEN', $rand, 'chaine', 0, 'Set sign security token', $conf->entity);
		}

		if (utsbackports_getDolGlobalString('SOCIETE_RIB_ONLINE_SIGNATURE_SECURITY_TOKEN', '') == '') {
			$rand = dol_hash(uniqid((string) mt_rand(), false), 'md5');
			dolibarr_set_const($db, 'SOCIETE_RIB_ONLINE_SIGNATURE_SECURITY_TOKEN', $rand, 'chaine', 0, 'Set sign security token', $conf->entity);
		}

		dolibarr_set_const($db, 'UPTOSIGN_MODULE_VERSION', $this->version, 'chaine', 0, 'Active module version', $conf->entity);
		dolibarr_del_const($db, 'UPTOSIGN_FILENAME_SUFFIX_UPTOSEAL', $conf->entity);
		dol_syslog("uptosign module end init", LOG_DEBUG);
		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		global $conf, $db;
		dol_include_once('/uptosign/lib/uptosign_upgrades.lib.php');

		dol_syslog("uptosign module remove for entity=" . $conf->entity, LOG_DEBUG);
		$sql = array();

		if ((int) $conf->entity != 1) {
			dol_syslog("uptosign module remove, multientity detected, not on main entity, do not remove data", LOG_DEBUG);
			return $this->_remove($sql, $options);
		}
		//cas très particulier de la migration 1.x -> 2.x on ne supprime rien
		//pour savoir si on était en version 1.x on regarde si UPTOSIGN_MODULE_VERSION existe :)
		if (utsbackports_getDolGlobalString('UPTOSIGN_MODULE_VERSION', '')  != '') {
			uptosign_bkup_module(strtolower($this->name));
			$sql = array("DELETE FROM ".MAIN_DB_PREFIX."const WHERE name like 'UPTOSIGN\_%' AND entity = '".$conf->entity . "';",
						'DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'uptosign;',
						'DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'uptosign_config;',
						'DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'uptosign_uptosignconfig;');
		} else {
			//multicomp ?
			if ((int) $conf->entity == 1) {
				//dans le cas où ça n'était pas présent, on l'ajoute pour que le init du module sache quoi faire
				dolibarr_set_const($db, 'UPTOSIGN_MODULE_VERSION', '1.x', 'chaine', 0, 'Active module version', $conf->entity);
				$sql = array(
					'RENAME TABLE '.MAIN_DB_PREFIX.'uptosign TO '.MAIN_DB_PREFIX.'uptosign_old;',
					'RENAME TABLE '.MAIN_DB_PREFIX.'uptosign_config TO '.MAIN_DB_PREFIX.'uptosign_config_old;',
				);
			}
		}
		//manual delete cron due to strange AND test=1
		$sql[] = "DELETE FROM ".MAIN_DB_PREFIX."cronjob WHERE module_name = 'uptosign' AND entity = '".$conf->entity . "';";
		return $this->_remove($sql, $options);
	}
}
