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
		* Actions of the native cards : document generation and deletion posted by the "Attached files" section,
		* and the send by e-mail form (opened on the card itself, like the native cards : see printCommonFooter)
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

			$mailactions	= array('presend', 'send', 'infrasfiles_sendbythirdparty');
			if (!in_array($action, array('builddoc', 'remove_file', 'infrasfiles_remove_files')) && !in_array($action, $mailactions)) {
				return 0;
			}
			$element	= $this->infrasfilesElementFromContext($parameters);
			if (empty($element)) {
				return 0;
			}
			$id			= (is_object($object) && !empty($object->id)) ? $object->id : GETPOSTINT('id');
			$docobject	= infrasfiles_load_object($element, $id);
			if (!is_object($docobject) || $docobject->id <= 0) {
				return 0;
			}
			if (in_array($action, $mailactions)) {
				return $this->infrasfilesDoMailActions($element, $docobject, $action);
			}
			// Mass deletion posted by the checkboxes of the "Attached files" section (same deletion as the trash icon, several files at once)
			if ($action == 'infrasfiles_remove_files') {
				// POST only : the core checks the token of a GET action only when its name starts with del / remove / set..., not ours
				// (a forged link would delete without token on an instance with MAIN_SECURITY_CSRF_WITH_TOKEN = 1 or 2) ; a POST is always checked
				if (!infrasfiles_is_post_request() || !infrasfiles_user_can($element, 'write')) {
					$action	= '';
					return 0;
				}
				infrasfiles_remove_files($element, $docobject, GETPOST('infrasfiles_files', 'array'));
				header('Location: '.$_SERVER['PHP_SELF'].'?id='.((int) $id));	// messages are in session ; a page reload must never delete again
				exit;
			}
			if (!infrasfiles_is_enabled($element, 'DOCUMENT') || !infrasfiles_user_can($element, 'write')) {
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
		* Send by e-mail actions posted by the form displayed on the native card (printCommonFooter) : the "Apply" button of the
		* e-mail template and "Cancel", the send loop "one e-mail per third party" (registry 'mailbythirdparty'), or the native
		* single e-mail mechanism (core/actions_sendmails.inc.php : attachments, send, trigger, redirection to the card)
		*
		* @param	string			$element		Registry element
		* @param	CommonObject	$docobject		Object (child class of the module)
		* @param	string			&$action		Current action, set to 'presend' when the form must be displayed again
		* @return	int								0 = not handled (native code runs), 1 = handled
		**/
		protected function infrasfilesDoMailActions($element, $docobject, &$action)
		{
			global $conf, $langs, $user, $db, $hookmanager, $mysoc, $dolibarr_main_url_root;
			$registry	= infrasfiles_get_registry();
			$definition	= $registry[$element];
			if (!empty($definition['nativemailtype'])) {
				return 0;	// the native card has its own send form and actions (ex : inventories)
			}
			if (!infrasfiles_is_enabled($element, 'EMAIL') || !infrasfiles_user_can($element, 'write')) {
				return 0;
			}
			if (GETPOST('cancel', 'alpha')) {
				$action	= '';
				return 1;
			}
			if (GETPOST('modelselected', 'alpha')) {
				$action	= 'presend';	// "Apply" button of the e-mail template : the form is displayed again with the chosen template (same as the native cards)
				return 1;
			}
			if ($action == 'presend') {
				return 1;	// the form is printed by printCommonFooter
			}
			$id			= (int) $docobject->id;
			$bythirdparty	= !empty($definition['mailbythirdparty']) && method_exists($docobject, 'infrasfilesGetMailBatches');
			if ($action == 'infrasfiles_sendbythirdparty') {
				if (!$bythirdparty || !infrasfiles_is_post_request()) {
					$action	= '';	// POST only (see the mass deletion in doActions) : a forged link must never send e-mails
					return 0;
				}
				dol_include_once('/infrasfiles/core/lib/infrasfilesmail.lib.php');
				$result	= infrasfiles_send_by_thirdparty($docobject, $definition, $docobject->infrasfilesGetMailBatches());
				if ($result['sent'] > 0 || empty($result['errors'])) {
					header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);	// messages are in session ; a page reload must never send again
					exit;
				}
				$action	= 'presend';	// nothing sent : the form is displayed again with the posted values
				return 1;
			}
			// 'send' : native single e-mail mechanism (objects without 'mailbythirdparty', ex : inventories), same variables as document.php
			if ($bythirdparty) {
				$action	= '';
				return 1;
			}
			$object				= $docobject;
			$trackid			= $definition['trackid'].$id;
			$triggersendname	= $definition['trigger'];
			$actiontypecode		= 'AC_OTH_AUTO';
			$autocopy			= 'MAIN_MAIL_AUTOCOPY_INFRASFILES_TO';
			$paramname			= 'id';	// native redirection after sending : PHP_SELF?id=<id> = the card
			include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';	// sets $action = 'presend' after an attachment change or a send error
			return 1;
		}
		/**
		* HTML of the send by e-mail form for a card : the module template (one e-mail per third party) or the native one
		*
		* @param	string			$element		Registry element
		* @param	CommonObject	$docobject		Object (child class of the module)
		* @return	string							HTML
		**/
		protected function infrasfilesGetPresendForm($element, $docobject)
		{
			global $conf, $langs, $user, $db, $hookmanager, $form;
			$registry	= infrasfiles_get_registry();
			$definition	= $registry[$element];
			$langs->loadLangs(array('mails', 'other', 'infrasfiles@infrasfiles'));
			if (!empty($definition['langs'])) {
				$langs->loadLangs((array) $definition['langs']);
			}
			// Variables expected by the templates (same as document.php)
			$object						= $docobject;
			$action						= 'presend';
			$modelmail					= $definition['mailtype'];
			$defaulttopic				= $definition['mailtopic'];
			$defaulttopiclang			= 'infrasfiles@infrasfiles';
			$diroutput					= infrasfiles_get_output_dir($element, null);
			$trackid					= $definition['trackid'].$object->id;
			$arrayoffamiliestoexclude	= null;
			$presendreturnurl			= $_SERVER['PHP_SELF'].'?id='.((int) $object->id);
			ob_start();
			if (!empty($definition['mailbythirdparty']) && method_exists($object, 'infrasfilesGetMailBatches')) {
				dol_include_once('/infrasfiles/core/lib/infrasfilesmail.lib.php');
				$batchdata	= $object->infrasfilesGetMailBatches();
				include dol_buildpath('/infrasfiles/core/tpl/infrasfiles_presend.tpl.php', 0);
			} else {
				include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
			}
			return ob_get_clean();
		}
		/**
		* Action buttons of the native cards : "Send by e-mail" (opens the send form on the card itself, like the native cards)
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
			$registry	= infrasfiles_get_registry();
			if (!empty($registry[$element]['nativemailtype'])) {
				return 0;	// the native card already has its own "Send by e-mail" button (ex : inventories)
			}
			$id	= (is_object($object) && !empty($object->id)) ? $object->id : GETPOSTINT('id');
			if ($id <= 0) {
				return 0;
			}
			$langs->load('mails');
			$url	= $_SERVER['PHP_SELF'].'?id='.((int) $id).'&action=presend&mode=init#formmailbeforetitle';
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
			$element	= infrasfiles_element_from_mailtype($formmail->param['models']);	// type of the module, or of the native card form ('nativemailtype')
			if (empty($element) || !infrasfiles_is_enabled($element)) {
				return 0;
			}
			$id			= !empty($formmail->param['id']) ? (int) $formmail->param['id'] : 0;
			$docobject	= infrasfiles_load_object($element, $id);
			if (!is_object($docobject)) {
				return 0;
			}
			if (preg_match('/\/infrasfiles\/document\.php$/', (string) $_SERVER['PHP_SELF'])) {
				$formmail->param['returnurl']	= dol_buildpath('/infrasfiles/document.php', 1).'?element='.urlencode($element).'&id='.$id;	// the native template builds PHP_SELF?id= without our 'element' parameter
			}
			if (!empty($formmail->param['infrasfiles_bythirdparty'])) {
				return 0;	// "one e-mail per third party" form : recipients and attachments are chosen per third party in the module table
			}
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
		* or the send by e-mail form instead when action = presend (same place and same behaviour as the native cards : no
		* documents section while the form is displayed), and counter badge on the "Documents" tab
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$hookobject	Not used : this hook receives no object, the object of the page is read as a global
		* @param	string			&$hookaction	Not used : this hook receives no action, the action of the page is read as a global
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function printCommonFooter($parameters, &$hookobject, &$hookaction, $hookmanager)
		{
			global $conf, $langs, $user, $db;
			global $object, $action;	// object and action of the native card (not passed by this hook) ; after "Apply" or a send error, doActions set $action to 'presend' while the posted action differs

			if (empty($conf->use_javascript_ajax)) {
				return 0;
			}
			if (in_array($action, array('create', 'edit', 'editline'))) {
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
			$registry	= infrasfiles_get_registry();
			if ($action == 'presend' && !empty($registry[$element]['nativemailtype'])) {
				return 0;	// the native card displays its own send form (and hides its documents) : nothing to add
			}
			$nbdocs	= infrasfiles_count_documents($element, $docobject);
			$out	= '';
			if ($action == 'presend' && infrasfiles_is_enabled($element, 'EMAIL') && infrasfiles_user_can($element, 'write')) {
				// Send form, moved under the action buttons SYNCHRONOUSLY (not in a ready() handler) : the editor (CKEditor) and the
				// select2 lists of the form initialise themselves on ready(), and moving an initialised editor in the DOM breaks it
				$out	.= '<div id = "infrasfiles_presend" class = "hideobject">'.$this->infrasfilesGetPresendForm($element, $docobject).'</div>';
				$out	.= '<script type = "text/javascript">
								(function() {
									var form	= jQuery("#infrasfiles_presend");
									var anchor	= jQuery("div.tabsAction").last();
									if (anchor.length) {
										var parentform	= anchor.closest("form");
										if (parentform.length) {
											anchor	= parentform;
										}
										anchor.after(form);
									}
									form.removeClass("hideobject").show();
								})();
							</script>';
			} elseif (infrasfiles_is_enabled($element, 'DOCUMENT')) {
				$urlsource	= $_SERVER['PHP_SELF'].'?id='.$docobject->id;
				$out		.= '<div id = "infrasfiles_docsection" class = "hideobject">
								<div class = "fichecenter"><div class = "fichehalfleft"><a name = "builddoc"></a>
									<div class = "ficheaddleft">'.infrasfiles_get_document_box($element, $docobject, $urlsource).'</div>
								</div></div>
							</div>';
				// "Third party" column of the file list (objects whose files are addressed to third parties), and mass deletion checkboxes
				$out		.= infrasfiles_get_thirdparty_column_script('#infrasfiles_docsection table.formdoc', infrasfiles_get_file_thirdparty_links($docobject));
				if (infrasfiles_user_can($element, 'write')) {
					$out	.= infrasfiles_get_mass_delete_script('#infrasfiles_docsection table.formdoc', 'box', '', '');
				}
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
		* ECM "object directories" (ecm/index_auto.php) : one automatic directory per object of the registry, named 'infrasfiles-<dirout>'.
		* The core calls this hook with different parameters depending on what it needs :
		*  - none : the directories to add to the tree (module, label, desc, test, position) ;
		*  - 'modulepart' : the list of our module names (the right panel checks it), and, for one of ours, the directory to list
		*    and the class to instantiate to show the link of the object (FormFile::list_of_autoecmfiles()) ;
		*  - 'modulepart' + 'fileinfo' : the ref of the object owning the file, first segment of its path ('<REF>/<file>.pdf').
		* Download links use modulepart 'infrasfiles-<dirout>' : dol_check_secure_access_document() splits it into 'infrasfiles' +
		* '<dirout>/…', which lands on checkSecureAccess() below (native permission of the object).
		* Context 'ecmautocard' only : the right panel (core/ajax/ajaxdirpreview.php) is included by ecm/index_auto.php in the same
		* request (mode 'noajax') ; called standalone, that page refuses any modulepart other than ecm / medias / website anyway.
		*
		* @param	array()			$parameters		Hook metadatas (modulepart, fileinfo)
		* @param	CommonObject	&$object		Not used
		* @param	string			&$action		Not used
		* @param	HookManager		$hookmanager	Hook manager
		* @return	int								0 = nothing for the core, 1 = $this->results filled
		**/
		public function addSectionECMAuto($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;
			$langs->load('infrasfiles@infrasfiles');
			$registry	= infrasfiles_get_registry();
			$modules	= array();	// our ECM module names, indexed by registry element
			foreach ($registry as $element => $definition) {
				if (empty($definition['dirout']) || !infrasfiles_is_enabled($element)) {
					continue;
				}
				$modules[$element]	= 'infrasfiles-'.trim($definition['dirout'], '/');
			}
			if (empty($modules)) {
				return 0;
			}
			// Tree of the ECM page : the directories to add
			if (!isset($parameters['modulepart'])) {
				$this->results	= array();
				$position		= 300;
				foreach ($modules as $element => $module) {
					$label				= $langs->trans($registry[$element]['label']);
					$this->results[]	= array('position'	=> $position,
												'level'		=> 1,
												'module'	=> $module,
												'test'		=> infrasfiles_user_can($element, 'read') ? 1 : 0,
												'label'		=> $label,
												'desc'		=> $langs->trans('ECMDocsBy', $langs->transnoentitiesnoconv($registry[$element]['label'])));
					$position			+= 10;
				}
				return 1;
			}
			// Right panel and file list : our module names, plus the directory and the class when the module asked is one of ours
			$this->results	= array('module' => array_values($modules));
			$element		= array_search($parameters['modulepart'], $modules, true);
			if ($element === false) {
				return 1;
			}
			$this->results['directory']	= infrasfiles_get_output_dir($element, null);
			$this->results['classpath']	= $registry[$element]['classpath'];	// the child class file requires the native parent class itself
			$this->results['classname']	= $registry[$element]['class'];
			if (!empty($parameters['fileinfo']) && is_array($parameters['fileinfo'])) {
				$relative	= isset($parameters['fileinfo']['relativename']) ? (string) $parameters['fileinfo']['relativename'] : '';
				if (strpos($relative, '/') !== false) {
					$this->results['ref']	= substr($relative, 0, strpos($relative, '/'));	// '<REF>/<file>.pdf' ; a file at the root (SPECIMEN.pdf) has no ref and is skipped by the core
				}
			}
			return 1;
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
