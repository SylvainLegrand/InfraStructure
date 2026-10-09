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
	* 	\file		./infrasfiles/core/modules/infrasfiles/inventory/doc/pdf_comptage.modules.php
	* 	\ingroup	InfraS
	* 	\brief		PDF model "comptage" : inventory counting sheet, storage zone in the first column (lines sorted by zone), empty boxes for the
	*				counted quantity and the recount, "counted by" and "recounted by" lines ; always portrait, a long text wraps in its cell
	************************************************/

	// Libraries ************************************
	dol_include_once('/infrasfiles/core/modules/infrasfiles/modules_infrasfilesinventory.php');
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

	/************************************************
	* Class pdf_comptage
	************************************************/
	class pdf_comptage extends ModelePDFInfrasfilesinventory
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
			$this->name			= 'comptage';
			$this->description	= $langs->trans('InfraSFilesModelComptageDesc');
		}

		/**
		*	Write the PDF file
		*
		*	@param		InfrasFilesInventory	$object			Object to generate
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
			global $conf, $langs;

			if (!is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			if (getDolGlobalString('MAIN_USE_FPDF')) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'companies', 'stocks', 'products', 'productbatch', 'categories', 'infrasfiles@infrasfiles'));

			$unit	= (is_array($moreparams) && !empty($moreparams['infrasfiles_unit'])) ? $moreparams['infrasfiles_unit'] : array('suffix' => '', 'label' => '');
			if (empty($object->infrasfiles_lines) && empty($object->specimen) && method_exists($object, 'infrasfilesFetchLines')) {
				$object->infrasfilesFetchLines();
			}
			$lines		= (array) $object->infrasfiles_lines;
			$title		= $outputlangs->transnoentities('InfraSFilesPdfTitleCounting');
			$showqty	= (bool) getDolGlobalInt(infrasfiles_const_name('inventory', 'SHOW_QTY'), 0);
			$hasbatch	= false;
			$haszone	= false;
			$multiwh	= empty($object->fk_warehouse);
			foreach ($lines as $line) {
				if (!empty($line['tobatch']) || $line['batch'] !== '') {
					$hasbatch	= true;
				}
				if ((string) $line['zone'] !== '') {
					$haszone	= true;	// the "Zone" column is shown only when at least one line has a zone (no zone source = no column)
				}
			}
			// Column widths (headers measured in the 4 languages at the header font size) : [Zone], Ref, Label (remaining width), [Warehouse], [Batch], [Physical stock], Counted quantity, Recount.
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
			$usable	= $this->page_largeur - $this->marge_gauche - $this->marge_droite - 4;
			if ($usable - array_sum($widths) < 40) {
				$ratio	= ($usable - 40) / array_sum($widths);
				foreach ($widths as $key => $w) {
					$widths[$key]	= round($w * $ratio, 1);
				}
			}
			$widths['label']	= $usable - array_sum($widths);

			// Output file
			$paths	= $this->infrasfilesGetFile($object, $unit, $outputlangs);
			if (!$paths) {
				return -1;
			}
			$this->infrasfilesBefore($object, $outputlangs, $paths['file']);

			// Warehouse
			$warehouse	= '';
			if (!empty($object->fk_warehouse)) {
				$entrepot	= new Entrepot($this->db);
				if ($entrepot->fetch($object->fk_warehouse) > 0) {
					$warehouse	= $entrepot->ref.(!empty($entrepot->lieu) ? ' - '.$entrepot->lieu : '');
				}
			}
			$date	= !empty($object->date_inventory) ? $object->date_inventory : $object->date_creation;
			// Filters of the inventory (product categories, single product) : printed in the head when set
			$filters	= method_exists($object, 'infrasfilesGetFilters') ? $object->infrasfilesGetFilters() : array('categories' => array(), 'product' => '');

			$pdf				= $this->infrasfilesInitPdf($outputlangs, $title.' '.$object->ref, $title);
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$width				= $this->page_largeur - $this->marge_gauche - $this->marge_droite;
			$this->infrasfilesAddPage($pdf);

			// Head
			$rightlines	= array(array($outputlangs->transnoentities('Ref'), $object->ref),
								array($outputlangs->transnoentities('Label'), (string) $object->title),
								array($outputlangs->transnoentities('Warehouse'), $warehouse),
								array($outputlangs->transnoentities('Categories'), implode(', ', $filters['categories'])),
								array($outputlangs->transnoentities('Product'), $filters['product']),
								array($outputlangs->transnoentities('Date'), !empty($date) ? dol_print_date($date, 'day', false, $outputlangs) : ''),
								array($outputlangs->transnoentities('Status'), dol_string_nohtmltag($object->getLibStatut(0))));
			$posy	= $this->infrasfilesWriteHead($pdf, $object, $outputlangs, $title, $rightlines);

			// Columns (widths computed above) : the two empty boxes "counted quantity" and "recount" are the last ones on the right
			$colx		= $this->marge_gauche + 2;
			$labels		= array('zone'			=> $outputlangs->transnoentities('InfraSFilesPdfZone'),
								'ref'			=> $outputlangs->transnoentities('Ref'),
								'label'			=> $outputlangs->transnoentities('Label'),
								'warehouse_ref'	=> $outputlangs->transnoentities('Warehouse'),
								'batch'			=> $outputlangs->transnoentities('Batch'),
								'qty_stock'		=> $outputlangs->transnoentities('PhysicalStock'),
								'counted'		=> $outputlangs->transnoentities('InfraSFilesPdfColCounted'),
								'recount'		=> $outputlangs->transnoentities('InfraSFilesPdfColRecount'));
			$boxes		= array('counted', 'recount');
			$columns	= array();
			$x			= $colx;
			foreach ($widths as $key => $w) {
				$columns[]	= array('key' => $key, 'label' => $labels[$key], 'x' => $x, 'w' => $w, 'align' => ($key == 'qty_stock' ? 'R' : (in_array($key, $boxes) ? 'C' : 'L')));
				$x	+= $w;
			}
			$posy	= $this->infrasfilesWriteTableHeader($pdf, $outputlangs, $posy, $columns);

			// Lines, sorted by zone rank, ref, batch (InfrasFilesInventory::infrasfilesFetchLines()) : the zone is a column, no more zone bands
			$total			= 0;
			foreach ($lines as $line) {
				$posy	= $this->infrasfilesCheckPageBreak($pdf, $object, $outputlangs, $posy, 9, $title, $columns);
				$ynext	= $posy;
				foreach ($columns as $column) {
					$key	= $column['key'];
					if (in_array($key, $boxes)) {
						continue;	// drawn after the row height is known
					}
					if ($key == 'zone') {
						$text	= ((string) $line['zone'] === '') ? '' : (!empty($line['zone_label']) ? $line['zone_label'] : (string) $line['zone']);	// no zone : empty cell (these lines are sorted last)
					} elseif ($key == 'ref') {
						$text	= $line['product_ref'];
					} elseif ($key == 'label') {
						$text	= $line['product_label'];
					} elseif ($key == 'qty_stock') {
						$text	= ($line['qty_stock'] === null) ? '' : (string) price2num($line['qty_stock'], 'MS');
					} else {
						$text	= (string) $line[$key];
					}
					$pdf->SetXY($column['x'], $posy + 1);
					$pdf->MultiCell($column['w'] - 1, 4, $outputlangs->convToOutputCharset($text), 0, $column['align']);
					$ynext	= max($ynext, $pdf->GetY());
				}
				$rowh	= max($ynext - $posy, 7) + 1;
				// Empty boxes for the counted quantity and the recount
				$pdf->SetDrawColor(120, 120, 120);
				foreach ($columns as $column) {
					if (in_array($column['key'], $boxes)) {
						$pdf->Rect($column['x'] + 2, $posy + 1, $column['w'] - 4, $rowh - 2);
					}
				}
				$pdf->SetDrawColor(210, 210, 210);
				$pdf->line($this->marge_gauche, $posy + $rowh, $this->marge_gauche + $width, $posy + $rowh);
				$posy	+= $rowh;
				$total++;
			}
			if (empty($lines)) {
				$pdf->SetXY($colx, $posy + 2);
				$pdf->MultiCell($width - 4, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('NoRecordFound')), 0, 'C');
				$posy	+= 8;
			}
			// Footer of the sheet : total, then the "counted by" and "recounted by" lines with date and signature
			$posy	= $this->infrasfilesCheckPageBreak($pdf, $object, $outputlangs, $posy, 34, $title, array());
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetXY($colx, $posy + 3);
			$pdf->MultiCell(100, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('InfraSFilesPdfNbRefs', $total)), 0, 'L');
			$pdf->SetFont('', '', $default_font_size);
			$this->infrasfilesSignLine($pdf, $outputlangs, $colx, $posy + 12, $width - 4, $outputlangs->transnoentities('InfraSFilesPdfCountedBy'));
			$this->infrasfilesSignLine($pdf, $outputlangs, $colx, $posy + 21, $width - 4, $outputlangs->transnoentities('InfraSFilesPdfRecountedBy'));

			$this->_pagefoot($pdf, $object, $outputlangs);
			return $this->infrasfilesFinish($pdf, $paths, $object, $outputlangs);
		}
		/**
		*	Signature line of the sheet : "<label> : ________   Date : ____ / ____ / ________   Signature : ________", the three parts at
		*	fixed positions so that the "counted by" and "recounted by" lines are aligned whatever the length of their label
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@param		Translate	$outputlangs	Output language
		*	@param		float		$x				Left position
		*	@param		float		$y				Top position
		*	@param		float		$w				Width of the line
		*	@param		string		$label			Label (ex : "Counted by")
		*	@return		void
		**/
		protected function infrasfilesSignLine(&$pdf, $outputlangs, $x, $y, $w, $label)
		{
			$xdate	= $x + round($w * 0.48);
			$xsign	= $x + round($w * 0.76);
			$base	= $y + 4;	// underline position
			$label	= $outputlangs->convToOutputCharset($label.' : ');
			$sign	= $outputlangs->convToOutputCharset($outputlangs->transnoentities('Signature').' : ');
			$pdf->SetDrawColor(80, 80, 80);
			$pdf->SetXY($x, $y);
			$pdf->MultiCell($xdate - $x - 2, 5, $label, 0, 'L');
			$pdf->line($x + $pdf->GetStringWidth($label), $base, $xdate - 4, $base);
			$pdf->SetXY($xdate, $y);
			$pdf->MultiCell($xsign - $xdate - 2, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('Date').' : ____ / ____ / ________'), 0, 'L');
			$pdf->SetXY($xsign, $y);
			$pdf->MultiCell($x + $w - $xsign, 5, $sign, 0, 'L');
			$pdf->line($xsign + $pdf->GetStringWidth($sign), $base, $x + $w, $base);
		}
	}
