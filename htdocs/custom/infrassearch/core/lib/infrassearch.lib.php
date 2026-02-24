<?php
	/************************************************
	* Copyright (C) 2018-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrassearch/core/lib/infrassearch.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraSSearch module
	************************************************/

	// Libraries ************************************

	/**
	* Build the list of breadcrumb links
	*
	* @return  string		html list of links
	**/
	function printDropdownBreadCrumb()
	{
		global $conf, $db, $user, $hookmanager;

		$savehook				= $hookmanager;	// Save hookmanager
		$hookmanager			= clone $hookmanager;	// create a new hookmanager => for multicompany
		$nbRows					= getDolGlobalInt('INFRASSEARCH_NB_BREADCRUMB', 5);
		$maxInThirdPos			= array('commande', 'contact', 'expensereport', 'facture', 'invoice_supplier', 'product', ' shipping', 'societe');
		$dropDownBreadCrumbHtml	= '						<div class = "breadcrumb-body dropdown-body">
															<div class = "dropdown-breadcrumb-list">';
		$sql	= 'SELECT element, fk_element, tms FROM '.$db->prefix().'infrassearch_history';
		$sql	.= ' WHERE fk_user = '.$user->id ;
		$sql	.= ' AND entity = '.$conf->entity;
		$sql	.= ' ORDER BY tms DESC';
		$sql	.= ' LIMIT '.$nbRows;
		$resql	= $db->query($sql);
		if ($resql && !preg_match('/(.*)invoicescontractlist(.*)/', $_SERVER['REQUEST_URI'], $reg)) {
			for ($i = 0; $i < $db->num_rows($resql); $i++) {
				try {
					$objp	= $db->fetch_object($resql);
					if ($objp->element != null) {
						$staticobject	= getobjectclass($objp->element);
						if ($staticobject != null && method_exists(getobjectclass($objp->element, 1), 'fetch')) {
							$result	= $staticobject->fetch($objp->fk_element);
							if ($result > 0 && method_exists($staticobject, 'getNomUrl')) {
								$dropDownBreadCrumbHtml	.= '	<div class = "infrassearchdropdown-breadcrumb-item infrassearchdropdown-item">';
								$dropDownBreadCrumbHtml	.= in_array($staticobject->element, $maxInThirdPos) ? $staticobject->getNomUrl(1, '', 30) : $staticobject->getNomUrl(1);
								$dropDownBreadCrumbHtml	.= '	</div>';
							}
						}
					}
				} catch (Exception $e) {
					$errorInfo	= handleInfraSearchError($e, 'fetch_object_breadcrumb', $objp->element);
					$result		= 0;
					$dropDownBreadCrumbHtml	.= '	<div class = "infrassearchdropdown-breadcrumb-item infrassearchdropdown-item text-warning">
														<i class = "fa fa-exclamation-triangle"></i> '.$errorInfo['message'].'
													</div>';
				}
			}
		}
		$dropDownBreadCrumbHtml	.= '						</div>
														</div>';
		$hookmanager			= $savehook;	// retrieve the initial hookmanager
		return $dropDownBreadCrumbHtml;
	}

	/**
	* Search the class
	*
	* @param	string				$objecttype		Type of object
	* @param	int					$onlyclass		return only class name
	* @return	string|object|null					class name, new object class or null if error or not found
	**/
	function getobjectclass($objecttype, $onlyclass = 0)
	{
		global $langs, $db;

		if (!isset($objecttype) || $objecttype == 'onepagebasketAdvProduct') {
			return null;
		}
		$regs	= array();
		if ($objecttype != 'supplier_proposal' && $objecttype != 'order_supplier' && $objecttype != 'invoice_supplier' && preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
			$element	= $regs[1];
			$subelement	= $regs[2];
		} else {
			$element	= $objecttype;
			$subelement	= $objecttype;
		}
		$classpath	= $element.'/class';
		// To work with non standard classpath
		if ($objecttype == 'propal') {
			$classpath	= 'comm/propal/class';
		} elseif ($objecttype == 'facture') {
			$classpath	= 'compta/facture/class';
		} elseif ($objecttype == 'facturerec') {
			$classpath	= 'compta/facture/class';
		} elseif ($objecttype == 'fichinter') {
			$classpath	= 'fichinter/class';
		} elseif ($objecttype == 'project') {
			$classpath	= 'projet/class';
		} elseif ($objecttype == 'project_task') {
			$classpath	= 'projet/class';
		} elseif ($objecttype == 'chargesociales') {
			$classpath	= 'compta/sociales/class';
		} elseif ($objecttype == 'subscription') {
			$classpath	= 'adherents/class';
		} elseif ($objecttype == 'action') {
			$classpath	= 'comm/action/class';
		} elseif ($objecttype == 'shipping') {
			$classpath	= 'expedition/class';
		} elseif ($objecttype == 'delivery') {
			$classpath	= 'delivery/class';
		} elseif ($objecttype == 'stock') {
			$classpath	= 'product/stock/class';
		} elseif ($objecttype == 'inventory') {
			$classpath	= 'product/inventory/class';
		} elseif ($objecttype == 'contratabonnement') {
			$classpath	= 'contrat/class';
		} elseif ($objecttype == 'supplier_proposal') {
			$classpath	= 'supplier_proposal/class';
		} elseif ($objecttype == 'order_supplier') {
			$classpath	= 'fourn/class';
		} elseif ($objecttype == 'invoice_supplier') {
			$classpath	= 'fourn/class';
		} elseif ($objecttype == 'member') {
			$classpath	= 'adherents/class';
		} elseif ($objecttype == 'mrp') {
			$classpath	= 'mrp/class';
		} elseif ($objecttype == 'conferenceorboothattendee') {
			$classpath	= 'eventorganization/class';
		} elseif ($objecttype == 'conferenceorbooth') {
			$classpath	= 'eventorganization/class';
		} elseif ($objecttype == 'equipevent') {
			$classpath	= 'equipement/class';
		} elseif ($objecttype == 'equipconso') {
			$classpath	= 'equipement/class';
		}
		// To work with non standard subelement
		if ($objecttype == 'action') {
			$subelement	= 'actioncomm';
		} elseif ($objecttype == 'shipping') {
			$subelement	= 'expedition';
		} elseif ($objecttype == 'delivery') {
			$subelement	= 'delivery';
		} elseif ($objecttype == 'stock') {
			$subelement	= 'entrepot';
		} elseif ($objecttype == 'contratabonnement') {
			$subelement	= 'contrat';
		} elseif ($objecttype == 'member') {
			$subelement	= 'adherent';
		}
		// Set classname
		$classname	= ucfirst($subelement);
		// To work with non standard classname
		if ($objecttype == 'order') {
			$classname	= 'Commande';
		} elseif ($objecttype == 'facturerec') {
			$classname	= 'FactureRec';
		} elseif ($objecttype == 'action') {
			$classname	= 'ActionComm';
		} elseif ($objecttype == 'inventory') {
			$classname	= 'Inventory';
		} elseif ($objecttype == 'supplier_proposal') {
			$classname	= 'SupplierProposal';
		} elseif ($objecttype == 'order_supplier') {
			$classname	= 'CommandeFournisseur';
		} elseif ($objecttype == 'invoice_supplier') {
			$classname	= 'FactureFournisseur';
		} elseif ($objecttype == 'conferenceorboothattendee') {
			$classname	= 'ConferenceOrBoothAttendee';
		} elseif ($objecttype == 'conferenceorbooth') {
			$classname	= 'ConferenceOrBooth';
		} elseif ($objecttype == 'uptosign') {
			$classname	= 'UptoSign';
		}
		// Set classfile
		$classfile	= strtolower($subelement);
		// To work with non standard classfile
		if ($objecttype == 'order') {
			$classfile	= 'commande';
		} elseif ($objecttype == 'facturerec') {
			$classfile	= 'facture-rec';
		} elseif ($objecttype == 'project_task') {
			$classfile	= 'task';
		} elseif ($objecttype == 'inventory') {
			$classfile	= 'inventory';
		} elseif ($objecttype == 'supplier_proposal') {
			$classfile	= 'supplier_proposal';
		} elseif ($objecttype == 'order_supplier') {
			$classfile	= 'fournisseur.commande';
		} elseif ($objecttype == 'invoice_supplier') {
			$classfile	= 'fournisseur.facture';
		} elseif ($objecttype == 'conferenceorboothattendee') {
			$classfile	= 'conferenceorboothattendee';
		} elseif ($objecttype == 'conferenceorbooth') {
			$classfile	= 'conferenceorbooth';
		}
		dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
		$langs->load($objecttype);
		if (class_exists($classname)) {
			$object	= new $classname($db);
			return empty($onlyclass) ? $object : $classname;
		} else {
			return null;
		}
	}

	/**
	* Gestion centralisée des erreurs HTTP pour InfraSearch
	*
	* @param	Exception	$exception		L'exception capturée
	* @param	string		$context		Le contexte où l'erreur s'est produite
	* @param	string		$objecttype		Type d'objet concerné (optionnel)
	* @return	array						Informations sur l'erreur
	**/
	function handleInfraSearchError($exception, $context = '', $objecttype = '')
	{
		$errorInfo	= array('is_http_500'	=> false,
							'is_critical'	=> false,
							'message'		=> $exception->getMessage(),
							'code'			=> $exception->getCode(),
							'context'		=> $context,
							'objecttype'	=> $objecttype
							);
		// Détection des erreurs 500
		if (strpos($exception->getMessage(), '500') !== false || $exception->getCode() == 500 || strpos($exception->getMessage(), 'Internal Server Error') !== false) {
			$errorInfo['is_http_500']	= true;
			$errorInfo['is_critical']	= true;
			dol_syslog("InfraSearch: Erreur HTTP 500 détectée - Context: $context, Object: $objecttype, Message: ".$exception->getMessage(), LOG_ERR);
		} elseif ($exception->getCode() >= 400 && $exception->getCode() < 600) {	// Détection d'autres erreurs critiques
			$errorInfo['is_critical']	= true;
			dol_syslog("InfraSearch: Erreur HTTP ".$exception->getCode()." - Context: $context, Object: $objecttype, Message: ".$exception->getMessage(), LOG_WARNING);
		} else {	// Erreurs générales
			dol_syslog("InfraSearch: Erreur générale - Context: $context, Object: $objecttype, Message: ".$exception->getMessage(), LOG_INFO);
		}
		return $errorInfo;
	}
