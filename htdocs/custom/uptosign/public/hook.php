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
// On purpose there is NO entity taken from the URL here: a webhook URI is stored on
// a remote server, exposing the entity there would be both useless and unwanted.
// The entity is deduced server side from the uptosign record matching the uuid sent
// by the webhook (see uptosign_switch_to_entity() below), once the HMAC signature
// has been verified.

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
// Card context of the hooks, resolved with the object itself by
// uptosign_webhook_object_descriptor(). It is not always $modulepart.'card': Dolibarr
// names some of them differently (a supplier order card is 'ordersuppliercard'), and
// the context is what decides whether doActions() runs.
$hookcontext = null;

$hookmanager = new HookManager($db);

// Read request headers. getallheaders() works under php-fpm/nginx (apache_request_headers() does not).
$h = function_exists('getallheaders') ? getallheaders() : array();
// Normalize header names to lowercase because header names are case-insensitive.
$hLower = array();
foreach ($h as $hName => $hValue) {
	$hLower[strtolower((string) $hName)] = $hValue;
}
$contentType = (string) ($hLower['content-type'] ?? '');
// dol_syslog('uptosign headers : '.json_encode($h));
if (stripos($contentType, 'application/json') !== false) {
	$content = file_get_contents('php://input');
	$json = json_decode($content);
	dol_syslog('uptosign request is : '.json_encode($json));
	if (!is_object($json)) {
		dol_syslog('uptosign error, json body is not a valid object : '.$content, LOG_ERR);
		http_response_code(400);
		echo 'Invalid JSON body.';
		exit(-1);
	}
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
	// Refuse any record with an empty hook_key: an empty key makes the HMAC signature
	// forgeable by anyone (hook_key is notnull=-1, so an empty value can exist in base).
	if (trim((string) $uptoSign->hook_key) === '') {
		dol_syslog('uptosign hook: empty hook_key for record id '.((int) $uptoSign->id).', reject to avoid forgeable signature', LOG_ERR);
		http_response_code(403);
		echo 'Bad value for security check(Signature).';
		exit(-1);
	}
	$sig = (string) ($hLower['signature'] ?? '');
	$computedSignature = hash_hmac('sha256', $content, $uptoSign->hook_key);
	dol_syslog('uptosign signature is '.$sig.' compare to '.$computedSignature.' ...');
	if (hash_equals($computedSignature, $sig)) {
		dol_syslog('uptosign signature is confirmed, can continue !');
	} else {
		dol_syslog('uptosign signature check failed for record id '.((int) $uptoSign->id), LOG_ERR);
		http_response_code(403);
		echo 'Bad value for security check(Signature).';
		exit(-1);
	}

	// The request is authenticated: we can trust the record and switch the whole
	// configuration to its entity. This MUST happen before fetching the Dolibarr
	// object and before any call to the remote API, otherwise UPTOSIGN_KEY_API and
	// the data directories are read from entity 1 (empty key -> 401 on every call).
	// The record exists (fetch above succeeded), so a 0 entity is an inconsistency
	// that must abort rather than silently fall back to entity 1.
	$recordEntity = uptosign_get_record_entity($db, $uptoSign->id);
	if ((int) $recordEntity <= 0) {
		dol_syslog('uptosign hook: unable to resolve entity for record id '.((int) $uptoSign->id).', abort', LOG_ERR);
		http_response_code(500);
		echo 'Unable to resolve entity for this record.';
		exit(-1);
	}
	uptosign_switch_to_entity($db, $recordEntity);

	if (trim(utsbackports_getDolGlobalString('UPTOSIGN_KEY_API', '')) === '') {
		dol_syslog('uptosign hook: UPTOSIGN_KEY_API is empty for entity ' . $conf->entity . ', abort (no API call sent)', LOG_ERR);
		http_response_code(500);
		echo 'UptoSign API key is not configured for this entity.';
		exit(-1);
	}

	$objectType = $uptoSign->object_type;
	dol_syslog("uptosign object type is $objectType !");

	// Which Dolibarr object this callback is about, and under which context its card
	// hooks must run. The mapping lives in the library so every object type the module
	// can sign is covered by a test: a missing branch answers 500 and the signed
	// document is lost.
	$descriptor = uptosign_webhook_object_descriptor($db, $uptoSign);
	if (!is_array($descriptor)) {
		//no dolibarr object matching this object type: nothing to fetch, abort with a log
		dol_syslog("uptosign hook: unsupported object type '$objectType' for record id ".((int) $uptoSign->id).", abort", LOG_ERR);
		http_response_code(500);
		echo 'Unsupported object type.';
		exit(-1);
	}

	$object = $descriptor['object'];
	$modulepart = $descriptor['modulepart'];
	$hookcontext = $descriptor['hookcontext'];
	$fko = $descriptor['id'];

	$resFetch = $object->fetch($fko);
	if ($resFetch <= 0) {
		dol_syslog("uptosign can't fetch $modulepart with id=$fko", LOG_ERR);
		http_response_code(403);
		exit(-1);
	}

	// Business triggers, once the object is loaded. They are excluded for uptoseal:
	// sealing a document does not close anything.
	if ($objectType == 'propal') {
		//customer is not a user !?! so could we use same user as validation ?
		$user = $uptoSign->findUserToUse($user, $object);
		// TODO on a refused signature, PROPAL_CLOSE_REFUSED is deliberately NOT called:
		// it implies a delete when the proposal was already signed.
		if ($uptoSign->api_name == 'uptosign') {
			//try to avoid triple entry on events
			if (isset($json->status) && $json->status == 'success' && $object->status != Propal::STATUS_SIGNED) {
				if (method_exists($object, 'call_trigger')) {
					$result = $object->call_trigger('PROPAL_CLOSE_SIGNED', $user);
					if ($result < 0) {
						++$error;
					}
				}
			}
		}
	} elseif ($objectType == 'contract' || $objectType == 'contrat') {
		if ($uptoSign->api_name == 'uptosign') {
			//try to avoid triple entry on events
			if (isset($json->status) && $json->status == 'success' && $object->status == Contrat::STATUS_VALIDATED) {
				if (method_exists($object, 'call_trigger')) {
					$result	= $object->call_trigger('CONTRACT_CLOSED_SIGNED', $user);
					if ($result < 0) {
						++$error;
					}
				}
			}
		}
	} elseif ($objectType == 'invoice' || $objectType == 'facture') {
		if ($uptoSign->api_name == 'uptosign') {
			//try to avoid triple entry on events
			if (isset($json->status) && $json->status == 'success') {
				if (method_exists($object, 'call_trigger')) {
					$result = $object->call_trigger('INVOICE_SEALED', $user);
					if ($result < 0) {
						++$error;
					}
				}
			}
		}
	}

	//TODO erreur de conception, le retour webhook donne un uuid de document disponible, il ne faut pas aller chercher
	//quel est l'objet dolibarr sous peine de télécharger le mauvais fichier !
	//exemple une facture avec 3 pièces jointes et scellement d'une PJ + facture ... echec assuré
	//donc on passe l'uuid dans les parametres
	// Note: we pass the sign_id of the record we found, NOT $json->id: on a "proof"
	// callback $json->id is the uuid of the proof file, not the one of the document
	// Note on the context: HookManager overwrites $parameters['currentcontext'] with the
	// context the module is registered under (hookmanager.class.php). initHooks() below is
	// therefore called with two of them: the card context, and 'uptosigncard' as a fallback.
	// For societe and companypaymentmode no module declares the card context, so the call
	// legitimately lands on 'uptosigncard', which doActions() does handle. Declaring
	// 'thirdpartycard' here would also plug the module into societe/card.php, which is a
	// different feature, not a webhook fix.
	$cardcontext = $hookcontext;
	$parameters = ['currentcontext' => $cardcontext, 'uuid' => $uptoSign->sign_id];
	$api_name = $uptoSign->api_name;

	//First sync
	$action = $api_name.'sync';
	dol_syslog('uptosign $action call hook doActions, object is'.json_encode($object->id));
	//note: $object peut être modifié par le hook ...
	$objectSave = $object;
	// On a "proof" callback we only fetch the proof (below): syncing and re-fetching the
	// main document here would trigger two useless remote calls. Detect it from the JSON
	// payload, not from $action which was just overwritten with the sync action above.
	$isProofCallback = (isset($json->typeOfDoc) && $json->typeOfDoc == 'proof');
	if (!$isProofCallback) {
		$hookmanager->initHooks([$cardcontext, 'uptosigncard']);
		$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

		//Then fetch
		$action = 'confirm_'.$api_name.'fetch';
		$object = $objectSave;
		dol_syslog('uptosign $action call hook doActions, object is'.json_encode($object->id));
		$hookmanager->initHooks([$cardcontext, 'uptosigncard']);
		$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
	}

	//Then fetch proof if exists - only if signed file, not if sealed
	if (isset($json->typeOfDoc) && $json->typeOfDoc == 'proof' && $api_name == 'uptosign') {
		$action = 'confirm_uptosignfetchproof';
		$object = $objectSave;
		$hookmanager->initHooks([$cardcontext, 'uptosigncard']);
		dol_syslog('uptosign $action call hook doActions, object is'.json_encode($object->id));
		$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
	}
	dol_syslog('uptosign after call hook doActions');
} else {
	// Any request without a JSON Content-Type is not a valid webhook call.
	dol_syslog('uptosign hook: unexpected Content-Type "'.$contentType.'", expected application/json', LOG_ERR);
	http_response_code(415);
	echo 'Unsupported Media Type, expected application/json.';
	exit(-1);
}
//$computedSignature = hash_hmac('sha256', $request, $configuredSigningSecret);
