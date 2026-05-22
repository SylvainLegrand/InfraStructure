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
	* 	\file		./infraspackplus/core/tpl/lineviews/_columns/total_ht.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		Partial : "Price total without tax" column with InfraS fix "Option in 2 columns"
	*               When special_code==3 (option line, no qty), the word "Option" is rendered
	*               in BOTH the local currency and the foreign currency Total HT cells (instead
	*               of a single cell with colspan=2 in Dolibarr core).
	*               Variables required from caller scope: $line, $sign, $object, $conf, $langs,
	*                   $coldisplay, $tooltiponprice, $tooltiponpriceend,
	*                   $tooltiponpricemultiprice, $tooltiponpriceendmultiprice
	************************************************/
	if ($line->special_code == 3) {
		print '<td class="linecolht nowrap right">'.$langs->trans('Option').'</td>';
		$coldisplay++;
		if (isModEnabled('multicurrency') && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
			print '<td class="linecoltotalht_currency nowrap right">'.$langs->trans('Option').'</td>';
			$coldisplay++;
		}
	} else {
		print '<td class="linecolht nowrap right">';
		$coldisplay++;
		print $tooltiponprice;
		print price($sign * $line->total_ht);
		print $tooltiponpriceend;
		print '</td>';
		if (isModEnabled('multicurrency') && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
			print '<td class="linecoltotalht_currency nowrap right">';
			print $tooltiponpricemultiprice;
			print price($sign * $line->multicurrency_total_ht);
			print $tooltiponpriceendmultiprice;
			print '</td>';
			$coldisplay++;
		}
	}
