<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/modules/modinfraspackplus.class.php
	* 	\ingroup	InfraS
	* 	\brief		Description and activation file for module InfraSPackPlus
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Description and activation class *************
	class modinfraspackplus extends DolibarrModules
	{
		/**
		* Constructor. Define names, constants, directories, boxes, permissions
		* @param DoliDB $db Database handler
		**/
		function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infraspackplus@infraspackplus');

			infraspackplus_test_php_ext();

			$this->db				= $db;
			$this->numero			= 550000;																				// Unique Id for module
			$this->name				= preg_replace('/^mod/i', '', get_class($this));	// Module label (no space allowed)
			$this->editor_name		= '<b>InfraS - Sylvain Legrand</b>';
			$this->editor_email		= 'support@infras.fr';
			$editor_web				= 'https://www.infras.fr/';
			$this->editor_url		= $editor_web;
			$this->url_last_version	= $editor_web.'jdownloads/Modules_Dolibarr/'.$this->name.'/'.$this->name.'.txt';
			$this->rights_class		= $this->name;																			// Key text used to identify module (for permissions, menus, etc...)
			$isDolinfras			= isModEnabled('dolinfras');
			$family					= $isDolinfras ? getDolGlobalString('DOLINFRAS_FAMILY') : 'Modules '.$langs->trans('basenamePackPlus');
			$this->family			= $family;																				// used to group modules in module setup page
			$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
			$this->module_position	= 100002;
			$this->description		= $langs->trans('Module550000Desc');												// Module description
			$this->version			= $this->getLocalVersion();																// Version : 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);										// llx_const table to save module status enabled/disabled
			$this->special			= 0;																					// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->picto			= $this->name.'@'.$this->name;															// Name of image file used for this module. If in theme => 'pictovalue' ; if in module => 'pictovalue@module' under name object_pictovalue.png
			$this->module_parts		= array('models'	=> 1,																// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
											'hooks'		=> array('main',
																 'login',
																 'formfile',
																 'pdfgeneration',
																 'thirdpartycard',
																 'globalcard',
																 'propalnote',
																 'ordernote',
																 'invoicenote',
																 'contractnote',
																 'expeditionnote',
																 'receptionnote',
																 'fichinternote',
																 'supplier_proposalnote',
																 'ordersuppliercardnote',
																 'invoicesuppliernote',
																 'expensereportnote'
																 ),
											'triggers'	=> 1,
											'css'		=> array('css' => '/'.$this->name.'/css/'.$this->name.'.css.php')
											);
			$this->dirs				= array('/mycompany/logos/thumbs',
											'/'.$this->name.'/fonts',
											'/'.$this->name.'/fonts/ttf',
											'/'.$this->name.'/specialfiles',
											'/'.$this->name.'/sql',
											'/'.$this->name.'/tmp');					// Data directories to create when module is enabled. Example: this->dirs = array("/mymodule/temp");
			$this->config_page_url	= array('infrasplussetup.php@'.$this->name);		// List of php page, stored into mymodule/admin directory, to use to setup module.
			// Dependencies
			$this->hidden			= false;											// A condition to hide module
			$this->depends			= array('modECM');									// List of modules id that must be enabled if this module is enabled
			// Soft dependency (not enforced by Dolibarr) : InfraSProject
			// - core/tpl/lineviews/_columns/refproject.tpl.php calls infrasproject_printprj()
			//   via dol_include_once('/infrasproject/core/lib/infrasproject.lib.php')
			//   when isModEnabled('infrasproject') is true (invoice_supplier context only).
			// - Reciprocal guard: actions_infrasproject.class.php yields printObjectLine
			//   view rendering to IPP when isModEnabled('infraspackplus') is true.
			// Graceful degradation: if InfraSProject is disabled, the supplier-invoice
			// "project" column is simply not rendered, no fatal error.
			$this->requiredby		= array();											// List of modules id to disable if this one is disabled
			$this->conflictwith		= array();											// List of modules id this module is in conflict with
			$this->langfiles		= array($this->name.'@'.$this->name);
			// Constants
			$this->const			= array();											// List of particular constants to add when module is enabled
			$this->tabs				= array();
			if (!isModEnabled('infraspackplus')) {
				$conf->infraspackplus			= new stdClass();
				$conf->infraspackplus->enabled	= 0;
			}
			$this->dictionaries		= array('langs'				=> $this->name.'@'.$this->name,
											'tabname'			=> array(	$this->db->prefix().'c_infraspackplus_mention',
																			$this->db->prefix().'c_infraspackplus_note'
																		),
											'tablib'			=> array(	'InfraSPlusDictMentions',
																			'InfraSPlusDictNotes'
																		),
											'tabsql'			=> array(	'SELECT rowid, code, entity, pos, libelle, active FROM '.$this->db->prefix().'c_infraspackplus_mention WHERE entity = '.getEntity($this->db->prefix().'c_infraspackplus_mention'),
																			'SELECT rowid, code, entity, pos, libelle, active FROM '.$this->db->prefix().'c_infraspackplus_note WHERE entity = '.getEntity($this->db->prefix().'c_infraspackplus_note')
																		),
											'tabsqlsort'		=> array(	'pos ASC',
																			'pos ASC'
																		),
											'tabfield'			=> array(	'code,pos,libelle',
																			'code,pos,libelle'
																		),
											'tabfieldvalue'		=> array(	'code,pos,libelle',
																			'code,pos,libelle'
																		),
											'tabfieldinsert'	=> array(	'code,pos,libelle,entity',
																			'code,pos,libelle,entity'
																		),
											'tabrowid'			=> array(	'rowid',
																			'rowid'
																		),
											'tabcond'			=> array(	isModEnabled('infraspackplus'),
																			isModEnabled('infraspackplus')
																		),
											'tabhelp'			=> array(	array('code'	=> $langs->trans('InfraSPlusDictEnterCodeMention1').' <FONT color = "red">'.$langs->trans('InfraSPlusCaution').' '.$langs->trans('InfraSPlusDictEnterCodeMention2').'</FONT>',
																				  'pos'		=> $langs->trans('PositionIntoComboList')),
																			array('code'	=> $langs->trans('EnterAnyCode'),
																				  'pos'		=> $langs->trans('PositionIntoComboList'))
																		)
											);	// Dictionaries
			$this->boxes			= array();										// List of boxes
			$this->cronjobs			= array();										// List of cron jobs entries to add
			$this->rights			= array();										// Permission array used by this module
			$r						= 0;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermMenu');			// libelle de la permission
			$this->rights[$r][3]	= 1;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramMenu';									// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermPDFDol');		// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramDolibarr';								// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermSpecif');		// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramInfraSPlus';							// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermImg');			// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramImages';								// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermAdr');			// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramAdresses';								// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermExtF');			// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramExtraFields';							// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermMent');			// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramMentions';								// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermNotes');			// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramNotes';									// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermDict');			// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramDict';									// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermGeneration');	// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramGeneration';							// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermBkpRest');		// libelle de la permission
			$this->rights[$r][3]	= 0;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramBkpRest';								// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermLastOpt');		// libelle de la permission
			$this->rights[$r][3]	= 1;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramLastOpt';								// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$r++;
			$this->rights[$r][0]	= $this->numero.$r;								// id de la permission
			$this->rights[$r][1]	= $langs->trans('InfraSPlusPermCGV');			// libelle de la permission
			$this->rights[$r][3]	= 1;											// La permission est-elle une permission par defaut (0/1)
			$this->rights[$r][4]	= 'paramCGV';									// action for php test if ($user->hasRight('permkey', 'level1', 'level2'))
			$this->menu				= array();										// List of menus to add
			$caret					= '&nbsp;&nbsp;<span class = "caret	caret--left"></span>&nbsp;';
			$r						= 0;
			if (!empty(infraspackplus_no_topmenu())) {
				// Menu Outils => entrée InfraS
				$this->menu[$r]		= array('fk_menu'	=> 'fk_mainmenu=tools',																							// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> 'InfraS',
											'mainmenu'	=> 'tools',
											'leftmenu'	=> 'infras',
											'url'		=> '/core/tools.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 50,
											'enabled'	=> 1,																											// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> 1,																											// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
				$r++;
			}
			// Entrée InfraS - sous-titre InfraSPackPlus
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $langs->trans('modcomnamePackPlus'),
											'mainmenu'	=> '',
											'leftmenu'	=> 'infras',
											'url'		=> '/core/tools.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 51,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> 1,																											// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Changelog
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsChangelog'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/changelog.php?leftmenu='.$this->name,
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 52,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu")',															// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres PDF de Dolibarr
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsGeneralPDF'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/generalpdf.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 53,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramDolibarr")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres spécifique InfraS
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsPDF'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/infrasplussetup.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 54,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramInfraSPlus")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres Images
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsImages'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/images.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 55,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramImages")',		// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres Adresses
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsAdresses'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/adresses.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 56,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramAdresses")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres Attributs supplémentaires
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsExtraFields'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/extrafields.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 57,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramExtraFields")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres Mentions complémentaires
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsMentions'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/mentions.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 58,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramMentions")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres Notes publiques
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsNotes'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/notes.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 59,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramNotes")',		// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres Dictionnaires
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsDict'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/dictionaries.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 60,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramDict")',		// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
			$r++;
			// Sous-titre InfraSPackPlus - Paramètres Génération
			$this->menu[$r]			= array('fk_menu'	=> 'fk_mainmenu=tools,fk_leftmenu=infras',																		// '' = top menu. left menu = 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
											'type'		=> 'left',																										// This is a Left menu entry (top for top menu entry)
											'titre'		=> $caret.$langs->trans('InfraSPlusParamsGeneration'),
											'mainmenu'	=> '',
											'leftmenu'	=> '',
											'url'		=> '/'.$this->name.'/admin/generation.php?leftmenu=infras',
											'langs'		=> $this->name.'@'.$this->name,																					// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
											'position'	=> 61,
											'enabled'	=> isModEnabled($this->name),																					// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
											'perms'		=> '$user->hasRight("'.$this->name.'", "paramMenu") && $user->hasRight("'.$this->name.'", "paramGeneration")',	// Use 'perms'=>'$user->hasRight('mymodule', 'level1', 'level2')' if you want your menu with a permission rules
											'target'	=> '',																											// '' to replace page or 'blank' to open on a new page
											'user'		=> 0);																											// 0=Menu for internal users, 1=external users, 2=both
		}

		/**
		* Function called when module is enabled.
		* The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
		* It also creates data directories
		* @param		string		$options		Options when enabling module ('', 'noboxes')
		* @return		int							1 if OK, 0 if KO
		**/
		function init($options = '')
		{
			global $conf, $db, $langs;

			$sql		= array();
			$path		= dol_buildpath($this->name, 0);
			$pathfonts	= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').$this->name.'/fonts';
			$resultCopy	= dolCopyDir(DOL_DOCUMENT_ROOT.'/includes/tecnickcom/tcpdf/fonts', $pathfonts, 0, 0);	// sync fonts from core to documents
			dol_syslog('modinfraspackplus.class::init pathfonts = '.$pathfonts.' resultCopy = '.$resultCopy);
			$resultCopy	= dolCopyDir($path.'/fonts', $pathfonts, 0, 0);	// sync fonts from module to documents
			dol_syslog('modinfraspackplus.class::init path/fonts = '.$path.'/fonts'.' pathfonts = '.$pathfonts.' resultCopy = '.$resultCopy);
			$this->_load_tables('/'.$this->name.'/sql/');
			infraspackplus_restore_module ($this->name);
			$res		= infraspackplus_migration_societe_address();
			if ($res < 0) {
				setEventMessage($langs->transnoentities('InfraSPlusParamMigrationError'), 'errors');
			}
			if (!getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT', ''))	{
				dolibarr_set_const($db, 'SOCIETE_ADDRESSES_MANAGEMENT', getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', 0), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
			dolibarr_set_const($db, 'INFRASPLUS_DOL_VERSION',	DOL_VERSION,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			dolibarr_set_const($db, 'INFRASPLUS_MAIN_VERSION',$this->version,	'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			dolibarr_del_const($db, 'INFRASPLUS_PDF_OPTION_typeadr',	$conf->entity);
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

			infraspackplus_bkup_module ($this->name);
			$sql		= array('DELETE FROM '.$this->db->prefix().'const WHERE name like "INFRASPLUS\_%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.$this->db->prefix().'const WHERE name like "INFRASPACKPLUS\_PS\_%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.$this->db->prefix().'const WHERE name like "MAIN\_MODULE\_INFRASPACKPLUS\_%" AND entity = "'.$conf->entity.'"',	// purge orphan module_parts constants (e.g. keys removed from descriptor between versions)
								'DELETE FROM '.$this->db->prefix().'const WHERE name like "%\_ADDON\_PDF" AND value like "InfraSPlus_%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.$this->db->prefix().'const WHERE name like "%\_FREE\_TEXT\_%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.$this->db->prefix().'const WHERE name like "%\_PUBLIC\_NOTE%" AND entity = "'.$conf->entity.'"',
								'DELETE FROM '.$this->db->prefix().'document_model WHERE nom like "InfraSPlus\_%" AND entity = "'.$conf->entity.'"',
								'DROP TABLE IF EXISTS '.$this->db->prefix().'infraspackplus_societe_address',
								'DROP TABLE IF EXISTS '.$this->db->prefix().'c_infraspackplus_mention',
								'DROP TABLE IF EXISTS '.$this->db->prefix().'c_infraspackplus_note');
			infraspackplus_search_extf (-1);
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
			global $conf, $langs;

			if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1) {
				return $langs->trans('InfraSPlusChangelogXMLError');
			}
			$currentversion					= array();
			$currentversion					= infraspackplus_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version	= explode('.', $currentversion[1]);	// Minimum version of Dolibarr required by module
			$this->phpmin					= explode('.', $currentversion[5]);	// Minimum version of PHP required by module
			$this->phpmax					= explode('.', $currentversion[6]);	// Maximum version of PHP required by module
			if (!getDolGlobalString('INFRASPACKPLUS_DISABLE_CHECK_VERSION_MIN', '') && version_compare($currentversion[1], DOL_VERSION, '>')) {
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
			$currentversion	= infraspackplus_getLocalVersionMinDoli($this->name);
			$ChangeLog		= infraspackplus_getChangeLog($this->name, $currentversion[0], $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}