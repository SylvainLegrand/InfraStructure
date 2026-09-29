<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infrasfiles/core/modules/modinfrasfiles.class.php
	*	\ingroup	InfraS
	*	\brief		Description and activation file for module InfraSFiles
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');
	dol_include_once('/infrasfiles/core/lib/infrasfilesAdmin.lib.php');

	// Description and activation class *************
	class modinfrasfiles extends DolibarrModules
	{

		/**
		* Constructor. Define names, constants, directories, boxes, permissions
		* @param DoliDB $db Database handler
		**/
		function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infrasfiles@infrasfiles');

			infrasfiles_test_php_ext();

			$this->db				= $db;
			$this->numero			= 550100;																				// Unique Id for module
			$this->name				= preg_replace('/^mod/i', '', get_class($this));										// Module label (no space allowed)
			$this->editor_name		= '<b>InfraS - Sylvain Legrand</b>';
			$this->editor_email		= 'support@infras.fr';
			$this->editor_url		= 'https://www.infras.fr/';
			$this->url_last_version	= 'https://raw.githubusercontent.com/InfraS-SARL/modules-versions/main/'.$this->name.'/'.$this->name.'.txt';
			$this->rights_class		= $this->name;																			// Key text used to identify module (for permissions, menus, etc...)
			$isDolinfras			= isModEnabled('dolinfras');
			$family					= $isDolinfras ? getDolGlobalString('DOLINFRAS_FAMILY') : 'Modules '.$langs->trans('basenameFile');
			$this->family			= $family;																				// used to group modules in module setup page
			$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
			$this->module_position	= 100014;
			$this->description		= $langs->trans('Module550100Desc');													// Module description
			$this->version			= $this->getLocalVersion();																// Version : 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);												// llx_const table to save module status enabled/disabled
			$this->special			= 0;																					// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->picto			= $this->name.'@'.$this->name;															// Name of image file used for this module. If in theme => 'pictovalue' ; if in module => 'pictovalue@module' under name object_pictovalue.png
			$this->module_parts		= array('hooks'		=> array('login', 'document', 'formmail', 'emailtemplates', 'directdebitprevcard', 'inventorycard'),	// Hook contexts : login (version check), document (file access control), formmail (recipients), emailtemplates (template types), cards of supported objects
											'models'	=> 1,																// The module provides document models (core/modules/<element>/doc/)
											'css'		=> array('css' => '/'.$this->name.'/css/'.$this->name.'.css.php')
											);
			$this->dirs				= array('/'.$this->name,																// Data directories to create when module is enabled
											'/'.$this->name.'/temp',
											'/'.$this->name.'/widthdraw',
											'/'.$this->name.'/inventory');
			$this->config_page_url	= array('infrasfilessetup.php@'.$this->name);											// List of php page, stored into mymodule/admin directory, to use to setup module.
			// Dependencies
			$this->hidden			= false;																				// A condition to hide module
			$this->depends			= array();																				// List of modules id that must be enabled if this module is enabled
			$this->requiredby		= array();																				// List of modules id to disable if this one is disabled
			$this->conflictwith		= array();																				// List of modules id this module is in conflict with
			$this->langfiles		= array($this->name.'@'.$this->name);
			// Constants
			$this->const			= array(0	=> array('INFRASFILES_WIDTHDRAW_SPLIT_MODE', 'chaine', 'thirdparty', 'InfraSFiles module', 0, 'current', 0));	// Default values (kept if already set)
			foreach (infrasfiles_get_registry() as $element => $definition) {
				if (!empty($definition['trigger'])) {
					$this->const[]	= array('MAIN_AGENDA_ACTIONAUTO_'.$definition['trigger'], 'chaine', '1', 'InfraSFiles module', 0, 'current', 0);	// agenda event on send by e-mail (can be disabled in the agenda setup)
				}
			}
			// "Documents" tab on the native card of every object of the registry (stored at activation : the condition, not the declaration, follows the setup)
			// Format : objecttype:+tabname:Title:langfile:condition:url — no ':' allowed in the condition (it is the separator) ; $user->hasRight("a", "b") is
			// accepted by dol_eval() in simple mode from Dolibarr 18 (explicitly listed in its comments), unlike $user->rights->a->b which warns when the property is absent
			$this->tabs				= array();
			foreach (infrasfiles_get_registry() as $element => $definition) {
				$condition	= 'isModEnabled("infrasfiles") && getDolGlobalInt("'.infrasfiles_const_name($element, 'ENABLED').'")';
				if (!empty($definition['permread'])) {
					$condition	.= ' && $user->hasRight("'.implode('", "', (array) $definition['permread']).'")';
				}
				$this->tabs[]	= $definition['tabcontext'].':+infrasfilesdoc:Documents:main:'.$condition.':/'.$this->name.'/document.php?element='.$element.'&id=__ID__';
			}
			if (!isModEnabled('infrasfiles')) {
				$conf->infrasfiles			= new stdClass();
				$conf->infrasfiles->enabled	= 0;
			}
			$this->dictionaries		= array();																				// Dictionaries
			$this->boxes			= array();																				// List of boxes
			$this->cronjobs			= array();																				// List of cron jobs entries to add
			$this->rights			= array();																				// Permission array used by this module
			$r						= 0;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSFilesPermMenu');													// libelle de la permission
			$this->rights[$r][3]	= 1;																					// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramMenu';																			// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSFilesPermSpecif');												// libelle de la permission
			$this->rights[$r][3]	= 0;																					// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramInfraSFiles';																	// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSFilesPermBkpRest');												// libelle de la permission
			$this->rights[$r][3]	= 0;																					// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramBkpRest';																		// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSFilesPermRead');													// libelle de la permission
			$this->rights[$r][3]	= 0;																					// Not a default permission : files of the objects are served on the NATIVE permission (hook checkSecureAccess) ; this one only adds the module backup file
			$this->rights[$r][4]	= 'read';																				// Generic branch of dol_check_secure_access_document() for modulepart = infrasfiles
			$this->menu				= array();																				// List of menus to add
			$caret					= '&nbsp;&nbsp;<span class = "caret	caret--left"></span>&nbsp;';
			$r						= 0;
			if (!empty(infrasfiles_no_topmenu())) {
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
			// Entrée InfraS - sous-titre InfraSFiles
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('modcomnameFile'),
											'mainmenu'	=> '',
											'leftmenu'	=> $this->name,
											'url'		=> '/core/tools.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 75,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',																// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSFiles - Changelog
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSFilesParamsChangelog'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/changelog.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 76,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',																// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSFiles - Paramètres spécifique InfraS
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSFilesParams'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/infrasfilessetup.php?leftmenu='.$this->name,
											'langs'		=> $this->name."@".$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 77,
											'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramInfraSFiles")',	// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
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
			global $langs, $conf, $db;

			$this->_load_tables('/'.$this->name.'/sql/');
			infrasfiles_restore_module($this->name);
			dolibarr_set_const($db, 'INFRASFILES_DOL_VERSION',	DOL_VERSION,	'chaine', 0, 'InfraSFiles module', $conf->entity);
			dolibarr_set_const($db, 'INFRASFILES_MAIN_VERSION',	$this->version,	'chaine', 0, 'InfraSFiles module', $conf->entity);
			// Idempotent migration : models activated with the registry key as type (development builds) instead of the docpart 'infrasfileswidthdraw'
			$sql	= array('DELETE FROM '.$this->db->prefix()."document_model WHERE type = 'widthdraw' AND entity = ".((int) $conf->entity));
			// Agenda automatic events (one row per trigger, shown in the agenda setup), inserted only when missing
			foreach (infrasfiles_get_registry() as $element => $definition) {
				if (empty($definition['trigger'])) {
					continue;
				}
				$code	= $this->db->escape($definition['trigger']);
				$label	= $this->db->escape($langs->transnoentities('Notify_'.$definition['trigger']));
				$sql[]	= 'INSERT INTO '.$this->db->prefix().'c_action_trigger (code, label, description, elementtype, rang)';
				$sql[count($sql) - 1]	.= " SELECT '".$code."', '".$label."', '".$label."', '".$this->db->escape($element)."', 90";	// no FROM DUAL : portable (MySQL, MariaDB, PostgreSQL)
				$sql[count($sql) - 1]	.= ' WHERE NOT EXISTS (SELECT 1 FROM '.$this->db->prefix()."c_action_trigger WHERE code = '".$code."')";
			}
			// Default e-mail template per object (language of the activation, editable in Setup > Emails > Templates), inserted only when missing (unique key entity + label + lang)
			$langs->load('infrasfiles@infrasfiles');
			foreach (infrasfiles_get_registry() as $element => $definition) {
				if (empty($definition['mailtype']) || empty($definition['mailtemplate'])) {
					continue;
				}
				$label	= $this->db->escape($langs->transnoentities($definition['mailtemplate'].'Label'));
				$topic	= $this->db->escape($langs->transnoentities($definition['mailtemplate'].'Topic'));
				$body	= $this->db->escape($langs->transnoentities($definition['mailtemplate'].'Content'));
				$sql[]	= 'INSERT INTO '.$this->db->prefix().'c_email_templates (entity, module, type_template, lang, private, datec, label, position, defaultfortype, enabled, active, email_from, email_to, email_tocc, email_tobcc, topic, joinfiles, content)';
				$sql[count($sql) - 1]	.= ' SELECT '.((int) $conf->entity).", 'infrasfiles', '".$this->db->escape($definition['mailtype'])."', '', 0, '".$this->db->idate(dol_now())."', '".$label."', 1, 1, '1', 1, '', '', '', '', '".$topic."', '1', '".$body."'";
				$sql[count($sql) - 1]	.= ' WHERE NOT EXISTS (SELECT 1 FROM '.$this->db->prefix().'c_email_templates WHERE entity = '.((int) $conf->entity)." AND type_template = '".$this->db->escape($definition['mailtype'])."')";
			}
			// Third party extrafield "address the slips to the parent company" (boolean, shown on the third party card while the module is enabled),
			// created only when missing and never removed at deactivation : the choices made on the third parties are kept
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields	= new ExtraFields($db);
			$extrafields->fetch_name_optionals_label('societe');
			if (empty($extrafields->attributes['societe']['label'][infrasfiles_to_parent_field()])) {
				// entity '' = the entity activating the module (the core stores $conf->entity), not shared between entities
				$extrafields->addExtraField(infrasfiles_to_parent_field(), 'InfraSFilesExtraParent', 'boolean', 100, '', 'societe', 0, 0, '', '', 1, '', '1', 'InfraSFilesExtraParentHelp', '', '', 'infrasfiles@infrasfiles', 'isModEnabled("infrasfiles")', 0, 0);
			}
			return $this->_init($sql, $options);
		}

		/**
		*	Function called when module is disabled.
		*	Remove from database constants, boxes and permissions from Dolibarr database.
		*	Data directories are not deleted
		*	@param		string		$options		Options when enabling module ('', 'noboxes')
		*	@return		int							1 if OK, 0 if KO
		**/
		function remove($options = '')
		{
			global $langs, $conf;

			infrasfiles_bkup_module($this->name);
			$sql	= array('DELETE FROM '.$this->db->prefix()."const WHERE name LIKE 'INFRASFILES_%' AND entity = ".((int) $conf->entity));
			return $this->_remove($sql);
		}

		/**
		*	Function called to check module name from local changelog
		*	Control of the min version of Dolibarr needed
		*	If dolibarr version does'nt match the min version the module is disabled
		*	@return		string		current version or error message
		**/
		function getLocalVersion()
		{
			global $conf, $langs;

			if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1) {
				return $langs->trans('InfraSFilesChangelogXMLError');
			}
			$currentversion					= array();
			$currentversion					= infrasfiles_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version	= explode('.', $currentversion[1]);	// Minimum version of Dolibarr required by module
			$this->phpmin					= explode('.', $currentversion[5]);	// Minimum version of PHP required by module
			$this->phpmax					= explode('.', $currentversion[6]);	// Maximum version of PHP required by module
			if (!getDolGlobalString('INFRASFILES_DISABLE_CHECK_VERSION_MIN', '') && version_compare($currentversion[1], DOL_VERSION, '>')) {
				$this->disabled	= true;
			}
			return $currentversion[0];
		}

		/**
		*	Function called to view changelog on help tab
		*	@return		string		html view
		**/
		function getChangeLog()
		{
			$currentversion	= infrasfiles_getLocalVersionMinDoli($this->name);
			$ChangeLog		= infrasfiles_getChangeLog($this->name, $currentversion[0], $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}
