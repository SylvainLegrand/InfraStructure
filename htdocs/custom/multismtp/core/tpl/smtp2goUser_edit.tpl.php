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
	* 	\file		./custom/multismtp/core/tpl/smtp2goUser_edit.tpl.php
	* 	\ingroup	multismtp
	* 	\brief		Template for editing an existing SMTP2GO user (main account)
	************************************************/

	// Protection contre l'appel direct
	if (empty($conf) || ! is_object($conf)) {
		print "Error, template page can't be called as URL";
		exit;
	}

	// Libraries ************************************
	dol_include_once('/multismtp/lib/multismtp.php');

	// View *****************************************
	print '	<div id = "add_edit_smtp_user_form"'.($open_edit_smtp_user_form ? '' : ' class = "hideobject"').'>';
	print '		<form method = "post" action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">
					<input type = "hidden" name = "token" value = "'.newToken().'">
					<input type = "hidden" name = "action" value = "edit_smtp_user">
					<input type = "hidden" id = "smtp2go_edit_target" name = "smtp2go_edit_target" value = "'.dol_escape_htmltag($smtp2go_edit_target).'"><br>
					<table class = "noborder centpercent">
						<tr class = "liste_titre">
							<td colspan = "2">'.$langs->trans('Smtp2goEditSmtpUser').'</td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Login').'</td>
							<td><input type = "text" id = "smtp2go_edit_username_display" value = "'.dol_escape_htmltag($smtp2go_edit_target).'" class = "flat minwidth300" disabled></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goToLinkWithUser').' '.$form->textwithpicto('', $langs->trans('Smtp2goToLinkWithUserHelp')).'</td>
							<td>'.$form->select_dolusers($smtp2go_edit_user_id, 'smtp2go_edit_user_id', 1).'</td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Password').'</td>
							<td><input type = "password" id = "smtp2go_edit_password" name = "smtp2go_password" value = "'.dol_escape_htmltag($smtp_password).'" class = "flat minwidth300" autocomplete="new-password">
								<span class="fa fa-eye paddingleft paddingright" onclick="newtype = (jQuery(\'#smtp2go_edit_password\').attr(\'type\') == \'text\' ? \'password\' : \'text\'); jQuery(\'#smtp2go_edit_password\').attr(\'type\', newtype);"></span>
								'.$form->textwithpicto('', $langs->trans('Smtp2goEditPasswordHelp', MULTISMTP_SMTP2GO_MIN_PASSWORD_CLASSES, MULTISMTP_SMTP2GO_MIN_PASSWORD_BITS)).'</td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Description').'</td>
							<td><input type = "text" id = "smtp2go_edit_description" name = "smtp2go_description" value = "'.dol_escape_htmltag($smtp_description).'" class = "flat minwidth300"></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goCustomRatelimit').' '.$form->textwithpicto('', $langs->trans('Smtp2goCustomRatelimitHelp')).'</td>
							<td><input type = "checkbox" id = "smtp2go_edit_ratelimit" name = "smtp2go_custom_ratelimit" value = "1"'.($smtp_custom_ratelimit ? ' checked' : '').'></td>
						</tr>
						<tr class = "oddeven smtp2go_edit_ratelimit_row">
							<td>'.$langs->trans('Smtp2goRatelimitValue').'</td>
							<td>
								<input type = "number" id = "smtp2go_edit_ratelimit_value" name = "smtp2go_custom_ratelimit_value" value = "'.($ratelimit_value > 0 ? (int) $ratelimit_value : '').'" min = "1" class = "flat width100"> ';
	print $form->selectarray('smtp2go_edit_custom_ratelimit_period', $smtp2go_rate_limits, (!empty($edit_ratelimit_period) ? $edit_ratelimit_period : '01:00:00'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth75', 0);
	print '					</td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goIpPool').' '.$form->textwithpicto('', $langs->trans('Smtp2goIpPoolHelp')).'</td>
							<td><input type = "number" id = "smtp2go_edit_ip_pool" name = "smtp2go_ip_pool" value = "'.($ip_pool > 0 ? (int) $ip_pool : '').'" min = "1" class = "flat width100"></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goFeedbackEnabled').' '.$form->textwithpicto('', $langs->trans('Smtp2goFeedbackEnabledHelp')).'</td>
							<td><input type = "checkbox" id = "smtp2go_edit_feedback_enabled" name = "smtp2go_feedback_enabled" value = "1"'.($smtp_feedback_enabled ? ' checked' : '').'></td>
						</tr>
						<tr class = "oddeven smtp2go_edit_feedback_row">
							<td>'.$langs->trans('Smtp2goFeedbackDomain').'</td>
							<td><input type = "text" id = "smtp2go_edit_feedback_domain" name = "smtp2go_feedback_domain" value = "'.dol_escape_htmltag(!empty($feedback_domain) ? $feedback_domain : 'default').'" class = "flat minwidth200"></td>
						</tr>
						<tr class = "oddeven smtp2go_edit_feedback_row">
							<td>'.$langs->trans('Smtp2goFeedbackHtml').'</td>
							<td><textarea id = "smtp2go_edit_feedback_html" name = "smtp2go_feedback_html" rows="3" class = "flat quatrevingtpercent">'.dol_escape_htmltag($feedback_html).'</textarea></td>
						</tr>
						<tr class = "oddeven smtp2go_edit_feedback_row">
							<td>'.$langs->trans('Smtp2goFeedbackText').'</td>
							<td><textarea id = "smtp2go_edit_feedback_text" name = "smtp2go_feedback_text" rows="3" class = "flat quatrevingtpercent">'.dol_escape_htmltag($feedback_text).'</textarea></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goOpenTracking').' '.$form->textwithpicto('', $langs->trans('Smtp2goOpenTrackingHelp')).'</td>
							<td><input type = "checkbox" id = "smtp2go_edit_open_tracking" name = "smtp2go_open_tracking" value = "1"'.($smtp_open_tracking ? ' checked' : '').'></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goClickTracking').' '.$form->textwithpicto('', $langs->trans('Smtp2goClickTrackingHelp')).'</td>
							<td><input type = "checkbox" id = "smtp2go_edit_click_tracking" name = "smtp2go_click_tracking" value = "1"'.($smtp_click_tracking ? ' checked' : '').'></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goArchive').' '.$form->textwithpicto('', $langs->trans('Smtp2goArchiveHelp')).'</td>
							<td><input type = "checkbox" id = "smtp2go_edit_archive" name = "smtp2go_archive" value = "1"'.($smtp_archive ? ' checked' : '').'></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goAuditEmail').' '.$form->textwithpicto('', $langs->trans('Smtp2goAuditEmailHelp')).'</td>
							<td><input type = "email" id = "smtp2go_edit_audit_email" name = "smtp2go_audit_email" value = "'.dol_escape_htmltag($audit_email).'" class = "flat minwidth300"></td>
						</tr>
						<tr class = "oddeven">
							<td>'.$langs->trans('Smtp2goBounceNotif').' '.$form->textwithpicto('', $langs->trans('Smtp2goBounceNotifHelp')).'</td>
							<td><input type = "text" id = "smtp2go_edit_bounce_notifications" name = "smtp2go_bounce_notifications" value = "'.dol_escape_htmltag(!empty($bounce_notifications) ? $bounce_notifications : 'from').'" class = "flat minwidth200"></td>
						</tr>
					</table><br>
					<div style="text-align:center">
						<input type = "submit" value = "'.$langs->trans('Save').'" class = "button">
						<input type = "button" id = "cancel_edit_smtp_user" value = "'.$langs->trans('Cancel').'" class = "button button-cancel">
					</div>
				</form>
			</div>
			<script type = "text/javascript">
				jQuery(document).ready(function() {
					var smtp2goUsersData = '.json_encode($smtp_users_by_username).';
					var smtp2goGetPasswordUrl = "'.dol_buildpath('/multismtp/ajax/smtp2go_getpassword.php', 1).'";	// InfraS add
					function smtp2go_toggle_dependent_rows() {
						jQuery(".smtp2go_edit_ratelimit_row").toggle(jQuery("#smtp2go_edit_ratelimit").prop("checked"));
						jQuery(".smtp2go_edit_feedback_row").toggle(jQuery("#smtp2go_edit_feedback_enabled").prop("checked"));
					}
					// Only used when the admin clicks the edit picto to open the form FRESH : it pulls
					// the currently known values from the local mirror table. It must NEVER be called
					// when redisplaying the form after a validation error, otherwise it would overwrite
					// what the admin just typed with the old mirrored values (that was the bug).
					function smtp2go_fill_edit_form(username) {
						var u = smtp2goUsersData[username] || {};
						jQuery("#smtp2go_edit_target").val(username);
						jQuery("#smtp2go_edit_username_display").val(username);
						jQuery("#smtp2go_edit_user_id").val(u.fk_user > 0 ? u.fk_user : -1).trigger("change");
						// InfraS change begin : the password is no longer part of smtp2goUsersData (that used to embed every
						// decrypted password in the page on every render) ; fetch it on demand for this one username only.
						jQuery("#smtp2go_edit_password").val("");
						jQuery.post(smtp2goGetPasswordUrl, {token: jQuery(\'input[name="token"]\').first().val(), username: username}, function(data) {
							if (data && data.password) {
								jQuery("#smtp2go_edit_password").val(data.password);
							}
						}, "json");
						// InfraS change end
						jQuery("#smtp2go_edit_description").val(u.description || "");
						jQuery("#smtp2go_edit_ratelimit").prop("checked", !!u.custom_ratelimit);
						jQuery("#smtp2go_edit_ratelimit_value").val(u.custom_ratelimit_value || "");
						jQuery("#smtp2go_edit_custom_ratelimit_period").val(u.custom_ratelimit_period || "01:00:00");
						jQuery("#smtp2go_edit_ip_pool").val(u.ip_pool || "");
						jQuery("#smtp2go_edit_feedback_enabled").prop("checked", !!u.feedback_enabled);
						jQuery("#smtp2go_edit_feedback_domain").val(u.feedback_domain || "default");
						jQuery("#smtp2go_edit_feedback_html").val(u.feedback_html || "");
						jQuery("#smtp2go_edit_feedback_text").val(u.feedback_text || "");
						jQuery("#smtp2go_edit_open_tracking").prop("checked", !!u.open_tracking_enabled);
						jQuery("#smtp2go_edit_click_tracking").prop("checked", !!u.click_tracking_enabled);
						jQuery("#smtp2go_edit_archive").prop("checked", !!u.archive_enabled);
						jQuery("#smtp2go_edit_audit_email").val(u.audit_email || "");
						jQuery("#smtp2go_edit_bounce_notifications").val(u.bounce_notifications || "from");
						smtp2go_toggle_dependent_rows();	// InfraS change : removed console.log() of the per-user record (it included the plaintext password)
						jQuery("#add_edit_smtp_user_form").show();
					}
					// Sync the checkbox-dependent rows visibility with whatever state the checkboxes are
					// already in (server-rendered on an error redisplay, or defaults on a fresh page load).
					smtp2go_toggle_dependent_rows();
					jQuery("#smtp2go_edit_ratelimit, #smtp2go_edit_feedback_enabled").change(smtp2go_toggle_dependent_rows);
					jQuery(".smtp2go-edit-user").click(function(e) {
						e.preventDefault();
						smtp2go_fill_edit_form(jQuery(this).data("username"));
					});
					jQuery("#cancel_edit_smtp_user").click(function(e) {
						e.preventDefault();
						jQuery("#add_edit_smtp_user_form form")[0].reset();
						smtp2go_toggle_dependent_rows();
						jQuery("#add_edit_smtp_user_form").hide();
					});
				});
			</script>';
