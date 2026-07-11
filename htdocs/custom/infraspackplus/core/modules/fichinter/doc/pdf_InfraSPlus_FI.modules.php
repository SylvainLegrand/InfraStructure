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
	* 	\file		./infraspackplus/core/modules/fichinter/doc/pdf_InfraSPlus_FI.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF fiche inter
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF intervention card InfraS
	************************************************/
	class pdf_InfraSPlus_FI extends ModelePDFFicheinter
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
		public $files;
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
		public $posystamp;
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
		public $largcol11;
		public $tableau = [];	// Array of table to print
		public $heightforfooter;
		public $larg_tabtotal;
		public $larg_tabinfo;
		public $posxtabtotal;
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $CGI;
		public $atleastoneproduct;
		public $duration_workday;
		public $endProd;
		public $endReport;
		public $hide_duration;
		public $lastNoteAsTable;
		public $pageEndProd;
		public $pageEndReport;
		public $posxdesc;
		public $pricefichinter = [];
		public $prodfichinter;
		public $show_sign_area_cli;
		public $show_sign_area_emet;
		public $show_dates_hours;
		public $showntusedascover;
		public $sign_area_full;
		public $typeadr;
		public $showtot;
		public $startNote;
		public $startProd;

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
			$this->db 							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusFicheInterName');
			$this->description					= $langs->trans('PDFInfraSPlusFicheInterDescription');
			$this->titlekey						= 'InterventionCard';
			$this->defaulttemplate				= getDolGlobalString('FICHEINTER_ADDON_PDF', '');
			$this->product_use_unit				= 0;
			$this->duration_workday				= getDolGlobalInt('MAIN_DURATION_OF_WORKDAY', 8);
			$this->draft_watermark				= getDolGlobalString('FICHINTER_DRAFT_WATERMARK', '');
			$this->lastNoteAsTable				= getDolGlobalInt('INFRASPLUS_PDF_LAST_NOTE_AS_TABLE', 0);
			$this->show_dates_hours				= getDolGlobalInt('INFRASPLUS_PDF_SHOW_DATES_HOURS_FI', 0);
			$this->show_sign_area_cli			= getDolGlobalInt('INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE', 0);
			$this->show_sign_area_emet			= getDolGlobalInt('INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET', 0);
			$this->show_sign_area				= $this->show_sign_area_cli || $this->show_sign_area_emet ? 1 : 0;
			$this->sign_area_full				= getDolGlobalInt('INFRASPLUS_PDF_INTERVENTION_SIGNATURE_FULL', 0);
			$this->show_ExtraFieldsLines		= getDolGlobalInt('INFRASPLUS_PDF_EXFL_FI', 0);
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
		*	@param		Fichinter	$object				Object to generate
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
			$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_FI') ? '' : '_FI';
			$baseDir	= !empty($conf->ficheinter->multidir_output[$conf->entity]) ? $conf->ficheinter->multidir_output[$conf->entity] : $conf->ficheinter->dir_output;

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
					$this->listfreet			= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->CGI					= !empty($hookmanager->resArray['cgi']) ? $hookmanager->resArray['cgi'] : '';
					$this->files				= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					$this->showntusedascover	= !empty($hookmanager->resArray['showntusedascover']) ? $hookmanager->resArray['showntusedascover'] : '';
					$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : '';
					$this->with_picture			= !empty($hookmanager->resArray['hidepict']) ? $hookmanager->resArray['hidepict'] : '';
					$this->refcol				= !empty($hookmanager->resArray['refcol']) ? $hookmanager->resArray['refcol'] : '';
					$this->hide_duration		= !empty($hookmanager->resArray['hidetimespent']) ? $hookmanager->resArray['hidetimespent'] : 0;
					$hidedesc					= !empty($hookmanager->resArray['hidedesc']) ? $hookmanager->resArray['hidedesc'] : '';
					$this->hide_discount		= !empty($hookmanager->resArray['hidedisc']) ? $hookmanager->resArray['hidedisc'] : '';
					$this->hide_cols			= !empty($hookmanager->resArray['hidecols']) ? $hookmanager->resArray['hidecols'] : '';
					$this->showwvccchk			= !empty($hookmanager->resArray['showwvccchk']) ? $hookmanager->resArray['showwvccchk'] : '';
					$this->showtot				= !empty($hookmanager->resArray['showtot']) ? $hookmanager->resArray['showtot'] : '';
					$this->signvalue			= !empty($hookmanager->resArray['signvalue']) ? $hookmanager->resArray['signvalue'] : '';
					$nblignes					= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->refcol)) {
						$hideref = 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
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
					$pdf->SetSubject($outputlangs->transnoentities('InterventionCard'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('InterventionCard'));
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
					$realpatharray			= [];
					$discount				= [];
					$isProd					= [];
					$pricesObjProd			= [];
					$listObjBib				= [];
					$listDescBib			= [];
					$objproduct				= new Product($this->db);
					$this->nbrProdTot		= 0;
					$this->nbrProdDif		= [];
					$this->ecoTaxes			= [];
					// Module management associé
					if (!empty( isModEnabled('management'))) {
						$this->pricefichinter	= [];
						$this->pricefichinter	= pdf_infrasplus_getpricefichinter($object);
					}
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// Module management associé
						$this->prodfichinter[$i]	= [];
						$isProd[$i]					= 0;
						if (!empty( isModEnabled('management'))) {
							$this->prodfichinter[$i]	= pdf_infrasplus_getlinefichinter($object, $i);
						}
						$discount[$i]		= !empty($this->prodfichinter[$i]) ? $this->prodfichinter[$i]['remise_percent']	: $object->lines[$i]->remise_percent;
						$fk_product			= !empty($this->prodfichinter[$i]) ? $this->prodfichinter[$i]['fk_product']		: $object->lines[$i]->fk_product;
						$qty				= pdf_InfraSPlus_getlineqty($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
						$product_type		= !empty($this->prodfichinter[$i]) ? $this->prodfichinter[$i]['product_type']	: $object->lines[$i]->product_type;
						$product_ref		= !empty($this->prodfichinter[$i]) ? $this->prodfichinter[$i]['ref']			: $object->lines[$i]->product_ref;
						$product_subprice	= !empty($this->prodfichinter[$i]) ? $this->prodfichinter[$i]['subprice']		: $object->lines[$i]->subprice;
						// Positionne $this->atleastoneproduct si on a au moins un produit / service
						if (!empty($fk_product)) {
							$this->atleastoneproduct++;
							$isProd[$i]	= $objproduct->fetch($fk_product);
						}
						// Positionne $this->atleastonediscount si on a au moins une remise
						if (!empty($discount[$i])) {
							$this->atleastonediscount++;
						}
						if (!empty($this->discount_auto) && $isProd[$i] > 0 && $product_subprice != $objproduct->price) {
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
						} else {
							$pricesObjProd[$i]	= [];
						}
						// Collecte des totaux par valeur de tva dans $this->tva['taux'] = total_tva
						$tvaligne			= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['total_tva']			: $object->lines[$i]->total_tva;
						$localtax1ligne		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['total_localtax1']	: $object->lines[$i]->total_localtax1;
						$localtax2ligne		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['total_localtax2']	: $object->lines[$i]->total_localtax2;
						$localtax1_rate		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax1_tx']		: $object->lines[$i]->localtax1_tx;
						$localtax2_rate		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax2_tx']		: $object->lines[$i]->localtax2_tx;
						$localtax1_type		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax1_type']	: $object->lines[$i]->localtax1_type;
						$localtax2_type		= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['localtax2_type']	: $object->lines[$i]->localtax2_type;
						$vatrate			= $this->prodfichinter[$i]	? (string) $this->prodfichinter[$i]['tva_tx']	: (string) $object->lines[$i]->tva_tx;
						$info_bits			= $this->prodfichinter[$i]	? $this->prodfichinter[$i]['info_bits']			: $object->lines[$i]->info_bits;
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
						if (($info_bits & 0x01) == 0x01) {
							$vatrate	.= '*';
						}
						if (! isset($this->tva[$vatrate])) {
							$this->tva[$vatrate]	= 0;
						}
						$this->tva[$vatrate]	+= $object->lines[$i]->product_type != 9 && $object->lines[$i]->special_code != 501028 ? $tvaligne : 0;
						// detect if there is at least one image to show
						if (!empty($this->with_picture) && $isProd[$i] > 0) {
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
						if ($isProd[$i] > 0 && isModEnabled('ouvrage') && is_array($this->listPrefixEcotax) && count($this->listPrefixEcotax) > 0) {
							foreach ($this->listPrefixEcotax as $PrefixEcotax) {
								if (! isset($this->ecoTaxes[$PrefixEcotax]['ht'])) {
									$this->ecoTaxes[$PrefixEcotax]['ht']	= 0;
								}
								if (! isset($this->ecoTaxes[$PrefixEcotax]['ttc'])) {
									$this->ecoTaxes[$PrefixEcotax]['ttc']	= 0;
								}
								$this->ecoTaxes[$PrefixEcotax]['ht']	+= preg_match('/'.$PrefixEcotax.'(.*)/', $objproduct->ref, $reg)								? $object->lines[$i]->total_ht									: 0;
								$this->ecoTaxes[$PrefixEcotax]['ttc']	+= preg_match('/'.$PrefixEcotax.'(.*)/', $objproduct->ref, $reg)								? $object->lines[$i]->total_ttc									: 0;
								$this->hasEcoTaxes						= !empty($this->ecoTaxes[$PrefixEcotax]['ht']) || !empty($this->ecoTaxes[$PrefixEcotax]['ttc'])	? $outputlangs->transnoentities('PDFInfraSPlusInclEcoTaxes')	: $this->hasEcoTaxes;
							}
						}
						// Attribut supplémentaires de produit
						if ($isProd[$i] > 0 && !empty($this->exfEcoTax)) {
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
						if (!empty($this->show_qty_prod_tot) && $product_type == 0) {
							$this->nbrProdTot								+= $qty;
							if (!in_array($product_ref, $this->nbrProdDif)) {
								$this->nbrProdDif[]	= $product_ref;
							}
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
					} elseif (empty($this->atleastonediscount)) {
						$this->larg_discount	= 0;
					}
					if (!empty($this->hide_discount)) {
						$this->larg_updisc	= 0;
					} elseif (empty($this->show_up_discounted)) {
						$this->larg_updisc	= 0;
					} elseif (empty($this->atleastonediscount)) {
						$this->larg_updisc	= 0;
					}
					if (empty($this->show_ttc_col)) {
						$this->larg_totalttc	= 0;
					}
					if (empty($this->hide_cols)) {
						$this->larg_desc	= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_unit + $this->larg_up + $this->larg_tva + $this->larg_discount +
											$this->larg_updisc + $this->larg_totalht + $this->larg_totalttc); // Largeur variable suivant la place restante
					} else {
						$this->num_desc		= $this->num_qty > $this->num_desc ? 1 : 2;
						$this->num_qty		= $this->num_desc == 2 ? 1 : 2;
						$this->larg_desc	= $this->larg_util_cadre - $this->larg_qty;
					}
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
											'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
											'up'		=> array('col' => $this->num_up,		'larg' => $this->larg_up,		'posx' => 0),
											'tva'		=> array('col' => $this->num_tva,		'larg' => $this->larg_tva,		'posx' => 0),
											'discount'	=> array('col' => $this->num_discount,	'larg' => $this->larg_discount,	'posx' => 0),
											'updisc'	=> array('col' => $this->num_updisc,	'larg' => $this->larg_updisc,	'posx' => 0),
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
					$this->larg_tabtotal	= 80;
					$this->larg_tabinfo		= $this->page_largeur - $this->marge_gauche - $this->marge_droite - $this->larg_tabtotal;
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
					$heightforheader		= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
					$ht_coltotal			= !empty($this->atleastoneproduct) && $this->showtot ? $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1) : 0;
					if (!empty($this->show_sign_area)) {
						$ht_coltotal	+= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
					}
					$heightforinfotot		= $ht_coltotal;
					$heightforinfotot		+= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle);
					$this->heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					// Livraison
					$height_livr			= 0;
					if (!empty($head['livrshow'])) {
						$pdf->SetFont('', 'B', $default_font_size + 2);
						$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusInter')), 0, 1);
						$xlivr	= $pdf->GetX() + $pdf->GetStringWidth($outputlangs->transnoentities('PDFInfraSPlusInter'), '', 'B', $default_font_size + 2) + 5;
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
						if (empty($object->statut)) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11	= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						$txtC12		= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->datec, 'day', false, $outputlangs, true);
						$largC12	= $this->larg_util_txt - $largC11;
						$xC12		= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', (!empty($this->datesbold) ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12, 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY		= $tab_top + $this->tab_hl + 1;
						$txtC22		= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, $txtC22, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
							$nexY	+= $this->tab_hl + 1;
						}
						$height_header_inf	= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf	+= $this->tab_hl;
					}
					$tab_top	+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $this->heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 1, $this->first_page_empty);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					// Affiche description
					$pdf->SetFont('', 'B', $default_font_size);
					$text		= $outputlangs->transnoentities('Description').' : '.$object->description;
					if ($object->duration > 0 && ! $this->hide_duration) {
						$totaltime	= convertSecondToTime($object->duration, 'allhourmin', $this->duration_workday);
						$text		.= (!empty($text) ? ' - ' : '').$outputlangs->trans('PDFInfraSPlusTemps').' : '.$totaltime;
					}
					if (isModEnabled('management') && !empty($this->show_dates_hours)) {
						$dateo	= !empty($object->dateo) ? $outputlangs->trans('PDFInfraSPlusDateO').' : '.dol_print_date($object->dateo, 'dayhour', false, $outputlangs, true) : '';
						$datee	= !empty($object->datee) ? $outputlangs->trans('PDFInfraSPlusDateE').' : '.dol_print_date($object->datee, 'dayhour', false, $outputlangs, true) : '';
						$text	.= (!empty($text) ? ' - ' : '').$dateo.(!empty($dateo) ? ' - ' : '').$datee;
					}
					// Add internal intervening if defined
					$arrayidcontact	= $object->getIdContact('internal','INTERVENING');
					if (count($arrayidcontact) > 0) {
						$object->fetch_user($arrayidcontact[0]);
						$text	.= (!empty($text) ? '<br />' : '' ).$outputlangs->transnoentities('PDFInfraSPlusFicheInterIntervening').' : '.$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
						if (!empty($object->user->email)) {
							$text	.= ', '.$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($object->user->email);
						}
						if (!empty($object->user->office_phone)) {
							$text	.= ', '.$outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($object->user->office_phone)));
						}
					}
					$desc			= dol_htmlentitiesbr($text, 1);
					$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->marge_gauche + $this->decal_round, $tab_top, $outputlangs->convToOutputCharset($desc), 0, 1, 0);
					$nexY			= $pdf->GetY();
					$height_desc	= $nexY - $tab_top;
					$tab_top		+= $height_desc;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					$nbReport		= -1;
					// Loop on each lines for report
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if (!empty($isProd[$i])) {
							continue;	// we keep only report line
						}
						$nbReport++;	// On incrémente le nombre de ligne de rapport
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
						// Description of report
						$valide						= empty($object->lines[$i]->id) ? 0 : $object->lines[$i]->fetch($object->lines[$i]->id);
						if ($valide > 0 || $object->specimen) {
							$extraDet	= '';
							$pdf->SetLineStyle($this->horLineStyle);
							// extrafieldsline
							if (!empty($this->show_ExtraFieldsLines)) {
								$extraDet	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor, $outputlangs);
							}
							// Custom values (weight, volume and code
							$WVCC	= '';
							if (!empty($this->showwvccchk)) {
								$WVCC	= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs, $this->emetteur);
							}
							$extraDet	.= empty($WVCC) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$WVCC.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
							// Description of product line
							$txt		= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->lines[$i]->date, 'dayhour', false, $outputlangs, true);
							if ($object->lines[$i]->duration > 0 && empty($this->hide_duration)) {
								$txt	.= ' - '.$outputlangs->transnoentities('Duration').' : '.convertSecondToTime($object->lines[$i]->duration);
							}
							$txt			= '<strong>'.dol_htmlentitiesbr($txt, 1, $outputlangs->charset_output).'</strong>';
							$desc			= dol_htmlentitiesbr($object->lines[$i]->desc, 1);
							$desc			= pdf_InfraSPlus_formatNotes($object, $outputlangs, $desc);	// enable the use of an image in description
							$pageposdesc	= $pdf->getPage();
							if ($object->lines[$i]->date) {
								$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posxdesc, $curY, $txt.'<br style = "line-height: '.$default_font_size.'px;"/>'.$desc.(empty($extraDet) ? '' : '<br style = "line-height: '.$default_font_size.'px;"/>'.$extraDet), 0, 1, 0);
							} else {
								pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->larg_util_txt, $this->tab_hl, $this->posxdesc, $curY, $hideref, 0, 0, $extraDet);
							}
							$pageposafter	= $pdf->getPage();
							$posyafter		= $pdf->GetY();
							if ($pageposafter > $pageposbefore) {	// There is a pagebreak
								if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
									if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
										$pdf->AddPage('','',true);
										$pdf->setPage($pageposafter + 1);
									}
								} else {
									$showpricebeforepagebreak	= 0; // we found a pagebreak
								}
							} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
									$watermarkedPages[$pdf->getPage()]	= true;
									$pdf->setPage($pageposafter + 1);
								}
							}
							$nexY			= $pdf->GetY();
							$pageposafter	= $pdf->getPage();
							$pdf->setPage($pageposbefore);
							$pdf->setTopMargin($this->marge_haute);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
								$pdf->setPage($pageposdesc);
							}
							$pdf->SetFont('','', $default_font_size - 1);	// On repositionne la police par defaut
							// Add dash or space between line
							if (!empty($this->dash_between_line) && $nbReport < ($nblignes - $this->atleastoneproduct - 1)) {
								$pdf->setPage($pageposafter);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
								$nexY	+= 2;
							} else {
								$nexY	+= $this->lineSep_hight;
							}
							// Detect if some page were added automatically and output _tableau for past pages
							while ($pagenb < $pageposafter) {
								$pdf->setPage($pagenb);
								$pagenb++;
								$pdf->setPage($pagenb);
								$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
								// Extraire le contenu texte pour corriger le z-order (filigrane/en-tete avant texte)
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
								// Reinjecter le contenu texte apres filigrane et en-tete
								if (method_exists($pdf, 'dropPageContent')) {
									$pdf->dropPageContent($savedContent);
								}
								// Restore grayscale FillColor after _pagehead to keep ColorFlag true
								$pdf->SetFillColor(255);
								$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
							}
							if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
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
					}
					// Enregistrement de la position intermédiaire
					$this->endReport		= $nexY;
					$this->pageEndReport	= $pdf->getPage();
					$entreTable				= ($this->tab_hl * 1.5) + $this->decal_round;
					$this->startProd		= $this->endReport + $entreTable;
					$nexY					+= ($this->hide_top_table && $this->pageEndReport > 1 ? $entreTable : ($entreTable + $this->ht_top_table)) + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					$nbProduct				= -1;
					// Loop on each lines for products / services
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// Module management associé
						if (!$isProd[$i]) {
							continue;	// we keep only product or service line
						}
						$nbProduct++;	// On incrémente le nombre de ligne de produit / service
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
						// We don't want a title line alone at the end of the page (just before a page break)
						$isSubTotalLine				= isModEnabled('subtotal') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
						$isInfraSLine	= infraspackplus_isInfrastructureLine($object->lines[$i]) ? 1 : 0;
						$isInfraSTotal	= infraspackplus_isInfrastructureTotal($object->lines[$i]) ? 1 : 0;	// Sous-total infrastructure (qty 91..99)
						$colYOffset		= !empty($isInfraSTotal) ? 1.0 : 0;	// pdfAddTotal applique setCellPaddings T=1 au libellé du sous-total. Les MultiCell des colonnes voisines ne respectent pas ce padding (hauteur explicite + valign 'M'), d'où un décalage visuel de ~1mm. On compense en décalant manuellement le Y des MultiCell pour les sous-totaux infrastructure.
						$isSubTitle					= $isSubTotalLine && $object->lines[$i]->qty < 10 ? 1 : 0;	// Sous-titre ATM
						$isSubTotal					= $isSubTotalLine && $object->lines[$i]->qty > 90 ? 1 : 0;	// Sous-total ATM
						if (!empty($isSubTitle)) {
							$nextimglinesize	= !empty($this->with_picture) && empty($this->picture_under) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i + 1]) : [];	// Define size of image if we need it
							if (empty($this->picture_under) && empty($this->picture_after) && isset($nextimglinesize['height'])) {
								$nextlinehight	= $nextimglinesize['height'] + (!empty($this->linkpictureurl) ? $this->tab_hl * 2 : $this->tab_hl);
							} else {
								$nextlinehight	= $this->tab_hl;
							}
							if (($curY + $this->tab_hl + $nextlinehight) > ($this->page_hauteur - $this->heightforfooter)) {	// There is no space left for next line + total + free text
								$pageposbefore				= $pdf->getPage();
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								$curY						= $heightforheader;
								$showpricebeforepagebreak	= 0;
							}
						}
						if (!empty($isSubTotal)) {
							if (($curY + $this->tab_hl) > ($this->page_hauteur - $this->heightforfooter)) {	// There is no space left for next line + total + free text
								$pdf->AddPage('', '', true);
								$pdf->setPage($pageposbefore + 1);
								$curY						= $heightforheader;
								$showpricebeforepagebreak	= 0;
							}
						}
						$valide	= empty($object->lines[$i]->id) ? 0 : $object->lines[$i]->fetch($object->lines[$i]->id);
						if ($valide > 0 || $object->specimen) {
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
							// Reference
							if (!empty($isProduct) && (!empty($this->refcol) || !empty($this->show_num_col))) {
								$pdf->startTransaction();
								$startline			= $curY;	// Utiliser $curY (position reelle de la ligne) plutot que $pdf->GetY(), qui peut etre desynchronise du curseur PDF interne
								$ref				= !empty($this->refcol) ? pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]) : $i + 1;
								$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $startline, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
								$endline			= $pdf->GetY();
								$heightRef			= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
								// l'image existe et doit être dans la colonne réf. => hauteur URL + hauteur image + 1/2 ligne d'espacement SINON on réinitialise à 0
								$this->heightline	= !empty($this->picture_in_ref) && !empty($imglinesize['width']) && !empty($imglinesize['height']) ? $imglinesize['height'] + $ht_url + ($this->tab_hl / 2) : 0;
								// J'ai une hauteur de ligne, donc une image, et ça remplace le réf. => on n'ajoute rien SINON on ajoute la hauteur prise par la référence + 1 ligne d'espacement si j'ai une image
								$this->heightline	+= !empty($this->heightline) && !empty($this->picture_replace_ref) ? 0 : $heightRef + (!empty($this->heightline) && empty($this->picture_replace_ref) ? $this->tab_hl : 0);
								$pdf->rollbackTransaction(true);
							}
							// Photo of product line first
							if (!empty($imglinesize['width']) && !empty($imglinesize['height']) && empty($this->picture_under) && empty($this->picture_after)) {
								if (($curY + (!empty($this->picture_in_ref) ? $this->heightline : $imglinesize['height']) + ($this->picture_padding * 2) + $ht_url) > ($this->page_hauteur - ($this->heightforfooter))) {	// If photo too high, we moved completely on new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposbefore + 1);
									$curY						= $heightforheader;
									$showpricebeforepagebreak	= 0;
								}
								$PictureY	= $curY + (!empty($this->picture_in_ref) ? $heightRef + ($this->tab_hl / 2) : $this->picture_padding);
								$PictureY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $this->tableau[$colPicture]['posx'], $PictureY, $this->tableau[$colPicture]['larg'], $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url);
								$curY		= (!empty($this->picture_in_ref) ? $curY : $PictureY) +	$this->picture_padding;
							}
							// Description of product line => preparation
							// Photo of product line between the label and the long description
							if (!empty($this->picture_under) && !empty($imglinesize['width']) && !empty($imglinesize['height'])) {
								if (($curY + $this->tab_hl + $imglinesize['height'] + $this->picture_padding) > ($this->page_hauteur - ($this->heightforfooter))) {	// If photo too high, we moved completely on new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposbefore + 1);
									$curY	= $heightforheader;
									$showpricebeforepagebreak	= 0;
								}
							}
							// extra fields and others informations after the long description
							$extraDet	= '';
							if ($product_type != 9) {
								$pdf->SetLineStyle($this->horLineStyle);
								// Ajout du numéro de série, s'il existe...
								$serialEquip		= isModEnabled('equipement') ? pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, $i, 'intervention') : '';
								$extraDet			.= empty($serialEquip) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$serialEquip.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
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
							}
							// Description of product line
							$txt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->lines[$i]->datei, 'dayhour', false, $outputlangs, true);
							if ($object->lines[$i]->duration > 0) {
								$txt	.= ' - '.$outputlangs->transnoentities('Duration').' : '.convertSecondToTime($object->lines[$i]->duration);
							}
							$txt			= '<strong>'.dol_htmlentitiesbr($txt, 1, $outputlangs->charset_output).'</strong>';
							$desc			= dol_htmlentitiesbr($object->lines[$i]->desc, 1);
							$pageposdesc	= $pdf->getPage();
							$hide_desc		= !empty($hidedesc) ? $hidedesc : (!empty($object->lines[$i]->fk_product) && $this->only_one_desc ? (in_array($object->lines[$i]->fk_product, $listDescBib) ? 1 : 0) : 0);
							if (!empty($object->lines[$i]->datei)) {
								$pdf->writeHTMLCell($this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $txt.'<br>'.$desc.(empty($extraDet) ? '' : '<br/>'.$extraDet), 0, 1, 0);
							} else {
								pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hide_desc, 0, $extraDet, $this->prodfichinter[$i], $this->desc_full_line, 0, $this->with_picture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url, $this->picture_padding, $this->exfEcoTax);
							}
							$ret			= !empty($object->lines[$i]->fk_product) ? $listDescBib[] = $object->lines[$i]->fk_product : '';
							$pageposafter	= $pdf->getPage();
							$posyafter		= $pdf->GetY();
							if ($pageposafter > $pageposbefore) {	// There is a pagebreak
								if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
									if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
										$pdf->AddPage('','',true);
										$pdf->setPage($pageposafter + 1);
									}
								} else {
									$showpricebeforepagebreak	= 0; // we found a pagebreak
								}
							} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
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
							// Quantity
							if (empty($this->hide_qty)) {
								$qty	= pdf_InfraSPlus_getlineqty($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
								$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
							}
							if (empty($this->hide_cols)) {
								// Reference
								if ((!empty($this->refcol) || !empty($this->show_num_col)) && (empty($this->picture_in_ref) || !empty($this->picture_in_ref) && empty($this->picture_replace_ref))) {
									$pagepos	= $pdf->getPage();
									$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY + $colYOffset, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
									$pdf->setPage($pagepos);
								}
								// Unit price
								if (empty($this->hide_up)) {
									if (empty($this->hide_discount)) {
										if (empty($this->hide_vat)) {
											$up_line	= pdf_InfraSPlus_getlineupexcltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i], $pricesObjProd[$i]);
										} else {
											$up_line	= pdf_InfraSPlus_getlineupincltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i], $pricesObjProd[$i]);
										}
									} else {
										if (empty($this->hide_vat)) {
											$up_line	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
										} else {
											$up_line	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
										}
									}
									$pdf->MultiCell($this->tableau['up']['larg'], $this->heightline, $up_line, '', 'R', 0, 1, $this->tableau['up']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
								}
								// VAT Rate
								if (empty($this->hide_vat) && empty($this->hide_vat_col)) {
									$vat_rate	= pdf_InfraSPlus_getlinevatrate($pdf, $object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
									$pdf->MultiCell($this->tableau['tva']['larg'], $this->heightline, $vat_rate, '', 'R', 0, 1, $this->tableau['tva']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
								}
								// Discount on line
								if (($discount[$i] && empty($this->hide_discount)) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
									$remise_percent	= pdf_InfraSPlus_getlineremisepercent($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i], $pricesObjProd[$i]);
									$pdf->MultiCell($this->tableau['discount']['larg'], $this->heightline, $remise_percent, '', 'R', 0, 1, $this->tableau['discount']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
								}
								// Discounted price
								if (($discount[$i] && empty($this->hide_discount) && $this->show_up_discounted) || (!empty($this->discount_auto) && !empty($pricesObjProd[$i]['remise']))) {
									if (empty($this->hide_vat)) {
										$up_disc	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
									} else {
										$up_disc	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
									}
									$pdf->MultiCell($this->tableau['updisc']['larg'], $this->heightline, $up_disc, '', 'R', 0, 1, $this->tableau['updisc']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
								}
								// Total line
								if (empty($this->hide_vat)) {
									$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
								} else {
									$total_line = pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails, $this->prodfichinter[$i]);
								}
								$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
								if ($this->show_ttc_col) {
									$totalTTC_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails);
									$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, $totalTTC_line, '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
								}
							}
							// Add dash or space between line
							if (!empty($this->dash_between_line) && $nbProduct < ($this->atleastoneproduct - 1)) {
								$pdf->setPage($pageposafter);
								$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
								$nexY	+= 2;
							} else {
								$nexY	+= $this->lineSep_hight;
							}
							// Detect if some page were added automatically and output _tableau for past pages
							while ($pagenb < $pageposafter) {
								$pdf->setPage($pagenb);
								$pagenb++;
								$pdf->setPage($pagenb);
								$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
								// Extraire le contenu texte pour corriger le z-order (filigrane/en-tete avant texte)
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
								// Reinjecter le contenu texte apres filigrane et en-tete
								if (method_exists($pdf, 'dropPageContent')) {
									$pdf->dropPageContent($savedContent);
								}
								// Restore grayscale FillColor after _pagehead to keep ColorFlag true
								$pdf->SetFillColor(255);
								$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
							}
							if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
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
					}
					// Enregistrement de la position intermédiaire
					$this->endProd		= $nexY;
					$this->pageEndProd	= $pdf->getPage();
					$entreTable2		= ($this->tab_hl * 1.5) + $this->decal_round;
					$this->startNote	= $this->endProd + $entreTable2;
					$nexY				+= $this->hide_top_table ? $entreTable2 : ($entreTable2 + $this->ht_top_table);
					// Last note as table
					if (!empty($object->note_public) && $this->lastNoteAsTable) {
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
						// Description of report
						$pdf->SetLineStyle($this->horLineStyle);
						$notetoshow					= pdf_InfraSPlus_formatNotes($object, $outputlangs, $object->note_public);
						$pdf->startTransaction();
						$pdf->writeHTMLCell($this->larg_desc, $this->tab_hl, $this->posxdesc, $curY, $notetoshow, 0, 1, 0);
						$pageposafter				= $pdf->getPage();
						$pageposdesc				= $pdf->getPage();
						$posyafter					= $pdf->GetY();
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							$pdf->rollbackTransaction(true);
							$pageposafter	= $pageposbefore;
							$pdf->setPageOrientation('', 1, $this->heightforfooter);	// Edit the bottom margin of current page to set it.
							$pageposdesc	= $pdf->getPage();
							$pdf->writeHTMLCell($this->larg_desc, $this->tab_hl, $this->posxdesc, $curY, $notetoshow, 0, 1, 0);
							$pageposafter	= $pdf->getPage();
							$posyafter		= $pdf->GetY();
							if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								$pdf->AddPage('','',true);
								pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
								$watermarkedPages[$pdf->getPage()]	= true;
								$pdf->setPage($pageposafter + 1);
							} else {
								$showpricebeforepagebreak	= 0; // we found a pagebreak
							}
						} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {
							$pdf->rollbackTransaction(true);
							$pageposafter	= $pageposbefore;
							$pdf->setPageOrientation('', 1, $this->heightforfooter);	// Edit the bottom margin of current page to set it.
							$pageposdesc	= $pdf->getPage();
							$pdf->writeHTMLCell($this->larg_desc, $this->tab_hl, $this->posxdesc, $curY, $notetoshow, 0, 1, 0);
							$pageposafter	= $pdf->getPage();
							$posyafter		= $pdf->GetY();
							$pdf->AddPage('','',true);
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
							$watermarkedPages[$pdf->getPage()]	= true;
							$pdf->setPage($pageposafter + 1);
						} else {
							$pdf->commitTransaction();	// No pagebreak
						}
						$nexY			= $pdf->GetY();
						$pageposafter	= $pdf->getPage();
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
						if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
							$pdf->setPage($pageposdesc);
						}
						$pdf->SetFont('','', $default_font_size - 1);	// On repositionne la police par defaut
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							// Extraire le contenu texte pour corriger le z-order (filigrane/en-tete avant texte)
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
							// Reinjecter le contenu texte apres filigrane et en-tete
							if (method_exists($pdf, 'dropPageContent')) {
								$pdf->dropPageContent($savedContent);
							}
							// Restore grayscale FillColor after _pagehead to keep ColorFlag true
							$pdf->SetFillColor(255);
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						}
					}
					for ($i = 1 ; $i < $pagenb ; $i++) {
						$pdf->setPage($i);
						$this->_pagefoot($pdf, $object, $outputlangs, 0);
						$bottom	= $this->page_hauteur - $this->heightforfooter;
						if (!empty($this->atleastoneproduct) && (!empty($object->note_public) && $this->lastNoteAsTable)) {
							if ($i == 1) {
								if ($i < $this->pageEndReport) {
									$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								} elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startProd <= $bottom) {
										$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
									}
								} elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startNote <= $bottom) {
										$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
									}
								}
							} else {
								if ($i < $this->pageEndReport) {
									$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								} elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startProd <= $bottom) {
										$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
									}
								} elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startNote <= $bottom) {
										$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
									}
								} elseif ($i > $this->pageEndReport && $i < $this->pageEndProd) {
									$this->_tableau($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
								} elseif ($i > $this->pageEndReport && $i == $this->pageEndProd) {
									$this->_tableau($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
									if ($this->startNote <= $bottom) {
										$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
									}
								} elseif ($i > $this->pageEndReport && $i > $this->pageEndProd) {
									$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
							}
						} elseif (!empty($this->atleastoneproduct)) {
							if ($i == 1 && $i < $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							} elseif ($i < $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							} elseif ($i == 1 && $i == $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottom) {
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								}
							} elseif ($i == $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottom) {
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottom - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								}
							} else {
								$this->_tableau($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
							}
						} elseif (!empty($object->note_public) && $this->lastNoteAsTable) {
							if ($i == 1 && $i < $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							} elseif ($i < $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i, 1);
							} elseif ($i == 1 && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endProd - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottom) {
									$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
							} elseif ($i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottom) {
									$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottom - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
							} else {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i, 1);
							}
						} else {
							if ($i == 1) {
								$this->_tableau1($pdf, $object, $tab_top, $bottom - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							} else {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $bottom - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
							}
						}
					}
					$pdf->setPage($pagenb);
					$bottomlasttab	= $this->page_hauteur - $heightforinfotot - $this->heightforfooter;
					if (!empty($this->atleastoneproduct) && (!empty($object->note_public) && $this->lastNoteAsTable)) {
						if ($i == 1) {
							if ($i < $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							} elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottomlasttab) {
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								}
							} elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
								$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottomlasttab) {
									$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
							}
						} else {
							if ($i < $this->pageEndReport) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							} elseif ($i == $this->pageEndReport && $i < $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startProd <= $bottomlasttab) {
									$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								}
							} elseif ($i == $this->pageEndReport && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								$this->_tableau($pdf, $object, $this->endReport + $entreTable, $this->endProd - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottomlasttab) {
									$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
							} elseif ($i > $this->pageEndReport && $i < $this->pageEndProd) {
								$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
							} elseif ($i > $this->pageEndReport && $i == $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
								if ($this->startNote <= $bottomlasttab) {
									$this->_tableau1($pdf, $object, $this->endProd + $entreTable2, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
								}
							} elseif ($i > $this->pageEndReport && $i > $this->pageEndProd) {
								$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
						}
					} elseif (!empty($this->atleastoneproduct)) {
						if ($i == 1 && $i < $this->pageEndReport) {
							$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
						} elseif ($i < $this->pageEndReport) {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
						} elseif ($i == 1 && $i == $this->pageEndReport) {
							$this->_tableau1($pdf, $object, $tab_top, $this->endReport - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startProd <= $bottomlasttab) {
								$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
							}
						} elseif ($i == $this->pageEndReport) {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endReport - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startProd <= $bottomlasttab) {
								$this->_tableau($pdf, $object, $this->endReport + $entreTable, $bottomlasttab - ($this->endReport + $entreTable), $outputlangs, $this->hide_top_table, 1, $i);
							}
						} else {
							$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
						}
					} elseif (!empty($object->note_public) && $this->lastNoteAsTable) {
						if ($i == 1 && $i < $this->pageEndProd) {
							$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
						} elseif ($i < $this->pageEndProd) {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
						} elseif ($i == 1 && $i == $this->pageEndProd) {
							$this->_tableau1($pdf, $object, $tab_top, $this->endProd - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startNote <= $bottomlasttab) {
								$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
						} elseif ($i == $this->pageEndProd) {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $this->endProd - $tab_top_newpage, $outputlangs, $this->hide_top_table, 1, $i);
							if ($this->startNote <= $bottomlasttab) {
								$this->_tableau1($pdf, $object, $this->endProd + $entreTable, $bottomlasttab - ($this->endProd + $entreTable2), $outputlangs, $this->hide_top_table, 1, $i, 1);
							}
						} else {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i, 1);
						}
					} else {
						if ($i == 1) {
							$this->_tableau1($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $i);
						} else {
							$this->_tableau1($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $i);
						}
					}
					$posytot		= !empty($this->atleastoneproduct) && $this->showtot ? $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0) : $bottomlasttab;
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$posysignarea	= $this->show_sign_area ? $this->_signature_area($pdf, $object, $posytot, $outputlangs, 0, 0) : $posytot;
					$posy			= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posysignarea, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					// If merge CGI is active
					if (!empty($this->CGI)) {
						pdf_InfraSPlus_CGV($pdf, $this->CGI, $this->hidepagenum, $object, $outputlangs, $this->formatpage);
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
				$this->error=$outputlangs->transnoentities('ErrorConstantNotDefined', 'FICHEINTER_OUTPUTDIR');
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Fichinter	$object			Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		array		$hauteurhead	'totalhead'		= hight of header
		*											'hauteurcadre	= hight of frame
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
				$pdf->SetFont('', (!empty($this->datesbold) ? 'B' : ''), $default_font_size - 2);
				$posy	+= $this->tab_hl;
				$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->datec, 'day', false, $outputlangs, true);
				$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
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
			$dimCadres['Y']	= !empty($this->use_iso_location) && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl);
			if (!empty($showaddress)) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'INTERREPFOLL'),
										'E' => $object->getIdContact('external', 'CUSTOMER')
										);
				$addresses		= [];
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', null, 0, -2, -2, $this->customerAddrSelect, $this->include_alias);
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
		*	@param		Fichinter	$object		Object to show
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
		*	Show table with unique column for lines
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Fichinter	$object		Object to show
		*	@param		string		$tab_top		Top position of table
		*	@param		string		$tab_height		Height of table (rectangle)
		*	@param		Translate	$outputlangs	Langs object
		*	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int			$hidebottom		Hide bottom bar of array
		*	@param		int			$pagenb			N° of page
		*	@param		int			$note			Table for note or for Report
		*	@return		void
		**/
		protected function _tableau1(&$pdf, $object, $tab_top, $tab_height, $outputlangs, $hidetop = 0, $hidebottom = 0, $pagenb, $note = '')
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
				} elseif (!empty($this->showtblline)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
				// Table frame
				if (!empty($this->showtblline))	 {
					$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				} else {
					$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
				}
			} elseif (!empty($this->showtblline)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			}
			if ($object->statut == Fichinter::STATUS_DRAFT && (!empty($this->draft_watermark))) {
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
			// Colonnes
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->larg_util_txt, $this->ht_top_table, $outputlangs->transnoentities(($note ? 'PDFInfraSPlusPiecesPrevision' : 'PDFInfraSPlusRapport')), '', 'C', 0, 1, $this->posxdesc, $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
			}
		}

		/**
		*	Show table for lines
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Fichinter	$object			Object to show
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
			if (!empty($hidetop)) {
				$hidetop	= -1;
			}
			$currency				= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
			$default_font_size		= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				if ($pagenb == 1) {
					$infocurrency	= !empty($this->hide_info_cur) ? '' : $outputlangs->transnoentities('AmountInCurrency', $outputlangs->transnoentitiesnoconv('Currency'.$currency));
					$pdf->MultiCell($pdf->GetStringWidth($infocurrency) + 3, 2, $infocurrency, '', 'R', 0, 1, $this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($infocurrency) + 3) - $this->decal_round, $tab_top - $this->tab_hl, true, 0, 0, false, 0, 'M', false);
				}
				// Table header
				if (!empty($this->title_bg)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				} elseif (!empty($this->showtblline) && empty($this->desc_full_line)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
				// Table frame
				if (!empty($this->showtblline) && empty($this->desc_full_line)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				} else {
					$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
				}
			} elseif (!empty($this->showtblline) && empty($this->desc_full_line)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			}
			if ($object->statut == Fichinter::STATUS_DRAFT && !empty($this->draft_watermark)) {
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
			if (empty($this->hide_cols) && !empty($this->showverline) && empty($this->desc_full_line)) {
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
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusPieces'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (empty($this->hide_qty)) {
					$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Qty'), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
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
		*	Show total to pay
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Fichinter	$object			Object to show
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
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalHTShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ht	= $this->pricefichinter['total_ht'];
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ht, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				if (!empty($this->invert_bg_ht_ttc)) {
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
			}
			if ((empty($this->only_ht) && empty($this->only_ttc)) || $this->show_ttc_vat_tot) {
				// Show VAT by rates and total
				$tvaisnull	= !empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000']) ? true : false;
				if (!empty($this->hide_vat_ifnull) && $tvaisnull) {
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
					foreach ($this->tva as $tvakey => $tvaval) {
						if ($tvakey > 0) {	// On affiche pas taux 0
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
							$totalvat	.= vatrate($tvakey, 1).$tvacompl;
							$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $tvaval, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
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
			}
			// Total TTC
			if (empty($this->only_ht)) {
				if (empty($this->invert_bg_ht_ttc)) {
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalTTC').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ttc	= $this->pricefichinter['total_ttc'];
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			// ecoTaxes
			if (is_array($this->ecoTaxes) && count($this->ecoTaxes) > 0) {
				foreach ($this->ecoTaxes as $key => $ecoTaxe) {
					if (!empty($this->only_ht) && !empty($ecoTaxe['ht']) || !empty($ecoTaxe['ttc'])) {
						$valEcoTaxe	= price2num(!empty($this->only_ht) ? $ecoTaxe['ht'] : $ecoTaxe['ttc'], 'MT');
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotalEcoTaxe', (!empty($this->only_ht) ? $outputlangs->transnoentities('HT') : $outputlangs->transnoentities('TTC'))).' '.$key, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $valEcoTaxe, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
			}
			if (!empty($this->number_words)) {
				$index++;
				$savcurrency	= $conf->currency;
				$conf->currency	= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
				$total			= ! $this->only_ht ? $total_ttc : $total_ht;
				$total_words	= $outputlangs->transnoentities('PDFInfraSPluSInterventionArrete').' : '.$outputlangs->getLabelFromNumber($total, 1);
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
		*	@param		Fichinter	$object			Object to show
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@param		int			$calculseul		no print => just to know the height
		*	@param		int			$freetext		1 if signature follows free text
		*	@return		int							Position pour suite
		**/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0, $freetext = 0)
		{
			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$signarea_hl_cli	= 0;
			$signarea_hl_full	= 0;
			$textNameCli		= '';
			$signarea_top		= $posy + 1 + (!empty($this->show_sign_area_emet) && !empty($this->e_signing) && isModEnabled('uptosign') ? 10 : 0);	// si UpToSign et 2 cadres on décale les cadres vers le bas pour le STAMP
			$posxsignarea1		= $this->marge_gauche;
			$posxsignarea2		= $this->posxtabtotal;
			$larg_signarea		= $this->larg_tabtotal;
			$signarea_hl_emet	= $this->show_sign_area_emet ? $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignTech')) : 0;
			$arrayidcontact		= array('I' => $object->getIdContact('internal', 'INTERVENING'),
										'E' => $object->getIdContact('external', 'CUSTOMER')
										);
			if (!empty($this->show_sign_area_cli)) {
				if (is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0) {
					$object->fetch_contact($arrayidcontact['E'][0]);
					$textNameCli	= $outputlangs->convToOutputCharset($object->contact->getFullName($outputlangs, 1, 4));
				}
				$signarea_hl_cli	= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignClient', $textNameCli));
			}
			if (!empty($this->sign_area_full)) {
				if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
					$object->fetch_user($arrayidcontact['I'][0]);
					$textName			= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
					$signarea_hl_full	= $pdf->getStringHeight($larg_signarea, $textName);
				}
			}
			$signarea_hl	= $signarea_hl_emet < $signarea_hl_cli ? ($signarea_hl_cli < $signarea_hl_full ? $signarea_hl_full : $signarea_hl_cli) : ($signarea_hl_emet < $signarea_hl_full ? $signarea_hl_full : $signarea_hl_emet);
			$signarea_hl	= $signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl;
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (!empty($this->show_sign_area_emet)) {
				$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignTech'), '', 'L', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
				$pdf->RoundedRect($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			}
			if (!empty($this->show_sign_area_cli)) {
				$pdf->MultiCell($larg_signarea, $signarea_hl, $outputlangs->transnoentities('PDFInfraSPlusFicheInterSignClient', $textNameCli), '', 'L', 0, 1, $posxsignarea2 + $this->decal_round, $signarea_top, true, 0, 0, false, 0, 'M', false);
				$pdf->RoundedRect($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea, $this->Rounded_rect, '1111', null, $this->signLineStyle);
			}
			// Add internal intervening if defined
			if (!empty($this->sign_area_full)) {
				if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
					$pdf->SetFont('', 'B', $default_font_size);
					$pdf->MultiCell($larg_signarea - ($this->decal_round * 2), $signarea_hl, $textName, '', 'C', 0, 1, $posxsignarea1 + $this->decal_round, $signarea_top + (($this->ht_signarea + $signarea_hl) / 2), true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 2);
				}
			}
			if (!empty($this->signvalue)) {
				pdf_InfraSPlus_Client_Sign($pdf, $this->signvalue, $larg_signarea, $this->ht_signarea, $posxsignarea2, $signarea_top + $signarea_hl);
			}
			if (!empty($this->e_signing) && isModEnabled('uptosign')) {
				$this->posystamp	= $posy + 1;
			}
			if (!empty($calculseul)) {
				$heightforarea	= $signarea_hl + $this->ht_signarea + 2 + (!empty($this->e_signing) && isModEnabled('uptosign') && !empty($this->show_sign_area_emet) ? 10 : 0);	// si UpToSign et 2 cadres on décale les cadres vers le bas pour le STAMP
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			} else {
				if (!empty($this->e_signing)) {
					// Signature électronique
					if (!isModEnabled('uptosign')) {
						if (!empty($this->show_sign_area_emet)) {
							$pdf->addEmptySignatureAppearance($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						}
						if (!empty($this->show_sign_area_cli)) {
							$pdf->addEmptySignatureAppearance($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						}
					} else {
						if (!empty($this->show_sign_area_emet)) {
							pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'internal', $posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						}
						if (!empty($this->show_sign_area_cli)) {
							pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						}
					}
				}
				$pdf->commitTransaction();
				return $signarea_top + $signarea_hl + $this->ht_signarea + 1 + (!empty($this->e_signing) && isModEnabled('uptosign') && !empty($this->show_sign_area_emet) ? 10 : 0);
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Fichinter	$object			Object to show
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int							Return height of bottom margin including footer text
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul)
		{
			$showdetails	= $this->type_foot.(!empty($this->pied) ? 1 : 0);
			$noendline		= !empty($this->atleastoneproduct) && $this->showtot ? 0 : 1;
			$specialFoot	= infraspackplus_fetchAllSpecialFooters(array($object->element));
			if (!empty($specialFoot['rootFileName'])) {
				$specialFoot	= 'pdf_'.$specialFoot['rootFileName'].'_pagefoot';
				$hauteurfoot	= $specialFoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle, $noendline);
				return $hauteurfoot;
			}
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, $this->hidepagenum, $this->bodytxtcolor, $this->stdLineStyle, $noendline);
		}
	}