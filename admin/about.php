<?php
	/*************************************************
	* <one line to give the program's name and a brief idea of what it does.>
	*
	* Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
	**************************************************/

	/****************************************************
	*	\file		admin/about.php
	*	\ingroup	infrastructure
	*	\brief		This file is an example about page
	*				Put some comments here
	****************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries *************************
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';
	dol_include_once('/infrastructure/core/lib/infrastructureAdmin.lib.php');

	// Translations *********************************
	$langs->load('infrastructure@infrastructure');

	// Access control *********************************
	if (!$user->admin) {
		accessforbidden();
	}

	// init variables *******************************
	$content	= dolMd2Html(file_get_contents(dol_buildpath('infrastructure/README.md', 0)),
							'parsedown',
							array ('doc/'		=> dol_buildpath('infrastructure/doc/', 1),
													'img/'		=> dol_buildpath('infrastructure/img/', 1),
													'images/'	=> dol_buildpath('infrastructure/images/', 1)
													)
							);
	// View *********************************
	$page_name  = $langs->trans('InfrastructureSetup').' - '.$langs->trans('About');
	llxHeader('', $page_name);
	$newToken   = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];
	$linkback   = '<a href="' . DOL_URL_ROOT . '/admin/modules.php&token='.$newToken.'">'. $langs->trans("BackToModuleList") . '</a>';
	print load_fiche_titre($page_name, $linkback);

	// Configuration header *************************
	$head   = infrastructure_admin_prepare_head();
	print dol_get_fiche_head($head, 'about', $langs->trans("Module104777Name"), 0, 'infrastructure@infrastructure');

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
		print '		<form class = "infrastructureformabout" action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
						<input type = "hidden" name = "token" value = "'.newToken().'">
						<div class = "moduledesclong">'.$content.'<div>
					</form>
					<a class = "infrastructureScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
