<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasproject/core/tpl/lineviews/_columns/refproject.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		Partial : reference project column (view mode) for supplier invoice lines
	*               Variables required from caller scope: $object, $line, $coldisplay
	************************************************/
	if ($object->element == 'invoice_supplier') {
		dol_include_once('/infrasproject/core/lib/infrasproject.lib.php');
		print '<td class="linecolrefproject">'.infrasproject_printprj($line->id).'</td>';
		$coldisplay++;
	}
