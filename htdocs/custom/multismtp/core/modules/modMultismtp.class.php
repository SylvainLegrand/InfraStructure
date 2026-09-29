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
		$this->module_position	= 100048;	// InfraS change

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Permite la configuración de una cuenta de correo a cada usuario";
		$this->editor_name      = '<b>Opendsi</b>';
        $this->editor_web       = 'https://opendsi.fr';
        $this->editor_url       = "https://opendsi.fr";
        $this->editor_email     = 'support@open-dsi.fr';
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = trim(file_get_contents(__DIR__.'/../../VERSION'));
		$this->url_last_version = 'https://git.open-dsi.fr/dolibarr-extension/'.strtolower($this->name).'/-/raw/2024/VERSION';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_MULTISMTP';
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 2;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        if((float) DOL_VERSION <= 11.0) {
            $this->picto='opendsi@'.strtolower($this->name);
        } else {
            $this->picto='opendsi_big@'.strtolower($this->name);
        }

		$this->module_parts = array(
			'hooks' => array(
				'main'
				,'maildao'
				,'mail'
			),
			'tpl'		=> 1,	// InfraS add
			'triggers' => 1
		);
		$this->dirs = array('multismtp/sql');	// InfraS add
		// Config pages. Put here list of php page, stored into mymodule/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@multismtp");

		// Dependencies
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
        $opendsi_info = json_decode(file_get_contents(__DIR__.'/../../.opendsi_info.json'));
        $this->phpmin = explode('.', $opendsi_info->php_min_version);                    // Minimum version of PHP required by module
        $this->need_dolibarr_version = explode('.', $opendsi_info->dlb_min_version);    // Minimum version of Dolibarr required by module
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
			),
			array(
				0 => 'MULTISMTP_CRON_REFRESH_TOKEN_DAYS',
				1 => 'int',
				2 => '30',
				3 => 'Number of days before expiration to refresh OAuth2 tokens',
				4 => false,
				5 => 'current'
			)
		);

		// Cronjobs
		$this->cronjobs = array(
			0 => array(
				'label' => 'MultismtpCronRefreshOAuth2TokensLabel',
				'jobtype' => 'method',
				'class' => '/multismtp/class/Multismtp.class.php',
				'objectname' => 'Multismtp',
				'method' => 'cronRefreshOAuth2Tokens',
				'parameters' => '',
				'comment' => 'MultismtpCronRefreshOAuth2TokensComment',
				'frequency' => 1,
				'unitfrequency' => 86400,
				'status' => 0,
				'test' => 'isModEnabled("multismtp")',
				'priority' => 50,
			),
			1 => array(
				'label'         => 'Multismtp - Synchronisation utilisateurs SMTP2GO',
				'jobtype'       => 'method',
				'class'         => '/multismtp/class/multismtp_smtp2go.class.php',
				'objectname'    => 'MultiSMTP_Smtp2go',
				'method'        => 'cronSyncUsers',
				'parameters'    => '',
				'comment'       => 'Synchronise la table locale avec les utilisateurs SMTP existants sur SMTP2GO',
				'frequency'     => 1,
				'unitfrequency' => 3600 * 24,
				'status'        => 1,	// activée par défaut
				'test'          => 'isModEnabled("multismtp")',	// InfraS change : cohérence avec le job ci-dessus
				'priority'      => 55,
			),
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