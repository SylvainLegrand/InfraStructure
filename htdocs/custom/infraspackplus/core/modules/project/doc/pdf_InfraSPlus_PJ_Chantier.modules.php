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
	* 	\file		./infraspackplus/core/modules/project/doc/pdf_InfraSPlus_PJ_Chantier.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF project => site reference file for the RGE qualification body
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	if (isModEnabled('propal')) {
		include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	}
	if (isModEnabled('facture')) {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	}
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	/************************************************
	*	Class to generate the site reference file of a project (RGE qualification body)
	************************************************/
	class pdf_InfraSPlus_PJ_Chantier extends ModelePDFProjects
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
		public $chantier_mode = 0;	// 1 when the model is usable : brand "vcc" and "categorie" extra field on projects

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
			$this->name							= $langs->trans('PDFInfraSPlusProjectChantierName');
			$this->description					= $langs->trans('PDFInfraSPlusProjectChantierDescription');
			$this->titlekey						= 'PDFInfraSPlusProjectChantierTitle';
			$this->update_main_doc_field		= 0;	// The site reference file is not the main document of the project
			$this->defaulttemplate				= getDolGlobalString('PROJECT_ADDON_PDF', '');
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 0;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_codeproduitservice	= 0;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
			$this->chantier_mode				= $this->isChantierMode();
			if (empty($this->chantier_mode)) {
				$this->version					= 'development';	// Hidden from the models list (Projects setup) unless MAIN_FEATURES_LEVEL >= 2
			}
		}

		/**
		*	Tell if the model is usable on this instance : the htdocs/BRAND file contains "vcc" and the "categorie" extra field
		*	(type of installation, used for the qualification code of the file name) is defined on the projects
		*
		*	@return		int		1 = usable, 0 = hidden and refused
		**/
		protected function isChantierMode()
		{
			if (infraspackplus_getBrand() !== 'vcc') {
				return 0;
			}
			if (! infraspackplus_isExtrafieldDefined($this->db, 'projet', 'categorie')) {
				dol_syslog(get_class($this).'::isChantierMode BRAND = vcc but the "categorie" extra field is missing on projects : model disabled', LOG_WARNING);
				return 0;
			}
			return 1;
		}

		/**
		*	Function to build pdf onto disk : concatenation of the signed ABE of the project, then the signed proposals, then the invoices.
		*	Nothing is produced when one of these three groups is empty. Annotated PDF (signatures added in a reader) are flattened by
		*	Ghostscript before import, because TCPDI ignores annotations
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
			if (empty($this->chantier_mode)) {
				$this->error	= $outputlangs->transnoentities('FeatureDisabled');
				return 0;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'dict', 'bills', 'products', 'companies', 'propal', 'orders', 'contracts', 'interventions', 'deliveries', 'sendings', 'projects', 'productbatch', 'payment', 'paybox', 'infraspackplus@infraspackplus'));
			$baseDir	= !empty($conf->projet->multidir_output[$conf->entity]) ? $conf->projet->multidir_output[$conf->entity] : $conf->projet->dir_output;
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
				$file	= $dir.'/'.$objectref.'_Chantier.pdf';
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
			// File name : <qualification code>_<external project leader>.pdf
			$extrafields	= new ExtraFields($this->db);
			$extrafields->fetch_name_optionals_label($object->table_element);
			$object->fetch_optionals();
			$nCategorie		= isset($object->array_options['options_categorie']) ? $object->array_options['options_categorie'] : '';
			/*
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
			*/
			if (in_array($nCategorie, array(1, 2, 3, 8, 11))) {
				$typeQual	= 'QPAC';
			} elseif (in_array($nCategorie, array(5, 6, 18))) {
				$typeQual	= 'QB';
			} elseif (in_array($nCategorie, array(4))) {
				$typeQual	= 'CPLUS';
			} elseif (in_array($nCategorie, array(9))) {
				$typeQual	= 'QS';
			} elseif (in_array($nCategorie, array(15, 16))) {
				$typeQual	= 'VPLUS';
			} else {
				$typeQual	= '';
			}
			$arrayidcontact	= $object->getIdContact('external', 'PROJECTLEADER');
			if (!empty($arrayidcontact) && is_array($arrayidcontact) && count($arrayidcontact) > 0) {
				$contact	= new Contact($this->db);
				$contact->fetch($arrayidcontact[0]);
				$nom		= $contact->getFullName($outputlangs, 0, 5, 0);
				if (!empty($nom)) {
					$nom	= preg_replace('/\s+/', '_', $nom);
					$file	= $dir.'/'.$typeQual.'_'.$nom.'.pdf';
				}
			} else {
				setEventMessages($outputlangs->transnoentities('PDFInfraSPlusProjectChantierNoProjectLeader'), null, 'warnings');
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
			$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
			$pdf->SetSubject($outputlangs->transnoentities('Project'));
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset(is_object($user) ? $user->getFullName($outputlangs) : ''));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref).' '.$outputlangs->transnoentities('Project'));
			$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
			// Files chosen in the generation form
			if (count($this->files) > 0) {
				pdf_InfraSPlus_files($pdf, $this->files, 1, $object, $outputlangs, $this->formatpage);
			}
			// 1) Signed certificates of completion (ABE) stored in the project directory
			$tmpFlattenedFiles	= [];	// temporary flattened files, removed after the PDF output
			$listFilesLinked	= dol_dir_list($dir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
			$fileLinkedArray	= [];
			foreach ($listFilesLinked as $fileLinked) {
				if (empty($fileLinked['name'])) {
					continue;
				}
				$pdfname	= pathinfo($fileLinked['name'], PATHINFO_FILENAME);
				if (preg_match('/(.*)_ABE(.*)-sign(é|e)$/', $pdfname, $reg)) {
					$fileLinkedArray[]	= $fileLinked;
				}
			}
			if (count($fileLinkedArray) > 0 && isModEnabled('propal')) {
				// 2) Signed proposals of the project
				$nbABE			= count($fileLinkedArray);
				$staticPropal	= new Propal($this->db);
				$listPropals	= $object->get_element_list('propal', 'propal', 'datep', '', '', 'fk_projet');
				if (is_array($listPropals) && count($listPropals) > 0) {
					$propalBaseDir	= !empty($conf->propal->multidir_output[$conf->entity]) ? $conf->propal->multidir_output[$conf->entity] : $conf->propal->dir_output;
					$num			= count($listPropals);
					for ($p = 0; $p < $num; $p++) {
						$tmp		= explode('_', $listPropals[$p]);
						$idofpropal	= $tmp[0];
						$staticPropal->fetch($idofpropal);
						if ($staticPropal->status >= Propal::STATUS_SIGNED) {
							$propalRef			= dol_sanitizeFileName($staticPropal->ref);
							$propalDir			= $propalBaseDir.'/'.$propalRef;
							$listPropalFiles	= dol_dir_list($propalDir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
							foreach ($listPropalFiles as $propalFile) {
								if (empty($propalFile['name'])) {
									continue;
								}
								$propalName	= pathinfo($propalFile['name'], PATHINFO_FILENAME);
								if (preg_match('/(.*)-sign(é|e)$/', $propalName, $reg)) {
									$fileLinkedArray[]	= $propalFile;
								}
							}
						}
					}
					if (count($fileLinkedArray) > $nbABE && isModEnabled('facture')) {
						// 3) Invoices of the project
						$nbFileLinked	= count($fileLinkedArray);
						$staticInvoice	= new Facture($this->db);
						$listInvoices	= $object->get_element_list('invoice', 'facture', 'datef', '', '', 'fk_projet');
						if (is_array($listInvoices) && count($listInvoices) > 0) {
							$invoiceBaseDir	= !empty($conf->facture->multidir_output[$conf->entity]) ? $conf->facture->multidir_output[$conf->entity] : $conf->facture->dir_output;
							$num			= count($listInvoices);
							for ($f = 0; $f < $num; $f++) {
								$tmp				= explode('_', $listInvoices[$f]);
								$idofinvoice		= $tmp[0];
								$staticInvoice->fetch($idofinvoice);
								$invoiceRef			= dol_sanitizeFileName($staticInvoice->ref);
								$invoiceDir			= $invoiceBaseDir.'/'.$invoiceRef;
								$listInvoiceFiles	= dol_dir_list($invoiceDir, 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 0, 1, '', 0);
								foreach ($listInvoiceFiles as $invoiceFile) {
									if (empty($invoiceFile['name'])) {
										continue;
									}
									$invoiceName	= pathinfo($invoiceFile['name'], PATHINFO_FILENAME);
									if ($invoiceName == $invoiceRef) {
										$fileLinkedArray[]	= $invoiceFile;
									}
								}
							}
						}
						if (count($fileLinkedArray) > $nbFileLinked) {
							// Import of the three groups, in order
							foreach ($fileLinkedArray as $fileLinked) {
								$infile	= $fileLinked['fullname'];
								if (! file_exists($infile) || ! is_readable($infile)) {
									continue;
								}
								$finfo	= finfo_open(FILEINFO_MIME_TYPE);
								if (finfo_file($finfo, $infile) != 'application/pdf') {
									continue;
								}
								// Flatten annotated PDF with a single Ghostscript pdfwrite pass so signatures / checkmarks become page content (TCPDI ignores annotations).
								// -dPreserveAnnots=false draws the annotations into the page content while keeping vector text and transparency.
								// Never use a PDF->PS->PDF roundtrip here : PostScript has no transparency and every TCPDF page declares a transparency group, so gs rasterizes (blurry image) or empties the whole page.
								$infileToImport	= $infile;
								if (filesize($infile) > 0 && filesize($infile) < 52428800) {	// cap scan to 50 MB
									$pdfContent	= @file_get_contents($infile);
									if ($pdfContent !== false && (strpos($pdfContent, '/ADBE_FillSign') !== false || strpos($pdfContent, '/AcroForm') !== false || strpos($pdfContent, '/Subtype/Widget') !== false || strpos($pdfContent, '/Subtype /Widget') !== false)) {
										$tmpBase	= tempnam(sys_get_temp_dir(), 'isp_flat_');
										if ($tmpBase !== false) {
											$flatFile	= $tmpBase.'.pdf';
											$gsOutput	= [];
											$gsRetval	= 0;
											$cmd		= 'gs -dQUIET -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -dPreserveAnnots=false -sOutputFile='.escapeshellarg($flatFile).' '.escapeshellarg($infile).' 2>&1';
											@exec($cmd, $gsOutput, $gsRetval);
											@unlink($tmpBase);	// empty placeholder created by tempnam()
											if (file_exists($flatFile) && filesize($flatFile) > 0) {
												$infileToImport			= $flatFile;
												$tmpFlattenedFiles[]	= $flatFile;
												dol_syslog(get_class($this).' : flattened annotated PDF (gs pdfwrite, PreserveAnnots=false) '.$infile.' -> '.$flatFile);
											} else {
												@unlink($flatFile);
												dol_syslog(get_class($this).' : gs flatten failed for '.$infile.' (retval '.$gsRetval.') '.implode(' | ', $gsOutput), LOG_WARNING);
											}
										}
									}
								}
								try {
									$pdf->SetAutoPageBreak(0, 0);
									$pagecount	= $pdf->setSourceFile($infileToImport);
									for ($i = 1; $i <= $pagecount; $i++) {
										$tplIdx	= $pdf->importPage($i);
										if ($tplIdx !== false) {
											$s	= $pdf->getTemplatesize($tplIdx);
											$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L', array($s['w'], $s['h']));
											$pdf->useTemplate($tplIdx);
										} else {
											setEventMessages(null, array($outputlangs->trans('PDFInfraSPlusPdfFileError1', $infile)), 'warnings');
										}
									}
									$pdf->SetAutoPageBreak(1, 0);
								} catch (Exception $e) {
									setEventMessages(null, array($outputlangs->trans('PDFInfraSPlusPdfFileError1', $infile).$outputlangs->trans('PDFInfraSPlusPdfFileError2', $e->getMessage())), 'warnings');
								}
							}
						}
					}
				}
			}
			$nbPage	= $pdf->getNumPages();
			$pdf->Close();
			if (!empty($nbPage)) {
				$pdf->Output($file, 'F');
			}
			foreach ($tmpFlattenedFiles as $tmpFlatFile) {
				@unlink($tmpFlatFile);
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
