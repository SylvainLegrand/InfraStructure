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
	*	\file		./infrasdiscount/core/triggers/interface_99_modinfrasdiscount_Infrasdiscounttrigger.class.php
	*	\ingroup	InfraS
	*	\brief		Trigger for the module InfraS
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
	dol_include_once('/infrasdiscount/core/lib/infrasdiscount.lib.php');

	/************************************************
	*	Trigger class
	************************************************/
	class InterfaceInfrasdiscounttrigger extends DolibarrTriggers
	{
		protected $db;	// Database handler @var DoliDB
		public $name				= '';							// Name of the trigger @var mixed|string
		public $description			= '';							// Description of the trigger @var string
		public $version				= self::VERSION_DEVELOPMENT;	// Version of the trigger @var string
		public $picto				= 'technic';					// Image of the trigger @var string
		public $family				= '';							// Category of the trigger @var string
		public $errors				= array();						// Errors reported by the trigger @var array
		const VERSION_DEVELOPMENT	= 'development';				// @var string module is in development
		const VERSION_EXPERIMENTAL	= 'experimental';				// @var string module is experimental
		const VERSION_DOLIBARR		= 'dolibarr';					// @var string module is dolibarr ready

		/**
		*	Constructor
		*
		*	@param DoliDB $db Database handler
		**/
		public function __construct($db)
		{
			$this->db			= $db;
			$this->name			= preg_replace('/^Interface/i', '', get_class($this));
			$this->family		= 'crm';
			$this->description	= 'Triggers of this module allows to create discounts';
			$this->version		= '1.9.1';			// 'development', 'experimental', 'dolibarr' or version
			$this->picto		= 'infrasdiscount@infrasdiscount';

		}

		/**
		*	Trigger name
		*
		*	@return		string		Name of trigger file
		**/
		public function getName()
		{
			return $this->name;
		}

		/**
		*	Trigger description
		*
		*	@return		string		Description of trigger file
		**/
		public function getDesc()
		{
			return $this->description;
		}

		/**
		*	Trigger version
		*
		*	@return		string		Version of trigger file
		**/
		public function getVersion()
		{
			global $langs;

			$langs->load('admin');

			if ($this->version == 'development') {
				return $langs->trans('Development');
			} elseif ($this->version == 'experimental') {
				return $langs->trans('Experimental');
			} elseif ($this->version == 'dolibarr') {
				return DOL_VERSION;
			} elseif ($this->version) {
				return $this->version;
			} else {
				return $langs->trans('Unknown');
			}
		}

		/**
		*	Function called when a Dolibarrr business event is done.
		*	All functions "run_trigger" are triggered if file is inside directory core/triggers
		*
		*	@param string		$action		Event action code
		*	@param object		$object		Object
		*	@param User			$user		Object user
		*	@param Translate	$langs		Object langs
		*	@param Conf			$conf		Object conf
		*	@return				int			<0 if KO, 0 if no triggered ran, >0 if OK
		**/
		public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
		{
			if (!isModEnabled('infrasdiscount') || !in_array($object->element, ['propaldet', 'commande', 'commandedet', 'facture', 'facturedet'])) {
				return 0;
			}
			$insert_actions		= array('LINEPROPAL_INSERT', 'LINEORDER_INSERT', 'LINEBILL_INSERT');
			$update_actions		= array('LINEPROPAL_UPDATE', 'LINEPROPAL_MODIFY', 'LINEORDER_UPDATE', 'LINEORDER_MODIFY', 'LINEBILL_UPDATE', 'LINEBILL_MODIFY','LINEORDER_DELETE');
			$validate_actions	= array('ORDER_VALIDATE', 'BILL_PAYED');
			$delete_actions		= array('LINEPROPAL_DELETE', 'LINEORDER_DELETE', 'LINEBILL_DELETE');
			$authorizedActions	= array_merge($insert_actions, $update_actions, $validate_actions, $delete_actions);
			if (!in_array($action, $authorizedActions)) {
				return 0;
			}
			$element	= null;
			switch ($object->element) {
				case 'propaldet' :
					$element	= new Propal($this->db);
					$element->fetch($object->fk_propal);
				break;
				case 'commandedet' :
					$element	= new Commande($this->db);
					$element->fetch($object->fk_commande);
				break;
				case 'facturedet' :
					$element	= new Facture($this->db);
					$element->fetch($object->fk_facture);
				break;
				case 'facture' :
					$element	= new Facture($this->db);
					$element->fetch($object->id);
				break;
				case 'commande' :
					$element	= new Commande($this->db);
					$element->fetch($object->id);
				break;
			}
			// Update
			if (in_array($action, $update_actions) || in_array($action, $insert_actions) || in_array($action, $delete_actions)) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '.__FILE__.' id = '.$object->rowid);
				return $this->updateRemise($element, $object, $action);
			}
			// validate
			if (in_array($action, $validate_actions)) {
				dol_syslog('Trigger"'.$this->name.'" for action '.$action.' launched by '.__FILE__.' id = '.$object->rowid);
				switch ($action) {
					case 'ORDER_VALIDATE':
						$res	= $this->validateRemiseAutomatique( $object);
					break;
					case 'BILL_PAYED':
						$res	= $this->payFacture($object);
					break;
					}
				return $res;
			}
			return 0;
		}

		/**
		*	Lors d'une action sur un élément (../element/card.php)
		*
		*	@param	Propal|Commande|Facture	$element	L'objet à traiter (une facture, une propal, etc...)
		*	@param	object					$object		La ligne à traiter (une ligne de facture, une ligne de propal, etc...)
		*	@param	string					$action		Code d'action de l'événement
		*	@return	int										< 0 en cas d'erreur, > 0 en cas de succès
		**/
		public function updateRemise($element, &$object, $action)
		{
			global $conf, $langs, $user;

			$langs->load('infrasdiscount@infrasdiscount');

			// Utilisation d'un flag statique pour éviter la récursion
			static $isUpdating = false;
			if ($isUpdating) {
				return 0; // Sortir immédiatement pour éviter la boucle infinie
			}

			try {
				$isUpdating		= true; // Activer le flag

				// Recharger l'objet complet avec ses lignes à jour
				$element->fetch($element->id);
				$element->fetch_lines();

				// Initialiser le résultat
				$result			= 1;

				// Recalculer TOUTES les remises en pourcentage (cascade)
				$resultPercent	= infrasdiscount_recalculatePercentDiscounts($element);
				if ($resultPercent < 0) {
					setEventMessages($element->error, $element->errors, 'errors');
					return -1;
				}

				// Recalculer TOUTES les remises au prorata
				$resultProrata	= infrasdiscount_recalculateProrataDiscounts($element);
				if ($resultProrata < 0) {
					setEventMessages($element->error, $element->errors, 'errors');
					return -1;
				}

				// Recharger l'objet pour avoir les nouvelles valeurs après recalcul
				$element->fetch($element->id);
				$element->fetch_lines();

				// Récupérer les références des produits de remise via le helper
				$refs			= infrasdiscount_getDiscountProductRefs();
				$remProductRef	= $refs['product'];
				$remServiceRef	= $refs['service'];

				// Identifier et supprimer les lignes de remise à 0
				$linesToDelete	= array();
				foreach ($element->lines as $line) {
					// Ignorer les lignes du module subtotal ATM (titres, sous-totaux, textes libres)
					if (infrasdiscount_isSubtotalLine($line)) {
						continue;
					}
					if (isset($line->array_options['options_specialtype']) && in_array($line->array_options['options_specialtype'], [1, 2, 3, 4])) {
						if (round(abs($line->total_ht), 2) == 0) { // Proche de 0
							$linesToDelete[]	= $line->id;
						}
					}
				}

				// Supprimer les lignes identifiées (en dehors de la boucle foreach)
				foreach ($linesToDelete as $lineId) {
					$deleteResult		= -1;
					if ($element->element == 'propal') {
						$deleteResult	= $element->deleteline($lineId);
					} elseif ($element->element == 'commande') {
						$deleteResult	= $element->deleteline($user, $lineId);
					} elseif ($element->element == 'facture') {
						$deleteResult	= $element->deleteline($lineId);
					} else {
						return -1; // Élément non reconnu
					}

					if ($deleteResult < 0) {
						setEventMessages($element->error, $element->errors, 'errors');
						return -1;
					} else {
						dol_syslog('Ligne supprimée avec succès : ID ligne = '.$lineId, LOG_DEBUG);
					}
				}

				// IMPORTANT : Recharger l'objet après les suppressions
				if (!empty($linesToDelete)) {
					$element->fetch($element->id);
					$element->fetch_lines();
					dol_syslog('Objet rechargé après suppression de '.count($linesToDelete).' lignes de remise', LOG_DEBUG);
				}

				// Générer le PDF si nécessaire
				if ($result > 0 && !getDolGlobalInt('MAIN_DISABLE_PDF_AUTOUPDATE', 0)) {
					$outputlangs	= $langs;
					$hidedetails	= (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS', '') ? 1 : 0));
					$hidedesc		= (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', '') ? 1 : 0));
					$hideref		= (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF', '') ? 1 : 0));
					if (getDolGlobalString('MAIN_MULTILANGS', '')) {
						$outputlangs = new Translate('', $conf);
						$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $element->thirdparty->default_lang);
						$outputlangs->setDefaultLang($newlang);
					}
					$ret				= $element->fetch($element->id); // Recharger pour obtenir les nouveaux enregistrements
					if ($ret > 0) {
						$element->fetch_thirdparty();
					}
					$element->generateDocument($element->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}

				// Mettre à jour le prix total
				if ($result > 0) {
					$result			= $element->update_price();
				}
				return $result;
			} finally {
				$isUpdating			= false; // Désactiver le flag même en cas d'erreur
			}
		}
		/**
		*	When we ask for an action (../element/card.php)
		*	@param	object	$object				The object to process (an invoice, a propale, etc...)
		*	@return	int							< 0 on error, > 0 on success
		**/
		private function validateRemiseAutomatique($object)
		{
			global $langs;
			$langs->load('infrasdiscount@infrasdiscount');
			// Récupération des paramètres globaux
			$productmultiselect		= explode(',', getDolGlobalString('INFRASDISCOUNT_PRODUCT_AFFILIATE', ''));
			$freeDiscountNumber		= getDolGlobalInt('INFRASDISCOUNT_FREE_LINE', 0);
			$numberDiscountAllow	= getDolGlobalInt('INFRASDISCOUNT_NUMBER_DISCOUNT_ALLOW', 0);
			$ponderationArticle		= getDolGlobalInt('INFRASDISCOUNT_PONDERATION', 0);
			$freetextDescription	= getDolGlobalString('INFRASDISCOUNT_DESC_FREETEXT', '');
			$extrafieldsline		= new ExtraFields($this->db);
			$extralabelsline		= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
			$array_options			= $extrafieldsline->getOptionalsFromPost($extralabelsline);
			foreach ($object->lines as $line) {
				// Récupérer le taux de TVA de la ligne
				$tva_tx	= $line->tva_tx;
				if (!empty($line->vat_src_code) && !preg_match('/\(/', $tva_tx)) {
					$tva_tx	.= ' ('.$line->vat_src_code.')';
				}
			}
			$localtax1_tx	= get_localtax($tva_tx, 1, $object->thirdparty);
			$localtax2_tx	= get_localtax($tva_tx, 2, $object->thirdparty);
			//vérifier si le produit selectionné dans le multiselect est dans la ligne de l'objet(commande)
			$productinObj	= array();
			foreach($object->lines as $line) {
				if (in_array($line->fk_product, $productmultiselect)) {
					$productinObj[]	= $line->fk_product;
				}
			}
			//vérifier le nombre de commandes validées du client
			$nbValidatedOrders	= 0;
			$commande			= new Commande($this->db);
			$sql				= "SELECT rowid FROM ".$this->db->prefix()."commande WHERE fk_soc = ".((int) $object->socid);
			$resql				= $this->db->query($sql);
			if ($resql) {
				while ($obj	= $this->db->fetch_object($resql)) {
					if ($commande->fetch($obj->rowid) > 0 && $commande->statut == Commande::STATUS_VALIDATED) {	// 1 = Validated
						$nbValidatedOrders++;
					}
				}
			} else {
				dol_syslog('Error fetching validated orders count: '.$this->db->lasterror());
				return -1;
			}
			// Verifier si le multiselect n'est pas vide et si le produit selectionné existe dans la commande et si le nombre de ligne gratuit n'est pas vide ou égale à zéro et si le nombre de remise autorisé est supérieur ou égal au nombre de commandes validées
			if (!empty($productmultiselect) && !empty($productinObj) && (!empty($freeDiscountNumber) || $freeDiscountNumber != 0) && ($numberDiscountAllow >= $nbValidatedOrders)) {
				$totalDiscountAmount	= 0;
				$descriptionDetails		= array();
				//si $ponderationArticle est vide ou égale à zéro, appliquer la remise sur tous les produits dans $productinObj
				if (empty($ponderationArticle) || $ponderationArticle == 0) {
					foreach($object->lines as $line) {
						if (in_array($line->fk_product, $productinObj)) {
							$quantity	= $line->qty;
							if ($quantity >= $freeDiscountNumber) {
								$discountAmount	= $freeDiscountNumber * $line->subprice;
							} else {
								$discountAmount	= $quantity * $line->subprice;
							}
							$totalDiscountAmount	+= $discountAmount;
							if ($quantity >= $freeDiscountNumber) {
								$descriptionDetails[]	= price($discountAmount, 0, $langs, 1, -1, -1, 'auto').' HT '.$langs->trans('onProduct').$line->product_label.' ('.$langs->trans('Quantity').': '.$freeDiscountNumber.' - '.$langs->trans('subprice').' : '.price($line->subprice, 0, $langs, 1, -1, -1, 'auto').')';
							} else {
								$descriptionDetails[]	= price($discountAmount, 0, $langs, 1, -1, -1, 'auto').' HT '.$langs->trans('onProduct').$line->product_label.' ('.$langs->trans('Quantity').': '.$quantity.' - '.$langs->trans('subprice').' : '.price($line->subprice, 0, $langs, 1, -1, -1, 'auto').')';
							}
						}
					}
				} else {
					// Première boucle : Parcourir toutes les lignes de l'objet pour trouver le produit dans $ponderationArticle
					foreach ($object->lines as $line) {
						if ($line->fk_product == $ponderationArticle) {
							// Logique pour les produits trouvés dans $ponderationArticle
							$quantity				= $line->qty;
							$discountAmount			= ($quantity >= $freeDiscountNumber) ? $freeDiscountNumber * $line->subprice : $quantity * $line->subprice;
							$totalDiscountAmount	+= $discountAmount;
							$newfreeDiscountNumber	= ($freeDiscountNumber - $quantity < 0) ? 0 : $freeDiscountNumber - $quantity;
							if ($quantity >= $freeDiscountNumber) {
								$descriptionDetails[]	= price($discountAmount, 0, $langs, 1, -1, -1, 'auto').' HT '.$langs->trans('onProduct').$line->product_label.' ('.$langs->trans('Quantity').': '.$freeDiscountNumber.' - '.$langs->trans('subprice').' : '.price($line->subprice, 0, $langs, 1, -1, -1, 'auto').')';
							} else {
								$descriptionDetails[]	= price($discountAmount, 0, $langs, 1, -1, -1, 'auto').' HT '.$langs->trans('onProduct').$line->product_label.' ('.$langs->trans('Quantity').': '.$quantity.' - '.$langs->trans('subprice').' : '.price($line->subprice, 0, $langs, 1, -1, -1, 'auto').')';
							}
							break;
						}
					}
					// Deuxième boucle : Parcourir toutes les lignes de l'objet pour chercher les articles dans $productinObj qui ne sont pas dans $ponderationArticle
					foreach ($object->lines as $line) {
						if (in_array($line->fk_product, $productinObj) && $line->fk_product != $ponderationArticle) {
							// Logique pour les produits dans $productinObj mais pas dans $ponderationArticle
							$quantity				= $line->qty;
							$discountAmount			= ($quantity >= $newfreeDiscountNumber) ? $newfreeDiscountNumber * $line->subprice : $quantity * $line->subprice;
							$totalDiscountAmount	+= $discountAmount;
							if ($quantity >= $newfreeDiscountNumber) {
								$descriptionDetails[]	= price($discountAmount, 0, $langs, 1, -1, -1, 'auto').' HT '.$langs->trans('onProduct').$line->product_label.' ('.$langs->trans('Quantity').': '.$newfreeDiscountNumber.' - '.$langs->trans('subprice').' : '.price($line->subprice, 0, $langs, 1, -1, -1, 'auto').')';
							} else {
								$descriptionDetails[]	= price($discountAmount, 0, $langs, 1, -1, -1, 'auto').' HT '.$langs->trans('onProduct').$line->product_label.' ('.$langs->trans('Quantity').': '.$quantity.' - '.$langs->trans('subprice').' : '.price($line->subprice, 0, $langs, 1, -1, -1, 'auto').')';
							}
						}
					}
				}
				// Add a discount line to the object
				if ($totalDiscountAmount > 0) {
					$newSubprice	= $totalDiscountAmount / $freeDiscountNumber;
					if (empty($freetextDescription)) {
						$discountDescription	= implode('</br>', $descriptionDetails);
					} else {
						$discountDescription	= $freetextDescription.'</br>'.implode('</br>', $descriptionDetails);
					}
					$discountLine	= array('desc'				=> $langs->trans('AutomaticDiscount').'</br>'.$discountDescription,
											'subprice'			=> -$newSubprice,
											'qty'				=> $freeDiscountNumber,
											'tva_tx'			=> $tva_tx,
											'fk_product_type'	=> 0,
											'info_bits'			=> 0,
											'type'				=> 0,
											'special_code'		=> 9,
											'fk_parent_line'	=> 0,
											'fk_unit'			=> 0,
											'localtax1_tx'		=> $localtax1_tx,
											'localtax2_tx'		=> $localtax2_tx,
											'pa_ht'				=> -$totalDiscountAmount
											);
					// Vérifier si une ligne de remise existe déjà
					$remiseLine		= null;
					foreach ($object->lines as $line) {
						if ($line->special_code == 9) {
							$remiseLine	= $line;
							break;
						}
					}
					if ($remiseLine) {
						// Recalculer la description avec les nouvelles quantités et montants
						$discountLine['desc']			= $langs->trans('AutomaticDiscount').'</br>'.implode('</br>', $descriptionDetails);
						$array_options['specialtype']	= 4;
						if (abs($remiseLine->subprice) != $totalDiscountAmount || $remiseLine->desc != $discountLine['desc']) {
							// Si la ligne de remise existe déjà, on met à jour le montant
							if ($object->element == 'commande') {
								$result	= $object->updateline($remiseLine->id,					//rowid
															  $discountLine['desc'],			// $desc
															  $discountLine['subprice'],		// $pu_ht
															  $discountLine['qty'],				// $qty
															  0,								// $remise_percent
															  $discountLine['tva_tx'],			// $txtva
															  $discountLine['txlocaltax1'],		// $txlocaltax1
															  $discountLine['txlocaltax2'],		// $txlocaltax2
															  'HT',								// $price_base_type
															  $discountLine['info_bits'],		// $info_bits
															  0,								// $date_start
															  0,								// $date_end
															  $discountLine['fk_product_type'],	// $fk_product_Type of line (0=product, 1=service)
															  0,								// $fk_parent_line
															  0,								//skip update total
															  0,								// $fk_fournprice
															  $discountLine['pa_ht'],			// $pa_ht
															  '',								// $label
															  $discountLine['special_code'],	// $special_code
															  $array_options,					// $array_options
															  null,								// $fk_unit
															  '',								// $pu_ht_devise
															  0,								//notrigger
															  ''								// $ref_ext
															  );
							}
						}
					} else {
						if ($object->element == 'commande') {
							$result	= $object->addline ($discountLine['desc'],				// $desc
														$discountLine['subprice'],			// $pu_ht
														$discountLine['qty'],				// $qty
														$discountLine['tva_tx'],			// $txtva
														0,									// $txlocaltax1
														0,									// $txlocaltax2
														$discountLine['fk_product_type'],	// $fk_product
														0,									// $remise_percent
														$discountLine['info_bits'],			// $info_bits
														0,									// $fk_remise_except
														'HT',								// $price_base_type
														0,									// $pu_ttc
														'',									// $date_start
														'',									// $date_end
														$discountLine['type'],				// $type
														-1,									// $rang
														$discountLine['special_code'],		// $special_code
														0,									// $fk_parent_line
														null,								// $fk_fournprice
														$discountLine['pa_ht'],				// $pa_ht
														'',									// $label
														$array_options,						// $array_options
														null,								// $fk_unit
														'',									// $origin
														0,									// $origin_id
														0,									// $pu_ht_devise
														''									// $ref_ext
														);
						}
						if ($result < 0) {
							dol_syslog('Error adding discount line: '.$object->error, LOG_ERR);
							return -1;
						}
					}
				}
			}
			return 1;
		}

		/**
		*	When we ask for an action (../element/card.php)
		*
		*	@param	CommonObject	$object		The line to process (an invoice line, a propale line, etc...)
		*	@return	int							< 0 on error, > 0 on success
		 **/
		private function payFacture($object)
		{
			// URL de l'API pour obtenir le token
			$url			= getDolGlobalString('INFRASDISCOUNT_OAUTH_URL', 'https://connection.sortandgroup.fr/realms/front/protocol/openid-connect/token');
			// Identifiants client (stockés en base via la page de configuration)
			$client_id		= getDolGlobalString('INFRASDISCOUNT_OAUTH_CLIENT_ID', '');
			$client_secret	= getDolGlobalString('INFRASDISCOUNT_OAUTH_CLIENT_SECRET', '');
			if (empty($client_id) || empty($client_secret)) {
				dol_syslog("payFacture: OAuth2 credentials not configured (INFRASDISCOUNT_OAUTH_CLIENT_ID / INFRASDISCOUNT_OAUTH_CLIENT_SECRET)", LOG_ERR);
				return -1;
			}
			// Type de grant pour l'authentification
			$grant_type		= 'client_credentials';
			// Initialiser cURL
			$ch				= curl_init();
			// Configurer la requête cURL
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['grant_type' => $grant_type]));
			curl_setopt($ch, CURLOPT_USERPWD, $client_id.':'.$client_secret);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
			// Exécuter la requête cURL
			$response	= curl_exec($ch);
			// Vérifier les erreurs
			if (curl_errno($ch)) {
				dol_syslog("Erreur cURL : ".curl_error($ch), LOG_ERR);
				curl_close($ch);
				return -1;	// Retourner -1 en cas d'erreur
			}
			// Fermer la connexion cURL
			curl_close($ch);
			// Décoder la réponse JSON
			$responseData	= json_decode($response, true);
			// Vérifier si le token est présent
			if (!isset($responseData['access_token'])) {
				dol_syslog("Erreur : Aucun token trouvé dans la réponse", LOG_ERR);
				return -1;	// Retourner -1 si aucun token n'est trouvé
			}
			// Récupérer le token d'accès
			$accessToken	= $responseData['access_token'];
			dol_syslog("Token d'accès récupéré avec succès", LOG_DEBUG);
			// Call the payment API with the batch ID and access token
			$batch_id	= $object->array_options['options_uid'];	// Assuming 'options_uid' contains the batch ID
			$products	= array();
			foreach($object->lines as $line) {
				$products[]	= $line->ref;
			}
			$response	= $this->callPaymentAPI($batch_id, $accessToken, $products);
			// Debugging: Log the API response
			if ($response) {
				dol_syslog("API Response: ".$response, LOG_DEBUG);
			} else {
				dol_syslog("API call failed.", LOG_ERR);
			}
			// Return the access token for further use if needed
			return $accessToken;
		}

		/**
		*	Calls the payment API for a specific batch.
		*
		*	@param	string	$batch_id		The ID of the batch to process payment for.
		*	@param	string	$access_token	Access token for API authentication.
		*	@return	string|null			The raw API response on success, or null on error.
		**/
		private function callPaymentAPI($batch_id, $access_token, $products) {
			// Construct the API URL using DOL_URL_ROOT for portability
			$url	= "https://icr-api.sortandgroup.fr/api/batches/$batch_id/_payment";
			// Initialize a cURL session
			$ch	= curl_init();
			// Set cURL options
			curl_setopt($ch, CURLOPT_URL, $url);	// Set the target URL
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	// Return the response as a string
			curl_setopt($ch, CURLOPT_POST, true);	// Indicate that this is a POST request
			curl_setopt($ch, CURLOPT_HTTPHEADER, [	// Set HTTP headers for authentication and content type
				"Authorization: Bearer $access_token",	// Pass the access token in the Authorization header
				"Content-Type: application/json",	// Specify that the request body is in JSON format
			]);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['products' => $products]));	// Set the request body to a JSON-encoded array
			// Execute the cURL request and capture the response
			$response	= curl_exec($ch);
			// Check for cURL errors and handle them
			if (curl_errno($ch)) {
				// Log the error message using dol_syslog
				dol_syslog("Erreur cURL : ".curl_error($ch), LOG_ERR);
				// Close the cURL session and return null to indicate failure
				curl_close($ch);
				return null;
			}
			// Close the cURL session
			curl_close($ch);
			// Return the raw response from the API
			return $response;
		}
	}
