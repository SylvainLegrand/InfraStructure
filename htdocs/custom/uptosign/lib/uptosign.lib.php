<?php
/* Copyright (C) 2022 Éric Seigne <eric.seigne@cap-rel.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file	uptosign/lib/uptosign.lib.php
 * \ingroup uptosign
 * \brief   Library files with common functions for UptoSign
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/modules/barcode/doc/tcpdfbarcode.modules.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';

dol_include_once('/uptosign/core/modules/modUptoSign.class.php');
dol_include_once('/uptosign/class/uptosignapiclient.class.php');
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/class/uptosignconfig.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/uptosign/lib/backports.lib.php');
// dol_include_once('/archivespdf/class/ecmfilesextended.class.php');

// Force le chargement des classes Smalot\PdfParser embarquées par uptosign
// avant qu'un autre module livrant sa propre copie de smalot/pdfparser
// (ex. dalfred) n'enregistre son autoloader composer : composer s'enregistre
// en "prepend", donc le dernier autoloader enregistré gagne et sa version de
// PDFObject/FilterHelper peut retourner des coordonnées de mots-clés erronées
// sur les PDF générés via FPDI (sceau et signature mal positionnés ou perdus).
dol_include_once('/uptosign/vendor/autoload.php');
class_exists('Smalot\PdfParser\Parser');
class_exists('Smalot\PdfParser\Page');
class_exists('Smalot\PdfParser\PDFObject');
class_exists('Smalot\PdfParser\RawData\FilterHelper');

/**
 *  Prepare array of tabs for UptoSign
 *
 *  @param  UptoSign   $object	 UptoSign
 *  @return array				   Array of tabs
 */
function uptosignPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("uptosign@uptosign");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/uptosign/uptosign_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	// require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	// $upload_dir = $conf->uptosign->dir_output . "/uptosign/" . dol_sanitizeFileName($object->ref);
	// $nbFiles = count(dol_dir_list($upload_dir,'files',0,'',['(\.meta|_preview.*\.png)$']));
	// $nbLinks = Link::count($db, $object->element, $object->id);

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'uptosign@uptosign');

	return $head;
}


/**
 * Prepare admin pages header
 *
 * @return array
 */
function uptosignAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("uptosign@uptosign");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/uptosign/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("UptoSignSettingsServer");
	$head[$h][2] = 'server';
	$h++;
	$head[$h][0] = dol_buildpath("/uptosign/admin/setup-1.php", 1);
	$head[$h][1] = $langs->trans("UptoSignSettings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/uptosign/admin/signs.php", 1);
	$head[$h][1] = $langs->trans("UptoSignSignsTab");
	$head[$h][2] = 'signs';
	$h++;
	$head[$h][0] = dol_buildpath("/uptosign/uptosignconfig_card.php?uts=first", 1); //uptosignconfig_list
	$head[$h][1] = $langs->trans("UptoSignTemplatesTab");
	$head[$h][2] = 'templates';
	$h++;
	$head[$h][0] = dol_buildpath("/uptosign/admin/reseller.php", 1);
	$head[$h][1] = $langs->trans("UptoSignResellers");
	$head[$h][2] = 'resellers';
	$h++;
	$head[$h][0] = dol_buildpath("/uptosign/admin/reseller-1.php", 1);
	$head[$h][1] = $langs->trans("UptoSignResellers2");
	$head[$h][2] = 'resellers2';
	$h++;
	$head[$h][0] = dol_buildpath("/uptosign/admin/workflow.php", 1);
	$head[$h][1] = $langs->trans("UptoSignWorkflow");
	$head[$h][2] = 'workflow';
	$h++;
	complete_head_from_modules($conf, $langs, null, $head, $h, 'uptosign_admin');
	$head[$h][0] = dol_buildpath("/uptosign/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@uptosign:/uptosign/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@uptosign:/uptosign/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'uptosign@uptosign', 'remove');

	return $head;
}

/**
 * Function to take mobile phone from mobile or pro field ...
 *
 * @param   string  $mobile       [$mobile description]
 * @param   string  $pro          [$pro description]
 * @param   string  $countryCode  [$countryCode description]
 *
 * @return  string                [return description]
 */
function uptoSignSearchMobile($mobile, $pro, $countryCode)
{
	$phone = $mobile;
	if (empty($phone)) {
		if (empty($pro)) {
			return '';
		} else {
			$phone = $pro;
		}
	}

	return uptoSignFixMobile($phone, $countryCode);
}

/**
 * Search mobile phone from contact
 *
 * @param   Contact  $c  [$c description]
 *
 * @return  string      [return description]
 */
function uptoSignSearchMobileContact($c)
{
	$fields = ['phone_mobile', 'phone_pro', 'phone_perso'];
	foreach ($fields as $f) {
		if (isset($c->$f) && !empty($c->$f)) {
			$phone_mobile = uptoSignSearchMobile($c->$f, '', $c->country_code);
			if ($phone_mobile != '') {
				dol_syslog("uptosign: uptoSignSearchMobileContact field is $f");
				return $phone_mobile;
			}
		}
	}
	return '';
}

/**
 * Function to clean phone number
 *
 *  @param  string  $mobileIn		Phone number to clean
 *  @param  string  $countryCode	Country code
 *  @return string				  Phone number cleaned, '' if error on impossible to clean
 */
function uptoSignFixMobile($mobileIn, $countryCode)
{
	global $mysoc;
	// dol_syslog("uptosign: uptoSignFixMobile mobile=$mobileIn, country=$countryCode", LOG_DEBUG);

	if (empty($mobileIn)) {
		dol_syslog("uptosign: uptoSignFixMobile mobile number is empty !", LOG_WARNING);
		return '';
	}

	if (empty($countryCode)) {
		$countryCode = $mysoc->country_code;
	}

	//if number does not starts with + we try to transform it ...
	if (!preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/', $mobileIn)) {
		if ($countryCode == 33) {
			$countryCode = 'FR';
		} elseif ($countryCode == 32) {
			$countryCode = 'BE';
		} elseif ($countryCode == 352) {
			$countryCode = 'LU';
		}

		// remove (0) and spaces
		$mobile = preg_replace(['/\(0\)/', '/\s/'], ['0', ''], $mobileIn);
		if (!preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/', $mobile)) {
			// try to add +33 if country is France and numbers starts with zero
			if ($countryCode == 'FR' && preg_match('/^0.*/', $mobile)) {
				// dol_syslog("uptosign: uptoSignFixMobile is $mobile and $countryCode", LOG_DEBUG);
				$sixousept = substr($mobile, 1, 1);
				if ($sixousept == '6' || $sixousept == '7') {
					$mobile = preg_replace('/^0/', '+33', $mobile);
				}
			} elseif ($countryCode == 'BE' && preg_match('/^0.*/', $mobile)) {
				$mobile = preg_replace('/^0/', '+32', $mobile);
			} elseif ($countryCode == 'LU' && preg_match('/^0.*/', $mobile)) {
				$mobile = preg_replace('/^0/', '+352', $mobile);
			}
		}
		if (!preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/', $mobile)) {
			// The number could not be normalized to an international format for this
			// country code (unsupported prefix, not a mobile, unknown country, ...).
			// Do not fail silently: log the rejected value so the reason is traceable.
			dol_syslog("uptosign: uptoSignFixMobile rejected number '$mobileIn' (country=$countryCode), could not build a valid international format", LOG_WARNING);
			$mobile = '';
		}
		// dol_syslog("uptosign: uptoSignFixMobile mobile number $mobileIn is not an international one, we try to transform it as $mobile", LOG_WARNING);
	} else {
		//remove spaces if +33 6 987 444 01 for example
		$mobile = preg_replace(['/\s/'], [''], $mobileIn);
	}

	dol_syslog("uptosign: uptoSignFixMobile input=$mobileIn ($countryCode), return $mobile", LOG_DEBUG);
	return $mobile;
}

/**
 * Add header user-agent to get informations server side (errors on client version)
 *
 * @return  string  [return description]
 */
function uptosignuserAgent()
{
	global $conf, $db, $modUptosign;
	if (!isset($modUptosign) || $modUptosign->version === null) {
		$modUptosign = new modUptoSign($db);
	}

	$uuid = dolibarr_get_const($db, "UPTOSIGN_UUID", 0);
	dol_syslog("uptosign: Server UUID : $uuid", LOG_DEBUG);
	if ($uuid == "") {
		$uuid = uniqid();
		$result = dolibarr_set_const($db, "UPTOSIGN_UUID", $uuid, 'chaine', 0, '', 0);
		dol_syslog("uptosign: Set server UUID $uuid", LOG_DEBUG);
	}

	return 'dolibarr/' . utsbackports_getDolGlobalString('MAIN_INFO_SOCIETE_NOM', '')  . " (uptosign@" . $modUptosign->version . ") [" . $uuid . "]";
}

/**
 * try to login with api key
 *
 * @return  string  [return description]
 */
function uptosignApiTryLoginWithAPIKey()
{
	global $conf, $langs, $db;

	dol_syslog('uptosign: uptosignApiTryLoginWithAPIKey Try to log in with api key ...');
	$apiClient = new UptoSignAPIClient($db);
	$response = $apiClient->getProfile();

	if ($response['http_code'] == 200 && !empty($response['content'])) {
		setEventMessages($langs->trans('CheckConnectOK'), [], 'mesgs');
		return true;
	}
	if (!empty($response['curl_error'])) {
		dol_syslog("uptosign: CURL error message is " . $response['curl_error']);
	}
	return false;
}

/**
 * Try to create an API key on the remote server.
 *
 * NOT IMPLEMENTED: the remote DocWizon API does not expose a documented endpoint
 * to self-provision an API key from the module. Keys are obtained through
 * uptosignApiCreateAccount() / uptosignApiTryLoginWithUserPass() which return an
 * access_token stored in UPTOSIGN_KEY_API. This function is intentionally kept as
 * an explicit "not implemented" so any future caller fails loudly (logged) instead
 * of silently believing a key was created.
 *
 * @return  bool  Always false (feature not available)
 */
function uptosignApiCreateAPIKey()
{
	dol_syslog("uptosign: uptosignApiCreateAPIKey is not implemented, no remote endpoint available to create an API key", LOG_ERR);
	return false;
}

/**
 * try to create an account on remote server via api call
 *
 * @return  bool  [return description]
 */
function uptosignApiCreateAccount()
{
	global $conf, $langs, $db, $user;
	$mesg = "";
	$mesgType = "errors";

	dol_syslog('uptosign: uptosignApiCreateAccount Try to create account ...');
	$firstname = ($user->firstname != '') ? $user->firstname : 'anonymous';
	$lastname = ($user->lastname != '') ? $user->lastname : 'anonyname';
	$email = utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', '');
	$password = dol_decode(utsbackports_getDolGlobalString('UPTOSIGN_PASS_API', ''));

	$apiClient = new UptoSignAPIClient($db);
	$response = $apiClient->createAccount($firstname, $lastname, $email, $password);

	if ($response['http_code'] == 200 && !empty($response['content'])) {
		$json = $response['data'];
		if (!is_array($json) || !isset($json['access_token'])) {
			dol_syslog("uptosign: uptosignApiCreateAccount 200 response without an access_token, can not store API key", LOG_ERR);
			$mesg = $langs->trans('CreateAccountError');
			setEventMessages($mesg, [], 'errors');
			return false;
		}
		dolibarr_set_const($db, 'UPTOSIGN_KEY_API', $json['access_token'], 'chaine', 0, '', $conf->entity);
		// A fresh key deserves a fresh chance: forget the previous auth failures
		UptoSignAPIClient::resetCircuit();
		$mesg = $langs->trans('CreateAccountOK');
		$mesgType = "mesgs";
		$retour = true;
	} else {
		$mesg = $langs->trans('CreateAccountError');
		if (!empty($response['content'])) {
			$mesg .= uptosignMergeMessage($response['content']);
		}
		$retour = false;
	}
	if (!empty($response['curl_error'])) {
		$mesg .= uptosignMergeMessage($response['curl_error']);
	}
	setEventMessages($mesg, [], $mesgType);
	return $retour;
}

/**
 * try to login on remote server with login/password
 *
 * @return  int  [return description]
 */
function uptosignApiTryLoginWithUserPass()
{
	global $conf, $langs, $db;
	$retour = -1;
	$mesg = "";
	$mesgType = "errors";

	dol_syslog('uptosign: uptosignApiTryLoginWithUserPass Try to log with user / pass ...');
	$email = utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', '');
	$password = dol_decode(utsbackports_getDolGlobalString('UPTOSIGN_PASS_API', ''));

	$apiClient = new UptoSignAPIClient($db);
	$response = $apiClient->login($email, $password);

	$retour = $response['http_code'];
	if ($response['http_code'] == 200 && !empty($response['content'])) {
		$json = $response['data'];
		if (!is_array($json) || !isset($json['access_token'])) {
			dol_syslog("uptosign: uptosignApiTryLoginWithUserPass 200 response without an access_token, can not store API key", LOG_ERR);
			return $retour;
		}
		dolibarr_set_const($db, 'UPTOSIGN_KEY_API', $json['access_token'], 'chaine', 0, '', $conf->entity);
		// A fresh key deserves a fresh chance: forget the previous auth failures
		UptoSignAPIClient::resetCircuit();
		$mesg = $langs->trans('CheckConnectOK');
		$mesgType = "mesgs";
	}
	if ($response['http_code'] == 401) {
		$mesg = $langs->trans('UptoSignErrorLoginWithUserPassError');
	}
	if (!empty($response['curl_error'])) {
		$mesg .= uptosignMergeMessage($response['curl_error']);
	}
	if (!empty($response['content']) && $response['http_code'] != 200) {
		dol_syslog('uptosign: uptosignApiTryLoginWithUserPass return message ' . json_encode($response['content']));
		$mesg = "";
	}

	if ($mesg != "") {
		setEventMessages($mesg, [], $mesgType);
	}
	dol_syslog('uptosign: uptosignApiTryLoginWithUserPass return value ' . $retour);
	return $retour;
}

/**
 * merge remote server messages
 *
 * @param   array|string  $srvMsg  [$srvMsg description]
 *
 * @return  string		   [return description]
 */
function uptosignMergeMessage($srvMsg)
{
	$msg = "";
	$m = json_decode($srvMsg);
	if (is_array($m)) {
		$msg = implode('', $m);
	} elseif (is_string($m)) {
		$msg = $m;
	} elseif (is_object($m)) {
		$msg = json_encode($m);
	}
	return stripslashes($msg);
}

/**
 * uptosignApiGetInfoAboutWebservice get informations about remote webservice (available, free, heavy loaded ...)
 * that function is directly called from php script (ie not from api.php AJAX)
 *
 * @return  string  [return description]
 */
function uptosignApiGetInfoAboutWebservice($format = 'html')
{
	global $conf, $mesg, $langs, $db;

	$module = new modUptoSign($db);

	$html = "";
	$json = "";

	$email = utsbackports_getDolGlobalString('UPTOSIGN_LOGIN', '');
	$apiClient = new UptoSignAPIClient($db);
	$response = $apiClient->healthCheck($email, $module->protocol);

	if ($response['http_code'] == 200 && !empty($response['content'])) {
		$arr = json_decode($response['content']);
		if (!is_object($arr) || !isset($arr->data) || !is_object($arr->data)) {
			dol_syslog("uptosign: uptosignApiGetInfoAboutWebservice 200 response with an unexpected body, can not read data", LOG_ERR);
			return ($format == 'html') ? '' : '';
		}
		$json = $arr->data->json ?? null;
		$html = $arr->data->html ?? '';

		//check if protocol version is the same
		if (is_object($json) && isset($json->protocol) && $json->protocol != $module->protocol) {
			$conf->global->UPTOSIGN_PROTOCOL_MISSMATCH = true;
			$html = "<div id=\"uptosign-account-status\">
			<h3 align=\"center\"><a href=\"https://app.uptosign.com\" target=\"_blank\">UpToSign WebService</a></h3>
			<p>" . $langs->trans('DEFAULT_UPTOSIGN_WEBSERVICE_PROTOCOL1') . " (srv=" . $json->protocol . ", local=" . $module->protocol . ")</p>
			<p><a href=\"https://app.uptosign.com/download/scaninvoice\" target=\"_blank\">" . $langs->trans('DEFAULT_UPTOSIGN_WEBSERVICE_PROTOCOL2') . "</a></p>
			</div>";
		}
		//save json list of server languages
		if (isset($json->srvlangs)) {
			$html .= "<input type=\"hidden\" name=\"srvlangs\" value=\"" . base64_encode(json_encode($json->srvlangs)) . "\">\n";
		}
	} elseif ($response['http_code'] == 503) {
		$html = "<div id=\"uptosign-account-status\">
		<h3 align=\"center\">" . $langs->trans('UPTOSIGN_SERVER_UPGRADE_IN_PROGRESS') . "...</h3>
		<p>" . $langs->trans('UPTOSIGN_SERVER_UPGRADE_IN_PROGRESS_MESSAGE') . ")</p>
		</div>";
	} else {
		$html = "<div id=\"uptosign-account-status\">
			<h3 align=\"center\"><a href=\"https://app.uptosign.com\" target=\"_blank\">UpToSign WebService</a></h3>
			<p>" . $langs->trans('DEFAULT_UPTOSIGN_WEBSERVICE_MSG1') . "</p>
			<p><a href=\"https://app.uptosign.com/tarifs\" target=\"_blank\">" . $langs->trans('DEFAULT_UPTOSIGN_WEBSERVICE_MSG2') . "</a></p>
			</div>";
	}
	if (!empty($response['curl_error'])) {
		$mesg = '<div class="error">' . $langs->trans('uptosignApiGetInfoAboutWebservice');
		$mesg .= '<br />' . $response['curl_error'];
		$mesg .= '</div>';
	}

	if ($format == 'html') {
		return $html;
	}
	return $json;
}

/**
 * Build random string $length
 */
function uptosignStrRand($length = 32)
{
	return bin2hex(random_bytes($length / 2));
}


/**
 * return document model like "azur" for a document
 *
 * @param   CommonObject  $document  [$document description]
 *
 * @return  string			 [return description]
 */
function uptosignModel($document)
{
	global $conf;

	$model = $document->model_pdf ?? '';
	if (empty($model)) {
		$model = $document->modelpdf ?? '';
	}
	if (empty($model)) {
		//try const like FACTURE_ADDON_PDF
		$key = strtoupper($document->element) . '_ADDON_PDF';
		if (utsbackports_getDolGlobalString($key, '')  != '') {
			$model = $conf->global->$key;
		}
	}
	return $model;
}

/**
 * extract model name from string like propal:azur
 *
 * @param   string  $modeltype  description
 *
 * @return  string		 [return description]
 */
function uptosign_unify_object_name($modeltype)
{
	if (strpos($modeltype, ':')) {
		$t = explode(':', $modeltype);
		return $t[1];
	}
	return $modeltype;
}

/**
 * extract model object type from string like propal:azur.
 *
 * @param   string  $modeltype  description
 *
 * @return  string		 [return description]
 */
function uptosign_unify_object_type($modeltype)
{
	// print "<p>uptosign_unify_object_type pour $modeltype</p>";
	dol_syslog("uptosign: uptosign_unify_object_type : " . $modeltype);
	if (strpos($modeltype, ':')) {
		$t = explode(':', $modeltype);
		return uptosign_unify_object_type_from_code($t[0]);
	}
	return uptosign_unify_object_type_from_code($modeltype);
}

/**
 * sometime SignOrSeal = "seal" or "uptoseal", that function make the job
 *
 * @param   string  $string  [$string description]
 *
 * @return  string		   [return description]
 */
function uptosign_unify_api_name($string)
{
	if (strpos($string, 'upto') === false) {
		return 'upto' . $string;
	}
	return $string;
}

/**
 * translate "code" object name to unique code name (ex facture/invoice -> invoice).
 *
 * @param   string  $code  [$code description]
 *
 * @return  string		 [return description]
 */
function uptosign_unify_object_type_from_code($code)
{
	// print "<p>uptosign_unify_object_type_from_code pour $code</p>";
	switch ($code) {
		case 'shipping':
		case 'expedition':
			$res = 'expedition';
			break;
		case 'invoice':
		case 'facture':
			$res = 'invoice';
			break;
		case 'order':
		case 'commande':
			$res = 'order';
			break;
		case 'contract':
		case 'contrat':
			$res = 'contrat';
			break;
		case 'supplier_order':
		case 'order_supplier':
			$res = 'order_supplier';
			break;
		default:
			$res = $code;
	}
	return $res;
}


/**
 * translate "code" object name to human name, ex: "propal" -> "proposition commerciale"
 *
 * @param   string  $code  [$code description]
 *
 * @return  string		 [return description]
 */
function uptosign_translate_object_type($code)
{
	global $langs;
	switch ($code) {
		case 'agenda':
			$res = $langs->trans('Agenda');
			break;
		case 'bankaccount':
			$res = $langs->trans('BankAccount');
			break;
		case 'contrat':
		case 'contract':
			$res = $langs->trans('Contract');
			break;
		case 'delivery':
			$langs->loadLangs(array("deliveries"));
			$res = $langs->trans('Delivery');
			break;
		case 'expensereport':
			// $langs->loadLangs(array("deliveries"));
			$res = $langs->trans('ExpenseReport');
			break;

		case 'propal':
			$res = $langs->trans('Proposal');
			break;
		case 'order':
			$res = $langs->trans('Order');
			break;
		case 'ficheinter':
		case 'fichinter':
			$res = $langs->trans('InterventionCard');
			break;
		case 'shipping':
		case 'expedition':
			$res = $langs->trans('Expedition');
			break;
		case 'invoice':
		case 'facture':
			$langs->loadLangs(array("bills"));
			$res = $langs->trans('Bill');
			break;
		case 'societe':
			$res = $langs->trans('ThirdParty');
			break;
		case 'invoice_supplier':
			$res = $langs->trans('SupplierBill');
			break;
		case 'mouvement':
			$res = $langs->trans('Mouvement');
			break;
		case 'order_supplier':
			$res = $langs->trans('SupplierOrder');
			break;
		case 'product':
			$res = $langs->trans('Product');
			break;
		case 'project':
			$res = $langs->trans('Project');
			break;
		case 'project_task':
			$res = $langs->trans('Task');
			break;
		case 'reception':
			$res = $langs->trans('Reception');
			break;
		case 'resource':
			$res = $langs->trans('Resource');
			break;
		case 'stock':
			$res = $langs->trans('Stock');
			break;
		case 'bank_account':
			$res = $langs->trans('RIB');
			break;
		default:
			$res = $code;
	}
	return $res;
}

function uptosign_full_path($path)
{
	if (!empty($path)) {
		if (false === strpos($path, DOL_DATA_ROOT)) {
			return rtrim(DOL_DATA_ROOT, '/\\') . '/' . ltrim($path, '/\\');
		}
	}
	return $path;
}

function uptosign_relative_path($path)
{
	return trim(str_replace(DOL_DATA_ROOT, '', $path ?? ''), '/\\');
}

/**
 * Restrict a list of UptoSign records to a single remote document
 *
 * A webhook carries the uuid of one document: only that one must be refreshed or
 * downloaded. Without this filter every webhook loops over all the documents of the
 * business object (4 documents x 4 webhooks = 16 downloads instead of 4), and the
 * slightest configuration problem turns into a burst of rejected requests.
 *
 * @param array  $children       List of UptoSign records
 * @param string $restrictSignId Remote uuid to keep ('' means no filter at all)
 * @return array                 Filtered list (empty if the uuid is unknown here)
 */
function uptosign_restrict_children_to_sign_id($children, $restrictSignId)
{
	if (!is_array($children) || (string) $restrictSignId === '') {
		return $children;
	}

	$filtered = array();
	foreach ($children as $child) {
		if (isset($child->sign_id) && (string) $child->sign_id === (string) $restrictSignId) {
			$filtered[] = $child;
		}
	}

	if (count($filtered) == 0) {
		dol_syslog('uptosign: uuid ' . $restrictSignId . ' does not match any of the ' . count($children) . ' record(s) of that object', LOG_WARNING);
	} else {
		dol_syslog('uptosign: restrict processing to uuid ' . $restrictSignId . ' (' . count($filtered) . '/' . count($children) . ' record)');
	}

	return $filtered;
}

/**
 * Read the entity owning an uptosign record
 *
 * Deliberately a direct read on the column: UptoSign::fetch*() disables the "entity"
 * field when the multicompany module is off, so $uptosign->entity cannot be trusted
 * there. The webhook needs that value before anything else, whatever the setup.
 *
 * @param DoliDB $db    Database handler
 * @param int    $rowid Id of the uptosign record
 * @return int          Entity of the record, 0 if unknown
 */
function uptosign_get_record_entity($db, $rowid)
{
	$rowid = (int) $rowid;
	if ($rowid <= 0) {
		dol_syslog('uptosign: can not read entity, invalid record id ' . $rowid, LOG_ERR);
		return 0;
	}

	$sql = "SELECT entity FROM " . MAIN_DB_PREFIX . "uptosign WHERE rowid = " . $rowid;
	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog('uptosign: can not read entity of record ' . $rowid . ' : ' . $db->lasterror(), LOG_ERR);
		return 0;
	}

	$obj = $db->fetch_object($resql);
	$db->free($resql);
	if (!$obj) {
		dol_syslog('uptosign: no record with id ' . $rowid . ', entity unknown', LOG_ERR);
		return 0;
	}

	return (int) $obj->entity;
}

/**
 * Switch the whole Dolibarr configuration to a given entity
 *
 * Used by the webhook (public/hook.php), which runs in NOLOGIN context: main.inc.php
 * has loaded the setup of entity 1 while the signature was started from another one.
 * The entity is never read from the URL, it is deduced from the uptosign record, so
 * that constants (UPTOSIGN_KEY_API in particular), data directories and the business
 * object are all read from the right place.
 *
 * @param DoliDB $db     Database handler
 * @param int    $entity Entity to switch to
 * @return int           Entity in use after the call, 0 if the switch could not be done
 */
function uptosign_switch_to_entity($db, $entity)
{
	global $conf, $langs, $mysoc;

	$entity = (int) $entity;
	if ($entity <= 0) {
		dol_syslog('uptosign: no entity to switch to, keep current entity ' . $conf->entity, LOG_WARNING);
		return 0;
	}

	if ($entity == (int) $conf->entity) {
		return $entity;
	}

	dol_syslog('uptosign: switch configuration from entity ' . $conf->entity . ' to entity ' . $entity);

	$res = $conf->setEntityValues($db, $entity);
	if ($res < 0) {
		dol_syslog('uptosign: setEntityValues failed for entity ' . $entity, LOG_ERR);
		return 0;
	}

	// $mysoc holds the company of the previous entity (name, country, currency, ...)
	if (isset($mysoc) && is_object($mysoc) && method_exists($mysoc, 'setMysoc')) {
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
		$mysoc = new Societe($db);
		$mysoc->setMysoc($conf);
	}

	// Same for the language, which was set from MAIN_LANG_DEFAULT of the previous entity
	$defaultLang = utsbackports_getDolGlobalString('MAIN_LANG_DEFAULT', '');
	if ($defaultLang != '' && isset($langs) && is_object($langs) && method_exists($langs, 'setDefaultLang')) {
		$langs->setDefaultLang($defaultLang);
	}

	return $entity;
}

/**
 * Detect a duplicate in-flight seal/sign request.
 *
 * Idempotency guard against double-clicks, F5 and back-arrow navigation on the seal/sign
 * confirmation page: re-posting the same form must not spawn a second remote procedure.
 *
 * Returns the existing UptoSign record only when ALL of the following match:
 *  - same fk_object + object_type
 *  - same api_name (uptosign and uptoseal are independent procedures)
 *  - same path_file (same source PDF)
 *  - status === STATUS_WAITING (procedure still pending)
 *  - hash_file === sha256 of the current source file (different hash means the document
 *    has been regenerated, the caller is allowed to create a fresh procedure)
 *
 * @param UptoSign $uts            Fresh UptoSign instance used to perform the lookup
 * @param int      $fkObject       Business object id
 * @param string   $objectType     Unified object type (eg. "propal", "commande", ...)
 * @param string   $apiName        "uptosign" or "uptoseal"
 * @param string   $sourceFullPath Absolute path of the source PDF the caller is about to submit
 * @return UptoSign|null           The in-flight record if a duplicate is detected, null otherwise
 */
function uptosign_find_duplicate_inflight(UptoSign $uts, $fkObject, $objectType, $apiName, $sourceFullPath)
{
	if ($sourceFullPath === '' || !is_file($sourceFullPath)) {
		dol_syslog("uptosign: duplicate-inflight check skipped, source file not readable: $sourceFullPath", LOG_WARNING);
		return null;
	}

	$pathFilter = uptosign_relative_path($sourceFullPath);
	$existing = $uts->fetchByObject((int) $fkObject, (string) $objectType, array(
		'api_name'  => (string) $apiName,
		'path_file' => $pathFilter,
	));
	if (!is_array($existing) || count($existing) === 0) {
		return null;
	}

	$currentHash = hash_file('sha256', $sourceFullPath);
	foreach ($existing as $ex) {
		if ((int) $ex->status === UptoSign::STATUS_WAITING && $ex->hash_file === $currentHash) {
			return $ex;
		}
	}
	return null;
}

/**
 * create new file name according to dolibarr guidelines
 *
 * @param   string $filename	[$filename description]
 * @param   string $suffix  	   [$suffix description]
 * @param   int $ts		  [$ts description]
 *
 * @return  string			 [return description]
 */
function uptosign_rename_file_dolibarr_guidelines($filename, $suffix = '', $ts = null)
{
	global $db;
	dol_syslog("uptosign: uptosign_rename_file_dolibarr_guidelines filename=$filename, suffix=$suffix, ts=$ts");

	$s = '';
	if ($suffix != '') {
		$s = '-' . $suffix;
	}

	$patterns = array();
	$patterns[0] = '/(' . $s . ')?(-\d{14})?.pdf$/'; //pour nettoyer eventuellement un fichier avec le suffixe dolibarr17

	$replacements = array();
	$replacements[0] = $s . '.pdf';

	$new =  preg_replace($patterns, $replacements, $filename);
	return $new;

	// -------------------------------- TODO eventuel ----------------------------------

	// 2023 : dolibarr core add full timestamp to signed filename so we could do the same
	// but that is a bad idea, more details on
	// https://www.dolibarr.fr/forum/t/dolibarr-17-suffixe-aux-fichiers-pdf-pseudo-signes/42677
	// $ecmfile	 = new EcmFilesExtended($db);
	//Archivage d'une copie du fichier avec étiquette sign / seal
	// $newfilename = $ecmfile->archiveStore($filename, ['uptosign',$suffix], 'copy');
	//puis changement du nom du fichier qui est à la racine de l'espace de stockage
	//dol_move($filename, dirname($filename) . '/' . basename($newfilename));

	// if (null === $ts) {
	// 	$ts = dol_now();
	// }
	// $date = dol_print_date($ts, "%Y%m%d%H%M%S");

	// $s = '';
	// if ($suffix != '') {
	// 	$s = '-' . $suffix;
	// }

	// $patterns = array();
	// $patterns[0] = '/(' . $s . ')?(-\d{14})?.pdf$/';

	// $replacements = array();
	// $replacements[0] = $s . '-' . $date . '.pdf';

	// $new =  preg_replace($patterns, $replacements, $filename);
	// return $new;
}

/**
 * find next available file name,
 *
 * for example in a dir there is FA2203-0012.pdf next name will be FA2203-0012-1.pdf
 * the file after : FA2203-0012-2.pdf, then FA2203-0012-3.pdf ...
 *
 * @param   string $str		 [$str description]
 * @param   string $signOrSeal  [$signOrSeal description]
 * @param   int $ts		  [$ts description]
 *
 * @return  string 			 [return description]
 */
function uptosign_find_next_filename($str, $signOrSeal = '', $ts = null)
{
	// 2023 : dolibarr core add full timestamp to signed filename so we could do the same
	// but that is a bad idea, more details on
	// https://www.dolibarr.fr/forum/t/dolibarr-17-suffixe-aux-fichiers-pdf-pseudo-signes/42677
	// if (null === $ts) {
	// 	$ts = dol_now();
	// }
	// $date = dol_print_date($ts, "%Y%m%d%H%M%S");

	// $s = '';
	// if ($signOrSeal != '') {
	// 	$s = '-' . $signOrSeal;
	// }

	// $patterns = array();
	// $patterns[0] = '/(' . $s . ')?(-\d{14})?.pdf$/';

	// $replacements = array();
	// $replacements[0] = $s . '-' . $date . '.pdf';

	// $new =  preg_replace($patterns, $replacements, $str);
	// return $new;

	return $str;
}

function uptosign_make_document_title($ref, $customer_ref, $typeOfObject)
{
	global $langs;
	$key = 'UptoSignMailSubject' . ucfirst($typeOfObject);
	$str = $langs->trans($key, $ref);

	if ($str == $key) {
		$str = $ref;
	}

	if (!empty($customer_ref)) {
		$str .= " (" . $customer_ref . ")";
	}

	return $str;
}

/**
 * Resolve the MediaBox to use for coordinate conversion.
 *
 * MediaBox is an INHERITABLE PDF attribute: a document may declare it only on
 * the parent /Pages node and omit it from each individual /Page. Smalot does
 * NOT resolve that inheritance, so $page->getDetails()['MediaBox'] is then
 * absent. In production this raised "Undefined array key 3" on every page and
 * left the page height null, which silently broke (or garbled) the magic
 * keyword positioning on those files.
 *
 * Resolution order:
 *   1. the page's own MediaBox (passed in $pageDetails) when valid,
 *   2. the MediaBox inherited from any /Pages node of the document,
 *   3. A4 in points (595.276 x 841.890) as a last resort, with a log.
 *
 * @param   Smalot\PdfParser\Document  $pdf          parsed PDF document
 * @param   array                      $pageDetails  current page getDetails()
 *
 * @return  float[]  MediaBox [llx, lly, urx, ury] in points (always 4 floats)
 */
function uptosign_resolveMediaBox($pdf, $pageDetails)
{
	// 1. page-level MediaBox.
	if (!empty($pageDetails['MediaBox']) && is_array($pageDetails['MediaBox']) && count($pageDetails['MediaBox']) >= 4) {
		return $pageDetails['MediaBox'];
	}

	// 2. MediaBox inherited from the parent /Pages node(s).
	try {
		foreach ($pdf->getObjectsByType('Pages') as $pagesNode) {
			$d = $pagesNode->getDetails();
			if (!empty($d['MediaBox']) && is_array($d['MediaBox']) && count($d['MediaBox']) >= 4) {
				dol_syslog("uptosign: uptosign_resolveMediaBox using MediaBox inherited from /Pages node", LOG_DEBUG);
				return $d['MediaBox'];
			}
		}
	} catch (\Throwable $e) {
		dol_syslog("uptosign: uptosign_resolveMediaBox failed to read /Pages node: " . $e->getMessage(), LOG_WARNING);
	}

	// 3. A4 default (595.276 x 841.890 pt). Logged so we know the PDF lacked a
	//    usable MediaBox entirely.
	dol_syslog("uptosign: uptosign_resolveMediaBox no MediaBox at page nor /Pages level, defaulting to A4", LOG_WARNING);
	return [0.0, 0.0, 595.276, 841.890];
}

/**
 * Search for a keyword inside every page of a PDF and return its position(s).
 *
 * TOOL A = Smalot\PdfParser (pure PHP).
 *
 * Coordinate system of this tool:
 *   - getDataTm() returns the text matrix Tm, with X at index [4] and Y at
 *     index [5], expressed in PDF user-space units = POINTS (1/72 inch).
 *   - The PDF native origin is the BOTTOM-LEFT corner of the page, Y growing
 *     upwards.
 *
 * Target coordinate system expected by the remote API (signArray/stampArray):
 *   - MILLIMETERS, origin TOP-LEFT corner, Y growing downwards.
 *
 * So the conversion is: points -> mm (divide by PT_PER_MM) AND flip Y
 * (Y_top = pageHeight - Y_bottom). This is the only difference with TOOL B
 * (pdftotext, see uptosign_autoFindWordPositionInPagepdftotext) whose
 * coordinates are already top-left, hence no flip there.
 *
 * @param   Smalot\PdfParser\Document  $pdf      parsed PDF document
 * @param   string                     $keyword  word to look for (matched even if split across several consecutive text-show tokens)
 * @param   array                      $result   appended with [X_mm, Y_mm, humanPage]
 *
 * @return  bool   true if at least one match was found, false otherwise
 */
function uptosign_autoFindWordPositionInPage($pdf, $keyword, &$result)
{
	dol_syslog("uptosign: uptosign_autoFindWordPositionInPage keyword=$keyword");
	$return = false;

	// 1 mm = 72/25.4 = 2.8346 pt. The historical code uses 2.83; we keep the
	// same value so the smalot path (TOOL A) and the pdftotext path (TOOL B)
	// stay perfectly consistent (sub-mm difference otherwise).
	$ptPerMm = 2.83;

	// Smalot's getPages() returns a 0-indexed sequential array (built with
	// array_values()/array_merge()), so $pages[0] is the FIRST physical page.
	// The previous loop iterated $pages[1..N] which (a) silently skipped the
	// real first page and (b) reported every match one page too early.
	// We iterate the array directly and compute a 1-based human page number.
	$humanPage = 0;
	foreach ($pdf->getPages() as $page) {
		$humanPage++;
		$details = $page->getDetails();

		// MediaBox = [llx, lly, urx, ury] in POINTS. Origin is usually (0,0)
		// but not always, so we use the box span instead of urx/ury directly.
		// MediaBox may be inherited from /Pages (not present on the page), so
		// we resolve it instead of reading $details['MediaBox'] blindly.
		$mediaBox = uptosign_resolveMediaBox($pdf, $details);
		$originX  = (float) $mediaBox[0];
		$originY  = (float) $mediaBox[1];
		$pageHeightPt = (float) $mediaBox[3] - $originY;

		$data = $page->getDataTm();
		// Some PDFs (e.g. Word/LibreOffice exports where kerning forces extra
		// positioning operators) split one visual word across several separate
		// text-show tokens, e.g. UPTOSIGN_STAMP_SIGN_HERE -> [UPTOSIGN][_][ST][AM][P]...
		// getText() silently reassembles those, but getDataTm() does not, so the
		// previous strict per-token equality test missed the keyword even though
		// it visually exists. We now also try concatenating consecutive tokens
		// starting at each position until they match the keyword (or stop being
		// a valid prefix of it), so fragmented keywords are found too.
		$nbTokens = count($data);
		for ($idx = 0; $idx < $nbTokens; $idx++) {
			$acc		= '';
			$consumed	= 0;
			for ($j = $idx; $j < $nbTokens; $j++) {
				$acc		.= trim($data[$j][1]);
				$consumed++;
				if ($acc === $keyword) {
					$dataWord = $data[$idx];
					// X: pt -> mm, relative to the page left edge, minus a 2 mm
					//    offset so the stamp/signature box starts on the word, not
					//    just after it.
					$X = round((($dataWord[0][4] - $originX) / $ptPerMm) - 2);
					// Y: flip from bottom-left to top-left, then pt -> mm.
					//    This now works for ANY page size (A4, Letter, landscape,
					//    custom) because we convert the real page height in points
					//    instead of special-casing the A4 dimensions.
					$Y = round(($pageHeightPt - ($dataWord[0][5] - $originY)) / $ptPerMm - 2);
					$result[] = [$X, $Y, $humanPage];
					$return = true;
					//stop a la 1ere position trouvée ... plus maintenant
					//break;
					break;
				}
				if (strpos($keyword, $acc) !== 0) {
					// $acc is no longer a prefix of $keyword: abandon this start point
					break;
				}
			}
			if ($acc === $keyword) {
				// skip the tokens just consumed so they are not reused as a new start point
				$idx += $consumed - 1;
			}
		}
	}
	dol_syslog("uptosign: uptosign_autoFindWordPositionInPage result is " . $return . " then resut is " .  json_encode($result));
	return $return;
}

/**
 * find position on PDF for special keywords
 *
 * @param   string  $pdf		pdf file
 * @param  	array  $arr	  	array (return data)
 * @param   string  $action  	action = presign or preseal
 *
 *                              preseal
 *
 * @return  bool true on success, false otherwise
 */
function uptosign_auto_position_magic_keywords($pdf, &$arr, $action)
{
	global $conf;
	dol_syslog("uptosign: call uptosign_auto_position_magic_keywords action=$action for $pdf");
	//first try with smalot pdf native
	if (uptosign_auto_position_magic_keywords_smalot($pdf, $arr, $action)) {
		dol_syslog("uptosign: uptosign_auto_position_magic_keywords call smalot success, returns " . json_encode($arr));
		return true;
	}

	//then with pdftotext
	if (utsbackports_getDolGlobalString('UPTOSIGN_USE_PDFTOTEXT', '')  != '') {
		if (uptosign_auto_position_magic_keywords_pdftotext($pdf, $arr, $action)) {
			dol_syslog("uptosign: uptosign_auto_position_magic_keywords call pdftotext success, returns " . json_encode($arr));
			return true;
		}
	}

	dol_syslog("uptosign: uptosign_auto_position_magic_keywords error, there is no magic keyword" . json_encode($arr));
	return false;
}

/**
 * find magic keyword with full php implementation thanks to smalot
 *
 * @param   string  $pdffilename     [$pdffilename description]
 * @param   array  $arr     [$arr description]
 * @param   string  $action  [$action description]
 *
 * @return  bool           [return description]
 */
function uptosign_auto_position_magic_keywords_smalot($pdffilename, &$arr, $action)
{
	global $langs;
	$langs->loadLangs(array("propal"));

	dol_syslog("uptosign: auto_position_smalot action=$action, for $pdffilename");
	$return = false;
	//recherche des mots clés UPTOSIGN_SIGN_TO_HERE / UPTOSIGN_SIGN_FROM_HERE / UPTOSIGN_STAMP_SIGN_HERE | UPTOSIGN_STAMP_SEAL_HERE
	$nbSeal = 0;
	$nbsignContact = 0;
	$nbsignUser = 0;
	try {
		$parser = new \Smalot\PdfParser\Parser();
		$pdf = $parser->parseFile($pdffilename);
		$resA = array();

		$keywords = ['STAMP' => "UPTOSIGN_STAMP_SIGN_HERE"];
		if ($action == 'preseal') {
			$keywords = ['STAMP' => "UPTOSIGN_STAMP_SEAL_HERE"];
		}

		foreach ($keywords as $key => $keyword) {
			if (!empty(uptosign_autoFindWordPositionInPage($pdf, $keyword, $resA))) {
				foreach ($resA as $reskeyword) {
					list($x, $y, $page) = $reskeyword;
					$arr[$key][$page] = ['p' => $page, 'x' => $x, 'y' =>  $y];
					$nbSeal++;
				}
				$return = true;
			}
		}

		/** @phpstan-ignore-next-line */
		$keywords = ["SIGN_00" => "UPTOSIGN_SIGN_TO_HERE", "SIGN_00new" => "UPTOSIGN_SIGN_TO_00_HERE", "SIGN_01" => "UPTOSIGN_SIGN_TO_01_HERE", "SIGN_02" => "UPTOSIGN_SIGN_TO_02_HERE"];
		foreach ($keywords as $key => $keyword) {
			//bad due to history
			if ($key == "SIGN_00new") {
				$key = "SIGN_00";
			}
			// Reset the accumulator on each keyword: uptosign_autoFindWordPositionInPage()
			// appends to it, so a shared array leaks the positions of the previous
			// keyword (SIGN_00 -> SIGN_01 -> SIGN_02) and duplicates signature areas.
			$resB = array();
			if (!empty(uptosign_autoFindWordPositionInPage($pdf, $keyword, $resB))) {
				foreach ($resB as $reskeyword) {
					list($x, $y, $page) = $reskeyword;
					$arr[$key][$page] = ['p' => $page, 'x' => $x, 'y' =>  $y];
					$nbsignContact++;
				}
				$return = true;
				//stop a la 1ere position trouvée ... plus maintenant
				//break;
			}
		}

		$keywords = ["FROM_00" => "UPTOSIGN_SIGN_FROM_HERE"];
		foreach ($keywords as $key => $keyword) {
			$resC = array();
			if (!empty(uptosign_autoFindWordPositionInPage($pdf, $keyword, $resC))) {
				foreach ($resC as $reskeyword) {
					list($x, $y, $page) = $reskeyword;
					$arr[$key][$page] = ['p' => $page, 'x' => $x, 'y' =>  $y];
					$nbsignUser++;
				}
				$return = true;
				//stop a la 1ere position trouvée ... plus maintenant
				//break;
			}
		}
	} catch (Exception $e) {
		dol_syslog("uptosign: auto_position_smalot ERREUR pour extraire les positions des signatures " . json_encode($e), LOG_WARNING);
	} catch (Error $e) {
		dol_syslog("uptosign: auto_position_smalot ERREUR PHP pour extraire les positions des signatures " . json_encode($e), LOG_WARNING);
	}
	dol_syslog("uptosign: auto_position_smalot returns " . json_encode($arr));
	return $return;
}

/**
 * auto position with external command call of pdftotext
 *
 * @param   string  $pdffilename     [$pdffilename description]
 * @param   array  $arr     [$arr description]
 * @param   string  $action  [$action description]
 *
 * @return  bool           [return description]
 */
function uptosign_auto_position_magic_keywords_pdftotext($pdffilename, &$arr, $action)
{
	global $langs;
	dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext for $pdffilename");
	$return = false;
	//recherche des mots clés UPTOSIGN_SIGN_TO_HERE / UPTOSIGN_SIGN_FROM_HERE / UPTOSIGN_STAMP_SIGN_HERE | UPTOSIGN_STAMP_SEAL_HERE
	$nbSeal = 0;
	$nbsignContact = 0;
	$nbsignUser = 0;
	try {
		$cmd = "pdftotext -bbox " . escapeshellarg($pdffilename) . " -";
		dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext cmd is $cmd");
		$output = array();
		// exec() only returns false on a failure to start the process; when
		// pdftotext is missing the shell exits with a non-zero code but exec()
		// still returns the (empty) last line, so we must inspect $resultCode
		// to actually detect an unavailable/failed command.
		$resultCode = 0;
		exec($cmd, $output, $resultCode);
		if ($resultCode == 0) {
			$resA = array();
			$keywords = ['STAMP' => "UPTOSIGN_STAMP_SIGN_HERE"];
			if ($action == 'preseal') {
				$keywords = ['STAMP' => "UPTOSIGN_STAMP_SEAL_HERE"];
			}

			foreach ($keywords as $key => $keyword) {
				if (!empty(uptosign_autoFindWordPositionInPagepdftotext($output, $keyword, $resA))) {
					foreach ($resA as $reskeyword) {
						list($x, $y, $page) = $reskeyword;
						$arr[$key][$page] = ['p' => $page, 'x' => $x, 'y' =>  $y];
					}
					$return = true;
				}
			}
			dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext for " . json_encode($keywords) . ", result is " . json_encode($arr));

			/** @phpstan-ignore-next-line */
			$keywords = ["SIGN_00" => "UPTOSIGN_SIGN_TO_HERE", "SIGN_00new" => "UPTOSIGN_SIGN_TO_00_HERE", "SIGN_01" => "UPTOSIGN_SIGN_TO_01_HERE", "SIGN_02" => "UPTOSIGN_SIGN_TO_02_HERE"];
			foreach ($keywords as $key => $keyword) {
				//bad due to history
				if ($key == "SIGN_00new") {
					$key = "SIGN_00";
				}
				// Reset the accumulator on each keyword to avoid leaking the
				// positions of SIGN_00 into SIGN_01/SIGN_02 (see smalot variant).
				$resB = array();
				if (!empty(uptosign_autoFindWordPositionInPagepdftotext($output, $keyword, $resB))) {
					foreach ($resB as $reskeyword) {
						list($x, $y, $page) = $reskeyword;
						$arr[$key][$page] = ['p' => $page, 'x' => $x, 'y' =>  $y];
					}
					$return = true;
					//stop a la 1ere position trouvée ... plus maintenant
					//break;
				}
			}
			dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext for " . json_encode($keywords) . ", result is " . json_encode($arr));

			//recherche de la ligne cachet, bon pour accord... signez ici
			// TODO ameliorer: nombre de mots + ponderation + stats > 80% de match sur une page bingo
			// $langs->load("propal");
			// $keywords = explode(" ",str_replace(['"',"'"], "", $langs->trans("ProposalCustomerSignature")));
			// $resLine = ['y' => 0, 'match' => 0, 'err' => 0];
			// foreach ($keywords as $keyword) {
			// 	if(strlen($keyword) < 4) {
			// 		continue;
			// 	}
			//     if (!empty(uptosign_autoFindWordPositionInPagepdftotext($output, $keyword, $resB))) {
			// 		foreach ($resB as $reskeyword) {
			//             list($x, $y, $p) = $reskeyword;
			// 			dol_syslog("uptosign: call uptosign_auto_position_magic_keywords_pdftotext search for $keyword, $x, $y,$p");
			// 			//1er coup initialisation
			//             if (empty($resLine['y'])) {
			//                 $resLine['x'] = $x;
			// 				$resLine['y'] = $y;
			// 				$resLine['p'] = $p;
			//                 $resLine['match'] = 1;
			//             } else {
			// 				//coups suivant comparaison
			// 				if($resLine['y'] == $y) {
			// 					$resLine['match']++;
			// 				}
			// 			}
			//         }
			//     }
			// }
			// if(count($resLine['y']) > 0) {
			// 	$arr[$nbsignContact]['defaultSignContactX'] = $resLine['x'];
			// 	$arr[$nbsignContact]['defaultSignContactY'] = $resLine['y'];
			// 	$arr[$nbsignContact]['defaultSignContactPage'] = $resLine['p'];
			// 	$nbsignContact++;
			// 	$return = true;
			// }

			$keywords = ["FROM_00" => "UPTOSIGN_SIGN_FROM_HERE"];
			foreach ($keywords as $key => $keyword) {
				$resC = array();
				if (!empty(uptosign_autoFindWordPositionInPagepdftotext($output, $keyword, $resC))) {
					foreach ($resC as $reskeyword) {
						list($x, $y, $page) = $reskeyword;
						$arr[$key][$page] = ['p' => $page, 'x' => $x, 'y' =>  $y];
						$nbsignUser++;
					}
					$return = true;
					//stop a la 1ere position trouvée ... plus maintenant
					//break;
				}
			}
			dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext for " . json_encode($keywords) . ", result is " . json_encode($arr));
		} else {
			dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext Err. de la commande pdftotext ... est-elle disponible sur ce serveur ?", LOG_WARNING);
			setEventMessages($langs->trans("Error") . $langs->trans("uptosignErrorPdfToText"), [], 'warnings');
		}
	} catch (Exception $e) {
		dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext ERREUR pour extraire les positions des signatures " . json_encode($e), LOG_ERR);
	}
	dol_syslog("uptosign: uptosign_auto_position_magic_keywords_pdftotext::auto_position_pdftotext returns " . json_encode($arr));
	return $return;
}

/**
 * Search a keyword inside the XHTML produced by `pdftotext -bbox`.
 *
 * TOOL B = pdftotext / Poppler (external command).
 *
 * Coordinate system of this tool:
 *   - The -bbox output gives xMin/yMin/xMax/yMax in POINTS (1/72 inch).
 *   - The origin is the TOP-LEFT corner of the page, Y growing downwards
 *     (HTML/screen convention), which is ALREADY the orientation the remote
 *     API expects.
 *
 * Consequence vs TOOL A (Smalot, see uptosign_autoFindWordPositionInPage):
 *   - Both convert points -> mm (divide by the same PT_PER_MM constant).
 *   - TOOL A must FLIP the Y axis (PDF native is bottom-left); TOOL B must NOT
 *     (it is already top-left). That single flip is the whole reason the two
 *     functions compute Y differently.
 *
 * Pages are counted from the `<page ...>` tags, giving a 1-based human page
 * number consistent with TOOL A.
 *
 * @param   array   $text     pdftotext -bbox output, one entry per line
 * @param   string  $keyword  string / keyword to search
 * @param   array   $result   appended with [X_mm, Y_mm, humanPage]
 *
 * @return  bool    true if at least one match was found, false otherwise
 */
function uptosign_autoFindWordPositionInPagepdftotext($text, $keyword, &$result)
{
	dol_syslog("uptosign: uptosign_autoFindWordPositionInPagepdftotext text is " . count($text) . " of lines");
	$return = false;
	$pageNb = 0;

	// Same constant as TOOL A so both engines yield identical coordinates.
	$ptPerMm = 2.83;

	$regexNumPage = '/<page.*width.*>/i';
	$regex = '/<.*xMin=\"(?P<x>([0-9,\.]+))\".*yMin=\"(?P<y>([0-9,\.]+))\" .*>(?P<word>' . preg_quote($keyword, '/') . ')/i';
	foreach ($text as $line) {
		if (preg_match_all($regexNumPage, $line)) {
			$pageNb++;
		}
		$matches = [];
		if (preg_match_all($regex, $line, $matches)) {
			// pt -> mm, minus a 2 mm offset. No Y flip: coordinates are
			// already top-left (see the doc block above). Use round() (not
			// number_format() which returns a locale-formatted string with a
			// thousands separator) to stay consistent with the smalot variant.
			$X = round(($matches['x'][0] / $ptPerMm) - 2);
			$Y = round(($matches['y'][0] / $ptPerMm) - 2);
			$result[] = [$X, $Y, $pageNb];
			$return = true;
			//stop a la 1ere position trouvée ... plus maintenant
			//break;
		}
	}

	return $return;
}

/**
 * send light mail (notifications)
 *
 * @param   string  $to       [$to description]
 * @param   string  $subject  [$subject description]
 * @param   string  $message  [$message description]
 *
 * @return  bool            [return description]
 */
function uptosign_send_mail($to, $subject, $message)
{
	global $conf, $langs, $mysoc;

	$from = utsbackports_getDolGlobalString('MAIN_MAIL_EMAIL_FROM', '');
	if (empty(trim($from)) || empty(trim($to))) {
		dol_syslog("uptosign: uptosign_send_mail early return, from=$from or to=$to is empty", LOG_INFO);
		return;
	}

	$ishtml = 0;
	if (dol_textishtml($message)) {
		$ishtml = 1;
	}

	$realsubject = html_entity_decode('[Dolibarr/UpToSign] ' . $subject);
	$realmessage = "<p>" . $langs->trans('UpToSignMailHello') . "</p>\n";
	$realmessage .= "<p>" . $message . "</p>\n";
	$realmessage .= $langs->transnoentitiesnoconv('UpToSignMailGoodBye');
	$realmessage .= "<br />\n<br />\n--<br />\n";
	$realmessage .= "<p>" . $langs->trans('UpToSignMailSignature', $mysoc->name) . "</p>";

	$trackid = 'uts' . dol_now();
	$moreinheader = 'X-Dolibarr-Info: uptosign_send_mail' . "\r\n";
	$addr_bcc = getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO');

	$result = null;
	try {
		$mailfile = new CMailFile($realsubject, $to, $from, $realmessage, array(), array(), array(), '', $addr_bcc, 0, $ishtml, '', '', $trackid, $moreinheader);
		$result = $mailfile->sendfile();
	} catch (Exception $e) {
		dol_syslog("uptosign: error sending mail, exception is " . json_encode($e), LOG_ERR);
	}

	if ($result) {
		dol_syslog("uptosign: uptosign_send_mail sent to " . $to, LOG_DEBUG);
	} else {
		dol_syslog("uptosign: uptosign_send_mail Failed to send EMail to " . $to, LOG_ERR);
	}

	return $result;
}


function uptosignApiCheckResellerMode()
{
	global $db;

	dol_syslog('uptosign: uptosignApiCheckResellerMode Try to log in with api key ...');
	$apiClient = new UptoSignAPIClient($db);
	$response = $apiClient->getProfile();

	if ($response['http_code'] == 200 && !empty($response['content'])) {
		return $response['data'];
	}
	if (!empty($response['curl_error'])) {
		dol_syslog("uptosign: CURL error message is " . $response['curl_error']);
	}
	return null;
}

//Returns a qrcode of uri
function uptosignQRCode($uri)
{
	$qrmodule = new modTcpdfbarcode();

	$tcpdfEncoding = $qrmodule->getTcpdfEncodingType('QRCODE');
	if (empty($tcpdfEncoding)) {
		return -1;
	}

	$color = array(0, 0, 0);
	$height = 3;
	$width = 3;
	require_once TCPDF_PATH . 'tcpdf_barcodes_2d.php';
	$barcodeobj = new TCPDF2DBarcode($uri, $tcpdfEncoding);
	return $barcodeobj->getBarcodePngData($width, $height, $color);
}


/**
 * recherche le tiers qui a cette adresse mail
 *
 * @param   string  $email  [$email description]
 *
 * @return  Societe          [return description]
 */
function uptosignSearchThirdpartWithEmail($email)
{
	global $db, $langs, $conf;
	$object = new Societe($db);
	//$rowid, $ref = '', $ref_ext = '', $barcode = '', $idprof1 = '', $idprof2 = '', $idprof3 = '', $idprof4 = '', $idprof5 = '', $idprof6 = '', $email = '', $ref_alias = '')
	$result = $object->fetch('', '', '', '', '', '', '', '', '', '', $email);
	if ($result > 0) {
		return $object;
	}
	//TODO si pas de resultat, chercher dans les contacts ?

	return null;
}

/**
 * recherche s'il existe un contrat 'magique' pour ce tiers
 *
 * @param   int  $customerid  local dolibarr customer thirdpart id
 *
 * @return  Contrat|null          [return description]
 */
function uptosignSearchUptoSignContract($customerid)
{
	global $db, $langs, $conf;
	$object = new Contrat($db);
	$res = $object->fetch('', '', 'uptosign-' . $customerid);
	if ($res > 0) {
		return $object;
	}
	return null;
}

/**
 * recherche s'il existe une facture récurrent 'magique' pour ce tiers
 *
 * @param   Contrat  $contract  local dolibarr contract object
 *
 * @return  FactureRec|null          [return description]
 */
function uptosignSearchUptoSignFactureRec($contract)
{
	global $db, $langs, $conf;

	$res = $contract->fetchObjectLinked($contract->id, 'contrat', null, 'facturerec', 'AND', 1, 'sourcetype', 0);
	if ($res <= 0) {
		dol_syslog("uptosign: uptosignSearchUptoSignFactureRec there is no facturerec linked to that contract, sorry");
		return null;
	}
	// dol_syslog("uptosign: uptosignSearchUptoSignFactureRec facturerec is " . json_encode($contract->linkedObjectsIds));
	$factureredid = array_values($contract->linkedObjectsIds['facturerec'])[0];
	if ($factureredid) {
		$object = new FactureRec($db);
		$res = $object->fetch($factureredid);
		if ($res) {
			return $object;
		}
	}
	return null;
}


/**
 * creation automatique d'un contrat dolibarr
 *
 * @param   int  $customerid  id of dolibarr customer
 * @param   int  $uptosignid  id of uptosign customer
 *
 * @return  int               [return description]
 */
function uptosignCreateContract($customerid, $uptosignid)
{
	global $db, $langs, $conf, $user, $mysoc;
	dol_syslog("uptosign: uptosignCreateContract, customerid=$customerid uptosignid=$uptosignid", LOG_ERR);

	if (empty($customerid) || empty($uptosignid)) {
		dol_syslog("uptosign: Erreur, customerid or uptosignid empty on autoCreateContract call", LOG_ERR);
		return -1;
	} else {
		$object = new Societe($db);
		//$rowid, $ref = '', $ref_ext = '', $barcode = '', $idprof1 = '', $idprof2 = '', $idprof3 = '', $idprof4 = '', $idprof5 = '', $idprof6 = '', $email = '', $ref_alias = '')
		$result = $object->fetch($customerid);
		if ($result < 0) {
			return -2;
		}

		//duplicate ?
		$contract = new Contrat($db);
		$res = $contract->fetch('', '', 'uptosign-' . $customerid);
		if ($res > 0) {
			return $contract->id;
		}

		$now = dol_now();
		if ($res <= 0) {
			$db->begin('uptosignCreateContract create contract');
			$contract->socid = $customerid;
			$contract->commercial_signature_id = $user->id;
			$contract->commercial_suivi_id = $user->id;
			$contract->date_contrat = $now;
			$contract->note_private = 'Contract created from the uptosign module.';
			$contract->ref_customer = 'uptosign-' . $customerid;
			$contract->ref_ext = 'uptosign-' . $customerid;

			$result = $contract->create($user);
			if ($result <= 0) {
				$db->rollback();
				dol_print_error_email('CREATECONTRACT', $contract->error, $contract->errors, 'alert alert-error');
				return (-10);
			}
		}

		// ----------------------------------------------------------
		//now add lines
		$tmpproduct = new Product($db);

		//                                                                               abonnement
		$res = $tmpproduct->fetch(utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_ABO', ''), '', '', '', 1, 1, 1);
		if ($res < 0) {
			$db->rollback();
			dol_print_error_email('CREATECONTRACT', $contract->error, $contract->errors, 'alert alert-error');
			return (-20);
		}
		$qty = 1;
		$vat = get_default_tva($mysoc, $object, $tmpproduct->id);
		$localtax1_tx = get_default_localtax($mysoc, $object, 1, 0);
		$localtax2_tx = get_default_localtax($mysoc, $object, 2, 0);
		$productidtocreate = $tmpproduct->id;
		$date_start = dol_get_first_day((int) date('Y'), (int) date('m'));
		$date_end = dol_get_last_day((int) date('Y'), (int) date('m'));
		$price = $tmpproduct->price;
		$discount = $object->remise_percent;
		$desc = $tmpproduct->description;

		$contractlineid = $contract->addline($desc, $price, $qty, $vat, $localtax1_tx, $localtax2_tx, $productidtocreate, $discount, $date_start, $date_end, 'HT', 0);
		if ($contractlineid < 0) {
			$db->rollback();
			dol_print_error_email('CREATECONTRACTLINE1', $contract->error, $contract->errors, 'alert alert-error');
			return (-30);
		}

		//                                                                               signatures
		$res = $tmpproduct->fetch(utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_SIGN', ''), '', '', '', 1, 1, 1);
		if ($res < 0) {
			$db->rollback();
			dol_print_error_email('CREATECONTRACT', $contract->error, $contract->errors, 'alert alert-error');
			return (-35);
		}
		$qty = 0;
		$vat = get_default_tva($mysoc, $object, $tmpproduct->id);
		$localtax1_tx = get_default_localtax($mysoc, $object, 1, 0);
		$localtax2_tx = get_default_localtax($mysoc, $object, 2, 0);
		$productidtocreate = $tmpproduct->id;
		$date_start = $now;
		$date_end = dol_get_last_day((int) date('Y'), (int) date('m'));
		$price = $tmpproduct->price;
		$discount = $object->remise_percent;
		$desc = $tmpproduct->description;

		$contractlineid = $contract->addline($desc, $price, $qty, $vat, $localtax1_tx, $localtax2_tx, $productidtocreate, $discount, $date_start, $date_end, 'HT', 0);
		if ($contractlineid < 0) {
			$db->rollback();
			dol_print_error_email('CREATECONTRACTLINE2', $contract->error, $contract->errors, 'alert alert-error');
			return (-39);
		}

		//                                                                               scellements
		$res = $tmpproduct->fetch(utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_SEAL', ''), '', '', '', 1, 1, 1);
		if ($res < 0) {
			$db->rollback();
			dol_print_error_email('CREATECONTRACT', $contract->error, $contract->errors, 'alert alert-error');
			return (-40);
		}
		$qty = 0;
		$vat = get_default_tva($mysoc, $object, $tmpproduct->id);
		$localtax1_tx = get_default_localtax($mysoc, $object, 1, 0);
		$localtax2_tx = get_default_localtax($mysoc, $object, 2, 0);
		$productidtocreate = $tmpproduct->id;
		$date_start = $now;
		$date_end = dol_get_last_day((int) date('Y'), (int) date('m'));
		$price = $tmpproduct->price;
		$discount = $object->remise_percent;
		$desc = $tmpproduct->description;

		$contractlineid = $contract->addline($desc, $price, $qty, $vat, $localtax1_tx, $localtax2_tx, $productidtocreate, $discount, $date_start, $date_end, 'HT', 0);
		if ($contractlineid < 0) {
			$db->rollback();
			dol_print_error_email('CREATECONTRACTLINE3', $contract->error, $contract->errors, 'alert alert-error');
			return (-45);
		}

		dol_syslog("uptosign: Reload all lines after creation to have contract->lines ok");
		$contract->fetch_lines();

		$contract->validate($user);
		$db->commit();
		setEventMessage($langs->trans('UptosignContractCreated'));
	}
	return $contract->id;
}


/**
 * creation automatique d'une facture récurrente dolibarr
 *
 * @param   int  $customerid  id of dolibarr customer
 * @param   int  $factureid	id de la facture à cloner comme facture recurrente
 *
 * @return  int               [return description]
 */
function uptosignCreateFactureRec($customerid, $contractid, $factureid)
{
	global $db, $langs, $conf, $user, $mysoc;
	$frequency = 1;
	$frequency_unit = 'm';
	$nb_gen_max = $error = 0;
	//print dol_print_date($date_start,'dayhour');
	//var_dump($remonth);

	$contract = new Contrat($db);
	$res = $contract->fetch($contractid);
	if ($res < 0) {
		return -1;
	}

	$invoice_draft = new Facture($db);
	$res = $invoice_draft->fetch($factureid);
	if ($res < 0) {
		return -2;
	}

	$invoice_rec = new FactureRec($db);

	$invoice_rec->titre = 'Template invoice for ' . $contract->ref . ' ' . $contract->ref_customer;
	$invoice_rec->title = 'Template invoice for ' . $contract->ref . ' ' . $contract->ref_customer;
	$invoice_rec->note_private = $contract->note_private;
	//$invoice_rec->note_public  = dol_concatdesc($contract->note_public, '__(Period)__ : __INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__ - __INVOICE_DATE_NEXT_INVOICE_AFTER_GEN__');
	$invoice_rec->note_public  = $contract->note_public;
	$invoice_rec->mode_reglement_id = $invoice_draft->mode_reglement_id;
	$invoice_rec->cond_reglement_id = $invoice_draft->cond_reglement_id;

	$invoice_rec->usenewprice = 0;

	$invoice_rec->frequency = $frequency;
	$invoice_rec->unit_frequency = $frequency_unit;
	$invoice_rec->nb_gen_max = $nb_gen_max;
	$invoice_rec->auto_validate = 0;

	$invoice_rec->fk_project = 0;
	$invoice_rec->ref_ext = 'uptosign-' . $customerid . '-' . $contractid;

	//tous les 2 du mois, le 1er tournera la tache planifiee qui actualisera les compteurs
	$date_next_execution = dol_mktime(0, 0, 0, date('m') + 1, 2, (int) date('Y'), false);
	$invoice_rec->date_when = $date_next_execution;
	// Facture (the invoice header) does not declare localtax1_tx / localtax2_tx -- these
	// live on the lines (FactureLigne). Read defensively to avoid Undefined property warnings.
	// Likewise FactureRec may not declare these properties on every Dolibarr version, so
	// only set them when the class has them as real or already-existing properties.
	$srcLocaltax1 = $invoice_draft->localtax1_tx ?? 0;
	$srcLocaltax2 = $invoice_draft->localtax2_tx ?? 0;
	if (property_exists($invoice_rec, 'localtax1_tx')) {
		$invoice_rec->localtax1_tx = get_localtax($srcLocaltax1, 1, $invoice_draft->thirdparty);
	}
	if (property_exists($invoice_rec, 'localtax2_tx')) {
		$invoice_rec->localtax2_tx = get_localtax($srcLocaltax2, 2, $invoice_draft->thirdparty);
	}

	// Get first contract linked to invoice used to generate template
	if ($invoice_draft->id > 0) {
		$srcObject = $invoice_draft;

		$srcObject->fetchObjectLinked();

		if (! empty($srcObject->linkedObjectsIds['contrat'])) {
			$contractidid = reset($srcObject->linkedObjectsIds['contrat']);

			$invoice_rec->origin = 'contrat';
			$invoice_rec->origin_id = $contractidid;
			$invoice_rec->linked_objects[$invoice_draft->origin] = $invoice_draft->origin_id;
		}
	}

	$invoicerecid = $invoice_rec->create($user, $factureid);
	if ($invoicerecid > 0) {
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facturedet_rec SET date_start_fill = 1, date_end_fill = 1 WHERE fk_facture = ' . ((int) $invoice_rec->id);
		$result = $db->query($sql);
		if (!$result) {
			$error++;
			dol_syslog("uptosign: uptosignCreateFactureRec failed to update facturedet_rec : " . $db->lasterror(), LOG_ERR);
			setEventMessages($db->lasterror(), [], 'errors');
		}

		//suppression de la facture brouillon
		$invoice_draft->delete($user);
	} else {
		$error++;
		setEventMessages($invoice_rec->error, $invoice_rec->errors, 'errors');
	}
	return $invoicerecid;
}


/**
 * creation automatique de la premiere facture dolibarr avec prorata temporis sur l'abonnement
 *
 * @param   int  $customerid  id of dolibarr customer
 *
 * @return  int               [return description]
 */
function uptosignCreateFirstFacture($customerid)
{
	global $db, $langs, $conf, $user, $mysoc;

	$error = 0;
	$array_options = [];

	$contract = new Contrat($db);
	$res = $contract->fetch('', '', 'uptosign-' . $customerid);

	$dateinvoice = dol_now();

	$invoice_draft = new Facture($db);
	$tmpproduct = new Product($db);

	// Create empty invoice
	$invoice_draft->socid				= $customerid;
	$invoice_draft->type				= Facture::TYPE_STANDARD;
	$invoice_draft->date				= $dateinvoice;

	$invoice_draft->note_private		= 'First invoice made by uptosign plugin';
	$invoice_draft->mode_reglement_id	= dol_getIdFromCode($db, 'CB', 'c_paiement', 'code', 'id', 1);
	$invoice_draft->cond_reglement_id	= dol_getIdFromCode($db, 'RECEP', 'c_payment_term', 'code', 'rowid', 1);

	$invoice_draft->fetch_thirdparty();

	$origin = 'contrat';
	$originid = $contract->id;

	$invoice_draft->origin = $origin;
	$invoice_draft->origin_id = $originid;

	// Possibility to add external linked objects with hooks
	$invoice_draft->linked_objects[$invoice_draft->origin] = $invoice_draft->origin_id;

	$idinvoice = $invoice_draft->create($user);      // This include class to add_object_linked() and add add_contact()
	if (! ($idinvoice > 0)) {
		setEventMessages($invoice_draft->error, $invoice_draft->errors, 'errors');
		$error++;
	}

	// Add lines on invoice
	if (! $error) {
		// Add lines of contract to template invoice
		$srcobject = $contract;

		$lines = $srcobject->lines;
		if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
			$srcobject->fetch_lines();
			$lines = $srcobject->lines;
		}

		$date_start = false;
		$fk_parent_line = 0;
		$num = count($lines);
		for ($i = 0; $i < $num; $i++) {
			$label = (! empty($lines[$i]->label) ? $lines[$i]->label : '');
			$desc = (! empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);
			if ($invoice_draft->situation_counter == 1) {
				$lines[$i]->situation_percent =  0;
			}

			// Positive line
			$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

			// Date start
			$date_start = false;
			if ($lines[$i]->date_start) {
				$date_start = $lines[$i]->date_start;
			}

			// Date end
			$date_end = false;
			if ($lines[$i]->date_end) {
				$date_end = $lines[$i]->date_end;
			}

			// If date start is in past, we set it to now
			$now = dol_now();

			// Reset fk_parent_line for no child products and special product
			if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
				$fk_parent_line = 0;
			}

			// Discount
			$discount = $lines[$i]->remise_percent;

			// Extrafields
			if ((utsbackports_getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED', '') == "") && method_exists($lines[$i], 'fetch_optionals')) {
				$lines[$i]->fetch_optionals($lines[$i]->rowid);
				$array_options = $lines[$i]->array_options;
			}

			$tva_tx = $lines[$i]->tva_tx;
			if (! empty($lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) {
				$tva_tx .= ' (' . $lines[$i]->vat_src_code . ')';
			}

			// View third's localtaxes for NOW and do not use value from origin.
			$localtax1_tx = get_localtax($tva_tx, 1, $invoice_draft->thirdparty);
			$localtax2_tx = get_localtax($tva_tx, 2, $invoice_draft->thirdparty);

			//$price_invoice_template_line = $lines[$i]->subprice * GETPOSTINT('frequency_multiple');
			$price_invoice_template_line = $lines[$i]->subprice;

			// Get data from product (frequency, discount type and val)
			$tmpproduct->fetch($lines[$i]->fk_product);

			dol_syslog("uptosign: Read frequency for product id=" . $tmpproduct->id, LOG_DEBUG, 0);


			// special case for abo -> make prorata temporis price on first invoice
			$price = $price_invoice_template_line;
			if ($lines[$i]->fk_product == utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_ABO', '')) {
				$date_start = $now;
				// $date_end = dol_get_last_day(date('Y'), date('m'));
				// Number of days between the two dates. date("d", diff) would read
				// the difference as an absolute timestamp (wrong day-of-month, wraps
				// past 31).
				$nbjours = round(($date_end - $date_start) / 86400);
				$price = $price_invoice_template_line * (($nbjours) / 30); //pour le prorata entre ajourd'hui et le 1er à venir
				$desc .= $langs->trans('uptosignProrataDaysAbo', $nbjours, price($price_invoice_template_line));
				$lines[$i]->qty = 1;
			} else {
				//les autres lignes forcément à zéro
				$lines[$i]->qty = 0;
			}

			// Insert the line
			$result = $invoice_draft->addline($desc, $price, $lines[$i]->qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $discount, $date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $invoice_draft->origin, $lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options, $lines[$i]->situation_percent, $lines[$i]->fk_prev_id, $lines[$i]->fk_unit);

			if ($result > 0) {
				$lineid = $result;
			} else {
				$lineid = 0;
				$error++;
				break;
			}

			// Defined the new fk_parent_line
			if ($result > 0 && $lines[$i]->product_type == 9) {
				$fk_parent_line = $result;
			}
		}
	}
	if ($error) {
		return -1 * $error;
	}
	return $idinvoice;
}

/**
 * auto create a dolibarr invoice
 *
 * @param   int $customerid       [$customerid description]
 * @param   bool $prorataTemporis  [$prorataTemporis description]
 * @param   bool  $validateInvoice  [$validateInvoice description]
 *
 * @return  int                   [return description]
 */
function uptosignCreateFacture($customerid, $prorataTemporis = false, $validateInvoice = false)
{
	global $db, $langs, $conf, $user, $mysoc;

	$error = 0;

	$contract = new Contrat($db);
	$res = $contract->fetch('', '', 'uptosign-' . $customerid);

	$dateinvoice = dol_now();
	$array_options = [];

	$invoice_draft = new Facture($db);
	$tmpproduct = new Product($db);

	// Create empty invoice
	$invoice_draft->socid				= $customerid;
	$invoice_draft->type				= Facture::TYPE_STANDARD;
	$invoice_draft->date				= $dateinvoice;

	$invoice_draft->note_private		= 'First invoice made by uptosign plugin';
	$invoice_draft->mode_reglement_id	= dol_getIdFromCode($db, 'CB', 'c_paiement', 'code', 'id', 1);
	$invoice_draft->cond_reglement_id	= dol_getIdFromCode($db, 'RECEP', 'c_payment_term', 'code', 'rowid', 1);

	$invoice_draft->fetch_thirdparty();

	$origin = 'contrat';
	$originid = $contract->id;

	$invoice_draft->origin = $origin;
	$invoice_draft->origin_id = $originid;

	// Possibility to add external linked objects with hooks
	$invoice_draft->linked_objects[$invoice_draft->origin] = $invoice_draft->origin_id;

	$idinvoice = $invoice_draft->create($user);      // This include class to add_object_linked() and add add_contact()
	if (! ($idinvoice > 0)) {
		setEventMessages($invoice_draft->error, $invoice_draft->errors, 'errors');
		$error++;
	}

	// Add lines on invoice
	if (! $error) {
		// Add lines of contract to template invoice
		$srcobject = $contract;

		$lines = $srcobject->lines;
		if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
			$srcobject->fetch_lines();
			$lines = $srcobject->lines;
		}

		$date_start = false;
		$fk_parent_line = 0;
		$num = count($lines);
		for ($i = 0; $i < $num; $i++) {
			$label = (! empty($lines[$i]->label) ? $lines[$i]->label : '');
			$desc = (! empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);
			if ($invoice_draft->situation_counter == 1) {
				$lines[$i]->situation_percent =  0;
			}

			// Positive line
			$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

			// Date start
			$date_start = false;
			if ($lines[$i]->date_start) {
				$date_start = $lines[$i]->date_start;
			}

			// Date end
			$date_end = false;
			if ($lines[$i]->date_end) {
				$date_end = $lines[$i]->date_end;
			}

			// If date start is in past, we set it to now
			$now = dol_now();

			// Reset fk_parent_line for no child products and special product
			if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
				$fk_parent_line = 0;
			}

			// Discount
			$discount = $lines[$i]->remise_percent;

			// Extrafields
			if ((utsbackports_getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED', '') == "") && method_exists($lines[$i], 'fetch_optionals')) {
				$lines[$i]->fetch_optionals($lines[$i]->rowid);
				$array_options = $lines[$i]->array_options;
			}

			$tva_tx = $lines[$i]->tva_tx;
			if (! empty($lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) {
				$tva_tx .= ' (' . $lines[$i]->vat_src_code . ')';
			}

			// View third's localtaxes for NOW and do not use value from origin.
			$localtax1_tx = get_localtax($tva_tx, 1, $invoice_draft->thirdparty);
			$localtax2_tx = get_localtax($tva_tx, 2, $invoice_draft->thirdparty);

			//$price_invoice_template_line = $lines[$i]->subprice * GETPOSTINT('frequency_multiple');
			$price_invoice_template_line = $lines[$i]->subprice;

			// Get data from product (frequency, discount type and val)
			$tmpproduct->fetch($lines[$i]->fk_product);

			dol_syslog("uptosign: Read frequency for product id=" . $tmpproduct->id, LOG_DEBUG, 0);


			// special case for abo -> make prorata temporis price on first invoice
			$price = $price_invoice_template_line;
			if ($lines[$i]->fk_product == utsbackports_getDolGlobalString('UPTOSIGN_RVD_AUTO_DEFAULT_ABO', '')) {
				if ($prorataTemporis) {
					$date_start = $now;
					// $date_end = dol_get_last_day(date('Y'), date('m'));
					// Number of days between the two dates. date("d", diff) would read
					// the difference as an absolute timestamp (wrong day-of-month, wraps
					// past 31).
					$nbjours = round(($date_end - $date_start) / 86400);
					$price = $price_invoice_template_line * (($nbjours) / 30); //pour le prorata entre ajourd'hui et le 1er à venir
					$desc .= $langs->trans('uptosignProrataDaysAbo', $nbjours, price($price_invoice_template_line));
				}
				$lines[$i]->qty = 1;
			} else {
				//les autres lignes forcément à zéro
				$lines[$i]->qty = 0;
			}

			// Insert the line
			$result = $invoice_draft->addline($desc, $price, $lines[$i]->qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $discount, $date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $invoice_draft->origin, $lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options, $lines[$i]->situation_percent, $lines[$i]->fk_prev_id, $lines[$i]->fk_unit);

			if ($result > 0) {
				$lineid = $result;
			} else {
				$lineid = 0;
				$error++;
				break;
			}

			// Defined the new fk_parent_line
			if ($result > 0 && $lines[$i]->product_type == 9) {
				$fk_parent_line = $result;
			}
		}

		if ($validateInvoice) {
			$invoice_draft->validate($user);
		}
	}
	if ($error) {
		return -1 * $error;
	}
	return $idinvoice;
}

/**
 * Detect positions from PDF magic keywords (STAMP, SIGN_XX, FROM_XX)
 *
 * @param  string $pdfFileFullPath Full path to the PDF file
 * @param  string $action          Current action (passed to keyword parser)
 * @param  array  $positionsSign   Sign positions array (modified by reference)
 * @param  array  $positionsSeal   Seal positions array (modified by reference)
 * @return array  ['autopositionSign' => bool, 'autopositionSeal' => bool]
 */
function uptosign_detect_pdf_positions($pdfFileFullPath, $action, &$positionsSign, &$positionsSeal)
{
	$autopositionSign = false;
	$autopositionSeal = false;

	$arr = [];
	if (uptosign_auto_position_magic_keywords($pdfFileFullPath, $arr, $action)) {
		if (isset($arr['STAMP'])) {
			foreach ($arr['STAMP'] as $page => $value) {
				$positionsSeal[$page]['STAMP'] = array(
					'defaultSealX' => (!empty($value['x']) ? $value['x'] : 0),
					'defaultSealY' => (!empty($value['y']) ? $value['y'] : 0),
					'defaultSealPage' => (!empty($value['p']) ? $value['p'] : 0)
				);
				$autopositionSeal = true;
			}
		}
		for ($idn = 0; $idn < 10; $idn++) {
			$tag = sprintf("SIGN_%'02d", $idn);
			if (isset($arr[$tag])) {
				foreach ($arr[$tag] as $page => $value) {
					dol_syslog("uptosign: auto position detect (sign debug) : $tag // $idn // page=$page :: " . $value['p']);
					$positionsSign[$page][$tag] = array(
						'defaultSignContactX' => (!empty($value['x']) ? $value['x'] : 0),
						'defaultSignContactY' => (!empty($value['y']) ? $value['y'] : 0),
						'defaultSignContactPage' => (!empty($value['p']) ? $value['p'] : 0)
					);
				}
				$autopositionSign = true;
			}
		}
		for ($idn = 0; $idn < 10; $idn++) {
			$tag = sprintf("FROM_%'02d", $idn);
			if (isset($arr[$tag])) {
				foreach ($arr[$tag] as $page => $value) {
					$positionsSign[$page][$tag] = array(
						'defaultSignUserX' => (!empty($value['x']) ? $value['x'] : 0),
						'defaultSignUserY' => (!empty($value['y']) ? $value['y'] : 0),
						'defaultSignUserPage' => (!empty($value['p']) ? $value['p'] : 0)
					);
				}
				$autopositionSign = true;
			}
		}

		dol_syslog('uptosign: auto position detect (sign) :' . json_encode($positionsSign));
		dol_syslog('uptosign: auto position detect (seal) :' . json_encode($positionsSeal));
	} else {
		dol_syslog('uptosign: auto position detect (keywords) fail');
	}

	return array('autopositionSign' => $autopositionSign, 'autopositionSeal' => $autopositionSeal);
}

/**
 * Fetch positions from UptoSignConfig database entries (fallback when auto-detection fails)
 *
 * @param  string          $modelPdf       PDF model name
 * @param  string          $modulepart     Module part (object type)
 * @param  string          $signOrSeal     "sign" or "seal"
 * @param  UptoSignConfig  $uptoSignConfig Config object instance
 * @param  bool            $autopositionSeal Whether seal position was already auto-detected
 * @param  bool            $autopositionSign Whether sign position was already auto-detected
 * @param  array           $positionsSign  Sign positions array (modified by reference)
 * @param  array           $positionsSeal  Seal positions array (modified by reference)
 * @return array  ['autopositionSign' => bool, 'autopositionSeal' => bool, 'noSign' => int]
 */
function uptosign_get_config_positions($modelPdf, $modulepart, $signOrSeal, $uptoSignConfig, $autopositionSeal, $autopositionSign, &$positionsSign, &$positionsSeal)
{
	$noSign = 0;
	$configIds = $uptoSignConfig->fetchListId($modelPdf, $modulepart, $signOrSeal);
	if ($configIds) {
		if (is_array($configIds)) {
			$uptoSignConfig->fetch($configIds[0]);

			if (!$autopositionSeal) {
				// seal_coordinate is expected to be "x,y". Guard against a malformed
				// or empty value so a missing second component does not raise a warning.
				$d = explode(',', (string) $uptoSignConfig->seal_coordinate);
				$positionsSeal[$uptoSignConfig->page_seal]['STAMP']['defaultSealX'] = isset($d[0]) ? $d[0] : 0;
				$positionsSeal[$uptoSignConfig->page_seal]['STAMP']['defaultSealY'] = isset($d[1]) ? $d[1] : 0;
				$positionsSeal[$uptoSignConfig->page_seal]['STAMP']['defaultSealPage'] = $uptoSignConfig->page_seal;
				$autopositionSeal = true;
			}
			if (!$autopositionSign) {
				if (!empty($uptoSignConfig->page_sign)) {
					$d = explode(',', (string) $uptoSignConfig->sign_coordinate);
					$positionsSign[$uptoSignConfig->page_sign]['SIGN_00']['defaultSignContactX'] = isset($d[0]) ? $d[0] : 0;
					$positionsSign[$uptoSignConfig->page_sign]['SIGN_00']['defaultSignContactY'] = isset($d[1]) ? $d[1] : 0;
					$positionsSign[$uptoSignConfig->page_sign]['SIGN_00']['defaultSignContactPage'] = $uptoSignConfig->page_sign;
					$autopositionSign = true;
				} else {
					$noSign = 1;
				}
			}
			dol_syslog('uptosign: position via profil de doc sign: ' . json_encode($positionsSign));
			dol_syslog('uptosign: position via profil de doc seal: ' . json_encode($positionsSeal));
		} else {
			dol_syslog("uptosign: Modèle de position des signatures introuvable", LOG_ERR);
		}
	}

	return array('autopositionSign' => $autopositionSign, 'autopositionSeal' => $autopositionSeal, 'noSign' => $noSign);
}

/**
 * Build seal JSON parameters array and print hidden form fields
 *
 * @param  array $positionsSeal Seal positions array
 * @return array JSON parameters array for seal positions
 */
function uptosign_build_seal_params($positionsSeal)
{
	$jsonparameters = array();
	if (count($positionsSeal) > 0) {
		$i = 0;
		foreach ($positionsSeal as $page => $position) {
			$jsonparameters[] = array(
				'paramId' => 'seal-' . $i,
				'description' => "SCEAU UPTOSIGN (obligatoire)<br />"
					. "Document scellé par uptosign<br />"
					. "Identifiant unique xxxxx<br />"
					. "https://uptosign.com/",
				'defaultX' => $position['STAMP']['defaultSealX'],
				'defaultY' => $position['STAMP']['defaultSealY'],
				'defaultPage' => $position['STAMP']['defaultSealPage']
			);
			$listOfFields = array('signX', 'signY', 'page');
			foreach ($listOfFields as $f) {
				$fieldName = 'seal-' . $i . '-' . $f;
				print '<input id="' . $fieldName . '" name="' . $fieldName . '" type="hidden" value="">' . "\n";
			}
			$i++;
		}
	} else {
		$i = 0;
		$jsonparameters[] = array(
			'paramId' => 'seal-' . $i,
			'description' => "SCEAU UPTOSIGN (obligatoire)<br />"
				. "Document scellé par uptosign<br />"
				. "Identifiant unique xxxxx<br />"
				. "https://uptosign.com/",
			'defaultX' => 0,
			'defaultY' => 0,
			'defaultPage' => 0
		);
		$listOfFields = array('signX', 'signY', 'page');
		foreach ($listOfFields as $f) {
			$fieldName = 'seal-' . $i . '-' . $f;
			print '<input id="' . $fieldName . '" name="' . $fieldName . '" type="hidden" value="">' . "\n";
		}
	}
	return $jsonparameters;
}

/**
 * Render PDF file selector dropdown and hidden base64 fields
 *
 * @param  string $uploadDir           Upload directory path
 * @param  string $pdfFileChoosed      Currently selected PDF filename
 * @param  string $pdfFileChoosedFullPath Full path (modified by reference)
 * @return array|null File info array of selected file, or null if no PDF found
 */
function uptosign_render_pdf_selector($uploadDir, $pdfFileChoosed, &$pdfFileChoosedFullPath)
{
	global $langs;

	/** @phpstan-ignore-next-line */
	$filearray = dol_dir_list($uploadDir, "files", 0, '\.pdf$', ['(\.meta|_preview.*\.png)$'], "name", SORT_ASC, 1);
	$fileInfo = null;
	if (is_array($filearray) && count($filearray) >= 1) {
		// Default to the first file; keep it as the value returned to the caller.
		$fileInfo = reset($filearray);
		$selectedFileInfo = $fileInfo;
		$pdfFileChoosedFullPath = dol_osencode(dol_sanitizePathName($fileInfo['fullname']));
		if (count($filearray) > 1) {
			print '<p>' . $langs->trans('UptoSignChooseFile') . '</p>' . "\n";
			print "<select name=\"pdfFileChoosed\" onchange=\"pdfFileChange();\" style=\"width:100%;max-width:90%;\">";
			foreach ($filearray as $oneFile) {
				$s = "";
				$optionValue = dol_osencode(dol_sanitizePathName($oneFile['name']));
				if ($pdfFileChoosed != "" && $optionValue == $pdfFileChoosed) {
					$pdfFileChoosedFullPath = dol_osencode($oneFile['fullname']);
					$selectedFileInfo = $oneFile;
					$s = "selected";
				}
				// Escape both the value and the label: a crafted file name would
				// otherwise break out of the attribute / inject markup (stored XSS).
				print "<option value=\"" . dol_escape_htmltag($optionValue) . "\" " . $s . ">" . dol_escape_htmltag($oneFile['name']) . "</option>";
			}
			print "<option value=\"\"></option>";
			print "</select>";
			// Return the file actually selected, not the last one iterated.
			$fileInfo = $selectedFileInfo;
		}
		print '	  <input type="hidden" id="pdfData" value="' . base64_encode(file_get_contents($pdfFileChoosedFullPath)) . '">' . "\n";	// Retrait de name="pdfData" pour ne pas POSTer le PDF entier (cause du 413 Request Entity Too Large) ; le champ reste lu côté client par le viewer PDF.js via son id, et le serveur relit le fichier sur disque
		print '	  <input type="hidden" id="pdfFileName" name="pdfFileName" value="' . base64_encode($pdfFileChoosedFullPath) . '">' . "\n";
	} else {
		print "<p style='color: #f00;font-weight: bold;'>" . $langs->trans('UptoSignNoPdfFilesAssociated') . "</p>";
		print "<p>" . $uploadDir . "</p>";
	}
	return $fileInfo;
}

/**
 * Render PDF page navigation buttons (prev/next with page counter)
 *
 * @return void
 */
function uptosign_render_page_nav()
{
	print '			<div style="display: flex; justify-content: space-between;" id="paramPages">
					<button style="display: flex; width: 48px;" id="prev">
						<svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
						</svg>
					</button>
					<div style="display: flex; flex-grow: 20;flex-direction: column;text-align: center;">
						<span>Page: <span id="page_num"></span> / <span id="page_count"></span></span>
					</div>
					<button style="display: flex; width: 48px;" id="next">
						<svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z" />
						</svg>
					</button>
				</div>' . "\n";
}

/**
 * try fo find file to sign/seal ... based on last_main_doc if exists
 *
 * @param   CommonObject  $obj  [$obj description]
 * @param   String  $last_main_doc file name if choosed one
 *
 * @return  String        [return description]
 */
function uptosignFindFileToUse(CommonObject $obj, $last_main_doc)
{
	global $conf;
	$dir = $filename = '';
	dol_syslog("uptosign: uptosignFindFileToUse last_main_doc=" . $last_main_doc);
	dol_syslog("uptosign: uptosignFindFileToUse obj=" . json_encode($obj));

	//$last_main_doc = 'commande/'.$objectref.'/'.$pdfFileChoosed;

	//priority is getpost user choice
	if ($last_main_doc != '') {
		$filename = $obj->element . '/' . $obj->ref . '/' . $last_main_doc;
	} elseif (GETPOSTISSET('selectFilename')) {
		$filename = dol_sanitizeFileName(GETPOST('selectFilename', "aZ09"));
		dol_syslog("uptosign: uptosignFindFileToUse GETPOSTISSET filename=" . $filename);
	} elseif ($obj->element == 'project' && !empty($last_main_doc)) {
		$filename = $last_main_doc;
		dol_syslog("uptosign: uptosignFindFileToUse element is project filename = " . $last_main_doc);
	} elseif (dol_is_file(DOL_DATA_ROOT . '/' . $obj->last_main_doc)) {
		$filename = $obj->last_main_doc;
	} else {
		$filename = dol_sanitizeFileName($obj->last_main_doc);
	}

	$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	if ($ext != "pdf") {
		dol_syslog("uptosign: uptosignFindFileToUse fix race condition, ext was=" . $ext);
		$filename .= '.pdf';
	}

	if (dol_is_file(DOL_DATA_ROOT . '/' . $filename)) {
		dol_syslog("uptosign: uptosignFindFileToUse dir=DOL_DATA_ROOT, filename=$filename, file found; return fullpath");
		return (DOL_DATA_ROOT . '/' . $filename);
	}

	// path depends on type of element ... but franglish is in action
	$elem = $obj->element ?? '';
	if (!empty($elem) && isset($conf->{$elem}) && is_object($conf->{$elem}) && isset($conf->{$elem}->dir_output)) {
		$dir = $conf->{$elem}->dir_output;
	}
	// special known cases
	if ($obj->element == 'shipping') {
		// special case for sign shipping
		$dir = $conf->expedition->dir_output . "/sending/";
	} elseif ($obj->element == 'contrat') {
		$dir = $conf->contrat->dir_output;
	} elseif ($obj->element == 'facture') {
		$dir = $conf->invoice->dir_output;
	} elseif ($obj->element == 'fichinter') {
		$dir = $conf->ficheinter->dir_output;
	} elseif ($obj->element == 'project') {
		$dir = $conf->projet->dir_output;
	}

	dol_syslog("uptosign: uptosignFindFileToUse dir=$dir, filename=$filename");
	if (dol_is_file($dir . '/' . $filename)) {
		dol_syslog("uptosign: uptosignFindFileToUse dir=$dir, filename=$filename, file found; return fullpath");
		return ($dir . '/' . $filename);
	}
	if (dol_is_file($dir . '/' . $obj->ref . '/' . $filename)) {
		dol_syslog("uptosign: uptosignFindFileToUse dir=$dir, filename=$filename, file found into obj->ref subdir, return fullpath");
		return ($dir . '/' . $obj->ref . '/' . $filename);
	}
	if (dol_is_file(DOL_DATA_ROOT . '/' . $obj->ref . '/' . $filename)) {
		dol_syslog("uptosign: uptosignFindFileToUse dir=$dir, filename=$filename, file found in other path=" . DOL_DATA_ROOT . '/' . $obj->ref, LOG_ERR);
		return (DOL_DATA_ROOT . '/' . $obj->ref . '/' . $filename);
	}
	dol_syslog("uptosign: uptosignFindFileToUse dir=$dir, filename=$filename, file not found return=''", LOG_ERR);
	return '';
}

/**
 * get list of files linked to object and who can be signed
 *
 * @param   CommonObject  $obj  [$obj description]
 *
 * @return  array              [return description]
 */
function uptosignListOfFilesLinkedTo(CommonObject $obj)
{
	// dol_syslog("ecm :: " . json_encode($filearray));
	require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
	$ecmfile = new EcmFiles($obj->db);
	// Detect real fetchAll() signature: in Dolibarr <=19 the $filter parameter is typed `array`,
	// in Dolibarr >=20 it became a USF string. Some installs have mixed state where DOL_VERSION
	// says 20+ but the file still has `array $filter`. Reflect on the actual class to pick the right form.
	$useUsfFilter = false;
	try {
		$refMethod = new ReflectionMethod('EcmFiles', 'fetchAll');
		$params = $refMethod->getParameters();
		if (isset($params[4])) {
			$type = $params[4]->getType();
			$useUsfFilter = ($type === null || (string) $type !== 'array');
		}
	} catch (ReflectionException $e) {
		dol_syslog("uptosign: uptosignListOfFilesLinkedTo reflection failed: " . $e->getMessage(), LOG_WARNING);
		$useUsfFilter = version_compare(DOL_VERSION, "20.0.0") >= 0;
	}
	if ($useUsfFilter) {
		$filter	= "(t.src_object_type:=:'".$obj->db->escape($obj->table_element)."') AND (t.src_object_id:=:".((int) $obj->id).")";
	} else {
		$filter	= array(
			't.src_object_type' => $obj->table_element,
			't.src_object_id'   => (int) $obj->id,
		);
	}
	$result = $ecmfile->fetchAll('', '', 0, 0, $filter);
	$filearray = array();
	if (is_array($ecmfile->lines) && count($ecmfile->lines) > 0) {
		foreach ($ecmfile->lines as $key => $fileEntry) {
			// print json_encode($fileEntry) . "<br />";
			if (preg_match("/\.pdf$/", $fileEntry->filename)) {
				$filearray[$fileEntry->filename] = $fileEntry->filename;
			}
		}
	}
	dol_syslog("uptosign: uptosignListOfFilesLinkedTo list is = " . json_encode($filearray));
	return $filearray;
}


/**
 * some dolibarr object have a const STATUS_SIGNED defined ...
 *
 * @param   CommonObject  $object  $object description
 *
 * @return  int|null           [return description]
 */
function uptosignCheckStatusSigned($object)
{
	$classname = get_class($object);
	if (defined("$classname::STATUS_SIGNED")) {
		return ($object->status == $object::STATUS_SIGNED);
	} else {
		return null;
	}
}


/**
 * create invoice from proposal
 *
 * @param   $object  dolibarr propal object
 *
 * @return  null|Facture           dolibarr invoice
 */
function uptosign_create_invoice_from_proposal($object)
{
	global $conf, $hookmanager, $db, $user;

	dol_include_once('/multicurrency/class/multicurrency.class.php');
	dol_include_once('/core/class/extrafields.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');

	$error = 0;
	$newinvoice = new Facture($db);

	$newinvoice->date = dol_now();
	// $newinvoice->source = 0;

	$num = count($object->lines);
	for ($i = 0; $i < $num; $i++) {
		$line = new FactureLigne($newinvoice->db);

		$line->libelle = $object->lines[$i]->libelle; // deprecated
		$line->label			= $object->lines[$i]->label;
		$line->desc				= $object->lines[$i]->desc;
		$line->subprice			= $object->lines[$i]->subprice;
		$line->total_ht			= $object->lines[$i]->total_ht;
		$line->total_tva		= $object->lines[$i]->total_tva;
		$line->total_localtax1	= $object->lines[$i]->total_localtax1;
		$line->total_localtax2	= $object->lines[$i]->total_localtax2;
		$line->total_ttc		= $object->lines[$i]->total_ttc;
		$line->vat_src_code = $object->lines[$i]->vat_src_code;
		$line->tva_tx = $object->lines[$i]->tva_tx;
		$line->localtax1_tx		= $object->lines[$i]->localtax1_tx;
		$line->localtax2_tx		= $object->lines[$i]->localtax2_tx;
		$line->qty = $object->lines[$i]->qty;
		$line->fk_remise_except = $object->lines[$i]->fk_remise_except;
		$line->remise_percent = $object->lines[$i]->remise_percent;
		$line->fk_product = $object->lines[$i]->fk_product;
		$line->info_bits = $object->lines[$i]->info_bits;
		$line->product_type		= $object->lines[$i]->product_type;
		$line->rang = $object->lines[$i]->rang;
		$line->special_code		= $object->lines[$i]->special_code;
		$line->fk_parent_line = $object->lines[$i]->fk_parent_line;
		$line->fk_unit = $object->lines[$i]->fk_unit;
		$line->date_start = $object->lines[$i]->date_start;
		$line->date_end = $object->lines[$i]->date_end;

		// Multicurrency
		$line->fk_multicurrency = $object->lines[$i]->fk_multicurrency;
		$line->multicurrency_code = $object->lines[$i]->multicurrency_code;
		$line->multicurrency_subprice = $object->lines[$i]->multicurrency_subprice;
		$line->multicurrency_total_ht = $object->lines[$i]->multicurrency_total_ht;
		$line->multicurrency_total_tva = $object->lines[$i]->multicurrency_total_tva;
		$line->multicurrency_total_ttc = $object->lines[$i]->multicurrency_total_ttc;

		$line->fk_fournprice = $object->lines[$i]->fk_fournprice;
		$marginInfos			= getMarginInfos($object->lines[$i]->subprice, $object->lines[$i]->remise_percent, $object->lines[$i]->tva_tx, $object->lines[$i]->localtax1_tx, $object->lines[$i]->localtax2_tx, $object->lines[$i]->fk_fournprice, $object->lines[$i]->pa_ht);
		$line->pa_ht			= $marginInfos[0];

		// get extrafields from original line
		$object->lines[$i]->fetch_optionals();
		foreach ($object->lines[$i]->array_options as $options_key => $value) {
			$line->array_options[$options_key] = $value;
		}

		$newinvoice->lines[$i] = $line;
	}

	$newinvoice->socid                = $object->socid;
	$newinvoice->fk_project           = $object->fk_project;
	$newinvoice->fk_account = $object->fk_account;
	$newinvoice->cond_reglement_id    = $object->cond_reglement_id;
	$newinvoice->mode_reglement_id    = $object->mode_reglement_id;
	// $newinvoice->availability_id      = $object->availability_id;
	$newinvoice->demand_reason_id     = $object->demand_reason_id;
	$newinvoice->delivery_date        = (empty($object->delivery_date) ? $object->date_livraison : $object->delivery_date);
	$newinvoice->date_livraison       = $object->delivery_date; // deprecated
	$newinvoice->fk_delivery_address  = $object->fk_delivery_address; // deprecated
	$newinvoice->contact_id           = $object->contact_id;
	$newinvoice->ref_client           = $object->ref_client;

	if (empty($conf->global->MAIN_DISABLE_PROPAGATE_NOTES_FROM_ORIGIN)) {
		$newinvoice->note_private = $object->note_private;
		$newinvoice->note_public = $object->note_public;
	}

	$newinvoice->module_source = $object->module_source;
	$newinvoice->pos_source = $object->pos_source;

	$newinvoice->origin = $object->element;
	$newinvoice->origin_id = $object->id;

	$newinvoice->fk_user_author = $user->id;

	// get extrafields from original line
	$object->fetch_optionals();
	foreach ($object->array_options as $options_key => $value) {
		$newinvoice->array_options[$options_key] = $value;
	}

	// Possibility to add external linked objects with hooks
	$newinvoice->linked_objects[$newinvoice->origin] = $newinvoice->origin_id;
	if (!empty($object->other_linked_objects) && is_array($object->other_linked_objects)) {
		$newinvoice->linked_objects = array_merge($newinvoice->linked_objects, $object->other_linked_objects);
	}

	$ret = $newinvoice->create($user);
	if ($ret > 0) {
		return $newinvoice;
	}
	return null;
}





/**
 * send mail with invoice ($object) attached
 *
 * @param   string  $modele  	mail model to use
 * @param   CommonObject  $object  	invoice
 * @param   string  $actionCode	actionComm code to use
 * @param   int  $forceMail	send mail even if actioncomm exists for that code
 *
 * @return  [type]           [return description]
 */
function uptosignSendInvoiceMailModele($modele, $object, $actionCode = "", $forceMail = 0)
{
	global $db, $conf, $langs, $user, $mysoc;
	dol_syslog("uptosign: uptosignSendInvoiceMailModele modele=$modele, actionCode=$actionCode, forceMail=$forceMail", LOG_DEBUG);
	$result = 0;
	$subject = $msg = "";

	if ($forceMail == 0) {
		// Factorisation du test si le mail n' pas déjà été envoyée (plus dur) en cas de réouverture / validation multiple
		//Si un mail d'information n'a pas été envoyé et que l'option est active il faut l'envoyer
		//pour savoir si le mail d'information n'a pas été envoyé -> recherche dans les actioncomm ?
		$actioncomm = new ActionComm($db);
		if (floatval(DOL_VERSION) < 15) {
			$resAC = $actioncomm->getActions($db, $object->socid, $object->id, "invoice", " AND code='AC_" . $actionCode . "'");
		} else {
			$resAC = $actioncomm->getActions($object->socid, $object->id, "invoice", " AND code='AC_" . $actionCode . "'");
		}
		if (!empty($resAC)) {
			dol_syslog("uptosign: uptosignSendInvoiceMailModele modele=$modele already sent", LOG_DEBUG);
			return $result;
		}
	}

	// Set output language
	$outputlangs = new Translate('', $conf);
	$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
	$outputlangs->loadLangs(array("main", "members", "bills"));

	$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');

	//destinataire -> contact facturation de la société et à défaut adresse mail de la société
	$facturationID = $object->getIdBillingContact();
	$to = '';
	if (!empty($facturationID)) {
		dol_syslog("uptosign: uptosignSendInvoiceMailModele résultat de  getIdBillingContact : " . json_encode($facturationID), LOG_DEBUG);
		foreach ($facturationID as $cfid) {
			$contactFacturation = new Contact($db);
			$contactresult = $contactFacturation->fetch($cfid);
			if ($contactresult) {
				if ($contactFacturation->email != '') {
					if ($to != '') {
						$to  .= ", ";
					}
					$to .= $contactFacturation->email;
				}
				dol_syslog("uptosign: uptosignSendInvoiceMailModele utilisation du contact facturation, destinataire (id = $cfid) email = $to", LOG_DEBUG);
			}
		}
	}
	if (empty($object->thirdparty)) {
		$societe = new Societe($db);
		$socresult = $societe->fetch($object->socid);
		if ($socresult) {
			$object->thirdparty = $societe;
		}
	}
	if (empty($to)) {
		$to = $object->thirdparty->email;
		dol_syslog("uptosign: uptosignSendInvoiceMailModele utilisation de l'adresse mail societe, destinataire = $to", LOG_DEBUG);
	}

	if (empty(trim($from)) || empty(trim($to))) {
		// print json_encode($object);
		dol_syslog("uptosign: uptosignSendInvoiceMailModele early return, from=$from or to=$to is empty", LOG_DEBUG);
		return;
	}

	// Get email content from templae
	$arraydefaultmessage = null;

	$formmail = new FormMail($db);

	if (!empty($modele)) {
		$arraydefaultmessage = $formmail->getEMailTemplate($db, 'facture_send', $user, $outputlangs, -2, 1, $modele);
		//getEMailTemplate($dbs, $type_template, $user, $outputlangs, $id = 0, $active = 1, $label = '', $defaultfortype = -1)
		// $arraydefaultmessage = $formmail->getEMailTemplate($db, 'facture_send', $user, $outputlangs, $model_id);
	}
	// print "<p>Recherche des mails type pour modele=$modele</p>";
	// print json_encode($arraydefaultmessage);
	// exit;

	if (!empty($modele) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
		$subject = $arraydefaultmessage->topic;
		$msg     = $arraydefaultmessage->content;
	} else {
		dol_syslog("uptosign: uptosignSendInvoiceMailModele empty modele or arraydefaultmessagee error", LOG_DEBUG);
	}

	$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);

	// $substitutionarray['__SELLYOURSAAS_PAYMENT_ERROR_DESC__']=$stripefailurecode.' '.$stripefailuremessage;

	complete_substitutions_array($substitutionarray, $outputlangs, $object);

	$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
	$texttosend = make_substitutions($msg, $substitutionarray, $outputlangs);

	dol_syslog('uptosign: uptosignSendInvoiceMailModele DIRECTDOWNLOAD_URL_INVOICE=' . $substitutionarray['__DIRECTDOWNLOAD_URL_INVOICE__']);
	dol_syslog('uptosign: uptosignSendInvoiceMailModele SUBJECT=' . $subjecttosend);
	// dol_syslog('uptosign: uptosignSendInvoiceMailModele MESSAGE='.$texttosend);

	// Fichier joint
	$file = '';
	$listofpaths = array();
	$listofnames = array();
	$listofmimes = array();
	if (is_object($object)) {
		$objectdiroutput = $conf->facture->dir_output;
		$fileparams = dol_most_recent_file($objectdiroutput . '/' . $object->ref, preg_quote($object->ref, '/') . '.*.pdf');

		$file = $fileparams['fullname'];

		if ($file) {
			$listofpaths = array($file);
			$listofnames = array(basename($file));
			$listofmimes = array(dol_mimetype($file));
		}
	}
	dol_syslog('uptosign: uptosignSendInvoiceMailModele fichier(s) joint(s) : ' . json_encode($listofpaths));

	$trackid = 'inv' . $object->id;
	$moreinheader = 'X-Dolibarr-Info: uptosignSendInvoiceMailModele' . "\r\n";
	$addr_cc = '';
	if (!empty($object->thirdparty->array_options['options_emailccinvoice'])) {
		$addr_cc = $object->thirdparty->array_options['options_emailccinvoice'];
	}
	$addr_bcc = getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO');

	// Send email (substitutionarray must be done just before this)
	$mailfile = new CMailFile($subjecttosend, $to, $from, $texttosend, $listofpaths, $listofmimes, $listofnames, $addr_cc, $addr_bcc, 0, -1, '', '', $trackid, $moreinheader);
	if ($mailfile->sendfile()) {
		$result = 1;
	} else {
		$error = $langs->trans("ErrorFailedToSendMail", $from, $to) . '. ' . $mailfile->error;
		dol_syslog('uptosign: uptosignSendInvoiceMailModele Error : ' . $mailfile->error, LOG_ERR);
		$result = -1;
	}

	if ($result < 0) {
		$errmsg = $error;
		$postactionmessages[] = $errmsg;
		$ispostactionok = -1;
	} else {
		if ($file) {
			$postactionmessages[] = 'Email sent to thirdparty (to ' . $to . ' with invoice document attached: ' . $file . ', language = ' . $outputlangs->defaultlang . ')';
		} else {
			$postactionmessages[] = 'Email sent to thirdparty (to ' . $to . ' without any attached document, language = ' . $outputlangs->defaultlang . ')';
		}

		uptosignAddActionComm($object, $actionCode, $subjecttosend, $texttosend, $postactionmessages, '');
	}


	dol_syslog("uptosign: uptosignSendInvoiceMailModele ends, return $result", LOG_DEBUG);
	return $result;
}



function uptosignAddActionComm($object, $actioncode, $label, $description, $postactionmessages, $extraparams, $date = null)
{
	global $db, $user;
	dol_syslog("uptosign: uptosignAddActionComm Record event for payment result - " . $description);
	$now = (!empty($date)) ? $date : dol_now();
	// Insert record of payment (success or error)
	$actioncomm = new ActionComm($db);

	$actioncomm->type_code    = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
	$actioncomm->code         = 'AC_' . $actioncode;
	$actioncomm->label        = $label;
	$actioncomm->note_private = implode(",\n", $postactionmessages);
	$actioncomm->fk_project   = $object->fk_project;
	$actioncomm->datep        = $now;
	$actioncomm->datef        = $now;
	$actioncomm->percentage   = -1;   // Not applicable
	$actioncomm->socid        = $object->socid;
	$actioncomm->contactid    = 0;
	$actioncomm->authorid     = $user->id;   // User saving action
	$actioncomm->userownerid  = $user->id;	// Owner of action
	$actioncomm->note_private = $description;
	// Fields when action is a real email (content is already into note)
	/*$actioncomm->email_msgid = $object->email_msgid;
	 $actioncomm->email_from  = $object->email_from;
	 $actioncomm->email_sender= $object->email_sender;
	 $actioncomm->email_to    = $object->email_to;
	 $actioncomm->email_tocc  = $object->email_tocc;
	 $actioncomm->email_tobcc = $object->email_tobcc;
	 $actioncomm->email_subject = $object->email_subject;
	 $actioncomm->errors_to   = $object->errors_to;*/
	$actioncomm->fk_element   = $object->id;
	$actioncomm->elementtype  = $object->element;
	$actioncomm->extraparams  = dol_trunc($extraparams, 250);
	$actioncomm->create($user);
}

/**
 * get list of dolibarr element where digitalsign extrafield exists
 *
 * @return  [type]  [return description]
 */
function uptosign_list_of_elements_with_extrafield()
{
	return ['propal', 'commande', 'contrat', 'projet', 'supplier_proposal'];
}


/**
 * handle all type of dolibarr objects in one place
 *
 * @param   String  $objectType  dolibarr object type
 * @param   Int  	$id          id of object, fetched if not null
 *
 * @return  array               		array['object','modulepart','head','noSeal','signOrSeal','pdfpath'];

 */
function uptosign_handle_all_type_of_objects($objectType, $id = null)
{
	global $db, $conf;

	$object = $modulepart = $head = $functionHead = $noSeal = $signOrSeal = $pdfpath = null;

	if ($objectType == 'propal') {
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
		$object = new Propal($db);
		$modulepart = "propal";
		$functionHead = 'propal_prepare_head';
		$pdfpath = $conf->propal->multidir_output[$conf->entity];
	} elseif ($objectType == 'contrat' || $objectType == 'contract') {
		require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
		$object = new Contrat($db);
		$modulepart = "contract";
		$functionHead = 'contract_prepare_head';
		$pdfpath = $conf->contract->multidir_output[$conf->entity];
	} elseif ($objectType == 'commande' || $objectType == 'order') {
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
		$object = new Commande($db);
		$modulepart = "commande";
		$functionHead = 'commande_prepare_head';
		$pdfpath = $conf->commande->multidir_output[$conf->entity];
	} elseif ($objectType == 'invoice' || $objectType == 'facture') {
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
		$object = new Facture($db);
		$modulepart = "facture";
		$functionHead = 'facture_prepare_head';
		$pdfpath = $conf->facture->multidir_output[$conf->entity];
	} elseif ($objectType == 'delivery') {
		if (((int) DOL_VERSION) > 12) {
			require_once DOL_DOCUMENT_ROOT . '/delivery/class/delivery.class.php';
			/** @phpstan-ignore-next-line */
			$object = new Delivery($db);
			$modulepart = "delivery";
			$pdfpath = $conf->expedition->dir_output . "/receipt";
		} else {
			dol_syslog("uptosign: uptoSignGetSpecimen delivery object is for Dolibarr 13.0", LOG_WARNING);
		}
	} elseif ($objectType == 'ficheinter'|| $objectType == 'intervention') {
		require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
		$object = new Fichinter($db);
		$modulepart = "fichinter";
		$pdfpath = $conf->ficheinter->dir_output;
	} elseif ($objectType == 'invoice_supplier') {
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
		$object = new FactureFournisseur($db);
		$modulepart = "supplier_invoice";
		$pdfpath = $conf->fournisseur->facture->dir_output;
	} elseif ($objectType == 'societe') {
		$object = new Societe($db);
		$modulepart = "societe";
		$pdfpath = $conf->societe->multidir_output[$conf->entity];
	} elseif ($objectType == 'project' || $objectType == 'projet') {
		require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
		$object = new Project($db);
		$modulepart = "project";
		$functionHead = 'project_prepare_head';
		$pdfpath = $conf->project->multidir_output[$conf->entity];
	} elseif ($objectType == 'supplier_order') {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
		$object = new CommandeFournisseur($db);
		$modulepart = "supplier_order";
		$functionHead = 'ordersupplier_prepare_head';
	} elseif ($objectType == 'supplier_proposal') {
		require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/supplier_proposal.lib.php';
		$object = new SupplierProposal($db);
		$modulepart = "supplier_proposal";
		$functionHead = 'supplier_proposal_prepare_head';
		// The module may be disabled on that instance: no output dir then
		$pdfpath = $conf->supplier_proposal->multidir_output[$conf->entity] ?? '';
	} elseif ($objectType == 'user') {
		require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
		$object = new User($db);
		$modulepart = "user";
		$functionHead = 'user_prepare_head';
	} elseif ($objectType == 'uptosignlist') {
		dol_include_once('/uptosign/class/uptosignlist.class.php');
		dol_include_once('/uptosign/lib/uptosign_uptosignlist.lib.php');
		$object = new UptoSignList($db);
		$functionHead = 'uptosignlistPrepareHead';
		$modulepart = "uptosignlist";
		$noSeal = 1;
		$signOrSeal = 'sign';
	} elseif ($objectType == 'bankaccount') {
		//TODO verif
		require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
		$object = new Account($db);
		$modulepart = "bank";
		$pdfpath = $conf->bank->dir_output;
	} elseif ($objectType == 'sepamandate') {
		//TODO verif
		require_once DOL_DOCUMENT_ROOT . '/societe/class/companypaymentmode.class.php';
		$object = new CompanyPaymentMode($db);
		$modulepart = "bank";
		$pdfpath = $conf->bank->dir_output;
	} elseif ($objectType == 'order_supplier') {
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
		$object = new CommandeFournisseur($db);
		$modulepart = "supplier_order";
		$pdfpath = $conf->fournisseur->commande->dir_output;
	} elseif ($objectType == 'infrassalariescontracts') {
		dol_include_once('/infrassalariescontracts/class/infrassalariescontracts.class.php');
		dol_include_once('/infrassalariescontracts/core/lib/infrassalariescontracts.lib.php');
		/** @phpstan-ignore-next-line */
		$object = new InfraSSalariesContracts($db);
		$modulepart = "infrassalariescontracts";
		/** @phpstan-ignore-next-line */
		$functionHead = 'infrassalariescontracts_prepare_head';
	}

	if ($id && $object) {
		$res = $object->fetch($id);
		if ($res) {
			// race condition for users
			if ( $objectType == 'user') {
				$object->getRights();
			}
			// tabs
			if (!empty($functionHead)) {
				$head = $functionHead($object);
			}
		}
	}

	return [
		'object' => $object,
		'modulepart' => $modulepart,
		'head' => $head,
		'noSeal' => $noSeal,
		'signOrSeal' => $signOrSeal,
		'pdfpath' => $pdfpath
	];
}
