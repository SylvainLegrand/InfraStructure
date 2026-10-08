<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasfiles/document.php
	* 	\ingroup	InfraS
	* 	\brief		Generic "Documents" tab (attached and linked files) for the objects of the registry
	*				Same rendering as the native document.php pages (core/tpl/document_actions_post_headers.tpl.php)
	************************************************/

	// Dolibarr environment *************************
	require 'config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('other', 'companies', 'infrasfiles@infrasfiles'));

	// Parameters ***********************************
	$element	= GETPOST('element', 'aZ09');
	$id			= GETPOSTINT('id');
	$action		= GETPOST('action', 'aZ09');
	$confirm	= GETPOST('confirm', 'alpha');
	$limit		= GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
	$sortfield	= GETPOST('sortfield', 'aZ09comma');
	$sortorder	= GETPOST('sortorder', 'aZ09comma');
	if (getDolGlobalString('MAIN_DOC_SORT_FIELD')) {
		$sortfield	= getDolGlobalString('MAIN_DOC_SORT_FIELD');
	}
	if (getDolGlobalString('MAIN_DOC_SORT_ORDER')) {
		$sortorder	= getDolGlobalString('MAIN_DOC_SORT_ORDER');
	}
	if (!$sortorder) {
		$sortorder	= 'ASC';
	}
	if (!$sortfield) {
		$sortfield	= 'name';
	}

	// Access control *******************************
	$registry	= infrasfiles_get_registry();
	if (empty($registry[$element]) || !infrasfiles_is_enabled($element) || !infrasfiles_user_can($element, 'read')) {
		accessforbidden();
	}
	$definition	= $registry[$element];
	$object		= infrasfiles_load_object($element, $id);
	if (!is_object($object) || $object->id <= 0) {
		llxHeader('', $langs->trans('Documents'));
		print '<div class="error">'.$langs->trans('ErrorRecordNotFound').'</div>';
		llxFooter();
		$db->close();
		exit;
	}
	if (isModEnabled('multicompany') && !empty($object->entity) && $object->entity != $conf->entity) {
		accessforbidden();
	}
	$permissiontoadd	= infrasfiles_user_can($element, 'write') ? 1 : 0;
	$permtoedit			= $permissiontoadd;
	$hookmanager->initHooks(array('infrasfilesdocument', 'globalcard'));

	// Actions **************************************
	$upload_dir	= $object->infrasfilesGetOutputDir();
	// The native templates build their URLs as PHP_SELF?id=<id> : our 'element' parameter must be carried along, otherwise the upload / link
	// form and the redirection after a deletion land on this page without element => access forbidden (audit of 2026-09-10)
	$moreparam	= '&element='.urlencode($element);	// appended by document_actions_post_headers.tpl.php to the upload / link form action
	$backtopage	= $_SERVER['PHP_SELF'].'?element='.urlencode($element).'&id='.((int) $id);	// redirection of actions_linkedfiles.inc.php after confirm_deletefile
	// Mass deletion posted by the checkboxes of the file list (same deletion as the trash icon, several files at once) ;
	// POST only : the core checks the token of a GET action only when its name starts with del / remove / set..., not ours
	if ($action == 'infrasfiles_remove_files' && !infrasfiles_is_post_request()) {
		$action	= '';
	}
	if ($action == 'infrasfiles_remove_files' && $permissiontoadd) {
		infrasfiles_remove_files($element, $object, GETPOST('infrasfiles_files', 'array'));
		header('Location: '.$backtopage);	// messages are in session ; a page reload must never delete again
		exit;
	}
	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
	// Send by e-mail : native mechanism (attachments, templates, substitutions, agenda event through the trigger <OBJECT>_SENTBYMAIL),
	// or one e-mail per third party (module form and send loop) for the objects whose registry entry has 'mailbythirdparty'
	$emailenabled		= infrasfiles_is_enabled($element, 'EMAIL') && $permissiontoadd;
	$bythirdparty		= $emailenabled && !empty($definition['mailbythirdparty']) && method_exists($object, 'infrasfilesGetMailBatches');
	$modelmail			= $definition['mailtype'];
	$defaulttopic		= $definition['mailtopic'];
	$defaulttopiclang	= 'infrasfiles@infrasfiles';
	$diroutput			= infrasfiles_get_output_dir($element, null);	// the template appends '/<ref>' to find the last generated file
	$trackid			= $definition['trackid'].$object->id;
	$triggersendname	= $definition['trigger'];
	$actiontypecode		= 'AC_OTH_AUTO';
	$autocopy			= 'MAIN_MAIL_AUTOCOPY_INFRASFILES_TO';
	$paramname			= 'element='.urlencode($element).'&id';	// used by the native redirection : PHP_SELF?<paramname>=<id>
	if (GETPOST('modelselected', 'alpha')) {
		$action	= 'presend';	// "Apply" button of the e-mail template : show the form again with the chosen template (same as the native cards)
	}
	if (GETPOST('cancel', 'alpha')) {
		$action	= '';
	}
	if ($action == 'infrasfiles_sendbythirdparty' && !infrasfiles_is_post_request()) {
		$action	= '';	// POST only : a forged link must never send e-mails
	}
	$batchdata	= array();
	if ($bythirdparty) {
		dol_include_once('/infrasfiles/core/lib/infrasfilesmail.lib.php');
		if (in_array($action, array('presend', 'infrasfiles_sendbythirdparty'))) {
			$batchdata	= $object->infrasfilesGetMailBatches();
		}
		if ($action == 'infrasfiles_sendbythirdparty') {
			$result	= infrasfiles_send_by_thirdparty($object, $definition, $batchdata);
			if ($result['sent'] > 0 || empty($result['errors'])) {
				header('Location: '.$backtopage);	// messages are in session ; a page reload must never send again
				exit;
			}
			$action	= 'presend';	// nothing sent : show the form again with the posted values
		}
	} elseif ($emailenabled) {
		include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
	}

	// View *****************************************
	$form		= new Form($db);
	$formfile	= new FormFile($db);
	$title		= $object->ref.' - '.$langs->trans('Documents');
	llxHeader('', $title);

	$filearray	= dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize	= 0;
	foreach ($filearray as $file) {
		$totalsize	+= $file['size'];
	}

	// Tabs of the native object
	if (!empty($definition['headlib'])) {
		require_once DOL_DOCUMENT_ROOT.$definition['headlib'];
	}
	$head	= array();
	if (function_exists($definition['headfunction'])) {
		$headfunction	= $definition['headfunction'];
		$head			= $headfunction($object);	// direct call : some native functions take the object by reference (inventoryPrepareHead), call_user_func() would warn
	}
	print dol_get_fiche_head($head, 'infrasfilesdoc', $langs->trans($definition['label']), -1, $definition['picto']);

	$linkback	= '<a href = "'.DOL_URL_ROOT.$definition['listurl'].'?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';
	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');

	print '	<div class = "fichecenter">
				<div class = "underbanner clearboth"></div>
				<table class = "border tableforfield centpercent">
					<tr><td class = "titlefield">'.$langs->trans('NbOfAttachedFiles').'</td><td>'.count($filearray).'</td></tr>
					<tr><td>'.$langs->trans('TotalSizeOfAttachedFiles').'</td><td>'.dol_print_size($totalsize, 1, 1).'</td></tr>
				</table>
			</div>';
	print dol_get_fiche_end();

	// Send by e-mail form (native template), opened by the "Send by e-mail" button of the card
	if ($action == 'presend' && $emailenabled) {
		$langs->loadLangs(array('mails', 'other'));
		if (!empty($definition['langs'])) {
			$langs->loadLangs((array) $definition['langs']);
		}
		$arrayoffamiliestoexclude	= null;
		if ($bythirdparty) {
			$presendreturnurl	= $backtopage;	// this page with its 'element' parameter
			include dol_buildpath('/infrasfiles/core/tpl/infrasfiles_presend.tpl.php', 0);
		} else {
		// The native template builds the form action and return URL as PHP_SELF?id=<id> (without our 'element' parameter) : rewrite them on the output
		$selfurl	= dol_escape_htmltag($_SERVER['PHP_SELF']);
		ob_start();
		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
		$presend	= ob_get_clean();
		print str_replace($selfurl.'?id='.$object->id, $selfurl.'?element='.urlencode($element).'&id='.$object->id, $presend);
		}
	}

	// Attached files and links (native template)
	$modulepart				= 'infrasfiles';
	$relativepathwithnofile	= infrasfiles_get_subdir($element, $object).'/';
	$param					= '&element='.urlencode($element).'&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
	// "Third party" column of the file list (objects whose files are addressed to third parties), and mass deletion checkboxes
	print infrasfiles_get_thirdparty_column_script('#tablelines', infrasfiles_get_file_thirdparty_links($object, $filearray));
	if ($permissiontoadd && $action != 'editfile') {	// rename mode : the native list is already inside its own form, never nest ours
		print infrasfiles_get_mass_delete_script('#tablelines', 'tab', $backtopage, '<input type="hidden" name="element" value="'.dol_escape_htmltag($element).'"><input type="hidden" name="id" value="'.((int) $object->id).'">');
	}

	llxFooter();
	$db->close();
