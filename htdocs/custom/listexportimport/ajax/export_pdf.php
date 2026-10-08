<?php
/* Copyright (C) 2026	InfraS	<contact@infras.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/custom/listexportimport/ajax/export_pdf.php
 *	\ingroup	listexportimport
 *	\brief		Export PDF d'une liste : PDF généré par TCPDF à partir du texte du tableau envoyé par le navigateur
 *
 *	InfraS add : fichier ajouté par InfraS (2026-10), appelé par exportTableToServerPDF() (js/listexport.js.php)
 *
 *	Requête POST, jeton CSRF dans l'URL (?token=...), corps JSON :
 *	{"title": "Titre", "subtitle": "Sous-titre", "rows": [["h", "LLR", "cellule 1", "cellule 2", "cellule 3"], ...]}
 *	1er élément d'une ligne : h = ligne de titre, b = ligne de données, t = ligne de total
 *	2e élément : alignement de chaque cellule (L, R ou C), puis le texte des cellules
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', 1);
}
if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', 1);	// Jeton obligatoire quel que soit MAIN_SECURITY_CSRF_WITH_TOKEN
}

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) { // From htdocs directory
	require '../../../main.inc.php'; // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var Societe $mysoc
 * @var User $user
 */

$langs->loadLangs(array('main', 'errors'));

/**
 *	Arrête la requête avec un message d'erreur en texte brut (affiché par le navigateur)
 *
 *	@param	int		$httpcode	Code HTTP
 *	@param	string	$message	Message déjà traduit (texte brut)
 *	@return	void
 */
function listexportimport_pdf_error($httpcode, $message)
{
	http_response_code($httpcode);
	top_httphead('text/plain; charset=UTF-8');
	print $message;
	exit;
}

// Limites des données reçues
$maxrows = 200000;
$maxcols = 100;
$maxcelllength = 1000;


/*
 * Access control
 */

if (empty($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
	listexportimport_pdf_error(405, $langs->transnoentitiesnoconv('ErrorBadParameters'));
}
if (!$user->hasRight('listexportimport', 'export')) {
	listexportimport_pdf_error(403, $langs->transnoentitiesnoconv('NotEnoughPermissions'));
}
// En cas de jeton invalide, main.inc.php retire le paramètre token : on refuse aussi ce cas
$token = GETPOST('token', 'alpha');
if ($token === '' || empty($_SESSION['token']) || $token !== $_SESSION['token']) {
	listexportimport_pdf_error(403, $langs->transnoentitiesnoconv('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry'));
}


/*
 * Data
 */

$timestart = microtime(true);
// Taille maximale du corps : post_max_size, que PHP n'applique pas à un corps JSON lu par php://input (64 Mo si illimité)
$maxbody = trim((string) ini_get('post_max_size'));
$unit = strtolower(substr($maxbody, -1));
$maxbody = (int) ((float) $maxbody * ($unit == 'g' ? 1073741824 : ($unit == 'm' ? 1048576 : ($unit == 'k' ? 1024 : 1))));
if ($maxbody <= 0) {
	$maxbody = 67108864;
}
if (!empty($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > $maxbody) {
	listexportimport_pdf_error(413, $langs->transnoentitiesnoconv('ErrorFileSizeTooLarge'));
}
$rawdata = file_get_contents('php://input', false, null, 0, $maxbody + 1);
if ($rawdata === false || $rawdata === '') {
	listexportimport_pdf_error(400, $langs->transnoentitiesnoconv('ErrorBadParameters'));
}
if (strlen($rawdata) > $maxbody) {
	listexportimport_pdf_error(413, $langs->transnoentitiesnoconv('ErrorFileSizeTooLarge'));
}
$data = json_decode($rawdata, true, 8);
unset($rawdata);
if (!is_array($data) || !isset($data['rows']) || !is_array($data['rows'])) {
	listexportimport_pdf_error(400, $langs->transnoentitiesnoconv('ErrorBadParameters'));
}
if (count($data['rows']) > $maxrows) {
	listexportimport_pdf_error(413, $langs->transnoentitiesnoconv('ErrorFileSizeTooLarge'));
}

$title = (isset($data['title']) && is_scalar($data['title'])) ? trim((string) $data['title']) : '';
if (dol_strlen($title) > 200) {
	$title = dol_substr($title, 0, 200);
}
$subtitle = (isset($data['subtitle']) && is_scalar($data['subtitle'])) ? trim((string) $data['subtitle']) : '';
if (dol_strlen($subtitle) > 200) {
	$subtitle = dol_substr($subtitle, 0, 200);
}

// Lignes normalisées : array(type, alignements, cellules)
$rows = array();
$ncols = 0;
$needunicode = false;
foreach ($data['rows'] as $row) {
	if (!is_array($row) || count($row) < 2) {
		continue;
	}
	$kind = (isset($row[0]) && in_array($row[0], array('h', 'b', 't'), true)) ? $row[0] : 'b';
	$rowaligns = (isset($row[1]) && is_string($row[1])) ? strtoupper($row[1]) : '';
	$cells = array();
	$aligns = '';
	$nb = min(count($row), $maxcols + 2);
	for ($i = 2; $i < $nb; $i++) {
		$text = (isset($row[$i]) && is_scalar($row[$i])) ? trim((string) $row[$i]) : '';
		if (strlen($text) > $maxcelllength) {
			$text = dol_substr($text, 0, $maxcelllength);
		}
		// Caractères hors Latin-1 / Windows-1252 : police Unicode nécessaire
		if (!$needunicode && $text !== '' && preg_match('/[^\x{0000}-\x{00FF}\x{20AC}\x{2013}\x{2014}\x{2018}\x{2019}\x{201C}\x{201D}\x{2026}]/u', $text)) {
			$needunicode = true;
		}
		$cells[] = $text;
		$align = substr($rowaligns, $i - 2, 1);
		$aligns .= in_array($align, array('L', 'R', 'C'), true) ? $align : 'L';
	}
	$ncols = max($ncols, count($cells));
	$rows[] = array($kind, $aligns, $cells);
}
unset($data);
if (empty($rows) || $ncols == 0) {
	listexportimport_pdf_error(400, $langs->transnoentitiesnoconv('NoRecordFound'));
}


/*
 * PDF
 */

// Toute sortie parasite (avertissement PHP affiché par TCPDF...) est jetée avant l'envoi du PDF
ob_start();

$font = pdf_getPDFFont($langs);
if ($needunicode && in_array(strtolower($font), array('helvetica', 'times', 'courier'), true)) {
	$font = 'dejavusans';
}
$format = pdf_getFormat($langs);
$pdf = pdf_getInstance(array($format['width'], $format['height']), 'mm', 'L');
$pdf->SetAutoPageBreak(false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
	$pdf->SetCompression(false);
}
$pdf->SetCreator('Dolibarr '.DOL_VERSION);
$pdf->SetAuthor($mysoc->name);
$pdf->SetTitle($title);

$marginleft = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
$marginright = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
$margintop = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
$marginbottom = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);
$pdf->SetMargins($marginleft, $margintop, $marginright);
$hpadding = 1;		// Marge interne gauche et droite des cellules (mm)
$vpadding = 0.6;	// Marge interne haute et basse des cellules (mm)
$pdf->setCellPaddings($hpadding, $vpadding, $hpadding, $vpadding);
$pdf->AddPage();
$pagewidth = $pdf->getPageWidth();
$pageheight = $pdf->getPageHeight();
$availablewidth = $pagewidth - $marginleft - $marginright;

// Largeur naturelle des colonnes, mesurée à la taille de police de base
$basesize = 8;
$minsize = 6;
$maxcolwidth = $availablewidth * 0.4;	// Au-delà, le texte passe à la ligne
$natural = array_fill(0, $ncols, 0);
$headerfull = array_fill(0, $ncols, 0);	// Largeur des titres de colonne sur une seule ligne
$cache = array('' => array(), 'B' => array());
foreach ($rows as $r => $row) {
	$style = ($row[0] == 'b') ? '' : 'B';
	$textwidths = array();
	foreach ($row[2] as $c => $text) {
		if ($text === '') {
			$textwidths[$c] = 0;
			continue;
		}
		if (!isset($cache[$style][$text])) {
			$cache[$style][$text] = $pdf->GetStringWidth($text, $font, $style, $basesize);
		}
		$textwidths[$c] = $cache[$style][$text];
		$need = $textwidths[$c];
		if ($row[0] == 'h') {
			// Les titres de colonne peuvent passer à la ligne : seul leur mot le plus long compte
			$need = 0;
			foreach (preg_split('/\s+/u', $text) as $word) {
				$need = max($need, $pdf->GetStringWidth($word, $font, $style, $basesize));
			}
			$headerfull[$c] = max($headerfull[$c], min($textwidths[$c], $maxcolwidth));
		}
		$natural[$c] = max($natural[$c], min($need, $maxcolwidth));
	}
	$rows[$r][3] = $textwidths;
}
unset($cache);

// Taille de police et largeurs : le tableau occupe toute la largeur utile, la police est réduite
// jusqu'à $minsize si nécessaire, puis les colonnes sont rétrécies (le texte passe à la ligne)
$sumnatural = array_sum($natural);
$paddingtotal = 2 * $hpadding * $ncols;
$fontsize = $basesize;
if ($sumnatural > 0 && $sumnatural + $paddingtotal > $availablewidth) {
	$fontsize = max($minsize, floor(2 * $basesize * ($availablewidth - $paddingtotal) / $sumnatural) / 2);
}
$ratio = $fontsize / $basesize;
$sumscaled = $sumnatural * $ratio;
$free = $availablewidth - $paddingtotal - $sumscaled;
// Place libre : d'abord pour que les titres de colonne tiennent sur une ligne, le reste au prorata des largeurs
$extra = array_fill(0, $ncols, 0);
if ($free > 0) {
	$sumwants = 0;
	for ($c = 0; $c < $ncols; $c++) {
		$extra[$c] = max(0, ($headerfull[$c] - $natural[$c]) * $ratio);
		$sumwants += $extra[$c];
	}
	if ($sumwants > $free) {
		for ($c = 0; $c < $ncols; $c++) {
			$extra[$c] = $extra[$c] * $free / $sumwants;
		}
		$sumwants = $free;
	}
	$free -= $sumwants;
}
$widths = array();
for ($c = 0; $c < $ncols; $c++) {
	if ($sumscaled > 0) {
		$widths[$c] = max(1, 2 * $hpadding + $natural[$c] * $ratio + $extra[$c] + $free * $natural[$c] * $ratio / $sumscaled);
	} else {
		$widths[$c] = $availablewidth / $ncols;
	}
}
$lineheight = $fontsize * 25.4 / 72 * 1.25;
$footerheight = 6;
$bottomlimit = $pageheight - $marginbottom;

/**
 *	Calcule la hauteur d'une ligne du tableau et si elle tient sur une seule ligne de texte
 *
 *	@param	TCPDF	$pdf			Objet PDF (police à la bonne taille déjà réglée par l'appelant pour le style de la ligne)
 *	@param	array	$row			Ligne normalisée (type, alignements, cellules, largeurs des textes à la taille de base)
 *	@param	array	$widths			Largeur des colonnes (mm)
 *	@param	float	$ratio			Rapport taille de police utilisée / taille de base
 *	@param	float	$lineheight		Hauteur d'une ligne de texte (mm)
 *	@param	float	$hpadding		Marge interne horizontale (mm)
 *	@param	float	$vpadding		Marge interne verticale (mm)
 *	@return	array					array(hauteur, true si une seule ligne de texte)
 */
function listexportimport_pdf_row_height($pdf, $row, $widths, $ratio, $lineheight, $hpadding, $vpadding)
{
	$maxlines = 1;
	foreach ($row[2] as $c => $text) {
		if ($text === '' || !isset($widths[$c])) {
			continue;
		}
		if ($row[3][$c] * $ratio > $widths[$c] - 2 * $hpadding + 0.01) {
			$maxlines = max($maxlines, $pdf->getNumLines($text, $widths[$c]));
		}
	}
	return array($maxlines * $lineheight + 2 * $vpadding, $maxlines == 1);
}

/**
 *	Dessine une ligne du tableau
 *
 *	@param	TCPDF	$pdf			Objet PDF (police et couleur de fond déjà réglées par l'appelant)
 *	@param	array	$row			Ligne normalisée (type, alignements, cellules)
 *	@param	array	$widths			Largeur des colonnes (mm)
 *	@param	float	$x				Abscisse du début de ligne (mm)
 *	@param	float	$y				Ordonnée du haut de ligne (mm)
 *	@param	float	$height			Hauteur de la ligne (mm)
 *	@param	bool	$singleline		true si toutes les cellules tiennent sur une ligne de texte
 *	@param	bool	$fill			true pour remplir le fond des cellules
 *	@return	void
 */
function listexportimport_pdf_draw_row($pdf, $row, $widths, $x, $y, $height, $singleline, $fill)
{
	foreach ($widths as $c => $width) {
		$text = isset($row[2][$c]) ? $row[2][$c] : '';
		$align = isset($row[1][$c]) ? $row[1][$c] : 'L';
		if ($singleline) {
			$pdf->SetXY($x, $y);
			$pdf->Cell($width, $height, $text, 0, 0, $align, $fill, '', 0, false, 'T', 'M');
		} else {
			$pdf->MultiCell($width, $height, $text, 0, $align, $fill, 0, $x, $y, true, 0, false, true, $height, 'M');
		}
		$x += $width;
	}
}

// Bloc de titre (1re page)
$y = $margintop;
if ($title !== '') {
	$pdf->SetFont($font, 'B', 11);
	$pdf->MultiCell($availablewidth, 6, $title, 0, 'L', false, 1, $marginleft, $y);
	$y = $pdf->GetY();
}
if ($subtitle !== '') {
	$pdf->SetFont($font, '', 8);
	$pdf->MultiCell($availablewidth, 4, $subtitle, 0, 'L', false, 1, $marginleft, $y);
	$y = $pdf->GetY();
}
$pdf->SetFont($font, '', 7);
$pdf->SetXY($marginleft, $y);
$pdf->Cell($availablewidth / 2, 4, $mysoc->name, 0, 0, 'L');
if (getDolGlobalString('LIST_EXPORT_IMPORT_PRINT_DATE_ON_PDF_EXPORT')) {
	$pdf->Cell($availablewidth / 2, 4, dol_print_date(dol_now(), 'dayhour', 'tzuserrel'), 0, 0, 'R');
}
$y += 4 + 2;

// Lignes de titre du début du tableau : répétées en haut de chaque page
$headerrows = array();
foreach ($rows as $row) {
	if ($row[0] != 'h') {
		break;
	}
	$headerrows[] = $row;
}
$titlefill = array_map('intval', explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR', '230,230,230')));
if (count($titlefill) != 3) {
	$titlefill = array(230, 230, 230);
}
$zebrafill = array(245, 245, 245);
$maxrowheight = $bottomlimit - $footerheight - $margintop - 20;
$pdf->SetDrawColor(170, 170, 170);
$pdf->SetLineWidth(0.2);

// Dessin des lignes
$nbheaderrows = count($headerrows);
$bodyindex = 0;
foreach ($rows as $r => $row) {
	$style = ($row[0] == 'b') ? '' : 'B';
	$pdf->SetFont($font, $style, $fontsize);
	list($height, $singleline) = listexportimport_pdf_row_height($pdf, $row, $widths, $ratio, $lineheight, $hpadding, $vpadding);
	$height = min($height, $maxrowheight);
	if ($r >= $nbheaderrows && $y + $height > $bottomlimit - $footerheight) {
		// Nouvelle page : les lignes de titre du tableau sont répétées
		$pdf->AddPage();
		$y = $margintop;
		$pdf->SetFont($font, 'B', $fontsize);
		$pdf->SetFillColor($titlefill[0], $titlefill[1], $titlefill[2]);
		foreach ($headerrows as $headerrow) {
			list($headerheight, $headersingleline) = listexportimport_pdf_row_height($pdf, $headerrow, $widths, $ratio, $lineheight, $hpadding, $vpadding);
			$headerheight = min($headerheight, $maxrowheight);
			listexportimport_pdf_draw_row($pdf, $headerrow, $widths, $marginleft, $y, $headerheight, $headersingleline, true);
			$y += $headerheight;
		}
		if ($nbheaderrows) {
			$pdf->Line($marginleft, $y, $marginleft + $availablewidth, $y);
		}
		$pdf->SetFont($font, $style, $fontsize);
	}
	if ($row[0] == 'h') {
		$fill = true;
		$pdf->SetFillColor($titlefill[0], $titlefill[1], $titlefill[2]);
	} elseif ($row[0] == 't') {
		$fill = false;
		$pdf->Line($marginleft, $y, $marginleft + $availablewidth, $y);
	} else {
		$fill = ($bodyindex % 2 == 1);
		$pdf->SetFillColor($zebrafill[0], $zebrafill[1], $zebrafill[2]);
		$bodyindex++;
	}
	listexportimport_pdf_draw_row($pdf, $row, $widths, $marginleft, $y, $height, $singleline, $fill);
	$y += $height;
	if ($nbheaderrows && $r == $nbheaderrows - 1) {
		$pdf->Line($marginleft, $y, $marginleft + $availablewidth, $y);
	}
}

// Numéros de page
$nbpages = $pdf->getNumPages();
$pdf->SetFont($font, '', 7);
for ($p = 1; $p <= $nbpages; $p++) {
	$pdf->setPage($p);
	$pdf->SetXY($marginleft, $pageheight - $marginbottom - 4);
	$pdf->Cell($availablewidth, 4, $langs->transnoentitiesnoconv('Page').' '.$p.' / '.$nbpages, 0, 0, 'R');
}

$content = $pdf->Output('', 'S');
dol_syslog('listexportimport export_pdf rows='.count($rows).' cols='.$ncols.' pages='.$nbpages.' font='.$font.' '.$fontsize.'pt time='.round(microtime(true) - $timestart, 2).'s');

ob_end_clean();
top_httphead('application/pdf');
header('Content-Disposition: attachment; filename="export.pdf"');
header('Content-Length: '.strlen($content));
print $content;
