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
	* 	\file		./infrastechinfos/core/lib/infrastechinfos.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraS module
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

	/**
	* Output a duration with unit
	*
	* @param   float       $duration		duration
	* @param   int         $unit			Unit of duration (h, d, ...)
	* @return  string						String to show duration
	**/
	function infrastechinfos_showDurationAndUnit($duration, $unit)
	{
		global $langs;

		if ($duration && $unit) {
			if ($duration > 1) {
				$dur	= array('i' => $langs->trans('Minutes'),
								'h' => $langs->trans('Hours'),
								'd' => $langs->trans('Days'),
								'w' => $langs->trans('Weeks'),
								'm' => $langs->trans('Months'),
								'y' => $langs->trans('Years')
								);
			} else if ($duration > 0) {
				$dur	= array('i' => $langs->trans('Minute'),
								'h' => $langs->trans('Hour'),
								'd' => $langs->trans('Day'),
								'w' => $langs->trans('Week'),
								'm' => $langs->trans('Month'),
								'y' => $langs->trans('Year')
								);
			}
			$unittxt	= !empty($unit) && isset($dur[$unit]) ? $langs->trans($dur[$unit]) : '';
			return $duration.' '.$unittxt;
		}
		return '';
	}

	/**
	* Output a dimension with best unit
	*
	* @param   float       $dimension		Dimension
	* @param   int         $unit			Unit of dimension (0, -3, ...)
	* @param   string      $type			'weight', 'volume', 'surface', ...
	* @param   Translate   $outputlangs		Translate language object
	* @param   int         $round			-1 = non rounding, x = number of decimal
	* @param   string      $forceunitoutput	'no' or numeric (-3, -6, ...) compared to $unit
	* @return  string						String to show dimensions
	**/
	function infrastechinfos_showDimInBestUnit($dimension, $unit, $type, $outputlangs, $round = -1, $forceunitoutput = 'no')
	{
			if (($forceunitoutput == 'no' && $dimension < 1/10000) || (is_numeric($forceunitoutput) && $forceunitoutput == -6)) {
				$dimension	= $dimension * 1000000;
				$unit		= $unit - 6;
			} elseif (($forceunitoutput == 'no' && $dimension < 1/10) || (is_numeric($forceunitoutput) && $forceunitoutput == -3)) {
				$dimension	= $dimension * 1000;
				$unit		= $type != 'surface' ? $unit - 3 : $unit - 2;
			} elseif (($forceunitoutput == 'no' && $dimension > 100000000) || (is_numeric($forceunitoutput) && $forceunitoutput == 6)) {
				$dimension	= $dimension / 1000000;
				$unit		= $unit + 6;
			} elseif (($forceunitoutput == 'no' && $dimension > 100000) || (is_numeric($forceunitoutput) && $forceunitoutput == 3)) {
				$dimension	= $type != 'surface' ? $dimension / 1000 : $dimension / 10000;
				$unit		= $type != 'surface' ? $unit + 3 : $unit + 4;
			}
		$ret	= price($dimension, 0, $outputlangs, 0, 0, $round).' '.measuring_units_string($unit, $type);
		return $ret;
	}