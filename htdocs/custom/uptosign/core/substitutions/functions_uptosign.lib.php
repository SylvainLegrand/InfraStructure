<?php
dol_include_once('/uptosign/lib/backports.lib.php');

/**
 * 		Function called to complete substitution array
 *
 *		@param	array		$substitutionarray	Array with substitution key=>val
 *		@param	Translate	$langs			    Output langs
 *		@param	Object		$object				Object to use to get values
 *      @param  Mixed		$parameters       	Add more parameters (useful to pass product lines)
 * 		@return	void							The entry parameter $substitutionarray is modified
 */
function uptosign_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters = null)
{
	global $conf;

	include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
	include_once DOL_DOCUMENT_ROOT . '/core/lib/signature.lib.php';

	if (is_object($object) && ($object->id > 0 || $object->specimen)) {
		dol_syslog("uptosign_completesubstitutionarray '".$object->id."' element=" . $object->element . ", DOL_VER=" . DOL_VERSION);
		$substitutionarray['uptosign_uri'] = "https://uptosign.com";
		$official = utsbackports_getOnlineSignatureUrl(0, $object->element, $object->ref, 1, $object);

		//race condition propal -> proposal
		// if ($object->element == "propal") {
		// 	if ((((int) DOL_VERSION) < 15)) {
		// 		$official = utsbackports_getOnlineSignatureUrl(0, 'proposal', $object->ref);
		// 	} else {
		// 		$official = getOnlineSignatureUrl(0, 'proposal', $object->ref);
		// 	}
		// } else {
		// 	$official = utsbackports_getOnlineSignatureUrl(0, $object->element, $object->ref);
		// }

		// if ($object->element == "commande") {
		// 	$official = preg_replace("/proposal/", preg_quote("commande"), $official);
		// }

		$uptosign = preg_replace("/public\/onlinesign/", preg_quote("custom/uptosign/public"), $official);

		$substitutionarray['__ONLINE_SIGN_URL__'] = $uptosign;
		dol_syslog("uptosign_completesubstitutionarray " . json_encode($substitutionarray));
	} else {
		dol_syslog("uptosign_completesubstitutionarray is not object or no id set");
	}
}
