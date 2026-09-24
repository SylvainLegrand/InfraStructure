<?php
/* InfraS add : fichier ajouté par InfraS, repris du module einvoicingsx (SYSAXES)
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
* \file    einvoicing/class/utils/EInvoicingReports.class.php
* \ingroup einvoicing
* \brief   Scheduled email reports of the module (cron jobs declared in modEInvoicing), sent to
*          EINVOICING_SYNC_NOTIFY_EMAILS. Their content is the one of the dashboard of the home page.
*/

require_once __DIR__ . '/../einvoicing.class.php';
require_once __DIR__ . '/../EInvoicingDashboard.class.php';
require_once __DIR__ . '/EInvoicingNotifier.class.php';


/**
 * Scheduled email reports of the e-invoicing module.
 */
class EInvoicingReports
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error of the last job
	 */
	public $error = '';

	/**
	 * @var string Output of the last job, shown in the list of scheduled jobs
	 */
	public $output = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Cron job, weekly: list the French suppliers that have no SIREN, invites to complete them.
	 *
	 * @return int 0 if OK, >0 if KO
	 */
	public function cronCheckSuppliersWithoutSiren()
	{
		global $langs;

		$langs->loadLangs(array('main', 'companies', 'einvoicing@einvoicing'));
		$notifier = new EInvoicingNotifier($this->db);
		if (!$this->hasRecipients($notifier)) {
			return 0;
		}

		$dashboard	= new EInvoicingDashboard($this->db);
		$rows		= $dashboard->suppliersWithoutSiren();
		if (empty($rows)) {
			$this->output = $langs->transnoentitiesnoconv('EInvoicingSuppliersNoSirenNone');
			return 0;
		}

		$th = '<th style="border:1px solid #cccccc;text-align:left;">';
		$td = '<td style="border:1px solid #cccccc;">';
		$missing = '<span style="color:#cc0000;">-</span>';
		$body = '<p>' . $langs->trans('EInvoicingSuppliersNoSirenIntro', count($rows)) . '</p>';
		$body .= '<p>' . $langs->trans('EInvoicingSuppliersNoSirenReason') . '</p>';
		$body .= '<p style="color:#555555;font-style:italic;">' . $langs->trans('EInvoicingSuppliersNoSirenScope') . '</p>';
		$body .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #cccccc;">';
		$body .= '<tr style="background-color:#f0f0f0;">' . $th . $langs->trans('ThirdParty') . '</th>' . $th . $langs->trans('EInvoicingColSiret') . '</th>';
		$body .= $th . $langs->trans('VATIntra') . '</th><th style="border:1px solid #cccccc;"></th></tr>';
		foreach ($rows as $r) {
			$siret = trim((string) $r->siret);
			$vat = trim((string) $r->tva_intra);
			$body .= '<tr>' . $td . dol_escape_htmltag($r->nom) . '</td>';
			$body .= $td . ($siret !== '' ? dol_escape_htmltag($siret) : $missing) . '</td>';
			$body .= $td . ($vat !== '' ? dol_escape_htmltag($vat) : $missing) . '</td>';
			$body .= $td . '<a href="' . DOL_MAIN_URL_ROOT . '/societe/card.php?socid=' . ((int) $r->rowid) . '">' . $langs->trans('EInvoicingOpenThirdparty') . '</a></td></tr>';
		}
		$body .= '</table>';
		$body .= '<p><b>' . $langs->trans('EInvoicingSuppliersNoSirenCallToAction') . '</b></p>';

		$subject = $notifier->buildSubject($langs->transnoentities('EInvoicingSuppliersNoSirenSubject', count($rows)));

		return $this->sendReport($notifier, $subject, $body, $langs->transnoentitiesnoconv('EInvoicingSuppliersNoSirenSent', count($rows), implode(', ', $notifier->getRecipients())));
	}

	/**
	* Cron job, daily: status of the customer invoices transmitted to the platform, highlighting the ones
	* whose reception is not confirmed yet (no age limit), with those confirmed in the last 7 days.
	*
	* @return int 0 if OK, >0 if KO
	*/
	public function cronReportSentInvoices()
	{
		global $langs;

		$langs->loadLangs(array('main', 'bills', 'companies', 'einvoicing@einvoicing'));
		$notifier	= new EInvoicingNotifier($this->db);
		if (!$this->hasRecipients($notifier)) {
			return 0;
		}

		$dashboard	= new EInvoicingDashboard($this->db);
		$status		= $dashboard->sentInvoicesStatus(7);
		$pending	= array_merge($status['watch'], $status['intransit']);
		$confirmed	= $status['confirmed'];
		if (empty($pending) && empty($confirmed)) {
			$this->output = $langs->transnoentitiesnoconv('EInvoicingReportNothing');
			return 0;
		}

		$einvoicing	= new EInvoicing($this->db);
		$detail		= $status['detail'];
		$cur		= getDolGlobalString('MAIN_MONNAIE');
		$renderRows	= function ($list) use ($langs, $einvoicing, $detail, $cur) {
			$html	= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #cccccc;"><tr style="background-color:#f0f0f0;">';
			foreach (array('Bill', 'ThirdParty', 'EInvoicingColStatus', 'EInvoicingColAck', 'EInvoicingColLifecycle', 'EInvoicingColReason', 'AmountHT', 'AmountTTC') as $h) {
				$html .= '<th style="border:1px solid #cccccc;text-align:left;">' . $langs->trans($h) . '</th>';
			}
			$html .= '<th style="border:1px solid #cccccc;"></th></tr>';
			foreach ($list as $r) {
				$doc		= $detail[(int) $r->element_id] ?? null;
				$ack		= ($doc && trim((string) $doc->ack_status) !== '') ? trim((string) $doc->ack_status) : '-';
				$lifecycle	= ($doc ? trim(trim((string) $doc->cdar_lifecycle_code) . ' ' . trim((string) $doc->cdar_lifecycle_label)) : '');
				$reason		= ($doc && trim((string) $doc->cdar_reason_code) !== '') ? trim((string) $doc->cdar_reason_code) : '-';
				$html .= '<tr><td style="border:1px solid #cccccc;">' . dol_escape_htmltag($r->ref) . '</td>';
				$html .= '<td style="border:1px solid #cccccc;">' . dol_escape_htmltag($r->socname) . '</td>';
				$html .= '<td style="border:1px solid #cccccc;">' . dol_escape_htmltag($einvoicing->getStatusLabel($r->syncstatus, 'facture')) . '</td>';
				$html .= '<td style="border:1px solid #cccccc;">' . dol_escape_htmltag($ack) . '</td>';
				$html .= '<td style="border:1px solid #cccccc;">' . dol_escape_htmltag($lifecycle !== '' ? $lifecycle : '-') . '</td>';
				$html .= '<td style="border:1px solid #cccccc;' . ($reason !== '-' ? 'color:#cc0000;font-weight:bold;' : '') . '">' . dol_escape_htmltag($reason) . '</td>';
				$html .= '<td style="border:1px solid #cccccc;text-align:right;">' . price($r->total_ht, 0, $langs, 1, -1, -1, $cur) . '</td>';
				$html .= '<td style="border:1px solid #cccccc;text-align:right;">' . price($r->total_ttc, 0, $langs, 1, -1, -1, $cur) . '</td>';
				$html .= '<td style="border:1px solid #cccccc;"><a href="' . DOL_MAIN_URL_ROOT . '/compta/facture/card.php?facid=' . ((int) $r->element_id) . '">' . $langs->trans('EInvoicingOpenInvoice') . '</a></td></tr>';
			}
			return $html . '</table>';
		};

		$body = '<p>' . $langs->trans('EInvoicingReportIntro') . '</p>';
		if (!empty($pending)) {
			$body .= '<h3 style="color:#cc0000;">' . $langs->trans('EInvoicingReportPendingTitle', count($pending)) . '</h3>';
			$body .= '<p>' . $langs->trans('EInvoicingReportPendingHelp') . '</p>';
			$body .= $renderRows($pending);
		} else {
			$body .= '<p><b>' . $langs->trans('EInvoicingReportNoPending') . '</b></p>';
		}
		if (!empty($confirmed)) {
			$body .= '<h3>' . $langs->trans('EInvoicingReportConfirmedTitle', count($confirmed)) . '</h3>';
			$body .= $renderRows($confirmed);
		}

		$subject = $notifier->buildSubject($langs->transnoentities('EInvoicingReportSubject', count($pending)));

		return $this->sendReport($notifier, $subject, $body, $langs->transnoentitiesnoconv('EInvoicingReportSent', count($pending), count($confirmed), implode(', ', $notifier->getRecipients())));
	}

	/**
	* Cron job, daily: what the module transmitted to the platform in the last 24 hours - cash-in statuses
	* (212), other lifecycle statuses, and customer invoices sent.
	*
	* @return int 0 if OK, >0 if KO
	*/
	public function cronReportOutboundToPlatform()
	{
		global $langs;

		$langs->loadLangs(array('main', 'bills', 'companies', 'einvoicing@einvoicing'));
		$notifier = new EInvoicingNotifier($this->db);
		if (!$this->hasRecipients($notifier)) {
			return 0;
		}

		$dashboard	= new EInvoicingDashboard($this->db);
		$out		= $dashboard->outbound(24);
		if (empty($out['sent']) && empty($out['cashins']) && empty($out['others'])) {
			$this->output = $langs->transnoentitiesnoconv('EInvoicingOutboundNothing');
			return 0;
		}

		$einvoicing	= new EInvoicing($this->db);
		$cur		= getDolGlobalString('MAIN_MONNAIE');
		$urlroot	= DOL_MAIN_URL_ROOT;
		$th			= function ($keys) use ($langs) {
			$html	= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #cccccc;"><tr style="background-color:#f0f0f0;">';
			foreach ($keys as $h) {
				$html .= '<th style="border:1px solid #cccccc;text-align:left;">' . $langs->trans($h) . '</th>';
			}
			return $html . '</tr>';
		};
		$td			= '<td style="border:1px solid #cccccc;">';
		$resultLabel = function ($v) use ($langs) {
			$v	= strtoupper(trim((string) $v));
			if ($v === 'OK') {
				return '<span style="color:#178a3a;">' . $langs->trans('EInvoicingOutboundResultOk') . '</span>';
			}
			if ($v === 'ERROR') {
				return '<span style="color:#cc0000;font-weight:bold;">' . $langs->trans('EInvoicingOutboundResultError') . '</span>';
			}
			return '<span style="color:#a06a00;">' . $langs->trans('EInvoicingOutboundResultPending') . '</span>';
		};

		$body = '<p>' . $langs->trans('EInvoicingOutboundIntro') . '</p>';
		if (!empty($out['cashins'])) {
			$body .= '<h3 style="color:#0b3d63;">' . $langs->trans('EInvoicingOutboundCashinTitle', count($out['cashins'])) . '</h3>';
			$body .= '<p>' . $langs->trans('EInvoicingOutboundCashinHelp') . '</p>';
			$body .= $th(array('Bill', 'ThirdParty', 'EInvoicingOutboundColAmount', 'EInvoicingOutboundColResult', 'Date'));
			foreach ($out['cashins'] as $r) {
				$id		= (int) $r->element_id;
				$ref	= isset($out['refFact'][$id]) ? $out['refFact'][$id]->ref : '#' . $id;
				$soc	= isset($out['refFact'][$id]) ? $out['refFact'][$id]->socname : '';
				$body .= '<tr>' . $td . '<a href="' . $urlroot . '/compta/facture/card.php?facid=' . $id . '">' . dol_escape_htmltag($ref) . '</a></td>';
				$body .= $td . dol_escape_htmltag($soc) . '</td>';
				$body .= '<td style="border:1px solid #cccccc;text-align:right;">' . ($r->cashed !== null ? price($r->cashed, 0, $langs, 1, -1, -1, $cur) : '-') . '</td>';
				$body .= $td . $resultLabel($r->lc_validation_status) . '</td>';
				$body .= $td . dol_print_date($this->db->jdate($r->date_creation), 'dayhour') . '</td></tr>';
			}
			$body .= '</table>';
		}
		if (!empty($out['others'])) {
			$body .= '<h3 style="color:#0b3d63;">' . $langs->trans('EInvoicingOutboundStatusesTitle', count($out['others'])) . '</h3>';
			$body .= $th(array('Document', 'EInvoicingColStatus', 'EInvoicingOutboundColResult', 'EInvoicingColReason', 'Date'));
			foreach ($out['others'] as $r) {
				$id		= (int) $r->element_id;
				$url	= '';
				if ($r->element_type === 'facture') {
					$ref = isset($out['refFact'][$id]) ? $out['refFact'][$id]->ref : '#' . $id;
					$url = $urlroot . '/compta/facture/card.php?facid=' . $id;
				} elseif ($r->element_type === 'invoice_supplier') {
					$ref = isset($out['refFourn'][$id]) ? $out['refFourn'][$id]->ref : '#' . $id;
					$url = $urlroot . '/fourn/facture/card.php?id=' . $id;
				} else {
					$ref = $r->element_type . ' #' . $id;
				}
				$reason = trim((string) $r->lc_reason_code);
				$body .= '<tr>' . $td . ($url !== '' ? '<a href="' . $url . '">' . dol_escape_htmltag($ref) . '</a>' : dol_escape_htmltag($ref)) . '</td>';
				$body .= $td . ((int) $r->lc_status) . ' ' . dol_escape_htmltag($einvoicing->getStatusLabel($r->lc_status, $r->element_type)) . '</td>';
				$body .= $td . $resultLabel($r->lc_validation_status) . '</td>';
				$body .= $td . ($reason !== '' ? dol_escape_htmltag($reason) : '-') . '</td>';
				$body .= $td . dol_print_date($this->db->jdate($r->date_creation), 'dayhour') . '</td></tr>';
			}
			$body .= '</table>';
		}
		if (!empty($out['sent'])) {
			$body .= '<h3 style="color:#0b3d63;">' . $langs->trans('EInvoicingOutboundSentTitle', count($out['sent'])) . '</h3>';
			$body .= $th(array('Bill', 'ThirdParty', 'AmountHT', 'AmountTTC', 'Date'));
			foreach ($out['sent'] as $r) {
				$body .= '<tr>' . $td . '<a href="' . $urlroot . '/compta/facture/card.php?facid=' . ((int) $r->id) . '">' . dol_escape_htmltag($r->ref) . '</a></td>';
				$body .= $td . dol_escape_htmltag($r->socname) . '</td>';
				$body .= '<td style="border:1px solid #cccccc;text-align:right;">' . price($r->total_ht, 0, $langs, 1, -1, -1, $cur) . '</td>';
				$body .= '<td style="border:1px solid #cccccc;text-align:right;">' . price($r->total_ttc, 0, $langs, 1, -1, -1, $cur) . '</td>';
				$body .= $td . dol_print_date($this->db->jdate($r->dc), 'dayhour') . '</td></tr>';
			}
			$body .= '</table>';
		}

		$subject = $notifier->buildSubject($langs->transnoentities('EInvoicingOutboundSubject'));

		return $this->sendReport($notifier, $subject, $body, $langs->transnoentitiesnoconv('EInvoicingOutboundSent', count($out['cashins']), count($out['others']), count($out['sent']), implode(', ', $notifier->getRecipients())));
	}

	/**
	* Say so in the job output when no recipient is configured.
	*
	* @param	EInvoicingNotifier	$notifier	Notifier
	* @return	bool							True when at least one recipient is configured
	*/
	private function hasRecipients($notifier)
	{
		global $langs;

		if (empty($notifier->getRecipients())) {
			$this->output = $langs->transnoentitiesnoconv('EInvoicingNoNotifyRecipient');
			return false;
		}

		return true;
	}

	/**
	* Send a report and set the output of the job.
	*
	* @param	EInvoicingNotifier	$notifier	Notifier
	* @param	string				$subject	Subject
	* @param	string				$body		HTML body
	* @param	string				$sentOutput	Output of the job when the report is sent
	* @return	int								0 if OK, 1 if KO
	*/
	private function sendReport($notifier, $subject, $body, $sentOutput)
	{
		if ($notifier->send($subject, $body) > 0) {
			$this->output = $sentOutput;
			return 0;
		}
		$this->error	= $notifier->error;
		$this->output	= $notifier->error;
		return 1;
	}
}
