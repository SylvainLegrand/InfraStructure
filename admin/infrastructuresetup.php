<?php
	/*************************************************
	* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
	* Copyright (C) 2022 SuperAdmin <maxime@gmail.com>
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
	* along with this program.  If not, see <https://www.gnu.org/licenses/>.
	*************************************************/

	/**************************************************
	* \file		infrastructure/admin/infrastructuresetup.php
	* \ingroup		infrastructure
	* \brief		infrastructure setup page.
	*************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrastructure/core/lib/infrastructureAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'propal', 'orders', 'bills', 'supplier', 'supplier_proposal', 'infrastructure@infrastructure'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrastructure', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrastructure', 'InfrastructureParamSpecif')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther(db: $db);
	$extrafields	= new ExtraFields($db);
	$action			= GETPOST('action','alpha');
	$confirm		= GETPOST('confirm', 'alpha');
	$backtopage		= GETPOST('backtopage', 'alpha');
	$modulepart		= GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php
	$value			= GETPOST('value', 'alpha');
	$label			= GETPOST('label', 'alpha');
	$confirm_mesg	= '';
	$result			= '';
	//Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infrastructure_bkup_module ('infrastructure');
	}
	if ($action == 'restoreParams') {
		$result	= infrastructure_restore_module ('infrastructure');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOSTINT('value'), 'chaine', 0, 'Infrastructure module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Gen'	=> array('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET',	'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET',
											'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET',	'INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS',
											'INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC',			'INFRASTRUCTURE_TEXT_FOR_TITLE_ORDERS_TO_INVOICE'
											),
							'aff'	=> array('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS',
											'INFRASTRUCTURE_BLOC_FOLD_MODE',					'INFRASTRUCTURE_TEXT_LINE_STYLE',
											'INFRASTRUCTURE_TITLE_STYLE',						'INFRASTRUCTURE_TOTAL_STYLE',
											'INFRASTRUCTURE_TITLE_AND_TOTAL_BRIGHTNESS_PERCENTAGE',
											'INFRASTRUCTURE_TITLE_BACKGROUND_COLOR',			'INFRASTRUCTURE_TOTAL_BACKGROUND_COLOR',
											'INFRASTRUCTURE_TITLE_COLOR',						'INFRASTRUCTURE_TOTAL_COLOR',
											'INFRASTRUCTURE_TITLE_COLOR_BLOC'
											),
							'pdf'	=> array('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS_PDF',
											'INFRASTRUCTURE_PDF_TITLE_SIZE',					'INFRASTRUCTURE_PDF_TITLE_STYLE_IF_HIDDEN_LINES',
											'INFRASTRUCTURE_PDF_TITLE_STYLE',					'INFRASTRUCTURE_PDF_TOTAL_STYLE',
											'INFRASTRUCTURE_PDF_TITLE_BACKGROUND_COLOR',		'INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_COLOR',
											'INFRASTRUCTURE_PDF_TITLE_COLOR',					'INFRASTRUCTURE_PDF_TOTAL_COLOR',
											'INFRASTRUCTURE_PDF_TITLE_AND_TOTAL_BRIGHTNESS_PERCENTAGE',
											'INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET','INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_POS_Y_OFFSET',
											'INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_HEIGHT_OFFSET','INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_POS_Y_OFFSET'
											)
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			if (in_array($constname, array('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET', 'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET', 'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET', 'INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS', 'INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS_PDF'))) {
				$constvalue = implode(',', GETPOST($constname, 'array'));
			} else {
				$constvalue	= GETPOST($constname, 'alpha');
			}
			$result	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'Infrastructure module', $conf->entity);
		}
	}
	//Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************
	$isV20p				= version_compare(DOL_VERSION, '20.0.0') >= 0;
	$propalSelected		= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET'));
	$orderSelected		= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET'));
	$invoiceSelected	= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET'));
	$selected			= explode(',', getDolGlobalString('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS'));
	$selectedPdf		= explode(',', getDolGlobalString('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS_PDF'));
	$titleWithTotal		= getDolGlobalInt('INFRASTRUCTURE_PDF_TITLE_WITH_TOTAL');
	if (getDolGlobalInt('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') > 0) {
		infrastructure_createExtraComprisNonCompris();
	}

	// View *****************************************
	$page_name			= $langs->trans('Infrastructure').' - '.$langs->trans('InfrastructureSetup');
	llxHeader('', $page_name);	// browser tab
	echo $confirm_mesg;
	$linkback			= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption		= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head				= infrastructure_admin_prepare_head();
	$picto				= 'infrastructure@infrastructure';
	print dol_get_fiche_head($head, 'infrastructuresetup', $langs->trans('Infrastructure'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "infrastructure_tblPSexp";
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar) {
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie(cookieName))) {
							tblPSexp = $.cookie(cookieName);
						}
						$(".toggle_bloc").hide();
						if (tblPSexp) {
							$("[name=" + tblPSexp + "]").toggle();
						}
					});
					$(function () {
						// Reset le style width inline mis par select2 pour retomber sur la classe CSS quatrevingtpercent.
						// Select2 fixe une largeur en px lors de son init si le parent est masqué (display:none) -> mauvaise valeur.
						function infrastructureResetSelect2Width($container) {
							$container.find("select.select2-hidden-accessible").next(".select2-container").css("width", "");
						}
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) {
								$(".toggle_bloc").hide();
							} else {
								$(".toggle_bloc").hide();
								var $target = $(this).siblings();
								$target.show();
								infrastructureResetSelect2Width($target);
							}
							$.cookie(cookieName, "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie(cookieName, $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						// Application immédiate sur un bloc déjà visible au chargement (cookie restore).
						infrastructureResetSelect2Width($(".toggle_bloc:visible"));
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 )	{
								$(".infrastructureScrollUp").css("right", "30px");
							} else {
								$(".infrastructureScrollUp").removeAttr("style");
							}
						});
					});
				</script>
				<style type="text/css">
					/* Le span wrapper Dolibarr (multiselectarray*) est inline par defaut, ce qui empeche width: 80% (.quatrevingtpercent) de se resoudre sur le wrapper select2. Force inline-block avec largeur pleine cellule. */
					.toggle_bloc span[class^="multiselectarray"], .toggle_bloc span[class*=" multiselectarray"] {
						display: inline-block;
						width: 100%;
					}
					.toggle_bloc .select2-container.quatrevingtpercent {
						min-width: 250px;
					}
					/* Supprime le margin/padding-left herite (browser default ou theme) sur le ul/li interne du select2 multiple. */
					.toggle_bloc .select2-selection__rendered, .toggle_bloc .select2-selection__rendered > li {
						margin-left: 0 !important;
						padding-left: 0 !important;
					}
				</style>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infrastructure_print_backup_restore();
	// Paramètres du module infrastructure
	print '		<div class = "foldable">';
	print infrastructure_load_title('<span class = "infrastructuretitleparam">'.$langs->trans('InfrastructureSetupPage').'</span>', $titleoption, dol_buildpath('/infrastructure/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblGen" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infrastructure_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrastructure_print_liste_titre($metas);
	if (!empty($accessright)) {
		infrastructure_print_btn_action('Gen', $langs->trans('InfrastructureParamCautionSave'), 4);
		$num	= 1;
		$num	= infrastructure_print_input('INFRASTRUCTURE_DISPLAY_MARGIN_ON_TOTAL', 'on_off', $langs->trans('InfrastructureDisplayMarginOnTotal'), '', array(), 2, 1, '', $num);
		if (!getDolGlobalInt('MAIN_MODULE_INFRASPACKPLUS')) {
			infrastructure_print_subTitle(4, 'InfrastructureManageNonCompris');
			$metas	= $form->selectarray('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS', array(0 => $langs->transnoentities('No'), 1 => $langs->transnoentities('Yes')), getDolGlobalInt('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS', 1), 0, 0, 0, '', 1, 0, 0, '', 'infrastructurewidth270 centpercent');
			$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureManageComprisNoncompris'), '', $metas, 2, 1, '', $num);
			$metas	= $form->selectarray('INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC',array('pdf_getlineqty'			=> $langs->trans('Qty'),
																										'pdf_getlinevatrate'		=> $langs->trans('VAT'),
																										'pdf_getlineupexcltax'		=> $langs->trans('PriceUHT'),
																										'pdf_getlinetotalexcltax'	=> $langs->trans('TotalHT'),
																										'pdf_getlinetotalincltax'	=> $langs->trans('TotalTTC'),
																										'pdf_getlineunit'			=> $langs->trans('Unit'),
																										'pdf_getlineremisepercent'	=> $langs->trans('Discount')
																										),
										getDolGlobalInt('INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC', 1), 0, 0, 0, '', 1, 0, 0, '', 'infrastructurewidth270 centpercent');
			$num	= infrastructure_print_input('', 'select', $langs->trans('INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC'), '', $metas, 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_NONCOMPRIS_UPDATE_PA_HT', 'on_off', $langs->trans('InfrastructureNoncomprisUpdatePaHt'), 'InfrastructureNoncomprisUpdatePaHtInfo', array(), 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_AUTO_ADD_TOTAL_ON_ADDING_NEW_TITLE', 'on_off', $langs->trans('InfrastructureAutoAddInfrastructureOnAddingNewTitle'), '', array(), 2, 1, '', $num);
		} else {
			$num += 4;
		}
		$metas	= array('class' => 'flat infrastructurewidth270 infrastructurefontsizeinherit');
		$num	= infrastructure_print_input('INFRASTRUCTURE_TEXT_FOR_TITLE_ORDERS_TO_INVOICE', 'input', $langs->trans('InfrastructureTextForTitleOrdetstoinvoice'), 'InfrastructureTextForTitleOrdetstoinvoiceInfo', $metas, 1, 2, '', $num);
		// num = 7
		infrastructure_print_subTitle(4, 'InfrastructureSetupForExtrafields');
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE', 'on_off', $langs->trans('InfrastructureAllowExtrafieldsOnTitle'), '', array(), 2, 1, '', $num);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET', $extrafields->fetch_name_optionals_label('propaldet'), $propalSelected, 0, 0, 'flat infrastructurewidth270 infrastructurefontsizeinherit', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureListOfExtrafieldsPropaldet'), '', $metas, 1, 2, '', $num);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET', $extrafields->fetch_name_optionals_label('commandedet'), $orderSelected, 0, 0, 'flat infrastructurewidth270 infrastructurefontsizeinherit', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureListOfExtrafieldsCommandedet'), '', $metas, 1, 2, '', $num);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET', $extrafields->fetch_name_optionals_label('facturedet'), $invoiceSelected, 0, 0, 'flat infrastructurewidth270 infrastructurefontsizeinherit', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureListOfExtrafieldsFacturedet'), '', $metas, 1, 2, '', $num);
		// num = 11
		infrastructure_print_subTitle(4, 'InfrastructureSetupForShipping');
		$num	= infrastructure_print_input('INFRASTRUCTURE_NO_TITLE_SHOW_ON_EXPED_GENERATION', 'on_off', $langs->trans('InfrastructureNoTitleShowOnExpedGeneration'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_DEFAULT_CHECK_SHIPPING_LIST_FOR_TITLE_DESC', 'on_off', $langs->trans('InfrastructureDefaultCheckShippingListForTitleDesc'), 'InfrastructureDefaultCheckShippingListForTitleDescInfo', array(), 2, 1, '', $num);
		infrastructure_print_subTitle(4, 'InfrastructureSetupForSubBlocs');
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_PRICE_DEFAULT_CHECKED', 'on_off', $langs->trans('InfrastructureHidePriceDefaultChecked'), '', array(), 2, 1, '', $num);
		if (!getDolGlobalInt('MAIN_MODULE_INFRASPACKPLUS')) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_DOCUMENT_TOTAL', 'on_off', $langs->trans('InfrastructureHideDocumentTotal'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('shippableorder')) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_SHIPPABLE_ORDER', 'on_off', $langs->trans('InfrastructureShippableOrder'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('clilacevenements')) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_SHOW_QTY_ON_TITLES', 'on_off', $langs->trans('InfrastructureShowQtyOnTitles'), '', array(), 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_ONLY_HIDE_SUBPRODUCTS_PRICES', 'on_off', $langs->trans('InfrastructureOnlyHideSubproductsPrices'), '', array(), 2, 1, '', $num);
		} else {
			$num += 2;
		}
		// num = 18
		if (!getDolGlobalInt('MAIN_MODULE_INFRASPACKPLUS')) {
			infrastructure_print_subTitle(4, 'InfrastructureExperimentalZone');
			$num	= infrastructure_print_input('INFRASTRUCTURE_ONE_LINE_IF_HIDE_INNERLINES', 'on_off', $langs->trans('InfrastructureOneLineIfHideInnerlines', $langs->trans('InfrastructureHideInnerLines')), '', array(), 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_REPLACE_WITH_VAT_IF_HIDE_INNERLINES', 'on_off', $langs->trans('InfrastructureReplaceWithVatIfHideInnerlines', $langs->trans('InfrastructureHideInnerLines')), '', array(), 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_DISABLE_FIX_TRANSACTION', 'on_off', $langs->trans('InfrastructureDisableFixTransaction'), 'InfrastructureDisableFixTransactionInfo', array(), 2, 1, '', $num);
		} else {
			$num += 3;
		}
	}
	print '		</table>';
	print '	</div>';
	// Paramètres d'affichage du module infrastructure
	print '		<div class = "foldable">';
	print infrastructure_load_title('<span class = "infrastructuretitleparam">'.$langs->trans('InfrastructureScreenDisplay').'</span>', $titleoption, dol_buildpath('/infrastructure/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblaff" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infrastructure_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrastructure_print_liste_titre($metas);
	if (!empty($accessright)) {
		infrastructure_print_btn_action('aff', $langs->trans('InfrastructureParamCautionSave'), 4);
		$num	= 1;
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_ADD_BLOCK', 'on_off', $langs->trans('InfrastructureAllowAddBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_EDIT_BLOCK', 'on_off', $langs->trans('InfrastructureAllowEditBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK', 'on_off', $langs->trans('InfrastructureAllowRemoveBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_DUPLICATE_BLOCK', 'on_off', $langs->trans('InfrastructureAllowDuplicateBlock'), '', array(), 2, 1, '', $num);
		// num = 5
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_DUPLICATE_LINE', 'on_off', $langs->trans('InfrastructureAllowDuplicateLine'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_ADD_LINE_UNDER_TITLE', 'on_off', $langs->trans('InfrastructureAllowAddLineUnderTitle'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ADD_LINE_UNDER_TITLE_AT_END_BLOCK', 'on_off', $langs->trans('InfrastructureAddLineUnderTitleAtEndBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_FOLDERS_BY_DEFAULT', 'on_off', $langs->trans('InfrastructureHideFoldersByDefault'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_OPTIONS_TITLE', 'on_off', $langs->trans('InfrastructureHideOptionsTitle'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_OPTIONS_BREAK_PAGE_BEFORE', 'on_off', $langs->trans('InfrastructureHideOptionsBreakPageBefore'), '', array(), 2, 1, '', $num);
		// num = 11
		if ($isV20p) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_FORCE_EXPLODE_ACTION_BTN', 'on_off', $langs->trans('InfrastructureForceExplodeActionBtn'), 'InfrastructureForceExplodeActionBtnInfo', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		$metas	= array('class' => 'flat infrastructurewidth270 infrastructurefontsizeinherit');
		$num	= infrastructure_print_input('INFRASTRUCTURE_TEXT_LINE_STYLE', 'input', $langs->trans('InfrastructureTextLineStyle'), '', $metas, 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_STYLE', 'input', $langs->trans('InfrastructureTitleStyle'), '', $metas, 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TOTAL_STYLE', 'input', $langs->trans('InfrastructureTotalStyle'), '', $metas, 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_AND_TOTAL_BRIGHTNESS_PERCENTAGE', 'input', $langs->trans('InfrastructureTitleAndInfrastructureBrightnessPercentage'), 'InfrastructureTitleAndInfrastructureBrightnessPercentageInfo', $metas, 2, 1, '%', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_DISABLE_SUMMARY', 'on_off', $langs->trans('InfrastructureDisableSummary'), '', $metas, 2, 1, '', $num);
		// num = 17
		$metas	= $form->selectarray('INFRASTRUCTURE_BLOC_FOLD_MODE', array('default' => $langs->trans('InfrastructureHideSubtitleOnFold'), 'keepTitle' => $langs->trans('InfrastructureKeepSubtitleDisplayOnFold'), 'hideAll' => $langs->trans('InfrastructureHideAllOnFold')), getDolGlobalString('INFRASTRUCTURE_BLOC_FOLD_MODE'), 0, 0, 0, '', 1, 0, 0, '', 'infrastructurewidth270 infrastructurefontsizeinherit');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureBlocFoldMode'), '', $metas, 2, 1, '', $num);
		$TFieldScreen	= array('propal'			=> $langs->trans('Proposal'),
								'commande'			=> $langs->trans('Order'),
								'facture'			=> $langs->trans('Invoice'),
								'supplier_proposal'	=> $langs->trans('SupplierProposal'),
								'order_supplier'	=> $langs->trans('SupplierOrder'),
								'invoice_supplier'	=> $langs->trans('SupplierInvoice'),
							);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS', $TFieldScreen, $selected, 0, 0, 'infrastructurewidth270 infrastructurefontsizeinherit', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureDefaultDisplayQtyForInfrastructureOnElements'), '', $metas, 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', 'color', $langs->trans('InfrastructureTitleBackgroundcolor'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_COLOR', 'color', $langs->trans('InfrastructureTitleColor'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_COLOR_BLOC', 'color', $langs->trans('InfrastructureTitleColorBloc'), '', array(), 2, 1, '', $num);
		// num = 22
		$num	= infrastructure_print_input('INFRASTRUCTURE_TOTAL_BACKGROUND_COLOR', 'color', $langs->trans('InfrastructureTotalBackgroundcolor'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TOTAL_COLOR', 'color', $langs->trans('InfrastructureTotalColor'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_OPTIONS_BUILD_DOC', 'on_off', $langs->trans('InfrastructureHideOptionsBuildDoc'), '', array(), 2, 1, '', $num);
		// num = 25
	}
	print '			</table>
				</div>';
	// Paramètres d'impression PDF du module infrastructure
	print '		<div class = "foldable">';
	print infrastructure_load_title('<span class = "infrastructuretitleparam">'.$langs->trans('InfrastructurePdfPrinting').'</span>', $titleoption, dol_buildpath('/infrastructure/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblpdf" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infrastructure_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrastructure_print_liste_titre($metas);
	if (!empty($accessright)) {
		infrastructure_print_btn_action('pdf', $langs->trans('InfrastructureParamCautionSave'), 4);
		$num	= 1;
		$num	= infrastructure_print_input('INFRASTRUCTURE_USE_NUMEROTATION', 'on_off', $langs->trans('InfrastructureUseNumerotation'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_WITH_TOTAL', 'on_off', $langs->trans('InfrastructurePdfTitleWithTotal'), 'InfrastructurePdfTitleWithTotalInfo', array(), 2, 1, '', $num);
		// num = 3
		$metas	= array('class' => 'flat infrastructurewidth270 infrastructurefontsizeinherit');
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_SIZE', 'input', $langs->trans('InfrastructurePdfTitleSize'), $langs->transnoentities('InfrastructurePdfTitleSizeInfo'), $metas, 1, 2, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_STYLE_IF_HIDDEN_LINES', 'input', $langs->trans('InfrastructurePdfTitleStyleIfHiddenLines'), 'InfrastructurePdfTitleStyleIfHiddenLinesInfo', $metas, 1, 2, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_STYLE', 'input', $langs->trans('InfrastructurePdfTitleStyle'), 'InfrastructurePdfTitleStyleInfo', $metas, 1, 2, '', $num);
		// num = 6
		if (empty($titleWithTotal)) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TOTAL_STYLE', 'input', $langs->trans('InfrastructurePdfTotalStyle'), 'InfrastructurePdfTotalStyleInfo', $metas, 1, 2, '', $num);
		} else {
			$num++;
		}
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_BACKGROUND_COLOR', 'color', $langs->trans('InfrastructurePdfTitleBackgroundcolor'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_COLOR', 'color', $langs->trans('InfrastructurePdfTitleColor'), 'InfrastructurePdfTitleColorInfo', array(), 2, 1, '', $num);
		// num = 9
		if (empty($titleWithTotal)) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_COLOR', 'color', $langs->trans('InfrastructurePdfTotalBackgroundcolor'), '', array(), 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TOTAL_COLOR', 'color', $langs->trans('InfrastructurePdfTotalColor'), 'InfrastructurePdfTotalColorInfo', array(), 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_TOTAL_LABEL', 'on_off', $langs->trans('InfrastructureConcatTitleLabelInTotalLabel'), '', array(), 2, 1, '', $num);
		} else {
			$num	+= 3;
		}
		// num = 12
		$metas	= array('class' => 'right flat infrastructurewidth250 infrastructurefontsizeinherit');
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_AND_TOTAL_BRIGHTNESS_PERCENTAGE', 'input', $langs->trans('InfrastructurePdfTitleAndTotalBrightnessPercentage'), 'InfrastructurePdfTitleAndTotalBrightnessPercentageInfo', $metas, 1, 2, '&nbsp;&nbsp;%', $num);
		$metas	= array('type' => 'number', 'step' => '0.01', 'class' => 'flat infrastructurewidth270 infrastructurefontsizeinherit');
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_HEIGHT_OFFSET', 'input', $langs->trans('InfrastructurePdfTitleBackgroundCellHeightOffset'), 'InfrastructureBackgroundCellOffsetInfo', $metas, 1, 2, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TITLE_BACKGROUND_CELL_POS_Y_OFFSET', 'input', $langs->trans('InfrastructurePdfTitleBackgroundCellPosYOffset'), 'InfrastructureBackgroundCellOffsetInfo1', $metas, 1, 2, '', $num);
		if (empty($titleWithTotal)) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_HEIGHT_OFFSET', 'input', $langs->trans('InfrastructurePdfTotalBackgroundCellHeightOffset'), 'InfrastructureBackgroundCellOffsetInfo', $metas, 1, 2, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_CELL_POS_Y_OFFSET', 'input', $langs->trans('InfrastructurePdfTotalBackgroundCellPosYOffset'), 'InfrastructureBackgroundCellOffsetInfo1', $metas, 1, 2, '', $num);
		} else {
			$num	+= 2;
		}
		// num = 17
		$TField	= array('propal'			=> $langs->trans('Proposal'),
						'commande'			=> $langs->trans('Order'),
						'facture'			=> $langs->trans('Invoice'),
						'supplier_proposal'	=> $langs->trans('SupplierProposal'),
						'order_supplier'	=> $langs->trans('SupplierOrder'),
						'invoice_supplier'	=> $langs->trans('SupplierInvoice'),
					);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_TOTAL_ON_ELEMENTS_PDF', $TField, $selectedPdf, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureDefaultDisplayQtyForInfrastructureOnElementsPdf'), 'InfrastructureDefaultDisplayQtyForInfrastructureOnElementsPdfInfo', $metas, 1, 2, '', $num);
		if (empty($titleWithTotal)) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_SHOW_TVA_ON_TOTAL_LINES', 'on_off', $langs->trans('InfrastructureShowTvaOnTotalLines'), '', array(), 2, 1, '', $num);
			if (getDolGlobalInt('INFRASTRUCTURE_SHOW_TVA_ON_TOTAL_LINES')) {
				$num	= infrastructure_print_input('INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS', 'on_off', $langs->trans('InfrastructureLimitTvaOnCondensedBlocs'), '', array(), 2, 1, '', $num);
			} else {
				$num++;
			}
		} else {
			$num	+= 2;
		}
		// num = 20
		infrastructure_print_subTitle(4, 'InfrastructureRecapGeneration');
		$num	= infrastructure_print_input('INFRASTRUCTURE_KEEP_RECAP_FILE', 'on_off', $langs->trans('InfrastructureKeepRecapFile'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_PROPAL_ADD_RECAP', 'on_off', $langs->trans('InfrastructurePropalAddRecap'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_COMMANDE_ADD_RECAP', 'on_off', $langs->trans('InfrastructureCommandeAddRecap'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_INVOICE_ADD_RECAP', 'on_off', $langs->trans('InfrastructureInvoiceAddRecap'), '', array(), 2, 1, '', $num);
		// $num = 24
	}
	print '			</table>
				</div>';
	print '	</form>
			<a class = "infrastructureScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
