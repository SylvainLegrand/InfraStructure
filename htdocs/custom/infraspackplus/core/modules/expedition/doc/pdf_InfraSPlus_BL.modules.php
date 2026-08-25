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
	* 	\file		./infraspackplus/core/modules/expedition/doc/pdf_InfraSPlus_BL.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF expedition
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
	include_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF proposal InfraS
	************************************************/
	class pdf_InfraSPlus_BL extends ModelePdfExpedition
	{
		const MIN_GAP_BEFORE_FOOTER	= 0.75;	// Espace mini garanti entre le bas du dernier bloc (colonne infos/bank ou zone de signature) et la ligne de separation du pied de page (~2px a 96dpi)

		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $draft_watermark;
		public $use_doli_addr_livr;
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
		public $wvcc_no_hr;
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
		public $posxcol6;
		public $posxcol7;
		public $posxcol8;
		public $posxcol9;
		public $largcol1;
		public $largcol2;
		public $largcol3;
		public $largcol4;
		public $largcol5;
		public $largcol6;
		public $largcol7;
		public $largcol8;
		public $largcol9;
		public $tableau = [];	// Array of table to print
		public $larg_tabtotal;
		public $larg_tabinfo;
		public $posxtabtotal;
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $Sst;
		public $adrSst;
		public $dimC2D;
		public $exf_prod_pos;
		public $hBC;
		public $hide_ordered;
		public $hide_wv;
		public $label_efl;
		public $larg_efl;
		public $larg_ordered;
		public $larg_rel;
		public $larg_wv;
		public $num_efl;
		public $num_ordered;
		public $num_rel;
		public $num_wv;
		public $show_2sign_area;
		public $show_bc_col;
		public $show_efl;
		public $show_rel_col;
		public $showntusedascover;
		public $showpricebl;
		public $totaux;
		public $typeadr;
		public $wBC;

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
			$this->name							= $langs->trans('PDFInfraSPlusExpeditionName');
			$this->description					= $langs->trans('PDFInfraSPlusExpeditionDescription');
			$this->titlekey						= 'PDFInfraSPlusExpeditionTitle';
			$this->defaulttemplate				= getDolGlobalString('EXPEDITION_ADDON_PDF', '');
			$this->draft_watermark				= getDolGlobalString('SHIPPING_DRAFT_WATERMARK', '');
			$this->use_doli_addr_livr			= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 0);
			$this->show_bc_col					= getDolGlobalInt('INFRASPLUS_PDF_BL_WITH_BC_COLUMN', 0);
			$this->show_rel_col					= getDolGlobalInt('INFRASPLUS_PDF_BL_WITH_REL_COLUMN', 0);
			$this->show_efl						= getDolGlobalInt('INFRASPLUS_PDF_BL_WITH_POS_COLUMN', 0);
			$this->exf_prod_pos					= getDolGlobalString('INFRASPLUS_PDF_EXF_PROD_POS', '');
			$this->hide_wv						= getDolGlobalInt('SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME', 0);
			$this->hide_ordered					= getDolGlobalInt('INFRASPLUS_PDF_HIDE_ORDERED', 0);
			$this->larg_ref						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_REF', 28);
			$this->larg_efl						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_EFL', 14);
			$this->larg_wv						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_WV', 22);
			$this->larg_unit					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_UNIT', 10);
			$this->larg_ordered					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_ORDERED', 10);
			$this->larg_rel						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_REL', 10);
			$this->larg_qty						= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_QTY', 10);
			$this->larg_totalht					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_PRICE', 24);
			$this->num_ref						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_REF', 1);
			$this->num_efl						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_EFL', 2);
			$this->num_desc						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_DESC', 3);
			$this->num_wv						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_WV', 4);
			$this->num_unit						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_UNIT', 5);
			$this->num_ordered					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_ORDERED', 6);
			$this->num_rel						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_REL', 7);
			$this->num_qty						= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_QTY', 8);
			$this->num_totalht					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_PRICE', 9);
			$this->show_sign_area				= getDolGlobalInt('INFRASPLUS_PDF_EXPEDITION_SHOW_SIGNATURE', 0);
			$this->show_ExtraFieldsLines		= getDolGlobalInt('INFRASPLUS_PDF_EXFL_E', 0);
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
		*	@param		Expedition	$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return	int									1=OK, 0=KO
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
			if (!getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME')) {
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_BL') ? '' : '_BL';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_PREFIX_TO_BL', '');
				$filesufixe = empty($fileprefix) ? '_BL' : '';
			}
			$baseDir	= !empty($conf->expedition->multidir_output[$conf->entity]) ? $conf->expedition->multidir_output[$conf->entity] : $conf->expedition->dir_output;

			if (!empty($baseDir)) {
				if (!empty($this->show_ExtraFieldsLines)) {
					$extrafieldsline	= new ExtraFields($this->db);
					$extralabelsline	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
				}
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$this->show_ExtraFieldsLines	= '';
					$dir							= $baseDir.'/sending';
					$file							= $dir.'/SPECIMEN.pdf';
				} else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/sending/'.$objectref;
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
					$this->Sst					= !empty($hookmanager->resArray['Sst']) ? $hookmanager->resArray['Sst'] : '';
					$this->adrSst				= !empty($hookmanager->resArray['adrSst']) ? $hookmanager->resArray['adrSst'] : '';
					$this->listfreet			= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->files				= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					$this->showntusedascover	= !empty($hookmanager->resArray['showntusedascover']) ? $hookmanager->resArray['showntusedascover'] : '';
					$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : '';
					$this->with_picture			= !empty($hookmanager->resArray['hidepict']) ? $hookmanager->resArray['hidepict'] : '';
					$this->refcol				= !empty($hookmanager->resArray['refcol']) ? $hookmanager->resArray['refcol'] : '';
					$hidedesc					= !empty($hookmanager->resArray['hidedesc']) ? $hookmanager->resArray['hidedesc'] : '';
					$this->showpricebl			= !empty($hookmanager->resArray['showpricebl']) ? $hookmanager->resArray['showpricebl'] : '';
					$this->showwvccchk			= !empty($hookmanager->resArray['showwvccchk']) ? $hookmanager->resArray['showwvccchk'] : '';
					$this->signvalue			= !empty($hookmanager->resArray['signvalue']) ? $hookmanager->resArray['signvalue'] : '';
					$nblignes					= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->show_bc_col)) {
						$this->refcol	= 0;	// Comme on affiche une colonne 'Code barre' on désactive la colonne 'Référence' qu'elle remplace
					}
					if (!empty($this->refcol)) {
						$hideref		= 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
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
					$pdf->SetSubject($outputlangs->transnoentities('Shipment'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Shipment'));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
					$watermarkedPages		= array($pdf->getPage() => true);
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
					if (!empty($this->show_rel_col)) {
						$commande = new Commande($this->db);
						if ($object->origin == 'commande' && $object->origin_id > 0) {
							$commande->fetch($object->origin_id);
							$commande->loadExpeditions();
						}
						$object->commande = $commande;
					}
					$this->totaux			= ['asked' => 0, 'shipped' => 0, 'rel' => 0];
					$qty_rel				= [];
					$realpatharray			= [];
					$prod_pos				= [];
					$listObjBib				= [];
					$objproduct				= new Product($this->db);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$this->totaux['asked']		+= $object->lines[$i]->qty_asked;
						$this->totaux['shipped']	+= $object->lines[$i]->qty_shipped;
						$qtyexpeditions = 0;
						if (!empty($this->show_rel_col) && !empty($object->commande->expeditions[$object->lines[$i]->fk_elementdet])) {
							$qtyexpeditions = $object->commande->expeditions[$object->lines[$i]->fk_elementdet];
						}
						$qty_rel[$i]			= $object->lines[$i]->qty_asked - $qtyexpeditions;
						$this->totaux['rel']	+= $qty_rel[$i];
						$isProd					= !empty($object->lines[$i]->fk_product) ? $objproduct->fetch($object->lines[$i]->fk_product) : 0;
						if (!empty($this->show_efl) && $isProd > 0) {
							$extrafieldsprod	= new ExtraFields($this->db);
							$extralabelsprod	= $extrafieldsprod->fetch_name_optionals_label($objproduct->table_element);
							$objproduct->fetch_optionals($objproduct->rowid);
							foreach ($extralabelsprod as $key => $label) {
								if ($this->exf_prod_pos && substr($key, 0, strlen($this->exf_prod_pos)) !== $this->exf_prod_pos) {
									continue;
								}
								$options_key		= $objproduct->array_options['options_'.$key];
								$this->label_efl	= $label;
								$value				= $extrafieldsprod->showOutputField($key, $options_key, '', $objproduct->table_element);
							}
							$prod_pos[$i]	= $value ? dol_string_nohtmltag($value) : '';
						}
						// detect if there is at least one image to show
						if (!empty($this->with_picture) && $isProd > 0) {
							$realpatharray[$i]	= pdf_InfraSPlus_getLineProductImage($this->db, $objproduct, $object->lines[$i], $this->old_path_photo, $this->cat_hq_image, $this->only_one_picture, $listObjBib);
						} else {
							$realpatharray[$i]	= '';
						}
					}
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					$this->larg_ref			= !empty($this->show_bc_col) ? $this->wBC : (empty($this->refcol) ? 0 : $this->larg_ref);
					if (empty($this->show_efl)) {
						$this->larg_efl	= 0;
					}
					if($this->hide_wv) {
						$this->larg_wv	= 0;
					}
					if (empty($this->product_use_unit)) {
						$this->larg_unit	= 0;
					}
					if ($this->hide_ordered) {
						$this->larg_ordered	= 0;
					}
					if (! $this->show_rel_col) {
						$this->larg_rel	= 0;
					}
					if (! $this->showpricebl) {
						$this->larg_totalht	= 0;
					}
					$this->larg_desc	= $this->larg_util_cadre - ($this->larg_ref + $this->larg_efl + $this->larg_wv + $this->larg_unit + $this->larg_ordered + $this->larg_rel + $this->larg_qty + $this->larg_totalht); // Largeur variable suivant la place restante
					$this->tableau		= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
												'efl'		=> array('col' => $this->num_efl,		'larg' => $this->larg_efl,		'posx' => 0),
												'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
												'wv'		=> array('col' => $this->num_wv,		'larg' => $this->larg_wv,		'posx' => 0),
												'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
												'ordered'	=> array('col' => $this->num_ordered,	'larg' => $this->larg_ordered,	'posx' => 0),
												'rel'		=> array('col' => $this->num_rel,		'larg' => $this->larg_rel,		'posx' => 0),
												'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
												'totalht'	=> array('col' => $this->num_totalht,	'larg' => $this->larg_totalht,	'posx' => 0)
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
						} elseif ($ncol_array['col'] == 6) {
							$this->largcol6	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 7) {
							$this->largcol7	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 8) {
							$this->largcol8	= $ncol_array['larg'];
						} elseif ($ncol_array['col'] == 9) {
							$this->largcol9	= $ncol_array['larg'];
						}
					}
					$this->posxcol1	= $this->marge_gauche;
					$this->posxcol2	= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3	= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4	= $this->posxcol3	+ $this->largcol3;
					$this->posxcol5	= $this->posxcol4	+ $this->largcol4;
					$this->posxcol6	= $this->posxcol5	+ $this->largcol5;
					$this->posxcol7	= $this->posxcol6	+ $this->largcol6;
					$this->posxcol8	= $this->posxcol7	+ $this->largcol7;
					$this->posxcol9	= $this->posxcol8	+ $this->largcol8;
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
					$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head					= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead			= $head['totalhead'];
					$hauteurcadre			= $head['hauteurcadre'];
					$tab_top				= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
					$tab_top_newpage		= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$ht_colinfo				= $this->_tableau_info($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht_coltotal			= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht2_coltotal			= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 1, 1, $this->horLineStyle) : 0;
					$ht_coltotal			+= $ht2_coltotal;
					$ht_signareas			= $this->show_2sign_area ? ($ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal) : $ht_coltotal;
					if (!empty($this->show_sign_area)) {

						if ($ht2_coltotal > 3) {
							$ht_signareas	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 1);
						} else {
							$ht_signareas	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
						}
					}
					$heightforinfotot	= $this->show_2sign_area ? $ht_signareas : ($ht_colinfo > $ht_signareas ? $ht_colinfo : $ht_signareas);
					$heightforinfotot	+= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$heightforinfotot	+= self::MIN_GAP_BEFORE_FOOTER;
					$heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
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
					$tab_top	+= $height_incoterms;
					// Livraison
					$height_livr	= 0;
					$height_SsT		= 0;
					$larg_livrshow	= !empty($head['livrshow']) && !empty($head['SsTshow']) ? ($this->larg_util_txt / 2) - 2 : $this->larg_util_txt;
					$larg_SsTshow	= !empty($head['livrshow']) && !empty($head['SsTshow']) ? ($this->larg_util_txt / 2) - 2 : $this->larg_util_txt;
					$posx_SsTshow	= !empty($head['livrshow']) && !empty($head['SsTshow']) ? $this->posx_G_txt + $larg_livrshow + 4 : $this->posx_G_txt;
					if (!empty($head['livrshow'])) {
						$pdf->SetFont('', 'B', $default_font_size + 2);
						$pdf->writeHTMLCell($larg_livrshow, $this->tab_hl, $this->posx_G_txt, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusLivr')), 0, 1);
						$xlivr	= $this->posx_G_txt + $pdf->GetStringWidth($outputlangs->transnoentities('PDFInfraSPlusLivr'), '', 'B', $default_font_size + 2) + 5;
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
							$pdf->writeHTMLCell($larg_livrshow - $xlivr - 3, $this->tab_hl, $xlivr, $tab_top + 0.6, dol_htmlentitiesbr($adrlivrName), 0, 1);
							$nexY_livrshow	= $pdf->GetY();
						} else {
							$nexY_livrshow	= $tab_top + 0.6;
						}
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell($larg_livrshow - $xlivr - 3, $this->tab_hl, $xlivr, $nexY_livrshow, dol_htmlentitiesbr($head['livrshow']), 0, 1);
						$nexY_livrshow	= $pdf->GetY();
						$height_livr	= $this->Rounded_rect * 2 > $nexY_livrshow - $tab_top ? $this->Rounded_rect * 2 : $nexY_livrshow - $tab_top;
					}
					if (!empty($head['SsTshow'])) {
						$pdf->SetFont('', 'B', $default_font_size + 2);
						$pdf->writeHTMLCell($larg_SsTshow, $this->tab_hl, $posx_SsTshow, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusSsT')), 0, 1);
						$xSsT	= $posx_SsTshow + $pdf->GetStringWidth($outputlangs->transnoentities('PDFInfraSPlusSsT'), '', 'B', $default_font_size + 2) + 5;
						if ($this->adrSst->name != '') {
							$pdf->SetFont('', 'B', $default_font_size);
							$pdf->writeHTMLCell($larg_SsTshow - $xSsT - 3, $this->tab_hl, $xSsT, $tab_top + 0.6, dol_htmlentitiesbr($this->adrSst->name), 0, 1);
							$nexY_SsTshow	= $pdf->GetY();
						} else {
							$nexY_SsTshow	= $tab_top + 0.6;
						}
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell($larg_SsTshow - $xSsT - 3, $this->tab_hl, $xSsT, $nexY_SsTshow, dol_htmlentitiesbr($head['SsTshow']), 0, 1);
						$nexY_SsTshow	= $pdf->GetY();
						$height_SsT		= $this->Rounded_rect * 2 > $nexY_SsTshow - $tab_top ? $this->Rounded_rect * 2 : $nexY_SsTshow - $tab_top;
					}
					$height_adrs	= $height_livr > $height_SsT ? $height_livr : $height_SsT;
					if ($height_adrs) {
						if ($this->showtblline) {
							$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_adrs + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						}
						$height_adrs			+= $this->tab_hl;
					}
					$tab_top			+= $height_adrs;
					// Header informations after Address blocks
					$height_header_inf	= 0;
					if (!empty($this->header_after_addr)) {
						$tab_top	+= $this->space_headerafter;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						$txtC11		= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->transnoentities('RefSending').' : '.$outputlangs->convToOutputCharset($object->ref);
						if ($object->statut == 0) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11	= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						if ($object->date_delivery) {
							$txtC12		= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($object->date_delivery, 'dayhour', false, $outputlangs, true);
							$largC12	= $this->larg_util_txt - $largC11;
							$xC12		= $this->posx_G_txt + $this->larg_util_txt - $largC12;
							$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
							$pdf->MultiCell($largC12, $this->tab_hl, $txtC12, 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
							$pdf->SetFont('', '', $default_font_size - 1);
						}
						$nexY	= $tab_top + $this->tab_hl + 1;
						if ($object->ref_customer) {
							$txtC21		= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_customer);
							$largC21	= $pdf->GetStringWidth($txtC21, '', '', $default_font_size - 1) + 3;
							$pdf->MultiCell($largC21, $this->tab_hl, $txtC21, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						$txtC22	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt + $largC21, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$largC22	= $this->larg_util_txt - $largC21;
							$xC22		= $object->ref_customer ? $this->posx_G_txt + $this->larg_util_txt - $largC22 : $this->posx_G_txt;
							$pdf->MultiCell($largC22, $this->tab_hl, $txtC22, 0, ($object->ref_customer ? 'R' : 'L'), 0, 0, $xC22, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						if ($object->ref_customer || !empty($txtC22)) {
							$nexY	+= $this->tab_hl + 1;
						}
						$height_header_inf	= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf	+= $this->tab_hl;
					}
					$tab_top		+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, -1);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$isInfraSLine	= infraspackplus_isInfrastructureLine($object->lines[$i]) ? 1 : 0;
						$isInfraSTotal	= infraspackplus_isInfrastructureTotal($object->lines[$i]) ? 1 : 0;	// Sous-total infrastructure (qty 91..99)
						$colYOffset	= !empty($isInfraSTotal) ? 1.0 : 0;	// pdfAddTotal applique setCellPaddings T=1 au libellé du sous-total. Les MultiCell des colonnes voisines ne respectent pas ce padding (hauteur explicite + valign 'M'), d'où un décalage visuel de ~1mm. On compense en décalant manuellement le Y des MultiCell pour les sous-totaux infrastructure.
						$curY	= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table)) {
							$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						} else {
							$pdf->setTopMargin($tab_top_newpage);
						}
						$pdf->setPageOrientation('', 1, $heightforfooter);	// Edit the bottom margin of current page to set it.
						$pageposbefore				= $pdf->getPage();
						$showpricebeforepagebreak	= 1;
						$colPicture					= $this->tableau['ref']['larg'] > 0 && $this->picture_in_ref ? 'ref' : 'desc';
						$imglinesize				= !empty($this->with_picture) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i]) : [];	// Define size of image if we need it
						$ht_url						= 0;
						if (!empty($imglinesize['width']) && !empty($imglinesize['height']) && $this->linkpictureurl) {
							$txturl	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $this->linkpictureurl);
							$ht_url	= $pdf->getStringHeight($this->tableau[$colPicture]['larg'], $txturl);
						}
						// Hauteur de ligne
						$this->heightline	= $this->tab_hl;
						// Hauteur du code barre
						if (!empty($this->show_bc_col)) {
							$pdf->startTransaction();
							$BC					= pdf_InfraSPlus_writelineBC($pdf, $object, $i, $this->bodytxtcolor, $this->tableau['ref']['posx'], $curY + $colYOffset, $this->wBC, $this->hBC);
							$this->heightline	= $BC < 1 ? $this->tab_hl : ($BC == 2 ? $this->dimC2D : $this->hBC);
							$pdf->rollbackTransaction(true);
						}
						// Hauteur de la Reference
						if (!empty($this->refcol)) {
							$pdf->startTransaction();
							$startline			= $pdf->GetY();
							$ref				= pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails);
							$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
							$endline			= $pdf->GetY();
							$heightRef			= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
							// l'image existe et doit être dans la colonne réf. => hauteur URL + hauteur image + 1/2 ligne d'espacement SINON on réinitialise à 0
							$this->heightline	= !empty($this->picture_in_ref) && !empty($imglinesize['width']) && !empty($imglinesize['height']) ? $imglinesize['height'] + $ht_url + ($this->tab_hl / 2) : 0;
							// J'ai une hauteur de ligne, donc une image, et ça remplace la réf. => on n'ajoute rien SINON on ajoute la hauteur prise par la référence + 1 ligne d'espacement si j'ai une image
							$this->heightline	+= !empty($this->heightline) && !empty($this->picture_replace_ref) ? 0 : $heightRef + (!empty($this->heightline) && empty($this->picture_replace_ref) ? $this->tab_hl : 0);
							$pdf->rollbackTransaction(true);
						}
						// Photo of product line first
						if (!empty($imglinesize['width']) && !empty($imglinesize['height']) && empty($this->picture_under) && empty($this->picture_after)) {
							if (($curY + ($this->picture_in_ref ? $this->heightline : $imglinesize['height']) + $ht_url) > ($this->page_hauteur - ($heightforfooter))) {	// If photo too high, we moved completely on new page
								$pdf->AddPage('', '', true);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);
								$watermarkedPages[$pdf->getPage()] = true;
								$pdf->setPage($pageposbefore + 1);
								$curY						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								$showpricebeforepagebreak	= 0;
							}
							$PictureY	= $curY + ($this->picture_in_ref ? $heightRef + ($this->tab_hl / 2) : 0);
							$PictureY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau[$colPicture]['posx'], $PictureY, $this->tableau[$colPicture]['larg'], $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url);
							$curY		= ($this->picture_in_ref ? $curY : $PictureY) +	$this->picture_padding;
						} else if (!empty($this->show_bc_col)) {
							if (($curY + $this->heightline) > ($this->page_hauteur - ($heightforfooter))) {	// If barre code too high, we moved completely on new page
								$pdf->AddPage('', '', true);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);
								$watermarkedPages[$pdf->getPage()] = true;
								$pdf->setPage($pageposbefore + 1);
								$curY						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								$showpricebeforepagebreak	= 0;
							}
						}
						// Extra fields & custom informations
						$pdf->SetLineStyle($this->horLineStyle);
						$extraDet			= '';
						// Ajout du numéro de série, s'il existe...
						$serialEquip		= isModEnabled('equipement') ? pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, $i, 'expedition') : '';
						$extraDet			.= empty($serialEquip) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$serialEquip.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
						// extrafieldsline
						$extrafieldslines	= '';
						if (!empty($this->show_ExtraFieldsLines)) {
							$extrafieldslines	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor, $outputlangs);
						}
						$extraDet	.= empty($extrafieldslines) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$extrafieldslines.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
						// Custom values (weight, volume and code
						$WVCC		= '';
						if ($this->showwvccchk) {
							$WVCC	= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs, $this->emetteur);
						}
						$extraDet		.= empty($WVCC) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$WVCC.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
						// Description of product line
						$pageposdesc	= $pdf->getPage();
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, 0, $extraDet, null, $this->desc_full_line, 0, $this->with_picture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url, $this->picture_padding);
						$pageposafter	= $pdf->getPage();
						$posyafter		= $pdf->GetY();
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);
									$watermarkedPages[$pdf->getPage()] = true;
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								$showpricebeforepagebreak	= 0;
							}
						} elseif ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);
								$watermarkedPages[$pdf->getPage()] = true;
								$pdf->setPage($pageposafter + 1);
							}
						}
						$nexY	= $pdf->GetY();
						// Photo of product line after description
						if (!empty($this->with_picture) && !empty($this->picture_after)) {
							$pageposimg	= $pdf->getPage();
							if (($nexY + (!empty($imglinesize['width']) && !empty($imglinesize['height']) ? $imglinesize['height'] : $this->tab_hl)) > ($this->page_hauteur - ($heightforfooter + ($i == ($nblignes - 1) ? $heightforinfotot : 0)))) {	// If photo too high, we moved completely on new page
								$pdf->AddPage('', '', true);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);
								$watermarkedPages[$pdf->getPage()] = true;
								$pdf->setPage($pageposimg + 1);
								$nexY						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								$showpricebeforepagebreak	= 0;
							}
							$widthpicture	= $this->desc_full_line ? $this->larg_util_txt : $this->tableau['desc']['larg'];
							$nexY			= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau['desc']['posx'], $nexY, $widthpicture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl);
						}
						$pageposafter	= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							if ($curY > ($this->page_hauteur - $heightforfooter - $this->tab_hl)) {
								$pdf->setPage($pageposafter);
								$curY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
							} else {
								$pdf->setPage($pageposdesc);
							}
						}
						$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
						// Bar code or ref
						if (!empty($this->show_bc_col)) {
							pdf_InfraSPlus_writelineBC($pdf, $object, $i, $this->bodytxtcolor, $this->tableau['ref']['posx'], $curY + $colYOffset, $this->wBC, $this->hBC);
						}
						if (!empty($this->refcol)) {
							$pagepos	= $pdf->getPage();
							$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY + $colYOffset, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
							$pdf->setPage($pagepos);
						}
						// Position
						if (!empty($this->show_efl)) {
							$pdf->MultiCell($this->tableau['efl']['larg'], $this->heightline, $prod_pos[$i], '', 'C', 0, 1, $this->tableau['efl']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}
						// volume & weight
						if (empty($this->hide_wv)) {
							$weighttxt	= '';
							$voltxt		= '';
							if ($object->lines[$i]->fk_product_type == 0) {
								if ($object->lines[$i]->weight) {
									$weighttxt	= round($object->lines[$i]->weight * $object->lines[$i]->qty_shipped, 2).' '.measuringUnitString(0, 'weight', $object->lines[$i]->weight_units, 1);
								}
								if ($object->lines[$i]->volume) {
									$voltxt		= round($object->lines[$i]->volume * $object->lines[$i]->qty_shipped, 2).' '.measuringUnitString(0, 'volume', ($object->lines[$i]->volume_units ? $object->lines[$i]->volume_units : 0), 1);
								}
							}
							$pdf->writeHTMLCell($this->tableau['wv']['larg'], $this->heightline, $this->tableau['wv']['posx'], $curY, $weighttxt.(($weighttxt && $voltxt) ? '<br>' : '').$voltxt, 0, 0, false, true, 'C');
						}
						// Unit
						if (!empty($this->product_use_unit)) {
							$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
							$pdf->writeHTMLCell($this->tableau['unit']['larg'], $this->heightline, $this->tableau['unit']['posx'], $curY + $colYOffset, $unit, 0, 1, false, true, $this->force_align_left_unit, true);
						}
						$isSubFreeT	= isModEnabled('subtotal') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal') && $object->lines[$i]->qty	== 50 ? 1 : 0;	// Ligne libre ATM
						if (empty($isSubFreeT)) {
							// Qty ordered
							if (empty($this->hide_ordered)) {
								$pdf->MultiCell($this->tableau['ordered']['larg'], $this->heightline, $object->lines[$i]->qty_asked, '', 'C', 0, 1, $this->tableau['ordered']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Qty backorder
							if (!empty($this->show_rel_col)) {
								$pdf->MultiCell($this->tableau['rel']['larg'], $this->heightline, $qty_rel[$i], '', 'C', 0, 1, $this->tableau['rel']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Qty to ship
							$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $object->lines[$i]->qty_shipped, '', 'C', 0, 1, $this->tableau['qty']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							// Total HT
							if (!empty($this->showpricebl)) {
								$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'C', 0, 1, $this->tableau['totalht']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
						}
						// Add dash or space between line
						if (!empty($this->dash_between_line) && $i < ($nblignes - 1)) {
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
							}
							$watermarkedPages[$pagenb] = true;
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
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$heightforfooter				= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} else {
								$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							}
							// New page
							$pdf->AddPage();
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
							$watermarkedPages[$pdf->getPage()] = true;
							$pagenb++;
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
					$bottomlasttab	= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} else {
						$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					}
					$posyinfo		= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$posytot		= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$posyfreetext	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 1, 0, $this->horLineStyle) : $posytot;
					$posysignarea	= $this->show_2sign_area ? ($posyinfo > $posyfreetext ? $posyinfo : $posyfreetext) : $posyfreetext;
					if (!empty($this->show_sign_area)) {
						if ($ht2_coltotal > 3) {
							$posyendsignarea	= $this->_signature_area($pdf, $object, $posysignarea, $outputlangs, 0, 1);
						} else {
							$posyendsignarea	= $this->_signature_area($pdf, $object, $posysignarea, $outputlangs, 0, 0);
						}
						$posy					= $this->show_2sign_area ? $posyendsignarea : ($posyinfo > $posyendsignarea ? $posyinfo : $posyendsignarea);
					} else {
						$posy	= $posysignarea;
					}
					$posy	= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posy;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					// if merge files is active
					if (!empty($this->files)) {
						pdf_InfraSPlus_files($pdf, $this->files, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
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
		*	@param		Expedition	$object			Object to show
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
										'E' => $object->getIdContact('external', (empty($this->use_doli_addr_livr) ? 'SHIPPING' : 'CUSTOMER')),
										'L' => (!empty($this->use_doli_addr_livr) ? $object->getIdContact('external', 'SHIPPING') : '')
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
		*	@param		Expedition	$object			Object to show
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
		*	@param		Expedition	$object			Object to show
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
			$hidebottom	= 0;
			if (!empty($hidetop)) {
				$hidetop	= -1;
			}
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
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
			if ($object->statut == Expedition::STATUS_DRAFT && (!empty($this->draft_watermark))) {
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
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol6, $tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol7 > $this->posxcol6 && $this->posxcol7 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol7, $tab_top, $this->posxcol7,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol8 > $this->posxcol7 && $this->posxcol8 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol8, $tab_top, $this->posxcol8,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol9 > $this->posxcol8 && $this->posxcol9 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol9, $tab_top, $this->posxcol9,	$tab_top + $tab_height, $this->verLineStyle);
				}
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				if (!empty($this->show_bc_col)) {
					$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusCB'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				if (!empty($this->show_efl)) {
					$pdf->MultiCell($this->tableau['efl']['larg'], $this->ht_top_table, $outputlangs->transnoentities($this->label_efl), '', 'C', 0, 1, $this->tableau['efl']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				if (!empty($this->refcol)) {
					$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Designation'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (empty($this->hide_wv)) {
					$pdf->MultiCell($this->tableau['wv']['larg'], $this->ht_top_table, $outputlangs->transnoentities('WeightVolShort'), '', 'C', 0, 1, $this->tableau['wv']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				if (!empty($this->product_use_unit)) {
					$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Unit'), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				if (empty($this->hide_ordered)) {
					$pdf->MultiCell($this->tableau['ordered']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ordered'), '', 'C', 0, 1, $this->tableau['ordered']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				if (!empty($this->show_rel_col)) {
					$pdf->MultiCell($this->tableau['rel']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusExpeditionbackorder'), '', 'C', 0, 1, $this->tableau['rel']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				}
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('QtyShippedShort'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
				if ($this->showpricebl) {
					$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities('TotalHTShort'), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
			}
		}

		/**
		*	Show miscellaneous information (payment mode, payment term, ...)
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Expedition	$object			Object to show
		*	@param		int			$posy			Y
		*	@param		Translate	$outputlangs	Langs object
		*	@return		int			$posy			Position pour suite
		**/
		protected function _tableau_info(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			$pdf->startTransaction();
			// cf. _tableau_info() de pdf_InfraSPlus_D.modules.php : un sous-total infrastructure en derniere ligne du document laisse le padding
			// haut/bas des cellules a 1mm, ce qui gonflerait la hauteur reellement dessinee par ce bloc et le ferait deborder sur le pied de page.
			$savedCellPaddings	= $pdf->getCellPaddings();
			$pdf->setCellPaddings($savedCellPaddings['L'], 0, $savedCellPaddings['R'], 0);
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabinfo		= $posy + $this->ht_space_info;
			$tabinfo_hl			= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$larg_tabinfo		= $this->larg_tabinfo;
			$larg_col1info		= 51;
			$larg_col2info		= $larg_tabinfo - $larg_col1info;
			$posxtabinfo		= $this->marge_gauche;
			$posxcol2info		= $posxtabinfo + $larg_col1info;
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$labelShipped		= $outputlangs->transnoentities('PDFInfraSPlusExpeditionTotalShipped').' : ';
			$pdf->MultiCell($larg_col1info, $tabinfo_hl, $labelShipped, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->MultiCell($larg_col2info, $tabinfo_hl, $this->totaux['shipped'], '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
			$posytabinfo		= $pdf->GetY() + 1;
			$pdf->SetFont('', 'B', $default_font_size - 2);
			if (empty($this->hide_ordered)) {
				$labelShipped	= $outputlangs->transnoentities('PDFInfraSPlusExpeditionTotalAsked').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $labelShipped, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $this->totaux['asked'], '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}
			if (!empty($this->show_efl)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$labelShipped	= $outputlangs->transnoentities('PDFInfraSPlusExpeditionTotalRel').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $labelShipped, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $this->totaux['rel'], '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}
			if ($object->shipping_method_id > 0) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('SendingMethod').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$label			= '';
				$code			= $outputlangs->getLabelFromKey($this->db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');	// Get code using getLabelFromKey
				$label			.= $outputlangs->trans('SendingMethod'.strtoupper($code));
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $label, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}
			if (!empty($object->tracking_number)) {
				$object->GetUrlTrackingStatus($object->tracking_number);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('TrackingNumber').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $object->tracking_number, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
				if (!empty($object->tracking_url) && $object->tracking_url != $object->tracking_number) {
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$titre			= $outputlangs->transnoentities('LinkToTrackYourPackage').' : ';
					$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 2);
					$pdf->writeHTMLCell($larg_col2info, $tabinfo_hl, $posxcol2info, $posytabinfo, $object->tracking_url, 0, 1, false, true, 'L');
					$posytabinfo	= $pdf->GetY() + 1;
				}
			}
			if (!empty($calculseul)) {
				$heightforinfo	= $posytabinfo - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforinfo;
			} else {
				$pdf->commitTransaction();
				$pdf->setCellPaddings($savedCellPaddings['L'], $savedCellPaddings['T'], $savedCellPaddings['R'], $savedCellPaddings['B']);
				return $posytabinfo;
			}
		}

		/**
		*	Show total to pay
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Expedition	$object			Object to show
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@return		int							Position pour suite
		**/
		protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			$pdf->startTransaction();
			// cf. _tableau_info() de pdf_InfraSPlus_D.modules.php : un sous-total infrastructure en derniere ligne du document laisse le padding
			// haut/bas des cellules a 1mm, ce qui gonflerait la hauteur reellement dessinee par ce bloc et le ferait deborder sur le pied de page.
			$savedCellPaddings	= $pdf->getCellPaddings();
			$pdf->setCellPaddings($savedCellPaddings['L'], 0, $savedCellPaddings['R'], 0);
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabtot			= $posy + $this->ht_space_tot;
			$tabtot_hl			= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabtotal		= $this->larg_tabtotal;
			$larg_col2total		= $this->tableau['wv']['larg'];
			$larg_col1total		= $larg_tabtotal - $larg_col2total;
			$posxtabtotal		= $this->posxtabtotal;
			$posxcol2total		= $this->tableau['wv']['posx'];
			$index				= 0;
			$totalWeighttoshow	= '';
			$totalVolumetoshow	= '';
			// Load dim data
			$tmparray			= $object->getTotalWeightVolume();
			$totalWeight		= round($tmparray['weight'], 2);
			$totalVolume		= $tmparray['volume'];
			$totalOrdered		= $tmparray['ordered'];
			$totalToShip		= $tmparray['toship'];
			// Set true Volume and volume_units not currently stored into database
			if ($object->trueWidth && $object->trueHeight && $object->trueDepth) {
				$object->trueVolume		= price(($object->trueWidth * $object->trueHeight * $object->trueDepth), 0, $outputlangs, 0, 0);
				$object->volume_units	= $object->size_units * 3;
			}
			if ($totalWeight != '') {
				$totalWeighttoshow	= showDimensionInBestUnit($totalWeight, 0, "weight", $outputlangs);
			}
			if ($totalVolume != '') {
				$totalVolumetoshow	= showDimensionInBestUnit($totalVolume, 0, "volume", $outputlangs);
			}
			if ($object->trueWeight) {
				$totalWeighttoshow	= showDimensionInBestUnit($object->trueWeight, $object->weight_units, "weight", $outputlangs);
			}
			if ($object->trueVolume) {
				$totalVolumetoshow	= showDimensionInBestUnit($object->trueVolume, $object->volume_units, "volume", $outputlangs);
			}
			// Totaux
			if (empty($this->hide_wv)) {
				$pdf->RoundedRect($posxtabtotal, $posytabtot, $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				if ($this->tableau['wv']['col'] > 1) {
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities(empty($this->hide_wv) ? 'Total' : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
				} else {
					$totalWeighttoshow .= $totalWeighttoshow ? ' (Total)' : '';
				}
				// Total weight
				if ($totalWeighttoshow) {
					$pdf->MultiCell($larg_col2total, $tabtot_hl, $totalWeighttoshow, '', 'C', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
				}
				// Total volume
				if ($totalVolumetoshow) {
					if ($index > 0) {
						$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					}
					$pdf->MultiCell($larg_col2total, $tabtot_hl, $totalVolumetoshow, '', 'C', 0, 1, $posxcol2total, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
				}
			} else {
				$pdf->MultiCell($larg_col1total, $tabtot_hl, ' ', '', 'L', 0, 1, $posxtabtotal, $posytabtot + ($tabtot_hl * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$posytabtot	= $pdf->GetY() + 1;
			if (!empty($calculseul)) {
				$heightfortot	= $posytabtot - $posy;
				$pdf->rollbackTransaction(true);
				return $heightfortot;
			} else {
				$pdf->commitTransaction();
				$pdf->setCellPaddings($savedCellPaddings['L'], $savedCellPaddings['T'], $savedCellPaddings['R'], $savedCellPaddings['B']);
				return $posytabtot;
			}
		}

		/**
		*	Show area for the customer to sign
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Expedition	$object			Object to show
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@param		int			$calculseul		no print => just to know the height
		*	@return		int							Position pour suite
		**/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0, $freetext = 0)
		{
			$pdf->startTransaction();
			// cf. _tableau_info() de pdf_InfraSPlus_D.modules.php : un sous-total infrastructure en derniere ligne du document laisse le padding
			// haut/bas des cellules a 1mm, ce qui gonflerait la hauteur reellement dessinee par ce bloc et le ferait deborder sur le pied de page.
			$savedCellPaddings	= $pdf->getCellPaddings();
			$pdf->setCellPaddings($savedCellPaddings['L'], 0, $savedCellPaddings['R'], 0);
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$signarea_top		= $posy + 1;
			$posxsignarea1		= $this->marge_gauche;
			$posxsignarea2		= $this->posxtabtotal;
			$larg_signarea		= $this->larg_tabtotal;
			$signarea_hl		= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $object->thirdparty->name));
			$signarea_hl2		= $this->show_2sign_area ? $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $this->adrSst->name)) : 0;
			$signarea_hl		= $signarea_hl < $signarea_hl2 ? ($signarea_hl2 < $this->tab_hl ? $this->tab_hl : $signarea_hl2) : ($signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (!empty($freetext)) {
				$pdf->Line(($this->show_2sign_area ? $posxsignarea1 : $posxsignarea2), $posy, $this->page_largeur - $this->marge_droite, $posy, $this->horLineStyle);
			}
			if (!empty($this->show_2sign_area)) {
				$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('ContactNameAndSignature', $this->adrSst->name), '', 'L', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
				$pdf->RoundedRect($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			}
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('ContactNameAndSignature', $object->thirdparty->name), '', 'L', 0, 1, $posxsignarea2 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			if (!empty($this->signvalue)) {
				pdf_InfraSPlus_Client_Sign($pdf, $this->signvalue, $larg_signarea, $this->ht_signarea, $posxsignarea2, $signarea_top + $signarea_hl);
			}
			if (!empty($calculseul)) {
				$heightforarea	= ($signarea_top + $signarea_hl + $this->ht_signarea + 1) - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			} else {
				$pdf->commitTransaction();
				$pdf->setCellPaddings($savedCellPaddings['L'], $savedCellPaddings['T'], $savedCellPaddings['R'], $savedCellPaddings['B']);
				return $signarea_top + $signarea_hl + $this->ht_signarea + 1;
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Expedition	$object			Object to show
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
