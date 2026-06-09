<?php
	/************************************************
	* Copyright (C) 2018-2020	Jeremie Ter-Heide - <jeremie@ter-heide.fr>
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
	* 	\file		../infrasproject/class/actions_infrasproject.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraSProject
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	dol_include_once('/infrasproject/class/infrasproject.class.php');
	dol_include_once('/infrasproject/core/lib/infrasproject.lib.php');
	dol_include_once('/infrasproject/core/lib/infrasprojectAdmin.lib.php');
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
		function updateSession($parameters, $user, $action)
		{
			$redirect_url	= infrasproject_getSubstitutionRedirectUrl();
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

			$currentversion	= array();
			$currentversion	= infrasproject_getLocalVersionMinDoli('infrasproject');
			if (!getDolGlobalString('INFRASPROJECT_DISABLE_CHECK_VERSION_MAX', '') && version_compare(explode('.', DOL_VERSION)[0], explode('.', $currentversion[4])[0], '>')) {
				setEventMessages($langs->trans('InfraSProjectWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			$redirect_url	= infrasproject_getSubstitutionRedirectUrl();
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

		/**
		* Pick the versioned template suffix matching the current Dolibarr version
		*
		* @param	string		$mode	view|title|edit|create
		* @return	string				Absolute path to the template, or '' if not supported
		**/
		protected function infrasproject_pickLineTpl($mode)
		{
			$modeDir	= array('view' => 'lineviews', 'title' => 'linetitles', 'edit' => 'lineedits', 'create' => 'linecreates');
			if (!isset($modeDir[$mode])) {
				return '';
			}
			$major		= (int) DOL_VERSION;
			$dolinfras	= getDolGlobalString('EASYA_VERSION', '') || getDolGlobalString('DOLINFRAS_VERSION', '');
			if ($major >= 24) {
				$tplname	= 'v24.tpl.php';
			} elseif ($major == 23) {
				$tplname	= 'v23.tpl.php';
			} elseif ($major == 22) {
				$tplname	= $dolinfras ? 'v22-DolInfraS.tpl.php' : 'v22.tpl.php';
			} elseif ($major == 21) {
				$tplname	= 'v21.tpl.php';
			} else {
				return '';
			}
			$tpl		= dol_buildpath('/infrasproject/core/tpl/'.$modeDir[$mode].'/'.$tplname, 0);
			return file_exists($tpl) ? $tpl : '';
		}

		/**
		* When the form to add a new line is rendered (create mode)
		* Replaces Dolibarr's native objectline_create.tpl.php with our versioned variant.
		* Trampoline pattern: we call CommonObject::formAddObjectLine() with $defaulttpldir
		* pointing to our module so the native dispatcher loads our tpl with the proper scope.
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	$object			The object to process
		* @param	string			$action			Current action
		* @return	int								< 0 on error, 0 to continue with native tpl, 1 to replace standard code
		**/
		public function formAddObjectLine($parameters, &$object, &$action)
		{
			global $mysoc;

			if (empty($this->infrasproject_pickLineTpl('create'))) {
				return 0;
			}

			// Supplier-side objects flip the seller/buyer direction (mirrors core card.php logic)
			$isSupplier	= in_array($object->element, array('order_supplier', 'invoice_supplier', 'invoice_supplier_rec', 'supplier_proposal'), true);
			if ($isSupplier) {
				$seller	= is_object($object->thirdparty) ? $object->thirdparty : new Societe($this->db);
				$buyer	= $mysoc;
			} else {
				$seller	= $mysoc;
				$buyer	= is_object($object->thirdparty) ? $object->thirdparty : new Societe($this->db);
			}

			$object->formAddObjectLine(1, $seller, $buyer, '/infrasproject/core/tpl');
			return 1;
		}

		/**
		* When we show a line (view or edit mode for existing lines)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	$object			The object to process
		* @param	string			$action			Current action
		* @return	int								< 0 on error, 0 to continue, 1 to replace standard code
		**/
		public function printObjectLine($parameters, &$object, &$action)
		{
			global $conf, $db, $langs, $user, $hookmanager;
			global $form;
			global $object_rights, $disableedit, $disablemove, $disableremove;

			$line			= !empty($parameters['line'])			? $parameters['line']			: null;
			if (!is_object($line)) {
				return 0;
			}
			$num			= !empty($parameters['num'])			? $parameters['num']			: 0;
			$i				= isset($parameters['i'])				? $parameters['i']				: 0;
			$dateSelector	= !empty($parameters['dateSelector'])	? $parameters['dateSelector']	: 0;
			$seller			= !empty($parameters['seller'])			? $parameters['seller']			: null;
			$buyer			= !empty($parameters['buyer'])			? $parameters['buyer']			: null;
			$selected		= isset($parameters['selected'])		? $parameters['selected']		: 0;
			$extrafields	= !empty($parameters['extrafieldsline'])? $parameters['extrafieldsline']: null;
			$object_rights	= $object->getRights();

			// If infraspackplus is active, leave the whole view-mode rendering to it
			// (it includes the refproject column via its own partial). We only handle edit mode here.
			$isView			= ($action != 'editline' || $selected != $line->id);
			if ($isView && isModEnabled('infraspackplus')) {
				return 0;
			}
			// Special lines from other modules (infrastructure titles/sub-totals/free texts,
			// infrasdiscount lines, etc.) have their own dedicated rendering hook. Skip them
			// here to avoid emitting a duplicate <tr> alongside the module that owns the line.
			// Dolibarr convention: native special_codes are 0-3; > 3 means a module-owned line.
			if (!empty($line->special_code) && (int) $line->special_code > 3) {
				return 0;
			}

			$text			= '';
			$description	= '';
			if ($isView) {
				if (!empty($line->fk_product) && $line->fk_product > 0) {
					$product_static			= new Product($db);
					$product_static->fetch($line->fk_product);
					$product_static->ref	= $line->ref;
					$product_static->label	= !empty($line->label) ? $line->label : '';
					$text					= $product_static->getNomUrl(1);
					if (getDolGlobalInt('MAIN_MULTILANGS')) {
						if (property_exists($object, 'socid') && !empty($object->socid) && !is_object($object->thirdparty)) {
							dol_print_error(null, 'Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
							return 0;
						}
						$prod			= new Product($db);
						$prod->fetch($line->fk_product);
						$outputlangs	= $langs;
						$newlang		= '';
						if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
							$newlang	= GETPOST('lang_id', 'aZ09');
						}
						if (getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE') && empty($newlang) && is_object($object->thirdparty)) {
							$newlang	= $object->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs	= new Translate('', $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$label	= !empty($prod->multilangs[$outputlangs->defaultlang]['label']) ? $prod->multilangs[$outputlangs->defaultlang]['label'] : $line->product_label;
					} else {
						$label	= $line->product_label;
					}
					$text			.= ' - '.(!empty($line->label) ? $line->label : $label);
					$description	.= getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : (!empty($line->description) ? dol_htmlentitiesbr($line->description) : '');
				}
				if (empty($line->subprice_ttc) && $line->qty) {
					$line->subprice_ttc	= (float) price2num($line->total_ttc / $line->qty, 'MU');
				}
				$line->pu_ttc	= $line->subprice_ttc;
			} else {
				$label			= (!empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));
				$line->pu_ttc	= price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU');
			}

			$tpl	= $this->infrasproject_pickLineTpl($isView ? 'view' : 'edit');
			if (empty($tpl)) {
				return 0;
			}
			// Capture du rendu pour permettre l'enrichissement par d'autres modules avant émission.
			// Pattern « buffer + sous-hook » : les modules tiers (ex. infrastructure pour la colonne
			// « Opt ») retournent une cellule <td>…</td> via $hookmanager->resPrint dans le hook
			// dédié 'infrasprojectEnrichObjectLine', cellule qui est ensuite injectée juste avant
			// la cellule .linecolmove. Évite la double émission de <tr> lorsque plusieurs modules
			// implémentent printObjectLine — le HookManager ne s'arrête pas sur le 1er return 1.
			ob_start();
			$res	= empty($conf->file->strict_mode) ? @include $tpl : include $tpl;
			$content	= ob_get_clean();
			if (empty($res)) {
				if (!empty($content)) {
					print $content;
				}
				return 0;
			}
			$savedResPrint			= $hookmanager->resPrint;
			$hookmanager->resPrint	= '';
			$enrichParams			= $parameters;
			$hookmanager->executeHooks('infrasprojectEnrichObjectLine', $enrichParams, $object, $action);
			$enrichHtml				= (string) $hookmanager->resPrint;
			$hookmanager->resPrint	= $savedResPrint;
			if (!empty($enrichHtml)) {
				$injected	= preg_replace('/(<td\b[^>]*\bclass="[^"]*\blinecolmove\b[^"]*"[^>]*>)/i', $enrichHtml.'$1', $content, 1);
				if ($injected !== null && $injected !== $content) {
					$content	= $injected;
				}
			}
			print $content;
			return 1;
		}

		/**
		* When we show the title (header) row of the object lines table
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	$object			The object to process
		* @param	string			$action			Current action
		* @return	int								< 0 on error, 0 to continue, 1 to replace standard code
		**/
		public function printObjectLineTitle($parameters, &$object, &$action)
		{
			global $conf, $langs, $user, $hookmanager;
			global $form;
			global $disableedit;

			$num					= !empty($parameters['num'])			? $parameters['num']			: 0;
			$dateSelector			= !empty($parameters['dateSelector'])	? $parameters['dateSelector']	: 0;
			$seller					= !empty($parameters['seller'])			? $parameters['seller']			: null;
			$buyer					= !empty($parameters['buyer'])			? $parameters['buyer']			: null;
			$selected				= isset($parameters['selected'])		? $parameters['selected']		: 0;
			$inputalsopricewithtax	= !empty($GLOBALS['inputalsopricewithtax'])			? $GLOBALS['inputalsopricewithtax']			: 0;
			$outputalsopricetotalwithtax	= !empty($GLOBALS['outputalsopricetotalwithtax'])	? $GLOBALS['outputalsopricetotalwithtax']	: 0;
			$usemargins				= !empty($GLOBALS['usemargins'])				? $GLOBALS['usemargins']				: 0;

			$tpl	= $this->infrasproject_pickLineTpl('title');
			if (empty($tpl)) {
				return 0;
			}
			// Capture du rendu pour permettre l'enrichissement par d'autres modules avant émission.
			// Pattern « buffer + sous-hook » : les modules tiers (ex. infrastructure pour la colonne
			// « Opt ») retournent une cellule <th>…</th> via $hookmanager->resPrint dans le hook
			// dédié 'infrasprojectEnrichObjectLineTitle', cellule qui est ensuite injectée juste
			// avant la cellule .linecolmove. Évite la double émission de <thead> lorsque plusieurs
			// modules implémentent printObjectLineTitle — le HookManager ne s'arrête pas sur le
			// 1er return 1.
			ob_start();
			$res	= empty($conf->file->strict_mode) ? @include $tpl : include $tpl;
			$content	= ob_get_clean();
			if (empty($res)) {
				if (!empty($content)) {
					print $content;
				}
				return 0;
			}
			$savedResPrint			= $hookmanager->resPrint;
			$hookmanager->resPrint	= '';
			$enrichParams			= $parameters;
			$hookmanager->executeHooks('infrasprojectEnrichObjectLineTitle', $enrichParams, $object, $action);
			$enrichHtml				= (string) $hookmanager->resPrint;
			$hookmanager->resPrint	= $savedResPrint;
			if (!empty($enrichHtml)) {
				$injected	= preg_replace('/(<th\b[^>]*\bclass="[^"]*\blinecolmove\b[^"]*"[^>]*>)/i', $enrichHtml.'$1', $content, 1);
				if ($injected !== null && $injected !== $content) {
					$content	= $injected;
				}
			}
			print $content;
			return 1;
		}
	}