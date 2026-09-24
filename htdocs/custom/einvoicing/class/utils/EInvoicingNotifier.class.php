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
* \file    einvoicing/class/utils/EInvoicingNotifier.class.php
* \ingroup einvoicing
* \brief   Email notifications of the module, sent to EINVOICING_SYNC_NOTIFY_EMAILS: supplier invoices
*          received by a synchronization, flows that entered an anomaly state, and the scheduled reports.
*          The feature is off while that constant is empty. A notification never fails a synchronization.
*/

require_once __DIR__.'/../einvoicing.class.php';
require_once __DIR__.'/EInvoicingFollowup.class.php';


/**
 * Email notifications of the e-invoicing module.
 */
class EInvoicingNotifier
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Last error
	 */
	public $error = '';

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
	 * Valid addresses of EINVOICING_SYNC_NOTIFY_EMAILS (comma or semicolon separated).
	 *
	 * @return string[]
	 */
	public function getRecipients()
	{
		$recipients = array();
		foreach (preg_split('/[;,]/', getDolGlobalString('EINVOICING_SYNC_NOTIFY_EMAILS')) as $addr) {
			$addr = trim($addr);
			if ($addr !== '' && isValidEmail($addr)) {
				$recipients[] = $addr;
			}
		}

		return $recipients;
	}

	/**
	 * Sender of the notifications: the generic sender of the email setup (never the current user), or the
	 * email of the company, displayed with the company name.
	 *
	 * @return string '' when neither is set
	 */
	public function getSender()
	{
		$fromemail = getDolGlobalString('MAIN_MAIL_EMAIL_FROM', getDolGlobalString('MAIN_INFO_SOCIETE_MAIL'));
		if ($fromemail === '') {
			return '';
		}
		$fromname = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

		return ($fromname !== '' ? '"'.str_replace('"', '', $fromname).'" <'.$fromemail.'>' : $fromemail);
	}

	/**
	 * Subject of a notification: "<company> - <text> - <date>". Not HTML encoded, it is a mail header.
	 *
	 * @param  string $text Subject text
	 * @return string
	 */
	public function buildSubject($text)
	{
		$socname = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');

		return ($socname !== '' ? $socname.' - ' : '').$text.' - '.dol_print_date(dol_now(), 'dayhour');
	}

	/**
	 * Send an HTML notification to the configured recipients.
	 *
	 * @param	string	$subject	Subject
	 * @param	string	$body		HTML body
	 * @return	int					1 if sent, 0 if nothing to do (no recipient), -1 on error ($this->error)
	 */
	public function send($subject, $body)
	{
		global $langs;

		$this->error	= '';
		$recipients		= $this->getRecipients();
		if (empty($recipients)) {
			$this->error = $langs->transnoentitiesnoconv('EInvoiceNotifyEmailNoRecipient');
			return 0;
		}
		$from = $this->getSender();
		if ($from === '') {
			$this->error = $langs->transnoentitiesnoconv('EInvoiceNotifyEmailNoSender');
			dol_syslog(__METHOD__.' No sender email (MAIN_MAIL_EMAIL_FROM) configured', LOG_WARNING, 0, '_einvoicing');
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		try {
			$mailfile = new CMailFile($subject, implode(',', $recipients), $from, $body, array(), array(), array(), '', '', 0, 1);
			if ($mailfile->sendfile()) {
				dol_syslog(__METHOD__.' Notification "'.$subject.'" sent to '.implode(',', $recipients), LOG_INFO, 0, '_einvoicing');
				return 1;
			}
			$this->error = $mailfile->error;
		} catch (Exception $e) {
			$this->error = $e->getMessage();
		}
		dol_syslog(__METHOD__.' Failed to send notification "'.$subject.'": '.$this->error, LOG_ERR, 0, '_einvoicing');

		return -1;
	}

	/**
	* After a synchronization: notify the supplier invoices it imported and the receptions that failed, and
	* alert on the flows it put in an anomaly state. Both are read back from the flow documents the run
	* recorded under its call id, so any provider can call it.
	*
	* @param	string|null							$callId Call id of the synchronization run
	* @param	array<int,array<string,mixed>>		$failed Flows that failed (keys: flowId, message, actioncode, actiondata)
	* @return	void
	*/
	public function notifyAfterSync($callId, $failed = array())
	{
		if (empty($this->getRecipients())) {
			return;
		}

		$invoices = array();
		$anomalies = array();
		if (!empty($callId)) {
			$followup = new EInvoicingFollowup($this->db);

			$sql = "SELECT d.fk_element_id as id, ff.ref, ff.ref_supplier, ff.total_ttc, s.nom as socname";
			$sql .= " FROM ".$this->db->prefix()."einvoicing_document as d";
			$sql .= " INNER JOIN ".$this->db->prefix()."facture_fourn as ff ON ff.rowid = d.fk_element_id";
			$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON s.rowid = ff.fk_soc";
			$sql .= " WHERE d.call_id = '".$this->db->escape($callId)."'";
			$sql .= " AND d.fk_element_type = 'invoice_supplier' AND d.flow_type = 'SupplierInvoice'";
			$sql .= " ORDER BY d.rowid";
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$invoices[(int) $obj->id] = $obj;
				}
				$this->db->free($resql);
			}

			$sql = "SELECT d.flow_id, d.tracking_idref, d.fk_element_type, d.fk_element_id, d.ack_status, d.ack_reason_code, d.ack_info,";
			$sql .= " d.cdar_lifecycle_code, d.cdar_lifecycle_label, d.cdar_reason_code, d.cdar_reason_desc, d.cdar_reason_detail";
			$sql .= " FROM ".$this->db->prefix()."einvoicing_document as d";
			$sql .= " WHERE d.call_id = '".$this->db->escape($callId)."'";
			$sql .= " AND (d.ack_status = 'Error' OR d.cdar_lifecycle_code IN ('".implode("','", EInvoicingFollowup::ANOMALY_LIFECYCLE_CODES)."'))";
			$sql .= " ORDER BY d.rowid";
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					if ((int) $obj->fk_element_id > 0 && $followup->isDismissed($obj->fk_element_type, (int) $obj->fk_element_id)) {
						continue;
					}
					$obj->reason = $followup->buildReadableReason($obj->ack_status, $obj->ack_reason_code, $obj->ack_info, $obj->cdar_reason_code, $obj->cdar_reason_desc, $obj->cdar_reason_detail);
					$anomalies[] = $obj;
				}
				$this->db->free($resql);
			}
		}

		if (!empty($invoices) || !empty($failed)) {
			$this->sendReceivedInvoices(array_values($invoices), $failed);
		}
		if (!empty($anomalies)) {
			$this->sendAnomalyAlert($anomalies);
		}
	}

	/**
	* Test of the received-invoices notification: send the supplier invoices received in the last $days days,
	* even when there is none, to check the email delivery.
	*
	* @param  int $days Look-back window in days
	* @return array{res:int, message:string}
	*/
	public function sendTest($days = 7)
	{
		global $langs;

		require_once __DIR__.'/../EInvoicingDashboard.class.php';

		$langs->load('einvoicing@einvoicing');
		$days		= max(1, (int) $days);
		$dashboard	= new EInvoicingDashboard($this->db);
		$invoices	= $dashboard->receivedInvoices($days);
		$res		= $this->sendReceivedInvoices($invoices, array(), $days);
		if ($res > 0) {
			return array('res' => 1, 'message' => $langs->trans('EInvoiceNotifyEmailSent', $this->getSender(), implode(', ', $this->getRecipients()), count($invoices)));
		}
		return array('res' => $res, 'message' => $this->error);
	}

	/**
	* Notification of the received supplier invoices, and of the receptions that could not be imported.
	*
	* @param	array<int,object>				$invoices Received supplier invoices {id, ref, ref_supplier, total_ttc, socname}
	* @param	array<int,array<string,mixed>>	$failed   Receptions that failed (keys: flowId, message, actioncode, actiondata)
	* @param	int								$testDays Look-back window of a test email, 0 for a real notification
	* @return	int								See send()
	*/
	private function sendReceivedInvoices($invoices, $failed, $testDays = 0)
	{
		global $conf, $langs;

		$langs->loadLangs(array('main', 'bills', 'companies', 'einvoicing@einvoicing'));
		$isTest		= ($testDays > 0);
		$urlroot	= DOL_MAIN_URL_ROOT;
		$th			= '<th style="border:1px solid #cccccc;text-align:left;">';
		$td			= '<td style="border:1px solid #cccccc;">';
		$body		= '';
		if ($isTest) {
			$body .= '<p><strong>'.$langs->trans('EInvoiceNotifyEmailTestBanner').'</strong></p>';
		}
		if (!empty($invoices)) {
			$body .= '<p>'.($isTest ? $langs->trans('EInvoiceNotifyEmailTestIntro', $testDays) : $langs->trans('EInvoiceNotifyEmailIntro', count($invoices))).'</p>';
			$body .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #cccccc;">';
			$body .= '<tr style="background-color:#f0f0f0;">'.$th.$langs->trans('Supplier').'</th>'.$th.$langs->trans('Ref').'</th>';
			$body .= $th.$langs->trans('RefSupplier').'</th>'.$th.$langs->trans('AmountTTC').'</th><th style="border:1px solid #cccccc;"></th></tr>';
			foreach ($invoices as $inv) {
				$body .= '<tr>'.$td.dol_escape_htmltag((string) $inv->socname).'</td>';
				$body .= $td.dol_escape_htmltag((string) $inv->ref).'</td>';
				$body .= $td.dol_escape_htmltag((string) $inv->ref_supplier).'</td>';
				$body .= '<td style="border:1px solid #cccccc;text-align:right;">'.price((float) $inv->total_ttc, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
				$body .= $td.'<a href="'.$urlroot.'/fourn/facture/card.php?id='.((int) $inv->id).'">'.$langs->trans('EInvoiceNotifyEmailOpen').'</a></td></tr>';
			}
			$body .= '</table>';
		} elseif ($isTest) {
			$body .= '<p>'.$langs->trans('EInvoiceNotifyEmailTestNone', $testDays).'</p>';
		} elseif (!empty($failed)) {
			$body .= '<p>'.$langs->trans('EInvoiceNotifyEmailNoneButErrors').'</p>';
		}

		if (!empty($failed)) {
			$body .= '<h3 style="color:#cc0000;">'.$langs->trans('EInvoiceNotifyErrorsTitle', count($failed)).'</h3>';
			$body .= '<p>'.$langs->trans('EInvoiceNotifyErrorsHelp').'</p>';
			$body .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #cccccc;">';
			$body .= '<tr style="background-color:#f0f0f0;">'.$th.$langs->trans('EInvoiceNotifyColFlow').'</th>';
			$body .= $th.$langs->trans('EInvoiceNotifyColInfo').'</th>'.$th.$langs->trans('EInvoiceNotifyColError').'</th></tr>';
			foreach ($failed as $f) {
				$infos = array();
				foreach ((is_array($f['actiondata'] ?? null) ? $f['actiondata'] : array()) as $k => $v) {
					if ((string) $k !== '' && is_scalar($v) && (string) $v !== '') {
						$infos[] = $k.': '.$v;
					}
				}
				$prefix = '';
				if (($f['actioncode'] ?? '') === 'THIRDPARTY_NOT_FOUND') {
					$prefix = $langs->trans('EInvoiceNotifyErrUnknownSupplier');
				} elseif (($f['actioncode'] ?? '') === 'PRODUCT_NOT_FOUND') {
					$prefix = $langs->trans('EInvoiceNotifyErrUnknownProduct');
				}
				$motif = trim(strip_tags((string) ($f['message'] ?? '')));
				$motif = ($prefix !== '' ? $prefix.($motif !== '' ? ' - ' : '') : '').$motif;
				$body .= '<tr>'.$td.dol_escape_htmltag((string) ($f['flowId'] ?? '')).'</td>';
				$body .= $td.(empty($infos) ? '-' : dol_escape_htmltag(implode(', ', $infos))).'</td>';
				$body .= $td.($motif !== '' ? dol_escape_htmltag($motif) : '-').'</td></tr>';
			}
			$body .= '</table>';
		}

		$subject = $this->buildSubject(($isTest ? '[TEST] ' : '').$langs->transnoentities('EInvoiceNotifyEmailSubjectCore'));

		return $this->send($subject, $body);
	}

	/**
	* Alert on the flows that entered an anomaly state during a synchronization.
	*
	* @param	array<int,object>	$anomalies Flow documents {tracking_idref, fk_element_type, fk_element_id, ack_status, cdar_lifecycle_code, cdar_lifecycle_label, reason}
	* @return	int					See send()
	*/
	private function sendAnomalyAlert($anomalies)
	{
		global $langs;

		$langs->loadLangs(array('main', 'bills', 'einvoicing@einvoicing'));
		$urlroot	= DOL_MAIN_URL_ROOT;
		$th			= '<th style="border:1px solid #cccccc;text-align:left;">';
		$td			= '<td style="border:1px solid #cccccc;">';
		$body = '<p>'.$langs->trans('EInvoiceAnomalyEmailIntro', count($anomalies)).'</p>';
		$body .= '<table cellpadding="6" cellspacing="0" style="border-collapse:collapse;border:1px solid #cccccc;">';
		$body .= '<tr style="background-color:#f0f0f0;">'.$th.$langs->trans('Ref').'</th>'.$th.$langs->trans('EInvoiceAnomalyColStatus').'</th>';
		$body .= $th.$langs->trans('EInvoiceAnomalyColReason').'</th><th style="border:1px solid #cccccc;"></th></tr>';
		foreach ($anomalies as $a) {
			$url = '';
			if ((int) $a->fk_element_id > 0) {
				if ($a->fk_element_type === 'invoice_supplier') {
					$url = $urlroot.'/fourn/facture/card.php?id='.((int) $a->fk_element_id);
				} elseif ($a->fk_element_type === 'facture') {
					$url = $urlroot.'/compta/facture/card.php?id='.((int) $a->fk_element_id);
				}
			}
			$statuslabel = trim((string) $a->cdar_lifecycle_label);
			if ($statuslabel === '') {
				$statuslabel = ($a->ack_status === 'Error' ? $langs->trans('EInvStatusError') : (string) $a->cdar_lifecycle_code);
			}
			$body .= '<tr>'.$td.dol_escape_htmltag((string) $a->tracking_idref).'</td>';
			$body .= $td.dol_escape_htmltag($statuslabel).'</td>';
			$body .= $td.dol_escape_htmltag((string) $a->reason).'</td>';
			$body .= $td.($url !== '' ? '<a href="'.$url.'">'.$langs->trans('EInvoiceNotifyEmailOpen').'</a>' : '').'</td></tr>';
		}
		$body .= '</table>';

		return $this->send($this->buildSubject($langs->transnoentities('EInvoiceAnomalyEmailSubjectCore')), $body);
	}
}
