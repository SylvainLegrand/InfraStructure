<?php
	/************************************************
	* Copyright (C) 2018-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrassearch/core/lib/infrassearchAdmin.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraSSearch module
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

	/**
	* Define head array for setup pages tabs
	*
	* @return	array			list of head
	**/
	function infrassearch_admin_prepare_head()
	{
		global $langs, $conf, $user;

		$h		= 0;
		$head	= array();
		if (!empty($user->admin) || !empty($user->hasRight('infrassearch', 'paramInfraSSearch'))) {
			$head[$h][0]	= dol_buildpath('/infrassearch/admin/infrassearchsetup.php',1);
			$head[$h][1]	= $langs->trans("InfraSSearchParams");
			$head[$h][2]	= 'infrassearchsetup';
		}
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infrassearch_admin');
		$h++;
		$head[$h][0]	= dol_buildpath('/infrassearch/admin/about.php', 1);
		$head[$h][1]	= $langs->trans('About');
		$head[$h][2]	= 'about';
		$h++;
		$head[$h][0]	= dol_buildpath('/infrassearch/admin/changelog.php', 1);
		$head[$h][1]	= $langs->trans('InfraSSearchParamsChangelog');
		$head[$h][2]	= 'changelog';
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infrassearch_admin', 'remove');
		return $head;
	}

	/**
	*	Test if the menu InfraS on tools top menu in loaded
	*
	**/
	function infrassearch_no_topmenu()
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
	function infrassearch_test_php_ext()
	{
		global $db, $conf, $langs;

		$langs->load('infrassearch@infrassearch');

		$expected	= extension_loaded('xml') ? '1' : '-1';
		// Write only on change: a DELETE + INSERT on llx_const at each call collided with concurrent transactions (deadlocks)
		if (getDolGlobalString('INFRAS_PHP_EXT_XML') !== $expected) {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	$expected, 'chaine', 0, 'InfraSSearch module', $conf->entity);
		}
		if ($expected == '-1') {
			setEventMessages('<span class = "infrassearchCaution">'.$langs->trans('InfraSSearchCautionMess').'</span>'.$langs->trans('InfraSXMLextError'), array(), 'warnings');
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
	function infrassearch_getLocalVersionMinDoli($appliname)
	{
		global $langs;

		$currentversion	= array();
		$sxe			= infrassearch_getChangelogFile($appliname);
		if (is_object($sxe)) {
			$currentversion[0]	= (string) $sxe->Version[count($sxe->Version) - 1]->attributes()->Number;
			$currentversion[1]	= (string) $sxe->Dolibarr->attributes()->minVersion;
			$currentversion[2]	= 0;
			$currentversion[3]	= $sxe->Version;
			$currentversion[4]	= (string) $sxe->Dolibarr->attributes()->maxVersion;
			$currentversion[5]	= (string) $sxe->PHP->attributes()->minVersion;
			$currentversion[6]	= (string) $sxe->PHP->attributes()->maxVersion;
		} else {
			$currentversion[0]	= '<span class = "infrassearchCaution"><b>'.$langs->trans('InfraSSearchChangelogXMLError').'</b></span>';
			$currentversion[1]	= $langs->trans('InfraSSearchnoMinDolVersion');
			$currentversion[2]	= -1;
			$currentversion[3]	= $langs->trans('InfraSSearchChangelogXMLError');
			$currentversion[4]	= $langs->trans('InfraSSearchnoMaxDolVersion');
			$currentversion[5]	= $langs->trans('InfraSSearchnoMinDolVersion');
			$currentversion[6]	= $langs->trans('InfraSSearchnoMaxDolVersion');
			foreach (libxml_get_errors() as $error) {
				$currentversion[3]	.= $error->message;
				dol_syslog('infrassearch.Lib::infrassearch_getLocalVersionMinDoli error->message = '.$error->message);
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
	function infrassearch_getChangelogFile($appliname, $from = '')
	{
		$file	= empty($from) ? dol_buildpath($appliname, 0).'/docs/changelog.xml' : DOL_DATA_ROOT.'/'.$appliname.'/changelogdwn.xml';
		if (is_file($file)) {
			libxml_use_internal_errors(true);
			$context	= stream_context_create(array('http' => array('method' => 'GET', 'header' => 'Accept: application/xml')));
			$changelog	= @file_get_contents($file, false, $context);
			$sxe		= @simplexml_load_string(rtrim($changelog));
			dol_syslog('infrassearch.Lib::infrassearch_getChangelogFile appliname = '.$appliname.' from = '.$from.' context = '.$context.' changelog = '.($changelog ? 'Ok' : 'KO').' sxe = '.($sxe ? 'Ok' : 'KO'));
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
	function infrassearch_dwnChangelog($appliname)
	{
		$path	= DOL_DATA_ROOT.'/'.$appliname;
		if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1) {
			return -1;
		}
		$newVersion	= getURLContent('https://raw.githubusercontent.com/InfraS-SARL/modules-versions/main/'.$appliname.'/changelog.xml', 'GET', '', 1, array(), array('http', 'https'), 0);
		if (!isset($newVersion['content'])) {	// not connected
			return -1;
		} else {
			$newhtmlversion		= preg_replace('#Downloaded=\".+\"#', 'Downloaded="'.date('Ymd').'"', $newVersion['content']);
			file_put_contents($path.'/changelogdwn.xml', $newhtmlversion);
		}
		return 1;
	}

	/**
	*	Sauvegarde les paramètres du module
	*
	*	@param		string		$appliname	module name
	*	@return		string		1 = Ok or -1 = Ko or or 0 and error message
	**/
	function infrassearch_bkup_module ($appliname)
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
			$currentversion	= infrassearch_getLocalVersionMinDoli('infrassearch');
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
-- InfraSSearch version		'.$currentversion[0].'

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = \'NO_AUTO_VALUE_ON_ZERO\';
';
			fwrite($handle, $sqlhead);
			$cols_const			= array ('name', 'entity', 'value', 'type', 'visible', 'note');
			$duplicate_const	= array ('2', 'value', 'name');
			$sql_const			= 'SELECT '.implode(', ', $cols_const);
			$sql_const			.= ' FROM '.$db->prefix().'const';
			$sql_const			.= ' WHERE name LIKE "INFRASSEARCH_%"';
			$sql_const			.= ' AND entity = "'.$conf->entity.'"';
			$sql_const			.= ' ORDER BY name';
			fwrite($handle, infrassearch_bkup_table ('const', $sql_const, $cols_const, $duplicate_const, 0, ''));
			// Enabling back the keys/index checking
			$sqlfooter		= '
SET FOREIGN_KEY_CHECKS = 1;

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
	function infrassearch_bkup_table ($table, $sql, $listeCols, $duplicate = array (), $truncate = 0, $add = '')
	{
		global $db;

		$sqlnewtable	= '';
		$result_sql		= $sql ? $db->query($sql) : '';
		dol_syslog('infrassearch.Lib::infrassearch_bkup_table sql = '.$sql);
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
	function infrassearch_restore_module ($appliname)
	{
		global $conf;

		$pathsql	= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
		dol_syslog('infrassearchAdmin.Lib::infrassearch_restore_module $pathsql = '.$pathsql);
		$handle		= @opendir($pathsql);
		if (is_resource($handle)) {
			$filesql	= $pathsql.'/'.'update.'.$conf->entity;
			$moved		= dol_copy($filesql, $filesql.'.sql');
			if (is_file($filesql.'.sql')) {
				$result	= run_sql($filesql.'.sql', (!getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG', '') ? 1 : 0), $conf->entity, 1);
			}
			$delete	= dol_delete_file($filesql.'.sql');
			dol_syslog('infrassearch.Lib::infrassearch_restore_module appliname = '.$appliname.' filesql = '.$filesql.' moved = '.$moved.' result = '.$result.' delete = '.$delete);
			if ($result > 0) {
				return 1;
			}
		}
		return -1;
	}

	/**
	*	Returns the HTML options code for column/position numbering and checks for an error like the same number twice
	*
	*	@param		array	$selectvalue	selected array with 'select' and 'value' keys
	*										Example: array ('select' => 'constant name for this column or position', 'value' => getDolGlobalInt('constant name for this column or position', 'default value'))
	*	@param		array	$listselect		array of all arrays with 'select' and 'value' keys
	*										Example: array (array ('select' => 'constant name for this column or position', 'value' => getDolGlobalInt('constant name for this column or position', 'default value')),
	*														array ('select' => 'col2', 'value' => getDolGlobalInt('col2', 2)),
	*														array ('select' => 'col3', 'value' => getDolGlobalInt('col3', 3)),
	*														etc...
	*														)
	*	@return		array|int				array('options' => HTML options for select with numbering, 'err' => error count) | -1 if an error occurs
	**/
	function infrassearch_num_pos(&$selectvalue, $listselect)
	{
		if (!is_array($selectvalue) || !isset($selectvalue['select']) || !isset($selectvalue['value']) || !is_array($listselect) || empty($listselect)) {
			return -1;
		}
		$nbCol	= count($listselect) + 1;
		$listOptionsTag	= array('options' => '', 'err' => 0);
		foreach ($listselect as $selectvalues) {
			if ($selectvalues['select'] != $selectvalue['select'] && $selectvalues['value'] == $selectvalue['value']) {
				$listOptionsTag['err']++;
			}
		}
		for ($i = 1 ; $i < $nbCol ; $i++) {
			$afficher			= $i < 10 ? '0'.$i : $i;
			$listOptionsTag['options']	.= '<option name = "'.$selectvalue['select'].'" value = "'.$i.'"';
			if ($selectvalue['value'] == $i) {
				$listOptionsTag['options']	.= ' selected';
				if ($listOptionsTag['err']) {
					$listOptionsTag['options']	.= ' class = "infrassearchbgred"';
				}
			}
			$listOptionsTag['options']	.= '>'.$afficher.'</option>';
		}
		return $listOptionsTag;
	}

	/**
	*	Print HTML backup / restore section
	*
	*	@return		void
	**/
	function infrassearch_print_backup_restore()
	{
		global $conf, $langs;

		print '	<table class = "centpercent noborderspacing">';
		$metas	= array('*', '90px', '156px', '120px');
		infrassearch_print_colgroup($metas);
		print '		<tr>
						<td colspan = "2" class = "center infrassearchtitleparam">
							<a href = "'.DOL_URL_ROOT.'/document.php?modulepart=infrassearch&file=sql/update.'.$conf->entity.'">'.$langs->trans('InfraSSearchParamAction1').' <b><span>'.$langs->trans('modcomnameSearch').'</span></b> '.$langs->trans('InfraSSearchParamAction2').'</a>
						</td>
						<td class = "center"><button class = "butAction" type = "submit" value = "bkupParams" name = "action">'.$langs->trans('InfraSSearchParamBkup').'</button></td>
						<td class = "center"><button class = "butActionDelete" type = "submit" value = "restoreParams" name = "action">'.$langs->trans('InfraSSearchParamRestore').'</button></td>
					</tr>';
		infrassearch_print_hr(count($metas));
		infrassearch_print_final(count($metas));
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
	function infrassearch_load_title($titre, $morehtmlright = '', $picto = 'generic', $pictoisfullpath = 0, $id = '', $morecssontable = '', $morehtmlcenter = '')
	{
		$out	= '';
		if ($picto == 'setup')	{
			$picto	= 'generic';
		}
		$out	.= '<table '.(!empty($id) ? 'id = "'.$id.'" ' : '').'class = "centpercent notopnoleftnoright table-fiche-title'.(!empty($morecssontable) ? ' '.$morecssontable : '').'">
											<tr class = "liste_titre">';
		if (!empty($picto)) {
			$out .= '							<td class = "infrassearchnoborder infrassearchnopadding widthpictotitle valignmiddle col-picto">'.img_picto('', $picto, 'class = "valignmiddle infrassearchwidthpictotitle pictotitle"', $pictoisfullpath).'</td>';
		}
		$out	.= '							<td class = "infrassearchnoborder infrassearchnopadding valignmiddle col-title"><div class = "infrassearchDivTitre uppercase inline-block">'.$titre.'</div></td>';
		if (dol_strlen($morehtmlcenter)) {
			$out .= '							<td class = "infrassearchnoborder infrassearchnopadding center valignmiddle">'.$morehtmlcenter.'</td>';
		}
		if (dol_strlen($morehtmlright)) {
			$out .= '							<td class = "infrassearchnoborder infrassearchnopadding titre_right wordbreakimp right valignmiddle">'.$morehtmlright.'</td>';
		}
		$out .= '							</tr>
										</table>';
		return $out;
	}

	/**
	*	Print HTML colgroup for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	**/
	function infrassearch_print_colgroup($metas = array())
	{
		print '	<tr>';
		foreach ($metas as $values)	{
			print '<td class = "infrassearchFinal infrassearchnopadding"'.($values == '*' ? '' : ' width = "'.$values.'"').' style =" height: 1px;'.($values == '*' ? '' : ' max-width: '.$values.'; min-width: '.$values.'; width: '.$values.';').'">&nbsp;</td>';
		}
		print '	</tr>';
	}

	/**
	*	Print HTML title for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	**/
	function infrassearch_print_liste_titre($metas = array())
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
	function infrassearch_print_btn_action($action, $desc = '', $cs1 = 3, $alignclass = 'center', $lbl = 'Modify', $noRowspan = false)
	{
		global $langs;

		print '	<tr>
					<td colspan = "'.$cs1.'" class = "'.$alignclass.'">'.$desc.'</td>
					<td'.(empty($noRowspan) ? ' rowspan = "0"' : '').' class = "center valigntop"><button class = "button infrassearchwidth110" type = "submit" value = "update_'.$action.'" name = "action">'.$langs->trans($lbl).'</button></td>
				</tr>';
	}

	/**
	*	Print HTML HR line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infrassearch_print_hr($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'"><hr class = "infrassearchHR"></td></tr>';
	}

	/**
	*	Print HTML subtitle line
	*
	*	@param		int			$cs1		first colspan
	*	@param		string		$subtitle	subtitle or translation key for subtitle
	*	@return		void
	**/
	function infrassearch_print_subTitle($cs1 = 3, $subtitle = '')
	{
		global $langs;

		infrassearch_print_hr($cs1);
		print '	<tr>
					<td colspan = "'.$cs1.'" class = "center"><span class = "infrassearchsubtitleparam">'.$langs->trans($subtitle).'</span></td>
				</tr>';
	}

	/**
	*	Print HTML final line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infrassearch_print_final($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'" class = "infrassearchFinal">&nbsp;</td></tr>';
	}

	/**
	*	Print HTML action button for admin page
	*
	*	@param		string			$confkey	action name (with prefix => 'update_')
	*	@param		string			$tag		input type (on/off button, input, textarea, color select, select (from Dolibarr functions), selectpos, select_produits, select_types_paiements, selectTypeContact, select_type_actions)
	*	@param		string			$desc		Description of action
	*	@param		string			$help		Help description => active tooltip
	*	@param		array|string	$metas		list of HTML parameters and values (example : 'type'=>'text' and/or 'class'=>'flat center', etc...)
	*	@param		int				$cs1		first colspan
	*	@param		int				$cs2		second colspan => we add it with $cs1 in case off textarea
	*	@param		string			$end		if input element string to be added after or empty td to finish the line
	*	@param		int				$num		Add a numbering column first with this number
	*	@return		int						line number for next option
	**/
	function infrassearch_print_input($confkey, $tag = 'on_off', $desc = '', $help = '', $metas = '', $cs1 = 2, $cs2 = 1, $end = '', $num = 0)
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
			print '	<td colspan = "'.$cs1.'"';
			if ($tag == 'selectpos') {
				$numcol	= infrassearch_num_pos($metas['arrayTObjectType'], $metas['validListTObjectType']);
				if ($numcol['err']) {
					print ' class = "infrassearchCaution"';
				}
			}
			print '>';
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
			$params	= '';
			if (!empty($metas) && is_array($metas)) {
				foreach ($metas as $key => $value) {
					$params	.= '&'.$key.'='.$value;
				}
			}
			print '		<a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=set_'.$confkey.$params.'&token='.newToken().'&value='.(getDolGlobalString($confkey, '') ? '0' : '1').'">';
			print ajax_constantonoff($confkey);
			print '		</a>';
		} elseif ($tag == 'on_off2') {
			print '		<a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=set_'.$confkey.'&token='.newToken().'&value='.(strpos(getDolGlobalString($confkey, ''), $metas) !== false ? '0' : '1').'">
							'.(strpos(getDolGlobalString($confkey, ''), $metas) !== false ? img_picto($langs->trans('Activated'), 'switch_on') : img_picto($langs->trans('Disabled'), 'switch_off')).'
						</a>';
		} elseif ($tag == 'input') {
			// management of the minimum value of number type input fields
			$inputValue	= getDolGlobalString($confkey, '');
			if (!empty($metas['type']) && $metas['type'] == 'number' && !empty($metas['min'])) {
				$currentValue	= getDolGlobalInt($confkey, $metas['min']);
				$inputValue		= $currentValue < $metas['min'] ? $metas['min'] : $currentValue;
			}
			// default input
			$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrassearchnopadding', 'style' => 'font-size: inherit;', 'name' => $confkey, 'id' => $confkey, 'value' => $inputValue);
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
				$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrassearchnopadding infrassearchfontsizeinherit', 'name' => $keymeta, 'id' => $keymeta, 'value' => getDolGlobalString($keymeta, ''));
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
				$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrassearchnopadding infrassearchfontsizeinherit', 'id' => $keymeta);
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
		} elseif ($tag == 'selectpos') {
			$numcol	= infrassearch_num_pos($metas['arrayTObjectType'], $metas['validListTObjectType']);
			print '	<select name = "'.$metas['arrayTObjectType']['select'].'" class = "flat infrassearchfontsizeinherit infrassearchnopadding infrassearchnoborder cursorpointer">
						'.$numcol['options'].'
					</select>';
			if ($numcol['err']) {
				print '&nbsp;&nbsp;<i class = "fa fa-exclamation-triangle infrassearchCaution"></i>';
			}
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
	function infrassearch_print_line_inputs($type = '', $desc = '', $metas = array(), $cs1 = 2, $w = 0, $end = '', $num = 0)
	{
		print '	<tr class = "oddeven">';
		if (!empty($num)) {
			print '	<td class = "center bold">'.$num.'</td>';
			$num++;
		}
		print '		<td colspan = "'.$cs1.'">
						<table class = "centpercent">
							<tr>
								<td rowspan = "2" class = "infrassearchnoborder">'.$desc.'</td>';
		foreach ($metas[0] as $confkey => $value) {
			$confkey	= str_replace('_AUTO', '', $confkey);
			print '				<td class = "center infrassearchnoborder" style = "max-width: '.$w.'px; min-width: '.$w.'px; width: '.$w.'px;">'.($type == 'tests' ? (getDolGlobalString($confkey, '') ? $value : '&nbsp;') : $value).'</td>';
		}
		print '				</tr>
							<tr>';
		foreach ($metas[1] as $confkey => $value) {
			print '				<td class = "center infrassearchnoborder">';
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
	function infrassearch_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn = 0)
	{
		global $langs, $user;

		$langs->loadLangs(array('admin', 'errors', 'infrassearch@infrassearch'));

		$supportURL				= 'https://support.infras.fr/create_ticket.php';
		$headerPath				= dol_buildpath('/'.$appliname.'/img/InfraSheader.png', 1);
		$logoPath				= dol_buildpath('/'.$appliname.'/img/InfraS.png', 1);
		$logoDolistorePath		= dol_buildpath('/'.$appliname.'/img/dolistore_logo.png', 1);
		$preferedPartnerPath	= dol_buildpath('/'.$appliname.'/img/Dolibarr_preferred_partner.png', 1);
		$listUpD				= dol_buildpath('/'.$appliname.'/img/list_updates.png', 1);
		$urlInfraS				= 'https://infras.fr';
		$urlWiki				= 'https://wiki.infras.fr/books/'.$appliname.'/page/presentation-generale';
		$urlstore				= 'https://infras.store/';
		$urlDoli				= 'https://www.dolistore.com/index.php?controller=search&orderby=position&orderway=desc&website=marketplace&search_query=InfraS';
		$InputCarac				= 'class = "butAction infrassearchwidth180 infrassearchheight32" name = "readmore" type = "button"';
		$supportvalue			= '/******************************'.'<br/>';
		$supportvalue			.= ' * Module : '.$langs->trans('modcomnameSearch').'<br/>';
		$supportvalue			.= ' * Module version : '.$version.'<br/>';
		$supportvalue			.= ' * Dolibarr version : '.DOL_VERSION.'<br/>';
		$supportvalue			.= ' * PHP version : '.PHP_VERSION.'<br/>';
		$supportvalue			.= ' ******************************/'.'<br/>';
		$supportvalue			.= 'Description de votre demande :'.'<br/>';
		$ret					= '	<form id = "ticket" method = "POST" target = "_blank" action = "'.$supportURL.'">
										<input name = message type = "hidden" value = "'.$supportvalue.'" />
										<input name = email type = "hidden" value = "'.$user->email.'" />
										<input name = category_code type = "hidden" value = "'.(strtoupper($langs->trans('modcomnameSearch'))).'" />
										<table class = "centpercent" style = "padding: 10; background: url('.$headerPath.'); background-size: cover;">
											<tr class = "infrassearchheight75">
												<td colspan = "3" class = "center bold valignmiddle">
													<a href = "'.$urlWiki.'" target = "_blank">
														<span class = "infrassearchColor" style = "font-size: 24px;">'.$langs->trans('InfraSSearchParamPresent1').'<span class = "infrassearchneuropolinfras"> InfraS</span>'.$langs->trans('InfraSSearchParamPresent2').'</span>
													</a>
												</td>
											</tr>
											<tr class = "infrassearchheight50">
												<td rowspan = "3" class = "left bold valignbottom infrassearchwidthtrentepercent infrassearchslogan" style = "color: white; font-size: 16px;">
													<a href = "'.$urlInfraS.'" target = "_blank"><img class = "infrassearchnoborder infrassearchwidth220" src = "'.$logoPath.'"></a>
													<br/>&nbsp;&nbsp;'.$langs->trans('InfraSSearchParamSlogan').'
												</td>
												<td class = "center valignmiddle infrassearchwidthtrentepercent">
													<a href = "'.$urlstore.'" target = "_blank"><input '.$InputCarac.' value = "'.$langs->trans('InfraSSearchParamLienModules').'" /></a>
													<button class = "butAction infrassearchwidth180 infrassearchheight32" type = "submit" >'.$langs->trans('InfraSSearchParamSupport').'</button>
												</td>
												<td rowspan = "3" class = "right bold valignbottom infrassearchwidthtrentepercent infrassearchslogan">
													<a href = "'.$urlDoli.'" target = "_blank"><img class = "infrassearchnoborder infrassearchwidth270" src = "'.$logoDolistorePath.'"></a>&nbsp;&nbsp;
													<br/>'.$langs->trans('InfraSSearchParamMoreModulesLink').'&nbsp;&nbsp;
												</td>
											</tr>
											<tr>
												<td class = "center valignbottom infrassearchminwidth700imp">
													<img class = "infrassearchnoborder infrassearchwidth220 infrassearchmargintop10imp" src="'.$preferedPartnerPath.'"/>
												</td>
											</tr>
											<tr>
												<td class = "center bold valignbottom infrassearchminwidth700imp infrassearchslogan">
													<div class = "infrassearchmargintop10imp">'.$langs->trans('InfraSSearchParamPreferedPartner1').'<span class = "infrassearchpuentedolibarr"> Dolibarr </span>'.$langs->trans('InfraSSearchParamPreferedPartner2').'</div>
												</td>
											</tr>
											<tr class = "infrassearchheight25"><td colspan = "3">&nbsp;</td></tr>
										</table>
									</form>';
		$ret					.= load_fiche_titre('<span class = "infrassearchTitleparam">'.$langs->trans('InfraSSearchParamHistoryUpdates').'</span>', '', $listUpD, 1);
		$sxe					= infrassearch_getChangelogFile($appliname);
		$sxelast				= infrassearch_getChangelogFile($appliname, 'dwn');
		$tblversionslast		= is_object($sxelast) ? $sxelast->Version : array();
		if ($resVersion == -1) {
			foreach ($tblversions as $error) {
				$ret	.= $error->message;
			}
			return $ret;
		}
		if (getDolGlobalString('INFRAS_SKIP_CHECKVERSION', '')) {
			$dwnbutton	= $dwn ? $langs->trans('InfraSSearchParamSkipCheck') : '';
		} else {
			$dwnbutton	= $dwn ? '<button class = "button" style = "width: 190px; padding: 3px 0px;" type = "submit" value = "dwnChangelog" name = "action" title = "'.$langs->trans('InfraSSearchParamCheckNewVersionTitle').'">'.$langs->trans('InfraSSearchParamCheckNewVersion').'</button>' : '';
		}
		$ret	.= '			<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
									<input type = "hidden" name = "token" value = "'.newToken().'">
									<table class = "infrassearchnoborder centpercent" >
										<tr class = "liste_titre">
														<th class = "center width100">'.$langs->trans('InfraSSearchParamNumberVersion').'</th>
														<th class = "center width100">'.$langs->trans('InfraSSearchParamMonthVersion').'</th>
														<th class = "left" >'.$langs->trans('InfraSSearchParamChangesVersion').'</th>
											<th class = "center width200">'.$dwnbutton.'</th>
										</tr>';
		if (is_object($sxe) && count($tblversionslast) > count($tblversions)) {	// il y a du nouveau
			for ($i = count($tblversionslast)-1; $i >= 0; $i--) {
				$sxePath		= $sxe->xpath('//Version[@Number="'.$tblversionslast[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversionslast[$i]->change;
				$ret			.= '	<tr class = "oddeven">
											<td class = "center valigntop '.(empty($sxePath) ? 'infrassearchbgorange' : '').'">'.$tblversionslast[$i]->attributes()->Number.'</td>
											<td class = "center valigntop '.(empty($sxePath) ? 'infrassearchbgorange' : '').'">'.$tblversionslast[$i]->attributes()->MonthVersion.'</td>
											<td class = "left valigntop infrassearchnopaddingvert '.(empty($sxePath) ? 'infrassearchbgorange' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infrassearchcaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrassearchgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrassearchblue';
					} else {
						$classcolor	= ' infrassearchblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infrassearchchangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infrassearchchangelogbase'.$classcolor.'">'.$changeline.'</td>
													</tr>
												</table>';
				}
				$ret	.= '				</td>
										</tr>';
			}
		} elseif (is_object($sxelast) && count($tblversionslast) < count($tblversions) && count($tblversionslast) > 0) {	// On est en avance
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$sxelastPath	= $sxelast->xpath('//Version[@Number="'.$tblversions[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversions[$i]->change;
				$ret			.= '	<tr class = "oddeven">
											<td class = "center valigntop '.(empty($sxelastPath) ? 'infrassearchbggreen infrassearchblack' : '').'">'.$tblversions[$i]->attributes()->Number.'</td>
											<td class = "center valigntop '.(empty($sxelastPath) ? 'infrassearchbggreen infrassearchblack' : '').'">'.$tblversions[$i]->attributes()->MonthVersion.'</td>
											<td class = "left valigntop infrassearchnopaddingvert '.(empty($sxelastPath) ? 'infrassearchbggreen' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infrassearchcaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrassearchgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrassearchblue';
					} else {
						$classcolor	= ' infrassearchblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infrassearchchangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infrassearchchangelogbase'.$classcolor.'">'.$changeline.'</td>
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
											<td class = "left valigntop infrassearchnopaddingvert" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infrassearchcaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrassearchgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrassearchblue';
					} else {
						$classcolor	= ' infrassearchblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infrassearchchangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infrassearchchangelogbase'.$classcolor.'">'.$changeline.'</td>
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
	function infrassearch_getSupportInformation($currentversion)
	{
		global $db, $langs;

		$ret	= '	<table class = "infrassearchnoborder" >
						<tr class = "liste_titre">
						<th class = "center width400">'.$langs->trans('InfraSSearchSupportInformation').'</th>
						<th class = "center">'.$langs->trans('Value').'</th>
					</tr>
					<tr class="oddeven">
						<td class = "width400 infrassearchchangelogbase">'.$langs->trans('DolibarrVersion').'</td>
						<td class = "infrassearchchangelogbase">'.DOL_VERSION.'</td>
					</tr>';
	if (getDolGlobalString('DOLINFRAS_VERSION', '')) {
		$ret	.= '<tr class = "oddeven">
						<td class = "width400 infrassearchchangelogbase">'.$langs->trans('InfraSSearchParamDolinfrasVersion').'</td>
						<td class = "infrassearchchangelogbase">'.getDolGlobalString('DOLINFRAS_VERSION', '').'</td>
					</tr>';
	}
	$ret	.= '	<tr class="oddeven">
						<td class = "width400 infrassearchchangelogbase">'.$langs->trans('ModuleVersion').'</td>
						<td class = "infrassearchchangelogbase">'.$currentversion.'</td>
					</tr>
					<tr class="oddeven">
						<td class = "width400 infrassearchchangelogbase">'.$langs->trans('PHPVersion').'</td>
						<td class = "infrassearchchangelogbase">'.version_php().'</td>
					</tr>
					<tr class="oddeven">
						<td class = "width400 infrassearchchangelogbase">'.$langs->trans('DatabaseVersion').'</td>
						<td class = "infrassearchchangelogbase">'.$db::LABEL.' '.$db->getVersion().'</td>
					</tr>
					<tr class="oddeven">
						<td class = "width400 infrassearchchangelogbase">'.$langs->trans('WebServerVersion').'</td>
						<td class = "infrassearchchangelogbase">'.dol_escape_htmltag($_SERVER['SERVER_SOFTWARE']).'</td>
					</tr>
						<tr><td colspan = "3" class = "infrassearchFinal">&nbsp;</td></tr>
				</table>
				<br/>';
		return $ret;
	}
