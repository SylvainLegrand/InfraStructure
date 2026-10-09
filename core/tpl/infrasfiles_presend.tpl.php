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
	* 	\file		./infrasfiles/core/tpl/infrasfiles_presend.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		"One e-mail per third party" send form, used instead of core/tpl/card_presend.tpl.php for the objects
	*				whose registry entry has 'mailbythirdparty'. The native FormMail::get_form() builds the form (sender,
	*				template, topic, body, copies) without the native recipients / attachments rows, replaced by a table
	*				of the third parties with their recipients and their PDF (infrasfiles_mail_thirdparty_row()).
	*				Expected from the caller : $object, $element, $definition, $modelmail, $defaulttopic, $defaulttopiclang, $trackid, $batchdata,
	*				$presendreturnurl (URL the form posts to and of the Cancel button : the card or the module document page)
	************************************************/

	// Protection to avoid direct call of template
	if (empty($conf) || !is_object($conf)) {
		print 'Error, template page can\'t be called as URL';
		exit(1);
	}

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	dol_include_once('/infrasfiles/core/lib/infrasfilesmail.lib.php');

	$langs->loadLangs(array('mails', 'other', 'infrasfiles@infrasfiles'));

	// Output language (the same for every e-mail : the third parties of an order may have different languages, the user chooses)
	$outputlangs	= $langs;
	$newlang		= '';
	if (getDolGlobalInt('MAIN_MULTILANGS') && GETPOST('lang_id', 'aZ09')) {
		$newlang	= GETPOST('lang_id', 'aZ09');
	}
	if (!empty($newlang)) {
		$outputlangs	= new Translate('', $conf);
		$outputlangs->setDefaultLang($newlang);
		$outputlangs->loadLangs(array('main', 'other'));
		if (!empty($defaulttopiclang)) {
			$outputlangs->loadLangs(array($defaulttopiclang));
		}
	}
	$topicmail	= $outputlangs->trans($defaulttopic, '__REF__');

	print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
	print '<div class="clearboth"></div>';
	print '<br>';
	print load_fiche_titre($langs->trans('SendMail'));
	print dol_get_fiche_head(array(), '', '', -1);

	$formmail	= new FormMail($db);
	$formmail->param['langsmodels']	= (empty($newlang) ? $langs->defaultlang : $newlang);
	$formmail->fromtype	= (GETPOST('fromtype') ? GETPOST('fromtype') : getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE', 'user'));
	if ($formmail->fromtype === 'user') {
		$formmail->fromid	= $user->id;
	}
	// Default sender (same hook as the native template)
	$defaultfrom	= '';
	if (GETPOSTISSET('fromtype')) {
		$defaultfrom	= GETPOST('fromtype');
	} else {
		$parameters	= array();
		$reshook	= $hookmanager->executeHooks('getDefaultFromEmail', $parameters, $formmail);
		if (empty($reshook)) {
			$defaultfrom	= $formmail->fromtype;
		}
		if (!empty($hookmanager->resArray['defaultfrom'])) {
			$defaultfrom	= $hookmanager->resArray['defaultfrom'];
		}
	}
	$formmail->fromtype		= $defaultfrom;
	$formmail->trackid		= empty($trackid) ? '' : $trackid;
	$formmail->withfrom		= 1;
	$formmail->withlayout	= 'email';
	$formmail->withaiprompt	= 'html';
	// Recipients and attachments are chosen per third party in the table : no native "To" / "To users" / "Attached files" rows
	$formmail->withto		= 0;
	$formmail->withtofree	= 0;
	$formmail->withtouser	= array();
	$formmail->withtocc		= 1;	// free "Copy to" field, common to every e-mail
	$formmail->withtoccc	= getDolGlobalString('MAIN_EMAIL_USECCC');
	if (getDolGlobalString('MAIN_MAIL_ENABLED_USER_DEST_SELECT')) {
		$listeuser	= array();
		$fuserdest	= new User($db);
		$result		= $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, "(t.statut:=:1) AND (t.employee:=:1) AND (t.email:isnot:NULL) AND (t.email:!=:'')", 'AND', true);
		if ($result > 0 && is_array($fuserdest->users)) {
			foreach ($fuserdest->users as $uuserdest) {
				$listeuser[$uuserdest->id]	= $uuserdest->user_get_property($uuserdest->id, 'email');
			}
		} elseif ($result < 0) {
			setEventMessages(null, $fuserdest->errors, 'errors');
		}
		if (!empty($listeuser)) {
			$formmail->withtoccuser	= $listeuser;	// users in copy, common to every e-mail
		}
	}
	$formmail->withtopic			= $topicmail;
	$formmail->withfile				= 0;
	$formmail->withbody				= 1;
	$formmail->withdeliveryreceipt	= 1;
	$formmail->withcancel			= 1;

	// Substitutions shown in the editor : the object has no third party here, so the __THIRDPARTY_*__ keys stay as they are
	// in the template and are substituted for each third party at send time (infrasfiles_send_by_thirdparty())
	$formmail->setSubstitFromObject($object, $langs);
	$substitutionarray	= getCommonSubstitutionArray($outputlangs, 0, null, $object);
	$emailsendersignature	= null;
	if ($formmail->fromtype) {
		$reg	= array();
		if (preg_match('/user/', $formmail->fromtype, $reg)) {
			$emailsendersignature	= $user->signature;
		} elseif (preg_match('/company/', $formmail->fromtype, $reg)) {
			$emailsendersignature	= '';
		} elseif (preg_match('/senderprofile_(\d+)/', $formmail->fromtype, $reg)) {
			$sql	= 'SELECT rowid, label, email, signature FROM '.$db->prefix().'c_email_senderprofile WHERE rowid = '.((int) $reg[1]);
			$resql	= $db->query($sql);
			$obj	= $resql ? $db->fetch_object($resql) : null;
			if ($obj) {
				$emailsendersignature	= $obj->signature;
			}
		}
	}
	$substitutionarray['__SENDEREMAIL_SIGNATURE__']	= $emailsendersignature;
	$substitutionarray['__CHECK_READ__']			= '';
	$substitutionarray['__CONTACTCIVNAME__']		= '';
	$parameters	= array('mode' => 'formemail');
	complete_substitutions_array($substitutionarray, $outputlangs, $object, $parameters);
	$formmail->substit	= $substitutionarray;

	$formmail->param['action']					= 'infrasfiles_sendbythirdparty';
	$formmail->param['models']					= $modelmail;
	$formmail->param['models_id']				= GETPOSTINT('modelmailselected');
	$formmail->param['id']						= $object->id;
	$formmail->param['returnurl']				= !empty($presendreturnurl) ? $presendreturnurl : $_SERVER['PHP_SELF'].'?id='.$object->id;
	$formmail->param['fileinit']				= array();
	$formmail->param['object_entity']			= $object->entity;
	$formmail->param['infrasfiles_bythirdparty']	= 1;	// tells the hook getFormMail not to force the native recipients / attachments

	print infrasfiles_mail_inject_row($formmail->get_form(), infrasfiles_mail_thirdparty_row($object, $batchdata));
	print dol_get_fiche_end();
