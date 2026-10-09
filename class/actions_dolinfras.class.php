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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./dolinfras/class/actions_dolinfras.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook handler class for module DolInfraS
	*
	*	SKELETON: This class provides hook method examples.
	*	Add your hook contexts in the module descriptor (module_parts => hooks).
	*	Then implement the corresponding methods here.
	*	See https://wiki.dolibarr.org/index.php/Hooks
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/dolinfras/core/lib/dolinfrasAdmin.lib.php');

	/************************************************
	* Class Actionsdolinfras - Hook handler
	*
	************************************************/
	class Actionsdolinfras
	{
		/** @var DoliDB Database handler */
		public $db;
		/** @var array Hook results. Propagated to $hookmanager->resArray for later reuse */
		public $results = array();
		/** @var string String displayed by executeHook() immediately after return */
		public $resprints;
		/** @var array Errors */
		public $errors = array();

		/**
		* Constructor
		*
		* @param	DoliDB		$db		Database handler
		* @return	void
		*/
		public function __construct($db)
		{
			$this->db = $db;
		}

		/**
		* When login (../main.inc.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		public function afterLogin($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			dolinfras_getVersionDolinfras();
			$currentversion	= array();
			$currentversion	= dolinfras_getLocalVersionMinDoli('dolinfras');
			if (!getDolGlobalString('DOLINFRAS_DISABLE_CHECK_VERSION_MAX', '') && version_compare(DOL_VERSION, $currentversion[4], '>')) {
				setEventMessages($langs->trans('DolInfraSWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			return 0;
		}
	}