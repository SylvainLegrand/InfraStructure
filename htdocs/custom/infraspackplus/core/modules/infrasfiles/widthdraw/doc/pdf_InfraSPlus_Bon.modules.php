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
	* 	\file		./infraspackplus/core/modules/infrasfiles/widthdraw/doc/pdf_InfraSPlus_Bon.modules.php
	* 	\ingroup	InfraS
	* 	\brief		InfraSPlus PDF model of the direct debit / credit transfer slip (module InfraSFiles, object 'widthdraw') :
	*				same content as the 'bordereau' model of InfraSFiles (one document per third party or per parent company,
	*				invoices, due dates, credit notes, status, total) with the InfraSPackPlus layout and options
	*				(logo, address frames, fonts, colors, borders, watermark, mentions, notes, signature, footer image, merged files)
	************************************************/

	// Libraries ************************************
	dol_include_once('/infrasfiles/core/modules/infrasfiles/modules_infrasfileswidthdraw.php');
	if (!class_exists('ModelePDFInfrasfileswidthdraw')) {
		return;	// Module InfraSFiles not installed : this model has no parent class, nothing to declare
	}
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	dol_include_once('/infraspackplus/class/address.class.php');

	/************************************************
	*	Class to generate the InfraS PDF direct debit / credit transfer slip
	************************************************/
	#[\AllowDynamicProperties]	// pdf_InfraSPlus_getValues() fills the whole set of InfraSPlus layout properties (parsed as a comment by PHP < 8.0)
	class pdf_InfraSPlus_Bon extends ModelePDFInfrasfileswidthdraw
	{
		public $db;
		public $name;
		public $description;
		public $defaulttemplate;
		public $use_fpdf;
		public $decal_round;
		public $formatpage;
		public $dash_between_line;
		public $multi_files;
		public $font;
		public $headertxtcolor;
		public $header_align_left;
		public $bodytxtcolor;
		public $exftxtcolor;
		public $title_size;
		public $ht_top_table;
		public $height_top_table;
		public $hide_top_table;
		public $Rounded_rect;
		public $title_bg;
		public $bg_color;
		public $txtcolor;
		public $tblLineW;
		public $tblLineDash;
		public $tblLineColor;
		public $showtblline;
		public $verLineColor;
		public $showverline;
		public $horLineColor;
		public $lineSep_hight;
		public $ht_signarea;
		public $signLineW;
		public $signLineDash;
		public $signLineColor;
		public $e_signing;
		public $type_foot;
		public $hidepagenum;
		public $maxsizeimgfoot;
		public $left_recep_corner;
		public $top_recep_corner;
		public $use_iso_location;
		public $tab_top;
		public $line_height;
		public $stdLineStyle = array();
		public $horLineStyle = array();
		public $signLineStyle = array();
		public $tblLineStyle = array();
		public $verLineStyle = array();
		public $tab_hl = 4;
		public $colpad;
		public $logo;
		public $adr;
		public $customerAddrSelect;
		public $listfreet;
		public $listnotep;
		public $pied;
		public $files;
		public $include_alias;
		public $isdebit;
		public $titlekey;
		public $account;	// Bank account of the company (creditor / ordering party)
		public $ics;
		public $addressee = array();	// Addressee of the unit (third party or parent company) : id, name, code, address, zip, town
		public $toparent = false;
		public $sameiban = true;	// All the lines of the PDF use the same bank account : shown in the frame, else under each line
		public $lines = array();	// Lines of the generation unit
		public $columns = array();	// Table columns : key => array('x', 'w', 'align', 'label')

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db		Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			parent::__construct($db);
			$langs->loadLangs(array('main', 'companies', 'bills', 'banks', 'withdrawals', 'infraspackplus@infraspackplus', 'infrasfiles@infrasfiles'));
			pdf_InfraSPlus_getValues($this);
			$this->db						= $db;
			$this->name						= $langs->trans('PDFInfraSPlusBonName');
			$this->description				= $langs->trans('PDFInfraSPlusBonDescription');
			$this->defaulttemplate			= getDolGlobalString('INFRASFILES_WIDTHDRAW_ADDON_PDF', '');
			$this->update_main_doc_field	= 0;	// Set to 1 by getValues() : the native table has no last_main_doc column, InfraSFiles stores it itself
			$this->line_height				= 6;
			$this->option_logo				= 1;	// Display logo
			$this->option_multilang			= 1;	// Available in several languages
			$this->option_freetext			= 1;	// Support add of a personalised text
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
			global $conf, $langs, $user, $hookmanager;

			if (!is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			$sav_charset_output	= $outputlangs->charset_output;
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'companies', 'bills', 'banks', 'withdrawals', 'salaries', 'infraspackplus@infraspackplus', 'infrasfiles@infrasfiles'));

			// Generation unit and lines (same data as the 'bordereau' model of InfraSFiles)
			$unit	= (is_array($moreparams) && !empty($moreparams['infrasfiles_unit'])) ? $moreparams['infrasfiles_unit'] : array('suffix' => '', 'label' => '', 'fk_soc' => 0, 'toparent' => false, 'addressee' => array(), 'lineids' => array());
			if (empty($object->infrasfiles_lines) && method_exists($object, 'infrasfilesFetchLines')) {
				$object->infrasfilesFetchLines();
			}
			$this->lines	= array();
			foreach ((array) $object->infrasfiles_lines as $line) {
				if (empty($unit['lineids']) || in_array($line['rowid'], $unit['lineids'])) {
					$this->lines[]	= $line;
				}
			}
			$this->isdebit	= ($object->type != 'bank-transfer');
			$this->titlekey	= $this->isdebit ? 'InfraSFilesPdfTitleDebit' : 'InfraSFilesPdfTitleTransfer';
			$this->toparent	= !empty($unit['toparent']);
			$ibans	= array();
			foreach ($this->lines as $line) {
				$ibans[(string) (!empty($line['rib']['iban']) ? $line['rib']['iban'] : '')]	= 1;
			}
			$this->sameiban	= (count($ibans) <= 1);

			// Output file (directory and base name from InfraSFiles, prefix / suffix from the InfraSPackPlus setup)
			$paths	= $this->infrasfilesGetFile($object, $unit, $outputlangs);
			if (!$paths) {
				return -1;
			}
			$paths	= $this->infrasplusApplyPrefix($paths);

			// Addressee (third party or parent company) loaded as a Societe : the InfraSPlus address helpers work on $object->thirdparty
			$this->infrasplusLoadAddressee($object, $unit);

			// Bank account of the company
			$this->account	= new Account($this->db);
			if (!empty($object->fk_bank_account)) {
				$this->account->fetch($object->fk_bank_account);
			}
			$this->ics	= $this->isdebit ? (!empty($this->account->ics) ? $this->account->ics : getDolGlobalString('PRELEVEMENT_ICS', '')) : (!empty($this->account->ics_transfer) ? $this->account->ics_transfer : getDolGlobalString('PAYMENTBYBANKTRANSFER_ICS', ''));

			// Hook beforePDFCreation : choices of the InfraSPackPlus options form (logo, addresses, mentions, notes, footer image, files, alias)
			$this->infrasfilesBefore($object, $outputlangs, $paths['file']);
			$res	= (is_object($hookmanager) && !empty($hookmanager->resArray) && is_array($hookmanager->resArray)) ? $hookmanager->resArray : array();
			$this->logo					= !empty($res['logo']) ? $res['logo'] : '';
			$this->adr					= !empty($res['adr']) ? $res['adr'] : '';
			$this->customerAddrSelect	= !empty($res['customerAddrSelect']) ? $res['customerAddrSelect'] : '';
			$this->listfreet			= !empty($res['listfreet']) ? $res['listfreet'] : '';
			$this->listnotep			= !empty($res['listnotep']) ? $res['listnotep'] : '';
			$this->pied					= !empty($res['pied']) ? $res['pied'] : getDolGlobalString('INFRASPLUS_PDF_IMAGE_FOOT', '');
			$this->files				= !empty($res['filesArray']) ? $res['filesArray'] : '';
			$this->include_alias		= !empty($res['includealias']) ? $res['includealias'] : '';

			// PDF instance (InfraS TCPDF / TCPDI : rounded rectangles, transactions, merge)
			$pdf				= pdf_InfraSPlus_getInstance($this->format, 'mm', 'P');
			$default_font_size	= pdf_getPDFFontSize($outputlangs);	// Must be after getInstance
			$pdf->SetAutoPageBreak(1, 0);
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont($this->font);
			$pdf->Open();
			$title	= $outputlangs->transnoentities($this->titlekey);
			$pdf->SetTitle($outputlangs->convToOutputCharset($title.' '.$object->ref));
			$pdf->SetSubject($outputlangs->convToOutputCharset($title));
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset(is_object($user) && !empty($user->id) ? $user->getFullName($outputlangs) : $this->emetteur->name));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($title.' '.$object->ref));
			if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
				$pdf->SetCompression(false);
			}
			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
			// Line styles of the InfraSPlus layout
			$this->stdLineStyle		= array('width' => 0.2, 'dash' => '0', 'cap' => 'butt', 'color' => array(128, 128, 128));
			$this->horLineStyle		= array('width' => $this->tblLineW, 'dash' => '0', 'cap' => 'butt', 'color' => $this->horLineColor);
			$this->signLineStyle	= array('width' => $this->signLineW, 'dash' => $this->signLineDash, 'cap' => 'butt', 'color' => $this->signLineColor);
			$this->tblLineStyle		= array('width' => $this->tblLineW, 'dash' => $this->tblLineDash, 'cap' => 'butt', 'color' => (!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
			$this->verLineStyle		= array('width' => $this->tblLineW, 'dash' => $this->tblLineDash, 'cap' => 'butt', 'color' => $this->verLineColor);
			$this->colpad			= ($this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0) + 1;	// horizontal padding (tied to border-radius)
			$this->line_height		= $this->lineSep_hight > 0 ? $this->lineSep_hight : 6;	// body row pitch from INFRASPLUS_PDF_LINESEP_HIGHT
			$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
			$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;	// header row height
			// Columns of the table : Document, Date, Due date, Third party (remaining width), Status, Amount
			$right		= $this->page_largeur - $this->marge_droite;
			$usable		= $right - $this->marge_gauche;
			$widths		= array('document' => 34, 'date' => 20, 'due' => 20, 'status' => 22, 'amount' => 30);
			$widths['third']	= $usable - array_sum($widths);
			$x	= $this->marge_gauche;
			foreach (array('document', 'date', 'due', 'third', 'status', 'amount') as $key) {
				$this->columns[$key]	= array('x' => $x, 'w' => $widths[$key], 'align' => in_array($key, array('date', 'due', 'status')) ? 'C' : ($key == 'amount' ? 'R' : 'L'));
				$x	+= $widths[$key];
			}
			$this->columns['document']['label']	= $outputlangs->transnoentities('InfraSFilesPdfColDocument');
			$this->columns['date']['label']		= $outputlangs->transnoentities('Date');
			$this->columns['due']['label']		= $outputlangs->transnoentities('InfraSFilesPdfColDueDate');
			$this->columns['third']['label']	= $outputlangs->transnoentities('ThirdParty');
			$this->columns['status']['label']	= $outputlangs->transnoentities('Status');
			$this->columns['amount']['label']	= $outputlangs->transnoentities('Amount');

			// First page
			$pdf->AddPage();
			$this->infrasplusWatermarks($pdf, $object, $outputlangs);
			$watermarkedPages	= array($pdf->getPage() => true);
			// Reserved heights at the bottom of the (last) page : footer, mentions, signature band, total band
			$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
			$heightforfreetext	= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 1, 1, $this->horLineStyle);
			$signband			= $this->ht_signarea + 8;	// label + box + margins
			$nbrejected			= 0;
			foreach ($this->lines as $line) {
				if ((int) $line['statut'] == 3) {
					$nbrejected++;
				}
			}
			$totalband			= 12 + ($nbrejected ? 6 : 0);
			$tab_bottom			= $this->page_hauteur - $heightforfooter - $heightforfreetext - $signband - $totalband;
			$this->tab_top		= $this->_pagehead($pdf, $object, $outputlangs, 1);
			// Notes of the InfraSPackPlus dictionary chosen in the options form (under the header, like the other InfraSPlus models)
			$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $this->tab_top, $usable - 2 * $this->colpad, $this->tab_hl, $this->marge_gauche + $this->colpad, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $usable, $this->tblLineStyle, -1, 0);
			$this->tab_top	+= $height_note;
			$hdrh			= empty($this->hide_top_table) ? $this->ht_top_table : 0;
			$curY			= $this->tab_top + $hdrh + 1;	// first data row

			// Body : one row per document of each line, credit notes under the document, rejected lines in red
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			$total			= 0;
			$rejected		= 0;
			$nb				= 0;
			$statuslabels	= array(0 => 'StatusWaiting', 2 => ($this->isdebit ? 'StatusDebited' : 'StatusCredited'), 3 => 'StatusRefused');	// LignePrelevement::LibStatut()
			foreach ($this->lines as $line) {
				$total		+= $line['amount'];
				$isrejected	= ((int) $line['statut'] == 3);
				if ($isrejected) {
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
				$lineiban	= (!$this->sameiban && !empty($line['rib']['iban'])) ? $line['rib']['iban'] : '';
				$statustxt	= isset($statuslabels[(int) $line['statut']]) ? $outputlangs->transnoentities($statuslabels[(int) $line['statut']]) : '';
				foreach ($rows as $row) {
					// Row height : the reference, the third party (+ IBAN) may wrap ; one extra small line per credit note
					$h_ref		= $pdf->getStringHeight($this->columns['document']['w'] - $this->colpad, $outputlangs->convToOutputCharset($row['ref']));
					$h_name		= max($pdf->getStringHeight($this->columns['third']['w'] - $this->colpad, $outputlangs->convToOutputCharset($line['name'])), $this->line_height);	// name cell (min. one row)
					$h_third	= $h_name + ($lineiban !== '' ? 3 : 0);	// + one small line for the IBAN under the name
					$rowh		= max($h_ref, $h_third, $this->line_height) + 4 * count($row['credits']);
					// Page break : close the table on this page, open a new one
					if (($curY + $rowh) > $tab_bottom) {
						$this->_tableau($pdf, $object, $this->tab_top, $curY - $this->tab_top, $outputlangs);
						$pdf->AddPage();
						if (empty($watermarkedPages[$pdf->getPage()])) {
							$this->infrasplusWatermarks($pdf, $object, $outputlangs);
						}
						$watermarkedPages[$pdf->getPage()]	= true;
						$this->tab_top	= $this->_pagehead($pdf, $object, $outputlangs, 0);
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						$pdf->SetFont('', '', $default_font_size - 1);
						$curY	= $this->tab_top + $hdrh + 1;
					}
					if ($isrejected) {
						$pdf->SetTextColor(200, 0, 0);
					}
					$pdf->MultiCell($this->columns['document']['w'] - $this->colpad, $this->line_height, $outputlangs->convToOutputCharset($row['ref']), '', 'L', 0, 1, $this->columns['document']['x'] + $this->colpad, $curY, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($this->columns['date']['w'], $this->line_height, $row['date'], '', 'C', 0, 1, $this->columns['date']['x'], $curY, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($this->columns['due']['w'], $this->line_height, $row['due'], '', 'C', 0, 1, $this->columns['due']['x'], $curY, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($this->columns['third']['w'] - $this->colpad, $this->line_height, $outputlangs->convToOutputCharset($line['name']), '', 'L', 0, 1, $this->columns['third']['x'] + $this->colpad, $curY, true, 0, 0, false, 0, 'M', false);
					if ($lineiban !== '') {
						$pdf->SetFont('', '', $default_font_size - 3);
						$pdf->MultiCell($this->columns['third']['w'] - $this->colpad, 3, $outputlangs->convToOutputCharset($lineiban), '', 'L', 0, 1, $this->columns['third']['x'] + $this->colpad, $curY + $h_name, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
					}
					$pdf->MultiCell($this->columns['status']['w'], $this->line_height, $outputlangs->convToOutputCharset($statustxt), '', 'C', 0, 1, $this->columns['status']['x'], $curY, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($this->columns['amount']['w'] - $this->colpad, $this->line_height, price($row['amount'], 0, $outputlangs, 1, -1, -1, $conf->currency), '', 'R', 0, 1, $this->columns['amount']['x'], $curY, true, 0, 0, false, 0, 'M', false);
					$nextY	= $curY + max($h_ref, $h_third, $this->line_height);
					// Credit notes applied on the document : reference and amount deducted, smaller and grey, under the document
					if (!empty($row['credits'])) {
						$pdf->SetFont('', 'I', $default_font_size - 2);
						$pdf->SetTextColor(100, 100, 100);
						foreach ($row['credits'] as $credit) {
							$pdf->MultiCell($this->columns['document']['w'] + $this->columns['date']['w'] + $this->columns['due']['w'] - $this->colpad - 3, 4, $outputlangs->convToOutputCharset($outputlangs->transnoentities('CreditNote').' '.$credit['ref']), '', 'L', 0, 1, $this->columns['document']['x'] + $this->colpad + 3, $nextY, true, 0, 0, false, 0, 'M', false);
							$pdf->MultiCell($this->columns['amount']['w'] - $this->colpad, 4, price(-1 * abs($credit['amount']), 0, $outputlangs, 1, -1, -1, $conf->currency), '', 'R', 0, 1, $this->columns['amount']['x'], $nextY, true, 0, 0, false, 0, 'M', false);
							$nextY	+= 4;
						}
						$pdf->SetFont('', '', $default_font_size - 1);
					}
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$curY	= $nextY;
					$nb++;
					// Row separator when dash between lines is enabled (MAIN_PDF_DASH_BETWEEN_LINES)
					if (!empty($this->dash_between_line)) {
						$pdf->Line($this->marge_gauche, $curY, $right, $curY, $this->horLineStyle);
					}
				}
			}
			// Table dressing of the last page (frame height fits the content)
			$this->_tableau($pdf, $object, $this->tab_top, $curY - $this->tab_top, $outputlangs);
			// Total band : number of lines on the left, total on the right (and rejected lines apart, in red)
			$cntop	= $curY + 3;
			$cnth	= 7;
			$totx	= $this->marge_gauche + (int) round($usable * 0.62);
			$pdf->RoundedRect($this->marge_gauche, $cntop, $usable, $cnth, $this->Rounded_rect, '1111', '', $this->stdLineStyle);
			$pdf->Line($totx, $cntop, $totx, $cntop + $cnth, $this->stdLineStyle);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($totx - $this->marge_gauche - 4, 5, $outputlangs->transnoentities('InfraSFilesPdfNbLines', $nb), '', 'L', 0, 1, $this->marge_gauche + 2, $cntop + 1, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(30, 5, $outputlangs->transnoentities('Total'), '', 'L', 0, 1, $totx + 2, $cntop + 1, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($right - ($totx + 32) - 1, 5, price($total, 0, $outputlangs, 1, -1, -1, $conf->currency), '', 'R', 0, 1, $totx + 32, $cntop + 1, true, 0, 0, false, 0, 'M', false);
			if ($nbrejected) {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell($usable - 4, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('InfraSFilesPdfRejected', $nbrejected, price($rejected, 0, $outputlangs, 1, -1, -1, $conf->currency))), '', 'R', 0, 1, $this->marge_gauche + 2, $cntop + $cnth + 1, true, 0, 0, false, 0, 'M', false);
			}
			// Bottom of the last page : signature area (right), then mentions above the footer, then footer
			$posyfoot	= $this->page_hauteur - $heightforfooter;
			$posysign	= $posyfoot - $heightforfreetext - $signband;
			$larg_signarea	= (int) round($usable * 0.40);
			$this->_signature_area($pdf, $object, $outputlangs, $right - $larg_signarea, $posysign, $larg_signarea);
			pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posyfoot - $heightforfreetext, $outputlangs, $this->emetteur, $this->listfreet, 1, 0, $this->horLineStyle);
			$this->_pagefoot($pdf, $object, $outputlangs, 0);
			// Files chosen in the options form, merged after the document
			if (!empty($this->files) && $this->files != 'none') {
				pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
			}
			$result	= $this->infrasfilesFinish($pdf, $paths, $object, $outputlangs);
			$outputlangs->charset_output	= $sav_charset_output;
			return $result;
		}

		/**
		*	Apply the InfraSPackPlus prefix / suffix rules to the file name computed by InfraSFiles
		*	(option "prefix of the models", constant INFRASPLUS_PDF_ADD_PREFIX_TO_Bon, and "_Bon" suffix in multi-files mode)
		*
		*	@param		array		$paths		array('dir', 'file', 'relative') from infrasfilesGetFile()
		*	@return		array					Same array with the final file name
		**/
		protected function infrasplusApplyPrefix($paths)
		{
			$fileprefix	= '';
			if (!getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME')) {
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_Bon') ? '' : '_Bon';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_ADD_PREFIX_TO_Bon', '');
				$filesufixe	= empty($fileprefix) ? '_Bon' : '';
			}
			if ($fileprefix === '' && $filesufixe === '') {
				return $paths;
			}
			$basename	= preg_replace('/\.pdf$/i', '', basename($paths['file']));
			$newname	= $fileprefix.$basename.$filesufixe.'.pdf';
			$paths['file']		= $paths['dir'].'/'.$newname;
			$paths['relative']	= preg_replace('/[^\/]+$/', $newname, $paths['relative']);
			return $paths;
		}

		/**
		*	Load the addressee of the unit (third party or parent company) into $this->addressee and $object->thirdparty (Societe),
		*	so that the InfraSPlus address helpers (multi addresses, alias, professional ids) apply to it
		*
		*	@param		InfrasFilesWithdraw	$object		Object
		*	@param		array				$unit		Generation unit (keys 'fk_soc', 'addressee')
		*	@return		void
		**/
		protected function infrasplusLoadAddressee(&$object, $unit)
		{
			global $conf;

			$this->addressee	= !empty($unit['addressee']) ? $unit['addressee'] : array();
			if (empty($this->addressee) && !empty($this->lines)) {
				$first	= $this->lines[0];
				$this->addressee	= array('id' => (int) $first['fk_soc'], 'name' => $first['name'], 'code' => '', 'address' => $first['address'], 'zip' => $first['zip'], 'town' => $first['town']);
			}
			$soc	= new Societe($this->db);
			if (empty($object->specimen) && !empty($this->addressee['id']) && $soc->fetch((int) $this->addressee['id']) > 0) {
				$object->thirdparty	= $soc;
				return;
			}
			// Specimen or third party not found : minimal Societe built from the addressee data
			$soc->id			= 0;
			$soc->name			= isset($this->addressee['name']) ? $this->addressee['name'] : '';
			$soc->address		= isset($this->addressee['address']) ? $this->addressee['address'] : '';
			$soc->zip			= isset($this->addressee['zip']) ? $this->addressee['zip'] : '';
			$soc->town			= isset($this->addressee['town']) ? $this->addressee['town'] : '';
			$soc->code_client	= isset($this->addressee['code']) ? $this->addressee['code'] : '';
			$soc->country_code	= $this->emetteur->country_code;
			$soc->country_id	= $this->emetteur->country_id;
			$soc->entity		= $conf->entity;
			$object->thirdparty	= $soc;
		}

		/**
		*	Watermarks of the page : InfraSPackPlus background image / text, and the draft watermark option of InfraSFiles
		*
		*	@param		TCPDF				$pdf			PDF instance
		*	@param		InfrasFilesWithdraw	$object			Object
		*	@param		Translate			$outputlangs	Output language
		*	@return		void
		**/
		protected function infrasplusWatermarks(&$pdf, $object, $outputlangs)
		{
			global $conf;

			pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, !empty($object->entity) ? $object->entity : $conf->entity, $outputlangs);
			$isdraft	= (isset($object->statut) && (int) $object->statut == 0) || (isset($object->status) && (int) $object->status == 0);
			$watermark	= infrasfiles_get_option('widthdraw', 'WATERMARK');
			if ($isdraft && $watermark !== '') {
				pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $watermark);
			}
		}

		/**
		*	Page head : logo, title with reference / dates / status on the right, then (first page) the InfraSPlus address frames
		*	(company on the left with its bank account, addressee on the right with its parent company and bank account)
		*
		*	@param		TCPDF				$pdf			PDF instance
		*	@param		InfrasFilesWithdraw	$object			Object
		*	@param		Translate			$outputlangs	Output language
		*	@param		int					$showaddress	1 = print the address frames (first page)
		*	@return		float								Top position of the table
		**/
		protected function _pagehead(&$pdf, $object, $outputlangs, $showaddress)
		{
			global $conf;

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$entity				= !empty($object->entity) ? $object->entity : $conf->entity;
			$dimCadres			= array('S' => ($this->page_largeur - ($this->marge_gauche + 6 + $this->left_recep_corner + $this->marge_droite)), 'R' => $this->left_recep_corner);
			$w					= $this->header_align_left ? 92 - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $entity);
			$heightLogo			+= $posy + $this->tab_hl;
			// Title
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$pdf->MultiCell($w, $this->tab_hl * $this->title_size, $outputlangs->transnoentities($this->titlekey), '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	= $pdf->getY();
			// Reference, dates, status
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl;
			$pdf->SetFont('', (!empty($this->datesbold) ? 'B' : ''), $default_font_size - 2);
			$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('Date').' : '.dol_print_date($object->datec, 'day', false, $outputlangs, true), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl - 0.5;
			if (!empty($object->date_trans)) {
				$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('TransData').' : '.dol_print_date($object->date_trans, 'day', false, $outputlangs, true), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$posy	+= $this->tab_hl - 0.5;
			}
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('Status').' : '.$outputlangs->convToOutputCharset($object->LibStatut($object->statut, 0)), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl;
			$dimCadres['Y']	= !empty($this->use_iso_location) && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl);
			if (empty($showaddress)) {
				return $dimCadres['Y'];
			}
			// Address frames : sender (with the sender address chosen in the options) and addressee ($object->thirdparty), InfraSPlus helpers
			$addresses	= pdf_InfraSPlus_getAddresses($object, $outputlangs, array('I' => array(), 'E' => array()), $this->adr, '', $this->emetteur, 0, 'accountStatus', null, 0, -2, -2, $this->customerAddrSelect, $this->include_alias);
			// Bank account of the company under its address
			$bank	= array();
			if (!empty($this->account->label)) {
				$bank[]	= $outputlangs->transnoentities('BankAccount').' : '.$this->account->label;
			}
			if (!empty($this->account->iban)) {
				$bank[]	= $outputlangs->transnoentities('IBAN').' : '.$this->account->iban;
			}
			if (!empty($this->account->bic)) {
				$bank[]	= $outputlangs->transnoentities('BIC').' : '.$this->account->bic;
			}
			if (!empty($this->ics)) {
				$bank[]	= $outputlangs->transnoentities('ICS').' : '.$this->ics;
			}
			if (!empty($bank)) {
				$addresses['carac_emetteur']	= rtrim((string) $addresses['carac_emetteur'], "\n")."\n".$outputlangs->convToOutputCharset(implode("\n", $bank));
			}
			// Parent company (for information, when the PDF is not addressed to it) and bank account of the addressee (when all lines share it)
			$more	= array();
			if (!$this->toparent && !empty($this->lines[0]['parent']['name'])) {
				$more[]	= $outputlangs->transnoentities('ParentCompany').' : '.$this->lines[0]['parent']['name'];
			}
			if ($this->sameiban && !empty($this->lines)) {
				$rib	= $this->lines[0]['rib'];
				if (!empty($rib['iban'])) {
					$more[]	= $outputlangs->transnoentities('IBAN').' : '.$rib['iban'];
				}
				if (!empty($rib['bic'])) {
					$more[]	= $outputlangs->transnoentities('BIC').' : '.$rib['bic'];
				}
				if ($this->isdebit && !empty($rib['rum'])) {
					$more[]	= $outputlangs->transnoentities('RUM').' : '.$rib['rum'];
				}
			}
			if (!empty($more)) {
				$addresses['carac_client']	= rtrim((string) $addresses['carac_client'], "\n")."\n".$outputlangs->convToOutputCharset(implode("\n", $more));
			}
			$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
			return $dimCadres['Y'] + $hauteurcadre + $this->tab_hl + 2;
		}

		/**
		*	Draw the table dressing (frame, header background, column titles, separators) for the current page,
		*	once the used height is known so the frame fits the content
		*
		*	@param		TCPDF				$pdf			PDF instance
		*	@param		InfrasFilesWithdraw	$object			Object
		*	@param		float				$tab_top		Top position of the table
		*	@param		float				$tab_height		Height of the table on this page
		*	@param		Translate			$outputlangs	Output language
		*	@return		void
		**/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs)
		{
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$right				= $this->page_largeur - $this->marge_droite;
			$usable				= $right - $this->marge_gauche;
			$hdrh				= $this->ht_top_table;
			$tab_bottom			= $tab_top + $tab_height;
			// Header row background
			if (empty($this->hide_top_table) && !empty($this->title_bg)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $usable, $hdrh, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
			}
			// Outer frame, height fits the content
			if (!empty($this->showtblline)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $usable, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			}
			// Header separator
			if (empty($this->hide_top_table)) {
				$pdf->Line($this->marge_gauche, $tab_top + $hdrh, $right, $tab_top + $hdrh, $this->horLineStyle);
			}
			// Column separators
			if (!empty($this->showverline)) {
				foreach ($this->columns as $key => $column) {
					if ($key != 'document') {
						$pdf->Line($column['x'], $tab_top, $column['x'], $tab_bottom, $this->verLineStyle);
					}
				}
			}
			// Column titles
			if (empty($this->hide_top_table)) {
				if (!empty($this->title_bg)) {
					$pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]);
				} else {
					$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
				}
				$pdf->SetFont('', 'B', $default_font_size - 2);
				foreach ($this->columns as $key => $column) {
					$pad	= in_array($key, array('document', 'third')) ? $this->colpad : 0;
					$pdf->MultiCell($column['w'] - $pad, $this->tab_hl, $outputlangs->convToOutputCharset($column['label']), '', $column['align'], 0, 1, $column['x'] + $pad, $tab_top + 1, true, 0, 0, false, 0, 'M', false);
				}
			}
		}

		/**
		*	Signature area at the bottom of the document (with the electronic signature appearance when enabled)
		*
		*	@param		TCPDF				$pdf			PDF instance
		*	@param		InfrasFilesWithdraw	$object			Object
		*	@param		Translate			$outputlangs	Output language
		*	@param		float				$posx			Left position
		*	@param		float				$posy			Top position
		*	@param		float				$larg			Width
		*	@return		void
		**/
		protected function _signature_area(&$pdf, $object, $outputlangs, $posx, $posy, $larg)
		{
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($larg, 4, $outputlangs->transnoentities('Signature'), '', 'L', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$boxy	= $pdf->getY() + 1;
			$pdf->RoundedRect($posx, $boxy, $larg, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			if (!empty($this->e_signing)) {
				if (!isModEnabled('uptosign') && method_exists($pdf, 'addEmptySignatureAppearance')) {
					$pdf->addEmptySignatureAppearance($posx, $boxy, $larg, $this->ht_signarea);
				} else {
					pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posx, $boxy, $larg, $this->ht_signarea);
				}
			}
		}

		/**
		*	Page footer of InfraSPackPlus (company details, footer image, page number)
		*
		*	@param		TCPDF				$pdf			PDF instance
		*	@param		InfrasFilesWithdraw	$object			Object
		*	@param		Translate			$outputlangs	Output language
		*	@param		int					$calculseul		1 = only compute the height
		*	@return		int									Height of the footer
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul = 0)
		{
			global $conf;

			$showdetails	= $this->type_foot.(!empty($this->pied) ? 1 : 0);
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, !empty($object->entity) ? $object->entity : $conf->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
