<?php
/* Copyright (C) 2022 Éric Seigne <eric.seigne@cap-rel.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    uptosign/lib/uptosign.lib.php
 * \ingroup uptosign
 * \brief   Library files with common functions for UptoSign
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

dol_include_once('/uptosign/core/modules/modUptoSign.class.php');
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/class/uptosignconfig.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');

/************************************************
*	Sauvegarde les paramètres du module
*
*	@param		string		$appliname	module name
*	@return		string		1 = Ok or -1 = Ko or or 0 and error message
************************************************/
function uptosign_bkup_module($appliname)
{
	global $db, $conf, $langs, $errormsg;

	// Set to UTF-8
	if (is_a($db, 'DoliDBMysqli')) {
		$db->db->set_charset('utf8');
	} elseif ($db->type != 'pgsql') {
		$db->query('SET NAMES utf8');
		$db->query('SET CHARACTER SET utf8');
	}

	// Control dir and file
	$path		= DOL_DATA_ROOT.'/'.(empty($conf->global->MAIN_MODULE_MULTICOMPANY) || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
	$bkpfile	= $path.'/update.'.$conf->entity;
	if (! file_exists($path)) {
		if (dol_mkdir($path) < 0) {
			$errormsg	= $langs->transnoentities('ErrorCanNotCreateDir', $path);
			return 0;
		}
	}
	if (file_exists($path)) {
		$module			= new modUptoSign($db);
		$currentversion	= $module->version;
		$dataversion    = utsbackports_getDolGlobalString('UPTOSIGN_MODULE_VERSION', '');
		$handle			= fopen($bkpfile, 'w+');
		if (fwrite($handle, '') === false) {
			$langs->load('errors');
			$errormsg	= $langs->trans('ErrorFailedToWriteInDir');
			return -1;
		}
		// Print headers and global mysql config vars
		$sqlhead	= '-- '.$db::LABEL.' dump via php with Dolibarr '.DOL_VERSION.'
--
-- Host: '.$db->db->host_info.'    Database: '.$db->database_name.'
-- ------------------------------------------------------
-- Server version        '.$db->db->server_info.'
-- Dolibarr version      '.DOL_VERSION.'
-- UptoSign version      '.$currentversion.'
-- UptoSign data version '.$dataversion.'

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = \'NO_AUTO_VALUE_ON_ZERO\';
';
		fwrite($handle, $sqlhead);

		//const
		$cols_const			= array('name', 'entity', 'value', 'type', 'visible', 'note');
		$sql_const			= 'SELECT '.implode(', ', $cols_const);
		$sql_const			.= ' FROM '.MAIN_DB_PREFIX.'const';
		$sql_const			.= ' WHERE name LIKE \'UPTOSIGN\_%\'';
		$sql_const			.= ' AND entity = '.$conf->entity;
		$sql_const			.= ' ORDER BY name';
		$duplicate			= array('2', 'value', 'name');
		fwrite($handle, uptosign_bkup_table('const', $sql_const, $cols_const, $duplicate, 0, ''));

		//main table
		$cols_uptosign		= array('rowid', 'entity', 'ref', 'label', 'fk_soc', 'description', 'date_creation', 'date_sign', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key', 'status', 'fk_object', 'object_type',
									'sign_status', 'sign_id', 'fk_contact_sign', 'fk_user_sign', 'hash_file', 'hash_file_signed', 'path_file', 'api_name', 'hook_key');
		if (version_compare($dataversion, '2.0.20', '<')) {
			$cols_uptosign		= array('rowid', 'entity', 'ref', 'label', 'fk_soc', 'description', 'date_creation', 'date_sign', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key', 'status', 'fk_object', 'object_type',
										'sign_status', 'sign_id', 'fk_contact_sign', 'fk_user_sign', 'hash_file', 'path_file', 'api_name', 'hook_key');
		}
		$sql_uptosign		= 'SELECT '.implode(', ', $cols_uptosign);
		$sql_uptosign		.= ' FROM '.MAIN_DB_PREFIX.'uptosign';
		$sql_uptosign		.= ' WHERE entity = "'.$conf->entity.'"';
		$sql_uptosign		.= ' ORDER BY date_creation';
		fwrite($handle, uptosign_bkup_table('uptosign', $sql_uptosign, $cols_uptosign, array(), 1, ''));

		//config table - check if data is correct before backup - forget periode where model_pdf was a foreign_key (int)
		if (((int) DOL_VERSION) > 16) {
			$sql_test = "SELECT * FROM ".MAIN_DB_PREFIX."uptosign_uptosignconfig WHERE ".$db->regexpsql('model_pdf', '^[0-9]+$');
			$resql_test = $db->query($sql_test);
			$nbtotalofrecords = $db->num_rows($resql_test);
			dol_syslog("uptosign: module backup ". $sql_test . " : nb = $nbtotalofrecords", LOG_DEBUG);
			if ($nbtotalofrecords == 0) {
				$cols_conf_uptosign	= array('label', 'sign_or_seal', 'entity', 'sign_coordinate', 'page_sign', 'seal_coordinate', 'page_seal', 'model_pdf',
										'date_creation', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key', 'status');
				$sql_conf_uptosign	= 'SELECT '.implode(', ', $cols_conf_uptosign);
				$sql_conf_uptosign	.= ' FROM '.MAIN_DB_PREFIX.'uptosign_uptosignconfig';
				$sql_conf_uptosign	.= ' WHERE entity = "'.$conf->entity.'" AND (import_key != "initial-setup" OR import_key IS NULL)';
				$sql_conf_uptosign	.= ' ORDER BY date_creation';
				dol_syslog("uptosign: module backup ". $sql_conf_uptosign, LOG_DEBUG);
				fwrite($handle, uptosign_bkup_table('uptosign_uptosignconfig', $sql_conf_uptosign, $cols_conf_uptosign, array(), 0, ''));
			} else {
				dol_syslog("uptosign: uptosign module backup cas ou $nbtotalofrecords != 0 ...", LOG_DEBUG);
			}
		}
		// Enabling back the keys/index checking
		$sqlfooter		= '
SET FOREIGN_KEY_CHECKS = 1;

-- Dump completed on '.date('Y-m-d G-i-s').'
';
		fwrite($handle, $sqlfooter);
		fclose($handle);
		// if (file_exists($bkpfile)) {
		// 	$moved	= dol_copy($bkpfile, DOL_DATA_ROOT.($conf->entity != 1 ? '/'.$conf->entity : '').'/admin/'.$appliname.'_update'.date('Y-m-d-G-i-s').'.'.$conf->entity);
		// }
		return 1;
	}
}

/************************************************
*	Recherche d'un fichier contenant un code langue dans son nom à partir d'une liste
*
*	@param	string	$table		table name to backup
*	@param	string	$sql		sql query to prepare data  for backup
*	@param	array	$listeCols	list of columns to backup on the table
*	@param	array	$duplicate	values for 'ON DUPLICATE KEY UPDATE'
*                               [0] = column to update
*                               [1] = column name to update
*                               [2] = key value for conflict control (only postgreSQL)
*	@param	boolean	$truncate	truncate the table before restore
*	@param	string	$add		sql data to add on the beginning of the query
*	@return	string				sql query to restore the datas
************************************************/
function uptosign_bkup_table($table, $sql, $listeCols, $duplicate = array(), $truncate = 0, $add = '')
{
	global $db, $conf, $langs, $errormsg;

	$sqlnewtable	= '';
	$result_sql		= $sql ? $db->query($sql) : '';
	dol_syslog('uptosign: uptosign.lib::uptosign_bkup_table sql = '.$sql);
	if ($result_sql) {
		$truncate		= $truncate ? 'TRUNCATE TABLE '.MAIN_DB_PREFIX.$table.';
' : '';
		$sqlnewtable	= '
-- Dumping data for table '.MAIN_DB_PREFIX.$table.'
'.$truncate.$add;
		while ($row	= $db->fetch_row($result_sql)) {
			// For each row of data we print a line of INSERT
			$colsInsert   = '';
			$entityNumCol = -1;
			$j = 0;
			foreach ($listeCols as $col) {
				$colsInsert	.= $col.', ';
				if ($col == 'entity') {
					$entityNumCol = $j;
				}
				$j++;
			}
			$sqlnewtable					.= 'INSERT INTO '.MAIN_DB_PREFIX.$table.' ('.substr($colsInsert, 0, -2).') VALUES (';
			$columns						= count($row);
			$duplicateValue					= '';
			for ($j = 0; $j < $columns; $j++) {
				// Processing each columns of the row to ensure that we correctly save the value (eg: add quotes for string - in fact we add quotes for everything, it's easier)
				if ($row[$j] == null && !is_string($row[$j])) {
					$row[$j]	= 'NULL';
				}	// IMPORTANT: if the field is NULL we set it NULL
				elseif (is_string($row[$j]) && $row[$j] == '') {
					$row[$j]	= '\'\'';
				}	// if it's an empty string, we set it as an empty string
				else {																	// else for all other cases we escape the value and put quotes around
					$row[$j]	= addslashes($row[$j]);
					$row[$j]	= preg_replace('#\n#', '\\n', $row[$j]);
					$row[$j]	= '\''.$row[$j].'\'';
				}
				if ($j == $entityNumCol) {
					$row[$j]	= '\'__ENTITY__\'';
				}
				if (!empty($duplicate)) {
					$onDuplicate	= $db->type == 'pgsql' ? ' ON CONFLICT ('.$duplicate[2].') DO UPDATE SET ' : ' ON DUPLICATE KEY UPDATE ';
					$duplicateValue .= $j == $duplicate[0] ? $onDuplicate.$duplicate[1].' = '.$row[$j] : '';
				}
			}
			$sqlnewtable	.= implode(', ', $row).')'.$duplicateValue.';
';
		}
	}
	return $sqlnewtable;
}

/************************************************
*	Restaure les paramètres du module
*
*	@param		string		$appliname	module name
*	@return		string		1 = Ok or -1 = Ko
************************************************/
function uptosign_restore_module($appliname, $tablename = "")
{
	global $conf;

	$pathsql	= DOL_DATA_ROOT.'/'.(empty($conf->global->MAIN_MODULE_MULTICOMPANY) || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
	$handle		= @opendir($pathsql);
	if (is_resource($handle)) {
		$filesql = $pathsql.'/'.'update.'.$conf->entity;
		$moved = $result = "";

		//if a table name is specified, make a sort of grep
		if ($tablename != "") {
			dol_syslog("uptosign: uptosign_restore_module table name $tablename", LOG_DEBUG);
			$sql = "";

			$content = file_get_contents($filesql);
			if (preg_match_all("|.* INTO $tablename .*|", $content, $out)) {
				foreach ($out[0] as &$line) {
					$sql .= "\n".$line;
				}
				file_put_contents($filesql.'.sql', $sql);
			}
			dol_syslog("uptosign: uptosign_restore_module save $sql to $filesql .sql", LOG_DEBUG);
		} else {
			$moved = dol_copy($filesql, $filesql.'.sql');
		}
		if (is_file($filesql.'.sql')) {
			$result	= run_sql($filesql.'.sql', (empty($conf->global->MAIN_DISPLAY_SQL_INSTALL_LOG) ? 1 : 0), $conf->entity, 1);
		}
		$delete	= dol_delete_file($filesql.'.sql');
		dol_syslog('uptosign: uptosign.Lib::uptosign_restore_module appliname = '.$appliname.' filesql = '.$filesql.' moved = '.$moved.' result = '.$result.' delete = '.$delete);

		//post restore : disable all impossible templates
		uptosignDisableAllImpossibleTemplates();

		if ($result > 0) {
			return 1;
		}
	}
	return -1;
}


/************************************************
// *	Recupere dans le fichier de backup quelle était la version du module
*   sauvegardé ... pour pouvoir gérer les gros changements de versions
*
*	@param		string		$appliname	module name
*	@return		string		version, ex 1.2.4, -1 if no backup
// ************************************************/
function uptosign_bkup_get_version($appliname)
{
	global $conf;
	dol_syslog("uptosign: " . __METHOD__ . " get version for " . $appliname, LOG_DEBUG);

	$version	= "0.0.0"; //default version
	$pathsql	= DOL_DATA_ROOT.'/'.(empty($conf->global->MAIN_MODULE_MULTICOMPANY) || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
	$filesql	= $pathsql.'/'.'update.'.$conf->entity;
	$pathCCsql	= DOL_DATA_ROOT.'/'.(empty($conf->global->MAIN_MODULE_MULTICOMPANY) || $conf->entity == 1 ? '' : $conf->entity.'/').'UptoSign/sql'; //some bad version 1.x
	$fileCCsql	= $pathCCsql.'/'.'update.'.$conf->entity;
	if (file_exists($fileCCsql)) {
		dol_syslog("uptosign: " . __METHOD__ . " migrate from CamelCase to " . $appliname, LOG_DEBUG);
		if (!is_dir($pathsql)) {
			dol_syslog("uptosign: " . __METHOD__ . " create target dir " . $pathsql, LOG_DEBUG);
			@mkdir($pathsql, 0700, true);
		}
		if (is_dir($pathsql)) {
			dol_syslog("uptosign: " . __METHOD__ . " rename old sql backup $fileCCsql to " . $filesql, LOG_DEBUG);
			@copy($fileCCsql, $filesql);
			//TODO @rename
		}
		$filesql = $fileCCsql;
		$pathsql = $pathCCsql;
	}
	// print "<p>Backup du module :" . $filesql . "</p>";
	if (is_file($filesql)) {
		$fp = fopen($filesql, 'r');
		for ($i = 0; ($i < 20) && ($version == -1); $i++) {
			if (feof($fp)) {
				// echo 'EOF reached';
				break;
			}
			$line = fgets($fp);

			//problème si l'utilisateur désactive le module après avoir installé la nouvelle version
			//le numéro de version mis en commentaire est lié à la version du code du module et non
			//à la version du module qui était actif jusqu'à présent :-(
			// if (preg_match('/UptoSign version\s+([\d\.]+)/i', $line, $reg)) {
			//     $version = $reg[1];
			// }
			//donc on cherche la valeur de llx_const UPTOSIGN_MODULE_VERSION -> si elle n'existe pas alors
			//version < 2.0.8
			if (preg_match("/'UPTOSIGN_MODULE_VERSION',\s+'__ENTITY__','([\d\.]+)',\s+'chaine'.*\)\s+/", $line, $reg)) {
				$version = $reg[1];
			}
		}
		fclose($fp);
	}
	dol_syslog("uptosign: " . __METHOD__ . " version is " . $version, LOG_DEBUG);
	return $version;
}


/**
 * migrate configuration from uptosign module v1 to v2
 */
function uptosign_migrate_conf_v1_to_v2()
{
	global $db, $conf;
	$migrate = [
		'UPTOSIGN_LOGIN_UPTOSIGN_PROD' => 'UPTOSIGN_LOGIN',
		'UPTOSIGN_API_KEY_UPTOSIGN_PROD' => 'UPTOSIGN_KEY_API',
		'UPTOSIGN_PASSWORD_UPTOSIGN_PROD' => 'UPTOSIGN_PASS_API'
	];

	foreach ($migrate as $old => $new) {
		// dol_syslog(__METHOD__ . ":: try to migrate from " . $old . " to " . $new, LOG_DEBUG);
		$oldvalue = dolibarr_get_const($db, $old, $conf->entity);
		if ($oldvalue != "") {
			$value = $oldvalue;
			//Migrate old clear password to new crypted
			if ($old == "UPTOSIGN_PASSWORD_UPTOSIGN_PROD") {
				$value = dol_encode($oldvalue);
			}
			$result = dolibarr_set_const($db, $new, $value, 'chaine', 0, '', $conf->entity);
			dolibarr_del_const($db, $old, $conf->entity);
			dol_syslog("uptosign: " . __METHOD__ . " migrate config data done from " . $old . " to " . $new, LOG_DEBUG);
		}
	}

	//Cleanup all old unused data
	$cleanup = ['UPTOSIGN_LOGIN_UPTOSIGN_DEMO','UPTOSIGN_PASSWORD_UPTOSIGN_DEMO',
				'UPTOSIGN_API_KEY_UPTOSIGN_DEMO','UPTOSIGN_SEND_MAIL_ALL_UPTOSIGN',
				'UPTOSIGN_SEND_MAIL_UPTOSIGN','UPTOSIGN_CERTIFICATE_TYPE_UPTOSIGN',
				'UPTOSIGN_LANGUAGE_UPTOSIGN','UPTOSIGN_HANDWRITEN_SIGN_UPTOSIGN',
			];
	foreach ($cleanup as $key) {
		if (utsbackports_getDolGlobalString($key, '')  != '') {
			dolibarr_del_const($db, $key, $conf->entity);
		}
	}
}

 /**
  * clean up path, remove old deprecated files (not needed)
  *
  * @param   string  $dir       [$dir description]
  * @param   string  $basedir   [$basedir description]
  * @param   array  $md5files  [$md5files description]
  *
  * @return  string             [return description]
  */
function uptosign_cleanupModulePath($dir, $basedir, $md5files)
{
	if (!is_dir($dir)) {
		return false;
	}

	$filemd5s = "";
	$d = dir($dir);

	while (false !== ($entry = $d->read())) {
		if ($entry != '.' && $entry != '..') {
			if (is_dir($dir.'/'.$entry)) {
				uptosign_cleanupModulePath($dir.'/'.$entry, $basedir, $md5files);
			} else {
				$base = ltrim(str_replace($basedir, '', $dir . "/"), '/');
				//Le fichier existe, on vérifie si le md5 est bon ?
				if (key_exists($base.$entry, $md5files)) {
					//$filemd5s .= $base.$entry . ";" . md5_file($dir.'/'.$entry) . ";\n";
				} else {
					//vieux fichier -> supprime ?
					unlink($dir.'/'.$entry);
					dol_syslog("uptosign: module init, unlink $dir/$entry ...", LOG_DEBUG);
				}
			}
		}
	}
	$d->close();
	return $filemd5s;
}


/**
 * migrate from full path_file to relative path_file_signed
 *
 */
function uptosign_migrate_data_before_2_0_38()
{
	global $db, $user;
	$uptosign = new UptoSign($db);
	$sql = "SELECT rowid,path_file FROM ".MAIN_DB_PREFIX."uptosign WHERE path_file IS NOT NULL";
	dol_syslog("uptosign: uptosign_migrate_data_before_2_0_38: " . json_encode($sql), LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$rowid = $obj->rowid;
				$newpath = uptosign_relative_path($obj->path_file);
				$result = $uptosign->fetch($rowid);
				if ($result) {
					$uptosign->path_file_signed = $newpath;
					$uptosign->path_file = null; //old file name ? impossible to invent
					$uptosign->update($user);
					dol_syslog("uptosign: uptosign_migrate_data_before_2_0_38 #$rowid from " . $obj->path_file . " -> " . $newpath . " [ok]");
				} else {
					dol_syslog("uptosign: uptosign_migrate_data_before_2_0_38 error for rowid=$rowid");
				}
			}
			$i++;
		}
	}
}
