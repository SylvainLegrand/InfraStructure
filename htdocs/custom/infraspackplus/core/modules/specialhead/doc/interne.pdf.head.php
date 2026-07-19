<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand 		- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infraspackplus/core/modules/specialhead/doc/interne.pdf.head.php
	*	\ingroup	InfraS
	*	\brief		Set of functions used for InfraS PDF generation
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/**
	*	Show top header of page.
	*
	*	@param	TCPDF		$pdf					Object PDF
	*	@param	object		$object					Object to show
	*	@param	int			$showaddress			0=no, 1=yes
	*	@param	Translate	$outputlangs			Object lang for output
	*	@param	array		$headertxtcolor			Color for header text
	*	@param	int			$header_align_left		0=no, 1=yes
	*	@param	float		$decal_round			value used to decal text for rounded corner
	*	@param	array		$formatpage				all poage dimensions and margins
	*	@param	string		$logo					file name used for logo
	*	@param	object		$emetteur				Object company
	*	@param	int			$tab_hl					Line height
	*	@param	int			$header_after_addr		0=no, 1=yes
	*	@param	int			$title_size				Title height multiplicator
	*	@param	string		$titlekey				Translation key for title
	*	@param	int			$ref_from_cust			0=no, 1=yes
	*	@param	int			$datesbold				0=no, 1=yes
	*	@param	int			$dates_br				0=no, 1=yes
	*	@param	int			$show_num_cli			0=no, 1=yes
	*	@param	int			$num_cli_frm			0=no, 1=yes
	*	@param	int			$show_code_cli_compt	0=no, 1=yes
	*	@param	int			$code_cli_compt_frm		0=no, 1=yes
	*	@param	int			$add_creator_in_header	0=no, 1=yes
	*	@param	int			$use_iso_location		0=no, 1=yes
	*	@param	int			$adr					ID
	*	@param	int			$typeadr				Address type for sender ('' || I || S || C || supplierInvoice || accountStatus)
	*	@param	int			$adrlivr				ID
	*	@param	int			$Rounded_rect			Radius corner value
	*	@param	string		$customerAddrSelect		Address type (thirdparty || thirdparty + contact name || etc...)
	*	@param	int			$Sst					Subcontractor ID
	*	@param	int			$adrSst					Subcontractor address ID
	*	@param	string		$qrcodestring			String to show as QR code like ref + company name + etc...
	*	@param	float		$deposits				Amount
	*	@param	array		$lines_deposits			list of line used for deposit
	*	@param	string		$title_if_deposit		Translation key for title
	*	@param	string		$adrfact				Address code for billing address (content of 'INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT' global constant)
	*	@param	int			$includealias			0=no, 1=yes
	*	@param	int			$left_recep_corner		x position for left top corner of recepient frame
	*	@param	int			$top_recep_corner		y position for left top corner of recepient frame
	*	@param	int			$cf_show_creation_date	0=no, 1=yes
	*	@return	array								Return height of header and height of address frame
	**/
	function pdf_interne_pagehead(&$pdf, $object, $showaddress, $outputlangs, $headertxtcolor, $header_align_left, $decal_round, $formatpage, $logo, $emetteur, $tab_hl, $header_after_addr, $title_size, $titlekey,
									$ref_from_cust, $datesbold, $dates_br, $show_num_cli, $num_cli_frm, $show_code_cli_compt, $code_cli_compt_frm, $add_creator_in_header, $use_iso_location, $adr, $typeadr, $adrlivr, $Rounded_rect,
									$customerAddrSelect, $Sst = -2, $adrSst = -2, $qrcodestring = '', $deposits = 0, $lines_deposits = [], $title_if_deposit = '', $adrfact = '', $includealias = 0, $left_recep_corner = 92, $top_recep_corner = 40,
									$cf_show_creation_date = 0)
	{

		$use_doli_addr_livr		= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 0);
		$doli_addr_livr_recep	= getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP') && !empty($use_doli_addr_livr) ? getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0) : 0;
		$hide_recep_frame		= getDolGlobalInt('INFRASPLUS_PDF_HIDE_RECEP_FRAME', 0);
		$default_font_size		= pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor((int) $headertxtcolor[0], (int) $headertxtcolor[1], (int) $headertxtcolor[2]);
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$dimCadres				= array ('S' => ($formatpage['largeur'] - ($formatpage['mgauche'] + 6 + $left_recep_corner + $formatpage['mdroite'])), 'R' => $left_recep_corner);	// page width = 210 (A4) 92 + 92  = 184 => keep 210 - 184 for margins => 26 ; 10 right and left and 6 on the middle
		$w						= $header_align_left ? 92 - $decal_round : 92;
		$align					= $header_align_left ? 'L' : 'R';
		$posy					= $formatpage['mhaute'];
		$posx					= $formatpage['largeur'] - $formatpage['mdroite'] - $w;
		// Logo
		$heightLogo				= pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $logo, $emetteur, $formatpage['mgauche'], $tab_hl, $headertxtcolor, $object->entity);
		$heightLogo				+= $posy + $tab_hl;
		if (empty($header_after_addr)) {
			if (!empty($qrcodestring)) {
				$sizeBC		= 25;
				$styleBC	= array('position'		=> '',
									'border'		=> false,
									'hpadding'		=> '0',
									'vpadding'		=> '0',
									'fgcolor'		=> array($headertxtcolor[0], $headertxtcolor[1], $headertxtcolor[2]),
									'bgcolor'		=> false,	// array(255,255,255)
									'module_width'	=> 1,		// width of a single module in points
									'module_height'	=> 1		// height of a single module in points
									);
				$pdf->write2DBarcode($qrcodestring, 'QRCODE,M', $posx + 2, $posy, $sizeBC, $sizeBC, $styleBC, 'N');
			} else {
				$sizeBC	= 0;
			}
			$pdf->SetFont('', 'B', $default_font_size * $title_size);
			if (!empty($object->situation_cycle_ref)) {
				$situationinvoice	= True;
				$titlekey			= $object->type == 2 ? 'InvoiceAvoir'		: 'PDFSituationTitle';
				$titlekeyAV			= $object->type == 2 ? 'PDFSituationTitle'	: '';
			}
			if (!empty($situationinvoice)) {
				$title	= $outputlangs->transnoentities($titlekey, $object->situation_counter).(!empty($titlekeyAV) ? ' ('.$outputlangs->transnoentities($titlekeyAV, $object->situation_counter).')' : '');
			} else {
				$title	= $outputlangs->transnoentities($titlekey).((!empty($deposits) || !empty($lines_deposits)) && !empty($title_if_deposit) ? ' '.$outputlangs->transnoentities($title_if_deposit) : '');
			}
			$pdf->MultiCell($w - $sizeBC - 3, $tab_hl * $title_size, $title, '', $align, 0, 1, $posx + $sizeBC + 3, $posy, true, 0, 0, false, 0, 'M', false);
			$posy							= $pdf->getY();
		}
		if (!empty($showaddress)) {
			if (in_array($object->element, array('societe'))) {
				$arrayidcontact	= array('I' => '',
										'E' => '',
										'L' => ''
										);
				$typeadr		= 'accountStatus';
			} elseif (in_array($object->element, array('propal', 'supplier_proposal'))) {
				$arrayidcontact	= array('I'  => $object->getIdContact('internal', 'SALESREPFOLL'),
										'LI' => $object->element == 'supplier_proposal' ? $object->getIdContact('internal', 'SHIPPING') : [],
										'E'  => $object->getIdContact('external', 'CUSTOMER'),
										'L'  => $object->getIdContact('external', 'SHIPPING')
										);
			} elseif (in_array($object->element, array('commande', 'order_supplier'))) {
				$arrayidcontact	= array('I'  => $object->getIdContact('internal', 'SALESREPFOLL'),
										'LI' => $object->element == 'order_supplier' ? $object->getIdContact('internal', 'SHIPPING') : [],
										'E'  => $object->getIdContact('external', (!empty($doli_addr_livr_recep) ? 'SHIPPING' : 'CUSTOMER')),
										'L'  => (empty($doli_addr_livr_recep) ? $object->getIdContact('external', 'SHIPPING') : [])
										);
			} elseif (in_array($object->element, array('facture'))) {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'BILLING'),
										'L' => $object->getIdContact('external', 'SHIPPING')
										);
			} else {
				$arrayidcontact	= array('I' => $object->getIdContact('internal', 'SALESREPFOLL'),
										'E' => $object->getIdContact('external', 'CUSTOMER'),
										);
			}
			$addresses			= [];
			$dimCadres['yR']	= $use_iso_location && $posy <= $top_recep_corner ? $top_recep_corner : ($heightLogo > $posy + $tab_hl ? $heightLogo : $posy + $tab_hl);
			$dimCadres['yS']	= $heightLogo + $tab_hl <= $dimCadres['yR'] ? $dimCadres['yR'] : $heightLogo;
			$dimCadres['Y']		= $dimCadres['yR'] > $dimCadres['yS'] ? $dimCadres['yR'] : $dimCadres['yS'];
			$addresses			= pdf_interne_getAddresses($object, $outputlangs, $arrayidcontact, $adr, $adrlivr, $emetteur, 0, $typeadr, $adrfact, 0, $Sst, $adrSst, $customerAddrSelect, $includealias);
			$hauteurcadre		= pdf_interne_writeAddresses($pdf, $object, $outputlangs, $formatpage, $dimCadres, $tab_hl, $emetteur, $addresses, $Rounded_rect, false, $hide_recep_frame, $title_size, $ref_from_cust, $datesbold,
															$dates_br, $show_num_cli, $num_cli_frm, $show_code_cli_compt, $code_cli_compt_frm, $add_creator_in_header, $cf_show_creation_date);
		}
		$hauteurhead	= array('totalhead'		=> $dimCadres['Y'] + $hauteurcadre,
								'hauteurcadre'	=> $hauteurcadre,
								'livrshow_name'	=> $addresses['livrshow_name'],
								'livrshow'		=> $addresses['livrshow']
								);
		return $hauteurhead;
	}

	/**
	*	Get all addresses needed
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	Translate	$outputlangs	Object lang for output
	*	@param	array		$arrayidcontact	List of contact ID
	*	@param	string		$adr			Alternative address ID for sender
	*	@param	int			$adrlivr		Shipping address ID
	*	@param	object		$emetteur		Object company
	*	@param	boolean		$invLivr		Inversion shipping address and recepient one
	*	@param	string		$typeadr		for supplier documents if we use both internal and external adrresses (shipping to customer)
	*											- I : Internal
	*											- S : Supplier
	*											- C : Customer
	*											- supplierInvoice : Supplier Invoice
	*											- accountStatus : Thirdparty account status
	*	@param	string		$adrfact		Address code for billing address (content of 'INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT' global constant)
	*	@param	int			$ticket			set to 1 for special format (small page)
	*	@param	int			$Sst			Subcontractor ID
	*	@param	int			$adrSst			Subcontractor address ID
	*	@param	string		$customerAddr	Customer address used ('T' = Third party only, 'C' = Linked Contact if exist or third party, 'B' = Third party addrss + contact name, 'A' = Contact addrss + Third party name)
	*	@param	int			$includealias	Use aliases in customer address (option from 'before pdf generation')
	*	@return	array		Return all addresses found
	**/
	function pdf_interne_getAddresses($object, $outputlangs, $arrayidcontact, $adr, $adrlivr, $emetteur, $invLivr = 0, $typeadr = '', $adrfact = '', $ticket = 0, $Sst = -2, $adrSst = -2, $customerAddr = '', $includealias = 0)
	{
		global $db;

		$thirdparty				= !empty($object->thirdparty) ? $object->thirdparty : $object;
		$show_emet_details			= empty($ticket) && getDolGlobalInt('INFRASPLUS_PDF_SHOW_EMET_DETAILS') ? getDolGlobalInt('INFRASPLUS_PDF_SHOW_EMET_DETAILS') : 0;
		$show_sender_alias			= getDolGlobalInt('INFRASPLUS_PDF_SHOW_SENDER_ALIAS', 0);
		$show_recep_details			= empty($ticket) && getDolGlobalInt('INFRASPLUS_PDF_SHOW_RECEP_DETAILS') ? getDolGlobalInt('INFRASPLUS_PDF_SHOW_RECEP_DETAILS') : 0;
		$show_livr_details			= empty($ticket) && getDolGlobalInt('INFRASPLUS_PDF_SHOW_LIVR_DETAILS') ? getDolGlobalInt('INFRASPLUS_PDF_SHOW_LIVR_DETAILS') : 0;
		$showadrlivr				= getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', 0);
		$free_addr_livr				= getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', '');
		$def_adrlivrfour			= getDolGlobalString('INFRASPLUS_PDF_DEFAULT_ADDR_DELIV', '');
		if ($typeadr == 'supplierInvoice') {
			$addresslivrstatic	= $adrlivr;
		} elseif (!empty($adrlivr) && (empty($typeadr) || $typeadr == 'I' || $typeadr == 'S')) {
			if ($adrlivr > 0) {
				$addresslivrstatic	= new Address($db);
				$addresslivrfound	= $addresslivrstatic->fetch($adrlivr);
				if ($addresslivrfound != 1) {
					$addresslivrstatic	= '';
				}
			} else {
				$addresslivrstatic	= $adrlivr == -1 ? 'Default' : '';
			}
		} elseif (!empty($adrlivr) && $typeadr == 'C') {
			$addresslivrstatic	= new Societe($db);
			$addresslivrfound	= $addresslivrstatic->fetch($adrlivr);
			if ($addresslivrfound <= 0) {
				$addresslivrstatic	= '';
			}
		}
		if (!empty($free_addr_livr)) {
			$extrafields	= new ExtraFields($db);
			$extralabels	= $extrafields->fetch_name_optionals_label($object->table_element);
			if (isset($extrafields->attributes[$object->table_element]['label'][$free_addr_livr])) {
				$printable		= intval($extrafields->attributes[$object->table_element]['printable'][$free_addr_livr]);
				$value			= pdf_InfraSPlus_formatNotes($object, $outputlangs, $extrafields->showOutputField($free_addr_livr, $object->array_options['options_'.$free_addr_livr] ?? '', '', $object->table_element));
				$free_addr_livr	= $printable == 1 || (!empty($value) && $printable == 2) ? $value : '';	// check if something is writting for this extrafield according to the extrafield management
			} else {
				$free_addr_livr	= '';	// extrafield configured (INFRASPLUS_PDF_FREE_LIVR_EXF) but not defined for this table_element
			}
		}
		$use_doli_addr_livr		= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 0);
		$use_doli_addr_fact		= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_FACTURATION', 0);
		$doli_addr_livr_recep	= getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0) && !empty($use_doli_addr_livr) ? getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0) : 0;
		$showadrSsT				= getDolGlobalInt('INFRASPLUS_PDF_ADRESSE_SOUS_TRAITANT', 0);
		if (!empty($showadrSsT)) {
			if (!empty($adrSst) && $adrSst > 0) {
				$addresssststatic	= new Address($db);
				$addresssstfound	= $addresssststatic->fetch($adrSst);
				if ($addresssstfound != 1) {
					$addresssststatic	= '';
				}
			} elseif ($adrSst == -1 && !empty($Sst) && $Sst > 0) {
				$addresssststatic	= new Societe($db);
				$addresssstfound	= $addresssststatic->fetch($Sst);
				if ($addresssstfound <= 0) {
					$addresssststatic	= '';
				}
			}
		}
		// Sender properties
		$sender_Alias	= '';
		$carac_emetteur	= '';
		// Add internal contact if defined
		if (is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0) {
			$object->fetch_user($arrayidcontact['I'][0]);
			$carac_emetteur	.= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		}
		if (!empty($addresslivrstatic) && $typeadr == 'supplierInvoice') {
			$carac_emetteur .= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $object->client, '', 0, ($show_emet_details ? 'source' : 'sourcewithnodetails'), $object, 1, $ticket);
		} elseif (!empty($adr)) {
			$addressstatic	= new Address($db);
			$addressfound	= $addressstatic->fetch($adr);
			if ($addressfound == 1) {
				$sender_Alias	= $show_sender_alias ? $addressstatic->name : '';
				$carac_emetteur .= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $addressstatic, $thirdparty, '', 0, ($show_emet_details ? 'source' : 'sourcewithnodetails'), $object, 1, $ticket);
			}
		} else {
			$carac_emetteur .= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $thirdparty, '', 0, ($show_emet_details ? 'source' : 'sourcewithnodetails'), $object, 1, $ticket);
		}
		// Recipient properties
		if (!empty($arrayidcontact['U']) && is_object($arrayidcontact['U'])) {	// Expense report
			$carac_client_name	= $outputlangs->transnoentities("AUTHOR").' : '.$arrayidcontact['U']->getFullName($outputlangs);
			$carac_client		= $outputlangs->transnoentities("DateCreation").' : '.dol_print_date($object->date_create, 'day', false, $outputlangs)."\n";
			$det_client			= $outputlangs->convToOutputCharset(dol_format_address($arrayidcontact['U']->address, 0, "\n", $outputlangs))."\n";
			$det_client			.= $arrayidcontact['U']->email ? $outputlangs->transnoentities("Email").' : '.$outputlangs->convToOutputCharset($arrayidcontact['U']->email)."\n" : '';
			$det_client			.= $arrayidcontact['B']->iban ? $outputlangs->transnoentities("IBAN").' : '.$outputlangs->convToOutputCharset($arrayidcontact['B']->iban)."\n" : '';
			$carac_client		.= $det_client ? $det_client."\n" : '';
			if ($object->fk_statut == 99 && $object->fk_user_refuse > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_refuse);
				$carac_client	.= $outputlangs->transnoentities("REFUSEUR").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("MOTIF_REFUS").' : '.$outputlangs->convToOutputCharset($object->detail_refuse)."\n";
				$carac_client	.= $outputlangs->transnoentities("DATE_REFUS").' : '.dol_print_date($object->date_refuse, 'day', false, $outputlangs);
			} elseif ($object->fk_statut == 4 && $object->fk_user_cancel > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_cancel);
				$carac_client	.= $outputlangs->transnoentities("CANCEL_USER").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("MOTIF_CANCEL").' : '.$outputlangs->convToOutputCharset($object->detail_cancel)."\n";
				$carac_client	.= $outputlangs->transnoentities("DATE_CANCEL").' : '.dol_print_date($object->date_cancel, 'day', false, $outputlangs);
			} elseif ($object->fk_user_approve > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_approve);
				$carac_client	.= $outputlangs->transnoentities("VALIDOR").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("DateApprove").' : '.dol_print_date($object->date_approve, 'day', false, $outputlangs);
			}
			if ($object->fk_statut == 6 && $object->fk_user_paid > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_paid);
				$carac_client	.= $outputlangs->transnoentities("AUTHORPAIEMENT").' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities("DATE_PAIEMENT").' : '.dol_print_date($object->date_paiement, 'day', false, $outputlangs);
			}
		} else {
			$adrfactfound	= 0;
			if (!empty($adrfact)) {
				$client			= infraspackplus_check_parent_addr_fact($object);
				$adrfactstatic	= new Address($db);
				$adrfactfound	= $adrfactstatic->fetch(0, $client->id, $adrfact);
			}
			if ($adrfactfound == 1) {
				$carac_client	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $adrfactstatic, '', false, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 2, $ticket);
				if (!empty($carac_client)) {
					$carac_client_name	= $outputlangs->convToOutputCharset($adrfactstatic->name);
				}
			} else {
				$usecontact		= false;
				if (!empty($use_doli_addr_fact) && $object->element == 'facture' && is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0) {
					$usecontact	= true;
					$result		= $object->fetch_contact($arrayidcontact['E'][0]);
				} elseif (!empty($doli_addr_livr_recep) && is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0 && $object->element == 'commande') {
					$usecontact	= true;
					$result		= $object->fetch_contact($arrayidcontact['E'][0]);
				} elseif (in_array($customerAddr, array('C', 'A')) && is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0) {
					$usecontact	= true;
					$result		= $object->fetch_contact($arrayidcontact['E'][0]);
				} elseif ($customerAddr == 'B' && is_array($arrayidcontact['E']) && count($arrayidcontact['E']) > 0) {
					$result		= $object->fetch_contact($arrayidcontact['E'][0]);
				}
				$thirdpartystatic	= $typeadr == 'supplierInvoice' ? $addresslivrstatic : ($typeadr == 'accountStatus' ? $thirdparty : infraspackplus_check_parent_addr_fact ($object));
				$carac_client_name	= pdf_InfraSPlus_Build_Third_party_Name($thirdpartystatic, $outputlangs, $includealias, $object->contact, $customerAddr);
				$carac_client		= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $thirdpartystatic, ($usecontact ? $object->contact : ''), $usecontact, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 1, $ticket);
			}
			// Shipping address
			// Priority 1 : static InfraSPlus address (adrlivrfour selected), 2 : internal SHIPPING contact, 3 : external SHIPPING contact
			if (!empty($use_doli_addr_livr) && !empty($addresslivrstatic) && empty($free_addr_livr)) {
				if ($addresslivrstatic == 'Default') {
					$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $thirdparty, '', 0, $show_livr_details ? 'targetwithdetails' : 'target', $object, 0, $ticket);
				} else {
					$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $addresslivrstatic, '', 0, $show_livr_details ? 'targetwithdetails' : 'target', $object, 0, $ticket);
				}
			} elseif (!empty($use_doli_addr_livr) && isset($arrayidcontact['LI']) && is_array($arrayidcontact['LI']) && count($arrayidcontact['LI']) > 0) {
				$result	= $object->fetch_user($arrayidcontact['LI'][0]);
				if ($result > 0 && is_object($object->user)) {
					$livrshow_name	= $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
					$livrshow		= $outputlangs->convToOutputCharset(dol_format_address($object->user, 0, "\n", $outputlangs));
				}
			} elseif (!empty($use_doli_addr_livr) && is_array($arrayidcontact['L']) && count($arrayidcontact['L']) > 0) {
				$companyDiff	= 0;
				$result			= $object->fetch_contact($arrayidcontact['L'][0]);
				$usecontact		= in_array($customerAddr, getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON') ? array('C', 'A', 'T', 'B') : array('C', 'A')) ? true : false;
				if ($object->contact->socid != $thirdparty->id) {
					$companyDiff	= 1;
					if ($object->contact->socid > 0) {
						$object->contact->fetch_thirdparty();
					}
					if (!is_object($object->contact->thirdparty)) {	// it's a contact not linked to a third party
						$usecontact						= true; // force to use contact address
						$object->contact->thirdparty	= new Societe($db); // not to have error in building address from target company
					}
				}
				$livrshow_name	= pdf_InfraSPlus_Build_Third_party_Name(($companyDiff ? $object->contact->thirdparty : $thirdparty), $outputlangs, $includealias, $object->contact, $customerAddr);
				$livrshow		= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $companyDiff ? $object->contact->thirdparty : $thirdparty, $usecontact ? $object->contact : '', $usecontact, $show_recep_details ? 'targetwithdetails' : 'target', $object, -1, $ticket);
			} elseif (!empty($showadrlivr) && !empty($addresslivrstatic) && empty($free_addr_livr)) {
				if ($addresslivrstatic == 'Default') {
					$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $thirdparty, '', 0, $show_livr_details ? 'targetwithdetails' : 'target', $object, 0, $ticket);
				} else {
					$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $addresslivrstatic, '', 0, $show_livr_details ? 'targetwithdetails' : 'target', $object, 0, $ticket);
				}
			}
			// Subcontractor address
			if (!empty($showadrSsT) && $addresssststatic) {
				$SsTshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $addresssststatic, '', 0, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, 0, $ticket);
			}
		}
		return	array(	'sender_Alias'		=> $sender_Alias,
						'carac_emetteur'	=> $carac_emetteur,
						'carac_client_name'	=> !empty($invLivr) && !empty($livrshow)			? $addresslivrstatic->name	: $carac_client_name,
						'carac_client'		=> !empty($invLivr) && !empty($livrshow)			? $livrshow					: $carac_client,
						'livrshow_name'		=> empty($free_addr_livr) && !empty($livrshow_name)	? $livrshow_name			: '',
						'livrshow'			=> empty($free_addr_livr) && !empty($livrshow)		? $livrshow					: $free_addr_livr,
						'SsTshow'			=> !empty($SsTshow)									? $SsTshow					: ''
					);
	}

	/**
	*	Show top small header of page.
	*
	*	@param	TCPDF		$pdf					The PDF factory
	*	@param	object		$object					Object shown in PDF
	*	@param	Translate	$outputlangs			Object lang for output
	*	@param	array		$formatpage				Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	array		$dimCadres				Frame dimensions => 'R', 'S', 'Y'
	*	@param	int			$tab_hl					Line height
	*	@param	object		$emetteur				Object company
	*	@param	array		$addresses				All addresses found
	*	@param	int			$Rounded_rect			Radius corner value
	*	@param	boolean		$ndf					Frames title for expense report
	*	@param	int			$hide_recep_frame		0=no, 1=yes
	*	@param	int			$title_size				Title height multiplicator
	*	@param	int			$ref_from_cust			0=no, 1=yes
	*	@param	int			$datesbold				0=no, 1=yes
	*	@param	int			$dates_br				0=no, 1=yes
	*	@param	int			$show_num_cli			0=no, 1=yes
	*	@param	int			$num_cli_frm			0=no, 1=yes
	*	@param	int			$show_code_cli_compt	0=no, 1=yes
	*	@param	int			$code_cli_compt_frm		0=no, 1=yes
	*	@param	int			$add_creator_in_header	0=no, 1=yes
	*	@param	int			$cf_show_creation_date	0=no, 1=yes
	*	@return	float		return frame height
	**/
	function pdf_interne_writeAddresses(&$pdf, $object, $outputlangs, $formatpage, $dimCadres, $tab_hl, $emetteur, $addresses, $Rounded_rect, $ndf = false, $hide_recep_frame, $title_size, $ref_from_cust, $datesbold, $dates_br,
										$show_num_cli, $num_cli_frm, $show_code_cli_compt, $code_cli_compt_frm, $add_creator_in_header, $cf_show_creation_date)
	{
		global $conf;

		$invert_sender_recipient	= getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT', 0);
		$frmeLineW					= getDolGlobalFloat('INFRASPLUS_PDF_FRM_E_LINE_WIDTH', 0.2);
		$frmeLineDash				= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_LINE_DASH', 0);
		$frmeLineColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_COLOR', '128,128,128');
		$frmeLineColor				= explode(',', $frmeLineColor);
		$frmeBgColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_E_BG_COLOR', '109,70,140');
		$frmeBgColor				= explode(',', $frmeBgColor);
		$frmeAlpha					= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_OPACITY', 30);
		$frmrLineW					= getDolGlobalFloat('INFRASPLUS_PDF_FRM_R_LINE_WIDTH', 0.2);
		$frmrLineDash				= getDolGlobalInt('INFRASPLUS_PDF_FRM_R_LINE_DASH', 0);
		$frmrLineColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_COLOR', '128,128,128');
		$frmrLineColor				= explode(',', $frmrLineColor);
		$frmrBgColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_R_BG_COLOR', '109,70,140');
		$frmrBgColor				= explode(',', $frmrBgColor);
		$frmrAlpha					= getDolGlobalInt('INFRASPLUS_PDF_FRM_R_OPACITY', 30);
		$frmeLineCap				= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		$frmeLineStyle				= array('width'=>$frmeLineW, 'dash'=>$frmeLineDash, 'cap'=>$frmeLineCap, 'color'=>$frmeLineColor);
		$frmrLineCap				= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		$frmrLineStyle				= array('width'=>$frmrLineW, 'dash'=>$frmrLineDash, 'cap'=>$frmrLineCap, 'color'=>$frmrLineColor);
		$default_font_size			= pdf_getPDFFontSize($outputlangs);
		if ($formatpage['largeur'] < 210) {
			$dimCadres['R']	= 84;	// To work with US executive format
		}
		if (!empty($invert_sender_recipient)) {
			$dimCadres['xS']	= empty($dimCadres['xS']) ? $formatpage['largeur'] - $formatpage['mdroite'] - $dimCadres['S'] : $dimCadres['xS'];
			$dimCadres['xR']	= empty($dimCadres['xR']) ? $formatpage['mgauche'] : $dimCadres['xR'];
		} else {
			$dimCadres['xS']	= empty($dimCadres['xS']) ? $formatpage['mgauche'] : $dimCadres['xS'];
			$dimCadres['xR']	= empty($dimCadres['xR']) ? $formatpage['largeur'] - $formatpage['mdroite'] - $dimCadres['R'] : $dimCadres['xR'];
		}
		$pdf->startTransaction();
		$hauteurscadres	= pdf_interne_writeFrame($pdf, $object, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, 0, $hide_recep_frame, $formatpage, $title_size, $ref_from_cust, $datesbold, $dates_br,
													$show_num_cli, $num_cli_frm, $show_code_cli_compt, $code_cli_compt_frm, $add_creator_in_header, $cf_show_creation_date);
		$pdf->rollbackTransaction(true);
		// Show sender frame
		$pdf->SetAlpha($frmeAlpha / 100);
		$frme	= (implode(',', $frmeLineColor) == '255, 255, 255' ? '' : 'D').( implode(',', $frmeBgColor) == '255, 255, 255' ? '' : 'F');
		$pdf->RoundedRect($dimCadres['xS'], $dimCadres['yS'], $dimCadres['S'], $hauteurscadres['sender'], $Rounded_rect, '1111', $frme, $frmeLineStyle, $frmeBgColor);
		// Show recipient frame
		$pdf->SetAlpha($frmrAlpha / 100);
		$frmr	= (implode(',', $frmrLineColor) == '255, 255, 255' ? '' : 'D').( implode(',', $frmrBgColor) == '255, 255, 255' ? '' : 'F');
		$pdf->RoundedRect($dimCadres['xR'], $dimCadres['yR'], $dimCadres['R'], $hauteurscadres['recipient'], $Rounded_rect, '1111', $frmr, $frmrLineStyle, $frmrBgColor);
		$pdf->SetAlpha(1);
		pdf_interne_writeFrame($pdf, $object, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, 0, $hide_recep_frame, $formatpage, $title_size, $ref_from_cust, $datesbold, $dates_br,
								$show_num_cli, $num_cli_frm, $show_code_cli_compt, $code_cli_compt_frm, $add_creator_in_header, $cf_show_creation_date);
		return $hauteurscadres['sender'] > $hauteurscadres['recipient'] ? $hauteurscadres['sender'] : $hauteurscadres['recipient'];
	}

	/**
	*	Show top small header of page.
	*
	*	@param	TCPDF		$pdf				The PDF factory
	*	@param	object		$object				Object shown in PDF
	*	@param	Translate	$outputlangs		Object lang for output
	*	@param	int			$default_font_size	Font size
	*	@param	int			$tab_hl				Line height
	*	@param	array		$dimCadres			Frame dimensions => 'R', 'S', 'Y', 'xS', 'xR'
	*	@param	object		$emetteur			Object company
	*	@param	array		$addresses			All addresses found
	*	@param	boolean		$ticket				Ticket format
	*	@param	int			$hide_recep_frame	Hide frames
	*	@param	array		$formatpage			Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int			$title_size				Title height multiplicator
	*	@param	int			$ref_from_cust			0=no, 1=yes
	*	@param	int			$datesbold				0=no, 1=yes
	*	@param	int			$dates_br				0=no, 1=yes
	*	@param	int			$show_num_cli			0=no, 1=yes
	*	@param	int			$num_cli_frm			0=no, 1=yes
	*	@param	int			$show_code_cli_compt	0=no, 1=yes
	*	@param	int			$code_cli_compt_frm		0=no, 1=yes
	*	@param	int			$add_creator_in_header	0=no, 1=yes
	*	@param	int			$cf_show_creation_date	0=no, 1=yes
	*	@return	float		Return frame height
	**/
	function pdf_interne_writeFrame(&$pdf, $object, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, $ticket = 0, $hide_recep_frame, $formatpage, $title_size, $ref_from_cust, $datesbold, $dates_br,
									$show_num_cli, $num_cli_frm, $show_code_cli_compt, $code_cli_compt_frm, $add_creator_in_header, $cf_show_creation_date)
	{
		global $conf;

		$frmeTxtColor		= getDolGlobalString('INFRASPLUS_PDF_FRM_E_TEXT_COLOR', '0,0,0');
		$frmeTxtColor		= explode(',', $frmeTxtColor);
		$frmrTxtColor		= getDolGlobalString('INFRASPLUS_PDF_FRM_R_TEXT_COLOR', '0,0,0');
		$frmrTxtColor		= explode(',', $frmrTxtColor);
		$dateduetxtcolor	= getDolGlobalString('INFRASPLUS_PDF_FACT_DATEDUE_COLOR', '0,0,0');
		$dateduetxtcolor	= explode(',', $dateduetxtcolor);
		$w					= $dimCadres['S'];
		$posx				= $formatpage['mgauche'];
		$posy				= $dimCadres['yS'];
		$align				= 'L';
		$pdf->SetTextColor((int) $frmeTxtColor[0], (int) $frmeTxtColor[1], (int) $frmeTxtColor[2]);
		$pdf->SetFont('', 'B', $default_font_size * $title_size / 2);
		$refDoc				= !empty($ref_from_cust) ? $object->ref_client : $object->ref;
		$refCli				= !empty($ref_from_cust) ? '' : ($object->element == 'shipping' ? $object->ref_customer : $object->ref_client);
		$txtref				= pdf_interne_refInvoice($pdf, $object, $outputlangs);
		$pdf->MultiCell($w, $tab_hl, $txtref, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
		$posy				= $pdf->getY();
		$pdf->SetTextColor((int) $frmeTxtColor[0], (int) $frmeTxtColor[1], (int) $frmeTxtColor[2]);
		$pdf->SetFont('', ($datesbold ? 'B' : ''), $default_font_size - 1);
		if ($object->element == 'societe') {
			$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date(dol_now(), 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
			$txtdt	= $outputlangs->transnoentities('Period').' : '.dol_print_date($object->context['account_statut']['date_start'], 'day', false, $outputlangs, true).' - '.dol_print_date($object->context['account_statut']['date_end'], 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
		} elseif ($object->element == 'propal') {
			$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
			if (empty($dates_br)) {
				if (!empty($object->fin_validite)) {
					$txtdt	.= ' / '.$outputlangs->transnoentities('DateEndPropal').' : '.dol_print_date($object->fin_validite, 'day', false, $outputlangs, true);
				}
			}
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
			if (!empty($dates_br)) {
				if (!empty($object->fin_validite)) {
					$txtdt	= '';
					$txtdt	= $outputlangs->transnoentities('DateEndPropal').' : '.dol_print_date($object->fin_validite, 'day', false, $outputlangs, true);
					$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
					$posy	+= $tab_hl - 0.5;
				}
			}
		} elseif ($object->element == 'commande') {
			$txtdt			= $outputlangs->transnoentities('PDFInfraSPlusOrderDate').' : '.dol_print_date($object->date_commande, 'day', false, $outputlangs, true);
			$date_livraison	= $object->delivery_date;
			if (empty($dates_br)) {
				if (!empty($date_livraison)) {
					$txtdt	.= ' / '.$outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($date_livraison, 'day', false, $outputlangs, true);
				}
			}
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
			if (!empty($dates_br)) {
				if (!empty($date_livraison)) {
					$txtdt	= '';
					$txtdt	= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($date_livraison, 'day', false, $outputlangs, true);
					$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
					$posy	+= $tab_hl - 0.5;
				}
			}
		} elseif ($object->element == 'facture') {
			$txtdt	= $outputlangs->transnoentities('DateInvoice').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
			if (empty($dates_br) && $object->type != 2) {
				$txtdt	.= ' / '.$outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_lim_reglement, 'day', false, $outputlangs, true);
			}
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
			if (!empty($dates_br) && $object->type != 2) {
				$txtdt	= '';
				$pdf->SetTextColor((int) $dateduetxtcolor[0], (int) $dateduetxtcolor[1], (int) $dateduetxtcolor[2]);
				$txtdt	= $outputlangs->transnoentities('DateDue').' : '.dol_print_date($object->date_lim_reglement, 'day', false, $outputlangs, true);
				$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$posy	+= $tab_hl - 0.5;
				$pdf->SetTextColor((int) $frmeTxtColor[0], (int) $frmeTxtColor[1], (int) $frmeTxtColor[2]);
				$posy	+= $tab_hl - 0.5;
			}
		} elseif ($object->element == 'contrat') {
			$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->date_contrat, 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
		} elseif ($object->element == 'shipping' || $object->element == 'reception') {
			if (!empty($object->date_delivery)) {
				$posy	+= $tab_hl;
				$txtdt	= $outputlangs->transnoentities('DateDeliveryPlanned').' : '.dol_print_date($object->date_delivery, 'dayhour', false, $outputlangs, true);
				$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$posy	+= $tab_hl - 0.5;
			}
		} elseif ($object->element == 'fichinter') {
			$txtdt	= $outputlangs->transnoentities('Date').' : '.dol_print_date($object->datec, 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
		} elseif ($object->element == 'order_supplier' && !empty($cf_show_creation_date) && !empty($object->date)) {
			$txtdt	= $outputlangs->transnoentities('PDFInfraSPlusCFDtCrea').' : '.dol_print_date($object->date, 'day', false, $outputlangs, true);
			$pdf->MultiCell($w, $tab_hl, $txtdt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
		}
		$pdf->SetFont('', '', $default_font_size - 1);
		if ($object->element == 'societe') {
			if ($object->context['account_statut']['export_type'] == 'Customer') {
				$thirdparty_language_key	= 'CustomerCode';
				$thirdparty_code			= $object->code_client;
			} elseif ($object->context['account_statut']['export_type'] == 'Supplier') {
				$thirdparty_language_key	= 'SupplierCode';
				$thirdparty_code			= $object->code_fournisseur;
			}
			$txtcc	= $outputlangs->transnoentities($thirdparty_language_key).' : '.$outputlangs->convToOutputCharset($thirdparty_code);
			$pdf->MultiCell($w, $tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl - 0.5;
		}
		if (!empty($refCli)) {
			$txtcc	= $outputlangs->transnoentities('RefCustomer').' : '.$outputlangs->convToOutputCharset($refCli);
			$pdf->MultiCell($w, $tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl * (($pdf->GetY() - $posy - 0.5) > $tab_hl ? 2 : 1);
		}
		if (!empty($object->ref_supplier)) {
			$txtcc	= $outputlangs->transnoentities('RefSupplier').' : '.$outputlangs->convToOutputCharset($object->ref_supplier);
			$pdf->MultiCell($w, $tab_hl, $txtcc, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= $tab_hl * (($pdf->GetY() - $posy - 0.5) > $tab_hl ? 2 : 1);
		}
		if (!empty($show_num_cli) && !empty($num_cli_frm) && $object->thirdparty->code_client) {
			$txtNumCli	= $outputlangs->transnoentities('CustomerCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_client);
			$pdf->MultiCell($w, $tab_hl, $txtNumCli, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy		+= $tab_hl - 0.5;
		}
		if (!empty($show_code_cli_compt) && !empty($code_cli_compt_frm) && $object->thirdparty->code_compta) {
			$txtCodeCliCompt	= $outputlangs->transnoentities('CustomerAccountancyCode').' : '.$outputlangs->convToOutputCharset($object->thirdparty->code_compta);
			$pdf->MultiCell($w, $tab_hl, $txtCodeCliCompt, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$posy				+= $tab_hl - 0.5;
		}
		if (!empty($add_creator_in_header)) {
			$usertmp	= pdf_InfraSPlus_creator($object, $outputlangs);
			if (!empty($usertmp)) {
				$pdf->MultiCell($w, $tab_hl, $outputlangs->transnoentities('PDFInfraSPlusRedac').' : '.$usertmp, '', $align, 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$posy	+= $tab_hl - 0.5;
			}
		}
		// Show list of linked objects
		if ($object->element != 'societe') {
			$posy	= pdf_InfraSPlus_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, $tab_hl, $align);
		}
		$posyendsender	= $pdf->getY();
		//Show Recipient
		$posy			= $dimCadres['yR'];
		if (empty($ticket)) {
			$pdf->SetTextColor((int) $frmrTxtColor[0], (int) $frmrTxtColor[1], (int) $frmrTxtColor[2]);
		}
		// Show recipient name
		$pdf->SetFont('', 'B', $default_font_size - ($ticket ? 2 : 0));
		$pdf->MultiCell($dimCadres['R'] - 4, $tab_hl, ($ticket ? $outputlangs->transnoentities('BillTo').' : ' : '').$addresses['carac_client_name'], '', 'L', 0, 1, $dimCadres['xR'] + 2, $posy + 1, true, 0, 0, false, 0, 'M', false);
		$posy								= $pdf->getY();
		// Show recipient information
		$pdf->SetFont('', '', $default_font_size - ($ticket ? 3 : 1));
		$pdf->MultiCell($dimCadres['R'] - 4, $tab_hl, $addresses['carac_client'], '', 'L', 0, 1, $dimCadres['xR'] + 2, $posy, true, 0, 0, false, 0, 'M', false);
		$posyendrecipient					= $pdf->getY();
		$hauteurscadres						= array('sender' => $posyendsender - $dimCadres['yS'] + 1, 'recipient' => $posyendrecipient - $dimCadres['yR'] + 1);
		return $hauteurscadres;
	}

		/**
		*	Set invoice reference.
		*
		*	@param		TCPDF		$pdf			Object PDF
		*	@param		object		$object		Object to show
		*	@param		Translate	$outputlangs	Object lang for output
		*	@return		string						Reference to show
		**/
		function pdf_interne_refInvoice(&$pdf, $object, $outputlangs) {

			global $db;

			if ($object->element == 'societe') {
				$txtref	= $outputlangs->transnoentities($object->context['account_statut']['export_type']).' : '.$outputlangs->convToOutputCharset($object->name);
			} else {
				$txtref	= $outputlangs->convToOutputCharset($object->ref);
				if ($object->statut == 0) {
					$pdf->SetTextColor(128, 0, 0);
					$txtref .= ' - '.$outputlangs->transnoentities('NotValidated');
				}
				if ($object->element == 'facture') {
					$objidnext	= $object->getIdReplacingInvoice('validated');
					if ($object->type == 0 && $objidnext) {
						$orep	= new Facture($db);
						$orep->fetch($objidnext);
						$txtref	.= ' / '.$outputlangs->transnoentities('ReplacementByInvoice').' : '.$outputlangs->convToOutputCharset($orep->ref);
					}
					if ($object->type == 1) {
						$orep	= new Facture($db);
						$orep->fetch($object->fk_facture_source);
						$txtref	.= ' / '.$outputlangs->transnoentities('ReplacementInvoice').' : '.$outputlangs->convToOutputCharset($orep->ref);
					}
					if ($object->type == 2 && !empty($object->fk_facture_source)) {
						$orep	= new Facture($db);
						$orep->fetch($object->fk_facture_source);
						$txtref	.= ' / '.$outputlangs->transnoentities('CorrectionInvoice').' : '.$outputlangs->convToOutputCharset($orep->ref);
					}
				}
			}
			return $txtref;
		}
