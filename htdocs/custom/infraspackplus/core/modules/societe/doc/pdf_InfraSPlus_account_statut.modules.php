<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand 		- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2017-2025	Open-DSI 				- <support@open-dsi.fr>
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
	* 	\file		./infraspackplus/core/modules/societe/doc/pdf_InfraSPlus_account_statut.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF thirdparty account status
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	if ($conf->multicompany->enabled) {
		dol_include_once('/multicompany/class/dao_multicompany.class.php');
	}

	/************************************************
	*	Class to generate PDF proposal InfraS
	************************************************/
	class pdf_InfraSPlus_account_statut extends CommonDocGenerator
	{
		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $draft_watermark;
		public $option_logo;
		public $option_tva;
		public $option_modereg;
		public $option_condreg;
		public $option_multilang;
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
		public $pied;
		public $include_alias;
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
		public $largcol1;
		public $largcol2;
		public $largcol3;
		public $largcol4;
		public $largcol5;
		public $largcol6;
		public $tableau = array();	// Array of table to print
		public $heightforfooter;
		public $larg_tabtotal;
		public $posxtabtotal;
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $add_multicurrency;
		public $colorLine;
		public $currency;
		public $export_type;
		public $exportparameters;
		public $factCodeExf;
		public $larg_date;
		public $larg_datelim;
		public $larg_remaining;
		public $larg_totalpaid;
		public $num_date;
		public $num_datelim;
		public $num_remaining;
		public $num_totalpaid;
		public $orderby;
		public $show_payment_deadline;
		public $total_amountrule;
		public $total_balance;
		public $total_multicurrencyamountrule;
		public $total_multicurrencybalance;
		public $total_multicurrencytotalamount;
		public $total_totalamount;
		public $defaultRefSupplier;
		public $depositJustPay;
		public $typeadr;

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		function __construct($db)
		{
			global $conf, $langs, $mysoc;

			$langs->loadLangs(array('main', 'dict', 'bills', 'companies', 'extraitcompteclient@extraitcompteclient', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusAccountStatusName');
			$this->description					= $langs->trans('PDFInfraSPlusAccountStatusDescription');
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->factCodeExf					= getDolGlobalString('EXTRAITCOMPTECLIENT_FACTURE_CODE_EXTRAFIELD', '');
			$this->depositJustPay				= getDolGlobalInt('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS', 0);
			$this->orderby						= getDolGlobalString('EXTRAITCOMPTECLIENT_ORDERBY', '') ? 'ASC' : 'DESC';
			$this->colorLine					= getDolGlobalString('EXTRAITCOMPTECLIENT_COLOR_LINE_PDF', '');
			$this->defaultRefSupplier			= getDolGlobalInt('EXTRAITCOMPTECLIENT_DEFAULT_REF_SUPPLIER', 0);
			$this->larg_date					= 20;
			$this->larg_datelim					= 20;
			$this->larg_totalttc				= 28;
			$this->larg_totalpaid				= 28;
			$this->larg_remaining				= 28;
			$this->num_date						= 1;
			$this->num_desc						= 2;
			$this->num_datelim					= 3;
			$this->num_totalttc					= 4;
			$this->num_totalpaid				= 5;
			$this->num_remaining				= 6;
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_modereg				= 1;	// Display payment mode
			$this->option_condreg				= 1;	// Display payment terms
			$this->option_multilang				= 1;	// Available in several languages
			$this->option_freetext				= 1;	// Support add of a personalised text
			$this->option_draft_watermark		= 1;	// Support add of a watermark on drafts
		}


		/**
		*	Function to build pdf onto disk
		*
		*	@param		Societe		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details (inutilisée ! laissé pour la compatibilité)
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return	int							1=OK, 0=KO
		**/
		public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
		{
			global $user, $langs, $conf, $hookmanager, $mc;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			// surcharge les traductions
			$outputlangsold	= $outputlangs;
			$outputlangs	= new Translate('', $conf);
			if (getDolGlobalString('MAIN_MULTILANGS', '')) {
				$newlang	= !empty(GETPOST('lang_id', 'aZ09')) ? GETPOST('lang_id', 'aZ09') : $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs->setDefaultLang($newlang);
			} else {
				$outputlangs->setDefaultLang($outputlangsold->defaultlang);
			}
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'companies', 'extraitcompteclient@extraitcompteclient', 'infraspackplus@infraspackplus'));
			$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_AS') ? '' : '_AS';
			$baseDir	= !empty($conf->societe->multidir_output[$object->entity]) ? $conf->societe->multidir_output[$object->entity] : $conf->societe->dir_output;
			if (!empty($baseDir)) {
				// Definition of $dir and $file
				if (!empty($object->specimen)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				} else {
					$objectid				= dol_sanitizeFileName($object->id);
					$dir					= $baseDir.'/'.$objectid;
					$datefile				= dol_print_date(dol_now(), '%Y-%m-%d');
					$this->exportparameters	= $object->context['account_statut'];
					$this->export_type		= $this->exportparameters['export_type'];
					$this->titlekey			= 'ExtraitCompteClientPDFAccountStatut'.$this->export_type;
					$thirdparty_code		= $this->export_type == 'Customer' ? $object->code_client : ($this->export_type == 'Supplier' ? $object->code_fournisseur : '');
					$export_type_lang		= $langs->transnoentitiesnoconv($this->export_type);
					$file_name				= trim($langs->transnoentitiesnoconv('ExtraitCompteClientPDFAccountStatutFileName', $export_type_lang, $thirdparty_code, $datefile));
					if (class_exists('DaoMulticompany') && $conf->multicompany->enabled && !empty($mc->sharings) && !empty($mc->sharings['thirdparty'])) {
						$ent = new DaoMulticompany($this->db);
						$ent->fetch($conf->entity);
						$file_name	.= '_'.$ent->label;
					}
					$file	= $dir.'/'.dol_sanitizeFileName($file_name.$filesufixe).'.pdf';
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
					$parameters						= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook						= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$date_start						= $this->exportparameters['date_start'];
					$date_end						= $this->exportparameters['date_end'];
					$show_subsidiaries				= !empty($this->exportparameters['show_subsidiaries']);
					$show_invoice_payed				= !empty($this->exportparameters['show_invoice_payed']);
					$show_invoice_abandoned			= !empty($this->exportparameters['show_invoice_abandoned']);
					$this->show_payment_deadline	= !empty($this->exportparameters['show_payment_deadline']);
					$show_payment_details			= !empty($this->exportparameters['show_payment_details']);
					$add_product_tags				= !empty($this->exportparameters['add_product_tags']);
					$show_thirdparty_ref			= !empty($this->exportparameters['show_thirdparty_ref']);
					$this->add_multicurrency		= !empty($conf->multicurrency->enabled) && !empty($this->exportparameters['add_multicurrency']);
					$idssubsidiaries				= array();
					if (!empty($show_subsidiaries)) {
						$sql	= 'SELECT s.rowid, s.client, s.fournisseur, s.nom AS name, s.name_alias, s.email, s.address, s.zip, s.town, s.code_client,';
						$sql	.= ' s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.canvas';
						$sql	.= ' FROM '.$this->db->prefix().'societe AS s';
						$sql	.= ' WHERE s.parent = '.((int) $object->id);
						$sql	.= ' AND s.entity IN ('.getEntity('societe').')';
						$sql	.= ' ORDER BY s.nom';
						$result	= $this->db->query($sql);
						$num	= $this->db->num_rows($result);
						if (!empty($num)) {
							for ($i = 0 ; $i < $num ; $i++) {
								$obj	= $this->db->fetch_object($result);
								array_push($idssubsidiaries, $obj->rowid);
							}
						}
					}
					if ($this->export_type == 'Customer') {	// Get lines of the customer account statut
						$sql	= 'SELECT f.datef AS date';
						$sql	.= ', f.ref AS label';
						$sql	.= (!empty($this->show_payment_deadline) ? ', f.date_lim_reglement AS date_limite' : '');
						$sql	.= ', f.ref_client AS label_externe';
						$sql	.= ', f.total_ttc AS total_amount';
						$sql	.= ', COALESCE(f.multicurrency_code, "'.$conf->currency.'") AS facture_multicurrency_code';
						$sql	.= ', f.multicurrency_tx AS facture_multicurrency_tx';
						$sql	.= ', f.multicurrency_total_ttc AS multicurrency_total_amount';
						$sql	.= ', f.type AS invoicetype';
						$sql	.= ', f.fk_statut AS invoicestatus';
						$sql	.= ', f.close_code AS invoiceclosecode';
						$sql	.= ', pf.amount_payed';
						$sql	.= ', COALESCE(pf.multicurrency_code, "'.$conf->currency.'") AS paiement_multicurrency_code';
						$sql	.= ', pf.paiement_multicurrency_tx, pf.multicurrency_amount_payed';
						$sql	.= ', pf.count_amount_payed';
						$sql	.= ', rc.amount_creditnote';
						$sql	.= ', rc.multicurrency_amount_creditnote';
						$sql	.= ', rc2.amount_creditused';
						$sql	.= ', rc2.multicurrency_amount_creditused';
						$sql	.= (!empty($add_product_tags) ? ', pt.tags AS product_tags' : '');
						$sql	.= (!empty($this->factCodeExf) ? ', ef.'.$this->factCodeExf.' AS extrafield_invoice' : '');
						$sql	.= ' FROM '.$this->db->prefix().'facture AS f';
						$sql	.= !empty($this->factCodeExf) ? ' LEFT JOIN '.$this->db->prefix().'facture_extrafields AS ef ON f.rowid = ef.fk_object' : '';
						$sql	.= ' LEFT JOIN (';
						$sql	.= '	SELECT sre.fk_facture AS fk_facture_source';
						$sql	.= '	, SUM(sre.amount) AS amount_payed';
						$sql	.= '	, sre.multicurrency_code';
						$sql	.= '	, sre.multicurrency_tx AS paiement_multicurrency_tx';
						$sql	.= '	, count(sre.amount) AS count_amount_payed';
						$sql	.= '	, SUM(sre.multicurrency_amount) AS multicurrency_amount_payed';
						$sql	.= '	FROM '.$this->db->prefix().'paiement_facture AS sre';
						$sql	.= '	GROUP BY sre.fk_facture';
						$sql	.= ') AS pf ON pf.fk_facture_source = f.rowid';
						$sql	.= ' LEFT JOIN (';
						$sql	.= '	SELECT sre.fk_facture_source AS fk_facture_source';
						$sql	.= '	, SUM(sre.amount_ttc) AS amount_creditnote';
						$sql	.= '	, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditnote';
						$sql	.= '	FROM '.$this->db->prefix().'societe_remise_except AS sre';
						$sql	.= '	GROUP BY sre.fk_facture_source';
						$sql	.= ') AS rc ON rc.fk_facture_source = f.rowid';
						$sql	.= ' LEFT JOIN (';
						$sql	.= '	SELECT sre.fk_facture AS fk_facture';
						$sql	.= '	, SUM(sre.amount_ttc) AS amount_creditused';
						$sql	.= '	, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditused';
						$sql	.= '	FROM '.$this->db->prefix().'societe_remise_except AS sre';
						$sql	.= '	GROUP BY sre.fk_facture';
						$sql	.= ') AS rc2 ON rc2.fk_facture = f.rowid';
						if (!empty($add_product_tags)) {
							$sql	.= ' LEFT JOIN (';
							$sql	.= "	SELECT fd.fk_facture, GROUP_CONCAT(DISTINCT c.label SEPARATOR '] [') AS tags";
							$sql	.= '	FROM '.$this->db->prefix().'facturedet AS fd';
							$sql	.= '	LEFT JOIN '.$this->db->prefix().'categorie_product AS cp ON cp.fk_product = fd.fk_product';
							$sql	.= '	LEFT JOIN '.$this->db->prefix().'categorie AS c ON c.rowid = cp.fk_categorie';
							$sql	.= '	GROUP BY fd.fk_facture';
							$sql	.= ' ) AS pt ON pt.fk_facture = f.rowid';
						}
						$sql	.= ' WHERE f.entity IN ('.getEntity('facture').')';
						$sql	.= ' AND f.datef >= "'.dol_print_date($date_start, 'dayrfc') . '"';
						$sql	.= ' AND f.datef <= "'.dol_print_date($date_end, 'dayrfc') . '"';
						if (!empty($show_subsidiaries)) {
							$sql	.= ' AND ((f.fk_soc = '.((int) $object->id).')';
							foreach ($idssubsidiaries as $id) {
								$sql	.= ' OR (f.fk_soc = '.((int) $id).')';
							}
							$sql .= ')';
						} else {
							$sql	.= ' AND f.fk_soc = '.$object->id;
						}
						$sql	.= empty($show_invoice_payed) ? ' AND f.paye = 0' : '';
						if (!empty($show_invoice_abandoned)) {
							$sql	.= ' AND f.fk_statut > 0';	// No draft invoice
						} else {
							$sql	.= ' AND f.fk_statut IN ('.Facture::STATUS_VALIDATED.','.Facture::STATUS_CLOSED.')';
						}
						if (getDolGlobalInt('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
							$sql	.= ' AND f.type IN ('.Facture::TYPE_STANDARD.','.Facture::TYPE_REPLACEMENT.','.Facture::TYPE_CREDIT_NOTE.','.Facture::TYPE_SITUATION.')';
						} else {
							$sql	.= ' AND f.type IN ('.Facture::TYPE_STANDARD.','.Facture::TYPE_REPLACEMENT.','.Facture::TYPE_CREDIT_NOTE.','.Facture::TYPE_DEPOSIT.','.Facture::TYPE_SITUATION.')';
						}
						$sql	.= ' GROUP BY f.rowid';
						$sql	.= ', f.datef';
						$sql	.= ', f.ref';
						$sql	.= (!empty($this->show_payment_deadline) ? ', f.date_lim_reglement' : '');
						$sql	.= ', f.ref_client';
						$sql	.= ', f.total_ttc';
						$sql	.= ', f.multicurrency_code';
						$sql	.= ', f.multicurrency_tx';
						$sql	.= ', f.multicurrency_total_ttc';
						$sql	.= ', f.type';
						$sql	.= ', pf.multicurrency_code';
						$sql	.= ', pf.paiement_multicurrency_tx';
						$sql	.= ', rc.amount_creditnote';
						$sql	.= ', rc.multicurrency_amount_creditnote';
						$sql	.= ', rc2.amount_creditused';
						$sql	.= ', rc2.multicurrency_amount_creditused';
						$sql	.= (!empty($add_product_tags) ? ', pt.tags' : '');
						$sql	.= (!empty($this->factCodeExf) ? ', ef.'.$this->factCodeExf : '');
						$sql	.= ' ORDER BY f.datef '.$this->orderby.', f.ref '.$this->orderby;
					} else if ($this->export_type == 'Supplier') {	// Get lines of the supplier account statut
						$sql	= 'SELECT f.datef AS date';
						$sql	.= ', f.ref AS label';
						$sql	.= ', f.ref_supplier AS label_externe';
						$sql	.= (!empty($this->show_payment_deadline) ? ', f.date_lim_reglement AS date_limite' : '');
						$sql	.= ', f.total_ttc AS total_amount';
						$sql	.= ', COALESCE(f.multicurrency_code, "'.$conf->currency.'") AS facture_multicurrency_code';
						$sql	.= ', f.multicurrency_tx AS facture_multicurrency_tx';
						$sql	.= ', f.multicurrency_total_ttc AS multicurrency_total_amount';
						$sql	.= ', f.type AS invoicetype';
						$sql	.= ', pf.amount_payed';
						$sql	.= ', COALESCE(pf.multicurrency_code, "'.$conf->currency.'") AS paiement_multicurrency_code';
						$sql	.= ', pf.paiement_multicurrency_tx, pf.multicurrency_amount_payed';
						$sql	.= ', pf.count_amount_payed';
						$sql	.= ', rc.amount_creditnote';
						$sql	.= ', rc.multicurrency_amount_creditnote';
						$sql	.= ', rc2.amount_creditused';
						$sql	.= ', rc2.multicurrency_amount_creditused';
						$sql	.= (!empty($add_product_tags) ? ', pt.tags AS product_tags' : '');
						$sql	.= ' FROM '.$this->db->prefix().'facture_fourn AS f';
						$sql	.= ' LEFT JOIN (';
						$sql	.= '	SELECT sre.fk_facturefourn AS fk_facture_source, SUM(sre.amount) as amount_payed, sre.multicurrency_code,';
						$sql	.= '	sre.multicurrency_tx AS paiement_multicurrency_tx, count(sre.amount) AS count_amount_payed,';
						$sql	.= '	SUM(sre.multicurrency_amount) AS multicurrency_amount_payed';
						$sql	.= '	FROM '.$this->db->prefix().'paiementfourn_facturefourn AS sre';
						$sql	.= '	GROUP BY sre.fk_facturefourn';
						$sql	.= ') AS pf ON pf.fk_facture_source = f.rowid';
						$sql	.= ' LEFT JOIN (';
						$sql	.= '	SELECT sre.fk_invoice_supplier_source AS fk_facture_source, SUM(sre.amount_ttc) AS amount_creditnote, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditnote';
						$sql	.= '	FROM '.$this->db->prefix().'societe_remise_except AS sre';
						$sql	.= '	GROUP BY sre.fk_invoice_supplier_source';
						$sql	.= ') AS rc ON rc.fk_facture_source = f.rowid';
						$sql	.= ' LEFT JOIN (';
						$sql	.= '	SELECT sre.fk_invoice_supplier AS fk_facture, SUM(sre.amount_ttc) AS amount_creditused, SUM(sre.multicurrency_amount_ttc) AS multicurrency_amount_creditused';
						$sql	.= '	FROM '.$this->db->prefix().'societe_remise_except AS sre';
						$sql	.= '	GROUP BY sre.fk_invoice_supplier';
						$sql	.= ') AS rc2 ON rc2.fk_facture = f.rowid';
						if (!empty($add_product_tags)) {
							$sql	.= ' LEFT JOIN (';
							$sql	.= "	SELECT ffd.fk_facture_fourn, GROUP_CONCAT(DISTINCT c.label SEPARATOR '] [') AS tags";
							$sql	.= '	FROM '.$this->db->prefix().'facture_fourn_det AS ffd';
							$sql	.= '	LEFT JOIN '.$this->db->prefix().'categorie_product AS cp ON cp.fk_product = ffd.fk_product';
							$sql	.= '	LEFT JOIN '.$this->db->prefix().'categorie AS c ON c.rowid = cp.fk_categorie';
							$sql	.= '	GROUP BY ffd.fk_facture_fourn';
							$sql	.= ' ) AS pt ON pt.fk_facture_fourn = f.rowid';
						}
						$sql	.= ' WHERE f.entity IN ('.getEntity('facture_fourn').')';
						$sql	.= ' AND f.datef >= "'.dol_print_date($date_start, 'dayrfc').'"';
						$sql	.= ' AND f.datef <= "'.dol_print_date($date_end, 'dayrfc').'"';
						if (!empty($show_subsidiaries)) {
							$sql	.= ' AND ((f.fk_soc = '.((int) $object->id).')';
							foreach ($idssubsidiaries as $id) {
								$sql	.= ' OR (f.fk_soc = '.((int) $id).')';
							}
							$sql .= ')';
						} else {
							$sql	.= ' AND f.fk_soc = '.$object->id;
						}
						$sql	.= empty($show_invoice_payed) ? ' AND f.paye = 0' : '';
						if (!empty($show_invoice_abandoned)) {
							$sql	.= ' AND f.fk_statut > 0';	// No draft invoice
						} else {
							$sql	.= ' AND f.fk_statut IN ('.FactureFournisseur::STATUS_VALIDATED.','.FactureFournisseur::STATUS_CLOSED.')';
						}
						$sql	.= ' GROUP BY f.rowid';
						$sql	.= ' , f.datef';
						$sql	.= ' , f.ref';
						$sql	.= ' , f.ref_supplier';
						$sql	.= (!empty($this->show_payment_deadline) ? ', f.date_lim_reglement' : '');
						$sql	.= ', f.total_ttc';
						$sql	.= ', f.multicurrency_code';
						$sql	.= ', f.multicurrency_tx';
						$sql	.= ', f.multicurrency_total_ttc';
						$sql	.= ', f.type';
						$sql	.= ', pf.multicurrency_code';
						$sql	.= ', pf.paiement_multicurrency_tx';
						$sql	.= !empty($add_product_tags) ? ', pt.tags' : '';
						$sql	.= ' ORDER BY f.datef '.$this->orderby.', f.ref '.$this->orderby;
					}
					$resql	= $this->db->query($sql);
					if (empty($resql)) {
						$this->error	= $this->db->error();
						return 0;
					}
					$nblignes			= $this->db->num_rows($resql);
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
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->name).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatut'.$this->export_type));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->name).' '.$outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatut'.$this->export_type).' '.$outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatutPeriod', dol_print_date($date_start, 'daytext', false, $outputlangs), dol_print_date($date_end, 'daytext', false, $outputlangs)));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
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
					// pour mettre ou non une couleur en arriere plan sur les lignes de facture
					if (!empty($this->colorLine)) {
						$rgbcolorarray	= colorHexToRgb($this->colorLine, false, true);
						$pdf->SetFillColorArray($rgbcolorarray);
						$color_back = true;
					} else {
						$color_back	= false;
					}
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Define width and position of notes frames
					$this->larg_util_txt	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite + ($this->Rounded_rect * 2) + 2);
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
					// Define width and position of main table columns
					if (empty($this->show_payment_deadline))	$this->larg_datelim	= 0;
					// Largeur variable suivant la place restante
					$this->larg_desc		= $this->larg_util_cadre - ($this->larg_date + $this->larg_datelim + $this->larg_totalttc + $this->larg_totalpaid + $this->larg_remaining);
					$this->tableau			= array('date'		=> array('col' => $this->num_date,		'larg' => $this->larg_date,			'posx' => 0),
													'desc'		=> array('col' => $this->num_desc,		'larg' => $this->larg_desc,			'posx' => 0),
													'datelim'	=> array('col' => $this->num_datelim,	'larg' => $this->larg_datelim,		'posx' => 0),
													'totalttc'	=> array('col' => $this->num_totalttc,	'larg' => $this->larg_totalttc,		'posx' => 0),
													'totalpaid'	=> array('col' => $this->num_totalpaid,	'larg' => $this->larg_totalpaid,	'posx' => 0),
													'remaining'	=> array('col' => $this->num_remaining,	'larg' => $this->larg_remaining,	'posx' => 0)
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
						}
					}
					$this->posxcol1	= $this->marge_gauche;
					$this->posxcol2	= $this->posxcol1 + $this->largcol1;
					$this->posxcol3	= $this->posxcol2 + $this->largcol2;
					$this->posxcol4	= $this->posxcol3 + $this->largcol3;
					$this->posxcol5	= $this->posxcol4 + $this->largcol4;
					$this->posxcol6	= $this->posxcol5 + $this->largcol5;
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
						}
					}
					$this->larg_tabtotal					= $this->larg_util_cadre - ($this->larg_desc + $this->larg_date + $this->larg_datelim);
					$this->posxtabtotal						= $this->page_largeur - $this->marge_droite - $this->larg_tabtotal;
					// Calculs de positions
					$this->tab_hl							= 4;
					$this->decal_round						= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
					$head									= $this->_pagehead($pdf, $object, 1, $outputlangs);
					$hauteurhead							= $head['totalhead'];
					$hauteurcadre							= $head['hauteurcadre'];
					$tab_top								= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
					$tab_top_newpage						= (empty($this->small_head2) ? $hauteurhead - $hauteurcadre : 17);
					$this->ht_top_table						= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
					$heightforheader						= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
					$ht_coltotal							= $this->_tableau_tot($pdf, $object, $this->marge_haute, $outputlangs, 1);
					$heightforinfotot						= $ht_coltotal + pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $this->marge_haute, $outputlangs, $this->emetteur, $this->listfreet, 0, 1, $this->horLineStyle);
					$this->heightforfooter					= $this->_pagefoot($pdf, $object, $outputlangs, 1);
					$tab_top								+= 	$this->tab_hl * 0.5;
					$nexY									= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
					$this->total_totalamount				= 0;
					$this->total_multicurrencytotalamount	= 0;
					$this->total_amountrule					= 0;
					$this->total_multicurrencyamountrule	= 0;
					$this->total_balance					= 0;
					$this->total_multicurrencybalance		= 0;
					$consistency_multicurrency				= 1;
					// Loop on each lines
					for ($i = 0; $i < $nblignes; $i++) {
						$line					= $this->db->fetch_object($resql);
						$invoice_ref			= $line->label;
						$nblignes_d				= 0;	// Payment details
						$paiement_detail_list	= array();	// paiement detail list
						if (!empty($show_payment_details)) {
							$datepayment	= '';
							$payment_label	= '';
							$num_paiement	= '';
							$montant_rglt	= 0;
							// Customer
							if ($this->export_type == 'Customer') {
								$sql_d	= 'SELECT p.datep as datepayment, p.ref as label_rglt, c.code as payment_code, c.libelle as payment_label, pf.amount as montant_rglt, p.num_paiement';
								$sql_d	.= ', p.multicurrency_amount AS multicurrency_montant_rglt';
								$sql_d	.= ', f.ref AS num_fac';
								$sql_d	.= ' FROM '.$this->db->prefix().'paiement AS p';
								$sql_d	.= ' JOIN '.$this->db->prefix().'paiement_facture AS pf ON p.rowid = pf.fk_paiement';
								$sql_d	.= ' JOIN '.$this->db->prefix().'facture AS f ON f.rowid = pf.fk_facture';
								$sql_d	.= ' JOIN '.$this->db->prefix().'c_paiement AS c ON p.fk_paiement = c.id';
								$sql_d	.= ' WHERE f.entity IN (' . getEntity('facture') . ')';
								$sql_d	.= ' AND f.ref = "'.$invoice_ref.'" AND pf.fk_paiement = p.rowid';
								$sql_d	.= ' ORDER BY f.ref, p.datep ASC;';
							}
							// Supplier
							if ($this->export_type == 'Supplier') {
								$sql_d	= 'SELECT p.datep as datepayment, p.ref as label_rglt, c.code as payment_code, c.libelle as payment_label, pf.amount as montant_rglt, p.num_paiement';
								$sql_d	.= ', p.multicurrency_amount AS multicurrency_montant_rglt';
								$sql_d	.= ', f.ref AS num_fac';
								$sql_d	.= ' FROM '.$this->db->prefix().'paiementfourn AS p';
								$sql_d	.= ' JOIN '.$this->db->prefix().'paiementfourn_facturefourn AS pf ON p.rowid = pf.fk_paiementfourn';
								$sql_d	.= ' JOIN '.$this->db->prefix().'facture_fourn AS f ON f.rowid = pf.fk_facturefourn';
								$sql_d	.= ' JOIN '.$this->db->prefix().'c_paiement AS c ON p.fk_paiement = c.id';
								$sql_d	.= ' WHERE f.entity IN (' . getEntity('facture_fourn') . ')';
								$sql_d	.= ' AND f.ref = "'.$invoice_ref.'" AND pf.fk_paiementfourn = p.rowid';
								$sql_d	.= ' ORDER BY f.ref, p.datep ASC;';
							}
							$resql_d	= $this->db->query($sql_d);
							if (empty($resql_d)) {
								$this->error	= $this->db->error();
								return 0;
							}
							$nblignes_d	= $this->db->num_rows($resql_d);
						}
						$line->label						.= !empty($add_product_tags) && !empty($line->product_tags) ? ' - ['.$line->product_tags.']' : '';
						$line->label						.= !empty($line->extrafield_invoice) ? ' - ('.$line->extrafield_invoice.')' : '';
						$paiement_detail_list[$invoice_ref]	= array();
						if (!empty($show_payment_details)) {
							for ($j = 0; $j < $nblignes_d; $j++) {
								$line_d	= $this->db->fetch_object($resql_d);
								if (!empty($show_payment_details) && !empty($line_d->payment_label)) {
									$paiement_detail_list[$invoice_ref][]	= array('label'					=> $line_d->payment_label,
																					'num'					=> $line_d->num_paiement,
																					'date'					=> $line_d->datepayment,
																					'montant'				=> $line_d->montant_rglt,
																					'montant_multicurrency'	=> $line_d->multicurrency_montant_rglt,
																					'code'					=> $line_d->payment_code
																					);
								}
							}
						}
						if (!empty($this->add_multicurrency)) {
							$this->currency	= $object->multicurrency_code;
							if ($line->facture_multicurrency_code != $this->currency) {
								$consistency_multicurrency	= 0;	// La facture n'est pas dans la devise du tiers
							}
							if (!empty($line->amount_payed) && $line->paiement_multicurrency_code != $this->currency) {
								$consistency_multicurrency	= 0;	// Le règlement n'est pas dans la devise du tiers
							}
							if (empty($consistency_multicurrency)) {	// Incohérence trouvé => inhibition de l'affichage en devise et arrêt de la boucle
								$this->add_multicurrency	= 0;
								$this->currency				= $conf->currency;
								break;
							}
						} else {
							$this->currency	= $conf->currency;
						}
						// 1st column - Total Amount
						$this->total_totalamount				+= $line->total_amount;
						$this->total_multicurrencytotalamount	+= $line->multicurrency_total_amount;
						// 2nd column - Total payed
						if ($line->invoicetype <> 3) {	// Deposit
							$amount_payed				= $line->amount_payed - $line->amount_creditnote + $line->amount_creditused;
							$multicurrency_amount_payed	= $line->multicurrency_amount_payed - $line->multicurrency_amount_creditnote + $line->multicurrency_amount_creditused;
							// }
						} else {
							$amount_payed				= $line->amount_payed + $line->amount_creditused;
							$multicurrency_amount_payed	= $line->multicurrency_amount_payed + $line->multicurrency_amount_creditused;
						}
						$this->total_amountrule					+= $amount_payed;
						$this->total_multicurrencyamountrule	+= $multicurrency_amount_payed;
						// 3rd column - Total Remain to pay
						$balance								= $line->total_amount - $amount_payed;
						$multicurrency_balance					= $line->multicurrency_total_amount - $multicurrency_amount_payed;
						$this->total_balance					+= $balance;
						$this->total_multicurrencybalance		+= $multicurrency_balance;
						$curY									= $nexY;
						$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
						$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
						if (empty($this->hide_top_table)) {
							$pdf->setTopMargin($tab_top_newpage + $this->ht_top_table + $this->decal_round);
						} else {
							$pdf->setTopMargin($tab_top_newpage);
						}
						$pdf->setPageOrientation('', 1, $this->heightforfooter);	// Edit the bottom margin of current page to set it.
						if ($color_back === true) {
							$pdf->SetFillColorArray($rgbcolorarray);
						}
						$pageposbefore				= $pdf->getPage();
						$showpricebeforepagebreak	= 1;
						// Description of line => printing
						$pageposdesc				= $pdf->getPage();
						$heightline					= $pdf->getStringHeight($this->tableau['desc']['larg'], $label);
						$this->heightline			= $this->tab_hl > $heightline ? $this->tab_hl : $heightline;
						if ($this->export_type == 'Supplier') {
							$label	= !empty($this->defaultRefSupplier) ? $line->label_externe.' ('.$line->label.')' : $line->label;
						} else {
							$label	= !empty($show_thirdparty_ref) ? $line->label.' ('.$line->label_externe.')' : $line->label;
						}
						$pdf->MultiCell($this->tableau['desc']['larg'], $this->heightline, $label, '', 'L', $color_back, 1, $this->tableau['desc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						$pageposafter	= $pdf->getPage();
						$posyafter		= $pdf->GetY();
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								$showpricebeforepagebreak	= 0;
							}
						} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
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
							if ($curY > ($this->page_hauteur - $this->heightforfooter - $this->tab_hl)) {
								$pdf->setPage($pageposafter);
								$curY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
							} else {
								$pdf->setPage($pageposdesc);
							}
						}
						$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
						// Date
						$date	= dol_print_date(dol_stringtotime($line->date), 'day', false, $outputlangs);
						$pdf->MultiCell($this->tableau['date']['larg'], $this->heightline, $date, '', 'C', $color_back, 1, $this->tableau['date']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Date limite
						if (!empty($this->show_payment_deadline)) {
							$datelim	= dol_print_date(dol_stringtotime($line->date_limite), 'day', false, $outputlangs);
							$pdf->MultiCell($this->tableau['datelim']['larg'], $this->heightline, $datelim, '', 'C', $color_back, 1, $this->tableau['datelim']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						}
						// Total amount
						$amount			= pdf_InfraSPlus_price($object, ($this->add_multicurrency ? $line->multicurrency_total_amount : $line->total_amount), $outputlangs, 0, 0, 'T');
						$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->heightline, $amount, '', 'R', $color_back, 1, $this->tableau['totalttc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Amount rule
						$amount_payed	= pdf_InfraSPlus_price($object, ($this->add_multicurrency ? $multicurrency_amount_payed : $amount_payed), $outputlangs, 0, 0, 'T');
						$pdf->MultiCell($this->tableau['totalpaid']['larg'], $this->heightline, $amount_payed, '', 'R', $color_back, 1, $this->tableau['totalpaid']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// Balance
						$balance		= pdf_InfraSPlus_price($object, ($this->add_multicurrency ? $multicurrency_balance : $balance), $outputlangs, 0, 0, 'T');
						$pdf->MultiCell($this->tableau['remaining']['larg'], $this->heightline, $balance, '', 'R', $color_back, 1, $this->tableau['remaining']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
						// debut des lignes de reglement
						$nexY			+= $this->lineSep_hight / 2;
						if (isset($paiement_detail_list[$invoice_ref])) {
							$nblignespayment	= count($paiement_detail_list[$invoice_ref]);
							foreach ($paiement_detail_list[$invoice_ref] as $k => $paiement_detail) {
								$datepayment				= $paiement_detail['date'];
								$payment_label				= $outputlangs->transnoentities('PaymentTypeShort'.$paiement_detail['code']) != ('PaymentTypeShort'.$paiement_detail['code']) ? $outputlangs->transnoentities('PaymentTypeShort'.$paiement_detail['code']) : $paiement_detail['code'];
								$num_paiement				= $paiement_detail['num'];
								$montant_rglt				= $paiement_detail['montant'];
								$multicurrency_montant_rglt	= $paiement_detail['montant_multicurrency'];
								$curY						= $nexY;
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
								$pdf->MultiCell($this->tableau['desc']['larg'], $this->heightline, $payment_label.' '.$num_paiement, '', 'L', 0, 1, $this->tableau['desc']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								$pageposafter				= $pdf->getPage();
								$posyafter					= $pdf->GetY();
								$pageposafter				= $pdf->getPage();
								$posyafter					= $pdf->GetY();
								if ($pageposafter > $pageposbefore) {	// There is a pagebreak
									if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
										if ($i == ($nblignes - 1)) {	// No more lines, and no space left to show total, so we create a new page
											$pdf->AddPage('', '', true);
											$pdf->setPage($pageposafter + 1);
										}
									} else {
										$showpricebeforepagebreak	= 0;
									}
								} elseif ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $heightforinfotot))) {	// There is no space left for total+free text
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
									if ($curY > ($this->page_hauteur - $this->heightforfooter - $this->tab_hl)) {
										$pdf->setPage($pageposafter);
										$curY	= $tab_top_newpage + ($this->hide_top_table ? $this->decal_round : $this->ht_top_table + $this->decal_round);
									} else {
										$pdf->setPage($pageposdesc);
									}
								}
								$pdf->SetFont('', '', $default_font_size - 1);	// On repositionne la police par defaut
								// Date
								$date	= dol_print_date(dol_stringtotime($datepayment), 'day', false, $outputlangs);
								$pdf->MultiCell($this->tableau['date']['larg'], $this->heightline, $date, '', 'C', 0, 1, $this->tableau['date']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								// Amount rule
								$montant_rglt		= pdf_InfraSPlus_price($object, ($this->add_multicurrency ? $multicurrency_montant_rglt : $montant_rglt), $outputlangs, 0, 0, 'T');
								$pdf->MultiCell($this->tableau['totalpaid']['larg'], $this->heightline, $montant_rglt, '', 'R', 0, 1, $this->tableau['totalpaid']['posx'], $curY, true, 0, 0, false, 0, 'M', false);
								$nexY				+= $this->lineSep_hight / 2;
							}	// fin des lignes de reglement
						}
						// Add dash or space between line
						if (!empty($this->dash_between_line) && $i < ($nblignes - 1)) {
							$pdf->setPage($pageposafter);
							$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1, $this->horLineStyle);
							$nexY	+= 2;
						} else {
							$nexY	+= $this->lineSep_hight;
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
						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
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
					$posytot	= $this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs, 0);
					$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
					$posy		= pdf_InfraSPlus_free_text($pdf, $object, $this->formatpage, $this->marge_gauche, $posytot, $outputlangs, $this->emetteur, $this->listfreet, 0, 0, $this->horLineStyle);
					$this->_pagefoot($pdf, $object, $outputlangs, 0);
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					global $action;
					$reshook	= $hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);	// Note that $action and $object may have been modified by some hooks
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
					$this->error	= $langs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			} else {
				$this->error	= $outputlangs->transnoentities('ErrorConstantNotDefined', 'SOC_OUTPUTDIR');
				return 0;
			}
		}

		/**
		*	Show top header of page.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Societe		$object			Object shown in PDF
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
												0, $this->title_size, $this->titlekey, 0, $this->datesbold, 0, 1, 1,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, 0, $this->use_iso_location, '', $this->typeadr, '', $this->Rounded_rect,
												'', -2, -2, '', 0, array(), '', -2, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
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
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$title				= $outputlangs->transnoentities($this->titlekey);
			$pdf->MultiCell($w, $this->tab_hl * $this->title_size, $title, '', 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$posy				= $pdf->getY();
			$txtref				= $outputlangs->transnoentities($this->export_type).' : '.$outputlangs->convToOutputCharset($object->name);
			$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', ($this->datesbold ? 'B' : ''), $default_font_size - 2);
			$posy	+= $this->tab_hl;
			$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date(dol_now(), 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl - 0.5;
			$txtdt	= $outputlangs->transnoentities('Period').' : '.dol_print_date($this->exportparameters['date_start'], 'day', false, $outputlangs, true).' - '.dol_print_date($this->exportparameters['date_end'], 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $this->tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 2);
			if ($this->export_type == 'Customer') {
				$thirdparty_language_key	= 'CustomerCode';
				$thirdparty_code			= $object->code_client;
			} elseif ($this->export_type == 'Supplier') {
				$thirdparty_language_key	= 'SupplierCode';
				$thirdparty_code			= $object->code_fournisseur;
			}
			$posy	+= $this->tab_hl - 0.5;
			$txtcc	= $outputlangs->transnoentities($thirdparty_language_key).' : '.$outputlangs->convToOutputCharset($thirdparty_code);
			$pdf->MultiCell($w, $this->tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $this->tab_hl - 0.5;
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			if (!empty($showaddress)) {
				$arrayidcontact	= array('I' => '',
										'E' => '',
										'L' => ''
										);
				$addresses		= array();
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, '', 0, $this->emetteur, 0, 'accountStatus', 0, 0, -2, -2, '', 0);
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
		*	@param		Societe		$object			Object shown in PDF
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
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Societe		$object			Object shown in PDF
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
			$default_font_size		= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 2);
			// Output Rounded Rectangle
			if (empty($hidetop) || $pagenb == 1) {
				if ($pagenb == 1) {
					$infocurrency	= !empty($this->hide_info_cur) ? '' : $outputlangs->transnoentities('AmountInCurrency', $outputlangs->transnoentitiesnoconv('Currency'.$this->currency));
					$pdf->MultiCell($pdf->GetStringWidth($infocurrency) + 3, 2, $infocurrency, '', 'R', 0, 1, $this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($infocurrency) + 3) - $this->decal_round, $tab_top - $this->tab_hl, true, 0, 0, false, 0, 'M', false);
				}
				if (!empty($this->title_bg)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', 'DF', $this->tblLineStyle, $this->bg_color);
				} else if (!empty($this->showtblline) && empty($this->desc_full_line)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $this->ht_top_table, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
				if (!empty($this->showtblline) && empty($this->desc_full_line)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top + $this->ht_top_table + $this->bgLineW, $this->larg_util_cadre, $tab_height - ($this->ht_top_table + $this->bgLineW), $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				} else {
					$pdf->line($this->marge_gauche, $tab_top + $tab_height, $this->marge_gauche + $this->larg_util_cadre, $tab_top + $tab_height, $this->horLineStyle);
				}
			} else if (!empty($this->showtblline) && empty($this->desc_full_line)) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
			} else {
				if (!empty($this->showtblline) && empty($this->desc_full_line)) {
					$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->larg_util_cadre, $tab_height, $this->Rounded_rect, '1111', null, $this->tblLineStyle);
				}
			}
			if ($object->statut == 0 && (!empty($this->draft_watermark))) {
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
			if ($this->showverline && !$this->desc_full_line) {
				// Colonnes
				if ($this->posxcol2 > $this->posxcol1 && $this->posxcol2 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol2,	$tab_top, $this->posxcol2,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol3 > $this->posxcol2 && $this->posxcol3 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol3,	$tab_top, $this->posxcol3,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol4 > $this->posxcol3 && $this->posxcol4 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol4,	$tab_top, $this->posxcol4,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol5 > $this->posxcol4 && $this->posxcol5 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol5,	$tab_top, $this->posxcol5,	$tab_top + $tab_height, $this->verLineStyle);
				}
				if ($this->posxcol6 > $this->posxcol5 && $this->posxcol6 < ($this->marge_gauche + $this->larg_util_cadre)) {
					$pdf->line($this->posxcol6,	$tab_top, $this->posxcol6,	$tab_top + $tab_height, $this->verLineStyle);
				}
			}
			// En-tête tableau
			$pdf->SetFont('', 'B', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			if (empty($hidetop) || $pagenb == 1) {
				$pdf->MultiCell($this->tableau['date']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatutDate'), '', 'C', 0, 1, $this->tableau['date']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['desc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatutLabel'), '', 'C', 0, 1, $this->tableau['desc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				if (!empty($this->show_payment_deadline)) {
					$pdf->MultiCell($this->tableau['datelim']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatutLimitDate'), '', 'C', 0, 1, $this->tableau['datelim']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				}
				$pdf->MultiCell($this->tableau['totalttc']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatutTotalAmountTTC'), '', 'C', 0, 1, $this->tableau['totalttc']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['totalpaid']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatutAmountRuleTTC'), '', 'C', 0, 1, $this->tableau['totalpaid']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
				$pdf->MultiCell($this->tableau['remaining']['larg'], $this->ht_top_table, $outputlangs->transnoentities('ExtraitCompteClientPDFAccountStatutBalanceTTC'), '', 'C', 0, 1, $this->tableau['remaining']['posx'], $tab_top, true, 0, 0, true, $this->ht_top_table, 'M', false);
			}
		}

		/**
		*	Show total to pay
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		Societe		$object			Object shown in PDF
		*	@param		int			$posy			y
		*	@param		Translate	$outputlangs	Objet langs
		*	@return		int							Position pour suite
		**/
		protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs, $calculseul = 0)
		{
			global $conf;

			$pdf->startTransaction();
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$posytabtot			= $posy + $this->ht_space_tot;
			$tabtot_hl			= $this->tab_hl;
			$pdf->SetFont('', '', $default_font_size - 1);
			!empty($this->title_bg) ? $pdf->SetTextColor($this->txtcolor[0], $this->txtcolor[1], $this->txtcolor[2]) : $pdf->SetTextColor($this->bodytxtcolor[0], $this->bodytxtcolor[1], $this->bodytxtcolor[2]);
			// Tableau total
			$larg_tabtotal		= $this->larg_tabtotal;
			$posxtabtotal		= $this->posxtabtotal;
			$pdf->RoundedRect($posxtabtotal, $posytabtot, $larg_tabtotal, $tabtot_hl, $this->Rounded_rect > $tabtot_hl / 2 ? $tabtot_hl / 2 : $this->Rounded_rect, '1111', 'DF', $this->bgLineStyle, $this->bg_color);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			// Total amount
			$total_totalamount	= $this->add_multicurrency ? $this->total_multicurrencytotalamount : $this->total_totalamount;
			$pdf->MultiCell($this->tableau['totalttc']['larg'], $tabtot_hl, pdf_InfraSPlus_price($object, $total_totalamount, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $this->tableau['totalttc']['posx'], $posytabtot, true, 0, 0, false, 0, 'M', false);
			// Amount rule HT
			$total_amountrule	= $this->add_multicurrency ? $this->total_multicurrencyamountrule : $this->total_amountrule;
			$pdf->MultiCell($this->tableau['totalpaid']['larg'], $tabtot_hl, pdf_InfraSPlus_price($object, $total_amountrule, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $this->tableau['totalpaid']['posx'], $posytabtot, true, 0, 0, false, 0, 'M', false);
			// Balance
			$total_balance		= $this->add_multicurrency ? $this->total_multicurrencybalance : $this->total_balance;
			$pdf->MultiCell($this->tableau['remaining']['larg'], $tabtot_hl, pdf_InfraSPlus_price($object, $total_balance, $outputlangs, !empty($this->show_tot_Cur_Symb), 0, 'T'), '', 'R', 0, 1, $this->tableau['remaining']['posx'], $posytabtot, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', '', $default_font_size - 1);
			$posytabtot			= $pdf->GetY() + 1;
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
		*	Show footer of page. Need this->emetteur object
		*
		*	@param		TCPDF			$pdf			The PDF factory
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		Societe		$fromcompany	Object company
		*	@param		int			$marge_basse	Margin bottom we use for the autobreak
		*	@param		int			$marge_gauche	Margin left
		*	@param		int			$page_hauteur	Page height
		*	@param		Societe		$object			Object shown in PDF
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
