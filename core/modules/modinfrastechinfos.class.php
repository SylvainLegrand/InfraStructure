<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <http://www.infras.fr>
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
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrastechinfos/core/modules/modinfrastechinfos.class.php
	* 	\ingroup	InfraS
	* 	\brief		Description and activation file for module InfraS supplier price
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrastechinfos/core/lib/infrastechinfosAdmin.lib.php');

	// Description and activation class *************
	class modinfrastechinfos extends DolibarrModules
	{
		/**
		* Constructor. Define names, constants, directories, boxes, permissions
		* @param DoliDB $db Database handler
		**/
		function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infrastechinfos@infrastechinfos');

			InfraSTechInfos_test_php_ext();

			$this->db				= $db;
			$this->numero			= 500060;																				// Unique Id for module
			$this->name				= preg_replace('/^mod/i', '', get_class($this));										// Module label (no space allowed)
			$this->editor_name		= '<b>InfraS - Sylvain Legrand</b>';
			$this->editor_email		= 'support@infras.fr';
			$this->editor_url		= 'https://www.infras.fr/';
			$this->url_last_version	= 'https://raw.githubusercontent.com/InfraS-SARL/modules-versions/main/'.$this->name.'/'.$this->name.'.txt';
			$this->rights_class		= $this->name;																			// Key text used to identify module (for permissions, menus, etc...)
			$isDolinfras			= isModEnabled('dolinfras');
			$family					= $isDolinfras ? getDolGlobalString('DOLINFRAS_FAMILY') : 'Modules '.$langs->trans('basenameTechInfos');
			$this->family			= $family;																				// used to group modules in module setup page
			$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
			$this->module_position	= 100020;
			$this->description		= $langs->trans('Module500060Desc');													// Module description
			$this->version			= $this->getLocalVersion();																// Version : 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);												// llx_const table to save module status enabled/disabled
			$this->special			= 0;																					// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->picto			= $this->name.'@'.$this->name;															// Name of image file used for this module. If in theme => 'pictovalue' ; if in module => 'pictovalue@module' under name object_pictovalue.png
			$this->module_parts		= array('hooks'	=>array('login',
															'propalcard',
															'ordercard',
															'expeditioncard',
															'supplier_proposalcard',
															'ordersuppliercard',
															),
											'css'	=> array('css' => '/'.$this->name.'/css/'.$this->name.'.css.php')
											); 												// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
			$this->dirs				= array('/'.$this->name.'/sql');						// Data directories to create when module is enabled. Example: this->dirs = array("/mymodule/temp");
			$this->config_page_url	= array('infrastechinfossetup.php@'.$this->name);		// List of php page, stored into mymodule/admin directory, to use to setup module.
			// Dependencies
			$this->hidden			= false;												// A condition to hide module
			$this->depends			= array();												// List of modules id that must be enabled if this module is enabled
			$this->requiredby		= array();												// List of modules id to disable if this one is disabled
			$this->conflictwith		= array();												// List of modules id this module is in conflict with
			$this->langfiles		= array($this->name."@".$this->name);
			// Constants
			$this->const			= array();												// List of particular constants to add when module is enabled
			$this->tabs				= array();
			if (! isset($conf->infrastechinfos->enabled)) {
				$conf->infrastechinfos			= new stdClass();
				$conf->infrastechinfos->enabled	= 0;
			}
			$this->dictionaries		= array();										// Dictionaries
			$this->boxes			= array();										// List of boxes
			$this->cronjobs			= array();										// List of cron jobs entries to add
			$r						= 0;
			$this->rights[$r][0]	= $this->numero + $r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSTechInfosPermMenu');		// libelle de la permission
			$this->rights[$r][3]	= 1;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'InfraSTechInfosParamMenu';					// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSTechInfosPermBkpRest');	// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut
			$this->rights[$r][4]	= 'paramBkpRest';								// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero + $r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSTechInfosPermSpecif');	// libelle de la permission
			$this->rights[$r][3]	= 1;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'InfraSTechInfosParamSpecif';					// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero + $r;							// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSTechInfosPermView');		// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut
			$this->rights[$r][4]	= 'InfraSTechInfosView';						// action
			$this->menu				= array();										// List of menus to add
			$caret					= '&nbsp;&nbsp;<span class = "caret	caret--left"></span>&nbsp;';
			$r						= 0;
			if (!empty(infrastechinfos_no_topmenu())) {
				// Menu Outils => entrée InfraS
				$this->menu[$r]		= array('fk_menu'	=> 'fk_mainmenu=tools',																											// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																														// This is a Left menu entry (top for top menu entry)
											'titre'		=> 'InfraS',
											'mainmenu'	=> 'tools',
											'leftmenu'	=> 'infras',
											'url'		=> '/core/tools.php?mainmenu=tools',
											'langs'		=> $this->name.'@'.$this->name,																									// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 50,
											'enabled'	=> 1,																															// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> 1,																															// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																															// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																															// 0=Menu for internal users, 1=external users, 2=both
				$r++;
			}
			// Entrée InfraS - sous-titre InfraSTechInfos
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																						// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																														// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('InfraSTechInfos'),
											'mainmenu'	=> '',
											'leftmenu'	=> $this->name,
											'url'		=> '/core/tools.php?leftmenu='.$this->name,
											'langs'		=> $this->name."@".$this->name,																									// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 105,
											'enabled'	=> '$conf->'.$this->name.'->enabled',																							// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '1',																															// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																															// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																															// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSTechInfos - Changelog
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSTechInfosParamsChangelog'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/changelog.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 106,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',																// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSTechInfos - Paramètres spécifique InfraS
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																						// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																														// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSTechInfosParamsSetup'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/infrastechinfos.php?leftmenu='.$this->name,
											'langs'		=> $this->name."@".$this->name,																									// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 107,
											'enabled'	=> '$conf->'.$this->name.'->enabled',																							// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "InfraSTechInfosParamMenu") && $user->hasRight("'.$this->name.'", "InfraSTechInfosParamSpecif")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																															// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																															// 0=Menu for internal users, 1=external users, 2=both
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

			$sql	= array();
			$this->_load_tables('/'.$this->name.'/sql/');
			infrastechinfos_restore_module ($this->name);
			dolibarr_set_const($db, 'INFRASTECHINFOS_DOL_VERSION',	DOL_VERSION,	'chaine', 0, 'InfraSTechInfos module', $conf->entity);
			dolibarr_set_const($db, 'INFRASTECHINFOS_MAIN_VERSION',	$this->version,	'chaine', 0, 'InfraSTechInfos module', $conf->entity);
			return $this->_init($sql, $options);
		}

		/**
		* Function called when module is disabled.
		* Remove from database constants, boxes and permissions from Dolibarr database.
		* Data directories are not deleted
		* @param		string		$options		Options when enabling module ('', 'noboxes')
		* @return		int							1 if OK, 0 if KO
		**/
		function remove($options = '')
		{
			global $conf;

			infrastechinfos_bkup_module ($this->name);
			$sql		= array('DELETE FROM '.$this->db->prefix().'const WHERE name LIKE "INFRASTECHINFOS\_%" AND entity = '.((int) $conf->entity));
			return $this->_remove($sql, $options);
		}

		/**
		* Function called to check module name from local changelog
		* Control of the min version of Dolibarr needed
		* If dolibarr version does'nt match the min version the module is disabled
		* @return		string		current version or error message
		**/
		function getLocalVersion()
		{
			global $conf, $langs;

			if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1)	return $langs->trans('InfraSTechInfosChangelogXMLError');
			$currentversion											= array();
			$currentversion											= infrastechinfos_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version							= $currentversion[1];
			$this->phpmin											= explode('.', $currentversion[5]);	// Minimum version of PHP required by module
			$this->phpmax											= explode('.', $currentversion[6]);	// Maximum version of PHP required by module
			if (!getDolGlobalString('INFRASTECHINFOS_DISABLE_CHECK_VERSION_MIN', '') && version_compare($currentversion[1], DOL_VERSION, '>')) {
				$this->disabled	= true;
			}
			return $currentversion[0];
		}

		/**
		* Function called to view changelog
		* @return		string		html view
		**/
		function getChangeLog()
		{
			$currentversion	= infrastechinfos_getLocalVersionMinDoli($this->name);
			$ChangeLog		= infrastechinfos_getChangeLog($this->name, $currentversion[0], $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}
