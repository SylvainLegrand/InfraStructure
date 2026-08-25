<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/modules/cheque/doc/pdf_InfraSPlus_BC.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF cheque deposit receipt (bordereau de remise de chèques)
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF cheque deposit receipt InfraS
	************************************************/
	class pdf_InfraSPlus_BC extends ModeleChequeReceipts
	{
		public $db;
		public $name;
		public $description;
		public $update_main_doc_field;
		public $type;
		public $emetteur;
		public $defaulttemplate;
		public $use_fpdf;
		public $page_largeur;
		public $page_hauteur;
		public $decal_round;
		public $format;
		public $marge_gauche;
		public $marge_haute;
		public $marge_droite;
		public $formatpage;
		public $dash_between_line;
		public $multi_files;
		public $font;
		public $headertxtcolor;
		public $header_align_left;
		public $bodytxtcolor;
		public $title_size;
		public $ht_top_table;
		public $height_top_table;
		public $hide_top_table;
		public $Rounded_rect;
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
		public $option_logo;
		public $option_multilang;
		public $option_freetext;
		public $ref_ext;
		public $tab_top;
		public $line_height;
		public $line_per_page;
		public $stdLineStyle = [];
		public $horLineStyle = [];
		public $signLineStyle = [];
		public $tblLineStyle = [];
		public $verLineStyle = [];
		public $tab_hl = 4;
		public $colpad;
		public $logo;
		public $listfreet;
		public $pied;
		public $posx_idx;
		public $posx_num;
		public $posx_bank;
		public $posx_emet;
		public $posx_amount;
		public $larg_idx;
		public $larg_num;
		public $larg_bank;
		public $larg_emet;
		public $larg_amount;
		public $account;	// Bank account object, loaded in write_file() for document generation
		public $lines = array();	// Cheque lines (stdClass: bank_chq, emetteur_chq, amount_chq, num_chq), loaded in write_file()

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'compta', 'bills', 'banks', 'companies', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db						= $db;
			$this->name						= $langs->trans('PDFInfraSPlusChequeReceiptName');
			$this->description				= $langs->trans('PDFInfraSPlusChequeReceiptDescription');
			$this->defaulttemplate			= getDolGlobalString('CHEQUERECEIPT_ADDON_PDF', '');
			$this->type						= 'pdf';
			$this->update_main_doc_field	= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->line_height				= 6;
			$this->option_logo				= 1;	// Display logo
			$this->option_multilang			= 1;	// Available in several languages
			$this->option_freetext			= 1;	// Support add of a personalised text
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		RemiseCheque	$object			Object RemiseCheque
		*	@param		string			$_dir			Directory (unused, path is computed from $object)
		*	@param		string			$number			Number (unused, path is computed from $object)
		*	@param		Translate		$outputlangs	Lang output object
		*	@return		int<-1,1>						1 if OK, <=0 if KO
		**/
		public function write_file($object, $_dir, $number, $outputlangs)
		{
			global $user, $conf, $langs, $hookmanager, $action;

			if (!is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			$sav_charset_output	= $outputlangs->charset_output;
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'compta', 'bills', 'banks', 'companies', 'infraspackplus@infraspackplus'));
			$this->account	= new Account($this->db);
			if (!empty($object->account_id)) {
				$this->account->fetch($object->account_id);
			}
			// Cheque lines : specimen uses fixed demo data, real records are read from llx_bank
			$this->lines	= array();
			if (!empty($object->specimen)) {
				$line1					= new stdClass();
				$line1->bank_chq		= 'Banque Exemple';
				$line1->emetteur_chq	= 'Jean Dupont';
				$line1->amount_chq		= 150.00;
				$line1->num_chq			= '1234567';
				$line2					= new stdClass();
				$line2->bank_chq		= 'Crédit Agricole';
				$line2->emetteur_chq	= 'Marie Martin';
				$line2->amount_chq		= 250.00;
				$line2->num_chq			= '7654321';
				$this->lines			= array($line1, $line2);
				$object->nbcheque		= count($this->lines);
				$object->amount			= $line1->amount_chq + $line2->amount_chq;
			} elseif (!empty($object->id)) {
				$sql = "SELECT b.banque, b.emetteur, b.amount, b.num_chq";
				$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."bordereau_cheque as bc ON b.fk_bordereau = bc.rowid";
				$sql .= " WHERE bc.rowid = ".((int) $object->id);
				$sql .= " AND bc.entity = ".((int) $conf->entity);
				$sql .= " ORDER BY b.dateo ASC, b.rowid ASC";
				$resql = $this->db->query($sql);
				if ($resql) {
					while ($objp = $this->db->fetch_object($resql)) {
						$line				= new stdClass();
						$line->bank_chq		= $objp->banque;
						$line->emetteur_chq	= $objp->emetteur;
						$line->amount_chq	= $objp->amount;
						$line->num_chq		= $objp->num_chq;
						$this->lines[]		= $line;
					}
					$this->db->free($resql);
				}
			}
			$number			= $object->ref;
			$fileprefix		= '';
			if (!getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME')) {
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_BC') ? '' : '_BC';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_ADD_PREFIX_TO_BC', '');
				$filesufixe	= empty($fileprefix) ? '_BC' : '';
			}
			$entity		= !empty($object->entity) ? $object->entity : $conf->entity;
			$baseDir	= (!empty($conf->bank->multidir_output[$entity]) ? $conf->bank->multidir_output[$entity] : $conf->bank->dir_output).'/checkdeposits';
			// Definition of $dir and $file
			if (!empty($object->specimen)) {
				$dir	= $baseDir;
				$file	= $dir.'/SPECIMEN'.$fileprefix.$filesufixe.'.pdf';
			} else {
				$objectref	= dol_sanitizeFileName($object->ref);
				$dir		= $baseDir.'/'.$objectref;
				$file		= $dir.'/'.$fileprefix.$objectref.$filesufixe.'.pdf';
			}
			if (!is_dir($dir) && dol_mkdir($dir) < 0) {
				$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
				return -1;
			}
			// Add pdfgeneration hook
			if (! is_object($hookmanager)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager	= new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters			= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			$reshook			= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			$this->logo			= !empty($hookmanager->resArray['logo']) ? $hookmanager->resArray['logo'] : '';
			$this->listfreet	= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
			// Footer image : value from the hook, else fall back to the infraspackplus global constant
			$this->pied			= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : getDolGlobalString('INFRASPLUS_PDF_IMAGE_FOOT', '');
			// Create pdf instance
			$pdf				= pdf_InfraSPlus_getInstance($this->format, 'mm', 'P');
			$default_font_size	= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
			$pdf->SetAutoPageBreak(1, 0);
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont($this->font);
			$pdf->Open();
			$pdf->SetTitle($outputlangs->transnoentities('CheckReceipt').' '.$number);
			$pdf->SetSubject($outputlangs->transnoentities('CheckReceipt'));
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
			$pdf->SetKeyWords($outputlangs->transnoentities('CheckReceipt').' '.$number);
			if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
				$pdf->SetCompression(false);
			}
			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
			// Default PDF line styles
			$this->stdLineStyle		= array('width' => 0.2, 'dash' => '0', 'cap' => 'butt', 'color' => array(128, 128, 128));
			$this->horLineStyle		= array('width' => $this->tblLineW, 'dash' => '0', 'cap' => 'butt', 'color' => $this->horLineColor);
			$this->signLineStyle	= array('width' => $this->signLineW, 'dash' => $this->signLineDash, 'cap' => 'butt', 'color' => $this->signLineColor);
			$this->tblLineStyle		= array('width' => $this->tblLineW, 'dash' => $this->tblLineDash, 'cap' => 'butt', 'color' => (!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
			$this->verLineStyle		= array('width' => $this->tblLineW, 'dash' => $this->tblLineDash, 'cap' => 'butt', 'color' => $this->verLineColor);
			$this->colpad			= ($this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0) + 1;	// horizontal padding (tied to border-radius)
			$this->line_height		= $this->lineSep_hight > 0 ? $this->lineSep_hight : 6;	// body row pitch from INFRASPLUS_PDF_LINESEP_HIGHT
			$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
			$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;	// header row height (INFRASPLUS_PDF_HEIGHT_TOP_TABLE)
			// Define columns of the cheques table
			$right					= $this->page_largeur - $this->marge_droite;
			$usable					= $right - $this->marge_gauche;
			$this->larg_idx			= 10;
			$this->larg_num			= 30;
			$this->larg_bank		= 50;
			$this->larg_amount		= 28;
			$this->larg_emet		= $usable - ($this->larg_idx + $this->larg_num + $this->larg_bank + $this->larg_amount);
			$this->posx_idx			= $this->marge_gauche;
			$this->posx_num			= $this->posx_idx + $this->larg_idx;
			$this->posx_bank		= $this->posx_num + $this->larg_num;
			$this->posx_emet		= $this->posx_bank + $this->larg_bank;
			$this->posx_amount		= $right - $this->larg_amount;
			// New page
			$pdf->AddPage();
			pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
			$watermarkedPages		= array($pdf->getPage() => true);
			$pagenb					= 1;
			// Compute footer height, signature band and table boundaries
			$heightforfooter		= $this->_pagefoot($pdf, $object, $outputlangs, 1);
			$larg_signarea			= (int) round($usable * 0.40);
			$signband				= $this->ht_signarea + 8;	// label + box + margins
			$tab_bottom				= $this->page_hauteur - $heightforfooter - $signband;
			$this->tab_top			= $this->_pagehead($pdf, $object, $number, $outputlangs, $tab_bottom);
			$datatop				= $this->tab_top + (empty($this->hide_top_table) ? $this->ht_top_table : 0) + 1;	// first data row
			// Body : loop on cheque lines (table dressing drawn per page by _tableau() once the used height is known)
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			$nboflines		= count($this->lines);
			$curY			= $datatop;
			for ($j = 0; $j < $nboflines; $j++) {
				// Dynamic line height computation (bank / transmitter may wrap)
				$h_bank		= $pdf->getStringHeight($this->larg_bank - $this->colpad, $outputlangs->convToOutputCharset($this->lines[$j]->bank_chq));
				$h_emet		= $pdf->getStringHeight($this->larg_emet - $this->colpad, $outputlangs->convToOutputCharset($this->lines[$j]->emetteur_chq));
				$max_h		= max($h_bank, $h_emet, $this->line_height);
				$nb_lines	= $max_h > $this->line_height ? ((int) floor($max_h / $this->line_height) + 1) : 1;
				$rowh		= $this->line_height * $nb_lines;
				// Page break : not enough room → close the table on this page then start a new one
				if (($curY + $rowh) > $tab_bottom) {
					$this->_tableau($pdf, $object, $this->tab_top, $curY - $this->tab_top, $outputlangs);
					$pagenb++;
					$pdf->AddPage();
					if (empty($watermarkedPages[$pdf->getPage()])) {
						pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
					}
					$watermarkedPages[$pdf->getPage()]	= true;
					$this->_pagehead($pdf, $object, $number, $outputlangs, $tab_bottom);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					$curY	= $datatop;
				}
				// Body cells : text color from bodytxtcolor, horizontal padding from colpad
				$pdf->MultiCell($this->larg_idx, $this->line_height, (string) ($j + 1), '', 'C', 0, 1, $this->posx_idx, $curY, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_num - $this->colpad, $this->line_height, !empty($this->lines[$j]->num_chq) ? $this->lines[$j]->num_chq : '', '', 'L', 0, 1, $this->posx_num + $this->colpad, $curY, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_bank - $this->colpad, $this->line_height, $outputlangs->convToOutputCharset($this->lines[$j]->bank_chq), '', 'L', 0, 1, $this->posx_bank + $this->colpad, $curY, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_emet - $this->colpad, $this->line_height, $outputlangs->convToOutputCharset($this->lines[$j]->emetteur_chq), '', 'L', 0, 1, $this->posx_emet + $this->colpad, $curY, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_amount - $this->colpad, $this->line_height, price($this->lines[$j]->amount_chq, 0, $outputlangs, 1, -1, -1, $conf->currency), '', 'R', 0, 1, $this->posx_amount, $curY, true, 0, 0, false, 0, 'M', false);
				$curY	+= $rowh;
				// Row separator (body border) when dash between lines is enabled (MAIN_PDF_DASH_BETWEEN_LINES)
				if (!empty($this->dash_between_line) && $j < ($nboflines - 1)) {
					$pdf->Line($this->marge_gauche, $curY, $this->page_largeur - $this->marge_droite, $curY, $this->horLineStyle);
				}
			}
			// Table dressing for the last page (frame height fits the content)
			$this->_tableau($pdf, $object, $this->tab_top, $curY, $outputlangs);
			// Signature area at the bottom of the (last) page
			$this->_signature_area($pdf, $object, $outputlangs, $right - $larg_signarea, $tab_bottom, $larg_signarea);
			// Footer
			$this->_pagefoot($pdf, $object, $outputlangs, 0);
			if (method_exists($pdf, 'AliasNbPages')) {
				$pdf->AliasNbPages();
			}
			$pdf->Close();
			$pdf->Output($file, 'F');
			// Add pdfgeneration hook
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters			= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			$reshook			= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$this->error	= $hookmanager->error;
				$this->errors	= $hookmanager->errors;
			}
			dolChmod($file);
			$this->result					= array('fullpath' => $file);
			$outputlangs->charset_output	= $sav_charset_output;
			return 1;
		}

		/**
		*	Show top header of page : logo, title, deposit info frame (right), count/total frame and table column titles
		*
		*	@param		TCPDF			$pdf			Object PDF
		*	@param		RemiseCheque	$object			Object to show
		*	@param		string			$number			Number (ref of the bordereau)
		*	@param		Translate		$outputlangs	Object lang for output
		*	@param		float			$tab_bottom		Bottom position of the cheques table
		*	@return		float							Top position of the cheques table
		**/
		protected function _pagehead(&$pdf, $object, $number, $outputlangs, $tab_bottom)
		{
			global $conf;

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$right				= $this->page_largeur - $this->marge_droite;
			$usable				= $right - $this->marge_gauche;
			$ml					= $this->marge_gauche;
			$posy				= $this->marge_haute;
			// Deposit information frame on the RIGHT (Ref / Date / Owner / Bank account)
			$infoboxw			= (int) round($usable * 0.46);
			$infox				= $right - $infoboxw;
			$wlogo				= $this->header_align_left ? 92 - $this->decal_round : 100;
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $wlogo, $this->logo, $this->emetteur, $ml, $this->tab_hl, $this->headertxtcolor, $object->entity);
			$heightLogo			+= $posy + $this->tab_hl;
			// Title
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 4);
			$pdf->MultiCell($infoboxw, 8, $outputlangs->transnoentities('CheckReceipt'), '', 'R', 0, 1, $infox, $posy, true, 0, 0, false, 0, 'M', false);
			$titlebottom	= $pdf->getY();
			// Deposit info stacked just below the title (MRP style : "Label : value", right-aligned, no frame)
			$pdf->SetFont('', '', $default_font_size - 1);
			$infoy	= $titlebottom;
			$pdf->MultiCell($infoboxw, $this->tab_hl, $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref.(!empty($object->ref_ext) ? ' - '.$object->ref_ext : '')), '', 'R', 0, 1, $infox, $infoy, true, 0, 0, false, 0, 'M', false);
			$infoy	+= $this->tab_hl;
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->MultiCell($infoboxw, $this->tab_hl, $outputlangs->transnoentities('Date').' : '.dol_print_date($object->date_bordereau, 'day', false, $outputlangs), '', 'R', 0, 1, $infox, $infoy, true, 0, 0, false, 0, 'M', false);
			$infoy	+= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			if (!empty($this->account->owner_name)) {
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell($infoboxw, $this->tab_hl, $outputlangs->transnoentities('Owner').' : '.$outputlangs->convToOutputCharset($this->account->owner_name), '', 'R', 0, 1, $infox, $infoy, true, 0, 0, false, 0, 'M', false);
				$infoy	+= $this->tab_hl;
			}
			$bankval	= !empty($this->account->label) ? $this->account->label : '';
			if (!empty($this->account->iban)) {
				$bankval	.= (!empty($bankval) ? ' - ' : '').$this->account->iban;
			} elseif (!empty($this->account->number)) {
				$bankval	.= (!empty($bankval) ? ' - ' : '').$this->account->number;
			}
			$pdf->MultiCell($infoboxw, $this->tab_hl, $outputlangs->transnoentities('BankAccount').' : '.$outputlangs->convToOutputCharset($bankval), '', 'R', 0, 1, $infox, $infoy, true, 0, 0, false, 0, 'M', false);
			$infoy	+= $this->tab_hl;
			// Number of cheques / Total frame (full width, below header)
			$headbottom	= max($infoy, $posy + $heightLogo);
			$cntop		= $headbottom + 6;
			$cnth		= 7;
			$totx		= $ml + (int) round($usable * 0.68);
			$pdf->RoundedRect($ml, $cntop, $usable, $cnth, $this->Rounded_rect, '1111', '', $this->stdLineStyle);
			$pdf->Line($totx, $cntop, $totx, $cntop + $cnth, $this->stdLineStyle);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell($totx - $ml - 40, 5, $outputlangs->transnoentities('NumberOfCheques'), '', 'L', 0, 1, $ml + 2, $cntop + 1, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(35, 5, (string) $object->nbcheque, '', 'L', 0, 1, $totx - 40, $cntop + 1, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size);
			$pdf->MultiCell(25, 5, $outputlangs->transnoentities('Total'), '', 'L', 0, 1, $totx + 2, $cntop + 1, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($right - ($totx + 27) - 1, 5, price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency), '', 'R', 0, 1, $totx + 27, $cntop + 1, true, 0, 0, false, 0, 'M', false);
			// Top of the cheques table (dressing drawn by _tableau() once the page height is known)
			$tab_top	= $cntop + $cnth + 4;
			return $tab_top;
		}

		/**
		*	Draw the cheques table dressing (frame, header background, column titles, separators) for the current page.
		*	Called once per page with the real used height so the frame fits the content (like the propale _tableau).
		*
		*	@param		TCPDF			$pdf			Object PDF
		*	@param		RemiseCheque	$object			Object to show
		*	@param		float			$tab_top		Top position of the table
		*	@param		float			$tab_height		Height of the table on this page (fits the content)
		*	@param		Translate		$outputlangs	Object lang for output
		*	@return		void
		**/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs)
		{
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$right				= $this->page_largeur - $this->marge_droite;
			$ml					= $this->marge_gauche;
			$usable				= $right - $ml;
			$hdrh				= $this->ht_top_table;	// header row height (INFRASPLUS_PDF_HEIGHT_TOP_TABLE + radius)
			$tab_bottom			= $tab_top + $tab_height;
			// Header row background (title_bg + bg_color) — both top corners rounded ('1001')
			if (empty($this->hide_top_table) && !empty($this->title_bg)) {
				$pdf->RoundedRect($ml, $tab_top, $usable, $hdrh, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
			}
			// Outer table frame (border) — only when table lines are enabled ; height fits the content
			if (!empty($this->showtblline)) {
				$pdf->RoundedRect($ml, $tab_top, $usable, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			}
			// Header separator line (hidden with the column titles when hide_top_table is set)
			if (empty($this->hide_top_table)) {
				$pdf->Line($ml, $tab_top + $hdrh, $right, $tab_top + $hdrh, $this->horLineStyle);
			}
			// Column separators (vertical lines) — only when enabled, from top to bottom of the used table
			if (!empty($this->showverline)) {
				$pdf->Line($this->posx_num, $tab_top, $this->posx_num, $tab_bottom, $this->verLineStyle);
				$pdf->Line($this->posx_bank, $tab_top, $this->posx_bank, $tab_bottom, $this->verLineStyle);
				$pdf->Line($this->posx_emet, $tab_top, $this->posx_emet, $tab_bottom, $this->verLineStyle);
				$pdf->Line($this->posx_amount, $tab_top, $this->posx_amount, $tab_bottom, $this->verLineStyle);
			}
			// Column titles (hidden when hide_top_table is set ; font from $this->font, padding from $this->colpad)
			if (empty($this->hide_top_table)) {
				if (!empty($this->title_bg)) {
					$pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]);
				} else {
					$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
				}
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($this->larg_idx, $this->tab_hl, '#', '', 'C', 0, 1, $this->posx_idx, $tab_top + 1, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_num - $this->colpad, $this->tab_hl, $outputlangs->transnoentities('Num'), '', 'L', 0, 1, $this->posx_num + $this->colpad, $tab_top + 1, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_bank - $this->colpad, $this->tab_hl, $outputlangs->transnoentities('Bank'), '', 'L', 0, 1, $this->posx_bank + $this->colpad, $tab_top + 1, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_emet - $this->colpad, $this->tab_hl, $outputlangs->transnoentities('CheckTransmitter'), '', 'L', 0, 1, $this->posx_emet + $this->colpad, $tab_top + 1, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($this->larg_amount - $this->colpad, $this->tab_hl, $outputlangs->transnoentities('Amount'), '', 'R', 0, 1, $this->posx_amount, $tab_top + 1, true, 0, 0, false, 0, 'M', false);
			}
		}

		/**
		*	Show signature area at the bottom of the document
		*
		*	@param		TCPDF			$pdf			Object PDF
		*	@param		RemiseCheque	$object			Object to show
		*	@param		Translate		$outputlangs	Object lang for output
		*	@param		float			$posx			Left position of the signature area
		*	@param		float			$posy			Top position of the signature area
		*	@param		float			$larg			Width of the signature area
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
			// Electronic signature appearance (when enabled and uptosign is not the manager)
			if (!empty($this->e_signing)) {
				if (!isModEnabled('uptosign') && method_exists($pdf, 'addEmptySignatureAppearance')) {
					$pdf->addEmptySignatureAppearance($posx, $boxy, $larg, $this->ht_signarea);
				} else {
					pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posx, $boxy, $larg, $this->ht_signarea);
				}
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF			$pdf			The PDF factory
		*	@param		RemiseCheque	$object			Object to show
		*	@param		Translate		$outputlangs	Object lang for output
		*	@param		int				$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int								Return height of bottom margin including footer text
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul)
		{
			$showdetails	= $this->type_foot.(!empty($this->pied) ? 1 : 0);
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
