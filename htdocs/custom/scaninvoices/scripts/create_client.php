#!/usr/bin/env php
<?php
/**
 * create_client.php
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

if (!defined('NOSESSION')) define('NOSESSION', '1');

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

require_once $path."../../../master.inc.php";
dol_include_once('/scaninvoices/lib/scaninvoices_compat.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Global variables
$version = DOL_VERSION;
$error = 0;

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

if (!isset($argv[1]) || !$argv[1]) {
	print "Usage: ".$script_file." vatNumber\n";
	exit(-1);
}

$vatNumber = $argv[1];

$c = scaninvoicesApiGetCompanyDetailsWithVatNumber($vatNumber);
create_client($c);

function scaninvoicesApiGetCompanyDetailsWithVatNumber($vatNumber)
{
	global $conf, $mesg, $langs, $db;
	$scaninvoices_endpoint = scaninvoicesGetDolGlobalString('SCANINVOICES_URI');
	$retour = false;

	if (strtolower(substr($vatNumber, 0, 2)) != "fr") {
		dol_syslog("scaninvoicesApiGetCompanyDetailsWithVatNumber :: Not a french VAT number, exit");
		return $retour;
	}

	$data = new stdClass();

	$siren = substr(preg_replace('/[\W]/', '', $vatNumber), 4);
	dol_syslog("scaninvoicesApiGetCompanyDetailsWithVatNumber :: $vatNumber");

	$headers = [
		'Accept' => 'application/json',
	];

	// TODO migrate to dolibarr internal network resquests
	// $client = new GuzzleHttp\Client(['base_uri' => 'https://entreprise.data.gouv.fr/api/sirene/v3/unites_legales/']);
	// try {
	//     $res = $client->get($siren);

	//     dol_syslog('scaninvoicesApiGetCompanyDetailsWithVatNumber get status code '.$res->getStatusCode());
	//     if ($res->getStatusCode() >= 200 && $res->getStatusCode() < 300) {
	//         $jsonR = json_decode($res->getBody());

	//         dol_syslog('scaninvoicesApiGetCompanyDetailsWithVatNumber json :: '.$res->getBody());
	//         $data->name = trim($jsonR->unite_legale->denomination);
	//         //Entreprise individuelle
	//         if($data->name == null) {
	//             $data->name = trim($jsonR->unite_legale->prenom_1) . " " . trim($jsonR->unite_legale->nom);
	//         }
	//         $data->Addr1 = $jsonR->unite_legale->etablissement_siege->numero_voie.' '.$jsonR->unite_legale->etablissement_siege->type_voie.' '.$jsonR->unite_legale->etablissement_siege->libelle_voie;
	//         $data->Addr2 = '';
	//         $data->Addr3 = '';
	//         $data->AddrCP = $jsonR->unite_legale->etablissement_siege->code_postal;
	//         $data->AddrCity = $jsonR->unite_legale->etablissement_siege->libelle_commune;
	//         $data->AddrCountry = 'France';
	//         $data->CountryCode = getCountry(null, 'all', 0, '', 1, $data->AddrCountry)['code'];

	//         $data->VAT = $jsonR->unite_legale->numero_tva_intra;
	//         dol_syslog('scaninvoicesApiGetCompanyDetailsWithVatNumber OK');
	//     } else {
	//         dol_syslog('scaninvoicesApiGetCompanyDetailsWithVatNumber error 1');
	//     }
	// } catch (\Exception $e) {
	//     dol_syslog("scaninvoicesApiGetCompanyDetailsWithVatNumber error :: $e");
	// }

	dol_syslog('scaninvoicesApiGetCompanyDetailsWithVatNumber :: end');

	return $data;
}


/**
 * create_cleent : création d'un client'
 *
 * @param mixed $f
 *
 * @return void
 */
function create_client($f)
{
	global $db, $user;
	$result = 0;
	$s = new Societe($db);

	$s->name = $f->name;
	$s->email = $f->mail;
	/** @phpstan-ignore-next-line */
	$s->country_id = getCountry($f->CountryCode, '3', $db);
	$s->country = $f->AddrCountry;
	$s->country_code = $f->CountryCode;
	$s->client = 1;
	$s->tva_assuj = 1;
	$s->fournisseur = 0;
	$s->code_client = -1;
	$s->code_fournisseur = -1;
	$s->tva_intra = $f->VAT;
	$s->address = $f->Addr1.' '.$f->Addr2.' '.$f->Addr3;
	$s->zip = $f->AddrCP;
	$s->town = $f->AddrCity;

	dol_syslog('create_client with : '.json_encode($s));

	$db->begin();
	$result = $s->create($user);
	if ($result <= 0) {
		$db->rollback();
		dol_syslog('create_client Erreur : '.$s->error);
	} else {
		dol_syslog('create_client OK : '.$result);
		$db->commit();
	}

	return $result;
}
