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
* \file    einvoicing/class/utils/EInvoicingImportPreview.class.php
* \ingroup einvoicing
* \brief   Dry run of the import of a received CII invoice, for the Debug tab: what the import would create
*          (supplier, lines, products, amounts, public note), with nothing written to the database. It reuses
*          the parsing and the amount resolution of CIIProtocol, so it shows what the import really does.
*/

require_once __DIR__.'/../protocols/CIIProtocol.class.php';


/**
 * Dry run of the import of a received CII invoice.
 */
class EInvoicingImportPreview extends CIIProtocol
{
	/**
	 * What the import of a CII XML would create. Read only.
	 *
	 * @param  string $xml Content of the CII XML
	 * @return array{res:int,error:string,header?:array,supplier?:array,notePublic?:string,lines?:array,totals?:array,xmlTotals?:array}
	 */
	public function preview($xml)
	{
		$xml = (string) $xml;
		if (trim($xml) === '') {
			return array('res' => -1, 'error' => 'Empty XML');
		}
		if (stripos($xml, 'CrossIndustryInvoice') === false) {
			return array('res' => -1, 'error' => 'Not a CII XML file');
		}

		try {
			$header	= $this->parseInvoiceHeader($xml);
			$lines	= $this->parseInvoiceLines($xml);
		} catch (Throwable $e) {
			return array('res' => -1, 'error' => $e->getMessage());
		}

		// Supplier: looked up only, the import creates or completes it
		$siren			= substr(preg_replace('/\D+/', '', (string) ($header['sellerLegalOrgId'] ?? '')), 0, 9);
		$vat			= trim((string) ($header['sellervatnumber'] ?? ''));
		$supplierId		= 0;
		$supplierName	= '';
		$conds			= array();
		if (strlen($siren) == 9) {
			$conds[] = "siren = '".$this->db->escape($siren)."'";
		}
		if ($vat !== '') {
			$conds[] = "tva_intra = '".$this->db->escape($vat)."'";
		}
		if (!empty($conds)) {
			$sql	= "SELECT rowid, nom FROM ".$this->db->prefix()."societe WHERE entity IN (".getEntity('societe').") AND (".implode(' OR ', $conds).")";
			$sql	.= $this->db->plimit(1);
			$resql	= $this->db->query($sql);
			if ($resql && ($obj = $this->db->fetch_object($resql))) {
				$supplierId		= (int) $obj->rowid;
				$supplierName	= (string) $obj->nom;
			}
		}

		// Public note: same rule as the import (free notes, no SubjectCode, as plain text)
		$notes = array();
		foreach ((array) ($header['documentNotes'] ?? array()) as $documentNote) {
			if (trim((string) ($documentNote['subjectCode'] ?? '')) === '') {
				$note = trim(str_replace("\xC2\xA0", ' ', dol_string_nohtmltag((string) ($documentNote['content'] ?? ''), 0)));
				if ($note !== '') {
					$notes[] = $note;
				}
			}
		}

		$autoCreate	= getDolGlobalInt('EINVOICING_PRODUCTS_AUTO_GENERATION');
		$freeLines	= getDolGlobalInt('EINVOICING_IMPORT_AS_FREE_LINES');
		$outLines	= array();
		$sumHt		= 0.0;
		$sumTva		= 0.0;
		foreach ($lines as $pl) {
			$pl['supplierId'] = $supplierId;

			// Product the import would use: existing | defaultrouting | new | free | manual
			$match	= array('status' => 'manual', 'id' => 0, 'ref' => '', 'type' => -1);
			$find	= ($supplierId > 0 ? $this->findProductFromEinvoiceLine($pl) : array('res' => 0));
			if ((int) ($find['res'] ?? 0) > 0) {
				$match['id']		= (int) $find['res'];
				$match['status']	= (($find['matchtype'] ?? '') === 'defaultrouting' ? 'defaultrouting' : 'existing');
				$resql	= $this->db->query("SELECT ref, fk_product_type FROM ".$this->db->prefix()."product WHERE rowid = ".((int) $match['id']));
				if ($resql && ($obj = $this->db->fetch_object($resql))) {
					$match['ref']	= (string) $obj->ref;
					$match['type']	= (int) $obj->fk_product_type;
				}
			} elseif ($autoCreate) {
				$match['status']	= 'new';
			} elseif ($freeLines) {
				$match['status']	= 'free';
			}

			// Amounts as the import stores them: unit price, line discount, then the reconciliation with BT-131
			$qty			= (float) ($pl['billedquantity'] ?? 0);
			$subprice		= null;
			$remisePercent	= 0.0;
			if (!empty($pl['lineAllowances'])) {
				$discount	= $this->resolveLineDiscountPercent($pl['lineAllowances'], $pl['lineTotalAmount'] ?? 0);
				if ($discount !== false) {
					$remisePercent	= (float) $discount['percent'];
					if ($qty) {
						$subprice	= round($discount['priceWithoutDiscount'] / $qty, 8);
					}
				}
			}
			if ($subprice === null) {
				$subprice = $this->resolveLineUnitPrice($pl);
			}
			$amounts		= $this->resolveLineAmounts($pl, $qty, (float) $subprice, $remisePercent);
			$qty			= $amounts['qty'];
			$subprice		= $amounts['subprice'];
			$remisePercent	= $amounts['remise_percent'];
			$warning		= $amounts['warning'];
			$totalHt		= (float) ($pl['lineTotalAmount'] ?? 0);
			$tvaTx			= (float) ($pl['rateApplicablePercent'] ?? 0);

			$outLines[] = array('lineid'		=> (string) ($pl['lineid'] ?? ''),
								'ref_supplier'	=> (string) ($pl['prodsellerid'] ?? ''),
								'name'			=> (string) ($pl['prodname'] ?? ''),
								'desc'			=> (string) ($pl['proddesc'] ?? ''),
								'match'			=> $match,
								'qty'			=> $qty,
								'unit'			=> (string) ($pl['billedquantityunitcode'] ?? ''),
								'subprice'		=> (float) $subprice,
								'remise_percent'=> $remisePercent,
								'tva_tx'		=> $tvaTx,
								'total_ht'		=> $totalHt,
								'warning'		=> $warning,
							);
			$sumHt += $totalHt;
			$sumTva += (float) ($pl['calculatedAmount'] ?? round($totalHt * $tvaTx / 100, 2));
		}

		return array('res'			=> 1,
					'error'			=> '',
					'header'		=> $header,
					'supplier'		=> array('name' => (string) ($header['sellername'] ?? ''), 'siren' => $siren, 'vat' => $vat, 'matchId' => $supplierId, 'matchName' => $supplierName),
					'notePublic'	=> implode("\n", $notes),
					'lines'			=> $outLines,
					'totals'		=> array('ht' => round($sumHt, 2), 'tva' => round($sumTva, 2), 'ttc' => round($sumHt + $sumTva, 2)),
					'xmlTotals'		=> array('ht' => isset($header['lineTotalAmount']) ? (float) $header['lineTotalAmount'] : null,
											'tva' => isset($header['taxTotalAmount']) ? (float) $header['taxTotalAmount'] : null,
											'ttc' => isset($header['grandTotalAmount']) ? (float) $header['grandTotalAmount'] : null,
											'due' => isset($header['duePayableAmount']) ? (float) $header['duePayableAmount'] : null,
										),
		);
	}
}
