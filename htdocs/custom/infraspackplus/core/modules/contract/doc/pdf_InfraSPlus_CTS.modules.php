<?php
/************************************************
* Copyright (C) 2016-2025 Sylvain Legrand
* GPL v3
************************************************/

/*
 * Objectif : Comportement identique à pdf_InfraSPlus_PJ_Docs
 * - Récupération config PDF via pdf_InfraSPlus_getValues
 * - Instance TCPDF
 * - Récupération éventuelle de fichiers fournis par hook (filesArray)
 * - Sinon : sélection des modèles PDF dans DOL_DATA_ROOT/.../infraspackplus/specialfiles
 *   filtrés par INFRASPLUS_PDF_SPECIAL_FILES (liste csv)
 *   + constante d’activation : INFRASPLUS_PDF_SPECIAL_FILE_{ELEMENT}_{PDFNAME}_AUTO
 *   (ELEMENT = strtoupper($object->element), PDFNAME = nom du pdf sans extension, uppercase)
 * - Vérifie qu’un homonyme .php existe dans custom/infraspackplus/core/modules/specialfiles
 * - completeFileArrayWithDatabaseInfo => récupération rowid ECM
 * - pdf_InfraSPlus_files fusionne et déclenche la fonction pdf_InfraSPlus_Merge_<PDFNAME>() définie dans chaque fichier .php (ex: Contrat_GAZ.php)
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

class pdf_InfraSPlus_CTS extends ModelePDFContract
{
		public $db;
		public $name;
		public $description;
		public $defaulttemplate;
		public $option_logo;
		public $option_multilang;
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
		public $files;
		public $horLineStyle = array();
		public $only_ht;
		public $tableau = array();	// Array of table to print
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;

	public function __construct($db)
	{
		global $langs;

		$langs->loadLangs(array('main', 'contracts', 'companies', 'infraspackplus@infraspackplus', 'specialfiles@infraspackplus'));

		pdf_InfraSPlus_getValues($this);
		$this->db						= $db;
		$this->name						= $langs->trans('PDFInfraSPlusCTSDossierName');
		$this->description				= $langs->trans('PDFInfraSPlusCTSDossierDescription');
		$this->update_main_doc_field	= 1;
		$this->defaulttemplate			= getDolGlobalString('CONTRACT_ADDON_PDF', '');
		$this->option_logo				= 1;
		$this->option_multilang			= 1;
	}

	/**
	 * Génération du PDF contrat (dossier) en assemblant les specialfiles comme pour les projets.
	 * @param  Contrat    $object
	 * @param  Translate  $outputlangs
	 * @param  string     $srctemplatepath
	 * @param  int        $hidedetails
	 * @param  int        $hidedesc
	 * @param  int        $hideref
	 * @return int 1 OK, 0 KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $user, $langs, $conf, $db, $hookmanager;

		dol_syslog('InfraSPlus_CTS::write_file outputlangs->defaultlang='.(is_object($outputlangs)?$outputlangs->defaultlang:'null'));

		if (!is_object($outputlangs)) {
			$outputlangs	= $langs;
		}
		if (!empty($this->use_fpdf)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}
		$outputlangs->loadLangs(array('main','dict','contracts','companies','infraspackplus@infraspackplus','specialfiles@infraspackplus'));
		$filesufixe			= (empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_CTS')) ? '' : '_CTS';
		// Répertoire de sortie
		$baseDir			= !empty($conf->contrat->multidir_output[$conf->entity]) ? $conf->contrat->multidir_output[$conf->entity] : $conf->contrat->dir_output;
		if (empty($baseDir)) {
			$this->error	= $outputlangs->trans('ErrorConstantNotDefined', 'CONTRACT_OUTPUTDIR');
			return 0;
		}

		$objectref			= dol_sanitizeFileName($object->ref);
		if (preg_match('/specimen/i', $objectref)) {
			$dir			= $baseDir;
			$file			= $dir.'/SPECIMEN.pdf';
		} else {
			$dir			= $baseDir.'/'.$objectref;
			$file			= $dir.'/'.$objectref.$filesufixe.'.pdf';
		}

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
				return 0;
			}
		}
		if (!file_exists($dir)) {
			$this->error		= $outputlangs->trans('ErrorCanNotCreateDir', $dir);
			return 0;
		}
		// Hooks
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager		= new HookManager($db);
		}
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters				= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
		global $action;
		$hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);
		$this->files			= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : array();
		$hookmanager->resArray	= array();
		// Instance PDF
		$pdf					= pdf_InfraSPlus_getInstance($this->format, 'mm', 'P');
		$default_font_size		= pdf_getPDFFontSize($outputlangs);
		$pdf->SetAutoPageBreak(1, 0);
		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont($this->font);
		$pdf->setHtmlVSpace(array('p'=>array(1=>array('h'=>0.0001,'n'=>1)), 'ul'=>array(0=>array('h'=>0.0001,'n'=>1))));
		$pdf->Open();
		$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
		$pdf->SetSubject($outputlangs->transnoentities('Contract'));
		$pdf->SetCreator('Dolibarr '.DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Contract'));
		$pdf->setPageOrientation('', 1, 0);
		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);
		// 1) Fichiers fournis par hook
		if (is_array($this->files) && count($this->files) > 0) {
			pdf_InfraSPlus_files($pdf, $this->files, 1, $object, $outputlangs, $this->formatpage);
		}
		// 2) Recherche des specialfiles configurés
		else {
			$paramspecialfiles		= !empty($conf->global->INFRASPLUS_PDF_SPECIAL_FILES) ? $conf->global->INFRASPLUS_PDF_SPECIAL_FILES : '';
			if (!empty($paramspecialfiles)) {
				$paramspecialfiles	= array_map('trim', explode(',', $paramspecialfiles));

				// Répertoire des PDF "modèles"
				$dirpdfs			= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/specialfiles';
				$listpdfs			= dol_dir_list($dirpdfs, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);

				// Liste des scripts (ABE.php, Contrat_GAZ.php, etc.)
				$listspecialfiles	= dol_dir_list(dol_buildpath('/infraspackplus/core/modules/specialfiles', 0), 'files', 0, '\.php$', null, 'name', SORT_ASC, 0, 0, '', 0);
				$listspecialfiles	= array_column($listspecialfiles, 'name');
				$filesArray			= array();
				foreach ($listpdfs as $pdfFile) {
					if (empty($pdfFile['name'])) {
						continue;
					}
					$pdfname		= pathinfo($pdfFile['name'], PATHINFO_FILENAME); // ex: Contrat_GAZ
					if (!in_array($pdfname, $paramspecialfiles)) {
						continue;
					}
					// Clé d'activation harmonisée avec le module Projet
					// INFRASPLUS_PDF_SPECIAL_FILE_CONTRAT_CONTRAT_GAZ_AUTO (object->element = 'contrat')
					$key			= 'INFRASPLUS_PDF_SPECIAL_FILE_'.strtoupper($object->element).'_'.strtoupper($pdfname).'_AUTO';
					$enabled		= !empty($conf->global->$key) ? (int)$conf->global->$key : 0;
					if (empty($enabled)) {
						continue;
					}
					// Vérifie que le .php correspondant existe
					if (!in_array($pdfname.'.php', $listspecialfiles)) {
						continue;
					}
					$filesArray[]	= $pdfFile;
				}
				if (!empty($filesArray)) {
					completeFileArrayWithDatabaseInfo($filesArray, 'infraspackplus/specialfiles');

					$arrayFilesID	= array();
					foreach ($filesArray as $row) {
						if (!empty($row['rowid'])) {
							$arrayFilesID[] = $row['rowid'];
						}
					}
					if (!empty($arrayFilesID)) {
						pdf_InfraSPlus_files($pdf, $arrayFilesID, 1, $object, $outputlangs, $this->formatpage);
					}
					$pdf_files_after	= glob($dir.'/*.pdf');
					$pdf_files_after	= is_array($pdf_files_after) ? $pdf_files_after : array();
					if (empty(array_diff($pdf_files_after, $pdf_files_before))) {
						dol_syslog('InfraSPlus_CTS: Aucun specialfile n\'a généré de PDF pour '.$objectref, LOG_WARNING);
						setEventMessages($outputlangs->transnoentities('WarningNoSpecialFilePDFGenerated', $objectref), null, 'warnings');
					}
				}
			}
		}
		$nbPage		= $pdf->getNumPages();
		$pdf->Close();
		if (!empty($nbPage)) {
			$pdf->Output($file, 'F');
		}
		// Hook after
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters			= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
		$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);
		if (!empty($nbPage)) {
			if (!empty($this->main_umask)) {
				@chmod($file, octdec($this->main_umask));
			}
			$this->result	= array('fullpath'=>$file);
		}
		return 1;
	}
}