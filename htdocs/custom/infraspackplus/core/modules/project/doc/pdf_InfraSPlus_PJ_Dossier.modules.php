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
	* 	\file		./infraspackplus/core/modules/project/doc/pdf_InfraSPlus_PJ_Dossier.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF project
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
	include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
	include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	if (isModEnabled('propal')) {
		include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	}
	if (isModEnabled('facture')) {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	}
	if (isModEnabled('facture')) {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
	}
	if (isModEnabled('commande')) {
		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	}
	if (isModEnabled('fournisseur')) {
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	}
	if (isModEnabled('fournisseur')) {
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	}
	if (isModEnabled('contrat')) {
		include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	}
	if (isModEnabled('ficheinter')) {
		include_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	}
	if (isModEnabled('deplacement')) {
		include_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
	}
	if (isModEnabled('expensereport')) {
		include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
	}
	if (isModEnabled('agenda')) {
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	}
	if (isModEnabled('ndfp')) {
		dol_include_once('/ndfp/class/ndfp.class.php');
	}
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate the PDF technical file of a project
	************************************************/
	class pdf_InfraSPlus_PJ_Dossier extends ModelePDFProjects
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
		// Documents receipt mode (see isRecudocsMode())
		public $recudocs_mode = 0;
		public $arrRecudocs = [];	// References / dates of the linked documents to list in the header
		// Values returned by the beforePDFCreation hook
		public $logo = '';
		public $adr = '';
		public $adrlivr = '';
		public $adrfact = '';
		public $typeadr = '';
		public $customerAddrSelect = '';
		public $listnotep = '';
		public $pied = '';
		public $include_alias = 0;
		public $qrcodestring = '';
		public $deposits = 0;
		public $lines_deposits = [];
		// Drawing parameters of the presentation page
		public $tab_hl = 4;
		public $stdLineW;
		public $stdLineDash;
		public $stdLineCap;
		public $stdLineColor;
		public $stdLineStyle;
		public $bgLineW;
		public $bgLineDash;
		public $bgLineCap;
		public $bgLineColor;
		public $bgLineStyle;
		public $tblLineCap;
		public $tblLineStyle;
		public $larg_util_cadre;
		public $larg_util_txt;
		public $posx_G_txt;

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
			$this->name							= $langs->trans('PDFInfraSPlusProjectDossierName');
			$this->description					= $langs->trans('PDFInfraSPlusProjectDossierDescription');
			$this->titlekey						= 'PDFInfraSPlusProjectDossierTitle';
			$this->recudocs_mode				= $this->isRecudocsMode();
			$this->update_main_doc_field		= $this->recudocs_mode ? 1 : 0;	// Generic mode : the merged file is not the main document of the project
			$this->defaulttemplate				= getDolGlobalString('PROJECT_ADDON_PDF', '');
			$this->deposit_are_payment			= getDolGlobalInt('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS', 0);
			$this->Prj_TimeStamp				= getDolGlobalInt('INFRASPLUS_PDF_PROJECT_TIMESTAMP', 0);
			$this->nodatelinked					= getDolGlobalInt('INFRASPLUS_PDF_NO_DATE_LINKED', 0);
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
		}

		/**
		*	Tell if the documents receipt mode must be used : the htdocs/BRAND file of the instance contains "vcc"
		*	and the "recudocs" extra field is defined on the projects. Otherwise the generic mode (merge of the special files) is used.
		*
		*	@return		int		1 = documents receipt mode, 0 = generic mode
		**/
		protected function isRecudocsMode()
		{
			if (infraspackplus_getBrand() !== 'vcc') {
				return 0;
			}
			if (! infraspackplus_isExtrafieldDefined($this->db, 'projet', 'recudocs')) {
				dol_syslog(get_class($this).'::isRecudocsMode BRAND = vcc but the "recudocs" extra field is missing on projects : generic mode used', LOG_WARNING);
				return 0;
			}
			return 1;
		}

		/**
		*	Function to build pdf onto disk
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
			global $langs, $conf, $hookmanager;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$baseDir		= !empty($conf->projet->multidir_output[$conf->entity]) ? $conf->projet->multidir_output[$conf->entity] : $conf->projet->dir_output;
			$fileprefix		= '';
			if (!getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME')) {
				$filesufixe	= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_PJ_Dossier') ? '' : '_PJ_D';
			} else {
				$fileprefix	= getDolGlobalString('INFRASPLUS_PDF_ADD_PREFIX_TO_PJ_Dossier', '');	// code PJ_Dossier of $listModeles (admin/infrasplussetup.php)
				$filesufixe	= empty($fileprefix) ? '_PJ_D' : '';
			}
			if (empty($baseDir)) {
				$this->error	= $outputlangs->trans('ErrorConstantNotDefined', 'PROJECT_OUTPUTDIR');
				return 0;
			}
			$objectref	= dol_sanitizeFileName($object->ref);
			$specimen	= preg_match('/specimen/i', $objectref) || !empty($object->specimen);
			// Definition of $dir and $file
			if ($specimen) {
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
			if (!empty($this->recudocs_mode)) {
				$object->fetch_thirdparty();
				// Documents receipt mode : the name of the external project leader is added to the file name
				if (! $specimen) {
					$arrayidcontact	= $object->getIdContact('external', 'PROJECTLEADER');
					if (!empty($arrayidcontact) && is_array($arrayidcontact) && count($arrayidcontact) > 0) {
						$contact	= new Contact($this->db);
						$contact->fetch($arrayidcontact[0]);
						$nom		= $contact->getFullName($outputlangs, 0, 5, 0);
						if (!empty($nom)) {
							$nom	= preg_replace('/\s+/', '_', $nom);
							$file	= $dir.'/'.$fileprefix.$objectref.'_'.$nom.'.pdf';
						}
					}
				}
			}
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
			$this->listnotep			= !empty($hookmanager->resArray['listnotep']) ? $hookmanager->resArray['listnotep'] : '';
			$this->pied					= !empty($hookmanager->resArray['pied']) ? $hookmanager->resArray['pied'] : '';
			$this->include_alias		= !empty($hookmanager->resArray['includealias']) ? $hookmanager->resArray['includealias'] : 0;
			// Files chosen in the generation form : array of ECM rowid, 'none' or nothing
			$this->files				= (!empty($hookmanager->resArray['filesArray']) && is_array($hookmanager->resArray['filesArray'])) ? $hookmanager->resArray['filesArray'] : [];
			$hookmanager->resArray		= [];
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
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
			if (!empty($this->recudocs_mode)) {
				$this->buildRecudocsFile($pdf, $object, $outputlangs, $dir, $default_font_size);
				$nbPage	= $pdf->getNumPages();
			} else {
				$this->buildMergeFile($pdf, $object, $outputlangs);
				$nbPage	= $pdf->getNumPages();
			}
			$pdf->Close();
			if (!empty($nbPage)) {
				$pdf->Output($file, 'F');
			}
			// Add pdfgeneration hook
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters	= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
			if (!empty($this->recudocs_mode)) {
				$parameters['fromInfraS']	= 1;
			}
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

		/**
		*	Generic mode : merge the files chosen in the generation form or, failing that, the InfraS special files enabled for the projects
		*	(INFRASPLUS_PDF_SPECIAL_FILES filtered by INFRASPLUS_PDF_SPECIAL_FILE_PROJECT_<NAME>_AUTO, with a matching script in core/modules/specialfiles).
		*	No page is added when there is nothing to merge : write_file() then produces no file.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		object		$object			Object to generate
		*	@param		Translate	$outputlangs	Lang output object
		*	@return		void
		**/
		protected function buildMergeFile(&$pdf, $object, $outputlangs)
		{
			global $conf;

			$pdf->SetSubject($outputlangs->transnoentities('Project'));
			$pdf->SetAuthor($outputlangs->convToOutputCharset($this->getAuthorName($outputlangs)));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Project'));
			// if merge files is active
			if (is_array($this->files) && count($this->files) > 0) {
				pdf_InfraSPlus_files($pdf, $this->files, 1, $object, $outputlangs, $this->formatpage);
				return;
			}
			$paramspecialfiles	= getDolGlobalString('INFRASPLUS_PDF_SPECIAL_FILES', '');
			if (empty($paramspecialfiles)) {
				return;
			}
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
			if (empty($filesArray)) {
				return;
			}
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

		/**
		*	Documents receipt mode : presentation page then merge of the documents checked in the "recudocs" extra field of the project.
		*	Documents searched : PV de réception (1) and PV de levée de réserves (9) in the project directory (signed file first, base file otherwise),
		*	certificat de conformité Qualigaz (8) in the project directory, attestation RGE (6) in the "Docs Societe" ECM directory according to the
		*	"categorie" extra field, cadre de contribution CEE (2), devis signé (3) and note de dimensionnement (7) in the first signed proposal,
		*	factures d'acompte (4) and facture (5) in the invoices of the project. The merge order is fixed by the array keys of $this->files.
		*
		*	@param		TCPDF		$pdf				Object PDF
		*	@param		object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$dir				Directory of the project documents
		*	@param		int			$default_font_size	Default font size
		*	@return		void
		**/
		protected function buildRecudocsFile(&$pdf, $object, $outputlangs, $dir, $default_font_size)
		{
			global $conf;

			$pdf->SetSubject($outputlangs->transnoentities('PDFInfraSPlusProjectDocsTitle'));
			$pdf->SetAuthor($outputlangs->convToOutputCharset($this->getAuthorName($outputlangs)));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('PDFInfraSPlusProjectDocsTitle').(is_object($object->thirdparty) && !empty($object->thirdparty->name) ? ' '.$outputlangs->convToOutputCharset($object->thirdparty->name) : ''));
			// Default delivery address : the "INSTAL" address of the thirdparty
			if (empty($this->adrlivr) && is_object($object->thirdparty) && !empty($object->thirdparty->id)) {
				$adrlivrtmp		= new Address($this->db);
				$res_adrlivr	= $adrlivrtmp->fetch_lines($object->thirdparty->id, 0);
				if ($res_adrlivr > 0) {
					foreach ($adrlivrtmp->lines as $lineadr) {
						if ($lineadr->label == 'INSTAL') {
							$this->adrlivr	= $lineadr->id;
							break;
						}
					}
				}
			}
			// Documents checked in the "recudocs" extra field
			$extrafields	= new ExtraFields($this->db);
			$extrafields->fetch_name_optionals_label($object->table_element);
			$object->fetch_optionals();
			$recudocsvalue		= isset($object->array_options['options_recudocs']) ? (string) $object->array_options['options_recudocs'] : '';
			$txtRecudocs		= $extrafields->showOutputField('recudocs', $recudocsvalue, '', $object->table_element);
			$recudocs			= explode(',', $recudocsvalue);
			$this->arrRecudocs	= [];
			$nCategorie			= isset($object->array_options['options_categorie']) ? $object->array_options['options_categorie'] : '';
			/*
			Reçu Document :
				1,PV de réception -
				2,Cadre contribution CEE
				3,Devis signé -
				4,Factures d'acompte(s) et solde -
				5,Facture -
				6,Attestation RGE -
				7,Note de dimensionnement -
				8,Certificat de conformité Qualigaz -
				9,PV de levée de réserves -
			Catégories :
				1,Pompe à chaleur air/air
				2,Pompe à chaleur air/air (Pose)
				3,Pompe à chaleur air/eau
				4,Chaudière gaz à condensation THPE
				5,Chaudière à granulés de bois
				6,Poele bois ou granulés
				7,Autre
				8,Chauffe-eau thermodynamique
				9,Chauffe-eau solaire individuel
				10,Triple C
				11,Pompe à chaleur air/eau hybride
				12,Plomberie
				13,Entretien annuel
				14,Vente pellets
				15,VMC simple flux
				16,VMC double flux
				17,Chauffage Central
				18,Poêle de masse
				19,Système Solaire Combiné
			*/
			// Files stored in the project directory
			$projectPVReceptionFound		= 0;
			$projectPVLeveeReservesFound	= 0;
			$pagecount						= 0;
			$listProjectFiles				= dol_dir_list($dir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
			$system_upload_relative_dir		= preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);
			$system_upload_relative_dir		= preg_replace('/^[\\/]/', '', $system_upload_relative_dir);
			completeFileArrayWithDatabaseInfo($listProjectFiles, $system_upload_relative_dir);
			foreach ($listProjectFiles as $projectFile) {
				if (empty($projectFile['name'])) {
					continue;
				}
				if (in_array('1', $recudocs)) {	// PV de réception
					if (strpos($projectFile['name'], 'PVReception-signé.pdf') !== false) {
						$this->files[10]			= $projectFile['rowid'];
						$projectPVReceptionFound	= 1;
					}
				}
				if (in_array('8', $recudocs)) {	// Certificat de conformité Qualigaz
					if (strpos($projectFile['name'], 'CCQ_') !== false) {
						$this->files[80]	= $projectFile['rowid'];
					}
				}
				if (in_array('9', $recudocs)) {	// PV de levée de réserves
					if (strpos($projectFile['name'], 'PVLeveeReserves-signé.pdf') !== false) {
						$this->files[12]				= $projectFile['rowid'];
						$projectPVLeveeReservesFound	= 1;
					}
				}
			}
			if (empty($projectPVReceptionFound) && in_array('1', $recudocs)) {		// PV de réception signé non trouvé => on cherche le fichier de base
				foreach ($listProjectFiles as $projectFile) {
					if (empty($projectFile['name'])) {
						continue;
					}
					if (strpos($projectFile['name'], 'PVReception.pdf') !== false) {
						$this->files[10]	= $projectFile['rowid'];
					}
				}
			}
			if (empty($projectPVLeveeReservesFound) && in_array('9', $recudocs)) {		// PV de levée de réserves signé non trouvé => on cherche le fichier de base
				foreach ($listProjectFiles as $projectFile) {
					if (empty($projectFile['name'])) {
						continue;
					}
					if (strpos($projectFile['name'], 'PVLeveeReserves.pdf') !== false) {
						$this->files[12]	= $projectFile['rowid'];
					}
				}
			}
			// Company files
			if (in_array('6', $recudocs) && !empty($nCategorie)) {	// Attestation RGE
				$listSocFiles	= dol_dir_list_in_database('ecm/Docs Societe', '\.pdf$', array('(\.meta|_preview.*\.png)$', '^\.'));
				foreach ($listSocFiles as $socFile) {
					if (empty($socFile['name'])) {
						continue;
					}
					$socFileName	= pathinfo($socFile['name'], PATHINFO_FILENAME);
					if (in_array($nCategorie, array(1, 2, 3, 8, 11))) {	// Qualipac => Pompe à chaleur air/air, Pompe à chaleur air/air (pose), Pompe à chaleur air/eau, Chauffe-eau thermodynamique, Pompe à chaleur air/eau hybride
						if (strpos($socFileName, 'Qualipac') !== false) {
							$this->files[60]	= $socFile['rowid'];
						}
					}
					if (in_array($nCategorie, array(5, 6, 18))) {	// Qualibois => Chaudière à granulés de bois, Poele bois ou granulés, Poêle de masse
						if (strpos($socFileName, 'Qualibois') !== false) {
							$this->files[60]	= $socFile['rowid'];
						}
					}
					if (in_array($nCategorie, array(4, 17))) {	// Chauffage => Chaudière gaz à condensation THPE, Chauffage Central
						if (strpos($socFileName, 'Chauffage+') !== false) {
							$this->files[60]	= $socFile['rowid'];
						}
					}
					if (in_array($nCategorie, array(9, 19))) {	// Solaire => Chauffe-eau solaire individuel, Système Solaire Combiné
						if (strpos($socFileName, 'Qualisol') !== false) {
							$this->files[60]	= $socFile['rowid'];
						}
					}
					if (in_array($nCategorie, array(15, 16))) {	// Ventilation => VMC simple flux, VMC double flux
						if (strpos($socFileName, 'Ventil+') !== false) {
							$this->files[60]	= $socFile['rowid'];
						}
					}
				}
			}
			// Files stored in the proposals
			if (isModEnabled('propal') && (in_array('2', $recudocs) || in_array('3', $recudocs) || in_array('7', $recudocs))) {
				$propalFileFound	= 0;
				$staticPropal		= new Propal($this->db);
				$listPropals		= $object->get_element_list('propal', 'propal', 'datep', '', '', 'fk_projet');
				if (is_array($listPropals) && count($listPropals) > 0) {
					$baseDirPropal	= !empty($conf->propal->multidir_output[$conf->entity]) ? $conf->propal->multidir_output[$conf->entity] : $conf->propal->dir_output;
					$num			= count($listPropals);
					// Only the signed proposals are checked
					for ($p = 0; $p < $num; $p++) {
						$tmp		= explode('_', $listPropals[$p]);
						$idofpropal	= $tmp[0];
						$staticPropal->fetch($idofpropal);
						if ($staticPropal->status >= Propal::STATUS_SIGNED) {
							$propalRef					= dol_sanitizeFileName($staticPropal->ref);
							$this->arrRecudocs[]		= array('ref_title' => $outputlangs->transnoentities('RefProposal'), 'ref_value' => $staticPropal->ref, 'date_value' => dol_print_date($staticPropal->date, 'day', '', $outputlangs));
							$dirPropal					= $baseDirPropal.'/'.$propalRef;
							$listPropalFiles			= dol_dir_list($dirPropal, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
							$system_upload_relative_dir	= preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dirPropal);
							$system_upload_relative_dir	= preg_replace('/^[\\/]/', '', $system_upload_relative_dir);
							completeFileArrayWithDatabaseInfo($listPropalFiles, $system_upload_relative_dir);
							foreach ($listPropalFiles as $propalFile) {
								if (empty($propalFile['name'])) {
									continue;
								}
								$propalName	= pathinfo($propalFile['name'], PATHINFO_FILENAME);
								if (in_array('2', $recudocs)) {	// Cadre contribution CEE
									if (strpos($propalName, 'CC_') !== false) {
										$this->files[20]	= $propalFile['rowid'];
									}
								}
								if (in_array('3', $recudocs)) {	// Devis signé
									if (strpos($propalName, $propalRef.'-sign') !== false) {
										$this->files[30]	= $propalFile['rowid'];
										$propalFileFound	= 1;
									}
								}
								if (in_array('7', $recudocs)) {	// Note de dimensionnement
									if (strpos($propalName, $propalRef.'_NoteDim_') !== false) {
										$this->files[70]	= $propalFile['rowid'];
									}
								}
							}
							if (empty($propalFileFound) && in_array('3', $recudocs)) {		// Devis signé non trouvé => on cherche le fichier de base : premier PDF dont le nom commence par la référence, hors note de dimensionnement
								foreach ($listPropalFiles as $propalFile) {
									if (empty($propalFile['name'])) {
										continue;
									}
									$propalName	= pathinfo($propalFile['name'], PATHINFO_FILENAME);
									if (strpos($propalName, $propalRef) === 0 && strpos($propalName, '_NoteDim_') === false) {
										$this->files[30]	= $propalFile['rowid'];
										break;
									}
								}
							}
						}
					}
				}
			}
			// Files stored in the invoices
			if (isModEnabled('facture') && (in_array('4', $recudocs) || in_array('5', $recudocs))) {
				$staticInvoice	= new Facture($this->db);
				$listInvoices	= $object->get_element_list('invoice', 'facture', 'datef', '', '', 'fk_projet');
				if (is_array($listInvoices) && count($listInvoices) > 0) {
					$baseDirInvoice	= !empty($conf->facture->multidir_output[$conf->entity]) ? $conf->facture->multidir_output[$conf->entity] : $conf->facture->dir_output;
					$num			= count($listInvoices);
					$nbAC			= 0;
					for ($f = 0; $f < $num; $f++) {
						$tmp						= explode('_', $listInvoices[$f]);
						$idofinvoice				= $tmp[0];
						$staticInvoice->fetch($idofinvoice);
						$invoiceRef					= dol_sanitizeFileName($staticInvoice->ref);
						$this->arrRecudocs[]		= array('ref_title' => $outputlangs->transnoentities('InvoiceRef'), 'ref_value' => $staticInvoice->ref, 'date_value' => dol_print_date($staticInvoice->date, 'day', '', $outputlangs));
						$dirInvoice					= $baseDirInvoice.'/'.$invoiceRef;
						$listInvoiceFiles			= dol_dir_list($dirInvoice, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
						$system_upload_relative_dir	= preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dirInvoice);
						$system_upload_relative_dir	= preg_replace('/^[\\/]/', '', $system_upload_relative_dir);
						completeFileArrayWithDatabaseInfo($listInvoiceFiles, $system_upload_relative_dir);
						if ($staticInvoice->type == Facture::TYPE_STANDARD) {	// Facture
							foreach ($listInvoiceFiles as $invoiceFile) {
								if (empty($invoiceFile['name'])) {
									continue;
								}
								if ($invoiceFile['name'] == $invoiceRef.'.pdf') {
									$this->files[50]	= $invoiceFile['rowid'];
								}
							}
						} elseif (in_array('4', $recudocs)) {	// Factures d'acompte(s)
							foreach ($listInvoiceFiles as $invoiceFile) {
								if (empty($invoiceFile['name'])) {
									continue;
								}
								if ($invoiceFile['name'] == $invoiceRef.'.pdf') {
									$this->files[40 + $nbAC]	= $invoiceFile['rowid'];
									$nbAC++;
								}
							}
						}
					}
				}
			}
			// Presentation page
			$pdf->AddPage();
			pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs);	// Show Watermarks
			// Default PDF parameters
			$this->stdLineW		= 0.2;	// épaisseur par défaut dans TCPDF = 0.2
			$this->stdLineDash	= '0';	// 0 = continue ; w = discontinue espace et tiret identiques ; w,x = tiret,espace ; w,x,y,z = tiret long,espace,tiret court,espace
			$this->stdLineCap	= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
			$this->stdLineColor	= array(128, 128, 128);
			$this->stdLineStyle	= array('width' => $this->stdLineW, 'dash' => $this->stdLineDash, 'cap' => $this->stdLineCap, 'color' => $this->stdLineColor);
			$this->bgLineW		= $this->tblLineW;
			$this->bgLineDash	= '0';
			$this->bgLineCap	= 'butt';
			$this->bgLineColor	= $this->bg_color;
			$this->bgLineStyle	= array('width' => $this->bgLineW, 'dash' => $this->bgLineDash, 'cap' => $this->bgLineCap, 'color' => $this->bgLineColor);
			$this->tblLineCap	= 'butt';
			$this->tblLineStyle	= array('width' => $this->tblLineW, 'dash' => $this->tblLineDash, 'cap' => $this->tblLineCap, 'color' => (!empty($this->title_bg) && empty($this->showtblline) ? $this->bg_color : $this->tblLineColor));
			$this->horLineStyle	= array('width' => $this->tblLineW, 'dash' => $this->tblLineDash, 'cap' => $this->tblLineCap, 'color' => $this->horLineColor);
			$pdf->MultiCell(0, 3, '');		// Set interline to 3
			$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
			$pdf->SetFont('', '', $default_font_size - 1);
			// Define width and position of notes frames
			$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
			$this->larg_util_txt	= $this->larg_util_cadre - (($this->Rounded_rect * 2) + 2);
			$this->posx_G_txt		= $this->marge_gauche + $this->Rounded_rect + 1;
			// Positions
			$this->tab_hl			= 4;
			$this->decal_round		= $this->Rounded_rect > 0.001 ? $this->Rounded_rect : 0;
			$head					= $this->_pagehead($pdf, $object, 1, $outputlangs);
			$hauteurhead			= $head['totalhead'];
			$tab_top				= $hauteurhead + 5 > $this->height_header_sep ? $hauteurhead + 5 : $this->height_header_sep;
			$this->ht_top_table		= ($this->Rounded_rect * 2 > $this->height_top_table ? $this->Rounded_rect * 2 : $this->height_top_table) + $this->tab_hl * 0.5;
			$heightforfooter		= $this->_pagefoot($pdf, $object, $outputlangs, 1);
			$pdf->SetFont('', '', $default_font_size - 1);
			// Notes, extra fields
			$height_note			= pdf_InfraSPlus_Notes($pdf, $object, $this->listnotep, $outputlangs, $this->exftxtcolor, $default_font_size, $tab_top, $this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $this->horLineStyle, $this->ht_top_table + $this->decal_round + $heightforfooter, $this->page_hauteur, $this->Rounded_rect, $this->showtblline, $this->marge_gauche, $this->larg_util_cadre, $this->tblLineStyle, 0, 0);
			$tab_top				+= $height_note > 0 ? $height_note : $this->tab_hl * 0.5;
			$nexY					= $tab_top + $this->ht_top_table + ($this->decal_round > 0 ? $this->decal_round : $this->tab_hl * 0.5);
			// List of the documents
			$pdf->writeHTMLCell($this->larg_util_cadre, $this->tab_hl, $this->marge_gauche, $nexY + ($this->tab_hl * 2), $outputlangs->transnoentities('PDFInfraSPlusProjectDossierDocList'), 0, 1, false, true, 'L', true);
			$pdf->writeHTMLCell($this->larg_util_cadre, $this->tab_hl, $this->marge_gauche, $nexY + ($this->tab_hl * 3), $txtRecudocs, 0, 1, false, true, 'L', true);
			// Footer
			$this->_pagefoot($pdf, $object, $outputlangs, 0);
			if (method_exists($pdf, 'AliasNbPages')) {
				$pdf->AliasNbPages();
			}
			// Merge of the documents, in the order of the array keys
			if (!empty($this->files)) {
				ksort($this->files);
				$pagecount	= pdf_InfraSPlus_files($pdf, $this->files, 1, $object, $outputlangs, $this->formatpage, 0);
			}
			// Back to page 1 to write the number of pages of the file
			$pdf->setPage(1);
			$pdf->SetFont('', '', $default_font_size - 1);
			$txtpagecount	= '<b>'.$outputlangs->transnoentities('PDFInfraSPlusProjectDossierPageCount', $pagecount + 1).'</b>';
			$pdf->writeHTMLCell($this->larg_util_cadre, $this->tab_hl, $this->marge_gauche, $nexY, $txtpagecount, 0, 1, false, true, 'C', true);
		}

		/**
		*	Name of the author written in the PDF properties
		*
		*	@param		Translate	$outputlangs	Lang output object
		*	@return		string						Full name of the current user, empty if none
		**/
		protected function getAuthorName($outputlangs)
		{
			global $user;

			return is_object($user) ? $user->getFullName($outputlangs) : '';
		}

		/**
		*	Show top header of page (documents receipt mode)
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		object		$object			Object to show
		*	@param		int			$showaddress	0=no, 1=yes
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		array		$hauteurhead	'totalhead'		= height of header
		*											'hauteurcadre'	= height of the addresses frames
		**/
		protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
		{
			global $conf, $hookmanager;

			$specialHead	= infraspackplus_fetchAllSpecialHeads(array($object->element));
			if (!empty($specialHead['rootFileName'])) {
				$specialhead	= 'pdf_'.$specialHead['rootFileName'].'_pagehead';
				$hauteurhead	= $specialhead($pdf, $object, $showaddress, $outputlangs, $this->headertxtcolor, $this->header_align_left, $this->decal_round, $this->formatpage, $this->logo, $this->emetteur, $this->tab_hl,
												0, $this->title_size, $this->titlekey, $this->ref_from_cust, $this->datesbold, $this->dates_br, $this->show_num_cli, $this->num_cli_frm,
												$this->show_code_cli_compt, $this->code_cli_compt_frm, $this->add_creator_in_header, $this->use_iso_location, $this->adr, $this->typeadr, $this->adrlivr, $this->Rounded_rect,
												$this->customerAddrSelect, -2, -2, $this->qrcodestring, $this->deposits, $this->lines_deposits, '', $this->adrfact, $this->include_alias, $this->left_recep_corner, $this->top_recep_corner, 0);
				return $hauteurhead;
			}
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$dimCadres			= array('S' => ($this->page_largeur - ($this->marge_gauche + 6 + $this->left_recep_corner + $this->marge_droite)), 'R' => $this->left_recep_corner);	// page width = 210 (A4) 92 + 92  = 184 => keep 210 - 184 for margins => 26 ; 10 right and left and 6 on the middle
			$w					= $this->header_align_left ? 92 - $this->decal_round : 100;
			$align				= $this->header_align_left ? 'L' : 'R';
			$posy				= $this->marge_haute;
			$posx				= $this->page_largeur - $this->marge_droite - $w;
			// Logo
			$heightLogo			= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $this->logo, $this->emetteur, $this->marge_gauche, $this->tab_hl, $this->headertxtcolor, $object->entity);
			$heightLogo			+= $posy + $this->tab_hl;
			$sizeBC				= 0;
			if (!empty($this->qrcodestring)) {
				$sizeBC		= 25;
				$styleBC	= array('position'		=> '',
									'border'		=> false,
									'hpadding'		=> '0',
									'vpadding'		=> '0',
									'fgcolor'		=> array((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]),
									'bgcolor'		=> false,	// array(255,255,255)
									'module_width'	=> 1,		// width of a single module in points
									'module_height'	=> 1		// height of a single module in points
									);
				$pdf->write2DBarcode($this->qrcodestring, 'QRCODE,M', $posx + 2, $posy, $sizeBC, $sizeBC, $styleBC, 'N');
			}
			$pdf->SetFont('', 'B', $default_font_size * $this->title_size);
			$title	= $outputlangs->transnoentities($this->titlekey);
			$pdf->MultiCell($w - $sizeBC - 3, $this->tab_hl * $this->title_size, $title, '', $align, 0, 1, $posx + $sizeBC + 3, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$posy	= $pdf->getY();
			$txtref	= $outputlangs->transnoentities('Ref').' : '.$outputlangs->convToOutputCharset($object->ref);
			$status	= isset($object->status) ? (int) $object->status : (int) $object->statut;
			if ($status == Project::STATUS_DRAFT) {
				$pdf->SetTextColor(128, 0, 0);
				$txtref	.= ' - '.$outputlangs->transnoentities('NotValidated');
			}
			$pdf->MultiCell($w, $this->tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$pdf->SetTextColor((int) $this->headertxtcolor[0], (int) $this->headertxtcolor[1], (int) $this->headertxtcolor[2]);
			$posy	= $pdf->getY();
			$pdf->SetFont('', '', $default_font_size - 2);
			if (!empty($object->ref_client)) {
				$posy	+= $this->tab_hl - 0.5;
				$txtcc	= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($object->ref_client);
				$pdf->MultiCell($w, $this->tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			}
			if (is_object($object->thirdparty)) {
				if (!empty($this->show_num_cli) && !empty($this->num_cli_frm) && !empty($object->thirdparty->code_client)) {
					$txtNumCli	= $outputlangs->transnoentities('CustomerCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_client);
					$posy		+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $txtNumCli, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
				$codeCliCompt	= pdf_InfraSPlus_getCustomerAccountancyCode($object->thirdparty);
				if (!empty($this->show_code_cli_compt) && !empty($this->code_cli_compt_frm) && !empty($codeCliCompt)) {
					$txtCodeCliCompt	= $outputlangs->transnoentities('CustomerAccountancyCode').' : '.$outputlangs->convToOutputCharset($codeCliCompt);
					$posy				+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $txtCodeCliCompt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
			}
			if (!empty($this->add_creator_in_header)) {
				$usertmp	= pdf_InfraSPlus_creator($object, $outputlangs);
				if (!empty($usertmp)) {
					$posy	+= $this->tab_hl - 0.5;
					$pdf->MultiCell($w, $this->tab_hl, $outputlangs->transnoentities('PDFInfraSPlusRedac').' : '.$usertmp, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
			}
			// References of the listed documents
			if (!empty($this->arrRecudocs)) {
				foreach ($this->arrRecudocs as $refRecudoc) {
					$reftoshow	= $refRecudoc['ref_title'].' : '.$refRecudoc['ref_value'];
					if (empty($this->nodatelinked) && !empty($refRecudoc['date_value'])) {
						$reftoshow	.= ' / '.$refRecudoc['date_value'];
					}
					$posy		= $pdf->getY();
					$pdf->MultiCell($w, $this->tab_hl, dol_trunc($reftoshow, 100), '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				}
			}
			$posy			= $pdf->getY();
			$posy			= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $this->tab_hl, $align);
			$posy			+= 0.5;
			$dimCadres['Y']	= ($this->use_iso_location && $posy <= $this->top_recep_corner ? $this->top_recep_corner : ($heightLogo > $posy + $this->tab_hl ? $heightLogo : $posy + $this->tab_hl));
			$hauteurcadre	= 0;
			$addresses		= array('livrshow_name' => '', 'livrshow' => '');
			if (!empty($showaddress)) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'BILLING'),
										'L' => $object->getIdContact('external', 'SHIPPING')
										);
				$addresses		= pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $this->adr, $this->adrlivr, $this->emetteur, 0, '', $this->adrfact, 0, -2, -2, $this->customerAddrSelect, $this->include_alias);
				$hauteurcadre	= pdf_InfraSPlus_writeAddresses($pdf, $object, $outputlangs, $this->formatpage, $dimCadres, $this->tab_hl, $this->emetteur, $addresses, $this->Rounded_rect);
			}
			$hauteurhead	= array('totalhead'		=> $dimCadres['Y'] + $hauteurcadre,
									'hauteurcadre'	=> $hauteurcadre,
									'livrshow_name'	=> isset($addresses['livrshow_name']) ? $addresses['livrshow_name'] : '',
									'livrshow'		=> isset($addresses['livrshow']) ? $addresses['livrshow'] : ''
									);
			return $hauteurhead;
		}

		/**
		*	Show footer of page (documents receipt mode). Need this->emetteur object
		*
		*	@param		TCPDF		$pdf			The PDF factory
		*	@param		object		$object			Object shown in PDF
		*	@param		Translate	$outputlangs	Object lang for output
		*	@param		int			$calculseul		Arrête la fonction au calcul de hauteur nécessaire
		*	@return		int							Return height of bottom margin including footer text
		**/
		protected function _pagefoot(&$pdf, $object, $outputlangs, $calculseul)
		{
			$showdetails	= $this->type_foot.(!empty($this->pied) ? 1 : 0);
			return pdf_InfraSPlus_pagefoot($pdf, $object, $outputlangs, $this->emetteur, $this->formatpage, $showdetails, 0, $calculseul, $object->entity, $this->pied, $this->maxsizeimgfoot, 1, $this->bodytxtcolor, $this->stdLineStyle);
		}
	}
