<?php
	/************************************************
	* Copyright (C) 2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infrashelpdesk/core/modules/modinfrashelpdesk.class.php
	*	\ingroup	InfraS
	*	\brief		Description and activation file for module InfraSHelpdesk
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrashelpdesk/core/lib/infrashelpdeskAdmin.lib.php');

	// Description and activation class *************
	class modinfrashelpdesk extends DolibarrModules
	{

		/**
		* Constructor. Define names, constants, directories, boxes, permissions
		* @param DoliDB $db Database handler
		**/
		function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infrashelpdesk@infrashelpdesk');

			infrashelpdesk_test_php_ext();

			$this->db				= $db;
			$this->numero			= 500101;
			$this->name				= preg_replace('/^mod/i', '', get_class($this));	// Module label (no space allowed), auto-derived from class name
			$this->editor_name		= '<b>InfraS - Sylvain Legrand</b>';
			$this->editor_email		= 'support@infras.fr';
			$this->editor_url		= 'https://www.infras.fr/';
			$this->url_last_version	= 'https://raw.githubusercontent.com/InfraS-SARL/modules-versions/main/'.$this->name.'/'.$this->name.'.txt';
			$this->rights_class		= $this->name;																			// Key text used to identify module (for permissions, menus, etc...)
			$isDolinfras			= isModEnabled('dolinfras');
			$family					= $isDolinfras ? getDolGlobalString('DOLINFRAS_FAMILY') : 'Modules '.$langs->trans('basenameInfraSHelpdesk');
			$this->family			= $family;																									// used to group modules in module setup page
			$this->familyinfo		= [$family => ['position' => '001', 'label' => $langs->trans($family)]];
			$this->module_position	= 100022;
			$this->description		= $langs->trans('Module500101Desc');													// Module description
			$this->version			= $this->getLocalVersion();																// Version read from docs/changelog.xml
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);
			$this->special			= 0;																					// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->picto			= $this->name.'@'.$this->name;															// Icon: object_infrashelpdesk.png in img/ folder
			$this->module_parts		= array('hooks'	=> array('all'),
											'js'	=> array('/'.$this->name.'/js/'.$this->name.'.js'),
											'css'	=> array('css' => '/'.$this->name.'/css/'.$this->name.'.css.php')
											);
			$this->config_page_url	= array('infrashelpdesksetup.php@'.$this->name);
			// Dependencies
			$this->hidden			= false;
			$this->depends			= array();																				// List of modules id that must be enabled if this module is enabled
			$this->requiredby		= array();																				// List of modules id to disable if this one is disabled
			$this->conflictwith		= array();																				// List of modules id this module is in conflict with
			$this->langfiles		= array($this->name.'@'.$this->name);
			// Constants
			$this->const			= array();
			$this->tabs				= array();
			if (!isModEnabled('infrashelpdesk')) {
				$conf->infrashelpdesk			= new stdClass();
				$conf->infrashelpdesk->enabled	= 0;
			}
			$this->dictionaries		= array('langs'				=> $this->name.'@'.$this->name,
											'tabname'			=> array(MAIN_DB_PREFIX.'c_infrashelpdesk_ctxurl'),																								// List of tables we want to see into dictionary editor
											'tablib'			=> array($langs->trans('InfraSHelpdeskCtxUrlDict')),																							// Label of tables
											'tabsql'			=> array('SELECT f.rowid as rowid, f.page, f.context, f.hooks, f.url_page, f.url_wiki, f.entity, f.active FROM '.MAIN_DB_PREFIX.'c_infrashelpdesk_ctxurl as f WHERE f.entity = '.((int) $conf->entity)),	// Request to select fields
											'tabsqlsort'		=> array('page ASC'),																															// Sort order
											'tabfield'			=> array('page,context,hooks,url_page,url_wiki'),																								// List of fields (result of select to show dictionary)
											'tabfieldvalue'		=> array('page,context,hooks,url_page,url_wiki'),																								// List of fields (list of fields to edit a record)
											'tabfieldinsert'	=> array('page,context,hooks,url_page,url_wiki,entity'),																						// List of fields (list of fields for insert)
											'tabrowid'			=> array('rowid'),																																// Name of columns with primary key
											'tabcond'			=> array(isModEnabled('infrashelpdesk'))
											);
			$this->boxes			= array();
			$this->cronjobs			= array();
			// Permissions
			$this->rights			= array();
			$r						= 0;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSHelpDeskPermMenu');												// libelle de la permission
			$this->rights[$r][3]	= 1;																					// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramMenu';																			// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSHelpDeskPermSpecif');												// libelle de la permission
			$this->rights[$r][3]	= 0;																					// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramInfraSHelpdesk';
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSHelpDeskPermBtn');												// libelle de la permission
			$this->rights[$r][3]	= 0;																					// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramInfraSHelpDeskBtn';																	// action for php test if ($user->rights->permkey->level1->level2)
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;																		// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSHelpDeskPermBkpRest');												// libelle de la permission
			$this->rights[$r][3]	= 0;																					// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramBkpRest';																		// action for php test if ($user->rights->permkey->level1->level2)
			// Menus
			$this->menu				= array();
			$caret					= '&nbsp;&nbsp;<span class = "caret	caret--left"></span>&nbsp;';
			if (!empty(infrashelpdesk_no_topmenu())) {
				// Menu Outils => entrée InfraS
				$this->menu[$r]		= array('fk_menu'	=> 'fk_mainmenu=tools',																										// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																													// This is a Left menu entry (top for top menu entry)
											'titre'		=> 'InfraS-2',
											'mainmenu'	=> 'tools',
											'leftmenu'	=> 'infras2',
											'url'		=> '/core/tools.php?mainmenu=tools',
											'langs'		=> $this->name.'@'.$this->name,																								// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 130,
											'enabled'	=> 1,																														// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> 1,																														// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
											'target'	=> '',																														// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
				$r++;
			}
			// Entrée InfraS - sous-titre InfraSHelpdesk
			$this->menu[$r]		= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras2',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
										'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
										'titre'		=> $langs->trans('modcomnameInfraSHelpdesk'),
										'mainmenu'	=> '',
										'leftmenu'	=> $this->name,
										'url'		=> '/core/tools.php?leftmenu='.$this->name,
										'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
										'position'	=> 132,
										'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
										'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',																// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
										'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
										'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSHelpdesk - Changelog
			$this->menu[$r]		= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras2',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
										'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
										'titre'		=> $caret.$langs->trans('InfraSHelpdeskParamsChangelog'),
										'mainmenu'	=> '',
										'leftmenu'	=> '',
										'url'		=> '/'.$this->name.'/admin/changelog.php?leftmenu='.$this->name,
										'langs'		=> $this->name.'@'.$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
										'position'	=> 134,
										'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
										'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',																// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
										'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
										'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSHelpdesk - Paramètres
			$this->menu[$r]		= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras2',																			// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
										'type'		=> 'left',																											// This is a Left menu entry (top for top menu entry)
										'titre'		=> $caret.$langs->trans('InfraSHelpdeskParams'),
										'mainmenu'	=> '',
										'leftmenu'	=> '',
										'url'		=> '/'.$this->name.'/admin/infrashelpdesksetup.php?leftmenu='.$this->name,
										'langs'		=> $this->name."@".$this->name,																						// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
										'position'	=> 135,
										'enabled'	=> !empty(isModEnabled($this->name)),																				// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
										'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramInfraSHelpdesk")',	// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
										'target'	=> '',																												// '' to replace page or 'blank' to open on a new page
										'user'		=> 0);																												// 0=Menu for internal users, 1=external users, 2=both
			$r++;																																					// 0=Menu for internal users, 1=external users, 2=both
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
			// Résout le jeton BRAND du préfixe wiki seedé par data.sql (lecture directe en base : la constante vient d'être insérée et n'est pas encore dans $conf)
			$prefix	= dolibarr_get_const($db, 'INFRASHELPDESK_PREFIX_LINK_WIKI_DOC', $conf->entity);
			if (is_string($prefix) && strpos($prefix, 'BRAND') !== false) {
				dolibarr_set_const($db, 'INFRASHELPDESK_PREFIX_LINK_WIKI_DOC', infrashelpdesk_resolve_brand($prefix), 'chaine', 0, 'InfraSHelpdesk module', $conf->entity);
			}
			return $this->_init(array(), $options);
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
			$sql	= array('DELETE FROM '.$this->db->prefix().'const WHERE name like "INFRASHELPDESK_%" AND entity = "'.$conf->entity.'"',
							'DROP TABLE IF EXISTS '.$this->db->prefix().'c_infrashelpdesk_ctxurl'
							);
			return $this->_remove($sql);
		}

		/**
		*	Function called to check module name from local changelog
		*	Control of the min version of Dolibarr needed
		*	If dolibarr version doesn't match the min version the module is disabled
		*	@return		string		current version or error message
		**/
		function getLocalVersion()
		{
			global $conf, $langs;

			if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1) {
				return $langs->trans('InfraSHelpdeskChangelogXMLError');
			}
			$currentversion					= infrashelpdesk_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version	= explode('.', $currentversion[1]);
			$this->phpmin					= explode('.', $currentversion[5]);
			$this->phpmax					= explode('.', $currentversion[6]);
			if (!getDolGlobalString('INFRASHELPDESK_DISABLE_CHECK_VERSION_MIN', '') && version_compare($currentversion[1], DOL_VERSION, '>')) {
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
			$currentversion	= infrashelpdesk_getLocalVersionMinDoli($this->name);
			$ChangeLog		= infrashelpdesk_getChangeLog($this->name, $currentversion[0], $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}
