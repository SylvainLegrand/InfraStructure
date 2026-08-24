<?php
/* Copyright (C) 2022 Éric Seigne <eric.seigne@cap-rel.fr>
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
 */

/**
 * \file    core/triggers/interface_99_modUptoSign_UptoSignTriggers.class.php
 * \ingroup uptosign
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modUptoSign_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');


/**
 *  Class of triggers for UptoSign module
 */
class InterfaceUptoSignTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "UptoSign triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'uptosign@uptosign';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * trigger called on user modification to remove uptosign specific rights
	 *
	 * @param   string     $action  [$action description]
	 * @param   CommonObject     $object  $object description
	 * @param   User       $user    [$user description]
	 * @param   Translate  $langs   [$langs description]
	 * @param   Conf       $conf    [$conf description]
	 *
	 */
	private function userModify($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $db;
		$toremove = [];
		// dol_syslog("Custom Trigger uptosign userModify '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		// dol_syslog("uptosign object : " . json_encode($object));
		// dol_syslog("uptosign user : " . json_encode($user));
		$listOfCheckUsers = explode(',', getDolGlobalString('UPTOSIGN_DOLIBARR_USERS_SIGN', '')); // InfraS change $conf->global->X non défini tant que la constante n'a jamais été écrite
		dol_syslog("uptosign signlist = " . json_encode($listOfCheckUsers));

		//object = user modified, implication uptosign, si on lui a supprimé le droit de signer il faut le supprimer de notre liste de signataire possibles
		$object->getRights();
		// InfraS change begin
		if ($object->hasRight('uptosign', 'sign')) { // ->rights->uptosign->sign n'existe pas tant que le droit n'a jamais été accordé à cet utilisateur
			//user can sign, nothing to do
		} else {
			//remove perm -> propagate to uptosign stuff
			$toremove = [$object->id];
		}
		// InfraS change end
		//autre cas de figure, l'utilisateur est maintenant externe
		if (!empty($object->socid)) {
			$toremove = [$object->id];
		}

		if (count($toremove) > 0) {
			dol_syslog("uptosign remove user from signlist " . json_encode($toremove));
		}
		$listOfCheckUsers = array_diff($listOfCheckUsers, $toremove);

		dol_syslog("uptosign signlist = " . json_encode($listOfCheckUsers));
		dolibarr_set_const($db, 'UPTOSIGN_DOLIBARR_USERS_SIGN', implode(',', $listOfCheckUsers), 'chaine', 0, '', $conf->entity);

		return 1;
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $db;
		$result = 0;
		if (empty($conf->uptosign) || empty($conf->uptosign->enabled)) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
		// For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
		$methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
		$callback = array($this, $methodName);
		dol_syslog("uptosign: Trigger ".$this->name." will call $methodName function");
		if (is_callable($callback)) {
			dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".(isset($object->id) ? $object->id : ''));	// InfraS change
			return call_user_func($callback, $action, $object, $user, $langs, $conf);
		};

		// Or you can execute some code here
		switch ($action) {
			// Users
			//case 'USER_CREATE':
			//case 'USER_MODIFY':
			//case 'USER_NEW_PASSWORD':
			//case 'USER_ENABLEDISABLE':
			//case 'USER_DELETE':

			// Actions
			//case 'ACTION_MODIFY':
			//case 'ACTION_CREATE':
			//case 'ACTION_DELETE':

			// Groups
			//case 'USERGROUP_CREATE':
			//case 'USERGROUP_MODIFY':
			//case 'USERGROUP_DELETE':

			// Companies
			//case 'COMPANY_CREATE':
			//case 'COMPANY_MODIFY':
			//case 'COMPANY_DELETE':

			// Contacts
			case 'CONTACT_CREATE':
				if (getDolGlobalInt('UPTOSIGN_FORCE_FIRST_CONTACT_AS_SIGNER')) {	// InfraS change
					$uptoSign = new UptoSign($db);
					$res = $uptoSign->giveAllRolesToContact($object);
					if ($res > 0) {
						dol_syslog("uptosign: UptoSignAssignAllSignRoleToContact trigger ok");
					} else {
						dol_syslog("uptosign: UptoSignAssignAllSignRoleToContact trigger error");
					}
				}
				break;
			//case 'CONTACT_MODIFY':
			//case 'CONTACT_DELETE':
			//case 'CONTACT_ENABLEDISABLE':

			// Products
			//case 'PRODUCT_CREATE':
			//case 'PRODUCT_MODIFY':
			//case 'PRODUCT_DELETE':
			//case 'PRODUCT_PRICE_MODIFY':
			//case 'PRODUCT_SET_MULTILANGS':
			//case 'PRODUCT_DEL_MULTILANGS':

			//Stock mouvement
			//case 'STOCK_MOVEMENT':

			//MYECMDIR
			//case 'MYECMDIR_CREATE':
			//case 'MYECMDIR_MODIFY':
			//case 'MYECMDIR_DELETE':

			// Customer orders
			//case 'ORDER_CREATE':
			//case 'ORDER_MODIFY':
			case 'ORDER_MODIFY':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'ORDER_VALIDATE':
			//case 'ORDER_DELETE':
			case 'ORDER_DELETE':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'ORDER_CANCEL':
			case 'ORDER_CANCEL':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'ORDER_SENTBYMAIL':
			//case 'ORDER_CLASSIFY_BILLED':
			//case 'ORDER_SETDRAFT':
			case 'ORDER_SETDRAFT':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			case 'ORDER_UNVALIDATE':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;

			CASE 'INVOICE_SEALED':
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (getDolGlobalString('UPTOSIGN_WORKFLOW_AUTO_SEND_INVOICE_IF_FROM_PROPAL_SIGN')) {
					//Auto send
					$object->fetchObjectLinked('', 'propal', $object->id, 'facture');

					// dol_syslog("uptosign linked :: " . json_encode($object->linkedObjects));
					foreach($object->linkedObjects as $key => $value) {
						if($key == 'propal') {
							foreach($value as $propal) {
								if(isset($propal->array_options) && isset($propal->array_options['options_digitalsign']) && $propal->array_options['options_digitalsign'] == 'uptosign') {
									dol_syslog("uptosign workflow automatic invoice sealed now send by mail");
									$model = getDolGlobalString('UPTOSIGN_WORKFLOW_AUTO_SEND_INVOICE_IF_FROM_PROPAL_SIGN_MODEL');
									uptosignSendInvoiceMailModele($model, $object, 'BILL_SENTBYMAIL');
								}
							}
						}
					}
				}
				break;

			//case 'LINEORDER_INSERT':
			//case 'LINEORDER_UPDATE':
			//case 'LINEORDER_DELETE':

			// Supplier orders
			//case 'ORDER_SUPPLIER_CREATE':
			//case 'ORDER_SUPPLIER_MODIFY':
			//case 'ORDER_SUPPLIER_VALIDATE':
			//case 'ORDER_SUPPLIER_DELETE':
			//case 'ORDER_SUPPLIER_APPROVE':
			//case 'ORDER_SUPPLIER_REFUSE':
			//case 'ORDER_SUPPLIER_CANCEL':
			//case 'ORDER_SUPPLIER_SENTBYMAIL':
			//case 'ORDER_SUPPLIER_DISPATCH':
			//case 'LINEORDER_SUPPLIER_DISPATCH':
			//case 'LINEORDER_SUPPLIER_CREATE':
			//case 'LINEORDER_SUPPLIER_UPDATE':
			//case 'LINEORDER_SUPPLIER_DELETE':

			// Proposals
			//case 'PROPAL_CREATE':
			//case 'PROPAL_MODIFY':
			// PROPAL_REOPEN : dolibarr 15+
			case 'PROPAL_REOPEN':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'PROPAL_VALIDATE':
			//case 'PROPAL_SENTBYMAIL':
			case 'PROPAL_CLOSE_SIGNED':
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				if (getDolGlobalString('UPTOSIGN_WORKFLOW_PROPAL_AUTOCREATE_INVOICE_ON_PROPAL_SIGN')) {
					//call auto create invoice
					$object->fetchObjectLinked();
					if (!empty($object->linkedObjectsIds['facture'])) {
						if (empty($object->context['closedfromonlinesignature'])) {
							setEventMessages($langs->trans("uptosignInvoiceExists"), [], 'warnings');
						}
					} else {
						$invoice = uptosign_create_invoice_from_proposal($object);

						if (null != $invoice) {
							//seal the invoice ?
							if (getDolGlobalString('UPTOSIGN_WORKFLOW_INVOICE_AUTOSEAL_IF_FROM_PROPAL_SIGN')) {
								//for that invoice must be validated
								$resValidate = $invoice->validate($user);
								if ($resValidate < 0) {
									dol_syslog("uptosign: PROPAL_CLOSE_SIGNED autocreate invoice validate failed id=".$invoice->id.": ".$invoice->error, LOG_ERR);
								}
								//next is BILL_VALIDATE trigger
							}
						} else {
							dol_syslog("uptosign: PROPAL_CLOSE_SIGNED autocreate invoice from proposal id=".$object->id." failed", LOG_ERR);
						}
					}
				}
				break;
			//case 'PROPAL_CLOSE_REFUSED':
			case 'PROPAL_CLOSE_REFUSED':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'PROPAL_DELETE':
			case 'PROPAL_DELETE':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'LINEPROPAL_INSERT':
			//case 'LINEPROPAL_UPDATE':
			//case 'LINEPROPAL_DELETE':

			// SupplierProposal
			//case 'SUPPLIER_PROPOSAL_CREATE':
			//case 'SUPPLIER_PROPOSAL_MODIFY':
			//case 'SUPPLIER_PROPOSAL_VALIDATE':
			//case 'SUPPLIER_PROPOSAL_SENTBYMAIL':
			//case 'SUPPLIER_PROPOSAL_CLOSE_SIGNED':
			//case 'SUPPLIER_PROPOSAL_CLOSE_REFUSED':
			//case 'SUPPLIER_PROPOSAL_DELETE':
			//case 'LINESUPPLIER_PROPOSAL_INSERT':
			//case 'LINESUPPLIER_PROPOSAL_UPDATE':
			//case 'LINESUPPLIER_PROPOSAL_DELETE':

			// Contracts
			//case 'CONTRACT_CREATE':
			//case 'CONTRACT_MODIFY':
			//case 'CONTRACT_ACTIVATE':
			//case 'CONTRACT_CANCEL':
			//case 'CONTRACT_CLOSE':
			case 'CONTRACT_CLOSED_SIGNED':
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				// Activate all service lines when contract is signed
				// load contract lines
				if ($object instanceof Contrat && getDolGlobalInt('UPTOSIGN_ACTIVATE_SERVICES_ON_CONTRACT_SIGNED')) {
					$object->fetch_lines();
					$error			= 0;
					$date_start		= dol_now();
					$uptosignLookup = new UptoSign($this->db);
					$records = $uptosignLookup->fetchByObject($object->id, 'contrat');
					if (is_array($records)) {
						foreach ($records as $record) {
							if (!empty($record->date_sign)) {
								$date_start = $record->date_sign;
								break;
							}
						}
					}
					foreach ($object->lines as $line) {
						if ($line->statut != ContratLigne::STATUS_OPEN) {
							$resLine = $line->active_line($user, $date_start, -1, '');
							if ($resLine < 0) {
								$error++;
								// Cumulate line errors flatly (no nested array) and keep processing the other lines.
								if (!empty($line->errors)) {
									$this->errors = array_merge($this->errors, $line->errors);
								}
								if (!empty($line->error)) {
									$this->errors[] = $line->error;
								}
								dol_syslog("uptosign: Error activating contract line id=".$line->id.": ".$line->error, LOG_ERR);
							} else {
								dol_syslog("uptosign: Contract line id=".$line->id." activated successfully");
							}
						}
					}
					if ($error > 0) {
						setEventMessages($langs->trans("ErrorActivatingContractLines"), [], 'errors');
						dol_syslog("uptosign: CONTRACT_CLOSED_SIGNED activated with ".$error." line error(s), keeping the signed closure", LOG_WARNING);
					}
				}
			break;
			//case 'CONTRACT_DELETE':
			//case 'LINECONTRACT_INSERT':
			//case 'LINECONTRACT_UPDATE':
			//case 'LINECONTRACT_DELETE':

			// Bills
			//case 'BILL_CREATE':
			//case 'BILL_MODIFY':
			case 'BILL_VALIDATE':
				if (getDolGlobalString('UPTOSIGN_WORKFLOW_INVOICE_AUTOSEAL_IF_FROM_PROPAL_SIGN')) {
					$object->fetchObjectLinked('', 'propal', $object->id, 'facture');

					// dol_syslog("uptosign linked :: " . json_encode($object->linkedObjects));
					foreach($object->linkedObjects as $key => $value) {
						if($key == 'propal') {
							foreach($value as $propal) {
								if(isset($propal->array_options) && isset($propal->array_options['options_digitalsign']) && $propal->array_options['options_digitalsign'] == 'uptosign') {
									dol_syslog("uptosign workflow automatic seal invoice");
									// then uptosign stuff if workflow is active
									$uptoSign = new UptoSign($db);
									$signOrSeal = "seal";
									$object_type = uptosign_unify_object_type($object->element);
									$api_name = uptosign_unify_api_name($signOrSeal);
									$result = $uptoSign->fetchByObject((int) $object->id, $object_type, array('api_name' => $api_name));
									//quid d'un vieux process ? lancé il y a x heures / minutes ?
									if ($result) {
										dol_syslog("uptosign workflow automatic seal already started for that object !");
										setEventMessages($langs->trans('UptoSignProcessAlreadyStarted'), [], 'warnings');
										// return -1;
									}

									//verif si filename existe
									$fullFileName = uptosignFindFileToUse($object, '');
									dol_syslog("uptosign UPTOSIGN_WORKFLOW_INVOICE_AUTOSEAL_IF_FROM_PROPAL_SIGN action=$action, Choosed file is filename=$fullFileName");

									if (empty($fullFileName)) {
										$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
										$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
										$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

										$outputlangs = $langs;
										$newlang = '';
										if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
											$newlang = GETPOST('lang_id', 'aZ09');
										}
										if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
											if (empty($object->thirdparty)) {
												$object->fetch_thirdparty();
											}
											if (!empty($object->thirdparty) && !empty($object->thirdparty->default_lang)) {
												$newlang = $object->thirdparty->default_lang;
											}
										}
										if (!empty($newlang)) {
											$outputlangs = new Translate("", $conf);
											$outputlangs->setDefaultLang($newlang);
											$outputlangs->load('products');
										}
										$fact = new Facture($db);
										$ret = $fact->fetch($object->id);
										if ($ret <= 0) {
											dol_syslog("uptosign: BILL_VALIDATE autoseal cannot fetch invoice id=".$object->id.": ".$fact->error, LOG_ERR);
											break 2;
										}
										$model = $object->model_pdf;
										$resDoc = $fact->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
										if ($resDoc <= 0) {
											dol_syslog("uptosign: BILL_VALIDATE autoseal generateDocument failed for invoice id=".$fact->id.": ".$fact->error, LOG_ERR);
										}

										$object = $fact;
										$fullFileName = uptosignFindFileToUse($fact, '');
										dol_syslog("uptosign UPTOSIGN_WORKFLOW_INVOICE_AUTOSEAL_IF_FROM_PROPAL_SIGN force generate doc ... now filename=$fullFileName");
									}

									if (!empty($fullFileName)) {
										$res = $uptoSign->sealInit($user, $object, $fullFileName);
										if ($res < 0) {
											dol_syslog("uptosign: BILL_VALIDATE autoseal sealInit failed: ".implode(', ', $uptoSign->errors), LOG_ERR);
										} else {
											dol_syslog("uptosign: BILL_VALIDATE autoseal sealInit ok");
										}
										break 2;
									} else {
										dol_syslog("uptosign workflow can't find file to seal ", LOG_WARNING);
									}

								}
							}

						}
					}
				}
				break;
			//case 'BILL_UNVALIDATE':
			//case 'BILL_SENTBYMAIL':
			//case 'BILL_CANCEL':
			//case 'BILL_DELETE':
			//case 'BILL_PAYED':
			//case 'LINEBILL_INSERT':
			//case 'LINEBILL_UPDATE':
			//case 'LINEBILL_DELETE':

			//Supplier Bill
			//case 'BILL_SUPPLIER_CREATE':
			//case 'BILL_SUPPLIER_UPDATE':
			//case 'BILL_SUPPLIER_DELETE':
			//case 'BILL_SUPPLIER_PAYED':
			//case 'BILL_SUPPLIER_UNPAYED':
			//case 'BILL_SUPPLIER_VALIDATE':
			//case 'BILL_SUPPLIER_UNVALIDATE':
			//case 'LINEBILL_SUPPLIER_CREATE':
			//case 'LINEBILL_SUPPLIER_UPDATE':
			//case 'LINEBILL_SUPPLIER_DELETE':

			// Payments
			//case 'PAYMENT_CUSTOMER_CREATE':
			//case 'PAYMENT_SUPPLIER_CREATE':
			//case 'PAYMENT_ADD_TO_BANK':
			//case 'PAYMENT_DELETE':

			// Online
			//case 'PAYMENT_PAYBOX_OK':
			//case 'PAYMENT_PAYPAL_OK':
			//case 'PAYMENT_STRIPE_OK':

			// Donation
			//case 'DON_CREATE':
			//case 'DON_UPDATE':
			//case 'DON_DELETE':

			// Interventions
			//case 'FICHINTER_CREATE':
			//case 'FICHINTER_MODIFY':
			case 'FICHINTER_MODIFY':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'FICHINTER_VALIDATE':
			//case 'FICHINTER_DELETE':
			case 'FICHINTER_DELETE':
				$result = $this->cancelUptoSign($object->id, $object->element, $user, $langs);
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
			//case 'LINEFICHINTER_CREATE':
			//case 'LINEFICHINTER_UPDATE':
			//case 'LINEFICHINTER_DELETE':

			// Members
			//case 'MEMBER_CREATE':
			//case 'MEMBER_VALIDATE':
			//case 'MEMBER_SUBSCRIPTION':
			//case 'MEMBER_MODIFY':
			//case 'MEMBER_NEW_PASSWORD':
			//case 'MEMBER_RESILIATE':
			//case 'MEMBER_DELETE':

			// Categories
			//case 'CATEGORY_CREATE':
			//case 'CATEGORY_MODIFY':
			//case 'CATEGORY_DELETE':
			//case 'CATEGORY_SET_MULTILANGS':

			// Projects
			//case 'PROJECT_CREATE':
			//case 'PROJECT_MODIFY':
			//case 'PROJECT_DELETE':

			// Project tasks
			//case 'TASK_CREATE':
			//case 'TASK_MODIFY':
			//case 'TASK_DELETE':

			// Task time spent
			//case 'TASK_TIMESPENT_CREATE':
			//case 'TASK_TIMESPENT_MODIFY':
			//case 'TASK_TIMESPENT_DELETE':
			//case 'PROJECT_ADD_CONTACT':
			//case 'PROJECT_DELETE_CONTACT':
			//case 'PROJECT_DELETE_RESOURCE':

			// Shipping
			//case 'SHIPPING_CREATE':
			//case 'SHIPPING_MODIFY':
			//case 'SHIPPING_VALIDATE':
			//case 'SHIPPING_SENTBYMAIL':
			//case 'SHIPPING_BILLED':
			//case 'SHIPPING_CLOSED':
			//case 'SHIPPING_REOPEN':
			//case 'SHIPPING_DELETE':

			// and more...

			default:
				dol_syslog("uptosign: Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".(isset($object->id) ? $object->id : ''));	// InfraS change
				break;
		}

		return $result;
	}

	/**
	 * Cancel a signing if signing status is waiting
	 *
	 * @param	int	$objectId	id of dolibarr object to cancel
	 * @param	string	$objectType	type of dolibarr object to cancel
	 * @param User $user object user
	 * @param Translate $langs	object language
	 *
	 * @return int -1 NOK, 1 Cancelled, 0 not cancelled
	 */
	private function cancelUptoSign($objectId, $objectType, $user, $langs)
	{
		global $conf;

		dol_include_once('/uptosign/lib/uptosign.lib.php');
		if (!empty($objectId)) {
			dol_include_once('/uptosign/class/uptosign.class.php');
			$uptoSign = new UptoSign($this->db);
			$result = $uptoSign->fetch(null, null, $objectId, $objectType);
			if ($result > 0) {
				$signStatus = $uptoSign->status;
				if ($signStatus == UptoSign::STATUS_WAITING) {
					// Cancel the remote procedure on the DocWizon service BEFORE removing the local record.
					// signCancel()/delete() only remove the local row and leave the remote procedure active.
					// deleteRemote() calls the remote cancel/delete endpoint first, then deleteCommon() locally.
					$res = $uptoSign->deleteRemote($user);
					if ($res < 0) {
						if (!empty($uptoSign->errors)) {
							$this->errors = $uptoSign->errors;
						}
						dol_syslog("uptosign: cancelUptoSign remote cancel failed for objectId=".$objectId." objectType=".$objectType.": ".implode(', ', (array) $uptoSign->errors), LOG_ERR);
						return -1;
					} else {
						setEventMessages($langs->trans('UptoSignCanceled'), [], 'warnings');
						return 1;
					}
				}
			}
		}
		return 0;
	}
}
