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
	* 	\file		./custom/multismtp/core/tpl/smtp2goSubaccount_create.tpl.php
	* 	\ingroup	multismtp
	* 	\brief		Template for creating a new SMTP2GO user on behalf of the subaccount
	************************************************/

	// Protection contre l'appel direct
	if (empty($conf) || ! is_object($conf)) {
		print "Error, template page can't be called as URL";
		exit;
	}

	// Libraries ************************************
	dol_include_once('/multismtp/lib/multismtp.php');

	// View *****************************************
	print '	<div id="add_my_smtp_user_form"'.($open_my_smtp_user_form ? '' : ' class="hideobject"').'>';
	print '		<form method="post" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">
					<input type="hidden" name="token" value="'.newToken().'">
					<input type="hidden" name="action" value="add_smtp_subaccount_user">
					<input type="hidden" name="smtp2go_myform" value="1">';
	print '			<table class="noborder" width="100%">
						<tr class="liste_titre"><td colspan="2">'.$langs->trans('Smtp2goAddSmtpSubAccountUser').'</td></tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goToLinkWithUser').' '.$form->textwithpicto('', $langs->trans('Smtp2goToLinkWithUserHelp')).'</td>
							<td>'.$form->select_dolusers($smtp2go_sub_user_id, 'smtp2go_sub_user_id', 1).'</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Login').'</td>
							<td><input type="text" name="smtp2go_username" value="'.dol_escape_htmltag($smtp_username).'" class="flat minwidth300" minlength="5" maxlength="100" required> '.$form->textwithpicto('', $langs->trans('Smtp2goErrorUsernameLength')).'</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Password').'</td>
							<td><input type="password" name="smtp2go_password" value="'.dol_escape_htmltag($smtp_password).'" class="flat minwidth300" autocomplete="new-password"> '.$form->textwithpicto('', $langs->trans('Smtp2goPasswordHelp')).'</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Description').'</td>
							<td><input type="text" name="smtp2go_description" value="'.dol_escape_htmltag($smtp_description).'" class="flat minwidth300"></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goTeaMembersEmail').' '.$form->textwithpicto('', $langs->trans('Smtp2goTeaMembersEmailHelp')).'</td>
							<td><input type="email" name="smtp2go_mysub_email" value="'.dol_escape_htmltag($smtp2go_mysub_email).'" class="flat minwidth300"></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goCustomRatelimit').' '.$form->textwithpicto('', $langs->trans('Smtp2goCustomRatelimitHelp')).'</td>
							<td><input type="checkbox" id="smtp2go_subaccount_ratelimit" name="smtp2go_custom_ratelimit" value="1"'.($smtp_custom_ratelimit ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven smtp2go_subaccount_ratelimit_row">
							<td>'.$langs->trans('Smtp2goRatelimitValue').'</td>
							<td>
								<input type="number" name="smtp2go_custom_ratelimit_value" value="'.($ratelimit_value > 0 ? (int) $ratelimit_value : '').'" min="1" class="flat width100"> ';
								print $form->selectarray('smtp2go_custom_ratelimit_period', $smtp2go_rate_limits, (!empty($ratelimit_period) ? $ratelimit_period : '1 hour'), 0).'
							</td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goArchive').'</td>
							<td><input type="checkbox" name="smtp2go_archive" value="1"'.($smtp_archive ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goOpenTracking').' '.$form->textwithpicto('', $langs->trans('Smtp2goOpenTrackingHelp')).'</td>
							<td><input type="checkbox" name="smtp2go_open_tracking" value="1"'.($smtp_open_tracking ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goClickTracking').' '.$form->textwithpicto('', $langs->trans('Smtp2goClickTrackingHelp')).'</td>
							<td><input type="checkbox" name="smtp2go_click_tracking" value="1"'.($smtp_click_tracking ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goEnforce2FA').' '.$form->textwithpicto('', $langs->trans('Smtp2goEnforce2FAHelp')).'</td>
							<td><input type="checkbox" name="smtp2go_enforce_2fa" value="1"'.($smtp_enforce_2fa ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven">
							<td>'.$langs->trans('Smtp2goEnableSms').'</td>
							<td><input type="checkbox" id="smtp2go_enable_sms" name="smtp2go_enable_sms" value="1"'.($smtp_enable_sms ? ' checked' : '').'></td>
						</tr>
						<tr class="oddeven smtp2go_sms_row">
							<td>'.$langs->trans('Smtp2goSmsLimit').'</td>
							<td><input type="number" name="smtp2go_sms_limit" value="'.($smtp_sms_limit > 0 ? (int) $smtp_sms_limit : 1000).'" min="1" class="flat width100"></td>
						</tr>
					</table><br>
					<div style="text-align:center">
						<input type="submit" value="'.$langs->trans('Create').'" class="button">
						<input type="button" id="cancel_add_my_smtp_user" value="'.$langs->trans('Cancel').'" class="button button-cancel">
					</div>';
	print '		</form>';
	print '	</div>';

	print '<script type="text/javascript">
				jQuery(document).ready(function() {
					function smtp2go_toggle_dependent_rows() {
						jQuery(".smtp2go_subaccount_ratelimit_row").toggle(jQuery("#smtp2go_subaccount_ratelimit").prop("checked"));
						jQuery(".smtp2go_sms_row").toggle(jQuery("#smtp2go_enable_sms").prop("checked"));
					}
					smtp2go_toggle_dependent_rows();
					jQuery("#smtp2go_subaccount_ratelimit, #smtp2go_enable_sms").change(smtp2go_toggle_dependent_rows);
					jQuery("#toggle_add_my_smtp_user").click(function(e) {
						e.preventDefault();
						jQuery("#add_my_smtp_user_form").toggle();
					});
					jQuery("#cancel_add_my_smtp_user").click(function(e) {
						e.preventDefault();
						jQuery("#add_my_smtp_user_form form")[0].reset();
						smtp2go_toggle_dependent_rows();
						jQuery("#add_my_smtp_user_form").hide();
					});
				});
			</script>';
