<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>   InfraS - <https://www.infras.fr>
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
	*	\file		./multismtp/class/multismtp_smtp2go.class.php
	*	\ingroup	MultiSMTP
	*	\brief		Class for MultiSMTP SMTP2GO management (CRUD)
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

	/**
	 * Class for MultiSMTP SMTP2GO management (CRUD)
	 */
	class MultiSMTP_Smtp2go extends CommonObject
	{
		/**
		 * @var string ID to identify managed object
		 */
		public $element = 'multismtp_smtp2go';
		/**
		 * @var string Name of table without prefix where object is stored
		 */
		public $table_element = 'user_smtp2go';
		/**
		 * @var int ID
		 */
		public $id;
		/**
		 * @var string  Login of the SMTP2GO account.
		 */
		public $username;
		/**
		 * @var string  Description of the SMTP2GO account.
		 */
		public $description;
		/**
		 * @var string  Password of the SMTP2GO account (stored encrypted).
		 */
		public $password;
		/**
		 * @var int|null  FK of the Dolibarr user linked to this SMTP2GO account (null = not linked).
		 */
		public $fk_user					= null;
		/**
		 * @var string  Subaccount ID of the SMTP2GO account (if any).
		 */
		public $subaccount_id				= '';
		/**
		 * @var int  Whether a custom rate limit is set for this SMTP2GO account (1) or not (0).
		 */
		public $custom_ratelimit;
		/**
		 * @var int  Value of the custom rate limit for this SMTP2GO account (if set).
		 */
		public $custom_ratelimit_value;
		/**
		 * @var string  Period of the custom rate limit for this SMTP2GO account (if set).
		 */
		public $custom_ratelimit_period;
		/**
		 * @var int  Whether the default rate limit is used for this SMTP2GO account (1) or not (0).
		 */
		public $ratelimit_default;
		/**
		 * @var int  Whether archiving is enabled for this SMTP2GO account (1) or not (0).
		 */
		public $archive_enabled;
		/**
		 * @var int  Whether open tracking is enabled for this SMTP2GO account (1) or not (0).
		 */
		public $open_tracking_enabled;
		/**
		 * @var int  Whether click tracking is enabled for this SMTP2GO account (1) or not (0).
		 */
		public $click_tracking_enabled;
		/**
		 * @var int  Whether feedback is enabled for this SMTP2GO account (1) or not (0).
		 */
		public $feedback_enabled			= 0;
		/**
		 * @var string  Domain for feedback notifications for this SMTP2GO account.
		 */
		public $feedback_domain				= '';
		/**
		 * @var string  HTML content for feedback notifications for this SMTP2GO account.
		 */
		public $feedback_html				= '';
		/**
		 * @var string  Text content for feedback notifications for this SMTP2GO account.
		 */
		public $feedback_text				= '';
		/**
		 * @var string  Email address for audit notifications for this SMTP2GO account.
		 */
		public $audit_email;
		/**
		 * @var string  Email address for bounce notifications for this SMTP2GO account.
		 */
		public $bounce_notifications;
		/**
		 * @var int  Whether two-factor authentication is enforced for this SMTP2GO account (1) or not (0).
		 */
		public $enforce_2fa					= 0;
		/**
		 * @var int  Whether SMS notifications are enabled for this SMTP2GO account (1) or not (0).
		 */
		public $enable_sms					= 0;
		/**
		 * @var int  SMS limit for this SMTP2GO account.
		 */
		public $sms_limit					= 0;
		/**
		 * @var int  Entity ID for this SMTP2GO account.
		 */
		public $entity;
		/**
		 * @var int  Last modification timestamp for this SMTP2GO account.
		 */
		public $tms;
		/**
		 * @var string  Summary message set by fetchAllSmtp2go() (cron-compatible output).
		 */
		public $output = '';
		/**
		 * @var array Array with all fields and their property
		 */
		public $fields  = array('rowid'						=> array('type' => 'integer',		'label' => 'TechnicalID',				'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 1),
								'username'					=> array('type' => 'varchar(100)',	'label' => 'Login',						'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 10, 'searchall' => 1, 'css' => 'minwidth200'),
								'description'				=> array('type' => 'varchar(255)',	'label' => 'Description',				'enabled' => 1, 'visible' => 1, 'position' => 20, 'css' => 'minwidth200'),
								'password'					=> array('type' => 'password',		'label' => 'Password',					'enabled' => 1, 'visible' => 0, 'position' => 30),
								'fk_user'					=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'User', 'enabled' => 1, 'visible' => 1, 'notnull' => 0, 'position' => 40, 'css' => 'maxwidth500 widthcentpercentminusxx'),
								'custom_ratelimit'			=> array('type' => 'integer',		'label' => 'Smtp2goCustomRatelimit',	'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 0, 'position' => 50),
								'custom_ratelimit_value'	=> array('type' => 'integer',		'label' => 'Smtp2goRatelimitValue',	'	enabled' => 1, 'visible' => 1, 'position' => 51),
								'custom_ratelimit_period'	=> array('type' => 'varchar(50)',	'label' => 'Smtp2goRatelimitPeriod',	'enabled' => 1, 'visible' => 1, 'position' => 52),
								'ratelimit_default'			=> array('type' => 'integer',		'label' => 'Smtp2goDefaultCustomRatelimit', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 0, 'position' => 53),
								'archive_enabled'			=> array('type' => 'integer',		'label' => 'Smtp2goArchiving',			'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 0, 'position' => 60),
								'open_tracking_enabled'		=> array('type' => 'integer',		'label' => 'Smtp2goOpenTracking',		'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 0, 'position' => 61),
								'click_tracking_enabled'	=> array('type' => 'integer',		'label' => 'Smtp2goClickTracking',		'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 0, 'position' => 62),
								'audit_email'				=> array('type' => 'mail',			'label' => 'Smtp2goAuditEmail',			'enabled' => 1, 'visible' => 1, 'position' => 70),
								'bounce_notifications'		=> array('type' => 'varchar(255)',	'label' => 'Smtp2goBounceNotif',		'enabled' => 1, 'visible' => 1, 'position' => 80),
								'entity'					=> array('type' => 'integer',		'label' => 'Entity',					'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'default' => 1, 'index' => 1, 'position' => 100),
								'tms'						=> array('type' => 'timestamp',	'label' => 'DateModification',				'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 500),
							);

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
		* Fill object properties from a SMTP2GO API data array (either the params sent to
		* users/smtp/add|edit, or a record returned by users/smtp/view). Does not save anything.
		*
		* @param	array	$data	Associative array of API parameters
		* @return	void
		*/
		public function fillFromApiData($data)
		{
			$stringfields	= array('username', 'email_password', 'description', 'subaccount_id', 'custom_ratelimit_period',
									'feedback_domain', 'feedback_html', 'feedback_text', 'audit_email', 'bounce_notifications');
			$intfields		= array('custom_ratelimit_value', 'sms_limit', 'ip_pool');
			$boolfields		= array('open_tracking_enabled', 'click_tracking_enabled', 'archive_enabled', 'custom_ratelimit',
									'enforce_2fa', 'enable_sms', 'feedback_enabled');

			foreach ($stringfields as $field) {
				if (array_key_exists($field, $data)) {
					$this->$field	= (string) $data[$field];
				}
			}
			foreach ($intfields as $field) {
				if (array_key_exists($field, $data)) {
					$this->$field	= (int) $data[$field];
				}
			}
			foreach ($boolfields as $field) {
				if (array_key_exists($field, $data)) {
					$this->$field	= empty($data[$field]) ? 0 : 1;
				}
			}
		}

		/**
		* Create object into database
		*
		* @param	User	$user		User that creates (unused, kept for the standard Dolibarr CRUD signature)
		* @param	int		$notrigger	1=Does not execute triggers, 0=Execute triggers
		* @return	int					<0 if KO, Id of created object if OK
		*/
		public function create($user = null, $notrigger = 0)
		{
			global $conf;

			$error	= 0;
			// Clean parameters
			if (isset($this->fk_user)) {
				$this->fk_user					    = (int) $this->fk_user;
			}
			if (isset($this->username)) {
				$this->username					    = trim($this->username);
			}
			if (isset($this->description)) {
				$this->description					= trim($this->description);
			}
			if (isset($this->password)) {
				$this->password						= trim($this->password);
			}
			if (isset($this->custom_ratelimit_period)) {
				$this->custom_ratelimit_period		= trim($this->custom_ratelimit_period);
			}
			if (isset($this->audit_email)) {
				$this->audit_email					= trim($this->audit_email);
			}
			if (isset($this->bounce_notifications)) {
				$this->bounce_notifications			= trim($this->bounce_notifications);
			}
			// Check parameters
			if (empty($this->username)) {
				$this->error = 'ErrorBadParameters: username is required';
				return -1;
			}
			$this->db->begin();
			// Insert request
			$sql	= 'INSERT INTO '.$this->db->prefix().$this->table_element.' (';
			$sql	.= 'username,';
			$sql	.= ' description,';
			$sql	.= ' password,';
			$sql	.= ' fk_user,';
			$sql	.= ' subaccount_id,';
			$sql	.= ' custom_ratelimit,';
			$sql	.= ' custom_ratelimit_value,';
			$sql	.= ' custom_ratelimit_period,';
			$sql	.= ' ratelimit_default,';
			$sql	.= ' enforce_2fa,';
			$sql	.= ' enable_sms,';
			$sql	.= ' sms_limit,';
			$sql	.= ' archive_enabled,';
			$sql	.= ' open_tracking_enabled,';
			$sql	.= ' click_tracking_enabled,';
			$sql	.= ' audit_email,';
			$sql	.= ' feedback_enabled,';
			$sql	.= ' feedback_domain,';
			$sql	.= ' feedback_html,';
			$sql	.= ' feedback_text,';
			$sql	.= ' bounce_notifications,';
			$sql	.= ' entity';
			$sql	.= ') VALUES (';
			$sql	.= "'".$this->db->escape($this->username)."',";
			$sql	.= (!empty($this->description) ? "'".$this->db->escape($this->description)."'" : "null").",";
			$sql	.= (!empty($this->password) ? "'".$this->db->escape($this->password)."'" : "null").",";
			$sql	.= (!empty($this->fk_user) ? ((int) $this->fk_user) : "null").",";
			$sql	.= ((int) $this->subaccount_id).",";
			$sql	.= ((int) $this->custom_ratelimit).",";
			$sql	.= (!empty($this->custom_ratelimit_value) ? (int) $this->custom_ratelimit_value : "null").",";
			$sql	.= (!empty($this->custom_ratelimit_period) ? "'".$this->db->escape($this->custom_ratelimit_period)."'" : "null").",";
			$sql	.= ((int) $this->ratelimit_default).",";
			$sql	.= ((int) $this->enforce_2fa).",";
			$sql	.= ((int) $this->enable_sms).",";
			$sql	.= ((int) $this->sms_limit).",";
			$sql	.= ((int) $this->archive_enabled).",";
			$sql	.= ((int) $this->open_tracking_enabled).",";
			$sql	.= ((int) $this->click_tracking_enabled).",";
			$sql	.= (!empty($this->audit_email) ? "'".$this->db->escape($this->audit_email)."'" : "null").",";
			$sql	.= ((int) $this->feedback_enabled).",";
			$sql	.= (!empty($this->feedback_domain) ? "'".$this->db->escape($this->feedback_domain)."'" : "null").",";
			$sql	.= (!empty($this->feedback_html) ? "'".$this->db->escape($this->feedback_html)."'" : "null").",";
			$sql	.= (!empty($this->feedback_text) ? "'".$this->db->escape($this->feedback_text)."'" : "null").",";
			$sql	.= (!empty($this->bounce_notifications) ? "'".$this->db->escape($this->bounce_notifications)."'" : "null").",";
			$sql	.= ((int) $conf->entity);
			$sql	.= ')';
			// InfraS change : removed debug dol_syslog() of the full INSERT SQL (logged the encrypted password value at LOG_DEBUG, avoidable)
			$resql	= $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
			}
			if (!$error) {
				$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
			}
			// Commit or rollback
			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this).'::create '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			} else {
				$this->db->commit();
				return $this->id;
			}
		}

		/**
		* Load object in memory from database
		*
		* @param	int		$id			Id object
		* @param	string	$username	SMTP2GO username (used if $id is empty)
		* @return	int					<0 if KO, 0 if not found, >0 if OK
		*/
		public function fetch($id, $username = '')
		{
			// Check parameters
			if (empty($id) && empty($username)) {
				$this->error = 'ErrorBadParameters';
				return -1;
			}
			$sql	= 'SELECT';
			$sql	.= ' t.rowid,';
			$sql	.= ' t.username,';
			$sql	.= ' t.description,';
			$sql	.= ' t.password,';
			$sql	.= ' t.fk_user,';
			$sql	.= ' t.subaccount_id,';
			$sql	.= ' t.custom_ratelimit,';
			$sql	.= ' t.custom_ratelimit_value,';
			$sql	.= ' t.custom_ratelimit_period,';
			$sql	.= ' t.ratelimit_default,';
			$sql	.= ' t.enforce_2fa,';
			$sql	.= ' t.enable_sms,';
			$sql	.= ' t.sms_limit,';
			$sql	.= ' t.archive_enabled,';
			$sql	.= ' t.open_tracking_enabled,';
			$sql	.= ' t.click_tracking_enabled,';
			$sql	.= ' t.audit_email,';
			$sql	.= ' t.feedback_enabled,';
			$sql	.= ' t.feedback_domain,';
			$sql	.= ' t.feedback_html,';
			$sql	.= ' t.feedback_text,';
			$sql	.= ' t.bounce_notifications,';
			$sql	.= ' t.entity,';
			$sql	.= ' t.tms';
			$sql	.= ' FROM '.$this->db->prefix().$this->table_element.' as t';
			if ($id) {
				$sql .= ' WHERE t.rowid = '.((int) $id);
			} elseif ($username) {
				$sql .= " WHERE t.username = '".$this->db->escape($username)."'";
			}
			dol_syslog(get_class($this).'::fetch', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					$obj							= $this->db->fetch_object($resql);
					$this->id						= $obj->rowid;
					$this->username					= $obj->username;
					$this->description				= $obj->description;
					// The password is only known locally (SMTP2GO never returns it back) : decrypt what we saved
					$this->password					= !empty($obj->password) ? dolDecrypt($obj->password) : '';
					$this->fk_user					= is_null($obj->fk_user) ? null : (int) $obj->fk_user;
					$this->subaccount_id			= $obj->subaccount_id;
					$this->custom_ratelimit			= $obj->custom_ratelimit;
					$this->custom_ratelimit_value	= $obj->custom_ratelimit_value;
					$this->custom_ratelimit_period	= $obj->custom_ratelimit_period;
					$this->ratelimit_default		= $obj->ratelimit_default;
					$this->enforce_2fa				= $obj->enforce_2fa;
					$this->enable_sms				= $obj->enable_sms;
					$this->sms_limit				= $obj->sms_limit;
					$this->archive_enabled			= $obj->archive_enabled;
					$this->open_tracking_enabled	= $obj->open_tracking_enabled;
					$this->click_tracking_enabled	= $obj->click_tracking_enabled;
					$this->audit_email				= $obj->audit_email;
					$this->feedback_enabled			= $obj->feedback_enabled;
					$this->feedback_domain			= $obj->feedback_domain;
					$this->feedback_html			= $obj->feedback_html;
					$this->feedback_text			= $obj->feedback_text;
					$this->bounce_notifications		= $obj->bounce_notifications;
					$this->entity					= $obj->entity;
					$this->tms						= $this->db->jdate($obj->tms);
					$this->db->free($resql);
					return 1;
				} else {
					$this->db->free($resql);
					return 0;
				}
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(get_class($this).'::fetch '.$this->db->lasterror(), LOG_ERR);
				return -1;
			}
		}

		/**
		* Update object into database
		*
		* @param	User	$user		User that modifies (unused, kept for the standard Dolibarr CRUD signature)
		* @param	int		$notrigger	1=Does not execute triggers, 0=Execute triggers
		* @return	int					<0 if KO, >0 if OK
		*/
		public function update($user = null, $notrigger = 0)
		{
			$error = 0;
			// Clean parameters
			if (isset($this->id)) {
				$this->id							= trim($this->id);
			}
			if (isset($this->username)) {
				$this->username						= trim($this->username);
			}
			if (isset($this->description)) {
				$this->description					= trim($this->description);
			}
			if (isset($this->password)) {
				$this->password						= trim($this->password);
			}
			if (isset($this->custom_ratelimit_period)) {
				$this->custom_ratelimit_period		= trim($this->custom_ratelimit_period);
			}
			if (isset($this->audit_email)) {
				$this->audit_email					= trim($this->audit_email);
			}
			if (isset($this->bounce_notifications)) {
				$this->bounce_notifications			= trim($this->bounce_notifications);
			}
			// Check parameters
			if (empty($this->id)) {
				$this->error = 'ErrorMissingId';
				return -1;
			}
			$this->db->begin();
			// Update request
			$sql	= 'UPDATE '.$this->db->prefix().$this->table_element.' SET';
			$sql	.= " username = '".$this->db->escape($this->username)."',";
			$sql	.= ' description = '.(!empty($this->description) ? "'".$this->db->escape($this->description)."'" : "null").',';
			$sql	.= ' password = '.(!empty($this->password) ? "'".$this->db->escape($this->password)."'" : "null").',';
			$sql	.= ' fk_user = '.(!empty($this->fk_user) ? ((int) $this->fk_user) : "null").',';
			$sql	.= ' subaccount_id = '.((int) $this->subaccount_id).',';
			$sql	.= ' custom_ratelimit = '.((int) $this->custom_ratelimit).',';
			$sql	.= ' custom_ratelimit_value = '.(!empty($this->custom_ratelimit_value) ? (int) $this->custom_ratelimit_value : "null").',';
			$sql	.= ' custom_ratelimit_period = '.(!empty($this->custom_ratelimit_period) ? "'".$this->db->escape($this->custom_ratelimit_period)."'" : "null").',';
			$sql	.= ' ratelimit_default = '.((int) $this->ratelimit_default).',';
			$sql	.= ' enforce_2fa = '.((int) $this->enforce_2fa).',';
			$sql	.= ' enable_sms = '.((int) $this->enable_sms).',';
			$sql	.= ' sms_limit = '.((int) $this->sms_limit).',';
			$sql	.= ' archive_enabled = '.((int) $this->archive_enabled).',';
			$sql	.= ' open_tracking_enabled = '.((int) $this->open_tracking_enabled).',';
			$sql	.= ' click_tracking_enabled = '.((int) $this->click_tracking_enabled).',';
			$sql	.= ' audit_email = '.(!empty($this->audit_email) ? "'".$this->db->escape($this->audit_email)."'" : "null").',';
			$sql	.= ' feedback_enabled = '.((int) $this->feedback_enabled).',';
			$sql	.= ' feedback_domain = '.(!empty($this->feedback_domain) ? "'".$this->db->escape($this->feedback_domain)."'" : "null").',';
			$sql	.= ' feedback_html = '.(!empty($this->feedback_html) ? "'".$this->db->escape($this->feedback_html)."'" : "null").',';
			$sql	.= ' feedback_text = '.(!empty($this->feedback_text) ? "'".$this->db->escape($this->feedback_text)."'" : "null").',';
			$sql	.= ' bounce_notifications = '.(!empty($this->bounce_notifications) ? "'".$this->db->escape($this->bounce_notifications)."'" : "null");
			$sql	.= ' WHERE rowid = '.((int) $this->id);
			dol_syslog(get_class($this).'::update', LOG_DEBUG);
			$resql	= $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
			}
			// Commit or rollback
			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this).'::update '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
			$this->db->commit();
			return 1;
		}

		/**
		* Delete object from database
		*
		* @param	User	$user		User that deletes (unused, kept for the standard Dolibarr CRUD signature)
		* @param	int		$notrigger	1=Does not execute triggers, 0=Execute triggers
		* @return	int					<0 if KO, >0 if OK
		*/
		public function delete($user = null, $notrigger = 0)
		{
			$error	= 0;
			$this->db->begin();
			$sql	= 'DELETE FROM '.$this->db->prefix().$this->table_element.' WHERE rowid = '.((int) $this->id);
			dol_syslog(get_class($this).'::delete', LOG_DEBUG);
			$resql	= $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
			}
			// Commit or rollback
			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this).'::delete '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			} else {
				$this->db->commit();
				return 1;
			}
		}

		/**
		* Load list of objects in memory from database
		*
		* @param	string	$sortfield	Sort field (ex: 't.username')
		* @param	string	$sortorder	Sort order (ASC or DESC)
		* @param	int		$limit		Limit
		* @param	int		$offset		Offset
		* @return	array|int			Array of MultiSMTP_Smtp2go if OK, <0 if KO
		*/
		public function fetchAll($sortfield = '', $sortorder = '', $limit = 0, $offset = 0)
		{
			$records	= array();
			$sql		= 'SELECT t.rowid FROM '.$this->db->prefix().$this->table_element.' as t';
			$sql		.= ' WHERE t.entity IN ('.getEntity($this->element).')';
			if (!empty($sortfield)) {
				$sql .= $this->db->order($sortfield, $sortorder);
			}
			if (!empty($limit)) {
				$sql .= $this->db->plimit($limit, $offset);
			}
			dol_syslog(get_class($this).'::fetchAll', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$record	= new MultiSMTP_Smtp2go($this->db);
					$result	= $record->fetch($obj->rowid);
					if ($result > 0) {
						$records[$record->id] = $record;
					}
				}
				$this->db->free($resql);
				return $records;
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(get_class($this).'::fetchAll '.$this->db->lasterror(), LOG_ERR);
				return -1;
			}
		}

		/**
		* Get the list of usernames of all local SMTP2GO records (lightweight query, no object hydration)
		*
		* @return	array|int	Array of usernames if OK, <0 if KO
		*/
		public function fetchAllUsernames()
		{
			$usernames	= array();
			$sql		= 'SELECT username FROM '.$this->db->prefix().$this->table_element;
			$sql		.= ' WHERE entity IN ('.getEntity($this->element).')';
			dol_syslog(get_class($this).'::fetchAllUsernames', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$usernames[] = $obj->username;
				}
				$this->db->free($resql);
				return $usernames;
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(get_class($this).'::fetchAllUsernames '.$this->db->lasterror(), LOG_ERR);
				return -1;
			}
		}

		/**
		* Get the user (fk_user) linked with the SMTP identifier
		*
		* @param	string		$username	Filter by username
		* @return	array|int				Array of usernames if OK, <0 if KO
		*/
		function getUserFromUsername($username)
		{
			$sql		= 'SELECT fk_user, username FROM '.$this->db->prefix().$this->table_element;
			$sql		.= ' WHERE entity IN ('.getEntity($this->element).')';
			$sql		.= " AND username = '".$this->db->escape($username)."'";
			$resql		= $this->db->query($sql);
			if ($resql) {
				$users = array();
				while ($obj = $this->db->fetch_object($resql)) {
					$users[$obj->username] = (int) $obj->fk_user;
				}
				$this->db->free($resql);
				return $users;
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(get_class($this).'::getUserFromUsername '.$this->db->lasterror(), LOG_ERR);
				return -1;
			}
		}

		/**
		* Get the Dolibarr user linked to each local SMTP2GO record (lightweight query, no object hydration).
		* Records not linked to any Dolibarr user are not returned.
		*
		* @return	array|int	Array of fk_user indexed by username if OK, <0 if KO
		*/
		public function fetchAllUserLinks()
		{
			$links	= array();
			$sql	= 'SELECT username, fk_user FROM '.$this->db->prefix().$this->table_element;
			$sql	.= ' WHERE entity IN ('.getEntity($this->element).')';
			$sql	.= ' AND fk_user > 0';	// Also excludes the 0 written by the versions that did not allow a null link
			dol_syslog(get_class($this).'::fetchAllUserLinks', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$links[$obj->username] = (int) $obj->fk_user;
				}
				$this->db->free($resql);
				return $links;
			} else {
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(get_class($this).'::fetchAllUserLinks '.$this->db->lasterror(), LOG_ERR);
				return -1;
			}
		}

		/**
		* Fetch all SMTP users from the SMTP2GO API and synchronize them into the local table :
		* creates the ones missing locally, updates the ones that changed, and removes the local
		* records of SMTP users that no longer exist on SMTP2GO. Cron-compatible (0=OK, <0=KO).
		*
		* @param	string	$subaccount_id	Subaccount ID to synchronize ('' = main account)
		* @return	int						<0 if KO (API call failed), 0 if OK
		*/
		public function cronSyncUsers($subaccountid = '')
		{
			global $user;

			dol_include_once('/multismtp/lib/smtp2go.lib.php');

			$this->output	= '';
			$this->error	= '';
			// Build the list of "scopes" to sync : the main account ('') plus every subaccount,
			// unless the job was restricted to a single subaccount id.
			$scopes	= array('');
			if (!empty($subaccountid)) {
				$scopes	= array($subaccountid);
			} else {
				$subaccounts_result	= multismtp_smtp2go_get_subaccounts();
				if (!$subaccounts_result['success']) {
					$this->error	= 'Unable to list subaccounts: '.$subaccounts_result['error'];
					return 1;
				}
				foreach ($subaccounts_result['subaccounts'] as $subaccount) {
					if (is_array($subaccount) && !empty($subaccount['id'])) {
						$scopes[]	= (string) $subaccount['id'];
					}
				}
			}
			$nbcreated	= 0;
			$nbupdated	= 0;
			$nberrors	= 0;
			foreach ($scopes as $scope) {
				$users_result	= multismtp_smtp2go_get_smtp_users($scope);
				if (!$users_result['success']) {
					$this->error	.= ($this->error ? ' - ' : '').'Unable to list SMTP users of scope "'.$scope.'": '.$users_result['error'];
					$nberrors++;
					continue;
				}
				foreach ($users_result['users'] as $userSmtp) {
					if (!is_array($userSmtp) || empty($userSmtp['username'])) {
						continue;
					}
					// Translate the API "view" field names into the internal keys used by fillFromApiData()
					$data	= array('username'					=> $userSmtp['username'],
									'description'				=> $userSmtp['description'] ?? '',
									'custom_ratelimit'			=> $userSmtp['custom_ratelimit'] ?? 0,
									'custom_ratelimit_value'	=> $userSmtp['custom_ratelimit_value'] ?? 0,
									'custom_ratelimit_period'	=> $userSmtp['custom_ratelimit_period'] ?? '',
									'ip_pool'					=> $userSmtp['ippool'] ?? 0,
									'feedback_enabled'			=> $userSmtp['feedback_enabled'] ?? 0,
									'feedback_domain'			=> $userSmtp['feedback_domain'] ?? '',
									'feedback_html'				=> $userSmtp['feedback_html'] ?? '',
									'feedback_text'				=> $userSmtp['feedback_text'] ?? '',
									'open_tracking_enabled'		=> $userSmtp['open_tracking_enabled'] ?? 0,
									'click_tracking_enabled'	=> $userSmtp['click_tracking_enabled'] ?? 0,
									'archive_enabled'			=> $userSmtp['archive_enabled'] ?? 0,
									'audit_email'				=> $userSmtp['audit_email'] ?? '',
									'bounce_notifications'		=> $userSmtp['bounce_notifications'] ?? '',
									'subaccount_id'				=> $scope,
								);

					$local	= new MultiSMTP_Smtp2go($this->db);
					$found	= $local->fetch(0, $data['username']);
					if ($found < 0) {
						$this->error	.= ($this->error ? ' - ' : '').'Unable to read local user "'.$data['username'].'": '.$local->error;
						$nberrors++;
						continue;
					}

					$local->fillFromApiData($data);
					$result	= $found ? $local->update($user) : $local->create($user);
					if ($result <= 0) {
						$this->error	.= ($this->error ? ' - ' : '').'Unable to save local user "'.$data['username'].'": '.$local->error;
						$nberrors++;
					} elseif ($found) {
						$nbupdated++;
					} else {
						$nbcreated++;
					}
				}
			}
			$this->output	= $nbcreated.' user(s) created locally, '.$nbupdated.' updated, '.$nberrors.' error(s)';
			return $nberrors ? 1 : 0;
		}
	}
