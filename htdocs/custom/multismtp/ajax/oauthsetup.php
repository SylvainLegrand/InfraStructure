<?php
/* Copyright (C) 2026 Open-DSI            <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/multismtp/ajax/oauthsetup.php
 * \brief   File to return oauth setup form
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
//if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

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

require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';
dol_include_once('/multismtp/lib/oauth.lib.php');
dol_include_once('/multismtp/ajax/oauthsetup.php');

$id			= GETPOST('id', 'int');
$type		= GETPOST('type', 'alphanohtml');
$provider	= GETPOST('provider', 'alphanohtml');

$langs->load('oauth');

/*
 * View
 */

//$outjson = array(
//    'content' => '', // Content
//    'error' => '',   // Error message
//);

$content = '';

if (empty($type) || empty($provider)) {
	$outjson = array(
		'content' => $content,
	);
} else {
	try {
		if ($id > 0) {
			$langs->loadLangs(array('users', 'members'));
			$fuser = new User($db);
			$result = $fuser->fetch($id);
			if ($result < 0) {
				$outjson = array(
					'error' => $fuser->errorsToString(),
				);
			} elseif ($result == 0) {
				$langs->load('errors');
				$outjson = array(
					'error' => $langs->transn('ErrorRecordNotFound'),
				);
			} elseif (checkUserAccessToObject($user, array('user'), $id, 'user&user', $user->socid > 0 && $user->rights->user->self->creer ? '' : 'user', '', 'rowid')
				&& ($user->admin || $user->rights->user->user->lire || $user->id == $id)
			) {
				$multismtp = new Multismtp($db, $conf);
				$multismtp->fetch($fuser);

				if ($type == 'smtp') {
					$credentials = $multismtp->getSmtpCredentials();
				} else {
					$credentials = $multismtp->getImapCredentials();
				}

				$supportedoauth2array = getSupportedOauth2Array();
				$keyforsupportedoauth2array = strtoupper($provider) . '_NAME';
				if (in_array($keyforsupportedoauth2array, array_keys($supportedoauth2array))) {
					$allow_change_server = ($type == 'imap' && empty($conf->global->MULTISMTP_IMAP_CONF_OAUTH_SERVICE)) || ($type == 'smtp' && !empty($conf->global->MULTISMTP_ALLOW_CHANGESERVER));
					$supportedoauth2info = $supportedoauth2array[$keyforsupportedoauth2array];

					$content .= '<table class="noborder centpercent">';

					// OAUTH provider infos
					$content .= '<tr class="liste_titre">';
					$content .= '<td colspan="2">';
					if (!empty($supportedoauth2info['urlforcredentials'])) {
						$content .= $langs->trans("OAUTH_URL_FOR_CREDENTIAL", $supportedoauth2info['urlforcredentials']);
					}
					$content .= '</td>';
					$content .= '</tr>';

					// Define $urlwithroot
					$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT; // This is to use external domain name found into config file
					//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
					$redirect_uri = dol_buildpath('/multismtp/core/oauth/', 3) . $supportedoauth2info['callbackfile'] . '_oauthcallback.php';
					//$redirect_uri = $urlwithroot . '/multismtp/core/modules/oauth/' . $supportedoauth2info['callbackfile'] . '_oauthcallback.php';
					$content .= '<tr class="oddeven value">';
					$content .= '<td class="titlefieldcreate">' . $langs->trans("UseTheFollowingUrlAsRedirectURI") . '</td>';
					$content .= '<td><input style="width: 80%" type="text" name="'.strtoupper($type) . '_OAUTH_URL_CALLBACK" id="'.$type . '_oauth_url_callback" value="' . $redirect_uri . '" disabled>';
					$content .= ajax_autoselect($type . '_oauth_url_callback');
					$content .= '</td>';
					$content .= '</tr>';

					// Api Url Authorize
					if ($keyforsupportedoauth2array == 'OAUTH_OTHER_NAME') {
						$content .= '<tr class="oddeven value">';
						$content .= '<td>' . $langs->trans("URLOfServiceForAuthorization") . '</td>';
						$content .= '<td>';
						if (!$allow_change_server) {
							$content .= $credentials['oauth_url_authorize'];
						} else {
							$content .= '<input style="width: 80%" type="text" name="' . strtoupper($type) . '_OAUTH_URL_AUTHORIZE" value="' . ($provider == $credentials['oauth_provider'] ? dol_escape_js($credentials['oauth_url_authorize'], 2) : '') . '" >';
						}
						$content .= '</td>';
						$content .= '</tr>';
					}

					// Api Id
					$content .= '<tr class="oddeven value">';
					$content .= '<td><label for="' . $type . '_oauth_id">' . $langs->trans("OAUTH_ID") . '</label></td>';
					$content .= '<td>';
					if (!$allow_change_server) {
						$content .= $credentials['oauth_id'];
					} else {
						$content .= '<input type="text" size="100" id="' . $type . '_oauth_id" name="' . strtoupper($type) . '_OAUTH_ID" value="' . ($provider == $credentials['oauth_provider'] ? dol_escape_js($credentials['oauth_id'], 2) : '') . '">';
					}
					$content .= '</td>';
					$content .= '</tr>';

					// Api Secret
					$content .= '<tr class="oddeven value">';
					$content .= '<td><label for="' . $type . '_oauth_secret">' . $langs->trans("OAUTH_SECRET") . '</label></td>';
					$content .= '<td>';
					if (!$allow_change_server) {
						$content .= !empty($credentials['oauth_secret']) ? '**********' : '';
					} else {
						$content .= '<input type="password" size="100" id="' . $type . '_oauth_secret" name="' . strtoupper($type) . '_OAUTH_SECRET" value="' . ($provider == $credentials['oauth_provider'] ? dol_escape_js($credentials['oauth_secret'], 2) : '') . '">';
					}
					$content .= '</td>';
					$content .= '</tr>';

					// Tenant
					if ($keyforsupportedoauth2array == 'OAUTH_MICROSOFT_NAME') {
						$content .= '<tr class="oddeven value">';
						$content .= '<td><label for="' . $type . '_oauth_tenant">' . $langs->trans("OAUTH_TENANT") . '</label></td>';
						$content .= '<td>';
						if (!$allow_change_server) {
							$content .= $credentials['oauth_tenant'];
						} else {
							$content .= '<input type="text" size="100" id="' . $type . '_oauth_tenant" name="' . strtoupper($type) . '_OAUTH_TENANT" value="' . ($provider == $credentials['oauth_provider'] ? dol_escape_js($credentials['oauth_tenant'], 2) : '') . '">';
						}
						$content .= '</td>';
						$content .= '</tr>';
					}

					// Api Scope
					$content .= '<tr class="oddeven value">';
					$content .= '<td>' . $langs->trans("Scopes") . '</td>';
					$content .= '<td>';
					if ($keyforsupportedoauth2array == 'OAUTH_OTHER_NAME') {
						if (!$allow_change_server) {
							$content .= $credentials['oauth_scope'];
						} else {
							$content .= '<input style="width: 80%" type"text" name="' . strtoupper($type) . '_OAUTH_SCOPE" value="' . ($provider == $credentials['oauth_provider'] ? dol_escape_js($credentials['oauth_scope'], 2) : '') . '" >';
						}
					} else {
						$availablescopes = array_flip(explode(',', $supportedoauth2info['availablescopes']));
						$currentscopes = ($provider == $credentials['oauth_provider'] ? explode(',', $credentials['oauth_scope']) : array());
						$scopestodispay = array();
						foreach ($availablescopes as $keyscope => $valscope) {
							if (in_array($keyscope, $currentscopes)) {
								$scopestodispay[$keyscope] = 1;
							} else {
								$scopestodispay[$keyscope] = 0;
							}
						}
						$disabled = !$allow_change_server ? ' disabled="disabled"' : '';
						foreach ($scopestodispay as $scope => $val) {
							$content .= '<input type="checkbox" id="' . $type . $scope . '_oauth_scope" name="' . strtoupper($type) . '_OAUTH_SCOPE[]" value="' . $scope . '"' . ($val ? ' checked' : '') . $disabled . '>';
							$content .= '<label style="margin-right: 10px" for="' . $type . $scope . '_oauth_scope">' . $scope . '</label>';
						}
					}
					$content .= '</td>';
					$content .= '</tr>';

					$content .= '</table>' . "\n";
				}

				$outjson = array(
					'content' => $content,
				);
			} else {
				$langs->load('errors');
				$outjson = array(
					'error' => $langs->trans('ErrorForbidden'),
				);
			}
		} else {
			$langs->load('errors');
			$outjson = array(
				'error' => $langs->trans('ErrorBadParameters'),
			);
		}
	} catch (Exception $e) {
		$outjson = array(
			'error' => $e->getMessage(),
		);
	}
}

header('Content-Type: application/json');
echo json_encode($outjson);

$db->close();
