<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Éric Seigne <eric.seigne@cap-rel.fr>
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
 * \file    scaninvoices/admin/setup.php
 * \ingroup scaninvoices
 * \brief   ScanInvoices setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	--$i;
	--$j;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)).'/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php';
}
// Try main.inc.php using relative path
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	exit('Include of main fails');
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/scaninvoices.lib.php';
//require_once "../class/myclass.class.php";
require_once DOL_DOCUMENT_ROOT.'/includes/sabre/autoload.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Translations
$langs->loadLangs(['admin', 'scaninvoices@scaninvoices']);
$mesg = ''; $mesg_type = '';
// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'scaninvoices';

$error = 0;
$setupnotempty = 0;

/*
 * Actions
 */

if ((float) DOL_VERSION >= 6) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}

if ($action == 'set') {
	$array = ['SCANINVOICES_DISABLE_IMPORT_LINES', 'SCANINVOICES_IMPORT_CREATE_PRODUCT','SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPE'];
	foreach ($array as $key) {
		if (GETPOST($key) != '') {
			$value = GETPOST($key, 'alpha');
			dolibarr_set_const($db, $key, $value, 'chaine', 0, '', $conf->entity);
			$conf->global->$key = $value;
		}
	}
}

//default value
if (!isset($conf->global->SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPE)) {
	$conf->global->SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPE = Product::TYPE_SERVICE;
	dolibarr_set_const($db, 'SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPE', (string) Product::TYPE_SERVICE, 'chaine', 0, '', $conf->entity);
}

$defaultCREATEPRODUCTchecked = '';
if (scaninvoicesgetDolGlobalString('SCANINVOICES_IMPORT_CREATE_PRODUCT', '') != '' && getDolGlobalString('SCANINVOICES_IMPORT_CREATE_PRODUCT') == 1) {
	$defaultCREATEPRODUCTchecked = ' checked';
}

$defaultOVERRIDELABELPRODUCTchecked = '';
if (scaninvoicesgetDolGlobalString('SCANINVOICES_IMPORT_OVERRIDE_LABEL_PRODUCT', '') != '' && getDolGlobalString('SCANINVOICES_IMPORT_OVERRIDE_LABEL_PRODUCT') == 1) {
	$defaultOVERRIDELABELPRODUCTchecked = ' checked';
}
if ($action == 'set') {
	$array = ['SCANINVOICES_IMPORT_OVERRIDE_LABEL_PRODUCT'];
	foreach ($array as $key) {
		$value = GETPOST($key, 'alpha');
		dolibarr_set_const($db, $key, $value, 'chaine', 0, '', $conf->entity);
	}
}

/*
 * View
 */

$form = new Form($db);

$page_name = 'ScanInvoicesSetupImportLines';
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans('BackToModuleList').'</a>';

echo load_fiche_titre($langs->trans($page_name), $linkback, 'object_scaninvoices@scaninvoices');

// Configuration header
$head = scaninvoicesAdminPrepareHead();
echo dol_get_fiche_head($head, 'importlines', '', -1, 'scaninvoices@scaninvoices');

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans('ScanInvoicesSetupPageLINES').'</span><br><br>';

echo '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
echo '<input type="hidden" name="token" value="'.newToken().'">';
echo '<input type="hidden" name="action" value="set">';

echo '<table class="noborder centpercent">';

echo '<tr class="oddeven"><td class=""><b>'.$langs->trans('SCANINVOICES_DISABLE_IMPORT_LINES').'</b><br /><i>'.$langs->trans('SCANINVOICES_DISABLE_IMPORT_LINESTooltip').'</i></td>';
echo '<td>';
echo '<input type="checkbox" name="SCANINVOICES_DISABLE_IMPORT_LINES" value="1" class="minwidth300" onchange="formChange();"'.(getDolGlobalInt('SCANINVOICES_DISABLE_IMPORT_LINES') ? 'checked="checked"' : '').'>';
echo '</td>';
echo '</tr>';


echo '<tr class="oddeven"><td class=""><b>'.$langs->trans('SCANINVOICES_IMPORT_CREATE_PRODUCT').'</b><br /><i>'.$langs->trans('SCANINVOICES_IMPORT_CREATE_PRODUCTTooltip').'</i></td>';
echo '<td>';
echo '<input type="checkbox" name="SCANINVOICES_IMPORT_CREATE_PRODUCT" value="1" class="minwidth300"'.$defaultCREATEPRODUCTchecked.' onchange="formChange();"'.(getDolGlobalInt('SCANINVOICES_IMPORT_CREATE_PRODUCT') ? 'checked="checked"' : '').'>';
echo '</td>';
echo '</tr>';

echo '<tr class="oddeven"><td class=""><b>'.$langs->trans('SCANINVOICES_IMPORT_OVERRIDE_LABEL_PRODUCT').'</b><br /><i>'.$langs->trans('SCANINVOICES_IMPORT_OVERRIDE_LABEL_PRODUCTTooltip').'</i></td>';
echo '<td>';
echo '<input type="checkbox" name="SCANINVOICES_IMPORT_OVERRIDE_LABEL_PRODUCT" value="1" class="minwidth300"'.$defaultOVERRIDELABELPRODUCTchecked.' onchange="formChange();"'.(getDolGlobalInt('SCANINVOICES_IMPORT_OVERRIDE_LABEL_PRODUCT') ? 'checked="checked"' : '').'>';
echo '</td>';
echo '</tr>';

$typepattern = array(
	Product::TYPE_PRODUCT => 'Produit',
	Product::TYPE_SERVICE => 'Service'
);
echo '<tr class="oddeven"><td class=""><b>'.$langs->trans('SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPE').'</b><br /><i>'.$langs->trans('SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPETooltip').'</i></td>';
echo '<td>';
print $form->selectArray("SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPE", $typepattern, $conf->global->SCANINVOICES_IMPORT_CREATE_PRODUCT_TYPE, 0, 0, 0, '', 0, 0, 0, '', 'width150');
echo '</td>';
echo '</tr>';

echo '</table>';

echo '<br><div class="right">';

echo '<input id="saveBtn" class="button button-save" type="submit" value="'.$langs->trans('Save').'">';

echo '<a id="checkConnectBtn" class="button button-checkconnect" href="'.$_SERVER['PHP_SELF'].'?action=checkConnectAPI">'.$langs->trans('CheckConnectToScanInvoices').'</a>';

echo '</div>';

echo '</form>';
echo '<br>';

echo '</div>';

echo "<script language='javascript'>function formChange(){ $('#checkConnectBtn').remove(); $('#saveBtn').css('visibility', 'visible'); }</script>";

$moduledir = 'scaninvoices';
$myTmpObjects = [];
$myTmpObjects['ScanInvoices'] = ['includerefgeneration' => 0, 'includedocgeneration' => 0];

foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
	if ($myTmpObjectKey == 'ScanInvoices') {
		continue;
	}
	if ($myTmpObjectArray['includerefgeneration']) {
		/*
		 * Orders Numbering model
		 */
		++$setupnotempty;

		echo load_fiche_titre($langs->trans('NumberingModules2', $myTmpObjectKey), '', '');

		echo '<table class="noborder centpercent">';
		echo '<tr class="liste_titre">';
		echo '<td>'.$langs->trans('Name').'</td>';
		echo '<td>'.$langs->trans('Description').'</td>';
		echo '<td class="nowrap">'.$langs->trans('Example').'</td>';
		echo '<td class="center" width="60">'.$langs->trans('Status').'</td>';
		echo '<td class="center" width="16">'.$langs->trans('ShortInfo').'</td>';
		echo '</tr>'."\n";

		clearstatcache();

		foreach ($dirmodels as $reldir) {
			$dir = dol_buildpath($reldir.'core/modules/'.$moduledir);

			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					while (($file = readdir($handle)) !== false) {
						if (strpos($file, 'mod_'.strtolower($myTmpObjectKey).'_') === 0 && substr($file, dol_strlen($file) - 3, 3) == 'php') {
							$file = substr($file, 0, dol_strlen($file) - 4);

							require_once $dir.'/'.$file.'.php';

							$module = new $file($db);

							// Show modules according to features level
							if ($module->version == 'development' && getDolGlobalString('MAIN_FEATURES_LEVEL') < 2) {
								continue;
							}
							if ($module->version == 'experimental' && getDolGlobalString('MAIN_FEATURES_LEVEL') < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								dol_include_once('/'.$moduledir.'/class/'.strtolower($myTmpObjectKey).'.class.php');

								echo '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
								echo $module->info();
								echo '</td>';

								// Show example of numbering model
								echo '<td class="nowrap">';
								$tmp = $module->getExample();
								if (preg_match('/^Error/', $tmp)) {
									$langs->load('errors');
									echo '<div class="error">'.$langs->trans($tmp).'</div>';
								} elseif ($tmp == 'NotConfigured') {
									echo $langs->trans($tmp);
								} else {
									echo $tmp;
								}
								echo '</td>'."\n";

								echo '<td class="center">';
								$constforvar = 'SCANINVOICES_'.strtoupper($myTmpObjectKey).'_ADDON';
								if (getDolGlobalString($constforvar) == $file) {
									echo img_picto($langs->trans('Activated'), 'switch_on');
								} else {
									echo '<a href="'.$_SERVER['PHP_SELF'].'?action=setmod&token='.newToken().'&object='.strtolower($myTmpObjectKey).'&value='.urlencode($file).'">';
									echo img_picto($langs->trans('Disabled'), 'switch_off');
									echo '</a>';
								}
								echo '</td>';

								$mytmpinstance = new $myTmpObjectKey($db);
								$mytmpinstance->initAsSpecimen();

								// Info
								$htmltooltip = '';
								$htmltooltip .= ''.$langs->trans('Version').': <b>'.$module->getVersion().'</b><br>';

								$nextval = $module->getNextValue($mytmpinstance);
								if ("$nextval" != $langs->trans('NotAvailable')) {  // Keep " on nextval
									$htmltooltip .= ''.$langs->trans('NextValue').': ';
									if ($nextval) {
										if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
											$nextval = $langs->trans($nextval);
										}
										$htmltooltip .= $nextval.'<br>';
									} else {
										$htmltooltip .= $langs->trans($module->error).'<br>';
									}
								}

								echo '<td class="center">';
								echo $form->textwithpicto('', $htmltooltip, 1, 0);
								echo '</td>';

								echo "</tr>\n";
							}
						}
					}
					closedir($handle);
				}
			}
		}
		echo "</table><br>\n";
	}
}

// Page end
echo dol_get_fiche_end();

dol_htmloutput_mesg($mesg, [], $mesg_type);

llxFooter();
$db->close();
