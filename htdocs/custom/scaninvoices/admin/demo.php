<?php
/* Copyright (C) 2026 Éric Seigne <eric.seigne@cap-rel.fr>
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
 * \file    scaninvoices/admin/demo.php
 * \ingroup scaninvoices
 * \brief   Demonstration data set of module ScanInvoices.
 *
 * Reachable by its address only, never listed in the tabs: it writes fictitious
 * suppliers and throw away accounts sharing a weak password, and it deletes in
 * bulk. The public demonstration service calls it through
 * resources/demo/scripts/seed_module.php, which posts action=generate in GET
 * with democonfirm=yes.
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
	$i--; $j--;
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

global $conf, $db, $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/scaninvoices/lib/scaninvoices_compat.lib.php');
dol_include_once('/scaninvoices/lib/scaninvoices.lib.php');
dol_include_once('/scaninvoices/lib/scaninvoices_demo.lib.php');

// Translations
$langs->loadLangs(array("admin", "scaninvoices@scaninvoices"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$results = array();

/*
 * Actions
 */

if ($action == 'generate' || $action == 'purge') {
	if (!GETPOST('token', 'alpha') || GETPOST('token', 'alpha') !== currentToken()) {
		dol_syslog('ScanInvoices demo: '.$action.' refused, bad token', LOG_WARNING);
		accessforbidden('Bad token');
	}

	// An acknowledgement of its own, distinct from confirm: the HTTP test suite
	// posts every action it finds in an admin page with confirm=yes, and would
	// generate the data set into the harness database on every run.
	if (GETPOST('democonfirm', 'alpha') !== 'yes') {
		dol_syslog('ScanInvoices demo: '.$action.' called without the explicit acknowledgement, nothing done', LOG_WARNING);
		setEventMessages($langs->trans("ScanInvoicesDemoConfirmRequired"), [], 'warnings');
		$action = '';
	}
}

if ($action == 'generate' || $action == 'purge') {
	// The guard covers the two mutating actions only: the page stays readable,
	// an administrator may see what it would do. DEMO_INSTANCE is the door the
	// public demonstration service comes through, and the only one it sets.
	if (!scaninvoicesDemoAllowed()) {
		dol_syslog('ScanInvoices demo: refused "'.$action.'" on a production-like install', LOG_WARNING);
		accessforbidden($langs->trans('ScanInvoicesDemoRefusedOnProduction'));
	}
}

if ($action == 'generate') {
	$outcome = scaninvoicesDemoGenerate($db, $user);
	$results = $outcome['results'];

	if ($outcome['error']) {
		setEventMessages($langs->trans("ScanInvoicesDemoGenerateFailed", $outcome['error']), [], 'errors');
	} elseif ($outcome['warnings']) {
		setEventMessages($langs->trans("ScanInvoicesDemoGeneratedWithWarnings", $outcome['warnings']), [], 'warnings');
	} else {
		setEventMessages($langs->trans("ScanInvoicesDemoGenerated"), [], 'mesgs');
	}
}

if ($action == 'purge') {
	$outcome = scaninvoicesDemoPurge($db, $user);
	$results = $outcome['results'];

	if ($outcome['error']) {
		setEventMessages($langs->trans("ScanInvoicesDemoPurgeFailed", $outcome['error']), [], 'errors');
	} elseif ($outcome['warnings']) {
		setEventMessages($langs->trans("ScanInvoicesDemoPurgedWithWarnings", $outcome['warnings']), [], 'warnings');
	} else {
		setEventMessages($langs->trans("ScanInvoicesDemoPurged"), [], 'mesgs');
	}
}

/*
 * View
 */

$page_name = "ScanInvoicesDemoTitle";
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'object_scaninvoices@scaninvoices');

$seeded = scaninvoicesDemoCount($db);

print '<span class="opacitymedium">'.$langs->trans("ScanInvoicesDemoIntro").'</span><br><br>';

print '<div class="info">';
print $langs->trans("ScanInvoicesDemoState", $seeded);
print '</div><br>';

if (!scaninvoicesDemoAllowed()) {
	print '<div class="warning">'.$langs->trans("ScanInvoicesDemoRefusedOnProduction").'</div><br>';
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" data-submit-once>';
print '<input type="hidden" name="token" value="'.newToken().'">';
// The action travels in a hidden field rather than on the buttons: the
// anti-double-click handler disables the button it was clicked on, and a
// disabled button carries neither its name nor its value.
print '<input type="hidden" name="action" id="scaninvoicesdemoaction" value="">';

print '<label><input type="checkbox" name="democonfirm" value="yes"> ';
print $langs->trans("ScanInvoicesDemoConfirm");
print '</label><br><br>';

print '<button type="submit" class="button" data-demo-action="generate">';
print dol_escape_htmltag($langs->transnoentities("ScanInvoicesDemoGenerate"));
print '</button>';
print ' ';
print '<button type="submit" class="button butActionDelete" data-demo-action="purge">';
print dol_escape_htmltag($langs->transnoentities("ScanInvoicesDemoPurge"));
print '</button>';
print '</form>';

if (!empty($results)) {
	print '<br><div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("ScanInvoicesDemoJournal").'</td></tr>';
	foreach ($results as $line) {
		if (strpos($line, '[ERREUR]') !== false) {
			$style = 'color:#c0392b;font-weight:bold;';
		} elseif (strpos($line, '[WARN]') !== false) {
			$style = 'color:#e67e22;';
		} else {
			$style = 'color:#27ae60;';
		}
		print '<tr class="oddeven"><td style="'.$style.'font-family:monospace;">'.dol_escape_htmltag($line).'</td></tr>';
	}
	print '</table>';
	print '</div>';
}

// Public by construction, and the reason the demonstration can be used at all.
if ($seeded > 0) {
	print '<br>';
	print '<div class="info">';
	print '<strong>'.$langs->trans("ScanInvoicesDemoAccounts").'</strong><br>';
	foreach (scaninvoicesDemoUsers() as $account) {
		print dol_escape_htmltag($account['login'].' / '.SCANINVOICES_DEMO_PASSWORD).'<br>';
	}
	print '</div>';
}

?>
<script>
// The clicked button names the action, the hidden field carries it.
document.querySelectorAll('button[data-demo-action]').forEach(function(button) {
	button.addEventListener('click', function() {
		document.getElementById('scaninvoicesdemoaction').value = button.dataset.demoAction;
	});
});

// Anti-double-click: disable the submit button as soon as the form is submitted (cf. MODULE.md section 12)
document.querySelectorAll('form[data-submit-once]').forEach(function(form) {
	form.addEventListener('submit', function() {
		form.querySelectorAll('button[type="submit"]').forEach(function(btn) {
			btn.disabled = true;
		});
	});
});
</script>
<?php

llxFooter();
$db->close();
