<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand			- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2026		Fallinah Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/admin/notes.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup Additional informations for the module InfraS
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
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infraspackplus', 'paramNotes')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$action			= GETPOST('action', 'alpha');
	$labelnote		= GETPOST('selnotes', 'alpha')		? GETPOST('selnotes', 'alpha')		: 'BASE';
	$selmodule		= GETPOST('selmodules', 'alpha')	? GETPOST('selmodules', 'alpha')	: 'PROPOSAL_PUBLIC_NOTE';
	$variablename	= $labelnote == 'BASE'				? $selmodule						: $selmodule.'_'.$labelnote;
	$result			= '';
	// Update with multicompany management
	if ($action == 'copyParams') {
		$sourceEntity	= GETPOSTINT('entity');
		$res			= infraspackplus_copy_entity($sourceEntity, $conf->entity);
		if ($res < 0) {
			setEventMessage($langs->trans('InfraSPackPlusErrorFailedToCopyParameters',$sourceEntity, $conf->entity),'errors');
		} else {
			setEventMessage($langs->trans('InfraSPackPlusParametersCopiedFromEntity', $sourceEntity), 'mesgs');
			header('Location: '.$_SERVER['PHP_SELF']);
			exit;
		}
	}
	//Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infraspackplus_bkup_module ('infraspackplus');
	}
	if ($action == 'restoreParams') {
		$result	= infraspackplus_restore_module ('infraspackplus');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('NoteStd'	=> array('INFRASPLUS_PDF_NT_USED_AS_COVER'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'none');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'Notes') {
			$result	= dolibarr_set_const($db, $variablename, GETPOST($variablename, 'none'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), array(), 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), array(), 'errors');
	}

	// init variables *******************************
	$listModules	= array ();
	if (isModEnabled('propal')) {
		$listModules['PROPOSAL_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_PROPALE';
	}
	if (isModEnabled('commande')) {
		$listModules['ORDER_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_COMMANDE';
	}
	if (isModEnabled('contrat')) {
		$listModules['CONTRACT_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_CONTRAT';
	}
	if (isModEnabled('expedition')) {
		$listModules['SHIPPING_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_EXPEDITION';
	}
	if (getDolGlobalString('MAIN_SUBMODULE_LIVRAISON', '')) {
		$listModules['DELIVERY_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON';
	}
	if (isModEnabled('ficheinter')) {
		$listModules['FICHINTER_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_FICHEINTER';
	}
	if (isModEnabled('facture')) {
		$listModules['INVOICE_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_FACTURE';
	}
	if (isModEnabled('supplier_proposal')) {
		$listModules['SUPPLIER_PROPOSAL_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL';
	}
	if (isModEnabled('fournisseur')) {
		$listModules['SUPPLIER_ORDER_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_FOURNISSEUR';
	}
	if (isModEnabled('product')) {
		$listModules['PRODUCT_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_PRODUCT';
	}
	if (isModEnabled('mrp')) {
		$listModules['MRP_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_MRP';
	}
	if (isModEnabled('bom')) {
		$listModules['BOM_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_BOM';
	}
	if (isModEnabled('projet')) {
		$listModules['PROJECT_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_PROJET';
	}
	if (isModEnabled('expensereport')) {
		$listModules['EXPENSEREPORT_PUBLIC_NOTE']	= 'InfraSPlusParam_MAIN_MODULE_EXPENSEREPORT';
	}
	$ntUsedAsCover	= getDolGlobalString('INFRASPLUS_PDF_NT_USED_AS_COVER', '');

	// View *****************************************
	$page_name	= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsNotes');
	llxHeader('', $page_name);
	$linkback	= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head		= infraspackplus_admin_prepare_head();
	$picto		= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'notes', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script type = "text/javascript">
					function doReloadNoteP(){
						document.frm1.submit();
					}
				</script>';
	}
	print '		<form name = "frm1" id = "frm1" action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
	}
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamGestionNotes').'</span>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "infrasplusnoborder centpercent">';
	$metas	= array('*', '130px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(3), 'InfraSPlusParamNewNote');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		infraspackplus_print_btn_action('Notes', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 2);
		print '			<tr class = "oddeven">
							<td colspan = "2">';
		print $form->textwithpicto($langs->trans('InfraSPlusParamNote1'), $langs->trans('AddCRIfTooLong').'<br><br>', 1, 'help', '', 0, 2, 'freetexttooltip').'&nbsp;';
		print '<label for = "selmodules">'.$langs->trans('InfraSPlusParamNote2').'</label> '.
		$form->selectarray('selmodules', $listModules, $selmodule, 0, 0, 0, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer" onchange = "doReloadNoteP();"', 1, 0, 0, '', '');
		print select_infraspackplus_dict('c_infraspackplus_note', $labelnote, 'selnotes', 0, 'doReloadNoteP()');
		print info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'), 1, 1, 0);
		print '				</td>
						</tr>';
		print '			<tr>
							<td colspan = "2">';
		$doleditor	= new DolEditor($variablename, getDolGlobalString($variablename, ''), 0, 80, 'dolibarr_notes');
		print $doleditor->Create();
		print '				</td>
						</tr>';
		infraspackplus_print_final();
	}
	print '			</table>
				</form>
				<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	if (!empty($accessright)) {
		print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamNotesSetup').'</span>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
		print '			<table class = "infrasplusnoborder centpercent">';
		$metas		= array('30px', '*', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		$metas		= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infraspackplus_print_liste_titre($metas);
		infraspackplus_print_btn_action('NoteStd', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 3);
		$num	= 1;
		$metas	= select_infraspackplus_dict('c_infraspackplus_note', $ntUsedAsCover, 'INFRASPLUS_PDF_NT_USED_AS_COVER', 1, '', 0);
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamNTUsedAsCover'), '', $metas, 1, 1, '', $num);
		if (isModEnabled('propal')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PROPALE')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('commande')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_COM', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_COMMANDE')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('contrat')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_CT', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_CONTRAT')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('expedition')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_EXP', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPEDITION')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('livraison')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_REC', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('ficheinter')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FI', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FICHEINTER')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('facture')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FAC', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FACTURE')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('supplier_proposal')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV_FOU', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('fournisseur')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FOU', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FOURNISSEUR')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('product')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_PROD', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PRODUCT')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('mrp')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_MRP', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_MRP')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('bom')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_BOM', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_BOM')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('projet')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_PROJ', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PROJET')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('expensereport')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_NT_BASE_EXPR', 'on_off', $langs->trans('InfraSPlusParamNTBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPENSEREPORT')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		print '		</table>';
	}
	print '		</form>';
	print dol_get_fiche_end();
	llxFooter();
