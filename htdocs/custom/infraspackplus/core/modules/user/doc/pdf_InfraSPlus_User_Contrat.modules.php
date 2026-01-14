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
		public $update_main_doc_field;	// Save the name of generated file as the main doc when generating a doc with this template
		public $type;
		public $phpmin	= array(7, 4);
		public $version	= 'dolibarr';
		public $page_largeur;
		public $page_hauteur;
		public $format;
		public $marge_gauche;
		public $marge_droite;
		public $marge_haute;
		public $marge_basse;
		public $emetteur;

		/********************************************
		*	Constructor
		*
		*	@param		DoliDB		$db	Database handler
		**/
		public function __construct($db)
		{
			global $conf, $langs, $mysoc;

			$langs->loadLangs(array('main', 'companies', 'infraspackplus@infraspackplus'));

			pdf_InfraSPlus_getValues($this);
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

		/********************************************
		*	Function to build pdf onto disk
		*
		*	@param		Object		$object				Object to generate
		*	@param		Translate	$outputlangs		Lang output object
		*	@return	int							1 = OK, <= 0 KO
		**/
		public function write_file($object, $outputlangs)
		{
			global $user, $langs, $conf, $db, $hookmanager;

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
						$hookmanager	= new HookManager($db);
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