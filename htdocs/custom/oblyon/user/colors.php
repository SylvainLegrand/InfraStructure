<?php
	/************************************************
	* Copyright (C) 2026   Sylvain Legrand   <contact@infras.fr>   InfraS - <https://www.infras.fr>
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
	* along with this program.  If not, see <https://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		../oblyon/user/colors.php
	* 	\ingroup	oblyon
	* 	\brief		User card, tab "Colors" : personal colours of the Oblyon theme (3.6.0), same layout as the core "Display setup" tab
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';		// dol_set_user_param
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';			// dolibarr_set_const
	require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';		// user_prepare_head
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	dol_include_once('/oblyon/lib/oblyon_colors.lib.php');
	dol_include_once('/oblyon/lib/oblyon_presets.lib.php');				// oblyon_check_preset_contrast

	// Translations *********************************
	$langs->loadLangs(array('users', 'admin', 'main', 'other', 'oblyon@oblyon'));

	// Access control (same rules as user/param_ihm.php) ***********
	$id				= GETPOSTINT('id');
	$action			= GETPOST('action', 'aZ09');
	if ($id <= 0)	accessforbidden();
	$canreaduser	= ($user->admin || $user->hasRight('user', 'user', 'read'));
	$caneditfield	= (($user->id == $id && $user->hasRight('user', 'self', 'write')) || ($user->id != $id && $user->hasRight('user', 'user', 'write')));
	$socid			= ($user->socid > 0 ? $user->socid : 0);
	$feature2		= (($socid && $user->hasRight('user', 'self', 'write')) ? '' : 'user');
	$result			= restrictedArea($user, 'user', $id, 'user&user', $feature2);
	if ($user->id != $id && !$canreaduser)	accessforbidden();
	$object			= new User($db);
	$object->fetch($id, '', '', 1);		// 1 = load the personal conf (llx_user_param) into $object->conf
	$object->loadRights();
	$form			= new Form($db);
	$canedit		= ($caneditfield || !empty($user->admin));
	$hookmanager->initHooks(array('usercard', 'globalcard'));	// same contexts as the core user card : tabs added by other modules through completeTabsHead
	$keys			= oblyon_user_colors_keys();

	// Actions **************************************
	if ($action == 'update' && $canedit) {
		if (!GETPOST('cancel')) {
			$tabparam	= array();
			$enable		= (GETPOST('check_OBLYON_USER_COLORS') == 'on');
			foreach ($keys as $key) {
				if ($enable) {
					// full snapshot : the posted value (jscolor posts the hex without '#'), else the instance value.
					// An empty field keeps the instance value : '' (auto contrast of the theme) must not become '#'
					$posted	= GETPOST($key, 'alpha');
					$value	= ($posted === '' ? '' : '#'.strtoupper(ltrim($posted, '#')));
					if ($value === '' || !preg_match('/^#([0-9A-F]{6})?$/', $value))	$value	= getDolGlobalString($key);
					$tabparam[$key]	= $value;
				} else {
					$tabparam[$key]	= '';		// '' = row deleted by dol_set_user_param
				}
			}
			$tabparam['OBLYON_USER_COLORS']	= ($enable ? 1 : '');
			if (!$enable)	$tabparam['THEME_ELDY_ENABLE_PERSONALIZED']	= '';	// historical core flag, also honoured by the theme : cleared too
			$res	= dol_set_user_param($db, $conf, $object, $tabparam);		// 4 arguments : signature of Dolibarr 22 LTS
			if ($res > 0) {
				// the stylesheet address carries &revision= : the browser fetches the new colours at once
				dolibarr_set_const($db, 'MAIN_IHM_PARAMS_REV', getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
				setEventMessages($langs->trans($enable ? 'OblyonUserColorsSaved' : 'OblyonUserColorsRemoved'), null, 'mesgs');
			} else {
				setEventMessages($langs->trans('Error'), null, 'errors');
			}
		}
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
		exit;
	}

	// View *****************************************
	$edit		= ($action == 'edit' && $canedit);
	$enabled	= oblyon_user_colors_enabled($object);	// OBLYON_USER_COLORS, or the historical THEME_ELDY_ENABLE_PERSONALIZED user param (same test as the theme)
	$title		= ($object->firstname ? $object->lastname.', '.$object->firstname : $object->lastname).' - '.$langs->trans('OblyonUserColorsTab');
	llxHeader('', $title, '', '', 0, 0, ($edit ? array('/oblyon/js/jscolor.js') : ''), '', '', 'mod-oblyon page-user-colors');
	$head		= user_prepare_head($object);
	if ($edit) {
		print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.((int) $id).'">';
	}
	print dol_get_fiche_head($head, 'oblyoncolors', $langs->trans('User'), -1, 'user');
	$linkback	= (($user->hasRight('user', 'user', 'read') || $user->admin) ? '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>' : '');
	dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'read') || $user->admin);
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';
	print '<tr><td class="titlefield">'.$langs->trans('Login').'</td><td>'.showValueWithClipboardCPButton($object->login).'</td></tr>';
	print '</table>';
	print dol_get_fiche_end();
	print '<br>';

	// Table : Parameter | Default value | Use personal value | Personal value (same layout as showSkins() of the core for a user profile)
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent'.($edit ? ' editmodeforshowskin' : '').' oblyon-user-colors">';
	print '<tr class="liste_titre"><th class="titlefieldmiddle">'.$langs->trans('Parameter').'</th><th>'.$langs->trans('DefaultValue').'</th><th>&nbsp;</th><th>'.$langs->trans('PersonalValue').'</th></tr>';
	print '<tr class="oddeven">';
	print '<td>'.$form->textwithpicto($langs->trans('OblyonUserColors'), $langs->trans('OblyonUserColorsHelp')).'</td>';
	print '<td>'.$langs->trans('OblyonUserColorsDefault').'</td>';
	print '<td class="nowrap left"><input id="check_OBLYON_USER_COLORS" name="check_OBLYON_USER_COLORS" type="checkbox"'.($edit ? '' : ' disabled').($enabled ? ' checked' : '').'> <label for="check_OBLYON_USER_COLORS">'.$langs->trans('UsePersonalValue').'</label></td>';
	print '<td>&nbsp;</td>';
	print '</tr>';
	$snapshot	= array();
	foreach (oblyon_user_colors_list() as $group => $names) {
		print '<tr class="liste_titre"><td colspan="4">'.$langs->trans($group).'</td></tr>';
		foreach ($names as $key) {
			$instance	= getDolGlobalString($key);
			$personal	= (isset($object->conf->$key) ? (string) $object->conf->$key : '');
			if ($enabled && $personal !== '')	$snapshot[$key]	= $personal;
			print '<tr class="oddeven">';
			print '<td>'.oblyon_user_color_label($key).'</td>';
			print '<td>'.oblyon_color_swatch($instance, $key).'</td>';
			print '<td>&nbsp;</td>';
			print '<td>';
			if ($edit) {
				$value	= ($personal !== '' && oblyon_color_is_valid($personal, $key) ? $personal : $instance);
				print '<input type="text" name="'.$key.'" id="'.$key.'" class="flat color oblyon-user-color" size="8" maxlength="7" value="'.dol_escape_htmltag($value).'"'.($enabled ? '' : ' disabled').'>';
			} else {
				print ($enabled && $personal !== '' ? oblyon_color_swatch($personal, $key) : '&nbsp;');
			}
			print '</td>';
			print '</tr>';
		}
	}
	print '</table>';
	print '</div>';

	// Contrast check of the personal palette (same couples as the preset cards)
	if (!$edit && $enabled && count($snapshot)) {
		$low	= oblyon_check_preset_contrast(array('colors' => $snapshot));
		if (count($low)) {
			$details	= array();
			foreach ($low as $c)	$details[]	= oblyon_user_color_label($c['text']).' / '.oblyon_user_color_label($c['background']).' : '.$c['ratio'];
			print '<div class="warning">'.$langs->trans('OblyonPresetContrastWarning', count($low)).'<br>'.implode('<br>', $details).'</div>';
		}
	}

	if ($edit) {
		print $form->buttonsSaveCancel();
		print '</form>';
		// the colour fields stay disabled until "use personal value" is ticked ; disabled fields are not posted
		print '<script type="text/javascript">
			jQuery(function () {
				function oblyonUserColorsToggle() {
					var on = jQuery("#check_OBLYON_USER_COLORS").prop("checked");
					jQuery("input.oblyon-user-color").prop("disabled", !on).toggleClass("opacitymedium", !on);
				}
				oblyonUserColorsToggle();
				jQuery("#check_OBLYON_USER_COLORS").on("click", oblyonUserColorsToggle);
			});
		</script>';
	} else {
		print '<div class="tabsAction">';
		if ($canedit) {
			print '<a class="butAction" href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=edit&token='.newToken().'&id='.((int) $object->id).'">'.$langs->trans('Modify').'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans('NotEnoughPermissions')).'" href="#">'.$langs->trans('Modify').'</a>';
		}
		print '</div>';
	}

	// End of page
	llxFooter();
	$db->close();
