<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrasfiles/core/modules/infrasfiles/widthdraw/doc/pdf_bordereau.modules.php
	* 	\ingroup	InfraS
	* 	\brief		PDF model "bordereau" : direct debit / credit transfer slip, one document per generation unit
	*				(a third party with its lines, or a parent company with the lines of its flagged third parties, depending on the split mode option)
	************************************************/

	// Libraries ************************************
	dol_include_once('/infrasfiles/core/modules/infrasfiles/modules_infrasfileswidthdraw.php');
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

	/************************************************
	* Class pdf_bordereau
	************************************************/
	class pdf_bordereau extends ModelePDFInfrasfileswidthdraw
	{
		/**
		*	Constructor
		*
		*	@param		DoliDB		$db		Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			parent::__construct($db);
			$langs->load('infrasfiles@infrasfiles');
			$this->name			= 'bordereau';
			$this->description	= $langs->trans('InfraSFilesModelBordereauDesc');
		}

		/**
		*	Write the PDF file
		*
		*	@param		InfrasFilesWithdraw	$object			Object to generate
		*	@param		Translate			$outputlangs	Lang output object
		*	@param		string				$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int					$hidedetails	Do not show line details
		*	@param		int					$hidedesc		Do not show desc
		*	@param		int					$hideref		Do not show ref
		*	@param		array|null			$moreparams		More parameters (key 'infrasfiles_unit' = generation unit)
		*	@return		int									1 if OK, <= 0 if KO
		**/
		public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
		{
			global $conf, $langs, $mysoc;

			if (!is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			if (getDolGlobalString('MAIN_USE_FPDF')) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'companies', 'bills', 'banks', 'withdrawals', 'salaries', 'infrasfiles@infrasfiles'));

			// Generation unit and lines
			$unit	= (is_array($moreparams) && !empty($moreparams['infrasfiles_unit'])) ? $moreparams['infrasfiles_unit'] : array('suffix' => '', 'label' => '', 'fk_soc' => 0, 'toparent' => false, 'addressee' => array(), 'lineids' => array());
			if (empty($object->infrasfiles_lines) && method_exists($object, 'infrasfilesFetchLines')) {
				$object->infrasfilesFetchLines();
			}
			$lines	= array();
			foreach ((array) $object->infrasfiles_lines as $line) {
				if (empty($unit['lineids']) || in_array($line['rowid'], $unit['lineids'])) {
					$lines[]	= $line;
				}
			}
			$isdebit	= ($object->type != 'bank-transfer');
			$title		= $outputlangs->transnoentities($isdebit ? 'InfraSFilesPdfTitleDebit' : 'InfraSFilesPdfTitleTransfer');

			// Output file
			$paths	= $this->infrasfilesGetFile($object, $unit, $outputlangs);
			if (!$paths) {
				return -1;
			}
			$this->infrasfilesBefore($object, $outputlangs, $paths['file']);

			// Bank account of the company
			$account	= new Account($this->db);
			if (!empty($object->fk_bank_account)) {
				$account->fetch($object->fk_bank_account);
			}
			$ics	= $isdebit ? (!empty($account->ics) ? $account->ics : getDolGlobalString('PRELEVEMENT_ICS', '')) : (!empty($account->ics_transfer) ? $account->ics_transfer : getDolGlobalString('PAYMENTBYBANKTRANSFER_ICS', ''));

			$pdf				= $this->infrasfilesInitPdf($outputlangs, $title.' '.$object->ref, $title);
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$width				= $this->page_largeur - $this->marge_gauche - $this->marge_droite;
			$this->infrasfilesAddPage($pdf);

			// Head
			$rightlines	= array(array($outputlangs->transnoentities('Ref'), $object->ref),
								array($outputlangs->transnoentities('Date'), dol_print_date($object->datec, 'day', false, $outputlangs)),
								array($outputlangs->transnoentities('TransData'), !empty($object->date_trans) ? dol_print_date($object->date_trans, 'day', false, $outputlangs) : ''),
								array($outputlangs->transnoentities('Status'), $object->LibStatut($object->statut, 0)));
			$posy	= $this->infrasfilesWriteHead($pdf, $object, $outputlangs, $title, $rightlines);

			// Blocks : company (creditor / ordering party) on the left, addressee (debtor / beneficiary) on the right
			$blockw	= ($width - 6) / 2;
			$company	= array($this->emetteur->name,
								$this->emetteur->address,
								trim($this->emetteur->zip.' '.$this->emetteur->town),
								!empty($account->label) ? $outputlangs->transnoentities('BankAccount').' : '.$account->label : '',
								!empty($account->iban) ? $outputlangs->transnoentities('IBAN').' : '.$account->iban : '',
								!empty($account->bic) ? $outputlangs->transnoentities('BIC').' : '.$account->bic : '',
								!empty($ics) ? $outputlangs->transnoentities('ICS').' : '.$ics : '');
			$ybottom	= $this->infrasfilesWriteBlock($pdf, $outputlangs, $this->marge_gauche, $posy, $blockw, $outputlangs->transnoentities($isdebit ? 'InfraSFilesPdfCreditor' : 'InfraSFilesPdfOrderingParty'), $company);
			// Addressee = the unit (parent company or third party) ; fallback on the third party of the first line (unit without addressee)
			$addressee	= !empty($unit['addressee']) ? $unit['addressee'] : (!empty($lines) ? array('name' => $lines[0]['name'], 'address' => $lines[0]['address'], 'zip' => $lines[0]['zip'], 'town' => $lines[0]['town']) : array());
			$toparent	= !empty($unit['toparent']);
			// Bank account shown in the block only when all the lines of the PDF use the same one, otherwise under each line of the table
			$ibans	= array();
			foreach ($lines as $line) {
				$ibans[(string) (!empty($line['rib']['iban']) ? $line['rib']['iban'] : '')]	= 1;
			}
			$sameiban	= (count($ibans) <= 1);
			if (!empty($addressee) && !empty($lines)) {
				$rib	= $lines[0]['rib'];
				$third	= array($addressee['name'],
								$addressee['address'],
								trim($addressee['zip'].' '.$addressee['town']),
								// Parent company of the third party, for information, when the PDF is not addressed to it
								(!$toparent && !empty($lines[0]['parent']['name'])) ? $outputlangs->transnoentities('ParentCompany').' : '.$lines[0]['parent']['name'] : '',
								($sameiban && !empty($rib['iban'])) ? $outputlangs->transnoentities('IBAN').' : '.$rib['iban'] : '',
								($sameiban && !empty($rib['bic'])) ? $outputlangs->transnoentities('BIC').' : '.$rib['bic'] : '',
								($sameiban && $isdebit && !empty($rib['rum'])) ? $outputlangs->transnoentities('RUM').' : '.$rib['rum'] : '');
				$ybottom2	= $this->infrasfilesWriteBlock($pdf, $outputlangs, $this->marge_gauche + $blockw + 6, $posy, $blockw, $outputlangs->transnoentities($isdebit ? 'InfraSFilesPdfDebtor' : 'InfraSFilesPdfBeneficiary'), $third);
				$ybottom	= max($ybottom, $ybottom2);
			}
			$posy	= $ybottom + 6;

			// Table of lines : document, date, due date, third party (with its bank account when they differ), status, amount ;
			// credit notes applied on a document are printed under it ; a rejected line is printed in red and summed apart
			$colx		= $this->marge_gauche + 2;
			$columns	= array(array('label' => $outputlangs->transnoentities('InfraSFilesPdfColDocument'),	'x' => $colx,		'w' => 34,	'align' => 'L'),
								array('label' => $outputlangs->transnoentities('Date'),							'x' => $colx + 34,	'w' => 20,	'align' => 'C'),
								array('label' => $outputlangs->transnoentities('InfraSFilesPdfColDueDate'),		'x' => $colx + 54,	'w' => 20,	'align' => 'C'),
								array('label' => $outputlangs->transnoentities('ThirdParty'),					'x' => $colx + 74,	'w' => 54,	'align' => 'L'),
								array('label' => $outputlangs->transnoentities('Status'),						'x' => $colx + 128,	'w' => 22,	'align' => 'C'),
								array('label' => $outputlangs->transnoentities('Amount'),						'x' => $colx + 150,	'w' => 34,	'align' => 'R'));
			$posy		= $this->infrasfilesWriteTableHeader($pdf, $outputlangs, $posy, $columns);
			$total		= 0;
			$nb			= 0;
			$nbrejected	= 0;
			$rejected	= 0;
			$statuslabels	= array(0 => 'StatusWaiting', 2 => ($isdebit ? 'StatusDebited' : 'StatusCredited'), 3 => 'StatusRefused');	// LignePrelevement::LibStatut()
			foreach ($lines as $line) {
				$total	+= $line['amount'];
				$isrejected	= ((int) $line['statut'] == 3);
				if ($isrejected) {
					$nbrejected++;
					$rejected	+= $line['amount'];
				}
				$rows	= array();
				if (empty($line['documents'])) {
					$rows[]	= array('ref' => '-', 'date' => '', 'due' => '', 'amount' => $line['amount'], 'credits' => array());
				} else {
					foreach ($line['documents'] as $document) {
						$rows[]	= array('ref'		=> $document['ref'].(!empty($document['ref_ext']) ? ' ('.$document['ref_ext'].')' : ''),
										'date'		=> !empty($document['date']) ? dol_print_date($document['date'], 'day', false, $outputlangs) : '',
										'due'		=> !empty($document['date_due']) ? dol_print_date($document['date_due'], 'day', false, $outputlangs) : '',
										'amount'	=> count($line['documents']) == 1 ? $line['amount'] : $document['amount'],
										'credits'	=> !empty($document['credits']) ? $document['credits'] : array());
					}
				}
				$lineiban	= (!$sameiban && !empty($line['rib']['iban'])) ? $line['rib']['iban'] : '';	// printed under the third party name, smaller, when the accounts differ inside the PDF
				foreach ($rows as $row) {
					$posy	= $this->infrasfilesCheckPageBreak($pdf, $object, $outputlangs, $posy, 8 + 4 * count($row['credits']), $title, $columns);
					if ($isrejected) {
						$pdf->SetTextColor(200, 0, 0);
					}
					$pdf->SetXY($columns[0]['x'], $posy + 1);
					$pdf->MultiCell($columns[0]['w'], 4, $outputlangs->convToOutputCharset($row['ref']), 0, 'L');
					$ynext	= $pdf->GetY();
					$pdf->SetXY($columns[1]['x'], $posy + 1);
					$pdf->MultiCell($columns[1]['w'], 4, $row['date'], 0, 'C');
					$pdf->SetXY($columns[2]['x'], $posy + 1);
					$pdf->MultiCell($columns[2]['w'], 4, $row['due'], 0, 'C');
					$pdf->SetXY($columns[3]['x'], $posy + 1);
					$pdf->MultiCell($columns[3]['w'], 4, $outputlangs->convToOutputCharset($line['name']), 0, 'L');
					if ($lineiban !== '') {
						$pdf->SetFont('', '', $default_font_size - 3);
						$pdf->SetXY($columns[3]['x'], $pdf->GetY());
						$pdf->MultiCell($columns[3]['w'], 3, $outputlangs->convToOutputCharset($lineiban), 0, 'L');
						$pdf->SetFont('', '', $default_font_size - 1);
					}
					$ynext	= max($ynext, $pdf->GetY());
					$pdf->SetXY($columns[4]['x'], $posy + 1);
					$pdf->MultiCell($columns[4]['w'], 4, $outputlangs->convToOutputCharset(isset($statuslabels[(int) $line['statut']]) ? $outputlangs->transnoentities($statuslabels[(int) $line['statut']]) : ''), 0, 'C');
					$pdf->SetXY($columns[5]['x'], $posy + 1);
					$pdf->MultiCell($columns[5]['w'], 4, price($row['amount'], 0, $outputlangs), 0, 'R');
					$ynext	= max($ynext, $pdf->GetY());
					// Credit notes applied on the document (reference and amount deducted), in grey under the document
					if (!empty($row['credits'])) {
						$pdf->SetFont('', 'I', $default_font_size - 2);
						$pdf->SetTextColor(100, 100, 100);
						foreach ($row['credits'] as $credit) {
							$pdf->SetXY($columns[0]['x'] + 3, $ynext);
							$pdf->MultiCell($columns[0]['w'] + $columns[1]['w'] + $columns[2]['w'] - 3, 4, $outputlangs->convToOutputCharset($outputlangs->transnoentities('CreditNote').' '.$credit['ref']), 0, 'L');
							$pdf->SetXY($columns[5]['x'], $ynext);
							$pdf->MultiCell($columns[5]['w'], 4, price(-1 * abs($credit['amount']), 0, $outputlangs), 0, 'R');
							$ynext	= $pdf->GetY();
						}
						$pdf->SetFont('', '', $default_font_size - 1);
					}
					$pdf->SetTextColor(0, 0, 0);
					$ynext	+= 1;
					$pdf->SetDrawColor(210, 210, 210);
					$pdf->line($this->marge_gauche, $ynext, $this->marge_gauche + $width, $ynext);
					$posy	= $ynext;
					$nb++;
				}
			}
			// Total (and rejected lines apart, when any)
			$posy	= $this->infrasfilesCheckPageBreak($pdf, $object, $outputlangs, $posy, ($nbrejected ? 18 : 12), $title, array());
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetXY($columns[0]['x'], $posy + 2);
			$pdf->MultiCell(100, 5, $outputlangs->transnoentities('InfraSFilesPdfNbLines', $nb), 0, 'L');
			$pdf->SetXY($columns[3]['x'], $posy + 2);
			$pdf->MultiCell($columns[3]['w'] + $columns[4]['w'], 5, $outputlangs->transnoentities('Total'), 0, 'R');
			$pdf->SetXY($columns[5]['x'], $posy + 2);
			$pdf->MultiCell($columns[5]['w'], 5, price($total, 0, $outputlangs, 1, -1, -1, $conf->currency), 0, 'R');
			$pdf->SetFont('', '', $default_font_size);
			if ($nbrejected) {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetXY($columns[0]['x'], $posy + 8);
				$pdf->MultiCell($width - 4, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('InfraSFilesPdfRejected', $nbrejected, price($rejected, 0, $outputlangs, 1, -1, -1, $conf->currency))), 0, 'R');
				$pdf->SetTextColor(0, 0, 0);
			}

			$this->_pagefoot($pdf, $object, $outputlangs);
			return $this->infrasfilesFinish($pdf, $paths, $object, $outputlangs);
		}
	}
