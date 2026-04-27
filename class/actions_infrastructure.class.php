<?php
	/**************************************************
	* Copyright (C) 2025 ATM Consulting <support@atm-consulting.fr>
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* along with this program. If not, see <https://www.gnu.org/licenses/>.
	*
	* SPDX-License-Identifier: GPL-3.0-or-later
	* This file is part of Dolibarr module Infrastructure
	**************************************************/

	/**************************************************
	* \file			infrastructure/class/actions_infrastructure.class.php
	* \ingroup		infrastructure
	* \brief		Hook actions for Infrastructure module
	*************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	dol_include_once('/infrastructure/class/infrastructure.class.php');
	dol_include_once('/infrastructure/core/lib/infrastructure.lib.php');
	dol_include_once('/infrastructure/backport/v19/core/class/commonhookactions.class.php');
	if (isModEnabled('ouvrage')) {
		dol_include_once('/ouvrage/class/ouvrage.class.php');
	}
	/**
	* Class ActionsInfrastructure
	*/
	class ActionsInfrastructure extends \infrastructure\RetroCompatCommonHookActions
	{
		public $db;											// @var DoliDB $db Database handler
		public $module_number = 550090;
		public $error;										// @var string $error
		public $errors = array();							// @var array $errors
		public $allow_move_block_lines;						// @var bool Allow move block lines
		protected $infrastructure_level_cur = 0;					// @var int Infrastructure current level
		protected $infrastructure_show_qty_by_default = false;	// @var bool Show infrastructure qty by default
		protected $infrastructure_sum_qty_enabled = false;		// @var bool Determine if sum on infrastructure qty is enabled
		protected $tfieldKeepWithNcCache = null;			// @var null|array cache local de INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC
		/**
		* Constructor
		*
		* @param DoliDB $db Database handler
		*/
		public function __construct($db)
		{
			global $langs;

			$langs->load('infrastructure@infrastructure');
			$this->db						= $db;
			$this->allow_move_block_lines	= true;
		}


		/**
		*	Cache du parent title d'une ligne (par rang).
		*	Évite de refaire un array_reverse + foreach complet à chaque appel de hook PDF.
		*
		*	@param	CommonObject	$object	Document
		*	@param	int				$rang	Rang de la ligne
		*	@return	bool|object				Ligne titre parente ou false
		**/
		protected function getCachedParentTitle(&$object, $rang)
		{
			if (!isset($object->context) || !is_array($object->context)) {
				$object->context	= array();
			}
			if (!isset($object->context['infrastructureCache']) || !is_array($object->context['infrastructureCache'])) {
				$object->context['infrastructureCache']	= array();
			}
			if (!isset($object->context['infrastructureCache']['parentTitleByRang']) || !is_array($object->context['infrastructureCache']['parentTitleByRang'])) {
				$object->context['infrastructureCache']['parentTitleByRang']	= array();
			}
			if (array_key_exists($rang, $object->context['infrastructureCache']['parentTitleByRang'])) {
				return $object->context['infrastructureCache']['parentTitleByRang'][$rang];
			}
			// Si le cache a été pré-chauffé (warmPDFInfrastructureCache), l'absence de clé => pas de parent
			if (!empty($object->context['infrastructureCache']['warmed'])) {
				$object->context['infrastructureCache']['parentTitleByRang'][$rang]	= false;
				return false;
			}
			$res	= TInfrastructure::getParentTitleOfLine($object, $rang, 0);
			$object->context['infrastructureCache']['parentTitleByRang'][$rang]	= $res;
			return $res;
		}

		/**
		*	Cache de la chaîne complète des titres englobants d'une ligne.
		*	Reproduit TInfrastructure::getAllTitleFromLine mais en O(1) après pré-chauffage.
		*	Fallback sur l'implémentation non mise en cache si le cache n'a pas été chauffé.
		*
		*	@param	CommonObject			$object		Document
		*	@param	CommonObjectLine		$line		Ligne
		*	@return	array								Titres englobants indexés par id
		**/
		protected function getCachedAllTitleFromLine(&$object, &$line)
		{
			if (empty($line) || !is_object($line) || !isset($line->rang)) {
				return array();
			}
			if (!isset($object->context) || !is_array($object->context)) {
				$object->context	= array();
			}
			if (!isset($object->context['infrastructureCache']) || !is_array($object->context['infrastructureCache'])) {
				$object->context['infrastructureCache']	= array();
			}
			if (isset($object->context['infrastructureCache']['allTitleChainByRang'][$line->rang])) {
				return $object->context['infrastructureCache']['allTitleChainByRang'][$line->rang];
			}
			if (!empty($object->context['infrastructureCache']['warmed'])) {
				// Cache chauffé mais rang absent => chaîne vide
				return array();
			}
			return TInfrastructure::getAllTitleFromLine($line);
		}

		/**
		*	Pré-chauffage du cache des titres parents pour toutes les lignes en un seul passage.
		*	Évite les O(n²) cumulés dans les hooks PDF (appels répétés de getParentTitleOfLine + getAllTitleFromLine).
		*	Utilise une pile de titres ouverts : title => push, infrastructure => pop (sémantique alignée sur getParentTitleOfLine avec $lvl=0).
		*
		*	@param	CommonObject	$object	Document
		*	@return	void
		**/
		protected function warmPDFInfrastructureCache(&$object)
		{
			if (empty($object->lines) || !is_array($object->lines)) {
				return;
			}
			if (!isset($object->context) || !is_array($object->context)) {
				$object->context	= array();
			}
			if (!isset($object->context['infrastructureCache']) || !is_array($object->context['infrastructureCache'])) {
				$object->context['infrastructureCache']	= array();
			}
			$parentByRang	= array();
			$chainByRang	= array();
			$openTitles		= array();	// pile de lignes titre ouvertes
			foreach ($object->lines as $line) {
				if (!is_object($line) || !isset($line->rang)) {
					continue;
				}
				// Parent du rang courant = sommet de pile avant traitement
				$parent		= !empty($openTitles) ? end($openTitles) : false;
				$parentByRang[$line->rang]	= $parent;
				// Chaîne complète = parent + chaîne du parent (indexée par id comme getAllTitleFromLine)
				if ($parent) {
					$parentChain	= isset($chainByRang[$parent->rang]) ? $chainByRang[$parent->rang] : array();
					$chain			= array($parent->id => $parent) + $parentChain;
				} else {
					$chain	= array();
				}
				$chainByRang[$line->rang]	= $chain;
				// Mise à jour de la pile pour la ligne suivante
				if (TInfrastructure::isTitle($line)) {
					$openTitles[]	= $line;
				} elseif (TInfrastructure::isInfrastructure($line)) {
					array_pop($openTitles);
				}
			}
			$object->context['infrastructureCache']['parentTitleByRang']		= $parentByRang;
			$object->context['infrastructureCache']['allTitleChainByRang']	= $chainByRang;
			$object->context['infrastructureCache']['warmed']					= true;
		}

		/**
		*	Cache du résultat de titleHasTotalLine pour une ligne titre.
		*
		*	@param	CommonObject	$object			Document
		*	@param	object			$title_line		Ligne titre
		*	@param	bool			$strict_mode	Mode strict
		*	@return	bool
		**/
		protected function getCachedTitleHasTotal(&$object, &$title_line, $strict_mode = false)
		{
			if (empty($title_line) || !is_object($title_line) || !isset($title_line->rang)) {
				return false;
			}
			if (!isset($object->context) || !is_array($object->context)) {
				$object->context	= array();
			}
			if (!isset($object->context['infrastructureCache']) || !is_array($object->context['infrastructureCache'])) {
				$object->context['infrastructureCache']	= array();
			}
			if (!isset($object->context['infrastructureCache']['titleHasTotalByKey']) || !is_array($object->context['infrastructureCache']['titleHasTotalByKey'])) {
				$object->context['infrastructureCache']['titleHasTotalByKey']	= array();
			}
			$key	= $title_line->rang.'_'.($strict_mode ? '1' : '0');
			if (array_key_exists($key, $object->context['infrastructureCache']['titleHasTotalByKey'])) {
				return $object->context['infrastructureCache']['titleHasTotalByKey'][$key];
			}
			$res	= TInfrastructure::titleHasTotalLine($object, $title_line, $strict_mode, false);
			$object->context['infrastructureCache']['titleHasTotalByKey'][$key]	= $res;
			return $res;
		}

		/**
		*	Cache local du tableau INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC (évite l'explode à chaque ligne).
		*
		*	@return	array
		**/
		protected function getNcTfieldKeepList()
		{
			if ($this->tfieldKeepWithNcCache === null) {
				$raw							= getDolGlobalString('INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC', '');
				$this->tfieldKeepWithNcCache	= ($raw === '') ? array() : explode(',', $raw);
			}
			return $this->tfieldKeepWithNcCache;
		}

		/**
		* Print field list select
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int								0 if OK, -1 if KO, 1 to replace standard code
		*/
		public function printFieldListSelect($parameters, &$object, &$action, $hookmanager)
		{

			global $type_element, $where;

			$contexts = explode(':', $parameters['context']);
			if (in_array('consumptionthirdparty', $contexts) && in_array($type_element, array('propal', 'order', 'invoice', 'supplier_order', 'supplier_invoice', 'supplier_proposal'))) {
				$mod_num = TInfrastructure::$module_number;
				$where	.= ' AND (d.special_code != '.$mod_num.' OR d.product_type != 9 OR d.qty > 9)';		// Not a title (can't use TInfrastructure class methods in sql)
				$where	.= ' AND (d.special_code != '.$mod_num.' OR d.product_type != 9 OR d.qty < 90)';	// Not a infrastructure (can't use TInfrastructure class methods in sql)
				$where	.= ' AND (d.special_code != '.$mod_num.' OR d.product_type != 9 OR d.qty != 50)';	// Not a free line text (can't use TInfrastructure class methods in sql)
			}
			return 0;
		}

		/**
		* Edit dictionary field list
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int								0 if OK, -1 if KO, 1 to replace standard code
		*/
		public function editDictionaryFieldlist($parameters, &$object, &$action, $hookmanager)
		{

			if ($parameters['tabname'] == $this->db->prefix().'c_infrastructure_free_text') {
				$value = TInfrastructure::getHtmlDictionnary();
				?>
				<script type="text/javascript">
					$(function () {
						if ($('input[name=content]').length > 0) {
							$('input[name=content]').each(function (i, item) {
								var value = '';
								// Le dernier item correspond à l'édition
								if (i == $('input[name=content]').length - 1) {
									value = <?php echo json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
								}
								$(item).replaceWith($('<textarea name="content">' + value + '</textarea>'));
							});
							<?php if (isModEnabled('fckeditor') && getDolGlobalString('FCKEDITOR_ENABLE_DETAILS')) { ?>
								$('textarea[name=content]').each(function(i, item) {
									CKEDITOR.replace(item, {
										toolbar: 'dolibarr_notes',
										customConfig: ckeditorConfig,
										versionCheck: false
									});
								});
							<?php } ?>
						}
					});
				</script>
				<?php
			}
			return 0;
		}

		/**
		* Create dictionary field list
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int								0 if OK, -1 if KO, 1 to replace standard code
		*/
		public function createDictionaryFieldlist($parameters, &$object, &$action, $hookmanager)
		{
			global $conf;

			if ($parameters['tabname'] == $this->db->prefix().'c_infrastructure_free_text') {
				// Editor wysiwyg
				$toolbarname		= 'dolibarr_notes';
				$disallowAnyContent	= true;
				if (getDolGlobalString('FCKEDITOR_ALLOW_ANY_CONTENT')) {
					$disallowAnyContent	= !getDolGlobalString('FCKEDITOR_ALLOW_ANY_CONTENT'); // Only predefined list of html tags are allowed or all
				}
				if (getDolGlobalString('FCKEDITOR_SKIN')) {
					$skin = getDolGlobalString('FCKEDITOR_SKIN');
				} else {
					$skin = 'moono-lisa'; // default with ckeditor 4.6 : moono-lisa
				}
				if (getDolGlobalString('FCKEDITOR_ENABLE_SCAYT_AUTOSTARTUP')) {
					$scaytautostartup = 'scayt_autoStartup: true,';
				} else {
					$scaytautostartup = '/*scayt is disable*/'; // Disable by default
				}
				$htmlencode_force		= preg_match('/_encoded$/', $toolbarname) ? 'true' : 'false';
				$editor_height			= getDolGlobalString('MAIN_DOLEDITOR_HEIGHT', 100);
				$editor_allowContent	= $disallowAnyContent ? 'false' : 'true';
				$value = TInfrastructure::getHtmlDictionnary();
				?>
				<script type="text/javascript">
					$(function () {
						if ($('input[name=content]').length > 0) {
							$('input[name=content]').each(function (i, item) {
								var value = '';
								// Le dernier item correspond à l'édition
								if (i == $('input[name=content]').length - 1) {
									value = <?php echo json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
								}
								$(item).replaceWith($('<textarea name="content">' + value + '</textarea>'));
							});

							<?php if (isModEnabled("fckeditor") && getDolGlobalString('FCKEDITOR_ENABLE_DETAILS')) { ?>
							$('textarea[name=content]').each(function (i, item) {
								CKEDITOR.replace(item, {
									toolbar: 'dolibarr_notes',
									customConfig: ckeditorConfig,
									versionCheck: false
								});
							});
							<?php } ?>
						}
					});
				</script>
				<?php
			}
			return 0;
		}

		/** 
		* Overloading the formObjectOptions function : replacing the parent's function with the one below
		*
		* @param 	array			$parameters  array           meta datas of the hook (context, etc...)
		* @param 	CommonObject	$object      CommonObject    the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param 	string			$action      string          current action (if set). Generally create or edit or null
		* @param 	HookManager 	$hookmanager HookManager     current hook manager
		* @return	int
		*/
		public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
		{
			global $langs,$db,$user, $conf;

			$langs->load('infrastructure@infrastructure');
			$contexts			= explode(':', $parameters['context']);
			if (in_array('ordercard', $contexts) || in_array('ordersuppliercard', $contexts) || in_array('propalcard', $contexts) || in_array('supplier_proposalcard', $contexts) || in_array('invoicecard', $contexts) || in_array('invoicesuppliercard', $contexts) || in_array('invoicereccard', $contexts) || in_array('expeditioncard', $contexts)) {
				$createRight	= $user->hasRight($object->element, 'creer');
				if ($object->element == 'facturerec') {
					$object->statut = 0; // hack for facture rec
					$createRight = $user->hasRight('facture', 'creer');
				} elseif ($object->element == 'order_supplier') {
					$createRight = $user->hasRight('fournisseur', 'commande', 'creer');
				} elseif ($object->element == 'invoice_supplier') {
					$createRight = $user->hasRight('fournisseur', 'facture', 'creer');
				} elseif ($object->element == 'shipping') {
					$createRight = true; // No rights management for shipments
				}
				if ($object->statut == 0 && $createRight) {
					$idvar		= $object->element == 'facture' ? 'facid' : 'id';
					if (in_array($action, array('add_title_line', 'add_total_line', 'add_subtitle_line', 'add_infrastructure_line', 'add_free_text'))) {
						$level	= GETPOST('level', 'int'); //New avec INFRASTRUCTURE_USE_NEW_FORMAT
						if ($action == 'add_title_line') {
							$title	= !empty(GETPOST('title', 'restricthtml')) ? GETPOST('title', 'restricthtml') : $langs->trans('InfrastructureTitle');
							$qty	= $level < 1 ? 1 : $level ;
						} elseif ($action=='add_free_text') {
							$title	= GETPOST('title', 'restricthtml');
							if (empty($title)) {
								$free_text		= GETPOST('free_text', 'int');
								if (!empty($free_text)) {
									$TFreeText	= infrastructure_getTFreeText();
									if (!empty($TFreeText[$free_text])) {
										$title	= $TFreeText[$free_text]->content;
									}
								}
							}
							$title	= !empty($title) ? $title : $langs->trans('InfrastructureAddLineDescription');
							$qty	= 50;
						} elseif ($action == 'add_subtitle_line') {
							$title	= !empty(GETPOST('title', 'restricthtml')) ? GETPOST('title', 'restricthtml') : $langs->trans('InfrastructureSubtitle');
							$qty	= 2;
						} elseif ($action == 'add_infrastructure_line') {
							$title	= $langs->trans('SubInfrastructure');
							$qty	= 98;
						} else {
							$title	= !empty(GETPOST('title', 'restricthtml')) ? GETPOST('title', 'restricthtml') : $langs->trans('Infrastructure');
							$qty	= $level ? 100 - $level : 99;
						}
						if (getDolGlobalString('INFRASTRUCTURE_AUTO_ADD_INFRASTRUCTURE_ON_ADDING_NEW_TITLE') && $qty < 10) {
							TInfrastructure::addInfrastructureMissing($object, $qty);
						}
						if (getDolGlobalInt('MAIN_VIEW_LINE_NUMBER') == 1) {
							$rang		= GETPOST('rank', 'int') ? (int)GETPOST('rank', 'int') : '-1';
							$newlineid	= TInfrastructure::addInfrastructureLine($object, $title, $qty, $rang);
							print '<div id="newlineid">'.$newlineid.'</div>';
						} else {
							TInfrastructure::addInfrastructureLine($object, $title, $qty);
						}
					} elseif ($action==='ask_deleteallline') {
						$form			= new Form($db);
						$lineid			= GETPOST('lineid', 'int');
						$TIdForGroup	= TInfrastructure::getLinesFromTitleId($object, $lineid, true);
						$nbLines		= count($TIdForGroup);
						$formconfirm	= $form->formconfirm(dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$object->id.'&lineid='.$lineid, $langs->trans('InfrastructureDeleteWithAllLines'), $langs->trans('InfrastructureConfirmDeleteAllThisLines', $nbLines), 'confirm_delete_all_lines', '', 0, 1);
						print $formconfirm;
					}
					if (getDolGlobalString('INFRASTRUCTURE_ALLOW_ADD_LINE_UNDER_TITLE')) {
						infrastructure_showSelectTitleToAdd($object);
					}
					if ($object->element != 'shipping' && $action != 'editline') {
						infrastructure_printNewFormat($object, $conf, $langs, $idvar);
					}
				}
			} elseif ((!empty($parameters['currentcontext']) && $parameters['currentcontext'] == 'orderstoinvoice') || in_array('orderstoinvoice', $contexts) || in_array('orderstoinvoicesupplier', $contexts)) {
				infrastructure_billOrdersAddCheckBoxForTitleBlocks();
			}
			return 0;
		}


		/**
		* Form build doc options
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object		Object
		* @return	int
		*/
		public function formBuilddocOptions($parameters, &$object)
		{

			global $langs;

			$action			= GETPOST('action', 'aZ09');
			$contextArray	= explode(':', $parameters['context']);
			if (!getDolGlobalString('INFRASTRUCTURE_HIDE_OPTIONS_BUILD_DOC') && (in_array('invoicecard', $contextArray) || in_array('invoicesuppliercard', $contextArray) || in_array('propalcard', $contextArray) || in_array('ordercard', $contextArray) || in_array('ordersuppliercard', $contextArray) || in_array('invoicereccard', $contextArray))) {
				$hideInnerLines			= isset($_SESSION['infrastructure_hideInnerLines_'.$parameters['modulepart']][$object->id]) ?  $_SESSION['infrastructure_hideInnerLines_'.$parameters['modulepart']][$object->id] : 0;
				$hidesubdetails			= isset($_SESSION['infrastructure_hidesubdetails_'.$parameters['modulepart']][$object->id]) ?  $_SESSION['infrastructure_hidesubdetails_'.$parameters['modulepart']][$object->id] : 0;
				$hidepricesDefaultConf	= getDolGlobalString('INFRASTRUCTURE_HIDE_PRICE_DEFAULT_CHECKED')?getDolGlobalString('INFRASTRUCTURE_HIDE_PRICE_DEFAULT_CHECKED') :0;
				$hideprices				= !empty($_SESSION['infrastructure_hideprices_'.$parameters['modulepart']][$object->id]) ?  $_SESSION['infrastructure_hideprices_'.$parameters['modulepart']][$object->id] : $hidepricesDefaultConf;
				$titleOptions			= $langs->trans('InfrastructureOptions').'&nbsp;&nbsp;&nbsp;'.img_picto($langs->trans('Setup'), 'setup', 'style="vertical-align: bottom; height: 20px;"');
				$titleStyle				= 'background: transparent !important; background-color: rgba(148, 148, 148, .065) !important; cursor: pointer;';
				$out					= '';
				$out	.= '	<tr class = "infrastructurefold" style = "'.$titleStyle.'"><td colspan = "6" align = "center" style = "font-size: 120%;">'.$titleOptions.'</td></tr>
								<tr class = "oddeven infrastructurefoldable">
									<td colspan = "6" class = "right">
										<label for = "hideInnerLines">'.$langs->trans('InfrastructureHideInnerLines').'</label>
										<input type = "checkbox" onclick="if($(this).is(\':checked\')) { $(\'#hidesubdetails\').prop(\'checked\', \'checked\')  }" id = "hideInnerLines" name = "hideInnerLines" value = "1" '.(!empty($hideInnerLines) ? 'checked = "checked"' : '').' />
									</td>
								</tr>
								<tr class = "oddeven infrastructurefoldable">
									<td colspan = "6" class = "right">
										<label for = "hidesubdetails">'.$langs->trans('InfrastructureHideDetails').'</label>
										<input type = "checkbox" id = "hidesubdetails" name = "hidesubdetails" value = "1" '.(!empty($hidesubdetails) ? 'checked = "checked"' : '').' />
									</td>
								</tr>
								<tr class = "oddeven infrastructurefoldable">
									<td colspan = "6" class = "right">
										<label for = "hideprices">'.$langs->trans('InfrastructureHidePrice').'</label>
										<input type = "checkbox" id = "hideprices" name = "hideprices" value = "1" '.(!empty($hideprices) ? 'checked = "checked"' : '').' />
									</td>
								</tr>';
				if ((in_array('propalcard', $contextArray) && getDolGlobalString('INFRASTRUCTURE_PROPAL_ADD_RECAP')) || (in_array('ordercard', $contextArray) && getDolGlobalString('INFRASTRUCTURE_COMMANDE_ADD_RECAP')) || (in_array('ordersuppliercard', $contextArray) && getDolGlobalString('INFRASTRUCTURE_COMMANDE_ADD_RECAP')) || (in_array('invoicecard', $contextArray) && getDolGlobalString('INFRASTRUCTURE_INVOICE_ADD_RECAP')) || (in_array('invoicesuppliercard', $contextArray) && getDolGlobalString('INFRASTRUCTURE_INVOICE_ADD_RECAP')) || (in_array('invoicereccard', $contextArray) && getDolGlobalString('INFRASTRUCTURE_INVOICE_ADD_RECAP'))) {
					$out	.= '<tr class = "oddeven infrastructurefoldable">
									<td colspan = "6" class = "right">
										<label for = "infrastructure_add_recap">'.$langs->trans('InfrastructureAddRecap').'</label>
										<input type = "checkbox" id = "infrastructure_add_recap" name = "infrastructure_add_recap" value = "1" '.(!empty(GETPOST('infrastructure_add_recap', 'int')) ? 'checked = "checked"' : '').'/>
									</td>
								</tr>';
				}
				$out	.= '	<script type = "text/javascript">
									$(document).ready(function(){
										$(".infrastructurefoldable").hide();
									});
									$(".infrastructurefold").click(function (){
										$(".infrastructurefoldable").toggle();
									});
								</script>';
				$this->resprints = $out;
			}
			return 0;
		}
		/**
		* ODT substitution line
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int
		*/
		public function ODTSubstitutionLine(&$parameters, &$object, $action, $hookmanager)
		{
			global $conf;

			if (in_array($action, array('builddoc', 'addline', 'confirm_valid', 'confirm_paiement'))) {
				$line												= &$parameters['line'];
				$object												= &$parameters['object'];
				$substitutionarray									= &$parameters['substitutionarray'];
				$substitutionarray['line_not_modinfrastructure']	= true;
				$substitutionarray['line_modinfrastructure']		= false;
				$substitutionarray['line_modinfrastructure_total']	= false;
				$substitutionarray['line_modinfrastructure_title']	= false;
				if ($line->product_type == 9 && $line->special_code == $this->module_number) {
					$substitutionarray['line_modinfrastructure']	= 1;
					$substitutionarray['line_not_modinfrastructure']= false;
					$substitutionarray['line_price_ht']			= $substitutionarray['line_price_vat']
																= $substitutionarray['line_price_ttc']
																= $substitutionarray['line_vatrate']
																= $substitutionarray['line_qty']
																= $substitutionarray['line_up']
																= '';
					if ($line->qty > 90) {
						$substitutionarray['line_modinfrastructure_total'] = true;
						$TInfo									= infrastructure_get_totalLineFromObject($object, $line, 0, 1);
						$substitutionarray['line_price_ht']		= price($TInfo[0], 0, '', 1, 0, getDolGlobalString('MAIN_MAX_DECIMALS_TOT'));
						$substitutionarray['line_price_vat']	= price($TInfo[1], 0, '', 1, 0, getDolGlobalString('MAIN_MAX_DECIMALS_TOT'));
						$substitutionarray['line_price_ttc']	= price($TInfo[2], 0, '', 1, 0, getDolGlobalString('MAIN_MAX_DECIMALS_TOT'));
					} else {
						$substitutionarray['line_modinfrastructure_title'] = true;
					}
				} else {
					$substitutionarray['line_not_modinfrastructure']	= true;
					$substitutionarray['line_modinfrastructure']		= 0;
				}
			}
			return 0;
		}

		/**
		* Do actions
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return int|void
		*/
		public function doActions($parameters, &$object, $action, $hookmanager)
		{
			global $db, $conf, $langs, $user, $hidesubdetails, $hideprices;

			$contextArray	= array();
			if (isset($parameters['context'])) {
				$contextArray = explode(':', $parameters['context']);
			}
			$showBlockExtrafields	= GETPOST('showBlockExtrafields', 'aZ09');
			$idvar					= isset($object->element) && $object->element == 'facture' ? 'facid' : 'id';
			if (in_array($action, array('updateligne', 'updateline'))) {
				$found	= false;
				$lineid	= GETPOST('lineid', 'int');
				foreach ($object->lines as &$line) {
					if ($line->id == $lineid && TInfrastructure::isModInfrastructureLine($line)) {
						$found	= true;
						if (TInfrastructure::isTitle($line) && !empty($showBlockExtrafields)) {
							$extrafieldsline	= new ExtraFields($db);
							$extralabelsline	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
							$extrafieldsline->setOptionalsFromPost($extralabelsline, $line);
						}
						infrastructure_updateInfrastructureLine($object, $line);
						infrastructure_updateInfrastructureBloc($object, $line);
						TInfrastructure::generateDoc($object);
						break;
					}
				}
				if ($found) {
					$urlSelf	= preg_replace('#(\.php).*$#', '$1', $_SERVER['PHP_SELF']);
					header('Location: '.$urlSelf.'?'.$idvar.'='.((int) $object->id));
					exit; // Surtout ne pas laisser Dolibarr faire du traitement sur le updateligne sinon ça plante les données de la ligne
				}
			} elseif ($action === 'builddoc') {
				if (in_array('invoicecard', $contextArray)
					|| in_array('propalcard', $contextArray)
					|| in_array('ordercard', $contextArray)
					|| in_array('ordersuppliercard', $contextArray)
					|| in_array('invoicesuppliercard', $contextArray)
					|| in_array('supplier_proposalcard', $contextArray)
				) {
					$TSessNames		= infrastructure_getSessionNames($contextArray);
					$sessname		= $TSessNames['hideInnerLines'];
					$sessname2		= $TSessNames['hidesubdetails'];
					$sessname3		= $TSessNames['hideprices'];
					$hideInnerLines	= GETPOST('hideInnerLines', 'int');
					if (!array_key_exists($sessname, $_SESSION) || empty($_SESSION[$sessname]) || !is_array($_SESSION[$sessname]) || !isset($_SESSION[$sessname][$object->id])) {
						$_SESSION[$sessname]			= array($object->id => 0); // prevent old system
					}
					$_SESSION[$sessname][$object->id]	= $hideInnerLines;
					$hidesubdetails						= GETPOST('hidesubdetails', 'int');
					if (!array_key_exists($sessname2, $_SESSION) || empty($_SESSION[$sessname2]) || !is_array($_SESSION[$sessname2]) || !isset($_SESSION[$sessname2][$object->id])) {
						$_SESSION[$sessname2]			= array($object->id => 0); // prevent old system
					}
					$_SESSION[$sessname2][$object->id]	= $hidesubdetails;
					$hideprices							= GETPOST('hideprices', 'int');
					if (!array_key_exists($sessname3, $_SESSION) || empty($_SESSION[$sessname3]) || !is_array($_SESSION[$sessname3]) || !isset($_SESSION[$sessname3][$object->id])) {
						$_SESSION[$sessname3]			= array($object->id => 0); // prevent old system
					}
					$_SESSION[$sessname3][$object->id]	= $hideprices;
					foreach ($object->lines as &$line) {
						if ($line->product_type == 9 && $line->special_code == $this->module_number) {
							if ($line->qty >= 90) {
								$line->modinfrastructure_total = 1;
							} else {
								$line->modinfrastructure_title = 1;
							}
							$line->total_ht = infrastructure_get_totalLineFromObject($object, $line, false);
						}
					}
				}
			} else if ($action === 'confirm_delete_all_lines' && GETPOST('confirm', 'alpha') == 'yes') {
				$error	= 0;
				$Tab	= TInfrastructure::getLinesFromTitleId($object, GETPOST('lineid', 'int'), true);
				foreach ($Tab as $line) {
					$result = 0;
					if (!empty(isModEnabled('ouvrage')) && class_exists('Ouvrage') && Ouvrage::isOuvrage($line)) {
						// Call trigger
						$interface			= new Interfaces($db);
						$result				= $interface->run_triggers('OUVRAGE_DELETE', $line, $user, $langs, $conf);
						if ($result < 0) {
							$error++;
						}
						// End call triggers
					}
					$idLine		= $line->id;
					if ($object->element == 'facture') {
						$result = $object->deleteline($idLine);
					} elseif ($object->element == 'invoice_supplier') {
						$result = $object->deleteline($idLine);
					} elseif ($object->element == 'propal') {
						$result = $object->deleteline($idLine);
					} elseif ($object->element == 'supplier_proposal') {
						$result = $object->deleteline($idLine);
					} elseif ($object->element == 'commande') {
						$result = $object->deleteline($user, $idLine);
					} elseif ($object->element == 'order_supplier') {
						$result = $object->deleteline($idLine);
					} elseif ($object->element == 'facturerec') {
						$result = $object->deleteline($idLine);
					} elseif ($object->element == 'shipping') {
						$result = $object->deleteline($user, $idLine);
					}
					if ($result < 0) {
						$error++;
					}
				}
				if ($error > 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$db->rollback();
				} else {
					$db->commit();
				}
				header('location:?id='.$object->id);
				exit;
			} elseif ($action == 'duplicate') {
				$lineid			= GETPOST('lineid', 'int');
				$nbDuplicate	= TInfrastructure::duplicateLines($object, $lineid, true);
				if ($nbDuplicate > 0) {
					setEventMessage($langs->trans('InfrastructureDuplicateSuccess', $nbDuplicate));
				} elseif ($nbDuplicate == 0) {
					setEventMessage($langs->trans('InfrastructureDuplicateLineidNotFound'), 'warnings');
				} else {
					setEventMessage($langs->trans('InfrastructureDuplicateError'), 'errors');
				}
				header('Location: ?id='.$object->id);
				exit;
			} elseif ((!empty($parameters['currentcontext']) && $parameters['currentcontext'] == 'orderstoinvoice') || in_array('orderstoinvoice', $contextArray) || in_array('orderstoinvoicesupplier', $contextArray) || in_array('orderlist', $contextArray)) {
				infrastructure_billOrdersAddCheckBoxForTitleBlocks();
			} else {
				// when automatic generate is enabled : keep last selected options from last "builddoc" action (ganerate document manually)
				if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
					if (in_array('invoicecard', $contextArray) || in_array('propalcard', $contextArray) || in_array('ordercard', $contextArray) || in_array('ordersuppliercard', $contextArray) || in_array('invoicesuppliercard', $contextArray) || in_array('supplier_proposalcard', $contextArray)) {
						$confirm	= GETPOST('confirm', 'alpha');
						if (in_array($action, array('modif', 'reopen')) || (in_array($action, array('confirm_modif', 'confirm_edit', 'confirm_validate', 'confirm_valid')) && $confirm == 'yes')) {
							$TSessNames	= infrastructure_getSessionNames($contextArray);
							$sessname	= $TSessNames['hideInnerLines'];
							$sessname2	= $TSessNames['hidesubdetails'];
							$sessname3	= $TSessNames['hideprices'];
							if (GETPOSTISSET('hideInnerLines')) {
								$hideInnerLines = GETPOST('hideInnerLines', 'int');
							} else {
								$hideInnerLines = isset($_SESSION[$sessname][$object->id]) ? $_SESSION[$sessname][$object->id] : 0;
							}
							$_POST['hideInnerLines'] = $hideInnerLines;
							if (GETPOSTISSET('hidesubdetails')) {
								$hidesubdetails = GETPOST('hidesubdetails', 'int');
							} else {
								$hidesubdetails = isset($_SESSION[$sessname2][$object->id]) ? $_SESSION[$sessname2][$object->id] : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0);
							}
							// no need to set POST value (it's a global value used in global card)
							if (GETPOSTISSET('hideprices')) {
								$hideprices	 = GETPOST('hideprices', 'int');
							} else {
								$hidepricesDefaultConf	= getDolGlobalString('INFRASTRUCTURE_HIDE_PRICE_DEFAULT_CHECKED') ? getDolGlobalString('INFRASTRUCTURE_HIDE_PRICE_DEFAULT_CHECKED') : 0;
								$hideprices				= isset($_SESSION[$sessname3][$object->id]) ? $_SESSION[$sessname3][$object->id] : $hidepricesDefaultConf;
							}
							// no need to set POST value (it's a global value used in this module)
						}
					}
				}
			}
			return 0;
		}

		/**
		* Change rounding mode
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int
		*/
		public function changeRoundingMode($parameters, &$object, &$action, $hookmanager)
		{
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && !empty($object->table_element_line) && in_array($object->element, array('commande', 'facture', 'propal'))) {
				if ($object->element == 'commande')
					$obj = new OrderLine($object->db);
				if ($object->element == 'propal')
					$obj = new PropaleLigne($object->db);
				if ($object->element == 'facture')
					$obj = new FactureLigne($object->db);
				if (!empty($parameters['fk_element'])) {
					if ($obj->fetch($parameters['fk_element'])) {
						$obj->id= $obj->rowid;
						if (empty($obj->array_options))
							$obj->fetch_optionals();
						if (!empty($obj->array_options['options_infrastructure_nc']))
							return 1;
					}
				}
			}

			return 0;
		}

		/**
		* PDF add total
		*
		* @param	TCPDF|ModelePDFStatic	$pdf		PDF object
		* @param	CommonObject			$object 	Object
		* @param	CommonObjectLine		$line 		Line
		* @param	string					$label		Label
		* @param	string					$description Description
		* @param	float					$posx 		Position X
		* @param	float					$posy 		Position Y
		* @param	float					$w 			Width
		* @param	float					$h 			Height
		* @return	void
		*/
		public function pdfAddTotal(&$pdf, &$object, &$line, $label, $description, $posx, $posy, $w, $h)
		{
			global $conf, $infrastructure_last_title_posy, $langs;

			$infrastructureDefaultTopPadding			= 1;
			$infrastructureDefaultBottomPadding		= 1;
			$infrastructureDefaultLeftPadding			= 0.5;
			$infrastructureDefaultRightPadding		= 0.5;
			$use_multicurrency					= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;
			empty($pdf->page_largeur) ? $pdf->page_largeur = 0 : '';
			empty($pdf->marge_droite) ? $pdf->marge_droite = 0 : '';
			empty($line->total) ? $line->total = 0 : '';
			empty($pdf->postotalht) ? $pdf->postotalht = 0 : '';
			$bgStyle							= infrastructure_getPdfBackgroundStyle($pdf, 'INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR', 'INFRASTRUCTURE_BACKGROUND_CELL_HEIGHT_OFFSET', 'INFRASTRUCTURE_BACKGROUND_CELL_POS_Y_OFFSET');
			$fillBackground						= $bgStyle['fill'];
			$backgroundColor					= $bgStyle['color'];
			$backgroundCellHeightOffset			= $bgStyle['heightOffset'];
			$backgroundCellPosYOffset			= $bgStyle['posYOffset'];
			// POUR LES PDF DE TYPE PDF_EVOLUTION (ceux avec les colonnes configurables)
			$pdfModelUseColSystem				= !empty($object->context['infrastructurePdfModelInfo']->cols); // justilise une variable au cas ou le test evolue
			if ($pdfModelUseColSystem) {
				include_once __DIR__.'/staticPdf.model.php';
				$staticPdfModel					= new ModelePDFStatic($object->db);
				$staticPdfModel->marge_droite	= $object->context['infrastructurePdfModelInfo']->marge_droite;
				$staticPdfModel->marge_gauche	= $object->context['infrastructurePdfModelInfo']->marge_gauche;
				$staticPdfModel->page_largeur	= $object->context['infrastructurePdfModelInfo']->page_largeur;
				$staticPdfModel->page_hauteur	= $object->context['infrastructurePdfModelInfo']->page_hauteur;
				$staticPdfModel->cols			= $object->context['infrastructurePdfModelInfo']->cols;
				if (property_exists($object->context['infrastructurePdfModelInfo'], 'defaultTitlesFieldsStyle')) {
					$staticPdfModel->defaultTitlesFieldsStyle	= $object->context['infrastructurePdfModelInfo']->defaultTitlesFieldsStyle;
				}
				if (property_exists($object->context['infrastructurePdfModelInfo'], 'defaultContentsFieldsStyle')) {
					$staticPdfModel->defaultContentsFieldsStyle	= $object->context['infrastructurePdfModelInfo']->defaultContentsFieldsStyle;
				}
				$staticPdfModel->prepareArrayColumnField($object, $langs);
				if (isset($staticPdfModel->cols['totalexcltax']['content']['padding'][0])) {
					$infrastructureDefaultTopPadding		= $staticPdfModel->cols['totalexcltax']['content']['padding'][0];
				}
				if (isset($staticPdfModel->cols['totalexcltax']['content']['padding'][2])) {
					$infrastructureDefaultBottomPadding	= $staticPdfModel->cols['totalexcltax']['content']['padding'][0];
				}

				if (isset($staticPdfModel->cols['totalincltax']['content']['padding'][0])) {
					$infrastructureDefaultTopPadding		= $staticPdfModel->cols['totalincltax']['content']['padding'][0];
				}
				if (isset($staticPdfModel->cols['totalincltax']['content']['padding'][2])) {
					$infrastructureDefaultBottomPadding	= $staticPdfModel->cols['totalincltax']['content']['padding'][0];
				}
			}
			$hideInnerLines	= GETPOST('hideInnerLines', 'int');
			if (getDolGlobalString('INFRASTRUCTURE_ONE_LINE_IF_HIDE_INNERLINES') && $hideInnerLines && !empty($infrastructure_last_title_posy)) {
				$posy						= $infrastructure_last_title_posy;
				$infrastructure_last_title_posy	= null;
			}
			$hidePriceOnInfrastructureLines	= GETPOST('hide_price_on_infrastructure_lines', 'int');
			if ($object->element == 'shipping' || $object->element == 'delivery') {
				$hidePriceOnInfrastructureLines = 1;
			}
			$set_pagebreak_margin	= false;
			if (method_exists('Closure', 'bind')) {
				$pageBreakOriginalValue = $pdf->AcceptPageBreak();
				$sweetsThief = function ($pdf) {
						return $pdf->bMargin ;
				};
				$sweetsThief	= Closure::bind($sweetsThief, null, $pdf);
				$bMargin		= $sweetsThief($pdf);
				$pdf->SetAutoPageBreak(false);
				$set_pagebreak_margin = true;
			}
			if ($line->qty == 99) {
				$pdf->SetFillColor(220, 220, 220);
			} elseif ($line->qty == 98) {
				$pdf->SetFillColor(230, 230, 230);
			} else {
				$pdf->SetFillColor(240, 240, 240);
			}
			$style				= getDolGlobalString('INFRASTRUCTURE_INFRASTRUCTURE_STYLE') ? getDolGlobalString('INFRASTRUCTURE_INFRASTRUCTURE_STYLE') : 'B';
			$pdf->SetFont('', $style, 9);
			$curentCellPaddinds = $pdf->getCellPaddings();	// save curent cell padding
			$pdf->setCellPaddings($curentCellPaddinds['L'], $infrastructureDefaultTopPadding, $curentCellPaddinds['R'], $infrastructureDefaultBottomPadding);	// set cell padding with column content definition for old PDF compatibility
			$pdf->writeHTMLCell($w, $h, $posx, $posy, $label, 0, 1, false, true, 'R', true);
			$pageAfter			= $pdf->getPage();
			$cell_height		= $pdf->getStringHeight($w, $label);	//Print background
			// POUR LES PDF DE TYPE PDF_EVOLUTION (ceux avec les colonnes configurables)
			if ($pdfModelUseColSystem) {
				if ($fillBackground) {
					$pdf->SetFillColor($backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
				}
				$pdf->SetXY($object->context['infrastructurePdfModelInfo']->marge_droite, $posy + $backgroundCellPosYOffset);
				$pdf->MultiCell($object->context['infrastructurePdfModelInfo']->page_largeur - $object->context['infrastructurePdfModelInfo']->marge_gauche - $object->context['infrastructurePdfModelInfo']->marge_droite, $cell_height, '', 0, '', 1);
			} else {
				$pdf->SetXY($posx, $posy + $backgroundCellPosYOffset); //-1 to take into account the entire height of the row
				//background color
				if ($fillBackground) {
					$pdf->SetFillColor($backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
					$pdf->SetFont('', '', 9); //remove UBI for the background
					$pdf->MultiCell($pdf->page_largeur - $pdf->marge_droite, $cell_height + $backgroundCellHeightOffset, '', 0, '', 1); //+2 same of SetXY()
					$pdf->SetXY($posx, $posy); //reset position
					$pdf->SetFont('', $style, 9); //reset style
				} else {
					$pdf->MultiCell($pdf->page_largeur - $pdf->marge_droite, $cell_height, '', 0, '', 1);
				}
			}
			if (!$hidePriceOnInfrastructureLines) {
				$total_to_print		= price($line->total, 0, '', 1, 0, getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'));
				if ($use_multicurrency) {
					$total_to_print	= price($line->multicurrency_total_ht,0,'',1,0,getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'));
				}
				if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS')) {
					$TTitle	= $this->getCachedAllTitleFromLine($object, $line);
					foreach ($TTitle as &$line_title) {
						if (!empty($line_title->array_options['options_infrastructure_nc'])) {
							$total_to_print = ''; // TODO Gestion "Compris/Non compris", voir si on affiche une annotation du genre "NC"
							break;
						}
					}
				}
				if ($total_to_print !== '') {
					if (GETPOST('hideInnerLines', 'int')) {
						// Dans le cas des lignes cachés, le calcul est déjà fait dans la méthode beforePDFCreation et les lignes de sous-totaux sont déjà renseignés
					} else {
						$TInfo			= infrastructure_get_totalLineFromObject($object, $line, false, 1);
						$TTotal_tva		= $TInfo[3];
						$total_to_print = price($TInfo[0], 0, '', 1, 0, getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'));
						if ($use_multicurrency) {
							$total_to_print = price($TInfo[6],0,'',1,0,getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'));
						}
						$line->total_ht	= $TInfo[0];
						$line->total	= $TInfo[0];
						if (!TInfrastructure::isModInfrastructureLine($line)) {
							$line->total_tva = $TInfo[1];
						}
						$line->total_ttc = $TInfo[2];
					}
				}
				$pdf->SetXY($pdf->postotalht, $posy);
				if ($set_pagebreak_margin) {
					$pdf->SetAutoPageBreak($pageBreakOriginalValue, $bMargin);
				}
				if ($pdfModelUseColSystem) {
					$staticPdfModel->printStdColumnContent($pdf, $posy, 'totalexcltax', $total_to_print);
					if (getDolGlobalString('PDF_PROPAL_SHOW_PRICE_INCL_TAX')) {
						$staticPdfModel->printStdColumnContent($pdf, $posy, 'totalincltax', price($line->total_ttc, 0, '', 1, 0, getDolGlobalInt('MAIN_MAX_DECIMALS_TOT')));
					}
				} else {
					$pdf->MultiCell($pdf->page_largeur - $pdf->marge_droite - $pdf->postotalht, 3, $total_to_print, 0, 'R', 0);
				}
			} else {
				if ($set_pagebreak_margin) {
					$pdf->SetAutoPageBreak($pageBreakOriginalValue, $bMargin);
				}
			}
			// restore cell padding
			$pdf->setCellPaddings($curentCellPaddinds['L'], $curentCellPaddinds['T'], $curentCellPaddinds['R'], $curentCellPaddinds['B']);
			$posy	= $posy + $cell_height;
			$pdf->SetXY($posx, $posy);
			$pdf->setColor('text', 0, 0, 0);
		}

		/**
		* PDF add title
		*
		* @param	TCPDF|ModelePDFStatic	$pdf			PDF object
		* @param	CommonObject			$object 		Object
		* @param	CommonObjectLine		$line			Line
		* @param	string					$label			Label
		* @param	string					$description	Description
		* @param	float					$posx			Horizontal position
		* @param	float					$posy			Vertical position
		* @param	float					$w				Width
		* @param	float					$h				Height
		* @return	void
		*/
		public function pdfAddTitle(&$pdf, &$object, &$line, $label, $description, $posx, $posy, $w, $h)
		{

			global $hidedesc;

			empty($pdf->page_largeur) ? $pdf->page_largeur = 0 : '';
			empty($pdf->marge_droite) ? $pdf->marge_droite = 0 : '';
			// Manage background color
			$fillDescBloc				= false;
			$bgStyle					= infrastructure_getPdfBackgroundStyle($pdf, 'INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', 'INFRASTRUCTURE_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET', 'INFRASTRUCTURE_TITLE_BACKGROUND_CELL_POS_Y_OFFSET');
			$fillBackground				= $bgStyle['fill'];
			$backgroundColor			= $bgStyle['color'];
			$backgroundCellHeightOffset	= $bgStyle['heightOffset'];
			$backgroundCellPosYOffset	= $bgStyle['posYOffset'];
			//$pdf->SetTextColor('text', 0, 0, 0);
			$infrastructure_last_title_posy	= $posy;
			$pdf->SetXY($posx, $posy);
			$hideInnerLines				= GETPOST('hideInnerLines', 'int');
			$style						= ($line->qty == 1) ? 'BU' : 'BUI';
			if (getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE')) {
				$style = getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE');
			}
			$size_title = 9;
			if (getDolGlobalString('INFRASTRUCTURE_TITLE_SIZE')) {
				$size_title = getDolGlobalString('INFRASTRUCTURE_TITLE_SIZE');
			}
			if ($hideInnerLines) {
				if ($line->qty == 1) {
					$pdf->SetFont('', $style, $size_title);
				} else {
					if (getDolGlobalString('INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES')) $style = getDolGlobalString('INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES');
					$pdf->SetFont('', $style, $size_title);
				}
			} else {
				if ($line->qty == 1) {
					$pdf->SetFont('', $style, $size_title); //TODO if super utile
				} else {
					$pdf->SetFont('', $style, $size_title);
				}
			}
			// save curent cell padding
			$curentCellPaddinds = $pdf->getCellPaddings();
			// set cell padding with column content definition PDF
			$pdf->setCellPaddings($curentCellPaddinds['L'], 1, $curentCellPaddinds['R'], 1);
			$posYBeforeTile = $pdf->GetY();
			if ($label === strip_tags($label) && $label === dol_html_entity_decode($label, ENT_QUOTES)) {
				$pdf->MultiCell($w, $h, $label, 0, 'L', $fillDescBloc); // Pas de HTML dans la chaine
			} else {
				$pdf->writeHTMLCell($w, $h, $posx, $posy, $label, 0, 1, $fillDescBloc, true, 'J', true); // et maintenant avec du HTML
			}
			$posYBeforeDesc = $pdf->GetY();
			if ($description && !($hidedesc ?? 0)) {
				$pdf->setColor('text', 0, 0, 0);
				$pdf->SetFont('', '', $size_title - 1);
				$pdf->writeHTMLCell($w, $h, $posx, $posYBeforeDesc + 1, $description, 0, 1, $fillDescBloc, true, 'J', true);
			}
			//background color
			if ($fillBackground) {
				$posYAfterDesc	= $pdf->GetY();
				$cell_height	= $pdf->getStringHeight($w, $label) + $backgroundCellHeightOffset;
				$bgStartX		= $posx;
				$bgW			= $pdf->page_largeur - $pdf->marge_droite;// historiquement ce sont ces valeurs, mais elles sont la plupart du temps vide
				// POUR LES PDF DE TYPE PDF_EVOLUTION (ceux avec les colonnes configurables)
				if (!empty($object->context['infrastructurePdfModelInfo']->cols)) {
					$bgStartX	= $object->context['infrastructurePdfModelInfo']->marge_droite;
					$bgW 		= $object->context['infrastructurePdfModelInfo']->page_largeur - $object->context['infrastructurePdfModelInfo']->marge_gauche - $object->context['infrastructurePdfModelInfo']->marge_droite;
				}
				$pdf->SetFillColor($backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
				$pdf->SetXY($bgStartX, $posy + $backgroundCellPosYOffset); //-2 to take into account  the entire height of the row
				$pdf->MultiCell($bgW, $cell_height, '', 0, '', 1, 1, null, null, true, 0, true); //+2 same of SetXY()
				$posy = $posYAfterDesc;
				$pdf->SetXY($posx, $posy); //reset position
				$pdf->SetFont('', $style, $size_title); //reset style
				$pdf->SetColor('text', 0, 0, 0); // restore default text color;
			}
			// restore cell padding
			$pdf->setCellPaddings($curentCellPaddinds['L'], $curentCellPaddinds['T'], $curentCellPaddinds['R'], $curentCellPaddinds['B']);
		}

		/**
		* PDF write line desc ref
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_writelinedesc_ref($parameters = array(), &$object, &$action = '')
		{
			return $this->pdf_writelinedesc($parameters, $object, $action);
		}

		/**
		* Is mod infrastructure line
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @return	bool
		*/
		public function isModInfrastructureLine(&$parameters, &$object)
		{

			$i		= is_array($parameters) ? $parameters['i'] : (int) $parameters;
			$line	= $object->lines[$i] ?? '';
			if ($object->element == 'shipping' || $object->element == 'delivery') {
				$line = new OrderLine($object->db);
				$line->fetch(!empty($object->lines[$i]->fk_elementdet) ? $object->lines[$i]->fk_elementdet : 0);
			}
			if (is_object($line) && property_exists($line, 'special_code') && $line->special_code == $this->module_number && $line->product_type == 9) {
				return true;
			}
			return false;
		}

		/**
		* Before percent calculation
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	void
		*/
		public function beforePercentCalculation($parameters = array(), &$object, &$action = '')
		{
			if ($object->name == 'sponge' && isset($parameters['object']) && !empty($parameters['object']->lines)) {
				foreach ($parameters['object']->lines as $k => $line) {
					if (TInfrastructure::isModInfrastructureLine($line)) {
						unset($parameters['object']->lines[$k]);
					}
				}
			}
		}

		/**
		* PDF get line qty
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlineqty($parameters = array(), &$object, &$action = '')
		{
			global $hidesubdetails, $hideprices, $hookmanager;

			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				if ($this->infrastructure_sum_qty_enabled === true) {
					$line_qty = intval($line->qty);
					if ($line_qty < 50) {
						// it's a title level (init level qty)
						$infrastructure_level = $line_qty;
						$this->infrastructure_level_cur = $infrastructure_level;
						TInfrastructure::setInfrastructureQtyForObject($object, $infrastructure_level, 0);
						// not show qty for title lines
						$this->resprints = '';
						return 1;
					} elseif ($line_qty > 50) {
						// it's a infrastructure level (show level qty and reset)
						$infrastructure_level = 100 - $line_qty;
						$level_qty_total = $object->TInfrastructureQty[$infrastructure_level];
						TInfrastructure::setInfrastructureQtyForObject($object, $infrastructure_level, 0);
						// show quantity sum only if it's a infrastructure line (level)
						$line_show_qty = TInfrastructure::showQtyForObjectLine($line, $this->infrastructure_show_qty_by_default);
						if ($line_show_qty === false) {
							$this->resprints = '';
						} else {
							$this->resprints = $level_qty_total;
						}
						return 1;
					} else {
						// not show qty for text line
						$this->resprints = '';
						return 1;
					}
				} else {
					$this->resprints = ' ';
					return 1;
				}
			} else {
				if ($this->infrastructure_sum_qty_enabled === true) {
					// sum quantities by infrastructure level
					if ($this->infrastructure_level_cur >= 1) {
						for ($infrastructure_level = 1; $infrastructure_level <= $this->infrastructure_level_cur; $infrastructure_level++) {
							TInfrastructure::addInfrastructureQtyForObject($object, $infrastructure_level, $line->qty);
						}
					}
				}
				if (!empty($hideprices) && !empty($object->lines[$parameters['i']]) && property_exists($object->lines[$parameters['i']], 'qty')) {
					$this->resprints = $object->lines[$parameters['i']]->qty;
					return 1;
				} elseif (getDolGlobalString('INFRASTRUCTURE_IF_HIDE_PRICES_SHOW_QTY')) {
					$hideInnerLines = GETPOST('hideInnerLines', 'int');
					//$hidesubdetails = GETPOST('hidesubdetails', 'int');
					if (empty($hideInnerLines) && !empty($hidesubdetails)) {
						$this->resprints = $object->lines[$parameters['i']]->qty;
					}
				}
				// Cache la quantité pour les lignes standards dolibarr qui sont dans un ensemble
				else if (!empty($hidesubdetails)) {
					// Check if a title exist for this line && if the title have infrastructure
					$lineTitle	= (!empty($object->lines[$i])) ? $this->getCachedParentTitle($object, $object->lines[$i]->rang): '';
					if (!($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true))) {
						$this->resprints	= $object->lines[$parameters['i']]->qty;
					} else {
						$this->resprints	= ' ';
						// currentcontext à modifier celon l'appel
						$params				= array('parameters' => $parameters, 'currentmethod' => 'pdf_getlineqty', 'currentcontext'=>'infrastructure_hidesubdetails', 'i' => $i);
						return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
					}
				}
			}
			if (is_array($parameters)) $i = &$parameters['i'];
			else $i = (int) $parameters;
			/** Attention, ici on peut ce retrouver avec un objet de type stdClass à cause de l'option cacher le détail des ensembles avec la notion de Non Compris (@see beforePDFCreation()) et dû à l'appel de TInfrastructure::hasNcTitle() */
			if (empty($object->lines[$i]->id)) return 0; // hideInnerLines => override $object->lines et Dolibarr ne nous permet pas de mettre à jour la variable qui conditionne la boucle sur les lignes (PR faite pour 6.0)
			if (empty($object->lines[$i]->array_options)) $object->lines[$i]->fetch_optionals();
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i]))) {
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					return 1;
				}
			}

			return 0;
		}

		/**
		* PDF get line total excl tax
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlinetotalexcltax($parameters = array(), &$object, &$action = '')
		{
			global $conf, $hideprices, $hidesubdetails, $hookmanager, $hidedetails, $langs;

			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				$use_multicurrency	= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;
				if (!empty($parameters['infrasplus'])) {
					$hidePriceOnInfrastructureLines = $object->element == 'shipping' || $object->element == 'delivery' ? 1 : GETPOST('hide_price_on_infrastructure_lines', 'int');
					if (empty($hidePriceOnInfrastructureLines)) {
						$total_to_print = price($object->lines[$i]->total);
						if (getDolGlobalInt('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS')) {
							$TTitle = $this->getCachedAllTitleFromLine($object, $object->lines[$i]);
							foreach ($TTitle as &$line_title) {
								if (!empty($line_title->array_options['options_infrastructure_nc'])) {
									$total_to_print = ''; // TODO Gestion "Compris/Non compris", voir si on affiche une annotation du genre "NC"
									break;
								}
							}
						}
						if($total_to_print !== '') {
							if (GETPOST('hideInnerLines', 'int')) {
								// Dans le cas des lignes cachés, le calcul est déjà fait dans la méthode beforePDFCreation et les lignes de sous-totaux sont déjà renseignés
							}
							else {
								dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
								$TInfo							= infrastructure_get_totalLineFromObject($object, $object->lines[$i], false, 1);
								$TTotal_tva						= $TInfo[3];
								$total_to_print					= pdf_InfraSPlus_price($object, $TInfo[0], $langs);
								if ($use_multicurrency) {
									$total_to_print				= pdf_InfraSPlus_price($object, $TInfo[6], $langs);
								}
								$object->lines[$i]->total		= $TInfo[0];
								$object->lines[$i]->total_ht	= $TInfo[0];
								$object->lines[$i]->total_tva	= !TInfrastructure::isModInfrastructureLine($object->lines[$i]) ? $TInfo[1] : $object->lines[$i]->total_tva;
								$object->lines[$i]->total_ttc	= $TInfo[2];
								$object->lines[$i]->multicurrency_total_ht	= $TInfo[6];
								$object->lines[$i]->multicurrency_total_ttc	= $TInfo[7];
							}
						}
						$this->resprints	= !empty($total_to_print) ? $total_to_print : ' ';
						return 1;
					}
				}
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				$this->resprints = ' ';
				return 1;
			} elseif (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS')) {
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					if (!empty($object->lines[$i]->array_options['options_infrastructure_nc'])) {
						$this->resprints = ' ';
						return 1;
					}
					$TTitle = $this->getCachedAllTitleFromLine($object, $object->lines[$i]);
					foreach ($TTitle as &$line_title) {
						if (!empty($line_title->array_options['options_infrastructure_nc'])) {
							$this->resprints = ' ';
							return 1;
						}
					}
				} elseif (in_array('pdf_getlinetotalexcltax', $this->getNcTfieldKeepList()) && floatval($object->lines[$i]->total_ht) == 0) {
					// On affiche le véritable total ht de la ligne sans le comptabilisé
					$this->resprints = price($object->lines[$i]->qty * $object->lines[$i]->subprice);
					return 1;
				}
			}
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i]))) {
				// alors je dois vérifier si la méthode fait partie de la conf qui l'exclue
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					// currentcontext à modifier celon l'appel
					$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinetotalexcltax', 'currentcontext' => 'infrastructure_hide_nc', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			} else if (!empty($hideprices) || !empty($hidesubdetails)) {
				// Check if a title exist for this line && if the title have infrastructure
				$lineTitle = (!empty($object->lines[$i])) ? $this->getCachedParentTitle($object, $object->lines[$i]->rang): '';
				if ($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true)) {
					$this->resprints = ' ';
					// currentcontext à modifier celon l'appel
					$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinetotalexcltax', 'currentcontext' => 'infrastructure_hideprices', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			} elseif (!empty($hidedetails)) {
				$lineTitle = (!empty($object->lines[$i])) ? $this->getCachedParentTitle($object, $object->lines[$i]->rang): '';
				if (!($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true))) {
					$this->resprints = price($object->lines[$i]->total_ht, 0, $langs);
					$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinetotalexcltax', 'currentcontext' => 'infrastructure_hidedetails', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			}
			return 0;
		}

		/**
		* Remplace le retour de la méthode qui l'appelle par un standard 1 ou autre chose celon le hook
		*
		* @param	CommonObject	$object			Object
		* @param	HookManager		$hookmanager	Hook manager
		* @param	string			$action			Action
		* @param	array			$params			Parameters
		* @param	int				$defaultReturn	Default return value
		* @return	int 1, 0, -1
		*/
		private function callHook(&$object, &$hookmanager, $action, $params, $defaultReturn = 1)
		{
			$reshook = $hookmanager->executeHooks('infrastructureHidePrices', $params, $object, $action);
			if ($reshook < 0) {
				$this->error	= $hookmanager->error;
				$this->errors	= $hookmanager->errors;
				return -1;
			} elseif (empty($reshook)) {
				if (property_exists($hookmanager, 'resPrints')) {
					$this->resprints	.= $hookmanager->resPrint;
				}
			} else {
				$this->resprints = $hookmanager->resPrint;
				// override return (use $this->results['overrideReturn'] or $this->resArray['overrideReturn'] in other module action_xxxx.class.php )
				if (isset($this->results['overrideReturn'])) {
					return $this->results['overrideReturn'];
				}
			}
			return $defaultReturn;
		}
		/**
		* PDF get line total with tax
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlinetotalwithtax($parameters = array(), &$object, &$action = '')
		{
			global $conf, $langs;

			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				if (!empty($parameters['infrasplus'])) {
					$hidePriceOnInfrastructureLines	= $object->element == 'shipping' || $object->element == 'delivery' ? 1 : GETPOST('hide_price_on_infrastructure_lines', 'int');
					if (empty($hidePriceOnInfrastructureLines)) {
						$total_to_print	= price($object->lines[$i]->total_ttc);
						if (getDolGlobalInt('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS')) {
							$TTitle	= $this->getCachedAllTitleFromLine($object, $object->lines[$i]);
							foreach ($TTitle as &$line_title) {
								if (!empty($line_title->array_options['options_infrastructure_nc'])) {
									$total_to_print = ''; // TODO Gestion "Compris/Non compris", voir si on affiche une annotation du genre "NC"
									break;
								}
							}
						}
						if ($total_to_print !== '') {
							if (GETPOST('hideInnerLines', 'int')) {
								// Dans le cas des lignes cachés, le calcul est déjà fait dans la méthode beforePDFCreation et les lignes de sous-totaux sont déjà renseignés
							} else {
								dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
								$TInfo							= infrastructure_get_totalLineFromObject($object, $object->lines[$i], false, 1);
								$TTotal_tva						= $TInfo[3];
								$total_to_print					= pdf_InfraSPlus_price($object, $TInfo[2], $langs);
								$object->lines[$i]->total		= $TInfo[0];
								$object->lines[$i]->total_ht	= $TInfo[0];
								$object->lines[$i]->total_tva	= !TInfrastructure::isModInfrastructureLine($object->lines[$i]) ? $TInfo[1] : $object->lines[$i]->total_tva;
								$object->lines[$i]->total_ttc	= $TInfo[2];
							}
						}
						$this->resprints	= !empty($total_to_print) ? $total_to_print : ' ';
						return 1;
					}
				}
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				$this->resprints = ' ';
				return 1;
			}
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i]))) {
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					return 1;
				}
			}
			return 0;
		}

		/**
		* PDF get line unit
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlineunit($parameters = array(), &$object, &$action = '')
		{
			global $conf;

			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				$this->resprints = ' ';
				return 1;
			}
			if (is_array($parameters)) {
				$i = &$parameters['i'];
			} else {
				$i = (int) $parameters;
			}
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i]))) {
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					return 1;
				}
			}
			return 0;
		}

		/**
		* PDF get line up excl tax
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlineupexcltax($parameters = array(), &$object, &$action = '')
		{
			global $conf, $hidesubdetails, $hideprices, $hidedetails, $hookmanager, $langs;

			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				$this->resprints = ' ';
				// On récupère les montants du bloc pour les afficher dans la ligne de sous-total
				if (TInfrastructure::isInfrastructure($line)) {
					$parentTitle = $this->getCachedParentTitle($object, $line->rang);
					if (is_object($parentTitle) && empty($parentTitle->array_options)) {
						$parentTitle->fetch_optionals();
					}
					if (!empty($parentTitle->array_options['options_show_total_ht'])) {
						$TTotal = TInfrastructure::getTotalBlockFromTitle($object, $parentTitle);
						$this->resprints = price($TTotal['total_unit_subprice'], 0, '', 1, 0, getDolGlobalString('MAIN_MAX_DECIMALS_TOT'));
					}
				}
				return 1;
			}
			// Si la gestion C/NC est active et que je suis sur un ligne dont l'extrafield est coché
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i]))) {
				// alors je dois vérifier si la méthode fait partie de la conf qui l'exclue
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					// currentcontext à modifier celon l'appel
					$params			= array('parameters' => $parameters, 'currentmethod' => 'pdf_getlineupexcltax', 'currentcontext'=>'infrastructure_hide_nc', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			} else if (!empty($hideprices) || !empty($hidesubdetails)) {
				// Check if a title exist for this line && if the title have infrastructure
				$lineTitle = (!empty($object->lines[$i])) ? $this->getCachedParentTitle($object, $object->lines[$i]->rang): '';
				if ($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true)) {
					$this->resprints = ' ';
					// currentcontext à modifier celon l'appel
					$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlineupexcltax', 'currentcontext' => 'infrastructure_hideprices', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			} elseif (!empty($hidedetails)) {
				$lineTitle = (!empty($object->lines[$i])) ? $this->getCachedParentTitle($object, $object->lines[$i]->rang) : '';
				if (!($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true))) {
					$this->resprints = price($object->lines[$i]->subprice, 0, $langs);
					$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlineupexcltax', 'currentcontext' => 'infrastructure_hidedetails', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				} //
			}
			return 0;
		}

		/**
		* PDF get line remise percent
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlineremisepercent($parameters = array(), &$object, &$action = '')
		{
			global $conf, $hidesubdetails, $hideprices, $hidedetails, $hookmanager, $langs;

			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				$this->resprints = ' ';
				// Affichage de la remise
				if (TInfrastructure::isInfrastructure($line)) {
					if ($parentTitle = $this->getCachedParentTitle($object, $line->rang)) {
						if (empty($parentTitle->array_options)) {
							$parentTitle->fetch_optionals();
						}
						if (!empty($parentTitle->array_options['options_show_reduc'])) {
							$TTotal				= TInfrastructure::getTotalBlockFromTitle($object, $parentTitle);
							$this->resprints	= price((1 - $TTotal['total_ht'] / $TTotal['total_subprice']) * 100, 0, '', 1, 2, 2).'%';
						}
					}
				}
				return 1;
			} elseif (!empty($hideprices) || !empty($hidesubdetails) || (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i])) )) {
				if (!empty($hideprices) || !in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					// Check if a title exist for this line && if the title have infrastructure
					$lineTitle	= $this->getCachedParentTitle($object, $object->lines[$i]->rang);
					if ($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true)) {
						$this->resprints	= ' ';
						return 1;
					}
				}
			} elseif (!empty($hidedetails)) {
				$lineTitle	= (!empty($object->lines[$i])) ? $this->getCachedParentTitle($object, $object->lines[$i]->rang): '';
				if (!($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true))) {
					$this->resprints	= dol_print_reduction($object->lines[$i]->remise_percent, $langs);
					return 1;
				}
			}
			return 0;
		}

		/**
		* PDF get line up with tax
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlineupwithtax($parameters = array(), &$object, &$action = '')
		{
			global $conf, $hidesubdetails, $hideprices;

			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;

			if ($this->isModInfrastructureLine($parameters, $object)) {
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				$this->resprints = ' ';
				return 1;
			}
			if (is_array($parameters)) {
				$i = &$parameters['i'];
			} else {
				$i = (int) $parameters;
			}
			if (!empty($hideprices) || !empty($hidesubdetails) || (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i])))) {
				if (!empty($hideprices) || !in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					return 1;
				}
			}
			return 0;
		}

		/**
		* PDF get line vat rate
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlinevatrate($parameters = array(), &$object, &$action = '')
		{
			global $hidesubdetails, $hideprices, $hidedetails, $hookmanager;

			$i			= intval($parameters['i']);
			$line		= isset($object->lines[$i]) ? $object->lines[$i] : null;		// Dans le cas des notes de frais report ne pas traiter
			$TContext	= explode(':', $parameters['context']);
			if (in_array('expensereportcard', $TContext))	return 0;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				// Vérifie le taux de TVA des lignes comprises entre un Titre et un Sous-total de même niveau.
				$tva_unique = TInfrastructure::getCommonVATRate($object, $object->lines[$i]);
				// Si un taux unique est trouvé, on l'affiche dans la colonne TVA
				   if (!empty(getDolGlobalString('INFRASTRUCTURE_SHOW_TVA_ON_INFRASTRUCTURE_LINES_ON_ELEMENTS')) && $tva_unique !== false
					   && (!getDolGlobalInt('INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS') || (getDolGlobalInt('INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS')
					   && ((!empty($line->array_options['options_print_as_list']) && $line->array_options['options_print_as_list'] > 0)
					   || (!empty($line->array_options['options_print_condensed']) && $line->array_options['options_print_condensed'] > 0))))) {
					   $this->resprints = vatrate($tva_unique, true);
				} else {
					if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
					$this->resprints = ' ';
				}
				return 1;
			}
			if (empty($object->lines[$i])) return 0; // hideInnerLines => override $object->lines et Dolibarr ne nous permet pas de mettre à jour la variable qui conditionne la boucle sur les lignes (PR faite pour 6.0)
			$object->lines[$i]->fetch_optionals();
			// Si la gestion C/NC est active et que je suis sur un ligne dont l'extrafield est coché
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i]))) {
				// alors je dois vérifier si la méthode fait partie de la conf qui l'exclue
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					// currentcontext à modifier celon l'appel
					$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinevatrate', 'currentcontext'=>'infrastructure_hide_nc', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			}
			// Cache le prix pour les lignes standards dolibarr qui sont dans un ensemble
			else if (!empty($hideprices) || !empty($hidesubdetails)) {
				// Check if a title exist for this line && if the title have infrastructure
				$lineTitle = $this->getCachedParentTitle($object, $object->lines[$i]->rang);
				if ($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true)) {
					$this->resprints = ' ';
					// currentcontext à modifier celon l'appel
					$params = array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinevatrate', 'currentcontext' => 'infrastructure_hideprices', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			} elseif (!empty($hidedetails)) {
				$lineTitle = (!empty($object->lines[$i])) ? $this->getCachedParentTitle($object, $object->lines[$i]->rang) : '';
				if (!($lineTitle && $this->getCachedTitleHasTotal($object, $lineTitle, true))) {
					$this->resprints	= vatrate($object->lines[$i]->tva_tx, true);
					$params				= array('parameters' => $parameters, 'currentmethod' => 'pdf_getlinevatrate', 'currentcontext' => 'infrastructure_hidedetails', 'i' => $i);
					return $this->callHook($object, $hookmanager, $action, $params); // return 1 (qui est la valeur par défaut) OU -1 si erreur OU overrideReturn (contient -1 ou 0 ou 1)
				}
			}
			return 0;
		}

		/**
		* PDF get line progress
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object 	Object
		* @param	string			$action 	Action
		* @return	int
		*/
		public function pdf_getlineprogress($parameters = array(), &$object, &$action)
		{
			$i		= intval($parameters['i']);
			$line	= isset($object->lines[$i]) ? $object->lines[$i] : null;
			if ($this->isModInfrastructureLine($parameters, $object)) {
				if ($line && $line->qty == -99) { $this->resprints = ' '; return 1; }
				$this->resprints = ' ';
				return 1;
			}
			if (is_array($parameters)) {
				$i = &$parameters['i'];
			} else {
				$i = (int) $parameters;
			}
			if (getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && (!empty($object->lines[$i]->array_options['options_infrastructure_nc']) || TInfrastructure::hasNcTitle($object->lines[$i]))) {
				if (!in_array(__FUNCTION__, $this->getNcTfieldKeepList())) {
					$this->resprints = ' ';
					return 1;
				}
			}
			return 0;
		}

		/**
		* Before PDF creation
		*
		* @param	array			$parameters	Parameters
		* @param	CommonObject	$object		Object
		* @param	string			$action		Action
		* @return	int							> 0 if OK, 0 if no hook executed, < 0 if KO
		*/
		public function beforePDFCreation($parameters = array(), &$object, &$action = '')
		{
			/**
			 * @var $pdf    TCPDF
			 */
			global $pdf, $conf, $langs;

			if (TInfrastructure::showQtyForObject($object) === true) {
				$this->infrastructure_sum_qty_enabled		= true;
				$this->infrastructure_show_qty_by_default = true;
			}
			if (!isset($object->context) || !is_array($object->context)) {
				$object->context	= array();
			}
			$object->context['infrastructureCache']	= array();
			$this->warmPDFInfrastructureCache($object);
			$TContext	= explode(':', $parameters['context']);
			if (in_array('pdfgeneration', $TContext)) {
				$object->context['infrastructurePdfModelInfo']		= new stdClass(); // see defineColumnFiel method in this class
				$object->context['infrastructurePdfModelInfo']->cols	= false;
				}
				if (in_array('propalcard', $TContext) || in_array('ordercard', $TContext) || in_array('invoicecard', $TContext) || in_array('supplier_proposalcard', $TContext) || in_array('ordersuppliercard', $TContext) || in_array('invoicesuppliercard', $TContext)) {
				$i = 0;
				if (isset($parameters['i'])) {
					$i = $parameters['i'];
				}
				foreach ($parameters as $key => $value) {
					${$key} = $value;
				}
				infrastructure_setDocTVA($pdf, $object);
				infrastructure_addNumerotation($object);
				foreach ($object->lines ?? [] as $k => &$l) {
					if (TInfrastructure::isInfrastructure($l)) {
						$parentTitle = $this->getCachedParentTitle($object, $l->rang);
						if (is_object($parentTitle) && empty($parentTitle->array_options)) $parentTitle->fetch_optionals();
						if (!empty($parentTitle->id) && !empty($parentTitle->array_options['options_show_reduc'])) {
							$l->remise_percent = 100;    // Affichage de la réduction sur la ligne de sous-total
						}
					}
					// Pas de hook sur les colonnes du PDF expédition, on unset les bonnes variables
					if (($object->element == 'shipping' || $object->element == 'delivery') && $this->isModInfrastructureLine($k, $object)) {
						$l->qty = $l->qty_asked;
						unset($l->qty_asked, $l->qty_shipped, $l->volume, $l->weight);
					}
				}
				$hideInnerLines	= GETPOST('hideInnerLines', 'int');
				$hidesubdetails = GETPOST('hidesubdetails', 'int');
				if (!empty($hideInnerLines)) { // si c une ligne de titre
					$fk_parent_line	= 0;
					$TLines			= array();
					$original_count = count($object->lines);
					$TTvas			= array(); // tableau de tva
					foreach ($object->lines as $k => &$line) {
						// to keep compatibility with supplier order and old versions (rowid was replaced with id in fetch lines method)
						if ($line->id > 0) {
							$line->rowid = $line->id;
						}
						if ($line->product_type == 9 && $line->rowid > 0) {
							$fk_parent_line	= $line->rowid;
							// Fix tk7201 - si on cache le détail, la TVA est renseigné au niveau du sous-total, l'erreur c'est s'il y a plusieurs sous-totaux pour les même lignes, ça va faire la somme
							if (TInfrastructure::isInfrastructure($line)) {
								$TInfo = infrastructure_get_totalLineFromObject($object, $line, false, 1);
								if (TInfrastructure::getNiveau($line) == 1) {
									$line->TTotal_tva = $TInfo[3];
									$line->TTotal_tva_array = $TInfo[5];
								}
								$line->total_ht		= $TInfo[0];
								$line->total_tva	= $TInfo[1];
								$line->total		= $line->total_ht;
								$line->total_ttc	= $TInfo[2];
							}
						}
						if ($hideInnerLines) {
							$hasParentTitle = $this->getCachedParentTitle($object, $line->rang);
							if (empty($hasParentTitle) && empty(TInfrastructure::isModInfrastructureLine($line))) {	// cette ligne n'est pas dans un titre => on l'affiche
								$TLines[] = $line;
							}
							if (getDolGlobalString('INFRASTRUCTURE_REPLACE_WITH_VAT_IF_HIDE_INNERLINES')) {
								if ($line->tva_tx != '0.000' && $line->product_type != 9) {
									// on remplit le tableau de tva pour substituer les lignes cachées
									if (!empty($TTvas[$line->tva_tx]['total_tva'])) $TTvas[$line->tva_tx]['total_tva']	+= $line->total_tva;
									if (!empty($TTvas[$line->tva_tx]['total_ht'])) $TTvas[$line->tva_tx]['total_ht']	+= $line->total_ht;
									if (!empty($TTvas[$line->tva_tx]['total_ttc'])) $TTvas[$line->tva_tx]['total_ttc']	+= $line->total_ttc;
								}
								if ($line->product_type == 9 && $line->rowid > 0) {
									//Cas où je doit cacher les produits et afficher uniquement les sous-totaux avec les titres
									// génère des lignes d'affichage des montants HT soumis à tva
									$nbtva = count($TTvas);
									if (!empty($nbtva)) {
										foreach ($TTvas as $tx => $val) {
											$copyL					= clone $line; // la variable $coyyL était nommé $l, j' l'ai renommé car probleme de référence d'instance dans le clone
											$copyL->product_type	= 1;
											$copyL->special_code	= '';
											$copyL->qty				= 1;
											$copyL->desc			= $langs->trans('AmountBeforeTaxesSubjectToVATX', $langs->transnoentitiesnoconv('VAT'), price($tx));
											$copyL->tva_tx			= $tx;
											$copyL->total_ht		= $val['total_ht'];
											$copyL->total_tva		= $val['total_tva'];
											$copyL->total			= $line->total_ht;
											$copyL->total_ttc		= $val['total_ttc'];
											$TLines[]				= $copyL;
											array_shift($TTvas);
										}
									}
									// ajoute la ligne de sous-total
									$TLines[] = $line;
								}
							} else {
								if ($line->product_type == 9 && $line->rowid > 0) {
									// Inject invisible VAT lines here
									if (!empty($line->TTotal_tva)) {
										foreach ($line->TTotal_tva as $vatrate => $vatamount) {
											$vatLine				= clone $line;
											$vatLine->qty			= -99;
											$vatLine->tva_tx		= $vatrate;
											$vatLine->total_tva		= $vatamount;
											$vatLine->total_ht		= 0;
											$vatLine->total_ttc		= 0;
											$vatLine->TTotal_tva	= null; // Clear to avoid recursion/confusion
											$TLines[]				= $vatLine;
										}
									}
									$lineForDisplay					= clone $line;
									$lineForDisplay->TTotal_tva		= null;
									$lineForDisplay->total_tva		= 0;
									// ajoute la ligne de sous-total
									$TLines[] = $lineForDisplay;
								}
							}
							} elseif (!empty($hidesubdetails)) {
							$TLines[] = $line; //Cas où je cache uniquement les prix des produits
						}
						if ($line->product_type != 9) { // jusqu'au prochain titre ou total
							//$line->fk_parent_line = $fk_parent_line;
						}
					}
					// cas incongru où il y aurait des produits en dessous du dernier sous-total
					$nbtva = count($TTvas);
					if(!empty($nbtva) && !empty($hideInnerLines) && getDolGlobalString('INFRASTRUCTURE_REPLACE_WITH_VAT_IF_HIDE_INNERLINES')) {
						foreach ($TTvas as $tx => $val) {
							$l					= clone $line;
							$l->product_type	= 1;
							$l->special_code	= '';
							$l->qty				= 1;
							$l->desc			= $langs->trans('AmountBeforeTaxesSubjectToVATX', $langs->transnoentitiesnoconv('VAT'), price($tx));
							$l->tva_tx			= $tx;
							$l->total_ht		= $val['total_ht'];
							$l->total_tva		= $val['total_tva'];
							$l->total			= $line->total_ht;
							$l->total_ttc		= $val['total_ttc'];
							$TLines[]			= $l;
							array_shift($TTvas);
						}
					}
					$nblignes		= count($TLines);
					$object->lines	= $TLines;
					$object->context['infrastructureCache']	= array();
					if ($i > count($object->lines)) {
						$this->resprints = '';
						return 0;
					}
				}
			}
			$this->warmPDFInfrastructureCache($object);
			return 0;
		}

		/**
		* PDF write line desc
		*
		* @param	array			$parameters	Parameters
		* @param	CommonObject	$object		Object
		* @param	string			$action		Action
		* @return	int
		*/
		public function pdf_writelinedesc($parameters = array(), &$object, &$action = '')
		{
			/**
			 * @var $pdf    TCPDF
			 */
			global $pdf;
			foreach ($parameters as $key => $value) {
				${$key} = $value;
			}
			// même si le foreach du dessu fait ce qu'il faut, l'IDE n'aime pas
			$outputlangs	= $parameters['outputlangs'];
			$i				= $parameters['i'];
			$posx			= $parameters['posx'];
			$h				= $parameters['h'];
			$w				= $parameters['w'];
			$hideInnerLines = GETPOST('hideInnerLines', 'int');
			$hidesubdetails = GETPOST('hidesubdetails', 'int');
			if ($this->isModInfrastructureLine($parameters, $object) ) {
				global $hidesubdetails, $hideprices;
				if(!empty($hideprices) || !empty($hidesubdetails)) {
					if (empty($object->context['infrastructureCache']['fkParentLineReset'])) {
						foreach ($object->lines as &$line) {
							if ($line->fk_product_type != 9) $line->fk_parent_line = -1;
						}
						unset($line);
						if (!is_array($object->context)) {
							$object->context = array();
						}
						if (!isset($object->context['infrastructureCache'])) {
							$object->context['infrastructureCache'] = array();
						}
						$object->context['infrastructureCache']['fkParentLineReset'] = true;
					}
				}
				$line = &$object->lines[$i];
				// Unset on Dolibarr < 20.0
				if ($object->element == 'delivery' && !empty($object->commande->expeditions[$line->fk_elementdet])) unset($object->commande->expeditions[$line->fk_elementdet]);
				// Unset on Dolibarr >= 20.0
				if ($object->element == 'delivery' && !empty($object->commande->expeditions[$line->fk_elementdet])) unset($object->commande->expeditions[$line->fk_elementdet]);
				$margin = $pdf->getMargins();
				if (!empty($margin) && $line->info_bits > 0) { // PAGE BREAK
					$pdf->addPage();
					$posy = $margin['top'];
				}
				$label			= $line->label;
				$description	= !empty($line->desc) ? $outputlangs->convToOutputCharset($line->desc) : $outputlangs->convToOutputCharset($line->description);
				if (empty($label)) {
					$label = $description;
					$description = '';
				}
				if ($line->qty == -99) {
					return 1;
				} elseif ($line->qty > 90) {
					if (getDolGlobalInt('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL')) {
						$label .= ' '.infrastructure_getTitle($object, $line);
					}
					if (!empty(getDolGlobalString('INFRASTRUCTURE_DISABLE_FIX_TRANSACTION'))) {
						/**
						 * TCPDF::startTransaction() committe la transaction en cours s'il y en a une,
						 * ce qui peut être problématique. Comme TCPDF::rollbackTransaction() ne fait rien
						 * si aucune transaction n'est en cours, on peut y faire appel sans problème pour revenir
						 * à l'état d'origine.
						 */
						$pdf->rollbackTransaction(true);
						$pdf->startTransaction();
						$pageBefore = $pdf->getPage();
					}
					// FIX DA024845 : Le module sous total amène des erreurs dans les sauts de page lorsque l'on arrive tout juste en bas de page.
					$heightForFooter = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10) + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)
					if ($pdf->getPageHeight() - $posy - $heightForFooter < 8) {
						$pdf->addPage('', '', true);
						$posy = $pdf->GetY();
					}
					$this->pdfAddTotal($pdf, $object, $line, $label, $description, $posx, $posy, $w, $h);
					if (!empty(getDolGlobalString('INFRASTRUCTURE_DISABLE_FIX_TRANSACTION'))) {
						$pageAfter = $pdf->getPage();
						if ($pageAfter > $pageBefore) {
							//print "ST $pageAfter>$pageBefore<br>";
							$pdf->rollbackTransaction(true);
							$pdf->addPage('', '', true);
							$posy = $pdf->GetY();
							$this->pdfAddTotal($pdf, $object, $line, $label, $description, $posx, $posy, $w, $h);
							$posy = $pdf->GetY();
							//print 'add ST'.$pdf->getPage().'<br />';
						} else {
							$pdf->commitTransaction();
						}
					}
					// On delivery PDF, we don't want quantities to appear and there are no hooks => setting text color to background color;
					if ($object->element == 'delivery') {
						switch ($line->qty) {
							case 99:
								$grey = 220;
								break;
							case 98:
								$grey = 230;
								break;
							default:
								$grey = 240;
						}
						$pdf->SetTextColor($grey, $grey, $grey);
					}
					$posy = $pdf->GetY();
					return 1;
				} elseif ($line->qty < 10) {
					if (!empty(getDolGlobalString('INFRASTRUCTURE_DISABLE_FIX_TRANSACTION'))) {
						/**
						 * TCPDF::startTransaction() committe la transaction en cours s'il y en a une,
						 * ce qui peut être problématique. Comme TCPDF::rollbackTransaction() ne fait rien
						 * si aucune transaction n'est en cours, on peut y faire appel sans problème pour revenir
						 * à l'état d'origine.
						 */
						$pdf->rollbackTransaction(true);
						$pdf->startTransaction();
						$pageBefore	= $pdf->getPage();
					}
					$this->pdfAddTitle($pdf, $object, $line, $label, $description, $posx, $posy, $w, $h);
					if (!empty(getDolGlobalString('INFRASTRUCTURE_DISABLE_FIX_TRANSACTION'))) {
						$pageAfter	= $pdf->getPage();
						if ($pageAfter > $pageBefore) {
							//print "ST $pageAfter>$pageBefore<br>";
							$pdf->rollbackTransaction(true);
							$pdf->addPage('', '', true);
							$posy	= $pdf->GetY();
							$this->pdfAddTitle($pdf, $object, $line, $label, $description, $posx, $posy, $w, $h);
							$posy	= $pdf->GetY();
							//print 'add ST'.$pdf->getPage().'<br />';
						} else {
							$pdf->commitTransaction();
						}
					}
					if ($object->element == 'delivery') {
						$pdf->SetTextColor(255, 255, 255);
					}
					$posy	= $pdf->GetY();
					return 1;
				} elseif (!empty($margin)) {
					$labelproductservice = pdf_getlinedesc($object, $i, $outputlangs, $parameters['hideref'], $parameters['hidedesc'], $parameters['issupplierline']);
					$labelproductservice = preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $labelproductservice, -1, $nbrep);
					if (!empty(getDolGlobalString('INFRASTRUCTURE_DISABLE_FIX_TRANSACTION'))) {
						/**
						 * TCPDF::startTransaction() committe la transaction en cours s'il y en a une,
						 * ce qui peut être problématique. Comme TCPDF::rollbackTransaction() ne fait rien
						 * si aucune transaction n'est en cours, on peut y faire appel sans problème pour revenir
						 * à l'état d'origine.
						 */
						$pdf->rollbackTransaction(true);
						$pdf->startTransaction();
						$pageBefore	= $pdf->getPage();
					}
					$pdf->writeHTMLCell($parameters['w'], $parameters['h'], $parameters['posx'], $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'J', true);
					if (!empty(getDolGlobalString('INFRASTRUCTURE_DISABLE_FIX_TRANSACTION'))) {
						$pageAfter	= $pdf->getPage();
						if ($pageAfter > $pageBefore) {
							//print "ST $pageAfter>$pageBefore<br>";
							$pdf->rollbackTransaction(true);
							$pdf->addPage('', '', true);
							$posy	= $pdf->GetY();
							$pdf->writeHTMLCell($parameters['w'], $parameters['h'], $parameters['posx'], $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'J', true);
							$posy	= $pdf->GetY();
							//print 'add ST'.$pdf->getPage().'<br />';
						} else {
							$pdf->commitTransaction();
						}
					}
					return 1;
				}
				return 0;
			} elseif (empty($object->lines[$parameters['i']])) {
				$this->resprints = -1;
			}
			return 0;
		}

		/**
		* Print object line
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param 	string			$action			Action
		* @param 	HookManager		$hookmanager	Hook manager
		* @return int
		*/
		public function printObjectLine($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $langs, $user, $db, $bc, $usercandelete, $toselect, $inputalsopricewithtax;

			$lineLabel	= "";
			$num		= &$parameters['num'];
			$line		= &$parameters['line'];
			$i			= &$parameters['i'];
			$var		= &$parameters['var'];
			$contexts	= explode(':', $parameters['context']);
			if ($parameters['currentcontext'] === 'paiementcard') return 0;
				$originline		= null;
				$newToken		= function_exists('newToken') ? newToken() : $_SESSION['newtoken'];
				$createRight	= $user->hasRight($object->element, 'creer');
				if ($object->element == 'facturerec' ) {
					$object->statut = 0; // hack for facture rec
					$createRight = $user->hasRight('facture', 'creer');
				} elseif ($object->element == 'order_supplier' ) {
					$createRight = $user->hasRight('fournisseur', 'commande', 'creer');
				} elseif ($object->element == 'invoice_supplier' ) {
					$createRight = $user->hasRight('fournisseur', 'facture', 'creer');
				} elseif ($object->element == 'commande' && in_array('ordershipmentcard', $contexts)) {
					// H4cK 4n0nYm0u$-style : $line n'est pas un objet instancié mais provient d'un fetch_object d'une requête SQL
					$line->id			= $line->rowid;
					$line->product_type = $line->type;
				} elseif ($object->element == 'shipping' || $object->element == 'delivery') {
					if (empty($line->origin_line_id) && !empty($line->fk_elementdet)) {
						$line->origin_line_id	= $line->fk_elementdet;
					}
					$originline = new OrderLine($db);
					$originline->fetch(!empty($line->origin_line_id) ? $line->origin_line_id : 0);
					foreach (get_object_vars($line) as $property => $value) {
						if (empty($originline->{ $property })) {
							$originline->{ $property } = $value;
						}
					}
					$line	= $originline;
				}
				$idvar		= $object->element=='facture' ? 'facid' : 'id';
				$isOuvrage	= !empty(isModEnabled('ouvrage')) && class_exists('Ouvrage') && Ouvrage::isOuvrage($line) ? 1 : 0;
				if ($line->special_code!=$this->module_number || $line->product_type!=9) {
					if ($object->statut == 0  && $createRight && getDolGlobalString('INFRASTRUCTURE_ALLOW_DUPLICATE_LINE') && $object->element !== 'invoice_supplier') {
						if (empty($line->fk_prev_id)) $line->fk_prev_id = null;
						if (($object->element != 'shipping' && $object->element != 'delivery')&& !(TInfrastructure::isModInfrastructureLine($line)) && ( $line->fk_prev_id === null ) && !($action == "editline" && GETPOST('lineid', 'int') == $line->id)) {
							echo '<a name="duplicate-'.((int) $line->id).'" href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=duplicate&lineid='.((int) $line->id).'&token='.$newToken.'"><i class="'.getDolGlobalString('MAIN_FONTAWESOME_ICON_STYLE').' fa-clone" aria-hidden="true"></i></a>';
							?>
								<script type="text/javascript">
									$(document).ready(function() {
										$("a[name='duplicate-<?php echo $line->id; ?>']").prependTo($('#row-<?php echo $line->id; ?>').find('.linecoledit'));
									});
								</script>
							<?php
						}
					}
					return 0;
				} elseif (in_array('invoicecard', $contexts) || in_array('invoicesuppliercard', $contexts) || in_array('propalcard', $contexts) || in_array('supplier_proposalcard', $contexts) || in_array('ordercard', $contexts) || in_array('ordersuppliercard', $contexts) || in_array('invoicereccard', $contexts)) {
					$line->description		= empty($line->description) ? $line->desc : $line->description;
					$TNonAffectedByMarge	= array('order_supplier', 'invoice_supplier', 'supplier_proposal');
					$affectedByMarge		= in_array($object->element, $TNonAffectedByMarge) ? 0 : 1;
					$colspan				= 5;
					if ($object->element == 'order_supplier') {$colspan = 6;}
					if ($object->element == 'invoice_supplier') {$colspan = 4;}
					if ($object->element == 'supplier_proposal') {$colspan = 3;}
					if (DOL_VERSION > 16.0 && empty(getDolGlobalString('MAIN_NO_INPUT_PRICE_WITH_TAX'))) {
						$colspan++; // Ajout de la colonne PU TTC
					}
					if ($object->element == 'facturerec') {$colspan = 5;}
					if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
						$colspan++; // Colonne PU Devise
						if (DOL_VERSION > 16.0 && empty(getDolGlobalString('MAIN_NO_INPUT_PRICE_WITH_TAX'))) {
							$colspan++; // Ajout de la colonne PU TTC
						}
					}
					if ($object->element == 'commande' && $object->statut < 3 && isModEnabled('shippableorder')) {$colspan++;}
					$margins_hidden_by_module = !isModEnabled('affmarges') ? false : !($_SESSION['marginsdisplayed']);
					if (isModEnabled('margin') && !$margins_hidden_by_module) {$colspan++;}
					if (isModEnabled('margin') && getDolGlobalString('DISPLAY_MARGIN_RATES') && !$margins_hidden_by_module && $affectedByMarge > 0) {$colspan++;}
					if (isModEnabled('margin') && getDolGlobalString('DISPLAY_MARK_RATES') && !$margins_hidden_by_module && $affectedByMarge > 0) {$colspan++;}
					if ($object->element == 'facture' && getDolGlobalString('INVOICE_USE_SITUATION') && $object->type == Facture::TYPE_SITUATION) {$colspan++;}
					if (getDolGlobalString('PRODUCT_USE_UNITS')) {$colspan++;}
					// Compatibility module showprice
					if (isModEnabled('showprice')) {$colspan++;}
					$data	= infrastructure_getHtmlData($parameters, $object, $action, $hookmanager);
					$class	= '';	// Prepare CSS class
					if (!empty(getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')))		$class	.= ' newInfrastructure';
					if ($line->qty > 0 && $line->qty < 10) {
						$class	.= ' subtitleLevel'.$line->qty;	// Sub-total level 1 to 9
					} elseif ($line->qty > 90 && $line->qty < 100) {
						$class	.= ' infrastructureLevel'.(100 - $line->qty);	// Sub-total level 99 (1) to 91 (9)
					} elseif ($line->qty == 50) {
						$class	.= ' infrastructureText';	// Free text
					}
					?>
					<!-- actions_infrastructure.class.php line <?php echo __LINE__; ?> -->
					<tr class="oddeven <?php echo $class; ?>" <?php echo $data; ?> rel="infrastructure" id="row-<?php echo $line->id ?>" style="<?php
					if (!empty(getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT'))) {
						$infrastructureBrightnessPercentage = getDolGlobalInt('INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE', 10);
						if ($line->qty <= 99 && $line->qty >= 91) {
							$infrastructureBackgroundColor = getDolGlobalString('INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR', '#adadcf');
							print 'background: none; background-color:'.colorLighten( $infrastructureBackgroundColor, ($line->qty < 99 ? (99 - $line->qty) * $infrastructureBrightnessPercentage : 1)).' !important';
						} elseif ($line->qty >= 1 && $line->qty <= 9) {
							$titleBackgroundColor = getDolGlobalString('INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', '#adadcf');
							print 'background: none; background-color:'.colorLighten( $titleBackgroundColor, ($line->qty > 1 ? ($line->qty - 1) * $infrastructureBrightnessPercentage : 1)).' !important';
						} elseif ($line->qty == 50) {	// Free text
							print '';
						}
						// À compléter si on veut plus de nuances de couleurs avec les niveaux 4,5,6,7,8 et 9
					} else {
						if ($line->qty == 99) {
							print 'background:#ddffdd';		// Sub-total level 1
						} elseif ($line->qty == 98) {
							print 'background:#ddddff;';	// Sub-total level 2
						} elseif ($line->qty == 2) {
							print 'background:#eeeeff; ';	// Title level 2
						} elseif ($line->qty == 50) {
							print '';						// Free text
						} else {
							print 'background:#eeffee;' ;						// Title level 1 and 3 to 9
						}
					}
					?>;">
					<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
						<td class="linecolnum"><?php echo $i + 1; ?></td>
					<?php } ?>
						<?php
						if ($object->element == 'order_supplier') {
							$colspan--;
						}
						if ($object->element == 'supplier_proposal') {
							$colspan += 2;
						}
						if ($object->element == 'invoice_supplier') {
							$colspan -= 2;
						}
						$line_show_qty = false;
						if (TInfrastructure::isInfrastructure($line)) {
							/* Total */
							$TInfrastructureDatas		= infrastructure_get_totalLineFromObject($object, $line, false, 1);
							$total_line					= $TInfrastructureDatas[0];
							$multicurrency_total_line	= $TInfrastructureDatas[6];
							$total_qty					= $TInfrastructureDatas[4];
							if ($show_qty_bu_deault = TInfrastructure::showQtyForObject($object)) {
								$line_show_qty	= TInfrastructure::showQtyForObjectLine($line, $show_qty_bu_deault);
							}
						}

					?>
					<?php
					if ($action == 'editline' && GETPOST('lineid', 'int') == $line->id && TInfrastructure::isModInfrastructureLine($line)) {
						include dol_buildpath('/infrastructure/core/tpl/infrastructureline_edit.tpl.php', 0);
					} else {
						include dol_buildpath('/infrastructure/core/tpl/infrastructureline_view.tpl.php', 0);
					}
					?>
					<?php
					if ($line->qty>90) {
						/* Total */
						echo '<td class="linecolht nowrap" align="right" style="font-weight:bold;" rel="infrastructure_total">'.price($total_line).'</td>';
						if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
							echo '<td class="linecoltotalht_currency right bold">'.price($multicurrency_total_line).'</td>';
						}
					} else {
						echo '<td class="linecolht movetitleblock">&nbsp;</td>';
						if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
							echo '<td class="linecoltotalht_currency">&nbsp;</td>';
						}
					}
					?>
					<td class="center nowrap linecoledit">						<?php
						if ($action != 'selectlines') {
							if ($action == 'editline' && GETPOST('lineid', 'int') == $line->id && TInfrastructure::isModInfrastructureLine($line) ) {
								?>
								<input id="savelinebutton" class="button" type="submit" name="save" value="<?php echo $langs->trans('Save') ?>" />
								<br />
								<input class="button" type="button" name="cancelEditlinetitle" value="<?php echo $langs->trans('Cancel') ?>" />
								<script type="text/javascript">
									$(document).ready(function() {
										$('input[name=cancelEditlinetitle]').click(function () {
											document.location.href="<?php echo '?'.$idvar.'='.$object->id ?>";
										});
									});

								</script>
								<?php
							} else {
								if ($object->statut == 0  && $createRight && getDolGlobalString('INFRASTRUCTURE_ALLOW_DUPLICATE_BLOCK') && $object->element !== 'invoice_supplier') {
									if (empty($line->fk_prev_id)) $line->fk_prev_id = null;
									if (TInfrastructure::isTitle($line) && ( $line->fk_prev_id === null )) {
										print '	<a class="infrastructure-line-action-btn" title="'.$langs->trans('InfrastructureCloneLInfrastructureBlock').'" href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=duplicate&lineid='.((int) $line->id).'&token='.$newToken.'" >
													<i class="'.getDolGlobalString('MAIN_FONTAWESOME_ICON_STYLE').' fa-clone" aria-hidden="true"></i>';
										print '	</a>';
									}
								}
								if ($object->statut == 0  && $createRight && getDolGlobalString('INFRASTRUCTURE_ALLOW_EDIT_BLOCK')) {
									print '		<a class="infrastructure-line-action-btn"  href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=editline&token='.$newToken.'&lineid='.((int) $line->id).'#row-'.((int) $line->id).'">'.img_edit().'</a>';
								}
							}
						}
						?>
					</td>
					<td class="center nowrap linecoldelete">						<?php
							if ($action != 'editline' && $action != 'selectlines') {
								if ($object->statut == 0  && $createRight && !empty(getDolGlobalString('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK'))) {
									$line->fk_prev_id	= empty($line->fk_prev_id) ? null : $line->fk_prev_id;
									if (!isset($line->fk_prev_id) || $line->fk_prev_id === null) {
										print '	<a class="infrastructure-line-action-btn"  href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=ask_deleteline&lineid='.((int) $line->id).'&token='.$newToken.'">'.img_delete().'</a>';
									}
									if (TInfrastructure::isTitle($line) && (!isset($line->fk_prev_id) || (isset($line->fk_prev_id) && ($line->fk_prev_id === null))) ) {
										$img_delete		= img_delete($langs->trans('InfrastructureDeleteWithAllLines'), ' style="color:#be3535 !important;" class="pictodelete pictodeleteallline"');
										print '	<a class="infrastructure-line-action-btn"  href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=ask_deleteallline&lineid='.((int) $line->id).'&token='.$newToken.'">'.$img_delete.'</a>';
									}
								}
							}
						?>
					</td>
					<?php
					if ($object->statut == 0  && $createRight && !empty(getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS')) && TInfrastructure::isTitle($line) && $action != 'editline' && $action != 'selectlines') {
						print '	<td class="infrastructure_nc">
									<input id="infrastructure_nc-'.$line->id.'" class="infrastructure_nc_chkbx" data-lineid="'.$line->id.'" type="checkbox" name="infrastructure_nc" value="1" '.(!empty($line->array_options['options_infrastructure_nc']) ? 'checked="checked"' : '').' />
								</td>';
					}
					if ($num > 1 && empty($conf->browser->phone)) { ?>
						<td class="center linecolmove tdlineupdown">						</td>
					<?php } else { ?>
						<td <?php echo ((empty($conf->browser->phone) && ($object->statut == 0  && $createRight ))?' class="center tdlineupdown"':' class="center"'); ?>></td>					<?php } ?>
					<?php
						$Telement	= array('propal', 'commande', 'facture', 'supplier_proposal', 'order_supplier', 'invoice_supplier');
						if (!empty(getDolGlobalString('MASSACTION_CARD_ENABLE_SELECTLINES')) && $object->status == $object::STATUS_DRAFT && $usercandelete && in_array($object->element, $Telement)|| $action == 'selectlines' ) { // dolibarr 8
							if ($action !== 'editline' && GETPOST('lineid', 'int') !== $line->id) {
								$checked	= '';
								if (!empty($toselect) && in_array($line->id, $toselect)) {
									$checked = 'checked';
								}
								if ($action != 'editline') {
									?>
										<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>"></td>
									<?php
								}
							}
						}
					?>
					</tr>
					<?php
					// Affichage des extrafields à la Dolibarr (car sinon non affiché sur les titres)
					if (TInfrastructure::isTitle($line) && getDolGlobalString('INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE')) {
						// Extrafields
						$extrafieldsline	= new ExtraFields($db);
						$extralabelsline	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
						$mode				= $action === 'editline' && $line->rowid == GETPOST('lineid', 'int') ? 'edit' : 'view';
						$ex_element			= $line->element;
						$line->element		= 'tr_extrafield_title '.$line->element; // Pour pouvoir manipuler ces tr
						$isExtraSelected	= false;
						$colspan			+= 3;
						print $line->showOptionals($extrafieldsline, $mode, array('style' => ' style="background:#eeffee;" ', 'colspan' => $colspan));
						foreach ($line->array_options as $option) {
							if (!empty($option) && $option != "-1") {
								$isExtraSelected = true;
								break;
							}
						}
						if ($mode === 'edit') {
							?>
							<script>
								$(document).ready(function () {
									var all_tr_extrafields = $("tr.tr_extrafield_title");
									<?php
									// Si un extrafield est rempli alors on affiche directement les extrafields
									if (!$isExtraSelected) {
										echo 'all_tr_extrafields.hide();';
										echo 'var trad = "'.$langs->trans('InfrastructureShowExtrafields').'";';
										echo 'var extra = 0;';
									} else {
										echo 'all_tr_extrafields.show();';
										echo 'var trad = "'.$langs->trans('InfrastructureHideExtrafields').'";';
										echo 'var extra = 1;';
									}
									?>
									$("div .infrastructure_underline").append(
										'<a id="printBlocExtrafields" onclick="return false;" href="#">' + trad + '</a>'
										+ '<input type="hidden" name="showBlockExtrafields" id="showBlockExtrafields" value="' + extra + '" />');
											$(document).on('click', "#printBlocExtrafields", function() {
												var btnShowBlock = $("#showBlockExtrafields");
												var val = btnShowBlock.val();
												if(val == '0') {
													btnShowBlock.val('1');
													$("#printBlocExtrafields").html("<?php print $langs->trans('InfrastructureHideExtrafields'); ?>");
													$(all_tr_extrafields).show();
												} else {
													btnShowBlock.val('0');
													$("#printBlocExtrafields").html("<?php print $langs->trans('InfrastructureShowExtrafields'); ?>");
													$(all_tr_extrafields).hide();
												}
									});
								});
							</script>
							<?php
						}
						$line->element = $ex_element;
					}
					print '<!-- END OF actions_infrastructure.class.php line '.__LINE__.' -->';
					return 1;
				} elseif (($object->element == 'commande' && in_array('ordershipmentcard', $contexts)) || (in_array('expeditioncard', $contexts) && $action == 'create')) {
					$colspan	= 4;
					$data		= infrastructure_getHtmlData($parameters, $object, $action, $hookmanager);
					$class		= '';
					if (!empty(getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT'))) {
						$class	.= ' newInfrastructure';
					}
					if ($line->qty > 0 && $line->qty < 10) {
						$class	.= ' subtitleLevel'.$line->qty;	// Sub-total level 1 to 9
					} elseif ($line->qty > 90 && $line->qty < 100) {
						$class	.= ' infrastructureLevel'.(100 - $line->qty);	// Sub-total level 99 (1) to 91 (9)
					} elseif ($line->qty == 50) {
						$class	.= ' infrastructureText';	// Free text
					}
					?>
					<!-- actions_infrastructure.class.php line <?php echo __LINE__; ?> -->
					<tr class="oddeven" <?php echo $data; ?> rel="infrastructure" id="row-<?php echo $line->id ?>" style="<?php
					if (getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')) {
						$infrastructureBrightnessPercentage = getDolGlobalInt('INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE', 10);
						if ($line->qty <= 99 && $line->qty >= 91) {
							$infrastructureBackgroundColor = getDolGlobalString('INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR', '#adadcf');
							print 'background: none; background-color:'.colorLighten( $infrastructureBackgroundColor, ($line->qty < 99 ? (99 - $line->qty) * $infrastructureBrightnessPercentage : 1)).' !important';
						} elseif ($line->qty >= 1 && $line->qty <= 9) {
							$titleBackgroundColor = getDolGlobalString('INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', '#adadcf');
							print 'background: none; background-color:'.colorLighten( $titleBackgroundColor, ($line->qty > 1 ? ($line->qty - 1) * $infrastructureBrightnessPercentage : 1)).' !important';
						} elseif ($line->qty == 50) {	// Free text
							print '';
						}
						// À compléter si on veut plus de nuances de couleurs avec les niveaux 4,5,6,7,8 et 9
					} else {
						if ($line->qty == 99) {
							print 'background:#ddffdd';		// Sub-total level 1
						} elseif ($line->qty==98) {
							print 'background:#ddddff;';	// Sub-total level 2
						} elseif ($line->qty==2) {
							print 'background:#eeeeff; ';	// Title level 2
						} elseif ($line->qty==50) {
							print '';						// Free text
						} else {
							print 'background:#eeffee;';	// Title level 1 and 3 to 9
						}
					}
					?>;">
					<td style="<?php TInfrastructure::isFreeText($line) ? '' : 'font-weight:bold;'; ?>  <?php echo ($line->qty > 90) ? 'text-align:right' : '' ?> "><?php
						if (getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')) {
							if (TInfrastructure::isTitle($line) || TInfrastructure::isInfrastructure($line)) {
								echo str_repeat('&nbsp;&nbsp;&nbsp;', max(floatval($line->qty) - 1, 0));
								if (TInfrastructure::isTitle($line)) {
									print img_picto('', 'infrastructure@infrastructure').'<span style="font-size:9px;margin-left:-3px;">'.$line->qty.'</span>&nbsp;&nbsp;';
								} else {
									print img_picto('', 'infrastructure2@infrastructure').'<span style="font-size:9px;margin-left:-1px;">'.(100-$line->qty).'</span>&nbsp;&nbsp;';
								}
							}
						} else {
							if ($line->qty <= 1) {
								print img_picto('', 'infrastructure@infrastructure');
							} elseif ($line->qty==2) {
								print img_picto('', 'subinfrastructure@infrastructure').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
							}
						}
						// Get display styles and apply them
						$titleStyleItalic		= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'I') === false ? '' : ' font-style: italic;';
						$titleStyleBold			= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'B') === false ? '' : ' font-weight:bold;';
						$titleStyleUnderline	= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'U') === false ? '' : ' text-decoration: underline;';
						if (empty($line->label)) {
							if ($line->qty >= 91 && $line->qty <= 99 && getDolGlobalInt('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL')) {
								print  $line->description.' '.infrastructure_getTitle($object, $line);
							} else {
								print  $line->description;
							}
						} else {
							if (getDolGlobalString('PRODUIT_DESC_IN_FORM') && !empty($line->description)) {
								print '<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" >'.$line->label.'</span><br><div class="infrastructure_desc">'.dol_htmlentitiesbr($line->description).'</div>';
							} else {
								print '<span class="infrastructure_label classfortooltip" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
							}
						}
						//if($line->qty>90) print ' : ';
						if (!empty($line->info_bits) && $line->info_bits > 0) echo img_picto($langs->trans('Pagebreak'), 'pagebreak@infrastructure');
						?>
					</td>
					<td colspan="<?php echo $colspan; ?>">
					<?php
						if (in_array('expeditioncard', $contexts) && $action == 'create') {
							$fk_entrepot = GETPOST('entrepot_id', 'int');
							?>
								<input type="hidden" name="idl<?php echo $i; ?>" value="<?php echo $line->id; ?>" />
								<input type="hidden" name="qtyasked<?php echo $i; ?>" value="<?php echo $line->qty; ?>" />
								<input type="hidden" name="qdelivered<?php echo $i; ?>" value="0" />
								<input type="hidden" name="qtyl<?php echo $i; ?>" value="<?php echo $line->qty; ?>" />
								<input type="hidden" name="entl<?php echo $i; ?>" value="<?php echo $fk_entrepot; ?>" />
							<?php
						}
					?>
					</td>
					</tr>
					<!-- END OF actions_infrastructure.class.php line <?php echo __LINE__; ?> -->
					<?php
					return 1;
				} elseif ($object->element == 'shipping' || $object->element == 'delivery') {
					global $form;
					$alreadysent		= $parameters['alreadysent'];
					$shipment_static	= new Expedition($db);
					$warehousestatic	= new Entrepot($db);
					$extrafieldsline	= new ExtraFields($db);
					$extralabelslines	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
					$colspan			= 4;
					if ($object->origin && $object->origin_id > 0) {
						$colspan++;
					}
					if (isModEnabled('stock')) {
						$colspan++;
					}
					if (isModEnabled('productbatch')) {
						$colspan++;
					}
					if ($object->statut == 0) {
						$colspan++;
					}
					if ($object->statut == 0 && !getDolGlobalString('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK')) {
						$colspan++;
					}
					if ($object->element == 'delivery') {
						$colspan = 2;
					}
					print '<!-- origin line id = '.$line->origin_line_id.' -->'; // id of order line
					// HTML 5 data for js
					$data	= infrastructure_getHtmlData($parameters, $object, $action, $hookmanager);
					$class	= '';
					if (!empty(getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')))		$class	.= ' newInfrastructure ';
						if ($line->qty > 0 && $line->qty < 10) {
							$class	.= ' subtitleLevel'.$line->qty;	// Sub-total level 1 to 9
						} elseif ($line->qty > 90 && $line->qty < 100) {
							$class	.= ' infrastructureLevel'.(100 - $line->qty);	// Sub-total level 99 (1) to 91 (9)
						} elseif ($line->qty == 50) {
							$class	.= ' infrastructureText';	// Free text
						}
						?>
						<!-- actions_infrastructure.class.php line <?php echo __LINE__; ?> -->
						<tr class="oddeven" <?php echo $data; ?> rel="infrastructure" id="row-<?php echo $line->id ?>" style="<?php
							if (getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')) {
								$infrastructureBrightnessPercentage = getDolGlobalInt('INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE', 10);
								if ($line->qty <= 99 && $line->qty >= 91) {
									$infrastructureBackgroundColor = getDolGlobalString('INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR', '#adadcf');
									print 'background: none; background-color:'.colorLighten( $infrastructureBackgroundColor, ($line->qty < 99 ? (99 - $line->qty) * $infrastructureBrightnessPercentage : 1)).' !important';
								} elseif ($line->qty >= 1 && $line->qty <= 9) {
									$titleBackgroundColor = getDolGlobalString('INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', '#adadcf');
									print 'background: none; background-color:'.colorLighten( $titleBackgroundColor, ($line->qty > 1 ? ($line->qty - 1) * $infrastructureBrightnessPercentage : 1)).' !important';
								} elseif ($line->qty == 50) {	// Free text
									print '';
								}
								// À compléter si on veut plus de nuances de couleurs avec les niveaux 4,5,6,7,8 et 9
							} else {
								if ($line->qty == 99) {
									print 'background:#ddffdd';		// Sub-total level 1
								} elseif ($line->qty == 98) {
									print 'background:#ddddff;';	// Sub-total level 2
								} elseif ($line->qty == 2) {
									print 'background:#eeeeff; ';	// Title level 2
								} elseif ($line->qty == 50) {
									print '';						// Free text
								} else {
									print 'background:#eeffee;';	// Title level 1, Sub-total level 1 and 3 to 9
								}
							}
						?>;">
						<?php
							// #
							if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
								print '<td align="center">'.($i+1).'</td>';
							}
							?>
						<td style="<?php TInfrastructure::isFreeText($line) ? '' : 'font-weight:bold;'; ?>  <?php echo ($line->qty > 90) ? 'text-align:right' : '' ?> "><?php
							if (getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')) {
								if (TInfrastructure::isTitle($line) || TInfrastructure::isInfrastructure($line)) {
									echo str_repeat('&nbsp;&nbsp;&nbsp;', max(floatval($line->qty) - 1, 0));
									if (TInfrastructure::isTitle($line)) {
										print img_picto('', 'infrastructure@infrastructure').'<span style="font-size:9px;margin-left:-3px;">'.$line->qty.'</span>&nbsp;&nbsp;';
									} else {
										print img_picto('', 'infrastructure2@infrastructure').'<span style="font-size:9px;margin-left:-1px;">'.(100 - $line->qty).'</span>&nbsp;&nbsp;';
									}
								}
							} else {
								if ($line->qty <= 1) {
									print img_picto('', 'infrastructure@infrastructure');
								} elseif ($line->qty==2) {
									print img_picto('', 'subinfrastructure@infrastructure').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
								}
							}
							// Get display styles and apply them
							$titleStyleItalic		= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'I') === false ? '' : ' font-style: italic;';
							$titleStyleBold			= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'B') === false ? '' : ' font-weight:bold;';
							$titleStyleUnderline	= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'U') === false ? '' : ' text-decoration: underline;';
							if (empty($line->label)) {
								if ($line->qty >= 91 && $line->qty <= 99 && getDolGlobalInt('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL')) {
									print  $line->description.' '.infrastructure_getTitle($object, $line);
								} else {
									print  $line->description;
								}
							} else {
								if (getDolGlobalString('PRODUIT_DESC_IN_FORM') && !empty($line->description)) {
									print '	<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" >'.$line->label.'</span><br><div class="infrastructure_desc">'.dol_htmlentitiesbr($line->description).'</div>';
								} else {
									print '	<span class="infrastructure_label classfortooltip " style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
								}
							}
							//if($line->qty>90) print ' : ';
							if ($line->info_bits > 0) {
								print img_picto($langs->trans('Pagebreak'), 'pagebreak@infrastructure');
							}
							?>
						</td>
						<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
						<?php
							if ($object->element == 'shipping' && $object->statut == 0 && getDolGlobalString('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK')) {
								print '<td class="linecoldelete nowrap" width="10">';
								$lineid				= $line->id;
								$line->fk_prev_id	= empty($line->fk_prev_id) ? null : $line->fk_prev_id;
								if ($line->element === 'commandedet') {
									foreach ($object->lines as $shipmentLine) {
										if ((!empty($shipmentLine->fk_elementdet)) && $shipmentLine->fk_origin == 'orderline' && $shipmentLine->fk_elementdet == $line->id) {
											$lineid = $shipmentLine->id;
										} elseif ((!empty($shipmentLine->fk_elementdet)) && $shipmentLine->fk_origin == 'orderline' && $shipmentLine->fk_elementdet == $line->id) {
											$lineid = $shipmentLine->id;
										}
									}
								}
								if ($line->fk_prev_id === null) {
									print '<a href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&amp;action=deleteline&amp;lineid='.((int) $lineid).'&token='.$newToken.'">'.img_delete().'</a>';
								}
								if (TInfrastructure::isTitle($line) && ($line->fk_prev_id === null) ) {
									$img_delete	 = img_delete($langs->trans('InfrastructureDeleteWithAllLines'), ' style="color:#be3535 !important;" class="pictodelete pictodeleteallline"');
									print '<a href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&amp;action=ask_deleteallline&amp;lineid='.((int) $lineid).'&token='.$newToken.'">'.$img_delete.'</a>';
								}
							print '	</td>';
						}
						print "</tr>\r\n";
						print "<!-- END OF actions_infrastructure.class.php -->\r\n";
						// Display lines extrafields
						if ($object->element == 'shipping' && getDolGlobalString('INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE') && is_array($extralabelslines) && count($extralabelslines) > 0) {
							$line	= new ExpeditionLigne($db);
							$line->fetch_optionals($line->id);
							print '<tr class="oddeven">';
							print $line->showOptionals($extrafieldsline, 'view', array('style' => $bc[$var], 'colspan' => $colspan), $i);
						}

					return 1;
				}
			return 0;
		}

		/**
		* Print origin object sub line
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param 	string			$action			Action
		* @param 	HookManager		$hookmanager	Hook manager
		* @return 	int
		*/
		public function printOriginObjectSubLine($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $restrictlist, $selectedLines;

			$line		= &$parameters['line'];
			$contexts	= explode(':', $parameters['context']);
			if (in_array('ordercard', $contexts) || in_array('invoicecard', $contexts) || in_array('ordersuppliercard', $contexts) || in_array('invoicesuppliercard', $contexts)) {
				if (class_exists('TInfrastructure')) {
					dol_include_once('/infrastructure/class/infrastructure.class.php');
				}
				if (TInfrastructure::isModInfrastructureLine($line)) {
					$object->tpl['infrastructure']	= $line->id;
					if (TInfrastructure::isTitle($line)) {
						$object->tpl['sub-type'] = 'title';
					} elseif (TInfrastructure::isInfrastructure($line)) {
						$object->tpl['sub-type'] = 'total';
					} elseif (TInfrastructure::isFreeText($line)) {
						$object->tpl['sub-type'] = 'freetext';
					}
					$object->tpl['sub-tr-style'] = '';
					if (!empty(getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT'))) {
						$object->tpl['sub-tr-class']	.= ' newInfrastructure';
					}
					if ($line->qty > 0 && $line->qty < 10) {
						$object->tpl['sub-tr-class']	.= ' subtitleLevel'.$line->qty;			// Sub-total level 1 to 9
					} elseif ($line->qty > 90 && $line->qty < 100) {
						$object->tpl['sub-tr-class']	.= ' infrastructureLevel'.(100 - $line->qty);	// Sub-total level 99 (1) to 91 (9)
					} elseif ($line->qty == 50) {
						$object->tpl['sub-tr-class']	.= ' infrastructureText';						// Free text
					}
					if (getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')) {
						$infrastructureBrightnessPercentage		= getDolGlobalInt('INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE', 10);
						if ($line->qty <= 99 && $line->qty >= 91) {
							$infrastructureBackgroundColor		= getDolGlobalString('INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR', '#adadcf');
							$object->tpl['sub-tr-style']	= 'background: none; background-color:'.colorLighten( $infrastructureBackgroundColor, ($line->qty < 99 ? (99 - $line->qty) * $infrastructureBrightnessPercentage : 1)).' !important';
						} elseif ($line->qty >= 1 && $line->qty <= 9) {
							$titleBackgroundColor			= getDolGlobalString('INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', '#adadcf');
							$object->tpl['sub-tr-style']	= 'background: none; background-color:'.colorLighten( $titleBackgroundColor, ($line->qty > 1 ? ($line->qty - 1) * $infrastructureBrightnessPercentage : 1)).' !important';
						} elseif ($line->qty == 50) {	// Free text
							$object->tpl['sub-tr-style']	= '';
						}
						// À compléter si on veut plus de nuances de couleurs avec les niveaux 4,5,6,7,8 et 9
					} else {
						if ($line->qty == 99) {
							$object->tpl['sub-tr-style']	.= 'background:#ddffdd';	// Sub-total level 1
						} elseif ($line->qty == 98) {
							$object->tpl['sub-tr-style']	.= 'background:#ddddff;';	// Sub-total level 2
						} elseif ($line->qty==2) {
							$object->tpl['sub-tr-style']	.= 'background:#eeeeff; ';	// Title level 2
						} elseif ($line->qty==50) {
							$object->tpl['sub-tr-style']	.= '';						// Free text
						} else {
							$object->tpl['sub-tr-style']	.= 'background:#eeffee;';	// Title level 1, Sub-total level 1 and 3 to 9
						}
					}
					$object->tpl['sub-td-style'] = '';
					if ($line->qty > 90) {
						$object->tpl['sub-td-style'] = 'style="text-align:right"';
					}
					if (getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT')) {
						if (TInfrastructure::isTitle($line) || TInfrastructure::isInfrastructure($line)) {
							$object->tpl["sublabel"]	= str_repeat('&nbsp;&nbsp;&nbsp;', max(floatval($line->qty) - 1, 0));
							if (TInfrastructure::isTitle($line)) {
								$object->tpl["sublabel"].= img_picto('', 'infrastructure@infrastructure').'<span style="font-size:9px;margin-left:-3px;">'.$line->qty.'</span>&nbsp;&nbsp;';
							} else {
								$object->tpl["sublabel"].= img_picto('', 'infrastructure2@infrastructure').'<span style="font-size:9px;margin-left:-1px;">'.(100-$line->qty).'</span>&nbsp;&nbsp;';
							}
						}
					} else {
						$object->tpl["sublabel"] = '';
						if ($line->qty <= 1 ) {
							$object->tpl["sublabel"] = img_picto('', 'infrastructure@infrastructure');
						} elseif ($line->qty == 2) {
							$object->tpl["sublabel"] = img_picto('', 'subinfrastructure@infrastructure').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						}
					}
					// Get display styles and apply them
					$titleStyleItalic		= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'I') === false ? '' : ' font-style: italic;';
					$titleStyleBold			=  strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'B') === false ? '' : ' font-weight:bold;';
					$titleStyleUnderline	=  strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE'), 'U') === false ? '' : ' text-decoration: underline;';
					if (empty($line->label)) {
						if ($line->qty >= 91 && $line->qty <= 99 && getDolGlobalInt('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL')) {
							$object->tpl["sublabel"].=  $line->description.' '.infrastructure_getTitle($object, $line);
						} else {
							$object->tpl["sublabel"]	= ($object->tpl["sublabel"] ?? '').$line->description;
						}
					} else {
						if (getDolGlobalString('PRODUIT_DESC_IN_FORM') && !empty($line->description)) {
							$object->tpl["sublabel"]	.= '<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" >'.$line->label.'</span><br><div class="infrastructure_desc">'.dol_htmlentitiesbr($line->description).'</div>';
						} else {
							$object->tpl["sublabel"]	.= '<span class="infrastructure_label classfortooltip" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
						}
					}
					if ($line->qty>90) {
						$total						= infrastructure_get_totalLineFromObject($object, $line, false);
						$object->tpl["sublabel"]	.= ' : <b>'.$total.'</b>';
					}
					$object->printOriginLine($line, '', $restrictlist, '/core/tpl', $selectedLines);
					unset($object->tpl["sublabel"]);
					unset($object->tpl['sub-td-style']);
					unset($object->tpl['sub-tr-style']);
					unset($object->tpl['sub-type']);
					unset($object->tpl['infrastructure']);
					return 1;
				}
			}
			return 0;
		}

		/**
		* For compatibility with dolibarr <= v14
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int
		*/
		public function printOriginObjectLine($parameters, $object, &$action, $hookmanager)
		{
			return $this->printOriginObjectSubLine($parameters, $object, $action, $hookmanager);
		}

		/**
		* Add more actions buttons
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$object			Object
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return int
		*/
		public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
			global $langs, $db, $conf;

			if ($object->statut == 0 && getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') && $action != 'editline') {
				if ($object->element == 'invoice_supplier' || $object->element == 'order_supplier') {
					foreach ($object->lines as $line) {
						// fetch optionals attributes and labels
						$extrafields=new ExtraFields($this->db);
						$extralabels=$extrafields->fetch_name_optionals_label($object->table_element_line, true);
						$line->fetch_optionals($line->id, $extralabels);
					}
				}
				$TSubNc = array();
				foreach ($object->lines as &$l) {
					$TSubNc[$l->id] = (int) ($l->array_options['options_infrastructure_nc'] ?? 0);
				}
				print '<script type="text/javascript" src="'.dol_buildpath('infrastructure/js/infrastructure.lib.js', 1).'"></script>';
				$form = new Form($db);
				?>
				<script type="text/javascript">
					$(function () {
						var infrastructure_TSubNc = <?php echo json_encode($TSubNc); ?>;
						$("#tablelines tr").each(function (i, item) {
							if ($(item).children('.infrastructure_nc').length == 0) {
								var id = $(item).attr('id');
								if ((typeof id != 'undefined' && id.indexOf('row-') == 0) || $(item).hasClass('liste_titre')) {
									let tableNCColSelector = 'td';
									if ($(item).hasClass('liste_titre') && $(item).children('th:last-child').length > 0 && $(item).children('td:last-child').length == 0) {
										tableNCColSelector = 'th'; // In Dolibarr V20.0 title use th instead of td
									}
									$(item).children(`${tableNCColSelector}:last-child`).before(`<${tableNCColSelector} class="infrastructure_nc"></${tableNCColSelector}>`);
									if ($(item).attr('rel') != 'infrastructure' && typeof $(item).attr('id') != 'undefined') {
										var idSplit = $(item).attr('id').split('-');
										$(item).children(`${tableNCColSelector}.infrastructure_nc`).append($('<input type="checkbox" id="infrastructure_nc-' + idSplit[1] + '" class="infrastructure_nc_chkbx" data-lineid="' + idSplit[1] + '" value="1" ' + (typeof infrastructure_TSubNc[idSplit[1]] != 'undefined' && infrastructure_TSubNc[idSplit[1]] == 1 ? 'checked="checked"' : '') + ' />'));
									}
								} else {
									$(item).append('<td class="infrastructure_nc"></td>');
								}
							}
						});
						$('#tablelines tr.liste_titre:first .infrastructure_nc').html(<?php echo json_encode($form->textwithtooltip($langs->trans('infrastructure_nc_title'), $langs->trans('infrastructure_nc_title_help'))); ?>);
						function callAjaxUpdateLineNC(set, lineid, infrastructure_nc) {
							$.ajax({
								url: '<?php echo dol_buildpath('/infrastructure/script/interface.php', 1); ?>'
								, type: 'POST'
								, data: {
									json: 1
									, set: set
									, element: '<?php echo dol_escape_js($object->element); ?>'
									, elementid: <?php echo (int)$object->id; ?>
									, lineid: lineid
									, infrastructure_nc: infrastructure_nc
									, token: '<?php echo newToken(); ?>'
								}
							}).done(function (response) {
								window.location.href = window.location.pathname + '?id=<?php echo $object->id; ?>&page_y=' + window.pageYOffset;
							});
						}
						$(".infrastructure_nc_chkbx").change(function (event) {
							var lineid = $(this).data('lineid');
							var infrastructure_nc = 0 | $(this).is(':checked'); // Renvoi 0 ou 1
							callAjaxUpdateLineNC('updateLineNC', lineid, infrastructure_nc);
						});

					});

				</script>
				<?php
			}
			infrastructure_ajaxBlockOrderJs($object);
			// Pass Oblyon sticky flags to summary menu JS for scroll offset compensation
			$isOblyon	= isModEnabled('oblyon') && isset($conf->theme) && $conf->theme == 'oblyon';
			$jsConfig	= array('langs'						=> array('InfrastructureSummaryTitle' => $langs->trans('InfrastructureQuickSummary')),
								'useOldSplittedTrForLine'	=> intval(DOL_VERSION) < 16 ? 1 : 0,
								'isOblyon'					=> $isOblyon ? 1 : 0,
								'fixArearefCard'			=> $isOblyon ? getDolGlobalInt('FIX_AREAREF_CARD') : 0,
								'fixStickyTabsCard'			=> $isOblyon ? getDolGlobalInt('FIX_STICKY_TABS_CARD') : 0
						);
			print '<script type="text/javascript"> if (typeof infrastructureSummaryJsConf === undefined) { var infrastructureSummaryJsConf = {}; } infrastructureSummaryJsConf = '.json_encode($jsConfig).'; </script>'; // used also for infrastructure.lib.js
			if (!getDolGlobalString('INFRASTRUCTURE_DISABLE_SUMMARY')) {
				$jsConfig	= array('langs'						=> array('InfrastructureSummaryTitle' => $langs->trans('InfrastructureQuickSummary')),
									'useOldSplittedTrForLine'	=> intval(DOL_VERSION) < 16 ? 1 : 0
								);
				print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('infrastructure/css/summary-menu.css.php', 1).'">';
				print '<script type="text/javascript" src="'.dol_buildpath('infrastructure/js/summary-menu.js', 1).'"></script>';
			}
			return 0;
		}

		/**
		* After PDF creation
		*
		* @param	array		$parameters		Parameters
		* @param	TCPDF		$pdf			PDF
		* @param	string		$action			Action
		* @param	HookManager	$hookmanager	Hook manager
		* @return	int
		*/
		public function afterPDFCreation($parameters, &$pdf, &$action, $hookmanager)
		{
			$object = $parameters['object'];
			if ((getDolGlobalString('INFRASTRUCTURE_PROPAL_ADD_RECAP') && $object->element == 'propal') || (getDolGlobalString('INFRASTRUCTURE_COMMANDE_ADD_RECAP') && $object->element == 'commande') || (getDolGlobalString('INFRASTRUCTURE_INVOICE_ADD_RECAP') && $object->element == 'facture')) {
				if (GETPOST('infrastructure_add_recap', 'int') && empty($parameters['fromInfraS'])) {
					TInfrastructure::addRecapPage($parameters, $pdf);
				}
			}
			return 0;
		}

		/**
		* Overloading the getlinetotalremise function : replacing the parent's function with the one below
		*
		* @param	array		$parameters		Meta datas of the hook (context, etc...)
		* @param	CommonObject$object			The object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string		$action			Current action (if set). Generally create or edit or null
		* @param	HookManager	$hookmanager	Current hook manager
		* @return	int
		*/
		function getlinetotalremise($parameters, &$object, &$action, $hookmanager)
		{
			// Si c'est une ligne de sous-total, la méthode pdfGetLineTotalDiscountAmount ne doit rien renvoyer
			if (!empty($object->lines[$parameters['i']]) && TInfrastructure::isModInfrastructureLine($object->lines[$parameters['i']])) {
				$this->resprints	= '';
				$this->results		= [];
				return 1;
			}
			return 0;
		}

		/**
		* Overloading the defineColumnField function
		*
		* @param	array								$parameters		Hook metadatas (context, etc...)
		* @param	CommonDocGenerator|ModelePDFStatic	$pdfDoc			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string								$action			Current action (if set). Generally create or edit or null
		* @param	HookManager 						$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int									< 0 on error, 0 on success, 1 to replace standard code
		*/
		public function defineColumnField($parameters, &$pdfDoc, &$action, $hookmanager)
		{
			// If this model is column field compatible it will add info to change infrastructure behavior
			$parameters['object']->context['infrastructurePdfModelInfo']->cols = $pdfDoc->cols;
			$parameters['object']->context['infrastructurePdfModelInfo']->cols = $pdfDoc->cols;
			// HACK Pour passer les paramettres du model dans les hooks sans infos
			$parameters['object']->context['infrastructurePdfModelInfo']->marge_droite 	= $pdfDoc->marge_droite;
			$parameters['object']->context['infrastructurePdfModelInfo']->marge_gauche 	= $pdfDoc->marge_gauche;
			$parameters['object']->context['infrastructurePdfModelInfo']->page_largeur 	= $pdfDoc->page_largeur;
			$parameters['object']->context['infrastructurePdfModelInfo']->page_hauteur 	= $pdfDoc->page_hauteur;
			$parameters['object']->context['infrastructurePdfModelInfo']->format			= $pdfDoc->format;
			if (property_exists($pdfDoc, 'context') && array_key_exists('infrastructurePdfModelInfo', $pdfDoc->context) && is_object($pdfDoc->context['infrastructurePdfModelInfo'])) {
				$parameters['object']->context['infrastructurePdfModelInfo']->defaultTitlesFieldsStyle	= $pdfDoc->context['infrastructurePdfModelInfo']->defaultTitlesFieldsStyle;
				$parameters['object']->context['infrastructurePdfModelInfo']->defaultContentsFieldsStyle	= $pdfDoc->context['infrastructurePdfModelInfo']->defaultContentsFieldsStyle;
			}
			return 0;
		}

		/**
		* Re-generate the document after creation of recurring invoice by cron
		*
		* @param	array				$parameters		Hook metadatas (context, etc...)
		* @param	CommonDocGenerator	$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string				$action			Current action (if set). Generally create or edit or null
		* @param	HookManager			$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int									< 0 on error, 0 on success, 1 to replace standard code
		*/
		public function afterCreationOfRecurringInvoice($parameters, &$object, &$action, $hookmanager)
		{
			$TSub	= new TInfrastructure;
			$TSub->generateDoc($object);
			return 0;
		}

		/**
		* Print common footer
		*
		* @param	array			$parameters		Parameters
		* @param	CommonObject	$objectHook		Object hook
		* @param	string			$action			Action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int
		*/
		public function printCommonFooter(&$parameters, &$objectHook, &$action, $hookmanager)
		{
			global $langs, $db, $conf;

			$contextArray = explode(':', $parameters['context']);
			/**Gestion des dossiers qui permettent de réduire un bloc**/
			if (in_array('invoicecard', $contextArray)
					|| in_array('invoicesuppliercard', $contextArray)
					|| in_array('propalcard', $contextArray)
					|| in_array('ordercard', $contextArray)
					|| in_array('ordersuppliercard', $contextArray)
					|| in_array('invoicereccard', $contextArray)
				) {
				$id					= !empty(GETPOSTINT('id')) ? GETPOSTINT('id') : GETPOSTINT('facid');	//On récupère les informations de l'objet actuel
				$TCurrentContexts	= explode('card', $parameters['currentcontext']);	//On détermine l'élement concernée en fonction du contexte
				/**
				 *  TODO John le 11/08/2023 : Je trouve bizarre d'utiliser le contexte pour déterminer la class de l'objet alors
				 *    que l'objet est passé en paramètres ça doit être due à de vielle versions de Dolibarr ou une compat avec un module externe...
				 *    Cette methode de chargement d'objet a causée une fatale car la classe de l'objet correspondant au contexte n'était pas chargé ce qui n'est pas logique...
				 *    La logique voudrait que l'on utilise $object->element
				 *    Cependant si on regarde plus loin $object qui est passé en référence dans les paramètres de cette méthode est remplacé quelques lignes plus bas.
				 */
				if ($TCurrentContexts[0] == 'order') {
					$element = 'Commande';
					if (!class_exists($element)) { include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';} 
				} elseif ($TCurrentContexts[0] == 'invoice') {
					$element = 'Facture';
					if (!class_exists($element)) { include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';}
				} elseif ($TCurrentContexts[0] == 'invoicesupplier') {
					$element = 'FactureFournisseur';
					if (!class_exists($element)) { include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';}
				}  elseif ($TCurrentContexts[0] == 'ordersupplier') {
					$element = 'CommandeFournisseur';
					if (!class_exists($element)) { include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';}
				} elseif ($TCurrentContexts[0] == 'invoicerec') {
					$element = 'FactureRec';
					if (!class_exists($element)) { include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';}
				} else $element = $TCurrentContexts[0];
				if (!class_exists($element)) {
					// Pour éviter la fatale sur une page d'un module externe qui utiliserait un nom de context de Dolibarr mais qui
					$this->error = $langs->trans('ErrorClassXNotExists', $element);
					return -1;
				}
				$object			= new $element($db);
				$object->fetch($id);
				$TLines			= TInfrastructure::getAllTitleFromDocument($object);	//On récupère tous les titres sous-total
				$TBlocksToHide	= array();	//On définit quels sont les blocs à cacher en fonction des données existantes (hideblock)
				$hideMode		= getDolGlobalString('INFRASTRUCTURE_BLOC_FOLD_MODE', 'default');
				$hideMode		= in_array($hideMode, ['default', 'keepTitle']) ? $hideMode : 'default';
				if (!empty($TLines)) {
					foreach ($TLines as $line) {
						if (array_key_exists('options_hideblock', $line->array_options) && $line->array_options['options_hideblock']) $TBlocksToHide[] = $line->id;
					}
				}
				$jsConf	= array('linesToHide'			=> $TBlocksToHide,
								'hideFoldersByDefault'	=> getDolGlobalInt('INFRASTRUCTURE_HIDE_FOLDERS_BY_DEFAULT'),
								'closeMode'				=> $hideMode, // default, keepTitle
								'interfaceUrl'			=> dol_buildpath('/infrastructure/script/interface.php', 1),
								'token'					=> newToken(),
								'element'				=> $element,
								'element_id'			=> $id,
								'img_folder_closed' 	=> img_picto('', 'folder'),
								'img_folder_open'		=> img_picto('', 'folder-open'),
								'langs'					=> array('Infrastructure_HideAll'		=> $langs->transnoentities('Infrastructure_HideAll'),
																'Infrastructure_ShowAll'		=> $langs->transnoentities('Infrastructure_ShowAll'),
																'Infrastructure_Hide'			=> $langs->transnoentities('Infrastructure_Hide'),
																'Infrastructure_Show'			=> $langs->transnoentities('Infrastructure_Show'),
																'Infrastructure_ForceHideAll'	=> $langs->transnoentities('Infrastructure_ForceHideAll'),
																'Infrastructure_ForceShowAll'	=> $langs->transnoentities('Infrastructure_ForceShowAll')
															)
														);
				print '<script type="text/javascript" src="'.dol_buildpath('infrastructure/js/infrastructure.lib.js', 1).'"></script>';
				?>
					<style>
						.fold-infrastructure-container{
							-webkit-user-select: none; /* Safari */
							-ms-user-select: none; /* IE 10 and IE 11 */
							user-select: none; /* Standard syntax */
						}

				.toggle-all-folder-status, .fold-infrastructure-btn {
					cursor: pointer;
				}

				.fold-infrastructure-btn[data-toggle-all-children="1"] {
					color: rgb(190, 53, 53);
				}

				.toggle-all-folder-status:hover, .fold-infrastructure-btn:hover {
					color: var(--colortextlink, rgb(10, 20, 100));
				}

				.fold-infrastructure-btn[data-toggle-all-children="1"]:hover {
					color: rgb(138, 28, 28);
				}
			</style>
			<script type="text/javascript">
				// TODO : mettre ça dans une classe js
				$(document).ready(function () {
					// Utilisation d'une sorte de namespace en JS
					infrastructureFolders = {};
					(function (o) {
						o.config = <?php print json_encode($jsConf); ?> ;
						/**
						 * Dolibarr token
						 * @type {string}
						 */
						o.newToken = o.config.token || '';
						/**
						 *
						 * @param {int} titleId
						 */
						o.countHiddenLinesForTitle = function (titleId) {
							let $titleLine = $('#row-' + titleId);
							let childrenList = getInfrastructureTitleChilds($titleLine, true); // renvoi la liste des id des enfants
							let totalHiddenLines = 0;
							if (childrenList.length > 0) {
								childrenList.forEach((childLineId) => {
									let $childLine = $('#' + childLineId);
									if (!$childLine.is(":visible")) {
										totalHiddenLines++;
									}
								});
							}
							return totalHiddenLines;
						}
						/**
						 * Mise à jour des titres parents pour l'affichage du nombre de lignes cachées
						 * @param {jQuery}  $childTilteLine la ligne de titre enfant
						 */
						o.updateHiddenLinesCountInfoForParentTitles = function ($childTilteLine) {
							let parentTitles = o.getTitleParents($childTilteLine);
							if (parentTitles.length > 0) {
								parentTitles.forEach((parentTitleLineId) => {
									let $titleCollapseInfos = $('.fold-infrastructure-info[data-title-line-target="' + parentTitleLineId + '"]');
									if ($titleCollapseInfos.length > 0) {
										let totalHiddenLines = o.countHiddenLinesForTitle(parentTitleLineId);
										$titleCollapseInfos.html('(' + totalHiddenLines + ')');
										if (totalHiddenLines == 0) {
											$titleCollapseInfos.html('');
										}
									}
								});
							}
						}
						/**
						 * @param {jQuery}  $childLine
						 * @param {int} titleId
						 */
						o.addTitleParentId = function ($childLine, titleId) {
							// Ajoute l'id parent si se n'est pas déja fait
							let parentTitleIds = $childLine.attr('data-parent-titles');
							if (parentTitleIds != null) {
								let parentTitleIdsList = parentTitleIds.split(",");
								if (!parentTitleIdsList.includes(titleId)) {
									$childLine.attr('data-parent-titles', parentTitleIds + ',' + titleId);
								}
							} else {
								$childLine.attr('data-parent-titles', titleId);
							}
						}
						/**
						 * @param {jQuery}  $childLine
						 * @param {int} titleId
						 * @return []
						 */
						o.getTitleParents = function ($childLine) {
							let result = [];
							let parentTitleIds = $childLine.attr('data-parent-titles');
							if (parentTitleIds != null) {
								return parentTitleIds.split(",");
							}
							return result;
						}
						/**
						 *
						 * @param {int} titleId
						 * @param toggleStatus : open, closed
						 */
						o.toggleChildFolderStatusDisplay = function (titleId, toggleStatus = 'open') {
							let $titleLine			= $('#row-' + titleId);
							let $collapseBtn		= $('.fold-infrastructure-btn[data-title-line-target="' + titleId + '"]');
							let $collapseSimpleBtn	= $('.fold-infrastructure-btn[data-title-line-target="' + titleId + '"][data-toggle-all-children="0"]');
							let $collapseAllBtn		= $('.fold-infrastructure-btn[data-title-line-target="' + titleId + '"][data-toggle-all-children="1"]');
							let $collapseInfos		= $('.fold-infrastructure-info[data-title-line-target="' + titleId + '"]');
							if ($titleLine.length > 0) {
								$titleLine.attr('data-folder-status', toggleStatus);
								let haveTitle		= false;
								let childrenList	= getInfrastructureTitleChilds($titleLine, true); // renvoi la liste des id des enfants
								let totalHiddenLines= 0;
								if (childrenList.length > 0) {
									let doNotDisplayLines = []; // Dans le cas de l'ouverture il faut vérifier que les titres enfants ne sont pas fermés avant d'ouvrir
									let doNotHiddeLines = []; // En mode keepTitle: Dans le cas de la fermeture il faut vérifier que les titres enfants ne sont pas ouvert avant de fermer
									childrenList.forEach((childLineId) => {
										let $childLine = $('#' + childLineId);
										if ($childLine.attr('data-isinfrastructure') == "title") {
											// Ajoute l'id parent si se n'est pas déja fait
											o.addTitleParentId($childLine, titleId);
											haveTitle = true;
											// Dans le cas de l'ouverture il faut vérifier que les titres enfants ne sont pas fermés avant d'ouvrir
											let grandChildrenList = getInfrastructureTitleChilds($childLine, true); // renvoi la liste des id des enfants
											if ($childLine.attr('data-folder-status') == "closed") {
												doNotDisplayLines = doNotDisplayLines.concat(grandChildrenList);
											} else if (o.config.closeMode == 'keepTitle' && $childLine.attr('data-folder-status') == "open") {
												doNotHiddeLines = doNotDisplayLines.concat(grandChildrenList);
											}
										}
										if (toggleStatus == 'closed') {
											if (o.config.closeMode == 'keepTitle' && ($childLine.attr('data-isinfrastructure') == "title" || $childLine.attr('data-isinfrastructure') == "infrastructure")) {
												$childLine.show();
											} else if (!doNotHiddeLines.includes(childLineId)) {
												$childLine.hide();
											}
										} else {
											if (!doNotDisplayLines.includes(childLineId)) {
												$childLine.show();
											}
										}
										if (!$childLine.is(":visible")) {
											totalHiddenLines++;
										}
									});
								}
								$collapseInfos.html('(' + totalHiddenLines + ')');
								if (totalHiddenLines == 0) {
									$collapseInfos.html('');
								}
								// Mise à jour des parents pour l'affichage du nombre de lignes cachées
								o.updateHiddenLinesCountInfoForParentTitles($titleLine);
								if (toggleStatus == 'closed') {
									$collapseBtn.html(o.config.img_folder_closed);
									$collapseSimpleBtn.attr('title', o.config.langs.Infrastructure_Show);
									$collapseAllBtn.attr('title', o.config.langs.Infrastructure_ForceShowAll);
								} else {
									$collapseBtn.html(o.config.img_folder_open);
									$collapseSimpleBtn.attr('title', o.config.langs.Infrastructure_Hide);
									$collapseAllBtn.attr('title', o.config.langs.Infrastructure_ForceHideAll);
								}
								// Si pas de titre pas besoin d'afficher le bouton dossier rouge
								if (haveTitle) {
									$collapseAllBtn.show();
								} else {
									$collapseAllBtn.hide();
								}
							}
						}
						// initialisation des lignes affichées ou non
						$('tr[data-isinfrastructure="title"]').each(function () {
							let lineId = $(this).attr('data-id');
							if (lineId != null) {
								if (o.config.linesToHide.includes(lineId)) {
									o.toggleChildFolderStatusDisplay(lineId, 'closed');
								} else {
									if (o.config.hideFoldersByDefault == 1) {
										o.toggleChildFolderStatusDisplay(lineId, 'closed');
									} else {
										o.toggleChildFolderStatusDisplay(lineId, 'open');
									}
								}
							}
						});
						// Lors du clic sur un dossier, on cache ou faire apparaitre les lignes contenues dans le bloc concerné
						$(document).on("click", ".fold-infrastructure-btn", function (event) {
							event.preventDefault();
							let targetTitleLineId = $(this).attr('data-title-line-target');
							if (targetTitleLineId != undefined) {
								// folderManage_click(targetTitleLineId);
								let titleRow = $('#row-' + targetTitleLineId);
								let newStatus = titleRow.attr('data-folder-status') == 'closed' ? 'open' : 'closed'
								let sendData = {
									element: o.config.element,
									element_id: o.config.element_id,
									titleStatusList: [{
										'id': targetTitleLineId,
										'status': newStatus !== 'closed' ? 0 : 1,
									}]
								};
								/**
								 * Pour les boutons de type "block" bouton pour ouvrir / fermer tous les blocs enfants (ex dossier rouge)
								 **/
								if ($(this).attr('data-toggle-all-children') == '1') { //o.config.closeMode == 'keepTitle'
									let childrenList = getInfrastructureTitleChilds(titleRow, true); // renvoi la liste des id des enfants
									if (childrenList.length > 0) {
										childrenList.forEach((childLineId) => {
											let $childLine = $('#' + childLineId);
											if ($childLine.attr('data-isinfrastructure') == "title") {
												sendData.titleStatusList.push({
													'id': $childLine.attr('data-id'),
													'status': newStatus !== 'closed' ? 0 : 1,
												});
												o.toggleChildFolderStatusDisplay($childLine.attr('data-id'), newStatus);
											}
										});
									}
								}
								o.toggleChildFolderStatusDisplay(targetTitleLineId, newStatus); // devrait être dans le callback ajax success mais pour plus d'ergonomie et rapidité de feedback je le sort
								o.callInterface('set', 'update_hideblock_data', sendData, function (response) {
									// TODO gérer un retour en cas d'érreur
									// o.toggleChildFolderStatusDisplay(targetTitleLineId, newStatus);
								})
							}
						});
						//Fonction qui permet d'ajouter l'option "Cacher les lignes" ou "Afficher les lignes"
						$('#tablelines>tbody:first').prepend(
							'<tr>' +
							'	<td colspan="100%" style="  text-align:right ">' +
							'		<span id="hide_all"  class="toggle-all-folder-status" data-folder-status="closed" >' + o.config.img_folder_open + '&nbsp;' + o.config.langs.Infrastructure_HideAll + '</span>' +
							'		&nbsp;' +
							'		<span id="show_all" class="toggle-all-folder-status" data-folder-status="open"  >' + o.config.img_folder_closed + '&nbsp;' + o.config.langs.Infrastructure_ShowAll + '</span>' +
							'	</td>' +
							'</tr>'
						);
						// Lors du clic sur un dossier, on cache ou faire apparaitre les lignes contenues dans le bloc concerné
						$(document).on("click", ".toggle-all-folder-status", function (event) {
							event.preventDefault();
							newStatus = $(this).attr('data-folder-status');
							$(this).fadeOut();
							let sendData = {
								element: o.config.element,
								element_id: o.config.element_id,
								titleStatusList: []
							};
							$('#tablelines tr[data-isinfrastructure=title]').each(function (index) {
								sendData.titleStatusList.push({
									'id': $(this).attr('data-id'),
									'status': newStatus !== 'closed' ? 0 : 1,
								});

								//TODO manage response feedback to rollback display on error
								o.toggleChildFolderStatusDisplay($(this).attr('data-id'), newStatus);
							});
							o.callInterface('set', 'update_hideblock_data', sendData, function (response) {
								// $('#tablelines tr[data-isinfrastructure=title]').each(function( index ) {
								// 	//TODO manage response feedback
								// });
							});
							$(this).fadeIn();
						});
						o.checkListOfLinesIdHaveTitle = function (childrenList) {
							if (!Array.isArray(childrenList)) {
								return false;
							}
							childrenList.forEach((childLineId) => {
								let $childLine = $('#' + childLineId);
								if ($childLine.length > 0 && $childLine.attr('data-isinfrastructure') == "title") {
									return true;
								}
							});
							return false;
						}
						/**
						*
						* @param {string} typeAction
						* @param {string} action
						* @param sendData
						* @param callBackFunction
						*/
						o.callInterface = function (typeAction = 'get', action, sendData = {}, callBackFunction) {

							let ajaxData = {
								'data': sendData,
								'token': o.newToken,
							};
							if (typeAction == 'set') {
								ajaxData.set = action;
							} else {
								ajaxData.get = action;
							}
							$.ajax({
								method: 'POST',
								url: o.config.interfaceUrl,
								dataType: 'json',
								data: ajaxData,
								success: function (response) {
									if (typeof callBackFunction === 'function') {
										callBackFunction(response);
									} else {
										console.error('Callback function invalide for callKanbanInterface');
									}
									if (response.newToken != undefined) {
										o.newToken = response.newToken;
									}
									if (response.msg.length > 0) {
										o.setEventMessage(response.msg, response.result > 0 ? true : false, response.result == 0 ? true : false);
									}
								},
								error: function (err) {
									if (err.responseText.length > 0) {
										// detect login page in case of just disconnected
										let loginPage = $(err.responseText).find('[name="actionlogin"]');
										if (loginPage != undefined && loginPage.val() == 'login') {
											o.setEventMessage(o.langs.errorAjaxCallDisconnected, false);
											setTimeout(function () {
												location.reload();
											}, 2000);
										} else {
											o.setEventMessage(o.langs.errorAjaxCall, false);
										}
									} else {
										o.setEventMessage(o.langs.errorAjaxCall, false);
									}
								}
							});
						}
						/**
						 *
						 * @param {string} msg
						 * @param {boolean} status
						 * @param {boolean} sticky
						 */
						o.setEventMessage = function (msg, status = true, sticky = false) {
							let jnotifyConf = {
								delay: 1500					// the default time to show each notification (in milliseconds)
								, type: 'error'
								, sticky: sticky			// determines if the message should be considered "sticky" (user must manually close notification)
								, closeLabel: "&times;"		// the HTML to use for the "Close" link
								, showClose: true			// determines if the "Close" link should be shown if notification is also sticky
								, fadeSpeed: 150			// the speed to fade messages out (in milliseconds)
								, slideSpeed: 250			// the speed used to slide messages out (in milliseconds)
							}
							if (msg.length > 0) {
								if (status) {
									jnotifyConf.type = '';
									$.jnotify(msg, jnotifyConf);
								} else {
									$.jnotify(msg, jnotifyConf);
								}
							} else {
								$.jnotify('ErrorMessageEmpty', jnotifyConf);
							}
						}
					})(infrastructureFolders);
				});
			</script>
			<?php
			}
			return 0;
		}
		/**
		* Print field list where
		*
		* @param	array			$parameters Parameters
		* @param	CommonObject	$object Object
		* @param	string			$action Action
		* @param	HookManager		$hookmanager Hook manager
		* @return	int
		*/
		public function printFieldListWhere(&$parameters, &$object, &$action, $hookmanager)
		{
			$contexts = explode(':', $parameters['context']);
			if (in_array('checkmarginlist', $contexts)) {
				$this->resprints = ' AND  d.special_code != 550090';
			}
			return 0; // succès
		}
	}
