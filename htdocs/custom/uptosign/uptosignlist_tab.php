<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Eric Seigne <eric.seigne@cap-rel.fr>
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
 *   	\file	   uptosign_tab.php
 *		\ingroup	uptosign
 *		\brief	  Page to create/edit/view settings
 */

//if (! defined('NOREQUIREDB'))			  define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))			define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))			 define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))			define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))	define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))			  define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))		   define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))			 define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))			define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))			define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))			define('NOREQUIREAJAX', '1');	   	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))				  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))				define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))		define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))				 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))	 define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))	 		 define('NOBROWSERNOTIF', '1');				// Disable browser notification

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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/signature.lib.php';

// load uptosign libraries
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/class/uptosignconfig.class.php');
dol_include_once('/uptosign/lib/backports.lib.php');
dol_include_once('/uptosign/lib/uptosign_uptosignlist.lib.php');
dol_include_once('/uptosign/lib/uptosign_standalone.lib.php');

dol_include_once('/uptosign/vendor/autoload.php');
$uptoSign = new UptoSign($db);
$uptoSignConfig = new UptoSignConfig($db);

//check module version vs last init version in database
dol_include_once('/uptosign/core/modules/modUptoSign.class.php');
$tmpmodule = new modUptoSign($db);
if ($tmpmodule->version != utsbackports_getDolGlobalString('UPTOSIGN_MODULE_VERSION', '')) {
	setEventMessages($langs->trans("ErrorUptoSignModuleVersionDatabase"), [], 'errors');
}

if (utsbackports_getDolGlobalString('UPTOSIGN_DEFAULT_USER', '') == '') {
	setEventMessages($langs->trans("ErrorUptoSignModuleChooseDefaultUser"), [], 'errors');
}

// dol_include_once('/scaninvoices/class/settings.class.php');
// dol_include_once('/uptosign/lib/uptosign.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("uptosign@uptosign", "main", "other", "companies", "errors"));

// Get parameters
$id = GETPOSTINT('id');
$objectType = (string) GETPOST('objectType', 'alpha');
$ref		= (string) GETPOST('ref', 'alpha');
$action = (string) GETPOST('action', 'aZ09');
$confirm	= (string) GETPOST('confirm', 'alpha');
$cancel	 = (string) GETPOST('cancel', 'aZ09');
$contextpage = (string) GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'settingscard'; // To manage different context of search
$backtopage = (string) GETPOST('backtopage', 'alpha');
$backtopageforcancel = (string) GETPOST('backtopageforcancel', 'alpha');
$pdfFileChoosed = dol_osencode(dol_sanitizePathName((string) GETPOST('pdfFileChoosed', 'alpha')));
$refTitle = (string) GETPOST('refTitle', 'alpha');
$postAutoposition = GETPOSTISSET('autoposition');
$countOfPages = GETPOSTINT('countOfPages');
//$lineid   = GETPOSTINT('lineid');
// Chrome-less rendering: see lib/uptosign_standalone.lib.php. Without it the page
// only shows a launcher opening the wizard in an isolated modal.
$standalone = uptosign_standalone_active();
$object = $modulepart = $head = null;
$signed = false;

// Initialize technical objects
if ($objectType == 'uptosignlist') {
	dol_include_once('/uptosign/class/uptosignlist.class.php');
	dol_include_once('/uptosign/lib/uptosign_uptosignlist.lib.php');
	$object = new UptoSignList($db);
	$object->fetch($id);
	$object->getRights();
	$head = uptosignlistPrepareHead($object);
	$modulepart = "uptosign";
	$noSeal = 1;
	$signOrSeal = 'sign';
}

if ($object === null) {
	dol_syslog("uptosign: that sort of object ($objectType) is not handled by this module !", LOG_ERR);
	accessforbidden($langs->trans('UptoSignObjectTypeNotCompatible'));
}

$model_pdf = uptosignModel($object);
$ref = $object->ref;

//default file = the first one
$pdfFileChoosedFullPath = "";
// Resolve the upload directory the same way uptosignlist_document.php does, so the
// position tab always looks where the PDF is actually stored (.../uptosignlist/<ref>).
$upload_dir = uptosign_uptosignlist_upload_dir($object);
if (empty($pdfFileChoosed)) {
	/** @phpstan-ignore-next-line */
	$filearray = dol_dir_list($upload_dir, "files", 0, '\.pdf$', ['(\.meta|_preview.*\.png)$'], "name", SORT_ASC, 1);
	$fileInfo = null;
	if (is_array($filearray) && count($filearray) >= 1) {
		//1er fichier par défaut
		$fileInfo = reset($filearray);
		$pdfFileChoosedFullPath = dol_osencode(dol_sanitizePathName($fileInfo['fullname']));
		$pdfFileChoosed = dol_osencode(dol_sanitizePathName($fileInfo['name']));
	}
}

$hookmanager->initHooks(array('uptosigntab', 'globalcard')); // Note that conf->hooks_modules contains array

$permissiontoaccess = $user->hasRight('uptosign', 'read');
$permissiontoadd = $user->hasRight('uptosign', 'create');
$permissiontodelete = $user->hasRight('uptosign', 'delete');

/*
Note: vérification des droits associés et nécessaires:
	- lire les tiers societe->lire;
	- etendre societe->client->voir
	.../...?
*/
$otherModulesRights = [
	$user->hasRight('societe', 'lire'),
	$user->hasRight('societe', 'client', 'voir'),
];
// Security check - Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}
if ($user->socid > 0) {
	$socid = $user->socid;
}
if (empty($permissiontoaccess)) {
	accessforbidden();
}
foreach ($otherModulesRights as $perm) {
	if (empty($perm)) {
		accessforbidden($langs->trans('NeedPerms'));
	}
}
// Entity guard on the uptosignlist object (IDOR across entities in multicompany).
// restrictedArea() is not used here: the uptosign module right is named 'read' (not the
// 'lire' restrictedArea expects), so calling it would deny every legitimate user.
if ($object->id > 0 && isset($object->entity) && !in_array((int) $object->entity, array_map('intval', explode(',', (string) $conf->entity)), true) && empty($user->admin)) {
	dol_syslog("uptosign: uptosignlist #" . $object->id . " entity " . $object->entity . " not in current entity " . $conf->entity, LOG_WARNING);
	accessforbidden();
}

/*
 * Actions
 */

// $parameters = array('currentcontext' =>  $modulepart . 'card');
// $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
// if ($reshook < 0) {
//	 setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
// }

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$ucontact = new User($db);
$contact = new Contact($db);
$noSign = $noSeal = null;

$title = $langs->trans("UptoSignListTab");
$help_url = 'https://doc.cap-rel.fr/projet_uptosign/faire_signer_le_meme_document_independamment_par_plusieurs_personnes';

$arrayofjs = array(
	"/uptosign/js/pdf.min.js?ver=" . filemtime("js/pdf.min.js"),
	"/uptosign/js/interact.min.js?ver=" . filemtime("js/interact.min.js"),
	"/uptosign/js/pdf.worker.min.js?ver=" . filemtime("js/pdf.worker.min.js"),
	"/uptosign/js/uptosign-wizard.js?ver=" . filemtime("js/uptosign-wizard.js")
);

$arrayofcss =  array(
	'/uptosign/css/uptosign-wizard.css?ver=' . filemtime('css/uptosign-wizard.css')
);
$nomain = "";

// Idempotency guard: an UptoSignList procedure is only allowed when the object is still DRAFT.
// On F5, back-arrow or double-click after success, the status has already moved away from
// DRAFT - redirect to the list card instead of re-running sealOrSignInitLight for each member.
// Placed before llxHeader so header() performs a clean HTTP redirect.
if ($action == 'uptosign' && $object->id > 0 && (int) $object->status !== (int) UptoSignList::STATUS_DRAFT) {
	dol_syslog("uptosign: duplicate uptosignlist sign request for id=" . $object->id . " (status=" . $object->status . "), redirect to card", LOG_WARNING);
	setEventMessages($langs->trans("UptoSignDuplicateRequestRedirect"), [], 'warnings');
	$idemTarget = dol_buildpath('/uptosign/uptosignlist_card.php', 1) . '?id=' . (int) $object->id;
	if ($standalone) {
		// Inside the modal a Location header would only reload the iframe.
		uptosign_standalone_leave($idemTarget);
	}
	header('Location: ' . $idemTarget);
	exit;
}

// Buffer page output so the success path can flush it and emit a POST-Redirect-GET header.
ob_start();
if ($standalone) {
	uptosign_standalone_header($langs->trans('UptoSignWizardTitle'), $arrayofjs, $arrayofcss, $object->ref);
} else {
	// The launcher does not need the wizard assets, they are loaded by the modal. The
	// modal script is loaded here and not only through module_parts, so the tab keeps
	// working on an installation that was not reactivated after the update.
	llxHeader('', 'UptoSign - Choose sign position', '', '', 0, 0, array('/uptosign/js/uptosign-modal.js?ver=' . filemtime('js/uptosign-modal.js')), array(), '', '', $nomain, 0);
}
$allreadyUsed = [];
$x = $y = $p = $s = null;
$error = null;

// Confirmation to sign
if ($action == 'uptosign') {
	if (empty($permissiontoadd)) {
		dol_syslog("uptosign: user lacks 'create' right to launch uptosignlist sign", LOG_WARNING);
		accessforbidden();
	}
	if ((int) $object->status !== (int) UptoSignList::STATUS_DRAFT) {
		dol_syslog("uptosign: uptosignlist #" . $object->id . " not in DRAFT status, sign refused", LOG_WARNING);
		accessforbidden($langs->trans("UptoSignObjectTypeNotCompatible"));
	}
	//Sauvegarder les données du formulaire (?)
	$pdfFileName = base64_decode((string) GETPOST('pdfFileName', 'alpha'));
	if (!empty($pdfFileName)) {
		$pdfFileName = dol_sanitizePathName($pdfFileName);
		// Reject paths escaping DOL_DATA_ROOT (LFI defense, same guard as uptosign_tab.php)
		if (strpos(realpath(dirname($pdfFileName)) . '/', realpath(DOL_DATA_ROOT) . '/') !== 0) {
			dol_syslog("uptosign: pdfFileName outside DOL_DATA_ROOT: $pdfFileName", LOG_ERR);
			accessforbidden('Invalid file path');
		}
	}
	$countOfContacts = $object->getNbContacts();
	$error = 0;

	$procedure = str_replace("upto", "", $action);

	//La liste pour le stamp
	$stampArray = [];
	for ($i = 0; $i <= $countOfPages; $i++) {
		$x = GETPOSTINT('seal-' . $i . '-signX');
		$y = GETPOSTINT('seal-' . $i . '-signY');
		$p = GETPOSTINT('seal-' . $i . '-page');
		$s = 1;
		if (!empty($x) && !empty($y)) {
			$stampArray[] = ['x' => $x, 'y' => $y, 'p' => $p, 's' => 1];
		}
	}

	if (count($stampArray) == 0) {
		$error++;
		setEventMessages($langs->trans("UptoSignErrorErrorStampPosition"), [], 'errors');
	}

	dol_syslog("uptosign: stamp data is " . json_encode($stampArray), LOG_DEBUG);

	$listMembers = null;

	// print "<p> recherche des signataires </p>";
	$signArray = [];
	for ($i = 0; $i <= $countOfPages; $i++) {
		$x = GETPOSTINT('sign-' . $i . '-signX');
		$y = GETPOSTINT('sign-' . $i . '-signY');
		$p = GETPOSTINT('sign-' . $i . '-page');
		$s = 1;
		if (!empty($x) && !empty($y)) {
			$signArray[] = ['x' => $x, 'y' => $y, 'p' => $p, 's' => 1];
		}
	}

	// exit;

	// TODO check if ok
	if ($action == 'uptosign') {
		$object->uptosignTitle = $langs->trans("UptoSignProcessTitle");
		$object->uptosignMessage = $langs->trans("UptoSignProcessStarted");
	}

	// print "<p>Avant l'appel de signorseal : " . json_encode($object) . "</p>";exit;
	// print "<p>Avant l'appel de signorseal : " . json_encode($listMembers) . "</p>";exit;
	$object->setStatut(UptoSignList::STATUS_SENTPARTIALY);

	//une procedure de signature pour chaque signataire...
	$sendRes = null;
	$contacts = $object->getContacts();
	foreach ($contacts as $contact) {
		$uptoSign = new UptoSign($db);
		$listMembers = [];
		$signerName = $contactemail = "";

		for ($i = 0; $i <= $countOfPages; $i++) {
			$x = GETPOSTINT('sign-' . $i . '-signX');
			$y = GETPOSTINT('sign-' . $i . '-signY');
			$p = GETPOSTINT('sign-' . $i . '-page');
			$s = 1;
			if (!empty($x) && !empty($y)) {
				$contactemail = $contact->email;
				$listMembers[] = array(
					'dolid' => $contact->source_id,
					'doltype' => $contact->source_type,
					'firstname' => $contact->firstname,
					'lastname' => $contact->lastname,
					'societe' => '',
					'email' => $contact->email,
					'mobile' => $contact->mobile,
					'signPosX' => $x,
					'signPosY' => $y,
					'signPage' => $p,
				);
				$signerName = $contact->firstname . " " . $contact->lastname;
			} else {
				dol_syslog("uptosign: do not start sign for that document, x or y is null");
			}
		}

		if ($contactemail != '') {
			dol_syslog("uptosign: will send sign to $contactemail ... ", LOG_DEBUG);
			//remote file name with email to be unique when dolibarr download result
			$remotePdfFileName = str_replace(".pdf", "-" . dol_sanitizeFileName($contactemail) . ".pdf", $pdfFileName);
			//Fichier à signer/sceller
			$fileToSign = array(
				'filename' => basename($remotePdfFileName),
				'fullname' => $remotePdfFileName,
				'content' => base64_encode(file_get_contents($pdfFileName)),
				'stampArray' => $stampArray,
				'title' => $refTitle . " - " . $signerName,
			);

			$sendRes = $uptoSign->sealOrSignInitLight($user, $object, $fileToSign, $listMembers, $procedure);

			// Link the member to the created UptoSign procedure
			if ($sendRes >= 0 && $uptoSign->id > 0) {
				$sql = "UPDATE ".$db->prefix()."uptosign_uptosignlistmembers";
				$sql .= " SET fk_uptosign = ".((int) $uptoSign->id);
				$sql .= " WHERE rowid = ".((int) $contact->rowid);
				$db->query($sql);
			}
		} else {
			dol_syslog("uptosign: sign target email is empty !", LOG_DEBUG);
			$sendRes = -1;
		}
	}

	// print "<p>Retour de l'appel du signorseal : $sendRes</p>";

	if ($sendRes >=  0) {
		$object->setStatut(UptoSignList::STATUS_SENTCOMPLETELY);

		if ($action == 'confirm_uptoseal' || $action == 'uptoseal') {
			$message = "SealRequestSuccessful";
		} else {
			$message = "SignRequestSuccessful";
		}
		setEventMessages($langs->trans($message), [], 'mesgs');

		// POST-Redirect-GET: discard buffered output (the header was emitted into the
		// ob_start buffer above) and redirect to the list card. Prevents re-running
		// sealOrSignInitLight on F5 or back/forward navigation.
		$successTarget = dol_buildpath('/uptosign/uptosignlist_card.php', 1) . '?id=' . (int) $object->id;
		if ($standalone) {
			// Hand the URL over to the host page: it closes the modal and navigates.
			uptosign_standalone_leave($successTarget);
		}
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		header('Location: ' . $successTarget);
		exit;
	} else {
		setEventMessages("sealOrSignInitLight errors : " . implode("\n", $uptoSign->errors), [], 'errors');
	}
}
// if (($action == 'confirm_uptosign' || $action == 'confirm_uptoseal')) {
// }

// Tells apart "the list is not signable any more" (message printed just below, id then
// cleared on purpose) from "no list at all", which needs its own dead end screen.
$objectFound = ($object->id > 0);

if ($object->status != UptoSignList::STATUS_DRAFT) {
	if (!$standalone) {
		print dol_get_fiche_head($head, 'uptosignlisttab', $langs->trans("UptoSign"), -1, $object->picto);
	}
	print '	<div class="fichecenter">' . "\n";
	print ' 	<div class="fichethirdleft" style="padding:10px; max-width: 200px">' . "\n";
	print "<p>" . $langs->trans("uptosignListProcessInProgress") . "</p>";
	print '     </div>' . "\n";
	print ' </div>' . "\n";
	$object->id = 0;
}

//
if (!$standalone && $object->id > 0) {
	// Dolibarr tab: keep the native chrome and the tab bar, and delegate the wizard to
	// the isolated modal. None of the expensive work below (PDF payload, position
	// detection) is done here, it belongs to the standalone request.
	$res = $object->fetch_optionals();

	print dol_get_fiche_head($head, 'uptosignlisttab', $langs->trans("UptoSign"), -1, $object->picto);

	// A list is signed, never sealed: keep presign so the wizard does not fall back to
	// the seal mode the "empty action" default would select.
	$wizardAction = in_array($action, array('presign', 'preseal'), true) ? $action : 'presign';
	$wizardUrl = dol_buildpath('/uptosign/uptosignlist_tab.php', 1)
		. '?id=' . urlencode((string) $id)
		. '&objectType=' . urlencode($objectType)
		. '&action=' . urlencode($wizardAction)
		. ($pdfFileChoosed != '' ? '&pdfFileChoosed=' . urlencode($pdfFileChoosed) : '')
		. '&standalone=1';
	uptosign_standalone_print_launcher($wizardUrl, $langs->trans('UptoSignWizardOpen'), $object);

	print dol_get_fiche_end();
} elseif (!$objectFound) {
	// Deleted list, stale bookmark, or an id from another entity: say so instead of
	// rendering an empty page the user cannot do anything with.
	dol_syslog("uptosign: no uptosignlist to sign for id=$id", LOG_WARNING);
	uptosign_standalone_print_object_not_found();
} elseif ($object->id > 0) {
	$res = $object->fetch_optionals();

	$formconfirm = $lineid = '';

	// Call Hook formConfirm
	$parameters = array('currentcontext' =>  $modulepart . 'card', 'formConfirm' => $formconfirm, 'lineid' => $lineid);
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
	$linkback = '<a href="'.dol_buildpath('/uptosign/uptosignlist_tab.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	// dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	// print '</div>' . "\n";
	print '<form id="leform" name="leform" method="POST" action="' . dol_buildpath('/uptosign/uptosignlist_tab.php', 1) . '">' . "\n";
	// Keep the chrome-less mode across the POST, otherwise the result would come back
	// with the full Dolibarr layout inside the modal.
	print '	  <input type="hidden" name="standalone" value="1">' . "\n";

	if ($action == "" || $action =="preseal") {
		$signOrSeal = 'seal';
		$noSign = 1;
	} else {
		$signOrSeal = 'sign';
		$noSeal = 1;
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div id="presend">'."\n";

		// Send
		$api_name = uptosign_unify_api_name($signOrSeal);
		if ($action == '') {
			$action = 'preseal';
		}
		// A grouped signature never stores the list in fk_object/object_type: those hold
		// the SIGNER (a contact, a user, a member...), and the only link back to the list
		// is fk_uptosignlist. Asking fetchByObject() for the list id therefore always came
		// back empty, and the whole branch below was dead code.
		$children = $uptoSign->fetchChildsOfList((int) $id, $api_name);
		$waiting = 0;
		if (is_array($children)) {
			foreach ($children as $child) {
				if ((int) $child->status === UptoSign::STATUS_WAITING) {
					$waiting++;
				}
			}
		} else {
			dol_syslog("uptosign: fetchChildsOfList failed for uptosignlist #" . $id . " : " . $uptoSign->error, LOG_ERR);
		}
		if ($waiting > 0) {
			// Hide the send buttons: the batch is running, sending it again would start a
			// second procedure for every signer. Link to the tracking table rather than to
			// the list card, it is the page telling where each signer stands.
			$signed = true;
			dol_syslog("uptosign: uptosignlist #" . $id . " already has " . $waiting . " " . $api_name . " procedure(s) waiting");
			$target = dol_buildpath('/uptosign/uptosignlist_docs.php', 1) . '?id=' . (int) $id;
			print uptosign_standalone_leave_link($target, $langs->trans('UptoSignProcedureAlreadyRunning'));
		}
		print '</div> <!-- end of presend -->'."\n";
	}
	print '	<div class="fichecenter">' . "\n";
	print ' 	<div class="fichethirdleft" style="padding:10px; max-width: 200px">' . "\n";
	uptosign_render_page_nav();
	dol_syslog("uptosign: pdfFileChoosed is " . $pdfFileChoosed);
	$fileInfo = uptosign_render_pdf_selector($upload_dir, $pdfFileChoosed, $pdfFileChoosedFullPath);
	print '	  <input type="hidden" id="objectType" name="objectType" value="' . $objectType . '">' . "\n";
	print '	  <input type="hidden" id="id" name="id" value="' . $id . '">' . "\n";
	print '		 <input type="hidden" name="token" value="'.newToken().'">'."\n";

	$autopositionSeal = $autopositionSign = false; //new way: seal is distinct of sign
	$positionsSeal = $positionsSign = array();
	print "<p>" . $langs->trans("ModelDocument") . " : " . $model_pdf . "</p>";

	if ($action == "" || $action =="preseal") {
		$actionSeal = "<li><b>Sceller (actif)</b></li>\n";
		$actionSign = "<li><a href='" . dol_escape_htmltag($_SERVER["PHP_SELF"]) . "?objectType=" . urlencode($objectType) . "&id=" . urlencode((string) $id) . "&action=presign&pdfFileChoosed=".urlencode($pdfFileChoosed)."'>Signer</a></li>\n";
	} else {
		$actionSeal = "<li><a href='" . dol_escape_htmltag($_SERVER["PHP_SELF"]) . "?objectType=" . urlencode($objectType) . "&id=" . urlencode((string) $id) . "&action=preseal&pdfFileChoosed=".urlencode($pdfFileChoosed)."'>Sceller</a></li>\n";
		$actionSign = "<li><b>Signer (actif)</b></li>\n";
	}
	// print "<p>Action possible : <ul>\n" . $actionSeal . $actionSign . "</ul>\n</p>\n";

	//En priorite utilisation de la position des mots clés magiques
	if (!empty($fileInfo)) {
		$autoResult = uptosign_detect_pdf_positions($pdfFileChoosedFullPath, $action, $positionsSign, $positionsSeal);
		$autopositionSign = $autoResult['autopositionSign'];
		$autopositionSeal = $autoResult['autopositionSeal'];
	}

	// note: partial fail possible: autoposition ok pour signature mais pas pour le sceau .. il faut donc quand meme passer sur ce bloc de code
	if ($autopositionSeal == false || $autopositionSign == false) {
		$configResult = uptosign_get_config_positions($model_pdf, $modulepart, $signOrSeal, $uptoSignConfig, $autopositionSeal, $autopositionSign, $positionsSign, $positionsSeal);
		$autopositionSign = $configResult['autopositionSign'];
		$autopositionSeal = $configResult['autopositionSeal'];
		$noSign = $configResult['noSign'];
	}



	print '	  <p>' . $langs->trans('UptoSignFirstPlaceUpToSignSeal') . '</p><div class="" id="paramContainer"></div>' . "\n";
	if (empty($noSign)) {
		print '	  <p style="padding-top: 90px;">' . $langs->trans('UptoSignTwoPlaceListSigns') . ':</p><div class="" id="paramContainerBis"></div>' . "\n";
	}

	print '	  <p style="padding-top: 90px;">' . $langs->trans('UptoSignThreeTitleAndButton') . ':</p>' . "\n";
	print '	</div> <!-- end of fichethirdleft -->' . "\n";

	print '<div class="ficheright">' . "\n";

	print '	<div id="pdfManager" style="float:left">' . "\n";
	print '	  <div class="row" id="selectorContainer">' . "\n";
	print '		<div class="">' . "\n";
	print '		  <div id="pageContainer" class="uptosignPdfViewer singlePageView uptosignDropzone nopadding" style="background-color:transparent">' . "\n";
	print '			<canvas id="uptosignCanvas" style="border:1px  solid black"></canvas>' . "\n";
	print '		  </div>' . "\n";
	print '		</div>' . "\n";
	print '	  </div>' . "\n";

	print '<div style="clear: both; margin: auto; text-align: center;">'."\n";
	// $signed means a batch is already running for that api_name: offering the title
	// field and the send button again would start a second procedure per signer.
	if (!$signed) {
		// print '<button class="butAction" onclick="formSave();" href="" title="Sauvegarder la position des objets">Sauvegarder le paramétrage</button>';
		$ref_client = $object->ref_client ?? "";
		$defaultTitle = uptosign_make_document_title($object->ref, $ref_client, $objectType);
		print "<label for='refTitle'>" . $langs->trans('UptoSignDocumentTitle') . "</label>\n";
		print "<input type='text' name='refTitle' value='" . dol_escape_htmltag($defaultTitle) . "' size='40'><br />\n";

		if ($object->getNbContacts() > 30) {
			print '		 <p>' . $langs->trans("UptoSignSendToSignListMoreThanTen") . '</p>'."\n";
		} elseif ($object->getNbContacts() == 1) {
			print '		 <button class="butAction" onclick="submit();" href="" title="' . $langs->trans("UptoSignSendToSignList") . '">' . $langs->trans("UptoSignBtnOnlySignListOne", $object->getNbContacts()) . '</button>'."\n";
		} elseif ($object->getNbContacts() > 0) {
			print '		 <button class="butAction" onclick="submit();" href="" title="' . $langs->trans("UptoSignSendToSignList") . '">' . $langs->trans("UptoSignBtnOnlySignList", $object->getNbContacts()) . '</button>'."\n";
		} else {
			print '		 <p>' . $langs->trans("UptoSignSendToSignListEmpty") . '</p>'."\n";
		}
	}
	print '</div>'."\n";

	print '	</div> <!-- end of pdfManager -->' . "\n";

	//La liste des objets a placer sur le document. 1. le sceau toujours présent
	$jsonparameters = uptosign_build_seal_params($positionsSeal);
	$jsonparameters[] = array('paramId' => 'sign-'.$i,
					'description' => str_replace("'", "&apos;", "PRENOM NOM<br />"
					. "Mail : xxxx@xxxxx.xxxxx<br />"
					. "Vérifié par SMS envoyé au +336000000000"),
					'defaultX' => $x,
					'defaultY' => $y,
					'defaultPage' => $p,
					'error' => $error
			);

	print '	  <input type="hidden" id="countOfContacts" name="countOfContacts" value="' . $object->getNbContacts() . '">'."\n";
	$listOfFields = ['signX', 'signY', 'page'];
	foreach ($listOfFields as $f) {
		$fieldName = 'sign-'.$i.'-'.$f;
		print '<input id="'.$fieldName.'" name="'.$fieldName.'" type="hidden" value="">'."\n";
	}
	//TODO
	// print '<input id="'.$uniqID.'" name="'.$uniqID.'" type="hidden" value="'.$c->id.'">'."\n";

	print '	  <input type="hidden" id="jsonparameters" value='."'".json_encode($jsonparameters)."'".' />'."\n";
	// print "<p>liste: " . json_encode($jsonparameters) . "</p>";
	//sera complété par le js
	print '	  <input type="hidden" id="countOfPages" name="countOfPages" value="">'."\n";
	print '		 <input type="hidden" name="action" value="uptosign">'."\n";
	if ($autopositionSign && $autopositionSeal) {
		print '	  <input id="autoposition" type="hidden" value="1" />'."\n";
	}
	print '	</div><!-- end of ficheright -->'."\n";
	print '</form>'."\n";
	print '  </div><!-- end of fichecenter -->'."\n";
}

// End of page
if ($standalone) {
	uptosign_standalone_footer();
} else {
	llxFooter();
	$db->close();
}
