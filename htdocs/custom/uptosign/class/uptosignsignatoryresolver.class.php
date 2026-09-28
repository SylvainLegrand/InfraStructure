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
	 * @param  ArrayObject<int, object>  $storeArray       Result array (modified by reference)
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
		$configLabel = self::roleCodeFromConfigLabel($configLabel);

		// Handle user element type (employee)
		if ($object->element == 'user') {
			$pm = $object->personal_mobile;
			if (empty($pm)) {
				$pm = $object->user_mobile;
				dol_syslog("uptosign: resolveSigners: user personal_mobile is empty, try user_mobile: $pm");
			}
			$phone_mobile = uptoSignSearchMobile($pm, $object->office_phone, $object->country_code);
			if (empty($phone_mobile)) {
				// Do not append a user without a valid mobile (align with societe/contact branches).
				array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
				dol_syslog("uptosign: resolveSigners: user element type, without mobile phone or error format, skip", LOG_WARNING);
			} elseif (!uptosign_object_has_name($object)) {
				// Checked before the dedup list, otherwise a nameless person burns the
				// mobile number of a valid namesake sharing it
				array_push($this->errors, "UptoSignContactNameMissing");
				dol_syslog("uptosign: resolveSigners: user id " . $object->id . " has no firstname nor lastname, skip", LOG_WARNING);
			} elseif (!in_array($phone_mobile, $dedup)) {
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

				if (!uptosign_object_has_name($c)) {
					array_push($this->errors, "UptoSignContactNameMissing");
					dol_syslog("uptosign: resolveSigners: (1) contact id " . $c->id . " (" . $c->email . ") has no firstname nor lastname, skip", LOG_WARNING);
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
				$this->resolveSignersOfThirdparty(
					$socid,
					$object->element,
					$internalExternal,
					$allContactsCanSign ? '' : $configLabel,
					$storeArray,
					$dedup
				);
			}
		} else {
			dol_syslog("uptosign: resolveSigners: sign contact linked to object found");
		}

		// Add internal users from config
		if ($internalExternal == "internal") {
			$listeUsers = explode(',', utsbackports_getDolGlobalString('UPTOSIGN_DOLIBARR_USERS_SIGN', ''));
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
						if (!uptosign_object_has_name($contact)) {
							array_push($this->errors, "UptoSignContactNameMissing");
							dol_syslog("uptosign: resolveSigners: (3) contact id " . $contact->id . " (" . $contact->email . ") has no firstname nor lastname, skip", LOG_WARNING);
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
						if (!uptosign_object_has_name($oneuser)) {
							array_push($this->errors, "UptoSignContactNameMissing");
							dol_syslog("uptosign: resolveSigners: (4) user id " . $oneuser->id . " (" . $oneuser->email . ") has no firstname nor lastname, skip", LOG_WARNING);
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
		// InfraS change begin
		if (is_countable($storeArray)) {
			dol_syslog("uptosign: resolveSigners: return size array = " . count($storeArray));
		} else {
			dol_syslog("uptosign: resolveSigners: return size array = " . $storeArray->count());
		}
		// InfraS change end
	}

	/**
	 * Resolve the contacts of a third party who can sign a given kind of document
	 *
	 * Single implementation of the "who can sign for that company" question: it is
	 * used both as the fallback of resolveSigners() (nothing linked to the document
	 * itself) and as the whole answer of uptosignCore::whoCanSign(), the public API
	 * external modules call. Same rules everywhere: a signatory needs a name, an
	 * email and a usable mobile, and one mobile number counts for one person.
	 *
	 * @param  int         $socid            Third party the signatories belong to
	 * @param  string      $element          Object element the role is declared for (propal, facture, ...)
	 * @param  string      $internalExternal 'internal' or 'external'
	 * @param  string      $configLabel      Contact role code, empty means every contact can sign
	 * @param  ArrayObject<int, object> $storeArray  Result array (modified by reference)
	 * @param  array<int, string> $dedup      Mobile numbers already used (modified by reference)
	 * @param  bool        $onlyModuleRoles  Keep only the roles brought by the uptosign module
	 * @return void
	 */
	public function resolveSignersOfThirdparty($socid, $element, $internalExternal, $configLabel, ArrayObject &$storeArray, array &$dedup = array(), $onlyModuleRoles = false)
	{
		$configLabel = self::roleCodeFromConfigLabel($configLabel);

		$societe = new Societe($this->db);
		if (!$societe->fetch($socid)) {
			dol_syslog("uptosign: resolveSignersOfThirdparty: can't fetch societe id $socid", LOG_WARNING);
			return;
		}

		// Roles of that element brought by the module, when the caller asks to ignore
		// the homonym roles another module could declare
		$moduleRoleIds = array();
		if ($onlyModuleRoles) {
			$types = $this->getTypeContactCode($element, '', '', array('module' => 'uptosign'));
			if (is_array($types)) {
				$moduleRoleIds = array_keys($types);
			}
		}

		$cts = $societe->contact_array_objects();
		if (count($cts) == 0) {
			dol_syslog("uptosign: resolveSignersOfThirdparty: no contact linked to societe [$socid]");
			return;
		}

		foreach ($cts as $c) {
			$found = false;
			if ($configLabel == '') {
				$found = true;
			} else {
				$c->fetchRoles();
				if (is_array($c->roles)) {
					foreach ($c->roles as $roleid => $role) {
						if ($role['element'] != $element || $role['source'] != $internalExternal || $role['code'] != $configLabel) {
							continue;
						}
						if ($onlyModuleRoles && !in_array($role['id'], $moduleRoleIds)) {
							dol_syslog("uptosign: resolveSignersOfThirdparty: role " . $role['code'] . " of contact " . $c->id . " does not come from the uptosign module, skip");
							continue;
						}
						$found = true;
					}
				}
			}
			if (!$found) {
				dol_syslog("uptosign: resolveSignersOfThirdparty: contact " . $c->email . " has not $configLabel role");
				continue;
			}

			// The signature link is sent by email: without one the person cannot sign
			if (empty($c->email)) {
				array_push($this->errors, "UptoSignContactEmailMissing");
				dol_syslog("uptosign: resolveSignersOfThirdparty: contact id " . $c->id . " has no email, skip", LOG_WARNING);
				continue;
			}

			$phone_mobile = uptoSignSearchMobile($c->phone_mobile, $c->phone_pro, $c->country_code);
			if (empty($phone_mobile)) {
				array_push($this->errors, "UptoSignContactPhoneMobileWrongFormat");
				dol_syslog("uptosign: resolveSignersOfThirdparty: contact id " . $c->id . " (" . $c->email . ") has no usable mobile, skip", LOG_WARNING);
				continue;
			}

			// Checked before the dedup list, otherwise a nameless person burns the
			// mobile number of a valid namesake sharing it
			if (!uptosign_object_has_name($c)) {
				array_push($this->errors, "UptoSignContactNameMissing");
				dol_syslog("uptosign: resolveSignersOfThirdparty: contact id " . $c->id . " (" . $c->email . ") has no firstname nor lastname, skip", LOG_WARNING);
				continue;
			}

			if (!in_array($phone_mobile, $dedup)) {
				dol_syslog("uptosign: resolveSignersOfThirdparty: put " . $phone_mobile . " (" . $c->email . ") in dedup list");
				array_push($dedup, $phone_mobile);
				$storeArray->append($c);
			}
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

		if (!is_array($object->roles)) {
			$object->roles = array();
		}
		$duplicateID = [];
		foreach ($object->roles as $key => $val) {
			$duplicateID[] = $val['id'];
		}
		// InfraS add begin
		if (empty($object->thirdparty) && is_callable(array($object, 'fetch_thirdparty'))) {
			$object->fetch_thirdparty();
		}
		if (empty($object->thirdparty)) {
			dol_syslog("uptosign: giveAllRolesToContact thirdparty not loaded, return");
			return -1;
		}
		// InfraS add end
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
	 * Turn the label of an UptoSignConfig record into a contact role code
	 *
	 * The label IS the code of the contact type (CustomerSign, VendorSign, ...),
	 * possibly carrying a legacy "uptosign" prefix. Single place where that
	 * conversion is done, so every caller resolves the same code.
	 *
	 * @param  string $configLabel Label of the UptoSignConfig record
	 * @return string              Contact role code
	 */
	public static function roleCodeFromConfigLabel($configLabel)
	{
		return str_ireplace('uptosign', '', (string) $configLabel);
	}

	/**
	 * Tell if a contact role code is declared for an element
	 *
	 * @param  string $element Object element name (propal, commande, ...)
	 * @param  string $code    Contact role code (CustomerSign, VendorSign, ...)
	 * @param  string $source  'internal', 'external' or 'all'
	 * @return bool            True when the code exists in llx_c_type_contact
	 */
	public function isTypeContactCodeDeclared($element, $code, $source = 'all')
	{
		if ((string) $code === '') {
			dol_syslog('uptosign: isTypeContactCodeDeclared called with an empty code for element ' . $element, LOG_WARNING);
			return false;
		}

		$types = $this->getTypeContactCode($element, $source);
		if (!is_array($types)) {
			dol_syslog('uptosign: isTypeContactCodeDeclared can not read contact types of element ' . $element, LOG_ERR);
			return false;
		}

		foreach ($types as $type) {
			if (isset($type['code']) && $type['code'] === $code) {
				return true;
			}
		}

		return false;
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
