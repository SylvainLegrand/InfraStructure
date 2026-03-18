<?php

/**
 * Copyright © 2015-2016 Marcos García de La Fuente <hola@marcosgdf.com>
 *
 * This file is part of Multismtp.
 *
 * Multismtp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Multismtp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Multismtp.  If not, see <http://www.gnu.org/licenses/>.
 */

/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module MyModule
 */
class modMultismtp extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
        global $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 402002;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'multismtp';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        // It is used to group modules by family in module setup page
		$isDolinfras	= isModEnabled('dolinfras');
		$family			= $isDolinfras ? getDolGlobalString('DOLINFRAS_FAMILY') : 'base';
		$this->family = $family;
		$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
		$this->module_position	= 100018;

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = 'Multi SMTP';
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Permite la configuración de una cuenta de correo a cada usuario";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.4.6';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_MULTISMTP';
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 2;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='email';

		$this->module_parts = array(
			'hooks' => array(
				'main'
			),
			'triggers' => 1
		);

		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		$this->config_page_url = array("index.php@multismtp");

		// Dependencies
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->phpmin = array(5,0);
		$this->need_dolibarr_version = array(3,6,3);
		$this->langfiles = array("multismtp@multismtp");

		$this->tabs = array(
			'user:+email:Email:main:1:/multismtp/user.php?id=__ID__'
		);

		$this->const = array(
			array(
				0 => 'MAIN_ACTIVATE_UPDATESESSIONTRIGGER',
				1 => 'chaine',
				2 => '1',
				3 => 'Used by MultiSMTP module',
				4 => true
			),
			array(
				0 => 'MULTISMTP_SMTP_ENABLED',
				1 => 'int',
				2 => '0',
				3 => '',
				4 => false,
				5 => 'current'
			)
		);
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$this->_load_tables('/multismtp/sql/');

		return $this->_init(array(), $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

}