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
	* 	\file		./infrassearch/search.php
	* 	\ingroup	InfraS
	* 	\brief		Tools menu search page
	************************************************/

	// Dolibarr environment *************************
	require 'config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

	$langs->load('infrassearch@infrassearch');

	$form					= new Form($db);
	$formother				= new FormOther($db);
	$keyword				= GETPOST('search_keyword');
	$listTObjectType		= array();
	$listTObjectType		= explode(',', getDolGlobalString('INFRASSEARCH_LISTTOBJECTTYPE', ''));
	$validListTObjectType	= array();
	foreach($listTObjectType as $TObjectType) {
		$validTObjectType	= 'INFRASSEARCH_MOD_'.strtoupper($TObjectType);
		if (getDolGlobalInt($validTObjectType, 0) == 1) {
			$validListTObjectType[]	= '"'.$TObjectType.'"';
		}
	}
	llxHeader('', $langs->trans('InfraSSearchInputPlaceHolder'), '', '', 0, 0, array('/infrassearch/js/jquery.tile.min.js'));
	print '<form method = "POST" action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'"  enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">
				<table width = "99%">
					<tr>
						<td align = "left" width = "33%">';
	print load_fiche_titre($langs->trans('InfraSSearchInputPlaceHolder'), '', dol_buildpath('/infrassearch/img/InfraSSearch.png', 1), 1);
	print '				</td>
						<td align = "center" width = "33%">
							<input type = "text" name = "keyword" id = "keyword" class = "minwidth400" value = "" />
						</td>
						<td align = "right" width = "33%">
							<input type = "button" name = "btnsearch" id = "btnsearch" value = "'.$langs->trans('Search').'" class = "button" />
						</td>
					</tr>
				</table>
				<div id = "results"></div>
				<div style = "clear:both"></div>
				<script type = "text/javascript">
					var url		= "'.dol_buildpath('/infrassearch/search.php?keyword=', 1).'";
					var TSearch	= ['.implode(',', $validListTObjectType).'];
					function performSearch() {
						var keyword	= $("#keyword").val();
						$("#results").html("<span class = \"loading\">'.$langs->trans('InfraSSearchWait').'</span>");
						$("a#search").attr("href", url + keyword);
						for(x in TSearch) {
							$.ajax({
								url : "'.dol_buildpath('/infrassearch/script/interface.php', 1).'"
								,data : {
									get : "search"
									,type : TSearch[x]
									,keyword : keyword
								}
							}).done(function(data) {
								$("#results span.loading").remove();
								$div = $("<div class = \"result\" />");
								$div.append(data);
								$("#results").append($div);
								$("#results div.result").tile();
							});
						}
					}
					$(document).ready(function() {
						// Action sur clic du bouton
						$("#btnsearch").click(function() {
							performSearch();
						});
						// Action sur appui de la touche Entrée dans le champ
						$("#keyword").keypress(function(e) {
							if (e.which == 13) {	// 13 = code de la touche Entrée
								e.preventDefault();	// Empêche la soumission du formulaire
								performSearch();
							}
						});
						';
	if ($keyword != '') {
		print '			$("#keyword").val("'.$keyword.'");
						performSearch();
						';
	}
	print '			});
			</script>
		</form>';
	// End of page
	llxFooter();
	$db->close();
