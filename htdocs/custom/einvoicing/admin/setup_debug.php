<?php
/* InfraS add : fichier ajouté par InfraS repris du module einvoicingsx (SYSAXES)
* Copyright (C) 2026		SYSAXES
* Copyright (C) 2026		InfraS					<technique@infras.fr>
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
* \file    einvoicing/admin/setup_debug.php
* \ingroup einvoicing
* \brief   Debug tab of the setup: debug mode, log file of the module (read and downloaded from the browser,
*          for an administrator with no access to the server), check of an emitted XML, dry run of the
*          import of a received XML. Nothing here writes a business record or sends anything.
*/

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
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
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once __DIR__.'/../lib/einvoicing.lib.php';

$langs->loadLangs(array("admin", "bills", "products", "einvoicing@einvoicing"));

$action		= GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (!$user->admin) {
	accessforbidden();
}

// Log file of the module: Dolibarr writes the lines logged with the '_einvoicing' suffix next to the main
// log file, when the File handler of the Syslog module is enabled
$logfile	= str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, getDolGlobalString('SYSLOG_FILE', 'DOL_DATA_ROOT/dolibarr.log'));
$logfile	= preg_replace('/\.log$/i', '_einvoicing.log', $logfile);
$debugmode	= getDolGlobalInt('EINVOICING_DEBUG_MODE');

/**
* Content of an uploaded file of the form, null when none was sent.
*
* @param	string			$field Name of the file field
* @return	string|null		Content
*/
function einvoicingDebugUploadedContent($field)
{
	if (empty($_FILES[$field]['tmp_name']) || (int) $_FILES[$field]['error'] !== 0 || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
		return null;
	}
	$content	= file_get_contents($_FILES[$field]['tmp_name']);

	return ($content === false || trim($content) === '') ? null : $content;
}


/*
 * Actions
 */

if ($action == 'set_debug') {
	dolibarr_set_const($db, 'EINVOICING_DEBUG_MODE', GETPOSTINT('EINVOICING_DEBUG_MODE'), 'chaine', 0, '', $conf->entity);
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'download_einv_log' && $debugmode) {
	if (is_readable($logfile)) {
		header('Content-Type: text/plain; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.basename($logfile).'"');
		header('Content-Length: '.filesize($logfile));
		readfile($logfile);
		exit;
	}
	setEventMessages($langs->trans('EInvDebugLogNotFound'), null, 'errors');
}

$emittedXml	= null;
if ($action == 'check_emitted_xml' && $debugmode) {
	$emittedXml = einvoicingDebugUploadedContent('emitxml');
	if ($emittedXml === null) {
		setEventMessages($langs->trans('EInvDebugEmitEmptyFile'), null, 'errors');
	}
}

$preview = null;
if ($action == 'preview_debug_xml' && $debugmode) {
	$xml	= einvoicingDebugUploadedContent('debugxml');
	if ($xml === null) {
		setEventMessages($langs->trans('EInvDebugImportXmlEmpty'), null, 'errors');
	} else {
		require_once __DIR__.'/../class/utils/EInvoicingImportPreview.class.php';
		$previewer	= new EInvoicingImportPreview($db);
		$preview	= $previewer->preview($xml);
		if ($preview['res'] <= 0) {
			setEventMessages($langs->trans('EInvDebugPreviewFailed').' - '.$preview['error'], null, 'errors');
			$preview = null;
		}
	}
}


/*
 * View
 */

$form	= new Form($db);
$title	= "EInvoicingSetup";

llxHeader('', $langs->trans($title), '', '', 0, 0, '', '', '', 'mod-einvoicing page-admin-debug');

$linkback	= '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head	= einvoicingAdminPrepareHead();
print dol_get_fiche_head($head, 'debug', $langs->trans($title), -1, "einvoicing.png@einvoicing");

print '<span class="opacitymedium">'.$langs->trans("EINVOICING_DEBUG_MODE_HELP").'</span><br><br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">
		<input type="hidden" name="token" value="'.newToken().'">
		<input type="hidden" name="action" value="set_debug">
		<table class="noborder centpercent">
			<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td class="right">'.$langs->trans("Value").'</td></tr>
			<tr class="oddeven"><td>'.$langs->trans("EINVOICING_DEBUG_MODE").'</td><td class="right">'.$form->selectyesno('EINVOICING_DEBUG_MODE', $debugmode, 1).'</td></tr>
		</table>
		<div class="center margintoponly"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>
		</form>';

if ($debugmode) {
	// Log file of the module
	print '<br>';
	print load_fiche_titre($langs->trans('EInvDebugLogTitle'), '', 'bug');
	if (is_readable($logfile) && filesize($logfile) > 0) {
		// Only the end of the file is read: it can be large
		$fh		= fopen($logfile, 'rb');
		$size	= filesize($logfile);
		if ($size > 400000) {
			fseek($fh, $size - 400000);
			fgets($fh);
		}
		$tail		= (string) stream_get_contents($fh);
		fclose($fh);
		$lastlines	= array_slice(array_filter(preg_split('/\r?\n/', $tail), 'strlen'), -150);

		print '<div class="opacitymedium small">'.$langs->trans('EInvDebugLogFile').' : <span class="wordbreak">'.dol_escape_htmltag($logfile).'</span>';
		print ' - '.dol_print_size($size, 1, 1).' - '.$langs->trans('DateLastModification').' : '.dol_print_date(filemtime($logfile), 'dayhoursec').'</div>';
		print '<div class="margintoponly marginbottomonly">';
		print '<a class="button small" href="'.$_SERVER['PHP_SELF'].'?action=download_einv_log&token='.newToken().'">'.img_picto('', 'download', 'class="paddingright"').$langs->trans('EInvDebugLogDownload').'</a> ';
		print '<a class="button small" href="'.$_SERVER['PHP_SELF'].'">'.img_picto('', 'refresh', 'class="paddingright"').$langs->trans('Refresh').'</a>';
		print '</div>';
		print '<pre style="max-height:400px;overflow:auto;border:1px solid #ddd;padding:10px;border-radius:4px;font-size:12px;">'.dol_escape_htmltag(implode("\n", $lastlines)).'</pre>';
	} else {
		print '<div class="warning">'.$langs->trans('EInvDebugLogEmpty').'<br><span class="opacitymedium small">'.dol_escape_htmltag($logfile).'</span></div>';
		print '<span class="opacitymedium small">'.$langs->trans('EInvDebugLogEmptyHint').'</span>';
	}

	// Check of an emitted XML
	print '<br><br>';
	print load_fiche_titre($langs->trans('EInvDebugEmitTitle'), '', 'bill');
	print '<span class="opacitymedium">'.$langs->trans('EInvDebugEmitHelp').'</span><br><br>';
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="check_emitted_xml">';
	print '<input type="file" name="emitxml" accept=".xml,text/xml,application/xml">';
	print ' <input type="submit" class="button small" value="'.dol_escape_htmltag($langs->trans('EInvDebugEmitRun')).'">';
	print '</form>';
	if ($emittedXml !== null) {
		// Empty elements are refused by the platforms (PEPPOL-EN16931-R008)
		$emptyTags	= array();
		$reg		= array();
		if (preg_match_all('#<([A-Za-z0-9:]+)(\s[^<>]*?)?/>#', $emittedXml, $reg)) {
			foreach ($reg[1] as $tag) {
				$emptyTags[$tag] = ($emptyTags[$tag] ?? 0) + 1;
			}
		}
		if (preg_match_all('#<([A-Za-z0-9:]+)(\s[^<>]*?)?>\s*</\1>#', $emittedXml, $reg)) {
			foreach ($reg[1] as $tag) {
				$emptyTags[$tag] = ($emptyTags[$tag] ?? 0) + 1;
			}
		}
		print '<br>';
		if (!empty($emptyTags)) {
			$parts = array();
			foreach ($emptyTags as $tag => $nb) {
				$parts[] = dol_escape_htmltag($tag).' ('.((int) $nb).')';
			}
			print '<div class="warning">'.$langs->trans('EInvDebugEmitEmptyFound').' : '.implode(', ', $parts).'</div>';
		} else {
			print '<div class="ok">'.$langs->trans('EInvDebugEmitNoEmpty').'</div>';
		}
		print '<pre style="max-height:520px;overflow:auto;border:1px solid #ddd;padding:10px;border-radius:4px;font-size:12px;">'.dol_escape_htmltag($emittedXml).'</pre>';
	}

	// Dry run of the import of a received XML
	print '<br><br>';
	print load_fiche_titre($langs->trans('EInvDebugSimTitle'), '', 'supplier_invoice');
	print '<span class="opacitymedium">'.$langs->trans('EInvDebugSimHelp').'</span><br><br>';
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="preview_debug_xml">';
	print '<input type="file" name="debugxml" accept=".xml,text/xml,application/xml">';
	print ' <input type="submit" class="button small" value="'.dol_escape_htmltag($langs->trans('EInvDebugSimRun')).'">';
	print '</form>';

	if (is_array($preview)) {
		$sup	= $preview['supplier'];
		$hdr	= $preview['header'];
		print '<br><div class="info">'.$langs->trans('EInvDebugSimNoWrite').'</div>';

		print '<div class="fichecenter"><div class="fichehalfleft"><table class="border centpercent tableforfield">';
		print '<tr><td class="titlefield">'.$langs->trans('Ref').'</td><td>'.dol_escape_htmltag((string) ($hdr['documentno'] ?? '')).'</td></tr>';
		print '<tr><td>'.$langs->trans('Currency').'</td><td>'.dol_escape_htmltag((string) ($hdr['invoiceCurrency'] ?? '')).'</td></tr>';
		print '<tr><td>'.$langs->trans('RefOrder').'</td><td>'.dol_escape_htmltag((string) ($hdr['orderReference'] ?? '')).'</td></tr>';
		print '</table></div><div class="fichehalfright"><table class="border centpercent tableforfield">';
		print '<tr><td class="titlefield">'.$langs->trans('Supplier').'</td><td>'.dol_escape_htmltag($sup['name']).'</td></tr>';
		print '<tr><td>'.$langs->trans('ProfId1FR').'</td><td>'.dol_escape_htmltag($sup['siren'] !== '' ? $sup['siren'] : '-').'</td></tr>';
		print '<tr><td>'.$langs->trans('VATIntra').'</td><td>'.dol_escape_htmltag($sup['vat'] !== '' ? $sup['vat'] : '-').'</td></tr>';
		print '<tr><td>'.$langs->trans('EInvDebugSupplierLink').'</td><td>';
		if ($sup['matchId'] > 0) {
			print '<span class="badge badge-status4 badge-status">'.$langs->trans('EInvDebugSupplierMatched').'</span> ';
			print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.((int) $sup['matchId']).'">'.dol_escape_htmltag($sup['matchName']).'</a>';
		} else {
			print '<span class="badge badge-status1 badge-status">'.$langs->trans('EInvDebugSupplierWouldCreate').'</span>';
		}
		print '</td></tr></table></div></div><div class="clearboth"></div><br>';

		$matchLabels = array(
			'existing'			=> array('badge-status4', 'EInvDebugMatchExisting'),
			'defaultrouting'	=> array('badge-status4', 'EInvDebugMatchDefaultRouting'),
			'new'				=> array('badge-status1', 'EInvDebugMatchNew'),
			'free'				=> array('badge-status0', 'EInvDebugMatchFree'),
			'manual'			=> array('badge-status8', 'EInvDebugMatchManual'),
		);
		print '<div class="div-table-responsive"><table class="noborder centpercent"><tr class="liste_titre">';
		foreach (array('Line', 'SupplierRef', 'Description', 'EInvDebugProductMatch', 'Qty', 'Unit', 'PriceUHT', 'ReductionShort', 'VAT', 'TotalHT') as $key) {
			print '<th>'.$langs->trans($key).'</th>';
		}
		print '</tr>';
		foreach ($preview['lines'] as $l) {
			$m = $l['match'];
			print '<tr class="oddeven"><td>'.dol_escape_htmltag($l['lineid']).'</td>';
			print '<td>'.dol_escape_htmltag($l['ref_supplier'] !== '' ? $l['ref_supplier'] : '-').'</td>';
			print '<td><strong>'.dol_escape_htmltag($l['name']).'</strong>';
			if ($l['desc'] !== '') {
				print '<br><span class="opacitymedium small">'.dol_escape_htmltag(dol_trunc($l['desc'], 160)).'</span>';
			}
			if ($l['warning'] !== '') {
				print '<br><span class="warning small">'.dol_escape_htmltag($l['warning']).'</span>';
			}
			print '</td><td class="small"><span class="badge badge-status '.$matchLabels[$m['status']][0].'">'.$langs->trans($matchLabels[$m['status']][1]).'</span>';
			if ($m['id'] > 0) {
				print ' <a href="'.DOL_URL_ROOT.'/product/card.php?id='.((int) $m['id']).'">'.dol_escape_htmltag($m['ref']).'</a>';
				print ' <span class="opacitymedium">('.$langs->trans($m['type'] == 1 ? 'Service' : 'Product').')</span>';
			}
			print '</td><td class="right">'.price($l['qty']).'</td>';
			print '<td class="center">'.dol_escape_htmltag($l['unit']).'</td>';
			print '<td class="right">'.price($l['subprice'], 0, $langs, 1, -1, 'MU').'</td>';
			print '<td class="right">'.($l['remise_percent'] ? price($l['remise_percent']).' %' : '-').'</td>';
			print '<td class="right">'.price($l['tva_tx']).' %</td>';
			print '<td class="right">'.price($l['total_ht'], 0, $langs, 1, -1, 2).'</td></tr>';
		}
		print '</table></div>';

		// Totals of the lines against the totals the document announces
		$tot	= $preview['totals'];
		$xtot	= $preview['xmlTotals'];
		$fmt	= function ($v) use ($langs) {
			return ($v === null ? '-' : price($v, 0, $langs, 1, -1, 2));
		};
		print '<br><div class="fichecenter"><div class="fichehalfright"><table class="noborder centpercent">';
		print '<tr class="liste_titre"><td></td><td class="right">'.$langs->trans('EInvDebugComputed').'</td><td class="right">'.$langs->trans('EInvDebugXmlDeclared').'</td></tr>';
		foreach (array('TotalHT' => 'ht', 'TotalVAT' => 'tva', 'TotalTTC' => 'ttc') as $label => $key) {
			$differs = ($xtot[$key] !== null && abs($tot[$key] - $xtot[$key]) >= 0.01);
			print '<tr class="oddeven"><td>'.$langs->trans($label).'</td><td class="right">'.$fmt($tot[$key]).'</td><td class="right'.($differs ? ' error' : '').'">'.$fmt($xtot[$key]).'</td></tr>';
		}
		if ($xtot['due'] !== null) {
			print '<tr class="oddeven"><td>'.$langs->trans('AmountExpected').'</td><td class="right">-</td><td class="right">'.$fmt($xtot['due']).'</td></tr>';
		}
		print '</table><span class="opacitymedium small">'.$langs->trans('EInvDebugTotalsHint').'</span></div></div><div class="clearboth"></div>';

		if ($preview['notePublic'] !== '') {
			print '<br><strong>'.$langs->trans('NotePublic').'</strong>';
			print '<div class="wordbreak" style="white-space:pre-wrap;border:1px solid #ddd;padding:8px;border-radius:4px;margin-top:4px;">'.dol_escape_htmltag($preview['notePublic']).'</div>';
		}
	}
}

print dol_get_fiche_end();

llxFooter();
$db->close();
