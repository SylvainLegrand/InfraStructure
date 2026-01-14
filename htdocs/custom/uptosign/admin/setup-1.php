<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
* Copyright (C) 2022 Éric Seigne <eric.seigne@cap-rel.fr>
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
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

/**
 * \file    uptosign/admin/setup-1.php
 * \ingroup uptosign
 * \brief   UptoSign setup page.
 */

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
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/uptosign.lib.php';
require_once "../class/uptosign.class.php";

// Translations
$langs->loadLangs(array("admin", "uptosign@uptosign"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('uptosignsetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = (string) GETPOST('action', 'aZ09');
$backtopage = (string) GETPOST('backtopage', 'alpha');
$modulepart = (string) GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = (string) GETPOST('value', 'alpha');
$label = (string) GETPOST('label', 'alpha');
$scandir = (string) GETPOST('scan_dir', 'alpha');
$type = 'myobject';

$error = 0;
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	// For retrocompatibility Dolibarr < 16.0
	if (floatval(DOL_VERSION) < 16.0 && !class_exists('FormSetup')) {
		require_once __DIR__.'/../backport/v16/core/class/html.formsetup.class.php';
	} else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}


if (utsbackports_getDolGlobalString('UPTOSIGN_REDIRECT_PAGE_AFTER_SIGN', '') == '') {
	$conf->global->UPTOSIGN_REDIRECT_PAGE_AFTER_SIGN = "https://uptosign.com/";
}

if (utsbackports_getDolGlobalString('UPTOSIGN_DISABLE_SMS_GLOBAL_SELECT', '') == '') {
	$conf->global->UPTOSIGN_DISABLE_SMS_GLOBAL_SELECT = 0;
}


// seal file replace native file
// 'UPTOSIGN_FILENAME_SUFFIX_UPTOSEAL' => array('css' => 'minwidth400', 'type' => 'text', 'class' => 'uptosign-prod uptosign-demo','default'=>$langs->trans("SignedCommonSeal")),
// $arrayofparameters = array(
// 	'UPTOSIGN_FILENAME_SUFFIX_UPTOSIGN' => array('css' => 'minwidth400', 'type' => 'text', 'class' => 'uptosign-prod uptosign-demo','default'=>$langs->trans("UptoSignSigned")),
// 	'UPTOSIGN_FILENAME_SUFFIX_PROOF' => array('css' => 'minwidth400', 'type' => 'text', 'class' => '','default'=>$langs->trans("UptoSignProof")),
// 	'UPTOSIGN_REDIRECT_PAGE_AFTER_SIGN' => array('css' => 'minwidth400', 'type' => 'text', 'class' => '','default'=>$langs->trans("UptoSignRedirectAfeterSign")),
// 	'UPTOSIGN_SEND_ERROR_MAIL_TO' => array('css' => 'minwidth400', 'type' => 'text', 'class' => ''),
// 	'UPTOSIGN_USE_PDFTOTEXT' => array('css' => 'minwidth400', 'type' => 'yesno', 'class' => '','default'=>$langs->trans("UPTOSIGN_USE_PDFTOTEXT")),
// 	'UPTOSIGN_HIDE_MAIL_AND_PHONE' => array('css' => 'minwidth400', 'type' => 'yesno', 'class' => '','default'=>$langs->trans("UPTOSIGN_HIDE_MAIL_AND_PHONE")),
// );

$formSetup = new FormSetup($db);
$form = new Form($db);
$item = $formSetup->newItem('UPTOSIGN_DEFAULT_USER')->setAsSelectUser();
$item = $formSetup->newItem('UPTOSIGN_FILENAME_SUFFIX_UPTOSIGN')->setAsString();
$item = $formSetup->newItem('UPTOSIGN_FILENAME_SUFFIX_PROOF')->setAsString();
$item = $formSetup->newItem('UPTOSIGN_REDIRECT_PAGE_AFTER_SIGN')->setAsString();
$item = $formSetup->newItem('UPTOSIGN_SEND_ERROR_MAIL_TO')->setAsString();
$item = $formSetup->newItem('UPTOSIGN_SEND_NOTIF_MAIL_CC')->setAsString();

$item = $formSetup->newItem('UPTOSIGN_USE_PDFTOTEXT')->setAsYesNo();

$item = $formSetup->newItem('THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION')->setAsYesNo();

$item = $formSetup->newItem('UPTOSIGN_FORCE_FIRST_CONTACT_AS_SIGNER')->setAsYesNo();
$item = $formSetup->newItem('UPTOSIGN_FORCE_AS_DEFAULT_SIGN_SYSTEM')->setAsYesNo();
$item = $formSetup->newItem('UPTOSIGN_FORCE_AS_DEFAULT_SIGN_SYSTEM_CLONE')->setAsYesNo();
$item = $formSetup->newItem('UPTOSIGN_CREATE_SIGN_ALL_CONTACT_LINKED')->setAsYesNo();
$item = $formSetup->newItem('UPTOSIGN_CREATE_SIGN_CONTACT_ONLINE')->setAsYesNo();

$item = $formSetup->newItem('UPTOSIGN_ADD_CONTACT_POSTE_FUNCTION')->setAsYesNo();

$item = $formSetup->newItem('UPTOSIGN_ADD_DIRECT_SIGN_LOCAL')->setAsYesNo();

$item = $formSetup->newItem('UPTOSIGN_ACTIVATE_SERVICES_ON_CONTRACT_SIGNED')->setAsYesNo();

$item = $formSetup->newItem('UPTOSIGN_EXPERIMENTAL')->setAsTitle();



$options = [
	'0' => $langs->trans('DigitalSignCodeBySMS'),
	'1' => $langs->trans('DigitalSignCodeByEmail')
];
$item = $formSetup->newItem('UPTOSIGN_DISABLE_SMS_GLOBAL_SELECT')->setAsSelect($options);
$item = $formSetup->newItem('UPTOSIGN_DISABLE_SMS_SELECT_THIRDPART')->setAsYesNo();
$item = $formSetup->newItem('UPTOSIGN_HIDE_MAIL_AND_PHONE')->setAsYesNo();
$item = $formSetup->newItem('UPTOSIGN_DISABLE_GETURL_DEBUG')->setAsYesNo();

/*
* Actions
*/

if ( versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}
include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


// if ($action == 'update' && is_array($arrayofparameters)) {
// 	$db->begin();

// 	$ok = true;
// 	foreach ($arrayofparameters as $key => $val) {
// 		if ($val['type'] != 'yesno' && $val['type'] != 'fieldset') {
// 			$value= (string) GETPOST($key, 'alpha');
// 			//pour nettoyer les noms de fichiers et eviter tout pb
// 			if ($key == "UPTOSIGN_FILENAME_SUFFIX_UPTOSIGN" || $key == "UPTOSIGN_FILENAME_SUFFIX_UPTOSEAL" || $key == "UPTOSIGN_FILENAME_SUFFIX_PROOF") {
// 				$value = dol_sanitizeFileName((string) GETPOST($key, 'alpha'));
// 			}
// 			$result = dolibarr_set_const($db, $key, $value, 'chaine', 0, '', $conf->entity);
// 			if ($result < 0) {
// 				$ok = false;
// 				break;
// 			}
// 		}
// 	}

// 	//Les deux speciaux


// 	if (!$error) {
// 		$db->commit();
// 		if (empty($nomessageinupdate)) {
// 			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
// 		}
// 	} else {
// 		$db->rollback();
// 		if (empty($nomessageinupdate)) {
// 			setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
// 		}
// 	}
// }

// if ($action == 'setYesNo') {
// 	$db->begin();

// 	// Process common param fields
// 	if (is_array($_GET)) {
// 		foreach ($_GET as $key => $val) {
// 			if (preg_match('/^param(\w*)$/', $key, $reg)) {
// 				$param = (string) GETPOST("param" . $reg[1], 'alpha');
// 				$value = GETPOSTINT("value" . $reg[1]);
// 				if ($param) {
// 					$res = dolibarr_set_const($db, $param, $value, 'yesno', 0, '', $conf->entity);
// 					if (!($res > 0)) {
// 						$error++;
// 					}
// 				}
// 			}
// 		}
// 	}

// 	if (!$error) {
// 		$db->commit();
// 	} else {
// 		$db->rollback();
// 		if (empty($nomessageinsetmoduleoptions)) {
// 			setEventMessages($langs->trans("SetupNotSaved"), null, 'errors');
// 		}
// 	}

// 	if ($result == 1) {
// 		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
// 	} elseif ($result == -1) {
// 		setEventMessages($langs->trans('Error'), null, 'errors');
// 	}
// }

// if ((utsbackports_getDolGlobalString('UPTOSIGN_USE_PDFTOTEXT','')  != '') {
// 	try {
// 		$cmd = "pdftotext -v";
// 		$output = array();
// 		if (exec($cmd, $output) === false) {
// 			dol_syslog("Erreur de lancement de la commande pdftotext ... est-elle disponible sur ce serveur ?" . json_encode($e), LOG_WARNING);
// 			setEventMessages($langs->trans("Error") . $langs->trans("uptosignErrorPdfToText"), null, 'warnings');
// 		}
// 	} catch (Exception $e) {
// 		dol_syslog("Erreur de lancement de la commande pdftotext ... est-elle disponible sur ce serveur ?" . json_encode($e), LOG_WARNING);
// 		setEventMessages($langs->trans("Error") . $langs->trans("uptosignErrorPdfToText"), null, 'warnings');
// 	}
// }

/*
* View
*/

$page_name = "settings";

$help_url = "https://doc.cap-rel.fr/projet_uptosign/";
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = uptosignAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "uptosign@uptosign");


// Setup page goes here
echo $langs->trans("UptoSignSetupPage");


// print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
// print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
// print '<input type="hidden" name="action" value="update">';

// print '<table class="noborder" width="100%">';
// print '<tr class="liste_titre"><td class="titlefield">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';

// foreach ($arrayofparameters as $key => $val) {
// 	$display = '';

// 	if ($val['type'] == 'fieldset') {
// 		print '<tr class="liste_titre" style="display: ' . $display . ';"><td>';
// 	} else {
// 		print '<tr class="oddeven" style="display: ' . $display . ';"><td>';
// 	}

// 	if ($langs->trans($key . 'Tooltip') != $key . 'Tooltip') {
// 		print $form->textwithpicto($langs->trans($key), $langs->trans($key . 'Tooltip'), 1, 'helpclickable', '', 0, 3, $key);
// 	} else {
// 		print $langs->trans($key);
// 	}

// 	if ($val['type'] == 'yesno') {
// 		if ($conf->global->$key == "1") {
// 			print '<td align="left"><a href="' . $_SERVER['PHP_SELF'] . '?action=setYesNo&param' . $key . '=' . $key . '&value' . $key . '=0">';
// 			print img_picto($langs->trans("Activated"), 'switch_on');
// 			print '</td></tr>';
// 		} else {
// 			print '<td align="left"><a href="' . $_SERVER['PHP_SELF'] . '?action=setYesNo&param' . $key . '=' . $key . '&value' . $key . '=1">';
// 			print img_picto($langs->trans("Disabled"), 'switch_off');
// 			print '</a></td></tr>';
// 		}
// 	} elseif ($val['type'] == 'fieldset') {
// 		print '</td><td></td></tr>';
// 	} elseif ($val['type'] == 'select') {
// 		print '</td><td>' . $form->selectarray($key, $selectSetup[$key], $conf->global->$key, 0) .
// 		'</td><tr>';
// 	} elseif (!empty($val['type'])) {
// 		print '</td><td><input type="' . $val['type'] . '" name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . ($conf->global->$key ?? '') . '"></td></tr>';
// 	} else {
// 		print '</td><td><input name="' . $key . '"  class="flat ' . (empty($val['css']) ? 'minwidth200' : $val['css']) . '" value="' . ($conf->global->$key ?? '') . '"></td></tr>';
// 	}
// }

// print '</table>';

// print '<br><div class="center">';
// print '<input class="butAction" type="submit" value="' . $langs->trans("Modify") . '">';
// print '</div>';

// print '</form>';
// print '<br>';

if ($action == 'edit') {
	print $formSetup->generateOutput(true);
} else {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
