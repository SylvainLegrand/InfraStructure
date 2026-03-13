 <?php
	/************************************************
	* Copyright (C) 2018-2020	Jeremie Ter-Heide  <jeremie@ter-heide.fr>
	* Copyright (C) 2020-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasproject/class/html.forminfrasproject.class.php
	* 	\ingroup	InfraS
	* 	\brief		This file manages form for InfraSProject
	************************************************/

	// Libraries ************************************
	include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	dol_include_once('/infrasproject/class/infrasproject.class.php');

	/************************************************
	* Class FormInfrasproject
	************************************************/
	class FormInfrasproject
	{
		public $db;	// @var DoliDB Database handler.
		public $error	= '';	// @var string Error code (or message)

		/**
		* Constructor
		*
		* @param   DATABASE		$db     db object
		* @return						void
		**/
		function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		*	HTML Form to write consumption
		*
		*	@param		User	$user		User object
		*	@param		string	$module		element object we work on
		*	@param		Project	$object		object we work on
		* 	@return		void
		**/
		public function showformwrite($user, $module, $object)
		{
			global $conf, $langs;
			$defaultwarehouse	= getDolGlobalString('INFRASPROJECT_DEFAULT_WAREHOUSE', '');
			$stockProdCat		= getDolGlobalString('INFRASPROJECT_STOCK_PROD_CAT') ? explode(',', getDolGlobalString('INFRASPROJECT_STOCK_PROD_CAT'))	: '';
			$disableEatBy		= getDolGlobalInt('PRODUCT_DISABLE_EATBY', 0);
			$disableSellBy		= getDolGlobalInt('PRODUCT_DISABLE_SELLBY', 0);
			$linkToUser			= getDolGlobalInt('INFRASPROJECT_LINK_TO_USER', 0);
			$infrasProject		= new InfraSProject($this->db);
			$form				= new Form ($this->db);
			$objectCat			= new Categorie($this->db);
			$formproduct		= new FormProduct($this->db);
			$id_entrepot		= !empty(GETPOSTINT('id_entrepot')) ? GETPOSTINT('id_entrepot') : $defaultwarehouse;
			switch($module) {
				case 'project':
					$right		= $object->statut > 0 && $user->hasRight('infrasproject', 'writeproject');
					$libelle	= $langs->trans('InfraSProjectProjectConsumption');
				break;
			}
			if (!empty($right)) {
				$selectProds	= array();
				$list_prod		= array();
				// Liste produit dans le dépôt sélectionné !!!!! Obligatoire !!!!!
				if (!empty($id_entrepot)) {
					$list_prod	= $infrasProject->list_product_warehouse($id_entrepot);
					// Filtre suivant => catégorie (optionnel)
					if (!empty($stockProdCat)) {
						$list_prods		= array();
						$list_prod_cat	= array();
						foreach ($stockProdCat as $idCat_prod) {
							$idCat_prod			= (int) $idCat_prod;
							if ($idCat_prod > 0) {
								$result	= $objectCat->fetch($idCat_prod, '', Categorie::TYPE_PRODUCT);
								if ($result <= 0) {
									dol_print_error($this->db, $objectCat->error);
								} else {
									$list_prod_cat	= $objectCat->getObjectsInCateg(Categorie::TYPE_PRODUCT, 1);
								}
								$list_prods			= array_merge($list_prods, $list_prod_cat);
							} elseif (intval($idCat_prod) == -2) {
								$sql	= 'SELECT p.rowid FROM '.$this->db->prefix().'product AS p WHERE NOT EXISTS (SELECT ck.fk_product FROM '.$this->db->prefix().'categorie_product as ck WHERE p.rowid = ck.fk_product)';
								$resql	= $this->db->query($sql);
								if (!empty($resql)) {
									for ($i = 0; $i < $this->db->num_rows($resql); $i++) {
										$objp			= $this->db->fetch_object($resql);
										$list_prods[]	= $objp->rowid;
									}
								} else {
									dol_print_error($this->db);
								}
							}
						}
						if (!empty($list_prods)) {
							foreach ($list_prods as $key => $prodID) {
								if (array_key_exists($prodID, $list_prod)) {
									$selectProds[$prodID]	= $list_prod[$prodID];
								}
							}
						}
					} else {
						$selectProds	= $list_prod;	// la liste dépend uniquement du dépôt
					}
				}
				//form for consumption
				print load_fiche_titre('Consommation', '', '');
				print '	<script  type = "text/javascript">
							$(document).ready(function() {
								$("#id_entrepot").change(function() {
									console.log("We have changed the warehouse - Reload page");
									// reload page
									window.location.href = "'.dol_escape_js(dol_escape_htmltag($_SERVER['PHP_SELF'])).'?objectType='.urlencode($module).'&id='.((int) $object->id).'&id_entrepot=" + $(this).val();
								});
							});
						</script>
						<form name = "consowrite" action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'&objectType='.urlencode($module).'" method = "post">
							<input type = "hidden" name = "token" value = "'.newToken().'">
							<input type = "hidden" name = "action" value = "conso">
							<input type = "hidden" name = "label" value = "'.$libelle.' '.$object->ref.'">
							<table class = "infrasprojectnoborder centpercent">';
				print '			<tr>
									<td class = "fieldrequired">'.$langs->trans('Warehouse').'</td>
									<td>'.$formproduct->selectWarehouses($id_entrepot, 'id_entrepot', '', 1).'</td>
									<td class = "fieldrequired">'.$langs->trans('Product').'</td>
									<td>'.$form->selectarray('product', $selectProds, '', 1, 0, 0, '', 0, 0, 0, '', 'flat', 1, '', 0, 0).'</td>
									<td class = "fieldrequired">'.$langs->trans('Quantity').'</td>
									<td><input class = "flat" name = "nbpiece" size = "10" value = ""></td>
								</tr>
								<tr>
									<td  class = "fieldrequired">'.$langs->trans('Date').'</td>
									<td'.(!empty($linkToUser) ? '' : ' colspan = "5"').'>'.$form->selectDate('', 'datem_', 0, 0, 0, '', 1, 1, 0, 0, '', '', '', 1, '', '', 'tzserver').'</td>';
				if (!empty($linkToUser)) {
					print '			<td  class = "fieldrequired">'.$langs->trans('Lastname').' / '.$langs->trans('Firstname').'</td>
									<td colspan = "3">'.img_object('', 'user', 'class = "pictofixedwidth"').$form->select_dolusers(-1, 'userlink', 1, null, 0, null, null, 0, 0, 0, 0, 0, 'minwidth100imp widthcentpercentminusxx maxwidth400').'</td>';
				}
				print '			</tr>';
				if (isModEnabled('productbatch')) {
					print '		<tr>
									<td>'.$langs->trans('batch_number').'</td>
									<td colspan = "5"><input type = "text" name = "batch_number" size = "40" value = "'.dol_escape_htmltag(GETPOST('batch_number', 'alphanohtml')).'"></td>
								</tr>';
					if (empty($disableEatBy) || empty($disableSellBy)) {
						print '	<tr>';
						if (empty($disableEatBy)) {
							print '	<td>'.$langs->trans('EatByDate').'</td>
									<td>';
							$eatbyselected	= dol_mktime(0, 0, 0, GETPOST('eatbymonth'), GETPOST('eatbyday'), GETPOST('eatbyyear'));
							$form->selectDate($eatbyselected, 'eatby', 0, 0, 1, '');
							print '	</td>';
						}
						if (empty($disableSellBy)) {
							print '	<td>'.$langs->trans('SellByDate').'</td>
									<td>';
							$sellbyselected=dol_mktime(0, 0, 0, GETPOST('sellbymonth'), GETPOST('sellbyday'), GETPOST('sellbyyear'));
							$form->selectDate($sellbyselected, 'sellby', 0, 0, 1, '');
							print '	</td>';
						}
						if (!empty($disableEatBy) || !empty($disableSellBy)) {
							print '	<td colspan = "2">&nbsp;</td>';
						}
						print '		<td colspan = "2">&nbsp;</td>
								</tr>';
					} else {
						print '		<td colspan = "6">&nbsp;</td>';
					}
				}
				print '			</table>';
				print dol_get_fiche_end();
				print '		<div class = "center">
								<td colspan = "5" align = "center">
									<input type = "submit" class = "button" value = "'.$langs->trans('Save').'">&nbsp;<input type = "submit" class = "button" name = "cancel" value = "'.$langs->trans('Cancel').'">
								</td>
							</div>
						</form>
						<br/>';
			}
		}

		/**
		*	HTML Form to view consumptions
		*
		*	@param		User	$user		User object
		*	@param		Project	$object		object we work on
		* 	@return		void
		**/
		public function showformview($user, $object)
		{
			global $langs, $conf, $hookmanager;

			$langs->loadLangs(array('products', 'stocks', 'productbatch', 'infrasproject@infrasproject'));

			// Security check
			$db						= $this->db;
			$result					= restrictedArea($user,'stock');
			$id						= GETPOSTINT('id');
			$objectType				= GETPOST('objectType','alpha');
			$ref					= GETPOST('ref','alpha');
			$msid					= GETPOSTINT('msid');
			$product_id				= GETPOSTINT('product_id');
			$action					= GETPOST('action','aZ09');
			$cancel					= GETPOST('cancel','alpha');
			$idproduct				= GETPOSTINT('idproduct');
			$search_ref				= GETPOST('search_ref', 'alpha');
			$search_date_start		= dol_mktime(0, 0, 0, GETPOSTINT('search_date_start_month'), GETPOSTINT('search_date_start_day'), GETPOSTINT('search_date_start_year'));
			$search_date_end		= dol_mktime(23, 59, 59, GETPOSTINT('search_date_end_month'), GETPOSTINT('search_date_end_day'), GETPOSTINT('search_date_end_year'));
			$search_product_ref		= trim(GETPOST('search_product_ref'));
			$search_product			= trim(GETPOST('search_product'));
			$search_batch			= trim(GETPOST('search_batch'));
			$search_movement		= GETPOST('search_movement');
			$search_warehouse		= trim(GETPOST('search_warehouse'));
			$search_inventorycode	= trim(GETPOST('search_inventorycode'));
			$search_user			= trim(GETPOST('search_user'));
			$search_qty				= trim(GETPOST('search_qty'));
			$limit					= GETPOST('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
			$page					= GETPOSTINT('page');
			 // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
			if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
				$page = 0;
			}
			$sortfield	= GETPOST('sortfield','alpha');
			$sortorder	= GETPOST('sortorder','alpha');
			if ($page < 0) {
				$page = 0;
			}
			$offset	= $limit * $page;
			if (! $sortfield) {
				$sortfield	= 'm.datem';
			}
			if (! $sortorder) {
				$sortorder	= 'DESC';
			}
			// Initialize context for list
			$contextpage			= GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'movementlist';
			$extrafields			= new ExtraFields($this->db);
			// fetch optionals attributes and labels
			$extralabels			= $extrafields->fetch_name_optionals_label('stock_mouvement');
			$search_array_options	= $extrafields->getOptionalsFromPost('stock_mouvement', '', 'search_');
			if (empty(isModEnabled('productbatch'))) {
				$batchLabel		= $langs->trans('Firstname').' / '.$langs->trans('Lastname');
				$batchEnable	= getDolGlobalInt('INFRASPROJECT_LINK_TO_USER', 0);
			} else {
				$batchLabel		= $langs->trans('BatchNumberShort');
				$batchEnable	= (isModEnabled('productbatch'));
			}
			$arrayfields	= array('m.rowid'			=> array('checked' => 1, 'label' => $langs->trans('Ref')),
									'm.datem'			=> array('checked' => 1, 'label' => $langs->trans('Date')),
									'p.ref'				=> array('checked' => 1, 'label' => $langs->trans('ProductRef')),
									'p.label'			=> array('checked' => 1, 'label' => $langs->trans('ProductLabel')),
									'm.batch'			=> array('checked' => 1, 'label' => $batchLabel,					'enabled' => ($batchEnable)),
									'pl.eatby'			=> array('checked' => 0, 'label' => $langs->trans('EatByDate'),		'enabled' => (isModEnabled('productbatch'))),
									'pl.sellby'			=> array('checked' => 0, 'label' => $langs->trans('SellByDate'),	'enabled' => (isModEnabled('productbatch'))),
									'e.ref'				=> array('checked' => 1, 'label' => $langs->trans('Warehouse'),		'enabled' => (!$id > 0)),	// If we are on specific warehouse, we hide it
									'm.fk_user_author'	=> array('checked' => 0, 'label' => $langs->trans('Author')),
									'm.inventorycode'	=> array('checked' => 1, 'label' => $langs->trans('InventoryCodeShort')),
									'm.label'			=> array('checked' => 1, 'label' => $langs->trans('MovementLabel')),
									'origin'			=> array('checked' => 1, 'label' => $langs->trans('Origin')),
									'p.price'			=> array('checked' => 1, 'label' => $langs->trans('AmountHTShort')),
									'm.value'			=> array('checked' => 1, 'label' => $langs->trans('Qty'))
									);
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';
			if (getDolGlobalInt('PRODUCT_DISABLE_SELLBY', 0)) {
				unset($arrayfields['pl.sellby']);
			}
			if (getDolGlobalInt('PRODUCT_DISABLE_EATBY', 0)) {
				unset($arrayfields['pl.eatby']);
			}
			include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
			// Do we click on purge search criteria ?
			if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) { // Both test are required to be compatible with all browsers
				$search_date_start		= '';
				$search_date_end		= '';
				$search_ref				= '';
				$search_movement		= '';
				$search_product_ref		= '';
				$search_product			= '';
				$search_warehouse		= '';
				$search_user			= '';
				$search_batch			= '';
				$search_qty				= '';
				$search_array_options	= array();
			}
			// View
			$productlot			= new ProductLot($this->db);
			$productstatic		= new Product($this->db);
			$warehousestatic	= new Entrepot($this->db);
			$movement			= new MouvementStock($this->db);
			$userstatic			= new User($this->db);
			$form				= new Form($this->db);
			$formother			= new FormOther($this->db);
			$formproduct		= new FormProduct($this->db);
			$formproject		= new FormProjets($this->db);
			$sql				= 'SELECT p.rowid, p.ref as product_ref, p.label as produit, p.price, p.fk_product_type as type, p.entity,';
			$sql				.= ' e.ref as stock, e.rowid as entrepot_id, e.lieu,';
			$sql				.= ' m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype, m.batch,';
			$sql				.= ' pl.rowid as lotid, pl.eatby, pl.sellby,';
			$sql				.= ' u.login, u.photo, u.lastname, u.firstname';
			// Add fields from extrafields
			if (!empty($extrafields->attributes['stock_mouvement']['label'])) {
				foreach ($extrafields->attributes['stock_mouvement']['label'] as $key => $val) {
					$sql	.= ($extrafields->attributes['stock_mouvement']['type'][$key] != 'separate' ? ', ef.'.$key.' as options_'.$key : '');
				}
			}
			$sql		= preg_replace('/,\s*$/', '', $sql);
			$sqlfields	= $sql; // $sql fields to remove for count total
			$sql		.= ' FROM '.$this->db->prefix().'entrepot as e, '.$this->db->prefix().'product as p, '.$this->db->prefix().'stock_mouvement as m';
			if (!empty($extrafields->attributes['stock_mouvement']['label']) && is_array($extrafields->attributes['stock_mouvement']['label']) && count($extrafields->attributes['stock_mouvement']['label'])) {
				$sql	.= ' LEFT JOIN '.$this->db->prefix().'stock_mouvement_extrafields as ef on (m.rowid = ef.fk_object)';
			}
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'user as u ON m.fk_user_author = u.rowid';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product';
			$sql	.= ' WHERE m.fk_product = p.rowid';
			if ($msid > 0) {
				$sql	.= ' AND m.rowid = '.$msid;
			}
			$sql	.= ' AND m.fk_entrepot = e.rowid';
			$sql	.= ' AND e.entity IN ('.getEntity('stock').')';
			switch (getDolGlobalInt('INFRASPROJECT_SEARCHMODE',0)) {
				case 1:
					$sql	.= ' AND m.label LIKE "%'.$this->db->escape($object->ref).'%"';
				break;
				case 2:
					$sql	.= ' AND m.inventorycode LIKE "'.$this->db->escape(getDolGlobalString('INFRASPROJECT_INVCODEPREFIX').$object->ref).'%"';
				break;
				case 3:
					$sql	.= ' AND  (m.inventorycode LIKE "'.$this->db->escape(getDolGlobalString('INFRASPROJECT_INVCODEPREFIX').$object->ref).'%" OR m.label LIKE "%'.$this->db->escape($object->ref).'%")';
				break;
			}
			if (!getDolGlobalInt('STOCK_SUPPORTS_SERVICES', 0)) {
				$sql	.= ' AND p.fk_product_type = 0';
			}
			if ($search_date_start) {
				$sql	.= ' AND m.datem >= "'.$this->db->idate($search_date_start).'"';
			}
			if ($search_date_end) {
				$sql	.= ' AND m.datem <= "'.$this->db->idate($search_date_end).'"';
			}
			if (! empty($search_ref)) {
				$sql	.= natural_search('m.rowid', $search_ref, 1);
			}
			if (! empty($search_movement)) {
				$sql	.= natural_search('m.label', $search_movement);
			}
			if (! empty($search_inventorycode)) {
				$sql	.= natural_search('m.inventorycode', $search_inventorycode);
			}
			if (! empty($search_product_ref)) {
				$sql	.= natural_search('p.ref', $search_product_ref);
			}
			if (! empty($search_product)) {
				$sql	.= natural_search('p.label', $search_product);
			}
			if ($search_warehouse > 0) {
				$sql	.= ' AND e.rowid = '.((int) $search_warehouse);
			}
			if (! empty($search_user)) {
				$sql	.= natural_search('u.login', $search_user);
			}
			if (! empty($search_batch)) {
				$sql	.= natural_search('m.batch', $search_batch);
			}
			if ($search_qty != '') {
				$sql	.= natural_search('m.value', $search_qty, 1);
			}
			// Add where from extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
			$nbtotalofrecords					= '';
			if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
				/* The fast and low memory method to get and count full list converts the sql into a sql count */
				$sqlforcount	= preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
				$sqlforcount	= preg_replace('/GROUP BY .*$/', '', $sqlforcount);
				$resql			= $db->query($sqlforcount);
				if ($resql) {
					$objforcount		= $db->fetch_object($resql);
					$nbtotalofrecords	= $objforcount->nbtotalofrecords;
				} else {
					dol_print_error($db);
				}
				if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
					$page	= 0;
					$offset	= 0;
				}
				$db->free($resql);
			}
			$sql	.= $this->db->order($sortfield, $sortorder);
			$sql	.= $limit ? $this->db->plimit($limit + 1, $offset) : '';
			$resql	= $this->db->query($sql);
			if (!empty($resql)) {
				$product	= new Product($this->db);
				if ($idproduct > 0) {
					$product->fetch($idproduct);
				}
				$num				= $this->db->num_rows($resql);
			//	$arrayofselected	= is_array($toselect) ? $toselect : array();
				$help_url			= 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
				if ($msid) {
					$texte	= $langs->trans('StockMovementForId', $msid);
				} else {
					$texte		= $langs->trans('ListOfStockMovements');
					if ($id)	$texte	.= ' ('.$langs->trans('InfraSProjectForThis'.$objectType).')';
				}
				$param																= '';
				if (! empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
					$param	.= '&contextpage='.$contextpage;
				}
				if ($limit > 0 && $limit != $conf->liste_limit) {
					$param	.= '&limit='.$limit;
				}
				if ($id > 0) {
					$param	.= '&id='.$id;
				}
				if ($search_date_start) {
					$param	.= '&search_date_start='.urlencode($search_date_start);
				}
				if ($search_date_end) {
					$param	.= '&search_date_end='.urlencode($search_date_end);
				}
				if ($search_movement) {
					$param	.= '&search_movement='.urlencode($search_movement);
				}
				if ($search_inventorycode) {
					$param	.= '&search_inventorycode='.urlencode($search_inventorycode);
				}
				if ($search_product_ref) {
					$param	.= '&search_product_ref='.urlencode($search_product_ref);
				}
				if ($search_product) {
					$param	.= '&search_product='.urlencode($search_product);
				}
				if ($search_batch) {
					$param	.= '&search_batch='.urlencode($search_batch);
				}
				if ($search_warehouse > 0) {
					$param	.= '&search_warehouse='.urlencode($search_warehouse);
				}
				if ($search_product_ref) {
					$param	.= '&search_product_ref='.urlencode($search_product_ref);
				}
				if ($search_product) {
					$param	.= '&search_product='.urlencode($search_product);
				}
				if ($search_user) {
					$param	.= '&search_user='.urlencode($search_user);
				}
				if ($idproduct > 0) {
					$param	.= '&idproduct='.$idproduct;
				}
				// Add $param from extra fields
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
			print '	<form method = "POST" action = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $id).'&objectType='.urlencode($objectType).'">
						<input type = "hidden" name = "token" value = "'.newToken().'">
						<input type = "hidden" name = "formfilteraction" id = "formfilteraction" value = "list">
						<input type = "hidden" name = "action" value = "list">
						<input type = "hidden" name = "sortfield" value = "'.dol_escape_htmltag($sortfield).'">
						<input type = "hidden" name = "sortorder" value = "'.dol_escape_htmltag($sortorder).'">
						<input type = "hidden" name = "page" value = "'.((int) $page).'">
						<input type = "hidden" name = "objectType" value = "'.dol_escape_htmltag($objectType).'">
						<input type = "hidden" name = "contextpage" value = "'.dol_escape_htmltag($contextpage).'">';
				print_barre_liste($texte, $page, dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $id).'&objectType=project', $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic', 0, '', '', $limit);
				$moreforfilter	= '';
				if (!empty($moreforfilter)) {
					print '	<div class = "liste_titre liste_titre_bydiv centpercent">'.$moreforfilter.'</div>';
				}
				$varpage		= empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
				$selectedfields	= $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));	// This also change content of $arrayfields
				print '		<div class = "div-table-responsive">
								<table class = "tagtable liste'.($moreforfilter ? ' listwithfilterbefore' : '').'">';
				// Lignes des champs de filtre
				print '				<tr class = "liste_titre_filter">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre center">';
					$searchpicto = $form->showFilterButtons('left');
					print $searchpicto;
					print '</td>';
				}
				// Ref
				if (!empty($arrayfields['m.rowid']['checked'])) {
					print '				<td class = "liste_titre left">
											<input class = "flat maxwidth25" type = "text" name = "search_ref" value = "'.dol_escape_htmltag($search_ref).'">
										</td>';
				}
				// Date
				if (! empty($arrayfields['m.datem']['checked'])) {
					print '				<td class = "liste_titre center">
											<div class = "nowrap">';
					print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start_', 0, 0, 1, '', 1, 0, 0, 0, '', '', '', 1, '', $langs->trans('From'), 'tzuserrel');
					print '					</div>
											<div class = "nowrap">';
					print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end_', 0, 0, 1, '', 1, 0, 0, 0, '', '', '', 1, '', $langs->trans('to'), 'tzuserrel');
					print '					</div>
										</td>';
				}
				// Product Ref
				if (! empty($arrayfields['p.ref']['checked'])) {
					print '				<td class = "liste_titre left">
											<input class = "flat maxwidth100" type = "text" name = "search_product_ref" value = "'.dol_escape_htmltag($idproduct ? $product->ref : $search_product_ref).'">
										</td>';
				}
				// Product label
				if (! empty($arrayfields['p.label']['checked'])) {
					print '				<td class = "liste_titre left">
											<input class = "flat maxwidth100" type = "text" name = "search_product" value = "'.dol_escape_htmltag($idproduct?$product->label:$search_product).'">
										</td>';
				}
				// Batch
				if (! empty($arrayfields['m.batch']['checked'])) {
					print '				<td class = "liste_titre center">
											<input class = "flat maxwidth100" type = "text" name = "search_batch" value = "'.dol_escape_htmltag($search_batch).'">
										</td>';
				}
				if (! empty($arrayfields['pl.eatby']['checked'])) {
					print '				<td class = "liste_titre left">&nbsp;</td>';
				}
				if (! empty($arrayfields['pl.sellby']['checked'])) {
					print '				<td class = "liste_titre left">&nbsp;</td>';
				}
				// Warehouse
				if (! empty($arrayfields['e.ref']['checked'])) {
					print '				<td class = "liste_titre maxwidthonsmartphone left">';
					print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, array(), 'maxwidth200');
					print '				</td>';
				}
				// Author
				if (! empty($arrayfields['m.fk_user_author']['checked'])) {
					print '				<td class = "liste_titre left">
											<input class = "flat" type = "text" size="6" name = "search_user" value = "'.dol_escape_htmltag($search_user).'">
										</td>';
				}
				// Inventory code
				if (! empty($arrayfields['m.inventorycode']['checked'])) {
					print '<td class = "liste_titre" align="left">';
					print '<input class = "flat" type = "text" size="4" name = "search_inventorycode" value = "'.dol_escape_htmltag($search_inventorycode).'">';
					print '</td>';
				}
				// Label of movement
				if (! empty($arrayfields['m.label']['checked'])) {
					print '				<td class = "liste_titre left">
											<input class = "flat" type = "text" size="6" name = "search_movement" value = "'.dol_escape_htmltag($search_movement).'">
										</td>';
				}
				// Origin of movement
				if (! empty($arrayfields['origin']['checked'])) {
					print '				<td class = "liste_titre left">&nbsp;</td>';
				}
				// Price
				if (! empty($arrayfields['p.price']['checked'])) {
					print '				<td class = "liste_titre left">&nbsp;</td>';
				}
				// Qty
				if (! empty($arrayfields['m.value']['checked'])) {
					print '				<td class = "liste_titre right">
											<input class = "flat" type = "text" size="6" name = "search_qty" value = "'.dol_escape_htmltag($search_qty).'">
										</td>';
				}
				// Extra fields
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre center">';
					$searchpicto = $form->showFilterButtons();
					print $searchpicto;
					print '</td>';
				}
				print "</tr>\n";
				$totalarray = array('nbfield' => 0);
				// Titres
				print '				<tr class = "liste_titre">';
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['m.rowid']['checked'])) {
					print_liste_field_titre($arrayfields['m.rowid']['label'], $_SERVER['PHP_SELF'], 'm.rowid', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['m.datem']['checked'])) {
					print_liste_field_titre($arrayfields['m.datem']['label'], $_SERVER['PHP_SELF'], 'm.datem', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['p.ref']['checked'])) {
					print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER['PHP_SELF'], 'p.ref', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['p.label']['checked'])) {
					print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER['PHP_SELF'], 'p.label', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['m.batch']['checked'])) {
					print_liste_field_titre($arrayfields['m.batch']['label'], $_SERVER['PHP_SELF'], 'm.batch','', $param,'align="center"', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['pl.eatby']['checked'])) {
					print_liste_field_titre($arrayfields['pl.eatby']['label'], $_SERVER['PHP_SELF'], 'pl.eatby','', $param,'align="center"', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['pl.sellby']['checked'])) {
					print_liste_field_titre($arrayfields['pl.sellby']['label'], $_SERVER['PHP_SELF'], 'pl.sellby','', $param,'align="center"', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['e.ref']['checked'])) {
					print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER['PHP_SELF'], 'e.ref', '', $param, '', $sortfield, $sortorder);	// We are on a specific warehouse card, no filter on other should be possible
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['m.fk_user_author']['checked'])) {
					print_liste_field_titre($arrayfields['m.fk_user_author']['label'], $_SERVER['PHP_SELF'], 'm.fk_user_author', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['m.inventorycode']['checked'])) {
					print_liste_field_titre($arrayfields['m.inventorycode']['label'], $_SERVER['PHP_SELF'], 'm.inventorycode', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['m.label']['checked'])) {
					print_liste_field_titre($arrayfields['m.label']['label'], $_SERVER['PHP_SELF'], 'm.label', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['origin']['checked'])) {
					print_liste_field_titre($arrayfields['origin']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['p.price']['checked'])) {
					print_liste_field_titre($arrayfields['p.price']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (! empty($arrayfields['m.value']['checked'])) {
					print_liste_field_titre($arrayfields['m.value']['label'], $_SERVER['PHP_SELF'], 'm.value', '', $param, 'align="right"', $sortfield, $sortorder);
					$totalarray['nbfield']++;
				}
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
					$totalarray['nbfield']++;
				}
				// Extra fields
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
				print '				</tr>';
				$totalarray						= array();
				$totalarray['nbfield']			= 0;
				$totalarray['val']['p.price']	= 0;	// Total HT
				$imaxinloop						= ($limit ? min($num, $limit) : $num);
				$total_ht						= 0;
				for ($i = 0; $i < min($num, $limit); $i++) {
					$objp				= $this->db->fetch_object($resql);
					$userstatic->fetch($objp->fk_user_author);
					$productstatic->fetch($objp->rowid);
					$productlot->id		= $objp->lotid;
					$productlot->batch	= $objp->batch;
					$productlot->eatby	= $objp->eatby;
					$productlot->sellby	= $objp->sellby;
					$warehousestatic->fetch($objp->entrepot_id);
					$total_ht_by_line	= 0;
					if(!empty($objp->fk_origin)) {
						$origin	= $movement->get_origin($objp->fk_origin, $objp->origintype);
					} else {
						$origin	= '';
					}
					print '			<tr class = "oddeven">';
					// Action column
					if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print '			<td class = "nowrap center">&nbsp;</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Id movement
					if (! empty($arrayfields['m.rowid']['checked'])) {
						print '			<td>'.$objp->mid.'</td>';	// This is primary not movement id
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Date
					if (! empty($arrayfields['m.datem']['checked'])) {
						print '			<td>'.dol_print_date($this->db->jdate($objp->datem), 'day').'</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Product ref
					if (! empty($arrayfields['p.ref']['checked'])) {
						print '			<td>';
						print $productstatic->getNomUrl(1,'stock',16);
						print '			</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Product label
					if (! empty($arrayfields['p.label']['checked'])) {
						print '			<td>';
						print $productstatic->label;
						print '			</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Batch
					if (! empty($arrayfields['m.batch']['checked'])) {
						print '			<td class = "center">';
						if ($productlot->id > 0)	print $productlot->getNomUrl(1);
						else						print $productlot->batch;		// the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
						print '			</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Eat by
					if (! empty($arrayfields['pl.eatby']['checked'])) {
						print '			<td class = "center">'.dol_print_date($objp->eatby, 'day').'</td>';
					}
					// Sell by
					if (! empty($arrayfields['pl.sellby']['checked'])) {
						print '			<td class = "center">'.dol_print_date($objp->sellby, 'day').'</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Warehouse
					if (! empty($arrayfields['e.ref']['checked'])) {
						print '			<td>';
						print $warehousestatic->getNomUrl(1);
						print '			</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Author
					if (! empty($arrayfields['m.fk_user_author']['checked'])) {
						print '			<td class = "tdoverflowmax100">';
						print $userstatic->getNomUrl(-1);
						print '			</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Inventory code
					if (! empty($arrayfields['m.inventorycode']['checked'])) {
						print '			<td>'.$objp->inventorycode.'</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Label of movement
					if (! empty($arrayfields['m.label']['checked'])) {
						print '			<td class = "tdoverflowmax100aaa">'.$objp->label.'</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Origin of movement
					if (! empty($arrayfields['origin']['checked'])) {
						print '			<td>'.$origin.'</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Price
					if (! empty($arrayfields['p.price']['checked'])) {
						$total_ht_by_line	= $productstatic->pmp * ($objp->qty > 0 ? 1 : -1) * $objp->qty;
						$total_ht			= $total_ht + $total_ht_by_line;
						print '			<td class = "right">'.price($total_ht_by_line).'</td>';
						if (!$i) {
							$totalarray['nbfield']++;
							$totalarray['pos'][$totalarray['nbfield']] = 'p.price';
						}
						$totalarray['val']['p.price'] += $total_ht_by_line;
					}
					// Qty
					if (! empty($arrayfields['m.value']['checked'])) {
						print '			<td class = "right">'.($objp->qty > 0 ? '+' : '').$objp->qty.'</td>';
						if (! $i) {
							$totalarray['nbfield']++;
						}
					}
					// Extra fields
					$object	= $movement;
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
					if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print '			<td class = "nowrap center">&nbsp;</td>';
						if (!$i) {
							$totalarray['nbfield']++;
						}
						print '			</tr>';
					}
				}
				// Show total line
				include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
				$this->db->free($resql);
				print '			</table>
							</div>
						</form>';
			} else {
				dol_print_error($this->db);
			}
		}
	}
