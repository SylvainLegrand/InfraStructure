<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand				- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina 	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/admin/mentions.php
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
	$langs->loadLangs(array('admin', 'companies', 'bills', 'errors', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infraspackplus', 'paramMentions')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$confirm_mesg	= '';
	$errors			= array();
	$action			= GETPOST('action', 'alpha');
	$confirm		= GETPOST('confirm', 'alpha');
	$labelmention	= GETPOST('selmentions', 'alpha') ? GETPOST('selmentions', 'alpha') : 'BASE';
	$selmodule		= GETPOST('selmodules', 'alpha') ? GETPOST('selmodules', 'alpha') : 'PROPOSAL_FREE_TEXT';
	$variablename	= $labelmention == 'BASE' ? $selmodule : $selmodule.'_'.$labelmention;
	$result			= '';
	// Confirmations
	if ($action == 'confirm_TVAauto' && $confirm == 'yes') {
		$sql	= 'UPDATE '.MAIN_DB_PREFIX.'c_infraspackplus_mention AS im
					JOIN (SELECT "TVA_MICRO" AS code, '.(int) $conf->entity.' AS entity, 1 AS active
							UNION ALL SELECT "TVA_VSI", '.(int) $conf->entity.', 0
							UNION ALL SELECT "TVA_VSE", '.(int) $conf->entity.', 0
							UNION ALL SELECT "TVA_VPI", '.(int) $conf->entity.', 0
							UNION ALL SELECT "TVA_VPE", '.(int) $conf->entity.', 0
							UNION ALL SELECT "TVA_BTP", '.(int) $conf->entity.', 0
						) vals ON im.code = vals.code AND im.entity = vals.entity
					SET im.active = vals.active';
		$resql	= $db->query($sql);
		if (!empty($resql)) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_FREETEXT_TVA_AUTO', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		} else {
			$errors[]	= $db->lasterror();
			$result		= -2;
		}
	}
	// Update with multicompany management
	if ($action == 'copyParams') {
		$sourceEntity	= GETPOSTINT('entity');
		dol_syslog('ici source entity = '.$sourceEntity, LOG_DEBUG);
		$res			= infraspackplus_copy_entity( $sourceEntity, $conf->entity);
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
		$list		= array('Notes'	=> array(),
							'Opt'	=> array('INFRASPLUS_PDF_FACTOR_PRE',
											'INFRASPLUS_PDF_FREETEXT_TVA_1',	'INFRASPLUS_PDF_FREETEXT_TVA_2',	'INFRASPLUS_PDF_FREETEXT_TVA_3',
											'INFRASPLUS_PDF_FREETEXT_TVA_4',	'INFRASPLUS_PDF_FREETEXT_TVA_5',	'INFRASPLUS_PDF_FREETEXT_TVA_6')
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
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
	if ($result == -2) {
		setEventMessages($langs->trans('Error'), $errors, 'errors');
	}

	// init variables *******************************
	$listModules	= array ();
	if (isModEnabled('propal')) {
		$listModules['PROPOSAL_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_PROPALE';
	}
	if (isModEnabled('commande')) {
		$listModules['ORDER_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_COMMANDE';
	}
	if (isModEnabled('contrat')) {
		$listModules['CONTRACT_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_CONTRAT';
	}
	if (isModEnabled('expedition')) {
		$listModules['SHIPPING_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_EXPEDITION';
	}
	if (getDolGlobalString('MAIN_SUBMODULE_LIVRAISON', '')) {
		$listModules['DELIVERY_FREE_TEXT']	= 'InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON';
	}
	if (isModEnabled('ficheinter')) {
		$listModules['FICHINTER_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_FICHEINTER';
	}
	if (isModEnabled('facture')) {
		$listModules['INVOICE_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_FACTURE';
	}
	if (isModEnabled('supplier_proposal')) {
		$listModules['SUPPLIER_PROPOSAL_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL';
	}
	if (isModEnabled('fournisseur')) {
		$listModules['SUPPLIER_ORDER_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_FOURNISSEUR';
	}
	if (isModEnabled('product')) {
		$listModules['PRODUCT_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_PRODUCT';
	}
	if (isModEnabled('mrp')) {
		$listModules['MRP_MO_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_MRP';
	}
	if (isModEnabled('bom')) {
		$listModules['BOM_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_BOM';
	}
	if (isModEnabled('expensereport')) {
		$listModules['EXPENSEREPORT_FREE_TEXT']	= 'InfraSPlusParam_MAIN_MODULE_EXPENSEREPORT';
	}
	$optionsSelect	= '';
	$country		= !empty($mysoc->country_code) ? $mysoc->country_code : substr($langs->defaultlang, -2);
	$franchise		= $country == 'FR' && empty($mysoc->tva_assuj) ? 1 : 0;
	if (!empty($franchise) && !getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_AUTO', '')) {
		$confirm_mesg	= $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('InfraSPlusParamTVAauto'), $langs->trans('InfraSPlusParamConfirmSetTVAauto'), 'confirm_TVAauto', '', 'yes', 1);
	}

	// View *****************************************
	$page_name	= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsMentions');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback	= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head		= infraspackplus_admin_prepare_head();
	$picto		= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'mentions', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script type = "text/javascript">
					function doReloadFreeT(){
						document.frm1.submit();
					}
					$(function () {
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) {
								$(".toggle_bloc").hide();
							} else {
								$(".toggle_bloc").hide();
								$(this).siblings().show();
							}
							$.cookie("tblIexp", "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie("tblIexp", $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
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
	print '		<form name="frm1" id="frm1" action = "'.$_SERVER['PHP_SELF'].'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
	}
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamGestionMentions').'</span>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "noborder centpercent">';
	$metas	= array('*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(3), 'InfraSPlusParamNewMention');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		infraspackplus_print_btn_action('Notes', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 2);
		print '			<tr class = "oddeven">
							<td colspan = "2">';
		print $form->textwithpicto($langs->trans('InfraSPlusParamMention1'), $langs->trans('AddCRIfTooLong').'<br><br>', 1, 'help', '', 0, 2, 'freetexttooltip').'&nbsp;';
		print '<label for = "selmodules">'.$langs->trans('InfraSPlusParamMention2').'</label> '.$form->selectarray('selmodules', $listModules, $selmodule, 0, 0, 0, 'class = "fontsizeinherit nopadding cursorpointer" onchange = "doReloadFreeT();"', 1, 0, 0, '', '');
		print select_infraspackplus_dict('c_infraspackplus_mention', $labelmention, 'selmentions', 0, 'doReloadFreeT()', 1);
		print info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'), 1, 1, 0);
		print '				</td>
						</tr>';
		print '			<tr>
							<td colspan = "2">';
		if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT', '')) {
			print '<textarea name="'.$variablename.'" class = "flat" cols = "120">'.getDolGlobalString($variablename, '').'</textarea>';
		} else {
			$doleditor	= new DolEditor($variablename, getDolGlobalString($variablename, ''), 0, 80, 'dolibarr_notes');
			print $doleditor->Create();
		}
		print '				</td>
						</tr>';
		infraspackplus_print_final(2);
	}
	print '			</table>
				</form>
				<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamMentionsSetup').'</span>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
	print '			<table class = "noborder centpercent">';
	$metas	= array('30px', '*', '456px', '130px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Opt', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 3);
		if (isModEnabled('propal')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PROPALE')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('commande')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_COM', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_COMMANDE')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('contrat')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_CT', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_CONTRAT')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('expedition')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXP', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPEDITION')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('livraison')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_REC', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_SUBMODULE_LIVRAISON')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('ficheinter')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FI', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FICHEINTER')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('facture')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FAC', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FACTURE')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('supplier_proposal')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV_FOU', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_SUPPLIERPROPOSAL')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('fournisseur')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FOU', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_FOURNISSEUR')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('product')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_PROD', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_PRODUCT')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('mrp')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_MRP', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_MRP')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('bom')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_BOM', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_BOM')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('expensereport')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXPR', 'on_off', $langs->trans('InfraSPlusParamMCBaseDef', $langs->trans('InfraSPlusParam_MAIN_MODULE_EXPENSEREPORT')), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		infraspackplus_print_hr(3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREETEXTEND', 'on_off', $langs->trans('InfraSPlusParamFreeTextEnd'), '', array(), 1, 1, '', $num);
		infraspackplus_print_hr(3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREETEXT_FACTOR_AUTO', 'on_off', $langs->trans('InfraSPlusParamFreeTextFactorAuto'), '', array(), 1, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_FREETEXT_FACTOR_AUTO', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTOR_PRE', 'input', $langs->trans('InfraSPlusParamFactorPrefix'), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		infraspackplus_print_hr(3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREETEXT_TVA_AUTO', 'on_off', $langs->trans('InfraSPlusParamFreeTextTVAauto'), ' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamFreeTextTVAautoHelp'), array(), 1, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_AUTO', '')) {
			if (!empty($franchise)) {
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_1', ''), 'INFRASPLUS_PDF_FREETEXT_TVA_1', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_1'), '', $metas, '1', '1');
				$num	+= 5;
			} else {
				$num++;
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_2', ''), 'INFRASPLUS_PDF_FREETEXT_TVA_2', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_2'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_3', ''), 'INFRASPLUS_PDF_FREETEXT_TVA_3', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_3'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_4', ''), 'INFRASPLUS_PDF_FREETEXT_TVA_4', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_4'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_5', ''), 'INFRASPLUS_PDF_FREETEXT_TVA_5', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_5'), '', $metas, 1, 1, '', $num);
				$metas	= select_infraspackplus_dict('c_infraspackplus_mention', getDolGlobalString('INFRASPLUS_PDF_FREETEXT_TVA_6', ''), 'INFRASPLUS_PDF_FREETEXT_TVA_6', 0, '', 0, 'code LIKE "TVA\_%"');
				$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFreeTextTVA_6'), '', $metas, 1, 1, '', $num);
			}
		} else {
			$num	+= 6;
		}
	}
	print '			</table>
				</form>
				<a class = "infrasplusScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
