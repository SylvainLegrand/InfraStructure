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
	* 	\file		./infrastructure/core/tpl/lineol/row.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		Partial : <td> de la colonne « Opt » (Optionnel(le)s)
	*				Variables attendues depuis le scope appelant : $line, $module_number
	*				Lignes spéciales infrastructure (titre/sous-total/texte libre) : cellule vide
	*				Lignes standards : checkbox d'option
	************************************************/

	'@phan-var-force CommonObjectLine $line';

	$isInfrastructureLine	= (isset($line->special_code) && $line->special_code == $module_number && isset($line->product_type) && $line->product_type == 9);
	if ($isInfrastructureLine) {
		print '<td class="infrastructure_ol"></td>';
	} else {
		$checked	= !empty($line->array_options['options_infrastructure_ol']) ? ' checked="checked"' : '';
		print '<td class="infrastructure_ol">';
		print '<input type="checkbox" id="infrastructure_ol-'.((int) $line->id).'" class="infrastructure_ol_chkbx" data-lineid="'.((int) $line->id).'" value="1"'.$checked.' />';
		print '</td>';
	}
