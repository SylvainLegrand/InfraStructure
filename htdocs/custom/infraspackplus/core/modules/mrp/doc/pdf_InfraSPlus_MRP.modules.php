<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/modules/commande/doc/pdf_InfraSPlus_MRP.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF Fabrication order
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/modules/mrp/modules_mo.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_MRP extends ModelePDFMo
	{
		public $db;
		public $name;
		public $description;
		public $update_main_doc_field;	// Save the name of generated file as the main doc when generating a doc with this template
		public $type;
		public $phpmin				= array(7, 4);
		public $version				= 'dolibarr';
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

			$langs->loadLangs(array('main', 'dict', 'bills', 'orders', 'products', 'productbatch', 'mrp', 'companies', 'projects', 'deliveries', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->name						= $langs->trans('PDFInfraSPlusMRPName');
			$this->description				= $langs->trans('PDFInfraSPlusOFDescription');
			$this->titlekey					= 'PDFInfraSPlusMRPTitle';
			$this->defaulttemplate			= getDolGlobalString('MRP_MO_ADDON_PDF', '');
			$this->draft_watermark			= getDolGlobalString('MRP_MO_DRAFT_WATERMARK', '');
			$this->show_serial				= getDolGlobalInt('INFRASPLUS_PDF_SHOW_SERIAL_ON_MRP', 0);
			$this->dim_col					= getDolGlobalInt('INFRASPLUS_PDF_MRP_WITH_DIM_COLUMNS', 0);
			$this->larg_ref					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_REF', 28);
			$this->larg_qty					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_QTY', 10);
			$this->larg_qtyTot				= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_QTYTOT', 22);
			$this->larg_unit				= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_UNIT', 10);
			$this->larg_dim					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_DIM', 24);
			$this->num_ref					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_REF', 1);
			$this->num_desc					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_DESC', 2);
			$this->num_qty					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_QTY', 3);
			$this->num_qtyTot				= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_QTYTOT', 4);
			$this->num_unit					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_UNIT', 5);
			$this->num_dim					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_DIM', 6);
			$this->heightControlTable		= getDolGlobalInt('INFRASPLUS_PDF_HEIGHT_MRP_CONTROL_TABLE', 50);
			$this->ExfBulleted				= getDolGlobalInt('INFRASPLUS_PDF_EXF_BULLETED', 0);
			$this->option_logo				= 1; // Display logo
			$this->option_multilang			= 1; //Available in several languages
			$this->option_escompte			= 0; // Displays if there has been a discount
			$this->option_credit_note		= 0; // Support credit notes
			$this->option_freetext			= 1; // Support add of a personalised text
			$this->option_draft_watermark	= 1; // Support add of a watermark on drafts
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		Object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return	int									1=OK, 0=KO
		**/
		public function write_file($object, $outputlangs = '', $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
		{
			global $user, $langs, $conf, $db, $hookmanager, $outputlangsbis;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'orders', 'products', 'productbatch', 'mrp', 'companies', 'projects', 'deliveries', 'infraspackplus@infraspackplus'));
			$outputlangsbis					= null;
			if (!empty($this->multilangsBis) && $outputlangs->defaultlang != $this->multilangsBis) {
				$outputlangsbis	= new Translate('', $conf);
				$outputlangsbis->setDefaultLang($this->multilangsBis);
				$outputlangsbis->loadLangs(array('main', 'dict', 'bills', 'orders', 'products', 'productbatch', 'mrp', 'companies', 'projects', 'infraspackplus@infraspackplus'));
			}
			$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_BOM') ? '' : '_BOM';
			$baseDir	= !empty($conf->mrp->multidir_output[$conf->entity]) ? $conf->mrp->multidir_output[$conf->entity] : $conf->mrp->dir_output;
			$nblines	= count($object->lines);
			$hidetop	= getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE', '0');
			// Loop on each lines to detect if there is at least one image to show
			$realpatharray	= array();
			if (!empty($baseDir)) {
				$object->fetch_thirdparty();
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				}
				else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/'.$objectref;
					$file		= $dir.'/'.$objectref.$filesufixe.'.pdf';
				}
				if (! file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
						return 0;
					}
				}
				if (file_exists($dir)) {
					if (! is_object($hookmanager)) {	// Add pdfgeneration hook
						include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
						$hookmanager	= new HookManager($db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters					= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook					= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$this->logo					= !empty($hookmanager->resArray['logo']) ? $hookmanager->resArray['logo'] : '';
					$this->adr					= !empty($hookmanager->resArray['adr']) ? $hookmanager->resArray['adr'] : '';
					$this->customerAddrSelect	= !empty($hookmanager->resArray['customerAddrSelect']) ? $hookmanager->resArray['customerAddrSelect'] : '';
					$this->adrlivr				= !empty($hookmanager->resArray['adrlivr']) ? $hookmanager->resArray['adrlivr'] : '';
					$this->listfreet			= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->files				= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					if (!isset($object->lines) || !is_array($object->lines)) {
						$object->lines	= array();
						$bom			= new BOM($db);
						$bom->fetch($object->fk_bom);
						$nblignes		= count($bom->lines);
						$frombom		= 1;
					}
					else {
						// First loop on each lines to prepare calculs and variables
						$nblignes		= count($object->lines);	// Set nblignes with the new object lines content after hook
						$frombom		= 0;
						$linesToUse		= array();
						for ($i = 0 ; $i < $nblignes ; $i++) {
							if (empty($frombom) && (($object->status >= 3 && $object->lines[$i]->role != 'consumed') || ($object->status < 3 && $object->lines[$i]->role != 'toconsume')))	continue;
							else																																							$linesToUse[]	= $object->lines[$i];
						}
						$nblignes	= count($linesToUse);	// Set nblignes with the lines content to use
					}
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
					$pdf->SetSubject($outputlangs->transnoentities('ManufacturingOrder'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('ManufacturingOrder').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
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
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->product_use_unit))	$this->larg_unit	= 0;
					if (empty($this->dim_col))			$this->larg_dim		= 0;
					$this->larg_desc					= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_qtyTot + $this->larg_unit + $this->larg_dim); // Largeur variable suivant la place restante
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
											'qtytot'	=> array('col' => $this->num_qtyTot,	'larg' => $this->larg_qtyTot,	'posx' => 0),
											'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
											'dim'		=> array('col' => $this->num_dim,		'larg' => $this->larg_dim,		'posx' => 0)
											);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->largcol1		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 2)	$this->largcol2		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 3)	$this->largcol3		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 4)	$this->largcol4		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 5)	$this->largcol5		= $ncol_array['larg'];
						elseif ($ncol_array['col'] == 6)	$this->largcol6		= $ncol_array['larg'];
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3		= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4		= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5		= $this->posxcol4	+ $this->largcol4;
					$this->posxcol6		= $this->posxcol5	+ $this->largcol5;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1)		$this->tableau[$ncol]['posx']	= $this->posxcol1;
						elseif ($ncol_array['col'] == 2)	$this->tableau[$ncol]['posx']	= $this->posxcol2;
						elseif ($ncol_array['col'] == 3)	$this->tableau[$ncol]['posx']	= $this->posxcol3;
						elseif ($ncol_array['col'] == 4)	$this->tableau[$ncol]['posx']	= $this->posxcol4;
						elseif ($ncol_array['col'] == 5)	$this->tableau[$ncol]['posx']	= $this->posxcol5;
						elseif ($ncol_array['col'] == 6)	$this->tableau[$ncol]['posx']	= $this->posxcol6;
					}
					// Define width and position of secondary tables columns
					$this->larg_tabtotal	= ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 10) / 2;
					$this->larg_tabinfo		= $this->larg_tabtotal;
					$this->posxtabtotal		= $this->page_largeur - $this->marge_droite - $this->larg_tabtotal;
					// Calculs de positions
					$this->tab_hl			= 4;
					$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head					= $this->_pagehead($pdf, $object, 0, $outputlangs);
					$hauteurhead			= $head['totalhead'];
					$hauteurcadre			= $head['hauteurcadre'];
					$tab_top				= $hauteurhead + 5;
					$tab_top_newpage		= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforheader		= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
					$ht_coltotal			= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle);
					$ht_coltotal			+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
					$heightforinfotot		= $ht_coltotal;
					$heightforfooter		= $this->_pagefoot($pdf, $object, $outputlangs, 1);

					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note			= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, 0);
					$tab_top				+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY					= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					for ($i = 0; $i < $nblignes; $i++) {
						$prod								= new Product($db);
						$prod->fetch(!empty($frombom) ? $bom->lines[$i]->fk_product : $linesToUse[$i]->fk_product);
						$curY								= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table))	$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						else								$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore						= $pdf->getPage();
						$showpricebeforepagebreak			= 1;
						// Hauteur de la référence
						$this->heightline					= $this->tab_hl;
						// Reference
						$pdf->startTransaction();
						$startline							= $pdf->GetY();
						$ref								= $prod->ref.' '.$nblignes2;
						$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
						$endline							= $pdf->GetY();
						$heightRef							= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
						$this->heightline					= $heightRef;
						$pdf->rollbackTransaction(true);
						// Description of product line => printing
						$pageposdesc						= $pdf->getPage();
						$libelleproduitservice				= $this->labelbold && !empty($prod->label) ? '<b>'.$prod->label.'</b>' : $prod->label;
						// Description long of product line
						if (!empty($prod->description)) {
							if (!empty($libelleproduitservice))	$libelleproduitservice	.= '__N__';
							// Check if description must be output for this kind of document
							if (!empty($object->element)) {
								$tmpkey	= 'MAIN_DOCUMENTS_HIDE_DESCRIPTION_FOR_'.strtoupper($object->element);
								if (getDolGlobalString($tmpkey, ''))	$hidedesc	= 1;
							}
							if (empty($hidedesc)) {
								if (!empty($this->descFirst))	$libelleproduitservice	= $prod->description.'__N__'.$libelleproduitservice;
								else {
									if (!empty($this->hidelblvariant) && $prod->isVariant())	$libelleproduitservice	= $prod->description;
									else														$libelleproduitservice	.= $prod->description;
								}
							}
						}
						// N° de série - gestion Dolibarr
						if (!empty($this->show_serial) && empty($frombom) && !empty($linesToUse[$i]->batch)) {
							$libelleproduitservice .= '__N__'.$outputlangs->trans('printBatch', $linesToUse[$i]->batch);
						}
						if (dol_textishtml($libelleproduitservice))	$libelleproduitservice	= preg_replace('/__N__/', '<br>', $libelleproduitservice);
						else										$libelleproduitservice	= preg_replace('/__N__/', "\n", $libelleproduitservice);
						$libelleproduitservice						= dol_htmlentitiesbr($libelleproduitservice, 1);
						$libelleproduitservice						= pdf_InfraSPlus_formatNotes($object, $outputlangs, $libelleproduitservice);	// enable the use of an image in description
						$pdf->writeHTMLCell($this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1, false, true, 'J', true);
						$pageposafter								= $pdf->getPage();
						$posyafter									= $pdf->GetY();
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
						$nexY	= $pdf->GetY();
						$pageposafter	= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							if ($curY > ($this->page_hauteur - $heightforfooter - $this->tab_hl)) {
								$pdf->setPage($pageposafter);
								$curY	= $heightforheader;
							}
							else	$pdf->setPage($pageposdesc);
						}
						$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
						// Reference
						$pagepos	= $pdf->getPage();
						$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
						$pdf->setPage($pagepos);
						// Quantity
						$qty	= !empty($frombom) ? $bom->lines[$i]->qty : $linesToUse[$i]->qty / $object->qty;
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Quantity total
						$qtytot	= $object->qty * $qty;
						$pdf->MultiCell($this->tableau['qtytot']['larg'], $this->heightline, $qtytot, '', 'R', 0, 1, $this->tableau['qtytot']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Unit
						if (!empty($this->product_use_unit)) {
							$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
							$pdf->writeHTMLCell($this->tableau['unit']['larg'], $this->heightline, $this->tableau['unit']['posx'], $curY, $unit, 0, 1, false, true, $this->force_align_left_unit, true);
						}
						// Dimensions
						$dims	= array_filter(array($prod->length, $prod->width, $prod->height));
						$dim	= implode('x', $dims);
						$pdf->MultiCell($this->tableau['dim']['larg'], $this->heightline, $dim, '', 'R', 0, 1, $this->tableau['dim']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
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
						// Detect if some page were added automatically and output header, table and footer for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)				$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							elseif ($pagenb > 1)			$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$heightforfooter		= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1)		$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							elseif ($pagenb > 1)	$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							// New page
							$pdf->AddPage();
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
							$pagenb++;
							if (empty($this->small_head2))	$this->_pagehead($pdf, $object, 0, $outputlangs);
							else							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							$nexY							= $heightforheader;
						}
					}
					$bottomlasttab			= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1)		$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					elseif ($pagenb > 1)	$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $pagenb);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$posyfreetext			= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $bottomlasttab, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle);
					$posysignarea			= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 0);
					$posy					= $posysignarea;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages'))	$pdf->AliasNbPages();
					// if merge files is active
					if (!empty($this->files))					pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters									= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'fromInfraS' => 1);
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
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}
			else {
				$this->error	= $outputlangs->transnoentities('ErrorConstantNotDefined', 'OM_OUTPUTDIR');
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
		*											'hauteurcadre	= hight of frame
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{
			global $conf, $db, $hookmanager;

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$dimCadres			= array ('S' => ($this->page_largeur - ($this->marge_gauche + 6 + $this->left_recep_corner + $this->marge_droite)), 'R' => $this->left_recep_corner);	// page width = 210 (A4) 92 + 92  = 184 => keep 210 - 184 for margins => 26 ; 10 right and left and 6 on the middle
			$w					= $this->header_align_left ? 92 - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $object->entity);
			$heightLogo			+= $posy + $this->tab_hl;
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$title	= $outputlangs->transnoentities($this->titlekey);
			$pdf->MultiCell($w, $this->tab_hl * $this->title_size, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$posy	= $pdf->getY();
			$txtref	= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
			if ($object->status == 0) {
				$pdf->SetTextColor(128, 0, 0);
				$txtref .= ' - '.$outputlangs->transnoentities('NotValidated');
			}
			$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
			$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
			$posy				+= $this->tab_hl;
			$txtdt				= !empty($object->date_approve) ? $outputlangs->transnoentities('MoDate').' : '.dol_print_date($object->date_approve, 'day', false, $outputlangs, true) : $outputlangs->transnoentities('ToApprove');
			$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			// CLient Name
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$carac_client_name	= $outputlangs->transnoentities('Customer').' : '.pdf_InfraSPlus_Build_Third_party_Name($object->thirdparty, $outputlangs, 0, '', '');
			$posy				+= $this->tab_hl;
			$pdf->MultiCell($w, $this->tab_hl, $carac_client_name, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			// product info
			$pdf->SetFont('', '', $default_font_size - 2);
			if (!empty($object->fk_product)) {
				// Référence
				$prodToMake		= new Product($db);
				$prodToMake->fetch($object->fk_product);
				// Dimensions
			//	$posy			+= $this->tab_hl - 0.5;
			//	$dims			= array_filter(array($prodToMake->length, $prodToMake->width, $prodToMake->height));
			//	$dim			= implode('x', $dims);
			//	$size			= $outputlangs->transnoentities('Size').' : '.$dim;
			//	$pdf->MultiCell($w, $this->tab_hl, $size, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				// Qty to produce
				$posy			+= $this->tab_hl + 0.5;
				$qtyToProduce	= $outputlangs->transnoentities('QtyToProduce').' : '.$object->qty;
				$pdf->MultiCell($w, $this->tab_hl, $qtyToProduce, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$posy			+= $this->tab_hl + 0.5;
				// Left side - we start just after the Logo
				$ref			= $outputlangs->transnoentities('PDFInfraSPlusArticleNumber').' : '.$prodToMake->ref;
				$pdf->MultiCell($w, $this->tab_hl, $ref, '', 'L', 0, 1, $this->marge_gauche, $heightLogo, true, 0, 0, false, 0, 'M', false);
				if (!empty($prodToMake->label)) {
					$heightLogo	+= $this->tab_hl - 0.5;
					$label	= $outputlangs->transnoentities('Designation').' : '.$prodToMake->label;
					$pdf->MultiCell($w, $this->tab_hl, $label, '', 'L', 0, 1, $this->marge_gauche, $heightLogo, true, 0, 0, false, 0, 'M', false);
				}
				$heightLogo	+= $this->tab_hl - 0.5;
			}
			// Show list of linked objects
			$posy						= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $this->tab_hl, $align);
			// Extrafields
			$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_MRP', 0);
			$extraDet					= empty($show_ExtraFields_in_notes) ? pdf_InfraSPlus_ExtraFieldsInNotes($object, $this->exftxtcolor, $outputlangs, $this->ExfBulleted) : '';
			if (!empty($extraDet))		$heightLogo	= pdf_InfraSPlus_writeNotes($pdf, $w, $this->tab_hl, $this->marge_gauche, $heightLogo, $extraDet, $this->horLineStyle, 0);
			// Date end planned
			$date						=  $outputlangs->transnoentities('DeliveryDate').' : '.dol_print_date($object->date_end_planned, 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $this->tab_hl, $date, '', 'L', 0, 1, $this->marge_gauche, $heightLogo, true, 0, 0, false, 0, 'M', false);
			$heightLogo					+= 0.5;
			$dimCadres['Y']				= ($this->use_iso_location && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			$hauteurhead				= array('totalhead'		=> $dimCadres['Y'],
												'hauteurcadre'	=> 0,
												);
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
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs)
		{
			global $conf, $hookmanager;

			$fromcompany	= $this->emetteur;
			$title			= $outputlangs->transnoentities($this->titlekey);
			pdf_InfraSPlus_pagesmallhead($pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $this->formatpage, $this->decal_round, $this->logo, $this->headertxtcolor);
		}

		/**
		*	Show table for lines
		*
		*	@param		PDF			$pdf			Object PDF
		*	@param		Object		$object		Object to show
		*	@param		float		$tab_top		Top position of table
		*	@param		float		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@return		void
		**/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb)
		{
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom				= 0;
			if (!empty($hidetop))	$hidetop	= -1;
			$currency				= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
			$default_font_size		= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				// Table header
				if (!empty($this->title_bg))			$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				else if (!empty($this->showtblline))	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				// Table frame
				if (!empty($this->showtblline))			$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				else									$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
			}
			else if (!empty($this->showtblline))	$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			if ($object->statut == Mo::STATUS_DRAFT && (!empty($this->draft_watermark))) {
				if (empty($hidetop))	pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + $this->ht_top_table + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				else					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			}
			// Show Folder mark
			if (!empty($this->fold_mark)) {
				$pdf->Line(0, ($this->page_hauteur)/3, $this->fold_mark, ($this->page_hauteur)/3, $this->stdLineStyle);
				$pdf->Line($this->page_largeur - $this->fold_mark, ($this->page_hauteur)/3, $this->page_largeur, ($this->page_hauteur)/3, $this->stdLineStyle);
			}
			if (!empty($this->showverline)) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol2,		$tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol3,		$tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol4,		$tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol5,		$tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->verLineStyle);
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre))		$pdf->line($this->posxcol6,		$tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->verLineStyle);
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Qty'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['qtytot']['larg'], $this->ht_top_table, $outputlangs->transnoentities('QtyTot'), '', 'C', 0, 1, $this->tableau['qtytot']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->product_use_unit))	$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Unit'), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['dim']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Size'), '', 'C', 0, 1, $this->tableau['dim']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
			}
		}

		/**
		*	Show area for the customer to sign
		*
		*	@param		PDF			$pdf			Object PDF
		*	@param		Facture		$object		Object invoice
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@return		int							Position pour suite
		**/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0, $freetext = 0)
		{
			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$signarea_top		= $posy + 1;
			$posxsignarea1		= $this->marge_gauche;
			$posxsignarea2		= $this->posxtabtotal;
			$larg_signarea		= $this->larg_tabtotal;
			$signarea_hl		= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->RoundedRect($this->marge_gauche, $signarea_top + $signarea_hl, $this->larg_util_cadre, $this->heightControlTable - $this->tab_hl, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			$pdf->line($this->marge_gauche, $signarea_top + ($signarea_hl * 3), $this->marge_gauche + $this->larg_util_cadre, $signarea_top + ($signarea_hl * 3), $this->signLineStyle);
			$pdf->line($this->marge_gauche + ($this->larg_util_cadre /4), $signarea_top + $signarea_hl, $this->marge_gauche + ($this->larg_util_cadre /4), $signarea_top + $signarea_hl + $this->heightControlTable - $this->tab_hl, $this->signLineStyle);
			$pdf->line($this->marge_gauche + ($this->larg_util_cadre /2), $signarea_top + $signarea_hl, $this->marge_gauche + ($this->larg_util_cadre /2), $signarea_top + $signarea_hl + $this->heightControlTable - $this->tab_hl, $this->signLineStyle);
			$pdf->line($this->marge_gauche + ($this->larg_util_cadre /4 * 3), $signarea_top + $signarea_hl, $this->marge_gauche + ($this->larg_util_cadre /4 * 3), $signarea_top + $signarea_hl + $this->heightControlTable - $this->tab_hl, $this->signLineStyle);
			$pdf->MultiCell($this->larg_util_cadre /4, $signarea_hl * 2, $outputlangs->transnoentities('PDFInfraSPlusControlDate'), '', 'C', 0, 1, $this->marge_gauche, $signarea_top + $signarea_hl, true, 0, false, true, $signarea_hl * 2, 'M', false);
			$pdf->MultiCell($this->larg_util_cadre /4, $signarea_hl * 2, $outputlangs->transnoentities('PDFInfraSPlusControl'), '', 'C', 0, 1, $this->marge_gauche + ($this->larg_util_cadre /4), $signarea_top + $signarea_hl, true, 0, false, true, $signarea_hl * 2, 'M', false);
			$pdf->MultiCell($this->larg_util_cadre /4, $signarea_hl * 2, $outputlangs->transnoentities('PDFInfraSPlusProductQty'), '', 'C', 0, 1, $this->marge_gauche + ($this->larg_util_cadre /2), $signarea_top + $signarea_hl, true, 0, false, true, $signarea_hl * 2, 'M', false);
			$pdf->MultiCell($this->larg_util_cadre /4, $signarea_hl * 2, $outputlangs->transnoentities('PDFInfraSPlusComments'), '', 'C', 0, 1, $this->marge_gauche + ($this->larg_util_cadre /4 * 3), $signarea_top + $signarea_hl, true, 0, false, true, $signarea_hl * 2, 'M', false);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusControledBy'), '', 'L', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top + $this->heightControlTable, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea1, $signarea_top + $signarea_hl + $this->heightControlTable, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusFinishedThe'), '', 'L', 0, 1, $posxsignarea2 + $this->decal_round, $signarea_top + $this->heightControlTable, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea2, $signarea_top + $signarea_hl + $this->heightControlTable, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			// Add internal intervening if defined
			$arrayidcontact		= array('I' => $object->getIdContact('internal', 'INTERVENING'));
			if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
				$object->fetch_user($arrayidcontact['I'][0]);
				$textName			= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($larg_signarea - ($this->decal_round * 2), $signarea_hl, $textName, '', 'C', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top + (($this->ht_signarea + $signarea_hl) / 2), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetFont('', '', $default_font_size - 2);
			if (!empty($calculseul)) {
				$heightforarea	= $signarea_hl + $this->ht_signarea + 2 + $this->heightControlTable;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			}
			else {
				$pdf->commitTransaction();
				return $signarea_top + $signarea_hl + $this->ht_signarea + 1 + $this->heightControlTable;
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		PDF			$pdf			The PDF factory
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		Societe		$fromcompany	Object company
		*	@param		int			$marge_basse	Margin bottom we use for the autobreak
		*	@param		int			$marge_gauche	Margin left
		*	@param		int			$page_hauteur	Page height
		*	@param		Object		$object			Object shown in PDF
		*	@param		int			$showdetails	Show company details into footer
		*	@param		int			$hidesupline	Completly hide the line up to footer (for some edition with only table)
		*	@param		int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int							Return height of bottom margin including footer text
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul)
		{
			global $conf;

			$showdetails				= $this->type_foot;
			if (!empty($this->pied))	$showdetails	.= 1;
			else						$showdetails	.= 0;
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}