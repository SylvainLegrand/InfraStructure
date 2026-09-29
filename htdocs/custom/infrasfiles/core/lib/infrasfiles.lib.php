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
	* 	\file		./infrasfiles/core/lib/infrasfiles.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Registry of supported objects and helper functions for module InfraSFiles
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

	/**
	*	Return the registry of objects supported by the module.
	*	Each entry describes how the generic layers (documents, attached files tab, e-mail) must plug on a native object.
	*	Third party modules can add their own entries through the hook 'infrasFilesRegisterObjects' (context 'infrasfilesregistry').
	*
	*	Keys of an entry :
	*		label		Translation key of the document name (shown in setup and tabs)
	*		picto		Picto shown before the name
	*		class		Name of the module child class adding generateDocument() to the native class (documents layer)
	*		classpath	Path of that child class (dol_include_once)
	*		parentclass	Name of the native class
	*		parentpath	Path of the native class (DOL_DOCUMENT_ROOT relative)
	*		table		Native table (without prefix)
	*		modelspath	Relative directory of the PDF models (searched in every module declaring 'models')
	*		docpart		Sub part used by FormFile::showdocuments() : modulepart 'infrasfiles:<docpart>' loads core/modules/infrasfiles/modules_<docpart>.php and class ModelePDF<Docpart>,
	*					and type used in llx_document_model
	*		modulepart	Value used for document.php / dol_check_secure_access_document() : always 'infrasfiles' (generic branch + hook checkSecureAccess for the native permission)
	*		dirout		Output directory relative to the module data directory ($conf->infrasfiles->dir_output), files of an object in <dirout>/<ref>/
	*		tabcontext	Context used by complete_head_from_modules() on the native card
	*		hookcontext	Hook context of the native card
	*		cardurl		URL of the native card (DOL_URL_ROOT relative), or list of pages receiving the "Attached files" section
	*		listurl		URL of the native list (DOL_URL_ROOT relative)
	*		headlib		Native library defining the tabs function (DOL_DOCUMENT_ROOT relative)
	*		headfunction	Native function returning the tabs of the card
	*		needmodule	List of native modules : at least one must be enabled for the entry to be available
	*		permread	Permission (array for $user->hasRight()) required to read the files
	*		permwrite	Permission (array for $user->hasRight()) required to generate / delete files
	*		langs		Translation files to load with the object
	*		mailtype	Type of the e-mail templates (llx_c_email_templates.type_template) and $modelmail of the send form
	*		mailtopic	Translation key of the default e-mail subject (with __REF__)
	*		mailtemplate	Prefix of the translation keys (<prefix>Label / Topic / Content) of the default e-mail template inserted at activation
	*		trigger		Trigger code fired after sending (<OBJECT>_SENTBYMAIL) : agenda event through the native agenda trigger
	*		trackid		Prefix of the e-mail tracking id (3 letters + object id)
	*		options		Object specific options shown on the setup page : array(SUFFIX => array('type', 'label', 'values', 'default', 'help')) - 'help' = translation key of the tooltip (optional)
	*
	*	@return		array		Registry
	**/
	function infrasfiles_get_registry()
	{
		global $db;

		static $registry	= null;

		if (is_array($registry)) {
			return $registry;
		}
		$registry	= array(
			'widthdraw'	=> array('label'		=> 'InfraSFilesObjWidthdraw',
									'picto'			=> 'payment',
									'class'			=> 'InfrasFilesWithdraw',
									'classpath'		=> '/infrasfiles/class/infrasfileswithdraw.class.php',
									'parentclass'	=> 'BonPrelevement',
									'parentpath'	=> '/compta/prelevement/class/bonprelevement.class.php',
									'table'			=> 'prelevement_bons',
									'modelspath'	=> 'core/modules/infrasfiles/widthdraw/doc/',
									'docpart'		=> 'infrasfileswidthdraw',
									'modulepart'	=> 'infrasfiles',
									'dirout'		=> 'widthdraw',
									'tabcontext'	=> 'prelevement',
									'hookcontext'	=> 'directdebitprevcard',
									'cardurl'		=> '/compta/prelevement/card.php',
									'listurl'		=> '/compta/prelevement/orders_list.php',
									'headlib'		=> '/core/lib/prelevement.lib.php',
									'headfunction'	=> 'prelevement_prepare_head',
									'needmodule'	=> array('prelevement', 'paymentbybanktransfer'),
									'permread'		=> array('prelevement', 'bons', 'lire'),
									'permwrite'		=> array('prelevement', 'bons', 'creer'),
									'langs'			=> array('withdrawals', 'banks', 'bills'),
									'mailtype'		=> 'infrasfiles_widthdraw',
									'mailtopic'		=> 'InfraSFilesMailTopicWidthdraw',
									'mailtemplate'	=> 'InfraSFilesMailTplWidthdraw',	// prefix of the translation keys (…Label, …Topic, …Content) of the default e-mail template
									'trigger'		=> 'WIDTHDRAW_SENTBYMAIL',
									'trackid'		=> 'wdr',
									'options'		=> array('SPLIT_MODE'	=> array('type'		=> 'select',
																						'label'		=> 'InfraSFilesOptSplitMode',
																						'help'		=> 'InfraSFilesOptSplitModeHelp',
																						'values'	=> array('thirdparty'	=> 'InfraSFilesSplitThirdparty',
																											'parent'		=> 'InfraSFilesSplitParent'),	// one PDF per parent company for the third parties flagged with the extrafield
																						'default'	=> 'thirdparty'),
															'FREE_TEXT'		=> array('type'		=> 'textarea',
																						'label'		=> 'InfraSFilesOptFreeText',
																						'default'	=> ''),
															'WATERMARK'		=> array('type'		=> 'text',
																						'label'		=> 'InfraSFilesOptWatermark',
																						'default'	=> ''))
									),
			'inventory'	=> array('label'		=> 'InfraSFilesObjInventory',
									'picto'			=> 'inventory',
									'class'			=> 'InfrasFilesInventory',
									'classpath'		=> '/infrasfiles/class/infrasfilesinventory.class.php',
									'parentclass'	=> 'Inventory',
									'parentpath'	=> '/product/inventory/class/inventory.class.php',
									'table'			=> 'inventory',
									'modelspath'	=> 'core/modules/infrasfiles/inventory/doc/',
									'docpart'		=> 'infrasfilesinventory',
									'modulepart'	=> 'infrasfiles',
									'dirout'		=> 'inventory',
									'tabcontext'	=> 'inventory',
									'hookcontext'	=> 'inventorycard',
									'cardurl'		=> array('/product/inventory/card.php', '/product/inventory/inventory.php'),	// card + counting lines page
									'listurl'		=> '/product/inventory/list.php',
									'headlib'		=> '/product/inventory/lib/inventory.lib.php',
									'headfunction'	=> 'inventoryPrepareHead',
									'needmodule'	=> array('stock'),
									'permread'		=> array('stock', 'lire'),
									'permwrite'		=> array('stock', 'creer'),
									'langs'			=> array('stocks', 'products'),
									'mailtype'		=> 'infrasfiles_inventory',
									'mailtopic'		=> 'InfraSFilesMailTopicInventory',
									'mailtemplate'	=> 'InfraSFilesMailTplInventory',
									'trigger'		=> 'INVENTORY_SENTBYMAIL',
									'trackid'		=> 'inv',
									// The storage zone of the counting sheet is not an option here : it follows the "Zone" column setup of the module InfraSWorkflow
									// (section "Inventory management" : product extrafield or location categories), see infrasfiles_inventory_zone_config()
									'options'		=> array('SHOW_QTY'		=> array('type'		=> 'on_off',
																						'label'		=> 'InfraSFilesOptShowQty',
																						'default'	=> '0'),
															'FREE_TEXT'		=> array('type'		=> 'textarea',
																						'label'		=> 'InfraSFilesOptFreeText',
																						'default'	=> ''),
															'WATERMARK'		=> array('type'		=> 'text',
																						'label'		=> 'InfraSFilesOptWatermark',
																						'default'	=> ''))
									),
		);
		// Third party modules can register their own objects
		$hookmanager	= new HookManager($db);
		$hookmanager->initHooks(array('infrasfilesregistry'));
		$parameters		= array('registry' => &$registry);
		$hookmanager->executeHooks('infrasFilesRegisterObjects', $parameters);
		if (!empty($hookmanager->resArray) && is_array($hookmanager->resArray)) {
			foreach ($hookmanager->resArray as $element => $definition) {
				if (is_array($definition) && !isset($registry[$element])) {
					$registry[$element]	= $definition;
				}
			}
		}
		return $registry;
	}

	/**
	*	Build the name of a module constant for an object
	*
	*	@param		string		$element	Object key of the registry (ex : 'widthdraw')
	*	@param		string		$suffix		Constant suffix (ex : 'ENABLED', 'DOCUMENT', 'EMAIL', 'ADDON_PDF', 'SPLIT_MODE')
	*	@return		string					Constant name (ex : 'INFRASFILES_WIDTHDRAW_ENABLED')
	**/
	function infrasfiles_const_name($element, $suffix)
	{
		return 'INFRASFILES_'.strtoupper(preg_replace('/[^a-z0-9]/i', '_', $element)).'_'.strtoupper($suffix);
	}

	/**
	*	Test if the native module(s) needed by an object are enabled
	*
	*	@param		array		$definition		Registry entry
	*	@return		bool						true if at least one needed module is enabled
	**/
	function infrasfiles_module_available($definition)
	{
		if (empty($definition['needmodule'])) {
			return true;
		}
		foreach ((array) $definition['needmodule'] as $module) {
			if (isModEnabled($module)) {
				return true;
			}
		}
		return false;
	}

	/**
	*	Test if an object is enabled in the module setup, optionally for a given feature
	*
	*	@param		string		$element	Object key of the registry
	*	@param		string		$feature	'' = object enabled, 'DOCUMENT' = PDF generation + attached files, 'EMAIL' = send by e-mail
	*	@return		bool
	**/
	function infrasfiles_is_enabled($element, $feature = '')
	{
		$registry	= infrasfiles_get_registry();
		if (empty($registry[$element]) || !infrasfiles_module_available($registry[$element])) {
			return false;
		}
		if (!getDolGlobalInt(infrasfiles_const_name($element, 'ENABLED'), 0)) {
			return false;
		}
		if (!empty($feature) && !getDolGlobalInt(infrasfiles_const_name($element, $feature), 0)) {
			return false;
		}
		return true;
	}

	/**
	*	Return the value of an object option, with its default when not set
	*
	*	@param		string		$element	Object key of the registry
	*	@param		string		$option		Option suffix (ex : 'SPLIT_MODE')
	*	@return		string					Value
	**/
	function infrasfiles_get_option($element, $option)
	{
		$registry	= infrasfiles_get_registry();
		$default	= isset($registry[$element]['options'][$option]['default']) ? $registry[$element]['options'][$option]['default'] : '';
		return getDolGlobalString(infrasfiles_const_name($element, $option), $default);
	}

	/**
	*	Code of the third party extrafield (boolean) "address the slips to the parent company", created by the module at activation
	*
	*	@return		string		Extrafield code (column of llx_societe_extrafields)
	**/
	function infrasfiles_to_parent_field()
	{
		return 'infrasfiles_to_parent';
	}
	/**
	*	Rule "this line is addressed to the parent company of its third party" : the third party must have a parent company
	*	(native field 'parent') AND the extrafield must be checked on its card. One level only (the direct parent).
	*
	*	@param		array		$line		Line of the order (keys 'fk_parent' and 'to_parent', see InfrasFilesWithdraw::infrasfilesFetchLines())
	*	@return		bool
	**/
	function infrasfiles_line_addressed_to_parent($line)
	{
		return !empty($line['fk_parent']) && !empty($line['to_parent']);
	}
	/**
	*	Test if a document model is activated (row in llx_document_model) for the current entity
	*
	*	@param		string		$doctype	Type in llx_document_model (docpart of the registry, ex : 'infrasfileswidthdraw')
	*	@param		string		$name		Model name (ex : 'bordereau')
	*	@return		bool
	**/
	function infrasfiles_document_model_is_active($doctype, $name)
	{
		global $db, $conf;
		$sql	= 'SELECT COUNT(rowid) AS nb FROM '.$db->prefix().'document_model';
		$sql	.= " WHERE type = '".$db->escape($doctype)."' AND nom = '".$db->escape($name)."' AND entity = ".((int) $conf->entity);
		$resql	= $db->query($sql);
		$obj	= $resql ? $db->fetch_object($resql) : null;
		return !empty($obj->nb);
	}
	/**
	*	Find the registry element whose e-mail template type is the given one
	*
	*	@param		string		$mailtype	Template type (ex : 'infrasfiles_widthdraw')
	*	@return		string					Element key or ''
	**/
	function infrasfiles_element_from_mailtype($mailtype)
	{
		foreach (infrasfiles_get_registry() as $element => $definition) {
			if (!empty($definition['mailtype']) && $definition['mailtype'] == $mailtype) {
				return $element;
			}
		}
		return '';
	}

	/**
	*	Allowed values of a 'select' option : static list ('values', translation keys) or list computed by a function ('values_callback', translated labels)
	*
	*	@param		array		$option		Option definition of the registry
	*	@return		array					array(value => label)
	**/
	function infrasfiles_get_option_values($option)
	{
		global $langs;

		if (!empty($option['values_callback']) && function_exists($option['values_callback'])) {
			return (array) call_user_func($option['values_callback']);
		}
		$values	= array();
		foreach ((array) (isset($option['values']) ? $option['values'] : array()) as $value => $label) {
			$values[$value]	= $langs->trans($label);
		}
		return $values;
	}

	/**
	*	Storage zone configuration of the counting sheet, read from the module InfraSWorkflow (section "Inventory management" :
	*	"Zone" column of the inventory lines, product extrafield or parent category of the location categories). The source is
	*	decided by InfraSWorkflow itself (infrasworkflow_inventoryZoneSqlParts() : extrafield first, category otherwise) so that
	*	the sheet and the "Zone" column always show the same thing. Optional dependency : without InfraSWorkflow, no zone.
	*
	*	@return		array		array('source' => 'extrafield' | 'category' | '', 'extrafield' => code, 'category' => id of the parent category)
	**/
	function infrasfiles_inventory_zone_config()
	{
		$config	= array('source' => '', 'extrafield' => '', 'category' => 0);

		if (!isModEnabled('infrasworkflow')) {
			return $config;
		}
		dol_include_once('/infrasworkflow/core/lib/infrasworkflow.lib.php');
		if (!function_exists('infrasworkflow_inventoryZoneSqlParts')) {
			return $config;	// older InfraSWorkflow without the "Zone" column
			}
		$parts	= infrasworkflow_inventoryZoneSqlParts();
		if ($parts['source'] == 'extrafield' && preg_match('/^[a-z0-9_]+$/i', $parts['extrafield'])) {
			$config['source']		= 'extrafield';
			$config['extrafield']	= $parts['extrafield'];
		} elseif ($parts['source'] == 'category') {
			$config['source']	= 'category';
			$config['category']	= getDolGlobalInt('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 0);
		}
		return $config;
	}

	/**
	*	Ordered list of the zones of the counting sheet : the order of the list is the order of the sheet.
	*	Extrafield of type list : the values in the order of the list ; category : the sub categories in creation order (chronological) ;
	*	extrafield of type text : empty list (the values are not known in advance, the sheet sorts them alphabetically)
	*
	*	@param		array		$config		Result of infrasfiles_inventory_zone_config()
	*	@return		array					array(key => array('label' => , 'rank' => )), key = extrafield value or category id
	**/
	function infrasfiles_inventory_zone_list($config)
	{
		global $db;

		$zones	= array();
		if ($config['source'] == 'extrafield') {
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields	= new ExtraFields($db);
		$extrafields->fetch_name_optionals_label('product');
			$field	= $config['extrafield'];
			if (!empty($extrafields->attributes['product']['param'][$field]['options']) && is_array($extrafields->attributes['product']['param'][$field]['options'])) {
				$rank	= 0;
		foreach ($extrafields->attributes['product']['param'][$field]['options'] as $code => $label) {
			if ($code === '' || $code === null) {
				continue;
			}
					$zones[(string) $code]	= array('label' => (string) $label, 'rank' => $rank++);
				}
			}
		} elseif ($config['source'] == 'category' && (int) $config['category'] > 0) {
			$sql	= 'SELECT rowid, label FROM '.$db->prefix().'categorie WHERE fk_parent = '.((int) $config['category']);
			$sql	.= ' AND entity IN ('.getEntity('category').') ORDER BY rowid ASC';	// creation order
			$resql	= $db->query($sql);
			$rank	= 0;
			while ($resql && ($obj = $db->fetch_object($resql))) {
				$zones[(string) $obj->rowid]	= array('label' => (string) $obj->label, 'rank' => $rank++);
			}
		}
		return $zones;
	}

	/**
	*	Base data directory of the module for the current entity
	*
	*	@return		string		Full path (without trailing slash)
	**/
	function infrasfiles_get_base_dir()
	{
		global $conf;

		if (!empty($conf->infrasfiles->multidir_output[$conf->entity])) {
			$dir	= $conf->infrasfiles->multidir_output[$conf->entity];
		} elseif (!empty($conf->infrasfiles->dir_output)) {
			$dir	= $conf->infrasfiles->dir_output;
		} else {
			$dir	= DOL_DATA_ROOT.'/infrasfiles';
		}
		// Keep the RAW value (only a trailing slash removed) : when the data root ends with "/", Dolibarr itself builds
		// <data root>/<module> with a double slash, and the ECM index is matched on the exact string DOL_DATA_ROOT.'/'.filepath
		// (completeFileArrayWithDatabaseInfo) : normalising here would break that match and re-index (duplicate uk_ecm_files)
		return rtrim($dir, '/');
	}

	/**
	*	Directory of the files of an object, relative to the module data directory (ex : 'widthdraw/T260901')
	*
	*	@param		string		$element	Object key of the registry
	*	@param		object|null	$object		Object (its ref is used) or null for the directory of the element only
	*	@return		string					Relative directory (without slashes at both ends)
	**/
	function infrasfiles_get_subdir($element, $object = null)
	{
		$registry	= infrasfiles_get_registry();
		$dirout		= isset($registry[$element]['dirout']) ? trim($registry[$element]['dirout'], '/') : $element;
		return $dirout.(is_object($object) && !empty($object->ref) ? '/'.dol_sanitizeFileName($object->ref) : '');
	}

	/**
	*	Full output directory of the files of an object (or of the element when no object is given)
	*
	*	@param		string		$element	Object key of the registry
	*	@param		object|null	$object		Object (its ref is used) or null
	*	@return		string					Full path (without trailing slash)
	**/
	function infrasfiles_get_output_dir($element, $object = null)
	{
		return infrasfiles_get_base_dir().'/'.infrasfiles_get_subdir($element, $object);
	}

	/**
	*	Test the native permission of a user on an object of the registry
	*
	*	@param		string		$element	Object key of the registry
	*	@param		string		$mode		'read' or 'write'
	*	@param		User|null	$fuser		User to check (null = current user)
	*	@return		bool
	**/
	function infrasfiles_user_can($element, $mode = 'read', $fuser = null)
	{
		global $user;

		$checkuser	= is_object($fuser) ? $fuser : $user;	// the file access hook receives the user to check from the core (may differ from the session user)
		$registry	= infrasfiles_get_registry();
		$key		= ($mode == 'write') ? 'permwrite' : 'permread';
		if (empty($registry[$element][$key])) {
			return !empty($checkuser->admin);
		}
		return (bool) call_user_func_array(array($checkuser, 'hasRight'), array_values((array) $registry[$element][$key]));
	}
	/**
	*	Element of the registry owning a file path relative to the module data directory (ex : 'widthdraw/T260901/T260901-CU.pdf' => 'widthdraw')
	*	Used by the file access hook and by the deletion from the native cards : a file is always handled with the permission of ITS element
	*
	*	@param		string		$file		Relative path ('<dirout>/<ref>/<name>')
	*	@return		string					Element key, '' when the path is not under the directory of an element
	**/
	function infrasfiles_element_from_file($file)
	{
		$file	= ltrim(str_replace('\\', '/', (string) $file), '/');
		foreach (infrasfiles_get_registry() as $element => $definition) {
			$dirout	= trim((string) $definition['dirout'], '/');
			if ($dirout !== '' && strpos($file, $dirout.'/') === 0) {
				return $element;
			}
		}
		return '';
	}

	/**
	*	Instantiate the module child class of an object, fetch it and load its document state
	*
	*	@param		string		$element	Object key of the registry
	*	@param		int			$id			Object id
	*	@return		object|null				Object (child class using InfrasFilesDocumentTrait) or null
	**/
	function infrasfiles_load_object($element, $id)
	{
		global $db;

		$registry	= infrasfiles_get_registry();
		if (empty($registry[$element]) || $id <= 0) {
			return null;
		}
		$definition	= $registry[$element];
		if (!empty($definition['parentpath'])) {
			require_once DOL_DOCUMENT_ROOT.$definition['parentpath'];
		}
		dol_include_once($definition['classpath']);
		if (!class_exists($definition['class'])) {
			return null;
		}
		$object	= new $definition['class']($db);
		if ($object->fetch($id) <= 0) {
			return null;
		}
		$object->infrasfilesLoadDocumentState();
		return $object;
	}

	/**
	*	Instantiate the module child class of an object filled with specimen data (no database record), for the preview of the models
	*
	*	@param		string		$element	Object key of the registry
	*	@return		object|null				Object (child class using InfrasFilesDocumentTrait) or null
	**/
	function infrasfiles_load_specimen($element)
	{
		global $db;

		$registry	= infrasfiles_get_registry();
		if (empty($registry[$element])) {
			return null;
		}
		$definition	= $registry[$element];
		if (!empty($definition['parentpath'])) {
			require_once DOL_DOCUMENT_ROOT.$definition['parentpath'];
		}
		dol_include_once($definition['classpath']);
		if (!class_exists($definition['class'])) {
			return null;
		}
		$object	= new $definition['class']($db);
		if (!method_exists($object, 'infrasfilesInitAsSpecimen')) {
			return null;
		}
		$object->infrasfilesInitAsSpecimen();
		return $object;
	}

	/**
	*	Number of attached files and links of an object (badge of the Documents tab)
	*
	*	@param		string		$element	Object key of the registry
	*	@param		object		$object		Object
	*	@return		int
	**/
	function infrasfiles_count_documents($element, $object)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$nbfiles	= count(dol_dir_list(infrasfiles_get_output_dir($element, $object), 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nblinks	= Link::count($db, $object->element, $object->id);
		return $nbfiles + $nblinks;
	}

	/**
	*	HTML of the native "Attached files" box (model selection + Generate button + file list) for an object
	*
	*	@param		string		$element	Object key of the registry
	*	@param		object		$object		Object (child class of the module, document state loaded)
	*	@param		string		$urlsource	URL receiving the actions builddoc / remove_file
	*	@return		string					HTML
	**/
	function infrasfiles_get_document_box($element, $object, $urlsource)
	{
		global $db, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
		$registry	= infrasfiles_get_registry();
		if (empty($registry[$element])) {
			return '';
		}
		$definition	= $registry[$element];
		if (!empty($definition['langs'])) {
			$langs->loadLangs((array) $definition['langs']);
		}
		$formfile	= new FormFile($db);
		$genallowed	= (infrasfiles_is_enabled($element, 'DOCUMENT') && infrasfiles_user_can($element, 'write')) ? 1 : 0;
		$delallowed	= infrasfiles_user_can($element, 'write') ? 1 : 0;
		return $formfile->showdocuments('infrasfiles:'.$definition['docpart'], infrasfiles_get_subdir($element, $object), infrasfiles_get_output_dir($element, $object), $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang, '', $object);
	}

	/**
	*	Return the list of PDF models available for an object (files pdf_*.modules.php found in every module declaring 'models')
	*
	*	@param		string		$element	Object key of the registry
	*	@return		array					array(modelname => array('file' => full path, 'classname' => class))
	**/
	function infrasfiles_get_models($element)
	{
		global $conf;

		$registry	= infrasfiles_get_registry();
		$models		= array();
		if (empty($registry[$element]['modelspath'])) {
			return $models;
		}
		$dirmodels	= array('/');
		if (!empty($conf->modules_parts['models']) && is_array($conf->modules_parts['models'])) {
			$dirmodels	= array_merge($dirmodels, $conf->modules_parts['models']);
		}
		foreach ($dirmodels as $reldir) {
			$dir	= dol_buildpath($reldir.$registry[$element]['modelspath'], 0);
			if (!is_dir($dir)) {
				continue;
			}
			$files	= dol_dir_list($dir, 'files', 0, '^pdf_.*\.modules\.php$', '', 'name', SORT_ASC, 0);
			foreach ($files as $file) {
				$name	= preg_replace('/^pdf_(.*)\.modules\.php$/', '$1', $file['name']);
				if (!isset($models[$name])) {
					$models[$name]	= array('file' => $file['fullname'], 'classname' => 'pdf_'.$name);
				}
			}
		}
		return $models;
	}
