<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasfiles/admin/infrasfilessetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraSFiles
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');
	dol_include_once('/infrasfiles/core/lib/infrasfilesAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'other', 'infrasfiles@infrasfiles'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrasfiles', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrasfiles', 'paramInfraSFiles')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form		= new Form($db);
	$action		= GETPOST('action', 'aZ09');
	$element	= GETPOST('element', 'aZ09');
	$value		= GETPOST('value', 'alpha');
	$result		= '';
	$registry	= infrasfiles_get_registry();
	// Sauvegarde / Restauration
	if ($action == 'bkupParams' && $accessright == 2) {
		$result	= infrasfiles_bkup_module('infrasfiles');
	}
	if ($action == 'restoreParams' && $accessright == 2) {
		$result	= infrasfiles_restore_module('infrasfiles');
	}
	// On / Off management (the link around the switch reloads the page so that the dependent options follow)
	if (preg_match('/^set_(INFRASFILES_[A-Z0-9_]+)$/', $action, $reg)) {
		$result	= dolibarr_set_const($db, $reg[1], GETPOSTINT('value'), 'chaine', 0, 'InfraSFiles module', $conf->entity);
	}
	// Object options (saved with the "Modify" button)
	if ($action == 'update_Options') {
		foreach ($registry as $key => $definition) {
			if (!infrasfiles_is_enabled($key) || empty($definition['options'])) {
				continue;
			}
			foreach ($definition['options'] as $suffix => $option) {
				$constname	= infrasfiles_const_name($key, $suffix);
				if (!GETPOSTISSET($constname)) {
					continue;
				}
				if ($option['type'] == 'on_off') {
					continue;	// saved by its own switch (action set_<CONST>)
				} elseif ($option['type'] == 'textarea') {
					$optvalue	= GETPOST($constname, 'restricthtml');
				} elseif ($option['type'] == 'select') {
					$optvalue	= GETPOST($constname, 'alpha');
					if (!array_key_exists($optvalue, infrasfiles_get_option_values($option))) {
						continue;	// value not in the allowed list
					}
				} else {
					$optvalue	= GETPOST($constname, 'alphanohtml');
				}
				$result		= dolibarr_set_const($db, $constname, $optvalue, 'chaine', 0, 'InfraSFiles module', $conf->entity);
			}
		}
	}
	// Document models : activation (set / del) and default model (setdoc) - same mechanism as native admin pages (llx_document_model)
	if (in_array($action, array('set', 'del', 'setdoc')) && !empty($registry[$element]) && !empty($value)) {
		$models		= infrasfiles_get_models($element);
		$constaddon	= infrasfiles_const_name($element, 'ADDON_PDF');
		$doctype	= $registry[$element]['docpart'];	// type in llx_document_model, read by ModelePDF<Docpart>::liste_modeles()
		if (array_key_exists($value, $models)) {
			if ($action == 'set') {
				if (!infrasfiles_document_model_is_active($doctype, $value)) {
					addDocumentModel($value, $doctype, '', '');
				}
			} elseif ($action == 'del') {
				delDocumentModel($value, $doctype);
				if (getDolGlobalString($constaddon) == $value) {
					dolibarr_del_const($db, $constaddon, $conf->entity);
				}
			} elseif ($action == 'setdoc') {
				dolibarr_set_const($db, $constaddon, $value, 'chaine', 0, 'InfraSFiles module', $conf->entity);
				if (!infrasfiles_document_model_is_active($doctype, $value)) {
					addDocumentModel($value, $doctype, '', '');	// the default model must be active
				}
			}
			// Redirect (POST-redirect-GET pattern) : the action is not in the URL any more, a reload of the page cannot replay it
			header('Location: '.$_SERVER['PHP_SELF'].'?page_y='.GETPOSTINT('page_y'));
			exit;
		}
	}
	// Preview of a model : specimen object (no database record) written by the model, then the PDF is displayed
	if ($action == 'specimen' && !empty($registry[$element]) && !empty($value)) {
		$models		= infrasfiles_get_models($element);
		$specimen	= array_key_exists($value, $models) ? infrasfiles_load_specimen($element) : null;
		if (is_object($specimen)) {
			require_once $models[$value]['file'];
			$classname	= $models[$value]['classname'];
			$module		= new $classname($db);
			$units		= method_exists($specimen, 'infrasfilesGetUnits') ? $specimen->infrasfilesGetUnits() : array();
			$unit		= !empty($units) ? $units[0] : array('suffix' => '', 'label' => '');
			if ($module->write_file($specimen, $langs, '', 0, 0, 0, array('infrasfiles_unit' => $unit)) > 0) {
				header('Location: '.DOL_URL_ROOT.'/document.php?modulepart=infrasfiles&file='.urlencode(infrasfiles_get_subdir($element, null).'/SPECIMEN.pdf'));
				exit;
			}
			setEventMessages($module->error, $module->errors, 'errors');
		} else {
			setEventMessages($langs->trans('ErrorBadParameters'), null, 'errors');
		}
	}
	// Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// View *****************************************
	$page_name		= $langs->trans('InfraSFilesSetupPages').' - '.$langs->trans('InfraSFilesParams');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head			= infrasfiles_admin_prepare_head();
	$picto			= 'infrasfiles@infrasfiles';
	print dol_get_fiche_head($head, 'infrasfilessetup', $langs->trans('modcomnameFile'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script type = "text/javascript">
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrasfilesScrollUp").css("right", "30px");
							} else {
								$(".infrasfilesScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Sauvegarde / Restauration
	if ($accessright == 2) {
		infrasfiles_print_backup_restore();
	}
	// Documents pris en charge : un sous-titre par document, sa ligne d'activation, puis ses options en dessous quand il est activé
	$num	= 1;
	print '	<div class = "foldable">';
	print infrasfiles_load_title('<span class = "infrasfilesTitleparam">'.$langs->trans('InfraSFilesTitleObjects').'</span>', $titleoption, dol_buildpath('/infrasfiles/img/option_tool.png', 1), 1, '', '');
	print '	<table name = "tblObjects" class = "infrasfilesnoborder" width = "100%">';
	$metas	= array('30px', '*', '220px', '120px');
	infrasfiles_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), '#', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrasfiles_print_liste_titre($metas);
	infrasfiles_print_btn_action('Options', '<span class = "infrasfilescaution">'.$langs->trans('InfraSFilesCaution').'</span> '.$langs->trans('InfraSFilesParamCautionSave'), 3);
	foreach ($registry as $key => $definition) {
		$available		= infrasfiles_module_available($definition);
		$constEnabled	= infrasfiles_const_name($key, 'ENABLED');
		$picto			= img_picto('', $definition['picto'], 'class = "paddingright"');
		// One dark band per document (same rendering as the models section) so that documents are told apart at a glance
		print '	<tr class = "liste_titre infrasfilesobjecttitle"><td colspan = "3">'.$picto.$langs->trans($definition['label']).'</td></tr>';
		if (!$available) {
			print '	<tr class = "oddeven">
						<td class = "center bold">'.$num.'</td>
						<td colspan = "2" class = "opacitymedium">'.$langs->trans('InfraSFilesColEnabled').' - <span class = "infrasfilescaution">'.$langs->trans('InfraSFilesNeedModule', implode(', ', (array) $definition['needmodule'])).'</span></td>
					</tr>';
			$num++;
			continue;
		}
		$num	= infrasfiles_print_input($constEnabled, 'on_off', $langs->trans('InfraSFilesColEnabled'), '', array(), 1, 1, '', $num);
		if (!getDolGlobalInt($constEnabled, 0)) {
			continue;
		}
		$num	= infrasfiles_print_input(infrasfiles_const_name($key, 'DOCUMENT'), 'on_off', '&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('InfraSFilesColPdf'), '', array(), 1, 1, '', $num);
		$num	= infrasfiles_print_input(infrasfiles_const_name($key, 'EMAIL'), 'on_off', '&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('InfraSFilesColEmail'), '', array(), 1, 1, '', $num);
		foreach ((array) $definition['options'] as $suffix => $option) {
			$constname	= infrasfiles_const_name($key, $suffix);
			$label		= '&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans($option['label']);
			$help		= !empty($option['help']) ? $option['help'] : '';	// translation key of the tooltip shown with the help picto (standard InfraS lib)
			if ($option['type'] == 'on_off') {
				$num	= infrasfiles_print_input($constname, 'on_off', $label, $help, array(), 1, 1, '', $num);
			} elseif ($option['type'] == 'select') {
				// Values : static list ('values', translation keys) or computed by a function ('values_callback')
				$metas	= $form->selectarray($constname, infrasfiles_get_option_values($option), infrasfiles_get_option($key, $suffix), 0, 0, 0, 'class = "infrasfileswidthquatrevingtdixpercent infrasfilesnopadding infrasfilesfontsizeinherit cursorpointer"');
				$num	= infrasfiles_print_input($constname, 'select', $label, $help, $metas, 1, 1, '', $num);
			} elseif ($option['type'] == 'textarea') {
				// HTML editor (CKEditor) in a compact size, on the full width of the description + value columns (label above, same tooltip as the lib)
				if (!empty($help)) {
					$label	= $form->textwithtooltip($label, $langs->trans($help), 2, 1, img_help(1, ''));
				}
				$doleditor	= new DolEditor($constname, infrasfiles_get_option($key, $suffix), '100%', 120, 'dolibarr_notes', 'In', false, true, true, 4, '100%');
				print '	<tr class = "oddeven">
							<td class = "center bold valigntop">'.$num.'</td>
							<td colspan = "2"><div class = "infrasfileseditorlabel">'.$label.'</div>';
				$doleditor->Create();
				print '		</td>
						</tr>';
				$num++;
			} else {
				$metas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent');
				$num	= infrasfiles_print_input($constname, 'input', $label, $help, $metas, 1, 1, '', $num);
			}
		}
	}
	infrasfiles_print_final(count($metas) + 1);
	print '	</table>
			</div>';
	// Modèles de documents
	print infrasfiles_load_title('<span class = "infrasfilesTitleparam">'.$langs->trans('InfraSFilesTitleModels').'</span>', $form->textwithpicto('', $langs->trans('InfraSFilesModelsHelp')), dol_buildpath('/infrasfiles/img/list_updates.png', 1), 1, '', '');
	$nbShown	= 0;
	foreach ($registry as $key => $definition) {
		if (!infrasfiles_is_enabled($key, 'DOCUMENT')) {
			continue;
		}
		$nbShown++;
		$models			= infrasfiles_get_models($key);
		$constaddon		= infrasfiles_const_name($key, 'ADDON_PDF');
		$activeModels	= array();
		$sql			= 'SELECT nom FROM '.$db->prefix()."document_model WHERE type = '".$db->escape($definition['docpart'])."' AND entity = ".((int) $conf->entity);
		$resql			= $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$activeModels[]	= $obj->nom;
			}
		}
		print '	<table class = "infrasfilesnoborder centpercent">';
		$metas	= array('30px', '160px', '60px', '*', '90px', '90px', '70px');
		infrasfiles_print_colgroup($metas);
		print '		<tr class = "liste_titre">
						<td colspan = "7">'.img_picto('', $definition['picto'], 'class = "paddingright"').$langs->trans($definition['label']).'</td>
					</tr>
					<tr class = "liste_titre">
						<td class = "center">#</td>
						<td>'.$langs->trans('Name').'</td>
						<td class = "center">'.$langs->trans('Type').'</td>
						<td>'.$langs->trans('Description').'</td>
						<td class = "center">'.$langs->trans('Status').'</td>
						<td class = "center">'.$langs->trans('Default').'</td>
						<td class = "center">'.$langs->trans('Preview').'</td>
					</tr>';
		if (empty($models)) {
			print '	<tr class = "oddeven"><td colspan = "7" class = "opacitymedium center">'.$langs->trans('InfraSFilesNoModel').'</td></tr>';
		}
		$nummodel	= 1;
		foreach ($models as $name => $model) {
			require_once $model['file'];
			$classname	= $model['classname'];
			if (!class_exists($classname)) {
				continue;
			}
			$module		= new $classname($db);
			$urlbase	= dol_escape_htmltag($_SERVER['PHP_SELF']).'?token='.newToken().'&element='.urlencode($key).'&value='.urlencode($name);
			print '	<tr class = "oddeven">
						<td class = "center bold">'.$nummodel.'</td>
						<td>'.(empty($module->name) ? $name : $module->name).'</td>
						<td class = "center">'.(empty($module->type) ? 'pdf' : $module->type).'</td>
						<td>'.(empty($module->description) ? '' : $module->description).'</td>
						<td class = "center">';
			if (in_array($name, $activeModels)) {
				print '<a class = "reposition" href = "'.$urlbase.'&action=del">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
			} else {
				print '<a class = "reposition" href = "'.$urlbase.'&action=set">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
			}
			print '		</td>
						<td class = "center">';
			if (getDolGlobalString($constaddon) == $name) {
				print img_picto($langs->trans('Default'), 'on');
			} else {
				print '<a class = "reposition" href = "'.$urlbase.'&action=setdoc">'.img_picto($langs->trans('Disabled'), 'off').'</a>';
			}
			print '		</td>
						<td class = "center"><a href = "'.$urlbase.'&action=specimen" target = "_blank">'.img_picto($langs->trans('Preview'), 'pdf').'</a></td>
					</tr>';
			$nummodel++;
		}
		infrasfiles_print_final(count($metas));
		print '	</table>';
	}
	if (!$nbShown) {
		print '	<table class = "infrasfilesnoborder centpercent">
					<tr class = "oddeven"><td class = "opacitymedium center">'.$langs->trans('InfraSFilesNoObjectEnabled').'</td></tr>
				</table>';
	}
	print '	</form>
				<a class = "infrasfilesScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
