	<?php
	/*************************************************
	* Copyright (C) 2013 		ATM Consulting <support@atm-consulting.fr>
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*************************************************/

	/**************************************************
	* 	\defgroup	infrastructure	infrastructure module
	* 	\brief		infrastructure module descriptor.
	* 	\file		core/modules/modinfrastructure.class.php
	* 	\ingroup	infrastructure
	* 	\brief		Description and activation file for module infrastructure
	*************************************************/

	// Libraries ****************************
	include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	dol_include_once('/infrastructure/core/lib/infrastructureAdmin.lib.php');
	dol_include_once('/infrastructure/class/techatm.class.php');

	/**
	* Description and activation class for module infrastructure
	*/
	class modInfrastructure extends DolibarrModules
	{
		public $editor_email;	// @var string Editor email
		public $special;		// @var int Module type (0=common, 1=interface, 2=others, 3=very specific)

		/**
		* 	Constructor. Define names, constants, directories, boxes, permissions
		*
		* 	@param	DoliDB		$db	Database handler
		*/
		public function __construct($db)
		{
			global $langs, $conf;

			$langs->load('infrastructure@infrastructure');
			infrastructure_test_php_ext();
			$this->db 				= $db;
			$this->numero			= 550090;																					// Unique Id for module
			$this->name				= preg_replace('/^mod/i', '', get_class($this));		// Module label (no space allowed)
			$this->editor_name		= '<b>InfraS - Sylvain Legrand</b>';
			$this->editor_email		= 'support@infras.fr';
			$editor_web				= 'https://www.infras.fr/';
			$this->editor_url		= $editor_web;
			$this->url_last_version	= $editor_web.'jdownloads/Modules_Dolibarr/'.$this->name.'/'.$this->name.'.txt';
			$this->rights_class		= $this->name;																				// Key text used to identify module (for permissions, menus, etc...)
			$family					= getDolGlobalString('EASYA_VERSION') ? 'easya' : 'Modules InfraS';					// It is used to group modules in module setup page
			$this->family			= $family;																					// used to group modules in module setup page
			$this->familyinfo		= array($family => array('position' => '001', 'label' => $langs->trans($family)));
			$this->description		= $langs->trans('Module550090Desc');													// Module description
			$this->version			= $this->getLocalVersion();																	// Version : 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
			$this->const_name		= 'MAIN_MODULE_'.strtoupper($this->name);											// llx_const table to save module status enabled/disabled
			$this->special			= 2;																						// (0=common,1=interface,2=others,3=very specific)
			$this->picto			= 'modinfrastructure@infrastructure';														// Name of image file used for this module. If in theme => 'pictovalue' ; if in module => 'pictovalue@module' under name object_pictovalue.png
			$this->module_parts		= array('triggers'	=> 1,
											'hooks'		=> array('invoicecard','invoicesuppliercard','propalcard','supplier_proposalcard','ordercard','ordersuppliercard',
																'odtgeneration','orderstoinvoice','orderstoinvoicesupplier','admin','invoicereccard',
																'consumptionthirdparty','ordershipmentcard','expeditioncard','deliverycard','paiementcard',
																'referencelettersinstacecard','shippableorderlist','propallist','orderlist','invoicelist',
																'supplierorderlist','supplierinvoicelist','cron','pdfgeneration','checkmarginlist'
																),
											'tpl'		=> 1,
											'css'		=> array('css' => '/infrastructure/css/infrastructure.css.php'),
			);
			$this->dirs				= array('/infrastructure/sql');																// Data directories to create when module is enabled.
			$this->config_page_url	= array('infrastructuresetup.php@infrastructure');											// stored into titre/admin directory, used to setup module.
			// Dependencies
			$this->depends			= array();																					// List of modules id that must be enabled if this module is enabled
			$this->requiredby		= array();																					// List of modules id to disable if this one is disabled
			$this->conflictwith		= array('modMilestone');																	// List of modules id that cannot be enabled if this module is enabled
			$this->langfiles		= array('infrastructure@infrastructure');
			$this->const			= array(0	=> array('INFRASTRUCTURE_STYLE_TITRES_SI_LIGNES_CACHEES', 'chaine', 'I', 'Définit le style (B : gras, I : Italique, U : Souligné) des sous titres lorsque le détail des lignes et des ensembles est caché', 1));
			$this->tabs				= array();
			if (!isModEnabled('infrastructure')) {
				$conf->infrastructure			= new stdClass();
				$conf->infrastructure->enabled	= 0;
			}
			// Dictionnaries
			$this->dictionaries		= array('langs'				=>'infrastructure@infrastructure',
											'tabname'			=> array(MAIN_DB_PREFIX.'c_infrastructure_free_text'),			// List of tables we want to see into dictonnary editor
											'tablib'			=> array($langs->trans('InfrastructureFreeLineDictionary')),// Label of tables
											'tabsql'			=> array('SELECT f.rowid as rowid, f.label, f.content, f.entity, f.active FROM '. $db->prefix() .'c_infrastructure_free_text as f WHERE f.entity='.$conf->entity),	// Request to select fields
											'tabsqlsort'		=> array('label ASC'),											// Sort order
											'tabfield'			=> array('label,content'),										// List of fields (result of select to show dictionary)
											'tabfieldvalue'		=> array('label,content'),										// List of fields (list of fields to edit a record)
											'tabfieldinsert'	=> array('label,content,entity'),								// List of fields (list of fields for insert)
											'tabrowid'			=> array('rowid'),												// Name of columns with primary key (try to always name it 'rowid')
											'tabcond'			=> array(isModEnabled('infrastructure'))
										);
			// Boxes
			$this->boxes			= array(); 																					// Boxes list
			// List of cron jobs entries to add
			$this->cronjobs			= array();
			// Permission array used by this module
			$this->rights			= array(); 																					// Permission array used by this module
			$this->menu				= array(); 																					// List of menus to add
		}

		/**
		* Function called when module is enabled.
		* The init function add constants, boxes, permissions and menus
		* (defined in constructor) into Dolibarr database.
		* It also creates data directories
		*
		* 	@param		string	$options	Options when enabling module ('', 'noboxes')
		* 	@return		int					1 if OK, 0 if KO
		*/
		public function init($options = '')
		{
			global $conf, $db, $langs;

			$sql	= array();

			// Création préalable des tables infrastructure (nécessaire comme cible de migration)
			$this->loadTables();

			// Migration depuis le module subtotal si présent et activé
			if (isModEnabled('subtotal')) {
				dol_include_once('/infrastructure/core/lib/infrastructureMigrateSubtotal.lib.php');
				$logMessages		= array();
				$logger				= function ($msg) use (&$logMessages) {
					$logMessages[]	= $msg;
					dol_syslog('modInfrastructure::init migrate-subtotal : '.$msg);
				};
				// Étape 1 : test (dry-run)
				$dryRun				= infrastructure_migrateFromSubtotal($db, $conf, true, $logger);
				if (! $dryRun['success']) {
					$this->error	= $langs->trans('InfrastructureMigrateSubtotalFailed').' : '.implode(' | ', $dryRun['errors']);
					dol_syslog('modInfrastructure::init migration dry-run FAILED : '.implode(' | ', $dryRun['errors']).' — messages : '.implode("\n", $logMessages), LOG_ERR);
					return 0;
				}
				// Étape 2 : exécution réelle
				$realRun			= infrastructure_migrateFromSubtotal($db, $conf, false, $logger);
				if (! $realRun['success']) {
					$this->error	= $langs->trans('InfrastructureMigrateSubtotalRealRunFailed').' : '.implode(' | ', $realRun['errors']);
					dol_syslog('modInfrastructure::init migration real-run FAILED : '.implode(' | ', $realRun['errors']).' — messages : '.implode("\n", $logMessages), LOG_ERR);
					return 0;
				}
				// Étape 3 : désactivation subtotal + cleanup
				$resCleanup			= infrastructure_cleanupSubtotal($db, $conf, $logger);
				if (! $resCleanup) {
					$this->error	= $langs->trans('InfrastructureCleanupSubtotalFailed');
					dol_syslog('modInfrastructure::init cleanup subtotal FAILED — messages : '.implode("\n", $logMessages), LOG_ERR);
					return 0;
				}
				// Étape 4 : migration special_code 104777 (valeur utilisée par subtotal) → 550090
				$dryRunCode			= infrastructure_migrateSpecialCode($db, $conf, true, $logger);
				if (! $dryRunCode['success']) {
					$this->error	= $langs->trans('InfrastructureMigrateSpecialCodeFailed').' : '.implode(' | ', $dryRunCode['errors']);
					dol_syslog('modInfrastructure::init migration special_code dry-run FAILED : '.implode(' | ', $dryRunCode['errors']).' — messages : '.implode("\n", $logMessages), LOG_ERR);
					return 0;
				}
				if ($dryRunCode['updated'] > 0) {
					$resCode			= infrastructure_migrateSpecialCode($db, $conf, false, $logger);
					if (! $resCode['success']) {
						$this->error	= $langs->trans('InfrastructureMigrateSpecialCodeFailed').' : '.implode(' | ', $resCode['errors']);
						dol_syslog('modInfrastructure::init migration special_code real-run FAILED : '.implode(' | ', $resCode['errors']).' — messages : '.implode("\n", $logMessages), LOG_ERR);
						return 0;
					}
					dol_syslog('modInfrastructure::init migration special_code 104777 → 550090 OK : '.$resCode['updated'].' ligne(s) mise(s) à jour');
				}
				dol_syslog('modInfrastructure::init migration subtotal → infrastructure OK — messages : '.implode("\n", $logMessages));
			}
			dol_include_once('/infrastructure/core/lib/infrastructure.lib.php');

			$TElementType	= array('propaldet', 'commandedet', 'facturedet', 'supplier_proposaldet', 'commande_fournisseurdet', 'facture_fourn_det');
			foreach ($TElementType as $element_type) {
				infrastructure_addExtraField('show_total_ht', $langs->trans('SubTotalShowTotalHTOnSubtotalBlock'), 'int', 0, 10, $element_type, 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
				infrastructure_addExtraField('show_reduc', $langs->trans('SubTotalShowReductionOnSubtotalBlock'), 'int', 0, 10, $element_type, 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
				infrastructure_addExtraField('infrastructure_show_qty', $langs->trans('SubTotalLineShowQty'), 'int', 0, 10, $element_type, 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			}
			infrastructure_addExtraField('hideblock', $langs->trans('Subtotal_ForceHideAll'), 'int', 4, 2, 'propaldet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('hideblock', $langs->trans('Subtotal_ForceHideAll'), 'int', 4, 2, 'commandedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('hideblock', $langs->trans('Subtotal_ForceHideAll'), 'int', 4, 2, 'commande_fournisseurdet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('hideblock', $langs->trans('Subtotal_ForceHideAll'), 'int', 4, 2, 'facturedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('hideblock', $langs->trans('Subtotal_ForceHideAll'), 'int', 4, 2, 'facture_fourn_det', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('show_table_header_before', $langs->trans('SubTotalShowTableHeaderBefore'), 'int', 4, 2, 'propaldet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('show_table_header_before', $langs->trans('SubTotalShowTableHeaderBefore'), 'int', 4, 2, 'commandedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('show_table_header_before', $langs->trans('SubTotalShowTableHeaderBefore'), 'int', 4, 2, 'facturedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('print_as_list', $langs->trans('SubTotalPrintAsList'), 'int', 4, 2, 'propaldet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('print_as_list', $langs->trans('SubTotalPrintAsList'), 'int', 4, 2, 'commandedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('print_as_list', $langs->trans('SubTotalPrintAsList'), 'int', 4, 2, 'facturedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('print_condensed', $langs->trans('SubTotalPrintCondensed'), 'int', 4, 2, 'propaldet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('print_condensed', $langs->trans('SubTotalPrintCondensed'), 'int', 4, 2, 'commandedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			infrastructure_addExtraField('print_condensed', $langs->trans('SubTotalPrintCondensed'), 'int', 4, 2, 'facturedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
			if (isModEnabled('oblyon') && getDolGlobalString('MAIN_MENU_INVERT') && getDolGlobalString('OBLYON_HIDE_LEFTMENU')) {
				// Désactive le sommaire rapide
				dolibarr_set_const($db, 'INFRASTRUCTURE_DISABLE_SUMMARY', 1, 'chaine', 0, '', $conf->entity);
			}
			return $this->_init($sql, $options);
		}

		/**
		* Function called when module is disabled.
		* Remove from database constants, boxes and permissions from Dolibarr database.
		* Data directories are not deleted
		*
		* 	@param		string	$options	Options when enabling module ('', 'noboxes')
		* 	@return		int					1 if OK, 0 if KO
		*/
		public function remove($options = '')
		{
			global $conf;

			infrastructure_bkup_module ($this->name);
			$sql	= array('DELETE FROM '.$this->db->prefix().'const WHERE name like "INFRASTRUCTURE\_%" AND entity = "'.$conf->entity.'"',
							'DROP TABLE IF EXISTS '.$this->db->prefix().'c_infrastructure_free_text'
							);
			return $this->_remove($sql, $options);
		}

		/**
		* Create tables, keys and data required by module
		* Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
		* and create data commands must be stored in directory /titre/sql/
		* This function is called by this->init
		*
		* 	@return		int		<=0 if KO, >0 if OK
		*/
		private function loadTables()
		{
			return $this->_load_tables('/infrastructure/sql/');
		}

		/**
		* Function called to check module name from local changelog
		* Control of the min version of Dolibarr needed
		* If dolibarr version does'nt match the min version the module is disabled
		* @return		string		current version or error message
		**/
		function getLocalVersion()
		{
			global $langs;

			if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1)	{
				return $langs->trans('InfrastructureChangelogXMLError');
			}
			$currentversion					= array();
			$currentversion					= infrastructure_getLocalVersionMinDoli($this->name);
			$this->need_dolibarr_version	= explode('.', $currentversion[1]);	// Minimum version of Dolibarr required by module
			$this->phpmin					= explode('.', $currentversion[5]);	// Minimum version of PHP required by module
			$this->phpmax					= explode('.', $currentversion[6]);	// Maximum version of PHP required by module
			if (!getDolGlobalString('INFRASTRUCTURE_DISABLE_CHECK_VERSION_MIN', '') && version_compare($currentversion[1], DOL_VERSION, '>')) {
				$this->disabled	= true;
			}
			return $currentversion[0];
		}

		/**
		* Function called to view changelog on help tab
		* @return		string		html view
		**/
		function getChangeLog()
		{
			$currentversion	= infrastructure_getLocalVersionMinDoli($this->name);
			$ChangeLog		= infrastructure_getChangeLog($this->name, $currentversion[0], $currentversion[2], $currentversion[3], 0);
			return $ChangeLog;
		}
	}
