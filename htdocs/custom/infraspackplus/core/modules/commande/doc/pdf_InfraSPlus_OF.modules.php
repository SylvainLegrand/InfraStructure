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
	* 	\file		./infraspackplus/core/modules/commande/doc/pdf_InfraSPlus_OF.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF command
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
	require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_OF extends ModelePDFCommandes
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
		public $include_alias;
		public $with_picture;
		public $refcol;
		public $hide_discount;
		public $hide_cols;
		public $showwvccchk;
		public $signvalue;
		public $hideInnerLines;
		public $add_recap;
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
		public $nbrProdTot = 0;
		public $nbrProdDif = array();
		public $only_ht;
		public $larg_util_cadre;
		public $larg_util_txt;
		public $posx_G_txt;
		public $larg_desc;
		public $posxcol1;
		public $posxcol2;
		public $posxcol3;
		public $posxcol4;
		public $largcol1;
		public $largcol2;
		public $largcol3;
		public $largcol4;
		public $tableau = array();	// Array of table to print
		public $heightforfooter;
		public $larg_tabtotal;
		public $larg_tabinfo;
		public $posxtabtotal;
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $Sst;
		public $adrSst;
		public $show_2sign_area;
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

			$langs->loadLangs(array('main', 'errors', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusOFName');
			$this->description					= $langs->trans('PDFInfraSPlusOFDescription');
			$this->titlekey						= 'ManufacturingOrder';
			$this->defaulttemplate				= getDolGlobalString('COMMANDE_ADDON_PDF', '');
			$this->draft_watermark				= getDolGlobalString('COMMANDE_DRAFT_WATERMARK', '');
			$this->doli_addr_livr_recep			= getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0) && !empty($this->use_doli_addr_livr) ? getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0) : 0;
			$this->show_sign_area				= getDolGlobalInt('INFRASPLUS_PDF_COMMANDE_SHOW_SIGNATURE', 0);
			$this->show_ExtraFieldsLines		= getDolGlobalInt('INFRASPLUS_PDF_EXFL_C', 0);
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
		*	@param		object		$object				Object to generate
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
			$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_OF') ? '' : '_OF';
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
					$this->Sst					= !empty($hookmanager->resArray['Sst']) ? $hookmanager->resArray['Sst'] : '';
					$this->adrSst				= !empty($hookmanager->resArray['adrSst']) ? $hookmanager->resArray['adrSst'] : '';
					$this->listfreet			= !empty($hookmanager->resArray['listfreet']) ? $hookmanager->resArray['listfreet'] : '';
					$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
					$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
					$this->CGV					= !empty($hookmanager->resArray['cgv']) ? $hookmanager->resArray['cgv'] : '';
					$this->files				= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : '';
					$this->with_picture			= !empty($hookmanager->resArray['hidepict']) ? $hookmanager->resArray['hidepict'] : '';
					$this->refcol				= !empty($hookmanager->resArray['refcol']) ? $hookmanager->resArray['refcol'] : '';
					$hidedesc					= !empty($hookmanager->resArray['hidedesc']) ? $hookmanager->resArray['hidedesc'] : '';
					$this->hide_discount		= !empty($hookmanager->resArray['hidedisc']) ? $hookmanager->resArray['hidedisc'] : '';
					$this->hide_cols			= !empty($hookmanager->resArray['hidecols']) ? $hookmanager->resArray['hidecols'] : '';
					$this->showwvccchk			= !empty($hookmanager->resArray['showwvccchk']) ? $hookmanager->resArray['showwvccchk'] : '';
					$this->signvalue			= !empty($hookmanager->resArray['signvalue']) ? $hookmanager->resArray['signvalue'] : '';
					$this->hideInnerLines		= !empty($hookmanager->resArray['hideInnerLines']) ? $hookmanager->resArray['hideInnerLines'] : '';
					$hookmanager->resArray		= array();
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
					$pdf->SetSubject($outputlangs->transnoentities('PDFInfraSPlusOrderTitle'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('PDFInfraSPlusOrderTitle').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
					$pagenb					= 1;
					// Default PDF parameters
					$this->stdLineColor		= array(128, 128, 128);
					$this->stdLineStyle		= array('width'=>$this->stdLineW, 'dash'=>$this->stdLineDash, 'cap'=>$this->stdLineCap, 'color'=>$this->stdLineColor);
					$this->bgLineW			= $this->tblLineW; // épaisseur par défaut dans TCPDF = 0.2
					$this->bgLineColor		= $this->bg_color;
					$this->bgLineStyle		= array('width'=>$this->bgLineW, 'dash'=>$this->bgLineDash, 'cap'=>$this->bgLineCap, 'color'=>$this->bgLineColor);
					$this->tblLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>(!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
					$this->verLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->verLineColor);
					$this->horLineStyle		= array('width'=>$this->tblLineW, 'dash'=>$this->tblLineDash, 'cap'=>$this->tblLineCap, 'color'=>$this->horLineColor);
					$this->signLineStyle	= array('width'=>$this->signLineW, 'dash'=>$this->signLineDash, 'cap'=>$this->signLineCap, 'color'=>$this->signLineColor);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// First loop on each lines to prepare calculs and variables
					$isTitleToList			= 0;
					$isTitleToCondense		= 0;
					$subtotalRecap			= array();
					$descWorksHidden		= array();
					$lineToHide				= array();
					$realpatharray			= array();
					$listObjBib				= array();
					$listDescBib			= array();
					$objproduct					= new Product($this->db);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						$isOuvrage		= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modOuvrage');
						$isSubTotalLine	= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
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
						// Comportements spéciaux => ouvrage ou sous-total avec le contenu masqué, réduit ou condensé
						if (!empty($isTitleToList)) {
							$idParentTitle	= pdf_InfraSPlus_escapeEns($object, $i, -3);	// Ligne sous un titre / sous titre à afficher sous forme de liste
							if (!empty($idParentTitle) && $idParentTitle == $isTitleToList) {
								$lineToHide[]	= $object->lines[$i]->id;
								$desc			= (!empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : ''));
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
								$desc			= (!empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : ''));
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
									$desc			= (!empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : ''));
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
									$desc			= (!empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : ''));
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
									$listPhotos	= array();
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
					if (empty($this->hide_cols)) {
						// Largeur variable suivant la place restante
						$this->larg_desc	= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_unit); // Largeur variable suivant la place restante
					} else {
						$this->larg_desc	= $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					}
					$this->tableau	= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
											'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,		'posx' => 0),
											'qty'		=> array('col' => $this->num_qty,		'larg' => $this->larg_qty,		'posx' => 0),
											'unit'		=> array('col' => $this->num_unit,		'larg' => $this->larg_unit,		'posx' => 0),
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
						}
					}
					$this->posxcol1		= $this->marge_gauche;
					$this->posxcol2		= $this->posxcol1	+ $this->largcol1;
					$this->posxcol3		= $this->posxcol2	+ $this->largcol2;
					$this->posxcol4		= $this->posxcol3	+ $this->largcol3;
					foreach($this->tableau as $ncol => $ncol_array) {
						if ($ncol_array['col'] == 1) {
							$this->tableau[$ncol]['posx']	= $this->posxcol1;
						} elseif ($ncol_array['col'] == 2) {
							$this->tableau[$ncol]['posx']	= $this->posxcol2;
						} elseif ($ncol_array['col'] == 3) {
							$this->tableau[$ncol]['posx']	= $this->posxcol3;
						} elseif ($ncol_array['col'] == 4) {
							$this->tableau[$ncol]['posx']	= $this->posxcol4;
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
						$pdf->show_ttc_col	= 0;
						$pdf->heightline	= $this->tab_hl;
					}
					$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head					= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead			= $head['totalhead'];
					$hauteurcadre			= $head['hauteurcadre'];
					$tab_top				= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
					$tab_top_newpage		= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforheader		= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
					$ht_colinfo				= $this->_tableau_info($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$ht2_coltotal			= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 1, $this->horLineStyle) : 0;
					$ht_coltotal		= 0;
					if (!empty($this->show_sign_area)) {
						if ($ht2_coltotal > 3) {
							$ht_coltotal	= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 1);
						} else {
							$ht_coltotal	= $this->_signature_area($pdf, $object, $this->marge_haute, $outputlangs, 1, 0);
						}
					}
					$ht_coltotal			+= $ht2_coltotal;
					$ht_colinfo				+= $this->show_2sign_area ? $ht_coltotal : 0;
					$heightforinfotot		= $ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal;
					$heightforinfotot		+= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$this->heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					// Livraison
					$height_livr	= 0;
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
					if ($head['SsTshow']) {
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
						$height_adrs	+= $this->tab_hl;
					}
					$tab_top	+= $height_adrs;
					// Header informations after Address blocks
					$height_header_inf	= 0;
					if (!empty($this->header_after_addr)) {
						$tab_top	+= $this->space_headerafter;
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
						$txtC11		= $outputlangs->transnoentities($this->titlekey).' '.$outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
						if ($object->statut == 0) {
							$pdf->SetTextColor(128, 0, 0);
							$txtC11	.= ' - '.$outputlangs->transnoentities('NotValidated');
						}
						$largC11	= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
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
						$isOuvrage		= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modOuvrage');
						$isSubTotalLine	= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
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
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
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
							$nextimglinesize	= !empty($this->with_picture) && empty($this->picture_under) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i + 1]) : array();	// Define size of image if we need it
							if (empty($this->picture_under) && empty($this->picture_after) && isset($nextimglinesize['height'])) {
								$nextlinehight	= $nextimglinesize['height'] + ($this->linkpictureurl ? $this->tab_hl * 2 : $this->tab_hl);
							} else {
								$nextlinehight	= $this->tab_hl;
							}
							if (($curY + $this->tab_hl + $nextlinehight) > ($this->page_hauteur - $this->heightforfooter)) {	// There is no space left for next line + total + free text
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
						$colPicture		= $this->tableau['ref']['larg'] > 0 && $this->picture_in_ref ? 'ref' : 'desc';
						$imglinesize	= !empty($this->with_picture) ? pdf_InfraSPlus_getlineimgsize($this->tableau[$colPicture]['larg'], $realpatharray[$i]) : array();	// Define size of image if we need it
						$ht_url			= 0;
						if (!empty($imglinesize['width']) && !empty($imglinesize['height']) && $this->linkpictureurl) {
							$txturl	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $this->linkpictureurl);
							$ht_url	= $pdf->getStringHeight($this->tableau[$colPicture]['larg'], $txturl);
						}
						// Hauteur de la référence
						$this->heightline	= $this->tab_hl;
						if (empty($this->hide_cols)) {
							// Reference
							if (!empty($this->refcol) || !empty($this->show_num_col)) {
								$pdf->startTransaction();
								$startline			= $pdf->GetY();
								$ref				= $isSubTitle || $isSubTotal ? '' : (!empty($this->refcol) ? pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails) : $i + 1);
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
							$extraDet			.= empty($extrafieldslines) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$extrafieldslines.'<hr style = "width: 80%;">';
							// Extrafield product / service
							$isProd				= $objproduct->fetch($object->lines[$i]->fk_product);
							$extrafieldsprod	= pdf_InfraSPlus_ExtraFieldsProd($objproduct, $this->exfltxtcolor, $outputlangs);
							$extraDet			.= empty($extrafieldsprod) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$extrafieldsprod.'<hr style = "width: 80%;">';
							// Custom values (weight, volume and code
							$WVCC				= '';
							if (!empty($this->showwvccchk)) {
								$WVCC	= pdf_InfraSPlus_getlinewvdcc($object, $i, $outputlangs, $this->emetteur);
							}
							$extraDet	.= empty($WVCC) ? '' : (empty($extraDet) ? '<hr style = "width: 80%;">' : '').$WVCC.'<hr style = "width: 80%;">';
						} else {	// Ouvrage or sub-total line
							$idOuvrage	= !empty($object->lines[$i]->array_options['options_fk_ouvrage']) ? $object->lines[$i]->array_options['options_fk_ouvrage'] : '';
							if (!empty($descWorksHidden[$object->lines[$i]->id]['desc'])) {
								$extraDet	= $descWorksHidden[$object->lines[$i]->id]['desc'].($descWorksHidden[$object->lines[$i]->id]['descType'] == 3 ? '</ul>' : '');
							}
						}
						// Description of product line => printing
						$pageposdesc	= $pdf->getPage();
						$hide_desc		= !empty($hidedesc) ? $hidedesc : (!empty($object->lines[$i]->fk_product) && $this->only_one_desc ? (in_array($object->lines[$i]->fk_product, $listDescBib) ? 1 : 0) : 0);
						pdf_InfraSPlus_writelinedesc($pdf, $object, $i, $outputlangs, $this->formatpage, $this->horLineStyle, $this->tableau['desc']['larg'], $this->heightline, $this->tableau['desc']['posx'], $curY, $hideref, $hide_desc, 0, $extraDet, null, $this->desc_full_line, 0, $this->with_picture, $realpatharray, $imglinesize, $this->linkpictureurl, $this->tab_hl, $ht_url, $this->picture_padding);
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
								}
							} else {
								$showpricebeforepagebreak	= 0;
							}
						} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblignes - (empty($nbChildren) && !empty($nbSubTotal) ? $nbSubTotal : $nbChildren) - 1)) {	// No more lines, and no space left to show total, so we create a new page
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
						if (empty($this->hide_cols)) {
							// Reference
							if ((!empty($this->refcol) || !empty($this->show_num_col)) && (empty($this->picture_in_ref) || (!empty($this->picture_in_ref) && empty($this->picture_replace_ref)))) {
								$pagepos	= $pdf->getPage();
								$pdf->writeHTMLCell($this->tableau['ref']['larg'], $this->heightline, $this->tableau['ref']['posx'], $curY, $ref, 0, 1, false, true, $this->force_align_left_ref, true);
								$pdf->setPage($pagepos);
							}
							// Quantity
							if (empty($this->hide_qty)) {
								$qty	= pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
								$pdf->MultiCell($this->tableau['qty']['larg'], $this->heightline, $qty, '', 'R', 0, 1, $this->tableau['qty']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
							}
							// Unit
							if (!empty($this->product_use_unit)) {
								$unit	= pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
								$pdf->writeHTMLCell($this->tableau['unit']['larg'], $this->heightline, $this->tableau['unit']['posx'], $curY, $unit, 0, 1, false, true, $this->force_align_left_unit, true);
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
							if ($pagenb == 1) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} else {
								$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							}
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
							// Save auto-break content so watermark goes behind it (z-order fix)
							$savedContent = method_exists($pdf, 'liftPageContent') ? $pdf->liftPageContent() : '';
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
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
						if (isset($object->lines[$i + $nbChildren + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							$this->heightforfooter	= $this->_pagefoot($pdf, $object, $outputlangs, 0);
							if ($pagenb == 1) {
								$this->_tableau($pdf, $object, $tab_top, $this->page_hauteur - $tab_top - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							} else {
								$this->_tableau($pdf, $object, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $this->heightforfooter, $outputlangs, $this->hide_top_table, 1, $pagenb);
							}
							// New page
							$pdf->AddPage();
							pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
							$pagenb++;
							if (empty($this->small_head2)) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							} else {
								$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
							}
							// Restore grayscale FillColor after _pagehead to keep ColorFlag true
							$pdf->SetFillColor(255);
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
							$nexY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
						}
					}
					$bottomlasttab	= $this->page_hauteur - $heightforinfotot - $this->heightforfooter - 1;
					if ($pagenb == 1) {
						$this->_tableau($pdf, $object, $tab_top, $bottomlasttab - $tab_top, $outputlangs, $this->hide_top_table, 1, $pagenb);
					} else {
						$this->_tableau($pdf, $object, $tab_top_newpage, $bottomlasttab - $tab_top_newpage, $outputlangs, $this->hide_top_table, 0, $pagenb);
					}
					$posyinfo		= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$posyfreetext	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posyinfo, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $bottomlasttab;
					if (!empty($this->show_sign_area)) {
						if ($ht2_coltotal > 3) {
							$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 1);
						} else {
							$posysignarea	= $this->_signature_area($pdf, $object, $posyfreetext, $outputlangs, 0, 0);
						}
					}
					$posy	= $posyinfo > $posysignarea ? $posyinfo : $posysignarea;
					$posy	= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posy;
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
		*	@param		object		$object			Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		array		$hauteurhead	'totalhead'		= hight of header
		*											'hauteurcadre	= hight of frame
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{
			global $conf, $hookmanager;

			$specialHead	= infraspackplus_fetchAllSpecialHeads(array($object->element));
			if (!empty($specialHead['rootFileName'])) {
				$specialhead	= 'pdf_'.$specialHead['rootFileName'].'_pagehead';
				$hauteurhead	= $specialhead($pdf, $object, $showaddress, $outputlangs, $this->headertxtcolor, $this->header_align_left, $this->decal_round, $this->formatpage, $this->logo, $this->emetteur, $this->tab_hl,
												$this->header_after_addr, $this->title_size, $this->titlekey, $this->ref_from_cust, $this->datesbold, $this->dates_br, $this->show_num_cli, $this->num_cli_frm,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, $this->add_creator_in_header, $this->use_iso_location, $this->adr, $this->typeadr, $this->adrlivr, $this->Rounded_rect,
												$this->customerAddrSelect, $this->Sst, $this->adrSst, '', 0, array(), '', -2, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
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
				$pdf->SetTextColor($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]);
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
					if (!empty($object->fin_validite)) {
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
			if (!empty($showaddress)) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', (!empty($this->doli_addr_livr_recep) ? 'SHIPPING' : 'CUSTOMER')),
										'L' => (empty($this->doli_addr_livr_recep) ? $object->getIdContact('external', 'SHIPPING') : '')
										);
				$addresses		= array();
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', 0, 0, $this->Sst, $this->adrSst, $this->customerAddrSelect, $this->include_alias);
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
		*	@param		object		$object		Object to show
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
		*	@param		TCPDF			$pdf			Object PDF
		*	@param		object		$object		Object to show
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
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
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
			if ($object->statut == Commande::STATUS_DRAFT && (!empty($this->draft_watermark))) {
				if (empty($hidetop)) {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + $this->ht_top_table + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				} else {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				}
				$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			}
			// Show Folder mark
			if (!empty($this->fold_mark)) {
				$pdf->Line(0, ($this->page_hauteur)/3, $this->fold_mark, ($this->page_hauteur)/3, $this->stdLineStyle);
				$pdf->Line($this->page_largeur - $this->fold_mark, ($this->page_hauteur)/3, $this->page_largeur, ($this->page_hauteur)/3, $this->stdLineStyle);
			}
			if (!$this->hide_cols && $this->showverline && empty($this->desc_full_line)) {
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
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
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
				}
			}
		}

		/**
		*	Show miscellaneous information (payment mode, payment term, ...)
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		object		$object			Object to show
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
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$larg_tabinfo		= $this->larg_tabinfo;
			$larg_col1info		= 46;
			$larg_col2info		= $larg_tabinfo - $larg_col1info;
			$posxtabinfo		= $this->marge_gauche;
			$posxcol2info		= $posxtabinfo + $larg_col1info;
			// Show Qty of products and number of different product for the document
			if (!empty($this->show_qty_prod_tot) && $this->nbrProdTot > 0) {
				$pdf->SetFont('', '', $default_font_size - 2);
				$nbrProd		= $outputlangs->transnoentities('PDFInfraSPlusQtyProd', $this->nbrProdTot, count($this->nbrProdDif));
				$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $nbrProd, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
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
			if (!empty($this->e_signing) && !isModEnabled('uptosign') && empty($this->show_2sign_area)) {
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
		*	@param		object		$object		Object invoice
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@return		int							Position pour suite
		**/
		protected function _signature_area(&$pdf, $object, $posy, $outputlangs, $calculseul = 0, $freetext = 0)
		{
			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$signarea_top		= $posy + 1 + (!empty($this->e_signing) && isModEnabled('uptosign') && !empty($this->show_2sign_area) ? 10 : 0);	// si UpToSign et 2 cadres on décale les cadres vers le bas pour le STAMP
			$signarea_top		= $posy + 1;
			$posxsignarea1		= $this->marge_gauche;
			$posxsignarea2		= $this->posxtabtotal;
			$larg_signarea		= $this->larg_tabtotal;
			$signarea_hl		= $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $object->thirdparty->name));
			$signarea_hl2		= $this->show_2sign_area ? $pdf->getStringHeight($larg_signarea, $outputlangs->transnoentities('ContactNameAndSignature', $this->adrSst->name)) : 0;
			$signarea_hl		= $signarea_hl < $signarea_hl2 ? ($signarea_hl2 < $this->tab_hl ? $this->tab_hl : $signarea_hl2) : ($signarea_hl < $this->tab_hl ? $this->tab_hl : $signarea_hl);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (!empty($freetext)) {
				$pdf->Line($posxsignarea1, $posy, $this->page_largeur - $this->marge_droite, $posy, $this->horLineStyle);
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
			if (!empty($this->e_signing) && isModEnabled('uptosign')) {
				$this->posystamp	= $posy + 1;
			}
			if (!empty($calculseul)) {
				$heightforarea	= ($signarea_top + $signarea_hl + $this->ht_signarea + 1 + (!empty($this->e_signing) && isModEnabled('uptosign') && !empty($this->show_2sign_area) ? 10 : 0)) - $posy;
				$pdf->rollbackTransaction(true);
				return $heightforarea;
			} else {
				// Signature électronique
				if (!empty($this->e_signing)) {
					if (!isModEnabled('uptosign')) {
						if (!empty($this->show_2sign_area)) {
							$pdf->addEmptySignatureAppearance($posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						}
						$pdf->addEmptySignatureAppearance($posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
					} else {
						if (!empty($this->show_2sign_area)) {
							pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'internal', $posxsignarea1, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
						}
						pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posxsignarea2, $signarea_top + $signarea_hl, $larg_signarea, $this->ht_signarea);
					}
				}
				$pdf->commitTransaction();
				return $signarea_top + $signarea_hl + $this->ht_signarea + 1 + (!empty($this->e_signing) && isModEnabled('uptosign') && !empty($this->show_2sign_area) ? 10 : 0);	// si UpToSign et 2 cadres on décale les cadres vers le bas pour le STAMP
			}
		}

		/**
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		Societe		$fromcompany	Object company
		*	@param		int			$marge_basse	Margin bottom we use for the autobreak
		*	@param		int			$marge_gauche	Margin left
		*	@param		int			$page_hauteur	Page height
		*	@param		object		$object			Object shown in PDF
		*	@param		int			$showdetails	Show company details into footer
		*	@param		int			$hidesupline	Completly hide the line up to footer (for some edition with only table)
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
