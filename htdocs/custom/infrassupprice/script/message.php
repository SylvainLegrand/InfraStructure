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
	* 	\file		../infrassupprice/script/message.php
	* 	\ingroup	InfraS
	* 	\brief		Page to use Dolibarr alert on the module InfraS supplier price
	************************************************/

	// Dolibarr environment *************************
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);
	}
	require '../config.php';

	// Access control *******************************
	if (!$user->hasRight('infrassupprice', 'update')) {
		accessforbidden();
	}

	// Translations *********************************
	$langs->load('infrassupprice@infrassupprice');

	$line	= GETPOST('line', 'int');
	if (empty($line) || $line == -1) {
		$line = 0;	// If $line is not defined, or '' or -1
	}
	$line	+= 1;
	$msg	= GETPOST('msg', 'aZ09');
	if ($msg =='Ok') {
		setEventMessages($langs->trans('InfraSSupPriceMajOk', (int) $line), null, 'mesgs');
	} elseif ($msg =='Idem') {
		setEventMessages($langs->trans('InfraSSupPriceMajIdem', (int) $line), null, 'warnings');
	} elseif ($msg =='Ko') {
		setEventMessages($langs->trans('InfraSSupPriceMajKo', (int) $line), null, 'errors');
	} elseif ($msg =='noCheck') {
		setEventMessages($langs->trans('InfraSSupPriceMajNoCheck'), null, 'errors');
	} else {
		setEventMessages($langs->trans('InfraSSupPriceMajKoElse', (int) $line, dol_escape_htmltag($msg)), null, 'errors');
	}
