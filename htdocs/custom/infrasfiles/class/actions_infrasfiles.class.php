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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	* 	\file		./infrasfiles/class/actions_infrasfiles.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraSFiles
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');
	dol_include_once('/infrasfiles/core/lib/infrasfilesAdmin.lib.php');

	/************************************************
	* Class infrasfiles
	************************************************/
	class Actionsinfrasfiles
	{
		public $db;	// @var DoliDB Database handler.
		public $results = array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $errors = array();	// @var array Errors

		/************************************************
		* Constructor
		*
		* @param	 DATABASE		$db		 db object
		* @return						void
		************************************************/
		public function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		* When login
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function afterLogin($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$currentversion	= array();
			$currentversion	= infrasfiles_getLocalVersionMinDoli('infrasfiles');
			if (!getDolGlobalString('INFRASFILES_DISABLE_CHECK_VERSION_MAX', '') && version_compare(explode('.', DOL_VERSION)[0], explode('.', $currentversion[4])[0], '>')) {
				setEventMessages($langs->trans('InfraSFilesWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			return 0;
		}

		/**
		* Find the registry element matching the hook context of the current page, when the page is the native card of that element
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @return	string							Element key or '' when the page is not a supported card
		**/
		protected function infrasfilesElementFromContext($parameters)
		{
			$contexts	= explode(':', (string) (isset($parameters['context']) ? $parameters['context'] : ''));
			$script		= isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
			foreach (infrasfiles_get_registry() as $element => $definition) {
				if (!in_array($definition['hookcontext'], $contexts)) {
					continue;
				}
				$pages	= (array) $definition['cardurl'];
				$found	= empty($pages);
				foreach ($pages as $page) {
					if (substr($script, -strlen($page)) == $page) {
						$found	= true;
						break;
					}
				}
				if (!$found) {
					continue;	// same hook context but a page not listed in the registry
				}
				return $element;
			}
			return '';
		}

		/**
		* Actions of the native cards : document generation and deletion posted by the "Attached files" section
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function doActions($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $langs, $user, $db;

			if (!in_array($action, array('builddoc', 'remove_file'))) {
				return 0;
			}
			$element	= $this->infrasfilesElementFromContext($parameters);
			if (empty($element) || !infrasfiles_is_enabled($element, 'DOCUMENT') || !infrasfiles_user_can($element, 'write')) {
				return 0;
			}
			$id			= (is_object($object) && !empty($object->id)) ? $object->id : GETPOSTINT('id');
			$docobject	= infrasfiles_load_object($element, $id);
			if (!is_object($docobject) || $docobject->id <= 0) {
				return 0;
			}
			// remove_file : the file must belong to the element of this card (upload_dir is the base directory of the module, so a file of another
			// element, ex 'inventory/REF/x.pdf' posted from a withdrawal card, would be deleted with the permission of the wrong object)
			if ($action == 'remove_file' && infrasfiles_element_from_file(GETPOST('file', 'alpha')) !== $element) {
				setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
				$action	= '';
				return 1;
			}
			// Variables expected by the native action file, then the native code does the job (model saved with setDocModel(), generateDocument(), messages)
			$permissiontoadd	= 1;
			$upload_dir			= infrasfiles_get_base_dir();	// remove_file receives file = '<dirout>/<ref>/<name>' (same convention as the native cards)
			$hidedetails		= 0;
			$hidedesc			= 0;
			$hideref			= 0;
			$moreparams			= null;
			$savedobject		= $object;
			$object				= $docobject;
			include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
			$object				= $savedobject;
			// The action is fully handled here : some native cards (ex : product/inventory/card.php) include actions_builddoc.inc.php themselves,
			// which would call setDocModel() on the native object (no model_pdf column) => tell the card the standard code is replaced and clear the action
			$action				= '';
			return 1;
		}

		/**
		* Action buttons of the native cards : "Send by e-mail" (opens the native send form on the module document page)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$element	= $this->infrasfilesElementFromContext($parameters);
			if (empty($element) || !infrasfiles_is_enabled($element, 'EMAIL') || !infrasfiles_user_can($element, 'write')) {
				return 0;
			}
			$id	= (is_object($object) && !empty($object->id)) ? $object->id : GETPOSTINT('id');
			if ($id <= 0) {
				return 0;
			}
			$langs->load('mails');
			$url	= dol_buildpath('/infrasfiles/document.php', 1).'?element='.urlencode($element).'&id='.((int) $id).'&action=presend&mode=init#formmailbeforetitle';
			print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $url, '', 1);	// addreplace hook : the card does not print resprints, the hook prints itself
			return 0;
		}

		/**
		* Send form (FormMail::get_form) : recipients of the module objects (contacts of the third parties of the lines, free e-mails)
		* and return URL of the module document page
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	FormMail		&$formmail		The FormMail instance being built
		* @param	string			&$action		Current action
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int								0 (form kept, completed)
		**/
		public function getFormMail($parameters, &$formmail, &$action, $hookmanager)
		{
			if (!is_object($formmail) || empty($formmail->param['models'])) {
				return 0;
			}
			$element	= infrasfiles_element_from_mailtype($formmail->param['models']);
			if (empty($element)) {
				return 0;
			}
			$id			= !empty($formmail->param['id']) ? (int) $formmail->param['id'] : 0;
			$docobject	= infrasfiles_load_object($element, $id);
			if (!is_object($docobject)) {
				return 0;
			}
			$formmail->param['returnurl']	= dol_buildpath('/infrasfiles/document.php', 1).'?element='.urlencode($element).'&id='.$id;
			// Recipients proposed in the list : the contacts of the third parties of the object (nothing is prefilled in the free field)
			if (method_exists($docobject, 'infrasfilesGetRecipients')) {
				$recipients	= $docobject->infrasfilesGetRecipients();
				$withto		= is_array($formmail->withto) ? $formmail->withto : array();
				foreach ((array) $recipients as $contactid => $label) {
					$withto[$contactid]	= $label;	// numeric key = contact id, resolved by actions_sendmails.inc.php
				}
				if (!empty($withto)) {
					$formmail->withto	= $withto;
					$formmail->withtocc	= $withto;
				}
			}
			// Attachments : ALL the PDF generated for the object (several files in "one PDF per third party / per line" modes),
			// instead of the single last_main_doc chosen by the native template ; attached on the first display (mode=init) or on template change
			$pdfs	= dol_dir_list(infrasfiles_get_output_dir($element, $docobject), 'files', 0, '\.pdf$', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 0);
			if (!empty($pdfs)) {
				$fileinit	= array();
				foreach ($pdfs as $pdf) {
					$fileinit[]	= $pdf['fullname'];
				}
				$formmail->param['fileinit']	= $fileinit;
			}
			return 0;
		}

		/**
		* E-mail templates setup page : add the template types of the module objects
		*
		* @param	array()			$parameters		Hook metadatas (elementList)
		* @param	CommonObject	&$object		Not used
		* @param	string			&$action		Not used
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int								0 (types added into $this->results)
		**/
		public function emailElementlist($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$langs->load('infrasfiles@infrasfiles');
			foreach (infrasfiles_get_registry() as $element => $definition) {
				if (empty($definition['mailtype']) || !infrasfiles_module_available($definition)) {
					continue;
				}
				$this->results[$definition['mailtype']]	= img_picto('', $definition['picto'], 'class="pictofixedwidth"').dol_escape_htmltag($langs->trans($definition['label']));
			}
			return 0;
		}

		/**
		* Footer of the native cards : "Attached files" section (generation box + file list) inserted after the action buttons,
		* and counter badge on the "Documents" tab
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$hookobject	Not used : this hook receives no object, the object of the page is read as a global
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function printCommonFooter($parameters, &$hookobject, &$action, $hookmanager)
		{
			global $conf, $langs, $user, $db;
			global $object;	// object of the native card (not passed by this hook)

			if (empty($conf->use_javascript_ajax)) {
				return 0;
			}
			if (in_array(GETPOST('action', 'aZ09'), array('create', 'edit', 'editline', 'presend'))) {
				return 0;	// same behaviour as the native cards : no documents section while a form is being edited
			}
			$element	= $this->infrasfilesElementFromContext($parameters);
			if (empty($element) || !infrasfiles_is_enabled($element) || !infrasfiles_user_can($element, 'read')) {
				return 0;
			}
			$id	= (is_object($object) && !empty($object->id)) ? $object->id : GETPOSTINT('id');
			if ($id <= 0) {
				return 0;
			}
			$docobject	= infrasfiles_load_object($element, $id);
			if (!is_object($docobject) || $docobject->id <= 0) {
				return 0;
			}
			$nbdocs	= infrasfiles_count_documents($element, $docobject);
			$out	= '';
			if (infrasfiles_is_enabled($element, 'DOCUMENT')) {
				$urlsource	= $_SERVER['PHP_SELF'].'?id='.$docobject->id;
				$out		.= '<div id = "infrasfiles_docsection" class = "hideobject">
								<div class = "fichecenter"><div class = "fichehalfleft"><a name = "builddoc"></a>
									<div class = "ficheaddleft">'.infrasfiles_get_document_box($element, $docobject, $urlsource).'</div>
								</div></div>
							</div>';
			}
			$out	.= '<script type = "text/javascript">
							jQuery(document).ready(function() {
								var section	= jQuery("#infrasfiles_docsection");
								if (section.length) {
									var anchor	= jQuery("div.tabsAction").last();
									if (anchor.length) {
										// The section holds its own <form> : never nest it in a form of the page (ex : lines form of inventory.php)
										var parentform	= anchor.closest("form");
										if (parentform.length) {
											anchor	= parentform;
										}
										anchor.after(section);
									} else {
										jQuery("div.fichecenter").last().after(section);
									}
									section.removeClass("hideobject").show();
								}
								var nbdocs	= '.((int) $nbdocs).';
								if (nbdocs > 0) {
									jQuery("div.tabs a[href*=\"infrasfiles/document.php\"]").first().append("<span class=\"badge marginleftonlyshort\">" + nbdocs + "</span>");
								}
							});
						</script>';
			print $out;	// printCommonFooter() does not output resprints : the hook prints itself and returns 0 to keep the native footer
			return 0;
		}

		/**
		* Access control of the files served with modulepart = infrasfiles : a file of an object is granted on the NATIVE permission
		* of that object (read or write, for the user given by the core). The core only honours a positive answer of this hook
		* (dol_check_secure_access_document() : "if (!empty($resArray['accessallowed']))") : when the native permission is missing, the
		* hook returns 0 and the generic decision applies, i.e. the permission 'read' of the module — which is therefore a "see every
		* file of the module" permission, not granted by default (see the module descriptor and CLAUDE.md).
		*
		* @param	array()			$parameters		Hook metadatas (modulepart, original_file, entity, fuser, mode)
		* @param	CommonObject	&$object		Not used
		* @param	string			&$action		Not used
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int								0 = generic decision, 1 = access granted ($this->results['accessallowed'] = 1)
		**/
		public function checkSecureAccess($parameters, &$object, &$action, $hookmanager)
		{
			global $user;

			if (empty($parameters['modulepart']) || $parameters['modulepart'] != 'infrasfiles' || empty($parameters['original_file'])) {
				return 0;
			}
			// Raw strings on both sides : the native code builds original_file as $conf->infrasfiles->dir_output.'/'.<file>, our base dir is the same raw value
			$basedir	= infrasfiles_get_base_dir();
			$relative	= preg_replace('/^'.preg_quote($basedir, '/').'\/+/', '', (string) $parameters['original_file']);
			$element	= infrasfiles_element_from_file($relative);
			if ($element === '') {
				return 0;	// not a file of an object of the registry (ex : temp/) : generic decision
				}
				$mode	= (!empty($parameters['mode']) && $parameters['mode'] == 'write') ? 'write' : 'read';
			$fuser	= (!empty($parameters['fuser']) && is_object($parameters['fuser'])) ? $parameters['fuser'] : $user;
			if (infrasfiles_user_can($element, $mode, $fuser)) {
				$this->results	= array('accessallowed' => 1);
				return 1;
			}
			return 0;
		}
	}
