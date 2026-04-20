<?php
/* Copyright (C) 2024-2025  Inovea Conseil          <info@inovea-conseil.com>
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
 * \file		listexportimport/admin/about.php
 * \ingroup		listexportimport
 * \brief		Support page of module List Export Import.
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
dol_include_once('/listexportimport/core/modules/modListExportImport.class.php');
dol_include_once('/listexportimport/lib/listexportimport.lib.php');
dol_include_once('/listexportimport/lib/inovea_common.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Langs
$langs->loadLangs(array('admin','listexportimport@listexportimport'));

// Access control
if (! $user->admin)
  accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

/*
 * View
 */
$help_url = '';
$title = "ListExportImportSetupSupport";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-listexportimport page-support');

// Subheader
$linkback = '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'object_inoveaconseil.png@listexportimport');

// Configuration header
$head = listexportimportAdminPrepareHead();

print dol_get_fiche_head($head, 'support', $langs->trans("Module432592Name"), 0, "info");

$modClass = new modListExportImport($db);
$moduleVersion = !empty($modClass->getVersion()) ? $modClass->getVersion() : 'NC';

$supportvalue = "/*****"."<br>";
$supportvalue.= " * Module : Liste Export import"."<br>";
$supportvalue.= " * Module version : ".$moduleVersion."<br>";
$supportvalue.= " * Dolibarr version : ".DOL_VERSION."<br>";
$supportvalue.= " * Dolibarr version installation initiale : ".$conf->global->MAIN_VERSION_LAST_INSTALL."<br>";
$supportvalue.= " * Version PHP : ".PHP_VERSION."<br>";
//$supportvalue.= " * Options activées :<br><br>";

$supportvalue.= " *****/"."<br><br>";
$supportvalue.= "Description de votre problème :"."<br>";

// print '<div class="div-table-responsive-no-min">';
print '<table class="centpercent">';

//print '<tr class="liste_titre"><td colspan="2">' . $langs->trans("Authors") . '</td>';
//print '</tr>'."\n";

// Inovea
print '<tr>';
print '<form id="ticket" method="POST" target="_blank" action="https://erp.inovea-conseil.com/public/ticket/create_ticket.php">';
print '<input name=message type="hidden" value="'.$supportvalue.'" />';
print '<input name=email type="hidden" value="'.$user->email.'" />';
print '<td class="titlefield center"><img alt="Inovea Conseil expert Dolibarr" title="Inovea Conseil expert Dolibarr" src="https://www.dolibiz.com/wp-content/uploads/2017/09/inovea.png" /></td>'."\n";
print '<td class="left"><p>'.$langs->trans("InoveaAboutDesc1").' <button type="submit" >'.$langs->trans("InoveaAboutDesc2").'</button> '.$langs->trans("InoveaAboutDesc3").'</p></td>'."\n";
print '</tr>'."\n";

print '</table>'."\n";
// print '</div>';

llxFooter();

$db->close();
