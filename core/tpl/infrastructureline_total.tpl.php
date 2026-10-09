<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrastructure/core/tpl/infrastructureline_total.tpl.php
	* 	\ingroup	infrastructure
	* 	\brief		Template d'affichage d'une ligne sous-total (qty 90-99) en mode vue
	*
	* Inclus depuis infrastructureline_view.tpl.php quand TInfrastructure::isTotal($line) est vrai.
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
	*   @var	User				$user			Utilisateur courant (utilisé pour les permissions margins)
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
<!-- BEGIN PHP TEMPLATE infrastructureline_total.tpl.php -->
<?php
	// Détermine si les cellules marge seront rendues (juste avant Total HT, dans les colonnes Marge natives)
	// Aligné sur les permissions Dolibarr standard (cf. core/tpl/objectline_view.tpl.php) : module margin actif, utilisateur interne, droit margins.liretous, pas de masquage par le module affmarges.
	$displayMargin			= getDolGlobalString('INFRASTRUCTURE_DISPLAY_MARGIN_ON_TOTAL') && isModEnabled('margin') && !in_array($object->element, array('supplier_order', 'supplier_invoice', 'supplier_proposal')) && empty($user->socid) && !(isset($margins_hidden_by_module) && $margins_hidden_by_module) && !empty($user) && $user->hasRight('margins', 'liretous');
	// Colonne montant (linecolmargin1 native) : le prix de revient cumulé du bloc n'est affiché que si INFRASTRUCTURE_DISPLAY_COST_PRICE_ON_TOTAL est actif (remplace l'ancien affichage systématique de la marge brute, devenue inutile depuis l'ajout des taux ci-dessous)
	$displayCostPrice		= $displayMargin && getDolGlobalString('INFRASTRUCTURE_DISPLAY_COST_PRICE_ON_TOTAL');
	// Colonnes Marge % / Marque % natives (linecolmargin2 / linecolmark1) : rendues si les options Dolibarr correspondantes sont actives (cf. margin/admin/margin.php)
	$displayMarginRate		= $displayMargin && getDolGlobalString('DISPLAY_MARGIN_RATES');
	$displayMarkRate		= $displayMargin && getDolGlobalString('DISPLAY_MARK_RATES');
	$marginColsCount		= ($displayCostPrice ? 1 : 0) + ($displayMarginRate ? 1 : 0) + ($displayMarkRate ? 1 : 0);
	// Styles communs du libellé
	$style					= getDolGlobalString('INFRASTRUCTURE_TOTAL_STYLE', '');
	$titleStyleItalic		= strpos($style, 'I') === false ? '' : ' font-style: italic;';
	$titleStyleBold			= strpos($style, 'B') === false ? '' : ' font-weight:bold;';
	$titleStyleUnderline	= strpos($style, 'U') === false ? '' : ' text-decoration: underline;';
	// Construction du HTML du libellé "Sous-total :" (réutilisé dans les deux modes de rendu)
	ob_start();
	if (empty($line->label)) {
		if (getDolGlobalInt('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_TOTAL_LABEL')) {
			print $line->description.' <span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.infrastructure_getTitle($object, $line).'</span>';
		} else {
			print '	<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$line->description.'</span>';
		}
	} else {
		if (getDolGlobalString('PRODUIT_DESC_IN_FORM') && !empty($line->description)) {
			$lineLabel	= $line->description != $line->label ? $line->label.'</span><br><div class="infrastructure_desc">'.dol_htmlentitiesbr($line->description) : $line->label;
			if (getDolGlobalInt('INFRASTRUCTURE_SCREEN_CONCAT_TITLE_LABEL_IN_TOTAL_LABEL')) {
				$lineLabel	.= ' '.infrastructure_getTitle($object, $line);
			}
			print '	<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$lineLabel.'</div>';
		} else {
			$lineLabel	= $line->label;
			if (getDolGlobalInt('INFRASTRUCTURE_SCREEN_CONCAT_TITLE_LABEL_IN_TOTAL_LABEL')) {
				$lineLabel	.= ' '.infrastructure_getTitle($object, $line);
			}
			print '	<span class="infrastructure_label classfortooltip" style=" '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$lineLabel.'</span>';
		}
	}
	if (!empty($total_options) && getDolGlobalString('INFRASTRUCTURE_OL_SHOW_DETAILS')) {
		print ' <span class="infrastructure_label_options">('.$langs->trans('InfrastructureOptionalTotalInLabel', price($total_options)).')</span>';
	}
	print ' : ';
	if ($line->info_bits > 0) {
		echo img_picto($langs->trans('InfrastructurePagebreak'), 'pagebreak@infrastructure');
	}
	$labelHtml		= ob_get_clean();
	$alignedMode	= $line_show_qty && isset($colsBeforeQty) && $colsBeforeQty > 0 && ($colsBeforeQty + 1) <= $colspan;
	if ($alignedMode) {
		$colsAfterQty	= $colspan - $colsBeforeQty - 1 - $marginColsCount;
		print '	<td colspan="'.$colsBeforeQty.'" style="font-weight:bold;text-align:right;">'.$labelHtml.'</td>';
		print '	<td class="linecolqty nowraponall right" style="font-weight:bold;">'.price($total_qty, 0, '', 0, 0).'</td>';
		// Cellule(s) vide(s) entre la colonne Qté et la colonne Marge / Total HT
		if ($colsAfterQty > 0) {
			print '	<td colspan="'.$colsAfterQty.'">&nbsp;</td>';
		}
	} else {
		// Mode legacy (fallback) : grosse cellule "Qty : valeur" à gauche puis libellé "Sous-total :" à droite
		if ($line_show_qty) {
			$colspan	-= 2;
			$qtyStyleItalic		= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'I') === false ? '' : ' font-style: italic;';
			$qtyStyleBold		= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'B') === false ? '' : ' font-weight:bold;';
			$qtyStyleUnderline	= strpos(getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', ''), 'U') === false ? '' : ' text-decoration: underline;';
			print '	<td colspan="'.$colspan.'" style="text-align:right;'.$qtyStyleBold.'">
						<span class="infrastructure_label" style="'.$qtyStyleItalic.$qtyStyleBold.$qtyStyleUnderline.'">'.$langs->trans('Qty').' : </span>&nbsp;&nbsp;'.price($total_qty, 0, '', 0, 0);
			print '</td>';
			$colspan = 2;
		}
		$labelColspan	= $colspan - $marginColsCount;
		if ($labelColspan < 1) {
			$labelColspan	= 1;
		}
		print '	<td colspan="'.$labelColspan.'" style="font-weight:bold;text-align:right">';
		print $labelHtml;
		print '</td>';
	}
	// Cellule(s) marge (rendues uniquement si activées + module margin actif), juste avant Total HT, sans libellé « Marge : »
	// Prix de revient total (si INFRASTRUCTURE_DISPLAY_COST_PRICE_ON_TOTAL), puis taux de marge (Marge / prix d'achat) et/ou taux de marque (Marge / prix de vente)
	// suivant les options Dolibarr DISPLAY_MARGIN_RATES / DISPLAY_MARK_RATES, dans leur colonne native respective (cf. core/tpl/objectline_view.tpl.php ~L504-514)
	if ($marginColsCount > 0) {
		$parentTitleLine	= TInfrastructure::getParentTitleOfLine($object, $line->rang);
		$productLines		= TInfrastructure::getLinesFromTitleId($object, $parentTitleLine->id);
		$totalCostPrice		= 0;
		if (!empty($productLines)) {
			foreach ($productLines as $l) {
				if (!empty($l->array_options['options_infrastructure_ol'])) {
					continue; // Ligne optionnelle déjà exclue de $total_line : exclure aussi son coût de revient pour garder une marge cohérente
				}
				$totalCostPrice	+= $l->pa_ht * $l->qty;
			}
		}
		$marge	= $total_line - $totalCostPrice;
		if ($displayCostPrice) {
			print '	<td nowrap="nowrap" class="margininfos right" style="text-align:right;font-weight:bold;">'.price($totalCostPrice).'</td>';
		}
		if ($displayMarginRate) {
			$margeTx	= $totalCostPrice != 0 ? (100 * $marge) / $totalCostPrice : '';
			print '	<td nowrap="nowrap" class="margininfos right" style="text-align:right;font-weight:bold;">'.($totalCostPrice == 0 ? 'n/a' : price(price2num($margeTx, 'MT')).'%').'</td>';
		}
		if ($displayMarkRate) {
			$marqueTx	= $total_line != 0 ? (100 * $marge) / $total_line : '';
			print '	<td nowrap="nowrap" class="margininfos right" style="text-align:right;font-weight:bold;">'.($total_line == 0 ? 'n/a' : price(price2num($marqueTx, 'MT')).'%').'</td>';
		}
	}
	?>
<!-- END PHP TEMPLATE infrastructureline_total.tpl.php -->