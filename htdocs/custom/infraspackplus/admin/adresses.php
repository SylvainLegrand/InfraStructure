<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand 			- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina 	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/admin/adresses.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup adresses for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'companies', 'errors', 'dict', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infraspackplus', 'paramAdresses')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form						= new Form($db);
	$formfile					= new FormFile($db);
	$formother					= new FormOther($db);
	$formcompany				= new FormCompany($db);
	$object						= new Societe($db);
	$address					= new Address($db);
	$btnAction					= 'value = "add" name = "add">'.$langs->trans('Add');
	$confirm_mesg				= '';
	$action						= GETPOST('action', 'alpha');
	$confirm					= GETPOST('confirm', 'alpha');
	$listElemtypeExfFreeLivr	= array('propal', 'commande', 'fichinter', 'expedition', 'facture', 'project', 'supplier_proposal', 'commande_fournisseur', 'facture_fourn');
	$listParamsExfFreeLivr		= array('type'				=> 'html',
										'pos'				=> 49,
										'size'				=> '2000',
										'unique'			=> 0,
										'required'			=> 0,
										'default_value'		=> '',
										'param'				=> 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',
										'alwayseditable'	=> 1,
										'perms'				=> '',
										'list'				=> '3',
										'help'				=> '',
										'computed'			=> '',
										'entity'			=> $conf->entity,
										'langfile'			=> 'infraspackplus@infraspackplus',
										'enabled'			=> '1',
										'totalizable'		=> 0,
										'printable'			=> 2
										);
	$result						= '';
	// Update with multicompany management
	if ($action == 'copyParams') {
		$sourceEntity	= GETPOSTINT('entity');
		$res			= infraspackplus_copy_entity( $sourceEntity, $conf->entity);
		if ($res < 0) {
			setEventMessage($langs->trans('InfraSPackPlusErrorFailedToCopyParameters',$sourceEntity, $conf->entity),'errors');
		} else {
			setEventMessage($langs->trans('InfraSPackPlusParametersCopiedFromEntity', $sourceEntity), 'mesgs');
			header('Location: '.$_SERVER['PHP_SELF']);
			exit;
		}
	}
	infraspackplus_test_new_fields('infraspackplus');
	// Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infraspackplus_bkup_module ('infraspackplus');
	}
	if ($action == 'restoreParams') {
		$result	= infraspackplus_restore_module ('infraspackplus');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Opt'	=> array('INFRASPLUS_PDF_CUSTOMER_ADDR_SELECT', 'INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT', 'INFRASPLUS_PDF_FREE_LIVR_EXF', 'INFRASPLUS_PDF_TYPE_SOUS_TRAITANT'));
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			if ($constname == 'INFRASPLUS_PDF_FREE_LIVR_EXF') {
				if (!empty($constvalue) && infraspackplus_check_extf_name ($constvalue) < 0) {
					$result = 0;
					continue;
				}
				$name	= getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', '');
				if (empty($constvalue) || (!empty($name) && $name != $constvalue)) {
					$result	= infraspackplus_search_extf (-2, '', 'INFRASPLUS_PDF_FREE_LIVR_EXF', 'InfraSPlusParamLabelExfFreeAddrLivr', $listElemtypeExfFreeLivr, $listParamsExfFreeLivr);
				}
			}
			$result	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	if (GETPOST('cancel')) {
		header('Location: '.$_SERVER['PHP_SELF']);
	}
	if ($action == 'edit' && class_exists('Address')) {
		$address->fetch(GETPOST('id', 'int'));
		$btnAction	= 'value = "save" name = "save">'.$langs->trans('Save');
		print '	<script type = "text/javascript">
					document.cookie = "tblAexp=tblGA-3; expires=1; path=/";
				</script>';
	}
	if (($action == 'add' || GETPOST('save')) && !GETPOST('cancel')) {
		$parms_ok	= true;
		if (GETPOST('label', 'alpha') == '' || GETPOST('label', 'alpha') == $langs->trans('RequiredField')) {
			$parms_ok	= false;
			setEventMessages($langs->trans('InfraSPlusParamAliasIsRequired'), null, 'errors');
		}
		if (GETPOST('name', 'alpha') == '' || GETPOST('label', 'alpha') == $langs->trans('RequiredField')) {
			$parms_ok	= false;
			setEventMessages($langs->trans('InfraSPlusParamNameIsRequired'), null, 'errors');
		}
		$address->label			= GETPOST('label', 'alphanohtml');
		$address->name			= GETPOST('name', 'alphanohtml');
		$address->address		= GETPOST('address', 'alphanohtml');
		$address->zip			= GETPOST('zipcode', 'alphanohtml');
		$address->town			= GETPOST('town', 'alphanohtml');
		$address->country_id	= GETPOST('country_id', 'int') ? GETPOST('country_id', 'int') : $mysoc->country_id;
		$address->phone			= GETPOST('phone', 'alpha');
		$address->fax			= GETPOST('fax', 'alpha');
		$address->note			= GETPOST('note', 'none');
		$address->email			= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
		$address->url			= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
		if (GETPOST('save') && $parms_ok) {
			$result_update	= $address->update(GETPOST('id', 'int'), $user);
			if ($result_update < 0) {
				setEventMessages($langs->trans('InfraSPlusParamErrorSavingAddress'), $address->error, 'errors');
			} else {
				setEventMessages($langs->trans('InfraSPlusParamAddressUpdated'), null, 'mesgs');
			}
		}
		if (($action == 'add' && $parms_ok) && !GETPOST('save')) {
			$res	= $address->fetch('', 0, GETPOST('label', 'alpha'));
			if ($res === 1) {
				setEventMessages($langs->trans('InfraSPlusParamAddressAlredyExists'), null, 'errors');
			} else {
				$address->socid	= 0;
				$result_insert	= $address->create($user);
				if ($result_insert < 0) {
					setEventMessages($langs->trans('InfraSPlusParamErrorSavingAddress'), $address->error, 'errors');
				} else {
					setEventMessages($langs->trans('InfraSPlusParamAddressSaved'), null, 'mesgs');
				}
			}
		}
	}
	if ($action == 'defaultL') {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_DEFAULT_ADDR_DELIV', GETPOST('defaultaddrdeliv'),'chaine',0,'',$conf->entity);
	}
	if ($action == 'delete') {
		$confirm_mesg	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.GETPOST('id', 'int'), $langs->trans('InfraSPlusParamDeleteAddress'), $langs->trans('InfraSPlusParamConfirmDeleteAddress'), 'delete_ok', '', 1, (int) $conf->use_javascript_ajax);
	}
	if ($action == 'delete_ok' && $confirm == 'yes') {
		$result_supp			= $address->delete(GETPOST('id', 'int'));
		if ($result_supp < 0) {
			setEventMessages($langs->trans('InfraSPlusParamErrorDeletingAddress'), null, 'errors');
		} else {
			setEventMessages($langs->trans('InfraSPlusParamDeleted'), null, 'mesgs');
		}
	}
	if ($action == 'setExfAddrLivr') {
		$result	= infraspackplus_search_extf (1, '', 'INFRASPLUS_PDF_FREE_LIVR_EXF', 'InfraSPlusParamLabelExfFreeAddrLivr', $listElemtypeExfFreeLivr, $listParamsExfFreeLivr);
	}
	// Retour => message Ok ou Ko
	if ($result >= 1) {
		setEventMessages($langs->trans('SetupSaved').($action == 'setExfAddrLivr' ? ' ('.$result.')' : ''), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************
	$result_show		= $address->fetch_lines(0, -1);
	$fckeditor			= isModEnabled('fckeditor') ? 1 : 0;
	$fckeditorEnable	= getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS', 0);
	$result				= dolibarr_set_const($db, 'SOCIETE_ADDRESSES_MANAGEMENT',				dolibarr_get_const($db, 'INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', $conf->entity), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	$result				= dolibarr_set_const($db, 'INFRASPACKPLUS_PS_ACTIVE_SOCIETE_CONTACT',	dolibarr_get_const($db, 'INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', $conf->entity), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	$customerAddrSelect	= getDolGlobalString('INFRASPLUS_PDF_CUSTOMER_ADDR_SELECT', 'T');
	$noCountryCode		= (empty($mysoc->country_code) ? true : false);
	if (empty($noCountryCode)) {
		$pid1	= $langs->transcountry("ProfId1", $mysoc->country_code);
		if ($pid1 == '-') {
			$pid1	= false;
		}
		$pid2	= $langs->transcountry("ProfId2", $mysoc->country_code);
		if ($pid2 == '-') {
			$pid2	= false;
		}
		$pid3	= $langs->transcountry("ProfId3", $mysoc->country_code);
		if ($pid3 == '-') {
			$pid3	= false;
		}
		$pid4	= $langs->transcountry("ProfId4", $mysoc->country_code);
		if ($pid4 == '-') {
			$pid4	= false;
		}
		$pid5	= $langs->transcountry("ProfId5", $mysoc->country_code);
		if ($pid5 == '-') {
			$pid5	= false;
		}
	} else {
		$pid1	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("CompanyCountry")).'</span>';
		$pid2	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("CompanyCountry")).'</span>';
		$pid3	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("CompanyCountry")).'</span>';
		$pid4	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("CompanyCountry")).'</span>';
		$pid5	= img_warning().' <span class = "error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("CompanyCountry")).'</span>';
	}
	$specialHead		= infraspackplus_fetchAllSpecialHeads();
	$typeSsT			= getDolGlobalString('INFRASPLUS_PDF_TYPE_SOUS_TRAITANT', '');
	$exfFreeLivrIsSet	= infraspackplus_search_extf (0, '', 'INFRASPLUS_PDF_FREE_LIVR_EXF', 'InfraSPlusParamLabelExfFreeAddrLivr', $listElemtypeExfFreeLivr, $listParamsExfFreeLivr);
	$textDescFreeLivr	= $langs->trans('InfraSPlusParamSetExf', getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', ''));
	$descFreeLivr		= $langs->trans('InfraSPlusParamFreeLivrExf').($exfFreeLivrIsSet == 0 ? ' <button class = "button infrasplusnopadding infrasplusheight20" type = "submit" value = "setExfAddrLivr" name = "action">'.$textDescFreeLivr.'</button>': '');

	// View *****************************************
	$page_name			= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsAdresses');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback			= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head				= infraspackplus_admin_prepare_head();
	$picto				= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'adresses', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					jQuery(document).ready(function() {
						var tblAexp = "";
						$.isSet = function(testVar){
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie("tblAexp"))) {
							tblAexp = $.cookie("tblAexp");
						}
						$(".toggle_bloc").hide();
						if (tblAexp != "") {
							$("[name=" + tblAexp + "]").toggle();
						}
						$("#label").focus(function() {
							hideMessage("label","'.$langs->trans('RequiredField').'");
						});
						$("#label").blur(function() {
							displayMessage("label","'.$langs->trans('RequiredField').'");
						});
						$("#name").focus(function() {
							hideMessage("name","'.$langs->trans('RequiredField').'");
						});
						$("#name").blur(function() {
							displayMessage("name","'.$langs->trans('RequiredField').'");
						});
						displayMessage("label","'.$langs->trans('RequiredField').'");
						displayMessage("name","'.$langs->trans('RequiredField').'");
						$("#label").css("color","grey");
						$("#name").css("color","grey");
					});
					$(function () {
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) {
								$(".toggle_bloc").hide();
							} else {
								$(".toggle_bloc").hide();
								$(this).siblings().show();
							}
							$.cookie("tblAexp", "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie("tblAexp", $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 )	{
								$(".infrasplusScrollUp").css("right", "30px");
							} else {
								$(".infrasplusScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data" name = "formsoc">
				<input type = "hidden" name = "token" value = "'.newToken().'">
				<input type = "hidden" name = "action" value = "add"/>
				<input type = "hidden" name = "id" value = "'.$address->id.'"/>';
	//Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
	}
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamGestionAdresses').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/corp.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblGA-3" class = "noborder toggle_bloc" width = "100%">';
	$metas	= array('125', '400px', '125px', '*', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(5), 'InfraSPlusParamNewAdresse');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		print '			<tr>
							<td class = "fieldrequired"><label for = "label">'.$langs->trans('InfraSPlusParamAdressAlias').'</label></td>
							<td><input type = "text" class = "minwidth300 infrasplusnopadding infrasplusnomargin" id = "label" name = "label" value="'.($address->label ? $address->label : $langs->trans('RequiredField')).'"></td>
							<td class = "fieldrequired"><label for = "name">'.$langs->trans('InfraSPlusParamAdressName').'</label></td>
							<td><input type = "text" class = "minwidth300 infrasplusnopadding infrasplusnomargin" id = "name" name = "name" value = "'.($address->name ? $address->name : $langs->trans('RequiredField')).'"></td>
							<td rowspan = "7" align="center">
								<button class = "button infraspluswidth110" type = "submit" '.$btnAction.'</button>
								<br/><br/>
								<button class = "button infraspluswidth110" type = "submit" value = "cancel" name = "cancel">'.$langs->trans('Cancel').'</button>
							</td>
						</tr>';
		print '			<tr>
							<td class = "tdtop"><label for = "address">'.$langs->trans('Address').'</label></td>
							<td colspan = "3"><textarea name = "address" id = "address" class = "quatrevingtpercent" rows = "3" wrap = "soft">'.$address->address.'</textarea></td>
						</tr>';
		print '			<tr>
							<td><label for = "zipcode">'.$langs->trans('Zip').'</label></td>
							<td>'.$formcompany->select_ziptown($address->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6).'</td>
							<td><label for = "town">'.$langs->trans('Town').'</label></td>
							<td>'.$formcompany->select_ziptown($address->town, 'town', array('zipcode', 'selectcountry_id', 'state_id')).'</td>
						</tr>';
		print '			<tr>
							<td><label for = "selectcounty_id">'.$langs->trans('Country').'</label></td>
							<td colspan = "3">'.$form->select_country((empty($address->country_id) ? $mysoc->country_id : $address->country_id), 'country_id').info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'),1).'</td>
						</tr>';
		print '			<tr>
							<td><label for = "phone">'.$langs->trans('Phone').'</label></td>
							<td><input type = "text" class = "minwidth300 infrasplusnopadding infrasplusnomargin" id = "phone" name = "phone" value = "'.$address->phone.'"></td>
							<td><label for = "fax">'.$langs->trans('Fax').'</label></td>
							<td><input type = "text" class = "minwidth300 infrasplusnopadding infrasplusnomargin" id = "fax" name = "fax" value = "'.$address->fax.'"></td>
						</tr>';
		print '			<tr>
							<td><label for = "email">'.$langs->trans('Email').'</label></td>
							<td ><input type = "text" class = "minwidth300 infrasplusnopadding infrasplusnomargin" id = "email" name = "email" value = "'.$address->email.'"></td>
							<td><label for = "url">'.$langs->trans('Web').'</label></td>
							<td ><input type = "text" class = "minwidth300 infrasplusnopadding infrasplusnomargin" id = "url" name = "url" value = "'.$address->url.'"></td>
						</tr>';
		print '			<tr>
							<td class = "tdtop"><label for = "note">'.$langs->trans('Note').'</label></td>
							<td colspan = "3">';
		if (!empty($fckeditor)) {
			$doleditor	= new DolEditor('note', $address->note, '', 80, 'dolibarr_notes');
			print $doleditor->Create();
		} else {
			print '				<textarea name = "note" id = "note" class = "quatrevingtpercent" rows = "6" wrap = "soft">'.$address->note.'</textarea>';
		}
		print '				</td>
						</tr>';
		print '			<tr class = "oddeven">
							<td colspan = "4">
								<label for = "defaultaddrdeliv">'.$langs->trans('InfraSPlusParamDefaultAddrDeliv').'</label>
								<select name = "defaultaddrdeliv" class = "select2-choice infrasplusnopadding infrasplusnomargin cursorpointer">
									<option name = "defaultaddrdeliv" value = "">'.$langs->trans('InfraSPlusParamNoAddrDeliv').'</option>';
		$selected_addr	= getDolGlobalString('INFRASPLUS_PDF_DEFAULT_ADDR_DELIV', '');
		foreach ($address->lines as $lineaddress) {
			print '					<option name = "defaultaddrdeliv" value = "'.$lineaddress->id.'"';
			if ($selected_addr === $lineaddress->id) {
				print ' selected';
			}
			print '					>'.$lineaddress->name.' ('.$lineaddress->label.')</option>';
		}
		print '					</select>
							</td>
							<td align="center"><button class = "button infraspluswidth110" type = "submit" value = "defaultL" name = "action">'.$langs->trans('Validate').'</button></td>
						</tr>';
	}
	print '			</table>
				</div>';
	if (isModEnabled('adressefrance')) {
		print '<script src="'.dol_buildpath('/adressefrance/js/search.js', 1).'"></script>';
	}
	print '	</form>';
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">
				<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamAddressesForMyCompany').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/list.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblLA-3" class = "noborder toggle_bloc" width = "100%">
						<tr class = "liste_titre">
							<td>'.$langs->trans('InfraSPlusParamAdressAlias').'</td>
							<td>'.$langs->trans('InfraSPlusParamAdressName').'</td>
							<td>'.$langs->trans('Address').'</td>
							<td>'.$langs->trans('Country').'</td>
							<td>'.$langs->trans('Email').'</td>
							<td>'.$langs->trans('Web').'</td>
							<td colspan = "2">&nbsp;</td>
						</tr>';
	if (!empty($accessright)) {
		if ($result_show > 0) {
			foreach ($address->lines as $lineaddress) {
				print '	<tr class = "oddeven">
							<td>'.$lineaddress->label.'</td>
							<td>'.$lineaddress->name.'</td>
							<td>'.$lineaddress->address.' - '.$lineaddress->zip.' '.$lineaddress->town.'</td>
							<td>'.$lineaddress->country.'</td>
							<td>'.$lineaddress->email.'</td>
							<td>'.$lineaddress->url.'</td>
							<td><a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=edit&id='.$lineaddress->id.'" class = "deletefilelink">'.img_edit().'</a></td>
							<td><a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=delete&id='.$lineaddress->id.'&token='.newToken().'" class = "deletefilelink">'.img_delete().'</a></td>
						</tr>';
			}
		}
		infraspackplus_print_final(8);
	}
	print '			</table>
				</div>
			</form>
			<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype="multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">
				<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamAddressesSetup').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblOPT-3" class = "noborder toggle_bloc" width = "100%">';
	$metas	= array('30px', '*', '170px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Opt', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_CUSTOM_COUNTRY_ADDR', 'on_off', $langs->trans('InfraSPlusParamUseCustomCountryAddr'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_COUNTRY', 'on_off', $langs->trans('InfraSPlusParamWithCountry'), '', array(), 1, 1, '', $num);
		// $num = 3
		if (empty(getDolGlobalString('INFRASPLUS_PDF_HIDE_RECEP_FRAME', '')) || !empty($specialHead['frameinfos'])) {
			infraspackplus_print_hr(3);
			if (empty(getDolGlobalString('MAIN_PDF_DISABLESOURCEDETAILS', ''))) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_STATUS_WITH_SENDER_NAME', 'on_off', $langs->trans('InfraSPlusParamshowStatusWithSenderName'), '', array(), 1, 1, '', $num);
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_EMET_DETAILS', 'on_off', $langs->trans('InfraSPlusParamshowEmetFDetails'), '', array(), 1, 1, '', $num);
				if (!empty(getDolGlobalString('INFRASPLUS_PDF_SHOW_EMET_DETAILS', ''))) {
					$metas		= array();
					$metas[0]	= array($langs->trans('Phone'), $langs->trans('Fax'), $langs->trans('Email'), $langs->trans('WebSite'));
					$metas[1]	= array('INFRASPLUS_PDF_SOURCE_DETAIL_PHONE'	=> '',
										'INFRASPLUS_PDF_SOURCE_DETAIL_FAX'		=> '',
										'INFRASPLUS_PDF_SOURCE_DETAIL_MAIL'		=> '',
										'INFRASPLUS_PDF_SOURCE_DETAIL_WEB'		=> '');
					$num		= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamSourceDetailsList'), $metas, 2, 100, '', $num);
				} else {
					$num++;
				}
			} else {
				$num	+= 3;
			}
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TVAINTRA_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowTvaIntraInSourceAddress'), '', array(), 1, 1, '', $num);
			if (!empty($pid1)) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID1_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid1, '', array(), 1, 1, '', $num);
			} else {
				$num++;
			}
			if (!empty($pid2)) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID2_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid2, '', array(), 1, 1, '', $num);
			} else {
				$num++;
			}
			if (!empty($pid3)) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID3_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid3, '', array(), 1, 1, '', $num);
			} else {
				$num++;
			}
			if (!empty($pid4)) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID4_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid4, '', array(), 1, 1, '', $num);
			} else {
				$num++;
			}
			if (!empty($pid5)) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROFID5_IN_SOURCE_ADDRESS', 'on_off', $langs->trans('ShowProfIdInSourceAddress').' - '.$pid5, '', array(), 1, 1, '', $num);
			} else {
				$num++;
			}
			infraspackplus_print_hr(3);
		} else {
			$num	+= 9;
		}
		// $num = 12
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_STATUS_WITH_CLIENT_NAME', 'on_off', $langs->trans('InfraSPlusParamshowStatusWithClientName'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_PARENT_ADDR_FACT', 'on_off', $langs->trans('InfraSPlusParamFactureParentAddrFact'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SENDER_ALIAS', 'on_off', $langs->trans('InfraSPlusParamShowSenderAlias').' '.$langs->trans('InfraSPlusGenModif'), '', array(), 1, 1, '', $num);
		$metas	= $form->selectarray('INFRASPLUS_PDF_CUSTOMER_ADDR_SELECT', array('T' => 'InfraSPlusParamThirdpartyAddr', 'C' => 'InfraSPlusParamContactAddr', 'B' => 'InfraSPlusParamThirdpartyBothAddr', 'A' => 'InfraSPlusParamContactBothAddr'), $customerAddrSelect, 0, 0, 0, '', 1, 0, 0, '', 'quatrevingtpercent');
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamCustomerAddrSelect'), '', $metas, 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT', 'input', $langs->trans('InfraSPlusParamCodeAddrFact1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCodeAddrFact2'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SYST', 'on_off', $langs->trans('InfraSPlusParamAddrLivrSyst'), '', array(), 1, 1, '', $num);
		// $num = 18
		if (!getDolGlobalString('INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SYST', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SI_FACT', 'on_off', $langs->trans('InfraSPlusParamAddrLivrSiFact'), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 19
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON', 'on_off', $langs->trans('InfraSPlusParamShowAdrLivr'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', 'on_off', $langs->trans('InfraSPlusParamShowAdrRecep'), '', array(), 1, 1, '', $num);
		// $num = 21
		if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_LIVR_DETAILS', 'on_off', $langs->trans('InfraSPlusParamshowLivrFDetails'), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS', '') && (getDolGlobalString('INFRASPLUS_PDF_SHOW_LIVR_DETAILS', ''))) {
			$metas		= array();
			$metas[0]	= array($langs->trans('Phone'), $langs->trans('Fax'), $langs->trans('Email'), $langs->trans('WebSite'));
			$metas[1]	= array('INFRASPLUS_PDF_TARGET_LIVR_DETAIL_PHONE'	=> '',
								'INFRASPLUS_PDF_TARGET_LIVR_DETAIL_FAX'		=> '',
								'INFRASPLUS_PDF_TARGET_LIVR_DETAIL_MAIL'	=> '',
								'INFRASPLUS_PDF_TARGET_LIVR_DETAIL_WEB'		=> '');
			$num		= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamTargetLivrDetailsList'), $metas, 2, 100, '', $num);
		} else {
			$num++;
		}
		if (getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_LIVRAISON') || getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 'on_off', $langs->trans('InfraSPlusParamUseDoliAdrLivr'), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 24
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FREE_LIVR_EXF', 'input', $descFreeLivr, '', array(), 1, 1, '', $num);
		if (!getDolGlobalString('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_ADRESSE_LIVRAISON_MIXTE', 'on_off', $langs->trans('InfraSPlusParamAdrLivrMixte'), '', array(), 1, 1, '', $num);
			$num++;
		} else {
			$num++;
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 'on_off', $langs->trans('InfraSPlusParamDoliAdrLivrRecep'), '', array(), 1, 1, '', $num);
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_DOLI_ADRESSE_FACTURATION', 'on_off', $langs->trans('InfraSPlusParamUseDoliAdrFact'), '', array(), 1, 1, '', $num);
		// $num = 28
		if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_RECEP_DETAILS', 'on_off', $langs->trans('InfraSPlusParamshowRecepFDetails'), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS', '') && getDolGlobalString('INFRASPLUS_PDF_SHOW_RECEP_DETAILS', '')) {
			$metas		= array();
			$metas[0]	= array($langs->trans('Phone'), $langs->trans('Fax'), $langs->trans('Email'), $langs->trans('WebSite'));
			$metas[1]	= array('INFRASPLUS_PDF_TARGET_DETAIL_PHONE'	=> '',
								'INFRASPLUS_PDF_TARGET_DETAIL_FAX'		=> '',
								'INFRASPLUS_PDF_TARGET_DETAIL_MAIL'		=> '',
								'INFRASPLUS_PDF_TARGET_DETAIL_WEB'		=> '');
			$num		= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamTargetDetailsList'), $metas, 2, 100, '', $num);
		} else {
			$num++;
		}
		// $num = 30
		if (isModEnabled('customlink')) {
			infraspackplus_print_hr(3);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT', 'on_off', $langs->trans('InfraSPlusParamAdrTiersSsT'), '', array(), 1, 1, '', $num);
			if (!empty(getDolGlobalString('INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT', ''))) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_SOUS_TRAITANT', 'selectTypeContact', $langs->trans('InfraSPlusParamTypeContactSsT'), '', array($object, $typeSsT, 'external', 'position', 1, 'minwidth100imp'), 1, 1, '', $num);
			} else {
				$num++;
			}
		} else {
			$num	+= 2;
		}
		// $num = 32
	}
	print '			</table>
				</div>
			</form>
			<a class = "infrasplusScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
