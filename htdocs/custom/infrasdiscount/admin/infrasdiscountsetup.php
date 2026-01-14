<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infrasdiscount/admin/infrasdiscountsetup.php
	*	\ingroup	InfraS
	*	\brief		Page to setup the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infrasdiscount/core/lib/infrasdiscountAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrasdiscount@infrasdiscount'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrasdiscount', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrasdiscount', 'paramInfrasdiscount')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$formcompany	= new FormCompany($db);
	$confirm_mesg	= '';
	$action			= GETPOST('action','alpha');
	$confirm		= GETPOST('confirm', 'alpha');
	$result			= '';
	// Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infrasdiscount_bkup_module ('infrasdiscount');
	}
	if ($action == 'restoreParams') {
		$result	= infrasdiscount_restore_module ('infrasdiscount');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSDiscount module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Gen'	=> array('INFRASDISCOUNT_LABEL_PROPAL',
											'INFRASDISCOUNT_LABEL_ORDER',
											'INFRASDISCOUNT_LABEL_INVOICE',
											'INFRASDISCOUNT_PRODUCT',
											'INFRASDISCOUNT_SERVICE_LINK_TO_DISCOUNT',
											'INFRASDISCOUNT_PRODUCT_LINK_TO_DISCOUNT',
											'INFRASDISCOUNT_DEFAULT_REM_VALUE'
											),
							'Auto'	=> array('INFRASDISCOUNT_PRODUCT_AFFILIATE',
											'INFRASDISCOUNT_FREE_LINE',
											'INFRASDISCOUNT_NUMBER_DISCOUNT_ALLOW',
											'INFRASDISCOUNT_PONDERATION',
											'INFRASDISCOUNT_DESC_FREETEXT'
											),
							);
		$confkey	= $reg[1];
		foreach ($list[$confkey] as $constname){
			$value	= GETPOST($constname, 'none');
			if ($constname == 'INFRASDISCOUNT_PRODUCT_AFFILIATE') {
				$value	= implode(',', GETPOST($constname, 'array'));
			}
			// Convertir une chaîne vide en NULL pour INFRASDISCOUNT_PONDERATION
			if ($constname == 'INFRASDISCOUNT_PONDERATION' && $value === '') {
				$value	= 0;
			}
			$result	= dolibarr_set_const($db, $constname, $value, 'chaine', 0, 'InfraSDiscount module', $conf->entity);
		}
	}
	// Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************
	$freeLine				= GETPOST('INFRASDISCOUNT_FREE_LINE', 'int');
	$numberDiscountAllow	= GETPOST('INFRASDISCOUNT_NUMBER_DISCOUNT_ALLOW', 'int');
	$ponderation			= GETPOST('INFRASDISCOUNT_PONDERATION', 'alpha');
	$productlistselected	= GETPOST('INFRASDISCOUNT_PRODUCT_AFFILIATE', 'array');
	$ponderations			= getDolGlobalString('INFRASDISCOUNT_PONDERATION', '');
	$producttype			= getDolGlobalInt('INFRASDISCOUNT_PRODUCT_TYPE', 0);
	$productlist			= $form->select_produits_list('0', 'INFRASDISCOUNT_PRODUCT_AFFILIATE', '', 0, 0, '', -1, 2, 1, 0, '0', 0, '', 1,'', -1);
	$arrayProdAffiliate		= !empty(GETPOST('INFRASDISCOUNT_PRODUCT_AFFILIATE', 'array')) ? GETPOST('INFRASDISCOUNT_PRODUCT_AFFILIATE', 'array') : explode(',', getDolGlobalString('INFRASDISCOUNT_PRODUCT_AFFILIATE', ''));
	$productAffiliateIds	= explode(',', getDolGlobalString('INFRASDISCOUNT_PRODUCT_AFFILIATE', ''));
	$productLabels			= array();
	if (!empty($productAffiliateIds)) {
		$sql = "SELECT rowid, label FROM ".$db->prefix()."product WHERE rowid IN (".implode(',', array_map('intval', $productAffiliateIds)).")";
		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$productLabels[$obj->rowid] = $obj->label;
			}
		}
	}
	// View *****************************************
	$page_name		= $langs->trans('InfraSDiscountSetup').' - '.$langs->trans('Settings');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head			= infrasdiscount_Prepare_Head();
	$picto			= 'infrasdiscount@infrasdiscount';
	print dol_get_fiche_head($head, 'settings', $langs->trans('modcomnameInfrasdiscount'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar){
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie("tblPSexp"))) {
							tblPSexp = $.cookie("tblPSexp");
						}
						$(".toggle_bloc").hide();
						if (tblPSexp != "") {
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
							$.cookie("tblPSexp", "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie("tblPSexp", $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrasdiscountScrollUp").css("right", "30px");
							} else {
								$(".infrasdiscountScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '		<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Sauvegarde / Restauration
	if ($accessright == 2)	infrasdiscount_print_backup_restore();
	print '			<div class = "foldable">';
	print infrasdiscount_load_title('<span class = "infrasdiscounttitleparam">'.$langs->trans('InfraSDiscountFeatures').'</span>', $titleoption, dol_buildpath('/infrasdiscount/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '				<table name = "tblAG" class = "noborder toggle_bloc" centpercent>';
	$metas	= array('30px', '*', '90px', '256px', '120px');
	infrasdiscount_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrasdiscount_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infrasdiscount_print_btn_action('Gen', '<span class = "infrasdiscountcaution">'.$langs->trans('InfraSDiscountCaution').'</span> '.$langs->trans('InfraSDiscountParamCautionSave'), 4);
		$num	= infrasdiscount_print_input('INFRASDISCOUNT_ON_PROPALE', 'on_off', $langs->trans('InfraSDiscountOnPropale'), '', array(), 2, 1, '', $num);
		if (getDolGlobalString('INFRASDISCOUNT_ON_PROPALE', '')) {
			$num	= infrasdiscount_print_input('INFRASDISCOUNT_LABEL_PROPAL', 'input', $langs->trans('InfraSDiscountlabelPropalDefault', $langs->trans(getDolGlobalString('INFRASDISCOUNT_LABEL_PROPAL', ''))), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infrasdiscount_print_input('INFRASDISCOUNT_ON_ORDER', 'on_off', $langs->trans('InfraSDiscountOnOrder'), '', array(), 2, 1, '', $num);
		// $num = 4
		if (getDolGlobalString('INFRASDISCOUNT_ON_ORDER', '')) {
			$num	= infrasdiscount_print_input('INFRASDISCOUNT_LABEL_ORDER', 'input', $langs->trans('InfraSDiscountlabelOrderDefault', $langs->trans(getDolGlobalString('INFRASDISCOUNT_LABEL_ORDER', ''))), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infrasdiscount_print_input('INFRASDISCOUNT_ON_INVOICE', 'on_off', $langs->trans('InfraSDiscountOnInvoice'), '', array(), 2, 1, '', $num);
		if (getDolGlobalString('INFRASDISCOUNT_ON_INVOICE', '')) {
			$num	= infrasdiscount_print_input('INFRASDISCOUNT_LABEL_INVOICE', 'input', $langs->trans('InfraSDiscountlabelInvoiceDefault', $langs->trans(getDolGlobalString('INFRASDISCOUNT_LABEL_INVOICE', ''))), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		$metas	= array(1, 0, 0, 1, 2, '', 1, array(), 0, '1', 0, 'quatrevingtpercent', 1, '', null, 0, -1);
		$num	= infrasdiscount_print_input('INFRASDISCOUNT_SERVICE_LINK_TO_DISCOUNT', 'select_produits', $langs->trans('InfraSDiscountServiceLinkToDiscount'), 'InfraSDiscountServiceLinkToDiscountHelp', $metas, 2, 1, '', $num);
		$metas	= array(0, 0, 0, 1, 2, '', 1, array(), 0, '1', 0, 'quatrevingtpercent', 1, '', null, 0, -1);
		$num	= infrasdiscount_print_input('INFRASDISCOUNT_PRODUCT_LINK_TO_DISCOUNT', 'select_produits', $langs->trans('InfraSDiscountProductLinkToDiscount'), 'InfraSDiscountProductLinkToDiscountHelp', $metas, 2, 1, '', $num);
		// $num = 9
		$metas	= array('type' => 'number', 'step' => '0.01', 'min' => '0', 'max' => '100', 'class' => 'quatrevingtpercent right');
		$num	= infrasdiscount_print_input('INFRASDISCOUNT_DEFAULT_REM_VALUE', 'input', $langs->trans('InfraSDiscountDefaultRemValue'), '', $metas, 2, 1, '', $num);
	}
	print '				</table>
					</div>';
	print '			<div class = "foldable">';
	print infrasdiscount_load_title('<span class = "infrasdiscounttitleparam">'.$langs->trans('InfraSDiscountAuto').'</span>', $titleoption, dol_buildpath('/infrasdiscount/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '				<table name = "tblHP" class = "noborder toggle_bloc" centpercent>';
	$metas	= array('30px', '*', '90px', '256px', '120px');
	infrasdiscount_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrasdiscount_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infrasdiscount_print_btn_action('Auto', '<span class = "infrasdiscountcaution">'.$langs->trans('InfraSDiscountCaution').'</span> '.$langs->trans('InfraSDiscountParamCautionSave'), 4);
		$options	= array();
		foreach($productlist as $product) {
			$options[$product['key']]	= $product['label'];
		}
		$metas			= array('type' => 'number', 'class' => 'quatrevingtpercent right');
		$num			= infrasdiscount_print_input('INFRASDISCOUNT_NUMBER_DISCOUNT_ALLOW', 'input', $langs->trans('InfraSDiscountNumberofDiscountAllowed'), 'InfraSDiscountNumberofDiscountAllowedHelp', $metas, 2, 1, '', $num);
		$metas			= array('type' => 'number', 'class' => 'quatrevingtpercent right');
		$num			= infrasdiscount_print_input('INFRASDISCOUNT_FREE_LINE', 'input', $langs->trans('InfraSDiscountNumberofFree'), 'InfraSDiscountNumberofFreeHelp', $metas, 2, 1, '', $num);
		$metas			= $form->multiselectarray('INFRASDISCOUNT_PRODUCT_AFFILIATE', $options, $arrayProdAffiliate, 0, 0, '', 0, '80%');
		$num			= infrasdiscount_print_input('INFRASDISCOUNT_PRODUCT_AFFILIATE', 'select', $langs->trans('InfraSDiscountProductAffiliate'), 'InfraSDiscountProductAffiliateHelp', $metas, 2, 1, '', $num);
		$productLabels	= array('' => '') + $productLabels;
		$metas			= $form->selectarray('INFRASDISCOUNT_PONDERATION', $productLabels, $ponderations, 0, 0, 0, '', 0, 0, 0, '', 'centpercent', 1, '', 0, 0);
		$num			= infrasdiscount_print_input('INFRASDISCOUNT_PONDERATION', 'select', $langs->trans('InfraSDiscountPonderation'), 'InfraSDiscountPonderationHelp', $metas, 2, 1, '', $num);
		$num			= infraspackplus_print_input('INFRASDISCOUNT_DESC_FREETEXT', 'textarea', $langs->trans('InfraSDiscountParamDescFreeText'), '', array(), 2, 1, '', $num);
		// $num = 6
	}
	print '				</table>
					</div>';
	print '		</form>
				<a class = "infrasdiscountScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
