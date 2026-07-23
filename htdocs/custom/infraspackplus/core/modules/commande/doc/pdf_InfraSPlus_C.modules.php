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
	* 	\file		./infraspackplus/core/modules/commande/doc/pdf_InfraSPlus_C.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF command
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_C extends ModelePDFCommandes
	{
		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $draft_watermark;
		public $use_doli_addr_livr;
		public $doli_addr_livr_recep;
		public $show_sign_area;
		public $show_ExtraFieldsLines;
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
		public $arrayidcontact;
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
		public $CGV;
		public $files;
		public $usentascover;
		public $include_alias;
		public $with_picture;
		public $refcol;
		public $hide_discount;
		public $hide_cols;
		public $showwvccchk;
		public $wvcc_no_hr;
		public $show_tot_disc;
		public $show_vir;
		public $show_tva_btp;
		public $signvalue;
		public $hideInnerLines;
		public $add_recap;
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
		public $TotRem = array('HT' => 0, 'TTC' => 0, 'multicurrency_HT' => 0, 'multicurrency_TTC' => 0);
		public $hasService = 0;
		public $hasProduct = 0;
		public $nbrProdTot = 0;
		public $nbrProdDif = [];
		public $ecoTaxes = [];
		public $hasEcoTaxes = 0;
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
		public $posxcol6;
		public $posxcol7;
		public $posxcol8;
		public $posxcol9;
		public $posxcol10;
		public $posxcol11;
		public $largcol1;
		public $largcol2;
		public $largcol3;
		public $largcol4;
		public $largcol5;
		public $largcol6;
		public $largcol7;
		public $largcol8;
		public $largcol9;
		public $largcol10;
		public $tableau = [];	// Array of table to print
		public $heightforfooter;
		public $larg_tabtotal;
		public $larg_tabinfo;
		public $posxtabtotal;
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $use_tva_forfait;
		public $tva_forfait;
		public $largcol11;
		public $posystamp;
		public $raw_prices;
		public $typeadr;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'errors', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusOrderName');
			$this->description					= $langs->trans('PDFInfraSPlusOrderDescription');
			$this->titlekey						= 'PDFInfraSPlusOrderTitle';
			$this->defaulttemplate				= getDolGlobalString('COMMANDE_ADDON_PDF', '');
			$this->draft_watermark				= getDolGlobalString('COMMANDE_DRAFT_WATERMARK', '');
			$this->use_doli_addr_livr			= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 0);
			$this->doli_addr_livr_recep			= getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0) && !empty($this->use_doli_addr_livr) ? getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0) : 0;
			$this->show_sign_area				= getDolGlobalInt('INFRASPLUS_PDF_COMMANDE_SHOW_SIGNATURE', 0);
			$this->show_ExtraFieldsLines		= getDolGlobalInt('INFRASPLUS_PDF_EXFL_C', 0);
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 1;	// Display payment mode
			$this->option_condreg				= 1;	// Display payment terms
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
		*	@param		Commande	$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return		int								1 = OK, 0 = KO
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
			if (!empty($this->show_desc)) {
				$hidedesc	= 0;
			}
			$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_C') ? '' : '_C';
			$baseDir	= !empty($conf->commande->multidir_output[$conf->entity]) ? $conf->commande->multidir_output[$conf->entity] : $conf->commande->dir_output;
			if (!empty($baseDir)) {
				$object->fetch_thirdparty();
				if (!empty($this->show_ExtraFieldsLines)) {
					$extrafieldsline	= new ExtraFields($this->db);
					$extralabelsline	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
				}
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$this->show_ExtraFieldsLines	= '';
					$dir							= $baseDir;
					$file							= $dir.'/SPECIMEN.pdf';
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
					$this->listfreet			= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->CGV					= !empty($hookmanager->resArray['cgv']) ? $hookmanager->resArray['cgv'] : '';
					$this->files				= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					$this->usentascover			= !empty($hookmanager->resArray['usentascover']) ? $hookmanager->resArray['usentascover'] : '';
					$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : '';
					$this->with_picture			= !empty($hookmanager->resArray['hidepict']) ? $hookmanager->resArray['hidepict'] : '';
					$this->refcol				= !empty($hookmanager->resArray['refcol']) ? $hookmanager->resArray['refcol'] : '';
					$hidedesc					= !empty($hookmanager->resArray['hidedesc']) ? $hookmanager->resArray['hidedesc'] : '';
					$this->hide_discount		= !empty($hookmanager->resArray['hidedisc']) ? $hookmanager->resArray['hidedisc'] : '';
					$this->hide_cols			= !empty($hookmanager->resArray['hidecols']) ? $hookmanager->resArray['hidecols'] : '';
					$this->showwvccchk			= !empty($hookmanager->resArray['showwvccchk']) ? $hookmanager->resArray['showwvccchk'] : '';
					$this->show_tot_disc		= !empty($hookmanager->resArray['showtotdisc']) ? $hookmanager->resArray['showtotdisc'] : '';
					$this->show_vir				= !empty($hookmanager->resArray['showvir']) ? $hookmanager->resArray['showvir'] : '';
					$this->show_tva_btp			= !empty($hookmanager->resArray['showtvabtp']) ? $hookmanager->resArray['showtvabtp'] : '';
					$this->signvalue			= !empty($hookmanager->resArray['signvalue']) ? $hookmanager->resArray['signvalue'] : '';
					$this->hideInnerLines		= !empty($hookmanager->resArray['hideInnerLines']) ? $hookmanager->resArray['hideInnerLines'] : '';
					$this->add_recap			= !empty($hookmanager->resArray['subtotal_add_recap']) ? $hookmanager->resArray['subtotal_add_recap'] : (!empty($hookmanager->resArray['infrastructure_add_recap']) ? $hookmanager->resArray['infrastructure_add_recap'] : '');
					$hookmanager->resArray		= [];
					if (!empty($this->usentascover)) {
						$this->first_page_empty	= 1;	// Comme on veut une page de garde on créé une page vide en premier puis on insère la note prévue sur celle-ci
					}
					// Si on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information Sauf si on utilise les prix par client et que l'otion d'affichage des références client est sur 1
					$hideref			= empty($this->refcol) || (getDolGlobalInt('PRODUIT_CUSTOMER_PRICES', 0) && getDolGlobalInt('PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE', 0) == 1) ? 0 : 1;
					$nblignes			= count($object->lines);	// Set nblignes with the new facture lines content after hook
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
					$tagvs						= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('PDFInfraSPlusOrderTitle'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('PDFInfraSPlusOrderTitle').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
					$watermarkedPages			= array($pdf->getPage() => true);	// Track pages with watermark to avoid double rendering in while loops
					$pagenb						= 1;
					// Default PDF parameters
					$this->stdLineColor			= array(128, 128, 128);
					$this->stdLineStyle			= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineW				= $this->tblLineW; // épaisseur par défaut dans TCPDF = 0.2
					$this->bgLineColor			= $this->bg_color;
					$this->bgLineStyle			= array('width'=>$this->bgLineW, 'dash'=>$this->bgLineDash, 'cap'=>$this->bgLineCap, 'color'=>$this->bgLineColor);
					$this->tblLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
					$this->verLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->verLineColor);
					$this->horLineStyle			= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$this->signLineStyle		= array('width'=>$this->signLineW, 'dash'=>$this->signLineDash, 'cap'=>$this->signLineCap, 'color'=>$this->signLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Use of multicurrency for this document
					$this->use_multicurrency	= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;
					// First loop on each lines to prepare calculs and variables
					$isTitleToList				= 0;
					$isTitleToCondense			= 0;
					$subtotalRecap				= [];
					$descWorksHidden			= [];
					$lineToHide					= [];
					$realpatharray				= [];
					$pricesObjProd				= [];
					$listObjBib					= [];
					$listDescBib				= [];
					$objproduct					= new Product($this->db);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$isOuvrage		= isModEnabled('ouvrage') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modOuvrage');
						$isSubTotalLine	= isModEnabled('subtotal') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
						$isProd			= !empty($object->lines[$i]->fk_product) && empty($isOuvrage) && empty($isSubTotalLine) ? $objproduct->fetch($object->lines[$i]->fk_product) : 0;
						// Test des options Sous-total
						if (!empty($isSubTotalLine)) {	// ATM lines
							if ($object->lines[$i]->qty < 10) {	// Sous-titres ATM
								// Titre / sous titre à afficher sous forme de liste
								if (!empty($object->lines[$i]->array_options['options_print_as_list']) && $object->lines[$i]->array_options['options_print_as_list'] > 0) {
									$isTitleToList	= $object->lines[$i]->id;
								}
								// Titre / sous titre à afficher condensé
								if (!empty($object->lines[$i]->array_options['options_print_condensed']) && $object->lines[$i]->array_options['options_print_condensed'] > 0) {
									$isTitleToCondense	= $object->lines[$i]->id;
								}
							} else {	// Sous-totaux ATM (ou texte libre ???)
								// SubTotal module with recap option OU Sous-totaux fusionnés avec les sous-titres
								if (!empty($this->add_recap) || !empty($this->subti_with_subto)) {
									$subtotalRecap	= pdf_InfraSPlus_subtotal_getrecap ($object, $i, $subtotalRecap);
								}
							}
						}
						// Sous-totaux Infrastructure : collecte pour récap (si add_recap actif)
						if (!empty($this->add_recap) && infraspackplus_isInfrastructureTotal($object->lines[$i])) {
							$subtotalRecap	= pdf_InfraSPlus_subtotal_getrecap($object, $i, $subtotalRecap);
						}
						// determine category of operation
						$this->hasProduct	+= $object->lines[$i]->product_type == Product::TYPE_PRODUCT ? 1 : 0;	// Products
						$this->hasService	+= $object->lines[$i]->product_type == Product::TYPE_SERVICE ? 1 : 0;	// Services
						// Comportements spéciaux => ouvrage ou sous-total avec le contenu masqué, réduit ou condensé
						if (!empty($isTitleToList)) {
							$idParentTitle	= pdf_InfraSPlus_escapeEns($object, $i, -3);	// Ligne sous un titre / sous titre à afficher sous forme de liste
							if (!empty($idParentTitle) && $idParentTitle == $isTitleToList) {
								$lineToHide[]	= $object->lines[$i]->id;
								$desc			= !empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : '');
								$label			= $isProd > 0 ? $objproduct->label : $desc;
								if (empty($descWorksHidden[$idParentTitle]['nb'])) {
									$descWorksHidden[$idParentTitle]['nb']	= 0;
								}
								$descWorksHidden[$idParentTitle]['desc']		.= (isset($descWorksHidden[$idParentTitle]['desc']) ? '<li>' : '<ul><li>').$label.($object->lines[$i]->qty > 1 ? ' ('.$object->lines[$i]->qty.')' : '').'</li>';
								$descWorksHidden[$idParentTitle]['descType']	= 3;
								$descWorksHidden[$idParentTitle]['nb']++;
							}
						}
						if (!empty($isTitleToCondense)) {
							$idParentTitle	= pdf_InfraSPlus_escapeEns($object, $i, -4);	// Ligne sous un titre / sous titre à afficher condensé
							if (!empty($idParentTitle) && $idParentTitle == $isTitleToCondense) {
								$lineToHide[]	= $object->lines[$i]->id;
								$desc			= !empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : '');
								$label			= $isProd > 0 ? $objproduct->label : $desc;
								if (empty($descWorksHidden[$idParentTitle]['nb'])) {
									$descWorksHidden[$idParentTitle]['nb']	= 0;
								}
								$descWorksHidden[$idParentTitle]['desc']		.= (isset($descWorksHidden[$idParentTitle]['desc']) ? ', ' : '<br/>').$label.($object->lines[$i]->qty > 1 ? ' ('.$object->lines[$i]->qty.')' : '');
								$descWorksHidden[$idParentTitle]['descType']	= 4;
								$descWorksHidden[$idParentTitle]['nb']++;
							}
						}
						// Comportements spéciaux => ouvrage avec le contenu masqué, réduit ou condensé
						if (GETPOSTINT('OUVRAGE_HIDE_PRODUCT_DETAIL') == 1) {	// Option du module ouvrage d'Inovea pour n'afficher que les ouvrages
							$idParentWork	= pdf_InfraSPlus_escapeEns($object, $i, 1);	// Composants d'ouvrage Inovea ayant le statut masqué
							if (!empty($idParentWork)) {
								$idParentLine	= $object->lines[$i]->fk_parent_line;
								if (!empty($idParentLine)) {
									$lineToHide[]	= $object->lines[$i]->id;
									if (empty($descWorksHidden[$idParentLine]['nb'])) {
										$descWorksHidden[$idParentLine]['nb']	= 0;
									}
									$descWorksHidden[$idParentLine]['desc']	= '';
									$descWorksHidden[$idParentLine]['nb']++;
								}
							}
						}
						if (!empty($this->hidden_ouv)) {
							$idParentWork	= pdf_InfraSPlus_escapeEns($object, $i, 3);	// Composants d'ouvrage Inovea ayant le statut détails en liste
							if (!empty($idParentWork)) {
								$idParentLine	= $object->lines[$i]->fk_parent_line;
								if (!empty($idParentLine)) {
									$lineToHide[]	= $object->lines[$i]->id;
									$desc			= !empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : '');
									$label			= $isProd > 0 ? $objproduct->label : $desc;
									if (empty($descWorksHidden[$idParentLine]['nb'])) {
										$descWorksHidden[$idParentLine]['nb']	= 0;
									}
									$descWorksHidden[$idParentLine]['desc']		.= (isset($descWorksHidden[$idParentLine]['desc']) ? '<li>' : '<ul><li>').$label.($object->lines[$i]->qty > 1 ? ' ('.$object->lines[$i]->qty.')' : '').'</li>';
									$descWorksHidden[$idParentLine]['descType']	= 3;
									$descWorksHidden[$idParentLine]['nb']++;
								}
							}
							$idParentWork	= pdf_InfraSPlus_escapeEns($object, $i, 4);	// Composants d'ouvrage Inovea ayant le statut détails condensés
							if (!empty($idParentWork)) {
								$idParentLine	= $object->lines[$i]->fk_parent_line;
								if (!empty($idParentLine)) {
									$lineToHide[]	= $object->lines[$i]->id;
									$desc			= !empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : '');
									$label			= $isProd > 0 ? $objproduct->label : $desc;
									if (empty($descWorksHidden[$idParentLine]['nb'])) {
										$descWorksHidden[$idParentLine]['nb']	= 0;
									}
									$descWorksHidden[$idParentLine]['desc']		.= (isset($descWorksHidden[$idParentLine]['desc']) ? ', ' : '<br/>').$label.($object->lines[$i]->qty > 1 ? ' ('.$object->lines[$i]->qty.')' : '');
									$descWorksHidden[$idParentLine]['descType']	= 4;
									$descWorksHidden[$idParentLine]['nb']++;
								}
							}
						}
						// Positionne $this->atleastonediscount si on a au moins une remise
						if (!empty($object->lines[$i]->remise_percent)) {	// remise de ligne
							$this->atleastonediscount++;
							if (!empty($this->show_tot_disc) && empty($this->show_disc_ttc)) {
								$this->TotRem['TTC']				+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht);
								$this->TotRem['multicurrency_TTC']	+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht, [], 1);
							}
							if (!empty($this->show_disc_tot)) {
								$this->TotRem['HT']					+= pdf_InfraSPlus_getTotRem($object, $i, 1);
								$this->TotRem['multicurrency_HT']	+= pdf_InfraSPlus_getTotRem($object, $i, 1, [], 1);
							}
							if (!empty($this->show_disc_ttc)) {
								$this->TotRem['TTC']				+= pdf_InfraSPlus_getTotRem($object, $i, 0);
								$this->TotRem['multicurrency_TTC']	+= pdf_InfraSPlus_getTotRem($object, $i, 0, [], 1);
							}
						}
						// remise calculé globale (type InfraSDiscount)
						if ($object->lines[$i]->info_bits == 2) {
							if (!empty($this->show_tot_disc) && empty($this->show_disc_ttc)) {
								$this->TotRem['TTC']				+= abs(empty($this->only_ht) ? $object->lines[$i]->total_ttc : $object->lines[$i]->total_ht);
								$this->TotRem['multicurrency_TTC']	+= abs(empty($this->only_ht) ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->multicurrency_total_ht);
							}
							if (!empty($this->show_disc_tot)) {
								$this->TotRem['HT']					+= abs($object->lines[$i]->total_ht);
								$this->TotRem['multicurrency_HT']	+= abs($object->lines[$i]->multicurrency_total_ht);
							}
							if (!empty($this->show_disc_ttc)) {
								$this->TotRem['TTC']				+= abs($object->lines[$i]->total_ttc);
								$this->TotRem['multicurrency_TTC']	+= abs($object->lines[$i]->multicurrency_total_ttc);
							}
						}
						// Remise automatique ou affichage des prix bruts
						if ($isProd > 0 && !empty($this->discount_auto) && $object->lines[$i]->subprice < $objproduct->price) {
							$this->atleastonediscount++;
							$pricesObjProd[$i]['pu_ht']						= $objproduct->price;
							$pricesObjProd[$i]['pu_ttc']					= $objproduct->price_ttc;
							$pricesObjProd[$i]['multicurrency_pu_ht']		= $this->use_multicurrency ? $objproduct->price * $object->multicurrency_tx : $objproduct->price;
							$pricesObjProd[$i]['multicurrency_pu_ttc']		= $this->use_multicurrency ? $objproduct->price_ttc * $object->multicurrency_tx : $objproduct->price_ttc;
							$pricesObjProd[$i]['total_ht']					= $objproduct->price * $object->lines[$i]->qty;
							$pricesObjProd[$i]['total_ttc']					= $objproduct->price_ttc * $object->lines[$i]->qty;
							$pricesObjProd[$i]['multicurrency_total_ht']	= ($this->use_multicurrency ? $objproduct->price * $object->multicurrency_tx : $objproduct->price) * $object->lines[$i]->qty;
							$pricesObjProd[$i]['multicurrency_total_ttc']	= ($this->use_multicurrency ? $objproduct->price_ttc * $object->multicurrency_tx : $objproduct->price_ttc) * $object->lines[$i]->qty;
							$pricesObjProd[$i]['remise']					= (($objproduct->price - $object->lines[$i]->subprice) * 100) / $objproduct->price;
							if (!empty($this->show_tot_disc) && empty($this->show_disc_ttc)) {
								$this->TotRem['TTC']				+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht, $pricesObjProd[$i]);
								$this->TotRem['multicurrency_TTC']	+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht, $pricesObjProd[$i], 1);
							}
							if (!empty($this->show_disc_tot)) {
								$this->TotRem['HT']					+= pdf_InfraSPlus_getTotRem($object, $i, 1, $pricesObjProd[$i]);
								$this->TotRem['multicurrency_HT']	+= pdf_InfraSPlus_getTotRem($object, $i, 1, $pricesObjProd[$i], 1);
							}
							if (!empty($this->show_disc_ttc)) {
								$this->TotRem['TTC']				+= pdf_InfraSPlus_getTotRem($object, $i, 0, $pricesObjProd[$i]);
								$this->TotRem['multicurrency_TTC']	+= pdf_InfraSPlus_getTotRem($object, $i, 0, $pricesObjProd[$i], 1);
							}
						} else {
							$pricesObjProd[$i]	= [];
						}
						// Collecte des totaux par valeur de tva
						if (empty($isOuvrage) && empty($isSubTotalLine)) {
							$tvaligne		= $this->use_multicurrency ? doubleval($object->lines[$i]->multicurrency_total_tva) : doubleval($object->lines[$i]->total_tva);
							$htligne		= $this->use_multicurrency ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
							$localtax1ligne	= $object->lines[$i]->total_localtax1;
							$localtax2ligne	= $object->lines[$i]->total_localtax2;
							$localtax1_rate	= $object->lines[$i]->localtax1_tx;
							$localtax2_rate	= $object->lines[$i]->localtax2_tx;
							$localtax1_type	= $object->lines[$i]->localtax1_type;
							$localtax2_type	= $object->lines[$i]->localtax2_type;
							if (!empty($object->remise_percent)) {
								$htligne		-= ($htligne * $object->remise_percent) / 100;
								$tvaligne		-= ($tvaligne * $object->remise_percent) / 100;
								$localtax1ligne	-= ($localtax1ligne * $object->remise_percent) / 100;
								$localtax2ligne	-= ($localtax2ligne * $object->remise_percent) / 100;
							}
							$vatrate	= (string) $object->lines[$i]->tva_tx;
							// Retrieve type from database for backward compatibility with old records
							if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
								&& (!empty($localtax1_rate) || !empty($localtax2_rate))) { // and there is local tax
								$localtaxtmp_array	= getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $this->emetteur);
								$localtax1_type		= isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
								$localtax2_type		= isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
							}
							// retrieve global local tax
							if (!empty($localtax1_type) && $localtax1ligne != 0) {
								$this->localtax1[$localtax1_type][$localtax1_rate]	+= $localtax1ligne;
							}
							if (!empty($localtax2_type) && $localtax2ligne != 0) {
								$this->localtax2[$localtax2_type][$localtax2_rate]	+= $localtax2ligne;
							}
							if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
								$vatrate	.= '*';
							}
							// Fill $this->tva and $this->tva_array
							if (!isset($this->tva[$vatrate])) {
								$this->tva[$vatrate]	= 0;
							}
							$vatcode		= $object->lines[$i]->vat_src_code;
							$vatrate_code	= $vatcode ? ' ('.$vatcode.')' : '';
							$vatlabel		= infraspackplus_showVatLabel($vatcode, $this->emetteur->country_code);
							if (empty($this->tva_array[$vatrate.$vatrate_code]['amount'])) {
								$this->tva_array[$vatrate.$vatrate_code]['amount']	= 0;
							}
							if (empty($this->tva_array[$vatrate.$vatrate_code]['base'])) {
								$this->tva_array[$vatrate.$vatrate_code]['base']	= 0;
							}
							$this->tva[$vatrate]						+= $tvaligne;	// ->tva is abandonned, we use now ->tva_array that is more complete
							$this->tva_array[$vatrate.$vatrate_code]	= array('vatrate'	=> $vatrate,
																				'vatcode'	=> $vatcode,
																				'base'		=> $this->tva_array[$vatrate.$vatrate_code]['base'] + $htligne,
																				'amount'	=> $this->tva_array[$vatrate.$vatrate_code]['amount'] + $tvaligne,
																				'label'		=> (!empty($vatlabel) && $vatlabel != -1 ? $vatlabel : '')
																				);
						} elseif (!empty($isSubTotalLine) && !empty($this->hideInnerLines) && !empty($object->lines[$i]->TTotal_tva_array)) {	// On subtotal lines TVA infos are creatre by the module
							foreach ($object->lines[$i]->TTotal_tva_array as $TTotal_tva_arrays => $TTotal_tva_array) {
								$vatlabel								= infraspackplus_showVatLabel($TTotal_tva_array['vatcode'], $this->emetteur->country_code);
								$this->tva_array[$TTotal_tva_arrays]	= array('vatrate'	=> $TTotal_tva_array['vatrate'],
																				'vatcode'	=> $TTotal_tva_array['vatcode'],
																				'base'		=> $this->tva_array[$TTotal_tva_arrays]['base'] + $TTotal_tva_array['base'],
																				'amount'	=> $this->tva_array[$TTotal_tva_arrays]['amount'] + $TTotal_tva_array['amount'],
																				'label'		=> (!empty($vatlabel) && $vatlabel != -1 ? $vatlabel : '')
																				);
							}
						}
						// detect if there is at least one image to show
						if (!empty($this->with_picture) && $isProd > 0) {
							$ecmfile	= new EcmFiles($this->db);
							if (!empty($this->old_path_photo)) {
								$pdir[0]	= get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id .'/photos/';
								$pdir[1]	= get_exdir(0, 0, 0, 0, $objproduct, 'product').dol_sanitizeFileName($objproduct->ref).'/';
							} else {
								$pdir[0]	= get_exdir(0, 0, 0, 0, $objproduct, 'product'); // default
								$pdir[1]	= get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id .'/photos/';		// alternative
							}
							$arephoto	= false;
							$onlyOne	= $this->only_one_picture ? (in_array($objproduct->id, $listObjBib) ? 1 : 0) : 0;
							foreach ($pdir as $midir) {
								if (!$arephoto && !$onlyOne) {
									$dir		= ($objproduct->entity != $conf->entity ? $conf->product->multidir_output[$objproduct->entity] : $conf->product->dir_output).'/'.$midir;
									$listPhotos	= [];
									// We recover all the photos attached to the product and we find their position in the ECM
									foreach ($objproduct->liste_photos($dir, 0) as $key => $obj) {
										$relpath	= ($objproduct->entity == 1 ? '' : $objproduct->entity.'/').'produit/'.$midir.$obj['photo'];
										$hasecmfile	= $ecmfile->fetch(0, '', $relpath, '', '', $objproduct->table_element, $objproduct->id);
										if ($hasecmfile > 0) {
											$obj['position']	= $ecmfile->position;
											$listPhotos[]		= $obj;
										}
									}
									// Sort the photos by position
									if (!empty($listPhotos)) {
										usort($listPhotos, function($a, $b) {
											return intval($a['position']) - intval($b['position']);
										});
										// Use the first sorted photo
										// TODO we could use a configuration to choose if we want the first, the last or more than one photo
										$obj	= $listPhotos[0];
										if (empty($this->cat_hq_image)) {	// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
											if (!empty($obj['photo_vignette'])) {
												$filename	= $obj['photo_vignette'];
											} else {
												$filename	= $obj['photo'];
											}
										} else {
											$filename	= $obj['photo'];
										}
										$realpath		= $dir.$filename;
										$listObjBib[]	= $objproduct->id;
										$arephoto		= true;
									}
								}
							}
							if (!empty($realpath) && !empty($arephoto)) {
								$realpatharray[$i]	= $realpath;
							} elseif (!empty($onlyOne)) {
								$realpatharray[$i]	= 'done';
							} else {
								$realpatharray[$i]	= '';
							}
						} else {
							$realpatharray[$i]	= '';
						}
						// ecoTaxes
						// Incluse dans les ouvrages
						if ($isProd > 0 && isModEnabled('ouvrage') && is_array($this->listPrefixEcotax) && count($this->listPrefixEcotax) > 0) {
							foreach ($this->listPrefixEcotax as $PrefixEcotax) {
								if (! isset($this->ecoTaxes[$PrefixEcotax]['ht'])) {
									$this->ecoTaxes[$PrefixEcotax]['ht']	= 0;
								}
								if (! isset($this->ecoTaxes[$PrefixEcotax]['ttc'])) {
									$this->ecoTaxes[$PrefixEcotax]['ttc']	= 0;
								}
								$this->ecoTaxes[$PrefixEcotax]['ht']	+= preg_match('/^'.$PrefixEcotax.'(.*)/', $objproduct->ref, $reg)								? $object->lines[$i]->total_ht									: 0;
								$this->ecoTaxes[$PrefixEcotax]['ttc']	+= preg_match('/^'.$PrefixEcotax.'(.*)/', $objproduct->ref, $reg)								? $object->lines[$i]->total_ttc									: 0;
								$this->hasEcoTaxes						= !empty($this->ecoTaxes[$PrefixEcotax]['ht']) || !empty($this->ecoTaxes[$PrefixEcotax]['ttc'])	? $outputlangs->transnoentities('PDFInfraSPlusInclEcoTaxes')	: $this->hasEcoTaxes;
							}
						}
						// Attribut supplémentaires de produit
						if ($isProd > 0 && !empty($this->exfEcoTax)) {
							if (!empty($objproduct->array_options['options_'.$this->exfEcoTax])) {
								if (! isset($this->ecoTaxes[$this->exfEcoTax]['ht'])) {
									$this->ecoTaxes[$this->exfEcoTax]['ht']		= 0;
								}
								if (! isset($this->ecoTaxes[$this->exfEcoTax]['ttc'])) {
									$this->ecoTaxes[$this->exfEcoTax]['ttc']	= 0;
								}
								$this->ecoTaxes[$this->exfEcoTax]['ht']		+= $objproduct->array_options['options_'.$this->exfEcoTax] * $object->lines[$i]->qty;
								$this->ecoTaxes[$this->exfEcoTax]['ttc']	+= $objproduct->array_options['options_'.$this->exfEcoTax] * $object->lines[$i]->qty * (1 + ($object->lines[$i]->tva_tx / 100));
								$this->hasEcoTaxes							= $outputlangs->transnoentities('PDFInfraSPlusInclEcoTaxes');
							}
						}
						// Calcul du nombre de produit du document si option
						if (!empty($this->show_qty_prod_tot) && $object->lines[$i]->product_type == 0) {
							$this->nbrProdTot	+= $object->lines[$i]->qty;
							if (!in_array($object->lines[$i]->product_ref, $this->nbrProdDif)) {
								$this->nbrProdDif[]	= $object->lines[$i]->product_ref;
							}
						}
					}
					// Define width and position of notes frames
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->larg_util_txt	= $this->larg_util_cadre - (($this->Rounded_rect * 2) + 2);
					$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->refcol) && empty($this->show_num_col)) {
						$this->larg_ref	= 0;
					}
					if (empty($this->product_use_unit)) {
						$this->larg_unit	= 0;
					}
					if (!empty($this->hide_qty)) {
						$this->larg_qty	= 0;
					}
					if (!empty($this->hide_up)) {
						$this->larg_up	= 0;
					}
					if (!empty($this->hide_vat) || !empty($this->hide_vat_col)) {
						$this->larg_tva	= 0;
					}
					if (!empty($this->hide_discount)) {
						$this->larg_discount	= 0;
					} else if (empty($this->atleastonediscount)) {
						$this->larg_discount	= 0;
					}
					if (!empty($this->hide_discount)) {
						$this->larg_updisc		= 0;
					} else if (empty($this->show_up_discounted)) {
						$this->larg_updisc		= 0;
					} else if (empty($this->atleastonediscount)) {
						$this->larg_updisc		= 0;
					}
					if (empty($object->situation_cycle_ref)) {
						$this->larg_progress	= 0;
					}
					if (empty($this->show_ttc_col)) {
						$this->larg_totalttc	= 0;
					}
					if (empty($this->hide_cols)) {
						// Largeur variable suivant la place restante
						$this->larg_desc	= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_unit + $this->larg_up + $this->larg_tva + $this->larg_discount + $this->larg_updisc + $this->larg_progress + $this->larg_totalht + $this->larg_totalttc);
					} else {
						$this->num_desc		= 1;
						$this->larg_desc	= $this->larg_util_cadre;
					}
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
											'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
											'up'		=> array('col' => $this->num_up,		'larg' => $this->larg_up,		'posx' => 0),
											'tva'		=> array('col' => $this->num_tva,		'larg' => $this->larg_tva,		'posx' => 0),
											'discount'	=> array('col' => $this->num_discount,	'larg' => $this->larg_discount,	'posx' => 0),
											'updisc'	=> array('col' => $this->num_updisc,	'larg' => $this->larg_updisc,	'posx' => 0),
											'progress'	=> array('col' => $this->num_progress,	'larg' => $this->larg_progress,	'posx' => 0),
											'totalht'	=> array('col' => $this->num_totalht,	'larg' => $this->larg_totalht,	'posx' => 0),
											'totalttc'	=> array('col' => $this->num_totalttc,	'larg' => $this->larg_totalttc,	'posx' => 0)
											);
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1) {
							$this->largcol1		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 2) {
							$this->largcol2		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 3) {
							$this->largcol3		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 4) {
							$this->largcol4		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 5) {
							$this->largcol5		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 6) {
							$this->largcol6		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 7) {
							$this->largcol7		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 8) {
							$this->largcol8		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 9) {
							$this->largcol9		= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 10) {
							$this->largcol10	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 11) {
							$this->largcol11	= $ncol_array['larg'];
						}
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3		= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4		= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5		= $this->posxcol4	+ $this->largcol4;
					$this->posxcol6		= $this->posxcol5	+ $this->largcol5;
					$this->posxcol7		= $this->posxcol6	+ $this->largcol6;
					$this->posxcol8		= $this->posxcol7	+ $this->largcol7;
					$this->posxcol9		= $this->posxcol8	+ $this->largcol8;
					$this->posxcol10	= $this->posxcol9	+ $this->largcol9;
					$this->posxcol11	= $this->posxcol10	+ $this->largcol10;
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
						} elseif ($ncol_array['col'] == 6) {
							$this->tableau[$ncol]['posx']	= $this->posxcol6;
						} elseif ($ncol_array['col'] == 7) {
							$this->tableau[$ncol]['posx']	= $this->posxcol7;
						} elseif ($ncol_array['col'] == 8) {
							$this->tableau[$ncol]['posx']	= $this->posxcol8;
						} elseif ($ncol_array['col'] == 9) {
							$this->tableau[$ncol]['posx']	= $this->posxcol9;
						} elseif ($ncol_array['col'] == 10) {
							$this->tableau[$ncol]['posx']	= $this->posxcol10;
						} elseif ($ncol_array['col'] == 11) {
							$this->tableau[$ncol]['posx']	= $this->posxcol11;
						}
					}
					// Define width and position of secondary tables columns
					$i			= 0;
					$tableauCol	= $this->tableau;
					usort($tableauCol, function($a, $b) {	// Sort by column numbering
						return $a['col'] > $b['col'];
					});
					$larg_tabinfo	= 0;
					foreach($tableauCol as $ncol => $ncol_array) {
						if ($ncol_array['larg'] > 0) {
							$larg_tabinfo	+= $ncol_array['larg'];
							$i++;
						}
						if ($i > 1 && $larg_tabinfo > 80) {
							break;
						}
					}
					$this->larg_tabtotal	= $this->larg_util_cadre - $larg_tabinfo;
					$this->larg_tabtotal	= ($this->larg_tabtotal < 80 ? 80 : $this->larg_tabtotal) + 12;
					$this->larg_tabinfo		= $this->larg_util_cadre - $this->larg_tabtotal;
					$this->posxtabtotal		= $this->page_largeur - $this->marge_droite - $this->larg_tabtotal;
					// Calculs de positions
					$this->tab_hl			= 4;
					if (isModEnabled('subtotal')) {
						$pdf->outputlangs	= $outputlangs;
						$pdf->show_ttc_col	= $this->show_ttc_col;
						$pdf->heightline	= $this->tab_hl;
						$pdf->totalht_posx	= $this->tableau['totalht']['posx'];
						$pdf->totalht_larg	= $this->tableau['totalht']['larg'];
						$pdf->totalttc_posx	= $this->tableau['totalttc']['posx'];
						$pdf->totalttc_larg	= $this->tableau['totalttc']['larg'];
					}
					$this->decal_round	= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head				= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead		= $head['totalhead'];
					$hauteurcadre		= $head['hauteurcadre'];
					$tab_top			= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
					$tab_top_newpage	= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table	= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforheader	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
					$ht_colinfo			= $this->_tableau_info($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht1_coltotal		= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht2_coltotal		= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 1, $this->horLineStyle) : 0;
					$ht_coltotal		= 0;
					if (!empty($this->show_sign_area)) {
						if ($ht2_coltotal > 3) {
							$ht_coltotal	= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 1);
						} else {
							$ht_coltotal	= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
						}
					}
					$ht_coltotal			+= $ht1_coltotal + $ht2_coltotal;
					$heightforinfotot		= $ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal;
					$heightforinfotot		+= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$this->heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					// Insert a empty page or a cover page first
					if (!empty($this->first_page_empty)) {
						if (!empty($this->usentascover)) {
							$height_cover	= pdf_InfraSPlus_Notes($pdf, $object, array($this->usentascover), $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $this->heightforfooter, $this->page_hauteur, $this->Rounded_rect, 0, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, -2, 0);
						}
						if (!empty($this->usentascover) && empty($height_cover)) {
							$this->first_page_empty	= 0;	// we want an empty first page for the cover, but no cover is found, so we don't need an empty first page anymore.
						} else {
							$pdf->AddPage('', '', true);
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
							$watermarkedPages[$pdf->getPage()]	= true;
							$pdf->setPage(2);
							$tab_top							= $tab_top_newpage;
						}
					}
					// Incoterm
					$height_incoterms	= 0;
					if (isModEnabled('incoterm')) {
						$desc_incoterms	= $object->getIncotermsForPDF();
						if (!empty($desc_incoterms)) {
							$pdf->SetFont('', '', $default_font_size - 1);
							$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $tab_top, dol_htmlentitiesbr($desc_incoterms), 0, 1);
							$nexY				= $pdf->GetY();
							$height_incoterms	= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
							if (!empty($this->showtblline) && empty($this->desc_full_line)) {
								$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_incoterms + 1, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
							}
							$height_incoterms	+= $this->tab_hl;
						}
					}
					$tab_top		+= $height_incoterms;
					// Livraison
					$height_livr	= 0;
					if (!empty($head['livrshow']) && getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION')) {
						$pdf->SetFont('', 'B', $default_font_size + 2);
						$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusLivr')), 0, 1);
						$xlivr	= $pdf->GetX() + $pdf->GetStringWidth($outputlangs->transnoentities('PDFInfraSPlusLivr'), '', 'B', $default_font_size + 2) + 5;
						if ($this->adrlivr > 0) {
							$addresslivrstatic	= new Address($this->db);
							$addresslivrfound	= $addresslivrstatic->fetch($this->adrlivr, $object->thirdparty->id);
							if ($addresslivrfound !== 1) {
								$addresslivrstatic	= '';
							}
						}
						$adrlivrName	= !empty($addresslivrstatic->name) ? $addresslivrstatic->name : (!empty($head['livrshow_name']) ? $head['livrshow_name'] : '');
						if (!empty($adrlivrName)) {
							$pdf->SetFont('', 'B', $default_font_size);
							$pdf->writeHTMLCell($this->larg_util_txt - $xlivr - 3, $this->tab_hl, $xlivr, $tab_top + 0.6, dol_htmlentitiesbr($adrlivrName), 0, 1);
							$nexY	= $pdf->GetY();
						} else {
							$nexY	= $tab_top + 0.6;
						}
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell($this->larg_util_txt - $xlivr - 3, $this->tab_hl, $xlivr, $nexY, dol_htmlentitiesbr($head['livrshow']), 0, 1);
						$nexY			= $pdf->GetY();
						$height_livr	= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						if (!empty($this->showtblline) && empty($this->desc_full_line)) {
							$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_livr + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						}
						$height_livr	+= $this->tab_hl;
					}
					$tab_top	+= $height_livr;
					// Header informations after Address blocks
					$height_header_inf	= 0;
					if (!empty($this->header_after_addr)) {
						$tab_top	+= $this->space_headerafter;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						$txtC11		= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
						if ($object->statut == 0) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11	= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						$txtC12		= $outputlangs->transnoentities('PDFInfraSPlusOrderDate').' : '.dol_print_date($object->date_commande, 'day', false, $outputlangs, true);
						$largC12	= $this->larg_util_txt - $largC11;
						$xC12		= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12, 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY		= $tab_top + $this->tab_hl + 1;
						if ($object->ref_client) {
							$txtC21		= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_client);
							$largC21	= $pdf->GetStringWidth($txtC21, '', '', $default_font_size - 1) + 3;
							$pdf->MultiCell($largC21, $this->tab_hl, $txtC21, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						$txtC22	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt + $largC21, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$largC22	= $this->larg_util_txt - $largC21;
							$xC22		= $object->ref_client ? $this->posx_G_txt + $this->larg_util_txt - $largC22 : $this->posx_G_txt;
							$pdf->MultiCell($largC22, $this->tab_hl, $txtC22, 0, ($object->ref_client ? 'R' : 'L'), 0, 0, $xC22, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						if ($object->ref_client || !empty($txtC22)) {
							$nexY	+= $this->tab_hl + 1;
						}
						$height_header_inf	= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf	+= $this->tab_hl;
					}
					$tab_top		+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $this->heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, $this->first_page_empty);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// Gestion des ouvrages, titres, sous-titres et sous-totaux
						$isOuvrage		= isModEnabled('ouvrage') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modOuvrage');
						$isSubTotalLine	= isModEnabled('subtotal') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
						$isInfraSLine	= infraspackplus_isInfrastructureLine($object->lines[$i]) ? 1 : 0;
						$isInfraSTotal	= infraspackplus_isInfrastructureTotal($object->lines[$i]) ? 1 : 0;	// Sous-total infrastructure (qty 91..99)
						$colYOffset		= !empty($isInfraSTotal) ? 1.0 : 0;	// pdfAddTotal applique setCellPaddings T=1 au libellé du sous-total. Les MultiCell des colonnes voisines ne respectent pas ce padding (hauteur explicite + valign 'M'), d'où un décalage visuel de ~1mm. On compense en décalant manuellement le Y des MultiCell pour les sous-totaux infrastructure.
						$isSubTitle		= $isSubTotalLine && $object->lines[$i]->qty < 10 ? 1 : 0;	// Sous-titre ATM
						$isSubTotal		= $isSubTotalLine && $object->lines[$i]->qty > 90 ? 1 : 0;	// Sous-total ATM
						if (!empty($isSubTotal) && !empty($this->subti_with_subto)) {
							continue;	// Sous-totaux fusionnés avec les sous-titres
						}
						if (in_array($object->lines[$i]->id, $lineToHide)) {
							continue;	// Composants d'ouvrage ou détail de sous-titre à masquer
						}
						if (!empty(pdf_InfraSPlus_escapeEns($object, $i, 1))) {
							continue;	// Tous les composants d'ouvrage Inovea sont masqués
						}
						$curY	= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table)) {
							$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						} else {
							$pdf->setTopMargin($tab_top_newpage);
						}
						$pdf->setPageOrientation('', 1, $this->heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore				= $pdf->getPage();
						$showpricebeforepagebreak	= 1;
						// totaux fusionnés avec les titres et la prochaine ligne est un total
						$nbSubTotal					= 0;
						if (!empty($this->subti_with_subto) && count($subtotalRecap) > 0 && array_search($i + 1, array_column($subtotalRecap, 'line')) !== false) {
							$nbSubTotal++;	// la ligne suivante est un sous-total (déjà vérifé lors du test ci-dessus)
							// On parcourt jusqu'à 10 lignes en avant pour vérifier si se sont les sous-totaux (titres / sous-totaux imbriqués)
							for ($k = 2 ; $k < 10 ; $k++) {
								if (array_search($i + $k, array_column($subtotalRecap, 'line')) !== false) {
									$nbSubTotal++;
								} else {
									break;	// on arrête au 1er trou dans la suite des sous-totaux
								}
							}
						}
						// We don't want a title line alone at the end of the page (just before a page break)
						if (!empty($isSubTitle)) {
							$nextimglinesize	= !empty($this->with_picture) && empty($this->picture_under) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i + 1]) : [];	// Define size of image if we need it
							if (empty($this->picture_under) && empty($this->picture_after) && isset($nextimglinesize['height'])) {
								$nextlinehight	= $nextimglinesize['height'] + ($this->linkpictureurl ? $this->tab_hl * 2 : $this->tab_hl);
							} else {
								$nextlinehight	= $this->tab_hl;
							}
							if (($curY + $this->tab_hl + $nextlinehight) > ($this->page_hauteur - $this->heightforfooter)) {	// There is no space left for next line + total + free text
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
								$curY						= $heightforheader;
								$showpricebeforepagebreak	= 0;
							}
						}
						if (!empty($isSubTotal)) {
							if (($curY + $this->tab_hl) > ($this->page_hauteur - $this->heightforfooter)) {	// There is no space left for next line + total + free text
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
								$curY						= $heightforheader;
								$showpricebeforepagebreak	= 0;
							}
						}
						$colPicture		= $this->tableau['ref']['larg'] > 0 && $this->picture_in_ref ? 'ref' : 'desc';
						$imglinesize	= !empty($this->with_picture) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i]) : [];	// Define size of image if we need it
						$ht_url			= 0;
						if (!empty($imglinesize['width']) && !empty($imglinesize['height']) && $this->linkpictureurl) {
							$txturl	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $this->linkpictureurl);
							$ht_url	= $pdf->getStringHeight($this->tableau[$colPicture]['larg'], $txturl);
						}
						$pdf->SetY($curY);	// Resynchronise le curseur PDF sur la position reelle de la ligne : sans cela, si pdf_InfraSPlus_writelinedesc() ne dessine rien (ex: hook tiers renvoyant une erreur sur pdf_writelinedesc), $pdf->GetY() resterait sur une position obsolete et desynchroniserait $nexY (chevauchement visuel des lignes suivantes)
						// Hauteur de la référence
						$this->heightline	= $this->tab_hl;
						if (empty($this->hide_cols)) {
							// Reference
							if (!empty($this->refcol) || !empty($this->show_num_col)) {
								$pdf->startTransaction();
								$startline			= $curY;	// Utiliser $curY (position reelle de la ligne) plutot que $pdf->GetY(), qui peut etre desynchronise du curseur PDF interne
								$ref				= $isSubTitle || $isSubTotal || $isInfraSLine ? '' : (!empty($this->refcol) ? pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails) : $i + 1);
								$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
								$endline			= $pdf->GetY();
								$heightRef			= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
								// l'image existe et doit être dans la colonne réf. => hauteur URL + hauteur image + 1/2 ligne d'espacement SINON on réinitialise à 0
								$this->heightline	= !empty($this->picture_in_ref) && !empty($imglinesize['width']) && !empty($imglinesize['height']) ? $imglinesize['height'] + $ht_url + ($this->tab_hl / 2) : 0;
								// J'ai une hauteur de ligne, donc une image, et ça remplace le réf. => on n'ajoute rien SINON on ajoute la hauteur prise par la référence + 1 ligne d'espacement si j'ai une image
								$this->heightline	+= !empty($this->heightline) && !empty($this->picture_replace_ref) ? 0 : $heightRef + (!empty($this->heightline) && empty($this->picture_replace_ref) ? $this->tab_hl : 0);
								$pdf->rollbackTransaction(true);
							}
						}
						// Photo of product line first
						if (!empty($imglinesize['width']) && !empty($imglinesize['height']) && empty($this->picture_under) && empty($this->picture_after)) {
							if (($curY + ($this->picture_in_ref ? $this->heightline : $imglinesize['height']) + ($this->picture_padding * 2) + $ht_url) > ($this->page_hauteur - ($this->heightforfooter))) {	// If photo too high, we moved completely on new page
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
								$curY	= $heightforheader;
							}
							$PictureY	= $curY + ($this->picture_in_ref ? $heightRef + ($this->tab_hl / 2) : $this->picture_padding);
							$PictureY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau[$colPicture]['posx'], $PictureY, $this->tableau[$colPicture]['larg'], $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url);
							$curY		= ($this->picture_in_ref ? $curY : $PictureY) +	$this->picture_padding;
						}
						// Description of product line => preparation
						// Photo of product line between the label and the long description
						if (!empty($this->picture_under) && !empty($imglinesize['width']) && !empty($imglinesize['height'])) {
							if (($curY + $this->tab_hl + $imglinesize['height'] + $this->picture_padding) > ($this->page_hauteur - ($this->heightforfooter))) {	// If photo too high, we moved completely on new page
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
								$curY						= $heightforheader;
								$showpricebeforepagebreak	= 0;
							}
						}
						// extra fields and others informations after the long description
						$extraDet	= '';
						$idOuvrage	= 0;
						if ($object->lines[$i]->product_type != 9) {	// Standard line
							$pdf->SetLineStyle($this->horLineStyle);
							// extrafieldsline
							$extrafieldslines	= '';
							if (!empty($this->show_ExtraFieldsLines)) {
								$extrafieldslines	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor, $outputlangs);
							}
							$extraDet	.= empty($extrafieldslines) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$extrafieldslines.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
							// Custom values (weight, volume and code
							$WVCC		= '';
							if (!empty($this->showwvccchk)) {
								$WVCC	= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs, $this->emetteur);
							}
							$extraDet	.= empty($WVCC) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$WVCC.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
						} else {	// Ouvrage or sub-total line
							$idOuvrage	= !empty($object->lines[$i]->array_options['options_fk_ouvrage']) ? $object->lines[$i]->array_options['options_fk_ouvrage'] : '';
							if (!empty($descWorksHidden[$object->lines[$i]->id]['desc'])) {
								$extraDet	= $descWorksHidden[$object->lines[$i]->id]['desc'].($descWorksHidden[$object->lines[$i]->id]['descType'] == 3 ? '</ul>' : '');
							}
						}
						// Description of product line => printing
						$pageposdesc	= $pdf->getPage();
						$hide_desc		= !empty($hidedesc) ? $hidedesc : (!empty($object->lines[$i]->fk_product) && $this->only_one_desc ? (in_array($object->lines[$i]->fk_product, $listDescBib) ? 1 : 0) : 0);
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hide_desc, 0, $extraDet, null, $this->desc_full_line, 0, $this->with_picture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url, $this->picture_padding, $this->exfEcoTax);
						$ret			= !empty($object->lines[$i]->fk_product) ? $listDescBib[] = $object->lines[$i]->fk_product : '';
						$pageposafter	= $pdf->getPage();
						$posyafter		= $pdf->GetY();
						// Ouvrage ou Sous-total chaché => des lignes ultérieurs ne seront pas affichées - est-on en fin de parcours ?
						$nbChildren	= 0;
						if (!empty($descWorksHidden[$object->lines[$i]->id]['nb'])) {
							if (!empty($isSubTitle)) {
								$nbChildren	= $descWorksHidden[$object->lines[$i]->id]['nb'] + $nbSubTotal + 1;
							}
							if (!empty($idOuvrage) || !empty($isOuvrage)) {
								$nbChildren	= $descWorksHidden[$object->lines[$i]->id]['nb'];
							}
						}
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - (empty($nbChildren) && !empty($nbSubTotal) ? $nbSubTotal : $nbChildren) - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
									pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
									$watermarkedPages[$pdf->getPage()]	= true;
								}
							} else {
								$showpricebeforepagebreak	= 0;
							}
						} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblignes - (empty($nbChildren) && !empty($nbSubTotal) ? $nbSubTotal : $nbChildren) - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposafter + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
							}
						}
						$nexY	= $pdf->GetY();
						// Photo of product line after description
						if (!empty($imglinesize['width']) && !empty($imglinesize['height']) && empty($this->picture_under) && !empty($this->picture_after)) {
							$pageposimg	= $pdf->getPage();
							$nexY		+= $this->picture_padding;
							if (($nexY + $imglinesize['height'] + $ht_url) > ($this->page_hauteur - ($this->heightforfooter + ($i == ($nblignes - 1) ? $heightforinfotot : 0)))) {	// If photo too high, we moved completely on new page
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposimg + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
								$nexY						= $heightforheader;
								$showpricebeforepagebreak	= 0;
							}
							$widthpicture	= $this->desc_full_line ? $this->larg_util_txt : $this->tableau['desc']['larg'];
							$nexY			= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau['desc']['posx'], $nexY, $widthpicture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url);
							$nexY			+= $this->picture_padding;
						}
						$pageposafter	= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							if ($curY > ($this->page_hauteur - $this->heightforfooter - $this->tab_hl)) {
								$pdf->setPage($pageposafter);
								$curY	= $heightforheader;
							} else {
								$pdf->setPage($pageposdesc);
							}
						}
						$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						if (empty($this->hide_cols)) {
							// Reference
							if ((!empty($this->refcol) || !empty($this->show_num_col)) && (empty($this->picture_in_ref) || !empty($this->picture_in_ref) && empty($this->picture_replace_ref))) {
								$pagepos	= $pdf->getPage();
								$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY + $colYOffset, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
								$pdf->setPage($pagepos);
							}
							// Quantity
							if (empty($this->hide_qty)) {
								$qty	= pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
							// Unit
							if (!empty($this->product_use_unit)) {
								$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
								$pdf->writeHTMLCell($this->tableau['unit']['larg'], $this->heightline, $this->tableau['unit']['posx'], $curY + $colYOffset, $unit, 0, 1, false, true, $this->force_align_left_unit, true);
							}
							// Unit price
							if (empty($this->hide_up)) {
								if (empty($this->hide_discount)) {
									if (empty($this->hide_vat)) {
										$up_line	= pdf_InfraSPlus_getlineupexcltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									} else {
										$up_line	= pdf_InfraSPlus_getlineupincltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									}
								} else {
									if (empty($this->hide_vat)) {
										$up_line	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									} else {
										$up_line	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									}
								}
								$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, $up_line, '', 'R', 0, 1, $this->tableau['up']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
							// VAT Rate
							if (empty($this->hide_vat) && empty($this->hide_vat_col)) {
								$vat_rate	= pdf_InfraSPlus_getlinevatrate($pdf, $object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['tva']['larg'], $this->heightline, $vat_rate, '', 'R', 0, 1, $this->tableau['tva']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
							// Discount on line
							if (($object->lines[$i]->remise_percent && empty($this->hide_discount)) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
								$remise_percent	= pdf_InfraSPlus_getlineremisepercent($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
								$pdf->MultiCell($this->tableau['discount']['larg'], $this->heightline, $remise_percent, '', 'R', 0, 1, $this->tableau['discount']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
							// Discounted price
							if (($object->lines[$i]->remise_percent && empty($this->hide_discount) && $this->show_up_discounted) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
								if (empty($this->hide_vat)) {
									$up_disc	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
								} else {
									$up_disc	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
								}
								$pdf->MultiCell($this->tableau['updisc']['larg'], $this->heightline, $up_disc, '', 'R', 0, 1, $this->tableau['updisc']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
							// Total line
							// Sous-titre ATM fusionné avec son sous-total
							if (!empty($this->subti_with_subto) && count($subtotalRecap) > 0 && array_search($object->lines[$i]->rang, array_column($subtotalRecap, 'rang')) !== false && array_search($object->lines[$i]->qty, array_column($subtotalRecap, 'level')) !== false) {
								$keyForIdSubtotal	= array_search($object->lines[$i]->rang, array_column($subtotalRecap, 'rang'));
								$IdSubtotal			= $subtotalRecap[$keyForIdSubtotal]['line'];
								if (empty($this->hide_vat)) {
									$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $IdSubtotal, $outputlangs, $hidedetails, null, []);
								} else {
									$total_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $IdSubtotal, $outputlangs, $hidedetails, null, []);
								}
							} else {	// Standard
								if (empty($this->hide_vat)) {
									$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails, null, (!empty($this->raw_prices) ? $pricesObjProd[$i] : []));
								} else {
									$total_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails, null, (!empty($this->raw_prices) ? $pricesObjProd[$i] : []));
								}
							}
							$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							if ($this->show_ttc_col) {
								// Sous-titre ATM fusionné avec son sous-total
								if (!empty($this->subti_with_subto) && count($subtotalRecap) > 0 && array_search($object->lines[$i]->rang, array_column($subtotalRecap, 'rang')) !== false && array_search($object->lines[$i]->qty, array_column($subtotalRecap, 'level')) !== false) {
									$keyForIdSubtotal	= array_search($object->lines[$i]->rang, array_column($subtotalRecap, 'rang'));
									$IdSubtotal			= $subtotalRecap[$keyForIdSubtotal]['line'];
									$totalTTC_line		= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $IdSubtotal, $outputlangs, $hidedetails, null, []);
								} else {
									$totalTTC_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails, null, (!empty($this->raw_prices) ? $pricesObjProd[$i] : []));	// Standard
								}
								$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, $totalTTC_line, '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
						}
						// Add dash or space between line
						$separate	= pdf_InfraSPlus_separateLine ($object, $i);
						if ($separate == -1) {
							if (!empty($this->dash_between_line) && $i < ($nblignes - 1)) {
								$pdf->setPage($pageposafter);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
								$nexY	+= 2;
							} else {
								$nexY	+= $this->lineSep_hight;
							}
						} else {
							$nexY	+= $separate;
						}
						// Detect if some page were added automatically and output header, table and footer for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$this->heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1 && empty($this->first_page_empty)) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} elseif ($pagenb == 2 && $this->first_page_empty) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} elseif ($pagenb > ($this->first_page_empty ? 2 : 1)) {
								$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							}
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							// Extraire le contenu texte pour corriger le z-order (filigrane/en-tête avant texte)
							$savedContent	= '';
							if (method_exists($pdf, 'liftPageContent')) {
								$savedContent	= $pdf->liftPageContent();
							}
							if (empty($watermarkedPages[$pagenb])) {
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pagenb]	= true;
							}
							if (empty($this->small_head2)) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							} else {
								$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
							// Réinjecter le contenu texte après filigrane et en-tête
							if (method_exists($pdf, 'dropPageContent')) {
								$pdf->dropPageContent($savedContent);
							}
							$pdf->SetFillColor(255);
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						}
						if (isset($object->lines[$i + $nbChildren + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$this->heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1 && empty($this->first_page_empty)) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} elseif ($pagenb == 2 && $this->first_page_empty) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} elseif ($pagenb > ($this->first_page_empty ? 2 : 1)) {
								$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
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
							$nexY	= $heightforheader;
						}
					}
					$bottomlasttab	= $this->page_hauteur - $heightforinfotot - $this->heightforfooter - 1;
					if ($pagenb == 1 && empty($this->first_page_empty)) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} elseif ($pagenb == 2 && $this->first_page_empty) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} elseif ($pagenb > ($this->first_page_empty ? 2 : 1)) {
						$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					}
					$posyinfo		= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$posytot		= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$posyfreetext	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 0, $this->horLineStyle) : $posytot;
					if (!empty($this->show_sign_area)) {
						if ($ht2_coltotal > 3) {
							$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 1);
						} else {
							$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 0);
						}
					} else {
						$posysignarea	= $posyfreetext;
					}
					$posy	= $posyinfo > $posysignarea ? $posyinfo : $posysignarea;
					$posy	= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posy;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					// Cas INFRASTRUCTURE_PDF_TITLE_WITH_TOTAL : les sous-totaux ont été retirés de $object->lines par Infrastructure — reconstruction depuis le contexte
					if (!empty($this->add_recap)) {
						pdf_InfraSPlus_subtotal_getrecap_from_context($object, $subtotalRecap);
					}
					if (!empty($this->add_recap) && count($subtotalRecap) > 0) {	// SubTotal module with recap option
						$subtotalRecap	= pdf_InfraSPlus_compare($subtotalRecap, 'rang');
						$pdf->AddPage();	// New page for review
						pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
						$pagenb++;
						$watermarkedPages[$pagenb]	= true;
						if (empty($this->small_head2)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						} else {
							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						$pdf->SetFillColor(255);
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						$posytotrecap	= pdf_InfraSPlus_subtotal_recap($pdf, $object, $tab_top_newpage, $outputlangs, $subtotalRecap, $this, $ht1_coltotal, $this->heightforfooter);
						$pageposafter	= $pdf->getPage();
						// Detect if some page were added automatically and output header, table and footer for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$this->heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							// Extraire le contenu texte pour corriger le z-order (filigrane/en-tête avant texte)
							$savedContent	= '';
							if (method_exists($pdf, 'liftPageContent')) {
								$savedContent	= $pdf->liftPageContent();
							}
							if (empty($watermarkedPages[$pagenb])) {
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pagenb]	= true;
							}
							if (empty($this->small_head2)) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							} else {
								$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
							// Réinjecter le contenu texte après filigrane et en-tête
							if (method_exists($pdf, 'dropPageContent')) {
								$pdf->dropPageContent($savedContent);
							}
							$pdf->SetFillColor(255);
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						}
						$bottomlasttab	= $this->page_hauteur - $ht1_coltotal - $this->heightforfooter - 1;
						$this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
						$this->_pagefoot($pdf, $object, $outputlangs, 0);
					}
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					// If merge CGV is active
					if (!empty($this->CGV)) {
						pdf_InfraSPlus_CGV($pdf, $this->CGV, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					}
					// if merge files is active
					if (!empty($this->files)) {
						pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'fromInfraS' => 1);
					global $action;
					$reshook	= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if (!empty($this->main_umask)) {
						@chmod($file, octdec($this->main_umask));
					}
					$this->result	= array('fullpath' => $file);
					return 1;	// Pas d'erreur
				} else {
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			} else {
				$this->error	= $outputlangs->transnoentities('ErrorConstantNotDefined', 'COMMANDE_OUTPUTDIR');
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
		*	@return		array		$hauteurhead	'totalhead'		= height of header
		*											'hauteurcadre	= height of frame
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{
			$specialHead	= infraspackplus_fetchAllSpecialHeads(array($object->element));
			if (!empty($specialHead['rootFileName'])) {
				$specialhead	= 'pdf_'.$specialHead['rootFileName'].'_pagehead';
				$hauteurhead	= $specialhead($pdf, $object, $showaddress, $outputlangs, $this->headertxtcolor, $this->header_align_left, $this->decal_round, $this->formatpage, $this->logo, $this->emetteur, $this->tab_hl,
												$this->header_after_addr, $this->title_size, $this->titlekey, $this->ref_from_cust, $this->datesbold, $this->dates_br, $this->show_num_cli, $this->num_cli_frm,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, $this->add_creator_in_header, $this->use_iso_location, $this->adr, $this->typeadr, $this->adrlivr, $this->Rounded_rect,
												$this->customerAddrSelect, -2, -2, '', 0, [], '', -2, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
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
				$txtref	= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
				if ($object->statut == 0) {
					$pdf->SetTextColor(128, 0, 0);
					$txtref .= ' - '.$outputlangs->transnoentities('NotValidated');
				}
				$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
				$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
				$posy			+= $this->tab_hl;
				$txtdt			= $outputlangs->transnoentities('PDFInfraSPlusOrderDate').' : '.dol_print_date($object->date_commande, 'day', false, $outputlangs, true);
				$date_livraison	= $object->delivery_date;
				if (empty($this->dates_br))
					if (!empty($date_livraison)) {
						$txtdt	.= ' / '.$outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($date_livraison, 'day', false, $outputlangs, true);
					}
				$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				if (!empty($this->dates_br)) {
					if (!empty($date_livraison)) {
						$txtdt	= '';
						$posy	+= $this->tab_hl - 0.5;
						$txtdt	= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($date_livraison, 'day', false, $outputlangs, true);
						$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
					}
				}
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
			}
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			$this->arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
											'E' => $object->getIdContact('external', (!empty($this->doli_addr_livr_recep) ? 'SHIPPING' : 'CUSTOMER')),
											'L' => (empty($this->doli_addr_livr_recep) ? $object->getIdContact('external', 'SHIPPING') : [])
											);
			if (!empty($showaddress)) {
				$addresses		= [];
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $this->arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', 0, 0, -2, -2, $this->customerAddrSelect, $this->include_alias);
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
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom	= 0;
			if (!empty($hidetop)) {
				$hidetop	= -1;
			}
			$currency			= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				if ($pagenb == 1) {
					// Show currency informations
					$infocurrency	= !empty($this->hide_info_cur) ? '' : $outputlangs->transnoentities('AmountInCurrency', $outputlangs->transnoentitiesnoconv('Currency'.$currency));
					$pdf->MultiCell($pdf->GetStringWidth($infocurrency) + 3, 2, $infocurrency, '', 'R', 0, 1, $this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($infocurrency) + 3) - $this->decal_round, $tab_top - $this->tab_hl, true, 0, 0, false, 0, 'M', false);
				}
				// Table header
				if (!empty($this->title_bg)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				} else if (!empty($this->showtblline) && empty($this->desc_full_line)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
				// Table frame
				if (!empty($this->showtblline)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				} else {
					$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
				}
			} else if (!empty($this->showtblline) && empty($this->desc_full_line)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			}
			// Watermarks
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
			if (!empty($this->showverline) && empty($this->desc_full_line)) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol2,		$tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol3,		$tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol4,		$tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol5,		$tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol6,		$tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol7 > $this->posxcol6 && $this->posxcol7 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol7,		$tab_top, $this->posxcol7,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol8 > $this->posxcol7 && $this->posxcol8 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol8,		$tab_top, $this->posxcol8,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol9 > $this->posxcol8 && $this->posxcol9 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol9,		$tab_top, $this->posxcol9,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol10 > $this->posxcol9 && $this->posxcol10 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol10,	$tab_top, $this->posxcol10,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol11 > $this->posxcol10 && $this->posxcol11 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol11,	$tab_top, $this->posxcol11,	$tab_top + $tab_height, $this->verLineStyle);
				}
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (empty($this->hide_cols)) {
					if (!empty($this->refcol)) {
						$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (!empty($this->show_num_col)) {
						$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusNum'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (empty($this->hide_qty)) {
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Qty'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (!empty($this->product_use_unit)) {
						$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Unit'), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (empty($this->hide_up)) {
						if (empty($this->hide_vat)) {
							$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PriceUHT'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
						} else {
							$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PriceUTTC'), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
						}
					}
					if (empty($this->hide_vat) && empty($this->hide_vat_col)) {
						$pdf->MultiCell($this->tableau['tva']['larg'], $this->ht_top_table, $outputlangs->transnoentities('VAT'), '', 'C', 0, 1, $this->tableau['tva']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (!empty($this->atleastonediscount) && empty($this->hide_discount)) {
						$pdf->MultiCell($this->tableau['discount']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ReductionShort'), '', 'C', 0, 1, $this->tableau['discount']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
						if (!empty($this->show_up_discounted)) {
							$pdf->MultiCell($this->tableau['updisc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusDiscountedPrice'), '', 'C', 0, 1, $this->tableau['updisc']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
						}
					}
					if (empty($this->hide_vat)) {
						$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalHTShort'), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					} else {
						$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTCShort'), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
					if (!empty($this->show_ttc_col)) {
						$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalTTCShort'), '', 'C', 0, 1, $this->tableau['totalttc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
				}
			}
		}

		/**
		*	Show miscellaneous information (payment mode, payment term, ...)
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Commande	$object			Object to show
		*	@param		int			$posy			Y
		*	@param		Translate	$outputlangs	Langs object
		*	@return		int			$posy			Position pour suite
		**/
		protected function _tableau_info(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabinfo		= $posy + $this->ht_space_info;
			$tabinfo_hl			= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$larg_tabinfo		= $this->larg_tabinfo;
			$larg_col1info		= 46;
			$larg_col2info		= $larg_tabinfo - $larg_col1info;
			$posxtabinfo		= $this->marge_gauche;
			$posxcol2info		= $posxtabinfo + $larg_col1info;
			// VAT statements
			if (!empty($this->text_TVA_auto)) {
				$statements	= pdf_InfraSPlus_VAT_auto($object, $this->emetteur, $object->thirdparty, $this->arrayidcontact, $this->adrlivr, $this->hasService, $this->hasProduct, $this->show_tva_btp);
				if (is_array($statements)) {
					$pdf->SetFont('', '', $default_font_size - 2);
					if (!empty($statements['F'])) {
						$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['F'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
					}
					if (!empty($statements['S'])) {
						$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['S'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
					}
					if (!empty($statements['P'])) {
						$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['P'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
					}
					if (!empty($statements['B'])) {
						$posytabinfo	= pdf_InfraSPlus_write_VAT_mention($pdf, $object, $outputlangs, $statements['B'], $larg_tabinfo, $tabinfo_hl, $posxtabinfo, $posytabinfo);
					}
				}
			}
			// Show Qty of products and number of different product for the document
			if (!empty($this->show_qty_prod_tot) && $this->nbrProdTot > 0) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$nbrProd		= $outputlangs->transnoentities('PDFInfraSPlusQtyProd', $this->nbrProdTot, count($this->nbrProdDif));
				$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $nbrProd, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 2;
			}
			// Show total discount
			if (!empty($this->show_tot_disc) && empty($this->show_disc_tot) && !empty($this->TotRem['TTC'])) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('PDFInfraSPlusTotRem').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$total_ht		= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
				$total_ttc		= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
				$TotRem			= pdf_InfraSPlus_price($object, ($this->use_multicurrency ? $this->TotRem['multicurrency_TTC'] : $this->TotRem['TTC']), $outputlangs, 0, 0, 'T').' '.$outputlangs->transnoentities(($this->only_ht ? "HT" : "TTC"));
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $TotRem, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 2;
			}
			// Show shipping date
			$date_livraison	= $object->delivery_date;
			if (!empty($date_livraison)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('DateDeliveryPlanned').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$dlp			= dol_print_date($date_livraison, 'daytext', false, $outputlangs, true);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $dlp, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			} elseif (!empty($object->availability_code) || !empty($object->availability)) {	// Show availability conditions
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre				= $outputlangs->transnoentities('AvailabilityPeriod').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_availability	= $outputlangs->transnoentities('AvailabilityType'.$object->availability_code) != ('AvailabilityType'.$object->availability_code) ? $outputlangs->transnoentities('AvailabilityType'.$object->availability_code) : $outputlangs->convToOutputCharset(isset($object->availability) ? $object->availability : '');
				$lib_availability	= str_replace('\n', "\n", $lib_availability);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_availability, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo		= $pdf->GetY() + 1;
			}
			// Show shipping method
			if ($object->shipping_method_id > 0) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre					= $outputlangs->transnoentities('SendingMethod').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$shipping_code			= $outputlangs->getLabelFromKey($this->db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');	// Get code using getLabelFromKey
				$lib_shipping_method	= $outputlangs->trans("SendingMethod".strtoupper($shipping_code));
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_shipping_method, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo			= $pdf->GetY() + 1;
			}
			// Show payments conditions
			if ($object->cond_reglement_code || $object->cond_reglement) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre					= $outputlangs->transnoentities('PaymentConditions').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_condition_paiement	= $outputlangs->transnoentities('PaymentCondition'.$object->cond_reglement_code) != ('PaymentCondition'.$object->cond_reglement_code) ? $outputlangs->transnoentities('PaymentCondition'.$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
				$lib_condition_paiement	= str_replace('\n', "\n", $lib_condition_paiement);
				if ($object->deposit_percent > 0) {
					$lib_condition_paiement = str_replace('__DEPOSIT_PERCENT__', $object->deposit_percent, $lib_condition_paiement);
				}
				if (empty($this->show_paymenttermcond_2l)) {
					$pdf->writeHTMLCell($larg_col2info, $tabinfo_hl, $posxcol2info, $posytabinfo, pdf_InfraSPlus_formatNotes($object, $outputlangs, $lib_condition_paiement), 0, 1, false, true, 'L', true);
				} else {
					$posytabinfo	= $pdf->GetY();
					$pdf->writeHTMLCell($larg_col1info + $larg_col2info, $tabinfo_hl, $posxtabinfo, $posytabinfo, pdf_InfraSPlus_formatNotes($object, $outputlangs, $lib_condition_paiement), 0, 1, false, true, 'L', true);
				}
				$posytabinfo	= $pdf->GetY() + ($tabinfo_hl / 2);
			}
			// Show payment mode
			if (!empty($object->mode_reglement_code) && $object->mode_reglement_code != 'CHQ' && $object->mode_reglement_code != 'VIR') {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('PaymentMode').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_mode_reg	= $outputlangs->transnoentities('PaymentType'.$object->mode_reglement_code) != ('PaymentType'.$object->mode_reglement_code) ? $outputlangs->transnoentities('PaymentType'.$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_mode_reg, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}
			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
				// Si mode reglement non force ou si force a CHQ
				if (!empty($this->chq_num)) {
					$diffsizetitle	= (empty($this->diffsize_title) ? 3 : $this->diffsize_title);
					$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
					if ($this->chq_num > 0) {
						$account	= new Account($this->db);
						$account->fetch($this->chq_num);
						$owner			= $account->proprio;
						$owner_address	= $account->owner_address.', '.$account->owner_zip.' '.$account->owner_town;
					}
					if ($this->chq_num == -1) {
						$owner			= $this->emetteur->name;
						$owner_address	= $this->emetteur->getFullAddress();
					}
					if (empty($this->hidechq_address)) {
						$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $owner), '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					} else {
						$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$owner, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					}
					if (empty($this->hidechq_address)) {
						$posytabinfo	= $pdf->GetY() - 1;
						$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
						$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->convToOutputCharset($owner_address), '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					}
					$posytabinfo	= $pdf->GetY() + 1;
				}
			}
			// If payment mode not forced or forced to VIR, show payment with BAN
			if (!empty($this->show_vir) && (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR' || ($object->mode_reglement_code == 'CB' && $this->IBAN_with_CB) || $this->IBAN_All)) {
				if ($object->fk_account > 0 || $object->fk_bank > 0 || !empty($this->rib_num)) {
					$bankid						= ($object->fk_account <= 0 ? $this->rib_num : $object->fk_account);
					if ($object->fk_bank > 0) {
						$bankid	= $object->fk_bank;	// For backward compatibility when object->fk_account is forced with object->fk_bank
					}
					$account					= new Account($this->db);
					$account->fetch($bankid);
					$pdf->SetLineStyle($this->stdLineStyle);
					$posytabinfo				= pdf_infrasplus_bank($pdf, $outputlangs, $posxtabinfo, $posytabinfo, $larg_tabinfo, $tabinfo_hl - 1, $account, $this->bank_only_number, $default_font_size);
					$posytabinfo				+= 1;
				}
			}
			if (!empty($this->e_signing) && isModEnabled('uptosign')) {
				$this->posystamp	= $posytabinfo;
				$posytabinfo		+= 10;	// espace requis pour le scellement
			}
			if (!empty($calculseul)) {
				$heightforinfo	= $posytabinfo - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforinfo;
			} else {
				$pdf->commitTransaction();
				return $posytabinfo;
			}
		}

		/**
		*	Show total to pay
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Commande	$object			Object to show
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@param		int			$calculseul		no print => just to know the height
		*	@return		int							Position pour suite
		**/
		protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			global $conf;

			$pdf->startTransaction();
			$default_font_size				= pdf_getPDFFontSize($outputlangs);
			$posytabtot						= $posy + $this->ht_space_tot;
			$tabtot_hl						= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabtotal					= $this->larg_tabtotal;
			$larg_col2total					= !empty($this->show_ttc_col) && $this->num_totalttc > $this->num_totalht ? $this->larg_totalttc : $this->larg_totalht;
			$larg_col1total					= $larg_tabtotal - $larg_col2total;
			$posxtabtotal					= $this->posxtabtotal;
			$posxcol2total					= $this->posxtabtotal + $larg_col1total;
			$index							= 0;
			// Total HT
			$this->atleastoneratenotnull	= 0;
			if (empty($this->only_ttc)) {
				if (!empty($this->only_ht) || !empty($this->invert_bg_ht_ttc)) {
					$pdf->RoundedRect($posxtabtotal, $posytabtot, $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
				// Calculs HT
				if (empty($this->use_tva_forfait)) {	// calculate standard HT
					$total_ht	= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
				} else {	// calculate HT with Flat rate VAT (like Swiss)
					$total_ht	= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc / (1 + (abs($this->tva_forfait) / 100));
				}
				// Inclusion du total des remises HT dans la table
				if (!empty($this->show_disc_tot)) {
					$totRemHT	= $this->use_multicurrency ? $this->TotRem['multicurrency_HT'] : $this->TotRem['HT'];
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusRawTotalHTShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ht + (!empty($object->remise) ? $object->remise : 0) + $totRemHT, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotRemHT').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $totRemHT, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
				}
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalHTShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ht + (!empty($object->remise) ? $object->remise : 0), $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				if (!empty($this->invert_bg_ht_ttc)) {
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
			}
			// VAT and local taxes
			if (((empty($this->only_ht) && empty($this->only_ttc)) || !empty($this->show_ttc_vat_tot)) && empty($this->use_tva_forfait)) {
				// Show VAT by rates and total
				$tvaisnull	= !empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000']) ? true : false;
				if (!empty($this->hide_vat_ifnull) && !empty($tvaisnull)) {
					// Nothing to do
				} else {
					//Local tax 1 before VAT
					foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('1', '3', '5'))) {
							continue;
						}
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0) {	// On affiche pas taux 0
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
					//Local tax 2 before VAT
					foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('1', '3', '5'))) {
							continue;
						}
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0) {	// On affiche pas taux 0
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
					// VAT
					foreach ($this->tva_array as $tvakey => $tvaval) {
						if (($tvakey > 0 || count($this->tva_array) > 1) && !empty($tvaval['base'])) {	// On affiche pas taux 0 sauf si plusieurs taux de tva sont utilisés
							$this->atleastoneratenotnull++;
							$index++;
							$pdf->SetAlpha($this->alpha);
							$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
							$pdf->SetAlpha(1);
							$tvacompl	= '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey		= str_replace('*', '', $tvakey);
								$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
							}
							$totalvat	= $outputlangs->transcountrynoentities('TotalVAT', $this->emetteur->country_code).' ';
							if ($this->vat_label_code_or_rate == 'rateonly') {
								$totalvat	.= vatrate($tvaval['vatrate'], 1).$tvacompl;
							} elseif ($this->vat_label_code_or_rate == 'codeonly') {
								$totalvat	.= $tvaval['vatcode'].$tvacompl;
							} elseif ($this->vat_label_code_or_rate == 'labelonly') {
								$totalvat	.= $tvaval['label'].$tvacompl;
							} elseif ($this->vat_label_code_or_rate == 'ratecode') {
								$totalvat	.= vatrate($tvaval['vatrate'], 1).(!empty($tvaval['vatcode']) ? ' ('.$tvaval['vatcode'].')' : '').$tvacompl;
							} elseif ($this->vat_label_code_or_rate == 'ratelabel') {
								$totalvat	.= vatrate($tvaval['vatrate'], 1).(!empty($tvaval['label']) ? ' ('.dol_trunc($tvaval['label'], 15, 'right', 'UTF-8', 1).')' : '').$tvacompl;
							} elseif ($this->vat_label_code_or_rate == 'codelabel') {
								$totalvat	.= $tvaval['vatcode'].(!empty($tvaval['label']) ? ' ('.dol_trunc($tvaval['label'], 15, 'right', 'UTF-8', 1).')' : '').$tvacompl;
							} else {
								$totalvat	.= vatrate($tvaval['vatrate'], 1).(!empty($tvaval['vatcode']) ? ' ('.$tvaval['vatcode'].')' : '').(!empty($tvaval['label']) ? ' ('.dol_trunc($tvaval['label'], 15, 'right', 'UTF-8', 1).')' : '').$tvacompl;
							}
							if (count($this->tva_array) > 1) {
								$totalvat	.= ' ('.pdf_InfraSPlus_price($object, $tvaval['base'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T').')';
							}
							$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval['amount'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						}
					}
					//Local tax 1 after VAT
					foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('2', '4', '6'))) {
							continue;
						}
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0) {	// On affiche pas taux 0
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
					//Local tax 2 after VAT
					foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('2', '4', '6'))) {
							continue;
						}
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0) {	// On affiche pas taux 0
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $tvakey)) {
									$tvakey		= str_replace('*', '', $tvakey);
									$tvacompl	= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($tvakey), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
						}
					}
				}
				$index++;
			} elseif (!empty($this->use_tva_forfait)) {	// calculate TVA with Flat rate VAT (like Swiss)
				$index++;
				$pdf->SetAlpha($this->alpha);
				$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
				$pdf->SetAlpha(1);
				$totalvat	= $outputlangs->transcountrynoentities('TotalVAT', $this->emetteur->country_code).' ';
				$totalvat	.= vatrate(abs($this->tva_forfait), 1);
				$vatmnt		= $object->total_ttc -($object->total_ttc / (1 + (abs($this->tva_forfait) / 100)));
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $vatmnt, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$index++;
			}
			// Total TTC
			if (empty($this->only_ht)) {
				if (empty($this->invert_bg_ht_ttc)) {
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalTTCShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ttc	= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			// Inclusion du total TTC des remises dans la table
			if (!empty($this->show_disc_ttc)) {
				$index++;
				$totRemTTC	= $this->use_multicurrency ? $this->TotRem['multicurrency_TTC'] : $this->TotRem['TTC'];
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotRemTTC').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $totRemTTC, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (!empty($this->efPaySpec)) {
				$totalPaySpec	= 0;
				$listPaySpec	= pdf_InfraSPlus_SpecPayExtraField($object);
				foreach ($listPaySpec as $key => $paySpec) {
					if ($paySpec['value'] != 0) {
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities($paySpec['label']), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $paySpec['value'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$totalPaySpec	+= $paySpec['value'];
					}
				}
				if ($totalPaySpec > 0) {
					$index++;
					$toBePaid	= price2num((empty($this->only_ht) ? $total_ttc : $total_ht + (!empty($object->remise) ? $object->remise : 0)) - $totalPaySpec, 'MT');
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusRemainExpense'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $toBePaid, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
			}
			// ecoTaxes
			if (is_array($this->ecoTaxes) && count($this->ecoTaxes) > 0) {
				foreach ($this->ecoTaxes as $key => $ecoTaxe) {
					if (!empty($this->only_ht) && !empty($ecoTaxe['ht']) || !empty($ecoTaxe['ttc'])) {
						$valEcoTaxe	= price2num(!empty($this->only_ht) ? $ecoTaxe['ht'] : $ecoTaxe['ttc'], 'MT');
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotalEcoTaxe', (!empty($this->only_ht) ? $outputlangs->transnoentities('HT') : $outputlangs->transnoentities('TTC'))).(!empty($this->show_tot_Cur_Symb) ? ' ('.$object->multicurrency_code.')' : '').' '.$key, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $valEcoTaxe, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
			}
			if (!empty($this->use_multicurrency) && !empty($this->show_tot_local_cur)) {
				$index++;
				$txtLocCur		= $outputlangs->transnoentities((empty($this->only_ht) ? 'TotalTTCShort' : 'TotalHTShort')).(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : '');
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $txtLocCur, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_loc_cur	= (empty($this->only_ht) ? $object->total_ttc : $object->total_ht);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_loc_cur, $outputlangs, 1, 1, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetFont('', '', $default_font_size - 1);
			if (!empty($this->number_words)) {
				$index++;
				$savcurrency	= $conf->currency;
				$conf->currency	= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
				$total			= empty($this->only_ht) ? $total_ttc : $total_ht;
				$total_words	= $outputlangs->transnoentities('PDFInfraSPlusOrderArrete').' : '.$outputlangs->getLabelFromNumber($total, 1);
				$pdf->MultiCell($larg_tabtotal, $tabtot_hl, $total_words, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$conf->currency	= $savcurrency;
			}
			$posytabtot	= $pdf->GetY() + 1;
			if (!empty($calculseul)) {
				$heightfortot	= $posytabtot - $posy;
				$pdf->rollbackTransaction(true);
				return $heightfortot;
			} else {
				$pdf->commitTransaction();
				return $posytabtot;
			}
		}

		/**
		*	Show area for the customer to sign
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Commande	$object			Object to show
		*	@param		int			$posy		 	Position y in PDF
		*	@param		Translate	$outputlangs	Object langs for output
		*	@return		int							Position pour suite
		**/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0, $freetext = 0)
		{
			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$signarea_top		= $posy + 1;
			$posxsignarea		= $this->posxtabtotal;
			$larg_signarea		= $this->larg_tabtotal;
			$signarea_hl		= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ProposalCustomerSignature'));
			$signarea_hl		= $signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl;
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (!empty($freetext)) {
				$pdf->Line($posxsignarea, $posy, $this->page_largeur - $this->marge_droite, $posy, $this->horLineStyle);
			}
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('ProposalCustomerSignature'), '', 'L', 0, 1, $posxsignarea + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			// Signature PAD
			if (!empty($this->signvalue)) {
				pdf_InfraSPlus_Client_Sign($pdf, $this->signvalue, $larg_signarea, $this->ht_signarea, $posxsignarea, $signarea_top + $signarea_hl);
			}
			// Pas d'impression => calcul de position
			if (!empty($calculseul)) {
				$heightforarea	= ($signarea_top + $signarea_hl + $this->ht_signarea + 1) - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			} else {
				// Signature électronique
				if (!empty($this->e_signing)) {
					if (!isModEnabled('uptosign')) {
						$pdf->addEmptySignatureAppearance($posxsignarea, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
					} else {
						pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posxsignarea, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
					}
				}
				$pdf->commitTransaction();
				return $signarea_top + $signarea_hl + $this->ht_signarea + 1;
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
				$hauteurfoot	= $specialFoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
				return $hauteurfoot;
			}
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
