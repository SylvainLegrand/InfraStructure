<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrassupprice/core/lib/infrassuppriceAdmin.lib.php
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

	/**
	* Define head array for setup pages tabs
	*
	* @return	array			list of head
	**/
	function infrassupprice_admin_prepare_head()
	{
		global $langs, $conf, $user;

		$h		= 0;
		$head	= array();
		if (!empty($user->admin) || !empty($user->hasRight('infrassupprice', 'paramMenu'))) {
			$head[$h][0]	= dol_buildpath('/infrassupprice/admin/infrassuppricesetup.php',1);
			$head[$h][1]	= $langs->trans("InfraSSupPriceParamsSetup");
			$head[$h][2]	= 'infrassuppricesetup';
		}
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infrassupprice_admin');
		$h++;
		$head[$h][0]	= dol_buildpath('/infrassupprice/admin/about.php',1);
		$head[$h][1]	= $langs->trans("About");
		$head[$h][2]	= 'about';
		$h++;
		$head[$h][0]	= dol_buildpath('/infrassupprice/admin/changelog.php', 1);
		$head[$h][1]	= $langs->trans('InfraSSupPriceParamsChangelog');
		$head[$h][2]	= 'changelog';
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infrassupprice_admin', 'remove');
		return $head;
	}

	/**
	*	Test if the menu InfraS on tools top menu in loaded
	*
	**/
	function infrassupprice_no_topmenu()
	{
		global $db, $conf;

		// gestion de la position du menu
		$sql	= 'SELECT rowid FROM '.MAIN_DB_PREFIX.'menu WHERE mainmenu = "tools" AND leftmenu = "infras" AND entity = '.((int) $conf->entity);
		$resql	= $db->query($sql);
		if (!empty($resql)) {
			// il y a un left menu on renvoie 0 : pas besoin d'en créer un nouveau
			if ($db->num_rows($resql) > 0)	return 0;
		}
		return 1;	// pas de top menu on renvoie 1
	}

	/**
	*	Test if the PHP extension 'XML' is loaded
	*
	**/
	function infrassupprice_test_php_ext()
	{
		global $db, $conf, $langs;

		$langs->load('infrassupprice@infrassupprice');

		if (extension_loaded('xml')) {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	1, 'chaine', 0, 'InfraSSupPrice module', $conf->entity);
		} else {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	-1, 'chaine', 0, 'InfraSSupPrice module', $conf->entity);
			setEventMessages('<span class = "infrassuppricecaution">'.$langs->trans('InfraSSupPriceCautionMess').'</span>'.$langs->trans('InfraSSupPriceXMLextError'), array(), 'warnings');
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
	function infrassupprice_getLocalVersionMinDoli($appliname)
	{
		global $langs;

		$currentversion	= array();
		$sxe			= infrassupprice_getChangelogFile($appliname);
		if (is_object($sxe)) {
			$currentversion[0]	= (string) $sxe->Version[count($sxe->Version) - 1]->attributes()->Number;
			$currentversion[1]	= (string) $sxe->Dolibarr->attributes()->minVersion;
			$currentversion[2]	= 0;
			$currentversion[3]	= $sxe->Version;
			$currentversion[4]	= (string) $sxe->Dolibarr->attributes()->maxVersion;
			$currentversion[5]	= (string) $sxe->PHP->attributes()->minVersion;
			$currentversion[6]	= (string) $sxe->PHP->attributes()->maxVersion;
		} else {
			$currentversion[0]	= '<span class = "infrassuppricecaution"><b>'.$langs->trans('InfraSSupPriceChangelogXMLError').'</b></span>';
			$currentversion[1]	= $langs->trans('InfraSSupPricenoMinDolVersion');
			$currentversion[2]	= -1;
			$currentversion[3]	= $langs->trans('InfraSSupPriceChangelogXMLError');
			$currentversion[4]	= $langs->trans('InfraSSupPricenoMaxDolVersion');
			$currentversion[5]	= $langs->trans('InfraSSupPricenoMinDolVersion');
			$currentversion[6]	= $langs->trans('InfraSSupPricenoMaxDolVersion');
			foreach (libxml_get_errors() as $error) {
				$currentversion[3]	.= $error->message;
				dol_syslog('infrassupprice.Lib::infrassupprice_getLocalVersionMinDoli error->message = '.$error->message);
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
	function infrassupprice_getChangelogFile($appliname, $from = '')
	{
		$file	= empty($from) ? dol_buildpath($appliname, 0).'/docs/changelog.xml' : DOL_DATA_ROOT.'/'.$appliname.'/changelogdwn.xml';
		if (is_file($file)) {
			libxml_use_internal_errors(true);
			$context	= stream_context_create(array('http' => array('method' => 'GET', 'header' => 'Accept: application/xml')));
			$changelog	= @file_get_contents($file, false, $context);
			$sxe		= @simplexml_load_string(rtrim($changelog));
			dol_syslog('infrassupprice.Lib::infrassupprice_getChangelogFile appliname = '.$appliname.' from = '.$from.' context = '.$context.' changelog = '.($changelog ? 'Ok' : 'KO').' sxe = '.($sxe ? 'Ok' : 'KO'));
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
	function infrassupprice_dwnChangelog($appliname)
	{
		global $langs, $conf;

		$path				= DOL_DATA_ROOT.'/'.$appliname;
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
	*	Sauvegarde les paramètres du module
	*
	*	@param		string		$appliname	module name
	*	@return		string		1 = Ok or -1 = Ko or or 0 and error message
	**/
	function infrassupprice_bkup_module ($appliname)
	{
		global $db, $conf, $langs, $errormsg;

		// Control dir and file
		$path		= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
		$bkpfile	= $path.'/update.'.$conf->entity;
		if (! file_exists($path)) {
			if (dol_mkdir($path) < 0) {
				$errormsg	= $langs->transnoentities('ErrorCanNotCreateDir', $path);
				return 0;
			}
		}
		if (file_exists($path)) {
			$currentversion	= infrassupprice_getLocalVersionMinDoli('infrassupprice');
			$handle			= fopen($bkpfile, 'w+');
			if (fwrite($handle, '') === FALSE) {
				$langs->load('errors');
				$errormsg	= $langs->trans('ErrorFailedToWriteInDir');
				return -1;
			}
			// Print headers and global mysql config vars
			$sqlhead	= '-- '.$db::LABEL.' dump via php with Dolibarr '.DOL_VERSION.'
--
-- Host: '.$db->db->host_info.'	Database: '.$db->database_name.'
-- ------------------------------------------------------
-- Server version			'.$db->db->server_info.'
-- Dolibarr version			'.DOL_VERSION.'
-- InfraSSupPrice version	'.$currentversion[0].'

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = \'NO_AUTO_VALUE_ON_ZERO\';
';
			fwrite($handle, $sqlhead);
			$cols_const			= array ('name', 'entity', 'value', 'type', 'visible', 'note');
			$duplicate_const	= array ('2', 'value', 'name');
			$sql_const			= 'SELECT '.implode(', ', $cols_const);
			$sql_const			.= ' FROM '.MAIN_DB_PREFIX.'const';
			$sql_const			.= ' WHERE name LIKE "INFRASSUPPRICE\_%"';
			$sql_const			.= ' AND entity = '.((int) $conf->entity);
			$sql_const			.= ' ORDER BY name';
			fwrite($handle, infrassupprice_bkup_table ('const', $sql_const, $cols_const, $duplicate_const, 0, ''));
			// Enabling back the keys/index checking
			$sqlfooter		= '
SET FOREIGN_KEY_CHECKS = 1;

-- Dump completed on '.date('Y-m-d G-i-s').'
';
			fwrite($handle, $sqlfooter);
			fclose($handle);
			if (file_exists($bkpfile)) {
				$moved	= dol_copy($bkpfile, DOL_DATA_ROOT.($conf->entity != 1 ? '/'.$conf->entity : '').'/admin/'.$appliname.'_update'.date('Y-m-d-G-i-s').'.'.$conf->entity);
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
	function infrassupprice_bkup_table ($table, $sql, $listeCols, $duplicate = array (), $truncate = 0, $add = '')
	{
		global $db, $conf, $langs, $errormsg;

		$sqlnewtable	= '';
		$result_sql		= $sql ? $db->query($sql) : '';
		dol_syslog('infrassuppriceAdmin.Lib::infrassupprice_bkup_table sql = '.$sql);
		if (!empty($result_sql)) {
			$truncate		= $truncate ? 'TRUNCATE TABLE '.MAIN_DB_PREFIX.$table.';
' : '';
			$sqlnewtable	= '
-- Dumping data for table '.MAIN_DB_PREFIX.$table.'
'.$truncate.$add;
			while($row	= $db->fetch_row($result_sql)) {
				// For each row of data we print a line of INSERT
				$colsInsert	= '';
				foreach ($listeCols as $col) {
					$colsInsert	.= $col.', ';
				}
				$sqlnewtable	.= 'INSERT INTO '.MAIN_DB_PREFIX.$table.' ('.substr($colsInsert, 0, -2).') VALUES (';
				$columns		= count($row);
				$duplicateValue	= '';
				for($j = 0; $j < $columns; $j++) {
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
	function infrassupprice_restore_module ($appliname)
	{
		global $conf;

		$pathsql	= DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').$appliname.'/sql';
		dol_syslog('infrassuppriceAdmin.Lib::infrassupprice_restore_module $pathsql = '.$pathsql);
		$handle		= @opendir($pathsql);
		if (is_resource($handle)) {
			$filesql	= $pathsql.'/'.'update.'.$conf->entity;
			$moved		= dol_copy($filesql, $filesql.'.sql');
			if (is_file($filesql.'.sql')) {
				$result	= run_sql($filesql.'.sql', (!getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG', '') ? 1 : 0), $conf->entity, 1);
			}
			$delete	= dol_delete_file($filesql.'.sql');
			dol_syslog('infrassuppriceAdmin.Lib::infrassupprice_restore_module appliname = '.$appliname.' filesql = '.$filesql.' moved = '.$moved.' result = '.$result.' delete = '.$delete);
			if ($result > 0) {
				return 1;
			}
		}
		return -1;
	}

	/**
	*	Print HTML backup / restore section
	*
	*	@return		void
	**/
	function infrassupprice_print_backup_restore()
	{
		global $conf, $langs;

		print '	<table class = "centpercent noborderspacing">';
		$metas	= array('*', '90px', '156px', '120px');
		infrassupprice_print_colgroup($metas);
		print '		<tr>
						<td colspan = "2" class = "center infrassuppricetitleparam">
							<a href = "'.DOL_URL_ROOT.'/document.php?modulepart=infrassupprice&file=sql/update.'.$conf->entity.'">'.$langs->trans('InfraSSupPriceParamAction1').' <b><span>'.$langs->trans('modcomnameInfraSSupPrice').'</span></b> '.$langs->trans('InfraSSupPriceParamAction2').'</a>
						</td>
						<td class = "center"><button class = "butAction" type = "submit" value = "bkupParams" name = "action">'.$langs->trans('InfraSSupPriceParamBkup').'</button></td>
						<td class = "center"><button class = "butActionDelete" type = "submit" value = "restoreParams" name = "action">'.$langs->trans('InfraSSupPriceParamRestore').'</button></td>
					</tr>';
		print '		<tr><td colspan = "4" class = "center nopadding"><hr></td></tr>';
		print '		<tr><td colspan = "4" class = "infrassuppriceFinal">&nbsp;</td></tr>';
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
	function infrassupprice_load_title($titre, $morehtmlright = '', $picto = 'generic', $pictoisfullpath = 0, $id = '', $morecssontable = '', $morehtmlcenter = '')
	{
		global $conf;

		$out					= '';
		if ($picto == 'setup')	$picto	= 'generic';
		$out					.= '	<table '.(!empty($id) ? 'id = "'.$id.'" ' : '').'class = "centpercent notopnoleftnoright table-fiche-title'.(!empty($morecssontable) ? ' '.$morecssontable : '').'">
											<tr class = "liste_titre">';
		if (!empty($picto)) {
			$out .= '							<td class = "nobordernopadding widthpictotitle valignmiddle col-picto">'.img_picto('', $picto, 'class = "valignmiddle infrassuppricewidthpictotitle pictotitle"', $pictoisfullpath).'</td>';
		}
		$out	.= '							<td class = "nobordernopadding valignmiddle col-title"><div class = "infrassuppriceDivTitre uppercase inline-block">'.$titre.'</div></td>';
		if (dol_strlen($morehtmlcenter)) {
			$out .= '							<td class = "nobordernopadding center valignmiddle">'.$morehtmlcenter.'</td>';
		}
		if (dol_strlen($morehtmlright)) {
			$out .= '							<td class = "nobordernopadding titre_right wordbreakimp right valignmiddle">'.$morehtmlright.'</td>';
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
	function infrassupprice_print_colgroup($metas = array())
	{
		print '	<tr>';
		foreach ($metas as $values) {
			print '<td class = "infrassuppriceFinal nopadding"'.($values == '*' ? '' : ' width = "'.$values.'"').' style = "'.($values == '*' ? '' : ' max-width: '.$values.'; min-width: '.$values.'; width: '.$values.';').'">&nbsp;</td>';
		}
		print '	</tr>';
	}

	/**
	*	Print HTML title for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	**/
	function infrassupprice_print_liste_titre($metas = array())
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
	function infrassupprice_print_btn_action($action, $desc = '', $cs1 = 3, $alignclass = 'center', $lbl = 'Modify', $noRowspan = false)
	{
		global $langs;

		print '	<tr>
					<td colspan = "'.$cs1.'" class = "'.$alignclass.'">'.$desc.'</td>
					<td'.(empty($noRowspan) ? ' rowspan = "0"' : '').' class = "center valigntop"><button class = "button infrassuppricewidth110" type = "submit" value = "update_'.$action.'" name = "action">'.$langs->trans($lbl).'</button></td>
				</tr>';
	}

	/**
	*	Print HTML HR line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infrassupprice_print_hr($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'"><hr class = "infrassuppriceHR"></td></tr>';
	}

	/**
	*	Print HTML subtitle line
	*
	*	@param		int			$cs1		first colspan
	*	@param		string		$subtitle	subtitle or translation key for subtitle
	*	@return		void
	**/
	function infrassupprice_print_subTitle($cs1 = 3, $subtitle = '')
	{
		global $langs;

		infrassupprice_print_hr($cs1);
		print '	<tr>
					<td colspan = "'.$cs1.'" class = "center"><span class = "infrassuppricesubtitleparam">'.$langs->trans($subtitle).'</span></td>
				</tr>';
	}

	/**
	*	Print HTML final line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infrassupprice_print_final($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'" class = "infrassuppriceFinal">&nbsp;</td></tr>';
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
	*	@return		int							line number for next option
	**/
	function infrassupprice_print_input($confkey, $tag = 'on_off', $desc = '', $help = '', $metas = '', $cs1 = 2, $cs2 = 1, $end = '', $num = 0)
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
			if (!empty($help)) {
				print $form->textwithtooltip(($desc ? $desc : $langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
			} else {
				print $desc ? $desc : $langs->trans($confkey);
			}
			print '	</td>
					<td colspan = "'.$cs2.'" class = "center">';
		} else {
			print '	<td colspan = "'.($cs1 + $cs2).'" class = "center">';
			if (!empty($desc))	print $desc.'<br/>';
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
			if (!empty($metas['type']) && $metas['type'] == 'number' && !empty($metas['min'])) {
				$currentValue	= getDolGlobalInt($confkey, $metas['min']);
				$inputValue		= $currentValue < $metas['min'] ? $metas['min'] : $currentValue;
			}
			// default input
			$defaultMetas		= array('type' => 'text', 'class' => 'flat quatrevingtpercent nopadding', 'style' => 'font-size: inherit;', 'name' => $confkey, 'id' => $confkey, 'value' => $inputValue);
			$metas				= array_merge ($defaultMetas, $metas);
			$metascompil		= '';
			foreach ($metas as $key => $value)	$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' ? '' : ' = "'.$value.'"');
			print '	<'.$tag.' '.$metascompil.'>'.(!preg_match('/<td(.*)/', $end, $reg) ? $end : '');
		} elseif ($tag == 'input2') {
			foreach ($metas as $keymeta => $meta2) {
				if (preg_match('/intercal(.*)/', $keymeta, $reg)) {
					print $meta2;
					continue;
				}
				$defaultMetas						= array('type' => 'text', 'class' => 'flat quatrevingtpercent nopadding', 'style' => 'font-size: inherit;', 'name' => $keymeta, 'id' => $keymeta, 'value' => getDolGlobalString($keymeta, ''));
				$meta								= array_merge ($defaultMetas, $meta2);
				$metascompil						= '';
				foreach ($meta as $key => $value)	$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' || $key == 'checked' ? '' : ' = "'.$value.'"');
				print '	<input '.$metascompil.'>';
			}
			print !preg_match('/<td(.*)/', $end, $reg) ? $end : '';
		} elseif ($tag == 'radio') {
			foreach ($metas as $keymeta => $meta2) {
				if (preg_match('/intercal(.*)/', $keymeta, $reg)) {
					print $meta2;
					continue;
				}
				$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent nopadding', 'style' => 'font-size: inherit;', 'id' => $keymeta);
				$meta			= array_merge ($defaultMetas, $meta2);
				$metascompil	= '';
				foreach ($meta as $key => $value) {
					$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' || $key == 'checked' ? '' : ' = "'.$value.'"');
				}
				print '	<'.$tag.' '.$metascompil.'>';
			}
			print !preg_match('/<td(.*)/', $end, $reg) ? $end : '';
		} elseif ($tag == 'textarea') {
			if (empty(getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT', ''))) {
				print '<textarea name = "'.$confkey.'" class = "flat" cols = "120">'.getDolGlobalString($confkey, '').'</textarea>';
			} else {
				$doleditor	= new DolEditor($confkey, getDolGlobalString($confkey, ''), 0, 80, 'dolibarr_notes');
				print $doleditor->Create();
			}
		} elseif ($tag == 'color') {
			print $formother->selectColor($metas, $confkey);
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
	function infrassupprice_print_line_inputs($type = '', $desc = '', $metas = array(), $cs1 = 2, $w = 0, $end = '', $num = 0)
	{
		global $conf;

		print '	<tr class = "oddeven">';
		if (!empty($num)) {
			print '	<td class = "center bold">'.$num.'</td>';
			$num++;
		}
		print '		<td colspan = "'.$cs1.'">
						<table class = "centpercent">
							<tr>
								<td rowspan = "2" class = "noborder">'.$desc.'</td>';
		foreach ($metas[0] as $confkey => $value) {
			$confkey	= str_replace('_AUTO', '', $confkey);
			print '				<td class = "center noborder" style = "max-width: '.$w.'px; min-width: '.$w.'px; width: '.$w.'px;">'.($type == 'tests' ? (getDolGlobalString($confkey, '') ? $value : '&nbsp;') : $value).'</td>';
		}
		print '				</tr>
							<tr>';
		foreach ($metas[1] as $confkey => $value) {
			print '				<td class = "center noborder">';
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
	function infrassupprice_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn = 0)
	{
		global $langs, $conf, $user;

		$langs->loadLangs(array('admin', 'errors', 'infrassupprice@infrassupprice'));

		$supportURL				= 'https://support.infras.fr/create_ticket.php';
		$headerPath				= dol_buildpath('/'.$appliname.'/img/InfraSheader.png', 1);
		$logoPath				= dol_buildpath('/'.$appliname.'/img/InfraS.png', 1);
		$logoDolistorePath		= dol_buildpath('/'.$appliname.'/img/dolistore_logo.png', 1);
		$preferedPartnerPath	= dol_buildpath('/'.$appliname.'/img/Dolibarr_preferred_partner.png', 1);
		$listUpD				= dol_buildpath('/'.$appliname.'/img/list_updates.png', 1);
		$urlInfraS				= 'https://infras.fr';
		$urlWiki				= 'https://wiki.infras.fr/books/'.$appliname.'/page/presentation-generale';
		$urlstore				= 'https://infras.store/';
		$urlDoli				= 'https://www.dolistore.com/index.php?controller=search&search_query=infras';
		$InputCarac				= 'class = "butAction nopadding infrassuppriceWidth180 infrassuppriceheight32" name = "readmore" type = "button"';
		$supportvalue			= '/******************************'.'<br/>';
		$supportvalue			.= ' * Module : '.$langs->trans('modcomnameInfraSSupPrice').'<br/>';
		$supportvalue			.= ' * Module version : '.$version.'<br/>';
		$supportvalue			.= ' * Dolibarr version : '.DOL_VERSION.'<br/>';
		$supportvalue			.= ' * PHP version : '.PHP_VERSION.'<br/>';
		$supportvalue			.= ' ******************************/'.'<br/>';
		$supportvalue			.= 'Description de votre demande :'.'<br/>';
		$ret					= '	<form id = "ticket" method = "POST" target = "_blank" action = "'.$supportURL.'">
										<input name = message type = "hidden" value = "'.$supportvalue.'" />
										<input name = email type = "hidden" value = "'.$user->email.'" />
										<input name = category_code type = "hidden" value = "'.(strtoupper($langs->trans('modcomnameInfraSSupPrice'))).'" />
										<table class = "centpercent" style = "padding: 10; background: url('.$headerPath.'); background-size: cover;">
											<tr class = "infrassuppriceheight75">
												<td colspan = "3" class = "center bold valignmiddle">
													<a href = "'.$urlWiki.'" target = "_blank">
														<span class = "infrassuppricecolor" style = "font-size: 24px;">'.$langs->trans('InfraSSupPriceParamPresent1').'<span class = "infrassupneuropolinfras"> InfraS</span>'.$langs->trans('InfraSSupPriceParamPresent2').'</span>
													</a>
												</td>
											</tr>
											<tr class = "infrassuppriceheight50">
												<td rowspan = "3" class = "left bold valignbottom infrassupwidthtrentepercent infrassuppriceslogan" style = "color: white; font-size: 16px;">
													<a href = "'.$urlInfraS.'" target = "_blank"><img class = "noborder infrassuppriceWidth220" src = "'.$logoPath.'"></a>
													<br/>&nbsp;&nbsp;'.$langs->trans('InfraSSupPriceParamSlogan').'
												</td>
												<td class = "center valignmiddle infrassupwidthtrentepercent">
													<a href = "'.$urlstore.'" target = "_blank"><input '.$InputCarac.' value = "'.$langs->trans('InfraSSupPriceParamLienModules').'" /></a>
													<button class = "butAction nopadding infrassuppriceWidth180 infrassuppriceheight32" type = "submit" >'.$langs->trans('InfraSSupPriceParamSupport').'</button>
												</td>
												<td rowspan = "3" class = "right bold valignbottom infrassupwidthtrentepercent infrassuppriceslogan">
													<a href = "'.$urlDoli.'" target = "_blank"><img class = "noborder infrassuppriceWidth270" src = "'.$logoDolistorePath.'"></a>&nbsp;&nbsp;
													<br/>'.$langs->trans('InfraSSupPriceParamMoreModulesLink').'&nbsp;&nbsp;
												</td>
											</tr>
											<tr>
												<td class = "center valignbottom infrassupminwidth800imp">
													<img class = "noborder infrassuppriceWidth220 infrassupmargintop10imp" src="'.$preferedPartnerPath.'"/>
												</td>
											</tr>
											<tr>
												<td class = "center bold valignbottom infrassupminwidth800imp infrassuppriceslogan">
													<div class = "infrassupmargintop10imp">'.$langs->trans('InfraSSupPriceParamPreferedPartner1').'<span class = "infrassuppuentedolibarr"> Dolibarr </span>'.$langs->trans('InfraSSupPriceParamPreferedPartner2').'</div>
												</td>
											</tr>
											<tr class = "infrassuppriceheight25"><td colspan = "3">&nbsp;</td></tr>
										</table>
									</form>';
		$ret					.= load_fiche_titre('<span class = "infrastitleparam">'.$langs->trans('InfraSSupPriceParamHistoryUpdates').'</span>', '', $listUpD, 1);
		$sxe					= infrassupprice_getChangelogFile($appliname);
		$sxelast				= infrassupprice_getChangelogFile($appliname, 'dwn');
		if (is_object($sxelast))	{
			$tblversionslast	= $sxelast->Version;
		} else {
			$tblversionslast	= array();
		}
		if ($resVersion == -1) {
			foreach ($tblversions as $error) {
				$ret	.= $error->message;
			}
			return $ret;
		}
		if (getDolGlobalString('INFRAS_SKIP_CHECKVERSION', '')) {
			$dwnbutton	= $dwn ? $langs->trans('InfraSSupPriceParamSkipCheck') : '';
		} else {
			$dwnbutton	= $dwn ? '<button class = "button" style = "width: 190px; padding: 3px 0px;" type = "submit" value = "dwnChangelog" name = "action" title = "'.$langs->trans('InfraSSupPriceParamCheckNewVersionTitle').'">'.$langs->trans('InfraSSupPriceParamCheckNewVersion').'</button>' : '';
		}
		$ret	.= '		<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
								<input type = "hidden" name = "token" value = "'.newToken().'">
								<table class = "noborder" >
									<tr class = "liste_titre">
										<th class = "center width100">'.$langs->trans('InfraSSupPriceParamNumberVersion').'</th>
										<th class = "center width100">'.$langs->trans('InfraSSupPriceParamMonthVersion').'</th>
										<th class = "left" >'.$langs->trans('InfraSSupPriceParamChangesVersion').'</th>
										<th class = "center width200">'.$dwnbutton.'</th>
									</tr>';
		if (is_object($sxe) && count($tblversionslast) > count($tblversions)) {	// il y a du nouveau
			for ($i = count($tblversionslast)-1; $i >= 0; $i--) {
				$sxePath		= $sxe->xpath('//Version[@Number="'.$tblversionslast[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversionslast[$i]->change;
				$ret			.= '<tr class = "oddeven">
										<td class = "center valigntop '.(empty($sxePath) ? 'infrassuppricebgorange' : '').'">'.$tblversionslast[$i]->attributes()->Number.'</td>
										<td class = "center valigntop '.(empty($sxePath) ? 'infrassuppricebgorange' : '').'">'.$tblversionslast[$i]->attributes()->MonthVersion.'</td>
										<td class = "left valigntop nopaddingvert '.(empty($sxePath) ? 'infrassuppricebgorange' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor = ' infrassuppricecaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor = ' infrassuppricegreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor = ' infrassuppriceblue';
					} else {
						$classcolor = ' infrassuppriceblack';
					}
					$ret	.= '			<table>
												<tr>
													<td class = "width50 infrassuppricechangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
													<td class = "infrassuppricechangelogbase'.$classcolor.'">'.$changeline.'</td>
												</tr>
											</table>';
				}
				$ret	.= '			</td>
									</tr>';
			}
		} elseif ($sxelast !== false && count($tblversionslast) < count($tblversions) && count($tblversionslast) > 0) {
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$sxelastPath	= $sxelast->xpath('//Version[@Number="'.$tblversions[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversions[$i]->change;
				$ret			.= '<tr class = "oddeven">
										<td class = "center valigntop '.(empty($sxelastPath) ? 'infrassuppricebggreen infrassuppriceblack' : '').'">'.$tblversions[$i]->attributes()->Number.'</td>
										<td class = "center valigntop '.(empty($sxelastPath) ? 'infrassuppricebggreen infrassuppriceblack' : '').'">'.$tblversions[$i]->attributes()->MonthVersion.'</td>
										<td class = "left valigntop nopaddingvert '.(empty($sxelastPath) ? 'infrassuppricebggreen' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor = ' infrassuppricecaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor = ' infrassuppricegreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor = ' infrassuppriceblue';
					} else {
						$classcolor = ' infrassuppriceblack';
					}
					$ret	.= '			<table>
												<tr>
													<td class = "width50 infrassuppricechangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
													<td class = "infrassuppricechangelogbase'.$classcolor.'">'.$changeline.'</td>
												</tr>
											</table>';
				}
				$ret	.= '			</td>
									</tr>';
			}
		} else {	//on est à jour des versions ou pas de connection internet
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$lineversion	= $tblversions[$i]->change;
				$ret			.= '<tr class = "oddeven">
										<td class = "center valigntop">'.$tblversions[$i]->attributes()->Number.'</td>
										<td class = "center valigntop">'.$tblversions[$i]->attributes()->MonthVersion.'</td>
										<td class = "left valigntop nopaddingvert" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor = ' infrassuppricecaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor = ' infrassuppricegreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor = ' infrassuppriceblue';
					} else {
						$classcolor = ' infrassuppriceblack';
					}
					$ret	.= '			<table>
												<tr>
													<td class = "width50 infrassuppricechangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
													<td class = "infrassuppricechangelogbase'.$classcolor.'">'.$changeline.'</td>
												</tr>
											</table>';
				}
				$ret	.= '			</td>
									</tr>';
			}
		}
		$ret	.= '			</table>
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
	function infrassupprice_getSupportInformation($currentversion)
	{
		global $db, $langs;

		$ret	= '	<table class = "noborder" >
						<tr class = "liste_titre">
							<th class = "center width400">'.$langs->trans('InfraSSupPricesupportInformation').'</th>
							<th class = "center">'.$langs->trans('Value').'</th>
						</tr>
						<tr class = "oddeven">
							<td class = "width400 infrassuppricechangelogbase">'.$langs->trans('DolibarrVersion').'</td>
							<td class = "infrassuppricechangelogbase">'.DOL_VERSION.'</td>
						</tr>';
		if (getDolGlobalString('DOLINFRAS_VERSION', '')) {
			$ret	.= '<tr class = "oddeven">
							<td class = "width400 infrassuppricechangelogbase">'.$langs->trans('InfraSSupPriceParamDolinfrasVersion').'</td>
							<td class = "infrassuppricechangelogbase">'.getDolGlobalString('DOLINFRAS_VERSION', '').'</td>
						</tr>';
		}
		$ret	.= '	<tr class = "oddeven">
							<td class = "width400 infrassuppricechangelogbase">'.$langs->trans('ModuleVersion').'</td>
							<td class = "infrassuppricechangelogbase">'.$currentversion.'</td>
						</tr>
						<tr class = "oddeven">
							<td class = "width400 infrassuppricechangelogbase">'.$langs->trans('PHPVersion').'</td>
							<td class = "infrassuppricechangelogbase">'.version_php().'</td>
						</tr>
						<tr class = "oddeven">
							<td class = "width400 infrassuppricechangelogbase">'.$langs->trans('DatabaseVersion').'</td>
							<td class = "infrassuppricechangelogbase">'.$db::LABEL.' '.$db->getVersion().'</td>
						</tr>
						<tr class = "oddeven">
							<td class = "width400 infrassuppricechangelogbase">'.$langs->trans('WebServerVersion').'</td>
							<td class = "infrassuppricechangelogbase">'.dol_escape_htmltag($_SERVER['SERVER_SOFTWARE']).'</td>
						</tr>
						<tr><td colspan = "3" class = "infrassuppriceFinal">&nbsp;</td></tr>
					</table>
					<br/>';
		return $ret;
	}
