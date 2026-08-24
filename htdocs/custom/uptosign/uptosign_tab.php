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
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
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
dol_include_once('/uptosign/vendor/autoload.php');
dol_include_once('/uptosign/lib/backports.lib.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');

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
$pdfFileName = base64_decode((string) GETPOST('pdfFileName', 'alpha'));
if (!empty($pdfFileName)) {
	$pdfFileName = dol_sanitizePathName($pdfFileName);
	if (strpos(realpath(dirname($pdfFileName)) . '/', realpath(DOL_DATA_ROOT) . '/') !== 0) {
		accessforbidden('Invalid file path');
	}
}
$refTitle = (string) GETPOST('refTitle', 'alpha');
$postAutoposition = GETPOSTISSET('autoposition');
$countOfPages = GETPOSTINT('countOfPages');
//$lineid   = GETPOSTINT('lineid');

$hallobj = uptosign_handle_all_type_of_objects($objectType, $id);
$object = $hallobj['object'];
$modulepart = $hallobj['modulepart'];
$head = $hallobj['head'];
$noSeal = $hallobj['noSeal'];
$signOrSeal = $hallobj['signOrSeal'];


if ($object === null) {
	dol_syslog("uptosign: that sort of object ($objectType) is not handled by this module !", LOG_ERR);
	accessforbidden($langs->trans('UptoSignObjectTypeNotCompatible'));
}

$model_pdf = uptosignModel($object);
$ref = $object->ref;

//default file = the first one
$upload_dir = $pdfFileChoosedFullPath = "";
if($objectType == "societe") {
	$upload_dir = $conf->{$modulepart}->multidir_output[$object->entity] . '/' . $object->id;
}elseif (isset($conf->{$modulepart}->multidir_output[$object->entity])) {
	$upload_dir = $conf->{$modulepart}->multidir_output[$object->entity] . '/' . dol_sanitizeFileName($object->ref);
} elseif (isset($conf->{$modulepart}->dir_output)) {
	$upload_dir = $conf->{$modulepart}->dir_output . '/' . dol_sanitizeFileName($object->ref);
} elseif (!empty($hallobj['pdfpath'])) {
	// Fallback: use pdfpath from uptosign_handle_all_type_of_objects (handles ficheinter, etc.)
	$upload_dir = $hallobj['pdfpath'] . '/' . dol_sanitizeFileName($object->ref);
}
if (empty($pdfFileChoosed)) {
	if (!empty($pdfFileName)) {
		$pdfFileChoosed = $pdfFileName;
	} else {
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

// Security check - restrictedArea on the underlying business object (IDOR + entity guard).
// Only known standard object types are enforced with restrictedArea; unknown/custom types
// are logged and left to the module read right above (no silent bypass).
// Feature name expected by restrictedArea() for each known modulepart. Values match the
// calls in the corresponding core *_card.php pages; defaults (fk_soc/rowid) are reused.
$restrictedAreaFeatures = array(
	'propal'            => 'propal',
	'commande'          => 'commande',
	'facture'           => 'facture',
	'contract'          => 'contrat',
	'project'           => 'projet',
	'societe'           => 'societe',
	'supplier_proposal' => 'supplier_proposal',
);
if ($object->id > 0 && isset($restrictedAreaFeatures[$modulepart])) {
	if ($modulepart == 'societe') {
		$result = restrictedArea($user, 'societe', $object->id, '&societe', '', 'fk_soc', 'rowid');
	} else {
		$result = restrictedArea($user, $restrictedAreaFeatures[$modulepart], $object->id);
	}
	if (!$result) {
		dol_syslog("uptosign: restrictedArea denied access to $objectType #" . $object->id, LOG_WARNING);
		accessforbidden();
	}
} else {
	dol_syslog("uptosign: restrictedArea not enforced for object type '$objectType' (relies on uptosign read right)", LOG_DEBUG);
}
// Entity guard: object must belong to an entity the user can see
if ($object->id > 0 && isset($object->entity) && !in_array((int) $object->entity, array_map('intval', explode(',', (string) $conf->entity)), true) && empty($user->admin)) {
	dol_syslog("uptosign: object $objectType #" . $object->id . " entity " . $object->entity . " not in current entity " . $conf->entity, LOG_WARNING);
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
ob_start();

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$ucontact = new User($db);
$contact = new Contact($db);
$noSign = $noSeal = null;

$title = $langs->trans("UpToSign");
$help_url = '';

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

// Idempotency guard: see uptosign_find_duplicate_inflight() docblock.
// Placed before llxHeader so header() can emit a clean HTTP redirect.
if ($action == 'uptosign' || $action == 'uptoseal') {
	$idemPdfFileName = base64_decode((string) GETPOST('pdfFileName', 'alpha'));
	if (!empty($idemPdfFileName)) {
		$idemPdfFileName = dol_sanitizePathName($idemPdfFileName);
		// Reject paths escaping DOL_DATA_ROOT (defense in depth, same check as the action handler)
		if (strpos(realpath(dirname($idemPdfFileName)) . '/', realpath(DOL_DATA_ROOT) . '/') === 0) {
			$idemDup = uptosign_find_duplicate_inflight(
				$uptoSign,
				(int) $id,
				uptosign_unify_object_type($modulepart),
				$action,
				$idemPdfFileName
			);
			if ($idemDup !== null) {
				dol_syslog("uptosign: duplicate $action request for fk_object=$id, redirect to existing UptoSign #" . $idemDup->id, LOG_WARNING);
				setEventMessages($langs->trans("UptoSignDuplicateRequestRedirect"), [], 'warnings');
				header('Location: ' . dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . (int) $idemDup->id);
				exit;
			}
		} else {
			dol_syslog("uptosign: idempotency check skipped, pdfFileName outside DOL_DATA_ROOT: $idemPdfFileName", LOG_WARNING);
		}
	}
}

llxHeader('', 'UptoSign - Choose sign position', '', '', 0, 0, $arrayofjs, $arrayofcss, '', '', $nomain, 0);
$allreadyUsed = [];

// Race condition "uptosign_local"
if ($action == 'uptosign_local') {
	//save data as a presign process then redirect
	$uptoSign->redirect_sign = true;
	$action = 'uptosign';
}


// Confirmation to sign
if ($action == 'uptosign' || $action == 'uptoseal') {
	//Sauvegarder les données du formulaire (?)
	$pdfFileName = base64_decode((string) GETPOST('pdfFileName', 'alpha'));
	if (!empty($pdfFileName)) {
		$pdfFileName = dol_sanitizePathName($pdfFileName);
		if (strpos(realpath(dirname($pdfFileName)) . '/', realpath(DOL_DATA_ROOT) . '/') !== 0) {
			accessforbidden('Invalid file path');
		}
	}
	$countOfContacts = (string) GETPOST('countOfContacts', 'alpha');
	$countOfUsers = (string) GETPOST('countOfUsers', 'alpha');
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

	//Fichier à signer/sceller
	$fileToSign = array(
		'filename' => basename($pdfFileName),
		'fullname' => $pdfFileName,
		'content' => base64_encode(file_get_contents($pdfFileName)),
		'stampArray' => $stampArray,
		'title' => $refTitle
	);
	dol_syslog("uptosign: stamp data is " . json_encode($stampArray), LOG_DEBUG);

	$listMembers = null;

	// print "<p> recherche des contacts </p>";
	for ($i = 0; $i < $countOfContacts; $i++) {
		//un champ hidden contact-0 contient l'id du contact dolibarr
		$fieldName = 'contact-' . $i;
		$contactID = (string) GETPOST($fieldName, 'alpha');
		if ($contact->fetch($contactID) > 0) {
			$signArray = [];
			//recherche sur chaque page possible si le contact est present
			for ($j = 0; $j <= $countOfPages; $j++) {
				$x = GETPOSTINT($fieldName . '-' . $j . '-signX');
				$y = GETPOSTINT($fieldName . '-' . $j . '-signY');
				$p = GETPOSTINT($fieldName . '-' . $j . '-page');
				$testUsed = $p . ":" . $x . ":" . $y;
				if (!empty($x) && !empty($y)) {
					if (!in_array($testUsed, $allreadyUsed)) {
						$signArray[] = ['x' => $x, 'y' => $y, 'p' => $p];
						$allreadyUsed[] = $testUsed;
					}
				}
			}
			if (count($signArray) > 0) {
				$listMembers[] = array(
					'id' => $contactID,
					'firstname' => $contact->firstname,
					'lastname' => $contact->lastname,
					'company' => $contact->socname,
					'email' => $contact->email,
					'mobile' => uptoSignSearchMobile($contact->phone_mobile, $contact->phone_pro, $contact->country_code),
					'signArray' => $signArray
				);
			}
		}
	}
	// dol_syslog("uptosign: sign people (contact) data is " . json_encode($listMembers), LOG_DEBUG);

	//Liste des utilisateurs dolibarr
	// print "<p>Recherche des utilisateurs : $countOfUsers</p>";
	for ($i = 0; $i < $countOfUsers; $i++) {
		// print "<p>User $i ...</p>";
		$fieldName = 'user-' . $i;
		$contactID = (string) GETPOST($fieldName, 'alpha');
		if ($ucontact->fetch($contactID) > 0) {
			$signArray = [];
			for ($j = 0; $j <= $countOfPages; $j++) {
				$x = GETPOSTINT($fieldName . '-' . $j . '-signX');
				$y = GETPOSTINT($fieldName . '-' . $j . '-signY');
				$p = GETPOSTINT($fieldName . '-' . $j . '-page');
				$testUsed = $p . ":" . $x . ":" . $y;
				if (!empty($x) && !empty($y)) {
					if (!in_array($testUsed, $allreadyUsed)) {
						$signArray[] = ['x' => $x, 'y' => $y, 'p' => $p];
						$allreadyUsed[] = $testUsed;
					} else {
					}
				}
			}

			if (count($signArray) > 0) {
				$listMembers[] = array(
					'id' => $contactID,
					'firstname' => $ucontact->firstname,
					'lastname' => $ucontact->lastname,
					'societe' => $mysoc->name,
					'email' => $ucontact->email,
					'mobile' => uptoSignSearchMobile($ucontact->user_mobile, $ucontact->office_phone, $ucontact->country_code),
					'signArray' => $signArray
				);
			}
		} else {
			print "<p>" . $langs->trans('UptoSignContactDoesNotExist') . "</p>";
		}
	}
	dol_syslog("uptosign: sign people (contact+user) data is " . json_encode($listMembers), LOG_DEBUG);

	// exit;

	// TODO check if ok
	$fieldName = 'socsign';
	$contactID = (string) GETPOST($fieldName, 'alpha');
	dol_syslog("uptosign: $fieldName search for socTiers (0)", LOG_DEBUG);
	if ($contactID != "") {
		dol_syslog("uptosign: $fieldName search for socTiers", LOG_DEBUG);

		$socTiers = new Societe($db);
		if ($socTiers->fetch($contactID) > 0) {
			$names = explode(' ', $socTiers->name);
			$firstname = array_shift($names) ?? $langs->trans('Customer');
			$lastname = implode(' ', $names);
			dol_syslog("uptosign: $fieldName socTiers is $firstname / $lastname", LOG_DEBUG);

			$signArray = [];
			for ($i = 0; $i <= $countOfPages; $i++) {
				$x = GETPOSTINT($fieldName . '-' . $i . '-signX');
				$y = GETPOSTINT($fieldName . '-' . $i . '-signY');
				$p = GETPOSTINT($fieldName . '-' . $i . '-page');
				$testUsed = $p . ":" . $x . ":" . $y;
				if (!empty($x) && !empty($y)) {
					if (!in_array($testUsed, $allreadyUsed)) {
						$signArray[] = ['x' => $x, 'y' => $y, 'p' => ($p)];
						$allreadyUsed[] = $testUsed;
					}
				}
			}

			if (count($signArray) > 0) {
				$listMembers[] = array(
					'id' => $contactID,
					'firstname' => $firstname,
					'lastname' => $lastname,
					'company' => $socTiers->name,
					'email' => $socTiers->email,
					'mobile' => uptoSignFixMobile($socTiers->phone_mobile ?? $socTiers->phone, $socTiers->country_code),
					'signArray' => $signArray
				);
			}
		} else {
			print "<p>" . $langs->trans('UptoSignContactDoesNotExist') . "</p>";
		}
		dol_syslog("uptosign: sign people (contact+user+societe) data is " . json_encode($listMembers), LOG_DEBUG);
	}

	//Il faut au moins une signature
	if ($action == 'uptosign' && $listMembers !== null && count($listMembers) == 0) {
		$error++;
		setEventMessages($langs->trans("UptoSignErrorErrorSignPosition"), [], 'errors');
	}

	if ($action == 'uptosign') {
		$object->uptosignTitle = $langs->trans("UptoSignProcessTitle");
		$object->uptosignMessage = $langs->trans("UptoSignProcessStarted");
	} else {
		$object->uptosignTitle = $langs->trans("UptoSealProcessTitle");
		$object->uptosignMessage = $langs->trans("UptoSealProcessStarted");
	}

	// print "<p>Avant l'appel de signorseal : " . json_encode($object) . "</p>";exit;
	// print "<p>Avant l'appel de signorseal : " . json_encode($listMembers) . "</p>";exit;

	if ($error == 0) {
		$sendRes = $uptoSign->sealOrSignInitLight($user, $object, $fileToSign, $listMembers, $procedure);
	} else {
		$sendRes = -1;
	}

	// print "<p>Retour de l'appel du signorseal : $sendRes</p>";

	if ($sendRes ==  0) {
		if ($action == 'confirm_uptoseal' || $action == 'uptoseal') {
			$message = "SealRequestSuccessful";
		} else {
			$message = "SignRequestSuccessful";
		}
		setEventMessages($langs->trans($message), [], 'mesgs');

		// POST-Redirect-GET: discard buffered output (llxHeader was already emitted into the
		// ob_start buffer above) and redirect to the procedure card. Prevents duplicate
		// creation on F5 or back/forward navigation.
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		header('Location: ' . dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . (int) $uptoSign->id);
		exit;
	} else {
		setEventMessages("sealOrSignInitLight errors : " . implode("\n", $uptoSign->errors), [], 'errors');
	}
}
// if (($action == 'confirm_uptosign' || $action == 'confirm_uptoseal')) {
// }

//
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	print dol_get_fiche_head($head, 'tabUptoSign', $langs->trans("UptoSign"), -1, $object->picto);

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
	$linkback = '<a href="' . dol_buildpath('/uptosign/uptosign_tab.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
	// dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	// print '</div>' . "\n";
	print '<form id="leform" name="leform" method="POST" action="' . dol_buildpath('/uptosign/uptosign_tab.php', 1) . '">' . "\n";

	if ($action == "" || $action == "preseal") {
		$signOrSeal = 'seal';
		$noSign = 1;
	} else {
		$signOrSeal = 'sign';
		$noSeal = 1;
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div id="presend">' . "\n";

		// Send
		$api_name = uptosign_unify_api_name($signOrSeal);
		// Construct full path if not yet set (when pdfFileChoosed comes from GET parameter)
		if (empty($pdfFileChoosedFullPath) && !empty($pdfFileChoosed) && !empty($upload_dir)) {
			$pdfFileChoosedFullPath = dol_osencode(dol_sanitizePathName($upload_dir . '/' . $pdfFileChoosed));
		}
		// Convert to relative path to match DB storage (uptosign stores relative paths via uptosign_relative_path)
		$pathFileFilter = !empty($pdfFileChoosedFullPath) ? uptosign_relative_path($pdfFileChoosedFullPath) : '';
		$result = $uptoSign->fetchByObject((int) $id, uptosign_unify_object_type($modulepart), array('api_name' => $api_name, 'path_file' => $pathFileFilter));
		$signed = false;
		// print "<p>UptoSignSignedNoModify : "  .  json_encode($result). "</p>";
		// print json_encode($uptoSign);
		if (is_array($result) && count($result) > 0) {
			$uts = reset($result);
			if ($uts !== false) {
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
						$target = dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . $uts->id;
						print "<button class=\"butAction\" onclick=\"event.preventDefault();window.location.href='" . $target . "'\" title=\"Procédure en cours\">Une procédure est déjà en cours</button>";
					}
				} elseif ($action == 'preseal' && $uts->api_name == 'uptoseal') {
					$signed = true;
					if ($signStatus == UptoSign::STATUS_SIGNED || $signStatus == UptoSign::STATUS_SEALED || $signStatus == UptoSign::STATUS_FILE_FETCHED) {
						// print "<p>UptoSignSignedNoModify</p>";
						print '<button class="butAction" onclick="#" href="" title="Document déjà scellé">Ce document est déjà scellé !</button>';
					}
					if ($signStatus == UptoSign::STATUS_WAITING) {
						$target = dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . $uts->id;
						print "<button class=\"butAction\" onclick=\"event.preventDefault();window.location.href='" . $target . "'\" title=\"Procédure en cours\">Une procédure est déjà en cours</button>";
					}
				}
			}
		}
		if ($signed) {
		} else {
			// print '<button class="butAction" onclick="formSave();" href="" title="Sauvegarder la position des objets">Sauvegarder le paramétrage</button>';
			$ref_client = $object->ref_client ?? "";
			$defaultTitle = uptosign_make_document_title($object->ref, $ref_client, $objectType);
			print "<label for='refTitle'>" . $langs->trans('UptoSignDocumentTitle') . "</label>\n";
			print "<input type='text' name='refTitle' value='" . dol_escape_htmltag($defaultTitle) . "' size='40'>\n";
			if (empty($noSign)) {
				print '		 <input type="hidden" id="action" name="action" value="uptosign">' . "\n";
				print '		 <button class="butAction" href="" title="' . $langs->trans("UptoSignSendToSign") . '">' . $langs->trans("UptoSignBtnOnlySign") . '</button>';
				if (utsbackports_getDolGlobalString('UPTOSIGN_ADD_DIRECT_SIGN_LOCAL')) {
					//convert ...getOnlineSignatureUrl gets 'proposal' | contract | fichinter | else
					// print "<p>search url sign for " . json_encode($details) . "</p>";
					$url = utsbackports_getOnlineSignatureUrl(0, $object->element, $object->ref, 1, $object, $pdfFileChoosed);
					if ($url != "") {
						print '<button class="butAction classfortooltip" onclick="document.getElementById(\'action\').value=\'uptosign_local\'" title="' . $langs->trans('UptoSignProcedureLocalSign') . '" ><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignBtnLocalSign') . '</button>';
					}
				}
			}
			if (empty($noSeal)) {
				print '		 <input type="hidden" name="action" value="uptoseal">' . "\n";
				print '		 <button class="butAction" title="' . $langs->trans("UptoSignSendToSeal") . '">' . $langs->trans("UptoSignBtnOnlySeal") . '</button>';
			}
		}
		print '</div> <!-- end of presend -->' . "\n";
	}
	print '	<div class="fichecenter">' . "\n";
	print ' 	<div class="fichethirdleft" style="padding:10px; max-width: 200px">' . "\n";
	uptosign_render_page_nav();
	dol_syslog("uptosign: pdfFileChoosed is " . $pdfFileChoosed);
	$fileInfo = uptosign_render_pdf_selector($upload_dir, $pdfFileChoosed, $pdfFileChoosedFullPath);
	print '	  <input type="hidden" id="objectType" name="objectType" value="' . $objectType . '">' . "\n";
	print '	  <input type="hidden" id="id" name="id" value="' . $id . '">' . "\n";
	print '		 <input type="hidden" name="token" value="' . newToken() . '">' . "\n";
	// print '	  <input type="hidden" id="" name="" value="' . $ . '">' . "\n";
	// print '	  <input type="hidden" id="" name="" value="' . $ . '">' . "\n";

	$autopositionSeal = $autopositionSign = false; //new way: seal is distinct of sign
	$positionsSeal = $positionsSign = array();
	print "<p>" . $langs->trans("ModelDocument") . " : " . $model_pdf . "</p>";

	if ($action == "" || $action == "preseal") {
		$actionSeal = "<li><b>Sceller (actif)</b></li>\n";
		$actionSign = "<li><a href='" . dol_escape_htmltag($_SERVER["PHP_SELF"]) . "?objectType=" . urlencode($objectType) . "&id=" . urlencode((string) $id) . "&action=presign&pdfFileChoosed=" . urlencode($pdfFileChoosed) . "'>Signer</a></li>\n";
	} else {
		$actionSeal = "<li><a href='" . dol_escape_htmltag($_SERVER["PHP_SELF"]) . "?objectType=" . urlencode($objectType) . "&id=" . urlencode((string) $id) . "&action=preseal&pdfFileChoosed=" . urlencode($pdfFileChoosed) . "'>Sceller</a></li>\n";
		$actionSign = "<li><b>Signer (actif)</b></li>\n";
	}
	print "<p>Action possible : <ul>\n" . $actionSeal . $actionSign . "</ul>\n</p>\n";

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
		print '	  <p style="padding-top: 90px;">' . $langs->trans('UptoSignTwoPlaceSigns') . ':</p><div class="" id="paramContainerBis"></div>' . "\n";
	}
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
	print '	</div> <!-- end of pdfManager -->' . "\n";

	//La liste des objets a placer sur le document. 1. le sceau toujours présent
	$jsonparameters = uptosign_build_seal_params($positionsSeal);

	//Cas d'une signature de document
	if (empty($noSign)) {
		//On ajoute la liste des personnes qui peuvent signer le document ...
		$contacts = new ArrayObject();
		$uptoSign->whoCanSign($object, 'internal', "", 				$contacts);
		$uptoSign->whoCanSign($object, 'internal', "CustomerSign", 	$contacts);
		$uptoSign->whoCanSign($object, 'internal', "VendorSign", 	$contacts);
		$uptoSign->whoCanSign($object, 'external', "CustomerSign", 	$contacts);

		// print json_encode($contacts);exit;

		// print "<pre>Ajout de contact :\n\n" . json_encode($contacts) . "</pre>";
		// print "<p>Contacts:</p><p> :\n\n" . json_encode($contacts) . "</p>";
		$contactCount = 0;
		$userCount = 0;
		$totalCountUser = 0;
		$totalCountContact = 0;
		$hasContact = false;

		//pour savoir s'il y a des contacts liés à la société ou s'il faut
		//utiliser le tiers comme signataire (cas particulier des ... particuliers !)
		//et au passage on affecte le tag SIGN_ et FROM_
		$nbFrom = $nbSign = 0;
		foreach ($contacts as $c) {
			// print "<p> c is " . gettype($c) . "</p>";
			if (! in_array($c->element, ["user", "societe"])) {
				$tag = sprintf("SIGN_%'02d", $nbSign);
				$c->uptoTag = $tag;
				$hasContact = true;
				$nbSign++;
			} else {
				$tag = sprintf("FROM_%'02d", $nbSign);
				$c->uptoTag = $tag;
				$nbFrom++;
			}
		}

		$eviteDoublon = $eviteDoublonJson = array();

		foreach ($contacts as $c) {
			$email = trim($c->email);
			if (in_array($email, $eviteDoublon)) {
				continue;
			}
			$eviteDoublon[] = $email;

			if (!$autopositionSign) {
				// print "<p> turn on contact without auto position ... </p>";
				//$totalCount > 0 pour laisser le 1er contact pré-positionné mais éviter que tous les
				//autres s'entassent au même endroit sur le pdf
				if ($positionsSign[0]['SIGN_00']['defaultSignContactX'] == null) {
					$positionsSign[0]['SIGN_00']['defaultSignContactX'] = 0;
					$positionsSign[0]['SIGN_00']['defaultSignContactY'] = (50 * $totalCountContact);
					$positionsSign[0]['SIGN_00']['defaultSignContactPage'] = 0;
				}
				if ($positionsSign[0]['FROM_00']['defaultSignUserX'] == null) {
					$positionsSign[0]['FROM_00']['defaultSignUserX'] = 0;
					$positionsSign[0]['FROM_00']['defaultSignUserY'] = (50 * $totalCountUser);
					$positionsSign[0]['FROM_00']['defaultSignUserPage'] = 0;
				}
			}

			$isUser = $isSoc = $isContact = false;
			//contact (socpeople) or dolibarr user ?
			if ($c->element == "user") {
				dol_syslog("uptosign: user " . json_encode($c));
				$uniqID = 'user-' . $userCount;
				$numero = uptoSignSearchMobile($c->user_mobile, $c->office_phone, $c->country_code);

				$name = "USER:" . strtoupper($c->lastname) . " " . ucfirst($c->firstname);
				$isUser = true;
				$userCount++;
			} elseif ($c->element == "societe") {
				dol_syslog("uptosign: societe " . json_encode($c));
				//si aucun contact n'est lié à ce tiers, utilisation du tiers
				if (!$hasContact) {
					dol_syslog("uptosign: tiers sans contact, utilisation du tiers " . json_encode($c));
					$uniqID = 'socsign';
					$numero = uptoSignFixMobile($c->phone_mobile ?? $c->phone, $c->state_code ?? $c->country_code);
					$name = "TIERS:" . $c->name;
					$isSoc = true;
				} else {
					continue;
				}
			} elseif ($c->element == 'contact' || $c->source == "external") {
				dol_syslog("uptosign: contact ou externe " . json_encode($c));

				if ($contact->fetch($c->id)) {
					$uniqID = 'contact-' . $contactCount;
					// print "<pre>Ajout de contact $contactCount :\n\n" . json_encode($contact) . "</pre>";
					$numero = uptoSignSearchMobile($contact->phone_mobile, $contact->phone_pro, $contact->country_code);
					if (empty($numero)) {
						// continue;
					}
					$name = "CONTACT:" . strtoupper($c->lastname) . " " . ucfirst($c->firstname);
					$isContact = true;
					$contactCount++;
				} else {
					continue;
				}
			} else {
				dol_syslog("uptosign: garbage collector " . json_encode($c));
				$uniqID = 'garbage collector';
				$name = "NONAME";
				// print '<p>' .$c->source . "::". json_encode($c). '</p>';
			}

			// print '<p>' .$c->source . "::". json_encode($c). '</p>';
			$error = false;
			$numeroTXT = "";
			if (!isset($numero) || $numero == "") {
				$numeroTXT = "<b style='color: red;'>" . $langs->trans('UptoSignMissingPhoneNumber') . "</b>";
				$error = true;
				$numero = -1;
			} elseif (substr($numero, 0, 1) != "+") {
				$numeroTXT = "<b style='color: red;'>" . $langs->trans('UptoSignErrorPhoneNumber') . "</b>";
				$error = true;
			} else {
				$numeroTXT = $numero;
			}
			if ($email == "") {
				$emailTXT = "<b style='color: red;'>" . $langs->trans('UptoSignMissingMail') . "</b>";
				$error = true;
				$email = -1;
			} else {
				$emailTXT = $email;
			}

			if ($numero == -1 && $email == -1) {
				dol_syslog("uptosign: continue due to numero=$numero or email=$email " . json_encode($c));
				continue;
			}

			// print "<p>debug: " . json_encode($positionsSign) . "</p>";
			if (strpos($uniqID, 'user-') === false) {
				$totalCountUser++;
			} else {
				$totalCountContact++;
			}

			// print "<p>debug: Position to sign: " . json_encode($positionsSign) . "</p>";


			// print "<p>debug: Ajoute dans la liste $email ($uniqID) " . $c->uptoTag . "</p>";
			// print "<p>debug: Position to sign: " . json_encode($positionsSign[$c->uptoTag]) . "</p>";
			$i = 0;
			dol_syslog("uptosign: lancement de la boucle sur " . json_encode($positionsSign));
			//genere les champs hidden html pour les differents objets
			foreach ($positionsSign as $page => $posSign) {
				$x = $y = $p = 0;
				dol_syslog("uptosign: [$i/$page] boucle foreach sur $email toutes positions = " . json_encode($posSign));
				$tabKey = "";
				if (strpos($uniqID, 'user-') === false) {
					$defaultKeyword = "Contact";
					if (!isset($posSign['defaultSign' . $defaultKeyword . 'X'])) {
						$tabKey = "SIGN_00";
					}
					$testUsed = ($posSign['defaultSignContactPage'] ?? 0) . ":" . ($posSign['defaultSignContactX'] ?? 0) . ":" . ($posSign['defaultSignContactY'] ?? 0);	// InfraS change
					if (!$autopositionSign && in_array($testUsed, $allreadyUsed)) {
						$posSign['defaultSignContactPage'] = $posSign['defaultSignContactX'] = $posSign['defaultSignContactY'] = 0;
					} else {
						$allreadyUsed[] = $testUsed;
					}
					// print "<p>debug: Position to sign: $i " . json_encode($posSign) . "</p>";
				} else {
					$defaultKeyword = "User";
					if (!isset($posSign['defaultSign' . $defaultKeyword . 'X'])) {
						$tabKey = "FROM_00";
					}
					$testUsed = ($posSign['defaultSignUserPage'] ?? 0) . ":" . ($posSign['defaultSignUserX'] ?? 0) . ":" . ($posSign['defaultSignUserY'] ?? 0);	// InfraS change
					if (!$autopositionSign && in_array($testUsed, $allreadyUsed)) {
						$posSign['defaultSignUserPage'] = $posSign['defaultSignUserX'] = $posSign['defaultSignUserY'] = 0;
					} else {
						$allreadyUsed[] = $testUsed;
					}
				}

				//si pas positionné sur la page -> 0 / 0 ... il ne faut pas de doublons !
				$x = $posSign[$tabKey]['defaultSign' . $defaultKeyword . 'X'] ?? 0;
				$y = $posSign[$tabKey]['defaultSign' . $defaultKeyword . 'Y'] ?? 0;
				$p = $posSign[$tabKey]['defaultSign' . $defaultKeyword . 'Page'] ?? 0;
				if ($x == 0 && $p == 0 && in_array($email, $eviteDoublonJson)) {
					dol_syslog("uptosign: [$i/$page] $x / $p ($defaultKeyword | $$tabKey) evite doublon ... " . json_encode($posSign));
					continue;
				}

				$eviteDoublonJson[] = trim($email);
				// print "<p>[$i] $email : pas un doublon dans " . json_encode($eviteDoublonJson) . " on ajoute $email ... $x $p</p>";

				$jsonparameters[] = array(
					'paramId' => $uniqID . '-' . $i,
					'description' => str_replace("'", "&apos;", $name . "<br />"
						. "Mail : " . $emailTXT . "<br />"
						. "Vérifié par SMS envoyé au " . $numeroTXT),
					'defaultX' => $x,
					'defaultY' => $y,
					'defaultPage' => $p,
					'error' => $error
				);

				dol_syslog("uptosign: [$i/$page] avant le 2° foreach, x=$x, y=$y, p=$p");
				$listOfFields = ['signX', 'signY', 'page'];
				$fieldName = '';
				foreach ($listOfFields as $f) {
					$fieldName = $uniqID . '-' . $i . '-' . $f;
					print '<input id="' . $fieldName . '" name="' . $fieldName . '" type="hidden" value="">' . "\n";
				}
				dol_syslog("uptosign: [$i/$page] apres le 2° foreach... $fieldName");
				$i++;
			}
			dol_syslog("uptosign: [$i] apres la boucle...");
			print '<input id="' . $uniqID . '" name="' . $uniqID . '" type="hidden" value="' . $c->id . '">' . "\n";
		}
		print '	  <input type="hidden" id="countOfContacts" name="countOfContacts" value="' . $contactCount . '">' . "\n";
		print '	  <input type="hidden" id="countOfUsers" name="countOfUsers" value="' . $userCount . '">' . "\n";
	}

	// dol_syslog('uptosign: positions EE :' . json_encode($jsonparameters));

	print '	  <input type="hidden" id="jsonparameters" value=' . "'" . json_encode($jsonparameters) . "'" . ' />' . "\n";
	// print "<p>liste: " . json_encode($jsonparameters) . "</p>";
	//sera complété par le js
	print '	  <input type="hidden" id="countOfPages" name="countOfPages" value="">' . "\n";
	if ($autopositionSign && $autopositionSeal) {
		print '	  <input id="autoposition" type="hidden" value="1" />' . "\n";
	}
	print '	</div><!-- end of ficheright -->' . "\n";
	print '  </div><!-- end of fichecenter -->' . "\n";
	print '</form>' . "\n";
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
