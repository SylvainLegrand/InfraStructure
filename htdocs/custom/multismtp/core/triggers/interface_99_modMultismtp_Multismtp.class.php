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

/* Copyright (C) 2010 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2011 Laurent Destailleur <eldy@users.sourceforge.net>
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

/**
 * Class InterfaceMultismtp
 *
 * Handles Dolibarr trigger events
 */
class InterfaceMultismtp
{
	private $db;
	private $version = '1.0';

	/**
	 *   Constructor
	 *
	 *   @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = 'Multi SMTP';
		$this->family = "Opendsi";
		$this->description = "Triggers of this module allows to manage workflows";
		$this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
	}


	/**
	 *   Return name of trigger file
	 *
	 *   @return     string      Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *   Return description of trigger file
	 *
	 *   @return     string      Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 *   Return version of trigger file
	 *
	 *   @return     string      Version of trigger file
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') {
			return $langs->trans("Development");
		} elseif ($this->version == 'experimental') {
			return $langs->trans("Experimental");
		} elseif ($this->version == 'dolibarr') {
			return DOL_VERSION;
		} elseif ($this->version) {
			return $this->version;
		} else {
			return $langs->trans("Unknown");
		}
	}

	/**
	 *      Function called when a Dolibarrr business event is done.
	 *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 *      @param	string		$action		Event action code
	 *      @param  object		$object     Object
	 *      @param  User		$user       Object user
	 *      @param  Translate	$langs      Object langs
	 *      @param  conf		$conf       Object conf
	 *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $user, $db;

		$used_triggers = array(
			'COMPANY_SENTBYMAIL',
			'BILL_SENTBYMAIL',
			'BILL_SUPPLIER_SENTBYMAIL',
			'ORDER_SENTBYMAIL',
			'ORDER_SUPPLIER_SENTBYMAIL',
			'PROPAL_SENTBYMAIL',
			'PROPOSAL_SUPPLIER_SENTBYMAIL',
			'SUPPLIER_PROPOSAL_SENTBYMAIL',
			'SHIPPING_SENTBYMAIL',
			'RECEPTION_SENTBYMAIL',
			'FICHINTER_SENTBYMAIL',
			'USER_SENTBYMAIL',
			'MEMBER_SENTBYMAIL',
			'BOM_SENTBYMAIL',
			'CONTACT_SENTBYMAIL',
			'CONTRACT_SENTBYMAIL',
			'USER_UPDATE_SESSION'
		);

		if (!in_array($action, $used_triggers)) {
			return 1;
		}

		global $conf;

		require_once __DIR__ . '/../../class/Multismtp.class.php';
		require_once __DIR__ . '/../../lib/multismtp.php';

		$multismtp = new Multismtp($db, $conf);

		try {
			$multismtp->fetch($user);
		} catch (Exception $e) {
			dol_syslog('[multismtp] ' . $e->getMessage(), LOG_ERR);
			return 1;
		}

		if ($action != 'USER_UPDATE_SESSION') {
			if (!MultismtpImap::isEnabled()) {
				return 0;
			}

			//Object that contains the email sent
			global $mailfile;

			if (!$mailfile->mail_saved) {
				if (!$multismtp->saveMail($mailfile)) {
					setEventMessages($langs->trans('IMAPSaveError'), $multismtp->errors, 'warnings');
				} else {
					$mailfile->mail_saved = 1;
				}
			}
		} else {
			if (!getDolGlobalInt('MULTISMTP_SMTP_ENABLED')) { // InfraS change
				return 0;
			}

			//We do not want to replace constants when navigating to admin/mails.php page
			if (strpos('/admin/mails.php', $_SERVER['PHP_SELF']) !== false) {
				return 0;
			}

			if (!Multismtp::replaceConfiguration($db, $user)) {
				global $langs;

				$langs->load('multismtp@multismtp');

				setEventMessage($langs->trans('SMTPInjectionError'), 'errors');
			}

			return 1;
		}

		return 0;
	}
}
