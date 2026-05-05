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
	* 	\file		./infrastructure/core/tpl/infrastructureline_row_document.tpl.php
	* 	\ingroup	infrastructure
	* 	\brief		Template du tr complet d'une ligne spéciale infrastructure (titre, sous-total, texte libre)
	*				en contexte fiche document principale (devis, commande, facture, fact. récurrente, ou les
	*				équivalents fournisseurs).
	*
	* Inclus depuis ActionsInfrastructure::printObjectLine() — bloc 1.
	* Rend l'intégralité du <tr>...</tr> :
	*   - cellule numéro de ligne (si MAIN_VIEW_LINE_NUMBER)
	*   - cellule libellé (déléguée à infrastructureline_view.tpl.php en mode vue ou infrastructureline_edit.tpl.php en mode édition,
	*     dispatch vers infrastructureline_infrastructure.tpl.php pour les sous-totaux en mode vue)
	*   - cellule total HT (avec multicurrency)
	*   - cellule edit (clone + edit en mode vue, save + cancel en mode édition)
	*   - cellule delete (delete + delete-all sur les titres)
	*   - cellule NC checkbox (si INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS, titres uniquement)
	*   - cellule move (drag-drop)
	*   - cellule selectlines (mass actions)
	*   - ligne suivante d'extrafields titre (si INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE) avec script JS de show/hide
	*
	* Variables disponibles via le scope local de la méthode appelante :
	*
	*   @var	CommonObject		$object			Document parent (propal, commande, facture, supplier_proposal, order_supplier, invoice_supplier, facturerec)
	*   @var	CommonObjectLine	$line			Ligne spéciale courante
	*   @var	string				$action			Action courante
	*   @var	array				$parameters		Paramètres du hook
	*   @var	HookManager			$hookmanager	Hook manager
	*   @var	array				$contexts		Liste des contextes du hook
	*   @var	int					$num			Nombre total de lignes
	*   @var	int					$i				Index courant de la ligne dans la liste
	*   @var	bool				$createRight	Droit de création/édition sur l'élément courant
	*   @var	string				$idvar			'facid' pour facture, 'id' sinon
	*   @var	string				$newToken		Jeton CSRF
	*   @var	array				$toselect		Liste des ids sélectionnés (mass actions)
	*   @var	bool				$usercandelete	Droit de suppression
	*   @var	DoliDB				$db				Handler base de données
	*   @var	Conf				$conf			Configuration globale
	*   @var	Translate			$langs			Traductions
	*   @var	User				$user			Utilisateur courant
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
	$line->description		= empty($line->description) ? $line->desc : $line->description;
	$TNonAffectedByMarge	= array('order_supplier', 'invoice_supplier', 'supplier_proposal');
	$affectedByMarge		= in_array($object->element, $TNonAffectedByMarge) ? 0 : 1;
	$colspan				= 5;
	if ($object->element == 'order_supplier') {$colspan = 6;}
	if ($object->element == 'invoice_supplier') {$colspan = 4;}
	if ($object->element == 'supplier_proposal') {$colspan = 3;}
	if (DOL_VERSION > 16.0 && empty(getDolGlobalString('MAIN_NO_INPUT_PRICE_WITH_TAX'))) {
		$colspan++;	// Ajout de la colonne PU TTC
	}
	if ($object->element == 'facturerec') {$colspan = 5;}
	if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
		$colspan++;	// Colonne PU Devise
		if (DOL_VERSION > 16.0 && empty(getDolGlobalString('MAIN_NO_INPUT_PRICE_WITH_TAX'))) {
			$colspan++;	// Ajout de la colonne PU TTC
		}
	}
	if ($object->element == 'commande' && $object->statut < 3 && isModEnabled('shippableorder')) {$colspan++;}
	$margins_hidden_by_module	= !isModEnabled('affmarges') ? false : !($_SESSION['marginsdisplayed']);
	if (isModEnabled('margin') && !$margins_hidden_by_module) {$colspan++;}
	if (isModEnabled('margin') && getDolGlobalString('DISPLAY_MARGIN_RATES') && !$margins_hidden_by_module && $affectedByMarge > 0) {$colspan++;}
	if (isModEnabled('margin') && getDolGlobalString('DISPLAY_MARK_RATES') && !$margins_hidden_by_module && $affectedByMarge > 0) {$colspan++;}
	if ($object->element == 'facture' && getDolGlobalString('INVOICE_USE_SITUATION') && $object->type == Facture::TYPE_SITUATION) {$colspan++;}
	if (getDolGlobalString('PRODUCT_USE_UNITS')) {$colspan++;}
	// Compatibility module showprice
	if (isModEnabled('showprice')) {$colspan++;}
	$data	= infrastructure_getHtmlData($parameters, $object, $action, $hookmanager);
	$class	= infrastructure_getLineSpecialClass($line);
	?>
<!-- BEGIN PHP TEMPLATE infrastructureline_row_document.tpl.php -->
	<tr class="oddeven <?php echo $class; ?>" <?php echo $data; ?> rel="infrastructure" id="row-<?php echo $line->id ?>" style="<?php print infrastructure_getLineSpecialStyle($line); ?>">
	<?php if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) { ?>
		<td class="linecolnum"><?php echo $i + 1; ?></td>
	<?php } ?>
		<?php
		// Ajustements de colspan selon l'élément (post-ouverture pour la cellule libellé)
		if ($object->element == 'order_supplier') {
			$colspan--;
		}
		if ($object->element == 'supplier_proposal') {
			$colspan += 2;
		}
		if ($object->element == 'invoice_supplier') {
			$colspan -= 2;
		}
		// Pour les sous-totaux : pré-calculs du total + quantité cumulée (utilisés par infrastructureline_infrastructure.tpl.php)
		$line_show_qty	= false;
		if (TInfrastructure::isTotal($line)) {
			$TInfrastructureDatas		= infrastructure_get_totalLineFromObject($object, $line, false, 1);
			$total_line					= $TInfrastructureDatas[0];
			$multicurrency_total_line	= $TInfrastructureDatas[6];
			$total_qty					= $TInfrastructureDatas[4];
			if (($show_qty_by_default = TInfrastructure::showQtyForObject($object))) {	// Assignation et if sur le retour pour éviter d'appeler showQtyForObject() pour chaque ligne
				$line_show_qty	= TInfrastructure::showQtyForObjectLine($line, $show_qty_by_default);
			}
		}
		// Nombre de colonnes situées avant la colonne Qté (incluse).
		$colsBeforeQty	= 3;	// Description + VAT + PU HT (toujours présents)
		if (in_array($object->element, array('supplier_proposal', 'order_supplier', 'invoice_supplier'))) {
			$colsBeforeQty++;	// linecolrefsupplier rendu après Description pour les documents fournisseurs
		}
		if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
			$colsBeforeQty++;	// PU HT devise
			if (DOL_VERSION > 16.0 && empty(getDolGlobalString('MAIN_NO_INPUT_PRICE_WITH_TAX'))) {
				$colsBeforeQty++;	// PU TTC devise
			}
		}
		if (DOL_VERSION > 16.0 && empty(getDolGlobalString('MAIN_NO_INPUT_PRICE_WITH_TAX'))) {
			$colsBeforeQty++;	// PU TTC
		}
		// Cellule libellé : edit ou view selon le mode
		if ($action == 'editline' && GETPOST('lineid', 'int') == $line->id && TInfrastructure::isModInfrastructureLine($line)) {
			include dol_buildpath('/infrastructure/core/tpl/infrastructureline_edit.tpl.php', 0);
		} else {
			include dol_buildpath('/infrastructure/core/tpl/infrastructureline_view.tpl.php', 0);
		}
		// Cellule total HT (avec multicurrency)
		if ($line->qty > 90) {
			echo '<td class="linecolht nowrap" align="right" style="font-weight:bold;" rel="infrastructure_total">'.price($total_line).'</td>';
			if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
				echo '<td class="linecoltotalht_currency right bold">'.price($multicurrency_total_line).'</td>';
			}
		} else {
			echo '<td class="linecolht movetitleblock">&nbsp;</td>';
			if (isModEnabled('multicurrency') && ($object->multicurrency_code != $conf->currency)) {
				echo '<td class="linecoltotalht_currency">&nbsp;</td>';
			}
		}
		?>
	<td class="center nowrap linecoledit">						<?php
		if ($action != 'selectlines') {
			if ($action == 'editline' && GETPOST('lineid', 'int') == $line->id && TInfrastructure::isModInfrastructureLine($line)) {
				?>
				<input id="savelinebutton" class="button" type="submit" name="save" value="<?php echo $langs->trans('Save') ?>" />
				<br />
				<input class="button" type="button" name="cancelEditlinetitle" value="<?php echo $langs->trans('Cancel') ?>" />
				<script type="text/javascript">
					$(document).ready(function() {
						$('input[name=cancelEditlinetitle]').click(function () {
							document.location.href="<?php echo '?'.$idvar.'='.$object->id ?>";
						});
					});
				</script>
				<?php
			} else {
				if ($object->statut == 0 && $createRight && getDolGlobalString('INFRASTRUCTURE_ALLOW_DUPLICATE_BLOCK') && $object->element !== 'invoice_supplier') {
					if (empty($line->fk_prev_id)) $line->fk_prev_id = null;
					if (TInfrastructure::isTitle($line) && ($line->fk_prev_id === null)) {
						$color	= getDolGlobalString('INFRASTRUCTURE_TITLE_COLOR_BLOC', 'be3535');
						print '	<a class="infrastructure-line-action-btn" title="'.$langs->trans('InfrastructureCloneLInfrastructureBlock').'" href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=duplicate&lineid='.((int) $line->id).'&token='.$newToken.'" >
									<i class="'.getDolGlobalString('MAIN_FONTAWESOME_ICON_STYLE').' fa-clone" aria-hidden="true" style="color:#'.$color.' !important;"></i>
								</a>';
					}
				}
				if ($object->statut == 0 && $createRight && getDolGlobalString('INFRASTRUCTURE_ALLOW_EDIT_BLOCK')) {
					$color	= getDolGlobalString(TInfrastructure::isTitle($line) ? 'INFRASTRUCTURE_TITLE_COLOR' : 'INFRASTRUCTURE_TOTAL_COLOR', '000000');
					print '		<a class="infrastructure-line-action-btn"  href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=editline&token='.$newToken.'&lineid='.((int) $line->id).'#row-'.((int) $line->id).'">
									'.img_edit('default', 0, ' style="color:#'.$color.' !important;"').'
								</a>';
				}
			}
		}
		?>
	</td>
	<td class="center nowrap linecoldelete">						<?php
		if ($action != 'editline' && $action != 'selectlines') {
			if ($object->statut == 0 && $createRight && !empty(getDolGlobalString('INFRASTRUCTURE_ALLOW_REMOVE_BLOCK'))) {
				$line->fk_prev_id	= empty($line->fk_prev_id) ? null : $line->fk_prev_id;
				if (!isset($line->fk_prev_id) || $line->fk_prev_id === null) {
					$color		= getDolGlobalString(TInfrastructure::isTitle($line) ? 'INFRASTRUCTURE_TITLE_COLOR' : 'INFRASTRUCTURE_TOTAL_COLOR', '000000');
					$img_delete	= img_delete('default', ' style="color:#'.$color.' !important;"', '');
					print '	<a class="infrastructure-line-action-btn"  href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=ask_deleteline&lineid='.((int) $line->id).'&token='.$newToken.'">'.$img_delete.'</a>';
				}
				if (TInfrastructure::isTitle($line) && (!isset($line->fk_prev_id) || (isset($line->fk_prev_id) && ($line->fk_prev_id === null)))) {
					$color		= getDolGlobalString('INFRASTRUCTURE_TITLE_COLOR_BLOC', 'be3535');
					$img_delete	= img_delete($langs->trans('InfrastructureDeleteWithAllLines'), ' style="color:#'.$color.' !important;" class="pictodelete pictodeleteallline"');
					print '	<a class="infrastructure-line-action-btn"  href="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.$idvar.'='.((int) $object->id).'&action=ask_deleteallline&lineid='.((int) $line->id).'&token='.$newToken.'">'.$img_delete.'</a>';
				}
			}
		}
		?>
	</td>
	<?php
	// Cellule NC checkbox (titres uniquement, hors mode édition)
	if ($object->statut == 0 && $createRight && !empty(getDolGlobalString('INFRASTRUCTURE_MANAGE_COMPRIS_NONCOMPRIS')) && TInfrastructure::isTitle($line) && $action != 'editline' && $action != 'selectlines') {
		print '	<td class="infrastructure_nc">
					<input id="infrastructure_nc-'.$line->id.'" class="infrastructure_nc_chkbx" data-lineid="'.$line->id.'" type="checkbox" name="infrastructure_nc" value="1" '.(!empty($line->array_options['options_infrastructure_nc']) ? 'checked="checked"' : '').' />
				</td>';
	}
	// Cellule move (drag-drop)
	if ($num > 1 && empty($conf->browser->phone)) { ?>
		<td class="center linecolmove tdlineupdown">						</td>
	<?php } else { ?>
		<td <?php echo ((empty($conf->browser->phone) && ($object->statut == 0 && $createRight)) ? ' class="center tdlineupdown"' : ' class="center"'); ?>></td>					<?php } ?>
	<?php
	// Cellule selectlines (mass actions)
	$Telement	= array('propal', 'commande', 'facture', 'supplier_proposal', 'order_supplier', 'invoice_supplier');
	if (!empty(getDolGlobalString('MASSACTION_CARD_ENABLE_SELECTLINES')) && $object->status == $object::STATUS_DRAFT && $usercandelete && in_array($object->element, $Telement) || $action == 'selectlines') {	// dolibarr 8
		if ($action !== 'editline' && GETPOST('lineid', 'int') !== $line->id) {
			$checked	= '';
			if (!empty($toselect) && in_array($line->id, $toselect)) {
				$checked	= 'checked';
			}
			if ($action != 'editline') {
				?>
					<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>"></td>
				<?php
			}
		}
	}
	?>
	</tr>
	<?php
	// Affichage des extrafields à la Dolibarr (sinon non affiché sur les titres)
	if (TInfrastructure::isTitle($line) && getDolGlobalString('INFRASTRUCTURE_ALLOW_EXTRAFIELDS_ON_TITLE')) {
		$extrafieldsline	= new ExtraFields($db);
		$extralabelsline	= $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$mode				= $action === 'editline' && $line->rowid == GETPOST('lineid', 'int') ? 'edit' : 'view';
		$ex_element			= $line->element;
		$line->element		= 'tr_extrafield_title '.$line->element;	// Pour pouvoir manipuler ces tr
		$isExtraSelected	= false;
		$colspan			+= 3;
		print $line->showOptionals($extrafieldsline, $mode, array('style' => ' style="background:#eeffee;" ', 'colspan' => $colspan));
		foreach ($line->array_options as $option) {
			if (!empty($option) && $option != "-1") {
				$isExtraSelected = true;
				break;
			}
		}
		if ($mode === 'edit') {
			?>
			<script>
				$(document).ready(function () {
					var all_tr_extrafields = $("tr.tr_extrafield_title");
					<?php
					// Si un extrafield est rempli alors on affiche directement les extrafields
					if (!$isExtraSelected) {
						echo 'all_tr_extrafields.hide();';
						echo 'var trad = "'.$langs->trans('InfrastructureShowExtrafields').'";';
						echo 'var extra = 0;';
					} else {
						echo 'all_tr_extrafields.show();';
						echo 'var trad = "'.$langs->trans('InfrastructureHideExtrafields').'";';
						echo 'var extra = 1;';
					}
					?>
					$("div .infrastructure_underline").append(
						'<a id="printBlocExtrafields" onclick="return false;" href="#">' + trad + '</a>'
						+ '<input type="hidden" name="showBlockExtrafields" id="showBlockExtrafields" value="' + extra + '" />');
							$(document).on('click', "#printBlocExtrafields", function() {
								var btnShowBlock = $("#showBlockExtrafields");
								var val = btnShowBlock.val();
								if(val == '0') {
									btnShowBlock.val('1');
									$("#printBlocExtrafields").html("<?php print $langs->trans('InfrastructureHideExtrafields'); ?>");
									$(all_tr_extrafields).show();
								} else {
									btnShowBlock.val('0');
									$("#printBlocExtrafields").html("<?php print $langs->trans('InfrastructureShowExtrafields'); ?>");
									$(all_tr_extrafields).hide();
								}
					});
				});
			</script>
			<?php
		}
		$line->element = $ex_element;
	}
	?>
<!-- END PHP TEMPLATE infrastructureline_row_document.tpl.php -->
