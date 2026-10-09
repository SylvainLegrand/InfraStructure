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
	* 	\file		../infrasproject/admin/generalproject.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraSProject
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infrasproject/core/lib/infrasprojectAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'sendings', 'stocks', 'projects', 'mrp', 'loan', 'donations', 'compta', 'banks','other', 'trips', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'errors', 'infrasproject@infrasproject'));
	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrasproject', 'paramDolibarr')) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formother		= new FormOther($db);
	$formcompany	= new FormCompany($db);
	$confirm_mesg	= '';
	$action			= GETPOST('action','alpha');
	$confirm		= GETPOST('confirm', 'alpha');
	$result			= '';
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		if (preg_match('/^(PROJECT_|TIMESPENT_)/', $confkey)) {
			$result		= dolibarr_set_const($db, $confkey, GETPOST('value', 'alphanohtml'), 'chaine', 0, 'InfraSProject module', $conf->entity);
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Gen'	=> array('PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY', 'PROJECT_OPEN_ALWAYS_ON_TAB', 'PROJECT_ELEMENTS_FOR_MINUS_MARGIN', 'PROJECT_ELEMENTS_FOR_PLUS_MARGIN'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			if (in_array($constname, array('PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY', 'PROJECT_ELEMENTS_FOR_MINUS_MARGIN', 'PROJECT_ELEMENTS_FOR_PLUS_MARGIN'))) {
				$constvalue	= implode(',', GETPOST($constname, 'array'));
			} else {
				$constvalue	= GETPOST($constname, 'alpha');
			}
			$result	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSProject module', $conf->entity);
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
	$OpenTab				= getDolGlobalString('PROJECT_OPEN_ALWAYS_ON_TAB', '');
	$selectedThirdparty		= explode(',', getDolGlobalString('PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY', ''));
	$thirdpartieslist		= $form->select_thirdparty_list($selectedThirdparty, 'thirdpartieslist', '', '1', 0, 0, array(), '', 1, 0, '', '', false, array(), 0);
	$thirdpartieslist		= infrasproject_replaceKeyArray($thirdpartieslist, 'key', 'value');
	$listofreferent			= infrasproject_getListOfReferent();
	foreach ($listofreferent as $key => $val) {
		$qualified	= $val['testparam'];
		if ($qualified) {
			$listofreferent[$key]	= $langs->trans($val['name']);
		} else {
			unset($listofreferent[$key]);
		}
	}
	$selectedMargin			= explode(',', getDolGlobalString('PROJECT_ELEMENTS_FOR_MINUS_MARGIN', ''));
	$selectedMarginPlus		= explode(',', getDolGlobalString('PROJECT_ELEMENTS_FOR_PLUS_MARGIN', ''));

	// Comportement général
	// View *****************************************
	$page_name				= $langs->trans('infrasproject') .' - '. $langs->trans('InfraSProjectParamsGeneral');
	llxHeader('', $page_name);
	$linkback				= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption			= '';

	// Configuration header *************************
	$head					= infrasproject_admin_prepare_head();
	$picto					= 'infrasproject@infrasproject';
	print dol_get_fiche_head($head, 'generalproject', $langs->trans('modcomnameInfraSProject'), 0, $picto);

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
	// Comportement général
	print '		<div class = "foldable">';
	print infrasproject_load_title('<span class = "infrasprojecttitleparam">'.$langs->trans('InfraSProjectParamsGeneral').'</span>', $titleoption, dol_buildpath('/infrasproject/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblAG" class = "infrasprojectnoborder toggle_bloc" width = "100%">';
	$metas	= array('30px', '*', '200px', '200px', '120px');
	infrasproject_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrasproject_print_liste_titre($metas);
	// Paramètres Dolibarr natif
	if (!empty($accessright)) {
		$num	= 1;
		infrasproject_print_btn_action('Gen', '<span class = "infrasprojectcaution">'.$langs->trans('InfraSProjectCaution').'</span> '.$langs->trans('InfraSProjectCautionSave'), 4, 'center', 'Modify', false);
		$metas 	= $form->multiselectarray('PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY', $thirdpartieslist, $selectedThirdparty, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrasproject_print_input('', 'select', $langs->trans('ProjectAllowLinkFromOtherCompany'), '', $metas, 1, 2, '', $num);
		$num	= infrasproject_print_input('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS', 'on_off', $langs->trans('ProjectCanAlwaysLinkToAllSuppliers'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_ALLOW_COMMENT_ON_PROJECT', 'on_off', $langs->trans('ProjectAllowCommentOnProject'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_ALLOW_COMMENT_ON_TASK', 'on_off', $langs->trans('ProjectAllowCommentOnTask'), '', array(), 2, 1, '', $num);
		// $num = 5
		$num	= infrasproject_print_input('PROJECT_ALWAYS_DISCARD_CLOSED_PROJECTS_IN_SELECT', 'on_off', $langs->trans('ProjectAllowDiscardClosedProjectsSelect'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_CREATE_ON_OVERVIEW_DISABLED', 'on_off', $langs->trans('ProjectDisabledCreateOnOverview'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_DISABLE_UNLINK_FROM_OVERVIEW', 'on_off', $langs->trans('ProjectDisabledUnlinkFromOverview'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_HIDE_UNSELECTABLES', 'on_off', $langs->trans('ProjectHideUnselectables'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_HIDE_TASKS', 'on_off', $langs->trans('ProjectHideTasks'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_LIST_SHOW_STARTDATE', 'on_off', $langs->trans('ProjectListShowStartDate'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_LINK_ON_OVERWIEW_DISABLED', 'on_off', $langs->trans('ProjectLinkOnOverviewDisabled'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_TIME_ON_ALL_TASKS_MY_PROJECTSTIME_ON_ALL_TASKS_MY_PROJECTS', 'on_off', $langs->trans('ProjectCanAddTimeSpentOnTask'), '', array(), 2, 1, '', $num);
		// $num = 13
		$array_to_check	= array('preview' => $langs->trans('ProjectOverview'), 'task' => $langs->trans('Tasks'));
		if (isModEnabled('eventorganization')) {
			$array_to_check['eventorganization'] = $langs->trans('EventOrganization');
		}
		$metas	= Form::selectarray('PROJECT_OPEN_ALWAYS_ON_TAB', $array_to_check, $OpenTab);
		$num	= infrasproject_print_input('PROJECT_OPEN_ALWAYS_ON_TAB', 'select', $langs->trans('ProjectOpenAlwaysOnTab'), '', $metas, 2, 1, '', $num);
		$metas	= $form->multiselectarray('PROJECT_ELEMENTS_FOR_PLUS_MARGIN', $listofreferent, $selectedMarginPlus, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrasproject_print_input('', 'select', $langs->trans('ProjectElementsForPlusMargin'), '', $metas, 1, 2, '', $num);
		$metas	= $form->multiselectarray('PROJECT_ELEMENTS_FOR_MINUS_MARGIN', $listofreferent, $selectedMargin, 0, 0, 'centpercent', 0, 0, '', '', '');
		$num	= infrasproject_print_input('', 'select', $langs->trans('ProjectElementsForMinusMargin'), '', $metas, 1, 2, '', $num);
		$num	= infrasproject_print_input('TIMESPENT_ALWAYS_UPDATE_THM', 'on_off', $langs->trans('ProjectTimeSpentAlwaysUpdateTHM'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_CREATE_NO_DRAFT', 'on_off', $langs->trans('ProjectCreateNoDraft'), '', array(), 2, 1, '', $num);
		$num	= infrasproject_print_input('PROJECT_ENABLE_SUB_PROJECT', 'on_off', $langs->trans('ProjectEnabledSubProject'), '', array(), 2, 1, '', $num);
		// $num = 19
	}
	print '			</table>';
	print '		</div>';
	print '	</form>
			<a class = "infrasprojectScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
