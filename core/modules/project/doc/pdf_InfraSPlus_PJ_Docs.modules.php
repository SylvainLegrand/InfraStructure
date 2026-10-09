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
	* 	\file		./infraspackplus/core/modules/project/doc/pdf_InfraSPlus_PJ_Docs.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF project => creation of all the InfraS special files enabled for the project
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	/************************************************
	*	Class to generate the InfraS special files of a project
	************************************************/
	class pdf_InfraSPlus_PJ_Docs extends ModelePDFProjects
	{
		public $db;
		public $name;
		public $description;
		public $titlekey;
		public $defaulttemplate;
		public $option_logo;
		public $option_tva;
		public $option_codeproduitservice;
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
		public $files = [];
		public $horLineStyle = [];
		public $only_ht;
		public $tableau = [];	// Array of table to print
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $Prj_TimeStamp;
		public $deposit_are_payment;
		public $nodatelinked;
		public $vcc_brand = 0;	// 1 when the instance brand is "vcc" : the only one where this model is listed and usable

		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'dict', 'bills', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'projects', 'trips', 'agenda', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db 							= $db;
			$this->name							= $langs->trans('PDFInfraSPlusProjectDocsName');
			$this->description					= $langs->trans('PDFInfraSPlusProjectDocsDescription');
			$this->titlekey						= 'PDFInfraSPlusProjectDocsTitle';
			$this->update_main_doc_field		= 0;	// The special files are the documents, not this merged file
			$this->defaulttemplate				= getDolGlobalString('PROJECT_ADDON_PDF', '');
			$this->deposit_are_payment			= getDolGlobalInt('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS', 0);
			$this->Prj_TimeStamp				= getDolGlobalInt('INFRASPLUS_PDF_PROJECT_TIMESTAMP', 0);
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->vcc_brand					= infraspackplus_getBrand() === 'vcc' ? 1 : 0;
			if (empty($this->vcc_brand)) {
				$this->version					= 'development';	// Hidden from the models list (Projects setup) unless MAIN_FEATURES_LEVEL >= 2
			}
		}

		/**
		*	Function to build pdf onto disk : merge of the files chosen in the generation form or, failing that, of the InfraS special files
		*	enabled for the projects (INFRASPLUS_PDF_SPECIAL_FILES filtered by INFRASPLUS_PDF_SPECIAL_FILE_PROJECT_<NAME>_AUTO, with a matching
		*	script in core/modules/specialfiles). The scripts write their own PDF in the project directory ; no page is added to this
		*	document when there is nothing to merge, and no file is then produced
		*
		*	@param		object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		int			$hidedetails		Do not show line details
		*	@param		int			$hidedesc			Do not show desc
		*	@param		int			$hideref			Do not show ref
		*	@return		int								1 = OK, <= 0 KO
		**/
		public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
		{
			global $user, $langs, $conf, $hookmanager;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			if (empty($this->vcc_brand)) {
				$this->error	= $outputlangs->transnoentities('FeatureDisabled');
				return 0;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$baseDir		= !empty($conf->projet->multidir_output[$conf->entity]) ? $conf->projet->multidir_output[$conf->entity] : $conf->projet->dir_output;
			$fileprefix		= '';
			if (!getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME')) {
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_PJ_Docs') ? '' : '_PJ_Docs';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_ADD_PREFIX_TO_PJ_Docs', '');
				$filesufixe	= empty($fileprefix) ? '_PJ_Docs' : '';
			}
			if (empty($baseDir)) {
				$this->error	= $outputlangs->trans('ErrorConstantNotDefined', 'PROJECT_OUTPUTDIR');
				return 0;
			}
			$objectref	= dol_sanitizeFileName($object->ref);
			// Definition of $dir and $file
			if (preg_match('/specimen/i', $objectref) || !empty($object->specimen)) {
				$dir	= $baseDir;
				$file	= $dir.'/SPECIMEN.pdf';
			} else {
				$dir	= $baseDir.'/'.$objectref;
				$file	= $dir.'/'.$fileprefix.$objectref.$filesufixe.'.pdf';
			}
			if (! file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}
			if (! file_exists($dir)) {
				$this->error	= $outputlangs->trans('ErrorCanNotCreateDir', $dir);
				return 0;
			}
			if (! is_object($hookmanager)) {	// Add pdfgeneration hook
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager	= new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters				= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			global $action;
			$reshook				= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			// Files chosen in the generation form : array of ECM rowid, 'none' or nothing
			$this->files			= (!empty($hookmanager->resArray['filesArray']) && is_array($hookmanager->resArray['filesArray'])) ? $hookmanager->resArray['filesArray'] : [];
			$hookmanager->resArray	= [];
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
			$tagvs	= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
			$pdf->setHtmlVSpace($tagvs);
			$pdf->Open();
			$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref).$filesufixe);
			$pdf->SetSubject($outputlangs->transnoentities('Project'));
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset(is_object($user) ? $user->getFullName($outputlangs) : ''));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Project'));
			$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
			// 1) Files chosen in the generation form
			if (count($this->files) > 0) {
				pdf_InfraSPlus_files($pdf, $this->files, 1, $object, $outputlangs, $this->formatpage);
			} else {
				// 2) Special files enabled for the projects
				$paramspecialfiles	= getDolGlobalString('INFRASPLUS_PDF_SPECIAL_FILES', '');
				if (!empty($paramspecialfiles)) {
					$paramspecialfiles	= explode(',', $paramspecialfiles);
					// Directory of the "model" PDF (relative path to DOL_DATA_ROOT : entity prefix in multicompany, like the ECM records)
					$reldirpdfs			= (!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/specialfiles';
					$dirpdfs			= DOL_DATA_ROOT.'/'.$reldirpdfs;
					$listpdfs			= dol_dir_list($dirpdfs, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
					$listspecialfiles	= dol_dir_list(dol_buildpath('/infraspackplus/core/modules/specialfiles', 0), 'files', 0, '\.php$', null, 'name', SORT_ASC, 0, 0, '', 0);
					$listspecialfiles	= array_column($listspecialfiles, 'name');
					$filesArray			= [];
					foreach ($listpdfs as $pdfFile) {
						if (empty($pdfFile['name'])) {
							continue;
						}
						$pdfname	= pathinfo($pdfFile['name'], PATHINFO_FILENAME);
						if (in_array($pdfname, $paramspecialfiles)) {
							$key		= 'INFRASPLUS_PDF_SPECIAL_FILE_'.(strtoupper($object->element)).'_'.(strtoupper($pdfname)).'_AUTO';
							$showfile	= getDolGlobalInt($key, 0);
							if (!empty($showfile) && in_array($pdfname.'.php', $listspecialfiles)) {
								$filesArray[]	= $pdfFile;
							}
						}
					}
					if (!empty($filesArray)) {
						completeFileArrayWithDatabaseInfo($filesArray, $reldirpdfs);
						$arrayFilesID	= [];
						foreach ($filesArray as $row) {
							if (!empty($row['rowid'])) {
								$arrayFilesID[]	= $row['rowid'];
							}
						}
						if (!empty($arrayFilesID)) {
							pdf_InfraSPlus_files($pdf, $arrayFilesID, 1, $object, $outputlangs, $this->formatpage);
						}
					}
				}
			}
			$nbPage	= $pdf->getNumPages();
			$pdf->Close();
			if (!empty($nbPage)) {
				$pdf->Output($file, 'F');
			}
			// Add pdfgeneration hook
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters	= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			global $action;
			$reshook	= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$this->error	= $hookmanager->error;
				$this->errors	= $hookmanager->errors;
			}
			if (!empty($nbPage)) {
				if (!empty($this->main_umask)) {
					@chmod($file, octdec($this->main_umask));
				}
				$this->result	= array('fullpath' => $file);
			}
			return 1;	// Pas d'erreur
		}
	}
