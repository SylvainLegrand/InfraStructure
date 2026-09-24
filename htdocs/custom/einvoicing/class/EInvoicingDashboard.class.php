<?php
/* InfraS add : fichier ajouté par InfraS, repris du module einvoicingsx (SYSAXES)
* Copyright (C) 2026		SYSAXES
* Copyright (C) 2026		InfraS					<technique@infras.fr>
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
* \file    einvoicing/class/EInvoicingDashboard.class.php
* \ingroup einvoicing
* \brief   Read-only collectors of the e-invoicing follow-up: they feed the home page of the module
*          (einvoicingindex.php) and the scheduled reports (class/utils/EInvoicingReports.class.php),
*          so both always show the same data.
*/

require_once __DIR__.'/einvoicing.class.php';
require_once __DIR__.'/utils/EInvoicingFollowup.class.php';


/**
 * Collectors of the e-invoicing dashboard and reports.
 */
class EInvoicingDashboard
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

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
	* Supplier invoices received from the platform, in the last $days days or in a period.
	*
	* @param	int			$days		Look-back window in days, used when no period is given
	* @param	int|null	$startTs	Start of the period (timestamp), with $endTs overrides $days
	* @param	int|null	$endTs		End of the period (timestamp, that day included)
	* @return	array<int,object>		Rows {id, dc, ref, ref_supplier, total_ht, total_ttc, socname, lc_code, lc_label, ack_status}
	*/
	public function receivedInvoices($days = 7, $startTs = null, $endTs = null)
	{
		$sql = "SELECT d.fk_element_id as id, MAX(d.date_creation) as dc, ff.ref, ff.ref_supplier,";
		$sql .= " ff.total_ht, ff.total_ttc, s.nom as socname,";
		// Latest e-invoicing status of the supplier invoice, as the card shows it
		$sql .= " last.cdar_lifecycle_code as lc_code, last.cdar_lifecycle_label as lc_label, last.ack_status as ack_status";
		$sql .= " FROM ".$this->db->prefix()."einvoicing_document as d";
		$sql .= " INNER JOIN ".$this->db->prefix()."facture_fourn as ff ON ff.rowid = d.fk_element_id";
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON s.rowid = ff.fk_soc";
		$sql .= " LEFT JOIN ".$this->db->prefix()."einvoicing_document as last ON last.rowid = (";
		$sql .= " SELECT MAX(dd.rowid) FROM ".$this->db->prefix()."einvoicing_document as dd";
		$sql .= " WHERE dd.fk_element_type = 'invoice_supplier' AND dd.fk_element_id = d.fk_element_id)";
		$sql .= " WHERE LOWER(d.flow_direction) = 'in' AND d.fk_element_type = 'invoice_supplier'";
		if ($startTs !== null && $endTs !== null) {
			$sql .= " AND d.date_creation >= '".$this->db->idate((int) $startTs)."'";
			$sql .= " AND d.date_creation < '".$this->db->idate((int) $endTs + 86400)."'";
		} else {
			$sql .= " AND d.date_creation >= '".$this->db->idate(dol_now() - ((int) $days) * 86400)."'";
		}
		$sql .= " AND ff.entity IN (".getEntity('facture_fourn').")";
		$sql .= " GROUP BY d.fk_element_id, ff.ref, ff.ref_supplier, ff.total_ht, ff.total_ttc, s.nom, last.cdar_lifecycle_code, last.cdar_lifecycle_label, last.ack_status";
		$sql .= " ORDER BY dc DESC";

		return $this->fetchAll($sql);
	}

	/**
	* Issued and received invoices in an anomaly state (see EInvoicingFollowup::isInAnomaly()), except the
	* abandoned ones.
	*
	* @return array<int,object> Rows {etype, id, ack_*, cdar_*, ref, total_ttc, socname}
	*/
	public function anomalies()
	{
		$rows = array();
		foreach (array('facture' => 'facture', 'invoice_supplier' => 'facture_fourn') as $etype => $table) {
			$sql = "SELECT d.fk_element_id as id, d.ack_status, d.ack_reason_code, d.ack_info,";
			$sql .= " d.cdar_lifecycle_code, d.cdar_lifecycle_label, d.cdar_reason_code, d.cdar_reason_desc, d.cdar_reason_detail,";
			$sql .= " i.ref, i.total_ttc, s.nom as socname";
			$sql .= " FROM ".$this->db->prefix()."einvoicing_document as d";
			$sql .= " INNER JOIN (SELECT fk_element_id, MAX(rowid) as maxid FROM ".$this->db->prefix()."einvoicing_document";
			$sql .= " WHERE fk_element_type = '".$this->db->escape($etype)."' AND fk_element_id > 0";
			$sql .= " GROUP BY fk_element_id) as last ON last.maxid = d.rowid";
			$sql .= " INNER JOIN ".$this->db->prefix().$table." as i ON i.rowid = d.fk_element_id";
			$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON s.rowid = i.fk_soc";
			$sql .= " WHERE (d.ack_status = 'Error' OR d.cdar_lifecycle_code IN ('".implode("','", EInvoicingFollowup::ANOMALY_LIFECYCLE_CODES)."'))";
			$sql .= " AND i.entity IN (".getEntity($table).")";
			$sql .= " AND NOT EXISTS (SELECT 1 FROM ".$this->db->prefix()."einvoicing_dismissed as dis";
			$sql .= " WHERE dis.element_type = '".$this->db->escape($etype)."' AND dis.element_id = d.fk_element_id)";
			$sql .= " ORDER BY i.ref";
			foreach ($this->fetchAll($sql) as $obj) {
				$obj->etype	= $etype;
				$rows[]		= $obj;
			}
		}

		return $rows;
	}

	/**
	* Active suppliers based in France (or with no country) that have no SIREN: a received e-invoice can only
	* be matched to them on their name or email, so they are at risk of being duplicated.
	*
	* @return array<int,object> Rows {rowid, nom, siret, tva_intra}
	*/
	public function suppliersWithoutSiren()
	{
		$sql = "SELECT s.rowid, s.nom, s.siret, s.tva_intra";
		$sql .= " FROM ".$this->db->prefix()."societe as s";
		$sql .= " LEFT JOIN ".$this->db->prefix()."c_country as c ON c.rowid = s.fk_pays";
		$sql .= " WHERE s.fournisseur > 0";
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		$sql .= " AND (s.siren IS NULL OR s.siren = '')";
		$sql .= " AND s.status = 1";
		$sql .= " AND (c.code = 'FR' OR s.fk_pays IS NULL OR s.fk_pays = 0)";
		$sql .= " ORDER BY s.nom";

		return $this->fetchAll($sql);
	}

	/**
	* Status of the customer invoices transmitted to the platform, except the abandoned ones. An invoice not
	* yet received (below 202) is "to watch" once its deposit is older than EINVOICING_SENT_WATCH_DELAY_DAYS
	* or its acknowledgement failed, "in transit" otherwise.
	*
	* @param	int			$days			Look-back window in days of the confirmed list, used when no period is given
	* @param	int|null	$confStartTs	Start of the period of the confirmed list (timestamp)
	* @param	int|null	$confEndTs		End of the period of the confirmed list (timestamp, that day included)
	* @return	array{watch:array<int,object>,intransit:array<int,object>,confirmed:array<int,object>,detail:array<int,object>}
	*/
	public function sentInvoicesStatus($days = 7, $confStartTs = null, $confEndTs = null)
	{
		$sql = "SELECT e.element_id, e.syncstatus, e.synccomment, e.flow_id, e.date_creation as deposit_dc,";
		$sql .= " f.ref, f.total_ht, f.total_ttc, f.datef, s.rowid as socid, s.nom as socname";
		$sql .= " FROM ".$this->db->prefix()."einvoicing_extlinks as e";
		$sql .= " INNER JOIN ".$this->db->prefix()."facture as f ON f.rowid = e.element_id";
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON s.rowid = f.fk_soc";
		$sql .= " WHERE e.element_type = 'facture'";
		$sql .= " AND e.flow_id IS NOT NULL AND e.flow_id <> ''";
		$sql .= " AND f.entity IN (".getEntity('facture').")";
		$sql .= " AND NOT EXISTS (SELECT 1 FROM ".$this->db->prefix()."einvoicing_dismissed as dis";
		$sql .= " WHERE dis.element_type = 'facture' AND dis.element_id = e.element_id)";
		$sql .= " ORDER BY f.datef ASC, f.ref ASC";

		$pending		= array();
		$confirmed		= array();
		$ids			= array();
		$useRange		= ($confStartTs !== null && $confEndTs !== null);
		$recentLimit	= dol_now() - ((int) $days) * 86400;
		foreach ($this->fetchAll($sql) as $obj) {
			$ids[]	= (int) $obj->element_id;
			if ((int) $obj->syncstatus < EInvoicing::STATUS_RECEIVED) {
				$pending[]	= $obj;
			} else {
				$datef	= $this->db->jdate($obj->datef);
				if ($useRange ? ($datef >= (int) $confStartTs && $datef < (int) $confEndTs + 86400) : ($datef >= $recentLimit)) {
					$confirmed[] = $obj;
				}
			}
		}

		// Latest flow document of each invoice (highest rowid wins)
		$detail = array();
		if (!empty($ids)) {
			$sql = "SELECT fk_element_id, ack_status, cdar_lifecycle_code, cdar_lifecycle_label, cdar_reason_code";
			$sql .= " FROM ".$this->db->prefix()."einvoicing_document";
			$sql .= " WHERE fk_element_type = 'facture' AND fk_element_id IN (".$this->db->sanitize(implode(',', $ids)).")";
			$sql .= " ORDER BY fk_element_id ASC, rowid ASC";
			foreach ($this->fetchAll($sql) as $obj) {
				$detail[(int) $obj->fk_element_id] = $obj;
			}
		}

		$watchBefore	= dol_now() - getDolGlobalInt('EINVOICING_SENT_WATCH_DELAY_DAYS', 4) * 86400;
		$watch			= array();
		$intransit		= array();
		foreach ($pending as $obj) {
			$doc		= $detail[(int) $obj->element_id] ?? null;
			$ack		= ($doc ? strtolower(trim((string) $doc->ack_status)) : '');
			$ackBad		= in_array($ack, array('error', 'ko', 'failed', 'rejected', 'refused'), true);
			$deposit	= (!empty($obj->deposit_dc) ? $this->db->jdate($obj->deposit_dc) : 0);
			if ($ackBad || $deposit <= 0 || $deposit < $watchBefore) {
				$watch[]		= $obj;
			} else {
				$intransit[]	= $obj;
			}
		}
		return array('watch' => $watch, 'intransit' => $intransit, 'confirmed' => $confirmed, 'detail' => $detail);
	}

	/**
	* Customer invoices whose e-invoicing follow-up was abandoned.
	*
	* @return array<int,object> Rows {rowid, element_id, comment, date_creation, ref, total_ttc, socid, socname}
	*/
	public function dismissedInvoices()
	{
		$sql = "SELECT dis.rowid, dis.element_id, dis.comment, dis.date_creation,";
		$sql .= " f.ref, f.total_ttc, s.rowid as socid, s.nom as socname";
		$sql .= " FROM ".$this->db->prefix()."einvoicing_dismissed as dis";
		$sql .= " INNER JOIN ".$this->db->prefix()."facture as f ON f.rowid = dis.element_id AND dis.element_type = 'facture'";
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON s.rowid = f.fk_soc";
		$sql .= " WHERE f.entity IN (".getEntity('facture').")";
		$sql .= " ORDER BY dis.date_creation DESC, f.ref ASC";

		return $this->fetchAll($sql);
	}

	/**
	* What the module transmitted to the platform in the last $hours hours: customer invoices sent, cash-in
	* statuses (212) and other lifecycle statuses.
	*
	* @param  int $hours Look-back window in hours
	* @return array{cashins:array<int,object>,others:array<int,object>,sent:array<int,object>,refFact:array<int,object>,refFourn:array<int,object>}
	*/
	public function outbound($hours = 24)
	{
		$since = "'".$this->db->idate(dol_now() - ((int) $hours) * 3600)."'";

		$sql = "SELECT d.fk_element_id as id, MAX(d.date_creation) as dc, f.ref, f.total_ht, f.total_ttc, s.nom as socname";
		$sql .= " FROM ".$this->db->prefix()."einvoicing_document as d";
		$sql .= " INNER JOIN ".$this->db->prefix()."facture as f ON f.rowid = d.fk_element_id";
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON s.rowid = f.fk_soc";
		$sql .= " WHERE LOWER(d.flow_direction) = 'out' AND d.fk_element_type = 'facture'";
		$sql .= " AND d.date_creation >= ".$since;
		$sql .= " AND f.entity IN (".getEntity('facture').")";
		$sql .= " GROUP BY d.fk_element_id, f.ref, f.total_ht, f.total_ttc, s.nom";
		$sql .= " ORDER BY dc DESC";
		$sent = $this->fetchAll($sql);

		$cashins	= array();
		$others		= array();
		$factIds	= array();
		$fournIds	= array();
		$sql = "SELECT element_id, element_type, lc_status, lc_validation_status, lc_reason_code, date_creation";
		$sql .= " FROM ".$this->db->prefix()."einvoicing_lifecycle_msg";
		$sql .= " WHERE LOWER(direction) = 'out' AND date_creation >= ".$since;
		$sql .= " ORDER BY date_creation DESC";
		foreach ($this->fetchAll($sql) as $obj) {
			if ($obj->element_type === 'facture') {
				$factIds[] = (int) $obj->element_id;
			} elseif ($obj->element_type === 'invoice_supplier') {
				$fournIds[] = (int) $obj->element_id;
			}
			if ((int) $obj->lc_status === EInvoicing::STATUS_PAID && $obj->element_type === 'facture') {
				$obj->cashed = $this->cashedAmountFor((int) $obj->element_id, $obj->date_creation);
				$cashins[] = $obj;
			} else {
				$others[] = $obj;
			}
		}

		return array('cashins'	=> $cashins,
					'others'	=> $others,
					'sent'		=> $sent,
					'refFact'	=> $this->fetchInvoiceRefs('facture', $factIds),
					'refFourn'	=> $this->fetchInvoiceRefs('facture_fourn', $fournIds),
				);
	}

	/**
	* Amount cashed in behind a 212 status. It is not stored on the lifecycle message, but the status is sent
	* when the customer payment is created, so the payments of the invoice created that same day carry it.
	*
	* @param	int			$factureId  Customer invoice id
	* @param	string		$statusDate Date of the lifecycle message (database format)
	* @return	float|null	Amount, null when nothing matches
	*/
	private function cashedAmountFor($factureId, $statusDate)
	{
		$ts = $this->db->jdate($statusDate);
		if (empty($ts)) {
			return null;
		}
		$dayStart = dol_get_first_hour($ts);

		$sql = "SELECT SUM(pf.amount) as amt FROM ".$this->db->prefix()."paiement_facture as pf";
		$sql .= " INNER JOIN ".$this->db->prefix()."paiement as p ON p.rowid = pf.fk_paiement";
		$sql .= " WHERE pf.fk_facture = ".((int) $factureId);
		$sql .= " AND p.datec >= '".$this->db->idate($dayStart)."' AND p.datec < '".$this->db->idate($dayStart + 86400)."'";
		$rows = $this->fetchAll($sql);

		return (!empty($rows) && $rows[0]->amt !== null) ? (float) $rows[0]->amt : null;
	}

	/**
	* Ref and thirdparty name of a set of invoices, keyed by id.
	*
	* @param  string $table 'facture' or 'facture_fourn'
	* @param  int[]  $ids   Invoice ids
	* @return array<int,object>
	*/
	private function fetchInvoiceRefs($table, $ids)
	{
		$out = array();
		$ids = array_values(array_unique(array_map('intval', $ids)));
		if (empty($ids)) {
			return $out;
		}
		$sql = "SELECT i.rowid as id, i.ref as ref, s.nom as socname";
		$sql .= " FROM ".$this->db->prefix().$table." as i";
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON s.rowid = i.fk_soc";
		$sql .= " WHERE i.rowid IN (".$this->db->sanitize(implode(',', $ids)).")";
		foreach ($this->fetchAll($sql) as $obj) {
			$out[(int) $obj->id] = $obj;
		}

		return $out;
	}

	/**
	* Run a SELECT and return all its rows (empty array on error).
	*
	* @param  string $sql Query
	* @return array<int,object>
	*/
	private function fetchAll($sql)
	{
		$rows	= array();
		$resql	= $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' '.$this->db->lasterror(), LOG_ERR);
			return $rows;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$rows[] = $obj;
		}
		$this->db->free($resql);
		return $rows;
	}
}
