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
	* 	\file		./infraspackplus/core/modules/commande/doc/pdf_InfraSPlus_BLC.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF pseudo expedition from order
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
	include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_BLC extends ModelePDFCommandes
	{
		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $draft_watermark;
		public $show_sign_area;
		public $option_logo;
		public $option_tva;
		public $option_modereg;
		public $option_condreg;
		public $option_codeproduitservice;
		public $option_multilang;
		public $option_escompte;
		public $option_credit_note;
		public $option_freetext;
		public $option_draft_watermark;
		public $update_main_doc_field;	// Save the name of generated file as the main doc when generating a doc with this template
		public $type;
		public $emetteur;
		public $atleastonediscount;
		public $tva;
		public $tva_array;
		public $localtax1;
		public $localtax2;
		public $atleastoneratenotnull;
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
		public $chq_num;
		public $diffsize_title;
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
		public $show_desc;
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
		public $e_signing;
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
		public $listfreet;
		public $listnotep;
		public $pied;
		public $files;
		public $include_alias;
		public $with_picture;
		public $refcol;
		public $showwvccchk;
		public $signvalue;
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
		public $verLineStyle = [];
		public $horLineStyle = [];
		public $signLineCap = '';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $signLineStyle = [];
		public $only_ht;
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
		public $Sst;
		public $adrSst;
		public $larg_efl;
		public $num_efl;
		public $paramspecialfiles;
		public $show_efl;
		public $showntusedascover;
		public $typeadr;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusExpeditionCL202203007Name');
			$this->description					= $langs->trans('PDFInfraSPlusExpeditionCL202203007Description');
			$this->titlekey						= 'PDFInfraSPlusExpeditionTitle';
			$this->defaulttemplate				= getDolGlobalString('EXPEDITION_ADDON_PDF', '');
			$this->draft_watermark				= getDolGlobalString('SHIPPING_DRAFT_WATERMARK', '');
			$this->paramspecialfiles			= getDolGlobalString('INFRASPLUS_PDF_SPECIAL_FILES', '');
			$this->show_efl						= 1;
			$this->larg_ref						= 30;
			$this->larg_efl						= 40;
			$this->larg_unit					= 18;
			$this->larg_qty						= 18;
			$this->num_ref						= 1;
			$this->num_efl						= 5;
			$this->num_desc						= 2;
			$this->num_unit						= 4;
			$this->num_qty						= 3;
			$this->show_sign_area				= 1;
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 0;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 0;	// Display payment mode
			$this->option_condreg				= 0;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 0;	// Displays if there has been a discount
			$this->option_credit_note			= 0;	// Support credit notes
			$this->option_freetext				= 1;	// Support add of a personalised text
			$this->option_draft_watermark		= 1;	// Support add of a watermark on drafts
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		Commande	$object					Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return	int							1=OK, 0=KO
		**/
		public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
		{
			global $user, $langs, $conf, $hookmanager, $nblignes;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$fileprefix		= '';
			$baseDir		= !empty($conf->commande->multidir_output[$conf->entity]) ? $conf->commande->multidir_output[$conf->entity] : $conf->commande->dir_output;
			if (!getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME')) {
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_BLC') ? '' : '_BLC';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_ADD_PREFIX_TO_BLC', '');
				$filesufixe	= empty($fileprefix) ? '_BLC' : '';
			}
			if (!empty($baseDir)) {
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$dir		= $baseDir;
					$file		= $dir.'/SPECIMEN.pdf';
				} else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/'.$objectref;
					$file		= $dir.'/'.$fileprefix.$objectref.$filesufixe.'.pdf';
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
						$hookmanager					= new HookManager($this->db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters					= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook					= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$this->logo					= !empty($hookmanager->resArray['logo']) ? $hookmanager->resArray['logo'] : '';
					$this->adr					= !empty($hookmanager->resArray['adr']) ? $hookmanager->resArray['adr'] : '';
					$this->customerAddrSelect	= !empty($hookmanager->resArray['customerAddrSelect']) ? $hookmanager->resArray['customerAddrSelect'] : '';
					$this->adrlivr				= !empty($hookmanager->resArray['adrlivr']) ? $hookmanager->resArray['adrlivr'] : '';
					$this->Sst					= !empty($hookmanager->resArray['Sst']) ? $hookmanager->resArray['Sst'] : '';
					$this->adrSst				= !empty($hookmanager->resArray['adrSst']) ? $hookmanager->resArray['adrSst'] : '';
					$this->listfreet			= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->files				= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					$this->showntusedascover	= !empty($hookmanager->resArray['showntusedascover']) ? $hookmanager->resArray['showntusedascover'] : '';
					$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : '';
					$this->refcol				= !empty($hookmanager->resArray['refcol']) ? $hookmanager->resArray['refcol'] : '';
					$hidedesc					= !empty($hookmanager->resArray['hidedesc']) ? $hookmanager->resArray['hidedesc'] : '';
					$this->showwvccchk			= !empty($hookmanager->resArray['showwvccchk']) ? $hookmanager->resArray['showwvccchk'] : '';
					$this->signvalue			= !empty($hookmanager->resArray['signvalue']) ? $hookmanager->resArray['signvalue'] : '';
					$hookmanager->resArray		= [];
					// Si on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information Sauf si on utilise les prix par client et que l'otion d'affichage des références client est sur 1
					$hideref					= empty($this->refcol) || (getDolGlobalInt('PRODUIT_CUSTOMER_PRICES', 0) && getDolGlobalInt('PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE', 0) == 1) ? 0 : 1;
					$nblignes					= count($object->lines);	// Set nblignes with the new facture lines content after hook
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
					$tagvs					= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('Shipment'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Shipment').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
					$watermarkedPages		= array($pdf->getPage() => true);	// Track pages with watermark to avoid double rendering in while loops
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
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// First loop on each lines to prepare calculs and variables
					$listlinetoshow			= [];
					$objproduct				= new Product($this->db);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$isSubATM				= isModEnabled('subtotal') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal') ? 1 : 0;	// Ligne ATM
						if (!empty($isSubATM)) {
							continue;
						}
						$isProd					= !empty($object->lines[$i]->fk_product) ? $objproduct->fetch($object->lines[$i]->fk_product) : 0;
						$isProd					= $isProd > 0 && $object->lines[$i]->product_type == 0 ? $isProd : 0;	// Products only, no services
						$status_batch			= !empty($isProd) ? $objproduct->status_batch : 0;	// product with unique serial number
						if ($object->lines[$i]->qty > 1 && $status_batch == 2) {	// we want one line per serial number for product with  unique serial number
							for ($j = 0 ; $j < $object->lines[$i]->qty ; $j++)	$listlinetoshow[]	= array('line' => $i, 'qtytoshow' => 1);
						} else {
							$listlinetoshow[]	= array('line' => $i, 'qtytoshow' => 'std');
						}
					}
					$nblignes							= count($listlinetoshow);
					// Define width and position of notes frames
					$this->larg_util_cadre				= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->larg_util_txt				= $this->larg_util_cadre - (($this->Rounded_rect * 2) + 2);
					$this->posx_G_txt					= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->refcol)) {
						$this->larg_ref	= 0;
					}
					if (empty($this->show_efl)) {
						$this->larg_efl	= 0;
					}
					if (empty($this->product_use_unit)) {
						$this->larg_unit	= 0;
					}
					$this->larg_desc					= $this->larg_util_cadre - ($this->larg_ref + $this->larg_efl + $this->larg_unit + $this->larg_qty); // Largeur variable suivant la place restante
					$this->tableau						= array('ref'	=> array('col' => $this->num_ref,	'larg' => $this->larg_ref,	'posx' => 0),
																'efl'	=> array('col' => $this->num_efl,	'larg' => $this->larg_efl,	'posx' => 0),
																'desc'	=> array('col' => $this->num_desc,	'larg' => $this->larg_desc,	'posx' => 0),
																'unit'	=> array('col' => $this->num_unit,	'larg' => $this->larg_unit,	'posx' => 0),
																'qty'	=> array('col' => $this->num_qty,	'larg' => $this->larg_qty,	'posx' => 0)
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
					$this->posxcol1	= $this->marge_gauche;
					$this->posxcol2	= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3	= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4	= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5	= $this->posxcol4	+ $this->largcol4;
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
					$this->tab_hl		= 4;
					$this->decal_round	= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head				= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead		= $head['totalhead'];
					$hauteurcadre		= $head['hauteurcadre'];
					$tab_top			= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
					$tab_top_newpage	= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table	= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforheader	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
					$heightforinfotot	= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					$nexY				= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					for ($k = 0 ; $k < $nblignes ; $k++) {
						$i									= $listlinetoshow[$k]['line'];
						$qtytoshow							= $listlinetoshow[$k]['qtytoshow'];
						$curY								= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table)) {
							$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						} else {
							$pdf->setTopMargin($tab_top_newpage);
						}
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore						= $pdf->getPage();
						$showpricebeforepagebreak			= 1;
						// Hauteur de ligne
						$this->heightline	= $this->tab_hl;
						// Hauteur de la Reference
						if (!empty($this->refcol)) {
							$pdf->startTransaction();
							$startline			= $pdf->GetY();
							$ref				= pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails);
							$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
							$endline			= $pdf->GetY();
							$this->heightline	= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
							$pdf->rollbackTransaction(true);
						}
						// Extra fields & custom informations
						$pdf->SetLineStyle($this->horLineStyle);
						// Description of product line
						$pageposdesc	= $pdf->getPage();
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, 0, '', null, 0, 0, $this->with_picture, [], [], $this->linkpictureurl, $this->tab_hl, 0, $this->picture_padding);
						$pageposafter	= $pdf->getPage();
						$posyafter		= $pdf->GetY();
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($k == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
									pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
									$watermarkedPages[$pdf->getPage()]	= true;
								}
							} else {
								$showpricebeforepagebreak	= 0;
							}
						}
						elseif ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
							if ($k == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposafter + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
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
								$curY	= $heightforheader;
							} else {
								$pdf->setPage($pageposdesc);
							}
						}
						$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
						// ref
						if (!empty($this->refcol)) {
							$pagepos	= $pdf->getPage();
							$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
							$pdf->setPage($pagepos);
						}
						// Position
						if (!empty($this->show_efl)) {
							$pdf->MultiCell($this->tableau['efl']['larg'], $this->heightline, '', '', 'C', 0, 1, $this->tableau['efl']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}
						// Unit
						if (!empty($this->product_use_unit)) {
							$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
							$pdf->writeHTMLCell($this->tableau['unit']['larg'], $this->heightline, $this->tableau['unit']['posx'], $curY, $unit, 0, 1, false, true, $this->force_align_left_unit, true);
						}
						// Qty to ship
						$qty	= $qtytoshow == 'std' ? pdf_getlineqty($object, $i, $outputlangs, $hidedetails) : $qtytoshow;
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'C', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Add dash or space between line
						if (!empty($this->dash_between_line) && $k < ($nblignes - 1)) {
							$pdf->setPage($pageposafter);
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
							$nexY	+= 2;
						} else {
							$nexY	+= $this->lineSep_hight;
						}
						// Detect if some page were added automatically and output _tableau for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} else {
								$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							}
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							// Save auto-break content so watermark goes behind it (z-order fix)
							$savedContent = method_exists($pdf, 'liftPageContent') ? $pdf->liftPageContent() : '';
							if (empty($watermarkedPages[$pagenb])) {
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pagenb]	= true;
							}
							if (empty($this->small_head2)) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							} else {
								$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
							// Restore auto-break content after watermark/header
							if ($savedContent !== '' && method_exists($pdf, 'dropPageContent')) {
								$pdf->dropPageContent($savedContent);
							}
							// Restore grayscale FillColor after _pagehead to keep ColorFlag true
							$pdf->SetFillColor(255);
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						}
						if (!empty($listlinetoshow[$k + 1]['line']) && isset($object->lines[$listlinetoshow[$k + 1]['line']]->pagebreak) && $object->lines[$listlinetoshow[$k + 1]['line']]->pagebreak) {
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} else {
								$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							}
							// New page
							$pdf->AddPage();
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
							$pagenb++;
							$watermarkedPages[$pagenb]	= true;
							if (empty($this->small_head2)) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							} else {
								$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
							// Restore grayscale FillColor after _pagehead to keep ColorFlag true
							$pdf->SetFillColor(255);
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
							$nexY							= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
						}
					}
					$bottomlasttab								= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} else {
						$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					}
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$posy										= $this->_signature_area($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					// if merge files is active
					if (!empty($this->paramspecialfiles)) {
						$this->paramspecialfiles	= explode(',', $this->paramspecialfiles);
						$dirpdfs					= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/specialfiles';
						$listpdfs					= dol_dir_list($dirpdfs, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
						$listspecialfiles			= dol_dir_list(dol_buildpath('/infraspackplus/core/modules/specialfiles', 0), 'files', 0, '\.php$', null, 'name', SORT_ASC, 0, 0, '', 0);
						$listspecialfiles			= array_column($listspecialfiles, 'name');
						$filesArray					= [];
						foreach ($listpdfs as $pdffile) {
							if (empty($pdffile['name'])) {
								continue;
							}
							$pdfname						= pathinfo($pdffile['name'], PATHINFO_FILENAME);
							if (in_array($pdfname, $this->paramspecialfiles)) {
								$key																	= 'INFRASPLUS_PDF_SPECIAL_FILE_'.(strtoupper($object->element)).'_'.(strtoupper($pdfname));
								$showfile																= getDolGlobalInt($key, 0);
								if (!empty($showfile) && in_array($pdfname.'.php', $listspecialfiles)) {
									$filesArray[]	= $pdffile;
								}
							}
						}
						completeFileArrayWithDatabaseInfo($filesArray, 'infraspackplus/specialfiles');
						if (is_array($filesArray) && count($filesArray)) {
							if (! is_array($this->files)) {
								$this->files	= explode(',', $this->files);
							}
							foreach($filesArray as $filetomerge)
								if (in_array($filetomerge['name'], array('PVreception.pdf', 'Reserves.pdf')) && ! in_array($filetomerge['rowid'], $this->files)) {
									$this->files[]	= $filetomerge['rowid'];
								}
						}
					}
					if (!empty($this->files)) {
						pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters					= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook					= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks
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
					$this->error=$outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			} else {
				$this->error=$outputlangs->transnoentities('ErrorConstantNotDefined', 'EXP_OUTPUTDIR');
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Commande	$object			Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		array		$hauteurhead	'totalhead' = height of header
		*											'hauteurcadre = height of frame
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{
			$specialHead	= infraspackplus_fetchAllSpecialHeads(array($object->element), '_BLC');
			if (!empty($specialHead['rootFileName'])) {
				$specialhead	= 'pdf_'.$specialHead['rootFileName'].'_pagehead';
				$hauteurhead	= $specialhead($pdf, $object, $showaddress, $outputlangs, $this->headertxtcolor, $this->header_align_left, $this->decal_round, $this->formatpage, $this->logo, $this->emetteur, $this->tab_hl,
												$this->header_after_addr, $this->title_size, $this->titlekey, $this->ref_from_cust, $this->datesbold, $this->dates_br, $this->show_num_cli, $this->num_cli_frm,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, $this->add_creator_in_header, $this->use_iso_location, $this->adr, $this->typeadr, $this->adrlivr, $this->Rounded_rect,
												$this->customerAddrSelect, $this->Sst, $this->adrSst, '', 0, [], '', -2, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
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
			if (empty($this->header_after_addr)) {
				$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
				$title	= $outputlangs->transnoentities($this->titlekey);
				$pdf->MultiCell($w, $this->tab_hl * $this->title_size, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$posy	= $pdf->getY();
				$txtref	= $outputlangs->transnoentities('RefSending').' : '.$outputlangs->convToOutputCharset($object->ref);
				if ($object->statut == 0) {
					$pdf->SetTextColor(128, 0, 0);
					$txtref .= ' - '.$outputlangs->transnoentities('NotValidated');
				}
				$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
				$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
				if ($object->date_delivery) {
					$posy	+= $this->tab_hl;
					$txtdt	= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($object->date_delivery, 'dayhour', false, $outputlangs, true);
					$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				$pdf->SetFont('', '', $default_font_size - 2);
				if ($object->ref_customer) {
					$posy	+= $this->tab_hl - 0.5;
					$txtcc	= '';
					$txtcc	.= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_customer);
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
				// Show list of linked objects
				$posy	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $this->tab_hl, $align);
				$posy	+= 0.5;
			}
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			if (!empty($showaddress)) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'SHIPPING')
										);
				$addresses		= [];
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', null, 0, $this->Sst, $this->adrSst, $this->customerAddrSelect, $this->include_alias);
				$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
			}
			$hauteurhead	= array('totalhead'		=> $dimCadres['Y'] + $hauteurcadre,
									'hauteurcadre'	=> $hauteurcadre,
									'livrshow_name'	=> $addresses['livrshow_name'],
									'livrshow'		=> $addresses['livrshow'],
									'SsTshow'		=> $addresses['SsTshow']
									);
			return $hauteurhead;
		}

		/**
		*	Show top small header of page.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Commande	$object			Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		void
		**/
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs)
		{
			$fromcompany	= $this->emetteur;
			$title			= $outputlangs->transnoentities($this->titlekey);
			pdf_InfraSPlus_pagesmallhead($pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $this->formatpage, $this->decal_round, $this->logo, $this->headertxtcolor);
		}

		/**
		*	Show table for lines
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Commande	$object			Object to show
		*	@param		float		$tab_top		Top position of table
		*	@param		float		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@return		void
		**/
		protected function _tableau(&$pdf, $object, $tab_top, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb)
		{
			// Force to disable hidetop and hidebottom
			$hidebottom				= 0;
			if (!empty($hidetop)) {
				$hidetop	= -1;
			}
			$default_font_size		= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				// Table header
				if (!empty($this->title_bg)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				} else if ($this->showtblline) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
				if ($this->showtblline) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				} else {
					$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
				}
			} else if (!empty($this->showtblline)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			}
			if ($object->statut == Commande::STATUS_DRAFT && (!empty($this->draft_watermark))) {
				if (empty($hidetop)) {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + $this->ht_top_table + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				} else {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				}
				$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			}
			// Show Folder mark
			if (!empty($this->fold_mark)) {
				$pdf->Line(0, ($this->page_hauteur)/3, $this->fold_mark, ($this->page_hauteur)/3, $this->stdLineStyle);
				$pdf->Line($this->page_largeur - $this->fold_mark, ($this->page_hauteur)/3, $this->page_largeur, ($this->page_hauteur)/3, $this->stdLineStyle);
			}
			if (!empty($this->showverline)) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol2, $tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol3, $tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol4, $tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol5, $tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->verLineStyle);
				}
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				if (!empty($this->refcol)) {
					$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				if (!empty($this->show_efl)) {
					$pdf->MultiCell($this->tableau['efl']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusSerialRef'), '', 'C', 0, 1, $this->tableau['efl']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->product_use_unit)) {
					$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Unit'), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('QtyShippedShort'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
			}
		}

		/**
		*	Show area for the customer to sign
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Commande	$object			Object to show
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@param		int			$calculseul		no print => just to know the height
		*	@return		int							Position pour suite
		**/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			$pdf->startTransaction();
			$default_font_size				= pdf_getPDFFontSize($outputlangs);
			$signarea_top					= $posy + 1;
			$posxsignarea					= $this->marge_gauche;
			$larg_signarea					= $this->larg_util_cadre;
			$titlesignarea					= $outputlangs->transnoentities('ForCustomer').' '.$outputlangs->transnoentities('PDFInfraSPlusExpeditionSgnatureStamp');
			$titlesignarea_hl				= $pdf->getStringHeight($larg_signarea, $titlesignarea);
			$titlesignarea_hl				= $titlesignarea_hl < $this->tab_hl ? $this->tab_hl : $titlesignarea_hl;
			$signarea_hl					= $this->tab_hl;
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			// Comments
			if (!empty($this->title_bg)) {
				$pdf->RoundedRect($posxsignarea, $signarea_top, $larg_signarea, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
			}
			$titlecomments					= $outputlangs->transnoentities('Comments').' :';
			$pdf->MultiCell($larg_signarea, $this->ht_top_table, $titlecomments, '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
			$signarea_top					= $pdf->GetY() + ($this->tab_hl * 10);	// 10 lines for comments
			// Signature
			if (!empty($this->title_bg)) {
				$pdf->RoundedRect($posxsignarea, $signarea_top, $larg_signarea, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
			}
			$pdf->MultiCell($larg_signarea, $titlesignarea_hl, $titlesignarea, '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			// Customer signature
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusExpeditionSgnatureName'), '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top + $titlesignarea_hl, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusExpeditionSgnatureQuality'), '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top + $titlesignarea_hl + $signarea_hl, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusExpeditionSgnatureStamp'), '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top + $titlesignarea_hl + ($signarea_hl * 2), true, 0, 0, false, 0, 'M', false);
			if (!empty($calculseul)) {
				$heightforarea	= ($signarea_top + $titlesignarea_hl + ($signarea_hl * 3) + $this->ht_signarea + 1) - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			} else {
				$pdf->commitTransaction();
				return $signarea_top + $titlesignarea_hl + ($signarea_hl * 3) + $this->ht_signarea + 1;
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Commande	$object			Object to show
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
				$hauteurfoot	= $specialFoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, 1, $this->bodytxtcolor, $this->stdLineStyle);
				return $hauteurfoot;
			}
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, 1, $this->bodytxtcolor, $this->stdLineStyle);
		}

	}