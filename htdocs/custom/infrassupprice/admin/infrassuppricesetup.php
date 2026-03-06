<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrassupprice/admin/infrassuppricesetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrassupprice/core/lib/infrassuppriceAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'other', 'infrassupprice@infrassupprice'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrassupprice', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrassupprice', 'paramInfraSSupPrice')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$action	= GETPOST('action','alpha');
	$result	= '';
	// Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infrassupprice_bkup_module ('infrassupprice');
	}
	if ($action == 'restoreParams') {
		$result	= infrassupprice_restore_module ('infrassupprice');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value', 'alphanohtml'), 'chaine', 0, 'InfraSSupPrice module', $conf->entity);
	}
	// Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), array(), 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), array(), 'errors');
	}

	// View *****************************************
	$page_name	= $langs->trans('InfraSSupPriceSetup').' - '.$langs->trans('Setup');
	llxHeader('', $page_name);
	$linkback	= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head		= infrassupprice_admin_prepare_head();
	$picto		= 'infrassupprice@infrassupprice';
	print dol_get_fiche_head($head, 'settings', $langs->trans('modcomnameInfraSSupPrice'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrassuppriceScrollUp").css("right", "30px");
							} else {
								$(".infrassuppriceScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Sauvegarde / Restauration
	if ($accessright == 2)	infrassupprice_print_backup_restore();
	//Comportement général
	print '		<div class = "foldable">';
	print infrassupprice_load_title('<span class = "infrastitleparam">'.$langs->trans('InfraSSupPriceParamTitleComp').'</span>', '', dol_buildpath('/infrassupprice/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblCG" class = "noborder centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infrassupprice_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrassupprice_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		$num	= infrassupprice_print_input('INFRASSUPPRICE_QTE_NOT_VALUE_MIN', 'on_off', $langs->trans('InfraSSupPriceParamQtyNotValueMin'), '', array(), 2, 1, '', $num);
	}
	print '			</table>
				</div>';
	print '	</form>
			<a class = "infrassuppriceScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
