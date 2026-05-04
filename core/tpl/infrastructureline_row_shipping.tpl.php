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
	* 	\file		./infrastructure/core/tpl/infrastructureline_row_shipping.tpl.php
	* 	\ingroup	infrastructure
	* 	\brief		Template du tr complet d'une ligne spéciale infrastructure (titre, sous-total, texte libre)
	*				en contexte fiche shipping (expédition existante) ou delivery (livraison).
	*
	* Inclus depuis ActionsInfrastructure::printObjectLine() — bloc 3.
	* Rend l'intégralité du <tr>...</tr> :
	*   - cellule numéro de ligne (si MAIN_VIEW_LINE_NUMBER)
	*   - cellule libellé (déléguée à infrastructureline_view.tpl.php en contexte 'shipment')
	*   - cellule colspan vide
	*   - cellule delete avec boutons « supprimer ligne » et « supprimer bloc » (uniquement si shipping draft + INFRASTRUCTURE_ALLOW_REMOVE_BLOCK)
	*   - ligne suivante d'extrafields shipping (si INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE)
	*
	* Variables disponibles via le scope local de la méthode appelante :
	*
	*   @var	CommonObject		$object			Document parent (Expedition / Delivery)
	*   @var	CommonObjectLine	$line			Ligne spéciale courante
	*   @var	string				$action			Action courante
	*   @var	array				$parameters		Paramètres du hook
	*   @var	HookManager			$hookmanager	Hook manager
	*   @var	int					$i				Index courant de la ligne dans la liste
	*   @var	string				$newToken		Jeton CSRF
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
	global $form, $bc, $var;
	$alreadysent		= $parameters['alreadysent'] ?? null;
	$shipment_static	= new Expedition($db);
	$warehousestatic	= new Entrepot($db);
	$extrafieldsline	= new ExtraFields($db);
	$extralabelslines	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	$colspan			= 4;
	if ($object->origin && $object->origin_id > 0) {
		$colspan++;
	}
	if (isModEnabled('stock')) {
		$colspan++;
	}
	if (isModEnabled('productbatch')) {
		$colspan++;
	}
	if ($object->statut == 0) {
		$colspan++;
	}
	if ($object->statut == 0 && !getDolGlobalString('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK')) {
		$colspan++;
	}
	if ($object->element == 'delivery') {
		$colspan = 2;
	}
	print '<!-- origin line id = '.$line->origin_line_id.' -->'; // id of order line
	// HTML 5 data for js
	$data	= infrastructure_getHtmlData($parameters, $object, $action, $hookmanager);
	$class	= infrastructure_getLineSpecialClass($line);
	?>
<!-- BEGIN PHP TEMPLATE infrastructureline_row_shipping.tpl.php -->
	<tr class="oddeven <?php echo $class; ?>" <?php echo $data; ?> rel="infrastructure" id="row-<?php echo $line->id ?>" style="<?php print infrastructure_getLineSpecialStyle($line); ?>">
	<?php
	// Cellule numéro de ligne
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		print '<td align="center">'.($i + 1).'</td>';
	}
	// Cellule libellé (template partagé)
	$infrastructureViewContext	= 'shipment';
	include dol_buildpath('/infrastructure/core/tpl/infrastructureline_view.tpl.php', 0);
	?>
	<td colspan="<?php echo $colspan; ?>">&nbsp;</td>
	<?php
	// Cellule delete (uniquement shipping en draft avec ALLOW_REMOVE_BLOCK)
	if ($object->element == 'shipping' && $object->statut == 0 && getDolGlobalString('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK')) {
		print '<td class="linecoldelete nowrap" width="10">';
		$lineid				= $line->id;
		$line->fk_prev_id	= empty($line->fk_prev_id) ? null : $line->fk_prev_id;
		if ($line->element === 'commandedet') {
			foreach ($object->lines as $shipmentLine) {
				if ((!empty($shipmentLine->fk_elementdet)) && $shipmentLine->fk_origin == 'orderline' && $shipmentLine->fk_elementdet == $line->id) {
					$lineid	= $shipmentLine->id;
				} elseif ((!empty($shipmentLine->fk_elementdet)) && $shipmentLine->fk_origin == 'orderline' && $shipmentLine->fk_elementdet == $line->id) {
					$lineid	= $shipmentLine->id;
				}
			}
		}
		if ($line->fk_prev_id === null) {
			$color		= getDolGlobalString('INFRASTRUCTURE_TITLE_COLOR', '000000');
			$img_delete	= img_delete('default', ' style="color:#'.$color.' !important;"');
			print '<a href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&amp;action=deleteline&amp;lineid='.((int) $lineid).'&token='.$newToken.'">'.$img_delete.'</a>';
		}
		if (TInfrastructure::isTitle($line) && ($line->fk_prev_id === null)) {
			$color		= getDolGlobalString('INFRASTRUCTURE_TITLE_COLOR_BLOC', 'be3535');
			$img_delete	= img_delete($langs->trans('InfrastructureDeleteWithAllLines'), ' style="color:#'.$color.' !important;" class="pictodelete pictodeleteallline"');
			print '<a href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&amp;action=ask_deleteallline&amp;lineid='.((int) $lineid).'&token='.$newToken.'">'.$img_delete.'</a>';
		}
		print '	</td>';
	}
	print "</tr>\r\n";
	// Display lines extrafields
	if ($object->element == 'shipping' && getDolGlobalString('INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE') && is_array($extralabelslines) && count($extralabelslines) > 0) {
		$line	= new ExpeditionLigne($db);
		$line->fetch_optionals($line->id);
		print '<tr class="oddeven">';
		print $line->showOptionals($extrafieldsline, 'view', array('style' => $bc[$var], 'colspan' => $colspan), $i);
	}
	?>
<!-- END PHP TEMPLATE infrastructureline_row_shipping.tpl.php -->
