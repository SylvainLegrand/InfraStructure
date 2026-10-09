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
	* 	\file		./infrassearch/admin/infrassearchsetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraSSearch
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infrassearch/core/lib/infrassearchAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infrassearch@infrassearch'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrassearch', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrassearch', 'paramInfraSSearch')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$formcompany	= new FormCompany($db);
	$confirm_mesg	= '';
	$action			= GETPOST('action','alpha');
	$confirm		= GETPOST('confirm', 'alpha');
	$result			= '';
	if (!empty($action)) {
		parse_str(str_replace('-', '&', GETPOST('stringListTObjectType', 'alpha')), $listTObjectType);	// change all '-' by '&' and Parse the string to an array
	}
	//Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infrassearch_bkup_module ('infrassearch');
	}
	if ($action == 'restoreParams') {
		$result	= infrassearch_restore_module ('infrassearch');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$value		= GETPOSTINT('value');
		$result		= dolibarr_set_const($db, $confkey, $value, 'chaine', 0, 'InfraSSearch module', $conf->entity);
		if (preg_match('/^INFRASSEARCH_MOD_(.*)/', $confkey, $reg2)) {
			$module	= strtolower( preg_replace('/^INFRASSEARCH_MOD_/i', '', $confkey));
			if ($value == 0) {
				if (array_key_exists($module, $listTObjectType)) {
					$old_value = getDolGlobalInt('INFRASSEARCH_POS_'.strtoupper($module), 0);
					unset($listTObjectType[$module]);
					foreach ($listTObjectType as $key => $value) {
						$current_value	= getDolGlobalInt('INFRASSEARCH_POS_'.strtoupper($key), 0);
						if ($current_value > $old_value) {
							$res	= dolibarr_set_const($db, 'INFRASSEARCH_POS_'.strtoupper($key), $current_value - 1, 'chaine', 0, 'InfraSSearch module', $conf->entity);
							if ($res < 0) {
								setEventMessages($langs->trans('Error'), array('Error updating INFRASSEARCH_POS_'.strtoupper($key)), 'errors');
							}
						}
					}
					dolibarr_del_const($db, 'INFRASSEARCH_POS_'.strtoupper($module));
				}
			} elseif ($value == 1) {
				$res	= dolibarr_set_const($db, 'INFRASSEARCH_POS_'.strtoupper($module), count($listTObjectType) + 1, 'chaine', 0, 'InfraSSearch module', $conf->entity);
			}
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list	= array('Gen'	=> array('INFRASSEARCH_ORDER', 'INFRASSEARCH_NB_CAR', 'INFRASSEARCH_NB_SEC', 'INFRASSEARCH_NB_ROWS', 'INFRASSEARCH_NB_BREADCRUMB'));
		foreach ($listTObjectType as $TObjectType => $arrayForTObjectType) {
			$list['Gen'][]	= 'INFRASSEARCH_POS_'.strtoupper($TObjectType);
		}
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSSearch module', $conf->entity);
		}
	}
	//Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************
	//Sauvegarde / Restauration
	//Comportement général
	$listSort			= array ('ASC' => $langs->trans('InfraSSearchParamASC'), 'DESC' => $langs->trans('InfraSSearchParamDESC'));
	$listTObjectType	= array();
	$listTObjectType	= explode(',', getDolGlobalString('INFRASSEARCH_LISTTOBJECTTYPE', ''));
	$modules_valid		= array();
	$modules			= array();
	$modules_names		= array();
	$modules_picto		= array();
	$modules_key		= array();
	$modulesdir			= dolGetModulesDirs();
	// List of all modules that can be used in the search
	// Phase 1 : scan once each ACTIVE module descriptor (avoid reinstantiation per object type AND avoid side effects from disabled modules)
	$modulesCache		= array();
	foreach ($modulesdir as $dir) {	// Load modules attributes in arrays (name, numero, orders) from dir directory
		$handle	= @opendir(dol_osencode($dir));
		if (! is_resource($handle)) {
			continue;
		}
		while (($file = readdir($handle))!== false) {
			if (! is_readable($dir.$file) || substr($file, 0, 3) != 'mod' || substr($file, dol_strlen($file) - 10) != '.class.php') {
				continue;
			}
			$modName	= substr($file, 0, dol_strlen($file) - 10);
			if (empty($modName)) {
				continue;
			}
			// Filter on filename: only instantiate active modules (skip disabled modules to avoid constructor side effects)
			$estimatedValid	= strtolower(preg_replace('/^mod/i', '', $modName));
			$estimatedValid	= $estimatedValid == 'propale' ? 'propal' : $estimatedValid;
			$estimatedValid	= $estimatedValid == 'supplierproposal' ? 'supplier_proposal' : $estimatedValid;
			if (! in_array($estimatedValid, $conf->modules)) {
				continue;
			}
			$res	= include_once $dir.$file;
			if (! class_exists($modName)) {
				continue;
			}
			$objMod			= new $modName($db);
			$valid			= strtolower(preg_replace('/^mod/i', '', $objMod->name));
			$valid			= $valid == 'propale' ? 'propal' : $valid;
			$valid			= $valid == 'supplierproposal' ? 'supplier_proposal' : $valid;
			$modulesCache[]	= array('valid' => $valid, 'objMod' => $objMod);
		}
		closedir($handle);
	}
	// Phase 2 : match cached modules with each TObjectType (preserving listTObjectType order)
	foreach ($listTObjectType as $TObjectType) {
		foreach ($modulesCache as $cached) {
			$valid			= $cached['valid'];
			$objMod			= $cached['objMod'];
			$validmodule	= false;
			if ($valid == $TObjectType && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $valid == 'product' && in_array('service', $conf->modules) ? $objMod->getName().'/Services' : $objMod->getName();
				$modules[$TObjectType]	= $valid == 'knowledgemanagement' ? $langs->trans('InfraSSearchLibknowledgemanagement') : $modules[$TObjectType];
				$modules[$TObjectType]	= $modules[$TObjectType] == 'Propalehistory' ? $langs->trans('InfraSSearchLibPropalHist') : $modules[$TObjectType];
				$modules[$TObjectType]	= $modules[$TObjectType] == 'rmindr' ? $langs->trans('InfraSSearchLibrmindr') : $modules[$TObjectType];
				$modules[$TObjectType]	= $modules[$TObjectType] == 'factory' ? $langs->trans('InfraSSearchLibFactory') : $modules[$TObjectType];
				$modules[$TObjectType]	= $modules[$TObjectType] == 'knowledgemanagement' ? $langs->trans('InfraSSearchLibknowledgemanagement') : $modules[$TObjectType];
				$validmodule			= true;
			} elseif ($TObjectType == 'contact' && $valid == 'societe' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= 'Contacts '.$objMod->getName();
				$validmodule			= true;
			} elseif ($TObjectType == 'task' && $valid == 'projet' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= 'Tâches '.$objMod->getName();
				$validmodule			= true;
			} elseif ($TObjectType == 'commandefournisseur' && $valid == 'fournisseur' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= 'Commandes '.$objMod->getName();
				$validmodule			= true;
			} elseif ($TObjectType == 'facturefournisseur' && $valid == 'fournisseur' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= 'Factures '.$objMod->getName();
				$validmodule			= true;
			} elseif ($TObjectType == 'paymentlinks' && $valid == 'infras2bridge' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibinfras2bridge_paymentlinks');
				$validmodule			= true;
			} elseif ($TObjectType == 'ticket' && $valid == 'ticket' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibTicket');
				$validmodule			= true;
			} elseif ($TObjectType == 'time_basket' && $valid == 'infrastimebasket' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibInfraSTimeBasket');
				$validmodule			= true;
			} elseif ($TObjectType == 'adherent' && $valid == 'adherent' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibAdherent');
				$validmodule			= true;
			} elseif ($TObjectType == 'bank' && $valid == 'banque' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibAccount');
				$validmodule			= true;
			} elseif ($TObjectType == 'bom' && $valid == 'bom' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibBOM');
				$validmodule			= true;
			}  elseif ($TObjectType == 'chequereceipt' && $valid == 'banque' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->transnoentities('InfraSSearchLibRemiseCheque');
				$validmodule			= true;
			}elseif ($TObjectType == 'mrp' && $valid == 'mrp' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibMo');
				$validmodule			= true;
			} elseif ($TObjectType == 'holiday' && $valid == 'holiday' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibHoliday');
				$validmodule			= true;
			} elseif ($TObjectType == 'reception' && $valid == 'reception' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibReception');
				$validmodule			= true;
			} elseif ($TObjectType == 'recruitment' && $valid == 'recruitment' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibRecruitmentCandidature');
				$validmodule			= true;
			} elseif ($TObjectType == 'hrm' && $valid == 'hrm' && in_array($valid, $conf->modules)) {
				$modules[$TObjectType]	= $langs->trans('InfraSSearchLibEvaluation');
				$validmodule			= true;
			}
			if ($validmodule) {
				$modules_names[$TObjectType]	= $objMod->name;
				$modules_picto[$TObjectType]	= (isset($objMod->picto) && $objMod->picto) ? $objMod->picto : 'generic';
				$modules_key[$TObjectType]		= 'INFRASSEARCH_MOD_'.strtoupper($TObjectType);
			}
		}
	}
	// list of modul included in the search
	$validListTObjectType	= array();
	$stringListTObjectType	= '';
	$i						= 1;
	foreach($listTObjectType as $TObjectType) {
		$validTObjectType	= 'INFRASSEARCH_MOD_'.strtoupper($TObjectType);
		if (getDolGlobalInt($validTObjectType, 0) == 1) {
			$validListTObjectType[$TObjectType]	= array('select' => 'INFRASSEARCH_POS_'.strtoupper($TObjectType), 'value' => getDolGlobalInt('INFRASSEARCH_POS_'.strtoupper($TObjectType), $i));
			$i++;
		}
	}
	$stringListTObjectType	= http_build_query ($validListTObjectType, '', '-');	// écriture de la chaine en utilisant comme séparateur le tiret (-) pour pouvoir la récupérer comme valeur $_POST

	// View *****************************************
	$page_name				= $langs->trans('InfraSSearchSetupPages').' - '.$langs->trans('InfraSSearchParams');
	llxHeader('', $page_name);
	$linkback				= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption			= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head					= infrassearch_admin_prepare_head();
	$picto					= 'infrassearch@infrassearch';
	print dol_get_fiche_head($head, 'infrassearchsetup', $langs->trans('modcomnameSearch'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "infrassearch_tblPSexp";
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar) {
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie(cookieName))) {
							tblPSexp = $.cookie(cookieName);
						}
						$(".toggle_bloc").hide();
						if (tblPSexp != "") {
							$("[name=" + tblPSexp + "]").toggle();
						}
					});
					$(function () {
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) {
								$(".toggle_bloc").hide();
							} else {
								$(".toggle_bloc").hide();
								$(this).siblings().show();
							}
							$.cookie(cookieName, "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie(cookieName, $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrassearchScrollUp").css("right", "30px");
							} else {
								$(".infrassearchScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">
				<input type = "hidden" name = "stringListTObjectType"  value = "'.dol_escape_htmltag($stringListTObjectType).'">';
	//Sauvegarde / Restauration
	if ($accessright == 2)	infrassearch_print_backup_restore();
	//Comportement général
	if (!empty($accessright)) {
		$num	= 1;
		print '	<div class = "foldable">';
		print infrassearch_load_title('<span class = "infrassearchTitleparam">'.$langs->trans('InfraSSearchTitleComp').'</span>', $titleoption, dol_buildpath('/infrassearch/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
		print '		<table name = "tblGen" class = "infrassearchnoborder" width = "100%">';
		$metas	= array('30px', '*', '156px', '120px');
		infrassearch_print_colgroup($metas);
		$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
		infrassearch_print_liste_titre($metas);
		infrassearch_print_btn_action('Gen', '<span class = "infrassearchcaution">'.$langs->trans('InfraSSearchCaution').'</span> '.$langs->trans('InfraSSearchParamCautionSave'), 3);
		$num	= infrassearch_print_input('INFRASSEARCH_SORT', 'on_off', $langs->trans('InfraSSearchParamSort'), '', array(), 1, 1, '', $num);
		if (getDolGlobalString('INFRASSEARCH_SORT', '')) {
			$metas	= $form->selectarray('INFRASSEARCH_ORDER', $listSort, getDolGlobalString('INFRASSEARCH_ORDER', ''), 0, 0, 0, 'class = "infrassearchwidthquatrevingtdixpercent infrassearchnopadding infrassearchfontsizeinherit cursorpointer"');
			$num	= infrassearch_print_input('', 'select', $langs->trans('InfraSSearchParamOrder'), '', $metas, 1, 1, '', $num);
		}
		else {
			$num++;
		}
		$num	= infrassearch_print_input('INFRASSEARCH_ONLY_IN_ENTITY', 'on_off', $langs->trans('InfraSSearchParamOnlyInEntity'), '', array(), 1, 1, '', $num);
		$num	= infrassearch_print_input('INFRASSEARCH_SHOW_FIND_FIELD', 'on_off', $langs->trans('InfraSSearchParamShowFindField'), '', array(), 1, 1, '', $num);
		$num	= infrassearch_print_input('MAIN_USE_TOP_MENU_SEARCH_DROPDOWN', 'on_off', $langs->trans('InfraSSearchParamStdSearchTop'), '', array(), 1, 1, '', $num);
		if (!getDolGlobalString('MAIN_USE_TOP_MENU_SEARCH_DROPDOWN', '')) {
			$num	= infrassearch_print_input('INFRASSEARCH_REPLACE_STD', 'on_off', $langs->trans('InfraSSearchParamReplaceStd'), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infrassearch_print_input('INFRASSEARCH_ON_TOP_MENU', 'on_off', $langs->trans('InfraSSearchParamOnTopMenu'), '', array(), 1, 1, '', $num);
		if (getDolGlobalString('INFRASSEARCH_REPLACE_STD', '') || getDolGlobalString('INFRASSEARCH_ON_TOP_MENU', '')) {
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '15');
			$num	= infrassearch_print_input('INFRASSEARCH_NB_CAR', 'input', $langs->trans('InfraSSearchParamNbCaracAvRech'), '', $metas, 1, 1, '', $num);
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '200', 'max' => '5000');
			$num	= infrassearch_print_input('INFRASSEARCH_NB_SEC', 'input', $langs->trans('InfraSSearchParamNbSecAvRech'), '', $metas, 1, 1, '', $num);
		} else {
			$num += 2;
		}
		$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '3', 'max' => '20');
		$num	= infrassearch_print_input('INFRASSEARCH_NB_ROWS', 'input', $langs->trans('InfraSSearchParamNbRows'), '', $metas, 1, 1, '', $num);
		infrassearch_print_hr(3);
		$num	= infrassearch_print_input('INFRASSEARCH_BREADCRUMB', 'on_off', $langs->trans('InfraSSearchParamBreadCrumb'), '', array(), 1, 1, '', $num);
		if (getDolGlobalString('INFRASSEARCH_BREADCRUMB', '')) {
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '5', 'max' => '30');
			$num	= infrassearch_print_input('INFRASSEARCH_NB_BREADCRUMB', 'input', $langs->trans('InfraSSearchParamNbBreadCrumbs'), '', $metas, 1, 1, '', $num);
		} else {
			$num++;
		}
		infrassearch_print_hr(3);
		foreach($modules_names as $numero => $name) {
			$text1	= preg_match('/^\//',$modules_picto[$numero]) ? img_picto('', $modules_picto[$numero], 'width = "14px"', 1) : img_object('', $modules_picto[$numero], 'width = "14px"');
			$text2	= ' '.$langs->trans('InfraSSearchParamActiveSearch', $modules[$numero]);
			$metas	= array('stringListTObjectType' => $stringListTObjectType);
			$num	= infrassearch_print_input($modules_key[$numero], 'on_off', $text1.$text2, '', $metas, 1, 1, '', $num);
			if (getDolGlobalInt($modules_key[$numero], 0)) {	// If module is active for search
				$text3	= ' '.$langs->trans('InfraSSearchParamPosModule', $modules[$numero]);
				$metas	= array('validListTObjectType' => $validListTObjectType, 'arrayTObjectType' => $validListTObjectType[$numero]);
				$num	= infrassearch_print_input('', 'selectpos', $text1.$text3, '', $metas, 1, 1, '', $num);
			} else {
				$num++;
			}
		}
		print '		</table>
				</div>';
	}
	print '	</form>
				<a class = "infrassearchScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
