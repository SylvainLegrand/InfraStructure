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
	* 	\file		./infrasproject/core/tpl/objectline_create.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		Dispatcher : routes Dolibarr's objectline_create.tpl.php to the matching versioned file under linecreates/
	************************************************/

	// Reached via the hook 'formAddObjectLine' (see ActionsInfrasproject::formAddObjectLine),
	// which calls CommonObject::formAddObjectLine() with $defaulttpldir = '/infrasproject/core/tpl'.
	// The native dispatcher loop then includes this file, which routes to the matching versioned tpl.
	$major		= (int) DOL_VERSION;
	$dolinfras	= getDolGlobalString('EASYA_VERSION', '') || getDolGlobalString('DOLINFRAS_VERSION', '');
	if ($major >= 24) {
		$tplname	= 'v24.tpl.php';
	} elseif ($major == 23) {
		$tplname	= 'v23.tpl.php';
	} elseif ($major == 22) {
		$tplname	= $dolinfras ? 'v22-DolInfraS.tpl.php' : 'v22.tpl.php';
	} elseif ($major == 21) {
		$tplname	= 'v21.tpl.php';
	} else {
		return 0;	// Dolibarr < 21 not supported, fallback to native template
	}
	include dol_buildpath('/infrasproject/core/tpl/linecreates/'.$tplname, 0);
