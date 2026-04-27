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
	* 	\file		./infrastructure/core/tpl/infrastructureline_infrastructure.tpl.php
	* 	\ingroup	infrastructure
	* 	\brief		Template d'affichage d'une ligne sous-total (qty 90-99) en mode vue
	*
	* Inclus depuis infrastructureline_view.tpl.php quand TInfrastructure::isInfrastructure($line) est vrai.
	* Gère : bloc quantité cumulée, bloc marge, cellule libellé alignée à droite avec séparateur ' : '.
	*
	* Variables disponibles via le scope local de la méthode appelante :
	*
	*   @var	CommonObject		$object			Document parent (propal, commande, facture...)
	*   @var	CommonObjectLine	$line			La ligne sous-total courante
	*   @var	string				$action			Action courante
	*   @var	int					$colspan		Colspan calculé (déjà ajusté par printObjectLine)
	*   @var	bool				$line_show_qty	Afficher la quantité cumulée
	*   @var	float				$total_qty		Quantité totale du bloc
	*   @var	float				$total_line		Montant total HT du bloc
	*   @var	DoliDB				$db				Handler base de données
	*   @var	Conf				$conf			Configuration globale
	*   @var	Translate			$langs			Traductions
	*   @var	ActionsInfrastructure		$this			Instance de la classe hook
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
	?>
<!-- BEGIN PHP TEMPLATE infrastructureline_infrastructure.tpl.php -->
<?php
	// Bloc quantité cumulée (réduit le colspan de 2)
	if ($line_show_qty) {
		$colspan				-= 2;
		$titleStyleItalic		= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'I') === false ? '' : ' font-style: italic;';
		$titleStyleBold			= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'B') === false ? '' : ' font-weight:bold;';
		$titleStyleUnderline	= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'U') === false ? '' : ' text-decoration: underline;';
		print '	<td colspan="'.$colspan.'" style="text-align:right;'.$titleStyleBold.'">
					<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$langs->trans('Qty').' : </span>&nbsp;&nbsp;'.price($total_qty, 0, '', 0, 0);
		print '</td>';
		$colspan = 2;
	}
	// Bloc marge
	if (getDolGlobalString('DISPLAY_MARGIN_ON_INFRASTRUCTURES')) {
		$colspan--;
		$titleStyleItalic		= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'I') === false ? '' : ' font-style: italic;';
		$titleStyleBold			= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'B') === false ? '' : ' font-weight:bold;';
		$titleStyleUnderline	= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'U') === false ? '' : ' text-decoration: underline;';
		print '	<td nowrap="nowrap" colspan="'.$colspan.'" style="text-align:right;font-weight:bold;">
					<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">Marge :</span>';
		$parentTitleLine		= TInfrastructure::getParentTitleOfLine($object, $line->rang);
		$productLines			= TInfrastructure::getLinesFromTitleId($object, $parentTitleLine->id);
		$totalCostPrice			= 0;
		if (!empty($productLines)) {
			foreach ($productLines as $l) {
				$product	= new Product($db);
				$res		= $product->fetch($l->fk_product);
				if ($res) {
					$totalCostPrice += $product->cost_price * $l->qty;
				}
			}
		}
		$marge = $total_line - $totalCostPrice;
		print '		&nbsp;&nbsp;'.price($marge);
		print '	</td>';
	}
	// Cellule principale : libellé aligné à droite
	$style					= getDolGlobalString('INFRASTRUCTURE_INFRASTRUCTURE_STYLE', '');
	$titleStyleItalic		= strpos($style, 'I') === false ? '' : ' font-style: italic;';
	$titleStyleBold			= strpos($style, 'B') === false ? '' : ' font-weight:bold;';
	$titleStyleUnderline	= strpos($style, 'U') === false ? '' : ' text-decoration: underline;';
	print '	<td'.(!getDolGlobalString('DISPLAY_MARGIN_ON_INFRASTRUCTURES') ? ' colspan="'.$colspan.'"' : '').' style="font-weight:bold;text-align:right">';
	// Affichage du libellé
	if (empty($line->label)) {
		if (getDolGlobalInt('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL')) {
			print $line->description.' <span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.infrastructure_getTitle($object, $line).'</span>';
		} else {
			print '	<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$line->description.'</span>';
		}
	} else {
		if (getDolGlobalString('PRODUIT_DESC_IN_FORM') && !empty($line->description)) {
			$lineLabel	= $line->description != $line->label ? $line->label.'</span><br><div class="infrastructure_desc">'.dol_htmlentitiesbr($line->description) : $line->label;
			print '	<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$lineLabel.'</div>';
		} else {
			print '	<span class="infrastructure_label classfortooltip" style=" '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
		}
	}
	print ' : ';
	if ($line->info_bits > 0) {
		echo img_picto($langs->trans('Pagebreak'), 'pagebreak@infrastructure');
	}
	echo '</td>';
	?>
<!-- END PHP TEMPLATE infrastructureline_infrastructure.tpl.php -->