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
	* 	\file		./infrassupprice/script/interface.php
	* 	\ingroup	InfraS
	* 	\brief		Page to interface the module InfraS supplier price
	************************************************/

	// Dolibarr environment *************************
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);
	}
	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infrassupprice/class/infrassupprice.class.php');

	// Access control *******************************
	if (!$user->hasRight('infrassupprice', 'update')) {
		accessforbidden();
	}

    $get	= GETPOST('get', 'aZ09');
    $put	= GETPOST('put', 'aZ09');
    switch($put) {
		case 'updateprice':
			ob_start();
			$product	= new InfraSSupPrice($db);
			$id_prod	= GETPOST('idprod', 'int');
			$ref_search	= GETPOST('ref_search', 'alpha');
			$product->fetch($id_prod, $ref_search);
			$fourn		= new Fournisseur($db);
			$fourn->fetch(GETPOST('fk_supplier', 'int'));
			$tva_tx		= str_replace('*', '', GETPOST('tvatx', 'alpha'));
			if (!preg_match('/\((.*)\)/', $tva_tx)) {
				$tva_tx = price2num($tva_tx);
			} else {
				$tva_tx = (float) $tva_tx;
			}
			$ret	= $product->InfraS_update_buyprice ($fourn,	// $fourn
														$id_prod,	// $id_prod
														GETPOST('ref', 'alpha'),	// $ref_fourn
														$tva_tx,	// $tva_tx
														price2num(GETPOST('currency_tx', 'alpha')),	// $currency_tx
														GETPOST('currency_code', 'alpha'),	// $currency_code
														price2num(GETPOST('currency_unitprice', 'alpha')),	// $currency_unitbuyprice
														price2num(GETPOST('unitprice', 'alpha')),	// $unitbuyprice
														price2num(GETPOST('qty', 'nohtml')),	// $qty
														price2num(GETPOST('currency_price', 'alpha')),	// $currency_buyprice
														price2num(GETPOST('price', 'alpha')),	// $buyprice
														price2num(GETPOST('rem_percent', 'alpha'))	// $remise_percent = 0
														);
			ob_clean();
			print json_encode(array('id' => $ret, 'desc' => $product->description));
		break;
    }
