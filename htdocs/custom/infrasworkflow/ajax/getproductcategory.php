<?php
	/************************************************
	* Copyright (C) 2025-2026	Lucky Ranasolonirina - <technique@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasworkflow/ajax/getproductcategory.php
	* 	\ingroup	InfraS
	* 	\brief		AJAX endpoint to get product sub-category
	************************************************/

	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', '1');
	}
	if (!defined('NOREQUIREMENU')) {
		define('NOREQUIREMENU', '1');
	}
	if (!defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', '1');
	}
	if (!defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}

	require_once '../config.php';
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

	global $db, $user;

	// Security check
	if (!$user->hasRight('contrat', 'lire')) {
		http_response_code(403);
		print json_encode(array('error' => 'Permission denied'));
		exit;
	}

	$productId			= GETPOSTINT('productid');
	$parentCategoryId	= GETPOSTINT('parentcatid');

	$result = array('subcatid' => 0, 'subcatlabel' => '');

	if ($productId > 0 && $parentCategoryId > 0) {
		$tmpCat	= new Categorie($db);
		$cats	= $tmpCat->containing($productId, Categorie::TYPE_PRODUCT, 'object');
		if (is_array($cats)) {
			foreach ($cats as $c) {
				if ($c->fk_parent == $parentCategoryId) {
					$result['subcatid']		= (int) $c->id;
					$result['subcatlabel']	= $c->label;
					break;
				}
			}
		}
	}

	header('Content-Type: application/json');
	print json_encode($result);
