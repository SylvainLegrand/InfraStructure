<?php
	/************************************************
	* Copyright (C) 2026	Fallinah Ranasolonirina	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./dolinfras/core/lib/dolinfrasAdmin.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by DolInfraS module
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
	*	Test if the PHP extension 'XML' is loaded
	*
	**/
	function dolinfras_test_php_ext()
	{
		global $db, $conf, $langs;

		$langs->load('dolinfras@dolinfras');

		if (extension_loaded('xml')) {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	1, 'chaine', 0, 'DolInfraS module', $conf->entity);
		} else {
			dolibarr_set_const($db, 'INFRAS_PHP_EXT_XML',	-1, 'chaine', 0, 'DolInfraS module', $conf->entity);
			setEventMessages('<span class = "dolinfrasCaution">'.$langs->trans('DolInfraSCautionMess').'</span>'.$langs->trans('InfraSXMLextError'), array(), 'warnings');
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
	function dolinfras_getLocalVersionMinDoli($appliname)
	{
		global $langs;

		$currentversion	= array();
		$sxe			= dolinfras_getChangelogFile($appliname);
		if (is_object($sxe)) {
			$currentversion[0]	= (string) $sxe->Version[count($sxe->Version) - 1]->attributes()->Number;
			$currentversion[1]	= (string) $sxe->Dolibarr->attributes()->minVersion;
			$currentversion[2]	= 0;
			$currentversion[3]	= $sxe->Version;
			$currentversion[4]	= (string) $sxe->Dolibarr->attributes()->maxVersion;
			$currentversion[5]	= (string) $sxe->PHP->attributes()->minVersion;
			$currentversion[6]	= (string) $sxe->PHP->attributes()->maxVersion;
		} else {
			$currentversion[0]	= '<span class = "dolinfrasCaution"><b>'.$langs->trans('DolInfraSChangelogXMLError').'</b></span>';
			$currentversion[1]	= $langs->trans('DolInfraSnoMinDolVersion');
			$currentversion[2]	= -1;
			$currentversion[3]	= $langs->trans('DolInfraSChangelogXMLError');
			$currentversion[4]	= $langs->trans('DolInfraSnoMaxDolVersion');
			$currentversion[5]	= $langs->trans('DolInfraSnoMinDolVersion');
			$currentversion[6]	= $langs->trans('DolInfraSnoMaxDolVersion');
			foreach (libxml_get_errors() as $error) {
				$currentversion[3]	.= $error->message;
				dol_syslog('dolinfras.Lib::dolinfras_getLocalVersionMinDoli error->message = '.$error->message);
			}
		}
		return $currentversion;
	}

	/**
	* Read the Dolibarr VERSION file and store its value in the DOLINFRAS_VERSION constant
	*
	* @return	void
	**/
	function dolinfras_getVersionDolinfras()
	{
		global $db, $conf;

		$version	= '';
		$file		= DOL_DOCUMENT_ROOT.'/VERSION';
		if (is_file($file)) {
			$version = trim(file_get_contents($file));
		}
		if (!empty($version)) {
			dolibarr_set_const($db, 'DOLINFRAS_VERSION', $version, 'chaine', 0, 'DolInfraS module', $conf->entity);
			$family	= '<span class = "dolinfraspuentedolibarr">Dolibarr</span> LTS by <span class = "dolinfrasneuropolinfras"> InfraS</span>';
			dolibarr_set_const($db, 'DOLINFRAS_FAMILY', $family, 'chaine', 0, 'DolInfraS module', $conf->entity);
		}
	}

	/**
	* Function called to check module name from local changelog
	* Control of the min version of Dolibarr needed and get versions list
	*
	* @param	string			$appliname	module name
	* @param	string			$from		sufixe name to separate inner changelog from download
	* @return	string|boolean				changelog file contents or false
	**/
	function dolinfras_getChangelogFile($appliname, $from = '')
	{
		$file	= empty($from) ? dol_buildpath($appliname, 0).'/docs/changelog.xml' : DOL_DATA_ROOT.'/'.$appliname.'/changelogdwn.xml';
		if (is_file($file)) {
			libxml_use_internal_errors(true);
			$changelog	= @file_get_contents($file);
			$sxe		= @simplexml_load_string(rtrim($changelog), 'SimpleXMLElement', LIBXML_NONET);
			dol_syslog('dolinfras.Lib::dolinfras_getChangelogFile appliname = '.$appliname.' from = '.$from.' changelog = '.($changelog ? 'Ok' : 'KO').' sxe = '.($sxe ? 'Ok' : 'KO'));
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
	function dolinfras_dwnChangelog($appliname)
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
	function dolinfras_getChangeLog($appliname, $version, $resVersion, $tblversions, $dwn = 0)
	{
		global $langs, $user;

		$langs->loadLangs(array('admin', 'errors', 'dolinfras@dolinfras'));

		$supportURL				= 'https://support.infras.fr/create_ticket.php';
		$headerPath				= dol_buildpath('/'.$appliname.'/img/InfraSheader.png', 1);
		$logoPath				= dol_buildpath('/'.$appliname.'/img/InfraS.png', 1);
		$logoDolistorePath		= dol_buildpath('/'.$appliname.'/img/dolistore_logo.png', 1);
		$preferedPartnerPath	= dol_buildpath('/'.$appliname.'/img/Dolibarr_preferred_partner.png', 1);
		$listUpD				= dol_buildpath('/'.$appliname.'/img/list.png', 1);
		$urlInfraS				= 'https://infras.fr';
		$urlWiki				= 'https://wiki.infras.fr/books/'.$appliname;
		$urlstore				= 'https://infras.store/';
		$urlDoli				= 'https://www.dolistore.com/index.php?controller=search&orderby=position&orderway=desc&website=marketplace&search_query=InfraS';
		$InputCarac				= 'class = "button dolinfraswidth180 dolinfrasheight32" name = "readmore" type = "button"';
		$supportvalue			= '/******************************'.'<br/>';
		$supportvalue			.= ' * Module : '.$langs->trans('modcomnameDolinfraS').'<br/>';
		$supportvalue			.= ' * Module version : '.dol_escape_htmltag($version).'<br/>';
		$supportvalue			.= ' * Dolibarr version : '.DOL_VERSION.'<br/>';
		$supportvalue			.= ' * PHP version : '.PHP_VERSION.'<br/>';
		$supportvalue			.= ' ******************************/'.'<br/>';
		$supportvalue			.= 'Description de votre demande :'.'<br/>';
		$ret					= '	<form id = "ticket" method = "POST" target = "_blank" action = "'.$supportURL.'">
										<input name = message type = "hidden" value = "'.$supportvalue.'" />
										<input name = email type = "hidden" value = "'.dol_escape_htmltag($user->email).'" />
										<input name = category_code type = "hidden" value = "'.(strtoupper($langs->trans('modcomnameDolinfraS'))).'" />
										<table class = "centpercent" style = "padding: 10px; background: url('.$headerPath.'); background-size: cover;">
											<tr class = "dolinfrasheight75">
												<td colspan = "3" class = "center bold valignmiddle">
													<a href = "'.$urlWiki.'" target = "_blank">
														<span class = "dolinfrascolor" style = "font-size: 24px;">'.$langs->trans('DolInfraSParamPresent1').'<span class = "dolinfrasneuropolinfras"> InfraS</span>'.$langs->trans('DolInfraSParamPresent2').'</span>
													</a>
												</td>
											</tr>
											<tr class = "dolinfrasheight50">
												<td rowspan = "3" class = "left bold valignbottom dolinfraswidthtrentepercent dolinfrasslogan" style = "color: white; font-size: 16px;">
													<a href = "'.$urlInfraS.'" target = "_blank"><img class = "dolinfrasnoborder dolinfraswidth220" src = "'.$logoPath.'"></a>
													<br/>&nbsp;&nbsp;'.$langs->trans('DolInfraSParamSlogan').'
												</td>
												<td class = "center valignmiddle dolinfraswidthtrentepercent">
													<a href = "'.$urlstore.'" target = "_blank"><input '.$InputCarac.' value = "'.$langs->trans('DolInfraSParamLienModules').'" /></a>
													<button class = "button dolinfraswidth180 dolinfrasheight32" type = "submit" >'.$langs->trans('DolInfraSParamSupport').'</button>
												</td>
												<td rowspan = "3" class = "right bold valignbottom dolinfraswidthtrentepercent dolinfrasslogan">
													<a href = "'.$urlDoli.'" target = "_blank"><img class = "dolinfrasnoborder dolinfraswidth270" src = "'.$logoDolistorePath.'"></a>&nbsp;&nbsp;
													<br/>'.$langs->trans('DolInfraSParamMoreModulesLink').'&nbsp;&nbsp;
												</td>
											</tr>
											<tr>
												<td class = "center valignbottom dolinfrasminwidth700imp">
													<img class = "dolinfrasnoborder dolinfraswidth220 dolinfrasmargintop10imp" src="'.$preferedPartnerPath.'"/>
												</td>
											</tr>
											<tr>
												<td class = "center bold valignbottom dolinfrasminwidth700imp dolinfrasslogan">
													<div class = "dolinfrasmargintop10imp">'.$langs->trans('DolInfraSParamPreferedPartner1').'<span class = "dolinfraspuentedolibarr"> Dolibarr </span>'.$langs->trans('DolInfraSParamPreferedPartner2').'</div>
												</td>
											</tr>
											<tr class = "dolinfrasheight25"><td colspan = "3">&nbsp;</td></tr>
										</table>
									</form>';
		$ret					.= load_fiche_titre('<span class = "dolinfrastitleparam">'.$langs->trans('DolInfraSParamHistoryUpdates').'</span>', '', $listUpD, 1);
		$sxe					= dolinfras_getChangelogFile($appliname);
		$sxelast				= dolinfras_getChangelogFile($appliname, 'dwn');
		$tblversionslast		= is_object($sxelast) ? $sxelast->Version : array();
		if ($resVersion == -1) {
			foreach ($tblversions as $error) {
				$ret	.= $error->message;
			}
			return $ret;
		}
		if (getDolGlobalString('INFRAS_SKIP_CHECKVERSION', '')) {
			$dwnbutton	= $dwn ? $langs->trans('DolInfraSParamSkipCheck') : '';
		} else {
			$dwnbutton	= $dwn ? '<button class = "button" style = "width: 190px; padding: 3px 0px;" type = "submit" value = "dwnChangelog" name = "action" title = "'.$langs->trans('DolInfraSParamCheckNewVersionTitle').'">'.$langs->trans('DolInfraSParamCheckNewVersion').'</button>' : '';
		}
		$ret	.= '			<form action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" method = "post" enctype = "multipart/form-data">
									<input type = "hidden" name = "token" value = "'.newToken().'">
									<table class = "dolinfrasnoborder centpercent" >
										<tr class = "liste_titre">
											<th class = "center width100">'.$langs->trans('DolInfraSParamNumberVersion').'</th>
											<th class = "center width100">'.$langs->trans('DolInfraSParamMonthVersion').'</th>
											<th class = "left" >'.$langs->trans('DolInfraSParamChangesVersion').'</th>
											<th class = "center width200">'.$dwnbutton.'</th>
										</tr>';
		if (is_object($sxe) && count($tblversionslast) > count($tblversions)) {	// il y a du nouveau
			for ($i = count($tblversionslast)-1; $i >= 0; $i--) {
				$sxePath		= $sxe->xpath('//Version[@Number="'.$tblversionslast[$i]->attributes()->Number.'"]');
				$lineversion	= $tblversionslast[$i]->change;
				$ret			.= '	<tr class = "oddeven">
											<td class = "center valigntop '.(empty($sxePath) ? 'dolinfrasbgorange' : '').'">'.dol_escape_htmltag($tblversionslast[$i]->attributes()->Number).'</td>
											<td class = "center valigntop '.(empty($sxePath) ? 'dolinfrasbgorange' : '').'">'.dol_escape_htmltag($tblversionslast[$i]->attributes()->MonthVersion).'</td>
											<td class = "left valigntop dolinfrasnopaddingvert '.(empty($sxePath) ? 'dolinfrasbgorange' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' dolinfraschangefix';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' dolinfraschangeadd';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' dolinfraschangechg';
					} else {
						$classcolor	= ' dolinfraschangedefault';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 dolinfraschangelogbase'.$classcolor.'">'.dol_escape_htmltag($changeline->attributes()->type).'</td>
														<td class = "dolinfraschangelogbase'.$classcolor.'">'.dol_escape_htmltag($changeline).'</td>
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
											<td class = "center valigntop '.(empty($sxelastPath) ? 'dolinfrasbggreen' : '').'">' .dol_escape_htmltag($tblversions[$i]->attributes()->Number).'</td>
											<td class = "center valigntop '.(empty($sxelastPath) ? 'dolinfrasbggreen' : '').'">' .dol_escape_htmltag($tblversions[$i]->attributes()->MonthVersion).'</td>
											<td class = "left valigntop dolinfrasnopaddingvert '.(empty($sxelastPath) ? 'dolinfrasbggreen' : '').'" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' dolinfraschangefix';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' dolinfraschangeadd';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' dolinfraschangechg';
					} else {
						$classcolor	= ' dolinfraschangedefault';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 dolinfraschangelogbase'.$classcolor.'">'.dol_escape_htmltag($changeline->attributes()->type).'</td>
														<td class = "dolinfraschangelogbase'.$classcolor.'">'.dol_escape_htmltag($changeline).'</td>
													</tr>
												</table>';
				}
				$ret	.= '				</td>
										</tr>';
			}
		} else {	// on est à jour des versions ou pas de connection internet
			for ($i = count($tblversions)-1; $i >= 0; $i--) {
				$lineversion	= $tblversions[$i]->change;
				$ret	.= '			<tr class = "oddeven">
											<td class = "center valigntop">'.dol_escape_htmltag($tblversions[$i]->attributes()->Number).'</td>
											<td class = "center valigntop">'.dol_escape_htmltag($tblversions[$i]->attributes()->MonthVersion).'</td>
											<td class = "left valigntop dolinfrasnopaddingvert" colspan = "2">';
				foreach ($lineversion as $changeline) {
					if ($changeline->attributes()->type == 'fix') {
						$classcolor	= ' dolinfraschangefix';
					} elseif ($changeline->attributes()->type == 'add') {
						$classcolor	= ' dolinfraschangeadd';
					} elseif ($changeline->attributes()->type == 'chg') {
						$classcolor	= ' dolinfraschangechg';
					} else {
						$classcolor	= ' dolinfraschangedefault';
					}
					$ret	.= '				<table>
													<tr>
														<td class = "width50 dolinfraschangelogbase'.$classcolor.'">'.dol_escape_htmltag($changeline->attributes()->type).'</td>
														<td class = "dolinfraschangelogbase'.$classcolor.'">'.dol_escape_htmltag($changeline).'</td>
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
