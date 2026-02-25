<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2016-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		../infrasdiscount/class/actions_infrasdiscount.class.php
	*	\ingroup	InfraS
	*	\brief		actions for infrasdiscount module (hook)
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
	dol_include_once('/infrasdiscount/core/lib/infrasdiscount.lib.php');

	// Description and activation class *************
	class ActionsInfraSDiscount
	{
		private $db;	// @var DoliDB Database handler
		public $results	= array();	// @var array Hook results.Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $error;	// @var string
		public $errors	= array();	// @var array Errors

		public function __construct($db)	// Constructor
		{
			$this->db	= $db;
		}

		/**
		* Add actions buttons
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set).Generally create or edit or null
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function addMoreActionsButtons($parameters, &$object, &$action)
		{
			global $langs, $user;

			$langs->load('infrasdiscount@infrasdiscount');
			$refs			= infrasdiscount_getDiscountProductRefs();
			$remProductRef	= $refs['product'];
			$remServiceRef	= $refs['service'];
			if ($object->statut >= 1) {
				return 0;
			}
			$elementValid	= array();
			if ($object->element == 'propal' && getDolGlobalString('INFRASDISCOUNT_ON_PROPALE', '')) {
				$elementValid[]	= $object->element;
			}
			if ($object->element == 'commande' && getDolGlobalString('INFRASDISCOUNT_ON_ORDER', '')) {
				$elementValid[]	= $object->element;
			}
			if ($object->element == 'facture' && getDolGlobalString('INFRASDISCOUNT_ON_INVOICE', '')) {
				$elementValid[]	= $object->element;
			}
			if (in_array($object->element, $elementValid) && $user->hasRight('infrasdiscount', 'use')) {
				print '	<div class = "inline-block divButAction">
							<a class = "butAction" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&action=remise">'.$langs->trans('InfraSDiscountLabelSubmit').'</a>
						</div>';
				// Vérifier s'il existe au moins une ligne de remise
				$hasRemiseLine	= false;
				if (!empty($object->lines) && is_array($object->lines)) {
					foreach ($object->lines as $line) {
						if (in_array($line->array_options['options_specialtype'], [1, 2, 3, 4])) {
							$hasRemiseLine	= true;
							break;
						}
					}
				}
				if ($hasRemiseLine) {
					print '	<div class = "inline-block divButAction">
								<a class = "butAction" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&action=modify_remise">'.$langs->trans('InfraSDiscountLabelModify').'</a>
							</div>';
				}
			}
			return 0;
		}

		/**
		* Confirmation
		*
		* @param	array			$parameters		Hook metadatas (context, etc...)
		* @param	object			$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			$action			Current action (if set).Generally create or edit or null
		* @return	int | string					< 0 on error, 0 on success, 1 to replace standard code + $this->resprints HTML code to show
		**/
		public function formConfirm($parameters, &$object, &$action)
		{
			global $langs, $conf, $user, $mysoc;

			$langs->load('infrasdiscount@infrasdiscount');

			if (in_array($object->element, array('propal', 'commande', 'facture')) && $action == 'remise') {
				$form				= new Form($this->db);
				$franchise			= ((is_numeric($mysoc->tva_assuj) && !$mysoc->tva_assuj) || (!is_numeric($mysoc->tva_assuj) && $mysoc->tva_assuj == 'franchise')) ? 1 : 0;
				$labelRemise		= 'INFRASDISCOUNT_LABEL_'.($object->element == 'propal' ? 'PROPAL' : ($object->element == 'commande' ? 'ORDER' : ($object->element == 'facture' ? 'INVOICE' : '')));
				$labelRemise		= getDolGlobalString($labelRemise, '');
				// Vérifier si le multicurrency est activé dans l'objet
				$is_multicurrency	= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;
				$formquestion		= array(array('type'		=> 'text',
													'label'		=> $langs->trans('InfraSDiscountDiscountValue'),
													'morecss'	=> 'right',
													'name'		=> 'myRemiseValue',
													'size'		=> 8,
													'value'		=> '',
													'moreattr'	=> 'placeholder = "'.getDolGlobalfloat('INFRASDISCOUNT_DEFAULT_REM_VALUE', 10.00).'" pattern = "[0-9.]{1,8}"'
													),
											array('type'		=> 'text',
													'label'		=> $langs->trans('InfraSDiscountDiscountValuecurrency'),
													'morecss'	=> 'right',
													'name'		=> 'myRemiseValuecurrency',
													'size'		=> 8,
													'value'		=> '',
													'moreattr'	=> 'placeholder = "'.getDolGlobalfloat('INFRASDISCOUNT_DEFAULT_REM_VALUE', 10.00).'" pattern = "[0-9.]{1,8}" style = "display:inline;"'
													),
											array('type'		=> 'separator'),
											array('type'		=> 'radio',
													'label'		=> $langs->trans('InfraSDiscountTypeDiscount'),
													'morecss'	=> 'valignmiddle',
													'name'		=> 'myRemise_is',
													'values'	=> array('percent'	=> $langs->trans('InfraSDiscountTypeDiscountPercent'),
																		'amount'	=> $langs->trans('InfraSDiscountTypeDiscountAmount'),
																		'total_ttc'	=> $langs->trans('InfraSDiscountTypeDiscountTotalTTC')
																		),
													'default'	=> 'percent'
													),
											array('type'		=> 'separator'),
											array('type'		=> 'select',
													'label'		=> $langs->trans('InfrasDiscountSelectTypeToApply'),
													'morecss'	=> 'valignmiddle',
													'name'		=> 'myRemise_type',
													'values'	=> array('1'	=> $langs->trans('InfraSDiscountTypeProductOnly'),
																		'2'		=> $langs->trans('InfraSDiscountTypeServiceOnly'),
																		'3'		=> $langs->trans('InfraSDiscountTypeProductAndService')
																		),
													'default'	=> '3'
													),
											array('type'		=> 'text',
													'label'		=> $langs->trans('InfraSDiscountlabelInput'),
													'morecss'	=> 'minwidth300',
													'name'		=> 'libelle',
													'value'		=> '',
													'moreattr'	=> 'placeholder = "'.$langs->trans($labelRemise).'"'
													)
											);
				if (!$franchise) {
					$formquestion[]	= array('type'		=> 'separator');
					$formquestion[]	= array('type'	=> 'other',
											'label'	=> $langs->trans('SellTaxRate'),
											'name'	=> 'remise_tva_tx',
											'value'	=> $form->load_tva('remise_tva_tx', (GETPOSTISSET('tva_tx') ? GETPOST('tva_tx', 'alpha', 2) : -1), $mysoc, $object->thirdparty, 0, 0, '', false, 1)
											);
				}
				else {
					$formquestion[]	= array('type'	=> 'hidden',
											'name'	=> 'remise_tva_tx',
											'value'	=> 0
											);
				}
				$formquestion[]		= array('type'		=> 'separator');
				$formquestion[]		= array('type'		=> 'onecolumn',
											 'value'	=> '<script>
																document.addEventListener("DOMContentLoaded", function () {
																	const radios			= document.querySelectorAll("input[name=myRemise_is]");
																	const selectField		= document.getElementById("myRemiseValuecurrency");
																	const use_multicurrency	= '.(int)$is_multicurrency.';
																	// Hide by default
																	if (selectField) {
																		var selectLabel	= selectField.closest(".tagtr");
																		selectField.style.display					= "none";
																		if (selectLabel) selectLabel.style.display	= "none";
																	}
																	radios.forEach(function (radio) {
																		radio.addEventListener("change", function () {
																			togglemyRemiseType(this);
																			togglemyRemiseCurrency(this,use_multicurrency);
																		});
																	});
																	var checkedRadio	= document.querySelector("input[name=myRemise_is]:checked");
																	if (checkedRadio) {
																		togglemyRemiseType(checkedRadio);
																		togglemyRemiseCurrency(checkedRadio,use_multicurrency);
																	}
																});
																function togglemyRemiseType(input) {
																	var selectField	= document.getElementById("myRemise_type");
																	var selectLabel	= selectField ? selectField.closest(".tagtr") : null;
																	if (input.value === "amount" || input.value === "percent") {
																		if (selectField) selectField.style.display	= "";
																		if (selectLabel) selectLabel.style.display	= "";
																	} else {
																		if (selectField) selectField.style.display	= "none";
																		if (selectLabel) selectLabel.style.display	= "none";
																	}
																}
																function togglemyRemiseCurrency(input,use_multicurrency) {
																	var selectField	= document.getElementById("myRemiseValuecurrency");
																	var selectLabel	= selectField ? selectField.closest(".tagtr") : null;
																	if (input.value === "amount" && use_multicurrency) {
																		if (selectField) selectField.style.display	= "";
																		if (selectLabel) selectLabel.style.display	= "";
																	} else {
																		if (selectField) selectField.style.display	= "none";
																		if (selectLabel) selectLabel.style.display	= "none";
																	}
																}
															</script>'
											);
				$this->resprints	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id), $langs->trans('InfraSDiscountLabelBox'), '', 'InfraSDiscountRemise', $formquestion, 'yes', 1, 0, 700);
			}
			// New code for 'modify_remise' action
			if (in_array($object->element, array('propal', 'commande', 'facture')) && $action == 'modify_remise') {
				return $this->formConfirmModifyRemise($parameters, $object, $action);
			}
			return 0;
		}

		/**
		* Formulaire pour Confirmer la modification des remises
		*
		* @param	array			$parameters		Hook metadatas (context, etc...)
		* @param	object			$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			$action			Current action (if set).Generally create or edit or null
		* @return	int | string					< 0 on error, 0 on success, 1 to replace standard code + $this->resprints HTML code to show
		**/
		public function formConfirmModifyRemise($parameters, &$object, &$action)
		{
			global $conf, $langs;

			$langs->load('infrasdiscount@infrasdiscount');
			// Get remise lines
			$remiseLines		= array();
			$refs				= infrasdiscount_getDiscountProductRefs();
			$remProductRef		= $refs['product'];
			$remServiceRef		= $refs['service'];
			$is_multicurrency	= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;			// 1. Parcours pour repérer les lignes prorata et les grouper par paire
			$prorataGroups		= array();
			$currentPair		= array();
			$pairTotal			= 0;
			//loop to find and combine prorata line
			foreach ($object->lines as $idx => $line) {
				if (in_array($line->array_options['options_specialtype'], [1, 2, 3, 4])) {
					if ($line->array_options['options_specialtype'] == 3) {
						// Ajouter la ligne au groupe courant
						$currentPair[]	= array('line' => $line,
												'index' => $idx,
												'id' => $line->id
												);
						$pairTotal		+= $line->total_ht;
						// Si on a une paire complète (produit + service)
						if (count($currentPair) == 2) {
							$prorataGroups[]	= array('lines' => $currentPair,
														'total' => $pairTotal
														);
							$currentPair		= array();
							$pairTotal			= 0;
						}
					}
				}
			}
			// 2. Construction de la liste des remises
			foreach ($object->lines as $idx => $line) {
				if (in_array($line->array_options['options_specialtype'], [1, 2, 3, 4])) {
					if ($line->array_options['options_specialtype'] == 3) {
						// Chercher le groupe qui contient cette ligne
						foreach ($prorataGroups as $groupIdx => $group) {
							$lineIds	= array_column($group['lines'], 'id');
							if (in_array($line->id, $lineIds)) {
								// Ne créer qu'une seule ligne combinée par groupe
								if ($line->id === $lineIds[0]) {
									$prorataLine				= new stdClass();
									$prorataLine->desc			= price($group['total'], 0, $langs, 1, -1, -1, 'auto').' - '.$langs->trans("InfraSDiscountProrataCombinedLabel");
									$prorataLine->total_ht		= $group['total'];
									$prorataLine->product_ref	= $remProductRef;
									$prorataLine->array_options	= ['options_specialtype' => 3];
									$prorataLine->prorata_ids	= $lineIds;
									$prorataLine->id			= 'prorata_'.implode('_', $lineIds);
									$remiseLines[]				= $prorataLine;
								}
								// Passer cette ligne car elle est déjà incluse dans une ligne combinée
								continue 2;
							}
						}
					} elseif ($line->array_options['options_specialtype'] == 4) {
						continue;
					} else {
						$remiseLines[]	= $line;
					}
				}
			}
			if (empty($remiseLines)) return 0;
			// Build Dolibarr formquestion array
			$formquestion	= array();
			$listLines		= array();
			foreach ($remiseLines as $line) {
				$is_multicurrency	= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;
				$placeholder		= '';
				$symbol				= '';
				$inputId			= $line->id;
				if ($line->array_options['options_specialtype'] == 1) {
					if (preg_match('/([\d\.,]+)\s*%/', $line->desc, $matches)) {
						$placeholder	= $matches[1];
					}
					$symbol	= ' %';
				} elseif ($line->array_options['options_specialtype'] == 2) {
						$placeholder	= price (abs($line->total_ht));
						$symbol			= ' ' .$conf->currency;
						$placeholders	= price (abs($line->multicurrency_total_ht));
						$symbols		= ' ' .$object->multicurrency_code;
				} elseif ($line->array_options['options_specialtype'] == 3) {
					// Ligne combinée prorata
					if ($is_multicurrency) {
						// On affiche l'addition des montants en devise étranger des deux lignes prorata.
						// On additionne le montant dans $pu_ht_devise pour chaque ligne prorata
						$pu_ht_devise	= 0;
						$placeholders	= '';
						$symbols		= '';
						foreach ($line->prorata_ids as $prorataId) {
							$prorataLine	= current(array_filter($object->lines, fn($l) => $l->id == $prorataId));
							if ($prorataLine) {
								// Correction : fallback si multicurrency_total_ht vide
								if ($prorataLine->multicurrency_total_ht != 0) {
									$pu_ht_devise	+= $prorataLine->multicurrency_total_ht;
								} else {
									$pu_ht_devise	+= $prorataLine->total_ht * ($object->multicurrency_tx ?: 1);
								}
							}
						}
					}
					// On affiche le montant en devise étrangère
					$pu_ht_devise	= price(-abs($pu_ht_devise));
					$placeholders	= $pu_ht_devise;
					$symbols		= ' '.$object->multicurrency_code;
					// on affiche le montant en devise par défaut
					$placeholder	= price (-abs($line->total_ht));
					$symbol			= ' '.$conf->currency;
					// On stocke la liste des ids des lignes à mettre à jour
					$inputId		= $line->id; // ex: "prorata_12_13"
				}
				// On ajoute un champ qui indique la position de la remise dans la liste des lignes de l'objet
				$formquestion[]	= array('type'		=> 'other',
										'name'		=> 'remiseModValue_'.$inputId,
										'value'		=> '<span style="display:block;padding:5px;">'.$langs->trans('InfraSDiscountLabelLinePosition').' : '.
															(
																// Si c'est une ligne prorata, on affiche la/les position(s) dans $object->lines (index+1)
																isset($line->prorata_ids) && is_array($line->prorata_ids)
																? implode('/', array_map(fn($id) => array_search($id, array_column($object->lines, 'id')) + 1 ?: '?', $line->prorata_ids))
																: (array_search($line->id, array_column($object->lines, 'id')) + 1 ?: '?')
															).'</span>',
										'moreattr'	=> 'style="padding:5px;"'
										);
				$formquestion[]	= array('type'		=> 'text',
										'label'		=> '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;border-bottom:1px solid #ccc;padding:5px;background-color:'.((count($formquestion) / 2) % 2 == 0 ? '#f1f1f1' : '#f9f9f9').';">'
														.'<span style="flex:1;"><i class="fa fa-tags" style="padding:5px;"></i>'.dol_escape_htmltag($line->desc).'</span>'
														.'<span style="color:#ff0505;min-width:100px;text-align:right;font-weight:bold;">'.price($line->total_ht).'</span>'
														.'</div>',
										'name'		=> 'remiseModValue_'.$inputId,
										'value'		=> '',
										'morecss'	=> 'right',
										'moreattr'	=> 'placeholder="'.$placeholder.''.$symbol.'" autocomplete="off" style="width:auto;text-align:right;font-size:1.1em;"'
										);

				$listLines[]	= $inputId;
				// Si c'est une ligne prorata, on stocke la correspondance pour traitement dans doActions
				if ($line->array_options['options_specialtype'] == 3 && !empty($line->prorata_ids)) {
					$is_multicurrency	= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;				// On ajoute un champ caché pour faire le lien entre l'id virtuel et les vrais ids
					$formquestion[]	= array ('type'		=>	'text',
											'name'		=>	'remiseValueCurrency_'.$inputId,
											'value'	=>	'',
											'morecss'	=>	'right',
											'moreattr'	=>	'placeholder = "'.$placeholders.$symbols.'" autocomplete = "off" id = "remiseValueCurrency_'.$inputId.'" style = "width:auto;text-align:right;font-size:1.1em;'.($is_multicurrency ? '' : ' display:none;').'"'
											);
					$formquestion[]	= array ('type'	=> 'hidden',
											'name'	=> 'prorata_ids_'.$inputId,
											'value'	=> implode(',', $line->prorata_ids)
											);

				}
				if ($line->array_options['options_specialtype'] == 2) {
					$is_multicurrency	= isModEnabled('multicurrency') && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 1 : 0;				// On ajoute un champ caché pour faire le lien entre l'id virtuel et les vrais ids
					$formquestion[]		= array ('type'		=> 'text',
												'name'		=> 'remiseValueCurrency_'.$inputId,
												'value'		=> '',
												'morecss'	=> 'right',
												'moreattr'	=> 'placeholder = "'.$placeholders.$symbols.'" autocomplete = "off" id = "remiseValueCurrency_'.$inputId.'" style = "width:auto;text-align:right;font-size:1.1em;'.($is_multicurrency ? '' : ' display:none;').'"'
												);
				}
			}
			$formquestion[]		= array ('type'	=> 'hidden',
										'name'	=> 'ListLines',
										'value'	=> implode(',', $listLines)
										);
			$form				= new Form($this->db);
			$this->resprints	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&action=modify_remise', $langs->trans('InfraSDiscountLabelModify'), $langs->trans('InfraSDiscountConfirmModifyRemise'), 'modify_remise', $formquestion, 'yes', 1, 0, 1000);
			return 0;
		}
		/**
		* When we ask for an action (../element/card.php)
		*
		* @param	array			$parameters		Hook metadatas (context, etc...)
		* @param	object			$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			$action			Current action (if set).Generally create or edit or null
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function doActions($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $langs, $user;

			$langs->load('infrasdiscount@infrasdiscount');

			$result	= 0;

			// Permission check
			if (!$user->hasRight('infrasdiscount', 'use')) {
				return 0;
			}

			/*
			 * Créations des Remises
			 */
			if (in_array($object->element, array('propal', 'commande', 'facture')) && $action == 'InfraSDiscountRemise' && GETPOST('confirm', 'alpha') == 'yes') {
				$result	= $this->handleCreateDiscount($object);
			}

			/*
			 * Modifications des Remises
			 */
			if (in_array($object->element, array('propal', 'commande', 'facture')) && $action == 'modify_remise' && GETPOST('confirm', 'alpha') == 'yes') {
				$result	= $this->handleModifyDiscount($object);

				// Redirection pour éviter la réouverture du popup
				if ($result >= 0) {
					header("Location: ".dol_escape_htmltag($_SERVER['PHP_SELF'])."?id=".((int) $object->id));
					exit;
				}
			}

			return $result;
		}

		/**
		 * Gère la création des remises
		 *
		 * @param	CommonObject	$object		L'objet
		 * @return	int							< 0 on error, >= 0 on success
		 */
		private function handleCreateDiscount(&$object)
		{
			global $langs;

			// Récupérer les paramètres du formulaire
			$myRemiseValue			= !empty(GETPOST('myRemiseValue', 'alpha'))
									? price2num(GETPOST('myRemiseValue', 'alpha'), 'CU', 2)
									: getDolGlobalfloat('INFRASDISCOUNT_DEFAULT_REM_VALUE', 10.00);
			$myRemiseValueCurrency	= price2num(GETPOST('myRemiseValuecurrency', 'alpha'), 'CU', 2);
			$myRemise_is			= GETPOST('myRemise_is', 'alpha');
			$myRemise_type			= GETPOST('myRemise_type', 'int');
			$libelle				= GETPOST('libelle', 'alpha');
			$tva_tx					= GETPOST('remise_tva_tx', 'alpha') ? GETPOST('remise_tva_tx', 'alpha') : 0;
			$tva_npr				= (preg_match('/\*/', $tva_tx) ? 1 : 0);
			$tva_tx					= str_replace('*', '', $tva_tx);

			// Calculer les taxes locales
			$localtax1_tx			= get_localtax($tva_tx, 1, $object->thirdparty);
			$localtax2_tx			= get_localtax($tva_tx, 2, $object->thirdparty);

			// Récupérer le libellé par défaut selon le type d'objet
			$labelRemise			= $this->getDefaultLabel($object->element);

			// Préparer les extrafields
			$extrafieldsline		= new ExtraFields($this->db);
			$extralabelsline		= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
			$array_options			= $extrafieldsline->getOptionalsFromPost($extralabelsline);

			// Si myRemiseValueCurrency est renseigné, on l'utilise comme base
			if (!empty($myRemiseValueCurrency)) {
				$myRemiseValue	= 0;
			}

			// Construire les paramètres pour la fonction helper
			$params	= array(
				'libelle'		=> $libelle,
				'labelRemise'	=> $labelRemise,
				'tva_tx'		=> $tva_tx,
				'localtax1_tx'	=> $localtax1_tx,
				'localtax2_tx'	=> $localtax2_tx,
				'array_options'	=> $array_options
			);

			// Appeler la fonction helper centralisée
			return infrasdiscount_createDiscountLines(
				$object,
				$myRemise_is,
				$myRemise_type,
				$myRemiseValue,
				$myRemiseValueCurrency,
				$params
			);
		}

		/**
		 * Gère la modification des remises
		 *
		 * @param	CommonObject	$object		L'objet
		 * @return	int							< 0 on error, >= 0 on success
		 */
		private function handleModifyDiscount(&$object)
		{
			global $langs;

			$listLines		= explode(',', GETPOST('ListLines', 'alpha'));
			$refs			= infrasdiscount_getDiscountProductRefs();
			$remProductRef	= $refs['product'];
			$remServiceRef	= $refs['service'];

			// Construire le mapping des lignes prorata virtuelles
			$prorataMap		= $this->buildProrataMap($listLines);

			// Traiter chaque ligne
			foreach ($listLines as $lineid) {
				$remiseModValue			= GETPOST("remiseModValue_{$lineid}", 'alpha');
				$remiseModValueCurrency	= GETPOST("remiseValueCurrency_{$lineid}", 'alpha');

				// Gérer les lignes prorata combinées
				if (isset($prorataMap[$lineid])) {
					$this->updateProrataLines($object, $lineid, $prorataMap, $remiseModValue, $remiseModValueCurrency);
					continue;
				}

				// Gérer les lignes normales
				$this->updateNormalLine($object, $lineid, $remiseModValue, $remiseModValueCurrency, $remProductRef, $remServiceRef);
			}

			// Recalculer les remises
			infrasdiscount_recalculatePercentDiscounts($object);
			infrasdiscount_recalculateProrataDiscounts($object);

			return 0;
		}

		/**
		 * Retourne le libellé par défaut selon le type d'objet
		 *
		 * @param	string	$element	Type d'objet
		 * @return	string				Libellé par défaut
		 */
		private function getDefaultLabel($element)
		{
			$configKey	= 'INFRASDISCOUNT_LABEL_';

			switch ($element) {
				case 'propal':
					$configKey	.= 'PROPAL';
					break;
				case 'commande':
					$configKey	.= 'ORDER';
					break;
				case 'facture':
					$configKey	.= 'INVOICE';
					break;
				default:
					$configKey	.= 'DEFAULT';
			}

			return getDolGlobalString($configKey, '');
		}

		/**
		 * Construit le mapping des lignes prorata
		 *
		 * @param	array	$listLines	Liste des IDs de lignes
		 * @return	array				Mapping prorata
		 */
		private function buildProrataMap($listLines)
		{
			$prorataMap	= array();

			foreach ($listLines as $lineid) {
				$prorataIds	= GETPOST("prorata_ids_{$lineid}", 'alpha');
				if (!empty($prorataIds)) {
					$prorataMap[$lineid]	= explode(',', $prorataIds);
				}
			}

			return $prorataMap;
		}

		/**
		 * Met à jour les lignes prorata combinées
		 *
		 * @param	CommonObject	$object				L'objet
		 * @param	int				$lineid				ID de la ligne virtuelle
		 * @param	array			$prorataMap			Mapping prorata
		 * @param	string			$newValueRaw		Nouvelle valeur
		 * @param	string			$newValueCurrency	Nouvelle valeur en devise
		 * @return	void
		 */
		private function updateProrataLines(&$object, $lineid, $prorataMap, $newValueRaw, $newValueCurrency)
		{
			if (empty($newValueRaw) && empty($newValueCurrency)) {
				return;
			}

			$newValue			= price2num($newValueRaw, 'CU', 2);
			$newValueCurrency	= price2num($newValueCurrency, 'CU', 2);

			if (!is_numeric($newValue) && !is_numeric($newValueCurrency)) {
				return;
			}

			// Calculer les totaux et anciennes remises
			$totals	= $this->calculateProrataBase($object, $prorataMap[$lineid]);

			$baseProduct		= $totals['baseProduct'];
			$baseService		= $totals['baseService'];
			$remiseProrataBase	= $baseProduct + $baseService;

			if ($remiseProrataBase == 0) {
				return;
			}

			// Préparer les prix
			$preparedPrices	= infrasdiscount_prepare_prices($newValue, $newValueCurrency, $object);
			$newValues		= round($preparedPrices['pu_ht'], 2, PHP_ROUND_HALF_UP);

			// Calculer le nouveau taux de remise
			$remiseRate		= $newValues / $remiseProrataBase;
			$remiseProduct	= round($baseProduct * $remiseRate, 2, PHP_ROUND_HALF_UP);
			$remiseService	= round($baseService * $remiseRate, 2, PHP_ROUND_HALF_UP);

			// Mettre à jour chaque ligne réelle
			foreach ($object->lines as $l) {
				if (!in_array($l->id, $prorataMap[$lineid]) ||
					!isset($l->array_options['options_specialtype']) ||
					$l->array_options['options_specialtype'] != 3) {
					continue;
				}

				$isProduct			= ($l->product_type == 0);
				$remise				= $isProduct ? $remiseProduct : $remiseService;
				$pu_ht_devise_calc	= 0;

				if (infrasdiscount_multicurrency_enabled($object)) {
					if (!empty($newValueCurrency)) {
						$remiseCurrency		= $isProduct
							? round($baseProduct * $remiseRate * $object->multicurrency_tx, 2, PHP_ROUND_HALF_UP)
							: round($baseService * $remiseRate * $object->multicurrency_tx, 2, PHP_ROUND_HALF_UP);
						$pu_ht_devise_calc	= $remiseCurrency;
					} else {
						$pu_ht_devise_calc	= infrasdiscount_to_foreign($remise, $object);
					}
				}

				// Mettre à jour la description
				$desc	= $this->updateProrataDescription($l->desc, $remise, $pu_ht_devise_calc, $object);

				infrasdiscount_updateRemLine($object, $l, $remise, $pu_ht_devise_calc, $desc);
			}
		}

		/**
		 * Calcule la base pour les lignes prorata
		 *
		 * @param	CommonObject	$object			L'objet
		 * @param	array			$prorataIds		IDs des lignes prorata
		 * @return	array							Totaux calculés
		 */
		private function calculateProrataBase(&$object, $prorataIds)
		{
			$oldProrataProduct	= $oldProrataService	= 0;
			$totalProductPrice	= $totalServicePrice	= 0;

			foreach ($object->lines as $l) {
				if (infrasdiscount_isSubtotalLine($l)) {
					continue;
				}

				$isRemline	= isset($l->array_options['options_specialtype']) &&
							  in_array($l->array_options['options_specialtype'], [1, 2, 3, 4]) &&
							  in_array($l->id, $prorataIds);

				if ($l->product_type == 0) {
					$totalProductPrice	+= $l->total_ht;
					if ($isRemline && $l->array_options['options_specialtype'] == 3) {
						$oldProrataProduct	+= $l->total_ht;
					}
				} elseif ($l->product_type == 1) {
					$totalServicePrice	+= $l->total_ht;
					if ($isRemline && $l->array_options['options_specialtype'] == 3) {
						$oldProrataService	+= $l->total_ht;
					}
				}
			}

			return array(
				'baseProduct'	=> $totalProductPrice + abs($oldProrataProduct),
				'baseService'	=> $totalServicePrice + abs($oldProrataService)
			);
		}

		/**
		 * Met à jour la description d'une ligne prorata
		 *
		 * @param	string			$desc				Description actuelle
		 * @param	float			$remise				Nouveau montant
		 * @param	float			$pu_ht_devise_calc	Montant en devise
		 * @param	CommonObject	$object				L'objet
		 * @return	string								Nouvelle description
		 */
		private function updateProrataDescription($desc, $remise, $pu_ht_devise_calc, $object)
		{
			if (infrasdiscount_multicurrency_enabled($object)) {
				return preg_replace_callback(
					'/([\d\s.,]+)\s*\/\s*([\d\s.,]+)\s*([^\d\s]+)(.*)$/u',
					function ($matches) use ($pu_ht_devise_calc, $remise) {
						$newValueCurrency	= price(abs($pu_ht_devise_calc), 0, '', 1, -1, -1, ' ');
						$newValueBase		= price(abs($remise), 0, '', 1, -1, -1, ' ');
						return $newValueCurrency.' / '.$newValueBase.$matches[3].$matches[4];
					},
					$desc
				);
			}

			return preg_replace_callback(
				'/^([^\d\s-]*)([\d\s.,]+)(.*)$/u',
				function ($matches) use ($remise) {
					$newValue	= price(abs($remise), 0, '', 1, -1, -1, ' ');
					return $matches[1].$newValue.$matches[3];
				},
				$desc
			);
		}

		/**
		 * Met à jour une ligne de remise normale
		 *
		 * @param	CommonObject	$object					L'objet
		 * @param	int				$lineid					ID de la ligne
		 * @param	string			$remiseModValue			Nouvelle valeur
		 * @param	string			$remiseModValueCurrency	Nouvelle valeur en devise
		 * @param	string			$remProductRef			Référence produit remise
		 * @param	string			$remServiceRef			Référence service remise
		 * @return	void
		 */
		private function updateNormalLine(&$object, $lineid, $remiseModValue, $remiseModValueCurrency, $remProductRef, $remServiceRef)
		{
			global $langs;

			foreach ($object->lines as $line) {
				if ($line->id != $lineid ||
					!isset($line->array_options['options_specialtype']) ||
					!in_array($line->array_options['options_specialtype'], [1, 2, 3, 4])) {
					continue;
				}

				// Récupérer la valeur saisie
				$value	= !empty($remiseModValueCurrency) ? $remiseModValueCurrency : $remiseModValue;

				if ($value === '' || $value === null) {
					continue;
				}

				$valueBase		= !empty($remiseModValue) ? price2num($remiseModValue, 'CU', 2) : 0;
				$valueCurrency	= !empty($remiseModValueCurrency) ? price2num($remiseModValueCurrency, 'CU', 2) : 0;

				$preparedPrices		= infrasdiscount_prepare_prices($valueBase, $valueCurrency, $object);
				$newValue			= $preparedPrices['pu_ht'];
				$newValueCurrency	= $preparedPrices['pu_ht_devise'];

				if (!is_numeric($newValue)) {
					continue;
				}

				$specialtype	= $line->array_options['options_specialtype'];
				$desc			= $line->desc;
				$remise			= 0;
				$pu_ht_devise_upd	= 0;

				switch ($specialtype) {
					case 1: // Pourcentage
						$updateData	= $this->calculatePercentUpdate($object, $line, $newValue, $remProductRef, $remServiceRef);
						if ($updateData === null) {
							continue 2;
						}
						$remise				= $updateData['remise'];
						$pu_ht_devise_upd	= $updateData['pu_ht_devise'];
						$desc				= preg_replace('/([\d\.,]+)\s*%/', $newValue.' %', $desc);
						break;

					case 2: // Montant fixe
						if (!$this->isValidAmountLine($line, $langs)) {
							continue 2;
						}
						$remise				= $newValue;
						$pu_ht_devise_upd	= $newValueCurrency;
						$desc				= $this->updateAmountDescription($desc, $newValue, $newValueCurrency, $object);
						break;

					default:
						continue 2;
				}

				infrasdiscount_updateRemLine($object, $line, $remise, $pu_ht_devise_upd, $desc);
			}
		}

		/**
		 * Calcule la mise à jour pour une remise en pourcentage
		 *
		 * @param	CommonObject	$object			L'objet
		 * @param	object			$line			La ligne
		 * @param	float			$newPercent		Nouveau pourcentage
		 * @param	string			$remProductRef	Référence produit remise
		 * @param	string			$remServiceRef	Référence service remise
		 * @return	array|null						Données de mise à jour ou null
		 */
		private function calculatePercentUpdate(&$object, $line, $newPercent, $remProductRef, $remServiceRef)
		{
			global $langs;

			$isProductDiscount	= (strpos($line->desc, $langs->trans('InfraSDiscountProductLabel')) !== false);

			// Trouver la position de la ligne
			$lineIds	= array_column($object->lines, 'id');
			$currentPos	= array_search($line->id, $lineIds, true);
			if ($currentPos === false) {
				$currentPos	= count($object->lines);
			}

			// Calculer la base en mode cascade
			$base	= infrasdiscount_calculateCascadeBase($object, $currentPos, $isProductDiscount, $remProductRef, $remServiceRef);

			$remise	= ($base == 0) ? 0 : $base * $newPercent / 100;

			$pu_ht_devise	= 0;
			if (infrasdiscount_multicurrency_enabled($object)) {
				$pu_ht_devise	= infrasdiscount_to_foreign($remise, $object);
			}

			return array(
				'remise'		=> $remise,
				'pu_ht_devise'	=> $pu_ht_devise
			);
		}

		/**
		 * Vérifie si une ligne de montant est valide
		 *
		 * @param	object	$line	La ligne
		 * @param	object	$langs	Objet langs
		 * @return	bool			True si valide
		 */
		private function isValidAmountLine($line, $langs)
		{
			$isProduct	= ($line->product_type == 0);
			$isService	= ($line->product_type == 1);

			$hasProductLabel	= (strpos($line->desc, $langs->trans('InfraSDiscountProductLabel')) !== false);
			$hasServiceLabel	= (strpos($line->desc, $langs->trans('InfraSDiscountServiceLabel')) !== false);

			return ($isProduct && $hasProductLabel) || ($isService && $hasServiceLabel);
		}

		/**
		 * Met à jour la description d'une ligne montant
		 *
		 * @param	string			$desc				Description actuelle
		 * @param	float			$newValue			Nouveau montant
		 * @param	float			$newValueCurrency	Nouveau montant en devise
		 * @param	CommonObject	$object				L'objet
		 * @return	string								Nouvelle description
		 */
		private function updateAmountDescription($desc, $newValue, $newValueCurrency, $object)
		{
			global $langs;

			if (infrasdiscount_multicurrency_enabled($object)) {
				return preg_replace_callback(
					'/([\d\s.,]+)\s*\/\s*([\d\s.,]+)\s*([^\d\s]+)(.*)$/u',
					function ($matches) use ($newValueCurrency, $newValue) {
						$dispCurrency	= price(abs($newValueCurrency), 0, '', 1, -1, -1, ' ');
						$dispBase		= price(abs($newValue), 0, '', 1, -1, -1, ' ');
						return $dispCurrency.' / '.$dispBase.$matches[3].$matches[4];
					},
					$desc
				);
			}

			return preg_replace('/^-?\s*[\d\.,\s]+€/', price($newValue, 0, $langs, 1, -1, -1, 'auto'), $desc);
		}

		/**
		 * Hook called after line modification
		 * Uses static flag to prevent conflict with trigger
		 *
		 * @param	array			$parameters		Hook metadatas
		 * @param	CommonObject	$object			The object
		 * @param	string			$action			Current action
		 * @return	int								< 0 on error, 0 on success
		 **/
		public function formObjectOptions($parameters, &$object, &$action)
		{
			static $isRecalculating	= false;

			if ($isRecalculating) {
				return 0; // Avoid recursion
			}

			if (in_array($object->element, array('propal', 'commande', 'facture'))) {
				$isRecalculating	= true;

				// Recalculate percent discounts
				infrasdiscount_recalculatePercentDiscounts($object);

				// Recalculate prorata discounts
				infrasdiscount_recalculateProrataDiscounts($object);

				$isRecalculating	= false;
			}
			return 0;
		}
	}
