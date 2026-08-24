<?php
/* Copyright (C) 2022-2023 Eric Seigne <eric.seigne@cap-rel.fr>
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
 * \file        class/uptosignCore.class.php
 * \ingroup     uptosignCore
 * \brief       This file is a class file for uptosignCore
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/core/modules/modUptoSign.class.php');

/**
 * Class for uptosignCore : uptosignCore is designed to be used by others dolibarr modules
 * note to developpers: please don't use uptosign object, stay on uptosignCore : that is a stable
 * public interface to all uptosign internal stuff :-)
 */
class uptosignCore implements ArrayAccess
{
	protected $fillable = [
		'db',
		'src_file_name',
		'object',
		'list_of_signers',
		'procedure',
		'plugin_name',
		'seal_x',
		'seal_y',
		'seal_page',
		'title',
		'redirect_sign',
		'redirect_end',
		'hook_uri',
		'hook_key',
		'hideMailAndPhone',
		'disableSms',
		'mail_alerts'
	];

	protected $attributes = [
		'db' => null,              // database
		'src_file_name' => null,   // full file name(and path) to sign or seal
		'object' => null,          // dolibarr object
		'list_of_signers' => null, // list of people who must sign that file (if apply) array of array
		'procedure' => null,       // "sign" or "seal"
		'plugin_name' => '',       // your plugin name (Stancer, MySuperPlugin ...)
		'seal_x' => 40,            // x position (in mm) of seal stamp
		'seal_y' => 20,            // y position (in mm) of seal stamp
		'seal_page' => 1,          // page number where to put seal
		'title' => '',             // document title
		'redirect_sign' => false,  // if you want to do a transparent redirect to uptosign (header location:)
		'redirect_end' => '',      // redirect page at the end of process
		'hook_uri' => '',          // if you want to enable that feature: uptosign server could make a request on your hook uri at the end of the process
		'hook_key' => '',          // key used for that hook
		'mail_alerts' => ''        // email where alerts will be sent
	];

	/**
	 * Example of list_of_signers array
	 *
	 * 	$list_of_signers[] = array(	'id' => $contactID,
	 * 								'firstname' => $firstname,
	 * 								'lastname' => $lastname,
	 * 								'company' => $socTiers->name,
	 * 								'email' => $socTiers->email,
	 * 								'mobile' => $mobilePhoneNumber,
	 * 								'signPage' => $contactPage,
	 * 								'signPosX' => $posx,
	 * 								'signPosY' => $posy );
	 *
	 * real example:
	 * $list_of_signers = array();
	 * $list_of_signers[] = array('id' => 15,
	 * 								'firstname' => 'Eric',
	 * 								'lastname' => 'SEIGNE',
	 * 								'company' => 'CAP-REL',
	 * 								'email' => 'eric.seigne@cap-rel.fr',
	 * 								'mobile' => '+33698744401',
	 * 								'signPage' => 1,
	 * 								'signPosXx' => 80,
	 * 								'signPosY' => 100 );
	 * $list_of_signers[] = array('id' => 28,
	 * 								'firstname' => 'Client',
	 * 								'lastname' => 'SYMPA',
	 * 								'company' => 'ENTREPRISE',
	 * 								'email' => 'client.sympa@caprel.fr',
	 * 								'mobile' => '+337123123123',
	 * 								'signPage' => 1,
	 * 								'signPosX' => 20,
	 * 								'signPosY' => 100 );
	 *
	*/

	private $resultArray;
	private $error;
	private $uptosign; //"real" uptosign object

	/**
	 * Create a new instance.
	 * @param  array  $attributes array of values to set
	 *                            $attributes = [
	 *                            'db'              => "",  // dolibarr database object
	 *                            'src_file_name'   => "",  // full file name(and path) to sign or seal
	 *                            'object'          => "",  // dolibarr object
	 *                            'list_of_signers' => "",  // list of people who must sign that file (if apply) array of array
	 *                            'procedure'       => "",  // "sign" or "seal"
	 *                            'plugin_name'     => "",  // your plugin name (Stancer, MySuperPlugin ...)
	 *                            'seal_x'          => 40,  // x position (in mm) of seal stam
	 *                            'seal_y'          => 20,  // y position (in mm) of seal stamp
	 *                            'seal_page'       => 1,   // page number where to put seal
	 *                            'title'           => 'Propal 20', // document title
	 *                            'mail_alerts'		=> 'adresse.mail@de.suivi' // if you want to get informations about that process by email
	 *                            'redirect_end'	=> 'https://url.to.redirect/' // if you want to configure the landing page at the end of sign process
	 *                            ]
	 *
	 * @return void
	 */
	public function __construct(array $attributes = [])
	{
		$this->error = '';
		$this->fill($attributes);
		$this->resultArray = array();
		if (isset($this->db)) {
			$this->uptosign = new UptoSign($this->db);
		}
	}

	/**
	 * Get a data by key
	 *
	 * @param string $key The key data to retrieve
	 * @access public
	 * @return void
	 */
	public function &__get($key)
	{
		// Return a real variable reference (returning the result of getAttribute()
		// directly would raise "Only variable references should be returned").
		$value = $this->getAttribute($key);
		return $value;
	}

	/**
	 * Assigns a value to the specified data
	 *
	 * @param string $key The data key to assign the value to
	 * @param mixed  $value The value to set
	 * @access public
	 * @return bool false on error, true elsewere
	 */
	public function __set($key, $value)
	{
		return $this->setAttribute($key, $value);
	}

	/**
	 * Whether or not an data exists by key
	 *
	 * @param string $key An data key to check for
	 * @access public
	 * @return boolean
	 * @abstracting ArrayAccess
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unsets an data by key
	 *
	 * @param string $key The key to unset
	 * @access public
	 * @return void
	 */
	public function __unset($key)
	{
		if (in_array($key, $this->fillable)) {
			unset($this->attributes[$key]);
		}
	}

	/**
	 * Assigns a value to the specified offset
	 *
	 * @param string $offset The offset to assign the value to
	 * @param mixed  $value The value to set
	 * @access public
	 * @abstracting ArrayAccess
	 * @return void
	 */
	public function offsetSet($offset, $value): void
	{
		$this->setAttribute($offset, $value);
	}

	/**
	 * Whether or not an offset exists
	 *
	 * @param string $offset An offset to check for
	 * @access public
	 * @return boolean
	 * @abstracting ArrayAccess
	 */
	public function offsetExists($offset): bool
	{
		// Use isset() so a legitimate 0/'' value is reported as existing
		// (the previous !empty() reported them as missing), while a null value
		// is still considered absent.
		return isset($this->attributes[$offset]);
	}

	/**
	 * Unsets an offset
	 *
	 * @param string $offset The offset to unset
	 * @access public
	 * @return void
	 * @abstracting ArrayAccess
	 */
	public function offsetUnset($offset): void
	{
		unset($this->attributes[$offset]);
	}

	/**
	 * Returns the value at specified offset
	 *
	 * @param string $offset The offset to retrieve
	 * @access public
	 * @return mixed
	 * @abstracting ArrayAccess
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->getAttribute($offset);
	}

	/**
	 * Fill the model with an array of attributes.
	 *
	 * @param  array  $attr array to fill data
	 * @return void
	 *
	 */
	public function fill(array $attr)
	{
		foreach ($attr as $key => $value) {
			if (in_array($key, $this->fillable)) {
				if (!$this->setAttribute($key, $value)) {
					dol_syslog("uptosignCore: fill() rejected value for key '$key' (setAttribute returned false)", LOG_WARNING);
				}
			}
		}
	}

	/**
	 * Get the attributes from the fluent instance.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}


	/**
	 * setter
	 *
	 * @param   string  $key    name of member var to set
	 * @param   mixed  $value  full file name (with path)
	 *
	 * @return  bool    false if file does not exists
	 */
	public function setAttribute($key, $value)
	{
		if ($key == 'src_file_name') {
			if (is_file($value)) {
				$this->attributes[$key] = $value;
				return true;
			} else {
				// print "<p>$value n'existe pas</p>";
				$this->error = "File does not exists : " . $value;
				dol_syslog('uptosignCore: ' . $this->error, LOG_ERR);
				return false;
			}
		}
		// Reject only null (or an empty string), never a legitimate 0/false so that
		// seal_x=0 / seal_y=0 / redirect_sign=false are correctly stored.
		if (is_null($value)) {
			$this->error = "null value rejected for key : " . $key;
			dol_syslog('uptosignCore: setAttribute ' . $this->error, LOG_WARNING);
			return false;
		}
		if (is_string($value) && $value === '') {
			$this->error = "empty string rejected for key : " . $key;
			dol_syslog('uptosignCore: setAttribute ' . $this->error, LOG_WARNING);
			return false;
		}
		$this->attributes[$key] = $value;
		return true;
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key name key to get
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		if (empty($key)) {
			return null;
		}
		return $this->attributes[$key] ?? null;
	}

	/**
	 * return array with result
	 *
	 * @return  array [
	 *     'error' => "error message or '' if no error",
	 *     'json' => "json returned by docwizon",
	 *     'file_name' => "source (local) file name"
	 *     'base64_file' => "base 64 encoded file if returned by docwizon"
	 *     'base64_file_name' => "base 64 file name"
	 * ]
	 *
	 */
	public function getResult()
	{
		return $this->resultArray;
	}

	/**
	 * Return the last error message recorded by this instance
	 *
	 * @return string Error message, or '' if none
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * get link to uptosign process
	 */
	public function signLink()
	{
		dol_syslog('uptosignCore request for sign_link: ' . $this->uptosign->sign_link, LOG_DEBUG);
		return $this->uptosign->sign_link;
	}


	/**
	 * start sign or seal process
	 *
	 * @param   array  $options  [$options description]
	 * @param   User  $user     [$user description]
	 *
	 */
	public function run(array $options, User $user)
	{
		global $conf;

		$this->resultArray = array(
			'error' => '',
			'json' => '',
			'file_name' => '',
			'base64_file' => '',
			'base64_file_name' => '',
		);

		if (empty($user) || empty($user->id)) {
			dol_syslog('uptosignCore run, user is empty, early return', LOG_DEBUG);
			dol_syslog("uptosign: " . json_encode($user), LOG_DEBUG);
			$this->error = 'user is empty';
			$this->resultArray['error'] = $this->error;
			return -1;
		}

		// The UptoSign object is only built when a db handler was provided.
		if (!is_object($this->uptosign)) {
			$this->error = 'uptosignCore has no db handler, cannot run (missing db attribute)';
			dol_syslog('uptosignCore: ' . $this->error, LOG_ERR);
			$this->resultArray['error'] = $this->error;
			return -1;
		}

		if (empty($this->src_file_name) || !is_file($this->src_file_name)) {
			$this->error = 'source file to sign is missing or unreadable';
			dol_syslog('uptosignCore run: ' . $this->error, LOG_ERR);
			$this->resultArray['error'] = $this->error;
			return -1;
		}

		$fileToSign = array(
			'filename' => basename($this->src_file_name),
			'fullname' => $this->src_file_name,
			'content' => base64_encode(file_get_contents($this->src_file_name)),
			'posx' => $this->seal_x,
			'posy' => $this->seal_y,
			'signonpage' => $this->seal_page,
			'stampnumber' => '1',
			'title' => $this->title,
			'alerts' => $this->mail_alerts,
		);

		// endRedirect can come either from the run() options or from the object attribute.
		if (!empty($options['redirect_end'])) {
			$this->uptosign->endRedirect = $options['redirect_end'];
		} elseif (!empty($this->redirect_end)) {
			$this->uptosign->endRedirect = $this->redirect_end;
		}

		$res = $this->uptosign->sealOrSignInitLight(
			$user,
			$this->object,
			$fileToSign,
			$this->list_of_signers,
			$this->procedure
		);

		// Populate the result contract from what sealOrSignInitLight left on the object.
		$this->resultArray['file_name'] = $this->src_file_name;
		$this->resultArray['json'] = $this->uptosign->sign_link ?? '';
		if ($res < 0) {
			$errs = array();
			if (!empty($this->uptosign->error)) {
				$errs[] = $this->uptosign->error;
			}
			if (!empty($this->uptosign->errors) && is_array($this->uptosign->errors)) {
				$errs = array_merge($errs, $this->uptosign->errors);
			}
			$this->error = implode(', ', $errs);
			$this->resultArray['error'] = $this->error;
			dol_syslog('uptosignCore run: sealOrSignInitLight returned error res=' . $res . ' : ' . $this->error, LOG_ERR);
		}

		return $res;
	}


	/**
	 * Return list of contacts who can sign for a given thirdparty and element type
	 *
	 * Public API for external modules. Uses UptoSignSignatoryResolver internally.
	 *
	 * @param   int    $socid   Thirdparty ID
	 * @param   string $element Object element (invoice, propal, etc.)
	 * @param   string $role    Contact role code (CustomerSign, VendorSign)
	 * @return  object|ArrayObject  Single contact or array of contacts
	 */
	public function whoCanSign($socid, $element, $role = 'CustomerSign')
	{
		dol_syslog('uptoSignCore whoCanSign socid=' . $socid . ' element=' . $element . ' role=' . $role, LOG_DEBUG);

		if (!isset($this->db) || !is_object($this->db)) {
			$this->error = 'uptosignCore has no db handler, cannot resolve signers';
			dol_syslog('uptoSignCore whoCanSign: ' . $this->error, LOG_ERR);
			return new ArrayObject();
		}

		$resolver = new UptoSignSignatoryResolver($this->db);
		$typeContacts = $resolver->getTypeContactCode($element, '', '', ['module' => 'uptosign']);
		$typeContactsIds = array();
		if (is_array($typeContacts) && count($typeContacts) > 0) {
			$typeContactsIds = array_keys($typeContacts);
		}

		$societe = new Societe($this->db);
		$societe->fetch($socid);
		$contacts = $societe->contact_array_objects();

		$result = new ArrayObject();
		foreach ($contacts as $contact) {
			$contact->fetchRoles();
			foreach ($contact->roles as $key => $value) {
				if ($value['element'] == $element && $value['code'] == $role && in_array($value['id'], $typeContactsIds)) {
					$numero = uptoSignSearchMobile($contact->phone_mobile, $contact->phone_pro, $contact->country_code);
					if (empty($contact->email)) {
						dol_syslog('uptoSignCore whoCanSign contact without email: ' . $contact->id, LOG_DEBUG);
					} elseif (empty($numero)) {
						dol_syslog('uptoSignCore whoCanSign contact without mobile: ' . $contact->id, LOG_DEBUG);
					} else {
						$result->append($contact);
					}
				}
			}
		}

		dol_syslog('uptoSignCore whoCanSign result count: ' . count($result), LOG_DEBUG);
		if (count($result) == 1) {
			return $result[0];
		}
		return $result;
	}
}
