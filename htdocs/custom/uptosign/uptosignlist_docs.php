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
 *		\brief      List page for docs of uptosignlist
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("MAIN_SECURITY_FORCECSP"))   define('MAIN_SECURITY_FORCECSP', 'none');	// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification
//if (! defined('NOSESSION'))                define('NOSESSION', '1');					// On CLI mode, no need to use web sessions

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

// load module libraries
dol_include_once('/uptosign/class/uptosignlist.class.php');
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/lib/backports.lib.php');
dol_include_once('/uptosign/lib/uptosign_uptosignlist.lib.php');

// for other modules
//dol_include_once('/othermodule/class/otherobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("uptosign@uptosign", "other"));

$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'create'/'add', 'edit'/'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$show_files = GETPOSTINT('show_files'); // Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$id 		= GETPOSTINT('id');
$ref 		= GETPOST('ref', 'alpha');
$objectType = GETPOST('objectType', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new UptoSignList($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->uptosign->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array($contextpage)); 	// Note that conf->hooks_modules contains array of activated contexes

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	reset($object->fields);					// Reset is required to avoid key() to return null.
	$sortfield = "t.".key($object->fields); // Set here default search field. By default 1st field in definition.
}
if (!$sortorder) {
	$sortorder = "ASC";
}

// Initialize array of search criterias
$search_all = GETPOST('search_all', 'alphanohtml');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha') !== '') {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
	if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
		$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, (int) GETPOST('search_'.$key.'_dtstartmonth', 'int'), (int) GETPOST('search_'.$key.'_dtstartday', 'int'), (int) GETPOST('search_'.$key.'_dtstartyear', 'int'));
		$search[$key.'_dtend'] = dol_mktime(23, 59, 59, (int) GETPOST('search_'.$key.'_dtendmonth', 'int'), (int) GETPOST('search_'.$key.'_dtendday', 'int'), (int) GETPOST('search_'.$key.'_dtendyear', 'int'));
	}
}
// Definition of array of fields for columns
$arrayfields = array();

//ajout manuel
// if($uts->path_file_signed) {
// 	$key = "signedFile";
// 	$arrayfields['t.'.$key] = array(
// 		'label'=>$langs->trans("signedFile"),
// 		'checked'=>1,
// 		'enabled'=>1,
// 		'position'=>250,
// 	);
// }

foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1);
		$arrayfields['t.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>(abs($visible) != 3 && dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=> isset($val['help']) ? $val['help'] : ''
		);
	}
}

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('uptosign', 'uptosignlist', 'read');
	$permissiontoadd = $user->hasRight('uptosign', 'uptosignlist', 'write');
	$permissiontodelete = $user->hasRight('uptosign', 'uptosignlist', 'delete');
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1;
	$permissiontodelete = 1;
}

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) accessforbidden();
//$socid = 0; if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, 0, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("uptosign")) {
	accessforbidden('Module uptosign not enabled');
}
if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	foreach ($object->fields as $key => $val) {
		$search[$key] = '';
		if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
			$search[$key.'_dtstart'] = '';
			$search[$key.'_dtend'] = '';
		}
	}
	$toselect = array();
	$search_array_options = array();
}
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
	|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
	$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
}

// Mass actions
$objectclass = 'UptoSignList';
$objectlabel = 'UptoSignList';
$uploaddir = $conf->uptosign->dir_output;
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';


if ($massaction == "uptosign_fetch") {
	$signOrSeal = "uptosign";
	//sync all doc in list
	foreach ($toselect as $upid) {
		$uptosign = new UptoSign($db);
		if ($res = $uptosign->fetch($upid)) {
			//confirm_uptosignfetch
			$res = $uptosign->signFetch($user, $uptosign, $signOrSeal);
		}
	}
}


/*
 * View
 */

 $form = new Form($db);
 $formfile = new FormFile($db);

$now = dol_now();

$help_url = "https://doc.cap-rel.fr/projet_uptosign/faire_signer_le_meme_document_independamment_par_plusieurs_personnes";
$help_btn = "<a href='$help_url' target='_blank'><i class='fa-solid fa-circle-info'>" . $langs->trans("Help") . "</i></a>";
$title = $langs->trans("UptoSignListsPage") . " " . $help_btn;
$morejs = array();
$morecss = array();


$object->fetch($id);
$uts = new UptoSign($db);
$list = $uts->fetchWhereLike('label', $object->ref);
$nbtotalofrecords = count($list);
$num = $nbtotalofrecords;

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

$arrayofselected = is_array($toselect) ? $toselect : array();
$head = uptosignlistPrepareHead($object);
$modulepart = "uptosign";

print dol_get_fiche_head($head, 'uptosignlisttabdocs', $langs->trans("UptoSign"), -1, $object->picto);

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
foreach ($search as $key => $val) {
	if (is_array($search[$key])) {
		foreach ($search[$key] as $skey) {
			if ($skey != '') {
				$param .= '&search_'.$key.'[]='.urlencode($skey);
			}
		}
	} elseif (preg_match('/(_dtstart|_dtend)$/', $key) && !empty($val)) {
		$param .= '&search_'.$key.'month='.((int) GETPOST('search_'.$key.'month', 'int'));
		$param .= '&search_'.$key.'day='.((int) GETPOST('search_'.$key.'day', 'int'));
		$param .= '&search_'.$key.'year='.((int) GETPOST('search_'.$key.'year', 'int'));
	} elseif ($search[$key] != '') {
		$param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;
// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	'uptosign_fetch'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("UptoSignSync"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions, 1, 'massaction', 'checkforselect minwidth500');

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="id" value="'.$id.'">';
print '<input type="hidden" name="objectType" value="'.$objectType.'">';

$newcardbutton = '';
$moreforfilter = '';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', -1, 0, 0, 1);

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';
print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, utsbackports_getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')) : ''); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
foreach ($uts->fields as $key => $val) {
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('id', 'rowid', 'ref', 'status')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
		$cssforfield .= ($cssforfield ? ' ' : '').'right';
	}
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		print '<td class="liste_titre'.($cssforfield ? ' '.$cssforfield : '').($key == 'status' ? ' parentonrightofpage' : '').'">';
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			print $form->selectarray('search_'.$key, $val['arrayofkeyval'], '', $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100'.($key == 'status' ? ' search_status width100 onrightofpage' : ''), 1);
		} elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
			// print $uts->showInputField($val, $key, '', '', '', 'search_', $cssforfield.' maxwidth250', 1);
		} elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
			// print '<div class="nowrap">';
			// print $form->selectDate('', "search_".$key."_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
			// print '</div>';
			// print '<div class="nowrap">';
			// print $form->selectDate('', "search_".$key."_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
			// print '</div>';
		} elseif ($key == 'lang') {
			// require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
			// $formadmin = new FormAdmin($db);
			// print $formadmin->select_language($search[$key], 'search_lang', 0, null, 1, 0, 0, 'minwidth150 maxwidth200', 2);
		} else {
			// print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '').'">';
		}
		print '</td>';
	}
}
//download file
print '<td class="liste_titre">&nbsp;</td>';


// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
foreach ($uts->fields as $key => $val) {
	$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
	if ($key == 'status') {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'center';
	} elseif (in_array($val['type'], array('timestamp'))) {
		$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	} elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('id', 'rowid', 'ref', 'status')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
		$cssforfield .= ($cssforfield ? ' ' : '').'right';
	}
	$cssforfield = preg_replace('/small\s*/', '', $cssforfield);	// the 'small' css must not be used for the title label
	if (!empty($arrayfields['t.'.$key]['checked'])) {
		/** @phpstan-ignore-next-line */
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''), 0, (empty($val['helplist']) ? '' : $val['helplist']))."\n";
		$totalarray['nbfield']++;
	}
}
print '<th class="wrapcolumntitle liste_titre">&nbsp;</th>';

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
foreach ($list as $stdo) {
	$obj = new UptoSign($db);
	$obj->fetch($stdo->rowid);
	if (empty($obj)) {
		print "empty";
		break; // Should not happen
	}

	// Show line of result
	$j = 0;
	print '<tr data-rowid="'.$obj->id.'" class="oddeven">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	foreach ($obj->fields as $key => $val) {
		$cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '').'center';
		} elseif ($key == 'status') {
			$cssforfield .= ($cssforfield ? ' ' : '').'center';
		}

		if (in_array($val['type'], array('timestamp'))) {
			$cssforfield .= ($cssforfield ? ' ' : '').'nowraponall';
		} elseif ($key == 'ref') {
			$cssforfield .= ($cssforfield ? ' ' : '').'nowraponall';
		}

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('id', 'rowid', 'ref', 'status')) && empty($val['arrayofkeyval'])) {
			$cssforfield .= ($cssforfield ? ' ' : '').'right';
		}
		//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

		if (!empty($arrayfields['t.'.$key]['checked'])) {
			print '<td'.($cssforfield ? ' class="'.$cssforfield.(preg_match('/tdoverflow/', $cssforfield) ? ' classfortooltip' : '').'"' : '');
			if (preg_match('/tdoverflow/', $cssforfield) && !is_numeric($obj->$key)) {
				print ' title="'.dol_escape_htmltag($obj->$key).'"';
			}
			print '>';

			if ($key == 'status') {
				print $obj->getLibStatut(5);
			} elseif ($key == 'rowid') {
				print $obj->showOutputField($val, $key, $obj->id, '');
			} else {
				print $obj->showOutputField($val, $key, $obj->$key, '');
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.'.$key;
				}
				if (!isset($totalarray['val'])) {
					$totalarray['val'] = array();
				}
				if (!isset($totalarray['val']['t.'.$key])) {
					$totalarray['val']['t.'.$key] = 0;
				}
				$totalarray['val']['t.'.$key] += $obj->$key;
			}
		}
	}

	$listoffiles = dol_dir_list(dirname(DOL_DATA_ROOT . '/' . $obj->path_file_signed), 'files', 0, 'pdf');
	$file = reset($listoffiles);
	$filenamesubdir = str_replace("uptosign/", "", $obj->path_file_signed);
	$download = '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=uptosign&file=/'.urlencode($filenamesubdir).'">'. img_picto('', 'listlight') . " " . $file['name'] . '</a>';
	print '<td class="wrapcolumntitle liste_titre">' . $download .'</td>';


	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	print '</tr>'."\n";

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}
// $db->free($resql);

print '</table>'."\n";
print '</div>'."\n";
print '</form>'."\n";


// End of page
llxFooter();
$db->close();
