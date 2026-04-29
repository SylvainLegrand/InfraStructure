<?php
	/**************************************************
	* Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* along with this program.  If not, see <https://www.gnu.org/licenses/>.
	**************************************************/

	/***************************************************
	*	\file		./infrastructure/core/lib/infrastructure.lib.php
	*	\ingroup	infrastructure
	*	\brief		This file is an example module library
	*				Put some comments here
	**************************************************/

	// Libraries ****************************
	include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
	dol_include_once('/infrastructure/class/infrastructure.class.php');
	if (isModEnabled('ouvrage')) {
		dol_include_once('/ouvrage/class/ouvrage.class.php');
	}

	/**
	* Add numerotation to title and infrastructure lines of an object
	*
	* @param	CommonObject	$object	Object
	* @return	void
	*/
	function infrastructure_addNumerotation(&$object)
	{
		if (getDolGlobalInt('INFRASTRUCTURE_USE_NUMEROTATION')) {
			$TLineTitle		= $TTitle = $TLineInfrastructure = array();
			foreach ($object->lines as &$line) {
				if ($line->id > 0 && TInfrastructure::isModInfrastructureLine($line) && $line->qty <= 10) {
					$TLineTitle[] = &$line;
				} elseif ($line->id > 0 && TInfrastructure::isInfrastructure($line) && !getDolGlobalInt('INFRASTRUCTURE_USE_NEW_FORMAT')) {
					$TLineInfrastructure[] = &$line;
				}
			}
			if (!empty($TLineTitle)) {
				$TTitleNumeroted	= infrastructure_formatNumerotation($TLineTitle);
				$TTitle				= infrastructure_getTitlesFlatArray($TTitleNumeroted);
				if (!empty($TLineInfrastructure)) {
					foreach ($TLineInfrastructure as &$stLine) {
						$parentTitle = TInfrastructure::getParentTitleOfLine($object, $stLine->rang);
						if (!empty($parentTitle) && array_key_exists($parentTitle->id, $TTitle)) {
							$stLine->label = $TTitle[$parentTitle->id]['numerotation'].' '.$stLine->label;
						}
					}
				}
			}
		}
	}

	/**
	* Ajax block order JS
	*
	* @param CommonObject$object Object
	* @return void
	*/
	function infrastructure_ajaxBlockOrderJs($object)
	{
		global $conf, $tagidfortablednd, $filepath, $langs;

		$id					= $object->id;
		$nboflines			= (isset($object->lines) ? count($object->lines) : 0);
		$forcereloadpage	= !getDolGlobalString('MAIN_FORCE_RELOAD_PAGE') ? 0 : 1;
		$fk_element			= $object->fk_element;
		$table_element_line	= $object->table_element_line;
		$nboflines			= (isset($object->lines) ? count($object->lines) : (empty($nboflines) ? 0 : $nboflines));
		$tagidfortablednd	= (empty($tagidfortablednd) ? 'tablelines' : $tagidfortablednd);
		$filepath			= (empty($filepath) ? '' : $filepath);
		if (GETPOST('action', 'aZ09') != 'editline' && $nboflines > 1) {
			$jsConf	= array( 'useOldSplittedTrForLine' => intval(DOL_VERSION) < 16 ? 1 : 0);
			print '<script type="text/javascript" src="'.dol_buildpath('infrastructure/js/infrastructure.lib.js', 1).'"></script>';
			?>
			<script type="text/javascript">
				$(document).ready(function () {
					let infrastructureConf = <?php print json_encode($jsConf); ?>;
					// target some elements
					var titleRow = $('tr[data-isinfrastructure="title"]');
					var lastTitleCol = titleRow.find('td:last-child');
					var moveBlockCol = titleRow.find('td.linecolht');
					moveBlockCol.disableSelection(); // prevent selection
					<?php if ($object->statut == 0) { ?>
						// apply some graphical stuff
						moveBlockCol.html('<i class="fa fa-grip-vertical"></i>');
						moveBlockCol.css("text-align","center");
						moveBlockCol.css("cursor","move");
						titleRow.attr('title', '<?php echo html_entity_decode($langs->trans('InfrastructureMoveTitleBlock')); ?>');
						$( "#<?php echo $tagidfortablednd; ?>" ).sortable({
							cursor: "move",
							handle: ".movetitleblock",
							items: 'tr:not(.nodrag,.nodrop,.noblockdrop)',
							delay: 150, //Needed to prevent accidental drag when trying to select
							opacity: 0.8,
							axis: "y", // limit y axis
							placeholder: "ui-state-highlight",
							start: function( event, ui ) {
									let colCount = 0;
									let uiChildren = ui.item.children();
									colCount = uiChildren.length;
									if (uiChildren.length > 0) {
										uiChildren.each(function( index ) {
											let colspan = $( this ).attr('colspan');
											if(colspan != null && colspan != '' &&  parseFloat(colspan) > 1){
												colCount+= parseFloat(colspan);
											}
										});
									}
									ui.placeholder.html('<td colspan="'+colCount+'">&nbsp;</td>');
									var TcurrentChilds = getInfrastructureTitleChilds(ui.item);
									ui.item.data('childrens',TcurrentChilds); // store data
									for (var key in TcurrentChilds) {
										$('#'+ TcurrentChilds[key]).addClass('noblockdrop');//'#row-'+
										$('#'+ TcurrentChilds[key]).fadeOut();//'#row-'+
									}
									$(this).sortable("refresh");	// "refresh" of source sortable is required to make "disable" work!
								},
								stop: function (event, ui) {
									// call we element is droped
									$('.noblockdrop').removeClass('noblockdrop');
									var TcurrentChilds = ui.item.data('childrens'); // reload child list from data and not attr to prevent load error
									for (var i =TcurrentChilds.length ; i >= 0; i--) {
										$('#'+ TcurrentChilds[i]).insertAfter(ui.item); //'#row-'+
										$('#'+ TcurrentChilds[i]).fadeIn(); //'#row-'+
									}
									console.log('onstop');
									console.log(cleanSerialize($(this).sortable('serialize')));
									$.ajax({
										data: {
											objet_id: <?php print $object->id; ?>,
											roworder: cleanSerialize($(this).sortable('serialize')),
											table_element_line: "<?php echo $table_element_line; ?>",
											fk_element: "<?php echo $fk_element; ?>",
											element_id: "<?php echo $id; ?>",
											filepath: "<?php echo urlencode($filepath); ?>",
											token: "<?php echo currentToken(); ?>"
										},
										type: 'POST',
										url: '<?php echo DOL_URL_ROOT; ?>/core/ajax/row.php',
										success: function(data) {
											console.log(data);
										},
									});

								},
								update: function (event, ui) {
									// POST to server using $.post or $.ajax
									$('.noblockdrop').removeClass('noblockdrop');
									//console.log('onupdate');
									//console.log(cleanSerialize($(this).sortable('serialize')));
								}
						});
					<?php } ?>
				});
			</script>
			<style type="text/css">
				tr.ui-state-highlight td{
					border: 1px solid #dad55e;
					background: #fffa90;
					color: #777620;
				}
				.infrastructure-line-action-btn {
					margin-right: 5px;
				}
			</style>
			<?php
		}
	}

	/**
	* Add a checkbox on the bill orders forms (either the old orderstoinvoice or the new mass
	* action) to create a title block per invoiced order when creating one invoice per client.
	*
	* @return void
	*/
	function infrastructure_billOrdersAddCheckBoxForTitleBlocks()
	{
		global $delayedhtmlcontent, $langs, $conf;

		ob_start();
		$jsConf = array('langs' => array('AddTitleBlocFromOrdersToInvoice'		=> $langs->trans('InfrastructureAddTitleBlocFromOrderstoinvoice'),
										'AddShippingListToTile'					=> $langs->trans('InfrastructureAddShippingListToTile'),
										'InfrastructureOptions'					=> $langs->trans('InfrastructureOptions'),
										'UseHiddenConfToAutoCheck'				=> $langs->trans('InfrastructureUseHiddenConfToAutoCheck'),
									),
						'isModShippingEnable' 									=> isModEnabled('expedition'),
						'INFRASTRUCTURE_DEFAULT_CHECK_SHIPPING_LIST_FOR_TITLE_DESC'	=> getDolGlobalInt('INFRASTRUCTURE_DEFAULT_CHECK_SHIPPING_LIST_FOR_TITLE_DESC')
					);
		?>
		<script type="text/javascript">
			$(function () {
				let jsConf = <?php print json_encode($jsConf); ?>;

				let tr = '<tr><td>' + jsConf.langs.InfrastructureOptions + '</td><td>';
				tr += '<label><input type="checkbox" value="1" name="infrastructure_add_title_bloc_from_orderstoinvoice" checked="checked" /> ' + jsConf.langs.AddTitleBlocFromOrdersToInvoice + '</label>';
				if (jsConf.isModShippingEnable) {
					tr += '<br/><label><input type="checkbox" value="1" name="infrastructure_add_shipping_list_to_title_desc" /> ' + jsConf.langs.AddShippingListToTile + ' <i class="fa fa-question-circle" title="' + jsConf.langs.UseHiddenConfToAutoCheck + ' INFRASTRUCTURE_DEFAULT_CHECK_SHIPPING_LIST_FOR_TITLE_DESC"></label>';
				}
				tr += '<td></tr>';
				let $noteTextArea = $("textarea[name=note]");
				if ($noteTextArea.length === 1) {
					$noteTextArea.closest($('tr')).after(tr);
					return;
				}
				let $inpCreateBills = $("#validate_invoices");
				if ($inpCreateBills.length === 1) {
					$inpCreateBills.closest($('tr')).after(tr);
				}
			});
		</script>
		<?php
		$delayedhtmlcontent .= ob_get_clean();
	}

	/**
	* Create extrafield "infrastructure_nc" on document lines.
	*
	* @return  void
	*/
	function infrastructure_createExtraComprisNonCompris() {

		global $db;

		$extra = new ExtraFields($db); // propaldet, commandedet, facturedet
		$extra->addExtraField('infrastructure_nc', 'Non compris', 'varchar', 0, 255, 'propaldet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('infrastructure_nc', 'Non compris', 'varchar', 0, 255, 'commandedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('infrastructure_nc', 'Non compris', 'varchar', 0, 255, 'facturedet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('infrastructure_nc', 'Non compris', 'varchar', 0, 255, 'supplier_proposaldet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('infrastructure_nc', 'Non compris', 'varchar', 0, 255, 'commande_fournisseurdet', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
		$extra->addExtraField('infrastructure_nc', 'Non compris', 'varchar', 0, 255, 'facture_fourn_det', 0, 0, '', unserialize('a:1:{s:7:"options";a:1:{s:0:"";N;}}'), 0, '', 0, 1);
	}

	/**
	* Update a document line with infrastructure module specific behavior.
	*
	* @param	CommonObject		$object			Parent object (invoice, order, proposal, supplier, ...)
	* @param	CommonObjectLine	$line			Line to update
	* @param	int|bool			$infrastructure_nc	Flag to mark line as "non compris dans le sous-total"
	* @param	int					$notrigger		Disable triggers if set to 1
	* @return	int									<0 if KO, >0 if OK
	*/
	function infrastructure_doUpdate(&$object, &$line, $infrastructure_nc, $notrigger = 0)
	{
		global $user;

		if (TInfrastructure::isFreeText($line) || TInfrastructure::isInfrastructure($line)) return 1;
		// Update extrafield et total
		if(! empty($infrastructure_nc)) {
			$line->total_ht = $line->total_tva = $line->total_ttc = $line->total_localtax1 = $line->total_localtax2 =
			$line->multicurrency_total_ht = $line->multicurrency_total_tva = $line->multicurrency_total_ttc = $line->remise = 0;
			if(getDolGlobalString('INFRASTRUCTURE_NONCOMPRIS_UPDATE_PA_HT')) {
				$line->pa_ht = '0';
			}
			$line->array_options['options_infrastructure_nc'] = 1;
			if ($line->element == 'propaldet') {
				$res = $line->update($notrigger);
			} else {
				$res = $line->update($user, $notrigger);
			}
		} else {
			if(in_array($object->element, array('invoice_supplier', 'order_supplier', 'supplier_proposal'))) {
				if(empty($line->label)) {
					$line->label = $line->description; // supplier lines don't have the field label
				}
				$extrafields	= new ExtraFields($object->db);
				$extralabels	= $extrafields->fetch_name_optionals_label($object->table_element_line,true);
				$line->fetch_optionals($line->id,$extralabels);
			}
			$line->array_options['options_infrastructure_nc'] = 0;
			if($object->element == 'order_supplier') {
				$line->update($user);
			}
			$res = TInfrastructure::doUpdateLine($object, $line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->product_type, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit, $notrigger);
		}
		return $res;
	}

	/**
	* Return HTML select list of predefined free texts.
	*
	* @param	bool	$withEmpty	Add an empty option in the select list
	* @return	string				HTML code of the select input with JS handler
	*/
	function infrastructure_getHtmlSelectFreeText($withEmpty=true)
	{
		global $langs;

		$TFreeText	= infrastructure_getTFreeText();
		$html		= '<label for="free_text">'.$langs->trans('InfrastructureLabelForFreeText').'</label>';
		$html		.= '<select onChange="infrastructure_getTFreeText($(this));" name="free_text" class="minwidth200">';
		if ($withEmpty) {
			$html.= '<option value=""></option>';
		}
		$TFreeTextContents = array();
		foreach ($TFreeText as $id => $tab) {
			$html					.= '<option value="'.dol_escape_htmltag($id).'">'.dol_escape_htmltag($tab->label).'</option>';
			$TFreeTextContents[$id] = $tab->content;
		}
		$html .= '</select>';
		$html .= '<script type="text/javascript">';
		$html .= 'function infrastructure_getTFreeText(select) {';
		$html .= ' var TFreeText = '.json_encode($TFreeTextContents).';';
		$html .= ' var id = select.val();';
		$html .= ' if (id in TFreeText) {';
		$html .= '  var content = TFreeText[id];';
		$html .= '  if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && "sub-total-title" in CKEDITOR.instances) {';
		$html .= '   var editor = CKEDITOR.instances["sub-total-title"];';
		$html .= '   editor.setData(content);';
		$html .= '  } else {';
		$html .= '   $("#sub-total-title").val(content);';
		$html .= '  }';
		$html .= ' }';
		$html .= '}';
		$html .= '</script>';
		return $html;
	}

	/**
	* Return HTML select list of infrastructure titles available in the document.
	*
	* @param   CommonObject   $object     Object containing lines (invoice, propal, order, ...)
	* @param   bool           $showLabel  Add HTML label before select field
	* @return  string                     HTML code of the select input
	*/
	function infrastructure_getHtmlSelectTitle(&$object, $showLabel=false)
	{
		global $langs;

		$TTitle	= TInfrastructure::getAllTitleFromDocument($object);
		$html	= '';
		if ($showLabel) {
			$html	.= '<label for="under_title">'.$langs->trans('InfrastructureLabelForUnderTitle').'</label>';
		}
		$html	.= '<select onChange="$(\'select[name=under_title]\').val(this.value);" name="under_title" class="under_title minwidth200"><option value="-1"></option>';
		$nbsp	= '&nbsp;';
		foreach ($TTitle as &$line) {
			$str = '';
			if($line->qty > 1) {
				$str = str_repeat($nbsp, (floatval($line->qty) - 1) * 3);
			}
			$html .= '<option value="'.dol_escape_htmltag($line->id).'">'.dol_escape_htmltag($str.(!empty($line->label) ? $line->label : dol_trunc($line->desc, 30))).'</option>';
		}
		$html .= '</select>';
		return $html;
	}

	/**
	* Retrieve all active predefined free texts for current entity.
	*
	* @return	array	List of free text objects indexed by rowid
	*/
	function infrastructure_getTFreeText()
	{
		global $db,$conf;

		$TFreeText	= array();
		$sql		= 'SELECT rowid, label, content, active, entity FROM '.MAIN_DB_PREFIX.'c_infrastructure_free_text WHERE active = 1 AND entity = '.((int) $conf->entity).' ORDER BY label';
		$resql		= $db->query($sql);
		if ($resql) {
			while ($row = $db->fetch_object($resql)) {
				$TFreeText[$row->rowid] = $row;
			}
		}
		return $TFreeText;
	}

	/**
	* Add an extrafield if it does not already exist
	*
	* @param	string 		$attrname		Name of the extrafield attribute (without "options_" prefix)
	* @param	string 		$label			Label of the extrafield to display
	* @param	string 		$type			Type of the extrafield (varchar, int, date, ...)
	* @param	int			$pos			Position of the extrafield in the list of extrafields of the element
	* @param	string 		$size			Size of the extrafield (only for varchar)
	* @param	string 		$element_type	Type of element on which extrafield must be added (propaldet, commandedet, ...)
	* @param	int			$unique			Whether the extrafield must be unique or not
	* @param	int			$required		Whether the extrafield is required or not
	* @param	string		$default		Default value of the extrafield
	* @param	mixed		$param			Additional parameters of the extrafield (associative array, only for select and multiselect types, with "options" key containing options of the select)
	* @param	int			$alwayseditable	Whether the extrafield must be always editable or not (even if the line is closed/locked)
	* @param	string		$perms			Permissions for the extrafield (a combination of 'r', 'w', 'd' for read, write and delete permissions)
	* @param	string		$list			Whether the extrafield must be displayed in the list view or not (1 or 0)
	* @param	string		$help			Help text for the extrafield
	* @param	string		$computed		Code to compute the value of the extrafield (only for computed fields)
	* @param	string		$entity			Entity for which the extrafield must be added
	* @param	string		$langfile		Lang file to use for the extrafield label and options (without ".lang" suffix)
	* @param	string		$enabled		Whether the extrafield must be enabled or not (1 or 0)
	* @param	int			$totalizable	Whether the extrafield must be totalizable or not (1 or 0)
	* @param	int			$printable		Whether the extrafield must be printable or not (1 or 0)
	* @param	array		$moreparams		More parameters (reserved for future use, can be empty)
	* @param	string		$aiprompt		Prompt to use for AI generation (only for AI generated fields)
	* @return	int							1 if extrafield has been added, 0 if extrafield already exists, <0 if error occurred
	*/
	function infrastructure_addExtraField($attrname, $label, $type, $pos, $size, $element_type, $unique = 0, $required = 0, $default = '', $param = '', $alwayseditable = 0, $perms = '', $list = '-1', $help = '', $computed = '', $entity = '', $langfile = '', $enabled = '1', $totalizable = 0, $printable = 0, $moreparams = array(), $aiprompt = '') {

		global $db;

		$extra		= new ExtraFields($db);
		$extra->fetch_name_optionals_label($element_type);
		$existing	= (!empty($extra->attributes[$element_type]['label']) && is_array($extra->attributes[$element_type]['label'])) ? array_keys($extra->attributes[$element_type]['label']) : array();
		if (in_array($attrname, $existing, true)) {
			return 0; // déjà existant
		}
		return $extra->addExtraField($attrname, $label, $type, $pos, $size, $element_type, $unique, $required, $default, $param, $alwayseditable, $perms, $list, $help, $computed, $entity, $langfile, $enabled, $totalizable, $printable, $moreparams, $aiprompt);
	}

	/**
	* Get title
	*
	* @param CommonObject $object Object
	* @param CommonObjectLine $currentLine Current line
	* @return string
	*/
	function infrastructure_getTitle(&$object, &$currentLine)
	{
		$res	= '';
		foreach ($object->lines as $line) {
			if ($line->id == $currentLine->id) {break;}
			$qty_search	= 100 - $currentLine->qty;
			if ($line->product_type == 9 && $line->special_code == TInfrastructure::$module_number && $line->qty == $qty_search) {
				$res	= ($line->label) ? $line->label : (($line->description) ? $line->description : $line->desc);
			}
		}
		return $res;
	}

	/**
	* Print new format
	*
	* @param	CommonObject	$object		Object
	* @param	Conf			$conf		Conf
	* @param	Translate		$langs		Langs
	* @param	string			$idvar		Id var
	* @return	bool|void
	*/
	function infrastructure_printNewFormat(&$object, &$conf, &$langs, $idvar)
	{
		if (!getDolGlobalString('INFRASTRUCTURE_ALLOW_ADD_BLOCK')) {return false;}

		$jsData = array('conf' => array('INFRASTRUCTURE_USE_NEW_FORMAT'	=> getDolGlobalInt('INFRASTRUCTURE_USE_NEW_FORMAT'),
										'MAIN_VIEW_LINE_NUMBER'		=> getDolGlobalInt('MAIN_VIEW_LINE_NUMBER'),
										'token'						=> newToken(),
										'groupBtn'					=> intval(DOL_VERSION) < 20.0 || getDolGlobalInt('INFRASTRUCTURE_FORCE_EXPLODE_ACTION_BTN') ? 0 : 1
									),
						'langs' => array('Level'					=> $langs->trans('InfrastructureLevel'),
										'Position'					=> $langs->transnoentities('Position'),
										'AddTitle'					=> $langs->trans('InfrastructureAddTitle'),
										'AddInfrastructure'				=> $langs->trans('InfrastructureAddInfrastructure'),
										'AddFreeText'				=> $langs->trans('InfrastructureAddFreeText'),
									)
					);
		$jsData['buttons'] = dolGetButtonAction('', $langs->trans('InfrastructuresAndTitlesActionBtnLabel'), 'default', [
			['attr' => ['rel' => 'add_title_line'], 'id' => 'add_title_line', 'urlraw' => '#', 'label' => $langs->trans('InfrastructureAddTitle'), 'perm' => 1],
			['attr' => ['rel' => 'add_total_line'], 'id' => 'add_total_line', 'urlraw' => '#', 'label' => $langs->trans('InfrastructureAddInfrastructure'), 'perm' => 1],
			['attr' => ['rel' => 'add_free_text'], 'id' => 'add_free_text', 'urlraw' => '#', 'label' => $langs->trans('InfrastructureAddFreeText'), 'perm' => 1],
		], 'infrastructure-actions-buttons-dropdown');
		if (empty($jsData['conf']['groupBtn'])) {
			$jsData['buttons'] = '<div class="inline-block divButAction"><a id="add_title_line" rel="add_title_line" href="javascript:;" class="butAction">'.$langs->trans('InfrastructureAddTitle').'</a></div>';
			$jsData['buttons'] .= '<div class="inline-block divButAction"><a id="add_total_line" rel="add_total_line" href="javascript:;" class="butAction">'.$langs->trans('InfrastructureAddInfrastructure').'</a></div>';
			$jsData['buttons'] .= '<div class="inline-block divButAction"><a id="add_free_text" rel="add_free_text" href="javascript:;" class="butAction">'.$langs->trans('InfrastructureAddFreeText').'</a></div>';
		}
		?>
			<!-- Infrastructure action printNewFormat -->
			<script type="text/javascript">
				$(document).ready(function() {
					let jsInfrastructureData = <?php print json_encode($jsData); ?>;
					if (jsInfrastructureData.conf.groupBtn == 0) {
						let targetContainer;
						if ($("div.fiche div.tabsAction > .butAction").length) {
							targetContainer = $("div.fiche div.tabsAction");
						} else {
							targetContainer = $("div.fiche div.tabsAction > .divButAction").length
								? $("div.fiche div.tabsAction")
								: $("div.fiche div.tabsAction");
						}
						targetContainer.append('<br />');
						targetContainer.append(jsInfrastructureData.buttons);
					} else {
						let elementsButon;
						elementsButon = $("div.fiche div.tabsAction > .butAction").length
							? $("div.fiche div.tabsAction > .butAction")
							: $("div.fiche div.tabsAction > .divButAction");

						$(jsInfrastructureData.buttons).insertBefore(elementsButon.first());
					}
					function updateAllMessageForms(){
						for (instance in CKEDITOR.instances) {
							CKEDITOR.instances[instance].updateElement();
						}
					}
					function promptInfrastructure(action, titleDialog, label, url_to, url_ajax, params, use_textarea, show_free_text, show_under_title) {
						$( "#dialog-prompt-infrastructure" ).remove();
							var dialog_html = '<div id="dialog-prompt-infrastructure" ' + (action == 'addInfrastructure' ? 'class="center"' : '') + ' >';
							dialog_html += '<input id="token" name="token" type="hidden" value="' + jsInfrastructureData.conf.token + '" />';
							if (typeof show_under_title != 'undefined' && show_under_title) {
								var selectUnderTitle = <?php echo json_encode(infrastructure_getHtmlSelectTitle($object, true)); ?>;
								dialog_html += selectUnderTitle + '<br /><br />';
							}
							if (action == 'addTitle' || action == 'addFreeTxt') {
								if (typeof show_free_text != 'undefined' && show_free_text) {
									var selectFreeText = <?php echo json_encode(infrastructure_getHtmlSelectFreeText()); ?>;
									dialog_html += selectFreeText + ' <?php echo $langs->transnoentities('InfrastructureFreeTextOrDesc'); ?><br />';
								}
								if (typeof use_textarea != 'undefined' && use_textarea) dialog_html += '<textarea id="sub-total-title" rows="<?php echo ROWS_8; ?>" cols="80" placeholder="' + label + '"></textarea>';
								else dialog_html += '<input id="sub-total-title" size="30" value="" placeholder="' + label + '" />';
							}
							if (action == 'addInfrastructure') {
								dialog_html += '<input id="sub-total-title" size="30" value="" placeholder="' + label + '" />';
							}

							if (jsInfrastructureData.conf.MAIN_VIEW_LINE_NUMBER) {
								dialog_html += '&emsp;<input style="max-width: 80px;" id="infrastructure_line_position" name="infrastructure_line_position" type="number" min="0" step="1" size="1" text-align="right" placeholder="' + jsInfrastructureData.langs.Position + '" />';
							}
							if (action == 'addTitle' || action == 'addInfrastructure') {

								if (jsInfrastructureData.conf.INFRASTRUCTURE_USE_NEW_FORMAT){
									dialog_html += '&emsp;<select name="infrastructure_line_level">';
									for (var i=1;i<10;i++){
										dialog_html += '<option value="' + i + '">' + jsInfrastructureData.langs.Level + ' ' + i + '</option>';
									}
									dialog_html += "</select>";
								} else {
									dialog_html += '<input type="hidden" name="infrastructure_line_level" value="' + i + '" />';
								}
							}
							dialog_html += '</div>';
							$('body').append(dialog_html);
								<?php
								$editorTool = getDolGlobalString('FCKEDITOR_EDITORNAME', 'ckeditor');
								$editorConf = empty(getDolGlobalString('FCKEDITOR_ENABLE_DETAILS')) ? false : getDolGlobalString('FCKEDITOR_ENABLE_DETAILS');
								if ($editorConf && in_array($editorTool, array('textarea','ckeditor'))) {
									?>
								if (action == 'addTitle' || action == 'addFreeTxt') {
									if (typeof use_textarea != 'undefined' && use_textarea && typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined") {
										CKEDITOR.replace('sub-total-title', {
											toolbar: 'dolibarr_details',
											versionCheck: false,
											toolbarStartupExpanded: false,

											// Intégration du filemanager via les variables JS de Dolibarr
											filebrowserBrowseUrl: ckeditorFilebrowserBrowseUrl,
											filebrowserImageBrowseUrl: ckeditorFilebrowserImageBrowseUrl,
											// filebrowserUploadUrl: DOL_URL_ROOT + '/includes/fckeditor/editor/filemanagerdol/connectors/php/upload.php?Type=File',
											// filebrowserImageUploadUrl: DOL_URL_ROOT + '/includes/fckeditor/editor/filemanagerdol/connectors/php/upload.php?Type=Image',

											// Dimensions des fenêtres popup
											filebrowserWindowWidth: '900',
											filebrowserWindowHeight: '500',
											filebrowserImageWindowWidth: '900',
											filebrowserImageWindowHeight: '500'
										});
									}
								}
							<?php } ?>
							$( "#dialog-prompt-infrastructure" ).dialog({
								resizable: false,
								height: 'auto',
								width: 'auto',
								modal: true,
								title: titleDialog,
								buttons: {
									"Ok": function() {
										if (typeof use_textarea != 'undefined' && use_textarea && typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" ){ updateAllMessageForms(); }
										params.rank = 0;
										if($(this).find('#infrastructure_line_position').length > 0){
											params.rank = $(this).find('#infrastructure_line_position').val();
										}

									params.title = (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && "sub-total-title" in CKEDITOR.instances ? CKEDITOR.instances["sub-total-title"].getData() : $(this).find('#sub-total-title').val());
									params.under_title = $(this).find('select[name=under_title]').val();
									params.free_text = $(this).find('select[name=free_text]').val();
									params.level = $(this).find('select[name=infrastructure_line_level]').val();
									params.token = $(this).find('input[name=token]').val();

									let microtime = new Date();
									url_to += "&microtime=" + microtime.getTime(); // to avoid # ancor blocking refresh by adding same rank as curent

									$.ajax({
										url: url_ajax
										, type: 'POST'
										, data: params
										, dataType: "html"
									}).done(function (response) {
										if (jsInfrastructureData.conf.MAIN_VIEW_LINE_NUMBER == 1) {
											newlineid = $($.parseHTML(response)).find("#newlineid").text();
											url_to = url_to + "&gotoline=" + params.rank + "#row-" + newlineid;
										} else {
											url_to = url_to + "&gotoline=" + params.rank + "#tableaddline";
										}
										document.location.href = url_to;
									});

										$( this ).dialog( "close" );
									},
									"<?php echo $langs->trans('Cancel') ?>": function() {
										$( this ).dialog( "close" );
									}
								}
							});
					}
					$('a[rel=add_title_line]').click(function (e) {
						e.preventDefault();
						promptInfrastructure('addTitle'
							, "<?php echo $langs->trans('InfrastructureYourTitleLabel') ?>"
							, "<?php echo $langs->trans('InfrastructureTitle'); ?>"
							, '?<?php echo $idvar ?>=<?php echo $object->id; ?>'
							, '<?php echo $_SERVER['PHP_SELF']; ?>'
							, {<?php echo $idvar; ?>: <?php echo (int)$object->id; ?>,
						action:'add_title_line'
					}
					)
						;
					});
					$('a[rel=add_total_line]').click(function (e) {
						e.preventDefault();
						promptInfrastructure('addInfrastructure'
							, '<?php echo $langs->trans('InfrastructureYourInfrastructureLabel') ?>'
							, '<?php echo $langs->trans('Infrastructure'); ?>'
							, '?<?php echo $idvar ?>=<?php echo $object->id; ?>'
							, '<?php echo $_SERVER['PHP_SELF']; ?>'
							, {<?php echo $idvar; ?>: <?php echo (int)$object->id; ?>,
						action:'add_total_line'
							}
						/*,false,false, <?php echo getDolGlobalString('INFRASTRUCTURE_ALLOW_ADD_LINE_UNDER_TITLE') ? 'true' : 'false'; ?>*/
						);
					});
					$('a[rel=add_free_text]').click(function (e) {
						e.preventDefault();
						promptInfrastructure('addFreeTxt',
							"<?php echo $langs->transnoentitiesnoconv('InfrastructureYourTextLabel') ?>",
							"<?php echo $langs->trans('InfrastructureAddLineDescription'); ?>",
							'?<?php echo $idvar ?>=<?php echo $object->id; ?>',
							'<?php echo $_SERVER['PHP_SELF']; ?>', {
								<?php echo $idvar; ?>: <?php echo (int)$object->id; ?>,action:'add_free_text'
							},
							true,
							true,
							<?php echo getDolGlobalString('INFRASTRUCTURE_ALLOW_ADD_LINE_UNDER_TITLE') ? 'true' : 'false'; ?>
						);
					});
				});
			</script>
		<?php
	}

	/**
	* Update a infrastructure or title line with its display options.
	*
	* @param	CommonObject		$object		Parent object (invoice, order, proposal, ...)
	* @param	CommonObjectLine	$line		Infrastructure or title line to update
	* @return	int								<0 if KO, >0 if OK
	*/
	function infrastructure_updateInfrastructureLine(&$object, &$line)
	{

		$label					= GETPOST('line-title', 'restricthtml');
		$description			= ($line->qty>90) ? '' : GETPOST('line-description', 'restricthtml');
		$pagebreak				= GETPOST('line-pagebreak', 'int');
		$showTableHeaderBefore	= GETPOST('line-showTableHeaderBefore', 'int');
		$printAsList			= GETPOST('line-printAsList', 'int');
		$printCondensed			= GETPOST('line-printCondensed', 'int');
		$showTotalHT			= GETPOST('line-showTotalHT', 'int');
		$showReduc				= GETPOST('line-showReduc', 'int');
		$showQty				= GETPOSTISSET('line-showQty') ? GETPOST('line-showQty', 'int') : -1;
		$level					= GETPOST('infrastructure_level', 'int');
		if (!empty($level)) {
			if ($line->qty > 90) {
				$line->qty = 100 - $level; // Si on edit une ligne sous-total
			} else {
				$line->qty = $level;
			}
		}
		$line->array_options['options_show_table_header_before']	= $showTableHeaderBefore;
		$line->array_options['options_print_as_list']				= $printAsList;
		$line->array_options['options_print_condensed']				= $printCondensed;
		$line->array_options['options_show_total_ht']				= $showTotalHT;
		$line->array_options['options_show_reduc']					= $showReduc;
		$line->array_options['options_infrastructure_show_qty']			= $showQty;
		$res														= TInfrastructure::doUpdateLine($object, $line->id, $description, 0, $line->qty, 0, '', '', 0, 9, 0, 0, 'HT', $pagebreak, 0, 1, null, 0, $label, TInfrastructure::$module_number, $line->array_options);
		$TKey														= null;
		if ($line->element == 'propaldet' && getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET')) {
			$TKey = explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_PROPALDET'));
		} elseif ($line->element == 'commandedet' && getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET')) {
			$TKey = explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_COMMANDEDET'));
		} elseif ($line->element == 'facturedet' && getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET')) {
			$TKey = explode(',', getDolGlobalString('INFRASTRUCTURE_LIST_OF_EXTRAFIELDS_FACTUREDET'));
		}
		// TODO ajouter la partie fournisseur
		if (!empty($TKey)) {
			$extrafields	= new ExtraFields($object->db);
			$extrafields->fetch_name_optionals_label($line->element);
			$TPost			= $extrafields->getOptionalsFromPost($line->element, '', 'infrastructure_');
			$TLine			= TInfrastructure::getLinesFromTitleId($object, $line->id);
			foreach ($TLine as $object_line) {
				foreach ($TKey as $key) {
					// TODO remove "true"
					if (isset($TPost['infrastructure_options_'.$key])) {
						$object_line->array_options['options_'.$key] = $TPost['infrastructure_options_'.$key];
					}
				}
				$object_line->insertExtraFields();
			}
		}
		return $res;
	}

	/**
	* Update all lines of a infrastructure block from a title line.
	*
	* @param	CommonObject		$object		Parent object (invoice, order, proposal, ...)
	* @param	CommonObjectLine	$line		Title line defining the infrastructure block
	* @return	int								Number of updated lines, or negative value if errors
	*/
	function infrastructure_updateInfrastructureBloc($object, $line)
	{
		global $langs;

		$infrastructure_tva_tx		= $infrastructure_tva_tx_init = GETPOST('infrastructure_tva_tx', 'int');
		$infrastructure_progress		= $infrastructure_progress_init = GETPOST('infrastructure_progress', 'int');
		$array_options			= $line->array_options;
		$showBlockExtrafields	= GETPOST('showBlockExtrafields', 'aZ09');
		if ($infrastructure_tva_tx != '' || $infrastructure_progress != '' || (!empty($showBlockExtrafields) && !empty($array_options))) {
			$error_progress	= $nb_progress_update = $nb_progress_not_updated = 0;
			$TLine			= TInfrastructure::getLinesFromTitleId($object, $line->id);
			foreach ($TLine as &$line) {
				if (!TInfrastructure::isModInfrastructureLine($line)) {
					$infrastructure_tva_tx = $infrastructure_tva_tx_init; // ré-init car la variable peut évoluer
					if (!empty($showBlockExtrafields)) {
						$line->array_options = $array_options;
					}
					if ($infrastructure_tva_tx == '') {
						$infrastructure_tva_tx = $line->tva_tx;
					}
					if ($object->element == 'facture' && getDolGlobalString('INVOICE_USE_SITUATION') && $object->type == Facture::TYPE_SITUATION) {
						$infrastructure_progress = $infrastructure_progress_init;
						if ($infrastructure_progress == '') {
							$infrastructure_progress = $line->situation_percent;
						} else {
							$prev_percent = $line->get_prev_progress($object->id);
							if ($infrastructure_progress < $prev_percent) {
								$nb_progress_not_updated++;
								$infrastructure_progress = $line->situation_percent;
							}
						}
					}
					$res = TInfrastructure::doUpdateLine($object, $line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $infrastructure_tva_tx, $line->product_type, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $infrastructure_progress, $line->fk_unit);
					if ($res > 0) {
						$success_updated_line++;
					} else {
						$error_updated_line++;
					}
				}
			}
			if ($nb_progress_not_updated > 0) {
				setEventMessage($langs->trans('InfrastructureNbProgressNotUpdated', $nb_progress_not_updated), 'warnings');
			}
			if ($success_updated_line > 0) {
				setEventMessage($langs->trans('InfrastructureSuccessUpdatedLine', $success_updated_line));
			}
			if ($error_updated_line > 0) {
				setEventMessage($langs->trans('InfrastructureErrorUpdatedLine', $error_updated_line), 'errors');
				return -$error_updated_line;
			}
			return $success_updated_line;
		}
		return 0;
	}

	/**
	* Maj du bloc pour forcer le total_tva et total_ht à 0 et recalculer le total du document
	*
	* @param	int		$lineid			Title lineid
	* @param	int		$infrastructure_nc	0 = "Compris" prise en compte des totaux des lignes; 1 = "Non compris" non prise en compte des totaux du bloc; null = update de toutes les lignes
	*/
	function infrastructure_updateLineNC($element, $elementid, $lineid, $infrastructure_nc=null, $notrigger = 0)
	{
		global $db,$langs,$tmp_object_nc;

		$error = 0;
		if (empty($element)) {
			$error++;
		}
		if (!$error) {
			if (!empty($tmp_object_nc) && $tmp_object_nc->element == $element && $tmp_object_nc->id == $elementid) {
				$object = $tmp_object_nc;
			} else {
				$TAllowedElements	= array(
					'propal'				=> 'Propal',
					'commande'				=> 'Commande',
					'facture'				=> 'Facture',
					'supplier_proposal'		=> 'SupplierProposal',
					'order_supplier'		=> 'CommandeFournisseur',
					'invoice_supplier'		=> 'FactureFournisseur',
				);
				if (empty($TAllowedElements[$element])) {
					$error++;
					return 0;
				}
				$classname = $TAllowedElements[$element];
				$object	= new $classname($db); // Propal | Commande | Facture
				$res	= $object->fetch($elementid);
				if ($res < 0) {
					$error++;
				} else {
					$tmp_object_nc = $object;
				}
			}
		}
		if (!$error) {
			foreach ($object->lines as &$l) {
				if($l->id == $lineid) {
					$line = $l;
					break;
				}
			}
			if (!empty($line)) {
				$db->begin();
				if(TInfrastructure::isModInfrastructureLine($line)) {
					if (TInfrastructure::isTitle($line)) {
						// Update le contenu du titre (ainsi que le titre lui même)
						$TTitleBlock = TInfrastructure::getLinesFromTitleId($object, $lineid, true);
						foreach($TTitleBlock as &$line_block) {
							$res = infrastructure_doUpdate($object, $line_block, $infrastructure_nc, $notrigger);
						}
					}
				} else {
					$res = infrastructure_doUpdate($object, $line, $infrastructure_nc, $notrigger);
				}
				$res	= $object->update_price(1);
				if ($res <= 0) {
					$error++;
				}
				if (!$error) {
					setEventMessage($langs->trans('InfrastructureUpdateNcSuccess'));
					$db->commit();
				} else {
					setEventMessage($langs->trans('InfrastructureUpdateNcError'), 'errors');
					$db->rollback();
				}
			}
		}
	}

	function infrastructure_updateLine($element, $elementid, $lineid)
	{
		infrastructure_updateLineNC($element, $elementid, $lineid);
	}

	/**
	* Get session variable names for hide options based on context
	*
	* @param	array	$contextArray	Array of context strings
	* @return	array					Array with keys 'hideInnerLines', 'hidesubdetails', 'hideprices'
	*/
	function infrastructure_getSessionNames($contextArray)
	{
		if (in_array('invoicecard', $contextArray)) {
			$suffix = 'facture';
		} elseif (in_array('invoicesuppliercard', $contextArray)) {
			$suffix = 'facture_fournisseur';
		} elseif (in_array('propalcard', $contextArray)) {
			$suffix = 'propal';
		} elseif (in_array('supplier_proposalcard', $contextArray)) {
			$suffix = 'supplier_proposal';
		} elseif (in_array('ordercard', $contextArray)) {
			$suffix = 'commande';
		} elseif (in_array('ordersuppliercard', $contextArray)) {
			$suffix = 'commande_fournisseur';
		} else {
			$suffix = 'unknown';
		}
		return array('hideInnerLines'	=> 'infrastructure_hideInnerLines_'.$suffix,
					'hidesubdetails'	=> 'infrastructure_hidesubdetails_'.$suffix,
					'hideprices'		=> 'infrastructure_hideprices_'.$suffix,
				);
	}

	/**
	* Compute PDF background style from a color configuration constant
	*
	* @param	TCPDF	$pdf					PDF object
	* @param	string	$colorConst				Global constant name for background color (e.g. 'INFRASTRUCTURE_TITLE_BACKGROUND_COLOR')
	* @param	string	$heightOffsetConst		Global constant name for cell height offset
	* @param	string	$posYOffsetConst		Global constant name for cell Y position offset
	* @return	array							Array with keys 'fill', 'color', 'heightOffset', 'posYOffset'
	*/
	function infrastructure_getPdfBackgroundStyle(&$pdf, $colorConst, $heightOffsetConst = '', $posYOffsetConst = '')
	{
		$result	= array('fill'			=> false,
						'color'			=> array(233, 233, 233),
						'heightOffset'	=> 0,
						'posYOffset'	=> 0,
					);
		if (getDolGlobalString($colorConst) && function_exists('colorValidateHex') && colorValidateHex(getDolGlobalString($colorConst)) && function_exists('colorStringToArray')) {
			$result['fill']		= true;
			$result['color']	= colorStringToArray(getDolGlobalString($colorConst), array(233, 233, 233));
			if (function_exists('colorIsLight') && !colorIsLight(getDolGlobalString($colorConst))) {
				$pdf->setColor('text', 255, 255, 255);
			}
			if ($heightOffsetConst && getDolGlobalString($heightOffsetConst)) {
				$result['heightOffset']	= doubleval(getDolGlobalString($heightOffsetConst));
			}
			if ($posYOffsetConst && getDolGlobalString($posYOffsetConst)) {
				$result['posYOffset']	= doubleval(getDolGlobalString($posYOffsetConst));
			}
		}
		return $result;
	}

	/**
	* duplicate from action_submodule
	*
	* @param	object			$object		Document object (invoice, order, propal, ...)
	* @param	object			$line		Line object
	* @param	bool			$use_level	level is used to get total of a bloc with infrastructure line with level superior to the line (and not only the lines with same level)
	* @param	int				$return_all	If set to 1, returns an array with total, total_tva, total_ttc, and TTotal_tva (total TVA by rate)
	* @return	array|float|int
	*/
	function infrastructure_getTotalLineFromObject(&$object, &$line, $use_level=false, $return_all=0) {

		global $conf;

		$rang		= $line->rang;
		$qty_line	= $line->qty;
		$lvl		= 0;
		if (TInfrastructure::isInfrastructure($line)) {
			$lvl = TInfrastructure::getNiveau($line);
		}
		$total			= 0;
		$total_tva		= 0;
		$total_ttc		= 0;
		$TTotal_tva		= array();
		$title_break	= TInfrastructure::getParentTitleOfLine($object, $rang, $lvl);
		$sign			= isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE') ? -1 : 1;
		$builddoc		= GETPOST('action', 'aZ09') == 'builddoc' ? true : false;
		$TLineReverse	= array_reverse($object->lines);
		foreach($TLineReverse as $l) {
			$l->total_ttc	= doubleval($l->total_ttc);
			$l->total_ht	= doubleval($l->total_ht);
			if ($l->rang >= $rang) {
				continue;
			}
			if (!empty($title_break) && $title_break->id == $l->id) {
				break;
			} elseif (!TInfrastructure::isModInfrastructureLine($l)) {
				// TODO retirer le test avec $builddoc quand Dolibarr affichera le total progression sur la card et pas seulement dans le PDF
				if ($builddoc && $object->element == 'facture' && $object->type==Facture::TYPE_SITUATION) {
					if ($l->situation_percent > 0 && !empty($l->total_ht)) {
						$prev_progress	= 0;
						$progress		= 1;
						if (method_exists($l, 'get_prev_progress')) {
							$prev_progress	= $l->get_prev_progress($object->id);
							$progress		= ($l->situation_percent - $prev_progress) / 100;
						}
						$result					= $sign * ($l->total_ht / ($l->situation_percent / 100)) * $progress;
						$total					+= $result;
						$total_tva				+= $sign * ($l->total_tva / ($l->situation_percent / 100)) * $progress;
						$TTotal_tva[$l->tva_tx] += $sign * ($l->total_tva / ($l->situation_percent / 100)) * $progress;
						$total_ttc				+= $sign * ($l->total_tva / ($l->total_ttc / 100)) * $progress;

					}
				} else {
					if ($l->product_type != 9) {
						$total					+= $l->total_ht;
						$total_tva				+= $l->total_tva;
						$TTotal_tva[$l->tva_tx] += $l->total_tva;
						$total_ttc				+= $l->total_ttc;
					}
				}
			}
		}
		if (!$return_all) {
			return $total;
		} else {
			return array($total, $total_tva, $total_ttc, $TTotal_tva);
		}
	}

	/**
	* Retourne le progrès actuel d'une ligne de facture de situation, additionne le progrès précédent et le pourcentage de la ligne (sauf pour une facture acompte)
	*
	* @param	FactureLigne	$line		L'objet ligne de facture
	* @param	int				$factureid	ID de la facture
	* @return	float						Progrès actuel en pourcentage (0 à 100)
	*/
	function infrastructure_getLineCurrentProgress($factureid, $line)
	{
		global $db;

		$previous_progress	= (floatval(DOL_VERSION) >= 21) ? $line->getAllPrevProgress($factureid) : $line->get_prev_progress($factureid);
		$parent				= new Facture($db);
		$res				= $parent->fetch($factureid);
		if ($res) {
			if ($parent->type == Facture::TYPE_CREDIT_NOTE) {
				return $previous_progress;
			}
			return $previous_progress + floatval($line->situation_percent);
		} else {
			dol_syslog($parent->error, LOG_ERR);
			return 0;
		}
	}

	/**
	* Get titles flat array
	*
	* @param	array	$TTitleNumeroted	Titles numeroted
	* @param	array	$resArray			Result array
	* @return	array
	*/
	function infrastructure_getTitlesFlatArray($TTitleNumeroted = array(), &$resArray = array())
	{
		if (is_array($TTitleNumeroted) && !empty($TTitleNumeroted)) {
			foreach ($TTitleNumeroted as $tn) {
				$resArray[$tn['line']->id] = $tn;
				if (array_key_exists('children', $tn)) {
					infrastructure_getTitlesFlatArray($tn['children'], $resArray);
				}
			}
		}
		return $resArray;
	}

	//@TODO change all call to this method with the method in lib !!!!
	/**
	* Get total line from object
	*
	* @param	CommonObject		$object		Object
	* @param	CommonObjectLine	$line		Line
	* @param	bool				$use_level	Use level
	* @param	int					$return_all	Return all
	* @return	array|float|int
	*/
	function infrastructure_get_totalLineFromObject(&$object, &$line, $use_level = false, $return_all = 0)
	{
		$rang	= $line->rang;
		$lvl	= 0;
		if (TInfrastructure::isInfrastructure($line)) {
			$lvl = TInfrastructure::getNiveau($line);
		}
		$memoEnabled	= isset($object->context['infrastructureCache']['warmed']) && !empty($object->context['infrastructureCache']['warmed']);
		$memoKey		= $rang.'|'.$lvl.'|'.intval((bool) $use_level).'|'.intval($return_all);
		if ($memoEnabled) {
			if (!isset($object->context['infrastructureCache']['totalLineByKey']) || !is_array($object->context['infrastructureCache']['totalLineByKey'])) {
				$object->context['infrastructureCache']['totalLineByKey']	= array();
			}
			if (array_key_exists($memoKey, $object->context['infrastructureCache']['totalLineByKey'])) {
				return $object->context['infrastructureCache']['totalLineByKey'][$memoKey];
			}
		}
		$title_break				= TInfrastructure::getParentTitleOfLine($object, $rang, $lvl);
		$total						= 0;
		$total_tva					= 0;
		$total_ttc					= 0;
		$total_qty					= 0;
		$TTotal_tva					= array();
		$TTotal_tva_array			= array();
		$multicurrency_total_ht		= 0;
		$multicurrency_total_ttc	= 0;
		$sign						= 1;
		if ($memoEnabled) {
			if (!isset($object->context['infrastructureCache']['linesReversed']) || !is_array($object->context['infrastructureCache']['linesReversed'])) {
				$object->context['infrastructureCache']['linesReversed']	= array_reverse($object->lines);
			}
			$TLineReverse	= $object->context['infrastructureCache']['linesReversed'];
		} else {
			$TLineReverse	= array_reverse($object->lines);
		}
		$listOuvrages				= array();
		if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
			$sign = -1;
		}
		if (!empty(isModEnabled('ouvrage')) && class_exists('Ouvrage') ) {
			// loop over the lines above the current total line
			foreach ($TLineReverse as $l) {
				$isOuvrage	= Ouvrage::isOuvrage($l) ? 1 : 0;	// ouvrage ??
				if (!empty($title_break) && $title_break->id == $l->id) {
					break;								// We go back from the end to the beginning, so when we find the associated title we stop
				} elseif (!empty($isOuvrage)) {			// it's a ouvrage
					$listOuvrages[$l->id]	= $l->qty;	// record the quantity linked to the ID
				}
			}
		}
		foreach($TLineReverse as $l) {
			$l->total_ttc				= doubleval($l->total_ttc);
			$l->total_ht				= doubleval($l->total_ht);
			$l->multicurrency_total_ht	= doubleval($l->multicurrency_total_ht);
			$l->multicurrency_total_ttc = doubleval($l->multicurrency_total_ttc);
			$isOuvrage					= !empty(isModEnabled('ouvrage')) && class_exists('Ouvrage') && Ouvrage::isOuvrage($l) ? 1 : 0;
			if ($l->rang >= $rang) {
				continue;
			}
			if (!empty($title_break) && $title_break->id == $l->id) {
				break;
			} elseif (!TInfrastructure::isModInfrastructureLine($l) && empty($isOuvrage)) {
				$totalQty	= !empty($listOuvrages) && !empty($l->fk_parent_line) && array_key_exists($l->fk_parent_line, $listOuvrages) ? $listOuvrages[$l->fk_parent_line] : 1;
				$total_qty += $l->qty;
				if ($object->element == 'facture' && $object->type == Facture::TYPE_SITUATION) {
					$sitFacTotLineAvt	= getDolGlobalInt('INFRASPLUS_PDF_SITFAC_TOTLINE_AVT', 0);
					// 1 = (legacy mode): situation_percent is cumulative (state at situation)
					// 2 = (new mode): situation_percent is non-cumulative (delta of current situation)
					$isCumulative = getDolGlobalInt('INVOICE_USE_SITUATION') === 1;
					if ($l->situation_percent > 0 && !empty($l->total_ht) && empty($sitFacTotLineAvt)) {
						$prev_progress = method_exists($l, 'get_prev_progress') ? $l->get_prev_progress($object->id) : 0;
						if ($isCumulative) {
							// legacy mode: $l->situation_percent = cumulative progress within the cycle
							$progressState				= $l->situation_percent;
							$progressDelta				= $progressState - $prev_progress;
							$progressRatio				= $progressDelta / $progressState;
							$lineTotalHT				= $sign * $l->total_ht * $progressRatio;
							$lineTotalTVA				= $sign * $l->total_tva * $progressRatio;
							$lineTotalTTC				= $sign * $l->total_ttc * $progressRatio;
							$lineMulticurrencyTotalHT	= $sign * $l->multicurrency_total_ht * $progressRatio;
							$lineMulticurrencyTotalTTC	= $sign * $l->multicurrency_total_ttc * $progressRatio;
						} else {
							// new mode: $l->situation_percent = progress delta of this situation invoice
							// the delta (=non-cumulative) values are stored directly on the line
							$lineTotalHT				= $l->total_ht;
							$lineTotalTVA				= $l->total_tva;
							$lineTotalTTC				= $l->total_ttc;
							$lineMulticurrencyTotalHT	= $l->multicurrency_total_ht;
							$lineMulticurrencyTotalTTC	= $l->multicurrency_total_ttc;
						}
						$total						+= $lineTotalHT;
						$total_tva					+= $lineTotalTVA;
						$total_ttc					+= $lineTotalTTC;
						if (!isset($TTotal_tva[$l->tva_tx])) {
							$TTotal_tva[$l->tva_tx]	= 0;
						}
						$TTotal_tva[$l->tva_tx]		+= $lineTotalTVA;
						$multicurrency_total_ht		+= $lineMulticurrencyTotalHT;
						$multicurrency_total_ttc	+= $lineMulticurrencyTotalTTC;
					} elseif ($l->product_type != 9) {
						$total						+= $l->total_ht * $totalQty;
						$total_tva					+= $l->total_tva * $totalQty;
						$TTotal_tva[$l->tva_tx]		+= $l->total_tva * $totalQty;
						$total_ttc					+= $l->total_ttc * $totalQty;
						$multicurrency_total_ht		+= $l->multicurrency_total_ht * $totalQty;
						$multicurrency_total_ttc	+= $l->multicurrency_total_ttc * $totalQty;
					}
				} elseif ($l->product_type != 9) {
					$total							+= $l->total_ht * $totalQty;
					$total_tva						+= $l->total_tva * $totalQty;
					$multicurrency_total_ht			+= $l->multicurrency_total_ht * $totalQty;
					if (! isset($TTotal_tva[$l->tva_tx])) {
						$TTotal_tva[$l->tva_tx]	= 0;
					}
					$TTotal_tva[$l->tva_tx]			+= $l->total_tva * $totalQty;
					$total_ttc						+= $l->total_ttc * $totalQty;
					$multicurrency_total_ttc		+= $l->multicurrency_total_ttc * $totalQty;
					$vatrate = (string) $l->tva_tx;
					if (($l->info_bits & 0x01) == 0x01) {
						$vatrate .= '*';
					}
					$vatcode	= $l->vat_src_code;
					if (empty($TTotal_tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'])) {
						$TTotal_tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] = 0;
					}
					$TTotal_tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')] = array('vatrate' => $vatrate, 'vatcode' => $vatcode, 'amount' => $TTotal_tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] + $l->total_tva, 'base' => $total);
				}
			}
		}
		if (!$return_all) {
			$result	= $total;
		} else {
			$result	= array($total, $total_tva, $total_ttc, $TTotal_tva, $total_qty, $TTotal_tva_array, $multicurrency_total_ht, $multicurrency_total_ttc);
		}
		if ($memoEnabled) {
			$object->context['infrastructureCache']['totalLineByKey'][$memoKey]	= $result;
		}
		return $result;
	}

	/**
	* TODO ne gère pas encore la numération des lignes "Totaux"
	*
	* @param	CommonObjectLine[]	$TLineTitle		Array of title lines
	* @param	CommonObjectLine|null	$line_reference	Parent title line reference
	* @param	int					$level			Level
	* @param	int					$prefix_num 	Prefix number
	* @return	array
	*/
	function infrastructure_formatNumerotation(&$TLineTitle, $line_reference = null, $level = 1, $prefix_num = 0)
	{
		$i							= 1;
		$j							= 0;
		$TTitle 					= array();
		$TLineElementsWithoutLabel	= array('facture_fourn_det', 'commande_fournisseurdet');
		foreach ($TLineTitle as $k => &$line) {
			if (!empty($line_reference) && $line->rang <= $line_reference->rang) continue;
			if (!empty($line_reference) && $line->qty <= $line_reference->qty) break;
			if ($line->qty == $level) {
				$TTitle[$j]['numerotation'] = ($prefix_num == 0) ? $i : $prefix_num.'.'.$i;
				if (empty($line->label) && (in_array($line->element, $TLineElementsWithoutLabel))) {
					$line->label	= !empty($line->desc) ? $line->desc : $line->description;
					$line->desc		= $line->description = '';
				}
				$line->label		= $TTitle[$j]['numerotation'].' '.$line->label;
				$TTitle[$j]['line'] = &$line;
				$deep_level			= $line->qty;
				do {
					$deep_level++;
					$TTitle[$j]['children'] = infrastructure_formatNumerotation($TLineTitle, $line, $deep_level, $TTitle[$j]['numerotation']);
				} while (empty($TTitle[$j]['children']) && $deep_level <= 10); // Exemple si un bloc Titre lvl 1 contient pas de sous lvl 2 mais directement un sous lvl 5
				// Rappel on peux avoir jusqu'a 10 niveau de titre
				$i++;
				$j++;
			}
		}
		return $TTitle;
	}

	/**
	* Get HTML data
	*
	* @param	array		$parameters		Parameters
	* @param	CommonObject$object			Object
	* @param	string		$action			Action
	* @param	HookManager	$hookmanager	Hook manager
	* @return	string
	*/
	function infrastructure_getHtmlData($parameters, &$object, &$action, $hookmanager)
	{

		$line							= &$parameters['line'];
		$ThtmlData['data-id']           = $line->id;
		$ThtmlData['data-product_type'] = $line->product_type;
		$ThtmlData['data-qty']          = 0; //$line->qty;
		$ThtmlData['data-level']        = TInfrastructure::getNiveau($line);
		if (TInfrastructure::isTitle($line)) {
			$ThtmlData['data-isinfrastructure']			= 'title';
			$ThtmlData['data-folder-status']		= 'open';
			if (!empty($line->array_options['options_hideblock'])) {
				$ThtmlData['data-folder-status']	= 'closed';
			}
		} elseif (TInfrastructure::isInfrastructure($line)) {
			$ThtmlData['data-isinfrastructure']	= 'infrastructure';
		} else {
			$ThtmlData['data-isinfrastructure']	= 'freetext';
		}
		// Change or add data  from hooks
		$parameters	= array_replace($parameters, array(  'ThtmlData' => $ThtmlData ));
		// hook
		$reshook	= $hookmanager->executeHooks('infrastructureLineHtmlData', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}
		if ($reshook > 0) {
			$ThtmlData	= $hookmanager->resArray;
		}
		return infrastructure_implodeHtmlData($ThtmlData);
	}

	/**
	* Implode HTML data
	*
	* @param array $ThtmlData HTML data
	* @return string
	*/
	function infrastructure_implodeHtmlData($ThtmlData = array())
	{
		$data = '';
		foreach ($ThtmlData as $k => $h) {
			if (is_array($h)) {
				$h = json_encode($h);
			}
			$data .= $k.'="'.dol_htmlentities($h, ENT_QUOTES).'" ';
		}
		return $data;
	}

	/**
	* Set doc TVA
	*
	* @param	TCPDF			$pdf	PDF
	* @param	CommonObject	$object	Object
	* @return	bool
	*/
	function infrastructure_setDocTVA(&$pdf, &$object)
	{
		$hidesubdetails	= GETPOST('hidesubdetails', 'int');
		if(empty($hidesubdetails)) return false;
		// TODO can't add VAT to document without lines... :-/
		return true;
	}

	/**
	* Show select title to add
	*
	* @param	CommonObject $object Object
	* @return	void
	*/
	function infrastructure_showSelectTitleToAdd(&$object)
	{
		global $langs;

		TInfrastructure::getAllTitleFromDocument($object);
		?>
		<script type="text/javascript">
			$(function () {
				var add_button = $("#addline");
				if (add_button.length > 0) {
					add_button.closest('tr').prev('tr.liste_titre').children('td:last').addClass('center').text("<?php echo $langs->trans('InfrastructureTitleToAddUnderTitle'); ?>");
					var select_title = $(<?php echo json_encode(infrastructure_getHtmlSelectTitle($object)); ?>);
					add_button.before(select_title);
				}
			});
		</script>
		<?php
	}
