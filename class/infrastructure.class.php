<?php
	/***************************************************
	* Copyright (C) 2025 ATM Consulting <support@atm-consulting.fr>
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
    *
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program. If not, see <https://www.gnu.org/licenses/>.
	***************************************************/

	/***************************************************
	* \file		./infrastructure/class/infrastructure.class.php
	* \ingroup	InfraS
	* \brief	Class to manage Infrastructure module
	***************************************************/

	// Libraries *************************
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

	class TInfrastructure
	{
		/** @var int|null Cache du numéro du module (lu depuis modInfrastructure->numero) */
		public static $module_number = null;

		/**
		*	Retourne le numéro du module lu depuis le descripteur modInfrastructure.
		*	Mis en cache en propriété statique à la première lecture.
		*
		*	@return	int
		**/
		public static function getModuleNumber()
		{
			if (self::$module_number === null) {
				global $db;
				dol_include_once('/infrastructure/core/modules/modInfrastructure.class.php');
				$mod					= new modInfrastructure($db);
				self::$module_number	= (int) $mod->numero;
			}
			return self::$module_number;
		}

		/**
		* Init infrastructure qty list by level
		*
		* @param   CommonObject    $object     Object
		* @param   int             $level      [=0] Sub-total level
		*/
		static function initInfrastructureQtyForObject($object, $level = 0)
		{
			if (!isset($object->TInfrastructureQty)) {
				$object->TInfrastructureQty = array();
			}
			if (!isset($object->TInfrastructureQty[$level])) {
				$object->TInfrastructureQty[$level] = 0;
			}
		}

		/**
		* Set infrastructure quantity in list by level
		*
		* @param   CommonObject    $object Object
		* @param   int             $level  Infrastructure level
		* @param   int             $qty    [=0] Infrastructure qty
		*/
		static function setInfrastructureQtyForObject($object, $level, $qty = 0)
		{
			self::initInfrastructureQtyForObject($object, $level);
			$object->TInfrastructureQty[$level] = $qty;
		}

		/**
		* Add infrastructure quantity in list by level
		*
		* @param   CommonObject    $object Object
		* @param   int             $level  Infrastructure level
		* @param   int             $qty    [=0] Infrastructure qty
		*/
		static function addInfrastructureQtyForObject($object, $level, $qty = 0)
		{
			self::initInfrastructureQtyForObject($object, $level);
			$object->TInfrastructureQty[$level] += $qty;
		}

		/**
		* Determine to show infrastructure line qty by default for this object
		*
		* @param	CommonObject	$object		Object
		* @param	string			$context	'screen' (défaut) ou 'pdf'. En contexte 'pdf' la constante INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS_PDF prime ; si vide, fallback sur la constante écran (compat. installations existantes).
		* @return	bool						False no show infrastructure qty for this object else True
		*/
		static function showQtyForObject($object, $context = 'screen')
		{
			$show = false;
			if ($context === 'pdf') {
				$value	= getDolGlobalString('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS_PDF');
			} else {
				$value	= getDolGlobalString('INFRASTRUCTURE_DEFAULT_DISPLAY_QTY_FOR_INFRASTRUCTURE_ON_ELEMENTS');
			}
			if ($value !== '' && in_array($object->element, explode(',', $value))) {
				$show	= true;
			}
			return $show;
		}

		/**
		* Determine to show infrastructure line qty by default for this object line
		*
		* @param   object  $line               Object line
		* @param   bool    $show_by_default    [=false] Not to show by default
		* @return  bool    False no show infrastructure qty for this object line else True
		*/
		static function showQtyForObjectLine($line, $show_by_default = false)
		{
			if ($show_by_default === false) {
				$line_show_qty = false;
				if (isset($line->array_options['options_infrastructure_show_qty']) && $line->array_options['options_infrastructure_show_qty'] > 0) {
					$line_show_qty = true;
				}
			} else {
				$line_show_qty = true;
				if (isset($line->array_options['options_infrastructure_show_qty']) && $line->array_options['options_infrastructure_show_qty'] < 0) {
					$line_show_qty = false;
				}
			}
			return $line_show_qty;
		}

		/**
		* Permet d'ajouter une ligne de sous-total ou de titre à un document (propal, commande, facture, etc...)n'est pas appelé lors de la  de facture depuis un object (propal/command)
		*
		* @param	CommonObject $object Document on which we want to add a infrastructure line
		* @param	string       $label	 Label of line
		* @param	int          $qty	 Quantity to put on line (used to determine the level of the title or infrastructure line, for example qty 1 for a title of level 1, qty 2 for a title of level 2, etc... and inversely for infrastructure line with qty 99 for a infrastructure of level 1, qty 98 for a infrastructure of level 2, etc...)
		* @param	int          $rang	 Rang where to add line (if -1 add at the end of document)
		* @param	string       $desc	 Description of line (only used for facture and propal for the moment, not used for supplier invoice and supplier propal)
		* @return	int
		*/
		static function addInfrastructureLine(&$object, $label, $qty, $rang=-1, $desc = '')
		{
			$res	= 0;
			$desc	= '';
			$TNotElements = array ('invoice_supplier', 'order_supplier');
			if ($qty == 50 && !in_array($object->element, $TNotElements)) {
				$desc	= $label;
				$label	= '';
			}
			if ($object->element=='facture') {
				$res	=  $object->addline($desc, 0, $qty, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT', 0, 9, $rang, TInfrastructure::getModuleNumber(), '', 0, 0, null, 0, $label);
			} elseif ($object->element=='invoice_supplier') {
				$object->special_code	= TInfrastructure::getModuleNumber();
				$res					= $object->addline($label, 0, 0, 0, 0, $qty, 0, 0, 0, 0, 0, 0, 'HT', 9, $rang, false, array(), null, 0, 0, '', TInfrastructure::getModuleNumber());
			} elseif ($object->element=='propal') {
				$res	= $object->addline($desc, 0, $qty, 0, 0, 0, 0, 0, 'HT', 0, 0, 9, $rang, TInfrastructure::getModuleNumber(), 0, 0, 0, $label);
			} elseif ($object->element=='supplier_proposal') {
				$res	= $object->addline($desc, 0, $qty, 0, 0, 0, 0, 0, 'HT', 0, 0, 9, $rang, TInfrastructure::getModuleNumber(), 0, 0, 0, $label);
			} elseif ($object->element=='commande') {
				$res	=  $object->addline($desc, 0, $qty, 0, 0, 0, 0, 0, 0, 0, 'HT', 0, '', '', 9, $rang, TInfrastructure::getModuleNumber(), 0, null, 0, $label);
			} elseif ($object->element=='order_supplier') {
				$object->special_code	= TInfrastructure::getModuleNumber(); // à garder pour la rétrocompatibilité
				$res					= $object->addline($label, 0, $qty, 0, 0, 0, 0, 0, '', 0, 'HT', 0, 9, 0, false, null, null, 0, null, 0, '', 0, -1, TInfrastructure::getModuleNumber());
			} elseif ($object->element=='facturerec') {
				$res =  $object->addline($desc, 0, $qty, 0, 0, 0, 0, 0, 'HT', 0, '', 0, 9, $rang, TInfrastructure::getModuleNumber(), $label);
			}
			self::generateDoc($object);
			return $res;
		}

		/**
		* @param CommonObject $object
		*/
		public static function generateDoc(&$object)
		{
			global $conf, $langs;

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$hidedetails	= (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
				$hidedesc		= (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
				$hideref		= (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));
				$outputlangs	= $langs;
				$newlang		= GETPOST('lang_id', 'alpha');
				if (getDolGlobalString('MAIN_MULTILANGS') && empty($newlang))
					$newlang	= !empty($object->client) ? $object->client->default_lang : $object->thirdparty->default_lang;
				if (!empty($newlang)) {
					$outputlangs	= new Translate('', $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$object->fetch($object->id); // Reload to get new records
				if ($object->element!= 'facturerec') {
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}
		}

		/**
		* Permet de mettre à jour les rangs afin de décaler des lignes pour une insertion en milieu de document
		*
		* @param	CommonObject	$object		Document on which we want to update line ranks
		* @param	int				$rang_start	Rang à partir duquel on veut faire le décalage (le rang de la ligne que l'on veut insérer)
		* @param	int				$move_to	Nombre de rang à décaler (exemple : 1 pour faire de la place pour une ligne, -1 pour refermer un espace laissé par une ligne supprimé, etc...)
		 * @return void
		*/
		public static function updateRang(&$object, $rang_start, $move_to = 1)
		{
			if (!class_exists('GenericObject')) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
			}

			$row						= new GenericObject($object->db);
			$row->table_element_line	= $object->table_element_line;
			$row->fk_element			= $object->fk_element;
			$row->id					= $object->id;
			foreach ($object->lines as &$line) {
				if ($line->rang < $rang_start) {
					continue;
				}
				$row->updateRangOfLine($line->id, $line->rang+$move_to);
			}
		}

		/**
		* Méthode qui se charge de faire les ajouts de sous-totaux manquant afin de fermer les titres ouvert lors de l'ajout d'un nouveau titre
		*
		* @param	CommonObject	$object				Document on which we want to add missing infrastructure lines
		* @param	int				$level_new_title	Niveau du nouveau titre
		*/
		public static function addInfrastructureMissing(&$object, $level_new_title)
		{
			global $langs;

			$TTitle			= self::getAllTitleWithoutTotalFromDocument($object);
			$TTitle_reverse	= array_reverse($TTitle);	// Reverse - Pour partir de la fin et remonter dans les titres pour me permettre de m'arrêter quand je trouve un titre avec un niveau inférieur à celui qui a était ajouté
			foreach ($TTitle_reverse as $k => $title_line) {
				$title_niveau	= self::getNiveau($title_line);
				if ($title_niveau < $level_new_title) {
					break;
				}
				$rang_to_add	= self::titleHasTotalLine($object, $title_line, true, true);
				if (is_numeric($rang_to_add)) {
					if ($rang_to_add != -1) {
						self::updateRang($object, $rang_to_add);
					}
					self::addInfrastructureLine($object, $langs->trans('Infrastructure'), 100-$title_niveau, $rang_to_add);
					if (method_exists($object, 'fetch_lines')) {
						$object->fetch_lines();
					} else {
						$object->fetch($object->id);
					}
				}
			}
		}

		/**
		* @param CommonObject $object	Document on which we want to add a title line
		* @param string       $label	Label of title line
		* @param int          $level	Level of title line (between 1 and 4 for example, but you can use the level you want just to be able to determine the hierarchy between titles and infrastructures)
		* @param int          $rang		Rang where to add line (if -1 add at the end of document)
		* @param string       $desc		Description of line (only used for facture and propal for the moment, not used for supplier invoice and supplier propal)
		* @return int
		*/
		public static function addTitle(&$object, $label, $level, $rang = -1, $desc = '')
		{
			return self::addInfrastructureLine($object, $label, $level, $rang, $desc);
		}

		/**
		* @param	CommonObject	$object	Document on which we want to add a infrastructure line
		* @param	string			$label	Label of infrastructure line
		* @param	int				$level	Level of infrastructure line (between 1 and 4 for example, but you can use the level you want just to be able to determine the hierarchy between titles and infrastructures, for example if you have a title of level 1 then you can put a infrastructure of level 1 with this method, if you have a title of level 2 then you can put a infrastructure of level 2 with this method, etc...)
		* @param	int				$rang	Rang where to add line (if -1 add at the end of document)
		* @return	int
		*/
		public static function addTotal(&$object, $label, $level, $rang = -1)
		{
			return self::addInfrastructureLine($object, $label, (100-$level), $rang);
		}

		/**
		* Récupère la liste des lignes de titre qui n'ont pas de sous-total
		*
		* @param	Propal|Commande|Facture				$object				Document on which we want to get title lines without total line
		* @param	boolean								$get_block_total	Whether to get the total block for each title (if true then for each title line we will have the total of the block it represents, if false we will just get title lines without total lines)
		*
		* @return array
		*/
		public static function getAllTitleWithoutTotalFromDocument(&$object, $get_block_total = false)
		{
			$TTitle = self::getAllTitleFromDocument($object, $get_block_total);

			foreach ($TTitle as $k => $title_line) {
				if (self::titleHasTotalLine($object, $title_line)) unset($TTitle[$k]);
			}

			return $TTitle;
		}

		/**
		* Est-ce que mon titre ($title_line) a un sous-total ?
		*
		* @param	Propal|Commande|Facture				$object					Document on which we want to check if title has total line
		* @param	PropaleLigne|OrderLine|FactureLigne	$title_line				Title line we want to check
		* @param	boolean								$strict_mode			si true alors un titre doit avoir un sous-total de même niveau; si false un titre possède un sous-total à partir du moment où l'on trouve un titre de niveau égale ou inférieur
		* @param	boolean								$return_rang_on_false	si true alors renvoi le rang où devrait ce trouver le sous-total
		* @return	boolean
		*/
		public static function titleHasTotalLine(&$object, &$title_line, $strict_mode = false, $return_rang_on_false = false)
		{
			if (empty($object->lines) || !is_array($object->lines)) return false;

			$title_niveau	= self::getNiveau($title_line);
			foreach ($object->lines as &$line) {
				if ($line->rang <= $title_line->rang) continue;
				if (self::isTitle($line) && self::getNiveau($line) <= $title_niveau) return false; // Oups on croise un titre d'un niveau inférieur ou égale (exemple : je croise un titre niveau 2 alors que je suis sur un titre de niveau 3) pas lieu de continuer car un nouveau bloc commence
				if (!self::isTotal($line)) continue;
				$infrastructure_niveau	= self::getNiveau($line);
				// Comparaison du niveau de la ligne de sous-total avec celui du titre
				if ($infrastructure_niveau == $title_niveau) {
					return true;												// niveau égale => Ok mon titre a un sous-total
				} elseif ($infrastructure_niveau < $title_niveau) {					// niveau inférieur trouvé (exemple : sous-total de niveau 1 contre mon titre de niveau 3)
					if ($strict_mode) {
						return ($return_rang_on_false) ? $line->rang : false;	// mode strict niveau pas égale donc faux
					} else {
						return true;											// mode libre => OK je considère que mon titre à un sous-total
					}
				}
			}
			// Sniff, j'ai parcouru toutes les lignes et pas de sous-total pour ce titre
			return ($return_rang_on_false) ? -1 : false;
		}

		/**
		* @param	CommonObject	$object				Document on which we want to get all title lines
		* @param	boolean			$get_block_total	Whether to get the total block for each title
		* @return	array
		*/
		public static function getAllTitleFromDocument(&$object, $get_block_total = false)
		{
			$TRes = array();
			if (!empty($object->lines)) {
				foreach ($object->lines as $k => &$line) {
					if (self::isTitle($line)) {
						if ($get_block_total) {
							$TTot							= self::getTotalBlockFromTitle($object, $line);
							$line->total_pa_ht				= $TTot['total_pa_ht'];
							$line->total_options			= $TTot['total_options'];
							$line->total_ht					= $TTot['total_ht'];
							$line->total_tva				= $TTot['total_tva'];
							$line->total_ttc				= $TTot['total_ttc'];
							$line->TTotal_tva				= $TTot['TTotal_tva'];
							$line->multicurrency_total_ht	= $TTot['multicurrency_total_ht'];
							$line->multicurrency_total_tva	= $TTot['multicurrency_total_tva'];
							$line->multicurrency_total_ttc	= $TTot['multicurrency_total_ttc'];
							$line->TTotal_tva_multicurrency	= $TTot['TTotal_tva_multicurrency'];
						}
						$TRes[]	= $line;
					}
				}
			}
			return $TRes;
		}

		/**
		* @param	CommonObject     $object		Document on which we want to get total block for title line
		* @param	CommonObjectLine $line			Title line for which we want to get total block
		* @param	boolean          $breakOnTitle	Whether to stop calculation when we find another title of same or higher level (example : I am on a title level 2 and I find a title level 1 or 2 then I stop calculation for my title block, if false I continue to calculate until I find a title of lower level than my initial title level)
		* @return	array
		*/
		public static function getTotalBlockFromTitle(&$object, &$line, $breakOnTitle = false)
		{
			dol_include_once('/core/lib/price.lib.php');
			$TTot = array('total_pa_ht' => 0, 'total_options' => 0, 'total_ht' => 0, 'total_tva' => 0, 'total_ttc' => 0, 'TTotal_tva' => array(), 'multicurrency_total_ht' => 0, 'multicurrency_total_tva' => 0, 'multicurrency_total_ttc' => 0, 'TTotal_tva_multicurrency' => array());
			foreach ($object->lines as &$l) {
				if ($l->rang <= $line->rang) {
					continue;
				} elseif (self::isTotal($l) && self::getNiveau($l) <= self::getNiveau($line)) {
					break;
				} elseif ($breakOnTitle && self::isTitle($l) && self::getNiveau($l) <= self::getNiveau($line)) {
					break;
				}
				if (!empty($l->array_options['options_infrastructure_nc'])) {
					$tabprice				= calcul_price_total($l->qty, $l->subprice, $l->remise_percent, $l->tva_tx, $l->localtax1_tx, $l->localtax2_tx, 0, 'HT', $l->info_bits, $l->product_type);
					$TTot['total_options']	+= $tabprice[0]; // total ht
				} else {
					// Fix DA020000 : exlure les sous-totaux du calcul (calcul pété)
					// sinon ça compte les ligne de produit puis les sous-totaux qui leurs correspondent...
					if (!self::isTotal($l)) {
						$TTot['total_pa_ht']							+= $l->pa_ht * $l->qty;
						$TTot['total_subprice']							+= $l->subprice * $l->qty;
						$TTot['total_unit_subprice']					+= $l->subprice; // Somme des prix unitaires non remisés
						$TTot['total_ht']								+= $l->total_ht;
						$TTot['total_tva']								+= $l->total_tva;
						$TTot['total_ttc']								+= $l->total_ttc;
						$TTot['TTotal_tva'][$l->tva_tx]					+= $l->total_tva;
						$TTot['multicurrency_total_ht']					+= $l->multicurrency_total_ht;
						$TTot['multicurrency_total_tva']				+= $l->multicurrency_total_tva;
						$TTot['multicurrency_total_ttc']				+= $l->multicurrency_total_ttc;
						$TTot['TTotal_tva_multicurrency'][$l->tva_tx]	+= $l->multicurrency_total_tva;
					}
				}
			}
			return $TTot;
		}

		/**
		* @param	int			$fk_commandedet		id de la ligne de commande
		* @param	bool		$supplier			Whether the line is from supplier order or not (default false for customer order)
		* @return	int|false
		*/
		public static function getOrderIdFromLineId(int $fk_commandedet, bool $supplier = false)
		{
			global $db;

			if (empty($fk_commandedet)) {
				return false;
			}
			$table		= 'commandedet';
			if ($supplier) {
				$table	= 'commande_fournisseurdet';
			}
			$sql		= 'SELECT fk_commande FROM '.$db->prefix().$table.' WHERE rowid = '.intval($fk_commandedet);
			$resql		= $db->query($sql);
			if ($resql && ($row = $db->fetch_object($resql))) {
				return $row->fk_commande;
			} else {
				return false;
			}
		}

		/**
		* @param	int			$fk_commande	id de la commande
		* @param	bool		$supplier		Whether the line is from supplier order or not (default false for customer order)
		* @return	false|int
		*/
		public static function getLastLineOrderId(int $fk_commande, bool $supplier = false)
		{
			global $db;

			if (empty($fk_commande)) {
				return false;
			}
			$table		= 'commandedet';
			if ($supplier) {
				$table	= 'commande_fournisseurdet';
			}
			$sql	= 'SELECT rowid FROM '.$db->prefix().$table.' WHERE fk_commande = '.intval($fk_commande).' ORDER BY rang DESC, rowid DESC LIMIT 1';
			$resql	= $db->query($sql);
			if ($resql && ($row = $db->fetch_object($resql))) {
				return (int) $row->rowid;
			} else {
				return false;
			}
		}

		/**
		* @param	FactureLigne|PropaleLigne|OrderLine $object
		* @param	int									$rang  rank of the line in the object; The first line has rank = 1, not 0.
		* @param 	int									$lvl
		* @return	bool|FactureLigne|PropaleLigne|OrderLine
		*/
		public static function getParentTitleOfLine(&$object, $rang, $lvl = 0)
		{
			if ($rang <= 0) {
				return false;
			}
			$skip_title		= 0;
			$TLineReverse	= array_reverse($object->lines);
			foreach ($TLineReverse as $line) {
				if ($line->rang >= $rang || ($lvl > 0 && self::getNiveau($line) > $lvl)) {
					continue; // Tout ce qui ce trouve en dessous j'ignore, nous voulons uniquement ce qui ce trouve au dessus
				}
				if (self::isTitle($line)) {
					if ($skip_title) {
						$skip_title--;
						continue;
					}
					//@INFO J'ai ma ligne titre qui contient ma ligne, par contre je check pas s'il y a un sous-total
					return $line;
				} elseif (self::isTotal($line)) {
					// Il s'agit d'un sous-total, ça veut dire que le prochain titre théoriquement doit être ignorer (je travail avec un incrément au cas ou je croise plusieurs sous-totaux)
					$skip_title++;
				}
			}
			return false;
		}

		/**
		* Donne la ligne sous-total associée au titre
		*
		* @param	FactureLigne|PropaleLigne|OrderLine $object
		* @param	int									$rang  rank of the line in the object; The first line has rank = 1, not 0.
		* @param	int									$lvl
		* @return	bool|FactureLigne|PropaleLigne|OrderLine
		*/
		public static function getSubLineOfTitle(&$object, $rang, $lvl = 0)
		{
			if ($rang <= 0) {
				return false;
			}
			$skip_title	= 0;
			if (!empty($object->lines)) {
				foreach ($object->lines as $line) {
					if ($line->rang <= $rang || ($lvl > 0 && self::getNiveau($line) < $lvl)) continue;
					if (self::isTitle($line)) {
						$skip_title++;
					} elseif (self::isTotal($line)) {
						if ($skip_title) {
							$skip_title--;
							continue;
						}
						return $line;
					}
				}
			}

			return false;
		}

		/**
		* @param CommonObjectLine $line
		* @return bool
		*/
		public static function hasBreakPage($line)
		{
			return property_exists($line, 'info_bits') && $line->info_bits == 8;
		}

		/**
		* @param	CommonObjectLine	$line	Line object we want to know if it's a title line and get the level of title if it's the case (level is determined by qty field, for example qty 1 for a title of level 1, qty 2 for a title of level 2, etc...)
		* @param	int					$level	Level of title line to check (if -1 just check if it's a title line without checking level)
		* @return	bool
		*/
		public static function isTitle(&$line, $level = -1)
		{
			$res	= !empty($line->special_code) && $line->special_code == self::getModuleNumber() && $line->product_type == 9 && $line->qty <= 9;
			if ($res && $level > -1) {
				return $line->qty == $level;
			} else {
				return $res;
			}
		}

		/**
		* @param	CommonObjectLine	$line	Line object we want to know if it's a infrastructure line and get the level of infrastructure if it's the case (level is determined by qty field, for example qty 90 for a infrastructure of level 1, qty 91 for a infrastructure of level 2, etc...)
		* @param	int					$level	Level of infrastructure line to check (if -1 just check if it's a infrastructure line without checking level)
		* @return	bool
		*/
		public static function isTotal(&$line, $level = -1)
		{
			$res = !empty($line->special_code) && $line->special_code == self::getModuleNumber() && $line->product_type == 9 && $line->qty >= 90;
			if ($res && $level > -1) {
				return self::getNiveau($line) == $level;
			} else {
				return $res;
			}
		}

		/**
		* @param CommonObjectLine $line
		* @return bool
		*/
		public static function isFreeText(&$line)
		{
			return !empty($line->special_code) && $line->special_code == self::getModuleNumber() && $line->product_type == 9 && $line->qty == 50;
		}

		/**
		* @param CommonObjectLine $line
		* @return bool
		*/
		public static function isModInfrastructureLine(&$line)
		{
			return self::isTitle($line) || self::isTotal($line) || self::isFreeText($line);
		}

		/**
		* @param	CommonObjectLine	$line		Line object we want to know if it's a free text line and get the html of description field if it's the case
		* @param	int					$readonly	Whether the line is in read only mode or not (in edit mode we want to be able to edit description with a wysiwyg editor, in view mode we just want to show description)
		* @return	string|void
		*/
		public static function getFreeTextHtml(&$line, $readonly = 0)
		{
			global $conf;

			// editeur wysiwyg
			include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$nbrows			= ROWS_2;
			$nbrows			= getDolGlobalString('MAIN_INPUT_DESC_HEIGHT', '');
			$enable			= getDolGlobalString('FCKEDITOR_ENABLE_DETAILS', 0);
			$toolbarname	= 'dolibarr_details';
			if (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS_FULL')) {
				$toolbarname	= 'dolibarr_notes';
			}
			$text			= !empty($line->description)?$line->description:$line->label;
			$doleditor		= new DolEditor('line-description', $text, '', 164, $toolbarname, '', false, true, $enable, $nbrows, '98%', $readonly);
			return $doleditor->Create(1);
		}

		/**
		* @param	CommonObject $object		Document on which we want to duplicate line
		* @param	int          $lineid		Id of line we want to duplicate
		* @param	bool         $withBlockLine	Whether to duplicate only the line with lineid or also the block line (title and infrastructure) associated with this line
		* @return	int
		*/
		public static function duplicateLines(&$object, $lineid, $withBlockLine = false)
		{
			global $user;

			$createRight	= $user->hasRight($object->element, 'creer');
			if ($object->element == 'facturerec' ) {
				$object->statut	= 0; // hack for facture rec
				$createRight	=  $user->hasRight('facture', 'creer');
			} elseif ($object->element == 'order_supplier' ) {
				$createRight	= $user->hasRight('fournisseur', 'commande', 'creer');
			} elseif ($object->element == 'invoice_supplier' ) {
				$createRight	= $user->hasRight('fournisseur', 'facture', 'creer');
			}
			if ($object->statut == 0  && $createRight && (getDolGlobalString('INFRASTRUCTURE_ALLOW_DUPLICATE_BLOCK') || getDolGlobalString('INFRASTRUCTURE_ALLOW_DUPLICATE_LINE'))) {
				dol_include_once('/infrastructure/core/lib/infrastructure.lib.php');
				if (!empty($object->lines)) {
					foreach ($object->lines as $line) {
						if ($line->id == $lineid) $duplicateLine = $line;
					}
				}
				if (!empty($duplicateLine) && !self::isModInfrastructureLine($duplicateLine)) {
					$TLine = array($duplicateLine);
				} else {
					$TLine = self::getLinesFromTitleId($object, $lineid, $withBlockLine);
				}
				if (!empty($TLine)) {
					$object->db->begin();
					$res										= 1;
					$object->context['infrastructureDuplicateLines']	= true;
					$TLineAdded									= array();
					foreach ($TLine as $line) {
						// TODO refactore avec un doAddLine sur le même schéma que le doUpdateLine
						switch ($object->element) {
							case 'propal':
								$res	= $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, 'HT', 0, $line->info_bits, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $object->element, $line->id);
								break;
							case 'supplier_proposal':
								$res	= $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, 'HT', 0, $line->info_bits, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $object->element, $line->id);
								break;
							case 'commande':
								$res	= $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->date_start, $line->date_end, $line->product_type, -1, $line->special_code, 0, 0, $line->pa_ht, $line->label, $line->array_options, $line->fk_unit, $object->element, $line->id);
								break;
							case 'order_supplier':
								$object->line				= $line;
								$object->line->origin		= $object->element;
								$object->line->origin_id	= $line->id;
								$object->line->fk_commande	= $object->id;
								$object->line->rang			= $object->line_max() +1;
								$res						= $object->line->insert(1);
								break;
							case 'facture':
								$res	= $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->date_start, $line->date_end, 0, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->product_type, -1, $line->special_code, $object->element, $line->id, $line->fk_parent_line, $line->fk_fournprice, $line->pa_ht, $line->label, $line->array_options, $line->situation_percent, $line->fk_prev_id, $line->fk_unit);
								break;
							case 'facturerec':
								$res	= $object->addline($line->desc, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->date_start, $line->date_end, 0, $line->info_bits, $line->fk_remise_except, 'HT', 0, $line->product_type, -1, $line->special_code, $line->origin, $line->origin_id, $line->fk_parent_line, $line->fk_fournprice, $line->pa_ht, $line->label, $line->array_options, $line->situation_percent, $line->fk_prev_id, $line->fk_unit);
								break;
						}
						$TLineAdded[]	= $object->line;
						// Error from addline
						if ($res <= 0) break;
					}
					if ($res > 0) {
						$object->db->commit();
						foreach ($TLineAdded as &$line) {
							// ça peut paraitre non optimisé de déclancher la fonction sur toutes les lignes mais ceci est nécessaire pour réappliquer l'état exact de chaque ligne
							//En gros ça met à jour le sous total
							if (!empty($line->array_options['options_infrastructure_nc'])) infrastructure_updateLineNC($object->element, $object->id, $line->id, $line->array_options['options_infrastructure_nc']);
						}
						return count($TLineAdded);
					} else {
						$object->db->rollback();
						return -1;
					}
				}
				return 0;
			}
			return 0;
		}

		/**
		* @param CommonObject $object		Document on which we want to get lines
		* @param string       $key_trad		Key of the title we want to search
		* @param int          $level		Level of the title
		* @param string       $under_title	Title under which we want to search
		* @param bool         $withBlockLine Whether to include the block line (title and infrastructure) associated with the line
		* @param bool         $key_is_id	Whether the key is an ID
		* @return array
		*/
		public static function getLinesFromTitle(&$object, $key_trad, $level = 1, $under_title = '', $withBlockLine = false, $key_is_id = false)
		{
			global $langs;

			// Besoin de comparer sur les 2 formes d'écriture
			if (!$key_is_id) {
				$TTitle_search	= array($langs->trans($key_trad), $langs->transnoentitiesnoconv($key_trad));
			}
			$TTitle_under_search	= array();
			if (!empty($under_title)) {
				$TTitle_under_search	= array($langs->trans($under_title), $langs->transnoentitiesnoconv($under_title));
			}
			$TLine				= array();
			$add_line			= false;
			$under_title_found	= false;
			foreach ($object->lines as $key => &$line) {
				if (!$under_title_found && !empty($TTitle_under_search)) {
					if ($line->product_type == 9 && (in_array($line->desc, $TTitle_under_search) || in_array($line->label, $TTitle_under_search))) {
						$under_title_found	= true;
					}
				} else {
					if (($key_is_id && $line->id == $key_trad) || (!$key_is_id && $line->product_type == 9 && $line->qty == $level && (in_array($line->desc, $TTitle_search) || in_array($line->label, $TTitle_search) ))) {
						if ($key_is_id) {
							$level	= $line->qty;
						}
						$add_line	= true;
						if ($withBlockLine) {
							$TLine[]	= $line;
						}
						continue;
					} elseif ($add_line && static::isModInfrastructureLine($line) && static::getNiveau($line) == $level) { // Si on tombe sur un sous-total, il faut que ce soit un du même niveau que le titre.
						if (self::isTotal($line)) {
							if ($withBlockLine) {
								$TLine[] = $line;
							}
						} // Si le sous-total a été supprimé, il ne faut pas premdre le titre de mêm niveau qui suit
						break;
					}
					if ($add_line) {
						if (!$withBlockLine && (self::isTitle($line) || self::isTotal($line))) {
							continue;
						} else {
							$TLine[] = $line;
						}
					}
				}
			}
			return $TLine;
		}

		/**
		* Get lines of a title from line id. It is a wrapper around getLinesFromTitle with key_is_id = true
		*
		* @param	CommonObject	$object			Document on which we want to get lines from title id
		* @param	int				$lineid			Id of the line title we want to search
		* @param	bool			$withBlockLine	Whether to include the block line (title and infrastructure) associated with the line
		* @return	array							Array of lines found
		*/
		public static function getLinesFromTitleId(&$object, $lineid, $withBlockLine = false)
		{
			return self::getLinesFromTitle($object, $lineid, 0, '', $withBlockLine, true);
		}

		/**
		* Wrapper around $object->updateline() to ensure it is called with the right parameters depending on the object's
		* type.
		*
		* @param	CommonObject	$object				Document on which we want to update line
		* @param	int				$rowid				Id of the line we want to update
		* @param	string			$desc				Description of the line
		* @param	double			$pu					Unit price of the line
		* @param	double			$qty				Quantity of the line
		* @param	double			$remise_percent		Discount percentage of the line
		* @param	string			$date_start			Start date of the line
		* @param	string			$date_end			End date of the line
		* @param	double			$txtva				Tax value of the line
		* @param	int 			$type				Type of the line
		* @param	int 			$txlocaltax1		Local tax 1 of the line
		* @param	int 			$txlocaltax2		Local tax 2 of the line
		* @param	string 			$price_base_type	Price base type of the line
		* @param	int 			$info_bits			Information bits of the line
		* @param	int 			$fk_parent_line		Id of parent line if the line is in a block line
		* @param	int 			$skip_update_total	Flag to skip update total
		* @param	int 			$fk_fournprice		Id of fournisseur price
		* @param	int 			$pa_ht				Purchase price
		* @param	string 			$label				Label of the line
		* @param	int 			$special_code		Special code of the line
		* @param	array 			$array_options		Array options of the line
		* @param	int 			$situation_percent	Situation percentage of the line
		* @param	int 			$fk_unit			Id of the unit of the line
		* @param	int 			$notrigger			Flag to skip triggers
		* @return int
		*/
		public static function doUpdateLine(&$object, $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $type, $txlocaltax1 = 0, $txlocaltax2 = 0, $price_base_type = 'HT', $info_bits = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $special_code = 0, $array_options = 0, $situation_percent = 0, $fk_unit = null, $notrigger = 0)
		{
			$res = 0;
			$object->db->begin();
			switch ($object->element) {
				case 'propal':
					$res	= $object->updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $desc, $price_base_type, $info_bits, $special_code, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $type, $date_start, $date_end, $array_options, $fk_unit, 0, $notrigger);
					break;
				case 'supplier_proposal':
					$res	= $object->updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $desc, $price_base_type, $info_bits, $special_code, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $type, $array_options, '', $fk_unit);
					break;
				case 'commande':
					$res	= $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $date_start, $date_end, $type, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $special_code, $array_options, $fk_unit, 0, $notrigger);
					break;
				case 'order_supplier':
					$object->special_code	= self::getModuleNumber();
					if (empty($desc) ) {
						$desc = $label;
					}
					$res	= $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $type, 0, $date_start, $date_end, $array_options, $fk_unit);
					break;
				case 'facture':
					$res	= $object->updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1, $txlocaltax2, $price_base_type, $info_bits, $type, $fk_parent_line, $skip_update_total, $fk_fournprice, $pa_ht, $label, $special_code, $array_options, $situation_percent, $fk_unit, 0, $notrigger);
					break;
				case 'invoice_supplier':
					$object->special_code = self::getModuleNumber();
					if (empty($desc)) {
						$desc	= $label;
					}
					$res	= $object->updateline($rowid, $desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, 0, $price_base_type, $info_bits, $type, $remise_percent, 0, $date_start, $date_end, $array_options, $fk_unit);
					break;
				case 'facturerec':
					// Add extrafields and get rang
					$factureRecLine	= new FactureLigneRec($object->db);
					$factureRecLine->fetch($rowid);
					$factureRecLine->array_options = $array_options;
					$factureRecLine->insertExtraFields();
					$rang			= $factureRecLine->rang;
					$fk_product		= 0; $fk_remise_except = ''; $pu_ttc = 0;
					$res			= $object->updateline($rowid, $desc, $pu, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $price_base_type, $info_bits, $fk_remise_except, $pu_ttc, $type, $rang, $special_code, $label, $fk_unit);
					break;
			}
			if ($res <= 0) {
				$object->db->rollback();
			} else {
				$object->db->commit();
			}
			return $res;
		}

		/**
		* @param	CommonObjectLine	$origin_line	Line from which we want to get title lines
		* @param	bool				$reverse		Whether to reverse the order of the returned array or not. By default, the closest title is the first one in the array, if reverse is true, the closest title will be the last one in the array.
		* @return	array								Array of title lines found
		*/
		public static function getAllTitleFromLine(&$origin_line, $reverse = false)
		{
			global $db;

			$TTitle			= array();
			$current_object	= null;
			// Get the parent object if needed
			if (!empty($GLOBALS['object']->id) && in_array($GLOBALS['object']->element, array('propal', 'commande', 'facture'))) {
				$current_object = $GLOBALS['object'];
			} else {
				if ($origin_line->element == 'propaldet') {
					$current_object = new Propal($db);
					$current_object->fetch($origin_line->fk_propal);
				} elseif ($origin_line->element == 'commandedet') {
					$current_object = new Commande($db);
					$current_object->fetch($origin_line->fk_commande);
				} elseif ($origin_line->element == 'facturedet') {
					$current_object = new Facture($db);
					$current_object->fetch($origin_line->fk_facture);
				} else {
					return $TTitle;
				}
			}
			// Récupération de la position de la ligne
			$i = 0;
			foreach ($current_object->lines as &$line) {
				if ($origin_line->id == $line->id) {
					break;
				} else {
					$i++;
				}
			}
			$i--; // Skip la ligne d'origine
			// Si elle n'est pas en 1ère position, alors on cherche des titres au dessus
			if ($i >= 0) {
				$next_title_lvl_to_skip = 0;
				for ($y = $i; $y >= 0; $y--) {
					// Si je tombe sur un sous-total, je récupère son niveau pour savoir quel est le prochain niveau de titre que doit ignorer
					if (self::isTotal($current_object->lines[$y])) {
						$next_title_lvl_to_skip = self::getNiveau($current_object->lines[$y]);
					} elseif (self::isTitle($current_object->lines[$y])) {
						if ($current_object->lines[$y]->qty == $next_title_lvl_to_skip) {
							$next_title_lvl_to_skip = 0;
							continue;
						} else {
							if (empty($current_object->lines[$y]->array_options) && !empty($current_object->lines[$y]->id)) $current_object->lines[$y]->fetch_optionals();
							$TTitle[$current_object->lines[$y]->id] = $current_object->lines[$y];

							if ($current_object->lines[$y]->qty == 1) break;
						}
					}
				}
			}
			if ($reverse) {
				$TTitle = array_reverse($TTitle, true);
			}
			return $TTitle;
		}

		/**
		* @param	CommonObjectLine	$line	Line for which we want to get the level
		* @return	int							0 = $level <= 9 for title, 90 < $level <= 99 for infrastructure, -1 for other lines
		*/
		public static function getNiveau(&$line)
		{
			if (self::isTitle($line)) {
				return $line->qty;
			} elseif (self::isTotal($line)) {
				return 100 - $line->qty;
			} else {
				return 0;
			}
		}

		/**
		* Ajoute une page de récap à la génération du PDF
		* Le tableau total en bas du document se base sur les totaux des titres niveau 1 pour le moment
		*
		* @param	array	$parameters		assoc array; keys: 'object' (CommonObject), 'file' (string), 'outputlangs' (Translate)
		* @param	int		$origin_pdf		unused [lines that used it are commented out]
		* @param	int		$fromInfraS		unused [lines that used it are commented out]
		*/
		public static function addRecapPage(&$parameters, &$origin_pdf, $fromInfraS = 0)
		{
			global $user, $conf, $langs;

			$langs->load('infrastructure@infrastructure');
			$objmarge				= new stdClass();
			$origin_file			= $parameters['file'];
			$outputlangs			= $parameters['outputlangs'];
			$object					= $parameters['object'];
			$objmarge->page_hauteur	= 297;
			$objmarge->page_largeur	= 210;
			$objmarge->marge_gauche	= 10;
			$objmarge->marge_haute	= 10;
			$objmarge->marge_droite	= 10;
			$objectref				= dol_sanitizeFileName($object->ref);
			if ($object->element == 'propal') {
				$dir	= $conf->propal->dir_output.'/'.$objectref;
			} elseif ($object->element == 'commande') {
				$dir	= $conf->commande->dir_output.'/'.$objectref;
			} elseif ($object->element == 'facture') {
				$dir	= $conf->facture->dir_output.'/'.$objectref;
			} elseif ($object->element == 'facturerec') {
				return; // no PDF for facturerec
			} else {
				setEventMessage($langs->trans('InfrastructureWarningRecapObjectElementUnknown', $object->element), 'warnings');
				return -1;
			}
			$file				= $dir.'/'.$objectref.'_recap.pdf';
			$pdf				= pdf_getInstance(array(210, 297)); // Format A4 Portrait
			$default_font_size	= pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
			$pdf->SetAutoPageBreak(1, 0);
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs));
			// Set path to the background PDF File
			if (!getDolGlobalString('MAIN_DISABLE_FPDI') && getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
				$pagecount	= $pdf->setSourceFile($conf->mycompany->dir_output.'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
				$tplidx		= $pdf->importPage(1);
			}
			$pdf->Open();
			$pagenb	= 0;
			$pdf->SetDrawColor(128, 128, 128);
			$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
			$pdf->SetSubject($outputlangs->transnoentities("infrastructureRecap"));
			$pdf->SetCreator("Dolibarr ".DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("infrastructureRecap")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
			if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
				$pdf->SetCompression(false);
			}
			$pdf->SetMargins($objmarge->marge_gauche, $objmarge->marge_haute, $objmarge->marge_droite);   // Left, Top, Right
			$pagenb	= 0;
			$pdf->SetDrawColor(128, 128, 128);
			// New page
			$pdf->AddPage();
			if (! empty($tplidx)) {
				$pdf->useTemplate($tplidx);
			}
			$pagenb++;
			self::pageHead($objmarge, $pdf, $object, 1, $outputlangs);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(0, 3, '');		// Set interline to 3
			$pdf->SetTextColor(0, 0, 0);
			$heightforinfotot	= 25;	// Height reserved to output the info and total part
			$heightforfooter	= $objmarge->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
			$posx_designation	= 25;
			$posx_options		= 150;
			$posx_montant		= 170;
			$tab_top			= 72;
			$tab_top_newpage	= (!getDolGlobalString('MAIN_PDF_DONOTREPEAT_HEAD')?72:20); // TODO à vérifier
			$TTot				= array('total_ht' => 0, 'total_ttc' => 0, 'TTotal_tva' => array());
			$TLine				= self::getAllTitleFromDocument($object, true);
			if (!empty($TLine)) {
				$hidetop		= 0;
				$iniY			= $tab_top + 10;
				$curY			= $tab_top + 10;
				$nexY			= $tab_top + 10;
				$nblignes		= count($TLine);
				foreach ($TLine as $i => &$line) {
					$curY = $nexY;
					if (self::getNiveau($line) == 1) {
						$pdf->SetFont('', 'B', $default_font_size - 1);   // Into loop to work with multipage
						$curY								+= 2;
						$TTot['total_ht']					+= $line->total_ht;
						$TTot['total_tva']					+= $line->total_tva;
						$TTot['total_ttc']					+= $line->total_ttc;
						$TTot['multicurrency_total_ht']		+= $line->multicurrency_total_ht;
						$TTot['multicurrency_total_tva']	+= $line->multicurrency_total_tva;
						$TTot['multicurrency_total_ttc']	+= $line->multicurrency_total_ttc;
						foreach ($line->TTotal_tva as $tx => $amount) {
							$TTot['TTotal_tva'][$tx] += $amount;
						}
						foreach ($line->TTotal_tva_multicurrency as $tx => $amount) {
							$TTot['TTotal_tva_multicurrency'][$tx] += $amount;
						}
					} else {
						$pdf->SetFont('', '', $default_font_size - 1);   // Into loop to work with multipage
					}
					$pdf->SetTextColor(0, 0, 0);
					$pdf->setTopMargin($tab_top_newpage + 10);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore				= $pdf->getPage();
					$showpricebeforepagebreak	= 1;
					$decalage					= (self::getNiveau($line) - 1) * 2;
					$label						= $line->label;
					$pdf->startTransaction();
					$pdf->writeHTMLCell($posx_options-$posx_designation-$decalage, 3, $posx_designation+$decalage, $curY, $outputlangs->convToOutputCharset($label), 0, 1, false, true, 'J', true);
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore) {	// There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						$pdf->writeHTMLCell($posx_options-$posx_designation-$decalage, 3, $posx_designation+$decalage, $curY, $outputlangs->convToOutputCharset($label), 0, 1, false, true, 'J', true);
						$pageposafter	= $pdf->getPage();
						$posyafter		= $pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($objmarge->page_hauteur - ($heightforfooter+$heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblignes-1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								if (! empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (!getDolGlobalString('MAIN_PDF_DONOTREPEAT_HEAD')) {
									self::pageHead($objmarge, $pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter+1);
							}
						} else {
							// We found a page break
							$showpricebeforepagebreak	= 0;
						}
					} else {
						$pdf->commitTransaction();
					}
					$posYAfterDescription	= $pdf->GetY();
					$nexY					= $pdf->GetY();
					$pageposafter			= $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($objmarge->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage + 10;
					}
					self::printLevel($objmarge, $pdf, $line, $curY, $posx_designation);
					// Print: Options
					if (!empty($line->total_options)) {
						$pdf->SetXY($posx_options, $curY);
						$pdf->MultiCell($posx_montant-$posx_options-0.8, 3, price($line->total_options, 0, $outputlangs), 0, 'R', 0);
					}
					// Print: Montant
					$pdf->SetXY($posx_montant, $curY);
					$pdf->MultiCell($objmarge->page_largeur-$objmarge->marge_droite-$posx_montant-0.8, 3, price($line->total_ht, 0, $outputlangs), 0, 'R', 0);
					$nexY+=2;    // Passe espace entre les lignes
					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top, $objmarge->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top_newpage, $objmarge->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code);
						}
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (!getDolGlobalString('MAIN_PDF_DONOTREPEAT_HEAD')) {
							self::pageHead($objmarge, $pdf, $object, 0, $outputlangs);
						}
					}
				}
			}
			// Show square
			if ($pagenb == 1) {
				self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top, $objmarge->page_hauteur - $tab_top - $heightforinfotot - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
				$bottomlasttab=$objmarge->page_hauteur - $heightforinfotot - $heightforfooter + 1;
			} else {
				self::tableau($objmarge, $pdf, $posx_designation, $posx_options, $posx_montant, $tab_top_newpage, $objmarge->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfooter, 0, $outputlangs, $hidetop, 0, $object->multicurrency_code);
				$bottomlasttab=$objmarge->page_hauteur - $heightforinfotot - $heightforfooter + 1;
			}
			// Affiche zone totaux
			$posy	= self::tableauTot($objmarge, $pdf, $object, $bottomlasttab, $outputlangs, $TTot);
			$pdf->Close();
			$pdf->Output($file, 'F');
			if (empty($fromInfraS)) {
				$pagecount = self::concat($outputlangs, array($origin_file, $file), $origin_file);
			}
			if (!empty($fromInfraS)){
				return $file;
			}
			if (!getDolGlobalString('INFRASTRUCTURE_KEEP_RECAP_FILE')) {
				unlink($file);
			}
		}

		/**
		* @param	stdClass         $objmarge			Margin object containing margin and page size information
		* @param	TCPDF            $pdf				PDF object
		* @param	CommonObjectLine $line				Line for which we want to print the level
		* @param	int              $curY				Current Y position on the PDF
		* @param	int              $posx_designation	X position of the designation column, used to calculate the width of the cell where the level is printed
		* @return void
		*/
		private static function printLevel($objmarge, $pdf, $line, $curY, $posx_designation)
		{
			$level = $line->qty; // TODO à améliorer

			$pdf->SetXY($objmarge->marge_gauche, $curY);
			$pdf->MultiCell($posx_designation-$objmarge->marge_gauche-0.8, 5, $level, 0, 'L', 0);
		}

		/**
		*  Show top header of page.
		*
		*  @param	TCPDF     $pdf          Object PDF
		*  @param  object    $object       Object to show
		*  @param  int       $showdetail   0=no, 1=yes
		*  @param  Translate $outputlangs  Object lang for output
		*  @return	void
		*/
		private static function pageHead(&$objmarge, &$pdf, &$object, $showdetail, $outputlangs)
		{
			global $conf,$mysoc;

			$default_font_size = pdf_getPDFFontSize($outputlangs);
			pdf_pagehead($pdf, $outputlangs, $objmarge->page_hauteur);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$posy	= $objmarge->marge_haute;
			$posx	= $objmarge->page_largeur-$objmarge->marge_droite-100;
			$pdf->SetXY($objmarge->marge_gauche, $posy);
			$logo	= $conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
			if ($mysoc->logo) {
				if (is_readable($logo)) {
					$height=pdf_getHeightForLogo($logo);
					$pdf->Image($logo, $objmarge->marge_gauche, $posy, 0, $height);	// width=0 (auto)
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
				$posy	+= 35;
			} else {
				$text	= $mysoc->name;
				$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
				$posy	+= 15;
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->SetXY($objmarge->marge_gauche, $posy);
			$key = 'infrastructurePropalTitle';
			if ($object->element == 'commande') {
				$key = 'infrastructureCommandeTitle';
			} elseif ($object->element == 'facture') {
				$key = 'infrastructureInvoiceTitle';
			} elseif ($object->element == 'facturerec') {
				$key = 'infrastructureInvoiceTitle';
			}
			$pdf->MultiCell(150, 4, $outputlangs->transnoentities($key, $object->ref, $object->thirdparty->name), '', 'L');
			$pdf->SetFont('', '', $default_font_size);
			$pdf->SetXY($objmarge->page_largeur-$objmarge->marge_droite-40, $posy);
			$pdf->MultiCell(40, 4, dol_print_date($object->date, 'daytext'), '', 'R');
			$posy += 8;
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->SetXY($objmarge->marge_gauche, $posy);
			$pdf->MultiCell(70, 4, $outputlangs->transnoentities('infrastructureRecapLot'), '', 'L');
		}
		/**
		*   Show table for lines
		*
		*   @param		TCPDF		$pdf     		Object PDF
		*   @param		string		$tab_top		Top position of table
		*   @param		string		$tab_height		Height of table (rectangle)
		*   @param		int			$nexY			Y (not used)
		*   @param		Translate	$outputlangs	Langs object
		*   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		*   @param		int			$hidebottom		Hide bottom bar of array
		*   @param		string		$currency		Currency code
		*   @return	void
		*/
		private static function tableau(&$objmarge, &$pdf, $posx_designation, $posx_options, $posx_montant, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
		{
			global $conf;

			// Force to disable hidetop and hidebottom
			$hidebottom			= 0;
			if ($hidetop) $hidetop =- 1;
			$currency			= !empty($currency) ? $currency : $conf->currency;
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			// Amount in (at tab_top - 1)
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size);
			if (empty($hidetop)) {
				$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
				$pdf->SetXY($objmarge->page_largeur - $objmarge->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4.5);
				$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);
				if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
					$pdf->Rect($objmarge->marge_gauche, $tab_top, $objmarge->page_largeur-$objmarge->marge_droite-$objmarge->marge_gauche, 8, 'F', array(), explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
				}
				$pdf->line($objmarge->marge_gauche, $tab_top, $objmarge->page_largeur-$objmarge->marge_droite, $tab_top);	// line prend une position y en 2eme param et 4eme param
				$pdf->SetXY($posx_designation, $tab_top+2);
				$pdf->MultiCell($posx_options - $posx_designation, 2, $outputlangs->transnoentities("Designation"), '', 'L');
				$pdf->SetXY($posx_options, $tab_top+2);
				$pdf->MultiCell($posx_montant - $posx_options, 2, $outputlangs->transnoentities("Options"), '', 'R');
				$pdf->SetXY($posx_montant, $tab_top+2);
				$pdf->MultiCell($objmarge->page_largeur - $objmarge->marge_droite - $posx_montant, 2, $outputlangs->transnoentities("Amount"), '', 'R');
				$pdf->line($objmarge->marge_gauche, $tab_top+8, $objmarge->page_largeur-$objmarge->marge_droite, $tab_top+8);	// line prend une position y en 2eme param et 4eme param
			} else {
				$pdf->line($objmarge->marge_gauche, $tab_top-2, $objmarge->page_largeur-$objmarge->marge_droite, $tab_top-2);	// line prend une position y en 2eme param et 4eme param
			}
		}

		/**
		* @param stdClass		$objmarge	Margin object containing margin and page size information
		* @param TCPDF			$pdf		PDF object
		* @param CommonObject	$object		Object for which we want to print the total table
		* @param int			$posy		Y position of the top of the total table
		* @param Translate		$outputlangs	Langs object
		* @param array			$TTot		Array containing total amounts (total_ht, total_tva, total_ttc, total_tva by rate, and if multicurrency is enabled, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc, multicurrency_total_tva by rate)
		* @return float|int					Y position of the bottom of the total table
		*/
		private static function tableauTot(&$objmarge, &$pdf, $object, $posy, $outputlangs, $TTot)
		{

			$pdf->line($objmarge->marge_gauche, $posy, $objmarge->page_largeur-$objmarge->marge_droite, $posy);	// line prend une position y en 2eme param et 4eme param
			$default_font_size	= pdf_getPDFFontSize($outputlangs);
			$tab2_top			= $posy+2;
			$tab2_hl			= 4;
			$pdf->SetFont('', '', $default_font_size - 1);
			// Tableau total
			$col1x	= 120;
			$col2x	= 170;
			if ($objmarge->page_largeur < 210) { // To work with US executive format
				$col2x-=20;
			}
			$largcol2	= ($objmarge->page_largeur - $objmarge->marge_droite - $col2x);
			$useborder	= 0;
			$index		= 0;
			// Total HT
			$pdf->SetFillColor(255, 255, 255);
			$pdf->SetXY($col1x, $tab2_top + 0);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
			// $total_ht = (isModEnabled('multicurrency') && $object->mylticurrency_tx != 1) ? $TTot['multicurrency_total_ht'] : $TTot['total_ht'];
			$total_ht	= $TTot['total_ht'];
			$pdf->SetXY($col2x, $tab2_top + 0);
			$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht, 0, $outputlangs), 0, 'R', 1);
			// Show VAT by rates and total
			$pdf->SetFillColor(248, 248, 248);
			$atleastoneratenotnull	= 0;
			foreach ($TTot['TTotal_tva'] as $tvakey => $tvaval) {
				if ($tvakey != 0) {    // On affiche pas taux 0
					$atleastoneratenotnull++;
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$tvacompl	= '';
					if (preg_match('/\*/', $tvakey)) {
						$tvakey		= str_replace('*', '', $tvakey);
						$tvacompl	= " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
					}
					$totalvat	= $outputlangs->transnoentities("TotalVAT").' ';
					$totalvat	.= vatrate($tvakey, 1).$tvacompl;
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
				}
			}
			// Total TTC
			$index++;
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);
			// $total_ttc = (isModEnabled('multicurrency') && $object->multiccurency_tx != 1) ? $TTot['multicurrency_total_ttc'] : $TTot['total_ttc'];
			$total_ttc = $TTot['total_ttc'];
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs), $useborder, 'R', 1);
			$pdf->SetTextColor(0, 0, 0);
			$index++;
			return ($tab2_top + ($tab2_hl * $index));
		}

		/**
		* Rect pdf
		*
		* @param	TCPDF	$pdf			Object PDF
		* @param	float	$x				Abscissa of first point
		* @param	float	$y		        Ordinate of first point
		* @param	float	$l				Width of the rectangle
		* @param	float	$h				Height of the rectangle
		* @param	int		$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
		* @param	int		$hidebottom		Hide bottom
		* @return	void
		*/
		private static function printRect($pdf, $x, $y, $l, $h, $hidetop = 0, $hidebottom = 0)
		{
			if (empty($hidetop) || $hidetop==-1) {
				$pdf->line($x, $y, $x+$l, $y);
			}
			$pdf->line($x+$l, $y, $x+$l, $y+$h);
			if (empty($hidebottom)) {
				$pdf->line($x+$l, $y+$h, $x, $y+$h);
			}
			$pdf->line($x, $y+$h, $x, $y);
		}

		/**
		* @param	Translate	$outputlangs	Langs object
		* @param	array		$files			Array of PDF files to concatenate
		* @param	string		$fileoutput		Output file path
		* @return	int							Number of pages in the concatenated PDF
		*/
		public static function concat(&$outputlangs, $files, $fileoutput = '')
		{

			if (empty($fileoutput)) {
				$fileoutput = $files[0];
			}
			$pdf	= pdf_getInstance();
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs));
			if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
				$pdf->SetCompression(false);
			}
			foreach ($files as $file) {
				$pagecount	= $pdf->setSourceFile($file);
				for ($i = 1; $i <= $pagecount; $i++) {
					$tplidx	= $pdf->ImportPage($i);
					$s		= $pdf->getTemplatesize($tplidx);
					$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
					$pdf->useTemplate($tplidx);
				}
			}
			$pdf->Output($fileoutput, 'F');
			if (getDolGlobalString('MAIN_UMASK')) {
				@chmod($fileoutput, octdec(getDolGlobalString('MAIN_UMASK')));
			}
			return $pagecount;
		}

		/**
		* Méthode pour savoir si une ligne fait partie d'un bloc Compris/Non Compris
		*
		* @param	PropaleLigne|OrderLine|FactureLigne	$line	Ligne à tester
		* @return	bool
		*/
		public static function hasNcTitle(&$line)
		{
			if (isset($line->has_nc_title)) {
				return $line->has_nc_title;
			}
			$TTitle = self::getAllTitleFromLine($line);
			foreach ($TTitle as &$line_title) {
				if (!empty($line_title->array_options['options_infrastructure_nc'])) {
					$line->has_nc_title = true;
					return true;
				}
			}
			$line->has_nc_title = false;
			return false;
		}

		/**
		* Méthode pour récupérer le titre de la ligne
		*
		* @param	PropaleLigne|OrderLine|FactureLigne	$line	Ligne à tester
		* @return	string
		*/
		public static function getTitleLabel($line)
		{
			$title = $line->label;
			if (empty($title)) {
				$title = !empty($line->description) ? $line->description : $line->desc;
			}
			return $title;
		}

		/**
		* Méthode pour récupérer le code html contenu dans un éditeur WYSIWYG d'un dictionnaire
		*
		* @return	string
		*/
		public static function getHtmlDictionnary():string
		{
			global $db;

			$value	= '';
			$sql	= 'SELECT content FROM '.$db->prefix().'c_infrastructure_free_text WHERE rowid = '.GETPOST('rowid', 'int');
			$resql	= $db->query($sql);
			if ($resql && ($obj = $db->fetch_object($resql))) {
				$value = $obj->content;
			}
			return $value;
		}

		/**
		* Retourne le taux de TVA unique des lignes comprises entre un Titre et un Sous-total de même niveau.
		* On peut appeler cette fonction en partant d'un titre ou d'un sous-total.
		*
		* @param	CommonObject     					  $object	Objet Dolibarr (Propal, Commande, Facture…)
		* @param	PropaleLigne|OrderLine|FactureLigne $lineRef	Ligne de type titre ou sous-total
		* @return	float|false									Taux de TVA homogène ou false si taux différents
		*/
		public static function getCommonVATRate($object, $lineRef)
		{
			if (!TInfrastructure::isTitle($lineRef) && !TInfrastructure::isTotal($lineRef)) {
				return false;
			}
			$niveau 	= TInfrastructure::getNiveau($lineRef);
			$tva_unique = null;
			$start_rang = null;
			$end_rang 	= null;
			// Cherche les bornes du bloc
			if (TInfrastructure::isTitle($lineRef)) {
				$start_rang = $lineRef->rang;
				foreach ($object->lines as $line) {
					if ($line->rang <= $start_rang) {
						continue;
					}
					// Si on rencontre un sous-total du même niveau que le titre initial, on marque la fin du bloc.
					if (TInfrastructure::isTotal($line) && TInfrastructure::getNiveau($line) == $niveau) {
						$end_rang = $line->rang;
						break;
					}
					// Si on rencontre un autre titre de niveau inférieur ou égal **avant** de trouver un sous-total de même niveau,
					// OU si l'option "Fusionner les sous-totaux avec les sous-titres" n'est pas activée,
					if ((TInfrastructure::isTitle($line) && TInfrastructure::getNiveau($line) <= $niveau) || empty(getDolGlobalInt('INFRASPLUS_PDF_SUBTI_WITH_SUBTO', 0))) {
						return false;
					}
				}
			} elseif (TInfrastructure::isTotal($lineRef)) {
				$end_rang = $lineRef->rang;
				for ($i = count($object->lines) - 1; $i >= 0; $i--) {
					$line = $object->lines[$i];
					if ($line->rang >= $end_rang) {
						continue;
					}
					// Si on rencontre un titre du même niveau que la ligne de départ, on marque le début du bloc.
					if (TInfrastructure::isTitle($line) && TInfrastructure::getNiveau($line) == $niveau) {
						$start_rang = $line->rang;
						break;
					}
				}
			}
			// Si une des bornes n’est pas trouvée
			if ($start_rang === null || $end_rang === null) {
				return false;
			}
			// Analyse des lignes normales(produits, services) entre les deux bornes
			foreach ($object->lines as $line) {
				if ($line->rang <= $start_rang || $line->rang >= $end_rang) {
					continue;
				}
				if (!TInfrastructure::isModInfrastructureLine($line)) {
					$tva_tx = $line->tva_tx;
					if ($tva_unique === null) {
						$tva_unique = $tva_tx;
					} elseif ($tva_tx !== $tva_unique) {
						return false;
					}
				}
			}
			return $tva_unique;
		}
	}
