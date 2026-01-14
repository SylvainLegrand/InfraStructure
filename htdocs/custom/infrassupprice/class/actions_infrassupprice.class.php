<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrassupprice/class/actions_infrassupprice.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraS
	************************************************/

	/************************************************
	* Class infrassupprice
	************************************************/
	class Actionsinfrassupprice
	{
		private $db;	// @var DoliDB Database handler
		public $results	= array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $error;	// @var string
		public $errors	= array();	// @var array Errors

		public function __construct($db)	// Constructor
		{
			$this->db	= $db;
		}

		/**
		* Overloading the doActions function : replacing the parent's function with the one below
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	object			$object			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			$action			Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		function addMoreActionsButtons($parameters, $object, $action, $hookmanager)
		{
			global $db, $conf, $langs, $user;
			$TContext	= explode(':', $parameters['context']);
			if (in_array('supplier_proposalcard', $TContext) || in_array('ordersuppliercard', $TContext) || in_array('invoicesuppliercard', $TContext)) {
				$qtyNotValueMin	= getDolGlobalInt('INFRASSUPPRICE_QTE_NOT_VALUE_MIN', 0);
				$nblignes		= count($object->lines);
 				if ($nblignes > 0 && (int) $object->statut >= 1 && $user->hasRight('infrassupprice', 'update')) {
					$langs->load('infrassupprice@infrassupprice');
					$ligneProd	= '';
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if (empty($object->lines[$i]->fk_product)) {
							continue;
						} else {
							$ref	= $object->lines[$i]->ref;
						}
						$VAT				= vatrate($object->lines[$i]->tva_tx, 0, $object->lines[$i]->info_bits, 1);
						$currency_subprice	= ($conf->multicurrency->enabled && $object->multicurrency_tx != 1 ? $object->lines[$i]->multicurrency_subprice : $object->lines[$i]->subprice);
						$currency_upFour	= price($currency_subprice, 0, $langs);
						$currency_tx		= $object->multicurrency_tx;
						$currency_code		= $object->multicurrency_code;
						$subprice			= $object->lines[$i]->subprice;
						$upFour				= price($subprice, 0, $langs);
						$rem_percent		= $object->lines[$i]->remise_percent;
						$idprod				= $object->lines[$i]->fk_product;
						$qtyMin				= $qtyNotValueMin ? 1 : $object->lines[$i]->qty;
						$refFour			= !empty($object->lines[$i]->ref_supplier) ? $object->lines[$i]->ref_supplier : $ref;
						$saisieRefFour		= '';
						$sql				= 'SELECT p.rowid, pfp.ref_fourn, pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.tva_tx';
						$sql				.= ' FROM '.MAIN_DB_PREFIX.'product AS p';
						$sql				.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price AS pfp';
						$sql				.= ' ON p.rowid = pfp.fk_product';
						$sql				.= ' WHERE pfp.entity IN ('.getEntity('product').')';
						$sql				.= ' AND p.ref = "'.$ref.'"';
						$sql				.= ' AND pfp.quantity = '.$qtyMin;
						$sql				.= ' AND pfp.fk_soc = "'.$object->thirdparty->id.'"';
						$resql				= $object->db->query($sql);
						if ($resql) {
							$obj	= $db->fetch_object($resql);
							if ($obj) {
								$idprod				= $obj->rowid;
								$refFour			= $obj->ref_fourn;
								$actualQtyMin		= $langs->trans('InfraSSupPriceActualQty').' '.$obj->quantity;
								$actualQtyMinTxt	= $qtyNotValueMin ? '' : ' '.$langs->trans('InfraSSupPriceActualQtyTxt', $obj->quantity);
								$actualSubprice		= $langs->trans('InfraSSupPriceActualSubPrice').' '.price($obj->price, 0, $langs).$actualQtyMinTxt;
								$actualUpFour		= $langs->trans('InfraSSupPriceActualUp').' '.price($obj->unitprice, 0, $langs).$actualQtyMinTxt;
								$actualRem			= $langs->trans('InfraSSupPriceActuaRem').' '.$obj->remise_percent.'%'.$actualQtyMinTxt;
								$actualVAT			= $langs->trans('InfraSSupPriceActualVat').' '.vatrate($obj->tva_tx, 0, $object->lines[$i]->info_bits, 1).'%';
								$saisieRefFour		= 'disabled';
							} else {
								$actualQtyMin = $actualQtyMinTxt = $actualSubprice = $actualUpFour = $actualRem = $actualVAT = $langs->trans('InfraSSupPriceActualNotDefined');
							}
						}
						$currency_pFour	= price(price2num($currency_upFour,'MU')  * $qtyMin, 0, $langs);
						$pFour			= price(price2num($upFour,'MU')  * $qtyMin, 0, $langs);
						$ligneProd		.= '<tr class = "oddeven">
												<td class = "center"><input type = "text" class = "flat center" id = "line_'.$i.'" name = "line_'.$i.'" value = "'.($i+1).'" disabled size = "6"></td>
												<td class = "center">
													<input type = "text" class = "flat" id = "ref_'.$i.'" name = "ref_'.$i.'" value = "'.$ref.'" disabled size = "25">
													<input type = "hidden" id = "idprod_'.$i.'" name = "idprod_'.$i.'" value = "'.$idprod.'">
												</td>
												<td class = "center"><input type = "text" class = "flat" id = "refFour_'.$i.'" frefFour_'.$i.'" value = "'.$refFour.'" '.$saisieRefFour.' size = "30"></td>
												<td class = "center"><input type = "text" class = "flat right" id = "VAT_'.$i.'" name = "VAT_'.$i.'" title = "'.$actualVAT.'" value = "'.$VAT.'" disabled size = "1">%</td>
												<td class = "center">
													<input type = "text" class = "flat right" id = "currency_upFour_'.$i.'" name = "currency_upFour_'.$i.'" title = "'.$actualUpFour.'" value = "'.$currency_upFour.'" disabled size = "10">
													<input type = "hidden" id = "currency_tx_'.$i.'" name = "currency_tx_'.$i.'" value = "'.$currency_tx.'">
													<input type = "hidden" id = "currency_code_'.$i.'" name = "currency_code_'.$i.'" value = "'.$currency_code.'">
													<input type = "hidden" id = "upFour_'.$i.'" name = "upFour_'.$i.'" value = "'.$upFour.'">
													<input type = "hidden" id = "pFour_'.$i.'" name = "pFour_'.$i.'" value = "'.$pFour.'">
												</td>
												<td class = "center"><input type = "number" class = "flat qtyMin right" id = "qtyMin_'.$i.'" name = "qtyMin_'.$i.'" title = "'.$actualQtyMin.'" value = "'.$qtyMin.'" min="1" max="999" '.($qtyNotValueMin ? '' : 'disabled').'></td>
												<td class = "center"><input type = "text" class = "flat right" id = "currency_pFour_'.$i.'" name = "currency_pFour_'.$i.'" title = "'.$actualSubprice.'" value = "'.$currency_pFour.'" disabled size = "10"></td>
												<td class = "center"><input type = "text" class = "flat right" id = "rem_percent_'.$i.'" name = "rem_percent_'.$i.'" title = "'.$actualRem.'" value = "'.$rem_percent.'" disabled size = "1">%</td>
												<td class = "center" colspan = "<?php echo $colspan + 1 ?>"><input type = "checkbox" class = "checkupdate" id = "up_qp_'.$i.'" name = "up_qp_'.$i.'" value = "'.$i.'"/></td>
											</tr>';
					}
					$colspan = in_array('ordersuppliercard', $TContext) ? 9 : 10;
?>
					<tr>
						<script type = "text/javascript">
							$(document).ready(function() {
								$('.foldable').each(function() {
									$(this).siblings().toggle();
								});
								$('.foldable').click(function() {
									$(this).siblings().toggle();
								});
								$("#all_up_qp").click(function() {
									if ($(this).is(':checked')) {
										$(".checkupdate").prop('checked', true);
									} else {
										$(".checkupdate").prop('checked', false);
									}
								});
								$("#bt_add_sp").click(function() {
									$(this).fadeOut();
									var test = 0;
									var i = '';
									$('.checkupdate').each(function() {
										if ($(this).prop('checked')) {
											test += 1;
											i = $(this).val();
											if ($("#idprod_"+i).val() != '' && $("#ref_"+i).val() != '' && $("#refFour_"+i).val() != '' && $("#qtyMin_"+i).val() != '') {
												req = $.ajax({
													async: false
													,url : "<?php echo dol_buildpath('/infrassupprice/script/interface.php', 1) ?>"
													,data: {
														put: 'updateprice'
														,idprod: $("#idprod_"+i).val()
														,ref_search: $('#ref_'+i).val()
														,fk_supplier: <?php echo !empty($object->socid) ? $object->socid : $object->fk_soc ?>
														,ref: $("#refFour_"+i).val()
														,tvatx: $("#VAT_"+i).val()
														,currency_tx: $("#currency_tx_"+i).val()
														,currency_code: $("#currency_code_"+i).val()
														,currency_unitprice: $("#currency_upFour_"+i).val()
														,unitprice: $("#upFour_"+i).val()
														,qty: $("#qtyMin_"+i).val()
														,currency_price: $("#currency_pFour_"+i).val()
														,price: $("#pFour_"+i).val()
														,rem_percent: $("#rem_percent_"+i).val()
													}
													,method: "post"
													,dataType: "json"
													,success: function(data) {
														if (data.id == 0) {
															$.ajax({
																async: false
																,url : "<?php echo dol_buildpath('/infrassupprice/script/message.php', 1) ?>"
																,data: {
																	line: i
																	,msg: "Ok"
																}
																,method: "post"
																,success: function() {
																	location.reload();
																}
															});
														} else if (data.id == 1) {
															$.ajax({
																async: false
																,url : "<?php echo dol_buildpath('/infrassupprice/script/message.php', 1) ?>"
																,data: {
																	line: i
																	,msg: "Idem"
																}
																,method: "post"
																,success: function() {
																	location.reload();
																}
															});
														} else {
															msg = data.desc;
															$.ajax({
																async: false
																,url : "<?php echo dol_buildpath('/infrassupprice/script/message.php', 1) ?>"
																,data: {
																	line: i
																	,msg: msg
																}
																,method: "post"
																,success: function() {
																	location.reload();
																}
															});
														}
													}
												});
											} else {
												$.ajax({
													async: false
													,url : "<?php echo dol_buildpath('/infrassupprice/script/message.php',1) ?>"
													,data: {
														line: i
														,msg: "Ko"
													}
													,method: "post"
													,success: function() {
														location.reload();
													}
												});
											}
										}
									});
									if (test == 0) {
										$.ajax({
											async: false
											,url : "<?php echo dol_buildpath('/infrassupprice/script/message.php',1) ?>"
											,data: {
												line: i
												,msg: "noCheck"
											}
											,method: "post"
											,success: function() {
												location.reload();
											}
										});
									}
								});
							});
						</script>
						<td colspan = "<?php echo $colspan ?>">
							<table class = "noborder noshadow" width = 100% style = "border: 0;">
								<colgroup>
									<col width = 3%>
									<col width = 20%>
									<col width = 20%>
									<col width = 8%>
									<col width = 10%>
									<col width = 5%>
									<col width = 10%>
									<col width = 8%>
									<col width = *>
								</colgroup>
								<head>
									<tr class = "liste_titre liste_titre_add nodrag nodrop foldable">
										<th colspan = 9 class = "center cursorpointer"><?php echo $langs->trans("InfraSSupPriceTitreMaj")?></th>
									</tr>
									<tr class = "liste_titre nodrag nodrop center">
										<th class = "center"><?php echo $langs->trans('InfraSSupPriceColLineN')?></th>
										<th class = "center"><?php echo $langs->trans('InfraSSupPriceColRef')?></th>
										<th class = "center"><?php echo $langs->trans('InfraSSupPriceColCodeFour')?></th>
										<th class = "center"><?php echo $langs->trans('InfraSSupPriceColTVA')?></th>
										<th class = "center"><?php echo $langs->trans(!empty($conf->multicurrency->enabled) && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1 ? 'PriceUHTCurrency' : 'InfraSSupPriceColPu')?></th>
										<th class = "center"><?php echo $langs->trans('InfraSSupPriceColQty')?></th>
										<th class = "center"><?php echo $langs->trans('InfraSSupPriceColQtyMin')?></th>
										<th class = "center"><?php echo $langs->trans('InfraSSupPriceColRem')?></th>
										<th class = "center">
											<input type = "checkbox" name = "all_up_qp" id = "all_up_qp"/>
										</th>
									</tr>
								</head>
								<body>
									<?php echo $ligneProd ?>
								</body>
								<footer>
									<tr>
										<td colspan = 9 class = "center">
											<input type = "button" name = "bt_add_sp" id = "bt_add_sp" value = "<?php echo $langs->trans("InfraSSupPricebtnModif")?>" class = "butAction" style = "height: 30px; margin: 5px !important;"/>
										</td>
									</tr>
								</footer>
							</table>
						</td>
					</tr>
<?php
				}
			}
			return 0; // or return 1 to replace standard code
		}
	}
