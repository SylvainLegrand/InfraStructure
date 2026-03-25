<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/modules/product/doc/pdf_InfraSPlus_P.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF product card
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF product card InfraS
	************************************************/
	class pdf_InfraSPlus_P extends ModelePDFProduct
	{
		public $db;
		public $name;
		public $description;
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
		public $listfreet;
		public $listnotep;
		public $pied;
		public $with_picture;
		public $stdLineW = 0.2; // Default line width in TCPDF = 0.2
		public $stdLineDash = '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
		public $stdLineCap = 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $stdLineColor = array(0, 0, 0);
		public $stdLineStyle = array();
		public $bgLineW = 0.2;	// Default line width in TCPDF = 0.2
		public $bgLineDash = '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
		public $bgLineCap = 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $bgLineColor = array(0, 0, 0);
		public $bgLineStyle = array();
		public $tblLineCap = 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $tblLineStyle = array();
		public $horLineStyle = array();
		public $only_ht;
		public $larg_util_cadre;
		public $larg_util_txt;
		public $posx_G_txt;
		public $tableau = array();	// Array of table to print
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $frmeBgColor;
		public $frmeLineCap;
		public $frmeLineColor;
		public $frmeLineDash;
		public $frmeLineStyle;
		public $frmeLineW;
		public $hauteurhead;
		public $hpicture;
		public $id;
		public $show_adr;
		public $show_emet_details;
		public $wpicture;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'dict', 'products', 'companies', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusProductName');
			$this->description					= $langs->trans('PDFInfraSPlusProductDescription');
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->defaulttemplate				= getDolGlobalString('PRODUCT_ADDON_PDF', '');
			$this->frmeLineW					= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_LINE_WIDTH', 0.2);
			$this->frmeLineDash					= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_LINE_DASH', 0);
			$this->frmeLineColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_COLOR', '');
			$this->frmeLineColor				= explode(',', $this->frmeLineColor);
			$this->frmeBgColor					= getDolGlobalString('INFRASPLUS_PDF_FRM_E_BG_COLOR', '');
			$this->frmeBgColor					= explode(',', $this->frmeBgColor);
			$this->show_adr						= getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADR_PROD', 0);
			$this->with_picture					= getDolGlobalInt('INFRASPLUS_PDF_WITH_PICTURE', 0);
			$this->wpicture						= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_WIDTH', 20);
			$this->hpicture						= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_HEIGHT', 32);
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 0;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 0;	// Display payment mode
			$this->option_condreg				= 0;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 0;	// Displays if there has been a discount
			$this->option_credit_note			= 0;	// Support credit notes
			$this->option_freetext				= 1;	// Support add of a personalised text
			$this->option_draft_watermark		= 0;	// Support add of a watermark on drafts
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		Product		$object				Object source to build document
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
			$filesufixe						= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_P') ? '' : '-P';
			$baseDir						= !empty($conf->product->multidir_output[$conf->entity]) ? $conf->product->multidir_output[$conf->entity] : $conf->product->dir_output;

			if (!empty($baseDir)) {
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				} else {
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/'.$objectref;
					$file		= $dir.'/'.$objectref.$filesufixe.'.pdf';
				}
				$productFournisseur		= new ProductFournisseur($this->db);
				$supplierprices			= $productFournisseur->list_product_fournisseur_price($object->id);
				$object->supplierprices	= $supplierprices;
				if (! file_exists($dir)) {
					if (dol_mkdir($dir) < 0) {
						$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
						return -1;
					}
				}
				if (file_exists($dir)) {
					if (! is_object($hookmanager))	// Add pdfgeneration hook
					{
						include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
						$hookmanager	= new HookManager($this->db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters				= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook				= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$this->logo				= !empty($hookmanager->resArray['logo']) ? $hookmanager->resArray['logo'] : '';
					$this->listfreet		= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep		= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied				= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$hookmanager->resArray	= array();
					// Create pdf instance
					$pdf					= pdf_InfraSPlus_getInstance($this->format);
					$default_font_size		= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
					$pdf->SetAutoPageBreak(1, 0);
					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}
					$pdf->SetFont($this->font);
					// reduce the top margin before ol / il tag
					$tagvs	= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities("Product"));
					$pdf->SetCreator("Dolibarr ".DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Product")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
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
					$this->frmeLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->frmeLineStyle	= array('width'=>$this->frmeLineW, 'dash'=>$this->frmeLineDash, 'cap'=>$this->frmeLineCap, 'color'=>$this->frmeLineColor);
					$this->tblLineCap		= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
					$this->tblLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
					$this->horLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
					// Calculs de positions
					$this->tab_hl			= 4;
					$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head					= $this->_pagehead($pdf, $object, $this->show_adr, $outputlangs);
					$this->hauteurhead			= $head["totalhead"];
					$hauteurcadre			= $head["hauteurcadre"];
					$tab_top				= $this->hauteurhead + 5;
					$tab_top_newpage		= (empty($this->small_head2) ? $this->hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforinfotot		= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle);
					$heightforfooter		= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					// Affiche notes
					$height_note			= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, -1);
					$tab_top				+= $height_note;
					$curY					= $tab_top + $this->ht_top_table + $this->bgLineW + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					if (!empty($this->with_picture)) {
						include_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';
						include_once DOL_DOCUMENT_ROOT .'/core/lib/images.lib.php';

						$sortfield		= 'position_name';
						$sortorder		= 'asc';
						$posxpicture	= $this->posx_G_txt;
						$posypicture	= $curY + 0.5;
						if (!empty($this->old_path_photo)) {
							$pdir = get_exdir($this->id,2,0,0,$object,'product') . $this->id ."/photos/";
						} else {
							$pdir = get_exdir(0, 0, 0, 0, $object, 'product').dol_sanitizeFileName($object->ref).'/';
						}
						$dir = $baseDir.'/'.$pdir;
						// Defined relative dir to DOL_DATA_ROOT
						$relativedir						= '';
						if ($dir) {
							$relativedir	= preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $dir);
							$relativedir	= preg_replace('/^[\\/]/','',$relativedir);
							$relativedir	= preg_replace('/[\\/]$/','',$relativedir);
						}
						$filearray				= dol_dir_list($dir, 'files', 0, '(.jpg|.jpeg|.png)$', null, $sortfield, (strtolower($sortorder)=='desc' ? SORT_DESC : SORT_ASC), 1);
						$filearrayindatabase	= dol_dir_list_in_database($relativedir, '', null, 'name', SORT_ASC);
						// Complete filearray with properties found into $filearrayindatabase
						foreach ($filearray as $key => $val) {
							// Search if it exists into $filearrayindatabase
							foreach ($filearrayindatabase as $key2 => $val2) {
								if ($filearrayindatabase[$key2]['name'] == $filearray[$key]['name']) {
									$filearray[$key]['position_name']	= ($filearrayindatabase[$key2]['position'] ? $filearrayindatabase[$key2]['position'] : '0').'_'.$filearrayindatabase[$key2]['name'];
									$filearray[$key]['position']		= $filearrayindatabase[$key2]['position'];
									$filearray[$key]['cover']			= $filearrayindatabase[$key2]['cover'];
									$filearray[$key]['acl']				= $filearrayindatabase[$key2]['acl'];
									$filearray[$key]['rowid']			= $filearrayindatabase[$key2]['rowid'];
									$filearray[$key]['label']			= $filearrayindatabase[$key2]['label'];
									break;
								}
							}
						}
						if (count($filearray)) {

							if ($sortfield && $sortorder) {
								$filearray	= dol_sort_array($filearray, $sortfield, $sortorder);
							}
							$this->wpicture					= ($this->larg_util_txt - ((count($filearray) - 1) * 5)) / count($filearray);	// corrige la largeur maximal des images pour être au plus égale à la largeur disponible / nombre de vignette à afficher
							$imglinesize					= array();
							foreach ($filearray as $key => $val) {
								$photo	= $val['name'];
								if (image_format_supported($photo) >= 0) {
									if (empty($this->cat_hq_image))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
									{
										$vignette					= $dir.'thumbs/'.basename(getImageFileNameForSize($dir.$photo, '_small'));
										if (dol_is_file($vignette)) {
											$realpath	= $vignette;
										} else {
											$realpath	= $dir.$photo;
										}
									} else {
										$realpath	= $dir.$photo;
									}
									if ($realpath) {
										$imglinesize	= pdf_InfraSPlus_getSizeForImage($realpath, $this->wpicture, $this->hpicture);
									}
									if (!empty($imglinesize['width']) && !empty($imglinesize['height'])) {
										$pdf->Image($realpath, $posxpicture, $posypicture, $imglinesize['width'], $imglinesize['height']);	// Use 300 dpi
										$posxpicture	+= $imglinesize['width'] + 5;	// $pdf->Image does not increase value return by getX, so we save it manually
										$posypictures	= $posypictures < ($posypicture + $imglinesize['height']) ? $posypicture + $imglinesize['height'] : $posypictures; // Recording of the highest height value
									}
								}
							}
							$curY	= ($posypictures ? $posypictures : $posypicture) + $this->tab_hl;	// $pdf->Image does not increase value return by getY, so we save it manually
						}
					}
					$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, dol_htmlentitiesbr($object->description), 0, 1, 0);
					$curY	= $pdf->GetY() + $this->tab_hl;
					if ($object->type != Product::TYPE_SERVICE) {
						$pdf->writeHTMLCell(0, 0, $this->posx_G_txt, $curY, $outputlangs->trans("Nature").' : '.$object->getLibFinished(), 0, 1);
						$curY	= $pdf->GetY() + $this->tab_hl;
					}
					if ($object->url) {
						$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, $outputlangs->trans("PublicUrl").' : '.$object->url, 0, 1);
						$curY		= $pdf->GetY();
					}
					$txtDim	=	pdf_InfraSPlus_getlinewvdcc($object, 'P', $outputlangs);
					$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, $txtDim, 0, 1);
					$curY	= $pdf->GetY();
					$curY	+= $this->tab_hl;
					if (!empty($this->product_use_unit)) {
						$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, $outputlangs->trans("DefaultUnitToShow").' : '.$outputlangs->trans($object->getLabelOfUnit()), 0, 1);
						$curY	= $pdf->GetY();
					}
					if ($object->barcode) {
						$pdf->startTransaction();
						$BC		= pdf_InfraSPlus_writelineBC($pdf, $object, -1, $this->bodytxtcolor, $this->posx_G_txt, $curY, 45, 50);
						$hBC	= $BC < 1 ? 0 : ($BC == 2 ? 40 : 20);
						$pdf->rollbackTransaction(true);
						pdf_InfraSPlus_writelineBC($pdf, $object, -1, $this->bodytxtcolor, $this->posx_G_txt, $curY, 45, $hBC);
					}
					$bottomlasttab	= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					$this->_tableau($pdf, $object, $tab_top, $height_note, $bottomlasttab - $tab_top, $outputlangs, 0, 0, $pagenb);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $bottomlasttab, $outputlangs, $this->emetteur, $this->listfreet, 0, 0);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters		= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook		= $hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);
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
					$this->error=$outputlangs->trans('ErrorCanNotCreateDir',$dir);
					return 0;
				}
			} else {
				$this->error=$outputlangs->trans("ErrorConstantNotDefined","PRODUCT_OUTPUTDIR");
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		TCPDF		$pdf				Object PDF
		*	@param		Product		$object				Object to show
		*	@param		int			$showaddress		0=no, 1=yes
		*	@param		Translate	$outputlangs		Object lang for output
		*	@param		string		$titlekey			Translation key to show as title of document
		*	@return		array		$this->hauteurhead	'totalhead'		= height of header
		*												'hauteurcadre	= height of frame
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "") {
			if ($object->type == 1) {
				$titlekey	= 'ServiceSheet';
			} else {
				$titlekey	= 'ProductSheet';
			}
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$largeurcadreS	= 92;
			$w				= 100;
			$posy			= $this->marge_haute;
			$posx			= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo		= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $object->entity);
			$heightLogo		+= $posy + $this->tab_hl;
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$title			= $outputlangs->transnoentities($titlekey);
			$pdf->MultiCell($w, $this->tab_hl * $this->title_size, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B',$default_font_size);
			$posy			= $pdf->getY();
			$txtref			= $outputlangs->transnoentities('Ref')." : ".$outputlangs->convToOutputCharset($object->ref);
			$pdf->MultiCell($w, $this->tab_hl, $txtref, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 1);
			$posy			+= $this->tab_hl + 1;
			// Show list of linked objects
			$posy			+= 1;
			$posy			= pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $this->tab_hl, 'R', $default_font_size);
			$posy			+= 1;
			$posycadre		= $heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl;
			if (!empty($showaddress)) {
				// Sender properties
				$carac_emetteur	= '';
				$recepdetails	= $this->show_emet_details ? 'source' : 'sourcewithnodetails';
				$carac_emetteur .= pdf_InfraSPlus_build_address($outputlangs, $this->emetteur, $this->emetteur, '', '', 0, $recepdetails, $object, 1);
				$posxcadreS	= $this->marge_gauche;
				//Calcul hauteur des cadres
				$pdf->startTransaction();
				// Show sender
				$posy		= $posycadre;
				// Show sender name
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($largeurcadreS - 4, $this->tab_hl, $outputlangs->convToOutputCharset($this->emetteur->name), '', 'L', 0, 1, $posxcadreS + 2, $posy + 1, true, 0, 0, false, 0, 'M', false);
				$posy	= $pdf->getY();
				// Show sender information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell($largeurcadreS - 4, $this->tab_hl, $carac_emetteur, '', 'L', 0, 1, $posxcadreS + 2, $posy, true, 0, 0, false, 0, 'M', false);
				$posyendsender	= $pdf->getY();
				//Calcul hauteur des cadres
				$hauteurcadre	= ($posyendsender - $posycadre) + 1;
				$pdf->rollbackTransaction(true);
				// writting
				$pdf->RoundedRect($posxcadreS, $posycadre, $largeurcadreS, $hauteurcadre, $this->Rounded_rect, '1111', 'DF', $this->frmeLineStyle, $this->frmeBgColor);
				// Show sender
				$posy		= $posycadre;
				// Show sender name
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($largeurcadreS - 4, $this->tab_hl, $outputlangs->convToOutputCharset($this->emetteur->name), '', 'L', 0, 1, $posxcadreS + 2, $posy + 1, true, 0, 0, false, 0, 'M', false);
				$posy	= $pdf->getY();
				// Show sender information
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell($largeurcadreS - 4, $this->tab_hl, $carac_emetteur, '', 'L', 0, 1, $posxcadreS + 2, $posy, true, 0, 0, false, 0, 'M', false);
				$posyendsender	= $pdf->getY();
				}
			$this->hauteurhead = array('totalhead'=>$posycadre + $hauteurcadre, 'hauteurcadre'=>$hauteurcadre);
			return $this->hauteurhead;
		}
		/**
		*	Show table for lines
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Product		$object			Object to show
		*	@param		float		$tab_top		Top position of table

		*	@param		float		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@return		void
		**/
		protected function _tableau(&$pdf, $object, $tab_top, $height_note, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb) {
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
				if (!empty($this->title_bg)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				} else if ($this->showtblline) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
				if ($this->showtblline) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $height_note + ($height_note > 0 ? ($this->bgLineW * 2) + 2 : $this->bgLineW), $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $height_note + ($height_note > 0 ? ($this->bgLineW * 2) + 2 : $this->bgLineW)), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
			}
			else
				if ($this->showtblline) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top + $height_note, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
			// Show Folder mark
			if (!empty($this->fold_mark)) {
				$pdf->Line(0, ($this->page_hauteur)/3, $this->fold_mark, ($this->page_hauteur)/3, $this->stdLineStyle);
				$pdf->Line($this->page_largeur - $this->fold_mark, ($this->page_hauteur)/3, $this->page_largeur, ($this->page_hauteur)/3, $this->stdLineStyle);
			}
			// Colonnes
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->writeHTMLCell($this->larg_util_cadre, 4, $this->marge_gauche, $tab_top + (($this->ht_top_table - 4) / 2), dol_htmlentitiesbr($object->label), 0, 1, 0, 1, 'C');
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Product		$object			Object to show
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
				$hauteurfoot	= $specialFoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 1, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
				return $hauteurfoot;
			}
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 1, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
