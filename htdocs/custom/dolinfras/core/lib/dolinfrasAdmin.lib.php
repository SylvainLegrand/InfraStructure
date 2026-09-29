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
	* Define head array for setup pages tabs
	*
	* @return	array			list of head
	**/
	function dolinfras_admin_prepare_head()
	{
		global $langs, $conf, $user;

		$h		= 0;
		$head	= array();
		if (!empty($user->admin)) {
			$head[$h][0]	= dol_buildpath('/dolinfras/admin/dolinfrassetup.php', 1);
			$head[$h][1]	= $langs->trans('DolInfraSParams');
			$head[$h][2]	= 'dolinfrassetup';
		}
		complete_head_from_modules($conf, $langs, null, $head, $h, 'dolinfras_admin');
		$h++;
		$head[$h][0]	= dol_buildpath('/dolinfras/admin/about.php', 1);
		$head[$h][1]	= $langs->trans('About');
		$head[$h][2]	= 'about';
		$h++;
		$head[$h][0]	= dol_buildpath('/dolinfras/admin/changelog.php', 1);
		$head[$h][1]	= $langs->trans('DolInfraSParamsChangelog');
		$head[$h][2]	= 'changelog';
		complete_head_from_modules($conf, $langs, null, $head, $h, 'dolinfras_admin', 'remove');
		return $head;
	}

	/**
	*	Lit le fichier sql/data.sql du module et retourne la liste des constantes LTS
	*
	*	@param		string	$appliname	module name
	*	@return		array				list of constants : name => array('value', 'visible', 'note')
	**/
	function dolinfras_get_lts_constants($appliname)
	{
		$constants	= array();
		$file		= dol_buildpath('/'.$appliname.'/sql/data.sql', 0);
		if (! is_file($file)) {
			dol_syslog('dolinfrasAdmin.Lib::dolinfras_get_lts_constants file not found = '.$file, LOG_ERR);
			return $constants;
		}
		$content	= file_get_contents($file);
		if ($content === false) {
			dol_syslog('dolinfrasAdmin.Lib::dolinfras_get_lts_constants unable to read file = '.$file, LOG_ERR);
			return $constants;
		}
		if (preg_match_all('/^insert ignore into llx_const \(name, entity, value, type, visible, note\) values \(\'([A-Za-z0-9_]+)\',\s+__ENTITY__, \'(.*)\',\s+\'chaine\', ([01]), \'(.*)\'\);$/m', $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				if (isset($constants[$match[1]])) {	// La première déclaration du fichier fait foi
					continue;
				}
				$constants[$match[1]]	= array('value' => $match[2], 'visible' => (int) $match[3], 'note' => $match[4]);
			}
		}
		return $constants;
	}

	/**
	*	Retourne le type d'option à afficher dans la page de paramétrage pour une constante LTS
	*
	*	@param		string	$confkey		constant name
	*	@param		string	$defaultvalue	default value of the constant (from data.sql)
	*	@return		string					on_off, lts_value, input, number, textarea, select_language or select_featureslevel
	**/
	function dolinfras_get_lts_param_type($confkey, $defaultvalue)
	{
		$specifictypes	= array(
								'CRON_WARNING_DELAY_HOURS'				=> 'number',
								'FCKEDITOR_ENABLE_SCAYT_LANG'			=> 'select_language',
								'MAIN_FEATURES_LEVEL'					=> 'select_featureslevel',
								'MAIN_HTML_FOOTER'						=> 'lts_value',
								'MAIN_MOTD'								=> 'textarea',
								'MAIN_SECURITY_MAXFILESIZE_DOWNLOADED'	=> 'number',
								'MAIN_UPLOAD_DOC'						=> 'number',
								);
		if (isset($specifictypes[$confkey])) {
			return $specifictypes[$confkey];
		}
		if ($defaultvalue === '0' || $defaultvalue === '1') {
			return 'on_off';
		}
		return 'input';
	}

	/**
	*	Retourne le lien HTML de bascule On / Off d'une constante LTS (sans ajax pour conserver les colonnes visible et note)
	*
	*	@param		string	$confkey	constant name
	*	@param		string	$valueon	value to set when the option is turned on
	*	@return		string				HTML link
	**/
	function dolinfras_lts_onoff_link($confkey, $valueon = '1')
	{
		global $langs;

		$currentvalue	= (string) getDolGlobalString($confkey, '');
		$isactive		= $valueon === '1' ? ($currentvalue !== '' && $currentvalue !== '0') : ($currentvalue === $valueon);
		return '<a class = "inline-block" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action='.($isactive ? 'del_' : 'set_').$confkey.'&token='.newToken().'#'.$confkey.'">'.img_picto($langs->trans($isactive ? 'Enabled' : 'Disabled'), $isactive ? 'switch_on' : 'switch_off').'</a>';
	}

	/**
	*	Force la valeur des constantes du fichier sql/data.sql dans la base de données (entité courante)
	*
	*	@param		string	$appliname	module name
	*	@return		int					number of constants applied or -1 if KO
	**/
	function dolinfras_force_lts_constants($appliname)
	{
		global $db, $conf;

		$constants	= dolinfras_get_lts_constants($appliname);
		if (empty($constants)) {
			return -1;
		}
		$nbapplied	= 0;
		$db->begin();
		foreach ($constants as $confkey => $constant) {
			$result	= dolibarr_set_const($db, $confkey, $constant['value'], 'chaine', $constant['visible'], $constant['note'], $conf->entity);
			if ($result < 0) {
				$db->rollback();
				dol_syslog('dolinfrasAdmin.Lib::dolinfras_force_lts_constants error on constant = '.$confkey, LOG_ERR);
				return -1;
			}
			$nbapplied++;
		}
		$db->commit();
		dol_syslog('dolinfrasAdmin.Lib::dolinfras_force_lts_constants nbapplied = '.$nbapplied);
		return $nbapplied;
	}

	/**
	*	Retourne la liste des modules à activer à l'installation de Dolibarr selon la marque de l'instance (contenu du fichier htdocs/BRAND)
	*	Chaque module est désigné par le nom de la classe de son descripteur : module natif (ex : modStock) ou externe (ex : modinfraspackplus)
	*	Marque vide ou sans liste propre : liste par défaut (modules activés auparavant par $force_install_module du modèle install.forced.php)
	*
	*	@param		string	$brand	brand name (content of the htdocs/BRAND file, may be empty)
	*	@return		array	list of module descriptor class names
	**/
	function dolinfras_get_brand_modules($brand)
	{
		$defaultmodules	= array('modSociete',
								'modProduct',
								'modService',
								'modProjet',
								'modBanque',
								'modPropale',
								'modCommande',
								'modFournisseur',
								'modReception',
								'modFacture',
								'modTax',
								'modMargin',
								'modAccounting',
								'modECM',
								'modFckeditor',
								'modCategorie',
								'modBookmark',
								'modWorkflow',
								'modImport',
								'modExport',
								'modCron',
								'modAgenda',
								'modinfraspackplus',
								'modinfrasdiscount',
								'modinfrassearch',
								'modinfrascusprice',
								'modInfrastructure',
								'modAdresseFrance',
								'modListExportImport',
								'modEInvoicing',
								'modOblyon',
								'modDbadmin',
								'modScrollTo'
								);
		// Liste complète propre à une marque ('marque' => array('modXxx', ...))
		$brandmodules	= array('keaticerpbtp'				=> array('modinfrasproject', 'modinfrastechinfos', 'modSirene', 'modExtraitCompteClient', 'modStock'),
								'keaticerpartisans'			=> array('modinfrasproject', 'modinfrastechinfos', 'modSirene', 'modExtraitCompteClient'),
								'keaticerpfsm'				=> array('modFicheinter', 'modContrat', 'modTicket', 'modKnowledgeManagement', 'modPrelevement', 'modStock'),
								'keaticerpdistributeurs'	=> array('modExpedition', 'modSupplierProposal', 'modPaymentByBankTransfer', 'modPrelevement', 'modStock', 'modBarcode'),
								'keaticerpmanufacturing'	=> array('modBom', 'modMrp', 'modProductBatch', 'modStock', ),
								'keaticerpesn'				=> array('modFicheinter', 'modSupplierProposal', 'modPaymentByBankTransfer', 'modPrelevement', 'modBarcode', 'modWebsite'),
								'keaticerpevents'			=> array('modEventOrganization', 'modResource')
								);
		if ($brand !== '' && isset($brandmodules[$brand])) {
			return array_values(array_unique(array_merge($defaultmodules, $brandmodules[$brand])));
		}
		return $defaultmodules;
	}

	/**
	*	Active, s'ils sont installés, les modules de la marque de l'instance (fichier htdocs/BRAND) ou ceux de la liste par défaut (marque vide ou sans liste propre)
	*	Appelée par moddolinfras::init() pendant l'installation de Dolibarr
	*
	*	@return		int		number of modules activated (dependencies included)
	**/
	function dolinfras_activate_brand_modules()
	{
		global $conf;

		$brandfile	= DOL_DOCUMENT_ROOT.'/BRAND';
		$brand		= is_readable($brandfile) ? trim((string) file_get_contents($brandfile)) : '';
		$modules	= dolinfras_get_brand_modules($brand);
		// Pendant l'installation, les racines n'ont pas les clés 'main' / 'altN' de l'exécution normale (master.inc.php) : dolGetModulesDirs() omet alors
		// core/modules et activateModule() ne trouve ni les modules natifs ni leurs dépendances. Clés rétablies le temps de l'activation
		$documentroots	= $conf->file->dol_document_root;
		if (!isset($documentroots['main'])) {
			$roots							= array_values($documentroots);
			$conf->file->dol_document_root	= array('main' => (string) array_shift($roots));
			foreach ($roots as $i => $dirroot) {
				$conf->file->dol_document_root['alt'.$i]	= (string) $dirroot;
			}
		}
		$modulesdir		= dolGetModulesDirs();
		$nbactivated	= 0;
		foreach ($modules as $modname) {
			$modfile	= '';
			foreach ($modulesdir as $dir) {
				if (is_readable($dir.$modname.'.class.php')) {
					$modfile	= $dir.$modname.'.class.php';
					break;
				}
			}
			if (empty($modfile)) {	// Module non installé : activateModule() provoquerait une erreur fatale (classe introuvable)
				dol_syslog('dolinfrasAdmin.Lib::dolinfras_activate_brand_modules module not installed = '.$modname.' brand = '.$brand, LOG_WARNING);
				continue;
			}
			$result	= activateModule($modname);
			if (!empty($result['errors'])) {
				dol_syslog('dolinfrasAdmin.Lib::dolinfras_activate_brand_modules error on module = '.$modname.' : '.implode(', ', $result['errors']), LOG_ERR);
			}
			$nbactivated	+= (int) $result['nbmodules'];
		}
		$conf->file->dol_document_root	= $documentroots;
		dol_syslog('dolinfrasAdmin.Lib::dolinfras_activate_brand_modules brand = '.$brand.' nbactivated = '.$nbactivated);
		return $nbactivated;
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
	function dolinfras_load_title($titre, $morehtmlright = '', $picto = 'generic', $pictoisfullpath = 0, $id = '', $morecssontable = '', $morehtmlcenter = '')
	{
		$out	= '';
		if ($picto == 'setup')	{
			$picto	= 'generic';
		}
		$out	.= '<table '.(!empty($id) ? 'id = "'.$id.'" ' : '').'class = "centpercent notopnoleftnoright table-fiche-title'.(!empty($morecssontable) ? ' '.$morecssontable : '').'">
											<tr class = "liste_titre">';
		if (!empty($picto)) {
			$out .= '							<td class = "dolinfrasnoborder dolinfrasnopadding widthpictotitle valignmiddle col-picto">'.img_picto('', $picto, 'class = "valignmiddle dolinfraswidthpictotitle pictotitle"', $pictoisfullpath).'</td>';
		}
		$out	.= '							<td class = "dolinfrasnoborder dolinfrasnopadding valignmiddle col-title"><div class = "dolinfrasDivTitre uppercase inline-block">'.$titre.'</div></td>';
		if (dol_strlen($morehtmlcenter)) {
			$out .= '							<td class = "dolinfrasnoborder dolinfrasnopadding center valignmiddle">'.$morehtmlcenter.'</td>';
		}
		if (dol_strlen($morehtmlright)) {
			$out .= '							<td class = "dolinfrasnoborder dolinfrasnopadding titre_right wordbreakimp right valignmiddle">'.$morehtmlright.'</td>';
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
	function dolinfras_print_colgroup($metas = array())
	{
		print '	<tr>';
		foreach ($metas as $values)	{
			print '<td class = "dolinfrasFinal dolinfrasnopadding"'.($values == '*' ? '' : ' width = "'.$values.'"').' style =" height: 1px;'.($values == '*' ? '' : ' max-width: '.$values.'; min-width: '.$values.'; width: '.$values.';').'">&nbsp;</td>';
		}
		print '	</tr>';
	}

	/**
	*	Print HTML title for admin page
	*
	*	@param		array		$metas	list of col value
	*	@return		void
	**/
	function dolinfras_print_liste_titre($metas = array())
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
	*	@param		string		$action			action value posted on the 'action' parameter
	*	@param		string		$desc			Description of action (writes on the first line)
	*	@param		int			$cs1			first colspan
	*	@param		string		$alignclass		Class used to align the description
	*	@param		string		$lbl			button label (translate key)
	*	@param		boolean		$noRowspan		don't use rowspan attribute
	*	@param		int			$num			Add a numbering column first with this number
	*	@return		int							line number for next option
	**/
	function dolinfras_print_btn_action($action, $desc = '', $cs1 = 3, $alignclass = 'center', $lbl = 'Modify', $noRowspan = false, $num = 0)
	{
		global $langs;

		print '	<tr'.(!empty($num) ? ' class = "oddeven"' : '').'>';
		if (!empty($num)) {
			print '	<td class = "center bold">'.$num.'</td>';
			$num++;
		}
		print '		<td colspan = "'.$cs1.'" class = "'.$alignclass.'">'.$desc.'</td>
					<td'.(empty($noRowspan) ? ' rowspan = "0"' : '').' class = "center valigntop"><button class = "button" type = "submit" value = "'.$action.'" name = "action">'.$langs->trans($lbl).'</button></td>
				</tr>';
		return $num;
	}

	/**
	*	Print HTML HR line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function dolinfras_print_hr($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'"><hr class = "dolinfrasHR"></td></tr>';
	}

	/**
	*	Print HTML subtitle line
	*
	*	@param		int			$cs1		first colspan
	*	@param		string		$subtitle	subtitle or translation key for subtitle
	*	@return		void
	**/
	function dolinfras_print_subTitle($cs1 = 3, $subtitle = '')
	{
		global $langs;

		dolinfras_print_hr($cs1);
		print '	<tr>
					<td colspan = "'.$cs1.'" class = "center"><span class = "dolinfrassubtitleparam">'.$langs->trans($subtitle).'</span></td>
				</tr>';
	}

	/**
	*	Print HTML final line
	*
	*	@param		int			$cs1		first colspan
	*	@return		void
	**/
	function dolinfras_print_final($cs1 = 3)
	{
		print '	<tr><td colspan = "'.$cs1.'" class = "dolinfrasFinal">&nbsp;</td></tr>';
	}

	/**
	*	Print HTML input line for admin page
	*
	*	@param		string			$confkey	constant name
	*	@param		string			$tag		input type (on_off button, input, textarea, select (from Dolibarr functions), editor)
	*	@param		string			$desc		Description of action
	*	@param		string			$help		Help description => active tooltip
	*	@param		array|string	$metas		list of HTML parameters and values (example : 'type'=>'text' and/or 'class'=>'flat center', etc...) or HTML select for the 'select' tag
	*	@param		int				$cs1		first colspan
	*	@param		int				$cs2		second colspan => we add it with $cs1 in case off textarea
	*	@param		string			$end		if input element string to be added after or empty td to finish the line
	*	@param		int				$num		Add a numbering column first with this number
	*	@return		int							line number for next option
	**/
	function dolinfras_print_input($confkey, $tag = 'on_off', $desc = '', $help = '', $metas = '', $cs1 = 2, $cs2 = 1, $end = '', $num = 0)
	{
		global $langs, $conf, $db;

		$form	= new Form($db);
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
			$params	= '';
			if (!empty($metas) && is_array($metas)) {
				foreach ($metas as $key => $value) {
					$params	.= '&'.$key.'='.$value;
				}
			}
			print '		<a href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=set_'.$confkey.$params.'&token='.newToken().'&value='.(getDolGlobalString($confkey, '') ? '0' : '1').'">';
			print ajax_constantonoff($confkey);
			print '		</a>';
		} elseif ($tag == 'input') {
			// management of the minimum value of number type input fields
			$inputValue	= getDolGlobalString($confkey, '');
			if (!empty($metas['type']) && $metas['type'] == 'number' && !empty($metas['min'])) {
				$currentValue	= getDolGlobalInt($confkey, $metas['min']);
				$inputValue		= $currentValue < $metas['min'] ? $metas['min'] : $currentValue;
			}
			// default input
			$defaultMetas	= array('type' => 'text', 'class' => 'flat quatrevingtpercent dolinfrasnopadding', 'style' => 'font-size: inherit;', 'name' => $confkey, 'id' => $confkey, 'value' => $inputValue);
			$metas			= array_merge ($defaultMetas, $metas);
			$metascompil	= '';
			foreach ($metas as $key => $value) {
				$metascompil	.= ' '.$key.($key == 'enabled' || $key == 'disabled' ? '' : ' = "'.$value.'"');
			}
			print '	<'.$tag.' '.$metascompil.'>'.(!preg_match('/<td(.*)/', $end, $reg) ? $end : '');
		} elseif ($tag == 'textarea') {
			print '<textarea name = "'.$confkey.'" class = "flat" cols = "120">'.getDolGlobalString($confkey, '').'</textarea>';
		} elseif ($tag == 'select') {
			print $metas;
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
													<span class = "dolinfrascolor" style = "font-size: 24px;">'.$langs->trans('DolInfraSParamPresent1').'<span class = "dolinfrasneuropolinfras"> InfraS</span>'.$langs->trans('DolInfraSParamPresent2').'</span>
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
