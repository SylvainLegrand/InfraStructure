<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright 2022-2023 ---Eric Seigne <eric.seigne@cap-rel.fr>---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       uptosign_card.php
 *		\ingroup    uptosign
 *		\brief      Page to create/edit/view uptosign
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification
//if (! defined('NOSESSION'))     		     define('NOSESSION', '1');				    // Disable session

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("uptosign@uptosign", "main", "other", "companies", "errors"));

// Get parameters
$id = GETPOSTINT('id');
$ref = (string) GETPOST('ref', 'alpha');
$action = (string) GETPOST('action', 'aZ09');
$confirm = (string) GETPOST('confirm', 'alpha');
$cancel = (string) GETPOST('cancel', 'aZ09');
$contextpage = (string) GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'uptosigncard'; // To manage different context of search
$backtopage = (string) GETPOST('backtopage', 'alpha');
$backtopageforcancel = (string) GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOSTINT('lineid');

// Initialize technical objects
$object = new UptoSign($db);
$object->fetch($id);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->uptosign->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('uptosigncard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = (string) GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if ((string) GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = (string) GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
$permissiontoadd = 0; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('uptosign', 'read');
	$permissiontoadd = $user->hasRight('uptosign', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('uptosign', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('uptosign', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('uptosign', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->uptosign->multidir_output[isset($object->entity) ? $object->entity : 1].'/uptosign';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->uptosign->enabled)) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

$objectType = $object->object_type;
$objectSync = $context = $modulepart = null;
$reshook = 0;
if (in_array($action, ["uptosignsync","uptosealsync","confirm_uptosignfetch","confirm_uptosealfetch", "checkHistory"])) {
	// c'est ici qu'on a un pb lié au modèle initial "on ne signe qu'un objet dolibarr" mais finalement un objet
	// dolibarr peut avoir plusieurs fichiers et l'utilisateur peut souhaiter signer / sceller plusieurs docs
	// liés à un objet ... quand on est sur uptosign_card on est sur un fichier précis, pourquoi remonter à
	// l'objet lié ?
	// 20230616 - tentative de passer l'uuid dans les parametres du hook
	$hallobj = uptosign_handle_all_type_of_objects($objectType, $id);
	$objectSync = $hallobj['object'];
	$modulepart = $hallobj['modulepart'];

	$parameters = array('currentcontext' =>  $modulepart.'card', "signOrSeal" => $object->api_name, "uuid" => $object->sign_id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $objectSync, $action); // Note that $action and $object may have been modified by some hooks
} elseif (in_array($action, ["confirm_delete"])) {
	$parameters = array('currentcontext' =>  $modulepart.'card', "signOrSeal" => $object->api_name, "uuid" => $object->sign_id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
}

if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/uptosign/uptosign_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/uptosign/uptosign_card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'UPTOSIGN_UPTOSIGN_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'UPTOSIGN_UPTOSIGN_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_UPTOSIGN_TO';
	$trackid = 'uptosign'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

if ($action == "check") {
	if ($object->checkFile()) {
		setEventMessages($langs->trans("UptoSignCheckFileOk") . ": " . $langs->trans("UptoSignCheckFileOkMessage"), []);
	} else {
		setEventMessages($langs->trans("UptoSignCheckFileError"), $object->errors, 'warnings');
	}
}
if ($action == "checkHistory") {
	setEventMessages($langs->trans("UptoSignHistoryRefresh"), []);
}

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("UptoSign");
$help_url = '';
llxHeader('', $title, $help_url);

// Example : Adding jquery code
print '<script type="text/javascript">
jQuery(document).ready(function() {
	$( function() {
	  $( "#uptosignHistory" ).accordion();
	});
});
</script>
';

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = uptosignPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("UptoSign"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteUptoSign'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}


	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/uptosign/uptosign_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->projet->enabled)) {
	 $langs->load("projects");
	 $morehtmlref .= '<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd) {
	 //if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> ';
	 $morehtmlref .= ' : ';
	 if ($action == 'classify') {
	 //$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref .= '<input type="hidden" name="action" value="classin">';
	 $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref .= '</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	//half right -> display history POC DEV EN COURS
	//{"eri***@garluche.fr":[{"description":"Sending mail to :email for invitation to sign a new document","step":"mailinvitationtosign","created_at":"2023-09-05T11:14:12.000000Z","updated_at":"2023-09-05T11:14:12.000000Z"},{"description":"Pre-Sign (display legal docs) for :email from :ip","step":"presign","created_at":"2023-09-05T11:14:55.000000Z","updated_at":"2023-09-05T11:14:55.000000Z"},{"description":"Pre-Sign conditions accepted for :email and name entered is :signname","step":"presignok","created_at":"2023-09-05T11:15:14.000000Z","updated_at":"2023-09-05T11:15:14.000000Z"},{"description":"User :email read (download) document from :ip","step":"documentread","created_at":"2023-09-05T11:15:15.000000Z","updated_at":"2023-09-05T11:15:15.000000Z"},{"description":"User :email is going to sign the document from :ip","step":"fingersign","created_at":"2023-09-05T11:15:29.000000Z","updated_at":"2023-09-05T11:15:29.000000Z"},{"description":"HandSign (handsign) was done by :email from :ip","step":"fingersigndone","created_at":"2023-09-05T11:15:42.000000Z","updated_at":"2023-09-05T11:15:42.000000Z"},{"description":"Send SMS Code to :email number :mobile","step":"sendingsmscode","created_at":"2023-09-05T11:15:43.000000Z","updated_at":"2023-09-05T11:15:43.000000Z"},{"description":"SMS code confirmed by :email send to :mobile from :ip","step":"textconfirmsuccess","created_at":"2023-09-05T11:16:33.000000Z","updated_at":"2023-09-05T11:16:33.000000Z"},{"description":"Download signed PDF file by :email","step":"downloadsigneddocument","created_at":"2023-09-05T11:19:07.000000Z","updated_at":"2023-09-05T11:19:07.000000Z"}]}
	if (!empty($object->sign_history)) {
		print $object->displayHistory();
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	print dol_get_fiche_end();

	/*
	 * Lines
	 */

	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow" width="100%">';
		}

		if (!empty($object->lines)) {
			$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1);
		}

		// Form to add new line
		// if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
		// 	if ($action != 'editline') {
		// 		// Add products/services form

		// 		$parameters = array();
		// 		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		// 		if ($reshook < 0) {
		// 			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		// 		}
		// 		if (empty($reshook)) {
		// 			$object->formAddObjectLine(1, $mysoc, $soc);
		// 		}
		// 	}
		// }

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}


	// Buttons for actions - cas particulier du dossier de preuves
	if ($action != 'presend' && $action != 'editline' && $object->hash_file == '') {
		print '<div class="tabsAction">'."\n";
		print $langs->trans("UptoSignProofButtonActions");
		print '</div>'."\n";
	}
	if ($action != 'presend' && $action != 'editline' && $object->hash_file != '') {
		print '<div class="tabsAction">'."\n";
		$parameters = array('currentcontext' =>  'uptosigncard');
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			// if (empty($user->socid)) {
			//     print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle');
			// }

			// Back to draft
			// if ($object->status == $object::STATUS_VALIDATED) {
			//     print dolGetButtonAction($langs->trans('SetToDraft'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
			// }

			// print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Validate
			// if ($object->status == $object::STATUS_DRAFT) {
			//     if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
			//         print dolGetButtonAction($langs->trans('Validate'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
			//     } else {
			//         $langs->load("errors");
			//         print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
			//     }
			// }

			// Clone
			// print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);

			/*
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_ENABLED) {
					print dolGetButtonAction($langs->trans('Disable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction($langs->trans('Enable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
				}
			}
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED) {
					print dolGetButtonAction($langs->trans('Cancel'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
				} else {
					print dolGetButtonAction($langs->trans('Re-Open'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
				}
			}
			*/
			/** @phpstan-ignore-next-line */
			print dolGetButtonAction($langs->trans('CheckHistory'), '', 'checkhistory', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=checkHistory&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));

			// TODO Download proof
			// print dolGetButtonAction($langs->trans('Download proof'), '', 'downloadProof', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_uptosignfetchproof&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
			/** @phpstan-ignore-next-line */
			print dolGetButtonAction($langs->trans('CheckFileWithCheckSum'), '', 'check', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=check&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));

			// Delete (need delete permission, or if draft, just need create/modify permission)
			/** @phpstan-ignore-next-line */
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'uptosign';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->uptosign->dir_output;
	$trackid = 'uptosign'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
