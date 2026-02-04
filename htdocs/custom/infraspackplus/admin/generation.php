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
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
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
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	$listOptions	= array('logo'					=> array('PDFInfraSPlusLogo',						1),	// $num = 1
							'adr'					=> array('PDFInfraSPlusAddress',					1),	// $num = 2
							'customerAddrSelect'	=> array('PDFInfraSPlusCustomerAddress',			1),	// $num = 3
							'listfreet'				=> array('PDFInfraSPlusMentions',					1),	// $num = 4
							'listnotep'				=> array('PDFInfraSPlusNotes',						1),	// $num = 5
							'pied'					=> array('PDFInfraSPlusPied',						1),	// $num = 6
							'adrlivr'				=> array('PDFInfraSPlusAdrLivr',					1),	// $num = 7
							'adrSst'				=> array('PDFInfraSPlusListSsT',					1),	// $num = 8
							'adrlivrfour'			=> array('PDFInfraSPlusAdrLivrFour',				1),	// $num = 9
							'cgv'					=> array('PDFInfraSPlusCGVchk',						1),	// $num = 10
							'cgi'					=> array('PDFInfraSPlusCGIchk',						1),	// $num = 11
							'cga'					=> array('PDFInfraSPlusCGAchk',						1),	// $num = 12
							'filesArray'			=> array('PDFInfraSPlusFiles',						1),	// $num = 13
							'expensereportfiles'	=> array('InfraSPlusParamFilesFromExpensereport',	1),	// $num = 14
							'includealias'			=> array('PDFParamAliasIn3rdName',					1),	// $num = 15
							'mergeproduct'			=> array('PDFInfraSPlusMergeProduct',				1),	// $num = 16
							'usentascover'			=> array('PDFInfraSPlusUseNtAsCover',				1),	// $num = 17
							'showwvccchk'			=> array('PDFInfraSPlusShowWVCCchk',				1),	// $num = 18
							'hidepict'				=> array('PDFInfraSPlusHidePictchk',				1),	// $num = 19
							'refcol'				=> array('PDFInfraSPlusShowRefCol',					1),	// $num = 20
							'hidetimespent'			=> array('PDFInfraSPlusHidetimeSpentchk',			1),	// $num = 21
							'hidedesc'				=> array('PDFInfraSPlusHideDescchk',				1),	// $num = 22
							'hidedisc'				=> array('PDFInfraSPlusHideDiscchk',				1),	// $num = 23
							'hidecols'				=> array('PDFInfraSPlusHideColschk',				1),	// $num = 24
							'showpricebl'			=> array('PDFInfraSPlusShowPriceBLchk',				1),	// $num = 25
							'adrfact'				=> array('PDFInfraSPlusParamAdrFact',				1),	// $num = 26
							'showtotdisc'			=> array('InfraSPlusShowTotDiscChk',				!getDolGlobalInt('INFRASPLUS_PDF_SHOW_DISCOUNT_TOT', 0)),	// $num = 27
							'showtvabtp'			=> array('InfraSPlusShowTVAtxtBTPChk',				1),	// $num = 28
							'showtot'				=> array('InfraSPlusShowTotChk',					1),	// $num = 29
							'showvir'				=> array('InfraSPlusParamNoIBAN',					1),	// $num = 30
							'showpayspec'			=> array('InfraSPlusShowPaySpecChk',				1),	// $num = 31
							'showPropalSignEmet'	=> array('InfraSPlusShowPropalSignEmetChk',			1)	// $num = 32
							);
	if (preg_match('/update_(.*)/', $action, $reg)) {
		foreach ($listOptions as $option => $transKey) {
			$constname	= 'INFRASPLUS_PDF_OPTION_'.$option;
			$result		= dolibarr_set_const($db, $constname, GETPOST($constname), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			if ($option == 'adrSst') {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_OPTION_Sst', GETPOST($constname), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
			if ($option == 'adrlivrfour') {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_OPTION_typeadr', GETPOST($constname), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
			if ($option == 'usentascover') {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_OPTION_showntusedascover', GETPOST($constname), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
		}
	}
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), array(), 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), array(), 'errors');
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
	print '		<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
	}
	print '			<div class = "NOfoldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamGenerationSetup').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title');
	print '				<table name = "tblGen" class = "noborder NOtoggle_bloc centpercent">';
	$metas	= array('30px', '*', '150px', '150px', '150px', '150px', '150px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1, 1, 1, 1 ,1), 'NumberingShort', 'Description', 'InfraSPlusParamBkpPerUser', 'InfraSPlusParamBkpPerDocument', 'InfraSPlusParamBkpPerType', 'InfraSPlusParamBkpPerCustomer', 'InfraSPlusParamBkpNone', '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Gen', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave').'<br/><span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamAvertissementCalculImage'), 7);
		foreach ($listOptions as $option => $transKey) {
			if (!empty($transKey[1])) {
				$confkey		= 'INFRASPLUS_PDF_OPTION_'.$option;
				$option_value	= getDolGlobalString($confkey, 'none');
				print '			<tr class = "oddeven">
									<td class = "center bold">'.$num.'</td>
									<td>'.$langs->trans($transKey[0]).'</td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "user"'.($option_value == 'user' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "doc"'.($option_value == 'doc' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "type"'.($option_value == 'type' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "cust"'.($option_value == 'cust' ? ' checked' : '').'/></td>
									<td class = "center"><input type = "radio" name = "'.$confkey.'" value = "none"'.($option_value == 'none' ? ' checked' : '').'/></td>
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
