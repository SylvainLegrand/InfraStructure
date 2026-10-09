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
	* 	\file		./infraspackplus/core/modules/supplier_invoice/pdf/pdf_InfraSPlus_FF.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF supplier invoice
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
	include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF supplier invoice InfraS
	************************************************/
	class pdf_InfraSPlus_FF extends ModelePDFSuppliersInvoices
	{
		const MIN_GAP_BEFORE_FOOTER	= 0.75;	// Espace mini garanti entre le bas du dernier bloc (colonne infos/bank ou zone de signature) et la ligne de separation du pied de page (~2px a 96dpi)

		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $draft_watermark;
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
		public $paid;
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
		public $listfreet;
		public $listnotep;
		public $pied;
		public $hide_discount;
		public $hide_cols;
		public $show_tva_btp;
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
		public $larg_tabtotal;
		public $larg_tabinfo;
		public $posxtabtotal;
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $resteapayer;
		public $doli_addr_livr_recep;
		public $recipient;
		public $show_creation_date;
		public $show_ref_col;
		public $supplier_ref_name;
		public $wvcc_no_hr;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $conf, $langs, $mysoc;

			$langs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusSupplierInvoiceName');
			$this->description					= $langs->trans('PDFInfraSPlusSupplierInvoiceDescription');
			$this->titlekey						= 'SupplierInvoice';
			$this->recipient					= $mysoc;
			$this->defaulttemplate				= getDolGlobalString('INVOICE_SUPPLIER_ADDON_PDF', '');
			$this->no_payment_details			= getDolGlobalInt('INVOICE_NO_PAYMENT_DETAILS', 0);
			$this->supplier_ref_name			= getDolGlobalInt('SUPPLIER_REF_IN_NAME', 0);
			$this->draft_watermark				= getDolGlobalString('FACTURE_DRAFT_WATERMARK', '');
			$this->show_creation_date			= getDolGlobalInt('INFRASPLUS_PDF_CF_SHOW_CREATION_DATE', 0);
			$this->doli_addr_livr_recep			= getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0);
			$this->show_ref_col					= getDolGlobalInt('INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN', 0);
			$this->hide_discount				= getDolGlobalInt('INFRASPLUS_PDF_HIDE_DISCOUNT', 0);
			$this->show_ExtraFieldsLines		= getDolGlobalInt('INFRASPLUS_PDF_EXFL_FF', 0);
			$this->option_logo					= 0;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 1;	// Display payment mode
			$this->option_condreg				= 1;	// Display payment terms
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_escompte				= 1;	// Displays if there has been a discount
			$this->option_credit_note			= 1;	// Support credit notes
			$this->option_freetext				= 1;	// Support add of a personalised text
			$this->option_draft_watermark		= 1;	// Support add of a watermark on drafts
		}


		/**
		*	Function to build pdf onto disk
		*
		*	@param		FactureFournisseur		$object				Object to generate
		*	@param		Translate				$outputlangs		Lang output object
		*	@param		string					$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int						$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int						$hidedesc			Do not show desc
		*	@param		int						$hideref			Do not show ref
		*	@return	int							1=OK, 0=KO
		**/
		public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
		{
			global $user, $langs, $conf, $hookmanager, $nblignes;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

			// Get source company
			if (! is_object($object->thirdparty)) {
				$object->fetch_thirdparty();
			}
			if (! is_object($object->thirdparty)) {
				$object->thirdparty	= $this->recipient;	// If fetch_thirdparty fails, object has no socid (specimen)
			}
			$this->emetteur	= $object->thirdparty;
			if (! $this->emetteur->country_code) {
				$this->emetteur->country_code	= substr($langs->defaultlang, -2);	// By default, if was not defined
			}
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
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_FF') ? '' : '_FF';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_ADD_PREFIX_TO_FF', '');
				$filesufixe	= empty($fileprefix) ? '_FF' : '';
			}
			if ($conf->fournisseur->facture->dir_output) {
				$object->fetch_thirdparty();
				// Use of multicurrency for this document
				$this->use_multicurrency	= (isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? 1 : 0;
				$this->paid					= $object->getSommePaiement($this->use_multicurrency ? 1 : 0);
				$this->credit_notes			= $object->getSumCreditNotesUsed($this->use_multicurrency ? 1 : 0);
				$this->deposits				= $object->getSumDepositsUsed($this->use_multicurrency ? 1 : 0);
				if (!empty($this->show_ExtraFieldsLines)) {
					$extrafieldsline		= new ExtraFields($this->db);
					$extralabelsline		= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
				}
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$this->show_ExtraFieldsLines	= '';
					$dir							= $conf->fournisseur->facture->dir_output;
					$file							= $dir.'/SPECIMEN.pdf';
				} else {
					$objectref						= dol_sanitizeFileName($object->ref);
					$objectrefsupplier				= dol_sanitizeFileName($object->ref_supplier);
					$dir							= $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$objectref;
					$file							= $dir.'/'.$fileprefix.$objectref.$filesufixe.'.pdf';
					if (!empty($this->supplier_ref_name)) {
						$file	= $dir.'/'.$objectref.($objectrefsupplier ? '_'.$objectrefsupplier : '').'.pdf';
					}
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
					$parameters							= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook							= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$this->logo							= !empty($hookmanager->resArray['logo']) ? $hookmanager->resArray['logo'] : '';
					$this->listfreet					= array('SUPPLIER_INVOICE_FREE_TEXT');
					$this->listnotep					= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->show_tva_btp					= !empty($hookmanager->resArray['showtvabtp']) ? $hookmanager->resArray['showtvabtp'] : '';
					$nblignes							= count($object->lines);	// Set nblignes with the new facture lines content after hook
					if (!empty($this->show_ref_col)) {
						$hideref = 1;	// Comme on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information
					}
					$nbpayments 						= count($object->getListOfPayments());
					// Create pdf instance
					$pdf								= pdf_InfraSPlus_getInstance($this->format, 'mm', 'P');
					$default_font_size					= pdf_getPDFFontSize($outputlangs);																								// Must be after pdf_getInstance
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
					$pdf->SetSubject($outputlangs->transnoentities('PdfInvoiceTitle'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('PdfInvoiceTitle').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
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
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// First loop on each lines to prepare calculs and variables
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// Positionne $this->atleastonediscount si on a au moins une remise
						if (!empty($object->lines[$i]->remise_percent)) {
							$this->atleastonediscount++;
						}
						// Lignes optionnelles (special_code = 3) : exclues des totaux du document par le module infrastructure (hook updateTotalPrice, option INFRASTRUCTURE_MANAGE_OL),
						// elles doivent donc aussi être exclues de la ventilation TVA / taxes locales pour rester cohérentes avec les totaux HT / TTC affichés
						$isOptionalLine	= $object->lines[$i]->special_code == 3 && isModEnabled('infrastructure') && getDolGlobalString('INFRASTRUCTURE_MANAGE_OL') ? 1 : 0;
						// Lignes spéciales infrastructure (titres, sous-totaux, textes libres) : montants nuls mais taux 0% => elles créent une entrée parasite dans $this->tva,
						// faisant croire à un document multi-taux
						$isInfraSLine	= infraspackplus_isInfrastructureLine($object->lines[$i]) ? 1 : 0;
						// Collecte des totaux par valeur de tva dans $this->tva["taux"] = total_tva
						$tvaligne										= $this->use_multicurrency ? doubleval($object->lines[$i]->multicurrency_total_tva) : doubleval($object->lines[$i]->total_tva);
						$htligne										= $this->use_multicurrency ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
						$localtax1ligne									= $object->lines[$i]->total_localtax1;
						$localtax2ligne									= $object->lines[$i]->total_localtax2;
						$localtax1_rate									= $object->lines[$i]->localtax1_tx;
						$localtax2_rate									= $object->lines[$i]->localtax2_tx;
						$localtax1_type									= $object->lines[$i]->localtax1_type;
						$localtax2_type									= $object->lines[$i]->localtax2_type;
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
							$localtaxtmp_array	= getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $this->recipient);
							$localtax1_type		= isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
							$localtax2_type		= isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
						}
						if (empty($isOptionalLine) && empty($isInfraSLine)) {
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
							if (! isset($this->tva[$vatrate])) {
								$this->tva[$vatrate]	= 0;
							}
							$this->tva[$vatrate]								+= $tvaligne;
						}
					}
					// Define width and position of notes frames
					$this->larg_util_txt											= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre											= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt												= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->show_ref_col)) {
						$this->larg_ref	= 0;
					}
					if (empty($this->product_use_unit)) {
						$this->larg_unit	= 0;
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
						$this->larg_updisc	= 0;
					} else if (empty($this->atleastonediscount)) {
						$this->larg_updisc	= 0;
					}
					if (! $object->situation_cycle_ref) {
						$this->larg_progress	= 0;
					}
					$this->larg_desc												= $this->larg_util_cadre - ($this->larg_qty + $this->larg_totalht +
																						$this->larg_progress + $this->larg_discount + $this->larg_unit +
																						$this->larg_up + $this->larg_updisc + $this->larg_tva +
																						$this->larg_ref); // Largeur variable suivant la place restante
					$this->tableau													= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
																							'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
																							'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
																							'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
																							'up'		=> array('col' => $this->num_up,		'larg' => $this->larg_up,		'posx' => 0),
																							'tva'		=> array('col' => $this->num_tva,		'larg' => $this->larg_tva,		'posx' => 0),
																							'discount'	=> array('col' => $this->num_discount,	'larg' => $this->larg_discount,	'posx' => 0),
																							'updisc'	=> array('col' => $this->num_updisc,	'larg' => $this->larg_updisc,	'posx' => 0),
																							'progress'	=> array('col' => $this->num_progress,	'larg' => $this->larg_progress,	'posx' => 0),
																							'totalht'	=> array('col' => $this->num_totalht,	'larg' => $this->larg_totalht,	'posx' => 0),
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
						}
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1 + $this->largcol1;
					$this->posxcol3		= $this->posxcol2 + $this->largcol2;
					$this->posxcol4		= $this->posxcol3 + $this->largcol3;
					$this->posxcol5		= $this->posxcol4 + $this->largcol4;
					$this->posxcol6		= $this->posxcol5 + $this->largcol5;
					$this->posxcol7		= $this->posxcol6 + $this->largcol6;
					$this->posxcol8		= $this->posxcol7 + $this->largcol7;
					$this->posxcol9		= $this->posxcol8 + $this->largcol8;
					$this->posxcol10	= $this->posxcol9 + $this->largcol9;
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
					$emptyTvaArray	= [];
					pdf_InfraSPlus_normalizeTotals($object, $emptyTvaArray, $this->tva, $this->localtax1, $this->localtax2, $this->use_multicurrency, 1);	// Arrondis : totaux comptables coherents (HT + TVA = TTC), voir infraspackplus.pdf.lib.php
					$ht_coltotal			= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					if (($this->paid || $this->credit_notes || $this->deposits) && empty($this->no_payment_details)) {
						$ht_coltotal	+= $this->_tableau_versements($pdf, $object, $this->marge_haute, $outputlangs, 1);
						$ht2_coltotal	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					} else {
						$ht2_coltotal	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 1, 1, $this->horLineStyle) : 0;
					}
					$ht_coltotal		+= $ht2_coltotal;
					$heightforinfotot	= $ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal;
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
							$nexY					= $pdf->GetY();
							$height_incoterms		= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
							if ($this->showtblline) {
								$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_incoterms + 1, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
							}
							$height_incoterms		+= $this->tab_hl;
						}
					}
					$tab_top			+= $height_incoterms;
					// Header informations after Address blocks
					$height_header_inf	= 0;
					if (!empty($this->header_after_addr)) {
						$tab_top			+= $this->space_headerafter;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						$txtC11				= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
						if ($object->statut == 0) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11						= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						$txtC12							= $outputlangs->transnoentities('DateInvoice').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
						$txtC12b						= $outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_echeance, 'day', false, $outputlangs, true);
						$largC12						= $this->larg_util_txt - $largC11;
						$xC12							= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12.(empty($this->dates_br) ? ' / '.$txtC12b : ''), 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						if (!empty($this->dates_br)) {
							$pdf->MultiCell($largC12, $this->tab_hl, $txtC12b, 0, 'R', 0, 0, $xC12, $tab_top + $this->tab_hl, true, 0, 0, false, 0, 'M', false);
						}
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY							= $tab_top + ($this->tab_hl * (!empty($this->dates_br) ? 2 : 1)) + 1;
						if ($object->ref_supplier) {
							$txtC21		= $outputlangs->transnoentities('RefSupplier').' : '.$outputlangs->convToOutputCharset($object->ref_supplier);
							$largC21	= $pdf->GetStringWidth($txtC21, '', '', $default_font_size - 1) + 3;
							$pdf->MultiCell($largC21, $this->tab_hl, $txtC21, 0, 'L', 0, 0, $this->posx_G_txt, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						$txtC22	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $this->posx_G_txt + $largC21, $nexY, 0, $this->tab_hl, 'R', $this->header_after_addr);
						if (!empty($txtC22)) {
							$largC22	= $this->larg_util_txt - $largC21;
							$xC22		= $object->ref_supplier ? $this->posx_G_txt + $this->larg_util_txt - $largC22 : $this->posx_G_txt;
							$pdf->MultiCell($largC22, $this->tab_hl, $txtC22, 0, ($object->ref_supplier ? 'R' : 'L'), 0, 0, $xC22, $nexY, true, 0, 0, false, 0, 'M', false);
						}
						if ($object->ref_supplier || !empty($txtC22)) {
							$nexY	+= $this->tab_hl + 1;
						}
						$height_header_inf							= $this->Rounded_rect * 2 > $nexY - $tab_top ? $this->Rounded_rect * 2 : $nexY - $tab_top;
						$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->larg_util_cadre, $height_header_inf + 2, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
						$height_header_inf							+= $this->tab_hl;
					}
					$tab_top	+= $height_header_inf;
					// Affiche représentant, notes, Attributs supplémentaires et n° de série
					$height_note	= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, -1, $this->first_page_empty);
					$tab_top		+= 	$height_note > 0 ? $height_note : $this->tab_hl * 0.5;
					$nexY			= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					// Loop on each lines
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$isInfraSLine	= infraspackplus_isInfrastructureLine($object->lines[$i]) ? 1 : 0;
						$isInfraSTotal	= infraspackplus_isInfrastructureTotal($object->lines[$i]) ? 1 : 0;	// Sous-total infrastructure (qty 91..99)
						$colYOffset	= !empty($isInfraSTotal) ? 1.0 : 0;	// pdfAddTotal applique setCellPaddings T=1 au libellé du sous-total. Les MultiCell des colonnes voisines ne respectent pas ce padding (hauteur explicite + valign 'M'), d'où un décalage visuel de ~1mm. On compense en décalant manuellement le Y des MultiCell pour les sous-totaux infrastructure.
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
						$pdf->SetY($curY);	// Resynchronise le curseur PDF sur la position reelle de la ligne : sans cela, si pdf_InfraSPlus_writelinedesc() ne dessine rien (ex: hook tiers renvoyant une erreur sur pdf_writelinedesc), $pdf->GetY() resterait sur une position obsolete et desynchroniserait $nexY (chevauchement visuel des lignes suivantes)
						// Hauteur de la référence
						$this->heightline	= $this->tab_hl;
						if (empty($this->hide_cols)) {
							// Reference
							if (!empty($this->show_ref_col)) {
								$pdf->startTransaction();
								$startline			= $curY;	// Utiliser $curY (position reelle de la ligne) plutot que $pdf->GetY(), qui peut etre desynchronise du curseur PDF interne
								$ref				= pdf_getlineref($object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $ref, '', 'L', 0, 1, $this->tableau['ref']['posx'], $startline, true, 0, 0, false, 0, 'M', false);
								$endline			= $pdf->GetY();
								$this->heightline	= (ceil($endline) - ceil($startline)) > $this->tab_hl ? (ceil($endline) - ceil($startline)) : $this->tab_hl;
								$pdf->rollbackTransaction(true);
							}
						}
						$extraDet									= '';
						$extrafieldslines							= '';
						$pdf->SetLineStyle($this->horLineStyle);
						// extrafieldsline
						if (!empty($this->show_ExtraFieldsLines)) {
							$extrafieldslines	.= pdf_InfraSPlus_ExtraFieldsLines($object->lines[$i], $extrafieldsline, $extralabelsline, $this->exfltxtcolor, $outputlangs);
						}
						$extraDet									.= empty($extrafieldslines) ? '' : (!empty($this->wvcc_no_hr) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '')).$extrafieldslines.(!empty($this->wvcc_no_hr) ? '' : '<hr style = "width: 80%;">');
						// Description of product line
						$pageposdesc								= $pdf->getPage();
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hidedesc, 1, $extraDet);
						$pageposafter								= $pdf->getPage();
						$posyafter									= $pdf->GetY();
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								$showpricebeforepagebreak	= 0;
							}
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
							} else {
								$pdf->setPage($pageposdesc);
							}
						}
						$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
						// Reference
						if (!empty($this->show_ref_col)) {
							$ref	= pdf_InfraSPlus_getlineref_supplier($object, $i, $outputlangs, $hidedetails);
							$pdf->MultiCell($this->tableau['ref']['larg'], $this->heightline, $ref, '', 'L', 0, 1, $this->tableau['ref']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
						}
						// Quantity
						$qty	= pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
						// Unit
						if (!empty($this->product_use_unit)) {
							$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
							$pdf->writeHTMLCell($this->tableau['unit']['larg'], $this->heightline, $this->tableau['unit']['posx'], $curY + $colYOffset, $unit, 0, 1, false, true, $this->force_align_left_unit, true);
						}
						// Unit price
						if (empty($this->hide_up)) {
							if (empty($this->hide_discount)) {
								if (empty($this->hide_vat)) {
									$up_line	= pdf_InfraSPlus_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
								} else {
									$up_line	= pdf_InfraSPlus_getlineupincltax($object, $i, $outputlangs, $hidedetails);
								}
							} else {
								if (empty($this->hide_vat)) {
									$up_line	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, null, []);
								} else {
									$up_line	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, null, []);
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
						if ($object->lines[$i]->remise_percent && empty($this->hide_discount)) {
							$remise_percent	= pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
							$pdf->MultiCell($this->tableau['discount']['larg'], $this->heightline, $remise_percent, '', 'R', 0, 1, $this->tableau['discount']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
						}
						// Discounted price
						if ($object->lines[$i]->remise_percent && empty($this->hide_discount) && $this->show_up_discounted) {
							if (empty($this->hide_vat)) {
								$up_disc	= pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails, null, []);
							} else {
								$up_disc	= pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails, null, []);
							}
							$pdf->MultiCell($this->tableau['updisc']['larg'], $this->heightline, $up_disc, '', 'R', 0, 1, $this->tableau['updisc']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
						}
						// Total line
						if (empty($this->hide_vat)) {
							$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $i, $outputlangs, $hidedetails);
						} else {
							$total_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $i, $outputlangs, $hidedetails);
						}
						$pdf->MultiCell($this->tableau['totalht']['larg'], $this->heightline, $total_line, '', 'R', 0, 1, $this->tableau['totalht']['posx'], $curY + $colYOffset, true, 0, 0, false, 0, 'M', false);
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
								$watermarkedPages[$pdf->getPage()] = true;
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
					$bottomlasttab		= $this->page_hauteur - $heightforinfotot - $heightforfooter - 1;
					if ($pagenb == 1) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} else {
						$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					}
					$posyinfo			= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$posytot			= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					if (($this->paid || $this->credit_notes || $this->deposits) && empty($this->no_payment_details)) {
						$posytot		= $this->_tableau_versements($pdf, $object, $posytot, $outputlangs, 0);
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						$posyfreetext	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posytot;
					} else {
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						$posyfreetext	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 1, 0, $this->horLineStyle) : $posytot;
					}
					$posy										= $posyinfo > $posyfreetext ? $posyinfo : $posyfreetext;
					$posy										= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posy;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters									= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook									= $hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);	// Note that $action and $object may have been modified by some hooks
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
				$this->error=$outputlangs->transnoentities('ErrorConstantNotDefined', 'SUPPLIER_OUTPUTDIR');
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		TCPDF				$pdf			Object PDF
		*	@param		FactureFournisseur	$object			Object to show
		*	@param		int					$showaddress	0=no, 1=yes
		*	@param		Translate			$outputlangs	Object lang for output
		*	@return		array								Array with height of header and height of header+address
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{

			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$dimCadres			= array ('S' => ($this->page_largeur - ($this->marge_gauche + 6 + $this->left_recep_corner + $this->marge_droite)), 'R' => $this->left_recep_corner);	// page width = 210 (A4) 92 + 92  = 184 => keep 210 - 184 for margins => 26 ; 10 right and left and 6 on the middle
			$w					= $this->header_align_left ? 92 - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			$pdf->MultiCell($w, $this->tab_hl, $outputlangs->convToOutputCharset($this->emetteur->name), '', 'L', 0, 1, $this->marge_gauche, $posy, true, 0, 0, false, 0, 'M', false);
			$heightLogo 		= $this->tab_hl;
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
				$txtdt	= $outputlangs->transnoentities('DateInvoice').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
				if (empty($this->dates_br))
					$txtdt	.= ' / '.$outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_echeance, 'day', false, $outputlangs, true);
				$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				if (!empty($this->dates_br)) {
					$txtdt	= '';
					$posy	+= $this->tab_hl - 0.5;
					$txtdt	= $outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_echeance, 'day', false, $outputlangs, true);
					$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				$pdf->SetFont('', '', $default_font_size - 2);
				if ($object->ref_supplier) {
					$posy	+= $this->tab_hl - 0.5;
					$txtcc	= '';
					$txtcc	.= $outputlangs->transnoentities('RefSupplier').' : '.$outputlangs->convToOutputCharset($object->ref_supplier);
					$pdf->MultiCell($w, $this->tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
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
				$arrayidcontact	= array('I' => [],
										'E' => []
										);
				$addresses		= [];
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, '', $this->recipient, $this->emetteur, 0, 'supplierInvoice');
				$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
			}
			$hauteurhead	= array('totalhead'		=> $dimCadres['Y'] + $hauteurcadre,
									'hauteurcadre'	=> $hauteurcadre,
									'livrshow'		=> $addresses['livrshow']
									);
			return $hauteurhead;
		}

		/**
		*	Show top small header of page.
		*
		*	@param		TCPDF				$pdf			Object PDF
		*	@param		FactureFournisseur	$object			Object to show
		*	@param		int					$showaddress	0=no, 1=yes
		*	@param		Translate			$outputlangs	Object lang for output
		*	@return		void
		**/
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs)
		{

			$fromcompany	= $this->emetteur;
			$logo			= '';
			$title			= $outputlangs->transnoentities($this->titlekey);
			pdf_InfraSPlus_pagesmallhead($pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $this->formatpage, $this->decal_round, $logo, $this->headertxtcolor);
		}

		/**
		*	Show table for lines
		*
		*	@param		TCPDF				$pdf			Object PDF
		*	@param		FactureFournisseur	$object			Object to show
		*	@param		float				$tab_top		Top position of table
		*	@param		float				$tab_height		Height of table (rectangle)
		*	@param		Translate			$outputlangs	Langs object
		*	@param		int					$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*	@param		int					$hidebottom		Hide bottom bar of array
		*	@param		int					$pagenb			Current page number
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
			if ($object->statut == FactureFournisseur::STATUS_DRAFT && (!empty($this->draft_watermark))) {
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
				if ($this->posxcol10 > $this->posxcol9 && $this->posxcol10 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol10, $tab_top, $this->posxcol10,	$tab_top + $tab_height, $this->verLineStyle);
				}
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Designation"), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->show_ref_col)) {
					$pdf->MultiCell($this->tableau['ref']['larg'], $this->ht_top_table, $outputlangs->transnoentities('Ref'), '', 'C', 0, 1, $this->tableau['ref']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
				$pdf->MultiCell($this->tableau['qty']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Qty"), '', 'C', 0, 1, $this->tableau['qty']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->product_use_unit)) {
					$pdf->MultiCell($this->tableau['unit']['larg'], $this->ht_top_table, $outputlangs->transnoentities("Unit"), '', 'C', 0, 1, $this->tableau['unit']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
				if (empty($this->hide_up)) {
					if (empty($this->hide_vat)) {
						$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities("PriceUHT"), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					} else {
						$pdf->MultiCell($this->tableau['up']['larg'], $this->ht_top_table, $outputlangs->transnoentities("PriceUTTC"), '', 'C', 0, 1, $this->tableau['up']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					}
				}
				if (empty($this->hide_vat) && empty($this->hide_vat_col)) {
					$pdf->MultiCell($this->tableau['tva']['larg'], $this->ht_top_table, $outputlangs->transnoentities("VAT"), '', 'C', 0, 1, $this->tableau['tva']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
				if (!empty($this->atleastonediscount) && empty($this->hide_discount)) {
					$pdf->MultiCell($this->tableau['discount']['larg'], $this->ht_top_table, $outputlangs->transnoentities("ReductionShort"), '', 'C', 0, 1, $this->tableau['discount']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
					if (!empty($this->show_up_discounted)) {
						$pdf->MultiCell($this->tableau['updisc']['larg'], $this->ht_top_table, $outputlangs->transnoentities("PDFInfraSPlusDiscountedPrice"), '', 'C', 0, 1, $this->tableau['updisc']['posx'], $tab_top, true, 0, false, true, $this->ht_top_table, 'M', false);
					}
				}
				if (empty($this->hide_vat)) {
					$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities("TotalHT"), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				} else {
					$pdf->MultiCell($this->tableau['totalht']['larg'], $this->ht_top_table, $outputlangs->transnoentities("TotalTTC"), '', 'C', 0, 1, $this->tableau['totalht']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
			}
		}

		/**
		*	Show miscellaneous information (payment mode, payment term, ...)
		*
		*	@param		TCPDF				$pdf			Object PDF
		*	@param		FactureFournisseur	$object			Object to show
		*	@param		int					$posy			Y
		*	@param		Translate			$outputlangs	Langs object
		*	@param		int					$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int									Height of information printed
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
			$larg_col1info		= 40;
			$larg_col2info		= $larg_tabinfo - $larg_col1info;
			$posxtabinfo		= $this->marge_gauche;
			$posxcol2info		= $posxtabinfo + $larg_col1info;
			// Show payments conditions
			if ($object->cond_reglement_code || $object->cond_reglement) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre								= $outputlangs->transnoentities('PaymentConditions').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_condition_paiement				= $outputlangs->transnoentities('PaymentCondition'.$object->cond_reglement_code) != ('PaymentCondition'.$object->cond_reglement_code) ? $outputlangs->transnoentities('PaymentCondition'.$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
				$lib_condition_paiement				= str_replace('\n', "\n", $lib_condition_paiement);
				if ($object->deposit_percent > 0) {
					$lib_condition_paiement = str_replace('__DEPOSIT_PERCENT__', $object->deposit_percent, $lib_condition_paiement);
				}
				if (empty($this->show_paymenttermcond_2l)) {
					$pdf->writeHTMLCell($larg_col2info, $tabinfo_hl, $posxcol2info, $posytabinfo, pdf_InfraSPlus_formatNotes($object, $outputlangs, $lib_condition_paiement), 0, 1, false, true, 'L', true);
				} else {
					$posytabinfo	= $pdf->GetY();
					$pdf->writeHTMLCell($larg_col1info + $larg_col2info, $tabinfo_hl, $posxtabinfo, $posytabinfo, pdf_InfraSPlus_formatNotes($object, $outputlangs, $lib_condition_paiement), 0, 1, false, true, 'L', true);
				}
				$posytabinfo	= $pdf->GetY() + 1;
			}
			// Show payment mode
			if ($object->mode_reglement_code) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('PaymentMode').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$lib_mode_reg	= $outputlangs->transnoentities('PaymentType'.$object->mode_reglement_code) != ('PaymentType'.$object->mode_reglement_code) ? $outputlangs->transnoentities('PaymentType'.$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $lib_mode_reg, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
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
		*	@param		TCPDF				$pdf			Object PDF
		*	@param		FactureFournisseur	$object			Object to show
		*	@param		int					$posy			y
		*	@param		Translate			$outputlangs	Objet langs
		*	@param		int					$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int									Position pour suite
		**/
		protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{

			$pdf->startTransaction();
			// cf. _tableau_info() de pdf_InfraSPlus_D.modules.php : un sous-total infrastructure en derniere ligne du document laisse le padding
			// haut/bas des cellules a 1mm, ce qui gonflerait la hauteur reellement dessinee par ce bloc et le ferait deborder sur le pied de page.
			$savedCellPaddings				= $pdf->getCellPaddings();
			$pdf->setCellPaddings($savedCellPaddings['L'], 0, $savedCellPaddings['R'], 0);
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
			$sign							= ($object->type == 2 && !empty($this->credit_note)) ? -1 : 1;
			// Total HT
			$this->atleastoneratenotnull	= 0;
			if (empty($this->only_ttc)) {
				if (!empty($this->only_ht) || !empty($this->invert_bg_ht_ttc)) {
					$pdf->RoundedRect($posxtabtotal, $posytabtot, $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalHTShort'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ht	= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $sign * ($total_ht + (!empty($object->remise) ? $object->remise : 0)), $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				if (!empty($this->invert_bg_ht_ttc)) {
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
			}
			if ((empty($this->only_ht) && empty($this->only_ttc)) || !empty($this->show_ttc_vat_tot)) {
				// Show VAT by rates and total
				$tvaisnull	= !empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000']) ? true : false;
				if (!empty($this->hide_vat_ifnull) && !empty($tvaisnull)) {
					// Nothing to do
				} else {
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
								$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat	= $outputlangs->transcountrynoentities("TotalVAT", $this->emetteur->country_code).' ';
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
									$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat	= $outputlangs->transcountrynoentities("TotalLT1", $this->emetteur->country_code).' ';
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
									$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat	= $outputlangs->transcountrynoentities("TotalLT2", $this->emetteur->country_code).' ';
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
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('TotalTTCShort'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ttc	= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $sign * $total_ttc, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$this->resteapayer	= price2num((! $this->only_ht ? $total_ttc : $total_ht) - $this->paid - $this->credit_notes - $this->deposits, 'MT');
			if ($object->paye) {
				$this->resteapayer	= 0;
			}
			if (($this->paid > 0 || $this->credit_notes > 0 || $this->deposits > 0) && empty($this->no_payment_details)) {
				// Already paid + Deposits
				$index++;
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('Paid'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->paid + $this->deposits, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				// Credit note
				if ($this->credit_notes) {
					$index++;
					$labeltouse	= ($outputlangs->transnoentities("CreditNotesOrExcessReceived") != "CreditNotesOrExcessReceived") ? $outputlangs->transnoentities("CreditNotesOrExcessReceived") : $outputlangs->transnoentities("CreditNotes");
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $labeltouse, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->credit_notes, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				}
				// Escompte
				if ($object->close_code == FactureFournisseur::CLOSECODE_DISCOUNTVAT) {
					$index++;
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("EscompteOfferedShort"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc - $this->paid - $this->credit_notes - $this->deposits, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$this->resteapayer	= 0;
				}
				$index++;
				$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
				$pdf->SetFont('', 'B', $default_font_size - 1);
				!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities("RemainderToPay"), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->resteapayer, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			}
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
		*	Show payments table
		*
		*	@param		TCPDF				$pdf		 	Object PDF
		*	@param		FactureFournisseur	$object			Object to show
		*	@param		int					$posy		 	Position y in PDF
		*	@param		Translate			$outputlangs	Object langs for output
		*	@param		int					$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int									Position pour suite
		**/
		protected function _tableau_versements(&$pdf, $object, $posy, $outputlangs, $calculseul = 0) {

			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabver			= $posy + 1;
			$tabver_hl			= $this->tab_hl - 1;
			$pdf->SetFont('', '', $default_font_size - 3);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabver		= $this->larg_tabtotal;
			$larg_col1ver		= ($larg_tabver / 4) - 5;
			$larg_col2ver		= $larg_tabver / 4;
			$larg_col3ver		= ($larg_tabver / 4) + 5;
			$larg_col4ver		= $larg_tabver / 4;
			$posxtabver			= $this->posxtabtotal;
			$posxcol2ver		= $posxtabver + $larg_col1ver;
			$posxcol3ver		= $posxcol2ver + $larg_col2ver;
			$posxcol4ver		= $posxcol3ver + $larg_col3ver;
			$index				= 0;
			$title				= $outputlangs->transnoentities("PaymentsAlreadyDone");
			$pdf->MultiCell($larg_tabver, $tabver_hl, $title, '', 'L', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index));
			$pdf->SetFont('', '', $default_font_size - 4);
			$pdf->MultiCell($larg_col1ver, $tabver_hl, $outputlangs->transnoentities("Date"), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col2ver, $tabver_hl, $outputlangs->transnoentities("Amount"), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col3ver, $tabver_hl, $outputlangs->transnoentities("Type"), '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col4ver, $tabver_hl, $outputlangs->transnoentities("Num"), '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index));
			$pdf->SetFont('', '', $default_font_size - 4);
			// Loop on each payment
			$sql	= 'SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount,';
			$sql	.= ' pf.multicurrency_amount, cp.code';
			$sql	.= ' FROM '.$this->db->prefix().'paiementfourn_facturefourn as pf, '.$this->db->prefix().'paiementfourn as p';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'c_paiement as cp ON p.fk_paiement = cp.id AND cp.entity IN ('.getEntity("c_paiement").')';
			$sql	.= ' WHERE pf.fk_paiementfourn = p.rowid AND pf.fk_facturefourn = '.((int) $object->id);
			$sql	.= ' ORDER BY p.datep';
			$resql	= $this->db->query($sql);
			if ($resql) {
				$num	= $this->db->num_rows($resql);
				$i		= 0;
				while ($i < $num) {
					$row		= $this->db->fetch_object($resql);
					$MntLine	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $row->multicurrency_amount : $row->amount;
					$oper		= $outputlangs->transnoentitiesnoconv("PaymentTypeShort".$row->code);
					$pdf->MultiCell($larg_col1ver, $tabver_hl, dol_print_date($this->db->jdate($row->date),'day', false, $outputlangs, true), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2ver, $tabver_hl, pdf_InfraSPlus_price($object, $MntLine, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col3ver, $tabver_hl, $oper, '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col4ver, $tabver_hl, $row->num, '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
					$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
					$i++;
				}
			} else {
				$this->error	= $this->db->lasterror();
			}
			$posytabver	= $pdf->GetY() + 1;
			if (!empty($calculseul)) {
				$heightforver	= $posytabver - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforver;
			} else {
				$pdf->commitTransaction();
				return $posytabver;
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF				$pdf			The PDF factory
		*	@param		FactureFournisseur	$object			Object shown in PDF
		*	@param		Translate			$outputlangs	Object lang for output
		*	@param		int					$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int									Return height of bottom margin including footer text
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
