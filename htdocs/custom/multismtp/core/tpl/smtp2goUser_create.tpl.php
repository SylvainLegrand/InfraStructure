<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*
	* SPDX-License-Identifier: GPL-3.0-or-later
	* This file is part of Dolibarr module Infrastructure
	**************************************************/

	/************************************************
	* 	\file		./custom/multismtp/core/tpl/smtp2goUser_create.tpl.php
	* 	\ingroup	multismtp
	* 	\brief		Template for creating a new SMTP2GO user (main account)
	************************************************/

	// Protection contre l'appel direct
	if (empty($conf) || ! is_object($conf)) {
		print "Error, template page can't be called as URL";
		exit;
	}

	// Libraries ************************************
	dol_include_once('/multismtp/lib/multismtp.php');

	// smart defaults on a fresh create form only (not when redisplaying after a validation error, where the admin's own choices must be preserved)
	$smtp2go_is_fresh_create_form	= ($action != 'add_smtp_user');
	$smtp2go_useremails	= array();
	$sql	= "SELECT rowid, email FROM ".MAIN_DB_PREFIX."user WHERE entity IN (".getEntity('user').") AND email IS NOT NULL AND email != ''";
	$resql	= $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$smtp2go_useremails[$obj->rowid]	= $obj->email;
		}
		$db->free($resql);
	}
	print '<script>var Smtp2goUserEmails = '.json_encode($smtp2go_useremails).';</script>'."\n";

	// View *****************************************
	print '	<div id="add_smtp_user_form"'.($open_smtp_user_form ? '' : ' class="hideobject"').'>';
	print '		<form method="post" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">
					<input type="hidden" name="token" value="'.newToken().'">
					<input type="hidden" name="action" value="add_smtp_user"><br>';

	print '			<table class="noborder" width="100%">
						<tr class="liste_titre"><td colspan="2">'.$langs->trans('Smtp2goAddSmtpUser').'</td></tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goToLinkWithUser').' '.$form->textwithpicto('', $langs->trans('Smtp2goToLinkWithUserHelp')).'</td>
							<td>'.$form->select_dolusers($smtp2go_add_user_id, 'smtp2go_add_user_id', 1).'</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Login').'</td>
							<td><input type="text" name="smtp2go_username" value="'.dol_escape_htmltag($smtp_username).'" class="flat minwidth300" minlength="5" maxlength="100" required> '.$form->textwithpicto('', $langs->trans('Smtp2goErrorUsernameLength')).'</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Password').'</td>
							<td><input type="password" id="smtp2go_add_password" name="smtp2go_password" value="'.dol_escape_htmltag($smtp_password).'" class="flat minwidth300" autocomplete="new-password" required>
								<span class="fa fa-eye paddingleft paddingright" onclick="newtype = (jQuery(\'#smtp2go_add_password\').attr(\'type\') == \'text\' ? \'password\' : \'text\'); jQuery(\'#smtp2go_add_password\').attr(\'type\', newtype);"></span>
								'.$form->textwithpicto('', $langs->trans('Smtp2goPasswordHelp', MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES, MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS)).'</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Description').'</td>
							<td><input type="text" name="smtp2go_description" value="'.dol_escape_htmltag($smtp_description).'" class="flat minwidth300"></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goCustomRatelimit').' '.$form->textwithpicto('', $langs->trans('Smtp2goCustomRatelimitHelp')).'</td>
							<td><input type="checkbox" id="smtp2go_custom_ratelimit" name="smtp2go_custom_ratelimit" value="1"'.($smtp_custom_ratelimit ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven smtp2go_ratelimit_row">
							<td>'.$langs->trans('Smtp2goRatelimitValue').'</td>
							<td>
								<input type="number" name="smtp2go_custom_ratelimit_value" value="'.($ratelimit_value > 0 ? (int) $ratelimit_value : '').'" min="1" class="flat width100"> ';
								print $form->selectarray('smtp2go_custom_ratelimit_period', $smtp2go_rate_limits, (!empty($ratelimit_period) ? $ratelimit_period : '1 hour'), 0).'
							</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goIpPool').' '.$form->textwithpicto('', $langs->trans('Smtp2goIpPoolHelp')).'</td>
							<td><input type="number" name="smtp2go_ip_pool" value="'.($ip_pool > 0 ? (int) $ip_pool : '').'" min="1" class="flat width100"></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goFeedbackEnabled').' '.$form->textwithpicto('', $langs->trans('Smtp2goFeedbackEnabledHelp')).'</td>
							<td><input type="checkbox" id="smtp2go_feedback_enabled" name="smtp2go_feedback_enabled" value="1"'.($smtp_feedback_enabled ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven smtp2go_feedback_row">
							<td>'.$langs->trans('Smtp2goFeedbackDomain').'</td>
							<td><input type="text" name="smtp2go_feedback_domain" value="'.dol_escape_htmltag(!empty($feedback_domain) ? $feedback_domain : 'default').'" class="flat minwidth200"></td>
						</tr>
						<tr class="oddeven smtp2go_feedback_row">
							<td>'.$langs->trans('Smtp2goFeedbackHtml').'</td>
							<td><textarea name="smtp2go_feedback_html" rows="3" class="flat quatrevingtpercent">'.dol_escape_htmltag($feedback_html).'</textarea></td>
						</tr>
						<tr class="oddeven smtp2go_feedback_row">
							<td>'.$langs->trans('Smtp2goFeedbackText').'</td>
							<td><textarea name="smtp2go_feedback_text" rows="3" class="flat quatrevingtpercent">'.dol_escape_htmltag($feedback_text).'</textarea></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goOpenTracking').' '.$form->textwithpicto('', $langs->trans('Smtp2goOpenTrackingHelp')).'</td>
							<td><input type="checkbox" name="smtp2go_open_tracking" value="1"'.(($smtp2go_is_fresh_create_form || $smtp_open_tracking) ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goClickTracking').' '.$form->textwithpicto('', $langs->trans('Smtp2goClickTrackingHelp')).'</td>
							<td><input type="checkbox" name="smtp2go_click_tracking" value="1"'.(($smtp2go_is_fresh_create_form || $smtp_click_tracking) ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goArchive').' '.$form->textwithpicto('', $langs->trans('Smtp2goArchiveHelp')).'</td>
							<td><input type="checkbox" name="smtp2go_archive" value="1"'.(($smtp2go_is_fresh_create_form || $smtp_archive) ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goAuditEmail').' '.$form->textwithpicto('', $langs->trans('Smtp2goAuditEmailHelp')).'</td>
							<td><input type="email" id="smtp2go_audit_email" name="smtp2go_audit_email" value="'.dol_escape_htmltag($audit_email).'" class="flat minwidth300"></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goBounceNotif').' '.$form->textwithpicto('', $langs->trans('Smtp2goBounceNotifHelp')).'</td>
							<td><input type="text" name="smtp2go_bounce_notifications" value="'.dol_escape_htmltag(!empty($bounce_notifications) ? $bounce_notifications : (getDolGlobalString('MAIN_MAIL_ERRORS_TO') ?: 'from')).'" class="flat minwidth200"></td>
						</tr>
					</table><br>
					<div style="text-align:center">
						<input type="submit" value="'.$langs->trans('Create').'" class="button">
						<input type="button" id="cancel_add_smtp_user" value="'.$langs->trans('Cancel').'" class="button button-cancel">
					</div>';
	print '		</form>';
	print '	</div>';

	print '<script type="text/javascript">
				jQuery(document).ready(function() {
					function smtp2go_toggle_dependent_rows() {
						jQuery(".smtp2go_ratelimit_row").toggle(jQuery("#smtp2go_custom_ratelimit").prop("checked"));
						jQuery(".smtp2go_feedback_row").toggle(jQuery("#smtp2go_feedback_enabled").prop("checked"));
					}
					smtp2go_toggle_dependent_rows();
					jQuery("#smtp2go_custom_ratelimit, #smtp2go_feedback_enabled").change(smtp2go_toggle_dependent_rows);
					// InfraS add begin : prefill the audit email with the linked Dolibarr user own email address
					function smtp2go_fill_audit_email() {
						var uid = jQuery("#smtp2go_add_user_id").val();
						var email = (typeof Smtp2goUserEmails !== "undefined" && Smtp2goUserEmails[uid]) ? Smtp2goUserEmails[uid] : "";
						if (email) {
							jQuery("#smtp2go_audit_email").val(email);
						}
					}
					smtp2go_fill_audit_email();	// also apply to the user already preselected by select_dolusers() on a fresh form (defaults to the current admin)
					jQuery("#smtp2go_add_user_id").change(smtp2go_fill_audit_email);
					// InfraS add end
					jQuery("#toggle_add_smtp_user").click(function(e) {
						e.preventDefault();
						jQuery("#add_smtp_user_form").toggle();
					});
					jQuery("#cancel_add_smtp_user").click(function(e) {
						e.preventDefault();
						jQuery("#add_smtp_user_form form")[0].reset();
						smtp2go_toggle_dependent_rows();
						jQuery("#add_smtp_user_form").hide();
					});
				});
			</script>';
