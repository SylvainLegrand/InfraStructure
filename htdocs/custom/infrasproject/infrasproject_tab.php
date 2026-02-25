<?php
	/************************************************
	* Copyright (C) 2018-2020	Jeremie Ter-Heide - <jeremie@ter-heide.fr>
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		../infrasproject/infrasproject_tab.php
	* 	\ingroup	InfraS
	* 	\brief		Consumption tab
	************************************************/

	// Libraries ************************************

	// Dolibarr environment *************************
	require './config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	dol_include_once('/infrasproject/class/infrasproject.class.php');
	dol_include_once('/infrasproject/class/html.forminfrasproject.class.php');

	// Translations *********************************
	$langs->loadLangs(array('stocks', 'products', 'productbatch', 'companies', 'infrasproject@infrasproject'));

	// Security check *******************************
	$socid = 0;
	if ($user->socid > 0) $socid = $user->socid;
	$id			= GETPOST('id', 'int');
	$ref		= GETPOST('ref', 'alpha');
	$objectType	= GETPOST('objectType', 'alpha');
	$action		= GETPOST('action', 'aZ09');
	if ($id == '' && $ref == '') {
		setEventMessage($langs->trans('ErrorBadParameters'), 'errors');
		header('Location: list.php');
		exit();
	}
	// Actions **************************************
	$form			= new Form($db);
	$formcosumption	= new FormInfrasproject($db);
	$userstatic		= new User($db);
	$object			= new Project($db);
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();
	$conso			= new InfraSProject($db);
	$product		= new Product($db);
	if ($action == 'conso' && empty(GETPOST('cancel'))) {
		$qty	= GETPOST('nbpiece', 'int');
		$datem	= dol_mktime(0, 0, 0, GETPOST('datem_month', 'int'), GETPOST('datem_day', 'int'), GETPOST('datem_year', 'int'));
		if (!empty($qty)) {
			$result	= $product->fetch(GETPOST('product', 'int'));
			if ($result > 0) {
				$linkToUser	= getDolGlobalInt('INFRASPROJECT_LINK_TO_USER', 0);
				$userlink	= GETPOST('userlink', 'int');
				if (!isModEnabled('productbatch') && !empty($linkToUser) && !empty($userlink)) {
					$userstatic->fetch($userlink);
					$batch_number	= $userstatic->firstname.' '.$userstatic->lastname;
				} else {
					$batch_number	= GETPOST('batch_number', 'alpha');
				}
				$result	= $conso->correct_stock($product->id,					// id
												$user,							// user
												GETPOST('id_entrepot', 'int'),	// entrepot
												$qty,							// nb piece
												1,								// Direction of movement:0=input (stock increase by a stock transfer), 1=output (stock decrease after by a stock transfer),2=output (stock decrease), 3=input (stock increase)
												GETPOST('label', 'alpha'),		// label
												0,								// price
												'',								// inventorycode
												GETPOST('objectType', 'alpha'),	// objectType
												$id,							// rowid of origin element
												$datem,							// Force date of movement (timestamp)
												GETPOST('eatby', 'alpha'),		// eat-by date. Will be used if lot does not exists yet and will be created.
												GETPOST('sellby', 'alpha'),		// sell-by date. Will be used if lot does not exists yet and will be created.
												$batch_number					// batch number or name of user link to consumption
												);
				if ($result > 0) {
					header('Location: infrasproject_tab.php?id='.$id.'&objectType=project');
					exit;
				}
			}
		}
	}

	// View *****************************************
	$page_name	= $langs->trans('InfraSProjectStockConsumption');
	llxHeader('', $page_name);

	// Configuration header *************************
	$head		= project_prepare_head($object);
	print dol_get_fiche_head($head, 'conso', $langs->trans('Project'), 0, 'project');

	// Page goes here *******************************
	if ($objectType == 'project') {
		$morehtmlref = '<div class = "refidno">';
		// Title
		$morehtmlref .= dol_escape_htmltag($object->title);
		// Thirdparty
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : ';
		if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
			$morehtmlref .= $object->thirdparty->getNomUrl(1, 'project');
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (!$user->hasRight('projet', 'all', 'lire')) {
			$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
			$object->next_prev_filter = " rowid IN (".$db->sanitize(count($objectsListId) ? join(',', array_keys($objectsListId)) : '0').")";
		}
	}

	$linkback	= '<a href = "'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';
	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&objectType='.$objectType);

	$formcosumption->showformwrite($user, 'project', $object);
	$formcosumption->showformview($user, $object);

	print dol_get_fiche_end();
	// End of page
	llxFooter();
	$db->close();
