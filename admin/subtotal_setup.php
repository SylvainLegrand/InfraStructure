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
	* \file		subtotal/admin/subtotal_setup.php
	* \ingroup		subtotal
	* \brief		subtotal setup page.
	*************************************************/

	// Dolibarr environment *************************
	require '../config.php'; // InfraS change

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/subtotal/core/lib/subtotalAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'propal', 'orders', 'bills', 'supplier', 'supplier_proposal', 'subtotal@subtotal'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('subtotal', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('subtotal', 'SubTotalParamSpecif')) ? 1 : 0);
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
		$result	= subtotal_bkup_module ('subtotal');
	}
	if ($action == 'restoreParams') {
		$result	= subtotal_restore_module ('subtotal');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOSTINT('value'), 'chaine', 0, 'SubTotal module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Gen'	=> array('SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET', 'SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET', 'SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET', 'SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS',
											'SUBTOTAL_BLOC_FOLD_MODE', 'SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS', 'SUBTOTAL_TFIELD_TO_KEEP_WITH_NC',  'SUBTOTAL_TEXT_LINE_STYLE', 'SUBTOTAL_TITLE_SIZE', 'SUBTOTAL_SUBTOTAL_STYLE', 
											'SUBTOTAL_TITLE_BACKGROUND_COLOR', 'SUBTOTAL_SUBTOTAL_BACKGROUND_COLOR', 'SUBTOTAL_TITLE_AND_SUBTOTAL_BRIGHTNESS_PERCENTAGE', 'SUBTOTAL_TITLE_STYLE'
												)
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			if (in_array($constname, array('SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET', 'SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET', 'SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET', 'SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS'))) {
				$constvalue = implode(',', GETPOST($constname, 'array'));
			} else {
				$constvalue	= GETPOST($constname, 'alpha');
			}
			$result	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'SubTotal module', $conf->entity);
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
	$propalSelected		= explode(',', getDolGlobalString('SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET'));
	$orderSelected		= explode(',', getDolGlobalString('SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET'));
	$invoiceSelected	= explode(',', getDolGlobalString('SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET'));
	$selected			= explode(',', getDolGlobalString('SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS'));
	if (getDolGlobalInt('SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS') > 0) {
		_createExtraComprisNonCompris();
	}

	// View *****************************************
	$page_name			= $langs->trans('SubTotal').' - '.$langs->trans('SubTotalSetup');
	llxHeader('', $page_name);	// browser tab
	echo $confirm_mesg;
	$linkback			= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption		= '';

	// Configuration header *************************
	$head				= subtotal_admin_prepare_head();
	$picto				= 'subtotal@subtotal';
	print dol_get_fiche_head($head, 'subtotalsetup', $langs->trans('SubTotal'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "subtotal_tblPSexp";
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
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) {
								$(".toggle_bloc").hide();
							} else {
								$(".toggle_bloc").hide();
								$(this).siblings().show();
							}
							$.cookie(cookieName, "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie(cookieName, $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 )	{
								$(".subtotalScrollUp").css("right", "30px");
							} else {
								$(".subtotalScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	subtotal_print_backup_restore();
	print '		<div class = "foldable">';
	print subtotal_load_title('<span class = "subtotaltitleparam">'.$langs->trans('SubTotalSetupPage').'</span>', $titleoption, dol_buildpath('/subtotal/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblGen" class = "noborder centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	subtotal_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	subtotal_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		subtotal_print_btn_action('Gen', $langs->trans('SubTotalParamCautionSave'), 4);
		$num	= subtotal_print_input('SUBTOTAL_USE_NEW_FORMAT', 'on_off', $langs->trans('SubTotalUseNewFormat'), 'SubTotalUseNewFormatHelp', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_CONCAT_TITLE_LABEL_IN_SUBTOTAL_LABEL', 'on_off', $langs->trans('SubTotalConcatTitleLabelInSubtotalLabel'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_USE_NUMEROTATION', 'on_off', $langs->trans('SubTotalUseNumerotation'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_ALLOW_ADD_BLOCK', 'on_off', $langs->trans('SubTotalAllowAddBlock'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_ALLOW_EDIT_BLOCK', 'on_off', $langs->trans('SubTotalAllowEditBlock'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_ALLOW_REMOVE_BLOCK', 'on_off', $langs->trans('SubTotalAllowRemoveBlock'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_ALLOW_DUPLICATE_BLOCK', 'on_off', $langs->trans('SubTotalAllowDuplicateBlock'), '', array(), 2, 1, '', $num);
		// num = 8
		$num	= subtotal_print_input('SUBTOTAL_ALLOW_DUPLICATE_LINE', 'on_off', $langs->trans('SubTotalAllowDuplicateLine'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE', 'on_off', $langs->trans('SubTotalAllowAddLineUnderTitle'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_ADD_LINE_UNDER_TITLE_AT_END_BLOCK', 'on_off', $langs->trans('SubTotalAddLineUnderTitleAtEndBlock'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_HIDE_FOLDERS_BY_DEFAULT', 'on_off', $langs->trans('SubTotalHideFoldersByDefault'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_HIDE_OPTIONS_TITLE', 'on_off', $langs->trans('SubTotalHideOptionsTitle'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_HIDE_OPTIONS_BREAK_PAGE_BEFORE', 'on_off', $langs->trans('SubTotalHideOptionsBreakPageBefore'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_HIDE_OPTIONS_BUILD_DOC', 'on_off', $langs->trans('SubTotalHideOptionsBuildDoc'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_TEXT_FOR_TITLE_ORDERS_TO_INVOICE', '', $langs->trans('SubTotalTextForTitleOrdetstoinvoice'), $langs->transnoentities('SubTotalTextForTitleOrdetstoinvoiceInfo'), array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_TITLE_STYLE', 'input', $langs->trans('SubTotalTitleStyle'), '', array(), 2, 1, '', $num);
		// num = 17
		$num	= subtotal_print_input('SUBTOTAL_TEXT_LINE_STYLE', 'input', $langs->trans('SubTotalTextLineStyle'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_TITLE_SIZE', 'input', $langs->trans('SubTotalTitleSize'), $langs->transnoentities('SubTotalTitleSizeInfo'), array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_SUBTOTAL_STYLE', 'input', $langs->trans('SubTotalSubtotalStyle'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_DISPLAY_MARGIN_ON_SUBTOTALS', 'on_off', $langs->trans('SubTotalDisplayMarginOnSubtotals'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_TITLE_BACKGROUND_COLOR', 'color', $langs->trans('SubTotalTitleBackgroundcolor'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_SUBTOTAL_BACKGROUND_COLOR', 'color', $langs->trans('SubTotalSubtotalBackgroundcolor'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_TITLE_AND_SUBTOTAL_BRIGHTNESS_PERCENTAGE', 'input', $langs->trans('SubTotalTitleAndSubtotalBrightnessPercentage'), 'SubTotalTitleAndSubtotalBrightnessPercentageInfo', array(), 2, 1, '%', $num);
		// num = 24
		$num	= subtotal_print_input('SUBTOTAL_DISABLE_SUMMARY', 'on_off', $langs->trans('SubTotalDisableSummary'), '', array(), 2, 1, '', $num);
		$metas	= $form->selectarray('SUBTOTAL_BLOC_FOLD_MODE', array('default' => $langs->trans('SubTotalHideSubtitleOnFold'), 'keepTitle' => $langs->trans('SubTotalKeepSubtitleDisplayOnFold')), getDolGlobalString('SUBTOTAL_BLOC_FOLD_MODE'), 0, 0, 0, '', 1, 0, 0, '', 'subtotalwidth270 centpercent');
		$num	= subtotal_print_input('', 'select', $langs->trans('SubTotalBlocFoldMode'), '', $metas, 2, 1, '', $num);
		if (!getDolGlobalInt('MAIN_MODULE_INFRASPACKPLUS')) {
			subtotal_print_subTitle(4, 'SubTotalManageNonCompris');
			$metas	= $form->selectarray('SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS', array(0 => $langs->transnoentities('No'), 1 => $langs->transnoentities('Yes')), getDolGlobalInt('SUBTOTAL_MANAGE_COMPRIS_NONCOMPRIS', 1), 0, 0, 0, '', 1, 0, 0, '', 'subtotalwidth270 centpercent');
			$num	= subtotal_print_input('', 'select', $langs->trans('SubTotalManageComprisNoncompris'), '', $metas, 2, 1, '', $num);
			$metas	= $form->selectarray('SUBTOTAL_TFIELD_TO_KEEP_WITH_NC',array('pdf_getlineqty'			=> $langs->trans('Qty'),
																								'pdf_getlinevatrate'		=> $langs->trans('VAT'),
																								'pdf_getlineupexcltax'		=> $langs->trans('PriceUHT'),
																								'pdf_getlinetotalexcltax'	=> $langs->trans('TotalHT'),
																								'pdf_getlinetotalincltax'	=> $langs->trans('TotalTTC'),
																								'pdf_getlineunit'			=> $langs->trans('Unit'),
																								'pdf_getlineremisepercent'	=> $langs->trans('Discount')
																								),
										getDolGlobalInt('SUBTOTAL_TFIELD_TO_KEEP_WITH_NC', 1), 0, 0, 0, '', 1, 0, 0, '', 'subtotalwidth270 centpercent');
			$num	= subtotal_print_input('', 'select', $langs->trans('SUBTOTAL_TFIELD_TO_KEEP_WITH_NC'), '', $metas, 2, 1, '', $num);
			$num	= subtotal_print_input('SUBTOTAL_NONCOMPRIS_UPDATE_PA_HT', 'on_off', $langs->trans('SubTotalNoncomprisUpdatePaHt'), 'SubTotalNoncomprisUpdatePaHtInfo', array(), 2, 1, '', $num);
			$num	= subtotal_print_input('SUBTOTAL_AUTO_ADD_SUBTOTAL_ON_ADDING_NEW_TITLE', 'on_off', $langs->trans('SubTotalAutoAddSubtotalOnAddingNewTitle'), '', array(), 2, 1, '', $num);
		} else {
			$num += 4;
		}
		// num = 30
		subtotal_print_subTitle(4, 'SubTotalSetupForExtrafields');
		$num	= subtotal_print_input('SUBTOTAL_ALLOW_EXTRAFIELDS_ON_TITLE', 'on_off', $langs->trans('SubTotalAllowExtrafieldsOnTitle'), '', array(), 2, 1, '', $num);
		$metas	= $form->multiselectarray('SUBTOTAL_LIST_OF_EXTRAFIELDS_PROPALDET', $extrafields->fetch_name_optionals_label('propaldet'), $propalSelected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= subtotal_print_input('', 'select', $langs->trans('SubTotalListOfExtrafieldsPropaldet'), '', $metas, 2, 1, '', $num);
		$metas	= $form->multiselectarray('SUBTOTAL_LIST_OF_EXTRAFIELDS_COMMANDEDET', $extrafields->fetch_name_optionals_label('commandedet'), $orderSelected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= subtotal_print_input('', 'select', $langs->trans('SubTotalListOfExtrafieldsCommandedet'), '', $metas, 2, 1, '', $num);
		$metas	= $form->multiselectarray('SUBTOTAL_LIST_OF_EXTRAFIELDS_FACTUREDET', $extrafields->fetch_name_optionals_label('facturedet'), $invoiceSelected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= subtotal_print_input('', 'select', $langs->trans('SubTotalListOfExtrafieldsFacturedet'), '', $metas, 2, 1, '', $num);
		// num = 34
		subtotal_print_subTitle(4, 'SubTotalSetup');
		$TField	= array('propal'			=> $langs->trans('Proposal'),
						'commande'			=> $langs->trans('Order'),
						'facture'			=> $langs->trans('Invoice'),
						'supplier_proposal'	=> $langs->trans('SupplierProposal'),
						'order_supplier'	=> $langs->trans('SupplierOrder'),
						'invoice_supplier'	=> $langs->trans('SupplierInvoice'),
					);
		$metas	= $form->multiselectarray('SUBTOTAL_DEFAULT_DISPLAY_QTY_FOR_SUBTOTAL_ON_ELEMENTS', $TField, $selected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= subtotal_print_input('', 'select', $langs->trans('SubTotalDefaultDisplayQtyForSubtotalOnElements'), 'SubTotalDefaultDisplayQtyForSubtotalOnElementsInfo', $metas, 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_NO_TITLE_SHOW_ON_EXPED_GENERATION', 'on_off', $langs->trans('SubTotalNoTitleShowOnExpedGeneration'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_SHOW_TVA_ON_SUBTOTAL_LINES_ON_ELEMENTS', 'on_off', $langs->trans('SubTotalShowTvaOnSubtotalLinesOnElements'), '', array(), 2, 1, '', $num);
		if (getDolGlobalInt('SUBTOTAL_SHOW_TVA_ON_SUBTOTAL_LINES_ON_ELEMENTS') && isModEnabled('infraspackplus')) {
			$num	= subtotal_print_input('SUBTOTAL_LIMIT_TVA_ON_CONDENSED_BLOCS', 'on_off', $langs->trans('SubTotalLimitTvaOnCondensedBlocs'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		// num = 38
		subtotal_print_subTitle(4, 'SubTotalRecapGeneration');
		$num	= subtotal_print_input('SUBTOTAL_KEEP_RECAP_FILE', 'on_off', $langs->trans('SubTotalKeepRecapFile'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_PROPAL_ADD_RECAP', 'on_off', $langs->trans('SubTotalPropalAddRecap'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_COMMANDE_ADD_RECAP', 'on_off', $langs->trans('SubTotalCommandeAddRecap'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_INVOICE_ADD_RECAP', 'on_off', $langs->trans('SubTotalInvoiceAddRecap'), '', array(), 2, 1, '', $num);
		subtotal_print_subTitle(4, 'SubTotalSetupForSubBlocs');
		$num	= subtotal_print_input('SUBTOTAL_HIDE_PRICE_DEFAULT_CHECKED', 'on_off', $langs->trans('SubTotalHidePriceDefaultChecked'), '', array(), 2, 1, '', $num);
		$num	= subtotal_print_input('SUBTOTAL_IF_HIDE_PRICES_SHOW_QTY', 'on_off', $langs->trans('SubTotalIfHidePricesShowQty'), '', array(), 2, 1, '', $num);
		if (!getDolGlobalInt('MAIN_MODULE_INFRASPACKPLUS')) {
			$num	= subtotal_print_input('SUBTOTAL_HIDE_DOCUMENT_TOTAL', 'on_off', $langs->trans('SubTotalHideDocumentTotal'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('shippableorder')) {
			$num	= subtotal_print_input('SUBTOTAL_SHIPPABLE_ORDER', 'on_off', $langs->trans('SubTotalShippableOrder'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('clilacevenements')) {
			$num	= subtotal_print_input('SUBTOTAL_SHOW_QTY_ON_TITLES', 'on_off', $langs->trans('SubTotalShowQtyOnTitles'), '', array(), 2, 1, '', $num);
			$num	= subtotal_print_input('SUBTOTAL_ONLY_HIDE_SUBPRODUCTS_PRICES', 'on_off', $langs->trans('SubTotalOnlyHideSubproductsPrices'), '', array(), 2, 1, '', $num);
		} else {
			$num += 2;
		}
		if (!getDolGlobalInt('MAIN_MODULE_INFRASPACKPLUS')) {
			subtotal_print_subTitle(4, 'SubTotalExperimentalZone');
			$num	= subtotal_print_input('SUBTOTAL_ONE_LINE_IF_HIDE_INNERLINES', 'on_off', $langs->trans('SubTotalOneLineIfHideInnerlines', $langs->trans('SubTotalHideInnerLines')), '', array(), 2, 1, '', $num);
			$num	= subtotal_print_input('SUBTOTAL_REPLACE_WITH_VAT_IF_HIDE_INNERLINES', 'on_off', $langs->trans('SubTotalReplaceWithVatIfHideInnerlines', $langs->trans('SubTotalHideInnerLines')), '', array(), 2, 1, '', $num);
		} else {
			$num += 2;
		}
		// num = 50
		print '		</table>';
		print '	</div>';
	}
	print '	</form>
			<a class = "subtotalScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
