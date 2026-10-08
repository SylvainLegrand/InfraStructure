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
	* 	\file		./infraspackplus/core/modules/infrasfiles/inventory/doc/pdf_InfraSPlus_INV.modules.php
	* 	\ingroup	InfraS
	* 	\brief		InfraSPlus PDF model of the inventory counting sheet (module InfraSFiles, object 'inventory') :
	*				same content as the 'comptage' model of InfraSFiles (inventory information in the head, storage zone in the first
	*				column with lines sorted by zone, empty "counted quantity" and "recount" boxes, "counted by" and "recounted by"
	*				areas with date and signature, always portrait : a long text wraps in its cell) with the InfraSPackPlus layout
	*				and options (logo, frames, fonts, colors, borders, watermark, mentions, notes, footer image, merged files)
	************************************************/

	// Libraries ************************************
	dol_include_once('/infrasfiles/core/modules/infrasfiles/modules_infrasfilesinventory.php');
	if (!class_exists('ModelePDFInfrasfilesinventory')) {
		return;	// Module InfraSFiles not installed : this model has no parent class, nothing to declare
	}
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	dol_include_once('/infraspackplus/class/address.class.php');

	/************************************************
	*	Class to generate the InfraS PDF inventory counting sheet
	************************************************/
	#[\AllowDynamicProperties]	// pdf_InfraSPlus_getValues() fills the whole set of InfraSPlus layout properties (parsed as a comment by PHP < 8.0)
	class pdf_InfraSPlus_INV extends ModelePDFInfrasfilesinventory
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
		public $listfreet;
		public $listnotep;
		public $pied;
		public $files;
		public $entrepot;	// Warehouse of the inventory (Entrepot) or null
		public $filters = array();	// Filters of the inventory : categories, product
		public $lines = array();	// Inventory lines
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
			$langs->loadLangs(array('main', 'companies', 'stocks', 'products', 'infraspackplus@infraspackplus', 'infrasfiles@infrasfiles'));
			pdf_InfraSPlus_getValues($this);
			$this->db						= $db;
			$this->name						= $langs->trans('PDFInfraSPlusINVName');
			$this->description				= $langs->trans('PDFInfraSPlusINVDescription');
			$this->defaulttemplate			= getDolGlobalString('INFRASFILES_INVENTORY_ADDON_PDF', '');
			$this->update_main_doc_field	= 0;	// Set to 1 by getValues() : the native table has no last_main_doc column, InfraSFiles stores it itself
			$this->line_height				= 6;
			$this->option_logo				= 1;	// Display logo
			$this->option_multilang			= 1;	// Available in several languages
			$this->option_freetext			= 1;	// Support add of a personalised text
		}

		/**
		*	Write the PDF file
		*
		*	@param		InfrasFilesInventory	$object			Object to generate
		*	@param		Translate				$outputlangs	Lang output object
		*	@param		string					$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int						$hidedetails	Do not show line details
		*	@param		int						$hidedesc		Do not show desc
		*	@param		int						$hideref		Do not show ref
		*	@param		array|null				$moreparams		More parameters (key 'infrasfiles_unit' = generation unit)
		*	@return		int										1 if OK, <= 0 if KO
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
			$outputlangs->loadLangs(array('main', 'companies', 'stocks', 'products', 'productbatch', 'categories', 'infraspackplus@infraspackplus', 'infrasfiles@infrasfiles'));

			// Lines (same data as the 'comptage' model of InfraSFiles : sorted by zone rank, product ref, batch)
			$unit	= (is_array($moreparams) && !empty($moreparams['infrasfiles_unit'])) ? $moreparams['infrasfiles_unit'] : array('suffix' => '', 'label' => '');
			if (empty($object->infrasfiles_lines) && empty($object->specimen) && method_exists($object, 'infrasfilesFetchLines')) {
				$object->infrasfilesFetchLines();
			}
			$this->lines	= (array) $object->infrasfiles_lines;
			$this->filters	= method_exists($object, 'infrasfilesGetFilters') ? $object->infrasfilesGetFilters() : array('categories' => array(), 'product' => '');
			$showqty	= (bool) getDolGlobalInt(infrasfiles_const_name('inventory', 'SHOW_QTY'), 0);
			$hasbatch	= false;
			$haszone	= false;
			$multiwh	= empty($object->fk_warehouse);
			foreach ($this->lines as $line) {
				if (!empty($line['tobatch']) || $line['batch'] !== '') {
					$hasbatch	= true;
				}
				if ((string) $line['zone'] !== '') {
					$haszone	= true;	// the "Zone" column is shown only when at least one line has a zone (no zone source = no column)
				}
			}
			// Column widths (headers measured in the 4 languages at the header font size) : [Zone], Ref, Label (remaining width), [Warehouse], [Batch], [Physical stock], Counted quantity, Recount (empty boxes).
			// Always portrait : a long label (or zone, ref...) wraps in its cell and the row grows. The label keeps at least 40 mm : when the
			// optional columns would leave less, the other columns are narrowed in proportion (their titles then wrap, the header row grows)
			$widths	= array();
			if ($haszone) {
				$widths['zone']	= 26;
			}
			$widths['ref']		= 32;
			$widths['label']	= 0;
			if ($multiwh) {
				$widths['warehouse_ref']	= 26;
			}
			if ($hasbatch) {
				$widths['batch']	= 28;
			}
			if ($showqty) {
				$widths['qty_stock']	= 26;
			}
			$widths['counted']	= 30;
			$widths['recount']	= 26;
			$usable	= $this->page_largeur - $this->marge_gauche - $this->marge_droite;
			if ($usable - array_sum($widths) < 40) {
				$ratio	= ($usable - 40) / array_sum($widths);
				foreach ($widths as $key => $w) {
					$widths[$key]	= round($w * $ratio, 1);
				}
			}
			$widths['label']	= $usable - array_sum($widths);
			$this->entrepot	= null;
			if (!empty($object->fk_warehouse)) {
				$entrepot	= new Entrepot($this->db);
				if ($entrepot->fetch($object->fk_warehouse) > 0) {
					$this->entrepot	= $entrepot;
				}
			}

			// Output file (directory and base name from InfraSFiles, prefix / suffix from the InfraSPackPlus setup)
			$paths	= $this->infrasfilesGetFile($object, $unit, $outputlangs);
			if (!$paths) {
				return -1;
			}
			$paths	= $this->infrasplusApplyPrefix($paths);

			// Hook beforePDFCreation : choices of the InfraSPackPlus options form (logo, sender address, mentions, notes, footer image, files)
			$this->infrasfilesBefore($object, $outputlangs, $paths['file']);
			$res	= (is_object($hookmanager) && !empty($hookmanager->resArray) && is_array($hookmanager->resArray)) ? $hookmanager->resArray : array();
			$this->logo			= !empty($res['logo']) ? $res['logo'] : '';
			$this->adr			= !empty($res['adr']) ? $res['adr'] : '';
			$this->listfreet	= !empty($res['listfreet']) ? $res['listfreet'] : '';
			$this->listnotep	= !empty($res['listnotep']) ? $res['listnotep'] : '';
			$this->pied			= !empty($res['pied']) ? $res['pied'] : getDolGlobalString('INFRASPLUS_PDF_IMAGE_FOOT', '');
			$this->files		= !empty($res['filesArray']) ? $res['filesArray'] : '';

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
			$title	= $outputlangs->transnoentities('InfraSFilesPdfTitleCounting');
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
			// Columns (widths computed above) : the two empty boxes "counted quantity" and "recount" are the last ones on the right
			$right		= $this->page_largeur - $this->marge_droite;
			$usable		= $right - $this->marge_gauche;
			$labels		= array('zone'			=> $outputlangs->transnoentities('InfraSFilesPdfZone'),
								'ref'			=> $outputlangs->transnoentities('Ref'),
								'label'			=> $outputlangs->transnoentities('Label'),
								'warehouse_ref'	=> $outputlangs->transnoentities('Warehouse'),
								'batch'			=> $outputlangs->transnoentities('Batch'),
								'qty_stock'		=> $outputlangs->transnoentities('PhysicalStock'),
								'counted'		=> $outputlangs->transnoentities('InfraSFilesPdfColCounted'),
								'recount'		=> $outputlangs->transnoentities('InfraSFilesPdfColRecount'));
			$boxes			= array('counted', 'recount');
			$this->columns	= array();	// reset : an instance generates several documents
			$x				= $this->marge_gauche;
			foreach ($widths as $key => $w) {
				$this->columns[$key]	= array('x' => $x, 'w' => $w, 'align' => ($key == 'qty_stock' ? 'R' : (in_array($key, $boxes) ? 'C' : 'L')), 'label' => $labels[$key]);
				$x	+= $w;
			}
			// Header row height : a column title too long for its column wraps, the header row grows (same font as _tableau())
			if (empty($this->hide_top_table)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				foreach ($this->columns as $key => $column) {
					$pad				= in_array($key, array('zone', 'ref', 'label', 'warehouse_ref', 'batch')) ? $this->colpad : 0;
					$this->ht_top_table	= max($this->ht_top_table, $pdf->getStringHeight($column['w'] - $pad, $outputlangs->convToOutputCharset($column['label'])) + 2);
				}
				$pdf->SetFont('', '', $default_font_size);
			}

			// First page
			$pdf->AddPage();
			$this->infrasplusWatermarks($pdf, $object, $outputlangs);
			$watermarkedPages	= array($pdf->getPage() => true);
			// Reserved heights : the footer on every page ; the mentions, the counting / signature band and the total line on the last page only
			// (they used to be reserved on every page, which left a blank band at the bottom of the intermediate pages)
			$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
			$heightforfreetext	= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 1, 1, $this->horLineStyle);
			$signband			= $this->ht_signarea + 18;	// "counted by" / "recounted by" blocks side by side : name line + date line + "Signature" label + box + margins
			$totalband			= 10;
			$tab_bottom			= $this->page_hauteur - $heightforfooter - 2;	// intermediate pages : rows down to the footer
			$lastband			= $totalband + $signband + $heightforfreetext;	// end of the sheet, printed under the table of the last page
			$this->tab_top		= $this->_pagehead($pdf, $object, $outputlangs, 1);
			// Notes of the InfraSPackPlus dictionary chosen in the options form (under the header, like the other InfraSPlus models)
			$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $this->tab_top, $usable - 2 * $this->colpad, $this->tab_hl, $this->marge_gauche + $this->colpad, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $usable, $this->tblLineStyle, -1, 0);
			$this->tab_top	+= $height_note;
			$hdrh			= empty($this->hide_top_table) ? $this->ht_top_table : 0;
			$curY			= $this->tab_top + $hdrh + 1;	// first data row

			// Body : one row per line (sorted by zone rank, ref, batch : the zone is a column, no more zone bands) with the two empty boxes
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			$nb				= 0;
			foreach ($this->lines as $line) {
				$zonetext	= ((string) $line['zone'] === '') ? '' : (!empty($line['zone_label']) ? $line['zone_label'] : (string) $line['zone']);	// no zone : empty cell (these lines are sorted last)
				$texts		= array('zone'			=> $zonetext,
									'ref'			=> $line['product_ref'],
									'label'			=> $line['product_label'],
									'warehouse_ref'	=> (string) $line['warehouse_ref'],
									'batch'			=> (string) $line['batch'],
									'qty_stock'		=> ($line['qty_stock'] === null) ? '' : (string) price2num($line['qty_stock'], 'MS'));
				// Row height : any text column may wrap (zone, ref, label, warehouse, batch) ; minimum = counting box height
				$rowh	= max($this->line_height, 7);
				foreach ($this->columns as $key => $column) {
					if (!in_array($key, $boxes)) {
						$rowh	= max($rowh, $pdf->getStringHeight($column['w'] - 2 * $this->colpad, $outputlangs->convToOutputCharset($texts[$key])));
					}
				}
				// Page break : close the table on this page, open a new one
				if (($curY + $rowh) > $tab_bottom) {
					$this->_tableau($pdf, $object, $this->tab_top, $curY - $this->tab_top, $outputlangs);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);	// footer (company details, page number) of every page, not only the last one
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
				foreach ($this->columns as $key => $column) {
					if (in_array($key, $boxes)) {
						continue;
					}
					$text	= $texts[$key];
					$pad	= in_array($key, array('zone', 'ref', 'label', 'warehouse_ref', 'batch')) ? $this->colpad : 0;
					$pdf->MultiCell($column['w'] - $pad - ($key == 'qty_stock' ? $this->colpad : 0), $this->line_height, $outputlangs->convToOutputCharset($text), '', $column['align'], 0, 1, $column['x'] + $pad, $curY, true, 0, 0, false, 0, 'M', false);
				}
				// Empty boxes for the counted quantity and the recount (InfraSPlus rounded rectangles)
				foreach ($boxes as $boxkey) {
					$box	= $this->columns[$boxkey];
					$pdf->RoundedRect($box['x'] + 2, $curY + 0.5, $box['w'] - 4, $rowh - 1, min($this->Rounded_rect, 1.5), '1111', null, $this->stdLineStyle);
				}
				$curY	+= $rowh;
				$nb++;
				if (!empty($this->dash_between_line)) {
					$pdf->Line($this->marge_gauche, $curY, $right, $curY, $this->horLineStyle);
				}
			}
			if (empty($this->lines)) {
				$pdf->MultiCell($usable - 2 * $this->colpad, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('NoRecordFound')), '', 'C', 0, 1, $this->marge_gauche + $this->colpad, $curY + 1, true, 0, 0, false, 0, 'M', false);
				$curY	+= 8;
			}
			// Table dressing of the last page (frame height fits the content)
			$this->_tableau($pdf, $object, $this->tab_top, $curY - $this->tab_top, $outputlangs);
			// End of the sheet (total, counting / signature band, mentions) too high for the space left under the table : on a new page
			if (($curY + $lastband) > ($this->page_hauteur - $heightforfooter)) {
				$this->_pagefoot($pdf, $object, $outputlangs, 0);
				$pdf->AddPage();
				if (empty($watermarkedPages[$pdf->getPage()])) {
					$this->infrasplusWatermarks($pdf, $object, $outputlangs);
				}
				$watermarkedPages[$pdf->getPage()]	= true;
				$curY	= $this->_pagehead($pdf, $object, $outputlangs, 0);
			}
			// Number of references
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($usable - 2 * $this->colpad, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('InfraSFilesPdfNbRefs', $nb)), '', 'L', 0, 1, $this->marge_gauche + $this->colpad, $curY + 2, true, 0, 0, false, 0, 'M', false);
			// Bottom of the last page : counting / signature area, then mentions above the footer, then footer
			$posyfoot	= $this->page_hauteur - $heightforfooter;
			$posysign	= $posyfoot - $heightforfreetext - $signband;
			$this->_counting_area($pdf, $object, $outputlangs, $posysign);
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
		*	(option "prefix of the models", constant INFRASPLUS_PDF_ADD_PREFIX_TO_INV, and "_INV" suffix in multi-files mode)
		*
		*	@param		array		$paths		array('dir', 'file', 'relative') from infrasfilesGetFile()
		*	@return		array					Same array with the final file name
		**/
		protected function infrasplusApplyPrefix($paths)
		{
			$fileprefix	= '';
			if (!getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME')) {
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_INV') ? '' : '_INV';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_ADD_PREFIX_TO_INV', '');
				$filesufixe	= empty($fileprefix) ? '_INV' : '';
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
		*	Watermarks of the page : InfraSPackPlus background image / text, and the draft watermark option of InfraSFiles
		*
		*	@param		TCPDF					$pdf			PDF instance
		*	@param		InfrasFilesInventory	$object			Object
		*	@param		Translate				$outputlangs	Output language
		*	@return		void
		**/
		protected function infrasplusWatermarks(&$pdf, $object, $outputlangs)
		{
			global $conf;

			pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, !empty($object->entity) ? $object->entity : $conf->entity, $outputlangs);
			$isdraft	= (isset($object->status) && (int) $object->status == 0);
			$watermark	= infrasfiles_get_option('inventory', 'WATERMARK');
			if ($isdraft && $watermark !== '') {
				pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $watermark);
			}
		}

		/**
		*	Page head : logo, title with reference / label / date / status on the right, then (first page) the InfraSPlus frames :
		*	company on the left (sender address chosen in the options), inventory on the right (warehouse and its address, filters)
		*
		*	@param		TCPDF					$pdf			PDF instance
		*	@param		InfrasFilesInventory	$object			Object
		*	@param		Translate				$outputlangs	Output language
		*	@param		int						$showaddress	1 = print the frames (first page)
		*	@return		float									Top position of the table
		**/
		protected function _pagehead(&$pdf, $object, $outputlangs, $showaddress)
		{
			global $conf, $db;

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
			$pdf->MultiCell($w, $this->tab_hl * $this->title_size, $outputlangs->transnoentities('InfraSFilesPdfTitleCounting'), '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	= $pdf->getY();
			// Reference, label, date, status
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 2);
			if (!empty($object->title)) {
				$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('Label').' : '.$outputlangs->convToOutputCharset($object->title), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$posy	+= $this->tab_hl - 0.5;
			}
			$date	= !empty($object->date_inventory) ? $object->date_inventory : $object->date_creation;
			$pdf->SetFont('', (!empty($this->datesbold) ? 'B' : ''), $default_font_size - 2);
			$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('Date').' : '.dol_print_date($date, 'day', false, $outputlangs, true), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl - 0.5;
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('Status').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag($object->getLibStatut(0))), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl;
			$dimCadres['Y']	= !empty($this->use_iso_location) && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl);
			if (empty($showaddress)) {
				return $dimCadres['Y'];
			}
			// Sender frame : company, with the sender address chosen in the options (same rule as pdf_InfraSPlus_getAddresses())
			$show_emet_details	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_EMET_DETAILS', 0);
			$sender_alias		= '';
			$carac_emetteur		= '';
			if (!empty($this->adr)) {
				$addressstatic	= new Address($db);
				if ($addressstatic->fetch($this->adr) == 1) {
					$sender_alias	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_SENDER_ALIAS', 0) ? $addressstatic->name : '';
					$carac_emetteur	= pdf_InfraSPlus_build_address($outputlangs, $this->emetteur, $addressstatic, $this->emetteur, '', 0, $show_emet_details ? 'source' : 'sourcewithnodetails', $object, 1, 0);
				}
			}
			if ($carac_emetteur === '') {
				$carac_emetteur	= pdf_InfraSPlus_build_address($outputlangs, $this->emetteur, $this->emetteur, $this->emetteur, '', 0, $show_emet_details ? 'source' : 'sourcewithnodetails', $object, 1, 0);
			}
			// Inventory frame : warehouse (or all warehouses) with its address, then the filters of the inventory
			$info	= array();
			if (is_object($this->entrepot)) {
				$name	= $this->entrepot->ref.(!empty($this->entrepot->lieu) ? ' - '.$this->entrepot->lieu : '');
				if (!empty($this->entrepot->address)) {
					$info[]	= $this->entrepot->address;
				}
				$zip	= trim((!empty($this->entrepot->zip) ? $this->entrepot->zip : '').' '.(!empty($this->entrepot->town) ? $this->entrepot->town : ''));
				if ($zip !== '') {
					$info[]	= $zip;
				}
			} else {
				$name	= $outputlangs->transnoentities('AllWarehouses');
			}
			if (!empty($this->filters['categories'])) {
				$info[]	= $outputlangs->transnoentities('Categories').' : '.implode(', ', $this->filters['categories']);
			}
			if (!empty($this->filters['product'])) {
				$info[]	= $outputlangs->transnoentities('Product').' : '.$this->filters['product'];
			}
			$addresses	= array('sender_Alias'		=> $sender_alias,
								'carac_emetteur'	=> $carac_emetteur,
								'carac_client_name'	=> $outputlangs->convToOutputCharset($name),
								'carac_client'		=> $outputlangs->convToOutputCharset(implode("\n", $info)),
								'livrshow_name'		=> '',
								'livrshow'			=> '',
								'SsTshow'			=> '');
			// The native frame labels are "Bill from / Bill to" : hidden here, the sheet prints "Sender / Inventory" itself
			$hidelabels	= getDolGlobalInt('INFRASPLUS_PDF_HIDE_LABELS_FRAMES', 0);
			if (empty($hidelabels)) {
				$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
				$pdf->SetFont('', '', $default_font_size - 2);
				$xS	= getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT', 0) ? $right = $this->page_largeur - $this->marge_droite - $dimCadres['S'] : $this->marge_gauche;
				$xR	= getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT', 0) ? $this->marge_gauche : $this->page_largeur - $this->marge_droite - $dimCadres['R'];
				$pdf->MultiCell($dimCadres['S'], $this->tab_hl + 1, $outputlangs->transnoentities('BillFrom').' : ', '', 'L', 0, 1, $xS + $this->decal_round, $dimCadres['Y'] - 4, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($dimCadres['R'], $this->tab_hl + 1, $outputlangs->transnoentities('Inventory').' : ', '', 'L', 0, 1, $xR + $this->decal_round, $dimCadres['Y'] - 4, true, 0, 0, false, 0, 'M', false);
			}
			$conf->global->INFRASPLUS_PDF_HIDE_LABELS_FRAMES	= 1;
			$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
			$conf->global->INFRASPLUS_PDF_HIDE_LABELS_FRAMES	= $hidelabels;
			return $dimCadres['Y'] + $hauteurcadre + $this->tab_hl + 2;
		}

		/**
		*	Draw the table dressing (frame, header background, column titles, separators) for the current page,
		*	once the used height is known so the frame fits the content
		*
		*	@param		TCPDF					$pdf			PDF instance
		*	@param		InfrasFilesInventory	$object			Object
		*	@param		float					$tab_top		Top position of the table
		*	@param		float					$tab_height		Height of the table on this page
		*	@param		Translate				$outputlangs	Output language
		*	@return		void
		**/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs)
		{
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$right				= $this->page_largeur - $this->marge_droite;
			$usable				= $right - $this->marge_gauche;
			$hdrh				= $this->ht_top_table;
			$tab_bottom			= $tab_top + $tab_height;
			if (empty($this->hide_top_table) && !empty($this->title_bg)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $usable, $hdrh, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
			}
			if (!empty($this->showtblline)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $usable, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			}
			if (empty($this->hide_top_table)) {
				$pdf->Line($this->marge_gauche, $tab_top + $hdrh, $right, $tab_top + $hdrh, $this->horLineStyle);
			}
			if (!empty($this->showverline)) {
				$first	= key($this->columns);	// no separator before the first column (Zone, or Ref when there is no zone)
				foreach ($this->columns as $key => $column) {
					if ($key != $first) {
						$pdf->Line($column['x'], $tab_top, $column['x'], $tab_bottom, $this->verLineStyle);
					}
				}
			}
			if (empty($this->hide_top_table)) {
				if (!empty($this->title_bg)) {
					$pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]);
				} else {
					$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
				}
				$pdf->SetFont('', 'B', $default_font_size - 2);
				foreach ($this->columns as $key => $column) {
					$pad	= in_array($key, array('zone', 'ref', 'label', 'warehouse_ref', 'batch')) ? $this->colpad : 0;
					$pdf->MultiCell($column['w'] - $pad, $this->tab_hl, $outputlangs->convToOutputCharset($column['label']), '', $column['align'], 0, 1, $column['x'] + $pad, $tab_top + 1, true, 0, 0, false, 0, 'M', false);
				}
			}
		}

		/**
		*	Counting area at the bottom of the sheet : two blocks side by side, "Counted by" on the left and "Recounted by" on the right,
		*	each with its name line, its date line and its signature box (same height as the former single block)
		*
		*	@param		TCPDF					$pdf			PDF instance
		*	@param		InfrasFilesInventory	$object			Object
		*	@param		Translate				$outputlangs	Output language
		*	@param		float					$posy			Top position of the area
		*	@return		void
		**/
		protected function _counting_area(&$pdf, $object, $outputlangs, $posy)
		{
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$right				= $this->page_largeur - $this->marge_droite;
			$usable				= $right - $this->marge_gauche;
			$gap				= 6;
			$larg_block			= ($usable - $gap) / 2;
			$blocks				= array('InfraSFilesPdfCountedBy' => $this->marge_gauche, 'InfraSFilesPdfRecountedBy' => $this->marge_gauche + $larg_block + $gap);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			$first	= true;
			foreach ($blocks as $labelkey => $posx) {
				$pdf->MultiCell($larg_block - $this->colpad, 4, $outputlangs->convToOutputCharset($outputlangs->transnoentities($labelkey).' : ______________________________'), '', 'L', 0, 1, $posx + $this->colpad, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_block - $this->colpad, 4, $outputlangs->convToOutputCharset($outputlangs->transnoentities('Date').' : ____ / ____ / ________'), '', 'L', 0, 1, $posx + $this->colpad, $posy + 5, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_block - $this->colpad, 4, $outputlangs->convToOutputCharset($outputlangs->transnoentities('Signature')), '', 'L', 0, 1, $posx + $this->colpad, $posy + 10, true, 0, 0, false, 0, 'M', false);
				$boxy	= $posy + 14.5;
				$pdf->RoundedRect($posx, $boxy, $larg_block, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
				if ($first && !empty($this->e_signing)) {	// electronic signature field on the "counted by" box only (one signature field per document)
					if (!isModEnabled('uptosign') && method_exists($pdf, 'addEmptySignatureAppearance')) {
						$pdf->addEmptySignatureAppearance($posx, $boxy, $larg_block, $this->ht_signarea);
					} else {
						pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posx, $boxy, $larg_block, $this->ht_signarea);
					}
				}
				$first	= false;
			}
		}

		/**
		*	Page footer of InfraSPackPlus (company details, footer image, page number)
		*
		*	@param		TCPDF					$pdf			PDF instance
		*	@param		InfrasFilesInventory	$object			Object
		*	@param		Translate				$outputlangs	Output language
		*	@param		int						$calculseul		1 = only compute the height
		*	@return		int										Height of the footer
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul = 0)
		{
			global $conf;

			$showdetails	= $this->type_foot.(!empty($this->pied) ? 1 : 0);
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, !empty($object->entity) ? $object->entity : $conf->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
