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
	* 	\file		./infrasworkflow/core/tpl/admin_extrafields_view.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		Dispatcher : routes admin extrafields view to the matching versioned file under adminextrafields/
	************************************************/
	$major = (int) DOL_VERSION;
	if ($major >= 24) {
		$tplname = 'view_v24.tpl.php';
	} elseif ($major == 23) {
		$tplname = 'view_v23.tpl.php';
	} elseif ($major == 22) {
		$tplname = 'view_v22.tpl.php';
	} else {
		$tplname = 'view_v21.tpl.php';
	}
	include dol_buildpath('/infrasworkflow/core/tpl/adminextrafields/'.$tplname, 0);
