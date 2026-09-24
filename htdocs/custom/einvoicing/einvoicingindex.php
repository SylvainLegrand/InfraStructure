<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025		SuperAdmin					<daoud.mouhamed@gmail.com>
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
 *	\file       einvoicing/einvoicingindex.php
 *	\ingroup    einvoicing
 *	\brief      Home page of einvoicing top menu
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
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
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
 * The main.inc.php has been included so the following variable are now defined:
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
'@phan-var-force User $user';
/** @var User $user */
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("einvoicing@einvoicing"));

// Load required classes
include_once __DIR__ . '/class/providers/PDPProviderManager.class.php';
include_once __DIR__ . '/class/providers/AbstractPDPProvider.class.php';
include_once __DIR__ . '/class/EInvoicingDashboard.class.php'; // InfraS add

$action = GETPOST('action', 'aZ09');

$now = dol_now();
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Security check - Protection if external user
$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
//$hookmanager->initHooks(array($object->element.'index'));

// Security check (enable the most restrictive one)
if (!isModEnabled('einvoicing')) {
	accessforbidden('Module not enabled');
}
if (!$user->hasRight('einvoicing', 'read')) {
	accessforbidden();
}


/*
 * Actions
 */

// InfraS change begin
// Reactivate the e-invoicing follow-up of an invoice listed in the abandoned invoices
if ($action == 'reactivate_einv' && $user->hasRight('facture', 'creer') && GETPOST('token', 'alpha') === currentToken()) {
	$reid	= GETPOSTINT('reid');
	if ($reid > 0) {
		$followup	= new EInvoicingFollowup($db);
		if ($followup->reactivate('facture', $reid, $user) > 0) {
			setEventMessages($langs->trans('EInvAbandonReactivated'), null, 'mesgs');
		} else {
			setEventMessages('', $followup->errors, 'errors');
		}
	}
	header('Location: ' . $_SERVER['PHP_SELF']);
	exit;
}
// InfraS change end


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("EInvoiceManagement"), '', '', 0, 0, '', '', '', 'mod-einvoicing page-index');

print load_fiche_titre($langs->trans("EInvoiceManagement"), '', 'einvoicing.png@einvoicing');

print '<div class="fichecenter">';



// Check if connected to a PA (Access Point)
$PDPManager = new PDPProviderManager($db);
$pa_connected = false;
$pa_name = '';

if (getDolGlobalString('EINVOICING_PDP')) {
	$provider = $PDPManager->getProvider(getDolGlobalString('EINVOICING_PDP'));

	if ($provider instanceof AbstractPDPProvider) {
		$pa_name = $provider->name;

		$tokenData = $provider->getTokenData();
		// Check if there is a token or credentials configured
		if ($tokenData['token']) {
			$pa_connected = true;
		}
	}
}

// Display PA connection status
if ($pa_connected) {
	print '<div class="green greenborder nomargintop">';
	print '<td colspan="2" class="center">' . $langs->trans("YourSoftwareSeemsConnectedWith", strtoupper($pa_name)) . '</td>';
	print '</div>';
	print '<br>';
} else {
	print '<div class="warning nomargintop">';
	print '<td colspan="2" class="center">' . $langs->trans("YourSoftwareDoesNotSeemsConnectedWith") . '</td>';
	print '</div>';
	print '<br>';
}



// Dashboard - Synchronization statistics
print '<div class="fichehalfleft">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">'.$langs->trans("SynchronizationDashboard").'</th>';
print '</tr>';

// Get last synchronization date
$sql_last_sync = "SELECT MAX(date_creation) as last_sync FROM " . $db->prefix() . "einvoicing_document";
$resql_last_sync = $db->query($sql_last_sync);
$last_sync_date = '';
if ($resql_last_sync && $db->num_rows($resql_last_sync) > 0) {
	$obj_last_sync = $db->fetch_object($resql_last_sync);
	if (!empty($obj_last_sync->last_sync)) {
		$last_sync_date = dol_print_date($db->jdate($obj_last_sync->last_sync), 'dayhour');
	}
	$db->free($resql_last_sync);
}

// Get count of customer invoices
$sql_customer = "SELECT COUNT(*) as nb_customer FROM " . $db->prefix() . "einvoicing_document WHERE fk_element_type = 'facture' and flow_type = 'CustomerInvoice'";
$resql_customer = $db->query($sql_customer);
$nb_customer = 0;
if ($resql_customer && $db->num_rows($resql_customer) > 0) {
	$obj_customer = $db->fetch_object($resql_customer);
	$nb_customer = $obj_customer->nb_customer;
	$db->free($resql_customer);
}

// Get count of supplier invoices
$sql_supplier = "SELECT COUNT(*) as nb_supplier FROM " . $db->prefix() . "einvoicing_document WHERE fk_element_type = 'invoice_supplier' and flow_type = 'SupplierInvoice'";
$resql_supplier = $db->query($sql_supplier);
$nb_supplier = 0;
if ($resql_supplier && $db->num_rows($resql_supplier) > 0) {
	$obj_supplier = $db->fetch_object($resql_supplier);
	$nb_supplier = $obj_supplier->nb_supplier;
	$db->free($resql_supplier);
}

// Display dashboard
print '<tr class="oddeven">';
print '<td>'.$langs->trans("LastSynchronizationDate").'</td>';
print '<td class="right">' . ($last_sync_date ?: $langs->trans("None")) . '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("CustomerInvoicesSynchronized").'</td>';
print '<td class="right"><span class="badge badge-info">' . $nb_customer . '</span></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("SupplierInvoicesSynchronized").'</td>';
print '<td class="right"><span class="badge badge-info">' . $nb_supplier . '</span></td>';
print '</tr>';

print '</table>';
print '</div>';


print '</div>';

print '<div class="fichehalfright">';



print '</div><div class="clearboth"></div>';

print '<div class="fichethirdleft">';


/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('einvoicing') && $user->hasRight('einvoicing', 'read')) {
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".$db->prefix()."commande as c";
	$sql.= ", ".$db->prefix()."societe as s";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftMyObjects").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$myobjectstatic->id=$obj->rowid;
				$myobjectstatic->ref=$obj->ref;
				$myobjectstatic->ref_client=$obj->ref_client;
				$myobjectstatic->total_ht = $obj->total_ht;
				$myobjectstatic->total_tva = $obj->total_tva;
				$myobjectstatic->total_ttc = $obj->total_ttc;

				print $myobjectstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */


print '</div><div class="fichetwothirdright">';


/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('einvoicing') && $user->hasRight('einvoicing', 'read')) {
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms";
	$sql.= " FROM ".$db->prefix()."einvoicing_myobject as s";
	$sql.= " WHERE s.entity IN (".getEntity($myobjectstatic->element).")";
	//if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->label=$objp->label;
				$myobjectstatic->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}
*/

print '</div></div>';

// InfraS add begin
/*
 * Dashboard: the content of the email notifications and reports, one collapsible section per subject.
 */
$dashboard	= new EInvoicingDashboard($db);
$einvoicing	= new EInvoicing($db);
$followup	= new EInvoicingFollowup($db);
$cur		= getDolGlobalString('MAIN_MONNAIE');

print '<style>	.einv-acc{border:1px solid #ccc;border-radius:5px;margin:6px 0;}
				.einv-acc>summary{cursor:pointer;padding:10px 12px;font-weight:bold;}
				.einv-acc .einv-body{padding:6px 12px 12px 12px;overflow-x:auto;}
				.einv-acc .einv-body h4{margin:10px 0 4px 0;}
		</style>';

$badge	= function ($n, $warn = false) {
			return '<span class="badge marginleftonlyshort '.($n == 0 ? 'badge-secondary' : ($warn ? 'badge-danger' : 'badge-info')).'">'.((int) $n).'</span>';
		};
$none	= '<div class="opacitymedium">'.$langs->trans('EInvoicingDashNone').'</div>';
$th		= function ($key) use ($langs) {
			return '<th>'.$langs->trans($key).'</th>';
		};
$resultLabel = function ($v) use ($langs) {
	$v = strtoupper(trim((string) $v));
	if ($v === 'OK') {
		return '<span class="badge badge-status4">'.$langs->trans('EInvoicingOutboundResultOk').'</span>';
	}
	if ($v === 'ERROR') {
		return '<span class="badge badge-status8">'.$langs->trans('EInvoicingOutboundResultError').'</span>';
	}
	return '<span class="badge badge-status1">'.$langs->trans('EInvoicingOutboundResultPending').'</span>';
};
$invoiceLink	= function ($id, $ref, $supplier = false) {
		$url	= DOL_URL_ROOT.($supplier ? '/fourn/facture/card.php?id=' : '/compta/facture/card.php?facid=').((int) $id);
		return '<a href="'.$url.'">'.dol_escape_htmltag($ref).'</a>';
	};

// Period filters of the received and sent sections
$recvStartTs	= dol_mktime(0, 0, 0, GETPOSTINT('recvstartmonth'), GETPOSTINT('recvstartday'), GETPOSTINT('recvstartyear'));
$recvEndTs		= dol_mktime(0, 0, 0, GETPOSTINT('recvendmonth'), GETPOSTINT('recvendday'), GETPOSTINT('recvendyear'));
$sentStartTs	= dol_mktime(0, 0, 0, GETPOSTINT('sentstartmonth'), GETPOSTINT('sentstartday'), GETPOSTINT('sentstartyear'));
$sentEndTs		= dol_mktime(0, 0, 0, GETPOSTINT('sentendmonth'), GETPOSTINT('sentendday'), GETPOSTINT('sentendyear'));
$recvCustom		= ($recvStartTs && $recvEndTs);
$sentCustom		= ($sentStartTs && $sentEndTs);
$periodForm		= function ($prefix, $startTs, $endTs) use ($form, $langs) {
	$html	= '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" class="marginbottomonly">';
	$html	.= $langs->trans('DateStart').' '.$form->selectDate($startTs ?: -1, $prefix.'start', 0, 0, 1, '', 1, 0).' ';
	$html	.= $langs->trans('DateEnd').' '.$form->selectDate($endTs ?: -1, $prefix.'end', 0, 0, 1, '', 1, 0).' ';
	$html	.= '<input type="submit" class="button smallpaddingimp" value="'.dol_escape_htmltag($langs->trans('EInvoicingDashApplyPeriod')).'">';
	if ($startTs || $endTs) {
		$html .= ' <a class="button smallpaddingimp" href="'.$_SERVER['PHP_SELF'].'">'.$langs->trans('EInvoicingDashLast7Days').'</a>';
	}
	return $html.'</form>';
};
print '<div class="fichecenter"><br>';
print '<span class="opacitymedium">'.$langs->trans('EInvoicingDashIntro').'</span><br><br>';

// Received supplier invoices
$received	= ($recvCustom ? $dashboard->receivedInvoices(7, $recvStartTs, $recvEndTs) : $dashboard->receivedInvoices(7));
print '<details class="einv-acc"'.($recvCustom ? ' open' : '').'><summary>'.$langs->trans('EInvoicingDashReceived').$badge(count($received)).'</summary><div class="einv-body">';
print $periodForm('recv', $recvStartTs, $recvEndTs);
print '<div class="opacitymedium small">'.($recvCustom ? $langs->trans('EInvoicingDashPeriodLabel', dol_print_date($recvStartTs, 'day'), dol_print_date($recvEndTs, 'day')) : $langs->trans('EInvoicingDashLast7Days')).'</div>';
if (empty($received)) {
	print $none;
} else {
	print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('Bill').$th('RefSupplier').$th('ThirdParty').$th('EInvoicingColStatus').$th('AmountHT').$th('AmountTTC').$th('Date').'</tr>';
	foreach ($received as $r) {
		$estatus = '';
		if (!empty($r->lc_code)) {
			$estatus = trim($r->lc_code.' '.$einvoicing->getStatusLabel($r->lc_code, 'invoice_supplier'));
		} elseif (!empty($r->ack_status)) {
			$estatus = (string) $r->ack_status;
		}
		print '<tr class="oddeven">
					<td>'.$invoiceLink($r->id, $r->ref, true).'</td>
					<td>'.dol_escape_htmltag($r->ref_supplier).'</td>
					<td>'.dol_escape_htmltag($r->socname).'</td>
					<td>'.dol_escape_htmltag($estatus !== '' ? $estatus : '-').'</td>
					<td class="right">'.price($r->total_ht, 0, $langs, 1, -1, -1, $cur).'</td>
					<td class="right">'.price($r->total_ttc, 0, $langs, 1, -1, -1, $cur).'</td>
					<td>'.dol_print_date($db->jdate($r->dc), 'dayhour').'</td>
				</tr>';
	}
	print '</table>';
}
print '</div></details>';

// Customer invoices sent
$sentstatus	= ($sentCustom ? $dashboard->sentInvoicesStatus(7, $sentStartTs, $sentEndTs) : $dashboard->sentInvoicesStatus(7));
$nbsent		= count($sentstatus['watch']) + count($sentstatus['intransit']) + count($sentstatus['confirmed']);
print '<details class="einv-acc"'.($sentCustom ? ' open' : '').'><summary>'.$langs->trans('EInvoicingDashSent').$badge($nbsent, !empty($sentstatus['watch'])).'</summary><div class="einv-body">';
print $periodForm('sent', $sentStartTs, $sentEndTs);
print '<div class="opacitymedium small">'.$langs->trans('EInvoicingDashSentPeriodNote').' '.($sentCustom ? $langs->trans('EInvoicingDashPeriodLabel', dol_print_date($sentStartTs, 'day'), dol_print_date($sentEndTs, 'day')) : $langs->trans('EInvoicingDashLast7Days')).'</div>';
$renderSent	= function ($list) use ($langs, $einvoicing, $sentstatus, $cur, $db, $th, $invoiceLink) {
	print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('Bill').$th('ThirdParty').$th('Date').$th('EInvoicingColStatus').$th('EInvoicingColAck').$th('EInvoicingColLifecycle').$th('EInvoicingColReason').$th('AmountTTC').'</tr>';
	foreach ($list as $r) {
		$doc		= $sentstatus['detail'][(int) $r->element_id] ?? null;
		$ack		= ($doc && trim((string) $doc->ack_status) !== '') ? trim((string) $doc->ack_status) : '-';
		$lifecycle	= ($doc ? trim(trim((string) $doc->cdar_lifecycle_code).' '.trim((string) $doc->cdar_lifecycle_label)) : '');
		$reason		= ($doc && trim((string) $doc->cdar_reason_code) !== '') ? trim((string) $doc->cdar_reason_code) : '-';
		print '<tr class="oddeven">
					<td>'.$invoiceLink($r->element_id, $r->ref).'</td>
					<td>'.dol_escape_htmltag($r->socname).'</td>
					<td>'.(!empty($r->datef) ? dol_print_date($db->jdate($r->datef), 'day') : '-').'</td>
					<td>'.dol_escape_htmltag($einvoicing->getStatusLabel($r->syncstatus, 'facture')).'</td>
					<td>'.dol_escape_htmltag($ack).'</td>
					<td>'.dol_escape_htmltag($lifecycle !== '' ? $lifecycle : '-').'</td>
					<td'.($reason !== '-' ? ' class="error"' : '').'>'.dol_escape_htmltag($reason).'</td>
					<td class="right">'.price($r->total_ttc, 0, $langs, 1, -1, -1, $cur).'</td>
				</tr>';
	}
	print '</table>';
};
if ($nbsent == 0) {
	print $none;
} else {
	if (!empty($sentstatus['watch'])) {
		print '<h4 class="error">'.$langs->trans('EInvoicingReportPendingTitle', count($sentstatus['watch'])).'</h4>';
		$renderSent($sentstatus['watch']);
	}
	if (!empty($sentstatus['intransit'])) {
		print '<h4>'.$langs->trans('EInvoicingDashSentInTransitTitle', count($sentstatus['intransit'])).'</h4>';
		$renderSent($sentstatus['intransit']);
	}
	if (!empty($sentstatus['confirmed'])) {
		print '<h4>'.$langs->trans('EInvoicingReportConfirmedTitle', count($sentstatus['confirmed'])).'</h4>';
		$renderSent($sentstatus['confirmed']);
	}
}
print '</div></details>';

// Anomalies
$anomalies = $dashboard->anomalies();
print '<details class="einv-acc"><summary>'.$langs->trans('EInvoicingDashAnomalies').$badge(count($anomalies), true).'</summary><div class="einv-body">';
if (empty($anomalies)) {
	print $none;
} else {
	print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('Document').$th('EInvoicingDashSens').$th('ThirdParty').$th('EInvoicingColStatus').$th('EInvoicingColReason').$th('AmountTTC').'</tr>';
	foreach ($anomalies as $r) {
		$lc	= trim((string) $r->cdar_lifecycle_code);
		if ($lc !== '') {
			$statuslabel	= $lc.' '.(trim((string) $r->cdar_lifecycle_label) !== '' ? trim((string) $r->cdar_lifecycle_label) : $einvoicing->getStatusLabel($lc, $r->etype));
		} else {
			$statuslabel	= $langs->trans('EInvStatusError');
		}
		$reason	= $followup->buildReadableReason($r->ack_status, $r->ack_reason_code, $r->ack_info, $r->cdar_reason_code, $r->cdar_reason_desc, $r->cdar_reason_detail);
		print '<tr class="oddeven">
					<td>'.$invoiceLink($r->id, $r->ref, $r->etype !== 'facture').'</td>
					<td>'.$langs->trans($r->etype === 'facture' ? 'EInvoicingDashIssued' : 'EInvoicingDashReceivedShort').'</td>
					<td>'.dol_escape_htmltag($r->socname).'</td>
					<td class="error">'.dol_escape_htmltag($statuslabel).'</td>
					<td>'.($reason !== '' ? dol_escape_htmltag($reason) : '-').'</td>
					<td class="right">'.price($r->total_ttc, 0, $langs, 1, -1, -1, $cur).'</td>
				</tr>';
	}
	print '</table>';
}
print '</div></details>';

// Abandoned customer invoices
$dismissed		= $dashboard->dismissedInvoices();
$canReactivate	= $user->hasRight('facture', 'creer');
print '<details class="einv-acc"><summary>'.$langs->trans('EInvoicingDashDismissed').$badge(count($dismissed)).'</summary><div class="einv-body">';
if (empty($dismissed)) {
	print $none;
} else {
	print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('Bill').$th('ThirdParty').$th('EInvAbandonColComment').$th('Date').$th('AmountTTC').($canReactivate ? '<th></th>' : '').'</tr>';
	foreach ($dismissed as $r) {
		print '<tr class="oddeven">
					<td>'.$invoiceLink($r->element_id, $r->ref).'</td>
					<td>'.dol_escape_htmltag($r->socname).'</td>
					<td>'.dol_escape_htmltag((string) $r->comment).'</td>
					<td>'.dol_print_date($db->jdate($r->date_creation), 'dayhour').'</td>
					<td class="right">'.price($r->total_ttc, 0, $langs, 1, -1, -1, $cur).'</td>';
		if ($canReactivate) {
			print '<td class="center"><a class="button smallpaddingimp" href="'.$_SERVER['PHP_SELF'].'?action=reactivate_einv&reid='.((int) $r->element_id).'&token='.newToken().'">'.$langs->trans('EInvAbandonReactivate').'</a></td>';
		}
		print '</tr>';
	}
	print '</table>';
}
print '</div></details>';

// Transmitted to the platform in the last 24 hours
$out	= $dashboard->outbound(24);
$nbout	= count($out['cashins']) + count($out['others']) + count($out['sent']);
print '<details class="einv-acc"><summary>'.$langs->trans('EInvoicingDashOutbound').$badge($nbout).'</summary><div class="einv-body">';
if ($nbout == 0) {
	print $none;
} else {
	if (!empty($out['cashins'])) {
		print '<h4>'.$langs->trans('EInvoicingOutboundCashinTitle', count($out['cashins'])).'</h4>';
		print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('Bill').$th('ThirdParty').$th('EInvoicingOutboundColAmount').$th('EInvoicingOutboundColResult').$th('Date').'</tr>';
		foreach ($out['cashins'] as $r) {
			$id = (int) $r->element_id;
			print '<tr class="oddeven">
						<td>'.$invoiceLink($id, isset($out['refFact'][$id]) ? $out['refFact'][$id]->ref : '#'.$id).'</td>
						<td>'.dol_escape_htmltag(isset($out['refFact'][$id]) ? $out['refFact'][$id]->socname : '').'</td>
						<td class="right">'.($r->cashed !== null ? price($r->cashed, 0, $langs, 1, -1, -1, $cur) : '-').'</td>
						<td>'.$resultLabel($r->lc_validation_status).'</td>
						<td>'.dol_print_date($db->jdate($r->date_creation), 'dayhour').'</td>
					</tr>';
		}
		print '</table>';
	}
	if (!empty($out['others'])) {
		print '<h4>'.$langs->trans('EInvoicingOutboundStatusesTitle', count($out['others'])).'</h4>';
		print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('Document').$th('EInvoicingColStatus').$th('EInvoicingOutboundColResult').$th('EInvoicingColReason').$th('Date').'</tr>';
		foreach ($out['others'] as $r) {
			$id	= (int) $r->element_id;
			if ($r->element_type === 'facture') {
				$link	= $invoiceLink($id, isset($out['refFact'][$id]) ? $out['refFact'][$id]->ref : '#'.$id);
			} elseif ($r->element_type === 'invoice_supplier') {
				$link	= $invoiceLink($id, isset($out['refFourn'][$id]) ? $out['refFourn'][$id]->ref : '#'.$id, true);
			} else {
				$link	= dol_escape_htmltag($r->element_type.' #'.$id);
			}
			$reason	= trim((string) $r->lc_reason_code);
			print '<tr class="oddeven"><td>'.$link.'</td>
						<td>'.((int) $r->lc_status).' '.dol_escape_htmltag($einvoicing->getStatusLabel($r->lc_status, $r->element_type)).'</td>
						<td>'.$resultLabel($r->lc_validation_status).'</td>
						<td>'.($reason !== '' ? dol_escape_htmltag($reason) : '-').'</td>
						<td>'.dol_print_date($db->jdate($r->date_creation), 'dayhour').'</td>
					</tr>';
		}
		print '</table>';
	}
	if (!empty($out['sent'])) {
		print '<h4>'.$langs->trans('EInvoicingOutboundSentTitle', count($out['sent'])).'</h4>';
		print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('Bill').$th('ThirdParty').$th('AmountHT').$th('AmountTTC').$th('Date').'</tr>';
		foreach ($out['sent'] as $r) {
			print '<tr class="oddeven"><td>'.$invoiceLink($r->id, $r->ref).'</td>
						<td>'.dol_escape_htmltag($r->socname).'</td>
						<td class="right">'.price($r->total_ht, 0, $langs, 1, -1, -1, $cur).'</td>
						<td class="right">'.price($r->total_ttc, 0, $langs, 1, -1, -1, $cur).'</td>
						<td>'.dol_print_date($db->jdate($r->dc), 'dayhour').'</td>
					</tr>';
		}
		print '</table>';
	}
}
print '</div></details>';

// Suppliers without SIREN
$nosiren = $dashboard->suppliersWithoutSiren();
print '<details class="einv-acc"><summary>'.$langs->trans('EInvoicingDashNoSiren').$badge(count($nosiren), true).'</summary><div class="einv-body">';
if (empty($nosiren)) {
	print $none;
} else {
	print '<table class="noborder centpercent"><tr class="liste_titre">'.$th('ThirdParty').$th('EInvoicingColSiret').$th('VATIntra').'</tr>';
	foreach ($nosiren as $r) {
		print '<tr class="oddeven"><td><a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.((int) $r->rowid).'">'.dol_escape_htmltag($r->nom).'</a></td>';
		print '<td>'.(trim((string) $r->siret) !== '' ? dol_escape_htmltag($r->siret) : '<span class="error">-</span>').'</td>';
		print '<td>'.(trim((string) $r->tva_intra) !== '' ? dol_escape_htmltag($r->tva_intra) : '<span class="error">-</span>').'</td></tr>';
	}
	print '</table>';
}
print '</div></details>';

print '</div>';
// InfraS add end

// End of page
llxFooter();
$db->close();
