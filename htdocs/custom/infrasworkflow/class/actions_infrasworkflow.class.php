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
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	*	\file		./infrasworkflow/class/actions_infrasworkflow.class.php
	*	\ingroup	InfraS
	*	\brief		actions for infrasworkflow module (hook)
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	dol_include_once('/infrasworkflow/core/lib/infrasworkflow.lib.php');
	dol_include_once('/infrasworkflow/core/lib/infrasworkflowAdmin.lib.php');

	/************************************************
	* Class ActionsInfraSWorkflow
	************************************************/
	class ActionsInfraSWorkflow
	{
		public $db;	// @var DoliDB Database handler
		public $results	= array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $error;	// @var string
		public $errors	= array();	// @var array Errors
		public $hookmanager;

		/**
		* Constructor
		*
		* @param	DoliDB	$db		db object
		* @return	void
		**/
		public function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		* After login (../main.inc.php)
		*
		* @param	array			$parameters		empty array
		* @param	CommonObject	$user			The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			$action			Current action (if set). Generally create or edit or null
		* @return	int								< 0 on error
		**/
		function updateSession($parameters, $user, $action)
		{
			global $conf;

			// Accès au navigateur de médias de l'éditeur WYSIWYG (bouton "Parcourir le serveur") pour les non-admins :
			// core/filemanagerdol/connectors/php/config.inc.php exige d'être admin ou d'avoir le droit website->write.
			// Ce hook s'exécute avant ce contrôle (main.inc.php) : on accorde un droit website->write virtuel, limité
			// à la requête courante, aux utilisateurs disposant du droit 'accessmedias' quand l'option est activée.
			if (getDolGlobalInt('INFRASWORKFLOW_MEDIAS_BROWSER_ALL_USERS', 0) && preg_match('#/core/filemanagerdol/#', $_SERVER['PHP_SELF']) && is_object($user) && !empty($user->id) && empty($user->admin)) {
				$user->loadRights();	// Les droits réels ne sont chargés par main.inc.php qu'après ce hook
				if (!empty($user->hasRight('infrasworkflow', 'accessmedias'))) {
					if (!isModEnabled('website')) {
						$conf->modules['website']	= 'website';	// Activation virtuelle pour passer le test isModEnabled() de hasRight()
					}
					if (empty($user->rights->website)) {
						$user->rights->website	= new stdClass();
					}
					$user->rights->website->write	= 1;
				}
			}
			$redirect_url	= infrasworkflow_getSubstitutionRedirectUrl();
			if (!empty($redirect_url)) {
				session_write_close();
				header('Location: '.$redirect_url);
				exit;
			}
			return 0; // or return 1 to replace standard code
		}

		/**
		* Action exécutée après la connexion de l'utilisateur
		*
		* @param array			$parameters		Hook metadatas (context, etc...)
		* @param CommonObject	$object			L'objet à traiter
		* @param string			$action			Action actuelle
		* @param HookManager	$hookmanager	Hook manager
		* @return int							 < 0 on error
		**/
		public function afterLogin($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$currentversion	= array();
			$currentversion	= infrasworkflow_getLocalVersionMinDoli('infrasworkflow');
			if (!getDolGlobalString('INFRASWORKFLOW_DISABLE_CHECK_VERSION_MAX', '') && version_compare(explode('.', DOL_VERSION)[0], explode('.', $currentversion[4])[0], '>')) {
				setEventMessages($langs->trans('InfraSWorkflowWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			$redirect_url	= infrasworkflow_getSubstitutionRedirectUrl();
			if (!empty($redirect_url)) {
				// Commit the DB transaction opened by main.inc.php (update_last_login_date + USER_LOGIN trigger)
				$this->db->commit();
				session_write_close();
				header('Location: '.$redirect_url);
				exit;
			}
			return 0;
		}

		/**
		* Add actions buttons
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
		{
			global $langs, $user;

			if ($object instanceof Propal) { // Workflow for deposit
				$langs->load('infrasworkflow@infrasworkflow');
				$object->fetch_optionals();
				$object->fetchObjectLinked();
				$eligibleForDepositGeneration	= true;
				$nbDeposits						= 0;
				if (array_key_exists('facture', $object->linkedObjects)) {
					foreach ($object->linkedObjects['facture'] as $invoice) {
						if ($invoice->type == Facture::TYPE_DEPOSIT) {
							$nbDeposits++;
						}
					}
				}
				// Block if there is no deposit or if 2 or more deposits exist
				if ($nbDeposits >= 2 || $nbDeposits < 1) {
					$eligibleForDepositGeneration	= false;
				}
				// Recovery of the extrafield code
				$exf_second_deposit	= getDolGlobalString('INFRASWORKFLOW_EXF_SECOND_DEPOSIT', '');
				// Verification and recovery of extrafield value
				if ($eligibleForDepositGeneration && !empty($exf_second_deposit) && !empty($object->array_options['options_'.$exf_second_deposit]) && $object->statut == Propal::STATUS_SIGNED) {
					if ($user->hasRight('infrasworkflow', 'use')) {
						print '	<div class = "inline-block divButAction">
									<a class = "butAction" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&action=generate_second_deposit">'.$langs->trans('InfraSWorkflowLabelSubmitSecondDeposit').'</a>
								</div>';
					}
				}
				if (isModEnabled('societe') && getDolGlobalInt('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT', 0)) {
					if ($object->element == 'propal' && strpos($parameters['context'], 'propalcard') !== false && $object->statut == Propal::STATUS_SIGNED) {
						$thirdparty = new Societe($this->db);
						$thirdparty->fetch($object->socid);
						$usercanclose			= !getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('propal','propal_advance', 'close'));
						$usercancreate			= $user->hasRight('propal', 'creer');
						$usercandelete			= $user->hasRight('propal', 'supprimer');
						$usercancreatecontract	= $user->hasRight('contrat', 'creer');
						if ($thirdparty->client == 2 || $thirdparty->client == 3) {
							// ReOpen
							if (((getDolGlobalString('PROPAL_REOPEN_UNSIGNED_ONLY') && $object->statut == Propal::STATUS_NOTSIGNED) || (!getDolGlobalString('PROPAL_REOPEN_UNSIGNED_ONLY') && ($object->statut == Propal::STATUS_SIGNED || $object->statut == Propal::STATUS_NOTSIGNED || $object->statut == Propal::STATUS_BILLED))) && (!getDolGlobalString('PROPAL_REOPEN_UNSIGNED_ONLY') && $usercanclose)) {
								print '<a class = "butAction reposition" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&action=reopen&token='.newToken().(!getDolGlobalString('MAIN_JUMP_TAG') ? '' : '#reopen').'"';
								print '>'.$langs->trans('ReOpen').'</a>';
							}
							// Send
							if (empty($user->socid)) {
								if (getDolGlobalString('PROPOSAL_SENDBYEMAIL_FOR_ALL_STATUS')) {
									print dolGetButtonAction('', $langs->trans('SendMail'), 'default', dol_escape_htmltag($_SERVER['PHP_SELF']).'?action=presend&token='.newToken().'&id='.$object->id.'&mode=init#formmailbeforetitle', '');
								}
							}
							// Create contract
							if (isModEnabled('contrat')) {
								$langs->load('contracts');
								if ($usercancreatecontract) {
									print '<a class = "butAction" href = "'.DOL_URL_ROOT.'/contrat/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans('AddContract').'</a>';
								}
							}
							// Clone
							if ($usercancreate) {
								print '<a class = "butAction" href = "'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&socid='.$object->socid.'&action=clone&token='.newToken().'&object='.$object->element.'">'.$langs->trans('ToClone').'</a>';
							}
							// Delete
							print dolGetButtonAction($langs->trans('Delete'), '', 'delete', dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $usercandelete);

							return 1;
						}
					}
				}
			}
			// Allow the creation of a contract from a proposal as soon as it is validated (natively Dolibarr only allows it once signed)
			if ($object instanceof Propal && strpos($parameters['context'], 'propalcard') !== false && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_FROM_VALIDATED_PROPAL', 0) && isModEnabled('contrat') && $object->statut == Propal::STATUS_VALIDATED && $user->hasRight('contrat', 'creer')) {
				$langs->load('contracts');
				print '<a class = "butAction" href = "'.DOL_URL_ROOT.'/contrat/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans('AddContract').'</a>';
			}
			if ($object instanceof Societe && getDolGlobalInt('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT', 0)) {
				$langs->load('infrasworkflow@infrasworkflow');
				if ($object->element == 'societe' && $parameters['currentcontext'] == 'globalcard' && strpos($parameters['context'], 'thirdpartycomm') !== false) {
					if ($object->client == 2 || $object->client == 3) {
						$sql			= 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
						$sql			.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as billed';
						$sql			.= ' FROM '.$this->db->prefix().'societe as s';
						$sql			.= ', '.$this->db->prefix().'commande as c';
						$sql			.= ' WHERE c.fk_soc = s.rowid';
						$sql			.= ' AND s.rowid = '.((int) $object->id);
						$sql			.= ' AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))';
						$resql			= $this->db->query($sql);
						$orders2invoice	= $this->db->num_rows($resql);
						$langs->loadLangs(array('propal', 'contracts', 'fichinter', 'orders'));

						if ($object->status != 1) {
							print '<div class = "inline-block divButAction"><a class = "butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyIsClosed")).'" href = "#">'.$langs->trans("ThirdPartyIsClosed").'</a></div>';
						}
						if (isModEnabled('propal') && $user->hasRight('propal', 'creer') && $object->status == 1) {
							print '<div class = "inline-block divButAction"><a class = "butAction" href = "'.DOL_URL_ROOT.'/comm/propal/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddProp").'</a></div>';
						}
						if ($user->hasRight('contrat', 'creer') && $object->status == 1) {
							print '<div class = "inline-block divButAction"><a class = "butAction" href = "'.DOL_URL_ROOT.'/contrat/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddContract").'</a></div>';
						}
						if (isModEnabled('ficheinter') && $user->hasRight('ficheinter', 'creer') && $object->status == 1) {
							print '<div class = "inline-block divButAction"><a class = "butAction" href = "'.DOL_URL_ROOT.'/fichinter/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a></div>';
						}
						if (isModEnabled('commande')) {
							if ($object->client != 0 && $object->client != 2) {
								if (!empty($orders2invoice) && $orders2invoice > 0) {
									print '<div class = "inline-block divButAction"><a class = "butAction" href = "'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->id.'&search_billed=0&autoselectall=1">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
								} else {
									print '<div class = "inline-block divButAction"><a class = "butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("NoOrdersToInvoice")).'" href = "#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
								}
							} else {
								print '<div class = "inline-block divButAction"><a class = "butActionRefused classfortooltip" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href = "#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
							}
						}
						return 1;
					}
				}
			}
			if ($object instanceof FactureFournisseur && strpos($parameters['context'], 'invoicesuppliercard') !== false) {
				// Create supplier order from supplier invoice
				$langs->load('infrasworkflow@infrasworkflow');
				$object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
				$nbLinked	= 0;
				if (!empty($object->linkedObjectsIds)) {
					foreach ($object->linkedObjectsIds as $type => $ids) {
						$nbLinked	+= is_array($ids) ? count($ids) : 0;
					}
				} elseif (!empty($object->linkedObjects)) {
					foreach ($object->linkedObjects as $type => $objs) {
						$nbLinked	+= is_array($objs) ? count($objs) : 0;
					}
				}
				// check that the configuration INFRASWORKFLOW_CREATE_ORDER_SUPPLIER is enabled
				// We also check that the user has the right to create a supplier order
				if (getDolGlobalInt('INFRASWORKFLOW_CREATE_ORDER_SUPPLIER', 0) && $user->hasRight('fournisseur', 'commande', 'creer') && (int) $nbLinked === 0) {
					// Use action for the button to create a supplier order from the supplier invoice
					$href	= dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&action=create_supplier_order_from_invoice';
					// Afficher directement dans la barre d’actions
					print '	<div class = "inline-block divButAction">
								<a class = "butAction" href = "'.$href.'">'.$langs->trans('InfraSWorkflowCreateSupplierOrder').'</a>
							</div>';
				}
			}
			if ($object instanceof Contrat && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_EMAIL_PROV', 0) && $object->statut == Contrat::STATUS_DRAFT) {
				$params	= array('attr' => array('title' => '', 'class' => 'classfortooltip'));
				if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->hasRight('contrat', 'creer'))) {
					print dolGetButtonAction('', $langs->trans('SendMail'), 'default', dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle', '', true, $params);
				} else {
					print dolGetButtonAction('', $langs->trans('SendMail'), 'default', '#', '', false, $params);
				}
			}
			return 0;
		}

		/**
		* Confirmation
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @return	int + string					< 0 on error, 0 on success, 1 to replace standard code + $this->resprints HTML code to show
		**/
		public function formConfirm($parameters, &$object, &$action)
		{
			global $langs, $conf;

			if ($object instanceof Propal) {
				$langs->load('infrasworkflow@infrasworkflow');
				$object->fetch_optionals();
				if ($action == 'closeas') {	// Close proposal as signed or not signed
					$has_deposit				= false;
					$first_deposit_value		= 0;
					// Verification and recovery of extrafields values if extrafields exist
					$exf_first_deposit			= getDolGlobalString('INFRASWORKFLOW_EXF_DEPOSIT', '');
					if (!empty($exf_first_deposit) && isset($object->array_options['options_'.$exf_first_deposit])) {
						$first_deposit_value	= $object->array_options['options_'.$exf_first_deposit];
						if ($first_deposit_value > 0) {
							$has_deposit		= true;
						}
					}
					if ($has_deposit) {
						$create_first_auto_deposit	= getDolGlobalString('INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT', '');
						$form						= new Form($this->db);
						//Form to close proposal (signed or not)
						$formquestion	= array();
						if (!getDolGlobalInt('PROPAL_SKIP_ACCEPT_REFUSE', 0)) {
							$formquestion[]	= array('type'		=> 'select',
													'name'		=> 'statut',
													'label'		=> '<span class = "fieldrequired">'.$langs->trans('CloseAs').'</span>',
													'values'	=> array($object::STATUS_SIGNED		=> $object->LibStatut($object::STATUS_SIGNED),
																		$object::STATUS_NOTSIGNED	=> $object->LibStatut($object::STATUS_NOTSIGNED)
																		)
													);
						}
						// Field to complete private note (not replace)
						$formquestion[]	= array('type'	=> 'text',
												'name'	=> 'note_private',
												'label'	=> $langs->trans('Note'),
												'value'	=> ''
												);
						// Hidden fields to provide information to the formconfirm
						// (id of the object, value of the first deposit, etc.)
						$formquestion[]	= array('type'	=> 'hidden',
												'name'	=> 'id',
												'value'	=> $object->id
												);
						$formquestion[]	= array('type'	=> 'hidden',
												'name'	=> 'first_deposit_value',
												'value'	=> dol_escape_htmltag($first_deposit_value)
												);
						// manual account generation
						// Checkbox : Génération de l’acompte
						if (!$create_first_auto_deposit) {
							$formquestion[]	= array('type'		=> 'checkbox',
													'tdclass'	=> 'showonlyifsigned',
													'name'		=> 'generate_first_deposit_amount',
													'morecss'	=> 'flat margintoponly marginbottomonly',
													'label'		=> '<label for = "generate_first_deposit_amount">'.$langs->trans('InfraSWorkflowGenerateDepositAmount', price($first_deposit_value, 0, '', 1, -1, -1, $conf->currency)).$form->textwithpicto('', $langs->trans('InfraSWorkflowFirstDepositAmount'), 1, 'info-circle').'</label>'
												);
							// Conditional fields displayed after activation of the deposit
							$formquestion[]	= array('type'		=> 'date',
													'tdclass'	=> 'showonlyifgeneratefirstdeposit showonlyifsigned',
													'name'		=> 'datef_first',
													'label'		=> $langs->trans('DateInvoice'),
													'value'		=> dol_now()
													);
							if (getDolGlobalString('INVOICE_POINTOFTAX_DATE', '')) {
								$formquestion[]	= array('type'		=> 'date',
														'tdclass'	=> 'showonlyifgeneratefirstdeposit showonlyifsigned',
														'name'		=> 'date_first_pointoftax',
														'label'		=> $langs->trans('DatePointOfTax'),
														'value'		=> dol_now()
														);
							}
							$formquestion[]	= array('type'	=> 'other',
													'tdclass'	=> 'showonlyifgeneratefirstdeposit showonlyifsigned',
													'name'		=> 'cond_reglement_first_id',
													'label'		=> $langs->trans('PaymentTerm'),
													'value'		=> $form->getSelectConditionsPaiements(0, 'cond_reglement_first_id', -1, 0, 1, 'minwidth200')
													);
							$formquestion[]	= array('type'		=> 'checkbox',
													'tdclass'	=> 'showonlyifgeneratefirstdeposit showonlyifsigned',
													'name'		=> 'validate_generated_first_deposit',
													'morecss'	=> 'flat margintoponly marginbottomonly',
													'label'		=> $langs->trans('ValidateGeneratedDeposit')
													);
							$formquestion[]	= array('type'	=> 'onecolumn',
													'value'	=> '<script>
																	let signedValue = '.$object::STATUS_SIGNED.';
																	$(document).ready(function() {
																		$("[name=generate_deposit]").change(function () {
																			let $self = $(this);
																			let $target = $(".showonlyifgeneratedeposit").parent(".tagtr");
																			if ($self.is(":checked")) {
																				$("[name=generate_first_deposit_amount]").prop("checked", false).trigger("change");
																				$target.show();
																			} else {
																				$target.hide();
																			}
																			return true;
																		});
																		$("[name=generate_first_deposit_amount]").change(function () {
																			let $self = $(this);
																			let $target = $(".showonlyifgeneratefirstdeposit").parent(".tagtr");
																			if ($self.is(":checked")) {
																				$("[name=generate_deposit]").prop("checked", false).trigger("change");
																				$target.show();
																			} else {
																				$target.hide();
																			}
																			return true;
																		});
																		$("#statut").change(function() {
																			let $target = $(".showonlyifsigned").parent(".tagtr");
																			if ($(this).val() == signedValue) {
																				$target.show();
																			} else {
																				$target.hide();
																			}
																			$("[name=generate_deposit]").trigger("change");
																			$("[name=generate_first_deposit_amount]").trigger("change");
																			return true;
																		});
																		$("#statut").trigger("change");
																	});
																</script>'
													);
						}
						if (!getDolGlobalInt('PROPAL_SKIP_ACCEPT_REFUSE', 0)) {
							$this->resprints	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id, $langs->trans('SetAcceptedRefused'), '', 'confirm_closeas', $formquestion, '', 1, 250);
						} else {
							$this->resprints	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?statut=3&id='.$object->id, $langs->trans('Close'), '', 'confirm_closeas', $formquestion, '', 1, 250);
						}
						return 1;
					}
				} elseif ($action == 'generate_second_deposit') {	// Generate second deposit
					$has_second_deposit		= false;
					$second_deposit_value	= 0;
					// Verification and recovery of extrafields values if extrafields exist
					$exf_second_deposit		= getDolGlobalString('INFRASWORKFLOW_EXF_SECOND_DEPOSIT', '');
					if (!empty($exf_second_deposit) && isset($object->array_options['options_'.$exf_second_deposit])) {
						$second_deposit_value	= $object->array_options['options_'.$exf_second_deposit];
						if ($second_deposit_value > 0) {
							$has_second_deposit = true;
						}
					}
					if ($has_second_deposit) {
						$form			= new Form($this->db);
						$text			= $langs->trans('InfraSWorkflowConfirmGenerateSecondDeposit', price($second_deposit_value, 0, '', 1, -1, -1, $conf->currency));
						// Conditional fields displayed after activation of the deposit
						$formquestion[]	= array('type'		=> 'date',
												'tdclass'	=> '',
												'name'		=> 'datef_second',
												'label'		=> $langs->trans('DateInvoice'),
												'value'		=> dol_now()
												);
						if (getDolGlobalString('INVOICE_POINTOFTAX_DATE', '')) {
							$formquestion[]	= array('type'		=> 'date',
													'tdclass'	=> '',
													'name'		=> 'date_second_pointoftax',
													'label'		=> $langs->trans('DatePointOfTax'),
													'value'		=> dol_now()
													);
						}
						$formquestion[]		= array('type'		=> 'other',
													'tdclass'	=> '',
													'name'		=> 'cond_reglement_second_id',
													'label'		=> $langs->trans('PaymentTerm'),
													'value'		=> $form->getSelectConditionsPaiements(0, 'cond_reglement_second_id', -1, 0, 1, 'minwidth200')
													);
						$formquestion[]		= array('type'		=> 'checkbox',
													'tdclass'	=> '',
													'name'		=> 'validate_generated_second_deposit',
													'morecss'	=> 'flat margintoponly marginbottomonly',
													'label'		=> $langs->trans('ValidateGeneratedDeposit')
													);
						$this->resprints	= $form->formconfirm(dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id, $langs->trans('InfraSWorkflowLabelSubmitSecondDeposit'), $text, 'confirm_generate_second_deposit', $formquestion, 0, 1);
						return 1;
					}
				}
			}
			return 0;
		}

		/**
		* When we ask for an action (../element/card.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		public function doActions($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $langs, $user;

			// Clean duplicate supplier prices before a thirdparty merge (avoid mergeCompany failure on uk_product_fournisseur_price_ref)
			if (getDolGlobalInt('INFRASWORKFLOW_MERGE_CLEAN_DUPLICATE_SUPPLIER_PRICES', 0) && $object instanceof Societe && $action == 'confirm_merge' && GETPOST('confirm', 'alpha') == 'yes') {
				$soc_origin_id	= GETPOSTINT('soc_origin');
				if ($soc_origin_id > 0 && $object->id > 0) {
					$nbdeleted	= infrasworkflow_cleanDuplicateSupplierPricesBeforeMerge($object->id, $soc_origin_id);
					if ($nbdeleted < 0) {
						setEventMessages($langs->trans('InfraSWorkflowErrorMergeCleanSupplierPrices'), null, 'errors');
					} elseif ($nbdeleted > 0) {
						dol_syslog(__METHOD__.' : '.$nbdeleted.' duplicate supplier price(s) removed from thirdparty '.$soc_origin_id.' before merge into '.$object->id);
					}
				}
			}

			// Workflow for deposit
			if ($object instanceof Propal) {
				$object->fetch_optionals();
				if ($action == 'confirm_closeas') {	// Confirm close proposal as signed
					$create_first_auto_deposit	= getDolGlobalInt('INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT', 0);
					// Not for automatic deposit creation => we ask to create the deposit manually (auto deposit creation is managed by trigger)
					if (!$create_first_auto_deposit && GETPOSTINT('statut') == Propal::STATUS_SIGNED && isModEnabled('facture') && $user->hasRight('facture', 'creer')) {
						$locationTarget						= '';
						$error								= 0;
						$depositamount						= null;
						$first_deposit_value				= GETPOST('first_deposit_value', 'alpha');
						$deposit_percent_from_payment_terms	= getDictionaryValue('c_payment_term', 'deposit_percent', $object->cond_reglement_id);
						if (GETPOST('generate_first_deposit_amount', 'alpha') == 'on' && !empty($first_deposit_value)) {	// If the first deposit is checked (amount)
							// first deposit value less than or equal to the total amount of the proposal
							$remaining_to_pay_with_first_deposit	= price2num($object->total_ttc) - price2num($first_deposit_value);
							if ($remaining_to_pay_with_first_deposit >= 0) {
								$date 					= dol_mktime(0,0,0,GETPOSTINT('datef_firstmonth'),GETPOSTINT('datef_firstday'),GETPOSTINT('datef_firstyear'));
								$forceFields			= array();
								$object->note_private	= GETPOST('note_private', 'restricthtml');
								try {
									$depositamount	= infrasworkflow_createdepositfromorigin($object, $date, GETPOSTINT('cond_reglement_first_id'), $user, 0, GETPOST('validate_generated_first_deposit', 'alpha') == 'on', $forceFields, $first_deposit_value);
									if ($depositamount) {
										setEventMessage('DepositGenerated');
									}
									$result	= $object->closeProposal($user, GETPOSTINT('statut'), GETPOST('note_private', 'restricthtml'));
									if ($result < 0) {
										dol_syslog(__METHOD__.' : closeProposal error - '.$object->error);
										setEventMessages($object->error, $object->errors, 'errors');
										$locationTarget	= DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->id;
										$error++;
									} else {
										$locationTarget	= DOL_URL_ROOT.'/compta/facture/card.php?id='.$depositamount->id;
									}
								} catch (Throwable $e) {
									dol_syslog($langs->trans('InfraSWorkflowErrorDeposit').' : '.$e->getMessage(), LOG_ERR);
									$error++;
									setEventMessages($object->error, $object->errors, 'errors');
								}
								if ($locationTarget) {
									header('Location: '.$locationTarget);
									exit;
								}
							} else {
								setEventMessages($langs->trans('InfraSWorkflowDepositExceedsRemaining'), null, 'warnings');
							}
						} elseif (GETPOST('generate_deposit', 'alpha') == 'on' && !empty($deposit_percent_from_payment_terms)) {	// If the deposit is checked (percentage)
							$date			= dol_mktime(0, 0, 0, GETPOSTINT('datefmonth'), GETPOSTINT('datefday'), GETPOSTINT('datefyear'));
							$forceFields	= array();
							if (GETPOSTISSET('date_pointoftax')) {
								$forceFields['date_pointoftax']	= dol_mktime(0, 0, 0, GETPOSTINT('date_pointoftaxmonth'), GETPOSTINT('date_pointoftaxday'), GETPOSTINT('date_pointoftaxyear'));
							}
							$deposit	= Facture::createDepositFromOrigin($object, $date, GETPOSTINT('cond_reglement_id'), $user, 0, GETPOST('validate_generated_deposit', 'alpha') == 'on', $forceFields);
							if ($deposit) {
								setEventMessage('DepositGenerated');
								$locationTarget	= DOL_URL_ROOT.'/compta/facture/card.php?id='.$deposit->id;
							} else {
								$error++;
								setEventMessages($object->error, $object->errors, 'errors');
							}
							if (!$error) {
								$this->db->commit();
								if ($deposit && !getDolGlobalInt('MAIN_DISABLE_PDF_AUTOUPDATE', 0)) {
									$ret			= $deposit->fetch($deposit->id); // Reload to get new records
									$outputlangs	= $langs;
									if (getDolGlobalInt('MAIN_MULTILANGS')) {
										$outputlangs	= new Translate('', $conf);
										$outputlangs->setDefaultLang($deposit->thirdparty->default_lang);
										$outputlangs->load('products');
									}
									$result	= $deposit->generateDocument($deposit->model_pdf, $outputlangs, 0, 0, 0);
									if ($result < 0) {
										setEventMessages($deposit->error, $deposit->errors, 'errors');
									}
								}
								if ($locationTarget) {
									header('Location: '.$locationTarget);
									exit;
								}
							} else {
								$this->db->rollback();
								$action	= '';
							}
						}
					}
				} elseif ($action == 'confirm_generate_second_deposit') {	// Confirm generation of the second deposit
					$locationTarget		= '';
					$error				= 0;
					$depositamount		= null;
					// Récupération des codes d'extrafields
					$exf_first_deposit	= getDolGlobalString('INFRASWORKFLOW_EXF_DEPOSIT', '');
					if (!empty($exf_first_deposit) && isset($object->array_options['options_'.$exf_first_deposit])) {
						$first_deposit_value	= $object->array_options['options_'.$exf_first_deposit];
					}
					$exf_second_deposit	= getDolGlobalString('INFRASWORKFLOW_EXF_SECOND_DEPOSIT', '');
					if (!empty($exf_second_deposit) && isset($object->array_options['options_'.$exf_second_deposit])) {
						$second_deposit_value	= $object->array_options['options_'.$exf_second_deposit];
					}
					// Calculate the remaining amount to pay
					$remaining_to_pay_with_second_deposit	= price2num($object->total_ttc) -  price2num($first_deposit_value);
					if (!empty($second_deposit_value) && isModEnabled('facture') && !empty($user->rights->facture->creer) && $second_deposit_value <= $remaining_to_pay_with_second_deposit) {
						$date			= dol_mktime(0, 0, 0, GETPOSTINT('datef_secondmonth'), GETPOSTINT('datef_secondday'), GETPOSTINT('datef_secondyear'));
						$forceFields	= array();
						if (GETPOSTISSET('date_second_pointoftax')) {
							$forceFields['date_second_pointoftax']	= dol_mktime(0, 0, 0, GETPOSTINT('date_second_pointoftaxmonth'), GETPOSTINT('date_second_pointoftaxday'), GETPOSTINT('date_second_pointoftaxyear'));
						}
						try {
							$depositsecondamount	= infrasworkflow_createdepositfromorigin($object, $date, GETPOSTINT('cond_reglement_second_id'), $user, 0, GETPOST('validate_generated_second_deposit', 'alpha') == 'on', $forceFields,$second_deposit_value);
							setEventMessage('DepositGenerated');
							$locationTarget			= DOL_URL_ROOT.'/compta/facture/card.php?id='.$depositsecondamount->id;
						} catch (Throwable $e) { // PHP 7+
							dol_syslog($langs->trans('InfraSWorkflowErrorDeposit').' : '.$e->getMessage(), LOG_ERR);
							$error++;
							setEventMessages($object->error, $object->errors, 'errors');
						}
						if ($locationTarget) {
							header('Location: '.$locationTarget);
							exit;
						}
					} elseif ($second_deposit_value > $remaining_to_pay_with_second_deposit) {	// Compare to see if the first deposit exceeds the remaining amount
						setEventMessages($langs->trans('InfraSWorkflowDepositExceedsRemaining'), null, 'warnings');
					}
				}
			}
			// Create supplier order from supplier invoice
			if ($object instanceof FactureFournisseur && $action == 'create_supplier_order_from_invoice') {
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
				$commande = new CommandeFournisseur($this->db);
				// Remplir les infos principales de la commande
				$commande->socid				= $object->socid;
				$commande->ref_supplier			= $object->ref_supplier;
				$commande->date_commande		= dol_now();
				$commande->note_private			= $object->note_private;
				$commande->note_public			= $object->note_public;
				$commande->cond_reglement_id	= $object->cond_reglement_id;
				$commande->mode_reglement_id	= $object->mode_reglement_id;
				$commande->fk_project			= $object->fk_project;
				// Copier les lignes de la facture fournisseur vers la commande fournisseur
				foreach ($object->lines as $line) {
					$commande->lines[]	= (object) array('desc'				=> $line->desc,
														'product_type'		=> $line->product_type,
														'fk_product'		=> $line->fk_product,
														'qty'				=> $line->qty,
														'subprice'			=> $line->subprice,
														'tva_tx'			=> $line->tva_tx,
														'total_ht'			=> $line->total_ht,
														'total_tva'			=> $line->total_tva,
														'total_ttc'			=> $line->total_ttc,
														'remise_percent'	=> $line->remise_percent,
														'ref'				=> $line->product_ref,
														'label'				=> $line->product_label,
														'fk_unit'			=> $line->fk_unit,
														);
				}
				// Create the supplier order
				$result		= $commande->create($user);
				if ($result > 0) {
					$commande->fetch($commande->id); // recharge pour avoir tous les champs
					$commande->valid($user); // Valide la commande fournisseur
					$commande->setStatus($user,2); // Met la commande en approuvée
					$commande->classifyBilled($user); // Met la commande en facturée
					// Link supplier invoice to supplier order
					$object->add_object_linked('order_supplier', $commande->id);
					setEventMessage($langs->trans('InfraSWorkflowSupplierOrderCreatedAndLinked'));
					header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id);
					exit;
				} else {
					setEventMessage($commande->error, 'errors');
				}
			}
			if (getDolGlobalInt('INFRASWORKFLOW_CONTROL_CUSTOMER_ACCOUNT', 0) && ($object instanceof Commande || $object instanceof Facture)) {
				if ($action == 'add') {
					$socid		= GETPOSTINT('socid');
					$thirdparty = new Societe($this->db);
					$thirdparty->fetch($socid);
					if ($thirdparty->client == 1) {
						return 0;
					} elseif (in_array($thirdparty->client, array(2, 3))) {
						$controlFields = infrasworkflow_thirdpartyAccountControl($thirdparty);
						if ($controlFields >= 0) {
							return 0;
						} else {
							header('Location: /societe/card.php?socid='.$socid);
							exit;
						}
					}
				}
			}
			if ($object instanceof Contrat && getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE')) {
				if ($action == 'add' && $user->hasRight('contrat', 'creer')) {
					// Initialisations
					$error			= 0;
					$socid			= GETPOSTINT('socid');
					$origin			= GETPOST('origin', 'aZ09');
					$originid		= GETPOSTINT('originid');
					$datecontrat	= '';

					// Reconstruction de la date (priorité au schéma card.php)
					if (GETPOSTINT('remonth') && GETPOSTINT('reday') && GETPOSTINT('reyear')) {
						$datecontrat	= dol_mktime(GETPOSTINT('rehour'), GETPOSTINT('remin'), 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear')
						);
					} else {
						$datecontrat	= dol_now();
					}
					// Extrafields init
					$extrafields		= new ExtraFields($this->db);
					$extrafields->fetch_name_optionals_label($object->table_element);
					// Vérifications de base
					if (empty($datecontrat)) {
						$error++;
						setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Date')), null, 'errors');
						$action			= 'create';
					}
					if ($socid < 1) {
						$error++;
						setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ThirdParty')), null, 'errors');
						$action			= 'create';
					}

					// Récupération des extrafields postés
					if (!$error) {
						$ret	= $extrafields->setOptionalsFromPost(null, $object);
						if ($ret < 0) {
							$error++;
							$action		= 'create';
						}
					}
					// Récupération des autres champs postés
					if (!$error) {
						$object->socid						= $socid;
						$object->date_contrat				= $datecontrat;
						$object->commercial_suivi_id		= GETPOSTINT('commercial_suivi_id');
						$object->commercial_signature_id	= GETPOSTINT('commercial_signature_id');
						$object->note_private				= GETPOST('note_private', 'alpha');
						$object->note_public				= GETPOST('note_public', 'alpha');
						$object->fk_project					= GETPOSTINT('projectid');
						$object->remise_percent				= (float) price2num(GETPOST('remise_percent'), '', 2);
						$object->ref						= GETPOST('ref', 'alpha');
						$object->ref_customer				= GETPOST('ref_customer', 'alpha');
						$object->ref_supplier				= GETPOST('ref_supplier', 'alpha');
						if (!empty($origin) && !empty($originid)) {
							$element		= $subelement = $origin;
							if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
								$element	= $regs[1];
							}
							// Compatibilité
							if ($element == 'order') {
								$element	= $subelement = 'commande';
							}
							if ($element == 'propal') {
								$element	= 'comm/propal';
								$subelement = 'propal';
							}
							if ($element == 'invoice' || $element == 'facture') {
								$element	= 'compta/facture';
								$subelement	= 'facture';
							}
							// Create contract
							$object->origin								= $origin;
							$object->origin_id							= $originid;
							$object->linked_objects[$object->origin]	= $object->origin_id;
							$other_linked_objects						= GETPOST('other_linked_objects', 'array');
							if (!empty($other_linked_objects) && is_array($other_linked_objects)) {
								// Validate that keys and values are safe (integers only)
								$sanitized_links	= array();
								foreach ($other_linked_objects as $key => $val) {
									$sanitized_links[preg_replace('/[^a-z0-9_]/', '', $key)]	= (int) $val;
								}
								$object->linked_objects	= array_merge($object->linked_objects, $sanitized_links);
							}
							$id	= $object->create($user);
							if ($id > 0) {
								dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
								$classname	= ucfirst($subelement);
								$srcobject	= new $classname($this->db);
								$result		= $srcobject->fetch($object->origin_id);
								if ($result > 0) {
									$srcobject->fetch_thirdparty();
									$lines	= $srcobject->lines;
									if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
										$srcobject->fetch_lines();
										$lines		= $srcobject->lines;
									}
									if (empty($object->thirdparty) || empty($object->thirdparty->id)) {
										$object->fetch_thirdparty();
									}
									$num			= is_array($lines) ? count($lines) : 0;
									// Parcours des lignes pour n’ajouter que les produits et services (product_type=0 ou 1)
									for ($i = 0; $i < $num; $i++) {
										$product_type	= ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
										if (in_array($product_type, array(0, 1))) {
											if ($lines[$i]->fk_product > 0) {
												// Détermination label/desc multilang
												if (getDolGlobalInt('MAIN_MULTILANGS') && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
													$prod		= new Product($this->db);
													$prod->id	= $lines[$i]->fk_product;
													$prod->getMultiLangs();

													$outputlangs	= $langs;
													$newlang		= '';
													if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
														$newlang = GETPOST('lang_id', 'aZ09');
													}
													if (empty($newlang) && !empty($srcobject->thirdparty->default_lang)) {
														$newlang = $srcobject->thirdparty->default_lang;
													}
													if (!empty($newlang)) {
														$outputlangs = new Translate('', $conf);
														$outputlangs->setDefaultLang($newlang);
													}
													$label	= (!empty($prod->multilangs[$outputlangs->defaultlang]['libelle'])) ? $prod->multilangs[$outputlangs->defaultlang]['libelle'] : $lines[$i]->product_label;
												} else {
													$label = $lines[$i]->product_label;
												}
												$desc = ($lines[$i]->desc && $lines[$i]->desc != $lines[$i]->libelle) ? dol_htmlentitiesbr($lines[$i]->desc) : '';
											} else {
												$desc = dol_htmlentitiesbr($lines[$i]->desc);
											}
											// Extrafields
											$array_options		= array();
											if (method_exists($lines[$i], 'fetch_optionals')) {
												$lines[$i]->fetch_optionals();
												if (!empty($lines[$i]->array_options)) {
													$array_options	= $lines[$i]->array_options;
												}
											}
											// Calcul des taux de TVA locaux
											$txtva			= $lines[$i]->vat_src_code ? $lines[$i]->tva_tx . ' (' . $lines[$i]->vat_src_code . ')' : $lines[$i]->tva_tx;
											$localtax1_tx	= get_localtax($txtva, 1, $object->thirdparty);
											$localtax2_tx	= get_localtax($txtva, 2, $object->thirdparty);
											// Ajout de la ligne
											$resAdd 		= $object->addline($desc,
																				$lines[$i]->subprice,
																				$lines[$i]->qty,
																				$txtva,
																				$localtax1_tx,
																				$localtax2_tx,
																				$lines[$i]->fk_product,
																				$lines[$i]->remise_percent,
																				$lines[$i]->date_start,
																				$lines[$i]->date_end,
																				'HT',
																				0,
																				$lines[$i]->info_bits,
																				$lines[$i]->fk_fournprice,
																				$lines[$i]->pa_ht,
																				$array_options,
																				$lines[$i]->fk_unit,
																				count($object->lines) + 1
																				);
											if ($resAdd > 0) {
												// Sécurisation si la signature diffère (certaines versions n’intègrent pas $type) :
												if ((int)$product_type !== 1) {
													$product_type	= ($lines[$i]->product_type ? (int)$lines[$i]->product_type : 0);
												}
												$sqlFix		= 'UPDATE '.$this->db->prefix().'contratdet SET product_type='.(int)$product_type.' WHERE rowid='.(int)$resAdd;
												$this->db->query($sqlFix);
											}
											if ($resAdd < 0) {
												$error++;
												setEventMessages($object->error, $object->errors, 'errors');
												break;
											}
										} // end if in_array
									} // end for
								} else {
									setEventMessages($srcobject->error, $srcobject->errors, 'errors');
									$error++;
								}
								// Hook createFrom
								if (!$error) {
									$parameters		= array('objFrom' => $srcobject);
									$reshook		= $hookmanager->executeHooks('createFrom', $parameters, $object, $action);
									if ($reshook < 0) {
										setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
										$error++;
									}
								}
								if (!$error) {
									header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id);
									exit;
								} else {
									$action			= 'create';
								}
							} else {
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
								$action				= 'create';
							}
						} else { // Pas d'objet source
							$resCreate				= $object->create($user);
							if ($resCreate > 0) {
								header('Location: '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.$object->id);
								exit;
							} else {
								setEventMessages($object->error, $object->errors, 'errors');
								$action				= 'create';
							}
						}
					}
					return 1; // to replace standard code
				}
			}
			return 0;
		}

		/**
		* Create payment (./compta/paiement/class/paiement.class.php)
		*
		* @param	array			$parameters		array('facid' => integer, 'invoice' => object, 'remaintopay' => float)
		* @param	Paiement		$paiement		The object to process (a paiement for this hook)
		* @param	string			$action			Current action (if set). Generally create or edit or null
		* @return	int								< 0 on error, 0 to do nothing else or 1 if we made an action
		**/
		function createPayment($parameters, $paiement, $action)
		{
			// InfraSPackPlus module is enabled and we used specials payments
			if (getDolGlobalInt('INFRASWORKFLOW_INVOICE_REGENARATION_ON_CLASSIFY_PAID', 0) && $action == 'CLOSEPAIDINVOICE') {
				infrasworkflow_pdfinvoicegeneration($parameters['invoice'], $action);
				return 1;
			}
			return 0; // or return 1 to replace standard code
		}

		/**
		*	Print common footer (../main.inc.php).
		*	Dispatches to the footer treatments of the module, each one gated on its own page and on its own setup option.
		*
		*	@param	array			$parameters		Hook metadatas (context, etc...)
		*	@return	int								0 to continue, 1 to replace standard code
		**/
		function printCommonFooter($parameters)
		{
			$this->dragAndDropAttachedFilesZone();
			$this->identifyNotShippableOrderLines();
			return 0;
		}

		/**
		*	Activates Dolibarr native drag-and-drop file upload zone on any
		*	"attached files" page (any document.php tab), bound to the title
		*	row of the attached files table, regardless of the object type.
		*
		*	@return	void
		**/
		private function dragAndDropAttachedFilesZone()
		{
			global $object, $conf, $langs;

			// Only on attached files pages (any module: */document.php)
			if (! preg_match('#/document\.php$#', $_SERVER['PHP_SELF'])) {
				return;
			}
			// Feature must be enabled in module setup
			if (!getDolGlobalInt('INFRASWORKFLOW_DOCUMENTS_DRAGDROP', 0)) {
				return;
			}
			// Need a valid object loaded by the page and JS support
			if (empty($conf->use_javascript_ajax) || !is_object($object) || empty($object->id) || empty($object->element)) {
				return;
			}
			$langs->load('infrasworkflow@infrasworkflow');
			$hintHtml	= '<span class="opacitymedium small infrasworkflowDragdropHint"><i class="fas fa-cloud-upload-alt"></i> '.dol_escape_htmltag($langs->trans('InfraSWorkflowDragdropHint')).'</span>';
			print '	<script>
						jQuery(document).ready(function() {
							jQuery(".table-list-of-attached-files tr.toptitle").attr("id", "infrasworkflowDragDropZone");
							jQuery(".table-list-of-attached-files tr.toptitle .titre").append('.json_encode($hintHtml).');
						});
					</script>';
			$dragDropOutput	= dragAndDropFileUpload('infrasworkflowDragDropZone');
			// On product/service pages, when option is enabled, route AJAX upload to our custom endpoint that bypasses the saving_doc_mask auto-prefix
			if ($object->element === 'product' && getDolGlobalInt('INFRASWORKFLOW_DOCUMENTS_DRAGDROP_PRODUCT_NO_MASK', 0)) {
				$dragDropOutput	= str_replace(DOL_URL_ROOT.'/core/ajax/fileupload.php', dol_buildpath('/infrasworkflow/ajax/dragdropupload.php', 1), $dragDropOutput);
			}
			print $dragDropOutput;
		}

		/**
		*	Permet de colorer en rouge (ou couleur configurée) la quantité des lignes de la commande client qui ne peuvent pas être expédiées, sur la fiche commande,
		*	et affiche le stock réel (et virtuel) du produit dans une infobulle sur cette quantité
		*
		*	@return	void
		**/
		private function identifyNotShippableOrderLines()
		{
			global $object, $langs;

			include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

			// Uniquement sur la fiche commande client
			if (! preg_match('#/commande/card\.php$#', $_SERVER['PHP_SELF'])) {
				return;
			}
			// La fonctionnalité doit être activée dans la configuration du module
			if (!getDolGlobalInt('INFRASWORKFLOW_IDENTIFY_ORDER_LINES_NO_SHIPPABLE', 0)) {
				return;
			}
			// Il faut une commande client chargée par la page (pas le formulaire de création)
			if (! is_object($object) || empty($object->id) || empty($object->element) || $object->element != 'commande') {
				return;
			}
			// Commande annulée ou clôturée : plus rien à expédier, rien à identifier
			if ((int) $object->status < Commande::STATUS_DRAFT || (int) $object->status >= Commande::STATUS_CLOSED) {
				return;
			}
			$notshippable	= infrasworkflow_getNotShippableOrderLines($object);
			if (empty($notshippable)) {
				return;
			}
			// Récupération de la couleur, stockée en triplet hexadécimal sans # devant (rouge par défaut)
			$color		= ltrim(getDolGlobalString('INFRASWORKFLOW_COLOR_TO_IDENTIFY', 'c3000f'), '#');
			if (!preg_match('/^[0-9a-fA-F]{3}$/', $color) && !preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
				$color	= 'c3000f';
			}
			$selectors	= array();
			$tooltips	= array();
			foreach ($notshippable as $lineid => $stock) {
				$selectors[]				= '#row-'.((int) $lineid).' td.linecolqty';
				$selectors[]				= '#row-'.((int) $lineid).' td.linecolqty *';
				// Infobulle affichant le stock réel puis le stock virtuel du produit
				$tooltips[(int) $lineid]	= $langs->trans('InfraSWorkflowParamInfoStock', price2num($stock['real'], 'MS')).' '.$langs->trans('InfraSWorkflowParamInfoVirtualStock', price2num($stock['virtual'], 'MS'));
			}
			// Injection du style : applique la couleur configurée sur la cellule de quantité des lignes concernées
			print '	<style>'."\n".''.implode(', ', $selectors).' { color: #'.$color.' !important; font-weight: bold; cursor: pointer; }'."\n".'	</style>'."\n";
			// Injection du script : enveloppe le contenu de la cellule dans un span avec l'infobulle (classe native Dolibarr classfortooltip)
			print '	<script>
					jQuery(document).ready(function() {
						var infrasworkflowNoShip	= '.json_encode($tooltips).';
						jQuery.each(infrasworkflowNoShip, function(lineid, title) {
							jQuery("#row-" + lineid + " td.linecolqty").each(function() {
								jQuery(this).wrapInner(jQuery("<span>", {"class": "classfortooltip", "title": title}));
							});
						});
					});
				</script>'."\n";
		}

		/**
		*	When the form to add a new line is rendered (create mode) in the infrasworkflow contract tab.
		*	Replaces Dolibarr's native objectline_create.tpl.php with the matching versioned variant.
		*	Only fires when infrasproject is not active (infrasproject takes priority).
		*
		*	@param	array			$parameters		Hook parameters
		*	@param	CommonObject	$object			The object
		*	@param	string			$action			Current action
		*	@return	int								0 to continue, 1 to replace standard code
		**/
		function formAddObjectLine($parameters, $object, $action)
		{
			global $mysoc;

			if (empty($GLOBALS['infrasworkflow_contrat_tab_active'])) {
				return 0;
			}
			if (isModEnabled('infrasproject')) {
				return 0; // infrasproject takes priority
			}
			if (empty(infrasworkflow_pickLineTpl('create'))) {
				return 0;
			}

			$seller		= $mysoc;
			$buyer		= is_object($object->thirdparty) ? $object->thirdparty : new Societe($this->db);
			$object->formAddObjectLine(1, $seller, $buyer, '/custom/infrasworkflow/core/tpl');
			return 1;
		}

		/**
		*	Show the thirdparty type (llx_c_typent dictionary) on the order creation form.
		*
		*	@param	array			$parameters		Hook parameters ('socid', optional 'objectsrc')
		*	@param	CommonObject	$object			The order being created
		*	@param	string			$action			Current action
		*	@param	HookManager		$hookmanager	Hook manager
		*	@return	int								0 = OK/continue
		**/
		function formObjectOptions($parameters, $object, $action, $hookmanager) {

			global $langs, $db;

			if (!getDolGlobalInt('INFRASWORKFLOW_ORDER_SHOW_THIRDPARTY_TYPE', 0)) {
				return 0;
			}
			if (!preg_match('#/commande/card\.php#', $_SERVER['PHP_SELF']) || $action != 'create') {
				return 0;
			}
			$socid				= !empty($parameters['socid']) ? (int) $parameters['socid'] : 0;
			if ($socid <= 0) {
				return '';
			}
			$thirdparty	= new Societe($db);
			if ($thirdparty->fetch($socid) <= 0) {
				return '';
			}
			$langs->loadLangs(array('main', 'companies'));
			$formcompany		= new FormCompany($db);
			$typents			= $formcompany->typent_array(0);
			$label				= !empty($typents[$thirdparty->typent_id]) ? (string) $typents[$thirdparty->typent_id] : '';
			$this->resprints	= '<tr><td>'.$langs->trans('ThirdPartyType').'</td><td colspan="2">';
			$this->resprints	.= '	<input type="text" id="infrasworkflow_thirdparty_type" name="typent_id" disabled="disabled" value="'.($label ? dol_escape_htmltag($label) : $langs->trans('Undefined')).'"/>'
								.'</td></tr>';
			return 0;
		}

		/**
		*	Sub-hook: override filtertype when infrasproject renders its create-line template
		*	from the contrat products tab context. Forces empty filtertype so all product types
		*	are available (the tab itself handles product-type filtering).
		*
		*	@param	array			$parameters		array('filtertype' => string)
		*	@param	CommonObject	$object			The object
		*	@param	string			$action			Current action
		*	@return	int								0 to continue, 1 to replace standard code
		**/
		function infrasprojectFormCreateProductFilter($parameters, $object, $action)
		{
			if (empty($GLOBALS['infrasworkflow_contrat_tab_active'])) {
				return 0;
			}
			if (!is_object($object) || $object->element != 'contrat') {
				return 0;
			}
			$this->results	= array('filtertype' => '');
			return 1;
		}

		/**
		*	Sub-hook: render filtered extrafields in the create-line form when infrasproject
		*	is rendering its template from the contrat products tab context.
		*
		*	@param	array			$parameters		array('objectline' => object, 'extrafields' => object)
		*	@param	CommonObject	$object			The object
		*	@param	string			$action			Current action
		*	@return	int								0 to continue, 1 to replace standard code
		**/
		function infrasprojectFormCreateExtrafields($parameters, $object, $action)
		{
			global $db, $langs;

			if (empty($GLOBALS['infrasworkflow_contrat_tab_active'])) {
				return 0;
			}
			if (!is_object($object) || $object->element != 'contrat') {
				return 0;
			}
			$objectline				= isset($parameters['objectline'])  ? $parameters['objectline']  : null;
			$extrafields_line		= isset($GLOBALS['extrafields_line'])                    ? $GLOBALS['extrafields_line']                    : null;
			$selected_extrafields	= isset($GLOBALS['infrasworkflow_selected_extrafields']) ? $GLOBALS['infrasworkflow_selected_extrafields'] : array();
			$useCategoryMode		= !empty($GLOBALS['infrasworkflow_useCategoryMode']);
			$parentCategoryId		= isset($GLOBALS['infrasworkflow_parentCategoryId'])     ? (int) $GLOBALS['infrasworkflow_parentCategoryId']     : 0;

			if (!is_object($objectline)) {
				return 0;
			}
			if (!is_object($extrafields_line) || empty($extrafields_line->attributes[$object->table_element_line]['label'])) {
				return 0;
			}

			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

			ob_start();
			if ($useCategoryMode) {
				$parentCat	= new Categorie($db);
				$parentCat->fetch($parentCategoryId);
				$subCats	= $parentCat->get_filles();
				if (is_array($subCats) && count($subCats) > 0) {
					print '<div style="padding-top: 10px" id="extrafield_lines_area_create" name="extrafield_lines_area_create">';
					print '<table class="border centpercent">';
					print '<tr><td class="titlefield">'.$langs->trans('Category').'</td>';
					print '<td><select name="infrasworkflow_subcategory" id="infrasworkflow_subcategory" class="flat minwidth200">';
					print '<option value="">&nbsp;</option>';
					foreach ($subCats as $sc) {
						print '<option value="'.((int) $sc->id).'">'.dol_escape_htmltag($sc->label).'</option>';
					}
					print '</select></td></tr>';
					print '</table>';
					print '</div>';
				}
			} elseif (!in_array('none', $selected_extrafields)) {
				$filtered_extrafields				= new ExtraFields($db);
				$filtered_extrafields->attributes	= array();
				if (!empty($extrafields_line->attributes[$object->table_element_line])) {
					foreach ($extrafields_line->attributes[$object->table_element_line] as $attr_type => $attr_values) {
						if (!is_array($attr_values)) {
							$filtered_extrafields->attributes[$object->table_element_line][$attr_type]	= $attr_values;
							continue;
						}
						$filtered_extrafields->attributes[$object->table_element_line][$attr_type]	= array();
						foreach ($attr_values as $key => $value) {
							if (shouldDisplayContractLineExtrafield($key, $selected_extrafields, $extrafields_line, $object->table_element_line)) {
								$filtered_extrafields->attributes[$object->table_element_line][$attr_type][$key]	= $value;
							}
						}
					}
				}
				if (!empty($filtered_extrafields->attributes[$object->table_element_line]['label'])) {
					$temps	= $objectline->showOptionals($filtered_extrafields, 'create', array(), '', '', '1', 'line');
					if (!empty($temps)) {
						print '<div style="padding-top: 10px" id="extrafield_lines_area_create" name="extrafield_lines_area_create">';
						print $temps;
						print '</div>';
					}
				}
			}
			$this->resprints	= ob_get_clean();
			return 1;
		}

		/**
		*	After lines are fetched for a contract — normalise ranks if needed.
		*
		*	@param	array			$parameters		Hook parameters
		*	@param	Contrat			$object			Contract object
		*	@param	string			$action			Current action
		*	@return	int								0 = OK/continue, <0 = KO
		**/
		function afterFetchLines($parameters, &$object, &$action)
		{
			if (empty($object) || $object->element != 'contrat') {
				return 0;
			}
			if (!getDolGlobalInt('INFRASWORKFLOW_AUTO_NORMALIZE_CONTRACT_RANKS')) {
				return 0;
			}
			if (infrasworkflow_needRankNormalization($object)) {
				dol_syslog(get_class($this).'::afterFetchLines Normalizing ranks for contract '.$object->id, LOG_DEBUG);
				infrasworkflow_normalizeContractProductRanks($object->id);
				$object->fetch_lines();
			}
			return 0;
		}

		/**
		*	Tells if the amount of $object must be computed on service lines only.
		*	Product lines are attached to contracts by INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE and,
		*	by design, are not part of the contract amount.
		*	We gate on the object (and not on $parameters['currentcontext']) because this module also
		*	registers context-independent hooks (main, login...) : executeHooks() runs a module only once
		*	per call, under the first declared context, so currentcontext is unreliable.
		*
		*	@param	CommonObject	$object			Object being rendered
		*	@return	bool							true when $object is a contract handled by the module
		**/
		private function contractAmountOnServicesOnly($object)
		{
			return !empty($object)
				&& is_object($object)
				&& !empty($object->element)
				&& $object->element == 'contrat'
				&& !empty($object->id)
				&& getDolGlobalInt('INFRASWORKFLOW_CONTRACT_PRODUCTS_FROM_SOURCE');
		}
		/**
		*	Rewrite the amounts of the contract tooltip so that only service lines are counted.
		*	The contract card and the PDF already exclude product lines, the tooltip did not.
		*	Hook context : contratdao, built by CommonObject::getTooltipContent() from Contrat->element.
		*	This is the nominal path, taken whenever MAIN_ENABLE_AJAX_TOOLTIP is enabled.
		*	$parameters['tooltipcontentarray'] is a reference to the caller array : writing into it is
		*	enough, there is nothing to print nor to replace.
		*
		*	@param	array			$parameters		Hook parameters
		*	@param	Contrat			$object			Contract object
		*	@param	string			$action			Current action
		*	@param	HookManager		$hookmanager	Hook manager
		*	@return	int								0 = OK/continue
		**/
		function getTooltipContent($parameters, &$object, &$action, $hookmanager)
		{
			global $conf, $langs;
			if (!$this->contractAmountOnServicesOnly($object)) {
				return 0;
			}
			if (!isset($parameters['tooltipcontentarray']) || ! is_array($parameters['tooltipcontentarray'])) {
				return 0;
			}
			$totals	= infrasworkflow_getContractServicesTotals($object);
			// Same keys, same order and same formatting as Contrat::getTooltipContentArray()
			if (!empty($totals['total_ht'])) {
				$parameters['tooltipcontentarray']['amountht']	= '<br><b>'.$langs->trans('AmountHT').':</b> '.price($totals['total_ht'], 0, $langs, 0, -1, -1, $conf->currency);
			} else {
				unset($parameters['tooltipcontentarray']['amountht']);
			}
			if (!empty($totals['total_tva'])) {
				$parameters['tooltipcontentarray']['vatamount']	= '<br><b>'.$langs->trans('VAT').':</b> '.price($totals['total_tva'], 0, $langs, 0, -1, -1, $conf->currency);
			} else {
				unset($parameters['tooltipcontentarray']['vatamount']);
			}
			if (!empty($totals['total_ttc'])) {
				$parameters['tooltipcontentarray']['amounttc']	= '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($totals['total_ttc'], 0, $langs, 0, -1, -1, $conf->currency);
			} else {
				unset($parameters['tooltipcontentarray']['amounttc']);
			}
			return 0;
		}
		/**
		*	Fallback of getTooltipContent() when MAIN_ENABLE_AJAX_TOOLTIP is disabled : in that case
		*	Contrat::getNomUrl() calls getTooltipContentArray() directly, which exposes no hook, and the
		*	label is already embedded in the title attribute of the link when this hook runs.
		*	Hook context : contractdao, hardcoded in Contrat::getNomUrl().
		*
		*	@param	array			$parameters		Hook parameters ('getnomurl' holds the built link)
		*	@param	Contrat			$object			Contract object
		*	@param	string			$action			Current action
		*	@param	HookManager		$hookmanager	Hook manager
		*	@return	int								1 = link replaced, 0 = untouched
		**/
		function getNomUrl($parameters, &$object, &$action, $hookmanager)
		{
			// Ajax tooltips are handled by getTooltipContent(), the title attribute holds no amount here
			if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
				return 0;
			}
			if (!$this->contractAmountOnServicesOnly($object)) {
				return 0;
			}
			if (empty($parameters['getnomurl']) || ! is_string($parameters['getnomurl'])) {
				return 0;
			}
			if (!preg_match('/title="[^"]*"/', $parameters['getnomurl'])) {
				return 0;
			}
			$totals			= infrasworkflow_getContractServicesTotals($object);
			// Rebuild the label with the service-only totals, then restore the object untouched
			$savedtotalht	= $object->total_ht;
			$savedtotaltva	= $object->total_tva;
			$savedtotalttc	= $object->total_ttc;
			$object->total_ht	= $totals['total_ht'];
			$object->total_tva	= $totals['total_tva'];
			$object->total_ttc	= $totals['total_ttc'];
			$label			= implode($object->getTooltipContentArray(array('id' => $object->id, 'objecttype' => $object->element, 'nofetch' => 1)));
			$object->total_ht	= $savedtotalht;
			$object->total_tva	= $savedtotaltva;
			$object->total_ttc	= $savedtotalttc;
			if ($label === '') {
				return 0;
			}
			// preg_replace_callback and not preg_replace : the label must not be parsed for $1 / backslashes
			$newtitle			= 'title="'.dolPrintHTMLForAttribute($label).'"';
			$this->resprints	= preg_replace_callback('/title="[^"]*"/', function () use ($newtitle) {
				return $newtitle;
			}, $parameters['getnomurl'], 1);
			return 1;
		}

		/**
		*	Tells if the improved mass invoicing of orders is active on this page.
		*
		*	@return	bool
		**/
		private function massCreateBillsAllowed()
		{
			global $user;

			return preg_match('#/commande/list\.php#', $_SERVER['PHP_SELF'])
				&& isModEnabled('commande')
				&& isModEnabled('facture')
				&& getDolGlobalInt('INFRASWORKFLOW_MASS_CREATEBILLS', 0)
				&& $user->hasRight('facture', 'creer');
		}

		/**
		*	Tells if the "Classify paid" mass action on the supplier invoice list is available.
		*	We gate on $_SERVER['PHP_SELF'] (and not on $parameters['currentcontext']) because this
		*	module also registers context-independent hooks (main, login...) : executeHooks() runs a
		*	module only once per call, under the first declared context, so currentcontext is unreliable.
		*
		*	@return	bool							true if the mass action is available
		**/
		private function supplierInvoiceMassPaidAllowed()
		{
			global $user;

			return preg_match('#/fourn/facture/list\.php#', $_SERVER['PHP_SELF'])
				&& isModEnabled('supplier_invoice')
				&& getDolGlobalInt('INFRASWORKFLOW_SUPPLIER_INVOICE_MASS_PAID', 0)
				&& $user->hasRight('fournisseur', 'facture', 'creer');
		}

		/**
		*	Add the "Classify paid" entry to the mass action dropdown of the supplier invoice list.
		*
		*	@param	array			$parameters		Hook parameters (contains currentcontext)
		*	@param	CommonObject	$object			The current object
		*	@param	string			$action			Current action
		*	@param	HookManager		$hookmanager	Hook manager
		*	@return	int								0 to continue
		**/
		public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			if (!$this->supplierInvoiceMassPaidAllowed()) {
				return 0;
			}
			$langs->load('bills');
			$this->resprints	= '<option value="presetpaidsupplier" data-html="'.dol_escape_htmltag(img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans('ClassifyPaid')).'">'.img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans('ClassifyPaid').'</option>';
			return 0;
		}

		/**
		*	Display the confirmation dialog before classifying supplier invoices as paid.
		*
		*	@param	array			$parameters		Hook parameters (contains toselect, massaction, currentcontext)
		*	@param	CommonObject	$object			The current object
		*	@param	string			$action			Current action
		*	@param	HookManager		$hookmanager	Hook manager
		*	@return	int								0 to continue
		**/ 
		public function doPreMassActions($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			if (empty($parameters['massaction']) || $parameters['massaction'] != 'presetpaidsupplier') {
				return 0;
			}
			if (!$this->supplierInvoiceMassPaidAllowed()) {
				return 0;
			}
			$langs->loadLangs(array('bills', 'infrasworkflow@infrasworkflow'));
			$toselect	= is_array($parameters['toselect']) ? $parameters['toselect'] : array();
			$form		= new Form($this->db);
			print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('InfraSWorkflowMassSetPaidSupplier'), $langs->trans('InfraSWorkflowMassSetPaidSupplierQuestion', count($toselect)), 'setpaidsupplier', null, 'yes', 0, 200, 500, 1);
			return 0;
		}

		/**
		*	Process the "Classify paid" mass action on the supplier invoice list once confirmed.
		*	Only validated and not-yet-paid supplier invoices are classified paid.
		*
		*	@param	array			$parameters		Hook parameters (contains toselect, massaction, currentcontext)
		*	@param	CommonObject	$object			The current object
		*	@param	string			$action			Current action
		*	@param	HookManager		$hookmanager	Hook manager
		*	@return	int								0 to continue, 1 to replace standard code
		**/
		public function doMassActions($parameters, &$object, &$action, $hookmanager)
		{
			global $user, $langs, $massaction;

			if (!empty($parameters['massaction']) && $parameters['massaction'] == 'confirm_createbills' && $this->massCreateBillsAllowed()) {
				$massaction	= '';
				$action		= '';
				return $this->massCreateBills($parameters);
			}

			if ($action != 'setpaidsupplier' || GETPOST('confirm', 'aZ09') != 'yes') {
				return 0;
			}
			if (!$this->supplierInvoiceMassPaidAllowed()) {
				return 0;
			}
			$langs->loadLangs(array('bills', 'infrasworkflow@infrasworkflow'));
			$toselect	= is_array($parameters['toselect']) ? $parameters['toselect'] : array();
			$nbok		= 0;
			$nbskip		= 0;
			$error		= 0;

			$this->db->begin();
			foreach ($toselect as $toselectid) {
				$invoice	= new FactureFournisseur($this->db);
				$res		= $invoice->fetch((int) $toselectid);
				if ($res <= 0) {
					$error++;
					$this->errors[]	= $invoice->error;
					break;
				}
				// Only validated invoices not already paid can be classified paid
				if ($invoice->statut != FactureFournisseur::STATUS_VALIDATED || $invoice->paye == 1) {
					$nbskip++;
					continue;
				}
				$res	= $invoice->setPaid($user, '', '');
				if ($res < 0) {
					$error++;
					$this->errors[]	= $invoice->error;
					break;
				}
				$nbok++;
			}

			if (!$error) {
				$this->db->commit();
				setEventMessages($langs->trans('InfraSWorkflowMassSetPaidSupplierDone', $nbok), null, 'mesgs');
				if ($nbskip > 0) {
					setEventMessages($langs->trans('InfraSWorkflowMassSetPaidSupplierSkipped', $nbskip), null, 'warnings');
				}
			} else {
				$this->db->rollback();
				setEventMessages($this->error, $this->errors, 'errors');
			}
			$action	= '';
			return 1;
		}

		/**
		*	Mass action to create bills from orders : reimplementation of Dolibarr's native "confirm_createbills"
		*
		*	@param	array			$parameters		Hook parameters (contains toselect, massaction, currentcontext)
		*	@return	int								0 to continue, 1 to replace standard code
		**/
		function massCreateBills($parameters)
		{
			global $user, $langs, $conf;

			$langs->loadLangs(array('orders', 'bills', 'errors'));

			$copyNotes				= getDolGlobalInt('INFRASWORKFLOW_MASS_CREATEBILLS_COPY_NOTES', 0);
			$setAuthor				= getDolGlobalInt('INFRASWORKFLOW_MASS_CREATEBILLS_SET_AUTHOR', 0);
			$dedupRef				= getDolGlobalInt('INFRASWORKFLOW_MASS_CREATEBILLS_DEDUP_REF', 0);
			$orders					= !empty($parameters['toselect']) && is_array($parameters['toselect']) ? $parameters['toselect'] : array();
			$createbills_onebythird	= GETPOSTINT('createbills_onebythird');
			$validate_invoices		= GETPOSTINT('validate_invoices');
			$nb_bills_created		= 0;
			$lastid					= 0;
			$error					= 0;
			$errors					= array();
			$TFact					= array();
			$TFactThird				= array();
			$TFactThirdNbLines		= array();
			$lastref				= '';

			$this->db->begin();
			$nbOrders		= count($orders);
			$currentIndex	= 0;
			foreach ($orders as $id_order) {
				$cmd		= new Commande($this->db);
				if ($cmd->fetch($id_order) <= 0) {
					// Commande introuvable/supprimée entre-temps : on l'ignore silencieusement et on passe à la suivante
					continue;
				}
				$cmd->fetch_thirdparty();
				$objecttmp	= new Facture($this->db);
				// Mode "1 facture par tiers" ET une facture existe déjà pour ce tiers (créée par une commande précédente dans la boucle)
				if (!empty($createbills_onebythird) && !empty($TFactThird[$cmd->socid])) {
					// If option "one bill per third" is set, and an invoice for this thirdparty was already created, we reuse it.
					$currentIndex++;
					$objecttmp	= $TFactThird[$cmd->socid];
				} else {
					// Soit mode "1 facture par commande", soit premier passage pour ce tiers : on crée une nouvelle facture
					$objecttmp->socid				= $cmd->socid;
					$objecttmp->thirdparty			= $cmd->thirdparty;
					$objecttmp->type				= $objecttmp::TYPE_STANDARD;
					$objecttmp->cond_reglement_id	= !empty($cmd->cond_reglement_id) ? $cmd->cond_reglement_id : $cmd->thirdparty->cond_reglement_id;
					$objecttmp->mode_reglement_id	= !empty($cmd->mode_reglement_id) ? $cmd->mode_reglement_id : $cmd->thirdparty->mode_reglement_id;
					$objecttmp->demand_reason_id	= !empty($cmd->demand_reason_id) ? $cmd->demand_reason_id : $cmd->thirdparty->demand_reason_id;
					$objecttmp->fk_project			= $cmd->fk_project;
					$objecttmp->multicurrency_code	= $cmd->multicurrency_code;
					if (empty($createbills_onebythird)) {
						$objecttmp->ref_client		= $cmd->ref_client;
					}

					if (empty($objecttmp->note_public) && getDolGlobalInt('MAXREFONDOC', 10) > 0) {
						$objecttmp->note_public		= $langs->transnoentities('Orders');
					}
					// Copie les notes de la commande vers la facture, seulement si 1 commande = 1 facture
					if ($copyNotes && empty($createbills_onebythird)) {
						if (!empty($cmd->note_public)) {
							$objecttmp->note_public	= dol_concatdesc($objecttmp->note_public, $cmd->note_public);
						}
						if (!empty($cmd->note_private)) {
							$objecttmp->note_private	= dol_concatdesc($objecttmp->note_private, $cmd->note_private);
						}
					}
					// Date de facture choisie dans le formulaire qu'un décalage de fuseau horaire ne fasse basculer la date sur le jour précédent/suivant
					$datefacture	= dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
					if (empty($datefacture)) {
						$datefacture	= dol_now();
					}
					$objecttmp->date			= $datefacture;
					$objecttmp->origin			= 'commande';
					$objecttmp->origin_id		= $id_order;
					// Copie les valeurs des extrafields de la commande vers la facture (suppose les mêmes clés d'extrafields des deux côtés)
					$objecttmp->array_options	= $cmd->array_options;
					if ($setAuthor) {
						// Force l'utilisateur qui lance l'action de masse comme auteur de la facture, au lieu de l'auteur par défaut
						$objecttmp->fk_user_author	= $user->id;
					}
					$res	= $objecttmp->create($user);
					if ($res > 0) {
						$nb_bills_created++;
						$lastref						= $objecttmp->ref;
						$lastid							= $objecttmp->id;
						$TFactThird[$cmd->socid]		= $objecttmp;
						$TFactThirdNbLines[$cmd->socid] = 0; // init à 0 pour numéroter les lignes dans l'ordre d'arrivée des commandes fusionnées
					} else {
						$errors[]	= $cmd->ref.' : '.$langs->trans($objecttmp->errors[0]);
						$error++;
					}
				}
				if ($objecttmp->id > 0) {
					// Crée le lien "objet lié" entre la facture et la commande d'origine (onglet "Objets liés")
					$res	= $objecttmp->add_object_linked($objecttmp->origin, $id_order);
					if ($res == 0) {
						$errors[]	= $cmd->ref.' : '.$langs->trans($objecttmp->errors[0]);
						$error++;
					}
					if (!$error) {
						$lines	= $cmd->lines;
						if (empty($lines) && method_exists($cmd, 'fetch_lines')) {
							// Les lignes ne sont pas toujours chargées par fetch() seul, selon le contexte d'appel
							$cmd->fetch_lines();
							$lines	= $cmd->lines;
						}
						$fk_parent_line	= 0; // id de la ligne "titre/section" parente en cours, pour rattacher ses sous-lignes
						$num			= count($lines);
						$array_options	= array();

						for ($i = 0; $i < $num; $i++) {
							$desc	= ($lines[$i]->desc ? $lines[$i]->desc : '');
							// Si plusieurs commandes sont regroupées dans une seule facture, on ajoute la réf. de la commande devant chaque ligne, sert à identifier l'origine de la ligne dans la facture finale.
							if (!empty($createbills_onebythird)) {
								$desc	= dol_concatdesc($desc, $langs->trans('Order').' '.$cmd->ref.' - '.dol_print_date($cmd->date, 'day'));
							}
							if ($lines[$i]->subprice < 0 && !getDolGlobalString('INVOICE_KEEP_DISCOUNT_LINES_AS_IN_ORIGIN')) {
								// Negative line, we create a discount line
								$discount				= new DiscountAbsolute($this->db);
								$discount->fk_soc		= $objecttmp->socid;
								$discount->socid		= $objecttmp->socid;
								$discount->amount_ht	= abs($lines[$i]->total_ht);
								$discount->amount_tva	= abs($lines[$i]->total_tva);
								$discount->amount_ttc	= abs($lines[$i]->total_ttc);
								$discount->tva_tx		= $lines[$i]->tva_tx;
								$discount->fk_user		= $user->id;
								$discount->description	= $desc;
								$discountid				= $discount->create($user);
								if ($discountid > 0) {
									// La remise est créée comme "disponible" pour le tiers puis immédiatement consommée sur cette facture
									$objecttmp->insert_discount($discountid);
								} else {
									setEventMessages($discount->error, $discount->errors, 'errors');
									$error++;
									break;
								}
							} else {
								// Ligne normale (prix positif)
								$product_type	= ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
								// Date de début : on prend la plus "définitive" disponible, dans l'ordre prévue -> réelle -> explicite
								$date_start		= false;
								if ($lines[$i]->date_debut_prevue) {
									$date_start	= $lines[$i]->date_debut_prevue;
								}
								if ($lines[$i]->date_debut_reel) {
									$date_start	= $lines[$i]->date_debut_reel;
								}
								if ($lines[$i]->date_start) {
									$date_start	= $lines[$i]->date_start;
								}
								$date_end		= false;
								if ($lines[$i]->date_fin_prevue) {
									$date_end	= $lines[$i]->date_fin_prevue;
								}
								if ($lines[$i]->date_fin_reel) {
									$date_end	= $lines[$i]->date_fin_reel;
								}
								if ($lines[$i]->date_end) {
									$date_end	= $lines[$i]->date_end;
								}
								// Reset fk_parent_line for no child products and special product
								if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
									$fk_parent_line = 0;
								}

								// Extrafields
								if (method_exists($lines[$i], 'fetch_optionals')) {
									$lines[$i]->fetch_optionals();
									$array_options	= $lines[$i]->array_options;
								}
								$objecttmp->context['createfromclone']	= 'createfromclone';
								$rang									= ($nbOrders > 1) ? -1 : $lines[$i]->rang;
								if (!empty($createbills_onebythird)) {
									// En mode "1 facture par tiers", numéroter les lignes de façon incrémentale
									$TFactThirdNbLines[$cmd->socid]++;
									$rang	= $TFactThirdNbLines[$cmd->socid];
								}

								$result	= $objecttmp->addline(
									$desc,
									$lines[$i]->subprice,
									$lines[$i]->qty,
									$lines[$i]->tva_tx,
									$lines[$i]->localtax1_tx,
									$lines[$i]->localtax2_tx,
									$lines[$i]->fk_product,
									$lines[$i]->remise_percent,
									$date_start,
									$date_end,
									0,
									$lines[$i]->info_bits,
									$lines[$i]->fk_remise_except,
									'HT',
									0,
									$product_type,
									$rang,
									$lines[$i]->special_code,
									$objecttmp->origin,
									$lines[$i]->rowid,
									$fk_parent_line,
									$lines[$i]->fk_fournprice,
									$lines[$i]->pa_ht,
									$lines[$i]->label,
									$array_options,
									100,
									0,
									$lines[$i]->fk_unit
								);
								if ($result > 0) {
									if (!empty($lines[$i]->extraparams)) {
										$factureLine				= new FactureLigne($this->db);
										$factureLine->id			= $result;
										$factureLine->extraparams	= $lines[$i]->extraparams;
										$factureLine->setExtraParameters();
									}
								} else {
									$error++;
									$errors[]	= $objecttmp->error;
									break;
								}
								// Defined the new fk_parent_line
								if ($result > 0 && $lines[$i]->product_type == 9) {
									$fk_parent_line	= $result;
								}
							}
						}
					}
				}

				if ($currentIndex <= getDolGlobalInt('MAXREFONDOC', 10)) {
					// Ajoute la référence de la commande (et la réf. client si connue) dans la note publique de la facture
					if (!$dedupRef || !empty($createbills_onebythird)) {
						$objecttmp->note_public	= dol_concatdesc($objecttmp->note_public, $langs->transnoentities($cmd->ref).(empty($cmd->ref_client) ? '' : ' ('.$cmd->ref_client.')'));
						$objecttmp->update($user);
					}
				}
				if (!empty($createbills_onebythird) && empty($TFactThird[$cmd->socid])) {
					$TFactThird[$cmd->socid]	= $objecttmp;
				} else {
					$TFact[$objecttmp->id]		= $objecttmp;
				}
			}
			// Construit la génération de document (PDF) pour toutes les factures créées
			$TAllFact	= empty($createbills_onebythird) ? $TFact : $TFactThird;
			if (!$error && $validate_invoices) {
				foreach ($TAllFact as $objecttmp) {
					$result	= $objecttmp->validate($user);
					if ($result <= 0) {
						$error++;
						$errors[]	= $objecttmp->error;
						break;
					}
					$id					= $objecttmp->id;
					$action				= 'builddoc';
					$donotredirect		= 1;
					$upload_dir			= $conf->facture->dir_output;
					$permissiontoadd	= $user->hasRight('facture', 'creer');
					$object				= $objecttmp;
					include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
				}
			}

			if (!$error) {
				$this->db->commit();
				if ($nb_bills_created == 1) {
					if (getDolGlobalInt('MAIN_MASSACTION_CREATEBILLS_REDIRECT_IF_ONE') == 1) {
						header('Location: '.DOL_URL_ROOT.'/compta/facture/card.php?id='.urlencode((string) $lastid));
						exit;
					}
					$texttoshow	= $langs->trans('BillXCreated', '{s1}');
					$texttoshow	= str_replace('{s1}', '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.urlencode((string) $lastid).'">'.$lastref.'</a>', $texttoshow);
					setEventMessages($texttoshow, null, 'mesgs');
				} else {
					if (getDolGlobalInt('MAIN_MASSACTION_CREATEBILLS_REDIRECT_IF_MANY') == 1) {
						header('Location: '.DOL_URL_ROOT.'/compta/facture/list.php?mainmenu=billing&leftmenu=customers_bills');
						exit;
					}
					setEventMessages($langs->trans('BillCreated', $nb_bills_created), null, 'mesgs');
				}
				// Return to the page the mass action was submitted from (its filters are posted, not carried in the URL).
				$refererTarget	= '';
				if (!empty($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_HOST'])) {
					$refererParts		= parse_url($_SERVER['HTTP_REFERER']);
					if (!empty($refererParts['host']) && $refererParts['host'] === $_SERVER['HTTP_HOST']) {
						$refererTarget	= dol_sanitizeUrl((isset($refererParts['path']) ? $refererParts['path'] : '').(empty($refererParts['query']) ? '' : '?'.$refererParts['query']), 0);
					}
				}
				header('Location: '.(!empty($refererTarget) ? $refererTarget : DOL_URL_ROOT.'/commande/list.php'));
				exit;
			} else {
				// En cas d'erreur, on annule toute la transaction : aucune facture partiellement créée ne reste en base
				$this->db->rollback();
				if (!empty($errors)) {
					setEventMessages(null, $errors, 'errors');
				} else {
					setEventMessages('Error', null, 'errors');
				}
			}
			return 1;
		}
	}
