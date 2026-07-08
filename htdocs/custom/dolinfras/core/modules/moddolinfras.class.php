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
	*	\file		./dolinfras/core/modules/moddolinfras.class.php
	*	\ingroup	InfraS
	*	\brief		Description and activation file for module DolInfraS
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/dolinfras/core/lib/dolinfrasAdmin.lib.php');

	// Description and activation class *************
	class moddolinfras extends DolibarrModules
	{

		/**
		* Constructor. Define names, constants, directories, boxes, permissions
		* @param DoliDB $db Database handler
		**/
		function __construct($db)
		{
			global $langs, $conf;

			$langs->load('dolinfras@dolinfras');

			dolinfras_test_php_ext();

			$this->db				= $db;
			$this->numero			= 500100;
			$this->name				= preg_replace('/^mod/i', '', get_class($this));	// Module label (no space allowed), auto-derived from class name
			$this->editor_name		= '<b>InfraS - Sylvain Legrand</b>';
			$this->editor_email		= 'support@infras.fr';
			$this->editor_url		= 'https://www.infras.fr/';
			$this->url_last_version	= 'https://raw.githubusercontent.com/InfraS-SARL/modules-versions/main/'.$this->name.'/'.$this->name.'.txt';
			$this->rights_class		= $this->name;																			// Key text used to identify module (for permissions, menus, etc...)
			$family					= '<span class = "dolinfraspuentedolibarr">Dolibarr</span> LTS by <span class = "dolinfrasneuropolinfras"> InfraS</span>';
			$this->family			= $family;																				// Used to group modules in module setup page
			$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
			$this->module_position	= 100001;
			$this->description		= $langs->trans('Module500100Desc');													// Module description
			$this->version			= $this->getLocalVersion();																// Version read from docs/changelog.xml
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);
			$this->special			= 0;																					// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
			$this->picto			= $this->name.'@'.$this->name;															// Icon: object_dolinfras.png in img/ folder
			$this->module_parts		= array('hooks'	=> array('login'),
											'js'	=> array('/'.$this->name.'/js/'.$this->name.'.js'),
											'css'	=> array('css' => '/'.$this->name.'/css/'.$this->name.'.css.php')
											);
			$this->config_page_url	= array('dolinfrassetup.php@'.$this->name);
			// Dependencies
			$this->hidden			= false;
			$this->depends			= array();																				// List of modules id that must be enabled if this module is enabled
			$this->requiredby		= array();																				// List of modules id to disable if this one is disabled
			$this->conflictwith		= array();																				// List of modules id this module is in conflict with
			$this->langfiles		= array($this->name.'@'.$this->name);
			// Constants
			$this->const			= array();
			$this->tabs				= array();
			if (!isModEnabled('dolinfras')) {
				$conf->dolinfras			= new stdClass();
				$conf->dolinfras->enabled	= 0;
			}
			$this->dictionaries		= array();
			$this->boxes			= array();
			$this->cronjobs			= array();
			// Permissions
			$this->rights			= array();
			// Menus
			$this->menu				= array();
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

			return $this->_remove(array());
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
				return $langs->trans('DolInfraSChangelogXMLError');
			}
			$currentversion					= dolinfras_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version	= explode('.', $currentversion[1]);
			$this->phpmin					= explode('.', $currentversion[5]);
			$this->phpmax					= explode('.', $currentversion[6]);
			if (!getDolGlobalString('DOLINFRAS_DISABLE_CHECK_VERSION_MIN', '') && version_compare($currentversion[1], DOL_VERSION, '>')) {
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
			$currentversion	= dolinfras_getLocalVersionMinDoli($this->name);
			$ChangeLog		= dolinfras_getChangeLog($this->name, $currentversion[0], $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}
