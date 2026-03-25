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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once __DIR__.'/../class/Multismtp.class.php';
require_once __DIR__.'/../lib/multismtp.php';

if (!$user->admin) {
	accessforbidden();
}

$form = new Form($db);
$action = GETPOST('action');
$c = GETPOST('c');

$allowed_constants = array(
	'MULTISMTP_SMTP_ENABLED',
	'MULTISMTP_ALLOW_CHANGESERVER',
	'MULTISMTP_IMAP_ENABLED',
	'MULTISMTP_IMAP_NOVALIDATECERT',
	'MULTISMTP_SENT_ONLY_FROM_CARD',
	'MAIN_IMAP_USE_PHPIMAP',
);

if (in_array($c, $allowed_constants)) {
	if ($action == 'enable') {
		dolibarr_set_const($db, $c, '1', 'int', 0, '', $conf->entity);
	} elseif ($action == 'disable') {

		if ($c == 'MULTISMTP_IMAP_ENABLED') {
			Multismtp::removeAllImapCredentials();
		} elseif ($c == 'MULTISMTP_SMTP_ENABLED') {
			Multismtp::removeAllSmtpCredentials();
		} elseif ($c == 'MULTISMTP_SENT_ONLY_FROM_CARD') {
			dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_SERVER', 1, 'chaine', 0, '', $conf->entity);
		}

		dolibarr_set_const($db, $c, '0', 'int', 0, '', $conf->entity);
	}
}

if ($action == 'set_cron_options') {
	$cron_refresh_days = GETPOSTINT('MULTISMTP_CRON_REFRESH_TOKEN_DAYS');
	if ($cron_refresh_days < 1) $cron_refresh_days = 30;
	dolibarr_set_const($db, 'MULTISMTP_CRON_REFRESH_TOKEN_DAYS', $cron_refresh_days, 'int', 0, '', $conf->entity);
	setEventMessage($langs->trans('SetupSaved'));
}

if ($action == 'set_imap_options') {

	$imap_server = GETPOST('MULTISMTP_IMAP_CONF_SERVER');
	$imap_port = GETPOST('MULTISMTP_IMAP_CONF_PORT');
	$imap_tls = GETPOST('MULTISMTP_IMAP_CONF_TLS', 'int');
	$imap_auth_type = GETPOST('MULTISMTP_IMAP_CONF_AUTH_TYPE', 'alphanohtml');
	if ($imap_auth_type == '-1') $imap_auth_type = '';
	$imap_oauth_service = GETPOST('MULTISMTP_IMAP_CONF_OAUTH_SERVICE', 'alphanohtml');
	if ($imap_oauth_service == '-1') $imap_oauth_service = '';

	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_SERVER', $imap_server, 'chaine', 0, '',
		$conf->entity);
	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_PORT', $imap_port, 'chaine', 0, '',
		$conf->entity);
	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_TLS', $imap_tls, 'int', 0, '',
		$conf->entity);
	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_AUTH_TYPE', $imap_auth_type, 'chaine', 0, '',
		$conf->entity);
	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_OAUTH_SERVICE', $imap_oauth_service, 'chaine', 0, '',
		$conf->entity);

	Multismtp::removeAllImapServerInfo();

	setEventMessage($langs->trans('SetupSaved'));
}

$langs->load('admin');
$langs->load('multismtp@multismtp');
$langs->load("opendsi@massupdaterights");

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

$wikihelp='EN:Multismtp_En|FR:Multismtp_Fr|ES:Multismtp_Es';
llxHeader('', $langs->trans("MultismtpSetup"), $wikihelp);
//print load_fiche_titre($langs->trans('ModuleSetup').' Multi SMTP', $linkback, 'title_setup');
print load_fiche_titre($langs->trans("MultismtpSetup"), $linkback, 'title_setup');
$head = multismtp_admin_prepare_head();
if (!isset($conf->global->MAIN_ACTIVATE_UPDATESESSIONTRIGGER)) {
	print info_admin($langs->trans('TriggerNotActive'));
}

// Test if Dolibarr version ok
/* if (versiondolibarrarray() == array(3,7,0)) {
	print info_admin($langs->trans('NotWorking370'));
} */

$isV14p = version_compare(DOL_VERSION, "14.0.0") >= 0;

if ($isV14p) {
    print dol_get_fiche_head($head, 'settings', $langs->trans("Module402002Name"), -1, 'opendsi@multismtp');
} else {
    dol_fiche_head($head, 'settings', $langs->trans("Module402002Name"), -1, 'opendsi@multismtp');
}


/**
 * SMTP
 */

$token=newToken();

print '<br><div class="titre">SMTP</div>';
print '<p>'.$langs->trans('SMTPDescription').'</p>';

if (!empty($conf->global->MAIN_DISABLE_ALL_MAILS)) {
	echo info_admin($langs->trans('WarningMailDisabled',
		'<a href="'.dol_buildpath('/admin/mails.php', 2).'">', '</a>'));
}

if (empty($conf->global->MAIN_MAIL_SENDMODE) || $conf->global->MAIN_MAIL_SENDMODE == 'mail') {
	echo info_admin($langs->trans('WarningMailSendMode', $langs->transnoentities('MAIN_MAIL_SENDMODE'),
		'<a href="'.dol_buildpath('/admin/mails.php', 2).'">', '</a>'));
} else {

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Enable/Disable
	print '<tr class="impair"><td>'.$langs->trans("MULTISMTP_SMTP_ENABLED").'</td><td>';
	if ($conf->global->MULTISMTP_SMTP_ENABLED == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=disable&c=MULTISMTP_SMTP_ENABLED&token='.$token.'">'.img_picto($langs->trans("Enabled"),
				'switch_on').'</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=enable&c=MULTISMTP_SMTP_ENABLED&token='.$token.'">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	}
	print '</td></tr>';

	// MultiSMTP only from card
	print '<tr class="impair"><td>'.$langs->trans("MULTISMTP_SENT_ONLY_FROM_CARD").'</td><td>';
	if ($conf->global->MULTISMTP_SENT_ONLY_FROM_CARD == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=disable&c=MULTISMTP_SENT_ONLY_FROM_CARD">'.img_picto($langs->trans("Enabled"),
				'switch_on').'</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=enable&c=MULTISMTP_SENT_ONLY_FROM_CARD">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	}
	print '</td></tr>';

	// Allow changing SMTP server
	print '<tr class="pair"><td>'.$langs->trans("MULTISMTP_ALLOW_CHANGESERVER").'</td><td>';
	if ($conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=disable&c=MULTISMTP_ALLOW_CHANGESERVER&token='.$token.'">'.img_picto($langs->trans("Enabled"),
				'switch_on').'</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=enable&c=MULTISMTP_ALLOW_CHANGESERVER&token='.$token.'">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	}
	print '</td></tr>';

	print '</table>';
	print '<p>'.$langs->trans('PreParameterAdvice').'</p>';
}


/**
 * IMAP
 */

// List of oauth services
$oauthservices = array();

foreach ($conf->global as $key => $val) {
	if (!empty($val) && preg_match('/^OAUTH_.*_ID$/', $key)) {
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

print '<form method="post">';
print '<input type="hidden" name="token" value="' . newtoken() . '">';
print '<input type="hidden" name="action" value="set_imap_options">';

print '<br><div class="titre">IMAP</div>';
print '<p>'.$langs->trans('IMAPDescription').'</p>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

// MAIN_IMAP_USE_PHPIMAP: Enable use of the PHP Imap library
print '<tr class="oddeven"><td>';
print $langs->trans("MAIN_IMAP_USE_PHPIMAP");
print '</td>';
print '<td class="left">';
if (getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')) {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=disable&c=MAIN_IMAP_USE_PHPIMAP&token=' . $token . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
} else {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=enable&c=MAIN_IMAP_USE_PHPIMAP&token='.$token.'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print '</td>';
print '</tr>';

// Enable/Disable
print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_IMAP_ENABLED").'</td><td>';
if (MultismtpImap::isEnabled()) {
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=disable&c=MULTISMTP_IMAP_ENABLED&token=' . $token . '">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
} else {
	if (MultismtpImap::isEnabled(true)) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=enable&c=MULTISMTP_IMAP_ENABLED&token='.$token.'">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	} else {
		print img_warning().' '.$langs->trans('IMAPNotAvailable');
	}
}
print '</td></tr>';

if (MultismtpImap::isEnabled(true)) {
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans('ForceIMAPConfiguration').'</td></tr>';

	// Allow self-signed certificates
	print '<tr class="oddeven"><td>'.$langs->trans('MULTISMTP_IMAP_NOVALIDATECERT').'</td><td>';
	if ($conf->global->MULTISMTP_IMAP_NOVALIDATECERT == 1) {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=disable&c=MULTISMTP_IMAP_NOVALIDATECERT&token='.$token.'">'.img_picto($langs->trans("Enabled"),
				'switch_on').'</a>';
	} else {
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=enable&c=MULTISMTP_IMAP_NOVALIDATECERT&token='.$token.'">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	}
	print '</td></tr>';

	// Restrict IMAP server
	print '<tr class="oddeven"><td>'.$langs->trans("Host").'</td><td>';
	print '<input type="text" name="MULTISMTP_IMAP_CONF_SERVER" value="'.($conf->global->MULTISMTP_IMAP_CONF_SERVER ?? '').'" class="flat"';
	if (!MultismtpImap::isEnabled()) {
		print 'disabled';
	}
	print '>';
	print '</td></tr>';

	// Restrict IMAP port
	print '<tr class="oddeven"><td>'.$langs->trans("Port").'</td><td>';
	print '<input type="text" name="MULTISMTP_IMAP_CONF_PORT" value="'.($conf->global->MULTISMTP_IMAP_CONF_PORT ?? '').'" size="4" class="flat"';
	if (!MultismtpImap::isEnabled()) {
		print 'disabled';
	}
	print '>';
	print '</td></tr>';

	// Restrict IMAP tls
	print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_IMAP_CONF_SSL").'</td><td>';
	print $form->selectyesno('MULTISMTP_IMAP_CONF_TLS', ($conf->global->MULTISMTP_IMAP_CONF_TLS ?? ''), 1, !MultismtpImap::isEnabled());
	print '</td></tr>';

	// Restrict IMAP auth mode
	$authtypes = array(
		'LOGIN' => $langs->trans("UsePassword"),
	);
	$disabledOAuthImap = !getDolGlobalString('MAIN_IMAP_USE_PHPIMAP')
		|| (!defined('EASYA_VERSION') && version_compare(DOL_VERSION, '22.0.0')) < 0
		|| (defined('EASYA_VERSION') && version_compare(EASYA_VERSION, '2024.0.0') < 0);
	if (!$disabledOAuthImap) {
		$authtypes['XOAUTH2'] = $langs->trans("UseOauth");
	}
	print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_IMAP_CONF_AUTH_TYPE").'</td><td>';
	print $form->selectarray('MULTISMTP_IMAP_CONF_AUTH_TYPE', $authtypes, getDolGlobalString('MULTISMTP_IMAP_CONF_AUTH_TYPE'), 1);
	print $form->textwithpicto('', $langs->trans("OauthNotAvailableForAllAndHadToBeCreatedBefore"));
	print '</td></tr>';

	// Restrict IMAP OAUTH service provider
	print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_IMAP_CONF_OAUTH_SERVICE").'</td><td>';
	print $form->selectarray('MULTISMTP_IMAP_CONF_OAUTH_SERVICE', $oauthservices, getDolGlobalString('MULTISMTP_IMAP_CONF_OAUTH_SERVICE'), 1);
	print '</td></tr>';
}

print '</table>';

print "\n".'<script type="text/javascript" language="javascript">';
print 'jQuery(document).ready(function () {
	function change_auth_method() {
		let type = jQuery("#MULTISMTP_IMAP_CONF_AUTH_TYPE").val();
		if (type != "XOAUTH2") {
			jQuery("#MULTISMTP_IMAP_CONF_OAUTH_SERVICE").closest("tr").hide();
		} else {
			jQuery("#MULTISMTP_IMAP_CONF_OAUTH_SERVICE").closest("tr").show();
		}
	}
	change_auth_method();
	jQuery("#MULTISMTP_IMAP_CONF_AUTH_TYPE").change(function() {
		change_auth_method();
	});
})';
print '</script>'."\n";

print '<br><br><div style="text-align:center"><input type="submit" value="'.$langs->trans('Save').'" class="button"></div>';

print '</form>';


/**
 * Cron / OAuth2
 */

print '<form method="post">';
print '<input type="hidden" name="token" value="' . newtoken() . '">';
print '<input type="hidden" name="action" value="set_cron_options">';

print '<br><div class="titre">'.$langs->trans('MultismtpCronOAuth2Title').'</div>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

// MULTISMTP_CRON_REFRESH_TOKEN_DAYS
print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_CRON_REFRESH_TOKEN_DAYS").'</td><td>';
print '<input type="number" name="MULTISMTP_CRON_REFRESH_TOKEN_DAYS" value="'.getDolGlobalInt('MULTISMTP_CRON_REFRESH_TOKEN_DAYS', 30).'" min="1" class="flat width50">';
print '</td></tr>';

print '</table>';

print '<br><div style="text-align:center"><input type="submit" value="'.$langs->trans('Save').'" class="button"></div>';

print '</form>';

llxFooter();
