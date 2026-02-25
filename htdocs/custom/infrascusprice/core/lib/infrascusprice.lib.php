<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infrascusprice/core/lib/infrascusprice.lib.php
	*	\ingroup	InfraS
	*	\brief		Functions used by InfraSCusPrice module
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

	/**
	* Is substitution file
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	bool
	**/
	function infrascusp_is_substitution_page($path)
	{
		global $dolibarr_main_url_root_alt;
		if (preg_match('/^\/(|'.preg_quote(trim($dolibarr_main_url_root_alt, '/'), '/').'\/)substitutionpages/i', $path) == 1) {
			return true;
		}
		return false;
	}

	/**
	 * Get substitution url if exists
	 * @param	string		$path		Relative path from the root of Dolibarr of the page to be substituted
	 * @return	string					substitution url
	 **/
	function infrascusp_get_substitution_url($path)
	{
		$const_name	= infrascusp_get_const_name_from_substitution_path($path);
		if (!empty(getDolGlobalString($const_name, ''))) {
			$dolibranch		= explode('.', DOL_VERSION);
			$coreVersion	= 'dlb'.$dolibranch[0].'0x';
			$path_dst		= '/infrascusprice/substitutionpages/'.$coreVersion.$path;
			$real_path_dst	= dol_buildpath($path_dst, 0);
			dol_syslog('infrascusprice.lib.php::infrascusprice_get_substitution_url $path = '.$path.' $real_path_dst = '.$real_path_dst);
			if (file_exists($real_path_dst)) {
				$url_path_dst = dol_buildpath($path_dst, 2);
				return $url_path_dst;
			}
		}
		return '';
	}

	/**
	* Get const name from substitution path
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	string		Substitution url or empty
	**/
	function infrascusp_get_const_name_from_substitution_path($path)
	{
		$const_name	= 'INFRASCUSP_PS_ACTIVE'.strtoupper(str_replace('/', '_', str_replace('.php', '', $path)));
		dol_syslog('infrascusprice.lib::infrascusp_get_const_name_from_substitution_path $path = '.$path.' $const_name = '.$const_name);
		return $const_name;
	}

	/**
	*	Changes permissions on files and directories within $dir and dives recursively into found subdirectories.
	*
	*	@param	string	$action		Type of action (update or delete)
	*	@param	int		$id			Thirdparty ID (parent company or this one)
	*	@return	int					< 0 on error, 0 on success
	**/
	function infrascusp_actions($action, $id)
	{
		global $langs, $db, $user;

		$langs->load('exports');

		if (!$action || !$id) {
			setEventMessages($langs->trans('InfraSCusPidErr'), null, 'errors');
			return -1;
		}
		$prodcustprice		= new Productcustomerprice($db);
		$filter				= array ('t.fk_soc' => $id);
		$nbtotalofrecords	= $prodcustprice->fetchAll('', '', 0, 0, $filter);
		if ($nbtotalofrecords > 0) {
			$error	= 0;
			foreach ($prodcustprice->lines as $line) {
				$prodcustpriceline	= new Productcustomerprice($db);
				$prodcustpriceline->fetch($line->id);
				if ($action == 'delete') {
					$result	= $prodcustpriceline->delete($user);
				}
				if ($action == 'update') {
					$result	= $prodcustpriceline->update($user, 0, 1);
				}
				if ($result < 0) {
					$error ++;
				}
			}
			if (empty($error)) {
				setEventMessages($langs->trans('NbOfLinesOK', $nbtotalofrecords), null, 'mesgs');
			} else {
				setEventMessages($error.' '.$langs->trans('Errors').' => '.$langs->trans('NbOfLinesOK', $nbtotalofrecords - $error), null, 'warnings');
			}
		} else {
			setEventMessages($langs->trans('NoRecordedProducts'), null, 'warnings');
		}
		return 0;
	}
