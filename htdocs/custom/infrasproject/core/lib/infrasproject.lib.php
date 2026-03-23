<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		../infrasproject/core/lib/infrasproject.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Functions used by InfraSProject module
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

	/**
	* Is substitution file
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	bool
	**/
	function infrasproject_is_substitution_page($path)
	{
		if (strpos($path, 'infrasproject/substitutionpages/') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * infrasproject_isDolTms check if a timestamp is valid.
	 *
	 * @param  int|string|null $timestamp timestamp to check
	 * @return bool
	 */
	function infrasproject_isDolTms($timestamp)
	{
		if ($timestamp === '') {
			dol_syslog('Using empty string for a timestamp is deprecated, prefer use of null when calling page '.$_SERVER['PHP_SELF'].infrasproject_getCallerInfoString(), LOG_NOTICE);
			return false;
		}
		if (is_null($timestamp) || !is_numeric($timestamp)) {
			return false;
		}
		return true;
	}

	/**
	 * Get caller info as a string that can be appended to a log message.
	 *
	 * @return string
	 */
	function infrasproject_getCallerInfoString()
	{
		$backtrace	= debug_backtrace();
		$msg		= '';
		if (count($backtrace) >= 1) {
			$pos	= 1;
			if (count($backtrace) == 1) {
				$pos	= 0;
			}
			$trace	= $backtrace[$pos];
			if (isset($trace['file'], $trace['line'])) {
				$msg	= ' From {'.$trace['file'].'}:'.$trace['line'].'.';
			}
		}
		return $msg;
	}

	/**
	* Get substitution url if exist
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	string	substitution url or empty
	**/
	function infrasproject_get_substitution_url($path)
	{
		$const_name	= infrasproject_get_const_name_from_substitution_path($path);
		if (getDolGlobalString($const_name, '')) {
			$dolibranch		= explode('.', DOL_VERSION);
			$dolinfras		= getDolGlobalString('EASYA_VERSION', '') || getDolGlobalString('DOLINFRAS_VERSION', '');
			$coreVersion	= 'dlb'.$dolibranch[0].'0x'.($dolinfras ? '-DolInfraS' : '');
			$path_dst		= '/infrasproject/substitutionpages/'.$coreVersion.$path;
			$real_path_dst	= dol_buildpath($path_dst, 0);
			dol_syslog('infrasproject.lib.php::infrasproject_get_substitution_url $path = '.$path.' $real_path_dst = '.$real_path_dst);
			if (file_exists($real_path_dst)) {
				$url_path_dst = dol_buildpath($path_dst, 2);
				return $url_path_dst;
			}
		}
		return '';
	}

	/**
	* Get const name from substitution path
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	string		Substitution url or empty
	**/
	function infrasproject_get_const_name_from_substitution_path($path)
	{
		$const_name	= 'INFRASPROJECT_PS_ACTIVE'.strtoupper(str_replace('/', '_', str_replace('.php', '', $path)));
		return $const_name;
	}

	/**
	* Get substitution redirect URL with filtered query params
	*
	* @return	string		Redirect URL or empty string if no redirect needed
	**/
	function infrasproject_getSubstitutionRedirectUrl()
	{
		$path_src	= preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/i', '', $_SERVER['PHP_SELF']);
		if (infrasproject_is_substitution_page($path_src)) {
			return '';
		}
		$url	= infrasproject_get_substitution_url($path_src);
		if (empty($url)) {
			return '';
		}
		// Forward only GET params (not POST which may contain login credentials)
		// Exclude token (CSRF) which is page-specific and would be invalid on redirect target
		$params	= $_GET;
		unset($params['token']);
		$query	= http_build_query($params);
		return $url.(!empty($query) ? '?'.$query : '');
	}

	/**
	*	Show project reference linked to the supplier invoice line
	*
	*	@param  int		$lineid		rowid of the supplier invoice line
	*	@param  int		$idOnly		return project ID instead of HTML name url
	*	@return	string				HTML value || project ID
	**/
	function infrasproject_printprj($lineid, $idOnly = 0)
	{
		global $db;

		$out	= '';
		$sql	= 'SELECT fk_projet FROM '.$db->prefix().'facture_fourn_det WHERE rowid = '.((int) $lineid);
		$resql	= $db->query($sql);
		dol_syslog('infrasproject.lib::infrasproject_printprj sql = '.$sql);
		if ($resql) {
			$obj_prj	= $db->fetch_object($resql);
			if (!empty($obj_prj->fk_projet)) {
				$proj	= new Project($db);
				$proj->fetch($obj_prj->fk_projet);
				$out	= empty($idOnly) ? $proj->getNomUrl(1) : $obj_prj->fk_projet;
			}
		} else {
			dol_print_error($db);
			return 'Error '.$db->lasterror();
		}
		$db->free($resql);
		return $out;
	}

	/**
	 * Replace key with value in array of table
	 *
	 * @param	array	$array			Array to check
	 * @param	string	$fieldKey		Field key to check
	 * @param	string	$fieldValue		Field value to check
	 * @return	array
	 */
	function infrasproject_replaceKeyArray($array, $fieldKey = 'key', $fieldValue = 'value')
	{
		$result	= array();
		if (!empty($array) && is_array($array)) {
			foreach ($array as $item) {
				if (isset($item[$fieldKey]) && isset($item[$fieldValue])) {
					$result[$item[$fieldKey]] = $item[$fieldValue];
				}
			}
		}
		return $result;
	}

	/**
	*	Find user ID from firstname and lastname
	*
	*	@param  string		$search		string with first name and lastname of the user
	*	@return	int|string				User ID || 0 if no user found || -1 or -2 on errors || sql error
	**/
	function infrasproject_finduser($search)
	{
		global $db;

		$out = '';
		if (!empty($search)) {
			$search = explode(' ', $search);
			if (!empty($search[0]) && !empty($search[1])) {
				$sql  = 'SELECT rowid';
				$sql .= ' FROM '.$db->prefix().'user';
				$sql .= ' WHERE (firstname LIKE "'.$db->escape($search[0]).'" AND lastname LIKE "'.$db->escape($search[1]).'")';
				$sql .= ' OR (firstname LIKE "'.$db->escape($search[1]).'" AND lastname LIKE "'.$db->escape($search[0]).'")';
				$sql .= ' AND entity IN ('.getEntity('user').')';
				$resql = $db->query($sql);
				if ($resql) {
					$obj_user = $db->fetch_object($resql);
					$out = !empty($obj_user->rowid) ? $obj_user->rowid : 0;
				} else {
					dol_print_error($db);
					return 'Error '.$db->lasterror();
				}
			} else {
				$out = -1;
			}
		} else {
			$out = -2;
		}
		$db->free($resql);
		return $out;
	}

	/**
	 * Get the list of referent elements linked to a project
	 *
	 * @param	int 		$id		Project ID
	 * @param	int 		$socid	Thirdparty ID
	 * @return 	array
	 */
	function infrasproject_getListOfReferent ($id = 0, $socid = 0)
	{
		global $user;

		$check_param	= empty($id) && empty($socid) ? false : true;
		$listofreferent	= array('entrepot'				=> array('name'			=> 'Warehouse',
																'title'			=> 'ListWarehouseAssociatedProject',
																'class'			=> 'Entrepot',
																'table'			=> 'entrepot',
																'datefieldname'	=> 'date_entrepot',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/product/stock/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'entrepot',
																'buttonnew'		=> 'AddWarehouse',
																'project_field'	=> 'fk_project',
																'testnew'		=> $user->hasRight('stock', 'creer'),
																'test'			=> isModEnabled('stock') && $user->hasRight('stock', 'lire') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_PROJECT'),
																'testparam'		=> isModEnabled('stock')
																),
								'propal'				=> array('name'			=> 'Proposals',
																'title'			=> 'ListProposalsAssociatedProject',
																'class'			=> 'Propal',
																'provmargin'	=> 'add',
																'table'			=> 'propal',
																'datefieldname'	=> 'datep',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/comm/propal/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'propal',
																'buttonnew'		=> 'AddProp',
																'testnew'		=> $user->hasRight('propal', 'creer'),
																'test'			=> isModEnabled('propal') && $user->hasRight('propal', 'lire'),
																'testparam'		=> isModEnabled('propal')
																),
								'order'					=> array('name'			=> 'CustomersOrders',
																'title'			=> 'ListOrdersAssociatedProject',
																'class'			=> 'Commande',
																'table'			=> 'commande',
																'datefieldname'	=> 'date_commande',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/commande/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'orders',
																'buttonnew'		=> 'CreateOrder',
																'testnew'		=> $user->hasRight('commande', 'creer'),
																'test'			=> isModEnabled('commande') && $user->hasRight('commande', 'lire'),
																'testparam'		=> isModEnabled('commande')
																),
								'invoice'				=> array('name'			=> 'CustomersInvoices',
																'title'			=> 'ListInvoicesAssociatedProject',
																'class'			=> 'Facture',
																'margin'		=> 'add',
																'table'			=> 'facture',
																'datefieldname'	=> 'datef',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/compta/facture/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'bills',
																'buttonnew'		=> 'CreateBill',
																'testnew'		=> $user->hasRight('facture', 'creer'),
																'test'			=> isModEnabled('facture') && $user->hasRight('facture', 'lire'),
																'testparam'		=> isModEnabled('facture')
																),
								'invoice_predefined'	=> array('name'			=> 'PredefinedInvoices',
																'title'			=> 'ListPredefinedInvoicesAssociatedProject',
																'class'			=> 'FactureRec',
																'table'			=> 'facture_rec',
																'datefieldname'	=> 'datec',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/compta/facture/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'bills',
																'buttonnew'		=> 'CreateBill',
																'testnew'		=> $user->hasRight('facture', 'creer'),
																'test'			=> isModEnabled('facture') && $user->hasRight('facture', 'lire'),
																'testparam'		=> isModEnabled('facture')
																),
								'proposal_supplier'		=> array('name'			=> 'SupplierProposals',
																'title'			=> 'ListSupplierProposalsAssociatedProject',
																'class'			=> 'SupplierProposal',
																'table'			=> 'supplier_proposal',
																'datefieldname'	=> 'date_valid',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/supplier_proposal/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '', // No socid parameter here, the socid is often the customer and we create a supplier object
																'lang'			=> 'supplier_proposal',
																'buttonnew'		=> 'AddSupplierProposal',
																'testnew'		=> $user->hasRight('supplier_proposal', 'creer'),
																'test'			=> isModEnabled('supplier_proposal') && $user->hasRight('supplier_proposal', 'lire'),
																'testparam'		=> isModEnabled('supplier_proposal')
																),
								'order_supplier'		=> array('name'			=> 'SuppliersOrders',
																'title'			=> 'ListSupplierOrdersAssociatedProject',
																'class'			=> 'CommandeFournisseur',
																'provmargin'	=> 'minus',
																'table'			=> 'commande_fournisseur',
																'datefieldname'	=> 'date_commande',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/fourn/commande/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '', // No socid parameter here, the socid is often the customer and we create a supplier object
																'lang'			=> 'suppliers',
																'buttonnew'		=> 'AddSupplierOrder',
																'testnew'		=> $user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer'),
																'test'			=> isModEnabled('supplier_order') && $user->hasRight('fournisseur', 'commande', 'lire') || $user->hasRight('supplier_order', 'lire'),
																'testparam'		=> isModEnabled('supplier_order')
																),
								'invoice_supplier'		=> array('name'			=> 'BillsSuppliers',
																'title'			=> 'ListSupplierInvoicesAssociatedProject',
																'class'			=> 'FactureFournisseur',
																'margin'		=> 'minus',
																'table'			=> 'facture_fourn',
																'datefieldname'	=> 'datef',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/fourn/facture/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '', // No socid parameter here, the socid is often the customer and we create a supplier object
																'lang'			=> 'suppliers',
																'buttonnew'		=> 'AddSupplierInvoice',
																'testnew'		=> $user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer'),
																'test'			=> isModEnabled('supplier_invoice') && $user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('supplier_invoice', 'lire'),
																'testparam'		=> isModEnabled('supplier_invoice')
																),
								'contract'				=> array('name'			=> 'Contracts',
																'title'			=> 'ListContractAssociatedProject',
																'class'			=> 'Contrat',
																'table'			=> 'contrat',
																'datefieldname'	=> 'date_contrat',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/contrat/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'contracts',
																'buttonnew'		=> 'AddContract',
																'testnew'		=> $user->hasRight('contrat', 'creer'),
																'test'			=> isModEnabled('contrat') && $user->hasRight('contrat', 'lire'),
																'testparam'		=> isModEnabled('contrat')
																),
								'intervention'			=> array('name'			=> 'Interventions',
																'title'			=> 'ListFichinterAssociatedProject',
																'class'			=> 'Fichinter',
																'table'			=> 'fichinter',
																'datefieldname'	=> 'date_valid',
																'disableamount'	=> 0,
																'margin'		=> 'minus',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/fichinter/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'interventions',
																'buttonnew'		=> 'AddIntervention',
																'testnew'		=> $user->hasRight('ficheinter', 'creer'),
																'test'			=> isModEnabled('ficheinter') && $user->hasRight('ficheinter', 'lire'),
																'testparam'		=> isModEnabled('ficheinter')
																),
								'shipping'				=> array('name'			=> 'Shippings',
																'title'			=> 'ListShippingAssociatedProject',
																'class'			=> 'Expedition',
																'table'			=> 'expedition',
																'datefieldname'	=> 'date_valid',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/expedition/card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'sendings',
																'buttonnew'		=> 'CreateShipment',
																'testnew'		=> 0,
																'test'			=> isModEnabled('expedition') && $user->hasRight('expedition', 'lire'),
																'testparam'		=> isModEnabled('expedition')
																),
								'mrp'					=> array('name'			=> 'MO',
																'title'			=> 'ListMOAssociatedProject',
																'class'			=> 'Mo',
																'table'			=> 'mrp_mo',
																'datefieldname'	=> 'date_valid',
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/mrp/mo_card.php?action=create&origin=project&originid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'mrp',
																'buttonnew'		=> 'CreateMO',
																'testnew'		=> $user->hasRight('mrp', 'write'),
																'project_field'	=> 'fk_project',
																'nototal'		=> 1,
																'test'			=> isModEnabled('mrp') && $user->hasRight('mrp', 'read'),
																'testparam'		=> isModEnabled('mrp')
																),
								'trip'					=> array('name'			=> 'TripsAndExpenses',
																'title'			=> 'ListExpenseReportsAssociatedProject',
																'class'			=> 'Deplacement',
																'table'			=> 'deplacement',
																'datefieldname'	=> 'dated',
																'margin'		=> 'minus',
																'disableamount'	=> 1,
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/deplacement/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'trips',
																'buttonnew'		=> 'AddTrip',
																'testnew'		=> $user->hasRight('deplacement', 'creer'),
																'test'			=> isModEnabled('deplacement') && $user->hasRight('deplacement', 'lire'),
																'testparam'		=> isModEnabled('deplacement')
																),
								'expensereport'			=> array('name'				=> 'ExpenseReports',
																'title'				=> 'ListExpenseReportsAssociatedProject',
																'class'				=> 'ExpenseReportLine',
																'table'				=> 'expensereport_det',
																'type_fees_code'	=> explode(',', getDolGlobalString('INFRASPROJECT_TYPE_FEES_NOT_INCLUDED_IN_MARGIN', '')),
																'datefieldname'		=> 'date',
																'provmargin'		=> 'minus',
																'margin'			=> 'minus',
																'disableamount'		=> 0,
																'urlnew'			=> $check_param ? DOL_URL_ROOT.'/expensereport/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'				=> 'trips',
																'buttonnew'			=> 'AddTrip',
																'testnew'			=> $user->hasRight('expensereport', 'creer'),
																'test'				=> isModEnabled('expensereport') && $user->hasRight('expensereport', 'lire'),
																'testparam'			=> isModEnabled('expensereport')
																),
								'donation'				=> array('name'			=> 'Donation',
																'title'			=> 'ListDonationsAssociatedProject',
																'class'			=> 'Don',
																'margin'		=> 'add',
																'table'			=> 'don',
																'datefieldname'	=> 'datedon',
																'disableamount'	=> 0,
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/don/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'donations',
																'buttonnew'		=> 'AddDonation',
																'testnew'		=> $user->hasRight('don', 'creer'),
																'test'			=> isModEnabled('don') && $user->hasRight('don', 'lire'),
																'testparam'		=> isModEnabled('don')
																),
								'loan'					=> array('name'			=> 'Loan',
																'title'			=> 'ListLoanAssociatedProject',
																'class'			=> 'Loan',
																'margin'		=> 'add',
																'table'			=> 'loan',
																'datefieldname'	=> 'datestart',
																'disableamount'	=> 0,
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/loan/card.php?action=create&projectid='.$id.'&socid='.$socid.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'loan',
																'buttonnew'		=> 'AddLoan',
																'testnew'		=> $user->hasRight('loan', 'write'),
																'test'			=> isModEnabled('loan') && $user->hasRight('loan', 'read'),
																'testparam'		=> isModEnabled('loan')
																),
								'chargesociales'		=> array('name'			=> 'SocialContribution',
																'title'			=> 'ListSocialContributionAssociatedProject',
																'class'			=> 'ChargeSociales',
																'margin'		=> 'minus',
																'table'			=> 'chargesociales',
																'datefieldname'	=> 'date_ech',
																'disableamount'	=> 0,
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/compta/sociales/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'compta',
																'buttonnew'		=> 'AddSocialContribution',
																'testnew'		=> $user->hasRight('tax', 'charges', 'lire'),
																'test'			=> isModEnabled('tax') && $user->hasRight('tax', 'charges', 'lire'),
																'testparam'		=> isModEnabled('tax')
																),
								'project_task'			=> array('name'			=> 'TaskTimeSpent',
																'title'			=> 'ListTaskTimeUserProject',
																'class'			=> 'Task',
																'margin'		=> 'minus',
																'table'			=> 'projet_task',
																'datefieldname'	=> 'element_date',
																'disableamount'	=> 0,
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/projet/tasks/time.php?withproject=1&action=createtime&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'buttonnew'		=> 'AddTimeSpent',
																'testnew'		=> $user->hasRight('project', 'creer'),
																'test'			=> isModEnabled('project') && $user->hasRight('projet', 'lire') && !getDolGlobalInt('PROJECT_HIDE_TASKS'),
																'testparam'		=> isModEnabled('project')
																),
								'stock_mouvement'		=> array('name'			=> 'InfraSProjectProjectConsumption',
																'title'			=> 'ListMouvementStockProject',
																'class'			=> 'MouvementStock',
																'table'			=> 'stock_mouvement',
																'datefieldname'	=> 'datem',
																'margin'		=> 'minus',
																'project_field' => 'fk_project',
																'disableamount'	=> 0,
																'test'			=> isModEnabled('stock') && $user->hasRight('stock', 'mouvement', 'lire') && getDolGlobalString('STOCK_MOVEMENT_INTO_PROJECT_OVERVIEW'),
																'testparam'		=> isModEnabled('stock')
																),
								'salaries'				=> array('name'			=> 'Salaries',
																'title'			=> 'ListSalariesAssociatedProject',
																'class'			=> 'Salary',
																'table'			=> 'salary',
																'datefieldname'	=> 'datesp',
																'margin'		=> 'minus',
																'disableamount'	=> 0,
																'urlnew'		=> DOL_URL_ROOT.'/salaries/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id),
																'lang'			=> 'salaries',
																'buttonnew'		=> 'AddSalary',
																'testnew'		=> $user->hasRight('salaries', 'write'),
																'test'			=> isModEnabled('salaries') && $user->hasRight('salaries', 'read'),
																'testparam'		=> isModEnabled('salaries')
																),
								'variouspayment'		=> array('name'			=> 'VariousPayments',
																'title'			=> 'ListVariousPaymentsAssociatedProject',
																'class'			=> 'PaymentVarious',
																'table'			=> 'payment_various',
																'datefieldname'	=> 'datev',
																'margin'		=> 'minus',
																'disableamount'	=> 0,
																'urlnew'		=> $check_param ? DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create&projectid='.$id.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$id) : '',
																'lang'			=> 'banks',
																'buttonnew'		=> 'AddVariousPayment',
																'testnew'		=> $user->hasRight('banque', 'modifier'),
																'test'			=> isModEnabled('banque') && $user->hasRight('banque', 'lire') && !getDolGlobalString('BANK_USE_OLD_VARIOUS_PAYMENT'),
																'testparam'		=> isModEnabled('banque')
																),
							);
		return $listofreferent;
	}

