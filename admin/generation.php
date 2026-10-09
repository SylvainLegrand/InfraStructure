<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand			- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/admin/generation.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup options before PDF generation for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infraspackplus', 'paramGeneration')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$confirm_mesg	= '';
	$action			= GETPOST('action', 'alpha');
	$confirm		= GETPOST('confirm', 'alpha');
	$result			= '';
	// Update with multicompany management
	if ($action == 'copyParams') {
		$sourceEntity	= GETPOSTINT('entity');
		$res			= infraspackplus_copy_entity($sourceEntity, $conf->entity);
		dol_syslog('ici generation.php copyParams sourceEntity='.$sourceEntity.' conf->entity='.$conf->entity.' res='.$res);
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
	// Purge des réglages PDF orphelins (table llx_infraspackplus_pdf_params)
	if ($action == 'purgePdfParams' && $accessright == 2) {
		$nbpurged	= infraspackplus_purge_pdf_params();
		if ($nbpurged < 0) {
			setEventMessages($langs->trans('Error'), [], 'errors');
		} else {
			setEventMessages($langs->trans('InfraSPlusPdfParamsPurged', $nbpurged), [], 'mesgs');
		}
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	// 3rd value = default state of the "always visible" (pin) checkbox: 1 = checked by default, absent/0 = unchecked
	$listOptions	= array('logo'					=> array('PDFInfraSPlusLogo',						1),	// $num = 1
							'adr'					=> array('PDFInfraSPlusAddress',					1),	// $num = 2
							'customerAddrSelect'	=> array('PDFInfraSPlusCustomerAddress',			1),	// $num = 3
							'listfreet'				=> array('PDFInfraSPlusMentions',					1),	// $num = 4
							'listnotep'				=> array('PDFInfraSPlusNotes',						1),	// $num = 5
							'pied'					=> array('PDFInfraSPlusPied',						1),	// $num = 6
							'adrlivr'				=> array('PDFInfraSPlusAdrLivr',					1,	1),	// $num = 7
							'adrSst'				=> array('PDFInfraSPlusListSsT',					1,	1),	// $num = 8
							'adrlivrfour'				=> array('PDFInfraSPlusAdrLivrFour',					1,	1),	// $num = 9
							'cgv'					=> array('PDFInfraSPlusCGVchk',						1),	// $num = 10
							'cgi'					=> array('PDFInfraSPlusCGIchk',						1),	// $num = 11
							'cga'					=> array('PDFInfraSPlusCGAchk',						1),	// $num = 12
							'filesArray'				=> array('PDFInfraSPlusFiles',						1),	// $num = 13
							'expensereportfiles'		=> array('InfraSPlusParamFilesFromExpensereport',	1),	// $num = 14
							'includealias'			=> array('PDFParamAliasIn3rdName',					1),	// $num = 15
							'mergeproduct'			=> array('PDFInfraSPlusMergeProduct',				1),	// $num = 16
							'docseparate'			=> array('PDFInfraSPlusDocSeparate',				1),	// $num = 17
							'usentascover'			=> array('PDFInfraSPlusUseNtAsCover',				1),	// $num = 18
							'showwvccchk'			=> array('PDFInfraSPlusShowWVCCchk',				1),	// $num = 19
							'hidepict'				=> array('PDFInfraSPlusHidePictchk',				1),	// $num = 20
							'refcol'				=> array('PDFInfraSPlusShowRefCol',					1),	// $num = 21
							'hidetimespent'			=> array('PDFInfraSPlusHidetimeSpentchk',			1),	// $num = 22
							'hidedesc'				=> array('PDFInfraSPlusHideDescchk',				1),	// $num = 23
							'hidedisc'				=> array('PDFInfraSPlusHideDiscchk',				1),	// $num = 24
							'hidecols'				=> array('PDFInfraSPlusHideColschk',				1),	// $num = 25
							'showpricebl'			=> array('PDFInfraSPlusShowPriceBLchk',				1),	// $num = 26
							'adrfact'				=> array('PDFInfraSPlusParamAdrFact',				1),	// $num = 27
							'showtotdisc'			=> array('InfraSPlusShowTotDiscChk',				!getDolGlobalInt('INFRASPLUS_PDF_SHOW_DISCOUNT_TOT', 0)),	// $num = 28
							'showtvabtp'			=> array('InfraSPlusShowTVAtxtBTPChk',				1),	// $num = 29
							'showtot'				=> array('InfraSPlusShowTotChk',					1),	// $num = 30
							'showvir'				=> array('InfraSPlusParamNoIBAN',					1),	// $num = 31
							'showpayspec'			=> array('InfraSPlusShowPaySpecChk',				1),	// $num = 32
							'showPropalSignEmet'	=> array('InfraSPlusShowPropalSignEmetChk',			1)	// $num = 33
							);
	if (preg_match('/update_(.*)/', $action, $reg)) {
		foreach ($listOptions as $option => $transKey) {
			$constname	= 'INFRASPLUS_PDF_OPTION_'.$option;
			$result		= dolibarr_set_const($db, $constname, GETPOST($constname, 'alpha'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$pinname	= 'INFRASPLUS_PDF_OPTION_PIN_'.$option;
			$result		= dolibarr_set_const($db, $pinname, GETPOST($pinname, 'int') ? '1' : '0', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			if ($option == 'adrSst') {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_OPTION_Sst', GETPOST($constname, 'alpha'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
			if ($option == 'adrlivrfour') {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_OPTION_typeadr', GETPOST($constname, 'alpha'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
			if ($option == 'usentascover') {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_OPTION_showntusedascover', GETPOST($constname, 'alpha'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
		}
	}
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), [], 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), [], 'errors');
	}

	// init variables *******************************

	// View *****************************************
	$page_name		= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsGeneration');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= '';

	// Configuration header *************************
	$head			= infraspackplus_admin_prepare_head();
	$picto			= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'generation', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					jQuery(document).ready(function() {
						var tblIexp = "";
						$.isSet = function(testVar) {
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie("tblIexp"))) {
							tblIexp = $.cookie("tblIexp");
						}
						$(".toggle_bloc").hide();
						if (tblIexp) { $("[name=" + tblIexp + "]").toggle(); }
					});
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
	print '		<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
		// Réglages PDF enregistrés par document / client / utilisateur (table llx_infraspackplus_pdf_params) et purge des orphelins
		$nbparams	= infraspackplus_count_pdf_params();
		print '	<table class = "centpercent noborderspacing">';
		$metas		= array('*', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		print '		<tr>
						<td colspan = "2" class = "center infrasplustitleparam">'.$langs->trans('InfraSPlusPdfParamsStored', $nbparams['doc'], $nbparams['cust'], $nbparams['user']).'<br><span class = "opacitymedium">'.$langs->trans('InfraSPlusPdfParamsPurgeDesc').'</span></td>
						<td class = "center"><button class = "butActionDelete" type = "submit" value = "purgePdfParams" name = "action">'.$langs->trans('InfraSPlusPdfParamsPurge').'</button></td>
					</tr>';
		infraspackplus_print_hr(count($metas));
		infraspackplus_print_final(count($metas));
		print '	</table>';
	}
	print '			<div class = "NOfoldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamGenerationSetup').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title');
	print '				<table name = "tblGen" class = "noborder NOtoggle_bloc centpercent">';
	$metas	= array('30px', '*', '150px', '150px', '150px', '150px', '150px', '150px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1, 1, 1, 1, 1, 1), 'NumberingShort', 'Description', 'InfraSPlusParamBkpPerUser', 'InfraSPlusParamBkpPerDocument', 'InfraSPlusParamBkpPerType', 'InfraSPlusParamBkpPerCustomer', 'InfraSPlusParamBkpNone', 'InfraSPlusParamPinVisible', '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Gen', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave').'<br/><span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamAvertissementCalculImage'), 8);
		foreach ($listOptions as $option => $transKey) {
			if (!empty($transKey[1])) {
				$confkey		= 'INFRASPLUS_PDF_OPTION_'.$option;
				$option_value	= getDolGlobalString($confkey, 'none');
				$pinkey			= 'INFRASPLUS_PDF_OPTION_PIN_'.$option;
				$pin_checked	= getDolGlobalString($pinkey, !empty($transKey[2]) ? '1' : '0') == '1';
				print '			<tr class = "oddeven">
									<td class = "center bold">'.$num.'</td>
									<td>'.$langs->trans($transKey[0]).'</td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "user"'.($option_value == 'user' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "doc"'.($option_value == 'doc' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "type"'.($option_value == 'type' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "cust"'.($option_value == 'cust' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "none"'.($option_value == 'none' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "checkbox" name = "'.$pinkey.'" value = "1"'.($pin_checked ? ' checked' : '').'/></td>
								</tr>';
			}
			$num++;
		}
	}
	print '				</table>
					</div>
				</form>
				<a class = "infrasplusScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
