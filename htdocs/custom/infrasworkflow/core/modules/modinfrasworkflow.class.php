<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrasworkflow/core/modules/modinfrasworkflow.class.php
	* 	\ingroup	InfraS
	* 	\brief		Description and activation file for module InfraSWorkflow
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrasworkflow/core/lib/infrasworkflowAdmin.lib.php');

	// Description and activation class *************
	class modinfrasworkflow extends DolibarrModules
	{
		/************************************************
		* Constructor. Define names, constants, directories, boxes, permissions
		* @param DoliDB $db Database handler
		************************************************/
		function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infrasworkflow@infrasworkflow');
			infrasworkflow_test_php_ext();
			$this->db				= $db;
			$this->numero			= 500080;																				// Unique Id for module
			$this->name				= preg_replace('/^mod/i', '', get_class($this));										// Module label (no space allowed)
			$this->editor_name		= '<b>InfraS - Sylvain Legrand</b>';
			$this->editor_email		= 'support@infras.fr';
			$this->editor_url		= 'https://www.infras.fr/';
			$this->url_last_version	= 'https://raw.githubusercontent.com/InfraS-SARL/modules-versions/main/'.$this->name.'/'.$this->name.'.txt';
			$this->rights_class		= $this->name;																			// Key text used to identify module (for permissions, menus, etc...)
			$isDolinfras			= isModEnabled('dolinfras');
			$family					= $isDolinfras ? getDolGlobalString('DOLINFRAS_FAMILY') : 'Modules '.$langs->trans('basenameInfraSWorkflow');
			$this->family			= $family;																				// used to group modules in module setup page
			$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
			$this->module_position	= 100008;
			$this->description		= $langs->trans('Module500080Desc');													// Module description
			$this->version			= $this->getLocalVersion();																// Version : 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);												// llx_const table to save module status enabled/disabled
			$this->special			= 0;																					// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->picto			= 'infrasworkflow@infrasworkflow';														// Name of image file used for this module. If in theme => 'pictovalue' ; if in module => 'pictovalue@module' under name object_pictovalue.png
			$this->module_parts		= array('hooks'		=> array('main',
																'login',
																'formfile',
																'globalcard',
																'thirdpartycomm',
																'paymentdao',
																'contractcard',
																'supplierinvoicelist',
																'contratdao',
																'contractdao',
																'ordercard',
																'orderlist'
																),
											'triggers'	=> 1,
											'css'		=> array('css' => '/'.$this->name.'/css/'.$this->name.'.css.php')
											);
			$this->dirs				= array('/'.$this->name.'/sql');														// Data directories to create when module is enabled. Example: this->dirs = array("/mymodule/temp");
			$this->config_page_url	= array('infrasworkflowsetup.php@'.$this->name);										// List of php page, stored into mymodule/admin directory, to use to setup module.
			// Dependencies
			$this->hidden			= false;																				// A condition to hide module
			$this->depends			= array();																				// List of modules id that must be enabled if this module is enabled
			$this->requiredby		= array();																				// List of modules id to disable if this one is disabled
			$this->conflictwith		= array();																				// List of modules id this module is in conflict with
			$this->langfiles		= array($this->name.'@'.$this->name);
			// List of particular constants to add when module is enabled
			$this->const			= array();
			// Array to add new pages in new tabs
			// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  				// To add a new tab identified by code tabname1
			//                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  // To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
			//                              'objecttype:-tabname:NU:conditiontoremove');                                                     									// To remove an existing tab identified by code tabname
			$this->tabs[]			= array('data'=>'contract:+tabProduct:ProductTab:infrasworkflow@infrasworkflow:($user->rights->contrat->lire && (int) getDolGlobalInt("INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE")==1):/infrasworkflow/tabs/infrasworkflow_contrat_products_tab.php?objectType=contract&id=__ID__');
			//$this->tabs				= array();
			if (!isModEnabled('infrasworkflow')) {
				$conf->infrasworkflow			= new stdClass();
				$conf->infrasworkflow->enabled	= 0;
			}
			// Dictionaries
			$this->dictionaries		= array();
			// List of boxes
			$this->boxes			= array();
			// List of cron jobs entries to add
			$this->cronjobs			= array();
			// Permission array used by this module
			$this->rights			= array();
			$r						= 0;
			$this->rights[$r][0]	= $this->numero.$r;									// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSWorkflowPermMenu');		// libelle de la permission
			$this->rights[$r][3]	= 1;												// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramMenu';										// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;									// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSWorkflowPermSpecif');	// libelle de la permission
			$this->rights[$r][3]	= 0;												// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramInfraSWorkflow';							// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;									// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSWorkflowPermBkpRest');	// libelle de la permission
			$this->rights[$r][3]	= 0;												// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramBkpRest';									// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;									// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSWorkflowPermAdd');		// libelle de la permission
			$this->rights[$r][3]	= 0;												// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'use';											// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;									// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSWorkflowPermExtF');		// libelle de la permission
			$this->rights[$r][3]	= 0;												// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramExtraFields';								// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;									// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSWorkflowPermMedias');	// libelle de la permission
			$this->rights[$r][3]	= 1;												// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'accessmedias';									// action for php test if ($user->rights->permkey->level1->level2)
			// List of menus to add
			$this->menu				= array();
			$caret					= '&nbsp;&nbsp;<span class = "caret	caret--left"></span>&nbsp;';
			$r						= 0;
			if (!empty(infrasworkflow_no_topmenu())) {
				// Menu Outils => entrée InfraS
				$this->menu[$r]		= array('fk_menu'	=> 'fk_mainmenu=tools',																								// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> 'InfraS',
											'mainmenu'	=> 'tools',
											'leftmenu'	=> 'infras',
											'url'		=> '/core/tools.php?mainmenu=tools',
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 50,
											'enabled'	=> 1,																												// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> 1,																												// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
				$r++;
			}
			// Entrée InfraS - sous-titre InfraSWorkflow
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('modcomnameInfraSWorkflow'),
											'mainmenu'	=> '',
											'leftmenu'	=> $this->name,
											'url'		=> '/core/tools.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 115,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',																// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSWorkflow - Changelog
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSWorkflowParamsChangelog'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/changelog.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 116,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',																// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSWorkflow - Paramètres spécifique InfraS
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSWorkflowParams'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/infrasworkflowsetup.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 117,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramInfraSWorkflow")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);
			$r++;
			// Sous-titre InfraSWorkflow - Paramètres Attributs supplémentaires
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSWorkflowParamsExtraFields'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/extrafields.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 118,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramExtraFields")',		// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
		}

		/**
		*	Function called when module is enabled.
		*	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
		*	It also creates data directories
		*	@param		string		$options		Options when enabling module ('', 'noboxes')
		*	@return		int							1 if OK, 0 if KO
		**/
		function init($options = '')
		{
			global $conf, $db;

			$sql			= array();
			$this->_load_tables('/'.$this->name.'/sql/');
			infrasworkflow_restore_module ($this->name);
			$listTypeKey	= array('propal'										=> 'propal',
									'propaldet'										=> 'propal',
									'commande'										=> 'commande',
									'commandedet'									=> 'commande',
									'facture'										=> 'facture',
									'facturedet'									=> 'facture',
									'facture_rec'									=> 'facture',
									'facturedet_rec'								=> 'facture',
									'supplier_proposal'								=> 'supplier_proposal',
									'supplier_proposaldet'							=> 'supplier_proposal',
									'commande_fournisseur'							=> 'fournisseur',
									'commande_fournisseurdet'						=> 'fournisseur',
									'commande_fournisseur_dispatch'					=> 'fournisseur',
									'facture_fourn'									=> 'fournisseur',
									'facture_fourn_det'								=> 'fournisseur',
									'projet'										=> 'projet',
									'projet_task'									=> 'projet',
									'fichinter'										=> 'ficheinter',
									'fichinterdet'									=> 'ficheinter',
									'contrat'										=> 'contrat',
									'contratdet'									=> 'contrat',
									'expedition'									=> 'expedition',
									'expeditiondet'									=> 'expedition',
									'reception'										=> 'reception',
									'mrp_mo'										=> 'mrp',
									'mrp_production'								=> 'mrp',
									'bom_bom'										=> 'bom',
									'bom_bomline'									=> 'bom',
									'product'										=> 'product',
									'product_fournisseur_price'						=> 'product',
									'product_lot'									=> 'product',
									'entrepots'										=> 'stock',
									'stock_mouvement'								=> 'stock',
									'inventory'										=> 'stock',
									'actioncomm'									=> 'agenda',
									'bank'											=> 'banque',
									'bank_account'									=> 'banque',
									'societe'										=> 'societe',
									'socpeople'										=> 'societe',
									'partnership'									=> 'partnership',
									'user'											=> 'user',
									'usergroup'										=> 'user',
									'salary'										=> 'salaries',
									'expensereport'									=> 'expensereport',
									'holiday'										=> 'holiday',
									'hrm_evaluation'								=> 'hrm',
									'hrm_job'										=> 'hrm',
									'hrm_skill'										=> 'hrm',
									'recruitment_recruitmentcandidature'			=> 'recruitment',
									'recruitment_recruitmentjobposition'			=> 'recruitment',
									'ecm_directories'								=> 'ecm',
									'ecm_files'										=> 'ecm',
									'ticket'										=> 'ticket',
									'knowledgemanagement_knowledgerecord'			=> 'knowledgemanagement',
									'resource'										=> 'resource',
									'eventorganization_conferenceorboothattendee'	=> 'eventorganization',
									'adherent'										=> 'adherent',
									'adherent_type'									=> 'adherent',
									'don'											=> 'don'
									);
			dolibarr_set_const($db, 'INFRASWORKFLOW_LISTTYPEKEY', http_build_query ($listTypeKey, ''), 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
			dolibarr_set_const($db, 'INFRASWORKFLOW_DOL_VERSION', DOL_VERSION, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
			dolibarr_set_const($db, 'INFRASWORKFLOW_MAIN_VERSION', $this->version, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
			return $this->_init($sql, $options);
		}

		/**
		* Function called when module is disabled.
		* Remove from database constants, boxes and permissions from Dolibarr database.
		* Data directories are not deleted
		* @param		string		$options		Options when enabling module ('', 'noboxes')
		* @return		int							1 if OK, 0 if KO
		**/
		function remove($options= '')
		{
			global $conf;

			infrasworkflow_bkup_module ($this->name);
			$sql	= array('DELETE FROM '.$this->db->prefix().'const WHERE name like "INFRASWORKFLOW\_%" AND entity = '.((int) $conf->entity));
			return $this->_remove($sql);
		}

		/**
		* Function called to check module name from local changelog
		* Control of the min version of Dolibarr needed
		* If dolibarr version does'nt match the min version the module is disabled
		* @return		string		current version or error message
		**/
		function getLocalVersion()
		{
			global $langs;

			if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1)	{
				return $langs->trans('InfraSWorkflowChangelogXMLError');
			}
			$currentversion					= array();
			$currentversion					= infrasworkflow_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version	= explode('.', $currentversion[1]);	// Minimum version of Dolibarr required by module
			$this->phpmin					= explode('.', $currentversion[5]);	// Minimum version of PHP required by module
			$this->phpmax					= explode('.', $currentversion[6]);	// Maximum version of PHP required by module
			if (!getDolGlobalString('INFRASWORKFLOW_DISABLE_CHECK_VERSION_MIN', '') && version_compare($currentversion[1], DOL_VERSION, '>')) {
				$this->disabled	= true;
			}
			return $currentversion[0];
		}

		/**
		* Function called to view changelog on help tab
		* @return		string		html view
		**/
		function getChangeLog()
		{
			$currentversion	= infrasworkflow_getLocalVersionMinDoli($this->name);
			$ChangeLog		= infrasworkflow_getChangeLog($this->name, $currentversion[0], $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}
