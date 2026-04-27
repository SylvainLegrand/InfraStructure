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
		$list		= array('Gen'	=> array('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET', 'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET', 'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET', 'INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS',
											'INFRASTRUCTURE_BLOC_FOLD_MODE', 'INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS', 'INFRASTRUCTURE_TFIELD_TO_KEEP_WITH_NC',  'INFRASTRUCTURE_TEXT_LINE_STYLE', 'INFRASTRUCTURE_TITLE_SIZE', 'INFRASTRUCTURE_INFRASTRUCTURE_STYLE',
											'INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', 'INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR', 'INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE', 'INFRASTRUCTURE_TITLE_STYLE'
												)
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			if (in_array($constname, array('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET', 'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET', 'INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET', 'INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS'))) {
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
	$propalSelected		= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET'));
	$orderSelected		= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET'));
	$invoiceSelected	= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET'));
	$selected			= explode(',', getDolGlobalString('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS'));
	if (getDolGlobalInt('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS') > 0) {
		infrastructure_createExtraComprisNonCompris();
	}

	// View *****************************************
	$page_name			= $langs->trans('Infrastructure').' - '.$langs->trans('InfrastructureSetup');
	llxHeader('', $page_name);	// browser tab
	echo $confirm_mesg;
	$linkback			= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption		= '';

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
								$(".infrastructureScrollUp").css("right", "30px");
							} else {
								$(".infrastructureScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infrastructure_print_backup_restore();
	print '		<div class = "foldable">';
	print infrastructure_load_title('<span class = "infrastructuretitleparam">'.$langs->trans('InfrastructureSetupPage').'</span>', $titleoption, dol_buildpath('/infrastructure/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblGen" class = "noborder centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infrastructure_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrastructure_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infrastructure_print_btn_action('Gen', $langs->trans('InfrastructureParamCautionSave'), 4);
		$num	= infrastructure_print_input('INFRASTRUCTURE_USE_NEW_FORMAT', 'on_off', $langs->trans('InfrastructureUseNewFormat'), 'InfrastructureUseNewFormatHelp', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL', 'on_off', $langs->trans('InfrastructureConcatTitleLabelInInfrastructureLabel'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_USE_NUMEROTATION', 'on_off', $langs->trans('InfrastructureUseNumerotation'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_ADD_BLOCK', 'on_off', $langs->trans('InfrastructureAllowAddBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_EDIT_BLOCK', 'on_off', $langs->trans('InfrastructureAllowEditBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK', 'on_off', $langs->trans('InfrastructureAllowRemoveBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_DUPLICATE_BLOCK', 'on_off', $langs->trans('InfrastructureAllowDuplicateBlock'), '', array(), 2, 1, '', $num);
		// num = 8
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_DUPLICATE_LINE', 'on_off', $langs->trans('InfrastructureAllowDuplicateLine'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_ADD_LINE_UNDER_TITLE', 'on_off', $langs->trans('InfrastructureAllowAddLineUnderTitle'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_ADD_LINE_UNDER_TITLE_AT_END_BLOCK', 'on_off', $langs->trans('InfrastructureAddLineUnderTitleAtEndBlock'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_FOLDERS_BY_DEFAULT', 'on_off', $langs->trans('InfrastructureHideFoldersByDefault'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_OPTIONS_TITLE', 'on_off', $langs->trans('InfrastructureHideOptionsTitle'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_OPTIONS_BREAK_PAGE_BEFORE', 'on_off', $langs->trans('InfrastructureHideOptionsBreakPageBefore'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_OPTIONS_BUILD_DOC', 'on_off', $langs->trans('InfrastructureHideOptionsBuildDoc'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TEXT_FOR_TITLE_ORDERS_TO_INVOICE', '', $langs->trans('InfrastructureTextForTitleOrdetstoinvoice'), $langs->transnoentities('InfrastructureTextForTitleOrdetstoinvoiceInfo'), array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_STYLE', 'input', $langs->trans('InfrastructureTitleStyle'), '', array(), 2, 1, '', $num);
		// num = 17
		$num	= infrastructure_print_input('INFRASTRUCTURE_TEXT_LINE_STYLE', 'input', $langs->trans('InfrastructureTextLineStyle'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_SIZE', 'input', $langs->trans('InfrastructureTitleSize'), $langs->transnoentities('InfrastructureTitleSizeInfo'), array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_INFRASTRUCTURE_STYLE', 'input', $langs->trans('InfrastructureInfrastructureStyle'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_DISPLAY_MARGIN_ON_INFRASTRUCTURES', 'on_off', $langs->trans('InfrastructureDisplayMarginOnInfrastructures'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', 'color', $langs->trans('InfrastructureTitleBackgroundcolor'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_INFRASTRUCTURE_BACKGROUND_COLOR', 'color', $langs->trans('InfrastructureInfrastructureBackgroundcolor'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_TITLE_AND_INFRASTRUCTURE_BRIGHTNESS_PERCENTAGE', 'input', $langs->trans('InfrastructureTitleAndInfrastructureBrightnessPercentage'), 'InfrastructureTitleAndInfrastructureBrightnessPercentageInfo', array(), 2, 1, '%', $num);
		// num = 24
		$num	= infrastructure_print_input('INFRASTRUCTURE_DISABLE_SUMMARY', 'on_off', $langs->trans('InfrastructureDisableSummary'), '', array(), 2, 1, '', $num);
		$metas	= $form->selectarray('INFRASTRUCTURE_BLOC_FOLD_MODE', array('default' => $langs->trans('InfrastructureHideSubtitleOnFold'), 'keepTitle' => $langs->trans('InfrastructureKeepSubtitleDisplayOnFold')), getDolGlobalString('INFRASTRUCTURE_BLOC_FOLD_MODE'), 0, 0, 0, '', 1, 0, 0, '', 'infrastructurewidth270 centpercent');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureBlocFoldMode'), '', $metas, 2, 1, '', $num);
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
			$num	= infrastructure_print_input('INFRASTRUCTURE_AUTO_ADD_INFRASTRUCTURE_ON_ADDING_NEW_TITLE', 'on_off', $langs->trans('InfrastructureAutoAddInfrastructureOnAddingNewTitle'), '', array(), 2, 1, '', $num);
		} else {
			$num += 4;
		}
		// num = 30
		infrastructure_print_subTitle(4, 'InfrastructureSetupForExtrafields');
		$num	= infrastructure_print_input('INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE', 'on_off', $langs->trans('InfrastructureAllowExtrafieldsOnTitle'), '', array(), 2, 1, '', $num);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET', $extrafields->fetch_name_optionals_label('propaldet'), $propalSelected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureListOfExtrafieldsPropaldet'), '', $metas, 2, 1, '', $num);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET', $extrafields->fetch_name_optionals_label('commandedet'), $orderSelected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureListOfExtrafieldsCommandedet'), '', $metas, 2, 1, '', $num);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET', $extrafields->fetch_name_optionals_label('facturedet'), $invoiceSelected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureListOfExtrafieldsFacturedet'), '', $metas, 2, 1, '', $num);
		// num = 34
		infrastructure_print_subTitle(4, 'InfrastructureSetup');
		$TField	= array('propal'			=> $langs->trans('Proposal'),
						'commande'			=> $langs->trans('Order'),
						'facture'			=> $langs->trans('Invoice'),
						'supplier_proposal'	=> $langs->trans('SupplierProposal'),
						'order_supplier'	=> $langs->trans('SupplierOrder'),
						'invoice_supplier'	=> $langs->trans('SupplierInvoice'),
					);
		$metas	= $form->multiselectarray('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS', $TField, $selected, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrastructure_print_input('', 'select', $langs->trans('InfrastructureDefaultDisplayQtyForInfrastructureOnElements'), 'InfrastructureDefaultDisplayQtyForInfrastructureOnElementsInfo', $metas, 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_NO_TITLE_SHOW_ON_EXPED_GENERATION', 'on_off', $langs->trans('InfrastructureNoTitleShowOnExpedGeneration'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_SHOW_TVA_ON_INFRASTRUCTURE_LINES_ON_ELEMENTS', 'on_off', $langs->trans('InfrastructureShowTvaOnInfrastructureLinesOnElements'), '', array(), 2, 1, '', $num);
		if (getDolGlobalInt('INFRASTRUCTURE_SHOW_TVA_ON_INFRASTRUCTURE_LINES_ON_ELEMENTS') && isModEnabled('infraspackplus')) {
			$num	= infrastructure_print_input('INFRASTRUCTURE_LIMIT_TVA_ON_CONDENSED_BLOCS', 'on_off', $langs->trans('InfrastructureLimitTvaOnCondensedBlocs'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		// num = 38
		infrastructure_print_subTitle(4, 'InfrastructureRecapGeneration');
		$num	= infrastructure_print_input('INFRASTRUCTURE_KEEP_RECAP_FILE', 'on_off', $langs->trans('InfrastructureKeepRecapFile'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_PROPAL_ADD_RECAP', 'on_off', $langs->trans('InfrastructurePropalAddRecap'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_COMMANDE_ADD_RECAP', 'on_off', $langs->trans('InfrastructureCommandeAddRecap'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_INVOICE_ADD_RECAP', 'on_off', $langs->trans('InfrastructureInvoiceAddRecap'), '', array(), 2, 1, '', $num);
		infrastructure_print_subTitle(4, 'InfrastructureSetupForSubBlocs');
		$num	= infrastructure_print_input('INFRASTRUCTURE_HIDE_PRICE_DEFAULT_CHECKED', 'on_off', $langs->trans('InfrastructureHidePriceDefaultChecked'), '', array(), 2, 1, '', $num);
		$num	= infrastructure_print_input('INFRASTRUCTURE_IF_HIDE_PRICES_SHOW_QTY', 'on_off', $langs->trans('InfrastructureIfHidePricesShowQty'), '', array(), 2, 1, '', $num);
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
		if (!getDolGlobalInt('MAIN_MODULE_INFRASPACKPLUS')) {
			infrastructure_print_subTitle(4, 'InfrastructureExperimentalZone');
			$num	= infrastructure_print_input('INFRASTRUCTURE_ONE_LINE_IF_HIDE_INNERLINES', 'on_off', $langs->trans('InfrastructureOneLineIfHideInnerlines', $langs->trans('InfrastructureHideInnerLines')), '', array(), 2, 1, '', $num);
			$num	= infrastructure_print_input('INFRASTRUCTURE_REPLACE_WITH_VAT_IF_HIDE_INNERLINES', 'on_off', $langs->trans('InfrastructureReplaceWithVatIfHideInnerlines', $langs->trans('InfrastructureHideInnerLines')), '', array(), 2, 1, '', $num);
		} else {
			$num += 2;
		}
		// num = 50
		print '		</table>';
		print '	</div>';
	}
	print '	</form>
			<a class = "infrastructureScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
