<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand 		- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program. If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infraspackplus/class/actions_infraspackplus.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraS
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	require_once DOL_DOCUMENT_ROOT.'/delivery/class/delivery.class.php';
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
	require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	/************************************************
	* Class Actionsinfraspackplus
	************************************************/
	class Actionsinfraspackplus
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
		* @param	string			$action			Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		function updateSession($parameters, $user, $action)
		{
			global $user;

			$path_src	= preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','', $_SERVER['PHP_SELF']);
			if (!infraspackplus_is_substitution_page($path_src)) {
				$url = infraspackplus_get_substitution_url($path_src);
				if (!empty($url)) {
					$params	= array_merge($_POST, $_GET);
					$params	= http_build_query($params);
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
			global $langs;

			$currentversion	= array();
			$currentversion	= infraspackplus_getLocalVersionMinDoli('infraspackplus');
			if (!getDolGlobalString('INFRASPACKPLUS_DISABLE_CHECK_VERSION_MAX', '') && version_compare(DOL_VERSION, $currentversion[4], '>')) {
				setEventMessages($langs->trans('PDFInfraSPlusWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			infraspackplus_test_new_fields('infraspackplus');	// Check the database configuration
			$path_src	= preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/i','', $_SERVER['PHP_SELF']);
			if (!infraspackplus_is_substitution_page($path_src)) {
				$url = infraspackplus_get_substitution_url($path_src);
				if (!empty($url)) {
					$params	= array_merge($_POST, $_GET);
					$params	= http_build_query($params);
					header('Location: '.$url.(!empty($params) ? '?'.$params : ''));
					exit;
				}
			}
			return 0;
		}

		/**
		* Table build to generate new document and to show linked objects (../core/class/html.formfile.class.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code + $this->resprints HTML code to show
		**/
		public function formBuilddocOptions($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $db, $langs, $user;

			$TContext	= explode(':', $parameters['context']);
			$exclude	= in_array('attestationtvacard', $TContext) ? 1 : 0;
			if (empty($exclude)) {
				// Rigths control *******************************
				$InfraSPermLastOpt	= 0;
				$this->resprints	= '';
				if (!empty($user->admin)) {
					$InfraSPermLastOpt	= 1;
				}
				if (!empty($user->hasRight('infraspackplus', 'paramLastOpt'))) {
					$InfraSPermLastOpt	= 1;
				}
				if ($object != null && method_exists($object, 'fetch_thirdparty')) {
					$object->fetch_thirdparty();
				}
				if (isModEnabled('milestone')) {
					if (version_compare(getDolGlobalString('MILESTONE_MAIN_VERSION', ''), '16.0.0', '>=')) {
						$reg	= '/\$tab_top_newpage = \(\!getDolGlobalInt\(\'MAIN_PDF_DONOTREPEAT_HEAD\'\) \? max\(pdf_getHeightForLogo\(\$logo\)\, 45\) \: 10\)\;/s';
						infraspackplus_test_module('milestone', '/class/actions_milestone.class.php', 'S', 'InfraSPackPlus_model', 'new16.txt', 'R', $reg);
					} else {
						$reg	= '/\$tab_top_newpage = \(\!getDolGlobalInt\(\'MAIN_PDF_DONOTREPEAT_HEAD\'\) \? max\(pdf_getHeightForLogo\(\$logo\)\, 45\) \: 10\)\;/s';
						infraspackplus_test_module('milestone', '/class/actions_milestone.class.php', 'S', 'InfraSPackPlus_model', 'new.txt', 'R', $reg);
					}
				}
				$path		= dol_buildpath('infraspackplus', 0);
				$urlpath	= dol_buildpath('infraspackplus', 1);
				// Colspan
					$colspan = 6;
				// Présentation générale des options, Récupération des paramètres sauvegardés
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'reception', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'mo', 'bom', 'project', 'expensereport'))) {
					$langs->load('infraspackplus@infraspackplus');
					infraspackplus_test_new_fields('infraspackplus');	// Check the database configuration
					$idvar				= ($object->element == 'facture') ? 'facid' : 'id';
					$urlTo				= '?'.$idvar.'='.$object->id;
					$ht_signarea		= getDolGlobalInt('INFRASPLUS_PDF_HT_SIGN_AREA', 24) * 7.5;
					$signColor			= getDolGlobalString('INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR', '0,0,0');
					$signColor			= '#'.colorArrayToHex(explode(',', $signColor));
					// Page JS to toggle some parameters
					$permHide			= !empty($InfraSPermLastOpt) ? '.infrasfoldable' : '.InfraSPermLastOpt';
					$permFoldFunction	= !empty($InfraSPermLastOpt) ? '$(".infrasfoldable").toggle();' : '';
					$js					= <<< EOJS
					function funcListSsT(id, val)
					{
						var listSsT = '?id=' + id + '&Sst=' + val;
						window.location.href = window.location.protocol + '//' + window.location.host + '/' + window.location.pathname + listSsT;
					}
					$(document).ready(function(){
						$('{$permHide}').hide();
					});
					$(function ()
					{
						$('.infrasfold').click(function (){
							{$permFoldFunction}
						});
						$('a[rel=getClientSign]').click(function (){
							$('#dialog-promptInfraSPlusSign').remove();
							var dialog_html = '	<div id = "dialog-promptInfraSPlusSign" style = "animation: drop-in 1s; animation-fill-mode: forwards;">';
							dialog_html += '		<div id = "signature"></div>';
							dialog_html += '	</div>';
							$('body').append(dialog_html);
							$("#signature").jSignature({'UndoButton':true
														, 'width': 500
														, 'height': {$ht_signarea}
														, 'decor-color': 'transparent'
														, 'color': '{$signColor}'
							})
							$( "#dialog-promptInfraSPlusSign" ).dialog({
								resizable: false,
								height: 'auto',
								width: 'auto',
								modal: true,
								title: "{$langs->trans('InfraSPlusSSignTitle')}",
								buttons: {
									"{$langs->trans('InfraSPlusSclearBtn')}": function() {
										$("#signature").jSignature("reset");
									},
									"{$langs->trans('Validate')}": function() {
										$("#signvalue").val($("#signature").jSignature("getData"));
										$(this).dialog("close");
									},
									"{$langs->trans('Cancel')}": function() {
										$(this).dialog("close");
									}
								}
							});
							$('.ui-widget-header').removeClass().addClass('liste_titre_bydiv infrasplusModal');
							$('.ui-dialog-title').removeClass().addClass('infrasplusModalTitle');
							var buttons = $('.ui-dialog-buttonset').children('button');
							$(buttons[0]).removeClass().addClass('butActionDelete');
							$(buttons[1]).removeClass().addClass('button');
							$(buttons[2]).removeClass().addClass('butActionDelete');
						});
					});
EOJS;
					// Récupération des paramètres sauvegardés (Liés à l'utilisateur, au document ou par défaut => configuration module)
					$defaultParams		= infraspackplus_defaultParam($object);
					$listOptions		= $defaultParams['listOptions'];
					$listModulesFreeT	= $defaultParams['listModulesFreeT'];	// liste nécessaire à la gestion des mentions complémentaires
					$listModulesNoteP	= $defaultParams['listModulesNoteP'];	// liste nécessaire à la gestion des notes publiques
					// Application des paramètres
					foreach ($listOptions as $key => $option) {
						$_POST[$key]	= $listOptions[$key]['value'];
					}
					// Titre des options InfraSPackPlus
					$titleOptions		= $langs->trans('PDFInfraSPlusOptions').'&nbsp;&nbsp;&nbsp;'.img_picto($langs->trans('Setup'), 'setup', 'style="vertical-align: bottom; height: 20px;"');
					$titleStyle			= 'background-color: rgba(148, 148, 148, .065) !important;';
					$this->resprints	= '	<!--[if lt IE 9]>
												<script type = "text/javascript" src = "'.$urlpath.'/includes/jsignature/flashcanvas.js"></script>
											<![endif]-->
											<script src = "'.$urlpath.'/includes/jsignature/jSignature.min.js"></script>
											<script src = "'.$urlpath.'/includes/jsignature/jSignature.UndoButton.js"></script>
											<script type = "text/javascript">'.$js.'</script>
											<tr class = "infrasfold cursorpointer infrasplusbgtrans" style = "'.$titleStyle.'"><td class = "center" colspan = "'.$colspan.'" style = "font-size: 120%;">'.$titleOptions.'</td></tr>';
				}
				// Logo et Adresse expéditeur, Mentions complémentaires + Image en pied de document
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'reception', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'mo', 'bom', 'project', 'expensereport'))) {
					$factor		= getDolGlobalString('INFRASPLUS_PDF_FACTOR_PRE', '');
					// logo
					$logos		= array();
					$logodir	= !empty($conf->mycompany->multidir_output[$object->entity]) ? $conf->mycompany->multidir_output[$object->entity] : $conf->mycompany->dir_output;
					foreach (glob($logodir.'/logos/*.jpg') as $file) {
						$logos[]	= dol_basename($file);
					}
					foreach (glob($logodir.'/logos/*.jpeg') as $file) {
						$logos[]	= dol_basename($file);
					}
					foreach (glob($logodir.'/logos/*.gif') as $file) {
						$logos[]	= dol_basename($file);
					}
					foreach (glob($logodir.'/logos/*.png') as $file) {
						$logos[]	= dol_basename($file);
					}
					$logoPost		= GETPOST('logo', 'alpha') == 'none' ? '' : GETPOST('logo', 'alpha');
					$disableLogo	= '';
					$optionNoLogo	= '';
					$selected_logo	= !empty($ParamLogoEmet) ? infraspackplus_getLogoEmet($object->thirdparty->id) : $logoPost;
					$noMyLogo		= getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO', 0);
					if (!empty($noMyLogo)) {
						$disableLogo	= 'disabled';
						$optionNoLogo	= '<option name = "logo" value = "" selected>&nbsp;</option>';
						$selected_logo	= '';
					}
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "logo">'.$langs->trans('PDFInfraSPlusLogo').'</label>&nbsp;
													<select class = "flat cursorpointer width200" id = "selectlogo" name = "logo" '.$disableLogo.'>
														<option name = "logo" value = "" data-html = "'.dol_escape_htmltag($langs->trans('PDFInfraSPlusDefaultLogo')).'">'.$langs->trans('PDFInfraSPlusDefaultLogo').'</option>';
					$this->resprints	.= $optionNoLogo;
					for ($i = 0; $i < count($logos); $i++) {
						$this->resprints	.= '<option name = "logo" value = "'.$logos[$i].'"';
						if ($selected_logo === $logos[$i]) {
							$this->resprints	.= ' selected';
						}
						$this->resprints	.= ' data-html = "'.dol_escape_htmltag($logos[$i]).'">'.$logos[$i].'</option>';
					}
					$this->resprints	.= '		</select>';
					$this->resprints	.= ajax_combobox('selectlogo', array(), 0, 0, 'resolve');
					$this->resprints	.= '	</td>
											</tr>';
					unset($i);
					// adresse expéditeur
					if (!in_array($object->element, array('product', 'mo', 'bom'))) {
						$adrPost		= GETPOST('adr', 'alpha') == 'none' ? '' : GETPOST('adr', 'int');
						$countryAddr	= getDolGlobalInt('INFRASPLUS_PDF_USE_CUSTOM_COUNTRY_ADDR', 0);
						$adrtmp			= new Address($db);
						if (!empty($countryAddr) && !empty($object->thirdparty->country_code)) {
							$adrfound	= $adrtmp->fetch(0, 0, $object->thirdparty->country_code);
						} else {
							$adrfound	= 0;
						}
						$selected_adr		= $adrfound == 1 ? $adrtmp->id : $adrPost;
						$res_adr			= $adrtmp->fetch_lines(0, -1);
						$this->resprints	.=	'<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "adr">'.$langs->trans('PDFInfraSPlusAddress').'</label>&nbsp;
														<select class = "flat cursorpointer width200" id = "selectadr" name = "adr">
															<option name = "adr" value = "" data-html = "'.dol_escape_htmltag($langs->trans('PDFInfraSPlusDefaultAddress')).'">'.$langs->trans('PDFInfraSPlusDefaultAddress').'</option>';
						if ($res_adr > 0) {
							foreach ($adrtmp->lines as $lineadr) {
								$this->resprints	.= '	<option name = "adr" value = "'.$lineadr->id.'" '.($selected_adr === $lineadr->id ? ' selected' : '').' data-html = "'.dol_escape_htmltag($lineadr->name.' ('.$lineadr->label.')').'">'.$lineadr->name.' ('.$lineadr->label.')</option>';
							}
						}
						$this->resprints	.= '		</select>';
						$this->resprints	.= ajax_combobox('selectadr', array(), 0, 0, 'resolve');
						$this->resprints	.= '	</td>
												</tr>';
					}
					// adresse destinataire
					if (!in_array($object->element, array('product', 'mo', 'bom'))) {
						$customerAddrPost	 = empty(GETPOST('customerAddrSelect', 'alpha')) || GETPOST('customerAddrSelect', 'alpha') == 'none' ? 'T' : GETPOST('customerAddrSelect', 'alpha');
						$this->resprints	.=	'<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "customerAddrSelect">'.$langs->trans('PDFInfraSPlusCustomerAddress').'</label>&nbsp;
														<select class = "flat cursorpointer width200" id = "selectcustomerAddrSelect" name = "customerAddrSelect">
															<option name = "customerAddrSelect" value = "T"'.($customerAddrPost == 'T' ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('InfraSPlusParamThirdpartyAddr')).'">'.$langs->trans('InfraSPlusParamThirdpartyAddr').'</option>
															<option name = "customerAddrSelect" value = "C"'.($customerAddrPost == 'C' ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('InfraSPlusParamContactAddr')).'">'.$langs->trans('InfraSPlusParamContactAddr').'</option>
															<option name = "customerAddrSelect" value = "B"'.($customerAddrPost == 'B' ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('InfraSPlusParamThirdpartyBothAddr')).'">'.$langs->trans('InfraSPlusParamThirdpartyBothAddr').'</option>
															<option name = "customerAddrSelect" value = "A"'.($customerAddrPost == 'A' ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('InfraSPlusParamContactBothAddr')).'">'.$langs->trans('InfraSPlusParamContactBothAddr').'</option>
														</select>';
						$this->resprints	.= ajax_combobox('selectcustomerAddrSelect', array(), 0, 0, 'resolve');
						$this->resprints	.= '	</td>
												</tr>';
					}
					// Mentions complémentaires
					$tvaAuto		= getDolGlobalInt('INFRASPLUS_PDF_FREETEXT_TVA_AUTO', 0);
					$freeTPost		= GETPOST('listfreet', 'alpha') == 'none' ? '' : GETPOST('listfreet', 'alpha');
					$selected_freeT	= is_array($freeTPost) ? $freeTPost : explode ('-', $freeTPost);
					$rootfreetext	= '';
					$showsysmcbase	= '';
					foreach ($listModulesFreeT as $module) {
						if ($object->element == $module[0]) {
							$rootfreetext	= $module[1];
							$showsysmcbase	= $module[2];
							$showsysmcbase	= empty($showsysmcbase) ? '' : getDolGlobalString($showsysmcbase, '');
							break;
						}
					}
					if (!empty($rootfreetext)) {
						$sql_freeT	= 'SELECT c.name, c.value, d.libelle, d.pos';
						$sql_freeT	.= ' FROM '.$db->prefix().'const AS c';
						$sql_freeT	.= ' LEFT JOIN '.$db->prefix().'c_infraspackplus_mention AS d ON d.code = REPLACE(c.name, "'.$rootfreetext.'_", "")';
						$sql_freeT	.= ' WHERE c.name LIKE "'.$rootfreetext.'%"';
						$sql_freeT	.= $rootfreetext == 'INVOICE_FREE_TEXT' && $tvaAuto ? ' AND (d.code NOT LIKE "TVA\_%" OR d.code IS NULL)' : '';			// on exclut les mentions dont le code commence par 'TVA_' (ou null)
						$sql_freeT	.= $rootfreetext == 'INVOICE_FREE_TEXT' && $factor ? ' AND (d.code NOT LIKE "'.$factor.'%" OR d.code IS NULL)' : '';	// on exclut les mentions dont le code commence par la valeur de $factor (ou null)
						if (!empty($showsysmcbase)) {	// Si l'option pour afficher systématiquement les mentions complémentaires est activée, on ne prend que les mentions actives
							$sql_freeT			.= ' AND d.active = 1';
							$sql_freeT			.= ' AND c.name NOT LIKE "'.$rootfreetext.'"';
							$this->resprints	.= '<input type = "hidden" name = "showsysmcbase" value = "'.$rootfreetext.'">';
						} else {	// Sinon on accepte les mentions actives ainsi que la mention de base du module courant
							$sql_freeT	.= ' AND (d.active = 1 OR c.name LIKE "'.$rootfreetext.'")';
						}
						$sql_freeT		.= ' AND c.entity = "'.$conf->entity.'"';
						$sql_freeT		.= ' ORDER BY d.pos ASC';
						$result_freeT	= $db->query($sql_freeT);
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_freeT = '.$sql_freeT);
					}
					if (!empty($result_freeT)) {
						$num		= $db->num_rows($result_freeT);
						$listFreeT	= array();
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions num_rows($result_freeT) = '.$num);
						for ($i = 0; $i < $num; $i++) {
							$objFreeT	= $db->fetch_object($result_freeT);
							if (!empty($objFreeT)) {
								$listFreeT[$objFreeT->name] = array('id' => $objFreeT->name, 'fulllabel' => ($objFreeT->libelle ? $objFreeT->libelle : $langs->trans('PDFInfraSPlusMentionsBase')));
							}
						}
						$db->free($result_freeT);
						unset($i);
					} else {
						dol_print_error($db);
					}
					$arrayFreeT	= array ();
					if (is_array($listFreeT) && count($listFreeT) > 0) {
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions count($listFreeT) = '.count($listFreeT));
						foreach($listFreeT as $key => $value) {
							$arrayFreeT[$listFreeT[$key]['id']] = $listFreeT[$key]['fulllabel'];
						}
						$form				= new Form($db);
						$this->resprints	.=	'<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "listfreet">'.$langs->trans('PDFInfraSPlusMentions').'</label>&nbsp;';
						$this->resprints	.= $form->multiselectarray('listfreet', $arrayFreeT, $selected_freeT, 0, 0, '', 0, '400px');
						$this->resprints	.=		'</td>
												</tr>';
					}
					// Notes publiques standards
					$usentascover		= getDolGlobalString('INFRASPLUS_PDF_NT_USED_AS_COVER', '');
					$notePPost			= GETPOST('listnotep', 'alpha') == 'none' ? '' : GETPOST('listnotep', 'alpha');
					$selected_noteP		= is_array($notePPost) ? $notePPost : explode ('-', $notePPost);
					$rootnotepub		= '';
					$showsysntbase		= '';
					foreach ($listModulesNoteP as $module) {
						if ($object->element == $module[0]) {
							$rootnotepub	= $module[1];
							$showsysntbase	= $module[2];
							$showsysntbase	= empty($showsysntbase) ? '' : getDolGlobalString($showsysntbase, '');
							break;
						}
					}
					if (!empty($rootnotepub)) {
						$sql_noteP	= 'SELECT c.name, c.value, d.libelle, d.pos';
						$sql_noteP	.= ' FROM '.$db->prefix().'const AS c';
						$sql_noteP	.= ' LEFT JOIN '.$db->prefix().'c_infraspackplus_note AS d ON d.code = REPLACE(c.name, "'.$rootnotepub.'_", "")';
						$sql_noteP	.= ' WHERE c.name LIKE "'.$rootnotepub.'%"';
						if (!empty($showsysntbase)) {	// Si l'option pour afficher systématiquement les notes publiques est activée, on ne prend que les notes actives
							$sql_noteP			.= ' AND d.active = 1';
							$this->resprints	.= '<input type = "hidden" name = "showsysntbase" value = "'.$rootnotepub.'">';
						} else {	// Sinon on accepte les notes publiques actives ainsi que la note publique de base du module courant
							$sql_noteP	.= ' AND (d.active = 1 OR c.name LIKE "'.$rootnotepub.'")';
						}
						if (!empty($usentascover)) {
							$sql_noteP			.= ' AND c.name NOT LIKE "'.$rootnotepub.'_'.$usentascover.'"';
						}
						$sql_noteP		.= ' AND c.entity = "'.$conf->entity.'"';
						$sql_noteP		.= ' ORDER BY d.pos ASC';
						$result_noteP	= $db->query($sql_noteP);
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_noteP = '.$sql_noteP);
					} else {
						$usentascover	= '';
					}
					$listNoteP	= array();
					if (!empty($result_noteP)) {
						$num	= $db->num_rows($result_noteP);
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions num_rows($result_noteP) = '.$num);
						for ($i = 0; $i < $num; $i++) {
							$objNoteP = $db->fetch_object($result_noteP);
							if (!empty($objNoteP)) {
								$listNoteP[$objNoteP->name] = array('id'=>$objNoteP->name, 'fulllabel'=>($objNoteP->libelle ? $objNoteP->libelle : $langs->trans('PDFInfraSPlusNotesBase')));
							}
						}
						$db->free($result_noteP);
						unset($i);
					} else {
						dol_print_error($db);
					}
					$arrayNoteP	= array ();
					if (count($listNoteP) > 0) {
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions count($listNoteP) = '.count($listNoteP));
						foreach($listNoteP as $key => $value) {
							$arrayNoteP[$listNoteP[$key]['id']] = $listNoteP[$key]['fulllabel'];
						}
						$form				= new Form($db);
						$this->resprints	.=	'<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "listnotep">'.$langs->trans('PDFInfraSPlusNotes').'</label>&nbsp;';
						$this->resprints	.= $form->multiselectarray('listnotep', $arrayNoteP, $selected_noteP, 0, 0, '', 0, '400px');
						$this->resprints	.=		'</td>
												</tr>';
					}
					// Image en pied de document
					$defaultimagefoot	= getDolGlobalString('INFRASPLUS_PDF_IMAGE_FOOT', '');
					$piedPost			= GETPOST('pied', 'alpha') == 'none' ? '' : GETPOST('pied', 'alpha');
					$selected_pied		= $piedPost;
					$this->resprints	.=	'<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "pied">'.$langs->trans('PDFInfraSPlusPied').'</label>&nbsp;
													<select class = "flat cursorpointer width200" id = "selectpied" name = "pied">
														<option name = "pied" value = "'.$defaultimagefoot.'" data-html = "'.dol_escape_htmltag($langs->trans('PDFInfraSPlusDefaultPied')).'">'.$langs->trans('PDFInfraSPlusDefaultPied').'</option>';
					for ($i = 0; $i < count($logos); $i++) {
						if ($logos[$i] === 'thumbs' || $logos[$i][0] === '.') {
							continue;	// Choose to ignore field which isn't image
						}
						$this->resprints	.= '<option name = "pied" value = "'.$logos[$i].'"';
						if ($selected_pied === $logos[$i]) {
							$this->resprints	.= ' selected';
						}
						$this->resprints	.= ' data-html = "'.dol_escape_htmltag($logos[$i]).'">'.$logos[$i].'</option>';
					}
					$this->resprints	.= '		</select>';
					$this->resprints	.= ajax_combobox('selectpied', array(), 0, 0, 'resolve');
					$this->resprints	.= '	</td>
											</tr>';
					unset($i);
				} else {
					$this->resprints	.= '<input type = "hidden" name = "logo" value = '.GETPOST('logo', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "adr" value = '.GETPOST('adr', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "customerAddrSelect" value = '.GETPOST('customerAddrSelect', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "listfreet" value = '.GETPOST('listfreet', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "usentascover" value = '.GETPOST('usentascover', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "listnotep" value = '.GETPOST('listnotep', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "pied" value = '.GETPOST('pied', 'alpha').'>';
				}
				// Adresse de livraison (client)
				if (in_array($object->element, array('propal', 'commande', 'facture', 'fichinter', 'shipping', 'reception', 'delivery', 'project'))) {
					// Adresse de livraison par défaut sur 'de base' si on utilise l'adresse de facturation automatique
					if (getDolGlobalInt('INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SI_FACT', 0) && $object->element == 'facture') {
						$res_adrfact = $listOptions['adrlivr']['value'];
					}
					$useDoliAddr		= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 0);
					$freeadrlivr		= getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', '');
					$showadrlivr		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', 0);
					$showadrlivr		= !empty($useDoliAddr) || !empty($object->array_options['options_'.$freeadrlivr]) ? 0 : $showadrlivr;
					$def_adrlivrfour	= getDolGlobalString('INFRASPLUS_PDF_DEFAULT_ADDR_DELIV', '');
					$typeadr		= in_array($object->element, array('fichinter')) ? $langs->trans('PDFInfraSPlusAdrInter') : $langs->trans('PDFInfraSPlusAdrLivr');
					$adrlivrPost		= !empty($res_adrfact) ? $res_adrfact : GETPOST('adrlivr', 'int');	// -1 pour défaut, -2 pour aucune, >0 pour ID
					if (!empty($showadrlivr)) {
						$adrlivrtmp			= new Address($db);
						$res_adrlivr		= $adrlivrtmp->fetch_lines($object->thirdparty->id);
						$this->resprints	.= '<tr class = "oddeven InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "adrlivr">'.$typeadr.'</label>&nbsp;
														<select class = "flat cursorpointer width200" id = "selectadrlivr" name = "adrlivr">
															<option name = "adrlivr" value = "-2"'.($adrlivrPost == -2 ? ' selected' : '').'>&nbsp;</option>
															<option name = "adrlivr" value = "-1"'.($adrlivrPost == -1 ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('PDFInfraSPlusBaseAddress')).'">'.$langs->trans('PDFInfraSPlusBaseAddress').'</option>';
						if ($res_adrlivr > 0) {
							foreach ($adrlivrtmp->lines as $lineadr) {
								$this->resprints	.= '	<option name = "adrlivr" value = "'.$lineadr->id.'" '.($adrlivrPost === $lineadr->id ? ' selected' : '').' data-html = "'.dol_escape_htmltag($lineadr->name.' ('.$lineadr->label.')').'">'.$lineadr->name.' ('.$lineadr->label.')</option>';
							}
						}
						$this->resprints	.= '		</select>';
						$this->resprints	.= ajax_combobox('selectadrlivr', array(), 0, 0, 'resolve');
						$this->resprints	.= '	</td>
												</tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "adrlivr" value = '.GETPOST('adrlivr', 'int').'>';
				}
				// Sous-Traitant (lié au client via "CustomLink")
				if (isModEnabled('customlink') && in_array($object->element, array('commande', 'shipping', 'reception', 'delivery'))) {
					$showadrSsT	= getDolGlobalInt('INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT', 0);
					$typeCtSsT	= getDolGlobalString('INFRASPLUS_PDF_TYPE_SOUS_TRAITANT', '');
					$doc_id		= GETPOST('id', 'int');
					$SstPost	= GETPOST('Sst', 'int') ? GETPOST('Sst', 'int') : '-2';
					$adrSstPost	= GETPOST('adrSst', 'int') ? GETPOST('adrSst', 'int') : '-2';
					if (!empty($showadrSsT) && !empty($typeCtSsT)) {
						$sql_listSsT	= 'SELECT DISTINCT s.rowid, s.nom';
						$sql_listSsT	.= ' FROM '.$db->prefix().'socpeople AS sp';
						$sql_listSsT	.= ' INNER JOIN '.$db->prefix().'element_contact AS ec ON sp.rowid = ec.fk_socpeople';
						$sql_listSsT	.= ' INNER JOIN '.$db->prefix().'societe AS s ON s.rowid = sp.fk_soc';
						$sql_listSsT	.= ' WHERE ec.element_id = '.$object->thirdparty->id.' AND ec.fk_c_type_contact = '.$typeCtSsT;
						$res_listSsT	= $db->query($sql_listSsT);
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions sql_listSsT = '.$sql_listSsT);
						$ar_listSsT		= array();
						$num_SsT		= $db->num_rows($res_listSsT);
						if (!empty($res_listSsT) && $num_SsT > 0) {
							$this->resprints	.= '<tr class = "oddeven InfraSPermLastOpt">
														<td colspan = "'.$colspan.'" align = "right">
															<label for = "Sst">'.$langs->trans('PDFInfraSPlusListSsT').'</label>&nbsp;
															<select class = "flat cursorpointer width200" id = "selectSst" name = "Sst" onchange="funcListSsT('.$doc_id.', this.value)">
																<option name = "Sst" value = "-2"'.($SstPost === -2 ? ' selected' : '').'>&nbsp;</option>';
							for ($i = 0; $i < $num_SsT; $i++) {
								$ar_listSsT			= $db->fetch_array($res_listSsT);
								$this->resprints	.= '		<option name = "Sst" value = "'.$ar_listSsT['rowid'].'"'.($SstPost === $ar_listSsT['rowid'] ? ' selected' : '').' data-html = "'.dol_escape_htmltag($ar_listSsT['nom']).'">'.$ar_listSsT['nom'].'</option>';
							}
							$this->resprints	.= '		</select>';
							$this->resprints	.= ajax_combobox('selectSst', array(), 0, 0, 'resolve');
							$this->resprints	.= '	</td>
													</tr>';
							if ($SstPost !== -2 || $num_SsT == 1) {
								$idCustomer			= $num_SsT > 1 ? $SstPost : $ar_listSsT['rowid'];
								$adrSsttmp			= new Address($db);
								$res_adrSst			= $adrSsttmp->fetch_lines($idCustomer);
								$this->resprints	.= '<tr class = "oddeven InfraSPermLastOpt">
															<td colspan = "'.$colspan.'" align = "right">
																<label for = "adrSst">'.$langs->trans('PDFInfraSPlusAdrSsT').'</label>&nbsp;
																<select class = "flat cursorpointer width200" id = "selectadrSst" name = "adrSst">
																	<option name = "adrSst" value = "-2"'.($adrSstPost === -2 ? ' selected' : '').'>&nbsp;</option>
																	<option name = "adrSst" value = "-1"'.($adrSstPost === -1 ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('PDFInfraSPlusBaseAddress')).'">'.$langs->trans('PDFInfraSPlusBaseAddress').'</option>';
								if ($res_adrSst > 0) {
									foreach ($adrSsttmp->lines as $lineadr) {
										$this->resprints	.= '	<option name = "adrSst" value = "'.$lineadr->id.'" '.($adrSstPost === $lineadr->id ? ' selected' : '').' data-html = "'.dol_escape_htmltag($lineadr->name.' ('.$lineadr->label.')').'">'.$lineadr->name.' ('.$lineadr->label.')</option>';
									}
								}
								$this->resprints	.= '		</select>';
								$this->resprints	.= ajax_combobox('selectadrSst', array(), 0, 0, 'resolve');
								$this->resprints	.= '	</td>
														</tr>';
							}
						} else {
							dol_print_error($db);
						}
						$db->free($res_listSsT);
						unset($i);
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "Sst" value = '.GETPOST('Sst', 'int').'>';
					$this->resprints	.= '<input type = "hidden" name = "adrSst" value = '.GETPOST('adrSst', 'int').'>';
				}
				// Adresse de livraison spéciale fournisseur (interne ou interne + client)
				if (in_array($object->element, array('supplier_proposal', 'order_supplier'))) {
					$useDoliAddr		= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 0);
					$freeadrlivr		= getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', '');
					$showadrlivrfour	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON', 0);
					$showadrlivrfour	= !empty($useDoliAddr) || !empty($object->array_options['options_'.$freeadrlivr]) ? 0 : $showadrlivrfour;
					$def_adrlivrfour	= getDolGlobalString('INFRASPLUS_PDF_DEFAULT_ADDR_DELIV', '');
					$adrlivrfourmixte	= getDolGlobalInt('INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE', 0);
					$adrlivrfourPost	= GETPOST('adrlivrfour', 'int');	// -2 pour aucune, >0 pour ID
					$typadrlivrfourPost	= GETPOST('typeadr', 'alpha');	// -2 pour aucune, I, C, S
					if (!empty($showadrlivrfour)) {
						if (empty($adrlivrfourmixte)) {
							$adrlivrfourtmp		= new Address($db);
							$res_adrlivrfour	= $adrlivrfourtmp->fetch_lines(0, -1);
							$this->resprints	.= '<tr class = "oddeven InfraSPermLastOpt">
														<td colspan = "'.$colspan.'" class = "right">
															<label for = "adrlivrfour">'.$langs->trans('PDFInfraSPlusAdrLivr').'</label>&nbsp;
															<select class = "flat cursorpointer width200" id = "selectadrlivrfour" name = "adrlivrfour">
																<option name = "adrlivrfour" value = "-2"'.($adrlivrfourPost == -2 ? ' selected' : '').'>&nbsp;</option>';
							if (!empty($def_adrlivrfour)) {
								$this->resprints	.= '		<option name = "adrlivrfour" value = "'.$def_adrlivrfour.'"'.($adrlivrfourPost == $def_adrlivrfour ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('PDFInfraSPlusBaseAddress')).'">'.$langs->trans('PDFInfraSPlusBaseAddress').'</option>';
							}
							if ($res_adrlivrfour > 0) {
								foreach ($adrlivrfourtmp->lines as $lineadr) {
									$labelToShow		= $lineadr->name.' ('.$lineadr->label.')';
									$this->resprints	.= '	<option name = "adrlivrfour" value = "'.$lineadr->id.'" '.($adrlivrfourPost == $lineadr->id ? ' selected' : '').' data-html = "'.dol_escape_htmltag($labelToShow).'">'.$labelToShow.'</option>';
								}
							}
							$this->resprints	.= '		</select>';
							$this->resprints	.= ajax_combobox('selectadrlivrfour', array(), 0, 0, 'resolve');
							$this->resprints	.= '	</td>
													</tr>';
						} else {
							$adrlivrfourtmp	= new Address($db);
							$res_adrlivrfour	= $adrlivrfourtmp->fetch_lines(0, 2);
							$this->resprints	.= '<tr class = "oddeven InfraSPermLastOpt">
														<td colspan = "'.$colspan.'" class = "right">
															<label for = "adrlivrfour">'.$langs->trans('PDFInfraSPlusAdrLivr').'</label>&nbsp;
															<select class = "flat cursorpointer width200" id = "selectadrlivrfour" name = "adrlivrfour">
																<option name = "adrlivrfour" value = "-2"'.($adrlivrfourPost == -2 ? ' selected' : '').'>&nbsp;</option>';
							if (!empty($def_adrlivrfour)) {
								$this->resprints	.= '		<option name = "adrlivrfour" value = "'.$def_adrlivrfour.'"'.($adrlivrfourPost == $def_adrlivrfour ? ' selected' : '').' data-html = "'.dol_escape_htmltag($langs->trans('PDFInfraSPlusBaseAddress')).'">'.$langs->trans('PDFInfraSPlusBaseAddress').'</option>';
							}
							if ($res_adrlivrfour > 0) {
								$typadrlivrfourPost	= $typadrlivrfourPost == -2 ? '' : $typadrlivrfourPost.'_';
								foreach ($adrlivrfourtmp->lines as $lineadr) {
									if ($lineadr->socid == '0' && $lineadr->id == $def_adrlivrfour) {
										continue;
									}
									// 'I' internal (ID => llx_infraspackplus_societe_address rowid) ; 'C' main company address (ID => llx_societe rowid) ; 'S' secondary company address (ID => llx_infraspackplus_societe_address rowid)
									if ($lineadr->socid == 0) {
										$prefixLabel	= 'I_';	// 'I' => Internal => name (label) / town
									} elseif ($lineadr->socid == 'NULL') {
										$prefixLabel	= 'C_';	// 'C' => Customer => soc_name / town
									} else {
										$prefixLabel	= 'S_';	// 'S' => Supplier => soc_name / name (label) / town
									}
									$value				= $prefixLabel.$lineadr->id;
									$labelToShow		= $lineadr->socid == '0' ? '' : $lineadr->soc_name;
									$labelToShow		.= !empty($labelToShow) && $lineadr->socid != 'NULL' ? ' - ' : '';
									$labelToShow		.= $lineadr->socid == 'NULL' ? '' : $lineadr->name.' ('.$lineadr->label.')';
									$this->resprints	.= '	<option name = "adrlivrfour" value = "'.$value.'" '.($prefixLabel.$adrlivrfourPost === $value ? ' selected' : '').' data-html = "'.dol_escape_htmltag($labelToShow).'">'.$labelToShow.'</option>';
								}
							}
							$this->resprints	.= '		</select>';
							$this->resprints	.= ajax_combobox('selectadrlivrfour', array(), 0, 0, 'resolve');
							$this->resprints	.= '	</td>
													</tr>';
						}
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "adrlivrfour" value = '.GETPOST('adrlivrfour', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "typeadr" value = '.GETPOST('typeadr', 'alpha').'>';
				}
				// Conditions générales
				if (!empty($user->hasRight('infraspackplus', 'paramCGV'))) {
					$CGbyLang	= getDolGlobalString('MAIN_MULTILANGS', '') && getDolGlobalString('INFRASPLUS_PDF_CGV_FROM_LANG', '') && !empty($object->thirdparty->default_lang) ? 1 : 0;
					// CGV
					if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat'))) {
						$cgvbydefPost	= GETPOST('cgv', 'alpha') == 'none' ? '' : GETPOST('cgv', 'alpha');
						$CGVs			= infraspackplus_get_CGfiles ('CGV', $object->entity, $object);
						if (count($CGVs) > 0) {
							$cgvbydef			= $cgvbydefPost && $CGbyLang ? infraspackplus_get_CGfiles_lang ($CGVs, $object->thirdparty->default_lang) : $cgvbydefPost;
							$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
														<td colspan = "'.$colspan.'" align = "right">
															<label for = "cgv">'.$langs->trans('PDFInfraSPlusCGVchk').'</label>&nbsp;
															<select class = "flat cursorpointer width200" id = "selectcgv" name = "cgv">
																<option name = "cgv" value = "" data-html = "'.dol_escape_htmltag($langs->trans('InfraSPlusParamNoCGV')).'">'.$langs->trans('InfraSPlusParamNoCGV').'</option>';
							for ($i = 0; $i < count($CGVs); $i++) {
								$this->resprints	.=	'		<option name = "cgv" value = "'.$CGVs[$i].($cgvbydef === $CGVs[$i] ? '" selected' : '"').' data-html = "'.dol_escape_htmltag($CGVs[$i]).'">'.$CGVs[$i].'</option>';
							}
							$this->resprints	.= '		</select>';
							$this->resprints	.= ajax_combobox('selectcgv', array(), 0, 0, 'resolve');
							$this->resprints	.= '	</td>
													</tr>';
							unset($i);
						}
					} else {
						$this->resprints	.= '<input type = "hidden" name = "cgv" value = '.GETPOST('cgv', 'alpha').'>';
					}
					// CGI
					if (in_array($object->element, array('fichinter'))) {
						$cgibydefPost	= GETPOST('cgi', 'alpha') == 'none' ? '' : GETPOST('cgi', 'alpha');
						$CGIs			= infraspackplus_get_CGfiles ('CGI', $object->entity, $object);
						if (count($CGIs) > 0) {
							$cgibydef			= $cgibydefPost && $CGbyLang ? infraspackplus_get_CGfiles_lang ($CGIs, $object->thirdparty->default_lang) : $cgibydefPost;
							$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
														<td colspan = "'.$colspan.'" align = "right">
															<label for = "cgi">'.$langs->trans('PDFInfraSPlusCGIchk').'</label>&nbsp;
															<select class = "flat cursorpointer width200" id = "selectcgi" name = "cgi">
																<option name = "cgi" value = "" data-html = "'.dol_escape_htmltag($langs->trans('InfraSPlusParamNoCGI')).'">'.$langs->trans('InfraSPlusParamNoCGI').'</option>';
							for ($i = 0; $i < count($CGIs); $i++) {
								$this->resprints	.=	'		<option name = "cgi" value = "'.$CGIs[$i].($cgibydef === $CGIs[$i] ? '" selected' : '"').' data-html = "'.dol_escape_htmltag($CGIs[$i]).'">'.$CGIs[$i].'</option>';
							}
							$this->resprints	.= '		</select>';
							$this->resprints	.= ajax_combobox('selectcgi', array(), 0, 0, 'resolve');
							$this->resprints	.= '	</td>
													</tr>';
							unset($i);
						}
					} else {
						$this->resprints	.= '<input type = "hidden" name = "cgi" value = '.GETPOST('cgi', 'alpha').'>';
					}
					// CGA
					if (in_array($object->element, array('supplier_proposal', 'order_supplier'))) {
						$cgabydefPost	= GETPOST('cga', 'alpha') == 'none' ? '' : GETPOST('cga', 'alpha');
						$CGAs			= infraspackplus_get_CGfiles ('CGA', $object->entity, $object);
						if (count($CGAs) > 0) {
							$cgabydef			= $cgabydefPost && $CGbyLang ? infraspackplus_get_CGfiles_lang ($CGAs, $object->thirdparty->default_lang) : $cgabydefPost;
							$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
														<td "'.$colspan.'" align = "right">
															<label for = "cga">'.$langs->trans('PDFInfraSPlusCGAchk').'</label>&nbsp;
															<select class = "flat cursorpointer width200" id = "selectcga" name = "cga">
																<option name = "cga" value = "" data-html = "'.dol_escape_htmltag($langs->trans('InfraSPlusParamNoCGA')).'">'.$langs->trans('InfraSPlusParamNoCGA').'</option>';
							for ($i = 0; $i < count($CGAs); $i++) {
								$this->resprints	.=	'		<option name = "cga" value = "'.$CGAs[$i].($cgabydef === $CGAs[$i] ? '" selected' : '"').' data-html = "'.dol_escape_htmltag($CGAs[$i]).'">'.$CGAs[$i].'</option>';
							}
							$this->resprints	.= '		</select>';
							$this->resprints	.= ajax_combobox('selectcga', array(), 0, 0, 'resolve');
							$this->resprints	.= '	</td>
													</tr>';
							unset($i);
						}
					} else {
						$this->resprints	.= '<input type = "hidden" name = "cga" value = '.GETPOST('cga', 'alpha').'>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "cgv" value = '.GETPOST('cgv', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "cgi" value = '.GETPOST('cgi', 'alpha').'>';
					$this->resprints	.= '<input type = "hidden" name = "cga" value = '.GETPOST('cga', 'alpha').'>';
				}
				// Fichiers joints à fusionner
				if (!$object instanceof Product) {
					$filesPost			= GETPOST('filesArray', 'alpha') == 'none' ? '' : GETPOST('filesArray', 'alpha');
					$selected_Files		= is_array($filesPost) ? $filesPost : explode ('-', $filesPost);
					// Update list of files in database
					$system_upload_dir	= '';
					$filesArray			= array();
					if ($object->element == 'propal') {
						$system_upload_dir	= !empty($conf->propal->multidir_output[$conf->entity]) ? $conf->propal->multidir_output[$conf->entity] : $conf->propal->dir_output;
					} elseif ($object->element == 'commande') {
						$system_upload_dir	= !empty($conf->commande->multidir_output[$conf->entity]) ? $conf->commande->multidir_output[$conf->entity] : $conf->commande->dir_output;
					} elseif ($object->element == 'facture') {
						$system_upload_dir	= !empty($conf->facture->multidir_output[$conf->entity]) ? $conf->facture->multidir_output[$conf->entity] : $conf->facture->dir_output;
					} elseif ($object->element == 'contrat') {
						$system_upload_dir	= !empty($conf->contrat->multidir_output[$conf->entity]) ? $conf->contrat->multidir_output[$conf->entity] : $conf->contrat->dir_output;
					} elseif ($object->element == 'shipping') {
						$system_upload_dir	= !empty($conf->expedition->multidir_output[$conf->entity]) ? $conf->expedition->multidir_output[$conf->entity] : $conf->expedition->dir_output;
					} elseif ($object->element == 'fichinter') {
						$system_upload_dir	= !empty($conf->ficheinter->multidir_output[$conf->entity]) ? $conf->ficheinter->multidir_output[$conf->entity] : $conf->ficheinter->dir_output;
					} elseif ($object->element == 'order_supplier') {
						$system_upload_dir	= !empty($conf->fournisseur->commande->multidir_output[$conf->entity]) ? $conf->fournisseur->commande->multidir_output[$conf->entity] : $conf->fournisseur->commande->dir_output;
					} elseif ($object->element == 'supplier_proposal') {
						$system_upload_dir	= !empty($conf->supplierproposal->multidir_output[$conf->entity]) ? $conf->supplierproposal->multidir_output[$conf->entity] : $conf->supplierproposal->dir_output;
					} elseif ($object->element == 'project') {
						$system_upload_dir	= !empty($conf->projet->multidir_output[$conf->entity]) ? $conf->projet->multidir_output[$conf->entity] : $conf->projet->dir_output;
					} elseif ($object->element == 'mo') {
						$system_upload_dir	= !empty($conf->mrp->multidir_output[$conf->entity]) ? $conf->mrp->multidir_output[$conf->entity] : $conf->mrp->dir_output;
					} elseif ($object->element == 'bom') {
						$system_upload_dir	= !empty($conf->bom->multidir_output[$conf->entity]) ? $conf->bom->multidir_output[$conf->entity] : $conf->bom->dir_output;
					} elseif ($object->element == 'expensereport') {
						$system_upload_dir	= !empty($conf->expensereport->multidir_output[$conf->entity]) ? $conf->expensereport->multidir_output[$conf->entity] : $conf->expensereport->dir_output;
					} elseif ($object->element == 'user') {
						$system_upload_dir	= !empty($conf->user->multidir_output[$conf->entity]) ? $conf->user->multidir_output[$conf->entity] : $conf->user->dir_output;
					}
					if (!empty($system_upload_dir)) {
						$system_upload_dir			.= '/'.dol_sanitizeFileName($object->ref);
						$filesArray					= dol_dir_list($system_upload_dir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
						$system_upload_relative_dir	= preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $system_upload_dir);
						$system_upload_relative_dir	= preg_replace('/^[\\/]/', '', $system_upload_relative_dir);
						completeFileArrayWithDatabaseInfo($filesArray, $system_upload_relative_dir);
					}
					$filesFromBom	= getDolGlobalInt('INFRASPLUS_PDF_FILES_FROM_BOM', 0);
					if (!empty($filesFromBom) && $object instanceof Mo) {
						require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
						$bomstatic		= new BOM($db);
						$bomstatic->fetch($object->fk_bom);
						$upload_dir2	= 'bom/'.dol_sanitizeFileName($bomstatic->ref);
						$filesArray2	= dol_dir_list_in_database($upload_dir2, '\.pdf$', array('(\.meta|_preview.*\.png)$','^\.'));
						$filesArray		= array_merge($filesArray, $filesArray2);
					}
					$filesFromProject	= getDolGlobalInt('INFRASPLUS_PDF_FILES_FROM_PROJECT', 0);
					if (!empty($filesFromProject) && !$object instanceof Project) {
						require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
						$projectstatic	= new Project($db);
						$projectstatic->fetch($object->fk_project);
						$upload_dir3	= 'projet/'.dol_sanitizeFileName($projectstatic->ref);
						$filesArray3	= dol_dir_list_in_database($upload_dir3, '\.pdf$', array('(\.meta|_preview.*\.png)$','^\.'));
						$filesArray		= array_merge($filesArray, $filesArray3);
					}
					// fichiers du module Attestation de TVA
					if (isModEnabled('attestationtva')) {
						$filesFromAttestationTVA	= getDolGlobalInt('INFRASPLUS_PDF_FILES_FROM_ATTESTATIONTVA', 0);
						if (!empty($filesFromAttestationTVA) && $object instanceof Propal) {
							dol_include_once('/attestationtva/class/attestationtva.class.php');
							if (class_exists('AttestationTVA')) {
								$attestationtvastatic	= new AttestationTVA($db);
								$attestationtvastatic->fetch('', $object->id);
								$upload_dir4			= 'attestationtva/'.dol_sanitizeFileName($attestationtvastatic->ref);
								$filesArray4			= dol_dir_list_in_database($upload_dir4, '\.pdf$', array('(\.meta|_preview.*\.png)$','^\.'));
								$filesArray				= array_merge($filesArray, $filesArray4);
							}
						}
					}
					$paramspecialfiles	= getDolGlobalString('INFRASPLUS_PDF_SPECIAL_FILES', '');
					if (!empty($paramspecialfiles)) {
						$paramspecialfiles	= explode(',', $paramspecialfiles);
						$dirpdfs			= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/specialfiles';
						$listpdfs			= dol_dir_list($dirpdfs, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
						$listspecialfiles	= dol_dir_list(dol_buildpath('/infraspackplus/core/modules/specialfiles', 0), 'files', 0, '\.php$', null, 'name', SORT_ASC, 0, 0, '', 0);
						$listspecialfiles	= array_column($listspecialfiles, 'name');
						$filesArray4		= array();
						foreach ($listpdfs as $pdf) {
							if (empty($pdf['name'])) {
								continue;
							}
							$pdfname	= pathinfo($pdf['name'], PATHINFO_FILENAME);
							if (in_array($pdfname, $paramspecialfiles)) {
								$key		= 'INFRASPLUS_PDF_SPECIAL_FILE_'.(strtoupper($object->element)).'_'.(strtoupper($pdfname));
								$key_AUTO	= 'INFRASPLUS_PDF_SPECIAL_FILE_'.(strtoupper($object->element)).'_'.(strtoupper($pdfname)).'_AUTO';
								$showfile	= getDolGlobalInt($key, 0);
								if (empty($showfile)) {
									$showfile		= getDolGlobalInt($key_AUTO, 0);
								}
								if (!empty($showfile) && in_array($pdfname.'.php', $listspecialfiles)) {
									$filesArray4[]	= $pdf;
								}
							}
						}
						completeFileArrayWithDatabaseInfo($filesArray4, (!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/specialfiles');
						$filesArray	= array_merge($filesArray, $filesArray4);
					}
					$arrayFiles	= array();
					if (is_array($filesArray) && count($filesArray) > 0) {
						dol_syslog('actions_infraspackplus.class::formBuilddocOptions count($filesArray) = '.count($filesArray));
						foreach($filesArray as $file) {
							$arrayFiles[$file['rowid']] = $file['name'];
						}
						$form				= new Form($db);
						$this->resprints	.=	'<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "filesArray">'.$langs->trans('PDFInfraSPlusFiles').'</label>&nbsp;';
						$this->resprints	.= $form->multiselectarray('filesArray', $arrayFiles, $selected_Files, 0, 0, '', 0, '400px');
						$this->resprints	.=		'</td>
												</tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "filesArray" value = '.GETPOST('filesArray', 'alpha').'>';
				}
				// Pièces jointes à fusionner aux notes de frais
				if ($object instanceof ExpenseReport) {
					$expensereportFilesPost	= empty(GETPOST('expensereportfiles', 'alpha')) || GETPOST('expensereportfiles', 'alpha') == 'none' ? 0 : 1;
					$this->resprints		.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "expensereportfiles">'.$langs->trans('InfraSPlusParamFilesFromExpensereport').'</label>&nbsp;
														<input type = "checkbox" name = "expensereportfiles" value = "expensereportfiles" '.($expensereportFilesPost ? 'checked' : '').' class = "cursorpointer">
													</td>
												</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "expensereportfiles" value = '.GETPOST('expensereportfiles', 'alpha').'>';
				}
				// Alias
				if (!in_array($object->element, array('product', 'mo', 'bom'))) {
					$includealiasPost	= empty(GETPOST('includealias', 'alpha')) || GETPOST('includealias', 'alpha') == 'none' ? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "includealias">'.$langs->trans('PDFParamAliasIn3rdName').'</label>&nbsp;
													<input type = "checkbox" name = "includealias" value = "includealias" '.($includealiasPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "includealias" value = '.GETPOST('includealias', 'alpha').'>';
				}
				// Fusion documentation produits / services
				if (in_array($object->element, array('propal'))) {
					$productMerge	= getDolGlobalInt('INFRASPLUS_PDF_PRODUIT_MERGE_PROPAL', 0);
					if (!empty($productMerge)) {
						$mergeproductPost	= empty(GETPOST('mergeproduct', 'alpha')) || GETPOST('mergeproduct', 'alpha') == 'none' ? 0 : 1;
						$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "mergeproduct">'.$langs->trans('PDFInfraSPlusMergeProduct').'</label>&nbsp;
														<input type = "checkbox" name = "mergeproduct" value = "mergeproduct" '.($mergeproductPost ? 'checked' : '').' class = "cursorpointer">
													</td>
												</tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "mergeproduct" value = '.GETPOST('mergeproduct', 'alpha').'>';
				}
				// Page de garde
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat'))) {
					if (!empty($usentascover)) {
						$usentascoverpost	= empty(GETPOST('usentascover', 'alpha')) || GETPOST('usentascover', 'alpha') == 'none' ? 0 : 1;
						$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "usentascover">'.$langs->trans('PDFInfraSPlusUseNtAsCover').'</label>&nbsp;
														<input type = "checkbox" name = "usentascover" value = "'.$rootnotepub.'_'.$usentascover.'" '.($usentascoverpost ? 'checked' : '').' class = "cursorpointer">
													</td>
												</tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "usentascover" value = '.GETPOST('usentascover', 'alpha').'>';
				}
				// Infos Douanières (Poids, volume, dimensions et code SH
				if (in_array($object->element, array('propal', 'commande', 'facture', 'shipping', 'reception', 'delivery'))) {
					$wvccPost	= GETPOST('showwvccchk', 'alpha');
					if ($wvccPost != -2) {
						$showwvccchk		= empty($wvccPost) || $wvccPost == 'none' ? 0 : 1;
						$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "showwvccchk">'.$langs->trans('PDFInfraSPlusShowWVCCchk').'</label>&nbsp;
														<input type = "checkbox" name = "showwvccchk" value = "showwvccchk" '.($showwvccchk ? 'checked' : '').' class = "cursorpointer">
													</td>
												</tr>';
					} else {
						$this->resprints	.= '<input type = "hidden" name = "showwvccchk" value = '.GETPOST('showwvccchk', 'alpha').'>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showwvccchk" value = '.GETPOST('showwvccchk', 'alpha').'>';
				}
				// Image des produits / services dans les documents client ou les commandes fournisseur
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'reception', 'delivery', 'order_supplier'))) {
					$hidepictPost		= empty(GETPOST('hidepict', 'alpha')) || GETPOST('hidepict', 'alpha') == 'none' ? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "hidepict">'.$langs->trans('PDFInfraSPlusHidePictchk').'</label>&nbsp;
													<input type = "checkbox" name = "hidepict" value = "hidepict" '.($hidepictPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "hidepict" value = '.GETPOST('hidepict', 'alpha').'>';
				}
				// colonne référence
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'reception', 'delivery'))) {
					$hasrefcol			= getDolGlobalInt('INFRASPLUS_PDF_WITH_REF_COLUMN', 0);
					$refcolPost			= empty(GETPOST('refcol', 'alpha')) || GETPOST('refcol', 'alpha') == 'none' ? 0 : 1;
					if (!empty($hasrefcol)) {
						$this->resprints .= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "refcol">'.$langs->trans('PDFInfraSPlusShowRefCol').'</label>&nbsp;
													<input type = "checkbox" name = "refcol" value = "refcol" '.($refcolPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "refcol" value = '.GETPOST('refcol', 'alpha').'>';
				}
				// durées (total et ligne par ligne) dans les fiches d'intervention
				if (in_array($object->element, array('fichinter'))) {
					$hidetimespentPost	= empty(GETPOST('hidetimespent', 'alpha')) || GETPOST('hidetimespent', 'alpha') == 'none'	? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "hidetimespent">'.$langs->trans('PDFInfraSPlusHidetimeSpentchk').'</label>&nbsp;
													<input type = "checkbox" name = "hidetimespent" value = "hidetimespent" '.($hidetimespentPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "hidetimespent" value = '.GETPOST('hidetimespent', 'alpha').'>';
				}
				// Description longue des produits / services
				$hidelabel	= getDolGlobalInt('INFRASPLUS_PDF_HIDE_LABEL', 0);
				if (in_array($object->element, array('propal', 'commande', 'facture', 'fichinter', 'shipping', 'reception', 'supplier_proposal', 'order_supplier')) && empty($hidelabel)) {
					$hidedescPost		= empty(GETPOST('hidedesc', 'alpha')) || GETPOST('hidedesc', 'alpha') == 'none'	? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "hidedesc">'.$langs->trans('PDFInfraSPlusHideDescchk').'</label>&nbsp;
													<input type = "checkbox" name = "hidedesc" value = "hidedesc" '.($hidedescPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "hidedesc" value = '.GETPOST('hidedesc', 'alpha').'>';
				}
				// Remise
				$discountAuto	= getDolGlobalInt('INFRASPLUS_PDF_DISCOUNT_AUTO', 0);
				$hidediscPost	= empty(GETPOST('hidedisc', 'alpha')) || GETPOST('hidedisc', 'alpha') == 'none' ? 0 : 1;
				if (in_array($object->element, array('propal', 'commande', 'contrat', 'facture', 'fichinter')) && empty($discountAuto)) {
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "hidedisc">'.$langs->trans('PDFInfraSPlusHideDiscchk').'</label>&nbsp;
													<input type = "checkbox" name = "hidedisc" value = "hidedisc" '.($hidediscPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "hidedisc" value = '.GETPOST('hidedisc', 'alpha').'>';
				}
				// Prix brutes
				$enable_rawprices	= getDolGlobalInt('INFRASPLUS_PDF_PROPAL_WITH_RAW_PRICES', 0);
				if (in_array($object->element, array('propal')) && !empty($enable_rawprices)) {
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "rawprices">'.$langs->trans('PDFInfraSPlusRawPriceschk').'</label>&nbsp;
													<input type = "checkbox" name = "rawprices" value = "rawprices" class = "cursorpointer">
												</td>
											</tr>';
				}
				// Description seule
				if (in_array($object->element, array('propal', 'commande', 'contrat', 'fichinter'))) {
					$hidecolsPost		= empty(GETPOST('hidecols', 'alpha')) || GETPOST('hidecols', 'alpha') == 'none' ? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "hidecols">'.$langs->trans('PDFInfraSPlusHideColschk').'</label>&nbsp;
													<input type = "checkbox" name = "hidecols" value = "hidecols" '.($hidecolsPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "hidecols" value = '.GETPOST('hidecols', 'alpha').'>';
				}
				// Affichage du total HT sur le BL
				$showpriceblPost	= empty(GETPOST('showpricebl', 'alpha')) || GETPOST('showpricebl', 'alpha') == 'none' ? 0 : 1;
				if (in_array($object->element, array('shipping'))) {
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "showpricebl">'.$langs->trans('PDFInfraSPlusShowPriceBLchk').'</label>&nbsp;
													<input type = "checkbox" name = "showpricebl" value = "showpricebl" '.($showpriceblPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showpricebl" value = '.GETPOST('showpricebl', 'alpha').'>';
				}
				// Information concernant l'adresse de facturation automatique
				if (in_array($object->element, array('facture'))) {
					$def_adrfact		= getDolGlobalString('INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT', '');
					$adrfactPost		= empty(GETPOST('adrfact', 'alpha')) || GETPOST('adrfact', 'alpha') == 'none' ? 0 : 1;
					$client				= infraspackplus_check_parent_addr_fact($object);
					$adrfacttmp			= new Address($db);
					$res_adrfact		= $adrfacttmp->fetch(0, $client->id, $def_adrfact);
					if ($res_adrfact > 0) {
						$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "adrfact">'.$langs->trans('PDFInfraSPlusAdrFact').' '.$adrfacttmp->name.' ('.$adrfacttmp->label.')'.'</label>&nbsp;
														<input type = "checkbox" id = "adrfact" name = "adrfact" value = "'.$def_adrfact.'" '.($adrfactPost ? 'checked' : '').' class = "cursorpointer">
													</td>
												</tr>';
					} else {
						$this->resprints	.=	'	<tr class = "oddeven infrasfoldable InfraSPermLastOpt"><td colspan = "'.$colspan.'" align = "right"></td></tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "adrfact" value = '.GETPOST('adrfact', 'alpha').'>';
				}
				// Affichage du total des remises si l'inclusion dans la table des totaux est inactive
				if (in_array($object->element, array('propal', 'commande', 'facture')) && !getDolGlobalInt('INFRASPLUS_PDF_SHOW_DISCOUNT_TOT',0)) {
					$showtotdiscPost	= empty(GETPOST('showtotdisc', 'alpha')) || GETPOST('showtotdisc', 'alpha') == 'none' ? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "showtotdisc">'.$langs->trans('InfraSPlusShowTotDiscChk').'</label>&nbsp;
													<input type = "checkbox" name = "showtotdisc" value = "showtotdisc" '.($showtotdiscPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showtotdisc" value = '.GETPOST('showtotdisc', 'alpha').'>';
				}
				// Affichage de la mention d'autoliquidation BTP
				if (in_array($object->element, array('propal', 'commande', 'facture', 'order_supplier'))) {
					$hastxttvabtp	= getDolGlobalInt('INFRASPLUS_PDF_FREETEXT_TVA_6', 0);
					if (!empty($hastxttvabtp)) {
						$showtvabtpPost		= empty(GETPOST('showtvabtp', 'alpha')) || GETPOST('showtvabtp', 'alpha') == 'none'	? 0	: 1;
						$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "showtvabtp">'.$langs->trans('InfraSPlusShowTVAtxtBTPChk').'</label>&nbsp;
														<input type = "checkbox" name = "showtvabtp" value = "showtvabtp" '.($showtvabtpPost ? 'checked' : '').' class = "cursorpointer">
													</td>
												</tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showtvabtp" value = '.GETPOST('showtvabtp', 'alpha').'>';
				}
				// Affichage des totaux en pied de document sur les fiches d'intervention
				if (in_array($object->element, array('fichinter'))) {
					$showtotPost		= empty(GETPOST('showtot', 'alpha')) || GETPOST('showtot', 'alpha') == 'none' ? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "showtot">'.$langs->trans('InfraSPlusShowTotChk').'</label>&nbsp;
													<input type = "checkbox" name = "showtot" value = "showtot" '.($showtotPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showtot" value = '.GETPOST('showtot', 'alpha').'>';
				}
				// Afficher / Masquer le mode de paiement par virement
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter'))) {
					$showvirPost		= empty(GETPOST('showvir', 'alpha')) || GETPOST('showvir', 'alpha') == 'none' ? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "showvir">'.$langs->trans('InfraSPlusShowVirChk').'</label>&nbsp;
													<input type = "checkbox" name = "showvir" value = "showvir" '.($showvirPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showvir" value = '.GETPOST('showvir', 'alpha').'>';
				}
				// Désactivation des paiements spéciaux
				if (in_array($object->element, array('propal'))) {
					$showpayspecPost	= empty(GETPOST('showpayspec', 'alpha')) || GETPOST('showpayspec', 'alpha') == 'none' ? 0 : 1;
					$this->resprints	.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
												<td colspan = "'.$colspan.'" align = "right">
													<label for = "showpayspec">'.$langs->trans('InfraSPlusShowPaySpecChk').'</label>&nbsp;
													<input type = "checkbox" name = "showpayspec" value = "showpayspec" '.($showpayspecPost ? 'checked' : '').' class = "cursorpointer">
												</td>
											</tr>';
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showpayspec" value = '.GETPOST('showpayspec', 'alpha').'>';
				}

				// Afficher / Masquer la zone de signature société émettrice sur les fiches d'intervention
				if (in_array($object->element, array('propal'))) {
					$hassignemet	= getDolGlobalInt('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE_EMET', 0);
					if (!empty($hassignemet)) {
						$showPropalSignEmetPost	= empty(GETPOST('showPropalSignEmet', 'alpha')) || GETPOST('showPropalSignEmet', 'alpha') == 'none' ? 0 : 1;
						$this->resprints		.= '<tr class = "oddeven infrasfoldable InfraSPermLastOpt">
														<td colspan = "'.$colspan.'" align = "right">
															<label for = "showPropalSignEmet">'.$langs->trans('InfraSPlusShowPropalSignEmetChk').'</label>&nbsp;
															<input type = "checkbox" name = "showPropalSignEmet" value = "showPropalSignEmet" '.($showPropalSignEmetPost ? 'checked' : '').' class = "cursorpointer">
														</td>
													</tr>';
					}
				} else {
					$this->resprints	.= '<input type = "hidden" name = "showPropalSignEmet" value = '.GETPOST('showPropalSignEmet', 'alpha').'>';
				}

				// Saisie de la signature client (PAD)
				if (in_array($object->element, array('propal', 'commande', 'contrat', 'fichinter', 'shipping', 'reception', 'project'))) {
					$getSign	= getDolGlobalInt('INFRASPLUS_PDF_GET_CUSTOMER_SIGNING', 0);
					// N'afficher le bouton de signature que pour les documents validés
					if (($object instanceof Propal && $object->status == Propal::STATUS_VALIDATED) || ($object instanceof Commande && $object->status == Commande::STATUS_VALIDATED) ||
						($object instanceof Contrat && $object->status == Contrat::STATUS_VALIDATED) || ($object instanceof Fichinter && $object->status == Fichinter::STATUS_VALIDATED) ||
						($object instanceof Expedition && $object->status == Expedition::STATUS_VALIDATED) || ($object instanceof Reception && $object->status == Reception::STATUS_VALIDATED) ||
						($object instanceof Project && $object->status == Project::STATUS_VALIDATED))
					{
						$isOk	= 1;
					} else {
						$isOk	= 0;
					}
					if (!empty($getSign) && !empty($isOk)) {
						$this->resprints	.= '<tr class = "oddeven">
													<td colspan = "'.$colspan.'" align = "right">
														<label for = "getClientSign">'.$langs->trans('InfraSPlusShowClientSignPopup').'</label>&nbsp;
														<a id = "getClientSign" rel = "getClientSign" href = "javascript:;" class = "butAction">'.$langs->trans('InfraSPlusSSign').'</a>
														<input type = "hidden" id = "signvalue" name = "signvalue" value = "">
													</td>
												</tr>';
					}
				}
				// ligne de séparation fin des options InfraSPackPlus
				if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'reception', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'mo', 'bom', 'project', 'expensereport'))) {
					$this->resprints	.= '<tr class = "infrasplusbgtrans"><td class = "center nopadding" colspan = "'.$colspan.'"><hr class = "quatrevingtpercent"></td></tr>';
				}
			}
			return 0;
		}

		/**
		* When we ask to generate a PDF document (../modules/type of element/doc/pdf_ModelName.modules.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		public function beforePDFCreation($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $db, $mysoc, $user;

			$_SESSION['InfraSPackPlus_model']	= true;	// Write a session variable to indicate that we are using an InfraSPackPlus template
			$manualPrint						= GETPOST('action', 'alpha') == 'builddoc' ? 1 : 0;	// from html.formfile.class.php => showdocuments
			pdf_InfraSPlus_getInstance(array(), 'mm', 'P', true);
			if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'reception', 'delivery', 'supplier_proposal', 'order_supplier', 'product', 'mo', 'bom', 'project', 'expensereport', 'user'))) {
				$freeadrlivr		= getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', '');
				// Récupération des paramètres sauvegardés (Liés à l'utilisateur, au document ou par défaut => configuration module)
				$defaultParams		= infraspackplus_defaultParam($object);
				$listOptions		= $defaultParams['listOptions'];
				$listModulesFreeT	= $defaultParams['listModulesFreeT'];	// liste nécessaire à la gestion des mentions complémentaires
				$listModulesNoteP	= $defaultParams['listModulesNoteP'];	// liste nécessaire à la gestion des notes publiques
				if (empty($manualPrint)) {
					// Application des paramètres
					foreach ($listOptions as $key => $option) {
						$_POST[$key]	= $listOptions[$key]['value'];
					}
				}
				// Logo
				$this->results['logo']					= GETPOST('logo', 'alpha') == 'none' ? '' : GETPOST('logo', 'alpha');
				// adresse expéditeur
				$this->results['adr']					= GETPOST('adr', 'alpha') == 'none' ? '' : GETPOST('adr', 'int');
				// adresse destinataire
				$this->results['customerAddrSelect']	= GETPOST('customerAddrSelect', 'alpha') == 'none' ? '' : GETPOST('customerAddrSelect', 'alpha');
				// Mentions complémentaires
				$this->results['listfreet']				= array();
				$freeTPost								= GETPOST('listfreet', 'alpha') == 'none' ? '' : GETPOST('listfreet', 'none');
				if ($freeTPost == '' && GETPOST('showsysmcbase', 'alpha') == '') {
					$this->results['listfreet']	= 'none';
				}
				if (GETPOST('showsysmcbase', 'alpha') != '') {
					$this->results['listfreet'][]	= GETPOST('showsysmcbase', 'alpha');
				}
				if (is_array($this->results['listfreet']) && is_array ($freeTPost)) {
					$this->results['listfreet']		= array_merge($this->results['listfreet'], $freeTPost);
				} elseif ($freeTPost != '') {
					$this->results['listfreet'][]	= GETPOST('listfreet', 'alpha');
				}
				if (is_array($this->results['listfreet'])) {
					$this->results['listfreet']	= array_flip(array_flip($this->results['listfreet']));	// clean array for unique keys
				}
				// Notes publiques standards
				$this->results['listnotep']	= array();
				$notePPost					= GETPOST('listnotep', 'alpha') == 'none' ? '' : GETPOST('listnotep', 'none');
				if ($notePPost == '' && GETPOST('showsysntbase', 'alpha') == '') {
					$this->results['listnotep']		= 'none';
				}
				if (GETPOST('showsysntbase', 'alpha') != '') {
					$this->results['listnotep'][]	= GETPOST('showsysntbase', 'alpha');
				}
				if (is_array($this->results['listnotep']) && is_array ($notePPost)) {
					$this->results['listnotep']		= array_merge($this->results['listnotep'], $notePPost);
				} elseif ($notePPost != '') {
					$this->results['listnotep'][]	= $notePPost;
				}
				if (is_array($this->results['listnotep'])) {
					$this->results['listnotep']	= array_flip(array_flip($this->results['listnotep'])); // clean array for unique keys
				}
				// Pied
				$this->results['pied']	= GETPOST('pied', 'alpha') == 'none' ? '' : GETPOST('pied', 'alpha');
				// Adresse de livraison (client)
				$showadrlivr	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', 0);
				if (!empty($showadrlivr)) {
					$this->results['adrlivr']	= GETPOST('adrlivr', 'int');
				}
				// Sous-Traitant (lié au client via "CustomLink")
				if (isModEnabled('customlink')) {
					$showadrSsT	= getDolGlobalInt('INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT', 0);
					if (!empty($showadrSsT)) {
						$this->results['Sst']		= GETPOST('Sst', 'int');
						$this->results['adrSst']	= GETPOST('adrSst', 'int');
					}
				}
				// Adresse de livraison spéciale fournisseur (interne ou interne + client)
				$showadrlivrfour	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON', 0);
				$showadrlivrfour	= !empty($useDoliAddr) || !empty($object->array_options['options_'.$freeadrlivr]) ? 0 : $showadrlivrfour;
				$adrlivrfourmixte	= getDolGlobalInt('INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE', 0);
				if (!empty($showadrlivrfour)) {
					if (empty($adrlivrfourmixte)) {	// only internal
						$this->results['adrlivrfour']	= GETPOST('adrlivrfour', 'int');
						$this->results['typeadr']		= 'I';
					} else {
						if (!preg_match('/\_/', GETPOST('adrlivrfour', 'alpha'))) {	// default => internal
							$this->results['adrlivrfour']	= GETPOST('adrlivrfour', 'int');
							$this->results['typeadr']		= 'I';
						} else {
							$this->results['adrlivrfour']	= substr(GETPOST('adrlivrfour', 'alpha'), 2);
							$this->results['typeadr']		= substr(GETPOST('adrlivrfour', 'alpha'), 0, 1);
						}
					}
				}
				// CGV
				$this->results['cgv']			= GETPOST('cgv', 'alpha') == 'none' ? '' : GETPOST('cgv', 'alpha');
				// CGI
				$this->results['cgi']			= GETPOST('cgi', 'alpha') == 'none' ? '' : GETPOST('cgi', 'alpha');
				// CGA
				$this->results['cga']			= GETPOST('cga', 'alpha') == 'none' ? '' : GETPOST('cga', 'alpha');
				// Fichiers joints à fusionner
				$this->results['filesArray']	= array();
				if (GETPOST('filesArray', 'alpha') == '') {
					$this->results['filesArray']	= 'none';
				} elseif (is_array($this->results['filesArray']) && is_array (GETPOST('filesArray', 'array'))) {
					$this->results['filesArray']	= array_merge($this->results['filesArray'], GETPOST('filesArray', 'array'));
				} elseif (GETPOST('filesArray', 'alpha') != '') {
					$this->results['filesArray']	= GETPOST('filesArray', 'alpha');
				}
				// Pièces jointes à fusionner aux notes de frais
				$this->results['expensereportfiles']	= GETPOST('expensereportfiles', 'alpha') == 'none' ? '' : GETPOST('expensereportfiles', 'alpha');
				// Alias
				$this->results['includealias']			= GETPOST('includealias', 'alpha') == 'none' ? '' : GETPOST('includealias', 'alpha');
				// Fusion documentation produits / services
				$this->results['mergeproduct']			= GETPOST('mergeproduct', 'alpha') == 'none' ? '' : GETPOST('mergeproduct', 'alpha');
				// Page de garde
				$this->results['usentascover']			= GETPOST('usentascover', 'alpha') == 'none' ? '' : GETPOST('usentascover', 'alpha');
				// Infos Douanières (Poids, volume, dimensions et code SH
				$wvccPost								= GETPOST('showwvccchk', 'alpha') == 'none' ? '' : GETPOST('showwvccchk', 'alpha');
				$this->results['showwvccchk']			= $wvccPost == -2 ? '' : $wvccPost;
				// Image des produits / services dans les documents client ou dans les commandes fournisseur
				$this->results['hidepict']				= GETPOST('hidepict', 'alpha') == 'none' ? 0 : GETPOST('hidepict', 'alpha');
				// colonne référence
				$this->results['refcol']				= GETPOST('refcol', 'alpha') == 'none' ? 0 : GETPOST('refcol', 'alpha');
				// durées (total et ligne par ligne) dans les fiches d'intervention
				$this->results['hidetimespent']			= GETPOST('hidetimespent', 'alpha') == 'none' ? 0 : GETPOST('hidetimespent', 'alpha');
				// Description longue des produits / services
				if (!getDolGlobalString('INFRASPLUS_PDF_HIDE_LABEL', '')) {
					$this->results['hidedesc'] = GETPOST('hidedesc', 'alpha') == 'none' ? 0 : GETPOST('hidedesc', 'alpha');
				}
				// Remise
				$discountAuto							= getDolGlobalInt('INFRASPLUS_PDF_DISCOUNT_AUTO', 0);
				$this->results['hidedisc']				= !empty($discountAuto) || GETPOST('hidedisc', 'alpha') == 'none' ? 0 : GETPOST('hidedisc', 'alpha');
				// Prix brutes
				$this->results['rawprices']				= GETPOST('rawprices', 'alpha') == 'none' ? 0 : GETPOST('rawprices', 'alpha');
				// Description seule
				$this->results['hidecols']				= GETPOST('hidecols', 'alpha') == 'none' ? 0 : GETPOST('hidecols', 'alpha');
				// Affichage du total HT sur le BL
				$showpricebl							= getDolGlobalInt('INFRASPLUS_PDF_BL_WITH_PRICE', 0);
				$this->results['showpricebl']			= empty($showpricebl) || GETPOST('showpricebl', 'alpha') == 'none' ? 0 : GETPOST('showpricebl', 'alpha');
				// Information concernant l'adresse de facturation automatique
				$this->results['adrfact']				= GETPOST('adrfact', 'alpha') == 'none' ? 0 : GETPOST('adrfact', 'alpha');
				// Affichage du total des remises si l'inclusion dans la table des totaux est inactive
				$this->results['showtotdisc']			= GETPOST('showtotdisc', 'alpha') == 'none' ? 0 : GETPOST('showtotdisc', 'alpha');
				// Affichage de la mention d'autoliquidation BTP
				$this->results['showtvabtp']			= GETPOST('showtvabtp', 'alpha') == 'none' ? 0 : GETPOST('showtvabtp', 'alpha');
				// Affichage des totaux en pied de document sur les fichiches d'intervention
				$this->results['showtot']				= GETPOST('showtot', 'alpha') == 'none' ? 0 : GETPOST('showtot', 'alpha');
				// Afficher / Masquer le mode de paiement par virement
				$this->results['showvir']				= GETPOST('showvir', 'alpha') == 'none' ? 0 : GETPOST('showvir', 'alpha');
				// Désactivation des paiements spéciaux
				$this->results['showpayspec']			= GETPOST('showpayspec', 'alpha') == 'none' ? 0 : GETPOST('showpayspec', 'alpha');
				// Afficher / Masquer la zone de signature société émettrice sur les fiches d'intervention
				$this->results['showPropalSignEmet']	= GETPOST('showPropalSignEmet', 'alpha') == 'none' ? 0 : GETPOST('showPropalSignEmet', 'alpha');
				// Saisie de la signature client
				$this->results['signvalue']				= GETPOST('signvalue', 'alpha');
				// Options du module Sous-total
				if (isModEnabled('subtotal')) {
					$this->results['hideInnerLines']	= GETPOST('hideInnerLines', 'int');
					if (getDolGlobalString('SUBTOTAL_PROPAL_ADD_RECAP', '') && in_array($object->element, array('propal'))
						|| (getDolGlobalString('SUBTOTAL_COMMANDE_ADD_RECAP', '') && in_array($object->element, array('commande')))
						|| (getDolGlobalString('SUBTOTAL_INVOICE_ADD_RECAP', '') && in_array($object->element, array('facture')))) {
						$this->results['subtotal_add_recap']	= GETPOST('subtotal_add_recap');
					}
				}
				// enregistrement des choix utilisateur
				$paramsResultsUser	= array();
				$paramsResultsDoc	= array();
				$paramsResultsType	= array();
				$paramsResultsCust	= array();
				foreach ($listOptions as $option => $optionParams) {
					$constname	= 'INFRASPLUS_PDF_OPTION_'.$option;
					if (!empty($this->results[$option])) {
						$constvalue	= is_array($this->results[$option]) && count($this->results[$option]) > 0 ? implode('-', $this->results[$option]) : $this->results[$option];
					} else {
						$constvalue	= 'none';
					}
					$bkptype	= getDolGlobalString($constname, 'none');
					if ($bkptype == 'user') {
						$paramsResultsUser[$option]	= $constvalue;
					} elseif ($bkptype == 'doc') {
						$paramsResultsDoc[$option]	= $constvalue;
					} elseif ($bkptype == 'type') {
						$paramsResultsType[$option]	= $constvalue;
					} elseif ($bkptype == 'cust') {
						$paramsResultsCust[$option]	= $constvalue;
					}
				}
				$txtResultsParamsUser	= http_build_query ($paramsResultsUser, '');	// écriture de la chaine
				dol_syslog('actions_infraspackplus.class::beforePDFCreation txtResultsParamsUser = '.$txtResultsParamsUser);
				dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_USER_'.$user->id,	$txtResultsParamsUser, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);	// enregistrement de la chaine
				$txtResultsParamsDoc	= http_build_query ($paramsResultsDoc, '');	// écriture de la chaine
				dol_syslog('actions_infraspackplus.class::beforePDFCreation txtResultsParamsDoc = '.$txtResultsParamsDoc);
				dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_DOC_'.$object->id,	$txtResultsParamsDoc, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);	// enregistrement de la chaine
				$txtResultsParamsType	= http_build_query ($paramsResultsType, '');	// écriture de la chaine
				dol_syslog('actions_infraspackplus.class::beforePDFCreation txtResultsParamsType = '.$txtResultsParamsType);
				dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_TYPE',	$txtResultsParamsType, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);	// enregistrement de la chaine
				$txtResultsParamsCust	= http_build_query ($paramsResultsCust, '');	// écriture de la chaine
				dol_syslog('actions_infraspackplus.class::beforePDFCreation txtResultsParamsCust = '.$txtResultsParamsCust);
				dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_CUST_'.$object->thirdparty->id,	$txtResultsParamsCust, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);	// enregistrement de la chaine
			}
			return 0;
		}

		/**
		* When we finish to generate a PDF document (../modules/type of element/doc/pdf_ModelName.modules.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		public function afterPDFCreation($parameters, &$object, &$action, $hookmanager)
		{
			unset($_SESSION['InfraSPackPlus_model']);	// Destroys the session variable that indicates that we are using an InfraSPackPlus template
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
			global $conf, $db, $langs;

			$langs->load('infraspackplus@infraspackplus');

			// Colspan
			$colspanshort	= 3;
			$TContext		= explode(':', $parameters['context']);
			$ParamLogoEmet	= getDolGlobalInt('INFRASPLUS_PDF_SET_LOGO_EMET_TIERS', 0);
			if (in_array('thirdpartycard', $TContext) && $ParamLogoEmet) {
				$selected_logo_emet	= infraspackplus_getLogoEmet($object->id);
				if ($action == 'create' || $action == 'edit') {
					$listlogos	= array();
					$logodir	= !empty($conf->mycompany->multidir_output[$object->entity]) ? $conf->mycompany->multidir_output[$object->entity]	: $conf->mycompany->dir_output;
					foreach (glob($logodir.'/logos/*.jpg') as $file) {
						$listlogos[]	= dol_basename($file);
					}
					foreach (glob($logodir.'/logos/*.gif') as $file) {
						$listlogos[]	= dol_basename($file);
					}
					foreach (glob($logodir.'/logos/*.png') as $file) {
						$listlogos[]	= dol_basename($file);
					}
					$this->resprints	.= '<tr>
												<td>'.$langs->trans('PDFInfraSPlusLogo').'</td>
												<td colspan = "'.$colspanshort++.'" class = "maxwidthonsmartphone">
													<select class = "flat cursorpointer" name = "logosChoice">
														<option name = "logosChoice" value = "">'.$langs->trans('PDFInfraSPlusDefaultLogo').'</option>';
					for ($i = 0; $i < count($listlogos); $i++) {
						$this->resprints	.= '		<option name = "logosChoice" value = "'.$listlogos[$i].($selected_logo_emet === $listlogos[$i] ? '" selected' : '"').'>'.$listlogos[$i].'</option>';
					}
					$this->resprints	.=			'</select>
												</td>
											</tr>';
					unset($i);
				} else {
					$this->resprints	.= '<tr>
												<td>'.$langs->trans('PDFInfraSPlusLogo').'</td>
												<td colspan = "'.$colspanshort++.'" class = "maxwidthonsmartphone">'.($selected_logo_emet ? $selected_logo_emet : $langs->trans('PDFInfraSPlusDefaultLogo')).'</td>
											</tr>';
				}
			}
			return 0;
		}

		/**
		* When we ask for an action (../element/card.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function doActions($parameters, &$object, &$action, $hookmanager)
		{
			global $db, $conf, $langs, $user;

			$TContext	= explode(':', $parameters['context']);
			$ParamLogoEmet	= getDolGlobalInt('INFRASPLUS_PDF_SET_LOGO_EMET_TIERS', 0);
			if (in_array('thirdpartycard', $TContext) && $ParamLogoEmet) {
				if ($action == 'update' && !empty($user->hasRight('societe', 'creer'))) {
					$result	= infraspackplus_setLogoEmet($parameters['id'], GETPOST('logosChoice', 'alpha'));
					if (empty($result)) {
						return 0;
					} else {
						$this->errors[]	= $result;
						return -1;
					}
				}
			}
			if (getDolGlobalInt('INFRASPLUS_PDF_SEMIAUTOUPDATE', 0)) {
				$onNotesChange	= $action == 'setnote_public' && getDolGlobalInt('INFRASPLUS_PDF_UPDATE_ON_NOTES_CHANGE', 0) && !GETPOST('cancel', 'alpha') ? 1 : 0;	// If we want to generate the document PDF when notes are changed
				$onExfChange	= $action == 'update_extras' && getDolGlobalInt('INFRASPLUS_PDF_UPDATE_ON_EXF_CHANGE', 0) ? 1 : 0;	// If we want to generate the document PDF when extrafields are changed
				$onFieldsChange	= ($action == 'setecheance'|| $action =='setconditions' || $action =='setmode' || $action =='setbankaccount' || $action =='setdate_livraison' || $action =='setavailability') && getDolGlobalInt('INFRASPLUS_PDF_UPDATE_ON_FIELDS_CHANGE', 0) ? 1 : 0;	// If we want to generate the document PDF when fields are changed
				$hidedetails	= (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS', '') ? 1 : 0));
				$hidedesc		= (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', '') ? 1 : 0));
				$hideref		= (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF', '') ? 1 : 0));
				$confirm		= GETPOST('confirm', 'alpha');
				$idwarehouse	= GETPOSTINT('idwarehouse');
				if (is_object($object) && !empty($object->id)) {
					$locationTarget	= $_SERVER['PHP_SELF'].'?id='.$object->id;
				}
				if ($object instanceof Propal) {
					$usercanvalidate	= ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('propal', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('propal', 'propal_advance', 'validate')));
					if ($usercanvalidate && (($action == 'confirm_validate' && $confirm == 'yes') || ($object->status == Propal::STATUS_VALIDATED && ($onNotesChange || $onExfChange || $onFieldsChange)))) {
						$result	= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ($object instanceof Commande) {
					$usercanvalidate	= ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('commande', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('commande', 'order_advance', 'validate')));
					if ($usercanvalidate && (($action == 'confirm_validate' && $confirm == 'yes') || ($object->status == Commande::STATUS_VALIDATED && ($onNotesChange || $onExfChange || $onFieldsChange)))) {
						$result			= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ($object instanceof Facture) {
					$usercanvalidate	= ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('facture', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('facture', 'invoice_advance', 'validate')));
					if ($usercanvalidate && (($action == 'confirm_valid' && $confirm == 'yes') || ($object->status == Facture::STATUS_VALIDATED && ($onNotesChange || $onExfChange || $onFieldsChange)))) {
						$result			= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ($object instanceof Contrat) {
					$usercanvalidate	= $user->hasRight('contrat', 'creer');
					if ($usercanvalidate && (($action == 'confirm_valid' && $confirm == 'yes') || ($object->statut == Contrat::STATUS_VALIDATED && ($onNotesChange || $onExfChange)))) {
						$result	= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ($object instanceof Fichinter) {
					$usercanvalidate	= $user->hasRight('fichinter', 'creer');
					if ($usercanvalidate && (($action == 'confirm_validate' && $confirm == 'yes') || ($object->statut == Fichinter::STATUS_VALIDATED && ($onNotesChange || $onExfChange)))) {
						$result	= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result > 0) {
							header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
						} else {
							dol_htmloutput_errors($object->error);
						}
						return 1;
					}
				}
				if ($object instanceof Expedition) {
					$usercanvalidate	= ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('expedition', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('expedition', 'shipping_advance', 'validate')));
					if ($usercanvalidate && (($action == 'confirm_valid' && $confirm == 'yes') || ($object->status == Expedition::STATUS_VALIDATED && ($onNotesChange || $onExfChange)))) {
						$result	= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ($object instanceof Reception) {
					$usercanvalidate	= ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('reception', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('reception', 'reception_advance', 'validate')));
					if ($usercanvalidate && (($action == 'confirm_valid' && $confirm == 'yes') || ($object->statut == Reception::STATUS_VALIDATED && ($onNotesChange || $onExfChange)))) {
						$result	= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ( $object instanceof Delivery ) {
					$usercanvalidate	= ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('expedition', 'delivery', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('expedition', 'delivery_advance', 'validate')));
					if ($usercanvalidate && (($action == 'confirm_valid' && $confirm == 'yes') || ($object->statut == 1 && ($onNotesChange || $onExfChange)))) {
						$result	= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ($object instanceof SupplierProposal) {
					$usercanvalidate	= ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('supplier_proposal', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS', '') && $user->hasRight('supplier_proposal', 'validate_advance')));
					$id					= GETPOSTINT('id');
					$ref				= GETPOST('ref', 'alpha');
					if ($id > 0 || !empty($ref)) {
						$object->fetch($id, $ref);
					}
					if ($usercanvalidate && (($action == 'confirm_validate' && $confirm == 'yes') || ($object->status == SupplierProposal::STATUS_VALIDATED && ($onNotesChange || $onExfChange)))) {
						$result	= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
				if ($object instanceof CommandeFournisseur) {
					// Common permissions
					$usercancreate		= ($user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer'));
					// Advanced permissions
					$usercanvalidate	= !getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !empty($usercancreate) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('fournisseur', 'supplier_order_advance', 'validate'));
					if ($usercanvalidate && (($action == 'confirm_valid' && $confirm == 'yes') || ($object->status == CommandeFournisseur::STATUS_VALIDATED && ($onNotesChange || $onExfChange)))) {
						$result			= infraspackplus_semiauto_update($object, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
						if ($result < 0) {
							$langs->load('errors');
							if (count($object->errors) > 0) {
								setEventMessages($object->error, $object->errors, 'errors');
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
							}
						}
						return 1;
					}
				}
			}
			return 0;
		}

		/**
		* When we show a line
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			$action			Current action (if set). Generally create or edit or null
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function printObjectLine($parameters, &$object, &$action)
		{
			global $db, $conf, $langs, $user, $object, $hookmanager;
			global $form;
			global $object_rights, $disableedit, $disablemove, $disableremove; // TODO We should not use global var for this !

			$object->fetch_thirdparty();	// If the action has not been carried out before
			$line			= !empty($parameters['line']) ? $parameters['line'] : '';
			$var			= !empty($parameters['var']) ? $parameters['var'] : '';
			$num			= !empty($parameters['num']) ? $parameters['num'] : '';
			$i				= !empty($parameters['i']) ? $parameters['i'] : '';
			$dateSelector	= !empty($parameters['dateSelector']) ? $parameters['dateSelector'] : '';
			$seller			= !empty($parameters['seller']) ? $parameters['seller'] : '';
			$buyer			= !empty($parameters['buyer']) ? $parameters['buyer'] : '';
			$selected		= !empty($parameters['selected']) ? $parameters['selected'] : '';
			$extrafields	= !empty($parameters['extrafieldsline']) ? $parameters['extrafieldsline'] : '';
			$defaulttpldir	= '/core/tpl';
			$object_rights	= $object->getRights();
			$element		= $object->element;
			$text			= '';
			$description	= '';
			$TContext		= explode(':', $parameters['context']);
			$isOuvrageLine	= infraspackplus_isLineFromExternalModule($line, $element, 'modOuvrage');
			$isOuvrage		= isModEnabled('ouvrage') && !empty($isOuvrageLine) ? true : false;
			$isSubTotalLine	= infraspackplus_isLineFromExternalModule($line, $element, 'modSubtotal');
			$isATMLine		= isModEnabled('subtotal') && !empty($isSubTotalLine) ? true : false;
			$isShipment		= in_array('ordershipmentcard', $TContext) || in_array('expeditioncard', $TContext) ? 1 : 0;
			if (in_array($object->element, array('propal', 'commande', 'facture', 'fichinter')) && getDolGlobalString('INFRASPLUS_PDF_SHOW_DISCOUNT_OPT', '') && empty($isShipment) && empty($isATMLine) && empty($isOuvrage)) {
				if ($action != 'editline' || $selected != $line->id) {	// Line in view mode
					if ($line->fk_product > 0) {	// Product
						$product_static			= new Product($db);
						$product_static->fetch($line->fk_product);
						$product_static->ref	= $line->ref; //can change ref in hook
						$product_static->label	= !empty($line->label) ? $line->label : ''; //can change label in hook
						$text					= $product_static->getNomUrl(1);
						if (getDolGlobalString('MAIN_MULTILANGS', '')) {	// Define output language and label
							if (property_exists($object, 'socid') && !is_object($object->thirdparty)) {
								dol_print_error('', 'Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
								return 0;
							}
							$prod			= new Product($db);
							$prod->fetch($line->fk_product);
							$outputlangs	= $langs;
							$newlang		= '';
							if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
								$newlang	= GETPOST('lang_id', 'aZ09');
							}
							if (getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE', '') && empty($newlang) && is_object($object->thirdparty)) {
								$newlang	= $object->thirdparty->default_lang; // To use language of customer
							}
							if (!empty($newlang)) {
								$outputlangs	= new Translate('', $conf);
								$outputlangs->setDefaultLang($newlang);
							}
							$label	= (!empty($prod->multilangs[$outputlangs->defaultlang]['label'])) ? $prod->multilangs[$outputlangs->defaultlang]['label'] : $line->product_label;
						} else {
							$label	= $line->product_label;
						}
						$text			.= ' - '.(!empty($line->label) ? $line->label : $label);
						$description	.= (getDolGlobalString('PRODUIT_DESC_IN_FORM', '') ? '' : (!empty($line->description) ? dol_htmlentitiesbr($line->description) : '')); // Description is what to show on popup. We shown nothing if already into desc.
					}
					$line->pu_ttc	= price2num((!empty($line->subprice) ? $line->subprice : 0) * (1 + ((!empty($line->tva_tx) ? $line->tva_tx : 0) / 100)), 'MU');
					// Output template part (modules that overwrite templates must declare this into descriptor)
					// Use global variables + $dateSelector + $seller and $buyer
					$dolibranch		= explode('.', DOL_VERSION);
					$coreVersion	= 'dlb'.$dolibranch[0].'0x'.(getDolGlobalString('EASYA_VERSION', '') ? '-Easya' : '');
					$tpl			= dol_buildpath('infraspackplus/substitutionpages/'.$coreVersion.'/core/tpl/objectline_view.tpl.php', 0);
					$res			= empty($conf->file->strict_mode) ? @include $tpl : include $tpl;	// for debug
					if (!empty($res)) {
						return 1;
					}
				}
				if ($object->statut == 0 && $action == 'editline' && $selected == $line->id) {	// Line in update mode
					$label			= (!empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));
					$line->pu_ttc	= price2num((!empty($line->subprice) ? $line->subprice : 0) * (1 + ((!empty($line->tva_tx) ? $line->tva_tx : 0) / 100)), 'MU');
					// Output template part (modules that overwrite templates must declare this into descriptor)
					// Use global variables + $dateSelector + $seller and $buyer
					$dirtpls		= array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
					foreach ($dirtpls as $module => $reldir) {
						$tpl	= !empty($module) ? dol_buildpath($reldir.'/objectline_edit.tpl.php') : DOL_DOCUMENT_ROOT.$reldir.'/objectline_edit.tpl.php';
						$res	= empty($conf->file->strict_mode) ? @include $tpl : include $tpl;	// for debug
						if (!empty($res)) {
							return 1;
						}
					}
				}
				return 0;
			}
			return 0;
		}
	}
