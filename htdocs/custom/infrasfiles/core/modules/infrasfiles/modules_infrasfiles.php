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
	* 	\file		./infrasfiles/core/modules/infrasfiles/modules_infrasfiles.php
	* 	\ingroup	InfraS
	* 	\brief		Parent class of the PDF document models of module InfraSFiles
	*				(page setup, output file, common header / footer, page breaks)
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');

	/************************************************
	* Class ModelePDFInfrasFiles
	************************************************/
	abstract class ModelePDFInfrasFiles extends CommonDocGenerator
	{
		public $infrasfiles_element = '';	// @var string Key of the object in the module registry (set by the child class)
		public $update_main_doc_field = 0;	// The native table has no last_main_doc column : the module stores it itself
		public $type = 'pdf';
		public $version = 'dolibarr';
		public $emetteur;
		public $page_largeur;
		public $page_hauteur;
		public $format;
		public $marge_gauche;
		public $marge_droite;
		public $marge_haute;
		public $marge_basse;
		public $option_logo = 1;
		public $option_multilang = 1;
		public $option_freetext = 1;
		public $infrasfiles_tplidx = 0;	// Template index of the background PDF (MAIN_ADD_PDF_BACKGROUND), applied on every page by infrasfilesAddPage()

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db		Database handler
		**/
		public function __construct($db)
		{
			global $langs, $mysoc;

			$langs->loadLangs(array('main', 'companies'));

			$this->db				= $db;
			$formatarray			= pdf_getFormat();
			$this->page_largeur		= $formatarray['width'];
			$this->page_hauteur		= $formatarray['height'];
			$this->format			= array($this->page_largeur, $this->page_hauteur);
			$this->marge_gauche		= getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
			$this->marge_droite		= getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
			$this->marge_haute		= getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
			$this->marge_basse		= getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);
			if (!is_object($mysoc) || empty($mysoc->name)) {
				// $mysoc is only loaded by main.inc.php : load it here for CLI / cron / test contexts (native method)
				global $conf;
				require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$mysoc	= new Societe($db);
				$mysoc->setMysoc($conf);
			}
			$this->emetteur			= $mysoc;
			if (empty($this->emetteur->country_code)) {
				$this->emetteur->country_code	= substr($langs->defaultlang, -2);	// By default if not defined
			}
		}

		/**
		*	Compute the output directory and file name of a unit, and create the directory
		*
		*	@param		CommonObject	$object			Object (child class of the module)
		*	@param		array			$unit			Generation unit (key 'suffix' used for the file name)
		*	@param		Translate		$outputlangs	Output language
		*	@return		array|false						array('dir' => , 'file' => full path, 'relative' => path relative to DOL_DATA_ROOT) or false
		**/
		protected function infrasfilesGetFile($object, $unit, $outputlangs)
		{
			if (empty($object->id) || empty($object->ref)) {
				$dir	= infrasfiles_get_output_dir($this->infrasfiles_element, null);
				$file	= $dir.'/SPECIMEN.pdf';
			} else {
				$dir	= $object->infrasfilesGetOutputDir();
				$file	= $dir.'/'.dol_sanitizeFileName($object->ref).(!empty($unit['suffix']) ? '-'.dol_sanitizeFileName($unit['suffix']) : '').'.pdf';
			}
			if (!file_exists($dir) && dol_mkdir($dir) < 0) {
				$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
				return false;
			}
			if (!is_writable($dir)) {
				$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
				return false;
			}
			return array('dir' => $dir, 'file' => $file, 'relative' => preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'\/?/', '', $file));
		}

		/**
		*	Create and set up the PDF instance (fonts, metadata, margins)
		*
		*	@param		Translate	$outputlangs	Output language
		*	@param		string		$title			Document title (metadata)
		*	@param		string		$subject		Document subject (metadata)
		*	@return		TCPDF|TCPDI					PDF instance
		**/
		protected function infrasfilesInitPdf($outputlangs, $title, $subject)
		{
			global $conf;

			$pdf				= pdf_getInstance($this->format);
			$default_font_size	= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
			$pdf->SetAutoPageBreak(0, 0);	// page breaks are managed by the models
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs));
			$this->infrasfiles_tplidx	= 0;
			if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND') && method_exists($pdf, 'setSourceFile')) {
				$background	= $conf->mycompany->dir_output.'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND');
				if (dol_is_file($background)) {
					$pagecount	= $pdf->setSourceFile($background);
					$this->infrasfiles_tplidx	= $pagecount ? $pdf->importPage(1) : 0;
				}
			}
			$pdf->Open();
			$pdf->SetDrawColor(128, 128, 128);
			$pdf->SetTitle($outputlangs->convToOutputCharset($title));
			$pdf->SetSubject($outputlangs->convToOutputCharset($subject));
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset(is_object($this->emetteur) ? $this->emetteur->name : ''));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($title));
			if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
				$pdf->SetCompression(false);
			}
			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
			$pdf->SetFont('', '', $default_font_size);
			return $pdf;
		}
		/**
		*	Add a page and apply the background PDF (MAIN_ADD_PDF_BACKGROUND) when one is loaded : to use instead of $pdf->AddPage()
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@return		void
		**/
		protected function infrasfilesAddPage(&$pdf)
		{
			$pdf->AddPage();
			if (!empty($this->infrasfiles_tplidx) && method_exists($pdf, 'useTemplate')) {
				$pdf->useTemplate($this->infrasfiles_tplidx, 0, 0, $this->page_largeur);
			}
		}

		/**
		*	Common page head : logo (or company name) on the left, title and information lines on the right
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@param		object		$object			Object
		*	@param		Translate	$outputlangs	Output language
		*	@param		string		$title			Title printed on the right
		*	@param		array		$rightlines		Information lines printed under the title : array(array(label, value), ...)
		*	@return		float						Y position after the head
		**/
		protected function infrasfilesWriteHead(&$pdf, $object, $outputlangs, $title, $rightlines = array())
		{
			global $conf;

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);
			// Watermark on draft documents (option WATERMARK of the object, empty = none)
			$isdraft	= (isset($object->statut) && (int) $object->statut == 0) || (isset($object->status) && (int) $object->status == 0);
			$watermark	= infrasfiles_get_option($this->infrasfiles_element, 'WATERMARK');
			if ($isdraft && $watermark !== '') {
				pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $watermark);
			}
			$posy	= $this->marge_haute;
			$posx	= $this->page_largeur - $this->marge_droite - 100;
			$ybottomleft	= $posy;
			// Logo or company name
			if ($this->option_logo && is_object($this->emetteur) && !empty($this->emetteur->logo)) {
				$logo	= $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
				if (is_readable($logo)) {
					$height	= pdf_getHeightForLogo($logo);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width = 0 (auto)
					$ybottomleft	= $posy + $height;
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->SetXY($this->marge_gauche, $posy);
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities('ErrorLogoFileNotFound', $logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities('ErrorGoToGlobalSetup'), 0, 'L');
					$ybottomleft	= $pdf->GetY();
				}
			} else {
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', 'B', $default_font_size + 3);
				$pdf->SetXY($this->marge_gauche, $posy);
				$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset(is_object($this->emetteur) ? $this->emetteur->name : ''), 0, 'L');
				$ybottomleft	= $pdf->GetY();
			}
			// Title and information on the right
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($title), '', 'R');
			$posy	= $pdf->GetY() + 1;
			$pdf->SetFont('', '', $default_font_size - 1);
			foreach ($rightlines as $line) {
				if (!isset($line[1]) || $line[1] === '') {
					continue;
				}
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($line[0].' : '.$line[1]), '', 'R');
				$posy	= $pdf->GetY();
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size);
			return max($posy, $ybottomleft) + 4;
		}

		/**
		*	Framed block with a title and text lines
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@param		Translate	$outputlangs	Output language
		*	@param		float		$x				X position
		*	@param		float		$y				Y position
		*	@param		float		$w				Width
		*	@param		string		$title			Block title
		*	@param		array		$lines			Text lines
		*	@return		float						Y position under the block
		**/
		protected function infrasfilesWriteBlock(&$pdf, $outputlangs, $x, $y, $w, $title, $lines)
		{
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetXY($x, $y);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($title), 0, 'L');
			$ytop	= $pdf->GetY();
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetXY($x + 2, $ytop + 1);
			$lines	= array_filter((array) $lines, function ($line) {
				return $line !== null && $line !== '';	// null-safe (a company field can be null) : strlen(null) is deprecated in PHP 8.1+
			});
			$pdf->MultiCell($w - 4, 4, $outputlangs->convToOutputCharset(implode("\n", $lines)), 0, 'L');
			$ybottom	= $pdf->GetY() + 1;
			$pdf->SetDrawColor(128, 128, 128);
			$pdf->Rect($x, $ytop, $w, $ybottom - $ytop);
			$pdf->SetFont('', '', $default_font_size);
			return $ybottom;
		}

		/**
		*	Table header row (grey background)
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@param		Translate	$outputlangs	Output language
		*	@param		float		$y				Y position
		*	@param		array		$columns		Columns : array(array('label' => , 'x' => , 'w' => , 'align' => 'L'|'R'|'C'))
		*	@param		float		$h				Row height
		*	@return		float						Y position under the header
		**/
		protected function infrasfilesWriteTableHeader(&$pdf, $outputlangs, $y, $columns, $h = 6)
		{
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$width	= $this->page_largeur - $this->marge_gauche - $this->marge_droite;
			$pdf->SetFillColor(230, 230, 230);
			$pdf->SetDrawColor(128, 128, 128);
			$pdf->Rect($this->marge_gauche, $y, $width, $h, 'DF');
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);
			foreach ($columns as $column) {
				$pdf->SetXY($column['x'], $y + 1);
				$pdf->MultiCell($column['w'], 4, $outputlangs->convToOutputCharset($column['label']), 0, $column['align']);
			}
			$pdf->SetFont('', '', $default_font_size - 1);
			return $y + $h;
		}

		/**
		*	Add a new page (with footer of the previous one and head of the new one) when the needed height does not fit
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@param		object		$object			Object
		*	@param		Translate	$outputlangs	Output language
		*	@param		float		$y				Current Y position
		*	@param		float		$needed			Needed height
		*	@param		string		$title			Head title (for the new page)
		*	@param		array		$columns		Table columns to repeat (empty = none)
		*	@return		float						Y position (unchanged or top of the new page)
		**/
		protected function infrasfilesCheckPageBreak(&$pdf, $object, $outputlangs, $y, $needed, $title, $columns = array())
		{
			if ($y + $needed <= $this->page_hauteur - $this->marge_basse - 15) {
				return $y;
			}
			$this->_pagefoot($pdf, $object, $outputlangs, 1);
			$this->infrasfilesAddPage($pdf);
			$y	= $this->infrasfilesWriteHead($pdf, $object, $outputlangs, $title, array());
			if (!empty($columns)) {
				$y	= $this->infrasfilesWriteTableHeader($pdf, $outputlangs, $y, $columns);
			}
			return $y;
		}

		/**
		*	Page footer (native pdf_pagefoot with the free text of the object)
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@param		object		$object			Object
		*	@param		Translate	$outputlangs	Output language
		*	@param		int			$hidefreetext	1 = hide free text
		*	@return		int							Height of the footer
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
		{
			$showdetails	= getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
			return pdf_pagefoot($pdf, $outputlangs, infrasfiles_const_name($this->infrasfiles_element, 'FREE_TEXT'), $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur);
		}

		/**
		*	Hook before the PDF creation
		*
		*	@param		object		$object			Object
		*	@param		Translate	$outputlangs	Output language
		*	@param		string		$file			Output file
		*	@return		void
		**/
		protected function infrasfilesBefore($object, $outputlangs, $file)
		{
			global $hookmanager, $action;

			if (!is_object($hookmanager)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager	= new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters	= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			$hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);
		}

		/**
		*	Close the PDF, write the file, run the after hook and register the result
		*
		*	@param		TCPDF		$pdf			PDF instance
		*	@param		array		$paths			Paths returned by infrasfilesGetFile()
		*	@param		object		$object			Object
		*	@param		Translate	$outputlangs	Output language
		*	@return		int							1 if OK, -1 if KO
		**/
		protected function infrasfilesFinish(&$pdf, $paths, $object, $outputlangs)
		{
			global $hookmanager, $action;

			if (method_exists($pdf, 'AliasNbPages')) {
				$pdf->AliasNbPages();
			}
			$pdf->Close();
			$pdf->Output($paths['file'], 'F');
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters	= array('file' => $paths['file'], 'object' => $object, 'outputlangs' => $outputlangs);
			$reshook	= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);
			if ($reshook < 0) {
				$this->error	= $hookmanager->error;
				$this->errors	= $hookmanager->errors;
				return -1;
			}
			dolChmod($paths['file']);
			$this->result			= array('fullpath' => $paths['file']);
			$object->last_main_doc	= $paths['relative'];
			return 1;
		}
	}
