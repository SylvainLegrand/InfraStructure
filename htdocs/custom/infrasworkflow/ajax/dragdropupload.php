<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
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
	*	\file		./infrasworkflow/ajax/dragdropupload.php
	*	\ingroup	InfraS
	*	\brief		AJAX endpoint for native drag-and-drop upload that bypasses
	*				the saving_doc_mask renaming on product/service objects when
	*				INFRASWORKFLOW_DOCUMENTS_DRAGDROP_PRODUCT_NO_MASK is enabled.
	*				Reproduces htdocs/core/ajax/fileupload.php with an override
	*				of FileUpload->options['saving_doc_mask'], and indexes each
	*				uploaded file into llx_ecm_files with its source object
	*				(src_object_type / src_object_id), which the native
	*				FileUpload class never does.
	************************************************/

	if (! defined('NOREQUIREMENU')) {
		define('NOREQUIREMENU', '1');
	}
	if (! defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', '1');
	}
	if (! defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	if (! defined('NOREQUIRESOC')) {
		define('NOREQUIRESOC', '1');
	}

	// Dolibarr environment *************************
	require '../config.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/fileupload.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	/**
	 * @var Conf $conf
	 * @var DoliDB $db
	 * @var Translate $langs
	 * @var User $user
	 */

	/************************************************
	*	Class FileUploadEcmIndexed
	*
	*	Extends the native FileUpload class to index each successfully
	*	uploaded file into llx_ecm_files with src_object_type and src_object_id, exactly like the classic upload form does through
	*	dol_add_file_process(). FileUpload::handleFileUpload() never indexes files : without this, dropped files stay invisible to any
	*	feature relying on that index, such as sellist extrafields filtered on src_object_
	************************************************/
	class FileUploadEcmIndexed extends FileUpload
	{
		public $srcobject;	// @var CommonObject Object the uploaded files are attached to

		/**
		*	Validate data, move the uploaded file, create the thumbs (parent),
		*	then index the file into llx_ecm_files with the source object.
		*
		*	@param	string		$uploaded_file		Upload file
		*	@param	string		$name				Original file name
		*	@param	int			$size				Size
		*	@param	string		$type				Mime type
		*	@param	string		$error				Error
		*	@param	string		$index				Index
		*	@return	stdClass|null					File object as returned by parent
		**/
		protected function handleFileUpload($uploaded_file, $name, $size, $type, $error, $index)
		{
			$file	= parent::handleFileUpload($uploaded_file, $name, $size, $type, $error, $index);
			if (is_object($file) && empty($file->error) && ! empty($file->name) && is_object($this->srcobject) && ! empty($this->srcobject->id)) {
				$upload_dir	= rtrim(dol_sanitizePathName($this->options['upload_dir']), '/');
				$destfile	= dol_sanitizeFileName($file->name);
				if (! dol_is_file($upload_dir.'/'.$destfile) && dol_is_file($upload_dir.'/'.$destfile.'.noexe')) {
					$destfile	.= '.noexe';	// dol_move_uploaded_file() appended .noexe on executable content
				}
				if (dol_is_file($upload_dir.'/'.$destfile)) {
					// Same sharing rule as dol_add_file_process() : PDF on a product with external download allowed
					$sharefile	= ($type == 'application/pdf' && getDolGlobalString('PRODUCT_ALLOW_EXTERNAL_DOWNLOAD') ? 1 : 0);
					$result		= addFileIntoDatabaseIndex($upload_dir, $destfile, $name, 'uploaded', $sharefile, $this->srcobject);
					if ($result < 0) {
						dol_syslog(__METHOD__.' : unable to index file '.$upload_dir.'/'.$destfile.' into llx_ecm_files', LOG_WARNING);
					}
				}
			}
			return $file;
		}
	}

	// Feature gate ************************************
	if (! getDolGlobalInt('INFRASWORKFLOW_DOCUMENTS_DRAGDROP', 0) || ! getDolGlobalInt('INFRASWORKFLOW_DOCUMENTS_DRAGDROP_PRODUCT_NO_MASK', 0)) {
		httponly_accessforbidden('InfraSWorkflow drag-and-drop no-mask feature is disabled');
	}

	$id				= GETPOSTINT('fk_element');
	$element		= GETPOST('element', 'aZ09arobase');
	$elementupload	= $element;

	// Only product/service objects are concerned by this endpoint
	if ($element !== 'product') {
		httponly_accessforbidden('This endpoint only handles product/service drag-and-drop uploads');
	}

	$object			= fetchObjectByElement($id, $element);
	if (! is_object($object) || empty($object->id)) {
		httponly_accessforbidden('Object not found');
	}

	$module					= $object->module;
	$element				= $object->element;
	$usesublevelpermission	= ($module != $element ? $element : '');
	if ($usesublevelpermission && ! $user->hasRight($module, $element)) {
		$usesublevelpermission	= '';
	}

	// Security check ************************************
	if (! empty($user->socid)) {
		$socid	= $user->socid;
		if (! empty($object->socid) && $socid != $object->socid) {
			httponly_accessforbidden('Access on object not allowed for this external user.');
		}
	}
	$result	= restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);
	if (! $result) {
		httponly_accessforbidden('Not allowed by restrictArea (module='.$object->module.' table_element='.$object->table_element.')');
	}

	// View **********************************************
	top_httphead();

	header('Pragma: no-cache');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Content-Disposition: inline; filename="files.json"');
	header('X-Content-Type-Options: nosniff');
	header('Access-Control-Allow-Origin: '.getRootURLFromURL(DOL_MAIN_URL_ROOT));
	header('Access-Control-Allow-Methods: OPTIONS, POST');

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'OPTIONS':
			break;
		case 'POST':
			$upload_handler	= new FileUploadEcmIndexed(null, $id, $elementupload);
			// Source object used to index uploaded files into llx_ecm_files (same as the classic upload form)
			$upload_handler->srcobject	= $object;
			// Override the auto-computed saving doc mask to keep the original filename
			$upload_handler->options['saving_doc_mask']	= '';
			$upload_handler->post();
			break;
		default:
			header('HTTP/1.0 405 Method Not Allowed');
			exit;
	}

	$db->close();
