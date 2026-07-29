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
	* 	\file		./infraspackplus/core/modules/facture/doc/pdf_InfraSPlus_F.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF invoice
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	include_once DOL_DOCUMENT_ROOT.'/expedition/class/'.(version_compare(DOL_VERSION, '15.0.0', '>=') ? 'expeditionlinebatch' : 'expeditionbatch').'.class.php';
	include_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	if (isModEnabled('infras2bridge')) {
		dol_include_once('/infras2bridge/class/infras2bridge_paymentlinks.class.php');
	}
	// For retrocompatibility Dolibarr < 21.0
	if (floatval(DOL_VERSION) < 21.0 && (!function_exists('getDolGlobalFloat') || !function_exists('getDolGlobalBool'))) {
		dol_include_once('/infraspackplus/backport/v21/core/lib/functions.lib.php');
	}

	/************************************************
	*	Class to generate PDF invoice InfraS
	************************************************/
	class pdf_InfraSPlus_F extends ModelePDFFactures
	{
		const MIN_GAP_BEFORE_FOOTER	= 0.75;	// Espace mini garanti entre le bas du dernier bloc (colonne infos/bank ou zone de signature) et la ligne de separation du pied de page (~2px a 96dpi)

		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $titlekeyAV;
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
		public $typeadr;
		public $sign = 1;
		public $emetteur;
		public $paid;
		public $paid_loc_cur;
		public $credit_notes;
		public $credit_notes_loc_cur;
		public $deposits;
		public $deposits_loc_cur;
		public $atleastonediscount;
		public $tvaProd = [];
		public $localtax1Prod = [];
		public $localtax2Prod = [];
		public $htProd = [];
		public $tvaServ = [];
		public $localtax1Serv = [];
		public $localtax2Serv = [];
		public $htServ = [];
		public $no_payment_table;
		public $positive_credit_note;
		public $show_pointoftax_date;
		public $show_link_online_pay;
		public $zatca_qr_code;
		public $swiss_qr_code;
		public $dateduetxtcolor;
		public $title_if_deposit;
		public $ht_by_vat_p_s;
		public $show_serial_from_shipping;
		public $use_situ_total_2;
		public $tva_mention_mode;
		public $tva_mode;
		public $diffsizetitle;
		public $diffsizecontent;
		public $add_belgian_structured_code;
		public $Belgian_qr_code;
		public $Pay_inLine;
		public $Pay_inLine_QR;
		public $deposits_at_end;
		public $use_Pay_Spec;
		public $use_magickword;
		public $showLCR;
		public $factor_auto;
		public $factor_pre;
		public $tva;
		public $tva_array;
		public $localtax1;
		public $localtax2;
		public $credit_note;
		public $listPaySpec	= [];
		public $atleastoneratenotnull;
		public $situationinvoice;
		public $prev_ht = 0;
		public $prev_ttc = 0;
		public $previnvoices = [];
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
		public $categoryOfOperation	= -1;	// @var int Category of operation	// unknown by default
		public $formatpage;
		public $use_iso_location;
		public $arrayidcontact;
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
		public $tableHeaderBefore;
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
		public $qrcodestring;
		public $adrfact;
		public $sizeBC = 25;
		public $styleBC = [];
		public $resteapayer;
		public $resteapayer_loc_cur;
		public $raw_prices;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'errors', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'payment', 'paybox', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusInvoiceName');
			$this->description					= $langs->trans('PDFInfraSPlusInvoiceDescription');
			$this->defaulttemplate				= getDolGlobalString('FACTURE_ADDON_PDF', '');
			$this->no_payment_table				= $this->no_payment_details;
			$this->positive_credit_note			= getDolGlobalInt('INVOICE_POSITIVE_CREDIT_NOTE', 0);
			$this->show_pointoftax_date			= getDolGlobalInt('INVOICE_POINTOFTAX_DATE', 0);
			$this->show_link_online_pay			= getDolGlobalInt('PDF_SHOW_LINK_TO_ONLINE_PAYMENT', 0);
			$this->zatca_qr_code				= getDolGlobalInt('INVOICE_ADD_ZATCA_QR_CODE', 0);
			$this->swiss_qr_code				= getDolGlobalInt('INVOICE_ADD_SWISS_QR_CODE', 0);
			$this->draft_watermark				= getDolGlobalString('FACTURE_DRAFT_WATERMARK', '');
			$this->dateduetxtcolor				= getDolGlobalString('INFRASPLUS_PDF_FACT_DATEDUE_COLOR', '0,0,0');
			$this->dateduetxtcolor				= explode(',', $this->dateduetxtcolor);
			$this->title_if_deposit				= getDolGlobalString('INFRASPLUS_PDF_INVOICE_TITLE_IF_DEPOSIT', '');
			$this->ht_by_vat_p_s				= getDolGlobalInt('INFRASPLUS_PDF_HT_BY_VAT_P_OR_S', 0);
			$this->show_serial_from_shipping	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_SERIAL_ON_INVOICE', 0);
			$this->use_situ_total_2				= getDolGlobalInt('INFRASPLUS_PDF_USE_SITU_TOTAL_2', 0);
			$this->tva_mention_mode				= getDolGlobalInt('INFRASPLUS_PDF_MENTION_TVA_MODE', 1);
			$this->tva_mode						= getDolGlobalInt('TAX_MODE', 0);	// 0 => exigible sur encaissements, 1 => exigible sur débits, 2 => exigible sur paiements
			$this->use_tva_forfait				= getDolGlobalInt('INFRASPLUS_PDF_USE_TVA_FORFAIT', 0);
			$this->tva_forfait					= getDolGlobalFloat('INFRASPLUS_PDF_TVA_FORFAIT', 0);
			$this->diffsizetitle				= getDolGlobalInt('PDF_DIFFSIZE_TITLE', 3);
			$this->diffsizecontent				= getDolGlobalInt('PDF_DIFFSIZE_CONTENT', 4);
			$this->add_belgian_structured_code	= getDolGlobalInt('INFRASPLUS_PDF_INVOICE_ADD_BELGIAN_STRUCTURED_CODE', 0);
			$this->Belgian_qr_code				= getDolGlobalInt('INFRASPLUS_PDF_INVOICE_ADD_BELGIAN_QR_CODE', 0);
			$this->Pay_inLine					= getDolGlobalInt('INFRASPLUS_PDF_PAY_INLINE', 0);
			$this->Pay_inLine_QR				= getDolGlobalInt('INFRASPLUS_PDF_PAY_INLINE_QR_CODE', 0);
			$this->deposits_at_end				= getDolGlobalInt('INFRASPLUS_PDF_DEPOSITS_AT_END', 0);
			$this->use_Pay_Spec					= getDolGlobalInt('INFRASPLUS_PDF_USE_PAY_SPEC', 0);
			$this->use_magickword				= getDolGlobalInt('INFRASPLUS_PDF_USE_MAGICKSTAMPWORD_ON_INVOICE', 1);
			$this->showLCR						= getDolGlobalInt('INFRASPLUS_PDF_SHOW_LCR', 0);
			$this->show_ExtraFieldsLines		= getDolGlobalInt('INFRASPLUS_PDF_EXFL_F', 0);
			$this->factor_auto					= getDolGlobalInt('INFRASPLUS_PDF_FREETEXT_FACTOR_AUTO', 0);
			$this->factor_pre					= getDolGlobalString('INFRASPLUS_PDF_FACTOR_PRE', '');
			$this->option_logo					= 1;	// Display logo
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
		*	@param		Facture		$object				Object to generate
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
			$outputlangs->loadLangs(array('main', 'errors', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			if ($object->type == 2 && !empty($this->credit_note)) {
				$this->sign	= -1;
			}
			$filesufixe		= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_F') ? '' : '_F';
			$baseDir		= !empty($conf->facture->multidir_output[$conf->entity]) ? $conf->facture->multidir_output[$conf->entity] : $conf->facture->dir_output;
			$this->titlekey	= 'Bill';
			if (!empty($object->situation_cycle_ref)) {
				$this->titlekey		= $object->type == 2 ? 'InvoiceAvoir'		: 'PDFSituationTitle';
				$this->titlekeyAV	= $object->type == 2 ? 'PDFSituationTitle'	: '';
			} else {
				if ($object->type == 1) {
					$this->titlekey	= 'InvoiceReplacement';
				} elseif ($object->type == 2) {
					$this->titlekey	= 'InvoiceAvoir';
				} elseif ($object->type == 3) {
					$this->titlekey	= 'InvoiceDeposit';
				} elseif ($object->type == 4) {
					$this->titlekey	= 'InvoiceProForma';
				}
			}
			if (!empty($baseDir)) {
				$object->fetch_thirdparty();
				// Use of multicurrency for this document
				$this->use_multicurrency	= (isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? 1 : 0;
				$this->paid					= $object->getSommePaiement($this->use_multicurrency ? 1 : 0);
				$this->paid_loc_cur			= $object->getSommePaiement(0);
				$this->credit_notes			= $object->getSumCreditNotesUsed($this->use_multicurrency ? 1 : 0);	// Warning, this also include excess received
				$this->credit_notes_loc_cur	= $object->getSumCreditNotesUsed(0);								// Warning, this also include excess received
				$this->deposits				= $object->getSumDepositsUsed($this->use_multicurrency ? 1 : 0);
				$this->deposits_loc_cur		= $object->getSumDepositsUsed(0);
				if ($this->paid && $this->use_Pay_Spec) {
					$sql	= 'SELECT p.fk_paiement, cp.code, cp.type, pf.amount, pf.multicurrency_amount';
					$sql	.= ' FROM '.$this->db->prefix().'paiement_facture as pf, '.$this->db->prefix().'paiement as p';
					$sql	.= ' LEFT JOIN '.$this->db->prefix().'c_paiement as cp ON p.fk_paiement = cp.id';
					$sql	.= ' WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = '.((int) $object->id).' AND cp.entity IN ('.getEntity('c_paiement').')';
					$sql	.= ' ORDER BY p.datep';
					$resql	= $this->db->query($sql);
					if ($resql) {
						$num				= $this->db->num_rows($resql);
						$nbPaySpec			= 0;
						for ($i = 0 ; $i < $num ; $i++) {
							$row	= $this->db->fetch_object($resql);
							if ($row->type == 3) {
								$nbPaySpec ++;
								$this->listPaySpec[$row->code]['amount']				+= $row->amount;
								$this->listPaySpec[$row->code]['multicurrency_amount']	+= $row->multicurrency_amount;
							}
						}
						if ($nbPaySpec != 0 && $nbPaySpec == $num && !$this->deposits) {
							$this->no_payment_table = 1;
						}
						$this->db->free($resql);
					}
				}
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
					$this->adrfact				= !empty($hookmanager->resArray['adrfact']) ? $hookmanager->resArray['adrfact'] : '';
					$this->showwvccchk			= !empty($hookmanager->resArray['showwvccchk']) ? $hookmanager->resArray['showwvccchk'] : '';
					$this->show_tot_disc		= !empty($hookmanager->resArray['showtotdisc']) ? $hookmanager->resArray['showtotdisc'] : '';
					$this->show_vir				= !empty($hookmanager->resArray['showvir']) ? $hookmanager->resArray['showvir'] : '';
					$this->show_tva_btp			= !empty($hookmanager->resArray['showtvabtp']) ? $hookmanager->resArray['showtvabtp'] : '';
					$this->hideInnerLines		= !empty($hookmanager->resArray['hideInnerLines']) ? $hookmanager->resArray['hideInnerLines'] : '';
					$this->add_recap			= !empty($hookmanager->resArray['subtotal_add_recap']) ? $hookmanager->resArray['subtotal_add_recap'] : (!empty($hookmanager->resArray['infrastructure_add_recap']) ? $hookmanager->resArray['infrastructure_add_recap'] : '');
					$hookmanager->resArray		= [];
					if (!empty($this->usentascover)) {
						$this->first_page_empty	= 1;	// Comme on veut une page de garde on créé une page vide en premier puis on insère la note prévue sur celle-ci
					}
					// Si on affiche une colonne 'Référence' on s'assure de ne pas répéter l'information Sauf si on utilise les prix par client et que l'otion d'affichage des références client est sur 1
					$hideref	= empty($this->refcol) || (getDolGlobalInt('PRODUIT_CUSTOMER_PRICES', 0) && getDolGlobalInt('PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE', 0) == 1) ? 0 : 1;
					if (!empty($this->factor_auto) && !empty($this->factor_pre) && ($object->fk_account > 0 || $object->fk_bank > 0 || !empty($this->rib_num))) {
							$bankid	= ($object->fk_account <= 0 ? $this->rib_num : $object->fk_account);
							if ($object->fk_bank > 0) {
								$bankid	= $object->fk_bank;	// For backward compatibility when object->fk_account is forced with object->fk_bank
							}
							$account		= new Account($this->db);
							$account->fetch($bankid);
							$factorFreeT	= 'INVOICE_FREE_TEXT_'.$this->factor_pre.$account->ref;
							if (getDolGlobalString($factorFreeT, '')) {
								$this->listfreet[]	= $factorFreeT;
							}
					}
					$nblignes			= count($object->lines);	// Set nblignes with the new facture lines content after hook
					$nbpayments			= count($object->getListOfPayments());
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
					$pdf->SetSubject($outputlangs->transnoentities('Bill'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Bill').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
					$watermarkedPages		= array($pdf->getPage() => true);
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
					$this->styleBC			= array('position'		=> '',
													'border'		=> false,
													'hpadding'		=> '0',
													'vpadding'		=> '0',
													'fgcolor'		=> array($this->headertxtcolor[0], $this->headertxtcolor[1], $this->headertxtcolor[2]),
													'bgcolor'		=> false,	// array(255,255,255)
													'module_width'	=> 1,		// width of a single module in points
													'module_height'	=> 1		// height of a single module in points
													);
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					$this->qrcodestring		= '';
					if (!empty($this->zatca_qr_code)) {
						$this->qrcodestring	= pdf_InfraSPlus_buildZATCAQRString($object);
					} elseif (!empty($this->swiss_qr_code)) {
						$this->qrcodestring	= pdf_InfraSPlus_buildSwitzerlandQRString($object);
					}
					// Situation invoice
					if (!empty($object->situation_cycle_ref)) {
						$this->situationinvoice	= True;
						$this->prev_ht			= 0;
						$this->prev_ttc			= 0;
						$this->previnvoices		= count($object->tab_previous_situation_invoice) ? $object->tab_previous_situation_invoice : [];
						if (count($this->previnvoices)) {
							foreach ($this->previnvoices as $invoice) {
								$invoice_ht		= $this->use_multicurrency ? $invoice->multicurrency_total_ht : $invoice->total_ht;
								$this->prev_ht	+= $invoice_ht;
								$invoice_ttc	= $this->use_multicurrency ? $invoice->multicurrency_total_ttc : $invoice->total_ttc;
								$this->prev_ttc	+= $invoice_ttc;
							}
						}
					}
					// First loop on each lines to prepare calculs and variables
					$isTitleToList			= 0;
					$isTitleToCondense		= 0;
					$subtotalRecap			= [];
					$descWorksHidden		= [];
					$lineToHide				= [];
					$realpatharray			= [];
					$pricesObjProd			= [];
					$listObjBib				= [];
					$listDescBib			= [];
					$objproduct				= new Product($this->db);
					$discount				= new DiscountAbsolute($this->db);
					for ($i = 0 ; $i < $nblignes ; $i++) {
						// deposits
						$isDiscount	= 0;
						if (!empty($this->deposits_at_end) && $object->lines[$i]->total_ht < 0 && !empty($object->lines[$i]->fk_remise_except)) {
							$discount->fetch($object->lines[$i]->fk_remise_except);
							if ($discount->fk_facture_source > 0) {
								// On ne créé qu'une ligne par facture source (gestion des avoirs avec plusieurs TVAs qui génèrent une ligne de remise par TVA)
								$txtDeposit	= $outputlangs->transnoentitiesnoconv('Deposit').' '.(!empty($discount->discount_type) ? $discount->ref_invoice_supplier_source : $discount->ref_facture_source);
								if (! isset($this->lines_deposits[$discount->fk_facture_source])) {
									$this->lines_deposits[$discount->fk_facture_source]	= array('txtDeposit'				=> $txtDeposit,
																								'total_ht'					=> 0,
																								'multicurrency_total_ht'	=> 0,
																								'total_ttc'					=> 0,
																								'multicurrency_total_ttc'	=> 0
																								);
								}
								$this->lines_deposits[$discount->fk_facture_source]['total_ht']					+= $object->lines[$i]->total_ht;
								$this->lines_deposits[$discount->fk_facture_source]['multicurrency_total_ht']	+= $object->lines[$i]->multicurrency_total_ht;
								$this->lines_deposits[$discount->fk_facture_source]['total_ttc']				+= $object->lines[$i]->total_ttc;
								$this->lines_deposits[$discount->fk_facture_source]['multicurrency_total_ttc']	+= $object->lines[$i]->multicurrency_total_ttc;
								$lineToHide[]																	= $object->lines[$i]->id;
								if (!empty($object->situation_cycle_ref)) {
									$isDiscount	= 1;
								}
							}
						}
						$isOuvrage		= isModEnabled('ouvrage') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modOuvrage');
						$isSubTotalLine	= isModEnabled('subtotal') && infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
						$isProd			= !empty($object->lines[$i]->fk_product) && empty($isOuvrage) && empty($isSubTotalLine) ? $objproduct->fetch($object->lines[$i]->fk_product) : 0;
						// Test des options Sous-total
						if (!empty($isSubTotalLine)) {	// ATM lines
							if ($object->lines[$i]->qty < 10) {	// Sous-titres ATM
								if (!empty($object->lines[$i]->info_bits) && $object->lines[$i]->info_bits > 0) {	// Avec saut de page demandé avant
									// Pour afficher l'en-tête du tableau juste avant un titre spécifique
									if (!empty($object->lines[$i]->array_options['options_show_table_header_before']) && $object->lines[$i]->array_options['options_show_table_header_before'] > 0) {
										$this->tableHeaderBefore	= $i;
									}
								}
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
						// Position $this->atleastonediscount if you have at least one discount
						// line discount
						if (!empty($object->lines[$i]->remise_percent)) {
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
						// global calculated discount (type InfraSDiscount)
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
						// Automatic discount or display of raw prices
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
						// Collection of totals by VAT value
						if (empty($isDiscount) && empty($isOuvrage) && empty($isSubTotalLine)) {
							$prev_progress	= $object->lines[$i]->get_prev_progress($object->id);
							$coef_progress	= $prev_progress > 0 && $this->use_situ_total_2 && !empty($object->lines[$i]->situation_percent) ? ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent : 1;
							$tvaligne		= $this->sign * ($this->use_multicurrency ? doubleval($object->lines[$i]->multicurrency_total_tva) : doubleval($object->lines[$i]->total_tva)) * $coef_progress;
							$htligne		= $this->sign * ($this->use_multicurrency ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht) * $coef_progress;
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
							if (empty($this->ht_by_vat_p_s)) {	// Collecte des totaux par valeur de tva
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
							} else {	// Collection of totals by product or service by VAT value (including HT)
								$vatindex	= $vatrate.$localtax1_type.$localtax1ligne.$localtax2_type.$localtax2ligne;
								if ($object->lines[$i]->product_type == 0) {	// Products
									// retrieve global local tax
									if (!empty($localtax1_type) && $localtax1ligne != 0) {
										if (! isset($this->localtax1Prod[$vatindex]['type'])) {
											$this->localtax1Prod[$vatindex]['type']	= $localtax1_type;
										}
										if (! isset($this->localtax1Prod[$vatindex]['rate'])) {
											$this->localtax1Prod[$vatindex]['rate']	= $localtax1_rate;
										}
										if (! isset($this->localtax1Prod[$vatindex]['mnt'])) {
											$this->localtax1Prod[$vatindex]['mnt']	= 0;
										}
										$this->localtax1Prod[$vatindex]['mnt']	+= $localtax1ligne;
									}
									if (!empty($localtax2_type) && $localtax2ligne != 0) {
										if (! isset($this->localtax2Prod[$vatindex]['type'])) {
											$this->localtax2Prod[$vatindex]['type']	= $localtax2_type;
										}
										if (! isset($this->localtax2Prod[$vatindex]['rate'])) {
											$this->localtax2Prod[$vatindex]['rate']	= $localtax2_rate;
										}
										if (! isset($this->localtax2Prod[$vatindex]['mnt'])) {
											$this->localtax2Prod[$vatindex]['mnt']	= 0;
										}
										$this->localtax2Prod[$vatindex]['mnt']	+= $localtax2ligne;
									}
									if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
										$vatrate	.= '*';
									}
									if (! isset($this->tvaProd[$vatindex]['rate'])) {
										$this->tvaProd[$vatindex]['rate']	= $vatrate;
									}
									if (! isset($this->tvaProd[$vatindex]['mnt'])) {
										$this->tvaProd[$vatindex]['mnt']	= 0;
									}
									$this->tvaProd[$vatindex]['mnt']	+= $tvaligne;
									// Segregation of totals HT products according to applied VAT rates
									$this->htProd[$vatindex]['vatrate']	= $outputlangs->transcountrynoentities("VAT", $this->emetteur->country_code).' '.vatrate(abs($vatrate), 1);
									if (! isset($this->htProd[$vatindex]['mnt'])) {
										$this->htProd[$vatindex]['mnt']	= 0;
									}
									$this->htProd[$vatindex]['mnt']	+= $htligne;
								} else if ($object->lines[$i]->product_type == 1) {	// Services
									// retrieve global local tax
									if (!empty($localtax1_type) && $localtax1ligne != 0) {
										if (! isset($this->localtax1Serv[$vatindex]['type'])) {
											$this->localtax1Serv[$vatindex]['type']	= $localtax1_type;
										}
										if (! isset($this->localtax1Serv[$vatindex]['rate'])) {
											$this->localtax1Serv[$vatindex]['rate']	= $localtax1_rate;
										}
										if (! isset($this->localtax1Serv[$vatindex]['mnt'])) {
											$this->localtax1Serv[$vatindex]['mnt']	= 0;
										}
										$this->localtax1Serv[$vatindex]['mnt']	+= $localtax1ligne;
									}
									if (!empty($localtax2_type) && $localtax2ligne != 0) {
										if (! isset($this->localtax2Serv[$vatindex]['type'])) {
											$this->localtax2Serv[$vatindex]['type']	= $localtax2_type;
										}
										if (! isset($this->localtax2Serv[$vatindex]['rate'])) {
											$this->localtax2Serv[$vatindex]['rate']	= $localtax2_rate;
										}
										if (! isset($this->localtax2Serv[$vatindex]['mnt'])) {
											$this->localtax2Serv[$vatindex]['mnt']	= 0;
										}
										$this->localtax2Serv[$vatindex]['mnt']	+= $localtax2ligne;
									}
									if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
										$vatrate	.= '*';
									}
									if (! isset($this->tvaServ[$vatindex]['rate'])) {
										$this->tvaServ[$vatindex]['rate']	= $vatrate;
									}
									if (! isset($this->tvaServ[$vatindex]['mnt'])) {
										$this->tvaServ[$vatindex]['mnt']	= 0;
									}
									$this->tvaServ[$vatindex]['mnt']	+= $tvaligne;
									// Segregation of totals HT products according to applied VAT rates
									$this->htServ[$vatindex]['vatrate']	= $outputlangs->transcountrynoentities("VAT", $this->emetteur->country_code).' '.vatrate(abs($vatrate), 1);
									if (! isset($this->htServ[$vatindex]['mnt'])) {
										$this->htServ[$vatindex]['mnt']	= 0;
									}
									$this->htServ[$vatindex]['mnt']	+= $htligne;
								}
							}
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
							$realpatharray[$i]	= pdf_InfraSPlus_getLineProductImage($this->db, $objproduct, $object->lines[$i], $this->old_path_photo, $this->cat_hq_image, $this->only_one_picture, $listObjBib);
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
					// determine category of operation
					$this->categoryOfOperation	= !empty($this->hasProduct) && !empty($this->hasService) ? 2 : (empty($this->hasProduct) && !empty($this->hasService) ? 1 : 0);	// mixed products and services | only service | only product
					// Define width and position of notes frames
					$this->larg_util_cadre		= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->larg_util_txt		= $this->larg_util_cadre - (($this->Rounded_rect * 2) + 2);
					$this->posx_G_txt			= $this->marge_gauche + $this->Rounded_rect + 1;
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
					if (! $this->situationinvoice) {
						$this->larg_progress	= 0;
					}
					if (empty($this->show_ttc_col)) {
						$this->larg_totalttc	= 0;
					}
					// Largeur variable suivant la place restante
					$this->larg_desc	= $this->larg_util_cadre - ($this->larg_ref + $this->larg_qty + $this->larg_unit + $this->larg_up + $this->larg_tva + $this->larg_discount + $this->larg_updisc + $this->larg_progress + $this->larg_totalht + $this->larg_totalttc); // Largeur variable suivant la place restante
					$this->tableau		= array('ref'		=> array('col' => $this->num_ref,		'larg' => $this->larg_ref,		'posx' => 0),
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
					$ht_coltotal		= 0;
					if (($this->paid || $this->credit_notes || $this->deposits) && empty($this->no_payment_table)) {
						$ht_coltotal	= $this->_tableau_versements($pdf, $object, $this->marge_haute, $outputlangs, 1);
						$ht2_coltotal	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					} else {
						$ht2_coltotal	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 1, $this->horLineStyle) : 0;
					}
					$ht_coltotal			+= $ht1_coltotal + $ht2_coltotal;
					$heightforinfotot		= $ht_colinfo > $ht_coltotal ? $ht_colinfo : $ht_coltotal;
					$heightforinfotot		+= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle) : 0;
					$heightforinfotot		+= self::MIN_GAP_BEFORE_FOOTER;
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
							$watermarkedPages[$pdf->getPage()] = true;
							$pdf->setPage(2);
							$tab_top	= $tab_top_newpage;
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
						if (!empty($this->situationinvoice)) {
							$title	= $outputlangs->transnoentities($this->titlekey, $object->situation_counter).(!empty($this->titlekeyAV) ? ' ('.$outputlangs->transnoentities($this->titlekeyAV, $object->situation_counter).')' : '');
						} else {
							$title	= $outputlangs->transnoentities($this->titlekey).((!empty($this->deposits) || !empty($this->lines_deposits)) && !empty($this->title_if_deposit) ? ' '.$outputlangs->transnoentities($this->title_if_deposit) : '');
						}
						$txtC11		= $this->_refInvoice($pdf, $object, $outputlangs);
						$largC11	= $pdf->GetStringWidth($txtC11, '', '', $default_font_size - 1) + 3;
						$pdf->MultiCell($largC11, $this->tab_hl, $txtC11, 0, 'L', 0, 0, $this->posx_G_txt, $tab_top, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
						$txtC12		= $outputlangs->transnoentities('DateInvoice').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
						$txtC12b	= '';
						if (!empty($this->show_pointoftax_date)) {
							$txtC12b	= $outputlangs->transnoentities('DatePointOfTax').' : '.dol_print_date($object->date_pointoftax, 'day', false, $outputlangs, true);
						}
						if ($object->type != 2) {
							$txtC12b	= $outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_lim_reglement, 'day', false, $outputlangs, true);
						}
						$largC12	= $this->larg_util_txt - $largC11;
						$xC12		= $this->posx_G_txt + $this->larg_util_txt - $largC12;
						$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 1);
						$pdf->MultiCell($largC12, $this->tab_hl, $txtC12.(empty($this->dates_br) ? ' / '.$txtC12b : ''), 0, 'R', 0, 0, $xC12, $tab_top, true, 0, 0, false, 0, 'M', false);
						if (!empty($this->dates_br)) {
							if ($object->type != 2) {
								$pdf->SetTextColor((int) $this->dateduetxtcolor[0], (int) $this->dateduetxtcolor[1], (int) $this->dateduetxtcolor[2]);
							}
							$pdf->MultiCell($largC12, $this->tab_hl, $txtC12b, 0, 'R', 0, 0, $xC12, $tab_top + $this->tab_hl, true, 0, 0, false, 0, 'M', false);
							if ($object->type != 2) {
								$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
							}
						}
						$pdf->SetFont('', '', $default_font_size - 1);
						$nexY	= $tab_top + ($this->tab_hl * (!empty($this->dates_br) ? 2 : 1)) + 1;
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
					// Préparation ajout du numéro de série depuis le module natif, s'il existe...
					if (isModEnabled('productbatch') && !empty($this->show_serial_from_shipping)) {
						$object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 1);
					}
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
							// Ajout du numéro de série, s'il existe...
							// Depuis le module externe Equipement (Patas-Monkey)
							$serialEquip	= isModEnabled('equipement') ? pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, $i, 'facture') : '';
							// Depuis le module natif
							if (!empty($object->linkedObjectsIds['shipping'])) {
								foreach ($object->linkedObjects['shipping'] as $j => $objectsipping) {
									foreach ($objectsipping->lines as $lineshipping) {
										if ($object->lines[$i]->fk_product == $lineshipping->fk_product) {
											if ($lineshipping->detail_batch) {
												foreach($lineshipping->detail_batch as $detail) {
													if ($detail->batch) {
														$serialEquip	.= !empty($serialEquip) ? '<br/>' : '';
														$serialEquip	.= $outputlangs->transnoentitiesnoconv('printBatch', $detail->batch).' - '.$outputlangs->transnoentitiesnoconv('printQty', $detail->qty);
													}
												}
											}
										}
									}
								}
							}
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
						// Reference
						if ((!empty($this->refcol) || !empty($this->show_num_col)) && (empty($this->picture_in_ref) || !empty($this->picture_in_ref) && empty($this->picture_replace_ref))) {
							$pagepos	= $pdf->getPage();
							infraspackplus_applyInfrastructureOlPdfStyle($pdf, $object, $i);
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
						// Situation progress
						if (!empty($this->situationinvoice)) {
							$progress	= pdf_InfraSPlus_getlineprogress($object, $i, $outputlangs, $hidedetails);
							$pdf->MultiCell($this->tableau['progress']['larg'], $this->heightline, $progress, '', 'R', 0, 1, $this->tableau['progress']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
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
						if($this->show_ttc_col) {
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
					$posyinfo	= $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$posytot	= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					if (($this->paid || $this->credit_notes || $this->deposits) && empty($this->no_payment_table)) {
						$posytot		= $this->_tableau_versements($pdf, $object, $posytot, $outputlangs, 0);
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						$posyfreetext	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posytot;
					} else {
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						$posyfreetext	= empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->posxtabtotal, $posytot, $outputlangs, $this->emetteur, $this->listfreet, (!empty($this->number_words) ? 1 : 0), 0, $this->horLineStyle) : $posytot;
					}
					$posy	= $posyinfo > $posyfreetext ? $posyinfo : $posyfreetext;
					$posy	= !empty($this->free_text_end) ? pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posy, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle) : $posy;
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if ($object->mode_reglement_code == 'LCR' && $this->showLCR) {
						// New page for LCR
						$pdf->AddPage();
						pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
						$watermarkedPages[$pdf->getPage()] = true;
						$pagenb++;
						if (empty($this->small_head2)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						} else {
							$this->_pagesmallhead($pdf, $object, 0, $outputlangs);
						}
						$pdf->SetFillColor(255);
						$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
						$this->_lcr($pdf, $object, $tab_top_newpage, $outputlangs);
						$this->_pagefoot($pdf, $object, $outputlangs, 0);
					}
					// Cas INFRASTRUCTURE_PDF_TITLE_WITH_TOTAL : les sous-totaux ont été retirés de $object->lines par Infrastructure — reconstruction depuis le contexte
					if (!empty($this->add_recap)) {
						pdf_InfraSPlus_subtotal_getrecap_from_context($object, $subtotalRecap);
					}
					if (!empty($this->add_recap) && count($subtotalRecap) > 0) {	// SubTotal module with recap option
						$subtotalRecap	= pdf_InfraSPlus_compare($subtotalRecap, 'rang');
						$pdf->AddPage();	// New page for review
						pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
						$watermarkedPages[$pdf->getPage()] = true;
						$pagenb++;
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
												$this->header_after_addr, $this->title_size, $this->titlekey, $this->ref_from_cust, $this->datesbold, $this->dates_br, $this->show_num_cli, $this->num_cli_frm,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, $this->add_creator_in_header, $this->use_iso_location, $this->adr, $this->typeadr, $this->adrlivr, $this->Rounded_rect,
												$this->customerAddrSelect, -2, -2, $this->qrcodestring, $this->deposits, $this->lines_deposits, $this->title_if_deposit, $this->adrfact, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
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
				if (!empty($this->qrcodestring)) {
					$pdf->write2DBarcode($this->qrcodestring, 'QRCODE,M', $posx + 2, $posy, $this->sizeBC, $this->sizeBC, $this->styleBC, 'N');
				}
				$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
				if (!empty($this->situationinvoice)) {
					$title	= $outputlangs->transnoentities($this->titlekey, $object->situation_counter).(!empty($this->titlekeyAV) ? ' ('.$outputlangs->transnoentities($this->titlekeyAV, $object->situation_counter).')' : '');
				} else {
					$title	= $outputlangs->transnoentities($this->titlekey).((!empty($this->deposits) || !empty($this->lines_deposits)) && !empty($this->title_if_deposit) ? ' '.$outputlangs->transnoentities($this->title_if_deposit) : '');
				}
				$pdf->MultiCell($w - $this->sizeBC - 3, $this->tab_hl * $this->title_size, $title, '', $align, 0, 1, $posx + $this->sizeBC + 3, $posy, true, 0, 0, false, 0, 'M', false);
				$posy	= $pdf->getY();
				$pdf->SetFont('', 'B', $default_font_size - 1);
				$txtref	= $this->_refInvoice($pdf, $object, $outputlangs, $w);
				$refArr	= explode('<br>', $txtref);
				foreach ($refArr as $ref) {
					$pdf->MultiCell($w, $this->tab_hl, $ref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
					$posy += $this->tab_hl; // add line
				}
				$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
				$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
				$txtdt	= $outputlangs->transnoentities('DateInvoice').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
				if (empty($this->dates_br)) {
					if (!empty($this->show_pointoftax_date)) {
						$txtdt	.= ' / '.$outputlangs->transnoentities('DatePointOfTax').' : '.dol_print_date($object->date_pointoftax, 'day', false, $outputlangs, true);
					}
					if ($object->type != 2) {
						$txtdt	.= ' / '.$outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_lim_reglement, 'day', false, $outputlangs, true);
					}
				}
				$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				if (!empty($this->dates_br)) {
					if (!empty($this->show_pointoftax_date)) {
						$txtdt	= '';
						$posy	+= $this->tab_hl - 0.5;
						$txtdt	= $outputlangs->transnoentities('DatePointOfTax').' : '.dol_print_date($object->date_pointoftax, 'day', false, $outputlangs, true);
						$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
					}
					if ($object->type != 2) {
						$txtdt	= '';
						$posy	+= $this->tab_hl - 0.5;
						$pdf->SetTextColor((int) $this->dateduetxtcolor[0], (int) $this->dateduetxtcolor[1], (int) $this->dateduetxtcolor[2]);
						$txtdt	= $outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_lim_reglement, 'day', false, $outputlangs, true);
						$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
						$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
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
			$dimCadres['Y']			= ($this->use_iso_location && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			$this->arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
											'E' => $object->getIdContact('external', 'BILLING'),
											'L' => $object->getIdContact('external', 'SHIPPING')
											);
			if (!empty($showaddress)) {
				$addresses		= [];
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $this->arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', $this->adrfact, 0, -2, -2, $this->customerAddrSelect, $this->include_alias);
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
		*	Set invoice reference.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Facture		$object			Object to show
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		int			$cellWidth		[=0] Cell width to add break lines or O not to use it
		*	@return		string						Reference to show
		**/
		protected function _refInvoice(&$pdf, $object, $outputlangs, $cellWidth = 0)
		{
			$txtref1	= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
			$txtsep		= ' / ';
			$txtref2	= '';
			if ($object->statut == Facture::STATUS_DRAFT) {
				$pdf->SetTextColor(128, 0, 0);
				$txtsep		= ' - ';
				$txtref2	= $outputlangs->transnoentities('NotValidated');
			}
			$objidnext	= $object->getIdReplacingInvoice('validated');
			if ($object->type == 0 && $objidnext) {
				$orep		= new Facture($this->db);
				$orep->fetch($objidnext);
				$txtref2	= $outputlangs->transnoentities('ReplacementByInvoice').' : '.$outputlangs->convToOutputCharset($orep->ref);
			}
			if ($object->type == 1) {
				$orep		= new Facture($this->db);
				$orep->fetch($object->fk_facture_source);
				$txtref2	= $outputlangs->transnoentities('ReplacementInvoice').' : '.$outputlangs->convToOutputCharset($orep->ref);
			}
			if ($object->type == 2 && !empty($object->fk_facture_source)) {
				$orep		= new Facture($this->db);
				$orep->fetch($object->fk_facture_source);
				$txtref2	= $outputlangs->transnoentities('CorrectionInvoice').' : '.$outputlangs->convToOutputCharset($orep->ref);
			}
			$txtref	= $txtref1;
			if (!empty($txtref2)) {
				$txtref	.= $txtsep.$txtref2;
			}
			if ($cellWidth > 0) {	// compare text and a little more (security) with cell width
				if ($pdf->GetStringWidth($txtref) + 2 >= $cellWidth) {
					$txtref	= $txtref1.'<br>'.$txtref2; // add break line
				}
			}
			return $txtref;
		}

		/**
		*	Show top small header of page.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Facture		$object			Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		void
		**/
		protected function _pagesmallhead(&$pdf, $object, $showaddress, $outputlangs)
		{
			$fromcompany	= $this->emetteur;
			if (!empty($this->situationinvoice)) {
				$title	= $outputlangs->transnoentities($this->titlekey, $object->situation_counter).(!empty($this->titlekeyAV) ? ' ('.$outputlangs->transnoentities($this->titlekeyAV, $object->situation_counter).')' : '');
			} else {
				$title	= $outputlangs->transnoentities($this->titlekey).((!empty($this->deposits) || !empty($this->lines_deposits)) && !empty($this->title_if_deposit) ? ' '.$outputlangs->transnoentities($this->title_if_deposit) : '');
			}
			pdf_InfraSPlus_pagesmallhead($pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $this->formatpage, $this->decal_round, $this->logo, $this->headertxtcolor);
		}

		/**
		*	Show table for lines
		*
		*	@param		TCPDF			$pdf		Object PDF
		*	@param		Facture		$object			Object to show
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
					// Show category of operations
					if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 1 && $this->categoryOfOperation >= 0) {
						$infoCatOpe = $outputlangs->transnoentities('PDFInfraSPlusCatOpe').' : '.$outputlangs->transnoentities('PDFInfraSPlusCatOpe'.$this->categoryOfOperation);
						$pdf->MultiCell($pdf->GetStringWidth($infoCatOpe) + 3, 2, $infoCatOpe, '', 'L', 0, 1, $this->marge_gauche + $this->decal_round, $tab_top - $this->tab_hl, true, 0, 0, false, 0, 'M', false);
					}
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
			if ($object->statut == Facture::STATUS_DRAFT && (!empty($this->draft_watermark))) {
				if (empty($hidetop)) {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + $this->ht_top_table + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				} else {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->draft_watermark, $tab_top + ($tab_height / 2), $this->larg_util_cadre, $this->page_hauteur, 'mm');
				}
				$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			}
			if ($object->paye && $this->paid_watermark) {
				if (empty($hidetop)) {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->paid_watermark, $tab_top + $this->ht_top_table + ($tab_height / 2), $this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->page_hauteur, 'mm');
				} else {
					pdf_InfraSPlus_watermark($pdf, $outputlangs, $this->paid_watermark, $tab_top + ($tab_height / 2), $this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->page_hauteur, 'mm');
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
				if (!empty($this->situationinvoice)) {
					$pdf->MultiCell($this->tableau['progress']['larg'], $this->ht_top_table, $outputlangs->transnoentities('PDFInfraSPlusAvancement'), '', 'C', 0, 1, $this->tableau['progress']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
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

		/**
		*	Show miscellaneous information (payment mode, payment term, ...)
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Facture		$object			Object to show
		*	@param		int			$posy			Y
		*	@param		Translate	$outputlangs	Langs object
		*	@return		int			$posy			Position pour suite
		**/
		protected function _tableau_info(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			global $conf;

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
			// VAT statements
			// Show if Option VAT debit option is on also if transmitter is french	// Decret n°2099-1299 2022-10-07
			if (!empty($this->tva_mention_mode) && $this->emetteur->country_code == 'FR') {
				$pdf->SetFont('', '', $default_font_size - 2);
				$txt_tva_mode	= $outputlangs->transnoentities(($this->tva_mode == 1 ? 'InfraSPlusParamMentionTvaDebits' : ($this->tva_mode == 2 ? '' : 'InfraSPlusParamMentionTvaEncaissement')));
				$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $txt_tva_mode, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo = $pdf->GetY() + 1;
			}
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
			// Show category of operations
			if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 2 && $this->categoryOfOperation >= 0) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre			= $outputlangs->transnoentities('PDFInfraSPlusCatOpe').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$infoCatOpe		= $outputlangs->transnoentities('PDFInfraSPlusCatOpe'.$this->categoryOfOperation);
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $infoCatOpe, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo	= $pdf->GetY() + 1;
			}
			// Show Outstandings
			if (!empty($this->show_outstandings)) {
				include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre				= $outputlangs->transnoentities('CurrentOutstandingBill').' : ';
				$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$pdf->SetFont('', '', $default_font_size - 2);
				$outstandingBills	= $object->thirdparty->getOutstandingBills();
				$outstandingAmount	= pdf_InfraSPlus_price($object, $outstandingBills['opened'], $outputlangs, 0, 0, 'T');
				$pdf->MultiCell($larg_col2info, $tabinfo_hl, $outstandingAmount, '', 'L', 0, 1, $posxcol2info, $posytabinfo, true, 0, 0, false, 0, 'M', false);
				$posytabinfo		= $pdf->GetY() + 1;
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
			// Show payments conditions
			if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$titre								= $outputlangs->transnoentities('PaymentConditions').' : ';
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
			if ($object->type != 2) {
				// Check if a payment mode is defined
				if (empty($object->mode_reglement_code) && !getDolGlobalInt('FACTURE_CHQ_NUMBER') && !getDolGlobalInt('FACTURE_RIB_NUMBER')) {
					$this->error = $outputlangs->transnoentities('ErrorNoPaiementModeConfigured');
				} elseif (($object->mode_reglement_code == 'CHQ' && !getDolGlobalInt('FACTURE_CHQ_NUMBER') && empty($object->fk_account) && empty($object->fk_bank))
				|| ($object->mode_reglement_code == 'VIR' && !getDolGlobalInt('FACTURE_RIB_NUMBER') && empty($object->fk_account) && empty($object->fk_bank))) {
					// Avoid having any valid PDF with setup that is not complete on invoices settings or on this invoice
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$this->error	= $outputlangs->transnoentities('ErrorPaymentModeDefinedToWithoutSetup', $object->mode_reglement_code);
					$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $this->error, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$posytabinfo	= $pdf->GetY() + 1;
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
						$pdf->SetFont('', 'B', $default_font_size - $this->diffsizetitle);
						if ($this->chq_num > 0) {
							$account		= new Account($this->db);
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
							$pdf->SetFont('', '', $default_font_size - $this->diffsizetitle);
							$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $outputlangs->convToOutputCharset($owner_address), '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
						}
						$posytabinfo	= $pdf->GetY() + 1;
					}
				}
				// If payment mode not forced or forced to VIR, show payment with BAN
				if (!empty($this->show_vir) && (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR' || ($object->mode_reglement_code == 'CB' && $this->IBAN_with_CB) || $this->IBAN_All)) {
					if ($object->fk_account > 0 || $object->fk_bank > 0 || !empty($this->rib_num)) {
						$bankid	= ($object->fk_account <= 0 ? $this->rib_num : $object->fk_account);
						if ($object->fk_bank > 0) {
							$bankid	= $object->fk_bank;	// For backward compatibility when object->fk_account is forced with object->fk_bank
						}
						$account		= new Account($this->db);
						$account->fetch($bankid);
						$pdf->SetLineStyle($this->stdLineStyle);
						$posytabinfo	= pdf_infrasplus_bank($pdf, $outputlangs, $posxtabinfo, $posytabinfo, $larg_tabinfo, $tabinfo_hl - 1, $account, $this->bank_only_number, $default_font_size);
						$posytabinfo	+= 1;
					}
				}
				// If payment mode forced to VIR, show payment with QR code and/or Link
				if (isModEnabled('infras2bridge') && $object->mode_reglement_code == 'VIR' && $object->statut == Facture::STATUS_VALIDATED) {
					$paymentLink	= new infras2bridge_paymentlinks($this->db);
					$links			= $paymentLink->get_status_from_ref(dol_sanitizeFileName($object->ref));
					if (is_array($links)) {
						foreach ($links as $link) {
							if ($link['status'] == 'valid') {
								if (getDolGlobalString('INFRASPLUS_PDF_BRIDGE_DISPLAY_PAYMENT_LINK')) {
									$pdf->SetFont('', 'B', $default_font_size - 2);
									$titre	= $outputlangs->transnoentities('PDFInfraSPlusTransferCreationLink').' : ';
									$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo - 2, true, 0, 0, false, 0, 'N', false);
									$pdf->SetFont('', '', $default_font_size - 2);
									$linktopay	= '<a href="'.$link['link'].'" title="'.$outputlangs->transnoentities('ClickHere').'" style="text-decoration:none">'.$outputlangs->transnoentities('PDFInfraSPlusBridgeLink').'</a>';
									$pdf->writeHTMLCell($larg_col2info, $tabinfo_hl, $posxcol2info, $posytabinfo - 2, dol_htmlentitiesbr($linktopay), 0, 1,false,true, 'N',true);
								}
								// Bridge QR Code (si valid + conf active)
								if (getDolGlobalString('INFRASPLUS_PDF_BRIDGE_DISPLAY_PAYMENT_LINK')) {
									$pdf->write2DBarcode($link['link'], 'QRCODE,M', $posxtabinfo, $posytabinfo + 5, $this->sizeBC, $this->sizeBC, $this->styleBC, 'N');
									$posytabinfo	= $pdf->GetY() + 1;
								}
							}
						}
					}
				}
				// Show belgium structured code
				if (!empty($this->add_belgian_structured_code)) {
					$pdf->SetFont('', '', $default_font_size - 2);
					$titre				= $outputlangs->transnoentities('PDFInfraSPlusBelgianStructuredCode').' : ';
					$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $titre, 0, 'L', false, 1, $posxtabinfo, $posytabinfo, true, 0, false, false, 0, 'M', false);
					$posytabinfo		= $pdf->GetY();
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$code_structured	= pdf_InfraSPlus_buildBelgiumStructuredCode($object);
					$pdf->MultiCell($larg_tabinfo, $tabinfo_hl, $code_structured, 0, 'L', false, 1, $posxtabinfo, $posytabinfo, true, 0, false, false, 0, 'M', false);
					$posytabinfo		= $pdf->GetY() + 1;
				}
				// Show QR code for Belgian invoices or inline payment => if the invoice isn't already paid !
				if ($object->statut == Facture::STATUS_VALIDATED) {
					$useonlinepayment	= ((isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox')) && !empty($this->show_link_online_pay));
					$onlinepaymentmode	= ($object->mode_reglement_code == 'CB' || $object->mode_reglement_code == 'VAD' || $object->mode_reglement_code == $this->Pay_inLine);
					if (!empty($this->Belgian_qr_code)) {
						$this->qrcodestring	= pdf_InfraSPlus_buildBelgiumQRString($object);
						if (!empty($this->qrcodestring)) {
							$pdf->write2DBarcode($this->qrcodestring, 'QRCODE,M', $posxtabinfo + 5, $posytabinfo + 6, $this->sizeBC, $this->sizeBC, $this->styleBC, 'N');
						}
						$posytabinfo	= $pdf->GetY() + 1;
					} elseif (empty($object->paye) && ((empty($object->mode_reglement_code) || $onlinepaymentmode) && $useonlinepayment)) {
						$pdf->SetFont('', 'B', $default_font_size - 2);
						$titre			= $outputlangs->transnoentities('PDFInfraSPlusURLPayment').' : ';
						$pdf->MultiCell($larg_col1info, $tabinfo_hl, $titre, '', 'L', 0, 1, $posxtabinfo, $posytabinfo, true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 2);
						$paiement_url	= getOnlinePaymentUrl(0, 'invoice', $object->ref, 0, '', 1);
						$linktopay		= '<a href = "'.$paiement_url.'" title = "'.$outputlangs->transnoentities('ClickHere').'">'.$outputlangs->transnoentities('PDFInfraSPlusURLlink').'</a>';
						$pdf->writeHTMLCell($larg_col2info, $tabinfo_hl, $posxcol2info, $posytabinfo, dol_htmlentitiesbr($linktopay), 0, 1);
						if (!empty($this->Pay_inLine_QR)) {
							$pdf->write2DBarcode($paiement_url, 'QRCODE,M', $posxtabinfo + 5, $posytabinfo + 6, $this->sizeBC, $this->sizeBC, $this->styleBC, 'N');
						}
						$posytabinfo	= $pdf->GetY() + 1;
					}
				}
			}
			if (isModEnabled('uptosign') && !empty($this->use_magickword)) {
				$signarea_top		= $posytabinfo + 1;
				$posxsignarea		= $this->posxtabtotal;
				$larg_signarea		= $this->larg_tabtotal;
				$signarea_hl		= $this->tab_hl;
				$this->posystamp	= $posytabinfo;
				// Position naturelle de la cellule signature client : juste sous le bloc info.
				// Si elle dépasserait le bas de page, on la remonte au plus tard pour qu'elle tienne sur la page courante (évite l'auto-break TCPDF). La cellule étant invisible (SetAlpha 0), l'éventuel chevauchement avec le bloc info est sans impact visuel.
				$sig_y				= $signarea_top + $signarea_hl;
				$sig_y_max			= $this->page_hauteur - $this->heightforfooter - $this->ht_signarea;
				if ($sig_y > $sig_y_max) {
					$sig_y			= $sig_y_max;
				}
				pdf_InfraSPlus_add_e_signature($pdf, $object, $this, 'customer', $posxsignarea, $sig_y, $larg_signarea, $this->ht_signarea);
				$posytabinfo		+= 10;	// espace requis pour le scellement
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
		*	@param		Facture		$object			Object to show
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@return		int							Position pour suite
		**/
		protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			global $conf;

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
			// Table for totals
			$larg_tabtotal					= $this->larg_tabtotal;
			$larg_col2total					= !empty($this->show_ttc_col) && $this->num_totalttc > $this->num_totalht ? $this->larg_totalttc : $this->larg_totalht;
			$larg_col1total					= $larg_tabtotal - $larg_col2total;
			$posxtabtotal					= $this->posxtabtotal;
			$posxcol2total					= $this->posxtabtotal + $larg_col1total;
			$index							= 0;
			$this->atleastoneratenotnull	= 0;
			// Total Calculations
			if (empty($this->only_ttc) && empty($this->ht_by_vat_p_s)) {
				if (empty($this->use_tva_forfait)) {	// calculate standard HT
					$actual_ht			= $this->use_multicurrency ? $object->multicurrency_total_ht : $object->total_ht;
					$actual_ht_loc_cur	= $object->total_ht;
					$total_ht			= $actual_ht + ($this->situationinvoice ? $this->prev_ht : 0) + (!empty($object->remise) ? $object->remise : 0);
				} else {	// calculate HT with Flat rate VAT (like Swiss)
					$actual_ht			= ($this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc) / (1 + (abs($this->tva_forfait) / 100));
					$actual_ht_loc_cur	= $object->total_ttc / (1 + (abs($this->tva_forfait) / 100));
					$total_ht			= $actual_ht + ($this->situationinvoice ? $this->prev_ttc / (1 + (abs($this->tva_forfait) / 100)) : 0) + (!empty($object->remise) ? $object->remise : 0);
				}
			}
			if (empty($this->only_ht)) {	// Calculation of Total TTC
				$actual_ttc			= $this->use_multicurrency ? $object->multicurrency_total_ttc : $object->total_ttc;
				$actual_ttc_loc_cur	= $object->total_ttc;
				$total_ttc			= $actual_ttc + ($this->situationinvoice ? $this->prev_ttc : 0);
			}
			// Inclusion of total discounts excluding VAT in the table
			if (!empty($this->show_disc_tot)) {
				$totRemHT	= $this->use_multicurrency ? $this->TotRem['multicurrency_HT'] : $this->TotRem['HT'];
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusRawTotalHTShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ht + (!empty($object->remise) ? $object->remise : 0) + $totRemHT, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$index++;
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotRemHT').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $totRemHT, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$index++;
			}
			// Deposits
			if (!empty($this->deposits_at_end) && !empty($this->lines_deposits)) {
				// HT before Deposits
				if (empty($this->only_ttc) && empty($this->ht_by_vat_p_s) && empty($object->situation_cycle_ref)) {
					$beforeDepositsHT	=	$actual_ht;
					foreach ($this->lines_deposits as $line_deposit) {
						$beforeDepositsHT	-= $this->use_multicurrency ? $line_deposit['multicurrency_total_ht'] : $line_deposit['total_ht'];
					}
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$txtBeforeDepositHT	= $outputlangs->transnoentitiesnoconv(!empty($this->show_ttc_col) ? 'PDFInfraSPlusBeforeDeposit' : 'PDFInfraSPlusBeforeDepositHT');
					$pdf->MultiCell(!empty($this->show_ttc_col) ? $larg_col1total - $this->larg_totalttc : $larg_col1total, $tabtot_hl, $txtBeforeDepositHT, '', 'L', 0, 1, $posxtabtotal, $posytabtot, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell(!empty($this->show_ttc_col) ? $this->larg_totalht : $larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $beforeDepositsHT, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, !empty($this->show_ttc_col) ? $this->tableau['totalht']['posx'] : $posxcol2total, $posytabtot, true, 0, 0, false, 0, 'M', false);
					if (empty($this->show_ttc_col)) {
						$index++;
					}
				}
				// TTC before Deposits
				if (empty($this->only_ht) && empty($object->situation_cycle_ref)) {
					$beforeDepositsTTC	=	$actual_ttc;
					foreach ($this->lines_deposits as $line_deposit) {
						$beforeDepositsTTC	-= $this->use_multicurrency ? $line_deposit['multicurrency_total_ttc'] : $line_deposit['total_ttc'];
					}
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					if (empty($this->show_ttc_col)) {
						$txtBeforeDepositTTC	= $outputlangs->transnoentitiesnoconv('PDFInfraSPlusBeforeDepositTTC');
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $txtBeforeDepositTTC, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
					$pdf->MultiCell(!empty($this->show_ttc_col) ? $this->larg_totalttc : $larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $beforeDepositsTTC, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, !empty($this->show_ttc_col) ? $this->tableau['totalttc']['posx'] : $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
				}
				// Deposits lines
				$pdf->SetFont('', '', $default_font_size - 1);
				$total_line_deposit	= 0;
				foreach ($this->lines_deposits as $line_deposit) {
					$line_deposit_ht	= $this->use_multicurrency ? $line_deposit['multicurrency_total_ht'] : $line_deposit['total_ht'];
					$line_deposit_ttc	= $this->use_multicurrency ? $line_deposit['multicurrency_total_ttc'] : $line_deposit['total_ttc'];
					if (empty($this->only_ttc) && empty($this->ht_by_vat_p_s) && empty($object->situation_cycle_ref)) {
						$total_line_deposit	= $line_deposit_ht;	// HT before Deposits
					}
					if (empty($this->only_ht) && empty($object->situation_cycle_ref)) {
						$total_line_deposit	= $line_deposit_ttc;	// TTC before Deposits
					}
					$pdf->MultiCell(!empty($this->show_ttc_col) ? $larg_col1total - $this->larg_totalttc : $larg_col1total, $tabtot_hl, $line_deposit['txtDeposit'], '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell(!empty($this->show_ttc_col) ? $this->larg_totalht : $larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, (empty($this->show_ttc_col) ? $total_line_deposit : $line_deposit_ht), $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, !empty($this->show_ttc_col) ? $this->tableau['totalht']['posx'] : $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					if (!empty($this->show_ttc_col)) {
						$pdf->MultiCell($this->larg_totalttc, $tabtot_hl, pdf_InfraSPlus_price($object, $line_deposit_ttc, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
					$index++;
				}
				$index++;
			}
			// HT
			if ((empty($this->only_ttc) || !empty($this->invert_bg_ht_ttc)) && empty($this->ht_by_vat_p_s)) {
				if (!empty($this->only_ht)) {	// line wrapping
					$pdf->RoundedRect($posxtabtotal, $posytabtot, $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
				$txtHT	= $outputlangs->transnoentities(!empty($this->situationinvoice) && count($this->previnvoices) ? 'PDFInfraSPlusCumulSituation' : 'TotalHTShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : '');
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $txtHT, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $total_ht, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				if (!empty($this->invert_bg_ht_ttc)) {
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
				if (!empty($this->situationinvoice) && count($this->previnvoices) && (!empty($this->use_situ_total_2) || !empty($this->only_ht))) {	// Situation invoice
					foreach ($this->previnvoices as $invoice) {
						if ($invoice instanceof Facture && $invoice->situation_counter > 0) {
							$index++;
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
							$pdf->SetAlpha($this->alpha);
							$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
							$pdf->SetAlpha(1);
							$invoiceref	= $outputlangs->transnoentities('InvoiceSituation').$outputlangs->convToOutputCharset(" n°".$invoice->situation_counter);
							$pdf->MultiCell($larg_col1total, $tabtot_hl, $invoiceref, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							$invoice_ht	= $this->use_multicurrency ? $invoice->multicurrency_total_ht : $invoice->total_ht;
							$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $invoice_ht, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						}
					}
					$index++;
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$actualref	= $outputlangs->transnoentities('InvoiceSituation').$outputlangs->convToOutputCharset(" n°".$object->situation_counter);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $actualref, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $actual_ht, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
				}
			}
			// VAT and local taxes
			if (((empty($this->only_ht) && empty($this->only_ttc)) || !empty($this->show_ttc_vat_tot)) && empty($this->use_tva_forfait)) {
				// Show VAT by rates and total
				$tvaisnull	= !empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000']) ? true : false;
				if (!empty($this->hide_vat_ifnull) && !empty($tvaisnull)) {
					// Nothing to do
				} else if (empty($this->ht_by_vat_p_s)) {
					//Local tax 1 before VAT
					foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('1', '3', '5'))) {
							continue;
						}
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0) {	//We do not display rate 0
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
							if ($tvakey != 0) {	// We do not display rate 0
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
					// Situations totals migth be wrong on huge amounts
					if ($object->situation_cycle_ref && $object->situation_counter > 1) {
						$sum_pdf_tva = 0;
						foreach ($this->tva_array as $tvakey => $tvaval) {
							$sum_pdf_tva	+= $tvaval['amount']; // sum VAT amounts to compare to object
						}
						if ($sum_pdf_tva != $object->total_tva) { // apply coef to recover the VAT object amount (the good one)
							$coef_fix_tva	= !empty($sum_pdf_tva) ? $object->total_tva / $sum_pdf_tva : 1;
							foreach ($this->tva_array as $tvakey => $tvaval) {
								$this->tva_array[$tvakey]['amount']	= $tvaval['amount'] * $coef_fix_tva;
							}
						}
					}
					foreach ($this->tva_array as $tvakey => $tvaval) {
						if (($tvakey > 0 || count($this->tva_array) > 1) && !empty($tvaval['base'])) {	// We do not display rate 0 unless several VAT rates are used
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
					// Local tax 1 after VAT
					foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
						if (in_array((string) $localtax_type, array('2', '4', '6'))) {
							continue;
						}
						foreach ($localtax_rate as $tvakey => $tvaval) {
							if ($tvakey != 0) {	// We do not display rate 0
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
					// Tax stamp
					if (!empty($object->revenuestamp) && price2num($object->revenuestamp) != 0) {
						$index++;
						$pdf->SetAlpha($this->alpha);
						$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
						$pdf->SetAlpha(1);
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('RevenueStamp'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $object->revenuestamp, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
				} else {
					// amounts HT, taxes and VAT for Products
					if (count($this->htProd)) {
						$pdf->SetFont('', 'B', $default_font_size - 1);
						$index++;
						$titleTotalHT	= $outputlangs->transnoentities('PDFInfraSPlusTotauxProd').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : '');
						$pdf->MultiCell($larg_tabtotal, $tabtot_hl, $titleTotalHT, '', 'C', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						foreach ($this->htProd as $vatindex => $htProd) {
							$index++;
							$totalht	= $outputlangs->transnoentities('TotalHTShort').' '.$this->htProd[$vatindex]['vatrate'];
							$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalht, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $htProd['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							//Local tax 1 before VAT
							if (! in_array((string) $this->localtax1Prod[$vatindex]['type'], array('1', '3', '5')) && $this->localtax1Prod[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax1Prod[$vatindex]['rate'])) {
									$this->localtax1Prod[$vatindex]['rate']	= str_replace('*', '', $this->localtax1Prod[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax1Prod[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax1Prod[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							//Local tax 2 before VAT
							if (! in_array((string) $this->localtax2Prod[$vatindex]['type'], array('1', '3', '5')) && $this->localtax2Prod[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax2Prod[$vatindex]['rate'])) {
									$this->localtax2Prod[$vatindex]['rate']	= str_replace('*', '', $this->localtax2Prod[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax2Prod[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax2Prod[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							// VAT
							if ($this->tvaProd[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->tvaProd[$vatindex]['rate'])) {
									$this->tvaProd[$vatindex]['rate']	= str_replace('*', '', $this->tvaProd[$vatindex]['rate']);
									$tvacompl							= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalVAT', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->tvaProd[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->tvaProd[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							//Local tax 1 after VAT
							if (! in_array((string) $this->localtax1Prod[$vatindex]['type'], array('2', '4', '6')) && $this->localtax1Prod[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax1Prod[$vatindex]['rate'])) {
									$this->localtax1Prod[$vatindex]['rate']	= str_replace('*', '', $this->localtax1Prod[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax1Prod[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax1Prod[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							//Local tax 2 after VAT
							if (! in_array((string) $this->localtax2Prod[$vatindex]['type'], array('2', '4', '6')) && $this->localtax2Prod[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax2Prod[$vatindex]['rate'])) {
									$this->localtax2Prod[$vatindex]['rate']	= str_replace('*', '', $this->localtax2Prod[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax2Prod[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax2Prod[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							if (next($this->htProd)) {
								$pdf->line($posxtabtotal + $this->decal_round, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index) + $tabtot_hl, $posxcol2total + $larg_col2total - $this->decal_round, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index) + $tabtot_hl, $this->stdLineStyle);
							}
						}
					}
					// amounts HT, taxes and VAT for Services
					if (count($this->htServ)) {
						$pdf->SetFont('', 'B', $default_font_size - 1);
						$index++;
						$pdf->MultiCell($larg_tabtotal, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotauxServ'), '', 'C', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->SetFont('', '', $default_font_size - 1);
						foreach ($this->htServ as $vatindex => $htServ) {
							$index++;
							$totalht	= $outputlangs->transnoentities('TotalHTShort').' '.$this->htServ[$vatindex]['vatrate'];
							$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalht, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $htServ['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							//Local tax 1 before VAT
							if (! in_array((string) $this->localtax1Serv[$vatindex]['type'], array('1', '3', '5')) && $this->localtax1Serv[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax1Serv[$vatindex]['rate'])) {
									$this->localtax1Serv[$vatindex]['rate']	= str_replace('*', '', $this->localtax1Serv[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax1Serv[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax1Serv[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							//Local tax 2 before VAT
							if (! in_array((string) $this->localtax2Serv[$vatindex]['type'], array('1', '3', '5')) && $this->localtax2Serv[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax2Serv[$vatindex]['rate'])) {
									$this->localtax2Serv[$vatindex]['rate']	= str_replace('*', '', $this->localtax2Serv[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax2Serv[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax2Serv[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							// VAT
							if ($this->tvaServ[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->tvaServ[$vatindex]['rate'])) {
									$this->tvaServ[$vatindex]['rate']	= str_replace('*', '', $this->tvaServ[$vatindex]['rate']);
									$tvacompl							= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalVAT', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->tvaServ[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->tvaServ[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							//Local tax 1 after VAT
							if (! in_array((string) $this->localtax1Serv[$vatindex]['type'], array('2', '4', '6')) && $this->localtax1Serv[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax1Serv[$vatindex]['rate'])) {
									$this->localtax1Serv[$vatindex]['rate']	= str_replace('*', '', $this->localtax1Serv[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT1', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax1Serv[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax1Serv[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							//Local tax 2 after VAT
							if (! in_array((string) $this->localtax2Serv[$vatindex]['type'], array('2', '4', '6')) && $this->localtax2Serv[$vatindex]['rate'] != 0) {
								$index++;
								$pdf->SetAlpha($this->alpha);
								$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
								$pdf->SetAlpha(1);
								$tvacompl	= '';
								if (preg_match('/\*/', $this->localtax2Serv[$vatindex]['rate'])) {
									$this->localtax2Serv[$vatindex]['rate']	= str_replace('*', '', $this->localtax2Serv[$vatindex]['rate']);
									$tvacompl								= ' ('.$outputlangs->transnoentities('NonPercuRecuperable').')';
								}
								$totalvat	= $outputlangs->transcountrynoentities('TotalLT2', $this->emetteur->country_code).' ';
								$totalvat	.= vatrate(abs($this->localtax2Serv[$vatindex]['rate']), 1).$tvacompl;
								$pdf->MultiCell($larg_col1total, $tabtot_hl, $totalvat, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
								$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->localtax2Serv[$vatindex]['mnt'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							}
							if (next($this->htServ)) {
								$pdf->line($posxtabtotal + $this->decal_round, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index) + $tabtot_hl, $posxcol2total + $larg_col2total - $this->decal_round, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index) + $tabtot_hl, $this->stdLineStyle);
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
				$txtTTC				= $outputlangs->transnoentities(!empty($this->positive_credit_note) && $object->type == 2 ? 'TotalTTCToYourCredit' : 'TotalTTCShort').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : '');
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $txtTTC, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$total_ttc_to_show	= !empty($this->situationinvoice) && !empty($this->use_situ_total_2) ? $actual_ttc : $total_ttc;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $total_ttc_to_show, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				if (!empty($this->situationinvoice) && count($this->previnvoices) && empty($this->use_situ_total_2)) {
					foreach ($this->previnvoices as $invoice) {
						if ($invoice->situation_counter > 0) {
							$index++;
							$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
							$pdf->SetAlpha($this->alpha);
							$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
							$pdf->SetAlpha(1);
							$invoiceref		= $outputlangs->transnoentities('InvoiceSituation').$outputlangs->convToOutputCharset(" n°".$invoice->situation_counter);
							$pdf->MultiCell($larg_col1total, $tabtot_hl, $invoiceref, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
							$invoice_ttc	= $this->use_multicurrency ? $invoice->multicurrency_total_ttc : $invoice->total_ttc;
							$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $invoice_ttc, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						}
					}
					$index++;
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$actualref	= $outputlangs->transnoentities('InvoiceSituation').$outputlangs->convToOutputCharset(" n°".$object->situation_counter);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $actualref, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->sign * $actual_ttc, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				}
			}
			// Inclusion of the total including tax of discounts in the table
			if (!empty($this->show_disc_ttc)){
				$index++;
				$totRemTTC	= $this->use_multicurrency ? $this->TotRem['multicurrency_TTC'] : $this->TotRem['TTC'];
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusTotRemTTC').(!empty($this->hasEcoTaxes) ? ' '.$this->hasEcoTaxes : ''), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $totRemTTC, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			// Retained warranty
			if ($object->displayRetainedWarranty()) {
				$index++;	// Billed - retained warranty
				$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				$pdf->SetAlpha($this->alpha);
				$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
				$pdf->SetAlpha(1);
				$retainedWarranty			= $object->getRetainedWarrantyAmount();
				$billedWithRetainedWarranty	= $actual_ttc - $retainedWarranty;
				$retainedWarranty			= pdf_InfraSPlus_price($object, $retainedWarranty, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T');
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusToPayOn', dol_print_date($object->date_lim_reglement, 'day')), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $billedWithRetainedWarranty, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$index++;	// retained warranty %
				$pdf->SetAlpha($this->alpha);
				$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
				$pdf->SetAlpha(1);
				$retainedWarrantyToPayOn	= $outputlangs->transnoentities('RetainedWarranty').' ('.$object->retained_warranty.'%)';
				$pdf->MultiCell($larg_col1total, $tabtot_hl, $retainedWarrantyToPayOn, '', (empty($object->retained_warranty_date_limit) ? 'L' : 'R'), 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($larg_col2total, $tabtot_hl, (empty($object->retained_warranty_date_limit) ? $retainedWarranty : ''), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				if (!empty($object->retained_warranty_date_limit)) {	// retained warranty amount
					$index++;
					$pdf->SetAlpha($this->alpha);
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetAlpha(1);
					$retainedWarrantyToPayOn	= $outputlangs->transnoentities('PDFInfraSPlusToPayOn', dol_print_date($object->retained_warranty_date_limit, 'day'));
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $retainedWarrantyToPayOn, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, $retainedWarranty, '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				}
			}
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$totalEfPaySpec			= 0;
			$totalEfPaySpec_loc_cur	= 0;
			if ($this->efPaySpec) {	// we show special payments before they are paid
				$listEfPaySpec			= pdf_InfraSPlus_SpecPayExtraField($object);
				foreach ($listEfPaySpec as $key => $efPaySpec) {
					if ($efPaySpec['value'] != 0) {
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities($efPaySpec['label']), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $efPaySpec['value'], $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$totalEfPaySpec			+= $efPaySpec['value'];
						$efPaySpec_loc_cur		= Multicurrency::getAmountConversionFromInvoiceRate($object->id, $efPaySpec['value'], 'customer', 'facture');
						$totalEfPaySpec_loc_cur	+= $efPaySpec_loc_cur;
					}
				}
				if ($totalEfPaySpec > 0) {
					$index++;
					$toBePaid	= price2num((!$this->only_ht ? $total_ttc : $total_ht) - $totalEfPaySpec, 'MT');
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PDFInfraSPlusRemainExpense'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $toBePaid, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
				}
			}
			$this->resteapayer			= price2num((empty($this->only_ht) ? $actual_ttc : $actual_ht) - $this->paid - $this->credit_notes - $this->deposits - $totalEfPaySpec, 'MT');
			$this->resteapayer_loc_cur	= price2num((empty($this->only_ht) ? $actual_ttc_loc_cur : $actual_ht_loc_cur) - $this->paid_loc_cur - $this->credit_notes_loc_cur - $this->deposits_loc_cur - $totalEfPaySpec_loc_cur, 'MT');
			if (($this->paid > 0 || $this->credit_notes > 0 || $this->deposits > 0) && empty($this->no_payment_details)) {
				// Already paid + Deposits
				$totalPaySpec	= 0;
				if (!empty($this->listPaySpec) && is_array($this->listPaySpec) && count($this->listPaySpec) > 0) {	// With Special payment
					$hasPaySpecToShow	= 0;
					foreach($this->listPaySpec as $PaySpec => $PaySpec_array) {
						$toHide	= 0;
						if ($this->efPaySpec) {
							foreach ($listEfPaySpec as $key => $efPaySpec) {
								if ($efPaySpec['value'] != 0 && $PaySpec == $key) {
									$this->resteapayer	+= price2num($efPaySpec['value'], 'MT');
									$toHide	++;
								} else {
									$hasPaySpecToShow++;
								}
							}
						}
						$MntLine		= $this->use_multicurrency ? $PaySpec_array['multicurrency_amount'] : $PaySpec_array['amount'];
						$totalPaySpec	+= $MntLine;
						if ($toHide > 0) {
							continue;
						}
						$index++;
						$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('PaymentTypeShort'.$PaySpec), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
						$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $MntLine, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					}
				}
				if (!empty($object->paye)) {
					$this->resteapayer = 0;
				}
				if (!$this->no_payment_table) {	// there are some standard payment
					$index++;
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('Paid'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->paid + $this->deposits - $totalPaySpec, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				}
				// Credit note
				if ($this->credit_notes) {
					$index++;
					$labeltouse	= ($outputlangs->transnoentities('CreditNotesOrExcessReceived') != "CreditNotesOrExcessReceived") ? $outputlangs->transnoentities('CreditNotesOrExcessReceived') : $outputlangs->transnoentities("CreditNotes");
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $labeltouse, '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->credit_notes, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				}
				// Escompte
				if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
					$index++;
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('EscompteOfferedShort'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_ttc - $this->paid - $this->credit_notes - $this->deposits, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$this->resteapayer	= 0;
				}
				if (!$this->no_payment_table) {
					$index++;
					$pdf->RoundedRect($posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					!empty($this->title_bg) ? $pdf->SetTextColor((int) $this->txtcolor[0], (int) $this->txtcolor[1], (int) $this->txtcolor[2]) : $pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->MultiCell($larg_col1total, $tabtot_hl, $outputlangs->transnoentities('RemainderToPay'), '', 'L', 0, 1, $posxtabtotal, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $this->resteapayer, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
				}
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
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
				$total_loc_cur	= $this->resteapayer_loc_cur;
				$pdf->MultiCell($larg_col2total, $tabtot_hl, pdf_InfraSPlus_price($object, $total_loc_cur, $outputlangs, 1, 1, 'T'), '', 'R', 0, 1, $posxcol2total, $posytabtot + (($tabtot_hl + $this->bgLineW) * $index), true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetFont('', '', $default_font_size - 1);
			if (!empty($this->number_words)) {
				$index++;
				$savcurrency	= $conf->currency;
				$conf->currency	= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
				if (!empty($this->situationinvoice)) {
					$total_words	= $outputlangs->transnoentities('PDFInfraSPlusSituationArrete').' : '.$outputlangs->getLabelFromNumber($this->resteapayer, 1);
				} else {
					$total_words	= $outputlangs->transnoentities('PDFInfraSPlusInvoiceArrete').' : '.$outputlangs->getLabelFromNumber($this->resteapayer, 1);
				}
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
				$pdf->setCellPaddings($savedCellPaddings['L'], $savedCellPaddings['T'], $savedCellPaddings['R'], $savedCellPaddings['B']);
				return $posytabtot;
			}
		}

		/**
		*	Show payments table
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Facture		$object			Object to show
		*	@param		int			$posy		 	Position y in PDF
		*	@param		Translate	$outputlangs	Object langs for output
		*	@return		int							Position pour suite
		**/
		protected function _tableau_versements(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabver			= $posy + 1;
			$tabver_hl			= $this->tab_hl - 1;
			$pdf->SetFont('', '', $default_font_size - 3);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabver		= $this->larg_tabtotal;
			$larg_col1ver		= ($larg_tabver / 4) - 5;
			$larg_col2ver		= ($larg_tabver / 4) - 5;
			$larg_col3ver		= ($larg_tabver / 4) + 5;
			$larg_col4ver		= ($larg_tabver / 4) + 5;
			$posxtabver			= $this->posxtabtotal;
			$posxcol2ver		= $posxtabver + $larg_col1ver;
			$posxcol3ver		= $posxcol2ver + $larg_col2ver;
			$posxcol4ver		= $posxcol3ver + $larg_col3ver;
			$index				= 0;
			$title				= $outputlangs->transnoentities('PaymentsAlreadyDone');
			if ($object->type == 2) {
				$title	= $outputlangs->transnoentities('PaymentsBackAlreadyDone');
			}
			$pdf->MultiCell($larg_tabver, $tabver_hl, $title, '', 'L', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
			$pdf->SetFont('', '', $default_font_size - 4);
			$pdf->MultiCell($larg_col1ver, $tabver_hl, $outputlangs->transnoentities('Date'), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col2ver, $tabver_hl, $outputlangs->transnoentities('Amount'), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col3ver, $tabver_hl, $outputlangs->transnoentities('Type'), '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($larg_col4ver, $tabver_hl, $outputlangs->transnoentities('Num'), '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
			$index++;
			$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
			$pdf->SetFont('', '', $default_font_size - 4);
			// Loop on each discount available (deposits as pyments, credit notes and excess of payment included)
			$sql	= 'SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva,';
			$sql	.= ' re.amount_ttc, re.multicurrency_amount_ttc, re.description, re.fk_facture_source, f.type, f.datef';
			$sql	.= ' FROM '.$this->db->prefix() .'societe_remise_except as re, '.$this->db->prefix() .'facture as f';
			$sql	.= ' WHERE re.fk_facture_source = f.rowid AND re.fk_facture = '.((int) $object->id);
			$resql	= $this->db->query($sql);
			if ($resql) {
				$num		= $this->db->num_rows($resql);
				$invoice	= new Facture($this->db);
				for ($i = 0 ; $i < $num ; $i++) {
					$obj	= $this->db->fetch_object($resql);
					$invoice->fetch($obj->fk_facture_source);
					if (empty($this->only_ht)) {
						$MntLine	= $this->use_multicurrency ? $obj->multicurrency_amount_ttc : $obj->amount_ttc;
					} else {
						$MntLine	= $this->use_multicurrency ? $obj->multicurrency_amount_ht : $obj->amount_ht;
					}
					if ($obj->type == 0) {
						$text	= $outputlangs->transnoentities('ExcessReceived');
					} elseif ($obj->type == 2) {
						$text	= $outputlangs->transnoentities('CreditNote');
					} elseif ($obj->type == 3) {
						$text	= $outputlangs->transnoentities('Deposit');
					} else {
						$text	= $outputlangs->transnoentities('UnknownType');
					}
					$pdf->MultiCell($larg_col1ver, $tabver_hl, dol_print_date($this->db->jdate($obj->datef), 'day', false, $outputlangs, true), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2ver, $tabver_hl, pdf_InfraSPlus_price($object, $MntLine, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col3ver, $tabver_hl, $text, '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col4ver, $tabver_hl, $invoice->ref, '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
					$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
				}
			} else {
				$this->error	= $this->db->lasterror();
			}
			// Loop on each payment
			$sql	= 'SELECT p.datep AS date, p.fk_paiement AS type, p.num_paiement AS num, pf.amount AS amount,';
			$sql	.= ' pf.multicurrency_amount, cp.code, cp.type AS payType';
			$sql	.= ' FROM '.$this->db->prefix().'paiement_facture AS pf, '.$this->db->prefix().'paiement AS p';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'c_paiement AS cp ON p.fk_paiement = cp.id';
			$sql	.= ' WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = '.((int) $object->id).' AND cp.entity IN ('.getEntity('c_paiement').')';
			$sql	.= $this->use_Pay_Spec ? ' AND cp.type <> 3' : '';
			$sql	.= ' ORDER BY p.datep';
			$resql	= $this->db->query($sql);
			if ($resql) {
				$num	= $this->db->num_rows($resql);
				for ($i = 0 ; $i < $num ; $i++) {
					$row		= $this->db->fetch_object($resql);
					$MntLine	= $this->use_multicurrency ? $row->multicurrency_amount : $row->amount;
					$oper		= $outputlangs->transnoentitiesnoconv("PaymentTypeShort".$row->code);
					$pdf->MultiCell($larg_col1ver, $tabver_hl, dol_print_date($this->db->jdate($row->date),'day', false, $outputlangs, true), '', 'C', 0, 1, $posxtabver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col2ver, $tabver_hl, pdf_InfraSPlus_price($object, $this->sign * $MntLine, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $posxcol2ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col3ver, $tabver_hl, $oper, '', 'C', 0, 1, $posxcol3ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($larg_col4ver, $tabver_hl, $row->num, '', 'C', 0, 1, $posxcol4ver, $posytabver + ($tabver_hl * $index), true, 0, 0, false, 0, 'M', false);
					$index++;
					$pdf->line($posxtabver, $posytabver + ($tabver_hl * $index), $posxtabver + $larg_tabver, $posytabver + ($tabver_hl * $index), $this->stdLineStyle);
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

		/**
		*	Show LCR page.
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		Facture		$object			Object to show
		*	@param		float		$tab_top		Top position of table
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		void
		**/
		protected function _lcr(&$pdf, $object, $tab_top, $outputlangs)
		{
			global $conf;

			$currency		= !empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency;
			$pdf->SetDrawColor(128, 128, 128);
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetFont('', '', $default_font_size - 1);
			$styleLCR		= array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(162, 162, 162));
			$posy			= $tab_top + 30;
			$interval		= 5;
			// Cadre externe
			$pdf->RoundedRect($this->marge_gauche, $posy, $this->larg_util_cadre, $this->tab_hl * 21, $this->Rounded_rect, '1111');
			// Intitulé
			$largIntitule	= 60;
			$posy			+= $this->tab_hl * 0.5;
			$posx1L1		= $this->marge_gauche + 35;
			$posx2L1		= $posx1L1 + $largIntitule;
			$pdf->Line($posx1L1, $posy, $posx1L1, $posy + $this->tab_hl * 4, $styleLCR);
			$pdf->writeHTMLCell($largIntitule, $this->tab_hl * 4, $posx1L1 + 1, $posy, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusLCRIntitule')), 0, 1);
			// Emetteur
			$carac_emetteur	= pdf_InfraSPlus_build_address($outputlangs, $this->emetteur, $this->emetteur, $object->thirdparty, '', 0, 'sourcewithnodetails', null, 0);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($largIntitule, $this->tab_hl, $outputlangs->convToOutputCharset($this->emetteur->name), '', 'C', 0, 1, $posx2L1, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->MultiCell($largIntitule, $this->tab_hl * 4, $carac_emetteur, '', 'C', 0, 1, $posx2L1, $posy + $this->tab_hl, true, 0, 0, false, 0, 'M', false);
			// Lieu & Date
			$largLieu		= 34;
			$largDate		= 21;
			$posy			+= $this->tab_hl * 5;
			$posx1L2		= $this->marge_gauche + $interval;
			$posx2L2		= $posx1L2 + $largLieu;
			$lieu			= $outputlangs->transnoentities('PDFInfraSPlusLCRa').' <b>'.$outputlangs->convToOutputCharset($this->emetteur->town).'</b>';
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->writeHTMLCell($largLieu, $this->tab_hl, $posx1L2, $posy, $lieu, 0, 1);
			$pdf->MultiCell($largDate, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRle').' ', '', 'L', 0, 1, $posx2L2, $posy, true, 0, 0, false, 0, 'M', false);
			$posyArrow		= $posy + ($this->tab_hl / 2);
			$posx3Arrow		= $posx2L2 + ($largDate / 2) - 3;
			$pdf->Line($posx3Arrow, $posyArrow, $posx3Arrow + $interval, $posyArrow, $styleLCR);
			$pdf->Line($posx3Arrow + $interval, $posyArrow, $posx3Arrow + $interval, $posyArrow + 2, $styleLCR);
			$pdf->Line($posx3Arrow + $interval - 1, $posyArrow + 2, $posx3Arrow + $interval + 1, $posyArrow + 2, $styleLCR);
			$pdf->Line($posx3Arrow + $interval - 1, $posyArrow + 2, $posx3Arrow + $interval, $posyArrow + 3, $styleLCR);
			$pdf->Line($posx3Arrow + $interval + 1, $posyArrow + 2, $posx3Arrow + $interval, $posyArrow + 3, $styleLCR);
			// Montant pour contrôle, date création & échéance, LCR seulement, symbole & montant => TRAME
			$largMontant	= 30.5;
			$posy			+= $this->tab_hl * 1.5;
			$posycadre		= $posy + ($this->tab_hl / 2);
			$posx1L3		= $this->marge_gauche + $interval;
			$pdf->SetFont('', '', $default_font_size - 3);
			$pdf->MultiCell($largMontant, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRMntCtrl'), '', 'C', 0, 1, $posx1L3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->Rect($posx1L3, $posycadre, $largMontant, $this->tab_hl * 2, 'D', array('L' => $styleLCR, 'T' => 0, 'R' => $styleLCR, 'B' => $styleLCR));
			$posx2L3		= $posx1L3 + $interval + $largMontant;
			$pdf->MultiCell($largDate, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRDtCrea'), '', 'C', 0, 1, $posx2L3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->Rect($posx2L3, $posycadre, $largDate, $this->tab_hl * 2, 'D', array('L' => $styleLCR, 'T' => 0, 'R' => $styleLCR, 'B' => $styleLCR));
			$posx3L3		= $posx2L3 + $interval + $largDate;
			$pdf->MultiCell($largDate, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCREchea'), '', 'C', 0, 1, $posx3L3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->Rect($posx3L3, $posycadre, $largDate, $this->tab_hl * 2, 'D', array('L' => $styleLCR, 'T' => 0, 'R' => $styleLCR, 'B' => $styleLCR));
			$largLCRSeul	= 57;
			$posy			-= $this->tab_hl;
			$posycadre		-= $this->tab_hl;
			$largExtrem		= 8;
			$posx4L3		= $posx3L3 + $interval + $largDate;
			$pdf->MultiCell($largLCRSeul, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRLCRSeul'), '', 'C', 0, 1, $posx4L3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->Rect($posx4L3, $posycadre, $largExtrem, $this->tab_hl * 3, 'D', array('L' => $styleLCR, 'T' => $styleLCR, 'R' => 0, 'B' => $styleLCR));
			$pdf->Line($posx4L3 + $largExtrem, $posycadre, $posx4L3 + $largExtrem + $interval, $posycadre, $styleLCR);
			$pdf->Line($posx4L3 + $largLCRSeul - $largExtrem - $interval, $posycadre, $posx4L3 + $largLCRSeul - $largExtrem, $posycadre, $styleLCR);
			$pdf->Rect($posx4L3 + $largLCRSeul - $largExtrem, $posycadre, $largExtrem, $this->tab_hl * 3, 'D', array('L' => 0, 'T' => $styleLCR, 'R' => $styleLCR, 'B' => $styleLCR));
			$pdf->SetFont('', '', $default_font_size - 6);
			$pdf->MultiCell($largExtrem, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRRefTire'), '', 'C', 0, 1, $posx4L3 + $largExtrem, $posy + ($this->tab_hl * 3), true, 0, 0, false, 0, 'M', false);
			$posycadre		+= $this->tab_hl * 1.5;
			$pdf->Rect($posx4L3 + ($largExtrem * 2), $posycadre, $largExtrem, $this->tab_hl * 1.5, 'D', array('L' => 0, 'T' => 0, 'R' => $styleLCR, 'B' => $styleLCR));
			$pdf->Rect($posx4L3 + ($largExtrem * 3) + $interval, $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => $styleLCR, 'T' => 0, 'R' => $styleLCR, 'B' => $styleLCR));
			$pdf->Rect($posx4L3 + ($largExtrem * 3) + ($interval * 3), $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => $styleLCR, 'T' => 0, 'R' => $styleLCR, 'B' => $styleLCR));
			$pdf->Line($posx4L3 + $largLCRSeul - $largExtrem, $posycadre, $posx4L3 + $largLCRSeul - $largExtrem, $posycadre + ($this->tab_hl * 1.5), $styleLCR);
			$posycadre		-= $this->tab_hl * 1.5;
			$posx5L3		= $posx4L3 + $interval + $largLCRSeul;
			$symnMnt		= '<b>'.$outputlangs->getCurrencySymbol($currency).'</b> '.$outputlangs->transnoentities('PDFInfraSPlusLCRSymbMnt');
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->writeHTMLCell($largMontant, $this->tab_hl, $posx5L3, $posy, dol_htmlentitiesbr($symnMnt), 0, 1, false, true, 'C');
			$pdf->Rect($posx5L3, $posycadre, $largMontant, $this->tab_hl * 3, 'D', array('L' => $styleLCR, 'T' => 0, 'R' => $styleLCR, 'B' => $styleLCR));
			// Montant pour contrôle, date création & échéance, LCR seulement, symbole & montant => Remplissage
			$posy			+= $this->tab_hl * 2;
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->MultiCell($largMontant, $this->tab_hl, pdf_InfraSPlus_price($object, $this->resteapayer, $outputlangs, 0, 0, 'T'), '', 'C', 0, 1, $posx1L3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largDate, $this->tab_hl, dol_print_date($object->date, 'day', false, $outputlangs, true), '', 'C', 0, 1, $posx2L3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largDate, $this->tab_hl, dol_print_date($object->date_lim_reglement, 'day', false, $outputlangs, true), '', 'C', 0, 1, $posx3L3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largMontant, $this->tab_hl, pdf_InfraSPlus_price($object, $this->resteapayer, $outputlangs, 0, 0, 'T'), '', 'C', 0, 1, $posx5L3, $posy, true, 0, 0, false, 0, 'M', false);
			// Références client, facture => Trame
			$largRefClient	= 65;
			$largRefFacture	= 45;
			$largRef		= 40;
			$posycadre		+= $this->tab_hl * 3.5;
			$posx1L4		= $this->marge_gauche + $interval;
			$pdf->Rect($posx1L4, $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => $styleLCR, 'T' => $styleLCR, 'R' => 0, 'B' => $styleLCR));
			$pdf->Line($posx1L4 + $interval, $posycadre + $this->tab_hl * 1.5, $posx1L4 + $largRefClient - $interval, $posycadre + $this->tab_hl * 1.5, $styleLCR);
			$pdf->Rect($posx1L4 + $largRefClient - $interval, $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => 0, 'T' => $styleLCR, 'R' => $styleLCR, 'B' => $styleLCR));
			$posx2L4		= $posx1L4 + $largRefClient + ($interval * 3);
			$pdf->Rect($posx2L4, $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => $styleLCR, 'T' => $styleLCR, 'R' => 0, 'B' => $styleLCR));
			$pdf->Rect($posx2L4 + $largRefFacture - $interval, $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => 0, 'T' => $styleLCR, 'R' => $styleLCR, 'B' => $styleLCR));
			$posx3L4		= $posx2L4 + $largRefFacture + ($interval * 3);
			$pdf->Rect($posx3L4, $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => $styleLCR, 'T' => $styleLCR, 'R' => 0, 'B' => $styleLCR));
			$pdf->Rect($posx3L4 + $largRef - $interval, $posycadre, $interval, $this->tab_hl * 1.5, 'D', array('L' => 0, 'T' => $styleLCR, 'R' => $styleLCR, 'B' => $styleLCR));
			// Références client, facture => Remplissage
			$posy			+= $this->tab_hl * 2.25;
			$pdf->MultiCell($largRefClient, $this->tab_hl, $outputlangs->transnoentities($object->thirdparty->code_client), '', 'C', 0, 1, $posx1L4, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largRefFacture, $this->tab_hl, $outputlangs->convToOutputCharset($object->ref), '', 'C', 0, 1, $posx2L4, $posy, true, 0, 0, false, 0, 'M', false);
			// RIB & Domiciliation => Trame
			$largRIB		= 65;
			$largEtab		= 15;
			$largGui		= 18;
			$largCpte		= 25;
			$largCle		= $largRIB - $largEtab - $largGui - $largCpte;
			$largLibAdr		= 12;
			$largAdr		= 38;
			$largDom		= 55;
			$posy			+= $this->tab_hl * 1.5;
			$posx1L5		= $this->marge_gauche + $interval;
			$posx2L5		= $posx1L5 + $largRIB + $interval;
			$posx3L5		= $posx1L5 + $largRIB + $largLibAdr + $interval;
			$posx4L5		= $posx1L5 + $largRIB + $largLibAdr + $largAdr + ($interval * 2);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($largRIB, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRRIB'), '', 'C', 0, 1, $posx1L5, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largDom, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRDom'), '', 'C', 0, 1, $posx4L5, $posy, true, 0, 0, false, 0, 'M', false);
			$posycadre		+= $this->tab_hl * 2.75;
			$pdf->Rect($posx1L5, $posycadre, $largRIB, $this->tab_hl, 'D', array('L' => $styleLCR, 'T' => $styleLCR, 'R' => $styleLCR, 'B' => 0));
			$pdf->Rect($posx1L5 + $largEtab, $posycadre, $largGui, $this->tab_hl, 'D', array('L' => $styleLCR, 'T' => 0, 'R' => $styleLCR, 'B' => 0));
			$pdf->Line($posx1L5 + $largRIB - $largCle, $posycadre, $posx1L5 + $largRIB - $largCle, $posycadre + $this->tab_hl, $styleLCR);
			$pdf->Rect($posx4L5, $posycadre, $largDom, $this->tab_hl * 4, 'D', array('all' => $styleLCR));
			$posy			+= $this->tab_hl * 2;
			$pdf->SetFont('', '', $default_font_size - 3);
			$pdf->MultiCell($largEtab, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCREtab'), '', 'C', 0, 1, $posx1L5, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largGui, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRGui'), '', 'C', 0, 1, $posx1L5 + $largEtab, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largCpte, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRCpt'), '', 'C', 0, 1, $posx1L5 + $largEtab + $largGui, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->MultiCell($largCle, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRCle'), '', 'C', 0, 1, $posx1L5 + $largEtab + $largGui + $largCpte, $posy, true, 0, 0, false, 0, 'M', false);
			$posycadre		+= $this->tab_hl;
			$pdf->Line($posx2L5, $posycadre, $posx2L5, $posycadre + ($this->tab_hl * 5), $styleLCR);
			$posy			+= $this->tab_hl;
			$pdf->MultiCell($largLibAdr, $this->tab_hl * 2, $outputlangs->transnoentities('PDFInfraSPlusLCRNmTire'), '', 'C', 0, 1, $posx2L5, $posy, true, 0, 0, false, 0, 'M', false);
			$posy			+= $this->tab_hl;
			$valeurEn		= $outputlangs->transnoentities('PDFInfraSPlusLCRVal').' <b>'.$outputlangs->transnoentitiesnoconv("Currency".$currency).'</b>';
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->writeHTMLCell($largEtab + $largGui + $largCpte, $this->tab_hl, $posx1L5, $posy, dol_htmlentitiesbr($valeurEn), 0, 1);
			$posy			+= $this->tab_hl;
			$pdf->MultiCell($largDom, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRSign'), '', 'C', 0, 1, $posx4L5, $posy, true, 0, 0, false, 0, 'M', false);
			// RIB & Domiciliation => Remplissage
			$posy			-= $this->tab_hl * 3;
			$posynext		= $posy;
			$sql			= 'SELECT fk_soc, domiciliation, code_banque, code_guichet, number, cle_rib, proprio, owner_address, default_rib';
			$sql			.= ' FROM '.$this->db->prefix() .'societe_rib as rib';
			$sql			.= ' WHERE rib.fk_soc = '.((int) $object->thirdparty->id);
			$sql			.= ' AND rib.default_rib = 1';
			$resql			= $this->db->query($sql);
			if ($resql) {
				$num	= $this->db->num_rows($resql);
				$i		= 0;
				while ($i <= $num) {
					$cpt	= $this->db->fetch_object($resql);
					$posy	-= $this->tab_hl;
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->MultiCell($largEtab, $this->tab_hl, $cpt->code_banque, '', 'C', 0, 1, $posx1L5, $posy, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($largGui, $this->tab_hl, $cpt->code_guichet, '', 'C', 0, 1, $posx1L5 + $largEtab, $posy, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($largCpte, $this->tab_hl, $cpt->number, '', 'C', 0, 1, $posx1L5 + $largEtab + $largGui, $posy, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($largCle, $this->tab_hl, $cpt->cle_rib, '', 'C', 0, 1, $posx1L5 + $largEtab + $largGui + $largCpte, $posy, true, 0, 0, false, 0, 'M', false);
					$pdf->MultiCell($largDom, $this->tab_hl * 3, $outputlangs->convToOutputCharset($cpt->domiciliation), '', 'C', 0, 1, $posx4L5, $posy, true, 0, 0, false, 0, 'M', false);
					$posy	+= $this->tab_hl;
					$pdf->MultiCell($largAdr, $this->tab_hl, $cpt->proprio, '', 'C', 0, 1, $posx3L5, $posy, true, 0, 0, false, 0, 'M', false);
					$posy	= $pdf->GetY();
					$pdf->MultiCell($largAdr, $this->tab_hl * 3, $outputlangs->convToOutputCharset($cpt->owner_address), '', 'C', 0, 1, $posx3L5, $posy, true, 0, 0, false, 0, 'M', false);
					$i++;
				}
			}
			// Bas de LCR
			$largAcc	= 30;
			$largRien	= 150;
			$posy		= $posynext + $this->tab_hl * 5;
			$posycadre	= $posy + $this->tab_hl;
			$posx1L6	= $this->marge_gauche + $interval;
			$posx2L6	= $posx1L6 + $largAcc;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($largAcc, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRAcc'), '', 'L', 0, 1, $posx1L6, $posy, true, 0, 0, false, 0, 'M', false);
			$posyArrow	= $posy + ($this->tab_hl / 2);
			$posx3Arrow	= $posx2L6;
			$pdf->Line($posx3Arrow, $posyArrow, $posx3Arrow + $interval, $posyArrow, $styleLCR);
			$pdf->Line($posx3Arrow + $interval, $posyArrow, $posx3Arrow + $interval, $posyArrow - 2, $styleLCR);
			$pdf->Line($posx3Arrow + $interval - 1, $posyArrow - 2, $posx3Arrow + $interval + 1, $posyArrow - 2, $styleLCR);
			$pdf->Line($posx3Arrow + $interval - 1, $posyArrow - 2, $posx3Arrow + $interval, $posyArrow - 3, $styleLCR);
			$pdf->Line($posx3Arrow + $interval + 1, $posyArrow - 2, $posx3Arrow + $interval, $posyArrow - 3, $styleLCR);
			$pdf->MultiCell($largRien, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLCRRien'), '', 'R', 0, 1, $posx2L6, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->Line($posx1L6, $posycadre, $posx2L6 + $largRien, $posycadre, $styleLCR);
		}
	}