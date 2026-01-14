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
$object = $modulepart = $head = null;
$signed = false;

// Initialize technical objects
if ($objectType == 'uptosignlist') {
	require_once 'class/uptosignlist.class.php';
	require_once 'lib/uptosign_uptosignlist.lib.php';
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
$upload_dir = $pdfFileChoosedFullPath = "";
if (isset($conf->{$modulepart}->multidir_output[$object->entity])) {
	$upload_dir = $conf->{$modulepart}->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
} elseif (isset($conf->{$modulepart}->dir_output)) {
	$upload_dir = $conf->{$modulepart}->dir_output .'/uptosignlist/'.dol_sanitizeFileName($object->ref);
}
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

$permissiontoaccess = $user->rights->uptosign->read;
$permissiontoadd = $user->rights->uptosign->create;
$permissiontodelete = $user->rights->uptosign->delete;

/*
Note: vérification des droits associés et nécessaires:
	- lire les tiers societe->lire;
	- etendre societe->client->voir
	.../...?
*/
$otherModulesRights = [
	$user->rights->societe->lire,
	$user->rights->societe->client->voir,
];
// Security check - Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}
if ($user->socid > 0) {
	$socid = $user->socid;
}
// $isdraft = (($object->statut == $object::STATUS_DRAFT) ? 1 : 0);
// $result = restrictedArea($user, 'uptosign', $object->id, '', '', 'fk_soc', 'rowid');//, $isdraft);
if (empty($permissiontoaccess)) {
	accessforbidden();
}
foreach ($otherModulesRights as $perm) {
	if (empty($perm)) {
		accessforbidden($langs->trans('NeedPerms'));
	}
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
llxHeader('', 'UptoSign - Choose sign position', '', '', 0, 0, $arrayofjs, $arrayofcss, '', '', $nomain, 0);
$allreadyUsed = [];
$x = $y = $p = $s = null;
$error = null;

// Confirmation to sign
if ($action == 'uptosign') {
	//Sauvegarder les données du formulaire (?)
	$pdfFileName = base64_decode((string) GETPOST('pdfFileName', 'alpha'));
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
				dol_syslog("do not start sign for that document, x or y is null");
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
		} else {
			dol_syslog("uptosign: sign target email is empty !", LOG_DEBUG);
			$sendRes = -1;
		}
	}

	// print "<p>Retour de l'appel du signorseal : $sendRes</p>";

	if ($sendRes >=  0) {
		print dol_get_fiche_head($head, 'uptosignlisttab', $langs->trans("UptoSign"), -1, $object->picto);

		$object->setStatut(UptoSignList::STATUS_SENTCOMPLETELY);

		if ($action == 'confirm_uptoseal' || $action == 'uptoseal') {
			$message = "SealRequestSuccessful";
		} else {
			$message = "SignRequestSuccessful";
		}
		setEventMessages($message, [], 'mesgs');

		if ($modulepart == "societe") {
			$url = "<a href='" . dol_buildpath('/uptosign/uptosignlist_card.php', 1) . '?id=' . $uptoSign->id . "'>" . $langs->trans("SignStatus") . "</a>";
		} else {
			$url = $object->getNomUrl(1);
		}

		print "<h2>" . $langs->trans($message) . "</h2>";
		print "<p>" . $langs->transnoentities('UptoSignDetailsHere', $url) . "</p>";

		llxFooter();
		exit;
	} else {
		setEventMessages("sealOrSignInitLight errors : " . implode("\n", $uptoSign->errors), [], 'errors');
	}
}
// if (($action == 'confirm_uptosign' || $action == 'confirm_uptoseal')) {
// }

if ($object->status != UptoSignList::STATUS_VALIDATED) {
	print dol_get_fiche_head($head, 'uptosignlisttab', $langs->trans("UptoSign"), -1, $object->picto);
	print '	<div class="fichecenter">' . "\n";
	print ' 	<div class="fichethirdleft" style="padding:10px; max-width: 200px">' . "\n";
	print "<p>" . $langs->trans("uptosignListProcessInProgress") . "</p>";
	print '     </div>' . "\n";
	print ' </div>' . "\n";
	$object->id = 0;
}

//
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	print dol_get_fiche_head($head, 'uptosignlisttab', $langs->trans("UptoSign"), -1, $object->picto);

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
		$result = $uptoSign->fetchAll('', '', 0, 0, array('customsql'=>"fk_object='" . $id ."' AND object_type='" . uptosign_unify_object_type($modulepart) . "' AND api_name='" . $api_name . "' AND path_file='" . $pdfFileChoosedFullPath . "'"));
		// print "<p>UptoSignSignedNoModify : "  .  json_encode($result). "</p>";
		// print json_encode($uptoSign);
		if (is_array($result)) {
			$uts = reset($result);
			// print "<p>UptoSignSignedNoModify : "  .  json_encode($uts). "</p>";

			$signStatus = $uts->status;
			if ($action == '') {
				$action = 'preseal';
			}
			if ($action == 'presign' && $uts->api_name == 'uptosign') {
				$signed = true;
				if ($signStatus == UptoSign::STATUS_SIGNED || $signStatus == UptoSign::STATUS_SEALED || $signStatus == UptoSign::STATUS_FILE_FETCHED) {
					// print "<p>UptoSignSignedNoModify</p>";
					print '<button class="butAction" onclick="#" href="" title="Document déjà signé">Ce document est déjà signé !</button>';
				}
				if ($signStatus == UptoSign::STATUS_WAITING) {
					$target = dol_buildpath('/uptosign/uptosignlist_card.php', 1) . '?id=' . $uts->id;
					print "<button class=\"butAction\" onclick=\"event.preventDefault();window.location.href='". $target . "'\" title=\"Procédure en cours\">Une procédure est déjà en cours</button>";
				}
			} elseif ($action == 'preseal' && $uts->api_name == 'uptoseal') {
				$signed = true;
				if ($signStatus == UptoSign::STATUS_SIGNED || $signStatus == UptoSign::STATUS_SEALED || $signStatus == UptoSign::STATUS_FILE_FETCHED) {
					// print "<p>UptoSignSignedNoModify</p>";
					print '<button class="butAction" onclick="#" href="" title="Document déjà scellé">Ce document est déjà scellé !</button>';
				}
				if ($signStatus == UptoSign::STATUS_WAITING) {
					$target = dol_buildpath('/uptosign/uptosignlist_card.php', 1) . '?id=' . $uts->id;
					print "<button class=\"butAction\" onclick=\"event.preventDefault();window.location.href='". $target . "'\" title=\"Procédure en cours\">Une procédure est déjà en cours</button>";
				}
			}
		}
		print '</div> <!-- end of presend -->'."\n";
	}
	print '	<div class="fichecenter">' . "\n";
	print ' 	<div class="fichethirdleft" style="padding:10px; max-width: 200px">' . "\n";
	print '			<div style="display: flex; justify-content: space-between;" id="paramPages">
						<button style="display: flex; width: 48px;" id="prev">
							<svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
							</svg>
						</button>
						<div style="display: flex; flex-grow: 20;flex-direction: column;text-align: center;">
							<span>Page: <span id="page_num"></span> / <span id="page_count"></span></span>
						</div>
						<button style="display: flex; width: 48px;" id="next">
							<svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
							</svg>
						</button>
					</div>' . "\n";
	// print "<p>Choix du fichier à signer : (liste des PDF liés à l'objet en cours)";
	// print '			<input type="file" id="file">';
	dol_syslog("uptosign, pdfFileChoosed is " . $pdfFileChoosed);
	//Liste des fichiers PDF liés à cet objet
	/** @phpstan-ignore-next-line */
	$filearray = dol_dir_list($upload_dir, "files", 0, '\.pdf$', ['(\.meta|_preview.*\.png)$'], "name", SORT_ASC, 1);
	$fileInfo = null;
	if (is_array($filearray) && count($filearray) >= 1) {
		//1er fichier par défaut
		$fileInfo = reset($filearray);
		$pdfFileChoosedFullPath = dol_osencode(dol_sanitizePathName($fileInfo['fullname']));
		if (count($filearray) > 1) {
			print '<p>' . $langs->trans('UptoSignChooseFile') . '</p>' . "\n";
			print "<select name='pdfFileChoosed' onchange='pdfFileChange();' style='width:100%;max-width:90%;'>";
			foreach ($filearray as $fileInfo) {
				$s = "";
				if ($pdfFileChoosed != "" && dol_osencode(dol_sanitizePathName($fileInfo['name'])) == $pdfFileChoosed) {
					//note do not use dol_sanitizePathName due to non ascii chars into file name...
					$pdfFileChoosedFullPath = dol_osencode($fileInfo['fullname']);
					$s = "selected";
				}
				print "<option value='" . dol_osencode(dol_sanitizePathName($fileInfo['name'])) . "' $s>" . $fileInfo['name'] . "</option>";
			}
			print "<option value=''></option>";
			print "</select>";
		}
		print '	  <input type="hidden" id="pdfData" name="pdfData" value="' . base64_encode(file_get_contents($pdfFileChoosedFullPath)) . '">' . "\n";
		print '	  <input type="hidden" id="pdfFileName" name="pdfFileName" value="' . base64_encode($pdfFileChoosedFullPath) . '">' . "\n";
	} else {
		print "<p style='color: #f00;font-weight: bold;'>" . $langs->trans('UptoSignNoPdfFilesAssociated') . "</p>";
		print "<p>" . $upload_dir . "</p>";
	}
	print '	  <input type="hidden" id="objectType" name="objectType" value="' . $objectType . '">' . "\n";
	print '	  <input type="hidden" id="id" name="id" value="' . $id . '">' . "\n";
	print '		 <input type="hidden" name="token" value="'.newToken().'">'."\n";
	// print '	  <input type="hidden" id="" name="" value="' . $ . '">' . "\n";
	// print '	  <input type="hidden" id="" name="" value="' . $ . '">' . "\n";

	$autopositionSeal = $autopositionSign = false; //new way: seal is distinct of sign
	$positionsSeal = $positionsSign = array();
	print "<p>" . $langs->trans("ModelDocument") . " : " . $model_pdf . "</p>";

	if ($action == "" || $action =="preseal") {
		$actionSeal = "<li><b>Sceller (actif)</b></li>\n";
		$actionSign = "<li><a href='" . $_SERVER["PHP_SELF"] . "?objectType=" . $objectType . "&id=" . $id . "&action=presign&pdfFileChoosed=".$pdfFileChoosed."'>Signer</a></li>\n";
	} else {
		$actionSeal = "<li><a href='" . $_SERVER["PHP_SELF"] . "?objectType=" . $objectType . "&id=" . $id . "&action=preseal&pdfFileChoosed=".$pdfFileChoosed."'>Sceller</a></li>\n";
		$actionSign = "<li><b>Signer (actif)</b></li>\n";
	}
	// print "<p>Action possible : <ul>\n" . $actionSeal . $actionSign . "</ul>\n</p>\n";

	//En priorite utilisation de la position des mots clés magiques
	if (!empty($fileInfo)) {
		$arr = [];
		if (uptosign_auto_position_magic_keywords($pdfFileChoosedFullPath, $arr, $action)) {
			// print json_encode($arr);exit;

			if (isset($arr['STAMP'])) {
				foreach ($arr['STAMP'] as $page => $value) {
					$positionsSeal[$page]['STAMP'] = array('defaultSealX' => (!empty($value['x']) ? $value['x'] : 0),
											'defaultSealY' => (!empty($value['y']) ? $value['y'] : 0),
											'defaultSealPage' => (!empty($value['p']) ? $value['p'] : 0));
					$autopositionSeal = true;
				}
			}
			for ($idn = 0; $idn < 10; $idn++) {
				$tag = sprintf("SIGN_%'02d", $idn);
				if (isset($arr[$tag])) {
					foreach ($arr[$tag] as $page => $value) {
						dol_syslog("uptosign: auto position detect (sign debug) : $tag // $idn // page=$page :: " . $value['p']);
						$positionsSign[$page][$tag]= array('defaultSignContactX' => (!empty($value['x']) ? $value['x'] : 0),
												'defaultSignContactY' => (!empty($value['y']) ? $value['y'] : 0),
												'defaultSignContactPage' => (!empty($value['p']) ? $value['p'] : 0));
					}
					$autopositionSign = true;
				}
			}
			for ($idn = 0; $idn < 10; $idn++) {
				$tag = sprintf("FROM_%'02d", $idn);
				if (isset($arr[$tag])) {
					foreach ($arr[$tag] as $page => $value) {
						$positionsSign[$page][$tag]= array('defaultSignUserX' => (!empty($value['x']) ? $value['x'] : 0),
												'defaultSignUserY' => (!empty($value['y']) ? $value['y'] : 0),
												'defaultSignUserPage' => (!empty($value['p']) ? $value['p'] : 0));
					}
					$autopositionSign = true;
				}
			}

			// print json_encode($positionsSign);exit;

			dol_syslog('uptosign: auto position detect (sign) :' . json_encode($positionsSign));
			dol_syslog('uptosign: auto position detect (seal) :' . json_encode($positionsSeal));
		} else {
			dol_syslog('uptosign: auto position detect (keywords) fail');
		}
	}

	// note: partial fail possible: autoposition ok pour signature mais pas pour le sceau .. il faut donc quand meme passer sur ce bloc de code
	if ($autopositionSeal == false || $autopositionSign == false) {
		$configIds = $uptoSignConfig->fetchListId($model_pdf, $modulepart, $signOrSeal);
		if ($configIds) {
			// print "<p>Modèle de document : " . json_encode($configIds) . "</p>";
			if (is_array($configIds)) {
				$uptoSignConfig->fetch($configIds[0]);

				//C'est là qu'on evite de prendre l'info si le autoposition a retourné qqchose : ces données ont été renseignées par autoconf
				if (!$autopositionSeal) {
					$d = explode(',', $uptoSignConfig->seal_coordinate);
					$positionsSeal[$uptoSignConfig->page_seal]['STAMP']['defaultSealX'] = $d[0];
					$positionsSeal[$uptoSignConfig->page_seal]['STAMP']['defaultSealY'] = $d[1];
					$positionsSeal[$uptoSignConfig->page_seal]['STAMP']['defaultSealPage'] = $uptoSignConfig->page_seal;
					$autopositionSeal = true;
				}
				//Une seule signature "client" préconfigurée => pour plus de sugnatures utiliser les mots 'magiques'
				if (!$autopositionSign) {
					if (!empty($uptoSignConfig->page_sign)) {	// test si la signature n'est pas désactivée pour ce type de document
						$d = explode(',', $uptoSignConfig->sign_coordinate);
						$positionsSign[$uptoSignConfig->page_sign]['SIGN_00']['defaultSignContactX'] = $d[0];
						$positionsSign[$uptoSignConfig->page_sign]['SIGN_00']['defaultSignContactY'] = $d[1];
						$positionsSign[$uptoSignConfig->page_sign]['SIGN_00']['defaultSignContactPage'] = $uptoSignConfig->page_sign;
						$autopositionSign = true;
					} else {
						$noSign = 1;
					}
				}
				dol_syslog('uptosign: position via profil de doc sign: ' . json_encode($positionsSign));
				dol_syslog('uptosign: position via profil de doc seal: ' . json_encode($positionsSeal));
			} else {
				dol_syslog("Modèle de position des signatures introuvable", LOG_ERR);
			}
		}
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
	if ($signed) {
	} else {
		// print '<button class="butAction" onclick="formSave();" href="" title="Sauvegarder la position des objets">Sauvegarder le paramétrage</button>';
		$ref_client = $object->ref_client ?? "";
		$defaultTitle = uptosign_make_document_title($object->ref, $ref_client, $objectType);
		print "<label for='refTitle'>" . $langs->trans('UptoSignDocumentTitle') . "</label>\n";
		print "<input type='text' name='refTitle' value='" . $defaultTitle . "' size='40'><br />\n";
	}

	if ($object->getNbContacts() > 30) {
		print '		 <p>' . $langs->trans("UptoSignSendToSignListMoreThanTen") . '</p>'."\n";
	} elseif ($object->getNbContacts() == 1) {
		print '		 <button class="butAction" onclick="submit();" href="" title="' . $langs->trans("UptoSignSendToSignList") . '">' . $langs->trans("UptoSignBtnOnlySignListOne", $object->getNbContacts()) . '</button>'."\n";
	} elseif ($object->getNbContacts() > 0) {
		print '		 <button class="butAction" onclick="submit();" href="" title="' . $langs->trans("UptoSignSendToSignList") . '">' . $langs->trans("UptoSignBtnOnlySignList", $object->getNbContacts()) . '</button>'."\n";
	} else {
		print '		 <p>' . $langs->trans("UptoSignSendToSignListEmpty") . '</p>'."\n";
	}
	print '</div>'."\n";

	print '	</div> <!-- end of pdfManager -->' . "\n";

	//La liste des objets a placer sur le document. 1. le sceau toujours présent
	$jsonparameters = array();
	if (count($positionsSeal) > 0) {
		$i = 0;
		foreach ($positionsSeal as $page => $position) {
			$jsonparameters[] = array('paramId' => 'seal-' . $i,
						'description' => "SCEAU UPTOSIGN (obligatoire)<br />"
						 . "Document scellé par uptosign<br />"
						 . "Identifiant unique xxxxx<br />"
						 . "https://uptosign.com/",
						'defaultX' => $position['STAMP']['defaultSealX'],
						'defaultY' => $position['STAMP']['defaultSealY'],
						'defaultPage' => $position['STAMP']['defaultSealPage']
						);
			$listOfFields = array('signX', 'signY', 'page');
			foreach ($listOfFields as $f) {
				$fieldName = 'seal-'.$i.'-'.$f;
				print '<input id="'.$fieldName.'" name="'.$fieldName.'" type="hidden" value="">'."\n";
			}
			$i++;
		}
	} else {
		$i = 0;
		$jsonparameters[] = array('paramId' => 'seal-' . $i,
		'description' => "SCEAU UPTOSIGN (obligatoire)<br />"
		. "Document scellé par uptosign<br />"
		. "Identifiant unique xxxxx<br />"
		. "https://uptosign.com/",
		'defaultX' => 0,
		'defaultY' => 0,
		'defaultPage' => 0
		);
		$listOfFields = array('signX', 'signY', 'page');
		foreach ($listOfFields as $f) {
			$fieldName = 'seal-'.$i.'-'.$f;
			print '<input id="'.$fieldName.'" name="'.$fieldName.'" type="hidden" value="">'."\n";
		}
	}
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
	//TODO CHECK
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
	// print '  </div><!-- end of fichecenter -->'."\n";
	print dol_get_fiche_end();
}


print '</form>'."\n";

print '</div>'."\n";
// End of page
llxFooter();
$db->close();
