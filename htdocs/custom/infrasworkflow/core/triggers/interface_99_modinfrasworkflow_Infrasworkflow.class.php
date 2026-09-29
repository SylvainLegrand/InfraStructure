<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
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
	*	\file		./infrasworkflow/core/triggers/interface_99_modinfrasworkflow_Infrasworkflowtrigger.class.php
	*	\ingroup	InfraS
	*	\brief		Trigger for the module InfraS
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	dol_include_once('/infrasworkflow/core/lib/infrasworkflowAdmin.lib.php');
	dol_include_once('/infrasworkflow/core/lib/infrasworkflow.lib.php');
	dol_include_once('/infrasworkflow/class/infrasworkflow_propal.class.php');

	// Description and activation class *************
	class InterfaceInfrasworkflow extends DolibarrTriggers
	{
		protected $db;	// Database handler @var DoliDB
		public $name				= '';	// Name of the trigger @var mixed|string
		public $description			= '';	// Description of the trigger @var string
		public $version				= self::VERSION_DEVELOPMENT;	// Version of the trigger @var string
		public $picto				= 'technic';	// Image of the trigger @var string
		public $family				= '';	// Category of the trigger @var string
		public $errors				= array();	// Errors reported by the trigger @var array
		const VERSION_DEVELOPMENT	= 'development';	// @var string module is in development
		const VERSION_EXPERIMENTAL	= 'experimental';	// @var string module is experimental
		const VERSION_DOLIBARR		= 'dolibarr';	// @var string module is dolibarr ready

		/**
		* Constructor
		*
		*	@param		DoliDB		$db		Database handler
		*/
		public function __construct($db)
		{
			global $langs;

			$langs->load('infrasworkflow@infrasworkflow');
			$this->db			= $db;
			$this->name			= preg_replace('/^Interface/i', '', get_class($this));
			$this->family		= 'Modules '.$langs->trans('basename');
			$this->description	= $langs->trans('Module500065DescTrigger');
			$currentversion		= infrasworkflow_getLocalVersionMinDoli('infrasworkflow');
			$this->version		= $currentversion[0];	// 'development', 'experimental', 'dolibarr' or version
			$this->picto		= 'infrasworkflow@infrasworkflow';
		}

		/**
		* Trigger name
		*
		*	@return		string	Name of trigger file
		*/
		public function getName()
		{
			return $this->name;
		}

		/**
		* Trigger description
		*
		*	@return		string	Description of trigger file
		*/
		public function getDesc()
		{
			return $this->description;
		}

		/**
		* Trigger version
		*
		*	@return		string	Version of trigger file
		*/
		public function getVersion()
		{
			global $langs;

			$langs->load('admin');
			if ($this->version == 'development') {
				return $langs->trans('Development');
			} elseif ($this->version == 'experimental') {
				return $langs->trans('Experimental');
			} elseif ($this->version == 'dolibarr') {
				return DOL_VERSION;
			} elseif (!empty($this->version)) {
				return $this->version;
			} else {
				return $langs->trans('Unknown');
			}
		}

		/**
		* Function called when a Dolibarr business event is done.
		* All functions "run_trigger" are triggered if file
		* is inside directory core/triggers
		*
		*	@param		string		$action		Event action code
		*	@param		object		$object		Object
		*	@param		User		$user		Object user
		*	@param		Translate	$langs		Object langs
		*	@param		conf		$conf		Object conf
		*	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
		*/
		public function runTrigger($action, $object, $user, $langs, $conf)
		{
			if (!isModEnabled('infrasworkflow')) {
				return 0;
			}
			if (empty($object->element) || !in_array($object->element, ['propal', 'facture', 'societe', 'product', 'contrat', 'contratdet', 'order_supplier', 'inventory'])) {
				return 0;
			}
			$propal_actions			= array('PROPAL_CLOSE_SIGNED');
			$bill_actions			= array('BILL_CREATE', 	'BILL_VALIDATE', 'BILL_PAYED');
			$societe_actions		= array('COMPANY_CREATE', 'COMPANY_MODIFY');
			$product_actions		= array('PRODUCT_CREATE', 'PRODUCT_MODIFY');
			$contract_actions		= array('CONTRACT_VALIDATE', 'OBJECT_LINK_INSERT', 'LINECONTRACT_INSERT');
			$ordersupplier_actions	= array('ORDER_SUPPLIER_CLASSIFY_BILLED');
			$inventory_actions		= array('INVENTORY_VALIDATED');
			$authorizedActions	= array_merge($propal_actions, $bill_actions, $societe_actions, $product_actions, $contract_actions, $ordersupplier_actions, $inventory_actions);
			if (!in_array($action, $authorizedActions)) {
				return 0;
			}
			if ($action == 'PROPAL_CLOSE_SIGNED' && $object->statut == Propal::STATUS_SIGNED) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
				$res	= 1;
				$res2	= 1;
				if (isModEnabled('societe') && getDolGlobalInt('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT', 0)) {
					$thirdparty	= new Societe($this->db);
					$thirdparty->fetch($object->socid);
					$clientType	= 3;
					$control	= infrasworkflow_thirdpartyAccountControl($thirdparty); // Check that all required fields are filled in the customer account when a proposal is signed
					if ($control < 1) {
						$res	= infrasworkflow_setCustomerAsProspectCustomer($thirdparty, $clientType); // if the required fields are not completed (set as Prospect)
					}
				}
				if (isModEnabled('facture')) {
					$res2	= infrasworkflow_firstAccountAuto($object);	// Generate the first account automatically when the propal is mark as signed (amount) => only if $create_first_auto_deposit is enabled
				}
				if (($res * $res2) < 0) {
					return -1;
				} else {
					return 1;
				}
			} elseif ($action === 'BILL_CREATE') {
				if (in_array($object->type, [Facture::TYPE_STANDARD, Facture::TYPE_DEPOSIT])) {
					if ($object->type == Facture::TYPE_STANDARD) {
						dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
						$result	= infrasworkflow_linkDepositsToFinalInvoice($object);	// Link the deposits found in the original proposal to the final invoice
						if ($result < 0) {
							return $result;	// Return error if any
						}
					}
					if (isModEnabled('infraspackplus')) {
						if (getDolGlobalInt('INFRASWORKFLOW_TRANSFER_PUBLIC_NOTES', 0) && getDolGlobalString('INFRASPLUS_PDF_OPTION_listnotep', '') == 'doc') {
							dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
							$resNote	= infrasworkflow_cloneNotePfromPropal($object);	// Copy the chosen public note(s) from the proposal to the invoice
							if ($resNote < 0) {
								return $resNote;	// Return error if any
							}
						}
						if (getDolGlobalString('INFRASWORKFLOW_TRANSFER_FREETEXT', '') && getDolGlobalString('INFRASPLUS_PDF_OPTION_listfreet', '') == 'doc') {
							dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
							$resFreeT	= infrasworkflow_cloneFreetextFromPropal($object);	// Copy the whitelisted freetext mention(s) from the proposal to the invoice
							if ($resFreeT < 0) {
								return $resFreeT;	// Return error if any
							}
						}
					}
				} elseif ($object->type == Facture::TYPE_CREDIT_NOTE && getDolGlobalInt('INFRASWORKFLOW_CREDIT_NOTE_TRANSFER_NOTES', 0)) {	// The InfraSPackPlus prerequisites are checked inside the function, they only apply to the note selection part
					dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
					$resNotes	= infrasworkflow_cloneNotefromInvoice($object);
					if ($resNotes < 0) {
						return $resNotes;
					}
				}
			} elseif ($action === 'BILL_VALIDATE' && $object->type == Facture::TYPE_STANDARD) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
				return infrasworkflow_factureValidation($object);
			} elseif ($action === 'BILL_PAYED' && getDolGlobalInt('INFRASWORKFLOW_INVOICE_REGENARATION_ON_CLASSIFY_PAID', 0)) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
				return infrasworkflow_pdfinvoicegeneration($object, $action);	// Regenerate the invoice PDF when the invoice is fully paid
			} elseif (in_array($action, array('COMPANY_CREATE', 'COMPANY_MODIFY')) && getDolGlobalInt('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT', 0)) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
				if ($action == 'COMPANY_CREATE') {
					$clientType	= 2;
					$control	= infrasworkflow_thirdpartyAccountControl($object);
				} elseif ($action == 'COMPANY_MODIFY') {
					$object_linked = infrasworkflow_getThirdpartyLinkedPropal($object); // Check that the third party is linked to a signed commercial proposal
					if ($object_linked > 0) {
						$clientType	= 3;
						$control	= infrasworkflow_thirdpartyAccountControl($object); // (set as prospect/customer)
					} else {
						$clientType	= 2;
						$control	= infrasworkflow_thirdpartyAccountControl($object); // Otherwise (set as prospect)
					}
				}
				if ($control < 1) {
					$res	= infrasworkflow_setCustomerAsProspectCustomer($object, $clientType);
					if ($res < 0) {
						return -1;
					} else {
						return 1;
					}
				}
			} elseif (in_array($action, array('PRODUCT_CREATE', 'PRODUCT_MODIFY')) && getDolGlobalInt('INFRASWORKFLOW_PRODUCT_VALIDATION_CONTROL', 0)) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
				$productControl	= infrasworkflow_productValidationControl($object);
				if ($productControl < 0) {
					$setAsOffSale = infrasworkflow_setProductAsOffSale($object, 0);
					if ($setAsOffSale < 0) {
						return -1;
					} else {
						return 1;
					}
				}
			} elseif ($action == 'CONTRACT_VALIDATE' && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_SERVICE_AUTO', 0)) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
				$date_start	= dol_now();
				$object->activateAll($user, $date_start, 0, '', -1);
			} elseif ($action == 'OBJECT_LINK_INSERT' && $object instanceof Contrat && !empty($object->context['link_origin']) && $object->context['link_origin'] == 'facture' && !empty($object->context['link_origin_id'])) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->id);
				// An invoice has just been linked to the contract
				$invoice			= new Facture($this->db);
				$nb_invoices_linked	= 0;
				$invoice->fetch($object->context['link_origin_id']);
				if ($invoice->type == Facture::TYPE_STANDARD) {
					// Check if there are items linked to the contract
					$object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
					// Count linked invoices (excluding the one just added)
					if (!empty($object->linkedObjectsIds['facture'])) {
						$nb_invoices_linked	= count($object->linkedObjectsIds['facture']);
						// If the current invoice is in the list, decrement the counter
						if (in_array($invoice->id, $object->linkedObjectsIds['facture'])) {
							$nb_invoices_linked--;
						}
					}
					if (empty($nb_invoices_linked)) {
						// Ensure contract extrafields are loaded before modifying them
						$object->fetch_optionals();
						$needUpdate	= false;
						if (getDolGlobalString('INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT')) {	// If no other invoice is linked to the contract and the option to copy invoice extrafields to contract is enabled
						// Get the list of extrafields to copy from invoice to contract
						$exfListStr	= getDolGlobalString('INFRASWORKFLOW_EXF_INVOICE_TO_CONTRACT', '');
						$exfList	= array_filter(explode(',', $exfListStr));
						// Copy each extrafield from invoice to contract
						foreach ($exfList as $exfKey) {
							$exfKey	= trim($exfKey);
							if (!empty($exfKey) && isset($invoice->array_options['options_'.$exfKey])) {
								$object->array_options['options_'.$exfKey] = $invoice->array_options['options_'.$exfKey];
									$needUpdate	= true;
								}
							}
						}
						// Copy invoice date to the selected contract date extrafield(s)
						$exfDateListStr	= getDolGlobalString('INFRASWORKFLOW_EXF_INVOICE_DATE_TO_CONTRACT', '');
						$exfDateList	= array_filter(explode(',', $exfDateListStr));
						if (!empty($invoice->date) && !empty($exfDateList)) {
							foreach ($exfDateList as $exfDateKey) {
								$exfDateKey		= trim($exfDateKey);
								if (!empty($exfDateKey)) {
									$object->array_options['options_'.$exfDateKey] = $invoice->date;
									$needUpdate	= true;
								}
							}
						}
						// Update contract with new extrafields values
						if ($needUpdate) {
							$object->insertExtraFields();
						}
					}
				}
			} elseif ($action == 'LINECONTRACT_INSERT' && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE', 0)) {
				// A contract line has just been added : make sure a catalog product is typed as a product.
				// Two emitters : Contrat::addline() carries the new line id in its context and passes the
				// contract itself, while ContratLigne::insert() passes the line.
				$lineid	= ($object->element == 'contratdet') ? (int) $object->id : (empty($object->context['line_id']) ? 0 : (int) $object->context['line_id']);
				if ($lineid > 0) {
					dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' line id = '.$lineid);
					$res	= infrasworkflow_forceContractProductLineType($lineid);
					if ($res < 0) {
						return -1;
					}
					return 1;
				}
			} elseif ($action == 'ORDER_SUPPLIER_CLASSIFY_BILLED' && getDolGlobalInt('INFRASWORKFLOW_SUPPLIER_ORDER_RECEIVED_ON_BILLED', 0)) {
				// Supplier order classified billed while at status "Ordered" (3) -> auto-move to "Received completely" (5), without any stock movement
				if (isset($object->statut) && $object->statut == $object::STATUS_ORDERSENT) {
					dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '.__FILE__.' id = '.$object->id);
					$res	= $object->setStatus($user, $object::STATUS_RECEIVED_COMPLETELY);
					if ($res < 0) {
						$this->errors	= array_merge($this->errors, (array) $object->errors);
						return -1;
					}
					return 1;
				}
			} elseif ($action == 'INVENTORY_VALIDATED' && $object->element == 'inventory' && (getDolGlobalInt('INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE', 0) || getDolGlobalInt('INFRASWORKFLOW_DISPLAY_SORTED_EMPTY_STOCK', 0))) {
				dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '.__FILE__.' id = '.$object->id);
				// Remove the lines of the products to hide (obsolete category, neither for sale nor for purchase)
				$res	= infrasworkflow_inventoryPurgeHiddenLines($object);
				if ($res < 0) {
					$this->errors[]	= $langs->trans('InfraSWorkflowInventoryErrorPurgeLines');
					return -1;
				}
				// Add a line (expected qty 0) for the matching products without stock record in the inventoried warehouse(s)
				$res	= infrasworkflow_inventoryAddEmptyStockLines($object, $user, GETPOSTINT('include_sub_warehouse'));	// The sub-warehouses choice is only known from the validation dialog (same request)
				if ($res < 0) {
					$this->errors[]	= $langs->trans('InfraSWorkflowInventoryErrorAddLines');
					return -1;
				}
				return 1;
			}
			return 0;
		}
	}
