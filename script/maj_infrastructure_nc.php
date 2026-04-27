<?php
/**
* SPDX-License-Identifier: GPL-3.0-or-later
* This file is part of Dolibarr module Infrastructure
*/

require '../config.php';

if (empty($user->admin)) {
	accessforbidden();
}

dol_include_once('/infrastructure/core/lib/infrastructure.lib.php');
dol_include_once('/infrastructure/class/infrastructure.class.php');
dol_include_once('/comm/propal/class/propal.class.php');
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/compta/facture/class/facture.class.php');
dol_include_once('/comm/propal/class/propal.class.php');

$limit	= GETPOST('limit', 'int');
$sql	= 'SELECT rowid FROM '.MAIN_DB_PREFIX.'propal WHERE total_ht + tva != total';
if(!empty($limit)) {
	$sql .= ' LIMIT '.$limit;
}
$resql	= $db->query($sql);
if($resql) {
	$db->begin();
	while($obj = $db->fetch_object($resql)) {
		$propal	= new Propal($db);
		var_dump($obj->rowid);
		$propal->fetch($obj->rowid);
		foreach($propal->lines as &$l) {
			if (empty($l->array_options)) {
				$l->fetch_optionals();
			}
			if (!empty($l->array_options['options_infrastructure_nc']) && ! TInfrastructure::isModInfrastructureLine($l)) {
				infrastructure_updateLineNC($propal->element, $propal->id, $l->id, $l->array_options['options_infrastructure_nc']);
			}
		}
	}
	$db->commit();
}