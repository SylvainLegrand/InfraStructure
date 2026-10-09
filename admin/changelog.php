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
	* 	\file		./infrashelpdesk/admin/changelog.php
	* 	\ingroup	InfraS
	* 	\brief		changelog page
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrashelpdesk/core/lib/infrashelpdeskAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrashelpdesk@infrashelpdesk'));

	// Access control *******************************
	$accessright	= !empty($user->admin) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}
	// Actions **************************************
	$action			= GETPOST('action', 'alpha');
	if ($action == 'dwnChangelog') {
		$result	= infrashelpdesk_dwnChangelog('infrashelpdesk');
	}

	// init variables *******************************
	$currentversion	= infrashelpdesk_getLocalVersionMinDoli('infrashelpdesk');

	// View *****************************************
	$page_name		= $langs->trans('InfraSHelpdeskSetupPages').' - '.$langs->trans('InfraSHelpdeskParamsChangelog');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head			= infrashelpdesk_admin_prepare_head();
	$picto			= 'infrashelpdesk@infrashelpdesk';
	print dol_get_fiche_head($head, 'changelog', $langs->trans('modcomnameInfraSHelpdesk'), 0, $picto);

	// Changelog page goes here *********************
	print infrashelpdesk_getChangeLog('infrashelpdesk', $currentversion[0], $currentversion[2], $currentversion[3], 1);
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
