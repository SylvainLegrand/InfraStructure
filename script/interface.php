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
	* 	\file		./subtotal/script/interface.php
	* 	\ingroup	Subtotal
	* 	\brief		Page to interface the module Subtotal
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';
	if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);

	// Libraries ************************************
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/commande/class/commande.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	dol_include_once('/fourn/class/fournisseur.commande.class.php');
	dol_include_once('/fourn/class/fournisseur.facture.class.php');
	dol_include_once('/subtotal/class/subtotal.class.php');
	dol_include_once('/subtotal/class/subTotalJsonResponse.class.php');
	dol_include_once('/subtotal/lib/subtotal.lib.php');
	dol_include_once('/supplier_proposal/class/supplier_proposal.class.php');

	$langs->load('subtotal@subtotal');

	// Access control
	restrictedArea($user, 'subtotal');

	// Whitelist of allowed element class names
	$TAllowedElements	= array('propal'				=> 'Propal',
								'commande'				=> 'Commande',
								'facture'				=> 'Facture',
								'supplier_proposal'		=> 'SupplierProposal',
								'order_supplier'		=> 'CommandeFournisseur',
								'invoice_supplier'		=> 'FactureFournisseur',
								// Aliases (class names as sent by JS)
								'Propal'				=> 'Propal',
								'Commande'				=> 'Commande',
								'Facture'				=> 'Facture',
								'SupplierProposal'		=> 'SupplierProposal',
								'CommandeFournisseur'	=> 'CommandeFournisseur',
								'FactureFournisseur'	=> 'FactureFournisseur',
							);

	$get	= GETPOST('get', 'aZ09');
	$set	= GETPOST('set', 'aZ09');

	switch ($get) {
		//récupération des lignes contenues dans un titre sous total en fonction d'un élément et de la ligne de titre concernée
		case 'getLinesFromTitle':
			global $db;
			$element	= GETPOST('element', 'aZ09');
			$element_id	= GETPOSTINT('elementid');
			$id_line	= GETPOSTINT('lineid');
			if (empty($TAllowedElements[$element]) || $element_id <= 0) {
				http_response_code(400);
				echo json_encode(array('error' => 'Invalid element'));
				break;
			}
			$className	= $TAllowedElements[$element];
			$object		= new $className($db);
			$object->fetch($element_id);
			if(!empty($object->lines)) {
				$TRes	= array();
				foreach ($object->lines as $line) {
					if ($line->id == $id_line) {
						$title_line		= $line;
						$subline_line	= TSubtotal::getSubLineOfTitle($object, $title_line->rang);
						break;
					}
				}
				foreach ($object->lines as $line) {
					$parent_line	= TSubtotal::getParentTitleOfLine($object, $line->rang);
					if(!empty($subline_line)) {
						if ($line->product_type != 9 && $line->rang > $title_line->rang && $line->rang < $subline_line->rang) {
							$TRes[$parent_line->id][]	= $line->id;
						}
					} else {
						if ($line->product_type != 9 && $line->rang > $title_line->rang) {
							$TRes[$parent_line->id][]	= $line->id;
						}
					}
				}
			}
			echo json_encode($TRes);
			break;
		default:
			break;
	}
	switch ($set) {
		case 'updateLineNC': // Gestion du Compris/Non Compris via les titres et/ou lignes
			echo json_encode( _updateLineNC(GETPOST('element', 'aZ09'), GETPOSTINT('elementid'), GETPOSTINT('lineid'), GETPOSTINT('subtotal_nc')) );
		break;
		//Mise � jour de la donn�e "hideblock" sur une ligne titre afin de savoir si le bloc doit �tre cach� ou pas
		case 'update_hideblock_data':
			$jsonResponse = new SubTotalJsonResponse();
			_updateHideBlockData($jsonResponse);
			echo $jsonResponse->getJsonResponse();
		break;
		case 'updateall_hideblock_data' :
			$element	= GETPOST('element', 'aZ09');
			$element_id	= GETPOSTINT('elementid');
			$value		= GETPOSTINT('value');
			if (empty($TAllowedElements[$element]) || $element_id <= 0) {
				http_response_code(400);
				echo json_encode(array('error' => 'Invalid element'));
				break;
			}
			$className	= $TAllowedElements[$element];
			$object		= new $className($db);
			$object->fetch($element_id);
			if(!empty($object->lines)) {
				foreach ($object->lines as $line) {
					if ($line->product_type == 9) {
						$line->fetch_optionals();
						$line->array_options['options_hideblock'] = $value;
						$line->insertExtraFields();
					}
				}
			}
		break;
		default:
		break;
	}

	/**
	* @param SubTotalJsonResponse $jsonResponse
	* @return bool|void
	*/
	function _updateHideBlockData($jsonResponse) {

		global  $db, $langs, $TAllowedElements;

		$data		= GETPOST('data', 'array');
		$element	= isset($data['element']) ? $data['element'] : '';
		$element_id	= isset($data['element_id']) ? (int) $data['element_id'] : 0;
		if (empty($element) || empty($TAllowedElements[$element])) {
			$jsonResponse->msg		= $langs->trans('ElementMissing');
			$jsonResponse->result	= 0;
			return false;
		}
		if ($element_id <= 0) {
			$jsonResponse->msg		= $langs->trans('ElementIdMissing');
			$jsonResponse->result	= 0;
			return false;
		}
		$titleStatusList	= isset($data['titleStatusList']) ? $data['titleStatusList'] : array();
		if (!empty($titleStatusList)) {
			$className		= $TAllowedElements[$element];
			$object			= new $className($db);
			if ($object->fetch($element_id) <= 0){
				$jsonResponse->msg		= $langs->trans('ErrorFetchingElement');
				$jsonResponse->result	= 0;
				return false;
			}
			if ($object->fetch($element_id) > 0 && !empty($object->lines)) {
				foreach ($object->lines as $line) {
					if ($line->product_type != 9) { // si ce n'est pas du sous total, skip
						continue;
					}
					foreach($titleStatusList as $lineStatus){
						if ($line->id == $lineStatus['id']) {
							$line->fetch_optionals();
							$line->array_options['options_hideblock'] = intval($lineStatus['status']);
							$line->insertExtraFields();
						}
					}
				}
			}
		}
		$jsonResponse->result = 1;
	}
