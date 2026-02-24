<?php
	/************************************************
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
	* 	\file		./infrassearch/script/interface.php
	* 	\ingroup	InfraS
	* 	\brief		Page to interface the module InfraSSearch
	************************************************/

	// Dolibarr environment *************************
	if (!defined("NOCSRFCHECK")) {
		define('NOCSRFCHECK', 1);
	}
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);
	}

	require '../config.php';

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/class/knowledgerecord.class.php';
	require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
	$classPaths	= array('/societe/class/address.class.php',
						'/infraspackplus/class/address.class.php',
						'/equipement/class/equipement.class.php',
						'/ndfp/class/ndfp.class.php',
						'/contacttracking/class/contacttracking.class.php',
						'/domain/class/domain.class.php',
						'/hosting/class/host.class.php',
						'/ticketsup/class/ticketsup.class.php',
						'/ticketsup/class/ticketsuplogs.class.php',
						'/abricot/inc.core.php',
						'/propalehistory/class/propaleHist.class.php',
						'/rmindr/class/rmindr.class.php',
						'/factory/class/factory.class.php'
						);
	foreach ($classPaths as $classPath) {
		$testClass	= dol_buildpath($classPath, 0, 1);
		if ($testClass) {
			require_once $testClass;
		}
	}
	$langs->load('infrassearch@infrassearch');
	$langs->load('orders');

	$get	= GETPOST('get');
	switch ($get) {
		case 'search':
			// from tools page
			_search(GETPOST('type'), GETPOST('keyword'));
		break;
		case 'search-all':
			// from top right menu input
			$listTObjectType		= array();
			$listTObjectType		= explode(',', getDolGlobalString('INFRASSEARCH_LISTTOBJECTTYPE', ''));
			$validListTObjectType	= array();
			foreach($listTObjectType as $TObjectType) {
				$validTObjectType	= 'INFRASSEARCH_MOD_'.strtoupper($TObjectType);
				if (getDolGlobalInt($validTObjectType, 0) == 1) {
					$validListTObjectType[]	= $TObjectType;
				}
			}
			// --- TRI PAR ORDRE PERSONNALISÉ ---
			$orderModules 	= array();
			foreach ($validListTObjectType as $TObjectType) {
				$order 						= getDolGlobalInt('INFRASSEARCH_POS_' . strtoupper($TObjectType), 0);
				// Si pas d'ordre défini, on met à la fin
				$orderModules[$TObjectType]	= $order > 0 ? $order : 999;
			}
			// Trie par valeur croissante (ordre choisi par l'utilisateur)
			asort($orderModules);
			$validListTObjectType 	= array_keys($orderModules);
			$TResult 				= array();
			foreach($validListTObjectType as $TObjectTypeValid) {
				$TResult[$langs->transnoentities(ucfirst($TObjectTypeValid))] = _search($TObjectTypeValid, GETPOST('keywords'), true);
			}
			echo json_encode($TResult);
		break;
	}

	/**
	*	Make the research
	*	@param		string		$TObjectTypeValid	name of valid module for which search is enabled
	*	@param		string		$keyword			word(s) to search
	*	@param		boolean		$asArray			true when the search comes from the menu, false when it comes from the search page (tools)
	*	@return		array|void						array of results | void if $asArray is false (used of print)
	**/
	function _search($TObjectTypeValid, $keyword, $asArray = false)
	{
		global $db, $conf, $langs;

		$beforeV19			= version_compare(DOL_VERSION, '19.0.0') < 0;
		$InfraSPlusV1561	= isModEnabled('infraspackplus') && version_compare(getDolGlobalString('INFRASPLUS_MAIN_VERSION', ''), '15.6.1') >= 0;
		$onlyInEntity		= getDolGlobalInt('INFRASSEARCH_ONLY_IN_ENTITY', 0);
		$show_find_field	= getDolGlobalString('INFRASSEARCH_SHOW_FIND_FIELD', '');
		$nbRows				= getDolGlobalInt('INFRASSEARCH_NB_ROWS', 3);
		$sort				= getDolGlobalInt('INFRASSEARCH_SORT', 0);
		$order				= getDolGlobalString('INFRASSEARCH_ORDER', 'DESC');
		$TResult			= array();
		$order_field		= '';
		$customtabelem		= null;
		switch ($TObjectTypeValid) {
			case 'agenda':
				$tables			= array($db->prefix().'actioncomm', $db->prefix().'actioncomm_extrafields', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'ActionComm';
				$complete_label = '';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'actioncomm_extrafields ON ('.$db->prefix().'actioncomm.id = '.$db->prefix().'actioncomm_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'actioncomm.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'actioncomm.fk_contact = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'actioncomm.id';
				$order_field	= $db->prefix().'actioncomm.datep';
			break;
			case 'categorie':
				$tables			= array($db->prefix().'categorie', $db->prefix().'categories_extrafields');
				$objname		= 'Categorie';
				$complete_label	= 'description';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'categories_extrafields ON ('.$db->prefix().'categorie.rowid = '.$db->prefix().'categories_extrafields.fk_object)';
				$id_field		= $db->prefix().'categorie.rowid';
			break;
			case 'commande':
				$tables			= array($db->prefix().'commande', $db->prefix().'commande_extrafields', $db->prefix().'commandedet', $db->prefix().'commandedet_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Commande';
				$customtabelem	= array('element' => 'commande', 'maintabl' => 'commande');
				$complete_label = 'ref_client';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'commande_extrafields ON ('.$db->prefix().'commande.rowid = '.$db->prefix().'commande_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'commandedet ON ('.$db->prefix().'commande.rowid = '.$db->prefix().'commandedet.fk_commande)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'commandedet_extrafields ON ('.$db->prefix().'commandedet.rowid = '.$db->prefix().'commandedet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'commandedet.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'commande.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "commande")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'commande.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'commande.rowid';
				$order_field	= $db->prefix().'commande.date_commande';
			break;
			case 'commandefournisseur':
				$tables			= array($db->prefix().'commande_fournisseur', $db->prefix().'commande_fournisseur_extrafields', $db->prefix().'commande_fournisseurdet', $db->prefix().'commande_fournisseurdet_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'CommandeFournisseur';
				$customtabelem	= array('element' => 'supplier_order', 'maintabl' => 'commande_fournisseur');
				$complete_label = 'ref_supplier';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'commande_fournisseur_extrafields ON ('.$db->prefix().'commande_fournisseur.rowid = '.$db->prefix().'commande_fournisseur_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'commande_fournisseurdet ON ('.$db->prefix().'commande_fournisseur.rowid = '.$db->prefix().'commande_fournisseurdet.fk_commande)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'commande_fournisseurdet_extrafields ON ('.$db->prefix().'commande_fournisseurdet.rowid = '.$db->prefix().'commande_fournisseurdet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'commande_fournisseurdet.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'commande_fournisseur.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "order_supplier")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'commande_fournisseur.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'commande_fournisseur.rowid';
				$order_field	= $db->prefix().'commande_fournisseur.date_creation';
			break;
			case 'contact':
				$tables			= array($db->prefix().'socpeople', $db->prefix().'socpeople_extrafields');
				$objname		= 'Contact';
				$customtabelem	= array('element' => 'contact', 'maintabl' => 'socpeople');
				$complete_label	= 'socname';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'socpeople_extrafields ON ('.$db->prefix().'socpeople.rowid = '.$db->prefix().'socpeople_extrafields.fk_object)';
				$id_field		= $db->prefix().'socpeople.rowid';
			break;
			case 'contacttracking':
				$tables			= array($db->prefix().'contacttracking', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Contacttracking';
				$complete_label = 'comment';
				$sql_join		= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'contacttracking.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= 'LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'contacttracking.fk_contact = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'contacttracking.rowid';
				$order_field	= $db->prefix().'contacttracking.date_creation';
			break;
			case 'contrat':
				$tables			= array($db->prefix().'contrat', $db->prefix().'contrat_extrafields', $db->prefix().'contratdet', $db->prefix().'contratdet_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Contrat';
				$customtabelem	= array('element' => 'contract', 'maintabl' => 'contrat');
				$complete_label = 'ref_customer';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'contrat_extrafields ON ('.$db->prefix().'contrat.rowid = '.$db->prefix().'contrat_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'contratdet ON ('.$db->prefix().'contrat.rowid = '.$db->prefix().'contratdet.fk_contrat)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'contratdet_extrafields ON ('.$db->prefix().'contratdet.rowid = '.$db->prefix().'contratdet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'contratdet.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'contrat.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "contrat")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'contrat.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'contrat.rowid';
				$order_field	= $db->prefix().'contrat.date_contrat';
			break;
			case 'domain':
				$tables			= array($db->prefix().'domain', $db->prefix().'domain_extrafields', $db->prefix().'societe');
				$objname		= 'Domain';
				$complete_label = 'label';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'domain_extrafields ON ('.$db->prefix().'domain.rowid = '.$db->prefix().'domain_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'domain.fk_soc = '.$db->prefix().'societe.rowid)';
				$id_field		= $db->prefix().'domain.rowid';
				$order_field	= $db->prefix().'domain.date_creation';
			break;
			case 'equipement':
				$tables			= array($db->prefix().'equipement', $db->prefix().'equipement_extrafields', $db->prefix().'equipementevt', $db->prefix().'equipementevt_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Equipement';
				$customtabelem	= array('element' => 'equipement', 'maintabl' => 'equipement');
				$complete_label = '';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'equipement_extrafields ON ('.$db->prefix().'equipement.rowid = '.$db->prefix().'equipement_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'equipementevt ON ('.$db->prefix().'equipement.rowid = '.$db->prefix().'equipementevt.fk_equipement)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'equipementevt_extrafields ON ('.$db->prefix().'equipementevt.rowid = '.$db->prefix().'equipementevt_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'equipement.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'equipement.fk_soc_fourn = '.$db->prefix().'societe.rowid OR '.$db->prefix().'equipement.fk_soc_client = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "equipement")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'equipement.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'equipement.rowid';
			break;
			case 'expedition':
				$tables			= array($db->prefix().'expedition', $db->prefix().'expedition_extrafields', $db->prefix().'expeditiondet', $db->prefix().'expeditiondet_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Expedition';
				$customtabelem	= array('element' => 'delivery', 'maintabl' => 'expedition');
				$complete_label = 'ref_customer';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'expedition_extrafields ON ('.$db->prefix().'expedition.rowid = '.$db->prefix().'expedition_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'expeditiondet ON ('.$db->prefix().'expedition.rowid = '.$db->prefix().'expeditiondet.fk_expedition)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'expeditiondet_extrafields ON ('.$db->prefix().'expeditiondet.rowid = '.$db->prefix().'expeditiondet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'commandedet ON ('.$db->prefix().'expeditiondet.fk_origin_line = '.$db->prefix().'commandedet.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'commandedet.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'expedition.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "expedition")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'expedition.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'expedition.rowid';
				$order_field	= $db->prefix().'expedition.date_creation';
			break;
			case 'expensereport':
 				$tables			= array($db->prefix().'expensereport', $db->prefix().'expensereport_extrafields', $db->prefix().'expensereport_det', $db->prefix().'c_type_fees', $db->prefix().'user');
 				$objname		= 'ExpenseReport';
 				$customtabelem	= array('element' => 'expensereport', 'maintabl' => 'expensereport');
 				$complete_label = '';
 				$sql_join		= 'LEFT JOIN '.$db->prefix().'expensereport_extrafields ON ('.$db->prefix().'expensereport.rowid = '.$db->prefix().'expensereport_extrafields.fk_object)';
 				$sql_join		.= ' LEFT JOIN '.$db->prefix().'expensereport_det ON ('.$db->prefix().'expensereport.rowid = '.$db->prefix().'expensereport_det.fk_expensereport)';
 				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_fees ON ('.$db->prefix().'expensereport_det.fk_c_type_fees = '.$db->prefix().'c_type_fees.id)';
 				$sql_join		.= ' LEFT JOIN '.$db->prefix().'user ON ('.$db->prefix().'expensereport.fk_user_author = '.$db->prefix().'user.rowid)';
 				$id_field		= $db->prefix().'expensereport.rowid';
 				$order_field	= $db->prefix().'expensereport.date_create';
			break;
			case 'factory':
				$tables			= array($db->prefix().'factory', $db->prefix().'factory_extrafields', $db->prefix().'factorydet');
				$objname		= 'Factory';
				$complete_label = 'description';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'factory_extrafields ON ('.$db->prefix().'factory.rowid = '.$db->prefix().'factory_extrafields.fk_object)';
				$sql_join		.= 'LEFT JOIN '.$db->prefix().'factorydet ON ('.$db->prefix().'factory.rowid = '.$db->prefix().'factorydet.fk_factory)';
				$id_field		= $db->prefix().'factory.rowid';
				$order_field	= $db->prefix().'factory.datec';
			break;
			case 'facture':
				$tables			= array($db->prefix().'facture', $db->prefix().'facture_extrafields', $db->prefix().'facturedet', $db->prefix().'facturedet_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Facture';
				$customtabelem	= array('element' => 'invoice', 'maintabl' => 'facture');
				$complete_label = 'ref_client';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'facture_extrafields ON ('.$db->prefix().'facture.rowid = '.$db->prefix().'facture_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'facturedet ON ('.$db->prefix().'facture.rowid = '.$db->prefix().'facturedet.fk_facture)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'facturedet_extrafields ON ('.$db->prefix().'facturedet.rowid = '.$db->prefix().'facturedet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'facturedet.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'facture.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "facture")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'facture.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'facture.rowid';
				$order_field	= $db->prefix().'facture.datef';
			break;
			case 'facturefournisseur':
				$tables			= array($db->prefix().'facture_fourn', $db->prefix().'facture_fourn_extrafields', $db->prefix().'facture_fourn_det', $db->prefix().'facture_fourn_det_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'FactureFournisseur';
				$customtabelem	= array('element' => 'supplier_invoice', 'maintabl' => 'facture_fourn');
				$complete_label = 'ref_supplier';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'facture_fourn_extrafields ON ('.$db->prefix().'facture_fourn.rowid = '.$db->prefix().'facture_fourn_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'facture_fourn_det ON ('.$db->prefix().'facture_fourn.rowid = '.$db->prefix().'facture_fourn_det.fk_facture_fourn)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'facture_fourn_det_extrafields ON ('.$db->prefix().'facture_fourn_det.rowid = '.$db->prefix().'facture_fourn_det_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'facture_fourn_det.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'facture_fourn.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "invoice_supplier")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'facture_fourn.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'facture_fourn.rowid';
				$order_field	= $db->prefix().'facture_fourn.datec';
			break;
			case 'ficheinter':
				$tables			= array($db->prefix().'fichinter', $db->prefix().'fichinter_extrafields', $db->prefix().'fichinterdet', $db->prefix().'fichinterdet_extrafields', $db->prefix().'societe', $db->prefix().'socpeople', $db->prefix().'fichinterdet_rec', $db->prefix().'product');
				$objname		= 'Fichinter';
				$customtabelem	= array('element' => 'intervention', 'maintabl' => 'fichinter');
				$complete_label = 'description';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'fichinter_extrafields ON ('.$db->prefix().'fichinter.rowid = '.$db->prefix().'fichinter_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'fichinterdet ON ('.$db->prefix().'fichinter.rowid = '.$db->prefix().'fichinterdet.fk_fichinter)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'fichinterdet_rec ON ('.$db->prefix().'fichinter.rowid = '.$db->prefix().'fichinterdet_rec.fk_fichinter)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'fichinterdet_extrafields ON ('.$db->prefix().'fichinterdet.rowid = '.$db->prefix().'fichinterdet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'fichinterdet_rec.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'fichinter.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "fichinter")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'fichinter.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'fichinter.rowid';
				$order_field	= $db->prefix().'fichinter.datec';
			break;
			case 'hosting':
				$tables			= array($db->prefix().'host', $db->prefix().'host_extrafields', $db->prefix().'societe');
				$objname		= 'Host';
				$complete_label = 'label';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'host_extrafields ON ('.$db->prefix().'host.rowid = '.$db->prefix().'host_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'host.fk_soc = '.$db->prefix().'societe.rowid)';
				$id_field		= $db->prefix().'host.rowid';
				$order_field	= $db->prefix().'host.date_creation';
			break;
			case 'knowledgemanagement':
				$tables			= array($db->prefix().'knowledgemanagement_knowledgerecord', $db->prefix().'knowledgemanagement_knowledgerecord_extrafields');
				$objname		= 'KnowledgeRecord';
				$complete_label = 'question';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'knowledgemanagement_knowledgerecord_extrafields ON ('.$db->prefix().'knowledgemanagement_knowledgerecord.rowid = '.$db->prefix().'knowledgemanagement_knowledgerecord_extrafields.fk_object)';
				$id_field		= $db->prefix().'knowledgemanagement_knowledgerecord.rowid';
				$order_field	= $db->prefix().'knowledgemanagement_knowledgerecord.date_creation';
			break;
			case 'ndfp':
				$tables			= array($db->prefix().'ndfp', $db->prefix().'ndfp_det');
				$objname		= 'Ndfp';
				$complete_label = 'description';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'ndfp_det ON ('.$db->prefix().'ndfp.rowid = '.$db->prefix().'ndfp_det.fk_ndfp)';
				$id_field		= $db->prefix().'ndfp.rowid';
				$order_field	= $db->prefix().'ndfp.datec';
			break;
			case 'product':
				$tables			= array($db->prefix().'product', $db->prefix().'product_extrafields');
				$objname		= 'Product';
				$customtabelem	= array('element' => 'product', 'maintabl' => 'product');
				$complete_label	= 'label';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'product_extrafields ON ('.$db->prefix().'product.rowid = '.$db->prefix().'product_extrafields.fk_object)';
				$id_field		= $db->prefix().'product.rowid';
			break;
			case 'projet':
				$tables			= array($db->prefix().'projet', $db->prefix().'projet_extrafields', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Project';
				$customtabelem	= array('element' => 'project', 'maintabl' => 'projet');
				$complete_label	= 'title';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'projet_extrafields ON ('.$db->prefix().'projet.rowid = '.$db->prefix().'projet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'projet.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "project")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'projet.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'projet.rowid';
				$order_field	= $db->prefix().'projet.datec';
			break;
			case 'propal':
				$tables			= array($db->prefix().'propal', $db->prefix().'propal_extrafields', $db->prefix().'propaldet', $db->prefix().'propaldet_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Propal';
				$customtabelem	= array('element' => 'propal', 'maintabl' => 'propal');
				$complete_label = 'ref_client';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'propal_extrafields ON ('.$db->prefix().'propal.rowid = '.$db->prefix().'propal_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'propaldet ON ('.$db->prefix().'propal.rowid = '.$db->prefix().'propaldet.fk_propal)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'propaldet_extrafields ON ('.$db->prefix().'propaldet.rowid = '.$db->prefix().'propaldet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'propaldet.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'propal.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "propal")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'propal.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'propal.rowid';
				$order_field	= $db->prefix().'propal.datep';
			break;
			case 'propalehistory':
				$tables			= array($db->prefix().'propal', $db->prefix().'propal_extrafields', $db->prefix().'propaldet', $db->prefix().'propaldet_extrafields', $db->prefix().'propale_history');
				$objname		= 'PropalHist';
				$complete_label = 'ref_client';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'propal_extrafields ON ('.$db->prefix().'propal.rowid = '.$db->prefix().'propal_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'propaldet ON ('.$db->prefix().'propal.rowid = '.$db->prefix().'propaldet.fk_propal)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'propaldet_extrafields ON ('.$db->prefix().'propaldet.rowid = '.$db->prefix().'propaldet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'propale_history ON ('.$db->prefix().'propal.rowid = '.$db->prefix().'propale_history.fk_propale)';
				$id_field		= $db->prefix().'propal.rowid';
				$order_field	= $db->prefix().'propale_history.date_cre';
			break;
			case 'rmindr':
				$tables			= array($db->prefix().'rmindr');
				$objname		= 'rmindr';
				$complete_label = 'description';
				$sql_join		= '';
				$id_field		= $db->prefix().'rmindr.rowid';
				$order_field	= $db->prefix().'dateo';
			break;
			case 'societe':
				$tables			= array($db->prefix().'societe', $db->prefix().'societe_extrafields');
				if ($beforeV19) {
					$tables[]	= $db->prefix().'societe_address';
				}
				if ($InfraSPlusV1561) {
					$tables[]	= $db->prefix().'infraspackplus_societe_address';
				}
				$objname		= 'Societe';
				$customtabelem	= array('element' => 'thirdparty', 'maintabl' => 'societe');
				$complete_label	= '';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'societe_extrafields ON ('.$db->prefix().'societe.rowid = '.$db->prefix().'societe_extrafields.fk_object)';
				if ($beforeV19) {
					$sql_join	.= ' LEFT JOIN '.$db->prefix().'societe_address ON ('.$db->prefix().'societe.rowid = '.$db->prefix().'societe_address.fk_soc)';
				}
				if ($InfraSPlusV1561) {
					$sql_join	.= ' LEFT JOIN '.$db->prefix().'infraspackplus_societe_address ON ('.$db->prefix().'societe.rowid = '.$db->prefix().'infraspackplus_societe_address.fk_soc)';
				}
				$id_field		= $db->prefix().'societe.rowid';
			break;
			case 'supplier_proposal':
				$tables			= array($db->prefix().'supplier_proposal', $db->prefix().'supplier_proposal_extrafields', $db->prefix().'supplier_proposaldet', $db->prefix().'supplier_proposaldet_extrafields', $db->prefix().'product', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'SupplierProposal';
				$complete_label = '';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'supplier_proposal_extrafields ON ('.$db->prefix().'supplier_proposal.rowid = '.$db->prefix().'supplier_proposal_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'supplier_proposaldet ON ('.$db->prefix().'supplier_proposal.rowid = '.$db->prefix().'supplier_proposaldet.fk_supplier_proposal)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'supplier_proposaldet_extrafields ON ('.$db->prefix().'supplier_proposaldet.rowid = '.$db->prefix().'supplier_proposaldet_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'product ON ('.$db->prefix().'supplier_proposaldet.fk_product = '.$db->prefix().'product.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'supplier_proposal.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "supplier_proposal")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'supplier_proposal.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'supplier_proposal.rowid';
				$order_field	= $db->prefix().'supplier_proposal.datec';
			break;
			case 'task':
				$tables			= array($db->prefix().'projet_task', $db->prefix().'projet_task_extrafields', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Task';
				$complete_label = 'label';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'projet_task_extrafields ON ('.$db->prefix().'projet_task.rowid = '.$db->prefix().'projet_task_extrafields.fk_object)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'projet ON ('.$db->prefix().'projet_task.fk_projet = '.$db->prefix().'projet.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'projet.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "project_task")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'projet_task.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'projet_task.rowid';
				$order_field	= $db->prefix().'projet_task.datec';
			break;
			case 'ticketsup':
				$tables			= array($db->prefix().'ticketsup', $db->prefix().'ticketsup_extrafields', $db->prefix().'ticketsup_logs', $db->prefix().'ticketsup_msg', $db->prefix().'societe', $db->prefix().'socpeople');
				$objname		= 'Ticketsup';
				$complete_label = 'subject';
				$sql_join		= 'LEFT JOIN '.$db->prefix().'ticketsup_extrafields ON ('.$db->prefix().'ticketsup.rowid = '.$db->prefix().'ticketsup_extrafields.fk_object)';
				$sql_join		.= 'LEFT JOIN '.$db->prefix().'ticketsup_logs ON ('.$db->prefix().'ticketsup.track_id = '.$db->prefix().'ticketsup_logs.fk_track_id)';
				$sql_join		.= 'LEFT JOIN '.$db->prefix().'ticketsup_msg ON ('.$db->prefix().'ticketsup.track_id = '.$db->prefix().'ticketsup_msg.fk_track_id)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'societe ON ('.$db->prefix().'ticketsup.fk_soc = '.$db->prefix().'societe.rowid)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'c_type_contact ON ('.$db->prefix().'c_type_contact.element = "ticketsup")';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'element_contact ON ('.$db->prefix().'ticketsup.rowid = '.$db->prefix().'element_contact.element_id AND '.$db->prefix().'c_type_contact.rowid = '.$db->prefix().'element_contact.fk_c_type_contact)';
				$sql_join		.= ' LEFT JOIN '.$db->prefix().'socpeople ON ('.$db->prefix().'element_contact.fk_socpeople = '.$db->prefix().'socpeople.rowid)';
				$id_field		= $db->prefix().'ticketsup.rowid';
				$order_field	= $db->prefix().'ticketsup.datec';
			break;
		}
		$sql_where	= ' 0 ';
		if (isModEnabled('customtabs') && is_array($customtabelem)) {
			$restab		= $db->query('SELECT DISTINCT '.$db->prefix().'customtabs.tablename FROM '.$db->prefix().'customtabs WHERE '.$db->prefix().'customtabs.element LIKE "'.$customtabelem['element'].'"');
			while($tab = $db->fetch_object($restab)) {
				$tables[]	= $db->prefix().'cust_'.$tab->tablename.'_extrafields';
				$sql_join	.= ' LEFT JOIN '.$db->prefix().'cust_'.$tab->tablename.'_extrafields ON ('.$db->prefix().$customtabelem['maintabl'].'.rowid = '.$db->prefix().'cust_'.$tab->tablename.'_extrafields.fk_object)';
			}
		}
		// Normalisation du mot-clé pour la recherche de numéros de téléphone
		// On extrait uniquement les chiffres du mot-clé
		$keywordPhoneDigits	= preg_replace('/[^\d]/', '', $keyword);
		// Le mot-clé ressemble à un numéro de téléphone si composé uniquement de chiffres, +, espaces, tirets, points, parenthèses, /
		$looksLikePhone		= !empty($keywordPhoneDigits) && strlen($keywordPhoneDigits) >= 5
								&& preg_match('/^[\+\d\s\-\.\(\)\/]+$/', trim($keyword));
		// Recherche croisée local/international pour Madagascar
		$keywordPhoneLocal	= '';
		if ($looksLikePhone) {
			if (strpos($keywordPhoneDigits, '00261') === 0 && strlen($keywordPhoneDigits) >= 14) {
				// 00261 34 ... → 034 ... (format local)
				$keywordPhoneLocal	= '0'.substr($keywordPhoneDigits, 5);
			} elseif (strpos($keywordPhoneDigits, '261') === 0 && strlen($keywordPhoneDigits) >= 12) {
				// +261 34 ... → 034 ... (format local)
				$keywordPhoneLocal	= '0'.substr($keywordPhoneDigits, 3);
			} elseif (strpos($keywordPhoneDigits, '0') === 0 && strlen($keywordPhoneDigits) >= 10) {
				// 034 ... → 26134 ... (format international sans +)
				$keywordPhoneLocal	= '261'.substr($keywordPhoneDigits, 1);
			}
		}
		$escapedKeyword			= $db->escape($db->escapeforlike($keyword));
		$escapedKeyPhDigits		= $db->escape($db->escapeforlike($keywordPhoneDigits));
		$escapedKeyPhLocal		= !empty($keywordPhoneLocal) ? $db->escape($db->escapeforlike($keywordPhoneLocal)) : '';

		// Pré-calculer les conversions de type une seule fois (au lieu de dans chaque itération de la boucle interne)
		$d_keyword	= _isDate($keyword);
		$i_keyword	= 0;
		if (!$looksLikePhone && is_numeric($keyword)) {
			$i_keyword	= (double) $keyword;
			if ($i_keyword > 2147483647 || empty($i_keyword)) {
				$i_keyword	= 0;
			}
		}
		$escapedKeywordDate	= !empty($d_keyword) ? $db->escape($db->escapeforlike($keyword)) : '';

		// Cache statique des résultats DESCRIBE pour éviter les requêtes répétées
		// sur les mêmes tables (societe, socpeople, product sont jointes par de nombreux modules)
		static $describeCache = array();

		// Colonnes techniques à exclure de la recherche (jamais pertinentes pour l'utilisateur)
		static $skipColumnsMap = null;
		if ($skipColumnsMap === null) {
			$skipColumnsMap	= array_flip(array(
				'rowid', 'entity', 'import_key', 'model_pdf', 'last_main_doc',
				'extraparams', 'fk_object', 'tms',
				'multicurrency_code', 'multicurrency_tx', 'fk_multicurrency',
				'rang', 'special_code', 'fk_unit', 'fk_parent_line',
				'fk_user_creat', 'fk_user_modif', 'fk_user_valid',
				'fk_user_author', 'fk_user_approve', 'fk_user_closing'
			));
		}

		foreach ($tables as $table) {
			// Utiliser le cache DESCRIBE si disponible
			if (!isset($describeCache[$table])) {
				$resDesc	= $db->query('DESCRIBE '.$table);
				if (!$resDesc) {
					$describeCache[$table]	= false;
					continue;
				}
				$describeCache[$table]	= array();
				while ($tbl = $db->fetch_object($resDesc)) {
					$describeCache[$table][]	= $tbl;
				}
			}
			if ($describeCache[$table] === false) {
				continue;
			}
			foreach ($describeCache[$table] as $tbl) {
				$fieldname	= $tbl->Field;
				// Exclure les colonnes techniques et les clés étrangères (fk_*)
				if (isset($skipColumnsMap[$fieldname]) || strpos($fieldname, 'fk_') === 0) {
					continue;
				}
				if (strpos($tbl->Type, 'varchar') !== false || strpos($tbl->Type, 'text') !== false) {
					$sql_where	.= ' OR '.$table.'.'.$fieldname.' LIKE "%'.$escapedKeyword.'%"';
					// Recherche normalisée pour les champs téléphone/fax : comparaison en chiffres uniquement
					if ($looksLikePhone && preg_match('/phone|fax|mobile|tel/i', $fieldname)) {
						$stripPhone	= 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE('.$table.'.'.$fieldname.', " ", ""), "-", ""), ".", ""), "(", ""), ")", ""), "/", ""), "+", "")';
						$sql_where	.= ' OR '.$stripPhone.' LIKE "%'.$escapedKeyPhDigits.'%"';
						if (!empty($escapedKeyPhLocal)) {
							$sql_where	.= ' OR '.$stripPhone.' LIKE "%'.$escapedKeyPhLocal.'%"';
						}
					}
				} elseif (strpos($tbl->Type, 'int') !== false || strpos($tbl->Type, 'double') !== false || strpos($tbl->Type, 'float') !== false) {
					if (!empty($i_keyword)) {
						$sql_where	.= ' OR '.$table.'.'.$fieldname.' = '.((int) $i_keyword);
					}
				} elseif (strpos($tbl->Type, 'date') !== false || strpos($tbl->Type, 'time') !== false) {
					if (!empty($escapedKeywordDate)) {
						$sql_where	.= ' OR '.$table.'.'.$fieldname.' LIKE "'.$escapedKeywordDate.'%"';
					}
				} else {
					$sql_where	.= ' OR '.$table.'.'.$fieldname.' = "'.$db->escape($keyword).'"';
				}
			}
		}
		$sql_where	.= in_array($db->prefix().'product', $tables) ? ' OR '.$db->prefix().'product.ref LIKE "%'.$escapedKeyword.'%"' : '';
		$sql_where	.= in_array($db->prefix().'socpeople', $tables) ? ' OR CONCAT_WS(" ",'.$db->prefix().'socpeople.firstname, '.$db->prefix().'socpeople.lastname) LIKE "%'.$escapedKeyword.'%" OR CONCAT_WS(" ",'.$db->prefix().'socpeople.lastname, '.$db->prefix().'socpeople.firstname) LIKE "%'.$escapedKeyword.'%"' : '';
		$sql		= 'SELECT DISTINCT '.$id_field.' as rowid FROM '.$tables[0].' '.$sql_join.' WHERE ('.$sql_where.') ';
		$sql		.= !empty($onlyInEntity) ? 'AND '.$tables[0].'.entity = '.$conf->entity.' ' : '';
		$sql		.= !empty($sort) && !empty($order) && !empty($order_field) ? 'ORDER BY '.$order_field.' '.$order.' ' : '';
		$sql		.= 'LIMIT '.$nbRows.' ';
		$res		= $db->query($sql);
		if (!$res) {
			dol_syslog('InfraSSearch: SQL error for type = '.$TObjectTypeValid.' keyword = '.$keyword.' : '.$db->lasterror(), LOG_ERR);
		}
		$nb_results	= $res ? $db->num_rows($res) : 0;
		if (!$asArray) {	// from the search page (tools)
			print '<table class = "centpercent infrassearchnoborderspacing">
								<tr class = "liste_titre">
									<td colspan = "2" style = "padding: 2px 5px 2px 5px;"><span class = "badge">'.$nb_results.'</span>&nbsp;&nbsp;'.$langs->trans('InfraSSearchLib'.$objname).'</td>
								</tr>';
		}
		if ($nb_results == 0) {
			if (!$asArray) {	// from the search page (tools)
				print '<td colspan = "2" style = "padding: 2px 5px 2px 5px;">'.$langs->trans('InfraSSearchNoResult').'</td>';
			}
		} else {
			if (class_exists($objname)) {
				while($obj = $db->fetch_object($res)) {
					$o	= new $objname($db);
					$o->fetch($obj->rowid);
					if ($o->id <= 0) {
						continue;
					}
					$label	= $complete_label && !empty($o->$complete_label) ? ' ('.$o->$complete_label.')' : '';
					if ($objname == 'Categorie' && method_exists($o, 'getNomUrl')) {
						$ref	= trim($o->getNomUrl(2).' '.$o->label);
					}
					if ($objname == 'rmindr') {
						$ref	= trim($o->label);
					} elseif (method_exists($o, 'getNomUrl')) {
						$ref	= trim($o->getNomUrl(1));
					}
					if (method_exists($o, 'getLibStatut')) {
						$statut	= $o->getLibStatut(3);
					}
					$desc	= '';
					if ($show_find_field) {
						$keywordRegex	= preg_quote($keyword, '/');
						foreach($o as $k => $v) {
							if (is_string($v) && preg_match('/'.$keywordRegex.'/i', $v)) {
								$desc .= '<br/>'.$k.' : '.preg_replace('/'.$keywordRegex.'/i', '<span class = "highlight">'.$keyword.'</span>', $v);
							}
						}
					}
					preg_match_all('/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $ref, $match);
					$url	= is_array($match['href']) ? $match['href'][0] : $match['href'];
					if ($asArray) {	// from the menu
						$TResult[]	= array('categorie'		=> $langs->trans('InfraSSearchLib'.$objname),
											'label'			=> $ref,
											'label_clean'	=> strip_tags($ref).$label,
											'url'			=> $url,
											'desc'			=> $desc,
											'statut'		=> $statut
											);
					} else {	// from the search page (tools)
						print '	<tr>
									<td class="tdoverflowmax200 nowrap" style="padding: 2px 0px 2px 5px;">'.$ref.$label.$desc.'</td>
									<td class="right" style = "padding: 2px 5px 2px 0px;">'.$statut.'</td>
								</tr>';
					}
				}
			}
		}
		$db->free($res);
		if (!$asArray) {	// from the search page (tools)
			print '</table>';
		} else {	// from the menu
			return $TResult;
		}
	}

	function _isDate($value)
	{
		if (empty($value)) {
			return false;
		}
		if (preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $value)) {
			$date = DateTime::createFromFormat('d/m/Y', $value);
			$value = $date->format('Y-m-d');
		}
    	$date	= date_parse($value);
		if ($date['error_count'] == 0 && $date['warning_count'] == 0) {
			return checkdate($date['month'], $date['day'], $date['year']);
		} else {
			return false;
		}
	}
