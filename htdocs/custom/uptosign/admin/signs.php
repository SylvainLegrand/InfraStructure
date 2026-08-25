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
 * \file    uptosign/admin/signs.php
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
dol_include_once('/uptosign/lib/uptosign.lib.php');
dol_include_once('/uptosign/class/uptosign.class.php');

// Translations
$langs->loadLangs(array("admin", "hrm", "uptosign@uptosign"));

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

$res = $user->fetchAll();
$error = 0;
/*
 * Actions
 */
$listToSave = [];
if ($action == 'update') {
	$i = 0;
	foreach ($user->users as $u) {
		$val = (string) GETPOST('cbx-' . $u->id, 'alpha');
		if ($val) {
			$listToSave[] = $u->id;
		}
	}

	$db->begin();

	$ok = true;
	$result = dolibarr_set_const($db, 'UPTOSIGN_DOLIBARR_USERS_SIGN', implode(',', $listToSave), 'chaine', 0, '', $conf->entity);
	if ($result < 0) {
		$ok = false;
	}
	if (!$error) {
		$db->commit();
		if (empty($nomessageinupdate)) {
			setEventMessages($langs->trans("SetupSaved"), [], 'mesgs');
		}
	} else {
		$db->rollback();
		if (empty($nomessageinupdate)) {
			setEventMessages($langs->trans("SetupNotSaved"), [], 'errors');
		}
	}
}

/*
 * View
 */

$form = new Form($db);

$page_name = "UptoSignSigns";
$help_url = "https://doc.cap-rel.fr/projet_uptosign/";
llxHeader('', $langs->trans($page_name), $help_url);

$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = uptosignAdminPrepareHead();
print dol_get_fiche_head($head, 'signs', $langs->trans($page_name), 0, 'uptosign@uptosign');

dol_include_once('/uptosign/core/modules/modUptoSign.class.php');

print $langs->trans("UptoSignSignsLongTxt1");

//Liste des uid des utilisateurs
$listOfCheckUsers = explode(',', utsbackports_getDolGlobalString('UPTOSIGN_DOLIBARR_USERS_SIGN', ''));

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" data-submit-once>';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

$i = 0;

print "<ul>";
foreach ($user->users as $u) {
	//On n'affiche pas les utilisateurs qui ne sont plus actifs
	$status = $u->statut ?? $u->status;
	if ($status == 0) {
		dol_syslog("uptosign, user " . $u->login . " is excluded due to his status (disabled ?)");
		continue;
	}
	//ni les utilisateurs externes liés à des tiers
	if (!empty($u->socid)) {
		dol_syslog("uptosign, user " . $u->login . " is linked to socid=" . $u->socid . ", (external ?)");
		continue;
	}

	print "<li> ";
	$disabled = "";
	$checked = "";
	$tel = uptoSignSearchMobile($u->user_mobile, $u->office_phone, $u->country_code);

	if ($tel == "" || substr($tel, 0, 1) != '+') {
		$tel = "<font style='color:red;'>" . $langs->trans('UptoSignUserPhoneMobileWrongFormat') . "</font>";
		$disabled = "disabled";
	}

	$email = trim($u->email);
	if ($email == "") {
		$email = "<font style='color:red;'>" . $langs->trans('UptoSignUserEmailMissing') . "</font>";
		$disabled = "disabled";
	} else {
		$email = dol_escape_htmltag($email);
	}

	$right = "";
	$u->getRights();
	if ($u->rights->uptosign->sign) {
	} else {
		$right = "<font style='color:red;'>" . $langs->trans('UptoSignUserCannotSign') . "</font>";
		$disabled = "disabled";
	}

	if (in_array($u->id, $listOfCheckUsers)) {
		$checked = "checked";
	}

	print "<input type='checkbox' name='cbx-" . $u->id . "' id='cbx-" . $u->id . "' value='" . $u->id . "' $disabled $checked> ";

	$style = "''";
	if ($u->job) {
		print "<b>" . dol_escape_htmltag($u->job) . ":</b> ";
		$style = ""; //"'padding-left: 2em;'";
	}
	$remarques = "";

	if ($u->employee) {
		$remarques = " (" . $langs->trans("Employee") . ") ";
	} else {
		$remarques = " (" . $langs->trans("UptosignNotEmployee") . ") ";
	}

	print "<label for='cbx-" . $u->id . "'><span style=$style>" . dol_escape_htmltag($u->firstname) . " " . dol_escape_htmltag($u->lastname) . $remarques . " mail: " . $email . " tel: " . $tel . " " . $right .  "</span></label>";
	print "</li>";
	$i++;
	// print json_encode($u);
}
print "</ul>";
print '<br><div class="center">';
print '<input class="butAction" type="submit" value="' . $langs->trans("Save") . '">';
print '</div>';

print "</form>";


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
