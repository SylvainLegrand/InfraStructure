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

	// Libraries ************************************

	/************************************************
	* 	\file		./infrasdiscount/core/lib/infrasdiscount.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraS module
	************************************************/

	/**
	* Calcul du total HT à prendre en compte pour la remise
	* Exclut les lignes du module subtotal ATM (titres, sous-totaux, textes libres)
	*
	* @param	CommonObject	$object			L'objet à traiter (une facture si vous êtes dans le module facture, une propal dans le module propal, etc...)
	* @param	int				$only_product	inclure uniquement les produits dans le calcul
	* @param	int				$only_service	inclure uniquement les services dans le calcul
	* @param	array			$exceptIds		liste des remises à ne pas inclure car elles sont après la remise que nous mettons à jour
	* @return	float							Total HT à prendre en compte pour la remise
	**/
	function infrasdiscount_currentTotalPriceLines(&$object, $only_product = 0, $only_service = 0, $exceptIds = array())
	{
		global $conf;

		$totalHT	= 0;
		dol_syslog('infrasdiscount.lib::infrasdiscount_currentTotalPriceLines $object->id = '.$object->id.' $only_product = '.$only_product.' $only_service = '.$only_service.' $exceptIds = '.implode(',', $exceptIds));
		foreach ($object->lines as $line) {
			// Ignorer les lignes du module subtotal ATM (titres, sous-totaux, textes libres)
			if (infrasdiscount_isSubtotalLine($line)) {
				continue;
			}
			if ((empty($line->special_code) || in_array($line->special_code, array(7, 8))) && (empty($exceptIds) || !in_array($line->id, $exceptIds))) {
				if (empty($only_product) && empty($only_service)) {
					// Inclure toutes les lignes
					$totalHT += $line->total_ht;
				} elseif ($only_product && $line->product_type == 0) {
					// Inclure uniquement les produits
					$totalHT += $line->total_ht;
				} elseif ($only_service && $line->product_type == 1) {
					// Inclure uniquement les services
					$totalHT += $line->total_ht;
				}
			}
		}
		return $totalHT;
	}
	/**
	 * Retourne true si multicurrency actif et taux valide sur l'objet
	 */
	function infrasdiscount_multicurrency_enabled($object)
	{
		return isModEnabled('multicurrency')
			&& isset($object->multicurrency_tx)
			&& $object->multicurrency_tx > 0
			&& $object->multicurrency_tx != 1;
	}

	/**
	 * Retourne le taux de change utilisable (1 si non disponible)
	 */
	function infrasdiscount_multicurrency_rate($object)
	{
		if (!infrasdiscount_multicurrency_enabled($object)) return 1.0;
		return $object->multicurrency_tx > 0 ? (float)$object->multicurrency_tx : 1.0;
	}

	/**
	 * Convertit montant base -> devise étrangère (arrondi)
	 */
	function infrasdiscount_to_foreign($amount, $object)
	{
		$rate	= infrasdiscount_multicurrency_rate($object);
		return round($amount * $rate, 2, PHP_ROUND_HALF_UP);
	}

	/**
	 * Convertit montant devise étrangère -> base (arrondi), protège division par zéro
	 * @param	float		$amount_foreign		- montant en devise étrangère
	 * @param	object		$object				- objet parent (facture, propal, etc.)
	 * @return	float							- montant en base
	 */
	function infrasdiscount_to_base($amount_foreign, $object)
	{
		$rate	= infrasdiscount_multicurrency_rate($object);
		if ($rate == 0) return 0;
		return round($amount_foreign / $rate, 2, PHP_ROUND_HALF_UP);
	}

	/**
	 * Retourne le montant HT d'une ligne (priorité au champ multicurrency si $foreign=true)
	 * @param	object	$line			- ligne à traiter
	 * @param	object	$object			- objet parent (facture, propal, etc.)
	 * @param	bool	$foreign		- si true, utilise le montant en devise étrangère si disponible
	 * @return	float					- montant HT arrondi
	 */
	function infrasdiscount_line_amount($line, $object, $foreign = false)
	{
		if ($foreign && infrasdiscount_multicurrency_enabled($object)) {
			if (isset($line->multicurrency_total_ht) && $line->multicurrency_total_ht != 0) {
				return round($line->multicurrency_total_ht, 2, PHP_ROUND_HALF_UP);
			}
			return infrasdiscount_to_foreign($line->total_ht, $object);
		}
		return round($line->total_ht, 2, PHP_ROUND_HALF_UP);
	}

	/**
	 * Prépare montants pour addline/updateline :
	 * @param float			$pu_ht 					- si $pu_ht_devise fourni (non nul) -> calcule pu_ht base depuis la devise étrangère
	 * @param float			$pu_ht_devise 			- sinon calcule pu_ht_devise depuis pu_ht
	 * @return array('pu_ht' => ..., 'pu_ht_devise' => ...)
	 */
	function infrasdiscount_prepare_prices($pu_ht, $pu_ht_devise, $object)
	{
		if (!infrasdiscount_multicurrency_enabled($object)) {
			return array('pu_ht' => $pu_ht, 'pu_ht_devise' => 0);
		}

		// Si valeur étrangère fournie -> prioritaire
		if ($pu_ht_devise != 0) {
			$pu_ht_base		= infrasdiscount_to_base($pu_ht_devise, $object);
			return array('pu_ht' => $pu_ht_base, 'pu_ht_devise' => round($pu_ht_devise, 2));
		}

		// Sinon si valeur de base fournie -> calculer étrangère
		if ($pu_ht != 0) {
			$pu_ht_dev		= infrasdiscount_to_foreign($pu_ht, $object);
			return array('pu_ht' => round($pu_ht, 2), 'pu_ht_devise' => $pu_ht_dev);
		}

		return array('pu_ht' => 0, 'pu_ht_devise' => 0);
	}

	/**
	* Vérifie si on trouve une remise
	*
	* @param	CommonObject	$object		L'objet à traiter (une facture si vous êtes dans le module facture, une propal dans le module propal, etc...)
	* @return	boolean						true si le type de produit correspond, false sinon
	**/
	function infrasdiscount_hasRemise($object)
	{
		global $conf;

		dol_syslog('infrasdiscount.lib::infrasdiscount_hasRemise $object->id = '.$object->id);
		foreach ($object->lines as $line)
			if (in_array($line->special_code, array(6, 7, 8)))	return true;	// 6 = montant || 7 = % sur produit || 8 = % pour tout
		return false;
	}

	/**
	*	Vérifie si le type de ligne provient d'un module externe
	*
	*	@param		object		$line			ligne sur laquelle on travaille
	*	@param		string		$element		élément de l'objet ligne (pour cas spécial comme l'expédition)
	*	@param		array		$searchNames	noms des modules que nous recherchons
	*	@return		boolean						true si la ligne est spéciale et a été créée par l'un des modules que nous recherchons
	**/
	function infrasdiscount_isLineFromExternalModule ($line, $element, $searchNames)
	{
		global $db;

		if ($element == 'shipping' || $element == 'delivery') {
			$fk_origin_line	= $line->fk_origin_line;
			$line			= new OrderLine($db);
			$line->fetch($fk_origin_line);
		}
		foreach ($searchNames as $searchName) {
			if (!empty($line) && $line->product_type == 9 && $line->special_code == infrasdiscount_get_mod_number($searchName)) {
				return true;
			}
		}
		return false;
	}

	/**
	*	Trouve le numéro du module
	*
	*	@param		string		$searchName		nom du module que nous recherchons
	*	@return		integer						-1 si KO, 0 non trouvé ou numéro de module si Ok
	**/
	function infrasdiscount_get_mod_number ($modName)
	{
		global $db;

		if (class_exists($modName)) {
			$objMod	= new $modName($db);
			return $objMod->numero;
		}
		return 0;
	}

	/**
	* Vérifie si une ligne est une ligne du module subtotal ATM (titre, sous-total ou texte libre)
	* Ces lignes ont special_code = 104777, product_type = 9 et qty spécifique :
	* - qty <= 9 pour les titres
	* - qty >= 90 pour les sous-totaux
	* - qty == 50 pour les textes libres
	*
	* @param	object	$line	Ligne à vérifier
	* @return	bool			true si c'est une ligne subtotal ATM, false sinon
	**/
	function infrasdiscount_isSubtotalLine($line)
	{
		// Numéro du module subtotal ATM
		$subtotalModuleNumber = 104777;

		// Vérification si le module subtotal est activé et si la ligne correspond
		if (!empty($line->special_code) && $line->special_code == $subtotalModuleNumber && $line->product_type == 9) {
			// C'est une ligne du module subtotal (titre, sous-total ou texte libre)
			return true;
		}

		return false;
	}

	/**
	* Vérifie si une ligne est un titre du module subtotal ATM
	*
	* @param	object	$line	Ligne à vérifier
	* @return	bool			true si c'est un titre, false sinon
	**/
	function infrasdiscount_isSubtotalTitle($line)
	{
		$subtotalModuleNumber = 104777;
		return !empty($line->special_code) && $line->special_code == $subtotalModuleNumber && $line->product_type == 9 && $line->qty <= 9;
	}

	/**
	* Vérifie si une ligne est un sous-total du module subtotal ATM
	*
	* @param	object	$line	Ligne à vérifier
	* @return	bool			true si c'est un sous-total, false sinon
	**/
	function infrasdiscount_isSubtotalTotal($line)
	{
		$subtotalModuleNumber = 104777;
		return !empty($line->special_code) && $line->special_code == $subtotalModuleNumber && $line->product_type == 9 && $line->qty >= 90;
	}

	/**
	* Fonction pour ajouter une ligne de remise (utilise la fonction addline() d'un document (Propal, Factures, commande)).
	* Elle combine tous les paramètres de addline() en une seule fonction.
	*
	* @param	object		$object				L'objet à traiter (une facture si vous êtes dans le module facture, une propal dans le module propal, etc...)
	* @param	string		$descRemise			Description de la remise
	* @param	int			$pu_ht				Montant de la remise
	* @param	int			$type				Type de remise
	* @param	int			$tva_tx				Taux de TVA
	* @param	int			$localtax1_tx		Taux de taxe locale 1
	* @param	int			$localtax2_tx		Taux de taxe locale 2
	* @param	int			$remiseLinkTo		Référence de remise à lier (produit ou service)
	* @param	array		$array_options		options des champs supplémentaires pour le type de commission
	* @param	int			$pu_ht_devise		Montant en multidevise
	**/
	function infrasdiscount_addDiscountLine($object, $descRemise, $pu_ht, $type, $tva_tx, $localtax1_tx, $localtax2_tx, $remiseLinkTo, $array_options = null, $pu_ht_devise = 0)
	{
		$prices			= infrasdiscount_prepare_prices($pu_ht, $pu_ht_devise, $object);
		$pu_ht			= $prices['pu_ht'];
		$pu_ht_devise	= $prices['pu_ht_devise'];
		if ($object->element == 'propal') {
			$result	= $object->addline ($descRemise,	// $desc
										$pu_ht * -1,	// $pu_ht
										1,				// $qty
										$tva_tx,		// $txtva
										$localtax1_tx,	// $txlocaltax1
										$localtax2_tx,	// $txlocaltax2
										$remiseLinkTo,	// $fk_product
										0,				// $remise_percent
										'HT',			// $price_base_type
										0,				// $pu_ttc
										2,				// $info_bits
										$type,			// $type
										-1,				// $rang
										0,				// $special_code
										0,				// $fk_parent_line
										0,				// $fk_fournprice
										0,				// $pa_ht
										'',				// $label
										'',				// $date_start
										'',				// $date_end
										$array_options,	// $array_options
										null,			// $fk_unit
										'',				// $origin
										0,				// $origin_id
										$pu_ht_devise * -1,	// $pu_ht_devise
										0,				// $fk_remise_except
										);
		}
		if ($object->element == 'commande') {
			$result	= $object->addline ($descRemise,	// $desc
										$pu_ht * -1,	// $pu_ht
										1,				// $qty
										$tva_tx,		// $txtva
										$localtax1_tx,	// $txlocaltax1
										$localtax2_tx,	// $txlocaltax2
										$remiseLinkTo,	// $fk_product
										0,				// $remise_percent
										2,				// $info_bits
										0,				// $fk_remise_except
										'HT',			// $price_base_type
										0,				// $pu_ttc
										'',				// $date_start
										'',				// $date_end
										$type,			// $type
										-1,				// $rang
										0,				// $special_code
										0,				// $fk_parent_line
										null,			// $fk_fournprice
										0,				// $pa_ht
										'',				// $label
										$array_options,	// $array_options
										null,			// $fk_unit
										'',				// $origin
										0,				// $origin_id
										$pu_ht_devise * -1,	// $pu_ht_devise
										'',				// $ref_ext
										);
		}
		if ($object->element == 'facture') {
			$result	= $object->addline ($descRemise,	// $desc
										$pu_ht * -1,	// $pu_ht
										1,				// $qty
										$tva_tx,		// $txtva
										$localtax1_tx,	// $txlocaltax1
										$localtax2_tx,	// $txlocaltax2
										$remiseLinkTo,	// $fk_product
										0,				// $remise_percent
										'',				// $date_start
										'',				// $date_end
										0,				// $ventil
										2,				// $info_bits
										'',				// $fk_remise_except
										'HT',			// $price_base_type
										0,				// $pu_ttc
										$type,			// $type
										-1,				// $rang
										0,				// $special_code
										'',				// $origin
										0,				// $origin_id
										0,				// $fk_parent_line
										null,			// $fk_fournprice
										0,				// $pa_ht
										'',				// $label
										$array_options,	// $array_options
										100,			// $situation_percent
										0,				// $fk_prev_id
										null,			// $fk_unit
										$pu_ht_devise * -1,	// $pu_ht_devise
										''				// $ref_ext
										);
		}
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			return -1;
		}
		return $result;
	}
	/**
	* Fonction pour mettre à jour une ligne de remise (utilise la fonction updateline() d'un document (Propal, Factures, commande)).
	* Elle combine tous les paramètres de updateline() en une seule fonction.
	*
	*	@param	object	$element		L'objet à traiter (une facture, une propal, etc...)
	*	@param	object	$line			La ligne à traiter (une ligne de facture, une ligne de propal, etc...)
	*	@param	float	$pu_ht			Le montant de la remise à appliquer
	*	@param	float	$pu_ht_devise	Le montant de la remise à appliquer dans la devise de la ligne
	*	@param	string	$desc			La description de la remise
	*	@return	int						< 0 en cas d'erreur, > 0 en cas de succès
	**/
	function infrasdiscount_updateRemLine($element, $line, $pu_ht, $pu_ht_devise = 0, $desc = '')
	{
		// Force la remise à être négative
		$desc				= !empty($desc) ? $desc : $line->desc; // Utilise la description fournie ou la description de la ligne
		// Gère le multidevise
		$prices				= infrasdiscount_prepare_prices(abs($pu_ht), abs($pu_ht_devise), $element);
		$pu_ht				= -abs($prices['pu_ht']); // Force négatif pour remise
		$pu_ht_devise		= -abs($prices['pu_ht_devise']); // Force négatif pour remise

		if ($element->element == 'propal') {
			return $element->updateline($line->id,				// $rowid
										$pu_ht,					// $pu_ht
										1,						// $qty
										0,						// $remise_percent
										$line->tva_tx,			// $txtva
										$line->localtax1_tx,	// $txlocaltax1
										$line->localtax2_tx,	// $txlocaltax2
										$desc,					// $desc
										'HT',					// $price_base_type
										$line->info_bits,		// $info_bits
										$line->special_code,	// $special_code
										$line->fk_parent_line,	// $fk_parent_line
										0,						// $skip_update_total
										$line->fk_fournprice,	// $fk_fournprice
										0,						// $pa_ht
										$line->label,			// $label
										$line->product_type,	// $type
										$line->date_start,		// $date_start
										$line->date_end,		// $date_end
										$line->array_options,	// $array_options
										$line->fk_unit,			// $fk_unit
										$pu_ht_devise,			// $pu_ht_devise
										1						// $notrigger
										);
		}
		if ($element->element == 'commande') {
			return $element->updateline($line->id,				// $rowid
										$desc,					// $desc
										$pu_ht,					// $pu_ht
										1,						// $qty
										0,						// $remise_percent
										$line->tva_tx,			// $txtva
										$line->localtax1_tx,	// $txlocaltax1
										$line->localtax2_tx,	// $txlocaltax2
										'HT',					// $price_base_type
										$line->info_bits,		// $info_bits
										$line->date_start,		// $date_start
										$line->date_end,		// $date_end
										$line->product_type,	// $type
										$line->fk_parent_line,	// $fk_parent_line
										0,						// $skip_update_total
										$line->fk_fournprice,	// $fk_fournprice
										0,						// $pa_ht
										$line->label,			// $label
										$line->special_code,	// $special_code
										$line->array_options,	// $array_options
										$line->fk_unit,			// $fk_unit
										$pu_ht_devise,			// $pu_ht_devise
										1,						// $notrigger
										$line->ref_ext			// $ref_ext
										);
		}
		if ($element->element == 'facture') {
			return $element->updateline($line->id,					// $rowid
										$desc,						// $desc
										$pu_ht,						// $pu_ht
										1,							// $qty
										0,							// $remise_percent
										$line->date_start,			// $date_start
										$line->date_end,			// $date_end
										$line->tva_tx,				// $txtva
										$line->localtax1_tx,		// $txlocaltax1
										$line->localtax2_tx,		// $txlocaltax2
										'HT',						// $price_base_type
										$line->info_bits,			// $info_bits
										$line->product_type,		// $type
										$line->fk_parent_line,		// $fk_parent_line
										0,							// $skip_update_total
										$line->fk_fournprice,		// $fk_fournprice
										0,							// $pa_ht
										$line->label,				// $label
										$line->special_code,		// $special_code
										$line->array_options,		// $array_options
										$line->situation_percent,	// $situation_percent
										$line->fk_unit,				// $fk_unit
										$pu_ht_devise,				// $pu_ht_devise
										1,							// $notrigger
										$line->ref_ext				// $ref_ext
										);
		}
		return -1;	// Retourne une erreur si l'élément n'est pas reconnu
	}

 /**
	* Récupère les références des produits de remise depuis la configuration
	*
	* @return	array	Tableau avec les références 'product' et 'service'
	**/
	function infrasdiscount_getDiscountProductRefs()
	{
		global $db;

		$remProductId	= getDolGlobalString('INFRASDISCOUNT_PRODUCT_LINK_TO_DISCOUNT');
		$remServiceId	= getDolGlobalString('INFRASDISCOUNT_SERVICE_LINK_TO_DISCOUNT');
		$product		= new Product($db);
		$remProductRef	= '';
		$remServiceRef	= '';

		if ($remProductId > 0 && $product->fetch($remProductId) > 0) {
			$remProductRef	= $product->ref;
		}
		if ($remServiceId > 0 && $product->fetch($remServiceId) > 0) {
			$remServiceRef	= $product->ref;
		}

		return array('product' => $remProductRef, 'service' => $remServiceRef);
	}

	/**
	* Calcule la base en cascade pour une remise en pourcentage à une position donnée
	* La base est la somme de toutes les lignes AVANT la position de la remise (mode cascade)
	* Exclut les lignes du module subtotal ATM (titres, sous-totaux, textes libres)
	*
	* @param	CommonObject	$object				L'objet à traiter
	* @param	int				$position			Index de position dans le tableau $object->lines
	* @param	bool			$isProductDiscount	True si la remise s'applique aux produits, false pour les services
	* @param	string			$remProductRef		Référence de la ligne de remise produit
	* @param	string			$remServiceRef		Référence de la ligne de remise service
	* @return	float								Montant HT de base pour le calcul en cascade
	**/
	function infrasdiscount_calculateCascadeBase($object, $position, $isProductDiscount, $remProductRef, $remServiceRef)
	{
		$base	= 0;

		// Boucle uniquement sur les lignes AVANT la position actuelle (cascade)
		for ($i = 0; $i < $position; $i++) {
			$line	= $object->lines[$i];

			// Ignorer les lignes du module subtotal ATM (titres, sous-totaux, textes libres)
			if (infrasdiscount_isSubtotalLine($line)) {
				continue;
			}

			if ($isProductDiscount) {
				// Pour la remise produit : inclure toutes les lignes produit (type 0) sauf les références de remise service
				// empty() + !== : compatible toutes versions Dolibarr (product_ref peut être null ou '' pour les lignes libres)
				if ($line->product_type == 0 && (empty($remServiceRef) || $line->product_ref !== $remServiceRef)) {
					$base += $line->total_ht;
				}
			} else {
				// Pour la remise service : inclure toutes les lignes service (type 1) sauf les références de remise produit
				// empty() + !== : compatible toutes versions Dolibarr (product_ref peut être null ou '' pour les lignes libres)
				if ($line->product_type == 1 && (empty($remProductRef) || $line->product_ref !== $remProductRef)) {
					$base += $line->total_ht;
				}
			}
		}

		return $base;
	}

	/**
	 * Crée une ou plusieurs lignes de remise selon le type et les options
	 *
	 * @param	CommonObject	$object				L'objet (propal, commande, facture)
	 * @param	string			$remise_is			Type de remise : 'percent', 'amount', 'total_ttc'
	 * @param	int				$remise_type		1=produit, 2=service, 3=les deux
	 * @param	float			$remiseValue		Valeur de la remise
	 * @param	float			$remiseValueCurrency	Valeur en devise étrangère (si applicable)
	 * @param	array			$params				Paramètres additionnels (libelle, tva_tx, etc.)
	 * @return	int									< 0 on error, > 0 on success
	 */
	function infrasdiscount_createDiscountLines(&$object, $remise_is, $remise_type, $remiseValue, $remiseValueCurrency = 0, $params = array())
	{
		global $conf, $langs, $user;

		$langs->load('infrasdiscount@infrasdiscount');

		$refs			= infrasdiscount_getDiscountProductRefs();
		$remProductRef	= $refs['product'];
		$remServiceRef	= $refs['service'];

		$newRemiseIndex		= count($object->lines);

		// Calculer les bases pour produits et services
		$totalProductPrice	= infrasdiscount_calculateCascadeBase($object, $newRemiseIndex, true, $remProductRef, $remServiceRef);
		$totalServicePrice	= infrasdiscount_calculateCascadeBase($object, $newRemiseIndex, false, $remProductRef, $remServiceRef);

		// Définir les types à traiter selon remise_type
		$typesToProcess		= infrasdiscount_getTypesToProcess($remise_type, $totalProductPrice, $totalServicePrice);

		if (empty($typesToProcess) && $remise_is != 'total_ttc') {
			return 0; // Rien à traiter
		}

		// Dispatcher selon le type de remise
		switch ($remise_is) {
			case 'percent':
				return infrasdiscount_createPercentDiscount($object, $typesToProcess, $remiseValue, $params);

			case 'amount':
				return infrasdiscount_createAmountDiscount($object, $typesToProcess, $remiseValue, $remiseValueCurrency, $params);

			case 'total_ttc':
				return infrasdiscount_createTotalTTCDiscount($object, $remiseValue, $params);

			default:
				return -1;
		}
	}

	/**
	 * Retourne les types à traiter selon le remise_type
	 *
	 * @param	int		$remise_type		1=produit, 2=service, 3=les deux
	 * @param	float	$totalProductPrice	Total produits
	 * @param	float	$totalServicePrice	Total services
	 * @return	array						Liste des types à traiter
	 */
	function infrasdiscount_getTypesToProcess($remise_type, $totalProductPrice, $totalServicePrice)
	{
		$types	= array();

		if ($remise_type == 1 || $remise_type == 3) {
			if ($totalProductPrice != 0) {
				$types[]	= array(
					'type'			=> 0,		// product_type
					'label_key'		=> 'InfraSDiscountProductLabel',
					'label_prorata'	=> 'InfraSDiscountProductLabelProrata',
					'link_config'	=> 'INFRASDISCOUNT_PRODUCT_LINK_TO_DISCOUNT',
					'total'			=> $totalProductPrice
				);
			}
		}

		if ($remise_type == 2 || $remise_type == 3) {
			if ($totalServicePrice != 0) {
				$types[]	= array(
					'type'			=> 1,		// product_type
					'label_key'		=> 'InfraSDiscountServiceLabel',
					'label_prorata'	=> 'InfraSDiscountServiceLabelProrata',
					'link_config'	=> 'INFRASDISCOUNT_SERVICE_LINK_TO_DISCOUNT',
					'total'			=> $totalServicePrice
				);
			}
		}

		return $types;
	}

	/**
	 * Crée les lignes de remise en pourcentage
	 *
	 * @param	CommonObject	$object			L'objet
	 * @param	array			$typesToProcess	Types à traiter
	 * @param	float			$percent		Pourcentage de remise
	 * @param	array			$params			Paramètres additionnels
	 * @return	int								< 0 on error, > 0 on success
	 */
	function infrasdiscount_createPercentDiscount(&$object, $typesToProcess, $percent, $params)
	{
		global $langs;

		$libelle		= isset($params['libelle']) ? $params['libelle'] : '';
		$labelRemise	= isset($params['labelRemise']) ? $params['labelRemise'] : '';
		$tva_tx			= isset($params['tva_tx']) ? $params['tva_tx'] : 0;
		$localtax1_tx	= isset($params['localtax1_tx']) ? $params['localtax1_tx'] : 0;
		$localtax2_tx	= isset($params['localtax2_tx']) ? $params['localtax2_tx'] : 0;
		$array_options	= isset($params['array_options']) ? $params['array_options'] : array();

		$array_options['options_specialtype']	= 1; // discount percent

		foreach ($typesToProcess as $typeInfo) {
			$remise			= $typeInfo['total'] * $percent / 100;
			$descRemise		= $percent.' % - '.$langs->trans(!empty($libelle) ? $libelle : $labelRemise).' '.$langs->trans($typeInfo['label_key']);
			$remiseLinkTo	= getDolGlobalInt($typeInfo['link_config'], 0);

			$result	= infrasdiscount_addDiscountLine(
				$object,
				$descRemise,
				$remise,
				$typeInfo['type'],
				$tva_tx,
				$localtax1_tx,
				$localtax2_tx,
				$remiseLinkTo,
				$array_options
			);

			if ($result < 0) {
				setEventMessage($langs->trans('ErrorAddingDiscountLine'), 'errors');
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Crée les lignes de remise en montant
	 *
	 * @param	CommonObject	$object				L'objet
	 * @param	array			$typesToProcess		Types à traiter
	 * @param	float			$amount				Montant de remise
	 * @param	float			$amountCurrency		Montant en devise étrangère
	 * @param	array			$params				Paramètres additionnels
	 * @return	int									< 0 on error, > 0 on success
	 */
	function infrasdiscount_createAmountDiscount(&$object, $typesToProcess, $amount, $amountCurrency, $params)
	{
		global $conf, $langs;

		$libelle		= isset($params['libelle']) ? $params['libelle'] : '';
		$labelRemise	= isset($params['labelRemise']) ? $params['labelRemise'] : '';
		$tva_tx			= isset($params['tva_tx']) ? $params['tva_tx'] : 0;
		$localtax1_tx	= isset($params['localtax1_tx']) ? $params['localtax1_tx'] : 0;
		$localtax2_tx	= isset($params['localtax2_tx']) ? $params['localtax2_tx'] : 0;
		$array_options	= isset($params['array_options']) ? $params['array_options'] : array();

		$is_multicurrency	= infrasdiscount_multicurrency_enabled($object);

		// Vérifier si les deux devises sont renseignées (erreur)
		if ($is_multicurrency && !empty($amountCurrency) && !empty($amount)) {
			setEventMessage($langs->trans('InfraSDiscountBothCurrencies',
				price($amountCurrency, 0, $langs, 0, -1, -1, $object->multicurrency_code),
				price($amount, 0, $langs, 0, -1, -1, $conf->currency)
			), 'errors');
			return -1;
		}

		// Préparer les montants
		$preparedPrices	= infrasdiscount_prepare_prices($amount, $amountCurrency, $object);
		$pu_ht			= $preparedPrices['pu_ht'];
		$pu_ht_devise	= $preparedPrices['pu_ht_devise'];

		// Si un seul type à traiter : montant fixe simple
		if (count($typesToProcess) == 1) {
			$typeInfo		= $typesToProcess[0];
			$descRemise		= $langs->trans(!empty($libelle) ? $libelle : $labelRemise).' '.$langs->trans($typeInfo['label_key']);
			$remiseLinkTo	= getDolGlobalInt($typeInfo['link_config'], 0);

			$array_options['options_specialtype']	= 2; // discount amount

			return infrasdiscount_addDiscountLine(
				$object,
				$descRemise,
				$pu_ht,
				$typeInfo['type'],
				$tva_tx,
				$localtax1_tx,
				$localtax2_tx,
				$remiseLinkTo,
				$array_options,
				$pu_ht_devise
			);
		}

		// Plusieurs types : répartition prorata
		return infrasdiscount_createProrataDiscount($object, $typesToProcess, $pu_ht, $pu_ht_devise, $params);
	}

	/**
	 * Crée les lignes de remise prorata (répartition entre produits et services)
	 *
	 * @param	CommonObject	$object				L'objet
	 * @param	array			$typesToProcess		Types à traiter
	 * @param	float			$totalAmount		Montant total de remise
	 * @param	float			$totalAmountDevise	Montant total en devise étrangère
	 * @param	array			$params				Paramètres additionnels
	 * @return	int									< 0 on error, > 0 on success
	 */
	function infrasdiscount_createProrataDiscount(&$object, $typesToProcess, $totalAmount, $totalAmountDevise, $params)
	{
		global $conf, $langs;

		$libelle		= isset($params['libelle']) ? $params['libelle'] : '';
		$labelRemise	= isset($params['labelRemise']) ? $params['labelRemise'] : '';
		$tva_tx			= isset($params['tva_tx']) ? $params['tva_tx'] : 0;
		$localtax1_tx	= isset($params['localtax1_tx']) ? $params['localtax1_tx'] : 0;
		$localtax2_tx	= isset($params['localtax2_tx']) ? $params['localtax2_tx'] : 0;
		$array_options	= isset($params['array_options']) ? $params['array_options'] : array();

		$is_multicurrency	= infrasdiscount_multicurrency_enabled($object);

		// Calculer le total de la base
		$totalBase	= 0;
		foreach ($typesToProcess as $typeInfo) {
			$totalBase	+= $typeInfo['total'];
		}

		if ($totalBase == 0) {
			return 0;
		}

		// Calculer le taux de répartition
		$remiseRate	= $totalAmount / $totalBase;

		// Construire la description de base
		if ($is_multicurrency && !empty($totalAmountDevise)) {
			$descBase	= price($totalAmountDevise, 0, $langs, 0, -1, -1, $object->multicurrency_code).' / '.
						  price($totalAmount, 0, $langs, 0, -1, -1, 'auto').' - '.
						  $langs->trans(!empty($libelle) ? $libelle : $labelRemise);
		} else {
			$descBase	= price($totalAmount, 0, $langs, 1, -1, -1, $conf->currency).' - '.
						  $langs->trans(!empty($libelle) ? $libelle : $labelRemise);
		}

		$array_options['options_specialtype']	= 3; // discount amount prorata

		foreach ($typesToProcess as $typeInfo) {
			$remise			= round($typeInfo['total'] * $remiseRate, 2, PHP_ROUND_HALF_UP);
			$remiseDevise	= $is_multicurrency ? infrasdiscount_to_foreign($remise, $object) : 0;

			$descRemise		= $descBase.' '.$langs->trans($typeInfo['label_prorata']);
			$remiseLinkTo	= getDolGlobalInt($typeInfo['link_config'], 0);

			$result	= infrasdiscount_addDiscountLine(
				$object,
				$descRemise,
				$remise,
				$typeInfo['type'],
				$tva_tx,
				$localtax1_tx,
				$localtax2_tx,
				$remiseLinkTo,
				$array_options,
				$remiseDevise
			);

			if ($result < 0) {
				setEventMessage($langs->trans('ErrorAddingDiscountLine'), 'errors');
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Crée les lignes de remise pour atteindre un total TTC cible
	 *
	 * @param	CommonObject	$object			L'objet
	 * @param	float			$targetTTC		Montant TTC cible
	 * @param	array			$params			Paramètres additionnels
	 * @return	int								< 0 on error, > 0 on success
	 */
	function infrasdiscount_createTotalTTCDiscount(&$object, $targetTTC, $params)
	{
		global $conf, $langs, $user;

		require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

		$libelle		= isset($params['libelle']) ? $params['libelle'] : '';
		$labelRemise	= isset($params['labelRemise']) ? $params['labelRemise'] : '';
		$localtax1_tx	= isset($params['localtax1_tx']) ? $params['localtax1_tx'] : 0;
		$localtax2_tx	= isset($params['localtax2_tx']) ? $params['localtax2_tx'] : 0;
		$array_options	= isset($params['array_options']) ? $params['array_options'] : array();

		// Vérification du montant cible
		if ($targetTTC <= 0) {
			setEventMessage($langs->trans('InfraSDiscountErrorInvalidTargetAmount'), 'errors');
			return -1;
		}

		// Analyser les lignes existantes
		$analysis	= infrasdiscount_analyzeLinesForTotalTTC($object);

		if (empty($analysis['groups'])) {
			setEventMessage($langs->trans('InfraSDiscountErrorNoLines'), 'errors');
			return -1;
		}

		$currentTotalTTC	= $analysis['total_ttc'];
		$discountTTCNeeded	= $currentTotalTTC - $targetTTC;

		if ($discountTTCNeeded <= 0) {
			setEventMessage($langs->trans('InfraSDiscountErrorAmountToHigh', $object->element), 'errors');
			return -1;
		}

		// Vérifier la marge
		$formmargin			= new FormMargin($object->db);
		$marginInfos		= $formmargin->getMarginInfosArray($object, false);
		$discountHTApprox	= $discountTTCNeeded / 1.2;

		if ($discountHTApprox > $marginInfos['total_margin'] &&
			(!getDolGlobalBool('MAIN_USE_ADVANCED_PERMS') || !$user->hasRight('produit', 'ignore_price_min_advance'))) {
			setEventMessage($langs->trans('marginError', $object->element), 'errors');
			return -1;
		}

		// Calculer les lignes de remise avec correction des arrondis
		$discountLines	= infrasdiscount_calculateTTCDiscountLines($analysis['groups'], $discountTTCNeeded, $currentTotalTTC);

		// Créer les lignes
		$array_options['options_specialtype']	= 4; // discount total ttc

		foreach ($discountLines as $discountLine) {
			$typeLabel	= ($discountLine['product_type'] == 0) ? 'InfraSDiscountProductLabel' : 'InfraSDiscountServiceLabel';
			$descRemise	= $langs->trans(!empty($libelle) ? $libelle : $labelRemise).' '.
						  $langs->trans($typeLabel).'/'.$langs->trans('Arrondi').' '.
						  price($targetTTC, 0, $langs, 1, -1, -1, 'auto').$langs->trans(' TTC');

			$linkConfig		= ($discountLine['product_type'] == 0) ? 'INFRASDISCOUNT_PRODUCT_LINK_TO_DISCOUNT' : 'INFRASDISCOUNT_SERVICE_LINK_TO_DISCOUNT';
			$remiseLinkTo	= getDolGlobalInt($linkConfig, 0);

			$pu_ht_devise	= 0;
			if (infrasdiscount_multicurrency_enabled($object)) {
				$pu_ht_devise	= infrasdiscount_to_foreign($discountLine['ht'], $object);
			}

			$result	= infrasdiscount_addDiscountLine(
				$object,
				$descRemise,
				$discountLine['ht'],
				$discountLine['product_type'],
				$discountLine['vat'],
				$localtax1_tx,
				$localtax2_tx,
				$remiseLinkTo,
				$array_options,
				$pu_ht_devise
			);

			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Analyse les lignes pour le calcul total TTC
	 *
	 * @param	CommonObject	$object		L'objet
	 * @return	array						Données d'analyse
	 */
	function infrasdiscount_analyzeLinesForTotalTTC(&$object)
	{
		$currentTotalTTC	= 0;
		$groups				= array();

		foreach ($object->lines as $line) {
			// Ignorer les lignes subtotal ATM
			if (infrasdiscount_isSubtotalLine($line)) {
				continue;
			}

			// Ignorer les lignes de remise type 4 (total_ttc)
			if (isset($line->array_options['options_specialtype']) && $line->array_options['options_specialtype'] == 4) {
				continue;
			}

			$currentTotalTTC	+= $line->total_ttc;
			$vatindex			= $line->tva_tx;
			$typeKey			= ($line->product_type == 0 ? 'prod_' : 'serv_').$vatindex;

			if (!isset($groups[$typeKey])) {
				$groups[$typeKey]	= array(
					'tva'			=> $vatindex,
					'total_ttc'		=> 0,
					'total_ht'		=> 0,
					'product_type'	=> $line->product_type
				);
			}

			$groups[$typeKey]['total_ttc']	+= $line->total_ttc;
			$groups[$typeKey]['total_ht']	+= $line->total_ht;
		}

		return array(
			'total_ttc'	=> $currentTotalTTC,
			'groups'	=> array_values($groups)
		);
	}

	/**
	 * Calcule les lignes de remise TTC avec correction des arrondis
	 *
	 * @param	array	$groups				Groupes de lignes par type/TVA
	 * @param	float	$discountTTCNeeded	Montant TTC de remise nécessaire
	 * @param	float	$currentTotalTTC	Total TTC actuel
	 * @return	array						Lignes de remise calculées
	 */
	function infrasdiscount_calculateTTCDiscountLines($groups, $discountTTCNeeded, $currentTotalTTC)
	{
		$discountLines				= array();
		$totalDiscountTTCCalculated	= 0;

		// Étape 1 : Calculer les remises HT théoriques
		foreach ($groups as $group) {
			$vatRate		= $group['tva'];
			$vatMultiplier	= 1 + ($vatRate / 100);
			$proportion		= $group['total_ttc'] / $currentTotalTTC;

			$discountTTCForGroup	= $discountTTCNeeded * $proportion;
			$discountHTForGroup		= $discountTTCForGroup / $vatMultiplier;
			$discountHTRounded		= round($discountHTForGroup, 2, PHP_ROUND_HALF_UP);
			$discountTTCRounded		= round($discountHTRounded * $vatMultiplier, 2, PHP_ROUND_HALF_UP);

			$discountLines[]	= array(
				'ht'				=> $discountHTRounded,
				'ttc'				=> $discountTTCRounded,
				'vat'				=> $vatRate,
				'vat_multiplier'	=> $vatMultiplier,
				'product_type'		=> $group['product_type']
			);

			$totalDiscountTTCCalculated	+= $discountTTCRounded;
		}

		// Étape 2 : Correction des arrondis
		$ttcDifference	= round($discountTTCNeeded - $totalDiscountTTCCalculated, 2, PHP_ROUND_HALF_UP);

		if (abs($ttcDifference) >= 0.01) {
			usort($discountLines, function($a, $b) {
				return $b['ttc'] <=> $a['ttc'];
			});

			$remainingDifference	= $ttcDifference;
			foreach ($discountLines as &$line) {
				if (abs($remainingDifference) < 0.01) {
					break;
				}

				$adjustment			= ($remainingDifference > 0) ? 0.01 : -0.01;
				$nbAdjustments		= min(abs(round($remainingDifference / 0.01)), 10);
				$totalAdjustment	= $adjustment * $nbAdjustments;

				$line['ttc']			= round($line['ttc'] + $totalAdjustment, 2, PHP_ROUND_HALF_UP);
				$line['ht']				= round($line['ttc'] / $line['vat_multiplier'], 2, PHP_ROUND_HALF_UP);
				$remainingDifference	= round($remainingDifference - $totalAdjustment, 2, PHP_ROUND_HALF_UP);
			}
			unset($line);
		}

		return $discountLines;
	}

	/**
	* Recalcule toutes les remises en pourcentage en mode cascade
	* Doit être appelé lorsque les lignes sont déplacées ou que les prix changent
	*
	* @param	CommonObject	$object		L'objet à traiter
	* @return	int							< 0 en cas d'erreur, >= 0 nombre de lignes recalculées
	**/
	function infrasdiscount_recalculatePercentDiscounts(&$object)
	{
		global $conf, $langs;

		// Vérifie si le recalcul automatique est activé
		if (!getDolGlobalInt('INFRASDISCOUNT_AUTO_RECALCULATE', 1)) {
			return 0;
		}

		$refs			= infrasdiscount_getDiscountProductRefs();
		$remProductRef	= $refs['product'];
		$remServiceRef	= $refs['service'];
		$recalculated	= 0;

		// Parcourt toutes les lignes pour trouver les remises en pourcentage
		foreach ($object->lines as $idx => $line) {
			// Vérifie si c'est une remise en pourcentage (specialtype = 1)
			if (isset($line->array_options['options_specialtype']) && $line->array_options['options_specialtype'] == 1) {
				// Extrait le pourcentage de la description
				$percentValue	= 0;
				if (preg_match('/([\d\.,]+)\s*%/', $line->desc, $matches)) {
					$percentValue	= price2num($matches[1], 'CU', 2);
				}

				if ($percentValue == 0) {
					continue; // Passe si aucun pourcentage valide trouvé
				}

				// Détermine si c'est une remise produit ou service
				$isProductDiscount	= ($line->product_type == 0);

				// Calcule la nouvelle base en utilisant la logique en cascade
				$base	= infrasdiscount_calculateCascadeBase($object, $idx, $isProductDiscount, $remProductRef, $remServiceRef);

				// Calcule le nouveau montant de la remise
				$newAmount	= round($base * $percentValue / 100, 2, PHP_ROUND_HALF_UP);

				// Vérifie si le montant a changé (évite les mises à jour inutiles)
				if (abs(abs($line->total_ht) - $newAmount) > 0.01) {
					// Met à jour la ligne
					$pu_ht_devise		= 0;
					if (infrasdiscount_multicurrency_enabled($object)) {
						$pu_ht_devise	= infrasdiscount_to_foreign($newAmount, $object);
					}
					$result	= infrasdiscount_updateRemLine($object, $line, $newAmount, $pu_ht_devise);
					if ($result < 0) {
						setEventMessages($langs->trans('InfraSDiscountErrorRecalculating').' '.$line->desc, null, 'errors');
						return -1;
					}
					$recalculated++;
				}
			}
		}

		// Recharge l'objet pour obtenir les totaux mis à jour
		if ($recalculated > 0) {
			$object->fetch($object->id);
		}

		return $recalculated;
	}

	/**
	 * Recalcule toutes les remises prorata en mode cascade
	 * Doit être appelé lorsque les lignes sont déplacées, que les prix changent ou que les lignes sont supprimées
	 * Chaque paire prorata calcule uniquement sur les lignes AU-DESSUS d'elle (cascade)
	 *
	 * @param	CommonObject	$object		L'objet à traiter
	 * @return	int							< 0 en cas d'erreur, >= 0 nombre de paires recalculées
	 **/
	function infrasdiscount_recalculateProrataDiscounts(&$object)
	{
		global $conf, $langs;

		// Vérifie si le recalcul automatique est activé
		if (!getDolGlobalInt('INFRASDISCOUNT_AUTO_RECALCULATE', 1)) {
			return 0;
		}
		$refs			= infrasdiscount_getDiscountProductRefs();
		$remProductRef	= $refs['product'];
		$remServiceRef	= $refs['service'];
		$recalculated	= 0;

		// 1. Trouve et regroupe les paires prorata avec leurs positions
		$prorataGroups	= array();
		$currentPair	= array();
		$pairTotal		= 0;
		$startPosition	= -1;

		foreach ($object->lines as $idx => $line) {
			if (isset($line->array_options['options_specialtype']) && $line->array_options['options_specialtype'] == 3) {
				// Marque la position de départ de la paire prorata (position de la première ligne)
				if (count($currentPair) == 0) {
					$startPosition = $idx;
				}
				$currentPair[]	= array('line' => $line, 'index' => $idx);
				$pairTotal		+= abs($line->total_ht);
				if (count($currentPair) == 2) {
					$prorataGroups[]	= array(
						'lines'			=> $currentPair,
						'total'			=> $pairTotal,
						'position'		=> $startPosition  // Position de la première ligne prorata
					);
					$currentPair		= array();
					$pairTotal			= 0;
					$startPosition		= -1;
				}
			}
		}
		// 2. Recalcule chaque paire prorata en fonction des lignes AU-DESSUS d'elle (mode cascade)
		foreach ($prorataGroups as $group) {
			$totalProductPrice	= 0;
			$totalServicePrice	= 0;
			$prorataPosition	= $group['position'];
			// Calcule les montants de base à partir des lignes AU-DESSUS de cette paire prorata (cascade)
			for ($i = 0; $i < $prorataPosition; $i++) {
				$line			= $object->lines[$i];
				// Ignore les lignes du module subtotal ATM (titres, sous-totaux, textes libres)
				if (infrasdiscount_isSubtotalLine($line)) {
					continue;
				}
				// Ignore les lignes des modules externes
				$searchNames	= array();
				if (isModEnabled('subtotal')) $searchNames[]	= 'modSubtotal';
				if (isModEnabled('milestone')) $searchNames[]	= 'modMilestone';
				if (isModEnabled('ouvrage')) $searchNames[]		= 'modOuvrage';
				if (!empty($searchNames) && infrasdiscount_isLineFromExternalModule($line, $object->element, $searchNames)) {
					continue;
				}
				// Ignore les autres lignes prorata (type 3) et les lignes total_ttc (type 4)
				// INCLUT les lignes normales ET les remises en pourcentage (type 1) ET les remises en montant (type 2)
				if (isset($line->array_options['options_specialtype']) && in_array($line->array_options['options_specialtype'], [3, 4])) {
					continue; // Ignore les remises prorata et total_ttc
				}
				// Somme les produits et services (y compris les remises de type 1 et 2)
				if ($line->product_type == 0) {
					$totalProductPrice += $line->total_ht;
				} elseif ($line->product_type == 1) {
					$totalServicePrice += $line->total_ht;
				}
			}
			// 3. Obtient le total de la remise actuelle pour cette paire
			$currentDiscountTotal	= $group['total'];
			// 4. Calcule la nouvelle distribution
			$remiseProrataBase		= $totalProductPrice + $totalServicePrice;
			if ($remiseProrataBase == 0) {
				continue; // Évite la division par zéro
			}
			$remiseRate		= $currentDiscountTotal / $remiseProrataBase;
			$remiseProduct	= round($totalProductPrice * $remiseRate, 2, PHP_ROUND_HALF_UP);
			$remiseService	= round($totalServicePrice * $remiseRate, 2, PHP_ROUND_HALF_UP);
			// 5. Met à jour chaque ligne de la paire
			foreach ($group['lines'] as $lineData) {
				$line			= $lineData['line'];
				$newAmount		= ($line->product_type == 0) ? $remiseProduct : $remiseService;
				// Vérifie si le montant a changé
				if (abs(abs($line->total_ht) - $newAmount) > 0.01) {
					$pu_ht_devise	= 0;
					if (infrasdiscount_multicurrency_enabled($object)) {
						$pu_ht_devise	= infrasdiscount_to_foreign($newAmount, $object);
					}
					// Mise à jour de la description pour refléter le nouveau montant
					$desc		= $line->desc;
					if (infrasdiscount_multicurrency_enabled($object)) {
						// Format: "montant_devise / montant_base symbole - reste"
						$desc	= preg_replace_callback(
							'/([\d\s.,]+)\s*\/\s*([\d\s.,]+)\s*([^\d\s]+)(.*)$/u',
							function ($matches) use ($pu_ht_devise, $newAmount, $object) {
								$newValueCurrency = price(abs($pu_ht_devise), 0, '', 1, -1, -1, ' ');
								$newValueBase = price(abs($newAmount), 0, '', 1, -1, -1, ' ');
								return $newValueCurrency.' / '.$newValueBase.$matches[3].$matches[4];
							},
							$desc
						);
					} else {
						// Pas de multidevise : remplacer uniquement le montant au début
						$desc	= preg_replace_callback(
							'/^([^\d\s-]*)([\d\s.,]+)(.*)$/u',
							function ($matches) use ($newAmount) {
								$newValue = price(abs($newAmount), 0, '', 1, -1, -1, ' ');
								return $matches[1].$newValue.$matches[3];
							},
							$desc
						);
					}
					$result		= infrasdiscount_updateRemLine($object, $line, $newAmount, $pu_ht_devise, $desc);
					if ($result < 0) {
						setEventMessages($langs->trans('InfraSDiscountErrorRecalculating').' '.$line->desc, null, 'errors');
						return -1;
					}

					$recalculated++;
				}
			}
		}
		// Recharge l'objet pour obtenir les totaux mis à jour
		if ($recalculated > 0) {
			$object->fetch($object->id);
		}
		return $recalculated;
	}
