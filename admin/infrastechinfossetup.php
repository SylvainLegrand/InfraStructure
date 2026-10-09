<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <http://www.infras.fr>
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
	* 	\file		../infrastechinfos/admin/infrastechinfos.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrastechinfos/core/lib/infrastechinfosAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrastechinfos@infrastechinfos'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrastechinfos', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrastechinfos', 'InfraSTechInfosParamSpecif')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$confirm_mesg	= '';
	$action			= GETPOST('action','alpha');
	$confirm		= GETPOST('confirm', 'alpha');
	$result			= '';
	//Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infrastechinfos_bkup_module ('infrastechinfos');
	}
	if ($action == 'restoreParams') {
		$result	= infrastechinfos_restore_module ('infrastechinfos');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOSTINT('value'), 'chaine', 0, 'InfraSTechInfos module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Gen'	=> array('MAIN_DURATION_OF_WORKDAY', 'INFRASTECHINFOS_DURATION_OF_WORKWEEK')
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSTechInfos module', $conf->entity);
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
	$duration_workday	= getDolGlobalInt('MAIN_DURATION_OF_WORKDAY', 28800);	// in seconds
	$duration_workday	= $duration_workday	/ 3600;	// in hours

	// View *****************************************
	$page_name			= $langs->trans('InfraSTechInfos').' - '.$langs->trans('InfraSTechInfosSetup');
	llxHeader('', $page_name);	// browser tab
	echo $confirm_mesg;
	$linkback			= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption		= '';

	// Configuration header *************************
	$head				= infrastechinfos_admin_prepare_head();
	$picto				= 'infrastechinfos@infrastechinfos';
	print dol_get_fiche_head($head, 'infrastechinfossetup', $langs->trans('InfraSTechInfos'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "infrastechinfos_tblPSexp";
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
								$(".infrastechinfosScrollUp").css("right", "30px");
							} else {
								$(".infrastechinfosScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infrastechinfos_print_backup_restore();
	print '		<div class = "foldable">';
	print infrastechinfos_load_title('<span class = "infrastitleparam">'.$langs->trans('InfraSTechInfosParamTitleComp').'</span>', $titleoption, dol_buildpath('/infrastechinfos/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblGen" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infrastechinfos_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrastechinfos_print_liste_titre($metas);
	if (! empty($accessright)) {
		$num	= 2;
		infrastechinfos_print_btn_action('Gen', $langs->trans('InfraSTechInfosExpenseParamCautionSave'), 4);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '3600', 'max' => '86400', 'step' => '3600');
		$desc	= $langs->trans('InfraSTechInfosDurationOfWorkDay1').' <span class = "infrastechinfoscaution">'.$langs->trans('InfraSTechInfosCaution').' </span>'.$langs->trans('InfraSTechInfosDurationOfWorkDay2', $duration_workday);
		$num	= infrastechinfos_print_input('MAIN_DURATION_OF_WORKDAY', 'input',	$desc, '', $metas, 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '4', 'max' => '7');
		$num	= infrastechinfos_print_input('INFRASTECHINFOS_DURATION_OF_WORKWEEK', 'input', $langs->trans('InfraSTechInfosDurationNbDaysPerWeek'), '', $metas, 2, 1, '', $num);
		$num	= infrastechinfos_print_input('INFRASTECHINFOS_TOTAL_TIME_IN_DAYS', 'on_off', $langs->trans('InfraSTechInfosTotalTimeInDays'), '', array(), 2, 1, '', $num);
		$num	= infrastechinfos_print_input('INFRASTECHINFOS_ONLY_TOTAL_TIME', 'on_off', $langs->trans('InfraSTechInfosOnlyTotalTime'), '', array(), 2, 1, '', $num);
		infrastechinfos_print_final(4);
		// $num = 6
	}
	print '			</table>';
	print '		</div>';
	print '	</form>
			<a class = "infrastechinfosScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
