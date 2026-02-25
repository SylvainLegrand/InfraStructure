<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand		- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/core/lib/infraspackplusAdmin.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraS module
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');

	/**
	* Define head array for setup pages tabs
	*
	* @return	array			list of head
	**/
	function infraspackplus_admin_prepare_head ()
	{
		global $langs, $conf, $user;

		$h		= 0;
		$head	= array();
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramDolibarr'))) {
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/generalpdf.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsGeneralPDF');
			$head[$h][2]	= 'generalpdf';
		}
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramInfraSPlus'))) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/infrasplussetup.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsPDF');
			$head[$h][2]	= 'infrasplussetup';
		}
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramImages'))) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/images.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsImages');
			$head[$h][2]	= 'images';
		}
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramAdresses'))) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/adresses.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsAdresses');
			$head[$h][2]	= 'adresses';
		}
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramExtraFields'))) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/extrafields.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsExtraFields');
			$head[$h][2]	= 'extrafields';
		}
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramMentions'))) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/mentions.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsMentions');
			$head[$h][2]	= 'mentions';
		}
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramNotes'))) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/notes.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsNotes');
			$head[$h][2]	= 'notes';
		}
		if (!empty($user->admin) || !empty($user->hasRight('infraspackplus', 'paramGeneration'))) {
			$h++;
			$head[$h][0]	= dol_buildpath('/infraspackplus/admin/generation.php', 1);
			$head[$h][1]	= $langs->trans('InfraSPlusParamsGeneration');
			$head[$h][2]	= 'generation';
		}
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infraspackplus_admin');
		$h++;
		$head[$h][0]	= dol_buildpath('/infraspackplus/admin/about.php', 1);
		$head[$h][1]	= $langs->trans('About');
		$head[$h][2]	= 'about';
		$h++;
		$head[$h][0]	= dol_buildpath('/infraspackplus/admin/changelog.php', 1);
		$head[$h][1]	= $langs->trans('InfraSPlusParamsChangeLog');
		$head[$h][2]	= 'changelog';
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infraspackplus_admin', 'remove');
		return $head;
	}

	/**
	*	Test if the menu InfraS on tools top menu in loaded
	*
	**/
	function infraspackplus_no_topmenu()
	{
		global $db, $conf;

		// gestion de la position du menu
		$sql	= 'SELECT rowid FROM '.$db->prefix().'menu WHERE mainmenu = "tools" AND leftmenu = "infras" AND entity = '.((int) $conf->entity);
		$resql	= $db->query($sql);
		if (!empty($resql)) {
			// il y a un left menu on renvoie 0 : pas besoin d'en créer un nouveau
			if ($db->num_rows($resql) > 0) {
				return 0;
			}
		}
		return 1;	// pas de top menu on renvoie 1
	}

	/**
	*	Test if the PHP extension 'XML' is loaded
	*
	**/
	function infraspackplus_test_php_ext()
	{
		global $db, $conf, $langs;

		$langs->load('infraspackplus@infraspackplus');

		if (extension_loaded('xml')) {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
		} else {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	-1, 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
			setEventMessages('<span class = "infraspluscaution">'.$langs->trans('InfraSPlusCautionMess').'</span>'.$langs->trans('InfraSXMLextError'), array(), 'warnings');
		}
	}

	/**
	* Function called to check module name from local changelog
	* Control of the min version of Dolibarr needed and get versions list
	*
	* @param	string	$appliname	module name
	* @return	array				[0] current version from changelog
	*								[1] Dolibarr min version
	*								[2] flag for error (-1 = KO ; 0 = OK)
	*								[3] array => versions list or errors list
	*								[4] Dolibarr max version
	*								[5] PHP min version
	*								[6] PHP max version
	**/
	function infraspackplus_getLocalVersionMinDoli($appliname)
	{
		global $langs;

		$currentversion	= array();
		$sxe			= infraspackplus_getChangelogFile($appliname);
		if (is_object($sxe)) {
			$currentversion[0]	= (string) $sxe->Version[count($sxe->Version) - 1]->attributes()->Number;
			$currentversion[1]	= (string) $sxe->Dolibarr->attributes()->minVersion;
			$currentversion[2]	= 0;
			$currentversion[3]	= $sxe->Version;
			$currentversion[4]	= (string) $sxe->Dolibarr->attributes()->maxVersion;
			$currentversion[5]	= (string) $sxe->PHP->attributes()->minVersion;
			$currentversion[6]	= (string) $sxe->PHP->attributes()->maxVersion;
		} else {
			$currentversion[0]	= '<span class = "infrasPlusCaution"><b>'.$langs->trans('InfraSPlusChangelogXMLError').'</b></span>';
			$currentversion[1]	= $langs->trans('InfraSPlusnoMinDolVersion');
			$currentversion[2]	= -1;
			$currentversion[3]	= $langs->trans('InfraSPlusChangelogXMLError');
			$currentversion[4]	= $langs->trans('InfraSPlusnoMaxDolVersion');
			$currentversion[5]	= $langs->trans('InfraSPlusnoMinDolVersion');
			$currentversion[6]	= $langs->trans('InfraSPlusnoMaxDolVersion');
			foreach (libxml_get_errors() as $error) {
				$currentversion[3]	.= $error->message;
				dol_syslog('infraspackplusAdmin.Lib::infraspackplus_getLocalVersionMinDoli error->message = '.$error->message);
			}
		}
		return $currentversion;
	}

	/**
	* Function called to check module name from local changelog
	* Control of the min version of Dolibarr needed and get versions list
	*
	* @param	string			$appliname	module name
	* @param	string			$from		sufixe name to separate inner changelog from download
	* @return	string|boolean				changelog file contents or false
	**/
	function infraspackplus_getChangelogFile($appliname, $from = '')
	{
		$file	= empty($from) ? dol_buildpath($appliname, 0).'/docs/changelog.xml' : DOL_DATA_ROOT.'/'.$appliname.'/changelogdwn.xml';
		if (is_file($file)) {
			libxml_use_internal_errors(true);
			$context	= stream_context_create(array('http' => array('method' => 'GET', 'header' => 'Accept: application/xml')));
			$changelog	= @file_get_contents($file, false, $context);
			$sxe		= @simplexml_load_string(rtrim($changelog));
			dol_syslog('infraspackplusAdmin.Lib::infraspackplus_getChangelogFile appliname = '.$appliname.' from = '.$from.' context = '.$context.' changelog = '.($changelog ? 'Ok' : 'KO').' sxe = '.($sxe ? 'Ok' : 'KO'));
			return $sxe;
		} else {
			return false;
		}
	}

	/**
	* Function called to check the available version by downloading the last changelog file
	* Check if the last changelog downloaded is less than 7 days if we do not do anything
	*
	* @return		string		current version with information about new ones on tooltip or error message
	**/
	function infraspackplus_dwnChangelog($appliname)
	{
		$path	= DOL_DATA_ROOT.'/'.$appliname;
		if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1) {
			return -1;
		}
		$newVersion	= getURLContent('https://infras.fr/jdownloads/Modules_Dolibarr/'.$appliname.'/changelog.xml', 'GET', '', 1, array(), array('http', 'https'), 0);
		if (!isset($newVersion['content'])) {	// not connected
			return -1;
		} else {
			$newhtmlversion		= preg_replace('#Downloaded=\".+\"#', 'Downloaded="'.date('Ymd').'"', $newVersion['content']);
			file_put_contents($path.'/changelogdwn.xml', $newhtmlversion);
		}
		return 1;
	}

	/**
	* Migration of llx_societe_address to llx_infraspackplus_societe_address
	*
	* @return	int			1 = Ok, -1 = Ko, 0 = Nothing to migrate
	*/
	function infraspackplus_migration_societe_address()
	{
		global $db, $conf;

		// Check if the destination table exists
		$sql	= 'SHOW TABLES LIKE "'.$db->prefix().'infraspackplus_societe_address"';
		$resql	= $db->query($sql);
		if (!$resql || $db->num_rows($resql) == 0) {
			return -1; // No destination table
		}
		// Check if the source table exists
		$sql	= 'SHOW TABLES LIKE "'.$db->prefix().'societe_address"';
		$resql	= $db->query($sql);
		if (!$resql || $db->num_rows($resql) == 0) {
			return 0; // No table to migrate
		}
		// Check the number of records
		$sql	= 'SELECT COUNT(*) AS nb FROM '.$db->prefix().'societe_address';
		$resql	= $db->query($sql);
		if ($resql) {
			$obj	= $db->fetch_object($resql);
			if (empty($obj->nb)) {
				return 0; // No data to migrate
			}
		}
		$columnsToMigrate	= array('rowid', 'datec', 'tms', 'label', 'fk_soc', 'name', 'address', 'zip', 'town', 'fk_pays', 'phone', 'fax', 'note', 'fk_user_creat', 'fk_user_modif');
		$columns			= array('entity', 'email', 'url');
		$columnsExists		= array();
		foreach ($columns as $column) {	// Check the existence of required columns
			$sql	= 'SHOW COLUMNS FROM '.$db->prefix().'societe_address LIKE "'.$db->escape($column).'"';
			$resql	= $db->query($sql);
			if ($resql) {
				$num					= $db->num_rows($resql);
				$columnsExists[$column]	= ($num > 0);
			} else {
				$columnsExists[$column]	= false;
			}
		}
		// Add the optional columns if they exist
		if ($columnsExists['entity']) {
			$columnsToMigrate[]	= 'entity';
		}
		if ($columnsExists['email']) {
			$columnsToMigrate[]	= 'email';
		}
		if ($columnsExists['url']) {
			$columnsToMigrate[]	= 'url';
		}
		// Migration
		$db->begin();
		 $sqlMigration		= 'INSERT IGNORE INTO '.$db->prefix().'infraspackplus_societe_address ('.(!$columnsExists['entity'] ? 'entity, ' : '').implode(', ', $columnsToMigrate).')';
		 $sqlMigration		.= ' SELECT '.(!$columnsExists['entity'] ? ((int) $conf->entity).', ' : '').implode(', ', $columnsToMigrate).' FROM '.$db->prefix().'societe_address';
		 $resqlMigration	= $db->query($sqlMigration);
		 if ($resqlMigration) {
			$sqlDelete		= 'DELETE FROM '.$db->prefix().'societe_address';
			$resqlDelete	= $db->query($sqlDelete);
			if ($resqlDelete) {
				 $db->commit();
				 return 1;
			} else {
				dol_print_error($db);
				$db->rollback();
				return -1;
			}
		} else {
			dol_print_error($db);
			$db->rollback();
			return 0;
		}
	}

	/**
	*	Sauvegarde les paramètres du module
	*
	*	@param		string		$appliname	module name
	*	@return		string		1 = Ok or -1 = Ko or or 0 and error message
	**/
	function infraspackplus_bkup_module ($appliname)
	{
		global $db, $conf, $langs;

		// Control dir and file
		$path		= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
		$bkpfile	= $path.'/update.'.$conf->entity;
		if (! file_exists($path)) {
			if (dol_mkdir($path) < 0) {
				setEventMessage($langs->transnoentities('ErrorCanNotCreateDir', $path), 'errors');
				return -1;
			}
		}
		if (file_exists($path)) {
			$currentversion	= infraspackplus_getLocalVersionMinDoli('infraspackplus');
			$handle			= fopen($bkpfile, 'w+');
			if (fwrite($handle, '') === FALSE) {
				$langs->load('errors');
				setEventMessage($langs->transnoentities('ErrorFailedToWriteInDir'), 'errors');
				return -1;
			}
			// Print headers and global mysql config vars
			$sqlhead	= '-- '.$db::LABEL.' dump via php with Dolibarr '.DOL_VERSION.'
--
-- Host: '.$db->db->host_info.'	Database: '.$db->database_name.'
-- ------------------------------------------------------
-- Server version			'.$db->db->server_info.'
-- Dolibarr version			'.DOL_VERSION.'
-- InfraSPackPlus version	'.$currentversion[0].'

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = \'NO_AUTO_VALUE_ON_ZERO\';
';
			fwrite($handle, $sqlhead);
			$cols_model			= array ('nom', 'entity', 'type', 'libelle');
			$duplicate_model	= array ('3', 'libelle', 'nom');
			$sql_model			= 'SELECT '.implode(', ', $cols_model);
			$sql_model			.= ' FROM '.$db->prefix().'document_model';
			$sql_model			.= ' WHERE nom LIKE "INFRASPLUS\_%" AND entity = "'.$conf->entity.'"';
			$sql_model			.= ' ORDER BY nom';
			fwrite($handle, infraspackplus_bkup_table ('document_model', $sql_model, $cols_model, $duplicate_model, 0, ''));
			$cols_const			= array ('name', 'entity', 'value', 'type', 'visible', 'note');
			$duplicate_const	= array ('2', 'value', 'name');
			$sql_const			= 'SELECT '.implode(', ', $cols_const);
			$sql_const			.= ' FROM '.$db->prefix().'const';
			$sql_const			.= ' WHERE ((name LIKE "INFRASPLUS\_%" AND name NOT LIKE "INFRASPLUS\_PDF\_VALID\_CORE\_CHGT") OR name LIKE "INFRASPACKPLUS\_PS\_%" OR (name LIKE "%\_ADDON\_PDF" AND value LIKE "InfraSPlus\_%") OR name LIKE "%\_FREE\_TEXT%" OR name LIKE "%\_PUBLIC\_NOTE%")';
			$sql_const			.= ' AND entity = "'.$conf->entity.'"';
			$sql_const			.= ' ORDER BY name';
			$autoupdate			= getDolGlobalInt('MAIN_DISABLE_PDF_AUTOUPDATE', 0);
			$onDuplicate		= $db->type == 'pgsql' ? ' ON CONFLICT (name) DO UPDATE SET ' : ' ON DUPLICATE KEY UPDATE ';
			$add				= 'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES (\'MAIN_DISABLE_PDF_AUTOUPDATE\', \'__ENTITY__\', \''.$autoupdate.'\', \'chaine\', \'0\', \'InfraSPackPlus module\')'.$onDuplicate.'value = \''.$autoupdate.'\';
';
			fwrite($handle, infraspackplus_bkup_table ('const', $sql_const, $cols_const, $duplicate_const, 0, $add));
			$cols_addr			= array ('entity', 'datec', 'tms', 'label', 'fk_soc', 'name', 'address', 'zip', 'town', 'fk_pays', 'phone', 'fax', 'email', 'url', 'note', 'fk_user_creat', 'fk_user_modif');
			$sql_addr			= 'SELECT '.implode(', ', $cols_addr);
			$sql_addr			.= ' FROM '.$db->prefix().'infraspackplus_societe_address';
			$sql_addr			.= ' WHERE entity = "'.$conf->entity.'"';
			fwrite($handle, infraspackplus_bkup_table ('infraspackplus_societe_address', $sql_addr, $cols_addr, array(), 0, ''));
			$cols_dict			= array ('code', 'entity', 'pos', 'libelle', 'active');
			$duplicate_dict		= array ('3', 'libelle', 'code');
			$sql_dict_1			= 'SELECT '.implode(', ', $cols_dict);
			$sql_dict_mention	= ' FROM '.$db->prefix().'c_infraspackplus_mention';
			$sql_dict_2			= ' WHERE entity = "'.$conf->entity.'" ORDER BY pos';
			fwrite($handle, infraspackplus_bkup_table ('c_infraspackplus_mention', $sql_dict_1.$sql_dict_mention.$sql_dict_2, $cols_dict, $duplicate_dict, 1, ''));
			$sql_dict_note		= ' FROM '.$db->prefix().'c_infraspackplus_note';
			fwrite($handle, infraspackplus_bkup_table ('c_infraspackplus_note', $sql_dict_1.$sql_dict_note.$sql_dict_2, $cols_dict, $duplicate_dict, 1, ''));
			// Enabling back the keys/index checking
			$sqlfooter		= '
SET FOREIGN_KEY_CHECKS = 1;
UPDATE llx_const AS co SET co.value = REPLACE(co.value, \'None\', \'none\')	WHERE co.name LIKE \'%INFRASPLUS_PDF_PARAMS%\';
-- Dump completed on '.date('Y-m-d G-i-s').'
';
			fwrite($handle, $sqlfooter);
			fclose($handle);
			if (file_exists($bkpfile)) {
				dol_copy($bkpfile, DOL_DATA_ROOT.($conf->entity != 1 ? '/'.$conf->entity : '').'/admin/'.$appliname.'_update'.date('Y-m-d-G-i-s').'.'.$conf->entity);
			}
			return 1;
		}
		return 0;
	}

	/**
	*	Recherche d'un fichier contenant un code langue dans son nom à partir d'une liste
	*
	*	@param	string	$table		table name to backup
	*	@param	string	$sql		sql query to prepare data	for backup
	*	@param	array	$listeCols	list of columns to backup on the table
	*	@param	array	$duplicate	values for 'ON DUPLICATE KEY UPDATE'
	*									[0] = column to update
	*									[1] = column name to update
	*									[2] = key value for conflict control (only postgreSQL)
	*	@param	boolean	$truncate	truncate the table before restore
	*	@param	string	$add		sql data to add on the beginning of the query
	*	@return	string				sql query to restore the datas
	**/
	function infraspackplus_bkup_table ($table, $sql, $listeCols, $duplicate = array (), $truncate = 0, $add = '')
	{
		global $db;

		$sqlnewtable	= '';
		$result_sql		= $sql ? $db->query($sql) : '';
		dol_syslog('infraspackplusAdmin.Lib::infraspackplus_bkup_table sql = '.$sql);
		if (!empty($result_sql)) {
			$truncate		= $truncate ? 'TRUNCATE TABLE '.$db->prefix().$table.';
' : '';
			$sqlnewtable	= '
-- Dumping data for table '.$db->prefix().$table.'
'.$truncate.$add;
			while($row	= $db->fetch_row($result_sql)) {
				// For each row of data we print a line of INSERT
				$colsInsert	= '';
				foreach ($listeCols as $col) {
					$colsInsert	.= $col.', ';
				}
				$sqlnewtable	.= 'INSERT INTO '.$db->prefix().$table.' ('.substr($colsInsert, 0, -2).') VALUES (';
				$columns		= count($row);
				$duplicateValue	= '';
				for ($j = 0; $j < $columns; $j++) {
					// Processing each columns of the row to ensure that we correctly save the value (eg: add quotes for string - in fact we add quotes for everything, it's easier)
					if ($row[$j] == null && !is_string($row[$j])) {
						$row[$j]	= 'NULL';	// IMPORTANT: if the field is NULL we set it NULL
					} elseif(is_string($row[$j]) && $row[$j] == '') {
						$row[$j]	= '\'\'';	// if it's an empty string, we set it as an empty string
					} else {																	// else for all other cases we escape the value and put quotes around
						$row[$j]	= addslashes($row[$j]);
						$row[$j]	= preg_replace('#\n#', '\\n', $row[$j]);
						$row[$j]	= '\''.$row[$j].'\'';
					}
					if ($j == 1) {
						$row[$j]	= '\'__ENTITY__\'';
					}
					if (!empty($duplicate) && !empty($duplicate[0]) && !empty($duplicate[1]) && !empty($duplicate[2])) {
						$onDuplicate	= $db->type == 'pgsql' ? ' ON CONFLICT ('.$duplicate[2].') DO UPDATE SET ' : ' ON DUPLICATE KEY UPDATE ';
						$duplicateValue	.= $j == $duplicate[0] ? $onDuplicate.$duplicate[1].' = '.$row[$j] : '';
					}
				}
				$sqlnewtable	.= implode(', ', $row).')'.$duplicateValue.';
';
			}
		}
		return $sqlnewtable;
	}

	/**
	*	Restaure les paramètres du module
	*
	*	@param		string		$appliname	module name
	*	@return		string		1 = Ok or -1 = Ko
	**/
	function infraspackplus_restore_module ($appliname)
	{
		global $conf;

		$pathsql	= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
		dol_syslog('infraspackplusAdmin.Lib::infraspackplus_restore_module $pathsql = '.$pathsql);
		$handle		= @opendir($pathsql);
		if (is_resource($handle)) {
			$filesql	= $pathsql.'/'.'update.'.$conf->entity;
			$moved		= dol_copy($filesql, $filesql.'.sql');
			if (is_file($filesql.'.sql')) {
				$result	= run_sql($filesql.'.sql', (!getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG', '') ? 1 : 0), $conf->entity, 1);
			}
			$delete	= dol_delete_file($filesql.'.sql');
			dol_syslog('infraspackplusAdmin.Lib::infraspackplus_restore_module appliname = '.$appliname.' filesql = '.$filesql.' moved = '.$moved.' result = '.$result.' delete = '.$delete);
			if ($result > 0) {
				return 1;
			}
		}
		return -1;
	}

	/**
	*	Set InfraS template as default
	*
	*	@return		string		1 = Ok or -1 = Ko
	**/
	function infraspackplus_Change_Template ()
	{
		global $db, $conf;

		$array_sql	= array('INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_BL", "'.$conf->entity.'", "shipping", "InfraSPlus_BL") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_BL"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_BR", "'.$conf->entity.'", "delivery", "InfraSPlus_BR") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_BR"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_Bom", "'.$conf->entity.'", "bom", "InfraSPlus_BOM") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_Bom"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_C", "'.$conf->entity.'", "order", "InfraSPlus_C") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_C"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_CF", "'.$conf->entity.'", "order_supplier", "InfraSPlus_CF") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_CF"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_CT", "'.$conf->entity.'", "contract", "InfraSPlus_CT") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_CT"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_D", "'.$conf->entity.'", "propal", "InfraSPlus_D") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_D"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_DF", "'.$conf->entity.'", "supplier_proposal", "InfraSPlus_DF") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_DF"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_F", "'.$conf->entity.'", "invoice", "InfraSPlus_F") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_F"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_FF", "'.$conf->entity.'", "invoice_supplier", "InfraSPlus_FF") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_FF"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_FI", "'.$conf->entity.'", "ficheinter", "InfraSPlus_FI") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_FI"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_MRP", "'.$conf->entity.'", "mrp", "InfraSPlus_MRP") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_MRP"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_NDF", "'.$conf->entity.'", "expensereport", "InfraSPlus_NDF") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_NDF"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_P", "'.$conf->entity.'", "product", "InfraSPlus_P") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_P"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_PJ", "'.$conf->entity.'", "project", "InfraSPlus_PJ") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_PJ"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_RE", "'.$conf->entity.'", "reception", "InfraSPlus_RE") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_RE"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_ST", "'.$conf->entity.'", "stock", "InfraSPlus_ST") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_ST"',
							'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("InfraSPlus_UST", "'.$conf->entity.'", "user", "InfraSPlus_UST") ON DUPLICATE KEY UPDATE nom = "InfraSPlus_UST"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("BOM_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_Bom", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_Bom"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("COMMANDE_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_C", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_C"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("COMMANDE_SUPPLIER_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_CF", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_CF"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("CONTRACT_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_CT", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_CT"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("EXPEDITION_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_BL", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_BL"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("EXPENSEREPORT_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_NDF", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_NDF"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("FACTURE_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_F", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_F"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("FICHEINTER_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_FI", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_FI"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("INVOICE_SUPPLIER_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_FF", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_FF"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("LIVRAISON_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_BR", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_BR"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("MRP_MO_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_MRP", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_MRP"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("PRODUCT_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_P", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_P"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("PROJECT_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_PJ", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_PJ"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("PROPALE_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_D", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_D"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("RECEPTION_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_RE", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_RE"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("STOCK_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_ST", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_ST"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("SUPPLIER_PROPOSAL_ADDON_PDF", "'.$conf->entity.'", "InfraSPlus_DF", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_DF"',
							'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("USER_ADDON_PDF_ODT", "'.$conf->entity.'", "InfraSPlus_UST", "chaine", "0", "InfraSPackPlus module") ON DUPLICATE KEY UPDATE value = "InfraSPlus_UST"'
							);
		$err		= 0;
		$num		= count($array_sql);
		for ($i = 0; $i < $num; $i++) {
			if (empty($err)) {
				dol_syslog('infraspackplusAdmin.lib.php::infraspackplus_Change_Template', LOG_DEBUG);
				$result				= $db->query($array_sql[$i]);
				if (empty($result))	$err++;
			}
		}
		return empty($err) ? 1 : -1;
	}

	/**
	*	Converts shorthand memory notation value to bytes
	*	From http://php.net/manual/en/function.ini-get.php
	*
	*	@param	string	$val	Memory size shorthand notation
	*	@return	int				value in bytes
	**/
	function infraspackplus_return_bytes($val)
	{
		$val	= trim($val);
		$last	= strtolower($val[strlen($val)-1]);
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/**
	*	Returns the column numbering and checks for an error like the same number twice
	*
	*	@param		array	$selectvalue	selected array with 'select' and 'value' keys
	*	@param		array	$listselect		array of all arrays with 'select' and 'value' keys
	*	@return		array
	**/
	function num_col(&$selectvalue, $listselect)
	{
		$nbCol	= is_array($listselect)	? count($listselect) + 1 : 12;
		$nums	= array('options' => '', 'err' => 0);
		for ($i = 1 ; $i < $nbCol ; $i++) {
			$afficher			= $i < 10 ? '0'.$i : $i;
			$nums['options']	.= '<option name = "'.$selectvalue['select'].'" value = "'.$i.'"';
			if ($selectvalue['value'] == $i) {
				$nums['options']	.= ' selected';
			}
			$nums['options']	.= '>'.$afficher.'</option>';
		}
		foreach ($listselect as $selectvalues) {
			if ($selectvalues['select'] != $selectvalue['select'] && $selectvalues['value'] == $selectvalue['value']) {
				$nums['err']++;
			}
		}
		return $nums;
	}

	/**
	*	Print HTML backup / restore section
	*
	*	@return		void
	**/
	function infraspackplus_print_backup_restore()
	{
		global $conf, $langs, $mc;

		print '	<table class = "centpercent noborderspacing">';
		$metas	= array('*', '90px', '156px', '120px');
		infraspackplus_print_colgroup($metas);
		print '		<tr>
						<td colspan = "2" class = "center infrasplustitleparam">
							<a href = "'.DOL_URL_ROOT.'/document.php?modulepart=infraspackplus&file=sql/update.'.$conf->entity.'">'.$langs->trans('InfraSPlusParamAction1').' <b><span>'.$langs->trans('modcomnamePackPlus').'</span></b> '.$langs->trans('InfraSPlusParamAction2').'</a>
						</td>
						<td class = "right"><button class = "butAction" type = "submit" value = "bkupParams" name = "action">'.$langs->trans('InfraSPlusParamBkup').'</button></td>
						<td class = "center"><button class = "butActionDelete" type = "submit" value = "restoreParams" name = "action">'.$langs->trans('InfraSPlusParamRestore').'</button></td>
					</tr>';
		if (isModEnabled('multicompany') && is_object($mc)) {
			$list	= $mc->getEntitiesList();
			$label	= $mc->label ? $mc->label : $list[$conf->entity];
			print '	<tr class = "infrasplusheight75">
						<td colspan = "2" class = "center infrasplustitleparam">'.$langs->trans('InfraSPlusAutoUpdateContent', $label).'</td>
						<td class = "center infrasplustitleparam">';
							print $mc->select_entities('', 'entity', '', false, array($conf->entity), true, false, '', 'minwidth300imp');
			print'		</td>
						<td class = "center"><button class = "butAction infraspluscopyParamsBtn" type = "submit" value = "copyParams" name = "action">'.$langs->trans('InfraSPlusParamCopy').'</button></td>
					</tr>';
		}
		infraspackplus_print_hr(count($metas));
		infraspackplus_print_final(count($metas));
		print '	</table>';
	}

	/**
	*	Load a title with picto
	*
	*	@param	string	$titre				Title to show
	*	@param	string	$morehtmlright		Added message to show on right
	*	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
	*	@param	int		$pictoisfullpath	1=Icon name is a full absolute url of image
	*	@param	string	$id					To force an id on html objects
	*	@param	string	$morecssontable		More css on table
	*	@param	string	$morehtmlcenter		Added message to show on center
	*	@return	string
	**/
	function infraspackplus_load_title($titre, $morehtmlright = '', $picto = 'generic', $pictoisfullpath = 0, $id = '', $morecssontable = '', $morehtmlcenter = '')
	{
		$out	= '';
		if ($picto == 'setup')	{
			$picto	= 'generic';
		}
		$out	.= '<table '.(!empty($id) ? 'id = "'.$id.'" ' : '').'class = "centpercent notopnoleftnoright table-fiche-title'.(!empty($morecssontable) ? ' '.$morecssontable : '').'">
					<tr class = "liste_titre">';
		if (!empty($picto)) {
			$out .= '	<td class = "infrasplusnoborder infrasplusnopadding widthpictotitle valignmiddle col-picto">'.img_picto('', $picto, 'class = "valignmiddle infraspluswidthpictotitle pictotitle"', $pictoisfullpath).'</td>';
		}
		$out	.= '	<td class = "infrasplusnoborder infrasplusnopadding valignmiddle col-title"><div class = "infrasplusDivTitre uppercase inline-block">'.$titre.'</div></td>';
		if (dol_strlen($morehtmlcenter)) {
			$out .= '	<td class = "infrasplusnoborder infrasplusnopadding center valignmiddle">'.$morehtmlcenter.'</td>';
		}
		if (dol_strlen($morehtmlright)) {
			$out .= '	<td class = "infrasplusnoborder infrasplusnopadding titre_right wordbreakimp right valignmiddle">'.$morehtmlright.'</td>';
		}
		$out .= '	</tr>
				</table>';
		return $out;
	}

	/**
	*	Print HTML colgroup for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	**/
	function infraspackplus_print_colgroup($metas = array())
	{
		print '	<tr>';
		foreach ($metas as $values)	{
			print '<td class = "infrasplusFinal infrasplusnopadding"'.($values == '*' ? '' : ' width = "'.$values.'"').' style =" height: 1px;'.($values == '*' ? '' : ' max-width: '.$values.'; min-width: '.$values.'; width: '.$values.';').'">&nbsp;</td>';
		}
		print '	</tr>';
	}

	/**
	*	Print HTML title for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	**/
	function infraspackplus_print_liste_titre($metas = array())
	{
		global $langs;

		print '	<tr class = "liste_titre">';
		for ($i = 1 ; $i < count($metas) ; $i++) {
			print '	<td colspan = "'.$metas[0][$i - 1].'" class = "center">'.$langs->trans($metas[$i]).'</td>';
		}
		print '	</tr>';
	}

	/**
	*	Print HTML action button for admin page
	*
	*	@param		string		$action			action name (with prefix => 'update_')
	*	@param		string		$desc			Description of action (writes on the first line)
	*	@param		int			$cs1			first colspan
	*	@param		string		$alignclass		Class used to align the description
	*	@param		string		$lbl			button label (translate key)
	*	@param		boolean		$noRowspan		don't use rowspan attribute
	*	@return		void
	**/
	function infraspackplus_print_btn_action($action, $desc = '', $cs1 = 3, $alignclass = 'center', $lbl = 'Modify', $noRowspan = false)
	{
		global $langs;

		print '	<tr>
					<td colspan = "'.$cs1.'" class = "'.$alignclass.'">'.$desc.'</td>
					<td'.(empty($noRowspan) ? ' rowspan = "0"' : '').' class = "center valigntop"><button class = "button infraspluswidth110" type = "submit" value = "update_'.$action.'" name = "action">'.$langs->trans($lbl).'</button></td>
				</tr>';
	}

	/**
	*	Print HTML HR line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infraspackplus_print_hr($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'"><hr class = "infrasplusHR"></td></tr>';
	}

	/**
	*	Print HTML subtitle line
	*
	*	@param		int			$cs1		first colspan
	*	@param		string		$subtitle	subtitle or translation key for subtitle
	*	@return		void
	**/
	function infraspackplus_print_subTitle($cs1 = 3, $subtitle = '')
	{
		global $langs;

		infraspackplus_print_hr($cs1);
		print '	<tr>
					<td colspan = "'.$cs1.'" class = "center"><span class = "infrasplussubtitleparam">'.$langs->trans($subtitle).'</span></td>
				</tr>';
	}

	/**
	*	Print HTML final line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infraspackplus_print_final($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'" class = "infrasplusFinal">&nbsp;</td></tr>';
	}

	/**
	*	Print HTML action button for admin page
	*
	*	@param		string			$confkey	action name (with prefix => 'update_')
	*	@param		string			$tag		input type (on/off button, input, textarea, color select, select, select_types_paiements, selectTypeContact)
	*	@param		string			$desc		Description of action
	*	@param		string			$help		Help description => active tooltip
	*	@param		array|string	$metas		list of HTML parameters and values (example : 'type'=>'text' and/or 'class'=>'flat center', etc...)
	*	@param		int				$cs1		first colspan
	*	@param		int				$cs2		second colspan => we add it with $cs1 in case off textarea
	*	@param		string			$end		if input element string to be added after or empty td to finish the line
	*	@param		int				$num		Add a numbering column first with this number
	*	@return		int						line number for next option
	**/
	function infraspackplus_print_input($confkey, $tag = 'on_off', $desc = '', $help = '', $metas = '', $cs1 = 2, $cs2 = 1, $end = '', $num = 0)
	{
		global $langs, $conf, $db;

		$form			= new Form($db);
		$formother		= new FormOther($db);
		$formcompany	= new FormCompany($db);
		$formactions	= new FormActions($db);
		print '	<tr class = "oddeven">';
		if (!empty($num)) {
			print '	<td class = "center bold">'.$num.'</td>';
			$num++;
		}
		if ($tag != 'textarea') {
			print '	<td colspan = "'.$cs1.'">';
			if (!empty($help))	{
				print $form->textwithtooltip(($desc ? $desc : $langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));

			} else {
				print $desc ? $desc : $langs->trans($confkey);
			}
			print '	</td>
					<td colspan = "'.$cs2.'" class = "center">';
		} else {
			print '	<td colspan = "'.($cs1 + $cs2).'" class = "center">';
			if (!empty($desc))	{
				print $desc.'<br/>';
			}
		}
		if ($tag == 'on_off') {
			print '		<a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=set_'.$confkey.'&token='.newToken().'&value='.(getDolGlobalString($confkey, '') ? '0' : '1').'">';
			print ajax_constantonoff($confkey);
			print '		</a>';
		} elseif ($tag == 'on_off2') {
			print '		<a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=set_'.$confkey.'&token='.newToken().'&value='.(strpos(getDolGlobalString($confkey, ''), $metas) !== false ? '0' : '1').'">
							'.(strpos(getDolGlobalString($confkey, ''), $metas) !== false ? img_picto($langs->trans('Activated'), 'switch_on') : img_picto($langs->trans('Disabled'), 'switch_off')).'
						</a>';
		} elseif ($tag == 'input') {
			// management of the minimum value of number type input fields
			$inputValue	= getDolGlobalString($confkey, '');
			if ($metas['type'] == 'number' && !empty($metas['min'])) {
				$currentValue	= getDolGlobalInt($confkey, $metas['min']);
				$inputValue		= $currentValue < $metas['min'] ? $metas['min'] : $currentValue;
			}
			// default input
			$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrasplusnopadding', 'style' => 'font-size: inherit;', 'name' => $confkey, 'id' => $confkey, 'value' => $inputValue);
			$metas			= array_merge ($defaultMetas, $metas);
			$metascompil	= '';
			foreach ($metas as $key => $value) {
				$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' ? '' : ' = "'.$value.'"');
			}
			print '	<'.$tag.' '.$metascompil.'>'.(!preg_match('/<td(.*)/', $end, $reg) ? $end : '');
		} elseif ($tag == 'input2') {
			foreach ($metas as $keymeta => $meta2) {
				if (preg_match('/intercal(.*)/', $keymeta, $reg)) {
					print $meta2;
					continue;
				}
				$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrasplusnopadding infrasplusfontsizeinherit', 'name' => $keymeta, 'id' => $keymeta, 'value' => getDolGlobalString($keymeta, ''));
				$meta			= array_merge ($defaultMetas, $meta2);
				$metascompil	= '';
				foreach ($meta as $key => $value) {
					$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' || $key == 'checked' ? '' : ' = "'.$value.'"');
				}
				print '	<input '.$metascompil.'>';
			}
			print !preg_match('/<td(.*)/', $end, $reg) ? $end : '';
		} elseif ($tag == 'radio') {
			foreach ($metas as $keymeta => $meta2) {
				if (preg_match('/intercal(.*)/', $keymeta, $reg)) {
					print $meta2;
					continue;
				}
				$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrasplusnopadding infrasplusfontsizeinherit', 'id' => $keymeta);
				$meta			= array_merge ($defaultMetas, $meta2);
				$metascompil	= '';
				foreach ($meta as $key => $value) {
					$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' || $key == 'checked' ? '' : ' = "'.$value.'"');
				}
				print '	<'.$tag.' '.$metascompil.'>';
			}
			print !preg_match('/<td(.*)/', $end, $reg) ? $end : '';
		} elseif ($tag == 'textarea') {
			if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT', '')) {
				print '<textarea name = "'.$confkey.'" class = "flat" cols = "120">'.getDolGlobalString($confkey, '').'</textarea>';
			} else {
				$doleditor	= new DolEditor($confkey, getDolGlobalString($confkey, ''), 0, 80, 'dolibarr_notes');
				print $doleditor->Create();
			}
		} elseif ($tag == 'color') {
			print $formother->selectColor($metas, $confkey, '', 1, array(), 'right hideifnotset');
		} elseif ($tag == 'select') {
			print $metas;
		} elseif ($tag == 'select_produits') {
			$form->select_produits(getDolGlobalString($confkey, ''), $confkey, $metas[0], $metas[1], $metas[2], $metas[3], $metas[4], $metas[5], $metas[6], $metas[7], $metas[8], $metas[9], $metas[10], $metas[11], $metas[12], $metas[13], $metas[14], $metas[15]);
		} elseif ($tag == 'select_types_paiements') {
			$form->select_types_paiements(getDolGlobalString($confkey, ''), $confkey, $metas[0], $metas[1], $metas[2], $metas[3], $metas[4]);
		} elseif ($tag == 'selectTypeContact') {
			print $formcompany->selectTypeContact($metas[0], $metas[1], $confkey, $metas[2], $metas[3], $metas[4], $metas[5]);
		} elseif ($tag == 'select_type_actions') {
			$formactions->select_type_actions(getDolGlobalString($confkey, ''), $confkey, $metas[0], $metas[1], $metas[2]);
		} elseif ($tag == 'editor') {
			$doleditor	= new DolEditor($confkey, getDolGlobalString($confkey, ''), $metas[0], $metas[1], $metas[2]);
			print $doleditor->Create();
		}
		print '		</td>';
		if (preg_match('/<td(.*)/', $end, $reg)) {
			print $end;
		}
		print '	</tr>';
		return $num;
	}

	/**
	*	Print HTML action button for admin page
	*
	*	@param		string		$type		input type (empty or tests)
	*	@param		string		$desc		Description of action
	*	@param		array		$metas		list of columns with input keys and values to test
	*	@param		int			$cs1		first colspan
	*	@param		int			$w			width for input columns
	*	@param		string		$end		element string to be added on the last td of the line
	*	@param		int			$num		Add a numbering column first with this number
	*	@return		int						line number for next option
	**/
	function infraspackplus_print_line_inputs($type = '', $desc = '', $metas = array(), $cs1 = 2, $w = 0, $end = '', $num = 0)
	{
		print '	<tr class = "oddeven">';
		if (!empty($num)) {
			print '	<td class = "center bold">'.$num.'</td>';
			$num++;
		}
		print '		<td colspan = "'.$cs1.'">
						<table class = "centpercent">
							<tr>
								<td rowspan = "2" class = "infrasplusnoborder">'.$desc.'</td>';
		foreach ($metas[0] as $confkey => $value) {
			$confkey	= str_replace('_AUTO', '', $confkey);
			print '				<td class = "center infrasplusnoborder" style = "max-width: '.$w.'px; min-width: '.$w.'px; width: '.$w.'px;">'.($type == 'tests' ? (getDolGlobalString($confkey, '') ? $value : '&nbsp;') : $value).'</td>';
		}
		print '				</tr>
							<tr>';
		foreach ($metas[1] as $confkey => $value) {
			print '				<td class = "center infrasplusnoborder">';
			if ($type == 'tests' && !getDolGlobalString($value, '')) {
				print '&nbsp;';
			} else {
				print '				<a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=set_'.$confkey.'&token='.newToken().'&value='.(getDolGlobalString($confkey, '') ? '0' : '1').'">';
				print ajax_constantonoff($confkey);
				print '				</a>'.($type == 'tests' ? '' : $value);
			}
			print '				</td>';
		}
		print '				</tr>
						</table>
					</td>';
		empty($end) ? print '' : print '<td class = "center">'.$end.'</td>';
		print '	</tr>';
		return $num;
	}

	/**
	* Function called to get downloaded changelog and compare with the local one
	* Presentation of results on a HTML table
	*
	* @param	string	$appliname		module name
	* @param	string	$version		version number
	* @param	string	$resVersion		flag for error (-1 = KO ; 0 = OK)
	* @param	array	$tblversions	array => versions list or errors list
	* @param	int		$dwn			flag to show download button (0 = hide it ; 1 = show it)
	* @return	string					HTML presentation
	**/
	function infraspackplus_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn = 0)
	{
		global $langs, $user;

		$langs->loadLangs(array('admin', 'errors', 'infraspackplus@infraspackplus'));

		$supportURL				= 'https://support.infras.fr/create_ticket.php';
		$headerPath				= dol_buildpath('/'.$appliname.'/img/InfraSheader.png', 1);
		$logoPath				= dol_buildpath('/'.$appliname.'/img/InfraS.png', 1);
		$logoDolistorePath		= dol_buildpath('/'.$appliname.'/img/dolistore_logo.png', 1);
		$preferedPartnerPath	= dol_buildpath('/'.$appliname.'/img/Dolibarr_preferred_partner.png', 1);
		$listUpD				= dol_buildpath('/'.$appliname.'/img/list_updates.png', 1);
		$urlInfraS				= 'https://infras.fr';
		$urlWiki				= 'https://wiki.infras.fr/books/'.$appliname;
		$urlstore				= 'https://infras.store/';
		$urlDoli				= 'https://www.dolistore.com/index.php?controller=search&orderby=position&orderway=desc&website=marketplace&search_query=InfraS';
		$InputCarac				= 'class = "button infraspluswidth180 infrasplusheight32" name = "readmore" type = "button"';
		$supportvalue			= '/******************************'.'<br/>';
		$supportvalue			.= ' * Module : '.$langs->trans('modcomnamePackPlus').'<br/>';
		$supportvalue			.= ' * Module version : '.$version.'<br/>';
		$supportvalue			.= ' * Dolibarr version : '.DOL_VERSION.'<br/>';
		$supportvalue			.= ' * PHP version : '.PHP_VERSION.'<br/>';
		$supportvalue			.= ' ******************************/'.'<br/>';
		$supportvalue			.= 'Description de votre demande :'.'<br/>';
		$ret					= '	<form id = "ticket" method = "POST" target = "_blank" action = "'.$supportURL.'">
										<input name = message type = "hidden" value = "'.$supportvalue.'" />
										<input name = email type = "hidden" value = "'.$user->email.'" />
										<input name = category_code type = "hidden" value = "'.(strtoupper($langs->trans('modcomnamePackPlus'))).'" />
										<table class = "centpercent" style = "padding: 10; background: url('.$headerPath.'); background-size: cover;">
											<tr class = "infrasplusheight75">
												<td colspan = "3" class = "center bold valignmiddle">
													<a href = "'.$urlWiki.'" target = "_blank">
														<span class = "infraspluscolor" style = "font-size: 24px;">'.$langs->trans('InfraSPlusParamPresent1').'<span class = "infrasplusneuropolinfras"> InfraS</span>'.$langs->trans('InfraSPlusParamPresent2').'</span>
													</a>
												</td>
											</tr>
											<tr class = "infrasplusheight50">
												<td rowspan = "3" class = "left bold valignbottom infraspluswidthtrentepercent infrasplusslogan" style = "color: white; font-size: 16px;">
													<a href = "'.$urlInfraS.'" target = "_blank"><img class = "infrasplusnoborder infraspluswidth220" src = "'.$logoPath.'"></a>
													<br/>&nbsp;&nbsp;'.$langs->trans('InfraSPlusParamSlogan').'
												</td>
												<td class = "center valignmiddle infraspluswidthtrentepercent">
													<a href = "'.$urlstore.'" target = "_blank"><input '.$InputCarac.' value = "'.$langs->trans('InfraSPlusParamLienModules').'" /></a>
													<button class = "button infraspluswidth180 infrasplusheight32" type = "submit" >'.$langs->trans('InfraSWorkflowParamSupport').'</button>
												</td>
												<td rowspan = "3" class = "right bold valignbottom infraspluswidthtrentepercent infrasplusslogan">
													<a href = "'.$urlDoli.'" target = "_blank"><img class = "infrasplusnoborder infraspluswidth270" src = "'.$logoDolistorePath.'"></a>&nbsp;&nbsp;
													<br/>'.$langs->trans('InfraSPlusParamMoreModulesLink').'&nbsp;&nbsp;
												</td>
											</tr>
											<tr>
												<td class = "center valignbottom infrasplusminwidth700imp">
													<img class = "infrasplusnoborder infraspluswidth220 infrasplusmargintop10imp" src="'.$preferedPartnerPath.'"/>
												</td>
											</tr>
											<tr>
												<td class = "center bold valignbottom infrasplusminwidth700imp infrasplusslogan">
													<div class = "infrasplusmargintop10imp">'.$langs->trans('InfraSPackPlusParamPreferedPartner1').'<span class = "infraspluspuentedolibarr"> Dolibarr </span>'.$langs->trans('InfraSPackPlusParamPreferedPartner2').'</div>
												</td>
											</tr>
											<tr class = "infrasplusheight25"><td colspan = "3">&nbsp;</td></tr>
										</table>
									</form>';
		$ret					.= load_fiche_titre('<span class = "infrasplustitleparam">'.$langs->trans('InfraSPlusParamHistoryUpdates').'</span>', '', $listUpD, 1);
		$sxe					= infraspackplus_getChangelogFile($appliname);
		$sxelast				= infraspackplus_getChangelogFile($appliname, 'dwn');
		$tblversionslast		= is_object($sxelast) ? $sxelast->Version : array();
		if ($resVersion == -1) {
			foreach ($tblversions as $error) {
				$ret	.= $error->message;
			}
			return $ret;
		}
		if (getDolGlobalString('INFRAS_SKIP_CHECKVERSION', '')) {
			$dwnbutton	= $dwn ? $langs->trans('InfraSPlusParamSkipCheck') : '';
		} else {
			$dwnbutton	= $dwn ? '<button class = "button" style = "width: 190px; padding: 3px 0px;" type = "submit" value = "dwnChangelog" name = "action" title = "'.$langs->trans('InfraSPlusParamCheckNewVersionTitle').'">'.$langs->trans('InfraSPlusParamCheckNewVersion').'</button>' : '';
		}
		$ret	.= '			<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
									<input type = "hidden" name = "token" value = "'.newToken().'">
									<table class = "infrasplusnoborder centpercent" >
										<tr class = "liste_titre">
											<th class = "center width100">'.$langs->trans('InfraSPlusParamNumberVersion').'</th>
											<th class = "center width100">'.$langs->trans('InfraSPlusParamMonthVersion').'</th>
											<th class = "left" >'.$langs->trans('InfraSPlusParamChangesVersion').'</th>
											<th class = "center width200">'.$dwnbutton.'</th>
										</tr>';
		if (is_object($sxe) && count($tblversionslast) > count($tblversions)) {	// il y a du nouveau
			for ($i = count($tblversionslast)-1; $i >= 0; $i--) {
				$sxePath		= $sxe->xpath('//Version[@Number="'.$tblversionslast[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversionslast[$i]->change;
				$ret			.= '	<tr class = "oddeven">
											<td class = "center valigntop '.(empty($sxePath) ? 'infrasplusbgorange' : '').'">'.$tblversionslast[$i]->attributes()->Number.'</td>
											<td class = "center valigntop '.(empty($sxePath) ? 'infrasplusbgorange' : '').'">'.$tblversionslast[$i]->attributes()->MonthVersion.'</td>
											<td class = "left valigntop infrasplusnopaddingvert '.(empty($sxePath) ? 'infrasplusbgorange' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infraspluscaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrasplusgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrasplusblue';
					} else {
						$classcolor	= ' infrasplusblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infraspluschangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infraspluschangelogbase'.$classcolor.'">'.$changeline.'</td>
													</tr>
												</table>';
				}
				$ret	.= '				</td>
										</tr>';
			}
		} elseif ($sxelast !== false && count($tblversionslast) < count($tblversions) && count($tblversionslast) > 0) {	// On est en avance
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$sxelastPath	= $sxelast->xpath('//Version[@Number="'.$tblversions[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversions[$i]->change;
				$ret			.= '	<tr class = "oddeven">
											<td class = "center valigntop '.(empty($sxelastPath) ? 'infrasplusbggreen infrasplusblack' : '').'">'.$tblversions[$i]->attributes()->Number.'</td>
											<td class = "center valigntop '.(empty($sxelastPath) ? 'infrasplusbggreen infrasplusblack' : '').'">'.$tblversions[$i]->attributes()->MonthVersion.'</td>
											<td class = "left valigntop infrasplusnopaddingvert '.(empty($sxelastPath) ? 'infrasplusbggreen' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infraspluscaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrasplusgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrasplusblue';
					} else {
						$classcolor	= ' infrasplusblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infraspluschangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infraspluschangelogbase'.$classcolor.'">'.$changeline.'</td>
													</tr>
												</table>';
				}
				$ret	.= '				</td>
										</tr>';
			}
		} else {	//on est à jour des versions ou pas de connection internet
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$lineversion	= $tblversions[$i]->change;
				$ret	.= '			<tr class = "oddeven">
											<td class = "center valigntop">'.$tblversions[$i]->attributes()->Number.'</td>
											<td class = "center valigntop">'.$tblversions[$i]->attributes()->MonthVersion.'</td>
											<td class = "left valigntop infrasplusnopaddingvert" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infraspluscaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrasplusgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrasplusblue';
					} else {
						$classcolor	= ' infrasplusblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infraspluschangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infraspluschangelogbase'.$classcolor.'">'.$changeline.'</td>
													</tr>
												</table>';
				}
				$ret	.= '				</td>
										</tr>';
			}
		}
		$ret	.= '				</table>
								</form>';
		return $ret;
	}

	/**
	* Function called to get support information
	* Presentation of results on a HTML table
	*
	* @param	string	$currentversion	current version from changelog
	* @return	string					HTML presentation
	**/
	function infraspackplus_getSupportInformation($currentversion)
	{
		global $db, $langs;

		$formatarray	= pdf_InfraSPlus_getFormat();
		$format			= array($formatarray['width'], $formatarray['height']);
		$pdf			= pdf_InfraSPlus_getInstance($format, 'mm', 'P');
		$ret			= '	<table class = "infrasplusnoborder" >
								<tr class = "liste_titre">
									<th class = "center width400">'.$langs->trans('InfraSPlusSupportInformation').'</th>
									<th class = "center">'.$langs->trans('Value').'</th>
								</tr>
								<tr class = "oddeven">
									<td class = "width400 infraspluschangelogbase">'.$langs->trans('DolibarrVersion').'</td>
									<td class = "infraspluschangelogbase">'.DOL_VERSION.'</td>
								</tr>
								<tr class = "oddeven">
									<td class = "width400 infraspluschangelogbase">'.$langs->trans('ModuleVersion').'</td>
									<td class = "infraspluschangelogbase">'.$currentversion.'</td>
								</tr>
								<tr class = "oddeven">
									<td class = "width400 infraspluschangelogbase">'.$langs->trans('InfraSPlusParamFontsFolder').'</td>
									<td class = "infraspluschangelogbase">'.K_PATH_FONTS.'</td>
								</tr>
								<tr class = "oddeven">
									<td class = "width400 infraspluschangelogbase">'.$langs->trans('PHPVersion').'</td>
									<td class = "infraspluschangelogbase">'.version_php().'</td>
								</tr>
								<tr class = "oddeven">
									<td class = "width400 infraspluschangelogbase">'.$langs->trans('DatabaseVersion').'</td>
									<td class = "infraspluschangelogbase">'.$db::LABEL.' '.$db->getVersion().'</td>
								</tr>
								<tr class = "oddeven">
									<td class = "width400 infraspluschangelogbase">'.$langs->trans('WebServerVersion').'</td>
									<td class = "infraspluschangelogbase">'.dol_escape_htmltag($_SERVER['SERVER_SOFTWARE']).'</td>
								</tr>
								<tr><td colspan = "3" class = "infrasplusFinal">&nbsp;</td></tr>
							</table>
							<br/>';
		return $ret;
	}
