<?php
/* Copyright 2022-2023 Éric Seigne <eric.seigne@cap-rel.fr>
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
 */

/**
 * \file        class/uptosignsignatoryresolver.class.php
 * \ingroup     uptosign
 * \brief       Resolves signatories, contact roles, and manages contact validation
 */

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

/**
 * Class UptoSignSignatoryResolver
 *
 * Centralizes signatory resolution logic: who can sign, contact role lookup,
 * and contact role assignment. Replaces duplicated whoCanSign() implementations.
 */
class UptoSignSignatoryResolver
{
	/**
	 * @var DoliDB Database handler
	 */
	private $db;

	/**
	 * @var string[] Error messages
	 */
	public $errors = array();

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
	 * Resolve the list of people who can sign a given object
	 *
	 * Handles user, societe, and contact element types. Performs phone/email
	 * validation, deduplication by phone number, and role-based filtering.
	 *
	 * @param  CommonObject $object           Business object to sign
	 * @param  string       $internalExternal 'internal' or 'external'
	 * @param  string       $configLabel      Contact role label (e.g. CustomerSign, VendorSign)
	 * @param  ArrayObject  $storeArray       Result array (modified by reference)
	 * @return void|int     -1 on error
	 */
	public function resolveSigners($object, $internalExternal, $configLabel, ArrayObject &$storeArray)
	{
		global $conf;
		dol_syslog("uptosign: UptoSignSignatoryResolver::resolveSigners object=" . $object->element . ", internalExternal=$internalExternal, configLabel=$configLabel, storeArray size=" . count($storeArray));

		$socid = null;
		$dedup = [];
		$allContactsCanSign = false;

		if ($object->element == "societe") {
			$socid = $object->id;
		} else {
			$socid = $object->socid;
		}

		if (empty($socid) && isset($object->fk_soc)) {
			$socid = $object->fk_soc;
		}

		if (empty($socid) && $object->element != 'user') {
			array_push($this->errors, "UptoSignThereIsNoSocidForThatObject");
			dol_syslog("uptosign: resolveSigners: there is no socid for that object", LOG_ERR);
			return -1;
		}

		if (utsbackports_getDolGlobalString('UPTOSIGN_CREATE_SIGN_ALL_CONTACT_LINKED', '')) {
			$configLabel = '';
			$allContactsCanSign = true;
		}

		// Remove uptosign prefix from label
		$configLabel = str_ireplace("uptosign", "", $configLabel);

		// Handle user element type (employee)
		if ($object->element == 'user') {
			$pm = $object->personal_mobile;
			if (empty($pm)) {
				$pm = $object->user_mobile;
				dol_syslog("uptosign: resolveSigners: user personal_mobile is empty, try user_mobile: $pm");
			}
			$phone_mobile = uptoSignSearchMobile($pm, $object->office_phone, $object->country_code);
			if (empty($phone_mobile)) {
				array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
				dol_syslog("uptosign: resolveSigners: user element type, without mobile phone or error format");
			}

			if (!in_array($phone_mobile, $dedup)) {
				dol_syslog("uptosign: resolveSigners: (u1) put in dedup " . $object->personal_email);
				array_push($dedup, $phone_mobile);
				$storeArray->append($object);
			}
		}
		// Handle societe element type
		elseif ($object->element == 'societe') {
			$cts = $object->contact_array_objects();
			foreach ($cts as $c) {
				$phone_mobile = uptoSignSearchMobileContact($c);
				if (empty($phone_mobile)) {
					array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
					continue;
				}

				if (!in_array($phone_mobile, $dedup)) {
					dol_syslog("uptosign: resolveSigners: (1) put in dedup " . $c->email);
					array_push($dedup, $phone_mobile);
					$storeArray->append($c);
				}
			}
		}

		// Fetch contacts linked to the object
		$contactIds = $object->getIdContact($internalExternal, $configLabel);
		if (count($contactIds) == 0) {
			dol_syslog("uptosign: resolveSigners: no sign contact linked to object with label=$configLabel, try with societe (socid=$socid)...");
			if ($internalExternal == 'external') {
				$societe = new Societe($this->db);
				$resSoc = $societe->fetch($socid);
				if ($resSoc) {
					$cts = $societe->contact_array_objects();
					foreach ($cts as $c) {
						$found = false;
						if ($allContactsCanSign) {
							$found = true;
						} elseif ($configLabel != '') {
							$c->fetchRoles();
							foreach ($c->roles as $roleid => $role) {
								if ($role['element'] == $object->element && $role['source'] == $internalExternal && $role['code'] == $configLabel) {
									$found = true;
								}
							}
						} else {
							$found = true;
						}
						if (!$found) {
							dol_syslog("uptosign: resolveSigners: contact " . $c->email . " has not $configLabel role");
							continue;
						}
						$phone_mobile = uptoSignSearchMobile($c->phone_mobile, $c->phone_pro, $c->country_code);
						if (empty($phone_mobile)) {
							array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
							continue;
						}

						if (!in_array($phone_mobile, $dedup)) {
							dol_syslog("uptosign: resolveSigners: (2) put " . $phone_mobile . " (" . $c->email . ") in dedup list");
							array_push($dedup, $phone_mobile);
							$storeArray->append($c);
						}
					}

					if (count($cts) == 0) {
						dol_syslog("uptosign: resolveSigners: no sign contact linked to societe [$socid] either");
					}
				} else {
					dol_syslog("uptosign: resolveSigners: can't fetch societe id $socid");
				}
			}
		} else {
			dol_syslog("uptosign: resolveSigners: sign contact linked to object found");
		}

		// Add internal users from config
		if ($internalExternal == "internal") {
			$listeUsers = explode(',', $conf->global->UPTOSIGN_DOLIBARR_USERS_SIGN);
			foreach ($listeUsers as $userid) {
				$contactIds[] = $userid;
				dol_syslog("uptosign: resolveSigners: add internal user $userid");
			}
		}

		// Fetch and validate each contact/user
		if (count($contactIds) > 0) {
			foreach ($contactIds as $key => $contactId) {
				if ($internalExternal == 'external') {
					$contact = new Contact($this->db);
					if ($contact->fetch($contactId) > 0) {
						if (empty($contact->email)) {
							array_push($this->errors, "UptoSignContactEmailMissing");
							continue;
						}
						if (empty($contact->country_code)) {
							$contact->country_code = 'FR';
						}

						$phone_mobile = uptoSignSearchMobile($contact->phone_mobile, $contact->phone_pro, $contact->country_code);
						if (empty($phone_mobile)) {
							array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
							continue;
						}
						if (!in_array($phone_mobile, $dedup)) {
							dol_syslog("uptosign: resolveSigners: (3) put in dedup $phone_mobile for email=$contact->email");
							array_push($dedup, $phone_mobile);
							$storeArray->append($contact);
						}
					}
				} else {
					$oneuser = new User($this->db);
					if ($oneuser->fetch($contactId) > 0) {
						if (empty($oneuser->email)) {
							array_push($this->errors, "UptoSignUserEmailMissing");
							continue;
						}
						if (empty($oneuser->country_code)) {
							$oneuser->country_code = 'FR';
						}
						$phone_mobile = uptoSignSearchMobile($oneuser->user_mobile, $oneuser->office_phone, $oneuser->country_code);
						if (empty($phone_mobile)) {
							array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
							continue;
						}
						if (!in_array($phone_mobile, $dedup)) {
							dol_syslog("uptosign: resolveSigners: (4) put in dedup " . $oneuser->email);
							array_push($dedup, $phone_mobile);
							$storeArray->append($oneuser);
						}
					}
				}
			}
		}

		if (is_countable($storeArray)) {
			dol_syslog("uptosign: resolveSigners: return size array = " . count($storeArray));
		} else {
			dol_syslog("uptosign: resolveSigners: return size array = " . $storeArray->count());
		}
	}

	/**
	 * Assign all relevant signing roles to a contact based on thirdparty type
	 *
	 * @param  Contact $object Contact object with thirdparty loaded
	 * @return int     Result of updateRoles(), or -1 if not customer/supplier
	 */
	public function giveAllRolesToContact($object)
	{
		global $conf;
		$object->fetchRoles();
		dol_syslog("uptosign: UptoSignSignatoryResolver::giveAllRolesToContact initial roles " . json_encode($object->roles));

		$duplicateID = [];
		foreach ($object->roles as $key => $val) {
			$duplicateID[] = $val['id'];
		}
		if (!is_array($object->roles)) {
			$object->roles = array();
		}

		$code = "";
		if ($object->thirdparty->client > 0) {
			$code = "'CustomerSign'";
		}
		if ($object->thirdparty->fournisseur == 1) {
			if ($code != '') {
				$code .= ",";
			}
			$code .= "'VendorSign'";
		}
		if ($code == "") {
			dol_syslog("uptosign: giveAllRolesToContact not customer, not supplier, return");
			return -1;
		}

		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "c_type_contact WHERE source='external' AND module='uptosign' AND code IN(" . $code . ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!in_array($obj->rowid, $duplicateID)) {
					dol_syslog("uptosign: giveAllRolesToContact add row " . json_encode($obj));
					$modulename = $obj->element;
					if (strpos($obj->element, 'project') !== false) {
						$modulename = 'projet';
					} elseif ($obj->element == 'contrat') {
						$modulename = 'contract';
					} elseif ($obj->element == 'action') {
						$modulename = 'agenda';
					} elseif (strpos($obj->element, 'supplier') !== false && $obj->element != 'supplier_proposal') {
						$modulename = 'fournisseur';
					}
					if (!empty($conf->{$modulename}->enabled)) {
						$object->roles[] = [
							'id' => $obj->rowid,
							'socid' => $object->socid,
							'element' => $obj->element,
							'source' => $obj->source,
							'code' => $obj->code,
							'label' => $obj->libelle,
						];
					} else {
						dol_syslog("uptosign: giveAllRolesToContact module $modulename seems to be disabled !");
					}
				}
			}
		} else {
			dol_syslog("uptosign: giveAllRolesToContact sql result empty/error " . json_encode($sql));
		}
		dol_syslog("uptosign: giveAllRolesToContact apply " . json_encode($object->roles));
		return $object->updateRoles();
	}

	/**
	 * Return array with list of possible contact type codes for an element
	 *
	 * @param  string     $element Object element name
	 * @param  string     $source  'internal', 'external' or 'all'
	 * @param  string     $order   Sort order by: 'position', 'code', 'rowid'...
	 * @param  array|null $filter  Associative array of additional filters (key => value)
	 * @return array|null          Array of contact types (id => [id, code, source]) or null on error
	 */
	public function getTypeContactCode($element, $source = 'external', $order = 'position', $filter = null)
	{
		if (empty($order)) {
			$order = 'position';
		}
		if ($order == 'position') {
			$order .= ',code';
		}
		if ($element == 'expedition' || $element == 'shipping') {
			$element = 'commande';
		}

		$tab = array();

		$sql = "SELECT DISTINCT tc.rowid, tc.source, tc.code, tc.libelle, tc.position";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
		$sql .= " WHERE tc.element='" . $this->db->escape($element) . "'";
		$sql .= " AND tc.active=1";
		if (!empty($source) && $source != 'all') {
			$sql .= " AND tc.source='" . $this->db->escape($source) . "'";
		}
		if (is_array($filter)) {
			foreach ($filter as $key => $val) {
				$sql .= " AND tc." . $key . "='" . $this->db->escape($val) . "'";
			}
		}
		$sql .= $this->db->order($order, 'ASC');

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tab[$obj->rowid] = array(
					'id' => $obj->rowid,
					'code' => $obj->code,
					'source' => $obj->source,
				);
				$i++;
			}
			return $tab;
		} else {
			array_push($this->errors, "Error " . $this->db->lasterror());
			dol_syslog("uptosign: UptoSignSignatoryResolver::getTypeContactCode " . join(',', $this->errors), LOG_ERR);
			return null;
		}
	}

	/**
	 * Return array with list of possible contact sources for an element
	 *
	 * @param  string     $element Object element name
	 * @return array|null          Array of sources (rowid => source) or null on error/empty
	 */
	public function getSourceContactCode($element)
	{
		dol_syslog('uptosign: UptoSignSignatoryResolver::getSourceContactCode for ' . $element, LOG_DEBUG);

		if (empty($element)) {
			dol_syslog('uptosign: UptoSignSignatoryResolver::getSourceContactCode element is empty, short return', LOG_DEBUG);
			return null;
		}

		if ($element == 'shipping' || $element == 'expedition') {
			$element = 'commande';
		}

		$tab = array();

		$sql = "SELECT DISTINCT tc.rowid, tc.source";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
		$sql .= " WHERE tc.element='" . $this->db->escape($element) . "'";
		$sql .= " AND tc.active=1";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tab[$obj->rowid] = $obj->source;
				$i++;
			}
			return $tab;
		} else {
			array_push($this->errors, "Error " . $this->db->lasterror());
			dol_syslog("uptosign: UptoSignSignatoryResolver::getSourceContactCode " . join(',', $this->errors), LOG_ERR);
			return null;
		}
	}
}
