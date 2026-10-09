<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasworkflow/admin/extrafields.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup extrafields for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infrasworkflow/core/lib/infrasworkflowAdmin.lib.php');
	dol_include_once('/infrasworkflow/core/lib/infrasworkflow.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors','other', 'sendings', 'infrasworkflow@infrasworkflow'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrasworkflow', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrasworkflow', 'paramExtraFields')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$extrafields			= new ExtraFields($db);
	$form					= new Form($db);
	$action					= GETPOST('action', 'aZ09');
	$attrname				= GETPOST('attrname', 'alpha');
	$selected_elementtype	= GETPOST('elementtype', 'alpha');
	$resparam				= 0;	// Variable to store the result of the actions
	// Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$resparam	= infrasworkflow_bkup_module ('infrasworkflow');
	}
	if ($action == 'restoreParams') {
		$resparam	= infrasworkflow_restore_module ('infrasworkflow');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$resparam	= dolibarr_set_const($db, $confkey, GETPOSTINT('value'), 'integer', 0, 'InfraSWorkflow module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('EXF'	=> array('INFRASWORKFLOW_EXF_LIST_TYPE'));
		$confkey	= $reg[1];
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			// écriture de la constante
			$resparam	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
		}
	}
	// Retour => message Ok ou Ko
	if ($resparam == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($resparam == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************
	$typeslistexf			= array(1 => $langs->trans('InfraSWorkflowParamExfListType1'),
									2 => $langs->trans('InfraSWorkflowParamExfListType2'),
									3 => $langs->trans('InfraSWorkflowParamExfListType3'),
									4 => $langs->trans('InfraSWorkflowParamExfListType4')
									);
	$selectedtypelistexf	= getDolGlobalInt('INFRASWORKFLOW_EXF_LIST_TYPE', 1);
	// Liste des formats supportés
	$tmptype2label			= ExtraFields::$type2label;
	$type2label				= array('');
	foreach ($tmptype2label as $key => $val) {
		$type2label[$key]	= $langs->transnoentitiesnoconv($val);
	}
	$elementtypes	= array();
	parse_str(getDolGlobalString('INFRASWORKFLOW_LISTTYPEKEY', ''), $elementtypes);
	$isV20p			= version_compare(DOL_VERSION, '20.0.0') >= 0;
	if ($isV20p) {
		// If Dolibarr version is lower than 20, we do not show the test for compatibility with v20+
		dolibarr_set_const($db, 'INFRASWORKFLOW_TEST_EXF_V20PLUS', "1", 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
	}
	// Action scan extrafields
	if ($action == 'audit_extrafields') {
		$audit_result = infrasworkflow_audit_extrafields(false);
	}

	// Action nettoyage extrafields
	if ($action == 'clean_extrafields_orphelins') {
		$audit_result = infrasworkflow_audit_extrafields(true, 'orphelins');
		setEventMessages($langs->trans('InfraSWorkflowExfCleaned'), null, 'mesgs');
		// Relance le scan
		$audit_result = infrasworkflow_audit_extrafields(false);
	}
	if ($action == 'clean_extrafields_disabled') {
		$audit_result = infrasworkflow_audit_extrafields(true, 'external_disabled');
		setEventMessages($langs->trans('InfraSWorkflowExfCleaned'), null, 'mesgs');
		// Relance le scan
		$audit_result = infrasworkflow_audit_extrafields(false);
	}
	if ($action == 'clean_extrafields_deleted') {
		$audit_result = infrasworkflow_audit_extrafields(true, 'external_deleted');
		setEventMessages($langs->trans('InfraSWorkflowExfCleaned'), null, 'mesgs');
		// Relance le scan
		$audit_result = infrasworkflow_audit_extrafields(false);
	}
	// View *****************************************
	$page_name		= $langs->trans('Module500080Name').' - '.$langs->trans('InfraSWorkflowParamsExtrafields');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head			= infrasworkflow_admin_prepare_head();
	$picto			= 'infrasworkflow@infrasworkflow';
	print dol_get_fiche_head($head, 'extrafields', $langs->trans('modcomnameInfrasworkflow'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "infrasworkflow_exf_tblPSexp";
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar) {
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie(cookieName))) {
							tblPSexp = $.cookie(cookieName);
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
							$.cookie(cookieName, "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie(cookieName, $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrasworkflowScrollUp").css("right", "30px");
							} else {
								$(".infrasworkflowScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">
				<input type="hidden" name="action" value="scan_extrafields">
				<div>';
	print infrasworkflow_load_title('<span class = "infrasworkflowtitleparam">'.$langs->trans('InfraSWorkflowParamTitle').'</span>', '', dol_buildpath('/infrasworkflow/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblAS" class = "noborder" width = "100%">';
	$metas	= array('30px', '*', '90px', '156px');
	infrasworkflow_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrasworkflow_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infrasworkflow_print_btn_action('EXF', '<span class = "infrasworkflowcaution">'.$langs->trans('InfraSWorkflowCaution').'</span> '.$langs->trans(key: 'InfraSWorkflowParamCautionSave'), 4);
		$metas	= $form->selectarray('INFRASWORKFLOW_EXF_LIST_TYPE', $typeslistexf, $selectedtypelistexf, 0, 0, 0, '', 0, 0, 0, '', 'width100', true);
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_EXF_LIST_TYPE', 'select', $langs->trans('InfraSWorkflowParamExfListType'), '', $metas, 2, 1, '', $num);
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_TEST_EXF_V20PLUS','on_off',$langs->trans('InfraSWorkflowParamTestExfV20plus'),'',[],2, 1, '', $num);
		$metas	= '<button class = "button infrasworkflowidth110" type = "submit" value = "audit_extrafields" name = "action">'.$langs->trans('InfraSWorkflowScanExtrafields').'</button>';
		$num	= infrasworkflow_print_input('', 'select', $langs->trans('InfraSWorkflowSAuditExtraFields'), '', $metas, 2, 1, '', $num);
		// Affichage du résultat du scan
		if (!empty($audit_result)) {
			$has_to_clean			= count($audit_result['orphelins']);
			$has_to_clean_disabled	= count($audit_result['external_disabled']);
			$has_to_clean_deleted	= count($audit_result['external_deleted']);
			$has_internal_disabled	= count($audit_result['internal_disabled']);

			print '<div class = "'.($has_to_clean ?'error' : 'info').'">';
				if ($has_to_clean) {
					print '<b class = "infrasworkflowslogan">'.$langs->trans('InfraSWorkflowExfToCleanOrph').'</b><br>';
					foreach (array_merge($audit_result['orphelins']) as $exf) {
						print '- <b class ="infrasworkflowtitleparam">'.$exf['elementtype'].'</b> : <b>'.$exf['name'].'</b> ['.$exf['label'].']<br>';
					}
					// Bouton pour Nettoyer les orphelins
					$metas = '<button class = "button infrasworkflowidth110" type="submit" name="action" value="clean_extrafields_orphelins">'.$langs->trans('InfraSWorkflowCleanExtrafields').'</button>';
					$num = infrasworkflow_print_input('', 'select', $langs->trans('InfraSWorkflowCleanExtrafieldsText'), '', $metas, 2, 1, '', $num);
				} else {
					print $langs->trans('InfraSWorkflowExfCleanDB');
				}
			print '	</div>
					<br/>';
			print '	<div class = "'.($has_to_clean_disabled ?'warning' : 'info').'">';
				if ($has_to_clean_disabled) {
					print '<b class = "infrasworkflowslogan">'.$langs->trans('InfraSWorkflowExfToCleandisbl').'</b><br>';
					foreach (array_merge($audit_result['external_disabled']) as $exf) {
						print '- <b class ="infrasworkflowtitleparam">'.$exf['elementtype'].'</b> : <b>'.$exf['name'].'</b> ['.$exf['label'].']<br>';
					}
					// Bouton pour Nettoyer les Desactivés
					$metas = '<button class = "button infrasworkflowidth110" type="submit" name="action" value="clean_extrafields_disabled">'.$langs->trans('InfraSWorkflowCleanExtrafieldsDisable').'</button>';
					$num = infrasworkflow_print_input('', 'select', $langs->trans('InfraSWorkflowCleanExtrafieldsTextDisbl'), '', $metas, 2, 1, '', $num);
				} else {
					print $langs->trans('InfraSWorkflowExfCleanDBdisbl');
				}
			print '	</div>
					<br/>';
			print '	<div class = "'.($has_to_clean_deleted ?'warning' : 'info').'">';
				if ($has_to_clean_deleted) {
					print '<b class = "infrasworkflowslogan">'.$langs->trans('InfraSWorkflowExfToCleanDelete').'</b><br>';
					foreach (array_merge($audit_result['external_deleted']) as $exf) {
						print '- <b class ="infrasworkflowtitleparam">'.$exf['elementtype'].'</b> : <b>'.$exf['name'].'</b> ['.$exf['label'].']<br>';
					}
					// Bouton pour Nettoyer les Supprimés
					$end_clean = '<td class = "center"><button class = "button infrasworkflowidth110" type="submit" name="action" value="clean_extrafields_deleted">'.$langs->trans('InfraSWorkflowCleanExtrafieldsDelete').'</button></td>';
					$num = infrasworkflow_print_input('', '', $langs->trans('InfraSWorkflowCleanExtrafieldsTextDelete'), '', array(), 1, 2, $end_clean, $num);
				} else {
					print $langs->trans('InfraSWorkflowExfCleanDBdelete');
				}
			print '	</div>
					<br/>';
			if ($has_internal_disabled) {
				print '	<div class = "'.($has_internal_disabled ?'warning' : 'info').'">';
				print '<br><span class = "warning infrasworkflowslogan">'.$langs->trans('InfraSWorkflowExfInternalDisabled').'</span><br>';
				foreach ($audit_result['internal_disabled'] as $exf) {
					print '- <b class ="infrasworkflowtitleparam">'.$exf['elementtype'].'</b> : <b>'.$exf['name'].'</b> ['.$exf['label'].']<br>';
				}
				print '	</div>
						<br/>';
			}
		}
	}
	print '			</table>
				</div>
			</form>';
	if (!empty($accessright)) {
		$hookmanager->initHooks(array('infrasworkflowadminextrafields'));
		foreach ($elementtypes as $typekey => $modkey) {
			$num++;
			$elementtype	= $typekey;
			if (!isModEnabled($modkey)) {
				continue;
			}
			// Load $extrafields->attributes
			$extrafields->fetch_name_optionals_label($elementtype);
			$nb					= 0;	// nombre d'attributs affichés
			$errV20Plus			= 0;	// Variable pour stocker les erreurs de compatibilité des attributs supplémentaires pour Dolibarr v20+
			if (isset($extrafields->attributes[$elementtype]['type']) && is_array($extrafields->attributes[$elementtype]['type']) && count($extrafields->attributes[$elementtype]['type'])) {
				foreach ($extrafields->attributes[$elementtype]['type'] as $key => $value) {
					// We filter the list by visibility (if $selectedtypelistexf is set to 1, we show all, if set to 2, we show only visible attributes, if set to 3, we show only hidden attributes, if set to 4, we show only inactive attributes)
					if (!empty($selectedtypelistexf) && $selectedtypelistexf == 1) {
						// Show all attributes
					} elseif (!empty($selectedtypelistexf) && $selectedtypelistexf == 2 && $extrafields->attributes[$elementtype]['list'][$key] == 0) {
						// Show only visible attributes
						continue;
					} elseif (!empty($selectedtypelistexf) && $selectedtypelistexf == 3 && $extrafields->attributes[$elementtype]['list'][$key] != 0) {
						// Show only hidden attributes
						continue;
					} elseif (!empty($selectedtypelistexf) && $selectedtypelistexf == 4 && $extrafields->attributes[$elementtype]['enabled'][$key] != 0) {
						// Show only inactive attributes
						continue;
					}
					// Load language if required
					if (!empty($extrafields->attributes[$elementtype]['langfile'][$key])) {
						$langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
					}
					$nb++;
					$test = '';
					if (getDolGlobalInt('INFRASWORKFLOW_TEST_EXF_V20PLUS', 0) && $extrafields->attributes[$elementtype]['type'][$key] == 'sellist' && !empty($extrafields->attributes[$elementtype]['param'][$key])) {
						$param = $extrafields->attributes[$elementtype]['param'][$key];
						if (is_array($param['options'])) {
							$errstr			= '';
							$param_list		= array_keys($param['options']);
							$InfoFieldList	= explode(':', $param_list[0], 5);
							$test			= forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1, 0, 1);
							if ($test == '1 = 2') {
								$errV20Plus++;
							}
						}
					}
				}
			}
			print '	<div class = "foldable">';
			print infrasworkflow_load_title('<span class = "infrasworkflowtitleparam">'.$langs->trans('InfraSWorkflowExfTitle').' '.$langs->trans('InfraSWorkflowExf_'.$typekey).' ('.$nb.')'.($errV20Plus ? ' - <span class = "infrasworkflowcaution">('.$langs->trans('InfraSWorkflowParamErrExfV20plus', $errV20Plus).')</span>' : '').'</span>', $titleoption, dol_buildpath('/infrasworkflow/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
			print '		<table name = "tbl_'.$typekey.'" class = "infrasworkflownoborder toggle_bloc centpercent">';
			print '			<tr>';
			print '				<td>';
			// Vue liste des extrafields
			$parameters	= array('elementtype' => $typekey, 'nb' => $nb, 'errV20Plus' => $errV20Plus);
			$reshook	= $hookmanager->executeHooks('formAdminExtrafieldsList', $parameters, $extrafields, $action);
			if ($reshook <= 0) {
				include dol_buildpath('/infrasworkflow/core/tpl/admin_extrafields_view.tpl.php');
			}
			// Actions
			if ($selected_elementtype === $typekey) {
				$reshook	= $hookmanager->executeHooks('formAdminExtrafieldsActions', $parameters, $extrafields, $action);
				if ($reshook <= 0) {
					include dol_buildpath('/infrasworkflow/core/tpl/actions_extrafields.inc.php');
				}
				if ($action == 'create') {	// Création
					print '<br/>';
					print load_fiche_titre($langs->trans('NewAttribute'), '', 'generic', 0, '', '');
					$reshook	= $hookmanager->executeHooks('formAdminExtrafieldsCreate', $parameters, $extrafields, $action);
					if ($reshook <= 0) {
						include dol_buildpath('/infrasworkflow/core/tpl/admin_extrafields_add.tpl.php');
					}
				}
				if ($action == 'edit' && !empty($attrname)) {	// Édition
					print '<br/>';
					print load_fiche_titre($langs->trans('FieldEdition', $attrname));
					$reshook	= $hookmanager->executeHooks('formAdminExtrafieldsEdit', $parameters, $extrafields, $action);
					if ($reshook <= 0) {
						include dol_buildpath('/infrasworkflow/core/tpl/admin_extrafields_edit.tpl.php');
					}
				}
			}
			print '				</td>';
			print '			</tr>';
			print '		</table>';
			print '	</div>
					<a class = "infrasworkflowScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
		}
		// $num = 61
	}
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
