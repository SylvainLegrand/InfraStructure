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
	* 	\file		./infrastructure/core/tpl/infrastructureline_edit.tpl.php
	* 	\ingroup	infrastructure
	* 	\brief		Template du formulaire d'edition d'une ligne speciale (titre, sous-total, texte libre)
	*
	* Inclus depuis ActionsInfrastructure::printObjectLine() en mode edition (action = editline).
	* Gere les trois types de lignes : titre (qty 0-9), sous-total (qty 90-99), texte libre (qty 50/100+).
	*
	* Variables disponibles via le scope local de la methode appelante :
	*
	*   @var	CommonObject		$object				Document parent (propal, commande, facture...)
	*   @var	CommonObjectLine	$line				La ligne speciale a editer
	*   @var	string				$action				Action courante ('editline')
	*   @var	int					$colspan			Colspan calcule (deja ajuste par printObjectLine)
	*   @var	HookManager			$hookmanager		Hook manager
	*   @var	array				$parameters			Parametres du hook (seller, buyer, context...)
	*   @var	bool				$show_qty_bu_deault	Resultat de TInfrastructure::showQtyForObject()
	*   @var	float				$line_show_qty		Quantite cumulee a afficher
	*   @var	DoliDB				$db					Handler base de donnees
	*   @var	Conf				$conf				Configuration globale
	*   @var	Translate			$langs				Traductions
	*   @var	ActionsInfrastructure		$this				Instance de la classe hook
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
<!-- BEGIN PHP TEMPLATE infrastructureline_edit.tpl.php -->
<?php
	print '	<td colspan="'.$colspan.'" style="'.(TInfrastructure::isFreeText($line) ? '' : 'font-weight:bold;').(($line->qty > 90) ? 'text-align:right' : '').'">';
	$params		= array('line' => $line);
	$reshook	= $hookmanager->executeHooks('formEditProductOptions', $params, $object, $action);
	print '		<div id="line_'.$line->id.'"></div>
					<input type="hidden" value="'.$line->id.'" name="lineid">
					<input id="product_type" type="hidden" value="'.$line->product_type.'" name="type">
					<input id="product_id" type="hidden" value="'.$line->fk_product.'" name="type">
					<input id="special_code" type="hidden" value="'.$line->special_code.'" name="type">';
	$isFreeText		= false;
	$qty_displayed	= 0;
	if (TInfrastructure::isTitle($line)) {
		$qty_displayed = $line->qty;
		print img_picto('', 'subinfrastructure@infrastructure').'<span style="font-size:9px;margin-left:-3px;color:#0075DE;">'.$qty_displayed.'</span>&nbsp;&nbsp;';
	} elseif (TInfrastructure::isInfrastructure($line)) {
		$qty_displayed = 100 - $line->qty;
		print img_picto('', 'subinfrastructure2@infrastructure').'<span style="font-size:9px;margin-left:-1px;color:#0075DE;">'.$qty_displayed.'</span>&nbsp;&nbsp;';
	} else {
		$isFreeText = true;
	}
	if ($object->element == 'order_supplier' || $object->element == 'invoice_supplier') {
		$line->label		= !empty($line->description) ? $line->description : $line->desc;
		$line->description	= '';
	}
	$newlabel = $line->label;
	if ($line->label == '' && !$isFreeText) {
		if (TInfrastructure::isInfrastructure($line)) {
			$newlabel			= $line->description.' '.infrastructure_getTitle($object, $line);
			$line->description	= '';
		}
	}
	$readonlyForSituation = '';
	if (empty($line->fk_prev_id)) {
		$line->fk_prev_id = null;
	}
	if (!empty($line->fk_prev_id) && $line->fk_prev_id != null) {
		$readonlyForSituation = 'readonly';
	}
	if (!$isFreeText) {
		print '		<input type="text" name="line-title" id-line="'.((int) $line->id).'" value="'.dol_escape_htmltag($newlabel).'" size="80" '.$readonlyForSituation.'/>&nbsp;';
	}
	if (getDolGlobalString('INFRASTRUCTURE_USE_NEW_FORMAT') && (TInfrastructure::isTitle($line) || TInfrastructure::isInfrastructure($line))) {
		$select	= '	<select name="infrastructure_level">';
		for ($j = 1; $j < 10; $j++) {
			if (!empty($readonlyForSituation)) {
				if ($qty_displayed == $j) {
					$select .= '<option selected="selected" value="'.$j.'">'.$langs->trans('InfrastructureLevel').' '.$j.'</option>';
				}
			} else {
				$select .= '	<option '.($qty_displayed == $j ? 'selected="selected"' : '').' value="'.$j.'">'.$langs->trans('InfrastructureLevel').' '.$j.'</option>';
			}
		}
		$select .= '</select>&nbsp;';
		print $select;
	}
	print '			<div class="infrastructure_underline" style="margin-left:24px; line-height: 25px;">';
	if (!getDolGlobalString('INFRASTRUCTURE_HIDE_OPTIONS_BREAK_PAGE_BEFORE')) {
		print '			<div>
							<input style="vertical-align:sub;" type="checkbox" name="line-pagebreak" id="infrastructure-pagebreak" value="8" '.(($line->info_bits > 0) ? 'checked="checked"' : '').' />&nbsp;
							<label for="infrastructure-pagebreak">'.$langs->trans('InfrastructureAddBreakPageBefore').'</label>
						</div>';
	}
	if (TInfrastructure::isTitle($line) && !getDolGlobalString('INFRASTRUCTURE_HIDE_OPTIONS_TITLE')) {
		if (!empty(isModEnabled('infraspackplus')) && in_array($object->element, array('propal', 'commande', 'facture'))) {
			print '		<div>
							<input style="vertical-align:sub;" type="checkbox" name="line-showTableHeaderBefore" id="infrastructure-showTableHeaderBefore" value="10" '.((!empty($line->array_options['options_show_table_header_before']) && $line->array_options['options_show_table_header_before'] > 0) ? 'checked="checked"' : '').' />&nbsp;
							<label for="infrastructure-showTableHeaderBefore">'.$langs->trans('InfrastructureShowTableHeaderBefore').'</label>
						</div>
						<div>
							<input style="vertical-align:sub;" type="checkbox" onclick="if($(this).is(\':checked\')) { $(\'#infrastructure-printCondensed\').prop(\'checked\', false) }" name="line-printAsList" id="infrastructure-printAsList" value="20" '.((!empty($line->array_options['options_print_as_list']) && $line->array_options['options_print_as_list'] > 0) ? 'checked="checked"' : '').' />&nbsp;
							<label for="infrastructure-printAsList">'.$langs->trans('InfrastructurePrintAsList').'</label>
						</div>
						<div>
							<input style="vertical-align:sub;" type="checkbox" onclick="if($(this).is(\':checked\')) { $(\'#infrastructure-printAsList\').prop(\'checked\', false) }" name="line-printCondensed" id="infrastructure-printCondensed" value="30" '.((!empty($line->array_options['options_print_condensed']) && $line->array_options['options_print_condensed'] > 0) ? 'checked="checked"' : '').' />&nbsp;
							<label for="infrastructure-printCondensed">'.$langs->trans('InfrastructurePrintCondensed').'</label>
						</div>';
		}
		$form	= new Form($db);
		print '			<div>
							<label for="infrastructure_tva_tx">'.$form->textwithpicto($langs->trans('InfrastructureApplyDefaultTva'), $langs->trans('InfrastructureApplyDefaultTvaHelp')).'</label>
							<select id="infrastructure_tva_tx" name="infrastructure_tva_tx" class="flat"><option selected="selected" value="">-</option>';
		if (empty($readonlyForSituation)) {
			print str_replace('selected', '', $form->load_tva('infrastructure_tva_tx', '', $parameters['seller'], $parameters['buyer'], 0, 0, '', true));
		}
		print '				</select>
						</div>';
		if (getDolGlobalString('INVOICE_USE_SITUATION') && $object->element == 'facture' && $object->type == Facture::TYPE_SITUATION) {
			print '		<div>
							<label for="infrastructure_progress">'.$langs->trans('InfrastructureApplyProgress').'</label> <input id="infrastructure_progress" name="infrastructure_progress" value="" size="1" />%
						</div>';
		}
		print '			<div>
							<input style="vertical-align:sub;" type="checkbox" name="line-showTotalHT" id="infrastructure-showTotalHT" value="9" '.((!empty($line->array_options['options_show_total_ht']) && $line->array_options['options_show_total_ht'] > 0) ? 'checked="checked"' : '').' />&nbsp;
							<label for="infrastructure-showTotalHT">'.$langs->trans('InfrastructureShowTotalHTOnInfrastructureBlock').'</label>
						</div>
						<div>
							<input style="vertical-align:sub;" type="checkbox" name="line-showReduc" id="infrastructure-showReduc" value="1" '.((!empty($line->array_options['options_show_reduc']) && $line->array_options['options_show_reduc'] > 0) ? 'checked="checked"' : '').' />&nbsp;
							<label for="infrastructure-showReduc">'.$langs->trans('InfrastructureShowReducOnInfrastructureBlock').'</label>
						</div>';
	} elseif ($isFreeText) {
		echo TInfrastructure::getFreeTextHtml($line, (bool) $readonlyForSituation);
	}
	if (TInfrastructure::isInfrastructure($line) && $show_qty_bu_deault = TInfrastructure::showQtyForObject($object)) {
		$line_show_qty = TInfrastructure::showQtyForObjectLine($line, $show_qty_bu_deault);
		print '			<div>
							<input style="vertical-align:sub;" type="checkbox" name="line-showQty" id="infrastructure-showQty" value="1" '.($line_show_qty ? 'checked="checked"' : '').' />&nbsp;
							<label for="infrastructure-showQty">'.$langs->trans('InfrastructureLineShowQty').'</label>
						</div>';
	}
	echo '</div>';
	if (TInfrastructure::isTitle($line)) {
		// WYSIWYG editor
		$nbrows			= ROWS_2;
		$cked_enabled	= (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS') ? getDolGlobalString('FCKEDITOR_ENABLE_DETAILS') : 0);
		if (getDolGlobalString('MAIN_INPUT_DESC_HEIGHT')) {
			$nbrows		= getDolGlobalString('MAIN_INPUT_DESC_HEIGHT');
		}
		$toolbarname	= 'dolibarr_details';
		if (getDolGlobalString('FCKEDITOR_ENABLE_DETAILS_FULL')) {
			$toolbarname	= 'dolibarr_notes';
		}
		$doleditor	= new DolEditor('line-description', $line->description, '', 100, $toolbarname, '', false, true, $cked_enabled, $nbrows, '98%', (bool) $readonlyForSituation);
		$doleditor->Create();
		$TKey		= null;
		if ($line->element == 'propaldet') {
			$TKey	= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET'));
		} elseif ($line->element == 'commandedet') {
			$TKey	= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET'));
		} elseif ($line->element == 'facturedet') {
			$TKey	= explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET'));
		}
		// TODO ajouter la partie fournisseur
		if (!empty($TKey)) {
			$extrafields	= new ExtraFields($this->db);
			$extrafields->fetch_name_optionals_label($object->table_element_line);
			if (!empty($extrafields->attributes[$line->element]['param'])) {
				foreach ($extrafields->attributes[$line->element]['param'] as $code => $val) {
					if (in_array($code, $TKey) && $extrafields->attributes[$line->element]['list'][$code] > 0) {
						print '	<div class="sub-'.$code.'">
									<label class="">'.$extrafields->attributes[$line->element]['label'][$code].'</label>';
						if (floatval(DOL_VERSION) >= 17) {
							print $extrafields->showInputField($code, $line->array_options['options_'.$code], '', '', 'infrastructure_', '', 0, $object->table_element_line);
						} else {
							print $extrafields->showInputField($code, $line->array_options['options_'.$code], '', '', 'infrastructure_');
						}
						print '</div>';
					}
				}
			}
		}
	}
	?>
<!-- END PHP TEMPLATE infrastructureline_edit.tpl.php -->