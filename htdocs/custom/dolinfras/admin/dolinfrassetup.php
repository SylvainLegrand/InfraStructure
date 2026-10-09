<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./dolinfras/admin/dolinfrassetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module DolInfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/dolinfras/core/lib/dolinfrasAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'other', 'dolinfras@dolinfras'));

	// Access control *******************************
	$accessright	= !empty($user->admin) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}
	// Actions **************************************
	$form			= new Form($db);
	$formadmin		= new FormAdmin($db);
	$action			= GETPOST('action', 'alpha');
	$result			= '';
	$ltsconstants	= dolinfras_get_lts_constants('dolinfras');
	$savedtypes		= array('input', 'number', 'textarea', 'select_language', 'select_featureslevel');
	// Application forcée de toutes les constantes du fichier data.sql
	if ($action == 'forceLTSParams') {
		$nbapplied	= dolinfras_force_lts_constants('dolinfras');
		if ($nbapplied >= 0) {
			setEventMessages($langs->trans('DolInfraSForceApplyDone', $nbapplied), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('DolInfraSForceApplyError'), null, 'errors');
		}
	}
	// On / Off management
	if (preg_match('/^(set|del)_([A-Za-z0-9_]+)$/', $action, $reg) && isset($ltsconstants[$reg[2]])) {
		$confkey	= $reg[2];
		$paramtype	= dolinfras_get_lts_param_type($confkey, $ltsconstants[$confkey]['value']);
		$value		= $reg[1] == 'del' ? '0' : ($paramtype == 'lts_value' ? $ltsconstants[$confkey]['value'] : '1');
		$result		= dolibarr_set_const($db, $confkey, $value, 'chaine', $ltsconstants[$confkey]['visible'], $ltsconstants[$confkey]['note'], $conf->entity);
	}
	// Update buttons management
	if (preg_match('/^update_(saas|options)$/', $action, $reg)) {
		$visible	= $reg[1] == 'saas' ? 0 : 1;
		foreach ($ltsconstants as $confkey => $constant) {
			if ($constant['visible'] != $visible) {
				continue;
			}
			$paramtype	= dolinfras_get_lts_param_type($confkey, $constant['value']);
			if (! in_array($paramtype, $savedtypes) || ! GETPOSTISSET($confkey)) {
				continue;
			}
			$value	= $paramtype == 'textarea' ? GETPOST($confkey, 'restricthtml') : GETPOST($confkey, 'alphanohtml');
			if ($paramtype == 'select_language' && $value === '0') {	// valeur vide de la liste des langues
				$value	= '';
			}
			$result	= dolibarr_set_const($db, $confkey, $value, 'chaine', $constant['visible'], $constant['note'], $conf->entity);
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
	$featureslevels	= array(
							-1	=> $langs->trans('DolInfraSFeaturesLevelDeprecated'),
							0	=> $langs->trans('DolInfraSFeaturesLevelStable'),
							1	=> $langs->trans('DolInfraSFeaturesLevelExperimental'),
							2	=> $langs->trans('DolInfraSFeaturesLevelDevelopment'),
							);
	$ltsgroups		= array(
							'saas'		=> array('visible' => 0, 'title' => 'DolInfraSTitleSaaSParams'),
							'options'	=> array('visible' => 1, 'title' => 'DolInfraSTitleDoliOptions'),
							);

	// View *****************************************
	$page_name		= $langs->trans('DolInfraSSetupPages').' - '.$langs->trans('DolInfraSParams');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head			= dolinfras_admin_prepare_head();
	$picto			= 'dolinfras@dolinfras';
	print dol_get_fiche_head($head, 'dolinfrassetup', $langs->trans('modcomnameDolinfraS'), 0, $picto);

		// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "dolinfras_tblPSexp";
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
								$(".dolinfrasScrollUp").css("right", "30px");
							} else {
								$(".dolinfrasScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}

	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Options de gestion des paramètres LTS
	$num	= 1;
	print dolinfras_load_title('<span class = "dolinfrastitleparam">'.$langs->trans('DolInfraSTitleLTS').'</span>', $titleoption, dol_buildpath('/dolinfras/img/option_tool.png', 1), 1, '', '', '');
	print '	<table name = "tblLTS" class = "dolinfrasnoborder" width = "100%">';
	$metas	= array('30px', '*', '240px');
	dolinfras_print_colgroup($metas);
	$metas	= array(array(1, 1, 1), 'NumberingShort', 'Description', 'Action');
	dolinfras_print_liste_titre($metas);
	$num	= dolinfras_print_btn_action('forceLTSParams', $langs->trans('DolInfraSParamForceApply'), 1, 'left', 'DolInfraSForceApplyBtn', true, $num);
	dolinfras_print_final(3);
	print '	</table>';
	// Constantes du fichier data.sql : un paramètre par constante
	foreach ($ltsgroups as $groupkey => $group) {
		print '		<div class = "foldable">';
		$suffix	= '		<span class = "dolinfrasCaution">'.($group['title'] == 'DolInfraSTitleSaaSParams' ? ' ('.$langs->trans('DolInfraSTitleConstCached').')' : ' ('.$langs->trans('DolInfraSTitleVisibleInDivers', '<a href = "https://dolinfras.infras.fr/admin/const.php?mainmenu=home">'.$langs->trans('DolInfraSTitleLinkInDivers').'</a>').')').'</span>';
		print dolinfras_load_title('<span class = "dolinfrastitleparam">'.$langs->trans($group['title']).$suffix.'</span>', $titleoption, dol_buildpath('/dolinfras/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer', '');
		print '				<table name = "tbl'.$groupkey.'" class = "noborder toggle_bloc" width = "100%">';
		$metas	= array('30px', '*', '90px', '120px', '90px');
		dolinfras_print_colgroup($metas);
		$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		dolinfras_print_liste_titre($metas);
		$num	= 1;
		dolinfras_print_btn_action('update_'.$groupkey, '<span class = "dolinfrasCaution">'.$langs->trans('DolInfraSParamCaution').'</span> '.$langs->trans('DolInfraSParamCautionSave'), 4);
		foreach ($ltsconstants as $confkey => $constant) {
			if ($constant['visible'] != $group['visible']) {
				continue;
			}
			$paramtype	= dolinfras_get_lts_param_type($confkey, $constant['value']);
			$desc		= $langs->trans('DolInfraSConst'.$confkey);
			if ($paramtype == 'on_off' || $paramtype == 'lts_value') {
				$num	= dolinfras_print_input($confkey, 'select', $desc, '', dolinfras_lts_onoff_link($confkey, $paramtype == 'lts_value' ? $constant['value'] : '1'), 2, 1, '', $num);
			} elseif ($paramtype == 'number') {
				$num	= dolinfras_print_input($confkey, 'input', $desc, '', array('type' => 'number', 'min' => '0', 'class' => 'flat center quatrevingtpercent dolinfrasnopadding'), 2, 1, '', $num);
			} elseif ($paramtype == 'textarea') {
				$num	= dolinfras_print_input($confkey, 'textarea', $desc, '', array(), 2, 1, '', $num);
			} elseif ($paramtype == 'select_language') {
				$num	= dolinfras_print_input($confkey, 'select', $desc, '', $formadmin->select_language(getDolGlobalString($confkey, ''), $confkey, 0, array(), 1, 0, 0, 'minwidth200', 1, 0, 0, array(), 0), 2, 1, '', $num);
			} elseif ($paramtype == 'select_featureslevel') {
				$num	= dolinfras_print_input($confkey, 'select', $desc, '', $form->selectarray($confkey, $featureslevels, getDolGlobalInt($confkey, 0), 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1, '', 0, 0), 2, 1, '', $num);
			} else {
				$num	= dolinfras_print_input($confkey, 'input', $desc, '', array(), 2, 1, '', $num);
			}
		}
		dolinfras_print_final(5);
		print '				</table>';
		print '		</div>';
	}
	print '	</form>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
