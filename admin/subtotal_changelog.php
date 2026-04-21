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
	*	\file		./subtotal/admin/changelog.php
	*	\ingroup	InfraS
	*	\brief		changelog page
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/subtotal/core/lib/subtotalAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'subtotal@subtotal'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('subtotal', 'paramSubTotal')) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$action		= GETPOST('action','alpha');
	if ($action == 'dwnChangelog') {
		$result	= subtotal_dwnChangelog('subtotal');
	}

	// init variables *******************************
	$currentversion	= subtotal_getLocalVersionMinDoli('subtotal');

	// View *****************************************
	$page_name		= $langs->trans('SubTotalSetup').' - '.$langs->trans('Changelog');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head			= subtotal_admin_prepare_head();
	$picto			= 'subtotal@subtotal';
	print dol_get_fiche_head($head, 'changelog', $langs->trans('modcomnameSubTotal'), 0, $picto);

	// About page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script type = "text/javascript">
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".subtotalScrollUp").css("right", "30px");
							}
							else {
								$(".subtotalScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print subtotal_getChangeLog('subtotal', $currentversion[0], $currentversion[2], $currentversion[3], 1);
	print subtotal_getSupportInformation($currentversion[0]);
	print '		<a class = "subtotalScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
