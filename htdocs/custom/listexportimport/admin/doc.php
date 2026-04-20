<?php
/* Copyright (C) 2017       AXeL                    <contact.axel.dev@gmail.com>
 * Copyright (C) 2024-2025  Inovea Conseil          <info@inovea-conseil.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		listexportimport/admin/about.php
 * 	\ingroup	listexportimport
 * 	\brief		Doc page about the module
 */
// Dolibarr environment *************************
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

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/listexportimport/lib/listexportimport.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

$backtopage = GETPOST('backtopage', 'alpha');

// Translations
$langs->load("admin");
$langs->load("listexportimport@listexportimport");

// Access control
if (! $user->admin) {
	accessforbidden();
}

/*
 * View
 */
$title = "ListExportImportSetupDocs";
llxHeader('', $langs->trans($title));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'object_inoveaconseil@listexportimport');

// Configuration header
$head = listexportimportAdminPrepareHead();
print dol_get_fiche_head($head, 'doc', $langs->trans($title), -1, "contact");

// Documentation page goes here

// How to use it
print load_fiche_titre($langs->trans("HowToUseIt"), '', 'title_question@listexportimport');

print '<p>'.$langs->trans("HowToUseItDesc").'</p>';
print '<br>';

print '<center>';
print img_picto('', 'doc/list_buttons_mode@listexportimport');
print '<br>';
print '<p>'.$langs->trans("HowToUseItMore").'</p>';
print '</center>';

// Export
print load_fiche_titre($langs->trans("HowExportWorks"), '', 'title_export@listexportimport');

print '<p>'.$langs->trans("HowExportWorksDesc").'</p>';
print '<br>';

print '<center>';
print img_picto('', 'doc/export_process@listexportimport');
print '</center>';

// Import
print load_fiche_titre($langs->trans("HowImportWorks"), '', 'title_import@listexportimport');

print '<p>'.$langs->trans("HowImportWorksDesc").'</p>';
print '<br>';

print '<center>';
print img_picto('', 'doc/import_process@listexportimport');
print '</center>';

print '<br>';
print '<p>'.$langs->trans("HowImportWorksNotes").'</p>';

// Need help
print load_fiche_titre($langs->trans("INeedSomeHelp"), '', 'title_question@listexportimport');

print '<p>'.$langs->trans("INeedSomeHelpDesc").'</p>';
print '<br>';

dol_get_fiche_end();

llxFooter();

$db->close();
