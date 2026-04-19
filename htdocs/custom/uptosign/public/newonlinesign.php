<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2023		anthony Berton			<anthony.berton@bb2a.fr>
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


/**
 *     	\file       htdocs/public/onlinesign/newonlinesign.php
 *		\ingroup    core
 *		\brief      File to offer a way to make an online signature for a particular Dolibarr entity
 *					Example of URL: https://localhost/public/onlinesign/newonlinesign.php?ref=PR...
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
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
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

dol_include_once('/uptosign/lib/backports.lib.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');

if (!isset($mysoc)) {
	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	$mysoc = new Societe($db);
	$mysoc->setMysoc($conf);
}
$uptoSign = new UptoSign($db);

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

// Get parameters
$action = (string) GETPOST('action', 'aZ09');
$cancel = (string) GETPOST('cancel', 'alpha');
$confirm = (string) GETPOST('confirm', 'alpha');


$refusepropal = (string) GETPOST('refusepropal', 'alpha');
$message = (string) GETPOST('message', 'aZ09');

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

$suffix = (string) GETPOST("suffix", 'aZ09');
$source = (string) GETPOST("source", 'alpha');
$pdfFileChoosed = (string) GETPOST("pdfFileChoosed", 'alpha');
$ref = $REF = (string) GETPOST("ref", 'alpha');
$urlok = '';
$urlko = '';

if (empty($source)) {
	$source = 'proposal';
}

if (!$action) {
	if ($source && !$ref) {
		print $langs->trans('ErrorBadParameters') . " - ref missing";
		exit;
	}
}
if (!empty($refusepropal)) {
	$action = "refusepropal";
}

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.


// Complete urls for post treatment
$SECUREKEY = GETPOST("securekey", "alpha"); // Secure key

if (!empty($source)) {
	$urlok .= 'source=' . urlencode($source) . '&';
	$urlko .= 'source=' . urlencode($source) . '&';
}
if (!empty($REF)) {
	$urlok .= 'ref=' . urlencode($REF) . '&';
	$urlko .= 'ref=' . urlencode($REF) . '&';
}
if (!empty($SECUREKEY)) {
	$urlok .= 'securekey=' . urlencode($SECUREKEY) . '&';
	$urlko .= 'securekey=' . urlencode($SECUREKEY) . '&';
}
if (!empty($entity)) {
	$urlok .= 'entity=' . urlencode((string) $entity) . '&';
	$urlko .= 'entity=' . urlencode((string) $entity) . '&';
}
$urlok = preg_replace('/&$/', '', $urlok); // Remove last &
$urlko = preg_replace('/&$/', '', $urlko); // Remove last &

$creditor = $mysoc->name;

$type = $source;
if (!$action) {
	if ($source && !$ref) {
		if (((int) DOL_VERSION) < 17) {
			accessforbidden($langs->trans('ErrorBadParameters') . " - ref missing");
		} else {
			/** @phpstan-ignore-next-line */
			httponly_accessforbidden($langs->trans('ErrorBadParameters') . " - ref missing", 400, 1);
		}
	}
}

$securekeyseed = '';
$typefix = $type;
if ($source == 'proposal') {
	require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
	$object = new Propal($db);
	if (((int) DOL_VERSION) < 15) {
		$result = $object->fetch(0, $ref);
	} else {
		//multi entity support
		$result = $object->fetch(0, $ref, '', $entity);
	}
	$securekeyseed = utsbackports_getDolGlobalString('PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN', '');
	$typefix = "proposal";
} elseif ($source == 'commande') {
	require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
	$object = new Commande($db);
	$result = $object->fetch(0, $ref);
	$securekeyseed = getDolGlobalString('COMMANDE_ONLINE_SIGNATURE_SECURITY_TOKEN');
	$typefix = "commande";
} elseif ($source == 'contract') {
	require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
	$object = new Contrat($db);
	$result = $object->fetch(0, $ref);
	$securekeyseed = utsbackports_getDolGlobalString('CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN', '');
	$typefix = "contract";
} elseif ($source == 'fichinter') {
	require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
	$object = new Fichinter($db);
	$result = $object->fetch(0, $ref);
	$securekeyseed = utsbackports_getDolGlobalString('FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN', '');
	$typefix = "fichinter";
} elseif ($source == 'project') {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	$object = new Project($db);
	$result = $object->fetch(0, $ref);
	$securekeyseed = utsbackports_getDolGlobalString('PROJECT_ONLINE_SIGNATURE_SECURITY_TOKEN', '');
	$typefix = "project";
} elseif ($source == 'societe_rib') {
	require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
	$object = new CompanyBankAccount($db);
	$result = $object->fetch(0, $ref);
	$securekeyseed = getDolGlobalString('SOCIETE_RIB_ONLINE_SIGNATURE_SECURITY_TOKEN');
	$typefix = "societe_rib";
} elseif ($source == 'expedition') {
	require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
	$object = new Expedition($db);
	$result = $object->fetch(0, $ref);
	$securekeyseed = getDolGlobalString('EXPEDITION_ONLINE_SIGNATURE_SECURITY_TOKEN');
	$typefix = "expedition";
} else {
	accessforbidden('Bad value for source');
	exit;
}

$defaultLang = empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang;
if ($defaultLang == 'auto') {
	$defaultLang = 'fr_FR';
}
dol_syslog("uptosign set default lang to $defaultLang, source is $source ref is $ref then object fetched is #" . $object->id . " type=" . $object->element);
$langs->setDefaultLang($defaultLang);
// Load translation files
$langs->loadLangs(array("uptosign@uptosign", "main", "other", "dict", "bills", "companies", "errors", "members", "propal", "commercial"));

//hmmmm fix pour ideal mais a verifier(en particulier par rapport à la race cond. du else...)
//note: voir dans le init du module: création auto. des clés de sécu pour les anciennes versions de dolibarr
if (((int) DOL_VERSION) < 15) {
	dol_syslog("uptosign dolibarr < 15, no securekey was enabled for that version of dolibarr");
	// if (!utsbackports_dol_verifyHash($securekeyseed.$typefix.$ref, $SECUREKEY, '0')) {
	// 	http_response_code(403);
	// 	print 'Bad value for securitykey (case 1). Value provided ('.dol_escape_htmltag($SECUREKEY).') does not match expected value for ref='.dol_escape_htmltag($ref) . ',seed='.$securekeyseed .',type='.$type;
	// 	exit(-1);
	// }
} else {
	if (!utsbackports_dol_verifyHash($securekeyseed . $typefix . $ref . (empty($conf->multicompany->enabled) ? '' : $entity), $SECUREKEY, '0')) {
		if (utsbackports_dol_verifyHash($securekeyseed . $typefix . $ref . (empty($conf->multicompany->enabled) ? '1' : $entity), $SECUREKEY, '0')) {
			//race condition dolibarr 15.0.3
		} else {
			http_response_code(403);
			dol_syslog('uptosign : Bad value for securitykey (case 2). Value provided ' . dol_escape_htmltag($SECUREKEY) . ' does not match expected value for ref=' . dol_escape_htmltag($ref) . ",typefix=$typefix,securekeyseed=$securekeyseed,source=$source...");
			print('dolibarr uptosign module, Bad value for securitykey (case 2)');
			exit(-1);
		}
	}
}

//TODO: Ajouter les verifications pour etre sur que tout est ok du genre signataire lié, présence num de tel etc.
//et le cas échéant ajouter un message d'information sympa

$error = 0;


/*
 * Actions
 */
$contactToSignID = '';

dol_syslog("uptosign : action=$action et cancel=$cancel ...");
if ($action == "dosign" && empty($cancel)) {
	$localError = 0;

	//choix d'un contact ?
	$contactToSignID = GETPOST('contactToSignID') ?? '';

	//creation d'un nouveau contact au vol ? prioritaire si données saisies
	if (utsbackports_getDolGlobalString('UPTOSIGN_CREATE_SIGN_CONTACT_ONLINE', '') != "") {
		$newContact = GETPOST('newContact');
		$contact = null;
		if ($newContact) {
			//do not create duplicate contact, keyword is email ?
			$newEmail = GETPOST('newEmail');

			$societe = new Societe($db);
			$societe->fetch($object->socid);
			$contactIds = $societe->getIdContact('external', 'CustomerSign');
			$found = false;
			foreach ($contactIds as $contactId) {
				$contact = new Contact($object->db);
				if ($result = $contact->fetch($contactId) > 0) {
					if ($contact->email == $newEmail) {
						dol_syslog("uptosign dosign, create new contact: an existant contact is found : " . json_encode($contact));
						$found = true;
					}
					$newMobile = GETPOST('newMobile');
					if (empty($contact->phone_mobile)) {
						dol_syslog("uptosign dosign, existant contact does not have phone number, update contact with it");
						$contact->phone_mobile = $newMobile;
						$contact->update($user, 1);
						$found = true;
					} elseif ($contact->phone_mobile != $newMobile) {
						dol_syslog("uptosign dosign, existant contact does not have that phone number, update contact with the new one");
						$ladate = date("d-m-Y");
						$contact->note_private .= "\n$ladate, old mobile phone was : " . $contact->phone_mobile;
						$contact->phone_mobile = $newMobile;
						$contact->update($user, 1);
						$found = true;
					}
				}
			}
			//il faut le creer s'il n'existe pas
			if (!$found) {
				$contact = new Contact($db);
				$contact->lastname = GETPOST('newLastName');
				$contact->firstname = GETPOST('newFirstName');
				$contact->phone_mobile = GETPOST('newMobile');
				$contact->email = $newEmail;
				$contact->socid = $object->socid;

				$user = $uptoSign->findUserToUse($user, $object);

				$ret1 = $contact->create($user);
				if ($ret1 > 0) {
					dol_syslog("uptosign : creation d'un contact au vol ok " . $ret1 . " json = " . json_encode($contact));
					$utsmessage = "Un nouveau signataire a été ajouté pour le document " . $object->ref . " nom=" . $contact->lastname . ", email=" . $contact->email;
					uptosign_send_mail(utsbackports_getDolGlobalString("UPTOSIGN_SEND_ERROR_MAIL_TO"), "Nouveau signataire", $utsmessage);
					$found = true;
				} else {
					dol_syslog("uptosign : erreur de creation du contact " . $newEmail);
					$utsmessage = "Le nouveau signataire n'a pas pu etre ajoute " . json_encode($contact);
					uptosign_send_mail(utsbackports_getDolGlobalString("UPTOSIGN_SEND_ERROR_MAIL_TO"), "Erreur de creation du signataire", $utsmessage);
					$localError++;
				}
			}

			//et si sa creation a marche ou qu'il existait deja il faut l'associer
			if ($found) {
				$contactToSignID = $contact->id;
				//il faut l'associer "signature électronique" sur ce document...
				$ret2 = $object->add_contact($contactToSignID, "CustomerSign", 'external', 1);
				if ($ret2  > 0) {
					dol_syslog("uptosign : association du contact au document avec le role de signature electronique ok " . json_encode($ret2));
				} else {
					dol_syslog("uptosign : erreur d'association du contact au document avec le role de signature electronique " . json_encode($ret2));
					$error++;
				}
			}
		}
	}

	if ($error) {
		setEventMessages($langs->trans("UptoSignOnlineSignFormErrorGenericMessage"), [], 'errors');
	} else {
		if (in_array($object->element, uptosign_list_of_elements_with_extrafield())) {
			//fix #27
			if (is_array($object->array_options) && empty($object->array_options['options_digitalsign']) && $conf->global->UPTOSIGN_FORCE_AS_DEFAULT_SIGN_SYSTEM_CLONE) {
				$object->array_options['options_digitalsign'] = 'uptosign';
				$res = $object->updateExtraField('digitalsign');
				if ($res > 0) {
					dol_syslog("uptosign newonlinesign update digitalsign empty to uptosign", LOG_DEBUG);
				} else {
					dol_syslog("uptosign newonlinesign can't update digitalsign ! res value is $res", LOG_INFO);
				}
			}
		}
		if (is_array($object->array_options) && !empty($object->array_options['options_digitalsign'])) {
			$signprovider = $object->array_options['options_digitalsign'];
			if ($signprovider == 'uptosign') {
				// print "creation uptosign & rebond";
				$last_main_doc = (string) GETPOST("last_main_doc", 'alpha');
				dol_syslog("uptosign : action dosign, start hookmanager with last_main_doc=$last_main_doc...");
				$hookmanager->initHooks(array('uptosignnewonlinesign'));     // Note that conf->hooks_modules contains array
				$parameters = [
					'currentcontext' => 'uptosignnewonlinesign',
					'process' => 'now',
					'contactToSignID' => $contactToSignID,
					'last_main_doc' => $last_main_doc
				];
				$action = "confirm_uptosign";
				$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					dol_syslog("uptosign: hook error is " . $hookmanager->error, LOG_ERR);
					if (utsbackports_getDolGlobalString("UPTOSIGN_SEND_ERROR_MAIL_TO")) {
						$utsmessage  = $hookmanager->error . "<br />\n";
						$utsmessage .= "errors = <pre>" . json_encode($hookmanager->errors) . "</pre><br>\n";
						$utsmessage .= "action = $action<br>\n";
						$utsmessage .= "parameters = <pre>" . json_encode($parameters) . "</pre><br>\n";
						$utsmessage .= "object = <pre>" . json_encode($object) . "</pre><br>\n";
						uptosign_send_mail(utsbackports_getDolGlobalString("UPTOSIGN_SEND_ERROR_MAIL_TO"), "New Online Sign Error", $utsmessage);
					}
					setEventMessages($langs->trans("UptoSignOnlineSignFormErrorGenericMessage"), [], 'errors');
				} else {
					$action = "uptosign_started";
				}
			}
		}
	}
}


if ($action == 'confirm_refusepropal' && $confirm == 'yes') {
	$db->begin();

	$sql  = "UPDATE " . MAIN_DB_PREFIX . "propal";
	$sql .= " SET fk_statut = " . ((int) $object::STATUS_NOTSIGNED) . ", note_private = '" . $db->escape($object->note_private) . "', date_signature='" . $db->idate(dol_now()) . "'";
	$sql .= " WHERE rowid = " . ((int) $object->id);

	dol_syslog(__METHOD__, LOG_DEBUG);
	$resql = $db->query($sql);
	if (!$resql) {
		$error++;
	}

	if (!$error) {
		$db->commit();

		$message = 'refused';
		setEventMessages("PropalRefused", [], 'warnings');
		if (method_exists($object, 'call_trigger')) {
			// Online customer is not a user, so we use the use that validates the documents
			$user = $uptoSign->findUserToUse($user, $object);

			$object->context = array('closedfromonlinesignature' => 'closedfromonlinesignature');
			$result = $object->call_trigger('PROPAL_CLOSE_REFUSED', $user);
			if ($result < 0) {
				$error++;
			}
		}
	} else {
		$db->rollback();
	}

	$object->fetch(0, $ref);
}


/*
 * View
 */

$formfile = new FormFile($db);
$form = new Form($db);
$head = '';
if (utsbackports_getDolGlobalString('MAIN_SIGN_CSS_URL', '')  != '') {
	$head = '<link rel="stylesheet" type="text/css" href="' . $conf->global->MAIN_SIGN_CSS_URL . '?lang=' . $langs->defaultlang . '">' . "\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

// Show logo (search order: logo defined by ONLINE_SIGN_LOGO_suffix, then ONLINE_SIGN_LOGO_, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_SIGN_LOGO_' . $suffix;
if (utsbackports_getDolGlobalString($paramlogo, '')  != '') {
	$logosmall = $conf->global->$paramlogo;
} elseif (utsbackports_getDolGlobalString('ONLINE_SIGN_LOGO', '')  != '') {
	$logosmall = utsbackports_getDolGlobalString('ONLINE_SIGN_LOGO', '');
}
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $logosmall)) {
	$urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/thumbs/' . $logosmall);
	$urllogofull = $dolibarr_main_url_root . '/viewimage.php?modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode('logos/thumbs/' . $logosmall);
} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $logo)) {
	$urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/' . $logo);
	$urllogofull = $dolibarr_main_url_root . '/viewimage.php?modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode('logos/' . $logo);
}


$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '') . '<div>';
llxHeader($head, $langs->trans("UptoSignOnlineSignature"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1);

if ($action == 'refusepropal') {
	print $form->formconfirm($_SERVER["PHP_SELF"] . '?ref=' . urlencode($ref) . '&securekey=' . urlencode($SECUREKEY) . ($conf->multicompany->enabled ? '&entity=' . $entity : ''), $langs->trans('RefusePropal'), $langs->trans('ConfirmRefusePropal', $object->ref), 'confirm_refusepropal', '', '', 1);
}

// Check link validity for param 'source' to avoid use of the examples as value
if (!empty($source) && in_array($ref, array('member_ref', 'contractline_ref', 'invoice_ref', 'order_ref', 'proposal_ref', ''))) {
	$langs->load("errors");
	if (utsbackports_getDolGlobalString("UPTOSIGN_SEND_ERROR_MAIL_TO")) {
		$utsmessage = $langs->trans("ErrorBadLinkSourceSetButBadValueForRef", $source, $ref);
		uptosign_send_mail(utsbackports_getDolGlobalString("UPTOSIGN_SEND_ERROR_MAIL_TO"), "New Oline Sign Error", $utsmessage);
	}
	//dol_print_error_email('BADREFINONLINESIGNFORM', $langs->trans("ErrorBadLinkSourceSetButBadValueForRef", $source, $ref));
	print $langs->trans("UptoSignOnlineSignFormErrorGenericMessage");
	// End of page
	llxFooter();
	$db->close();
	exit;
}

if ($action == "uptosign_started") {
	if ($urllogo) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		print '<img id="dolpaymentlogo" src="' . $urllogo . '"';
		print '>';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">' . $langs->trans("PoweredBy") . '<br><img class="poweredbyimg" src="' . DOL_URL_ROOT . '/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}
	print '<div class="center">' . "\n";
	print "<p>Votre demande a bien été enregistrée, notre partenaire uptosign va vous envoyer un lien de signature électronique par courrier électronique dans quelques instants.<br />Si vous ne le recevez pas vérifiez dans vos indésirables.</p>";

	if (((int) DOL_VERSION) < 18) {
		/** @phpstan-ignore-next-line */
		htmlPrintOnlinePaymentFooter($mysoc, $langs);
	} else {
		/** @phpstan-ignore-next-line */
		htmlPrintOnlineFooter($mysoc, $langs);
	}
	print "</div>";
	llxFooter('', 'public');
	$db->close();
	exit;
}

print '<span id="dolpaymentspan"></span>' . "\n";
print '<div class="center">' . "\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="' . $_SERVER["PHP_SELF"] . '" method="POST" data-submit-once>' . "\n";
print '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";
print '<input type="hidden" name="action" value="dosign">' . "\n";
print '<input type="hidden" name="tag" value="' . (string) GETPOST("tag", 'alpha') . '">' . "\n";
print '<input type="hidden" name="suffix" value="' . (string) GETPOST("suffix", 'alpha') . '">' . "\n";
print '<input type="hidden" name="securekey" value="' . dol_escape_htmltag($SECUREKEY) . '">' . "\n";
print '<input type="hidden" name="entity" value="' . $entity . '" />';
print '<input type="hidden" name="page_y" value="" />';
print '<input type="hidden" name="last_main_doc" value="' . $pdfFileChoosed . '" />';
print "\n";
print '<!-- Form to sign -->' . "\n";

// Output html code for logo
if ($urllogo) {
	print '<div class="backgreypublicpayment">';
	print '<div class="logopublicpayment">';
	print '<img id="dolpaymentlogo" src="' . $urllogo . '"';
	print '>';
	print '</div>';
	if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">' . $langs->trans("PoweredBy") . '<br><img class="poweredbyimg" src="' . DOL_URL_ROOT . '/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';
}
if ($source == 'proposal' && (utsbackports_getDolGlobalString('PROPOSAL_IMAGE_PUBLIC_SIGN', '') != '')) {
	print '<div class="backimagepublicproposalsign">';
	print '<img id="idPROPOSAL_IMAGE_PUBLIC_INTERFACE" src="' . $conf->global->PROPOSAL_IMAGE_PUBLIC_SIGN . '">';
	print '</div>';
}

print '<table style="max-width:800px;" id="dolpublictable" summary="Payment form" class="center">' . "\n";
// Output introduction text
$text = '';
if (utsbackports_getDolGlobalString('ONLINE_SIGN_NEWFORM_TEXT', '')  != '') {
	$reg = array();
	if (preg_match('/^\((.*)\)$/', $conf->global->ONLINE_SIGN_NEWFORM_TEXT, $reg)) {
		$text .= $langs->trans($reg[1]) . "<br>\n";
	} else {
		$text .= $conf->global->ONLINE_SIGN_NEWFORM_TEXT . "<br>\n";
	}
	$text = '<tr><td align="center"><br>' . $text . '<br></td></tr>' . "\n";
}
if (empty($text)) {
	if ($source == 'proposal') {
		$text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageProposal", $mysoc->name) . '</strong></td></tr>' . "\n";
		$text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromProposal", $creditor) . '<br><br></td></tr>' . "\n";
	} elseif ($source == 'contract' || $source == 'contrat') {
		$text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageContract", $mysoc->name) . '</strong></td></tr>' . "\n";
		$text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromContract", $creditor) . '<br><br></td></tr>' . "\n";
	} elseif ($source == 'fichinter') {
		$text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageFichinter", $mysoc->name) . '</strong></td></tr>' . "\n";
		$text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromFichinter", $creditor) . '<br><br></td></tr>' . "\n";
	} elseif ($source == 'commande') {
		$text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageCommande", $mysoc->name) . '</strong></td></tr>' . "\n";
		$text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromCommande", $creditor) . '<br><br></td></tr>' . "\n";
	} elseif ($source == 'project') {
		$text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageProject", $mysoc->name) . '</strong></td></tr>' . "\n";
		$text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromProject", $creditor) . '<br><br></td></tr>' . "\n";
	}
}
print $text;

// Output payment summary form
print '<tr><td align="center">';
print '<table style="margin-bottom: 0;" with="100%" id="tablepublicpayment">';
if ($source == 'proposal') {
	print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignProposal") . ' :</td></tr>' . "\n";
} elseif ($source == 'contract' || $source == 'contrat') {
	print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignContract") . ' :</td></tr>' . "\n";
} elseif ($source == 'fichinter') {
	print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignFichinter") . ' :</td></tr>' . "\n";
} elseif ($source == 'commande') {
	print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignCommande") . ' :</td></tr>' . "\n";
} elseif ($source == 'project') {
	print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignProject") . ' :</td></tr>' . "\n";
}

$found = false;
$error = 0;
$mesg = "";

// Signature on commercial proposal
if ($source == 'proposal') {
	$found = true;
	$langs->load("proposal");

	// require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

	// $proposal = new Propal($db);
	// $result = $proposal->fetch('', $ref);
	// if ($result <= 0) {
	//     $mesg = $proposal->error;
	//     $error++;
	// } else {
	//     $result = $proposal->fetch_thirdparty($proposal->socid);
	// }

	$result = $object->fetch_thirdparty($object->socid);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Creditor");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $creditor . '</b>';
	print '<input type="hidden" name="creditor" value="' . $creditor . '">';
	print '</td></tr>' . "\n";

	// Debitor

	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $object->thirdparty->name . '</b>';
	print '</td></tr>' . "\n";

	// Amount

	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Amount");
	print '</td><td class="CTableRow2">';
	print '<b>' . price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . '</b>';
	print '</td></tr>' . "\n";

	// Object

	$text = '<b>' . $langs->trans("UptoSignSignatureProposalRef", $object->ref) . '</b>';
	print '<tr class="CTableRow2">';
	print '<td class="CTableRow2"></td>';
	print '<td class="CTableRow2">' . $text;
	$last_main_doc_file = $object->last_main_doc;
	if ($object->status == $object::STATUS_VALIDATED) {
		if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
			// It seems document has never been generated, or was generated and then deleted.
			// So we try to regenerate it with its default template.
			$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
			$object->generateDocument($defaulttemplate, $langs);
		}

		$directdownloadlink = $object->getLastMainDocLink('proposal');
		// print "<p>****************** $last_main_doc_file *********************</p>";exit;
		// print "<p>****************** $directdownloadlink *********************</p>";exit;

		// eric pour preview intégrée plutôt que download
		if ($directdownloadlink) {
			print "<p><a style='margin:0' class='butAction pictopreview documentpreview' mime='" . dol_mimetype($object->last_main_doc) . "' target='_blank' href='" . $directdownloadlink . "&attachment=0'>";
			print $langs->trans("UptoSignViewDocument") . "&nbsp;&nbsp;&nbsp;";
			print img_mime($object->last_main_doc, dol_mimetype($object->last_main_doc), '');
			print "</a>";
			print '</p>';
		}

		//print '<i class="fa fa-file-pdf-o paddingright" title="Mime type: pdf"></i>T&eacute;l&eacute;charger le document</a><a class="pictopreview documentpreview" href="' . $directdownloadlink . '&attachment=1" mime="application/pdf"  target="_blank"><span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span></a><input type="hidden" name="source" value="proposal"><input type="hidden" name="ref" value="PR2309-0073">';
	} else {
		if ($object->status == $object::STATUS_NOTSIGNED) {
			$directdownloadlink = $object->getLastMainDocLink('proposal');
			// eric pour preview intégrée plutôt que download
			if ($directdownloadlink) {
				print "<p><a style='margin:0' class='butAction pictopreview documentpreview' mime='" . dol_mimetype($object->last_main_doc) . "' target='_blank' href='" . $directdownloadlink . "&attachment=0'>";
				print $langs->trans("UptoSignViewDocument") . "&nbsp;&nbsp;&nbsp;";
				print img_mime($object->last_main_doc, dol_mimetype($object->last_main_doc), '');
				print "</a>";
				print '</p>';
			} elseif (uptosignCheckStatusSigned($object) || $object->status == $object::STATUS_BILLED) {
				if (preg_match('/_signed-(\d+)/', $last_main_doc_file)) {	// If the last main doc has been signed
					$last_main_doc_file_not_signed = preg_replace('/_signed-(\d+)/', '', $last_main_doc_file);

					$datefilesigned = dol_filemtime($last_main_doc_file);
					$datefilenotsigned = dol_filemtime($last_main_doc_file_not_signed);

					if (empty($datefilenotsigned) || $datefilesigned > $datefilenotsigned) {
						$directdownloadlink = $object->getLastMainDocLink('proposal');
						// eric pour preview intégrée plutôt que download
						if ($directdownloadlink) {
							print "<p><a style='margin:0' class='butAction pictopreview documentpreview' mime='" . dol_mimetype($object->last_main_doc) . "' target='_blank' href='" . $directdownloadlink . "&attachment=0'>";
							print $langs->trans("UptoSignViewDocument") . "&nbsp;&nbsp;&nbsp;";
							print img_mime($object->last_main_doc, dol_mimetype($object->last_main_doc), '');
							print "</a>";
							print '</p>';
						}
					}
				}
			}
		}
	}

	print '<input type="hidden" name="source" value="' . (string) GETPOST("source", 'alpha') . '">';
	print '<input type="hidden" name="ref" value="' . $object->ref . '">';
	print '</td></tr>' . "\n";
} elseif ($source == 'contract' || $source == 'contrat') { // Signature on contract
	$found = true;
	$langs->load("contract");

	$result = $object->fetch_thirdparty($object->socid);

	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $creditor . '</b>';
	print '<input type="hidden" name="creditor" value="' . $creditor . '">';
	print '</td></tr>' . "\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $object->thirdparty->name . '</b>';
	print '</td></tr>' . "\n";

	// Object
	$text = '<b>' . $langs->trans("SignatureContractRef", $object->ref) . '</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
	print '</td><td class="CTableRow2">' . $text;

	$last_main_doc_file = $object->last_main_doc;

	if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
		$object->generateDocument($defaulttemplate, $langs);
	}

	$directdownloadlink = $object->getLastMainDocLink('contract');

	// eric pour preview intégrée plutôt que download
	if ($directdownloadlink) {
		print "<p><a style='margin:0' class='butAction pictopreview documentpreview' mime='" . dol_mimetype($object->last_main_doc) . "' target='_blank' href='" . $directdownloadlink . "&attachment=0'>";
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument") . "&nbsp;&nbsp;&nbsp;";
		} else {
			print $langs->trans("UptoSignViewDocument") . "&nbsp;&nbsp;&nbsp;";
		}
		print img_mime($object->last_main_doc, dol_mimetype($object->last_main_doc), '');
		print "</a>";
		print '</p>';
	}

	print '<input type="hidden" name="source" value="' . (string) GETPOST("source", 'alpha') . '">';
	print '<input type="hidden" name="ref" value="' . $object->ref . '">';
	print '</td></tr>' . "\n";
} elseif ($source == 'fichinter') { // Signature on fichinter
	$found = true;
	$langs->load("fichinter");

	$result = $object->fetch_thirdparty($object->socid);
	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $creditor . '</b>';
	print '<input type="hidden" name="creditor" value="' . $creditor . '">';
	print '</td></tr>' . "\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $object->thirdparty->name . '</b>';
	print '</td></tr>' . "\n";

	// Object
	$text = '<b>' . $langs->trans("SignatureFichinterRef", $object->ref) . '</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
	print '</td><td class="CTableRow2">' . $text;

	$last_main_doc_file = $object->last_main_doc;

	if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
		$object->generateDocument($defaulttemplate, $langs);
	}

	$directdownloadlink = $object->getLastMainDocLink('fichinter');
	// eric pour preview intégrée plutôt que download
	if ($directdownloadlink) {
		print "<p><a style='margin:0' class='butAction pictopreview documentpreview' mime='" . dol_mimetype($object->last_main_doc) . "' target='_blank' href='" . $directdownloadlink . "&attachment=0'>";
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument") . "&nbsp;&nbsp;&nbsp;";
		} else {
			print $langs->trans("UptoSignViewDocument") . "&nbsp;&nbsp;&nbsp;";
		}
		print img_mime($object->last_main_doc, dol_mimetype($object->last_main_doc), '');
		print "</a>";
		print '</p>';
	}

	print '<input type="hidden" name="source" value="' . (string) GETPOST("source", 'alpha') . '">';
	print '<input type="hidden" name="ref" value="' . $object->ref . '">';
	print '</td></tr>' . "\n";
} elseif ($source == 'commande') { // Signature on commande(order)
	$found = true;
	$langs->load("commande");

	$result = $object->fetch_thirdparty($object->socid);
	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $creditor . '</b>';
	print '<input type="hidden" name="creditor" value="' . $creditor . '">';
	print '</td></tr>' . "\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $object->thirdparty->name . '</b>';
	print '</td></tr>' . "\n";

	// Object
	$text = '<b>' . $langs->trans("SignatureCommandeRef", $object->ref) . '</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
	print '</td><td class="CTableRow2">' . $text;

	$last_main_doc_file = $object->last_main_doc;

	if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
		$object->generateDocument($defaulttemplate, $langs);
	}

	$objectref	= dol_sanitizeFileName($object->ref);
	$last_main_doc = 'commande/' . $objectref . '/' . $pdfFileChoosed;
	// $directdownloadlink = $object->getLastMainDocLink('commande');
	$directdownloadlink = utsbackports_getLastMainDocLink($last_main_doc, 'commande', 1);
	// eric pour preview intégrée plutôt que download
	if ($directdownloadlink) {
		print "<p><a style='margin:0' class='butAction pictopreview documentpreview' mime='" . dol_mimetype($object->last_main_doc) . "' target='_blank' href='" . $directdownloadlink . "&attachment=0'>";
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument") . "&nbsp;&nbsp;&nbsp;";
		} else {
			print $langs->trans("UptoSignViewDocument") . "&nbsp;&nbsp;&nbsp;";
		}
		print img_mime($object->last_main_doc, dol_mimetype($object->last_main_doc), '');
		print "</a>";
		print '</p>';
	}

	print '<input type="hidden" name="source" value="' . (string) GETPOST("source", 'alpha') . '">';
	print '<input type="hidden" name="ref" value="' . $object->ref . '">';
	print '</td></tr>' . "\n";
} elseif ($source == 'project') { // Signature on project
	$found = true;
	$langs->load("projects");

	$result = $object->fetch_thirdparty($object->socid);
	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $creditor . '</b>';
	print '<input type="hidden" name="creditor" value="' . $creditor . '">';
	print '</td></tr>' . "\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $object->thirdparty->name . '</b>';
	print '</td></tr>' . "\n";

	// Object
	$text = '<b>' . $langs->trans("UptoSignSignatureProjectRef", $pdfFileChoosed) . '</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
	print '</td><td class="CTableRow2">' . $text;

	$objectref	= dol_sanitizeFileName($object->ref);
	$last_main_doc = 'projet/' . $objectref . '/' . $pdfFileChoosed;
	$directdownloadlink = utsbackports_getLastMainDocLink($last_main_doc, 'project', 1);
	// eric pour preview intégrée plutôt que download
	if ($directdownloadlink) {
		print "<p><a style='margin:0' class='butAction pictopreview documentpreview' mime='" . dol_mimetype($last_main_doc) . "' target='_blank' href='" . $directdownloadlink . "&attachment=0'>";
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument") . "&nbsp;&nbsp;&nbsp;";
		} else {
			print $langs->trans("UptoSignViewDocument") . "&nbsp;&nbsp;&nbsp;";
		}
		print img_mime($last_main_doc, dol_mimetype($last_main_doc), '');
		print "</a>";
		print '</p>';
	}

	print '<input type="hidden" name="source" value="' . (string) GETPOST("source", 'alpha') . '">';
	print '<input type="hidden" name="ref" value="' . $object->ref . '">';
	print '<input type="hidden" name="last_main_doc" value="' . $last_main_doc . '">';
	print '</td></tr>' . "\n";
}

if (!$found && !$mesg) {
	dol_syslog("uptosign newonlinesign bad parameters");
	$mesg = $langs->transnoentitiesnoconv("ErrorBadParameters");
}

if ($mesg) {
	print '<tr><td class="center" colspan="2"><br><div class="warning">' . dol_escape_htmltag($mesg) . '</div></td></tr>' . "\n";
}

print '</table>' . "\n";
print "\n";

if ($action != 'dosign') {
	dol_syslog("uptosign action is $action, found=$found, error=$error");
	if ($found && !$error) {
		// We are in a management option and no error
		dol_syslog("uptosign We are in a management option and no error");
	} else {
		dol_syslog("uptosign error is ERRORNEWONLINESIGN");
		dol_print_error_email('ERRORNEWONLINESIGN');
	}
} else {
	// Print
}

print '</td></tr>' . "\n";
print '<tr><td class="left">';

if ($action != "dosign" && $message != 'signed' && uptosignCheckStatusSigned($object) != true) {
	$contacts = new ArrayObject();
	// $uptoSign->whoCanSign($object, 'internal', "CustomerSign", $contacts);
	$uptoSign->whoCanSign($object, 'external', "CustomerSign", $contacts);

	if (empty($contacts) || ($contacts->count() == 0)) {
		print "<p> " . $langs->trans('UpToSignNoContact') . "</p>";
	} else {
		print "<p>" . $langs->trans('UpToSignChooseContact');
		print "<select name='contactToSignID'>";
		foreach ($contacts as $contact) {
			print "<option value='" . $contact->id . "'>" . $uptoSign->getShortContactNameMailTel($contact) . "</option>";
		}
		print "</select>";
		print "</p>";
	}
	//print json_encode($w);

	if ($conf->global->UPTOSIGN_CREATE_SIGN_CONTACT_ONLINE) {
		$createMessage = "";
		if ($contacts->count() == 0) {
			print "<p>" . $langs->trans('UpToSignYouCanCreateFirstContact') . "</p>";
			$createMessage = "UpToSignYouCanCreateFirstContactCheckbox";
		} else {
			print "<p>" . $langs->trans('UpToSignYouCanCreateOneContact') . "</p>";
			$createMessage = "UpToSignYouCanCreateOneContactCheckbox";
		}
		print "<p><label><input type='checkbox' name='newContact' id='newContact' onclick='toggleNewContact();'>" . $langs->trans($createMessage) . "</label></p>";
		print "<div style='display: none' id='newContactDiv'>";
		print "<p>" . $langs->trans('Firstname') . ": <input type='text' name='newFirstName'> " . $langs->trans('Lastname') . ": <input type='text' name='newLastName'> </p>";
		print "<p>" . $langs->trans('Email') . ": <input type='email' name='newEmail'> " . $langs->trans('Mobile') . ": <input type='tel' name='newMobile' placeholder='+33612345678' pattern='\+[0-9]*'> </p>";
		print "<div>";
		print '	<script type="text/javascript">
		function toggleNewContact() {
			$("#newContactDiv").toggle("slow", function() {
			  if($("#newContact").is(":checked")) {
				$("input").prop("required",true);
			  } else {
				$("input").prop("required",false);
			  }
			});
		}</script>';
	} else {
		if ($contacts->count() == 0) {
			print "<p> " . $langs->transnoentities('UpToSignContactSignIsEmpty', "<a href='mailto:" . $mysoc->email . "'>") . "</p>";
		} else {
			print "<p> " . $langs->transnoentities('UpToSignContactSignIsWrong', "<a href='mailto:" . $mysoc->email . "'>") . "</p>";
		}
	}
}


print '</td></tr>' . "\n";
print '<tr><td class="center">';

if ($action == "dosign" && empty($cancel)) {
	print '<div style="margin-bottom: 0;" class="tablepublicpayment">';
	print '<input type="button" class="buttonDelete small" id="clearsignature" value="' . $langs->trans("ClearSignature") . '">';
	print '<input type="text" class="paddingleftonly marginleftonly paddingrightonly marginrightonly" id="name"  placeholder="' . $langs->trans("Lastname") . '">';
	print '<div id="signature" style="border:solid;"></div>';
	print '</div>';
	// Do not use class="reposition" here: It breaks the submit and there is a message on top to say it's ok, so going back top is better.
	print '<input type="button" class="button" id="signbutton" value="' . $langs->trans("Sign") . '">';
	print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';

	// Add js code managed into the div #signature
	print '<script language="JavaScript" type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jSignature/jSignature.js"></script>
	<script type="text/javascript">
	function toggleNewContact() {
		$( "#newContactDiv" ).toggle( "slow", function() {
		  // Animation complete.
		});
	}

	$(document).ready(function() {
	  $("#signature").jSignature({ color:"#000", lineWidth:0, ' . (empty($conf->dol_optimize_smallscreen) ? '' : 'width: 280, ') . 'height: 180});

	  $("#signature").on("change",function(){
		$("#clearsignature").css("display","");
		$("#signbutton").attr("disabled",false);
		if(!$._data($("#signbutton")[0], "events")){
			$("#signbutton").on("click",function(){
				console.log("We click on button sign");
				$("#signbutton").val(\'' . dol_escape_js($langs->transnoentities('PleaseBePatient')) . '\');
				var signature = $("#signature").jSignature("getData", "image");
				var name = document.getElementById("name").value;
				$.ajax({
					type: "POST",
					url: "' . DOL_URL_ROOT . '/core/ajax/onlineSign.php",
					dataType: "text",
					data: {
						"action" : "importSignature",
						"token" : \'' . newToken() . '\',
						"signaturebase64" : signature,
						"onlinesignname" : name,
						"ref" : \'' . dol_escape_js($REF) . '\',
						"securekey" : \'' . dol_escape_js($SECUREKEY) . '\',
						"mode" : \'' . dol_escape_htmltag($source) . '\',
						"entity" : \'' . dol_escape_htmltag((string) $entity) . '\',
					},
					success: function(response) {
						if(response == "success"){
							console.log("Success on saving signature");
							window.location.replace("' . $_SERVER["PHP_SELF"] . '?ref=' . urlencode($ref) . '&source=' . urlencode($source) . '&message=signed&securekey=' . urlencode($SECUREKEY) . (isModEnabled('multicompany') ? '&entity=' . $entity : '') . '");
						}else{
							console.error(response);
						}
					},
				});
			});
		}
	  });

	  $("#clearsignature").on("click",function(){
		$("#signature").jSignature("clear");
		$("#signbutton").attr("disabled",true);
		// document.getElementById("onlinesignname").value = "";
	  });

	  $("#signbutton").attr("disabled",true);
	});
	</script>';
} else {
	if ($source == 'proposal') {
		if (uptosignCheckStatusSigned($object)) {
			print '<br>';
			if ($message == 'signed') {
				print img_picto('', 'check', '', 0, 0, 0, '', 'size2x') . '<br>';
				print '<span class="ok">' . $langs->trans("PropalSigned") . '</span>';
			} else {
				print img_picto('', 'check', '', 0, 0, 0, '', 'size2x') . '<br>';
				print '<span class="ok">' . $langs->trans("PropalAlreadySigned") . '</span>';
			}
		} elseif ($object->status == $object::STATUS_NOTSIGNED) {
			print '<br>';
			if ($message == 'refused') {
				print img_picto('', 'cross', '', 0, 0, 0, '', 'size2x') . '<br>';
				print '<span class="ok">' . $langs->trans("PropalRefused") . '</span>';
			} else {
				print img_picto('', 'cross', '', 0, 0, 0, '', 'size2x') . '<br>';
				print '<span class="warning">' . $langs->trans("PropalAlreadyRefused") . '</span>';
			}
		} else {
			print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("AcceptDocument") . '">';
			print '<input name="refusepropal" type="submit" class="butActionDelete small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("RefuseDocument") . '">';
		}
	} elseif ($source == 'contract' || $source == 'contrat') {
		if ($message == 'signed') {
			print '<span class="ok">' . $langs->trans("ContractSigned") . '</span>';
		} else {
			print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("SignContract") . '">';
		}
	} elseif ($source == 'fichinter') {
		if ($message == 'signed') {
			print '<span class="ok">' . $langs->trans("FichinterSigned") . '</span>';
		} else {
			print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("SignFichinter") . '">';
		}
	} elseif ($source == 'commande') {
		if ($message == 'signed') {
			print '<span class="ok">' . $langs->trans("CommandeSigned") . '</span>';
		} else {
			print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("AcceptDocument") . '">';
		}
	} elseif ($source == 'project') {
		if ($message == 'signed') {
			print '<span class="ok">' . $langs->trans("DocumentAlreadySigned") . '</span>';
		} else {
			print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("AcceptDocument") . '">';
		}
	}
}
print '</td></tr>' . "\n";
print '</table>' . "\n";
print '</form>' . "\n";
print '</div>' . "\n";
print '<br>';

if (((int) DOL_VERSION) < 18) {
	/** @phpstan-ignore-next-line */
	htmlPrintOnlinePaymentFooter($mysoc, $langs);
} else {
	/** @phpstan-ignore-next-line */
	htmlPrintOnlineFooter($mysoc, $langs);
}

print '<script>
document.querySelectorAll("form[data-submit-once]").forEach(function(form) {
	form.addEventListener("submit", function() {
		var btn = form.querySelector("[type=submit]");
		if (btn) {
			btn.disabled = true;
			btn.dataset.originalText = btn.innerHTML;
			btn.innerHTML = \'<span class="loading loading-spinner loading-xs"></span> \' + (btn.dataset.loadingText || btn.textContent);
		}
	});
});
</script>';

llxFooter('', 'public');

$db->close();
