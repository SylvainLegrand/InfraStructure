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
	* 	\file		./infrascusprice/core/lib/infrascuspriceAdmin.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraSCusPrice module
	************************************************/
	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

	/**
	* Define head array for setup pages tabs
	*
	* @return	array			list of head
	**/
	function infrascusp_admin_prepare_head ()
	{
		global $langs, $conf, $user;

		$h				= 0;
		$head			= array();
		if (!empty($user->admin) || !empty($user->hasRight('infrascusprice', 'paramInfraSCusPrice'))) {
			$head[$h][0]	= dol_buildpath('/infrascusprice/admin/infrascuspricesetup.php', 1);
			$head[$h][1]	= $langs->trans('InfraSCusPParams');
			$head[$h][2]	= 'infrascuspricesetup';
		}
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infrascusp_admin');
		$h++;
		$head[$h][0]	= dol_buildpath('/infrascusprice/admin/about.php', 1);
		$head[$h][1]	= $langs->trans('About');
		$head[$h][2]	= 'about';
		$h++;
		$head[$h][0]	= dol_buildpath('/infrascusprice/admin/changelog.php', 1);
		$head[$h][1]	= $langs->trans('InfraSCusPParamsChangeLog');
		$head[$h][2]	= 'changelog';
		complete_head_from_modules($conf, $langs, null, $head, $h, 'infrascusp_admin', 'remove');
		return $head;
	}

	/**
	*	Test if the menu InfraS on tools top menu in loaded
	*
	**/
	function infrascusp_no_topmenu()
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
	function infrascusp_test_php_ext()
	{
		global $db, $conf, $langs;

		$langs->load('infrascusprice@infrascusprice');

		if (extension_loaded('xml')) {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	1, 'chaine', 0, 'InfraSCusPrice module', $conf->entity);
		} else {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	-1, 'chaine', 0, 'InfraSCusPrice module', $conf->entity);
			setEventMessages('<span class = "infrascuspCaution">'.$langs->trans('InfraSCusPCautionMess').'</span>'.$langs->trans('InfraSXMLextError'), array(), 'warnings');
		}
	}

	/**
	* Function called to check module name from local changelog
	* Control of the min version of Dolibarr needed and get versions list
	*
	* @param	string	$appliname	module name
	* @return	array				[0] current version from changelog
	* 								[1] Dolibarr min version
	*								[2] flag for error (-1 = KO ; 0 = OK)
	*								[3] array => versions list or errors list
	*								[4] Dolibarr max version
	*								[5] PHP min version
	*								[6] PHP max version
	**/
	function infrascusp_getLocalVersionMinDoli($appliname)
	{
		global $langs;

		$currentversion	= array();
		$sxe			= infrascusp_getChangelogFile($appliname);
		if (is_object($sxe)) {
			$currentversion[0]	= (string) $sxe->Version[count($sxe->Version) - 1]->attributes()->Number;
			$currentversion[1]	= (string) $sxe->Dolibarr->attributes()->minVersion;
			$currentversion[2]	= 0;
			$currentversion[3]	= $sxe->Version;
			$currentversion[4]	= (string) $sxe->Dolibarr->attributes()->maxVersion;
			$currentversion[5]	= (string) $sxe->PHP->attributes()->minVersion;
			$currentversion[6]	= (string) $sxe->PHP->attributes()->maxVersion;
		} else {
			$currentversion[0]	= '<span class = "infrascuspCaution"><b>'.$langs->trans('InfraSCusPChangelogXMLError').'</b></span>';
			$currentversion[1]	= $langs->trans('InfraSCusPnoMinDolVersion');
			$currentversion[2]	= -1;
			$currentversion[3]	= $langs->trans('InfraSCusPChangelogXMLError');
			$currentversion[4]	= $langs->trans('InfraSCusPnoMaxDolVersion');
			$currentversion[5]	= $langs->trans('InfraSCusPnoMinDolVersion');
			$currentversion[6]	= $langs->trans('InfraSCusPnoMaxDolVersion');
			foreach (libxml_get_errors() as $error) {
				$currentversion[3]	.= $error->message;
				dol_syslog('infrascuspriceAdmin.Lib::infrascusp_getLocalVersionMinDoli error->message = '.$error->message);
			}
		}
		return $currentversion;
	}

	/**
	* Function called to check module name from local changelog
	* Control of the min version of Dolibarr needed and get versions list
	*
	* @param   string			$appliname	module name
	* @param   string			$from		sufixe name to separate inner changelog from download
	* @return	string|boolean				changelog file contents or false
	**/
	function infrascusp_getChangelogFile($appliname, $from = '')
	{
		$file	= empty($from) ? dol_buildpath($appliname, 0).'/docs/changelog.xml' : DOL_DATA_ROOT.'/'.$appliname.'/changelogdwn.xml';
		if (is_file($file)) {
			libxml_use_internal_errors(true);
			$context	= stream_context_create(array('http' => array('method' => 'GET', 'header' => 'Accept: application/xml')));
			$changelog	= @file_get_contents($file, false, $context);
			$sxe		= @simplexml_load_string(rtrim($changelog));
			dol_syslog('infrascuspriceAdmin.Lib::infrascusp_getChangelogFile appliname = '.$appliname.' from = '.$from.' context = '.$context.' changelog = '.($changelog ? 'Ok' : 'KO').' sxe = '.($sxe ? 'Ok' : 'KO'));
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
	function infrascusp_dwnChangelog($appliname)
	{
		$path	= DOL_DATA_ROOT.'/'.$appliname;
		if (getDolGlobalString('INFRAS_PHP_EXT_XML', '') == -1) {
			return -1;
		}
		$newVersion	= getURLContent('https://raw.githubusercontent.com/InfraS-SARL/modules-versions/main/'.$appliname.'/changelog.xml', 'GET', '', 1, array(), array('http', 'https'), 0);
		if (!isset($newVersion['content'])) {	// not connected
			return -1;
		} else {
			$newhtmlversion	= preg_replace('#Downloaded=\".+\"#', 'Downloaded="'.date('Ymd').'"', $newVersion['content']);
			file_put_contents($path.'/changelogdwn.xml', $newhtmlversion);
		}
	return 1;
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
	function infrascusp_num_pos(&$selectvalue, $listselect)
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
	function infrascusp_print_backup_restore()
	{
		global $conf, $langs;

		print '	<table class = "centpercent noborderspacing">';
		$metas	= array('*', '90px', '156px', '120px');
		infrascusp_print_colgroup($metas);
		print '		<tr>
						<td colspan = "2" class = "center infrascuspTitleparam">
							<a href = "'.DOL_URL_ROOT.'/document.php?modulepart=infrascusprice&file=sql/update.'.$conf->entity.'">'.$langs->trans('InfraSCusPParamAction1').' <b><span>'.$langs->trans('modcomnameCusP').'</span></b> '.$langs->trans('InfraSCusPParamAction2').'</a>
						</td>
						<td class = "center"><button class = "butAction" type = "submit" value = "bkupParams" name = "action">'.$langs->trans('InfraSCusPParamBkup').'</button></td>
						<td class = "center"><button class = "butActionDelete" type = "submit" value = "restoreParams" name = "action">'.$langs->trans('InfraSCusPParamRestore').'</button></td>
					</tr>';
		infrascusp_print_hr(count($metas));
		infrascusp_print_final(count($metas));
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
	function infrascusp_load_title($titre, $morehtmlright = '', $picto = 'generic', $pictoisfullpath = 0, $id = '', $morecssontable = '', $morehtmlcenter = '')
	{
		$out	= '';
		if ($picto == 'setup')	{
			$picto	= 'generic';
		}
		$out	.= '<table '.(!empty($id) ? 'id = "'.$id.'" ' : '').'class = "centpercent notopnoleftnoright table-fiche-title'.(!empty($morecssontable) ? ' '.$morecssontable : '').'">
					<tr class = "liste_titre">';
		if (!empty($picto)) {
			$out .= '	<td class = "infrascuspricenoborder infrascuspricenopadding widthpictotitle valignmiddle col-picto">'.img_picto('', $picto, 'class = "valignmiddle infrascuspwidthpictotitle pictotitle"', $pictoisfullpath).'</td>';
		}
		$out	.= '	<td class = "infrascuspricenoborder infrascuspricenopadding valignmiddle col-title"><div class = "infrascuspDivTitre uppercase inline-block">'.$titre.'</div></td>';
		if (dol_strlen($morehtmlcenter)) {
			$out .= '	<td class = "infrascuspricenoborder infrascuspricenopadding center valignmiddle">'.$morehtmlcenter.'</td>';
		}
		if (dol_strlen($morehtmlright)) {
			$out .= '	<td class = "infrascuspricenoborder infrascuspricenopadding titre_right wordbreakimp right valignmiddle">'.$morehtmlright.'</td>';
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
	function infrascusp_print_colgroup($metas = array())
	{
		print '	<tr>';
		foreach ($metas as $values)	{
			print '<td class = "infrascuspFinal infrascuspricenopadding"'.($values == '*' ? '' : ' width = "'.$values.'"').' style =" height: 1px;'.($values == '*' ? '' : ' max-width: '.$values.'; min-width: '.$values.'; width: '.$values.';').'">&nbsp;</td>';
		}
		print '	</tr>';
	}

	/**
	*	Print HTML title for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	**/
	function infrascusp_print_liste_titre($metas = array())
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
	function infrascusp_print_btn_action($action, $desc = '', $cs1 = 3, $alignclass = 'center', $lbl = 'Modify', $noRowspan = false)
	{
		global $langs;

		print '	<tr>
					<td colspan = "'.$cs1.'" class = "'.$alignclass.'">'.$desc.'</td>
					<td'.(empty($noRowspan) ? ' rowspan = "0"' : '').' class = "center valigntop"><button class = "button infrascuspricewidth110" type = "submit" value = "update_'.$action.'" name = "action">'.$langs->trans($lbl).'</button></td>
				</tr>';
	}

	/**
	*	Print HTML HR line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infrascusp_print_hr($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'"><hr class = "infrascuspHR"></td></tr>';
	}

	/**
	*	Print HTML subtitle line
	*
	*	@param		int			$cs1		first colspan
	*	@param		string		$subtitle	subtitle or translation key for subtitle
	*	@return		void
	**/
	function infrascusp_print_subTitle($cs1 = 3, $subtitle = '')
	{
		global $langs;

		infrascusp_print_hr($cs1);
		print '	<tr>
					<td colspan = "'.$cs1.'" class = "center"><span class = "infrascusp_subtitleparam">'.$langs->trans($subtitle).'</span></td>
				</tr>';
	}

	/**
	*	Print HTML final line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function infrascusp_print_final($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'" class = "infrascuspFinal">&nbsp;</td></tr>';
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
	function infrascusp_print_input($confkey, $tag = 'on_off', $desc = '', $help = '', $metas = '', $cs1 = 2, $cs2 = 1, $end = '', $num = 0)
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
				$numcol	= infrascusp_num_pos($metas['arrayTObjectType'], $metas['validListTObjectType']);
				if ($numcol['err']) {
					print ' class = "infrascuspCaution"';
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
			$defaultMetas		= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrascuspricenopadding', 'style' => 'font-size: inherit;', 'name' => $confkey, 'id' => $confkey, 'value' => $inputValue);
			$metas				= array_merge ($defaultMetas, $metas);
			$metascompil		= '';
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
				$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrascuspricenopadding infrascuspricefontsizeinherit', 'name' => $keymeta, 'id' => $keymeta, 'value' => getDolGlobalString($keymeta, ''));
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
				$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent infrascuspricenopadding infrascuspricefontsizeinherit', 'id' => $keymeta);
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
			$numcol	= infrascusp_num_pos($metas['arrayTObjectType'], $metas['validListTObjectType']);
			print '	<select name = "'.$metas['arrayTObjectType']['select'].'" class = "flat infrascuspricefontsizeinherit infrascuspricenopadding infrascuspricenoborder cursorpointer">
						'.$numcol['options'].'
					</select>';
			if ($numcol['err']) {
				print '&nbsp;&nbsp;<i class = "fa fa-exclamation-triangle infrascuspCaution"></i>';
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
	function infrascusp_print_line_inputs($type = '', $desc = '', $metas = array(), $cs1 = 2, $w = 0, $end = '', $num = 0)
	{
		print '	<tr class = "oddeven">';
		if (!empty($num)) {
			print '	<td class = "center bold">'.$num.'</td>';
			$num++;
		}
		print '		<td colspan = "'.$cs1.'">
						<table class = "centpercent">
							<tr>
								<td rowspan = "2" class = "infrascuspricenoborder">'.$desc.'</td>';
		foreach ($metas[0] as $confkey => $value) {
			$confkey	= str_replace('_AUTO', '', $confkey);
			print '				<td class = "center infrascuspricenoborder" style = "max-width: '.$w.'px; min-width: '.$w.'px; width: '.$w.'px;">'.($type == 'tests' ? (getDolGlobalString($confkey, '') ? $value : '&nbsp;') : $value).'</td>';
		}
		print '				</tr>
							<tr>';
		foreach ($metas[1] as $confkey => $value) {
			print '				<td class = "center infrascuspricenoborder">';
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
	* @param   string	$appliname		module name
	* @param	string	$version		version number
	* @param   string	$resVersion		flag for error (-1 = KO ; 0 = OK)
	* @param   array	$tblversions	array => versions list or errors list
	* @param	int		$dwn			flag to show download button (0 = hide it ; 1 = show it)
	* @return	string					HTML presentation
	**/
	function infrascusp_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn = 0)
	{
		global $langs, $user;

		$langs->loadLangs(array('admin', 'errors', 'infrascusprice@infrascusprice'));

		$supportURL				= 'https://support.infras.fr/create_ticket.php';
		$headerPath				= dol_buildpath('/'.$appliname.'/img/InfraSheader.png', 1);
		$logoPath				= dol_buildpath('/'.$appliname.'/img/InfraS.png', 1);
		$logoDolistorePath		= dol_buildpath('/'.$appliname.'/img/dolistore_logo.png', 1);
		$preferedPartnerPath	= dol_buildpath('/'.$appliname.'/img/Dolibarr_preferred_partner.png', 1);
		$listUpD				= dol_buildpath('/'.$appliname.'/img/list_updates.png', 1);
		$urlInfraS				= 'https://infras.fr';
		$urlWiki				= 'https://wiki.infras.fr/books/'.$appliname.'/page/presentation-du-module';
		$urlstore				= 'https://infras.store/';
		$urlDoli				= 'https://www.dolistore.com/index.php?controller=search&orderby=position&orderway=desc&website=marketplace&search_query=InfraS';
		$InputCarac				= 'class = "butAction infrascuspricenopadding infrascuspricewidth180 infrascuspriceheight32" name = "readmore" type = "button"';
		$supportvalue			= '/******************************'.'<br/>';
		$supportvalue			.= ' * Module : '.$langs->trans('modcomnameCusP').'<br/>';
		$supportvalue			.= ' * Module version : '.$version.'<br/>';
		$supportvalue			.= ' * Dolibarr version : '.DOL_VERSION.'<br/>';
		$supportvalue			.= ' * PHP version : '.PHP_VERSION.'<br/>';
		$supportvalue			.= ' ******************************/'.'<br/>';
		$supportvalue			.= 'Description de votre demande :'.'<br/>';
		$ret					= '	<form id = "ticket" method = "POST" target = "_blank" action = "'.$supportURL.'">
										<input name = message type = "hidden" value = "'.$supportvalue.'" />
										<input name = email type = "hidden" value = "'.$user->email.'" />
										<input name = category_code type = "hidden" value = "'.(strtoupper($langs->trans('modcomnameCusP'))).'" />
										<table class = "centpercent" style = "padding: 10; background: url('.$headerPath.'); background-size: cover;">
											<tr class = "infrascuspriceheight75">
												<td colspan = "3" class = "center bold valignmiddle">
													<a href = "'.$urlWiki.'" target = "_blank">
														<span class = "infrascuspColor" style = "font-size: 24px;">'.$langs->trans('InfraSCusPParamPresent1').'<span class = "infrascuspriceneuropolinfras"> InfraS</span>'.$langs->trans('InfraSCusPParamPresent2').'</span>
													</a>
												</td>
											</tr>
											<tr class = "infrascuspriceheight75">
												<td rowspan = "3" class = "left bold valignbottom infrascuspricewidthtrentepercent infrascuspSlogan" style = "color: white; font-size: 16px;">
													<a href = "'.$urlInfraS.'" target = "_blank"><img class = "infrascuspricenoborder infrascuspricewidth220" src = "'.$logoPath.'"></a>
													<br/>&nbsp;&nbsp;'.$langs->trans('InfraSCusPParamSlogan').'
												</td>
												<td class = "center valignmiddle infrascuspricewidthtrentepercent">
													<a class = "center" href = "'.$urlstore.'" target = "_blank"><input '.$InputCarac.' value = "'.$langs->trans('InfraSCusPParamLienModules').'" /></a>
													<button class = "butAction infrascuspricenopadding infrascuspricewidth180 infrascuspriceheight32" type = "submit" >'.$langs->trans('InfraSCusPParamSupport').'</button>
												</td>
												<td rowspan = "3" class = "right bold valignbottom infrascuspricewidthtrentepercent infrascuspSlogan">
													<a href = "'.$urlDoli.'" target = "_blank"><img class = "infrascuspricenoborder infrascuspricewidth270" src = "'.$logoDolistorePath.'"></a>&nbsp;&nbsp;
													<br/>'.$langs->trans('InfraSCusPParamMoreModulesLink').'&nbsp;&nbsp;
												</td>
											</tr>
											<tr>
												<td class = "center valignbottom infrascuspriceminwidth700imp">
													<img class = "infrascuspricenoborder infrascuspricewidth220 infrascuspricemargintop10imp" src="'.$preferedPartnerPath.'"/>
												</td>
											</tr>
											<tr>
												<td class = "center bold valignbottom infrascuspriceminwidth700imp infrascuspSlogan">
													<div class = "infrascuspricemargintop10imp">'.$langs->trans('InfraSCusPParamPreferedPartner1').'<span class = "infrascuspricepuentedolibarr"> Dolibarr </span>'.$langs->trans('InfraSCusPParamPreferedPartner2').'</div>
												</td>
											</tr>
											<tr class = "infrascuspriceheight25"><td colspan = "3">&nbsp;</td></tr>
										</table>
									</form>';
		$ret					.= load_fiche_titre('<span class = "infrascuspTitleparam">'.$langs->trans('InfraSCusPParamHistoryUpdates').'</span>', '', $listUpD, 1);
		$sxe					= infrascusp_getChangelogFile($appliname);
		$sxelast				= infrascusp_getChangelogFile($appliname, 'dwn');
		$tblversionslast		= is_object($sxelast) ? $sxelast->Version : array();
		if ($resVersion == -1) {
			foreach ($tblversions as $error) {
				$ret	.= $error->message;
			}
			return $ret;
		}
		if (getDolGlobalString('INFRAS_SKIP_CHECKVERSION', '')) {
			$dwnbutton	= $dwn ? $langs->trans('InfraSCusPParamSkipCheck') : '';
		} else {
			$dwnbutton	= $dwn ? '<button class = "button infrascuspricewidth180" type = "submit" value = "dwnChangelog" name = "action" title = "'.$langs->trans('InfraSCusPParamCheckNewVersionTitle').'">'.$langs->trans('InfraSCusPParamCheckNewVersion').'</button>' : '';
		}
		$ret	.= '			<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
									<input type = "hidden" name = "token" value = "'.newToken().'">
									<table class = "infrascuspricenoborder centpercent" >
										<tr class = "liste_titre">
														<th class = "center width100">'.$langs->trans('InfraSCusPParamNumberVersion').'</th>
														<th class = "center width100">'.$langs->trans('InfraSCusPParamMonthVersion').'</th>
														<th class = "left" >'.$langs->trans('InfraSCusPParamChangesVersion').'</th>
											<th class = "center width200">'.$dwnbutton.'</th>
										</tr>';
		if (is_object($sxe) && count($tblversionslast) > count($tblversions)) {	// il y a du nouveau
			for ($i = count($tblversionslast)-1; $i >= 0; $i--) {
				$sxePath		= $sxe->xpath('//Version[@Number="'.$tblversionslast[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversionslast[$i]->change;
				$ret			.= '	<tr class = "oddeven">
											<td class = "center valigntop '.(empty($sxePath) ? 'infrascuspbgorange' : '').'">'.$tblversionslast[$i]->attributes()->Number.'</td>
											<td class = "center valigntop '.(empty($sxePath) ? 'infrascuspbgorange' : '').'">'.$tblversionslast[$i]->attributes()->MonthVersion.'</td>
											<td class = "left valigntop infrascuspricenopaddingvert '.(empty($sxePath) ? 'infrascuspbgorange' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infrascuspCaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrascuspgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrascuspblue';
					} else {
						$classcolor	= ' infrascuspblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infrascuspchangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infrascuspchangelogbase'.$classcolor.'">'.$changeline.'</td>
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
											<td class = "center valigntop '.(empty($sxelastPath) ? 'infrascuspbggreen infrascuspblack' : '').'">'.$tblversions[$i]->attributes()->Number.'</td>
											<td class = "center valigntop '.(empty($sxelastPath) ? 'infrascuspbggreen infrascuspblack' : '').'">'.$tblversions[$i]->attributes()->MonthVersion.'</td>
											<td class = "left valigntop infrascuspricenopaddingvert '.(empty($sxelastPath) ? 'infrascuspbggreen' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infrascuspCaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrascuspgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrascuspblue';
					} else {
						$classcolor	= ' infrascuspblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infrascuspchangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infrascuspchangelogbase'.$classcolor.'">'.$changeline.'</td>
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
											<td class = "left valigntop infrascuspricenopaddingvert" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' infrascuspCaution';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' infrascuspgreen';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' infrascuspblue';
					} else {
						$classcolor	= ' infrascuspblack';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 infrascuspchangelogbase'.$classcolor.'">'.$changeline->attributes()->type.'</td>
														<td class = "infrascuspchangelogbase'.$classcolor.'">'.$changeline.'</td>
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
	 * @param   string	$currentversion	current version from changelog
	 * @return	string					HTML presentation
	**/
	function infrascusp_getSupportInformation($currentversion)
	{
		global $db, $langs;

		$ret	= '	<table class = "infrascuspricenoborder" >
						<tr class = "liste_titre">
							<th class = "center width400">'.$langs->trans('InfraSCusPSupportInformation').'</th>
							<th class = "center">'.$langs->trans('Value').'</th>
						</tr>
						<tr class="oddeven">
							<td class = "width400 infrascuspchangelogbase">'.$langs->trans('DolibarrVersion').'</td>
							<td class = "infrascuspchangelogbase">'.DOL_VERSION.'</td>
						</tr>';
		if (getDolGlobalString('DOLINFRAS_VERSION', '')) {
			$ret	.= '<tr class = "oddeven">
							<td class = "width400 infrascuspchangelogbase">'.$langs->trans('InfraSCusPriceParamDolinfrasVersion').'</td>
							<td class = "infrascuspchangelogbase">'.getDolGlobalString('DOLINFRAS_VERSION', '').'</td>
						</tr>';
		}
		$ret	.= '	<tr class="oddeven">
							<td class = "width400 infrascuspchangelogbase">'.$langs->trans('ModuleVersion').'</td>
							<td class = "infrascuspchangelogbase">'.$currentversion.'</td>
						</tr>
						<tr class="oddeven">
							<td class = "width400 infrascuspchangelogbase">'.$langs->trans('PHPVersion').'</td>
							<td class = "infrascuspchangelogbase">'.version_php().'</td>
						</tr>
						<tr class="oddeven">
							<td class = "width400 infrascuspchangelogbase">'.$langs->trans('DatabaseVersion').'</td>
							<td class = "infrascuspchangelogbase">'.$db::LABEL.' '.$db->getVersion().'</td>
						</tr>
						<tr class="oddeven">
							<td class = "width400 infrascuspchangelogbase">'.$langs->trans('WebServerVersion').'</td>
							<td class = "infrascuspchangelogbase">'.dol_escape_htmltag($_SERVER['SERVER_SOFTWARE']).'</td>
						</tr>
						<tr><td colspan = "3" class = "infrascuspFinal">&nbsp;</td></tr>
					</table>
				<br/>';
		return $ret;
	}
