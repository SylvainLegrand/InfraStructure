<?php

/**
 * api.php.
 *
 * Copyright (c) 2021 Eric Seigne <eric.seigne@cap-rel.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
define('NOTOKENRENEWAL', 1);

require_once __DIR__ . '/functions.php';
dol_include_once('/scaninvoices/lib/scaninvoices_compat.lib.php');
dol_include_once('/scaninvoices/middlewares.php');
dol_include_once('/scaninvoices/class/filestoimport.class.php');
$output = "";
$baseVerb = getenv('BASE_VERB');

// Default index page
router('GET', '^/' . $baseVerb . '/$', function () {
	html('<h3>hello world!</h3>');
});

// GET jpeg file
router('GET', 'jpgfile/(?<filename>(.*))&token=.*$', function ($params) {
	$filename = $params['filename'];
	// dol_syslog("recherche de $filename ...");
	$filePath = scaninvoicesFindpathfor($filename, DOL_DATA_ROOT . '/scaninvoices/uploads/');

	// dol_syslog("$filename trouvé dans $filePath");
	$fic = $filePath . str_replace(".pdf", ".jpg", $filename);
	if (file_exists($fic)) {
		dol_syslog('Passage du fichier : ' . $fic);
		header('Content-Description: File Transfer');
		header('Content-Type: image/jpg');
		header('Content-Disposition: attachment; filename="' . basename($fic) . '"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Length: ' . @filesize($fic));
		readfile($fic);
		exit;
	} else {
		// dol_syslog("Le fichier n'existe pas : " . $fic);
		//On demande au webservice de le générer ?
		$ficpdf = $filePath . $filename;
		if (file_exists($ficpdf)) {
			if ($retPdf2Jpeg = scaninvoicesPdf2jpeg($ficpdf, $fic)) {
				if (isset($retPdf2Jpeg['ocrid'])) {
					$ocrID = $retPdf2Jpeg['ocrid'];
					if (file_exists($fic)) {
						dol_syslog('Passage du fichier : ' . $fic);
						header('Content-Description: File Transfer');
						header('Content-Type: image/jpg');
						header('Content-Disposition: attachment; filename="' . basename($fic) . '"');
						header('Content-Transfer-Encoding: binary');
						header('Expires: 0');
						header('Pragma: public');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Cache-Control: private', false);
						header('Content-Length: ' . @filesize($fic));
						readfile($fic);
						exit;
					}
				}
			}
		}
	}
});

// get data on rect position
router('POST', 'rect', function ($params) {
	global $conf, $mesg, $langs, $db;
	$scaninvoices_endpoint = scaninvoicesGetDolGlobalString('SCANINVOICES_URI');
	$ratio = GETPOST('ratio', 'alpha');
	if (!is_numeric($ratio) && !($ratio > 0)) {
		$ratio = 1;
	}
	dol_syslog('ScanInvoices internal API::RECT On a un appel avec ' . json_encode($_POST));
	$output = [];

	$url = $scaninvoices_endpoint . '/api/ocrcuts';
	dol_syslog('ScanInvoices internal API::RECT Try to get ocr data from rect with ' . $url . ' ...');
	$rectIn = GETPOST('rect', 'array');
	$param = [
		'json' => [
			'ocrID' => GETPOST('ocrID', 'alphanohtml'),
			'rect'  => implode(":", is_array($rectIn) ? $rectIn : []),
			'ratio' => $ratio,
			'action' => 'rect',
		]
	];
	$result = getURLContent($url, 'POST', json_encode($param), 1, scanInvoicesApiCommonHeader(), ['http','https'], 2);
	scaninvoiceshandleTimeoutCheckBlacklist($result);

	$httpCode = (is_array($result) && isset($result['http_code'])) ? (int) $result['http_code'] : 0;
	$content  = (is_array($result) && isset($result['content'])) ? $result['content'] : '';

	$texte = null;
	if ($httpCode == 200 && $content !== '') {
		$json = json_decode($content);
		// ->result may come back as a JSON-encoded string or as a structure.
		$results = (is_object($json) && isset($json->result) && is_string($json->result))
			? json_decode($json->result)
			: (is_object($json) && isset($json->result) ? $json->result : null);
		if (is_object($results) && isset($results->texte) && is_scalar($results->texte)) {
			$texte = trim((string) $results->texte);
		}
	}

	if ($texte !== null) {
		$output['texte'] = $texte;

		$ratio = 1;
		$rect = is_array($rectIn) ? $rectIn : [];
		$posX = round(((float) ($rect['startX'] ?? 0)) / $ratio);
		$posY = round(((float) ($rect['startY'] ?? 0)) / $ratio);
		$largeur = round(((float) ($rect['w'] ?? 0)) / $ratio);
		$hauteur = round(((float) ($rect['h'] ?? 0)) / $ratio);
		$output['x'] = $posX;
		$output['y'] = $posY;
		$output['w'] = $largeur;
		$output['h'] = $hauteur;
	} else {
		dol_syslog(
			'ScanInvoices internal API::RECT FAILED http_code=' . $httpCode
			. ' content=' . dol_trunc((string) $content, 500),
			LOG_ERR
		);
		$output['ERRcode'] = "1121558a";
	}
	json([$output]);
});

//Demande d'import automatique d'un fichier qui est déjà stocké dans temp/
router('POST', 'importAuto', function ($params) {
	global $db, $user;

	// Capture any stray PHP output (warnings/notices from importNow() or the OCR
	// response parsing) so it can never corrupt the JSON body. Without this the
	// client gets an HTTP 200 with a non-JSON body -> "importOneInvoice error 200:
	// parsererror" and the auto-import chain silently stops (file stays "Waiting").
	ob_start();

	$object = new Filestoimport($db);

	$id = (int) GETPOST('id', 'int');
	$fournID = (int) GETPOST('fournID', 'int');

	// dol_syslog('ScanInvoices: POST importAuto :' . $_POST['id']);
	$retour = array('error' => "");
	if ($id <= 0) {
		$retour['error'] = "idNotFound " . json_encode($params);
	} elseif ($object->fetch($id) <= 0) {
		$retour['error'] = "fetchFailed id=" . $id;
	} elseif (!$object->fullImportSuccess()) {
		$retour = $object->importNow($fournID);
		if (!empty($retour['ocr_unavailable'])) {
			dol_syslog('ScanInvoices::api importAuto: OCR unavailable, returning ocr_unavailable=true to client for id=' . $id, LOG_WARNING);
		}
	} else {
		$retour['message'] = scaninvoicesMessageErreurAnalyse('DUPLICATE-001', $object->fk_supplier, $object->fk_invoice);
		$s = new Societe($db);
		if ($fournID != "") {
			$fourn = $s->fetch($fournID);
			$retour['fourn'] = $s->nom;
		} elseif ($fourn = $s->fetch($object->fk_supplier)) {
			$retour['fourn'] = $s->nom;
		}
		$facfou = new FactureFournisseur($db);
		if ($facfou->fetch($object->fk_invoice)) {
			$retour['fact'] = $facfou->getNomUrl(1, '', '', '', '', 0, 0, 0);
		}
		$retour['justif'] = $object->filename;
	}

	scaninvoicesStripStrayOutput('importAuto');
	json($retour);
});


//Start OCR stuff
router('POST', 'runocr', function ($params) {
	global $conf, $mesg, $langs, $db;

	// Capture any stray PHP output (warnings/notices) produced while processing so
	// it can never corrupt the JSON body sent to the AJAX client (which would show
	// up as a jQuery "parsererror" with an HTTP 200). Flushed by scaninvoicesRunocrFlush().
	ob_start();

	$scaninvoices_endpoint = scaninvoicesGetDolGlobalString('SCANINVOICES_URI');
	$ratio = GETPOST('ratio', 'alpha');
	if (!is_numeric($ratio) && !($ratio > 0)) {
		$ratio = 1;
	}

	$output = [
		'ratio' => $ratio,
		'error' => ""
	];

	$keys = ['fournisseurRect','fournisseurTvaRect','ladateRect','factureRect','totalhtRect','totalttcRect'];
	$jsonRect = [];
	foreach ($keys as $key) {
		// The frontend (canvascode.js) sends each zone as a "x:y:w:h" string,
		// not an array, so read it as a string. GETPOST(..., 'array') silently
		// drops a scalar and leaves jsonRect empty (false "no new zone" case).
		$val = GETPOST($key, 'alphanohtml');
		if (!empty($val)) {
			$jsonRect[$key] = $val;
		}
	}

	if (count($jsonRect) <= 0) {
		dol_syslog("ScanInvoices:runocr no data extraction zone requests ! short return");
		$mesg = '<div class="message">'.$langs->trans('runocrInfoThereIsNoNewZone') . '</div>';
		$output['error'] = $mesg;
		scaninvoicesRunocrFlush($output);
		return;
	}

	$url = $scaninvoices_endpoint . '/api/ocrcuts';
	$lang = GETPOST('lang', 'aZ09');
	dol_syslog('ScanInvoices internal API::RECT Try to get ocr data from rect with ' . $url . ' ... and lang=' . $lang);
	$param = [
		'ocrID' => GETPOST('ocrID', 'alphanohtml'),
		'filename' => GETPOST('filenamePDF', 'alphanohtml'),
		'jsonRect' => $jsonRect,
		'ratio' => $ratio,
		'action' => 'multicut',
		'lang' => $lang,
	];
	$result = getURLContent($url, 'POST', json_encode($param), 1, scanInvoicesApiCommonHeader(), ['http','https'], 2);
	scaninvoiceshandleTimeoutCheckBlacklist($result);

	// Normalize the getURLContent() result so nothing is dereferenced blindly.
	$httpCode   = (is_array($result) && isset($result['http_code'])) ? (int) $result['http_code'] : 0;
	$content    = (is_array($result) && isset($result['content'])) ? $result['content'] : '';
	$curlErrNo  = (is_array($result) && isset($result['curl_error_no'])) ? $result['curl_error_no'] : '';
	$curlErrMsg = (is_array($result) && isset($result['curl_error_msg'])) ? $result['curl_error_msg'] : '';

	$ocrOk = false;
	if ($httpCode == 200 && $content !== '') {
		$json = json_decode($content);
		// The OCR server wraps the extracted fields in a JSON string under ->result.
		// Guard every step: a malformed/unexpected body must not trigger PHP warnings
		// (which would leak into the response and break JSON parsing on the client).
		if (is_object($json) && isset($json->result)) {
			// ->result is normally a JSON-encoded string, but tolerate a structure
			// sent as-is. Decoding an object body yields a stdClass, which foreach
			// walks fine but is_iterable() rejects (it only accepts array and
			// Traversable), hence the explicit array/object test below.
			$results = is_string($json->result) ? json_decode($json->result) : $json->result;
			if (is_array($results) || is_object($results)) {
				foreach ($results as $key => $val) {
					dol_syslog('ScanInvoices internal API::RECT return ' . $key . " => " . (is_scalar($val) ? $val : json_encode($val)));
					if ($key == 'totalht' || $key == 'totalttc') {
						$output[$key] = scaninvoicesClean_amount($val);
					} else {
						$output[$key] = $val;
					}
				}
				$ocrOk = true;
			}
		}
	}

	if (!$ocrOk) {
		// Produce a clear, user-facing message plus a copy-pasteable detail block
		// so support does not have to guess. A support reference ties the UI message
		// to the server log line below.
		$supportRef = 'SIOCR-' . dol_print_date(dol_now(), '%Y%m%d-%H%M%S');

		if ($curlErrMsg !== '' || ($curlErrNo !== '' && $curlErrNo !== 0)) {
			$reason = $langs->trans('OcrErrorUnreachable');
		} elseif ($httpCode != 200) {
			$reason = $langs->trans('OcrErrorHttp', $httpCode);
		} else {
			$reason = $langs->trans('OcrErrorBadResponse');
		}

		dol_syslog(
			'ScanInvoices:runocr FAILED ref=' . $supportRef . ' http_code=' . $httpCode
			. ' curl_no=' . $curlErrNo . ' curl_msg=' . $curlErrMsg
			. ' content=' . dol_trunc((string) $content, 500),
			LOG_ERR
		);

		$output['error'] = $reason;
		$output['errorDetails'] = scaninvoicesBuildOcrErrorDetails($supportRef, $url, $httpCode, $curlErrNo, $curlErrMsg, $content);
		$output['supportRef'] = $supportRef;
	}

	scaninvoicesRunocrFlush($output);
});

// Collect client-side (JS) errors and log them server-side, so support can find
// them in dolibarr.log by reference instead of asking the user for a screenshot.
// Grep the server log with: grep 'ScanInvoices:CLIENT' dolibarr.log
router('POST', 'clientlog', function ($params) {
	ob_start();

	$level   = GETPOST('level', 'aZ09');
	$context = GETPOST('context', 'alphanohtml');
	$ref     = GETPOST('ref', 'alphanohtml');
	$message = GETPOST('message', 'nohtml');
	$details = GETPOST('details', 'nohtml');

	$logLevel = ($level === 'error') ? LOG_ERR : LOG_WARNING;
	dol_syslog(
		'ScanInvoices:CLIENT ref=' . $ref . ' context=' . $context
		. ' message=' . dol_trunc((string) $message, 300)
		. ' details=' . dol_trunc((string) $details, 2000),
		$logLevel
	);

	scaninvoicesStripStrayOutput('clientlog');
	json(['ok' => 1, 'ref' => $ref]);
});

// //on demande la creation du fournisseur a partir du num de tva
// router('POST', 'createSupplier', function ($params) {
//     global $db;
//     $output = new stdClass();

//     $numTva = substr(preg_replace('/\s+/', '', $_POST['fournisseurTva']), 0, 13);
//     $f = scaninvoicesApiGetCompanyDetailsWithVatNumber($numTva);
//     $fournisseurID = scaninvoicesCreate_supplier($f);

//     $sql = 'SELECT rowid, nom FROM '.MAIN_DB_PREFIX."societe WHERE fournisseur = 1 AND tva_intra = '".$db->escape($numTva)."'";
//     $resql = $db->query($sql);
//     if ($resql) {
//         while ($obj = $db->fetch_object($resql)) {
//             $output->data = $obj;
//         }
//     }
//     $output->fournisseurID = $fournisseurID;
//     json($output);
// });

//on demande la creation d'une facture
router('POST', 'importInvoice', function ($params) {
	global $db, $langs, $conf, $user;
	dol_syslog("scaninvoicesApi::importInvoice start");

	$scaninvoices_endpoint = scaninvoicesGetDolGlobalString('SCANINVOICES_URI');

	$data = new stdClass();
	$data->fournisseurID = GETPOST('fournID', 'int') ? GETPOST('fournID', 'int') : null;
	$data->fournisseur = GETPOST('fournisseur', 'alpha') ? GETPOST('fournisseur', 'alpha') : null;
	$data->fournisseurTva = GETPOST('fournisseurTva', 'alpha') ? GETPOST('fournisseurTva', 'alpha') : null;
	$ladate = GETPOST('ladate', 'alpha') ? GETPOST('ladate', 'alpha') : null;
	$data->ladate = scaninvoicesClean_ladate($ladate);
	$data->label = "";

	//linked to a order_supplier ?
	$data->order_supplier = GETPOST('orderID', 'int') ? GETPOST('orderID', 'int') : null;

	//    $data->label = 'Facture #'.$_POST['facture'].' du '.$_POST['ladate'];
	$data->facture = GETPOST('facture', 'alpha') ? GETPOST('facture', 'alpha') : null;

	$ht = GETPOST('totalht', 'alpha') ? GETPOST('totalht', 'alpha') : null;
	$data->ht = scaninvoicesClean_amount($ht);

	$ttc = GETPOST('totalttc', 'alpha') ? GETPOST('totalttc', 'alpha') : null;
	$data->ttc = scaninvoicesClean_amount($ttc);
	$data->fileName = GETPOST('filenamePDF', 'alpha') ? GETPOST('filenamePDF', 'alpha') : null;

	//Filtrage / pseudo validator 'fournisseurTva',
	$fieldsToCheck = ['fournisseur', 'ladate', 'facture', 'ht', 'ttc'];
	$errMsg = "";
	foreach ($fieldsToCheck as $f) {
		// dol_syslog(" *******************************************************                               verification du champ $f : " . $data->{$f});
		if (trim($data->{$f}) == "") {
			$errMsg .= $langs->trans('ERROR_FIELD_EMPTY', $f);
		}
	}
	if ($errMsg != "") {
		$data->error = 11;
		$data->message = $errMsg;
		json($data);
		return;
	}

	//Confirm/Correct OCR server of "good" values
	$url = $scaninvoices_endpoint . '/api/ocrcuts';
	// dol_syslog('ScanInvoices internal API::RECT Try to get ocr data from rect with ' . $url . ' ...');
	// Zones are sent by the frontend as "x:y:w:h" strings, not arrays.
	$jsonRect = [
		'fournisseurRect' => GETPOST('fournisseurRect', 'alphanohtml'),
		'fournisseurTvaRect' => GETPOST('fournisseurTvaRect', 'alphanohtml'),
		'ladateRect' => GETPOST('ladateRect', 'alphanohtml'),
		'factureRect' => GETPOST('factureRect', 'alphanohtml'),
		'totalhtRect' => GETPOST('totalhtRect', 'alphanohtml'),
		'totalttcRect' => GETPOST('totalttcRect', 'alphanohtml'),
	];
	$jsonConfirmValues = [
		'fournisseur' => GETPOST('fournisseur', 'alphanohtml'),
		'fournisseurTva' => GETPOST('fournisseurTva', 'alphanohtml'),
		'ladate' => GETPOST('ladate', 'alphanohtml'),
		'facture' => GETPOST('facture', 'alphanohtml'),
		'totalht' => GETPOST('totalht', 'alphanohtml'),
		'totalttc' => GETPOST('totalttc', 'alphanohtml'),
	];
	$param = [
		'ocrID' => GETPOST('ocrID', 'alphanohtml'),
		'filename' => GETPOST('filenamePDF', 'alphanohtml'),
		'jsonRect' => $jsonRect,
		'jsonConfirmValues' => $jsonConfirmValues,
		'ratio' => GETPOST('ratio', 'alpha'),
		'action' => 'confirmvalues',
	];

	$result = getURLContent($url, 'POST', json_encode($param), 1, scanInvoicesApiCommonHeader(), ['http','https'], 2);
	scaninvoiceshandleTimeoutCheckBlacklist($result);

	if (is_array($result) && $result['http_code'] == 200 && isset($result['content'])) {
		//nothing to do, confirm is ok
	} else {
		dol_syslog("scaninvoicesApi::importInvoice Bad news, can't confirm to ocr server good values but that's not a fatal error");
	}

	if (is_numeric($data->fournisseurID) && $data->fournisseurID > 0) {
		dol_syslog("scaninvoicesApi::importInvoice fournisseurID is > 0 no need to create it");
	} else {
		dol_syslog("scaninvoicesApi::importInvoice fournisseurID is null/empty/<0, TVA=" . $data->fournisseurTva);
		if ($data->fournisseurTva != '') {
			$data->fournisseurID = scaninvoicesFournisseurIdfromVAT($data->fournisseurTva);
			if ($data->fournisseurID < 0) {
				$f = scaninvoicesApiGetCompanyDetailsWithVatNumber($data->fournisseurTva);
				dol_syslog('scaninvoicesApi::importInvoice données récupérées par scaninvoicesApiGetCompanyDetailsWithVatNumber :: ' . json_encode($f));
				if (isset($f->fournisseur) && $f->fournisseur != "") {
					dol_syslog('scaninvoicesApi::importInvoice Tentative de creation du fournisseur a partir des données récupérées par scaninvoicesApiGetCompanyDetailsWithVatNumber :: ' . json_encode($f));
					$data->fournisseurID = scaninvoicesCreate_supplier($f);
				} else {
					dol_syslog("scaninvoicesApi::importInvoice Fournisseur avec ce numéro de TVA inconnu !");
				}
			}
		}
		if ($data->fournisseurID == '') {
			$data->fournisseurID = scaninvoicesFournisseurIdfromExactName($data->fournisseur);
		}
	}

	if (is_null($data->fournisseurID)) {
		dol_syslog("scaninvoicesApi::importInvoice Fournisseur introuvable et création automatique impossible");
		$data->message = html_entity_decode($langs->trans('MANUAL_IMPORT_ERROR_SUPPLIER', "<a href='" . DOL_URL_ROOT . "/societe/card.php?action=create&leftmenu=&name=" . urlencode($data->fournisseur) . "&type=f' target='_blank'>", "</a>", "<b>".$data->fournisseur."</b>"));
		$data->error = 12;
	} else {
		dol_syslog("Création d'une facture fournisseur (A)"); //for full debug . json_encode($data));
		// dol_syslog("___________________________________________________________________________________________");

		//Le produit si il a été choisi sur le dropdown
		$fournisseurProduct = GETPOST('fournisseurProduct', 'alphanohtml');
		if ($fournisseurProduct !== '' && $fournisseurProduct != -1) {
			dol_syslog(" product choosed from dropdown : id=" . $fournisseurProduct);
			$data->defaultProductID = str_replace("idprod_", "", $fournisseurProduct);
		} else {
			//Le produit par défaut s'il est configuré
			$defaultproduct = new Settings($db);
			// if (floatval(DOL_VERSION) < 20.0) {
			$resultDefProAll = $defaultproduct->fetchAll('', '', 0, 0, array('customsql' => "t.fk_soc=" . $data->fournisseurID));
			// } else {
			// 	$resultDefProAll = $defaultproduct->fetchAll('', '', 0, 0, "t.fk_soc:=:" . (int)$data->fournisseurID);
			// }
			$defaultproduct = reset($resultDefProAll);
			if ($resultDefProAll && !empty($defaultproduct->fk_default_product)) {
				$data->defaultProductID = $defaultproduct->fk_default_product;
				dol_syslog('scaninvoicesApi::importInvoice produit/service par défaut (from supplier) id='.$defaultproduct->fk_default_product);
			} elseif (scaninvoicesGetDolGlobalString('SCANINVOICES_DEFAULT_PRODUCT')) {
				$data->defaultProductID = scaninvoicesGetDolGlobalString('SCANINVOICES_DEFAULT_PRODUCT');
				dol_syslog('scaninvoicesApi::importInvoice produit/service par défaut (from module conf) (a) id='.$data->defaultProductID);
			} else {
				dol_syslog('scaninvoicesApi::importInvoice pas de produit/service par défaut pour ce fournisseur');
			}
		}
		dol_syslog("scaninvoicesApi::importInvoice Création d'une facture fournisseur (2): " . json_encode($data));
		$res = scaninvoicesCreate_fact_fournisseur($data);
		$data->message = $res['message'];
		$data->error = $res['error'];
		$data->factureurl = $res['factureurl'];
		$data->factureid = $res['factureid'];

		//L'entreprise existe on peut sauvegarder le modèle
		scaninvoicesSaveModel($data->fournisseurID);

		//import ok
		$object = new Filestoimport($db);
		$fileID = GETPOST('filestoimportId', 'int');
		// dol_syslog("Résultat obk = $fileID");
		if ($fileID) {
			$object->fetch($fileID);
		} else {
			$resultObjects = $object->fetchAll('', '', 0, 0, array('customsql' => "t.filename='" . $data->fileName . "'"));
			if ($resultObjects && is_array($resultObjects)) {
				$object = reset($resultObjects);
			}
		}
		if ($object) {
			$object->status = Filestoimport::STATUS_CLOSED;
			$object->message = "";
			$object->fk_invoice = $data->factureid;
			$object->fk_supplier = $data->fournisseurID;
			$object->update($user);
		}

		// dol_syslog("Création d'une facture fournisseur : " . json_encode($data));
		// dol_syslog("+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++");
	}
	json($data);
});

router('POST', 'scaninvoicesSaveModel', function ($params) {
	$data = scaninvoicesSaveModel('');
	json($data);
});

//
router('POST', 'supplier', function ($params) {
	global $db, $conf;
	$output = "";

	$name = '%';
	$nameRaw = GETPOST('name', 'alphanohtml');
	if (trim($nameRaw) != '') {
		$name .= $db->escape($nameRaw);
		$name .= '%';
	}

	// $output['id']   = "";
	// $output['rect']  = $rect;
	$sql = 'SELECT rowid,nom FROM ' . MAIN_DB_PREFIX . "societe WHERE fournisseur = 1 AND (nom LIKE '" . $name . "' OR name_alias LIKE '" . $name . "') and entity = ".$conf->entity;
	//$output['sql'] = $sql;
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$output[] = $obj;
		}
	}
	json($output);
});

//On passe une image pour calcul du ratio d'affichage
router('POST', 'imageInfo', function ($params) {
	$data = [];
	$ratio = 1;
	$maxHeight = (int) GETPOST('maxHeight', 'int');
	$filenamePDFRaw = GETPOST('filenamePDF', 'alphanohtml');
	$filenamePDF = basename($filenamePDFRaw);
	$filenameJPG = str_replace('.pdf', '.jpg', $filenamePDFRaw);
	$filePath = scaninvoicesFindpathfor($filenamePDF, DOL_DATA_ROOT . '/scaninvoices/uploads/');
	dol_syslog("scaninvoices api imageInfo for $filenamePDF -> $filePath");
	$nomfichierJPG = $filePath . $filenameJPG;
	@unlink($nomfichierJPG);

	if ($retPdf2Jpeg = scaninvoicesPdf2jpeg($filePath  . $filenamePDF, $nomfichierJPG)) {
		if (isset($retPdf2Jpeg['ocrid'])) {
			$data += $retPdf2Jpeg;

			$ocrID = $retPdf2Jpeg['ocrid'];
			list($width, $height, $type, $attr) = getimagesize($nomfichierJPG);

			dol_syslog("api/imageInfo, taille de l'image w=$width ,h=$height, taille max acceptée côté canvas =$maxHeight");
			if ($height > $maxHeight) {
				$ratio =  $maxHeight / $height;

				dol_syslog("api/imageInfo, image trop haute, (max=$maxHeight) calcul d'un ratio=$ratio");
				$new_w = $width * $ratio;
				$new_h = $height * $ratio;

				$outfile = imagecreatetruecolor($new_w, $new_h);
				$source = imagecreatefromjpeg($nomfichierJPG);
				// imagecopyresized($outfile, $source, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
				imagecopyresampled($outfile, $source, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
				imagejpeg($outfile, $nomfichierJPG, 100);
				imagedestroy($outfile);

				$data['ratio'] = $ratio;
				$data['width'] = $new_w;
				$data['height'] = $new_h;
			} else {
				$data['ratio'] = 1;
				$data['width'] = $width;
				$data['height'] = $height;
			}
			$data['ocrID'] = $ocrID;

			dol_syslog("calcul du ratio : le canvas propose maxHeight=" . $maxHeight . " et l'image fait $height ... résultat le ratio=$ratio");
		} else {
			dol_syslog("Erreur de conversion du pdf en jpeg !");
			$data['message'] = "convert scaninvoicesPdf2jpeg error";
		}
	}
	json($data);
});


// Route constructed with the helper
router('GET', entry('hi', '(?<name>(.*))'), function ($params) {
	html("hello {$params['name']}");
});

// In the worst case...
error('404 Not Found (1)');
