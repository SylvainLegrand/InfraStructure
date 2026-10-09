<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasfiles/class/infrasfilesdocumenttrait.class.php
	* 	\ingroup	InfraS
	* 	\brief		Trait shared by the child classes adding the documents layer to native objects
	*				(document state stored in llx_infrasfiles_document, generation loop, output directory)
	************************************************/

	// Libraries ************************************
	dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');

	/************************************************
	* Trait InfrasFilesDocumentTrait
	* Must be used by a class extending CommonObject (access to the protected commonGenerateDocument())
	************************************************/
	trait InfrasFilesDocumentTrait
	{
		// The using class must declare : public $infrasfiles_element = '<registry key>'; (a trait cannot redeclare a property with another default value)
		public $infrasfiles_lines = array();	// @var array Lines loaded by infrasfilesFetchLines() for the PDF models

		/**
		*	Return the registry definition of the object
		*
		*	@return		array		Registry entry (empty array if unknown)
		**/
		public function infrasfilesGetDefinition()
		{
			$registry	= infrasfiles_get_registry();
			return isset($registry[$this->infrasfiles_element]) ? $registry[$this->infrasfiles_element] : array();
		}

		/**
		*	Load model_pdf and last_main_doc from llx_infrasfiles_document (native tables do not have these columns)
		*
		*	@return		int			1 if OK, -1 if KO
		**/
		public function infrasfilesLoadDocumentState()
		{
			global $conf;

			$this->model_pdf		= '';
			$this->last_main_doc	= '';
			$sql	= 'SELECT model_pdf, last_main_doc FROM '.$this->db->prefix().'infrasfiles_document';
			$sql	.= ' WHERE entity = '.((int) $conf->entity);
			$sql	.= " AND element = '".$this->db->escape($this->infrasfiles_element)."'";
			$sql	.= ' AND fk_element = '.((int) $this->id);
			$resql	= $this->db->query($sql);
			if (!$resql) {
				$this->error	= $this->db->lasterror();
				return -1;
			}
			$obj	= $this->db->fetch_object($resql);
			if ($obj) {
				$this->model_pdf		= (string) $obj->model_pdf;
				$this->last_main_doc	= (string) $obj->last_main_doc;
			}
			if (empty($this->model_pdf)) {
				$this->model_pdf	= getDolGlobalString(infrasfiles_const_name($this->infrasfiles_element, 'ADDON_PDF'), '');
			}
			if (!empty($this->last_main_doc) && !dol_is_file(DOL_DATA_ROOT.'/'.$this->last_main_doc)) {
				$this->last_main_doc	= '';	// file deleted from the tab or the disk
			}
			return 1;
		}

		/**
		*	Save model_pdf and last_main_doc into llx_infrasfiles_document (insert or update)
		*
		*	@return		int			1 if OK, -1 if KO
		**/
		public function infrasfilesSaveDocumentState()
		{
			global $conf, $user;

			if (!empty($this->specimen) || empty($this->id)) {
				return 1;	// specimen object (model preview) : nothing to store
			}
			$sql	= 'SELECT rowid FROM '.$this->db->prefix().'infrasfiles_document';
			$sql	.= ' WHERE entity = '.((int) $conf->entity);
			$sql	.= " AND element = '".$this->db->escape($this->infrasfiles_element)."'";
			$sql	.= ' AND fk_element = '.((int) $this->id);
			$resql	= $this->db->query($sql);
			if (!$resql) {
				$this->error	= $this->db->lasterror();
				return -1;
			}
			$obj	= $this->db->fetch_object($resql);
			if ($obj) {
				$sql	= 'UPDATE '.$this->db->prefix().'infrasfiles_document SET';
				$sql	.= ' model_pdf = '.(empty($this->model_pdf) ? 'NULL' : "'".$this->db->escape($this->model_pdf)."'");
				$sql	.= ', last_main_doc = '.(empty($this->last_main_doc) ? 'NULL' : "'".$this->db->escape($this->last_main_doc)."'");
				$sql	.= ', fk_user_modif = '.((int) $user->id);
				$sql	.= ' WHERE rowid = '.((int) $obj->rowid);
			} else {
				$sql	= 'INSERT INTO '.$this->db->prefix().'infrasfiles_document (entity, element, fk_element, model_pdf, last_main_doc, date_creation, fk_user_modif) VALUES (';
				$sql	.= ((int) $conf->entity);
				$sql	.= ", '".$this->db->escape($this->infrasfiles_element)."'";
				$sql	.= ', '.((int) $this->id);
				$sql	.= ', '.(empty($this->model_pdf) ? 'NULL' : "'".$this->db->escape($this->model_pdf)."'");
				$sql	.= ', '.(empty($this->last_main_doc) ? 'NULL' : "'".$this->db->escape($this->last_main_doc)."'");
				$sql	.= ", '".$this->db->idate(dol_now())."'";
				$sql	.= ', '.((int) $user->id).')';
			}
			if (!$this->db->query($sql)) {
				$this->error	= $this->db->lasterror();
				return -1;
			}
			return 1;
		}

		/**
		*	Overload of CommonObject::setDocModel() : the native table has no model_pdf column
		*
		*	@param		User		$user		User
		*	@param		string		$modelpdf	Model name
		*	@return		int						1 if OK, -1 if KO
		**/
		public function setDocModel($user, $modelpdf)
		{
			$this->model_pdf	= dol_trunc($modelpdf, 255);
			return $this->infrasfilesSaveDocumentState();
		}

		/**
		*	Output directory of the object files
		*
		*	@return		string		Full path (without trailing slash)
		**/
		public function infrasfilesGetOutputDir()
		{
			return infrasfiles_get_output_dir($this->infrasfiles_element, $this);
		}

		/**
		*	Match the files of the object with the third parties they are addressed to. File names are deterministic
		*	(<REF>-<suffix>.pdf, possibly prefixed / suffixed by a third party model such as InfraSPlus_Bon) : a file belongs to the
		*	addressee whose '<REF>-<suffix>' is in its name, the longest suffix first and never as the beginning of a longer code
		*	(CU2609-0006 does not match CU2609-00065). Objects without infrasfilesGetAddressees() (inventories) have no addressee.
		*
		*	@param		array|null	$files		Files as returned by dol_dir_list() (null = files of the output directory of the object)
		*	@return		array					array(file name => addressee array('id', 'name', 'code', 'email', 'suffix'))
		**/
		public function infrasfilesGetFileAddressees($files = null)
		{
			$map	= array();
			if (!method_exists($this, 'infrasfilesGetAddressees') || empty($this->ref)) {
				return $map;
			}
			if (empty($this->infrasfiles_lines) && empty($this->specimen) && method_exists($this, 'infrasfilesFetchLines')) {
				$this->infrasfilesFetchLines();
			}
			$addressees	= $this->infrasfilesGetAddressees();
			if (empty($addressees)) {
				return $map;
			}
			if ($files === null) {
				$files	= dol_dir_list($this->infrasfilesGetOutputDir(), 'files', 0, '', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 0);
			}
			usort($addressees, function ($a, $b) {
				return strlen($b['suffix']) - strlen($a['suffix']);	// longest suffix first
			});
			$ref	= dol_sanitizeFileName($this->ref);
			foreach ($files as $file) {
				$name	= is_array($file) ? $file['name'] : basename((string) $file);
				foreach ($addressees as $addressee) {
					if (preg_match('/'.preg_quote($ref.'-'.$addressee['suffix'], '/').'(?![A-Za-z0-9-])/', $name)) {
						$map[$name]	= $addressee;
						break;
					}
				}
			}
			return $map;
		}
		/**
		*	Generation loop : one call of the native commonGenerateDocument() per unit (a unit = one PDF)
		*
		*	@param		string		$modele			Model name ('' = last used or default)
		*	@param		Translate	$outputlangs	Output language
		*	@param		int			$hidedetails	Hide details
		*	@param		int			$hidedesc		Hide descriptions
		*	@param		int			$hideref		Hide references
		*	@param		array|null	$moreparams		More parameters
		*	@param		array		$units			List of units : array(array('suffix' => file suffix, 'label' => label, ... model specific keys))
		*	@return		int							1 if OK, <= 0 if KO
		**/
		protected function infrasfilesGenerate($modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams, $units)
		{
			global $langs;

			$definition	= $this->infrasfilesGetDefinition();
			if (empty($definition['modelspath'])) {
				$this->error	= $langs->trans('ErrorBadParameters');
				return -1;
			}
			if (!dol_strlen($modele)) {
				$modele	= !empty($this->model_pdf) ? $this->model_pdf : getDolGlobalString(infrasfiles_const_name($this->infrasfiles_element, 'ADDON_PDF'), '');
			}
			if (!dol_strlen($modele)) {
				$this->error	= $langs->trans('InfraSFilesNoModelSelected');
				return -1;
			}
			if (empty($units)) {
				$units	= array(array('suffix' => '', 'label' => ''));
			}
			$moreparams	= is_array($moreparams) ? $moreparams : array();
			foreach ($units as $unit) {
				$moreparams['infrasfiles_unit']	= $unit;
				$result	= $this->commonGenerateDocument($definition['modelspath'], $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
				if ($result <= 0) {
					return $result;
				}
			}
			$this->infrasfilesSaveDocumentState();	// model_pdf set by commonGenerateDocument(), last_main_doc set by the model
			return 1;
		}
	}
