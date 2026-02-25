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
	* 	\file		./infraspackplus/admin/generalpdf.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'companies', 'errors', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramDolibarr')) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$formadmin	= new FormAdmin($db);
	$form		= new Form($db);
	$formfile	= new FormFile($db);
	$formother	= new FormOther($db);
	$action		= GETPOST('action','alpha');
	$result		= '';
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		if ($confkey == 'ADD_HTML_FORMATING_INTO_DESC_DOC') {
			$result	= dolibarr_set_const($db, 'PDF_BOLD_PRODUCT_REF_AND_PERIOD', GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'MAIN_GENERATE_DOCUMENTS_HIDE_REF' && !getDolGlobalString($confkey, '')) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_REF_COLUMN',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE' && empty(GETPOST('value'))) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_SHOW_WVCC',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'INVOICE_ADD_ZATCA_QR_CODE' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'INVOICE_ADD_SWISS_QR_CODE',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'INVOICE_ADD_SWISS_QR_CODE' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'INVOICE_ADD_ZATCA_QR_CODE',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array ('Gen'	=> array('MAIN_PDF_MARGIN_LEFT',				'MAIN_PDF_MARGIN_TOP',				'MAIN_PDF_MARGIN_RIGHT',	'MAIN_PDF_MARGIN_BOTTOM',
											'MAIN_PDF_FORMAT',						'MAIN_PDF_FORCE_FONT_SIZE',			'PRODUCT_USE_UNITS',		'PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES',
											'PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE',	'INVOICE_CATEGORY_OF_OPERATION',	'PDF_VAT_LABEL_IS_CODE_OR_RATE'));
		$confkey	= $reg[1];
		$error		= 0;
		$chgUnits	= false;
		foreach ($list[$confkey] as $constname) {
			// Specific case for units management
			if ($constname == 'PRODUCT_USE_UNITS' && (GETPOST('PRODUCT_USE_UNITS', 'alpha') == 'none' || GETPOST('PRODUCT_USE_UNITS', 'alpha') == '-1')) {
				$value	= '';
			} else {
				$value	= GETPOST($constname, 'alpha');
			}
			// check if we change units management
			if ($constname == 'PRODUCT_USE_UNITS' && $value != getDolGlobalString('PRODUCT_USE_UNITS')) {
				$chgUnits = true;
			}
			$result		= dolibarr_set_const($db, $constname, $value, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	// Retour => message Ok ou KO
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), array(), 'mesgs');
	}
	// Warning if units management changed
	if ($chgUnits) {
		setEventMessages($langs->trans('PDFParamWarnChgUnits'), array(), 'warnings');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), array(), 'errors');
	}

	// init variables *******************************
	if (getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 0) < 4) {
		dolibarr_set_const($db, 'MAIN_PDF_MARGIN_TOP', 4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 0) < 4) {
		dolibarr_set_const($db, 'MAIN_PDF_MARGIN_LEFT', 4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 0) < 4) {
		dolibarr_set_const($db, 'MAIN_PDF_MARGIN_RIGHT', 4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 0) < 4) {
		dolibarr_set_const($db, 'MAIN_PDF_MARGIN_BOTTOM', 4, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$selected			= getDolGlobalString('MAIN_PDF_FORMAT', dol_getDefaultFormat());
	$default_font_size	= 10;
	$noCountryCode		= (empty($mysoc->country_code) ? true : false);
	if (empty($noCountryCode)) {
		$pid1	= $langs->transcountry('ProfId1',$mysoc->country_code);
		if ($pid1 == '-') {
			$pid1	= false;
		}
		$pid2	= $langs->transcountry('ProfId2',$mysoc->country_code);
		if ($pid2 == '-'){
			$pid2	= false;
		}
		$pid3	= $langs->transcountry('ProfId3',$mysoc->country_code);
		if ($pid3 == '-'){
			$pid3	= false;
		}
		$pid4	= $langs->transcountry('ProfId4',$mysoc->country_code);
		if ($pid4 == '-'){
			$pid4	= false;
		}
		$pid5	= $langs->transcountry('ProfId5',$mysoc->country_code);
		if ($pid5 == '-') {
			$pid5	= false;
		}
	} else {
		$pid1	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</span>';
		$pid2	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</span>';
		$pid3	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</span>';
		$pid4	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</span>';
		$pid5	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv('CompanyCountry')).'</span>';
	}
	$typeRefSell		= getDolGlobalString('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES', '0');
	$listTypeRefSell	= array('0' => 'PDFParamRefInSupplierLine0', '1' => 'PDFParamRefInSupplierLine1', '2' => 'PDFParamRefInSupplierLine2');
	$typeRefBuy			= getDolGlobalString('PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE', '0');
	$listTypeRefbuy		= array('0' => 'PDFParamRefInBuyerLine0', '1' => 'PDFParamRefInBuyerLine1', '2' => 'PDFParamRefInBuyerLine2');
	$catOpe				= getDolGlobalString('INVOICE_CATEGORY_OF_OPERATION', '0');
	$listCatOpe			= array('0' => 'No', '1' => 'PDFParamCatOpe1', '2' => 'PDFParamCatOpe2');
	$listVatRateOnly	= array('rateonly' => 'PDFParamVatRateOnly', 'codeonly' => 'PDFParamVatCodeOnly', 'labelonly' => 'PDFParamVatLabelOnly',
								'ratecode' => 'PDFParamVatRateCode', 'ratelabel' => 'PDFParamVatRateLabel', 'codelabel' => 'PDFParamVatCodeLabel');
	$vatRateOnly		= getDolGlobalString('PDF_VAT_LABEL_IS_CODE_OR_RATE', '0');

	// View *****************************************
	$page_name			= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsGeneralPDF');
	llxHeader('', $page_name);
	$linkback			= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head				= infraspackplus_admin_prepare_head();
	$picto				= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'generalpdf', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script type = "text/javascript">
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrasplusScrollUp").css("right", "30px");
							} else {
								$(".infrasplusScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '		<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype="multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	print load_fiche_titre(''.$langs->trans('PDFParamGeneralDol').'</FONT>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "infrasplusnoborder centpercent">';
	$metas	= array('30px', '*', '356px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	infraspackplus_print_btn_action('Gen', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 4);
	if (!empty($accessright)) {
		$num				= 1;
		print '			<tr class = "oddeven">
							<td class = "center bold">'.$num.'</td>
							<td colspan = "3">
								<table class = "centpercent">
									<tr>
										<td class = "width500 infrasplusnomargin infrasplusnopadding infrasplusnoborder">'.$langs->trans('PDFParamMargin').'</td>
										<td class = "infrasplusnomargin infrasplusnopadding infrasplusnoborder">
											<table>
												<tr>
													<td class = "center infrasplusnomargin infrasplusnopadding infrasplusnoborder">
														'.$langs->trans('PDFParamMarginTop').'<br/><input type = "number" size = "10" class = "center infrasplusnomargin infrasplusnopadding infrasplusnoborder" dir="rtl" id = "MAIN_PDF_MARGIN_TOP" name = "MAIN_PDF_MARGIN_TOP" min = "4" max = "20" value = "'.getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 4).'">
													</td>
												</tr>
												<tr>
													<td class = "center infrasplusnomargin infrasplusnopadding infrasplusnoborder">
														'.$langs->trans('PDFParamMarginLeft').'&nbsp;<input type = "number" size = "10" class = "left infrasplusnomargin infrasplusnopadding infrasplusnoborder" id = "MAIN_PDF_MARGIN_LEFT" name = "MAIN_PDF_MARGIN_LEFT" min = "4" max = "20" value = "'.getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 4).'">
														&nbsp;&nbsp;&nbsp;<input type = "number" size = "10" class = "right infrasplusnomargin infrasplusnopadding infrasplusnoborder" dir="rtl" id = "MAIN_PDF_MARGIN_RIGHT" name = "MAIN_PDF_MARGIN_RIGHT" min = "4" max = "20" value = "'.getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 4).'">&nbsp;'.$langs->trans('PDFParamMarginRight').'
													</td>
												</tr>
												<tr>
													<td class = "center infrasplusnomargin infrasplusnopadding infrasplusnoborder">
														<input type = "number" size = "10" class = "center infrasplusnomargin infrasplusnopadding infrasplusnoborder" dir="rtl" id = "MAIN_PDF_MARGIN_BOTTOM" name = "MAIN_PDF_MARGIN_BOTTOM" min = "4" max = "20" value = "'.getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 4).'"><br/>'.$langs->trans('PDFParamMarginBottom').'
													</td>
												</tr>
											</table>
										</td>
										<td class = "right infrasplusnomargin infrasplusnopadding infrasplusnoborder">'.$langs->trans('DictionaryPaperFormat').' : '.$formadmin->select_paper_format($selected, 'MAIN_PDF_FORMAT').'</td>
									</tr>
								</table>
							</td>
						</tr>';
		$num++;
		$metas				= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '2', 'max' => '30');
		$num				= infraspackplus_print_input('MAIN_PDF_FORCE_FONT_SIZE', 'input', $langs->trans('PDFParamForceFontSize', $default_font_size), '', $metas, 2, 1, '', $num);
		// $num = 3
		infraspackplus_print_hr(4);
		$num				= infraspackplus_print_input('PDF_DISABLE_MYCOMPANY_LOGO', 'on_off', $langs->trans('PDFParamNoMyLogo'), '', array(), 2, 1, '', $num);
		$num				= infraspackplus_print_input('MAIN_PDF_USE_LARGE_LOGO', 'on_off', $langs->trans('PDFParamLargeLogo1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('PDFParamLargeLogo2'), '', array(), 2, 1, '', $num);
		$num				= infraspackplus_print_input('MAIN_INVERT_SENDER_RECIPIENT', 'on_off', $langs->trans('PDFParamInvertSenderRecipient'), '', array(), 2, 1, '', $num);
		$num				= infraspackplus_print_input('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME', 'on_off', $langs->trans('PDFParamAliasIn3rdName').' '.$langs->trans('InfraSPlusGenModif'), '', array(), 2, 1, '', $num);
		$num				= infraspackplus_print_input('MAIN_PDF_USE_ISO_LOCATION', 'on_off', $langs->trans('PlaceCustomerAddressToIsoLocation'), '', array(), 2, 1, '', $num);
		$num				= infraspackplus_print_input('MAIN_PDF_DISABLESOURCEDETAILS', 'on_off', $langs->trans('PDFParamDisableSourceDetails'), '', array(), 2, 1, '', $num);
		$num				= infraspackplus_print_input('MAIN_PDF_ADDALSOTARGETDETAILS', 'on_off', $langs->trans('PDFParamAddAlsoTargetDetails'), '', array(), 2, 1, '', $num);
		$num				= infraspackplus_print_input('MAIN_TVAINTRA_NOT_IN_ADDRESS', 'on_off', $langs->trans('PDFParamHideVATIntraInAddress'), '', array(), 2, 1, '', $num);
		// $num = 11
		if (!empty($pid1)) {
			$num	= infraspackplus_print_input('MAIN_PROFID1_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid1, '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (!empty($pid2)) {
			$num	= infraspackplus_print_input('MAIN_PROFID2_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid2, '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (!empty($pid3)) {
			$num	= infraspackplus_print_input('MAIN_PROFID3_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid3, '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (!empty($pid4)) {
			$num	= infraspackplus_print_input('MAIN_PROFID4_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid4, '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (!empty($pid5)) {
			$num	= infraspackplus_print_input('MAIN_PROFID5_IN_ADDRESS', 'on_off', $langs->trans('PDFParamShowProfIdInAddress').' - '.$pid5, '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 16
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('MAIN_PDF_DASH_BETWEEN_LINES', 'on_off', $langs->trans('PDFParamShowDashOnPDF'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('FCKEDITOR_ENABLE_DETAILS_FULL', 'on_off', $langs->trans('PDFParamFullDetWYSIWYG'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('ADD_HTML_FORMATING_INTO_DESC_DOC', 'on_off', $langs->trans('PDFParamHTMLformatDesc'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_GENERATE_DOCUMENTS_HIDE_REF', 'on_off', $langs->trans('HideRefOnPDF'), '', array(), 2, 1, '', $num);
		if (!getDolGlobalString('INFRASPLUS_PDF_HIDE_LABEL', '')) {
			$num	= infraspackplus_print_input('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', 'on_off', $langs->trans('HideDescOnPDF').' '.$langs->trans('InfraSPlusGenModif'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 21
		$num	= infraspackplus_print_input('MAIN_DOCUMENTS_DESCRIPTION_FIRST', 'on_off', $langs->trans('PDFParamDescFirst'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE', 'on_off', $langs->trans('PDFParamDisableCustomProductCodeOnPDF'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME', 'on_off', $langs->trans('PDFParamHideWaightAndVolumeOnPDF').' '.$langs->trans('InfraSPlusGenModif'), '', array(), 2, 1, '', $num);
		// Récupérer les unités de mesure depuis le dictionnaire c_units
		$metas 	= $form->selectUnits(getDolGlobalString('PRODUCT_USE_UNITS', ''), 'PRODUCT_USE_UNITS', 1, '' );
		$num	= infraspackplus_print_input('PRODUCT_USE_UNITS', 'select', $langs->trans('PDFParamProdUseUnit'), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('PRODUIT_PDF_MERGE_PROPAL', 'on_off', $langs->trans('PDFParamMergeProductPDF1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('PDFParamMergeProductPDF2'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('CATEGORY_ADD_DESC_INTO_DOC', 'on_off', $langs->trans('PDFParamAddDescIntoDoc'), '', array(), 2, 1, '', $num);
		$metas	= $form->selectarray('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES', $listTypeRefSell, $typeRefSell, 1, 0, 0, '', 1, 0, 0, '', 'centpercent');
		$num	= infraspackplus_print_input('', 'select', $langs->trans('PDFParamRefInSupplierLine', $langs->trans('PDFParamRefInSupplierLine0'), $langs->trans('PDFParamRefInSupplierLine1'), $langs->trans('PDFParamRefInSupplierLine2')), '', $metas, 1, 2, '', $num);
		if (getDolGlobalInt('PRODUIT_CUSTOMER_PRICES', 0)) {
			$metas	= $form->selectarray('PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE', $listTypeRefbuy, $typeRefBuy, 1, 0, 0, '', 1, 0, 0, '', 'centpercent');
			$num	= infraspackplus_print_input('', 'select', $langs->trans('PDFParamRefInBuyerLine', $langs->trans('PDFParamRefInBuyerLine0'), $langs->trans('PDFParamRefInBuyerLine1'), $langs->trans('PDFParamRefInBuyerLine2')), '', $metas, 1, 2, '', $num);
		} else {
			$num++;
		}
		// $num = 29
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INVOICE_ADD_ZATCA_QR_CODE', 'on_off', $langs->trans('PDFParamUseZatcaQrFac'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INVOICE_ADD_SWISS_QR_CODE', 'on_off', $langs->trans('PDFParamUseSwissQrFac'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INVOICE_USE_SITUATION', 'on_off', $langs->trans('PDFParamUseSitFac'), '', array(), 2, 1, '', $num);
		$metas	= $form->selectarray('INVOICE_CATEGORY_OF_OPERATION', $listCatOpe, $catOpe, 0, 0, 0, '', 1, 0, 0, '', 'centpercent');
		$num	= infraspackplus_print_input('', 'select', $langs->trans('PDFParamCatOpe'), 'PDFParamCatOpeHelp', $metas, 1, 2, '', $num);
		$num	= infraspackplus_print_input('MAIN_PDF_HIDE_CHQ_ADDRESS', 'on_off', $langs->trans('PDFParamHideChqAddr'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN', 'on_off', $langs->trans('PDFParamOnlyBICIBAN'), '', array(), 2, 1, '', $num);
		// $num = 35
		if (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox')) {
			$num	= infraspackplus_print_input('PDF_SHOW_LINK_TO_ONLINE_PAYMENT', 'on_off', $langs->trans('PDFParamShowLinkOnlinePay'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INVOICE_POSITIVE_CREDIT_NOTE', 'on_off', $langs->trans('PDFParamInvoiceType2Positive'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS', 'on_off', $langs->trans('PDFParamFactureDepositAsPayments'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('PROPALE_PDF_HIDE_PAYMENTTERMCOND', 'on_off', $langs->trans('PDFParamHidePayCond'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('PROPALE_PDF_HIDE_PAYMENTTERMMOD', 'on_off', $langs->trans('PDFParamHidePayMode'), '', array(), 2, 1, '', $num);
		$num	= infraspackplus_print_input('INVOICE_NO_PAYMENT_DETAILS', 'on_off', $langs->trans('PDFParamNoPayDetInv'), '', array(), 2, 1, '', $num);
		$metas	= $form->selectarray('PDF_VAT_LABEL_IS_CODE_OR_RATE', $listVatRateOnly, $vatRateOnly, 1, 0, 0, '', 1, 0, 0, '', 'centpercent');
		$num	= infraspackplus_print_input('', 'select', $langs->trans('PDFParamTotalVatDisplay'), '', $metas, 1, 2, '', $num);
		// $num = 42
	}
	print '			</table>';
	if (!empty($user->admin)) {
		print '		<table class = "centpercent">
						<tr>
							<td class = "center"><a href="'.DOL_URL_ROOT.'/admin/pdf.php">'.$langs->trans('PDFParamBackToPDFConf').'</a></td>
						</tr>
					</table>
					<br/>';
	}
	print '			<a class = "infrasplusScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>
				</form>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
