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
	* 	\file		./infrastructure/core/tpl/infrastructureline_view.tpl.php
	* 	\ingroup	infrastructure
	* 	\brief		Template d'affichage du libellé d'une ligne spéciale infrastructure (titre, sous-total, texte libre)
	*
	* Inclus depuis ActionsInfrastructure::printObjectLine() pour les 3 contextes de rendu :
	*   - 'document'  (défaut) : fiche devis/commande/facture/... — bloc 1 de printObjectLine.
	*                            Les sous-totaux sont dispatchés vers infrastructureline_infrastructure.tpl.php
	*                            (cellules quantité cumulée + marge + libellé aligné droite).
	*                            Boutons de pliage rendus pour les titres.
	*                            Style du libellé : INFRASTRUCTURE_TEXT_LINE_STYLE pour free text,
	*                            INFRASTRUCTURE_TITLE_STYLE pour les titres.
	*   - 'shipment' : page de création expédition (bloc 2) ou fiche shipping/delivery (bloc 3).
	*                  Rendu compact : single <td> pour tous les types y compris sous-totaux,
	*                  sans dispatch ni boutons de pliage. Sous-totaux indentés et alignés à droite.
	*                  Style du libellé : INFRASTRUCTURE_TITLE_STYLE pour tous les types.
	*                  Cas particulier : sur sous-total avec label vide et option
	*                  INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL active, le titre parent
	*                  est concaténé à la description.
	*
	* Variables disponibles via le scope local de la méthode appelante :
	*
	*   @var	CommonObject		$object						Document parent (propal, commande, facture...)
	*   @var	CommonObjectLine	$line						La ligne spéciale courante
	*   @var	string				$action						Action courante
	*   @var	int					$colspan					Colspan calculé (uniquement utilisé en contexte 'document')
	*   @var	bool				$line_show_qty				Afficher la quantité cumulée (contexte 'document' / sous-total)
	*   @var	float				$total_qty					Quantité totale du bloc
	*   @var	float				$total_line					Montant total HT du bloc
	*   @var	DoliDB				$db							Handler base de données
	*   @var	Conf				$conf						Configuration globale
	*   @var	Translate			$langs						Traductions
	*   @var	ActionsInfrastructure		$this				Instance de la classe hook
	*   @var	string				$infrastructureViewContext	(optionnel) 'document' (défaut) | 'shipment'
	************************************************/

	// Protection contre l'appel direct
	if (empty($conf) || ! is_object($conf)) {
		print "Error, template page can't be called as URL";
		exit;
	}

	// Libraries ************************************
	dol_include_once('/infrastructure/class/infrastructure.class.php');
	dol_include_once('/infrastructure/core/lib/infrastructure.lib.php');

	// Détermination du contexte de rendu (défaut : fiche document principale)
	$infrastructureViewContext	= isset($infrastructureViewContext) && $infrastructureViewContext === 'shipment' ? 'shipment' : 'document';

	// Dispatch vers le template dédié pour les sous-totaux (uniquement contexte 'document')
	if ($infrastructureViewContext === 'document' && TInfrastructure::isTotal($line)) {
		include dol_buildpath('/infrastructure/core/tpl/infrastructureline_infrastructure.tpl.php', 0);
		return;
	}
	// View *****************************************
	?>
<!-- BEGIN PHP TEMPLATE infrastructureline_view.tpl.php (context=<?php echo $infrastructureViewContext; ?>) -->
<?php
	// Cellule principale : libellé de la ligne
	$tdStyle	= TInfrastructure::isFreeText($line) ? '' : 'font-weight:bold;';
	if ($infrastructureViewContext === 'shipment' && $line->qty > 90) {
		$tdStyle	.= ' text-align:right;';
	}
	$tdAttr		= $infrastructureViewContext === 'document' ? 'colspan="'.$colspan.'" ' : '';
	print '<td '.$tdAttr.'style="'.$tdStyle.'">';
	// Icône de niveau (titre) ou indentation seule (sous-total en contexte shipment)
	if (TInfrastructure::isTitle($line)) {
		print str_repeat('&nbsp;&nbsp;&nbsp;', max(floatval($line->qty) - 1, 0));
		print '<i class="'.getDolGlobalString('MAIN_FONTAWESOME_ICON_STYLE').' fa-tenge" aria-hidden="true"></i>'.$line->qty.'&nbsp;&nbsp;';
	} elseif ($infrastructureViewContext === 'shipment' && TInfrastructure::isTotal($line)) {
		// En contexte shipment, indenter les sous-totaux selon leur niveau (sans icône)
		print str_repeat('&nbsp;&nbsp;&nbsp;', max(floatval(100 - $line->qty) - 1, 0));
	}
	// Styles du libellé (gras, italique, souligné)
	if ($infrastructureViewContext === 'shipment') {
		$style	= getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', '');	// En contexte shipment : INFRASTRUCTURE_TITLE_STYLE pour tous les types
	} else {
		$style	= TInfrastructure::isFreeText($line) ? getDolGlobalString('INFRASTRUCTURE_TEXT_LINE_STYLE', '') : getDolGlobalString('INFRASTRUCTURE_TITLE_STYLE', '');
	}
	$titleStyleItalic		= strpos($style, 'I') === false ? '' : ' font-style: italic;';
	$titleStyleBold			= strpos($style, 'B') === false ? '' : ' font-weight:bold;';
	$titleStyleUnderline	= strpos($style, 'U') === false ? '' : ' text-decoration: underline;';
	// Affichage du libellé
	if (empty($line->label)) {
		if ($infrastructureViewContext === 'shipment' && TInfrastructure::isTotal($line) && getDolGlobalInt('INFRASTRUCTURE_CONCAT_TITLE_LABEL_IN_INFRASTRUCTURE_LABEL')) {
			// Sous-total à label vide en contexte shipment : concaténer le titre parent à la description
			print dol_escape_htmltag($line->description).' '.dol_escape_htmltag(infrastructure_getTitle($object, $line));
		} else {
			print '		<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.dol_htmlentitiesbr($line->description).'</span>';
		}
	} else {
		if (getDolGlobalString('PRODUIT_DESC_IN_FORM') && !empty($line->description)) {
			$lineLabel	= $line->description != $line->label ? dol_escape_htmltag($line->label).'</span><br><div class="infrastructure_desc">'.dol_htmlentitiesbr($line->description) : dol_escape_htmltag($line->label);
			print '	<span class="infrastructure_label" style="'.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'">'.$lineLabel.'</div>';
		} else {
			print '	<span class="infrastructure_label classfortooltip" style=" '.$titleStyleItalic.$titleStyleBold.$titleStyleUnderline.'" title="'.dol_escape_htmltag($line->description).'">'.dol_escape_htmltag($line->label).'</span>';
		}
	}
	// Boutons de repliage pour les titres (uniquement en contexte 'document')
	if ($infrastructureViewContext === 'document' && TInfrastructure::isTitle($line)) {
		$titleAttr	= (array_key_exists('options_hideblock', $line->array_options) && $line->array_options['options_hideblock'] == 1) ? $langs->trans("Infrastructure_Show") : $langs->trans("Infrastructure_Hide");
		$folderIcon	= (array_key_exists('options_hideblock', $line->array_options) && $line->array_options['options_hideblock'] == 1) ? img_picto('', 'folder') : img_picto('', 'folder-open');
		print '	<span class="fold-infrastructure-container">';
		// Bouton 1 ("plier sans toucher aux titres enfants") : toujours visible. En mode 'default' c'est le seul bouton ;
		// en modes 'keepTitle' et 'hideAll' il garde les titres enfants visibles.
		print ' 	<span title="'.dol_escape_htmltag($titleAttr).'" class="fold-infrastructure-btn" data-toggle-all-children="0" data-title-line-target="'.$line->id.'" id="collapse-'.$line->id.'">';
		print 			$folderIcon;
		print '		</span>';
		// Bouton 2 ("plier tout") : visible uniquement en modes 'keepTitle' (hide récursif identique au bouton 1) ou 'hideAll'
		// (force le masquage de tout le contenu, y compris les sous-titres et leurs sous-totaux).
		if (in_array(getDolGlobalString('INFRASTRUCTURE_BLOC_FOLD_MODE'), array('keepTitle', 'hideAll'))) {
			print ' <span title="'.dol_escape_htmltag($titleAttr).'" class="fold-infrastructure-btn" data-toggle-all-children="1" data-title-line-target="'.$line->id.'" id="collapse-children-'.$line->id.'">';
			print 		$folderIcon;
			print '	</span>';
		}
		print ' 	<span class="fold-infrastructure-info" title="'.dol_escape_htmltag($langs->trans('InfrastructureNumberOfHiddenLines')).'" data-title-line-target="'.$line->id.'"></span>';
		print '</span>';
	}
	if ($line->info_bits > 0) {
		print img_picto($langs->trans('Pagebreak'), 'pagebreak@infrastructure');
	}
	print '</td>';
	?>
<!-- END PHP TEMPLATE infrastructureline_view.tpl.php (context=<?php echo $infrastructureViewContext; ?>) -->