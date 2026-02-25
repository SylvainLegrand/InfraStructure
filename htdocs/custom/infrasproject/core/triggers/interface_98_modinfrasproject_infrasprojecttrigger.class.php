<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		../infrasproject/core/triggers/interface_98_modinfrasproject_infrasprojecttrigger.class.php
	*	\ingroup	InfraS
	*	\brief		Trigger for the module InfraS
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	dol_include_once('/infrasproject/core/lib/infrasprojectAdmin.lib.php');
	dol_include_once('/infrasproject/core/lib/infrasproject.lib.php');

	// Description and activation class *************
	class InterfaceInfrasprojecttrigger extends DolibarrTriggers
	{
		protected $db;	// Database handler @var DoliDB
		public $name				= '';	// Name of the trigger @var mixed|string
		public $description			= '';	// Description of the trigger @var string
		public $version				= self::VERSION_DOLIBARR;	// Version of the trigger @var string
		public $picto				= 'technic';	// Image of the trigger @var string
		public $family				= '';	// Category of the trigger @var string
		public $errors				= array();	// Errors reported by the trigger @var array
		const VERSION_DEVELOPMENT	= 'development';	// @var string module is in development
		const VERSION_EXPERIMENTAL	= 'experimental';	// @var string module is experimental
		const VERSION_DOLIBARR		= 'dolibarr';	// @var string module is dolibarr ready

		/**
		*	Constructor.
		*	@param	DoliDB	$db		Database handler
		**/
		public function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infrasproject@infrasproject');

			$this->db			= $db;
			$this->name			= preg_replace('/^Interface/i', '', get_class($this));
			$this->family		= 'Modules '.$langs->trans('basenameInfraSProject');
			$this->description	= $langs->trans('Module500055DescTrigger');
			$currentversion		= infrasproject_getLocalVersionMinDoli('infrasproject');
			$this->version		= $currentversion[0];	// 'development', 'experimental', 'dolibarr' or version
			$this->picto		= 'infrasproject@infrasproject';
		}

		/**
		*	Trigger name
		*
		*	@return	string		Name of trigger file
		**/
		public function getName()
		{
			return $this->name;
		}

		/**
		*	Trigger description
		*
		*	@return	string		Description of trigger file
		**/
		public function getDesc()
		{
			return $this->description;
		}

		/**
		* Trigger version
		*
		*	@return	string		Version of trigger file
		**/
		public function getVersion()
		{
			global $langs;
			$langs->load('admin');

			if ($this->version == 'development') {
				return $langs->trans('Development');
			} elseif ($this->version == 'experimental') {
				return $langs->trans('Experimental');
			} elseif ($this->version == 'dolibarr') {
				return DOL_VERSION;
			} elseif (!empty($this->version)) {
				return $this->version;
			} else {
				return $langs->trans('Unknown');
			}
		}

		/**
		* Function called when a Dolibarrr business event is done.
		* All functions "run_trigger" are triggered if file
		* is inside directory core/triggers
		*
		*	@param		string		$action		Event action code
		*	@param		object		$object		Object
		*	@param		User		$user		Object user
		*	@param		Translate	$langs		Object langs
		*	@param		conf		$conf		Object conf
		*	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
		**/
		public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
		{
			global $conf, $langs;

			// Quick environment test
			if (!isModEnabled('infrasproject') || !in_array($object->element, ['propal', 'facture_fourn_det'])) {
				return 0;
			}
			$insert_actions		= array('LINEBILL_SUPPLIER_CREATE');
			$update_actions		= array('LINEBILL_SUPPLIER_MODIFY');
			$validate_actions	= array('PROPAL_CLOSE_SIGNED');
			$authorizedActions	= array_merge($insert_actions, $update_actions, $validate_actions);
			if (!in_array($action, $authorizedActions)) {
				return 0;
			}
			// Actions
			switch ($action) {
				case 'PROPAL_CLOSE_SIGNED':
					dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->rowid.' element = '.$object->element);
					if (getDolGlobalInt('INFRASPROJECT_CREATE_PROJECT_FROM_SIGNED_PROPAL', 0)) {
						$newProject	= $this->createProject($object);
						if (!empty($newProject) && $newProject instanceof Project) {	// If the project has been created
							$result	= $newProject->setValid($user);
						} else {
							$result = $newProject;
						}
					} else {
						$result = 0;
					}
				break;
				case 'LINEBILL_SUPPLIER_CREATE':
				case 'LINEBILL_SUPPLIER_MODIFY':
					dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->rowid.' element = '.$object->element);
					$result	= $this->supplierInvoiceLineProject($object);
				break;
			}
			return $result < 0 ? $result : 0;
		}

		/**
		* Create a new project and link it to the object from which we are calling this function
		*
		*	@param		Propal				$object
		*	@return		int|Project			<0 if KO, Project object if OK
		**/
		private function createProject(&$object)
		{
			global $langs, $user, $hookmanager;

			$hookmanager->initHooks(array('infrasprojectcreateproject'));
			if (empty($object->thirdparty)) {
				$object->fetch_thirdparty();
			}
			$project	= new Project($this->db);
			$action		= 'createProject';
			$reshook	= $hookmanager->executeHooks('createProject', array('project' => &$project), $object, $action);
			if (!empty($hookmanager->resArray)) {
				$project	= &$hookmanager->resArray[0];
				return $project; // $project est donnée par référence et il doit avoir été soit create ou fetch
			} else {
				// Un projet est déja associé
				if (!empty($object->fk_project)) {
					$result	= $project->fetch($object->fk_project);
					if ($project->id > 0) {
						return $project;
					} else {
						return -1;
					}
				}
				$title	= (!empty($object->ref_client)) ? $object->ref_client : $object->thirdparty->name.' - '.$object->ref;
				$project->title			= $title;
				$project->socid			= $object->socid;
				$project->description	= '';
				$project->public		= 1; // 0 = Contacts du projet  ||  1 = Tout le monde
				$project->datec			= dol_now();
				$project->date_start	= !empty($object->delivery_date) ? $object->delivery_date : dol_now();
				$project->date_end		= null;
				$project->ref			= $this->get_project_ref($project);
				$result					= $project->create($user);
				if ($result > 0) {
					$project->array_options	= $object->array_options;
					$res					= $project->insertExtraFields();
					$object->setProject($result);
					$action					= 'afterCreateProject';
					$reshook				= $hookmanager->executeHooks('afterCreateProject', array('project' => &$project), $object, $action);
					setEventMessage($langs->transnoentitiesnoconv('InfraSProjectProjectCreated', $project->ref));
					return $project;
				} else {
					setEventMessage($langs->transnoentitiesnoconv('InfraSProjectErrorCreateProject', $result.$project->error), 'errors');
				}
			}
			return -1;
		}

		/**
		* Create a new project reference
		*
		*	@param		Project		$project
		*	@return		string		Reference
		**/
		private function get_project_ref(&$project)
		{
			global $conf;

			$project->fetch_thirdparty();
			$defaultref	= '';
			$modele		= getDolGlobalString('PROJECT_ADDON') ?? 'mod_project_simple';
			// Search template files
			$file		= '';
			$classname	= '';
			$filefound	= 0;
			$dirmodels	= array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach($dirmodels as $reldir) {
				$file	= dol_buildpath($reldir.'core/modules/project/'.$modele.'.php', 0);
				if (file_exists($file)) {
					$filefound	= 1;
					$classname	= $modele;
					break;
				}
			}
			if ($filefound) {
				$result		= dol_include_once($reldir.'core/modules/project/'.$modele.'.php');
				$modProject	= new $classname;
				$defaultref	= $modProject->getNextValue($project->thirdparty, $project);
			}
			return $defaultref;
		}

		/**
		* Complete the supplier invoice line with the selected project
		*
		*	@param		SupplierInvoiceLine		$object
		*	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
		**/
		private function supplierInvoiceLineProject(&$object)
		{
			global $conf;

			$factFour		= new FactureFournisseur($this->db);
			$factFour->fetch($object->fk_facture_fourn);	// Facture fournisseur d'origine
			$fk_projectLine	= infrasproject_printprj($object->id, 1);	// Récupération du projet de la ligne de facture fournisseur
			$lineprojectid	= !empty(GETPOSTINT('lineprojectid')) ? GETPOSTINT('lineprojectid') : (!empty($fk_projectLine) && empty(GETPOSTINT('infraslineedit')) ? $fk_projectLine : 0);
			$sql			= 'UPDATE '.$this->db->prefix().'facture_fourn_det SET';
			$sql			.= ' entity = '.((int) (isset($factFour->entity) ? $factFour->entity : $conf->entity)).',';
			$sql			.= ' fk_soc = '.(isset($factFour->fk_soc) ? ((int) $factFour->fk_soc) : 'null').',';
			$sql			.= ' fk_projet = '.(!empty($lineprojectid) ? ((int) $lineprojectid) : 'null');
			$sql			.= ' WHERE rowid = '.((int) $object->id).';';
			$resql			= $this->db->query($sql);
			if (!$resql) {
				setEventMessage($this->db->lasterror(), 'errors');
				return -1;
			}
			// If the supplier invoice is linked to a project, it's removed because the distribution on the projects is done at the level of the lines
			if (!empty($lineprojectid) && $lineprojectid > 0) {
				return $factFour->setProject(0);
			}
			return 1;
		}
	}
