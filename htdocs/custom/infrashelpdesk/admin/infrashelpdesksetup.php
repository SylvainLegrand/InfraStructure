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
	* 	\file		./infrashelpdesk/admin/infrashelpdesksetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraSHelpdesk
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrashelpdesk/core/lib/infrashelpdeskAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrashelpdesk@infrashelpdesk'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrashelpdesk', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrashelpdesk', 'paramInfraSHelpdesk')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}
	// Actions **************************************
	$action			= GETPOST('action', 'alpha');
	$result			= '';
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOSTINT('value'), 'chaine', 0, 'InfraSHelpdesk module', $conf->entity);
	}

	// Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infrashelpdesk_bkup_module ('infrashelpdesk');
	} elseif ($action == 'restoreParams') {
		$result	= infrashelpdesk_restore_module ('infrashelpdesk');
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('tblCtxButton' => array('INFRASHELPDESK_PREFIX_LINK_WIKI_DOC'));
		$confkey	= $reg[1];
		foreach ($list[$confkey] as $constname) {
			if ($constname == 'INFRASHELPDESK_PREFIX_LINK_WIKI_DOC') {
				$constvalue	= infrashelpdesk_resolve_brand(GETPOST($constname, 'alpha'));	// jeton BRAND -> marque de l'instance (htdocs/BRAND), repli sur dolibarr
			} else {
				$constvalue	= GETPOST($constname, 'alpha');
			}
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSHelpdesk module', $conf->entity);
		}
	}

	//Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// View *****************************************
	$page_name		= $langs->trans('InfraSHelpdeskSetupPages').' - '.$langs->trans('InfraSHelpdeskParams');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head			= infrashelpdesk_admin_prepare_head();
	$picto			= 'infrashelpdesk@infrashelpdesk';
	print dol_get_fiche_head($head, 'infrashelpdesksetup', $langs->trans('modcomnameInfraSHelpdesk'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "infrashelpdesk_tblPSexp";
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar) {
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie(cookieName))) {
							tblPSexp = $.cookie(cookieName);
						}
					});
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrashelpdeskScrollUp").css("right", "30px");
							} else {
								$(".infrashelpdeskScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	if ($accessright == 2) {
		infrashelpdesk_print_backup_restore();
	}
	// Options du bouton flottant de contexte
	print '	<div class = "foldable">';
	print infrashelpdesk_load_title('<span class = "infrashelpdesktitleparam">'.$langs->trans('InfraSHelpdeskTitleCtxButton').'</span>', $titleoption, dol_buildpath('/infrashelpdesk/img/option_tool.png', 1), 1, '', '', '');
	print '		<table name = "tblCtxButton" class = "noborder centpercent">';
	$metas	= array('30px', '*', '90px', '*', '90px');
	infrashelpdesk_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrashelpdesk_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infrashelpdesk_print_btn_action('tblCtxButton', '<span class = "infrashelpdeskCaution">'.$langs->trans('InfraSHelpdeskCaution').'</span> '.$langs->trans('InfraSHelpdeskParamCautionSave'), 4);
		$num	= infrashelpdesk_print_input('INFRASHELPDESK_PREFIX_LINK_WIKI_DOC', 'input', $langs->trans('InfraSHelpdeskPrefixLinkWikiDoc'), 'InfraSHelpdeskPrefixLinkWikiDocHelp', array(), 1, 2, '', $num);
		$num	= infrashelpdesk_print_input('INFRASHELPDESK_BUTTON_FOR_ALL_USERS', 'on_off', $langs->trans('InfraSHelpdeskBtnAllUsers'), '', array(), 2, 1, '', $num);
		$num	= infrashelpdesk_print_input('INFRASHELPDESK_ENABLE_DEV_MODE', 'on_off', $langs->trans('InfraSHelpdeskEnableDevMode'), '', array(), 2, 1, '', $num);
	}
	print '		</table>
				</div>';
	print '	</form>
			<a class = "infrashelpdeskScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
