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
	*	\file		./infraspackplus/core/modules/specialfoot/doc/interne.pdf.foot.php
	*	\ingroup	InfraS
	*	\brief		Set of functions used for InfraS PDF generation
	************************************************/

	// Libraries ************************************
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/**
	*	Show top header of page.
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	object			$object			Object shown in PDF
	*	@param	Translate		$outputlangs	Object lang for output
	*	@param	Societe			$fromcompany	Object company
	*	@param	array			$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int				$showdetails	Show company details into footer. This param seems to not be used by standard version.
	*											00000 = vide
	*											1xx0x = address								=> line 1
	*											1xx1x = address need 2 lines				=> line 1 + 1bis
	*											2xxxx = contacts (phone, fax, url, mail)	=> line 2
	*											3xxxx = 1xxx + 2xxx
	*											x1xxx = manager								=> line 3
	*											xx1xx = type soc. + capital					=> line 3
	*											xx2xx = prof. ids							=> line 4
	*											xx3xx = xx1x + xx2x
	*											xxxx1 = footer image						=> line 5
	*	@param	int				$hidesupline	Completly hide the line up to footer (for some edition with only table)
	*	@param	int				$calculseul		Arrête la fonction au calcul de hauteur nécessaire
	*	@param	int				$objEntity		Object entity
	*	@param	string			$image_foot		File Name of the image to show
	*	@param	array			$maxsizeimgfoot	Maximum size for image foot => 'largeur', 'hauteur'
	*	@param	int				$hidepagenum	Hide page num (x/y)
	*	@param	array			$txtcolor		Text color
	*	@param	array			$LineStyle		PDF Line style
	*	@param	int				$noendline		1 to hide the end line (before footer)
	*	@return	int								Return height of bottom margin including footer text
	**/
	function pdf_interne_pagefoot(&$pdf, $object, $outputlangs, $fromcompany, $formatpage, $showdetails, $hidesupline, $calculseul, $objEntity, $image_foot = '', $maxsizeimgfoot, $hidepagenum = 0, $txtcolor = array(0, 0, 0), $LineStyle = null, $noendline = 0)
	{
		global $conf;

		$pdf->SetTextColor($txtcolor[0], $txtcolor[1], $txtcolor[2]);
		$pdf->SetFont('', '', 7);
		$alignL1	= 'R';
		$posx		= 85;
		$w			= 76;
		$line1		= '';
		$htLine1	= 3;
		$line2		= '';
		$line3		= '';
		$line4		= '';
		$line5		= 0;
		// First line of company infos
		if (!empty($fromcompany->name)) {
			$line1	.= $fromcompany->name; // Company name
		}
		if (!empty($fromcompany->address)) {
			$line1	.= ($line1 ? ' - ' : '').str_replace(CHR(13).CHR(10),' - ',$fromcompany->address); // Address
		}
		if (!empty($fromcompany->zip)) {
			$line1	.= ($line1 ? ' - ' : '').$fromcompany->zip; // Zip code
		}
		if (!empty($fromcompany->town)) {
			$line1	.= ($line1 ? ' ' : '').$fromcompany->town; // Town
		}
		if (!empty($fromcompany->country_code)) {
			$line1	.= ($line1 ? ' - ' : '').$outputlangs->transnoentitiesnoconv('Country'.$fromcompany->country_code); // Country
		}
		// Second line of company infos
		if (!empty($fromcompany->phone)) {
			$line2	.= $outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($fromcompany->phone))); // Phone
		}
		if (!empty($fromcompany->fax)) {
			$line2	.= ($line2 ? ' - ' : '').$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($fromcompany->fax))); // Fax
		}
		if (!empty($fromcompany->url)) {
			$line2	.= ($line2 ? ' - ' : '').$fromcompany->url; // URL
		}
		// Third line of company infos
		if (!empty($fromcompany->forme_juridique_code)) {
			$line3 .= $outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code)); // Juridical status
		}
		if (!empty($fromcompany->capital)) { // Capital
			$tmpamounttoshow	= price2num($fromcompany->capital); // This field is a free string or a float
			if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) {
				$line3	.= ($line3 ? ' - ' : '').$outputlangs->transnoentities('CapitalOf', price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
			} elseif (!empty($fromcompany->capital) ) {
				$line3	.= ($line3 ? ' - ' : '').$outputlangs->transnoentities('CapitalOf', $tmpamounttoshow);
			}
		}
		// Fourth line of company infos
		if (!empty($fromcompany->idprof2)) { // Prof Id 2
			$field	= $outputlangs->transcountrynoentities('ProfId2', $fromcompany->country_code);
			if (preg_match('/\((.*)\)/i', $field, $reg)) {
				$field	= $reg[1];
			}
			$tmpID	= pdf_InfraSPlus_build_IDs('ID2', $outputlangs->convToOutputCharset($fromcompany->idprof2), $fromcompany->country_code);
			$line4	.= $field.' : '.$tmpID;
		}
		if ($fromcompany->tva_intra != '') {	// IntraCommunautary VAT
			$tmpID	= pdf_InfraSPlus_build_IDs('TVA', $outputlangs->convToOutputCharset($fromcompany->tva_intra), $fromcompany->country_code);
			$line4	.= ($line4 ? ' - ' : '').$outputlangs->transnoentities('VATIntraShort').' : '.$tmpID;
		}
		// Image line of company infos
		$logodir	= !empty($conf->mycompany->multidir_output[$objEntity]) ? $conf->mycompany->multidir_output[$objEntity] : $conf->mycompany->dir_output;
		$logospied	= $logodir.'/logos/'.$image_foot;	// Logos partenaires en ligne 5
		if (is_readable($logospied)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
			$imglinesize	= pdf_InfraSPlus_getSizeForImage($logospied, 200, 30);
			if (!empty($imglinesize['height'])) {
				$line5	= $imglinesize['height'];
			}
		}
		// The start of the bottom of this page footer is positioned according to # of lines
		$nopage				= $pdf->PageNo();
		$nbpage				= $pdf->getNumPages();
		$marginwithfooter	= (($nopage == $nbpage) && empty($hidesupline) ? 1 : 0) + 5 + $line5 + $formatpage['mbasse'];
		if ($calculseul == 1) {
			return $marginwithfooter;
		}
		$posy	= $formatpage['hauteur'] - $marginwithfooter + 5;
		$pdf->SetY($posy);
		if (!empty($logospied) && is_readable($logospied) && !empty($line5)) {
			$posxpicture	= $formatpage['mgauche'] + (($formatpage['largeur'] - $formatpage['mgauche'] - $formatpage['mdroite'] - $imglinesize['width']) / 2);	// centre l'image dans la colonne
			$pdf->Image($logospied, $posxpicture, $posy, $imglinesize['width'], $line5);	// width = 0 or height = 0 (auto)
			$posy	+= 1.5;
		}
		if (!empty($line1)) {
			$pdf->MultiCell($w, 2, $line1, 0, $alignL1, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= 3;
		}
		if (!empty($line2)) {
			$pdf->MultiCell($w, 2, $line2, 0, $alignL1, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= 3;
		}
		if (!empty($line3)) {
			$pdf->MultiCell($w, 2, $line3, 0, $alignL1, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= 3;
		}
		if (!empty($line4)) {
			$pdf->MultiCell($w, 2, $line4, 0, $alignL1, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
		}
		$pdf->SetFont('', '', 7);
		if (empty($hidepagenum)) { // Show page nb only on iso languages (so default Helvetica font)
			$prevFont									= $pdf->getFontFamily();
			$pdf->SetFont('Helvetica');
			if (!getDolGlobalString('MAIN_USE_FPDF', '')) {
				$pdf->MultiCell(26, 2, $pdf->PageNo().' / '.$pdf->getAliasNbPages(), 0, 'R', 0, 1, $formatpage['largeur'] - ($formatpage['mdroite'] + 20), $formatpage['hauteur'] - $formatpage['mbasse'], true, 0, 0, false, 0, 'M', false);
			} else {
				$pdf->MultiCell(26, 2, $pdf->PageNo().' / {nb}', 0, 'R', 0, 1, $formatpage['largeur'] - ($formatpage['mdroite'] + 20), $formatpage['hauteur'] - $formatpage['mbasse'], true, 0, 0, false, 0, 'M', false);
			}
			$pdf->SetFont($prevFont);
		}
		return $marginwithfooter;
	}
