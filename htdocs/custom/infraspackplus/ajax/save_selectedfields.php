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
	* 	\file		./infraspackplus/ajax/save_selectedfields.php
	* 	\ingroup	InfraS
	* 	\brief		AJAX endpoint to persist selected columns per user
	************************************************/

	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', '1');
	}
	if (!defined('NOREQUIREMENU')) {
		define('NOREQUIREMENU', '1');
	}
	if (!defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', '1');
	}
	if (!defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	if (!defined('NOREQUIRESOC')) {
		define('NOREQUIRESOC', '1');
	}
	if (!defined('CSRFCHECK_WITH_TOKEN')) {
		define('CSRFCHECK_WITH_TOKEN', '1');
	}

	// Dolibarr environment *************************
	require '../config.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	// Access control *******************************
	if (empty($user->id)) {
		httponly_accessforbidden('Login required', 401);
	}

	// Inputs ***************************************
	$varpage		= GETPOST('varpage', 'aZ09');
	$selectedfields	= GETPOST('selectedfields', 'alphanohtml');

	if (empty($varpage) || strpos($varpage, 'infraspackplus_') !== 0) {
		httponly_accessforbidden('Invalid varpage', 400);
	}

	// Actions **************************************
	$result	= dol_set_user_param($db, $conf, $user, array('MAIN_SELECTEDFIELDS_'.$varpage => $selectedfields));

	// View *****************************************
	top_httphead('application/json');
	print json_encode(array('result' => ($result > 0 ? 'OK' : 'KO')));
