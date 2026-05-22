<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infraspackplus/comm/address.php
	* 	\ingroup	InfraS
	* 	\brief		Tab address of thirdparty
	************************************************/

	// Dolibarr environment *************************
	require '../config.php';

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
	dol_include_once('/infraspackplus/class/address.class.php');

	global $user;

	// Translations *********************************
	$langs->loadLangs(array('companies', 'commercial', 'infraspackplus@infraspackplus'));

	$id			= GETPOSTINT('id');
	$action		= GETPOST('action','alpha');
	$cancel		= GETPOST('cancel', 'alpha');
	$confirm	= GETPOST('confirm','alpha');
	$backtopage	= GETPOST('backtopage','alpha');
	if (!empty($backtopage)) {
		$backtopage = dol_sanitizeUrl($backtopage);
	}
	$origin		= GETPOST('origin','alpha');
	$originid	= GETPOSTINT('originid');
	$socid		= $user->socid ? $user->socid : (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));
	$societe	= new Societe($db);
	$societe->fetch($socid);

	// Access control *******************************
	$result		= restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', 0);
	if (empty($user->hasRight('societe', 'contact', 'lire'))) {
		accessforbidden();
	}
	$permissiontoadd	= !empty($user->hasRight('societe', 'creer'));

	// Actions **************************************
	// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
	$hookmanager->initHooks(array('addresscard'));
	if (!empty($cancel)) {
		$action	= '';
		if (!empty($backtopage)) {
			header('Location: '.$backtopage);
			exit;
		}
	}
	$object			= new Address($db);
	$form			= new Form($db);
	$formcompany	= new FormCompany($db);
	if (!empty($id)) {
		$object->fetch($id);
	}
	if ($action == 'add' || $action == 'update') {
		$object->label		= GETPOST('label', 'alphanohtml');
		$object->socid		= $socid;
		$object->name		= GETPOST('name', 'alphanohtml');
		$object->address	= GETPOST('address', 'alphanohtml');
		$object->zip		= GETPOST('zipcode', 'alphanohtml');
		$object->town		= GETPOST('town', 'alphanohtml');
		$object->country_id	= GETPOSTINT('country_id') ? GETPOSTINT('country_id') : $mysoc->country_id;
		$object->phone		= GETPOST('phone', 'alpha');
		$object->fax		= GETPOST('fax', 'alpha');
		$object->note		= GETPOST('note', 'none');
		$object->email		= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
		$object->url		= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
		// Add new address
		if ($action == 'add') {
			$result	= $object->create($user);
			if ($result >= 0) {
				if (!empty($backtopage)) {
					header('Location: '.$backtopage);
					exit;
				} else if ($origin == 'commande') {
					header('Location: ../commande/contact.php?action=editdelivery_adress&socid='.$socid.'&id='.$originid);
					exit;
				} elseif ($origin == 'propal') {
					header('Location: ../comm/propal/contact.php?action=editdelivery_adress&socid='.$socid.'&id='.$originid);
					exit;
				} elseif ($origin == 'shipment') {
					header('Location: ../expedition/card.php?id='.$originid);
					exit;
				} else {
					header('Location: '.$_SERVER['PHP_SELF'].'?socid='.$socid);
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action	= 'create';
			}
		} elseif ($action == 'update') {	// Update address
			$result	= $object->update($id, $user);
			if ($result >= 0) {
				if (!empty($backtopage)) {
					header('Location: '.$backtopage);
					exit;
				} elseif ($origin == 'commande') {
					header('Location: ../commande/contact.php?id='.$originid);
					exit;
				} elseif ($origin == 'propal') {
					header('Location: ../comm/propal/contact.php?id='.$originid);
					exit;
				} elseif ($origin == 'shipment') {
					header('Location: ../expedition/card.php?id='.$originid);
					exit;
				} elseif ($origin == 'commande') {
					header('Location: ../commande/contact.php?id='.$originid);
					exit;
				} else {
					header('Location: '.$_SERVER['PHP_SELF'].'?socid='.$socid);
					exit;
				}
			} else {
				$reload	= 0;
				setEventMessages($object->error, $object->errors, 'errors');
				$action	= 'edit';
			}
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && !empty($user->hasRight('societe', 'supprimer'))) {
		$result	= $object->delete($id);
		if ($result > 0) {
			if (!empty($backtopage)) {
				header('Location: '.$backtopage);
				exit;
			}
			header('Location: '.DOL_URL_ROOT.'/societe/contact.php?socid='.$socid);
			exit;
		} else {
			$reload	= 0;
			$action	= '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Page goes here *******************************
	$title				= !empty(getDolGlobalString('MAIN_HTML_TITLE', '')) && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE', '')) && $societe->name ? $societe->name.' - '.$langs->trans('InfraSPlusParamsAdresses') : $langs->trans('ThirdParty');
	$help_url			= 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
	llxHeader('', $title, $help_url);
	$head				= societe_prepare_head($societe);
	print dol_get_fiche_head($head, 'contact', $langs->trans('ThirdParty'), 0, 'company');
	$linkback			= '<a href = "'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';
	dol_banner_tab($societe, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');
	print dol_get_fiche_end();
	print '			<br/>';
	$countrynotdefined	= $langs->trans('ErrorSetACountryFirst').' ('.$langs->trans('SeeAbove').')';
	if ($action == 'create') {	// Create mode
		if (!empty($user->hasRight('societe', 'creer'))) {
			if (!empty(GETPOST('label', 'alphanohtml')) && !empty(GETPOST('name', 'alphanohtml'))) {
				$object->label		=	GETPOST('label', 'alphanohtml');
				$object->socid		=	$socid;
				$object->name		=	GETPOST('name', 'alphanohtml');
				$object->address	=	GETPOST('address', 'alphanohtml');
				$object->zip		=	GETPOST('zipcode', 'alphanohtml');
				$object->town		=	GETPOST('town', 'alphanohtml');
				$object->phone		=	GETPOST('phone', 'alpha');
				$object->fax		=	GETPOST('fax', 'alpha');
				$object->note		=	GETPOST('note', 'none');
				$object->email		=	GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
				$object->url		=	GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
			}
			$object->country_id	= (GETPOSTINT('country_id') ? GETPOSTINT('country_id') : $mysoc->country_id);
			if (!empty($object->country_id)) {
				$tmparray				= getCountry($object->country_id,'all');
				$object->country_code	= $tmparray['code'];
				$object->country		= $tmparray['label'];
			}
			print load_fiche_titre($langs->trans('AddAddress'));
			print '			<br/>';
			print '			<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "POST" name = "formsoc">
								<input type = "hidden" name = "token" value = "'.newToken().'"/>
								<input type = "hidden" name = "socid" value = "'.$socid.'"/>
								<input type = "hidden" name = "backtopage" value = "'.dol_escape_htmltag($backtopage).'"/>
								<input type = "hidden" name = "origin" value = "'.$origin.'"/>
								<input type = "hidden" name = "originid" value = "'.$originid.'"/>
								<input type = "hidden" name = "action" value = "add"/>
								<table class = "border" width = "100%">';
			// Label / Code
			print '					<tr>
										<td class = "fieldrequired">'.$langs->trans('InfraSPlusParamAdressAlias').'</td>
										<td colspan = "3">
											<input type = "text" class = "minwidth300" maxlength = "128" name = "label" id = "label" value = "'.$object->label.'" placeholder = "'.$langs->trans('RequiredField').'" autofocus = "autofocus">
										</td>
									</tr>';
			// Name
			print '					<tr>
										<td class = "fieldrequired">'.$langs->trans('Name').'</td>
										<td colspan = "3">
											<input type = "text" class = "minwidth300" name = "name" id = "name" value = "'.$object->name.'" placeholder = "'.$langs->trans('RequiredField').'">
										</td>
									</tr>';
			// Address
			print '					<tr>
										<td class = "tdtop">'.$langs->trans('Address').'</td>
										<td colspan = "3">
											<textarea name = "address" id = "address" class = "quatrevingtpercent" rows = "3" wrap = "soft">'.dol_escape_htmltag($object->address, 0, 1).'</textarea>';
			print $form->widgetForTranslation('address', $object, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
			print '						</td>
									</tr>';
			// Zip / Town
			print '					<tr>
										<td>'.$langs->trans('Zip').'</td>
										<td>';
			print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth100');
			print '						</td>';
			if ($conf->browser->layout == 'phone') {
				print '				</tr>
									<tr>';
			}
			print '						<td>'.$langs->trans('Town').'</td>
										<td>';
			print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			print $form->widgetForTranslation('town', $object, $permissiontoadd, 'string', 'alphanohtml', 'maxwidth100 quatrevingtpercent');
			print '						</td>
									</tr>';
			// Country
			print '					<tr>
										<td>'.$langs->trans('Country').'</td>
										<td colspan = "3">
											'.img_picto('', 'globe-americas', 'class = "paddingrightonly"');
			print $form->select_country($object->country_id, 'country_id', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
			if (!empty($user->admin)) {
				print info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'), 1);
			}
			print '						</td>
									</tr>';
			// Phone / fax
			print '					<tr>
										<td>'.$langs->trans('Phone').'</td>
										<td>
											'.img_picto('', 'object_phoning').' <input type = "text" name = "phone" id = "phone" class = "maxwidth200 widthcentpercentminusx" value = "'.$object->phone.'">
										</td>';
			if ($conf->browser->layout == 'phone') {
				print '				</tr>
									<tr>';
			}
			print '						<td>'.$langs->trans('Fax').'</td>
										<td>
											'.img_picto('', 'object_phoning_fax').' <input type = "text" name = "fax" id = "fax" class = "maxwidth200 widthcentpercentminusx" value = "'.$object->fax.'">
										</td>
									</tr>';
			// email
			print '					<tr>
										<td>'.$langs->trans('Email').'</td>
										<td colspan = "3">
											'.img_picto('', 'object_email').' <input type = "text" name = "email" id = "email" class = "maxwidth200onsmartphone maxwidth500 widthcentpercentminusx" value = "'.$object->email.'">
										</td>
									</tr>';
			// web
			print '					<tr>
										<td>'.$langs->trans('url').'</td>
										<td colspan = "3">
											'.img_picto('', 'globe').' <input type = "text" name = "url" id = "url" class = "maxwidth200onsmartphone maxwidth500 widthcentpercentminusx " value = "'.$object->url.'">
										</td>
									</tr>';
			// Notes
			print '					<tr>
										<td>'.$langs->trans('Note').'</td>
										<td colspan = "3">
											<textarea name = "note" cols = "40" rows = "6" wrap = "soft">'.dol_escape_htmltag($object->note, 0, 1).'</textarea>
										</td>
									</tr>';
			print '				</table>
								<br/>';

			// Other attributes
			$parameters = array('socid'=>$socid);
			// Note that $action and $object may be modified by hook
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
			print $hookmanager->resPrint;
			print '				<div class = "center">
									<input type = "submit" class = "button" name = "add" value = "'.$langs->trans('Add').'">';
			if (!empty($backtopage)) {
				print '				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<input type = "submit" class = "button" name = "cancel" value = "'.$langs->trans('Cancel').'">';
			}
			print '				</div>
							</form>';
		}
	} elseif ($action == 'edit') {	// Edit mode
		print load_fiche_titre($langs->trans('InfraSPlusAddressesModifs'));
		print '			<br/>';
		if (!empty($socid)) {
			if (!empty($reload) || empty(GETPOST('name', 'alphanohtml'))) {
				$object->fetch($id);
			} else {
				$object->id			=	$id;
				$object->socid		=	$socid;
				$object->label		=	GETPOST('label', 'alphanohtml');
				$object->name		=	GETPOST('name', 'alphanohtml');
				$object->address	=	GETPOST('address', 'alphanohtml');
				$object->zip		=	GETPOST('zipcode', 'alphanohtml');
				$object->town		=	GETPOST('town', 'alphanohtml');
				$object->country_id	=	GETPOSTINT('country_id') ? GETPOSTINT('country_id') : $mysoc->country_id;
				$object->phone		=	GETPOST('phone', 'alpha');
				$object->fax		=	GETPOST('fax', 'alpha');
				$object->email		=	GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
				$object->url		=	GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
				$object->note		=	GETPOST('note', 'none');
				if (!empty($object->country_id)) {
					$tmparray=getCountry($object->country_id,'all');
					$object->country_code	= $tmparray['code'];
					$object->country		= $tmparray['label'];
				}
			}
			print '			<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?socid='.$object->socid.'" method = "POST" name = "formsoc">
								<input type = "hidden" name = "token" value = "'.newToken().'"/>
								<input type = "hidden" name = "socid" value = "'.$object->socid.'"/>
								<input type = "hidden" name = "backtopage" value = "'.dol_escape_htmltag($backtopage).'"/>
								<input type = "hidden" name = "origin" value = "'.$origin.'"/>
								<input type = "hidden" name = "originid" value = "'.$originid.'"/>
								<input type = "hidden" name = "action" value = "update"/>
								<input type = "hidden" name = "id" value = "'.$object->id.'"/>
								<table class = "border" width = "100%">';
			// Label / Code
			print '					<tr>
										<td>'.$langs->trans('InfraSPlusParamAdressAlias').'</td>
										<td colspan = "3">
											<input type = "text" class = "minwidth300" maxlength = "128" name = "label" id = "label" value = "'.$object->label.'" autofocus = "autofocus">
										</td>
									</tr>';
			// Name
			print '					<tr>
										<td>'.$langs->trans('Name').'</td>
										<td colspan = "3">
											<input type = "text" class = "minwidth300" name = "name" id = "name" value = "'.$object->name.'">
										</td>
									</tr>';
			// Address
			print '					<tr>
										<td class = "tdtop">'.$langs->trans('Address').'</td>
										<td colspan = "3">
											<textarea name = "address" id = "address" class = "quatrevingtpercent" rows = "3" wrap = "soft">'.dol_escape_htmltag($object->address, 0, 1).'</textarea>';
			print $form->widgetForTranslation('address', $object, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
			print '						</td>
									</tr>';
			// Zip / Town
			print '					<tr>
										<td>'.$langs->trans('Zip').'</td>
										<td>';
			print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth100');
			print '						</td>';
			if ($conf->browser->layout == 'phone') {
				print '				</tr>
									<tr>';
			}
			print '						<td>'.$langs->trans('Town').'</td>
										<td>';
			print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			print $form->widgetForTranslation('town', $object, $permissiontoadd, 'string', 'alphanohtml', 'maxwidth100 quatrevingtpercent');
			print '						</td>
									</tr>';
			// Country
			print '					<tr>
										<td>'.$langs->trans('Country').'</td>
										<td colspan = "3">
											'.img_picto('', 'globe-americas', 'class = "paddingrightonly"');
			print $form->select_country($object->country_id, 'country_id', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
			if (!empty($user->admin)) {
				print info_admin($langs->trans('YouCanChangeValuesForThisListFromDictionarySetup'), 1);
			}
			print '						</td>
									</tr>';
			// Phone / fax
			print '					<tr>
										<td>'.$langs->trans('Phone').'</td>
										<td>
											'.img_picto('', 'object_phoning').' <input type = "text" name = "phone" id = "phone" class = "maxwidth200 widthcentpercentminusx" value = "'.$object->phone.'">
										</td>';
			if ($conf->browser->layout == 'phone') {
				print '				</tr>
									<tr>';
			}
			print '						<td>'.$langs->trans('Fax').'</td>
										<td>
											'.img_picto('', 'object_phoning_fax').' <input type = "text" name = "fax" id = "fax" class = "maxwidth200 widthcentpercentminusx" value = "'.$object->fax.'">
										</td>
									</tr>';
			// email
			print '					<tr>
										<td>'.$langs->trans('Email').'</td>
										<td colspan = "3">
											'.img_picto('', 'object_email').' <input type = "text" name = "email" id = "email" class = "maxwidth200onsmartphone maxwidth500 widthcentpercentminusx" value = "'.$object->email.'">
										</td>
									</tr>';
			// web
			print '					<tr>
										<td>'.$langs->trans('url').'</td>
										<td colspan = "3">
											'.img_picto('', 'globe').' <input type = "text" name = "url" id = "url" class = "maxwidth200onsmartphone maxwidth500 widthcentpercentminusx " value = "'.$object->url.'">
										</td>
									</tr>';
			// Notes
			print '					<tr>
										<td>'.$langs->trans('Note').'</td>
										<td colspan = "3">
											<textarea name = "note" cols = "40" rows = "6" wrap = "soft">'.dol_escape_htmltag($object->note, 0, 1).'</textarea>
										</td>
									</tr>';
			print '				</table>
								<br/>';
			// Other attributes
			$parameters = array('socid'=>$socid);
			// Note that $action and $object may be modified by hook
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
			print $hookmanager->resPrint;
			print '				<div class = "center">
									<input type = "submit" class = "button" name = "save" value = "'.$langs->trans('Save').'">';
			if (!empty($backtopage)) {
				print '				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<input type = "submit" class = "button" name = "cancel" value = "'.$langs->trans('Cancel').'">';
			}
			print '				</div>
							</form>';
		}
	} else {	// View mode
		$result	= $object->fetch_lines($socid);
		if ($result < 0) {
			dol_print_error($db,$object->error);
			exit;
		}
		// Confirmation delete
		if ($action == 'delete') {
			print $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?socid='.$socid.'&id='.$id.(!empty($backtopage) ? '&backtopage='.urlencode($backtopage) : ''), $langs->trans('InfraSPlusParamDeleteAddress'), $langs->trans('InfraSPlusParamConfirmDeleteAddress'), 'confirm_delete', '', '', 1, 200, 500, 0, 'Yes', 'No');
		}
		$nblines	= count($object->lines);
		if (!empty($nblines)) {
			for ($i = 0 ; $i < $nblines ; $i++) {
				if ($object->lines[$i]->id == $id) {
					$objectLine	= $object->lines[$i];
					break;
				}
			}
			if (!empty($objectLine)) {
				print '		<div class = "fichecenter">
								<div class = "fichehalfleft">
									<div class = "underbanner clearboth"></div>
									<table class = "border tableforfield" width = "100%">
										<tr>
											<td>'.$langs->trans('InfraSPlusParamAdressAlias').'</td>
											<td>'.$object->lines[$i]->label.'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('Name').'</td>
											<td>'.$object->lines[$i]->name.'</td>
										</tr>
										<tr>
											<td valign = "top">'.$langs->trans('Address').'</td>
											<td>'.nl2br($object->lines[$i]->address).'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('Zip').'</td>
											<td>'.$object->lines[$i]->zip.'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('Town').'</td>
											<td>'.$object->lines[$i]->town.'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('Country').'</td>
											<td>'.$object->lines[$i]->country.'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('Phone').'</td>
											<td>'.dol_print_phone($object->lines[$i]->phone, $object->lines[$i]->country_code, 0, $socid, 'AC_TEL').'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('Fax').'</td>
											<td>'.dol_print_phone($object->lines[$i]->fax, $object->lines[$i]->country_code, 0, $socid, 'AC_FAX').'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('Email').'</td>
											<td>'.$object->lines[$i]->email.'</td>
										</tr>
										<tr>
											<td>'.$langs->trans('url').'</td>
											<td>'.$object->lines[$i]->url.'</td>
										</tr>
									</table>
								</div>
								<div class = "fichehalfright">
									<div class = "ficheaddleft">
										<div class = "underbanner clearboth"></div>
										<table class = "border tableforfield" width = "100%">
											<tr>
												<td valign = "top">'.$langs->trans('Note').' :<br/>'.nl2br($object->lines[$i]->note).'</td>
											</tr>
										</table>
									</div>
								</div>
							</div>
							<div class = "clearboth"></div>';
			}
		}
		// Action button
		print '				<div class = "tabsAction">';
		$parameters	= array();
		$reshook	= $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook+
		if (empty($reshook) && $action != 'presend') {
			if (!empty($user->hasRight('societe', 'creer'))) {
				print '				<div class = "inline-block divButAction">
										<a class = "butAction" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?socid='.$socid.'&action=create&backtopage='.urlencode($backtopage).'">'.$langs->trans('Add').'</a>
									</div>';
				if (!empty($id) && !empty($objectLine)) {
					print '			<div class = "inline-block divButAction">
										<a class = "butAction" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?socid='.$socid.'&id='.$id.'&action=edit&backtopage='.urlencode($backtopage).'">'.$langs->trans('Modify').'</a>
									</div>';
				}
			}
			if (!empty($user->hasRight('societe', 'supprimer')) && !empty($id) && !empty($objectLine)) {
				print '				<div class = "inline-block divButAction">
										<a class = "butActionDelete" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?socid='.$socid.'&id='.$id.'&action=delete&backtopage='.urlencode($backtopage).'">'.$langs->trans('Delete').'</a>
									</div>';
			}
		}
		print '				</div>';
	}
	// End of page
	llxFooter();
	$db->close();
