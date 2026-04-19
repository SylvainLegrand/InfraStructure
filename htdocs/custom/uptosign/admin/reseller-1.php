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
 * \file    uptosign/admin/reseller.php
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

$error = 0;
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	// For retrocompatibility Dolibarr < 16.0
	if (floatval(DOL_VERSION) < 16.0 && !class_exists('FormSetup')) {
		dol_include_once('/uptosign/backport/v16/core/class/html.formsetup.class.php');
	} else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	}
}

/*
* Actions
*/

if (($action == "autoCreateContract" || $action == "autoCreateContractAndRecInvoice") && (!GETPOST('token', 'alpha') || GETPOST('token', 'alpha') != newToken())) {
	accessforbidden('Invalid CSRF token');
}

if ($action == "autoCreateContract") {
	$uptosignid = (string) GETPOST('uptosignid', 'aZ09');
	$customerid = (string) GETPOST('customerid', 'aZ09');
	$contrat = uptosignCreateContract($customerid, $uptosignid);
} elseif ($action == "autoCreateContractAndRecInvoice") {
	$uptosignid = (string) GETPOST('uptosignid', 'aZ09');
	$customerid = (string) GETPOST('customerid', 'aZ09');
	$contratid = uptosignCreateContract($customerid, $uptosignid);
	if ($contratid) {
		//facture modele
		$factid = uptosignCreateFacture($customerid, false, false);
		if ($factid) {
			$factRid = uptosignCreateFactureRec($customerid, $contratid, $factid);
			if ($factRid) {
				setEventMessage($langs->trans('UptosignFactRectCreated'));
			}
		}

		//1ere facture ponctuelle
		$factid = uptosignCreateFacture($customerid, true, true);
		if ($factid) {
			setEventMessage($langs->trans('UptosignFirstFactCreated'));
		}
	}
}

/*
* View
*/

$page_name = "resellers";
$help_url = "https://doc.cap-rel.fr/projet_uptosign/";
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = uptosignAdminPrepareHead();
print dol_get_fiche_head($head, 'resellers2', $langs->trans($page_name), -1, "uptosign@uptosign");

$uptosignAccount = uptosignApiCheckResellerMode();
if ($uptosignAccount === null || $uptosignAccount['main_role_level'] != UptoSign::ROLE_LEVEL_RESELLER) {
	print $langs->trans("uptosignAccountNotReseller") . "<br />" . $langs->trans("uptosignAccountNotResellerTXT");
} else {
	// Setup page goes here
	// echo $langs->trans("UptoSignSetupPage");

	if ($uptosignAccount['main_role_level'] == UptoSign::ROLE_LEVEL_RESELLER) {
		echo "<h4>" . $langs->trans("uptosignAccountReseller") . "</h4>";
		echo "<p>" . $langs->trans("uptosignAccountResellerTXT") . "</p>";


		echo "<p>" . $langs->trans("uptosignAccountResellerMyCustomers") . ":</p>";

		echo "<table class='noborder noshadow'>";
		echo "<thead>\n";
		echo "<tr class='liste_titre nodrag nodrop'>\n";
		echo "<th>UptoSign</th>\n";
		echo "<th colspan='2'>Dolibarr</th>\n";
		echo "</tr>\n";
		echo "</thead>\n";

		echo "<tbody>\n";
		if (isset($uptosignAccount['customers']) && count($uptosignAccount['customers']) > 0) {
			foreach ($uptosignAccount['customers'] as $customer) {
				echo "<tr>\n";
				echo "<td><a href='https://app.uptosign.com/admin/s-list/users/s-form/users/" . urlencode($customer['id']) . "'>UpToSign: " . dol_escape_htmltag($customer['firstname']) . " " . dol_escape_htmltag($customer['name']) . "</a></td>\n";
				//search for a local dolibarr thirdpart with that email ...
				$doliTiers = uptosignSearchThirdpartWithEmail($customer['email']);
				if ($doliTiers) {
					echo "<td>" . $langs->transnoentities("uptosignLocalCustomerWithThatEmail", "<a href='" .DOL_URL_ROOT.'/societe/card.php?customerid='.urlencode((string) $doliTiers->id) . "&token=" . newToken() . "'>" . $doliTiers->name . "</a></td>\n");
					//recherche si un contrat magique existe déjà ?
					$tiersContrat = uptosignSearchUptoSignContract($doliTiers->id);
					if ($tiersContrat) {
						echo "<td>" . $langs->transnoentities("uptosignLocalCustomerContract", "<a href='" .DOL_URL_ROOT.'/contrat/card.php?id='.urlencode((string) $tiersContrat->id) . "&token=" . newToken() . "'>" . $tiersContrat->ref . "</a></td>");
					} else {
						if (utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_CREATE_INVOICES', '') != '') {
							echo "<td>" . $langs->transnoentities("uptosignLocalCustomerCreateContractAndRecInvoice", "<a class='butAction' title='" . $langs->transnoentities("uptosignLocalCustomerCreateAutoTooltip") . "' href='" . dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=autoCreateContractAndRecInvoice&customerid='.urlencode((string) $doliTiers->id) . "&uptosignid=" . urlencode($customer['id']) . "&token=" . newToken() . "'>", "</a>") . "</td>\n";
						} elseif (utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_CREATE_CONTRACT', '') != '') {
							echo "<td>" . $langs->transnoentities("uptosignLocalCustomerCreateContract", "<a class='butAction' href='" . dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=autoCreateContract&customerid='.urlencode((string) $doliTiers->id) . "&uptosignid=" . urlencode($customer['id']) . "&token=" . newToken() . "'>", "</a>") . "</td>\n";
						} else {
							echo "<td>" . $langs->transnoentities("uptosignLocalCustomerCreateContractIsDisabled") . "</td>\n";
						}
					}
				} else {
					echo "<td colspan='2'>" . $langs->trans("uptosignThereIsNoLocalCustomerWithThatEmail", dol_escape_htmltag($customer['email'])) . "</td>\n";
				}
				echo "</tr>\n";
			}
		}
		echo "</tbody>\n";
		echo "</table>";

		echo "<p>" . $langs->trans("uptosignAccountResellerBillingData") . ":</p>";
		echo "<textarea cols='80' rows='20'>";
		echo $uptosignAccount['billingData'];
		echo "</textarea>";
	} else {
		echo "<h4>" . $langs->trans("uptosignAccountNotReseller") . "</h4>";
		echo "<p>" . $langs->trans("uptosignAccountNotResellerTXT") . "</p>";
	}
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
