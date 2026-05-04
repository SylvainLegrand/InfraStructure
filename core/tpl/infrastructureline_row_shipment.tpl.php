<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program. If not, see <https://www.gnu.org/licenses/>.
	*
	* SPDX-License-Identifier: GPL-3.0-or-later
	* This file is part of Dolibarr module Infrastructure
	**************************************************/

	/************************************************
	* 	\file		./infrastructure/core/tpl/infrastructureline_row_shipment.tpl.php
	* 	\ingroup	infrastructure
	* 	\brief		Template du tr complet d'une ligne spéciale infrastructure (titre, sous-total, texte libre)
	*				en contexte « création d'expédition depuis une commande » (commande + ordershipmentcard
	*				ou expeditioncard + action='create').
	*
	* Inclus depuis ActionsInfrastructure::printObjectLine() — bloc 2.
	* Rend l'intégralité du <tr>...</tr> : <td> libellé (délégué à infrastructureline_view.tpl.php en contexte
	* 'shipment') + <td colspan> contenant les inputs hidden nécessaires au formulaire de création
	* d'expédition (idl, qtyasked, qdelivered, qtyl, entl).
	*
	* Variables disponibles via le scope local de la méthode appelante :
	*
	*   @var	CommonObject		$object			Document parent (commande)
	*   @var	CommonObjectLine	$line			Ligne spéciale courante
	*   @var	string				$action			Action courante
	*   @var	array				$parameters		Paramètres du hook
	*   @var	HookManager			$hookmanager	Hook manager
	*   @var	array				$contexts		Liste des contextes du hook
	*   @var	int					$i				Index courant de la ligne dans la liste
	*   @var	DoliDB				$db				Handler base de données
	*   @var	Conf				$conf			Configuration globale
	*   @var	Translate			$langs			Traductions
	************************************************/

	// Protection contre l'appel direct
	if (empty($conf) || ! is_object($conf)) {
		print "Error, template page can't be called as URL";
		exit;
	}

	// Libraries ************************************
	dol_include_once('/infrastructure/class/infrastructure.class.php');
	dol_include_once('/infrastructure/core/lib/infrastructure.lib.php');

	// View *****************************************
	$colspan	= 4;
	$data		= infrastructure_getHtmlData($parameters, $object, $action, $hookmanager);
	$class		= infrastructure_getLineSpecialClass($line);
	?>
<!-- BEGIN PHP TEMPLATE infrastructureline_row_shipment.tpl.php -->
	<tr class="oddeven <?php echo $class; ?>" <?php echo $data; ?> rel="infrastructure" id="row-<?php echo $line->id ?>" style="<?php print infrastructure_getLineSpecialStyle($line); ?>">
	<?php
	$infrastructureViewContext	= 'shipment';
	include dol_buildpath('/infrastructure/core/tpl/infrastructureline_view.tpl.php', 0);
	?>
	<td colspan="<?php echo $colspan; ?>">
	<?php
		if (in_array('expeditioncard', $contexts) && $action == 'create') {
			$fk_entrepot	= GETPOST('entrepot_id', 'int');
			?>
				<input type="hidden" name="idl<?php echo $i; ?>" value="<?php echo $line->id; ?>" />
				<input type="hidden" name="qtyasked<?php echo $i; ?>" value="<?php echo $line->qty; ?>" />
				<input type="hidden" name="qdelivered<?php echo $i; ?>" value="0" />
				<input type="hidden" name="qtyl<?php echo $i; ?>" value="<?php echo $line->qty; ?>" />
				<input type="hidden" name="entl<?php echo $i; ?>" value="<?php echo $fk_entrepot; ?>" />
			<?php
		}
	?>
	</td>
	</tr>
<!-- END PHP TEMPLATE infrastructureline_row_shipment.tpl.php -->
