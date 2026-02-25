<?php
	/************************************************
	* Copyright (C) 2018-2020	Jeremie Ter-Heide - <jeremie@ter-heide.fr>
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		../infrasproject/class/actions_infrasproject.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraSProject
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	dol_include_once('/infrasproject/class/infrasproject.class.php');
	dol_include_once('/infrasproject/core/lib/infrasproject.lib.php');
	if (isModEnabled('contacttracking'))	dol_include_once('/contacttracking/class/contacttracking.class.php');

	/************************************************
	* Class ActionsInfrasproject
	************************************************/
	class ActionsInfrasproject
	{
		public $db;	// @var DoliDB Database handler.
		public $results = array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $errors = array();	// @var array Errors

		/**
		* Constructor
		*
		* @param	DATABASE	$db		db object
		* @return	void
		**/
		public function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		* After login (../main.inc.php)
		*
		* @param	array()			$parameters		empty array
		* @param	CommonObject	$user			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		function updateSession($parameters, $user, &$action)
		{
			global $user;

			$path_src	= preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','', $_SERVER['PHP_SELF']);
			if (!infrasproject_is_substitution_page($path_src)) {
				$url = infrasproject_get_substitution_url($path_src);
				if (!empty($url)) {
					$params	= http_build_query($_GET);
					header('Location: '.$url.(!empty($params) ? '?'.$params : ''));
					exit;
				}
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
			global $user;

			$path_src	= preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','', $_SERVER['PHP_SELF']);
			if (!infrasproject_is_substitution_page($path_src)) {
				$url = infrasproject_get_substitution_url($path_src);
				if (!empty($url)) {
					$params	= http_build_query($_GET);
					header('Location: '.$url.(!empty($params) ? '?'.$params : ''));
					exit;
				}
			}
			return 0;
		}

		/**
		* Execute action completeTabsHead
		*
		* @param	array			$parameters		Array of parameters
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		'add', 'update', 'view'
		* @param	Hookmanager		$hookmanager	hookmanager
		* @return	int								<0 if KO,
		*											=0 if OK but we want to process standard actions too,
		*											>0 if OK and we want to replace standard actions.
		**/
		function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
		{
			global $langs, $db;

			if ($object instanceof Project && in_array('fileslib', explode(':', $parameters['context']))) {
				$conso		= new InfraSProject($this->db);
				$obj		= $parameters['object'];
				$datacount	= $conso->countconso($obj);
				$langs->load('infrasproject@infrasproject');
				if ($datacount > 0) {
					$head	= $parameters['head'];
					foreach ($head as $key => $tab) {
						if ($tab[2] == 'conso') {
							$head[$key][1]	= $langs->trans('InfraSProjectStockConsumption').' <span class = "badge marginleftonlyshort">'.$datacount.'</span>';
							$this->results	= $head;
							return 1;
						}
					}
				}
			}
			return 0;
		}

		/**
		* When login (../main.inc.php)
		*
		* @param   array()         $parameters     Hook metadatas (context, etc...)
		* @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param   string          &$action        Current action (if set). Generally create or edit or null
		* @return  int                             < 0 on error
		**/
		public function completeListOfReferent($parameters, &$object, &$action)
		{
			global $conf, $langs, $user;

			$langs->load('infrasproject@infrasproject');

			$TContext	= explode(':', $parameters['context']);
			if (in_array('projectOverview', $TContext)) {
				$newreferent	= array('invoice_supplier_det'	=> array('name'			=> 'InfraSProjectLinesBillsSuppliers',
																		'title'			=> 'InfraSProjectListSupplierInvoicesLines',
																		'class'			=> 'Infrasprojectsupplierinvoiceline',
																		'margin'		=> 'minus',
																		'table'			=> 'facture_fourn_det',
																		'datefieldname'	=> '',
																		'lang'			=> 'infrasproject',
																		'test'			=> isModEnabled('supplier_invoice') && $user->hasRight('fournisseur','facture','lire')
																		),
										);
				$listofreferent	= $parameters['listofreferent'];
				$pos			= array_search('contract', array_keys($listofreferent));
				$this->results	= array_merge(array_slice($listofreferent, 0, $pos), $newreferent, array_slice($listofreferent, $pos));
				// Change rules for profit/benefit calculation
				if (getDolGlobalString('PROJECT_ELEMENTS_FOR_PLUS_MARGIN', '')) {
					foreach ($this->results as $key => $element) {
						if ($this->results[$key]['margin'] == 'add') {
							unset($this->results[$key]['margin']);
						}
					}
					$newelementforplusmargin	= explode(',', getDolGlobalString('PROJECT_ELEMENTS_FOR_PLUS_MARGIN', ''));
					foreach ($newelementforplusmargin as $value) {
						$this->results[trim($value)]['margin']	= 'add';
					}
				}
				if (getDolGlobalString('PROJECT_ELEMENTS_FOR_MINUS_MARGIN', '')) {
					foreach ($this->results as $key => $element) {
						if ($this->results[$key]['margin'] == 'minus') {
							unset($this->results[$key]['margin']);
						}
					}
					$newelementforminusmargin	= explode(',', getDolGlobalString('PROJECT_ELEMENTS_FOR_MINUS_MARGIN', ''));
					foreach ($newelementforminusmargin as $value) {
						$this->results[trim($value)]['margin']	= 'minus';
					}
				}
			}
			return 0;
		}

		/**
		* When we ask for an action (../element/card.php)
		*
		* @param   array()         $parameters     Hook metadatas (context, etc...)
		* @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param   string          &$action        Current action (if set). Generally create or edit or null
		* @return  int                             < 0 on error, 0 on success, 1 to replace standard code
		**/
		public function doActions($parameters, &$object, &$action)
		{
			global $user, $langs, $db;

			$TContext	= explode(':', $parameters['context']);
			if (in_array('invoicesuppliercard', $TContext)) {
				$langs->load('infrasproject@infrasproject');
				if ($action == 'classin') {
					foreach ($object->lines as $line) {
						if (!empty(infrasproject_printprj($line->id, 1))) {
							setEventMessage($langs->trans('InfraSProjectWarningLineWithProject'), 'warnings');
							return 1;
						}
					}
				}
			}
			// Enable add link to supplier invoice for users with only read rights on supplier invoices
			if (in_array('invoicesuppliercard', $TContext) && getDolGlobalInt('INFRASPROJECT_SHOW_MARGIN_PROV', 0) && in_array($action, array('addlink', 'addlinkbyref', 'dellink'))) {
				$id					= (GETPOSTINT('facid') ? GETPOSTINT('facid') : GETPOSTINT('id'));
				$permissiondellink	= $user->hasRight("fournisseur", "facture", "lire") || $user->hasRight("supplier_invoice", "lire");	// We change the permission $permissiondellink
				include_once DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';
				return 1;
			}
			return 0;
		}

		/**
		* When we show or edit object extrafields on main card (../core/tpl/extrafields_add+_edit+_view.tpl.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int + string					< 0 on error, 0 on success, 1 to replace standard code
		*											$this->resprints HTML code to show
		**/
		public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $langs;

			$langs->load('infrasproject@infrasproject');
			$TContext	= explode(':', $parameters['context']);
			if (in_array('projectcard', $TContext) && isModEnabled('contacttracking')) {
				if (class_exists('Contacttracking') && getDolGlobalInt('INFRASPROJECT_SHOW_LAST_EXCHANGE', 0)) {
					$comment	= '';
					$sql		= 'SELECT ct.rowid AS id FROM '.$this->db->prefix().'contacttracking AS ct';
					$sql		.= ' WHERE ct.entity = '.((int) $conf->entity).' AND ct.element_type LIKE "projet" AND ct.fk_element_id = '.((int) $object->id);
					$sql		.= ' ORDER BY ct.date_creation DESC LIMIT 1';
					$resql		= $this->db->query($sql);
					if (!empty($resql)) {
						$contactEx		= new Contacttracking($this->db);
						$objContactEx	= $this->db->fetch_object($resql);
						$contactEx->fetch($objContactEx->id);
						$comment		= $contactEx->comment;
						$this->db->free($resql);
					} else	dol_print_error($this->db);
					$this->resprints	.= '<tr>
												<td>'.$langs->trans('InfraSProjectLastContact').'</td>
												<td colspan = "3" class = "maxwidthonsmartphone">'.$comment.'</td>
											</tr>';
				}
				if (getDolGlobalInt('INFRASPROJECT_SHOW_NEXT_ACTION', 0)) {
					$action		= '';
					$sql		= 'SELECT ac.id FROM '.$this->db->prefix().'actioncomm AS ac';
					$sql		.= ' WHERE ac.entity = '.((int) $conf->entity).' AND ac.fk_project = '.((int) $object->id);
					$sql		.= ' ORDER BY ac.datep DESC LIMIT 1';
					$resql		= $this->db->query($sql);
					if (!empty($resql)) {
						$actionComm		= new ActionComm($this->db);
						$objActionComm	= $this->db->fetch_object($resql);
						$actionComm->fetch($objActionComm->id);
						$action			= $actionComm->note_private.' - '.dol_print_date($actionComm->datep, 'dayhourtextshort');
						$this->db->free($resql);
					} else	dol_print_error($this->db);
					$this->resprints	.= '<tr>
												<td>'.$langs->trans('InfraSProjectNextAction').'</td>
												<td colspan = "3" class = "maxwidthonsmartphone">'.$action.'</td>
											</tr>';
				}
			}
			return 0;
		}
	}