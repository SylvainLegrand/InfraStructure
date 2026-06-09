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
	* 	\file		./infrasproject/core/tpl/linecreates/_columns/refproject_header.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		Partial : project column header (create mode, in title row of the add-line form)
	*               Variables required from caller scope: $object, $langs
	************************************************/
	if ($object->element == 'invoice_supplier') {
		?>
		<td class="linecolrefproject"><span id="title_project_ref"><?php echo $langs->trans('Project'); ?></span></td>
		<?php
	}
