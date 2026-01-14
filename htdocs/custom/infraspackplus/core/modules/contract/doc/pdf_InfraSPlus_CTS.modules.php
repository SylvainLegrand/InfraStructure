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

require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

class pdf_InfraSPlus_CTS extends ModelePDFContract
{
	public $db;
	public $name;
	public $description;
	public $update_main_doc_field;
	public $type;
	public $phpmin = array(7, 4);
	public $version = 'dolibarr';

	public $page_largeur;
	public $page_hauteur;
	public $format;
	public $marge_gauche;
	public $marge_droite;
	public $marge_haute;
	public $marge_basse;
	public $emetteur;

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
	 * @return int 1 OK, 0 KO
	 */
	public function write_file($object, $outputlangs)
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
					if (!in_array($pdfname, $paramspecialfiles)) continue;
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
						if (!empty($row['rowid'])) $arrayFilesID[] = $row['rowid'];
					}
					if (!empty($arrayFilesID)) {
						pdf_InfraSPlus_files($pdf, $arrayFilesID, 1, $object, $outputlangs, $this->formatpage);
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