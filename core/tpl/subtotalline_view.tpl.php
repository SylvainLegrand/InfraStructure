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
	* This file is part of Dolibarr module Subtotal
	**************************************************/

	/************************************************
	* 	\file		./subtotal/core/tpl/subtotalline_view.tpl.php
	* 	\ingroup	subtotal
	* 	\brief		Template d'affichage du libellé d'une ligne sous-total en mode vue (fiche document)
	*
	* Inclus depuis ActionsSubtotal::printObjectLine() dans le bloc else (mode vue).
	* Dispatche vers subtotalline_subtotal.tpl.php pour les lignes sous-total (qty 90-99).
	* Gère les titres (qty 0-9) et textes libres (qty 100+/50).
	* Les colonnes total HT, actions, </tr> et extrafields restent dans printObjectLine().
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
	*   @var	ActionsSubtotal		$this			Instance de la classe hook
	************************************************/

	// Protection contre l'appel direct
	if (empty($conf) || ! is_object($conf)) {
		print "Error, template page can't be called as URL";
		exit;
	}

	// Libraries ************************************
	dol_include_once('/subtotal/class/subtotal.class.php');
	dol_include_once('/subtotal/lib/subtotal.lib.php');

	// Dispatch vers le template dédié pour les sous-totaux
	if (TSubtotal::isSubtotal($line)) {
		include dol_buildpath('/subtotal/core/tpl/subtotalline_subtotal.tpl.php', 0);
		return;
	}
	// View *****************************************
	?>
<!-- BEGIN PHP TEMPLATE subtotalline_view.tpl.php -->
<?php
	// Cellule principale : libellé de la ligne (titres et textes libres uniquement)
	$style		= TSubtotal::isFreeText($line) ? '' : 'font-weight:bold;';
	print '<td colspan="'.$colspan.'" style="'.$style.'">';
	// Icône de niveau (nouveau format ou classique)
	if (getDolGlobalString('SUBTOTAL_USE_NEW_FORMAT')) {
		if (TSubtotal::isTitle($line)) {
			print str_repeat('&nbsp;&nbsp;&nbsp;', max(floatval($line->qty) - 1, 0));
			print img_picto('', 'subtotal@subtotal').'<span style="font-size:9px;margin-left:-3px;">'.$line->qty.'</span>&nbsp;&nbsp;';
		}
	} else {
		if ($line->qty <= 1) {
			print img_picto('', 'subtotal@subtotal');
		} elseif ($line->qty == 2) {
			print img_picto('', 'subsubtotal@subtotal').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
	}
	// Styles du libellé (gras, italique, souligné)
	$style					= TSubtotal::isFreeText($line) ? getDolGlobalString('SUBTOTAL_TEXT_LINE_STYLE', '') : getDolGlobalString('SUBTOTAL_TITLE_STYLE', '');
	$titleStyleItalic		= strpos($style, 'I') === false ? '' : ' font-style: italic;';
	$titleStyleBold			= strpos($style, 'B') === false ? '' : ' font-weight:bold;';
	$titleStyleUnderline	= strpos($style, 'U') === false ? '' : ' text-decoration: underline;';
	// Affichage du libellé
	if (empty($line->label)) {
		print '		<span class="subtotal_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$line->description.'</span>';
	} else {
		if (getDolGlobalString('PRODUIT_DESC_IN_FORM') && !empty($line->description)) {
			$lineLabel	= $line->description != $line->label ? $line->label.'</span><br><div class="subtotal_desc">'.dol_htmlentitiesbr($line->description) : $line->label;
			print '	<span class="subtotal_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$lineLabel.'</div>';
		} else {
			print '	<span class="subtotal_label classfortooltip" style=" '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.$line->description.'">'.$line->label.'</span>';
		}
	}
	// Boutons de repliage pour les titres
	if (TSubtotal::isTitle($line)) {
		$titleAttr	= (array_key_exists('options_hideblock', $line->array_options) && $line->array_options['options_hideblock'] == 1) ? $langs->trans("Subtotal_Show") : $langs->trans("Subtotal_Hide");
		print '	<span class="fold-subtotal-container">';
		print ' 	<span title="'.dol_escape_htmltag($titleAttr).'" class="fold-subtotal-btn" data-toggle-all-children="0" data-title-line-target="'.$line->id.'" id="collapse-'.$line->id.'">';
		print 			((array_key_exists('options_hideblock', $line->array_options) && $line->array_options['options_hideblock'] == 1) ? img_picto('', 'folder') : img_picto('', 'folder-open'));
		print '		</span>';
		print ' 	<span title="'.dol_escape_htmltag($titleAttr).'" class="fold-subtotal-btn" data-toggle-all-children="1" data-title-line-target="'.$line->id.'" id="collapse-children-'.$line->id.'">';
		print 			((array_key_exists('options_hideblock', $line->array_options) && $line->array_options['options_hideblock'] == 1) ? img_picto('', 'folder') : img_picto('', 'folder-open'));
		print '		</span>';
		print ' 	<span class="fold-subtotal-info" title="'.dol_escape_htmltag($langs->trans('SubTotalNumberOfHiddenLines')).'" data-title-line-target="'.$line->id.'"></span>';
		print '</span>';
	}
	if ($line->info_bits > 0) {
		print img_picto($langs->trans('Pagebreak'), 'pagebreak@subtotal');
	}
	print '</td>';
	?>
<!-- END PHP TEMPLATE subtotalline_view.tpl.php -->