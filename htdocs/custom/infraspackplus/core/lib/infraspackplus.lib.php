<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand 		- <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Fallinah Ranasolonirina 	- <contact@infras.fr>	InfraS - <https://www.infras.fr>
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

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
	include_once DOL_DOCUMENT_ROOT.'/delivery/class/delivery.class.php';
	include_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	include_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	include_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
	include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
	include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infraspackplus/class/address.class.php');
	dol_include_once('/infraspackplus/core/lib/infraspackplus.pdf.lib.php');


	/**
	*	Get all files for special head
	*
	*	@param	array				$modelslist		List of model where we may find a special head
	*	@param	string				$modelfile		suffix used to apply a special head only for certain models, among all those intended for the same type of document
	*	@return	integer | string					0 if Ko or no special header wanted, otherwise the name used for the special header
	**/
	function infraspackplus_fetchAllSpecialHeads($modelslist = [], $modelfile = '')
	{
		global $conf, $db, $langs;

		$specialHead	= array('rootFileName' => '', 'frameinfos' => 0);
		if (getDolGlobalString('INFRASPLUS_PDF_SPE_HEAD', '')) {
			$rootFileName	= getDolGlobalString('INFRASPLUS_PDF_SPE_HEAD', '');
			$filefound		= '';
			$dirmodels		= array('/');
			$modelhead		= '';
			$errors			= 0;
			if (empty($modelslist)) {
				$modelslist	= array('commande',
									'contract',
									'delivery',
									'expedition',
									'expensereport',
									'facture',
									'fichinter',
									'product',
									'project',
									'propal',
									'reception',
									'societe',
									'supplier_invoice',
									'supplier_order',
									'supplier_proposal'
									);
			}
			if (is_array($conf->modules_parts['models'])) {
				$dirmodels	= array_merge($dirmodels, $conf->modules_parts['models']);
			}
			foreach ($dirmodels as $reldir) {
				foreach ($modelslist as $modelspath) {
					if ($modelspath == 'propal') {
						$modelspath = 'propale';
					}
					if ($modelspath == 'shipping') {
						$modelspath = 'expedition';
					}
					if ($modelspath == 'order_supplier') {
						$modelspath = 'supplier_order';
					}
					if ($modelspath == 'invoice_supplier') {
						$modelspath = 'supplier_invoice';
					}
					$filename	= $rootFileName.'_'.$modelspath.$modelfile.'.pdf.head.php';
					$file		= dol_buildpath($reldir.'core/modules/'.$modelspath.'/doc/'.$filename, 0);	// we search for special head linked to a specific type of document (propal, order or invoice, etc...)
					dol_syslog('fetchAllSpecialHeads modelspath = '.$modelspath.' reldir = '.$reldir.' file = '.$file);
					if (file_exists($file)) {
						dol_syslog('fetchAllSpecialHeads Exist modelspath = '.$modelspath.' reldir = '.$reldir.' file = '.$file);
						$modelhead	= $modelspath;
						$filefound	= $file;
						break;
					}
					if (!empty($filefound)) {
						break;
					}
				}
				if (empty($filefound)) {
					$file	= dol_buildpath($reldir.'core/modules/specialhead/doc/'.$rootFileName.'.pdf.head.php', 0);	// we search for special head linked to all types of document
					if (file_exists($file)) {
						dol_syslog('fetchAllSpecialHeads Exist on appliname reldir = '.$reldir.' file = '.$file);
						$filefound	= $file;
						break;
					}
				}
			}
			if (empty($filefound)) {
				if (empty($modelslist)) {
					setEventMessage($langs->trans('InfraSPlusParamSpecialHeadError', $filefound), 'errors');
					if (empty($modelfile)) {
						dolibarr_set_const($db, 'INFRASPLUS_PDF_SPE_HEAD', '', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
					}
				}
			} else {
				include_once $filefound;
				$rootFileName	.= !empty($modelhead) ? '_'.$modelhead : '';
				$functionList	= array ('pdf_'.$rootFileName.'_pagehead', 'pdf_'.$rootFileName.'_writeAddresses', 'pdf_'.$rootFileName.'_writeFrame', 'pdf_'.$rootFileName.'_getAddresses');
				foreach ($functionList as $function) {
					if (! function_exists($function)) {
						setEventMessage($langs->trans('InfraSPlusParamSpecialHeadFunctionError', $function, $filefound), 'errors');
						if (empty($modelfile)) {
							dolibarr_set_const($db, 'INFRASPLUS_PDF_SPE_HEAD', '', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
						}
						$errors++;
					}
				}
				if (empty($errors)) {
					$specialHead['rootFileName']	= $rootFileName;
				}
				if (function_exists('pdf_'.$rootFileName.'_frameinfos')) {
					$specialHead['frameinfos']	= 1;
				}
			}
		}
		return $specialHead;
	}

	/**
	*	Get all files for special Foot
	*
	*	@param	array				$modelslist		List of model where we may find a special Foot
	*	@param	string				$modelfile		suffix used to apply a special Foot only for certain models, among all those intended for the same type of document
	*	@return	integer | string					0 if Ko or no special Footer wanted, otherwise the name used for the special Footer
	**/
	function infraspackplus_fetchAllSpecialFooters($modelslist = [], $modelfile = '')
	{
		global $conf, $db, $langs;

		$specialFoot	= array('rootFileName' => '', 'frameinfos' => 0);
		if (getDolGlobalString('INFRASPLUS_PDF_SPE_FOOT', '')) {
			$rootFileName	= getDolGlobalString('INFRASPLUS_PDF_SPE_FOOT', '');
			$filefound		= '';
			$dirmodels		= array('/');
			$modelFoot		= '';
			$errors			= 0;
			if (empty($modelslist)) {
				$modelslist	= array('commande',
									'contract',
									'delivery',
									'expedition',
									'expensereport',
									'facture',
									'fichinter',
									'product',
									'project',
									'propal',
									'reception',
									'societe',
									'supplier_invoice',
									'supplier_order',
									'supplier_proposal'
									);
			}
			if (is_array($conf->modules_parts['models'])) {
				$dirmodels	= array_merge($dirmodels, $conf->modules_parts['models']);
			}
			foreach ($dirmodels as $reldir) {
				foreach ($modelslist as $modelspath) {
					if ($modelspath == 'propal') {
						$modelspath = 'propale';
					}
					if ($modelspath == 'shipping') {
						$modelspath = 'expedition';
					}
					if ($modelspath == 'order_supplier') {
						$modelspath = 'supplier_order';
					}
					if ($modelspath == 'invoice_supplier') {
						$modelspath = 'supplier_invoice';
					}
					$filename	= $rootFileName.'_'.$modelspath.$modelfile.'.pdf.foot.php';
					$file		= dol_buildpath($reldir.'core/modules/'.$modelspath.'/doc/'.$filename, 0);	// we search for special Foot linked to a specific type of document (propal, order or invoice, etc...)
					dol_syslog('fetchAllSpecialFooters modelspath = '.$modelspath.' reldir = '.$reldir.' file = '.$file);
					if (file_exists($file)) {
						dol_syslog('fetchAllSpecialFooters Exist modelspath = '.$modelspath.' reldir = '.$reldir.' file = '.$file);
						$modelFoot	= $modelspath;
						$filefound	= $file;
						break;
					}
					if (!empty($filefound)) {
						break;
					}
				}
				if (empty($filefound)) {
					$file	= dol_buildpath($reldir.'core/modules/specialfoot/doc/'.$rootFileName.'.pdf.foot.php', 0);	// we search for special Foot linked to all types of document
					if (file_exists($file)) {
						dol_syslog('fetchAllSpecialFooters Exist on appliname reldir = '.$reldir.' file = '.$file);
						$filefound	= $file;
						break;
					}
				}
			}
			if (empty($filefound)) {
				if (empty($modelslist)) {
					setEventMessage($langs->trans('InfraSPlusParamSpecialFootError', $filefound), 'errors');
					if (empty($modelfile)) {
						dolibarr_set_const($db, 'INFRASPLUS_PDF_SPE_FOOT', '', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
					}
				}
			} else {
				include_once $filefound;
				$rootFileName	.= !empty($modelFoot) ? '_'.$modelFoot : '';
				$functionList	= array ('pdf_'.$rootFileName.'_pagefoot');
				foreach ($functionList as $function) {
					if (! function_exists($function)) {
						setEventMessage($langs->trans('InfraSPlusParamSpecialFootFunctionError', $function, $filefound), 'errors');
						if (empty($modelfile)) {
							dolibarr_set_const($db, 'INFRASPLUS_PDF_SPE_FOOT', '', 'chaine', 0, 'InfraSPackPlus module', $conf->entity);
						}
						$errors++;
					}
				}
				if (empty($errors)) {
					$specialFoot['rootFileName']	= $rootFileName;
				}
				if (function_exists('pdf_'.$rootFileName.'_frameinfos')) {
					$specialFoot['frameinfos']	= 1;
				}
			}
		}
		return $specialFoot;
	}

	/**
	*	Tests présence de champs supplémentaires dans les tables.
	*
	*	@param	string	$appliname	module name
	**/
	function infraspackplus_test_new_fields($appliname)
	{
		dol_syslog('infraspackplus.Lib::infraspackplus_test_new_fields appliname = '.$appliname);
		infraspackplus_get_test_new_fields($appliname, 'infraspackplus_societe_address', 'email');
		infraspackplus_get_test_new_fields($appliname, 'infraspackplus_societe_address', 'url');
		infraspackplus_get_test_new_fields($appliname, 'societe', 'logo_emet');
	}

	/**
	*	Tests présence d'un champ supplémentaire dans une table.
	*	Exécution du script de création si besoin.
	*
	*	@param	string	$appliname	module name
	*	@param	string	$table		simple table name without any prefix
	*	@param	string	$field		field name
	**/
	function infraspackplus_get_test_new_fields($appliname, $table, $field)
	{
		global $conf, $db;

		$path			= dol_buildpath($appliname, 0).'/sql';
		$sql_column		= 'SHOW COLUMNS FROM '.$db->prefix().$table.' LIKE "'.$field.'"';
		$result_columns	= $db->query($sql_column);
		dol_syslog('infraspackplus.Lib::infraspackplus_get_test_new_fields sql_column = '.$sql_column);
		if (empty($db->num_rows($result_columns))) {
			$filetable	= $path.'/llx_'.$table.'-'.$field.'.sql';
			$result		= run_sql($filetable, 1, 0, 1);
			dol_syslog('infraspackplus.Lib::infraspackplus_get_test_new_fields filetable = '.$filetable.' result = '.$result);
		}
		$db->free($result_columns);
	}

	/**
	*	Tests présence d'une table.
	*
	*	@param		string	$table		simple table name without any prefix
	*	@return		int					<0 KO | 0 no table found | >1 Ok
	**/
	function infraspackplus_test_tables($table)
	{
		global $db;

		$sql_tables		= 'SHOW TABLES LIKE "'.$db->prefix().$table.'"';
		$result_tables	= $db->query($sql_tables);
		dol_syslog('infraspackplus.Lib::infraspackplus_test_tables sql_tables = '.$sql_tables);
		if (!empty($result_tables)) {
			$num	= $db->num_rows($result_tables);
			$db->free($result_tables);
			return empty($num) ? 0 : 1;
		} else {
			dol_print_error($db);
			return -1;
		}
	}

	/**
	*	Modifie le fichier actions_milestone.class.php
	*	pour le rendre compatible avec le module
	*
	*	@param		string		$module_name	module name where is the file to change
	*	@param		string		$relFile		file name (with relative path from module name) to change
	*	@param		string		$tSearch		type of search 'F' => file 'S' => string
	*	@param		string		$search			string to search or name of the file containing this string
	*	@param		string		$replace		name of the file containing the string to use for replacement
	*	@param		string		$tReg			type of replacement 'F' => from file 'R' => from RegEx
	*	@param		string		$reg			string to replace or name of the file containing this string
	*	@return		void
	**/
	function infraspackplus_test_module($module_name, $relFile, $tSearch, $search, $replace, $tReg, $reg)
	{
		global $conf, $langs;

		if (!getDolGlobalString('INFRASPACKPLUS_DISABLED_MODULE_CHANGE', '')) {
			$path			= dol_buildpath($module_name, 0);
			$mypath			= dol_buildpath('infraspackplus', 0);
			dol_syslog('infraspackplus.lib::infraspackplus_test_module path = '.$path.' PHP_OS = '.PHP_OS);
			$fileactions	= $path.$relFile;
			$i				= substr ($search, 0, 1) == substr ($reg, 0, 1) && preg_match('/^\d/', $search) ? '.'.substr ($search, 0, 1) : '';
			$search			= $tSearch == 'F' ? file_get_contents ($mypath.'/includes/'.$module_name.'/'.$search) : $search;
			$filereplace	= $mypath.'/includes/'.$module_name.'/'.$replace;
			$reg			= $tReg == 'F' ? file_get_contents ($mypath.'/includes/'.$module_name.'/'.$reg) : $reg;
			$actions		= strpos (file_get_contents ($fileactions), $search) === false ? file_get_contents ($fileactions) : false;
			if ($actions !== false && is_file($filereplace)) {
				$moved	= dol_copy($fileactions, $fileactions.'.old'.$i);
				dol_syslog('infraspackplus.lib::infraspackplus_test_module fileactions = '.$fileactions.' moved = '.$moved);
				if ($moved > 0) {
					if ($tReg == 'R') {
						$result	= file_put_contents ($fileactions, preg_replace ($reg, file_get_contents ($filereplace), $actions));
					}
					if ($tReg == 'F') {
						$result	= file_put_contents ($fileactions, str_replace ($reg, file_get_contents ($filereplace), $actions));
					}
				} else {
					$result	= false;
				}
				if ($result	=== false) {
					setEventMessages('<FONT color = "red">'.$langs->trans('InfraSPlusCautionMess').'</FONT>'.$langs->trans('InfraSPlusModuleFileError', $module_name), null, 'errors');
				}
			}
		}
	}

	/**
	*	Check line type from external module ?
	*
	*	@param		object		$line			line we work on
	*	@param		string		$element		line object element (for special case like shipping)
	*	@param		string		$searchName		module name we look for
	*	@return		boolean						true if the line is a special one and was created by the module we ask for
	**/
	function infraspackplus_isLineFromExternalModule ($line, $element, $searchName)
	{
		global $db;

		if ($element == 'shipping' || $element == 'delivery') {
			$fk_origin_line	= $line->fk_origin_line;
			$line			= new OrderLine($db);
			$line->fetch($fk_origin_line);
		}
		if (!empty($line) && $line->product_type == 9 && $line->special_code == infraspackplus_get_mod_number($searchName)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	*	Find module number
	*
	*	@param		string		$modName		module name we look for
	*	@return		integer						-1 if KO, 0 not found or module number if Ok
	**/
	function infraspackplus_get_mod_number ($modName)
	{
		global $db;

		if (class_exists($modName)) {
			$objMod	= new $modName($db);
			return $objMod->numero;
		}
		return 0;
	}

	/**
	*	Find the title link to subtotal
	*
	*	@param		object		$object			Object we work on
	*	@param		object		$currentLine	Line we work on
	*	@param		integer		$modNumber		External module ID used
	*	@return		string						label or description of the title found
	**/
	function infraspackplus_getTitle($object, $currentLine, $modNumber)
	{
		$res	= '';
		foreach ($object->lines as $line) {
			if ($line->id == $currentLine->id) {
				break;
			}
			$qty_search	= 100 - $currentLine->qty;
			if ($line->product_type == 9 && $line->special_code == $modNumber && $line->qty == $qty_search) {
				$res = ($line->label) ? $line->label : (($line->description) ? $line->description : $line->desc);
			}
		}
		return $res;
	}

	/**
	*	Change directory name for Dolibarr 12
	*
	*	@param		string		$oldname	path with folder name to change
	*	@param		string		$newname	path with the new folder name
	*	@return		void
	**/
	function infraspackplus_chg_dir_name ($oldname, $newname)
	{
		if (dol_is_dir($oldname)) {
			$resultCopy	= dolCopyDir($oldname, $newname, 0, 1);
			dol_syslog('infraspackplus.lib::infraspackplus_chg_dir_name oldname = '.$oldname.' newname = '.$newname.' resultCopy = '.$resultCopy);
			if ($resultCopy > 0) {
				dol_delete_dir_recursive($oldname);
			}
		}
	}

	/**
	*	Liste de fichier de conditions générales suivant le type recherché
	*
	*	@param	string	$type	type of file (CGV, CGI or CGA)
	*	@param	integer	$entity	current entity for multicompany
	*	@param	object	$object	object we work on
	*	@return	array			array of file name found for the type wanted
	**/
	function infraspackplus_get_CGfiles ($type, $entity, $object = null)
	{
		global $conf, $db;

		$CGFromPro		= getDolGlobalString('INFRASPLUS_PDF_CGV_FROM_PRO', '') ? 1 : 0;
		$labelFromPro	= empty($CGFromPro) ? '' : getDolGlobalString('INFRASPLUS_PDF_CGV_FROM_PRO_LABEL', '');
		$myCompDir		= !empty($conf->mycompany->multidir_output[$entity]) ? $conf->mycompany->multidir_output[$entity] : $conf->mycompany->dir_output;
		$CGs			= [];
		$labelToSearch	= !empty($labelFromPro) && is_object($object) ? ($object->thirdparty->typent_code == $labelFromPro ? $labelFromPro : '') : '';
		if (glob($myCompDir.'/'.$type.'_*.pdf')) {
			foreach (glob($myCompDir.'/'.$type.'_*'.$labelToSearch.'*.pdf') as $file)	$CGs[]	= dol_basename($file);
		}
		if (!empty($CGFromPro) && empty($labelToSearch) && !empty($labelFromPro) && !empty($CGs)) {
			$exclude	= [];
			foreach (glob($myCompDir.'/'.$type.'_*'.$labelFromPro.'*.pdf') as $file)	$exclude[]	= dol_basename($file);
			$CGs		= array_diff($CGs, $exclude);
		}
		return $CGs;
	}

	/**
	*	Recherche d'un fichier contenant un code langue dans son nom à partir d'une liste
	*
	*	@param	array	$CGs		list of file name to check
	*	@param	string	$searchLang	langage code search
	*	@return	string				file name found
	**/
	function infraspackplus_get_CGfiles_lang ($CGs, $searchLang)
	{
		for ($i = 0; $i < count($CGs); $i++) {
			$langCG	= explode('.', $CGs[$i]);
			$langCG	= $langCG[count($langCG) - 2];
			if ($langCG == $searchLang) {
				return $CGs[$i];
			}
		}
		return '';
	}

	/**
	*	Conversion de fichier ttf en police TCPDF.
	*
	*	@param	string		$type			Font type. Leave empty for autodetect mode.
	*										Valid values are:
	*											TrueTypeUnicode
	*											TrueType
	*											Type1
	*											CID0JP	= CID-0	Japanese
	*											CID0KR	= CID-0	Korean
	*											CID0CS	= CID-0	Chinese Simplified
	*											CID0CT	= CID-0	Chinese Traditional
	*	@param	string		$enc			Name of the encoding table to use.
	*										Leave empty for default mode. Omit this parameter for TrueType Unicode and symbolic font like Symbol or ZapfDingBats.
	*	@param	int			$flags			Unsigned 32-bit integer containing flags specifying various characteristics of the font
	*										(PDF32000:2008 - 9.8.2 Font Descriptor Flags):
	*											+1 for fixed font;
	*											+4 for symbol;
	*											+32 for non-symbol;
	*											+64 for italic.
	*											Fixed and Italic mode are generally autodetected so you have to set it to 32 = non-symbolic font (default) or 4 = symbolic font.
	*	@param	string		$outpath		Output path for generated font files (must be writeable by the web server). Leave empty for default font folder.
	* 	@param	string		$platid			Platform ID for CMAP table to extract
	*										(when building a Unicode font for Windows this value should be 3, for Macintosh should be 1).
	* 	@param	int			$encid			Encoding ID for CMAP table to extract
	*										(when building a Unicode font for Windows this value should be 1, for Macintosh should be 0).
	*										When Platform ID is 3, legal values for Encoding ID are:
	*											0	= Symbol
	*											1	= Unicode
	*											2	= ShiftJIS
	*											3	= PRC
	*											4	= Big5
	*											5	= Wansung
	*											6	= Johab
	*											7	= Reserved
	*											8	= Reserved
	*											9	= Reserved
	*											10	= UCS-4
	* 	@param	boolean		$addcbbox		Includes the character bounding box information on the php font file.
	* 	@param	boolean		$link			Link to system font instead of copying the font data # (not transportable) - Note: do not work with Type1 font.
	* 	@param	string		$font			input font file.
	*	@return	string						Return Font name or false if error.
	**/
	function infraspackplus_Add_TCPDF_Font($type = '', $enc = '', $flags = 32, $outpath, $platid = 3, $encid = 1, $addcbbox = false, $link = false, $font)
	{
		global $conf;

		if (!defined('K_PATH_FONTS')) {
			define('K_PATH_FONTS', DOL_DATA_ROOT.'/'.(!isModEnabled('multicompany') || $conf->entity == 1 ? '' : $conf->entity.'/').'infraspackplus/fonts/');
		}
		include_once TCPDF_PATH.'tcpdf.php';
		include_once TCPDF_PATH.'include/tcpdf_fonts.php';
		$options	= [];
		$typefont	= array('TrueTypeUnicode', 'TrueType', 'Type1', 'CID0JP', 'CID0KR', 'CID0CS', 'CID0CT');
		if (in_array($type, $typefont)) {
			$options['type']	= $type;
		} else {
			$options['type']	= '';
		}
		$options['enc']		= $enc;
		$options['flags']	= intval($flags);
		$options['outpath']	= realpath($outpath);
		if (substr($options['outpath'], -1) != '/') {
			$options['outpath']	.= '/';
		}
		$options['platid']		= min(max(1, intval($platid)), 3);
		$options['encid']		= min(max(0, intval($encid)), 10);
		$options['addcbbox']	= $addcbbox;
		$options['link']		= $link;
		$fontfile				= realpath($font);
		$fontname				= TCPDF_FONTS::addTTFfont($fontfile, $options['type'], $options['enc'], $options['flags'], $options['outpath'], $options['platid'], $options['encid'], $options['addcbbox'], $options['link']);
		return $fontname;
	}

	/**
	* Function called to check Logo files associate to customer
	*
	* @param	string	$socid	societe Id to check
	* @return	string			logo file name
	**/
	function infraspackplus_getLogoEmet($socid)
	{
		global $conf, $db;

		$logo_emet			= '';
		$sql_logo_emet		= 'SELECT s.logo_emet';
		$sql_logo_emet		.= ' FROM '.$db->prefix().'societe AS s';
		$sql_logo_emet		.= ' WHERE s.rowid = '.((int) $socid);
		$result_logo_emet	= $db->query($sql_logo_emet);
		if (!empty($result_logo_emet)) {
			$obj_logo_emet	= $db->fetch_object($result_logo_emet);
			$logo_emet		= $obj_logo_emet->logo_emet;
		}
		$db->free($result_logo_emet);
		return $logo_emet;
	}
	/**
	* Function called to update Logo files associate to customer
	*
	* @param	string	$socid	societe Id to update
	* @param	string	$logo	File name to update
	* @return	string			0 if OK SQL error else
	**/
	function infraspackplus_setLogoEmet($socid, $logo)
	{
		global $conf, $db;

		$sql_upt	= 'UPDATE '.$db->prefix().'societe';
		$sql_upt	.= ' SET logo_emet = "'.$logo.'"';
		$sql_upt	.= ' WHERE rowid = '.((int) $socid);
		$result_upt	= $db->query($sql_upt);
		if (!empty($result_upt)) {
			$db->free($result_upt);
			return 0;
		} else {
			return $db->error().' sql = '.$sql_upt;
		}
	}

	/**
	* Function called to generate PDF of bordereau de cheque with specific model for infraspackplus module
	* @param	object		$object			Object we work on
	* @param	string		$model			Model to use for PDF generation
	* @param	object		$outputlangs	Object Langs to use for PDF generation
	* @return	string						File name of generated PDF or empty string if KO
	*/
	function infraspackplus_bc_generatePdf($object, $model, $outputlangs)
	{
		global $conf;

		if (empty($model)) {
			$model = 'blochet';
		}
		$customfile = dol_buildpath('/infraspackplus/core/modules/cheque/doc/pdf_'.$model.'.modules.php', 0);
		if (!file_exists($customfile)) {
			return $object->generatePdf($model, $outputlangs);	// modèle natif (blochet, ...)
		}
		require_once $customfile;
		$classname = 'pdf_'.$model;
		$docmodel = new $classname($object->db);

		return $docmodel->write_file($object, $conf->bank->dir_output.'/checkdeposits', $object->ref, $outputlangs);
	}
	/**
	*	Return list of mention
	*
	*	@param	string			$dict			SQL table name
	*	@param	string			$selected		Preselected type
	*	@param	string			$htmlname		Name of field in html form
	* 	@param	int				$showempty		Add an empty field
	*	@param	string			$onChange		JavaScript for onchange event
	*	@param	int				$hasLabel		Show label before select
	*	@param	string			$filter			MySQL filter (example : 'code LIKE "TVA\_%"')
	*	@param	int				$needArray		Ask for an array instead of a html string
	*	@return	string | array					Select html tag with all mention labels found
	**/
	function select_infraspackplus_dict($dict, $selected = '', $htmlname = 'fk_infraspackplus_dict', $showempty = 0, $onChange = '', $hasLabel = 1, $filter = '', $needArray = 0)
	{
		global $db, $conf, $langs;

		$typeDict	= ucfirst(explode('_', $dict)[2]);
		$result		= '';
		$sql		= 'SELECT rowid, code, libelle';
		$sql		.= ' FROM '.$db->prefix().$dict;
		$sql		.= ' WHERE active = 1 AND entity = '.((int) $conf->entity);
		$sql		.= !empty($filter) ? ' AND '.$filter : '';
		$sql		.= ' ORDER BY pos ASC';
		$resql		= $db->query($sql);
		dol_syslog('infraspackplus.Lib::select_infraspackplus_dict sql = '.$sql);
		if (!empty($resql)) {
			$num	= $db->num_rows($resql);
			$i		= 0;
			if ($needArray) {
				$result	= [];
				while ($obj = $db->fetch_object($resql)) {
					if (getDolGlobalString('PROPOSAL_FREE_TEXT_'.$obj->code, '') && getDolGlobalString('INVOICE_FREE_TEXT_'.$obj->code, '')) {
						$result[$obj->code]	= $obj->libelle;
					}
				}
				return $result;
			}
			if (!empty($num)) {
				$result	.= $hasLabel ? '&nbsp;'.$langs->trans('InfraSPlusParam'.$typeDict.'3').'&nbsp;' : '';
				$result	.= '<select class = "flat minwidth300 maxwidth400" name="'.$htmlname.'"'.($onChange ? ' onchange = "'.$onChange.';"' : '').'>';
				if (!empty($showempty)) {
					$result	.= '<option value = "-1"';
					if ($selected == -1) {
						$result	.= ' selected = "selected"';
					}
					$result	.= '>&nbsp;</option>';
				}
				while ($i < $num) {
					$obj		= $db->fetch_object($resql);
					$libelle	= ($langs->trans('InfraSPlusDict'.$typeDict.'s'.$obj->code) != ('InfraSPlusDict'.$typeDict.'s'.$obj->code) ? $langs->trans('InfraSPlusDict'.$typeDict.'s'.$obj->code) : ($obj->libelle != '-' ? $obj->libelle : ''));
					$result		.= '<option value = "'.$obj->code.'"';
					if ($obj->code == $selected) {
						$result	.= ' selected';
					}
					$result	.= '>'.dol_trunc($libelle, 38, 'middle').'</option>';
					$i++;
				}
				$result	.= '</select>';
			} else {
				$result	.= '<input type = "hidden" name = "'.$htmlname.' id = "'.$htmlname.'" value = -1>';	// si pas de liste, on positionne un hidden vide
			}
		} else {
			$result	.= '<input type = "hidden" name = "'.$htmlname.' id = "'.$htmlname.'" value = -1>';	// si pas de liste, on positionne un hidden vide
		}
		return	$result;
	}

	/**
	*	Modify payment methode according to an old parameter.
	*
	*	@return	int				1 = Ok -1 = Ko
	**/
	function infraspackplus_modify_paiement_spec()
	{
		global $db, $conf;

		$idPaySpec	= getDolGlobalString('INFRASPLUS_PDF_PAY_SPEC', '');
		dol_syslog('infraspackplus.Lib::infraspackplus_modify_paiement_spec idPaySpec = '.$idPaySpec);
		if (!empty($idPaySpec)) {
			$sqldict	= 'UPDATE '.$db->prefix().'c_paiement SET type = 3';
			$sqldict	.= ' WHERE id = '.((int) $idPaySpec).' AND entity = '.((int) $conf->entity);
			$resqldict	= $db->query($sqldict);
			if (!empty($resqldict)) {
				$result	= dolibarr_del_const($db, 'INFRASPLUS_PDF_PAY_SPEC', $conf->entity);
				return $result;
			}
		}
		return -1;
	}

	/**
	*	Create a new PDF to show what the chosen font looks like
	*
	*	@return		string		1 = Ok or 0 = Ko
	**/
	function infraspackplus_test_font()
	{
		global $conf, $langs;

		$formatarray		= pdf_InfraSPlus_getFormat();
		$page_largeur		= $formatarray['width'];
		$page_hauteur		= $formatarray['height'];
		$format				= array($page_largeur, $page_hauteur);
		$main_umask			= getDolGlobalString('MAIN_UMASK', '0755');
		$font				= getDolGlobalString('INFRASPLUS_PDF_FONT', 'Helvetica');
		$dir				= $conf->ecm->dir_output.'/temp/';
		$file				= $dir.'TEST.pdf';
		if (! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				setEventMessages($langs->trans('ErrorCanNotCreateDir', $dir), null, 'errors');
				return 0;
			}
		}
		if (file_exists($dir)) {
			// Create pdf instance
			$pdf				= pdf_InfraSPlus_getInstance($format, 'mm', 'L');
			$default_font_size	= pdf_getPDFFontSize($langs);	// Must be after pdf_getInstance
			$pdf->SetAutoPageBreak(1, 0);
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont($font, '', 14);	// set font
			$tagvs						= array('p' => array(1 => array('h' => 0.0001, 'n' => 1)), 'ul' => array(0 => array('h' => 0.0001, 'n' => 1)));
			$pdf->setHtmlVSpace($tagvs);
			$pdf->Open();
			// set document information
			$pdf->SetTitle('InfraSPackPlus test font');
			$pdf->SetSubject('InfraSPackPlus');
			$pdf->SetCreator('Dolibarr '.DOL_VERSION);
			$pdf->SetAuthor('InfraS - Sylvain Legrand');
			$pdf->SetKeywords('InfraS, InfraSPack, InfraSPackPlus, PDF, example, test, guide');
			$pdf->SetMargins(10, 10, 10);	// Left, Top, Right
			$pdf->AddPage();	// add a page
			$txt						= "Font : ".$font."<br/>Test :<ul><li>Normal<ul><li>&nbsp;&nbsp;a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</li><li>&nbsp;&nbsp;0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</li></ul></li><li><b>Gras</b><ul><li>&nbsp;&nbsp;<b>a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</b></li><li>&nbsp;&nbsp;<b>0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</b></li></ul></li><li><em>Italique<em><ul><li>&nbsp;&nbsp;<em>a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</em></li><li>&nbsp;&nbsp;<em>0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</em></li></ul></li><li><b>Gras Italique</b><ul><li>&nbsp;&nbsp;<em><b>a b c d e f g h i j k l m n o p q r s t u v w x y z A B C D E F G H I J K L M N O P Q R S T U V W X Y Z</b></em></li><li>&nbsp;&nbsp;<em><b>0 1 2 3 4 5 6 7 8 9 + - * = ° ² é è à ù ç â ê î ô û ä ë ï ö ü , ; : ! ? . & § % µ @ $ £ € ¤ # | ( ) { } [ ] < > _ ~</b></em></li></ul></li></ul>";	// set some text to print
			$pdf->writeHTMLCell($page_hauteur - 20, $page_largeur - 20, 10, 10, dol_htmlentitiesbr($txt), 0, 1);
			$pdf->Close();
			$pdf->Output($file, 'F');
			if (!empty($main_umask)) {
				@chmod($file, octdec($main_umask));
			}
			return 1;	// Pas d'erreur
		} else {
			setEventMessages($langs->transnoentities('ErrorCanNotCreateDir', $dir), null, 'errors');
			return 0;
		}
	}

	/**
	*	Check if the parent company sould be used
	*
	*	@param		object		$object		Object we want to build document for
	*	@return		object					Object address found
	**/
	function infraspackplus_check_parent_addr_fact ($object)
	{
		global $db, $conf;

		$parent_adrfact	= getDolGlobalInt('INFRASPLUS_PDF_FACTURE_PARENT_ADDR_FACT', 0);
		if (!empty($parent_adrfact) && !empty($object->thirdparty->parent)) {
			$parent	= new Societe($db);
			$parent->fetch($object->thirdparty->parent);
			return $parent;
		} else {
			return $object->thirdparty;
		}
	}

	/**
	*	Check extrafield name
	*
	*	@param		string		$name		Name or code to check
	*	@return		integer					> 0	= Ok
	*										-1	= alphabetical and lower case only error
	*										-2	= reserved keyword error
	*										-3	= length error (less than 3 charaters)
	**/
	function infraspackplus_check_extf_name ($name = '')
	{
		global $langs;

		$langs->load('errors');

		$name	= sanitizeVal($name, 'aZ09');
		if (preg_match('/^[a-z0-9-_]+$/', $name) && !is_numeric($name)) {	// alphabetical and lower case only
			// length must be superior to 3 characters
			if (strlen($name) < 3) {
				setEventMessages($langs->trans('ErrorValueLength', $langs->transnoentitiesnoconv('AttributeCode'), 3), null, 'errors');
				return -3;
			}
			// Check reserved keyword with more than 3 characters
			if (in_array($name, array('and', 'keyword', 'table', 'index', 'int', 'integer', 'float', 'double', 'real', 'position'))) {
				setEventMessages($langs->trans('ErrorReservedKeyword', $name), null, 'errors');
				return -2;
			}
			return 1;
		} else {	// must be alphabetical and lower case only
			setEventMessages($langs->trans('ErrorFieldCanNotContainSpecialNorUpperCharacters', $langs->transnoentities('AttributeCode')), null, 'errors');
			return -1;
		}
	}

	/**
	*	Search an extrafield by name
	*
	*	@param		integer		$set		-2	= delete extrafields
	*										-1	= disable extrafields
	*										0	= check
	*										1	= update or enable
	*										2	= create extrafields
	*	@param		string		$tempName	temporary name of exterafield (used when the $const values are not loaded => init module process)
	*	@param		string		$constKey	Key used to store the name of extrafield
	*	@param		string		$langKey	Translation key used for the extrafield label
	*	@param		array		$listElem	Elements on which the extrafield must be found
	*	@param		array		$listParams	extrafield parameters
	*											'type'
	*											'pos'
	*											'size'
	*											'unique'
	*											'required'
	*											'default_value'
	*											'param'
	*											'alwayseditable'
	*											'perms'
	*											'list'
	*											'help'
	*											'computed'
	*											'entity'
	*											'langfile'
	*											'enabled'
	*											'totalizable'
	*											'printable'
	*	@return		integer || array		array (if $set = 0) = list of element where the extrafield is missing
	*										> 0	= found (we return the number of extrafields found + the number created or the number updated)
	*										0	= not found
	*										-1	= not enough parameters
	*										-2	= on error
	**/
	function infraspackplus_search_extf ($set = 0, $tempName = '', $constKey = '', $langKey = '', $listElem = [], $listParams = [])
	{
		global $db, $conf;

		dol_syslog('infraspackplus_search_extf $set = '.$set.' $tempName = '.$tempName);
		if ((!empty($tempName) || !empty($constKey)) && !empty($langKey) && !empty($listElem)) {
			$name	= getDolGlobalString($constKey, $tempName);
			if (!empty($name)) {
				$sql		= 'SELECT elementtype FROM '.$db->prefix().'extrafields WHERE name LIKE "'.$db->escapeforlike($name).'" AND entity = '.((int) $conf->entity);
				$resql		= $db->query($sql);
				if (!empty($resql)) {
					$num	= $db->num_rows($resql);
					if ($num == 0 && $set == 0) {
						return 0;	// there is no extrafield found and we don't want to create them
					}
					$results	= 0;
					$extra		= new ExtraFields($db);
					if ($set == 2) {	// create
						foreach($listElem as $newElementType) {
							$results	+= $extra->addExtraField($name, $langKey, $listParams['type'], $listParams['pos'], $listParams['size'], $newElementType, $listParams['unique'], $listParams['required'], $listParams['default_value'], $listParams['param'], $listParams['alwayseditable'], $listParams['perms'], $listParams['list'], $listParams['help'], $listParams['computed'], $listParams['entity'], $listParams['langfile'], $listParams['enabled'], $listParams['totalizable'], $listParams['printable']);
						}
						return $results;
					}
					$arr	= [];
					while ($obj = $db->fetch_object($resql)) {
						$arr[]	= $obj->elementtype;
					}
					dol_syslog('infraspackplus_search_extf $arr = '.implode(',', $arr).' $name = '.$name);
					if ($set == 0) {	// check
						$diff	= array_diff($listElem, $arr);
						return !empty($diff) ? $diff : 1;	// return the list of element where this extrafield ($name) is missing or 1 if this extrafield ($name) is found on each element
					}
					if ($set == 1) {	// update or enable
						foreach($arr as $newElementType) {
							$results	+= $extra->update($name, $langKey, $listParams['type'], $listParams['size'], $newElementType, $listParams['unique'], $listParams['required'], $listParams['pos'], $listParams['param'], $listParams['alwayseditable'], $listParams['perms'], $listParams['list'], $listParams['help'], $listParams['default_value'], $listParams['computed'], $listParams['entity'], $listParams['langfile'], $listParams['enabled'], $listParams['totalizable'], $listParams['printable']);
						}
						$diff	= array_diff($listElem, $arr);
						if (!empty($diff)) {	// some extrafields are missing for some types; we need to create the missing elements
							$resdiff	= 0;
							foreach($diff as $newElementType) {
								$resdiff += $extra->addExtraField($name, $langKey, $listParams['type'], $listParams['pos'], $listParams['size'], $newElementType, $listParams['unique'], $listParams['required'], $listParams['default_value'], $listParams['param'], $listParams['alwayseditable'], $listParams['perms'], $listParams['list'], $listParams['help'], $listParams['computed'], $listParams['entity'], $listParams['langfile'], $listParams['enabled'], $listParams['totalizable'], $listParams['printable']);
							}
							return $results + $resdiff;
						}
						return $results;
					}
					if ($set == -1) {	// disable
						foreach($arr as $oldElementType) {
							$results	+= $extra->update($name, $langKey, $listParams['type'], $listParams['size'], $oldElementType, $listParams['unique'], $listParams['required'], $listParams['pos'], $listParams['param'], $listParams['alwayseditable'], $listParams['perms'], $listParams['list'], $listParams['help'], $listParams['default_value'], $listParams['computed'], $listParams['entity'], $listParams['langfile'], $listParams['enabled'], $listParams['totalizable'], $listParams['printable']);
						}
						return $results;	// < 0 if KO, 0 if nothing is done, 1 if OK
					}
					if ($set == -2) {	// delete
						foreach($arr as $oldElementType) {
							$results	+= $extra->delete($name, $oldElementType);
						}
						return $results;	// < 0 if KO, 0 if nothing is done, 1 if OK
					}
				} else {
					return -2;
				}
			}
		}
		return -1;
	}

	/**
	*	Search an extrafield by name
	*
	*	@param		integer		$set		-2	= delete dictionary entry
	*										-1	= disable dictionary entry
	*										1	= update or enable dictionary entry
	*										2	= create dictionary entry
	*	@param		string		$tablename	name of dictionary
	*	@param		string		$code		entry code to work on
	*	@param		string		$langKey	Translation key used for the entry label
	*	@return		integer					> 0	= Ok
	*										-1	= not enough parameters
	*										-2	= on error
	**/
	function infraspackplus_search_dict ($set = 1, $tablename = '', $code = '', $langKey = '')
	{
		global $db, $conf, $langs;

		dol_syslog('infraspackplus_search_dict $set = '.$set.' $tablename = '.$tablename.' $code = '.$code.' $langKey = '.$langKey);
		if (!empty($tablename) && !empty($code) && !empty($langKey)) {
			$resPaySpec	= getDictionaryValue($tablename, 'code', $code, true, 'code');
			if (!empty($resPaySpec)) {
				// delete
				if ($set == -2) {
					$sql	= 'DELETE FROM '.$tablename.' WHERE code LIKE "'.$db->escape($code).'" AND entity = '.(int) $conf->entity;
					$result	= $db->query($sql);
					if (empty($result)) {
						setEventMessages($langs->trans('Error'), dol_print_error($db), 'errors');
						return -2;
					}
				} elseif ($set == -1) {	// disable
					$sql	= 'UPDATE '.$tablename.' SET active = 0 WHERE code LIKE "'.$db->escape($code).'" AND entity = '.(int) $conf->entity;
					$result	= $db->query($sql);
					if (empty($result)) {
						setEventMessages($langs->trans('Error'), dol_print_error($db), 'errors');
						return -2;
					}
				} elseif ($set == 1) {	// update or enable
					$sql	= 'UPDATE '.$tablename.' SET type = 3, libelle = "'.$db->escape($langKey).'", active = 1  WHERE code LIKE "'.$db->escape($code).'" AND entity = '.(int) $conf->entity;
					$result	= $db->query($sql);
					if (empty($result)) {
						setEventMessages($langs->trans('Error'), dol_print_error($db), 'errors');
						return -2;
					}
				} elseif ($set == 2) {	// create ?
					return -2;	// already exist we can't create it
				}
			} else {
				// create
				if ($set == 2) {
					$sql	= 'INSERT INTO '.$tablename.' (entity, code, libelle, type, active, position) VALUES ('.(int) $conf->entity.', "'.$code.'", "'.$db->escape($langKey).'", 3, 1, 0)';
					$result	= $db->query($sql);
					if (empty($result)) {
						setEventMessages($langs->trans('Error'), dol_print_error($db), 'errors');
						return -2;
					}
				} else {
					return -2;	// doesn't exist => error !
				}
			}
			return	1;
		} else {
			return -1;
		}
	}

	/**
	*	Show html area for list of addresses
	*
	*	@param	Societe		$object		Third party object
	*	@param	string		$backtopage	Url to go once address is created
	*	@return	integer					Number of addresses
	**/
	function infraspackplus_show_addresses($object, $backtopage = '')
	{
		global $db, $langs, $user;

		dol_include_once('/infraspackplus/class/address.class.php');

		$langs->load('infraspackplus@infraspackplus');

		$form			= new Form($db);
		$addresses		= new Address($db);
		$num			= $addresses->fetch_lines($object->id);
		$newcardbutton	= '';
		if (!empty($user->hasRight('societe', 'creer'))) {
			$newcardbutton	= '	<a class = "btnTitle btnTitlePlus" href = "'.dol_buildpath('infraspackplus', 1).'/comm/address.php?socid='.$object->id.'&action=create&backtopage='.urlencode($backtopage).'">
									<span class = "fa fa-plus-circle valignmiddle"></span>
								</a>';
		}
		$arrayfields	= array(
			'label'		=> array('label' => $langs->trans('InfraSPlusParamAdressAlias'),	'checked' => 1, 'position' => 10),
			'name'		=> array('label' => $langs->trans('CompanyName'),					'checked' => 1, 'position' => 20),
			'address'	=> array('label' => $langs->trans('Address'),						'checked' => 1, 'position' => 25),
			'town'		=> array('label' => $langs->trans('Town'),							'checked' => 1, 'position' => 30),
			'country'	=> array('label' => $langs->trans('Country'),						'checked' => 1, 'position' => 40),
			'phone'		=> array('label' => $langs->trans('Phone'),						'checked' => 1, 'position' => 50),
			'fax'		=> array('label' => $langs->trans('Fax'),							'checked' => 1, 'position' => 60),
			'email'		=> array('label' => $langs->trans('Email'),						'checked' => 1, 'position' => 70),
			'url'		=> array('label' => $langs->trans('url'),							'checked' => 1, 'position' => 80),
			'note'		=> array('label' => $langs->trans('Note'),							'checked' => 1, 'position' => 90),
		);
		$arrayfields	= dol_sort_array($arrayfields, 'position');
		$selectedfields	= $form->multiSelectArrayWithCheckbox('infraspackplusselectedfields', $arrayfields, 'infraspackplus_addresses', getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));
		$actionLeft		= getDolGlobalInt('MAIN_CHECKBOX_LEFT_COLUMN');
		print load_fiche_titre($langs->trans('AddressesForCompany'), $newcardbutton, '');
		print '			<form id = "form_filter_addresses" method = "GET" action = "#" onsubmit = "return false;">
							<table id = "infraspackplus_addresses_table" class = "noborder" width = "100%">
								<tr class = "liste_titre_filter">';
		if ($actionLeft) {
			print '					<td class = "liste_titre center maxwidthsearch actioncolumn">'.$form->showFilterButtons('left').'</td>';
		}
		foreach ($arrayfields as $fkey => $fval) {
			if (!empty($fval['checked'])) {
				print '				<td class = "liste_titre" data-field = "'.dol_escape_htmltag($fkey).'"><input type = "text" class = "flat width75" oninput = "infraspackplusFilterAddresses();" data-field = "'.dol_escape_htmltag($fkey).'"></td>';
			}
		}
		if (!$actionLeft) {
			print '					<td class = "liste_titre center maxwidthsearch actioncolumn">'.$form->showFilterButtons().'</td>';
		}
		print '					</tr>
								<tr class = "liste_titre">';
		if ($actionLeft) {
			print '					<th class = "center maxwidthsearch actioncolumn">'.$selectedfields.'</th>';
		}
		foreach ($arrayfields as $fkey => $fval) {
			if (!empty($fval['checked'])) {
				print '				<th data-field = "'.dol_escape_htmltag($fkey).'">'.$fval['label'].'</th>';
			}
		}
		if (!$actionLeft) {
			print '					<th class = "center maxwidthsearch actioncolumn">'.$selectedfields.'</th>';
		}
		print '					</tr>';
		if ($num > 0) {
			foreach ($addresses->lines as $address) {
				$addressstatic	= new Address($db);
				$addressstatic->fetch($address->id);
				$img			= picto_from_langcode($address->country_code);
				$actions		= '';
				if (!empty($user->hasRight('societe', 'creer'))) {
					$actions	.= '<a class = "editfielda marginrightonly" href = "'.dol_buildpath('infraspackplus', 1).'/comm/address.php?action=edit&id='.$addressstatic->id.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">'.img_edit().'</a>';
				}
				if (!empty($user->hasRight('societe', 'supprimer'))) {
					$actions	.= '<a class = "reposition" href = "'.dol_buildpath('infraspackplus', 1).'/comm/address.php?action=delete&id='.$addressstatic->id.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">'.img_delete().'</a>';
				}
				$actionCell		= !empty($actions) ? '<td>'.$actions.'</td>' : '<td></td>';
				print '			<tr class = "oddeven infraspackplus_address_row">';
				if ($actionLeft) {
					print $actionCell;
				}
				foreach ($arrayfields as $fkey => $fval) {
					if (empty($fval['checked'])) {
						continue;
					}
					switch ($fkey) {
						case 'label':
							print '	<td data-field = "label">'.$addressstatic->getNomUrl(1, '&backtopage='.urlencode($backtopage)).'</td>';
							break;
						case 'name':
							print '	<td data-field = "name">'.dol_escape_htmltag($addressstatic->name).'</td>';
							break;
						case 'address':
							print '	<td data-field = "address">'.dol_nl2br(dol_escape_htmltag($addressstatic->address, 0, 1)).'</td>';
							break;
						case 'town':
							print '	<td data-field = "town">'.dol_escape_htmltag($addressstatic->town).'</td>';
							break;
						case 'country':
							print '	<td data-field = "country">'.($img ? $img.' ' : '').dol_escape_htmltag($addressstatic->country).'</td>';
							break;
						case 'phone':
							print '	<td data-field = "phone">'.dol_print_phone($addressstatic->phone, $addressstatic->country_code, $addressstatic->id, $object->id, 'AC_TEL').'</td>';
							break;
						case 'fax':
							print '	<td data-field = "fax">'.dol_print_phone($addressstatic->fax, $addressstatic->country_code, $addressstatic->id, $object->id, 'AC_FAX').'</td>';
							break;
						case 'email':
							print '	<td data-field = "email">'.dol_escape_htmltag($addressstatic->email).'</td>';
							break;
						case 'url':
							print '	<td data-field = "url">'.dol_escape_htmltag($addressstatic->url).'</td>';
							break;
						case 'note':
							print '	<td data-field = "note">'.dol_nl2br(dol_escape_htmltag($addressstatic->note, 0, 1)).'</td>';
							break;
					}
				}
				if (!$actionLeft) {
					print $actionCell;
				}
				print '			</tr>';
			}
		}
		print '				</table>
						</form>
						<br />';
		print '			<script type = "text/javascript">
							function infraspackplusFilterAddresses() {
								var table	= document.getElementById(\'infraspackplus_addresses_table\');
								if (!table) return;
								var inputs	= table.querySelectorAll(\'tr.liste_titre_filter input[data-field]\');
								var rows	= table.querySelectorAll(\'tr.infraspackplus_address_row\');
								rows.forEach(function(row) {
									var show	= true;
									inputs.forEach(function(inp) {
										var filter	= inp.value.toLowerCase().trim();
										if (!filter) return;
										var field	= inp.getAttribute(\'data-field\');
										var cell	= row.querySelector(\'td[data-field="\' + field + \'"]\');
										if (!cell || cell.textContent.toLowerCase().indexOf(filter) === -1) {
											show = false;
										}
									});
									row.style.display	= show ? \'\' : \'none\';
								});
							}
							function infraspackplusResetFilterAddresses() {
								var table	= document.getElementById(\'infraspackplus_addresses_table\');
								if (!table) return;
								var inputs	= table.querySelectorAll(\'tr.liste_titre_filter input[data-field]\');
								inputs.forEach(function(inp) { inp.value = \'\'; });
								infraspackplusFilterAddresses();
							}
							(function() {
								var formFilter	= document.getElementById(\'form_filter_addresses\');
								if (!formFilter) return;
								formFilter.addEventListener(\'click\', function(e) {
									var btn	= e.target.closest(\'button.button_removefilter\');
									if (btn) {
										e.preventDefault();
										infraspackplusResetFilterAddresses();
										return;
									}
									btn	= e.target.closest(\'button.button_search\');
									if (btn) {
										e.preventDefault();
										infraspackplusFilterAddresses();
									}
								});
								var table	= document.getElementById(\'infraspackplus_addresses_table\');
								if (!table) return;
								var dropdown	= table.querySelector(\'.multiselectcheckboxinfraspackplusselectedfields\');
								if (!dropdown) return;
								dropdown.addEventListener(\'click\', function(e) {
									var cb	= e.target.closest(\'input[type="checkbox"]\');
									if (!cb) return;
									setTimeout(function() {
										var hidden	= table.querySelector(\'input.infraspackplusselectedfields\');
										if (!hidden) return;
										var data	= new FormData();
										data.append(\'varpage\', \'infraspackplus_addresses\');
										data.append(\'selectedfields\', hidden.value);
										data.append(\'token\', \''.newToken().'\');
										fetch(\''.dol_escape_js(dol_buildpath('/infraspackplus/ajax/save_selectedfields.php', 1)).'\', {
											method: \'POST\',
											body: data,
											credentials: \'same-origin\'
										}).then(function() {
											window.location.reload();
										});
									}, 50);
								});
							})();
						</script>';
		return $num;
	}

	/**
	*	get the Qty already received by order lines
	*
	*	@param	int		$origin_id		Object Origin ID
	*	@return	array					Array of order lines with qty already received or [] if no order lines found
	**/
	function infraspackplus_get_alreadyreceived($origin_id)
	{
		global $db;

		$alreadyreceived	= [];
		if ($origin_id > 0) {
			$sql	= 'SELECT rlb.fk_elementdet, SUM(rlb.qty) AS qty';
			$sql	.= ' FROM '.$db->prefix().'receptiondet_batch AS rlb';
			$sql	.= ' LEFT JOIN '.$db->prefix().'reception AS r ON r.rowid = rlb.fk_reception';
			$sql	.= ' WHERE r.entity IN ('.getEntity('reception').')';
			$sql	.= ' AND rlb.fk_element = '.((int) $origin_id);
			$sql	.= " AND rlb.element_type = 'supplier_order'";
			$sql	.= ' GROUP BY rlb.fk_elementdet';
			dol_syslog('infraspackplus.lib.php::infraspackplus_get_alreadyreceived $sql = '.$sql, LOG_DEBUG);
			$resql	= $db->query($sql);
			if (!empty($resql)) {
				for ($i = 0 ; $i < $db->num_rows($resql) ; $i++) {
					$obj	= $db->fetch_object($resql);
					if (!empty($obj)) {
						$alreadyreceived[$obj->fk_elementdet]	= $obj->qty;
					}
				}
				return $alreadyreceived;
			}
		}
		return [];
	}

	/**
	*	get the list of serial number already received by order lines
	*
	*	@param	int			$origin_id		Object Origin ID
	*	@param	int			$object_id		Object ID
	*	@return	array|int					Array of serial numbers received by order lines or 0 if no serial number found
	**/
	function infraspackplus_get_serialreceived($origin_id, $object_id)
	{
		global $db;

		$serialreceived	= [];
		if ($origin_id > 0 && $object_id > 0) {
			$sql	= 'SELECT rlb.fk_elementdet, rlb.batch, rlb.qty';
			$sql	.= ' FROM '.$db->prefix().'receptiondet_batch AS rlb';
			$sql	.= ' LEFT JOIN '.$db->prefix().'reception AS r ON r.rowid = rlb.fk_reception';
			$sql	.= ' WHERE r.entity IN ('.getEntity('reception').')';
			$sql	.= ' AND rlb.fk_reception = '.((int) $object_id);
			$sql	.= ' AND rlb.fk_element = '.((int) $origin_id);
			$sql	.= " AND rlb.element_type = 'supplier_order'";
			dol_syslog('infraspackplus.lib.php::infraspackplus_get_serialreceived $sql = '.$sql, LOG_DEBUG);
			$resql	= $db->query($sql);
			if (!empty($resql)) {
				for ($i = 0 ; $i < $db->num_rows($resql) ; $i++) {
					$obj	= $db->fetch_object($resql);
					if (!empty($obj)) {
						$serialreceived[$obj->fk_elementdet][$obj->batch]	= $obj->qty;
					}
				}
				return $serialreceived;
			}
		}
		return 0;
	}

	/**
	* Is substitution file
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	bool
	**/
	function infraspackplus_is_substitution_page($path)
	{
		if (strpos($path, 'infraspackplus/substitutionpages/') !== false) {
			return true;
		}
		return false;
	}

    /**
	* Get const name from substitution path
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	string		Substitution url or empty
	**/
	function infraspackplus_get_const_name_from_substitution_path($path)
	{
		$const_name	= 'INFRASPACKPLUS_PS_ACTIVE'.strtoupper(str_replace('/', '_', str_replace('.php', '', $path)));
		return $const_name;
	}

	/**
	* Get substitution url if exist
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	string	substitution url or empty
	**/
	function infraspackplus_get_substitution_url($path)
	{
		$const_name	= infraspackplus_get_const_name_from_substitution_path($path);
		if (getDolGlobalString($const_name, '')) {
			$dolibranch		= explode('.', DOL_VERSION);
			$dolinfras		= getDolGlobalString('EASYA_VERSION', '') || getDolGlobalString('DOLINFRAS_VERSION', '');
			$coreVersion	= 'dlb'.$dolibranch[0].'0x'.($dolinfras ? '-DolInfraS' : '');
			$path_dst		= '/infraspackplus/substitutionpages/'.$coreVersion.$path;
			$real_path_dst	= dol_buildpath($path_dst, 0);
			dol_syslog('infraspackplus.lib.php::infraspackplus_get_substitution_url $path = '.$path.' $real_path_dst = '.$real_path_dst);
			if (file_exists($real_path_dst)) {
				$url_path_dst = dol_buildpath($path_dst, 2);
				return $url_path_dst;
			}
		}
		return '';
	}

	/**
	* Get substitution redirect URL with filtered query params
	*
	* @return	string		Redirect URL or empty string if no redirect needed
	**/
	function infraspackplus_getSubstitutionRedirectUrl()
	{
		$path_src	= preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/i', '', $_SERVER['PHP_SELF']);
		if (infraspackplus_is_substitution_page($path_src)) {
			return '';
		}
		$url	= infraspackplus_get_substitution_url($path_src);
		if (empty($url)) {
			return '';
		}
		// Forward only GET params (not POST which may contain login credentials)
		// Exclude token (CSRF) which is page-specific and would be invalid on redirect target
		$params	= $_GET;
		unset($params['token']);
		$query	= http_build_query($params);
		return $url.(!empty($query) ? '?'.$query : '');
	}

	/**
	* Get list of product in warehouse
	*
	*	@param	int			$id		Object warehouse ID
	*	@return	array|int			List of products in warehouse or -1 if error
	**/
	function infraspackplus_get_list_product_warehouse($id)
	{
		global $db, $conf, $langs;

		$Lines	= [];
		$sql	= 'SELECT p.rowid AS rowid, p.ref AS product_ref, p.label AS produit, p.tobatch, p.fk_product_type AS type, p.pmp AS ppmp, p.price, p.price_ttc, p.entity,';
		$sql	.= ' ps.reel AS qty';
		$sql	.= ' FROM '.$db->prefix().'product_stock AS ps, '.$db->prefix().'product AS p';
		$sql	.= ' WHERE ps.fk_product = p.rowid';
		$sql	.= ' AND ps.reel <> 0'; // We do not show if stock is 0 (no product in this warehouse)
		$sql	.= ' AND ps.fk_entrepot = '.((int) $id);
		$sql	.= $db->order('p.ref', 'ASC');
		$resql	= $db->query($sql);
		if (!empty($resql)) {
			$nblines = $db->num_rows($resql);
			for ($i = 0; $i < $nblines; $i++) {
				$objp	= $db->fetch_object($resql);
				// Multilangs
				if (getDolGlobalString('MAIN_MULTILANGS', '')) { // si l'option est active
					$sqllang	= 'SELECT label FROM '.$db->prefix().'product_lang WHERE fk_product = '.((int) $objp->rowid).' AND lang = "'.$db->escape($langs->getDefaultLang()).'" LIMIT 1';
					$resqllang	= $db->query($sqllang);
					if (!empty($resqllang)) {
						$objplang	= $db->fetch_object($resqllang);
						if ($objplang->label != '') {
							$objp->produit	= $objplang->label;
						}
					}
				}
				$Lines[]	= $objp;
			}
			return $Lines;
		} else {
			dol_print_error($db);
			return -1;
		}
	}

	/**
	* Output a dimension with best unit
	*
	*	@param	float		$dimension			Dimension
	*	@param	int			$unit				Unit scale of dimension (Example: 0=kg, -3=g, -6=mg, 98=ounce, 99=pound, ...)
	*	@param	string		$type				'weight', 'volume', ...
	*	@param	Translate	$outputlangs		Translate language object
	*	@param	int			$round				-1 = non rounding, x = number of decimal
	*	@param	string		$forceunitoutput	'no' or numeric (-3, -6, ...) compared to $unit (In most case, this value is value defined into $conf->global->MAIN_WEIGHT_DEFAULT_UNIT)
	*	@param	int			$use_short_label	1 = Use short label ('g' instead of 'gram'). Short labels are not translated.
	*	@return	string							String to show dimensions
	**/
	function infraspackplus_showDimensionInBestUnit($dimension, $unit, $type, $outputlangs, $round = -1, $forceunitoutput = 'no', $use_short_label = 1)
	{
		if (($forceunitoutput == 'no' && $dimension < 1 / 10000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == -6)) {
			$dimension	= $dimension * 1000000;
			$unit		= $unit - 6;
		} elseif (($forceunitoutput == 'no' && $dimension < 1 / 10 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == -3)) {
			$dimension	= $dimension * 1000;
			$unit		= $unit - 3;
		} elseif (($forceunitoutput == 'no' && $dimension > 100000000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == 6)) {
			$dimension	= $dimension / 1000000;
			$unit		= $unit + 6;
		} elseif (($forceunitoutput == 'no' && $dimension > 100000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == 3)) {
			$dimension	= $dimension / 1000;
			$unit		= $unit + 3;
		}
		$ret	= price($dimension, 0, $outputlangs, 0, 0, $round).' '.measuringUnitString(0, $type, $unit, $use_short_label);
		return $ret;
	}

	/**
	* Output TVA label
	*
	*	@param	string		$code				VAT code
	*	@param	string		$country_code		country code
	*	@return	string							VAT label, 0 if not found, >0 on error
	**/
	function infraspackplus_showVatLabel($code, $country_code)
	{
		global $db;

		if (!empty($code) && !empty($country_code)) {
			$sql	= 'SELECT t.note';
			$sql	.= ' FROM '.$db->prefix().'c_tva as t, '.$db->prefix().'c_country as c';
			$sql	.= ' WHERE t.fk_pays = c.rowid';
			$sql	.= ' AND t.active > 0';
			$sql	.= ' AND c.code LIKE "'.$country_code.'"';
			$sql	.= ' AND t.code LIKE "'.$code.'"';
			$resql	= $db->query($sql);
			if (!empty($resql)) {
				$objp	= $db->fetch_object($resql);
				return	$objp->note != '' ? $objp->note : 0;
			} else {
				dol_print_error($db);
				return -1;
			}
		}
		return '';
	}

	/**
	*
	*	@param	CommonObject	$object					The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	*	@return	array									list of options option name => array(type value, type backup (user / doc), value, constante used for default value)
	**/
	function infraspackplus_defaultParam($object)
	{
		global $db, $user;

		$listOptions		= array('logo'					=> array('typeVal' => 'alpha',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_SET_LOGO_EMET_TIERS'),
									'adr'					=> array('typeVal' => 'int',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_USE_CUSTOM_COUNTRY_ADDR'),
									'customerAddrSelect'	=> array('typeVal' => 'alpha',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_CUSTOMER_ADDR_SELECT'),
									'listfreet'				=> array('typeVal' => 'array',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'listnotep'				=> array('typeVal' => 'array',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'pied'					=> array('typeVal' => 'alpha',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_IMAGE_FOOT'),
									'adrlivr'				=> array('typeVal' => 'int',	'bkptype' => '', 'value' => '', 'defaultconst' => array('INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT', 'INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SYST', 'INFRASPLUS_PDF_FACTURE_ADDR_LIVR_SI_FACT')),
									'Sst'					=> array('typeVal' => 'int',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'adrSst'				=> array('typeVal' => 'int',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'adrlivrfour'			=> array('typeVal' => 'int',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_DEFAULT_ADDR_DELIV'),
									'typeadr'				=> array('typeVal' => 'alpha',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'cgv'					=> array('typeVal' => 'alpha',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_CGV'),
									'cgi'					=> array('typeVal' => 'alpha',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_CGI'),
									'cga'					=> array('typeVal' => 'alpha',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_CGA'),
									'filesArray'			=> array('typeVal' => 'array',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'expensereportfiles'	=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_FILES_FROM_EXPENSE_REPORT'),
									'includealias'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME'),
									'mergeproduct'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'usentascover'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_NT_USED_AS_COVER'),
									'showwvccchk'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'hidepict'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => array('INFRASPLUS_PDF_WITH_PICTURE', 'INFRASPLUS_PDF_SUPPLIER_ORDER_WITH_PICTURE')),
									'refcol'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_WITH_REF_COLUMN'),
									'hidetimespent'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_HIDE_TIME_SPENT_FI'),
									'hidedesc'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => array('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', 'INFRASPLUS_PDF_SHOW_DESC_DEV')),
									'hidedisc'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_HIDE_DISCOUNT'),
									'hidecols'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_HIDE_COLS'),
									'showpricebl'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_BL_WITH_PRICE'),
									'adrfact'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_FACTURE_CODE_ADDR_FACT'),
									'showtotdisc'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_SHOW_TOT_DISCOUNT'),
									'showtvabtp'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_FREETEXT_TVA_6'),
									'showtot'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'showvir'				=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => 'INFRASPLUS_PDF_NO_IBAN'),
									'showpayspec'			=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => ''),
									'showPropalSignEmet'	=> array('typeVal' => 'chk',	'bkptype' => '', 'value' => '', 'defaultconst' => '')
									);
		$listModulesFreeT	= array(array ('propal',			'PROPOSAL_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV'),
									array ('commande',			'ORDER_FREE_TEXT',				'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_COM'),
									array ('contrat',			'CONTRACT_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_CT'),
									array ('shipping',			'SHIPPING_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXP'),
									array ('reception',			'RECEPTION_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_RE'),
									array ('delivery',			'DELIVERY_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_REC'),
									array ('fichinter',			'FICHINTER_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FI'),
									array ('facture',			'INVOICE_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FAC'),
									array ('supplier_proposal',	'SUPPLIER_PROPOSAL_FREE_TEXT',	'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_DEV_FOU'),
									array ('order_supplier',	'SUPPLIER_ORDER_FREE_TEXT',		'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_FOU'),
									array ('product',			'PRODUCT_FREE_TEXT',			'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_PROD'),
									array ('mo',				'MRP_MO_FREE_TEXT',				'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_MRP'),
									array ('bom',				'BOM_FREE_TEXT',				'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_BOM'),
									array ('project',			'PROJECT_FREE_NOTE',			''),
									array ('expensereport',		'EXPENSEREPORT_FREE_TEXT',		'INFRASPLUS_PDF_SHOW_SYS_MC_BASE_EXPR')
									);
		$listModulesNoteP	= array(array ('propal',			'PROPOSAL_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV'),
									array ('commande',			'ORDER_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_COM'),
									array ('contrat',			'CONTRACT_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_CT'),
									array ('shipping',			'SHIPPING_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_EXP'),
									array ('reception',			'RECEPTION_PUBLIC_NOTE',			'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_RE'),
									array ('delivery',			'DELIVERY_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_REC'),
									array ('fichinter',			'FICHINTER_PUBLIC_NOTE',			'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FI'),
									array ('facture',			'INVOICE_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FAC'),
									array ('supplier_proposal',	'SUPPLIER_PROPOSAL_PUBLIC_NOTE',	'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_DEV_FOU'),
									array ('order_supplier',	'SUPPLIER_ORDER_PUBLIC_NOTE',		'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_FOU'),
									array ('product',			'PRODUCT_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_PROD'),
									array ('mo',				'MRP_PUBLIC_NOTE',					'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_MRP'),
									array ('bom',				'BOM_PUBLIC_NOTE',					'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_BOM'),
									array ('project',			'PROJECT_PUBLIC_NOTE',				'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_PROJ'),
									array ('expensereport',		'EXPENSEREPORT_PUBLIC_NOTE',		'INFRASPLUS_PDF_SHOW_SYS_NT_BASE_EXPR')
									);
		// On parcour la liste des options pour les trier entre options utilisateur et option document
		foreach ($listOptions as $key => $option) {
			$constname	= 'INFRASPLUS_PDF_OPTION_'.$key;
			$listOptions[$key]['bkptype']	= getDolGlobalString($constname, '');	// 'user', 'doc', 'type', 'cust' or empty
		}
		// Constantes contenant les paramètres sauvegardés
		$paramsKeyUser	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_USER_'.$user->id;
		$paramsKeyDoc	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_DOC_'.$object->id;
		$paramsKeyType	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_TYPE';
		$paramsKeyCust	= 'INFRASPLUS_PDF_PARAMS_'.$object->element.'_CUST_'.(!empty($object->thirdparty->id) ? $object->thirdparty->id : '');
		// Paramètres enregistrés (utilisateur, document, type de document ou client)
		$txtParamsUser	= getDolGlobalString($paramsKeyUser, '');
		$txtParamsDoc	= getDolGlobalString($paramsKeyDoc, '');
		$txtParamsType	= getDolGlobalString($paramsKeyType, '');
		$txtParamsCust	= getDolGlobalString($paramsKeyCust, '');
		// liste de contrôle des paramètres enregistrés (utilisateur, document, type de document ou client)
		$listParamUser	= [];
		$listParamDoc	= [];
		$listParamType	= [];
		$listParamCust	= [];
		// On parcourt les paramètres utilisateurs
		if (!empty($txtParamsUser)) {
			$userParams	= explode ('&', $txtParamsUser);
			foreach ($userParams as $userParam) {
				$paramUser	= array ();
				parse_str($userParam, $paramUser);
				if (!empty($listOptions[key($paramUser)]['bkptype']) && $listOptions[key($paramUser)]['bkptype'] == 'user') {
					$typeVal	= !empty($listOptions[key($paramUser)]['typeVal']) ? $listOptions[key($paramUser)]['typeVal'] : '';
					if ($paramUser[key($paramUser)] == 'none') {	// empty value for param
						$listOptions[key($paramUser)]['value']	= $typeVal == 'chk' ? 0 : '';
					} else {
						$listOptions[key($paramUser)]['value']	= $typeVal == 'array' ? explode ('-', $paramUser[key($paramUser)]) : $paramUser[key($paramUser)];
					}
					$listParamUser[]	= key($paramUser);
					dol_syslog('infraspackplus.lib::infraspackplus_defaultParam key($paramUser) = '.key($paramUser).' $paramUser[key($paramUser)] = '.$paramUser[key($paramUser)]);
				}
			}
		}
		// On parcourt les paramètres document (référence)
		if (!empty($txtParamsDoc)) {
			$docParams	= explode ('&', $txtParamsDoc);
			foreach ($docParams as $docParam) {
				$paramDoc	= array ();
				parse_str($docParam, $paramDoc);
				if (!empty($listOptions[key($paramDoc)]['bkptype']) && $listOptions[key($paramDoc)]['bkptype'] == 'doc') {
					$typeVal	= !empty($listOptions[key($paramDoc)]['typeVal']) ? $listOptions[key($paramDoc)]['typeVal'] : '';
					if ($paramDoc[key($paramDoc)] == 'none') {	// empty value for param
						$listOptions[key($paramDoc)]['value']	= $typeVal == 'chk' ? 0 : '';
					} else {
						$listOptions[key($paramDoc)]['value']	= $typeVal == 'array' ? explode ('-', $paramDoc[key($paramDoc)]) : $paramDoc[key($paramDoc)];
					}
					$listParamDoc[]	= key($paramDoc);
					dol_syslog('infraspackplus.lib::infraspackplus_defaultParam key($paramDoc) = '.key($paramDoc).' $paramDoc[key($paramDoc)] = '.$paramDoc[key($paramDoc)]);
				}
			}
		}
		// On parcourt les paramètres type de document (devis, commande, ...)
		if (!empty($txtParamsType)) {
			$typeParams	= explode ('&', $txtParamsType);
			foreach ($typeParams as $typeParam) {
				$paramType	= array ();
				parse_str($typeParam, $paramType);
				if (!empty($listOptions[key($paramType)]['bkptype']) && $listOptions[key($paramType)]['bkptype'] == 'type') {
					$typeVal	= $listOptions[key($paramType)]['typeVal'];
					if ($paramType[key($paramType)] == 'none') {	// empty value for param
						$listOptions[key($paramType)]['value']	= $typeVal == 'chk' ? 0 : '';
					} else {
						$listOptions[key($paramType)]['value']	= $typeVal == 'array' ? explode ('-', $paramType[key($paramType)]) : $paramType[key($paramType)];
					}
					$listParamType[]	= key($paramType);
					dol_syslog('infraspackplus.lib::infraspackplus_defaultParam key($paramType) = '.key($paramType).' $paramType[key($paramType)] = '.$paramType[key($paramType)]);
				}
			}
		}
		// On parcourt les paramètres clients
		if (!empty($txtParamsCust)) {
			$custParams	= explode ('&', $txtParamsCust);
			foreach ($custParams as $custParam) {
				$paramCust	= array ();
				parse_str($custParam, $paramCust);
				if (!empty($listOptions[key($paramCust)]['bkptype']) && $listOptions[key($paramCust)]['bkptype'] == 'cust') {
					$typeVal	= $listOptions[key($paramCust)]['typeVal'];
					if ($paramCust[key($paramCust)] == 'none') {	// empty value for param
						$listOptions[key($paramCust)]['value']	= $typeVal == 'chk' ? 0 : '';
					} else {
						$listOptions[key($paramCust)]['value']	= $typeVal == 'array' ? explode ('-', $paramCust[key($paramCust)]) : $paramCust[key($paramCust)];
					}
					$listParamCust[]	= key($paramCust);
					dol_syslog('infraspackplus.lib::infraspackplus_defaultParam key($paramCust) = '.key($paramCust).' $paramCust[key($paramCust)] = '.$paramCust[key($paramCust)]);
				}
			}
		}
		// Aucun paramètres enregistrés => nouveau document et / ou nouvel utilisateur et / ou nouveau type de document et / ou nouveau client
		foreach ($listOptions as $key => $option) {
			if (!empty($listOptions[$key]['bkptype']) && ($listOptions[$key]['bkptype'] == 'none' || (!in_array($key, $listParamUser) && $listOptions[$key]['bkptype'] == 'user')
																								  || (!in_array($key, $listParamDoc) && $listOptions[$key]['bkptype'] == 'doc')
																								  || (!in_array($key, $listParamType) && $listOptions[$key]['bkptype'] == 'type')
																								  || (!in_array($key, $listParamCust) && $listOptions[$key]['bkptype'] == 'cust'))) {
				// logo expéditeur
				if ($key == 'logo') {
					$ParamLogoEmet				= getDolGlobalInt($listOptions[$key]['defaultconst'], 0);
					$listOptions[$key]['value']	= !empty($ParamLogoEmet) ? infraspackplus_getLogoEmet($object->thirdparty->id) : 'none';
				}
				// adresse expéditeur
				if ($key == 'adr') {
					$countryAddr	= getDolGlobalInt($listOptions[$key]['defaultconst'], 0);
					if (!empty($countryAddr) && !empty($object->thirdparty->country_code)) {
						$adrtmp		= new Address($db);
						$adrfound	= $adrtmp->fetch(0, 0, $object->thirdparty->country_code);
					} else {
						$adrfound	= 0;
					}
					$listOptions[$key]['value']	= $adrfound == 1 ? $adrtmp->id : 'none';
				}
				// type d'affichage adresse expéditeur (avec / sans contact, adresse société / contact; etc...)
				if ($key == 'customerAddrSelect') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Mentions complémentaires
				if ($key == 'listfreet') {
					$showsysmcbase	= '';
					foreach ($listModulesFreeT as $module) {
						if ($object->element == $module[0]) {
							$showsysmcbase				= $module[2];
							$freeT						= $module[1];
							$listOptions[$key]['value']	= empty($showsysmcbase) || !getDolGlobalString($freeT) ? 'none' : $freeT;
							break;
						}
					}
				}
				// Notes publiques standards
				if ($key == 'listnotep') {
					$showsysntbase	= '';
					foreach ($listModulesNoteP as $module) {
						if ($object->element == $module[0]) {
							$rootnotepub	= $module[1];
							$showsysntbase	= $module[2];
							$listOptions[$key]['value']	= empty($showsysntbase) ? 'none' : getDolGlobalString($showsysntbase, 'none');
							break;
						}
					}
				}
				// Image en pied de document
				if ($key == 'pied') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Adresse de livraison (client)
				if ($key == 'adrlivr' && in_array($object->element, array('propal', 'commande', 'facture', 'fichinter', 'shipping', 'reception', 'delivery'))) {
					$def_adrfact		= getDolGlobalString($listOptions[$key]['defaultconst'][0], 'Vide');	// Code adresse de facturation par défaut
					$def_adrfact_sys	= getDolGlobalInt($listOptions[$key]['defaultconst'][1], 0);	// Afficher automatiquement (par défaut) une adresse de livraison même si aucune adresse de facturation n'est présente
					$addrLivrSiFact		= getDolGlobalInt($listOptions[$key]['defaultconst'][2], 0);	// 	Afficher automatiquement (par défaut) une adresse de livraison quand l'adresse de facturation automatique est active
					$client				= infraspackplus_check_parent_addr_fact($object);	// Check if the parent company should be used
					$adrfacttmp			= new Address($db);
					$res_adrfact		= $adrfacttmp->fetch(0, $client->id, $def_adrfact);
					$listOptions[$key]['value']	= !empty($def_adrfact_sys) || (!empty($addrLivrSiFact) && $res_adrfact > 0) ? -1 : -2;	// -1 pour défaut, -2 pour aucune
				}
				// Sous-Traitant (lié au client via "CustomLink")
				if ($key == 'Sst' || $key == 'adrSst') {
					$listOptions[$key]['value']	= -2;	// -2 pour aucun
				}
				// Adresse de livraison spéciale fournisseur (interne ou interne + client)
				if ($key == 'adrlivrfour') {
					$listOptions[$key]['value']	= getDolGlobalInt($listOptions[$key]['defaultconst'], -2);
				}
				// Type d'adresse de livraison spéciale fournisseur (I_ interne, C_ adresse principale client ou S_ adresse secondaire client)
				if ($key == 'typeadr') {
					$listOptions[$key]['value']	= -2;	// -2 pour aucun;
				}
				// Conditions générales
				if (in_array($key, array('cgv', 'cgi', 'cga')) && !empty($user->hasRight('infraspackplus', 'paramCGV'))) {
					// CGV
					if ($key == 'cgv') {
						$cgbydef	= 0;
						$cgbydef	+= (in_array($object->element, array('propal')) && getDolGlobalString('INFRASPLUS_PDF_CGV_BY_DEF_FOR_PROPOSALS', '')) ? 1 : 0;
						$cgbydef	+= (in_array($object->element, array('commande')) && getDolGlobalString('INFRASPLUS_PDF_CGV_BY_DEF_FOR_ORDERS', '')) ? 1 : 0;
						$cgbydef	+= (in_array($object->element, array('facture')) && getDolGlobalString('INFRASPLUS_PDF_CGV_BY_DEF_FOR_INVOICES', '')) ? 1 : 0;
						$cgbydef	+= (in_array($object->element, array('contrat')) && getDolGlobalString('INFRASPLUS_PDF_CGV_BY_DEF_FOR_CONTRACTS', '')) ? 1 : 0;
					}
					// CGI
					if ($key == 'cgi') {
						$cgbydef	= 1;
					}
					// CGA
					if ($key == 'cga') {
						$cgbydef	= 0;
						$cgbydef	+= (in_array($object->element, array('supplier_proposal')) && getDolGlobalString('INFRASPLUS_PDF_CGA_BY_DEF_FOR_PROPOSALS', '')) ? 1 : 0;
						$cgbydef	+= (in_array($object->element, array('order_supplier')) && getDolGlobalString('INFRASPLUS_PDF_CGA_BY_DEF_FOR_ORDERS', '')) ? 1 : 0;
					}
					$listOptions[$key]['value']	= $cgbydef > 0 ? getDolGlobalString($listOptions[$key]['defaultconst'], 'none') : 'none';
				}
				// Fichiers joints à fusionner
				if ($key == 'filesArray') {
					$listOptions[$key]['value']	= 'none';
				}
				// Pièces jointes à fusionner aux notes de frais
				if ($key == 'expensereportfiles') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Alias
				if ($key == 'includealias') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Fusion documentation produits / services
				if ($key == 'mergeproduct') {
					$listOptions[$key]['value']	= 'none';
				}
				// Page de garde
				if ($key == 'usentascover') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], '') && !empty($rootnotepub) ? $rootnotepub.'_'.getDolGlobalString($listOptions[$key]['defaultconst'], '') : 'none';
				}
				// Infos Douanières (Poids, volume, dimensions et code SH
				if ($key == 'showwvccchk') {
					$wvccopt	= 5;
					if (getDolGlobalString('PRODUCT_DISABLE_SIZE', '')) {
						$wvccopt--;
					}
					if (getDolGlobalString('PRODUCT_DISABLE_LENGTH', '')) {
						$wvccopt--;
					}
					if (getDolGlobalString('PRODUCT_DISABLE_SURFACE', '')) {
						$wvccopt--;
					}
					if (getDolGlobalString('PRODUCT_DISABLE_VOLUME', '')) {
						$wvccopt--;
					}
					if (getDolGlobalString('PRODUCT_DISABLE_CUSTOM_INFO', '')) {
						$wvccopt--;
					}
					if ($wvccopt > 0) {
						$wvccproposals	= getDolGlobalInt('INFRASPLUS_PDF_WVCC_BY_DEF_FOR_PROPOSALS', 0);
						$wvccorders		= getDolGlobalInt('INFRASPLUS_PDF_WVCC_BY_DEF_FOR_ORDERS', 0);
						$wvccexped		= getDolGlobalInt('INFRASPLUS_PDF_WVCC_BY_DEF_FOR_EXPEDITION', 0);
						$wvccinvoices	= getDolGlobalInt('INFRASPLUS_PDF_WVCC_BY_DEF_FOR_INVOICES', 0);
						if (($object->element == 'propal' && !empty($wvccproposals)) || ($object->element == 'commande' && !empty($wvccorders))
							|| ($object->element == 'shipping' && !empty($wvccexped)) || ($object->element == 'facture' && !empty($wvccinvoices))) {
							$listOptions[$key]['value']	= 'showwvccchk';
						} else {
							$listOptions[$key]['value']	= 'none';
						}
					} else {
						$listOptions[$key]['value']	= -2;	// -2 pour aucune
					}
				}
				// Image des produits / services
				if ($key == 'hidepict') {
					// dans les documents client
					if (in_array($object->element, array('propal', 'commande', 'facture', 'contrat', 'fichinter', 'shipping', 'reception', 'delivery'))) {
						$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'][0], 'none');
					}
					// dans les commandes fournisseur
					if (in_array($object->element, array('order_supplier'))) {
						$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'][1], 'none');
					}
				}
				// colonne référence
				if ($key == 'refcol') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// durées (total et ligne par ligne) dans les fiches d'intervention
				if ($key == 'hidetimespent') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Description longue des produits / services
				if ($key == 'hidedesc') {
					$hidedesc					= getDolGlobalString($listOptions[$key]['defaultconst'][0], 'none');
					$showdescdev				= getDolGlobalInt($listOptions[$key]['defaultconst'][1], 0);
					$listOptions[$key]['value']	= in_array($object->element, array('propal')) && !empty($showdescdev) ? 'none' : $hidedesc;
				}
				// Remise
				if ($key == 'hidedisc') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Description seule
				if ($key == 'hidecols') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Affichage du total HT sur le BL
				if ($key == 'showpricebl') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Information concernant l'adresse de facturation automatique
				if ($key == 'adrfact') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Affichage du total des remises
				if ($key == 'showtotdisc') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], 'none');
				}
				// Affichage de la mention d'autoliquidation BTP
				// Case d'option par document (opt-in) : décochée par défaut tant qu'aucun choix n'est mémorisé.
				// Le 'defaultconst' (INFRASPLUS_PDF_FREETEXT_TVA_6) sert uniquement à AFFICHER la case, pas à la cocher.
				if ($key == 'showtvabtp') {
					$listOptions[$key]['value']	= 0;
				}
				// Affichage des totaux en pied de document sur les fiches d'intervention
				if ($key == 'showtot') {
					$listOptions[$key]['value']	= 'showtot';
				}
				// Afficher / Masquer le mode de paiement par virement
				if ($key == 'showvir') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], '') ? 'none' : 'showvir';
				}
				// Désactivation des paiements spéciaux
				if ($key == 'showpayspec') {
					$listOptions[$key]['value']	= 'showpayspec';
				}
				// Afficher / Masquer la zone de signature société émettrice sur les fiches d'intervention
				if ($key == 'showPropalSignEmet') {
					$listOptions[$key]['value']	= getDolGlobalString($listOptions[$key]['defaultconst'], '') ? 'showPropalSignEmet' : 'none';
				}
				dol_syslog('infraspackplus.lib::infraspackplus_defaultParam $key = '.$key.' => typeVal : '.$listOptions[$key]['typeVal'].', bkptype : '.$listOptions[$key]['bkptype'].', value : '.$listOptions[$key]['value'].', defaultconst : '.(is_array($listOptions[$key]['defaultconst']) ? implode('; ', (array) $listOptions[$key]['defaultconst']) : $listOptions[$key]['defaultconst']));
			}
		}
		return array('listOptions' => $listOptions, 'listModulesFreeT' => $listModulesFreeT, 'listModulesNoteP' => $listModulesNoteP);
	}

	/**
	*
	*	@param	object		$object				The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	*	@param	int			$hidedetails		0 to show details, 1 to hide details
	*	@param	int			$hidedesc			0 to show description, 1 to hide description
	*	@param	int			$hideref			0 to show reference, 1 to hide reference
	*	@param	int			$idwarehouse		Id of warehouse to use for stock movements
	*	@param	string		$locationTarget		Location to redirect after
	*	@param	string		$action				Action performed (e.g. 'validate', 'close', etc.)
	*	@return	int								0 if OK, -1 if KO
	**/
	function infraspackplus_semiauto_update(&$object, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $idwarehouse = 0, &$locationTarget = '', &$action = '')
	{
		global $conf, $db, $langs, $user;

		$print		= 0;
		$isV16p		= version_compare(DOL_VERSION, '16.0.0') >= 0;
		$isV20p		= version_compare(DOL_VERSION, '20.0.0') >= 0;
		$isV22p		= version_compare(DOL_VERSION, '22.0.0') >= 0;
		$deposit	= null;
		if ($object != null && method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}
		if ($action == 'setnote_public') {
			$result_update	= $object->update_note(dol_html_entity_decode(GETPOST('note_public', 'restricthtml'), ENT_QUOTES | ENT_HTML5, 'UTF-8', 1), '_public');
			if ($result_update < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			} else {
				$print	= 1;
			}
		} elseif ($action == 'update_extras') {
			$extra				= new ExtraFields($db);
			$object->oldcopy	= dol_clone($object, 2);
			$attribute_name		= GETPOST('attribute', 'restricthtml');
			$extra->fetch_name_optionals_label($object->table_element);
			$ret				= $extra->setOptionalsFromPost(null, $object, $attribute_name);
			if ($ret < 0) {
				setEventMessages($extra->error, $object->errors, 'errors');
				$action	= 'edit_extras';
				return -1;
			} else {
				$triggerMap	= array('propal'			=> 'PROPAL_MODIFY',
									'commande'			=> 'ORDER_MODIFY',
									'facture'			=> 'BILL_MODIFY',
									'contrat'			=> 'CONTRACT_MODIFY',
									'delivery'			=> 'DELIVERY_MODIFY',
									'shipping'			=> ($isV22p ? 'SHIPPING_MODIFY' : 'SHIPMENT_MODIFY'),
									'expensereport'		=> 'EXPENSEREPORT_MODIFY',
									'fichinter'			=> 'INTERVENTION_MODIFY',
									'order_supplier'	=> 'ORDER_SUPPLIER_MODIFY',
									'invoice_supplier' 	=> 'BILL_SUPPLIER_MODIFY',
									'reception'        	=> 'RECEPTION_MODIFY',
									'supplier_proposal'	=> 'PROPOSAL_SUPPLIER_MODIFY'
									);
				$result	= $object->updateExtraField($attribute_name, !empty($triggerMap[$object->element]) ? $triggerMap[$object->element] : '');
				if ($result > 0) {
					setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
					$action	= 'view';
					$print	= 1;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$action	= 'edit_extras';
				}
			}
		} elseif ($action == 'setecheance') {
			// Traitement de la modification de la date de fin de validité
			$newdate		= dol_mktime(12, 0, 0, GETPOSTINT('echmonth'), GETPOSTINT('echday'), GETPOSTINT('echyear'));
			$result_update	= $object->set_echeance($user, $newdate);
			if ($result_update < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			} else {
				$print	= 1;
			}
		} elseif ($action == 'setconditions') {
			// Traitement de la modification des conditions de règlement
			$cond_reglement_id					= GETPOSTINT('cond_reglement_id');
			$cond_reglement_id_deposit_percent	= GETPOSTFLOAT('cond_reglement_id_deposit_percent');
			$sql								= 'SELECT code FROM '.$db->prefix().'c_payment_term WHERE rowid = '.((int) $cond_reglement_id);
			$result								= $db->query($sql);
			if ($result) {
				$obj	= $db->fetch_object($result);
				if ($obj && $obj->code == 'DEP30PCTDEL') {
					$result_update	= $object->setPaymentTerms($cond_reglement_id, $cond_reglement_id_deposit_percent);
				} else {
					$object->deposit_percent	= 0;
					$object->update($user);
					$result_update				= $object->setPaymentTerms($cond_reglement_id, $object->deposit_percent);
				}
				if ($result_update < 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					return -1;
				} else {
					$print = 1;
				}
			} else {
				setEventMessages($db->lasterror(), null, 'errors');
				return -1;
			}
		} elseif ($action == 'setmode') {
			// Traitement de la modification du mode de règlement
			$result_update = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
			if ($result_update < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			} else {
				$print	= 1;
			}
		} elseif ($action == 'setbankaccount') {
			// Traitement de la modification du compte bancaire
			$result_update = $object->setBankAccount(GETPOSTINT('fk_account'));
			if ($result_update < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			} else {
				$print = 1;
			}
		} elseif ($action == 'setdate_livraison') {
			// Traitement de la modification de la date d'expédition
			$date_delivery_propal	= dol_mktime(12, 0, 0, GETPOSTINT('date_livraisonmonth'), GETPOSTINT('date_livraisonday'), GETPOSTINT('date_livraisonyear'));
			$date_delivery_commande	= dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));
			if ($object instanceof Propal) {
				$result_update	= $object->setDeliveryDate($user, $date_delivery_propal);
			} elseif ($object instanceof Commande) {
				$result_update	= $object->setDeliveryDate($user, $date_delivery_commande);
			} else {
				$result_update	= -1;
			}
			if ($result_update < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			} else {
				$print = 1;
			}
		} elseif ($action == 'setavailability') {
			// Traitement de la modification du délai de livraison
			$result_update = $object->availability(GETPOST('availability_id'));
			if ($result_update < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			} else {
				$print = 1;
			}
		} else {
			if ($object instanceof Propal) {
				$result	= $object->valid($user);	// Validation
				if ($result > 0 && getDolGlobalString('PROPAL_SKIP_ACCEPT_REFUSE', '')) {
					$result	= $object->closeProposal($user, $object::STATUS_SIGNED);
				}
				if ($result >= 0) {
					$print	= 1;
				}
			} elseif ($object instanceof Commande) {
				$qualified_for_stock_change	= !getDolGlobalString('STOCK_SUPPORTS_SERVICES', '') ? $object->hasProductsOrServices(2) : $object->hasProductsOrServices(1);
				// Check parameters
				if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER', '') && $qualified_for_stock_change) {
					if (empty($idwarehouse) || $idwarehouse == -1) {
						$object->error	= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Warehouse'));
						return -1;
					}
				}
				$db->begin();
				$result	= $object->valid($user, $idwarehouse);
				if ($result >= 0) {
					$deposit_percent_from_payment_terms	= (float) getDictionaryValue($db->prefix().'c_payment_term', 'deposit_percent', $object->cond_reglement_id);
					if (GETPOST('generate_deposit', 'alpha') == 'on' && !empty($deposit_percent_from_payment_terms) && isModEnabled((!$isV20p ? 'facture' : 'invoice')) && !empty($user->hasRight('facture', 'creer'))) {
						$date			= dol_mktime(0, 0, 0, GETPOSTINT('datefmonth'), GETPOSTINT('datefday'), GETPOSTINT('datefyear'));
						$forceFields	= [];
						if (GETPOSTISSET('date_pointoftax')) {
							$forceFields['date_pointoftax']	= dol_mktime(0, 0, 0, GETPOSTINT('date_pointoftaxmonth'), GETPOSTINT('date_pointoftaxday'), GETPOSTINT('date_pointoftaxyear'));
						}
						$deposit	= Facture::createDepositFromOrigin($object, $date, GETPOSTINT('cond_reglement_id'), $user, 0, GETPOST('validate_generated_deposit', 'alpha') == 'on', $forceFields);
						if (!empty($deposit)) {
							setEventMessage('DepositGenerated');
							$locationTarget	= DOL_URL_ROOT.'/compta/facture/card.php?id='.$deposit->id;
						} else {
							$db->rollback();
							return -1;
						}
					}
					$print	= 1;
					$db->commit();
				}
			} elseif ($object instanceof Facture) {
				$object->fetch($object->id);
				$object->fetch_thirdparty();
				$error	= 0;
				if (!$isV16p) {	// pour Dolibarr version < 16
					// Check for mandatory fields in invoice
					$array_to_check	= array('REF_CLIENT' => 'RefCustomer');
					foreach ($array_to_check as $key => $val) {
						$keymin			= strtolower($key);
						$vallabel		= $object->$keymin;
						$keymandatory	= 'INVOICE_'.$key.'_MANDATORY_FOR_VALIDATION';
						if (empty($vallabel) && getDolGlobalString($keymandatory, '')) {
							$langs->load('errors');
							$error++;
							$object->errors[]	= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv($val));
						}
					}
				}
				// Check for warehouse
				if ($object->type != Facture::TYPE_DEPOSIT && getDolGlobalString('STOCK_CALCULATE_ON_BILL', '')) {
					$qualified_for_stock_change	= !getDolGlobalString('STOCK_SUPPORTS_SERVICES', '') ? $object->hasProductsOrServices(2) : $object->hasProductsOrServices(1);
					if (!empty($qualified_for_stock_change)) {
						if (empty($idwarehouse) || $idwarehouse == - 1) {
							$error++;
							$object->errors[]	= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Warehouse'));
						}
					}
				}
				if (!$error) {
					$result	= $object->validate($user, 0, $idwarehouse);
					if ($result >= 0) {
						$print	= 1;
					}
				} else {
					return -1;
				}
			} elseif ($object instanceof Contrat) {
				$result	= $object->validate($user);	// Validation
				if ($result > 0) {
					$print	= 1;
				}
			} elseif ($object instanceof Fichinter) {
				$result	= $object->setValid($user);	// Validation
				if ($result >= 0) {
					$print	= 1;
				}
			} elseif ($object instanceof Expedition || $object instanceof Reception || $object instanceof Delivery || $object instanceof SupplierProposal) {
				$object->fetch_thirdparty();
				$result	= $object->valid($user);	// Validation
				if ($result >= 0) {
					$print	= 1;
				}
			} elseif ($object instanceof CommandeFournisseur) {
				// Additional area permissions
				$usercanapprove	= $user->hasRight('fournisseur', 'commande', 'approuver');
				$db->begin();
				$object->date_commande	= dol_now();
				$result					= $object->valid($user);	// Validation
				if ($result >= 0) {
					if (!getDolGlobalString('SUPPLIER_ORDER_NO_DIRECT_APPROVE') && $usercanapprove && !(getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') && $object->hasProductsOrServices(1))) {
						$qualified_for_stock_change	= !getDolGlobalString('STOCK_SUPPORTS_SERVICES') ? $object->hasProductsOrServices(2) : $object->hasProductsOrServices(1);
						// Check parameters
						if (isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') && $qualified_for_stock_change) {	// warning name of option should be STOCK_CALCULATE_ON_SUPPLIER_APPROVE_ORDER
							if (!$idwarehouse || $idwarehouse == -1) {
								$db->rollback();
								$object->error	= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Warehouse'));
								return -1;
							}
						}
						$result	= $object->approve($user, $idwarehouse, ($action == 'confirm_approve2' ? 1 : 0));
						if ($result <= 0) {
							$db->rollback();
							setEventMessages($object->error, $object->errors, 'errors');
							return -1;
						}
					}
					$print	= 1;
					$db->commit();
				}
			}
		}
		if ($print) {
			$outputlangs	= $langs;
			$newlang		= '';
			if (getDolGlobalString('MAIN_MULTILANGS', '') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang	= GETPOST('lang_id', 'aZ09');
			}
			if (getDolGlobalString('MAIN_MULTILANGS', '') && empty($newlang) && is_object($object->thirdparty)) {
				$newlang	= $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs	= new Translate('', $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret	= $object->fetch($object->id);	// Reload to get new records
			if ($ret > 0) {
				$object->fetch_thirdparty();
			}
			if ($object instanceof Fichinter) {
				$result = fichinter_create($db, (object) $object, $object->model_pdf, $outputlangs);
			} else {
				$result	= $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
			if ($result < 0) {
				return -1;
			}
			if ($object instanceof Commande && !empty($deposit)) {
				$deposit->fetch($deposit->id); // Reload to get new records
				$deposit->generateDocument($deposit->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			} elseif ($object instanceof Facture && isModEnabled('infrasworkflow') && getDolGlobalInt('INFRASWORKFLOW_USE_DOCUMENT_MODEL_INFRASPLUS_FR')) {
				$hasDepositLine = false;
				foreach ($object->lines as $line) {
					$discount = new DiscountAbsolute($db);
					if ($discount->fetch($line->fk_remise_except) >= 0 && !empty($discount->fk_facture_source)) {
						// On a détecté une ligne de remise liée à une facture d’acompte
						$hasDepositLine = true;
						// Rechercher la facture d'acompte correspondante pour regénérer le document (besoin d'un PDF marqué 'payé')
						$deposit_invoice = new Facture($db);
						if ($deposit_invoice->fetch($discount->fk_facture_source) > 0 && $deposit_invoice->type == Facture::TYPE_DEPOSIT) {
							$deposit_invoice->generateDocument($deposit_invoice->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
						}
					}
				}
				if (!empty($hasDepositLine)) {
					$result = $object->generateDocument('InfraSPlus_FR', $outputlangs, $hidedetails, $hidedesc, $hideref);
					if ($result < 0) {
						return -1;
					}
				}
			}
			return 1;
		} else {
			$langs->load('errors');
			if (count($object->errors) > 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans($object->error), null, 'errors');
			}
			return -1;
		}
	}

	/**
	* Copy parameters from other company (MultiCompany)
	*
	* @param	int		$fromEntity			Source entity ID
	* @param	int		$toEntity			Target entity ID
	* @return	int						0 if OK, -1 if KO
	*/
	function infraspackplus_copy_entity($fromEntity, $toEntity)
	{
		global $db;

		if (!is_numeric($fromEntity) || !is_numeric($toEntity)) {
			return -1;
		}
		$db->begin();

		// DOCUMENT MODELS
		$sql	= 'SELECT nom, type, libelle FROM '.$db->prefix().'document_model WHERE entity = '.((int) $fromEntity).' AND nom LIKE \'INFRASPLUS\_%\'';
		$resql	= $db->query($sql);
		if ($resql == false) {
			$db->rollback();
			return -1;
		}
		while ($obj	= $db->fetch_object($resql)) {
			$sqlInsert	= 'INSERT INTO '.$db->prefix().'document_model (nom, entity, type, libelle) VALUES ("'.$db->escape($obj->nom).'", '.((int) $toEntity).', "'.$db->escape($obj->type).'", "'.$db->escape($obj->libelle).'")';
			$sqlInsert	.=' ON DUPLICATE KEY UPDATE type = VALUES(type), libelle = VALUES(libelle)';
			if (!$db->query($sqlInsert)) {
				$db->rollback();
				return -1;
			}
		}
		$db->free($resql);

		// CONST TABLE
		$sql1	= 'SELECT name, value, type, visible, note FROM '.$db->prefix().'const';
		$sql1	.= ' WHERE entity = '.((int) $fromEntity);
		$sql1	.= ' AND ((name LIKE "INFRASPLUS\_%" AND name NOT LIKE "INFRASPLUS\_PDF\_VALID\_CORE\_CHGT") OR name LIKE "INFRASPACKPLUS\_PS\_%" OR (name LIKE "%\_ADDON\_PDF" AND value LIKE "InfraSPlus\_%") OR name LIKE "%\_FREE_TEXT%" OR name LIKE "%\_PUBLIC\_NOTE%" OR name LIKE "MAIN_DOCUMENTS_LOGO_HEIGHT")';
		$resql1	= $db->query($sql1);
		if ($resql1 == false) {
			$db->rollback();
			return -1;
		}
		while ($obj = $db->fetch_object($resql1)) {
			$sqlInsert1	= 'INSERT INTO '.$db->prefix().'const (name, entity, value, type, visible, note) VALUES ("'.$db->escape($obj->name).'",'.((int) $toEntity).',"'.$db->escape($obj->value).'","'.$db->escape($obj->type).'",'.((int) $obj->visible).',"'.$db->escape($obj->note).'")';
			$sqlInsert1	.=' ON DUPLICATE KEY UPDATE value = VALUES(value), type = VALUES(type), visible = VALUES(visible), note = VALUES(note)';
			if (!$db->query($sqlInsert1)) {
				$db->rollback();
				return -1;
			}
		}
		$db->free($resql1);

		// 3. SOCIETE ADDRESS
		$sql2	= 'SELECT * FROM '.$db->prefix().'infraspackplus_societe_address WHERE entity = '.((int) $fromEntity);
		$resql2	= $db->query($sql2);
		if ($resql2 == false) {
			$db->rollback();
			return -1;
		}
		while ($obj	= $db->fetch_object($resql2)) {
			unset($obj->rowid);
			$obj->entity	= (int) $toEntity;
			$fields 		= [];
			$values 		= [];
			$updates 		= [];
			foreach ($obj as $key => $value) {
				$fields[]	= $key;
				if ($value == null) {
					$values[] 	= 'NULL';
					$updates[]	= $key.' = NULL';
				} else {
					$values[]	= "'".$db->escape($value)."'";
					$updates[]	= $key." = '".$db->escape($value)."'";
				}
			}
			$sqlInsert2	= 'INSERT INTO '.$db->prefix().'infraspackplus_societe_address ('.implode(',', $fields).') VALUES ('.implode(',', $values).') ON DUPLICATE KEY UPDATE '.implode(',', $updates);
			if (!$db->query($sqlInsert2)) {
				$db->rollback();
				return -1;
			}
		}
		$db->free($resql2);

		// DICTIONARIES
		$dictTables = array('c_infraspackplus_mention','c_infraspackplus_note');
		foreach ($dictTables as $table) {
			$sql3	= 'SELECT code, pos, libelle, active FROM '.$db->prefix().$table.' WHERE entity = '.((int) $fromEntity).' ORDER BY pos ASC';
			$resql3	= $db->query($sql3);
			if ($resql3 == false) {
				$db->rollback();
				return -1;
			}
			while ($obj	= $db->fetch_object($resql3)) {
				$sqlInsert3	= 'INSERT INTO '.$db->prefix().$table.' (code, entity, pos, libelle, active) VALUES ("'.$db->escape($obj->code).'", '.((int) $toEntity).', '.((int) $obj->pos).', "'.$db->escape($obj->libelle).'", '.((int) $obj->active).')';
				$sqlInsert3	.= ' ON DUPLICATE KEY UPDATE pos = VALUES(pos), libelle = VALUES(libelle), active = VALUES(active)';
				if (!$db->query($sqlInsert3)) {
					$db->rollback();
					return -1;
				}
			}
			$db->free($resql3);
		}
		$db->commit();
		return 1;
	}

	/**
	*	Split a product/service label string into label part and description part
	*	by finding the first line break (HTML or plain text).
	*	Also fixes malformed HTML patterns like <p> <br> </p>.
	*
	*	@param	string		$labelproductservice	The full label+description string (modified by reference if HTML fix is needed)
	*	@return	array|false							Array with 'pos' (end of label) and 'startdesc' (start of description), or false if no break found
	**/
	function infraspackplus_splitLabelDescription(&$labelproductservice)
	{
		$pos		= false;
		$startdesc	= 0;

		if (dol_textishtml($labelproductservice)) {
			$retchararray	= array('<br>', '<br/>', '<br />', '</p>');
			$isbr			= false;
			$retcharlen		= 0;
			foreach ($retchararray as $retchar) {	// Get first position of a html return
				$posfound	= strpos($labelproductservice, $retchar);
				if ($pos === false || ($posfound !== false && $posfound < $pos)) {
					$pos		= $posfound;
					$isbr		= $retchar != '</p>';
					$retcharlen	= strlen($retchar);
				}
			}
			if ($pos !== false) {
				if (!empty($isbr)) {	// Fix html to <br> <p> </p> if it's the case <p> <br> </p>
					$posfound	= strpos($labelproductservice, '<p>');
					if ($posfound !== false && $posfound < $pos) {
						$labelproductservice	= substr_replace($labelproductservice, '<p>', $pos + $retcharlen, 0);
						$labelproductservice	= substr_replace($labelproductservice, '', $posfound, strlen('<p>'));
						$pos					-= strlen('<p>');
					}
				}
				// Fix the real positions
				$pos		= $isbr ? $pos					: $pos + $retcharlen;
				$startdesc	= $isbr ? $pos + $retcharlen	: $pos;
			}
		} else {
			$pos		= strpos($labelproductservice, "\n");
			$startdesc	= $pos + strlen("\n");
		}

		if ($pos === false) {
			return false;
		}
		return array('pos' => $pos, 'startdesc' => $startdesc);
	}
