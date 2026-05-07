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
dol_include_once('/uptosign/class/uptosignconfig.class.php');
dol_include_once('/uptosign/class/uptosign.class.php');
dol_include_once('/uptosign/lib/uptosign.lib.php');

/**
 * \file    uptosign/class/actions_uptosign.class.php
 * \ingroup uptosign
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsUptoSign
 */
class ActionsUptoSign
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * list of context handled by that module
	 *
	 * @var array
	 */
	public $array_of_handled_context;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->array_of_handled_context = ['propalcard','interventioncard','ordercard', 'contractcard', 'expeditioncard', 'invoicecard', 'projectcard','uptosignnewonlinesign','uptosigncard', 'usercard','contactcard', 'ordersuppliercard', 'infrassalariescontractscard'];
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		dol_syslog("uptosign::doaction parameters=" . json_encode($parameters) . ", action=" . $action);

		$currentcontext = $parameters['currentcontext'];
		if (!in_array($currentcontext, $this->array_of_handled_context)) {
			//nothing to do here
			return 0;
		}

		$error = 0; // Error counter
		$errors = array(); // error mesgs
		$dir = $res = null;
		$uptoSign = new UptoSign($this->db);
		$uptosignstatic = new UptoSign($this->db);

		if (empty($object)) {
			dol_syslog("uptosign::doaction object is empty, early return", LOG_WARNING);
			return 0;
		}

		if (empty($object->thirdparty)) {
			if (is_callable(array($object, 'fetch_thirdparty'))) {
				$result = $object->fetch_thirdparty();
				if ($result < 0) {
					dol_syslog("uptosign::doaction can't fetch thirdparty");
				}
			} else {
				dol_syslog("uptosign::doaction thirdparty is not callable on that object");
			}
		}

		//cas particulier pour la fiche contact d'un utilisateur: on ajoute un bouton pour lui donner tous les droits de signer tous les docs possibles
		if ($currentcontext == 'contactcard' && $action == 'uptosignAllDocsToContact') {
			dol_syslog("uptosign doActions uptosignAllDocsToContact", LOG_DEBUG);
			$res = $uptoSign->giveAllRolesToContact($object);
			if ($res > 0) {
				dol_syslog("uptosign: UptoSignAssignAllSignRoleToContact hook ok");
				setEventMessages($langs->trans('UptoSignAssignAllSignRoleToContact'), [], 'mesgs');
			} else {
				dol_syslog("uptosign: UptoSignAssignAllSignRoleToContact hook error");
				setEventMessages($langs->trans('UptoSignAssignAllSignRoleToContactError'), [], 'warning');
			}
			return $res;
		}

		// print "<p>object type is: " . json_encode($object->element)."</p>"; print json_encode($parameters); print json_encode($object); print "<p>action: " . $action."</p>"; exit;
		// dol_syslog("uptosign doAction param = " . json_encode($parameters) . ", action = $action", LOG_DEBUG);
		// dol_syslog("uptosign doAction object = " . json_encode($object), LOG_DEBUG);

		//Creation d'un objet -> si champ digitalsign & option globale alors valeur par defaut
		if (utsbackports_getDolGlobalString('UPTOSIGN_FORCE_AS_DEFAULT_SIGN_SYSTEM', '') != '' && $action == "create") {
			if (in_array($object->element, uptosign_list_of_elements_with_extrafield())) {
				if (!empty($object->id) && isset($object->array_options) && empty($object->array_options['options_digitalsign'])) {
					$object->array_options['options_digitalsign'] = 'uptosign';
					$res = $object->updateExtraField('digitalsign');
					dol_syslog("uptosign create object update digitalsign empty to uptosign", LOG_DEBUG);
				}
				return 0;
			}
		}

		//uuid possible >> prioritaire
		if (isset($parameters['uuid'])) {
			$resUTS = $uptosignstatic->fetchWhereUuidSign($parameters['uuid']);
			if ($resUTS <= 0) {
				dol_syslog("uptosign doActions can't find document : " . json_encode($uptoSign), LOG_ERR);
			} else {
				dol_syslog("uptosign resUTS=" . json_encode($uptosignstatic));
				if($uptosignstatic->object_type == $object->element && empty($object->id)) {
					$object->fetch($uptosignstatic->fk_object);
				}
			}
		}

		// we need object id and element ...
		if (empty($object->id) || empty($object->element)) {
			dol_syslog("uptosign doAction $action, error object id ($object->id) or element ($object->element) is empty (a) !");
			dol_syslog("uptosign doAction parameters=" . json_encode($parameters));
			return 0;
		}

		if (empty($uptosignstatic->id)) {
			dol_syslog("uptosign doActions action=$action, document id is empty, request from objectid=" . $object->id . ", element=" . $object->element);
			$result = $uptosignstatic->fetch(null, null, $object->id, $object->element);
		}
		$signOrSeal = "";
		dol_syslog("uptosign doActions action=$action, document id is : " . json_encode($uptosignstatic->id));

		switch ($action) {
			case "presend": //Avant de lancer un mail : vérification qu'on a des contacts liés
				$listContacts = $uptoSign->getListContacts($object);
				if (!is_array($listContacts)) {
					$errormessage = "";
					foreach ($uptoSign->errors as $errmsg) {
						$errormessage .= $langs->trans($errmsg);
					}
					setEventMessages($errormessage, [], 'warnings');
					return -1;
				} elseif (count($listContacts) == 0 && empty($listContacts['noSign'])) {
					setEventMessages($langs->trans('UptoSignContactMissing'), [], 'warnings');
					return -1;
				}
				break;
			case "confirm_uptosign":
				$signOrSeal = "sign";
				// no break
			case "confirm_uptoseal":
				if ($signOrSeal == "") {
					$signOrSeal = "seal";
				}

				$object_type = uptosign_unify_object_type($object->element);
				$api_name = uptosign_unify_api_name($signOrSeal);
				$result = $uptoSign->fetchByObject((int) $object->id, $object_type, array('api_name' => $api_name));
				//quid d'un vieux process ? lancé il y a x heures / minutes ?
				if ($result) {
					setEventMessages($langs->trans('UptoSignProcessAlreadyStarted'), [], 'warnings');
					// return -1;
				}

				//erics todo				confirm_uptoseal
				//verif si filename existe
				$fullFileName = uptosignFindFileToUse($object, $parameters['last_main_doc'] ?? '');
				dol_syslog("uptosign doActions action=$action, Choosed file is filename=$fullFileName");

				if ($currentcontext == 'uptosignnewonlinesign') {
					dol_syslog("uptosign doActions current context is uptosignnewonlinesign, customer self clic on online sign...");
					//Sur la page publique le client clique lui meme sur le bouton "signer"
					if ($parameters['process'] == "now") {
						//il faut un user -> celui qui a validé le devis
						$user = $uptoSign->findUserToUse($user, $object);
						$object->redirectSign = true;
						dol_syslog("uptosign start signInit with redirectionMode enabled");
					}
					if ($parameters['contactToSignID'] != "") {
						dol_syslog("uptosign start signInit with contactToSignID set to " . $parameters['contactToSignID']);
						//On a une adresse mail comme contact signataire il faut l'utiliser !
						$object->contactToSignID = $parameters['contactToSignID'];
					}
				}

				if (!empty($fullFileName)) {
					if ($action == 'confirm_uptosign') {
						$res = $uptoSign->signInit($user, $object, $fullFileName);
					}
					if ($action == 'confirm_uptoseal') {
						$res = $uptoSign->sealInit($user, $object, $fullFileName);
					}

					if ($res == 0) {
						if ($action == 'confirm_uptoseal') {
							setEventMessages("SealRequestSuccessful", [], 'mesgs');
						} else {
							setEventMessages("SignRequestSuccessful", [], 'mesgs');
						}
					} else {
						array_push($errors, 'Init Process Error, res is ' . $res . ' and action is ' . $action  . " <br /> " . implode(',', $uptoSign->errors));
						$error++;
					}
				}
				break;
			case "confirm_uptosignfetch":
				// print json_encode($this); exit;
				$signOrSeal = "uptosign";
				// no break
			case "confirm_uptosealfetch":
				if ($signOrSeal == "") {
					dol_syslog("uptosign : confirm_uptosealfetch");
					$signOrSeal = "uptoseal";
				} else {
					dol_syslog("uptosign : confirm_uptosignfetch");
				}
				//si l'uuid est spécifié on est alors sur un objet uptosign specifique
				if (isset($parameters['uuid'])) {
					$res = $uptoSign->signFetch($user, $uptosignstatic, $signOrSeal);
				} else {
					$res = $uptoSign->signFetch($user, $object, $signOrSeal);
				}
				if ($res < 0) {
					dol_syslog("uptosign doAction signFetch Error", LOG_DEBUG);
					$errors = $uptoSign->errors;
					array_push($errors, 'Fetch Process Error res=' . $res);
					$error++;
				} else {
					setEventMessages($langs->trans('UptoSignDocumentFetched'), [], 'mesgs');
				}
				break;
			case "confirm_uptosignfetchproof":
				// dol_syslog("uptosign : confirm_uptosignfetchproof");
				$res = $uptoSign->signFetchProof($user, $object);
				if ($res < 0) {
					dol_syslog("uptosign doAction signFetchProof Error", LOG_DEBUG);
					$errors = $uptoSign->errors;
					array_push($errors, 'FetchProof Process Error');
					$error++;
				} else {
					setEventMessages($langs->trans('UptoSignDocumentFetched'), [], 'mesgs');
				}
				break;
			case "checkHistory":
				$mode = 'synchistory';
				// no break
			case "uptosignsync":
				$signOrSeal = "uptosign";
				// no break
			case "uptosealsync":
				if ($signOrSeal == "") {
					$signOrSeal = $parameters['signOrSeal'] ?? ''; // InfraS change
				}
				if (!isset($mode) || $mode == "") {
					$mode = 'sync';
				}

				$object_type = uptosign_unify_object_type($object->element);
				$api_name = uptosign_unify_api_name($signOrSeal);
				$res = $uptoSign->signFetch($user, $object, $signOrSeal);
				if ($res) {
					$signStatus = $uptoSign->signInfo($user, $object, $mode);
					// print "<p>update $object->ref, set sign status to $signStatus</p>";

					// for updating status in view
					// $object->fetch($object->id);
					if ($signStatus == UptoSign::STATUS_WAITING) {
						setEventMessages('UptoSign: ' . $langs->trans('WaitingUptoSign'), [], 'mesgs');
					} elseif ($signStatus == UptoSign::STATUS_SIGNED) {
						setEventMessages('UptoSign: ' . $langs->trans('SignedUptoSign'), [], 'mesgs');
						$this->resprints = '<td>' . $langs->trans('SignedUptoSign') . '</td>';
						if ($currentcontext == 'uptosigncard') {
							header("Location: " . $_SERVER["PHP_SELF"] . '?id=' . GETPOSTINT('id'));
							exit;
						}
						header("Location: " . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_uptosignfetch');
						exit;
					} elseif ($signStatus == UptoSign::STATUS_CANCELED) {
						setEventMessages('UptoSign: ' . $langs->trans('CanceledUptoSign'), [], 'mesgs');
						$this->resprints = '<td>' . $langs->trans('CanceledUptoSign') . '</td>';
					} elseif ($signStatus == UptoSign::STATUS_ERROR) {
						setEventMessages('UptoSign: ' . $langs->trans('ErrorUptoSign'), [], 'mesgs');
						$this->resprints = '<td>' . $langs->trans('ErrorUptoSign') . '</td>';
					} elseif ($signStatus == UptoSign::STATUS_SEALED) {
						setEventMessages('UptoSign: ' . $langs->trans('UptoSignSealedIsAvailable'), [], 'mesgs');
						$this->resprints = '<td>' . $langs->trans('UptoSignSealedIsAvailable') . '</td>';
					} elseif ($signStatus == UptoSign::STATUS_DRAFT) {
						setEventMessages('UptoSign: ' . $langs->trans('UptoSignStatusDraft'), [], 'mesgs');
						$this->resprints = '<td>' . $langs->trans('UptoSignStatusDraft') . '</td>';
					} else {
						setEventMessages('UptoSign: générique', [], 'mesgs');
					}
				}
				break;
			case "modif":
			case "modify":
			case "confirm_modify":
			case "confirm_modif":
			case "confirm_reopen":
				$result = $uptoSign->fetch(null, null, $object->id, $object->element);
				if ($result > 0) {
					$signStatus = $uptoSign->status;
					if (($signStatus == UptoSign::STATUS_SIGNED || $signStatus == UptoSign::STATUS_FILE_FETCHED) && !empty($uptoSign->fk_contact_sign)) {
						dol_syslog("uptosign doAction confirm_reopen Error", LOG_DEBUG);
						$errors = ["UptoSignSignedNoModify"];
						$error++;
					} else {
						// $uptoSign->delete($user);
					}
				}
				break;
			case "confirm_delete":
				$result = $uptoSign->fetch(null, null, $object->id, $object->element);
				if ($result > 0) {
					$signStatus = $uptoSign->status;
					if ($signStatus == UptoSign::STATUS_SIGNED && !empty($uptoSign->fk_contact_sign)) {
						dol_syslog("uptosign doAction confirm_delete Error", LOG_DEBUG);
						$errors = ["UptoSignSignedNoDelete"];
						$error++;
					} else {
						$uptoSign->delete($user);
					}
				}
				break;
			case "builddoc":
				//Si le document est signé / scellé il faut "capturer" le clic sur le bouton de création du PDF
				$object_type = uptosign_unify_object_type(uptosignModel($object));
				$result = $uptoSign->fetchByObject((int) $object->id, $object_type, array('sign_status' => 'done'));
				if (is_array($result) && count($result) > 0) {
					$this->formConfirm($parameters, $object, $action, $hookmanager);
					$this->results = array('myreturn' => 999);
					$this->resprints = 'A text to show';
					return 1;
				}
				break;
			case "confirm_builddoc":
				//change uptosign entries -> override
				$object_type = uptosign_unify_object_type(uptosignModel($object));
				$result = $uptoSign->fetchByObject((int) $object->id, $object_type, array('sign_status' => 'done'));
				if (is_array($result) && count($result) > 0) {
					foreach ($result as $uts) {
						// $uts->delete($user);
						// $uts->status = UptoSign::STATUS_FILE_REWRITE;
						// $uts->save($user);
					}
				}
				$action = "builddoc";
				break;
			default:
		}

		if ($object->element == 'propal' && $object->status == Propal::STATUS_VALIDATED) {
			// make sure uptosign status and propal status are aligned
			$result = $uptoSign->fetch(null, null, $object->id, $object->element);
			if ($result > 0) {
				$signStatus = $uptoSign->status;
				//ne pas cloturer le devis si c'est juste un scellement
				if ($signStatus >= UptoSign::STATUS_SIGNED && $uptoSign->api_name == "uptosign") {
					// en V14 signature($user, $statut, $note = '', $notrigger = 0) est renommé closeProposal($user, $status, $note = '', $notrigger = 0)
					// en V14 cloture($user, $status, $note = "", $notrigger = 0) est renommé classifyBilled(User $user, $notrigger = 0, $note = '')
					// attention aux paramètres

					//last check to avoid double close / double trigger
					$propal = new Propal($this->db);
					$res = $propal->fetch($object->id);
					if ($res > 0 && $propal->status == Propal::STATUS_VALIDATED) {
						// surtout ne pas re-générer le PDF !
						$conf->global->MAIN_DISABLE_PDF_AUTOUPDATE = 1;
						if (((int) DOL_VERSION) < 14) {
							dol_syslog("uptosign doAction call propal cloture (dolibarr < 14)");
							$object->cloture($user, Propal::STATUS_SIGNED, $langs->trans('SignedByUptoSign'));
						} else {
							dol_syslog("uptosign doAction call propal closeProposal (dolibarr > 14)");
							$object->closeProposal($user, Propal::STATUS_SIGNED, $langs->trans('SignedByUptoSign'));
						}
					}
				}
			}
		}

		if ($object->element == 'commande' && ($object->statut == Commande::STATUS_VALIDATED || $object->statut == Commande::STATUS_SHIPMENTONPROCESS)) {
			// make sure uptosign status and order status are aligned
			$result = $uptoSign->fetch(null, null, $object->id, $object->element);
			if ($result > 0) {
				$signStatus = $uptoSign->status;
				dol_syslog("uptosign doAction object is commande, fetch result is > 0, status is $signStatus");
				//if ($signStatus == UptoSign::STATUS_CANCELED || $signStatus == UptoSign::STATUS_ERROR) {
				if ($signStatus == UptoSign::STATUS_CANCELED) {
					if (((int) DOL_VERSION) >= 23) {
						$object->cancel($user);
					} else {
						$object->cancel();
					}
				} elseif ($signStatus >= UptoSign::STATUS_SIGNED) {
					//TODO pourquoi ?
					if(getDolGlobalString('UPTOSIGN_WORKFLOW_AUTO_CLOSE_ORDER')) {
						$object->cloture($user);
					}
				}
			} else {
				dol_syslog("uptosign doAction object is commande there is no process in progress, continue 2");
			}
		}


		if (!$error) {
			dol_syslog("uptosign doAction end", LOG_DEBUG);
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			dol_syslog("uptosign doAction end Error", LOG_DEBUG);
			$this->errors = $errors;
			return -1;
		}
	}


	/**
	 * Overloading the formConfirm function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs;

		// print(json_encode($object));exit;
		$ret = 0;
		$error = 0; // Error counter
		$errors = array();
		$formconfirm = '';
		$form = new Form($this->db);
		$uptoSign = new UptoSign($this->db);

		if ($action == 'uptosign') {
			$formquestion = [
				[
					'type' => 'hidden',
					'name' => 'redirectionMode',
					'value' => "false",
					'default' => "false"
				]
			];
			$height = 210;

			$formconfirm = $form->formconfirm(
				$_SERVER["PHP_SELF"] . '?id=' . $object->id,
				$langs->trans('UptoSign'),
				$langs->trans('ConfirmUptoSign', $object->ref),
				'confirm_uptosign',
				$formquestion,
				0,
				1,
				$height
			);
		} elseif ($action == 'uptoseal') {
			$filearray = uptosignListOfFilesLinkedTo($object);
			if (count($filearray) == 0) {
				//fix #35: aucun fichier à sceller / signer -> message
				dol_syslog("uptosign: seal error there is no file linked to that object", LOG_ERR);
				setEventMessages($langs->trans("UptoSignNoPdfFilesAssociated"), [], 'warnings');
				return -1;
			}

			$height = 210;
			$formquestion = array(
				array(
					'name' => 'selectFilename',
					'label' => $langs->trans('UptoSignChooseFilePopup'),
					'type' => 'other',
					'value' => $form->selectarray('selectFilename', $filearray, 'selectFilename', 0, 0, 0, '', 0, 0, 0, '', '', 0, '', 0, 0)
				)
			);

			$formconfirm = $form->formconfirm(
				$_SERVER["PHP_SELF"] . '?id=' . $object->id,
				$langs->trans('UptoSeal'),
				$langs->trans('ConfirmUptoSeal', $object->ref),
				'confirm_uptoseal',
				$formquestion,
				0,
				1,
				$height
			);
		} elseif ($action == 'uptosignfetch') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('UptoSign'), $langs->trans('ConfirmUptoSignFetch', $object->ref), 'confirm_uptosignfetch', '', 0, 1);
		} elseif ($action == 'uptosealfetch') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('UptoSign'), $langs->trans('ConfirmUptoSignFetch', $object->ref), 'confirm_uptosealfetch', '', 0, 1);
		} elseif ($action == 'builddoc') {
			$object_type = uptosign_unify_object_type(uptosignModel($object));
			$result = $uptoSign->fetchByObject((int) $object->id, $object_type, array('sign_status' => 'done'));
			if (is_array($result) && count($result) > 0) {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('UptoSign'), $langs->trans('UptoSignConfirmRebuildPDF'), 'confirm_builddoc', '', 0, 1);
				$ret = 1;
			}
		}

		if (! $error) {
			$this->resprints = $formconfirm;
			return $ret;                                    // or return 1 to replace standard code
		} else {
			$this->errors = $errors;
			return -1;
		}
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		if (empty($object->id)) {
			return -1;
		}
		$currentcontext = $parameters['currentcontext'];


		//cas particulier pour la fiche contact d'un utilisateur: on ajoute un bouton pour lui donner tous les droits de signer tous les docs possibles
		if ($currentcontext == 'contactcard') {
			dol_syslog("uptosign addMoreActionsButtons param = " . json_encode($parameters) . ", action = $action", LOG_DEBUG);
			print '<div class="inline-block divButAction"><a class="butAction classfortooltip" title="' . $langs->trans('UptoSignAddAllDocToSignTooltip') . '" href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?id=' . $object->id . '&action=uptosignAllDocsToContact"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignAddAllDocToSign') . '</a></div>';
		}

		if (!in_array($currentcontext, $this->array_of_handled_context)) {
			// dol_syslog("uptosign bye bye addMoreActionsButtons param = " . json_encode($parameters) . ", action = $action", LOG_DEBUG);
			//nothing to do here
			return 0;
		}
		dol_syslog("uptosign addMoreActionsButtons param = " . json_encode($parameters) . ", action = $action", LOG_DEBUG);

		$objectExtraFieldUptoSignEnabled = false;
		if (isset($object->array_options) && isset($object->array_options['options_digitalsign']) && ($object->array_options['options_digitalsign'] == 'uptosign')) {
			$objectExtraFieldUptoSignEnabled = true;
		}

		$config = new UptoSignConfig($this->db);
		$model_pdf = uptosignModel($object);
		// print json_encode($currentcontext);exit;
		//print json_encode($object);exit;
		$uptoSign = new UptoSign($this->db);
		if ($currentcontext == 'propalcard' && ! empty($config->fetchListId($model_pdf, $object->element))) {
			$active = true;
			$minStatus = Propal::STATUS_VALIDATED;
			$maxStatus = Propal::STATUS_VALIDATED;
		} elseif ($currentcontext == 'uptosigncard') {
			$active = true;
			$minStatus = UptoSign::STATUS_ERROR;
			$maxStatus = UptoSign::STATUS_FILE_FETCHED;
		} elseif ($currentcontext == 'ordercard' && ! empty($config->fetchListId($model_pdf, $object->element))) {
			$active = true;
			$minStatus = Commande::STATUS_VALIDATED;
			$maxStatus = Commande::STATUS_SHIPMENTONPROCESS;
		} elseif ($currentcontext == 'interventioncard' && ! empty($config->fetchListId($model_pdf, $object->element))) {
			$active = true;
			$minStatus = Fichinter::STATUS_VALIDATED;
			$maxStatus = Fichinter::STATUS_VALIDATED;
		} elseif ($currentcontext == 'contractcard') { /* && ! empty($config->fetchListId($model_pdf, $object->element))) */
			$active = true;
			if (((int) DOL_VERSION) < 11) {
				dol_syslog("uptosign, setStatusCommon is available on dolibarr > 10.0, let use old setStatut...", LOG_WARNING);
				$minStatus = 1;
				$maxStatus = 1;
			} else {
				/** @phpstan-ignore-next-line */
				$minStatus = Contrat::STATUS_VALIDATED;
				/** @phpstan-ignore-next-line */
				$maxStatus = Contrat::STATUS_VALIDATED;
			}
		} elseif ($currentcontext == 'expeditioncard' && ! empty($config->fetchListId($model_pdf, $object->element))) {
			$active = true;
			$minStatus = Expedition::STATUS_VALIDATED;
			$maxStatus = Expedition::STATUS_VALIDATED;
		} elseif ($currentcontext == 'invoicecard' && ! empty($config->fetchListId($model_pdf, $object->element))) {
			$active = true;
			$minStatus = Facture::STATUS_VALIDATED;
			$maxStatus = Facture::STATUS_CLOSED;
		} elseif ($currentcontext == 'projectcard' && ! empty($config->fetchListId($model_pdf, $object->element))) {
			$active = true;
			$minStatus = Project::STATUS_VALIDATED;
			$maxStatus = Project::STATUS_CLOSED;
		} elseif ($parameters['uptosigncustomcard'] == true && ! empty($config->fetchListId($model_pdf, $object->element))) {
			dol_include_once($parameters['include_class_file']);
			$class = $parameters['class_name'];
			$active = true;
			$minStatus = $parameters['min_status'];
			$maxStatus = $parameters['max_status'];
		} elseif ($currentcontext == 'ordersuppliercard' && ! empty($config->fetchListId($model_pdf, $object->element))) {
			$active = true;
			$minStatus = CommandeFournisseur::STATUS_VALIDATED;
			$maxStatus = CommandeFournisseur::STATUS_ACCEPTED;
		} elseif ($currentcontext == 'infrassalariescontractscard') {
			$active = true;
			/** @phpstan-ignore-next-line */
			$minStatus = Infrassalariescontracts::STATUS_VALIDATED;
			/** @phpstan-ignore-next-line */
			$maxStatus = Infrassalariescontracts::STATUS_VALIDATED;
		} else {
			$active = false;
			$minStatus = -100;
			// $maxStatus = 100;
		}

		$status = $object->statut ?? $object->status;
		dol_syslog("uptosign addMoreActionsButtons active = $active, minstatut=$minStatus et status = $status", LOG_DEBUG);

		// print "<p>Object=" . json_encode($object) . "</p>";
		if ($active && $status >= $minStatus) {
			//cas particlier le uptosign_card
			if ($currentcontext == 'uptosigncard') {
				$result = $uptoSign->fetch($object->id, $object->ref);
			} else {
				$result = $uptoSign->fetch(null, null, $object->id, $object->element);
			}

			// print "<p>context $currentcontext : result=" . json_encode($uptoSign) . "</p>";
			// dol_syslog("uptosign addMoreActionsButtons result = " . json_encode($result), LOG_DEBUG);
			//Processus déjà lancé
			if ($result > 0) {
				$signStatus = $uptoSign->status;
				$signOrSeal = $uptoSign->api_name;

				//Cas particulier des documents qui peuvent être scellés puis ensuite signés
				if ($signOrSeal == "uptoseal" && in_array(uptosign_unify_object_type($uptoSign->object_type), ['propal', 'contrat', 'contract'])) {
					//Verifier s'il n'y a pas une procédure de signature en cours ...
					print $this->_availableButtonSignSeal($object, $objectExtraFieldUptoSignEnabled, null, false);
				}

				// print "<p>UpToSign context : object=" . json_encode($uptoSign) . "</p>";

				// print "<p>UpToSign context : min=$minStatus, max=$maxStatus et status=$status ou " . json_encode($signStatus) . "</p>";
				// && $status <= $maxStatus
				$phpself = dol_escape_htmltag($_SERVER["PHP_SELF"]);
				if ($currentcontext == "uptosigncard") {
					print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignSync') . '</a></div>';
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $phpself . '?id=' . $object->id . '&action=' . $signOrSeal . 'fetch"><i class=\"fas fa-signature\"></i>' . $langs->trans($signOrSeal . 'Fetch') . '</a></div>';
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $phpself . '?id=' . $object->id . '&action=' . $signOrSeal . 'sync"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignForceRefresh') . '</a></div>';
				} else {
					// print "<p>UptoSign : debug pour signStatus == $signStatus</p>";
					if ($signStatus == UptoSign::STATUS_WAITING || $signStatus == UptoSign::STATUS_DRAFT) {
						if ($user->hasRight('uptosign', 'read')) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $phpself . '?id=' . $object->id . '&action=' . $signOrSeal . 'sync"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignSync') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="' . $langs->trans('UptoSignYouDoNotHaveRightsToDo') . '" href="#"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignSync') . '</a></div>';
						}
					} elseif ($signStatus == UptoSign::STATUS_SIGNED || $signStatus == UptoSign::STATUS_SEALED) {
						//|| $signStatus == UptoSign::STATUS_FILE_FETCHED -> si déjà téléchargé on n'affiche pas le bouton
						if ($user->hasRight('uptosign', 'read')) {
							if ($currentcontext == 'contractcard') {
								print '<div class="inline-block divButAction"><a class="butAction" href="' . $phpself . '?id=' . $object->id . '&action=confirm_' . $signOrSeal . 'fetch"><i class=\"fas fa-signature\"></i>' . $langs->trans($signOrSeal . 'Fetch') . '</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butAction" href="' . $phpself . '?id=' . $object->id . '&action=' . $signOrSeal . 'fetch"><i class=\"fas fa-signature\"></i>' . $langs->trans($signOrSeal . 'Fetch') . '</a></div>';
							}
						}
					} elseif ($signStatus == UptoSign::STATUS_CANCELED) {
						if ($objectExtraFieldUptoSignEnabled) {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="' . $langs->trans('UptoSignProcedureCancelled') . '"  href="#"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignBtnSign') . '</a></div>';
						}
					} elseif ($signStatus == UptoSign::STATUS_ERROR) {
						print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">' . $langs->trans('UptoSignSyncError') . '</a></div>';
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $phpself . '?id=' . $object->id . '&action=uptosignsync"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignForceRefresh') . '</a></div>';
					} elseif ($signStatus == UptoSign::STATUS_FILE_FETCHED) {
						//verifications si le fichier local n'est "pas le fichier signé/scellé"
						if (!$uptoSign->checkFile()) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $phpself . '?id=' . $object->id . '&action=uptosignsync"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignForceRefresh') . '</a></div>';
						}
					}
				}
			} else {
				if ($status <= $maxStatus) {
					//Creation
					if ($user->hasRight('uptosign', 'create')) {
						print $this->_availableButtonSignSeal($object, $objectExtraFieldUptoSignEnabled);
					} else {
						if ($objectExtraFieldUptoSignEnabled) {
							print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="' . $langs->trans('UptoSignYouDoNotHaveRightsToDo') . '" href="#"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignBtnSign') . '</a></div>';
						}
					}
				}
			}
		}
		if (! $error) {
			// or return 1 to replace standard code
			return 0;
		} else {
			array_push($this->errors, 'Error message 002');
			return -1;
		}
	}

	/**
	 * code factoring to display buttons
	 *
	 * @param   CommonObject $object         $object description
	 * @param   bool $objectExtraFieldUptoSignEnabled  [$force_signbtn description]
	 * @param   bool $force_signbtn  [$force_signbtn description]
	 * @param   bool $force_sealbtn  [$force_sealbtn description]
	 *
	 * @return  string  html code
	 */
	private function _availableButtonSignSeal($object, $objectExtraFieldUptoSignEnabled, $force_signbtn = null, $force_sealbtn = null)
	{
		global $langs;
		$retour = "";
		$typeOfObject = uptosign_unify_object_type($object->element);
		$model = uptosign_unify_object_name(uptosignModel($object));
		// print "<p>********** type = $typeOfObject | model = $model</p>";
		$uptoSignConfig = new UptoSignConfig($this->db);
		// print "<p>Modèle de document : $model</p>";

		$signbtn = $sealbtn = false;
		$signbtnMessage = $sealbtnMessage = "";

		//Par defaut pas de configuration
		$signbtnMessage .= $langs->trans('UptoSignErrorThereIsNoConfig', $model . ' (' . $typeOfObject . ')');
		$sealbtnMessage .= $langs->trans('UptoSignErrorThereIsNoConfig', $model . ' (' . $typeOfObject . ')');


		//modele odt -- mots clés magiques obligatoires :)
		if (substr($model, -3) == "odt") {
			$signbtn = true;
			$sealbtn = true;
		} else {
			$configIds = $uptoSignConfig->fetchListId($model, $typeOfObject);
			if (is_array($configIds)) {
				// print "<p>" . json_encode($configIds) . "</p>";
				foreach ($configIds as $cid) {
					$res = $uptoSignConfig->fetch($cid);
					if ($res) {
						if ($uptoSignConfig->sign_or_seal == "sign") {
							// print "<p>" . json_encode($object) . "</p>";
							// print "<p>" . json_encode($uptoSignConfig) . "</p>";
							if ($uptoSignConfig->label == "CustomerSign") {
								$contacts = new ArrayObject();
								$uptoSign = new UptoSign($this->db);
								$uptoSign->whoCanSign($object, 'internal', "", $contacts);
								$uptoSign->whoCanSign($object, 'internal', "CustomerSign", $contacts);
								$uptoSign->whoCanSign($object, 'external', "CustomerSign", $contacts);
								if (count($contacts) > 1) {
									$signbtn = true;
								} else {
									$signbtn = false;
								}
							}
						}
						if ($uptoSignConfig->sign_or_seal == "seal") {
							//Verifier si le document n'a pas déjà été scellé
							$sealbtn = true;
							$sealbtnMessage = '';
						}
					}
				}
			}
		}

		//Si la fiche en cours est déjà sur uptosign
		if ($object->element == 'uptosign') {
			//cas particulier du dossier de preuves
			if ($object->hash_file != '') {
				$uptoSign = $object;
			}
		} else {
			//une procedure est déjà en cours ?
			$uptoSign = new UptoSign($this->db);
			$result = $uptoSign->fetch(null, null, $object->id);
		}

		// print "<p>".json_encode($uptoSign)."</p>";

		if (isset($uptoSign) && is_array($uptoSign)) {
			foreach ($uptoSign as $uts) {
				if ($uts->api_name == 'uptoseal') {
					$sealbtnMessage .= $langs->trans("UptoSignProcessing") . $uts->getLibStatut();
				} else {
					$signbtnMessage .= $langs->trans("UptoSignProcessing") . $uts->getLibStatut();
				}
			}
		} else {
			if (isset($uptoSign->status)) {
				if ($uptoSign->api_name == 'uptoseal') {
					$sealbtnMessage .= $langs->trans("UptoSignProcessing") . $uptoSign->getLibStatut();
				} else {
					$signbtnMessage .= $langs->trans("UptoSignProcessing") . $uptoSign->getLibStatut();
				}
			}
		}
		// print json_encode($signbtnMessage);

		if (null !== $force_sealbtn) {
			$sealbtn = $force_sealbtn;
		}
		if (null !== $force_signbtn) {
			$signbtn = $force_signbtn;
		}


		// $contactCode = $typeContacts[$uptoSignConfig->fk_c_type_contact];
		// $contactIds = $object->getIdContact('external', $contactCode['code']);
		// $userIds = $object->getIdContact('internal', $contactCode['code']);

		//Si pas de btn alors affichage en mode disabled
		if ($signbtn) {
			if ($objectExtraFieldUptoSignEnabled) {
				$retour .= '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath("/custom/uptosign/uptosign_tab.php", 1) . '?id=' . $object->id . '&objectType=' . $typeOfObject . '&action=presign"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignBtnSign') . '</a></div>';
				//$retour .= '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=uptosign"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignBtnSign') . '</a></div>';
			}
		} else {
			if (!in_array($object->element, ['facture', 'invoice', 'supplier_invoice'])) {
				if ($objectExtraFieldUptoSignEnabled) {
					$retour .= '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="' . $signbtnMessage . '" href="#"><i class=\"fas fa-signature\"></i>' . $langs->trans('UptoSignBtnSign') . '</a></div>';
				}
			}
		}
		if ($sealbtn) {
			$retour .=  '<div class="inline-block divButAction"><a class="butAction" href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?id=' . $object->id . '&action=uptoseal">' . $langs->trans('UptoSignBtnSeal') . '</a></div>';
		} else {
			$retour .= '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" title="' . $sealbtnMessage . '" href="#">' . $langs->trans('UptoSignBtnSeal') . '</a></div>';
		}

		return $retour;
	}

	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;

		$error = 0;
		$tabErrors = array();
		dol_syslog("uptosign: " . get_class($this) . '::doMassActions ' . json_encode($parameters));

		$massContextClasses = array(
			'propallist' => 'Propal',
			'orderlist' => 'Commande',
			'invoicelist' => 'Facture',
			'contractlist' => 'Contrat',
			'interventionlist' => 'Fichinter',
			'shipmentlist' => 'Expedition',
			'projectlist' => 'Project',
		);

		$currentcontext = $parameters['currentcontext'];
		if (isset($massContextClasses[$currentcontext]) && $parameters['massaction'] == "uptosealMass") {
			$className = $massContextClasses[$currentcontext];
			$obj = new $className($db);
			foreach ($parameters['toselect'] as $id) {
				$res = $obj->fetch($id);
				if ($res > 0) {
					$uptoSign = new UptoSign($this->db);
					$filename = dol_sanitizeFileName($obj->ref);
					// Resolve document directory based on object type
					$elem = $obj->element;
					if ($elem == 'shipping') {
						$baseDir = $conf->expedition->dir_output . "/sending";
					} elseif ($elem == 'contrat') {
						$baseDir = $conf->contrat->dir_output;
					} elseif ($elem == 'facture') {
						$baseDir = $conf->invoice->dir_output;
					} elseif ($elem == 'project') {
						$baseDir = $conf->projet->dir_output;
					} else {
						$baseDir = $conf->{$elem}->dir_output;
					}
					$dir = $baseDir . "/" . $filename;
					$resUTS = $uptoSign->sealInit($user, $obj, $dir);
					if ($resUTS < 0) {
						$error++;
						$tabErrors = array_merge($tabErrors, $uptoSign->errors);
						dol_syslog("uptosign: " . get_class($this) . '::doMassActions error, ' . json_encode($uptoSign->errors));
					}
				}
			}
		}

		if (!$error) {
			return 0;
		} else {
			$this->errors = array_unique($tabErrors);
			return -1;
		}
	}

	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
		$langs->load("uptosign@uptosign");
		dol_syslog("uptosign: " . get_class($this) . '::addMoreMassActions ' . json_encode($parameters));

		$error = 0;
		$disabled = 0;

		$massContextLabels = array(
			'propallist' => 'UptoSignSealProposals',
			'orderlist' => 'UptoSignSealOrders',
			'invoicelist' => 'UptoSignSealInvoices',
			'contractlist' => 'UptoSignSealContracts',
			'interventionlist' => 'UptoSignSealInterventions',
			'shipmentlist' => 'UptoSignSealShipments',
			'projectlist' => 'UptoSignSealProjects',
		);

		$currentcontext = $parameters['currentcontext'];
		if (isset($massContextLabels[$currentcontext])) {
			dol_syslog("uptosign: " . get_class($this) . '::addMoreMassActions ' . $currentcontext);
			$this->resprints = '<option value="uptosealMass"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans($massContextLabels[$currentcontext]) . '</option>';
		}

		if (!$error) {
			return 0;
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}




	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog("uptosign: " . get_class($this) . '::executeHooks beforePDFCreation action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {		// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog("uptosign: " . get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}



	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$langs->load("uptosign@uptosign");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'uptosign') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("UptoSign");
			$this->results['picto'] = 'uptosign@uptosign';
		}

		$head[$h][0] = 'customreports.php?objecttype=' . $parameters['objecttype'] . (empty($parameters['tabfamily']) ? '' : '&tabfamily=' . $parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
	}



	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	<0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->hasRight('uptosign', 'myobject', 'read')) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}


	/**
	 * Overloading the emailElementlist function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function emailElementlist($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('emailtemplates'))) {
			if ($user->hasRight('uptosign', 'create')) {
				// $this->results['uptosign_init_propal'] = $langs->trans('UptoSignInitPropalTemplate');
				// $this->results['uptosign_end_propal'] = $langs->trans('UptoSignEndPropalTemplate');
				// $this->results['uptosign_init_expedition'] = $langs->trans('UptoSignInitExpeditionTemplate');
				// $this->results['uptosign_end_expedition'] = $langs->trans('UptoSignEndExpeditionTemplate');
				// $this->results['uptosign_init_commande'] = $langs->trans('UptoSignInitCommandeTemplate');
				// $this->results['uptosign_end_commande'] = $langs->trans('UptoSignEndCommandeTemplate');
				// $this->results['uptosign_init_fichinter'] = $langs->trans('UptoSignInitInterventionTemplate');
				// $this->results['uptosign_end_fichinter'] = $langs->trans('UptoSignEndInterventionTemplate');
				// $this->results['uptosign_init_contrat'] = $langs->trans('UptoSignInitContractTemplate');
				// $this->results['uptosign_end_contrat'] = $langs->trans('UptoSignEndContratTemplate');
				// $this->results['uptosign_reminders'] = $langs->trans('UptoSignReminders');
				// $this->results['uptosign_refused'] = $langs->trans('UptoSignRefused');
			} else {
				$this->results = array();
			}
		}

		if (! $error) {
			return 0;                                    // or return 1 to replace standard code
		} else {
			array_push($this->errors, 'Error message 005');
			return -1;
		}
	}


	/**
	 * Overloading the printFieldListOption function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListOption($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		return 0;
	}


	/**
	 * Overloading the printFieldListTitle function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		return 0;                                    // or return 1 to replace standard code
	}


	/**
	 * Overloading the printFieldListValue function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function printFieldListValue($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $totalarray, $i;

		$error = 0; // Error counter
		$errors = array();
		$signStatus = null;
		$dolObject = new stdClass();
		$dolObject->db = $this->db;

		return 0;                                    // or return 1 to replace standard code
	}

	private function _getMoreInfoFor($object)
	{
		$out = "";
		$uptoSign = new UptoSign($this->db);
		// $result = $uptoSign->fetchAll(null, null, $object->id, $object->element);
		$object_type = uptosign_unify_object_type(uptosignModel($object));
		$result = $uptoSign->fetchByObject((int) $object->id, $object_type);
		foreach ($result as $uts) {
			// print '<p>'.json_encode($uts).'</p>';
			if ($uts->api_name == 'uptoseal') {
				$out .= " <a href='" . dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . $uts->id . "' title='Document scellé par UpToSign'><i class=\"fas fa-stamp\"></i></a>";
			} elseif ($uts->api_name == 'uptosign') {
				$out .= " <a href='" . dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . $uts->id . "' title='Signature électronique UpToSign'><i class=\"fas fa-signature\"></i></a>";
			}
		}
		// print "<p> on a $out</p>";
		return $out;
	}

	/**
	 * Change the signature area
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function changeSignatureArea($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		$signCount = 0;

		//Récupération des différentes UptoSignConfig
		$config = new UptoSignConfig($object->db);
		$model_pdf = uptosignModel($object);

		$configIds = $config->fetchListId($model_pdf, $object->element, 'sign');
		$typeContacts = $config->getTypeContactCode($object->element, $parameters['sourceContact']);
		$sourceContacts = $config->getSourceContactCode($object->element);

		//Parcourt les ID de configuration
		foreach ($configIds as $configId) {
			//Récupére les configurations disponible
			$res = $config->fetch($configId);

			//Si le nombre de configuration est en dessous de 0 retourne une erreur
			if ($res <= 0) {
				array_push($this->errors, 'changeSignatureArea: no config available for that file');;
				return --$error;
			}

			//Récupère les informations du contact et de l'utilisateur
			$contactCode = $typeContacts[$config->fk_c_type_contact];
			$contactSource = $sourceContacts[$config->fk_c_type_contact];
			if ($object->getIdContact($contactSource, $contactCode)) {
				$signCount += count($object->getIdContact($contactSource, $contactCode));
			}
		}

		$height = $parameters['tab'] * 3;

		for ($i = 0; $i < $signCount; $i++) {
			$parameters['pdf']->addEmptySignatureAppearance($parameters['posx'], $parameters['posy'] + $parameters['tab'], $parameters['largcol'], $height, -1, 'sign_' . $parameters['sourceContact']);
			$parameters['pdf']->SetXY($parameters['posx'], $parameters['posy'] + $parameters['tab']);
			$parameters['pdf']->MultiCell($parameters['largcol'], $height, '', 1, 'R');
			$parameters['posy'] += $height + 4;
		}

		return $i;
	}


	/**
	 * Ajout d'une petite information sur la ligne du document scellé ou signé
	 *
	 * @param  array        $parameters  Hook metadatas (context, etc...)
	 * @param  CommonObject $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param  string       $action      Current action (if set). Generally create or edit or null
	 * @param  HookManager  $hookmanager Hook manager propagated to allow calling another hook
	 * @return int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formBuilddocLineOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		// dol_syslog(get_class($this).'::formBuilddocLineOptions parameters ' . json_encode($parameters));
		// dol_syslog(get_class($this).'::formBuilddocLineOptions object ' . json_encode($object));
		// dol_syslog(get_class($this).'::formBuilddocLineOptions action ' . json_encode($action));
		// print '<p>::formBuilddocLineOptions object ' . json_encode($parameters['modulepart']) . '</p>';

		//params {"colspan":6,"socid":"150","id":"","modulepart":"company","relativepath":"150\/Mandat SEPA-31-RUM-CU2206-00002-31-1678642586_signed-20230312183626.pdf","context":"fileslib:formfile:main:searchform:leftblock:toprightmenu:thirdpartybancard:globalcard","currentcontext":"formfile"}
		//object {"name":"Mandat SEPA-31-RUM-CU2206-00002-31-1678642586_signed-20230312183626.pdf","path":"\/home\/webs\/bar.devtemp.fr\/documents\/societe\/150","level1name":"150","relativename":"Mandat SEPA-31-RUM-CU2206-00002-31-1678642586_signed-20230312183626.pdf","fullname":"\/home\/webs\/bar.devtemp.fr\/documents\/societe\/150\/Mandat SEPA-31-RUM-CU2206-00002-31-1678642586_signed-20230312183626.pdf","date":1678643651,"size":"","type":"file","position_name":"16_Mandat SEPA-31-RUM-CU2206-00002-31-1678642586_signed-20230312183626.pdf","position":16,"cover":null,"acl":null,"rowid":"291","label":"e73bef3effde35eb603b04c870d550e8","share":null}
		$out = "";

		//sepamandate
		// if($parameters['modulepart'] == "company")

		if (is_file($object['fullname'])) {
			$hash = hash_file('sha256', $object['fullname']);
			$uptoSign = new UptoSign($this->db);
			$object_type = uptosign_unify_object_type($parameters['modulepart']);
			$result = $uptoSign->fetchByObject((int) $parameters['id'], $object_type, array('hash_file_signed' => $hash));
			if (is_array($result)) {
				$res = reset($result);
				if ($res === false) {
					dol_syslog("uptosign: " . get_class($this) . '::formBuilddocLineOptions file is not signed or sealed, returns resprints len=0 and 0');
					$this->resprints = '';
					return 0;
				} else {
					dol_syslog("uptosign: " . get_class($this) . '::formBuilddocLineOptions match result ' . json_encode($res));
					if ($res->api_name == 'uptoseal') {
						$out = "<td><a href=" . dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . $res->id . " title=\"Document scellé par UpToSign\"><i class=\"fas fa-stamp\"></i></a></td>";
					} elseif ($res->api_name == 'uptosign') {
						$out = "<td><a href=" . dol_buildpath('/uptosign/uptosign_card.php', 1) . '?id=' . $res->id . " title=\"Signature électronique UpToSign\"><i class=\"fas fa-signature\"></i></a></td>";
					}
				}
			} else {
				dol_syslog("uptosign: " . get_class($this) . '::formBuilddocLineOptions file is not known');
				$this->resprints = '';
				return 0;
			}
		} else {
			dol_syslog("uptosign: " . get_class($this) . '::formBuilddocLineOptions that file name doest not exists');
			$this->resprints = '';
			return 0;
		}
		// if ($out == "") {
		// 	$out = '<td>&nbsp;</td>';
		// }
		$this->resprints = $out;
		return 0;

		// $relativepath = $parameters['relativepath'];
		// $modulepart   = $parameters['modulepart'];
		// $socid        = $parameters['id'];

		// $documenturl = DOL_URL_ROOT.'/document.php';
		// if (utsbackports_getDolGlobalString('DOL_URL_ROOT_DOCUMENT_PHP','')  != '') $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper

		// $documenturl .= '?modulepart=' . $modulepart . '&file='.urlencode($relativepath).'&socid='.$socid;

		// $out = "<td><a href='#'>";
		// $out .= img_picto($langs->trans("PrintFile", $relativepath), 'printer.png', 'onclick="printJS({printable:\'' . $documenturl . '\', type:\'pdf\', showModal:true})"');
		// $out .= "</a></td>";

		// $this->resprints = $out;
		// return;
	}


	/**
	 * fix #27 : capture le hook sur le clonage d'un objet pour appliquer uptosign comme signature par défaut
	 * si l'option globale est active dans la conf du module
	 *
	 * @param  array        $parameters  Hook metadatas (context, etc...)
	 * @param  CommonObject $object      The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param  string       $action      Current action (if set). Generally create or edit or null
	 * @param  HookManager  $hookmanager Hook manager propagated to allow calling another hook
	 * @return int                             < 0 on error, 0 on success, 1 to replace standard code
	 *
	 */
	function createFrom($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$errors = array(); // error mesgs
		$dir = $res = null;
		$currentcontext = $parameters['currentcontext'];

		if (!in_array($currentcontext, $this->array_of_handled_context)) {
			//nothing to do here
			return 0;
		}

		// print "<p>object type is: " . json_encode($object->element)."</p>"; print json_encode($parameters); print json_encode($object); print "<p>action: " . $action."</p>"; exit;
		// dol_syslog("uptosign createFrom param = " . json_encode($object) . ", action = $action", LOG_DEBUG);
		// dol_syslog("uptosign doAction object = " . json_encode($object), LOG_DEBUG);

		// we need object id and element ...
		if (empty($object->id) || empty($object->element)) {
			dol_syslog("uptosign createFrom $action, error object id or element is empty (b) !");
			return 0;
		}

		if (isset($object->array_options) && empty($object->array_options['options_digitalsign']) && utsbackports_getDolGlobalString('UPTOSIGN_FORCE_AS_DEFAULT_SIGN_SYSTEM_CLONE', '') != '') {
			$object->array_options['options_digitalsign'] = 'uptosign';
			$res = $object->updateExtraField('digitalsign');
			if ($res > 0) {
				dol_syslog("uptosign createFrom clone update digitalsign empty to uptosign", LOG_DEBUG);
			} else {
				dol_syslog("uptosign createFrom clone can't update digitalsign ! res value is $res", LOG_INFO);
			}
		} else {
			dol_syslog("uptosign createFrom clone do not update object : " . $object->array_options['options_digitalsign'], LOG_INFO);
		}

		return 0;
	}
}
