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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		../infrasproject/admin/infrasprojectsetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraSProject
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infrasproject/core/lib/infrasprojectAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'other', 'loan', 'donations', 'salaries', 'compta', 'banks', 'trips', 'products', 'stocks', 'mrp', 'companies', 'propal', 'orders', 'sendings', 'contracts', 'bills', 'projects', 'infrasproject@infrasproject'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrasproject', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrasproject', 'paramSetup')) ? 1 : 0);
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
	if ($action == 'bkupParams' && $accessright == 2) {
		$result	= infrasproject_bkup_module ('infrasproject');
	}
	if ($action == 'restoreParams' && $accessright == 2) {
		$result	= infrasproject_restore_module ('infrasproject');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		if (preg_match('/^(INFRASPROJECT_|STOCK_MOVEMENT_INTO_PROJECT_OVERVIEW|STOCK_SUPPORTS_SERVICES|PRODUCT_DISABLE_)/', $confkey)) {
			$result		= dolibarr_set_const($db, $confkey, GETPOST('value', 'alphanohtml'), 'chaine', 0, 'InfraSProject module', $conf->entity);
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Gen'	=> array('INFRASPROJECT_INVCODEPREFIX', 'INFRASPROJECT_SEARCHMODE', 'INFRASPROJECT_DEFAULT_WAREHOUSE', 'INFRASPROJECT_STOCK_PROD_CAT', 'INFRASPROJECT_TYPE_FEES_NOT_INCLUDED_IN_MARGIN',
											 'INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV', 'INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV', 'INFRASPROJECT_FIRST_MARK_RATE_TO_BE_APPLIED', 'INFRASPROJECT_SECOND_MARK_RATE_TO_BE_APPLIED', 'INFRASPROJECT_THIRD_MARK_RATE_TO_BE_APPLIED',
											));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			if (in_array($constname, array('INFRASPROJECT_TYPE_FEES_NOT_INCLUDED_IN_MARGIN', 'INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV', 'INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV'))) {
				$constvalue = implode(',', GETPOST($constname, 'array'));
			} else {
				$constvalue	= GETPOST($constname, 'alpha');
			}
			$result	= dolibarr_set_const($db, $constname, is_array($constvalue) ? implode(',', $constvalue) : $constvalue, 'chaine', 0, 'InfraSProject module', $conf->entity);
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
	// Comportement général
	$searchMode					= getDolGlobalInt('INFRASPROJECT_SEARCHMODE', 1);
	$categoriesProductArr		= $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
	$categoriesProductArr[-2]	= '- '.$langs->trans('NotCategorized').' -';
	$selectedTypeFees			= explode(',', getDolGlobalString('INFRASPROJECT_TYPE_FEES_NOT_INCLUDED_IN_MARGIN', ''));
	$listofreferent				= infrasproject_getListOfReferent();
	foreach ($listofreferent as $key => $val) {
		$qualified	= $val['testparam'];
		if ($qualified) {
			$listofreferent[$key]	= $langs->trans($val['name']);
		} else {
			unset($listofreferent[$key]);
		}
	}
	$selectedMarginProv			= explode(',', getDolGlobalString('INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV', ''));
	$selectedMarginProvPlus		= explode(',', getDolGlobalString('INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV', ''));

	// View *****************************************
	$page_name					= $langs->trans('Module500055Name').' - '.$langs->trans('InfraSProjectParamModule');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback					= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption				= '';

	// Configuration header *************************
	$head						= infrasproject_admin_prepare_head();
	$picto						= 'infrasproject@infrasproject';
	print dol_get_fiche_head($head, 'infrasprojectsetup', $langs->trans('modcomnameInfraSProject'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrasprojectScrollUp").css("right", "30px");
							} else {
								$(".infrasprojectScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Sauvegarde / Restauration
	if ($accessright == 2)	infrasproject_print_backup_restore();
	// Comportement général
	print '		<div class = "foldable">';
	print infrasproject_load_title('<span class = "infrasprojecttitleparam">'.$langs->trans('InfraSProjectParamModule').'</span>', $titleoption, dol_buildpath('/infrasproject/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblAG" class = "infrasprojectnoborder toggle_bloc" width = "100%">';
	$metas	= array('30px', '*', '200px', '200px', '120px');
	infrasproject_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrasproject_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infrasproject_print_btn_action('Gen', '<span class = "infrasprojectcaution">'.$langs->trans('InfraSProjectCaution').'</span> '.$langs->trans('InfraSProjectCautionSave'), 4, 'center', 'Modify', false);
		$num	= infrasproject_print_input('INFRASPROJECT_INVCODEPREFIX', 'input', $langs->trans('InfraSProjectPrefixInvcod'), '', array(), 2, 1, '', $num);
		$metas	= $form->selectarray('INFRASPROJECT_SEARCHMODE', array('1' => 'InfraSProjectSearch1', '2' => 'InfraSProjectSearch2', '3' => 'InfraSProjectSearch3'), $searchMode, 0, 0, 0, '', 1, 0, 0, '', 'quatrevingtpercent');
		$num	= infrasproject_print_input('', 'select', $langs->trans('InfraSProjectSearchMode'), '', $metas, 2, 1, '', $num);
		$num	= infrasproject_print_input('STOCK_MOVEMENT_INTO_PROJECT_OVERVIEW', 'on_off', $langs->trans('InfraSProjectStockMvtIntoPrjOverview'), '', array(), 2, 1, '', $num);
		$metas	= array('filterstatus' => '', 'empty' => 1, 'disabled' => 0, 'fk_product' => 0, 'empty_label' => '', 'showstock' => 0, 'forcecombo' => 0, 'events' => array(), 'morecss' => 'quatrevingtpercent', 'exclude' => array(), 'showfullpath' => 1, 'stockMin' => false, 'orderBy' => 'e.ref');
		$num	= infrasproject_print_input('INFRASPROJECT_DEFAULT_WAREHOUSE', 'select_warehouse', $langs->trans('MainDefaultWarehouse'), '', $metas, 2, 1, '', $num);
		$num	= infrasproject_print_input('INFRASPROJECT_LINK_TO_USER', 'on_off', $langs->trans('InfraSInfraSProjectProjectConsumptionLinkToUser'), '', array(), 2, 1, '', $num);
		$metas	= Form::multiselectarray('INFRASPROJECT_STOCK_PROD_CAT', $categoriesProductArr, explode(',', getDolGlobalString('INFRASPROJECT_STOCK_PROD_CAT', '')), 0, 0, 'centpercent');
		$num	= infrasproject_print_input('', 'select', $langs->trans('InfraSInfraSProjectStockProdCat'), '', $metas, 1, 2, '', $num);
		$num	= infrasproject_print_input('INFRASPROJECT_CREATE_PROJECT_FROM_SIGNED_PROPAL', 'on_off', $langs->trans('InfraSInfraSProjectCreateProjectFromSignedPropal'), '', array(), 2, 1, '', $num);
		// $num = 8
		if (isModEnabled('contacttracking')) {
			infrasproject_print_hr(4);
			$num	= infrasproject_print_input('INFRASPROJECT_SHOW_LAST_EXCHANGE', 'on_off', $langs->trans('InfraSInfraSProjectShowLastExchange'), '', array(), 2, 1, '', $num);
			$num	= infrasproject_print_input('INFRASPROJECT_SHOW_NEXT_ACTION', 'on_off', $langs->trans('InfraSInfraSProjectShowNextAction'), '', array(), 2, 1, '', $num);
		} else {
			$num	+= 2;
		}
		$num	= infrasproject_print_input('INFRASPROJECT_SHOW_MARGIN_PROV', 'on_off', $langs->trans('InfraSInfraSProjectShowMarginProv'), '', array(), 2, 1, '', $num);
		// $num = 11
		if (getDolGlobalInt('INFRASPROJECT_SHOW_MARGIN_PROV',0)) {
			$metas	= $form->multiselectarray('INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV', $listofreferent, $selectedMarginProvPlus, 0, 0, 'centpercent', 0, 0, '', '', '');
			$num	= infrasproject_print_input('', 'select', $langs->trans('InfraSInfraSProjectElementsForPlusMargin'), '', $metas, 1, 2, '', $num);
			$metas	= $form->multiselectarray('INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV', $listofreferent, $selectedMarginProv, 0, 0, 'centpercent', 0, 0, '', '', '');
			$num	= infrasproject_print_input('', 'select', $langs->trans('InfraSInfraSProjectElementsForMinusMargin'), '', $metas, 1, 2, '', $num);
			if (in_array('invoice_supplier', $selectedMarginProvPlus) || in_array('invoice_supplier', $selectedMarginProv)) {
				$num = infrasproject_print_input('INFRASPROJECT_ADD_SUPPLIER_INVOICE_IN_MARGIN_PROV', 'on_off', $langs->trans('InfraSProjectAddSupplierInvoiceInMarginProv'), '', array(), 2,1, '', $num);
			} else {
				$num++;
			}
			$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
			$num		= infrasproject_print_input('INFRASPROJECT_FIRST_MARK_RATE_TO_BE_APPLIED', 'input', $langs->trans('InfraSProjectFirstMarkRateToBeApplied'), '', $metas, 2, 1, '&nbsp;%', $num);
			$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
			$num		= infrasproject_print_input('INFRASPROJECT_SECOND_MARK_RATE_TO_BE_APPLIED', 'input', $langs->trans('InfraSProjectSecondMarkRateToBeApplied'), '', $metas, 2, 1, '&nbsp;%', $num);
			$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
			$num		= infrasproject_print_input('INFRASPROJECT_THIRD_MARK_RATE_TO_BE_APPLIED', 'input', $langs->trans('InfraSProjectThirdMarkRateToBeApplied'), '', $metas, 2, 1, '&nbsp;%', $num);
		} else {
			$num += 6;
		}
		// $num = 17
		if (getDolGlobalInt('INFRASPROJECT_SHOW_MARGIN_PROV',0)) {
			$num	= infrasproject_print_input('INFRASPROJECT_SHOW_MARGIN_PROV_FIRST', 'on_off', $langs->trans('InfraSProjectShowMarginProvFirst'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		$metas	= array($selectedTypeFees, 0, 0, 'centpercent', 0, 0, '', '', '', -1);
		$num	= infrasproject_print_input('INFRASPROJECT_TYPE_FEES_NOT_INCLUDED_IN_MARGIN', 'multiselect_type_fees', $langs->trans('InfraSProjectTypeFeesNotIncludedInMargin1').' <span class = "infraspluscaution">'.$langs->trans('InfraSProjectTypeFeesNotIncludedInMargin2').'</span> '.$langs->trans('InfraSProjectTypeFeesNotIncludedInMargin3'), '', $metas, 1, 2, '', $num);
		$num	= infrasproject_print_input('INFRASPROJECT_HIDE_EMPTY_LIST', 'on_off', $langs->trans('InfraSProjectHideEmptyList'), '', array(), 2, 1, '', $num);
		// $num = 20
		if (isModEnabled('order')) {
			$num	= infrasproject_print_input('INFRASPROJECT_HIDE_CUSTOMER_ORDERS_LIST', 'on_off', $langs->trans('InfraSProjectHideCustomerOrderList'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('invoice')) {
			$num	= infrasproject_print_input('INFRASPROJECT_HIDE_CUSTOMER_INVOICE_LIST', 'on_off', $langs->trans('InfraSProjectHideCustomerInvoiceList'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('supplier_proposal')) {
			$num	= infrasproject_print_input('INFRASPROJECT_HIDE_SUPPLIER_PROPOSAL_LIST', 'on_off', $langs->trans('InfraSProjectHideSupplierProposalList'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 23
		if (isModEnabled('expedition')) {
			$num	= infrasproject_print_input('INFRASPROJECT_HIDE_SHIPMENT_LIST', 'on_off', $langs->trans('InfraSProjectHideShipmentList'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('projet')) {
			$num	= infrasproject_print_input('INFRASPROJECT_HIDE_PROJECT_TASK_LIST', 'on_off', $langs->trans('InfraSProjectHideProjectTaskList'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 25
		$num	= infrasproject_print_input('INFRASPROJECT_SHOW_ORDER_LINK', 'on_off', $langs->trans('InfraSProjectShowOrderLink'), '', array(), 2, 1, '', $num);

	}
	print '			</table>';
	print '		</div>';
	// Paramètres Dolibarr natif
	if (isModEnabled('productbatch')) {
		print '	<div class = "foldable">';
		print infrasproject_load_title('<span class = "infrasprojecttitleparam">'.$langs->trans('InfraSProjectParamDolibarr').'</span>', $titleoption, dol_buildpath('/infrasproject/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblDOL" class = "infrasprojectnoborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '200px');
		infrasproject_print_colgroup($metas);
		$metas	= array(array(1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'));
		infrasproject_print_liste_titre($metas);
		if (!empty($accessright)) {
			$num	= 1;
			$num	= infrasproject_print_input('PRODUCT_DISABLE_EATBY', 'on_off', $langs->trans('InfraSProjectDisableEatBy'), '', array(), 1, 1, '', $num);
			$num	= infrasproject_print_input('PRODUCT_DISABLE_SELLBY', 'on_off', $langs->trans('InfraSProjectDisableSellBy'), '', array(), 1, 1, '', $num);
			// $num = 3
	}
		print '		</table>';
		print '	</div>';
	}
	print '	</form>
			<a class = "infrasprojectScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
