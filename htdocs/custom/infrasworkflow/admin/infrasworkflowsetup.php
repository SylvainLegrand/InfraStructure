<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	*	\file		./infrasworkflow/admin/infrasworkflowsetup.php
	*	\ingroup	InfraS
	*	\brief		Page to setup the module InfraS
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	dol_include_once('/infrasworkflow/core/lib/infrasworkflowAdmin.lib.php');
	dol_include_once('/infrasworkflow/core/lib/infrasworkflow.lib.php');
	if (isModEnabled('infraspackplus')) {
		dol_include_once('/infraspackplus/core/modules/modinfraspackplus.class.php');
	}

	// Translations *********************************
	$langs->loadLangs(array('admin', 'errors', 'other', 'infrasworkflow@infrasworkflow'));

	// Access control *******************************
	$accessright	= !empty($user->admin) || !empty($user->hasRight('infrasworkflow', 'paramBkpRest')) ? 2 : (!empty($user->hasRight('infrasworkflow', 'paramInfraSWorkflow')) ? 1 : 0);
	if (empty($accessright)) {
		accessforbidden();
	}

	// Actions **************************************
	$form			= new Form($db);
	$formfile		= new FormFile($db);
	$formother		= new FormOther($db);
	$formcompany	= new FormCompany($db);
	if (isModEnabled('infraspackplus')) {
		$module_infraspackplus	= new modinfraspackplus($db);
	}
	$confirm_mesg					= '';
	$action							= GETPOST('action','alpha');
	$confirm						= GETPOST('confirm', 'alpha');
	$resparam						= 0;	// Variable to store the result of the actions
	$listParamsExfDeposit1			= array('type'				=> 'price',
											'pos'				=> 50,
											'size'				=> '',
											'unique'			=> 0,
											'required'			=> 0,
											'default_value'		=> '',
											'param'				=> 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',
											'alwayseditable'	=> 1,
											'perms'				=> '',
											'list'				=> '1',
											'help'				=> '',
											'computed'			=> '',
											'entity'			=> $conf->entity,
											'langfile'			=> 'infrasworkflow@infrasworkflow',
											'enabled'			=> '1',
											'totalizable'		=> 1,
											'printable'			=> 0
											);
	$listParamsExfDeposit2			= $listParamsExfDeposit1;
	$listParamsExfDeposit2['pos']	= 51;	// Position of the second deposit field
	$set							= getDolGlobalInt('INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE', 0) ? -1 : -2;	// Disable (-1) or Delete (-2) extrafields ?
	if ($action == 'setauto_normalize_ranks') {
		$value = GETPOSTINT('value');
		$res = dolibarr_set_const($db, 'INFRASWORKFLOW_AUTO_NORMALIZE_CONTRACT_RANKS', $value, 'chaine', 0, '', $conf->entity);
		if ($res > 0) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
	// Sauvegarde / Restauration
	if ($action == 'bkupParams') {
		$resparam	= infrasworkflow_bkup_module ('infrasworkflow');
	}
	if ($action == 'restoreParams') {
		$resparam	= infrasworkflow_restore_module ('infrasworkflow');
	}
	// On / Off management
	if (preg_match('/set_(.*)/', $action, $reg)) {
		$confkey	= $reg[1];
		$resparam	= dolibarr_set_const($db, $confkey, GETPOSTINT('value'), 'integer', 0, 'InfraSWorkflow module', $conf->entity);
		if ($confkey == 'INFRASWORKFLOW_DISPLAY_ZONE_COLUMN') {
			dolibarr_set_const($db, 'INFRASWORKFLOW_PS_ACTIVE_PRODUCT_INVENTORY_INVENTORY', GETPOSTINT('value'), 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
		}
		if ($confkey == 'INFRASWORKFLOW_USE_DOCUMENT_MODEL_INFRASPLUS_FR') {
			$modelName	= 'InfraSPlus_FR';
			$type		= 'invoice';
			if (GETPOSTINT('value') === 1) {
				// Activer le modèle
				$ret	= delDocumentModel($modelName, $type);
				$ret	= addDocumentModel($modelName, $type, $modelName, '');
				if (getDolGlobalString('INFRASPLUS_PDF_SEMIAUTOUPDATE', '')) {
					dolibarr_set_const($db, 'INFRASPLUS_PDF_SEMIAUTOUPDATE_OLD', 1, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
				} else {
					dolibarr_set_const($db, 'INFRASPLUS_PDF_SEMIAUTOUPDATE_OLD', 0, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
				}
				dolibarr_set_const($db, 'INFRASPLUS_PDF_SEMIAUTOUPDATE', 1, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
			} else {
				// Désactiver le modèle
				$ret	= delDocumentModel($modelName, $type);
				if (getDolGlobalString('INFRASPLUS_PDF_SEMIAUTOUPDATE_OLD', '')) {
					dolibarr_set_const($db, 'INFRASPLUS_PDF_SEMIAUTOUPDATE', 1, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
				} else {
					dolibarr_set_const($db, 'INFRASPLUS_PDF_SEMIAUTOUPDATE', 0, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
				}
			}
		} elseif (isModEnabled('infraspackplus') && $confkey == 'INFRASWORKFLOW_TRANSFER_PUBLIC_NOTES' && GETPOSTINT('value') == 1) {
			dolibarr_set_const($db, 'INFRASPLUS_PDF_OPTION_listnotep', 'doc', 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
		} elseif ($confkey == 'INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT') {
			if (GETPOSTINT('value') === 1) {
				// If we enable the control of customer account, we save the current value of SOCIETE_DISABLE_PROSPECTSCUSTOMERS and we disable it
				$controlOld	= dolibarr_get_const($db, 'SOCIETE_DISABLE_PROSPECTSCUSTOMERS', $conf->entity);
				dolibarr_set_const($db, 'INFRASWORKFLOW_DISABLE_PROSPECTSCUSTOMERS', $controlOld, 'int', 0, 'InfraSWorkflow module', $conf->entity);
				dolibarr_set_const($db, 'SOCIETE_DISABLE_PROSPECTSCUSTOMERS', 0, 'yesno', 0, '', $conf->entity);
			} else {
				// If we disable the control of customer account, we restore the previous value of SOCIETE_DISABLE_PROSPECTSCUSTOMERS
				$savedControl	= dolibarr_get_const($db, 'INFRASWORKFLOW_DISABLE_PROSPECTSCUSTOMERS', $conf->entity);
				dolibarr_set_const($db, 'SOCIETE_DISABLE_PROSPECTSCUSTOMERS', $savedControl, 'int', 0, '', $conf->entity);
				dolibarr_del_const($db, 'INFRASWORKFLOW_DISABLE_PROSPECTSCUSTOMERS', $conf->entity);
			}
		}
	}
	// Update buttons management
	if (preg_match('/update_(.*)/', $action, $reg)) {
		$list		= array('EXF'	=> array('INFRASWORKFLOW_EXF_DEPOSIT',							'INFRASWORKFLOW_EXF_SECOND_DEPOSIT',	'INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT',
											'INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION',	'INFRASWORKFLOW_DEFAULT_COMMERCIAL_SIGNATURE',	'INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', 'INFRASWORKFLOW_EXF_INVOICE_DATE_TO_CONTRACT',
											'INFRASWORKFLOW_CONTRACT_PRODUCT_PARENT_CATEGORY',		'INFRASWORKFLOW_TRANSFER_FREETEXT', 'INFRASWORKFLOW_COLOR_TO_IDENTIFY',
											'INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY',			'INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', 'INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY'));
		$confkey	= $reg[1];
		foreach ($list[$confkey] as $constname) {
			// Inventory settings : their selects are only rendered when the parent option is enabled -> never overwrite a value with an absent field
			if (in_array($constname, array('INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY', 'INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', 'INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY'))) {
				if (!GETPOSTISSET($constname)) {
					continue;
				}
				if ($constname == 'INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD') {
					$constvalue		= GETPOST($constname, 'aZ09');
					$constvalue		= $constvalue == '-1' ? '' : $constvalue;	// selectarray() returns -1 for the empty choice
					// The two zone sources are exclusive : if both are filled, only the parent category is kept (see infrasworkflow_inventoryZoneSqlParts())
					$zoneCategory	= GETPOSTISSET('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY') ? max(0, GETPOSTINT('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY')) : getDolGlobalInt('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 0);
					if (!empty($constvalue) && $zoneCategory > 0) {
						$constvalue	= '';
						setEventMessages($langs->trans('InfraSWorkflowInventoryZoneExclusive'), null, 'warnings');
					}
				} else {
					$constvalue		= max(0, GETPOSTINT($constname));	// select_all_categories() returns -1 for the empty choice
				}
				$resparam	= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
				continue;
			}
			if ($constname == 'INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION') {
				$constvalue	= GETPOST($constname, 'array');
				$constvalue	= is_array($constvalue) ? implode(',', $constvalue) : '';
			}
			// Extrafield Montant du premier acompte (en valeur monétaire)
			elseif ($constname == 'INFRASWORKFLOW_EXF_DEPOSIT') {
				$constvalue	= GETPOST($constname, 'alpha');
				// Contrôle du code utilisé pour l'attribut (longueur, caractères spéciaux, etc...)
				if (!empty($constvalue) && infrasworkflow_check_extf_name ($constvalue) < 0) {
					$resparam	= 0;	// Message d'erreur dans la fonction infrasworkflow_check_extf_name
					continue;
				}
				$name	= getDolGlobalString('INFRASWORKFLOW_EXF_DEPOSIT', '');
				if (empty($constvalue) || (!empty($name) && $name != $constvalue)) {	// If the constant is empty or if the name changed
					// Disable or delete the old extrafield
					$resparam	= infrasworkflow_manage_extf ($set, '', 'INFRASWORKFLOW_EXF_DEPOSIT', 'InfraSWorkflowParamLabelExfDeposit', array('propal'), $listParamsExfDeposit1);	// delete
					// If the name change we may need to create a new extrafield => next turn
				}
			}
			// Extrafield Montant du deuxième acompte (en valeur monétaire)
			elseif ($constname == 'INFRASWORKFLOW_EXF_SECOND_DEPOSIT') {
				$constvalue	= GETPOST($constname, 'alpha');
				// Contrôle du code utilisé pour l'attribut (longueur, caractères spéciaux, etc...)
				if (!empty($constvalue) && infrasworkflow_check_extf_name ($constvalue) < 0) {
					$resparam	= 0;	// Message d'erreur dans la fonction infrasworkflow_check_extf_name
					continue;
				}
				$name			= getDolGlobalString('INFRASWORKFLOW_EXF_SECOND_DEPOSIT', '');
				if (empty($constvalue) || (!empty($name) && $name != $constvalue)) {	// If the constant is empty or if the name changed
					// Disable or delete the old extrafield
					$resparam	= infrasworkflow_manage_extf ($set, '', 'INFRASWORKFLOW_EXF_SECOND_DEPOSIT', 'InfraSWorkflowParamLabelExfSecondDeposit', array('propal'), $listParamsExfDeposit2);	// delete
					// If the name change we may need to create a new extrafield => next turn
				}
			}
			if (in_array($constname, ['INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT', 'INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', 'INFRASWORKFLOW_EXF_INVOICE_DATE_TO_CONTRACT', 'INFRASWORKFLOW_TRANSFER_FREETEXT'])) {
				$constvalue	= implode(',', GETPOST($constname, 'array'));
			}
			if ($constname == 'INFRASWORKFLOW_CONTRACT_PRODUCT_PARENT_CATEGORY') {
				$constvalue	= GETPOST($constname, 'int');
			}
			if ($constname == 'INFRASWORKFLOW_COLOR_TO_IDENTIFY') {
				$constvalue	= ltrim(GETPOST($constname, 'alpha'), '#');
			}
			// Writing the constant
			$resparam		= dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
		}
	}
	// Create or update (enable) extrafields for deposits
	if ($action == 'setExfDeposit')	{
		$resparam	= infrasworkflow_manage_extf (1, '', 'INFRASWORKFLOW_EXF_DEPOSIT', 'InfraSWorkflowParamLabelExfDeposit', array('propal'), $listParamsExfDeposit1);	// create
	}
	if ($action == 'setExfSecondDeposit') {
		$resparam	= infrasworkflow_manage_extf (1, '', 'INFRASWORKFLOW_EXF_SECOND_DEPOSIT', 'InfraSWorkflowParamLabelExfSecondDeposit', array('propal'), $listParamsExfDeposit2);	// create
	}
	// Retour => message Ok ou Ko
	if ($resparam == 1) {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	}
	if ($resparam == -1) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	}

	// init variables *******************************
	// If the proposal suggest down payment invoice creation is not enabled, we disable or delete the extrafields for deposits
	if (!getDolGlobalInt('PROPOSAL_SUGGEST_DOWN_PAYMENT_INVOICE_CREATION', 0)) {
		$res	= infrasworkflow_manage_extf($set, '', 'INFRASWORKFLOW_EXF_DEPOSIT', 'InfraSWorkflowParamLabelExfDeposit', array('propal'), $listParamsExfDeposit1);
		$res	= infrasworkflow_manage_extf($set, '', 'INFRASWORKFLOW_EXF_SECOND_DEPOSIT', 'InfraSWorkflowParamLabelExfSecondDeposit', array('propal'), $listParamsExfDeposit2);
	} else {
		$exfDepositIsSet		= infrasworkflow_manage_extf (0, '', 'INFRASWORKFLOW_EXF_DEPOSIT', 'InfraSWorkflowParamLabelExfDeposit', array('propal'), $listParamsExfDeposit1);	// update
		$exfDepositIsSet		= is_array($exfDepositIsSet) && $exfDepositIsSet['propal'] < 1 ? 0 : $exfDepositIsSet;
		$textDescDeposit		= $langs->trans('InfraSWorkflowParamSetExf', getDolGlobalString('INFRASWORKFLOW_EXF_DEPOSIT', ''));
		$descDeposit			= $langs->trans('InfraSWorkflowParamExfDeposit').($exfDepositIsSet == 0 ? ' <button class = "button infrasworkflowheight20 infrasworkflownopadding" type = "submit" value = "setExfDeposit" name = "action">'.$textDescDeposit.'</button>': '');
		$exfSecondDepositIsSet	= infrasworkflow_manage_extf (0, '', 'INFRASWORKFLOW_EXF_SECOND_DEPOSIT', 'InfraSWorkflowParamLabelExfSecondDeposit', array('propal'), $listParamsExfDeposit2);	// update
		$exfSecondDepositIsSet	= is_array($exfSecondDepositIsSet) && $exfSecondDepositIsSet['propal'] < 1 ? 0 : $exfSecondDepositIsSet;
		$textDescSecondDeposit	= $langs->trans('InfraSWorkflowParamSetExf', getDolGlobalString('INFRASWORKFLOW_EXF_SECOND_DEPOSIT', ''));
		$descSecondDeposit		= $langs->trans('InfraSWorkflowParamExfSecondDeposit').($exfSecondDepositIsSet == 0 ? ' <button class = "button infrasworkflowheight20 infrasworkflownopadding" type = "submit" value = "setExfSecondDeposit" name = "action">'.$textDescSecondDeposit.'</button>': '');
		$exf_deposit			= getDolGlobalString('INFRASWORKFLOW_EXF_DEPOSIT', '');
	}
	// Load the array of extrafields definition $this->attributes for propal and create a multiselection array
	$extrafields			= new ExtraFields($db);
	// Load extrafields for propals
	$extrafields->fetch_name_optionals_label('propal');
	$exfPropalList			= $extrafields->attributes['propal']['label'];	// Array of extrafields for propals (name => label)
	// Load extrafields for invoices
	$extrafields->fetch_name_optionals_label('facture');
	$exfFactureList			= $extrafields->attributes['facture']['label'];	// Array of extrafields for invoices (name => label)
	// Create list of common extrafields between propals and invoices
	$exfPropalInvoiceList	= array();
	if (!empty($exfPropalList) && !empty($exfFactureList)) {
		foreach ($exfPropalList as $key => $label) {
			if (isset($exfFactureList[$key])) {
				// Exclude separators and inactive extrafields
				$type		= isset($extrafields->attributes['facture']['type'][$key]) ? $extrafields->attributes['facture']['type'][$key] : '';
				$enabled	= isset($extrafields->attributes['facture']['enabled'][$key]) ? $extrafields->attributes['facture']['enabled'][$key] : '1';
				// Evaluate the enabled condition if it's a PHP expression
				if (!empty($enabled) && $enabled != '1' && $enabled != '0') {
					// It's a PHP expression, evaluate it
					$enabled = verifCond($enabled) ? '1' : '0';
				}
				// Skip if it's a separator or not enabled
				if ($type == 'separate' || $enabled != '1') {
					continue;
				}
				$exfPropalInvoiceList[$key] = $label;	// Add to common list if exists in both propal and invoice
			}
		}
	}
	// Load free text mentions for transfer to invoices and create a multiselection array
	$mentionDoc				= getDolGlobalString('INFRASPLUS_PDF_OPTION_listfreet', '') != 'doc';
	$textFreeText			= $langs->trans('InfraSWorkflowParamTransferFreetext', ($mentionDoc ? '<span class = "infrasworkflowcaution">'.$langs->transnoentities('InfraSWorkflowParamTransferFreetextInfo2').'<a href="'.dol_buildpath('custom/infraspackplus/admin/generation.php', 1).'" class = "infrasworkflowcautionbold">'.$langs->transnoentities('InfraSWorkflowParamTransferFreetextInfo3').'</a></span>' : $langs->transnoentities('InfraSWorkflowParamTransferFreetextInfo')));
	$arrayMentionSelected	= explode(',', getDolGlobalString('INFRASWORKFLOW_TRANSFER_FREETEXT', ''));
	$arrayMention			= select_infraspackplus_dict('c_infraspackplus_mention', 1, 'INFRASWORKFLOW_TRANSFER_FREETEXT', 1, '', 1, '', 1);
	// Create list of common free text mentions between propals and invoices
	foreach ($arrayMention as $code => $label) {
		if (!getDolGlobalString('PROPOSAL_FREE_TEXT_'.$code, '') || !getDolGlobalString('INVOICE_FREE_TEXT_'.$code, '')) {
			unset($arrayMention[$code]);
		}
	}
	// Load extrafields for contracts
	$extrafields->fetch_name_optionals_label('contrat');
	$exfContractList		= $extrafields->attributes['contrat']['label'];	// Array of extrafields for contracts (name => label)
	// Create list of common extrafields between contracts and invoices
	$exfContractInvoiceList	= array();
	if (!empty($exfContractList) && !empty($exfFactureList)) {
		foreach ($exfContractList as $key => $label) {
			// Check if exists in both contract and invoice
			if (isset($exfFactureList[$key])) {
				// Exclude separators and inactive extrafields
				$type		= isset($extrafields->attributes['contrat']['type'][$key]) ? $extrafields->attributes['contrat']['type'][$key] : '';
				$enabled	= isset($extrafields->attributes['contrat']['enabled'][$key]) ? $extrafields->attributes['contrat']['enabled'][$key] : '1';
				// Evaluate the enabled condition if it's a PHP expression
				if (!empty($enabled) && $enabled != '1' && $enabled != '0') {
					// It's a PHP expression, evaluate it
					$enabled = verifCond($enabled) ? '1' : '0';
				}
				// Skip if it's a separator or not enabled
				if ($type == 'separate' || $enabled != '1') {
					continue;
				}
				$exfContractInvoiceList[$key] = $label;	// Add to common list if exists in both contract and invoice
			}
		}
	}
	$arrayExfPropalSelected		= !empty(GETPOST('INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT', 'array')) ? GETPOST('INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT', 'array') : array_filter(explode(',', getDolGlobalString('INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT', '')));
	$arrayExfContractSelected	= !empty(GETPOST('INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', 'array')) ? GETPOST('INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', 'array') : array_filter(explode(',', getDolGlobalString('INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', '')));
	// Build list of date-type contract extrafields, excluding those already selected in INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT
	$exfContractDateList		= array();
	if (!empty($exfContractList)) {
		foreach ($exfContractList as $key => $label) {
			$type		= isset($extrafields->attributes['contrat']['type'][$key]) ? $extrafields->attributes['contrat']['type'][$key] : '';
			$enabled	= isset($extrafields->attributes['contrat']['enabled'][$key]) ? $extrafields->attributes['contrat']['enabled'][$key] : '1';
			if (!empty($enabled) && $enabled != '1' && $enabled != '0') {
				$enabled = verifCond($enabled) ? '1' : '0';
			}
			if ($enabled != '1') {
				continue;
			}
			// Only date and datetime types
			if (!in_array($type, array('date', 'datetime'))) {
				continue;
			}
			// Exclude extrafields already selected in INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT
			if (in_array($key, $arrayExfContractSelected)) {
				continue;
			}
			$exfContractDateList[$key] = $label;
		}
	}
	$arrayExfContractDateSelected	= !empty(GETPOST('INFRASWORKFLOW_EXF_INVOICE_DATE_TO_CONTRACT', 'array')) ? GETPOST('INFRASWORKFLOW_EXF_INVOICE_DATE_TO_CONTRACT', 'array') : array_filter(explode(',', getDolGlobalString('INFRASWORKFLOW_EXF_INVOICE_DATE_TO_CONTRACT', '')));
	// If InfraSPackPlus is enabled and the option to transfer public notes is not set to 'doc', we disable the option in InfraSWorkflow
	if (isModEnabled('infraspackplus')) {
		$version_infraspackplus	= $module_infraspackplus->version;
		if (getDolGlobalString('INFRASPLUS_PDF_OPTION_listnotep', '') != 'doc') {
			dolibarr_set_const($db, 'INFRASWORKFLOW_TRANSFER_PUBLIC_NOTES', '0', 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
		}
		if (getDolGlobalString('INFRASPLUS_PDF_OPTION_listfreet', '') != 'doc') {
			dolibarr_set_const($db, 'INFRASWORKFLOW_TRANSFER_FREETEXT', '', 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
		}
	}
	$extrafields_contractline	= new ExtraFields($db);
	$extrafields_contractline->fetch_name_optionals_label('contratdet');
	$available_extrafields		= array('none' => $langs->trans('None'));
	if (!empty($extrafields_contractline->attributes['contratdet']['label'])) {
		foreach ($extrafields_contractline->attributes['contratdet']['label'] as $key => $label) {
			$available_extrafields[$key] = $label;
		}
	}
	// Inventory "Zone" column : product extrafields usable as a zone (name => label), single-value types only
	$exfProductZoneList			= array();
	if (isModEnabled('stock')) {
		$extrafields->fetch_name_optionals_label('product');
		if (!empty($extrafields->attributes['product']['label'])) {
			foreach ($extrafields->attributes['product']['label'] as $key => $label) {
				$type	= isset($extrafields->attributes['product']['type'][$key]) ? $extrafields->attributes['product']['type'][$key] : '';
				if (!in_array($type, array('varchar', 'select', 'sellist', 'int', 'radio'))) {
					continue;
				}
				$exfProductZoneList[$key]	= $langs->trans($label).' ('.$key.')';
			}
		}
		// Self-healing : the substitution page constant must follow the "Zone" column option (see action set_)
		if (getDolGlobalInt('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 0) != getDolGlobalInt('INFRASWORKFLOW_PS_ACTIVE_PRODUCT_INVENTORY_INVENTORY', 0)) {
			dolibarr_set_const($db, 'INFRASWORKFLOW_PS_ACTIVE_PRODUCT_INVENTORY_INVENTORY', getDolGlobalInt('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 0), 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
		}
	}
	// Récupérer la sélection actuelle
	$selected_extrafields_json	= getDolGlobalString('INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION', '[]');
	$selected_extrafields		= json_decode($selected_extrafields_json, true);
	if (!is_array($selected_extrafields)) {
		$selected_extrafields	= array();
	}

	// View *****************************************
	$page_name		= $langs->trans('Module500080Name').' - '.$langs->trans('InfraSWorkflowParams');
	llxHeader('', $page_name);
	echo $confirm_mesg;
	$linkback		= !empty($user->admin) ? '<a href = "'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>' : '';
	print load_fiche_titre($page_name, $linkback, 'title_setup');
	$titleoption	= '';

	// Configuration header *************************
	$head			= infrasworkflow_admin_prepare_head();
	$picto			= 'infrasworkflow@infrasworkflow';
	print dol_get_fiche_head($head, 'settings', $langs->trans('modcomnameInfraSWorkflow'), 0, $picto);

	// setup page goes here *************************
	if ($conf->use_javascript_ajax) {
		print '	<script src = "'.dol_buildpath('/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js', 1).'"></script>
				<script type = "text/javascript">
					var cookieName = "infrasworkflow_tblPSexp";
					jQuery(document).ready(function() {
						var tblPSexp = "";
						$.isSet = function(testVar) {
							return typeof(testVar) !== "undefined" && testVar !== null && testVar !== "";
						};
						if ($.cookie && $.isSet($.cookie(cookieName))) {
							tblPSexp = $.cookie(cookieName);
						}
						$(".toggle_bloc").show();
						if (tblPSexp) {
					//		$("[name=" + tblPSexp + "]").toggle();
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
							if ($(this).scrollTop() > 200 ) {
								$(".infrasworkflowScrollUp").css("right", "30px");
							} else {
								$(".infrasworkflowScrollUp").removeAttr("style");
							}
						});
					});
				</script>';
	}
	print '	<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
				<input type = "hidden" name = "token" value = "'.newToken().'">';
	// Sauvegarde / Restauration
	if ($accessright == 2) {
		infrasworkflow_print_backup_restore();
	}
	// Comportement général -> génération automatique, 1 fichier par modèle
	print '		<div class = "foldable">';
	print infrasworkflow_load_title('<span class = "infrasworkflowtitleparam">'.$langs->trans('InfraSWorkflowParamTitle').'</span>', $titleoption, dol_buildpath('/infrasworkflow/img/option_tool.png', 1), 1, '', '');
	print '			<table name = "tblAG" class = "noborder toggle_bloc centpercent">';
	$metas	= array('30px', '*', '90px', '156px', '120px');
	infrasworkflow_print_colgroup($metas);
	$metas	= array(array(1, 2, 1, 1), 'NumberingShort', 'Description', $langs->trans('Status').' / '.$langs->trans('Value'), '&nbsp;');
	infrasworkflow_print_liste_titre($metas);
	if (!empty($accessright)) {
		$num	= 1;
		infrasworkflow_print_btn_action('EXF', '<span class = "infrasworkflowcaution">'.$langs->trans('InfraSWorkflowCaution').'</span> '.$langs->trans(key: 'InfraSWorkflowParamCautionSave'), 4);
		infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageDragAndDrop', ' bold');
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_DOCUMENTS_DRAGDROP','on_off',$langs->trans('InfraSWorkflowParamDocumentsDragdrop'),'',array(),2,1,'',$num);
		if (getDolGlobalInt('INFRASWORKFLOW_DOCUMENTS_DRAGDROP', 0)) {
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_DOCUMENTS_DRAGDROP_PRODUCT_NO_MASK','on_off',$langs->trans('InfraSWorkflowParamDocumentsDragdropProductNoMask'),'',array(),2,1,'',$num);
		} else {
			$num++;
		}
		// $num = 3
		if (isModEnabled('facture') && isModEnabled('propal')) {
			infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageDeposits', ' bold');
			$num	= infrasworkflow_print_input('PROPOSAL_SUGGEST_DOWN_PAYMENT_INVOICE_CREATION', 'on_off', $langs->trans('DolibarrProposalSuggestDepositInvoice'), '', array(), 2, 1, '', $num);
			if (getDolGlobalInt('PROPOSAL_SUGGEST_DOWN_PAYMENT_INVOICE_CREATION', 0)) {
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_EXF_DEPOSIT', 'input', $descDeposit, '', array(), 2, 1, '', $num);
				if (!empty($exf_deposit)) {
					$num	= infrasworkflow_print_input('INFRASWORKFLOW_EXF_SECOND_DEPOSIT', 'input', $descSecondDeposit, '', array(), 2, 1, '', $num);
					$num	= infrasworkflow_print_input('INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT','on_off',$langs->trans('InfraSWorkflowParamCreateFirstAutoDeposit'),'',array(),2,1,'',$num);
					if (getDolGlobalInt('INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT', 0)) {
						$num	= infrasworkflow_print_input('INFRASWORKFLOW_VALIDATE_FIRST_AUTO_DEPOSIT', 'on_off', $langs->trans('InfraSWorkflowParamValidateFirstAutoDeposit'), '', array(), 2, 1, '', $num);
					} else {
						$num++;
					}
					$metas	= $form->multiselectarray('INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT', $exfPropalInvoiceList, $arrayExfPropalSelected, 0, 0, '', 0, '100%');
					$num	= infrasworkflow_print_input('INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT', 'select', $langs->trans('InfraSWorkflowParamNoTransferPropalToDeposit'), '', $metas, 1, 2, '', $num);
				} else {
					$num	+= 4;
				}
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_LINK_DEPOSITS_TO_FINAL_INVOICE', 'on_off', $langs->trans('InfraSWorkflowParamLinkDepositsToFinalInvoice'), '', array(), 2, 1, '', $num);
			} else {
				$num	+= 6;
			}
		} else {
			$num	+= 7;
		}
		// $num = 10
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_CREDIT_NOTE_TRANSFER_NOTES', 'on_off', $langs->trans('InfraSWorkflowParamCreditNoteTransferNotes'), '', array(), 2, 1, '', $num);
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_ORDER_SHOW_THIRDPARTY_TYPE', 'on_off', $langs->trans('InfraSWorkflowParamOrderShowThirdpartyType'), '', array(), 2, 1, '', $num);

		// $num = 12
		infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageInvoices', ' bold');
		if (isModEnabled('facture') && isModEnabled('infraspackplus') && getDolGlobalInt('PROPOSAL_SUGGEST_DOWN_PAYMENT_INVOICE_CREATION', 0) && version_compare($version_infraspackplus, '15.7.0', '>=')) {
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_USE_DOCUMENT_MODEL_INFRASPLUS_FR', 'on_off', $langs->trans('InfraSWorkflowParamUseDocumentModelInfraSPlusFR'), '', array(), 2, 1, '', $num);
		} else {
			$num++;
		}
		// $num = 13
		if (isModEnabled('facture') && isModEnabled('propal') && isModEnabled('infraspackplus') && version_compare($version_infraspackplus, '15.7.0', '>=')) {
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_TRANSFER_PUBLIC_NOTES','on_off',$langs->trans('InfraSWorkflowParamTransferPublicNotes'),'',array(),2,1,'',$num);
		} else {
			$num++;
		}
		if (isModEnabled('facture') && isModEnabled('propal') && isModEnabled('infraspackplus')) {
			$metas	= $form->multiselectarray('INFRASWORKFLOW_TRANSFER_FREETEXT', $arrayMention, $arrayMentionSelected, 0, 0, '', 0, '100%', ($mentionDoc ? 'disabled' : ''));
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_TRANSFER_FREETEXT', 'select', $textFreeText, '', $metas, 1, 2, '', $num);
		} else {
			$num++;
		}
		// $num = 15
		if (isModEnabled('facture')) {
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_INVOICE_VALIDATION','on_off',$langs->trans('InfraSWorkflowParamInvoiceValidation'),'',array(),2,1,'',$num);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_INVOICE_REGENARATION_ON_CLASSIFY_PAID','on_off',$langs->trans('InfraSWorkflowParamInvoiceRegenarationOnClassifyPaid'),'',array(),2,1,'',$num);
			if (isModEnabled('propal')) {
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_INVOICES_CLASSIFY_BILLED_PROPALS','on_off',$langs->trans('InfraSWorkflowParamInvoicesClassifyBilledPropals'),'',array(),2,1,'',$num);
			} else {
				$num ++;
			}
		} else {
			$num += 3;
		}
		// $num = 18
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_CREATE_ORDER_SUPPLIER','on_off',$langs->trans('InfraSWorkflowParamCreateOrderSupplier'),'',array(),2,1,'',$num);
		if (isModEnabled('supplier_invoice')) {
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_SUPPLIER_INVOICE_MASS_PAID','on_off',$langs->trans('InfraSWorkflowParamSupplierInvoiceMassPaid'),'',array(),2,1,'',$num);
		} else {
			$num++;
		}
		if (isModEnabled('supplier_order')) {
			$descRcvdOnBilled	= $langs->trans('InfraSWorkflowParamSupplierOrderReceivedOnBilled');
			if (isModEnabled('stock')) {
				$descRcvdOnBilled	.= ' <span class = "infrasworkflowcaution">'.$langs->trans('InfraSWorkflowSupplierOrderBilledStockWarning').'</span>';
			}
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_SUPPLIER_ORDER_RECEIVED_ON_BILLED','on_off',$descRcvdOnBilled,$langs->trans('InfraSWorkflowParamSupplierOrderReceivedOnBilledHelp'),array(),2,1,'',$num);
		} else {
			$num++;
		}
		// num = 21
		infrasworkflow_print_subTitle(4, 'InfraSWorkflowParamMassCreateBills', ' bold');
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_MASS_CREATEBILLS_COPY_NOTES', 'on_off', $langs->trans('InfraSWorkflowParamCopyNotesFromOrderToInvoice'), '', array(), 2, 1, '', $num);
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_MASS_CREATEBILLS_SET_AUTHOR', 'on_off', $langs->trans('InfraSWorkflowParamSelectionUserAsCreator'), '', array(), 2, 1, '', $num);
		$num	= infrasworkflow_print_input('INFRASWORKFLOW_MASS_CREATEBILLS_DEDUP_REF', 'on_off', $langs->trans('InfraSWorkflowParamDeduplicationOrderNumber'), '', array(), 2, 1, '', $num);

		// $num = 24
		if (isModEnabled('societe')) {
			infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageThirdparties', ' bold');
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_MERGE_CLEAN_DUPLICATE_SUPPLIER_PRICES','on_off',$langs->trans('InfraSWorkflowParamMergeCleanSupplierPrices'),$langs->trans('InfraSWorkflowParamMergeCleanSupplierPricesHelp'),array(),2,1,'',$num);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT','on_off',$langs->trans('InfraSWorkflowParamCustomerAccount'),'',array(),2,1,'',$num);
			if (getDolGlobalInt('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT', 0)) {
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_CLIENT_CODE_FIELDS','on_off',$langs->trans('InfraSWorkflowParamCodeClient'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_TVA_ASSUJ_FIELDS','on_off',$langs->trans('InfraSWorkflowParamAssujTVA'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_TVA_FIELDS','on_off',$langs->trans('InfraSWorkflowParamTVA'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_CODE_COMPTA_FIELDS','on_off',$langs->trans('InfraSWorkflowParamCodeComptaClient'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_MODE_REGLEMENT_FIELDS','on_off',$langs->trans('InfraSWorkflowParamModeReglement'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_COND_REGLEMENT_FIELDS','on_off',$langs->trans('InfraSWorkflowParamCondReglement'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_DATE_SIGN_FIELDS','on_off',$langs->trans('InfraSWorkflowParamDateOfSignature'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_SIRET_FIELDS','on_off',$langs->trans('InfraSWorkflowParamSiret'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_MAIL_FIELDS','on_off',$langs->trans('InfraSWorkflowParamEmail'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_COUNTRY_FIELDS','on_off',$langs->trans('InfraSWorkflowParamCountry'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_PARENT_FIELDS','on_off',$langs->trans('InfraSWorkflowParamParent'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_CONTACT_FIELDS','on_off',$langs->trans('InfraSWorkflowParamContact'),'',array(),2,1,'',$num);
			} else {
				$num	+= 12;
			}
		} else {
			$num	+= 14;
		}
		// $num = 38
		if (isModEnabled('product')) {
			infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageProducts', ' bold');
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_PRODUCT_VALIDATION_CONTROL','on_off',$langs->trans('InfraSWorkflowParamProductValidation'),'',array(),2,1,'',$num);
			if (getDolGlobalInt('INFRASWORKFLOW_PRODUCT_VALIDATION_CONTROL', 0)) {
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCodeSell'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_INTRA_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCodeSellIntra'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_EXPORT_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCodeSellExport'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCodeBuy'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_INTRA_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCodeBuyIntra'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_EXPORT_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCodeBuyExport'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_CUSTOM_CODE_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCustomCode'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_WEIGHT_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductWeight'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_COUNTRY_ID_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductCountry'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_DESIRED_STOCK_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductDesiredStock'),'',array(),2,1,'',$num);
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTROL_DEFAULT_WAREHOUSE_FIELDS','on_off',$langs->trans('InfraSWorkflowParamProductDefaultWarehouse'),'',array(),2,1,'',$num);
			} else {
				$num += 11;
			}
		} else {
			$num	+= 12;
		}
		// $num = 50
		if (isModEnabled('stock')) {
			infrasworkflow_print_subTitle(4, 'InfraSWorkflowParamManageStock', ' bold');
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_IDENTIFY_ORDER_LINES_NO_SHIPPABLE','on_off',$langs->trans('InfraSWorkflowParamIdentifyOrderLines'),'',array(),2,1,'',$num);
			if (getDolGlobalInt('INFRASWORKFLOW_IDENTIFY_ORDER_LINES_NO_SHIPPABLE', 0)) {
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_COLOR_TO_IDENTIFY','color', $langs->trans('InfraSWorkflowParamColorToIdentify'),'', array(), 2, 1,'',$num);
			} else {
				$num++;
			}
		}
		// $num = 52
		if (isModEnabled('stock')) {
			infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageInventory', ' bold');
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_DISPLAY_SORTED_EMPTY_STOCK', 'on_off', $langs->trans('InfraSWorkflowDisplaySortedEmptyStock'), $langs->trans('InfraSWorkflowDisplaySortedEmptyStockHelp'), array(), 2, 1, '', $num);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE', 'on_off', $langs->trans('InfraSWorkflowHideItemsTaggedObsolete'), '', array(), 2, 1, '', $num);
			if (getDolGlobalInt('INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE', 0)) {
				$metas	= $form->select_all_categories(Categorie::TYPE_PRODUCT, getDolGlobalInt('INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY', 0), 'INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY', 64, 0, 0, 0, 'minwidth300');
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY', 'select', $langs->trans('InfraSWorkflowInventoryObsoleteCategory'), $langs->trans('InfraSWorkflowInventoryObsoleteCategoryHelp'), $metas, 2, 1, '', $num);
			} else {
				$num++;
			}
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 'on_off', $langs->trans('InfraSWorkflowDisplayZoneColumn'), $langs->trans('InfraSWorkflowDisplayZoneColumnHelp'), array(), 2, 1, '', $num);
			if (getDolGlobalInt('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 0)) {
				$metas	= $form->selectarray('INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', $exfProductZoneList, getDolGlobalString('INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', ''), 1, 0, 0, '', 0, 0, 0, '', 'minwidth300');
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', 'select', $langs->trans('InfraSWorkflowInventoryZoneExtrafield'), $langs->trans('InfraSWorkflowInventoryZoneExtrafieldHelp'), $metas, 1, 2, '', $num);
				$title	= '<b class = "infrasworkflowcaution">'.$langs->trans('InfraSWorkflowInventoryOr').'</b>';
				$metas	= $form->select_all_categories(Categorie::TYPE_PRODUCT, getDolGlobalInt('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 0), 'INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 64, 0, 0, 0, 'minwidth300');
				$num	= infrasworkflow_print_input('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 'select', $title.$langs->trans('InfraSWorkflowInventoryZoneParentCategory'), $langs->trans('InfraSWorkflowInventoryZoneParentCategoryHelp'), $metas, 2, 1, '', $num);
			} else {
				$num	+= 2;
			}
		} else {
			$num	+= 6;
		}
		// $num = 58
		if (isModEnabled('contrat')) {
			infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageContracts', ' bold');
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE','on_off',$langs->trans('InfraSWorkflowParamContractProductsFromSource'),'',array(),2,1,'',$num);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTRACT_FROM_VALIDATED_PROPAL','on_off',$langs->trans('InfraSWorkflowParamContractFromValidatedPropal'),'',array(),2,1,'',$num);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTRACT_EMAIL_PROV','on_off',$langs->trans('InfraSWorkflowParamContractEmailProv'),'',array(),2,1,'',$num);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTRACT_SERVICE_AUTO','on_off',$langs->trans('InfraSWorkflowParamContractServiceAuto'),'',array(),2,1,'',$num);
			$metas	= $form->select_dolusers(getDolGlobalInt('INFRASWORKFLOW_DEFAULT_COMMERCIAL_SIGNATURE'), 'INFRASWORKFLOW_DEFAULT_COMMERCIAL_SIGNATURE', 1, null);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_DEFAULT_COMMERCIAL_SIGNATURE', 'select', $langs->trans('InfraSWorkflowParamDefaultCommercialSignature'), '', $metas, 1, 2, '', $num);
			$metas	= $form->multiselectarray('INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', $exfContractInvoiceList, $arrayExfContractSelected, 0, 0, '', 0, '100%');
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', 'select', $langs->trans('InfraSWorkflowParamInvoiceToContract'), '', $metas, 1, 2, '', $num);
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_AUTO_NORMALIZE_CONTRACT_RANKS','on_off',$langs->trans('InfraSWorkflowParamAutoNormalizeContractRanks'),$langs->trans('InfraSWorkflowParamAutoNormalizeContractRanksHelp'),array(),2,1,'',$num);
			if (!empty($available_extrafields)) {
				$selected	= explode(',', getDolGlobalString('INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION', ''));
				$metas		= Form::multiselectarray('INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION', $available_extrafields, $selected, 0, 0, 'centpercent');
				$num		= infrasworkflow_print_input('', 'select', $langs->trans('InfraSWorkflowParamContractLineExtrafieldsSelection'), '', $metas, 1, 2, '', $num);
			} else {
				$num++;
			}
			// Catégorie parente pour produits du contrat (alternative aux extrafields)
			$metas	= $form->select_all_categories(Categorie::TYPE_PRODUCT, getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCT_PARENT_CATEGORY', 0), 'INFRASWORKFLOW_CONTRACT_PRODUCT_PARENT_CATEGORY', 64, 0, 0, 0, 'minwidth300');
			$num	= infrasworkflow_print_input('INFRASWORKFLOW_CONTRACT_PRODUCT_PARENT_CATEGORY', 'select', $langs->trans('InfraSWorkflowParamContractProductParentCategory'), $langs->trans('InfraSWorkflowParamContractProductParentCategoryHelp'), $metas, 1, 2, '', $num);
			$num	= infrasworkflow_print_input('CHANGE_TIERS_CONTRACT_FROM_PROPAL_COMMANDE','on_off',$langs->trans('InfraSWorkflowParamChangeTiersContractFromPropalCommande'),'',array(),2,1,'',$num);
		} else {
			$num += 10;
		}
		// $num = 68
		infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageExtraFields', ' bold');
		$num		= infrasworkflow_print_input('INFRASWORKFLOW_CLONE_EXTRAFIELDS','on_off',$langs->trans('InfraSWorkflowEnableExfClone'),'',array(),2,1,'',$num);
		$num		= infrasworkflow_print_input('INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE','on_off',$langs->trans('InfraSWorkflowEnableExfTrashMode'),'',array(),2,1,'',$num);
		// $num = 70
		infrasworkflow_print_subTitle(4, 'InfrasworkflowExtraFieldsPropagation', ' bold');
		if (isModEnabled('societe')) {
			if (isModEnabled('facture')) {
				$num	= infrasworkflow_print_input('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_INVOICE', 'on_off', $langs->trans('DolibarrPropagateExtraFieldsToInvoice'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
			if (isModEnabled('commande')) {
				$num	= infrasworkflow_print_input('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_ORDER', 'on_off', $langs->trans('DolibarrPropagateExtraFieldsToCustomerOrder'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
			if (isModEnabled('supplier_order')) {
				$num	= infrasworkflow_print_input('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_SUPPLIER_ORDER', 'on_off', $langs->trans('DolibarrPropagateExtraFieldsToSupplierOrder'), '', [], 2, 1, '', $num);
			} else {
				$num++;
			}
		} else {
			$num += 3;
		}
		// $num = 73
		if (isModEnabled('product')) {
			$num	= infrasworkflow_print_input('PRODUCT_LOAD_EXTRAFIELD_INTO_OBJECTLINES','on_off',$langs->trans('DolibarrProductLoadExtrafieldsIntoObjectLines'),'',[],2,1,'',$num);
		} else {
			$num ++;
		}
		// $num = 74
		infrasworkflow_print_subTitle(4, 'InfraSWorkflowManageMedias', ' bold');
		$num		= infrasworkflow_print_input('INFRASWORKFLOW_MEDIAS_BROWSER_ALL_USERS','on_off',$langs->trans('InfraSWorkflowParamMediasBrowserAllUsers'),$langs->trans('InfraSWorkflowParamMediasBrowserAllUsersHelp'),array(),2,1,'',$num);
		// $num = 75
	}
	print '			</table>
				</div>';
	print '	</form>
			<a class = "infrasworkflowScrollUp" href = "#top">'.img_picto($langs->trans('Top'), 'angle-double-up').'</a>';
	print dol_get_fiche_end();
	llxFooter();
	$db->close();

