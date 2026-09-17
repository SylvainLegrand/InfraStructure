<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand				- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina 	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/admin/infrasplussetup.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'companies', 'orders', 'sendings', 'contracts', 'bills', 'errors', 'stocks', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infraspackplus', 'paramInfraSPlus')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form					= new Form($db);
	$formfile				= new FormFile($db);
	$formother				= new FormOther($db);
	$formcompany			= new FormCompany($db);
	$formadmin				= new FormAdmin($db);
	$confirm_mesg			= '';
	$action					= GETPOST('action','alpha');
	$confirm				= GETPOST('confirm', 'alpha');
	$urlfile				= GETPOST('urlfile', 'alpha');
	$typefile				= GETPOST('typefile', 'alpha');
	$listModeles 			= ['BLC', 'C', 'CBC', 'CBL', 'CP', 'OF', 'OM', 'CF', 'CFBL', 'F', 'FL', 'FR', 'FT', 'FF', 'D', 'DP', 'DST', 'DF', 'PJ_Dossier', 'PJ',
								'BL', 'BLX', 'ET', 'BR', 'CT', 'CTS', 'RE', 'MRP', 'FI', 'NDF', 'BC', 'BOM', 'Bon'];
	// Module Dolibarr requis pour chaque modèle (masque l'option de préfixe si le module correspondant est désactivé)
	$listModelesModule		= ['BLC' => 'commande', 'C' => 'commande', 'CBC' => 'commande', 'CBL' => 'commande', 'CP' => 'commande', 'OF' => 'commande', 'OM' => 'commande',
								'CF' => 'supplier_order', 'CFBL' => 'supplier_order', 'F' => 'facture', 'FL' => 'facture', 'FR' => 'facture', 'FT' => 'facture', 'FF' => 'supplier_invoice',
								'D'	=> 'propal', 'DP' => 'propal', 'DST' => 'propal', 'DF' => 'supplier_proposal', 'PJ_Dossier' => 'projet', 'PJ' => 'projet', 'BL' => 'expedition',
								'BLX' => 'expedition', 'ET' => 'expedition', 'BR' => 'expedition', 'CT' => 'contrat', 'CTS' => 'contrat', 'RE' => 'reception', 'MRP' => 'mrp',
								'FI' => 'ficheinter', 'NDF' => 'expensereport', 'BC' => 'banque', 'BOM' => 'mrp', 'Bon' => 'infrasfiles',	// Bon = bons de prélèvement / virement via le module InfraSFiles
							];
	$listParamsExfProdPos	= array('type'				=> 'varchar',
									'pos'				=> 50,
									'size'				=> '64',
									'unique'			=> 0,
									'required'			=> 0,
									'default_value'		=> '',
									'param'				=> 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',
									'alwayseditable'	=> 1,
									'perms'				=> '',
									'list'				=> '3',
									'help'				=> '',
									'computed'			=> '',
									'entity'			=> $conf->entity,
									'langfile'			=> 'infraspackplus@infraspackplus',
									'enabled'			=> '1',
									'totalizable'		=> 0,
									'printable'			=> 2
									);
	$result					= '';
	$cgxdir					= !empty($conf->mycompany->multidir_output[$conf->entity])	? $conf->mycompany->multidir_output[$conf->entity]	: $conf->mycompany->dir_output;
	$pdfsdir				= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/';
	$phpsdir				= dol_buildpath('/infraspackplus/core/modules/specialfiles', 0);
	// Update with multicompany management
	if ($action == 'copyParams') {
		$sourceEntity	= GETPOSTINT('entity');
		$res			= infraspackplus_copy_entity( $sourceEntity, $conf->entity);
		if ($res < 0) {
			setEventMessage($langs->trans('InfraSPackPlusErrorFailedToCopyParameters',$sourceEntity, $conf->entity),'errors');
		} else {
			setEventMessage($langs->trans('InfraSPackPlusParametersCopiedFromEntity', $sourceEntity), 'mesgs');
			header('Location: '.$_SERVER['PHP_SELF']);
			exit;
		}
	}
	// Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infraspackplus_bkup_module ('infraspackplus');
	} elseif ($action == 'restoreParams') {
		$result	= infraspackplus_restore_module ('infraspackplus');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		if (preg_match('/INFRASPLUS_PDF_(FRM_E|FRM_R|TBL|SIGN)_LINE_DASH_([0124])/', $confkey, $reg2)) {
			$listReg	= array(0, 1, 2, 4);
			foreach ($listReg as $key) {
				if ($reg2[2] == $key) {
					continue;
				}
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_'.$reg2[1].'_LINE_DASH_'.$key,	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
			$inputFrmLineDashCleanValue		= $reg2[1] == 'FRM_E' && $reg2[2] > 0 ? 1 : '';
			$inputFrmRLineDashCleanValue	= $reg2[1] == 'FRM_R' && $reg2[2] > 0 ? 1 : '';
			$inputTblLineDashCleanValue		= $reg2[1] == 'TBL' && $reg2[2] > 0 ? 1 : '';
			$inputSignLineDashCleanValue	= $reg2[1] == 'SIGN' && $reg2[2] > 0 ? 1 : '';
		}
		// PDF generation
		if ($confkey == 'INFRASPLUS_PDF_SEMIAUTOUPDATE' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'MAIN_DISABLE_PDF_AUTOUPDATE', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'MAIN_DISABLE_PDF_AUTOUPDATE' && GETPOST('value') == 0) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_SEMIAUTOUPDATE', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		// automatic switch between num and ref column
		if ($confkey == 'INFRASPLUS_PDF_WITH_NUM_COLUMN' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_REF_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'INFRASPLUS_PDF_WITH_REF_COLUMN' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'MAIN_GENERATE_DOCUMENTS_HIDE_REF', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_NUM_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		// Special files on project : auto vs manual
		if (preg_match('/INFRASPLUS_PDF_SPECIAL_FILE_PROJECT_/', $confkey, $reg2)) {
			if (preg_match('/_AUTO/', $confkey, $reg2) && GETPOST('value') == 1) {
				$result	= dolibarr_set_const($db, str_replace('_AUTO', '', $confkey), 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			} elseif (GETPOST('value') == 1) {
				$result	= dolibarr_set_const($db, $confkey.'_AUTO', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
		}
		// Custom country code (SH)
		if ($confkey == 'INFRASPLUS_PDF_SHOW_WVCC' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		// Total discount in the informations table or in the total table
		if ($confkey == 'INFRASPLUS_PDF_SHOW_TOT_DISCOUNT' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_SHOW_DISCOUNT_TOT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		if ($confkey == 'INFRASPLUS_PDF_SHOW_DISCOUNT_TOT' && GETPOST('value') == 1) {
			$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_SHOW_TOT_DISCOUNT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		// Sub total background color management
		if ($confkey == 'INFRASPLUS_PDF_HIDE_BODY_SUBTO') {
			if (GETPOST('value') == 1) {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR', '255,255,255', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', '0', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			} else {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', '1', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
		}
		if ($confkey == 'INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI') {
			if (GETPOST('value') == 1) {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR', '255,255,255', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HIDE_BODY_SUBTO', '0', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			} else {
				$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HIDE_BODY_SUBTO', '1', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			}
		}
	}
	$INFRASPLUS_PDF_ADD_PREFIX_TO_MODEL	= [];
	foreach ($listModeles as $model) {
		$INFRASPLUS_PDF_ADD_PREFIX_TO_MODEL[] = 'INFRASPLUS_PDF_ADD_PREFIX_TO_'.$model;
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('manage'	=> array_merge(array('INFRASPLUS_PDF_ROUNDING_UP', 'INFRASPLUS_PDF_ROUNDING_TOT'), $INFRASPLUS_PDF_ADD_PREFIX_TO_MODEL),
							'Gen'		=> array('INFRASPLUS_PDF_ROUNDED_REC',				'INFRASPLUS_PDF_FACTURE_PAID_WATERMARK',	'INFRASPLUS_PDF_ENABLE_TEST_WATERMARK', 'INFRASPLUS_PDF_PROPAL_PROV_WATERMARK'),
							'Template'	=> [],
							'Head'		=> array('INFRASPLUS_PDF_SPE_HEAD',					'INFRASPLUS_PDF_INVOICE_TITLE_IF_DEPOSIT',	'INFRASPLUS_PDF_TITLE_SIZE', 			'INFRASPLUS_PDF_FRM_E_LINE_WIDTH',
												'INFRASPLUS_PDF_FRM_E_LINE_DASH',			'INFRASPLUS_PDF_FRM_E_LINE_OPACITY',		'INFRASPLUS_PDF_FRM_E_OPACITY',			'INFRASPLUS_PDF_FRM_R_LINE_WIDTH',
												'INFRASPLUS_PDF_FRM_R_LINE_DASH',			'INFRASPLUS_PDF_FRM_R_LINE_OPACITY',		'INFRASPLUS_PDF_FRM_R_OPACITY',			'INFRASPLUS_PDF_FOLD_MARK',
												'INFRASPLUS_PDF_HEIGHT_HEAD_SEP',			'INFRASPLUS_PDF_LEFT_RECEP_CORNER',			'INFRASPLUS_PDF_TOP_RECEP_CORNER',		'INFRASPLUS_PDF_SPACE_HEADERAFTER'),
							'Body'		=> array('INFRASPLUS_PDF_HEIGHT_TOP_TABLE',			'INFRASPLUS_PDF_TBL_LINE_WIDTH',			'INFRASPLUS_PDF_TBL_LINE_DASH',			'INFRASPLUS_PDF_LINESEP_HIGHT',
												'INFRASPLUS_PDF_EXF_PROD_POS',				'INFRASPLUS_PDF_HT_BC',						'INFRASPLUS_PDF_LARG_BC',				'INFRASPLUS_PDF_DIM_C2D',
												'INFRASPLUS_PDF_NUMCOL_REF',				'INFRASPLUS_PDF_NUMCOL_DESC',				'INFRASPLUS_PDF_NUMCOL_QTY',			'INFRASPLUS_PDF_NUMCOL_UNIT',
												'INFRASPLUS_PDF_NUMCOL_UP',					'INFRASPLUS_PDF_NUMCOL_TVA',				'INFRASPLUS_PDF_NUMCOL_DISC',			'INFRASPLUS_PDF_NUMCOL_UPD',
												'INFRASPLUS_PDF_NUMCOL_PROGRESS',			'INFRASPLUS_PDF_NUMCOL_TOTAL',				'INFRASPLUS_PDF_NUMCOL_TOTAL_TTC',
												'INFRASPLUS_PDF_LARGCOL_REF',				'INFRASPLUS_PDF_LARGCOL_QTY',				'INFRASPLUS_PDF_LARGCOL_UNIT',
												'INFRASPLUS_PDF_LARGCOL_UP',				'INFRASPLUS_PDF_LARGCOL_TVA',				'INFRASPLUS_PDF_LARGCOL_DISC',			'INFRASPLUS_PDF_LARGCOL_UPD',
												'INFRASPLUS_PDF_LARGCOL_PROGRESS',			'INFRASPLUS_PDF_LARGCOL_TOTAL',				'INFRASPLUS_PDF_LARGCOL_TOTAL_TTC',
												'INFRASPLUS_PDF_NUMCOLBL_REF',				'INFRASPLUS_PDF_NUMCOLBL_EFL',				'INFRASPLUS_PDF_NUMCOLBL_DESC',			'INFRASPLUS_PDF_NUMCOLBL_WV',
												'INFRASPLUS_PDF_NUMCOLBL_UNIT',				'INFRASPLUS_PDF_NUMCOLBL_ORDERED',			'INFRASPLUS_PDF_NUMCOLBL_REL',			'INFRASPLUS_PDF_NUMCOLBL_QTY',
												'INFRASPLUS_PDF_NUMCOLBL_PRICE',
												'INFRASPLUS_PDF_LARGCOLBL_REF',				'INFRASPLUS_PDF_LARGCOLBL_EFL',				'INFRASPLUS_PDF_LARGCOLBL_WV',
												'INFRASPLUS_PDF_LARGCOLBL_UNIT',			'INFRASPLUS_PDF_LARGCOLBL_ORDERED',			'INFRASPLUS_PDF_LARGCOLBL_REL',			'INFRASPLUS_PDF_LARGCOLBL_QTY',
												'INFRASPLUS_PDF_LARGCOLBL_PRICE',
												'INFRASPLUS_PDF_NUMCOLBR_REF',				'INFRASPLUS_PDF_NUMCOLBR_DESC',				'INFRASPLUS_PDF_NUMCOLBR_COMM',			'INFRASPLUS_PDF_NUMCOLBR_UNIT',
												'INFRASPLUS_PDF_NUMCOLBR_ORDERED',			'INFRASPLUS_PDF_NUMCOLBR_REL',				'INFRASPLUS_PDF_NUMCOLBR_QTY',
												'INFRASPLUS_PDF_LARGCOLBR_REF',				'INFRASPLUS_PDF_LARGCOLBR_COMM',			'INFRASPLUS_PDF_LARGCOLBR_UNIT',		'INFRASPLUS_PDF_LARGCOLBR_ORDERED',
												'INFRASPLUS_PDF_LARGCOLBR_REL',				'INFRASPLUS_PDF_LARGCOLBR_QTY',
												'INFRASPLUS_PDF_NUMCOLST_REF',				'INFRASPLUS_PDF_NUMCOLST_DESC',				'INFRASPLUS_PDF_NUMCOLST_QTY',			'INFRASPLUS_PDF_NUMCOLST_PMP',
												'INFRASPLUS_PDF_NUMCOLST_PMPT',				'INFRASPLUS_PDF_NUMCOLST_PU',				'INFRASPLUS_PDF_NUMCOLST_TOT',
												'INFRASPLUS_PDF_LARGCOLMRP_REF',			'INFRASPLUS_PDF_LARGCOLMRP_QTY',			'INFRASPLUS_PDF_LARGCOLMRP_QTYTOT',		'INFRASPLUS_PDF_LARGCOLMRP_UNIT',
												'INFRASPLUS_PDF_LARGCOLMRP_DIM',
												'INFRASPLUS_PDF_NUMCOLMRP_REF',				'INFRASPLUS_PDF_NUMCOLMRP_DESC',			'INFRASPLUS_PDF_NUMCOLMRP_QTY',			'INFRASPLUS_PDF_NUMCOLMRP_QTYTOT',
												'INFRASPLUS_PDF_NUMCOLMRP_UNIT',			'INFRASPLUS_PDF_NUMCOLMRP_DIM',
												'INFRASPLUS_PDF_LARGCOLST_REF',				'INFRASPLUS_PDF_LARGCOLST_QTY',				'INFRASPLUS_PDF_LARGCOLST_PMP',			'INFRASPLUS_PDF_LARGCOLST_PMPT',
												'INFRASPLUS_PDF_LARGCOLST_PU',				'INFRASPLUS_PDF_LARGCOLST_TOT',
												'INFRASPLUS_PDF_USER_STICKER_FORMAT', 		'INFRASPLUS_PDF_USER_STICKER_TITLE',
												'INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH',		'INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE',		'INFRASPLUS_PDF_TEXT_OUV_STYLE',		'INFRASPLUS_PDF_OUVRAGE_BULLET',
												'INFRASPLUS_PDF_OUVRAGE_DETAILSEP_HIGHT',	'INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF',		'INFRASPLUS_PDF_FORCE_ALIGN_LEFT_UNIT',	'INFRASPLUS_PDF_HEIGHT_MRP_CONTROL_TABLE'),
							'Foot'		=> array('INFRASPLUS_PDF_SPACE_INFO',				'INFRASPLUS_PDF_SPACE_TOT',					'INFRASPLUS_PDF_PAY_INLINE',			'INFRASPLUS_PDF_PAY_SPEC',
												'INFRASPLUS_PDF_TVA_FORFAIT',				'INFRASPLUS_PDF_HT_SIGN_AREA',				'INFRASPLUS_PDF_SIGN_LINE_WIDTH',		'INFRASPLUS_PDF_SIGN_LINE_DASH'),
							'FootP'		=> array('INFRASPLUS_PDF_FOOTER_FREETEXT',			'INFRASPLUS_PDF_SPE_FOOT',					'INFRASPLUS_PDF_X_PAGE_NUM',			'INFRASPLUS_PDF_Y_PAGE_NUM'),
							'CGx'		=> array('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL',		'INFRASPLUS_PDF_CGV',						'INFRASPLUS_PDF_CGI',					'INFRASPLUS_PDF_CGA')
							);
		$listcolor	= array('manage'	=> [],
							'Gen'		=> array('INFRASPLUS_PDF_BODY_TEXT_COLOR'),
							'Template'	=> [],
							'Head'		=> array('INFRASPLUS_PDF_HEADER_TEXT_COLOR',		'INFRASPLUS_PDF_FACT_DATEDUE_COLOR',		'INFRASPLUS_PDF_FRM_E_LINE_COLOR',		'INFRASPLUS_PDF_FRM_E_BG_COLOR',
												'INFRASPLUS_PDF_FRM_E_TEXT_COLOR',			'INFRASPLUS_PDF_FRM_R_LINE_COLOR',			'INFRASPLUS_PDF_FRM_R_BG_COLOR',		'INFRASPLUS_PDF_FRM_R_TEXT_COLOR'),
							'Body'		=> array('INFRASPLUS_PDF_BACKGROUND_COLOR',			'INFRASPLUS_PDF_TEXT_COLOR',
												'INFRASPLUS_PDF_TBL_LINE_COLOR',			'INFRASPLUS_PDF_HOR_LINE_COLOR',			'INFRASPLUS_PDF_VER_LINE_COLOR',
												'INFRASPLUS_PDF_BODY_SUBTI_COLOR',			'INFRASPLUS_PDF_TEXT_SUBTI_COLOR',			'INFRASPLUS_PDF_TEXT_SUBTO_COLOR',		'INFRASPLUS_PDF_DESC_SUBTO_COLOR',
												'INFRASPLUS_PDF_BODY_SUBTO_COLOR',
												'INFRASPLUS_PDF_BODY_OUV_COLOR',			'INFRASPLUS_PDF_TEXT_OUV_COLOR',			'INFRASPLUS_PDF_DESC_FULL_LINE_COLOR',	'INFRASPLUS_PDF_DESC_PERIOD_COLOR'),
							'Foot'		=> array('INFRASPLUS_PDF_SIGN_LINE_COLOR', 			'INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR'),
							'FootP'		=> [],
							'CGx'		=> []
							);
		$confkey	= $reg[1];
		$error		= 0;
		foreach ($list[$confkey] as $constname) {
			$constvalue	= $constname == 'INFRASPLUS_PDF_ROUNDED_REC' ? (GETPOST($constname, 'alpha') == 0 ? 0.001 : GETPOST($constname, 'alpha')) : GETPOST($constname, 'none');
			if ($constname == 'INFRASPLUS_PDF_EXF_PROD_POS') {
				if (!empty($constvalue) && infraspackplus_check_extf_name ($constvalue) < 0) {
					$result = 0;
					continue;
				}
				$name	= getDolGlobalString('INFRASPLUS_PDF_EXF_PROD_POS', '');
				if (empty($constvalue) || !empty($name) && $name != $constvalue) {
					$result	= infraspackplus_search_extf (-2, '', 'INFRASPLUS_PDF_EXF_PROD_POS', 'InfraSPlusParamLabelExfProdPos', array('expedition'), $listParamsExfProdPos);
				}
			}
			// Pour les préfixes, on remplace les espaces par des underscores et on ajoute un underscore à la fin si nécessaire
			if (strpos($constname, 'INFRASPLUS_PDF_ADD_PREFIX_TO_') === 0) {
				$constvalue = preg_replace('/\s+/', '_', trim(GETPOST($constname, 'alpha')));
				if (!empty($constvalue) && substr($constvalue, -1) !== '_') {
					$constvalue .= '_';
				}
			}
			$result	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		foreach ($listcolor[$confkey] as $constname) {
			$constvalue	= implode(', ', colorStringToArray(GETPOST($constname, 'alpha')));
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			// Sub total background color management
			if ($constname == 'INFRASPLUS_PDF_BODY_SUBTO_COLOR') {
				if ($constvalue != '255, 255, 255') {
					$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HIDE_BODY_SUBTO', '0', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
					$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', '0', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
				} else {
					$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', '1', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
				}
			}
		}
		if ($confkey == 'Gen') {
			$result		= dolibarr_set_const($db, 'INFRASPLUS_PDF_FONT', GETPOST('defaultfont'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			$resultTest	= infraspackplus_test_font();
			if (empty($resultTest)) {
				setEventMessages($langs->trans('InfraSPlusParamTestFondKO'), [], 'errors');
			}
		}
		if ($confkey == 'Template') {
			$result	= infraspackplus_Change_Template();
		}
	}
	// Comportement général -> génération automatique, 1 fichier par modèle
	// Apparence générale -> police, couleur de texte, style des en-têtes et des cadres, fond, symbol monétaire
	if ($action == 'setfont') {
		$extension	= pathinfo($_FILES['fontfile']['name'], PATHINFO_EXTENSION);
		if ($extension == 'ttf' || $extension == 'TTF') {
			$pathfonts	= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/fonts';
			$pathTTFs	= $pathfonts.'/ttf/';
			$fontfile	= $_FILES['fontfile']['tmp_name'];
			$dest_file	= $_FILES['fontfile']['name'];
			$moved		= dol_move_uploaded_file($fontfile, $pathTTFs.$dest_file, 1, 0, $_FILES['fontfile']['error']);
			if ($moved > 0) {
				$outpath	= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/tmp/';
				$fontname	= infraspackplus_Add_TCPDF_Font ('TrueTypeUnicode', '', 32, $outpath, 3, 1, true, false, $pathTTFs.$dest_file);
				if ($fontname === false) {
					setEventMessages($langs->trans('InfraSPlusParamAddFontKo', $fontname), [], 'errors');
				} else {
					dolCopyDir($outpath, $pathfonts, 0, 1);
					array_map('unlink', glob($outpath.'*'));
					setEventMessages($langs->trans('InfraSPlusParamAddFontOk', $fontname), [], 'mesgs');
				}
			} else {
				setEventMessages($langs->trans('InfraSPlusParamAddFontKo', $fontname), [], 'errors');
			}
		} else {
			setEventMessages($langs->trans('InfraSPlusParamAddTTFKo', $_FILES['fontfile']['name']), [], 'errors');
		}
	}
	// Haut de page -> cadres, contenu des en-têtes, adresses, note, pliage, filigrame, dommées additionnelles (douanes)
	// Contenu, colonnage -> Colonnes additionnelles et masquées (référence, tva, remises), taille et position
	if ($action == 'setExfProdPos') {
		$result	= infraspackplus_search_extf (1, '', 'INFRASPLUS_PDF_EXF_PROD_POS', 'InfraSPlusParamLabelExfProdPos', array('expedition'), $listParamsExfProdPos);
	}
	// Pied de document -> encours, total des remises, multi-devises, number-words, zones de signature, mentions complémentaires
	if (getDolGlobalString('INFRASPLUS_PDF_NUMBER_WORDS', '') && !in_array('numberwords', $conf->modules)) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_NUMBER_WORDS', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		setEventMessages($langs->trans('InfraSPlusParamErrorNumWords'), [], 'errors');
	}
	if ($action == 'modifyPaySpec') {
		$result	= infraspackplus_modify_paiement_spec ();
	}
	// Pied de page -> Lignes d'informations supplémentaires, n° de page, LCR
	if (preg_match('/set_INFRASPLUS_PDF_TYPE_FOOT_(.*)/', $action, $reg) || preg_match('/set_INFRASPLUS_PDF_HIDE_RECEP_FRAME/', $action, $reg2)) {
		$footAdress		= getDolGlobalString('INFRASPLUS_PDF_HIDE_RECEP_FRAME', '') ? '1' : dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS', $conf->entity);
		$footContacts	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_CONTACTS', $conf->entity);
		$footManager	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_MANAGER', $conf->entity);
		$footTypeSoc	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_TYPESOC', $conf->entity);
		$footIds		= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_IDS', $conf->entity);
		$footAdress2	= dolibarr_get_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS2', $conf->entity);
		$typefoot1		= $footAdress	? ($footContacts	? '3' : '1') : ($footContacts	? '2' : '0');	// 1er digit = 0 (no address nor contact) / 1 (address only) / 2 (contact only) /3 (address and contact)
		$typefoot2		= $footManager	? '1' : '0';	// 2ème digit = 0(no manager) / 1 (manager)
		$typefoot3		= $footTypeSoc	? ($footIds			? '3' : '1') : ($footIds		? '2' : '0');	// 3ème digit = 0 (no type nor IDs) / 1 (type only) / 2 (Ids only) /3 (type and IDs)
		$typefoot4		= $footAdress2	? '1' : '0';	// 4ème digit = 0(1 line for address) / 1 (2 lines for address)
		$typefoot		= $typefoot1.$typefoot2.$typefoot3.$typefoot4;
		$result			= dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT',	$typefoot, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Conditions générales -> vente, interventions, achats
	if ($action == 'addcgv') {
		$extension	= pathinfo($_FILES['CGVFile']['name'], PATHINFO_EXTENSION);
		$dest_file	= GETPOST('typeCG', 'alpha').'_'.GETPOST('CGVName', 'alpha').'.'.$extension;
		$moved		= dol_move_uploaded_file($_FILES['CGVFile']['tmp_name'], $cgxdir.'/'.$dest_file, 1, 0, $_FILES['CGVFile']['error']);
		if ($moved > 0) {
			setEventMessages($dest_file.' : '.$langs->trans('FileSaved'), [], 'mesgs');
		} else if ($moved !== 1) {	// errors
			if ($moved < 0) {
				setEventMessages('UknownFileUploadError', [], 'errors');	// API documented error
			} else {
				setEventMessages($moved, [], 'errors');	// We got an error string /o\
			}
		}
	}
	// Fichiers spéciaux
	if ($action == 'setpdf') {
		$extension	= pathinfo($_FILES['pdffile']['name'], PATHINFO_EXTENSION);
		if ($extension == 'pdf' || $extension == 'PDF') {
			$pdffile	= $_FILES['pdffile']['tmp_name'];
			$dest_file	= $_FILES['pdffile']['name'];
			$moved		= dol_move_uploaded_file($pdffile, $pdfsdir.'specialfiles/'.$dest_file, 1, 0, $_FILES['pdffile']['error']);
			if ($moved > 0) {
				setEventMessages($langs->trans('InfraSPlusParamAddFileOk', $dest_file), [], 'mesgs');
			} else {
				setEventMessages($langs->trans('InfraSPlusParamAddFileKo', $dest_file), [], 'errors');
			}
		} else {
			setEventMessages($langs->trans('InfraSPlusParamAddPDFKo', $_FILES['pdffile']['name']), [], 'errors');
		}
	}
	if ($action == 'setphp') {
		$extension	= pathinfo($_FILES['phpfile']['name'], PATHINFO_EXTENSION);
		if ($extension == 'php' || $extension == 'PHP') {
			$phpfile	= $_FILES['phpfile']['tmp_name'];
			$dest_file	= $_FILES['phpfile']['name'];
			$moved		= dol_move_uploaded_file($phpfile, $phpsdir.'/'.$dest_file, 1, 0, $_FILES['phpfile']['error']);
			if ($moved > 0) {
				setEventMessages($langs->trans('InfraSPlusParamAddFileOk', $dest_file), [], 'mesgs');
			} else {
				setEventMessages($langs->trans('InfraSPlusParamAddFileKo', $dest_file), [], 'errors');
			}
		} else {
			setEventMessages($langs->trans('InfraSPlusParamAddPHPKo', $_FILES['phpfile']['name']), [], 'errors');
		}
	}
	// Suppression CGV && Fichiers spéciaux
	if (((float) DOL_VERSION <= 14.0 && $action == 'delete') || ((float) DOL_VERSION >= 15.0 && $action == 'deletefile')) {
		$confirm_mesg	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?urlfile='.$urlfile.'&typefile='.$typefile, $langs->trans('InfraSPlusParamDeleteAFile'), $langs->trans('InfraSPlusParamConfirmDeleteAFile').' '.$urlfile.' ?', 'delete_ok', '', 1, (int) $conf->use_javascript_ajax);
	}
	if ($action == 'delete_ok' && $confirm == 'yes') {
		$urlfile_dirname	= pathinfo($urlfile, PATHINFO_DIRNAME);
		$urlfile_filename	= pathinfo($urlfile, PATHINFO_FILENAME);
		$urlfile_ext		= pathinfo($urlfile, PATHINFO_EXTENSION);
		$a					= dol_delete_file(($typefile == 'cgv' ? $cgxdir : ($typefile == 'pdf' ? $pdfsdir : $phpsdir)).$urlfile, 1);
		if (!empty($a)) {
			setEventMessages($langs->trans('InfraSPlusParamFileDeleted', $urlfile_filename.'.'.$urlfile_ext), [], 'mesgs');
		} else {
			setEventMessages($langs->trans('ErrorFailToDeleteFile', $urlfile), [], 'errors');
		}
	}
	// Retour => message Ok ou Ko
	if ($result == 1) {
		setEventMessages($langs->trans('SetupSaved'), [], 'mesgs');
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), [], 'errors');
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}

	// init variables *******************************
	// Sauvegarde / Restauration
	// Comportement général -> génération automatique, 1 fichier par modèle
	// Apparence générale -> police, couleur de texte, style des en-têtes et des cadres, fond
	$selected_font	= getDolGlobalString('INFRASPLUS_PDF_FONT', 'centurygothic');
	$dirfonts		= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/fonts';
	$listfonts		= dol_dir_list($dirfonts, 'files', 0, '\.php$', null, 'name', SORT_ASC, 0, 0, '', 0);
	$listfonttouse	= [];
	$name			= '';
	foreach ($listfonts as $font) {
		if (empty($font['name'])) {
			continue;
		}
		$fontname	= pathinfo($font['name'], PATHINFO_FILENAME);
		include_once ($font['fullname']);
		if ($name != '') {
			$listfonttouse[]	= array('name' => $name, 'fontname' => $fontname);
		}
		$name	= '';
	}
	// Haut de page -> cadres, contenu des en-têtes, adresses, note, pliage, filigrame, dommées additionnelles (douanes)
	$specialHead	= infraspackplus_fetchAllSpecialHeads();
	if (getDolGlobalString('INFRASPLUS_PDF_HEADER_AFTER_ADDR', '')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_SMALL_HEAD_2', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_NUM_CLI_FRM', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$INFRASPLUS_PDF_FRM_E_LINE_DASH	= getDolGlobalInt('INFRASPLUS_PDF_FRM_E_LINE_DASH', 0);
	if (getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_DASH_1', '')) {
		$inputFrmLineDash	= 'size = "2" value = "'.($inputFrmLineDashCleanValue ? '' : $INFRASPLUS_PDF_FRM_E_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_DASH_2', '')) {
		$inputFrmLineDash	= 'size = "5" value = "'.($inputFrmLineDashCleanValue ? '' : $INFRASPLUS_PDF_FRM_E_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_DASH_4', '')) {
		$inputFrmLineDash	= 'size = "8" value = "'.($inputFrmLineDashCleanValue ? '' : $INFRASPLUS_PDF_FRM_E_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_FRM_E_LINE_DASH_0',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_FRM_E_LINE_DASH',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputFrmLineDash	= 'size = "1" value = "" readonly';
	}
	$INFRASPLUS_PDF_FRM_R_LINE_DASH	= getDolGlobalInt('INFRASPLUS_PDF_FRM_R_LINE_DASH', 0) ? getDolGlobalInt('INFRASPLUS_PDF_FRM_R_LINE_DASH', 0) : '';
	if (getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_DASH_1', '')) {
		$inputFrmRLineDash	= 'size = "2" value = "'.($inputFrmRLineDashCleanValue ? '' : $INFRASPLUS_PDF_FRM_R_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_DASH_2', '')) {
		$inputFrmRLineDash	= 'size = "5" value = "'.($inputFrmRLineDashCleanValue ? '' : $INFRASPLUS_PDF_FRM_R_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_DASH_4', '')) {
		$inputFrmRLineDash	= 'size = "8" value = "'.($inputFrmRLineDashCleanValue ? '' : $INFRASPLUS_PDF_FRM_R_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_FRM_R_LINE_DASH_0',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_FRM_R_LINE_DASH',	0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputFrmRLineDash	= 'size = "1" value = "" readonly';
	}
	if (getDolGlobalString('INFRASPLUS_PDF_HEIGHT_HEAD_SEP', '') < 60) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HEIGHT_HEAD_SEP',	60, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Contenu, colonnage -> Colonnes additionnelles et masquées (référence, tva, remises), taille et position
	$INFRASPLUS_PDF_TBL_LINE_DASH	= getDolGlobalInt('INFRASPLUS_PDF_TBL_LINE_DASH', 0) ? getDolGlobalInt('INFRASPLUS_PDF_TBL_LINE_DASH', 0) : '';
	if (getDolGlobalString('INFRASPLUS_PDF_TBL_LINE_DASH_1', '')) {
		$inputTblLineDash	= 'size = "2" value = "'.($inputTblLineDashCleanValue ? '' : $INFRASPLUS_PDF_TBL_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_TBL_LINE_DASH_2', '')) {
		$inputTblLineDash	= 'size = "5" value = "'.($inputTblLineDashCleanValue ? '' : $INFRASPLUS_PDF_TBL_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_TBL_LINE_DASH_4', '')) {
		$inputTblLineDash	= 'size = "8" value = "'.($inputTblLineDashCleanValue ? '' : $INFRASPLUS_PDF_TBL_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_TBL_LINE_DASH_0',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_TBL_LINE_DASH',		0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputTblLineDash	= 'size = "1" value = "" readonly';
	}
	// Subtotal background color management
	$txtSubtoColorSubti	= getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI') ? ' '.$langs->trans('InfraSPlusParamBodySubTiColor1') : '';
	if ((!getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR') || getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR') == '255,255,255') && !getDolGlobalString('INFRASPLUS_PDF_HIDE_BODY_SUBTO') && !getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_HIDE_BODY_SUBTO')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_HIDE_BODY_SUBTO', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_HIDE_LABEL', '')) {
		dolibarr_set_const($db, 'MAIN_GENERATE_DOCUMENTS_HIDE_DESC', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalInt('INFRASPLUS_PDF_DIM_C2D', 0) && getDolGlobalInt('INFRASPLUS_PDF_LARG_BC', 0)) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_DIM_C2D', getDolGlobalInt('INFRASPLUS_PDF_DIM_C2D', 0) > getDolGlobalInt('INFRASPLUS_PDF_LARG_BC', 0) ? getDolGlobalInt('INFRASPLUS_PDF_LARG_BC', 0) : getDolGlobalInt('INFRASPLUS_PDF_DIM_C2D', 0), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (isModEnabled('management')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_SHOW_DATES_HOURS_FI', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (!getDolGlobalString('PRODUIT_CUSTOMER_PRICES', '')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_DISCOUNT_AUTO', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_DISCOUNT_AUTO', '')) {
		$infoDiscountAuto	= ' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamDiscountAutoInfo');
		dolibarr_set_const($db, 'INFRASPLUS_PDF_HIDE_UP',				0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_HIDE_DISCOUNT',			0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_SHOW_UP_DISCOUNTED',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	} else {
		$infoDiscountAuto	= '';
	}
	$workBullet		= getDolGlobalString('INFRASPLUS_PDF_OUVRAGE_BULLET', '¤');
	$alignLeftRef	= getDolGlobalString('INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF', 'L');
	$alignLeftUnit	= getDolGlobalString('INFRASPLUS_PDF_FORCE_ALIGN_LEFT_UNIT', 'L');
	if (!getDolGlobalString('INFRASPLUS_PDF_DESC_FULL_LINE', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN', '')) {
		$result	= dolibarr_set_const($db, 'MAIN_GENERATE_DOCUMENTS_HIDE_REF', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
    if (getDolGlobalString('INFRASPLUS_PDF_SHOW_DESC_DEV', '')) {
        $result	= dolibarr_set_const($db, 'MAIN_GENERATE_DOCUMENTS_HIDE_DESC', dolibarr_get_const($db, 'INFRASPLUS_PDF_SHOW_DESC_DEV', $conf->entity), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
    }
	if (getDolGlobalString('INFRASPLUS_PDF_HIDE_DISCOUNT', '')) {
		if (getDolGlobalString('INFRASPLUS_PDF_SHOW_UP_DISCOUNTED', '')) {
			setEventMessages($langs->trans('InfraSPlusParamShowUPDiscountedKo1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamShowUPDiscountedKo2').'</span> '.$langs->trans('InfraSPlusParamShowUPDiscountedKo3').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamShowUPDiscountedKo4').'</span> !', [], 'warnings');
		}
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_SHOW_UP_DISCOUNTED', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_WITH_TTC_COLUMN', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_TTC_WITH_VAT_TOT', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_ONLY_TTC', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HT_BY_VAT_P_OR_S', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_ONLY_HT', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HT_BY_VAT_P_OR_S', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (getDolGlobalString('INFRASPLUS_PDF_USE_TVA_FORFAIT', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITH_TTC_COLUMN', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_TTC_WITH_VAT_TOT',	 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_HT_BY_VAT_P_OR_S', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$country			= !empty($mysoc->country_code)					? $mysoc->country_code	: substr($langs->defaultlang, -2);
	$TVAforfaitaire		= $country == 'CH' && empty($mysoc->tva_assuj)	? 1						: 0;
	$listselect			= array(array('select' => 'INFRASPLUS_PDF_NUMCOL_REF',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_REF', 1)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_DESC',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_DESC', 2)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_QTY',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_QTY', 3)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_UNIT',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_UNIT', 4)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_UP',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_UP', 5)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_TVA',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_TVA', 6)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_DISC',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_DISC', 7)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_UPD',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_UPD', 8)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_PROGRESS',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_PROGRESS', 9)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_TOTAL',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_TOTAL', 10)),
								array('select' => 'INFRASPLUS_PDF_NUMCOL_TOTAL_TTC',	'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOL_TOTAL_TTC', 11)));
	$listcol			= array($langs->transnoentities(getDolGlobalString('INFRASPLUS_PDF_WITH_NUM_COLUMN', '') ? 'PDFInfraSPlusNum' : 'Ref'),
								$langs->transnoentities('Designation'),
								$langs->transnoentities('Qty'),
								$langs->transnoentities('Unit'),
								$langs->transnoentities('PriceU'),
								$langs->transnoentities('VAT'),
								$langs->transnoentities('ReductionShort'),
								$langs->transnoentities('PDFInfraSPlusDiscountedPrice'),
								'('.$langs->transnoentities('Situation').')*',
								$langs->transnoentities(getDolGlobalString('INFRASPLUS_PDF_TTC_WITH_VAT_TOT', '') || getDolGlobalString('INFRASPLUS_PDF_ONLY_TTC', '') ? 'TotalTTC' : 'TotalHT'),
								$langs->transnoentities(getDolGlobalString('INFRASPLUS_PDF_WITH_TTC_COLUMN', '') ? 'TotalTTC' : '-'));
	$listlarg			= array(array('key' => 'INFRASPLUS_PDF_LARGCOL_REF',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_REF', 28)),
								array('key' => 'DESC',									'value' => 0),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_QTY',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_QTY', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_UNIT',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_UNIT', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_UP',				'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_UP', 22)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_TVA',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_TVA', 14)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_DISC',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_DISC', 14)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_UPD',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_UPD', 22)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_PROGRESS',		'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_PROGRESS', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_TOTAL',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_TOTAL', 24)),
								array('key' => 'INFRASPLUS_PDF_LARGCOL_TOTAL_TTC',		'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOL_TOTAL_TTC', 24)));
	$exfProdPosIsSet	= infraspackplus_search_extf (0, '', 'INFRASPLUS_PDF_EXF_PROD_POS', 'InfraSPlusParamLabelExfProdPos', array('expedition'), $listParamsExfProdPos);
	$textDescProdPos	= $langs->trans('InfraSPlusParamSetExf', getDolGlobalString('INFRASPLUS_PDF_EXF_PROD_POS', ''));
	$descProdPos		= $langs->trans('InfraSPlusParamEXFprodPos').($exfProdPosIsSet == 0 ? ' <button class = "button infrasplusheight20 infrasplusnopadding" type = "submit" value = "setExfProdPos" name = "action">'.$textDescProdPos.'</button>': '');
	$listselectBL		= array(array('select' => 'INFRASPLUS_PDF_NUMCOLBL_REF',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_REF', 1)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_EFL',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_EFL', 2)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_DESC',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_DESC', 3)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_WV',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_WV', 4)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_UNIT',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_UNIT', 5)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_ORDERED',	'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_ORDERED', 6)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_REL',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_REL', 7)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_QTY',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_QTY', 8)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBL_PRICE',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBL_PRICE', 9)));
	$listcolBL			= array($langs->transnoentities(getDolGlobalString('INFRASPLUS_PDF_BL_WITH_BC_COLUMN', '') ? 'PDFInfraSPlusCB' : 'Ref'),
								$langs->transnoentities('InfraSPlusParamColEFLBL'),
								$langs->transnoentities('Designation'),
								$langs->transnoentities('WeightVolShort'),
								$langs->transnoentities('Unit'),
								$langs->transnoentities('Ordered'),
								$langs->transnoentities('PDFInfraSPlusExpeditionbackorder'),
								$langs->transnoentities('QtyShippedShort'),
								$langs->transnoentities('TotalHT'));
	$listlargBL			= array(array('key' => 'INFRASPLUS_PDF_LARGCOLBL_REF',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_REF', 28)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBL_EFL',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_EFL', 14)),
								array('key' => 'DESC',									'value' => 0),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBL_WV',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_WV', 22)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBL_UNIT',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_UNIT', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBL_ORDERED',		'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_ORDERED', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBL_REL',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_REL', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBL_QTY',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_QTY', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBL_PRICE',		'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBL_PRICE', 24)));
	$listselectBR		= array(array('select' => 'INFRASPLUS_PDF_NUMCOLBR_REF',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBR_REF', 1)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBR_DESC',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBR_DESC', 2)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBR_COMM',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBR_COMM', 3)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBR_UNIT',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBR_UNIT', 4)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBR_ORDERED',	'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBR_ORDERED', 5)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBR_REL',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBR_REL', 6)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLBR_QTY',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLBR_QTY', 7)));
	$listcolBR			= array($langs->transnoentities(getDolGlobalString('INFRASPLUS_PDF_BL_WITH_BC_COLUMN', '') ? 'PDFInfraSPlusCB' : 'Ref'),
								$langs->transnoentities('Designation'),
								$langs->transnoentities('Comments'),
								$langs->transnoentities('Unit'),
								$langs->transnoentities('Ordered'),
								$langs->transnoentities('PDFInfraSPlusExpeditionbackorder'),
								$langs->transnoentities('QtyShippedShort'));
	$listlargBR			= array(array('key' => 'INFRASPLUS_PDF_LARGCOLBR_REF',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBR_REF', 28)),
								array('key' => 'DESC',									'value' => 0),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBR_COMM',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBR_COMM', 50)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBR_UNIT',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBR_UNIT', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBR_ORDERED',		'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBR_ORDERED', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBR_REL',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBR_REL', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLBR_QTY',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLBR_QTY', 10)));
	$listselectST		= array(array('select' => 'INFRASPLUS_PDF_NUMCOLST_REF',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_REF', 1)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLST_DESC',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_DESC', 2)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLST_QTY',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_QTY', 3)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLST_PMP',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_PMP', 4)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLST_PMPT',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_PMPT', 5)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLST_PU',			'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_PU', 6)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLST_TOT',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLST_TOT', 7)));
	$listcolST			= array($langs->transnoentities('Ref'),
								$langs->transnoentities('Designation'),
								$langs->transnoentities('Qty'),
								$langs->transnoentities('AverageUnitPricePMPShort'),
								$langs->transnoentities('EstimatedStockValueShort'),
								$langs->transnoentities('SellPriceMin'),
								$langs->transnoentities('EstimatedStockValueSellShort'));
	$listlargST			= array(array('key' => 'INFRASPLUS_PDF_LARGCOLST_REF',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_REF', 28)),
								array('key' => 'DESC',									'value' => 0),
								array('key' => 'INFRASPLUS_PDF_LARGCOLST_QTY',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_QTY', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLST_PMP',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_PMP', 22)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLST_PMPT',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_PMPT', 24)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLST_PU',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_PU', 22)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLST_TOT',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLST_TOT', 24)));
	$listselectMRP		= array(array('select' => 'INFRASPLUS_PDF_NUMCOLMRP_REF',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_REF', 1)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLMRP_DESC',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_DESC', 2)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLMRP_QTY',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_QTY', 3)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLMRP_QTYTOT',	'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_QTYTOT', 4)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLMRP_UNIT',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_UNIT', 5)),
								array('select' => 'INFRASPLUS_PDF_NUMCOLMRP_DIM',		'value' => getDolGlobalInt('INFRASPLUS_PDF_NUMCOLMRP_DIM', 6)));
	$listcolMRP			= array($langs->transnoentities('Ref'),
								$langs->transnoentities('Designation'),
								$langs->transnoentities('Qty'),
								$langs->transnoentities('QtyTot'),
								$langs->transnoentities('Unit'),
								$langs->transnoentities('Size'));
	$listlargMRP		= array(array('key' => 'INFRASPLUS_PDF_LARGCOLMRP_REF',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_REF', 28)),
								array('key' => 'DESC',									'value' => 0),
								array('key' => 'INFRASPLUS_PDF_LARGCOLMRP_QTY',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_QTY', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLMRP_QTYTOT',		'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_QTYTOT', 22)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLMRP_UNIT',		'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_UNIT', 10)),
								array('key' => 'INFRASPLUS_PDF_LARGCOLMRP_DIM',			'value' => getDolGlobalInt('INFRASPLUS_PDF_LARGCOLMRP_DIM', 24)));
	$marge_gauche		= getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
	$marge_droite		= getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
	$formatarray		= pdf_InfraSPlus_getFormat();
	$largutil			= $marge_gauche + $marge_droite;
	foreach ($listlarg as $largs) {
		if ($largs['key'] != 'DESC') {
			$largutil	+= $largs['value'];
		}
	}
	$larg_desc_progress		= $formatarray['width'] - $largutil;
	$larg_desc				= $larg_desc_progress + $listlarg[8]['value'];
	$listlarg[1]['value']	= $larg_desc.' / ('.$larg_desc_progress.')*';
	$largutilBL				= $marge_gauche + $marge_droite;
	foreach ($listlargBL as $largsBL) {
		if ($largsBL['key'] != 'DESC') {
			$largutilBL	+= $largsBL['value'];
		}
	}
	$listlargBL[2]['value']	= $formatarray['width'] - $largutilBL;
	$largutilBR				= $marge_gauche + $marge_droite;
	foreach ($listlargBR as $largsBR) {
		if ($largsBR['key'] != 'DESC') {
			$largutilBR	+= $largsBR['value'];
		}
	}
	$listlargBR[1]['value']	= $formatarray['width'] - $largutilBR;
	$largutilST				= 0;
	foreach ($listlargST as $largsST) {
		if ($largsST['key'] != 'DESC') {
			$largutilST	+= $largsST['value'];
		}
	}
	$listlargST[1]['value']	= $formatarray['width'] - $largutilST;
	$largutilMRP			= 0;
	foreach ($listlargMRP as $largsMRP) {
		if ($largsMRP['key'] != 'DESC') {
			$largutilMRP	+= $largsMRP['value'];
		}
	}
	$listlargMRP[1]['value']	= $formatarray['width'] - $largutilMRP;
	$wvccopt					= 0;
	if (!getDolGlobalString('PRODUCT_DISABLE_CUSTOM_INFO', '')) {
		$wvccopt++;
	}
	if (!getDolGlobalString('PRODUCT_DISABLE_LENGTH', '')) {
		$wvccopt++;
	}
	if (!getDolGlobalString('PRODUCT_DISABLE_SIZE', '')) {
		$wvccopt++;
	}
	if (!getDolGlobalString('PRODUCT_DISABLE_SURFACE', '')) {
		$wvccopt++;
	}
	if (!getDolGlobalString('PRODUCT_DISABLE_VOLUME', '')) {
		$wvccopt++;
	}
	if (!getDolGlobalString('PRODUCT_DISABLE_WEIGHT', '')) {
		$wvccopt++;
	}
	$selectedUserStickerFormat = getDolGlobalString('INFRASPLUS_PDF_USER_STICKER_FORMAT','CARD');
	// Pied de document -> encours, total des remises, multi-devises, number-words, zones de signature, mentions complémentaires
	if (getDolGlobalString('INFRASPLUS_PDF_HT_BY_VAT_P_OR_S', '')) {
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_TTC', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_ONLY_HT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$result	= dolibarr_set_const($db, 'INFRASPLUS_PDF_USE_TVA_FORFAIT', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	if (! in_array('numberwords', $conf->modules)) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_NUMBER_WORDS', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$INFRASPLUS_PDF_SIGN_LINE_DASH = getDolGlobalInt('INFRASPLUS_PDF_SIGN_LINE_DASH', 0) ? getDolGlobalInt('INFRASPLUS_PDF_SIGN_LINE_DASH', 0) : '';
	if (getDolGlobalString('INFRASPLUS_PDF_SIGN_LINE_DASH_1', '')) {
		$inputSignLineDash	= 'size = "2" value = "'.($inputSignLineDashCleanValue ? '' : $INFRASPLUS_PDF_SIGN_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W" pattern = "^(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_SIGN_LINE_DASH_2', '')) {
		$inputSignLineDash	= 'size = "5" value = "'.($inputSignLineDashCleanValue ? '' : $INFRASPLUS_PDF_SIGN_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} elseif (getDolGlobalString('INFRASPLUS_PDF_SIGN_LINE_DASH_4', '')) {
		$inputSignLineDash	= 'size = "8" value = "'.($inputSignLineDashCleanValue ? '' : $INFRASPLUS_PDF_SIGN_LINE_DASH).'" title = "'.$langs->trans('InfraSPlusParamLineDashTitle').'" required = "required" placeholder = "W,X,Y,Z" pattern = "^(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30),(0?[1-9]|[1-2][0-9]|30)$"';
	} else {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_SIGN_LINE_DASH_0', 1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_SIGN_LINE_DASH', 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		$inputSignLineDash	= 'size = "1" value = "" readonly';
	}
	// Pied de page -> Lignes d'informations supplémentaires, n° de page, LCR
	$typefoot	= getDolGlobalString('INFRASPLUS_PDF_TYPE_FOOT', '0000');
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS', (substr($typefoot, 0, 1) == 1 || substr($typefoot, 0, 1) == 3) ? 1 : 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_CONTACTS', (substr($typefoot, 0, 1) == 2 || substr($typefoot, 0, 1) == 3) ? 1 : 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_MANAGER', substr($typefoot, 1, 1) == 1 ? 1 : 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_TYPESOC', (substr($typefoot, 2, 1) == 1 || substr($typefoot, 2, 1) == 3) ? 1 : 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_IDS', (substr($typefoot, 2, 1) == 2 || substr($typefoot, 2, 1) == 3) ? 1 : 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	dolibarr_set_const($db, 'INFRASPLUS_PDF_TYPE_FOOT_ADDRESS2', substr($typefoot, 3, 1) == 1 ? 1 : 0, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	// Conditions générales -> vente, interventions, achats
	$selectCG	= array('CGV' => $langs->trans('InfraSPlusParamTypeCGV'), 'CGI' => $langs->trans('InfraSPlusParamTypeCGI'), 'CGA' => $langs->trans('InfraSPlusParamTypeCGA'));
	$CGVs		= infraspackplus_get_CGfiles ('CGV', $conf->entity);
	$CGIs		= infraspackplus_get_CGfiles ('CGI', $conf->entity);
	$CGAs		= infraspackplus_get_CGfiles ('CGA', $conf->entity);
	if (getDolGlobalString('MAIN_MULTILANGS', '') && getDolGlobalString('INFRASPLUS_PDF_CGV_FROM_LANG', '')) {
		dolibarr_set_const($db, 'INFRASPLUS_PDF_CGV', infraspackplus_get_CGfiles_lang ($CGVs, getDolGlobalString('MAIN_LANG_DEFAULT', '')), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_CGI', infraspackplus_get_CGfiles_lang ($CGIs, getDolGlobalString('MAIN_LANG_DEFAULT', '')), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		dolibarr_set_const($db, 'INFRASPLUS_PDF_CGA', infraspackplus_get_CGfiles_lang ($CGAs, getDolGlobalString('MAIN_LANG_DEFAULT', '')), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	$selected_cgv	= getDolGlobalString('INFRASPLUS_PDF_CGV', '');
	$selected_cgi	= getDolGlobalString('INFRASPLUS_PDF_CGI', '');
	$selected_cga	= getDolGlobalString('INFRASPLUS_PDF_CGA', '');
	// Fichiers spéciaux
	$listpdfs			= dol_dir_list($pdfsdir.'specialfiles', 'files', 0, '\.pdf$', null, 'name', SORT_ASC, 1, 0, '', 0);
	$listpdftouse		= [];
	$listphps			= dol_dir_list($phpsdir, 'files', 0, '\.php$', null, 'name', SORT_ASC, 1, 0, '', 0);
	$listspecialfiles	= array_column($listphps, 'name');
	foreach ($listpdfs as $pdf) {
		if (empty($pdf['name'])) {
			continue;
		}
		$pdfname	= pathinfo($pdf['name'], PATHINFO_FILENAME);
		if (in_array($pdfname.'.php', $listspecialfiles)) {
			$listpdftouse[]	= $pdfname;
		}
	}
	dolibarr_set_const($db, 'INFRASPLUS_PDF_SPECIAL_FILES', (count($listpdftouse) > 0 ? implode(',', $listpdftouse) : ''), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);

	// View *****************************************
	$page_name		= $langs->trans('infrasplussetup').' - '.$langs->trans('InfraSPlusParamsPDF');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= img_picto($langs->trans('Setup'), 'setup', '', false, 0, 0, '', 'fa-15 paddingright10imp');

	// Configuration header *************************
	$head			= infraspackplus_admin_prepare_head();
	$picto			= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'infrasplussetup', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "infraspackplus_tblPSexp";
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar) {
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie(cookieName))) {
							tblPSexp = $.cookie(cookieName);
						}
						$(".toggle_bloc").hide();
						if (tblPSexp) {
							$("[name=" + tblPSexp + "]").toggle();
						}
					});
					$(function () {
						$(".foldable .toggle_bloc_title").click(function() {
							if ($(this).siblings().is(":visible")) {
								$(".toggle_bloc").hide();
							} else {
								$(".toggle_bloc").hide();
								$(this).siblings().show();
							}
							$.cookie(cookieName, "", { expires: 1, path: "/" });
							$(".toggle_bloc").each(function() {
								if ($(this).is(":visible")) {
									$.cookie(cookieName, $(this).attr("name"), { expires: 1, path: "/" });
								}
							});
						});
						$(window).scroll(function() {
							if ($(this).scrollTop() > 200 )	{
								$(".infrasplusScrollUp").css("right", "30px");
							} else {
								$(".infrasplusScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
	}
	// Comportement général -> génération automatique, 1 fichier par modèle
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamTitleComp').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblCG" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 2;
		infraspackplus_print_btn_action('manage', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 3);
		infraspackplus_print_btn_action('Template', $langs->trans('InfraSPlusParamChangeTemplate'), 2, 'left', 'InfraSPlusParamChange', true);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SEMIAUTOUPDATE', 'on_off', $langs->trans('InfraSPlusParamSemiAutoUpdate'), '', [], 1, 1, '', $num);
		if (getDolGlobalInt('INFRASPLUS_PDF_SEMIAUTOUPDATE', 0)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_UPDATE_ON_NOTES_CHANGE', 'on_off', $langs->trans('InfraSPlusParamUpdateOnNotesChange'), '', [], 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_UPDATE_ON_EXF_CHANGE', 'on_off', $langs->trans('InfraSPlusParamUpdateOnExfChange'), '', [], 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_UPDATE_ON_FIELDS_CHANGE', 'on_off', $langs->trans('InfraSPlusParamUpdateOnFieldsChange'), '', [], 1, 1, '', $num);
		} else {
			$num	+= 3;
		}
		$num	= infraspackplus_print_input('MAIN_DISABLE_PDF_AUTOUPDATE', 'on_off', $langs->trans('InfraSPlusParamAutoUpdate'), '', [], 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_MULTI_FILES', 'on_off', $langs->trans('InfraSPlusParamMultiFiles'), '', [], 1, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_MULTI_FILES', '')) {
			print '		<tr><td colspan = "2" class = "center">'.$langs->trans('InfraSPlusParamMultiFilesText').'</td><td>&nbsp;</td></tr>';
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME', 'on_off', $langs->trans('InfraSPlusPDFAddPrefixToTemplateName'), '', [], 1, 1, '', $num);
		if (getDolGlobalInt('INFRASPLUS_PDF_ADD_PREFIX_TO_TEMPLATE_NAME', 0)) {
			foreach ($listModeles as $model) {
				if (!empty($listModelesModule[$model]) && isModEnabled($listModelesModule[$model])) {
					$num	= infraspackplus_print_input('INFRASPLUS_PDF_ADD_PREFIX_TO_'.$model, 'input', $langs->trans('InfraSPlusPDFAddPrefixTo'.$model), '', ['class' => 'centpercent width200'], 1, 1, '', $num);
				}
			}
		} else {
			$num	+= count($listModeles);
		}
		// $num = 41
		if (getDolGlobalString('INFRASPLUS_PDF_MULTI_FILES', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROJECT_TIMESTAMP', 'on_off', $langs->trans('InfraSPlusParamProjectTimeStamp'), '', [], 1, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FILES_FROM_PROJECT', 'on_off', $langs->trans('InfraSPlusParamFilesFromProject'), '', [], 1, 1, '', $num);
		if (isModEnabled('mrp')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FILES_FROM_BOM', 'on_off', $langs->trans('InfraSPlusParamFilesFromBom'), '', [], 1, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 44
		if (isModEnabled('expensereport')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FILES_FROM_EXPENSEREPORT', 'on_off', $langs->trans('InfraSPlusParamFilesFromExpensereport').' '.$langs->trans('InfraSPlusGenModif'), '', [], 1, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_WITH_RAW_PRICES', 'on_off', $langs->trans('InfraSPlusParamPropalWithRawPrices'), '', [], 1, 1, '', $num);
		if (getDolGlobalString('PRODUIT_PDF_MERGE_PROPAL', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PRODUIT_MERGE_PROPAL', 'on_off', $langs->trans('InfraSPlusParamProduitMergePropal') , '', [], 1, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PRODUIT_CHECK_MERGE_PROPAL_X2', 'on_off', $langs->trans('InfraSPlusParamCheckProduitMergePropalX2'), '', [], 1, 1, '', $num);
		} else {
			$num	+= 2;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_MERGE_PRODUCT_LINKS', 'on_off', $langs->trans('InfraSPlusParamMergeProductLinks'), '', [], 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DOC_SEPARATE', 'on_off', $langs->trans('InfraSPlusParamDocSeparate'), '', [], 1, 1, '', $num);
		if (isModEnabled('attestationtva')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FILES_FROM_ATTESTATIONTVA', 'on_off', $langs->trans('InfraSPlusParamFilesFromAttestationTVA'), '', [], 1, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 51
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '10', 'step' => '1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ROUNDING_UP', 'input', $langs->trans('InfraSPlusParamRoundingUP'), '', $metas, 1, 1, '&nbsp;', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '10', 'step' => '1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ROUNDING_TOT', 'input', $langs->trans('InfraSPlusParamRoundingTot'), '', $metas, 1, 1, '&nbsp;', $num);
		// $num = 53
	}
	print '			</table>
				</div>';
	// Apparence générale -> police, couleur de texte, style des en-têtes et des cadres, fond
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamTitleGen').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblAG" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		$metas	= array('type' => 'file', 'class' => 'flat centpercent', 'accept' => '.ttf', 'style' => 'padding: 0px; font-size: inherit; cursor: pointer;');
		$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "setfont" name = "action">'.$langs->trans('Add').'</button></td>';
		$num	= infraspackplus_print_input('fontfile', 'input', $langs->trans('InfraSPlusParamAddFont'), '', $metas, 1, 2, $end, $num);
		infraspackplus_print_btn_action('Gen', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 4);
		for ($i = 0; $i < count($listfonttouse); $i++) {
			$selectOptions[$listfonttouse[$i]['fontname']]	= $listfonttouse[$i]['name'];
		}
		$metas	= '<a class = "pictopreview documentpreview paddingright" href = "/document.php?modulepart=ecm&attachment=0&file=temp/TEST.pdf&entity='.$conf->entity.'" mime = "application/pdf" target = "_blank" ><span class = "fa fa-search-plus" style = "color: gray"></span></a>
					'.$form->selectarray('defaultfont', $selectOptions, $selected_font, 0, 0, 0, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"', 0, 0, 0, '', 'maxwidth200');
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamFont'), '', $metas, 1, 2, '', $num);
		$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_BODY_TEXT_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_BODY_TEXT_COLOR', '')) : []);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamBodyTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_BODY_TEXT_COLOR', '')), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_REFDATE_MERGE', 'on_off', $langs->trans('InfraSPlusParamRefDateMerge'), '', [], 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '5', 'step' => '0.001');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ROUNDED_REC', 'input', $langs->trans('InfraSPlusParamRoundedRec'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACTURE_PAID_WATERMARK', 'input', $langs->trans('InfraSPlusParamInvoicePaidMark'), '', [], 2, 1, '', $num);
		// $num = 7
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ENABLE_TEST_WATERMARK', 'input', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamEnableTestMark').'</span>', '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_EXF_PROPALPROV', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_PROV_WATERMARK', 'input', $langs->trans('InfraSPlusParamProvisionalWatermark'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_CUR_SYMB', 'on_off', $langs->trans('InfraSPlusParamCurSymb'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_SHOW_CUR_SYMB', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_INFO_CUR', 'on_off', $langs->trans('InfraSPlusParamInfoCur'), '', [], 2, 1, '', $num);
			$num++;
		} else {
			$num++;
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_CUR_SYMB_ON_TABLEAU_TOT', 'on_off', $langs->trans('InfraSPlusParamCurSymbOnTableauTot'), '', [], 2, 1, '', $num);
		}
		// $num = 12
	}
	print '			</table>
				</div>';
	// Haut de page -> cadres, contenu des en-têtes, adresses, note, pliage, filigrame, dommées additionnelles (douanes)
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamTitleHeader').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblHP" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Head', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 4);
		if (getDolGlobalString('INFRASPLUS_PDF_NT_USED_AS_COVER', '') == -1) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FIRST_PAGE_EMPTY', 'on_off', $langs->trans('InfraSPlusParamFirstPageEmpty'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPE_HEAD', 'input', $langs->trans('InfraSPlusParamSpecialHead'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SMALL_HEAD_2', 'on_off', $langs->trans('InfraSPlusParamSmallHead2').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').' '.$langs->trans('InfraSPlusParamSmallHead2Forced').'</span>', '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_INVOICE_TITLE_IF_DEPOSIT', 'input', $langs->trans('InfraSPlusParamInvoiceTitleIfDeposit'), '', [], 2, 1, '', $num);
		$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_HEADER_TEXT_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_HEADER_TEXT_COLOR', '')) : []);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEADER_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamHeaderTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_HEADER_TEXT_COLOR', '')), '', $metas, 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEADER_AFTER_ADDR', 'on_off', $langs->trans('InfraSPlusParamHeaderAfterAddr'), '', [], 2, 1, '', $num);
		// $num = 7
		if (!getDolGlobalString('INFRASPLUS_PDF_HEADER_AFTER_ADDR', '')) {
			$metas	= array('type' => 'number', 'class' => 'flat quatrevingtpercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '3', 'step' => '0.1');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TITLE_SIZE', 'input', $langs->trans('InfraSPlusParamTitleSize').$langs->trans('InfraSPlusParamFontSize'), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEADER_ALIGN_LEFT', 'on_off', $langs->trans('InfraSPlusParamHeaderAlignLeft'), '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CREATOR_IN_HEADER', 'on_off', $langs->trans('InfraSPlusParamCreatorHeader'), '', [], 2, 1, '', $num);
			$num++;
		} else {
			$num	+= 3;
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '10');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPACE_HEADERAFTER', 'input', $langs->trans('InfraSPlusParamSpaceBeforeHeaderAfter'), '', $metas, 2, 1, '&nbsp;mm', $num);
		}
		// $num = 11
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DATES_BR', 'on_off', $langs->trans('InfraSPlusParamDatesBR'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DATES_BOLD', 'on_off', $langs->trans('InfraSPlusParamDatesBold'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_DATES_BR', '')) {
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_FACT_DATEDUE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_FACT_DATEDUE_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FACT_DATEDUE_COLOR', 'color', $langs->trans('InfraSPlusParamFactDateDueColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_FACT_DATEDUE_COLOR', '')), '', $metas, 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_CF_SHOW_CREATION_DATE', 'on_off', $langs->trans('InfraSPlusParamCFshowCreationDate'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_REFD_FROM_CUSTOMER', 'on_off', $langs->trans('InfraSPlusParamRefDFromCustomer'), '', [], 2, 1, '', $num);
		// $num = 16
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_NO_DATE_LINKED', 'on_off', $langs->trans('InfraSPlusParamNoDateLinked'), '', [], 2, 1, '', $num);
		if (isModEnabled('propal')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_PROPAL', 'on_off', $langs->trans('InfraSPlusParamShowRefPropal'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('commande')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_ORDER', 'on_off', $langs->trans('InfraSPlusParamShowRefOrder'), '', [], 2, 1, '', $num);
			if (getDolGlobalString('INFRASPLUS_PDF_SHOW_REF_ORDER', '')) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_CUST_ON_ORDER', 'on_off', $langs->trans('InfraSPlusParamShowRefCustOnOrder'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
		} else {
			$num	+= 2;
		}
		// $num = 20
		if (isModEnabled('expedition')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_SHIPPING', 'on_off', $langs->trans('InfraSPlusParamShowRefShipping'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('contrat')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_CONTRACT', 'on_off', $langs->trans('InfraSPlusParamShowRefContract'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('ficheinter')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_FICHINTER', 'on_off', $langs->trans('InfraSPlusParamShowRefFichinter'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('projet')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_REF_PROJECT', 'on_off', $langs->trans('InfraSPlusParamShowRefProject'), '', [], 2, 1, '', $num);
			if (getDolGlobalString('INFRASPLUS_PDF_SHOW_REF_PROJECT', '')) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DESC_PROJECT', 'on_off', $langs->trans('InfraSPlusParamShowDescProject'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
		} else {
			$num	+= 2;
		}
		// $num = 25
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_LABELS_FRAMES', 'on_off', $langs->trans('InfraSPlusParamHideLabelsFrames', $langs->transnoentities('BillFrom'), $langs->transnoentities('BillTo')), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_RECEP_FRAME', 'on_off', $langs->trans('InfraSPlusParamHideRecepFrame'), '', [], 2, 1, '', $num);
		if (!getDolGlobalString('INFRASPLUS_PDF_HIDE_RECEP_FRAME', '') || !empty($specialHead['frameinfos'])) {
			// $num = 27
			infraspackplus_print_hr(4);
			$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
			$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamFrmELineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas		= [];
			$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
			$metas[1]	= array('INFRASPLUS_PDF_FRM_E_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
								'INFRASPLUS_PDF_FRM_E_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',		'Dash1.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
								'INFRASPLUS_PDF_FRM_E_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
								'INFRASPLUS_PDF_FRM_E_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',		'Dash4.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'));
			$end		= '<input type = "text" class = "flat quatrevingtpercent right infrasplusfontsizeinherit infrasplusnopadding" id = "INFRASPLUS_PDF_FRM_E_LINE_DASH" name = "INFRASPLUS_PDF_FRM_E_LINE_DASH" '.$inputFrmLineDash.'>';
			$num		= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamFrmELineDash'), $metas, 2, 200, $end, $num);
			$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_COLOR', '')) : []);
			$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamFrmELineColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_FRM_E_LINE_COLOR', '')), '', $metas, 2, 1, '', $num);
			$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
			$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_LINE_OPACITY', 'input', $langs->trans('InfraSPlusParamFrmELineOpacity'), '', $metas, 2, 1, '&nbsp;%', $num);
			$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_FRM_E_BG_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_FRM_E_BG_COLOR', '')) : []);
			$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_BG_COLOR', 'color', $langs->trans('InfraSPlusParamFrmEBgColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_FRM_E_BG_COLOR', '')), '', $metas, 2, 1, '', $num);
			$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
			$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_OPACITY', 'input', $langs->trans('InfraSPlusParamFrmEOpacity'), '', $metas, 2, 1, '&nbsp;%', $num);
			$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_FRM_E_TEXT_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_FRM_E_TEXT_COLOR', '')) : []);
			$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_E_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamFrmETextColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_FRM_E_TEXT_COLOR', '')), '', $metas, 2, 1, '', $num);
		} else {
			$num	+= 7;
		}
		// $num = 34
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamFrmRLineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= [];
		$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
		$metas[1]	= array('INFRASPLUS_PDF_FRM_R_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_FRM_R_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',		'Dash1.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_FRM_R_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_FRM_R_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',		'Dash4.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'));
		$end		= '<input type = "text" class = "flat quatrevingtpercent right infrasplusfontsizeinherit infrasplusnopadding" id = "INFRASPLUS_PDF_FRM_R_LINE_DASH" name = "INFRASPLUS_PDF_FRM_R_LINE_DASH" '.$inputFrmRLineDash.'>';
		$num		= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamFrmRLineDash'), $metas, 2, 200, $end, $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamFrmRLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_FRM_R_LINE_COLOR', '')), '', $metas, 2, 1, '', $num);
		$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_LINE_OPACITY', 'input', $langs->trans('InfraSPlusParamFrmRLineOpacity'), '', $metas, 2, 1, '&nbsp;%', $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_FRM_R_BG_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_FRM_R_BG_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_BG_COLOR', 'color', $langs->trans('InfraSPlusParamFrmRBgColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_FRM_R_BG_COLOR', '')), '', $metas, 2, 1, '', $num);
		$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100');
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_OPACITY', 'input', $langs->trans('InfraSPlusParamFrmROpacity'), '', $metas, 2, 1, '&nbsp;%', $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_FRM_R_TEXT_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_FRM_R_TEXT_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_FRM_R_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamFrmRTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_FRM_R_TEXT_COLOR', '')), '', $metas, 2, 1, '', $num);
		// $num = 41
		infraspackplus_print_hr(4);
		if (isModEnabled('product')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_ADR_PROD', 'on_off', $langs->trans('InfraSPlusParamshowAdrProd'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_NUM_CLI', 'on_off', $langs->trans('InfraSPlusParamshowNumCli'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_SHOW_NUM_CLI', '') && !getDolGlobalString('INFRASPLUS_PDF_HEADER_AFTER_ADDR', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_NUM_CLI_FRM', 'on_off', $langs->trans('InfraSPlusParamNumCliFrm'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 44
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT', 'on_off', $langs->trans('InfraSPlusParamshowCodeCliComp'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_SHOW_CODE_CLI_COMPT', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CODE_CLI_COMPT_FRM', 'on_off', $langs->trans('InfraSPlusParamCodeCliCompFrm'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 46
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PRJ_DATEO_IN_NOTE', 'on_off', $langs->trans('InfraSPlusParamPrjDateoNote'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FIRST_SALES_REP_IN_NOTE', 'on_off', $langs->trans('InfraSPlusParam1SalesRepNote'), '', [], 2, 1, '', $num);
		// $num = 48
		if (getDolGlobalInt('INFRASPLUS_PDF_FIRST_SALES_REP_IN_NOTE', 0)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FIRST_SALES_REP_BOLD', 'on_off', $langs->trans('InfraSPlusParam1SalesRepBold'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_LAST_NOTE_AS_TABLE', 'on_off', $langs->trans('InfraSPlusParam1LastNoteAsTable'), '', [], 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '10');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FOLD_MARK', 'input', $langs->trans('InfraSPlusParamFoldMark'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '60', 'max' => '100');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEIGHT_HEAD_SEP', 'input', $langs->trans('InfraSPlusParamHeightHeadSep'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '70', 'max' => '120');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_LEFT_RECEP_CORNER', 'input', $langs->trans('InfraSPlusParamLeftRecepCorner'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '40', 'max' => '80');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TOP_RECEP_CORNER', 'input', $langs->trans('InfraSPlusParamTopRecepCorner'), '', $metas, 2, 1, '&nbsp;mm', $num);
		// $num = 54
	}
	print '			</table>
				</div>';
	// Contenu, colonnage -> Colonnes additionnelles et masquées (référence, tva, remises), taille et position
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamTitleCorps').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblCC" class = "noborder toggle_bloc centpercent">';
	$metas		= array('30px', '*', '90px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas		= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num		= 1;
		infraspackplus_print_btn_action('Body', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 4);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_BACKGROUND_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_BACKGROUND_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_BACKGROUND_COLOR', 'color', $langs->trans('InfraSPlusParamBackgroundColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_BACKGROUND_COLOR', '')), '', $metas, 2, 1, '', $num);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_TITLE_BG', 'on_off', $langs->trans('InfraSPlusParamtTitleBackground'), '', [], 2, 1, '', $num);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_COLOR_AUTO', 'on_off', $langs->trans('InfraSPlusParamTextColorAuto'), '', [], 2, 1, '', $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_TEXT_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_TEXT_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_TEXT_COLOR', '')), '', $metas, 2, 1, '', $num);
		// $num = 5
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '4', 'max' => '20', 'step' => '0.1');
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_HEIGHT_TOP_TABLE', 'input', $langs->trans('InfraSPlusParamHeightTopTable1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamHeightTopTable2'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_TOP_TABLE', 'on_off', $langs->trans('InfraSPlusParamhidetoptable'), '', [], 2, 1, '', $num);
		// $num = 7
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_TBL_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamTblLineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= [];
		$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
		$metas[1]	= array('INFRASPLUS_PDF_TBL_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_TBL_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',	'Dash1.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_TBL_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_TBL_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',	'Dash4.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'));
		$end		= '<input type = "text" class = "flat quatrevingtpercent right infrasplusfontsizeinherit infrasplusnopadding" id = "INFRASPLUS_PDF_TBL_LINE_DASH" name = "INFRASPLUS_PDF_TBL_LINE_DASH" '.$inputTblLineDash.'>';
		$num		= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamTblLineDash'), $metas, 2, 200, $end, $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_TBL_LINE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_TBL_LINE_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_TBL_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamTblLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_TBL_LINE_COLOR', '')), '', $metas, 2, 1, '', $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_VER_LINE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_VER_LINE_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_VER_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamVerLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_VER_LINE_COLOR', '')), '', $metas, 2, 1, '', $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_HOR_LINE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_HOR_LINE_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_HOR_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamHorLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_HOR_LINE_COLOR', '')), '', $metas, 2, 1, '', $num);
		if (!getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES', '')) {
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '10');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LINESEP_HIGHT', 'input', $langs->trans('InfraSPlusParamLineSepHight'), '', $metas, 2, 1, '&nbsp;mm', $num);
		} else {
			$num++;
		}
		if (isModEnabled('subtotal') || isModEnabled('subtotals')) {	// Module ATM Subtotal ou module natif Sous-totaux de Dolibarr : mêmes réglages de rendu
			// $num = 13
			infraspackplus_print_hr(4);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTI_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTI_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_SUBTI_COLOR', 'color', $langs->trans('InfraSPlusParamBodySubTiColor').$txtSubtoColorSubti.' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTI_COLOR', '')), '', $metas, 2, 1, '', $num);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', 'color', $langs->trans('InfraSPlusParamTextSubTiColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '')), '', $metas, 2, 1, '', $num);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', 'color', $langs->trans('InfraSPlusParamTextSubToColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTO_COLOR', '')), '', $metas, 2, 1, '', $num);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_DESC_SUBTO_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_DESC_SUBTO_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_SUBTO_COLOR', 'color', $langs->trans('InfraSPlusParamDescSubToColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_DESC_SUBTO_COLOR', '')), '', $metas, 2, 1, '', $num);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_SUBTO_COLOR', 'color', $langs->trans('InfraSPlusParamBodySubToColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_BODY_SUBTO_COLOR', '')), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_BODY_SUBTO', 'on_off', $langs->trans('InfraSPlusParamHideBodySubTo'), '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_SUBTO_COLOR_SUBTI', 'on_off', $langs->trans('InfraSPlusParamBodySubToColorSubTi'), '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SUBTI_WITH_SUBTO', 'on_off', $langs->trans('InfraSPlusParamSubTiWithSubTo'), '', [], 2, 1, '', $num);
		} else {
			$num	+= 8;
		}
		if (isModEnabled('milestone')) {
			// $num = 21
			infraspackplus_print_hr(4);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', 'color', $langs->trans('InfraSPlusParamTextJalonColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_TEXT_SUBTI_COLOR', '')), '', $metas, 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('ouvrage')) {
			// $num = 22
			infraspackplus_print_hr(4);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_BODY_OUV_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_BODY_OUV_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BODY_OUV_COLOR', 'color', $langs->trans('InfraSPlusParamBodyOuvColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_BODY_OUV_COLOR', '')), '', $metas, 2, 1, '', $num);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_TEXT_OUV_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_TEXT_OUV_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_OUV_COLOR', 'color', $langs->trans('InfraSPlusParamTextOuvColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_TEXT_OUV_COLOR', '')), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TEXT_OUV_STYLE', 'input', $langs->trans('InfraSPlusParamTextOuvStyle'), '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_OUV_STYLE_STD', 'on_off', $langs->trans('InfraSPlusParamDescOuvStyleStd'), '', [], 2, 1, '', $num);
			$metas	= $form->selectarray('INFRASPLUS_PDF_OUVRAGE_BULLET', array('>>' => '>>', '>' => '>', '=>' => '=>', '¤' => '¤'), $workBullet, 1, 0, 0, '', 1, 0, 0, '', 'quatrevingtpercent');
			$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamOuvrageBullet'), '', $metas, 2, 1, '', $num);
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '10');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_OUVRAGE_DETAILSEP_HIGHT', 'input', $langs->trans('InfraSPlusParamOuvrageDetailSepHight'), '', $metas, 2, 1, '&nbsp;mm', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDDEN_OUV', 'on_off', $langs->trans('InfraSPlusParamHiddenOuv'), '', [], 2, 1, '', $num);
		} else {
			$num	+= 7;
		}
		// $num = 29
		infraspackplus_print_hr(4);
		if (isModEnabled('barcode')) {
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '5', 'max' => '20');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HT_BC', 'input', $langs->trans('InfraSPlusParamHtBC'), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '25', 'max' => '45', 'step' => '10');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LARG_BC', 'input', $langs->trans('InfraSPlusParamLargBC'), '', $metas, 2, 1, '&nbsp;mm', $num);
			$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '15', 'max' => '40');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DIM_C2D', 'input', $langs->trans('InfraSPlusParamDimC2D'), '', $metas, 2, 1, '&nbsp;mm', $num);
			infraspackplus_print_hr(4);
		} else {
			$num	+= 3;
		}
		// $num = 32
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_NUM_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowNumCol'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_REF_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowRefCol').' '.$langs->trans('InfraSPlusGenModif'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '') && isModEnabled('barcode')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_REF_WITH_GENCODE', 'on_off', $langs->trans('InfraSPlusParamRefColumnWithGencode'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (getDolGlobalString('INFRASPLUS_PDF_WITH_NUM_COLUMN', '') || getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '')) {
			$metas	= $form->selectarray('INFRASPLUS_PDF_FORCE_ALIGN_LEFT_REF', array('L' => 'Left', 'C' => 'Center', 'R' => 'Right'), $alignLeftRef, 0, 0, 0, '', 1, 0, 0, '', 'quatrevingtpercent');
			$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamForceAlignLeftRefColumn'), '', $metas, 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BOLD_REF', 'on_off', $langs->trans('InfraSPlusParamBoldRefColumn'), '', [], 2, 1, '', $num);
		} else {
			$num	+= 2;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowSupRefCol'), '', [], 2, 1, '', $num);
		// $num = 38
		if (getDolGlobalString('INFRASPLUS_PDF_EXF_ECOTAX', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_ECOTAX_ON_SUPPLIER_ORDER', 'on_off', $langs->trans('InfraSPlusParamEXFecoTaxOnSupplierOrder'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 39
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$metas	= $form->selectarray('INFRASPLUS_PDF_FORCE_ALIGN_LEFT_UNIT', array('L' => 'Left', 'C' => 'Center', 'R' => 'Right'), $alignLeftUnit, 0, 0, 0, '', 1, 0, 0, '', 'quatrevingtpercent');
			$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamForcealignLeftUnitColumn'), '', $metas, 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 40
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_FULL_LINE', 'on_off', $langs->trans('InfraSPlusParamDescriptionFullLine'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_DESC_FULL_LINE', '')) {
			$metas = array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100', 'step' => '1');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_FULL_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamDescFullLineWidth'), '', $metas, 2, 1, '&nbsp;&percnt;', $num);
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_DESC_FULL_LINE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_DESC_FULL_LINE_COLOR', '')) : []);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_FULL_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamDescFullLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_DESC_FULL_LINE_COLOR', '')), '', $metas, 2, 1, '', $num);
			infraspackplus_print_hr(4);
		} else {
			$num	+= 2;
		}
		// $num = 43
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '20', 'step' => '1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_PERIOD_FONT_SIZE','input', $langs->trans('InfraSPlusParamDescPeriodFontSize'),'', $metas, 2, 1, '&nbsp;pt', $num);
		$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_COLOR', '')) : []);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_PERIOD_COLOR','color',$langs->trans('InfraSPlusParamDescPeriodColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_DESC_PERIOD_COLOR', '')),'', $metas, 2, 1, '', $num);
		// $num = 45
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_DESC_CLEAN_FONT', 'on_off', $langs->trans('InfraSPlusParamDescriptionCleanFont'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_LABEL', 'on_off', $langs->trans('InfraSPlusParamHideLabel'), '', [], 2, 1, '', $num);
		if (!getDolGlobalString('INFRASPLUS_PDF_HIDE_LABEL', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DESC_DEV', 'on_off', $langs->trans('InfraSPlusParamShowDescDev'), '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_ONE_DESC', 'on_off', $langs->trans('InfraSPlusParamOnlyOneDesc1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamOnlyOneDesc2').'</span> '.$langs->trans('InfraSPlusParamOnlyOneDesc3'), '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_LABEL_BOLD', 'on_off', $langs->trans('InfraSPlusParamLabelBold1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamLabelBold2'), '', [], 2, 1, '', $num);
		} else {
			$num += 3;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXTRADET_SECOND', 'on_off', $langs->trans('InfraSPlusParamExtraDetSecond'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_SERVICE_DATES', 'on_off', $langs->trans('InfraSPlusParamServiceDates'), '', [], 2, 1, '', $num);
		// $num = 52
		if (isModEnabled('ficheinter')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_TIME_SPENT_FI', 'on_off', $langs->trans('InfraSPlusParamTimeSpentFI').' '.$langs->trans('InfraSPlusGenModif'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('management')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DATES_HOURS_FI', 'on_off', $langs->trans('InfraSPlusParamDatesHoursFI'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_QTY', 'on_off', $langs->trans('InfraSPlusParamHideQty'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_UP', 'on_off', $langs->trans('InfraSPlusParamHideUP').$infoDiscountAuto, '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_DISCOUNT', 'on_off', $langs->trans('InfraSPlusParamHideDiscount').' '.$langs->trans('InfraSPlusGenModif').$infoDiscountAuto, '', [], 2, 1, '', $num);
		// $num = 57
		if (!getDolGlobalString('INFRASPLUS_PDF_HIDE_DISCOUNT', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DISCOUNT_OPT', 'on_off', $langs->trans('InfraSPlusParamShowDiscountOpt'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_UP_DISCOUNTED', 'on_off', $langs->trans('InfraSPlusParamShowUPDiscounted').$infoDiscountAuto, '', [], 2, 1, '', $num);
		if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DISCOUNT_AUTO', 'on_off', $langs->trans('InfraSPlusParamDiscountAuto'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (getDolGlobalString('INVOICE_USE_SITUATION', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SITFAC_TOTLINE_AVT', 'on_off', $langs->trans('InfraSPlusParamSitFacTotLineAvt'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 61
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_TTC_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowTTCColumn'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', 'on_off', $langs->trans('InfraSPlusParamHideVATColumn1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamHideVATColumn2'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TTC_WITH_VAT_TOT', 'on_off', $langs->trans('InfraSPlusParamTTCWithVATTotal1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamTTCWithVATTotal2').'</span> '.$langs->trans('InfraSPlusParamTTCWithVATTotal3'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_TTC', 'on_off', $langs->trans('InfraSPlusParamHideAnyVATInformation'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_ONLY_HT', 'on_off', $langs->trans('InfraSPlusParamShowOlnyHT'), '', [], 2, 1, '', $num);
		// $num = 66
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_COLS', 'on_off', $langs->trans('InfraSPlusParamHideCols'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_PRICES_COL_DEVST', 'on_off', $langs->trans('InfraSPlusParamHidePricesColDevSt'), '', [], 2, 1, '', $num);
		if (!getDolGlobalInt('INFRASPLUS_PDF_HIDE_PRICES_COL_DEVST', 0)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_TOT_COL_DEVST', 'on_off', $langs->trans('InfraSPlusParamHideTotColDevSt'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 69
		if ($wvccopt > 0) {
			infraspackplus_print_hr(4);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_WVCC', 'on_off', $langs->trans('InfraSPlusParamShowWVCC1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamShowWVCC2'), '', [], 2, 1, '', $num);
			if (getDolGlobalString('INFRASPLUS_PDF_SHOW_WVCC', '')) {
				$num		= infraspackplus_print_input('INFRASPLUS_PDF_NO_SHOW_WVCC_SAME_COUNTRY', 'on_off', $langs->trans('InfraSPlusParamNoShowWVCCsameCountry'), '', [], 2, 1, '', $num);
				$num		= infraspackplus_print_input('INFRASPLUS_PDF_WVCC_NO_HR', 'on_off', $langs->trans('InfraSPlusParamWVCCnoHR'), '', [], 2, 1, '', $num);
				$metas		= [];
				$metas[0]	= array('MAIN_MODULE_PROPALE'		=> $langs->trans('Proposals'),
									'MAIN_MODULE_COMMANDE'		=> $langs->trans('Orders'),
									'MAIN_MODULE_EXPEDITION'	=> $langs->trans('SendingCard'),
									'MAIN_MODULE_FACTURE'		=> $langs->trans('Invoices'));
				$metas[1]	= array('INFRASPLUS_PDF_WVCC_BY_DEF_FOR_PROPOSALS'	=> 'MAIN_MODULE_PROPALE',
									'INFRASPLUS_PDF_WVCC_BY_DEF_FOR_ORDERS'		=> 'MAIN_MODULE_COMMANDE',
									'INFRASPLUS_PDF_WVCC_BY_DEF_FOR_EXPEDITION'	=> 'MAIN_MODULE_EXPEDITION',
									'INFRASPLUS_PDF_WVCC_BY_DEF_FOR_INVOICES'	=> 'MAIN_MODULE_FACTURE');
				$num	= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamTypeDoc').'&nbsp;'.$langs->trans('InfraSPlusParamShowWVCCbyDef'), $metas, 3, 120, '', $num);
			} else {
				$num	+= 3;
			}
		} else {
			$num	+= 4;
		}
		if (isModEnabled('productbatch')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SERIAL_ON_INVOICE', 'on_off', $langs->trans('InfraSPlusParamShowSerialOnInvoice'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 74
		infraspackplus_print_hr(4);
		print '			<tr class = "oddeven">
							<td class = "center bold">'.$num.'</td>
							<td colspan = "3">
								<table class = "noborderbottom centpercent">
									<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
		foreach ($listselect as $selectvalues) {
			$numcol	= num_col($selectvalues, $listselect);
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">
											<select name = "'.$selectvalues['select'].'" class = "flat infrasplusfontsizeinherit infrasplusnopadding noborder cursorpointer'.($numcol['err'] > 0 ? ' infrasplusbgred' : '').'">';
			print								$numcol['options'].'
											</select>
										</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
		foreach ($listcol as $col) {
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">'.$col.'</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
		foreach ($listlarg as $largs) {
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">';
			if ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_PROGRESS') {
				print '(';
			}
			if ($largs['key'] == 'DESC') {
				print $largs['value'];
			} else {
				print '						<input type = "number" size = "2" class = "center infrasplusnomargin infrasplusnopadding" dir = "rtl" id = "'.$largs['key'].'" name = "'.$largs['key'].'"';
				if ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_REF' && !getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '') && !getDolGlobalString('INFRASPLUS_PDF_WITH_NUM_COLUMN', '') && !getDolGlobalString('INFRASPLUS_PDF_WITH_SUPPLIER_REF_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_UNIT' && !getDolGlobalInt('PRODUCT_USE_UNITS')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_QTY' && getDolGlobalString('INFRASPLUS_PDF_HIDE_QTY', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_UP' && getDolGlobalString('INFRASPLUS_PDF_HIDE_UP', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_TVA' && (getDolGlobalString('INFRASPLUS_PDF_WITHOUT_VAT_COLUMN', '') || getDolGlobalString('INFRASPLUS_PDF_TTC_WITH_VAT_TOT', '') || getDolGlobalString('INFRASPLUS_PDF_ONLY_TTC', '') || getDolGlobalString('INFRASPLUS_PDF_ONLY_HT', ''))) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_DISC' && getDolGlobalString('INFRASPLUS_PDF_HIDE_DISCOUNT', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_UPD' && (!getDolGlobalString('INFRASPLUS_PDF_SHOW_UP_DISCOUNTED', '') || getDolGlobalString('INFRASPLUS_PDF_HIDE_DISCOUNT', ''))) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_TOTAL_TTC' && !getDolGlobalString('INFRASPLUS_PDF_WITH_TTC_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} else {
					print '					min = "10" max = "100" value = "'.($largs['value'] > 0 ? $largs['value'] : 10).'">';
				}
			}
			if ($largs['key'] == 'INFRASPLUS_PDF_LARGCOL_PROGRESS') {
				print ')*';
			}
			print '						</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td colspan = "12" class = "center">'.$langs->trans('InfraSPlusParamColProgress').'</td>
									</tr>';
		print '					</table>
							</td>
						</tr>';
		$num++;
		// $num = 75
		infraspackplus_print_subTitle(4, 'InfraSPlusParamShipping');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BL_WITH_BC_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBLBCCol'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BL_WITH_POS_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBLposCol'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_BL_WITH_POS_COLUMN', '')) {
			$metas = array('size' => '6');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_PROD_POS', 'input', $descProdPos,'', $metas, 2, 1, '&nbsp;', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_ORDERED', 'on_off', $langs->trans('InfraSPlusParamHideOrdered'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BL_WITH_REL_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBLrelCol'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BL_WITH_PRICE', 'on_off', $langs->trans('InfraSPlusParamShowBLwithPrice').' '.$langs->trans('InfraSPlusGenModif'), '', [], 2, 1, '', $num);
		// $num = 81
		infraspackplus_print_hr(4);
		print '			<tr class = "oddeven">
							<td class = "center bold">'.$num.'</td>
							<td colspan = "3">
								<table class = "noborderbottom centpercent">';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
		foreach ($listselectBL as $selectvaluesBL) {
			$numcol	= num_col($selectvaluesBL, $listselectBL);
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">
											<select name = "'.$selectvaluesBL['select'].'" class = "flat infrasplusfontsizeinherit infrasplusnopadding noborder cursorpointer'.($numcol['err'] > 0 ? ' infrasplusbgred' : '').'">';
			print								$numcol['options'].'
											</select>
										</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
		foreach ($listcolBL as $colBL) {
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">'.$colBL.'</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
		foreach ($listlargBL as $largsBL) {
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">';
			if ($largsBL['key'] == 'DESC') {
				print $largsBL['value'];
			} else {
				print '						<input type = "number" size = "2" class = "center infrasplusnomargin infrasplusnopadding" dir = "rtl" id = "'.$largsBL['key'].'" name = "'.$largsBL['key'].'"';
				if ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_REF' && !getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '') && !getDolGlobalString('INFRASPLUS_PDF_BL_WITH_BC_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_EFL' && !getDolGlobalString('INFRASPLUS_PDF_BL_WITH_POS_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_WV' && getDolGlobalString('SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_UNIT' && !getDolGlobalInt('PRODUCT_USE_UNITS')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_ORDERED' && (getDolGlobalString('INFRASPLUS_PDF_HIDE_ORDERED', ''))) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_REL' && !getDolGlobalString('INFRASPLUS_PDF_BL_WITH_REL_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBL['key'] == 'INFRASPLUS_PDF_LARGCOLBL_PRICE' && !getDolGlobalString('INFRASPLUS_PDF_BL_WITH_PRICE', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} else {
					print '					min = "10" max = "100" value = "'.($largsBL['value'] > 0 ? $largsBL['value'] : 10).'">';
				}
			}
			print '						</td>';
		}
		print '						</tr>';
		print '					</table>
							</td>
						</tr>';
		$num++;
		// $num = 82
		infraspackplus_print_subTitle(4, 'InfraSPlusParamReceipt');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BR_WITH_BC_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBRBCCol'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BR_WITH_COMM_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBRcommCol'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BR_HIDE_ORDERED', 'on_off', $langs->trans('InfraSPlusParamHideOrderedBR'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BR_WITH_REL_COLUMN', 'on_off', $langs->trans('InfraSPlusParamShowBRrelCol'), '', [], 2, 1, '', $num);
		// $num = 85
		infraspackplus_print_hr(4);
		print '			<tr class = "oddeven">
							<td class = "center bold">'.$num.'</td>
							<td colspan = "3">
								<table class = "noborderbottom centpercent">';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
		foreach ($listselectBR as $selectvaluesBR) {
			$numcol	= num_col($selectvaluesBR, $listselectBR);
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">
											<select name = "'.$selectvaluesBR['select'].'" class = "flat infrasplusfontsizeinherit infrasplusnopadding noborder cursorpointer'.($numcol['err'] > 0 ? ' infrasplusbgred' : '').'">';
			print								$numcol['options'].'
											</select>
										</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
		foreach ($listcolBR as $colBR) {
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">'.$colBR.'</td>';
		}
		print '						</tr>';
		print '						<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
		foreach ($listlargBR as $largsBR) {
			print '						<td class = "center infrasplusnomargin infrasplusnopadding noborder">';
			if ($largsBR['key'] == 'DESC') {
				print $largsBR['value'];
			} else {
				print '						<input type = "number" size = "2" class = "center infrasplusnomargin infrasplusnopadding" dir = "rtl" id = "'.$largsBR['key'].'" name = "'.$largsBR['key'].'"';
				if ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_REF' && !getDolGlobalString('INFRASPLUS_PDF_WITH_REF_COLUMN', '') && !getDolGlobalString('INFRASPLUS_PDF_BR_WITH_BC_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_COMM' && !getDolGlobalString('INFRASPLUS_PDF_BR_WITH_COMM_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_UNIT' && !getDolGlobalInt('PRODUCT_USE_UNITS')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_ORDERED' && (getDolGlobalString('INFRASPLUS_PDF_BR_HIDE_ORDERED', ''))) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} elseif ($largsBR['key'] == 'INFRASPLUS_PDF_LARGCOLBR_REL' && !getDolGlobalString('INFRASPLUS_PDF_BR_WITH_REL_COLUMN', '')) {
					print '					min = "0" max = "0" value = "0" readonly>';
				} else {
					print '					min = "10" max = "100" value = "'.($largsBR['value'] > 0 ? $largsBR['value'] : 10).'">';
				}
			}
			print '						</td>';
		}
		print '						</tr>';
		print '					</table>
							</td>
						</tr>';
		$num++;
		// $num = 87
		if (isModEnabled('stock')) {
			infraspackplus_print_subTitle(4, 'InfraSPlusParamStock');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_WITH_STOCK_VAL_COLUMNS', 'on_off', $langs->trans('InfraSPlusParamWithStockValColumns'), '', [], 2, 1, '', $num);
			// $num = 87
			infraspackplus_print_hr(4);
			print '		<tr class = "oddeven">
							<td class = "center bold">'.$num.'</td>
							<td colspan = "3">
								<table class = "noborderbottom centpercent">';
			print '					<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
			foreach ($listselectST as $selectvaluesST) {
				$numcol	= num_col($selectvaluesST, $listselectST);
				print '					<td class = "center infrasplusnomargin infrasplusnopadding noborder">
											<select name = "'.$selectvaluesST['select'].'" class = "flat infrasplusfontsizeinherit infrasplusnopadding noborder cursorpointer'.($numcol['err'] > 0 ? ' infrasplusbgred' : '').'">';
				print							$numcol['options'].'
											</select>
										</td>';
			}
			print '					</tr>';
			print '					<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
			foreach ($listcolST as $colST) {
				print '					<td class = "center infrasplusnomargin infrasplusnopadding noborder">'.$colST.'</td>';
			}
			print '					</tr>';
			print '					<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
			foreach ($listlargST as $largsST) {
				print '					<td class = "center infrasplusnomargin infrasplusnopadding noborder">';
				if ($largsST['key'] == 'DESC') {
					print $largsST['value'];
				} else {
					print '						<input type = "number" size = "2" class = "center infrasplusnomargin infrasplusnopadding" dir = "rtl" id = "'.$largsST['key'].'" name = "'.$largsST['key'].'"';
					if ($largsST['key'] == 'INFRASPLUS_PDF_LARGCOLST_PMP' && !getDolGlobalString('INFRASPLUS_PDF_WITH_STOCK_VAL_COLUMNS', '')) {
						print '					min = "0" max = "0" value = "0" readonly>';
					} elseif ($largsST['key'] == 'INFRASPLUS_PDF_LARGCOLST_PMPT' && !getDolGlobalString('INFRASPLUS_PDF_WITH_STOCK_VAL_COLUMNS', '')) {
						print '					min = "0" max = "0" value = "0" readonly>';
					} elseif ($largsST['key'] == 'INFRASPLUS_PDF_LARGCOLST_TOT' && !getDolGlobalString('INFRASPLUS_PDF_WITH_STOCK_VAL_COLUMNS', '')) {
						print '					min = "0" max = "0" value = "0" readonly>';
					} else {
						print '					min = "10" max = "100" value = "'.($largsST['value'] > 0 ? $largsST['value'] : 10).'">';
					}
				}
				print '					</td>';
			}
			print '					</tr>';
			print '				</table>
							</td>
						</tr>';
			$num++;
		} else {
			$num	+= 2;
		}
		// $num = 89
		if (isModEnabled('mrp')) {
			infraspackplus_print_hr(4);
			print '		<tr>
							<td colspan = "4" class = "center"><span class = "infrasplussubtitleparam">'.$langs->trans('InfraSPlusParamMRP').'</span></td>
						</tr>';
			if (isModEnabled('productbatch')) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_SERIAL_ON_MRP', 'on_off', $langs->trans('InfraSPlusParamShowSerialOnMRP'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
			$metas = array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '20', 'max' => '90', 'step' => '1');
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_HEIGHT_MRP_CONTROL_TABLE','input', $langs->trans('InfraSPlusParamHeightMrpControlTable'),'', $metas, 2, 1, '&nbsp;pt', $num);
			// $num = 91
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_MRP_WITH_DIM_COLUMNS', 'on_off', $langs->trans('InfraSPlusParamMrpWithDimColumns'), '', [], 2, 1, '', $num);
			print '		<tr class = "oddeven">
							<td class = "center bold">'.$num.'</td>
							<td colspan = "3">
								<table class = "noborderbottom centpercent">';
			print '					<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColNum').'
										</td>';
			foreach ($listselectMRP as $selectvaluesMRP) {
				$numcol	= num_col($selectvaluesMRP, $listselectMRP);
				print '					<td class = "center infrasplusnomargin infrasplusnopadding noborder">
											<select name = "'.$selectvaluesMRP['select'].'" class = "flat infrasplusfontsizeinherit infrasplusnopadding noborder cursorpointer'.($numcol['err'] > 0 ? ' infrasplusbgred' : '').'">';
				print							$numcol['options'].'
											</select>
										</td>';
			}
			print '					</tr>';
			print '					<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColName').'
										</td>';
			foreach ($listcolMRP as $colMRP) {
				print '					<td class = "center infrasplusnomargin infrasplusnopadding noborder">'.$colMRP.'</td>';
			}
			print '					</tr>';
			print '					<tr>
										<td class = "infrasplusnomargin infrasplusnopadding noborder">
											'.$langs->trans('InfraSPlusParamColLarg').'
										</td>';
			foreach ($listlargMRP as $largsMRP) {
				print '					<td class = "center infrasplusnomargin infrasplusnopadding noborder">';
				if ($largsMRP['key'] == 'DESC') {
					print $largsMRP['value'];
				} else {
					print '					<input type = "number" size = "2" class = "center infrasplusnomargin infrasplusnopadding" dir = "rtl" id = "'.$largsMRP['key'].'" name = "'.$largsMRP['key'].'"';
					if ($largsMRP['key'] == 'INFRASPLUS_PDF_LARGCOLMRP_UNIT' && !getDolGlobalInt('PRODUCT_USE_UNITS')) {
						print '				min = "0" max = "0" value = "0" readonly>';
					} elseif ($largsMRP['key'] == 'INFRASPLUS_PDF_LARGCOLMRP_DIM' && !getDolGlobalString('INFRASPLUS_PDF_MRP_WITH_DIM_COLUMNS', '')) {
						print '					min = "0" max = "0" value = "0" readonly>';
					} else {
						print '				min = "10" max = "100" value = "'.($largsMRP['value'] > 0 ? $largsMRP['value'] : 10).'">';
					}
				}
				print '					</td>';
			}
			print '					</tr>';
			print '				</table>
							</td>
						</tr>';
			$num++;
		} else {
			$num	+= 4;
		}
		// $num = 93
		infraspackplus_print_subTitle(4, 'InfraSPlusParamUserSticker');
		$metas	= $formadmin->select_paper_format($selectedUserStickerFormat, 'INFRASPLUS_PDF_USER_STICKER_FORMAT');
		$num	= infraspackplus_print_input('', 'select', $langs->trans('InfraSPlusParamUserStickerFormat'), '', $metas, 1, 2, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USER_STICKER_TITLE', 'input', $langs->trans('InfraSPlusParamUserStickerTitle'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USER_STICKER_PHOTO', 'on_off', $langs->trans('InfraSPlusParamUserStickerPhoto'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USER_STICKER_JOB', 'on_off', $langs->trans('InfraSPlusParamUserStickerJob'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USER_STICKER_SOC_TEL', 'on_off', $langs->trans('InfraSPlusParamUserStickerSocTel'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USER_STICKER_SOC_MAIL', 'on_off', $langs->trans('InfraSPlusParamUserStickerSocMail'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USER_STICKER_LOGO', 'on_off', $langs->trans('InfraSPlusParamUserStickerLogo'), '', [], 2, 1, '', $num);
		// $num = 100
	}
	print '			</table>
				</div>';
	// Pied de document -> encours, total des remises, multi-devises, number-words, zones de signature, mentions complémentaires
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamTitleFooter').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblPD" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('Foot', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 4);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '1', 'max' => '10');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPACE_INFO', 'input', $langs->trans('InfraSPlusParamSpaceBeforeInfo'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPACE_TOT', 'input', $langs->trans('InfraSPlusParamSpaceBeforeTot'), '', $metas, 2, 1, '&nbsp;mm', $num);
		// $num = 3
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_MENTION_TVA_MODE','on_off',$langs->trans('InfraSPlusParamMentionTvaMode', $langs->trans((getDolGlobalInt('TAX_MODE', 0) ? 'InfraSPlusParamMentionTvaDebits' : 'InfraSPlusParamMentionTvaEncaissement'))), '', [], 2, 1, '', $num);
		if (!getDolGlobalString('PROPALE_PDF_HIDE_PAYMENTTERMCOND', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_PAYMENTTERMCOND_2L', 'on_off', $langs->trans('InfraSPlusParamShowPaymentTermCond2L'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (!empty($mysoc->country_code) && $mysoc->country_code == 'BE') {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_INVOICE_ADD_BELGIAN_STRUCTURED_CODE', 'on_off', $langs->trans('InfraSPlusParamInvoiceAddBelgianStructuredCode'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_QTY_PROD_TOT', 'on_off', $langs->trans('InfraSPlusParamShowQtyProdTot'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_TOT_DISCOUNT', 'on_off', $langs->trans('InfraSPlusParamShowTotDisc').' '.$langs->trans('InfraSPlusGenModif'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DISCOUNT_TOT', 'on_off', $langs->trans('InfraSPlusParamShowDiscTot'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_SHOW_DISCOUNT_TOT', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_DISCOUNT_TTC', 'on_off', $langs->trans('InfraSPlusParamShowDiscTTC'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_OUTSTDBILL', 'on_off', $langs->trans('InfraSPlusParamShowOutStdBill'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_INVERT_BG_HT_TTC', 'on_off', $langs->trans('InfraSPlusParamInvertBgHtTtc'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HT_BY_VAT_P_OR_S', 'on_off', $langs->trans('InfraSPlusParamHTbyTvaPorS'), '', [], 2, 1, '', $num);
		// $num = 13
		if (getDolGlobalString('INVOICE_USE_SITUATION', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_SITU_TOTAL_2', 'on_off', $langs->trans('InfraSPlusParamUseSituTotal2'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (!empty($TVAforfaitaire)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_TVA_FORFAIT', 'on_off', $langs->trans('InfraSPlusParamUseTVAforfaitaire'), '', [], 2, 1, '', $num);
			if (getDolGlobalString('INFRASPLUS_PDF_USE_TVA_FORFAIT', '')) {
				$metas = array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0', 'max' => '100', 'step' => '0.1');
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_TVA_FORFAIT', 'input', $langs->trans('InfraSPlusParamTVAforfaitaire'), '', $metas, 2, 1, '&nbsp;&percnt;', $num);
			} else {
				$num++;
			}
		} else {
			$num	+= 2;
		}
		// $num = 16
		if (isModEnabled('multicurrency')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_TOTAL_LOCAL_CUR', 'on_off', $langs->trans('InfraSPlusParamShowTotLocCur'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (isModEnabled('numberwords')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_NUMBER_WORDS', 'on_off', $langs->trans('InfraSPlusParamNumWords1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamNumWords2').' <a href="'.$langs->trans('InfraSPlusParamNumWordsLink').'" target="_blank">'.$langs->trans('InfraSPlusParamNumWordsLinkText').'</a>', '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		if (!empty($mysoc->country_code) && in_array($mysoc->country_code, array('BE', 'NL', 'DE', 'AT', 'FI'))) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_INVOICE_ADD_BELGIAN_QR_CODE', 'on_off', $langs->trans('InfraSPlusParamInvoiceAddBelgianQRcode'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PAY_INLINE', 'select_types_paiements', $langs->trans('InfraSPlusParamPayInLine'), '', array('CRDT', 2, 1, 1, 20), 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_PAY_INLINE', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PAY_INLINE_QR_CODE', 'on_off', $langs->trans('InfraSPlusParamPayInLineQRcode'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$bridgePropalEnabled = isModEnabled('infras2bridge') ? getDolGlobalString('INFRAS2BRIDGE_ENABLE_PROPAL_PAYMENT_LINK', 0) : 0;
		if ($bridgePropalEnabled) {
			$descBridgePayementLink		= $langs->trans('InfraSPlusParamBridgeDisplayPaymentLinkPropal');
			$descBridgePaymentQRCode	= $langs->trans('InfraSPlusParamBridgeDisplayPaymentQRCodePropal');
		} else {
			$descBridgePayementLink		= $langs->trans('InfraSPlusParamBridgeDisplayPaymentLink');
			$descBridgePaymentQRCode	= $langs->trans('InfraSPlusParamBridgeDisplayPaymentQRCode');
		}
		if (isModEnabled('infras2bridge')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BRIDGE_DISPLAY_PAYMENT_LINK', 'on_off', $descBridgePayementLink, '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_BRIDGE_DISPLAY_PAYMENT_QR_CODE', 'on_off', $descBridgePaymentQRCode, '', [], 2, 1, '', $num);
		} else {
			$num	+=2;
		}
		if (!getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_DEPOSITS_AT_END', 'on_off', $langs->trans('InfraSPlusParamDepositsAtEnd'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_PAY_SPEC', 'on_off', $langs->trans('InfraSPlusParamUsePaySpec'), '', [], 2, 1, '', $num);
		// $num = 25
		if (getDolGlobalString('INFRASPLUS_PDF_USE_PAY_SPEC', '') && getDolGlobalString('INFRASPLUS_PDF_PAY_SPEC', '')) {
			print '		<tr class = "oddeven">
							<td class = "center bold">'.$num.'</td>
							<td colspan = "2">'.$langs->trans('InfraSPlusParamPaySpec').'</td>
							<td class = "center">
								<button class = "button infraspluswidth110" style = "padding: 3px 0;" type = "submit" value = "modifyPaySpec" name = "action">'.$langs->trans('Modify').'</button>
							</td>
						</tr>';
		}
		$num++;
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_BANK_ONLY_NUMBER', 'on_off', $langs->trans('InfraSPlusParamBankOnlyNumber'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_NO_IBAN', 'on_off', $langs->trans('InfraSPlusParamNoIBAN').' '.$langs->trans('InfraSPlusGenModif'), '', [], 2, 1, '', $num);
		if (!getDolGlobalString('INFRASPLUS_PDF_NO_IBAN', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_IBAN_WITH_CB', 'on_off', $langs->trans('InfraSPlusParamIBANwithCB'), '', [], 2, 1, '', $num);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_IBAN_ALL', 'on_off', $langs->trans('InfraSPlusParamIBANAll'), '', [], 2, 1, '', $num);
		} else {
			$num	+= 2;
		}
		// $num = 30
		if (isModEnabled('uptosign')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_USE_MAGICKSTAMPWORD_ON_INVOICE', 'on_off', $langs->trans('InfraSPlusParamUseMagickStampWordOnInvoice'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 31
		infraspackplus_print_hr(4);
		$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '8', 'max' => '48');
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_HT_SIGN_AREA', 'input', $langs->trans('InfraSPlusParamHtSignArea'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '0.1', 'max' => '5', 'step' => '0.1');
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_SIGN_LINE_WIDTH', 'input', $langs->trans('InfraSPlusParamSignLineW').$langs->trans('InfraSPlusParamLineW'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas		= [];
		$metas[0]	= array($langs->trans('InfraSPlusParamLineDash0'), $langs->trans('InfraSPlusParamLineDash1'), $langs->trans('InfraSPlusParamLineDash2'), $langs->trans('InfraSPlusParamLineDash4'));
		$metas[1]	= array('INFRASPLUS_PDF_SIGN_LINE_DASH_0' => '&nbsp;&nbsp;'.img_picto('Ligne continue',		'Dash0.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_SIGN_LINE_DASH_1' => '&nbsp;&nbsp;'.img_picto('Pointillés égaux',	'Dash1.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_SIGN_LINE_DASH_2' => '&nbsp;&nbsp;'.img_picto('Pointillés inégaux',	'Dash2.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'),
							'INFRASPLUS_PDF_SIGN_LINE_DASH_4' => '&nbsp;&nbsp;'.img_picto('Ligne discontinue',	'Dash4.png@infraspackplus', 'class = "valignbottom infrasplusheight20"'));
		$end		= '<input type = "text" class = "flat quatrevingtpercent right infrasplusfontsizeinherit infrasplusnopadding" id = "INFRASPLUS_PDF_SIGN_LINE_DASH" name = "INFRASPLUS_PDF_SIGN_LINE_DASH" '.$inputSignLineDash.'>';
		$num		= infraspackplus_print_line_inputs('', $langs->trans('InfraSPlusParamSignLineDash'), $metas, 2, 200, $end, $num);
		$metas		= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_SIGN_LINE_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_SIGN_LINE_COLOR', '')) : []);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_SIGN_LINE_COLOR', 'color', $langs->trans('InfraSPlusParamSignLineColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_SIGN_LINE_COLOR', '')), '', $metas, 2, 1, '', $num);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_E_SIGNING', 'on_off', $langs->trans('InfraSPlusParamShowESigning'), '', [], 2, 1, '', $num);
		$num		= infraspackplus_print_input('INFRASPLUS_PDF_GET_CUSTOMER_SIGNING', 'on_off', $langs->trans('InfraSPlusParamGetCustomerSign'), '', [], 2, 1, '', $num);
		// $num = 37
		if (getDolGlobalString('INFRASPLUS_PDF_GET_CUSTOMER_SIGNING', '')) {
			$metas	= colorArrayToHex(getDolGlobalString('INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR', '') ? explode(',', getDolGlobalString('INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR', '0,0,0')) : array('0', '0', '0'));
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR', 'color', $langs->trans('InfraSPlusParamCustomerSigncolor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_CUSTOMER_SIGNING_COLOR', '')), '', $metas, 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignature'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE_EMET', 'on_off', $langs->trans('InfraSPlusParamShowSignatureEmet'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_ST_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureSt'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE', '') || getDolGlobalString('INFRASPLUS_PDF_PROPAL_ST_SHOW_SIGNATURE', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_PROPAL_SHOW_SIGNATURE_NAME_FUNCTION', 'on_off', $langs->trans('InfraSPlusParamShowSignatureNameFunction'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 42
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_COMMANDE_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureCom'), '', [], 2, 1, '', $num);
		if (isModEnabled('customlink')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_COMMANDE_OF_SHOW_2_SIGNATURES', 'on_off', $langs->trans('InfraSPlusParamShow2SignaturesCom'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_CONTRACT_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureCtr'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_CONTRACT_SHOW_SIGNATURE', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CONTRACT_SHOW_SIGNATURE_NAME_FUNCTION', 'on_off', $langs->trans('InfraSPlusParamShowSignatureNameFunctionCtr'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXPEDITION_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureExp'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE', 'on_off', $langs->trans('InfraSPlusParamShowSignatureFi'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET', 'on_off', $langs->trans('InfraSPlusParamShowSignatureFiEmet'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_INTERVENTION_SHOW_SIGNATURE_EMET', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_INTERVENTION_SIGNATURE_FULL', 'on_off', $langs->trans('InfraSPlusParamSignatureFiFull'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 50
	}
	print '			</table>
				</div>';
	// Pied de page -> Lignes d'informations supplémentaires, n° de page, LCR
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamTitleFooterPage').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblPP" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infraspackplus_print_btn_action('FootP', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_ADDRESS', 'on_off', $langs->trans('InfraSPlusParamFooterAdress').(!getDolGlobalString('INFRASPLUS_PDF_HIDE_RECEP_FRAME', '') ? '' : ' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').' '.$langs->trans('InfraSPlusParamFooterAdressForced').'</span>'), '', [], 2, 1, '', $num);
		if (getDolGlobalString('INFRASPLUS_PDF_TYPE_FOOT_ADDRESS', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_ADDRESS2', 'on_off', $langs->trans('InfraSPlusParamFooterAdress2'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_CONTACTS', 'on_off', $langs->trans('InfraSPlusParamFooterContacts'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_MANAGER', 'on_off', $langs->trans('InfraSPlusParamFooterManager'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_TYPESOC', 'on_off', $langs->trans('InfraSPlusParamFooterTypeSoc'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_TYPE_FOOT_IDS', 'on_off', $langs->trans('InfraSPlusParamFooterIds'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_FOOTER_BOLD', 'on_off', $langs->trans('InfraSPlusParamFooterBold'), '', [], 2, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_NO_LINE_FOOTER', 'on_off', $langs->trans('InfraSPlusParamNoLineFooter'), '', [], 2, 1, '', $num);
		// $num = 9
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SPE_FOOT', 'input', $langs->trans('InfraSPlusParamSpecialFoot'), '', [], 2, 1, '', $num);
		if (!getDolGlobalString('INFRASPLUS_PDF_SPE_FOOT', '')) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_FOOTER_FREETEXT', 'textarea', $langs->trans('InfraSPlusParamFooterFreeText'), '', [], 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 11
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_HIDE_PAGE_NUM', 'on_off', $langs->trans('InfraSPlusParamHidePageNum'), '', [], 2, 1, '', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '10', 'max' => '267', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_X_PAGE_NUM', 'input', $langs->trans('InfraSPlusParamPosXPageNum'), '', $metas, 2, 1, '&nbsp;mm', $num);
		$metas	= array('type' => 'number', 'class' => 'flat soixantepercent right', 'dir' => 'rtl', 'min' => '10', 'max' => '285', 'step' => '0.1');
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_Y_PAGE_NUM', 'input', $langs->trans('InfraSPlusParamPosYPageNum'), '', $metas, 2, 1, '&nbsp;mm', $num);
		// $num = 14
		infraspackplus_print_hr(4);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_SHOW_LCR', 'on_off', $langs->trans('InfraSPlusParamShowLCR'), '', [], 2, 1, '', $num);
		// $num = 15
	}
	print '			</table>
				</div>';
	// Conditions générales -> vente, interventions, achats
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamCGVs').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/Tools.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblCGx" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	if (!empty($accessright)) {
		$num	= 1;
		print '			<tr class = "oddeven">
							<td colspan = "4">
								<table class = "centpercent">
								<tr>
										<td class = "noborder">
											<label for = "CGVFile">'.$langs->trans('InfraSPlusParamCGVFile').'</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											<input type = "file" class = "flat infrasplusfontsizeinherit infrasplusnopadding cursorpointer" id = "CGVFile" name = "CGVFile" accept=".pdf">
										</td>
										<td class = "noborder">
											<label for = "typeCG">'.$langs->trans('InfraSPlusParamTypeCG').'</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											'.$form->selectarray('typeCG', $selectCG, '', 0, 0, 0, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"').'
										</td>
										<td class = "right noborder">
											<label for = "CGVName">'.$langs->trans('InfraSPlusParamCGVName').'</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
											<input type = "text" class = "flat infrasplusfontsizeinherit infrasplusnopadding" size = "30" id = "CGVName" name = "CGVName">
										</td>
									</tr>
								</table>
							</td>
							<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "addcgv" name = "action">'.$langs->trans("Add").'</button></td>
						</tr>';
		print '			<tr>
							<td colspan = "5">';
		print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamListCGV').'</span>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
		$CGV_files	= dol_dir_list($cgxdir, 'files', 0, '', null, 'name', SORT_ASC, 1, 0, '', 0);
		$formfile->list_of_documents($CGV_files, null, 'mycompany', '&typefile=cgv', 1, '', 1, 0, '', 0, 'none');
		print '				</td>
						</tr>';
		if (count($CGVs) > 0 || count($CGIs) > 0 || count($CGAs) > 0) {
			$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
			infraspackplus_print_liste_titre($metas);
			infraspackplus_print_btn_action('CGx', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 4);
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_CGV_FROM_PRO', 'on_off', $langs->trans('InfraSPlusParamCGVFromPro'), '', [], 2, 1, '', $num);
			if (getDolGlobalString('INFRASPLUS_PDF_CGV_FROM_PRO', '')) {
				$sortparam	= getDolGlobalString('SOCIETE_SORT_ON_TYPEENT', 'ASC'); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
				$metas		= $form->selectarray('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL', $formcompany->typent_array(1), getDolGlobalString('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL', ''), 0, 0, 0, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"', 0, 0, 0, $sortparam, '', 1).' '.info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'), 1);
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL', 'select', $langs->trans('InfraSPlusParamCGVFromProLabel1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamCGVFromProLabel2').'</span> '.$langs->trans('InfraSPlusParamCGVFromProLabel3').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamCGVFromProLabel2').'</span> '.$langs->trans('InfraSPlusParamCGVFromLang6'), '', $metas, 2, 1, '', $num);
			} else {
				$num++;
			}
			if (getDolGlobalString('MAIN_MULTILANGS', '')) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_CGV_FROM_LANG', 'on_off', $langs->trans('InfraSPlusParamCGVFromLang1').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCGVFromLang2').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamCGVFromLang3').'</span> '.$langs->trans('InfraSPlusParamCGVFromLang4').' <span class = "infraspluscaution">'.$langs->trans('InfraSPlusParamCGVFromLang5').'</span> '.$langs->trans('InfraSPlusParamCGVFromLang6'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
			// $num = 4
			$desc		= $form->selectarray('INFRASPLUS_PDF_CGV', $CGVs, $selected_cgv, $langs->trans('InfraSPlusParamNoCGV'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"');
			$desc		.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('InfraSPlusParamTypeDoc');
			$metas		= [];
			$metas[0]	= array('MAIN_MODULE_PROPALE'	=> $langs->trans('Proposals'),
								'MAIN_MODULE_COMMANDE'	=> $langs->trans('Orders'),
								'MAIN_MODULE_CONTRAT'	=> $langs->trans('Contracts'),
								'MAIN_MODULE_FACTURE'	=> $langs->trans('Invoices'));
			$metas[1]	= array('INFRASPLUS_PDF_CGV_BY_DEF_FOR_PROPOSALS'	=> 'MAIN_MODULE_PROPALE',
								'INFRASPLUS_PDF_CGV_BY_DEF_FOR_ORDERS'		=> 'MAIN_MODULE_COMMANDE',
								'INFRASPLUS_PDF_CGV_BY_DEF_FOR_CONTRACTS'	=> 'MAIN_MODULE_CONTRAT',
								'INFRASPLUS_PDF_CGV_BY_DEF_FOR_INVOICES'	=> 'MAIN_MODULE_FACTURE');
			$num		= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamDefaultCGV').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$desc, $metas, 3, 120, '', $num);
			$desc		= $form->selectarray('INFRASPLUS_PDF_CGI', $CGIs, $selected_cgi, $langs->trans('InfraSPlusParamNoCGI'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"');
			$metas[0]	= array('INFRASPLUS_PDF_NO_VALUE'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE1'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE2'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE3'	=> '&nbsp;');
			$metas[1]	= array('INFRASPLUS_PDF_NO_VALUE'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE1'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE2'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE3'	=> '&nbsp;');
			$num		= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamDefaultCGI').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$desc, $metas, 3, 120, '', $num);
			$desc		= $form->selectarray('INFRASPLUS_PDF_CGA', $CGAs, $selected_cga, $langs->trans('InfraSPlusParamNoCGA'), 0, 1, 'class = "infrasplusfontsizeinherit infrasplusnopadding cursorpointer"');
			$desc		.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('InfraSPlusParamTypeDoc');
			$metas[0]	= array('MAIN_MODULE_PROPALE'		=> $langs->trans('Proposals'),
								'MAIN_MODULE_COMMANDE'		=> $langs->trans('Orders'),
								'INFRASPLUS_PDF_NO_VALUE'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE1'	=> '&nbsp;');
			$metas[1]	= array('INFRASPLUS_PDF_CGA_BY_DEF_FOR_PROPOSALS'	=> 'MAIN_MODULE_PROPALE',
								'INFRASPLUS_PDF_CGA_BY_DEF_FOR_ORDERS'		=> 'MAIN_MODULE_COMMANDE',
								'INFRASPLUS_PDF_NO_VALUE'	=> '&nbsp;',
								'INFRASPLUS_PDF_NO_VALUE1'	=> '&nbsp;');
			$num		= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamDefaultCGA').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$desc, $metas, 3, 120, '', $num);
			// $num = 6
			if (getDolGlobalString('PRODUIT_PDF_MERGE_PROPAL', '')) {
				$num	= infraspackplus_print_input('INFRASPLUS_PDF_CGV_AT_VERY_END', 'on_off', $langs->trans('InfraSPlusParamCGVAtVeryEnd'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
		}
		// $num = 7
	}
	print '			</table>
				</div>';
	// Fichiers spéciaux
	print '		<div class = "foldable">';
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamSpf').'</span>', $titleoption, dol_buildpath('/infraspackplus/img/Tools.png', 1), 1, '', 'toggle_bloc_title cursorpointer');
	print '			<table name = "tblSpf" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	if (!empty($accessright)) {
		print '			<tr>
							<td colspan = "5">';
		print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamListSpf').'</span>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
		print '				</td>
						</tr>';
		$num	= 1;
		$metas	= array('type' => 'file', 'class' => 'flat centpercent', 'accept' => '.pdf', 'style' => 'padding: 0px; font-size: inherit; cursor: pointer;');
		$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "setpdf" name = "action">'.$langs->trans('Add').'</button></td>';
		$num	= infraspackplus_print_input('pdffile', 'input', $langs->trans('InfraSPlusParamAddPDF'), '', $metas, 1, 2, $end, $num);
		print '			<tr>
							<td colspan = "5">';
		$formfile->list_of_documents($listpdfs, null, 'infraspackplus', '&typefile=pdf', 1, 'specialfiles/', 1, 0, '', 0, 'none');
		print '				</td>
						</tr>';
		print '			<tr>
							<td colspan = "5">';
		print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamListPhp').'</span>', '', dol_buildpath('/infraspackplus/img/list.png', 1), 1);
		print '				</td>
						</tr>';
		$metas	= array('type' => 'file', 'class' => 'flat centpercent', 'accept' => '.php', 'style' => 'padding: 0px; font-size: inherit; cursor: pointer;');
		$end	= '<td class = "center"><button class = "button infraspluswidth110" type = "submit" value = "setphp" name = "action">'.$langs->trans('Add').'</button></td>';
		$num	= infraspackplus_print_input('phpfile', 'input', $langs->trans('InfraSPlusParamAddPhp'), '', $metas, 1, 2, $end, $num);
		print '			<tr>
							<td colspan = "5">';
		$formfile->list_of_documents($listphps, null, 'infraspackplus', '&typefile=php', 1, '', 1, 0, '', 0, 'none');
		print '				</td>
						</tr>';
		if (is_array($listpdftouse) && count($listpdftouse) > 0) {
			$metas	= array(array(1, 1, 3), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'));
			infraspackplus_print_liste_titre($metas);
			foreach ($listpdftouse as $pdftouse) {
				print '	<tr class = "oddeven">
							<td colspan = "5">
								<table class = "centpercent">';
				$metas		= [];
				$metas[0]	= array('MAIN_MODULE_PROPALE'		=> $langs->trans('Proposals'),
									'MAIN_MODULE_COMMANDE'		=> $langs->trans('Orders'),
									'MAIN_MODULE_FICHEINTER'	=> $langs->trans('Interventions'),
									'MAIN_MODULE_FACTURE'		=> $langs->trans('Invoices'),
									'MAIN_MODULE_PROJET'		=> $langs->trans('Projects'),
									'MAIN_MODULE_PROJET_AUTO'	=> $langs->trans('InfraSPlusParamProjectAuto'),
									'MAIN_MODULE_CONTRAT'		=> $langs->trans('Contracts'),
									'MAIN_MODULE_CONTRAT_AUTO'	=> $langs->trans('Contracts Auto'),
									'MAIN_MODULE_USER'			=> $langs->trans('Users'));
				$metas[1]	= array('INFRASPLUS_PDF_SPECIAL_FILE_PROPAL_'.(strtoupper($pdftouse))			=> 'MAIN_MODULE_PROPALE',
									'INFRASPLUS_PDF_SPECIAL_FILE_COMMANDE_'.(strtoupper($pdftouse))			=> 'MAIN_MODULE_COMMANDE',
									'INFRASPLUS_PDF_SPECIAL_FILE_FICHINTER_'.(strtoupper($pdftouse))		=> 'MAIN_MODULE_FICHEINTER',
									'INFRASPLUS_PDF_SPECIAL_FILE_FACTURE_'.(strtoupper($pdftouse))			=> 'MAIN_MODULE_FACTURE',
									'INFRASPLUS_PDF_SPECIAL_FILE_PROJECT_'.(strtoupper($pdftouse))			=> 'MAIN_MODULE_PROJET',
									'INFRASPLUS_PDF_SPECIAL_FILE_PROJECT_'.(strtoupper($pdftouse)).'_AUTO'	=> 'MAIN_MODULE_PROJET',
									'INFRASPLUS_PDF_SPECIAL_FILE_CONTRAT_'.(strtoupper($pdftouse))			=> 'MAIN_MODULE_CONTRAT',
									'INFRASPLUS_PDF_SPECIAL_FILE_CONTRAT_'.(strtoupper($pdftouse)).'_AUTO'	=> 'MAIN_MODULE_CONTRAT',
									'INFRASPLUS_PDF_SPECIAL_FILE_USER_'.(strtoupper($pdftouse))				=> 'MAIN_MODULE_USER');
				$num		= infraspackplus_print_line_inputs('tests', $langs->trans('InfraSPlusParamSpecialFiles', $pdftouse), $metas, 4, 120, '', $num);
				print '			</table>
							</td>
						</tr>';
			}
		}
		// $num = 3
	}
	print '			</table>
				</div>';
	print '	</form>
			<a class = "infrasplusScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
