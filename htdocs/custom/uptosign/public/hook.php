<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 */

/*
 *     	\file       htdocs/public/hook.php
 *		\ingroup    core
 *		\brief      File to offer a async return from sign service like a webhook
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define('DOLENTITY', $entity);
}

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER['CONTEXT_DOCUMENT_ROOT'])) {
	$res = @include $_SERVER['CONTEXT_DOCUMENT_ROOT'].'/main.inc.php';
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	--$i;
	--$j;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)).'/main.inc.php')) {
	$res = @include substr($tmp, 0, ($i + 1)).'/main.inc.php';
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php')) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))).'/main.inc.php';
}
// Try main.inc.php using relative path
if (!$res && file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res && file_exists('../../main.inc.php')) {
	$res = @include '../../main.inc.php';
}
if (!$res && file_exists('../../../main.inc.php')) {
	$res = @include '../../../main.inc.php';
}
if (!$res) {
	exit('Include of main fails');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');

if (!isset($mysoc)) {
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	$mysoc = new Societe($db);
	$mysoc->setMysoc($conf);
}

// Security check
// No check on module enabled. Done later

// Get parameters
$action = (string) GETPOST('action', 'aZ09');
$message = (string) GETPOST('message', 'aZ09');
$source = (string) GETPOST('source', 'alpha');
$object = $modulepart = $error = null;

$hookmanager = new HookManager($db);

//TODO: all
$h = apache_request_headers();
// dol_syslog('uptosign headers : '.json_encode($h));
if ($h['Content-Type'] == 'application/json') {
	$content = file_get_contents('php://input');
	$json = json_decode($content);
	dol_syslog('uptosign request is : '.json_encode($json));
	if (empty($json->id)) {
		dol_syslog('uptosign error, json id is empty : '.$content, LOG_ERR);
		http_response_code(403);
		exit(-1);
	}

	$uptoSign = new UptoSign($db);
	if (isset($json->typeOfDoc) && $json->typeOfDoc == 'proof' && isset($json->parent_id)) {
		$resUTS = $uptoSign->fetchWhereUuidSign($json->parent_id);
		if ($resUTS <= 0) {
			dol_syslog("uptosign can't find document (1,proof mode)", LOG_ERR);
			dol_syslog('uptosign request is : '.json_encode($json), LOG_ERR);
			http_response_code(403);
			exit(-1);
		} else {
			//handle proof input hook (normal situation is to get a hook on main document and dolibarr call twice remote)
			//uptosign service to get main doc AND proof ... but uptosign cound make call for proof too
			$action = 'confirm_uptosignfetchproof';
			//then normal process to fetch main document linked to that proof
		}
	} else {
		$resUTS = $uptoSign->fetchWhereUuidSign($json->id);
		if ($resUTS <= 0) {
			dol_syslog("uptosign can't find document (2)", LOG_ERR);
			dol_syslog('uptosign request is : '.json_encode($json), LOG_ERR);
			http_response_code(403);
			exit(-2);
		}
	}

	// dol_syslog("uptosign document is : ".json_encode($uptoSign));
	// dol_syslog("uptosign document, content is : ".$content);
	// dol_syslog("uptosign document, hook key is : ".$uptoSign->hook_key);
	//check signature
	$computedSignature = hash_hmac('sha256', $content, $uptoSign->hook_key);
	dol_syslog('uptosign signature is '.$h['Signature'].' compare to '.$computedSignature.' ...');
	if (hash_equals($computedSignature, $h['Signature'])) {
		dol_syslog('uptosign signature is confirmed, can continue !');
	} else {
		http_response_code(403);
		echo 'Bad value for security check(Signature).';
		exit(-1);
	}

	$objectType = $uptoSign->object_type;
	dol_syslog("uptosign object type is $objectType !");
	// Initialize technical objects and call triggers
	$fko = $uptoSign->fk_object;
	if ($objectType == 'propal') {
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
		$object = new Propal($db);
		$object->fetch($fko);
		//customer is not a user !?! so could we use same user as validation ?
		$user = $uptoSign->findUserToUse($user, $object);
		//exclude uptoseal to that auto close process
		if ($uptoSign->api_name == 'uptosign') {
			//try to avoid triple entry on events
			if ($json->status == 'success' && $object->status != Propal::STATUS_SIGNED) {
				if (method_exists($object, 'call_trigger')) {
					$result = $object->call_trigger('PROPAL_CLOSE_SIGNED', $user);
					if ($result < 0) {
						++$error;
					}
				}
			} elseif ($object->status != Propal::STATUS_NOTSIGNED) {
				// TODO pas si sur de devoir absolument lancer ce trigger !
				// car ça implique un delete dans le cas où le devis était déjà signé par exemple
				// if (method_exists($object, 'call_trigger')) {
				// 	$result = $object->call_trigger('PROPAL_CLOSE_REFUSED', $user);
				// 	if ($result < 0) {
				// 		++$error;
				// 	}
				// }
			}
		}
		$modulepart = 'propal';
	} elseif ($objectType == 'contract' || $objectType == 'contrat') {
		require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
		$object = new Contrat($db);
		$resFetch = $object->fetch($fko);
		if ($uptoSign->api_name == 'uptosign') {
			//try to avoid triple entry on events
			if ($json->status == 'success' && $object->status == Contrat::STATUS_VALIDATED) {
				if (method_exists($object, 'call_trigger')) {
					$result	= $object->call_trigger('CONTRACT_CLOSED_SIGNED', $user);
					if ($result < 0) {
						++$error;
					}
				}
			}
		}
		$modulepart = 'contract';
		if ($resFetch <= 0) {
			dol_syslog("uptosign can't fetch $modulepart with id=$fko", LOG_ERR);
			http_response_code(403);
			exit(-1);
		}
	} elseif ($objectType == 'invoice' || $objectType == 'facture') {
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
		$object = new Facture($db);
		$resFetch = $object->fetch($fko);
		$modulepart = 'invoice';
		if ($resFetch <= 0) {
			dol_syslog("uptosign can't fetch $modulepart with id=$fko", LOG_ERR);
			http_response_code(403);
			exit(-1);
		}
		if ($uptoSign->api_name == 'uptosign') {
			//try to avoid triple entry on events
			if ($json->status == 'success') {
				if (method_exists($object, 'call_trigger')) {
					$result = $object->call_trigger('INVOICE_SEALED', $user);
					if ($result < 0) {
						++$error;
					}
				}
			}
		}
	} elseif ($objectType == 'project') {
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
		$object = new Project($db);
		$resFetch = $object->fetch($fko);
		$modulepart = 'project';
		if ($resFetch <= 0) {
			dol_syslog("uptosign can't fetch $modulepart with id=$fko", LOG_ERR);
			http_response_code(403);
			exit(-1);
		}
	} elseif ($objectType == 'societe') {
		$object = new Societe($db);
		$resFetch = $object->fetch($fko);
		$modulepart = 'societe';
		if ($resFetch <= 0) {
			dol_syslog("uptosign can't fetch $modulepart with id=$fko", LOG_ERR);
			http_response_code(403);
			exit(-1);
		}
	} elseif ($objectType == 'order') {
		$object = new Commande($db);
		$resFetch = $object->fetch($fko);
		$modulepart = 'commande';
		if ($resFetch <= 0) {
			dol_syslog("uptosign can't fetch $modulepart with id=$fko", LOG_ERR);
			http_response_code(403);
			exit(-1);
		}
	} elseif ($objectType == 'companypaymentmode') {
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
		//sepa mandate
		$object = new CompanyPaymentMode($db);
		$resFetch = $object->fetch($fko);
		$modulepart = 'companypaymentmode';
		// dol_syslog('uptosign CompanyPaymentMode : ' . json_encode($object->id));
		if ($resFetch <= 0) {
			dol_syslog("uptosign can't fetch $modulepart with id=$fko", LOG_ERR);
			http_response_code(403);
			exit(-1);
		}
	} elseif ($objectType == 'user') {
		$object = new User($db);
		$object->fetch($fko);
		$modulepart = "user";
	} else {
		//no dolibarr object
	}

	//TODO erreur de conception, le retour webhook donne un uuid de document disponible, il ne faut pas aller chercher
	//quel est l'objet dolibarr sous peine de télécharger le mauvais fichier !
	//exemple une facture avec 3 pièces jointes et scellement d'une PJ + facture ... echec assuré
	//donc on passe l'uuid dans les parametres
	$parameters = ['currentcontext' => $modulepart.'card', 'uuid' => $json->id];
	$api_name = $uptoSign->api_name;

	if (isModEnabled('multicompany')) {
		$conf->setEntityValues($db, $object->entity);
	}
	//First sync
	$action = $api_name.'sync';
	dol_syslog('uptosign $action call hook doActions, object is'.json_encode($object->id));
	//note: $object peut être modifié par le hook ...
	$objectSave = $object;
	if ($action != "confirm_uptosignfetchproof") {
		$hookmanager->initHooks([$modulepart.'card', 'uptosigncard']);
		$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

		//Then fetch
		$action = 'confirm_'.$api_name.'fetch';
		$object = $objectSave;
		dol_syslog('uptosign $action call hook doActions, object is'.json_encode($object->id));
		$hookmanager->initHooks([$modulepart.'card', 'uptosigncard']);
		$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
	}

	//Then fetch proof if exists - only if signed file, not if sealed
	if (isset($json->typeOfDoc) && $json->typeOfDoc == 'proof' && $api_name == 'uptosign') {
		$action = 'confirm_uptosignfetchproof';
		$object = $objectSave;
		$hookmanager->initHooks([$modulepart.'card', 'uptosigncard']);
		dol_syslog('uptosign $action call hook doActions, object is'.json_encode($object->id));
		$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
	}
	dol_syslog('uptosign after call hook doActions');
}
//$computedSignature = hash_hmac('sha256', $request, $configuredSigningSecret);
