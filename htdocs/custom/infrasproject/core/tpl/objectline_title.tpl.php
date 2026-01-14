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
	* 	\file		./infrasproject/core/tpl/objectline_title.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		change template from Dolibarr
	************************************************/
	$isV19p = version_compare(DOL_VERSION, '19.0.0') >= 0;	// InfraS add
	$isV20p = version_compare(DOL_VERSION, '20.0.0') >= 0;	// InfraS add
	$isV21p = version_compare(DOL_VERSION, '21.0.0') >= 0;	// InfraS add
	$isV22p = version_compare(DOL_VERSION, '22.0.0') >= 0;	// InfraS add
	if ($isV22p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_title_22.tpl.php', 0);
	} elseif ($isV21p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_title_21.tpl.php', 0);
	} elseif ($isV20p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_title_20.tpl.php', 0);
	} elseif ($isV19p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_title_19.tpl.php', 0);
	} else {
		include dol_buildpath('infrasproject/core/tpl/objectline_title_18.tpl.php', 0);
	}
