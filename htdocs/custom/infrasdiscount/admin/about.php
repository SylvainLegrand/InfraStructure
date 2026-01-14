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
	* 	\file		./infrasdiscount/admin/about.php
	* 	\ingroup	InfraS
	* 	\brief		about page
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';
	dol_include_once('/infrasdiscount/core/lib/infrasdiscountAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrasdiscount@infrasdiscount'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrasdiscount', 'paramInfrasdiscount')) ? 1 : 0;
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$action		= GETPOST('action','alpha');

	// init variables *******************************
	$content	= dolMd2Html(file_get_contents(dol_buildpath('infrasdiscount/README.md', 0)),
							'parsedown',
							array ('doc/'		=> dol_buildpath('infrasdiscount/doc/', 1),
									'img/'		=> dol_buildpath('infrasdiscount/img/', 1),
									'images/'	=> dol_buildpath('infrasdiscount/images/', 1)
									)
							);

	// View *****************************************
	$page_name	= $langs->trans('InfraSDiscountSetup').' - '.$langs->trans('About');
	llxHeader('', $page_name);
	$linkback	= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head		= infrasdiscount_Prepare_Head();
	$picto		= 'infrasdiscount@infrasdiscount';
	print dol_get_fiche_head($head, 'about', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// About page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script type = "text/javascript">
					$(function () {
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrasdiscountScrollUp").css("right", "30px");
							}
							else {
								$(".infrasdiscountScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '		<form class = "infrasdiscountformabout" action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">
					<div class = "moduledesclong">'.$content.'<div>
				</form>
				<a class = "infrasdiscountScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();

