<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand			- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/admin/extrafields.php
	* 	\ingroup	InfraS
	* 	\brief		Page to setup extrafields for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplusAdmin.lib.php');

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'infraspackplus@infraspackplus'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infraspackplus', 'paramExtraFields')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form					= new Form($db);
	$formfile				= new FormFile($db);
	$formother				= new FormOther($db);
	$action					= GETPOST('action', 'alpha');
	$listParamsExfDeposit	= array('type'				=> 'double',
									'pos'				=> 50,
									'size'				=> '5,2',
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
									'printable'			=> 0
									);
	$listParamsExfPrice		= array('type'				=> 'price',
									'pos'				=> 51,
									'size'				=> '',
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
									'totalizable'		=> 1,
									'printable'			=> 0
									);
	$listParamsExfDate		= array('type'				=> 'date',
									'pos'				=> 52,
									'size'				=> '',
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
									'printable'			=> 0
									);
	$result					= '';
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
	//Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$result	= infraspackplus_bkup_module ('infraspackplus');
	}
	if ($action == 'restoreParams') {
		$result	= infraspackplus_restore_module ('infraspackplus');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$result		= dolibarr_set_const($db, $confkey, GETPOST('value'), 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
	}
	// Update buttons management
	$errors	= array();
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('EXF'	=> array('INFRASPLUS_PDF_EXF_PAY_SPEC', 'INFRASPLUS_PDF_EXF_DEPOSIT', 'INFRASPLUS_PDF_EXF_ECOTAX', 'INFRASPLUS_PDF_EXF_PROPALPROV'));
		$listcolor	= array('EXF'	=> array('INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR', 'INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR'));
		$confkey	= $reg[1];
		foreach ($list[$confkey] as $constname) {
			$constvalue	= GETPOST($constname, 'alpha');
			if ($constname == 'INFRASPLUS_PDF_EXF_PAY_SPEC') {
				// Paiements spéciaux
				$oldListPaySpec	= getDolGlobalString('INFRASPLUS_PDF_EXF_PAY_SPEC', '');
				$oldListPaySpec	= !empty($oldListPaySpec) ? explode(',', $oldListPaySpec) : array();
				if (!empty($constvalue)) {
					$listPaySpec	= explode(',', preg_replace('/(\s*,?\s*)*$/', '', $constvalue));	// clean the string from the last comma
					$newListPaySpec	= array();
					// Contrôle du code utilisé pour l'attribut (longueur, caractères spéciaux, etc...)
					foreach ($listPaySpec as $paySpec) {
						if (infraspackplus_check_extf_name ($paySpec) < 0) {
							$errors[]	= $langs->trans('InfraSPlusParamErrorFormatPaySpec',  $paySpec);
							continue;
						} else {
							$newListPaySpec[]	= $paySpec;
						}
					}
					$diff	= array_diff($oldListPaySpec, $newListPaySpec);	// some values have been deleted
					if (!empty($diff)) {
						foreach ($diff as $paySpec) {
							$result	= infraspackplus_search_extf (-2, $paySpec, '', $langs->trans('InfraSPlusParamLabelExfPaySpec',  $paySpec), array('facture'), $listParamsExfPrice);	// delete
							if ($result <= 0) {
								$errors[]	= $langs->trans('InfraSPlusParamErrorDelPaySpec',  $paySpec);
							}
							$result	= infraspackplus_search_dict (-2, $db->prefix().'c_paiement', strtoupper($paySpec), $langs->trans('InfraSPlusParamLabelExfPaySpec', strtoupper($dictPaySpecToCreate)));	// delete
							if ($result <= 0) {
								$errors[]	= $langs->trans('InfraSPlusParamErrorDelDictPaySpec',  $paySpec);
							}
						}
					}
					$constvalue	= implode(',', $newListPaySpec);
				}
				// tout doit être effacé
				elseif (!empty($oldListPaySpec)) {
					foreach ($oldListPaySpec as $paySpec) {
						$result	= infraspackplus_search_extf (-2, $paySpec, '', $langs->trans('InfraSPlusParamLabelExfPaySpec',  $paySpec), array('facture'), $listParamsExfPrice);	// delete
						if ($result <= 0) {
							$errors[]	= $langs->trans('InfraSPlusParamErrorDelPaySpec',  $paySpec);
						}
						$result	= infraspackplus_search_dict (-2, $db->prefix().'c_paiement', strtoupper($paySpec), $langs->trans('InfraSPlusParamLabelExfPaySpec', strtoupper($dictPaySpecToCreate)));	// delete
						if ($result <= 0) {
							$errors[]	= $langs->trans('InfraSPlusParamErrorDelDictPaySpec',  $paySpec);
						}
					}
				}
			}
			// Acompte (deposit)
			if ($constname == 'INFRASPLUS_PDF_EXF_DEPOSIT') {
				// Contrôle du code utilisé pour l'attribut (longueur, caractères spéciaux, etc...)
				if (!empty($constvalue) && infraspackplus_check_extf_name ($constvalue) < 0) {
					$result = 0;
					continue;
				}
				$name	= getDolGlobalString('INFRASPLUS_PDF_EXF_DEPOSIT', '');
				if (empty($constvalue) || (!empty($name) && $name != $constvalue)) {
					$result	= infraspackplus_search_extf (-2, '', 'INFRASPLUS_PDF_EXF_DEPOSIT', 'InfraSPlusParamLabelExfDeposit', array('propal'), $listParamsExfDeposit);	// delete
				}
			}
			// Écotaxe
			if ($constname == 'INFRASPLUS_PDF_EXF_ECOTAX') {
				// Contrôle du code utilisé pour l'attribut (longueur, caractères spéciaux, etc...)
				if (!empty($constvalue) && infraspackplus_check_extf_name ($constvalue) < 0) {
					$result = 0;
					continue;
				}
				$name	= getDolGlobalString('INFRASPLUS_PDF_EXF_ECOTAX', '');
				if (empty($constvalue) || (!empty($name) && $name != $constvalue)) {
					$result	= infraspackplus_search_extf (-2, '', 'INFRASPLUS_PDF_EXF_ECOTAX', 'InfraSPlusParamLabelExfEcoTax', array('product'), $listParamsExfPrice);	// delete
				}
			}
			// Filigrame de proposition provisoire
			if ($constname == 'INFRASPLUS_PDF_EXF_PROPALPROV') {
				// Contrôle du code utilisé pour l'attribut (longueur, caractères spéciaux, etc...)
				if (!empty($constvalue) && infraspackplus_check_extf_name ($constvalue) < 0) {
					$result = 0;
					continue;
				}
				$name	= getDolGlobalString('INFRASPLUS_PDF_EXF_PROPALPROV', '');
				if (empty($constvalue) || (!empty($name) && $name != $constvalue)) {
					$result	= infraspackplus_search_extf (-2, '', 'INFRASPLUS_PDF_EXF_PROPALPROV', 'InfraSPlusParamLabelExfPropalProv', array('product'), $listParamsExfDate);	// delete
				}
			}
			// écriture de la constante
			$result	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
		foreach ($listcolor[$confkey] as $constname) {
			$constvalue	= implode(', ', colorStringToArray(GETPOST($constname, 'alpha')));
			$result		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		}
	}
	if ($action == 'setExfPaySpec' || ($action == 'setDictPaySpec' && getDolGlobalString('INFRASPLUS_PDF_USE_PAY_SPEC', ''))) {
		$listPaySpec	= getDolGlobalString('INFRASPLUS_PDF_EXF_PAY_SPEC', '');
		if (!empty($listPaySpec)) {
			$listPaySpec	= explode(',', $listPaySpec);
			if ($action == 'setExfPaySpec') {
				$listExfPaySpec	= array();
				foreach ($listPaySpec as $paySpec) {
					$resPaySpec	= infraspackplus_search_extf (0, $paySpec, '', $langs->trans('InfraSPlusParamLabelExfPaySpec',  strtoupper($paySpec)), array('facture'), $listParamsExfPrice);	// check => 0 nothing found, 1 found
					if (!empty($resPaySpec)) {
						$listExfPaySpec[]	= $paySpec;	// création de la liste des attributs supplémentaires correspondants à un code enregistré
					}
				}
				$listExfPaySpecToCreate	= array_diff($listPaySpec, $listExfPaySpec);	// différence entre la liste des paiements spéciaux enregistrée et ceux trouvés comme attribut supplémentaire
				foreach ($listExfPaySpecToCreate as $exfPaySpecToCreate) {
					$result	= infraspackplus_search_extf (2, $exfPaySpecToCreate, '', $langs->trans('InfraSPlusParamLabelExfPaySpec',  strtoupper($exfPaySpecToCreate)), array('facture'), $listParamsExfPrice);	// create
					if ($result <= 0) {
						$errors[]	= $langs->trans('InfraSPlusParamErrorCreatePaySpec',  $exfPaySpecToCreate);
					}
				}
			} else {	// $action == 'setDictPaySpec'
				$listDictPaySpec	= array();
				foreach ($listPaySpec as $paySpec) {
					$resPaySpec	= getDictionaryValue($db->prefix().'c_paiement', 'code', strtoupper($paySpec), true, 'code');
					if (!empty($resPaySpec)) {
						$listDictPaySpec[]	= $paySpec;	// création de la liste des modes de paiement correspondants à un code enregistré
					}
				}
				$listDictPaySpecToCreate	= array_diff($listPaySpec, $listDictPaySpec);	// différence entre la liste des paiements spéciaux enregistrée et ceux trouvés comme mode de paiement
				foreach ($listDictPaySpecToCreate as $dictPaySpecToCreate) {
					$result	= infraspackplus_search_dict (2, $db->prefix().'c_paiement', strtoupper($dictPaySpecToCreate), $langs->trans('InfraSPlusParamLabelExfPaySpec', strtoupper($dictPaySpecToCreate)));	// create
					if ($result <= 0) {
						$errors[]	= $langs->trans('InfraSPlusParamErrorCreateDictPaySpec',  $dictPaySpecToCreate);
					}
				}
			}
		}
	}
	// Retour => message Ok ou Ko
	if ($action == 'setExfDeposit') {
		$result	= infraspackplus_search_extf (2, '', 'INFRASPLUS_PDF_EXF_DEPOSIT', 'InfraSPlusParamLabelExfDeposit', array('propal'), $listParamsExfDeposit);	// create
	}
	if ($action == 'setExfEcoTax') {
		$result	= infraspackplus_search_extf (2, '', 'INFRASPLUS_PDF_EXF_ECOTAX', 'InfraSPlusParamLabelExfEcoTax', array('product'), $listParamsExfPrice);	// create
	}
	if ($action == 'setExfPropalProv') {
		$result	= infraspackplus_search_extf (2, '', 'INFRASPLUS_PDF_EXF_PROPALPROV', 'InfraSPlusParamLabelExfPropalProv', array('product'), $listParamsExfDate);	// create
	}
	if (!empty($errors)) {
		foreach($errors as $error) {
			setEventMessages($error, null, 'errors');
		}
	}
	if ($result >= 1) {
		setEventMessages($langs->trans('SetupSaved').($action == 'setExfDeposit' || $action == 'setExfEcoTax' ? ' ('.$result.')' : ''), null, 'mesgs');
	}
	if ($result == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************
	global $dictvalues;
	unset($dictvalues);
	$listPaySpec	= getDolGlobalString('INFRASPLUS_PDF_EXF_PAY_SPEC', '');
	if (!empty($listPaySpec)) {
		$listPaySpec		= explode(',', $listPaySpec);
		$listExfPaySpec		= array();
		$listDictPaySpec	= array();
		foreach ($listPaySpec as $paySpec) {
			$resPaySpec	= infraspackplus_search_extf (0, $paySpec, '', $langs->trans('InfraSPlusParamLabelExfPaySpec',  strtoupper($paySpec)), array('facture'), $listParamsExfPrice);	// check => 0 nothing found, 1 found
			if (!empty($resPaySpec)) {
				$listExfPaySpec[]	= $paySpec;	// création de la liste des attributs supplémentaires correspondants à un code enregistré
			}
			// 	Payment Modes Dictionary
			if (getDolGlobalString('INFRASPLUS_PDF_USE_PAY_SPEC', '')) {
				$resPaySpec	= getDictionaryValue($db->prefix().'c_paiement', 'code', strtoupper($paySpec), true, 'code');
				if (!empty($resPaySpec)) {
					$listDictPaySpec[]	= $paySpec;	// création de la liste des modes de paiement correspondants à un code enregistré
				}
			}
		}
		$listExfPaySpecToCreate		= array_diff($listPaySpec, $listExfPaySpec);	// différence entre la liste des paiements spéciaux enregistrée et ceux trouvés comme attribut supplémentaire => génère la liste des éléments à ajouter
		$textDescExfPaySpec			= $langs->trans(count($listExfPaySpecToCreate) > 1 ? 'InfraSPlusParamSetExfs' : 'InfraSPlusParamSetExf', implode(', ', $listExfPaySpecToCreate));
		$listDictPaySpecToCreate	= array_diff($listPaySpec, $listDictPaySpec);	// différence entre la liste des paiements spéciaux enregistrée et ceux trouvés comme mode de paiement => génère la liste des éléments à ajouter
		$textDescDictPaySpec		= $langs->trans(count($listDictPaySpecToCreate) > 1 ? 'InfraSPlusParamSetDicts' : 'InfraSPlusParamSetDict', implode(', ', $listDictPaySpecToCreate));
	}
	$descPaySpec		= $langs->trans('InfraSPlusParamEXFpaySpec');
	$descPaySpec		.= !empty($listExfPaySpecToCreate) ? ' <button class = "butAction infrasplusheight20 infrasplusnopadding" type = "submit" value = "setExfPaySpec" name = "action">'.$textDescExfPaySpec.'</button>' : '';
	$descPaySpec		.= !empty($listDictPaySpecToCreate) ? ' <button class = "butAction infrasplusheight20 infrasplusnopadding" type = "submit" value = "setDictPaySpec" name = "action">'.$textDescDictPaySpec.'</button>' : '';
	$exfDepositIsSet	= infraspackplus_search_extf (0, '', 'INFRASPLUS_PDF_EXF_DEPOSIT', 'InfraSPlusParamLabelExfDeposit', array('propal'), $listParamsExfDeposit);	// update
	$textDescDeposit	= $langs->trans('InfraSPlusParamSetExf', getDolGlobalString('INFRASPLUS_PDF_EXF_DEPOSIT', ''));
	$descDeposit		= $langs->trans('InfraSPlusParamEXFdeposit').($exfDepositIsSet == 0 ? ' <button class = "button infrasplusheight20 infrasplusnopadding" type = "submit" value = "setExfDeposit" name = "action">'.$textDescDeposit.'</button>': '');
	$exfEcoTaxIsSet		= infraspackplus_search_extf (0, '', 'INFRASPLUS_PDF_EXF_ECOTAX', 'InfraSPlusParamLabelExfEcoTax', array('product'), $listParamsExfPrice);	// update
	$textDescEcoTax		= $langs->trans('InfraSPlusParamSetExf', getDolGlobalString('INFRASPLUS_PDF_EXF_ECOTAX', ''));
	$descEcoTax			= $langs->trans('InfraSPlusParamEXFecoTax').($exfEcoTaxIsSet == 0 ? ' <button class = "button infrasplusheight20 infrasplusnopadding" type = "submit" value = "setExfEcoTax" name = "action">'.$textDescEcoTax.'</button>': '');
	$exfPropalProvIsSet	= infraspackplus_search_extf (0, '', 'INFRASPLUS_PDF_EXF_PROPALPROV', 'InfraSPlusParamLabelExfPropalProv', array('product'), $listParamsExfDate);	// update
	$textDescPropalProv	= $langs->trans('InfraSPlusParamSetExf', getDolGlobalString('INFRASPLUS_PDF_EXF_PROPALPROV', ''));
	$descPropalProv		= $langs->trans('InfraSPlusParamEXFpropalProv').($exfPropalProvIsSet == 0 ? ' <button class = "button infrasplusheight20 infrasplusnopadding" type = "submit" value = "setExfEcoTax" name = "action">'.$textDescPropalProv.'</button>': '');
	$listExfNotes		= array('INFRASPLUS_PDF_EXF_D', 'INFRASPLUS_PDF_EXF_C', 'INFRASPLUS_PDF_EXF_CT', 'INFRASPLUS_PDF_EXF_FI', 'INFRASPLUS_PDF_EXF_E',
								'INFRASPLUS_PDF_EXF_F', 'INFRASPLUS_PDF_EXF_MRP', 'INFRASPLUS_PDF_EXF_B', 'INFRASPLUS_PDF_EXF_DF', 'INFRASPLUS_PDF_EXF_CF',
								'INFRASPLUS_PDF_EXF_FF');

	// View *****************************************
	$page_name			= $langs->trans('infrasplussetup') .' - '. $langs->trans('InfraSPlusParamsExtraFields');
	llxHeader('', $page_name);
	$linkback			= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');

	// Configuration header *************************
	$head				= infraspackplus_admin_prepare_head();
	$picto				= 'infraspackplus@infraspackplus';
	print dol_get_fiche_head($head, 'extrafields', $langs->trans('modcomnamePackPlus'), 0, $picto);

	// setup page goes here *************************
	if (!empty($conf->use_javascript_ajax)) {
		print '	<script type = "text/javascript">
					$(function () {
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
	print '		<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post">
					<input type = "hidden" name = "token" value = "'.newToken().'">';
	//Sauvegarde / Restauration
	if ($accessright == 2) {
		infraspackplus_print_backup_restore();
	}
	print infraspackplus_load_title('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamExtraFieldsSetup').'</span>', '', dol_buildpath('/infraspackplus/img/option_tool.png', 1), 1);
	print '			<table class = "infrasplusnoborder centpercent">';
	$metas	= array('30px', '*', '156px', '120px');
	infraspackplus_print_colgroup($metas);
	$metas	= array(array(1, 1, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infraspackplus_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num				= 1;
		infraspackplus_print_btn_action('EXF', '<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCaution').'</span> '.$langs->trans('InfraSPlusParamCautionSave'), 3);
		$metas				= colorArrayToHex(explode(',', getDolGlobalString('INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR', '')));
		$num				= infraspackplus_print_input('INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamEXFValueTextColor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_EXF_VALUE_TEXT_COLOR', '')), '', $metas, 1, 1, '', $num);
		$num				= infraspackplus_print_input('INFRASPLUS_PDF_EXF_PAY_SPEC', 'input', $descPaySpec, '', array(), 1, 1, '', $num);
		$num				= infraspackplus_print_input('INFRASPLUS_PDF_EXF_DEPOSIT', 'input', $descDeposit, '', array(), 1, 1, '', $num);
		$num				= infraspackplus_print_input('INFRASPLUS_PDF_EXF_ECOTAX', 'input', $descEcoTax, '', array(), 1, 1, '', $num);
		$num				= infraspackplus_print_input('INFRASPLUS_PDF_EXF_PROPALPROV', 'input', $descPropalProv, '', array(), 1, 1, '', $num);
		// $num = 6
		$showExfBulleted	= 0;
		foreach ($listExfNotes as $exfNote) {
			if (getDolGlobalInt($exfNote, 0)) {
				$showExfBulleted	= 1;
				break;
			}
		}
		if (!empty($showExfBulleted)) {
			$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_BULLETED', 'on_off', $langs->trans('InfraSPlusParamEXFBulleted'), '', array(), 1, 1, '', $num);
		} else {
			$num++;
		}
		infraspackplus_print_hr(3);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_D', 'on_off', $langs->trans('InfraSPlusParamEXFD'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_C', 'on_off', $langs->trans('InfraSPlusParamEXFC'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_CT', 'on_off', $langs->trans('InfraSPlusParamEXFCT'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_FI', 'on_off', $langs->trans('InfraSPlusParamEXFFI'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_E', 'on_off', $langs->trans('InfraSPlusParamEXFE'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_F', 'on_off', $langs->trans('InfraSPlusParamEXFF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_MRP', 'on_off', $langs->trans('InfraSPlusParamEXFMRP'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_B', 'on_off', $langs->trans('InfraSPlusParamEXFB'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_DF', 'on_off', $langs->trans('InfraSPlusParamEXFDF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_CF', 'on_off', $langs->trans('InfraSPlusParamEXFCF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXF_FF', 'on_off', $langs->trans('InfraSPlusParamEXFFF'), '', array(), 1, 1, '', $num);
		// $num = 18
		infraspackplus_print_hr(3);
		$metas	= colorArrayToHex(explode(',', getDolGlobalString('INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR', '')));
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR', 'color', $langs->trans('InfraSPlusParamEXFLValueTextcolor').' '.$langs->trans('InfraSPlusParamActualRVB', getDolGlobalString('INFRASPLUS_PDF_EXFL_VALUE_TEXT_COLOR', '')), '', $metas, 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_D', 'on_off', $langs->trans('InfraSPlusParamEXFLD'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_C', 'on_off', $langs->trans('InfraSPlusParamEXFLC'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_CT', 'on_off', $langs->trans('InfraSPlusParamEXFLCT'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_FI', 'on_off', $langs->trans('InfraSPlusParamEXFLFI'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_E', 'on_off', $langs->trans('InfraSPlusParamEXFLE'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_F', 'on_off', $langs->trans('InfraSPlusParamEXFLF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_B', 'on_off', $langs->trans('InfraSPlusParamEXFLB'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_DF', 'on_off', $langs->trans('InfraSPlusParamEXFLDF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_CF', 'on_off', $langs->trans('InfraSPlusParamEXFLCF'), '', array(), 1, 1, '', $num);
		$num	= infraspackplus_print_input('INFRASPLUS_PDF_EXFL_FF', 'on_off', $langs->trans('InfraSPlusParamEXFLFF'), '', array(), 1, 1, '', $num);
		// $num = 29
	}
	print '			</table>
				</form>
				<a class = "infrasplusScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();
