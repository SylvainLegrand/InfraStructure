<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/modules/member/doc/pdf_infrasplus.class.php
	* 	\ingroup	InfraS
	* 	\brief		Class file for InfraS PDF membership card
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/commonstickergenerator.class.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/************************************************
	*	Class to generate PDF membership card InfraS
	************************************************/
	class pdf_infrasplus extends CommonStickerGenerator
	{
		/**
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $conf, $langs, $mysoc;

			$langs->loadLangs(array('main', 'dict', 'admin', 'companies', 'members', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
			$this->name						= $langs->trans('PDFInfraSPlusMemberName');
			$this->description				= $langs->trans('PDFInfraSPlusMemberDescription');
			$this->update_main_doc_field	= 1;	// Save the name of generated file as the main doc when generating a doc with this this
			$this->atleastonediscount		= 0;
			$this->type						= 'pdf';
			$this->multilangs				= getDolGlobalInt('MAIN_MULTILANGS', 0);
			$this->use_fpdf					= getDolGlobalInt('MAIN_USE_FPDF', 0);
			$this->main_umask				= getDolGlobalString('MAIN_UMASK', '0755');
			$this->code						= '101x54';
			$this->font						= getDolGlobalString('INFRASPLUS_PDF_FONT', 'centurygothic');
			$this->cat_hq_image				= getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES', 0);
			$this->watermark_i_opacity		= getDolGlobalInt('INFRASPLUS_PDF_I_WATERMARK_OPACITY', 1);
		}

		/**
		*	Function to build pdf onto disk
		*
		*	@param		Object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
		*	@param		string		$mode				Tell if doc module is called for 'member', ...
		*	@param		int			$nooutput			1 = Generate only file on disk and do not return it on response
		*	@return		int								1 = OK, 0 = KO
		**/
		public function write_file($object, $outputlangs, $srctemplatepath, $mode = 'member', $nooutput = 0)
		{
			global $user, $langs, $conf, $db, $hookmanager, $mysoc, $_Avery_Labels;

			dol_syslog('write_file outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));
			if (! is_object($outputlangs))	$outputlangs					= $langs;
			// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
			if (!empty($this->use_fpdf))	$outputlangs->charset_output	= 'ISO-8859-1';
			$outputlangs->loadLangs(array('main', 'dict', 'admin', 'companies', 'members', 'infraspackplus@infraspackplus'));
			$baseDir						= !empty($conf->adherent->multidir_output[$conf->entity]) ? $conf->adherent->multidir_output[$conf->entity] : $conf->adherent->dir_output;
			if (empty($mode) || $mode == 'member') {
				$title		= $outputlangs->transnoentities('MembersCards');
				$keywords	= $outputlangs->transnoentities('MembersCards').' '.$outputlangs->transnoentities('Foundation').' '.$outputlangs->convToOutputCharset($mysoc->name);
			} else {
				dol_print_error('', 'Bad value for $mode');
				return -1;
			}
			$this->Tformat					= $_Avery_Labels[$this->code];
			if (empty($this->Tformat)) {
				dol_print_error('', 'ErrorBadTypeForCard'.$this->code);
				exit;
			}
			$this->_Metric_Doc								= $this->Tformat['metric'];
			if ($this->Tformat['paper-size'] != 'custom')	$this->format = $this->Tformat['paper-size'];	// standard format
			else {	//custom
				$resolution		= array($this->Tformat['custom_x'], $this->Tformat['custom_y']);
				$this->format	= $resolution;
			}
			if (!empty($baseDir)) {
				$object->fetch_thirdparty();
				if (!empty($object->specimen)) {
					$this->show_ExtraFieldsLines	= '';
					$dir							= $baseDir;
					$file							= $dir.'/SPECIMEN.pdf';
				}
				elseif (is_object($object)) {
					$filename	= dol_sanitizeFileName($object->firstname.'.'.$object->lastname);
					$objectref	= dol_sanitizeFileName($object->ref);
					$dir		= $baseDir.'/'.$objectref;
					$file		= $dir.'/'.$filename.'.pdf';
				}
				else {
					$filename	= 'tmp_cards.pdf';
					$dir		= $conf->adherent->dir_temp;
					$file		= $dir.'/'.$filename;
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
						$hookmanager	= new HookManager($db);
					}
					$hookmanager->initHooks(array('pdfgeneration'));
					$parameters			= array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
					// Create pdf instance
					$pdf				= pdf_InfraSPlus_getInstance($this->format, $this->Tformat['metric'], $this->Tformat['orientation']);
					$default_font_size	= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
					$pdf->SetAutoPageBreak(false);
					if (class_exists('TCPDF')) {
						$pdf->setPrintHeader(false);
						$pdf->setPrintFooter(false);
					}
					$pdf->SetFont($this->font);
					// reduce the top margin before ol / il tag
					$tagvs		= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
					$pdf->setHtmlVSpace($tagvs);
					$pdf->Open();
					$pdf->SetTitle($title);
					$pdf->SetSubject($outputlangs->transnoentities('PDFInfraSPlusMemberName'));
					$pdf->SetCreator('Dolibarr '.DOL_VERSION);
					$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
					$pdf->SetKeyWords($keywords.' '.$outputlangs->transnoentities('PDFInfraSPlusMemberName').' '.$outputlangs->convToOutputCharset($object->thirdparty->name));
					$pdf->SetMargins(0, 0, 0);	// Left, Top, Right
					// New page
					$pdf->AddPage();
					$this->_Set_Format($pdf, $this->Tformat);
					$posy					= $this->_Margin_Top;
					$posx					= $this->_Margin_Left;
					$this->larg_util_txt	= $this->_Width - ($this->_Margin_Left + $this->_Margin_Right);
					$this->tab_hl			= 4;
					// Define background image
					$societedir	= !empty($conf->societe->multidir_output[$object->entity]) ? $conf->societe->multidir_output[$object->entity] : $conf->societe->dir_output;
					$filigrane	= get_exdir(0, 0, 0, 0, $object->thirdparty, 'thirdparty').'filigrane.png';
					$filigrane	= $societedir.'/'.$filigrane;
					if (!empty($filigrane) && is_readable($filigrane)) {
						$imgsize	= array();
						$imgsize	= pdf_InfraSPlus_getSizeForImage($filigrane, $this->_Width, $this->_Height);
						if (isset($imgsize['width']) && isset($imgsize['height'])) {
							$pdf->SetAlpha($this->watermark_i_opacity / 100);
							$bMargin			= $pdf->getBreakMargin();	// get the current page break margin
							$auto_page_break	= $pdf->getAutoPageBreak();	// get current auto-page-break mode
							$pdf->SetAutoPageBreak(false, 0);	// disable auto-page-break
							$posxpicture		= ($this->_Width - $imgsize['width']) / 2;	// centre l'image dans la page
							$posypicture		= ($this->_Height - $imgsize['height']) / 2;	// centre l'image dans la page
							$pdf->Image($filigrane, $posxpicture, $posypicture, $imgsize['width'], $imgsize['height'], '', '', '', false, 300, '', false, false, 0);	// set bacground image
							$pdf->SetAutoPageBreak($auto_page_break, $bMargin);	// restore auto-page-break status
							$pdf->setPageMark();	// set the starting point for the page content
							$pdf->SetAlpha(1);
						}
					}
					// Define Text values
					if (is_object($object)) {
						if ($object->country == '-')	$object->country	= '';
						$now							= dol_now();
						// List of values to scan for a replacement
						$substitutionarray				= array('__ID__'			=> $object->id,
																'__LOGIN__'			=> $object->login,
																'__FIRSTNAME__'		=> $object->firstname,
																'__LASTNAME__'		=> $object->lastname,
																'__FULLNAME__'		=> $object->getFullName($outputlangs),
																'__COMPANY__'		=> $object->company,
																'__ADDRESS__'		=> $object->address,
																'__ZIP__'			=> $object->zip,
																'__TOWN__'			=> $object->town,
																'__COUNTRY__'		=> $object->country,
																'__COUNTRY_CODE__'	=> $object->country_code,
																'__EMAIL__'			=> $object->email,
																'__BIRTH__'			=> dol_print_date($object->birth, 'day'),
																'__TYPE__'			=> $object->type,
																'__YEAR__'			=> dol_print_date($now, '%Y'),
																'__MONTH__'			=> dol_print_date($now, '%m'),
																'__DAY__'			=> dol_print_date($now, '%d'),
																);
						complete_substitutions_array($substitutionarray, $outputlangs);
						$textheader						= make_substitutions(getDolGlobalString('ADHERENT_CARD_HEADER_TEXT', ''), $substitutionarray);
						$textleft						= make_substitutions(getDolGlobalString('ADHERENT_CARD_TEXT', ''), $substitutionarray);
						$textright						= make_substitutions(getDolGlobalString('ADHERENT_CARD_TEXT_RIGHT', ''), $substitutionarray);
						$textfooter						= make_substitutions(getDolGlobalString('ADHERENT_CARD_FOOTER_TEXT', ''), $substitutionarray);
					}
					// Define Top
					if (!empty($textheader)) {
						$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, $outputlangs->convToOutputCharset($textheader), '', 'C', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
						$posy	= $pdf->getY();
					}
					// Define logo - Left
					$logo		= get_exdir(0, 0, 0, 0, $object->thirdparty, 'thirdparty').'logos/'.$object->thirdparty->logo;
					$logo		= $societedir.'/'.$logo;
					if (is_file($logo) && is_readable($logo)) {
						$heightLogo	= pdf_getHeightForLogo($logo);
						$logosize	= pdf_InfraSPlus_getSizeForImage($logo, '50', $heightLogo, 1);	// $logosize['width'] $logosize['height']
						$pdf->Image($logo, $this->_Width - ($logosize['width'] + $this->_Margin_Right), $posy, $logosize['width'], 0);	// height = 0 (auto)
						$posyLeft	= $posy + $logosize['height'] + 2;
					}
					// Define member
					$member	= new Adherent($db);
					$member->fetch($object->id);
					// Define photo - right
					if (!empty($object->photo)) {
						$photo		= get_exdir(0, 0, 0, 0, $member, 'member').'photos/'.$object->photo;
						$photo		= $baseDir.'/'.$photo;
						if (is_file($photo) && is_readable($photo)) {
							$heightPhoto	= pdf_getHeightForLogo($photo);
							$photosize		= pdf_InfraSPlus_getSizeForImage($photo, '50', $heightPhoto, 1);	// $photosize['width'] $photosize['height']
							$pdf->Image($photo, $this->_Margin_Left, $posy, $photosize['width'], 0);	// height = 0 (auto)
							$posyRight		= $posy + $photosize['height'] + 2;
						}
					}
					// Define left
					if (!empty($textleft)) {
						$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, $outputlangs->convToOutputCharset($textleft), '', 'L', 0, 1, $posx, $posyLeft, true, 0, 0, false, 0, 'M', false);
						$posyLeft	= $pdf->getY();
					}
					// Define right
					if (!empty($textright)) {
						$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, $outputlangs->convToOutputCharset($textright), '', 'R', 0, 1, $posx, $posyRight, true, 0, 0, false, 0, 'M', false);
						$posyRight	= $pdf->getY();
					}
					// Define bottom
					if (!empty($textfooter)) {
						$heightBC	= 20;
						$styleBC	= array('position'		=> 'C',
											'border'		=> false,
											'hpadding'		=> '0',
											'vpadding'		=> '0',
											'fgcolor'		=> array(0, 0, 0),
											'bgcolor'		=> false,	// array(255,255,255)
											'module_width'	=> 1,		// width of a single module in points
											'module_height'	=> 1		// height of a single module in points
											);
						$pdf->write2DBarcode($object->url, 'QRCODE', $posx, $this->_Height - ($this->tab_hl + $this->_Margin_Bottom + $heightBC), $heightBC, $heightBC, $styleBC, 'B');
						$pdf->MultiCell($this->larg_util_txt, $this->tab_hl, $outputlangs->convToOutputCharset($textfooter), '', 'C', 0, 1, $posx, $this->_Height - ($this->tab_hl + $this->_Margin_Bottom), true, 0, 0, false, 0, 'M', false);
						$posy		= $pdf->getY();
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
					if (!empty($this->main_umask))	@chmod($file, octdec($this->main_umask));
					$this->result					= array('fullpath' => $file);
					// Output to http stream
					if (empty($nooutput)) {
						clearstatcache();
						$attachment					= getDolGlobalString('MAIN_DISABLE_FORCE_SAVEAS', '') ? false : true;
						$type						= dol_mimetype($filename);
						if (!empty($type))			header('Content-Type: '.$type);
						if (!empty($attachment))	header('Content-Disposition: attachment; filename="'.$filename.'"');
						else						header('Content-Disposition: inline; filename="'.$filename.'"');
						// Ajout directives pour resoudre bug IE
						header('Cache-Control: Public, must-revalidate');
						header('Pragma: public');
						readfile($file);
					}
					return 1;	// Pas d'erreur
				}
				else {
					$this->error	= $outputlangs->transnoentities('ErrorCanNotCreateDir', $dir);
					return 0;
				}
			}
			else {
				$this->error	= $outputlangs->transnoentities('ErrorConstantNotDefined', 'MEMBER_OUTPUTDIR');
				return 0;
			}
		}
		/**
		*	Output a sticker on page at position _COUNTX, _COUNTY (_COUNTX and _COUNTY start from 0)
		*
		*	@param	TCPDF		$pdf			PDF reference
		*	@param	Translate	$outputlangs	Output langs
		*	@param	array		$param			Associative array containing label content and optional parameters
		*	@return	void
		**/
		public function addSticker(&$pdf, $outputlangs, $param)
		{
			// use this method in future refactoring
		}
	}