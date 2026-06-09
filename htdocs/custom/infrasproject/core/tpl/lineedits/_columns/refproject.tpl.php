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
	* 	\file		./infrasproject/core/tpl/lineedits/_columns/refproject.tpl.php
	* 	\ingroup	InfraS
	* 	\brief		Partial : project selector column (edit mode) for supplier invoice lines
	*               Variables required from caller scope: $object, $line, $coldisplay
	************************************************/
	if ($object->element == 'invoice_supplier') {
		global $db;
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
		dol_include_once('/infrasproject/core/lib/infrasproject.lib.php');
		$formproject	= new FormProjets($db);
		$coldisplay++;
		?>
		<td><?php $formproject->select_projects(-1, infrasproject_printprj($line->id, 1), 'lineprojectid', 16, 0, 1, 0, 0, 0, 0, '', 0, 0, 'maxwidth200', ''); ?>
			<input type="hidden" name="infraslineedit" value="1">
		</td>
		<?php
	}
