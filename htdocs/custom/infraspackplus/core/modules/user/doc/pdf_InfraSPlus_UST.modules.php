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
	* 	\file		./infraspackplus/core/modules/user/doc/pdf_InfraSPlus_UST.modules.php
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
	class pdf_InfraSPlus_UST extends ModelePDFUser
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
		public $logo;
		public $horLineStyle = array();
		public $only_ht;
		public $larg_util_cadre;
		public $larg_util_txt;
		public $posx_G_txt;
		public $tableau = array();	// Array of table to print
		public $tab_hl = 4;
		public $decal_round = 0;
		public $ht_top_table;
		public $heightline;
		public $job;
		public $photo;
		public $photomaxsize;
		public $socmail;
		public $soctel;
		public $watermark;

		/**
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
			$this->name							= $langs->trans('PDFInfraSPlusUserStickerName');
			$this->description					= $langs->trans('PDFInfraSPlusUserStickerDescription');
			$this->titlekey						= getDolGlobalString('INFRASPLUS_PDF_USER_STICKER_TITLE', '');
			$this->update_main_doc_field		= 0;	// Save the name of generated file as the main doc when generating a doc with this template
			$this->defaulttemplate				= getDolGlobalString('USER_ADDON_PDF', '');
			$formatarray						= pdf_InfraSPlus_getFormat(getDolGlobalString('INFRASPLUS_PDF_USER_STICKER_FORMAT', 'CARD'));
			$this->page_largeur					= $formatarray['width'];
			$this->page_hauteur					= $formatarray['height'];
			$this->format						= array($this->page_largeur, $this->page_hauteur);
			$this->marge_gauche					= 1;
			$this->marge_haute					= 1;
			$this->marge_droite					= 1;
			$this->marge_basse					= 1;
			$this->formatpage					= array('largeur'	=> $this->page_largeur,	'hauteur'	=> $this->page_hauteur,	'mgauche'	=> $this->marge_gauche,
														'mdroite'	=> $this->marge_droite,	'mhaute'	=> $this->marge_haute,	'mbasse'	=> $this->marge_basse);
			$this->watermark					= getDolGlobalString('INFRASPLUS_PDF_USER_STICKER_IMAGE_WATERMARK', '');
			$this->photo						= getDolGlobalInt('INFRASPLUS_PDF_USER_STICKER_PHOTO', 0);
			$this->job							= getDolGlobalInt('INFRASPLUS_PDF_USER_STICKER_JOB', 0);
			$this->soctel						= getDolGlobalInt('INFRASPLUS_PDF_USER_STICKER_SOC_TEL', 0);
			$this->socmail						= getDolGlobalInt('INFRASPLUS_PDF_USER_STICKER_SOC_MAIL', 0);
			$this->logo							= getDolGlobalInt('INFRASPLUS_PDF_USER_STICKER_LOGO', 0);
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
			if (! is_object($outputlangs)) {
				$outputlangs	= $langs;
			}
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf)) {
				$outputlangs->charset_output	= 'ISO-8859-1';
			}
			$outputlangs->loadLangs(array('main', 'companies', 'infraspackplus@infraspackplus'));
			$filesufixe						= empty($this->multi_files) || (!empty($this->defaulttemplate) && $this->defaulttemplate == 'InfraSPlus_User_Contrat') ? '' : '_UST';
			$baseDir						= $conf->user->dir_output;

			if (!empty($baseDir)) {
				$objectref	= dol_sanitizeFileName($object->ref);
				// Definition of $dir and $file
				if (preg_match('/specimen/i', $objectref)) {
					$dir	= $baseDir;
					$file	= $dir.'/SPECIMEN.pdf';
				} else {
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
					$parameters			= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook			= $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
					// Create pdf instance
					$pdf				= pdf_InfraSPlus_getInstance($this->format, 'mm', 'L');
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
					$pdf->SetTitle($outputlangs->convToOutputCharset($object->firstname.'_'.$object->lastname).$filesufixe);
					$pdf->SetSubject($outputlangs->transnoentities('Contract'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->firstname).' '.$outputlangs->convToOutputCharset($object->lastname).' '.$outputlangs->transnoentities('Card'));
					$pdf->setPageOrientation('', 1, 0);	// Edit the bottom margin of current page to set it.
					$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					pdf_InfraSPlus_bg_watermark($pdf, $this->formatpage, $object->entity, $outputlangs, $this->watermark);	// Show Watermarks
					$watermarkedPages		= array($pdf->getPage() => true);	// Track pages with watermark to avoid double rendering in while loops
					$pagenb					= 1;
					$pdf->MultiCell(0, 3, '');		// Set interline to 3
					$pdf->SetTextColor((int) $this->bodytxtcolor[0], (int) $this->bodytxtcolor[1], (int) $this->bodytxtcolor[2]);
					$pdf->SetFont('', '', $default_font_size - 1);
					// Calculs de positions
					$this->larg_util_cadre	= $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
					$this->larg_util_txt	= $this->larg_util_cadre;
					$this->posx_G_txt		= $this->marge_gauche;
					$this->tab_hl			= 4;
					$this->photomaxsize		= array('width' => 30, 'height' => 40);
					$curY					= $this->marge_haute;
					// Société - top / center
					$pdf->SetFont('', 'B', $default_font_size + 6);
					$title					= !empty($this->titlekey) ? $this->titlekey : $this->emetteur->name;
					$pdf->writeHTMLCell($this->larg_util_txt, $this->tab_hl, $this->posx_G_txt, $curY, $title, 0, 1, false, true, 'C', true);
					$curY					= $pdf->getY();
					// Photo - bottom / left
					$posxPhoto				= $this->marge_gauche;
					$larg_util_photo		= $this->larg_util_txt;
					if (!empty($this->photo)) {
						if (!empty($object->photo)) {	// Photo linked to user
							$photo	= get_exdir(0, 0, 0, 0, $object, 'user').'photos/'.$object->photo;
							$photo	= $baseDir.'/'.$photo;
						}
						else {	// No photo => use generic file
							$photo = DOL_DOCUMENT_ROOT.'/public/theme/common/user_'.($object->gender == 'woman' ? 'woman' : ($object->gender == 'man' ? 'man' : 'anonymous')).'.png';
						}
						// Print $photo
						if (is_file($photo) && is_readable($photo)) {
							$photosize			= pdf_InfraSPlus_getSizeForImage($photo, $this->photomaxsize['width'], $this->photomaxsize['height'], 0);	// $photosize['width'] $photosize['height']
							$pdf->Image($photo, $this->marge_gauche, $this->page_hauteur - ($this->marge_basse + $photosize['height']), $photosize['width'], 0);	// height = 0 (auto)
							$posxPhoto			= $this->marge_gauche + $photosize['width'] + 1;
							$larg_util_photo	= $this->larg_util_cadre -($photosize['width'] + 1);
						}
					}
					// Nom - center to the right of photo
					$pdf->SetFont('', 'B', $default_font_size + 2);
					$pdf->writeHTMLCell($larg_util_photo, $this->tab_hl, $posxPhoto, $curY + ($this->tab_hl * 2), $object->firstname.' '.$object->lastname, 0, 1, false, true, 'L', true);
					$curY		= $pdf->getY();
					$pdf->SetFont('', '', $default_font_size);
					// Job
					if (!empty($this->job)) {
						$pdf->writeHTMLCell($larg_util_photo, $this->tab_hl, $posxPhoto, $curY + $this->tab_hl, $object->job, 0, 1, false, true, 'L', true);
						$curY		= $pdf->getY();
					}
					// Logo - bottom / right
					if (!empty($this->logo) && !empty($this->emetteur->logo)) {
						$logodir	= !empty($conf->mycompany->multidir_output[$object->entity]) ? $conf->mycompany->multidir_output[$object->entity] : $conf->mycompany->dir_output;
						$logo		= $logodir.'/logos/'.$this->emetteur->logo;
						if (is_file($logo) && is_readable($logo)) {
							$w			= $this->larg_util_cadre - ($photosize['width'] * 2.2);
							$logosize	= pdf_InfraSPlus_getSizeForImage($logo, $w, $this->photomaxsize['height'], 1);
							$pdf->Image($logo, $this->page_largeur - ($this->marge_droite + $logosize['width']), $this->page_hauteur - ($this->marge_basse + $logosize['height']), $logosize['width'], 0);	// height = 0 (auto)
						}
					}
					$pdf->SetFont('', '', $default_font_size - 2);
					// Téléphone société
					if (!empty($this->soctel) && !empty($this->emetteur->phone)) {
						$pdf->writeHTMLCell($larg_util_photo, $this->tab_hl, $posxPhoto, $this->page_hauteur - ($this->marge_basse + ($this->tab_hl * 2)), $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($this->emetteur->phone))), 0, 1, false, true, 'L', true);
					}
					// Email société
					if (!empty($this->socmail) && !empty($this->emetteur->email)) {
						$pdf->writeHTMLCell($larg_util_photo, $this->tab_hl, $posxPhoto, $this->page_hauteur - ($this->marge_basse + $this->tab_hl), $outputlangs->convToOutputCharset($this->emetteur->email), 0, 1, false, true, 'L', true);
					}
					$pdf->Close();
					$pdf->Output($file, 'F');
					// Add pdfgeneration hook
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters	= array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
					global $action;
					$reshook	= $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks
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
					$this->error=$outputlangs->trans('ErrorCanNotCreateDir',$dir);
					return 0;
				}
			} else {
				$this->error=$outputlangs->trans('ErrorConstantNotDefined', 'USER_OUTPUTDIR');
				return 0;
			}
		}
	}