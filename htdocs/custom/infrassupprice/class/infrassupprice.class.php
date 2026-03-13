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
	* 	\file		./infrassupprice/class/infrassupprice.class.php
	* 	\ingroup	InfraS
	* 	\brief		File of class to InfraSSupPrice module
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

	/************************************************
	* Class to manage members type
	************************************************/
	class InfraSSupPrice extends Product
	{
		/**
		* Constructor
		*	@param		DoliDB		$this->db		Database handler
		**/
		function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		*    Modify the purchase price for a supplier
		*
		*    @param		Societe		$fourn					Supplier
		*    @param		string		$ref_fourn				Supplier ref
		*    @param		string		$id_prod				product id
		*    @param		float		$tva_tx					New VAT Rate (For example 8.5. Should not be a string)
		*    @param		float		$currency_tx			currency rate
		*    @param		float		$currency_code			currency code
		*    @param		float		$currency_unitbuyprice	Unit Purchase currency price
		*    @param		float		$unitbuyprice			Unit Purchase price
		*    @param		float		$qty					Min quantity for which price is valid
		*    @param		float		$currency_buyprice		Purchase currency price for the quantity
		*    @param		float		$buyprice				Purchase price for the quantity
		*    @param		float		$remise_percent			Discount percent
		*    @return	int									< 0 if KO, >= 0 if OK
		**/
		function InfraS_update_buyprice($fourn, $id_prod, $ref_fourn, $tva_tx, $currency_tx, $currency_code, $currency_unitbuyprice, $unitbuyprice, $qty, $currency_buyprice, $buyprice, $remise_percent = 0)
		{
			global $conf, $langs, $user;

			// Multicurrency
			$sql_search_currency	= '';
			$sql_upt_currency		= '';
			$fk_currency			= 'NULL';
			if ($conf->multicurrency->enabled) {
				if (empty($currency_tx)) {
					$currency_tx			= 1;
				}
				if (empty($currency_unitbuyprice)) {
					$currency_unitbuyprice	= 0;
				}
				if (empty($currency_buyprice)) {
					$currency_buyprice		= 0;
				}
				$currency_unitbuyprice	= price2num($currency_unitbuyprice, 'MU');
				$currency_buyprice		= price2num($currency_buyprice, 'MU');
				$fk_currency			= MultiCurrency::getIdFromCode($this->db, $currency_code);
				$sql_search_currency	= ' AND (fk_multicurrency = '.((int) $fk_currency).' OR ISNULL(fk_multicurrency))';
				$sql_upt_currency		= ', multicurrency_tx = '.((float) $currency_tx);
				$sql_upt_currency		.= ', multicurrency_price = '.((float) $currency_buyprice);
			}
			$sql_search		= 'SELECT rowid, price, quantity, remise_percent, tva_tx, fk_multicurrency, multicurrency_tx, multicurrency_price';
			$sql_search		.= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price';
			$sql_search		.= ' WHERE entity = '.$conf->entity;
			$sql_search		.= ' AND fk_soc = '.$fourn->id;
			$sql_search		.= ' AND ref_fourn = "'.$ref_fourn.'"';
			$sql_search		.= ' AND quantity = '.$qty;
			$sql_search		.= $sql_search_currency;
			$result_search	= $this->db->query($sql_search);
			if ($result_search) {
				$num	= $this->db->num_rows($result_search);
				if ($num  > 0) {
					$obj_found	= $this->db->fetch_object($result_search);
					if ($obj_found->price != $buyprice || $obj_found->tva_tx != $tva_tx || $obj_found->remise_percent != $remise_percent
						|| ($conf->multicurrency->enabled && $obj_found->fk_multicurrency != $fk_currency)
						|| ($conf->multicurrency->enabled && $obj_found->fk_multicurrency == $fk_currency && $obj_found->multicurrency_tx != $currency_tx)) {
						$sql_upt	= 'UPDATE '.MAIN_DB_PREFIX.'product_fournisseur_price';
						$sql_upt	.= ' SET datec = "'.$this->db->idate(dol_now()).'"';
						$sql_upt	.= ', price = '.((float) $buyprice);
						$sql_upt	.= ', remise_percent = '.((float) $remise_percent);
						$sql_upt	.= ', unitprice = '.((float) $currency_unitbuyprice);
						$sql_upt	.= ', tva_tx = '.((float) $tva_tx);
						$sql_upt	.= $sql_upt_currency;
						$sql_upt	.= ' WHERE rowid = '.((int) $obj_found->rowid);
						$result_upt	= $this->db->query($sql_upt);
						if ($result_upt) {
							$this->db->free($result_upt);
							$this->db->free($result_search);
							return 0;
						} else {
							$this->description = $this->db->error();
							return -1;
						}
					} else {
						$this->db->free($result_search);
						return 1;
					}
				} else {
					$sql_ins	= 'INSERT INTO '.MAIN_DB_PREFIX.'product_fournisseur_price';
					$sql_ins	.= ' (entity, datec, fk_product, fk_soc, ref_fourn, price, quantity, remise_percent, unitprice, tva_tx, fk_user,';
					$sql_ins	.= ' delivery_time_days, supplier_reputation, fk_multicurrency, multicurrency_code, multicurrency_tx,';
					$sql_ins	.= ' multicurrency_price, multicurrency_unitprice)';
					$sql_ins	.= ' VALUES ';
					$sql_ins	.= ' ('.((int) $conf->entity);
					$sql_ins	.= ', "'.$this->db->idate(dol_now()).'"';
					$sql_ins	.= ', '.((int) $id_prod);
					$sql_ins	.= ', '.((int) $fourn->id);
					$sql_ins	.= ', "'.$this->db->escape($ref_fourn).'"';
					$sql_ins	.= ', '.((float) $buyprice);
					$sql_ins	.= ', '.((float) $qty);
					$sql_ins	.= ', '.((float) $remise_percent);
					$sql_ins	.= ', '.((float) $unitbuyprice);
					$sql_ins	.= ', '.((float) $tva_tx);
					$sql_ins	.= ', '.((int) $user->id);
					$sql_ins	.= ', 0';
					$sql_ins	.= ', "FAVORITE"';
					$sql_ins	.= ', '.((int) $fk_currency);
					$sql_ins	.= ', "'.$this->db->escape($currency_code).'"';
					$sql_ins	.= ', '.((float) $currency_tx);
					$sql_ins	.= ', '.((float) $currency_buyprice);
					$sql_ins	.= ', '.((float) $currency_unitbuyprice);
					$sql_ins	.= ')';
					$result_ins	= $this->db->query($sql_ins);
					if ($result_ins) {
						$this->db->free($result_ins);
						$this->db->free($result_search);
						return 0;
					} else {
						$this->description	= $this->db->error();
						$this->db->free($result_search);
						return -2;
					}
				}
			} else {
				$this->description	= $this->db->lasterror();
				$this->db->free($result_search);
				return -3;
			}
		}
	}
