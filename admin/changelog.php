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
	*	\file		./infrastructure/admin/changelog.php
	*	\ingroup	InfraS
	*	\brief		changelog page
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrastructure/core/lib/infrastructureAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrastructure@infrastructure'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrastructure', 'paramInfrastructure')) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$action		= GETPOST('action','alpha');
	if ($action == 'dwnChangelog') {
		$result	= infrastructure_dwnChangelog('infrastructure');
	}

	// init variables *******************************
	$currentversion	= infrastructure_getLocalVersionMinDoli('infrastructure');

	// View *****************************************
	$page_name		= $langs->trans('InfrastructureSetup').' - '.$langs->trans('Changelog');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head			= infrastructure_admin_prepare_head();
	$picto			= 'infrastructure@infrastructure';
	print dol_get_fiche_head($head, 'changelog', $langs->trans('modcomnameInfrastructure'), 0, $picto);

	// About page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script type = "text/javascript">
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrastructureScrollUp").css("right", "30px");
							}
							else {
								$(".infrastructureScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print infrastructure_getChangeLog('infrastructure', $currentversion[0], $currentversion[2], $currentversion[3], 1);
	print infrastructure_getSupportInformation($currentversion[0]);
	print '		<a class = "infrastructureScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
