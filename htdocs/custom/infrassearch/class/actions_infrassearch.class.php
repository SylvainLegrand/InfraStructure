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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrassearch/class/actions_infrassearch.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraSSearch
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	dol_include_once('/infrassearch/core/lib/infrassearch.lib.php');
	dol_include_once('/infrassearch/core/lib/infrassearchAdmin.lib.php');

	/************************************************
	* Class infrassearch
	************************************************/
	class Actionsinfrassearch
	{
		public $db;	// @var DoliDB Database handler.
		public $results = array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $errors = array();	// @var array Errors

		/************************************************
		* Constructor
		*
		* @param	 DATABASE		$db		 db object
		* @return						void
		************************************************/
		public function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		* When login
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function afterLogin($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$currentversion	= array();
			$currentversion	= infrassearch_getLocalVersionMinDoli('infrassearch');
			if (!getDolGlobalString('INFRASSEARCH_DISABLE_CHECK_VERSION_MAX', '') && version_compare(explode('.', DOL_VERSION)[0], explode('.', $currentversion[4])[0], '>')) {
				setEventMessages($langs->trans('InfraSSearchWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			return 0;
		}

		/**
		* Search tool
		*
		* @param	array()			$parameters		 Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function printTopRightMenu($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$out	= '';
			if (getDolGlobalString('INFRASSEARCH_ON_TOP_MENU', '') || getDolGlobalString('INFRASSEARCH_BREADCRUMB', '')) {
				$langs->load('infrassearch@infrassearch');
				$nbCar	= getDolGlobalInt('INFRASSEARCH_NB_CAR', 3);
				$nbSec	= getDolGlobalInt('INFRASSEARCH_NB_SEC', 500);
				$out	.= '				<div class = "inline-block nowrap">';
				if (getDolGlobalString('INFRASSEARCH_BREADCRUMB', '')) {	// fil d'Ariane
					$out	.= '				<!-- div for breadcrumb -->
												<div class = "inline-block login_block_elem_name">
													<div id = "topmenu-breadcrumb-dropdown" class = "userimg atoplogin dropdown user user-menu inline-block" style = "padding: 0 5px 0 5px;">
														<a class = "dropdown-toggle" data-toggle = "dropdown" href = "#" title = "'.$langs->trans('InfraSSearchBreadcrumb').' ('.$langs->trans('InfraSSearchBreadcrumbShortCut').')">
															<i class = "fa fa-history" ></i>
														</a>
														<div id = "topmenu-breadcrumb-dropdown-body" class = "dropdown-menu" style = "width: 335px;">'.printDropdownBreadCrumb().'</div>
													</div>
												</div>
												<!-- Code to show/hide the user drop-down -->
												<script type = "text/javascript">
													$( document ).ready(function() {
														$(document).on("click", function(event) {
															if (!$(event.target).closest("#topmenu-breadcrumb-dropdown").length) {
																$("#topmenu-breadcrumb-dropdown").removeClass("open");	// Hide the menus.
															}
														});
														$("#topmenu-breadcrumb-dropdown .dropdown-toggle").on("click", function(event) {
															openBreadCrumbDropDown();
														});
														// Key map shortcut
														$(document).keydown(function(e){
																if( e.which === 75 && e.ctrlKey && e.shiftKey ){
																 console.log(\'control + shift + k : trigger open breadcrumb dropdown\');
																 openBreadCrumbDropDown();
																}
														});

														var openBreadCrumbDropDown = function() {
															event.preventDefault();
															$(".dropdown-breadcrumb-list a").each(function() {
																$(this).removeClass("classfortooltip");
															});
															var topmenuhight = $(".menulogocontainer").height();
															$("#topmenu-breadcrumb-dropdown-body").css("top", topmenuhight + 10);
															$("#topmenu-breadcrumb-dropdown").toggleClass("open");
														}
													});
												</script>';
				}
				if (getDolGlobalString('INFRASSEARCH_ON_TOP_MENU', '')) {	// Advanced search
					// Allow two constant to use other values for backward compatibility
					$dataforrenderITem	= defined('JS_QUERY_AUTOCOMPLETE_RENDERITEM') ? constant('JS_QUERY_AUTOCOMPLETE_RENDERITEM') : 'ui-autocomplete';
					$dataforitem		= defined('JS_QUERY_AUTOCOMPLETE_ITEM') ? constant('JS_QUERY_AUTOCOMPLETE_ITEM') : 'ui-autocomplete-item';
					$out				.= '	<!-- form for search input -->
												<div class = "inline-block login_block_elem_name">
													<div id = "topmenu-search" class = "userimg atoplogin user user-menu inline-block width200" style = "padding: 4px 5px 0 5px;">
														<form method = "post" action = "'.dol_buildpath('/infrassearch/search.php',1).'">
															<input type = "hidden" name = "token" value = "'.newToken().'">
															<input type = "hidden" id = "keywords" name = "keywords" value = ""/>
															<input type = "text" size = "15" id = "search_keyword" name = "search_keyword" title = "'.$langs->trans('Keyword').'" class = "infrassearchbgtrans infrassearchwidthquatrevingtdixpercent ui-autocomplete-input" placeholder = "'.$langs->trans('InfraSSearchInputPlaceHolder').'"/>
														</form>
													</div>
												</div>
												<script type = "text/javascript">
													// Check options for secondary actions when keyup
													$("input#search_keyword").keyup(function() {
															if ($(this).val().length == 0) {
																$("#search_keyword").val("");
																$("#keywords").val("").trigger("change");
															}
													});
													$("input#search_keyword").autocomplete({
														source: function(request, response) {
															$.ajax({
																url: "'.dol_buildpath('/infrassearch/script/interface.php',1).'",
																dataType: "json",
																data: {
																	keywords: request.term
																	,get: "search-all"
																}
																,success: function( data ) {
																	var c = [];
																	$.each(data, function (i, cat) {
																		var first = true;
																		$.each(cat, function(j, obj) {
																			if(first) {
																				c.push({value:obj.categorie, label:obj.categorie, object:"title"});	// Add a first item with the category name
																				first = false;
																			}
																			c.push({ value:obj.desc, label:"  "+obj.label_clean, url:obj.url, desc:"  "+obj.desc, object:i});
																		});
																	});
																	response(c);
																}
															});
														},
														minLength: '.$nbCar.',
														select: function(event, ui) {
															// A new value has been selected, we trigger the handlers on #keywords
															$("#keywords").val(ui.item.id).trigger("change");	// Select new value
															if (ui.item.url) {
																document.location.href = ui.item.url;
															}
															$("input#search_keyword").trigger("change");	// We have changed value of the combo select, we must be sure to trigger all js hook binded on this event. This is required to trigger other javascript change method binded on original field by other code.
															return false;
														},
														delay: '.$nbSec.'
													}).data("'.$dataforrenderITem.'")._renderItem = function( ul, item ) {
														$li = $("<li style=\"white-space: nowrap;\" />")
														.attr("data-value", item.value)
														.data("'.$dataforitem.'", item) // jQuery UI > 1.10.0
														.append(\'<span class="select2-results">\' + item.label + "</span>")
														.appendTo(ul);
														if (item.object=="title") {
															$li.css("font-weight","bold");
														}
														if (item.object=="title") {
															$li.css("cursor","default");
														}
														return $li;
													};
												</script>';
				}
				$out	.= '				</div>';
			}
			print $out;
			return 0;
		}

		/**
		* Search tool
		*
		* @param	array()			 $parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action			Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager		Hook manager propagated to allow calling another hook
		* @return	int									< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function printSearchForm($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			if (in_array('searchform', explode(':', $parameters['context'])) && getDolGlobalString('INFRASSEARCH_REPLACE_STD', '')) {
				$res	= '';
				if (!getDolGlobalString('INFRASSEARCH_ON_TOP_MENU', '')) {
					$langs->load('infrassearch@infrassearch');
					$nbCar	= getDolGlobalInt('INFRASSEARCH_NB_CAR', 3);
					$nbSec	= getDolGlobalInt('INFRASSEARCH_NB_SEC', 500);
					$res	= '	<form method = "post" action = "'.dol_buildpath('/infrassearch/search.php',1).'">
									<input type = "hidden" name = "token" value = "'.newToken().'">
									<input type = "text" size = "15" name = "keyword" title = "'.$langs->trans('Keyword').'" class = "flat infrassearchbgtrans infrassearchwidthquatrevingtdixpercent" id = "sew_keyword" placeholder = "'.$langs->trans('InfraSSearchInputPlaceHolder').'"/>
								</form>
								<script type = "text/javascript">
									$("#sew_keyword").autocomplete({
										source: function( request, response ) {
											$.ajax({
												url: "'.dol_buildpath('/infrassearch/script/interface.php',1).'",
												dataType: "json",
												data: {
													keyword: request.term
													,get:"search-all"
												}
												,success: function( data ) {
													var c = [];
													$.each(data, function (i, cat) {
														var first = true;
														$.each(cat, function(j, obj) {
															if(first) {
																c.push({value:obj.categorie, label:obj.categorie, object:"title"});	// Add a first item with the category name
																first = false;
															}
															c.push({ value:obj.desc, label:"  "+obj.label_clean, url:obj.url, desc:"  "+obj.desc, object:i});	// Add the list of items
														});
													});
													response(c);
												}
											});
										},
										delay: '.$nbSec.',
										minLength: '.$nbCar.',
										select: function( event, ui ) {
											if(ui.item.url) {
												document.location.href = ui.item.url;
											}
											return false;
										},
										open: function( event, ui ) {
											$( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
										},
										close: function() {
											$( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
										}
									});
									$( "#sew_keyword" ).autocomplete( "instance" )._renderItem = function( ul, item ) {
										$li = $( "<li style=\"white-space: nowrap;\" />" )
										.attr( "data-value", item.value )
										.append("<span class=\"select2-results\" >"+item.label+"</span>" )
										.appendTo( ul );
										if(item.object=="title") $li.css("font-weight","bold");
										if(item.object=="title") $li.css("cursor","default");
										return $li;
									};
								</script>';
				}
				$this->resprints	= $res;
				return 1;
			}
			return (getDolGlobalString('INFRASSEARCH_ON_TOP_MENU', '') ? 1 : 0);
		}

		/**
		* Search tool
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function addSearchEntry($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			if (in_array('searchform', explode(':', $parameters['context'])) && !getDolGlobalString('INFRASSEARCH_REPLACE_STD', '')) {
				$langs->load('infrassearch@infrassearch');
				$search_boxvalue	= $parameters['search_boxvalue'];
				$this->results		= array('infrassearch'	=> array('img'	=> 'object_infrassearch',
																	'label'	=> $langs->trans('InfraSSearchInputPlaceHolder'),
																	'text'	=> img_picto('','infrassearch@infrassearch').' '.$langs->trans('InfraSSearchInputPlaceHolder'),
																	'url'	=> dol_buildpath('/infrassearch/search.php',1).'?keyword='.urlencode($search_boxvalue)
																	)
											);
			}
			return 0;
		}

		/**
		* When we ask for an action (../element/card.php | ../admin/modules.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function doActions($parameters, &$object, &$action, $hookmanager)
		{
			global $conf;

			$TContext	= explode(':', $parameters['context']);
			if (in_array('adminmodules', $TContext) && $action == 'reset') {	// Reset a module
				$module = preg_replace('/^mod/i', '', GETPOST('value', 'alpha'));	// Get the module name
				// delete all InfraSSearch entries for this module
				dolibarr_del_const($this->db, 'INFRASSEARCH_MOD_'.strtoupper($module), $conf->entity);	// Enabling search in the module
				dolibarr_del_const($this->db, 'INFRASSEARCH_POS_'.strtoupper($module), $conf->entity);	// Position of the search results for this module
			}
			return 0;
		}

		/**
		* save breadcrumb
		*
		* @param	array()	$parameters		Hook metadatas (context, etc...)
		* @return	int						< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function printCommonFooter($parameters)
		{
			global $user, $conf, $object;

			$objectId	= !empty($object->id) ? $object->id : (!empty($object->rowid) ? $object->rowid : '');
			if (is_object($object) && property_exists($object, 'element') && !empty($objectId) && $object->element != 'commonsign') {
				// Clean
				$sqlclean	= 'DELETE FROM '.$this->db->prefix().'infrassearch_history';
				$sqlclean	.= ' WHERE entity = '.$conf->entity;
				$sqlclean	.= ' AND fk_user = '.$user->id;
				$sqlclean	.= ' AND DATE_FORMAT(tms, "%Y-%m-%d") <  DATE_FORMAT(CURDATE(), "%Y-%m-01") - INTERVAL 1 MONTH';	// we keep the last month only
				$this->db->query($sqlclean);
				// Delete
				$sqldel		= 'DELETE FROM '.$this->db->prefix().'infrassearch_history';
				$sqldel		.= ' WHERE entity = '.$conf->entity;
				$sqldel		.= ' AND element = "'.$object->element.'"';
				$sqldel		.= ' AND fk_element = '.$objectId;
				$sqldel		.= ' AND fk_user = '.$user->id;
				$this->db->query($sqldel);
				// Add
				$sqladd		= 'INSERT INTO '.$this->db->prefix().'infrassearch_history';
				$sqladd		.= ' (entity, element, fk_element, fk_user)';
				$sqladd		.= ' VALUES ('.$conf->entity.', "'.$object->element.'", '.$objectId.', '.$user->id.')';
				$this->db->query($sqladd);
			}
			return 0;
		}
	}
