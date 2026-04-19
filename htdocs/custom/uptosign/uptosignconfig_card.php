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
 *   	\file       uptosignconfig_card.php
 *		\ingroup    uptosign
 *		\brief      Page to create/edit/view uptosignconfig
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
dol_include_once('/uptosign/class/uptosignconfig.class.php');
dol_include_once('/uptosign/lib/uptosign_uptosignconfig.lib.php');
dol_include_once('/uptosign/lib/backports.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("uptosign@uptosign", "main", "other", "companies", "errors"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Get parameters
$id = GETPOSTINT('id');
$ref = (string) GETPOST('ref', 'alpha');
$action = (string) GETPOST('action', 'aZ09');
$confirm = (string) GETPOST('confirm', 'alpha');
$cancel = (string) GETPOST('cancel', 'aZ09');
$contextpage = (string) GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'uptosignconfigcard'; // To manage different context of search
$backtopage = (string) GETPOST('backtopage', 'alpha');
$backtopageforcancel = (string) GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOSTINT('lineid');

// Initialize technical objects
$object = new UptoSignConfig($db);
$objFound = false;
$utsfirst = (string) GETPOST('uts', 'alpha');
if ($utsfirst == 'first') {
	$tmplist = $object->fetchAll('', '', 1);
	$obj = reset($tmplist);
	if ($obj) {
		$object = $obj;
		$id = $obj->id;
		$ref = $obj->ref;
		$objFound = true;
	}
} else {
	if (!empty($id)) {
		$res = $object->fetch($id);
		if ($res> 0) {
			$objFound = true;
		}
	}
}

if (!$objFound) {
	setEventMessages($langs->trans("UptoSignSetup"), [$langs->trans("UptoSignSetupThereIsNoConfig")], 'errors');
}

$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->uptosign->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('uptosignconfigcard', 'globalcard')); // Note that conf->hooks_modules contains array

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
$enablepermissioncheck = 1;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('uptosign', 'uptosignconfig', 'read');
	$permissiontoadd = $user->hasRight('uptosign', 'uptosignconfig', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('uptosign', 'uptosignconfig', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('uptosign', 'uptosignconfig', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('uptosign', 'uptosignconfig', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

$upload_dir = $conf->uptosign->multidir_output[isset($object->entity) ? $object->entity : 1].'/uptosignconfig';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
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

//fix #41: simple create form, then deduce other required fields
if ($action == 'add') {
	//force backtopage en mode edition de l'id qu'on est en train de créer
	$backtopage = 'uptosignconfig_card.php?id=__ID__&action=edit';
	if (GETPOST("sign_or_seal") == 'sign') {
		$object->label = 'CustomerSign';
		$object->sign_coordinate = '100,50';
		$object->page_sign = 1;
	} else {
		$object->label = 'DocumentSeal';
	}
	$object->seal_coordinate = '100,10';
	$object->page_seal = 1;
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$triggermodname = 'UPTOSIGN_UPTOSIGNCONFIG_MODIFY'; // Name of trigger action code to execute when we modify record

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
	$triggersendname = 'UPTOSIGN_UPTOSIGNCONFIG_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_UPTOSIGNCONFIG_TO';
	$trackid = 'uptosignconfig'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	//erics et backtopage qui ne marche pas ...
	if ($error == 0 && !empty($backtopage) && empty($action)) {
		header("Location: ".$backtopage);
		exit;
	}
}


/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("UptoSignTemplatesTab");
$help_url = '';

$arrayofjs = array(
	"/uptosign/js/pdf.min.js?ver=" . filemtime("js/pdf.min.js"),
	"/uptosign/js/interact.min.js?ver=" . filemtime("js/interact.min.js"),
	"/uptosign/js/pdf.worker.min.js?ver=" . filemtime("js/pdf.worker.min.js"),
	"/uptosign/js/uptosign-specimen.js?ver=" . filemtime("js/uptosign-wizard.js")
);

$arrayofcss =  array(
	'/uptosign/css/uptosign-wizard.css?ver=' . filemtime('css/uptosign-wizard.css')
);

llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

print '<div class="fichehalfleft">';

print load_fiche_titre($title, '', 'title_setup');

// Configuration header
$head = uptosignAdminPrepareHead();
print dol_get_fiche_head($head, 'templates', $title, 0, 'uptosign@uptosign');


// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';


// Part to create
if ($action == 'create') {
	if (empty($permissiontoadd)) {
		accessforbidden($langs->trans('NotEnoughPermissions'), 0, 1);
		exit;
	}

	// print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("UptoSignConfig")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	$fieldsToShow = ['model_pdf','sign_or_seal'];
	foreach ($object->fields as $key => &$field) {
		if (!in_array($key, $fieldsToShow)) {
			$field['visible'] = 0;
		}
	}


	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	if (((int) DOL_VERSION) < 15) {
		print utsbackports_buttonsSaveCancel("Create");
	} else {
		print $form->buttonsSaveCancel("Create");
	}

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	$fieldsToEdit = [];
	$fieldsToShow = ['model_pdf'];
	if ($object->sign_or_seal == 'sign') {
		array_push($fieldsToEdit, 'sign_coordinate', 'page_sign');
	}
	array_push($fieldsToEdit, 'seal_coordinate', 'page_seal');


	print load_fiche_titre('', '', '', 0, '', '', $langs->trans('uptosignEditHelpMessage'));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<input type="hidden" name="model_pdf" value="'.$object->model_pdf.'">';
	print '<input type="hidden" name="sign_or_seal" value="'.$object->sign_or_seal .'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";
	// show only
	foreach ($object->fields as $key => &$field) {
		if (in_array($key, $fieldsToShow)) {
			print "<tr><td class='titlefield fieldname_" . $key . "'>" . $langs->trans($field['label']) . "</td><td class='valuefield fieldname_" . $key . "'>" . $object->{$key} . "</td></tr>";
		}
	}

	// Common attributes
	foreach ($object->fields as $key => &$field) {
		if (!in_array($key, $fieldsToEdit)) {
			$field['visible'] = 0;
		} else {
			$field['visible'] = 1;
		}
	}
	print '<table class="border centpercent tableforfieldedit">'."\n";
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	if (((int) DOL_VERSION) < 15) {
		print utsbackports_buttonsSaveCancel();
	} else {
		print $form->buttonsSaveCancel();
	}

	print '</div>';

	print displayPDF($object, false);
	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	// $head = uptosignconfigPrepareHead($object);
	// print dol_get_fiche_head($head, 'card', $langs->trans("UptoSignConfig"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&backtopage='.urlencode('uptosignconfig_list.php'), $langs->trans('DeleteUptoSignConfig'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	// if ($action == 'deleteline') {
	// 	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	// }
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
	$linkback = '<a href="'.dol_buildpath('/uptosign/uptosignconfig_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	print '<tr>';
	print '<td class="valeur">';
	$object->next_prev_filter = " status != '" . UptoSignConfig::STATUS_DISABLED . "'";
	print $form->showrefnav($object, 'id',  $langs->trans("uptosignThenClickHere"), 1, '', 'none', '', '', 0, '');
	print '</td></tr>';

	// dol_banner_tab($object, '', $linkback, 0);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	$fieldsToShow = ['model_pdf','label'];
	if ($object->sign_or_seal == 'sign') {
		array_push($fieldsToShow, 'sign_coordinate', 'page_sign');
	}
	array_push($fieldsToShow, 'seal_coordinate', 'page_seal');

	foreach ($object->fields as $key => &$field) {
		if (!in_array($key, $fieldsToShow)) {
			$field['visible'] = 0;
		}
	}

	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	// include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
	print '</table>';
	print '</div>';
	print '</div>';

	/*
			// print json_encode($this);
			$lien = "<a href='uptosign_card.php?id=" . $this->id ."'>";
			$this->error = $langs->transnoentitiesnoconv("UptoSignFileDoesNotExists", $lien,"</a>");
			$result = -1;

	*/

	//Si un autre modèle de document est lié ... on l'indique
	// $other = new UptoSignConfig($db);
	// $res = $other->fetchAll('', '', 0, 0, array('customsql'=>"model_pdf='" . uptosignModel($object) ."' AND rowid!='" . $object->id ."'"));
	// if ($res && count($res) > 0) {
	// 	print '<div class="clearboth">';
	// 	print '<table class="border centpercent tableforfield">'."\n";
	// 	foreach ($res as $r) {
	// 		$lien = "<a href='uptosignconfig_card.php?id=" . $r->id ."'>MODEL#" . $r->id . "</a>";
	// 		print "<tr><td class='titlefield'>" . $langs->trans("UptoSignOtherModelLinked"). "</td><td>" . $lien. "</td></tr>\n";
	// 	}
	// 	print '</table>';
	// 	print '</div>';
	// }

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Clone
			// print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.(!empty($object->socid) ? '&socid='.$object->socid : '').'&action=clone&token='.newToken(), '', $permissiontoadd);

			// Delete (need delete permission, or if draft, just need create/modify permission)
			/** @phpstan-ignore-next-line */
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));

			if ($object->status == $object::STATUS_DRAFT) {
				/** @phpstan-ignore-next-line */
				print dolGetButtonAction($langs->trans('Enable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_validate&confirm=yes&token='.newToken(), '', $permissiontoadd);
			} else {
				if ($object->status == $object::STATUS_VALIDATED) {
					/** @phpstan-ignore-next-line */
					print dolGetButtonAction($langs->trans('Disable'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
				}
			}

			// Create new one
			$backtopage = urlencode('uptosignconfig_card.php?id=__ID__');
			print dolGetButtonAction($langs->trans('New'), '', 'new', $_SERVER['PHP_SELF'].'?action=create&backtopage=' . $backtopage . '&token='.newToken(), '', $permissiontoadd);

			// Full list
			print dolGetButtonAction($langs->trans('List'), '', 'list', 'uptosignconfig_list.php?token='.newToken(), '', $permissiontoadd);
		}
		print '</div>'."\n";
	}

	print displayPDF($object, true);

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 0;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->uptosign->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('uptosign:UptoSignConfig', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, uptosignModel($object), 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		// $linktoelem = $form->showLinkToObjectBlock($object, null, array('uptosignconfig'));
		// $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'uptosignconfig';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->uptosign->dir_output;
	$trackid = 'uptosignconfig'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();

function displayPDF($object, $readOnly = true)
{
	$model_pdf = uptosignModel($object);
	$t = explode(':', $model_pdf);
	$b64 = uptoSignGetSpecimen($t[0], $t[1], true);

	print '</div>';
	print '<div class="fichehalright" id="pdfManager" style="float:left">' . "\n";
	print '<div class="" id="paramContainer"></div>' . "\n";
	print '<div class="" id="paramContainerBis"></div>' . "\n";

	// print '<form id="leform" name="leform" method="POST">';
	print '      <input type="hidden" id="readonly" value="' . $readOnly . '">' . "\n";
	print '      <input type="hidden" id="pdfData" name="pdfData" value="' . $b64 . '">' . "\n";
	print '      <div class="row" id="selectorContainer">' . "\n";
	print '        <div class="">' . "\n";
	print '          <div id="pageContainer" class="uptosignPdfViewer singlePageView uptosignDropzone nopadding" style="background-color:transparent">' . "\n";
	print '            <canvas id="uptosignCanvas" style="border:1px  solid black"></canvas>' . "\n";
	print '          </div>' . "\n";
	print '        </div>' . "\n";
	print '      </div>' . "\n";
	// print '</form>';


	$defaultSealX = $defaultSealY = 0;
	$defaultSealX1 = $defaultSealY1 = 0;
	$defaultSealPage = $defaultSignPage1 = 0;
	$d = explode(',', $object->seal_coordinate);
	$defaultSealX = $d[0];
	$defaultSealY = $d[1];
	$defaultSealPage = $object->page_seal;

	$jsonparameters = [
	[
		'paramId' => 'seal',
		'formTargetField' => 'seal_coordinate',
		'description' => "SCEAU UPTOSIGN<br />"
		 . "Document scellé par uptosign<br />"
		 . "Identifiant unique xxxxx<br />"
		 . "https://uptosign.com/",
		'defaultX' => $defaultSealX,
		'defaultY' => $defaultSealY,
		'defaultPage' => $defaultSealPage,
	]
	];
	$listOfFields = [ 'signX', 'signY', 'page'];
	foreach ($listOfFields as $f) {
		$fieldName = 'seal-' . $f;
		print '      <input id="' . $fieldName . '" name="' . $fieldName . '" type="hidden" value="">' . "\n";
	}

	//Les autres signataires
	if ($object->sign_or_seal == 'sign' && $object->sign_coordinate != '') {
		$d = explode(',', $object->sign_coordinate);
		$defaultSignX1 = $d[0];
		$defaultSignY1 = $d[1];
		$defaultSignPage1 = $object->page_sign;
		$jsonparameters[] = [
			'paramId' => 'sign',
			'formTargetField' => 'sign_coordinate',
			'description' => "Dessin de la signature<br /><br />"
			. "Signé par Nom - Prénom<br />"
			. "Le xx xx xxxx a hh mm ss depuis xxx.xxx.xxx <br />"
			. "Lien sécurisé envoyé par mail à xxxx@xxxx.xxxx <br />"
			. "Vérifié par SMS envoyé au xxxxxxxxxx",
			'defaultX' => $defaultSignX1,
			'defaultY' => $defaultSignY1,
			'defaultPage' =>  $defaultSignPage1
		];
	}

	// print json_encode($object);

	print '      <input id="jsonparameters" type="hidden" value=' . "'" . json_encode($jsonparameters) . "'".' />' . "\n";
}
