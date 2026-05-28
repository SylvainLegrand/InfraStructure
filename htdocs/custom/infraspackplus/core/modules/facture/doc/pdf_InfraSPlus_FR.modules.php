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
	* 	\file		./infraspackplus/core/modules/facture/doc/pdf_InfraSPlus_FR.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF invoice
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
	include_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	include_once DOL_DOCUMENT_ROOT.'/expedition/class/'.(version_compare(DOL_VERSION, '15.0.0', '>=') ? 'expeditionlinebatch' : 'expeditionbatch').'.class.php';
	include_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF invoice InfraS
	************************************************/
	class pdf_InfraSPlus_FR extends ModelePDFFactures
	{
		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $option_logo;
		public $option_tva;
		public $option_modereg;
		public $option_condreg;
		public $option_codeproduitservice;
		public $option_multilang;
		public $option_escompte;
		public $option_credit_note;
		public $option_freetext;
		public $update_main_doc_field;	// Save the name of generated file as the main doc when generating a doc with this template
		public $type;
		public $typeadr;
		public $emetteur;
		public $credit_notes;
		public $deposits;
		public $atleastonediscount;
		public $tva;
		public $tva_array;
		public $localtax1;
		public $localtax2;
		public $credit_note;
		public $atleastoneratenotnull;
		public $situationinvoice;
		public $lines_deposits = [];
		public $use_fpdf;
		public $main_umask;
		public $page_largeur;
		public $page_hauteur;
		public $format;
		public $marge_gauche;
		public $marge_haute;
		public $marge_droite;
		public $marge_basse;
		public $formatpage;
		public $use_iso_location;
		public $dash_between_line;
		public $product_use_unit;
		public $hide_vat_ifnull;
		public $vat_label_code_or_rate;
		public $no_payment_details;
		public $chq_num;
		public $hidechq_address;
		public $rib_num;
		public $text_TVA_auto;
		public $multi_files;
		public $font;
		public $headertxtcolor;
		public $bodytxtcolor;
		public $datesbold;
		public $ref_from_cust;
		public $first_page_empty;
		public $small_head2;
		public $title_size;
		public $height_header_sep;
		public $left_recep_corner;
		public $top_recep_corner;
		public $height_top_table;
		public $hide_top_table;
		public $Rounded_rect;
		public $bg_color;
		public $txtcolor;
		public $title_bg;
		public $header_after_addr;
		public $space_headerafter;
		public $header_align_left;
		public $dates_br;
		public $show_num_cli;
		public $num_cli_frm;
		public $show_code_cli_compt;
		public $code_cli_compt_frm;
		public $add_creator_in_header;
		public $fold_mark;
		public $paid_watermark;
		public $hide_info_cur;
		public $tblLineW;
		public $tblLineDash;
		public $tblLineColor;
		public $showtblline;
		public $verLineColor;
		public $showverline;
		public $horLineColor;
		public $subti_with_subto;
		public $lineSep_hight;
		public $show_num_col;
		public $force_align_left_ref;
		public $picture_in_ref;
		public $picture_replace_ref;
		public $force_align_left_unit;
		public $desc_full_line;
		public $hidden_ouv;
		public $only_one_desc;
		public $hide_qty;
		public $hide_up;
		public $show_up_discounted;
		public $discount_auto;
		public $show_ttc_col;
		public $hide_vat_col;
		public $show_ttc_vat_tot;
		public $hide_vat;
		public $only_ttc;
		public $larg_ref;
		public $larg_qty;
		public $larg_unit;
		public $larg_up;
		public $larg_date;
		public $larg_remaintopay;
		public $larg_tva;
		public $larg_discount;
		public $larg_updisc;
		public $larg_progress;
		public $larg_totalht;
		public $larg_totalttc;
		public $num_ref;
		public $num_desc;
		public $num_qty;
		public $num_unit;
		public $num_up;
		public $num_date;
		public $num_remaintopay;
		public $num_tva;
		public $num_discount;
		public $num_updisc;
		public $num_progress;
		public $num_totalht;
		public $num_totalttc;
		public $ht_space_info;
		public $ht_space_tot;
		public $show_paymenttermcond_2l;
		public $show_qty_prod_tot;
		public $efPaySpec;
		public $IBAN_with_CB;
		public $IBAN_All;
		public $bank_only_number;
		public $show_outstandings;
		public $invert_bg_ht_ttc;
		public $show_disc_tot;
		public $show_disc_ttc;
		public $show_tot_local_cur;
		public $show_tot_Cur_Symb;
		public $number_words;
		public $listPrefixEcotax;
		public $exfEcoTax;
		public $ht_signarea;
		public $signLineW;
		public $signLineDash;
		public $signLineColor;
		public $free_text_end;
		public $type_foot;
		public $hidepagenum;
		public $maxsizeimgfoot;
		public $only_one_picture;
		public $picture_after;
		public $picture_under;
		public $picture_padding;
		public $linkpictureurl;
		public $old_path_photo;
		public $cat_hq_image;
		public $alpha;
		public $exftxtcolor;
		public $exfltxtcolor;
		public $logo;
		public $adr;
		public $customerAddrSelect;
		public $adrlivr;
		public $listnotep;
		public $pied;
		public $files;
		public $include_alias;
		public $stdLineW = 0.2; // Default line width in TCPDF = 0.2
		public $stdLineDash = '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
		public $stdLineCap = 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $stdLineColor = array(0, 0, 0);
		public $stdLineStyle = [];
		public $bgLineW = 0.2;	// Default line width in TCPDF = 0.2
		public $bgLineDash = '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
		public $bgLineCap = 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $bgLineColor = array(0, 0, 0);
		public $bgLineStyle = [];
		public $tblLineCap = 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $tblLineStyle = [];
		public $horLineStyle = [];
		public $only_ht;
		public $use_multicurrency;
		public $larg_util_cadre;
		public $larg_util_txt;
		public $posx_G_txt;
		public $larg_desc;
		public $posxcol1;
		public $posxcol2;
		public $posxcol3;
		public $posxcol4;
		public $posxcol5;
		public $largcol1;
		public $largcol2;
		public $largcol3;
		public $largcol4;
		public $largcol5;
		public $tableau = [];	// Array of table to print
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $qrcodestring;
		public $adrfact;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'payment', 'paybox', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusInvoiceReleveName');
			$this->description					= $langs->trans('PDFInfraSPlusInvoiceReleveDescription');
			$this->defaulttemplate				= getDolGlobalString('FACTURE_ADDON_PDF', '');
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 1;	// Display payment mode
			$this->option_condreg				= 1;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 1;	// Displays if there has been a discount
			$this->option_credit_note			= 1;	// Support credit notes
			$this->option_freetext				= 1;	// Support add of a personalised text
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		Facture		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return	int									1=OK, 0=KO
		**/
		public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
		{
			global $user, $langs, $conf, $hookmanager;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$filesufixe						= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_FR') ? '' : '_FR';
			$baseDir						= !empty($conf->facture->multidir_output[$conf->entity]) ? $conf->facture->multidir_output[$conf->entity] : $conf->facture->dir_output;
			$this->titlekey					= 'PDFInfraSPlusInvoiceReleveTitle';
			if (!empty($baseDir)) {
				$object->fetch_thirdparty();
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				} else {
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
						$hookmanager	= new HookManager($this->db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters					= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook					= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$this->logo					= !empty($hookmanager->resArray['logo']) ? $hookmanager->resArray['logo'] : '';
					$this->adr					= !empty($hookmanager->resArray['adr']) ? $hookmanager->resArray['adr'] : '';
					$this->customerAddrSelect	= !empty($hookmanager->resArray['customerAddrSelect']) ? $hookmanager->resArray['customerAddrSelect'] : '';
					$this->adrlivr				= !empty($hookmanager->resArray['adrlivr']) ? $hookmanager->resArray['adrlivr'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->files				= is_array($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : [];
					$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : '';
					$this->adrfact				= !empty($hookmanager->resArray['adrfact']) ? $hookmanager->resArray['adrfact'] : '';
					$nblignes					= count($object->lines);	// Set nblignes with the new facture lines content after hook
					$hookmanager->resArray		= [];
					// Create pdf instance
					$pdf						= pdf_InfraSPlus_getInstance($this->format, 'mm', 'P');
					$default_font_size			= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
					$pdf->SetAutoPageBreak(1, 0);
					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}
					$pdf->SetFont($this->font);
					// reduce the top margin before ol / il tag
					$tagvs						= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('PDFInfraSPlusInvoiceReleveTitle'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('PDFInfraSPlusInvoiceReleveTitle').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
					$watermarkedPages			= array($pdf->getPage() => true);
					$pagenb						= 1;
					// Default PDF parameters
					$this->stdLineW				= 0.2; // épaisseur par défaut dans TCPDF = 0.2
					$this->stdLineDash			= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->stdLineCap			= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->stdLineColor			= array(128, 128, 128);
					$this->stdLineStyle			= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineW				= $this->tblLineW; // épaisseur par défaut dans TCPDF = 0.2
					$this->bgLineDash			= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
					$this->bgLineCap			= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->bgLineColor			= $this->bg_color;
					$this->bgLineStyle			= array('width'=>$this->bgLineW, 'dash'=>$this->bgLineDash, 'cap'=>$this->bgLineCap, 'color'=>$this->bgLineColor);
					$this->tblLineCap			= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->tblLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
					$this->horLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Define width and position of notes frames
					$this->larg_util_cadre		= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->larg_util_txt		= $this->larg_util_cadre - (($this->Rounded_rect * 2) + 2);
					$this->posx_G_txt			= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					$this->num_ref				= 2;
					$this->num_desc				= 3;
					$this->num_date				= 1;
					$this->num_totalttc			= 4;
					$this->num_remaintopay		= 5;
					$this->larg_ref				= 30;
					$this->larg_date			= 30;
					$this->larg_totalttc		= 30;
					$this->larg_remaintopay		= 30;
					$this->larg_desc			= $this->larg_util_cadre - ($this->larg_ref + $this->larg_date + $this->larg_totalttc + $this->larg_remaintopay); // Largeur variable suivant la place restante
					$this->tableau				= array('ref'			=> array('col' => $this->num_ref,			'larg' => $this->larg_ref,			'posx' => 0),
														'desc'			=> array('col' => $this->num_desc,			'larg' => $this->larg_desc,			'posx' => 0),
														'date'			=> array('col' => $this->num_date,			'larg' => $this->larg_date,			'posx' => 0),
														'totalttc'		=> array('col' => $this->num_totalttc,		'larg' => $this->larg_totalttc,		'posx' => 0),
														'remaintopay'	=> array('col' => $this->num_remaintopay,	'larg' => $this->larg_remaintopay,	'posx' => 0)
														);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1) {
							$this->largcol1	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 2) {
							$this->largcol2	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 3) {
							$this->largcol3	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 4) {
							$this->largcol4	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 5) {
							$this->largcol5	= $ncol_array['larg'];
						}
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3		= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4		= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5		= $this->posxcol4	+ $this->largcol4;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1) {
							$this->tableau[$ncol]['posx']	= $this->posxcol1;
						} elseif ($ncol_array['col'] == 2) {
							$this->tableau[$ncol]['posx']	= $this->posxcol2;
						} elseif ($ncol_array['col'] == 3) {
							$this->tableau[$ncol]['posx']	= $this->posxcol3;
						} elseif ($ncol_array['col'] == 4) {
							$this->tableau[$ncol]['posx']	= $this->posxcol4;
						} elseif ($ncol_array['col'] == 5) {
							$this->tableau[$ncol]['posx']	= $this->posxcol5;
						}
					}
					// Calculs de positions
					$this->tab_hl				= 4;
					$this->decal_round			= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head						= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead				= $head['totalhead'];
					$hauteurcadre				= $head['hauteurcadre'];
					$tab_top					= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
					$tab_top_newpage			= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table			= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforheader			= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
					$heightforfooter			= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note				= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, 0);
					$tab_top					+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY						= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Table head
					// Output Rounded Rectangle
					if (!empty($this->title_bg)) {
						$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
					} else if (!empty($this->showtblline)) {
						$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
					}
					// Show Folder mark
					if (!empty($this->fold_mark)) {
						$pdf->Line(0, ($this->page_hauteur)/3, $this->fold_mark, ($this->page_hauteur)/3, $this->stdLineStyle);
						$pdf->Line($this->page_largeur - $this->fold_mark, ($this->page_hauteur)/3, $this->page_largeur, ($this->page_hauteur)/3, $this->stdLineStyle);
					}
					// En-tête tableau
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['date']['larg'], $this->ht_top_table, $outputlangs->transnoentities('DateInvoice'), '', 'C', 0, 1, $this->tableau['date']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTCShort'), '', 'C', 0, 1, $this->tableau['totalttc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					$pdf->MultiCell($this->tableau['remaintopay']['larg'], $this->ht_top_table, $outputlangs->transnoentities('RemainToPay'), '', 'C', 0, 1, $this->tableau['remaintopay']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					// Récap
					$discount				= new DiscountAbsolute($this->db);
					$tmpInvoice				= new Facture($this->db);
					$listFacturesSources	= [];
					$totaux					= array('ttc' => 0, 'remaintopay' => 0);
					// Loop on each line
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						if (empty($object->lines[$i]->fk_remise_except)) {
							continue;	// no deposit line
						}
						$discount->fetch($object->lines[$i]->fk_remise_except);
						// deposit line with link to deposit invoice
						if (!empty($discount->ref_facture_source)) {
							if (in_array($discount->fk_facture_source, $listFacturesSources)) {
								continue;	// Attention à ne pas inclure plusieurs fois la même facture d'acompte (quand plusieurs taux de TVA sont utilisés)
							}
							$listFacturesSources[]												= $discount->fk_facture_source;
							$res																= $tmpInvoice->fetch($discount->fk_facture_source);
							$paid																= $tmpInvoice->getSommePaiement(0);
							$sign																= $tmpInvoice->type == 2 && !empty($this->credit_note) ? -1 : 1;
							$totaux['ttc']														+= $sign * $tmpInvoice->total_ttc;
							$totaux['remaintopay']												+= $sign * ($tmpInvoice->total_ttc - $paid);
							$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->tab_hl, $this->tableau['ref']['posx'], $nexY, $tmpInvoice->ref, 0, 1, false, true, 'L', true);
							$pdf->writeHTMLCell($this->tableau['desc']['larg'], $this->tab_hl, $this->tableau['desc']['posx'], $nexY, $outputlangs->convToOutputCharset($tmpInvoice->ref_client), 0, 1, false, true, 'L', true);
							$pdf->writeHTMLCell($this->tableau['date']['larg'], $this->tab_hl, $this->tableau['date']['posx'], $nexY, dol_print_date($tmpInvoice->date, 'day', false, $outputlangs, true), 0, 1, false, true, 'L', true);
							$total_ttc															= pdf_InfraSPlus_price($tmpInvoice, $sign * $tmpInvoice->total_ttc, $outputlangs, 1, 0, 'T');
							$pdf->writeHTMLCell($this->tableau['totalttc']['larg'], $this->tab_hl, $this->tableau['totalttc']['posx'], $nexY, $total_ttc, 0, 1, false, true, 'R', true);
							$remaintopay														= pdf_InfraSPlus_price($tmpInvoice, $sign * ($tmpInvoice->total_ttc - $paid), $outputlangs, 1, 0, 'T');
							$pdf->writeHTMLCell($this->tableau['remaintopay']['larg'], $this->tab_hl, $this->tableau['remaintopay']['posx'], $nexY, $remaintopay, 0, 1, false, true, 'R', true);
							$nexY																+= $this->tab_hl * 1.5;
							// search for the pdf file
							$discountRef														= dol_sanitizeFileName($discount->ref_facture_source);
							$discountDir														= $baseDir.'/'.$discountRef;
							$listDiscountFiles													= dol_dir_list($discountDir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
							$system_upload_relative_dir											= preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $discountDir);
							$system_upload_relative_dir											= preg_replace('/^[\\/]/', '', $system_upload_relative_dir);
							completeFileArrayWithDatabaseInfo($listDiscountFiles, $system_upload_relative_dir);
							foreach ($listDiscountFiles as $discountFile) {
								if (empty($discountFile['name'])) {
									continue;
								}
								$discountName						= pathinfo($discountFile['name'], PATHINFO_FILENAME);
								if ($discountName == $discountRef) {
									$this->files[]	= $discountFile['rowid'];
								}
							}
						}
					}
					// Include the current invoice
					$sign			= $object->type == 2 && !empty($this->credit_note) ? -1 : 1;
					// Paiezments spéciaux (ma prime renov, CEE, etc...)
					$totalEfPaySpec	= 0;
					if ($this->efPaySpec) {	// we show special payments before they are paid
						$listEfPaySpec	= pdf_InfraSPlus_SpecPayExtraField($object);
						foreach ($listEfPaySpec as $key => $efPaySpec) {
							if ($efPaySpec['value'] != 0) {
								$totalEfPaySpec	+= price2num($efPaySpec['value'], 'MT');
							}
						}
					}
					$totaux['ttc']	+= $sign * ($object->total_ttc - $totalEfPaySpec);
					// Loop on each payment
					$stdpaidamount	= 0;
					$sql			= 'SELECT SUM(pf.amount) AS stdpaidamount';
					$sql			.= ' FROM '.$this->db->prefix().'paiement_facture AS pf, '.$this->db->prefix().'paiement AS p';
					$sql			.= ' LEFT JOIN '.$this->db->prefix().'c_paiement AS cp ON p.fk_paiement = cp.id';
					$sql			.= ' WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = '.((int) $object->id).' AND cp.entity IN ('.getEntity('c_paiement').') AND cp.type != 3';
					$resql			= $this->db->query($sql);
					if ($resql) {
						$obj			= $this->db->fetch_object($resql);
						$stdpaidamount	= $obj->stdpaidamount;
					} else {
						$this->error	= $this->db->lasterror();
					}
					$this->db->free($resql);
					$totaux['remaintopay']		+= $sign * ($object->total_ttc - $totalEfPaySpec - $stdpaidamount);
					$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->tab_hl, $this->tableau['ref']['posx'], $nexY, $object->ref, 0, 1, false, true, 'L', true);
					$pdf->writeHTMLCell($this->tableau['desc']['larg'], $this->tab_hl, $this->tableau['desc']['posx'], $nexY, $outputlangs->convToOutputCharset($object->ref_client), 0, 1, false, true, 'L', true);
					$pdf->writeHTMLCell($this->tableau['date']['larg'], $this->tab_hl, $this->tableau['date']['posx'], $nexY, dol_print_date($object->date, 'day', false, $outputlangs, true), 0, 1, false, true, 'L', true);
					$total_ttc					= pdf_InfraSPlus_price($object, $sign * ($object->total_ttc - $totalEfPaySpec), $outputlangs, 1, 0, 'T');
					$pdf->writeHTMLCell($this->tableau['totalttc']['larg'], $this->tab_hl, $this->tableau['totalttc']['posx'], $nexY, $total_ttc, 0, 1, false, true, 'R', true);
					$remaintopay				= pdf_InfraSPlus_price($object, $sign * ($object->total_ttc - $totalEfPaySpec - $stdpaidamount), $outputlangs, 1, 0, 'T');
					$pdf->writeHTMLCell($this->tableau['remaintopay']['larg'], $this->tab_hl, $this->tableau['remaintopay']['posx'], $nexY, $remaintopay, 0, 1, false, true, 'R', true);
					// Loop on each documents to find the pdf file
					$listInvoiceFiles			= dol_dir_list($dir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
					$system_upload_relative_dir	= preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);
					$system_upload_relative_dir	= preg_replace('/^[\\/]/', '', $system_upload_relative_dir);
					completeFileArrayWithDatabaseInfo($listInvoiceFiles, $system_upload_relative_dir);
					foreach ($listInvoiceFiles as $invoiceFile) {
						if (empty($invoiceFile['name'])) {
							continue;
						}
						$invoiceName	= pathinfo($invoiceFile['name'], PATHINFO_FILENAME);
						if ($invoiceName == $objectref) {
							$this->files[]	= $invoiceFile['rowid'];
						}
					}
					// Avoir ou excédent
					$this->credit_notes	= $object->getSumCreditNotesUsed($this->use_multicurrency ? 1 : 0);	// Warning, this also include excess received
					$sql				= 'SELECT re.fk_facture_source FROM '.$this->db->prefix().'societe_remise_except as re WHERE fk_facture = '.((int) $object->id);
					$resql				= $this->db->query($sql);
					if ($resql) {
						$creditNote	= new Facture($this->db);
						for ($i = 0 ; $i < $this->db->num_rows($resql) ; $i++) {
							$obj						= $this->db->fetch_object($resql);
							$res						= $creditNote->fetch($obj->fk_facture_source);
							$paid						= $creditNote->getSommePaiement(0);
							$sign						= $creditNote->type == 2 && !empty($this->credit_note) ? -1 : 1;
							$totaux['ttc']				+= $sign * $creditNote->total_ttc;
							$totaux['remaintopay']		+= $sign * ($creditNote->total_ttc - $paid);
							$nexY						+= $this->tab_hl * 1.5;
							$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->tab_hl, $this->tableau['ref']['posx'], $nexY, $creditNote->ref, 0, 1, false, true, 'L', true);
							$pdf->writeHTMLCell($this->tableau['desc']['larg'], $this->tab_hl, $this->tableau['desc']['posx'], $nexY, $outputlangs->convToOutputCharset($creditNote->ref_client), 0, 1, false, true, 'L', true);
							$pdf->writeHTMLCell($this->tableau['date']['larg'], $this->tab_hl, $this->tableau['date']['posx'], $nexY, dol_print_date($creditNote->date, 'day', false, $outputlangs, true), 0, 1, false, true, 'L', true);
							$total_ttc					= pdf_InfraSPlus_price($creditNote, $sign * $creditNote->total_ttc, $outputlangs, 1, 0, 'T');
							$pdf->writeHTMLCell($this->tableau['totalttc']['larg'], $this->tab_hl, $this->tableau['totalttc']['posx'], $nexY, $total_ttc, 0, 1, false, true, 'R', true);
							$remaintopay				= pdf_InfraSPlus_price($creditNote, $sign * ($creditNote->total_ttc - $paid), $outputlangs, 1, 0, 'T');
							$pdf->writeHTMLCell($this->tableau['remaintopay']['larg'], $this->tab_hl, $this->tableau['remaintopay']['posx'], $nexY, $remaintopay, 0, 1, false, true, 'R', true);
							// search for the pdf file
							$creditNoteRef				= dol_sanitizeFileName($creditNote->ref);
							$creditNoteDir				= $baseDir.'/'.$creditNoteRef;
							$listCrerditNoteFiles		= dol_dir_list($creditNoteDir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
							$system_upload_relative_dir	= preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $creditNoteDir);
							$system_upload_relative_dir	= preg_replace('/^[\\/]/', '', $system_upload_relative_dir);
							completeFileArrayWithDatabaseInfo($listCrerditNoteFiles, $system_upload_relative_dir);
							foreach ($listCrerditNoteFiles as $creditNoteFile) {
								if (empty($creditNoteFile['name'])) {
									continue;
								}
								$creditNoteName	= pathinfo($creditNoteFile['name'], PATHINFO_FILENAME);
								if ($creditNoteName == $creditNoteRef) {
									$this->files[]	= $creditNoteFile['rowid'];
								}
							}
						}
					} else {
						dol_print_error($this->db);
					}
					$this->db->free($resql);
					// Total
					$nexY										+= $this->tab_hl * 2;
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->line($this->tableau['totalttc']['posx'], $nexY - 2, $this->page_largeur - $this->marge_gauche, $nexY - 2);
					$pdf->line($this->tableau['totalttc']['posx'], $nexY - 1, $this->page_largeur - $this->marge_gauche, $nexY - 1);
					$total_ttc									= pdf_InfraSPlus_price($object, $totaux['ttc'], $outputlangs, 1, 0, 'T');
					$pdf->writeHTMLCell($this->tableau['totalttc']['larg'], $this->tab_hl, $this->tableau['totalttc']['posx'], $nexY, $total_ttc, 0, 1, false, true, 'R', true);
					$remaintopay								= pdf_InfraSPlus_price($object, $totaux['remaintopay'], $outputlangs, 1, 0, 'T');
					$pdf->writeHTMLCell($this->tableau['remaintopay']['larg'], $this->tab_hl, $this->tableau['remaintopay']['posx'], $nexY, $remaintopay, 0, 1, false, true, 'R', true);
					// Footer
					$heightforfooter							= $this->_pagefoot($pdf, $object, $outputlangs, 0);
					$posy										= $this->page_hauteur - $heightforfooter - 1;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					// if merge files is active
					if (!empty($this->files)) {
						pdf_InfraSPlus_files($pdf, $this->files, 1, $object, $outputlangs, $this->formatpage, 1);
					}
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
					if (!empty($this->main_umask)) {
						@chmod($file, octdec($this->main_umask));
					}
					$this->result					= array('fullpath' => $file);
					return 1;	// Pas d'erreur
				} else {
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			} else {
				$this->error	= $outputlangs->transnoentities('ErrorConstantNotDefined', 'FAC_OUTPUTDIR');
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Facture		$object			Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		array		$hauteurhead	'totalhead'		= height of header
		*											'hauteurcadre	= height of frame
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{
			$specialHead	= infraspackplus_fetchAllSpecialHeads(array($object->element));
			if (!empty($specialHead['rootFileName'])) {
				$specialhead	= 'pdf_'.$specialHead['rootFileName'].'_pagehead';
				$hauteurhead	= $specialhead($pdf, $object, $showaddress, $outputlangs, $this->headertxtcolor, $this->header_align_left, $this->decal_round, $this->formatpage, $this->logo, $this->emetteur, $this->tab_hl,
												0, $this->title_size, $this->titlekey, $this->ref_from_cust, $this->datesbold, $this->dates_br, $this->show_num_cli, $this->num_cli_frm,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, $this->add_creator_in_header, $this->use_iso_location, $this->adr, $this->typeadr, $this->adrlivr, $this->Rounded_rect,
												$this->customerAddrSelect, -2, -2, $this->qrcodestring, $this->deposits, $this->lines_deposits, '', $this->adrfact, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
				return $hauteurhead;
			}
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$dimCadres			= array ('S' => ($this->page_largeur - ($this->marge_gauche + 6 + $this->left_recep_corner + $this->marge_droite)), 'R' => $this->left_recep_corner);	// page width = 210 (A4) 92 + 92  = 184 => keep 210 - 184 for margins => 26 ; 10 right and left and 6 on the middle
			$w					= $this->header_align_left ? 92 - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $object->entity);
			$heightLogo			+= $posy + $this->tab_hl;
			if (!empty($this->qrcodestring)) {
				$sizeBC		= 25;
				$styleBC	= array('position'		=> '',
									'border'		=> false,
									'hpadding'		=> '0',
									'vpadding'		=> '0',
									'fgcolor'		=> array($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]),
									'bgcolor'		=> false,	// array(255,255,255)
									'module_width'	=> 1,		// width of a single module in points
									'module_height'	=> 1		// height of a single module in points
									);
				$pdf->write2DBarcode($this->qrcodestring, 'QRCODE,M', $posx + 2, $posy, $sizeBC, $sizeBC, $styleBC, 'N');
			}
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$title							= $outputlangs->transnoentities($this->titlekey);
			$pdf->MultiCell($w - $sizeBC - 3, $this->tab_hl * $this->title_size, $title, '', $align, 0, 1, $posx + $sizeBC + 3, $posy, true, 0, 0, false, 0, 'M', false);
			$posy							= $pdf->getY();
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			if ($object->ref_client) {
				$posy	+= $this->tab_hl - 0.5;
				$txtcc	= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_client);
				$pdf->MultiCell($w, $this->tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			}
			if (!empty($this->show_num_cli) && !empty($this->num_cli_frm) && $object->thirdparty->code_client) {
				$txtNumCli	= $outputlangs->transnoentities('CustomerCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_client);
				$posy		+= $this->tab_hl - 0.5;
				$pdf->MultiCell($w, $this->tab_hl, $txtNumCli, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			}
			if (!empty($this->show_code_cli_compt) && !empty($this->code_cli_compt_frm) && $object->thirdparty->code_compta) {
				$txtCodeCliCompt	= $outputlangs->transnoentities('CustomerAccountancyCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_compta);
				$posy				+= $this->tab_hl - 0.5;
				$pdf->MultiCell($w, $this->tab_hl, $txtCodeCliCompt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			}
			if (!empty($this->add_creator_in_header)) {
				$usertmp	= pdf_InfraSPlus_creator($object, $outputlangs);
				if (!empty($usertmp)) {
					$posy	+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusRedac').' : '.$usertmp, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
			}
			// Show list of linked objects
			$posy	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $this->tab_hl, $align);
			$posy	+= 0.5;
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			if (!empty($showaddress)) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'BILLING'),
										'L' => $object->getIdContact('external', 'SHIPPING')
										);
				$addresses		= [];
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', $this->adrfact, 0, -2, -2, $this->customerAddrSelect, $this->include_alias);
				$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
			}
			$hauteurhead	= array('totalhead'		=> $dimCadres['Y'] + $hauteurcadre,
									'hauteurcadre'	=> $hauteurcadre,
									'livrshow_name'	=> $addresses['livrshow_name'],
									'livrshow'		=> $addresses['livrshow']
									);
			return $hauteurhead;
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Facture		$object			Object to show
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
