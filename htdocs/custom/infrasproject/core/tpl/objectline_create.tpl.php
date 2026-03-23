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
	* 	\file		./infrasproject/core/tpl/objectline_create.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		change template from Dolibarr
	************************************************/
	$isV19p 	= version_compare(DOL_VERSION, '19.0.0') >= 0;
	$isV20p 	= version_compare(DOL_VERSION, '20.0.0') >= 0;
	$isV21p 	= version_compare(DOL_VERSION, '21.0.0') >= 0;
	$isV22p 	= version_compare(DOL_VERSION, '22.0.0') >= 0;
	$isV23p 	= version_compare(DOL_VERSION, '23.0.0') >= 0;
	$isV24p 	= version_compare(DOL_VERSION, '24.0.0') >= 0;
	$dolinfras	= getDolGlobalString('EASYA_VERSION', '') || getDolGlobalString('DOLINFRAS_VERSION', '');
	if ($isV24p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_create_24.tpl.php', 0);
	} elseif ($isV23p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_create_23.tpl.php', 0);
	} elseif ($isV22p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_create_22'.($dolinfras ? '-DolInfraS' : '').'.tpl.php', 0);
	} elseif ($isV21p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_create_21.tpl.php', 0);
	} elseif ($isV20p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_create_20.tpl.php', 0);
	} elseif ($isV19p) {
		include dol_buildpath('infrasproject/core/tpl/objectline_create_19.tpl.php', 0);
	} else {
		include dol_buildpath('infrasproject/core/tpl/objectline_create_18'.($dolinfras ? '-DolInfraS' : '').'.tpl.php', 0);
	}
