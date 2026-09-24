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
* \file    einvoicing/class/utils/EInvoicingInvoiceTools.class.php
* \ingroup einvoicing
* \brief   Helpers of the invoice cards: e-invoice status badge in the banner, lines with a negative amount
*          (converted into a global discount), recap shown before the transmission to the platform.
*/

require_once __DIR__.'/../einvoicing.class.php';
require_once __DIR__.'/EInvoicingFollowup.class.php';


/**
 * Helpers of the customer and supplier invoice cards.
 */
class EInvoicingInvoiceTools
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
	* Script adding a colored "E-invoice: <status>" badge under the status of the card banner. The banner
	* belongs to the core: when its anchor is not found the badge is simply omitted.
	*
	* @param	Facture|FactureFournisseur	$object Invoice
	* @return	string						HTML, empty when there is no meaningful status yet
	*/
	public function bannerStatusBadge($object)
	{
		global $langs;

		if (empty($object->id) || !in_array($object->element, array('facture', 'invoice_supplier'))) {
			return '';
		}

		$einvoicing	= new EInvoicing($this->db);
		$followup	= new EInvoicingFollowup($this->db);
		if ($followup->isDismissed($object->element, (int) $object->id)) {
			$label		= $langs->transnoentitiesnoconv('EInvAbandonBadge');
			$badgeclass	= 'badge-status9';
		} else {
			$code	= 0;
			if ($object->element == 'facture') {
				$status	= $einvoicing->fetchLastknownInvoiceStatus((int) $object->id, (string) $object->ref);
				$code	= (int) ($status['code'] ?? EInvoicing::STATUS_UNKNOWN);
			} else {
				$sql	= "SELECT lc_status FROM ".$this->db->prefix()."einvoicing_lifecycle_msg";
				$sql	.= " WHERE element_type = 'invoice_supplier' AND element_id = ".((int) $object->id)." AND lc_validation_status = 'Ok'";
				$sql	.= " ORDER BY rowid DESC".$this->db->plimit(1);
				$resql	= $this->db->query($sql);
				if ($resql && ($obj = $this->db->fetch_object($resql))) {
					$code = (int) $obj->lc_status;
				}
			}
			if ($code <= 0 || in_array($code, array(EInvoicing::STATUS_NOT_GENERATED, EInvoicing::STATUS_IGNORE, EInvoicing::STATUS_IGNORE_2), true)) {
				return '';
			}
			$label	= $einvoicing->getStatusLabel($code, $object->element);
			if (in_array($code, array(EInvoicing::STATUS_DISPUTED, EInvoicing::STATUS_SUSPENDED, EInvoicing::STATUS_REFUSED, EInvoicing::STATUS_REJECTED, EInvoicing::STATUS_ERROR), true)) {
				$badgeclass = 'badge-status8';
			} elseif (in_array($code, array(EInvoicing::STATUS_APPROVED, EInvoicing::STATUS_COMPLETED, EInvoicing::STATUS_PAYMENT_SENT, EInvoicing::STATUS_PAID), true)) {
				$badgeclass = 'badge-status4';
			} else {
				$badgeclass = 'badge-status1';
			}
		}

		$text = $langs->transnoentitiesnoconv('EInvoiceBannerPrefix').' : '.$label;
		$html = '<div class="einv-banner-badge right margintoponlyshort"><span class="badge badge-status '.$badgeclass.'" title="'.dol_escape_htmltag($text).'">'.dol_escape_htmltag($text).'</span></div>';

		return '<script>jQuery(function() { if (jQuery(".einv-banner-badge").length) { return; }'
			. ' var target = jQuery(".arearef .statusref").first(); if (target.length) { target.append('.json_encode($html).'); } });</script>';
	}

	/**
	* Lines of a standard customer invoice used as a rebate with a negative amount. EN 16931 wants a document
	* allowance (BG-20) instead, and a negative unit price breaks BR-27. Lines already linked to a discount
	* and deposit lines are conformant.
	*
	* @param	Facture				$invoice Customer invoice
	* @return	FactureLigne[]		Lines to convert
	*/
	public function getNegativeLines($invoice)
	{
		$out = array();
		if ($invoice->element != 'facture' || (int) $invoice->type !== Facture::TYPE_STANDARD) {
			return $out;
		}
		if (empty($invoice->lines)) {
			$invoice->fetch_lines();
		}
		foreach ((array) $invoice->lines as $line) {
			if ((float) $line->total_ht >= 0 || !empty($line->fk_remise_except) || $line->desc === '(DEPOSIT)') {
				continue;
			}
			$out[] = $line;
		}

		return $out;
	}

	/**
	* Turn the lines with a negative amount of a draft invoice into global discounts of the same amounts and
	* VAT rate, emitted as document allowances: the total of the invoice does not change.
	*
	* @param	Facture	$invoice	Draft customer invoice
	* @param	User	$user		User
	* @return	int					Number of lines converted, <0 on error ($this->error)
	*/
	public function convertNegativeLines($invoice, $user)
	{
		global $conf, $langs;

		if ((int) $invoice->status !== Facture::STATUS_DRAFT) {
			$this->error = $langs->trans('EInvoiceNegPriceNeedsDraft');
			return -1;
		}
		// The multicurrency amounts of the discount would have to be computed apart
		if (!empty($invoice->multicurrency_code) && $invoice->multicurrency_code != $conf->currency) {
			$this->error = $langs->trans('EInvoiceNegPriceMulticurrency');
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		// Snapshot first: deleting and adding lines changes $invoice->lines
		$toconvert	= array();
		foreach ($this->getNegativeLines($invoice) as $line) {
			$desc			= trim(dol_string_nohtmltag((string) $line->desc));
			$toconvert[]	= array('lineid'	=> (int) $line->id,
									'ht'		=> abs((float) $line->total_ht),
									'tva'		=> abs((float) $line->total_tva),
									'ttc'		=> abs((float) $line->total_ttc),
									'tva_tx'	=> $line->tva_tx,
									'vat_src_code'=> (string) $line->vat_src_code,
									'desc'		=> ($desc !== '' ? $desc : $langs->transnoentitiesnoconv('Discount')),
								);
		}

		$nb = 0;
		foreach ($toconvert as $c) {
			if ($invoice->deleteline($c['lineid'], $invoice->id) < 0) {
				$this->error = $invoice->error;
				return -1;
			}
			$discount							= new DiscountAbsolute($this->db);
			$discount->fk_soc					= $invoice->socid;
			$discount->socid					= $invoice->socid;
			$discount->discount_type			= 0;
			$discount->amount_ht				= $c['ht'];
			$discount->amount_tva				= $c['tva'];
			$discount->amount_ttc				= $c['ttc'];
			$discount->multicurrency_amount_ht	= $c['ht'];
			$discount->multicurrency_amount_tva	= $c['tva'];
			$discount->multicurrency_amount_ttc	= $c['ttc'];
			$discount->tva_tx					= $c['tva_tx'];
			$discount->vat_src_code				= $c['vat_src_code'];
			$discount->description				= $c['desc'];
			if ($discount->create($user) < 0) {
				$this->error = $discount->error;
				return -1;
			}
			if ($invoice->insert_discount($discount->id) < 0) {
				$this->error = $invoice->error;
				return -1;
			}
			$nb++;
		}
		if ($nb > 0) {
			$invoice->update_price(1);
			$invoice->fetch_lines();
		}

		return $nb;
	}

	/**
	* Warning of the card about the lines with a negative amount, with the conversion button on a draft.
	*
	* @param  Facture $invoice Customer invoice
	* @return string           HTML, empty when there is no such line
	*/
	public function negativeLinesBlock($invoice)
	{
		global $langs;

		$lines = $this->getNegativeLines($invoice);
		if (empty($lines)) {
			return '';
		}

		$labels	= array();
		foreach ($lines as $line) {
			$label		= (!empty($line->product_label) ? $line->product_label : (!empty($line->desc) ? $line->desc : (string) $line->ref));
			$labels[]	= dol_trunc(dol_string_nohtmltag($label), 40).' ('.price($line->total_ht).')';
		}

		$out	= '<tr class="treinvoicing_collapseseparator"><td class="tdtop">'.img_warning().' '.$langs->trans('EInvoiceNegPriceTitle').'</td><td>';
		$out	.= '<div class="warning">'.$langs->trans('EInvoiceNegPriceHelp').'<br>'.dol_escape_htmltag(implode(' ; ', $labels));
		if ((int) $invoice->status === Facture::STATUS_DRAFT) {
			$out	.= '<br><a class="butAction marginleftonly" href="'.$_SERVER['PHP_SELF'].'?facid='.((int) $invoice->id).'&action=einvoicing_fix_negprice&token='.newToken().'"';
			$out	.= ' onclick="return confirm('.dol_escape_htmltag(json_encode($langs->transnoentitiesnoconv('EInvoiceNegPriceConfirm'))).');">'.$langs->trans('EInvoiceNegPriceFixButton').'</a>';
		} else {
			$out	.= '<br><span class="opacitymedium">'.$langs->trans('EInvoiceNegPriceDraftOnly').'</span>';
		}

		return $out.'</div></td></tr>';
	}

	/**
	* Recap shown in the confirmation before the transmission: thirdparty, amounts, electronic address.
	*
	* @param	Facture	$invoice	Customer invoice
	* @return	string				HTML
	*/
	public function sendRecap($invoice)
	{
		global $conf, $langs;

		$langs->loadLangs(array('companies', 'bills', 'einvoicing@einvoicing'));
		if (!is_object($invoice->thirdparty)) {
			$invoice->fetch_thirdparty();
		}
		$thirdparty	= $invoice->thirdparty;
		$einvoicing	= new EInvoicing($this->db);
		$status		= $einvoicing->fetchLastknownInvoiceStatus((int) $invoice->id, (string) $invoice->ref);
		$address	= (string) ($status['override_routing_id'] ?? '');
		if ($address === '') {
			$default	= $einvoicing->fetchDefaultRouting((int) $invoice->socid, 'thirdparty');
			$address	= ((is_string($default) && $default !== '' && $default !== '0') ? $default : preg_replace('/[^0-9]/', '', (string) idprof($thirdparty)));
		}
		$postal	= trim($thirdparty->address.' '.$thirdparty->zip.' '.$thirdparty->town);
		$out	= '<b>'.$langs->trans('ThirdParty').'</b> : '.dol_escape_htmltag($thirdparty->name);
		if (!empty($thirdparty->idprof1)) {
			$out	.= ' <span class="opacitymedium">('.dol_escape_htmltag($thirdparty->idprof1).')</span>';
		}
		$out	.= '<br>';
		if ($postal !== '') {
			$out	.= '<b>'.$langs->trans('Address').'</b> : '.dol_escape_htmltag($postal).'<br>';
		}
		$out	.= '<b>'.$langs->trans('AmountHT').'</b> : '.price($invoice->total_ht, 0, $langs, 1, -1, -1, $conf->currency).'<br>';
		// Rounded TTC of the LTS core when it has it (sum of the rounded totals), the stored TTC otherwise
		$totalttc	= (method_exists($invoice, 'getRoundedTotalTTC') ? $invoice->getRoundedTotalTTC() : $invoice->total_ttc);
		$out	.= '<b>'.$langs->trans('AmountTTC').'</b> : '.price($totalttc, 0, $langs, 1, -1, -1, $conf->currency).'<br>';
		$out	.= '<b>'.$langs->trans('EInvoicingBillingAddress').'</b> : '.($address !== '' ? dol_escape_htmltag($address) : '<span class="opacitymedium">'.$langs->trans('Automatic').'</span>');

		return $out;
	}
}
