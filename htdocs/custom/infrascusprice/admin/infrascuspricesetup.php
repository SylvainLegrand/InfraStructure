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
	* 	\file		./infrascusprice/admin/infrascuspricesetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraSCusPrice
	************************************************/
	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrascusprice/core/lib/infrascuspriceAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrascusprice@infrascusprice'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrascusprice', 'paramInfraSCusPrice')) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$action		= GETPOST('action','alpha');
	$confirm	= GETPOST('confirm', 'alpha');
	$result		= '';

	//Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************

	// View *****************************************
	$page_name		= $langs->trans('InfraSCusPSetup').' - '.$langs->trans('InfraSCusPParams');
	llxHeader('', $page_name);
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= '';

	// Configuration header *************************
	$head	= infrascusp_admin_prepare_head();
	$picto	= 'infrascusprice@infrascusprice';
	print dol_get_fiche_head($head, 'infrascuspricesetup', $langs->trans('modcomnameCusP'), 0, $picto);

	// setup page goes here *************************
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Comportement général
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print infrascusp_load_title('<span class = "infrascuspriceTitleparam">'.$langs->trans('InfraSCusPParamNoConfTechInfos').'</span>', $titleoption, dol_buildpath('/infrascusprice/img/option_tool.png', 1), 1, '', '');
		print '		<table name = "tblGen" class = "infrascuspricenoborder" width = "100%">';
		$metas	= array('30px', '*', '156px', '120px');
		infrascusp_print_colgroup($metas);
		$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infrascusp_print_liste_titre($metas);
		print '		</table>
				</div>';
	}
	print '	</form>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();