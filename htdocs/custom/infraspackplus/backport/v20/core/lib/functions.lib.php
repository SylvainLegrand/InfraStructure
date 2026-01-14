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
	*	\file		./infraspackplus/backport/v20/core/lib/functions.lib.php
	*	\ingroup	InfraS
	*	\brief		This file contains all frequently used functions.
	************************************************/

	/**
	*	Return the value of a $_GET or $_POST supervariable, converted into float.
	*
	*	@param string							$paramname		Name of the $_GET or $_POST parameter
	*	@param ''|'MU'|'MT'|'MS'|'CU'|'CT'|int	$rounding		Type of rounding ('', 'MU', 'MT, 'MS', 'CU', 'CT', integer) {@see price2num()}
	*	@return float											Value converted into float
	**/
	if (!function_exists('GETPOSTFLOAT')) {
		function GETPOSTFLOAT($paramname, $rounding = '')
		{
			// price2num() is used to sanitize any valid user input (such as "1 234.5", "1 234,5", "1'234,5", "1·234,5", "1,234.5", etc.)
			return (float) price2num(GETPOST($paramname), $rounding, 2);
		}
	}

