<?php
	/**************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This file is part of Multismtp.
	* File added by InfraS (2026-08) : SMTP2GO tab (API key setup, SMTP users and subaccounts creation).
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
	**************************************************/

	/************************************************
	*	\file		./custom/multismtp/admin/smtp2go.php
	*	\ingroup	multismtp
	*	\brief		Page to setup the SMTP2GO integration for the Multismtp module
	************************************************/

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

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	dol_include_once('/multismtp/lib/multismtp.php');
	dol_include_once('/multismtp/lib/smtp2go.lib.php');
	dol_include_once('/multismtp/class/multismtp_smtp2go.class.php');

	// Access control *******************************
	if (!$user->admin) {
		accessforbidden();
	}

	// Translations *********************************
	$langs->loadLangs(array('admin', 'users', 'multismtp@multismtp'));

	// Actions **************************************
	$form						= new Form($db);
	$action						= GETPOST('action', 'aZ09');
	$open_smtp_user_form		= false;	// Re-open the creation forms after a submit error
	$open_edit_smtp_user_form	= false;
	// $open_my_smtp_user_form hors périmètre : formulaire "My subaccount" désactivé	// InfraS change
	$smtp2go_rate_limits		= ['01:00:00' => $langs->trans('Smtp2goPerHour'), '1 day' => $langs->trans('Smtp2goPerDays'),
									'7 days' => $langs->trans('Smtp2goPerWeeks'), '30 days' => $langs->trans('Smtp2goPerMonths')];	// Rate limit periods allowed by the SMTP2GO API (key = API value)

	// Load variable **************************************
	$smtp_username				= GETPOST('smtp2go_username', 'alphanohtml');
	$smtp_password				= GETPOST('smtp2go_password', 'password');
	$smtp_description			= GETPOST('smtp2go_description', 'alphanohtml');
	$ratelimit_period			= GETPOST('smtp2go_custom_ratelimit_period', 'alphanohtml');
	$ratelimit_value			= GETPOSTINT('smtp2go_custom_ratelimit_value');
	$ip_pool					= GETPOSTINT('smtp2go_ip_pool');
	$feedback_domain			= GETPOST('smtp2go_feedback_domain', 'alphanohtml');
	$feedback_html				= GETPOST('smtp2go_feedback_html', 'restricthtml');
	$feedback_text				= GETPOST('smtp2go_feedback_text', 'alphanohtml');
	$audit_email				= GETPOST('smtp2go_audit_email', 'email');
	$bounce_notifications		= GETPOST('smtp2go_bounce_notifications', 'alphanohtml');
	$smtp2go_edit_target		= GETPOST('smtp2go_edit_target', 'alphanohtml');
	$edit_ratelimit_period		= GETPOST('smtp2go_edit_custom_ratelimit_period', 'alphanohtml');
	$smtp2go_remove_user		= GETPOST('smtp2go_remove_user', 'alphanohtml');
	$smtp_open_tracking			= GETPOSTINT('smtp2go_open_tracking');
	$smtp_click_tracking		= GETPOSTINT('smtp2go_click_tracking');
	$smtp_archive				= GETPOSTINT('smtp2go_archive');
	$smtp_custom_ratelimit		= GETPOSTINT('smtp2go_custom_ratelimit');
	$smtp_enforce_2fa			= GETPOSTINT('smtp2go_enforce_2fa');
	$smtp_enable_sms			= GETPOSTINT('smtp2go_enable_sms');
	$smtp_sms_limit				= GETPOSTINT('smtp2go_sms_limit');
	$smtp_feedback_enabled		= GETPOSTINT('smtp2go_feedback_enabled');
	$smtp2go_add_user_id		= GETPOSTINT('smtp2go_add_user_id');
	$smtp2go_edit_user_id		= GETPOSTINT('smtp2go_edit_user_id');
	$checked					= '<i class="fa-regular fa-square-check"></i>';
	$unchecked					= '<i class="fa-regular fa-square"></i>';

	// Save setup values in llx_const table if form submitted
	$smtp2go_allowed_constants	= array('MULTISMTP_SMTP2GO_API_KEY', 'MULTISMTP_SMTP2GO_STATS');
	if (preg_match('/^set_(.*)/', $action, $reg) && in_array($reg[1], $smtp2go_allowed_constants)) {
		$confkey	= $reg[1];
		$constvalue	= GETPOST($confkey);
		dolibarr_set_const($db, $confkey, $constvalue, 'chaine', 0, '', $conf->entity);
		setEventMessage($langs->trans('SetupSaved'));
		header('Location: '.$_SERVER['PHP_SELF']);	// avoid replaying the action on page refresh
		exit;
	}
	if ($action == 'add_smtp_user') {
		// Pre-check the password strength BEFORE calling the API : SMTP2GO rejects
		$smtp_password_bits	= multismtp_smtp2go_password_entropy_bits($smtp_password);
		$smtp_password_classes	= multismtp_smtp2go_password_classes_count($smtp_password);
		if (dol_strlen($smtp_username) < 5 || dol_strlen($smtp_username) > 100) {
			setEventMessage($langs->trans('Smtp2goErrorUsernameLength'), 'errors');
			$open_smtp_user_form	= true;
		} elseif (empty($smtp_password)) {
			setEventMessage($langs->trans('Smtp2goErrorPasswordRequired'), 'errors');
			$open_smtp_user_form	= true;
		} elseif ($smtp_password_bits < MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS || $smtp_password_classes < MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES) {
			setEventMessage($langs->trans('Smtp2goErrorPasswordInvalid', round($smtp_password_bits), MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS, MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES), 'errors');
			$open_smtp_user_form	= true;
			
		} elseif (multismtp_smtp2go_password_is_common($smtp_password)) {
			setEventMessage($langs->trans('Smtp2goErrorPasswordCommon'), 'errors');
			$open_smtp_user_form	= true;
		} else {
			$params	= array('username' => $smtp_username);
			if (!empty($smtp_password)) {
				$params['email_password']	= $smtp_password;
			}
			if (!empty($smtp_description)) {
				$params['description']	= $smtp_description;
			}
			if (GETPOSTINT('smtp2go_open_tracking')) {
				$params['open_tracking_enabled']	= true;
			}
			if (GETPOSTINT('smtp2go_click_tracking')) {
				$params['click_tracking_enabled']	= true;
			}
			if (GETPOSTINT('smtp2go_archive')) {
				$params['archive_enabled']	= true;
			}
			if (GETPOSTINT('smtp2go_custom_ratelimit')) {
				$params['custom_ratelimit']	= true;
				if ($ratelimit_value > 0) {
					$params['custom_ratelimit_value']	= $ratelimit_value;
				}
				if (!empty($ratelimit_period)) {
					$params['custom_ratelimit_period']	= $ratelimit_period;
				}
			}
			if (GETPOSTINT('smtp2go_enforce_2fa')) {
				$params['enforce_2fa']	= true;
			}
			if (GETPOSTINT('smtp2go_enable_sms')) {
				$params['enable_sms']	= true;
				$sms_limit	= GETPOSTINT('smtp2go_sms_limit');
				if ($sms_limit > 0) {
					$params['sms_limit']	= $sms_limit;
				}
			}
			if ($ip_pool > 0) {
				$params['ip_pool']	= $ip_pool;
			}
			if (GETPOSTINT('smtp2go_feedback_enabled')) {
				$params['feedback_enabled']	= true;
				if (!empty($feedback_domain)) {
					$params['feedback_domain']	= $feedback_domain;
				}
				if (!empty($feedback_html)) {
					$params['feedback_html']	= $feedback_html;
				}
				if (!empty($feedback_text)) {
					$params['feedback_text']	= $feedback_text;
				}
			}
			if (!empty($audit_email)) {
				$params['audit_email']	= $audit_email;
			}
			if (!empty($bounce_notifications) && $bounce_notifications != 'from') {	// 'from' is the API default
				$params['bounce_notifications']	= $bounce_notifications;
			}
			$result	= multismtp_smtp2go_request('users/smtp/add', $params);
			if ($result['success']) {
				// Mirror locally what was sent to the API : SMTP2GO never returns the password back afterwards
				$object				= new MultiSMTP_Smtp2go($db);
				$object->fillFromApiData($params);
				$fk_user_to_link	= $smtp2go_add_user_id;
				$object->fk_user	= ($fk_user_to_link > 0) ? $fk_user_to_link : null;
				$object->password	= dolEncrypt($smtp_password);	// The SMTP2GO API never returns the password back : encrypt it before saving so we never store it in clear text
				$resultlocal		= $object->create($user);
				if ($resultlocal <= 0) {
					dol_syslog('Failed to mirror SMTP2GO user "'.$smtp_username.'" locally: '.$object->error, LOG_ERR);
				}
				multismtp_smtp2go_sync_native_smtp_credentials($fk_user_to_link, $smtp_username, $smtp_password);
				multismtp_smtp2go_cache_clear_users('');
				setEventMessage($langs->trans('Smtp2goSmtpUserCreated', $smtp_username));
				header('Location: '.$_SERVER['PHP_SELF']);	// avoid replaying the action on page refresh
				exit;
			} else {
				setEventMessage($langs->trans('Smtp2goApiError', $result['error']), 'errors');
				$open_smtp_user_form	= true;
			}
		}
	}
	if ($action == 'edit_smtp_user') {
		$smtp_password_bits	= multismtp_smtp2go_password_entropy_bits($smtp_password);
		$smtp_password_classes	= multismtp_smtp2go_password_classes_count($smtp_password);
		if (empty($smtp2go_edit_target)) {
			setEventMessage($langs->trans('Smtp2goErrorEditTargetMissing'), 'errors');
		} elseif (!empty($smtp_password) && ($smtp_password_bits < MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS || $smtp_password_classes < MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES)) {	// enforce a minimum character-class diversity, not just entropy
			setEventMessage($langs->trans('Smtp2goErrorPasswordInvalid', round($smtp_password_bits), MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS, MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES), 'errors');
			$open_edit_smtp_user_form	= true;
		} elseif (!empty($smtp_password) && multismtp_smtp2go_password_is_common($smtp_password)) {
			setEventMessage($langs->trans('Smtp2goErrorPasswordCommon'), 'errors');
			$open_edit_smtp_user_form	= true;
		} else {
			$params	= array('username' => $smtp2go_edit_target);
			if (!empty($smtp_password)) {
				$params['email_password']	= $smtp_password;
			}
			if (!empty($smtp_description)) {
				$params['description']	= $smtp_description;
			}
			if (GETPOSTINT('smtp2go_open_tracking')) {
				$params['open_tracking_enabled']	= true;
			}
			if (GETPOSTINT('smtp2go_click_tracking')) {
				$params['click_tracking_enabled']	= true;
			}
			if (GETPOSTINT('smtp2go_archive')) {
				$params['archive_enabled']	= true;
			}
			if (GETPOSTINT('smtp2go_custom_ratelimit')) {
				$params['custom_ratelimit']	= true;
				if ($ratelimit_value > 0) {
					$params['custom_ratelimit_value']	= $ratelimit_value;
				}
				if (!empty($edit_ratelimit_period)) {
					$params['custom_ratelimit_period']	= $edit_ratelimit_period;
				}
			}
			if ($ip_pool > 0) {
				$params['ip_pool']	= $ip_pool;
			}
			if (GETPOSTINT('smtp2go_feedback_enabled')) {
				$params['feedback_enabled']	= true;
				if (!empty($feedback_domain)) {
					$params['feedback_domain']	= $feedback_domain;
				}
				if (!empty($feedback_html)) {
					$params['feedback_html']	= $feedback_html;
				}
				if (!empty($feedback_text)) {
					$params['feedback_text']	= $feedback_text;
				}
			}
			if (!empty($audit_email)) {
				$params['audit_email']	= $audit_email;
			}
			if (!empty($bounce_notifications) && $bounce_notifications != 'from') {	// 'from' is the API default
				$params['bounce_notifications']	= $bounce_notifications;
			}
			$result	= multismtp_smtp2go_request('users/smtp/edit', $params);
			if ($result['success']) {
				// Mirror the change locally (create the local record if it did not exist yet)
				$object				= new MultiSMTP_Smtp2go($db);
				$resultfetch		= $object->fetch(0, $smtp2go_edit_target);
				$object->fillFromApiData($params);
				$object->fk_user	= ($smtp2go_edit_user_id > 0) ? $smtp2go_edit_user_id : null;
				$smtp_plaintext_for_sync	= !empty($smtp_password) ? $smtp_password : $object->password;
				// A blank password field means "keep the current password" (see Smtp2goEditPasswordHelp) ; fetch() above already decrypted the existing password into $object->password in clear
				if (!empty($smtp_password)) {
					$object->password	= dolEncrypt($smtp_password);		// a new password was provided : encrypt and store it
				} elseif (!empty($object->password)) {
					$object->password	= dolEncrypt($object->password);	// keep the existing password : re-encrypt the plaintext fetch() just decrypted
				}
				$resultlocal		= ($resultfetch > 0) ? $object->update($user) : $object->create($user);
				if ($resultlocal <= 0) {
					dol_syslog('Failed to mirror SMTP2GO user "'.$smtp2go_edit_target.'" locally: '.$object->error, LOG_ERR);
				}
				multismtp_smtp2go_sync_native_smtp_credentials($object->fk_user, $smtp2go_edit_target, $smtp_plaintext_for_sync);
				multismtp_smtp2go_cache_clear_users('');	// show the change immediately instead of waiting for MULTISMTP_SMTP2GO_CACHE_TTL to elapse
				setEventMessage($langs->trans('Smtp2goSmtpUserUpdated', $smtp2go_edit_target));
				header('Location: '.$_SERVER['PHP_SELF']);	// avoid replaying the action on page refresh
				exit;
			} else {
				setEventMessage($langs->trans('Smtp2goApiError', $result['error']), 'errors');
				$open_edit_smtp_user_form	= true;
			}
		}
	}
	if ($action == 'confirm_remove_smtp_user' && GETPOST('confirm') == 'yes') {
		if (empty($smtp2go_remove_user)) {
			setEventMessage($langs->trans('Smtp2goErrorRemoveTargetMissing'), 'errors');
		} else {
			$result	= multismtp_smtp2go_request('users/smtp/remove', array('username' => $smtp2go_remove_user));
			if ($result['success']) {
				// Remove the local mirror too, if any
				$object	= new MultiSMTP_Smtp2go($db);
				if ($object->fetch(0, $smtp2go_remove_user) > 0) {
					$fk_user_unlinked	= $object->fk_user;	// captured before delete() to clear the native SMTP credentials mirrored on that user, if any
					$resultlocal = $object->delete($user);
					if ($resultlocal <= 0) {
						dol_syslog('Failed to remove local mirror of SMTP2GO user "'.$smtp2go_remove_user.'": '.$object->error, LOG_ERR);
					}
					multismtp_smtp2go_sync_native_smtp_credentials($fk_user_unlinked, '', '');	// the SMTP2GO account is gone, clear the identifier/password it had mirrored into llx_user2smtp
				}
				multismtp_smtp2go_cache_clear_users('');	// show the removal immediately instead of waiting for MULTISMTP_SMTP2GO_CACHE_TTL to elapse
				setEventMessage($langs->trans('Smtp2goSmtpUserRemoved', $smtp2go_remove_user));
			} else {
				setEventMessage($langs->trans('Smtp2goApiError', $result['error']), 'errors');
			}
		}
		header('Location: '.$_SERVER['PHP_SELF']);	// avoid replaying the action on page refresh
		exit;
	}

	// View ****************************************
	$wikihelp		= 'EN:Multismtp_En|FR:Multismtp_Fr|ES:Multismtp_Es';
	llxHeader('', $langs->trans("MultismtpSetup"), $wikihelp);
	$smtp2go_password_langs	= array('Smtp2goPasswordGenerate'			=> $langs->trans('Smtp2goPasswordGenerate'),
										'Smtp2goPasswordMissLower'		=> $langs->trans('Smtp2goPasswordMissLower'),
										'Smtp2goPasswordMissUpper'		=> $langs->trans('Smtp2goPasswordMissUpper'),
										'Smtp2goPasswordMissDigit'		=> $langs->trans('Smtp2goPasswordMissDigit'),
										'Smtp2goPasswordMissSpecial'	=> $langs->trans('Smtp2goPasswordMissSpecial'),
										'Smtp2goPasswordBitsLabel'		=> $langs->trans('Smtp2goPasswordBitsLabel'),
										'Smtp2goPasswordStrongEnough'	=> $langs->trans('Smtp2goPasswordStrongEnough'),
										'Smtp2goPasswordTooWeak'		=> $langs->trans('Smtp2goPasswordTooWeak'),
										'Smtp2goPasswordAddHint'		=> $langs->trans('Smtp2goPasswordAddHint', '%s'),
										'Smtp2goPasswordCharsNeeded'	=> $langs->trans('Smtp2goPasswordCharsNeeded', '%s'),
										'Smtp2goPasswordAnd'			=> $langs->trans('Smtp2goPasswordAnd'),
										'Smtp2goPasswordWeakForSmtp2go'	=> $langs->trans('Smtp2goPasswordWeakForSmtp2go', '%s'));
	print '<script>var Smtp2goPasswordLangs = '.json_encode($smtp2go_password_langs).';</script>'."\n";
	print '<script>var Smtp2goPasswordConfig = '.json_encode(array('minBits' => MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS, 'minClasses' => MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES)).';</script>'."\n";
	print '<script src="'.dol_buildpath('/multismtp/js/smtp2go_password_strength.js', 1).'"></script>'."\n";
	$linkback		= '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans('BackToModuleList').'</a>';
	print load_fiche_titre($langs->trans("MultismtpSetup"), $linkback, 'title_setup');

	$apikey_defined	= getDolGlobalString('MULTISMTP_SMTP2GO_API_KEY') != '';
	$head			= multismtp_admin_prepare_head();
	print dol_get_fiche_head($head, 'smtp2go', $langs->trans('Module402002Name'), -1, 'opendsi@multismtp');
	print '<p>'.$langs->trans('Smtp2goDescription').'</p>';

	if ($action == 'remove_smtp_user' && !empty($smtp2go_remove_user)) {
		print $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?smtp2go_remove_user='.urlencode($smtp2go_remove_user), $langs->trans('Smtp2goConfirmRemoveTitle'), $langs->trans('Smtp2goConfirmRemoveQuestion', $smtp2go_remove_user), 'confirm_remove_smtp_user', '', '', 1);
	}
	// API ****************************************
	print '<form method="post" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">
				<input type="hidden" name="token" value="'.newToken().'">
				<input type="hidden" name="action" value="set_MULTISMTP_SMTP2GO_API_KEY">
				<br><div class="titre">'.$langs->trans('Smtp2goApiTitle').'</div>';

	print '		<table class="noborder centpercent">
					<tr class="liste_titre"><td>'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>
					<tr class="oddeven">
						<td>'.$langs->trans('MULTISMTP_SMTP2GO_API_KEY').'</td>
						<td><input type="password" id="MULTISMTP_SMTP2GO_API_KEY" name="MULTISMTP_SMTP2GO_API_KEY" value="'.dol_escape_htmltag(getDolGlobalString('MULTISMTP_SMTP2GO_API_KEY')).'" class="flat minwidth300" autocomplete="off">	<!-- InfraS change -->
							<span class="fa fa-eye paddingleft paddingright" onclick="newtype = (jQuery(\'#MULTISMTP_SMTP2GO_API_KEY\').attr(\'type\') == \'text\' ? \'password\' : \'text\'); jQuery(\'#MULTISMTP_SMTP2GO_API_KEY\').attr(\'type\', newtype);"></span>	<!-- InfraS add --></td>
					</tr>
				</table>
				<br><div style="text-align:center"><input type="submit" value="'.$langs->trans('Save').'" class="button"></div>';
	print '</form>';

	if (!$apikey_defined) {
		print info_admin($langs->trans('Smtp2goErrorNoApiKey'));
	} else {
		$smtp_users_result		= multismtp_smtp2go_get_smtp_users('');	// Get SMTP users from SMTP2GO API
		$smtp_users_by_username	= array();								// Map used to prefill the edit form client-side (JS)
		$smtp2go_local			= new MultiSMTP_Smtp2go($db);
		$smtp2go_userlinks		= $smtp2go_local->fetchAllUserLinks();	// Link with a Dolibarr user is only known locally : the API never returns it
		if (!is_array($smtp2go_userlinks)) {
			$smtp2go_userlinks	= array();
		}
		$morehtmlright			= '<a href="#" id="toggle_add_smtp_user" class=" btnTitle classfortooltip" title="'.dol_escape_htmltag($langs->trans('Smtp2goAddSmtpUser')).'"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a>';
		print '<br>'.multismtp_smtp2go_title($langs->trans('Smtp2goSmtpUsersTitle'), $morehtmlright, '', 0, '', 'titre-multismtp');
		print '<p>'.$langs->trans('Smtp2goSmtpUsersDesc').'</p>';
		if (!$smtp_users_result['success']) {
			print info_admin($langs->trans('Smtp2goApiError', $smtp_users_result['error']), 0, 0, 'error');
		} else {
			print '<table class="noborder centpercent">
						<tr class="liste_titre">
							<td>'.$langs->trans('Login').'</td>
							<td>'.$langs->trans('Description').'</td>
							<td>'.$langs->trans('Smtp2goLinkedToDolibarrUser').'</td>
							<td>'.$langs->trans('Smtp2goCustomRatelimit').'</td>
							<td>'.$langs->trans('Smtp2goArchiving').'</td>
							<td>'.$langs->trans('Smtp2goOpenTracking').'</td>
							<td>'.$langs->trans('Smtp2goClickTracking').'</td>
							<td>'.$langs->trans('Status').'</td>
							<td class="right"></td>
						</tr>';
			if (empty($smtp_users_result['users'])) {
				print '	<tr class="oddeven"><td colspan="9"><span class="opacitymedium">'.$langs->trans('None').'</span></td></tr>';
			} else {
				$smtp2go_userlinks_names	= array();	// fk_user => rendered link, built once to avoid fetching the same Dolibarr user twice
				foreach (array_unique($smtp2go_userlinks) as $fk_user_to_resolve) {
					$linkeduser	= new User($db);
					if ($linkeduser->fetch($fk_user_to_resolve) > 0) {
						$smtp2go_userlinks_names[$fk_user_to_resolve]	= $linkeduser->getNomUrl(0);
					}
				}
				foreach ($smtp_users_result['users'] as $smtp2go_user) {
					if (!is_array($smtp2go_user) || empty($smtp2go_user['username'])) {
						continue;
					}
					$fk_user_linked	= $smtp2go_userlinks[$smtp2go_user['username']] ?? 0;
					$smtp_users_by_username[$smtp2go_user['username']]	= array('description'				=> $smtp2go_user['description'] ?? '',
																				'custom_ratelimit'			=> !empty($smtp2go_user['custom_ratelimit']),
																				'custom_ratelimit_value'	=> $smtp2go_user['custom_ratelimit_value'] ?? '',
																				'custom_ratelimit_period'	=> $smtp2go_user['custom_ratelimit_period'] ?? '',
																				'ip_pool'					=> $smtp2go_user['ippool'] ?? '',
																				'feedback_enabled'			=> !empty($smtp2go_user['feedback_enabled']),
																				'feedback_domain'			=> $smtp2go_user['feedback_domain'] ?? '',
																				'feedback_html'				=> $smtp2go_user['feedback_html'] ?? '',
																				'feedback_text'				=> $smtp2go_user['feedback_text'] ?? '',
																				'open_tracking_enabled'		=> !empty($smtp2go_user['open_tracking_enabled']),
																				'click_tracking_enabled'	=> !empty($smtp2go_user['click_tracking_enabled']),
																				'archive_enabled'			=> !empty($smtp2go_user['archive_enabled']),
																				'audit_email'				=> $smtp2go_user['audit_email'] ?? '',
																				'bounce_notifications'		=> $smtp2go_user['bounce_notifications'] ?? 'from',
																				'fk_user'					=> $fk_user_linked
																			);
					print '<tr class="oddeven">
							<td>'.dol_escape_htmltag($smtp2go_user['username'] ?? '').'</td>
							<td>'.dol_escape_htmltag($smtp2go_user['description'] ?? '').'</td>
							<td>'.(!empty($fk_user_linked) ? ($smtp2go_userlinks_names[$fk_user_linked] ?? '') : '').'</td>
							<td>'.(!empty($smtp2go_user['custom_ratelimit']) ? dol_escape_htmltag($smtp2go_user['custom_ratelimit_value'].' '.$smtp2go_rate_limits[$smtp2go_user['custom_ratelimit_period']]) : $langs->trans('Smtp2goDefaultCustomRatelimit')).'</td>
							<td>'.(!empty($smtp2go_user['archive_enabled']) ? $checked : $unchecked).'</td>
							<td>'.(!empty($smtp2go_user['open_tracking_enabled']) ? $checked : $unchecked).'</td>
							<td>'.(!empty($smtp2go_user['click_tracking_enabled']) ? $checked : $unchecked).'</td>
							<td>'.dol_escape_htmltag($smtp2go_user['status'] ?? '').'</td>
							<td class="right">
								<a href="#" class="smtp2go-edit-user" data-username="'.dol_escape_htmltag($smtp2go_user['username']).'">'.img_edit().'</a>
								<a href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=remove_smtp_user&token='.newToken().'&smtp2go_remove_user='.urlencode($smtp2go_user['username']).'" title="'.dol_escape_htmltag($langs->trans('Delete')).'">'.img_delete().'</a>
							</td>
						</tr>';
				}
			}
			print '</table>';
		}
		include dol_buildpath('/multismtp/core/tpl/smtp2goUser_create.tpl.php');	// Creation form of a SMTP2GO user (main account)
		include dol_buildpath('/multismtp/core/tpl/smtp2goUser_edit.tpl.php');		// Edit form of a SMTP2GO user (main account)

		// Cycle usage & rates ****************************
		$email_stats_result	= multismtp_smtp2go_get_email_stats();
		print '<br>'.multismtp_smtp2go_title($langs->trans('Smtp2goStatsCycle'), '', '');
		if (!$email_stats_result['success']) {
			print info_admin($langs->trans('Smtp2goApiError', $email_stats_result['error']), 0, 0, 'error');
		} else {
			$stats				= $email_stats_result['stats'];
			$cycle_used			= (int) ($stats['cycle_used'] ?? 0);
			$cycle_max			= (int) ($stats['cycle_max'] ?? 0);
			$cycle_remaining	= (int) ($stats['cycle_remaining'] ?? 0);
			$cycle_end			= $stats['cycle_end'] ?? '';
			$cycle_percent		= ($cycle_max > 0) ? min(100, round($cycle_used / $cycle_max * 100, 2)) : 0;
			print '<table class="noborder centpercent">
						<tr class="liste_titre">
							<td>'.$langs->trans('Smtp2goStatsCycleUsage').'</td>
							<td>'.$langs->trans('Smtp2goStatsBouncePercent').'</td>
							<td>'.$langs->trans('Smtp2goStatsSpamPercent').'</td>
						</tr>
						<tr class="oddeven">
							<td>
								<div class="progress sm" title="'.$cycle_percent.'%">
									<div class="progress-bar progress-bar-info" style="width: '.$cycle_percent.'%"></div>
								</div>
								'.dol_escape_htmltag($cycle_used.' / '.$cycle_max).
								($cycle_remaining ? ' ('.dol_escape_htmltag($langs->trans('Smtp2goStatsCycleRemaining', $cycle_remaining)).')' : '').'
								<br><span class="opacitymedium">'.(!empty($cycle_end) ? dol_escape_htmltag($langs->trans('Smtp2goStatsCycleResetDate', dol_print_date(dol_stringtotime($cycle_end), 'day'))) : '').'</span>
							</td>
							<td>'.dol_escape_htmltag($stats['bounce_percent'] ?? '0').' %</td>
							<td>'.dol_escape_htmltag($stats['spam_percent'] ?? '0').' %</td>
						</tr>
					</table>';
		}

		// Email statistics ****************************
		print '<form method="post" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">
					<input type="hidden" name="token" value="'.newToken().'">
					<input type="hidden" name="action" value="set_MULTISMTP_SMTP2GO_STATS">
					<br><div class="titre">'.$langs->trans('Smtp2goStatsTitle').'</div>';

		print '		<table class="noborder centpercent">
						<tr class="liste_titre"><td>'.$langs->trans('Parameter').'</td><td>'.$langs->trans('Value').'</td></tr>
						<tr class="oddeven">
							<td>'.$langs->trans('MULTISMTP_SMTP2GO_STATS').'</td>
							<td><input type="number" name="MULTISMTP_SMTP2GO_STATS" value="'.dol_escape_htmltag(getDolGlobalString('MULTISMTP_SMTP2GO_STATS', 7)).'" class="flat minwidth300" autocomplete="off"></td>
						</tr>
					</table>
					<br><div style="text-align:center"><input type="submit" value="'.$langs->trans('Save').'" class="button"></div>';
		print '</form>';
		print '<div class="div-table-responsive">
					<table class="noborder centpercent">';
						$nb_days			= getDolGlobalInt('MULTISMTP_SMTP2GO_STATS', 7);
						$activity_result	= multismtp_smtp2go_get_daily_activity($nb_days);	// Daily activity chart, like the SMTP2GO dashboard sending graph ***
						if (!$activity_result['success']) {
							print info_admin($langs->trans('Smtp2goApiError', $activity_result['error']), 0, 0, 'error');
						} else {
							if (!empty($activity_result['truncated'])) {
								print info_admin($langs->trans('Smtp2goActivityChartTruncated'));
							}
							$graph_datas = array();
							foreach ($activity_result['days'] as $daykey => $counts) {
								$graph_datas[] = array(dol_print_date(dol_stringtotime($daykey), 'day'), $counts['sent'], $counts['opens'], $counts['clicks'], $counts['bounces']);
							}
							$dolgraph = new DolGraph('chart');
							$dolgraph->SetData($graph_datas);
							$dolgraph->SetLegend(array($langs->trans('Smtp2goStatsEmailCount'), $langs->trans('Smtp2goStatsOpens'), $langs->trans('Smtp2goStatsClicks'), $langs->trans('Smtp2goStatsBounces')));
							$dolgraph->SetMaxValue($dolgraph->GetCeilMaxValue() < 0 ? 0 : $dolgraph->GetCeilMaxValue());
							$dolgraph->SetMinValue(0);
							$dolgraph->SetWidth('100%');
							$dolgraph->SetHeight(300);
							$dolgraph->SetTitle($langs->trans('Smtp2goActivityChartTitle', $nb_days));
							$dolgraph->SetType(array('lines', 'lines', 'lines', 'lines'));
							$dolgraph->draw('smtp2go_activity_chart');
							print $dolgraph->show();
						}
		print '		</table>
				</div>';
	}
	print dol_get_fiche_end();
	llxFooter();
	$db->close();