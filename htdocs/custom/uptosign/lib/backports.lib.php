<?php

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

// some backports from dolibarr 15/16 for older versions

/**
 * 	Compute a hash and compare it to the given one
 *  For backward compatibility reasons, if the hash is not in the password_hash format, we will try to match against md5 and sha1md5
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function.
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt.
 *
 * 	@param 		string		$chain		String to hash (not hashed string)
 * 	@param 		string		$hash		hash to compare
 * 	@param		string		$type		Type of hash ('0':auto, '1':sha1, '2':sha1+md5, '3':md5, '4': for OpenLdap, '5':sha256). Use '3' here, if hash is not needed for security purpose, for security need, prefer '0'.
 * 	@return		bool					True if the computed hash is the same as the given one
 */
function utsbackports_dol_verifyHash($chain, $hash, $type = '0')
{
	// Never log $chain (the clear string being verified, e.g. a submitted securekey)
	// nor $hash (the stored hash): this function runs on public NOLOGIN pages.
	dol_syslog("utsbackports_dol_verifyHash type=$type MAIN_SECURITY_HASH_ALGO=" . utsbackports_getDolGlobalString('MAIN_SECURITY_HASH_ALGO', ''));

	if ($type == '0'
	&& (utsbackports_getDolGlobalString('MAIN_SECURITY_HASH_ALGO', '') != "")
	&& (utsbackports_getDolGlobalString('MAIN_SECURITY_HASH_ALGO', '')  == 'password_hash')
	&& function_exists('password_verify')) {
		if ($hash[0] == '$') {
			dol_syslog("utsbackports_dol_verifyHash (a) type=$type");
			return password_verify($chain, $hash);
		} elseif (strlen($hash) == 32) {
			dol_syslog("utsbackports_dol_verifyHash size is 32");
			return utsbackports_dol_verifyHash($chain, $hash, '3'); // md5
		} elseif (strlen($hash) == 40) {
			dol_syslog("utsbackports_dol_verifyHash size is 40");
			return utsbackports_dol_verifyHash($chain, $hash, '2'); // sha1md5
		}

		dol_syslog("utsbackports_dol_verifyHash else is false");
		return false;
	}
	dol_syslog("utsbackports_dol_verifyHash return call to utsbackports_dol_hash");
	return utsbackports_dol_hash($chain, $type) == $hash;
}

/**
 * 	Returns a specific ldap hash of a password.
 *
 * 	@param 		string		$password	Password to hash
 * 	@param		string		$type		Type of hash
 * 	@return		string					Hash of password
 */
function utsbackports_dolGetLdapPasswordHash($password, $type = 'md5')
{
	if (empty($type)) {
		$type = 'md5';
	}

	$salt = substr(sha1((string) time()), 0, 8);

	if ($type === 'md5') {
		return '{MD5}' . base64_encode(hash("md5", $password, true)); //For OpenLdap with md5 (based on an unencrypted password in base)
	} elseif ($type === 'md5frommd5') {
		return '{MD5}' . base64_encode(hex2bin($password)); // Create OpenLDAP MD5 password from Dolibarr MD5 password
	} elseif ($type === 'smd5') {
		return "{SMD5}" . base64_encode(hash("md5", $password . $salt, true) . $salt);
	} elseif ($type === 'sha') {
		return '{SHA}' . base64_encode(hash("sha1", $password, true));
	} elseif ($type === 'ssha') {
		return "{SSHA}" . base64_encode(hash("sha1", $password . $salt, true) . $salt);
	} elseif ($type === 'sha256') {
		return "{SHA256}" . base64_encode(hash("sha256", $password, true));
	} elseif ($type === 'ssha256') {
		return "{SSHA256}" . base64_encode(hash("sha256", $password . $salt, true) . $salt);
	} elseif ($type === 'sha384') {
		return "{SHA384}" . base64_encode(hash("sha384", $password, true));
	} elseif ($type === 'ssha384') {
		return "{SSHA384}" . base64_encode(hash("sha384", $password . $salt, true) . $salt);
	} elseif ($type === 'sha512') {
		return "{SHA512}" . base64_encode(hash("sha512", $password, true));
	} elseif ($type === 'ssha512') {
		return "{SSHA512}" . base64_encode(hash("sha512", $password . $salt, true) . $salt);
	} elseif ($type === 'crypt') {
		return '{CRYPT}' . crypt($password, $salt);
	} elseif ($type === 'clear') {
		return '{CLEAR}' . $password;  // Just for test, plain text password is not secured !
	}
	return '';
}


/**
 * 	Returns a hash of a string.
 *  If constant MAIN_SECURITY_HASH_ALGO is defined, we use this function as hashing function (recommanded value is 'password_hash')
 *  If constant MAIN_SECURITY_SALT is defined, we use it as a salt (used only if hashing algorightm is something else than 'password_hash').
 *
 * 	@param 		string		$chain		String to hash
 * 	@param		string		$type		Type of hash ('0':auto will use MAIN_SECURITY_HASH_ALGO else md5, '1':sha1, '2':sha1+md5, '3':md5, '4': for OpenLdap, '5':sha256, '6':password_hash). Use '3' here, if hash is not needed for security purpose, for security need, prefer '0'.
 * 	@return		string					Hash of string
 *  @see getRandomPassword()
 */
function utsbackports_dol_hash($chain, $type = '0')
{
	global $conf;
	dol_syslog("utsbackports_dol_hash type=$type");

	// No need to add salt for password_hash
	if (($type == '0' || $type == 'auto')
	&& (utsbackports_getDolGlobalString('MAIN_SECURITY_HASH_ALGO', '') != '')
	&& (utsbackports_getDolGlobalString('MAIN_SECURITY_HASH_ALGO', '') == 'password_hash')
	&& function_exists('password_hash')) {
		dol_syslog("utsbackports_dol_hash return password_hash");
		return password_hash($chain, PASSWORD_DEFAULT);
	}

	// Salt value
	if (!empty($conf->global->MAIN_SECURITY_SALT) && $type != '4' && $type !== 'openldap') {
		$chain = $conf->global->MAIN_SECURITY_SALT.$chain;
	}

	if ($type == '1' || $type == 'sha1') {
		return sha1($chain);
	} elseif ($type == '2' || $type == 'sha1md5') {
		return sha1(md5($chain));
	} elseif ($type == '3' || $type == 'md5') {
		return md5($chain);
	} elseif ($type == '4' || $type == 'openldap') {
		return utsbackports_dolGetLdapPasswordHash($chain, utsbackports_getDolGlobalString('LDAP_PASSWORD_HASH_TYPE', 'md5'));
	} elseif ($type == '5' || $type == 'sha256') {
		return hash('sha256', $chain);
	} elseif ($type == '6' || $type == 'password_hash') {
		return password_hash($chain, PASSWORD_DEFAULT);
	} elseif (!empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'sha1') {
		return sha1($chain);
	} elseif (!empty($conf->global->MAIN_SECURITY_HASH_ALGO) && $conf->global->MAIN_SECURITY_HASH_ALGO == 'sha1md5') {
		return sha1(md5($chain));
	}

	// No particular encoding defined, use default. Do not log $chain (may be a secret).
	dol_syslog("utsbackports_dol_hash No particular encoding defined, use default md5");
	return md5($chain);
}

/**
 * Return string with full Url
 *
 * @param   int				$mode				0=True url, 1=Url formatted with colors
 * @param   string			$type				Type of URL ('proposal', ...)
 * @param	string			$ref				Ref of object
 * @param   int     		$localorexternal  	0=Url for browser, 1=Url for external access
 * @param   CommonObject  	$obj  				object (needed to make multicompany good links)
 * @param	string 			$pdfFileChoosed		specify file to sign (important in case of file to sign is not main_doc_file)
 * @return	string								Url string
 */
function utsbackports_getOnlineSignatureUrl($mode, $type, $ref = '', $localorexternal = 1, $obj = null, $pdfFileChoosed = '')
{
	global $dolibarr_main_url_root;
	dol_syslog("utsbackports_getOnlineSignatureUrl mode=$mode, type=$type, ref=$ref, localorexternal=$localorexternal");

	if (empty($obj)) {
		// For compatibility with 15.0 -> 19.0
		global $object;
		if (empty($object)) {
			$obj = new stdClass();
		} else {
			dol_syslog("uptosign: " . __FUNCTION__." using global object is deprecated, please give obj as argument", LOG_WARNING);
			$obj = $object;
		}
	}

	//force type depends on element
	if ($obj->element == 'propal') {
		$type = 'proposal';
	} elseif ($obj->element == 'contrat') {
		$type = 'contract';
	} elseif ($obj->element == 'fichinter') {
		$type = 'fichinter';
	} elseif ($obj->element == 'project') {
		$type = 'project';
	}

	$out = '';

	//uptosign
	$urltouse = dol_buildpath('/custom/uptosign/public/newonlinesign.php', 3);


	$securekeyseed = '';

	if ($type == 'proposal') {
		$securekeyseed = utsbackports_getDolGlobalString('PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN');

		$out = $urltouse.'?source=proposal&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'proposal_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if ($mode == 1) {
			$out .= "hash('".$securekeyseed."' + '".$type."' + proposal_ref)";
		} else {
			$out .= '&securekey='.utsbackports_dol_hash($securekeyseed.$type.$ref.(isModEnabled('multicompany') ? (empty($obj->entity) ? '' : $obj->entity) : ''), '0');
		}
	} elseif ($type == 'contract') {
		$securekeyseed = utsbackports_getDolGlobalString('CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN');
		$out = $urltouse.'?source=contract&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'contract_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if ($mode == 1) {
			$out .= "hash('".$securekeyseed."' + '".$type."' + contract_ref)";
		} else {
			$out .= '&securekey='.utsbackports_dol_hash($securekeyseed.$type.$ref.(isModEnabled('multicompany') ? (empty($obj->entity) ? '' : (int) $obj->entity) : ''), '0');
		}
	} elseif ($type == 'fichinter') {
		$securekeyseed = utsbackports_getDolGlobalString('FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN');
		$out = $urltouse.'?source=fichinter&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'fichinter_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if ($mode == 1) {
			$out .= "hash('".$securekeyseed."' + '".$type."' + fichinter_ref)";
		} else {
			$out .= '&securekey='.utsbackports_dol_hash($securekeyseed.$type.$ref.(isModEnabled('multicompany') ? (empty($obj->entity) ? '' : (int) $obj->entity) : ''), '0');
		}
	} elseif ($type == 'project') {
		$securekeyseed = utsbackports_getDolGlobalString('PROJECT_ONLINE_SIGNATURE_SECURITY_TOKEN');
		$out = $urltouse.'?source=project&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'project_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if ($mode == 1) {
			$out .= "hash('".$securekeyseed."' + '".$type."' + project_ref)";
		} else {
			$out .= '&securekey='.utsbackports_dol_hash($securekeyseed.$type.$ref.(isModEnabled('multicompany') ? (empty($obj->entity) ? '' : (int) $obj->entity) : ''), '0');
		}
	} else {	// For example $type = 'societe_rib'
		dol_syslog("utsbackports_getOnlineSignatureUrl other type=$type");

		$securekeyseed = utsbackports_getDolGlobalString(dol_strtoupper($type).'_ONLINE_SIGNATURE_SECURITY_TOKEN');
		if (strpos($securekeyseed, "\0") !== false) {
			// String contains a null character that can't be encoded. Return an error to avoid fatal error later.
			return 'Invalid parameter '.dol_strtoupper($type).'_ONLINE_SIGNATURE_SECURITY_TOKEN. Contains a null character.';
		}
		dol_syslog("utsbackports_getOnlineSignatureUrl other type, securekeyseed=$securekeyseed");

		$out = $urltouse.'?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= $type.'_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if ($mode == 1) {
			$out .= "hash('".$securekeyseed."' + '".$type."' + ".$type."_ref)";
		} else {
			$out .= '&securekey='.utsbackports_dol_hash($securekeyseed.$type.$ref.(!isModEnabled('multicompany') ? '' : (empty($obj->entity) ? '' : (int) $obj->entity)), '0');
		}
	}
	dol_syslog("utsbackports_getOnlineSignatureUrl out is " . json_encode($out));

	// For multicompany
	if (!empty($out) && isModEnabled('multicompany')) {
		$out .= "&entity=".(empty($obj->entity) ? '' : (int) $obj->entity); // Check the entity because we may have the same reference in several entities
	}

	// If a file is specifyied
	if ($pdfFileChoosed) {
		$out .= '&pdfFileChoosed='.urlencode($pdfFileChoosed);
	}

	return $out;
}




/**
 * Function to concat keys of fields
 *
 * @param  CommonObject $obj Object whose $fields keys must be listed
 * @return string
 */
function utsbackports_getFieldList($obj)
{
	/*
	foreach ($object->fields as $key => $val) {
	$sql .= 't.'.$key.', ';
	}
	*/
	$keys = array_keys($obj->fields);
	return implode(',', $keys);
}

/**
 * Output the buttons to submit a creation/edit form
 *
 * @param   string  $save_label     Alternative label for save button
 * @param   string  $cancel_label   Alternative label for cancel button
 * @param   array   $morebuttons    Add additional buttons between save and cancel
 * @param   int    	$withoutdiv     Option to remove enclosing centered div
 * @param	string	$morecss		More CSS
 * @return 	string					Html code with the buttons
 */
function utsbackports_buttonsSaveCancel($save_label = 'Save', $cancel_label = 'Cancel', $morebuttons = array(), $withoutdiv = 0, $morecss = '')
{
	global $langs;

	$buttons = array();

	$save = array(
		'name' => 'save',
		'label_key' => $save_label,
	);

	if ($save_label == 'Create' || $save_label == 'Add') {
		$save['name'] = 'add';
	} elseif ($save_label == 'Modify') {
		$save['name'] = 'edit';
	}

	$cancel = array(
			'name' => 'cancel',
			'label_key' => 'Cancel',
	);

	!empty($save_label) ? $buttons[] = $save : '';

	if (!empty($morebuttons) && is_array($morebuttons)) {
		// $morebuttons is a list of button definitions: merge them one by one so the
		// render loop reads $button['name'] on each, instead of on the wrapping array.
		$buttons = array_merge($buttons, $morebuttons);
	}

	!empty($cancel_label) ? $buttons[] = $cancel : '';

	$retstring = $withoutdiv ? '' : '<div class="center">';

	foreach ($buttons as $button) {
		$addclass = empty($button['addclass']) ? '' : $button['addclass'];
		$retstring .= '<input type="submit" class="button button-'.$button['name'].($morecss ? ' '.$morecss : '').' '.$addclass.'" name="'.$button['name'].'" value="'.dol_escape_htmltag($langs->trans($button['label_key'])).'">';
	}
	$retstring .= $withoutdiv ? '' : '</div>';

	return $retstring;
}


/**
 * Return dolibarr global constant string value
 * @param string $key key to return value, return '' if not set
 * @param string $default value to return
 * @return string
 */
function utsbackports_getDolGlobalString($key, $default = '')
{
	if (function_exists('getDolGlobalString')) {
		if (((int) DOL_VERSION) < 15) {
			$res = getDolGlobalString($key);
			if (empty($res)) {
				$res = $default;
			}
			return $res;
		} else {
			return getDolGlobalString($key, $default);
		}
	}
	global $conf;
	// return $conf->global->$key ?? $default;
	return (string) (empty($conf->global->$key) ? $default : $conf->global->$key);
}

/**
 * Return the link of last main doc file for direct public download.
 *
 * @param	string	$last_main_doc		Relative path of file from document directory. Example: 'path/path2/file' or 'path/path2/*'
 * @param	string	$modulepart			Module related to document
 * @param	int		$initsharekey		Init the share key if it was not yet defined
 * @param	int		$relativelink		0=Return full external link, 1=Return link relative to root of file
 * @return	string						Link or empty string if there is no download link
 */
function utsbackports_getLastMainDocLink($last_main_doc, $modulepart, $initsharekey = 0, $relativelink = 0)
{
	global $db, $user, $dolibarr_main_url_root;

	if (empty($last_main_doc)) {
		dol_syslog("utsbackports_getLastMainDocLink last_main_doc is empty");
		return ''; // No way to known which document name to use
	}

	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	$ecmfile = new EcmFiles($db);
	$result = $ecmfile->fetch(0, '', $last_main_doc);
	if ($result < 0) {
		dol_syslog("utsbackports_getLastMainDocLink ecmfiles fetch returns " . $result);
		return '';
	}

	if (empty($ecmfile->id)) {
		// No ECM index entry for this document: we only know last_main_doc and the
		// module part, not the full path on disk, so we cannot rebuild the entry and
		// generate a share key here (the Dolibarr core leaves this case as a TODO for
		// the same reason). Returning a link now would produce a dead URL without a
		// hashp, so we return an empty string with a log instead.
		dol_syslog("utsbackports_getLastMainDocLink no ECM entry for '$last_main_doc' (modulepart=$modulepart), can not create a share key, return empty link", LOG_WARNING);
		return '';
	} elseif (empty($ecmfile->share)) {
		// Add entry into index
		if ($initsharekey) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$ecmfile->share = getRandomPassword(true);
			$ecmfile->update($user);
		} else {
			return '';
		}
	}
	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	// This is to use external domain name found into config file
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;
	$forcedownload = 0;
	$paramlink = '';
	if (!empty($ecmfile->share)) {
		$paramlink .= ($paramlink ? '&' : '').'hashp='.$ecmfile->share; // Hash for public share
	}

	if ($forcedownload) {
		$paramlink .= ($paramlink ? '&' : '').'attachment=1';
	}

	if ($relativelink) {
		$linktoreturn = 'document.php'.($paramlink ? '?'.$paramlink : '');
	} else {
		$linktoreturn = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');
	}

	// Here $ecmfile->share is defined
	return $linktoreturn;
}

if (((int) DOL_VERSION) < 11) {
	if (!function_exists('newToken')) {
		/**
		 * Return the value of token currently saved into session with name 'newtoken'.
		 * This token must be send by any POST as it will be used by next page for comparison with value in session.
		 *
		 * @return  string
		 */
		function newToken()
		{
			return $_SESSION['newtoken'];
		}
	}
}
