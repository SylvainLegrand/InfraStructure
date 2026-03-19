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
	* 	\file		./infraspackplus/core/modules/user/doc/pdf_InfraSPlus_User_Contrat.modules.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF user
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/modules/user/modules_user.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	/************************************************
	*	Class to generate PDF order InfraS
	************************************************/
	class pdf_InfraSPlus_User_Contrat extends ModelePDFUser
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
		public $files;
		public $horLineStyle = array();
		public $only_ht;
		public $tableau = array();	// Array of table to print
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;

		/********************************************
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $langs;

			$langs->loadLangs(array('main', 'companies', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->db							= $db;
			$this->name							= $langs->trans('InfraSPlus_User_Contrat');
			$this->description					= $langs->trans('PDFInfraSPlusUserContratDescription');
			$this->titlekey						= 'Dossier Projet';
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->defaulttemplate				= getDolGlobalString('USER_ADDON_PDF', '');
			$this->option_logo					= 1;	// Display logo
			$this->option_tva					= 1;	// Manage the vat option FACTURE_TVAOPTION
			$this->option_codeproduitservice	= 1;	// Display product-service code
			$this->option_multilang				= 1;	// Available in several languages
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		User		$object				Object to generate
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
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'companies', 'infraspackplus@infraspackplus'));
			$filesufixe						= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_User_Contrat') ? '' : '_UCT';
			$baseDir						= !empty($conf->user->multidir_output[getEntity('user')]) ? $conf->user->multidir_output[getEntity('user')] : $conf->user->dir_output;

			if (!empty($baseDir)) {
				$objectref	= dol_sanitizeFileName($object->ref);
				// Definition of $dir and $file
				if (preg_match('/specimen/i', $objectref)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				}
				else {
					$dir	= $baseDir.'/'.$objectref;
					$file	= $dir.'/'.dol_sanitizeFileName($object->firstname.'_'.$object->lastname).$filesufixe.'.pdf';
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
					$parameters				= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook				= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					$this->files			= !empty($hookmanager->resArray['filesArray']) ? $hookmanager->resArray['filesArray'] : '';
					// Create pdf instance
					$pdf					= pdf_InfraSPlus_getInstance($this->format, 'mm', 'P');
					$default_font_size		= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
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
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->firstname.'_'.$object->lastname).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('Contract'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->firstname).' '.$outputlangs->convToOutputCharset($object->lastname).' '.$outputlangs->transnoentities('Contract'));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// if merge files is active
					if (is_array($this->files) && count($this->files) > 0) {
						pdf_InfraSPlus_files($pdf, $this->files, 1, $object, $outputlangs, $this->formatpage);
					}
					$nbPage		= $pdf->getNumPages();
					$pdf->Close();
					if(!empty($nbPage))	$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook	= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						$this->error	= $hookmanager->error;
						$this->errors	= $hookmanager->errors;
					}
					if(!empty($nbPage)) {
						if (!empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
						$this->result					= array('fullpath' => $file);
					}
					return 1;	// Pas d'erreur
				}
				else {
					$this->error=$outputlangs->trans('ErrorCanNotCreateDir',$dir);
					return 0;
				}
			}
			else {
				$this->error=$outputlangs->trans('ErrorConstantNotDefined', 'USER_OUTPUTDIR');
				return 0;
			}
		}
	}