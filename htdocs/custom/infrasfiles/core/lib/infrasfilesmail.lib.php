<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrasfiles/core/lib/infrasfilesmail.lib.php
	* 	\ingroup	InfraS
	* 	\brief		"One e-mail per third party" send form : recipients table injected into the native FormMail form, and send loop
	*				(the native core/actions_sendmails.inc.php sends to one recipient only)
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');

	/**
	*	Sender address from the 'fromtype' value posted by the native form (same rules as core/actions_sendmails.inc.php)
	*
	*	@param		string		$fromtype	Value of the 'fromtype' select (user, company, robot, user_aliases_N, global_aliases_N, senderprofile_N_N, from_template_N, or custom)
	*	@return		array					array('from' => 'Name <email>', 'signature' => signature of a sender profile or '')
	**/
	function infrasfiles_mail_from($fromtype)
	{
		global $conf, $db, $langs, $user;

		$from		= '';
		$signature	= '';
		$reg		= array();
		if ($fromtype === 'robot') {
			$from	= dol_string_nospecial(getDolGlobalString('MAIN_MAIL_EMAIL_FROM'), ' ', array(',')).' <'.getDolGlobalString('MAIN_MAIL_EMAIL_FROM').'>';
		} elseif ($fromtype === 'user') {
			$from	= dol_string_nospecial($user->getFullName($langs), ' ', array(',')).' <'.$user->email.'>';
		} elseif ($fromtype === 'company') {
			$from	= dol_string_nospecial(getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), ' ', array(',')).' <'.getDolGlobalString('MAIN_INFO_SOCIETE_MAIL').'>';
		} elseif (preg_match('/user_aliases_(\d+)/', $fromtype, $reg)) {
			$tmp	= explode(',', (string) $user->email_aliases);
			$from	= isset($tmp[(int) $reg[1] - 1]) ? trim($tmp[(int) $reg[1] - 1]) : '';
		} elseif (preg_match('/global_aliases_(\d+)/', $fromtype, $reg)) {
			$tmp	= explode(',', getDolGlobalString('MAIN_INFO_SOCIETE_MAIL_ALIASES'));
			$from	= isset($tmp[(int) $reg[1] - 1]) ? trim($tmp[(int) $reg[1] - 1]) : '';
		} elseif (preg_match('/senderprofile_(\d+)_(\d+)/', $fromtype, $reg)) {
			$sql	= 'SELECT rowid, label, email, signature FROM '.$db->prefix().'c_email_senderprofile WHERE rowid = '.((int) $reg[1]);
			$resql	= $db->query($sql);
			$obj	= $resql ? $db->fetch_object($resql) : null;
			if ($obj) {
				$from		= dol_string_nospecial($obj->label, ' ', array(',')).' <'.$obj->email.'>';
				$signature	= (string) $obj->signature;
			}
		} elseif (preg_match('/from_template_(\d+)/', $fromtype, $reg)) {
			$sql	= 'SELECT rowid, email_from FROM '.$db->prefix().'c_email_templates WHERE rowid = '.((int) $reg[1]);
			$resql	= $db->query($sql);
			$obj	= $resql ? $db->fetch_object($resql) : null;
			if ($obj) {
				$from	= (string) $obj->email_from;
			}
		} else {
			$from	= dol_string_nospecial(GETPOST('fromname'), ' ', array(',')).' <'.GETPOST('frommail').'>';
		}
		return array('from' => $from, 'signature' => $signature);
	}

	/**
	*	Invalid addresses of a free recipients field ('a@b.fr, Name <c@d.fr>'), split and read as CMailFile does (comma separator,
	*	e-mail between <> when a name is given), each one checked by the native isValidEmail()
	*
	*	@param		string		$addresses	Free field value
	*	@return		array					List of the invalid entries (empty = all valid)
	**/
	function infrasfiles_mail_invalid_addresses($addresses)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$invalid	= array();
		foreach (explode(',', (string) $addresses) as $entry) {
			$entry	= trim($entry);
			if ($entry === '') {
				continue;
			}
			if (!isValidEmail(CMailFile::getValidAddress($entry, 2))) {
				$invalid[]	= $entry;
			}
		}
		return $invalid;
	}
	/**
	*	Row "Recipients per third party" of the send form : one line per batch (checkbox, third party, recipients to choose,
	*	free e-mail, PDF attached), files not matching any third party listed as not sent. Posted values are kept on a new
	*	display (template change, send error).
	*
	*	@param		object		$object		Object (child class of the module)
	*	@param		array		$batchdata	Result of infrasfilesGetMailBatches() : 'batches' and 'orphans'
	*	@return		string					HTML (a <tr> of the 2 columns form table)
	**/
	function infrasfiles_mail_thirdparty_row($object, $batchdata)
	{
		global $db, $langs;

		$langs->loadLangs(array('companies', 'mails', 'other', 'infrasfiles@infrasfiles'));
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$form		= new Form($db);
		$batches	= !empty($batchdata['batches']) ? $batchdata['batches'] : array();
		$orphans	= !empty($batchdata['orphans']) ? $batchdata['orphans'] : array();
		$posted		= GETPOSTISSET('infrasfiles_posted');
		$selected	= $posted ? array_map('intval', GETPOST('infrasfiles_soc', 'array')) : array();
		$istransfer	= (!empty($object->type) && $object->type == 'bank-transfer');
		$subdir		= infrasfiles_get_subdir($object->infrasfiles_element, $object);

		$out	= '<tr><td class="tdtop">'.$form->textwithpicto($langs->trans('InfraSFilesMailByThirdparty'), $langs->trans('InfraSFilesMailByThirdpartyHelp'), 1, 'help').'</td><td>';
		$out	.= '<input type="hidden" name="infrasfiles_posted" value="1">';
		if (empty($batches)) {
			$out	.= '<span class="warning">'.$langs->trans('InfraSFilesMailNoFile').'</span>';
		} else {
			$out	.= '<div class="div-table-responsive-no-min"><table class="noborder centpercent" id="infrasfiles_mailbatches">';
			$out	.= '<tr class="liste_titre">';
			$out	.= '<th class="center"><input type="checkbox" id="infrasfiles_soc_all" title="'.dol_escape_htmltag($langs->trans('SelectAll')).'"></th>';
			$out	.= '<th>'.$langs->trans('ThirdParty').'</th>';
			$out	.= '<th>'.$langs->trans('MailTo').'</th>';
			$out	.= '<th>'.$langs->trans('AttachedFiles').'</th>';
			$out	.= '</tr>';
			foreach ($batches as $socid => $batch) {
				$soc		= new Societe($db);
				$soc->id	= (int) $socid;
				$soc->name	= $batch['addressee']['name'];
				if ($istransfer) {
					$soc->code_fournisseur	= $batch['addressee']['code'];
				} else {
					$soc->code_client		= $batch['addressee']['code'];
				}
				$options	= array();
				foreach ($batch['recipients'] as $key => $label) {
					$options[$key]	= str_replace(array('<', '>'), array('(', ')'), $label);	// same display as the native recipients list
				}
				$receivers	= $posted ? GETPOST('infrasfiles_receiver_'.$socid, 'array') : $batch['default'];
				$free		= $posted ? GETPOST('infrasfiles_sendto_'.$socid, 'alphawithlgt') : '';
				$checked	= $posted ? in_array((int) $socid, $selected) : (!empty($options) || !empty($free));
				$out	.= '<tr class="oddeven">';
				$out	.= '<td class="center"><input type="checkbox" class="infrasfiles_soc" name="infrasfiles_soc[]" value="'.((int) $socid).'"'.($checked ? ' checked="checked"' : '').'></td>';
				$out	.= '<td class="tdoverflowmax200">'.$soc->getNomUrl(1, '', 0, 1).'</td>';
				$out	.= '<td>';
				if (!empty($options)) {
					$out	.= $form->multiselectarray('infrasfiles_receiver_'.$socid, $options, $receivers, 0, 0, 'minwidth300', 0, '');
					$out	.= '<br>'.$langs->trans('and').'/'.$langs->trans('or').' ';
				} else {
					$out	.= '<span class="opacitymedium">'.$langs->trans('InfraSFilesMailNoRecipient').'</span><br>';
				}
				$out	.= '<input type="text" class="minwidth300" name="infrasfiles_sendto_'.((int) $socid).'" value="'.dol_escape_htmltag($free).'">';
				$out	.= '</td>';
				$out	.= '<td class="tdoverflowmax300">';
				foreach ($batch['files'] as $file) {
					$name	= basename($file);
					$out	.= '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=infrasfiles&file='.urlencode($subdir.'/'.$name).'" target="_blank" rel="noopener">'.img_mime($name, '', 'paddingright').dol_escape_htmltag($name).'</a><br>';
				}
				$out	.= '</td>';
				$out	.= '</tr>';
			}
			$out	.= '</table></div>';
			$out	.= '<script type = "text/javascript">
							jQuery(document).ready(function() {
								jQuery("#infrasfiles_soc_all").prop("checked", jQuery("input.infrasfiles_soc:not(:checked)").length == 0);
								jQuery("#infrasfiles_soc_all").on("click", function() {
									jQuery("input.infrasfiles_soc").prop("checked", this.checked);
								});
							});
						</script>';
		}
		if (!empty($orphans)) {
			$out	.= '<br><span class="opacitymedium">'.$langs->trans('InfraSFilesMailOrphanFiles', dol_escape_htmltag(implode(', ', $orphans))).'</span>';
		}
		$out	.= '</td></tr>';
		return $out;
	}

	/**
	*	Insert the recipients row into the HTML of the native form : before the "Copy to" row, else before the "Topic" row,
	*	else at the top of the form table
	*
	*	@param		string		$formhtml	HTML returned by FormMail::get_form()
	*	@param		string		$row		HTML of the row (infrasfiles_mail_thirdparty_row())
	*	@return		string					HTML
	**/
	function infrasfiles_mail_inject_row($formhtml, $row)
	{
		foreach (array('id="sendtocc"', 'id="subject"') as $marker) {
			$pos	= strpos($formhtml, $marker);
			if ($pos === false) {
				continue;
			}
			$start	= strrpos(substr($formhtml, 0, $pos), '<tr');
			if ($start !== false) {
				return substr($formhtml, 0, $start).$row.substr($formhtml, $start);
			}
		}
		$pos	= strpos($formhtml, '<table class="tableforemailform');
		if ($pos !== false) {
			$end	= strpos($formhtml, '>', $pos);
			return substr($formhtml, 0, $end + 1).$row.substr($formhtml, $end + 1);
		}
		dol_syslog(__FUNCTION__.' : form table not found, recipients row not inserted', LOG_WARNING);
		return $formhtml;
	}

	/**
	*	Send one e-mail per checked third party : the common subject / message are substituted for each third party
	*	(__THIRDPARTY_*__ from its Societe), its own PDF are attached, then the <OBJECT>_SENTBYMAIL trigger fires with the
	*	third party as socid (one agenda event per third party). Messages (summary, errors) are set here ; the caller redirects.
	*
	*	@param		object		$object		Object (child class of the module)
	*	@param		array		$definition	Registry entry of the object (trigger, trackid)
	*	@param		array		$batchdata	Result of infrasfilesGetMailBatches()
	*	@return		array					array('sent' => number of e-mails sent, 'selected' => number of third parties checked, 'errors' => list of messages)
	**/
	function infrasfiles_send_by_thirdparty($object, $definition, $batchdata)
	{
		global $conf, $db, $langs, $user, $dolibarr_main_url_root;

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$langs->loadLangs(array('mails', 'commercial', 'other', 'infrasfiles@infrasfiles'));

		$result		= array('sent' => 0, 'selected' => 0, 'errors' => array());
		$batches	= !empty($batchdata['batches']) ? $batchdata['batches'] : array();
		$selected	= array_map('intval', GETPOST('infrasfiles_soc', 'array'));
		$selected	= array_values(array_filter($selected, function ($socid) use ($batches) {
			return isset($batches[$socid]);
		}));
		$result['selected']	= count($selected);
		if (empty($selected)) {
			setEventMessages($langs->trans('InfraSFilesMailNoThirdpartySelected'), null, 'warnings');
			return $result;
		}
		// Common part of every e-mail (same reading as the native send action)
		$sender		= infrasfiles_mail_from(GETPOST('fromtype', 'alpha'));
		$from		= $sender['from'];
		$subject	= GETPOST('subject', 'restricthtml');
		$message	= GETPOST('message', 'restricthtml');
		$urlwithouturlroot	= preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot		= $urlwithouturlroot.DOL_URL_ROOT;
		$message	= preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $message);
		$tmparray	= array();
		if (trim(GETPOST('sendtocc', 'alphawithlgt'))) {
			$tmparray[]	= trim(GETPOST('sendtocc', 'alphawithlgt'));
		}
		$sendtoccuserid	= array();
		if (getDolGlobalString('MAIN_MAIL_ENABLED_USER_DEST_SELECT')) {
			$receiverccuser	= GETPOST('receiverccuser', 'array');
			if (!empty($receiverccuser)) {
				$fuserdest	= new User($db);
				foreach ($receiverccuser as $val) {
					$tmparray[]			= $fuserdest->user_get_property((int) $val, 'email');
					$sendtoccuserid[]	= (int) $val;
				}
			}
		}
		$sendtocc	= implode(',', $tmparray);
		$sendtobcc	= GETPOST('sendtoccc', 'alphawithlgt');
		if (getDolGlobalString('MAIN_MAIL_AUTOCOPY_INFRASFILES_TO')) {
			$sendtobcc	.= ($sendtobcc ? ', ' : '').getDolGlobalString('MAIN_MAIL_AUTOCOPY_INFRASFILES_TO');
		}
		$deliveryreceipt	= GETPOSTINT('deliveryreceipt') ? 1 : 0;
		$trackid			= $definition['trackid'].$object->id;
		$upload_dir_tmp		= $conf->user->dir_output.'/'.$user->id.'/temp';	// used by CMailFile to convert images embedded as data:image
		$triggername		= !empty($definition['trigger']) ? $definition['trigger'] : '';

		foreach ($selected as $socid) {
			$batch	= $batches[$socid];
			$name	= $batch['addressee']['name'];
			// Recipients of this third party : chosen in the list (contact ids or 'thirdparty') and / or typed in the free field
			$sendto		= array();
			$sendtoid	= array();
			foreach ((array) GETPOST('infrasfiles_receiver_'.$socid, 'array') as $key) {
				if ($key === 'thirdparty' && isset($batch['recipients']['thirdparty'])) {
					$sendto[]	= $batch['recipients']['thirdparty'];
				} elseif (is_numeric($key) && isset($batch['recipients'][(int) $key])) {
					$sendto[]	= $batch['recipients'][(int) $key];
					$sendtoid[]	= (int) $key;
				}
			}
			$free	= trim(GETPOST('infrasfiles_sendto_'.$socid, 'alphawithlgt'));
			if ($free !== '') {
				// Free field : every address must be valid (CMailFile would silently drop an invalid one and the e-mail would be counted as sent)
				$invalid	= infrasfiles_mail_invalid_addresses($free);
				if (!empty($invalid)) {
					$result['errors'][]	= $langs->trans('InfraSFilesMailErrorFor', $name, $langs->transnoentities('InfraSFilesMailErrorBadEmail', implode(', ', $invalid)));	// inner text not encoded : the outer trans() encodes the whole message once (a nested trans() was encoded twice)
					continue;
				}
				$sendto[]	= $free;
			}
			$sendto	= implode(',', $sendto);
			if (!dol_strlen($sendto)) {
				$result['errors'][]	= $langs->trans('InfraSFilesMailErrorFor', $name, $langs->transnoentities('InfraSFilesMailErrorNoRecipient'));
				continue;
			}
			// Substitutions for this third party : __THIRDPARTY_*__ come from $object->thirdparty. A third party that cannot be loaded
			// (deleted since the generation) is skipped : its e-mail would leave with the __THIRDPARTY_*__ variables not replaced
			$soc	= new Societe($db);
			if ($soc->fetch($socid) <= 0) {
				$result['errors'][]	= $langs->trans('InfraSFilesMailErrorFor', $name, $langs->transnoentities('InfraSFilesMailErrorThirdparty'));
				continue;
			}
			$object->thirdparty	= $soc;
			$substitutionarray	= getCommonSubstitutionArray($langs, 0, null, $object);
			$substitutionarray['__SENDEREMAIL_SIGNATURE__']	= (empty($sender['signature']) ? $user->signature : $sender['signature']);
			$substitutionarray['__EMAIL__']					= $sendto;
			$substitutionarray['__CHECK_READ__']			= '';
			$parameters	= array('mode' => 'formemail');
			complete_substitutions_array($substitutionarray, $langs, $object, $parameters);
			$thissubject	= html_entity_decode(make_substitutions($subject, $substitutionarray), ENT_QUOTES | ENT_HTML5, 'UTF-8');	// as the native send action of the LTS core : no &gt; in e-mail clients
			$thismessage	= make_substitutions($message, $substitutionarray);
			// Attachments : the PDF of this third party only
			$filepath	= array();
			$filename	= array();
			$mimetype	= array();
			foreach ($batch['files'] as $file) {
				$filepath[]	= $file;
				$filename[]	= basename($file);
				$mimetype[]	= dol_mimetype($file);
			}
			$mailfile	= new CMailFile($thissubject, $sendto, $from, $thismessage, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', 'standard', '', $upload_dir_tmp);
			if (!empty($mailfile->error) || !empty($mailfile->errors)) {
				$result['errors'][]	= $langs->trans('InfraSFilesMailErrorFor', $name, $mailfile->error.(!empty($mailfile->errors) ? ' '.implode(' ', $mailfile->errors) : ''));
				continue;
			}
			if (!$mailfile->sendfile()) {
				$error	= !empty($mailfile->error) ? $mailfile->error : (getDolGlobalString('MAIN_DISABLE_ALL_MAILS') ? 'MAIN_DISABLE_ALL_MAILS' : $langs->transnoentities('InfraSFilesMailErrorSend'));
				$result['errors'][]	= $langs->trans('InfraSFilesMailErrorFor', $name, $error);
				continue;
			}
			$result['sent']++;
			// Agenda event of this third party through the native trigger (same properties as the native send action)
			if (!empty($triggername)) {
				$object->socid			= (int) $socid;
				$object->sendtoid		= $sendtoid;
				$object->actiontypecode	= 'AC_OTH_AUTO';
				$object->actionmsg		= $thismessage;
				$object->actionmsg2		= getDolGlobalString('MAIN_MAIL_REPLACE_EVENT_TITLE_BY_EMAIL_SUBJECT') ? $thissubject : $langs->transnoentities('MailSentByTo', CMailFile::getValidAddress($from, 4, 0, 1), CMailFile::getValidAddress($sendto, 4, 0, 1));
				$object->trackid		= $trackid;
				$object->fk_element		= $object->id;
				$object->elementtype	= $object->element;
				$object->attachedfiles	= array('paths' => $filepath, 'names' => $filename, 'mimes' => $mimetype);
				if (!empty($sendtoccuserid)) {
					$object->sendtouserid	= $sendtoccuserid;
				}
				$object->email_msgid	= $mailfile->msgid;
				$object->email_from		= $from;
				$object->email_subject	= $thissubject;
				$object->email_to		= $sendto;
				$object->email_tocc		= $sendtocc;
				$object->email_tobcc	= $sendtobcc;
				$object->context['actionmsgmore']	= $langs->transnoentities('AttachedFiles').': '.implode(', ', $filename);	// the native action reads the session, not used here
				if ($object->call_trigger($triggername, $user) < 0) {
					$result['errors'][]	= $langs->trans('InfraSFilesMailErrorFor', $name, (!empty($object->error) ? $object->error : implode(' ', (array) $object->errors)));
				}
				unset($object->context['actionmsgmore']);
			}
		}
		$object->thirdparty	= null;	// never leave the last third party on the object : the form would substitute __THIRDPARTY_*__ on a new display
		setEventMessages($langs->trans('InfraSFilesMailSentSummary', $result['sent'], $result['selected']), null, ($result['sent'] > 0 ? 'mesgs' : 'warnings'));
		if (!empty($result['errors'])) {
			setEventMessages('', $result['errors'], 'errors');
		}
		return $result;
	}
