<?php
	/************************************************
	* Copyright (C) 2026-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasfiles/class/infrasfilesinventory.class.php
	* 	\ingroup	InfraS
	* 	\brief		Child class of the native Inventory adding the documents layer (generateDocument, document state)
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
	dol_include_once('/infrasfiles/class/infrasfilesdocumenttrait.class.php');

	/************************************************
	* Class InfrasFilesInventory
	************************************************/
	class InfrasFilesInventory extends Inventory
	{
		use InfrasFilesDocumentTrait;

		public $infrasfiles_element = 'inventory';
		public $infrasfiles_filters = array();	// Filters of the inventory (categories, product) as labels, see infrasfilesGetFilters()

		/**
		*	Create a document onto disk according to template module
		*
		*	@param		string		$modele			Force template to use ('' to not force)
		*	@param		Translate	$outputlangs	Object lang to use for translation
		*	@param		int			$hidedetails	Hide details of lines
		*	@param		int			$hidedesc		Hide description
		*	@param		int			$hideref		Hide ref
		*	@param		array|null	$moreparams		Array to provide more information
		*	@return		int							1 if OK, <= 0 if KO
		**/
		public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
		{
			$outputlangs->loadLangs(array('stocks', 'products', 'productbatch', 'categories', 'infrasfiles@infrasfiles'));
			if (empty($this->specimen) && $this->infrasfilesFetchLines() < 0) {
				return -1;	// $this->error set : never generate a silent empty sheet
			}
			return $this->infrasfilesGenerate($modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams, array(array('suffix' => '', 'label' => '')));
		}

		/**
		*	Fill the object with specimen data (no database record) : used by the preview of the models on the setup page
		*
		*	@return		void
		**/
		public function infrasfilesInitAsSpecimen()
		{
			global $langs;

			$langs->loadLangs(array('products', 'stocks', 'categories', 'infrasfiles@infrasfiles'));
			$this->id				= 0;
			$this->specimen			= 1;
			$this->ref				= 'SPECIMEN';
			$this->title			= $langs->transnoentities('InfraSFilesSpecimen');
			$this->status			= self::STATUS_DRAFT;
			$this->date_creation	= dol_now();
			$this->date_inventory	= dol_now();
			$this->fk_warehouse		= 0;
			$this->fk_product		= 0;
			$this->categories_product	= '';
			$this->infrasfiles_filters	= array('categories' => array($langs->transnoentities('InfraSFilesSpecimen').' '.$langs->transnoentities('Category')), 'product' => '');
			$this->infrasfiles_lines	= array();
			for ($i = 1; $i <= 5; $i++) {
				$this->infrasfiles_lines[]	= array('rowid'				=> $i,
													'fk_product'		=> $i,
													'fk_warehouse'		=> 0,
													'batch'				=> ($i % 2 ? '' : 'LOT-'.$i),
													'qty_stock'			=> 10 * $i,
													'qty_view'			=> null,
													'product_ref'		=> 'PROD-SPECIMEN-'.$i,
													'product_label'		=> $langs->transnoentities('InfraSFilesSpecimen').' '.$langs->transnoentities('Product').' '.$i,
													'barcode'			=> '',
													'tobatch'			=> ($i % 2 ? 0 : 1),
													'warehouse_ref'		=> $langs->transnoentities('Warehouse'),
													'warehouse_lieu'	=> '',
													'zone'				=> ($i <= 3 ? 'A' : 'B'),
													'zone_label'		=> $langs->transnoentities('InfraSFilesPdfZone').' '.($i <= 3 ? 'A' : 'B'),
													'zone_rank'			=> ($i <= 3 ? 0 : 1));
			}
		}

		/**
		*	Filters of the inventory printed in the head of the sheet : product categories and single product, as labels
		*
		*	@return		array		array('categories' => list of labels, 'product' => 'REF - label' or '')
		**/
		public function infrasfilesGetFilters()
		{
			if (!empty($this->infrasfiles_filters) && is_array($this->infrasfiles_filters)) {
				return $this->infrasfiles_filters;	// specimen
			}
			$filters	= array('categories' => array(), 'product' => '');
			$catids		= array_filter(array_map('intval', explode(',', (string) $this->categories_product)));
			if (!empty($catids)) {
				$sql	= 'SELECT label FROM '.$this->db->prefix().'categorie WHERE rowid IN ('.implode(',', $catids).') ORDER BY label ASC';
				$resql	= $this->db->query($sql);
				while ($resql && ($obj = $this->db->fetch_object($resql))) {
					$filters['categories'][]	= (string) $obj->label;
				}
			}
			if ((int) $this->fk_product > 0) {
				$sql	= 'SELECT ref, label FROM '.$this->db->prefix().'product WHERE rowid = '.((int) $this->fk_product);
				$resql	= $this->db->query($sql);
				if ($resql && ($obj = $this->db->fetch_object($resql))) {
					$filters['product']	= $obj->ref.(!empty($obj->label) ? ' - '.$obj->label : '');
				}
			}
			$this->infrasfiles_filters	= $filters;
			return $filters;
		}
		/**
		*	Load the inventory lines with product, warehouse and storage zone into $this->infrasfiles_lines.
		*	The zone follows the "Zone" column setup of InfraSWorkflow (infrasfiles_inventory_zone_config()) : product extrafield
		*	or location category (oldest category of the product among the sub categories of the configured parent).
		*	Lines are sorted by rank of the zone (order of the list / creation order of the categories), then product ref, then batch ;
		*	unknown zones after the known ones, lines without zone last.
		*
		*	@return		int			Number of lines, -1 if KO
		**/
		public function infrasfilesFetchLines()
		{
			$this->infrasfiles_lines	= array();
			$config	= infrasfiles_inventory_zone_config();
			$zones	= infrasfiles_inventory_zone_list($config);
			$sql	= 'SELECT d.rowid, d.fk_product, d.fk_warehouse, d.batch, d.qty_stock, d.qty_view,';
			$sql	.= ' p.ref AS product_ref, p.label AS product_label, p.barcode, p.tobatch,';
			$sql	.= ' e.ref AS warehouse_ref, e.lieu AS warehouse_lieu';
			if ($config['source'] == 'extrafield') {
				$sql	.= ', pe.'.$config['extrafield'].' AS zone';	// code validated by regex in infrasfiles_inventory_zone_config()
			} elseif ($config['source'] == 'category') {
				$sql	.= ', zc.zone';
			} else {
				$sql	.= ", '' AS zone";
			}
			$sql	.= ' FROM '.$this->db->prefix().'inventorydet AS d';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'product AS p ON p.rowid = d.fk_product';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'entrepot AS e ON e.rowid = d.fk_warehouse';
			if ($config['source'] == 'extrafield') {
				$sql	.= ' LEFT JOIN '.$this->db->prefix().'product_extrafields AS pe ON pe.fk_object = p.rowid';
			} elseif ($config['source'] == 'category') {
				// One zone per product : the oldest sub category of the parent among those of the product (id, resolved to label / rank below)
				$sql	.= ' LEFT JOIN (SELECT cp.fk_product, MIN(c.rowid) AS zone FROM '.$this->db->prefix().'categorie_product AS cp';
				$sql	.= ' INNER JOIN '.$this->db->prefix().'categorie AS c ON c.rowid = cp.fk_categorie WHERE c.fk_parent = '.((int) $config['category']).' GROUP BY cp.fk_product) AS zc ON zc.fk_product = p.rowid';
			}
			$sql	.= ' WHERE d.fk_inventory = '.((int) $this->id);
			$sql	.= ' ORDER BY e.ref ASC, p.ref ASC, d.batch ASC';
			$resql	= $this->db->query($sql);
			if (!$resql) {
				$this->error	= $this->db->lasterror();
				return -1;
			}
			while ($obj = $this->db->fetch_object($resql)) {
				$zone	= (string) $obj->zone;
				if ($zone !== '' && isset($zones[$zone])) {
					$label	= $zones[$zone]['label'];
					$rank	= $zones[$zone]['rank'];
				} elseif ($zone !== '' && $config['source'] == 'extrafield') {
					$label	= $zone;	// text extrafield or value removed from the list : raw value, after the known zones, alphabetical
					$rank	= PHP_INT_MAX - 1;
				} else {
					$zone	= '';	// no zone (or category id not found) : last
					$label	= '';
					$rank	= PHP_INT_MAX;
				}
				$this->infrasfiles_lines[]	= array('rowid'				=> (int) $obj->rowid,
													'fk_product'		=> (int) $obj->fk_product,
													'fk_warehouse'		=> (int) $obj->fk_warehouse,
													'batch'				=> (string) $obj->batch,
													'qty_stock'			=> $obj->qty_stock === null ? null : (float) $obj->qty_stock,
													'qty_view'			=> $obj->qty_view === null ? null : (float) $obj->qty_view,
													'product_ref'		=> (string) $obj->product_ref,
													'product_label'		=> (string) $obj->product_label,
													'barcode'			=> (string) $obj->barcode,
													'tobatch'			=> (int) $obj->tobatch,
													'warehouse_ref'		=> (string) $obj->warehouse_ref,
													'warehouse_lieu'	=> (string) $obj->warehouse_lieu,
													'zone'				=> $zone,
													'zone_label'		=> $label,
													'zone_rank'			=> $rank);
			}
			// Sort : zone (rank, then key for unknown values), then product ref, then batch
			usort($this->infrasfiles_lines, function ($a, $b) {
				if ($a['zone_rank'] != $b['zone_rank']) {
					return $a['zone_rank'] < $b['zone_rank'] ? -1 : 1;
				}
				$cmp	= strnatcasecmp($a['zone_label'], $b['zone_label']);
				if ($cmp != 0) {
					return $cmp;
				}
				$cmp	= strnatcasecmp($a['product_ref'], $b['product_ref']);
				return $cmp != 0 ? $cmp : strnatcasecmp($a['batch'], $b['batch']);
			});
			return count($this->infrasfiles_lines);
		}
	}
