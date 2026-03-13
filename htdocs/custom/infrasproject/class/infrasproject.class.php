<?php
	/************************************************
	* Copyright (C) 2018-2020	Jeremie Ter-Heide	<jeremie@ter-heide.fr>
	* Copyright (C) 2020-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	*	\file		./infrasproject/class/infrasproject.class.php
	*	\ingroup	InfraS
	*	\brief		This file manages InfraSProject
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
	require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
	dol_include_once('/infrasproject/core/lib/infrasproject.lib.php');

	/************************************************
	* Class InfraSProject
	************************************************/
	class InfraSProject extends Project
	{
		public $lines	= array();

		/**
		* Constructor
		*
		* @param	 DATABASE		$db		 db object
		* @return						void
		**/
		function __construct($db)
		{
			global $conf;

			$this->db					= $db;
			$this->ismultientitymanaged	= 1;
			$this->isextrafieldmanaged	= 1;
			if (floatval(DOL_VERSION) >= 19) {
				$this->labelStatusShort	= array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
				$this->labelStatus		= array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
			} else {
				$this->statuts_short	= array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
				$this->statuts_long		= array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
			}
			if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')) {
				$this->fields['rowid']['visible']	= 0;
			}
			if (!getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
				$this->fields['fk_opp_status']['enabled']		= 0;
				$this->fields['opp_percent']['enabled']			= 0;
				$this->fields['opp_amount']['enabled']			= 0;
				$this->fields['usage_opportunity']['enabled']	= 0;
			}
			if (getDolGlobalString('PROJECT_HIDE_TASKS')) {
				$this->fields['usage_bill_time']['visible']	= 0;
				$this->fields['usage_task']['visible']		= 0;
			}
			if (!isModEnabled('eventorganization')) {
				$this->fields['usage_organize_event']['visible']			= 0;
				$this->fields['accept_conference_suggestions']['enabled']	= 0;
				$this->fields['accept_booth_suggestions']['enabled']		= 0;
				$this->fields['price_registration']['enabled']				= 0;
				$this->fields['price_booth']['enabled']						= 0;
				$this->fields['max_attendees']['enabled']					= 0;
			}
		}

		/**
		*	Correct stock
		*
		*	@param		int				$productid			product rowid
		*	@param		User			$user				User object
		*	@param		int				$id_entrepot		Id of warehouse
		*	@param		int				$nbpiece			Qty of movement (can be <0 or >0 depending on parameter type)
		*	@param		int				$movement			Direction of movement:
		*													0=input (stock increase by a stock transfer), 1=output (stock decrease by a stock transfer),
		*													2=output (stock decrease), 3=input (stock increase)
		*																						Note that qty should be > 0 with 0 or 3, < 0 with 1 or 2.
		*	@param		string			$label				Label of stock movement
		*	@param		int				$price				Unit price HT of product, used to calculate average weighted price (AWP or PMP in french). If 0, average weighted price is not changed.
		*	@param		string			$inventorycode		Inventory code
		*	@param		string			$objectType			type of origin element
		*	@param		int				$origin_id			rowid of origin element
		*	@param		integer|string	$datem				Force date of movement (timestamp)
		*	@param		integer|string	$eatby				eat-by date. Will be used if lot does not exists yet and will be created.
		*	@param		integer|string	$sellby				sell-by date. Will be used if lot does not exists yet and will be created.
		*	@param		string			$batch				batch number
		*	@return		int									<0 if KO, >0 if OK
		**/
		function correct_stock($productid, $user, $id_entrepot, $nbpiece, $movement, $label = '', $price = 0, $inventorycode = '', $objectType = '', $origin_id = null, $datem = '', $eatby = '', $sellby = '', $batch = '')
		{
			global $conf;

			if ($id_entrepot) {
				$this->db->begin();
				$product	= new Product($this->db);
				$product->fetch($productid);
				$price		= $product->pmp > 0 ? $product->pmp : $product->cost_price;
				if ($nbpiece > 0) {
					$op[0]	= '+'.trim(abs($nbpiece));
					$op[1]	= '-'.trim(abs($nbpiece));
				} elseif ($nbpiece < 0) {
					$op[0]	= '-'.trim(abs($nbpiece));
					$op[1]	= '+'.trim(abs($nbpiece));
				}
				$movementstock				= new MouvementStock($this->db);
				$classname					= ucfirst($objectType);
				$origin						= new $classname($this->db);
				$res						= $origin->fetch($origin_id);
				$movementstock->origin		= $origin;
				$movementstock->origin_type	= $objectType;
				$movementstock->origin->id	= $origin_id;
				$movementstock->origin_id	= $origin_id;
				$movementstock->fk_project	= $origin_id;
				$datem						= empty($datem) ? dol_now() : $datem;
				$inventorycode				= getDolGlobalString('INFRASPROJECT_INVCODEPREFIX').$movementstock->origin->ref.dol_print_date($datem,'%y%m%d%H%M%S');
				$result						= $movementstock->_create($user, $productid, $id_entrepot, $op[$movement], $movement, $price, $label, $inventorycode, $datem, $eatby, $sellby, $batch);
				if ($result > 0) {
					$this->db->commit();
					return 1;
				} else {
					$this->error	= $movementstock->error;
					$this->errors	= $movementstock->errors;
					$this->db->rollback();
					return -1;
				}
			}
			return 0;
		}

		/**
		*	Number of consumption for a project
		*
		*	@param		Project	$object		object we work on
		*	@return		int					number of records found
		**/
		function countconso($object)
		{
			global $conf;

			$sql	= 'SELECT COUNT(*) as nbtotalofrecords FROM '.$this->db->prefix().'stock_mouvement AS m WHERE';
			switch (getDolGlobalString('INFRASPROJECT_SEARCHMODE')) {
				case 1:
					$sql	.= ' m.label LIKE "%'.$this->db->escape($object->ref).'%"';
				break;
				case 2:
					$sql	.= ' m.inventorycode LIKE "'.$this->db->escape(getDolGlobalString('INFRASPROJECT_INVCODEPREFIX').$object->ref).'%"';
				break;
				case 3:
					$sql	.= ' (m.inventorycode LIKE "'.$this->db->escape(getDolGlobalString('INFRASPROJECT_INVCODEPREFIX').$object->ref).'%" OR m.label LIKE "%'.$this->db->escape($object->ref).'%")';
				break;
			}
			$nbtotalofrecords	= 0;
			$result				= $this->db->query($sql);
			if ($result) {
				$obj				= $this->db->fetch_object($result);
				$nbtotalofrecords	= $obj->nbtotalofrecords;
				$this->db->free($result);
			}
			return $nbtotalofrecords;
		}

		/**
		*	List of products for a warehouse
		*
		*	@param		int		$warehouse		warehouse ID
		*	@return		array					list of product for this warehouse
		**/
		function list_product_warehouse($warehouse)
		{
			$out	= array();
			$sql	= 'SELECT ps.fk_product, p.ref, p.label';
			$sql	.= ' FROM '.$this->db->prefix().'product_stock AS ps';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'product AS p ON p.rowid = ps.fk_product';
			$sql	.= ' WHERE ps.fk_entrepot = '.((int) $warehouse);
			$sql	.= ' AND ps.reel > 0';
			$sql	.= ' AND p.stockable_product = 1';
			$resql	= $this->db->query($sql);
			if (!empty($resql)) {
				for ($i = 0; $i < $this->db->num_rows($resql); $i++) {
					$objp					= $this->db->fetch_object($resql);
					$out[$objp->fk_product]	= $objp->ref.' - '.$objp->label;
				}
			} else {
				dol_print_error($this->db);
			}
			return	$out;
		}

		/**
		*	Return list of elements for type, linked to a project
		*
		*	@param		string		$type			'propal','order','invoice','order_supplier','invoice_supplier',...
		*	@param		string		$tablename		name of table associated of the type
		*	@param		string		$datefieldname	name of date field for filter
		*	@param		int			$date_start		Start date
		*	@param		int			$date_end		End date
		*	@param		string		$projectkey		Equivalent key	to fk_projet for actual type
		*	@return		mixed						Array list of object ids linked to project, < 0 or string if error
		**/
		public function get_element_list($type, $tablename, $datefieldname = '', $date_start = null, $date_end = null, $projectkey = 'fk_projet')
		{
			global $hookmanager;

			$elements	= array();
			if ($this->id <= 0) {
				return $elements;
			}
			$ids		= $this->id;
			if ($type == 'agenda') {
				$sql	= 'SELECT id AS rowid FROM '.$this->db->prefix().'actioncomm WHERE fk_project IN ('.$this->db->sanitize($ids).') AND entity IN ('.getEntity('agenda').')';
			} elseif ($type == 'expensereport') {
				$sql	= 'SELECT ed.rowid FROM '.$this->db->prefix().'expensereport AS e, '.$this->db->prefix().'expensereport_det AS ed WHERE e.rowid = ed.fk_expensereport AND e.entity IN ('.getEntity('expensereport').') AND ed.fk_projet IN ('.$this->db->sanitize($ids).')';
			} elseif ($type == 'project_task') {
				$sql	= 'SELECT DISTINCT pt.rowid FROM '.$this->db->prefix().'projet_task AS pt WHERE pt.fk_projet IN ('.$this->db->sanitize($ids).')';
			} elseif ($type == 'element_time') {	// Case we want to duplicate line foreach user
				$sql	= 'SELECT DISTINCT pt.rowid, ptt.fk_user FROM '.$this->db->prefix().'projet_task AS pt, '.$this->db->prefix().'element_time AS ptt WHERE pt.rowid = ptt.fk_element AND ptt.elementtype = "task" AND pt.fk_projet IN ('.$this->db->sanitize($ids).')';
			} elseif ($type == 'stock_mouvement') {
				$sql	= 'SELECT ms.rowid, ms.fk_user_author AS fk_user FROM '.$this->db->prefix().'stock_mouvement AS ms, '.$this->db->prefix().'entrepot AS e WHERE e.rowid = ms.fk_entrepot AND e.entity IN ('.getEntity('stock').') AND ms.origintype = "project" AND ms.fk_origin IN ('.$this->db->sanitize($ids).') AND ms.type_mouvement = 1';
			} elseif ($type == 'stocktransfer_stocktransfer') {
				$sql	= 'SELECT ms.rowid, ms.fk_user_author AS fk_user FROM '.$this->db->prefix().'stocktransfer_stocktransfer AS ms, '.$this->db->prefix().'entrepot AS e WHERE e.rowid = ms.fk_entrepot AND e.entity IN ('.getEntity('stock').') AND ms.origintype = "project" AND ms.fk_origin IN ('.$this->db->sanitize($ids).') AND ms.type_mouvement = 1';
			} elseif ($type == 'loan') {
				$sql	= 'SELECT l.rowid, l.fk_user_author AS fk_user FROM '.$this->db->prefix().'loan AS l WHERE l.entity IN ('.getEntity('loan').') AND l.fk_projet IN ('.$this->db->sanitize($ids).')';
			} else {
				$sql	= 'SELECT rowid FROM '.$this->db->prefix().$tablename.' WHERE '.$projectkey.' IN ('.$this->db->sanitize($ids).') AND entity IN ('.getEntity($type).')';
			}
			if (infrasproject_isDolTms($date_start) && $type == 'loan') {
				$sql	.= ' AND (dateend > "'.$this->db->idate($date_start).'" OR dateend IS NULL)';
			} elseif (infrasproject_isDolTms($date_start) && ($type != 'project_task')) {	// For table project_taks, we want the filter on date apply on project_time_spent table
				if (empty($datefieldname) && !empty($this->table_element_date)) {
					$datefieldname = $this->table_element_date;
				}
				if (empty($datefieldname)) {
					return 'Error this object has no date field defined';
				}
				$sql	.= " AND (".$datefieldname." >= '".$this->db->idate($date_start)."' OR ".$datefieldname." IS NULL)";
			}
			if (infrasproject_isDolTms($date_end) && $type == 'loan') {
				$sql	.= ' AND (datestart < "'.$this->db->idate($date_end).'" OR datestart IS NULL)';
			} elseif (infrasproject_isDolTms($date_end) && ($type != 'project_task')) {	// For table project_taks, we want the filter on date apply on project_time_spent table
				if (empty($datefieldname) && !empty($this->table_element_date)) {
					$datefieldname = $this->table_element_date;
				}
				if (empty($datefieldname)) {
					return 'Error this object has no date field defined';
				}
				$sql	.= ' AND ('.$datefieldname.' <= "'.$this->db->idate($date_end).'" OR '.$datefieldname.' IS NULL)';
			}
			$parameters = array(
				'sql'			=> $sql,
				'type'			=> $type,
				'tablename'		=> $tablename,
				'datefieldname'	=> $datefieldname,
				'dates'			=> $date_start,
				'datee'			=> $date_end,
				'fk_projet'		=> $projectkey,
				'ids'			=> $ids,
			);
			$reshook	= $hookmanager->executeHooks('getElementList', $parameters);
			if ($reshook > 0) {
				$sql	= $hookmanager->resPrint;
			} else {
				$sql	.= $hookmanager->resPrint;
			}
			if (!$sql) {
				return -1;
			}
			dol_syslog(get_class($this)."::get_element_list", LOG_DEBUG);
			$result			= $this->db->query($sql);
			if ($result) {
				$nump		= $this->db->num_rows($result);
				if ($nump) {
					for ($i = 0; $i < $nump; $i++) {
						$obj			= $this->db->fetch_object($result);
						$elements[$i]	= $obj->rowid.(empty($obj->fk_user) ? '' : '_'.$obj->fk_user);
					}
					$this->db->free($result);
				}
				/* Return array even if empty*/
				return $elements;
			} else {
				dol_print_error($this->db);
			}
			return -1;
		}

		/**
		 * Calculate provisional margin for a project within a date range
		 *
		 * @param	int		$dates		Start date timestamp (optional)
		 * @param	int		$datee		End date timestamp (optional)
		 * @return	array				Array with ca_ht, margin_ht, margin_ttc, margin_rate, propal_ht, supplier_order_ht
		 */
		public function calculateProvMargin($dates = null, $datee = null)
		{
			global $db, $langs, $conf, $hookmanager, $mysoc;

			$form = new Form($db);
			$listofreferent = infrasproject_getListOfReferent($this->id, $this->socid);

			// ✅ Appliquer la configuration AVANT la boucle
			if (getDolGlobalInt('INFRASPROJECT_ADD_SUPPLIER_INVOICE_IN_MARGIN_PROV', 0)) {
				$listofreferent['invoice_supplier']['provmargin'] = 'minus';
			}

			// ✅ Appliquer INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV
			if (!empty(getDolGlobalString('INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV'))) {
				foreach ($listofreferent as $key => $element) {
					if (isset($listofreferent[$key]['provmargin']) && $listofreferent[$key]['provmargin'] == 'add') {
						unset($listofreferent[$key]['provmargin']);
					}
				}
				$newelementforplusmarginprov = explode(',', getDolGlobalString('INFRASPROJECT_ELEMENTS_FOR_PLUS_MARGIN_PROV'));
				foreach ($newelementforplusmarginprov as $value) {
					$listofreferent[trim($value)]['provmargin'] = 'add';
				}
			}

			// ✅ Appliquer INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV
			if (!empty(getDolGlobalString('INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV'))) {
				foreach ($listofreferent as $key => $element) {
					if (isset($listofreferent[$key]['provmargin']) && $listofreferent[$key]['provmargin'] == 'minus') {
						unset($listofreferent[$key]['provmargin']);
					}
				}
				$newelementforminusmarginprov = explode(',', getDolGlobalString('INFRASPROJECT_ELEMENTS_FOR_MINUS_MARGIN_PROV'));
				foreach ($newelementforminusmarginprov as $value) {
					$listofreferent[trim($value)]['provmargin'] = 'minus';
				}
			}

			$total_provmargin_ht	= 0;
			$provmargin_ht			= 0;
			$provmargin_ttc			= 0;
			$totalpropal_ht			= 0;
			$totalsupplierorder_ht	= 0;

			foreach ($listofreferent as $key => $value) {
				$qualified		= $value['test'];
				$provmargin		= isset($value['provmargin']) ? $value['provmargin'] : null;

				if ($qualified && isset($provmargin)) {
					$classname		= $value['class'];
					$tablename		= $value['table'];
					$datefieldname	= $value['datefieldname'];
					$project_field	= $value['project_field'];

					$element				= new $classname($db);
					$qualifiedTotal			= 0;
					$qualifiedTotalRemain	= 0;
					$elementarray			= $this->get_element_list($key, $tablename, $datefieldname, $dates, $datee, !empty($project_field) ? $project_field : 'fk_projet');

					if (is_array($elementarray) && count($elementarray) > 0) {
						$total_ht				= 0;
						$total_ttc				= 0;
						$totalremaintopay_ht	= 0;
						$totalremaintopay_ttc	= 0;
						$num					= count($elementarray);
						$nb						= $num;

						for ($i = 0; $i < $num; $i++) {
							$tmp				= explode('_', $elementarray[$i]);
							$idofelement		= $tmp[0];
							$idofelementuser	= !empty($tmp[1]) ? $tmp[1] : '';
							$element->fetch($idofelement);

							// ✅ CORRECTION 1: Filtrage des factures fournisseur liées aux commandes
							if ($key == 'invoice_supplier' && getDolGlobalInt('INFRASPROJECT_ADD_SUPPLIER_INVOICE_IN_MARGIN_PROV', 0)) {
								$element->fetchObjectLinked();
								if (!empty($element->linkedObjects['order_supplier'])) {
									continue; // Skip supplier invoices linked to supplier orders
								}
							}

							// We don't want to count propal with wrong status
							$nb -= $tablename == 'propal' && $element->status != Propal::STATUS_SIGNED && $element->status != Propal::STATUS_BILLED ? 1 : 0;

							// Define if record must be used for total or not
							$qualifiedfortotal = true;
							if ($key == 'invoice') {
								if (!empty($element->close_code) && $element->close_code == 'replaced') {
									$qualifiedfortotal = false;
								}
								if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS) && $element->type == Facture::TYPE_DEPOSIT) {
									$qualifiedfortotal = false;
								}
							}
							if ($key == 'propal') {
								if ($element->status != Propal::STATUS_SIGNED && $element->status != Propal::STATUS_BILLED) {
									$qualifiedfortotal = false;
								}
							}

							// ✅ CORRECTION 2: Ajout du filtrage des notes de frais par type
							if ($key == 'expensereport') {
								if (!empty($value['type_fees_code']) && is_array($value['type_fees_code']) && in_array($element->type_fees_code, $value['type_fees_code'])) {
									$qualifiedfortotal = false; // selected type of fees must not be included in total
								}
							}

							if ($tablename != 'expensereport_det' && method_exists($element, 'fetch_thirdparty')) {
								$element->fetch_thirdparty();
							}

							// Define $total_ht_by_line
							$total_ht_by_line = $element->total_ht;

							// Define $total_ttc_by_line
							$total_ttc_by_line = $element->total_ttc;

							// Remain to pay on supplier order
							if (empty($value['disableamount']) && $tablename == 'commande_fournisseur') {
								$remaintopay_ht = 0;
								$remaintopay_ttc = 0;
								if ($element->status > CommandeFournisseur::STATUS_DRAFT && $element->status < CommandeFournisseur::STATUS_CANCELED) {
									$totalonlinkedelements = 0;
									$totalonlinkedelements_ttc = 0;
									$element->fetchObjectLinked($element->id, $element->element);
									if (!empty($element->linkedObjects)) {
										foreach ($element->linkedObjects['invoice_supplier'] as $factureliee) {
											$totalonlinkedelements += $factureliee->total_ht;
											$totalonlinkedelements_ttc += $factureliee->total_ttc;
										}
									}
									$remaintopay_ht = $element->total_ht - $totalonlinkedelements;
									$remaintopay_ttc = $element->total_ttc - $totalonlinkedelements_ttc;
								}
								if (isset($totalonlinkedelements)) {
									if ($remaintopay_ht < 0) {
										$totalremaintopay_ht += $remaintopay_ht;
										$totalremaintopay_ttc += $remaintopay_ttc;
										$qualifiedTotalRemain++;
									}
								}
							}

							// Add total if we have to
							if ($qualifiedfortotal) {
								$total_ht = $total_ht + $total_ht_by_line;
								$total_ttc = $total_ttc + $total_ttc_by_line;
								$qualifiedTotal++;
							}
						}

						// Calculate margin
						$qualifiedforfinalprofit = true;
						if ($key == 'intervention' && empty($conf->global->PROJECT_INCLUDE_INTERVENTION_AMOUNT_IN_PROFIT)) {
							$qualifiedforfinalprofit = false;
						}

						if ($qualifiedforfinalprofit) {
							if ($provmargin == 'add') {
								$total_provmargin_ht += $total_ht;
							}
							// NE PAS inverser le signe ici, il sera inversé après
							if ($provmargin != "add") {
								$total_ht = -$total_ht;
								$total_ttc = -$total_ttc;
							}
							$provmargin_ht += $total_ht;
							$provmargin_ttc += $total_ttc;

							// Remain to pay on supplier order
							$provmargin_ht += $totalremaintopay_ht;
							$provmargin_ttc += $totalremaintopay_ttc;

							if ($key == 'propal') {
								$totalpropal_ht = $total_ht;
							}
							if ($key == 'order_supplier') {
								$totalsupplierorder_ht = -$total_ht;
							}
						}
					}
				}
			}
			// Calculate margin rate
			$margin_rate = 0;
			if ($total_provmargin_ht > 0) {
				$margin_rate = round(100 * $provmargin_ht / $total_provmargin_ht, 2);
			}
			return array(
				'ca_ht'				=> $total_provmargin_ht,
				'margin_ht'			=> $provmargin_ht,
				'margin_ttc'		=> $provmargin_ttc,
				'margin_rate'		=> $margin_rate,
				'propal_ht'			=> $totalpropal_ht,
				'supplier_order_ht'	=> $totalsupplierorder_ht
			);
		}
	}
