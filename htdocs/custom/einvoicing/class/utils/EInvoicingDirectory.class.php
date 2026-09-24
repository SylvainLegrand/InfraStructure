<?php
/* InfraS add : fichier ajouté par InfraS, repris du module einvoicingsx (SYSAXES)
* Copyright (C) 2026		SYSAXES
* Copyright (C) 2026		InfraS					<technique@infras.fr>
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
* along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

/**
* \file    einvoicing/class/utils/EInvoicingDirectory.class.php
* \ingroup einvoicing
* \brief   Reception addresses of a recipient read from the AFNOR directory (XP Z12-013) of the platform,
*          enriched with the establishment details of the public French business registry, so the user
*          picks the right addressing identifier: on the thirdparty card (added to its routing list) or
*          on the invoice card (routing override of the invoice, BT-49).
*/

require_once __DIR__.'/../einvoicing.class.php';
require_once __DIR__.'/../providers/PDPProviderManager.class.php';


/**
 * Reception addresses of the AFNOR directory.
 */
class EInvoicingDirectory
{
	/**
	 * Maximum number of establishments looked up one by one in the business registry
	 */
	const MAX_REGISTRY_LOOKUPS = 25;

	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	* All the reception addresses the directory declares for a SIREN (read only, never throws).
	*
	* @param  string $siren Recipient SIREN (a SIRET is reduced to its SIREN)
	* @return array{status:string,message:string,addresses:array<int,array<string,mixed>>} status: ok|absent|error|unsupported
	*/
	public function listRecipientAddresses($siren)
	{
		$result		= array('status' => 'unsupported', 'message' => '', 'addresses' => array());
		$manager	= new PDPProviderManager($this->db);
		$provider	= $manager->getProvider(getDolGlobalString('EINVOICING_PDP'));
		if (!is_object($provider) || !method_exists($provider, 'getApiUrl') || !method_exists($provider, 'callApi') || empty($provider->getApiUrl('afnor_directory'))) {
			return $result;
		}
		$siren	= substr(preg_replace('/[^0-9]/', '', (string) $siren), 0, 9);
		if (!preg_match('/^[0-9]{9}$/', $siren)) {
			$result['status']	= 'error';
			$result['message']	= 'EInvoicingDirectoryNoSiren';
			return $result;
		}

		// No 'fields' in the search: the platforms reject directoryLineStatus there but return it with the whole line
		$body		= json_encode(array('filters' => array('siren' => array('op' => 'strict', 'value' => $siren))));
		$response	= $provider->callApi('afnor-directory/v1/directory-line/search', 'POST', $body, array(), 'precheck_directory');
		$httpcode	= (int) ($response['status_code'] ?? 0);
		if ($httpcode != 200) {
			$result['status']	= 'error';
			$result['message']	= (string) ($response['errorMessage'] ?? ('HTTP '.$httpcode));
			return $result;
		}

		$lines	= (isset($response['response']['results']) && is_array($response['response']['results'])) ? $response['response']['results'] : array();
		foreach ($lines as $line) {
			$identifier = trim((string) ($line['addressingIdentifier'] ?? ''));
			if ($identifier === '') {
				continue;
			}
			$result['addresses'][]	= array('identifier'	=> $identifier,
											'suffix'		=> (string) ($line['addressingSuffix'] ?? ''),
											'linestatus'	=> (string) ($line['directoryLineStatus'] ?? ''),
											'platform'		=> (string) ($line['platformType'] ?? ''),
											'siret'			=> (string) ($line['siret'] ?? ''),
										);
		}
		$result['status'] = (empty($result['addresses']) ? 'absent' : 'ok');
		if ($result['status'] == 'ok') {
			$result['addresses'] = $this->enrichAddresses($siren, $result['addresses']);
		}
		return $result;
	}

	/**
	* Add the establishment details (designation, postal address, town, head office) of the public French
	* business registry (recherche-entreprises.api.gouv.fr, no authentication) to the addresses, matched on
	* their SIRET. Best effort: the addresses are returned unchanged on any failure.
	*
	* @param	string							$siren		SIREN
	* @param	array<int,array<string,mixed>>	$addresses	Addresses (identifier, optional siret)
	* @return	array<int,array<string,mixed>>				Same addresses, with name, address, zip, town, is_siege when found
	*/
	public function enrichAddresses($siren, $addresses)
	{
		foreach ($addresses as $i => $a) {
			$siret = preg_replace('/[^0-9]/', '', (string) ($a['siret'] ?? ''));
			$reg = array();
			if (strlen($siret) !== 14 && preg_match('/(\d{14})/', (string) $a['identifier'], $reg)) {
				$siret = $reg[1];
			}
			$addresses[$i]['siret'] = $siret;
		}

		// One query on the SIREN gives the legal name and the head office
		$map			= array();
		$companyName	= '';
		$data			= $this->callRegistry($siren);
		foreach (($data['results'] ?? array()) as $res) {
			if (($res['siren'] ?? '') !== $siren) {
				continue;
			}
			$companyName = trim((string) ($res['nom_complet'] ?? ''));
			if (!empty($res['siege']['siret'])) {
				$map[(string) $res['siege']['siret']] = $this->formatEstablishment($res['siege'], $companyName, true);
			}
			foreach ((array) ($res['matching_etablissements'] ?? array()) as $et) {
				if (!empty($et['siret']) && !isset($map[(string) $et['siret']])) {
					$map[(string) $et['siret']] = $this->formatEstablishment($et, $companyName, false);
				}
			}
			break;
		}

		// A SIREN search does not return every establishment: look the others up one by one, within a limit
		$done = 0;
		foreach ($addresses as $a) {
			$siret = (string) $a['siret'];
			if ($siret === '' || isset($map[$siret]) || $done >= self::MAX_REGISTRY_LOOKUPS) {
				continue;
			}
			$done++;
			$data = $this->callRegistry($siret);
			foreach (($data['results'] ?? array()) as $res) {
				$name = ($companyName !== '' ? $companyName : trim((string) ($res['nom_complet'] ?? '')));
				if ((string) ($res['siege']['siret'] ?? '') === $siret) {
					$map[$siret] = $this->formatEstablishment($res['siege'], $name, true);
					break;
				}
				foreach ((array) ($res['matching_etablissements'] ?? array()) as $et) {
					if ((string) ($et['siret'] ?? '') === $siret) {
						$map[$siret] = $this->formatEstablishment($et, $name, false);
						break 2;
					}
				}
			}
		}

		foreach ($addresses as $i => $a) {
			if ($a['siret'] !== '' && isset($map[$a['siret']])) {
				$addresses[$i] = array_merge($a, $map[$a['siret']]);
			}
		}

		return $addresses;
	}

	/**
	* Add to the routing list of a thirdparty the addresses the user picked, skipping the ones already there.
	*
	* @param  int      $socid Thirdparty id
	* @param  string[] $ids   Addressing identifiers
	* @param  string[] $infos Labels of the identifiers, same keys
	* @param  string   $error Error message, filled on failure
	* @return int             Number of addresses added, <0 on error
	*/
	public function addRoutings($socid, $ids, $infos, &$error)
	{
		$einvoicing	= new EInvoicing($this->db);
		$existing	= array();
		foreach ((array) $einvoicing->fetchAllRoutings($socid) as $r) {
			$existing[(string) $r['routing_id']] = 1;
		}
		$added = 0;
		foreach ($ids as $k => $rid) {
			$rid = trim((string) $rid);
			if ($rid === '' || isset($existing[$rid])) {
				continue;
			}
			if ($einvoicing->addRouting($socid, $rid, trim((string) ($infos[$k] ?? '')), 'thirdparty') < 0) {
				$error = $einvoicing->error;
				return -1;
			}
			$existing[$rid] = 1;
			$added++;
		}
		return $added;
	}

	/**
	* Picker of the thirdparty card: button to read the directory and, once read, the addresses to add to the
	* routing list of the thirdparty.
	*
	* @param	Societe							$object			Thirdparty
	* @param	array<int,array<string,mixed>>	$allRoutings	Routings already recorded for the thirdparty
	* @return	string											HTML
	*/
	public function thirdpartyPicker($object, $allRoutings)
	{
		global $langs;

		$siren = preg_replace('/[^0-9]/', '', (string) idprof($object));
		if ($siren === '') {
			return '<div class="opacitymedium margintoponly">'.img_picto('', 'info', 'class="paddingright"').$langs->trans('EInvoicingFetchDirectoryNoSiren').'</div>';
		}

		$out = '<div class="margintoponly"><a class="button small smallpaddingimp reposition" href="'.$_SERVER['PHP_SELF'].'?id='.((int) $object->id).'&action=pdp_fetchdirectory&token='.newToken().'">';
		$out .= img_picto('', 'refresh', 'class="paddingright"').$langs->trans('EInvoicingFetchDirectoryAddresses').'</a></div>';
		if (GETPOST('action', 'aZ09') != 'pdp_fetchdirectory') {
			return $out;
		}

		$existing = array();
		foreach ($allRoutings as $r) {
			$existing[(string) $r['routing_id']] = 1;
		}
		$res = $this->listRecipientAddresses($siren);
		$out .= '<div class="margintoponly" style="border:1px solid #ddd;padding:6px;border-radius:4px;">';
		if ($res['status'] == 'ok') {
			$out .= '<div class="opacitymedium small marginbottomonly">'.$langs->trans('EInvoicingFetchDirectoryAddressesHelp').'</div>';
			$out .= $this->addressesTable($res['addresses'], 'checkbox', $existing, '');
			$out .= '<button type="button" class="button small smallpaddingimp margintoponly" onclick="einvoicingAddDirectoryRoutings()">'.$langs->trans('AddSelectedAddresses').'</button>';
			$out .= '<script>
			function einvoicingAddDirectoryRoutings() {
				var boxes = document.querySelectorAll(".einv-diraddr:checked");
				if (boxes.length === 0) { return; }
				var f = document.createElement("form");
				f.method = "post"; f.action = "'.dol_escape_js($_SERVER['PHP_SELF'].'?id='.((int) $object->id)).'";
				function add(n, v) { var i = document.createElement("input"); i.type = "hidden"; i.name = n; i.value = v; f.appendChild(i); }
				add("token", "'.dol_escape_js(newToken()).'");
				add("action", "pdp_addrouting_bulk");
				boxes.forEach(function(b) { add("sel_id[]", b.value); add("sel_info[]", b.getAttribute("data-info") || ""); });
				document.body.appendChild(f); f.submit();
			}
			</script>';
		} else {
			$out .= $this->statusMessage($res, $siren);
		}

		return $out.'</div>';
	}

	/**
	* Row of the invoice card: button to read the directory of the recipient and, once read, the addresses to
	* pick the routing override of the invoice from (saved on the thirdparty too).
	*
	* @param	Facture		$object				Invoice
	* @param	string		$currentOverride	Current routing override of the invoice
	* @return	string							HTML row, empty when not applicable
	*/
	public function invoicePickerRow($object, $currentOverride)
	{
		global $langs, $form;

		if (!is_object($object->thirdparty ?? null) && !empty($object->socid)) {
			$object->fetch_thirdparty();
		}
		if (!is_object($object->thirdparty ?? null)) {
			return '';
		}

		$out = '<tr class="treinvoicing_collapseseparator"><td class="tdtop">'.$form->textwithpicto($langs->trans('EInvoicingFetchDirectoryAddresses'), $langs->trans('EInvoicingChooseAddressInvoiceHelp')).'</td><td>';
		$siren = preg_replace('/[^0-9]/', '', (string) idprof($object->thirdparty));
		if ($siren === '') {
			return $out.'<span class="opacitymedium">'.img_picto('', 'info', 'class="paddingright"').$langs->trans('EInvoicingFetchDirectoryNoSiren').'</span></td></tr>';
		}

		$out .= '<a class="button small smallpaddingimp reposition" href="'.$_SERVER['PHP_SELF'].'?id='.((int) $object->id).'&action=einvoicing_fetchdir&token='.newToken().'">';
		$out .= img_picto('', 'refresh', 'class="paddingright"').$langs->trans('EInvoicingFetchDirectoryAddresses').'</a>';
		if (GETPOST('action', 'aZ09') == 'einvoicing_fetchdir') {
			$res = $this->listRecipientAddresses($siren);
			$out .= '<div class="margintoponly" style="border:1px solid #ddd;padding:6px;border-radius:4px;">';
			if ($res['status'] == 'ok') {
				$out .= '<div class="opacitymedium small marginbottomonly">'.$langs->trans('EInvoicingChooseAddressInvoiceHelp').'</div>';
				$out .= $this->addressesTable($res['addresses'], 'radio', array(), $currentOverride);
				$out .= '<button type="button" class="button small smallpaddingimp margintoponly" onclick="einvoicingUseDirectoryAddress()">'.$langs->trans('EInvoicingUseThisAddress').'</button>';
				$out .= '<script>
				function einvoicingUseDirectoryAddress() {
					var b = document.querySelector(".einv-diraddr:checked");
					if (!b) { return; }
					var f = document.createElement("form");
					f.method = "post"; f.action = "'.dol_escape_js($_SERVER['PHP_SELF'].'?id='.((int) $object->id)).'";
					function add(n, v) { var i = document.createElement("input"); i.type = "hidden"; i.name = n; i.value = v; f.appendChild(i); }
					add("token", "'.dol_escape_js(newToken()).'");
					add("action", "setoverriderouting");
					add("override_routing_id", b.value);
					add("override_routing_info", b.getAttribute("data-info") || "");
					document.body.appendChild(f); f.submit();
				}
				</script>';
			} else {
				$out .= $this->statusMessage($res, $siren);
			}
			$out .= '</div>';
		}

		return $out.'</td></tr>';
	}

	/**
	* Table of addresses with a checkbox (thirdparty) or a radio button (invoice) per address.
	*
	* @param	array<int,array<string,mixed>>	$addresses	Addresses
	* @param	string							$type		'checkbox' or 'radio'
	* @param	array<string,int>				$existing	Identifiers already recorded (no box, a tick instead)
	* @param	string							$current	Identifier to select by default (radio)
	* @return	string										HTML
	*/
	private function addressesTable($addresses, $type, $existing, $current)
	{
		global $langs;

		$out = '<table class="noborder centpercent"><tr class="liste_titre"><td></td>';
		$out .= '<td>'.$langs->trans('EInvoicingColSiret').'</td><td>'.$langs->trans('EInvoicingColDesignation').'</td>';
		$out .= '<td>'.$langs->trans('EInvoicingColPostalAddress').'</td><td>'.$langs->trans('EInvoicingColStatus').'</td></tr>';
		foreach ($addresses as $a) {
			$id			= (string) $a['identifier'];
			$linestatus = (string) $a['linestatus'];
			$enabled	= (strtolower($linestatus) === 'enabled');
			$siret		= (string) $a['siret'];
			$name		= trim((string) ($a['name'] ?? ''));
			$address	= trim((string) ($a['address'] ?? ''));
			$issiege	= !empty($a['is_siege']);

			// Label recorded with the routing, shown in the routing override list of the invoices
			$infoparts = array();
			if ($name !== '') {
				$infoparts[] = $name;
			}
			if (!empty($a['town'])) {
				$infoparts[] = (string) $a['town'];
			}
			if ($issiege) {
				$infoparts[] = $langs->transnoentitiesnoconv('EInvoicingHeadOffice');
			}
			$info = implode(' - ', $infoparts);
			if ($info === '') {
				$info = trim($linestatus.(!empty($a['platform']) ? ' / '.$a['platform'] : ''));
			}

			$out .= '<tr class="oddeven"><td class="tdtop" style="width:24px;">';
			if (isset($existing[$id])) {
				$out .= img_picto($langs->trans('EInvoicingAddressAlreadyLinked'), 'tick', 'class="color-green"');
			} else {
				$checked = ($type == 'radio' ? (($current !== '' && $current === $id) || ($current === '' && $enabled)) : $enabled);
				$out .= '<input type="'.$type.'" name="einv_diraddr" class="einv-diraddr" value="'.dolPrintHTMLForAttribute($id).'" data-info="'.dolPrintHTMLForAttribute($info).'"'.($checked ? ' checked' : '').'>';
			}
			$out .= '</td><td class="tdtop nowraponall">'.dol_escape_htmltag($siret !== '' ? $siret : $id);
			if ($issiege) {
				$out .= ' <span class="badge badge-status8 badge-status">'.$langs->trans('EInvoicingHeadOfficeShort').'</span>';
			}
			if ($siret !== '' && $id !== $siret) {
				$out .= '<br><span class="opacitymedium small">'.dol_escape_htmltag($id).'</span>';
			}
			$out .= '</td><td class="tdtop">'.($name !== '' ? dol_escape_htmltag($name) : '<span class="opacitymedium">-</span>').'</td>';
			$out .= '<td class="tdtop small">'.($address !== '' ? dol_escape_htmltag($address) : '<span class="opacitymedium">-</span>').'</td>';
			$out .= '<td class="tdtop"><span class="badge badge-status'.($enabled ? '4' : '1').' badge-status">'.dol_escape_htmltag($linestatus !== '' ? $linestatus : $langs->trans('Unknown')).'</span></td></tr>';
		}

		return $out.'</table>';
	}

	/**
	* Message of a directory lookup that returned no address.
	*
	* @param	array{status:string,message:string}	$res	Result of listRecipientAddresses()
	* @param	string								$siren	SIREN looked up
	* @return	string								HTML
	*/
	private function statusMessage($res, $siren)
	{
		global $langs;

		if ($res['status'] == 'absent') {
			return '<span class="opacitymedium">'.$langs->trans('EInvoicingDirectoryAbsent', $siren).'</span>';
		}
		$message = ($res['message'] !== '' ? $langs->trans($res['message']) : $langs->trans('EInvoicingDirectoryUnsupported'));

		return '<span class="opacitymedium">'.dol_escape_htmltag($message).'</span>';
	}

	/**
	* Query the public French business registry, null on any failure.
	*
	* @param  string     $q SIREN or SIRET
	* @return array|null    Decoded answer
	*/
	private function callRegistry($q)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$response = getURLContent('https://recherche-entreprises.api.gouv.fr/search?q='.urlencode((string) $q).'&per_page=1', 'GET', '', 1, array('Accept: application/json'));
		if ((int) ($response['http_code'] ?? 0) !== 200) {
			dol_syslog(__METHOD__.' recherche-entreprises HTTP '.($response['http_code'] ?? 0).' for '.$q, LOG_WARNING);
			return null;
		}
		$data = json_decode((string) $response['content'], true);

		return (!empty($data['results']) && is_array($data['results'])) ? $data : null;
	}

	/**
	* Fields of an establishment of the business registry displayed by the pickers.
	*
	* @param	array<string,mixed>	$et				Establishment
	* @param	string				$companyName	Legal name of the company
	* @param	bool				$isSiege		True for the head office
	* @return	array{name:string,address:string,zip:string,town:string,is_siege:bool}
	*/
	private function formatEstablishment($et, $companyName, $isSiege)
	{
		return array('name'		=> $companyName,
					'address'	=> trim((string) ($et['adresse'] ?? '')),
					'zip'		=> trim((string) ($et['code_postal'] ?? '')),
					'town'		=> trim((string) ($et['libelle_commune'] ?? '')),
					'is_siege'	=> ($isSiege || !empty($et['est_siege'])),
				);
	}
}
