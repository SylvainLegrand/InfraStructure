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
	* 	\file		./infrascusprice/class/actions_infrascusprice.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraSCusPrice
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrascusprice/core/lib/infrascusprice.lib.php');
	dol_include_once('/infrascusprice/core/lib/infrascuspriceAdmin.lib.php');

	/************************************************
	* Class infrascusprice
	************************************************/
	class Actionsinfrascusprice
	{
		public $db;	// @var DoliDB Database handler.
		public $results = array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $errors = array();	// @var array Errors

		/**
		* Constructor
		*
		* @param	DATABASE		$db		db object
		* @return	void
		**/
		public function __construct($db)
		{
			$this->db	= $db;
		}	// public function __construct($db)

		/**
		* After login (../main.inc.php)
		*
		* @param	array()			$parameters		empty array
		* @param	CommonObject	$user			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			$action			Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		function updateSession($parameters, $user, $action)
		{
			$redirect_url	= infrascusp_getSubstitutionRedirectUrl();
			if (!empty($redirect_url)) {
				session_write_close();
				header('Location: '.$redirect_url);
				exit;
			}
			return 0; // or return 1 to replace standard code
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

			$langs->load('infrascusprice@infrascusprice');

			$currentversion	= array();
			$currentversion	= infrascusp_getLocalVersionMinDoli('infrascusprice');
			if (!getDolGlobalString('INFRASCUSPRICE_DISABLE_CHECK_VERSION_MAX', '') && version_compare(explode('.', DOL_VERSION)[0], explode('.', $currentversion[4])[0], '>')) {
				setEventMessages($langs->trans('InfraSCusPWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			$redirect_url	= infrascusp_getSubstitutionRedirectUrl();
			if (!empty($redirect_url)) {
				// Commit the DB transaction opened by main.inc.php (update_last_login_date + USER_LOGIN trigger)
				$this->db->commit();
				session_write_close();
				header('Location: '.$redirect_url);
				exit;
			}
			return 0;
		}

		/**
		* When we show or edit action button on main card
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	object			&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function addMoreActionsButtons($parameters, &$object, $action)
		{
			global $db, $conf, $langs, $user;

			$TContext	= explode(':', $parameters['context']);
			if (in_array('thirdpartycustomerprice', $TContext) && $user->hasRight('infrascusprice', 'paramUse')) {
				$langs->load('infrascusprice@infrascusprice');
				require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
				$soc			= new Societe($db);
				$soc->fetch($object->parent);
				$btnTitleSupr	= $langs->trans('InfraSCusPbtnSuprTitle', $soc->name);
				$linkSupr		= dol_escape_htmltag($_SERVER["PHP_SELF"]).'?socid='.$object->id.'&action=deleteCustPrices&token='.newToken().'&from=infrascusprice&socid='.$object->id;
				$btnTitleUpd	= $langs->trans('InfraSCusPbtn3thdTitle', $soc->name);
				$linkUpd		= dol_escape_htmltag($_SERVER["PHP_SELF"]).'?socid='.$object->id.'&action=updateCustPrices&token='.newToken().'&from=infrascusprice&idParent='.$object->parent;
				if ($object->parent){
					print '<div class = "inline-block divButAction"><a class = "butAction" href = "'.$linkSupr.'" title = "'.$btnTitleSupr.'">'.$langs->trans('InfraSCusPbtnSupr').'</a></div>';
					print '<div class = "inline-block divButAction"><a class = "butAction" href = "'.$linkUpd.'" title = "'.$btnTitleUpd.'">'.$langs->trans('InfraSCusPbtn3thd').'</a></div>';
				}
			}
			return 0;
		}

		/**
		* When we ask for an action
		*
		* @param	array()			$parameters	  Hook metadatas (context, etc...)
		* @param	CommonObject	 &$object		  The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			 &$action		  Current action (if set). Generally create or edit or null
		* @return  int									  < 0 on error, 0 on success, 1 to replace standard code
		**/
		public function doActions($parameters, &$object, $action)
		{
			global $user;

			$TContext	= explode(':', $parameters['context']);
			if (in_array('thirdpartycustomerprice', $TContext) && $user->hasRight('societe', 'creer') && $user->hasRight('infrascusprice', 'paramUse')) {
				if ($action == 'deleteCustPrices') {
					$result	= infrascusp_actions ('delete', GETPOST('socid', 'int'));
				} elseif ($action == 'updateCustPrices') {
					$result	= infrascusp_actions ('update', GETPOST('idParent', 'int'));
				}
				if ($result	< 0) {
					return -1;
				}
			}
			return 0;
		}
	}