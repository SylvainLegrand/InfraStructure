<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/modules/propale/doc/pdf_InfraSPlus_CT.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF commercial proposal
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF proposal InfraS
	************************************************/
	class pdf_InfraSPlus_CT extends ModelePDFContract
	{
		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $draft_watermark;
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
		public $signvalue;
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
		public $verLineStyle = array();
		public $horLineStyle = array();
		public $signLineCap = '';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		public $signLineStyle = array();
		public $TotRem = array('HT' => 0, 'TTC' => 0, 'multicurrency_HT' => 0, 'multicurrency_TTC' => 0);
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
		public $tableau = array();	// Array of table to print
		public $heightforfooter;
		public $larg_tabtotal;
		public $larg_tabinfo;
		public $posxtabtotal;
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $largcol11;
		public $posystamp;
		public $typeadr;


		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusCrontractName');
			$this->description					= $langs->trans('PDFInfraSPlusCrontractDescription');
			$this->titlekey						= 'ContractCard';
			$this->defaulttemplate				= getDolGlobalString('CONTRACT_ADDON_PDF', '');
			$this->draft_watermark				= getDolGlobalString('CONTRACT_DRAFT_WATERMARK', '');
			$this->show_sign_area				= getDolGlobalInt('INFRASPLUS_PDF_CONTRACT_SHOW_SIGNATURE', 0);
			$this->show_ExtraFieldsLines		= getDolGlobalInt('INFRASPLUS_PDF_EXFL_CT', 0);
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 0;	// Display payment mode
			$this->option_condreg				= 0;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 1;	// Displays if there has been a discount
			$this->option_credit_note			= 0;	// Support credit notes
			$this->option_freetext				= 1;	// Support add of a personalised text
			$this->option_draft_watermark		= 1;	// Support add of a watermark on drafts
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		Contrat		$object				Object to generate
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
			$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_CT') ? '' : '_CT';
			$baseDir	= !empty($conf->contrat->multidir_output[$conf->entity]) ? $conf->contrat->multidir_output[$conf->entity] : $conf->contrat->dir_output;

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
					$this->listfreet			= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->CGV					= !empty($hookmanager->resArray['cgv']) ? $hookmanager->resArray['cgv'] : '';
					$this->files				= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					$this->usentascover			= !empty($hookmanager->resArray['usentascover']) ? $hookmanager->resArray['usentascover'] : '';
					$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : '';
					$this->with_picture			= !empty($hookmanager->resArray['hidepict']) ? $hookmanager->resArray['hidepict'] : '';
					$this->refcol				= !empty($hookmanager->resArray['refcol']) ? $hookmanager->resArray['refcol'] : '';
					$this->hide_discount		= !empty($hookmanager->resArray['hidedisc']) ? $hookmanager->resArray['hidedisc'] : '';
					$this->hide_cols			= !empty($hookmanager->resArray['hidecols']) ? $hookmanager->resArray['hidecols'] : '';
					$this->showwvccchk			= !empty($hookmanager->resArray['showwvccchk']) ? $hookmanager->resArray['showwvccchk'] : '';
					$this->signvalue			= !empty($hookmanager->resArray['signvalue']) ? $hookmanager->resArray['signvalue'] : '';
					if (!empty($this->usentascover)) {
						$this->first_page_empty	= 1;	// Comme on veut une page de garde on créé une page vide en premier puis on insère la note prévue sur celle-ci
					}
					if (!empty($this->refcol)) {
						$hideref	= 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
					}
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
					$tagvs					= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('ContractCard'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('ContractCard').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
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
					$realpatharray			= array();
					$pricesObjProd			= array();
					$listObjBib				= array();
					$listDescBib			= array();
					$objproduct				= new Product($this->db);
					$this->TotRem			= 0;
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if (isModEnabled('infrasworkflow') && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE') && $object->lines[$i]->product_type == Product::TYPE_PRODUCT) {
							continue;
						}
						$isProd	= !empty($object->lines[$i]->fk_product) ? $objproduct->fetch($object->lines[$i]->fk_product) : 0;
						// Positionne $this->atleastonediscount si on a au moins une remise
						if (!empty($object->lines[$i]->remise_percent)) {
							$this->atleastonediscount++;
						}
						if (!empty($this->discount_auto) && $isProd > 0 && $object->lines[$i]->subprice < $objproduct->price) {
							$this->atleastonediscount++;
							$pricesObjProd[$i]['pu_ht']					= $objproduct->price;
							$pricesObjProd[$i]['pu_ttc']				= $objproduct->price_ttc;
							$pricesObjProd[$i]['multicurrency_pu_ht']	= $this->use_multicurrency ? $objproduct->price * $object->multicurrency_tx : $objproduct->price;
							$pricesObjProd[$i]['multicurrency_pu_ttc']	= $this->use_multicurrency ? $objproduct->price_ttc * $object->multicurrency_tx : $objproduct->price_ttc;
							$pricesObjProd[$i]['pu_ttc']				= $objproduct->price_ttc;
							$pricesObjProd[$i]['remise']				= (($objproduct->price - $object->lines[$i]->subprice) * 100) / $objproduct->price;
							if (!empty($this->show_tot_disc)) {
								$this->TotRem	+= pdf_InfraSPlus_getTotRem($object, $i, $this->only_ht, $pricesObjProd[$i]);
							}
						} else	$pricesObjProd[$i]	= array();
						// detect if there is at least one image to show
						if (!empty($this->with_picture) && $isProd > 0) {
							if (!empty($this->old_path_photo)) {
								$pdir[0]	= get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id ."/photos/";
								$pdir[1]	= get_exdir(0, 0, 0, 0, $objproduct, 'product').dol_sanitizeFileName($objproduct->ref).'/';
							} else {
								$pdir[0]	= get_exdir(0, 0, 0, 0, $objproduct, 'product'); // default
								$pdir[1]	= get_exdir($objproduct->id, 2, 0, 0, $objproduct, 'product').$objproduct->id ."/photos/";		// alternative
							}
							$arephoto	= false;
							$onlyOne	= $this->only_one_picture ? (in_array($objproduct->id, $listObjBib) ? 1 : 0) : 0;
							foreach ($pdir as $midir) {
								if (!$arephoto && !$onlyOne) {
									$dir	= ($objproduct->entity != $conf->entity ? $conf->product->multidir_output[$objproduct->entity] : $conf->product->dir_output).'/'.$midir;
									foreach ($objproduct->liste_photos($dir, 1) as $key => $obj) {
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
							} else {
								$realpatharray[$i]	= '';
							}
						} else {
							$realpatharray[$i]	= '';
						}
					}
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
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
						$this->larg_updisc	= 0;
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
						$this->larg_desc	= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_unit + $this->larg_up + $this->larg_tva + $this->larg_discount + $this->larg_updisc +
												$this->larg_progress + $this->larg_totalht + $this->larg_totalttc); // Largeur variable suivant la place restante
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
					$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head					= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead			= $head['totalhead'];
					$hauteurcadre			= $head['hauteurcadre'];
					$tab_top				= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
					$tab_top_newpage		= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$ht_coltotal			= 0;
					if (!empty($this->show_sign_area)) {
						$ht_coltotal	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
					}
					$heightforinfotot		= $ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal;
					$heightforinfotot		+= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle);
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
							$tab_top	= $tab_top_newpage;
						}
					}
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
						$txtC12		= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->date_contrat, 'day', false, $outputlangs, true);
						$largC12	= $this->larg_util_txt - $largC11;
						$xC12		= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12, 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY		= $tab_top + $this->tab_hl + 1;
						if ($object->ref_customer || $object->ref_supplier) {
							$txtC21						= '';
							if ($object->ref_customer) {
								$txtC21	.= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_customer);
							}
							if ($object->ref_supplier) {
								$txtC21	.= ($txtC21 ? ' / ' : '').$outputlangs->transnoentities('RefSupplier').' : '.$outputlangs->transnoentities($object->ref_supplier);
							}
							$pdf->MultiCell($largC21, $this->tab_hl, $txtC21, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						$txtC22	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt + $largC21, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$largC22	= $this->larg_util_txt - $largC21;
							$xC22		= !empty($txtC21) ? $this->posx_G_txt + $this->larg_util_txt - $largC22 : $this->posx_G_txt;
							$pdf->MultiCell($largC22, $this->tab_hl, $txtC22, 0, (!empty($txtC21) ? 'R' : 'L'), 0, 0, $xC22, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						if ($object->ref_customer || !empty($txtC22)) {
							$nexY	+= $this->tab_hl + 1;
						}
						$height_header_inf	= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf	+= $this->tab_hl;
					}
					$tab_top	+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $this->heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, $this->first_page_empty);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if (isModEnabled('infrasworkflow') && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE') && $object->lines[$i]->product_type == Product::TYPE_PRODUCT) {
							continue;
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
						$imglinesize				= !empty($this->with_picture) ? pdf_InfraSPlus_getlineimgsize($this->tableau['desc']['larg'], $realpatharray[$i]) : array();	// Define size of image if we need it
						// Photo of product line first
						if (!empty($this->with_picture) && empty($this->picture_under) && empty($this->picture_after)) {
							if (($curY + (!empty($imglinesize['width']) && !empty($imglinesize['height']) ? $imglinesize['height'] : $this->tab_hl)) > ($this->page_hauteur - ($this->heightforfooter))) {	// If photo too high, we moved completely on new page
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
								$curY						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								$showpricebeforepagebreak	= 0;
							}
							$curY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau['desc']['posx'], $curY, $this->tableau['desc']['larg'], $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl);
							$curY	+= $this->picture_padding;
						}
						$objectligne	= $object->lines[$i];
						$valide			= empty($objectligne->id) ? 0 : $objectligne->fetch($objectligne->id);
						if ($valide > 0 || $object->specimen) {
							// Hauteur de la référence
							$this->heightline	= $this->tab_hl;
							if (empty($this->hide_cols)) {
								// Reference
								if (!empty($this->refcol) || !empty($this->show_num_col)) {
									$pdf->startTransaction();
									$startline			= $pdf->GetY();
									$ref				= !empty($this->refcol) ? pdf_getlineref($object, $i, $outputlangs, $hidedetails) : $i + 1;
									$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $ref, '', $this->force_align_left_ref, 0, 1, $this->tableau['ref']['posx'], $startline, true, 0, 0, false, 0, 'M', false);
									$endline			= $pdf->GetY();
									$this->heightline	= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
									$pdf->rollbackTransaction(true);
								}
							}
							$extraDet	= '';
							if ($object->lines[$i]->product_type != 9) {
								$pdf->SetLineStyle($this->horLineStyle);
								// extrafieldsline
								$extrafieldslines	= '';
								if (!empty($this->show_ExtraFieldsLines)) {
									$extrafieldslines	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor, $outputlangs);
								}
								$extraDet	.= empty($extrafieldslines) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$extrafieldslines.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
							}
							// Description of product line
							$pageposdesc	= $pdf->getPage();
							$hide_desc		= !empty($hidedesc) ? $hidedesc : (!empty($object->lines[$i]->fk_product) && $this->only_one_desc ? (in_array($object->lines[$i]->fk_product, $listDescBib) ? 1 : 0) : 0);
							pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hide_desc, 0, $extraDet, null, $this->desc_full_line, 0, $this->with_picture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url, $this->picture_padding);
							$ret			= !empty($object->lines[$i]->fk_product) ? $listDescBib[] = $object->lines[$i]->fk_product : '';
							$pageposafter	= $pdf->getPage();
							$posyafter		= $pdf->GetY();
							if ($pageposafter > $pageposbefore) {	// There is a pagebreak
								if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
									if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
										$pdf->AddPage('','',true);
										$pdf->setPage($pageposafter + 1);
										pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
										$watermarkedPages[$pdf->getPage()]	= true;
									}
								} else {
									$showpricebeforepagebreak	= 0; // we found a pagebreak
								}
							} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
									pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
									$watermarkedPages[$pdf->getPage()]	= true;
								}
							}
							$nexY	= $pdf->GetY();
							// Photo of product line after description
							if (!empty($this->with_picture) && !empty($this->picture_after)) {
								$pageposimg	= $pdf->getPage();
								$nexY		+= $this->picture_padding;
								if ($this->linkpictureurl) {
									$lineurl	= pdf_InfraSPlus_getlineurl($object, $i);
									$txturl		= pdf_InfraSPlus_formatNotes($object, $outputlangs, $this->linkpictureurl);
									$linkurl	= !empty($lineurl) ? '<a href = "'.$lineurl.'" target = "_blank">'.$txturl.'</a>' : '&nbsp;';
									$ht_url		= $pdf->getStringHeight($this->tableau[$colPicture]['larg'], $txturl);
								}
								if (($nexY + (!empty($imglinesize['width']) && !empty($imglinesize['height']) ? $imglinesize['height'] : $this->tab_hl) + $ht_url) > ($this->page_hauteur - ($this->heightforfooter + ($i == ($nblignes - 1) ? $heightforinfotot : 0)))) {	// If photo too high, we moved completely on new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposimg + 1);
									pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
									$watermarkedPages[$pdf->getPage()]	= true;
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
								if ($curY > ($this->page_hauteur - $this->heightforfooter - $this->tab_hl)) {
									$pdf->setPage($pageposafter);
									$curY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
								} else {
									$pdf->setPage($pageposdesc);
								}
							}
							$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
							if (empty($this->hide_cols)) {
								// Reference
								if (!empty($this->refcol) || !empty($this->show_num_col)) {
									$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $ref, '', $this->force_align_left_ref, 0, 1, $this->tableau['ref']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Quantity
								if (empty($this->hide_qty)) {
									$qty	= pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
									$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Unit
							if (!empty($this->product_use_unit)) {
									$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
									$pdf->writeHTMLCell($this->tableau['unit']['larg'], $this->heightline, $this->tableau['unit']['posx'], $curY, $unit, 0, 1, false, true, $this->force_align_left_unit, true);
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
									$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, $up_line, '', 'R', 0, 1, $this->tableau['up']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// VAT Rate
								if (empty($this->hide_vat) && empty($this->hide_vat_col)) {
									$vat_rate	= pdf_InfraSPlus_getlinevatrate($pdf, $object, $i, $outputlangs, $hidedetails);
									$pdf->MultiCell($this->tableau['tva']['larg'], $this->heightline, $vat_rate, '', 'R', 0, 1, $this->tableau['tva']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Discount on line
								if (($object->lines[$i]->remise_percent && empty($this->hide_discount)) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
									$remise_percent	= pdf_InfraSPlus_getlineremisepercent($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									$pdf->MultiCell($this->tableau['discount']['larg'], $this->heightline, $remise_percent, '', 'R', 0, 1, $this->tableau['discount']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Discounted price
								if (($object->lines[$i]->remise_percent && empty($this->hide_discount) && $this->show_up_discounted) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
									if (empty($this->hide_vat)) {
										$up_disc	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									} else {
										$up_disc	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, null, $pricesObjProd[$i]);
									}
									$pdf->MultiCell($this->tableau['updisc']['larg'], $this->heightline, $up_disc, '', 'R', 0, 1, $this->tableau['updisc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								}
								// Total line
								if (empty($this->hide_vat)) {
									$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails);
								} else {
									$total_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails);
								}
								$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								if($this->show_ttc_col) {
									$totalTTC_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails);
									$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, $totalTTC_line, '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
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
							if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
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
								$nextY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
							}
						}
					}
					$bottomlasttab	= $this->page_hauteur - $heightforinfotot - $this->heightforfooter - 1;
					if ($pagenb == 1 && empty($this->first_page_empty)) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} elseif ($pagenb == 2 && $this->first_page_empty) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} elseif ($pagenb > ($this->first_page_empty ? 2 : 1)) {
						$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $pagenb);
					}
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					if (!empty($this->show_sign_area)) {
						$posysignarea = $this->_signature_area($pdf, $object, $bottomlasttab, $outputlangs, 0, 0);
					}
					$posy	= !empty($posysignarea) ? $posysignarea : $bottomlasttab;
					$posy	= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
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
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			} else {
				$this->error	= $outputlangs->trans('ErrorConstantNotDefined', 'CONTRACT_OUTPUTDIR');
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Contrat		$object			Object to show
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
												$this->customerAddrSelect, -2, -2, '', 0, array(), '', -2, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
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
				$posy	+= $this->tab_hl;
				$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->date_contrat, 'day', false, $outputlangs, true);
				$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				if ($object->ref_customer || $object->ref_supplier) {
					$posy	+= $this->tab_hl - 0.5;
					$txtcc	= '';
					if ($object->ref_customer) {
						$txtcc	.= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_customer);
					}
					if ($object->ref_supplier) {
						$txtcc	.= ' / '.$outputlangs->transnoentities('RefSupplier').' : '.$outputlangs->transnoentities($object->ref_supplier);
					}
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
			if (!empty($showaddress)) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'CUSTOMER')
										);
				$addresses		= array();
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, 0, $this->emetteur, 0, '', 0, 0, -2, -2, $this->customerAddrSelect, $this->include_alias);
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
		*	@param		Contrat		$object			Object to show
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
		*	@param		Contrat		$object			Object to show
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
			if ($object->statut == Contrat::STATUS_DRAFT && (!empty($this->draft_watermark))) {
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
			if (!$this->hide_cols && !empty($this->showverline) && empty($this->desc_full_line)) {
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
		*	Show area for the customer to sign
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Contrat		$object			Object shown in PDF
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@param		int			$calculseul		no print => just to know the height
		*	@return		int							Position pour suite
		**/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0, $freetext = 0)
		{
			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$signarea_top		= $posy + 1 + (!empty($this->e_signing) && isModEnabled('uptosign') ? 10 : 0);	// si UpToSign et 2 cadres on décale les cadres vers le bas pour le STAMP
			$posxsignarea1		= $this->marge_gauche;
			$posxsignarea2		= $this->posxtabtotal;
			$larg_signarea		= $this->larg_tabtotal;
			$signarea_hl		= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $object->thirdparty->name));
			$signarea_hl2		= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $this->emetteur->name));
			$signarea_hl		= $signarea_hl < $signarea_hl2 ? ($signarea_hl2 < $this->tab_hl ? $this->tab_hl : $signarea_hl2) : ($signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities("ContactNameAndSignature", $object->thirdparty->name), '', 'L', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities("ContactNameAndSignature", $this->emetteur->name), 0, 'L', 0, 1, $posxsignarea2 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
			$pdf->RoundedRect($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			if (!empty($this->signvalue)) {
				pdf_InfraSPlus_Client_Sign($pdf, $this->signvalue, $larg_signarea, $this->ht_signarea, $posxsignarea1, $signarea_top + $signarea_hl);
			}
			if (!empty($this->e_signing) && isModEnabled('uptosign')) {
				$this->posystamp	= $posy + 1;
			}
			if (!empty($calculseul)) {
				$heightforarea	= ($signarea_top + $signarea_hl + $this->ht_signarea + 1 + (!empty($this->e_signing) && isModEnabled('uptosign') ? 10 : 0)) - $posy;	// si UpToSign et 2 cadres on décale les cadres vers le bas pour le STAMP
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			} else {
				if (!empty($this->e_signing)) {
					if (!isModEnabled('uptosign')) {
						$pdf->addEmptySignatureAppearance($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						$pdf->addEmptySignatureAppearance($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
					} else {
						pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'internal', $posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
					}
				}
				$pdf->commitTransaction();
				return $signarea_top + $signarea_hl + $this->ht_signarea + 1 + (!empty($this->e_signing) && isModEnabled('uptosign') ? 10 : 0);	// si UpToSign et 2 cadres on décale les cadres vers le bas pour le STAMP
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Contrat		$object			Object shown in PDF
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
