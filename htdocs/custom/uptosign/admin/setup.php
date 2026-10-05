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
 * \file    uptosign/admin/setup.php
 * \ingroup uptosign
 * \brief   UptoSign setup page.
 */

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
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
dol_include_once('/uptosign/lib/uptosign.lib.php');
dol_include_once('/uptosign/class/uptosign.class.php');

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

$result = 0;
$error = 0;


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';
dol_include_once('/uptosign/lib/uptosign_upgrades.lib.php');
dol_include_once('/uptosign/lib/uptosign_uptosignconfig.lib.php');

if ($action == 'bkupParams') {
	$result	= uptosign_bkup_module('uptosign');
}
if ($action == 'restoreParams') {
	$result	= uptosign_restore_module('uptosign');
}
if ($action == 'set') {
	$array = ['UPTOSIGN_ENVIRONMENT', 'UPTOSIGN_LOGIN', 'UPTOSIGN_PASS_API', 'UPTOSIGN_ACCEPT_CGU'];
	$changes = false;
	foreach ($array as $key) {
		$oldvalue = dolibarr_get_const($db, $key, $conf->entity);
		$value = rtrim(GETPOST($key), '/');
		if ($value != $oldvalue) {
			if ($key == 'UPTOSIGN_PASS_API') {
				$value = dol_encode($value);
			}
			dolibarr_set_const($db, $key, $value, 'chaine', 0, '', $conf->entity);
			$changes = true;
		}
	}
	if ($changes) {
		dolibarr_set_const($db, 'UPTOSIGN_KEY_API', '', 'chaine', 0, '', $conf->entity);
		setEventMessages($langs->trans('UptoSignSetupSavedPleaseTest'), [], 'mesgs');
	}
}

$defaultENV = "uptosign-prod";
if (utsbackports_getDolGlobalString('UPTOSIGN_ENVIRONMENT', '')  != '') {
	$defaultENV = utsbackports_getDolGlobalString('UPTOSIGN_ENVIRONMENT', '');
}
$defaultEmail = utsbackports_getDolGlobalString('MAIN_INFO_SOCIETE_MAIL', '');
if (utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', '') != '') {
	$defaultEmail = utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', '');
}
$defaultPassword = "";
if (utsbackports_getDolGlobalString('UPTOSIGN_PASS_API', '') != '') {
	$defaultPassword = dol_decode(utsbackports_getDolGlobalString('UPTOSIGN_PASS_API', ''));
}
$envprodselected = $envdemoselected = $envdevselected = "";
if ($defaultENV == "uptosign-prod") {
	$envprodselected = "selected";
} elseif ($defaultENV == "uptosign-dev") {
	$envdevselected = "selected";
} else {
	$envdemoselected = "selected";
}
$defaultCGU = $defaultCGUchecked = "";
if (utsbackports_getDolGlobalString('UPTOSIGN_ACCEPT_CGU', '')  != '') {
	$defaultCGU = utsbackports_getDolGlobalString('UPTOSIGN_ACCEPT_CGU', '');
	$defaultCGUchecked = "checked";
}
$resetPasswordLink = $createAccountLink = "";

if ($action == 'checkConnectAPI' && !empty(GETPOST('token', 'alpha')) && GETPOST('token', 'alpha') == newToken()) {
	//Note: in case of remote api key removed or disabled, local api is set but can't be used anymore
	if (utsbackports_getDolGlobalString('UPTOSIGN_KEY_API', '')  != "") {
		if (uptosignApiTryLoginWithAPIKey()) {
			//Ok
		} else {
			$result = -1;
			//set empty then will catch by next if :)
			dolibarr_set_const($db, "UPTOSIGN_PASS_API", '', 'chaine', 0, '', $conf->entity);
			$conf->global->UPTOSIGN_KEY_API = "";
		}
	}

	if (utsbackports_getDolGlobalString('UPTOSIGN_KEY_API', '')  == "") {
		//Si ce compte utilisateur existe déjà
		$codeRetour = uptosignApiTryLoginWithUserPass();
		dol_syslog("uptosign: uptosignApiTryLoginWithUserPass, code retour = $codeRetour");
		switch ($codeRetour) {
			case 200:
				//UPTOSIGN_KEY_API is set in uptosignApiTryLoginWithUserPass
				dol_syslog("uptosign:: uptosignApiTryLoginWithUserPass (1)");
				break;
			case 400:
			case 401:
				dol_syslog("uptosign:: uptosignApiTryLoginWithUserPass (2) login or password does not match, maybe new account ?");
				if (uptosignApiCreateAccount()) {
					dol_syslog("uptosign:  : uptosignApiCreateAccount");
				} else {
					//this account exist but that password does not match -> add forgot password link
					$defaultURI = UptoSign::getEndPoint($defaultENV);
					$defaultEmail = utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', '');
					$resetPasswordLink = "<a class='butAction' href='" . $defaultURI .  "/forgot-password?email=" . $defaultEmail . "' target='_blank'>" . $langs->trans("UptoSignResetPass") . "</a>";
					$createAccountLink = "<a class='butAction' href='" . $defaultURI .  "/register' target='_blank'>" . $langs->trans("UptoSignCreateAccount") . "</a>";
					$mesg_type = 'error';
				}
				break;
			default:
		}
	}
}

if ($result == 1) {
	setEventMessages($langs->trans('SetupSaved'), [], 'mesgs');
} elseif ($result == -1) {
	setEventMessages($langs->trans('Error'), [], 'errors');
}

$vendor = dol_buildpath('/uptosign/vendor', 0);
if (!is_dir($vendor)) {
	setEventMessages($langs->trans("Error") . $langs->trans("uptosignErrorVendorDoesNotExists"), [], 'errors');
} else {
	dol_include_once('/uptosign/vendor/autoload.php');
	if (!class_exists("\Smalot\PdfParser\Parser")) {
		setEventMessages($langs->trans("Error") . $langs->trans("uptosignErrorSmalotDoesNotExists"), [], 'errors');
	}
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
$page_name = "server";

llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = uptosignAdminPrepareHead();
print dol_get_fiche_head($head, 'server', $langs->trans($page_name), -1, "uptosign@uptosign");

//List of php functions (depends)
$phpListUsedFunctions = ['finfo_open'];
$phpListExtensions = ['intl', 'fileinfo', 'gd', 'mbstring'];

$moduleError = 0;
foreach ($phpListExtensions as $extension) {
	if (!extension_loaded($extension)) {
		echo "<p>ERROR: $extension is missing, please install and load it</p>";
		$moduleError++;
	}
}
foreach ($phpListUsedFunctions as $function) {
	if (!function_exists($function)) {
		echo "<p>ERROR: $function is missing, please install and load it</p>";
		$moduleError++;
	}
}

if ($moduleError > 0) {
	print dol_get_fiche_end();
	llxFooter();
	exit;
}


//dolibarr core security settings
if (utsbackports_getDolGlobalString('MAIN_SECURITY_HASH_ALGO', '') == '') {
	echo '<span class="error">' . $langs->transnoentities("UPTOSIGNdolibarrSecurityCheck", "<a href='https://doc.cap-rel.fr/projet_dolibarr/securite' target='_blank'>", "</a>") . '</span><br><br>';
	// Page end
	print dol_get_fiche_end();

	llxFooter();
	$db->close();
	exit;
}

// Setup page goes here
echo '<span class="opacitymedium">' . $langs->trans("UPTOSIGNSetupPage") . '</span><br><br>';
print '	<form action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data" data-submit-once>
			<input type="hidden" name="token" value="' . newToken() . '">
			<table width="100%" style="border-spacing: 0px;">
				<tr>
					<td class="uptosignFinal" style="padding: 0px; height: 1px;">&nbsp;</td>
					<td class="uptosignFinal" width="90px" style="padding: 0px; height: 1px; max-width: 90px; min-width: 90px; width: 90px;">&nbsp;</td>
					<td class="uptosignFinal" width="156px" style="padding: 0px; height: 1px; max-width: 156px; min-width: 156px; width: 156px;">&nbsp;</td>
					<td class="uptosignFinal" width="120px" style="padding: 0px; height: 1px; max-width: 120px; min-width: 120px; width: 120px;">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="2" style="font-size: 14px;">
						' . $langs->trans('UptoSignParamAction1') . ' <b><FONT color="#382453">' . $langs->trans('ModuleUptoSignName') . '</FONT></b> <FONT size="2">' . $langs->trans('UptoSignParamAction2') . '</FONT>';

$path		= DOL_DATA_ROOT . '/' . (empty($conf->global->MAIN_MODULE_MULTICOMPANY) || $conf->entity == 1 ? '' : $conf->entity . '/') . 'uptosign/sql';
$bkpfile	= $path . '/update.' . $conf->entity;
if (file_exists($bkpfile)) {
	print '						<a href="' . DOL_URL_ROOT . '/document.php?modulepart=uptosign&file=sql/update.' . $conf->entity . '">' . $langs->trans('UptoSignDownBkup') . '</a>';
}

print '					</td>
					<td colspan="2" align="center">
						<button class="butAction" type="submit" value="bkupParams" name="action">' . $langs->trans('UptoSignParamBkup') . '</button>
						<button class="butAction" type="submit" value="restoreParams" name="action">' . $langs->trans('UptoSignParamRestore') . '</button>
					</td>
				</tr>
				<tr><td colspan="4" align="center" style="padding: 0;"><hr></td></tr>
				<tr><td colspan="4" style="line-height: 1px;">&nbsp;</td></tr>
			</table>
		</form>';

if ($resetPasswordLink != "") {
	print "<div id='resetPassDiv'>";
	print "<p>" .  $langs->trans("UPTOSIGN_LOGIN_USED_RESETPASS") . "</p>";
	print "<p>" .  $langs->trans("UPTOSIGN_LOGIN_USED_RESETPASS2") . "</p>";
	print "<p>" .  $langs->trans("UPTOSIGN_LOGIN_USED_RESETPASS3") . "</p>";
	print "<p><b>" . $resetPasswordLink . "</b> <b>" . $createAccountLink . "</b></p>";
	print "</div>";
} else {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" data-submit-once>';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="set">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="">' . $langs->trans("Parameter") . '</td><td>' . $langs->trans("Value") . '</td></tr>';

	print '<tr class="oddeven"><td class=""><b>' . $langs->trans("UPTOSIGN_ENVIRONMENT") . "</b><br /><i>" . $langs->trans("UPTOSIGN_ENVIRONMENTTooltip") . '</i></td>';
	print '<td>';
	print '<select name="UPTOSIGN_ENVIRONMENT" onchange="formChange();">';
	print '<option value="uptosign-prod" ' . $envprodselected . '>' . $langs->trans("UPTOSIGN_ENVIRONMENT_PROD") . '</option>';
	print '<option value="uptosign-demo" ' . $envdemoselected . '>' . $langs->trans("UPTOSIGN_ENVIRONMENT_DEMO") . '</option>';
	print '<option value="uptosign-dev" ' . $envdevselected . '>' . $langs->trans("UPTOSIGN_ENVIRONMENT_DEV") . '</option>';
	print '</select>';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven"><td class=""><b>' . $langs->trans("UPTOSIGN_LOGIN") . "</b><br /><i>" . $langs->trans("UPTOSIGN_LOGINTooltip") . '</i></td>';
	print '<td>';
	print '<input type="text" name="UPTOSIGN_LOGIN" value="' . dol_escape_htmltag($defaultEmail) . '" class="minwidth300" onchange="formChange();">';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven"><td class=""><b>' . $langs->trans("UPTOSIGN_PASS_API") . "</b><br /><i>" . $langs->trans("UPTOSIGN_PASS_APITooltip") . '</i></td>';
	print '<td>';
	print '<input type="password" name="UPTOSIGN_PASS_API" value="' . dol_escape_htmltag($defaultPassword) . '" class="minwidth300" onchange="formChange();">';
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven"><td class=""><b>' . $langs->trans("UPTOSIGN_ACCEPT_CGU") . "</b><br /><i>" . $langs->trans("UPTOSIGN_ACCEPT_CGUTooltip") . '</i></td>';
	print '<td>';
	print '<input type="checkbox" name="UPTOSIGN_ACCEPT_CGU" id="UPTOSIGN_ACCEPT_CGU" class="minwidth300" onchange="formChange();" value="1" ' . $defaultCGUchecked . '>';
	print '</td>';
	print '</tr>';

	print '</table>';

	print '<br><div class="right">';

	$btnDefaultStatus = "";
	// $defaultENV always holds a value, it falls back to "uptosign-prod"
	if ($defaultEmail != "" && $defaultPassword != "") {
		$btnDefaultStatus = "style='visibility: hidden;'";
	}
	$btnCheckVisible = "";
	if ($defaultCGU == "") {
		$btnDefaultStatus = "style='visibility: hidden;'";
		$btnCheckVisible = "style='visibility: hidden;'";
	}

	print '<input id="saveBtn" class="button button-save" type="submit" value="' . $langs->trans("Save") . '" ' . $btnDefaultStatus . '>';

	print '<a id="checkConnectBtn" class="butAction" href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?action=checkConnectAPI&token=' . newToken() . '"' . $btnCheckVisible . '>' . $langs->trans("CheckConnectToUPTOSIGN") . '</a>';

	print '</div>';

	print '</form>';
	print '<br>';

	print '</div>';

	print "<script language='javascript'>function formChange(){ $('#checkConnectBtn').remove(); if($('#UPTOSIGN_ACCEPT_CGU').prop('checked')) { $('#saveBtn').css('visibility', 'visible');} else { $('#saveBtn').css('visibility', 'hidden');} }</script>";
}


print '<div>Il est nécessaire de faire l\'acquisition de signatures auprès de UpToSign ici : <a href="https://uptosign.com/" target="_blank">https://uptosign.com/</a></p><p>Si vous voulez tester le système vous pouvez ouvrir un compte de test sur le serveur de démo ici : <a href="https://demo.uptosign.org/register" target="_blank">https://demo.uptosign.org/register</a></p></div>';

//print '<div>Vérifiez que votre configuration sécurité est complète</div>';

// Anti-double-click protection
print '<script>
document.querySelectorAll("form[data-submit-once]").forEach(function(form) {
	form.addEventListener("submit", function() {
		var btn = form.querySelector("[type=submit]");
		if (btn) {
			btn.disabled = true;
			btn.dataset.originalText = btn.innerHTML;
			btn.innerHTML = \'<span class="loading loading-spinner loading-xs"></span> \' + (btn.dataset.loadingText || btn.textContent);
		}
	});
});
</script>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
