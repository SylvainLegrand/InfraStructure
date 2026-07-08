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
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/dolinfras/core/lib/dolinfrasAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'dolinfras@dolinfras'));

	// Access control *******************************
	$accessright	= !empty($user->admin) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}
	// Actions **************************************
	$action			= GETPOST('action', 'alpha');
	if ($action == 'forceLTSParams') {
		$result	= dolinfras_force_lts_constants('dolinfras');
		if ($result >= 0) {
			setEventMessages($langs->trans('DolInfraSForceApplyDone', $result), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('DolInfraSForceApplyError'), null, 'errors');
		}
	}

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
	print '	</table>
			</form>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
