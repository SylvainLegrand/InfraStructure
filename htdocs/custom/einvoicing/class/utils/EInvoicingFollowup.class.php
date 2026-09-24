<?php
/* InfraS add : fichier ajouté par InfraS, repris du module einvoicingsx (SYSAXES)
* Copyright (C) 2026		SYSAXES
* Copyright (C) 2026		InfraS					<technique@infras.fr>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
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
* \file    einvoicing/class/utils/EInvoicingFollowup.class.php
* \ingroup einvoicing
* \brief   Follow-up of the invoices sent to or received from the platform: anomaly state, and the
*          user's decision to abandon (dismiss) or reactivate that follow-up (llx_einvoicing_dismissed).
*/

require_once __DIR__.'/../einvoicing.class.php';


/**
 * Follow-up state of an invoice for the e-invoicing reports, alerts and dashboard.
 */
class EInvoicingFollowup
{
	/**
	 * Lifecycle codes that put a transmitted invoice in an anomaly state: disputed, suspended, refused, rejected.
	 */
	const ANOMALY_LIFECYCLE_CODES = array('207', '208', '210', '213');

	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string[] Errors
	 */
	public $errors = array();

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
	* Is the invoice in an anomaly state? The latest flow document of the invoice reports an acknowledgement
	* error, or one of ANOMALY_LIFECYCLE_CODES.
	*
	* @param	string	$elementType	'facture' or 'invoice_supplier'
	* @param	int		$elementId		Invoice id
	* @return	bool
	*/
	public function isInAnomaly($elementType, $elementId)
	{
		$sql = "SELECT ack_status, cdar_lifecycle_code FROM ".$this->db->prefix()."einvoicing_document";
		$sql .= " WHERE fk_element_type = '".$this->db->escape($elementType)."' AND fk_element_id = ".((int) $elementId);
		$sql .= " ORDER BY rowid DESC";
		$sql .= $this->db->plimit(1);
		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);

		return ($obj && ($obj->ack_status === 'Error' || in_array((string) $obj->cdar_lifecycle_code, self::ANOMALY_LIFECYCLE_CODES, true)));
	}

	/**
	* Has the user abandoned the e-invoicing follow-up of this invoice?
	*
	* @param	string	$elementType	'facture' or 'invoice_supplier'
	* @param	int		$elementId		Invoice id
	* @return	bool
	*/
	public function isDismissed($elementType, $elementId)
	{
		$sql = "SELECT rowid FROM ".$this->db->prefix()."einvoicing_dismissed";
		$sql .= " WHERE element_type = '".$this->db->escape($elementType)."' AND element_id = ".((int) $elementId);
		$sql .= " AND entity IN (".getEntity($elementType === 'invoice_supplier' ? 'facture_fourn' : 'facture').")";
		$sql .= $this->db->plimit(1);
		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}
		$found = ($this->db->num_rows($resql) > 0);
		$this->db->free($resql);

		return $found;
	}

	/**
	* Is the follow-up of an issued invoice still open, i.e. does it show up in the reports? It is when the
	* invoice is in an anomaly state, or when it was transmitted (a flow_id exists) but its reception is not
	* confirmed yet (status below 202 Received).
	*
	* @param	string	$elementType	'facture'
	* @param	int		$elementId		Invoice id
	* @return	bool
	*/
	public function isFollowupOpen($elementType, $elementId)
	{
		if ($this->isInAnomaly($elementType, $elementId)) {
			return true;
		}
		if ($elementType !== 'facture') {
			return false;
		}

		$sql = "SELECT syncstatus FROM ".$this->db->prefix()."einvoicing_extlinks";
		$sql .= " WHERE element_type = 'facture' AND element_id = ".((int) $elementId);
		$sql .= " AND flow_id IS NOT NULL AND flow_id <> ''";
		$sql .= $this->db->plimit(1);
		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);

		return ($obj && (int) $obj->syncstatus < EInvoicing::STATUS_RECEIVED);
	}

	/**
	* Abandon the e-invoicing follow-up of an invoice, and log the comment as an event of the invoice.
	* Idempotent: an invoice already abandoned is left as it is.
	*
	* @param  CommonObject $object  Invoice
	* @param  string       $comment Reason given by the user
	* @param  User         $user    User abandoning the follow-up
	* @return int                   >0 if OK, <0 if KO
	*/
	public function dismiss($object, $comment, $user)
	{
		global $conf, $langs;

		if ($this->isDismissed($object->element, (int) $object->id)) {
			return 1;
		}

		$sql = "INSERT INTO ".$this->db->prefix()."einvoicing_dismissed";
		$sql .= " (element_type, element_id, comment, date_creation, fk_user_creat, entity)";
		$sql .= " VALUES ('".$this->db->escape($object->element)."', ".((int) $object->id);
		$sql .= ", '".$this->db->escape((string) $comment)."', '".$this->db->idate(dol_now())."'";
		$sql .= ", ".((int) $user->id).", ".((int) $conf->entity).")";
		if (!$this->db->query($sql)) {
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		// Best effort: the follow-up is abandoned even when the event cannot be recorded
		$this->addEvent($object->element, (int) $object->id, 'AC_EINVOICING_ANOMALY_DISMISSED', $langs->transnoentitiesnoconv('EInvAbandonEventLabel'), $comment, $user, $object);

		return 1;
	}

	/**
	* Reactivate the e-invoicing follow-up of an abandoned invoice, so it shows up in the reports again.
	*
	* @param	string	$elementType	'facture' or 'invoice_supplier'
	* @param	int		$elementId		Invoice id
	* @param	User		$user			User reactivating the follow-up
	* @return	int						>0 if OK, <0 if KO
	*/
	public function reactivate($elementType, $elementId, $user)
	{
		global $langs;

		$sql = "DELETE FROM ".$this->db->prefix()."einvoicing_dismissed";
		$sql .= " WHERE element_type = '".$this->db->escape($elementType)."' AND element_id = ".((int) $elementId);
		$sql .= " AND entity IN (".getEntity($elementType === 'invoice_supplier' ? 'facture_fourn' : 'facture').")";
		if (!$this->db->query($sql)) {
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		$this->addEvent($elementType, (int) $elementId, 'AC_EINVOICING_ANOMALY_REACTIVATED', $langs->transnoentitiesnoconv('EInvAbandonReactivateEventLabel'), '', $user);

		return 1;
	}

	/**
	* Readable reason of an anomaly, from the fields a flow document carries: the label of the CDAR reason
	* code when it is known, its description and detail, and the acknowledgement message on an ack error.
	*
	* @param	?string		$ackStatus		Acknowledgement status ('Error' when the platform rejected the document)
	* @param	?string		$ackReasonCode	Acknowledgement reason code
	* @param	?string		$ackInfo		Acknowledgement message
	* @param	?string		$reasonCode		CDAR reason code
	* @param	?string		$reasonDesc		CDAR reason description
	* @param	?string		$reasonDetail	CDAR reason detail
	* @return	string						Readable reason, not HTML encoded, '' when nothing is known
	*/
	public function buildReadableReason($ackStatus, $ackReasonCode, $ackInfo, $reasonCode, $reasonDesc, $reasonDetail)
	{
		global $langs;

		$langs->load('einvoicing@einvoicing');
		$parts = array();

		$reasonCode	= trim((string) $reasonCode);
		if ($reasonCode !== '') {
			$key		= EInvoicing::REASONS[$reasonCode]['label'] ?? '';
			$label		= ($key !== '' ? $langs->transnoentitiesnoconv($key) : '');
			$parts[]	= (($label !== '' && $label !== $key) ? $label.' ('.$reasonCode.')' : $reasonCode);
		}
		$reasonDesc = trim((string) $reasonDesc);
		if ($reasonDesc !== '' && !in_array($reasonDesc, $parts, true)) {
			$parts[]	= $reasonDesc;
		}
		$reasonDetail	= trim((string) $reasonDetail);
		if ($reasonDetail !== '' && $reasonDetail !== $reasonDesc) {
			$parts[]	= $reasonDetail;
		}
		if ((string) $ackStatus === 'Error') {
			$acktxt	= trim((string) $ackInfo);
			if ($acktxt === '') {
				$acktxt	= trim((string) $ackReasonCode);
			}
			$parts[] = $langs->transnoentitiesnoconv('EInvoiceAnomalyAckError').($acktxt !== '' ? ' : '.$acktxt : '');
		}

		return implode(' - ', $parts);
	}

	/**
	* Record an event on the invoice (best effort, a failure is only logged).
	*
	* @param	string				$elementType	Element type
	* @param	int					$elementId		Element id
	* @param	string				$code			Event code
	* @param	string				$label			Event label
	* @param	string				$note			Private note of the event
	* @param	User				$user			User
	* @param	CommonObject|null	$object			Invoice, when loaded, to link the event to its thirdparty and project
	* @return	void
	*/
	private function addEvent($elementType, $elementId, $code, $label, $note, $user, $object = null)
	{
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

		$actioncomm					= new ActionComm($this->db);
		$actioncomm->type_code		= 'AC_OTH_AUTO';
		$actioncomm->code			= $code;
		$actioncomm->label			= $label;
		$actioncomm->note_private	= $note;
		$actioncomm->datep			= dol_now();
		$actioncomm->datef			= dol_now();
		$actioncomm->percentage		= -1;
		$actioncomm->authorid		= $user->id;
		$actioncomm->userownerid	= $user->id;
		$actioncomm->elementid		= $elementId;
		$actioncomm->elementtype	= $elementType;
		if (is_object($object)) {
			$actioncomm->socid		= (int) $object->socid;
			$actioncomm->fk_project = (int) $object->fk_project;
		}
		if ($actioncomm->create($user) < 0) {
			dol_syslog(__METHOD__.' Failed to record event '.$code.' on '.$elementType.' '.$elementId.': '.$actioncomm->error, LOG_WARNING);
		}
	}
}
