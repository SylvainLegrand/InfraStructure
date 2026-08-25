<?php

/**
 * Copyright © 2015-2016 Marcos García de La Fuente <hola@marcosgdf.com>
 *
 * This file is part of Multismtp.
 *
 * Multismtp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Multismtp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Multismtp.  If not, see <http://www.gnu.org/licenses/>.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res && file_exists("../../../../../main.inc.php")) {
	$res = @include "../../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';
require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/multismtp/lib/oauth.lib.php');
dol_include_once('/multismtp/class/Multismtp.class.php');
dol_include_once('/multismtp/lib/multismtp.php');
dol_include_once('/multismtp/lib/smtp2go.lib.php');	// InfraS add

use OAuth\Common\Storage\DoliStorage;

// Load translation files required by page
$langs->loadLangs(array('multismtp@multismtp', 'oauth', 'users', 'members'));

// Defini si peux lire/modifier permisssions
$canreaduser = (!empty($user->admin) || $user->hasRight("user", "user", "read"));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

if ($id)
{
	$caneditfield = ((($user->id == $id) && $user->hasRight("user", "self", "write")) || (($user->id != $id) && $user->hasRight("user", "user", "write")));
}

// Security check
$socid = 0;
if ($user->socid > 0) $socid = $user->socid;
$feature2 = (($socid && $user->hasRight("user", "self", "write")) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
if ($user->id != $id && !$canreaduser) {
	accessforbidden();
}

// Charge utilisateur edite
$fuser = new User($db);
$fuser->fetch($id, '', '', 1);
$fuser->getrights();

$multismtp = new Multismtp($db, $conf);
$multismtp->fetch($fuser);
$smtp_credentials = $multismtp->getSmtpCredentials();
$imap_credentials = $multismtp->getImapCredentials();

// Load Oauth Tokens
$supportedoauth2array = getSupportedOauth2Array();
$smtpOauthToken = null;
$keyforprovider = preg_replace('/^.*-/', '', $smtp_credentials['oauth_service_user']);
$keyforsupportedoauth2array = 'OAUTH_'.preg_replace('/-.*$/', '', $smtp_credentials['oauth_service_user']).'_NAME';
$OAUTH_SERVICENAME = empty($supportedoauth2array[$keyforsupportedoauth2array]['name']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['name'].($keyforprovider ? '-'.$keyforprovider : '');
$smtpOauthStorage = new DoliStorage($db, $conf, $keyforprovider);
try {
	$smtpOauthToken = $smtpOauthStorage->retrieveAccessToken($OAUTH_SERVICENAME);
} catch (Exception $e) {
}
$imapOauthToken = null;
$keyforprovider = preg_replace('/^.*-/', '', $imap_credentials['oauth_service_user']);
$keyforsupportedoauth2array = 'OAUTH_'.preg_replace('/-.*$/', '', $imap_credentials['oauth_service_user']).'_NAME';
$OAUTH_SERVICENAME = empty($supportedoauth2array[$keyforsupportedoauth2array]['name']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['name'].($keyforprovider ? '-'.$keyforprovider : '');
$imapOauthStorage = new DoliStorage($db, $conf, $keyforprovider);
try {
	$imapOauthToken = $imapOauthStorage->retrieveAccessToken($OAUTH_SERVICENAME);
} catch (Exception $e) {
}

// Load Imap folders
$imap_folders = array();
$imap_folders_errors = '';
if ($multismtp->checkImapConfig()) {
	$imap_folders = $multismtp->getImapFolders();
	if (!is_array($imap_folders)) {
		$imap_folders_errors = img_warning() . ' ' . $langs->trans('ErrorRetrievingIMAPFolders');
		$errorMsg = implode('<br>', $multismtp->errors);
		if ($errorMsg) {
			$imap_folders_errors .= '<br>' . $errorMsg;
		}
		$imap_folders = array();
	}
}

/*
 * Actions
 */
if ($action == 'update' && empty($_POST["cancel"]) && $caneditfield) {
	if (MultismtpImap::isEnabled()) {
		$multismtp->imap_server = GETPOST('IMAP_SERVER');
		$multismtp->imap_id = GETPOST('IMAP_ID');
		$multismtp->imap_pw = GETPOST('IMAP_PW');
		if (!is_object($imapOauthToken)) {
			// Can modify only if dont have current token
			$multismtp->imap_auth_type = GETPOST('IMAP_AUTH_TYPE');
			$multismtp->imap_oauth_service = GETPOST('IMAP_OAUTH_SERVICE');
			$multismtp->imap_oauth_provider = empty($multismtp->imap_oauth_service) ? GETPOST('IMAP_OAUTH_PROVIDER') : '';
			$multismtp->imap_oauth_url_authorize = empty($multismtp->imap_oauth_service) ? GETPOST('IMAP_OAUTH_URL_AUTHORIZE') : '';
			$multismtp->imap_oauth_id = empty($multismtp->imap_oauth_service) ? GETPOST('IMAP_OAUTH_ID') : '';
			$multismtp->imap_oauth_secret = empty($multismtp->imap_oauth_service) ? GETPOST('IMAP_OAUTH_SECRET') : '';
			$multismtp->imap_oauth_tenant = empty($multismtp->imap_oauth_service) ? GETPOST('IMAP_OAUTH_TENANT') : '';
			$multismtp->imap_oauth_scope = empty($multismtp->imap_oauth_service) ? ($multismtp->imap_oauth_provider == 'OAUTH_OTHER_NAME' ? GETPOST('IMAP_OAUTH_SCOPE') : implode(',', GETPOST('IMAP_OAUTH_SCOPE', 'array'))) : '';
		}
		$multismtp->imap_tls = null;
		$multismtp->imap_folder = null;
		$multismtp->imap_port = null;

		if (GETPOST('IMAP_PORT', 'int') != 0) {
			$multismtp->imap_port = GETPOST('IMAP_PORT', 'int');
		}

		if ($multismtp->checkImapConfig()) {
			$multismtp->imap_tls = GETPOST('IMAP_TLS', 'int');

			if (!$multismtp->checkImap()) {
				$msg = $langs->trans('IMAPConnectionError');

				if ($lasterror = $multismtp->error) {
					$msg .= "<br>" . $lasterror;
				}

				setEventMessage($msg, 'warnings');
			}
		}
	}

	if (getDolGlobalInt('MULTISMTP_SMTP_ENABLED')) { // InfraS change
		$main_mail_smtps_id = GETPOST('MAIN_MAIL_SMTPS_ID');
		$main_mail_smtps_auth_type = GETPOST('MAIN_MAIL_SMTPS_AUTH_TYPE');
		$main_mail_smtps_pw = GETPOST('MAIN_MAIL_SMTPS_PW', "none");
		$main_mail_smtps_oauth_service = GETPOST('MAIN_MAIL_SMTPS_OAUTH_SERVICE');
		$mail_mail_smtps_tls = GETPOST('MAIN_MAIL_EMAIL_TLS', 'int');
		$mail_mail_smtps_starttls = GETPOST('MAIN_MAIL_EMAIL_STARTTLS', 'int');
		$mail_mail_smtps_server = GETPOST('MAIN_MAIL_SMTP_SERVER');
		$mail_mail_smtps_port = null;
		$smtp_oauth_provider = empty($main_mail_smtps_oauth_service) ? GETPOST('SMTP_OAUTH_PROVIDER') : '';
		$smtp_oauth_url_authorize = empty($main_mail_smtps_oauth_service) ? GETPOST('SMTP_OAUTH_URL_AUTHORIZE') : '';
		$smtp_oauth_id = empty($main_mail_smtps_oauth_service) ? GETPOST('SMTP_OAUTH_ID') : '';
		$smtp_oauth_secret = empty($main_mail_smtps_oauth_service) ? GETPOST('SMTP_OAUTH_SECRET') : '';
		$smtp_oauth_tenant = empty($main_mail_smtps_oauth_service) ? GETPOST('SMTP_OAUTH_TENANT') : '';
		$smtp_oauth_scope = empty($main_mail_smtps_oauth_service) ? ($multismtp->imap_oauth_provider == 'OAUTH_OTHER_NAME' ? GETPOST('SMTP_OAUTH_SCOPE') : implode(',', GETPOST('SMTP_OAUTH_SCOPE', 'array'))) : '';

		if (GETPOST('MAIN_MAIL_SMTP_PORT', 'int') != 0) {
			$mail_mail_smtps_port = GETPOST('MAIN_MAIL_SMTP_PORT', 'int');
		}

		$multismtp->smtp_id = $main_mail_smtps_id;
		$multismtp->smtp_pw = $main_mail_smtps_pw;
		if (!is_object($smtpOauthToken)) {
			// Can modify only if dont have current token
			$multismtp->smtp_auth_type = $main_mail_smtps_auth_type;
			$multismtp->smtp_oauth_service = $main_mail_smtps_oauth_service;
			$multismtp->smtp_oauth_provider = $smtp_oauth_provider;
			$multismtp->smtp_oauth_url_authorize = $smtp_oauth_url_authorize;
			$multismtp->smtp_oauth_id = $smtp_oauth_id;
			$multismtp->smtp_oauth_secret = $smtp_oauth_secret;
			$multismtp->smtp_oauth_tenant = $smtp_oauth_tenant;
			$multismtp->smtp_oauth_scope = $smtp_oauth_scope;
		}
		$multismtp->smtp_tls = (bool)$mail_mail_smtps_tls;
		$multismtp->smtp_starttls = (bool)$mail_mail_smtps_starttls;

		if (!empty($mail_mail_smtps_server)) {
			$multismtp->smtp_server = $mail_mail_smtps_server;
		}

		if (!empty($mail_mail_smtps_port)) {
			$multismtp->smtp_port = $mail_mail_smtps_port;
		}

		/**
		 * SMTP check is disabled because of bug #5750
		 * https://github.com/Dolibarr/dolibarr/issues/5750
		 */

//		$smtpcred_check = $multismtp->checkSmtp($fuser);
//
//		if (is_string($smtpcred_check) && $multismtp->checkSmtpConfig()) {
//
//			$msg = $langs->trans('SMTPConnectionError').'<br>'.$smtpcred_check;
//
//			setEventMessage($msg, 'warnings');
//		}
	}

	try {
		$multismtp->update();
		$langs->load('admin');
		setEventMessages('SetupSaved', null);
	} catch (Exception $e) {
		setEventMessages($e->getMessage(), null, 'errors');
	}
} elseif ($action == 'set_imap_folder' && empty($_POST["cancel"]) && $caneditfield && !empty($imap_folders)) {
	try {
		$imap_folder = GETPOST('imap_folder', 'alpha');

		if (!in_array($imap_folder, array_keys($imap_folders))) {
			$langs->load('multismtp@multismtp');
			throw new Exception($langs->trans('ErrorFolderNotExist'));
		}

		$multismtp->imap_folder = $imap_folder;

		$multismtp->update();
		$langs->load('admin');
		setEventMessages('SetupSaved', null);
	} catch (Exception $e) {
		setEventMessages($e->getMessage(), null, 'errors');
	}
}

/*
 * View
 */

$langs->load("admin");

$form = new Form($db);

llxHeader('', $langs->trans('Email'));

$head = user_prepare_head($fuser);

$title = $langs->trans("User");

print dol_get_fiche_head($head, 'email', $title, 0, 'user');

// List of oauth services
$oauthservices = array();

foreach ($conf->global as $key => $val) {
	if (!empty($val) && preg_match('/^OAUTH_.*_ID$/', $key) && !preg_match('/-MultiSmtpUser/', $key)) {
		$key = preg_replace('/^OAUTH_/', '', $key);
		$key = preg_replace('/_ID$/', '', $key);
		if (preg_match('/^.*-/', $key)) {
			$name = preg_replace('/^.*-/', '', $key);
		} else {
			$name = $langs->trans("NoName");
		}
		$provider = preg_replace('/-.*$/', '', $key);
		$provider = ucfirst(strtolower($provider));

		$oauthservices[$key] = $name." (".$provider.")";
	}
}

$list = getAllOauth2Array();
$oathProviderOptions = [];
foreach ($list as $key) {
	$keyforsupportedoauth2array = $key[0];

	if (!in_array($keyforsupportedoauth2array, array_keys($supportedoauth2array))) {
		continue; // show only supported
	}

	$oathProviderOptions[str_replace('_NAME', '', $keyforsupportedoauth2array)] = $supportedoauth2array[$keyforsupportedoauth2array]['name'];
}

$atlestoneenabled = getDolGlobalInt('MULTISMTP_SMTP_ENABLED') || MultismtpImap::isEnabled(); // InfraS change

if ($action == 'edit' && $atlestoneenabled && $caneditfield) {
	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$id.'">'; // InfraS change
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
}

$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php">'.$langs->trans("BackToList").'</a>';
dol_banner_tab($fuser,'id',$linkback,$user->hasRight("user", "user", "read") || $user->admin);
print '<div class="underbanner clearboth"></div>';
print '<br>';

$smtp_iniserver = ini_get('SMTP') ?: $langs->transnoentities("Undefined");
$smtp_iniport = ini_get('smtp_port') ?: $langs->transnoentities("Undefined");
$smtp_credentials = $multismtp->getSmtpCredentials();
$imap_credentials = $multismtp->getImapCredentials();

if ($action == 'edit' && $atlestoneenabled && $caneditfield) {
	if ($conf->use_javascript_ajax && (getDolGlobalInt('MULTISMTP_SMTP_ENABLED') || MultismtpImap::isEnabled())) { // InfraS change
		$ajaxUrl = dol_buildpath('/multismtp/ajax/oauthsetup.php', 1);
		print "\n".'<script type="text/javascript" language="javascript">';
		print 'jQuery(document).ready(function () {
					function change_auth_method(type) {
						console.log("call change_auth_method", type);
						if (jQuery("#" + type + "_radio_oauth").prop("checked")) {
							jQuery("." + type + "_oauth_service").show();
							jQuery("." + type + "_pw").hide();
						} else {
							jQuery("." + type + "_oauth_service").hide();
							jQuery("." + type + "_pw").show();
						}
						change_auth_service(type);
					}
					function change_auth_service(type) {
						console.log("call change_auth_service", type);
						let input_oauth_service = jQuery("tr." + type + "_oauth_service");
						let custom = false;

						if (input_oauth_service.is(":visible")) {
							let input = input_oauth_service.find("select");
							if (input.length > 0 && input.val() == "") {
								custom = true;
								jQuery("." + type + "_oauth_setup").show();
							}
						}

						if (!custom) {
							jQuery("." + type + "_oauth_setup").hide();
						}
					}
					function change_auth_setup(type) {
						console.log("call change_auth_setup", type);
						let select_input = jQuery("#" + type.toUpperCase() + "_OAUTH_PROVIDER");
						let div_content = jQuery("#" + type + "_oauth_setup");
						let span_waiting = jQuery("#" + type + "_oauth_provider_waiting");

						span_waiting.hide();
						div_content.empty();
						if (type != "" && select_input.length > 0) {
							select_input.prop("disabled", true);
							span_waiting.show();
							$.ajax("'.$ajaxUrl.'", {
								method: "POST",
								data: {
									"token": "'.newToken().'",
									"id": '.$id.',
									"type": type,
									"provider": select_input.val(),
								},
								dataType: "json"
							}).done(function (response) {
								if (typeof response !== "undefined" && typeof response.content === "string") {
									div_content.html(response.content);
								} else if (typeof response !== "undefined" && typeof response.error === "string") {
									/* jnotify(message, preset of message type, keepmessage) */
									$.jnotify(response.error, "error", true, { remove: function() {} });
								}
							}).fail(function (jqxhr, textStatus, error) {
								/* jnotify(message, preset of message type, keepmessage) */
								$.jnotify(textStatus + " - " + error, "error", true, { remove: function() {} });
							}).always(function () {
								select_input.prop("disabled", false);
								span_waiting.hide();
							});
						}
					}
 					change_auth_method("smtp");
 					change_auth_method("imap");
					change_auth_setup("smtp");
					change_auth_setup("imap");
					jQuery(".radio_pw, .radio_oauth").change(function() {
	 					console.log("click radio");
						change_auth_method($(this).closest("tr").attr("data-type"));
					});
 					jQuery(".oauth_service").find("select").change(function() {
 						console.log("change oauth_service");
						change_auth_service($(this).closest("tr").attr("data-type"));
					});
					jQuery(".oauth_provider").find("select").change(function() {
						console.log("change oauth_provider");
						change_auth_setup($(this).closest("tr").attr("data-type"));
					});
              })';
		print '</script>'."\n";
	}

	if (getDolGlobalInt('MULTISMTP_SMTP_ENABLED')) { // InfraS change
		print '<div class="titre">'.$langs->trans('SMTPConfiguration').'</div>';
		print '<br><table class="border" width="100%">';

		// Server
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER", $smtp_iniserver).'</td><td>';

		if (getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER') == 1) { // InfraS change
			print '<input type="text" class="flat" size="32" name="MAIN_MAIL_SMTP_SERVER" value="'.$smtp_credentials['server'].'">';
		} else {
			print dol_htmlentities($smtp_credentials['server']);
		}
		print '</td></tr>';

		// Port
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT", $smtp_iniport).'</td><td>';
		if (getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER') == 1) { // InfraS change
			print '<input type="text" class="flat" size="3" name="MAIN_MAIL_SMTP_PORT" value="'.$smtp_credentials['port'].'">';
		} else {
			print dol_htmlentities($smtp_credentials['port']);
		}
		print '</td></tr>';

		// TLS
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		if (function_exists('openssl_open')) {
			if (getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER') == 1) { // InfraS change
				print $form->selectyesno('MAIN_MAIL_EMAIL_TLS', $smtp_credentials['tls'], 1);
			} else {
				print yn($smtp_credentials['tls']);
			}
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
		print '</td></tr>';

		// STARTTLS
		$var = '';
		if (versioncompare(versiondolibarrarray(), array(4,0,-5)) >= 0) {
			$var = !$var;
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
			if (getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER') == 1) { // InfraS change
				print $form->selectyesno('MAIN_MAIL_EMAIL_STARTTLS', $smtp_credentials['starttls'], 1);
			} else {
				print yn($smtp_credentials['starttls']);
			}
			print '</td></tr>';
		}

		// Auth mode
		$disabled	= empty(getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER')) ? ' disabled="disabled"' : ''; // InfraS change
		$userLink	= multismtp_smtp2go_get_user_credentials($id);	// InfraS add
		$smtp2go_apikey_defined	= getDolGlobalString('MULTISMTP_SMTP2GO_API_KEY') != '';	// InfraS add : when SMTP2GO governs this account, its own settings page is the place to manage the SMTP username/password, not this card
		print '<tr class="smtp_auth_method oddeven" data-type="smtp"><td>'.$langs->trans("MAIN_MAIL_SMTPS_AUTH_TYPE").'</td><td>';
		// Note: Default value for MAIN_MAIL_SMTPS_AUTH_TYPE if not defined is 'LOGIN' (but login/pass may be empty and they won't be provided in such a case)
		print '<input type="radio" class="radio_pw" id="smtp_radio_pw" name="MAIN_MAIL_SMTPS_AUTH_TYPE" value="LOGIN"'.($smtp_credentials['auth_type'] == 'LOGIN' ? ' checked' : '').$disabled.'> ';
		print '<label for="smtp_radio_pw" >'.$langs->trans("UsePassword").'</label>';
		print '&nbsp; &nbsp; &nbsp;';
		$disabledOauth = empty(getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER')) || version_compare(DOL_VERSION, '18.0.0') < 0 ? ' disabled="disabled"' : ''; // InfraS change
		print '<input type="radio" class="radio_oauth" id="smtp_radio_oauth" name="MAIN_MAIL_SMTPS_AUTH_TYPE" value="XOAUTH2"'.($smtp_credentials['auth_type'] == 'XOAUTH2' ? ' checked' : '').$disabledOauth.'> ';
		print '<label for="smtp_radio_oauth" >'.$form->textwithpicto($langs->trans("UseOauth"), $langs->trans("OauthNotAvailableForAllAndHadToBeCreatedBefore")).'</label>';
		print '</td></tr>';

		// SMTPS ID
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td><input type="text" class="flat" size="32" name="MAIN_MAIL_SMTPS_ID" value="'.dol_escape_htmltag(!empty($userLink['username']) ? $userLink['username'] : $smtp_credentials['id']).'"'.($smtp2go_apikey_defined ? ' readonly' : '').'></td></tr>';	// InfraS change : readonly (but still submitted) once SMTP2GO governs this account, to avoid diverging from the value managed there

		// SMTPS PW
		if ($smtp2go_apikey_defined) {	// InfraS add begin : password is managed on the SMTP2GO settings tab, not editable here anymore
			print '<tr class="smtp_pw oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>
				<input type="hidden" name="MAIN_MAIL_SMTPS_PW" value="'.dol_escape_htmltag(!empty($userLink['password']) ? $userLink['password'] : $smtp_credentials['pw']).'">
				<span class="opacitymedium">'.$langs->trans('Smtp2goPasswordManagedElsewhere').'</span>
				<a href="'.dol_escape_htmltag(dol_buildpath('/multismtp/admin/smtp2go.php', 1)).'">'.$langs->trans('Smtp2goGoToSettings').'</a>
			</td></tr>';
		} else {
			print '<tr class="smtp_pw oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td><input type="password" id="main_mail_smtps_pw" class="flat" size="32" name="MAIN_MAIL_SMTPS_PW" value="'.dol_escape_htmltag(!empty($userLink['password']) ? $userLink['password'] : $smtp_credentials['pw']).'">
				<span class="fa fa-eye paddingleft paddingright" onclick="newtype = (jQuery(\'#main_mail_smtps_pw\').attr(\'type\') == \'text\' ? \'password\' : \'text\'); jQuery(\'#main_mail_smtps_pw\').attr(\'type\', newtype);"></span></td></tr>';
		}	// InfraS add end

		// OAUTH service provider
		print '<tr class="smtp_oauth_service oauth_service oddeven" data-type="smtp"><td>'.$langs->trans("MAIN_MAIL_SMTPS_OAUTH_SERVICE").'</td><td>';
		if (empty(getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER'))) { // InfraS change
			if (!empty($smtp_credentials['oauth_service'])) {
				$text = $oauthservices[$smtp_credentials['oauth_service']] ?? '';
				if (empty($text)) {
					$text = $langs->trans("Undefined") . img_warning();
				}
				print $text;
			} elseif (!empty($smtp_credentials['oauth_provider'])) {
				print $langs->trans('MultiSmtpCustom') . ' (' . str_replace('OAUTH_', '', strtoupper($smtp_credentials['oauth_provider'])) . ')';
			} else {
				print $langs->trans("None");
			}
			print '<input type="hidden" id="MAIN_MAIL_SMTPS_OAUTH_SERVICE" name="MAIN_MAIL_SMTPS_OAUTH_SERVICE" value="'.dol_escape_js($smtp_credentials['oauth_service'], 2) . '">';
		} else {
			$options = [
				'' => $langs->trans('MultiSmtpCustom')
			];
			$options += $oauthservices;
			print $form->selectarray('MAIN_MAIL_SMTPS_OAUTH_SERVICE', $options, $smtp_credentials['oauth_service']);
		}
		print '</td></tr>';

		// OAUTH provider
		print '<tr class="smtp_oauth_provider smtp_oauth_setup oauth_provider oddeven" data-type="smtp"><td>'.$langs->trans("OAuthProvider").'</td><td>';
		if (empty(getDolGlobalInt('MULTISMTP_ALLOW_CHANGESERVER'))) { // InfraS change
			print $oathProviderOptions[$smtp_credentials['oauth_provider']] ?? '';
			print '<input type="hidden" id="SMTP_OAUTH_PROVIDER" name="SMTP_OAUTH_PROVIDER" value="'.dol_escape_js($smtp_credentials['oauth_provider'], 2) . '">';
		} else {
			print $form->selectarray('SMTP_OAUTH_PROVIDER', $oathProviderOptions, $smtp_credentials['oauth_provider']);
			print '<span id="smtp_oauth_provider_waiting"> <i class="fa fa-spin fa-spinner"></i></span>';
		}
		print '</td></tr>';

		print '</table><br>';

		// OAUTH setup
		print '<div class="smtp_oauth_setup" id="smtp_oauth_setup"></div>';
	}

	if (MultismtpImap::isEnabled()) {
		print '<div class="titre">'.$langs->trans('IMAPConfiguration').'</div>';

		print '<br><table class="border" width="100%">';

		// Server
		print '<tr class="oddeven">
		<td>'.$langs->trans('IMAP_SERVER').'</td>
		<td>';
		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_SERVER'))) { // InfraS change
			print dol_htmlentities($imap_credentials['server']);
		} else {
			print '<input type="text" class="flat" size="32" name="IMAP_SERVER" value="'.$imap_credentials['server'].'">';
		}
		print '</td>
		</tr>';

		// Port
		print '<tr class="oddeven"><td>'.$langs->trans('IMAP_PORT').'</td><td>';
		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_PORT'))) { // InfraS change
			print dol_htmlentities($imap_credentials['port']);
		} else {
			print '<input type="text" class="flat" size="5" name="IMAP_PORT" value="'.$imap_credentials['port'].'"></td></tr>';
		}

		// TLS
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_SERVER'))) { // InfraS change
			print yn(getDolGlobalInt('MULTISMTP_IMAP_CONF_TLS')); // InfraS change
		} else {
			print $form->selectyesno('IMAP_TLS', $imap_credentials['tls'], 1);
		}
		print '</td></tr>';

		// Auth mode
		$disabled = !empty(getDolGlobalString('MULTISMTP_IMAP_CONF_SERVER')); // InfraS change
		print '<tr class="imap_auth_method oddeven" data-type="imap"><td>'.$langs->trans("IMAP_AUTH_TYPE").'</td><td>';
		// Note: Default value for IMAP_AUTH_TYPE if not defined is 'LOGIN' (but login/pass may be empty and they won't be provided in such a case)
		print '<input type="radio" class="radio_pw" id="imap_radio_pw" name="IMAP_AUTH_TYPE" value="LOGIN"'.($imap_credentials['auth_type'] == 'LOGIN' ? ' checked' : '').($disabled ? ' disabled' : '').'> ';
		print '<label for="imap_radio_pw" >'.$langs->trans("UsePassword").'</label>';
		print '&nbsp; &nbsp; &nbsp;';
		$disabledOAuthImap = !getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')
			|| (!defined('EASYA_VERSION') && version_compare(DOL_VERSION, '22.0.0')) < 0
			|| (defined('EASYA_VERSION') && version_compare(EASYA_VERSION, '2024.0.0') < 0);
		print '<input type="radio" class="radio_oauth" id="imap_radio_oauth" name="IMAP_AUTH_TYPE" value="XOAUTH2"'.(!$disabledOAuthImap && $imap_credentials['auth_type'] == 'XOAUTH2' ? ' checked' : '').($disabledOAuthImap || $disabled ? ' disabled' : '').'> ';
		print '<label for="imap_radio_oauth" >'.$form->textwithpicto($langs->trans("UseOauth"), $langs->trans("OauthNotAvailableForAllAndHadToBeCreatedBefore").($disabledOAuthImap ? '<br>' . $langs->trans("MultismtpWarningsActivatePhpImap") : '')).'</label>';
		print '</td></tr>';

		// IMAP ID
		print '<tr class="oddeven"><td>'.$langs->trans('IMAP_ID').'</td><td><input type="text" class="flat" size="32" name="IMAP_ID" value="'.$imap_credentials['id'].'"></td></tr>';

		// IMAP PW
		print '<tr class="imap_pw oddeven">
	<td>'.$langs->trans('IMAP_PW').'</td>
	<td><input type="password" id="imap_pw" class="flat" size="32" name="IMAP_PW" value="'.$imap_credentials['pw'].'">
		<span class="fa fa-eye paddingleft paddingright" onclick="newtype = (jQuery(\'#imap_pw\').attr(\'type\') == \'text\' ? \'password\' : \'text\'); jQuery(\'#imap_pw\').attr(\'type\', newtype);"></span></td></tr>';	// InfraS add

		// OAUTH service provider
		print '<tr class="imap_oauth_service oauth_service oddeven" data-type="imap"><td>'.$langs->trans("IMAP_OAUTH_SERVICE").'</td><td>';
		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_OAUTH_SERVICE'))) { // InfraS change
			if (!empty($imap_credentials['oauth_service'])) {
				$text = $oauthservices[$imap_credentials['oauth_service']] ?? '';
				if (empty($text)) {
					$text = $langs->trans("Undefined") . img_warning();
				}
				print $text;
			} elseif (!empty($imap_credentials['oauth_provider'])) {
				print $langs->trans('MultiSmtpCustom') . ' (' . str_replace('OAUTH_', '', strtoupper($imap_credentials['oauth_provider'])) . ')';
			} else {
				print $langs->trans("None");
			}
			print '<input type="hidden" id="IMAP_OAUTH_SERVICE" name="IMAP_OAUTH_SERVICE" value="'.dol_escape_js($imap_credentials['oauth_service'], 2) . '">';
		} else {
			$options = [
				'' => $langs->trans('MultiSmtpCustom')
			];
			$options += $oauthservices;
			print $form->selectarray('IMAP_OAUTH_SERVICE', $options, $imap_credentials['oauth_service']);
		}
		print '</td></tr>';

		// OAUTH provider
		print '<tr class="imap_oauth_provider imap_oauth_setup oauth_provider oddeven" data-type="imap"><td>'.$langs->trans("OAuthProvider").'</td><td>';
		if (!empty(getDolGlobalString('MULTISMTP_IMAP_CONF_OAUTH_SERVICE'))) { // InfraS change
			print $oathProviderOptions[$imap_credentials['oauth_provider']] ?? '';
			print '<input type="hidden" id="IMAP_OAUTH_PROVIDER" name="IMAP_OAUTH_PROVIDER" value="'.dol_escape_js($imap_credentials['oauth_provider'], 2) . '">';
		} else {
			print $form->selectarray('IMAP_OAUTH_PROVIDER', $oathProviderOptions, $imap_credentials['oauth_provider']);
		}
		print '<span id="imap_oauth_provider_waiting"> <i class="fa fa-spin fa-spinner"></i></span>';
		print '</td></tr>';

		print '</table><br>';

		// OAUTH setup
		print '<div class="imap_oauth_setup" id="imap_oauth_setup"></div>';
	}

	print '<br><div class="center">';

	print '<input type="hidden" name="token" value="' . newtoken() . '">';

	print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div></form>';
} else {
	if (getDolGlobalInt('MULTISMTP_SMTP_ENABLED')) { // InfraS change
		print '<div class="titre">'.$langs->trans('SMTPConfiguration').'</div>';

		print '<br><table class="border" width="100%">';

		// Server
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER", $smtp_iniserver).'</td><td>'.dol_htmlentities($smtp_credentials['server']).'</td></tr>';

		// Port
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT", $smtp_iniport).'</td><td>'.dol_htmlentities($smtp_credentials['port']).'</td></tr>';

		// TLS
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		if (function_exists('openssl_open')) {
			print yn($smtp_credentials['tls']);
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
		print '</td></tr>';

		// STARTTLS
		$var = '';
		if (versioncompare(versiondolibarrarray(), array(4,0,-5)) >= 0) {
			$var = !$var;
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
			if (function_exists('openssl_open')) {
				print yn($smtp_credentials['starttls']);
			} else {
				print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
			}
			print '</td></tr>';
		}

		// AUTH method
		$text = ($smtp_credentials['auth_type'] === "LOGIN") ? $langs->trans("UsePassword") : ($smtp_credentials['auth_type'] === "XOAUTH2" ?  $langs->trans("UseOauth") : '') ;
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_AUTH_TYPE").'</td><td>'.$text.'</td></tr>';

		// SMTPS ID
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>'.dol_htmlentities($smtp_credentials['id']).'</td></tr>';

		// SMTPS PW
		if ($smtp_credentials['auth_type'] != "XOAUTH2") {
			print '<tr class="oddeven"><td>' . $langs->trans("MAIN_MAIL_SMTPS_PW") . '</td><td>' . preg_replace('/./', '*', $smtp_credentials['pw']) . '</td></tr>';
		}

		// SMTPS oauth service
		if ($smtp_credentials['auth_type'] === "XOAUTH2") {
			if (!empty($smtp_credentials['oauth_service'])) {
				$text = $oauthservices[$smtp_credentials['oauth_service']] ?? '';
				if (empty($text)) {
					$text = $langs->trans("Undefined") . img_warning();
				}
			} elseif (!empty($smtp_credentials['oauth_provider'])) {
				$text = $langs->trans('MultiSmtpCustom') . ' (' . str_replace('OAUTH_', '', strtoupper($smtp_credentials['oauth_provider'])) . ')';
			} else {
				$text = $langs->trans("None");
			}
			print '<tr class="oddeven"><td>' . $langs->trans("MAIN_MAIL_SMTPS_OAUTH_SERVICE") . '</td><td>' . $text . '</td></tr>';
		}

		// Manage access token
		printOauthAccessTokenManagment($smtpOauthStorage, $smtpOauthToken, $smtp_credentials);

		print '</table><br>';
	}

	if (MultismtpImap::isEnabled()) {
		print '<div class="titre">'.$langs->trans('IMAPConfiguration').'</div>';

		print '<br><table class="border" width="100%">';

		// Server
		print '<tr class="oddeven"><td>'.$langs->trans('IMAP_SERVER').'</td><td>'.dol_htmlentities($imap_credentials['server']).'</td></tr>';

		// Port
		print '<tr class="oddeven"><td>'.$langs->trans('IMAP_PORT').'</td><td>'.dol_htmlentities($imap_credentials['port']).'</td></tr>';

		// TLS
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		print yn($imap_credentials['tls']);
		print '</td></tr>';

		// AUTH method
		$text = ($imap_credentials['auth_type'] === "LOGIN") ? $langs->trans("UsePassword") : ($imap_credentials['auth_type'] === "XOAUTH2" ?  $langs->trans("UseOauth") : '') ;
		print '<tr class="oddeven"><td>'.$langs->trans("IMAP_AUTH_TYPE").'</td><td>'.$text.'</td></tr>';

		// IMAP ID
		print '<tr class="oddeven"><td>'.$langs->trans('IMAP_ID').'</td><td>'.dol_htmlentities($imap_credentials['id']).'</td></tr>';

		// IMAP PW
		if ($imap_credentials['auth_type'] != "XOAUTH2") {
			print '<tr class="oddeven"><td>' . $langs->trans('IMAP_PW') . '</td><td>' . preg_replace('/./', '*', $imap_credentials['pw']) . '</td></tr>';
		}

		// IMAP oauth service
		if ($imap_credentials['auth_type'] === "XOAUTH2") {
			if (!empty($imap_credentials['oauth_service'])) {
				$text = $oauthservices[$imap_credentials['oauth_service']] ?? '';
				if (empty($text)) {
					$text = $langs->trans("Undefined") . img_warning();
				}
			} elseif (!empty($imap_credentials['oauth_provider'])) {
				$text = $langs->trans('MultiSmtpCustom') . ' (' . str_replace('OAUTH_', '', strtoupper($imap_credentials['oauth_provider'])) . ')';
			} else {
				$text = $langs->trans("None");
			}
			print '<tr class="oddeven"><td>' . $langs->trans("IMAP_OAUTH_SERVICE") . '</td><td>' . $text . '</td></tr>';

			// Manage access token
			printOauthAccessTokenManagment($imapOauthStorage, $imapOauthToken, $imap_credentials);
		}

		// IMAP Folders
		print '<tr class="oddeven"><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('IMAP_FOLDER');
		print '</td>';
		if ($action != 'edit_imap_folder' && !empty($imap_folders) && $caneditfield) {
			print '<td class="right"><a class="editfielda" href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?action=edit_imap_folder&token=' . newToken() . '&id=' . $id . '">' . // InfraS change
				img_edit($langs->transnoentitiesnoconv('MultiSmtpSetImapFolder'), 1) . '</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'edit_imap_folder' && !empty($imap_folders) && $caneditfield) {
			print '<form method="POST" action="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?id=' . $id . '">'; // InfraS change
			print '<input type="hidden" name="action" value="set_imap_folder">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print $form->selectarray('imap_folder', $imap_folders, $imap_credentials['folder'], true);
			print '<input type="submit" class="button smallpaddingimp valignmiddle" value="' . $langs->trans("Modify") . '">';
			print '</form>';
		} else {
			print empty($imap_credentials['folder']) ? '' : ($imap_folders[$imap_credentials['folder']] ?? $langs->trans('ObjectNotFound', $imap_credentials['folder']));
		}
		print '</td></tr>';

		print '</table>';
	}

	if (!$atlestoneenabled) {
		$url = dol_buildpath('/multismtp/admin/setup.php', 1);

		print '<p style="text-align: center">'.img_warning().' ';

		if ($user->admin) {
			print '<a href="'.$url.'">';
		}

		print $langs->trans('CheckModuleConfiguration');

		if ($user->admin) {
			print '</a>';
		}

		print '</p>';
	}
}

print dol_get_fiche_end();

if ($action != 'edit' && $atlestoneenabled && $caneditfield) {
	// Boutons actions
	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$id.'&action=edit">'.$langs->trans("Modify").'</a>'; // InfraS change

	print '</div>';
}

llxFooter();


function printOauthAccessTokenManagment($oauthStorage, $oauthToken, $credentials)
{
	global $langs, $form, $user, $supportedoauth2array, $caneditfield, $dolibarr_main_url_root;

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$backtourl = urlencode(dol_buildpath('/multismtp/user.php', 1) . '?id=' . $user->id);
	$oauthstateanticsrf = bin2hex(random_bytes(128 / 8));

	$keyforsupportedoauth2array = $credentials['oauth_service_user'];                        // May be OAUTH_GOOGLE_NAME or OAUTH_GOOGLE_xxx_NAME
	if (preg_match('/^.*-/', $keyforsupportedoauth2array)) {
		$keyforprovider = preg_replace('/^.*-/', '', $keyforsupportedoauth2array);
	} else {
		$keyforprovider = '';
	}
	$keyforsupportedoauth2array = preg_replace('/-.*$/', '', $keyforsupportedoauth2array);
	$keyforsupportedoauth2array = 'OAUTH_' . $keyforsupportedoauth2array . '_NAME';

	if (!empty($credentials['oauth_service'])) {
		$scopes = getDolGlobalString('OAUTH_' . $credentials['oauth_service'] . '_SCOPE');
	} else {
		$scopes = $credentials['oauth_scope'];
	}
	$shortscope = '';
	if (!empty($scopes)) {
		$shortscope = $scopes;
	}
	$state = $shortscope;    // TODO USe a better state

	// Define $urltorenew, $urltodelete, $urltocheckperms
	if ($keyforsupportedoauth2array == 'OAUTH_GITHUB_NAME') {
		// List of keys that will be converted into scopes (from constants 'SCOPE_state_in_uppercase' in file of service).
		// We pass this param list in to 'state' because we need it before and after the redirect.
		// Note: github does not accept csrf key inside the state parameter (only known values)
		$urlbase = $urlwithroot.'/core/modules/oauth/github_oauthcallback.php';
		//$urlbase = dol_buildpath('/multismtp/core/oauth/github_oauthcallback.php', 3);
		$urltorenew = $urlbase . '?shortscope=' . urlencode($shortscope) . '&state=' . urlencode($shortscope) . '&backtourl=' . $backtourl;
		$urltodelete = $urlbase . '?action=delete&token=' . newToken() . '&backtourl=' . $backtourl;
		$urltocheckperms = 'https://github.com/settings/applications/';
	} elseif ($keyforsupportedoauth2array == 'OAUTH_GOOGLE_NAME') {
		// List of keys that will be converted into scopes (from constants 'SCOPE_state_in_uppercase' in file of service).
		// List of scopes for Google are here: https://developers.google.com/identity/protocols/oauth2/scopes
		// We pass this key list into the param 'state' because we need it before and after the redirect.
		$urlbase = $urlwithroot.'/core/modules/oauth/google_oauthcallback.php';
		//$urlbase = dol_buildpath('/multismtp/core/oauth/google_oauthcallback.php', 3);
		$urltorenew = $urlbase . '?shortscope=' . urlencode($shortscope) . '&state=' . urlencode($state) . '-' . $oauthstateanticsrf . '&backtourl=' . $backtourl;
		$urltodelete = $urlbase . '?action=delete&token=' . newToken() . '&backtourl=' . $backtourl;
		$urltocheckperms = 'https://security.google.com/settings/security/permissions';
	} elseif (!empty($supportedoauth2array[$keyforsupportedoauth2array]['returnurl'])) {
		$urlbase = $urlwithroot.$supportedoauth2array[$keyforsupportedoauth2array]['returnurl'];
		//$urlbase = dol_buildpath(preg_replace('/^\/core\/modules\/oauth\//', '/multismtp/core/oauth/', $supportedoauth2array[$keyforsupportedoauth2array]['returnurl']), 3);
		$urltorenew = $urlbase . '?shortscope=' . urlencode($shortscope) . '&state=' . urlencode($state) . '&backtourl=' . $backtourl;
		$urltodelete = $urlbase . '?action=delete&token=' . newToken() . '&backtourl=' . $backtourl;
		$urltocheckperms = '';
	} else {
		$urltorenew = '';
		$urltodelete = '';
		$urltocheckperms = '';
	}

	if ($urltorenew) {
		$urltorenew .= '&keyforprovider=' . urlencode($keyforprovider);
	}
	if ($urltodelete) {
		$urltodelete .= '&keyforprovider=' . urlencode($keyforprovider);
	}

	// SMTPS oauth token
	print '<tr class="oddeven">';
	print '<tr class="oddeven"><td>' . $langs->trans("IsTokenGenerated") . '</td>';
	print '<td>';
	print '<table class="centpercent nobordernopadding">';
	print '<tr>';
	print '<td>';
	if (is_object($oauthToken)) {
		print $form->textwithpicto(yn(1), $langs->trans("HasAccessToken") . ' : ' . dol_print_date($oauthStorage->date_modification, 'dayhour'));

		$refreshtoken = $oauthToken->getRefreshToken();
		// Is token expired or will token expire in the next 30 seconds
		$expire = ($oauthToken->getEndOfLife() !== $oauthToken::EOL_NEVER_EXPIRES && $oauthToken->getEndOfLife() !== $oauthToken::EOL_UNKNOWN && time() > ($oauthToken->getEndOfLife() - 30));
		$endoflife = $oauthToken->getEndOfLife();
		if ($endoflife == $oauthToken::EOL_NEVER_EXPIRES) {
			$expiredat = $langs->trans("Never");
		} elseif ($endoflife == $oauthToken::EOL_UNKNOWN) {
			$expiredat = $langs->trans("Unknown");
		} else {
			$expiredat = dol_print_date($endoflife, "dayhour", 'tzuserrel');
		}
		print '<br>' . $langs->trans("TOKEN_REFRESH") . ' : ' . yn(!empty($refreshtoken));
		print '<br>' . $langs->trans("TOKEN_EXPIRED") . ' : ' . yn($expire);
		print '<br>' . $langs->trans("TOKEN_EXPIRE_AT") . ' : ' . $expiredat;
	} else {
		print '<span class="opacitymedium">' . $langs->trans("NoAccessToken") . '</span>';
	}
	print '</td>';
	print '<td class="right">';
	if ($caneditfield) {
		// Delete token
		if (is_object($oauthToken)) {
			if ($urltodelete) {
				print '<a class="button smallpaddingimp" href="' . $urltodelete . '">' . $langs->trans('DeleteAccess') . '</a><br>';
			} else {
				print '<span class="opacitymedium">' . $langs->trans('GoOnTokenProviderToDeleteToken') . '</span><br>';
			}
		}
		// Get/renew token
		if ($urltorenew) {
			print '<a class="button smallpaddingimp" href="' . $urltorenew . '">' . $langs->trans('GetAccess') . '</a>';
			print $form->textwithpicto('', $langs->trans('RequestAccess'));
			print '<br>';
		}
		// Check remote access
		if ($urltocheckperms) {
			print '<br>' . $langs->trans("ToCheckDeleteTokenOnProvider") . ': <a href="' . $urltocheckperms . '" target="_' . strtolower($credentials['oauth_service_user']) . '">' . $urltocheckperms . '</a>';
		}
	}
	print '</td>';
	print '</tr>';
	print '</table>';
	print '</td>';
	print '</tr>';
}
