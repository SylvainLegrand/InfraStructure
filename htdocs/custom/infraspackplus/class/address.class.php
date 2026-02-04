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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		htdocs/infraspackplus/class/address.class.php
	* 	\ingroup	InfraS
	* 	\brief		File of class to manage addresses.
	************************************************/

	// Description and activation class *************
	class Address
	{
		protected $db;
		public $element			= 'address';	// @var string ID to identify managed object
		public $table_element	= 'infraspackplus_societe_address';	// @var string Name of table without prefix where object is stored
		public $fk_element		= 'fk_soc_addr';	// @var string Field with ID of parent key if this field has a parent or for child tables)
		public $id;	//	@var int ID
		public $date_creation;
		public $date_modification;
		public $label;	// @var string Address label
		public $socid;
		public $name;
		public $address;	// @var string Address
		public $zip;
		public $town;
		public $state_id;	// @var int State ID (if used)
		public $country_id;
		public $country_code;
		public $country;
		public $phone;
		public $fax;
		public $note;
		public $email;
		public $entity;
		public $url;
		public $lines	= array();	// @var array Adresses liees a la societe
		public $error;	// @var string Error string
		public $errors	= array();	// @var array Errors

		/**
		*	Constructor.
		*	@param DoliDB $db Database handler
		**/
		function __construct($db)
		{
			$this->db = $db;
		}

		/**
		*	Create address into database
		*
		*	@param	User	$user	 Object user making creation
		*	@return int				0 if OK, < 0 if KO
		**/
		function create($user = '')
		{
			global $langs, $conf;

			$langs->load('infraspackplus@infraspackplus');

			// Nettoyage parametres
			$now			= dol_now();
			$this->label	= dol_sanitizeFileName($this->label);
			$this->name		= trim($this->name);
			if (empty($this->socid)) {
				$this->socid	= 0;
			}
			$this->entity	= ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);
			$this->db->begin();
			$result			= $this->verify();
			if ($result >= 0) {
				$sql	= 'INSERT INTO '.$this->db->prefix().'infraspackplus_societe_address (';
				$sql	.= 'datec';
				$sql	.= ', label';
				$sql	.= ', fk_soc';
				$sql	.= ', name';
				$sql	.= ', fk_user_creat';
				$sql	.= ', fk_user_modif';
				$sql	.= ', entity';
				$sql	.= ') VALUES (';
				$sql	.= '"'.$this->db->idate($now).'"';
				$sql	.= ', "'.$this->db->escape($this->label).'"';
				$sql	.= ', "'.((int) $this->socid).'"';
				$sql	.= ', "'.$this->db->escape($this->name).'"';
				$sql	.= ', "'.($user->id > 0 ? ((int) $user->id) : 'null').'"';
				$sql	.= ', "'.($user->id > 0 ? ((int) $user->id) : 'null').'"';
				$sql	.= ', "'.((int) $this->entity).'"';
				$sql	.= ')';
				dol_syslog(get_class($this).'::create', LOG_DEBUG);
				$result	= $this->db->query($sql);
				if (!empty($result)) {
					$this->id	= $this->db->last_insert_id($this->db->prefix().'infraspackplus_societe_address');
					$ret		= $this->update($this->id, $user);
					if ($ret >= 0) {
						$this->db->commit();
						return $this->id;
					} else {
						dol_syslog(get_class($this).'::create echec update');
						$this->db->rollback();
						return -3;
					}
				} else {
					if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')	$this->error	= $langs->trans('InfraSPlusParamLabelAlredyExists', $this->label);
					$this->db->rollback();
					return -2;
				}
			} else {
				$this->db->rollback();
				dol_syslog(get_class($this).'::create echec verify');
				return -1;
			}
		}

		/**
		*	Check name and label
		*
		*	@return int				0 if OK, < 0 if KO
		**/
		function verify()
		{
			global $langs;

			$langs->load('infraspackplus@infraspackplus');

			$this->label	= dol_sanitizeFileName($this->label);
			$this->name		= trim($this->name);
			$result			= 0;
			if (empty($this->name) || empty($this->label)) {
				$this->error	= $langs->trans('InfraSPlusParamLabelOrNameEmpty');
				$result			= -2;
			}
			return $result;
		}

		/**
		*	update address
		*
		*	@param	int		$id		id address
		*	@param	User	$user	Utilisateur qui demande la mise a jour
		*	@return	int				<0 if KO, >=0 if OK
		**/
		function update($id, $user = '')
		{
			global $conf, $langs;

			// Clean parameters
			$this->id			= $id;
			$this->label		= dol_sanitizeFileName($this->label);
			$this->name			= trim($this->name);
			$this->address		= trim($this->address);
			$this->zip			= trim($this->zip);
			$this->town			= trim($this->town);
			$this->country_id	= trim($this->country_id);
			$this->phone		= trim($this->phone);
			$this->fax			= trim($this->fax);
			$this->note			= trim($this->note);
			$this->email		= trim($this->email);
			$this->entity		= ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);
			$this->url			= trim($this->url);
			$result				= $this->verify();		// Verifie que name et label obligatoire
			if ($result >= 0) {
				dol_syslog(get_class($this).'::Update verify ok');
				$this->db->begin();
				$sql	= 'UPDATE '.$this->db->prefix().'infraspackplus_societe_address';
				$sql	.= ' SET label = "'.$this->db->escape($this->label).'"';	// Champ obligatoire
				$sql	.= ', fk_soc = '.($this->socid > 0 ? ((int) $this->socid) : 0);
				$sql	.= ', name = "'.$this->db->escape($this->name).'"';	// Champ obligatoire
				$sql	.= ', address = '.($this->address ? '"'.$this->db->escape($this->address).'"' : 'null');
				$sql	.= ', zip = '.($this->zip ? '"'.$this->db->escape($this->zip).'"' : 'null');
				$sql	.= ', town = '.($this->town ? '"'.$this->db->escape($this->town).'"' : 'null');
				$sql	.= ', fk_pays = "'.($this->country_id > 0 ? $this->country_id : 'NULL').'"';
				$sql	.= ', phone = '.($this->phone ? '"'.$this->db->escape($this->phone).'"' : 'null');
				$sql	.= ', fax = '.($this->fax ? '"'.$this->db->escape($this->fax).'"' : 'null');
				$sql	.= ', note = '.($this->note ? '"'.$this->db->escape($this->note).'"' : 'null');
				$sql	.= ', fk_user_modif = '.($user->id > 0 ? '"'.$this->db->escape($user->id).'"' : 'null');
				$sql	.= ', email = '.($this->email ? '"'.$this->db->escape($this->email).'"' : 'null');
				$sql	.= ', entity = '.((int) $this->entity);
				$sql	.= ', url = '.($this->url ? '"'.$this->db->escape($this->url).'"' : 'null');
				$sql	.= ' WHERE rowid = '.((int) $id);
				dol_syslog(get_class($this).'::Update', LOG_DEBUG);
				$resql	= $this->db->query($sql);
				if (!empty($resql)) {
					dol_syslog(get_class($this).'::Update success');
					$this->db->commit();
					return 1;
				} else {
					if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
						$this->error	= $langs->trans('ErrorDuplicateField', $this->label);
						$result			= -1;
					} else {
						$this->error	= $this->db->lasterror();
						$result			= -2;
					}
					$this->db->rollback();
					return $result;
				}
			}
			return 0;
		}

		/**
		*	load all company addresses on memory
		*
		*	@param	int		$socid	Id de la societe
		*	@param	int		$all	-1 => with $socid = 0 for internal addresses || 1 => return all addresses order by company name || 2 => return all addresses + company main address order by company name
		*	@return	int				> 0 (number of addresses found) si ok, 0 if no address found for this company, < 0 si ko
		**/
		function fetch_lines($socid, $all = 0)
		{
			global $langs;

			if (empty($socid) && empty($all)) {
				return -1;
			}
			$sql	= 'SELECT a.rowid, a.datec AS date_creation, a.tms AS date_modification, a.label, a.fk_soc, a.name, a.address';
			$sql	.= ', a.zip, a.town, a.fk_pays AS country_id, a.phone, a.fax, a.note, a.email, a.entity, a.url';
			$sql	.= ', c.code AS country_code, c.label AS country';
			$sql	.= ', s.nom';
			$sql	.= ' FROM '.$this->db->prefix().'infraspackplus_societe_address AS a';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'societe AS s ON a.fk_soc = s.rowid';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'c_country AS c ON a.fk_pays = c.rowid';
			$sql	.= ' WHERE a.entity IN ('.getEntity($this->element).')';
			$sql	.= $all <= 0 ? ' AND a.fk_soc = '.((int) $socid) : '';
			$sql	.= ' ORDER BY '.($all > 0 ? 's.nom ASC, ' : '').'a.label ASC';
			$resql	= $this->db->query($sql);
			if (!empty($resql)) {
				$num	= $this->db->num_rows($resql);
				if (!empty($num)) {
					for ($i = 0; $i < $num; $i++) {
						$obj						= $this->db->fetch_object($resql);
						$line						= new AddressLine($this->db);
						$line->id					= $obj->rowid;
						$line->date_creation		= $this->db->jdate($obj->date_creation);
						$line->date_modification	= $this->db->jdate($obj->date_modification);
						$line->label				= $obj->label;
						$line->socid				= $obj->fk_soc;
						$line->name					= $obj->name;
						$line->address				= $obj->address;
						$line->zip					= $obj->zip;
						$line->town					= $obj->town;
						$line->country_id			= $obj->country_id;
						$line->country_code			= $obj->country_id ? $obj->country_code : '';
						$line->country				= $obj->country_id ? ($langs->trans('Country'.$obj->country_code) != 'Country'.$obj->country_code ? $langs->trans('Country'.$obj->country_code) : $obj->country) : '';
						$line->phone				= $obj->phone;
						$line->fax					= $obj->fax;
						$line->note					= $obj->note;
						$line->email				= $obj->email;
						$line->url					= $obj->url;
						$line->soc_name				= $obj->nom;
						$this->lines[$i]			= $line;
					}
					$result	= $num;
				} else {
					$result	= 0;
				}
				$this->db->free($resql);
			} else {
				$this->error	= $this->db->lasterror();
				$this->errors[]	= $this->db->lasterror();
				return -2;
			}
			if ($all > 1) {
				$sql2	= 'SELECT s.rowid, s.datec AS date_creation, s.tms AS date_modification, "NULL" AS label, "NULL" AS fk_soc, "NULL" AS name, s.address';
				$sql2	.= ', s.zip, s.town, s.fk_pays AS country_id, s.phone, s.fax, s.note_public AS note, s.email, s.entity, s.url';
				$sql2	.= ', c.code AS country_code, c.label AS country';
				$sql2	.= ', s.nom';
				$sql2	.= ' FROM '.$this->db->prefix().'societe AS s';
				$sql2	.= ' LEFT JOIN '.$this->db->prefix().'c_country AS c ON s.fk_pays = c.rowid';
				$sql2	.= ' WHERE s.entity IN ('.getEntity($this->element).')';
				$sql2	.= ' AND s.client = 1';	// request for shipping addresses => we need only customers
				$sql2	.= ' ORDER BY s.nom ASC';
				$resql2	= $this->db->query($sql2);
				if (!empty($resql2)) {
					$num	= $this->db->num_rows($resql2);
					if (!empty($num)) {
						for ($i = 0; $i < $num; $i++) {
							$obj						= $this->db->fetch_object($resql2);
							$line						= new AddressLine($this->db);
							$line->id					= $obj->rowid;
							$line->date_creation		= $this->db->jdate($obj->date_creation);
							$line->date_modification	= $this->db->jdate($obj->date_modification);
							$line->label				= $obj->label;
							$line->socid				= $obj->fk_soc;
							$line->name					= $obj->name;
							$line->address				= $obj->address;
							$line->zip					= $obj->zip;
							$line->town					= $obj->town;
							$line->country_id			= $obj->country_id;
							$line->country_code			= $obj->country_id ? $obj->country_code : '';
							$line->country				= $obj->country_id ? ($langs->trans('Country'.$obj->country_code) != 'Country'.$obj->country_code ? $langs->trans('Country'.$obj->country_code) : $obj->country) : '';
							$line->phone				= $obj->phone;
							$line->fax					= $obj->fax;
							$line->note					= $obj->note;
							$line->email				= $obj->email;
							$line->url					= $obj->url;
							$line->soc_name				= $obj->nom;
							$this->lines[$i + $result]	= $line;
						}
						$result	+= $num;
					}
					$this->db->free($resql2);
				} else {
					$this->error	= $this->db->lasterror();
					$this->errors[]	= $this->db->lasterror();
					$result			= -3;
				}
			}
			return $result;
		}

		/**
		*	load address on memory
		*
		*	@param	int		$rowid		Id de l'adresse à charger en memoire
		*	@param	int		$socid		Id de la societe
		*	@param	string	$label		Libellé de l'adresse à charger en memoire
		*	@return	int					1 if OK, <0 if KO, 2 if two records found for same id or label, 0 if not found.
		**/
		function fetch($rowid, $socid = 0, $label = '')
		{
			global $conf, $langs;

			if (empty($rowid) && empty($label)) {
				return -1;
			}
			$sql	= 'SELECT a.rowid, a.datec AS date_creation, a.tms AS date_modification, a.label, a.fk_soc, a.name, a.address';
			$sql	.= ', a.zip, a.town, a.fk_pays AS country_id, a.phone, a.fax, a.note, a.email, a.entity, a.url';
			$sql	.= ', c.code AS country_code, c.label AS country';
			$sql	.= ' FROM '.$this->db->prefix().'infraspackplus_societe_address AS a';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'c_country AS c ON a.fk_pays = c.rowid';
			$sql	.= ' WHERE a.entity IN ('.getEntity($this->element).')';
			$sql	.= empty($rowid) ? ' AND a.fk_soc = '.((int) $socid) : '';	// no rowid so we search by label => we need to know fk_soc
			$sql	.= !empty($rowid) ? ' AND a.rowid = '.((int) $rowid) : '';
			$sql	.= !empty($label) ? ' AND a.label = "'.$this->db->escape($label).'"' : '';
			$resql	= $this->db->query($sql);
			if (!empty($resql)) {
				$num	= $this->db->num_rows($resql);
				if ($num > 1) {
					$this->error	= 'Fetch found several records. Rename one of address to avoid duplicate.';
					dol_syslog($this->error, LOG_ERR);
					return 2;
				} elseif (!empty($num)) {	// $num = 1
					$obj						= $this->db->fetch_object($resql);
					$this->id					= $obj->rowid;
					$this->date_creation 		= $this->db->jdate($obj->date_creation);
					$this->date_modification	= $this->db->jdate($obj->date_modification);
					$this->label 				= $obj->label;
					$this->socid				= $obj->fk_soc;
					$this->name 				= $obj->name;
					$this->address 				= $obj->address;
					$this->zip 					= $obj->zip;
					$this->town 				= $obj->town;
					$this->country_id 			= $obj->country_id;
					$this->country_code 		= $obj->country_id ? $obj->country_code : '';
					$this->country				= $obj->country_id ? ($langs->trans('Country'.$obj->country_code) != 'Country'.$obj->country_code ? $langs->trans('Country'.$obj->country_code) : $obj->country) : '';
					$this->phone				= $obj->phone;
					$this->fax					= $obj->fax;
					$this->note					= $obj->note;
					$this->email				= $obj->email;
					$this->url					= $obj->url;
					$result						= 1;
				} else {
					$result	= 0;
				}
				$this->db->free($resql);
			} else {
				$this->error	= $this->db->lasterror();
				$this->errors[]	= $this->db->lasterror();
				$result			= -3;
			}
			return $result;
		}

		/**
		*	Delete address
		*
		*	@param	int		$rowid	id de la societe a supprimer
		*	@return	int				>0 si ok, <0 si ko
		**/
		function delete($rowid)
		{
			dol_syslog('Address::Delete');
			$sql	= 'DELETE FROM '.$this->db->prefix().'infraspackplus_societe_address WHERE rowid = '.((int) $rowid);
			$result	= $this->db->query($sql);
			return empty($result) ? -1 : 1;
		}

		/**
		*	Return name of address with link (and eventually picto)
		*	Use $this->id, $this->label, $this->socid
		*
		*	@param		int			$withpicto		Include picto with link
		*	@param		string		$option			Where the link point to
		*	@return		string						String with URL
		**/
		function getNomUrl($withpicto = 0, $option = '')
		{
			global $langs;

			$label		= $langs->trans('ShowAddress').' : '.$this->label;
			$link		= '<a href = "'.dol_buildpath('infraspackplus', 1).'/comm/address.php?id='.$this->id.'&socid='.$this->socid.(!empty($option) ? $option : '').'" title = "'.dol_escape_htmltag($label, 1).'" class = "classfortooltip">';
			$linkend	= '</a>';
			return		$link.(!empty($withpicto) ? img_object($label, 'address', 'class = "classfortooltip"').' ' : '').$this->label.$linkend;
		}

		/**
		* 	Charge les informations d'ordre info dans l'objet societe
		*
		*	@param	int		$id	id de la societe a charger
		*	@return	void
		**/
		function info($id)
		{
			$sql	= 'SELECT s.rowid, s.nom AS name, s.datec AS date_creation, s.tms AS date_modification, s.fk_user_creat, s.fk_user_modif';
			$sql	.= ' FROM '.$this->db->prefix().'societe AS s';
			$sql	.= ' WHERE s.rowid = '.$id;
			$result	= $this->db->query($sql);
			if (!empty($result)) {
				if (!empty($this->db->num_rows($result))) {
					$obj		= $this->db->fetch_object($result);
					$this->id	= $obj->rowid;
					if (!empty($obj->fk_user_creat)) {
						$cuser					= new User($this->db);
						$cuser->fetch($obj->fk_user_creat);
						$this->user_creation	= $cuser;
					}
					if (!empty($obj->fk_user_modif)) {
						$muser						= new User($this->db);
						$muser->fetch($obj->fk_user_modif);
						$this->user_modification	= $muser;
					}
					$this->ref					= $obj->name;
					$this->date_creation		= $this->db->jdate($obj->date_creation);
					$this->date_modification	= $this->db->jdate($obj->date_modification);
				}
				$this->db->free($result);
			} else {
				dol_print_error($this->db);
			}
		}
	}

	// Class to manage one address line *************
	class AddressLine
	{
		protected $db;
		public $id;	//	@var int ID
		public $date_creation;
		public $date_modification;
		public $label;	// @var string Address label
		public $socid;
		public $name;
		public $address;	// @var string Address
		public $zip;
		public $town;
		public $country_id;
		public $country_code;
		public $country;
		public $phone;
		public $fax;
		public $note;
		public $email;
		public $entity;
		public $url;
		public $soc_name;

		/**
		*	Constructor.
		*	@param DoliDB $db Database handler
		**/
		function __construct($db)
		{
			$this->db = $db;
		}
	}
