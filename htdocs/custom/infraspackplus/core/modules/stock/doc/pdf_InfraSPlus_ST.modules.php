<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand 		- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/modules/stock/doc/pdf_InfraSPlus_ST.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF stock
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_stock.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	if (!empty( isModEnabled('infrasloc')))	dol_include_once('/infrasloc/core/lib/infrasloc.lib.php');
	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_ST extends ModelePDFStock
	{
		public $db;
		public $name;
		public $description;
		public $update_main_doc_field;	// Save the name of generated file as the main doc when generating a doc with this template
		public $type;
		public $phpmin	= array(7, 4);
		public $version	= 'dolibarr';
		public $page_largeur;
		public $page_hauteur;
		public $format;
		public $marge_gauche;
		public $marge_droite;
		public $marge_haute;
		public $marge_basse;
		public $emetteur;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $conf, $langs, $mysoc;

			$langs->loadLangs(array('main', 'dict', 'bills', 'companies', 'stocks', 'orders', 'deliveries', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->name							= $langs->trans('PDFInfraSPlusStockName');
			$this->description					= $langs->trans('PDFInfraSPlusStockDescription');
			$this->titlekey						= 'Warehouse';
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->defaulttemplate				= getDolGlobalString('STOCK_ADDON_PDF', '');
			$this->stock_val					= getDolGlobalInt('INFRASPLUS_PDF_WITH_STOCK_VAL_COLUMNS', 0);
			$this->larg_ref						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_REF', 28);
			$this->larg_qty						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_QTY', 10);
			$this->larg_pmp						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_PMP', 22);
			$this->larg_pmpt					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_PMPT', 24);
			$this->larg_up						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_PU', 22);
			$this->larg_totalht					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_TOT', 24);
			$this->num_ref						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_REF', 1);
			$this->num_desc						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_DESC', 2);
			$this->num_qty						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_QTY', 3);
			$this->num_pmp						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_PMP', 4);
			$this->num_pmpt						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_PMPT', 5);
			$this->num_up						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_PU', 6);
			$this->num_totalht					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_TOT', 7);
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@return	int							1 = OK, <= 0 KO
		**/
		public function write_file($object, $outputlangs) {
			global $user, $langs, $conf, $db, $hookmanager, $nblignes;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'companies', 'stocks', 'orders', 'deliveries', 'infraspackplus@infraspackplus'));
			$timeStamp						= dol_print_date(dol_now(), '%Y%m%d', false, $outputlangs, true);
			$filesufixe						= $timeStamp.(empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_ST') ? '' : '_ST');
			$baseDir						= !empty($conf->stock->multidir_output[$conf->entity]) ? $conf->stock->multidir_output[$conf->entity] : $conf->stock->dir_output;

			if (!empty($baseDir)) {
				$objectref	= dol_sanitizeFileName($object->ref);
				// Definition of $dir and $file
				if (preg_match('/specimen/i', $objectref)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				}
				else {
					$dir	= $baseDir.'/'.$objectref;
					$file	= $dir.'/'.$objectref.$filesufixe.'.pdf';
				}
				if (! file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error=$outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
						return 0;
					}
				}
				if (file_exists($dir)) {
					if (! is_object($hookmanager)) {	// Add pdfgeneration hook
						include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
						$hookmanager	= new HookManager($db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters			= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook			= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$this->logo			= !empty($hookmanager->resArray['logo']) ? $hookmanager->resArray['logo'] : '';
					$this->listnotep	= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied			= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->files		= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					$object->lines		= infraspackplus_get_list_product_warehouse($object->id);
					$nblignes			= is_array($object->lines) ? count($object->lines) : 0;
					// Create pdf instance
					$pdf				= pdf_InfraSPlus_getInstance($this->format, 'mm', 'P');
					$default_font_size	= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
					$pdf->SetAutoPageBreak(1, 0);
					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}
					$pdf->SetFont($this->font);
					// reduce the top margin before ol / il tag
					$tagvs					= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('Stock'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Stock').' '.$outputlangs->convToOutputCharset($object->label));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					$pagenb					= 1;
					// Default PDF parameters
					$this->stdLineW			= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash		= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor		= array(128, 128, 128);
					$this->stdLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineW			= $this->tblLineW; // épaisseur par défaut dans TCPDF = 0.2
					$this->bgLineDash		= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->bgLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->bgLineColor		= $this->bg_color;
					$this->bgLineStyle		= array('width'=>$this->bgLineW, 'dash'=>$this->bgLineDash, 'cap'=>$this->bgLineCap, 'color'=>$this->bgLineColor);
					$this->tblLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->tblLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
					$this->verLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->verLineColor);
					$this->horLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$this->signLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->signLineStyle	= array('width'=>$this->signLineW, 'dash'=>$this->signLineDash, 'cap'=>$this->signLineCap, 'color'=>$this->signLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// First loop on each lines to prepare calculs and variables
					$valpmpproducts			= 0;
					$valsellproducts		= 0;
					$valbuyproducts			= 0;
					$valbuyproduct			= array();
					$product_fourn			= new ProductFournisseur($db);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$valpmpproducts	+= price2num($object->lines[$i]->ppmp * $object->lines[$i]->qty, 'MT');
					//	if (empty($conf->global->PRODUIT_MULTIPRICES))	$valsellproducts	+= price2num($object->lines[$i]->price * $object->lines[$i]->qty, 'MT');
						if ($product_fourn->find_min_price_product_fournisseur($object->lines[$i]->rowid) > 0) {
							if ($product_fourn->product_fourn_price_id > 0) {
								$valbuyproduct[$i]	= $product_fourn->fourn_unitprice * (1 - $product_fourn->fourn_remise_percent / 100) + $product_fourn->fourn_remise;
								$valbuyproducts		+= price2num($valbuyproduct[$i] * $object->lines[$i]->qty, 'MT');
							}
						}
					}
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->stock_val)) {
						$this->larg_pmp		= 0;
						$this->larg_pmpt	= 0;
						$this->larg_totalht	= 0;
					}
					$this->larg_desc	= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_pmp + $this->larg_pmpt + $this->larg_up + $this->larg_totalht); // Largeur variable suivant la place restante
					$this->tableau		= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
												'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
												'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
												'pmp'		=> array('col' => $this->num_pmp,		'larg' => $this->larg_pmp,		'posx' => 0),
												'pmpt'		=> array('col' => $this->num_pmpt,		'larg' => $this->larg_pmpt,		'posx' => 0),
												'up'		=> array('col' => $this->num_up,		'larg' => $this->larg_up,		'posx' => 0),
												'totalht'	=> array('col' => $this->num_totalht,	'larg' => $this->larg_totalht,	'posx' => 0)
												);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->largcol1	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 2)	$this->largcol2	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 3)	$this->largcol3	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 4)	$this->largcol4	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 5)	$this->largcol5	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 6)	$this->largcol6	= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 7)	$this->largcol7	= $ncol_array['larg'];
					}
					$this->posxcol1	= $this->marge_gauche;
					$this->posxcol2	= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3	= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4	= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5	= $this->posxcol4	+ $this->largcol4;
					$this->posxcol6	= $this->posxcol5	+ $this->largcol5;
					$this->posxcol7	= $this->posxcol6	+ $this->largcol6;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->tableau[$ncol]['posx']	= $this->posxcol1;
						elseif ($ncol_array['col'] == 2)	$this->tableau[$ncol]['posx']	= $this->posxcol2;
						elseif ($ncol_array['col'] == 3)	$this->tableau[$ncol]['posx']	= $this->posxcol3;
						elseif ($ncol_array['col'] == 4)	$this->tableau[$ncol]['posx']	= $this->posxcol4;
						elseif ($ncol_array['col'] == 5)	$this->tableau[$ncol]['posx']	= $this->posxcol5;
						elseif ($ncol_array['col'] == 6)	$this->tableau[$ncol]['posx']	= $this->posxcol6;
						elseif ($ncol_array['col'] == 7)	$this->tableau[$ncol]['posx']	= $this->posxcol7;
					}
					// Calculs de positions
					$this->tab_hl		= 4;
					$this->heightline	= $this->tab_hl;
					$this->decal_round	= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head				= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead		= $head['totalhead'];
					$tab_top			= $hauteurhead + 5;
					$tab_top_newpage	= (empty($this->small_head2) ? $hauteurhead : 17);
					$this->ht_top_table	= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1) + $this->heightline;
					$pdf->SetFont('', '', $default_font_size - 1);
					// Indication de location
					if (!empty( isModEnabled('infrasloc'))) {
						$leadID	= infrasloc_getOriginLead($object->id);
						if ($leadID > 0) {
							$txtloc	= '<b>'.$outputlangs->transnoentities('PDFInfraSPlusTxtLoc').' : </b>';
							$pdf->writeHTMLCell($this->larg_util_txt / 2, $this->tab_hl, $this->posx_G_txt, $tab_top, $txtloc, 0, 1, false, true, 'L', true);
							$tab_top	+= $this->tab_hl;
						}
					}
					// Details des quantités à gauche
					$calcproductsunique	= $object->nb_different_products();
					$txtproductsunique	= '<b>'.$outputlangs->transnoentities('NumberOfDifferentProducts').' : </b>'.(empty($calcproductsunique['nb']) ? '0' : price2num($calcproductsunique['nb'], 'MS'));
					$pdf->writeHTMLCell($this->larg_util_txt / 2, $this->tab_hl, $this->posx_G_txt, $tab_top, $txtproductsunique, 0, 1, false, true, 'L', true);
					$calcproducts		= $object->nb_products();
					$txtproducts		= '<b>'.$outputlangs->transnoentities('NumberOfProducts').' : </b>'.(empty($calcproducts['nb']) ? '0' : price2num($calcproducts['nb'], 'MS'));
					$pdf->writeHTMLCell($this->larg_util_txt / 2, $this->tab_hl, $this->posx_G_txt, $tab_top + $this->tab_hl, $txtproducts, 0, 1, false, true, 'L', true);
					// Valorisation à droite
					$txtvalpmpproducts	= '<b>'.$outputlangs->transnoentities('EstimatedStockValue').' : </b>'.pdf_InfraSPlus_price($object, (empty($valpmpproducts) ? '0' : price2num($valpmpproducts, 'MT')), $outputlangs, 1, 0, 'U');
					$pdf->writeHTMLCell($this->larg_util_txt / 2, $this->tab_hl, $this->posx_G_txt + ($this->larg_util_txt / 2), $tab_top, $txtvalpmpproducts, 0, 1, false, true, 'R', true);
					$txtvalbuyproducts	= '<b>'.$outputlangs->transnoentities('PDFInfraSPlusEstimatedStockValue').' : </b>'.pdf_InfraSPlus_price($object, (empty($valbuyproducts) ? '0' : price2num($valbuyproducts, 'MT')), $outputlangs, 1, 0, 'U');
					$pdf->writeHTMLCell($this->larg_util_txt / 2, $this->tab_hl, $this->posx_G_txt + ($this->larg_util_txt / 2), $tab_top + $this->tab_hl, $txtvalbuyproducts, 0, 1, false, true, 'R', true);
				//	if (empty($conf->global->PRODUIT_MULTIPRICES)) {
				//		$txtvalsellproducts		= '<b>'.$outputlangs->transnoentities('EstimatedStockValueSellShort').' : </b>'.pdf_InfraSPlus_price($object, (empty($valsellproducts) ? '0' : price2num($valsellproducts, 'MT')), $outputlangs, 1, 0, 'U');
				//		$pdf->writeHTMLCell($this->larg_util_txt / 2, $this->tab_hl, $this->posx_G_txt + ($this->larg_util_txt / 2), $tab_top + $this->tab_hl * 2, $txtvalsellproducts, 0, 1, false, true, 'R', true);
				//	}
					$tab_top			+= $this->tab_hl * 3;
					$nexY				= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$curY								= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table))	$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						else								$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore						= $pdf->getPage();
						$showpricebeforepagebreak			= 1;
						// Hauteur de ligne
						$this->heightline					= $this->tab_hl;
						// Hauteur de la Reference
						$pdf->startTransaction();
						$startline							= $pdf->GetY();
						$ref								= pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails);
						$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
						$endline							= $pdf->GetY();
						$this->heightline					= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
						$pdf->rollbackTransaction(true);
						// Description of product line
						$pageposdesc						= $pdf->getPage();
						$pdf->writeHTMLCell($this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, dol_trunc($object->lines[$i]->produit, $this->tableau['desc']['larg'] / 2), 0, 1, false, true, $this->force_align_left_ref, true);
						$pageposafter						= $pdf->getPage();
						$posyafter							= $pdf->GetY();
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
								}
							}
							else	$showpricebeforepagebreak	= 0;
						}
						elseif ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposafter + 1);
							}
						}
						$nexY			= $pdf->GetY();
						$pageposafter	= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							if ($curY > ($this->page_hauteur - $heightforfooter - $this->tab_hl)) {
								$pdf->setPage($pageposafter);
								$curY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
							}
							else	$pdf->setPage($pageposdesc);
						}
						$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
						// Reference
						$pagepos	= $pdf->getPage();
						$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
						$pdf->setPage($pagepos);
						// Quantity
						$qty	= price2num(pdf_getlineqty($object, $i, $outputlangs, $hidedetails), 'MS');
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
					//	if (empty($conf->global->PRODUIT_MULTIPRICES)) {
					//		// Price sell min
					//		$up_line	= pdf_InfraSPlus_price($object, $object->lines[$i]->price, $outputlangs, 0, 0, 'U');
					//		$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, $up_line, '', 'R', 0, 1, $this->tableau['up']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
					//	}

						// Best buy price
						if (!empty($valbuyproduct[$i])) {
							$up_line	= pdf_InfraSPlus_price($object, $valbuyproduct[$i], $outputlangs, 0, 0, 'U');
							$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, $up_line, '', 'R', 0, 1, $this->tableau['up']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}

						if (!empty($this->stock_val)) {
							// PMP unitaire
							$pmp_line	= pdf_InfraSPlus_price($object, $object->lines[$i]->ppmp, $outputlangs, 0, 0, 'U');
							$pdf->MultiCell($this->tableau['pmp']['larg'], $this->heightline, $pmp_line, '', 'R', 0, 1, $this->tableau['pmp']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							// PMP total
							$pmpt_line	= pdf_InfraSPlus_price($object, $object->lines[$i]->ppmp * $object->lines[$i]->qty, $outputlangs, 0, 0, 'U');
							$pdf->MultiCell($this->tableau['pmpt']['larg'], $this->heightline, $pmpt_line, '', 'R', 0, 1, $this->tableau['pmpt']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
					//		if (empty($conf->global->PRODUIT_MULTIPRICES)) {
					//			// Total sell min
					//			$total_line	= pdf_InfraSPlus_price($object, $object->lines[$i]->price * $object->lines[$i]->qty, $outputlangs, 0, 0, 'U');
					//			$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
					//		}

							// Total Best buy price
							if (!empty($valbuyproduct[$i])) {
								$total_line	= pdf_InfraSPlus_price($object, $valbuyproduct[$i] * $object->lines[$i]->qty, $outputlangs, 0, 0, 'U');
								$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}

						}
						// Add dash or space between line
						$separate	= pdf_InfraSPlus_separateLine ($object, $i);
						if ($separate == -1) {
							if (!empty($this->dash_between_line) && $i < ($nblignes - 1)) {
								$pdf->setPage($pageposafter);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
								$nexY	+= 2;
							}
							else	$nexY	+= $this->lineSep_hight;
						}
						else	$nexY	+= $separate;
						// Detect if some page were added automatically and output _pagefoot for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)				$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							else							$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)				$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							else							$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							// New page
							$pdf->AddPage();
							$pagenb++;
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							$nexY							= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
						}
					}
					$bottomlasttab		= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1)	$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					else				$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $pagenb);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages'))	$pdf->AliasNbPages();
					// if merge files is active
					if (!empty($this->files))					pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters									= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook									= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if (!empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
					$this->result					= array('fullpath' => $file);
					return 1;	// Pas d'erreur
				}
				else {
					$this->error=$outputlangs->trans('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}
			else {
				$this->error=$outputlangs->trans('ErrorConstantNotDefined', 'PRODUCT_OUTPUTDIR');
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		PDF			$pdf			Object PDF
		*	@param		Object		$object		Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		array		$hauteurhead	'totalhead'		= hight of header
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs) {
			global $conf, $hookmanager;

			$specialHead	= infraspackplus_fetchAllSpecialHeads(array($object->element));
			if (!empty($specialHead['rootFileName'])) {
				$specialhead	= 'pdf_'.$specialHead['rootFileName'].'_pagehead';
				$hauteurhead	= $specialhead($pdf, $object, $showaddress, $outputlangs, $this->headertxtcolor, $this->header_align_left, $this->decal_round, $this->formatpage, $this->logo, $this->emetteur, $this->tab_hl,
												$this->header_after_addr, $this->title_size, $this->titlekey, $this->ref_from_cust, $this->datesbold, $this->dates_br, $this->show_num_cli, $this->num_cli_frm,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, $this->add_creator_in_header, $this->use_iso_location, $this->adr, $this->typeadr, $this->adrlivr, $this->Rounded_rect,
												$this->customerAddrSelect, -2, -2, '', 0, array(), '', -2, 0, $this->left_recep_corner, $this->top_recep_corner, 0);
				return $hauteurhead;
			}
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$dimCadres			= array ('S' => ($this->page_largeur - ($this->marge_gauche + 6 + $this->left_recep_corner + $this->marge_droite)), 'R' => $this->left_recep_corner);	// page width = 210 (A4) 92 + 92  = 184 => keep 210 - 184 for margins => 26 ; 10 right and left and 6 on the middle
			$w					= $this->header_align_left ? 92 - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $dimCadres['S'], $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $object->entity);
			// Name left
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$pdf->MultiCell($dimCadres['S'], $this->tab_hl * $this->title_size, $this->emetteur->name, '', 'L', 0, 1, $this->marge_gauche, $posy + $heightLogo, true, 0, 0, false, 0, 'M', false);
			$heightLogo			= $pdf->GetY();
			// Title right
			$title				= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->convToOutputCharset($object->ref);
			$pdf->MultiCell($w, $this->tab_hl * $this->title_size, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy				= $pdf->GetY();
			// Date right
			$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
			$txtdt				= $outputlangs->transnoentities('Date').' : '.dol_print_date(dol_now(), 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy				+= $this->tab_hl - 0.5;
			// End
			$pdf->SetFont('', '', $default_font_size - 2);
			$posy				+= 0.5;
			$hauteurhead		= array('totalhead'		=> ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			return $hauteurhead;
		}

		/**
		*	Show top small header of page.
		*
		*	@param		PDF			$pdf			Object PDF
		*	@param		Object		$object		Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		void
		**/
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs) {
			global $conf, $hookmanager;

			$fromcompany	= $this->emetteur;
			$title			= $outputlangs->transnoentities($this->titlekey);	// $titlekey);
			pdf_InfraSPlus_pagesmallhead($pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $this->formatpage, $this->decal_round, $this->logo, $this->headertxtcolor);
		}

		/**
		*	Show table for lines
		*
		*	@param		PDF			$pdf			Object PDF
		*	@param		Object		$object		Object to show
		*	@param		string		$tab_top		Top position of table
		*	@param		string		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@return		void
		**/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb) {
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom				= 0;
			if (!empty($hidetop))	$hidetop	= -1;
			$default_font_size		= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				if ($pagenb == 1) {
					$infocurrency	= $outputlangs->transnoentities('AmountInCurrency', $outputlangs->transnoentitiesnoconv('Currency'.$conf->currency));
					$pdf->MultiCell($pdf->GetStringWidth($infocurrency) + 3, 2, $infocurrency, '', 'R', 0, 1, $this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($infocurrency) + 3) - $this->decal_round, $tab_top - $this->tab_hl, true, 0, 0, false, 0, 'M', false);
				}
				// Table header
				if (!empty($this->title_bg))			$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				else if (!empty($this->showtblline))	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				// Table frame
				if (!empty($this->showtblline))			$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				else									$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
			}
			else
				if ($this->showtblline)	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			if (!empty($this->showverline)) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol2,		$tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol3,		$tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol4,		$tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol5,		$tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol6,		$tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol7 > $this->posxcol6 && $this->posxcol7 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol7,		$tab_top, $this->posxcol7,	$tab_top + $tab_height, $this->verLineStyle);
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Qty'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('BuyingPrice'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->stock_val)) {
					$pdf->MultiCell($this->tableau['pmp']['larg'], $this->ht_top_table, $outputlangs->transnoentities("AverageUnitPricePMPShort"), '', 'R', 0, 1, $this->tableau['pmp']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['pmpt']['larg'], $this->ht_top_table, $outputlangs->transnoentities("EstimatedStockValueShort"), '', 'R', 0, 1, $this->tableau['pmpt']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities("PDFInfraSPlusEstimatedStockValue"), '', 'R', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		PDF			$pdf			The PDF factory
		*	@param		Object		$object			Object shown in PDF
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int							Return height of bottom margin including footer text
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul)
		{
			$showdetails	= $this->type_foot.(!empty($this->pied) ? 1 : 0);
			$specialFoot	= infraspackplus_fetchAllSpecialFooters(array($object->element));
			if (!empty($specialFoot['rootFileName'])) {
				$specialFoot	= 'pdf_'.$specialFoot['rootFileName'].'_pagefoot';
				$hauteurfoot	= $specialFoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
				return $hauteurfoot;
			}
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
