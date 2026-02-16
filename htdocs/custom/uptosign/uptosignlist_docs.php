<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2025 Eric Seigne <eric.seigne@cap-rel.fr>
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
 *   	\file       uptosignlist_docs.php
 *		\ingroup    uptosign
 *		\brief      Synthetic tracking page for uptosignlist signing procedures
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

// load module libraries
dol_include_once('/uptosign/class/uptosignlist.class.php');
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/lib/backports.lib.php');
dol_include_once('/uptosign/lib/uptosign_uptosignlist.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("uptosign@uptosign", "other"));

$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$massaction = GETPOST('massaction', 'alpha');
$confirm    = GETPOST('confirm', 'alpha');
$toselect   = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php'));

$id = GETPOSTINT('id');
$objectType = GETPOST('objectType', 'alpha');

// Initialize technical objects
$object = new UptoSignList($db);
$hookmanager->initHooks(array($contextpage));

// Permissions
$permissiontoread = $user->hasRight('uptosign', 'uptosignlist', 'read');
$permissiontodelete = $user->hasRight('uptosign', 'uptosignlist', 'delete');

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!isModEnabled("uptosign")) {
	accessforbidden('Module uptosign not enabled');
}
if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

if ($massaction == "uptosign_fetch") {
	foreach ($toselect as $upid) {
		$uptosign = new UptoSign($db);
		if ($uptosign->fetch($upid) > 0) {
			$uptosign->signFetch($user, $uptosign, "uptosign");
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$object->fetch($id);

$help_url = "https://doc.cap-rel.fr/projet_uptosign/faire_signer_le_meme_document_independamment_par_plusieurs_personnes";
$title = $langs->trans("UptoSignListsPage");

llxHeader('', $title, $help_url);

$head = uptosignlistPrepareHead($object);
print dol_get_fiche_head($head, 'uptosignlisttabdocs', $langs->trans("UptoSign"), -1, $object->picto);

// Fetch members with their linked UptoSign procedures
$membersWithProcs = $object->getContactsWithProcedures();

// Compute summary counters
$nbTotal = count($membersWithProcs);
$nbWaiting = 0;
$nbSigned = 0;
$nbError = 0;
$nbNotSent = 0;
$nbFetched = 0;

foreach ($membersWithProcs as $row) {
	if (empty($row->uptosign_id)) {
		$nbNotSent++;
	} elseif ($row->uptosign_status == UptoSign::STATUS_WAITING) {
		$nbWaiting++;
	} elseif ($row->uptosign_status == UptoSign::STATUS_SIGNED || $row->uptosign_status == UptoSign::STATUS_SEALED) {
		$nbSigned++;
	} elseif ($row->uptosign_status == UptoSign::STATUS_FILE_FETCHED) {
		$nbFetched++;
	} elseif ($row->uptosign_status < 0) {
		$nbError++;
	}
}

// Summary bar
print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield">'.$langs->trans("TotalNbOfDistinctRecipients").'</td>';
print '<td><strong>'.$nbTotal.'</strong>';
if ($nbTotal > 0) {
	$parts = array();
	if ($nbNotSent > 0) {
		$parts[] = '<span class="badge badge-status0 badge-status">'.$nbNotSent.' '.$langs->trans("MailingStatusNotSent").'</span>';
	}
	if ($nbWaiting > 0) {
		$parts[] = '<span class="badge badge-status1 badge-status">'.$nbWaiting.' '.$langs->trans("WaitingCommonSign").'</span>';
	}
	if ($nbSigned > 0) {
		$parts[] = '<span class="badge badge-status4 badge-status">'.$nbSigned.' '.$langs->trans("UptoSignSigned").'</span>';
	}
	if ($nbFetched > 0) {
		$parts[] = '<span class="badge badge-status6 badge-status">'.$nbFetched.' '.$langs->trans("UptoSignFetchedSign").'</span>';
	}
	if ($nbError > 0) {
		$parts[] = '<span class="badge badge-status8 badge-status">'.$nbError.' '.$langs->trans("UptoSignError").'</span>';
	}
	if (!empty($parts)) {
		print ' &nbsp; '.implode(' &nbsp; ', $parts);
	}
}
print '</td></tr>';
print '</table>';
print '</div>';
print '<br>';

// Mass action form
$arrayofmassactions = array(
	'uptosign_fetch' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("UptoSignSync"),
);
$massactionbutton = $form->selectMassAction('', $arrayofmassactions, 1, 'massaction', 'checkforselect minwidth500');

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="id" value="'.$id.'">';
print '<input type="hidden" name="objectType" value="'.$objectType.'">';

print_barre_liste(
	$langs->trans("UptoSignListTabResult"),
	0,
	$_SERVER["PHP_SELF"],
	'&id='.$id,
	'',
	'',
	$massactionbutton,
	$nbTotal,
	$nbTotal,
	'object_'.$object->picto,
	0,
	'',
	'',
	-1,
	0,
	0,
	1
);

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

// Source object cache to avoid repeated fetches
$objectstaticcontact = new Contact($db);
$objectstaticuser = new User($db);
$objectstaticcompany = new Societe($db);

// UptoSign object for status rendering
$utsStatic = new UptoSign($db);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">'."\n";

// Header row
print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<th class="liste_titre center maxwidthsearch">'.$form->showCheckAddButtons('checkforselect', 1).'</th>';
}
print '<th class="liste_titre">'.$langs->trans("Lastname").'</th>';
print '<th class="liste_titre">'.$langs->trans("Firstname").'</th>';
print '<th class="liste_titre">'.$langs->trans("EMail").'</th>';
print '<th class="liste_titre center">'.$langs->trans("Source").'</th>';
print '<th class="liste_titre">'.$langs->trans("Ref").'</th>';
print '<th class="liste_titre center">'.$langs->trans("Status").'</th>';
print '<th class="liste_titre center">'.$langs->trans("DateSign").'</th>';
print '<th class="liste_titre">'.$langs->trans("Document").'</th>';
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<th class="liste_titre center maxwidthsearch">'.$form->showCheckAddButtons('checkforselect', 1).'</th>';
}
print '</tr>'."\n";

// Data rows
if ($nbTotal > 0) {
	foreach ($membersWithProcs as $row) {
		print '<tr class="oddeven">';

		// Checkbox left
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if (!empty($row->uptosign_id) && ($massactionbutton || $massaction)) {
				$selected = in_array($row->uptosign_id, is_array($toselect) ? $toselect : array()) ? ' checked="checked"' : '';
				print '<input id="cb'.$row->uptosign_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$row->uptosign_id.'"'.$selected.'>';
			}
			print '</td>';
		}

		// Lastname
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($row->lastname).'">'.dol_escape_htmltag($row->lastname).'</td>';

		// Firstname
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($row->firstname).'">'.dol_escape_htmltag($row->firstname).'</td>';

		// Email
		print '<td class="tdoverflowmax200">';
		print img_picto('', 'email', 'class="pictofixedwidth"');
		print dol_escape_htmltag($row->email);
		print '</td>';

		// Source
		print '<td class="center tdoverflowmax150">';
		if (!empty($row->source_id) && !empty($row->source_type)) {
			if ($row->source_type == 'contact') {
				$objectstaticcontact->fetch($row->source_id);
				print $objectstaticcontact->getNomUrl(1);
			} elseif ($row->source_type == 'user') {
				$objectstaticuser->fetch($row->source_id);
				print $objectstaticuser->getNomUrl(1);
			} elseif ($row->source_type == 'thirdparty') {
				$objectstaticcompany->fetch($row->source_id);
				print $objectstaticcompany->getNomUrl(1);
			} else {
				print dol_escape_htmltag($row->source_type);
			}
		}
		print '</td>';

		// UptoSign ref
		print '<td class="nowraponall">';
		if (!empty($row->uptosign_id)) {
			$utsStatic->id = $row->uptosign_id;
			$utsStatic->ref = $row->uptosign_ref;
			$utsStatic->status = $row->uptosign_status;
			print $utsStatic->getNomUrl(1);
		} else {
			print '<span class="opacitymedium">'.$langs->trans("MailingStatusNotSent").'</span>';
		}
		print '</td>';

		// Status
		print '<td class="center nowrap">';
		if (!empty($row->uptosign_id)) {
			$utsStatic->status = $row->uptosign_status;
			print $utsStatic->getLibStatut(5);
		}
		print '</td>';

		// Date sign
		print '<td class="center nowraponall">';
		if (!empty($row->date_sign)) {
			print dol_print_date($db->jdate($row->date_sign), 'dayhour');
		}
		print '</td>';

		// Signed document download
		print '<td>';
		if (!empty($row->path_file_signed)) {
			$filenamesubdir = str_replace("uptosign/", "", $row->path_file_signed);
			$filename = basename($row->path_file_signed);
			print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=uptosign&file=/'.urlencode($filenamesubdir).'">';
			print img_picto('', 'pdf', 'class="pictofixedwidth"');
			print dol_escape_htmltag($filename);
			print '</a>';
		}
		print '</td>';

		// Checkbox right
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if (!empty($row->uptosign_id) && ($massactionbutton || $massaction)) {
				$selected = in_array($row->uptosign_id, is_array($toselect) ? $toselect : array()) ? ' checked="checked"' : '';
				print '<input id="cb'.$row->uptosign_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$row->uptosign_id.'"'.$selected.'>';
			}
			print '</td>';
		}

		print '</tr>'."\n";
	}
} else {
	print '<tr><td colspan="9"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

print '</table>'."\n";
print '</div>'."\n";
print '</form>'."\n";

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
