<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>			InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Lucky Ranasolonirina - <technique@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infrasworkflow/tabs/infrasworkflow_contrat_products_tab.php
	*	\ingroup	InfraS
	*	\brief		actions for infrasworkflow module (hook)
	************************************************/
	require_once '../config.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	dol_include_once('/infrasworkflow/class/actions_infrasworkflow.class.php');
	dol_include_once('/infrasworkflow/core/lib/infrasworkflow.lib.php');
	if (isModEnabled('project')) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
	}
	global $db, $langs, $user, $conf, $hookmanager, $mysoc;

	$langs->loadLangs(array('contracts','products','companies','bills', 'invoices', 'infrasworkflow@infrasworkflow', 'main'));

	$socid		= GETPOSTINT('socid');
	$id			= GETPOSTINT('id');
	$ref		= GETPOST('ref','alpha');
	$action		= GETPOST('action', 'aZ09');
	$lineid		= GETPOSTINT('lineid');
	$backtopage	= GETPOST('backtopage','alpha');
	$confirm	= GETPOST('confirm', 'alpha');
	$token		= newToken();
	$object		= new Contrat($db);
	if ($object->fetch($id, $ref) <= 0) {
		accessforbidden();
	}
	$object->fetch_thirdparty();
	$object->fetch_lines();
	restrictedArea($user,'contrat',$id,'','');
	// Hooks
	if (!is_object($hookmanager)) {
		include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
		$hookmanager	= new HookManager($db);
	}
	$hookmanager->initHooks(array('contractcard','globalcard'));
	// Extrafields
	$extrafields				= new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($object->table_element);
	$extrafields_line			= new ExtraFields($db);
	$extrafields_line->fetch_name_optionals_label($object->table_element_line);
	// Récupérer la liste des extrafields disponibles pour les lignes de contrat
	$selected_extrafields_json	= getDolGlobalString('INFRASWORKFLOW_CONTRACT_LINE_EXTRAFIELDS_SELECTION', '');
	$selected_extrafields		= array();
	if (!empty($selected_extrafields_json)) {
		$selected_extrafields	= explode(',', $selected_extrafields_json);
		// Nettoyer les valeurs (trim)
		$selected_extrafields	= array_map('trim', $selected_extrafields);
		// Supprimer les valeurs vides
		$selected_extrafields	= array_filter($selected_extrafields);
	}
	// Mode catégorie (alternative aux extrafields)
	$parentCategoryId	= getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCT_PARENT_CATEGORY', 0);
	$useCategoryMode	= ($parentCategoryId > 0);
	// Vérification de cohérence : les deux options ne doivent pas être remplies ou vides en même temps
	$hasExtrafields		= (!empty($selected_extrafields) && !in_array('none', $selected_extrafields));
	if ($useCategoryMode && $hasExtrafields) {
		setEventMessages($langs->trans('InfraSWorkflowErrorBothOptions45And46'), null, 'errors');
	} elseif (!$useCategoryMode && !$hasExtrafields) {
		setEventMessages($langs->trans('InfraSWorkflowErrorNeitherOption45Nor46'), null, 'warnings');
	}
	// Objet
	$form				= new Form($db);
	$formfile			= new FormFile($db);
	$formconfirm		= '';
	if (isModEnabled('project')) {
		$formproject	= new FormProjets($db);
	}
	/*
	* Actions
	*/
	// --- MISE A JOUR D'UNE LIGNE AVEC EXTRAFIELDS ---------------------------------
	if ($action == 'updateline' && $user->hasRight('contrat','creer')) {
		if (GETPOST('cancel', 'alpha')) {
			header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
			exit;
		}
		$error			= 0;
		$db->begin();
		$lineid			= GETPOSTINT('lineid');
		$desc			= dol_htmlcleanlastbr(GETPOST('product_desc', 'restricthtml'));
		$qty			= price2num(GETPOST('qty','alpha'));	// champs renommés dans le form
		$price_ht		= price2num(GETPOST('price_ht','alpha'));
		$remise_percent	= price2num(GETPOST('remise_percent','alpha'));
		$raw_tva		= GETPOST('tva_tx','alpha');
		$tva_tx			= price2num($raw_tva, 'CU');
		$vat_src_code	= '';
		if (preg_match('/\(([^)]+)\)$/', $raw_tva, $reg)) {
			$vat_src_code	= trim($reg[1]);
		}
		$date_start			= 0;
		$date_end			= 0;
		// Récupération des extrafields de ligne
		$extralabelsline	= $extrafields_line->fetch_name_optionals_label($object->table_element_line);
		$array_options		= $extrafields_line->getOptionalsFromPost($object->table_element_line);
		if ($qty === '' || $qty <= 0) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
			$error++;
		}
		if ($price_ht === '') {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('PriceUHT')), null, 'errors');
			$error++;
		}
		$line = new ContratLigne($db);
		if (!$error && $line->fetch($lineid) <= 0) {
			setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
			$error++;
		}
		if (!$error) {
			// Recalcule taxes locales
			$localtax1_tx	= get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_tx);
			$localtax2_tx	= get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_tx);
			// $desc provient du WYSIWYG (product_desc) récupéré en GETPOST ligne 92
			// Appel updateline (signature simplifiée – adapter si ta version diffère)
			$res			= $object->updateline ($lineid,
													$desc,
													$price_ht,
													$qty,
													$remise_percent,
													$date_start,
													$date_end,
													$tva_tx,
													$localtax1_tx,
													$localtax2_tx,
													0,
													0,
													$price_ht,
													0,
													$line->fk_fournprice,
													$line->pa_ht,
													$array_options, // Extrafields
													$line->fk_unit,
													$line->rang
													);
			if ($res > 0) {
				// Forcer product_type = 0 si ligne libre
				if (empty($line->fk_product)) {
					$sql	= 'UPDATE '.$db->prefix().'contratdet SET product_type = 0 WHERE rowid = '.((int) $lineid);
					$db->query($sql);
				}
				$db->commit();
				setEventMessages($langs->trans('RecordModifiedSuccessfully'), null);
				header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
				exit;
			} else {
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$db->rollback();
		}
	}
	// --- DÉPLACER UNE LIGNE VERS LE HAUT (Méthode native Dolibarr) --------------
	if ($action == 'up' && $user->hasRight('contrat', 'creer')) {
		$lineId	= GETPOSTINT('rowid');
		if ($lineId > 0) {
			$result	= $object->line_up($lineId);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		$object->fetch($object->id);
		$object->fetch_lines();
		header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
		exit;
	}
	// --- DÉPLACER UNE LIGNE VERS LE BAS (Méthode native Dolibarr) ---------------
	if ($action == 'down' && $user->hasRight('contrat', 'creer')) {
		$lineId	= GETPOSTINT('rowid');
		if ($lineId > 0) {
			$result	= $object->line_down($lineId);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		$object->fetch($object->id);
		$object->fetch_lines();
		header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
		exit;
	}
	// --- CLONER UNE LIGNE AVEC EXTRAFIELDS ---------------------------------------
	if ($action == 'confirm_cloneline' && $confirm == 'yes' && $user->hasRight('contrat', 'creer')) {
		$error	= 0;
		$db->begin();
		$lineid	= GETPOSTINT('lineid');
		$line	= new ContratLigne($db);
		if ($line->fetch($lineid) > 0) {
			// Récupérer les extrafields de la ligne source
			$line->fetch_optionals();
			// Récupérer le rang max
			$sql		= 'SELECT MAX(rang) as maxrang FROM '.$db->prefix().'contratdet WHERE fk_contrat = '.((int) $object->id);
			$resql		= $db->query($sql);
			$maxrang	= 0;
			if ($resql) {
				$obj	= $db->fetch_object($resql);
				if ($obj && $obj->maxrang !== null) {
					$maxrang	= (int) $obj->maxrang;
				}
			}
			// Ajouter la nouvelle ligne clonée avec extrafields
			$result	= $object->addline($line->desc,
										$line->subprice,
										$line->qty,
										$line->tva_tx,
										$line->localtax1_tx,
										$line->localtax2_tx,
										$line->fk_product,
										$line->remise_percent,
										'',
										'',
										'HT',
										0,
										0,
										null,
										0,
										$line->array_options, // Cloner les extrafields
										$line->fk_unit,
										$maxrang + 1
										);
			if ($result > 0) {
				// Forcer product_type = 0
				$sql	= 'UPDATE '.$db->prefix().'contratdet SET product_type = 0 WHERE rowid = '.((int) $result);
				$db->query($sql);
				$db->commit();
				$object->fetch_lines();
				setEventMessages($langs->trans('LineSuccessfullyAdded'), null);
				header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
				exit;
			} else {
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$db->rollback();
			setEventMessages($line->error, $line->errors, 'errors');
		}
	}
	// --- AJOUTER UNE LIGNE AVEC EXTRAFIELDS --------------------------------------
	if (($action == 'addline' || GETPOST('addline')) && $user->hasRight('contrat', 'creer')) {
		$error				= 0;
		$db->begin();
		$prod_entry_mode	= GETPOST('prod_entry_mode', 'alpha');
		$desc				= trim(GETPOST('dp_desc', 'restricthtml'));
		$qty				= price2num(GETPOST('qty', 'alpha'));
		$price_ht			= price2num(GETPOST('price_ht', 'alpha'));
		$remise_percent		= price2num(GETPOST('remise_percent', 'alpha'));
		$tva_tx				= price2num(GETPOST('tva_tx', 'alpha'));
		// Récupération des extrafields de ligne
		$extralabelsline	= $extrafields_line->fetch_name_optionals_label($object->table_element_line);
		$array_options		= $extrafields_line->getOptionalsFromPost($object->table_element_line);
		// Ligne libre
		if ($prod_entry_mode == 'free') {
			if ($desc == '') {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
				$error++;
			}
			if ($qty === '' || $qty <= 0) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
				$error++;
			}
			if ($price_ht === '') {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('PriceUHT')), null, 'errors');
				$error++;
			}
			if (!$error) {
				// Calcul TVA locale
				$localtax1_tx	= get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_tx);
				$localtax2_tx	= get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_tx);
				// Calcul du rang max pour placer la ligne à la fin
				$sql			= 'SELECT MAX(rang) as maxrang FROM '.$db->prefix().'contratdet WHERE fk_contrat = '.((int)$object->id);
				$resql			= $db->query($sql);
				$maxrang		= 0;
				if ($resql) {
					$obj	= $db->fetch_object($resql);
					if ($obj && $obj->maxrang !== null) {
						$maxrang	= (int) $obj->maxrang;
					}
				}
				// Ajout de la ligne libre avec extrafields
				$result	= $object->addline($desc,
											$price_ht,
											$qty,
											$tva_tx,
											$localtax1_tx,
											$localtax2_tx,
											0,               // idprod = 0 (ligne libre)
											$remise_percent,
											'',
											'',
											'HT',
											0,
											0,
											null,
											0,
											$array_options, // Extrafields
											null,
											$maxrang + 1
											);
				if ($result > 0) {
					// Forcer le type produit (product_type = 0)
					$sql = 'UPDATE '.$db->prefix().'contratdet SET product_type = 0 WHERE rowid = '.((int) $result);
					$db->query($sql);

					$db->commit();
					$object->fetch_lines();
					setEventMessages($langs->trans('LineSuccessfullyAdded'), null);
					header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
					exit;
				} else {
					$db->rollback();
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				$db->rollback();
			}
		} else {
			// Produit du catalogue
			$idprod	= GETPOSTINT('idprod');
			if ($idprod <= 0) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ProductOrService')), null, 'errors');
				$error++;
			}
			if ($qty <= 0) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
				$error++;
			}
			if (!$error) {
				$product	= new Product($db);
					if ($product->fetch($idprod) > 0) {
					if ($product->type != 0) {
						setEventMessages($langs->trans('ErrorProductExpected'), null, 'errors');
						$error++;
					} else {
						if (empty($price_ht)) {
							$price_ht	= $product->price;
						}
						$desc			= $product->description;
						$tva_tx			= get_default_tva($mysoc, $object->thirdparty, $product->id);
						$localtax1_tx	= get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_tx);
						$localtax2_tx	= get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_tx);
						// Calcul du rang max
						$sql			= 'SELECT MAX(rang) as maxrang FROM '.$db->prefix().'contratdet WHERE fk_contrat = '.((int) $object->id);
						$resql			= $db->query($sql);
						$maxrang		= 0;
						if ($resql) {
							$obj	= $db->fetch_object($resql);
							if ($obj && $obj->maxrang !== null) {
								$maxrang	= (int) $obj->maxrang;
							}
						}
						// Ajout du produit avec extrafields
						$result	= $object->addline($desc,	// Description
													$price_ht,	// Prix unitaire HT
													$qty,	// Quantité
													$tva_tx,	// Taux de TVA
													$localtax1_tx,	// Taux de taxe locale 1
													$localtax2_tx,	// Taux de taxe locale 2
													$idprod,	// ID du produit
													$remise_percent,	// Pourcentage de remise
													'',	// Date de début (vide pour les produits)
													'',	// Date de fin (vide pour les produits)
													'HT',	// Info bits
													0,	// TVA NPR
													0,	// Ventilation comptable
													null,	// Code extrafield
													0,	// devise pu_ht
													$array_options,	// Extrafields
													null,	// Unité
													$maxrang + 1	// ✅ CORRECTION : Rang incrémenté
													);
						if ($result > 0) {
							// Forcer product_type = 0
							$sql = 'UPDATE '.$db->prefix().'contratdet SET product_type = 0 WHERE rowid = '.((int) $result);
							$db->query($sql);
							$db->commit();
							$object->fetch_lines();
							setEventMessages($langs->trans('LineSuccessfullyAdded'), null);
							header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
							exit;
						} else {
							$db->rollback();
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				} else {
					$db->rollback();
					setEventMessages($product->error, $product->errors, 'errors');
				}
			} else {
				$db->rollback();
			}
		}
	}
	// --- SUPPRIMER UNE LIGNE ------------------------------------------------------
	if ($action == 'deleteline' && $user->hasRight('contrat', 'creer')) {
		$formconfirm	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&lineid='.$lineid,
											$langs->trans('DeleteContractLine'),
											$langs->trans('ConfirmDeleteContractLine'),
											'confirm_deleteline',
											'',
											0,
											1
											);
	}
	if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->hasRight('contrat', 'creer')) {
		$result	= $object->deleteline(GETPOSTINT('lineid'), $user);
		if ($result >= 0) {
			header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.(int) $object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	// --- CONFIRMATION CLONAGE -----------------------------------------------------
	if ($action == 'cloneline' && $user->hasRight('contrat', 'creer')) {
		$formconfirm	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&lineid='.$lineid,
											$langs->trans('CloneLine'),
											$langs->trans('ConfirmCloneLine'),
											'confirm_cloneline',
											'',
											0,
											1
											);
	}
	/*
	 * View
	 */
	$help_url				= 'EN:Module_Contracts|FR:Module_Contrat';
	llxHeader('', $langs->trans('Contract').' - '.$langs->trans('Products'));
	print load_fiche_titre($langs->trans('Products'), '', 'product');
	// Sélection onglet : on force un code interne différent pour ce sous‐écran
	$selected				= 'tabProduct';
	// En-tête standard
	$head					= contract_prepare_head($object);
	print dol_get_fiche_head($head, $selected, $langs->trans('Contract'), -1, 'contract');
		print $formconfirm;
	// Contract card
	$linkback				= '<a href = "'.DOL_URL_ROOT.'/contrat/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans('BackToList').'</a>';
	$morehtmlref			= '';
	$morehtmlref			.= $object->ref;
	$morehtmlref			.= '<div class = "refidno">';
	// Ref customer
	$morehtmlref			.= $form->editfieldkey('RefCustomer', 'ref_customer', $object->ref_customer, $object, $user->hasRight('contrat', 'creer'), 'string', '', 0, 1);
	$thirpartyRefInputSize	= getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE');
	$morehtmlref			.= $form->editfieldval('RefCustomer', 'ref_customer', $object->ref_customer, $object, $user->hasRight('contrat', 'creer'), 'string' . ($thirpartyRefInputSize ? ':' . $thirpartyRefInputSize : ''), '', null, null, '', 1, 'getFormatedCustomerRef');
	// Ref supplier
	$morehtmlref			.= '<br>';
	$morehtmlref			.= $form->editfieldkey('RefSupplier', 'ref_supplier', $object->ref_supplier, $object, $user->hasRight('contrat', 'creer'), 'string', '', 0, 1);
	$morehtmlref			.= $form->editfieldval('RefSupplier', 'ref_supplier', $object->ref_supplier, $object, $user->hasRight('contrat', 'creer'), 'string', '', null, null, '', 1, 'getFormatedSupplierRef');
	// Thirdparty
	$morehtmlref			.= '<br>'.$object->thirdparty->getNomUrl(1);
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) {
		$morehtmlref	.= ' (<a href = "'.DOL_URL_ROOT.'/contrat/list.php?socid='.$object->thirdparty->id.'&search_name='.urlencode($object->thirdparty->name).'">'.$langs->trans('OtherContracts').'</a>)';
	}
	// Project
	if (isModEnabled('project')) {
		$langs->load('projects');
		$morehtmlref		.= '<br>';
		$permissiontoadd	= $user->hasRight('contrat', 'creer');
		if ($permissiontoadd) {
			$morehtmlref	.= img_picto($langs->trans('Project'), 'project', 'class = "pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref	.= '<a class = "editfielda" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref	.= $form->form_project(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj			= new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref	.= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref	.= '<span class = "opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref	.= '</div>';
	dol_banner_tab($object,'ref',$linkback,1,'ref','none',$morehtmlref);
	// Zone infos
	print '	<div class = "fichecenter">';
	print '		<div class = "underbanner clearboth"></div>';
	print '	</div>';
	print '	<br/>';
	/*
	 * Lines of contracts
	 */
	$productstatic	= new Product($db);
	print '	<div id = "contrat-lines-container" data-contractid = "'.(int)$object->id.'" data-element = "'.htmlspecialchars($object->element, ENT_QUOTES, 'UTF-8').'">';
	// ✅ TRI UNIQUE : Trier les lignes par rang UNE SEULE FOIS
	usort($object->lines, function ($a, $b) {
		$rang_a	= (!empty($a->rang) && $a->rang > 0) ? $a->rang : 999999;
		$rang_b	= (!empty($b->rang) && $b->rang > 0) ? $b->rang : 999999;
		if ($rang_a == $rang_b) {
			return $a->rowid - $b->rowid;
		}
		return $rang_a - $rang_b;
	});
	// Compter le nombre de produits (product_type = 0)
	$productLines	= array_filter($object->lines, function ($line) {
		return (int) $line->product_type === 0;
	});
	$nbProductLines	= count($productLines);
	if ($nbProductLines > 0) {
		print '		<div class = "div-table-responsive-no-min">';
		print '			<table class = "notopnoleftnoright allwidth tableforservicepart1 centpercent">';
		// En-tête du tableau
		print '				<tr class = "liste_titre">';
		$colspan = 5;
		print '					<td>'.$langs->trans('Product').'</td>';
		print '					<td class = "right">'.$langs->trans('VAT').'</td>';
		print '					<td class = "right">'.$langs->trans('PriceUHT').'</td>';
		print '					<td class = "right">'.$langs->trans('Qty').'</td>';
		print '					<td class = "right">'.$langs->trans('ReductionShort').'</td>';
		print '					<td class = "right">'.$langs->trans('TotalHT').'</td>';
		print '					<td class = "linecoledit" colspan = "'.$colspan.'">&nbsp;</td>';
		print '				</tr>';
		$linePosition	= 0;
		foreach ($object->lines as $line) {
			if ((int)$line->product_type !== 0) {
				continue;
			}
			$linePosition++;
			// Charger les extrafields de la ligne
			$line->fetch_optionals();
			// MODE EDIT INLINE AVEC EXTRAFIELDS
			if ($action == 'editline' && $user->hasRight('contrat', 'creer') && $line->id == $lineid) {
				if ($line->fk_product > 0) {
					$productstatic->fetch($line->fk_product);
				}
				print '		<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'" method="POST">';
				print '			<input type = "hidden" name = "token" value = "'.newToken().'">';
				print '			<input type = "hidden" name = "action" value = "updateline">';
				print '			<input type = "hidden" name = "id" value = "'.$object->id.'">';
				print '			<input type = "hidden" name = "lineid" value = "'.$line->id.'">';
				// Ligne principale (produit, prix, qté, etc.)
				print '			<tr class = "oddeven tredited" id = "row-'.$line->id.'">';
				// Produit (non modifiable)
				print '				<td>';
				if ($line->fk_product > 0) {
					print $productstatic->getNomUrl(1);
					print '				<input type = "hidden" name = "idprod" value = "'.$line->fk_product.'">';
				} else {
					print $langs->trans("FreeProduct");
				}
				print '				</td>';
				// TVA
				print '				<td class = "right">';
				$selectedvat	= $line->tva_tx.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : '');
				print $form->load_tva('tva_tx', $selectedvat, $mysoc, $object->thirdparty, $line->fk_product, $line->id, $line->vat_src_code, $line->product_type);
				print '				</td>';
				// Prix HT
				print '				<td class = "right"><input type = "text" name = "price_ht" class = "flat right width75" value = "' . price2num($line->subprice) . '"></td>';
				// Quantité
				print '				<td class = "right"><input type = "text" name = "qty" class = "flat right width50" value = "' . $line->qty . '"></td>';
				// Remise
				print '				<td class = "right"><input type = "text" name = "remise_percent" class = "flat right width50" value = "' . $line->remise_percent . '">%</td>';
				// Total
				print '				<td class = "right">'.price($line->total_ht).'</td>';
					// Actions (Save / Cancel)
				print '				<td class = "right" colspan = "5">';
				print '					<input type = "submit" class = "button button-save" name = "save" value = "'.$langs->trans("Save").'"> ';
				print '					<input type = "submit" class = "button button-cancel" name = "cancel" value = "'.$langs->trans("Cancel").'">';
				print '				</td>';
				print '			</tr>';
				// ✅ NOUVELLE LIGNE : Description (WYSIWYG)
				$colspan = 6;
				if (!empty($selected_extrafields) && !empty($extrafields_line->attributes[$object->table_element_line]['label'])) {
					// Compter les extrafields affichables
					$nbExtrafields = 0;
					foreach ($extrafields_line->attributes[$object->table_element_line]['label'] as $key => $label) {
						if (shouldDisplayContractLineExtrafield($key, $selected_extrafields, $extrafields_line, $object->table_element_line)) {
							$nbExtrafields++;
						}
					}
					$colspan += $nbExtrafields;
				}
				$colspan += 5; // Actions columns
				print '			<tr class = "oddeven">';
				print '				<td colspan = "' . $colspan . '">';
				// Éditeur WYSIWYG pour la description
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$nbrows	= ROWS_2;
				if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
					$nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
				}
				$enable			= (isset($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
				$toolbarname	= 'dolibarr_details';
				if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
					$toolbarname	= 'dolibarr_notes';
				}
				$doleditor	= new DolEditor('product_desc', $line->desc, '', getDolGlobalInt('MAIN_DOLEDITOR_HEIGHT', 164), $toolbarname, '', false, true, $enable, $nbrows, '90%');
				$doleditor->Create();
				print '				</td>';
				print '			</tr>';
				// ✅ NOUVELLE LIGNE : Catégorie ou Extrafields de ligne (mode édition)
				if ($useCategoryMode && $line->fk_product > 0) {
					$categoryLabel = '';
					$tmpCat	= new Categorie($db);
					$cats	= $tmpCat->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'object');
					if (is_array($cats)) {
						foreach ($cats as $c) {
							if ($c->fk_parent == $parentCategoryId) {
								$categoryLabel = $c->label;
								break;
							}
						}
					}
					if (!empty($categoryLabel)) {
						print '			<tr class = "oddeven">';
						print '				<td class = "titlefield" style = "font-weight: bold;">'.$langs->trans('Category').'</td>';
						print '				<td colspan = "'.($colspan - 1).'">'.$categoryLabel.'</td>';
						print '			</tr>';
					}
				} elseif (!empty($extrafields_line->attributes[$object->table_element_line]['label']) && !in_array('none', $selected_extrafields)) {
					$objectline							= new ContratLigne($db);
					$objectline->id						= $line->id;
					$objectline->fetch_optionals();
					// Créer une copie filtrée des extrafields selon la sélection
					$filtered_extrafields				= new ExtraFields($db);
					$filtered_extrafields->attributes	= array();
					if (!empty($extrafields_line->attributes[$object->table_element_line])) {
						foreach ($extrafields_line->attributes[$object->table_element_line] as $attr_type => $attr_values) {
							if (!is_array($attr_values)) {
								$filtered_extrafields->attributes[$object->table_element_line][$attr_type] = $attr_values;
								continue;
							}
							$filtered_extrafields->attributes[$object->table_element_line][$attr_type] = array();
							foreach ($attr_values as $key => $value) {
								if (shouldDisplayContractLineExtrafield($key, $selected_extrafields, $extrafields_line, $object->table_element_line)) {
									$filtered_extrafields->attributes[$object->table_element_line][$attr_type][$key] = $value;
								}
							}
						}
					}
					if (!empty($filtered_extrafields->attributes[$object->table_element_line]['label'])) {
						print $objectline->showOptionals($filtered_extrafields, 'edit', array('style' => 'class = "oddeven"', 'colspan' => $colspan, 'tdclass' => 'notitlefieldcreate'), '', '', 1);
					}
				}
				print '		</form>';
				continue;
			}
			// MODE VUE (normal) AVEC EXTRAFIELDS
			if ($line->fk_product > 0) {
				$productstatic->fetch($line->fk_product);
			}
			// Ligne principale
			print '			<tr class = "oddeven" id = "row-'.$line->id.'">';
			// Produit
			print '				<td>';
			if ($line->fk_product > 0) {
				$text = $productstatic->getNomUrl(1, '', 32);
				if ($productstatic->label) {
					$text .= ' - ' . $productstatic->label;
				}
				$description = $line->desc;
				// Add description in tooltip selon configuration
				if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
					$text .= (!empty($line->desc) && $line->desc != $productstatic->label) ? '<br>' . dol_htmlentitiesbr($line->desc) : '';
					$description = ''; // Already added into main visible desc
				}
				print $form->textwithtooltip($text, $description, 3, '', '', $linePosition, 3, '');
			} else {
				print dol_trunc(dol_htmlentitiesbr($line->desc), 80);
			}
			print '				</td>';
			// TVA
			print '				<td class = "right">'.vatrate($line->tva_tx.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : ''), true).'</td>';
			// PU HT
			print '				<td class = "right">'.price($line->subprice).'</td>';
			// Qte
			print '				<td class = "right">'.$line->qty.'</td>';
			// Remise
			print '				<td class = "right">'.($line->remise_percent > 0 ? dol_print_reduction((float) $line->remise_percent, $langs) : '').'</td>';
			// Total HT
			print '				<td class = "right">'.price($line->total_ht).'</td>';
				// Actions
			print '				<td class = "center nowraponall">';
			if ($user->hasRight('contrat', 'creer')) {
				print '				<a href = "' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?id=' . $object->id . '&action=editline&lineid=' . $line->id . '">' . img_edit() . '</a>';
			}
			print '				</td>';
			// Cloner
			print '				<td class = "center nowraponall">';
			if ($user->hasRight('contrat', 'creer')) {
				print '				<a href = "' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?id=' . $object->id . '&action=cloneline&lineid=' . $line->id . '">' . img_picto($langs->trans("Duplicate"), 'clone') . '</a>';
			}
			print '				</td>';
			// Suppression
			print '				<td class = "center nowraponall">';
			if ($user->hasRight('contrat', 'creer')) {
				print '				<a href = "' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?id=' . $object->id . '&action=deleteline&lineid=' . $line->id . '&token=' . newToken() . '">' . img_delete() . '</a>';
			}
			print '				</td>';
			// Flèches haut/bas (méthodes natives Dolibarr)
			print '				<td class = "center nowraponall linecolmove">';
			if ($user->hasRight('contrat', 'creer')) {
				// Flèche haut
				if ($linePosition > 1) {
					print '			<a class = "reposition" href = "' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?id=' . $object->id . '&action=up&token=' . newToken() . '&rowid=' . $line->id . '">';
					print img_up();
					print '			</a>';
				} else {
					print '			<span style="width:14px; display:inline-block;"></span>';
				}
				// Flèche bas
				if ($linePosition < $nbProductLines) {
					print '			<a class = "reposition marginleftonly" href = "' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?id=' . $object->id . '&action=down&token=' . newToken() . '&rowid=' . $line->id . '">';
					print img_down();
					print '			</a>';
				} else {
					print '			<span style="width:14px; display:inline-block;"></span>';
				}
			}
			print '				</td>';
			print '				</tr>';
			// ✅ NOUVELLE LIGNE : Catégorie ou Extrafields de ligne (mode vue)
			$colspan	= 6; // Colonnes de base (Produit, TVA, Prix, Qté, Remise, Total)
			$colspan	+= 5; // Actions (Edit, Clone, Delete, Up, Down)
			if ($useCategoryMode && $line->fk_product > 0) {
				$categoryLabel = '';
				$tmpCat	= new Categorie($db);
				$cats	= $tmpCat->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'object');
				if (is_array($cats)) {
					foreach ($cats as $c) {
						if ($c->fk_parent == $parentCategoryId) {
							$categoryLabel = $c->label;
							break;
						}
					}
				}
				if (!empty($categoryLabel)) {
					print '			<tr class = "oddeven">';
					print '				<td class = "titlefield" style = "font-weight: bold;">'.$langs->trans('Category').'</td>';
					print '				<td colspan = "'.($colspan - 1).'">'.$categoryLabel.'</td>';
					print '			</tr>';
				}
			} elseif (!empty($extrafields_line->attributes[$object->table_element_line]['label']) && !in_array('none', $selected_extrafields)) {
				$objectline							= new ContratLigne($db);
				$objectline->id						= $line->id;
				$objectline->fetch_optionals();
				// Créer une copie filtrée des extrafields selon la sélection
				$filtered_extrafields				= new ExtraFields($db);
				$filtered_extrafields->attributes	= array();
				if (!empty($extrafields_line->attributes[$object->table_element_line])) {
					foreach ($extrafields_line->attributes[$object->table_element_line] as $attr_type => $attr_values) {
						if (!is_array($attr_values)) {
							$filtered_extrafields->attributes[$object->table_element_line][$attr_type] = $attr_values;
							continue;
						}
						$filtered_extrafields->attributes[$object->table_element_line][$attr_type] = array();
						foreach ($attr_values as $key => $value) {
							if (shouldDisplayContractLineExtrafield($key, $selected_extrafields, $extrafields_line, $object->table_element_line)) {
								$filtered_extrafields->attributes[$object->table_element_line][$attr_type][$key] = $value;
							}
						}
					}
				}
				if (!empty($filtered_extrafields->attributes[$object->table_element_line]['label'])) {
					print $objectline->showOptionals($filtered_extrafields, 'view', array('class'=>'oddeven', 'colspan'=>$colspan), '', '', 1);
				}
			}
		}
		print '				</table>';
		print '			</div>'; // fin table responsive
	} // fin if nbProductLines > 0
	print '		</div>'; // fin contrat-lines-container
	// Formulaire d'ajout de ligne avec extrafields
	print '		<form method="POST" action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'">';
	print '			<input type = "hidden" name = "token" value = "'.$token.'">';
	print '			<input type = "hidden" name = "action" value = "addline">';
	print '			<div class = "div-table-responsive-no-min">';
	print '				<table class = "centpercent">';
	$buyer					= $object->thirdparty;
	$seller					= $mysoc;
	$dateSelector			= 1;
	$forcetoshowtitlelines	= 1;
	// Exposer les variables de contexte pour les sous-hooks dans le template infrasproject
	$GLOBALS['infrasworkflow_contrat_tab_active']   = true;
	$GLOBALS['extrafields_line']                    = $extrafields_line;
	$GLOBALS['infrasworkflow_selected_extrafields'] = $selected_extrafields;
	$GLOBALS['infrasworkflow_useCategoryMode']      = $useCategoryMode;
	$GLOBALS['infrasworkflow_parentCategoryId']     = isset($parentCategoryId) ? (int) $parentCategoryId : 0;
	$reshook = $hookmanager->executeHooks('formAddObjectLine', array(), $object, $action);
	if ($reshook <= 0) {
		// Fallback : aucun hook n'a rendu le formulaire, utiliser le dispatcher infrasworkflow
		include dol_buildpath('/infrasworkflow/core/tpl/objectline_create.tpl.php');
	}
	unset($GLOBALS['infrasworkflow_contrat_tab_active'], $GLOBALS['extrafields_line'],
		$GLOBALS['infrasworkflow_selected_extrafields'], $GLOBALS['infrasworkflow_useCategoryMode'],
		$GLOBALS['infrasworkflow_parentCategoryId']);
	print '				</table>';
	print '			</div>';
	print '		</form>';

	// InfraS - Auto-remplissage de la sous-catégorie lors de la sélection d'un produit
	if ($useCategoryMode) {
		$ajaxUrl = dol_buildpath('/infrasworkflow/ajax/getproductcategory.php', 1);
		print '<script type="text/javascript">'."\n";
		print '$(document).ready(function() {'."\n";
		print '	$(document).on("change", "#idprod", function() {'."\n";
		print '		var productId = $(this).val();'."\n";
		print '		var subCatSelect = $("#infrasworkflow_subcategory");'."\n";
		print '		if (productId > 0 && subCatSelect.length > 0) {'."\n";
		print '			$.ajax({'."\n";
		print '				url: "'.dol_escape_js($ajaxUrl).'",'."\n";
		print '				type: "GET",'."\n";
		print '				dataType: "json",'."\n";
		print '				data: { productid: productId, parentcatid: '.((int) $parentCategoryId).' },'."\n";
		print '				success: function(data) {'."\n";
		print '					if (data.subcatid > 0) {'."\n";
		print '						subCatSelect.val(data.subcatid);'."\n";
		print '					} else {'."\n";
		print '						subCatSelect.val("");'."\n";
		print '					}'."\n";
		print '				}'."\n";
		print '			});'."\n";
		print '		} else {'."\n";
		print '			subCatSelect.val("");'."\n";
		print '		}'."\n";
		print '	});'."\n";
		print '});'."\n";
		print '</script>'."\n";
	}

	print dol_get_fiche_end();
	llxFooter();
	$db->close();