<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand			- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/admin/images.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup pictures for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'companies', 'errors', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infraspackplus', 'paramImages')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form				= new Form($db);
	$formfile			= new FormFile($db);
	$formother			= new FormOther($db);
	$confirm_mesg		= '';
	$action				= GETPOST('action', 'alpha');
	$confirm			= GETPOST('confirm', 'alpha');
	$urlfile			= GETPOST('urlfile', 'alpha');
	$result				= '';
	$logodir			= !empty($conf->mycompany->multidir_output[$conf->entity])	? $conf->mycompany->multidir_output[$conf->entity]	: $conf->mycompany->dir_output;
	$use_iso_location	= getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION', 0);
	$hlogo				= getDolGlobalInt('MAIN_DOCUMENTS_LOGO_HEIGHT', 10);
	$maxhlogo			= ($use_iso_location ? 28 : 50);
	$hlogo				= ($hlogo > $maxhlogo ? $maxhlogo : $hlogo);
	dolibarr_set_const($db, "MAIN_DOCUMENTS_LOGO_HEIGHT", $hlogo, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
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
	//Sauvegarde / Restauration
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
		if ($confkey == 'INFRASPLUS_PDF_PICTURE_IN_REF' && getDolGlobalString('INFRASPLUS_PDF_PICTURE_IN_REF', '')) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_AFTER',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_UNDER',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'INFRASPLUS_PDF_PICTURE_AFTER' && getDolGlobalString('INFRASPLUS_PDF_PICTURE_AFTER', '')) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_IN_REF',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_UNDER',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'INFRASPLUS_PDF_PICTURE_UNDER' && getDolGlobalString('INFRASPLUS_PDF_PICTURE_UNDER', '')) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_IN_REF',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_AFTER',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('Img'	=> array('MAIN_DOCUMENTS_LOGO_HEIGHT',			'INFRASPLUS_PDF_LOGO_SMALL_HEAD_HEIGHT',	'INFRASPLUS_PDF_PICTURE_FOOT_WIDTH',
											'INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT',	'INFRASPLUS_PDF_LINK_PICTURE_URL',			'INFRASPLUS_PDF_PICTURE_PADDING',
											'INFRASPLUS_PDF_PICTURE_WIDTH',			'INFRASPLUS_PDF_PICTURE_HEIGHT',			'INFRASPLUS_PDF_T_WATERMARK_OPACITY',
											'INFRASPLUS_PDF_I_WATERMARK_OPACITY',	'INFRASPLUS_PDF_SIGNATURE_EMET_WIDTH'
											)
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}

	if($action == 'add') {
		$extension	= pathinfo($_FILES['InfraSPlusParamLogoFile']['name'], PATHINFO_EXTENSION);
		$dest_file	= GETPOST('InfraSPlusParamLogoName', 'alpha').'.'.mb_strtolower($extension);
		$dest_path	= $logodir.'/logos/';
		$moved		= dol_move_uploaded_file($_FILES['InfraSPlusParamLogoFile']['tmp_name'], $dest_path.$dest_file, 1, 0, $_FILES['InfraSPlusParamLogoFile']['error']);
		if ($moved > 0) {
			if ($isimage = image_format_supported($dest_file) === 1) {
				$imgThumbSmall	= vignette($dest_path.$dest_file, $maxwidthsmall, $maxheightsmall, '_mini', $quality);
				$imgThumbSmall	= vignette($dest_path.$dest_file, $maxwidthsmall, $maxheightsmall, '_small', $quality);
			}
			setEventMessages($dest_file.' : '.$langs->trans('FileSaved'), array(), 'mesgs');
		} else if ($moved !== 1) {	// errors
			if ($moved < 0) {
				setEventMessages('UknownFileUploadError', array(), 'errors');	// API documented error
			} else {
				setEventMessages($moved, array(), 'errors');	// We got an error string /o\
			}
		}
	}
	if ($action == 'defaultP') {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_IMAGE_FOOT', GETPOST('defaultpied'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if ($action == 'defaultW') {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_IMAGE_WATERMARK', GETPOST('defaultwatermark'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if ($action == 'defaultWUST') {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USER_STICKER_IMAGE_WATERMARK', GETPOST('defaultwatermarkust'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if ($action == 'defaultH') {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_LOGO_HEADER_2', GETPOST('defaultheader'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if ($action == 'defaultS') {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_SIGNATURE_EMET', GETPOST('defaultsignemet'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (((float) DOL_VERSION <= 14.0 && $action == 'delete') || ((float) DOL_VERSION >= 15.0 && $action == 'deletefile')) {
		$confirm_mesg	= $form->formconfirm($_SERVER['PHP_SELF'].'?urlfile='.$urlfile, $langs->trans('InfraSPlusParamDeleteAFile'), $langs->trans('InfraSPlusParamConfirmDeleteAFile').' '.$urlfile.' ?', 'delete_ok', '', 1, (int) $conf->use_javascript_ajax);
	}
	if ($action == 'delete_ok' && $confirm == 'yes') {
		$urlfile_dirname	= pathinfo($urlfile, PATHINFO_DIRNAME);
		$urlfile_filename	= pathinfo($urlfile, PATHINFO_FILENAME);
		$urlfile_ext		= pathinfo($urlfile, PATHINFO_EXTENSION);
		$urlfile_small		= $urlfile_filename.'_small.'.$urlfile_ext;
		$urlfile_mini		= $urlfile_filename.'_mini.'.$urlfile_ext;
		$a					= dol_delete_file($logodir.'/'.$urlfile, 1);
		$b					= dol_delete_file($logodir.'/logos/thumbs/'.$urlfile_small, 1);
		$c					= dol_delete_file($logodir.'/logos/thumbs/'.$urlfile_mini, 1);
		if (!empty($a) && !empty($b) && !empty($c)) {
			setEventMessages($urlfile_filename.'.'.$urlfile_ext.' '.$langs->trans('Deleted'), array(), 'mesgs');
		} else {
			setEventMessages($langs->trans('ErrorFailToDeleteFile', $urlfile), array(), 'errors');
		}
	}

	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), array(), 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), array(), 'errors');
	}

	// init variables *******************************
	$logos				= array();
	foreach (glob($logodir.'/logos/*.jpg') as $file) {
		$logos[]	= dol_basename($file);
	}
	foreach (glob($logodir.'/logos/*.jpeg') as $file) {
		$logos[]	= dol_basename($file);
	}
	foreach (glob($logodir.'/logos/*.gif') as $file) {
		$logos[]	= dol_basename($file);
	}
	foreach (glob($logodir.'/logos/*.png') as $file) {
		$logos[]	= dol_basename($file);
	}
	$selected_logo			= getDolGlobalString('INFRASPLUS_PDF_IMAGE_FOOT', '');
	$selected_watermark		= getDolGlobalString('INFRASPLUS_PDF_IMAGE_WATERMARK', '');
	$selected_watermark_UST	= getDolGlobalString('INFRASPLUS_PDF_USER_STICKER_IMAGE_WATERMARK', '');
	$selected_header		= getDolGlobalString('INFRASPLUS_PDF_LOGO_HEADER_2', '');
	$selected_signemet		= getDolGlobalString('INFRASPLUS_PDF_SIGNATURE_EMET', '');
	$noMyLogo				= getDolGlobalString('PDF_DISABLE_MYCOMPANY_LOGO', '0');
	$disabledLogoHeight		= empty($noMyLogo) ? 'enabled' : 'disabled';
	if (getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '') && getDolGlobalString('INFRASPLUS_PDF_PICTURE_IN_REF', '')) {
		$picture_width	= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_REF', 28);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_PICTURE_WIDTH', $picture_width, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$modifWidth		= ' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamPictureInRef2');
	} else {
		$modifWidth	= '';
	}
	if (!getDolGlobalString('INFRASPLUS_PDF_LOGO_SMALL_HEAD_HEIGHT', '')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_LOGO_SMALL_HEAD_HEIGHT', '6', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$rowInnerSpan	= getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '') ? 5 : 4;

	// View *****************************************
	$page_name		= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsImages');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head			= infraspackplus_admin_prepare_head();
	$picto			= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'images', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					jQuery(document).ready(function() {
						var tblIexp = "";
						$.isSet = function(testVar){
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie("tblIexp"))) {
							tblIexp = $.cookie("tblIexp");
						}
						$(".toggle_bloc").hide();
						if (tblIexp) {
							$("[name=" + tblIexp + "]").toggle();
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
							$.cookie("tblIexp", "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie("tblIexp", $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 ) {
								$(".infrasplusScrollUp").css("right", "30px");
							} else {
								$(".infrasplusScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '		<form action = "'.$_SERVER['PHP_SELF'].'" method = "post" enctype = "multipart/form-data">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
	}
	print '			<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamGestionLogos').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/Tools.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '				<table name = "tblGF" class = "infrasplusnoborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '350px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 3), 'NumberingShort', 'InfraSPlusParamNewLogo');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		print '				<tr class = "oddeven">
								<td class = "center bold">'.$num.'</td>
								<td>
									<label for = "InfraSPlusParamLogoFile">'.$langs->trans('InfraSPlusParamLogoFile').'</label>
									<input type = "file" class = "flat infrasplusnopadding cursorpointer" id = "InfraSPlusParamLogoFile" name = "InfraSPlusParamLogoFile" accept="image/*">
								</td>
								<td class = "right">
									<label for = "InfraSPlusParamLogoName">'.$langs->trans('InfraSPlusParamLogoName').'</label>
									<input type = "text" class = "flat infrasplusnopadding cursorpointer" id = "InfraSPlusParamLogoName" name = "InfraSPlusParamLogoName">
								</td>
								<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "add" name = "action">'.$langs->trans('Add').'</button></td>
							</tr>';
		$num++;
		$metas	= $form->selectarray('defaultpied', $logos, $selected_logo, $langs->trans('InfraSPlusParamNoPied'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"', 0, 0, 0, '', 'centpercent');
		$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "defaultP" name = "action">'.$langs->trans('Validate').'</button></td>';
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamDefaultImageFooter'), '', $metas, 1, 1, $end, $num);
		// $num = 3
		$metas	= $form->selectarray('defaultwatermark', $logos, $selected_watermark, $langs->trans('InfraSPlusParamNoWatermarkImage'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"', 0, 0, 0, '', 'centpercent');
		$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "defaultW" name = "action">'.$langs->trans('Validate').'</button></td>';
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamDefaultWatermarkImage'), '', $metas, 1, 1, $end, $num);
		$metas	= $form->selectarray('defaultwatermarkust', $logos, $selected_watermark_UST, $langs->trans('InfraSPlusParamNoWatermarkImage'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"', 0, 0, 0, '', 'centpercent');
		$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "defaultWUST" name = "action">'.$langs->trans('Validate').'</button></td>';
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamUserStickerDefaultWatermarkImage'), '', $metas, 1, 1, $end, $num);
		// $num = 5
		if (getDolGlobalString('INFRASPLUS_PDF_LOGO_SECONDARY_SMALL_HEAD', '')) {
			$metas	= $form->selectarray('defaultheader', $logos, $selected_header, $langs->trans('InfraSPlusParamNoHeader'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"', 0, 0, 0, '', 'centpercent');
			$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "defaultH" name = "action">'.$langs->trans('Validate').'</button></td>';
			$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamDefaultImageHeader'), '', $metas, 1, 1, $end, $num);
		} else {
			$num++;
		}
		if (getDolGlobalString('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE_EMET', '')) {
			$metas	= $form->selectarray('defaultsignemet', $logos, $selected_signemet, $langs->trans('InfraSPlusParamNoSignEmet'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"', 0, 0, 0, '', 'centpercent');
			$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "defaultS" name = "action">'.$langs->trans('Validate').'</button></td>';
			$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamDefaultImageSignEmet'), '', $metas, 1, 1, $end, $num);
		} else {
			$num++;
		}
		// $num = 7
	}
	print '				</table>
					</div>
				</form>';
	$logo_files	= dol_dir_list($logodir.'/logos/', 'files', 0, '', null, 'name', SORT_ASC, 1, 0, '', 0);
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamListLogos').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/list.png', 1), 1, '', 'infrasplusNoBCollapse toggle_bloc_title cursorpointer');
	print '			<div name = "tblLL" class = "toggle_bloc">';
	$formfile->list_of_documents($logo_files, null, 'mycompany', '', 1, 'logos/', 1, 0, $langs->trans('NoLogo'), 0, 'none');
	print '			</div>
				</div>';
	print '		<form action = "'.$_SERVER['PHP_SELF'].'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">
					<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamImagesSetup').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '				<table name = "tblOPT" class = "infrasplusnoborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Img', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave').'<br/><span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamAvertissementCalculImage'), 3);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '5', 'max' => $maxhlogo, $disabledLogoHeight => 'true');
		$num	= infraspackplus_print_input('MAIN_DOCUMENTS_LOGO_HEIGHT', 'input', $langs->trans('InfraSPlusParamLogoHeight', $maxhlogo), '', $metas, 1, 1, '&nbsp;mm', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_SMALL_HEAD_2', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LOGO_SECONDARY_SMALL_HEAD', 'on_off', $langs->trans('InfraSPlusParamLogoSecondarySmallHead'), '', array(), 1, 1, '', $num);
		}
		else	$num++;
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '6', 'max' => '20');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_LOGO_SMALL_HEAD_HEIGHT', 'input', $langs->trans('InfraSPlusParamLogoSmallHeadHeight'), '', $metas, 1, 1, '&nbsp;mm', $num);
		// $num = 3
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '20', 'max' => '190');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_FOOT_WIDTH', 'input', $langs->trans('InfraSPlusParamPictureFootWidth'), '', $metas, 1, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '4', 'max' => '30');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT', 'input', $langs->trans('InfraSPlusParamPictureFootHeight'), '', $metas, 1, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SET_LOGO_EMET_TIERS', 'on_off', $langs->trans('InfraSPlusParamSetLogoEmetTiers'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_PICTURE', 'on_off', $langs->trans('InfraSPlusParamWithPicture').' '.$langs->trans('InfraSPlusGenModif'), '', array(), 1, 1, '', $num);
		// $num = 7
		if (getDolGlobalString('INFRASPLUS_PDF_WITH_PICTURE', '')) {
			if (getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '') || getDolGlobalString('INFRASPLUS_PDF_WITH_NUM_COLUMN', '')) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_IN_REF', 'on_off', $langs->trans('InfraSPlusParamPictureInRef'), '', array(), 1, 1, '', $num);
				if (getDolGlobalString('INFRASPLUS_PDF_PICTURE_IN_REF', '')) {
					$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_REPLACE_REF', 'on_off', $langs->trans('InfraSPlusParamPictureReplaceRef'), '', array(), 1, 1, '', $num);
				} else {
					$num++;
				}
			} else {
				$num	+= 2;
			}
		// $num = 9
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_ONE_PICTURE', 'on_off', $langs->trans('InfraSPlusParamOnlyOnePicture1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamOnlyOnePicture2').'</span> '.$langs->trans('InfraSPlusParamOnlyOnePicture3'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_AFTER', 'on_off', $langs->trans('InfraSPlusParamPictureAfter1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamPictureAfter2').'</span> '.$langs->trans('InfraSPlusParamPictureAfter3'), '', array(), 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_UNDER', 'on_off', $langs->trans('InfraSPlusParamPictureUnder1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamPictureUnder2').'</span> '.$langs->trans('InfraSPlusParamPictureUnder3'), '', array(), 1, 1, '', $num);
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '15');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_PADDING', 'input', $langs->trans('InfraSPlusParamPicturePadding'), '', $metas, 1, 1, '&nbsp;mm', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LINK_PICTURE_URL', 'input', $langs->trans('InfraSPlusParamLinkPictureUrl'), '', array(), 1, 1, '', $num);
		} else {
			$num	+= 7;
		}
		// $num = 14
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SUPPLIER_ORDER_WITH_PICTURE', 'on_off', $langs->trans('InfraSPlusParamSupplierOrderWithPicture').' '.$langs->trans('InfraSPlusGenModif'), '', array(), 1, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '16', 'max' => '160');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_WIDTH', 'input', $langs->trans('InfraSPlusParamPictureWidth').$modifWidth, '', $metas, 1, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '16', 'max' => '160');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PICTURE_HEIGHT', 'input', $langs->trans('InfraSPlusParamPictureHeight'), '', $metas, 1, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('PRODUCT_USE_OLD_PATH_FOR_PHOTO', 'on_off', $langs->trans('InfraSPlusParamOldPathPhoto'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('CAT_HIGH_QUALITY_IMAGES', 'on_off', $langs->trans('InfraSPlusParamHQPicture'), '', array(), 1, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '100');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_T_WATERMARK_OPACITY', 'input', $langs->trans('InfraSPlusParamWatermarkTOpacity'), '', $metas, 1, 1, '&nbsp;%', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '100');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_I_WATERMARK_OPACITY', 'input', $langs->trans('InfraSPlusParamWatermarkIOpacity'), '', $metas, 1, 1, '&nbsp;%', $num);
		// $num = 21
		if (!empty($selected_signemet) && $selected_signemet != '-1') {
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '16', 'max' => '160');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SIGNATURE_EMET_WIDTH', 'input', $langs->trans('InfraSPlusParamSignatureEmetWidth').$modifWidth, '', $metas, 1, 1, '&nbsp;mm', $num);
		} else {
			$num++;
		}
		// $num = 22
	}
	print '				</table>
					</div>
				</form>
				<a class = "infrasplusScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
