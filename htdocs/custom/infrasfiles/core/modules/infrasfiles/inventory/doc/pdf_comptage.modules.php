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
	* 	\brief		PDF model "comptage" : inventory counting sheet, lines grouped by storage zone with an empty box to write the counted quantity
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
			$multiwh	= empty($object->fk_warehouse);
			foreach ($lines as $line) {
				if (!empty($line['tobatch']) || $line['batch'] !== '') {
					$hasbatch	= true;
				}
			}

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

			// Columns : the empty "counted" box is always the last one on the right
			$colx		= $this->marge_gauche + 2;
			$right		= $this->marge_gauche + $width - 2;
			$wcounted	= 36;
			$wqty		= $showqty ? 26 : 0;
			$wbatch		= $hasbatch ? 32 : 0;
			$wwh		= $multiwh ? 30 : 0;
			$wref		= 38;
			$wlabel		= $right - $colx - $wref - $wwh - $wbatch - $wqty - $wcounted;
			$columns	= array(array('label' => $outputlangs->transnoentities('Ref'),	'x' => $colx,			'w' => $wref,	'align' => 'L'),
								array('label' => $outputlangs->transnoentities('Label'),	'x' => $colx + $wref,	'w' => $wlabel,	'align' => 'L'));
			$x	= $colx + $wref + $wlabel;
			if ($multiwh) {
				$columns[]	= array('label' => $outputlangs->transnoentities('Warehouse'), 'x' => $x, 'w' => $wwh, 'align' => 'L', 'key' => 'warehouse_ref');
				$x	+= $wwh;
			}
			if ($hasbatch) {
				$columns[]	= array('label' => $outputlangs->transnoentities('Batch'), 'x' => $x, 'w' => $wbatch, 'align' => 'L', 'key' => 'batch');
				$x	+= $wbatch;
			}
			if ($showqty) {
				$columns[]	= array('label' => $outputlangs->transnoentities('PhysicalStock'), 'x' => $x, 'w' => $wqty, 'align' => 'R', 'key' => 'qty_stock');
				$x	+= $wqty;
			}
			$columns[]	= array('label' => $outputlangs->transnoentities('InfraSFilesPdfColCounted'), 'x' => $x, 'w' => $wcounted, 'align' => 'C', 'key' => 'counted');
			$posy	= $this->infrasfilesWriteTableHeader($pdf, $outputlangs, $posy, $columns);

			// Lines grouped by zone (lines are already sorted by zone rank, ref, batch)
			$currentzone	= null;
			$nbzone			= 0;
			$total			= 0;
			foreach ($lines as $index => $line) {
				$zonekey	= (string) $line['zone'];
				if ($currentzone === null || $zonekey !== $currentzone) {
					// Zone band : label + number of references of the zone
					$nbzone	= 0;
					foreach ($lines as $other) {
						if ((string) $other['zone'] === $zonekey) {
							$nbzone++;
						}
					}
					$posy	= $this->infrasfilesCheckPageBreak($pdf, $object, $outputlangs, $posy, 14, $title, $columns);
					$zonelabel	= ($zonekey === '') ? $outputlangs->transnoentities('InfraSFilesPdfNoZone') : (!empty($line['zone_label']) ? $line['zone_label'] : $zonekey);
					$pdf->SetFillColor(245, 245, 245);
					$pdf->Rect($this->marge_gauche, $posy, $width, 6, 'F');
					$pdf->SetFont('', 'B', $default_font_size);
					$pdf->SetXY($colx, $posy + 1);
					$pdf->MultiCell($width - 4, 4, $outputlangs->convToOutputCharset($outputlangs->transnoentities('InfraSFilesPdfZone').' : '.$zonelabel.' - '.$outputlangs->transnoentities('InfraSFilesPdfNbRefs', $nbzone)), 0, 'L');
					$pdf->SetFont('', '', $default_font_size - 1);
					$posy	+= 6;
					$currentzone	= $zonekey;
				}
				$posy	= $this->infrasfilesCheckPageBreak($pdf, $object, $outputlangs, $posy, 9, $title, $columns);
				$ynext	= $posy;
				foreach ($columns as $column) {
					$key	= isset($column['key']) ? $column['key'] : '';
					if ($key == 'counted') {
						continue;	// drawn after the row height is known
					}
					if ($key == '') {
						$text	= ($column['x'] == $colx) ? $line['product_ref'] : $line['product_label'];
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
				// Empty box for the counted quantity
				$last	= $columns[count($columns) - 1];
				$pdf->SetDrawColor(120, 120, 120);
				$pdf->Rect($last['x'] + 2, $posy + 1, $last['w'] - 4, $rowh - 2);
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
			// Footer of the sheet : total and signature area
			$posy	= $this->infrasfilesCheckPageBreak($pdf, $object, $outputlangs, $posy, 26, $title, array());
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetXY($colx, $posy + 3);
			$pdf->MultiCell(100, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('InfraSFilesPdfNbRefs', $total)), 0, 'L');
			$pdf->SetFont('', '', $default_font_size);
			$pdf->SetXY($colx, $posy + 12);
			$pdf->MultiCell($width - 4, 5, $outputlangs->convToOutputCharset($outputlangs->transnoentities('InfraSFilesPdfCountedBy').' : ________________________________        '.$outputlangs->transnoentities('Date').' : ____ / ____ / ________        '.$outputlangs->transnoentities('Signature').' :'), 0, 'L');

			$this->_pagefoot($pdf, $object, $outputlangs);
			return $this->infrasfilesFinish($pdf, $paths, $object, $outputlangs);
		}
	}
