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
	*	\file		./infraspackplus/core/lib/infraspackplus.pdf.lib.php
	*	\ingroup	InfraS
	*	\brief		Set of functions used for InfraS PDF generation
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbank.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	if (isModEnabled('ouvrage')) {
		dol_include_once('/ouvrage/class/ouvrage.class.php');
		dol_include_once('/ouvrage/core/modules/modouvrage.class.php');
	}
	if (isModEnabled('subtotal')) {
		dol_include_once('/subtotal/core/modules/modSubtotal.class.php');
		dol_include_once('/subtotal/class/subtotal.class.php');
	}
	if (isModEnabled('milestone')) {
		dol_include_once('/milestone/core/modules/modMilestone.class.php');
	}
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	// For retrocompatibility Dolibarr < 21.0
	if (floatval(DOL_VERSION) < 21.0 && (!function_exists('getDolGlobalFloat') || !function_exists('getDolGlobalBool'))) {
		dol_include_once('/infraspackplus/backport/v21/core/lib/functions.lib.php');
	}

	/**
	*	Return array with format properties
	*
	*	@param	object		$template	Object we work on
	*	@return	void
	**/
	function pdf_InfraSPlus_getValues(&$template)
	{
		global $langs, $mysoc;

		$template->update_main_doc_field	= 1;	// Save the name of generated file as the main doc when generating a doc with this template
		$template->emetteur					= $mysoc;
		if (empty($template->emetteur->country_code)) {
			$template->emetteur->country_code	= substr($langs->defaultlang, -2);
		}
		$template->atleastonediscount		= 0;
		$template->tva						= array();
		$template->tva_array				= array();
		$template->localtax1				= array();
		$template->localtax2				= array();
		$template->credit_note				= getDolGlobalInt('INVOICE_POSITIVE_CREDIT_NOTE', 0);
		$template->atleastoneratenotnull	= 0;
		$template->situationinvoice			= False;
		$template->type						= 'pdf';
		$template->multilangs				= getDolGlobalInt('MAIN_MULTILANGS', 0);
		$template->multilangsBis			= getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE', '');
		$template->use_fpdf					= getDolGlobalInt('MAIN_USE_FPDF', 0);
		$template->main_umask				= getDolGlobalString('MAIN_UMASK', '0755');
		$formatarray						= pdf_InfraSPlus_getFormat();
		$template->page_largeur				= $formatarray['width'];
		$template->page_hauteur				= $formatarray['height'];
		$template->format					= array($template->page_largeur, $template->page_hauteur);
		$template->marge_gauche				= getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10) >= 4 ? getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10) : 10;
		$template->marge_haute				= getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10) >= 4 ? getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10) : 10;
		$template->marge_droite				= getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10) >= 4 ? getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10) : 10;
		$template->marge_basse				= getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10) >= 4 ? getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10) : 10;
		$template->formatpage				= array('largeur'	=> $template->page_largeur,	'hauteur'	=> $template->page_hauteur,	'mgauche'	=> $template->marge_gauche,
													'mdroite'	=> $template->marge_droite,	'mhaute'	=> $template->marge_haute,	'mbasse'	=> $template->marge_basse);
		$template->use_iso_location			= getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION', 0);
		$template->dash_between_line		= getDolGlobalInt('MAIN_PDF_DASH_BETWEEN_LINES', 0);
		$template->product_use_unit			= getDolGlobalString('PRODUCT_USE_UNITS', '');
		$template->hide_vat_ifnull			= getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL', 0);
		$template->vat_label_code_or_rate	= getDolGlobalString('PDF_VAT_LABEL_IS_CODE_OR_RATE', '');
		$template->no_payment_details		= getDolGlobalInt('INVOICE_NO_PAYMENT_DETAILS', 0);
		$template->hide_pay_term_cond		= getDolGlobalInt('PROPALE_PDF_HIDE_PAYMENTTERMCOND', 0);
		$template->hide_pay_term_mode		= getDolGlobalInt('PROPALE_PDF_HIDE_PAYMENTTERMMOD', 0);
		$template->chq_num					= getDolGlobalInt('FACTURE_CHQ_NUMBER', 0);
		$template->diffsize_title			= getDolGlobalInt('PDF_DIFFSIZE_TITLE', 0);
		$template->hidechq_address			= getDolGlobalInt('MAIN_PDF_HIDE_CHQ_ADDRESS', 0);
		$template->rib_num					= getDolGlobalInt('FACTURE_RIB_NUMBER', 0);
		$template->text_TVA_auto			= getDolGlobalInt('INFRASPLUS_PDF_FREETEXT_TVA_AUTO', 0);
		$template->multi_files				= getDolGlobalInt('INFRASPLUS_PDF_MULTI_FILES', 0);
		$template->font						= getDolGlobalString('INFRASPLUS_PDF_FONT', 'centurygothic');
		$template->headertxtcolor			= getDolGlobalString('INFRASPLUS_PDF_HEADER_TEXT_COLOR', '0,0,0');
		$template->headertxtcolor			= explode(',', $template->headertxtcolor);
		$template->bodytxtcolor				= getDolGlobalString('INFRASPLUS_PDF_BODY_TEXT_COLOR', '0,0,0');
		$template->bodytxtcolor				= explode(',', $template->bodytxtcolor);
		$template->datesbold				= getDolGlobalInt('INFRASPLUS_PDF_DATES_BOLD', 0);
		$template->ref_from_cust			= getDolGlobalInt('INFRASPLUS_PDF_REFD_FROM_CUSTOMER', 0);
		$template->first_page_empty			= getDolGlobalInt('INFRASPLUS_PDF_FIRST_PAGE_EMPTY', 0);
		$template->small_head2				= getDolGlobalInt('INFRASPLUS_PDF_SMALL_HEAD_2', 0);
		$template->title_size				= getDolGlobalFloat('INFRASPLUS_PDF_TITLE_SIZE', 2);
		$template->height_header_sep		= getDolGlobalInt('INFRASPLUS_PDF_HEIGHT_HEAD_SEP', 60);
		$template->left_recep_corner		= getDolGlobalInt('INFRASPLUS_PDF_LEFT_RECEP_CORNER', 92);
		$template->top_recep_corner			= getDolGlobalInt('INFRASPLUS_PDF_TOP_RECEP_CORNER', 40);
		$template->height_top_table			= getDolGlobalFloat('INFRASPLUS_PDF_HEIGHT_TOP_TABLE', 4);
		$template->hide_top_table			= getDolGlobalInt('INFRASPLUS_PDF_HIDE_TOP_TABLE', 0);
		$template->Rounded_rect				= getDolGlobalFloat('INFRASPLUS_PDF_ROUNDED_REC', 0);
		$template->bg_color					= getDolGlobalString('INFRASPLUS_PDF_BACKGROUND_COLOR', '109,70,140');
		$template->txtcolor					= explode(',', pdf_InfraSPlus_txt_color($template->bg_color));
		$template->bg_color					= explode(',', $template->bg_color);
		$template->title_bg					= getDolGlobalInt('INFRASPLUS_PDF_TITLE_BG', 0);
		$template->header_after_addr		= getDolGlobalInt('INFRASPLUS_PDF_HEADER_AFTER_ADDR', 0);
		$template->space_headerafter		= getDolGlobalInt('INFRASPLUS_PDF_SPACE_HEADERAFTER', 0);
		$template->header_align_left		= getDolGlobalInt('INFRASPLUS_PDF_HEADER_ALIGN_LEFT', 0);
		$template->dates_br					= getDolGlobalInt('INFRASPLUS_PDF_DATES_BR', 0);
		$template->show_emet_details		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_EMET_DETAILS', 0);
		$template->show_recep_details		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_RECEP_DETAILS', 0);
		$template->show_num_cli				= getDolGlobalInt('INFRASPLUS_PDF_SHOW_NUM_CLI', 0);
		$template->num_cli_frm				= getDolGlobalInt('INFRASPLUS_PDF_NUM_CLI_FRM', 0);
		$template->show_code_cli_compt		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT', 0);
		$template->code_cli_compt_frm		= getDolGlobalInt('INFRASPLUS_PDF_CODE_CLI_COMPT_FRM', 0);
		$template->add_creator_in_header	= getDolGlobalInt('INFRASPLUS_PDF_CREATOR_IN_HEADER', 0);
		$template->fold_mark				= getDolGlobalInt('INFRASPLUS_PDF_FOLD_MARK', 0);
		$template->paid_watermark			= getDolGlobalString('INFRASPLUS_PDF_FACTURE_PAID_WATERMARK', '');
		$template->hide_info_cur			= getDolGlobalInt('INFRASPLUS_PDF_HIDE_INFO_CUR', 0);
		$template->tblLineW					= getDolGlobalFloat('INFRASPLUS_PDF_TBL_LINE_WIDTH', 0.2);
		$template->tblLineDash				= getDolGlobalInt('INFRASPLUS_PDF_TBL_LINE_DASH', 0);
		$template->tblLineColor				= getDolGlobalString('INFRASPLUS_PDF_TBL_LINE_COLOR', '128,128,128');
		$template->showtblline				= str_replace(' ', '', $template->tblLineColor) == '255,255,255' ? 0 : 1;
		$template->tblLineColor				= explode(',', $template->tblLineColor);
		$template->verLineColor				= getDolGlobalString('INFRASPLUS_PDF_VER_LINE_COLOR', '128,128,128');
		$template->showverline				= str_replace(' ', '', $template->verLineColor) == '255,255,255' ? 0 : 1;
		$template->verLineColor				= explode(',', $template->verLineColor);
		$template->horLineColor				= getDolGlobalString('INFRASPLUS_PDF_HOR_LINE_COLOR', '128,128,128');
		$template->horLineColor				= explode(',', $template->horLineColor);
		$template->hBC						= getDolGlobalInt('INFRASPLUS_PDF_HT_BC', 12);
		$template->wBC						= getDolGlobalInt('INFRASPLUS_PDF_LARG_BC', 35);
		$template->dimC2D					= getDolGlobalInt('INFRASPLUS_PDF_DIM_C2D', 15);
		$template->subti_with_subto			= getDolGlobalInt('INFRASPLUS_PDF_SUBTI_WITH_SUBTO', 0);
		$template->lineSep_hight			= getDolGlobalInt('INFRASPLUS_PDF_LINESEP_HIGHT', 4);
		$template->show_ref_col				= getDolGlobalInt('INFRASPLUS_PDF_WITH_REF_COLUMN', 0);
		$template->show_num_col				= getDolGlobalInt('INFRASPLUS_PDF_WITH_NUM_COLUMN', 0);
		$template->force_align_left_ref		= getDolGlobalString('INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF', 'L');
		$template->picture_in_ref			= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_IN_REF', 0);
		$template->picture_replace_ref		= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_REPLACE_REF', 0);
		$template->force_align_left_unit	= getDolGlobalString('INFRASPLUS_PDF_FORCE_ALIGN_LEFT_UNIT', 'L');
		$template->desc_full_line			= getDolGlobalInt('INFRASPLUS_PDF_DESC_FULL_LINE', 0);
		$template->show_desc				= getDolGlobalInt('INFRASPLUS_PDF_SHOW_DESC_DEV', 0);
		$template->hidden_ouv				= getDolGlobalInt('INFRASPLUS_PDF_HIDDEN_OUV', 0);
		$template->only_one_desc			= getDolGlobalInt('INFRASPLUS_PDF_ONLY_ONE_DESC', 0);
		$template->hide_qty					= getDolGlobalInt('INFRASPLUS_PDF_HIDE_QTY', 0);
		$template->hide_up					= getDolGlobalInt('INFRASPLUS_PDF_HIDE_UP', 0);
		$template->show_up_discounted		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_UP_DISCOUNTED', 0);
		$template->discount_auto			= getDolGlobalInt('INFRASPLUS_PDF_DISCOUNT_AUTO', 0);
		$template->show_ttc_col				= getDolGlobalInt('INFRASPLUS_PDF_WITH_TTC_COLUMN', 0);
		$template->hide_vat_col				= getDolGlobalInt('INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', 0);
		$template->show_ttc_vat_tot			= getDolGlobalInt('INFRASPLUS_PDF_TTC_WITH_VAT_TOT', 0);
		if (!empty($template->show_ttc_vat_tot)) {
			$template->hide_vat	= 1;
		}
		$template->only_ttc	= getDolGlobalInt('INFRASPLUS_PDF_ONLY_TTC', 0);
		if (!empty($template->only_ttc)) {
			$template->hide_vat_col	= 1;
			$template->hide_vat		= 1;
		}
		$template->only_ht	= getDolGlobalInt('INFRASPLUS_PDF_ONLY_HT', 0);
		if (!empty($template->only_ht)) {
			$template->hide_vat_col	= 1;
			$template->hide_vat		= 0;
		}
		$template->larg_ref					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_REF', 28);
		$template->larg_qty					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_QTY', 10);
		$template->larg_unit				= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_UNIT', 10);
		$template->larg_up					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_UP', 22);
		$template->larg_tva					= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_TVA', 14);
		$template->larg_discount			= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_DISC', 14);
		$template->larg_updisc				= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_UPD', 22);
		$template->larg_progress			= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_PROGRESS', 10);
		$template->larg_totalht				= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_TOTAL', 24);
		$template->larg_totalttc			= getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_TOTAL_TTC', 24);
		$template->num_ref					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_REF', 1);
		$template->num_desc					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_DESC', 2);
		$template->num_qty					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_QTY', 3);
		$template->num_unit					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_UNIT', 4);
		$template->num_up					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_UP', 5);
		$template->num_tva					= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_TVA', 6);
		$template->num_discount				= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_DISC', 7);
		$template->num_updisc				= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_UPD', 8);
		$template->num_progress				= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_PROGRESS', 9);
		$template->num_totalht				= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_TOTAL', 10);
		$template->num_totalttc				= getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_TOTAL_TTC', 11);
		$template->ht_space_info			= getDolGlobalInt('INFRASPLUS_PDF_SPACE_INFO', 5);
		$template->ht_space_tot				= getDolGlobalInt('INFRASPLUS_PDF_SPACE_TOT', 1);
		$template->show_paymenttermcond_2l	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_PAYMENTTERMCOND_2L', 0);
		$template->show_qty_prod_tot		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_QTY_PROD_TOT', 0);
		$template->efPaySpec				= getDolGlobalString('INFRASPLUS_PDF_EXF_PAY_SPEC', '');
		$template->efDeposit				= getDolGlobalString('INFRASPLUS_PDF_EXF_DEPOSIT', '');
		$template->IBAN_with_CB				= getDolGlobalInt('INFRASPLUS_PDF_IBAN_WITH_CB', 0);
		$template->IBAN_All					= getDolGlobalInt('INFRASPLUS_PDF_IBAN_ALL', 0);
		$template->bank_only_number			= getDolGlobalInt('INFRASPLUS_PDF_BANK_ONLY_NUMBER', 0);
		$template->show_outstandings		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_OUTSTDBILL', 0);
		$template->invert_bg_ht_ttc			= getDolGlobalInt('INFRASPLUS_PDF_INVERT_BG_HT_TTC', 0);
		$template->show_disc_tot			= getDolGlobalInt('INFRASPLUS_PDF_SHOW_DISCOUNT_TOT', 0);
		$template->show_disc_ttc			= getDolGlobalInt('INFRASPLUS_PDF_SHOW_DISCOUNT_TTC', 0);
		$template->show_tot_local_cur		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_TOTAL_LOCAL_CUR', 0);
		$template->show_tot_Cur_Symb		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_CUR_SYMB_ON_TABLEAU_TOT', 0);
		$template->number_words				= getDolGlobalInt('INFRASPLUS_PDF_NUMBER_WORDS', 0);
		$template->listPrefixEcotax			= getDolGlobalString('OUVRAGE_LIST_PREFIX_ECOTAX', '');
		$template->listPrefixEcotax			= !empty($template->listPrefixEcotax) ? explode(',', $template->listPrefixEcotax) : '';
		$template->exfEcoTax				= getDolGlobalString('INFRASPLUS_PDF_EXF_ECOTAX', '');
		$template->ht_signarea				= getDolGlobalInt('INFRASPLUS_PDF_HT_SIGN_AREA', 24);
		$template->signLineW				= getDolGlobalFloat('INFRASPLUS_PDF_SIGN_LINE_WIDTH', 0.2);
		$template->signLineDash				= getDolGlobalInt('INFRASPLUS_PDF_SIGN_LINE_DASH', 0);
		$template->signLineColor			= getDolGlobalString('INFRASPLUS_PDF_SIGN_LINE_COLOR', '128,128,128');
		$template->signLineColor			= explode(',', $template->signLineColor);
		$template->show_2sign_area			= isModEnabled('customlink') ? getDolGlobalInt('INFRASPLUS_PDF_COMMANDE_OF_SHOW_2_SIGNATURES', 0) : 0;
		$template->e_signing				= getDolGlobalInt('INFRASPLUS_PDF_SHOW_E_SIGNING', 0);
		$template->free_text_end			= getDolGlobalInt('INFRASPLUS_PDF_FREETEXTEND', 0);
		$template->type_foot				= getDolGlobalString('INFRASPLUS_PDF_TYPE_FOOT', '0000');
		$template->hidepagenum				= getDolGlobalInt('INFRASPLUS_PDF_HIDE_PAGE_NUM', 0);
		$template->wpicturefoot				= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_FOOT_WIDTH', 188);
		$template->hpicturefoot				= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_FOOT_HEIGHT', 12);
		$template->maxsizeimgfoot			= array('largeur'=>$template->wpicturefoot, 'hauteur'=>$template->hpicturefoot);
		$template->only_one_picture			= getDolGlobalInt('INFRASPLUS_PDF_ONLY_ONE_PICTURE', 0);
		$template->picture_after			= empty($template->picture_in_ref) ? getDolGlobalInt('INFRASPLUS_PDF_PICTURE_AFTER', 0) : 0;
		$template->picture_under			= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_UNDER', 0);
		$template->picture_padding			= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_PADDING', 0);
		$template->linkpictureurl			= getDolGlobalString('INFRASPLUS_PDF_LINK_PICTURE_URL', '');
		$template->old_path_photo			= getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO', 0);
		$template->cat_hq_image				= getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES', 0);
		$template->alpha					= 0.2;
		$template->exftxtcolor				= getDolGlobalString('INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR', '0,0,0');
		$template->exftxtcolor				= explode(',', $template->exftxtcolor);
		$template->exfltxtcolor				= getDolGlobalString('INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR', '0,0,0');
		$template->exfltxtcolor				= explode(',', $template->exfltxtcolor);
	}

	/**
	*	Return a PDF instance object. We create a FPDI instance that instantiate TCPDF.
	*
	*	@param	array		$format			Array(width,height). Keep empty to use default setup.
	*	@param	string		$metric			Unit of format ('mm')
	*	@param	string		$pagetype		'P' or 'l'
	*	@param	boolean		$onlyConf		true, only for defining constants || false, to also create the PDF object
	*	@return	TCPDF|int					PDF object or 1 if we just need to define constants
	**/
	function pdf_InfraSPlus_getInstance($format = array(), $metric = 'mm', $pagetype = 'P', $onlyConf = false)
	{
		global $conf;

		if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {	// Define constant for TCPDF
			define('K_TCPDF_EXTERNAL_CONFIG', 1); // this avoid using tcpdf_config file
			define('K_PATH_CACHE', DOL_DATA_ROOT.'/admin/temp/');
			define('K_PATH_URL_CACHE', DOL_DATA_ROOT.'/admin/temp/');
			dol_mkdir(K_PATH_CACHE);
			define('K_PATH_FONTS', DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/fonts/');
			define('K_BLANK_IMAGE', '_blank.png');
			define('PDF_PAGE_FORMAT', 'A4');
			define('PDF_PAGE_ORIENTATION', $pagetype);
			define('PDF_CREATOR', 'TCPDF');
			define('PDF_AUTHOR', 'TCPDF');
			define('PDF_HEADER_TITLE', 'TCPDF Example');
			define('PDF_HEADER_STRING', 'by Dolibarr ERP CRM - powered by InfraS');
			define('PDF_UNIT', $metric);
			define('PDF_MARGIN_HEADER', 5);
			define('PDF_MARGIN_FOOTER', 10);
			define('PDF_MARGIN_TOP', 10);
			define('PDF_MARGIN_BOTTOM', 10);
			define('PDF_MARGIN_LEFT', 10);
			define('PDF_MARGIN_RIGHT', 10);
			define('PDF_FONT_NAME_MAIN', 'helvetica');
			define('PDF_FONT_SIZE_MAIN', 10);
			define('PDF_FONT_NAME_DATA', 'helvetica');
			define('PDF_FONT_SIZE_DATA', 8);
			define('PDF_FONT_MONOSPACED', 'courier');
			define('PDF_IMAGE_SCALE_RATIO', 1.25);
			define('HEAD_MAGNIFICATION', 1.1);
			define('K_CELL_HEIGHT_RATIO', 1.25);
			define('K_TITLE_MAGNIFICATION', 1.3);
			define('K_SMALL_RATIO', 2 / 3);
			define('K_THAI_TOPCHARS', true);
			define('K_TCPDF_CALLS_IN_HTML', true);
			if (getDolGlobalString('TCPDF_THROW_ERRORS_INSTEAD_OF_DIE', '')) {
				define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
			} else {
				define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
			}
		}
		require_once TCPDF_PATH.'tcpdf.php';	// Load TCPDF
		// We need to instantiate tcpdi object (instead of tcpdf) to use merging features. But we can disable it (this will break all merge features).
		if (!getDolGlobalString('MAIN_DISABLE_TCPDI', '')) {
			require_once TCPDI_PATH.'tcpdi.php';
		}
		// Load InfraS subclasses that fix TCPDF ColorFlag bug (text color lost on page breaks)
		dol_include_once('/infraspackplus/class/tcpdf_infrasplus.class.php');
		if (!empty($onlyConf)) {
			return 1;
		}
		$pdfa	= getDolGlobalString('PDF_USE_A', false);	// PDF/A-1 ou PDF/A-3
		if (class_exists('TCPDI_InfraS')) {
			$pdf	= new TCPDI_InfraS($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
		} elseif (class_exists('TCPDF_InfraS')) {
			$pdf	= new TCPDF_InfraS($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
		} elseif (class_exists('TCPDI')) {
			$pdf	= new TCPDI($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
		} else {
			$pdf	= new TCPDF($pagetype, $metric, $format, true, 'UTF-8', false, $pdfa);
		}
		// Protection and encryption of pdf
		if (getDolGlobalString('PDF_SECURITY_ENCRYPTION', '')) {
			/* Permission supported by TCPDF
			- print : Print the document;
			- modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';
			- copy : Copy or otherwise extract text and graphics from the document;
			- annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);
			- fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;
			- extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);
			- assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;
			- print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.
			- owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.
			*/

			// For TCPDF, we specify permission we want to block
			$pdfrights		= (getDolGlobalString('PDF_SECURITY_ENCRYPTION_RIGHTS', '') ? json_decode(getDolGlobalString('PDF_SECURITY_ENCRYPTION_RIGHTS', ''), true) : array('modify', 'copy')); // Json format in llx_const
			// Password for the end user
			$pdfuserpass	= getDolGlobalString('PDF_SECURITY_ENCRYPTION_USERPASS', '');
			// Password of the owner, created randomly if not defined
			$pdfownerpass	= getDolGlobalString('PDF_SECURITY_ENCRYPTION_OWNERPASS', null);
			// For encryption strength: 0 = RC4 40 bit; 1 = RC4 128 bit; 2 = AES 128 bit; 3 = AES 256 bit
			$encstrength	= getDolGlobalInt('PDF_SECURITY_ENCRYPTION_STRENGTH', 0);
			// Array of recipients containing public-key certificates ('c') and permissions ('p').
			// For example: array(array('c' => 'file://../examples/data/cert/tcpdf.crt', 'p' => array('print')))
			$pubkeys		= (getDolGlobalString('PDF_SECURITY_ENCRYPTION_PUBKEYS', '') ? json_decode(getDolGlobalString('PDF_SECURITY_ENCRYPTION_PUBKEYS', ''), true) : null); // Json format in llx_const
			$pdf->SetProtection($pdfrights, $pdfuserpass, $pdfownerpass, $encstrength, $pubkeys);
		}
		return $pdf;
	}

	/**
	*	Return array with format properties
	*
	*	@param	string		$format			specific format to use
	*	@param	Translate	$outputlangs	Output lang to use to autodetect output format if setup not done
	*	@param	string		$mode			'setup' = Use setup, 'auto' = Force autodetection whatever is setup (this onkly if local $format is not used)
	*	@return	array						Array('width'=>w,'height'=>h,'unit'=>u);
	**/
	function pdf_InfraSPlus_getFormat($format = '', $outputlangs = null, $mode = 'setup')
	{
		global $conf, $db, $langs;

		dol_syslog('pdf_InfraSPlus_getFormat Get paper format with mode = '.$mode.' MAIN_PDF_FORMAT = '.getDolGlobalString('MAIN_PDF_FORMAT', 'null').' outputlangs->defaultlang = '.(is_object($outputlangs) ? $outputlangs->defaultlang : 'null').' and langs->defaultlang = '.(is_object($langs) ? $langs->defaultlang : 'null'));
		// Default value if setup was not done and/or entry into c_paper_format not defined
		$width	= 210;
		$height	= 297;
		$unit	= 'mm';
		if (!empty($format)) {
			$pdfformat	= $format;
		} elseif ($mode == 'auto' || !getDolGlobalString('MAIN_PDF_FORMAT', '') || getDolGlobalString('MAIN_PDF_FORMAT', '') == 'auto') {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$pdfformat	= dol_getDefaultFormat($outputlangs);
		} else {
			$pdfformat	= getDolGlobalString('MAIN_PDF_FORMAT', '');
		}
		$sql	= 'SELECT code, label, width, height, unit FROM '.$db->prefix().'c_paper_format';
		$sql	.= ' WHERE code = "'.$db->escape($pdfformat).'"';
		$resql	= $db->query($sql);
		if (!empty($resql)) {
			$obj	= $db->fetch_object($resql);
			if (!empty($obj)) {
				$width	= (int) $obj->width;
				$height	= (int) $obj->height;
				$unit	= $obj->unit;
			}
		}
		$db->free($resql);
		return array('width' => $width, 'height' => $height, 'unit' => $unit);
	}

	/**
	*	Select text color from background values
	*
	*	@param	string		$bgcolor			RGB value for background color
	*	@return	string							'255' or '0' for white (255, 255 ,255) or black (0, 0, 0)
	**/
	function pdf_InfraSPlus_txt_color(&$bgcolor)
	{
		global $conf;

		$tmppart	= explode(',', $bgcolor);
		$tmpvalr	= (!empty($tmppart[0]) ? $tmppart[0] : 0) * 0.3;
		$tmpvalg	= (!empty($tmppart[1]) ? $tmppart[1] : 0) * 0.59;
		$tmpvalb	= (!empty($tmppart[2]) ? $tmppart[2] : 0) * 0.11;
		$tmpval		= $tmpvalr + $tmpvalg + $tmpvalb;
		if ($tmpval <= 128) {
			$colorauto	= '255, 255, 255';
		} else {
			$colorauto	= '0, 0, 0';
		}
		$textcolorauto	= getDolGlobalInt('INFRASPLUS_PDF_TEXT_COLOR_AUTO', 0);
		$colorman		= getDolGlobalString('INFRASPLUS_PDF_TEXT_COLOR', $colorauto);
		if (!empty($textcolorauto)) {
			return $colorauto;
		} else {
			return $colorman;
		}
	}

	/**
	*	Display a background as a watermark
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	array			$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int				$objEntity		Object entity
	*	@param	Translate		$outputlangs	Output lang to use to autodetect output format if setup not done
	*	@param	string			$useLogo		Use named logo as watermark
	*	@return	void
	**/
	function pdf_InfraSPlus_bg_watermark($pdf, $formatpage, $objEntity, $outputlangs = null, $useLogo = '')
	{
		global $conf;

		$image_watermark		= !empty($useLogo) ? $useLogo : getDolGlobalString('INFRASPLUS_PDF_IMAGE_WATERMARK', '');
		$test_watermark			= getDolGlobalString('INFRASPLUS_PDF_ENABLE_TEST_WATERMARK', '');
		$watermark_i_opacity	= getDolGlobalInt('INFRASPLUS_PDF_I_WATERMARK_OPACITY', 1);
		$logodir				= !empty($conf->mycompany->multidir_output[$objEntity]) ? $conf->mycompany->multidir_output[$objEntity] : $conf->mycompany->dir_output;
		$filigrane				= $logodir.'/logos/'.$image_watermark;
		if (!empty($image_watermark) && is_readable($filigrane)) {
			$imgsize	= array();
			$imgsize	= pdf_InfraSPlus_getSizeForImage($filigrane, $formatpage['largeur'], $formatpage['hauteur']);
			if (isset($imgsize['width']) && isset($imgsize['height'])) {
				$pdf->SetAlpha($watermark_i_opacity / 100);
				$bMargin			= $pdf->getBreakMargin();	// get the current page break margin
				$auto_page_break	= $pdf->getAutoPageBreak();	// get current auto-page-break mode
				$pdf->SetAutoPageBreak(false, 0);	// disable auto-page-break
				$posxpicture		= ($formatpage['largeur'] - $imgsize['width']) / 2;	// centre l'image dans la page
				$posypicture		= ($formatpage['hauteur'] - $imgsize['height']) / 2;	// centre l'image dans la page
				$pdf->Image($filigrane, $posxpicture, $posypicture, $imgsize['width'], $imgsize['height'], '', '', '', false, 300, '', false, false, 0);	// set bacground image
				$pdf->SetAutoPageBreak($auto_page_break, $bMargin);	// restore auto-page-break status
				$pdf->SetAlpha(1);	// restore full opacity before page mark so subsequent content inserted at intmrk is not affected by watermark alpha
				$pdf->setPageMark();	// set the starting point for the page content
			}
		}
		if (!empty($test_watermark) && !empty($outputlangs)) {
			$larg_util_cadre	= $formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']);
			$ht_util_cadre		= $formatpage['hauteur'] - ($formatpage['mhaute'] + $formatpage['mbasse']);
			pdf_InfraSPlus_watermark($pdf, $outputlangs, $test_watermark, $ht_util_cadre / 2, $larg_util_cadre, $ht_util_cadre, 'mm');
		}
	}

	/**
	*	Calcul for total discount
	*
	*	@param		object		$object			Object shown in PDF
	*	@param		int			$i				Row number
	*	@param		boolean		$only_ht		don't include taxes
	*	@param		array		$pricesObjProd	price datas from product (need if we use customer prices for product and automatic discount)
	*	@param		boolean		$multicurrency	use multicurrency values
	*	@return		string						Return the difference between standart price and discounted one
	**/
	function pdf_InfraSPlus_getTotRem($object, $i, $only_ht = 0, $pricesObjProd = array(), $multicurrency = 0)
	{
		global $conf;

		$isSitFac		= !empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 ? $object->lines[$i]->situation_percent / 100 : 1;	// use this to find the real unit price on situation invoice
		$TotBrutLine	= (!empty($multicurrency) ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice) * $object->lines[$i]->qty * $isSitFac;
		$TotBrutLine	= !empty($pricesObjProd['pu_ht']) ? $pricesObjProd['pu_ht'] * (!empty($multicurrency) ? $object->multicurrency_tx : 1) * $object->lines[$i]->qty : $TotBrutLine;
		if (empty($only_ht)) {
			$tvalignebrut		= $TotBrutLine * $object->lines[$i]->tva_tx / 100;
			$localtax1lignebrut	= $TotBrutLine * $object->lines[$i]->localtax1_tx / 100;
			$localtax2lignebrut	= $TotBrutLine * $object->lines[$i]->localtax2_tx / 100;
			return ($TotBrutLine + $tvalignebrut + $localtax1lignebrut + $localtax2lignebrut) - (!empty($multicurrency) ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc);
		} else {
			return $TotBrutLine - (!empty($multicurrency) ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht);
		}
	}

	/**
	*	Show top small header of page.
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	Translate		$outputlangs		Object lang for output
	*	@param	float			$posy				Pos y
	*	@param	float			$w					Width
	*	@param	string			$logo				Name of logo file
	*	@param	object			$emetteur
	*	@param	int				$posx				Pos x
	*	@param	int				$tab_hl
	*	@param	array			$headertxtcolor		Text color
	*	@param	int				$objEntity			Object entity
	*	@param	int				$forceWidth			we force logo width to de value of $w
	*	@param	int				$center				we center logo in $w
	*	@param	int				$left				logo to the left of $w
	*	@return	float								Return height of logo
	**/
	function pdf_InfraSPlus_logo($pdf, $outputlangs, $posy, $w, $logo, $emetteur, $posx, $tab_hl, $headertxtcolor, $objEntity, $forceWidth = 0, $center = 0, $left = 0)
	{
		global $conf;

		$default_font_size	= pdf_getPDFFontSize($outputlangs);
		$cat_hq_image		= getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES', 0);
		$noMyLogo			= getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO', 0);
		$useLargeLogo		= getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO', 0);
		$heightLogo			= 0;
		if (!empty($noMyLogo)) {
			return $heightLogo;
		}
		$logodir	= !empty($conf->mycompany->multidir_output[$objEntity])	? $conf->mycompany->multidir_output[$objEntity]		: $conf->mycompany->dir_output;
		if (!empty($logo)) {
			$logo	= $logodir.'/logos/'.$logo;
		} else {
			$logo	= empty($useLargeLogo) && empty($cat_hq_image)	? $logodir.'/logos/thumbs/'.$emetteur->logo_small	: $logodir.'/logos/'.$emetteur->logo;
		}
		if (!empty($logo)) {
			if (is_file($logo) && is_readable($logo)) {
				$heightLogo	= pdf_getHeightForLogo($logo);
				if (!empty($forceWidth) || !empty($center)) {
					$logosize	= pdf_InfraSPlus_getSizeForImage($logo, $w, $heightLogo, 1);
					$posxlogo	= !empty($center) ? $posx + (($w - $logosize['width']) / 2) : (!empty($left)  ? $posx + ($w - $logosize['width']) : $posx);	// positionne l'image dans la colonne => centre | gauche | $posx
					$pdf->Image($logo, $posxlogo, $posy, $logosize['width'], 0);	// height = 0 (auto)
					$heightLogo	= $logosize['height'];
				} else {
					$logosize	= pdf_InfraSPlus_getSizeForImage($logo, $w, $heightLogo, 0);
					$pdf->Image($logo, (!empty($left)  ? $posx + ($w - $logosize['width']) : $posx), $posy, 0, $heightLogo);	// width = 0 (auto)
				}
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($w, $tab_hl, $outputlangs->transnoentities('PDFInfraSPlusLogoFileNotFound', $logo), '', 'L', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell($w, $tab_hl, $outputlangs->transnoentities('ErrorGoToGlobalSetup'), '', 'L', 0, 1, $posx, $pdf->getY() + 1, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor($headertxtcolor[0], $headertxtcolor[1], $headertxtcolor[2]);
				$heightLogo	= $pdf->getY() + 1;
			}
		} else {
			$text		= $emetteur->name;
			$pdf->MultiCell($w, $tab_hl, $outputlangs->convToOutputCharset($text), '', 'L', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			$heightLogo = $tab_hl;
		}
		return $heightLogo;
	}

	/**
	*	Search Creator name
	*
	*	@param	object			$object			Object
	*	@param	Translate		$outputlangs	Output langs object
	*	@param	int				$returnObj		0 => return name found | 1 => return object user found
	*	@return	string							value found or empty
	**/
	function pdf_InfraSPlus_creator($object, $outputlangs, $returnObj = 0)
	{
		global $db;

		if (is_object($object->user_creation)) {
			return !empty($object->user_creation->id) ? (!empty($returnObj) ? $object->user_creation : $object->user_creation->getFullName($outputlangs)) : '';
		} else {
			$userstatic				= new User($db);
			if ($object->user_creation_id > 0 || $object->user_creation > 0) {
				$userstatic->fetch(!empty($object->user_creation_id) ? $object->user_creation_id : $object->user_creation);
			}
			if (!empty($userstatic->id)) {
				return !empty($returnObj) ? $userstatic : $userstatic->getFullName($outputlangs);
			} else {
				if ($object->user_author_id > 0 || $object->user_author > 0) {
					$userstatic->fetch(!empty($object->user_author_id) ? $object->user_author_id : $object->user_author);
				}
				if (!empty($userstatic->id)) {
					return !empty($returnObj) ? $userstatic : $userstatic->getFullName($outputlangs);
				} else {
					return '';
				}
			}
		}
	}

	/**
	*	Show linked objects for PDF generation
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	object			$object				Object shown in PDF
	*	@param	Translate		$outputlangs		Output langs object
	*	@param	int				$posx				Pos x
	*	@param	int				$posy				Pos y
	*	@param	int				$w					Width of cells. If 0, they extend up to the right margin of the page
	*	@param	int				$tab_hl				Cell minimum height. The cell extends automatically if needed.
	*	@param	string			$align				Align
	*	@param	int|string		$header_after_addr	Where it's displayed
	*	@return	float | string						The Y PDF position or the string to print
	**/
	function pdf_InfraSPlus_writeLinkedObjects(&$pdf, $object, $outputlangs, $posx, $posy, $w, $tab_hl, $align, $header_after_addr = 0)
	{
		global $conf;

		$nodatelinked	= getDolGlobalInt('INFRASPLUS_PDF_NO_DATE_LINKED', 0);
		$linkedobjects	= pdf_InfraSPlus_getLinkedObjects($object, $outputlangs);
		if (!empty($linkedobjects)) {
			$refstoshow	= '';
			foreach ($linkedobjects as $linkedobject) {
				$reftoshow	= $linkedobject['ref_title'].' : '.$linkedobject['ref_value'];
				if (empty($nodatelinked) && !empty($linkedobject['date_value'])) {
					$reftoshow	.= ' / '.$linkedobject['date_value'];
				}
				if (empty($header_after_addr)) {
					$posy2	= $pdf->getY();
					$pdf->MultiCell($w, $tab_hl, dol_trunc($reftoshow, 100), '', $align, 0, 1, $posx, ($posy2 > $posy ? $posy2 : $posy), true, 0, 0, false, 0, 'M', false);
				} else {
					$refstoshow	.= ($refstoshow ? ' / ' : '').$reftoshow;
				}
			}
		}
		return (empty($header_after_addr) ? $pdf->getY() : $refstoshow);
	}

	/**
	*	Return linked objects to use for document generation.
	*	Warning: To save space, it returns only one link per link type (all links are concated on same record string).
	*	It is used by pdf_InfraSPlus_writeLinkedObjects
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	Translate	$outputlangs	Output langs object
	*	@return	array						Linked objects
	**/
	function pdf_InfraSPlus_getLinkedObjects($object, $outputlangs)
	{
		global $conf, $db, $hookmanager;

		$propallinked		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_REF_PROPAL', 0);
		$orderlinked		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_REF_ORDER', 0);
		$refcustonorder		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_REF_CUST_ON_ORDER', 0);
		$shippinglinked		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_REF_SHIPPING', 0);
		$contractlinked		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_REF_CONTRACT', 0);
		$fichinterlinked	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_REF_FICHINTER', 0);
		$projectlinked		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_REF_PROJECT', 0);
		$projectdesc		= getDolGlobalInt('INFRASPLUS_PDF_SHOW_DESC_PROJECT', 0);
		$linkedobjects		= array();
		$object->fetchObjectLinked();
		foreach($object->linkedObjects as $objecttype => $objects) {
			if ($objecttype == 'facture') {
			// For invoice, we don't want to have a reference line on document. Image we are using recuring invoice, we will have a line longer than document width.
			} elseif (($object->element != 'propal' && $objecttype == 'propal' && $propallinked) || ($object->element != 'supplier_proposal' && $objecttype == 'supplier_proposal' && $propallinked)) {
				$outputlangs->load('propal');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefProposal');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('DatePropal');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date, 'day', '', $outputlangs);
				}
			} elseif (($object->element != 'commande' && $objecttype == 'commande' && $orderlinked) || ($object->element != 'order_supplier' && $objecttype == 'order_supplier' && $orderlinked)) {
				$outputlangs->load('orders');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefOrder');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref).(!empty($elementobject->ref_client) && !empty($refcustonorder) ? ' ('.$elementobject->ref_client.')' : '').(!empty($elementobject->ref_supplier) ? ' ('.$elementobject->ref_supplier.')' : '');
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('OrderDate');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date, 'day', '', $outputlangs);
				}
			}
			elseif ($object->element != 'contrat' && $objecttype == 'contrat' && $contractlinked) {
				$outputlangs->load('contracts');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefContract');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('DateContract');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date_contrat, 'day', '', $outputlangs);
				}
			}
			elseif ($object->element != 'fichinter' && $objecttype == 'fichinter' && $fichinterlinked) {
				$outputlangs->load('interventions');
				foreach($objects as $elementobject) {
					$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('PDFInfraSPlusRefInter');
					$linkedobjects[$objecttype]['ref_value']	= $outputlangs->transnoentities($elementobject->ref);
					$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('Date');
					$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->datec, 'day', '', $outputlangs);
				}
			}
			elseif ($object->element != 'shipping' && $objecttype == 'shipping' && $shippinglinked) {
				foreach($objects as $x => $elementobject) {
					$order	= null;
					if (empty($object->linkedObjects['commande']) && $object->element != 'commande') {	// There is not already a link to order and object is not the order, so we show also info with order
						$elementobject->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
						if (!empty($elementobject->linkedObjectsIds['commande'])) {
							include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
							$order	= new Commande($db);
							$ret	= $order->fetch(reset($elementobject->linkedObjectsIds['commande']));
							if ($ret < 1) {
								$order	= null;
							}
						}
					}
					$linkedobjects[$objecttype]['ref_value']	= '';
					if (! is_object($order)) {
						$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefSending');
						if (!empty($linkedobjects[$objecttype]['ref_value'])) {
							$linkedobjects[$objecttype]['ref_value']	.=' / ';
						}
						$linkedobjects[$objecttype]['ref_value']	.= $outputlangs->transnoentities($elementobject->ref);
						$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('DateDeliveryPlanned');
						$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date_delivery, 'day', '', $outputlangs);
					} else {
						$linkedobjects[$objecttype]['ref_title']	= $outputlangs->transnoentities('RefOrder').' / '.$outputlangs->transnoentities('RefSending');
						if (empty($linkedobjects[$objecttype]['ref_value'])) {
							$linkedobjects[$objecttype]['ref_value']	= $outputlangs->convToOutputCharset($order->ref) . ($order->ref_client ? ' ('.$order->ref_client.')' : '');
						}
						$linkedobjects[$objecttype]['ref_value']	.= ' / '.$outputlangs->transnoentities($elementobject->ref);
						$linkedobjects[$objecttype]['date_title']	= $outputlangs->transnoentities('DateDeliveryPlanned');
						$linkedobjects[$objecttype]['date_value']	= dol_print_date($elementobject->date_delivery, 'day', '', $outputlangs);
					}
				}
			}
		}
		if (!empty($projectlinked) && isModEnabled('projet') && !empty($object->fk_project)) {
			$proj									= new Project($db);
			$proj->fetch($object->fk_project);
			$linkedobjects['project']['ref_title']	= $outputlangs->transnoentities('RefProject');
			$linkedobjects['project']['ref_value']	= $proj->ref.($projectdesc ? ' - '.$proj->title : ''); // simplification => only the reference remains
		}
		// For add external linked objects
		if (is_object($hookmanager)) {
			$parameters	= array('linkedobjects' => $linkedobjects, 'outputlangs' => $outputlangs);
			$action		= '';
			$hookmanager->executeHooks('pdf_getLinkedObjects', $parameters, $object, $action);
			if (!empty($hookmanager->resArray)) {
				$linkedobjects	= $hookmanager->resArray;
			}
		}
		return $linkedobjects;
	}

	/**
	*	Return linked shipping objects to use for document generation.
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	Translate	$outputlangs	Output langs object
	*	@return	array						Linked shippings
	**/
	function pdf_InfraSPlus_getLinkedshippings($object, $outputlangs)
	{
		global $conf, $db;

		$linkedshippings	= array();
		$sql				= 'SELECT *';
		$sql				.= ' FROM '.$db->prefix().'element_element AS ee';
		$sql				.= ' INNER JOIN '.$db->prefix().'expedition AS e';
		$sql				.= ' ON ee.fk_source = e.rowid';
		$sql				.= ' WHERE ee.sourcetype = "shipping"';
		$sql				.= ' AND ee.fk_target = "'.$object->id.'"';
		$resql				= $db->query($sql);
		if (!empty($resql)) {
			$num	= $db->num_rows($resql);
			for ($i = 0; $i < $num; $i++) {
				$obj		= $db->fetch_object($resql);
				$linkedshippings['ref']					= $obj->ref;
				$linkedshippings['date_delivery']		= $obj->date_delivery;
				$linkedshippings['fk_shipping_method']	= $obj->fk_shipping_method;
				$linkedshippings['tracking_number']		= $obj->tracking_number;
			}
		}
		$db->free($resql);
		return $linkedshippings;
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
	function pdf_InfraSPlus_getAddresses($object, $outputlangs, $arrayidcontact, $adr, $adrlivr, $emetteur, $invLivr = 0, $typeadr = '', $adrfact = '', $ticket = 0, $Sst = -2, $adrSst = -2, $customerAddr = '', $includealias = 0)
	{
		global $db, $conf;

		$thirdparty					= !empty($object->thirdparty) ? $object->thirdparty : $object;
		$show_sales_rep_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_FIRST_SALES_REP_IN_NOTE', 0);
		$show_emet_details			= !empty($ticket) ? 0 : getDolGlobalInt('INFRASPLUS_PDF_SHOW_EMET_DETAILS', 0);
		$show_livr_details			= !empty($ticket) ? 0 : getDolGlobalInt('INFRASPLUS_PDF_SHOW_LIVR_DETAILS', 0);
		$show_sender_alias			= getDolGlobalInt('INFRASPLUS_PDF_SHOW_SENDER_ALIAS', 0);
		$show_recep_details			= !empty($ticket) ? 0 : getDolGlobalInt('INFRASPLUS_PDF_SHOW_RECEP_DETAILS', 0);
		$showadrlivr				= getDolGlobalInt('INFRASPLUS_PDF_SHOW_ADRESSE_RECEPTION', 0);
		$free_addr_livr				= getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', '');
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
			$printable		= intval($extrafields->attributes[$object->table_element]['printable'][$free_addr_livr]);
			$value			= pdf_InfraSPlus_formatNotes($object, $outputlangs, $extrafields->showOutputField($free_addr_livr, $object->array_options['options_'.$free_addr_livr], '', $object->table_element));
			$free_addr_livr	= $printable == 1 || (!empty($value) && $printable == 2) ? $value : '';	// check if something is writting for this extrafield according to the extrafield management
		}
		$use_doli_addr_livr		= getDolGlobalInt('INFRASPLUS_PDF_USE_DOLI_ADRESSE_LIVRAISON', 0);
		$doli_addr_livr_recep	= empty($use_doli_addr_livr) ? 0 : getDolGlobalInt('INFRASPLUS_PDF_DOLI_ADRESSE_LIVRAISON_RECEP', 0);
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
		// Add internal contact if defined if we don't want this contact in notes
		if (!empty($arrayidcontact['I']) && is_array($arrayidcontact['I']) && count($arrayidcontact['I']) > 0 && empty($show_sales_rep_in_notes)) {
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
		if (!empty($arrayidcontact['U']) && is_object ($arrayidcontact['U'])) {	// Expense report
			$carac_client_name	= $outputlangs->transnoentities('AUTHOR').' : '.$arrayidcontact['U']->getFullName($outputlangs);
			$carac_client		= $outputlangs->transnoentities('DateCreation').' : '.dol_print_date($object->date_create, 'day', false, $outputlangs)."\n";
			$det_client			= $outputlangs->convToOutputCharset(dol_format_address($arrayidcontact['U']->address, 0, "\n", $outputlangs))."\n";
			$det_client			.= $arrayidcontact['U']->email ? $outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($arrayidcontact['U']->email)."\n" : '';
			$det_client			.= $arrayidcontact['B']->iban ? $outputlangs->transnoentities('IBAN').' : '.$outputlangs->convToOutputCharset($arrayidcontact['B']->iban)."\n" : '';
			$carac_client		.= $det_client ? $det_client."\n" : '';
			if ($object->fk_statut == 99 && $object->fk_user_refuse > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_refuse);
				$carac_client	.= $outputlangs->transnoentities('REFUSEUR').' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities('MOTIF_REFUS').' : '.$outputlangs->convToOutputCharset($object->detail_refuse)."\n";
				$carac_client	.= $outputlangs->transnoentities('DATE_REFUS').' : '.dol_print_date($object->date_refuse, 'day', false, $outputlangs);
			} elseif ($object->fk_statut == 4 && $object->fk_user_cancel > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_cancel);
				$carac_client	.= $outputlangs->transnoentities('CANCEL_USER').' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities('MOTIF_CANCEL').' : '.$outputlangs->convToOutputCharset($object->detail_cancel)."\n";
				$carac_client	.= $outputlangs->transnoentities('DATE_CANCEL').' : '.dol_print_date($object->date_cancel, 'day', false, $outputlangs);
			} elseif ($object->fk_user_approve > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_approve);
				$carac_client	.= $outputlangs->transnoentities('VALIDOR').' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities('DateApprove').' : '.dol_print_date($object->date_approve, 'day', false, $outputlangs);
			}
			if ($object->fk_statut == 6 && $object->fk_user_paid > 0) {
				$userfee		= new User($db);
				$userfee->fetch($object->fk_user_paid);
				$carac_client	.= $outputlangs->transnoentities('AUTHORPAIEMENT').' : '.$userfee->getFullName($outputlangs)."\n";
				$carac_client	.= $outputlangs->transnoentities('DATE_PAIEMENT').' : '.dol_print_date($object->date_paiement, 'day', false, $outputlangs);
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
				if (!empty($doli_addr_livr_recep) && is_array($arrayidcontact['L']) && count($arrayidcontact['L']) > 0 && $object->element == 'commande') {
					$usecontact	= true;
					$result		= $object->fetch_contact($arrayidcontact['L'][0]);
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
			if (!empty($use_doli_addr_livr) && is_array($arrayidcontact['L']) && count($arrayidcontact['L']) > 0) {
				$companyDiff	= 0;
				$result			= $object->fetch_contact($arrayidcontact['L'][0]);
				$usecontact		= in_array($customerAddr, array('C', 'A')) ? true : false;
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
				$livrshow		= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, ($companyDiff ? $object->contact->thirdparty : $thirdparty), ($usecontact ? $object->contact : ''), $usecontact, ($show_recep_details ? 'targetwithdetails' : 'target'), $object, -1, $ticket);
			} elseif (!empty($showadrlivr) && !empty($addresslivrstatic) && empty($free_addr_livr)) {
				if ($addresslivrstatic == 'Default') {
					$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $thirdparty, '', 0, ($show_livr_details ? 'targetwithdetails' : 'target'), $object, 0, $ticket);
				} else {
					$livrshow	= pdf_InfraSPlus_build_address($outputlangs, $emetteur, $emetteur, $addresslivrstatic, '', 0, ($show_livr_details ? 'targetwithdetails' : 'target'), $object, 0, $ticket);
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
	*	Return a string with full address formated for output on documents
	*
	*	@param	Translate			$outputlangs		Output langs object
	*	@param	Societe				$sourcecompany		Source company object
	*	@param	object				$sourceaddress		Source address object
	*	@param	object|string|null	$targetcompany		Target company object
	*	@param	object|string|null	$targetcontact		Target contact object
	*	@param	int					$usecontact			Use contact instead of company
	*	@param	string				$mode				Address type ('source', 'sourcewithnodetails', 'target', 'targetwithnodetails', 'targetwithdetails',
	*																	'targetwithdetails_xxx': target but include also phone/fax/email/url)
	*	@param	object				$object				Object we want to build document for
	*	@param	boolean				$profids			Display profesionnal IDs (-1 => for shipping address, 0 => standard adress with no prof IDs, 1 => standard adress with prof IDs)
	*	@param	boolean				$ticket				set to 1 for special format (small page)
	*	@param	boolean				$forceHideNumCli	Disable the option 'show customer number' for some specific templates
	*	@return	string									String with full address
	**/
	function pdf_InfraSPlus_build_address($outputlangs, $sourcecompany, $sourceaddress = '', $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null, $profids = 0, $ticket = 0, $forceHideNumCli = 0)
	{
		global $conf, $hookmanager;

		if ((strpos($mode, 'source') === 0 && ! is_object($sourcecompany) && ! is_object($sourceaddress)) || (strpos($mode, 'target') === 0 && ! is_object($targetcompany))) {
			return -1;
		}
		if (!empty($sourceaddress->state_id) && empty($sourceaddress->state)) {
			$sourceaddress->state = getState($sourceaddress->state_id);
		}
		if (!empty($targetcompany->state_id) && empty($targetcompany->state)) {
			$targetcompany->state = getState($targetcompany->state_id);
		}
		$targetcompanyIDs	= $profids == 2 ? infraspackplus_check_parent_addr_fact($object) : $targetcompany;
		$reshook			= 0;
		$stringaddress		= '';
		$disableSourceDet	= getDolGlobalInt('MAIN_PDF_DISABLESOURCEDETAILS', 0);
		$forceWithCountry	= getDolGlobalInt('INFRASPLUS_PDF_WITH_COUNTRY', 0);
		$sourceDetPhone		= getDolGlobalInt('INFRASPLUS_PDF_SOURCE_DETAIL_PHONE', 0);
		$sourceDetFax		= getDolGlobalInt('INFRASPLUS_PDF_SOURCE_DETAIL_FAX', 0);
		$sourceDetEmail		= getDolGlobalInt('INFRASPLUS_PDF_SOURCE_DETAIL_MAIL', 0);
		$sourceDetWeb		= getDolGlobalInt('INFRASPLUS_PDF_SOURCE_DETAIL_WEB', 0);
		$tvaInSourceAddr	= getDolGlobalInt('INFRASPLUS_PDF_TVAINTRA_IN_SOURCE_ADDRESS', 0);
		$id1InSourceAddr	= getDolGlobalInt('INFRASPLUS_PDF_PROFID1_IN_SOURCE_ADDRESS', 0);
		$id2InSourceAddr	= getDolGlobalInt('INFRASPLUS_PDF_PROFID2_IN_SOURCE_ADDRESS', 0);
		$id3InSourceAddr	= getDolGlobalInt('INFRASPLUS_PDF_PROFID3_IN_SOURCE_ADDRESS', 0);
		$id4InSourceAddr	= getDolGlobalInt('INFRASPLUS_PDF_PROFID4_IN_SOURCE_ADDRESS', 0);
		$id5InSourceAddr	= getDolGlobalInt('INFRASPLUS_PDF_PROFID5_IN_SOURCE_ADDRESS', 0);
		$id6InSourceAddr	= getDolGlobalInt('INFRASPLUS_PDF_PROFID6_IN_SOURCE_ADDRESS', 0);
		$moreInSourceAddr	= getDolGlobalInt('PDF_ADD_MORE_AFTER_SOURCE_ADDRESS', 0);
		$targetDet			= getDolGlobalInt('MAIN_PDF_ADDALSOTARGETDETAILS', 0);
		$targetDetPhone		= getDolGlobalInt('INFRASPLUS_PDF_TARGET_DETAIL_PHONE', 0);
		$targetDetFax		= getDolGlobalInt('INFRASPLUS_PDF_TARGET_DETAIL_FAX', 0);
		$targetDetEmail		= getDolGlobalInt('INFRASPLUS_PDF_TARGET_DETAIL_MAIL', 0);
		$targetDetWeb		= getDolGlobalInt('INFRASPLUS_PDF_TARGET_DETAIL_WEB', 0);
		$showNumCli			= getDolGlobalInt('INFRASPLUS_PDF_SHOW_NUM_CLI', 0);
		$numCliFrm			= getDolGlobalInt('INFRASPLUS_PDF_NUM_CLI_FRM', 0);
		$showCodeCliCompt	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT', 0);
		$codeCliComptFrm	= getDolGlobalInt('INFRASPLUS_PDF_CODE_CLI_COMPT_FRM', 0);
		$noTvaAddr			= getDolGlobalInt('MAIN_TVAINTRA_NOT_IN_ADDRESS', 0);
		$id1InAddr			= getDolGlobalInt('MAIN_PROFID1_IN_ADDRESS', 0);
		$id2InAddr			= getDolGlobalInt('MAIN_PROFID2_IN_ADDRESS', 0);
		$id3InAddr			= getDolGlobalInt('MAIN_PROFID3_IN_ADDRESS', 0);
		$id4InAddr			= getDolGlobalInt('MAIN_PROFID4_IN_ADDRESS', 0);
		$id5InAddr			= getDolGlobalInt('MAIN_PROFID5_IN_ADDRESS', 0);
		$id6InAddr			= getDolGlobalInt('MAIN_PROFID6_IN_ADDRESS', 0);
		$noteInAddr			= getDolGlobalInt('MAIN_PUBLIC_NOTE_IN_ADDRESS', 0);
		if (is_object($hookmanager)) {
			$parameters		= array('sourcecompany'=>&$sourcecompany, 'targetcompany'=>&$targetcompany, 'targetcontact'=>&$targetcontact, 'outputlangs'=>$outputlangs, 'mode'=>$mode, 'usecontact'=>$usecontact);
			$action			= '';
			$reshook		= $hookmanager->executeHooks('pdf_build_address', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			$stringaddress	.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			$withCountry	= ((!empty($sourceaddress->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourceaddress->country_code)) || $forceWithCountry) ? 1 : 0;	// Country
			if ($mode == 'source' || $mode == 'sourcewithnodetails') {
				$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourceaddress, $withCountry, "\n", $outputlangs)).($ticket ? '' : "\n");
				if ($mode != 'sourcewithnodetails') {
					if (empty($disableSourceDet)) {
						// Phone
						if (!empty($sourceDetPhone) && $sourceaddress->phone) {
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($sourceaddress->phone)));
						}
						// Fax
						if (!empty($sourceDetFax) && $sourceaddress->fax) {
							$stringaddress	.= ($stringaddress ? ($sourceaddress->phone ? ' - ' : "\n") : '' ).$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($sourceaddress->fax)));
						}
						// EMail
						if (!empty($sourceDetEmail) && $sourceaddress->email) {
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($sourceaddress->email);
						}
						// Web
						if (!empty($sourceDetWeb) && $sourceaddress->url) {
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Web').' : '.$outputlangs->convToOutputCharset($sourceaddress->url);
						}
					}
				}
				if ($profids > 0) {
					$reg	= array();
					if ((!empty($tvaInSourceAddr) || !empty($ticket)) && !empty($sourcecompany->tva_intra)) {
						$tmpID			= pdf_InfraSPlus_build_IDs('TVA', $outputlangs->convToOutputCharset($sourcecompany->tva_intra), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('VATIntraShort').' : '.$tmpID;
					}
					if ((!empty($id1InSourceAddr) || !empty($ticket)) && !empty($sourcecompany->idprof1)) {
						$tmp	= $outputlangs->transcountrynoentities('ProfId1', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) {
							$tmp	= $reg[1];
						}
						$tmpID			= pdf_InfraSPlus_build_IDs('ID1', $outputlangs->convToOutputCharset($sourcecompany->idprof1), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id2InSourceAddr) && !empty($sourcecompany->idprof2)) {
						$tmp	= $outputlangs->transcountrynoentities('ProfId2', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) {
							$tmp	= $reg[1];
						}
						$tmpID			= pdf_InfraSPlus_build_IDs('ID2', $outputlangs->convToOutputCharset($sourcecompany->idprof2), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id3InSourceAddr) && !empty($sourcecompany->idprof3)) {
						$tmp	= $outputlangs->transcountrynoentities('ProfId3', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) {
							$tmp	= $reg[1];
						}
						$tmpID			= pdf_InfraSPlus_build_IDs('ID3', $outputlangs->convToOutputCharset($sourcecompany->idprof3), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id4InSourceAddr) && !empty($sourcecompany->idprof4)) {
						$tmp	= $outputlangs->transcountrynoentities('ProfId4', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) {
							$tmp	= $reg[1];
						}
						$tmpID			= pdf_InfraSPlus_build_IDs('ID4', $outputlangs->convToOutputCharset($sourcecompany->idprof4), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id5InSourceAddr) && !empty($sourcecompany->idprof5)) {
						$tmp	= $outputlangs->transcountrynoentities('ProfId5', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) {
							$tmp	= $reg[1];
						}
						$tmpID			= pdf_InfraSPlus_build_IDs('ID5', $outputlangs->convToOutputCharset($sourcecompany->idprof5), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
					if (!empty($id6InSourceAddr) && !empty($sourcecompany->idprof6)) {
						$tmp	= $outputlangs->transcountrynoentities('ProfId6', $sourcecompany->country_code);
						if (preg_match('/\((.+)\)/', $tmp, $reg)) {
							$tmp	= $reg[1];
						}
						$tmpID			= pdf_InfraSPlus_build_IDs('ID6', $outputlangs->convToOutputCharset($sourcecompany->idprof6), $sourcecompany->country_code);
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
					}
				}
				if (!empty($moreInSourceAddr)) {
					$stringaddress	.= ($stringaddress ? "\n" : '').$moreInSourceAddr;
				}
			}
			if ($mode == 'target' || $mode == 'targetwithnodetails' || preg_match('/targetwithdetails/',$mode)) {
				if (!empty($usecontact)) {
					if (is_object($targetcontact)) {
						if (!empty($targetcontact->address)) {
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact, $withCountry, "\n", $outputlangs))."\n";
						} else {
							$companytouseforaddress	= $targetcompany;
							if ($targetcontact->socid > 0 && $targetcontact->socid != $targetcompany->id) {	// Contact on a thirdparty that is a different thirdparty than the thirdparty of object
								$targetcontact->fetch_thirdparty();
								$companytouseforaddress	= $targetcontact->thirdparty;
							}
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress, $withCountry, "\n", $outputlangs))."\n";
						}
						if (!empty($targetDet) || preg_match('/targetwithdetails/', $mode)) {
							// Phone
							if (!empty($targetDet) || preg_match('/targetwithdetails_phone/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetPhone))) {
								if (!empty($targetcontact->phone_pro) || !empty($targetcontact->phone_mobile)) {
									$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('PhoneShort').' : ';
								}
								if (!empty($targetcontact->phone_pro)) {
									$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcontact->phone_pro)));
								}
								if (!empty($targetcontact->phone_pro) && !empty($targetcontact->phone_mobile)) {
									$stringaddress	.= ' / ';
								}
								if (!empty($targetcontact->phone_mobile)) {
									$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcontact->phone_mobile)));
								}
							}
							// Fax
							if (!empty($targetDet) || preg_match('/targetwithdetails_fax/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetFax))) {
								if (!empty($targetcontact->fax)) {
									$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcontact->fax)));
								}
							}
							// EMail
							if (!empty($targetDet) || preg_match('/targetwithdetails_email/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetEmail))) {
								if (!empty($targetcontact->email)) {
									$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($targetcontact->email);
								}
							}
							// Web
							if (!empty($targetDet) || preg_match('/targetwithdetails_url/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetWeb))) {
								if (!empty($targetcontact->url)) {
									$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Web').' : '.$outputlangs->convToOutputCharset($targetcontact->url);
								}
							}
						}
					}
				} else {
					if (is_object($targetcompany)) {
						$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany, $withCountry, "\n", $outputlangs)).($ticket ? '' : "\n");
						if (!empty($targetDet) || preg_match('/targetwithdetails/', $mode)) {
							// Phone
							if (!empty($targetDet) || preg_match('/targetwithdetails_phone/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetPhone))) {
								if (!empty($targetcompany->phone) || !empty($targetcompany->phone_mobile))	$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('PhoneShort').' : ';
								if (!empty($targetcompany->phone))											$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcompany->phone)));
								if (!empty($targetcompany->phone) && !empty($targetcompany->phone_mobile))	$stringaddress	.= ' / ';
								if (!empty($targetcompany->phone_mobile))									$stringaddress	.= $outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcompany->phone_mobile)));
							}
							// Fax
							if (!empty($targetDet) || preg_match('/targetwithdetails_fax/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetFax))) {
								if (!empty($targetcompany->fax)) {
									$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($targetcompany->fax)));
								}
							}
							// EMail
							if (!empty($targetDet) || preg_match('/targetwithdetails_email/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetEmail))) {
								if (!empty($targetcompany->email)) {
									$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($targetcompany->email);
								}
							}
							// Web
							if (!empty($targetDet) || preg_match('/targetwithdetails_url/', $mode) || ($mode == 'targetwithdetails' && !empty($targetDetWeb))) {
								if (!empty($targetcompany->url)) {
									$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('Web').' : '.$outputlangs->convToOutputCharset($targetcompany->url);
								}
							}
						}
					}
				}
				if ($profids > -1) {
					$listElementsCli	= array('propal', 'commande', 'facture', 'contrat', 'shipping', 'fichinter');
					$showNumCli			= !empty($showNumCli) && empty($numCliFrm) && empty($forceHideNumCli) ? 1 : 0;
					$showCodeCliCompt	= !empty($showCodeCliCompt) && empty($codeCliComptFrm) ? 1 : 0;
					if (in_array($object->element, $listElementsCli) && $showNumCli && $mode != 'targetwithnodetails') {
						if (!empty($object->thirdparty->code_client)) {
							$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities('CustomerCode') . ' : ' . $outputlangs->convToOutputCharset($object->thirdparty->code_client);
						}
					}
					if (in_array($object->element, $listElementsCli) && $showCodeCliCompt && $mode != 'targetwithnodetails') {
						if (!empty($object->thirdparty->code_compta)) {
							$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities('CustomerAccountancyCode') . ' : ' . $outputlangs->convToOutputCharset($object->thirdparty->code_compta);
						}
					}
					// Intra VAT
					if (empty($noTvaAddr) && $mode != 'targetwithnodetails') {
						if (!empty($targetcompanyIDs->tva_intra)) {
							$tmpID			= pdf_InfraSPlus_build_IDs('TVA', $outputlangs->convToOutputCharset($targetcompanyIDs->tva_intra), $targetcompanyIDs->country_code);
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities('VATIntraShort').' : '.$tmpID;
						}
					}
					// Professionnal Ids
					if ($profids > 0) {
						if (!empty($id1InAddr) && !empty($targetcompanyIDs->idprof1)) {
							$tmp	= $outputlangs->transcountrynoentities('ProfId1', $targetcompanyIDs->country_code);
							if (preg_match('/\((.+)\)/', $tmp, $reg)) {
								$tmp	= $reg[1];
							}
							$tmpID			= pdf_InfraSPlus_build_IDs('ID1', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof1), $targetcompanyIDs->country_code);
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
						}
						if (!empty($id2InAddr) && !empty($targetcompanyIDs->idprof2)) {
							$tmp	= $outputlangs->transcountrynoentities('ProfId2', $targetcompanyIDs->country_code);
							if (preg_match('/\((.+)\)/', $tmp, $reg)) {
								$tmp	= $reg[1];
							}
							$tmpID			= pdf_InfraSPlus_build_IDs('ID2', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof2), $targetcompanyIDs->country_code);
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
						}
						if (!empty($id3InAddr) && !empty($targetcompanyIDs->idprof3)) {
							$tmp	= $outputlangs->transcountrynoentities('ProfId3', $targetcompanyIDs->country_code);
							if (preg_match('/\((.+)\)/', $tmp, $reg)) {
								$tmp	= $reg[1];
							}
							$tmpID			= pdf_InfraSPlus_build_IDs('ID3', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof3), $targetcompanyIDs->country_code);
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
						}
						if (!empty($id4InAddr) && !empty($targetcompanyIDs->idprof4)) {
							$tmp	= $outputlangs->transcountrynoentities('ProfId4', $targetcompanyIDs->country_code);
							if (preg_match('/\((.+)\)/', $tmp, $reg)) {
								$tmp	= $reg[1];
							}
							$tmpID			= pdf_InfraSPlus_build_IDs('ID4', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof4), $targetcompanyIDs->country_code);
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
						}
						if (!empty($id5InAddr) && !empty($targetcompanyIDs->idprof5)) {
							$tmp	= $outputlangs->transcountrynoentities('ProfId5', $targetcompanyIDs->country_code);
							if (preg_match('/\((.+)\)/', $tmp, $reg)) {
								$tmp	= $reg[1];
							}
							$tmpID			= pdf_InfraSPlus_build_IDs('ID5', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof5), $targetcompanyIDs->country_code);
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
						}
						if (!empty($id6InAddr) && !empty($targetcompanyIDs->idprof6)) {
							$tmp	= $outputlangs->transcountrynoentities('ProfId6', $targetcompanyIDs->country_code);
							if (preg_match('/\((.+)\)/', $tmp, $reg)) {
								$tmp	= $reg[1];
							}
							$tmpID			= pdf_InfraSPlus_build_IDs('ID6', $outputlangs->convToOutputCharset($targetcompanyIDs->idprof6), $targetcompanyIDs->country_code);
							$stringaddress	.= ($stringaddress ? "\n" : '' ).$tmp.' : '.$tmpID;
						}
					}
					// Public note
					if (!empty($noteInAddr)) {
						if ($mode == 'source' && !empty($sourcecompany->note_public)) {
							$stringaddress	.= ($stringaddress ? "\n" : '' ).dol_string_nohtmltag($sourcecompany->note_public);
						} elseif (($mode == 'target' || preg_match('/targetwithdetails/',$mode)) && !empty($targetcompany->note_public)) {
							$stringaddress	.= ($stringaddress ? "\n" : '' ).dol_string_nohtmltag($targetcompany->note_public);
						}
					}
				}
			}
		}
		return $stringaddress;
	}

	/**
	*	Format IDs
	*
	*	@param	string		$typeID			Type of IDs (ID1, ID2, ..., TVA)
	*	@param	string		$profID			Original ID
	*	@param	string		$country_code	Country code of company
	*	@return	string						String with formated ID
	**/
	function pdf_InfraSPlus_build_IDs($typeID, $profID, $country_code)
	{
		if (strtoupper($country_code) == 'FR') {
			if ($typeID == 'ID1' && dol_strlen($profID) == 9) {
				$profID	= substr($profID, 0, 3).' '.substr($profID, 3, 3).' '.substr($profID, 6, 3);
			} elseif ($typeID == 'ID2' && dol_strlen($profID) == 14) {
				$profID	= substr($profID, 0, 3).' '.substr($profID, 3, 3).' '.substr($profID, 6, 3).' '.substr($profID, 9, 5);
			} elseif ($typeID == 'TVA' && dol_strlen($profID) == 13) {
				$profID	= substr($profID, 0, 2).' '.substr($profID, 2, 2).' '.substr($profID, 4, 3).' '.substr($profID, 7, 3).' '.substr($profID, 10, 3);
			}
		}
		return $profID;
	}

	/**
	*	Returns the name of the thirdparty
	*
	*	@param	Societe|Contact		$thirdparty		Contact or thirdparty
	*	@param	Translate			$outputlangs	Output language
	*	@param	int					$includealias	1 = Include alias name before name
	*	@param	int					$contact		Contact
	*	@param	string				$customerAddr	Customer address used ('T' = Third party only, 'C' = Linked Contact if exist or third party, 'B' = Third party addrss + contact name)
	*	@return	string				String with name of thirdparty (+ alias if requested)
	**/
	function pdf_InfraSPlus_Build_Third_party_Name($thirdparty, $outputlangs, $includealias = 0, $contact = '', $customerAddr = '')
	{
		$useContactName	= !empty($contact) && in_array($customerAddr, array('C', 'B', 'A')) ? 1 : 0;
		$statusWithName	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_STATUS_WITH_CLIENT_NAME', 0);
		$statusWithName	= !empty($statusWithName) && $thirdparty->forme_juridique_code ? ' '.$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($thirdparty->forme_juridique_code)) : '';
		if ($thirdparty instanceof Societe) {
			$socname		= $thirdparty->name.$statusWithName.($includealias && !empty($thirdparty->name_alias) ? ' - '.$thirdparty->name_alias : '');
		}
		if ($contact instanceof Contact) {
			$contactname	= $outputlangs->convToOutputCharset($contact->getFullName($outputlangs, 1, -1));
		}
		return $outputlangs->convToOutputCharset($customerAddr == 'C' && !empty($contactname) ? $contactname : (in_array($customerAddr, array('B', 'A')) ? $socname.(!empty($contactname) ? "\n".$contactname : '') : $socname));
	}

	/**
	*	Show top small header of page.
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	object			$object			Object shown in PDF
	*	@param	Translate		$outputlangs	Object lang for output
	*	@param	array			$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	array			$dimCadres		Frame dimensions => 'R', 'S', 'Y'
	*	@param	int				$tab_hl			Line height
	*	@param	object			$emetteur		Object company
	*	@param	array			$addresses		All addresses found
	*	@param	int				$Rounded_rect	Radius corner value
	*	@param	boolean			$ndf			Frames title for expense report
	*	@return	float							return frame height
	**/
	function pdf_InfraSPlus_writeAddresses(&$pdf, $object, $outputlangs, $formatpage, $dimCadres, $tab_hl, $emetteur, $addresses, $Rounded_rect, $ndf = false)
	{
		global $conf;

		$invert_sender_recipient	= getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT', 0);
		$hide_labels_frames			= getDolGlobalInt('INFRASPLUS_PDF_HIDE_LABELS_FRAMES', 0);
		$hide_recep_frame			= getDolGlobalInt('INFRASPLUS_PDF_HIDE_RECEP_FRAME', 0);
		$frmeLineW					= getDolGlobalFloat('INFRASPLUS_PDF_FRM_E_LINE_WIDTH', 0.2);
		$frmeLineDash				= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_LINE_DASH', 0);
		$frmeLineColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_COLOR', '128,128,128');
		$frmeLineColor				= explode(',', $frmeLineColor);
		$frmeLineAlpha				= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_LINE_OPACITY', 30);
		$frmeBgColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_E_BG_COLOR', '109,70,140');
		$frmeBgColor				= explode(',', $frmeBgColor);
		$frmeAlpha					= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_OPACITY', 30);
		$frmrLineW					= getDolGlobalFloat('INFRASPLUS_PDF_FRM_R_LINE_WIDTH', 0.2);
		$frmrLineDash				= getDolGlobalInt('INFRASPLUS_PDF_FRM_R_LINE_DASH', 0);
		$frmrLineColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_COLOR', '128,128,128');
		$frmrLineColor				= explode(',', $frmrLineColor);
		$frmrLineAlpha				= getDolGlobalInt('INFRASPLUS_PDF_FRM_R_LINE_OPACITY', 30);
		$frmrBgColor				= getDolGlobalString('INFRASPLUS_PDF_FRM_R_BG_COLOR', '109,70,140');
		$frmrBgColor				= explode(',', $frmrBgColor);
		$frmrAlpha					= getDolGlobalInt('INFRASPLUS_PDF_FRM_R_OPACITY', 30);
		$frmeLineCap				= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		$frmeLineStyle				= array('width'=>$frmeLineW, 'dash'=>$frmeLineDash, 'cap'=>$frmeLineCap, 'color'=>$frmeLineColor);
		$frmrLineCap				= 'butt';	// fin de trait : butt = rectangle/lg->Dash ; round = rond/lg->Dash + width : square = rectangle/lg->Dash + width
		$frmrLineStyle				= array('width'=>$frmrLineW, 'dash'=>$frmrLineDash, 'cap'=>$frmrLineCap, 'color'=>$frmrLineColor);
		$default_font_size			= pdf_getPDFFontSize($outputlangs);
		$decal_round				= $Rounded_rect > 0 ? $Rounded_rect : 0;
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
		$hauteurcadre	= pdf_InfraSPlus_writeFrame($pdf, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, 0, $hide_recep_frame);
		$pdf->rollbackTransaction(true);
		if (empty($hide_labels_frames)) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$txtFrom	= $ndf ? 'TripSociete' : 'BillFrom';
			$pdf->MultiCell($dimCadres['S'], $tab_hl + 1, $outputlangs->transnoentities($txtFrom).' : ', '', 'L', 0, 1, $dimCadres['xS'] + $decal_round, $dimCadres['Y'] - 4, true, 0, 0, false, 0, 'M', false);
			$txtTo		= $ndf ? 'TripNDF' : ($object->element == 'order_supplier' || $object->element == 'supplier_proposal' ? 'PDFInfraSPlusAddressedTo' : 'BillTo');
			$pdf->MultiCell($dimCadres['R'], $tab_hl + 1, $outputlangs->transnoentities($txtTo).' : ', '', 'L', 0, 1, $dimCadres['xR'] + $decal_round, $dimCadres['Y'] - 4, true, 0, 0, false, 0, 'M', false);
		}
		// Show sender frame
		if (empty($hide_recep_frame)) {
			if (implode(',', $frmeLineColor) != '255, 255, 255') {
				$pdf->SetAlpha($frmeLineAlpha / 100);
				$pdf->RoundedRect($dimCadres['xS'], $dimCadres['Y'], $dimCadres['S'], $hauteurcadre, $Rounded_rect, '1111', null, $frmeLineStyle);	// cadre seul
			}
			$pdf->SetAlpha($frmeAlpha / 100);
			$frme	= implode(',', $frmeBgColor) == '255, 255, 255' ? '' : 'F';	// 'null' => cadre seul | 'D' => cadre seul | 'F' => fond seul | 'DF' => cadre + fond
			$pdf->RoundedRect($dimCadres['xS'], $dimCadres['Y'], $dimCadres['S'], $hauteurcadre, $Rounded_rect, '1111', $frme, $frmeLineStyle, $frmeBgColor);
		}
		// Show recipient frame
		if (implode(',', $frmrLineColor) != '255, 255, 255') {
			$pdf->SetAlpha($frmrLineAlpha / 100);
			$pdf->RoundedRect($dimCadres['xR'], $dimCadres['Y'], $dimCadres['R'], $hauteurcadre, $Rounded_rect, '1111', null, $frmrLineStyle);	// cadre seul
		}
		$pdf->SetAlpha($frmrAlpha / 100);
		$frmr	= implode(',', $frmrBgColor) == '255, 255, 255' ? '' : 'F';	// 'null' => cadre seul | 'D' => cadre seul | 'F' => fond seul | 'DF' => cadre + fond
		$pdf->RoundedRect($dimCadres['xR'], $dimCadres['Y'], $dimCadres['R'], $hauteurcadre, $Rounded_rect, '1111', $frmr, $frmrLineStyle, $frmrBgColor);
		$pdf->SetAlpha(1);
		pdf_InfraSPlus_writeFrame($pdf, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, 0, $hide_recep_frame);
		return $hauteurcadre;
	}

	/**
	*	Show top small header of page.
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	Translate		$outputlangs		Object lang for output
	*	@param	int				$default_font_size	Font size
	*	@param	int				$tab_hl				Line height
	*	@param	array			$dimCadres			Frame dimensions => 'R', 'S', 'Y', 'xS', 'xR'
	*	@param	object			$emetteur			Object company
	*	@param	array			$addresses			All addresses found
	*	@param	boolean			$ticket				Ticket format
	*	@param	boolean			$hide_recep_frame	No sender frame
	*	@param	float								return frame height
	**/
	function pdf_InfraSPlus_writeFrame(&$pdf, $outputlangs, $default_font_size, $tab_hl, $dimCadres, $emetteur, $addresses, $ticket = 0, $hide_recep_frame = 0)
	{
		global $conf;

		$frmeTxtColor	= getDolGlobalString('INFRASPLUS_PDF_FRM_E_TEXT_COLOR', '0,0,0');
		$frmeTxtColor	= explode(',', $frmeTxtColor);
		$frmrTxtColor	= getDolGlobalString('INFRASPLUS_PDF_FRM_R_TEXT_COLOR', '0,0,0');
		$frmrTxtColor	= explode(',', $frmrTxtColor);
		$statusWithName	= getDolGlobalString('INFRASPLUS_PDF_SHOW_STATUS_WITH_SENDER_NAME', '') && !empty($emetteur->forme_juridique_code) ? ' '.$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($emetteur->forme_juridique_code))	: '';
		if (empty($hide_recep_frame)) {
			// Show sender
			$posy	= $dimCadres['Y'];
			if (empty($ticket)) {
				$pdf->SetTextColor($frmeTxtColor[0], $frmeTxtColor[1], $frmeTxtColor[2]);
			}
			// Show sender name
			$pdf->SetFont('', 'B', $default_font_size - ($ticket ? 2 : 0));
			$emetteurName	= ($ticket ? $outputlangs->transnoentities('BillFrom').' : ' : '').(!empty($addresses['sender_Alias']) ? $addresses['sender_Alias'] : $emetteur->name);
			$pdf->MultiCell($dimCadres['S'] - 4, $tab_hl, $outputlangs->convToOutputCharset($emetteurName).$statusWithName, '', 'L', 0, 1, $dimCadres['xS'] + 2, $posy + 1, true, 0, 0, false, 0, 'M', false);
			$posy			= $pdf->getY();
			// Show sender information
			$pdf->SetFont('', '', $default_font_size - ($ticket ? 3 : 1));
			$pdf->MultiCell($dimCadres['S'] - 4, $tab_hl, $addresses['carac_emetteur'], '', 'L', 0, 1, $dimCadres['xS'] + 2, $posy, true, 0, 0, false, 0, 'M', false);
			$posyendsender	= $pdf->getY();
		}
		//Show Recipient
		if (empty($ticket)) {
			$posy	= $dimCadres['Y'];
		} else {
			$posy	= $posyendsender;
		}
		if (empty($ticket)) {
			$pdf->SetTextColor($frmrTxtColor[0], $frmrTxtColor[1], $frmrTxtColor[2]);
		}
		// Show recipient name
		$pdf->SetFont('', 'B', $default_font_size - ($ticket ? 2 : 0));
		$pdf->MultiCell($dimCadres['R'] - 4, $tab_hl, ($ticket ? $outputlangs->transnoentities('BillTo').' : ' : '').$addresses['carac_client_name'], '', 'L', 0, 1, $dimCadres['xR'] + 2, $posy + 1, true, 0, 0, false, 0, 'M', false);
		$posy									= $pdf->getY();
		// Show recipient information
		$pdf->SetFont('', '', $default_font_size - ($ticket ? 3 : 1));
		$pdf->MultiCell($dimCadres['R'] - 4, $tab_hl, $addresses['carac_client'], '', 'L', 0, 1, $dimCadres['xR'] + 2, $posy, true, 0, 0, false, 0, 'M', false);
		$posyendrecipient	= $pdf->getY();
		return $posyendsender > $posyendrecipient ? ($posyendsender - $dimCadres['Y']) + 1 : ($posyendrecipient - $dimCadres['Y']) + 1;
	}

	/**
	*	Show top small header of page.
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	object			$object			Object shown in PDF
	*	@param	int				$showaddress	0=no, 1=yes
	*	@param	Translate		$outputlangs	Object lang for output
	*	@param	string			$title			string title in connection with objet type
	*	@param	Societe			$fromcompany	Object company
	*	@param	array			$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int				$decal_round	décalage en fonction du rayon des angles du tableau
	*	@param	string			$logo			Objet logo to show
	*	@param	array			$txtcolor		Text color
	**/
	function pdf_InfraSPlus_pagesmallhead(&$pdf, $object, $showaddress, $outputlangs, $title, $fromcompany, $formatpage, $decal_round, $logo, $txtcolor = array(0, 0, 0))
	{
		global $conf;

		$logosheader2			= getDolGlobalString('INFRASPLUS_PDF_LOGO_HEADER_2', '');
		$logosecondarysmallhead	= getDolGlobalInt('INFRASPLUS_PDF_LOGO_SECONDARY_SMALL_HEAD', 0);
		$logosmallheadheight	= getDolGlobalInt('INFRASPLUS_PDF_LOGO_SMALL_HEAD_HEIGHT', 6);
		$logo					= !empty($logosheader2) && !empty($logosecondarysmallhead) ? $logosheader2 : $logo;
		$default_font_size		= pdf_getPDFFontSize($outputlangs);
		$pdf->SetTextColor($txtcolor[0], $txtcolor[1], $txtcolor[2]);
		$pdf->SetFont('','', $default_font_size - 2);
		$posy					= $formatpage['mhaute'];
		$posx					= $formatpage['largeur'] - $formatpage['mdroite'] - 100 - $decal_round;
		// Logo
		$logodir				= !empty($conf->mycompany->multidir_output[$object->entity]) ? $conf->mycompany->multidir_output[$object->entity] : $conf->mycompany->dir_output;
		$logo					= !empty($logo) ? $logodir.'/logos/'.$logo : $logodir.'/logos/'.$fromcompany->logo;
		if (!empty($logo)) {
			if (is_file($logo) && is_readable($logo)) {
				$pdf->Image($logo, $formatpage['mgauche'], $posy, 0, $logosmallheadheight);	// width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0 ,0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 4, $outputlangs->transnoentities('PDFInfraSPlusLogoFileNotFound', $logo), '', 'L', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
				$pdf->MultiCell(100, 4, $outputlangs->transnoentities('ErrorGoToGlobalSetup'), '', 'L', 0, 1, $formatpage['mgauche'], $posy + 8, true, 0, 0, false, 0, 'M', false);
				$pdf->SetTextColor($txtcolor[0], $txtcolor[1], $txtcolor[2]);
			}
		} else {
			$text	= $fromcompany->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), '', 'L', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
		}
		$posy				+= 3;
		pdf_InfraSPlus_pagesrefdate($pdf, $object, $outputlangs, $title, $posy, $posx);
	}

	/**
	*	Function whitch returns vat statement (according to the seller, the buyer and the products present in the document)
	*	If the seller is in france and not subject to VAT => statement n° 1 => End of rule.
	*	If the seller is not subject to VAT => End of rule.
	*	If the seller and the buyer are from the same country => End of rule.
	*	If the seller and the buyer are from different countries from the EEC and there are services on the document => statement n° 2 => End of rule.
	*	If the seller is from the EEC but not the buyer and there are services on the document => statement n° 3 => End of rule.
	*	If the seller and the buyer are from different countries from the EEC and there are products on the document => statement n° 4 => End of rule.
	*	If the seller is from the EEC but not the buyer and there are products on the document => statement n° 5 => End of rule.
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	object		$seller			Object seller
	*	@param	object		$buyer			Object buyer
	*	@param	boolean		$hasService		there are services on the document
	*	@param	boolean		$hasProduct		there are products on the document
	*	@param	boolean		$show_tva_btp	we show the BTP mention
	*	@return array						0 = no mention or array of mention (keys are 'F' => franchise, 'S' => services, 'P' => products)
	**/
	function pdf_InfraSPlus_VAT_auto($object, $seller, $buyer, $hasService = 0, $hasProduct = 0, $show_tva_btp = 0)
	{
		$result			= array();
		$franchise		= ((is_numeric($seller->tva_assuj) && empty($seller->tva_assuj)) || (!is_numeric($seller->tva_assuj) && $seller->tva_assuj == 'franchise')) ? 1 : 0;
		$sellerCC		= $seller->country_code;
		$sellerInEEC	= isInEEC($seller);
		$buyerCC		= $buyer->country_code;
		$buyerInEEC		= isInEEC($buyer);
		// ($franchise && $sellerCC != 'FR') || $sellerCC == $buyerCC => nothing to do
		if ($sellerCC == 'FR' && !empty($franchise)) {
			$result['F']	= pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_1');
		} elseif (!empty($sellerInEEC) && !empty($buyerInEEC) && $sellerCC != $buyerCC) {
			$result['S']	= !empty($hasService) ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_2') : '';
			$result['P']	= !empty($hasProduct) ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_4') : '';
		} elseif (!empty($sellerInEEC) && empty($buyerInEEC)) {
			$result['S']	= !empty($hasService) ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_3') : '';
			$result['P']	= !empty($hasProduct) ? pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_5') : '';
		}
		if (!empty($show_tva_btp)) {
			$result['B']	= pdf_InfraSPlus_get_VAT_mention($object, 'INFRASPLUS_PDF_FREETEXT_TVA_6');
		}
		return (is_array($result) && count($result) > 0 ? $result : 0);
	}

	/**
	*	Search label on dictionary from constant key that contained the code
	*
	*	@param	object		$object		Object shown in PDF
	*	@param	string		$keyTVA		mention code to search on dictionary
	*	@return string					0 if Ko else label found on dictionary
	**/
	function pdf_InfraSPlus_get_VAT_mention($object, $keyTVA)
	{
		if ($object->element == 'propal') {
			$prefix	= 'PROPOSAL';
		} elseif ($object->element == 'commande') {
			$prefix	= 'ORDER';
		} elseif ($object->element == 'contrat') {
			$prefix	= 'CONTRACT';
		} elseif ($object->element == 'shipping') {
			$prefix	= 'SHIPPING';
		} elseif ($object->element == 'fichinter') {
			$prefix	= 'FICHINTER';
		} elseif ($object->element == 'facture') {
			$prefix	= 'INVOICE';
		} elseif ($object->element == 'supplier_proposal') {
			$prefix	= 'SUPPLIER_PROPOSAL';
		} elseif ($object->element == 'order_supplier') {
			$prefix	= 'SUPPLIER_ORDER';
		}
		$freeTTVA	= $prefix.'_FREE_TEXT_'.(getDolGlobalString($keyTVA, '') ? getDolGlobalString($keyTVA, '') : '');
		return getDolGlobalString($freeTTVA, '');
	}

	/**
	*	Search label on dictionary from constant key that contained the code
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	object			$object			Object shown in PDF
	*	@param	Translate		$outputlangs	Objet langs
	*	@param	string			$txtTVA			mention code to search on dictionary
	*	@param	int				$w				width
	*	@param	int				$hl				line height
	*	@param	int				$x				position x
	*	@param	int				$y				position y
	*	@return string							next y position
	**/
	function pdf_InfraSPlus_write_VAT_mention(&$pdf, $object, $outputlangs, $txtTVA, $w, $hl, $x, $y)
	{
		$txtTVA2	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $txtTVA);
		$pdf->writeHTMLCell($w, $hl, $x, $y, $txtTVA2, 0, 1, false, true, '', true);
		return $pdf->GetY();
	}

	/**
	*	Show free text
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	object			$object			Object shown in PDF
	*	@param	array			$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int				$posx			Position depart (largeur)
	*	@param	int				$posy			Position depart (hauteur)
	*	@param	Translate		$outputlangs	Objet langs
	*	@param	Societe			$fromcompany	Object company
	*	@param	array			$listfreetext	Root of constant names of free text
	*	@param	int				$withupline		Trace une ligne au-dessus du texte sur la largeur
	*	@param	int				$calculseul		Arrête la fonction au calcul de hauteur nécessaire
	*	@param	array			$LineStyle		PDF Line style
	*	@return	int							Return height of free text
	**/
	function pdf_InfraSPlus_free_text(&$pdf, $object, $formatpage, $posx, $posy, $outputlangs, $fromcompany, $listfreetext, $withupline = 0, $calculseul = 0, $LineStyle = null)
	{
		global $db, $conf;

		$pdf->startTransaction();
		$posy0			= $posy;
		$line			= '';
		if ($listfreetext != 'None' && is_array($listfreetext) && count($listfreetext) > 0) {
			foreach ($listfreetext as $freeT) {
				$freetext	= getDolGlobalString($freeT, '');
				// Line of free text
				if (!empty($freetext)) {
					$newfreetext	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $freetext);
					$line			.= $line ? '<br />'.$outputlangs->convToOutputCharset($newfreetext) : $outputlangs->convToOutputCharset($newfreetext);
				}
			}
		}
		if (!empty($line)) {	// Free text
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$pdf->SetFont('', '', $default_font_size - 2);
			if (!empty($withupline)) {
				$posy	+= 0.5;
				$pdf->Line($posx, $posy, $formatpage['largeur'] - $formatpage['mdroite'], $posy, $LineStyle);
			}
			$posy	+= 0.5;
			$pdf->writeHTMLCell(0, 3, $posx, $posy, dol_htmlentitiesbr($line), 0, 1);
			$posy	= $pdf->GetY() + 1;
		}
		if (!empty($calculseul)) {
			$heightforfreetext	= ($posy - $posy0);
			$pdf->rollbackTransaction(true);
			return $heightforfreetext;
		} else {
			$pdf->commitTransaction();
			return $posy;
		}
	}

	/**
	*	Show notes
	*
	*	@param		TCPDF		$pdf				The PDF factory
	*	@param		object		$object				Object shown in PDF
	*	@param		array		$listnotep			Root of constant names of standard public notes
	*	@param		Translate	$outputlangs		Object lang for output
	*	@param		array		$exftxtcolor		text color values (RGB)
	*	@param		int			$default_font_size	font size value
	*	@param		int			$tab_top			height for top page (header)
	*	@param		int			$larg_util_txt		note width
	*	@param		int			$tab_hl				line height
	*	@param		int			$posx_G_txt			x position for the notes
	*	@param		array		$horLineStyle		params for horizontale line style
	*	@param		int			$usedSpace			used space height
	*	@param		int			$page_hauteur		page width
	*	@param		int			$Rounded_rect		radius value for rounded corner
	*	@param		boolean		$showtblline		Show note frame
	*	@param		int			$marge_gauche		left margin
	*	@param		int			$larg_util_cadre	table width
	*	@param		array		$tblLineStyle		params for table line style
	*	@param		int			$typeNotes			type of notes to show :	-2 => Cover page
	*																		-1 => short (public notes + extrafields)
	*																		0 => standard (sales representative + public notes + extrafields)
	*																		1 => extended (sales representative + public notes + extrafields + serial number for equipement)
	*	@param		boolean		$firstpageempty		If we insert an empty page first we need to change the condition to check the pitch of the notes
	*	@return		int								Return height of notes
	**/
	function pdf_InfraSPlus_Notes(&$pdf, $object, $listnotep, $outputlangs, $exftxtcolor, $default_font_size, $tab_top, $larg_util_txt, $tab_hl, $posx_G_txt, $horLineStyle, $usedSpace, $page_hauteur, $Rounded_rect, $showtblline, $marge_gauche, $larg_util_cadre, $tblLineStyle, $typeNotes = 0, $firstpageempty = 0)
	{
		global $db;

		$show_sales_rep_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_FIRST_SALES_REP_IN_NOTE', 0);
		$ExfBulleted				= getDolGlobalInt('INFRASPLUS_PDF_EXF_BULLETED', 0);
		switch ($object->element) {
			case 'propal':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_D', 0);
			break;
			case 'commande':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_C', 0);
				$add_prj_dateo_in_notes		= getDolGlobalInt('INFRASPLUS_PDF_PRJ_DATEO_IN_NOTE', 0);
				if (($object->modelpdf == 'InfraSPlus_OF' || $object->modelpdf == 'InfraSPlus_OM') && $add_prj_dateo_in_notes) {
					$txtDateoPrj	= $outputlangs->transnoentities('PDFInfraSPlusDateoPrj').' : ';
					if (isModEnabled('projet') && !empty($object->fk_project)) {
						$proj			= new Project($db);
						$proj->fetch($object->fk_project);
						$txtDateoPrj	.= dol_print_date($proj->date_start, 'day', false, $outputlangs, true);
					}
				}
			break;
			case 'contrat':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_CT', 0);
			break;
			case 'fichinter':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_FI', 0);
				$lastNoteAsTable			= getDolGlobalInt('INFRASPLUS_PDF_LAST_NOTE_AS_TABLE', 0);
			break;
			case 'bom':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_B', 0);
			break;
			case 'mo':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_MRP', 0);
			break;
			case 'shipping':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_E', 0);
			break;
			case 'facture':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_F', 0);
			break;
			case 'supplier_proposal':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_DF', 0);
			break;
			case 'order_supplier':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_CF', 0);
			break;
			case 'invoice_supplier':
				$show_ExtraFields_in_notes	= getDolGlobalInt('INFRASPLUS_PDF_EXF_FF', 0);
			break;
			default:
				$show_ExtraFields_in_notes	= 0;
			break;
		}
		$height_note	= 0;
		$salesrep		= !empty($show_sales_rep_in_notes) && $typeNotes > -1 ? pdf_InfraSPlus_SalesRepInNotes($object, $outputlangs) : '';
		if ($listnotep != 'None' && is_array($listnotep) && count($listnotep) > 0) {
			$notesptoshow	= array();
			foreach ($listnotep as $noteP) {
				$notePub	= getDolGlobalString($noteP, '');
				if (!empty($notePub)) {
					$notePub		= pdf_InfraSPlus_formatNotes($object, $outputlangs, $notePub);
					$notesptoshow[]	= $notePub;	// to avoid printing empty notes
				}
			}
		}
		$notetoshow		= !empty($object->note_public) && empty($lastNoteAsTable) && $typeNotes > -2 ? pdf_InfraSPlus_formatNotes($object, $outputlangs, $object->note_public) : '';
		$extraDet		= !empty($show_ExtraFields_in_notes) && $typeNotes > -2 ? pdf_InfraSPlus_ExtraFieldsInNotes($object, $exftxtcolor, $outputlangs, $ExfBulleted) : '';
		$serialEquip	= $typeNotes > 0 ? pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, 0, 'intervention') : '';
		if (($typeNotes > -2 && (!empty($txtDateoPrj) || !empty($salesrep) || !empty($notesptoshow) || !empty($notetoshow) || !empty($extraDet) || !empty($serialEquip))) || !empty($notesptoshow)) {
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->startTransaction();
			$nexY	= $tab_top;
			if (!empty($firstpageempty)) {
				$pdf->line($posx_G_txt, $nexY + 1, $posx_G_txt + $larg_util_txt, $nexY + 1, $horLineStyle);
				$nexY	+= 2;
			}
			if (!empty($txtDateoPrj)) {
				$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $txtDateoPrj, $horLineStyle, ($salesrep || !empty($notesptoshow) || $notetoshow || $extraDet || $serialEquip ? 1 : 0));
			}
			if (!empty($salesrep)) {
				$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $salesrep, $horLineStyle, (!empty($notesptoshow) || $notetoshow || $extraDet || $serialEquip ? 1 : 0));
			}
			if (!empty($notesptoshow)) {
				foreach ($notesptoshow as $noteptoshow) {
					$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $noteptoshow, $horLineStyle, (next($notesptoshow) !== FALSE || $notetoshow || $extraDet || $serialEquip ? 1 : 0));
				}
			}
			if (!empty($notetoshow)) {
				$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $notetoshow, $horLineStyle, ($extraDet || $serialEquip ? 1 : 0));
			}
			if (!empty($extraDet)) {
				$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $extraDet, $horLineStyle, ($serialEquip ? 1 : 0));
			}
			if (!empty($serialEquip)) {
				$nexY	= pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $serialEquip, $horLineStyle, 0);
			}
			if ($pdf->getPage() > ($firstpageempty ? 2 : 1) || $pdf->GetY() > ($page_hauteur - (($tab_hl * 4) + $usedSpace))) {	// Notes need pagebreak or There is no space left for footer
				$pdf->rollbackTransaction(true);
				$pdf->writeHTMLCell($larg_util_txt, $tab_hl, $posx_G_txt, $tab_top, dol_htmlentitiesbr($outputlangs->transnoentities('PDFInfraSPlusNoteTooLong')), 0, 1);
			} else {
				$pdf->commitTransaction();
			}
			$nexY			= $pdf->GetY();
			$height_note	= $Rounded_rect * 2 > $nexY - $tab_top ? $Rounded_rect * 2 : $nexY - $tab_top;
			if (!empty($showtblline)) {
				$pdf->RoundedRect($marge_gauche, $tab_top - 1, $larg_util_cadre, $height_note + 2, $Rounded_rect, '1111', null, $tblLineStyle);
			}
			$height_note	+= $tab_hl * 1.5;
		}
		return $height_note;
	}

	/**
	*	Write notes
	*
	*	@param		TCPDF		$pdf				The PDF factory
	*	@param		int			$larg_util_txt		note width
	*	@param		int			$tab_hl				line height
	*	@param		int			$posx_G_txt			x position for the notes
	*	@param		int			$nexY				y position to start
	*	@param		string		$notes				notes to write
	*	@param		array		$horLineStyle		params for horizontale line style
	*	@param		boolean		$hasNextVal			Some more notes exist
	*	@return		int								Return Y value for the next position
	**/
	function pdf_InfraSPlus_writeNotes($pdf, $larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $notes, $horLineStyle, $hasNextVal = 0)
	{
		$pdf->writeHTMLCell($larg_util_txt, $tab_hl, $posx_G_txt, $nexY, $notes, 0, 1);
		$nexY	= $pdf->GetY();
		if (!empty($hasNextVal)) {
			$pdf->line($posx_G_txt + 30, $nexY + 2, $posx_G_txt + $larg_util_txt - 30 , $nexY + 2, $horLineStyle);
			$nexY	= $pdf->GetY() + 4;
		}
		return $nexY;
	}

	/**
	*	Get sales representative
	*
	*	@param		object		$object			Object shown in PDF
	*	@param		Translate	$outputlangs	Object lang for output
	*	@return		string						Return sales representative with details if found
	**/
	function pdf_InfraSPlus_SalesRepInNotes($object, $outputlangs)
	{
		global $db;

		$show_sales_rep_bold	= getDolGlobalInt('INFRASPLUS_PDF_FIRST_SALES_REP_BOLD', 0);
		$salesrep				= '';
		$arrayidcontact			= $object->getIdContact('internal', 'SALESREPFOLL');
		if (count($arrayidcontact) > 0) {
			$tmpuser	= new User($db);
			$tmpuser->fetch($arrayidcontact[0]);
			$salesrep	.= $outputlangs->transnoentities('CaseFollowedBy').' '.(!empty($show_sales_rep_bold) ? '<b>'.$tmpuser->getFullName($outputlangs).'</b>' : $tmpuser->getFullName($outputlangs));
			if (!empty($tmpuser->email)) {
				$salesrep	.= ', '.$outputlangs->transnoentities('Email').' : '.$outputlangs->convToOutputCharset($tmpuser->email);
			}
			if (!empty($tmpuser->office_phone)) {
				$salesrep	.= ', '.$outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($tmpuser->office_phone)));
			}
			if (!empty($tmpuser->user_mobile)) {
				$salesrep	.= ', '.$outputlangs->transnoentities('PhoneMobile').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($tmpuser->user_mobile)));
			}
		}
		return $salesrep;
	}

	/**
	*	Format notes with substitutions and right path for pictures
	*
	*	@param		object		$object			Object shown in PDF
	*	@param		Translate	$outputlangs	Object lang for output
	*	@param		string		$notes			html string from data base
	*	@return		string						Return html string ready to print
	**/
	function pdf_InfraSPlus_formatNotes($object, $outputlangs, $notes)
	{
		global $dolibarr_main_url_root;

		$substitutionarray	= pdf_getSubstitutionArray($outputlangs, null, $object);
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$html				= make_substitutions($notes, $substitutionarray, $outputlangs);
		// Clean variables not found
		$reg				= array();
		while (preg_match('/__(.+)_(.+)__/', $html, $reg)) {
			$html	= str_replace($reg[0], '', $html);
		}
		// the code below came from a Dolibarr v10 native function (convertBackOfficeMediasLinksToPublicLinks()) on functions2.lib.php
		$urlwithouturlroot	= preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));	// Define $urlwithroot
		$urlwithroot		= $urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
		$html				= preg_replace('/src="[a-zA-Z0-9_\/\-\.]*(viewimage\.php\?modulepart=medias[^"]*)"/', 'src="'.$urlwithroot.'/\1"', preg_replace('#amp;#', '', $html));
		return $html;
	}

	/**
	*	Get document extrafields
	*
	*	@param		object		$object			Object shown in PDF
	*	@param		array		$exftxtcolor	array with rgb color code
	*	@param		Translate	$outputlangs	Object lang for output
	*	@param		int			$bulleted		Print list as bulleted list
	*	@return		string						Return extrafields found
	**/
	function pdf_InfraSPlus_ExtraFieldsInNotes($object, $exftxtcolor, $outputlangs, $bulleted = 1)
	{
		global $db, $conf;

		$efPaySpec		= getDolGlobalString('INFRASPLUS_PDF_EXF_PAY_SPEC', '');
		$efPaySpec		= explode(',', preg_replace('/\s+/', '', $efPaySpec));	// string without any space to array
		$efDeposit		= getDolGlobalString('INFRASPLUS_PDF_EXF_DEPOSIT', '');
		$efDeposit		= explode(',', preg_replace('/\s+/', '', $efDeposit));	// string without any space to array
		$ef				= array_merge($efPaySpec, $efDeposit);
		$free_addr_livr	= getDolGlobalString('INFRASPLUS_PDF_FREE_LIVR_EXF', '');
		$listEF			= array();
		$extraDet		= '';
		$extrafields	= new ExtraFields($db);
		$extralabels	= $extrafields->fetch_name_optionals_label($object->table_element);
		$object->fetch_optionals();
		foreach ($extralabels as $key => $label) {
			$printable	= intval($extrafields->attributes[$object->table_element]['printable'][$key]);
			// check extrafield atribute printable (0 = no ; 1 = always ; 2 = if not empty) || key to avoid printing extra field for special payment || key to avoid printing extra field for delivery address
			if (empty($printable) || in_array($key, $ef) || (!empty($free_addr_livr) && $key == $free_addr_livr)) {
				continue;
			}
			$options_key	= $object->array_options['options_'.$key];
			$value			= pdf_InfraSPlus_formatNotes($object, $outputlangs, $extrafields->showOutputField($key, $options_key, '', $object->table_element));
			if ($printable == 1 || (!empty(dol_string_nohtmltag($value)) && $printable == 2)) {	// check if something is writting for this extrafield according to the extrafield management
				$EF			= new stdClass();
				$EF->rank	= intval($extrafields->attributes[$object->table_element]['pos'][$key]);
				$EF->label	= $outputlangs->trans($label);
				$EF->value	= $value;
				$EF->type	= $extrafields->attributes[$object->table_element]['type'][$key];
				$listEF[]	= $EF;
			}
		}
		if (!empty($listEF)) {
			uasort($listEF, function ($a, $b) { return	($a->rank > $b->rank) ? 1 : -1; });
			foreach ($listEF as $EF) {
				$value		= '<span style = "color: rgb('.$exftxtcolor[0].', '.$exftxtcolor[1].', '.$exftxtcolor[2].')">'.$EF->value.'</span>';
				$extraDet	.= !empty($bulleted) ? (empty($extraDet) ? '<ul><li>' : '<li>') : (!empty($extraDet) ? '<br/>' : '');
				$extraDet	.= $outputlangs->trans($EF->label).' : <b>'.$value.'</b>';
				$extraDet	.= !empty($bulleted) ? '</li>' : '';
			}
		}
		$extraDet	.= empty($extraDet) || empty($bulleted) ? '' : '</ul>';
		return $extraDet;
	}

	/**
	*	Get serial Number for equipement
	*
	*	@param		object		$object			Object shown in PDF
	*	@param		Translate	$outputlangs	Object lang for output
	*	@param		int			$i				Row number.
	*	@param		string		$typedoc		type of document asking.
	*	@return		string						Return equipement serial number
	**/
	function pdf_InfraSPlus_getEquipementSerialDesc($object, $outputlangs, $i, $typedoc = '')
	{
		global $db;

		$idprod	= (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
		$space	= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$retStr	= '';
		if (!empty($idprod) || $typedoc == 'intervention') {
			if ($typedoc == 'expedition') {
				$sql	= 'SELECT eq.ref';
				$sql	.= ' FROM '.$db->prefix().'equipement AS eq, '.$db->prefix().'equipementevt_equipement AS eqevteq, '.$db->prefix().'equipementevt_element AS eqevtel';
				$sql	.= ' WHERE eqevteq.fk_equipementevt = eqevtel.fk_equipementevt';
				$sql	.= ' AND eq.rowid = eqevteq.fk_equipement';
				$sql	.= ' AND eqevtel.fk_element = "'.$object->id.'"';
				$sql	.= ' AND eqevtel.elementtype = "shipping"';
				$sql	.= ' AND eq.fk_product = "'.$idprod.'"';
			} elseif ($typedoc == 'facture') {
				$sql	= 'SELECT eq.ref';
				$sql	.= ' FROM '.$db->prefix().'equipement AS eq';
				$sql	.= ' WHERE eq.fk_facture = "'.$object->id.'"';
				$sql	.= ' AND eq.fk_product = "'.$idprod.'"';
			} elseif ($typedoc == 'intervention') {
				$sql	= 'SELECT eq.ref, p.ref as refproduct';
				$sql	.= ' FROM '.$db->prefix().'equipement AS eq, '.$db->prefix().'equipementevt_equipement AS eqevteq, '.$db->prefix().'equipementevt_element AS eqevtel,'.$db->prefix().'product AS p';
				$sql	.= ' WHERE eqevteq.fk_equipementevt = eqevtel.fk_equipementevt';
				$sql	.= ' AND eq.rowid = eqevteq.fk_equipement';
				$sql	.= ' AND p.rowid = eq.fk_product';
				$sql	.= ' AND eqevtel.fk_element = "'.$object->id.'"';
				$sql	.= ' AND eqevtel.elementtype = "fichinter"';
				$sql	.= ' ORDER BY eq.fk_product';
			} else {
				return	$retStr;
			}
			$result	= $db->query($sql);
			if (!empty($result)) {
				$num = $db->num_rows($result);
				if ($num > 0) {
					$retStr	= $outputlangs->trans('PDFInfraSPlusSerialRef').' = '.($num > 1 ? '<br/>' : '');
					for ($i = 0; $i < $num; $i++) {
						$objp	= $db->fetch_object($result);
						if ($typedoc == 'intervention') {
							$retStr	.= ($num > 1 ? ($i == 0 ? $space : '<br/>'.$space) : '&nbsp;').$outputlangs->trans('PDFInfraSPlusFicheInterSerialNum', $objp->refproduct, $objp->ref);
						} else {
							$retStr	.= ($num > 1 ? ($i == 0 ? $space : '<br/>'.$space) : '&nbsp;').$objp->ref;
						}
					}
				}
			} else {
				$retStr	= '';
			}
		}
		return	$retStr;
	}

	/**
	*	Return line product ref for Intervention card
	*
	*	@param	object	$object		Object
	*	@param	int		$i			Current line number
	*	@return	array
	**/
	function pdf_infrasplus_getlinefichinter($object, $i)
	{
		global $db;

		$prodfichinter	= array();
		$sql	= 'SELECT fid.total_ht, fid.subprice, fid.fk_product, fid.tva_tx, fid.localtax1_tx, fid.localtax1_type, fid.localtax2_tx, fid.localtax2_type, fid.qty,';
		$sql	.= ' fid.remise_percent, fid.remise, fid.fk_remise_except, fid.price, fid.total_tva, fid.total_localtax1, fid.total_localtax2, fid.total_ttc,';
		$sql	.= ' fid.product_type, fid.info_bits, fid.buy_price_ht, fid.fk_product_fournisseur_price, p.ref, p.label';
		$sql	.= ' FROM '.$db->prefix().'fichinterdet AS fid';
		$sql	.= ' LEFT JOIN '.$db->prefix().'product AS p ON fid.fk_product = p.rowid';
		$sql	.= ' WHERE fid.fk_fichinter = '.$object->id.' AND fid.rowid = '.$object->lines[$i]->id;
		$resql	= $db->query($sql);
		if (!empty($resql)) {
			$num	= $db->num_rows($resql);
			$j		= 0;
			while ($j < $num) {
				$objp											= $db->fetch_object($resql);
				$prodfichinter['total_ht']						= $objp->total_ht;
				$prodfichinter['subprice']						= $objp->subprice;
				$prodfichinter['fk_product']					= $objp->fk_product;
				$prodfichinter['tva_tx']						= $objp->tva_tx;
				$prodfichinter['localtax1_tx']					= $objp->localtax1_tx;
				$prodfichinter['localtax1_type']				= $objp->localtax1_type;
				$prodfichinter['localtax2_tx']					= $objp->localtax2_tx;
				$prodfichinter['localtax2_type']				= $objp->localtax2_type;
				$prodfichinter['qty']							= $objp->qty;
				$prodfichinter['remise_percent']				= $objp->remise_percent;
				$prodfichinter['remise']						= $objp->remise;
				$prodfichinter['fk_remise_except']				= $objp->fk_remise_except;
				$prodfichinter['price']							= $objp->price;
				$prodfichinter['total_tva']						= $objp->total_tva;
				$prodfichinter['total_localtax1']				= $objp->total_localtax1;
				$prodfichinter['total_localtax2']				= $objp->total_localtax2;
				$prodfichinter['total_ttc']						= $objp->total_ttc;
				$prodfichinter['product_type']					= $objp->product_type;
				$prodfichinter['info_bits']						= $objp->info_bits;
				$prodfichinter['buy_price_ht']					= $objp->buy_price_ht;
				$prodfichinter['fk_product_fournisseur_price']	= $objp->fk_product_fournisseur_price;
				$prodfichinter['ref']							= $objp->ref;
				$prodfichinter['label']							= $objp->label;
				$j++;
			}
			$db->free($resql);
		}
		return $prodfichinter;
	}

	/**
	*	Return dimensions to use for images onto PDF,
	*	checking that width and height are not higher than maximum (20x32 by default).
	*
	*	@param	string		$realpath			Full path to photo file to use
	*	@param	int			$maxwidth			Maximum width to use
	*	@param	int			$maxheight			Maximum height to use
	*	@param	boolean		$wFirst				1 => Adjust to the width first | 2 => Force to the width (no matter the height result)
	*	@return	array							Height and width to use to output image (in pdf user unit, so mm)
	**/
	function pdf_InfraSPlus_getSizeForImage($realpath, $maxwidth, $maxheight, $wFirst = 0)
	{
		global $conf;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
		$imglinesize	= dol_getImageSize($realpath);
		if (!empty($imglinesize['height']) && !empty($imglinesize['width'])) {
			if (empty($wFirst)) {
				$width	= (int) round($maxheight * $imglinesize['width'] / $imglinesize['height']);	// I try to use maxheight
				if ($width > $maxwidth) {	// Pb with maxwidth, so i use maxheight
					$width	= $maxwidth;
					$height	= (int) round($maxwidth * $imglinesize['height'] / $imglinesize['width']);
				} else {
					$height	= $maxheight;	// No pb with maxwidth
				}
			} else if ($wFirst == 1) {
				$height	= (int) round($maxwidth * $imglinesize['height'] / $imglinesize['width']);	// I try to use maxwidth
				if ($height > $maxheight) {	// Pb with maxheight, so i use maxwidth
					$height	= $maxheight;
					$width	= (int) round($maxheight * $imglinesize['width'] / $imglinesize['height']);
				} else {
					$width	= $maxwidth;	// No pb with maxheight
				}
			} else if ($wFirst == 2) {
				$height		= (int) round($maxwidth * $imglinesize['height'] / $imglinesize['width']);	// Force to use maxwidth
				$width		= $maxwidth;
			}
			return array('width' => $width, 'height' => $height);
		}
		return array();
	}

	/**
	*	Return line url
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	int			$i				Current line number (0 = first line, 1 = second line, ...)
	*	@return string						url found in product/service data
	**/
	function pdf_InfraSPlus_getlineurl(&$object, $i)
	{
		global $db;

		$idprod		= (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
		$prodser	= new Product($db);
		$prodser->fetch($idprod);

		return $prodser->url;
	}

	/**
	*	Return line product ref
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@return	string
	**/
	function pdf_infrasplus_getlineref($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = array())
	{
		global $db, $hookmanager;

		$reshook		= 0;
		$result			= '';
		$idprod			= $prodfichinter ? $prodfichinter['fk_product'] : (!empty($object->lines[$i]->fk_product)	? $object->lines[$i]->fk_product	: false);
		$prodCustPrice	= getDolGlobalInt('PRODUIT_CUSTOMER_PRICES', 0);
		$prodCustRef	= getDolGlobalInt('PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE', 0);
		$prodser		= new Product($db);
		if (!empty($idprod)) {
			$prodser->fetch($idprod);
		}
		$ref_prodserv	= $prodser->ref; // Show local ref only
		// Prix par client actif
		if (!empty($prodCustPrice)) {
			$productCustomerPriceStatic	= new Productcustomerprice($db);
			$filter						= array('fk_product' => $idprod, 'fk_soc' => $object->socid);
			$nbCustomerPrices			= method_exists($productCustomerPriceStatic, 'fetchAll') ? $productCustomerPriceStatic->fetchAll('', '', 1, 0, $filter) : $productCustomerPriceStatic->fetchAll('', '', 1, 0, $filter);
			if ($nbCustomerPrices > 0) {	// Prix par client trouvé
				$productCustomerPrice	= $productCustomerPriceStatic->lines[0];
				if (!empty($productCustomerPrice->ref_customer)) {
					switch ($prodCustRef) {
						case 1:
							$ref_prodserv	= $productCustomerPrice->ref_customer;
						break;
						case 2:
							$ref_prodserv	= $productCustomerPrice->ref_customer.' ('.$outputlangs->transnoentitiesnoconv('InternalRef').' '.$prodser->ref.')';
						break;
						default:
							$ref_prodserv	= $prodser->ref.' ('.$outputlangs->transnoentitiesnoconv('RefCustomer').' '.$productCustomerPrice->ref_customer.')';
					}
				}
			}
		}
		// Gestion code barre
		if ($object->element != 'infrasloc_infraslocreturn') {
			$bold_num_col	= getDolGlobalInt('INFRASPLUS_PDF_BOLD_REF', 0);
			$with_gencode	= getDolGlobalInt('INFRASPLUS_PDF_REF_WITH_GENCODE', 0);
		} else	$bold_num_col	= getDolGlobalInt('INFRASLOC_PDF_RET_BOLD_REF', 0);	// pour module de location InfraS
		if ((!empty($with_gencode) && $i > -1) || ($object->element == 'reception' && empty($ref_prodserv))) {
			if (empty($ref_prodserv)) {
				$ref_prodserv	= $prodser->ref;
			}
		} else {
			$prodser	= $object;
		}
		// Hook standard Dolibarr
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineref', $parameters,$object, $action);	// Note that $action and $object may have been modified by some hooks
			$result		.= $hookmanager->resPrint;
		}
		// Impression
		if (empty($reshook)) {
			if (!empty($bold_num_col)) {
				$ref_prodserv	= '<b>'.$ref_prodserv.'</b>';
			}
			if (empty($hidedetails) || $hidedetails > 1) {
				$result			= dol_htmlentitiesbr($ref_prodserv.($prodser->barcode ? '<br/>'.$prodser->barcode : ''));
			}
		}
		return $result;
	}

	/**
	*	Return line ref_supplier
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0 = no, 1 = yes, 2 = just special lines)
	*	@return	string
	**/
	function pdf_InfraSPlus_getlineref_supplier($object, $i, $outputlangs, $hidedetails = 0)
	{
		global $db, $conf, $hookmanager;

		$prodRefSupp	= getDolGlobalInt('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES', 0);
		$bold_num_col	= getDolGlobalInt('INFRASPLUS_PDF_BOLD_REF', 0);
		$idprod			= !empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false;
		$ref_supplier	= (!empty($object->lines[$i]->ref_supplier) ? $object->lines[$i]->ref_supplier : (!empty($object->lines[$i]->ref_fourn) ? $object->lines[$i]->ref_fourn : ''));
		if (empty($ref_supplier)) {
			$ref	= $object->lines[$i]->ref;
			$sql	= 'SELECT pfp.ref_fourn ';
			$sql	.= 'FROM '.$db->prefix().'product AS p ';
			$sql	.= 'LEFT JOIN '.$db->prefix().'product_fournisseur_price AS pfp ON p.rowid = pfp.fk_product ';
			$sql	.= 'WHERE p.ref = "'.$ref.'" AND pfp.fk_soc = "'.$object->thirdparty->id.'"';
			$resql	= $db->query($sql);
			if (!empty($resql)) {
				$obj	= $db->fetch_object($resql);
				if (!empty($obj)) {
					$ref_supplier	= $obj->ref_fourn;
				}
			}
			$db->free($resql);
		}
		$prodser	= new ProductFournisseur($db);
		if (!empty($idprod)) {
			$prodser->fetch($idprod);
		}
		if ($prodRefSupp == 1) {
			$ref_prodserv	= $ref_supplier;
		} elseif ($prodRefSupp == 2) {
			$ref_prodserv	= (!empty($ref_supplier) ? $ref_supplier.' ' : '').($prodser->ref ? '('.$prodser->ref.')' : '');
		} else {	// Common case
			$ref_prodserv	= $prodser->ref; // Show local ref
			if (!empty($ref_supplier)) {
				$ref_prodserv	.= ($prodser->ref ? ' ' : '').'('.$ref_supplier.')';
			}
		}
		$reshook	= 0;
		$result		= '';
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineref_supplier', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			$result		.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (!empty($bold_num_col)) {
				$ref_prodserv	= '<b>'.$ref_prodserv.'</b>';
			}
			$result	.= dol_htmlentitiesbr($ref_prodserv);
		}
		return $result;
	}

	/**
	*	Output line product bar code
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	object			$object			Object
	*	@param	int				$i				Current line number or -1 if we want to print the object bar code instead of line object bar code
	*	@param	array			$bodytxtcolor	current text color
	*	@param	float			$posx			x position
	*	@param	float			$posy			y position
	*	@param	float			$width			bar code width
	*	@param	float			$height			bar code height (for 2D code such as Qr Code width = height and we change the x position to be on the middle of the width)
	*	@return	boolean							1 -> Ok ; <1 -> Ko
	**/
	function pdf_InfraSPlus_writelineBC(&$pdf, $object, $i, $bodytxtcolor, $posx, $posy, $width, $height)
	{
		global $db;

		$pos	= $i > -2 ? 'C' : '';
		$decal	= 0;
		if ($i > -1) {
			$idprod		= !empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false;
			$prodser	= new Product($db);
			if (!empty($idprod)) {
				$prodser->fetch($idprod);
			}
			$pos	= '';
			$decal	= ($width - $height) / 2;
		} else {
			$prodser	= $object;
		}
		if (!empty($prodser->barcode)) {
			// Complete object if not complete
			if (empty($prodser->barcode_type_code) || empty($prodser->barcode_type_coder)) {
				$result	= $prodser->fetch_barcode();
				if ($result < 1) {
					$BC	= '-- ErrorFetchBarcode -- '.$result;	//Check if fetch_barcode() failed
				}
			}
			if (empty($BC)) {
				if ($prodser->barcode_type <= 6) {
					if ($prodser->barcode_type == 2 && strlen($prodser->barcode) > 12) {
						// Check if the barecode format is the right one
						$ean	= substr($prodser->barcode, 0, 12);
						$even	= true;
						$esum	= 0;
						$osum	= 0;
						$ln		= strlen($ean) - 1;
						for ($i = $ln; $i >= 0; $i--) {
							if (!empty($even)) {
								$esum	+= $ean[$i];
							} else {
								$osum	+= $ean[$i];
							}
							$even	= !$even;
						}
						$eansum	= (10 - ((3 * $esum + $osum) % 10)) % 10;
						if (substr($prodser->barcode, -1) != $eansum) {
							$txtErr	= '<b><FONT size="7">'.$ean.'<FONT color="red">'.substr($prodser->barcode, -1).'</FONT><FONT color="green">('.$eansum.')</FONT></FONT></b>';
							$pdf->writeHTMLCell($width, $height, $posx, $posy, $txtErr, 0, 0, false, true, 'C');
							return 1;
						}
					}
					switch ($width) {
						case 25:
							$xres	= 0.2;
							break;
						case 35:
							$xres	= 0.3;
							break;
						case 45:
							$xres	= 0.4;
							break;
						default:
							$xres	= 0.2;
					}
					$styleBC	= array('position'		=> $pos,
										'align'			=> '',
										'stretch'		=> false,
										'fitwidth'		=> true,
										'cellfitalign'	=> 'C',
										'border'		=> false,
										'hpadding'		=> '0',
										'vpadding'		=> '0',
										'fgcolor'		=> array((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]),
										'bgcolor'		=> false,
										'text'			=> true,
										'label'			=> $prodser->barcode,
										'font'			=> $pdf->getFontFamily(),
										'fontsize'		=> 6,
										'stretchtext'	=> 4
										);
					$pdf->write1DBarcode($prodser->barcode, $prodser->barcode_type_code, $posx, $posy, $width, $height, $xres, $styleBC, 'B');
					return 1;
				}
				if ($prodser->barcode_type > 6) {
					$posx			+= $decal;
					$styleBC		= array('position'		=> $pos,
											'border'		=> false,
											'hpadding'		=> '0',
											'vpadding'		=> '0',
											'fgcolor'		=> array((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]),
											'bgcolor'		=> false,	// array(255,255,255)
											'module_width'	=> 1,		// width of a single module in points
											'module_height'	=> 1		// height of a single module in points
											);
					$pdf->write2DBarcode($prodser->barcode, $prodser->barcode_type_code, $posx, $posy, $height, $height, $styleBC, 'B');
					return 2;
				}
			} else {
				$pdf->writeHTMLCell(0, 0, $posx, $posy, $BC, 0, 1);
			}
		}
		return 0;
	}

	/**
	*	Get lines extrafields
	*
	*	@param		object		$line				Line shown in PDF
	*	@param		object		$extrafieldsline	Extra field line in document
	*	@param		array		$extralabelsline	Extra label line in document
	*	@param		array		$exfltxtcolor		array with rgb color code
	 *	@param		Translate	$outputlangs		Object langs for output
	*	@return		string							Return extrafields found
	**/
	function pdf_InfraSPlus_ExtraFieldsLines($line, $extrafieldsline, $extralabelsline, $exfltxtcolor, $outputlangs)
	{
		global $langs;

		$langs_saved	= $langs;
		$langs			= $outputlangs;
		$extraDet		= '';
		$line->fetch_optionals();
		foreach ($extralabelsline as $key => $label) {
			$lang_file	= $extrafieldsline->attributes[$line->table_element]['langfile'][$key];
			if (!empty($lang_file)) {
				$outputlangs->load($lang_file);
			}
			$printable	= intval($extrafieldsline->attributes[$line->table_element]['printable'][$key]);
			if (empty($printable)) {
				continue;	// check extrafield atribute printable (0 = no ; 1 or 3 = always ; 4 = if not empty)
			}
			$options_key	= $line->array_options['options_'.$key];
			$value			= $extrafieldsline->showOutputField($key, $options_key, '', $line->table_element);
			if (preg_match('#<img.*src=.*\/>#', $value)) {
				$value	= preg_replace('#src=\"\/viewimage.*modulepart=#', 'src="'.DOL_DATA_ROOT.'/', preg_replace('#&amp;entity=[0-9]*&amp;file=#', '/', $value));
			}
			if (in_array($printable, array(1, 3)) || (!empty($value) && $printable == 4)) {	// check if something is writting for this extrafield according to the extrafield management
				$value		= '<span style = "color: rgb('.$exfltxtcolor[0].', '.$exfltxtcolor[1].', '.$exfltxtcolor[2].')">'.$value.'</span>';
				$extraDet	.= (empty($extraDet) ? '' : '<br/>').$outputlangs->trans($label).' : <b>'.$value.'</b>';
			}
		}
		$langs	= $langs_saved;
		return $extraDet;
	}

	/**
	*	Get product extrafields
	*
	*	@param		object		$product			Line shown in PDF
	*	@param		array		$exfltxtcolor		array with rgb color code
	 *	@param		Translate	$outputlangs		Object langs for output
	*	@return		string							Return extrafields found
	**/
	function pdf_InfraSPlus_ExtraFieldsProd($product, $exfltxtcolor, $outputlangs)
	{
		global $db;

		$extraProd		= '';
		$extrafields	= new ExtraFields($db);
		$extralabels	= $extrafields->fetch_name_optionals_label($product->table_element);
		foreach ($extralabels as $key => $label) {
			$lang_file	= $extrafields->attributes[$product->table_element]['langfile'][$key];
			if (!empty($lang_file)) {
				$outputlangs->load($lang_file);
			}
			$printable	= intval($extrafields->attributes[$product->table_element]['printable'][$key]);
			if (empty($printable)) {
				continue;	// check extrafield atribute printable (0 = no ; 1 or 3 = always ; 4 = if not empty)
			}
			$options_key	= $product->array_options['options_'.$key];
			$value			= $extrafields->showOutputField($key, $options_key, '', $product->table_element);
			if (preg_match('#<img.*src=.*\/>#', $value)) {
				$value	= preg_replace('#src=\"\/viewimage.*modulepart=#', 'src="'.DOL_DATA_ROOT.'/', preg_replace('#&amp;entity=[0-9]*&amp;file=#', '/', $value));
			}
			if ($printable == 1 || (!empty($value) && $printable == 2)) {	// check if something is writting for this extrafield according to the extrafield management
				$value		= '<span style = "color: rgb('.$exfltxtcolor[0].', '.$exfltxtcolor[1].', '.$exfltxtcolor[2].')">'.$value.'</span>';
				$extraProd	.= (empty($extraProd) ? '' : '<br/>').$outputlangs->trans($label).' : <b>'.$value.'</b>';
			}
		}
		return $extraProd;
	}

	/**
	*	Return line weight volume dimensions and Customs code into array
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	int			$i				Current line number (0 = first line, 1 = second line, ...)
	*	@param	Translate	$outputlangs	Object langs for output
	*	@param	object		$emetteur		Object company
	*	@return string						html code with elements found
	**/
	function pdf_InfraSPlus_getlinewvdcc(&$object, $i, $outputlangs, $emetteur = '')
	{
		global $db;

		$outputlangs->load('other');

		$notForCustomerSameCountry	= getDolGlobalInt('INFRASPLUS_PDF_NO_SHOW_WVCC_SAME_COUNTRY', 0);
		if (!empty($notForCustomerSameCountry)) {
			$thirdparty		= !empty($object->thirdparty) ? $object->thirdparty : '';
			$sameCountry	= (!empty($emetteur->country_code) && !empty($thirdparty->country_code) && ($thirdparty->country_code == $emetteur->country_code)) ? 1 : 0;
			if (!empty($sameCountry)) {
				return '';
			}
		}
		if ($i === 'P') {
			$idprod	= $object->id;
			$type	= $object->fk_product_type;
		} else {
			$idprod	= (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
			$type	= $object->lines[$i]->product_type;
		}
		$prodser	= new Product($db);
		$dimtxt		= '';
		$weighttxt	= '';
		$voltxt		= '';
		$surftxt	= '';
		$ccodetxt	= '';
		$countrytxt	= '';
		if (!empty($idprod) && $type == 0) {
			$prodser->fetch($idprod);
			if (!empty($prodser->length) || !empty($prodser->width) || !empty($prodser->height)) {
				$txtDim	= '';
				$txtDim	.= ($prodser->length ? $outputlangs->trans('Length') : '');
				$txtDim	.= ($prodser->length && ($prodser->width || $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->width ? $outputlangs->trans('Width') : '');
				$txtDim	.= (($prodser->width && $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->height ? $outputlangs->trans('Height') : '');
				$dimtxt	= $txtDim.' : ';
				$txtDim	= ($prodser->length ? $prodser->length : '');
				$txtDim	.= ($prodser->length && ($prodser->width || $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->width ? $prodser->width : '');
				$txtDim	.= (($prodser->width && $prodser->height) ? ' x ' : '');
				$txtDim	.= ($prodser->height ? $prodser->height : '');
				$txtDim	.= ' '.(version_compare(DOL_VERSION, '10.0.0', '<') ? measuring_units_string($prodser->length_units, 'size') : measuringUnitString(7, 'size', $prodser->length_units));
				$dimtxt	.= $txtDim;
			}
			if (version_compare(DOL_VERSION, '10.0.0', '<')) {
				if (!empty($prodser->weight)) {
					$weighttxt	= $outputlangs->trans('Weight').' : '.$prodser->weight.' '.measuring_units_string($prodser->weight_units, 'weight');
				}
				if (!empty($prodser->volume)) {
					$voltxt		= $outputlangs->trans('Volume').' : '.$prodser->volume.' '.measuring_units_string($prodser->volume_units, 'volume');
				}
				if (!empty($prodser->surface)) {
					$surftxt	= $outputlangs->trans('Surface').' : '.$prodser->surface.' '.measuring_units_string($prodser->surface_units, 'surface');
				}
			} else {
				if (!empty($prodser->weight)) {
					$weighttxt	= $outputlangs->trans('Weight').' : '.$prodser->weight.' '.measuringUnitString(2, 'weight', $prodser->weight_units);
				}
				if (!empty($prodser->volume)) {
					$voltxt		= $outputlangs->trans('Volume').' : '.$prodser->volume.' '.measuringUnitString(19, 'volume', $prodser->volume_units);
				}
				if (!empty($prodser->surface)) {
					$surftxt	= $outputlangs->trans('Surface').' : '.$prodser->surface.' '.measuringUnitString(13, 'surface', $prodser->surface_units);
				}
			}
			if (!empty($prodser->customcode)) {
				$ccodetxt	= $outputlangs->trans('CustomCode').' : '.$prodser->customcode;
			}
			if (!empty($prodser->country_id)) {
				$countrytxt	= $outputlangs->trans('CountryOrigin').' : '.getCountry($prodser->country_id, 0, $db);
			}
		}
		$linewvdcc	= $dimtxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($weighttxt)	? '<br/>' : '').$weighttxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($voltxt)		? '<br/>' : '').$voltxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($surftxt)		? '<br/>' : '').$surftxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($ccodetxt)		? '<br/>' : '').$ccodetxt;
		$linewvdcc	.= (!empty($linewvdcc) && !empty($countrytxt)	? '<br/>' : '').$countrytxt;
		return $linewvdcc;
	}

	/**
	*	Return line comment
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	int			$i				Current line number (0 = first line, 1 = second line, ...)
	*	@param	Translate	$outputlangs	Object langs for output
	*	@return string						html code with elements found
	**/
	function pdf_InfraSPlus_getlinecomment(&$object, $i, $outputlangs)
	{
		return $object->element == 'reception' ? pdf_InfraSPlus_formatNotes($object, $outputlangs, $object->lines[$i]->comment) : '';
	}

	/**
	*	Output line description into PDF
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	object			$object				Object shown in PDF
	*	@param	int				$i					Current line number
	*	@param	Translate		$outputlangs		Object lang for output
	*	@param	array			$formatpage			Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	array			$LineStyle			params for table line style
	*	@param	int				$w					Width
	*	@param	int				$h					Height
	*	@param	int				$posx				Pos x
	*	@param	int				$posy				Pos y
	*	@param	int				$hideref			Hide reference
	*	@param	int				$hidedesc			Hide description
	*	@param	int				$issupplierline		Is it a line for a supplier object ?
	*	@param	string			$extraDet			HTML extra fields content
	*	@param	array			$prodfichinter		Product information when object is an intervention card
	*	@param	int				$desc_full_line		description width full line
	*	@param	int				$with_picture		picture on document
	*	@param	array			$realpatharray		List of image path classed by line row ID ($i)
	*	@param	array			$imglinesize		Image size => 'width', 'height'
	*	@param	string			$linkpictureurl		Public URL to show
	*	@param	int				$tab_hl				Line height
	*	@param	int				$ht_url				URL height
	*	@param	int				$picture_padding	value of space before and after the picture
	*	@param	string			$exfEcoTax			extrafield used for product ecotax
	*	@return	string
	**/
	function pdf_InfraSPlus_writelinedesc(&$pdf, $object, $i, $outputlangs, $formatpage, $LineStyle, $w, $h, $posx, $posy, $hideref = 0, $hidedesc = 0, $issupplierline = 0, $extraDet = '', $prodfichinter = null, $desc_full_line = 0, $isRecap = 0, $with_picture = 0, $realpatharray = array(), $imglinesize = array(), $linkpictureurl = '', $tab_hl = 4, $ht_url = 4, $picture_padding = 0, $exfEcoTax = '')
	{
		global $db, $hookmanager;

		$bodytxtcolor		= getDolGlobalString('INFRASPLUS_PDF_BODY_TEXT_COLOR', '0,0,0');
		$bodytxtcolor		= explode(',', $bodytxtcolor);
		$bodyColorSpan		= '<span style="color:rgb('.((int) $bodytxtcolor[0]).','.((int) $bodytxtcolor[1]).','.((int) $bodytxtcolor[2]).');">';
		$picture_in_ref		= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_IN_REF', 0);
		$cleanFont			= getDolGlobalInt('INFRASPLUS_PDF_DESC_CLEAN_FONT', 0);
		$descFullLineWitdh	= getDolGlobalInt('INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH', 0);
		$descFullLineColor	= getDolGlobalString('INFRASPLUS_PDF_DESC_FULL_LINE_COLOR', '0,0,0');
		$workBullet			= getDolGlobalString('INFRASPLUS_PDF_OUVRAGE_BULLET', '¤');
		$picture_under		= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_UNDER', 0);
		if (!empty($descFullLineColor)) {
			$LineStyle['color']	= explode(',', $descFullLineColor);
		}
		$reshook				= 0;
		$result					= '';
		$labelproductservice	= '';
		if (isModEnabled('subtotal')) {	// Ligne ATM
			$isATMLine	= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
			$isSubTitle	= $isATMLine && $object->lines[$i]->qty < 10 ? 1 : 0;	// Sous-titre ATM
			$isSubTotal	= $isATMLine && $object->lines[$i]->qty > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
			$isSubFreeT	= $isATMLine && $object->lines[$i]->qty == 50 ? 1 : 0;	// Ligne libre ATM
		} else {
			$isATMLine	= 0;
			$isSubTitle	= 0;
			$isSubTotal	= 0;
			$isSubFreeT	= 0;
		}
		$isOuvrage	= pdf_InfraSPlus_escapeEns ($object, $i, 2);	// Ouvrage Inovea
		if (isModEnabled('milestone')) {	// ligne Milestone - Jalon
			$isMilestoneLine	= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modMilestone');
			if (!empty($isMilestoneLine)) {
				$bodytxtsubticolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '0,0,0');
				$bodytxtsubticolor	= explode(',', $bodytxtsubticolor);
				$bodybgsubticolor	= getDolGlobalString('MILESTONE_BACKGROUND_COLOR', 'e6e6e6');
				$bodybgsubticolor	= colorStringToArray($bodybgsubticolor);
				$pdf->SetTextColor($bodytxtsubticolor[0], $bodytxtsubticolor[1], $bodytxtsubticolor[2]);	// Sous-titre Milestone/Jalon
				$frm				= implode(',', $bodybgsubticolor) == '255, 255, 255' ? '' : 'F';
				$frmstyle			= array('width'=>'0.2', 'dash'=>'0', 'cap'=>'butt', 'color'=>'255, 255, 255');
				$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h--, 0.001, '1111', $frm, $frmstyle, $bodybgsubticolor);
			}
		}
		if (is_object($hookmanager) && empty($isATMLine) && empty($isMilestoneLine)) {
			$special_code	= empty($object->lines[$i]->special_code) ? '' : $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('pdf'=>$pdf, 'i'=>$i, 'outputlangs'=>$outputlangs, 'w'=>$w, 'h'=>$h, 'posx'=>$posx, 'posy'=>$posy, 'hideref'=>$hideref, 'hidedesc'=>$hidedesc, 'issupplierline'=>$issupplierline, 'special_code'=>$special_code);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_writelinedesc', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				$labelproductservice	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			$fulllabel				= pdf_InfraSPlus_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline, $extraDet, $prodfichinter, $isSubTotal, $isSubTitle, $isSubFreeT, $isOuvrage);
			$labelproductservice	.= $fulllabel['main'];
			// ajout de la mention sur l'éco-particiation incluse
			if (!empty($exfEcoTax)) {
				$idprod	= $prodfichinter ? $prodfichinter['fk_product'] : (!empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
				if (!empty($idprod)) {
					$prodser	= new Product($db);
					$prodser->fetch($idprod);
					if (!empty($prodser->array_options['options_'.$exfEcoTax]) && $prodser->array_options['options_'.$exfEcoTax] > 0) {
						$labelproductservice	.= '<br/>'.$outputlangs->transnoentities('PDFInfraSPlusInclEcoTaxe', pdf_InfraSPlus_price($object, $prodser->array_options['options_'.$exfEcoTax], $outputlangs, 0, 0, 'U'));
					}
				}
			}
			$labelproductservice	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $labelproductservice);	// enable the use of an image in description
			// Fix bug of some HTML editors that replace links <img src="http://localhostgit/viewimage.php?modulepart=medias&file=image/efd.png" into <img src="http://localhostgit/viewimage.php?modulepart=medias&amp;file=image/efd.png"
			// We make the reverse, so PDF generation has the real URL.
			$labelproductservice	= preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $labelproductservice, -1, $nbrep);
			if (!empty($cleanFont)) {
				$labelproductservice	= dol_string_neverthesehtmltags($labelproductservice, $disallowed_tags = array('span'));
			}
			// Note: background-color CSS is no longer stripped here.
			// The TCPDF ColorFlag bug (text color lost on page breaks when background-color
			// matches text color) is now fixed via TCPDF_InfraS / TCPDI_InfraS subclasses
			// that force ColorFlag = true (see tcpdf_infrasplus.class.php).

			// Ligne ATM - Saut de page
			if (!empty($isATMLine) && $object->lines[$i]->info_bits > 0) {
				$pdf->addPage();
				$posy	= $pdf->GetY();
			}
			// Description
			// Open-DSI -- NEW full line description -- Begin
			if ((!empty($desc_full_line) || (!empty($with_picture) && !empty($picture_under))) && empty($isSubTotal) && empty($isSubTitle) && $isOuvrage < 2 && empty($isMilestoneLine) && $object->lines[$i]->info_bits == 0) {
				$pageposbefore			= $pdf->getPage();
				$xPos					= $picture_in_ref ? $posx : $formatpage['mgauche'];
				$wd						= $formatpage['largeur'] - $xPos - $formatpage['mdroite'];
				$decal					= $wd * ((1 - ($descFullLineWitdh / 100)) / 2);
				$labelproductservice	= (!empty($isSubTotalLine) && $object->lines[$i]->qty == 50 ? '<br />' : '').$labelproductservice;	// Free text of SubTotal
				$splitResult			= infraspackplus_splitLabelDescription($labelproductservice);
				$pos					= $splitResult !== false ? $splitResult['pos'] : false;
				$startdesc				= $splitResult !== false ? $splitResult['startdesc'] : 0;
				if ($pos !== false) {
					// Label
					$heightline			= $pdf->getStringHeight($w - $fulllabel['decal'], $outputlangs->convToOutputCharset(substr($labelproductservice, 0, $pos)));
					$fulllabel['decal']	+= !empty($fulllabel['decal']) ? pdf_InfraSPlus_write_bullet($pdf, $outputlangs, 0, $h, (!empty($desc_full_line) && $isSubFreeT ? $xPos : $posx) + $fulllabel['decal'], $posy, $workBullet, 0, 1, false, true, '', true) : 0;
					// Force grayscale FillColor ('g' format) which can never match RGB TextColor ('rg' format) => ColorFlag always true
					$pdf->SetFillColor(255);
					$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]);
					$pdf->writeHTMLCell((!empty($desc_full_line) && $isSubFreeT ? $wd : $w) - $fulllabel['decal'], $h, (!empty($desc_full_line) && $isSubFreeT ? $xPos : $posx) + $fulllabel['decal'], $posy, $bodyColorSpan.$outputlangs->convToOutputCharset(substr($labelproductservice, 0, $pos)).'</span>', 0, 1, false, true, 'L', true);
					$posy				= $picture_in_ref ? ($pageposbefore == $pdf->getPage()? $posy + $heightline : $pdf->GetY()) : $pdf->GetY();
					// Picture between label and description
					if (!empty($with_picture) && !empty($picture_under) && !empty($imglinesize['height']) && $imglinesize['height'] > 1) {
						$PictureY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $posx + $fulllabel['decal'], $posy + $picture_padding, $w - $fulllabel['decal'], $realpatharray, $imglinesize, $linkpictureurl, $tab_hl, $ht_url);
						$posy		= $pageposbefore == $pdf->getPage() ? $PictureY + $picture_padding : $pdf->GetY();
					}
					// Description
					if (empty($hidedesc) && !empty($descFullLineWitdh)) {
						$pdf->line($xPos + $decal, $posy + 1, $formatpage['largeur'] - $formatpage['mdroite'] - $decal, $posy + 1, $LineStyle);
					}
					$pdf->SetFillColor(255);
					$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]);
					$pdf->writeHTMLCell((!empty($desc_full_line) ? $wd : $w) - $fulllabel['decal'], $h, (!empty($desc_full_line) ? $xPos : $posx) + $fulllabel['decal'], $posy + 2, $bodyColorSpan.$outputlangs->convToOutputCharset(substr($labelproductservice, $startdesc)).'</span>', 0, 1, false, true, 'L', true);
				} else {
					// Picture between label and description
					if (!empty($with_picture) && !empty($picture_under) && !empty($imglinesize['height']) && $imglinesize['height'] > 1) {
						$PictureY	= pdf_InfraSPlus_writelineimg($pdf, $object, $i, $outputlangs, $posx + $fulllabel['decal'], $posy + $picture_padding, $w - $fulllabel['decal'], $realpatharray, $imglinesize, $linkpictureurl, $tab_hl, $ht_url);
						$posy		= $pageposbefore == $pdf->getPage() ? $PictureY + $picture_padding : $pdf->GetY();
					}
					$fulllabel['decal']	+= !empty($fulllabel['decal']) ? pdf_InfraSPlus_write_bullet($pdf, $outputlangs, 0, $h, $posx + $fulllabel['decal'], $posy, $workBullet, 0, 1, false, true, '', true) : 0;
					$pdf->SetFillColor(255);
					$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]);
					$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $bodyColorSpan.$outputlangs->convToOutputCharset($labelproductservice).'</span>', 0, 1, false, true, 'L', true);
				}
			} elseif (!empty($isSubTotal) || !empty($isSubTitle)) {	// ligne de sous-titre ou de sous-total ATM
				$bodysubticolor		= getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTI_COLOR', '220,220,220');
				$bodytxtsubticolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '0,0,0');
				$bodytxtsubticolor	= explode(',', $bodytxtsubticolor);
				$bodytxtsubtocolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', '0,0,0');
				$bodytxtsubtocolor	= explode(',', $bodytxtsubtocolor);
				$bodydescsubticolor	= getDolGlobalString('INFRASPLUS_PDF_DESC_SUBTO_COLOR', '0,0,0');
				$bodydescsubticolor	= explode(',', $bodydescsubticolor);
				$h					= $pdf->getStringHeight($w, $labelproductservice);
				$frmstyle			= array('width'=>'0.2', 'dash'=>'0', 'cap'=>'butt', 'color'=>'255, 255, 255');
				if (!empty($isSubTitle)) {	// Sous-titre ATM
					$style			= getDolGlobalString('SUBTOTAL_TITLE_STYLE', ($object->lines[$i]->qty == 1 ? 'BU' : 'BUI'));
					$bodybgsubcolor	= colorStringToArray($bodysubticolor);
					$frm			= implode(',', $bodybgsubcolor) == '255, 255, 255' ? '' : 'F';
					$pdf->SetTextColor($bodytxtsubticolor[0], $bodytxtsubticolor[1], $bodytxtsubticolor[2]);
					$pdf->SetFont('', $style);
					$tmpAlpha		= ($object->lines[$i]->qty - 1) * 0.25;
					$pdf->SetAlpha(1 - ($tmpAlpha >= 0 ? $tmpAlpha : 1));
					// desc_full_line: use full page width for subtitle
					$subTiX			= !empty($desc_full_line) ? $formatpage['mgauche'] : $posx;
					$subTiW			= !empty($desc_full_line) ? $formatpage['largeur'] - $formatpage['mgauche'] - $formatpage['mdroite'] : $w;
					if ($frm == 'F') {
						$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h, 1, '1111', $frm, $frmstyle, $bodybgsubcolor);
					}
					$pdf->SetAlpha(1);
					$pdf->writeHTMLCell($subTiW, $h, $subTiX, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'L', true);
					$pdf->SetTextColor($bodydescsubticolor[0], $bodydescsubticolor[1], $bodydescsubticolor[2]);
					$pdf->SetFont('', '', pdf_getPDFFontSize($outputlangs) - 1);	// On repositionne la police par defaut
					if (!empty($fulllabel['subdesc'])) {
						$pdf->writeHTMLCell($subTiW, $h, $subTiX, $posy + $h, $outputlangs->convToOutputCharset($fulllabel['subdesc']), 0, 1, false, true, 'L', true);
					}
					$pdf->SetFillColor(255);
					$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]);	// Restore default text color after subtitle
				} elseif (!empty($isSubTotal)) {	// Sous-total ATM
					$hideBg				= getDolGlobalInt('INFRASPLUS_PDF_HIDE_BODY_SUBTO', 0);
					$bgSubToColor		= getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR', '255,255,255');
					$bgSubToColorSubTi	= getDolGlobalInt('INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', 0);
					$subTotNewF			= getDolGlobalInt('SUBTOTAL_USE_NEW_FORMAT', 0);
					$style				= getDolGlobalString('SUBTOTAL_SUBTOTAL_STYLE', 'B');
					$txt				= $outputlangs->convToOutputCharset($labelproductservice);
					$txt				= $isRecap && !empty($subTotNewF) ? substr($txt, 0, strlen($txt) - 13) : $txt;
					$pdf->SetTextColor($bodytxtsubtocolor[0], $bodytxtsubtocolor[1], $bodytxtsubtocolor[2]);
					$pdf->SetFont('', $style);
					if (empty($hideBg)) {
						if (!empty($bgSubToColor) && $bgSubToColor != '255,255,255') {	// Personalized background color for subtotals
							$bodybgsubcolor	= explode(',', $bgSubToColor);
						} else {	// Coordinate the background color (highlighting) of subtotals with that of subtitles
							$bodybgsubcolor	= colorStringToArray((!empty($bgSubToColorSubTi) ? $bodysubticolor : ($object->lines[$i]->qty == 99 ? '220, 220, 220' : ($object->lines[$i]->qty == 98 ? '230, 230, 230' : '240, 240, 240'))));
						}
						$frm		= implode(',', $bodybgsubcolor) == '255,255,255' ? '' : 'F';
						$tmpAlpha	= (100 - $object->lines[$i]->qty - 1) * 0.25;
						$pdf->SetAlpha(!empty($bgSubToColorSubTi) ? 1 - ($tmpAlpha >= 0 ? $tmpAlpha : 1) : 1);
						if ($frm == 'F') {
							$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h, 1, '1111', $frm, $frmstyle, $bodybgsubcolor);
						}
						$pdf->SetAlpha(1);
					}
					$pdf->writeHTMLCell($w, $h, $posx, $posy, $txt, 0, 1, false, true, ($isRecap ? 'L' : 'R'), true);
					$pdf->SetFont('', '', pdf_getPDFFontSize($outputlangs) - 1);	// On repositionne la police par defaut
					$pdf->SetFillColor(255);
					$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]);	// Restore default text color after subtotal
				}
			} elseif ($isOuvrage > 1) {	// ligne d'ouvrage Inovea
				$pageposbefore	= $pdf->getPage();
				$bodyouvcolor	= getDolGlobalString('INFRASPLUS_PDF_BODY_OUV_COLOR', '220,220,220');
				$txtouvcolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_OUV_COLOR', '0,0,0');
				$txtouvcolor	= explode(',', $txtouvcolor);
				$txtouvstyle	= getDolGlobalString('INFRASPLUS_PDF_TEXT_OUV_STYLE', 'B');
				$descstylestd	= getDolGlobalInt('INFRASPLUS_PDF_DESC_OUV_STYLE_STD', 0);
				$frmstyle		= array('width'=>'0.2', 'dash'=>'0', 'cap'=>'butt', 'color'=>'255, 255, 255');
				$bodyouvcolor	= colorStringToArray($bodyouvcolor);
				$h				= $pdf->getStringHeight($w, $labelproductservice);
				$frm			= implode(',', $bodyouvcolor) == '255, 255, 255' ? '' : 'F';
				$pdf->SetTextColor($txtouvcolor[0], $txtouvcolor[1], $txtouvcolor[2]);
				$pdf->SetFont('', $txtouvstyle);
				if ($frm == 'F') {
					$pdf->RoundedRect($formatpage['mgauche'], $posy, $formatpage['largeur'] - $formatpage['mdroite'] - $formatpage['mgauche'], $h, 1, '1111', $frm, $frmstyle, $bodyouvcolor);
				}
				$fulllabel['decal']	+= !empty($fulllabel['decal']) ? pdf_InfraSPlus_write_bullet($pdf, $outputlangs, 0, $h, $posx + $fulllabel['decal'], $posy, $workBullet, 0, 1, false, true, '', true) : 0;
				$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'L', true);
				$pdf->SetFont('', '', pdf_getPDFFontSize($outputlangs) - 1);	// On repositionne la police par defaut
				$posy				= ($pageposbefore == $pdf->getPage()) ? $posy + $h : $pdf->GetY();
				if (!empty($descstylestd)) {
					$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]); // retour à la normal
				}
				if (!empty($fulllabel['subdesc'])) {
					$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $outputlangs->convToOutputCharset($fulllabel['subdesc']), 0, 1, false, true, 'L', true);
				}
			} else {
				$fulllabel['decal']	+= !empty($fulllabel['decal']) ? pdf_InfraSPlus_write_bullet($pdf, $outputlangs, 0, $h, $posx + $fulllabel['decal'], $posy, $workBullet, 0, 1, false, true, '', true) : 0;
				$pdf->SetFillColor(255);
				$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]);
				$pdf->writeHTMLCell($w - $fulllabel['decal'], $h, $posx + $fulllabel['decal'], $posy, $bodyColorSpan.$outputlangs->convToOutputCharset($labelproductservice).'</span>', 0, 1, false, true, 'L', true);
			}
			$result	.= $labelproductservice;
		}
		if ($object->lines[$i]->product_type == 9) {
			$pdf->SetTextColor((int) $bodytxtcolor[0], (int) $bodytxtcolor[1], (int) $bodytxtcolor[2]); // retour à la normal
		}
		return $result;
	}

	/**
	*	Print Bullet characters and return the width size printed
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	Translate		$outputlangs	Object langs for output
	*	@param	int				$w				Cell width. If 0, the cell extends up to the right margin
	*	@param	int				$h				Cell minimum height. The cell extends automatically if needed
	*	@param	int				$x				upper-left corner X coordinate
	*	@param	int				$y				upper-left corner Y coordinate
	*	@param	string			$html			html string to print
	*	@param	mixed			$border			if borders must be drawn around the cell (0 => no border, 1 => frame || L => left and/or T => top and/or R => right and/or B => bottom)
	*	@param	int				$ln				where the current position should go after the call (0 => to the right (or left for RTL language), 1 => to the beginning of the next line, 2 => below)
	*	@param	boolean			$fill			cell background must be painted (1) or transparent (0)
	*	@param	boolean			$reseth			reset the last cell height
	*	@param	string			$align			center or align the text (L => left, R => right, C => center, empty string => left for LTR or right for RTL)
	*	@param	boolean			$autopadding	uses internal padding and automatically adjust it to account for line width
	*	@return int								bullet width
	**/
	function pdf_InfraSPlus_write_bullet(&$pdf, $outputlangs, $w = 0, $h, $x, $y, $html = '', $border = 0, $ln = 1, $fill = false, $reseth = true, $align = '', $autopadding = true)
	{
		if ($html == -1) {
			return 6;
		}
		$pdf->writeHTMLCell($w, $h, $x, $y, $html, $border, $ln, $fill, $reseth, $align, $autopadding);
		$largBullet	= $pdf->GetStringWidth($html) + 4;
		return $largBullet;
	}

	/**
	*	Return line description translated in outputlangs and encoded into htmlentities and with <br>
	*
	*	@param	object		$object			Object shown in PDF
	*	@param	int			$i				Current line number (0 = first line, 1 = second line, ...)
	*	@param	Translate	$outputlangs	Object langs for output
	*	@param	int			$hideref		Hide reference
	*	@param	int			$hidedesc		Hide description
	*	@param	int			$issupplierline	Is it a line for a supplier object ?
	*	@param	string		$extraDet		HTML extra fields content
	*	@param	array		$prodfichinter	Product information when object is an intervention card
	*	@param	int			$isSubTotal		external module number if line is title or subtotal from external module like subTotal
	*	@return array						'main' => line description, 'subdesc' => sub description, 'decal' => decal value
	**/
	function pdf_InfraSPlus_getlinedesc(&$object, $i, $outputlangs, $hideref = 0, $hidedesc = 0, $issupplierline = 0, $extraDet = '', $prodfichinter = null, $isSubTotal = 0, $isSubTitle = 0, $isSubFreeT = 0, $isOuvrage = 0)
	{
		global $db, $conf, $langs;

		$idprod			= $prodfichinter ? $prodfichinter['fk_product'] : (!empty($object->lines[$i]->fk_product)	? $object->lines[$i]->fk_product	: false);
		$label			= $prodfichinter ? $prodfichinter['label']		: (!empty($object->lines[$i]->label)		? $object->lines[$i]->label			: (!empty($object->lines[$i]->product_label) ? $object->lines[$i]->product_label : ''));
		dol_syslog('infraspackplus.pdf.lib.php::pdf_InfraSPlus_getlinedesc $object->lines[$i]->label = '.$object->lines[$i]->label.' $object->lines[$i]->product_label = '.$object->lines[$i]->product_label);
		$desc			= (!empty($object->lines[$i]->desc) ? $object->lines[$i]->desc : (!empty($object->lines[$i]->description) ? $object->lines[$i]->description : ''));
		// For discount lines (info_bits & 2), when line's own label is empty, use line description as label instead of product label
		if (!empty($object->lines[$i]->info_bits) && ($object->lines[$i]->info_bits & 2) && empty($object->lines[$i]->label)) {
			$label		= $desc;
			$desc		= '';
		}
		$note			= (!empty($object->lines[$i]->note) ? $object->lines[$i]->note : '');
		$dbatch			= (!empty($object->lines[$i]->detail_batch) ? $object->lines[$i]->detail_batch : false);
		$subTotalNewF	= getDolGlobalInt('SUBTOTAL_USE_NEW_FORMAT', 0);
		$titleInSubT	= getDolGlobalInt('CONCAT_TITLE_LABEL_IN_SUBTOTAL_LABEL', 0);
		$isMultilangs	= getDolGlobalInt('MAIN_MULTILANGS', 0);
		$forceTranslate	= getDolGlobalInt('MAIN_MULTILANG_TRANSLATE_EVEN_IF_MODIFIED', 0);
		$hidelabel		= getDolGlobalInt('INFRASPLUS_PDF_HIDE_LABEL', 0);
		$labelbold		= getDolGlobalInt('INFRASPLUS_PDF_LABEL_BOLD', 0);
		$subref			= getDolGlobalInt('SHOW_SUBPRODUCT_REF_IN_PDF', 0);
		$extraDetPos2	= getDolGlobalInt('INFRASPLUS_PDF_EXTRADET_SECOND', 0);
		$depositDate	= getDolGlobalInt('INVOICE_ADD_DEPOSIT_DATE', 0);
		$descFirst		= getDolGlobalInt('MAIN_DOCUMENTS_DESCRIPTION_FIRST', 0);
		$hidelblvariant	= getDolGlobalInt('HIDE_LABEL_VARIANT_PDF', 0);
		$prodAddType	= getDolGlobalInt('PRODUCT_ADD_TYPE_IN_DOCUMENTS', 0);
		$prodHideRef	= getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_HIDE_REF', 0);
		$prodRefSupp	= getDolGlobalInt('PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES', 0);
		$prodCustPrice	= getDolGlobalInt('PRODUIT_CUSTOMER_PRICES', 0);
		$prodCustRef	= getDolGlobalInt('PRODUIT_CUSTOMER_PRICES_PDF_REF_MODE', 0);
		$HTMLinDesc		= getDolGlobalInt('PDF_BOLD_PRODUCT_REF_AND_PERIOD', getDolGlobalInt('ADD_HTML_FORMATING_INTO_DESC_DOC', 0));
		$CatInDesc		= getDolGlobalInt('CATEGORY_ADD_DESC_INTO_DOC', 0);
		$hideServDate	= getDolGlobalInt('INFRASPLUS_PDF_HIDE_SERVICE_DATES', 0);
		// cas particuliers des sous-titre, sous-totaux, textes libres ou ouvrages (ATM, Inovea)
		if (!empty($isSubTotal) || !empty($isSubTitle) || !empty($isSubFreeT) || $isOuvrage > 1 ) {
			if ($object->element == 'delivery' && !empty($object->commande->expeditions[$object->lines[$i]->fk_origin_line])) {
				unset($object->commande->expeditions[$object->lines[$i]->fk_origin_line]);
			}
			if (empty($label)) {
				$label	= $desc;
				$desc	= '';
			}
			// Gestion des variables de substitutions
			if (!empty($label)) {
				$label	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $label);
			}
			if (!empty($desc)) {
				$desc	= pdf_InfraSPlus_formatNotes($object, $outputlangs, $desc);
			}
			if (!empty($titleInSubT) && !empty($isSubTotal)) {
				$libelleproduitservice	= (infraspackplus_getTitle($object, $object->lines[$i], $isSubTotal) != '' ? infraspackplus_getTitle($object, $object->lines[$i], $isSubTotal).' : ' : '').$label;
			} else {
				$libelleproduitservice	= $label;
			}
			$decal	= empty($object->lines[$i]->fk_parent_line) ? 0 : 3;
			return array('main' => $libelleproduitservice, 'subdesc' => ((!empty($isSubTitle) || $isOuvrage > 1) ? $desc.pdf_InfraSPlus_formatNotes($object, $outputlangs, $extraDet) : ''), 'decal' => $decal);
		}
		// Ligne produit fournisseur
		if (!empty($issupplierline)) {
			$ref_supplier	= (!empty($object->lines[$i]->ref_supplier) ? $object->lines[$i]->ref_supplier : (!empty($object->lines[$i]->ref_fourn) ? $object->lines[$i]->ref_fourn : ''));
			if (empty($ref_supplier)) {
				$ref	= $object->lines[$i]->ref;
				$sql	= 'SELECT pfp.ref_fourn ';
				$sql	.= 'FROM '.$db->prefix().'product AS p ';
				$sql	.= 'LEFT JOIN '.$db->prefix().'product_fournisseur_price AS pfp ON p.rowid = pfp.fk_product ';
				$sql	.= 'WHERE p.ref = "'.$ref.'" AND pfp.fk_soc = "'.$object->thirdparty->id.'"';
				$resql	= $object->db->query($sql);
				if (!empty($resql)) {
					$obj	= $db->fetch_object($resql);
					if (!empty($obj)) {
						$ref_supplier	= $obj->ref_fourn;
					}
				}
				$db->free($resql);
			}
			$prodser	= new ProductFournisseur($db);
		} else {
			$prodser	= new Product($db);
		}
		if (!empty($idprod)) {
			$prodser->fetch($idprod);
			// If a predefined product and multilang and on other lang, we renamed label with label translated
			dol_syslog('infraspackplus.pdf.lib.php::pdf_InfraSPlus_getlinedesc $isMultilangs = '.$isMultilangs.' $outputlangs->defaultlang = '.$outputlangs->defaultlang.' $langs->defaultlang = '.$langs->defaultlang);
			if (!empty($isMultilangs) && ($outputlangs->defaultlang != $langs->defaultlang)) {
				$translatealsoifmodified	= (!empty($forceTranslate));	// By default if value was modified manually, we keep it (no translation because we don't have it)
				// TODO Instead of making a compare to see if param was modified, check that content contains reference translation. If yes, add the added part to the new translation
				// ($textwasnotmodified is replaced with $textwasmodifiedorcompleted and we add completion).
				// Set label
				// If we want another language, and if label is same than default language (we did force it to a specific value), we can use translation.
				$textwasnotmodified			= ($label == $prodser->label);
				if (!empty($prodser->multilangs[$outputlangs->defaultlang]['label']) && ($textwasnotmodified || $translatealsoifmodified)) {
					$label = $prodser->multilangs[$outputlangs->defaultlang]['label'];
				}
				// Set desc
				// Manage HTML entities description test because $prodser->description is store with htmlentities but $desc no
				$textwasnotmodified	= false;
				if (!empty($desc) && dol_textishtml($desc) && !empty($prodser->description) && dol_textishtml($prodser->description)) {
					$textwasnotmodified	= (strpos(dol_html_entity_decode($desc, ENT_QUOTES | ENT_HTML5), dol_html_entity_decode($prodser->description, ENT_QUOTES | ENT_HTML5)) !== false);
				} else {
					$textwasnotmodified	= ($desc == $prodser->description);
				}
				if (!empty($prodser->multilangs[$outputlangs->defaultlang]['description']) && ($textwasnotmodified || $translatealsoifmodified)) {
					$desc	= $prodser->multilangs[$outputlangs->defaultlang]['description'];
				}
				// Set note
				$textwasnotmodified	= ($note == $prodser->note_public);
				if (!empty($prodser->multilangs[$outputlangs->defaultlang]['other']) && ($textwasnotmodified || $translatealsoifmodified)) {
					$note	= $prodser->multilangs[$outputlangs->defaultlang]['other'];
				}
			}
		} elseif (($object->element == 'facture' || $object->element == 'facturefourn') && preg_match('/^\(DEPOSIT\).+/', $desc)) {
			$desc	= str_replace('(DEPOSIT)', $outputlangs->trans('Deposit'), $desc);
		}
		$libelleproduitservice																									= '';
		// Description short of product line
		if (empty($hidelabel)) {
			if (!empty($labelbold) && !empty($label)) {
				// Adding <b> may convert the original string into a HTML string. So we have to first convert \n into <br> for text is not already HTML.
				if (!dol_textishtml($libelleproduitservice)) {
					$libelleproduitservice	= str_replace("\n", '<br>', $libelleproduitservice);
				}
				$libelleproduitservice							.= '<b>'.$label.'</b>';
			} else	$libelleproduitservice	.= $label;
			// Add ref of subproducts
			if (!empty($subref)) {
				$prodser->get_sousproduits_arbo();
				if (!empty($prodser->sousprods) && is_array($prodser->sousprods) && count($prodser->sousprods)) {
					$tmparrayofsubproducts	= reset($prodser->sousprods);
					foreach ($tmparrayofsubproducts as $subprodval) {
						$libelleproduitservice .= '__N__ * '.$subprodval[5].(($subprodval[5] && $subprodval[3]) ? ' - ' : '').$subprodval[3].' ('.$subprodval[1].')';
					}
				}
			}
		}
		// Extra-details of product line
		if (!empty($extraDet)) {
			$libelleproduitservice	.= empty($extraDetPos2) ? $extraDet : '';
		}
		// Description long of product line
		if (!empty($desc) && ($desc != $label || !empty($hidelabel))) {
			if (!empty($libelleproduitservice) && empty($hidedesc)) {
				$libelleproduitservice	.= '__N__';
			}
			if ($desc == '(CREDIT_NOTE)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$sourceref				= !empty($discount->discount_type) ? $discount->ref_invoice_supplier_source : $discount->ref_facture_source;
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromCreditNote', $sourceref);
			} elseif ($desc == '(DEPOSIT)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$sourceref				= !empty($discount->discount_type) ? $discount->ref_invoice_supplier_source : $discount->ref_facture_source;
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromDeposit', $sourceref);
				// Add date of deposit
				if (!empty($depositDate)) {
					echo ' ('.dol_print_date($discount->datec, 'day', '', $outputlangs).')';
				}
			} elseif ($desc == '(EXCESS RECEIVED)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromExcessReceived', $discount->ref_facture_source);
			} elseif ($desc == '(EXCESS PAID)' && $object->lines[$i]->fk_remise_except) {
				$discount				= new DiscountAbsolute($db);
				$discount->fetch($object->lines[$i]->fk_remise_except);
				$libelleproduitservice	= $outputlangs->transnoentitiesnoconv('DiscountFromExcessPaid', $discount->ref_invoice_supplier_source);
			} else {
				if (!empty($idprod)) {
					// Check if description must be output for this kind of document
					if (!empty($object->element)) {
						$tmpkey	= 'MAIN_DOCUMENTS_HIDE_DESCRIPTION_FOR_'.strtoupper($object->element);
						if (getDolGlobalString($tmpkey, '')) {
							$hidedesc	= 1;
						}
					}
					if (empty($hidedesc)) {
						if (!empty($descFirst)) {
							$libelleproduitservice	= $desc.'__N__'.$libelleproduitservice;
						} else {
							if (!empty($hidelblvariant) && $prodser->isVariant()) {
								$libelleproduitservice	= $desc;
							} else {
								$libelleproduitservice	.= $desc;
							}
						}
					}
				} else {
					$libelleproduitservice	.= $desc;
				}
			}
		}
		if (!empty($extraDetPos2)) {
			$libelleproduitservice	.= !empty($extraDet) ? $extraDet : '';
		}
		// We add ref of product (and supplier ref if defined)
		$prefix_prodserv	= '';
		$ref_prodserv		= '';
		if (!empty($prodAddType)) {	// In standard mode, we do not show this
			$prefix_prodserv	= $outputlangs->transnoentitiesnoconv(!empty($prodser->isService()) ? 'Service' : 'Product').' ';
		}
		if (empty($hideref) && empty($prodHideRef)) {
			if (!empty($issupplierline)) {
				if (empty($prodRefSupp)) {	// Common case
					$ref_prodserv	= $prodser->ref; // Show local ref
					if (!empty($ref_supplier)) {
						$ref_prodserv	.= ($prodser->ref ? ' (' : '').$outputlangs->transnoentitiesnoconv('SupplierRef').' '.$ref_supplier.($prodser->ref ? ')' : '');
					}
				} elseif ($prodRefSupp == 1) {
					$ref_prodserv	= $ref_supplier;
				} elseif ($prodRefSupp == 2) {
					$ref_prodserv	= $ref_supplier.' ('.$outputlangs->transnoentitiesnoconv('InternalRef').' '.$prodser->ref.')';
				}
			} else {
				$ref_prodserv	= $prodser->ref; // Show local ref only
				if (!empty($prodCustPrice)) {
					$productCustomerPriceStatic	= new Productcustomerprice($db);
					$filter						= array('fk_product' => $idprod, 'fk_soc' => $object->socid);
					$nbCustomerPrices			= method_exists($productCustomerPriceStatic, 'fetchAll') ? $productCustomerPriceStatic->fetchAll('', '', 1, 0, $filter) : $productCustomerPriceStatic->fetch_all('', '', 1, 0, $filter);
					if ($nbCustomerPrices > 0) {
						$productCustomerPrice	= $productCustomerPriceStatic->lines[0];
						if (!empty($productCustomerPrice->ref_customer)) {
							switch ($prodCustRef) {
								case 1:
									$ref_prodserv	= $productCustomerPrice->ref_customer;
								break;
								case 2:
									$ref_prodserv	= $productCustomerPrice->ref_customer.' ('.$outputlangs->transnoentitiesnoconv('InternalRef').' '.$prodser->ref.')';
								break;
								default:
									$ref_prodserv	= $prodser->ref.' ('.$outputlangs->transnoentitiesnoconv('RefCustomer').' '.$productCustomerPrice->ref_customer.')';
							}
						}
					}
				}
			}
			if (!empty($libelleproduitservice) && !empty($ref_prodserv)) {
				$ref_prodserv	.= ' - ';
			}
		}
		if (!empty($ref_prodserv) && !empty($HTMLinDesc)) {
			$ref_prodserv	= '<b>'.$ref_prodserv.'</b>';
		}
		$libelleproduitservice	= $prefix_prodserv.$ref_prodserv.$libelleproduitservice;
		// Add an additional description for the category products
		if (!empty($CatInDesc) && $idprod && isModEnabled('categorie')) {
			include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$categstatic	= new Categorie($db);
			$tblcateg		= $categstatic->containing($idprod, Categorie::TYPE_PRODUCT);	// recovering the list of all the categories linked to product
			foreach($tblcateg as $cate) {
				$desccateg	= $cate->description;	// Adding the descriptions if they are filled
				if (!empty($desccateg)) {
					$libelleproduitservice	.= '__N__'.$desccateg;
				}
			}
		}
		if (empty($hideServDate)) {
			$period		= '';
			$period2	= '';
			// Show duration if exists
			if ($object->element == 'contrat') {
				if (!empty($object->lines[$i]->date_start)) {
					$period		.= $outputlangs->transnoentitiesnoconv('DateStartPlanned').' : '.dol_print_date($object->lines[$i]->date_start, 'day', false, $outputlangs);
				}
				if (!empty($object->lines[$i]->date_end)) {
					$period		.= ($period ? ' - ' : '').$outputlangs->transnoentitiesnoconv('DateEndPlanned').' : '.dol_print_date($object->lines[$i]->date_end, 'day', false, $outputlangs);
				}
				if (!empty($object->lines[$i]->date_ouverture)) {
					$period2	.= $outputlangs->transnoentitiesnoconv('DateStartReal').' : '.dol_print_date($object->lines[$i]->date_ouverture, 'day', false, $outputlangs);
				}
				if (!empty($object->lines[$i]->date_cloture)) {
					$period2	.= ($period ? ' - ' : '').$outputlangs->transnoentitiesnoconv('DateEndReal').' : '.dol_print_date($object->lines[$i]->date_cloture, 'day', false, $outputlangs);
				}
				$period	.= ($period && $period2 ? '__N__'.$period2 : $period2);
			} else {
				if (!empty($object->lines[$i]->date_start) && !empty($object->lines[$i]->date_end)) {
					$period	= '('.$outputlangs->transnoentitiesnoconv('DateFromTo', dol_print_date($object->lines[$i]->date_start, 'day', false, $outputlangs), dol_print_date($object->lines[$i]->date_end, 'day', false, $outputlangs)).')';
				}
				if (!empty($object->lines[$i]->date_start) && empty($object->lines[$i]->date_end)) {
					$period	= '('.$outputlangs->transnoentitiesnoconv('DateFrom', dol_print_date($object->lines[$i]->date_start, 'day', false, $outputlangs)).')';
				}
				if (empty($object->lines[$i]->date_start) && !empty($object->lines[$i]->date_end)) {
					$period = '('.$outputlangs->transnoentitiesnoconv('DateUntil', dol_print_date($object->lines[$i]->date_end, 'day', false, $outputlangs)).')';
				}
			}
			if (!empty($period)) {
				$period_html = $period;
				if (getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE', '') || getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_COLOR', '')) {
					$period_style = '';
					if (getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_COLOR', '')) {
						$period_style .= ' color: rgb('.getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_COLOR', '').');';
					}
					if (getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE', '')) {
						$period_style .= ' font-size: '.getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE', '').';';
					}
					$period_html = '<span' . (!empty($period_style) ? ' style="' . $period_style . '"' : '') . '>' . $period_html . '</span>';
				}
				$libelleproduitservice .= "__N__" . $period_html;
			}
		}
		if (!empty($dbatch)) {
			foreach($dbatch as $detail) {
				// Ne pas afficher le lot si la quantité est à 0
				if (empty($detail->qty) || $detail->qty == 0) {
					continue;
				}
				$dte=array();
				if (!empty($detail->eatby)) {
					$dte[]	= $outputlangs->transnoentitiesnoconv('printEatby', dol_print_date($detail->eatby, 'day', false, $outputlangs));
				}
				if (!empty($detail->sellby)) {
					$dte[]	= $outputlangs->transnoentitiesnoconv('printSellby', dol_print_date($detail->sellby, 'day', false, $outputlangs));
				}
				if (!empty($detail->batch)) {
					$dte[]	= $outputlangs->transnoentitiesnoconv('printBatch', $detail->batch);
				}
				$dte[]					= $outputlangs->transnoentitiesnoconv('printQty', $detail->qty);
				$libelleproduitservice	.= '__N__ '.implode(' - ', $dte);
			}
		}
		// Now we convert \n into br
		if (dol_textishtml($libelleproduitservice)) {
			$libelleproduitservice	= preg_replace('/__N__/', '<br>', $libelleproduitservice);
		} else {
			$libelleproduitservice	= preg_replace('/__N__/', "\n", $libelleproduitservice);
		}
		$libelleproduitservice	= dol_htmlentitiesbr($libelleproduitservice, 1);
		$decal					= empty($object->lines[$i]->fk_parent_line) ? 0 : 6;
		return array('main' => $libelleproduitservice, 'subdesc' => '', 'decal' => $decal);
	}

	/**
	*	Return dimensions to use for images onto PDF with a width limit (to fit column widt for example)
	*
	*	@param	int			$w			Width
	*	@param	string		$realpath	image path
	*	@return	array					Height and width to use to output image (in pdf user unit, so mm)
	**/
	function pdf_InfraSPlus_getlineimgsize($w, $realpath)
	{
		global $conf;

		$wpicture	= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_WIDTH', 20);
		$hpicture	= getDolGlobalInt('INFRASPLUS_PDF_PICTURE_HEIGHT', 32);
		if ($w - 2 < $wpicture) {
			$wpicture	= $w - 2;	// corrige la largeur maximal de l'image pour être au plus égale à la largeur colonne
		}
		$imglinesize	= array();
		if (!empty($realpath)) {
			$imglinesize	= pdf_InfraSPlus_getSizeForImage($realpath, $wpicture, $hpicture);
		}
		return $imglinesize;
	}

	/**
	*	Output product / service image into PDF
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	object			$object			Object shown in PDF
	*	@param	int				$i				Current line number
	*	@param	Translate		$outputlangs	Object lang for output
	*	@param	int				$posx			Pos x
	*	@param	int				$posy			Pos y
	*	@param	int				$w				Width
	*	@param	array			$realpatharray	List of image path classed by line row ID ($i)
	*	@param	array			$imglinesize	Image size => 'width', 'height'
	*	@param	string			$linkpictureurl	Public URL to show
	*	@param	int				$tab_hl			Line height
	*	@param	int				$ht_url			URL height
	*	@return	int								New pos y
	**/
	function pdf_InfraSPlus_writelineimg(&$pdf, $object, $i, $outputlangs, $posx, $posy, $w, $realpatharray, $imglinesize, $linkpictureurl, $tab_hl = 4, $ht_url = 4)
	{
		$lineurl	= pdf_InfraSPlus_getlineurl($object, $i);
		$linkurl	= !empty($lineurl) && !empty($linkpictureurl) ? '<a href = "'.$lineurl.'" target = "_blank">'.pdf_InfraSPlus_formatNotes($object, $outputlangs, $linkpictureurl).'</a>' : '&nbsp;';
		if (!empty($imglinesize['width']) && !empty($imglinesize['height'])) {
			$posxpicture	= $posx + (($w - $imglinesize['width']) / 2);	// centre l'image dans la colonne
			$pdf->Image($realpatharray[$i], $posxpicture, $posy, $imglinesize['width'], $imglinesize['height']);	// Use 300 dpi
			$pdf->writeHTMLCell($w, $tab_hl, $posxpicture, $posy + $imglinesize['height'] - ($linkurl == '&nbsp;' ? $tab_hl : 0), dol_htmlentitiesbr($linkurl), 0, 1);
			return $posy + $imglinesize['height'] - ($linkurl == '&nbsp;' ? $tab_hl : $ht_url * -1);
		} elseif	($linkurl != '&nbsp;' && $realpatharray[$i] != 'done') {
			$pdf->writeHTMLCell($w, $tab_hl, $posx, $posy, dol_htmlentitiesbr($linkurl), 0, 1);
			return $posy + $ht_url;
		} else {
			return $posy;
		}
	}

	/**
	*	Return line quantity
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@return	string
	**/
	function pdf_InfraSPlus_getlineqty($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null)
	{
		global $hookmanager;

		$result		= 0;
		$reshook	= 0;
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineqty', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint)) {
				$result	= (float) $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if ($object->lines[$i]->special_code == 3) {
				return '';
			}
			if (empty($hidedetails) || $hidedetails > 1) {
				$result	= $prodfichinter ? (float) $prodfichinter['qty'] : (float) $object->lines[$i]->qty;
			}
		}
		return $result;
	}

	/**
	*	Return line vat rate
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	object			$object				Object
	*	@param	int				$i					Current line number
	*	@param	Translate		$outputlangs		Object langs for output
	*	@param	int				$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array			$prodfichinter		intervention Line
	*	@param	boolean			$calcul				0 = return TVA with sign (to print) ; 1 = return value for calculation
	*	@return	string
	**/
	function pdf_InfraSPlus_getlinevatrate(&$pdf, $object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $calcul = 0)
	{
		global $conf, $hookmanager, $mysoc;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$result			= '';
		$reshook		= 0;
		$isSubTotalLine	= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
		$isATMLine		= isModEnabled('subtotal') && $isSubTotalLine ? true : false;
		$isSubTitle		= $isATMLine && $object->lines[$i]->qty < 10 ? 1 : 0;	// Sous-titre ATM
		$isSubTotal		= $isATMLine && $object->lines[$i]->qty > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
		if (!empty($isSubTitle)) {	// Sous-titre ATM
			$bodytxtsubticolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '0,0,0');
			$bodytxtsubticolor	= explode(',', $bodytxtsubticolor);
			$pdf->SetTextColor($bodytxtsubticolor[0], $bodytxtsubticolor[1], $bodytxtsubticolor[2]);
			$pdf->SetFont('', getDolGlobalString('SUBTOTAL_SUBTOTAL_STYLE', 'B'));
		} elseif (!empty($isSubTotal)) {	// Sous-total ATM
			$bodytxtsubtocolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', '0,0,0');
			$bodytxtsubtocolor	= explode(',', $bodytxtsubtocolor);
			$pdf->SetTextColor($bodytxtsubtocolor[0], $bodytxtsubtocolor[1], $bodytxtsubtocolor[2]);
			$pdf->SetFont('', getDolGlobalString('SUBTOTAL_SUBTOTAL_STYLE', 'B'));
		}
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlinevatrate',$parameters,$object,$action);	// Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				$result	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				$tva_tx		= $prodfichinter ? $prodfichinter['tva_tx'] : $object->lines[$i]->tva_tx;
				$info_bits	= $prodfichinter ? $prodfichinter['info_bits'] : $object->lines[$i]->info_bits;
				$tmpresult	= vatrate($tva_tx, 0, $info_bits, -1);
				if (!getDolGlobalString('MAIN_PDF_MAIN_HIDE_SECOND_TAX', '')) {
					$localtax1_tx	= $prodfichinter ? $prodfichinter['localtax1_tx'] : $object->lines[$i]->localtax1_tx;
					if (price2num($localtax1_tx)) {
						$tmpresult		.= (preg_replace('/[\s0%]/','',$tmpresult) ? '/' : '').vatrate(abs($localtax1_tx), 0);
					}
				}
				if (!getDolGlobalString('MAIN_PDF_MAIN_HIDE_THIRD_TAX', '')) {
					$localtax2_tx	= $prodfichinter ? $prodfichinter['localtax2_tx'] : $object->lines[$i]->localtax2_tx;
					if (price2num($localtax2_tx)) {
						$tmpresult		.= (preg_replace('/[\s0%]/','',$tmpresult) ? '/' : '').vatrate(abs($localtax2_tx), 0);
					}
				}
				$tmpresult	.= $calcul ? '' : '%';
				$result		.= $tmpresult;
			}
		}
		return $result;
	}

	/**
	*	Return line remise percent
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	*	@return	string
	**/
	function pdf_InfraSPlus_getlineremisepercent($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $conf, $hookmanager;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$showDiscOpt	= getDolGlobalInt('INFRASPLUS_PDF_SHOW_DISCOUNT_OPT', 0);
		$rounding		= min(getDolGlobalString('MAIN_MAX_DECIMALS_UNIT', ''), getDolGlobalString('MAIN_MAX_DECIMALS_TOT', ''));
		$reshook		= 0;
		$result			= '';
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i, 'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineremisepercent', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				$result	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			$remise_percent	= $prodfichinter ? $prodfichinter['remise_percent'] : $object->lines[$i]->remise_percent;
			$remise_percent	= empty($remise_percent) && !empty($pricesObjProd['remise']) ? $pricesObjProd['remise'] : $remise_percent;
			if (empty($hidedetails) || $hidedetails > 1) {
				$result	.= dol_print_reduction(round($remise_percent, $rounding), $outputlangs);
			}
		}
		return $result;
	}

	/**
	*	Return formated price
	*
	*	@param	object		$object			Object
	*	@param	float		$price			price to format
	*	@param	Translate	$outputlangs	Object langs for output
	*	@param	integer		$forceSymb		write currency symbol
	*	@param	integer		$local			force value in Dolibarr currency
	*	@param	string		$priceType		price type (U => unit price || T => total price)
	*	@return	string						formated price
	**/
	function pdf_InfraSPlus_price($object, $price, $outputlangs, $forceSymb = 0, $local = 0, $priceType = '')
	{
		global $conf;

		$roundingUP		= getDolGlobalInt('INFRASPLUS_PDF_ROUNDING_UP', 0);
		$roundingTot	= getDolGlobalInt('INFRASPLUS_PDF_ROUNDING_TOT', 0);
		$roundingDol	= min(getDolGlobalString('MAIN_MAX_DECIMALS_UNIT', ''), getDolGlobalString('MAIN_MAX_DECIMALS_TOT', ''));
		$rounding		= empty($priceType) || ($priceType == 'U' && empty($roundingUP)) || ($priceType == 'T' && empty($roundingTot)) ? $roundingDol : ($priceType == 'U' ? $roundingUP : ($priceType == 'T' ? $roundingTot : ''));
		$currency		= !empty($object->multicurrency_code) && empty($local) ? $object->multicurrency_code : $conf->currency;
		$showCurSymb	= !empty($forceSymb) ? 1 : getDolGlobalInt('INFRASPLUS_PDF_SHOW_CUR_SYMB', 0);
		return price($price, 0, $outputlangs, 1, $rounding, $rounding, (!empty($showCurSymb) ? $currency : ''));
	}
	/**
	*	Return line unit price excluding tax
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	*	@return	string							Line unit price excluding tax
	**/
	function pdf_InfraSPlus_getlineupexcltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$sign		= isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE', '') ? -1 : 1;
		$reshook	= 0;
		$result		= '';
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i,'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineupexcltax', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				$result	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$subprice	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_subprice != 0 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
					case 'fichinter':
						$subprice	= $prodfichinter ? $prodfichinter['subprice'] : 0;
					break;
					default:
						$subprice	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
				}
				$subprice	= !empty($pricesObjProd['pu_ht']) ? $pricesObjProd['pu_ht'] : $subprice;
				$result		.= pdf_InfraSPlus_price($object, $sign * $subprice, $outputlangs, 0, 0, 'U');
			}
		}
		return $result;
	}

	/**
	*	Return line unit price including tax
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	*	@return	string							Line unit price including tax
	**/
	function pdf_InfraSPlus_getlineupincltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$sign		= isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE', '') ? -1 : 1;
		$reshook	= 0;
		$result		= '';
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i,'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineupwithtax', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				$result	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$subprice	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_subprice != 0 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
					case 'fichinter':
						$subprice	= $prodfichinter ? $prodfichinter['subprice'] : 0;
					break;
					default:
						$subprice	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
					break;
				}
				$tva_tx		= $prodfichinter ? $prodfichinter['tva_tx'] : $object->lines[$i]->tva_tx;
				$ttcPrice	= !empty($pricesObjProd['pu_ttc']) ? $pricesObjProd['pu_ttc'] : ($subprice + ($subprice * $tva_tx / 100));
				$result		.= pdf_InfraSPlus_price($object, $sign * $ttcPrice, $outputlangs, 0, 0, 'U');
			}
		}
		return $result;
	}

	/**
	*	Return line unit price with discount and excluding tax
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	*	@return	string							Line unit price with discount and excluding tax
	**/
	function pdf_InfraSPlus_getlineincldiscountexcltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$sign			= isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE', '') ? -1 : 1;
		$reshook		= 0;
		$result			= '';
		$special_code	= $object->lines[$i]->special_code;
		if (is_object($hookmanager)) {
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i'=>$i,'outputlangs'=>$outputlangs, 'hidedetails'=>$hidedetails, 'special_code'=>$special_code, 'sign'=>$sign);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineupexcltax', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				$result	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				// special_code => 3 ==>> lignes d'options (quantité a 0)
				switch ($object->element) {
					case 'contrat':
						if ($special_code == 3) {
							$total_ht	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_subprice != 0 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
						} else {
							$total_ht	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_total_ht != 0 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
						}
					break;
					case 'fichinter':
						if ($special_code == 3) {
							$total_ht	= $subprice	= $prodfichinter ? $prodfichinter['subprice'] : 0;
						} else {
							$total_ht	= $prodfichinter ? $prodfichinter['total_ht'] : 0;
						}
					break;
					default:
						if ($special_code == 3) {
							if (!empty($pricesObjProd['remise'])) {
								$total_ht	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $pricesObjProd['multicurrency_pu_ht'] : $pricesObjProd['pu_ht'];
							} else {
								$total_ht	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
							}
						} else {
							$total_ht	= !isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
						}
					break;
				}
				if ($special_code == 3) {
					$remise_percent	= $prodfichinter ? $prodfichinter['remise_percent'] : $object->lines[$i]->remise_percent;
					$remise_percent	= empty($remise_percent) && !empty($pricesObjProd['remise']) ? $pricesObjProd['remise'] : $remise_percent;
					$total_ht		= !empty($remise_percent) ? $total_ht * (1 - ($remise_percent / 100)) : $total_ht;
				}
				$isSitFac	= !empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 ? $object->lines[$i]->situation_percent / 100 : 1;	// use this to find the real unit price on situation invoice
				$qty		= $prodfichinter ? $prodfichinter['qty'] : $object->lines[$i]->qty;
				$qty		= $qty == 0 ? 1 : $qty;
				$result		.= pdf_InfraSPlus_price($object, $sign * ($total_ht / $qty / $isSitFac), $outputlangs, 0, 0, 'U');
			}
		}
		return $result;
	}

	/**
	*	Return line unit price with discount and including tax
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@param	array		$prodfichinter		intervention Line
	*	@param	array		$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	*	@return	string							Line unit price with discount and including tax
	**/
	function pdf_InfraSPlus_getlineincldiscountincltax($object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$sign			= isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE', '') ? -1 : 1;
		$reshook		= 0;
		$result			= '';
		$special_code	= $object->lines[$i]->special_code;
		if (is_object($hookmanager)) {
			if (!empty($object->lines[$i]->fk_parent_line))	$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters										= array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code, 'sign' => $sign);
			$action											= '';
			$reshook										= $hookmanager->executeHooks('pdf_getlineupwithtax', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint))				$result			.= $hookmanager->resPrint;
		}
		if (empty($reshook)) {
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						if ($special_code == 3)	$subprice	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_subprice != 0 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
						else					$total_ttc	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_total_ttc != 0 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
					break;
					case 'fichinter':
						if ($special_code == 3)	$subprice	= $subprice	= $prodfichinter ? $prodfichinter['subprice'] : 0;
						else					$total_ttc	= $prodfichinter ? $prodfichinter['total_ttc'] : 0;
					break;
					default:
						if ($special_code == 3)	$subprice	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice;
						else					$total_ttc	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
					break;
				}
				if ($special_code == 3) {
					$tva_tx			= $prodfichinter ? $prodfichinter['tva_tx'] : $object->lines[$i]->tva_tx;
					$ttcPrice		= !empty($pricesObjProd['pu_ttc']) ? $pricesObjProd['pu_ttc'] : ($subprice + ($subprice * $tva_tx / 100));
					$remise_percent	= $prodfichinter												? $prodfichinter['remise_percent']	: $object->lines[$i]->remise_percent;
					$remise_percent	= empty($remise_percent) && !empty($pricesObjProd['remise'])	? $pricesObjProd['remise']			: $remise_percent;
					$total_ttc		= !empty($remise_percent) ? $ttcPrice * (1 - ($remise_percent / 100)) : $ttcPrice;
				}
				$isSitFac	= !empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 ? $object->lines[$i]->situation_percent / 100 : 1;	// use this to find the real unit price on situation invoice
				$qty		= $prodfichinter ? $prodfichinter['qty'] : $object->lines[$i]->qty;
				$qty		= $qty == 0 ? 1 : $qty;
				$result		.= pdf_InfraSPlus_price($object, $sign * ($total_ttc / $qty / $isSitFac), $outputlangs, 0, 0, 'U');
			}
		}
		return $result;
	}

	/**
	*	Return line percent
	*
	*	@param	object		$object				Object
	*	@param	int			$i					Current line number
	*	@param	Translate	$outputlangs		Object langs for output
	*	@param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@param	object		$hookmanager		Hook manager instance
	*	@return	string							Rounded percentage of line progress
	**/
	function pdf_InfraSPlus_getlineprogress($object, $i, $outputlangs, $hidedetails = 0, $hookmanager = null)
	{
		if (empty($hookmanager)) global $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$reshook	= 0;
		$result		= '';
		$rounding	= min(getDolGlobalString('MAIN_MAX_DECIMALS_UNIT', ''), getDolGlobalString('MAIN_MAX_DECIMALS_TOT', ''));
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code);
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlineprogress', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				return $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if ($object->lines[$i]->special_code == 3) {
				return '';
			}
			if (empty($hidedetails) || $hidedetails > 1) {
				if (getDolGlobalString('SITUATION_DISPLAY_DIFF_ON_PDF', '')) {
					$prev_progress	= 0;
					if (method_exists($object, 'get_prev_progress')) {
						$prev_progress	= $object->lines[$i]->get_prev_progress($object->id);
					}
					$result	= round(($object->lines[$i]->situation_percent - $prev_progress), $rounding).'%';
				} else {
					$result	= round($object->lines[$i]->situation_percent, $rounding).'%';
				}
			}
		}
		return $result;
	}

	/**
	*	Return total of line excluding tax
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	object			$object				Object
	*	@param	int				$i					Current line number
	*	@param	Translate		$outputlangs		Object langs for output
	*	@param	int				$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@param	array			$prodfichinter		intervention Line
	*	@param	array			$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	*	@return	string								Total of line excluding tax
	**/
	function pdf_InfraSPlus_getlinetotalexcltax(&$pdf, $object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $db, $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$sitFacTotLineAvt	= getDolGlobalInt('INFRASPLUS_PDF_SITFAC_TOTLINE_AVT', 0);
		$sign				= isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE', '') ? -1 : 1;
		$reshook			= 0;
		$result				= '';
		$isSubTotalLine		= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
		$isATMLine			= isModEnabled('subtotal') && $isSubTotalLine ? true : false;
		$isSubTotal			= $isATMLine && $object->lines[$i]->qty > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
		if (!empty($isSubTotal)) {	// Sous-total ATM
			$bodytxtsubtocolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', '0,0,0');
			$bodytxtsubtocolor	= explode(',', $bodytxtsubtocolor);
			$pdf->SetTextColor($bodytxtsubtocolor[0], $bodytxtsubtocolor[1], $bodytxtsubtocolor[2]);
			$pdf->SetFont('', getDolGlobalString('SUBTOTAL_SUBTOTAL_STYLE', 'B'));
		}
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code, 'sign' => $sign, 'infrasplus' => ($isSubTotal ? 1 : 0));
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlinetotalexcltax', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if(!empty($hookmanager->resPrint)) {
				$result	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if ($object->lines[$i]->special_code == 3) {
				return $outputlangs->transnoentities('Option');
			}
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$total_ht	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_total_ht != 0 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
					break;
					case 'fichinter':
						$total_ht	= $prodfichinter ? $prodfichinter['total_ht'] : 0;
					break;
					case 'shipping':
						$total_ht			= 0;
						$staticOrderLine	= new OrderLine($db);
						if ($staticOrderLine->fetch($object->lines[$i]->fk_origin_line) > 0 && !empty($staticOrderLine->qty)) {
							// On passe par le total HT de la ligne de commande / par sa quantité pour tenir compte des remises éventuelles
							$subprice	= (isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $staticOrderLine->multicurrency_total_ht : $staticOrderLine->total_ht) / $staticOrderLine->qty;
							$total_ht	=  $subprice * $object->lines[$i]->qty;	// On x le prix unitaire recalculé par la quantité livrée
						}
					break;
					default:
						$total_ht	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ht : $object->lines[$i]->total_ht;
						$total_ht	= !empty($pricesObjProd) ? (isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $pricesObjProd['multicurrency_total_ht'] : $pricesObjProd['total_ht']) : $total_ht;
					break;
				}
				if (!empty($object->lines[$i]->situation_percent) && $object->lines[$i]->situation_percent > 0 && empty($sitFacTotLineAvt)) {
					$prev_progress	= 0;
					$progress		= 1;
					if (method_exists($object->lines[$i], 'get_prev_progress')) {
						$prev_progress	= $object->lines[$i]->get_prev_progress($object->id);
						$progress		= ($object->lines[$i]->situation_percent - $prev_progress) / 100;
					}
					$result	.= pdf_InfraSPlus_price($object, $sign * ($total_ht / ($object->lines[$i]->situation_percent / 100)) * $progress, $outputlangs, 0, 0, 'T');
				} else {
					$result	.= pdf_InfraSPlus_price($object, $sign * $total_ht, $outputlangs, 0, 0, 'T');
				}
			}
		}
		return $result;
	}

	/**
	*	Return total of line including tax
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	object			$object				Object
	*	@param	int				$i					Current line number
	*	@param	Translate		$outputlangs		Object langs for output
	*	@param	int				$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
	*	@param	array			$pricesObjProd		price datas from product (need if we use customer prices for product and automatic discount)
	*	@param	array			$prodfichinter		intervention Line
	*	@return	string								Total of line including tax
	**/
	function pdf_InfraSPlus_getlinetotalincltax(&$pdf, $object, $i, $outputlangs, $hidedetails = 0, $prodfichinter = null, $pricesObjProd = array())
	{
		global $hookmanager;

		if (!empty(pdf_InfraSPlus_escapeEns($object, $i))) {
			return '';
		}
		$sitFacTotLineAvt	= getDolGlobalInt('INFRASPLUS_PDF_SITFAC_TOTLINE_AVT', 0);
		$sign				= isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE', '') ? -1 : 1;
		$reshook			= 0;
		$result				= '';
		$isSubTotalLine		= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
		$isATMLine			= isModEnabled('subtotal') && $isSubTotalLine ? true : false;
		$isSubTotal			= $isATMLine && $object->lines[$i]->qty > 90 ? infraspackplus_get_mod_number('modSubtotal') : 0;	// Sous-total ATM
		if (!empty($isSubTotal)) {	// Sous-total ATM
			$bodytxtsubtocolor	= getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', '0,0,0');
			$bodytxtsubtocolor	= explode(',', $bodytxtsubtocolor);
			$pdf->SetTextColor($bodytxtsubtocolor[0], $bodytxtsubtocolor[1], $bodytxtsubtocolor[2]);
			$pdf->SetFont('', getDolGlobalString('SUBTOTAL_SUBTOTAL_STYLE', 'B'));
		}
		if (is_object($hookmanager)) {
			$special_code	= $object->lines[$i]->special_code;
			if (!empty($object->lines[$i]->fk_parent_line)) {
				$special_code	= $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			}
			$parameters	= array('i' => $i, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'special_code' => $special_code, 'sign' => $sign, 'infrasplus' => ($isSubTotal ? 1 : 0));
			$action		= '';
			$reshook	= $hookmanager->executeHooks('pdf_getlinetotalwithtax', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks
			if (!empty($hookmanager->resPrint)) {
				$result	.= $hookmanager->resPrint;
			}
		}
		if (empty($reshook)) {
			if ($object->lines[$i]->special_code == 3) {
				return $outputlangs->transnoentities('Option');
			}
			if (empty($hidedetails) || $hidedetails > 1) {
				switch ($object->element) {
					case 'contrat':
						$total_ttc	= isModEnabled('multicurrency') && $object->lines[$i]->multicurrency_total_ttc != 0 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
					break;
					case 'fichinter':
						$total_ttc	= $prodfichinter ? $prodfichinter['total_ttc'] : 0;
					break;
					default:
						$total_ttc	= isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_total_ttc : $object->lines[$i]->total_ttc;
						$total_ttc	= !empty($pricesObjProd) ? (isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $pricesObjProd['multicurrency_total_ttc'] : $pricesObjProd['total_ttc']) : $total_ttc;
					break;
				}
				if ($object->lines[$i]->situation_percent > 0 && empty($sitFacTotLineAvt)) {
					$prev_progress	= 0;
					$progress		= 1;
					if (method_exists($object->lines[$i], 'get_prev_progress')) {
						$prev_progress	= $object->lines[$i]->get_prev_progress($object->id);
						$progress		= ($object->lines[$i]->situation_percent - $prev_progress) / 100;
					}
					$result	.= pdf_InfraSPlus_price($object, $sign * ($total_ttc / ($object->lines[$i]->situation_percent / 100)) * $progress, $outputlangs, 0, 0, 'T');
				} else {
					$result	.= pdf_InfraSPlus_price($object, $sign * $total_ttc, $outputlangs, 0, 0, 'T');
				}
			}
		}
		return $result;
	}

	/**
	*	Return line product ref for Intervention card
	*
	*	@param	object	$object		Object
	*	@return	array
	**/
	function pdf_infrasplus_getpricefichinter($object)
	{
		global $db;

		$pricefichinter	= array();
		$sql	= 'SELECT fi.total_ht, fi.total_ttc, fi.total_tva, fi.total_localtax1, fi.total_localtax2';
		$sql	.= ' FROM '.$db->prefix().'fichinter AS fi';
		$sql	.= ' WHERE fi.rowid = '.$object->id;
		$resql	= $db->query($sql);
		if (!empty($resql)) {
			$num	= $db->num_rows($resql);
			for ($j = 0; $j < $num; $j++) {
				$objp								= $db->fetch_object($resql);
				$pricefichinter['total_ht']			= $objp->total_ht;
				$pricefichinter['total_ttc']		= $objp->total_ttc;
				$prodfichinter['total_tva']			= $objp->total_tva;
				$prodfichinter['total_localtax1']	= $objp->total_localtax1;
				$prodfichinter['total_localtax2']	= $objp->total_localtax2;
			}
			$db->free($resql);
		}
		return $pricefichinter;
	}

	/**
	*	Show bank informations for PDF generation
	*
	*	@param	TCPDF|TCPDI		$pdf				The PDF factory
	*	@param	Translate		$outputlangs		Object lang for output
	*	@param	int				$posx				X position
	*	@param	int				$posy				Y position
	*	@param	int				$larg				Block Width
	*	@param	int				$hl					Line height
	*	@param	object			$account			Bank account object
	*	@param	int				$onlynumber			Output only number (bank+desk+key+number according to country, but without name of bank and domiciliation)
	*	@param	int				$default_font_size	Default font size
	*	@return	float								The Y PDF position
	**/
	function pdf_infrasplus_bank(&$pdf, $outputlangs, $posx, $posy, $larg = 100, $hl = 4, $account, $onlynumber = 0, $default_font_size = 10)
	{
		global $conf;

		$outputlangs->load('banks');

		$diffsizetitle		= getDolGlobalInt('PDF_DIFFSIZE_TITLE', 3);
		$diffsizecontent	= getDolGlobalInt('PDF_DIFFSIZE_CONTENT', 4);
		$only_BIC_IBAN		= getDolGlobalInt('PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN', 0);
		if (empty($onlynumber)) {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').' : ', 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $hl;
		}
		$bickey				= $account->getCountryCode() == 'EN' ? 'SWIFT' : 'BICNumber';	// Use correct name of bank id according to country
		$usedetailedbban	= $account->useDetailedBBAN();	// Get format of bank account according to its country
		if (!empty($usedetailedbban)) {
			$savposx	= $posx;
			if (empty($onlynumber)) {
				$pdf->SetFont('', '', $default_font_size - $diffsizecontent);
				$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('Bank').' : '.$outputlangs->convToOutputCharset($account->bank), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
				$posy	+= $hl;
			}
			if (empty($only_BIC_IBAN)) {	// Note that some countries still need bank number, BIC/IBAN not enougth for them
				// Note:
				// bank = code_banque (FR), sort code (GB, IR. Example: 12-34-56)
				// desk = code guichet (FR), used only when $usedetailedbban = 1
				// number = account number
				// key = check control key used only when $usedetailedbban = 1
				if (empty($onlynumber)) {
					$pdf->line($posx, $posy, $posx, $posy + (($hl - 1) * 2));
				}
				foreach ($account->getFieldsToShow() as $val) {
					if ($val == 'BankCode') {
						$tmplength	= $larg / 6;
						$content	= $account->code_banque;
					} elseif ($val == 'DeskCode') {
						$tmplength	= $larg / 6;
						$content	= $account->code_guichet;
					} elseif ($val == 'BankAccountNumber') {
						$tmplength	= $larg / 4;
						$content	= $account->number;
					} elseif ($val == 'BankAccountNumberKey') {
						$tmplength	= $larg / 12;
						$content	= $account->cle_rib;
					} elseif ($val == 'IBAN' || $val == 'BIC') {
						$tmplength	= 0;
						$content	= '';
					} else {
						dol_print_error($account->db, 'Unexpected value for getFieldsToShow: '.$val);
						break;
					}
					$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
					$pdf->MultiCell($tmplength, $hl, $outputlangs->transnoentities($val), 0, 'C', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
					$pdf->SetFont('', '', $default_font_size - $diffsizecontent);
					$pdf->MultiCell($tmplength, $hl, $outputlangs->convToOutputCharset($content), 0, 'C', false, 1, $posx, $posy + ($hl * 2) - 2, true, 0, false, false, 0, 'M', false);
					// Open-DSI -- FIX vertical align in PDF models -- End
					$posx	+= $tmplength;
					if (empty($onlynumber)) {
						$pdf->line($posx, $posy, $posx, $posy + ((($hl * 2) - 2) * 2));
					}
				}
				$posx	= $savposx;
				$posy	+= (($hl * 2) * 2) + 1;
			}
		} else {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('Bank').' : '.$outputlangs->convToOutputCharset($account->bank), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $hl;
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities('BankAccountNumber').' : '.$outputlangs->convToOutputCharset($account->number), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $hl;
			if ($diffsizecontent <= 2) {
				$posy += 1;
			}
		}
		$pdf->SetFont('', '', $default_font_size - $diffsizecontent);
		if (empty($onlynumber) && !empty($account->domiciliation)) {
			$val	= $outputlangs->transnoentities('Residence').' : '.$outputlangs->convToOutputCharset($account->domiciliation);
			$pdf->MultiCell($larg, $hl, $val, 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $pdf->getStringHeight($larg, $val);
		}
		if (empty($onlynumber) && !empty($account->proprio)) {
			$val	= $outputlangs->transnoentities('BankAccountOwner').' : '.$outputlangs->convToOutputCharset($account->proprio);
			$pdf->MultiCell($larg, $hl, $val, 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $pdf->getStringHeight($larg, $val);
		} elseif (empty($usedetailedbban)) {
			$posy	+= 1;
		}
		$ibankey	= FormBank::getIBANLabel($account);	// Use correct name of bank id according to country
		if (!empty($account->iban)) {
			//Remove whitespaces to ensure we are dealing with the format we expect
			$ibanDisplay_temp	= str_replace(' ', '', $outputlangs->convToOutputCharset($account->iban));
			$ibanDisplay		= '';
			$nbIbanDisplay_temp	= dol_strlen($ibanDisplay_temp);
			for ($i = 0; $i < $nbIbanDisplay_temp; $i++) {
				$ibanDisplay	.= $ibanDisplay_temp[$i];
				if ($i % 4 == 3 && $i > 0) {
					$ibanDisplay	.= ' ';
				}
			}
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$val	= $outputlangs->transnoentities($ibankey).' : '.$ibanDisplay;
			$pdf->MultiCell($larg, $hl, $val, 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
			$posy	+= $pdf->getStringHeight($larg, $val);
		}
		if (!empty($account->bic)) {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizecontent);
			$pdf->MultiCell($larg, $hl, $outputlangs->transnoentities($bickey).' : '.$outputlangs->convToOutputCharset($account->bic), 0, 'L', false, 1, $posx, $posy, true, 0, false, false, 0, 'M', false);
		}
		return $pdf->getY();
	}

	/**
	*	Get spacial payment extrafield
	*
	*	@param		object		$object			Object shown in PDF
	*	@param		int			$deposit		0 for special payments || 1 for deposit
	*	@return		array						Return extrafield found ['label'] => label ['value'] => value
	**/
	function pdf_InfraSPlus_SpecPayExtraField($object, $deposit = 0)
	{
		global $db, $conf;

		$list			= array();
		$efPaySpec		= getDolGlobalString('INFRASPLUS_PDF_EXF_PAY_SPEC', '');
		$efDeposit		= getDolGlobalString('INFRASPLUS_PDF_EXF_DEPOSIT', '');
		$ef				= explode(',', preg_replace('/\s+/', '', ($deposit ? $efDeposit : $efPaySpec)));	// string without any space to array
		$extrafields	= new ExtraFields($db);
		$extralabels	= $extrafields->fetch_name_optionals_label($object->table_element);
		$object->fetch_optionals();
		foreach ($extralabels as $key => $label) {
			if (in_array($key, $ef)) {
				$options_key	= $object->array_options['options_' .$key];
				$value			= price2num($extrafields->showOutputField($key, $options_key, '', $object->table_element), 'MT');
				if (!empty($value)) { // check if something is writting for this extrafield
					$list[$key]['label']	= $label;
					$list[$key]['value']	= $value;
				}
			}
		}
		return $list;
	}

	/**
	*	Show CGV for PDF generation
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	string			$cgv			PDF file name
	*	@param	int				$hidepagenum	Hide page num (x/y)
	*	@param	object			$object			Object shown in PDF
	*	@param	Translate		$outputlangs	Object lang for output
	*	@param	array			$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	**/
	function pdf_InfraSPlus_CGV(&$pdf, $cgv, $hidepagenum = 0, $object, $outputlangs, $formatpage)
	{
		global $conf;

		$path		= ($conf->entity > 1 ? '/'.$conf->entity : '');
		$cgv_pdf	= DOL_DATA_ROOT.$path.'/mycompany/'.$cgv;
		pdf_InfraSPlus_Merge($pdf, $cgv_pdf, $hidepagenum, $object, $outputlangs, $formatpage);
	}

	/**
	*	Show files for PDF generation
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	array			$files			list of rowid for PDF file from llx_ecm_files
	*	@param	int				$hidepagenum	Hide page num (x/y)
	*	@param	object			$object			Object shown in PDF
	*	@param	Translate		$outputlangs	Object lang for output
	*	@param	array			$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int				$noteBills		Is note bills ?
	*	@return	int								number of pages merged
	**/
	function pdf_InfraSPlus_files(&$pdf, $files, $hidepagenum = 0, $object, $outputlangs, $formatpage, $noteBills = 0)
	{
		global $conf, $db;

		$pagecount			= 0;
		$paramspecialfiles	= getDolGlobalString('INFRASPLUS_PDF_SPECIAL_FILES', '');
		if (!empty($paramspecialfiles)) {
			$paramspecialfiles	= explode(',', $paramspecialfiles);
		}
		if ($files != 'None' && is_array($files) && count($files) > 0) {
			foreach ($files as $fileID) {
				$sql	= ' SELECT filename, filepath';
				$sql	.= ' FROM '.$db->prefix().'ecm_files';
				$sql	.= ' WHERE rowid = '.$fileID;
				$sql	.= ' AND entity = '.$conf->entity;
				$resql	= $db->query($sql);
				if (!empty($resql)) {
					$objFile	= $db->fetch_object($resql);
					if (!empty($objFile)) {
						$filename	= $objFile->filename;
						$filepath	= $objFile->filepath;
						$file		= DOL_DATA_ROOT.'/'.$filepath.'/'.$filename;
						if (is_array($paramspecialfiles) && in_array(substr($filename, 0, -4), $paramspecialfiles)) {
							dol_include_once('/infraspackplus/core/modules/specialfiles/'.substr($filename, 0, -4).'.php');
							$function						= 'pdf_InfraSPlus_Merge_'.substr($filename, 0, -4);
							if (function_exists($function))	$function($pdf, $file, $hidepagenum, $object, $outputlangs, $formatpage);
						} else {
							$pagecount	+= pdf_InfraSPlus_Merge($pdf, $file, $hidepagenum, $object, $outputlangs, $formatpage, $noteBills);
						}
					}
				}
			}
		}
		return $pagecount;
	}

	/**
	*	Show CGV for PDF generation
	*
	*	@param	TCPDF|TCPDI|TCPDI	$pdf			The PDF factory
	*	@param	string				$infile			PDF file full name (with path) to merge
	*	@param	int					$hidepagenum	Hide page num (x/y)
	*	@param	object				$object			Object shown in PDF
	*	@param	Translate			$outputlangs	Object lang for output
	*	@param	array				$formatpage		Page Format => 'largeur', 'hauteur', 'mgauche', 'mdroite', 'mhaute', 'mbasse'
	*	@param	int					$noteBills		Is note bills ?
	*	@return	int									number of pages merged
	**/
	function pdf_InfraSPlus_Merge(&$pdf, $infile, $hidepagenum = 0, $object, $outputlangs, $formatpage, $noteBills = 0)
	{
		if (file_exists($infile) && is_readable($infile)) {
			$finfo	= finfo_open(FILEINFO_MIME_TYPE);
			if (finfo_file($finfo, $infile) == 'application/pdf') {
				try {
					$pdf->SetAutoPageBreak(0, 0);
					$pagecount	= $pdf->setSourceFile($infile);
					for ($i = 1; $i <= $pagecount; $i ++) {
						$tplIdx	= $pdf->importPage($i);
						if ($tplIdx !== false) {
							$s	= $pdf->getTemplatesize($tplIdx);
							$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L', array($s['w'], $s['h']));
							$pdf->useTemplate($tplIdx);
							if (empty($hidepagenum)) {
								$prevFont	= $pdf->getFontFamily();
								$pdf->SetFont('Helvetica');
								if (!getDolGlobalString('MAIN_USE_FPDF', '')) {
									$pdf->MultiCell(20, 2, $pdf->PageNo().' / '.$pdf->getAliasNbPages(), 0, 'R', 0, 1, getDolGlobalFloat('INFRASPLUS_PDF_X_PAGE_NUM', 0), getDolGlobalFloat('INFRASPLUS_PDF_Y_PAGE_NUM', 0), true, 0, 0, false, 0, 'M', false);
								} else {
									$pdf->MultiCell(20, 2, $pdf->PageNo().' / {nb}', 0, 'R', 0, 1, getDolGlobalFloat('INFRASPLUS_PDF_X_PAGE_NUM', 0), getDolGlobalFloat('INFRASPLUS_PDF_Y_PAGE_NUM', 0), true, 0, 0, false, 0, 'M', false);
								}
								$pdf->SetFont($prevFont);
							}
							if (getDolGlobalString('INFRASPLUS_PDF_REFDATE_MERGE', '')) {
								pdf_InfraSPlus_pagesrefdate($pdf, $object, $outputlangs, (!empty($noteBills) ? 'noteBills' : ''), $formatpage['mhaute'], (!empty($noteBills) ? $formatpage['largeur'] / 2 - 50 : $formatpage['largeur'] - $formatpage['mdroite'] - 100));
							}
						} else {
							setEventMessages(null, array($outputlangs->trans('PDFInfraSPlusPdfFileError1', $infile)), 'warnings');
						}
					}
					$pdf->SetAutoPageBreak(1, 0);
					return $pagecount;
				}
				catch (exception $e) {
					setEventMessages(null, array($outputlangs->trans('PDFInfraSPlusPdfFileError1', $infile).$outputlangs->trans('PDFInfraSPlusPdfFileError2', $e->getMessage())), 'warnings');
				}
			}
		}
		return 0;
	}

	/**
	*	Show reference and date of document.
	*
	*	@param	TCPDF|TCPDI|TCPDI	$pdf			The PDF factory
	*	@param	object				$object			Object shown in PDF
	*	@param	Translate			$outputlangs	Object lang for output
	*	@param	string				$title			string title in connection with objet type
	*	@param	int					$posx			Position depart (largeur)
	*	@param	int					$posy			Position depart (hauteur)
	*	@param	int					$ticket			From Ticket type invoice ?
	*	@return	void
	**/
	function pdf_InfraSPlus_pagesrefdate(&$pdf, $object, $outputlangs, $title, $posy, $posx, $ticket = 0)
	{
		if ($title != 'noteBills') {
			$ref_from_cust	= getDolGlobalInt('INFRASPLUS_PDF_REFD_FROM_CUSTOMER', 0);
			$ref			= $outputlangs->transnoentities('Ref');
			$reference		= $outputlangs->convToOutputCharset($object->element == 'propal' && !empty($ref_from_cust) ? $object->ref_client : $object->ref);
			$date			= dol_print_date(($object->element == 'stock' ? dol_now() : $object->date), 'day', false, $outputlangs, true);
			if (empty($date)) {
				$date	= dol_print_date($object->date_commande, 'day', false, $outputlangs, true);
			}
			if (empty($date)) {
				$date	= dol_print_date($object->date_contrat, 'day', false, $outputlangs, true);
			}
			if (empty($date)) {
				$date	= dol_print_date($object->date_delivery, 'day', false, $outputlangs, true);
			}
			if (empty($date)) {
				$date	= dol_print_date($object->datec, 'day', false, $outputlangs, true);
			}
			if (!empty($date)) {
				$pdf->MultiCell(100, 4, ($title ? $title.' ' : '').$ref.' '.$reference.' '.$outputlangs->transnoentities('Of').' '.$date, '', $ticket ? 'L' : 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			} else {
				$pdf->MultiCell(100, 4, ($title ? $title.' ' : '').$ref.' '.$reference, '', $ticket ? 'L' : 'R', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
			}
		} else {
			$date	= dol_print_date(dol_now(), 'day', false, $outputlangs, true);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities('PDFInfraSPlusInvoiceReleveTitle').' - '.$date, '', 'C', 0, 1, $posx, $posy, true, 0, 0, false, 0, 'M', false);
		}
	}

	/**
	*	Add a draft watermark on PDF files
	*
	*	@param	TCPDF|TCPDI|TCPDI	$pdf			The PDF factory
	*	@param	Translate			$outputlangs	Object lang
	*	@param	string				$text			Text to show
	*	@param	int					$center_y		Y center of rotation
	*	@param	int					$w				Width of table
	*	@param	int					$hp				Height of page
	*	@param	string				$unit			Unit of height (mm, pt, ...)
	*	@return	void
	**/
	function pdf_InfraSPlus_watermark(&$pdf, $outputlangs, $text, $center_y, $w, $hp, $unit)
	{
		$watermark_t_opacity	= getDolGlobalInt('INFRASPLUS_PDF_T_WATERMARK_OPACITY', 10);
		// Print Draft Watermark
		if ($unit=='pt') {
			$k = 1;
		} elseif ($unit=='mm') {
			$k = 72/25.4;
		} elseif ($unit=='cm') {
			$k = 72/2.54;
		} elseif ($unit=='in') {
			$k = 72;
		}
		$savx				= $pdf->getX();
		$savy				= $pdf->getY();
		$savFont			= $pdf->getFontFamily();
		$savFontStyle		= $pdf->getFontStyle();
		$savFontSizePt		= $pdf->getFontSizePt();
		$watermark_angle	= 20 / 180 * pi();	// angle de rotation 20° en radian
		$center_x			= $w / 2;			// x centre
		$pdf->SetFont('', 'B', 40);
		$pdf->SetTextColor(255, 0, 0);
		$pdf->SetAlpha($watermark_t_opacity / 100);
		//rotate
		$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', cos($watermark_angle), sin($watermark_angle),
					-sin($watermark_angle), cos($watermark_angle), $center_x * $k, ($hp - $center_y) * $k, -$center_x * $k, -($hp - $center_y) * $k));
		//print watermark
		$pdf->SetXY(10, $center_y - 10);
		$pdf->Cell($w, 20, $outputlangs->convToOutputCharset($text), '', 2, 'C', 0);
		//antirotate
		$pdf->_out('Q');
		$pdf->SetXY($savx, $savy);
		$pdf->SetAlpha(1);
		$pdf->SetFont($savFont, $savFontStyle, $savFontSizePt);
	}

	/**
	*	Show customer signature
	*
	*	@param	TCPDF|TCPDI		$pdf			The PDF factory
	*	@param	string			$signvalue		base64 string for png image
	*	@param	int				$larg_signarea	Width of area
	*	@param	int				$ht_signarea	Height of area
	*	@param	int				$posxsignarea	X position for the up left corner of area
	*	@param	int				$posysignarea	Y position for the up left corner of area
	*	@return	void
	**/
	function pdf_InfraSPlus_Client_Sign($pdf, $signvalue, $larg_signarea, $ht_signarea, $posxsignarea, $posysignarea)
	{
		global $conf;

		$imgSign64	= preg_replace('#^data:image/[^;]+;base64,#', '', $signvalue);
		$fileSign	= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/tmp/tmp.png';
		file_put_contents($fileSign, base64_decode($imgSign64));
		if (!empty($fileSign) && is_readable($fileSign)) {
			$imgsize	= array();
			$imgsize	= pdf_InfraSPlus_getSizeForImage($fileSign, $larg_signarea, $ht_signarea);
			if (isset($imgsize['width']) && isset($imgsize['height'])) {
				$posxSign	= ($larg_signarea - $imgsize['width']) / 2;	// centre l'image dans la zone
				$posySign	= ($ht_signarea - $imgsize['height']) / 2;	// centre l'image dans la zone
				$pdf->Image($fileSign, $posxsignarea + $posxSign, $posysignarea + $posySign, $imgsize['width'], $imgsize['height'], '', '', '', false, 300, '', false, false, 0);	// set sgnature image
			}
		}
		dol_delete_file($fileSign);
	}

	/**
	*	Show footer of page for PDF generation
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
	function pdf_InfraSPlus_pagefoot(&$pdf, $object, $outputlangs, $fromcompany, $formatpage, $showdetails, $hidesupline, $calculseul, $objEntity, $image_foot = '', $maxsizeimgfoot, $hidepagenum = 0, $txtcolor = array(0, 0, 0), $LineStyle = null, $noendline = 0)
	{
		global $conf, $user;

		$pdf->SetTextColor($txtcolor[0], $txtcolor[1], $txtcolor[2]);
		$footer_bold	= getDolGlobalInt('INFRASPLUS_PDF_REFD_FROM_CUSTOMER', 0);
		$noendline		= !empty($noendline) || getDolGlobalInt('INFRASPLUS_PDF_NO_LINE_FOOTER') ? 1 : 0;
		$pdf->SetFont('', $footer_bold ? 'B' : '', 7);
		$alignL1		= 'C';
		// First line of company infos
		if (getDolGlobalString('INFRASPLUS_PDF_FOOTER_FREETEXT', '')) {
			$footer_freeText	= getDolGlobalString('INFRASPLUS_PDF_FOOTER_FREETEXT', '');
			$line1				= pdf_InfraSPlus_formatNotes($object, $outputlangs, $footer_freeText);
			$htLine1			= $pdf->getStringHeight($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), dol_htmlentitiesbr($line1), true, false, array(), 0);
			$alignL1			= '';
		} else {
			$line1 = ''; $htLine1 = 3; $line2 = ''; $line3 = ''; $line4 = ''; $line5 = 0;
			if (substr($showdetails, 0, 1) == 1 || substr($showdetails, 0, 1) == 3) {
				if (!empty($fromcompany->name)) {
					$line1	.= ($line1 ? ' - ' : '').$outputlangs->transnoentities('RegisteredOffice').' : '.$fromcompany->name; // Company name
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
			}
			if (substr($showdetails, 0, 1) == 2 || substr($showdetails, 0, 1) == 3) {
				if (!empty($fromcompany->phone)) {
					$line2	.= ($line2 ? ' - ' : '').$outputlangs->transnoentities('PhoneShort').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($fromcompany->phone))); // Phone
				}
				if (!empty($fromcompany->fax)) {
					$line2	.= ($line2 ? ' - ' : '').$outputlangs->transnoentities('Fax').' : '.$outputlangs->convToOutputCharset(dol_string_nohtmltag(dol_print_phone($fromcompany->fax))); // Fax
				}
				if (!empty($fromcompany->url)) {
					$line2	.= ($line2 ? ' - ' : '').$fromcompany->url; // URL
				}
				if (!empty($fromcompany->email)) {
					$line2	.= ($line2 ? ' - ' : '').$fromcompany->email; // Email
				}
			}
			if (substr($showdetails, 1, 1) == 1 || ($fromcompany->country_code == 'DE')) {
				if (!empty($fromcompany->managers)) {
					$line3 .= ($line3 ? ' - ' : '').$outputlangs->transnoentities('PDFInfraSPlusManagement').' : '.$fromcompany->managers; // Managers
				}
			}
			if (substr($showdetails, 2, 1) == 1 || substr($showdetails, 2, 1) == 3) {
				if (!empty($fromcompany->forme_juridique_code)) {
					$line3 .= ($line3 ? ' - ' : '').$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code)); // Juridical status
				}
				if (!empty($fromcompany->capital)) { // Capital
					$tmpamounttoshow	= price2num($fromcompany->capital); // This field is a free string or a float
					if (is_numeric($tmpamounttoshow) && $tmpamounttoshow > 0) {
						$line3	.= ($line3 ? ' - ' : '').$outputlangs->transnoentities('CapitalOf', price($tmpamounttoshow, 0, $outputlangs, 0, 0, 0, $conf->currency));
					} elseif (!empty($fromcompany->capital) ) {
						$line3	.= ($line3 ? ' - ' : '').$outputlangs->transnoentities('CapitalOf', $tmpamounttoshow);
					}
				}
			}
			if (substr($showdetails, 2, 1) == 2 || substr($showdetails, 2, 1) == 3) {
				if (!empty($fromcompany->idprof1) && ($fromcompany->country_code != 'FR' || empty($fromcompany->idprof2))) { // Prof Id 1
					$field	= $outputlangs->transcountrynoentities('ProfId1', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg)) {
						$field	= $reg[1];
					}
					$tmpID	= pdf_InfraSPlus_build_IDs('ID1', $outputlangs->convToOutputCharset($fromcompany->idprof1), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if (!empty($fromcompany->idprof2)) { // Prof Id 2
					$field	= $outputlangs->transcountrynoentities('ProfId2', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg)) {
						$field	= $reg[1];
					}
					$tmpID	= pdf_InfraSPlus_build_IDs('ID2', $outputlangs->convToOutputCharset($fromcompany->idprof2), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if (!empty($fromcompany->idprof3)) { // Prof Id 3
					$field	= $outputlangs->transcountrynoentities('ProfId3', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg)) {
						$field	= $reg[1];
					}
					$tmpID	= pdf_InfraSPlus_build_IDs('ID3', $outputlangs->convToOutputCharset($fromcompany->idprof3), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if (!empty($fromcompany->idprof4)) { // Prof Id 4
					$field	= $outputlangs->transcountrynoentities('ProfId4', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg)) {
						$field	= $reg[1];
					}
					$tmpID	= pdf_InfraSPlus_build_IDs('ID4', $outputlangs->convToOutputCharset($fromcompany->idprof4), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if (!empty($fromcompany->idprof5)) { // Prof Id 5
					$field	= $outputlangs->transcountrynoentities('ProfId5', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg)) {
						$field	= $reg[1];
					}
					$tmpID	= pdf_InfraSPlus_build_IDs('ID5', $outputlangs->convToOutputCharset($fromcompany->idprof5), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if (!empty($fromcompany->idprof6)) { // Prof Id 6
					$field	= $outputlangs->transcountrynoentities('ProfId6', $fromcompany->country_code);
					if (preg_match('/\((.*)\)/i', $field, $reg)) {
						$field	= $reg[1];
					}
					$tmpID	= pdf_InfraSPlus_build_IDs('ID6', $outputlangs->convToOutputCharset($fromcompany->idprof6), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$field.' : '.$tmpID;
				}
				if ($fromcompany->tva_intra != '') {	// IntraCommunautary VAT
					$tmpID	= pdf_InfraSPlus_build_IDs('TVA', $outputlangs->convToOutputCharset($fromcompany->tva_intra), $fromcompany->country_code);
					$line4	.= ($line4 ? ' - ' : '').$outputlangs->transnoentities('VATIntraShort').' : '.$tmpID;
				}
			}
		}
		if (substr($showdetails, 4, 1) == 1) {
			$logodir	= !empty($conf->mycompany->multidir_output[$objEntity]) ? $conf->mycompany->multidir_output[$objEntity] : $conf->mycompany->dir_output;
			$logospied	= $logodir.'/logos/'.$image_foot;	// Logos partenaires en ligne 5
			if (is_readable($logospied)) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
				$imglinesize	= pdf_InfraSPlus_getSizeForImage($logospied, $maxsizeimgfoot['largeur'], $maxsizeimgfoot['hauteur']);
				if (!empty($imglinesize['height'])) {
					$line5	= $imglinesize['height'];
				}
			}
		}
		// The start of the bottom of this page footer is positioned according to # of lines
		$nopage				= $pdf->PageNo();
		$nbpage				= $pdf->getNumPages();
		$marginwithfooter	= (($nopage == $nbpage) && empty($hidesupline) ? 1 : 0) + (!empty($line1) ? $htLine1 : 0) + (!empty($line2) ? 3 : 0) + (!empty($line3) ? 3 : 0) + (!empty($line4) ? 3 : 0) + $line5 + $formatpage['mbasse'];
		if ($calculseul == 1) {
			return $marginwithfooter;
		}
		$posy	= $formatpage['hauteur'] - $marginwithfooter;
		$pdf->SetY($posy);
		if (empty($noendline) && $nopage == $nbpage && empty($hidesupline)) {
			$pdf->line($formatpage['mgauche'], $posy, $formatpage['largeur']-$formatpage['mdroite'], $posy, $LineStyle);
			$posy++;
		}
		if (!empty($line1)) {
			$pdf->writeHTMLCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), $htLine1, $formatpage['mgauche'], $posy, dol_htmlentitiesbr($line1), 0, 1, false, true, $alignL1, true);
			$posy	+= $htLine1 == 3 ? (substr($showdetails, 3, 1) == 1 ? 6 : 3) : $htLine1;
		}
		if (!empty($line2)) {
			$pdf->MultiCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), 2, $line2, 0, 'C', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= 3;
		}
		if (!empty($line3)) {
			$pdf->MultiCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), 2, $line3, 0, 'C', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
			$posy	+= 3;
		}
		if (!empty($line4)) {
			$pdf->MultiCell($formatpage['largeur'] - ($formatpage['mgauche'] + $formatpage['mdroite']), 2, $line4, 0, 'C', 0, 1, $formatpage['mgauche'], $posy, true, 0, 0, false, 0, 'M', false);
		}
		if (!empty($logospied) && is_readable($logospied) && !empty($line5)) {
			$posy			+= $htLine1 == 3 ? 3 : 0;
			$posxpicture	= $formatpage['mgauche'] + (($formatpage['largeur'] - $formatpage['mgauche'] - $formatpage['mdroite'] - $imglinesize['width']) / 2);	// centre l'image dans la colonne
			$pdf->Image($logospied, $posxpicture, $posy, $imglinesize['width'], $line5);	// width = 0 or height = 0 (auto)
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

	/**
	*	Convert RGBA color to RGB value
	*
	*	@param	string		$bgcolor	RGB value for background color
	*	@param	string		$color		RGB color to convert
	*	@param	float		$alpha		alpha value => 0.0 to 1
	*	@return	string					new RGB color
	**/
	function pdf_InfraSPlus_rgba_to_rgb(&$color, $bgcolor = '255, 255, 255', $alpha = 1)
	{
		$tmpcol		= explode(',', $color);
		$tmpcol[0]	= (!empty($tmpcol[0]) ? $tmpcol[0] : 0);
		$tmpcol[1]	= (!empty($tmpcol[1]) ? $tmpcol[1] : 0);
		$tmpcol[2]	= (!empty($tmpcol[2]) ? $tmpcol[2] : 0);
		$tmpbg		= explode(',', $bgcolor);
		$tmpbg[0]	= (!empty($tmpbg[0]) ? $tmpbg[0] : 0);
		$tmpbg[1]	= (!empty($tmpbg[1]) ? $tmpbg[1] : 0);
		$tmpbg[2]	= (!empty($tmpbg[2]) ? $tmpbg[2] : 0);
		$alpha		= (!empty($alpha) && 0 < $alpha && $alpha < 1 ? $alpha : 1);
		$tmpvalr	= ((1 - $alpha) * $tmpbg[0]) + ($alpha * $tmpcol[0]);
		$tmpvalg	= ((1 - $alpha) * $tmpbg[1]) + ($alpha * $tmpcol[1]);
		$tmpvalb	= ((1 - $alpha) * $tmpbg[2]) + ($alpha * $tmpcol[2]);
		$tmpval		= $tmpvalr.', '.$tmpvalg.', '.$tmpvalb;
		return $tmpval;
	}

	/**
	*	Get subtotal lines for summary
	*
	*	@param		object		$object			Object shown in PDF
	*	@param		integer		$i				Line number we work on
	*	@param		array		$subtotalRecap	list of subtotal lines
	*	@return		array						array of subtotal lines updated
	**/
	function pdf_InfraSPlus_subtotal_getrecap ($object, $i, $subtotalRecap)
	{
		// TODO contrôles en double ?
		$isSubTotalLine	= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
		$isSubTitle		= $isSubTotalLine && $object->lines[$i]->qty < 10 ? 1 : 0;	// Sous-titre ATM
		$isSubTotal		= $isSubTotalLine && $object->lines[$i]->qty > 90 ? 1 : 0;	// Sous-total ATM
		if (!empty($isSubTotal)) {	// Sous-total trouvé
			foreach ($object->lines as $line) {	// Parcours des lignes depuis le début
				if ($line->id == $object->lines[$i]->id) {
					break;	// Ligne de sous-total courant trouvée on arrête le parcours
				}
				$qty_search		= 100 - $object->lines[$i]->qty;	// calcul de la qty du sous-titre correspondant au sous-total courant (niveau)
				$isSubTotalLine	= infraspackplus_isLineFromExternalModule($line, $object->element, 'modSubtotal');
				$isSubTitle		= $isSubTotalLine && $line->qty < 10 ? 1 : 0;	// Sous-titre ATM
				if (!empty($isSubTitle) && $line->qty == $qty_search) {
					$titleRang	= $line->rang;	// ligne de sous-titre correspondant au sous-total courant trouvée
				}
			}
			$subtotalRecap[]	= array('line' => $i, 'type' => 'subtotal', 'rang' => $titleRang, 'level' => $qty_search);	// Enregistrement du lien entre le sous-total ($i) et son sous-titre associé ($titleRang)
		}
		return $subtotalRecap;
	}

	/**
	*	Sort an array by values using a key (for multi-dimensional array)
	*
	*	@param		array		$array	Array to sort
	*	@param		string		$key	Key to use for sorting
	*	@return		array				Sorted array
	**/
	function pdf_InfraSPlus_compare($array, $key)
	{
		usort($array, function ($a, $b) use ($key) {
			return strnatcmp($a[$key], $b[$key]);
		});
		return $array;
	}

	/**
	*	Print subtotal Summary
	*
	*	@param		TCPDF		&$pdf				The PDF factory
	*	@param		object		$object				Object shown in PDF
	*	@param		float		$tab_top			Top position of table
	*	@param		Translate	$outputlangs		Object lang for output
	*	@param		array		$subtotalRecap		array of lines to print
	*	@param		object		$template			object template we work on
	*	@param		integer		$heightforinfotot	height reserved for info table
	*	@param		integer		$heightforfooter	height reserved for footer
	*	@return		integer							next Y position
	**/
	function pdf_InfraSPlus_subtotal_recap (&$pdf, $object, $tab_top, $outputlangs, $subtotalRecap, &$template, $ht_coltotal, $heightforfooter)
	{
		global $conf, $db;

		$default_font_size	= pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->MultiCell($template->formatpage['largeur'] - $template->formatpage['mgauche'] - $template->formatpage['mdroite'], $template->heightline * 2, $outputlangs->transnoentities('PDFInfraSPlusRecap'), '', 'C', 0, 1, $template->formatpage['mgauche'], $tab_top + 10, true, 0, 0, false, 0, 'M', false);
		$pdf->SetFont('', '', $default_font_size - 1);
		$posy				= $tab_top + 30;
		$nblignes			= count($subtotalRecap);
		for ($i = 0 ; $i < $nblignes ; $i++) {
			$pageposbefore	= $pdf->getPage();
			$posx			= $template->tableau['desc']['posx'] + ($subtotalRecap[$i]['level'] > 1 ? $subtotalRecap[$i]['level'] * 4 : 0);
			pdf_InfraSPlus_writelinedesc($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs, $template->formatpage, $template->horLineStyle, $template->tableau['desc']['larg'], $template->heightline, $posx, $posy, 0, 0, 0, '', null, 0, 1);
			// Total line
			if (empty($template->hide_vat)) {
				$total_line	= pdf_InfraSPlus_getlinetotalexcltax($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs);
			} else {
				$total_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs);
			}
			$pdf->MultiCell($template->tableau['totalht']['larg'], $template->heightline, $total_line, '', 'R', 0, 1, $template->tableau['totalht']['posx'], $posy, true, 0, 0, false, 0, 'M', false);
			if ($template->show_ttc_col) {
				$totalTTC_line	= pdf_InfraSPlus_getlinetotalincltax($pdf, $object, $subtotalRecap[$i]['line'], $outputlangs);
				$pdf->MultiCell($template->tableau['totalttc']['larg'], $template->heightline, $totalTTC_line, '', 'R', 0, 1, $template->tableau['totalttc']['posx'], $posy, true, 0, 0, false, 0, 'M', false);
			}
			$pageposafter	= $pdf->getPage();
			$posyafter		= $pdf->GetY();
			if ($pageposafter > $pageposbefore) {	// There is a pagebreak
				if ($posyafter > ($template->formatpage['hauteur'] - ($heightforfooter + $ht_coltotal))) {	// There is no space left for total+free text
					$pdf->AddPage('', '', true);
					$pdf->setPage($pageposafter + 1);
					$posy	= $tab_top + ($template->hide_top_table ? $template->decal_round : $template->ht_top_table + $template->decal_round);
				}
			} elseif ($posyafter > ($template->formatpage['hauteur'] - ($heightforfooter + $ht_coltotal))) {	// There is no space left for total+free text
				$pdf->AddPage('', '', true);
				$pdf->setPage($pageposafter + 1);
				$posy	= $tab_top + ($template->hide_top_table ? $template->decal_round : $template->ht_top_table + $template->decal_round);
			}
			$posy	+= $template->heightline * 2;
		}
		return $pdf->GetY();
	}

	/**
	*	Check whether we need to show or hide the values of works and / or sub-elements of work
	*
	*	@param	object		$object		Object (propal order, ...)
	*	@param	int			$i			Current line number
	*	@param	int			$mode		function mode	=> -1 = ATM sub-total module - returns 1 for a standard line, under no title or under a title without subtotal
	*													=> -3 = ATM sub-total module - returns the row ID of the parent title if the details of this title should be displayed in a list (label and qty in a bulleted list)
	*													=> -4 = ATM sub-total module - returns the row ID of the parent title if the details of that title should be displayed condensed (label and qty to concatenate)
	*													=> 0 = Ouvrage module - std (return 1 to hide numeric values || 0 to show them)
	*													=> 1 = Ouvrage module - all internal lines (return 1 to hide the entire line || 0 to show it)
	*													=> 2 = Ouvrage module - check for description and label
	*													=> 3 = Ouvrage module - list work line check (label and qty in a bulleted list)
	*													=> 4 = Ouvrage module - condensed work line check (label and qty to concatenate)
	*	@return	int						the result depends on the mode
	**/
	function pdf_InfraSPlus_escapeEns ($object, $i, $mode = 0)
	{
		global $db;

		if (isModEnabled('subtotal') && $mode < 0) {
			$isSubTotalLine	= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal') ? 1 : 0;
			if (empty($isSubTotalLine)) {	// not a title nor a subtotal
				//	Check if a title exist for this line && if this title has subtotal
				$hasTitle	= !empty($object->lines[$i]) ? TSubtotal::getParentTitleOfLine($object, $object->lines[$i]->rang) : '';	// Cette ligne est-elle sous un titre ?
				if (!empty($hasTitle)) {
					switch ($mode) {
						case '-4':
							// Titre / sous titre à afficher condensé
							if (!empty($hasTitle->array_options['options_print_condensed']) && $hasTitle->array_options['options_print_condensed'] > 0)	return $hasTitle->id;
						break;
						case '-3':
							// Titre / sous titre à afficher sous forme de liste
							if (!empty($hasTitle->array_options['options_print_as_list']) && $hasTitle->array_options['options_print_as_list'] > 0)	return $hasTitle->id;
						break;
					}
				}
				return empty($hasTitle) || empty(TSubtotal::titleHasTotalLine($object, $hasTitle, true)) ? 1 : 0;	// Pas de titre au-dessus ou ce titre n'est pas associé à un sous-total
			}
		} elseif (isModEnabled('ouvrage') && class_exists('Ouvrage') && $mode >= 0) {
			$ouvHideMnt		= GETPOST('OUVRAGE_HIDE_MONTANT', 'int');	// Cacher le montant des ouvrages/forfaits
			$ouvHideDet		= GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL', 'int');	// Afficher uniquement l'ouvrage/forfait
			$ouvHideDesc	= GETPOST('OUVRAGE_HIDE_PRODUCT_DESCRIPTION', 'int');	// Cacher les détails tarifaires des produits/services
			$isOuvrage		= Ouvrage::isOuvrage($object->lines[$i]) ? 2 : 0;	// ligne d'ouvrage Inovea
			if ($isOuvrage == 2 && !empty($ouvHideMnt) && empty($mode)) {
				return 1;	// ligne d'ouvrage + mode 1 => on cache le montant
			}
			if (isset($object->lines[$i]->fk_parent_line)) {
				$inOuvrage = false;
				foreach ($object->lines as $key => $value) {
					if ($object->lines[$i]->fk_parent_line == $object->lines[$key]->rowid && Ouvrage::isOuvrage($object->lines[$key])) {
						$inOuvrage	= true;
						$ouvrageID	= $object->lines[$key]->array_options['options_fk_ouvrage'];
					}
				}
				if (!empty($inOuvrage)) {	// Composant d'ouvrage
					$result	= 0;
					switch ($mode) {
						case '4':
						case '3':
							if (empty($isOuvrage)) {
								$ouvrage	= new Ouvrage($db);
								if (!empty($ouvrageID)) {
									$res		= $ouvrage->fetch($ouvrageID);
									$toReturn	= $mode == 3 && $res > 0 && $ouvrage->statut == Ouvrage::STATUS_HIDDEN ? $ouvrageID : 0;	// ligne d'ouvrage => ID de l'ouvrage parent (ouvrage avec statut détails masqués) || 0 = ouvrage standard
									if (!empty($toReturn)) {
										$result	= $toReturn;
									} else {
										$result	= $mode == 4 && $res > 0 && $ouvrage->statut == Ouvrage::STATUS_VERY_HIDDEN ? $ouvrageID : 0;	// ligne d'ouvrage => ID de l'ouvrage parent (ouvrage avec statut détails condensés) || 0 = ouvrage standard
									}
								} else {
									$result	= 0;
								}
							}
						break;
						case '2':
							$result	= $isOuvrage + 1;	// mode 2 et ligne incluse dans un ouvrage Inovea => 0 + 1 ligne standard dans un ouvrage || 2 + 1 sous-ouvrage
						break;
						case '1':
							if (!empty($ouvHideDet) && !Ouvrage::isOuvrage($object->lines[$i])) {
								$result	= 1;	// mode 1 et Afficher uniquement l'ouvrage et la ligne n'est pas un ouvrage
							}
						break;
						case '0':
							if ((!empty($ouvHideDet) || !empty($ouvHideDesc)) && !empty($ouvHideMnt)) {
								$result	= 1;	// (Afficher uniquement l'ouvrage ou Cacher les détails tarifaires) et Cacher le montant
							} elseif ((!empty($ouvHideDet) || !empty($ouvHideDesc)) && !Ouvrage::isOuvrage($object->lines[$i])) {
								$result	= 1;	// (Afficher uniquement l'ouvrage ou Cacher les détails tarifaires) et la ligne n'est pas un ouvrage
							} elseif (Ouvrage::isOuvrage($object->lines[$i]) && !empty($ouvHideMnt)) {
								$result	= 1;	// la ligne est un ouvrage et Cacher le montant
							}
						break;
					}
					return $result;
				}
			} elseif ($mode == 2) {
				return $isOuvrage;	// si appellé depuis pdf_InfraSPlus_writelinedesc et pas de parent (Ouvrage principal)
			}
		}
		return 0;
	}

	/**
	*	Check whether we need to show or hide the lines between elements
	*
	*	@param	object		$object		Object (propal order, ...)
	*	@param	int			$i			Current line number
	*	@return	int						-1 if we can use the standard type of separation between elements, the value of the height of separation between the details of the work otherwise
	**/
	function pdf_InfraSPlus_separateLine ($object, $i)
	{
		global $conf;

		if (isModEnabled('ouvrage') && class_exists('Ouvrage')) {
			$detailSep_hight	= getDolGlobalInt('INFRASPLUS_PDF_OUVRAGE_DETAILSEP_HIGHT', 0);
			$ouvHideDet			= GETPOST('OUVRAGE_HIDE_PRODUCT_DETAIL', 'int');	// Afficher uniquement l'ouvrage/forfait
			if (!empty($ouvHideDet)) {
				return -1;	// each line can be processed as usual because we hide all the details of the works
			} elseif (Ouvrage::isOuvrage($object->lines[$i])) {
				return $detailSep_hight;	// we never want to show a separation between a work and its sub-elements
			} else {
				if (isset($object->lines[$i]->fk_parent_line)) {
					$inOuvrage = false;
					foreach ($object->lines as $key => $value) {
						if ($object->lines[$i]->fk_parent_line == $object->lines[$key]->rowid && Ouvrage::isOuvrage($object->lines[$key])) {
							$inOuvrage = true;
						}
					}
					if (!empty($inOuvrage)) {	// Composant d'ouvrage
						if ($object->lines[$i]->fk_parent_line == $object->lines[$i + 1]->fk_parent_line) {
							return $detailSep_hight;
						} else {
							return -1;
						}
					}
				}
			}
		} elseif (isModEnabled('subtotal')) {
			// Is the current line an ATM subtitle/subtotal?
			$isATMLine		= infraspackplus_isLineFromExternalModule($object->lines[$i], $object->element, 'modSubtotal');
			// The rule changes if it is a text line (qty == 50)
			$isATMLine		= $isATMLine && ($object->lines[$i]->qty != 50);
			// Is the following line an ATM subtitle/subtotal?
			$isATMLineNext	= !empty($object->lines[$i + 1]) ? infraspackplus_isLineFromExternalModule($object->lines[$i + 1], $object->element, 'modSubtotal') : false;
			// The rule changes if it is a text line (qty == 50)
			$isATMLineNext	= $isATMLineNext && ($object->lines[$i + 1]->qty != 50);
			return !empty($isATMLine) || !empty($isATMLineNext) ? 1 : -1;
		}
		return -1;
	}

	/**
	*	Build string for ZATCA QR Code (Arabi Saudia)
	*
	*	@param	object		$object		Object (propal order, ...)
	*	@return	string		String for ZATCA QR Code
	**/
	function pdf_InfraSPlus_buildZATCAQRString ($object)
	{
		global $conf, $mysoc;

		$tmplang			= new Translate('', $conf);
		$tmplang->setDefaultLang('en_US');
		$tmplang->load('main');
		$datestring			= dol_print_date($object->date, 'dayhourrfc');
		$pricewithtaxstring	= price2num($object->total_ttc, 2, 1);
		$pricetaxstring		= price2num($object->total_tva, 2, 1);
		// Using TLV format
		$s					= pack('C1', 1).pack('C1', strlen($mysoc->name)).$mysoc->name;
		$s					.= pack('C1', 2).pack('C1', strlen($mysoc->tva_intra)).$mysoc->tva_intra;
		$s					.= pack('C1', 3).pack('C1', strlen($datestring)).$datestring;
		$s					.= pack('C1', 4).pack('C1', strlen($pricewithtaxstring)).$pricewithtaxstring;
		$s					.= pack('C1', 5).pack('C1', strlen($pricetaxstring)).$pricetaxstring;
		$s					.= '';					// Hash of xml invoice
		$s					.= '';					// ecda signature
		$s					.= '';					// ecda public key
		$s					.= '';					// ecda signature of public key stamp
		$s					= base64_encode($s);
		return $s;
	}

	/**
	*	Build string for QR-Bill (Switzerland)
	*
	*	@param	object		$object		Object (propal order, ...)
	*	@return	string		String for Switzerland QR Code if QR-Bill
	**/
	function pdf_InfraSPlus_buildSwitzerlandQRString ($object)
	{
		global $conf, $db, $mysoc;

		/*
		Example: //S1/10/10201409/11/190512/20/1400.000-53/30/106017086/31/180508/32/7.7/40/2:10;0:30
		/10/ Numéro de facture : 10201409
		/11/ Date de facture : 12.05.2019
		/20/ Référence client : 1400.000-53
		/30/ Numéro IDE pour la TVA à CHE-106.017.086 TVA
		/31/ Date de la prestation pour la comptabilisation de la TVA ? 08.05.2018
		/32/ Taux de TVA sur le montant total de la facture : 7.7%
		/40/ Conditions : 2% d'escompte à 10 jours, paiement net à 30 jours
		*/
		$tmplang			= new Translate('', $conf);
		$tmplang->setDefaultLang('en_US');
		$tmplang->load('main');
		$pricewithtaxstring	= price2num($object->total_ttc, 2, 1);
		$pricetaxstring		= price2num($object->total_tva, 2, 1);
		$complementaryinfo	= '';
		$datestring			= dol_print_date($object->date, '%y%m%d');
		$complementaryinfo	= '//S1/10/'.str_replace('/', '', $object->ref).'/11/'.$datestring;
		$complementaryinfo	.= $object->ref_client				? '/20/'.$object->ref_client				: '';
		$complementaryinfo	.= $object->thirdparty->vat_number	? '/30/'.$object->thirdparty->vat_number	: '';
		// Header
		$s					= 'SPC'."\n";
		$s					.= '0200'."\n";
		$s					.= '1'."\n";
		// Info Seller ("Compte / Payable à")
		if ($object->fk_account > 0) {
			// Bank BAN if country is LI or CH
			$bankaccount	= new Account($db);
			$bankaccount->fetch($object->fk_account);
			$s				.= $bankaccount->iban."\n";
		} else {
			$s	.= "\n";
		}
		// Seller
		if ($bankaccount->id > 0 && getDolGlobalString('PDF_SWISS_QRCODE_USE_OWNER_OF_ACCOUNT_AS_CREDITOR')) {
			// If a bank account is prodived and we ask to use it as creditor, we use the bank address
			// TODO In a future, we may always use this address, and if name/address/zip/town/country differs from $mysoc, we can use the address of $mysoc into the final seller field ?
			$s					.= "S\n";
			$s					.= dol_trunc($bankaccount->proprio, 70, 'right', 'UTF-8', 1)."\n";
			$addresslinearray	= explode("\n", $bankaccount->owner_address);
			$s					.= dol_trunc(empty($addresslinearray[1]) ? '' : $addresslinearray[1], 70, 'right', 'UTF-8', 1)."\n";		// address line 1
			$s					.= dol_trunc(empty($addresslinearray[2]) ? '' : $addresslinearray[2], 70, 'right', 'UTF-8', 1)."\n";		// address line 2
			/*
			$s					.= dol_trunc($mysoc->zip, 16, 'right', 'UTF-8', 1)."\n";
			$s					.= dol_trunc($mysoc->town, 35, 'right', 'UTF-8', 1)."\n";
			$s					.= dol_trunc($mysoc->country_code, 2, 'right', 'UTF-8', 1)."\n";
			*/
		} else {
			$s					.= 'S'."\n";
			$s					.= dol_trunc($mysoc->name, 70, 'right', 'UTF-8', 1)."\n";
			$addresslinearray	= explode("\n", $mysoc->address);
			$s					.= dol_trunc(empty($addresslinearray[1]) ? '' : $addresslinearray[1], 70, 'right', 'UTF-8', 1)."\n";	// address line 1
			$s					.= dol_trunc(empty($addresslinearray[2]) ? '' : $addresslinearray[2], 70, 'right', 'UTF-8', 1)."\n";	// address line 2
			$s					.= dol_trunc($mysoc->zip, 16, 'right', 'UTF-8', 1)."\n";
			$s					.= dol_trunc($mysoc->town, 35, 'right', 'UTF-8', 1)."\n";
			$s					.= dol_trunc($mysoc->country_code, 2, 'right', 'UTF-8', 1)."\n";
		}
		// Final seller
		$s					.= "\n";
		$s					.= "\n";
		$s					.= "\n";
		$s					.= "\n";
		$s					.= "\n";
		$s					.= "\n";
		$s					.= "\n";
		// Amount of payment (to do?)
		$s					.= price($pricewithtaxstring, 0, 'none', 0, 0, 2)."\n";
		$s					.= ($object->multicurrency_code ? $object->multicurrency_code : $conf->currency)."\n";
		// Buyer
		$s					.= 'S'."\n";
		$s					.= dol_trunc($object->thirdparty->name, 70, 'right', 'UTF-8', 1)."\n";
		$addresslinearray	= explode("\n", $object->thirdparty->address);
		$s					.= dol_trunc(empty($addresslinearray[1]) ? '' : $addresslinearray[1], 70, 'right', 'UTF-8', 1)."\n";	// address line 1
		$s					.= dol_trunc(empty($addresslinearray[2]) ? '' : $addresslinearray[2], 70, 'right', 'UTF-8', 1)."\n";	// address line 2
		$s					.= dol_trunc($object->thirdparty->zip, 16, 'right', 'UTF-8', 1)."\n";
		$s					.= dol_trunc($object->thirdparty->town, 35, 'right', 'UTF-8', 1)."\n";
		$s					.= dol_trunc($object->thirdparty->country_code, 2, 'right', 'UTF-8', 1)."\n";
		// ID of payment
		$s					.= 'NON'."\n";	// NON or QRR
		$s					.= "\n";		// QR Code if previous field is QRR
		$s					.= $complementaryinfo ? $complementaryinfo."\n" : "\n";	// Free text
		$s					.= 'EPD'."\n";
		$s					.= $complementaryinfo ? $complementaryinfo."\n" : '';	// More text, complementary info
		$s					.= "\n";
		return $s;
	}

	/**
	*	Build string for QR-Bill (Belgium, Netherlands, Germany, Austria and Finland)
	*
	*	@param	object		$object		Object (propal order, ...)
	*	@return	string					String for QR Code
	*									Exemple : Code QR de l'EPC pour payer 1,50 € à la Croix-Rouge de Belgique
	*									Code de service			: BCD
	*									Version					: 002
	*									Jeu de caractères		: 1
	*									Identification			: SCT
	*									BIC						: BPOTBEB1
	*									Nom						: Red Cross of Belgium
	*									IBAN					: BE72000000001616
	*									Montant					: EUR1.5
	*									Reason (4 chars max)	: CHAR
	*									Ref of invoice			: Empty line or REFINVOICE
	*									Or text					: Urgency fund or Empty line
	*									Information				: Sample QR code
	*									BCD\002\1\SCT\BPOTBEB1\Red Cross of Belgium\BE72000000001616\EUR1.5\CHAR\REFINVOICE\Urgency fund\Sample QR code
	**/
	function pdf_InfraSPlus_buildBelgiumQRString ($object)
	{
		global $db, $mysoc;

		$pricewithtaxstring	= price2num($object->total_ttc, 2, 1);
		$s					= '';
		if ($object->fk_account > 0) {
			// Bank BAN if country is LI or CH
			$bankaccount	= new Account($db);
			$result			= $bankaccount->fetch($object->fk_account);
			if ($result > 0) {
				$s	= 'BCD'."\n";	// Service code
				$s	.= '002'."\n";	// Version
				$s	.= '1'."\n";	// Character set
				$s	.= 'SCT'."\n";	// Identification
				$s	.= $bankaccount->bic."\n";	// BIC
				$s	.= ($bankaccount->proprio ? $bankaccount->proprio : $mysoc->name)."\n";	// Name of the bank account or seller
				$s	.= $bankaccount->iban."\n";	// IBAN
				$s	.= 'EUR'.price($pricewithtaxstring, 0, 'none', 0, 0, 2)."\n";	// Amount of payment
				$s	.= "\n";	// Reason
				$s	.= $object->ref."\n";	// Ref of invoice
				$s	.= "\n";	// Or text
				$s	.= "\n";	// Information
			}
		}
		return $s;
	}

	/**
	*	Build string for structured code (Belgium)
	*
	*	@param	object		$object		Object (propal order, ...)
	*	@return	string					String for structured code
	**/
	function pdf_InfraSPlus_buildBelgiumStructuredCode ($object)
	{
		global $db, $mysoc;

		$invoice_number	= preg_replace('/[^0-9]/', '', $object->ref); // Keep only numbers
		$invoice_number	= substr('00000000'.$invoice_number, -8);	// We complete with 0 and take the last 8 digits of the number are used to generate the reference base.
		// Prefix with invoice type
		switch ($object->type) {
			case '0':
				$invoice_type = '20'; // invoice type standard
			break;
			case '1':
				$invoice_type = '30'; // invoice type replacement
			break;
			case '2':
				$invoice_type = '40'; // invoice type credit note
			break;
			case '3':
				$invoice_type = '21'; // invoice type deposit
			break;
			case '5':
				$invoice_type = '20'; // invoice type situation, force to standard
			break;
			default:
				$invoice_type = '00';
		}
		// Calculate module97
		$invoice_number	= $invoice_type.$invoice_number;
		$mod97			= intval($invoice_number) % 97;
		$controlKey		= ($mod97 === 0) ? 97 : $mod97;
		// Add the check digit at the end of the reference
		$invoice_number	.= $controlKey;
		// Format reference as XXX/XXXX/XXXXX
		$part1			= substr($invoice_number, 0, 3);
		$part2			= substr($invoice_number, 3, 4);
		$part3			= substr($invoice_number, 7, 5); // Includes last 3 digits + 2 check digits
		$invoice_number	= $part1 . '/' . $part2 . '/' . $part3;
		return $invoice_number;
	}

	/**
	*	Print magic words for UpToSign
	*
	*	@param		TCPDF		&$pdf				The PDF factory
	*	@param		object		$object				Object (propal order, ...)
	*	@param		object		$template			template we work on
	*	@param		string		$type				Signature type => customer | internal
	*	@param		int			$posxsignarea		x position for top left corner
	*	@param		int			$posysignarea		y position for top left corner
	*	@param		int			$larg_signarea		signature width
	*	@param		int			$ht_signarea		signature height
	*	@return		void
	**/
	function pdf_InfraSPlus_add_e_signature(&$pdf, $object, $template, $type, $posxsignarea, $posysignarea, $larg_signarea, $ht_signarea)
	{
		global $conf;

		$posxsignarea	+= $template->Rounded_rect / 2;
		$posysignarea	+= $template->Rounded_rect / 2;
		$larg_signarea	-= $template->Rounded_rect;
		$ht_signarea	-= $template->Rounded_rect;
		$pdf->SetAlpha(0);
		if ($object->element != 'facture' || $type != 'stamp') {
			$pdf->MultiCell($larg_signarea, $ht_signarea, ($type == 'customer' ? 'UPTOSIGN_SIGN_TO_HERE' : 'UPTOSIGN_SIGN_FROM_HERE'), '', 'L', 0, 1, $posxsignarea, $posysignarea, true, 0, 0, false, 0, 'M', false);
			if ($object->element == 'contrat' || ($object->element == 'fichinter' && !empty($template->show_sign_area_emet)) || ($object->element == 'commande' && !empty($template->show_2sign_area))) {
				$pdf->MultiCell(65, 10, 'UPTOSIGN_STAMP_SIGN_HERE', '', 'L', 0, 1, ($template->page_largeur / 2) - 32.5, $template->posystamp, true, 0, 0, false, 0, 'M', false);
			} else {
				$pdf->MultiCell(65, 10, 'UPTOSIGN_STAMP_SIGN_HERE', '', 'L', 0, 1, $template->marge_gauche, $template->posystamp, true, 0, 0, false, 0, 'M', false);
			}
		} else {
			$pdf->MultiCell(65, 10, 'UPTOSIGN_STAMP_HERE', '', 'L', 0, 1, $template->marge_gauche, $template->posystamp, true, 0, 0, false, 0, 'M', false);
		}
		$pdf->SetAlpha(1);
	}
