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
	*	\file		./infrasworkflow/core/lib/infrasworkflow.lib.php
	*	\ingroup	InfraS
	*	\brief		Functions used by InfraS module
	************************************************/

	// Libraries************************************
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	if (isModEnabled('infraspackplus')) {
		dol_include_once('/infraspackplus/core/lib/infraspackplus.lib.php');
	}

	/**
	*	Search an extrafield by name
	*
	*	@param		integer		$set			-2	= delete extrafields
	*											-1	= disable extrafields
	*											0	= check
	*											1	= update or create
	*											2	= create extrafields
	*	@param		string		$tempName		temporary name of exterafield (used when the $const values are not loaded => init module process)
	*	@param		string		$constKey		Key used to store the name of extrafield
	*	@param		string		$langKey		Translation key used for the extrafield label
	*	@param		array		$listElem		Elements on which the extrafield must be found
	*	@param		array		$listParams		extrafield parameters
	*												'type'
	*												'pos'
	*												'size'
	*												'unique'
	*												'required'
	*												'default_value'
	*												'param'
	*												'alwayseditable'
	*												'perms'
	*												'list'
	*												'help'
	*												'computed'
	*												'entity'
	*												'langfile'
	*												'enabled'
	*												'totalizable'
	*												'printable'
	*	@return		integer|array				if $set = -2 =>	number of extrafields deleted
	*											if $set = -1 =>	number of extrafields disabled
	*											if $set = 0 =>	0 = extrafield missing on all elements
	*															1 = extrafield found and enabled on all elements
	*															array of all elements (as keys) with value -1 if not found, 0 if not enabled, 1 if enabled
	*											if $set = 1 =>	array 'updated' = number of extrafields updated, 'created' = number of extrafields created
	*											if $set = 2 =>	number of extrafields created
	*											0	= not found and we don't want to create them
	*											-1	= not enough parameters
	*											-2 on error
	**/
	function infrasworkflow_manage_extf ($set = 0, $tempName = '', $constKey = '', $langKey = '', $listElem = array(), $listParams = array())
	{
		global $db;

		if ((!empty($tempName) || !empty($constKey)) && !empty($langKey) && !empty($listElem)) {
			$name	= getDolGlobalString($constKey, $tempName);
			if (!empty($name)) {
				$sql		= 'SELECT elementtype FROM '.$db->prefix()."extrafields WHERE name LIKE '".$db->escape($name)."'";
				$resql		= $db->query($sql);
				if (!empty($resql)) {
					$num	= $db->num_rows($resql);
					if ($num == 0 && $set == 0)	{
						return 0;	// there is no extrafield found and we don't want to create them
					}
					$results	= 0;
					$extra		= new ExtraFields($db);
					if ($set == 2) {	// create
						foreach($listElem as $newElementType)	{
							$results	+= $extra->addExtraField($name,
																$langKey,
																$listParams['type'],
																$listParams['pos'],
																$listParams['size'],
																$newElementType,
																$listParams['unique'],
																$listParams['required'],
																$listParams['default_value'],
																$listParams['param'],
																$listParams['alwayseditable'],
																$listParams['perms'],
																$listParams['list'],
																$listParams['help'],
																$listParams['computed'],
																$listParams['entity'],
																$listParams['langfile'],
																$listParams['enabled'],
																$listParams['totalizable'],
																$listParams['printable']
																);
						}
						return $results;
					}
					$listElemFound	= array();
					while ($obj	= $db->fetch_object($resql))	{
						$listElemFound[]	= $obj->elementtype;
					}
					$diff	= array_diff($listElem, $listElemFound);	// Retourne un tableau contenant toutes les entités du tableau `$listElem` qui ne sont pas présentes dans `$listElemFound`
					if ($set == 0) {	// check
						if (!empty($diff) && count($diff) == count($listElem)) {
							return 0;	// if the extrafield is missing on all elements
						} else {
							$listElems	= array();
							foreach($listElem as $elementType) {	// On parcours tous les éléments cherchés pour vérifier leur existence et si oui leur activation
								$listElems[$elementType]	= -1;	// -1 = not found
								$sqlcheck	= 'SELECT enabled FROM '.$db->prefix().'extrafields WHERE elementtype LIKE "'.$db->escape($elementType).'" AND name LIKE "'.$db->escape($name).'"';
								$resqlcheck	= $db->query($sqlcheck);
								if (!empty($resqlcheck)) {
									$rowElem					= $db->fetch_object($resqlcheck);
									$listElems[$elementType]	= $rowElem->enabled;	// 0 = not enabled, 1 = enabled
								}
							}
							$counts		= array_count_values($listElems);
							$nbEnabled	= isset($counts[1]) ? $counts[1] : 0;
							if ($nbEnabled == count($listElem)) {
								return 1;	// if all elements have the extrafield enabled
							} else {
								return $listElems;	// array of all elements (as keys) with value -1 if not found, 0 if not enabled, 1 if enabled
							}
						}
					}
					if ($set == 1) {	// update or create
						foreach($listElemFound as $newElementType)	{
							$results	+= $extra->update($name,
															$langKey,
															$listParams['type'],
															$listParams['size'],
															$newElementType,
															$listParams['unique'],
															$listParams['required'],
															$listParams['pos'],
															$listParams['param'],
															$listParams['alwayseditable'],
															$listParams['perms'],
															$listParams['list'],
															$listParams['help'],
															$listParams['default_value'],
															$listParams['computed'],
															$listParams['entity'],
															$listParams['langfile'],
															(!empty($listParams['enabled']) ? $listParams['enabled'] : 1),
															$listParams['totalizable'],
															$listParams['printable']
														);
						}
						if (!empty($diff)) {	// some extrafields are missing for some types; we need to create the missing elements
							$resdiff	= 0;
							foreach($diff as $newElementType)	{
								$resdiff	+= $extra->addExtraField($name,
																	$langKey,
																	$listParams['type'],
																	$listParams['pos'],
																	$listParams['size'],
																	$newElementType,
																	$listParams['unique'],
																	$listParams['required'],
																	$listParams['default_value'],
																	$listParams['param'],
																	$listParams['alwayseditable'],
																	$listParams['perms'],
																	$listParams['list'],
																	$listParams['help'],
																	$listParams['computed'],
																	$listParams['entity'],
																	$listParams['langfile'],
																	(!empty($listParams['enabled']) ? $listParams['enabled'] : 1),
																	$listParams['totalizable'],
																	$listParams['printable']);
							}
							return array('updated' => $results, 'created' => $resdiff);	// we return the number of extrafields updated + the number created
						}
						return $results;
					}
					if ($set == -1) {	// disable
						foreach($listElemFound as $oldElementType)	{
							$results	+= $extra->update($name,
															$langKey,
															$listParams['type'],
															$listParams['size'],
															$oldElementType,
															$listParams['unique'],
															$listParams['required'],
															$listParams['pos'],
															$listParams['param'],
															$listParams['alwayseditable'],
															$listParams['perms'],
															$listParams['list'],
															$listParams['help'],
															$listParams['default_value'],
															$listParams['computed'],
															$listParams['entity'],
															$listParams['langfile'],
															0,
															$listParams['totalizable'],
															$listParams['printable']);
						}
						return $results;	// < 0 if KO, 0 if nothing is done, 1 if OK
					}
					if ($set == -2) {	// delete
						foreach($listElemFound as $oldElementType)	{
							$results	+= $extra->delete($name, $oldElementType);
						}
						return $results;	// < 0 if KO, 0 if nothing is done, 1 if OK
					}
				} else {
					return -2;
				}
			}
		}
		return -1;
	}

	/**
	*	Check extrafield name
	*
	*	@param		string		$name		Name orr code to check
	*	@return		integer					> 0	= Ok
	*										-1	= alphabetical and lower case only error
	*										-2	= reserved keyword error
	*										-3	= length error (less than 3 charaters)
	**/
	function infrasworkflow_check_extf_name ($name = '')
	{
		global $langs;

		$langs->load('errors');
		$name	= dol_string_nospecial($name, 'aZ09');
		if (preg_match('/^[a-z0-9-_]+$/', $name) && !is_numeric($name)) {	// alphabetical and lower case only
			// length must be superior to 3 characters
			if (strlen($name) < 3) {
				setEventMessages($langs->trans('ErrorValueLength', $langs->transnoentitiesnoconv('AttributeCode'), 3), null, 'errors');
				return -3;
			}
			// Check reserved keyword with more than 3 characters
			$isV19p = version_compare(DOL_VERSION, '19.0.0') >= 0;
			if ($isV19p) {
				$listofreservedwords	= array('ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASENSITIVE',
												'BEFORE', 'BETWEEN', 'BINARY', 'BLOB', 'BOTH',
												'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION', 'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR',
												'DATABASE', 'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DECIMAL', 'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DOUBLE', 'DROP', 'DUAL',
												'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXPLAIN',
												'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FORCE', 'FOREIGN', 'FULLTEXT',
												'GRANT', 'GROUP',
												'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND',
												'IGNORE', 'IGNORE_SERVER_IDS', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INTEGER', 'INTERVAL', 'INTO', 'ITERATE',
												'KEYS', 'KEYWORD',
												'LEADING', 'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINES', 'LOCALTIME', 'LOCALTIMESTAMP', 'LONGBLOB', 'LONGTEXT',
												'MASTER_SSL_VERIFY_SERVER_CERT', 'MATCH', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MODIFIES',
												'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NUMERIC',
												'OFFSET', 'ON', 'OPTION', 'OPTIONALLY', 'OUTER', 'OUTFILE', 'OVER',
												'PARTITION', 'POSITION', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE',
												'RANGE', 'READS', 'READ_WRITE', 'REAL', 'REFERENCES', 'REGEXP', 'RELEASE', 'RENAME', 'REPEAT', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE',
												'SCHEMAS', 'SECOND_MICROSECOND', 'SENSITIVE', 'SEPARATOR', 'SIGNAL', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN',
												'TABLE', 'TERMINATED', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TRAILING', 'TRIGGER',
												'UNDO', 'UNIQUE', 'UNSIGNED', 'UPDATE', 'USAGE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP',
												'VALUES', 'VARBINARY', 'VARCHAR', 'VARYING',
												'WHEN', 'WHERE', 'WHILE', 'WRITE',
												'XOR',
												'YEAR_MONTH',
												'ZEROFILL'
												);
			} else {
				$listofreservedwords	= array('AND', 'KEYWORD', 'TABLE', 'INDEX', 'INT', 'INTEGER', 'FLOAT', 'DOUBLE', 'REAL', 'POSITION');
			}
			if (in_array(strtoupper($name), $listofreservedwords)) {
				setEventMessages($langs->trans('ErrorReservedKeyword', $name), null, 'errors');
				return -2;
			}
			return 1;
		} else {	// must be alphabetical and lower case only
			setEventMessages($langs->trans('ErrorFieldCanNotContainSpecialNorUpperCharacters', $langs->transnoentities('AttributeCode')), null, 'errors');
			return -1;
		}
	}

	/**
	* Creates a deposit from a proposal or an order with a fixed monetary amount
	*
	* @param	Propal|Commande		$origin					The original proposal or order
	* @param	int					$date					Invoice date
	* @param	int					$payment_terms_id		Invoice payment terms
	* @param	User				$user					Object user
	* @param	int					$notrigger				1=Does not execute triggers, 0= execute triggers
	* @param	bool				$autoValidateDeposit	Whether to aumatically validate the deposit created
	* @param	array				$overrideFields			Array of fields to force values
	* @param	float				$deposit_amount			Fixed deposit amount (monetary value)
	* @return	Facture|null								The deposit created, or null if error (populates $origin->error in this case)
	**/
	function infrasworkflow_createdepositfromorigin($origin, $date, $payment_terms_id, $user, $notrigger = 0, $autoValidateDeposit = false, $overrideFields = array(), $deposit_amount = 0)
	{
		global $conf, $langs, $hookmanager, $action;
		if (!in_array($origin->element, array('propal', 'commande'))) {
			$origin->error	= 'ErrorCanOnlyAutomaticallyGenerateADepositFromProposalOrOrder';
			dol_syslog(__METHOD__.' : '.$origin->error.' - element ='.$origin->element, LOG_ERR);
			return null;
		}
		if (empty($date)) {
			$origin->error	= $langs->trans('ErrorFieldRequired', $langs->transnoentities('DateInvoice'));
			dol_syslog(__METHOD__.' : '.$origin->error.' - date empty', LOG_ERR);
			return null;
		}
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		if ($date > (dol_get_last_hour(dol_now('tzuserrel')) + (empty($conf->global->INVOICE_MAX_FUTURE_DELAY) ? 0 : $conf->global->INVOICE_MAX_FUTURE_DELAY))) {
			$origin->error	= 'ErrorDateIsInFuture';
			dol_syslog(__METHOD__.' : '.$origin->error.' - date = '.dol_print_date($date, 'dayhour'), LOG_ERR);
			return null;
		}
		if ($payment_terms_id <= 0) {
			$origin->error	= $langs->trans('ErrorFieldRequired', $langs->transnoentities('PaymentConditionsShort'));
			dol_syslog(__METHOD__.' : '.$origin->error.' - payment_terms_id = '.$payment_terms_id, LOG_ERR);
			return null;
		}
		// Vérification du montant de l'acompte
		if (empty($deposit_amount) || $deposit_amount <= 0) {
			$origin->error	= $langs->trans('ErrorFieldRequired', $langs->transnoentities('DepositAmount'));
			dol_syslog(__METHOD__.' : '.$origin->error.' - deposit_amount = '.$deposit_amount, LOG_ERR);
			return null;
		}
		// Vérification que le montant de l'acompte n'est pas supérieur au total TTC
		if ($deposit_amount > $origin->total_ttc) {
			$origin->error	= $langs->trans('ErrorDepositAmountHigherThanTotal');
			dol_syslog(__METHOD__.' : '.$origin->error.' - deposit_amount = '.$deposit_amount.' > total_ttc = '.$origin->total_ttc, LOG_ERR);
			return null;
		}
		$deposit						= new Facture($origin->db);
		$deposit->socid					= $origin->socid;
		$deposit->type					= $deposit::TYPE_DEPOSIT;
		$deposit->fk_project			= $origin->fk_project;
		$deposit->ref_client			= $origin->ref_client;
		$deposit->date					= $date;
		$deposit->mode_reglement_id		= $origin->mode_reglement_id;
		$deposit->cond_reglement_id		= $payment_terms_id;
		$deposit->availability_id		= $origin->availability_id;
		$deposit->demand_reason_id		= $origin->demand_reason_id;
		$deposit->fk_account			= $origin->fk_account;
		$deposit->fk_incoterms			= $origin->fk_incoterms;
		$deposit->location_incoterms	= $origin->location_incoterms;
		$deposit->fk_multicurrency		= $origin->fk_multicurrency;
		$deposit->multicurrency_code	= $origin->multicurrency_code;
		$deposit->multicurrency_tx		= $origin->multicurrency_tx;
		$deposit->module_source			= $origin->module_source;
		$deposit->pos_source			= $origin->pos_source;
		$deposit->model_pdf				= 'crabe';
		$modelByTypeConfName			= 'FACTURE_ADDON_PDF_'.$deposit->type;
		if (!empty($conf->global->$modelByTypeConfName)) {
			$deposit->model_pdf	= $conf->global->$modelByTypeConfName;
		} elseif (!empty($conf->global->FACTURE_ADDON_PDF)) {
			$deposit->model_pdf	= $conf->global->FACTURE_ADDON_PDF;
		}
		if (empty($conf->global->MAIN_DISABLE_PROPAGATE_NOTES_FROM_ORIGIN)) {
			$deposit->note_private	= $origin->note_private;
			$deposit->note_public	= $origin->note_public;
		}
		$deposit->origin		= $origin->element;
		$deposit->origin_id		= $origin->id;
		// Copy of extrafields from origin
		$origin->fetch_optionals();
		$arrayExfPropalSelected	= array_filter(explode(',', getDolGlobalString('INFRASWORKFLOW_EXF_NO_TRANSFER_PROPAL_TO_DEPOSIT', '')));
		foreach ($origin->array_options as $extrakey => $value) {
			if (!in_array(str_replace('options_', '', $extrakey), $arrayExfPropalSelected)) {
				$deposit->array_options[$extrakey]	= $value;
			}
		}
		// Linked objects
		$deposit->linked_objects[$deposit->origin]	= $deposit->origin_id;
		// Override fields
		foreach ($overrideFields as $key => $value) {
			$deposit->$key	= $value;
		}
		$deposit->context['createdepositfromorigin']	= 'createdepositfromorigin';
		$origin->db->begin();
		// Facture::create() also imports contact from origin
		$createReturn									= $deposit->create($user, $notrigger);
		if ($createReturn <= 0) {
			$origin->db->rollback();
			$origin->error	= $deposit->error;
			$origin->errors	= $deposit->errors;
			return null;
		}
		$amount_ttc_diff	= 0;
		$amountdeposit		= array();
		$descriptions		= array();
		if (!empty($conf->global->MAIN_DEPOSIT_MULTI_TVA)) {
			// Calcul de la répartition du montant fixe selon les taux de TVA
			$TTotalByTva	= array();
			$total_ttc		= 0;
			foreach ($origin->lines as &$line) {
				if (!empty($line->special_code)) {
					continue;
				}
				if (!isset($TTotalByTva[$line->tva_tx])) {
					$TTotalByTva[$line->tva_tx]	= 0;
				}
				if (!isset($descriptions[$line->tva_tx])) {
					$descriptions[$line->tva_tx]	= '';
				}
				$TTotalByTva[$line->tva_tx]		+= $line->total_ttc;
				$total_ttc						+= $line->total_ttc;
				$descriptions[$line->tva_tx]	.= '<li>'.(!empty($line->product_ref) ? $line->product_ref.' - ' : '');
				$descriptions[$line->tva_tx]	.= (!empty($line->product_label) ? $line->product_label.' - ' : '');
				$descriptions[$line->tva_tx]	.= $langs->trans('Qty').' : '.$line->qty;
				$descriptions[$line->tva_tx]	.= ' - '.$langs->trans('TotalHT').' : '.price($line->total_ht).'</li>';
			}
			foreach ($TTotalByTva as $tva => &$total) {
				$coef					= $total / $total_ttc; // Calcul du coefficient pour la répartition proportionnelle
				$am						= $deposit_amount * $coef; // Répartition du montant TTC selon le coefficient
				$amount_ttc_diff		+= $am;
				$amountdeposit[$tva]	= $am / (1 + $tva / 100); // Conversion en HT pour l'ajout de ligne
			}
		} else {
			// Utilisation d'un taux de TVA unique (première ligne non spéciale)
			$tva_tx	= 0;
			foreach ($origin->lines as $line) {
				if (empty($line->special_code)) {
					$tva_tx	= $line->tva_tx;
					break;
				}
			}
			// On ajoute une seule ligne avec le montant HT calculé
			$amountdeposit[$tva_tx]	= $deposit_amount / (1 + $tva_tx / 100);
			$amount_ttc_diff		= $deposit_amount;
			// Description détaillée pour la ligne unique
			foreach ($origin->lines as $line) {
				if (empty($line->qty) || !empty($line->special_code)) {
					continue;
				}
				if (!isset($descriptions[$tva_tx])) {
					$descriptions[$tva_tx]	= '';
				}
				$descriptions[$tva_tx]	.= '<li>'.(!empty($line->product_ref) ? $line->product_ref.' - ' : '');
				$descriptions[$tva_tx]	.= (!empty($line->product_label) ? $line->product_label.' - ' : '');
				$descriptions[$tva_tx]	.= $langs->trans('Qty').' : '.$line->qty;
				$descriptions[$tva_tx]	.= ' - '.$langs->trans('TotalHT').' : '.price($line->total_ht).'</li>';
			}
		}
		foreach ($amountdeposit as $tva => $amount) {
			if (empty($amount)) {
				continue;
			}
			$descline	= '(DEPOSIT) - '.$origin->ref.' - '.price($deposit_amount, 0, $langs, 1, -1, -1, $conf->currency);
			// Hidden conf
			if (!empty($conf->global->INVOICE_DEPOSIT_VARIABLE_MODE_DETAIL_LINES_IN_DESCRIPTION) && !empty($descriptions[$tva])) {
				$descline .= '<ul>'.$descriptions[$tva].'</ul>';
			}
			$addlineResult	= $deposit->addline($descline, $amount, 1, $tva, 0, 0, getDolGlobalInt('INVOICE_PRODUCTID_DEPOSIT', 0), 0, 0, 0, 0, 0, 0, 'HT', 0, 0, 1, 0, $deposit->origin, 0, 0, 0, 0);
			if ($addlineResult < 0) {
				$origin->db->rollback();
				$origin->error	= $deposit->error;
				$origin->errors	= $deposit->errors;
				return null;
			}
		}
		$diff	= $deposit->total_ttc - $amount_ttc_diff;
		// Correction si différence minime due aux arrondis
		if (!empty($conf->global->MAIN_DEPOSIT_MULTI_TVA) && abs($diff) >= 0.01) {
			$deposit->fetch_lines();
			$subprice_diff		= $deposit->lines[0]->subprice - $diff / (1 + $deposit->lines[0]->tva_tx / 100);
			$updatelineResult	= $deposit->updateline($deposit->lines[0]->id, $deposit->lines[0]->desc, $subprice_diff, $deposit->lines[0]->qty, $deposit->lines[0]->remise_percent, $deposit->lines[0]->date_start, $deposit->lines[0]->date_end, $deposit->lines[0]->tva_tx, 0, 0, 'HT', $deposit->lines[0]->info_bits, $deposit->lines[0]->product_type, 0, 0, 0, $deposit->lines[0]->pa_ht, $deposit->lines[0]->label, 0, array(), 100);
			if ($updatelineResult < 0) {
				$origin->db->rollback();
				$origin->error	= $deposit->error;
				$origin->errors	= $deposit->errors;
				return null;
			}
		}
		$hookmanager->initHooks(array('invoicedao'));
		$parameters	= array('objFrom' => $origin);
		$reshook	= $hookmanager->executeHooks('createFrom', $parameters, $deposit, $action); // Note that $action and $object may have been
		// modified by hook
		if ($reshook < 0) {
			$origin->db->rollback();
			$origin->error	= $hookmanager->error;
			$origin->errors	= $hookmanager->errors;
			return null;
		}
		if (!empty($autoValidateDeposit)) {
			$validateReturn	= $deposit->validate($user, '', 0, $notrigger);
			if ($validateReturn < 0) {
				$origin->db->rollback();
				$origin->error	= $deposit->error;
				$origin->errors	= $deposit->errors;
				return null;
			}
		}
		unset($deposit->context['createdepositfromorigin']);
		$origin->db->commit();
		return $deposit;
	}

	/**
	* Get label for types of element
	*
	* @return	array	Associative array [type => label]
	**/
	function infrasworkflow_gettype2label()
	{
		global $langs;

		// Liste des formats supportés
		$tmptype2label			= ExtraFields::$type2label;
		$type2label				= array('');
		foreach ($tmptype2label as $key => $val) {
			$type2label[$key]	= $langs->transnoentitiesnoconv($val);
		}
		return $type2label;
	}

	/**
	* Get all elementtypes for extra fields with a human-readable label.
	*
	* @return	array	Associative array [elementtype => label]
	**/
	function infrasworkflow_getallextrafields()
	{
		global $db;

		$sql			= 'SELECT DISTINCT elementtype FROM '.$db->prefix().'extrafields ORDER BY elementtype';
		$resql			= $db->query($sql);
		$elementtypes	= array();
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$elementtypes[$obj->elementtype] = $obj->elementtype;
			}
		} else {
			dol_print_error($db);
		}
		return $elementtypes;
	}

	/**
	* Count total number of extra fields defined.
	*
	* @return int
	**/
	function infrasworkflow_countallextrafields()
	{
		global $db;

		$sql	= 'SELECT COUNT(rowid) AS cnt FROM '.$db->prefix().'extrafields';
		$res	= $db->query($sql);
		if ($res) {
			$obj = $db->fetch_object($res);
			return (int)$obj->cnt;
		} else {
			dol_print_error($db);
			return 0;
		}
	}

	/**
	* Built a list of propagation chains based on the element type.
	*
	* @param	mixed		$elementtype	Element type for which we want to get the linked object types
	* @param	bool		$include		Whether to include the current element type from the result
	* @return	array
	**/
	function infrasworkflow_getPropagationChains($elementtype, $include = false)
	{
		$client_related_objects		= array();
		$client_related_lines		= array();
		$supplier_related_objects	= array();
		$supplier_related_lines		= array();
		// CAS PARTICULIER : si l'élément est 'societe', on ne propose QUE les 3 modules cibles si options activées
		if ($elementtype === 'societe') {
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_INVOICE', 0)) {
				$client_related_objects[] = 'facture';
			}
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_ORDER', 0)) {
				$client_related_objects[] = 'commande';
			}
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_SUPPLIER_ORDER', 0)) {
				$supplier_related_objects[] = 'commande_fournisseur';
			}
			// On retourne directement la liste filtrée
			$result = array_merge($client_related_objects, $supplier_related_objects);
			return $include ? $result : array_diff($result, array($elementtype));
		}
		// Propagation possible chaine des ventes => Attributs de documents et lignes de documents
		if (isModEnabled('propal')) {
			$client_related_objects[]	= 'propal';
			$client_related_lines[]		= 'propaldet';
		}
		if (isModEnabled('commande')) {
			$client_related_objects[]	= 'commande';
			$client_related_lines[]		= 'commandedet';
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_ORDER', 0) && in_array($elementtype, array('commande', 'societe'))) {
				$client_related_objects[]	= 'societe'; // 'societe' = tiers Dolibarr
			}
		}
		if (isModEnabled('ficheinter')) {
			$client_related_objects[]	= 'fichinter';
			$client_related_lines[]		= 'fichinterdet';
		}
		if (isModEnabled('expedition')) {
			$client_related_objects[]	= 'expedition';
			$client_related_lines[]		= 'expeditiondet';
		}
		if (isModEnabled('contrat')) {
			$client_related_objects[]	= 'contrat';
			$client_related_lines[]		= 'contratdet';
		}
		if (isModEnabled('facture')) {
			$client_related_objects[]	= 'facture';
			$client_related_lines[]		= 'facturedet';
			if ($elementtype === 'facture') {
				$client_related_objects[]	= 'facture_rec';
			}
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_INVOICE', 0) && in_array($elementtype, array('facture', 'societe'))) {
				$client_related_objects[]	= 'societe'; // 'societe' = tiers Dolibarr
			}
		}
		if (isModEnabled('projet') && isModEnabled('infrasproject')) {
			$client_related_objects[]	= 'projet';
		}
		// Propagation possible chaine des achats => Attributs de documents et lignes de documents
		if (isModEnabled('supplier_proposal')) {
			$supplier_related_objects[]	= 'supplier_proposal';
			$supplier_related_lines[]	= 'supplier_proposaldet';
		}
		if (isModEnabled('supplier_order')) {
			$supplier_related_objects[]	= 'commande_fournisseur';
			$supplier_related_lines[]	= 'commande_fournisseurdet';
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_SUPPLIER_ORDER', 0) && in_array($elementtype, array('commande_fournisseur', 'societe'))) {
				$client_related_objects[]	= 'societe'; // 'societe' = tiers Dolibarr
			}
		}
		if (isModEnabled('supplier_invoice')) {
			$supplier_related_objects[]	= 'facture_fourn';
			$supplier_related_lines[]	= 'facture_fourn_det';
		}
		if (isModEnabled('societe')) {
			// Propagation uniquement si les options sont activées
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_INVOICE', 0) && in_array($elementtype, array('facture', 'societe'))) {
				$client_related_objects[] = 'facture'; // 'facture' = facture Dolibarr
			}
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_ORDER', 0) && in_array($elementtype, array('commande', 'societe'))) {
				$client_related_objects[] = 'commande'; // 'commande' = commande Dolibarr
			}
			if (getDolGlobalInt('THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_SUPPLIER_ORDER', 0) && in_array($elementtype, array('commande_fournisseur', 'societe'))) {
				$supplier_related_objects[] = 'commande_fournisseur'; // 'commande_fournisseur' = commande fournisseur Dolibarr
			}
		}
		if (in_array($elementtype, $client_related_objects)) {
			return empty($include) ? array_diff($client_related_objects, array($elementtype)) : $client_related_objects;
		} elseif (in_array($elementtype, $client_related_lines)) {
			return empty($include) ? array_diff($client_related_lines, array($elementtype)) : $client_related_lines;
		} elseif (in_array($elementtype, $supplier_related_objects)) {
			return empty($include) ? array_diff($supplier_related_objects, array($elementtype)) : $supplier_related_objects;
		} elseif (in_array($elementtype, $supplier_related_lines)) {
			return empty($include) ? array_diff($supplier_related_lines, array($elementtype)) : $supplier_related_lines;
		}
		return array();
	}

	/**
	* Built a list of check boxes and warning options according to the existence or not of a field in other propagations of objects.
	*
	* @param string		$elementtype		Element type we work on => we should not include this type in the result
	* @param string		$attrname			Extrafield name to check
	* @param array		$linked_objects		List of linked element types to test
	* @param string		$prefixlabel		Prefix for checkbox labels
	* @param bool		$invert				Invert logic (false = show if field does not exist, true = show if field exists)
	* @param string		$operation			Type of operation in progress (e.g. 'add', 'update', etc.)
	* @return array							'elementtype'	=> Element type we work on (to transmit it)
	*										'list'			=> Array of checkboxes
	*										'delete'		=> empty string or 'definitive' if it's the main element and we are in delete mode (no trash or the field is already disabled)
	*										'hasdefinitive'	=> false or true if we have at least one definitive deletion
	*										'hasdifftypes'	=> empty or 'type of extrafield source' if we have at least one extrafield with a different type for the same name
	**/
	function infrasworkflow_buildCheckBoxes($elementtype, $attrname, $linked_objects, $prefixlabel = 'InfraSWorkflowExf_', $invert = false, $operation = '')
	{
		global $db, $langs;

		$type2label	= infrasworkflow_gettype2label();
		$checkboxes	= array('elementtype' => $elementtype, 'list' => array(), 'delete' => '', 'hasdefinitive' => false, 'hasdifftypes' => '');
		if ($operation == 'update') {
			// Define list of possible type transition
			$sourcetype				= '';	// Type of the extrafield in the source elementtype => for consistency control (if attributes of the same name have different types)
			$typewecanchangeinto	= infrasworkflow_get_wecanchangeinto();
			$sqlsourcetype			= 'SELECT type FROM '.$db->prefix().'extrafields WHERE name = "'.$db->escape($attrname).'" AND elementtype = "'.$db->escape($elementtype).'"';
			$resqlsourcetype		= $db->query($sqlsourcetype);
			if ($resqlsourcetype) {
				$objsourcetype	= $db->fetch_object($resqlsourcetype);
				if (!empty($objsourcetype)) {
					$sourcetype	= $objsourcetype->type;
				}
			}
		}
		foreach ($linked_objects as $target) {
			$sql	= 'SELECT type, enabled FROM '.$db->prefix().'extrafields WHERE name = "'.$db->escape($attrname).'" AND elementtype = "'.$db->escape($target).'"';
			$resql	= $db->query($sql);
			if ($resql) {
				$obj			= $db->fetch_object($resql);
				$hasdifftype	= '';	// We control if we already have a different type for the same extrafield name
				$definitive		= false;	// Final deletion not activated => Management of warning messages
				if (!empty($obj)) {
					if ($operation == 'update') {
						// We control if we already have a different type for the same extrafield name => no different type has been found yet and the source exists
						if (!empty($sourcetype) && isset($typewecanchangeinto[$sourcetype]) && in_array($obj->type, $typewecanchangeinto[$sourcetype])) {
							$checkboxes['hasdifftypes']	= empty($checkboxes['hasdifftypes']) && in_array($obj->type, $typewecanchangeinto[$sourcetype]) ? '' : $sourcetype;
							$hasdifftype				= in_array($obj->type, $typewecanchangeinto[$sourcetype]) ? '' : $obj->type;
						} else {
							$checkboxes['hasdifftypes'] = $sourcetype;	// We have a different type and we cannot change type => we set the source type as type with difference (to display in warning message)
							$hasdifftype				= $obj->type;
						}
					} elseif ($operation == 'delete' && $db->num_rows($resql) > 0) {
						// If we are in deletion mode and the field exists, we control if the field is enabled and if the trash mode is active
						if (getDolGlobalInt('INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE', 0) && empty($obj->enabled)) {
							// Standard mode (direct deletion) or TRASH and already disabled
							$checkboxes['hasdefinitive']	= true;	// we have at least one definitive deletion
							$definitive						= true;	// the current extra attribute will be deleted definitively
							if ($elementtype == $target) {	// If it's the main element, we add the mention definitive deletion
								$checkboxes['delete']	= 'definitive';
							}
						}
					}
				}
				if ($elementtype != $target && (($db->num_rows($resql) == 0 && !$invert) || ($db->num_rows($resql) > 0 && $invert))) {
					$texthasdifftype		= $hasdifftype ? ' <span class = "infrasworkflowcaution bold">'.$langs->trans('InfraSWorkflowUpdateAlert2').' => '.$type2label[$hasdifftype].'</span>' : '';
					$textdefinitive			= $definitive ? ' <span class = "infrasworkflowcaution bold">'.$langs->trans('InfraSWorkflowDeleteAlert3').'</span>' : '';
					$checkboxes['list'][]	= array('type'	=> 'checkbox',
													'name'	=> 'check_'.$target,
													'label'	=> dol_ucfirst($langs->trans($prefixlabel.$target)).$texthasdifftype.$textdefinitive,
													'value'	=> 0
													);
				}
			}
		}
		return $checkboxes;
	}

	/**
	* Ajoute des cases à cocher à un formulaire pour les objets dérivés.
	*
	* @param array		$formquestion	Tableau des questions du formulaire
	* @param int		$height			Hauteur du formulaire
	* @param array		$checkboxes		Liste des cases à cocher à ajouter
	* @param string		$title			Titre pour la section des cases à cocher
	* @param string		$action			action en cours(add, clone, update ou delete)
	* @return void
	**/
	function infrasworkflow_appendCheckboxesToForm(&$formquestion, &$height, $checkboxes, $title, $action)
	{
		global $langs;

		if ($action == 'update') {
			if (!empty($checkboxes['hasdifftypes'])) {
				$type2label		= infrasworkflow_gettype2label();
				$formquestion[]	= array('type' => 'other', 'value' => '<p class = "infrasworkflowcaution center bold" style = "margin-top: 10px;">'.$langs->trans('InfraSWorkflowCreateAlert', $type2label[$checkboxes['hasdifftypes']]).'</p>');
				$height			+= 68;
			}
		} elseif ($action == 'delete') {
			if (!getDolGlobalInt('INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE', 0)) {	// mode TRASH actif
				$formquestion[]	= array('type' => 'other', 'value' => '<p class = "infrasworkflowcaution center bold" style = "margin-top: 10px;">'.$langs->trans('InfraSWorkflowDeleteAlert4').'</p>');
				$height			+= 68;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
				$formquestion[]	= array('type' => 'other', 'value' => '<p class = "infrasworkflowcaution center" style = "margin-top: 10px;">'.$langs->trans('InfraSWorkflowDeleteHelp').'</p>');
				$height			+= 68;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
				$formquestion[]	= array('type' => 'other', 'value' => '<p class = "infrasworkflowcaution center" style = "margin-top: 10px;">'.$langs->trans('InfraSWorkflowDeleteHelp2').'</p>');
				$height			+= 56;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
			}
			if (!empty($checkboxes['delete'])) {
				$text			= $langs->trans('InfraSWorkflowDeleteAlert').' '.$langs->trans('InfraSWorkflowExf_'.$checkboxes['elementtype']).' '.$langs->trans('InfraSWorkflowDeleteAlert2');
				$formquestion[]	= array('type' => 'other', 'value' => '<p class = "infrasworkflowcaution bold center" style = "margin-top: 10px;">'.$text.'</p>');
				$height			+= 34;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
			}
			if (!empty($checkboxes['hasdefinitive'])) {
				$formquestion[]	= array('type' => 'other', 'value' => '<p class = "infrasworkflowcaution center" style = "margin-top: 10px;">'.$langs->trans('InfraSWorkflowDeleteHelp').'</p>');
				$height			+= 68;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
			}
		}
		if (!empty($checkboxes['list'])) {
			$formquestion[]	= array('type' => 'other', 'value' => '<p style = "font-weight: bold; margin-top: 10px;">'.$title.'</p>');
			$height			+= 28;
			$formquestion[]	= array('type' => 'separator'); // Espacement
			$height			+= 13;

			// Ajout de la checkbox "Tout cocher"
			if (count($checkboxes['list']) >= 2) {
				$formquestion[]	= array('type'		=> 'checkbox',
										'name'		=> 'checkall_'.uniqid(),
										'label'		=> $langs->trans('InfraSWorkflowCheckAll'),
										'tdclass'	=> 'right',
										'moreattr'	=> 'onclick = "infrasCheckAll(this)"',
										'value'		=> 0
										);
				$height			+= 15;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
			}
			foreach ($checkboxes['list'] as $checkbox) {
				$formquestion[]	= array('type'	=> 'checkbox',
										'name'	=> (isset($checkbox['name']) ? $checkbox['name'] : ''),
										'label'	=> (isset($checkbox['label']) ? $checkbox['label'] : ''),
										'tdclass'	=> 'pair',
										'value'	=> (!empty($checkbox['checked']) ? 1 : 0)
										);
				$height			+= 15;
				$formquestion[]	= array('type' => 'separator'); // Espacement
				$height			+= 13;
			}
			$formquestion[]		= array('type'	=> 'other',
										'value'	=> '<script type = "text/javascript">
														function infrasCheckAll(source) {
															var checkboxes	= document.querySelectorAll(".infrasworkflow-checkbox");
															for (var i = 0; i < checkboxes.length; i++) {
																checkboxes[i].checked	= source.checked;
															}
														}

														// Fonction pour vérifier les cases à cocher et adapter leur apparence
														function updateCheckAllState() {
															var checkboxes		= document.querySelectorAll(".infrasworkflow-checkbox");
															var checkAllBox		= document.querySelector("input[name^=\'checkall_\']");
															var checkedCount	= 0;
															if (checkboxes.length === 0 || !checkAllBox) {
																return;
															}
															for (var i = 0; i < checkboxes.length; i++) {
																if (checkboxes[i].checked) {
																	checkedCount++;
																}
															}
															// Mettre à jour la case "Tout cocher"
															if (checkedCount === 0) {
																checkAllBox.checked			= false;
																checkAllBox.indeterminate	= false;
															} else if (checkedCount === checkboxes.length) {
																checkAllBox.checked			= true;
																checkAllBox.indeterminate	= false;
															} else {
																checkAllBox.checked			= false;
																checkAllBox.indeterminate	= true;
															}
														}

														// Appliquer la classe manuellement aux input[type=checkbox] liés
														document.addEventListener("DOMContentLoaded", function() {
															const allCheckboxes	= document.querySelectorAll("input[type=\'checkbox\']");
															allCheckboxes.forEach(function(cb) {
																// Filtrage uniquement ceux qui sont dans la zone
																if (cb.name && cb.name.startsWith("check_")) {
																	cb.classList.add("infrasworkflow-checkbox");
																	// Ajouter un listener pour mettre à jour la case "Tout cocher"
																	cb.addEventListener("change", updateCheckAllState);
																}
															});

															// Mise à jour initiale
															updateCheckAllState();
														});
													</script>'
										);
		}
	}

	/**
	* Vérifie la validité des valeurs saisies selon le type d'attribut supplémentaire désiré, la taille demandée et ses autres paramètres.
	*
	* @param	string		$attrname		Nom du type d'objet associé à l'attribut (ex: 'propal', 'propaldet', 'commande', 'commandedet', etc.)
	* @param	string		$type			Type du champ (ex: 'varchar', 'int', 'select', etc.)
	* @param	string		$param			Paramètre associé au champ (ex: liste de valeurs pour un champ 'select')
	* @param	int			$extrasize		Taille maximale autorisée pour le champ (selon le type)
	* @param	array		$mesgs			Tableau de messages d'erreur à compléter en cas de problème
	* @param	string		$action			Action en cours ('confirm_add' ou 'confirm_update'), modifiée si erreur
	* @return	int							Retourne 0 si pas d'erreur, sinon le nombre d'erreurs détectées
	**/
	function infrasworkflow_checkValues($attrname, $type, $param, $extrasize, &$mesgs, &$action)
	{
		global $langs;

		$isV21p			= version_compare(DOL_VERSION, '21.0.0') >= 0;
		$error			= 0;
		$maxsizestring	= 255;
		$maxsizeint		= 10;
		$mode			= $action == 'add' ? 'create' : 'edit';
		if (GETPOST('button') != $langs->trans('Cancel')) {
			// Check values
			if (!$type) {
				$error++;
				$langs->load('errors');
				$mesgs[]	= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type'));
				$action		= $mode;
			}
			if ($type == 'varchar' && $extrasize <= 0) {
				$error++;
				$langs->load('errors');
				$mesgs[]	= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Size'));
				$action		= $mode;
			}
			if ($type == 'varchar' && $extrasize > $maxsizestring) {
				$error++;
				$langs->load('errors');
				$mesgs[]	= $langs->trans('ErrorSizeTooLongForVarcharType', $maxsizestring);
				$action		= $mode;
			}
			if ($isV21p && $type == 'stars' && ($extrasize < 1 || $extrasize > 10)) {
				$error++;
				$langs->load('errors');
				$mesgs[]	= $langs->trans('ErrorSizeForStarsType');
				$action		= $mode;
			}
			if ($type == 'int' && $extrasize > $maxsizeint) {
				$error++;
				$langs->load('errors');
				$mesgs[]	= $langs->trans('ErrorSizeTooLongForIntType', $maxsizeint);
				$action		= $mode;
			}
			if (in_array($type, ['select', 'sellist', 'checkbox', 'radio', 'link']) && !$param) {
				$errMesg	= array('select'	=> 'ErrorNoValueForSelectType',
									'sellist'	=> 'ErrorNoValueForSelectListType',
									'checkbox'	=> 'ErrorNoValueForCheckBoxType',
									'radio'		=> 'ErrorNoValueForRadioType',
									'link'		=> 'ErrorNoValueForLinkType'
									);
				$error++;
				$mesgs[]	= $langs->trans($errMesg[$type]);
				$action		= $mode;
			}
			if ((($type == 'radio') || ($type == 'checkbox')) && $param) {
				// Construct array for parameter (value of select list)
				$parameters	= $param;
				if (str_contains($parameters, "\r\n")) {
					$parameters	= str_replace("\r\n", "\n", $parameters);
				}
				$parameters_array	= explode("\n", $parameters);
				foreach ($parameters_array as $param_ligne) {
					if (!empty($param_ligne)) {
						if (!preg_match('/^[^,]+(,[^,]+)?$/', $param_ligne)) {
							$error++;
							$mesgs[]	= $langs->trans('ErrorBadFormatValueList', $param_ligne);
							$action		= $mode;
						}
					}
				}
			}
			if (!$error && strlen($attrname) < 3 && ($mode !== 'edit' || !getDolGlobalInt('MAIN_DISABLE_EXTRAFIELDS_CHECK_FOR_UPDATE', 0))) {
				$error++;
				$mesgs[]	= $langs->trans('ErrorValueLength', $langs->transnoentitiesnoconv('AttributeCode'), 3);
				$action		= $mode;
			}
			if (!$error && infrasworkflow_check_extf_name ($attrname) < 0 && ($mode !== 'edit' || !getDolGlobalInt('MAIN_DISABLE_EXTRAFIELDS_CHECK_FOR_UPDATE', 0))) {
				$error++;
				$mesgs[]	= $langs->trans('ErrorReservedKeyword', $attrname);
				$action		= $mode;
			}
		}
		return $error;
	}

	/**
	* Construct array for parameter (value of select list)
	*
	* @param	string		$type			Type du champ (ex: 'varchar', 'int', 'select', etc.)
	* @param	string		$param			Paramètre associé au champ (ex: liste de valeurs pour un champ 'select')
	* @return	array						parameters for select list or SQL expression or php expression in an array
	**/
	function infrasworkflow_getArrayParams($type, $param)
	{
		$isV20p	= version_compare(DOL_VERSION, '20.0.0') >= 0;
		$params	= array();
		if (str_contains($param, "\r\n")) {
			$param	= str_replace("\r\n", "\n", $param);
		}
		$parameters_array	= explode("\n", $param);
		//In sellist we have only one line and it can have come to do SQL expression
		if ($type == 'sellist' || $type == 'chkbxlst') {
			foreach ($parameters_array as $param_ligne) {
				$params['options']	= array($param => null);
			}
		} else {
			// Else it's separated key/value and coma list
			foreach ($parameters_array as $param_ligne) {
				if (strpos($param_ligne, ',') !== false) {
					if (!$isV20p) {	// Before v20, we had a simple key,value format
						list($key, $value)	= explode(',', $param_ligne);
					} else {	// From v20, we can have multiple comma separated values but only the first one is the key, the rest is the value
						$tmp	= explode(',', $param_ligne);
						$key	= $tmp[0];
						if (!empty($tmp[1])) {
							$value	= $tmp[1];
						}
					}
					if (!array_key_exists('options', $params)) {
						$params['options']	= array();
					}
				} else {
					$key	= $param_ligne;
					$value	= null;
				}
				$params['options'][$key]	= $value;
			}
		}
		return $params;
	}

	/**
	* Analyse tous les extrafields et leur statut/module.
	*
	* @param	string		$do_clean			Indique si on doit nettoyer les extrafields orphelins (true) ou juste les lister (false)
	* @param	string		$clean_type			Type de nettoyage à faire (empty = pas de nettoyage, 'orphelins' = nettoyage des orphelins, 'disabled' = nettoyage des extrafields de modules externes désactivés, 'deleted' = nettoyage des extrafields de modules externes supprimés)
	* @return	array		$result				Tableau des résultats de l'audit
	**/
	function infrasworkflow_audit_extrafields($do_clean = false, $clean_type = '')
	{
		global $db, $conf;

		$sql	= 'SELECT rowid, name, label, elementtype FROM '.$db->prefix().'extrafields WHERE type NOT LIKE "separate" ORDER BY elementtype, name';
		$resql	= $db->query($sql);
		$result = array ('orphelins'		=> array(),
						'external_disabled'	=> array(),
						'external_deleted'	=> array(),
						'internal_disabled'	=> array(),
						'cleaned'			=> array()
						);
		if (!$resql) {
			dol_syslog('Erreur SQL extrafields : '.$db->lasterror(), LOG_ERR);
			return $result;
		}
		// 1. Récupère tous les extrafields de llx_extrafields
		$extrafields_llx	= array();
		while ($obj = $db->fetch_object($resql)) {
			$extrafields_llx[$obj->elementtype][$obj->name]	= array ('rowid'		=> $obj->rowid,
																	 'name'			=> $obj->name,
																	 'label'		=> $obj->label,
																	 'elementtype'	=> $obj->elementtype
																	);
		}
		// 2. Pour chaque elementtype, récupère les colonnes de la table d’extrafields du module
		foreach ($extrafields_llx as $elementtype => $fields) {
			$table			= $db->prefix() . $elementtype . '_extrafields';
			$sql_table		= 'SHOW TABLES LIKE "'.$db->escape($table).'"';
			$res_table		= $db->query($sql_table);
			$table_exists	= ($res_table && $db->num_rows($res_table) > 0);

			$module_cols	= array();
			if ($table_exists) {
				$sql_col	= 'SHOW COLUMNS FROM "'.$db->escape($table).'"';
				$res_col	= $db->query($sql_col);
				if ($res_col) {
					while ($col = $db->fetch_object($res_col)) {
						if (!in_array($col->Field, ['rowid', 'tms', 'fk_object', 'import_key'])) {
							$module_cols[]	= $col->Field;
						}
					}
				}
			}
			// Orphelins dans llx_extrafields (présents dans llx_extrafields mais pas dans la table du module)
			foreach ($fields as $name => $info) {
				if (!$table_exists || !in_array($name, $module_cols)) {
					$result['orphelins'][]	= array ('rowid'		=> $info['rowid'],
													'name'			=> $name,
													'label'			=> $info['label'],
													'elementtype'	=> $elementtype,
													'table'			=> $table,
													'table_exists'	=> $table_exists,
													'col_exists'	=> ($table_exists && in_array($name, $module_cols)),
													'type'			=> 'llx_extrafields'
													);
				}
			}
			// Orphelins dans la table du module (présents dans la table du module mais pas dans llx_extrafields)
			foreach ($module_cols as $colname) {
				if (!isset($fields[$colname])) {
					$result['orphelins'][]	= array ('name'			=> $colname,
													'elementtype'	=> $elementtype,
													'table'			=> $table,
													'table_exists'	=> $table_exists,
													'col_exists'	=> true,
													'type'			=> 'module_table'
													);
				}
			}
		}
		// 3. Vérifie le statut du module pour chaque extrafield
		while ($obj	= $db->fetch_object($resql)) {
			$table			= $db->prefix() . $obj->elementtype . '_extrafields';
			if (!preg_match('/^[a-z0-9_]+$/', $obj->elementtype)) {
				continue; // Skip invalid elementtype
			}
			// Vérifie si la table existe
			$table_exists	= false;
			$sql_table		= 'SHOW TABLES LIKE "'.$db->escape($table).'"';
			$res_table		= $db->query($sql_table);
			if ($res_table && $db->num_rows($res_table) > 0) {
				$table_exists	= true;
			}
			// Vérifie si la colonne existe dans la table
			$col_exists	= false;
			if ($table_exists) {
				$sql_col	= 'SHOW COLUMNS FROM "'.$db->escape($table).'" LIKE "'.$db->escape($obj->name).'"';
				$res_col	= $db->query($sql_col);
				if ($res_col && $db->num_rows($res_col) > 0) {
					$col_exists	= true;
				}
			}
			// Vérifie le statut du module
			$module_name		= infrasworkflow_getModuleFromElementType($obj->elementtype);
			$is_enabled			= isModEnabled($module_name);
			$module_type		= infrasworkflow_getModuleType($module_name);
			$custom_dir			= DOL_DOCUMENT_ROOT.'/custom/'.$module_name;
			$custom_dir_exists	= is_dir($custom_dir);
			// Module interne désactivé (juste pour affichage)
			if ($module_type == 'core' && !$is_enabled) {
				$result['internal_disabled'][]	= array ('rowid'		=> $obj->rowid,
														'name'			=> $obj->name,
														'label'			=> $obj->label,
														'elementtype'	=> $obj->elementtype,
														'table'			=> $table
														);
				continue;
			}
		}
		// 4. Détection des extrafields de modules externes désactivés
		$standard_elementtypes	= array_keys(infrasworkflow_getModuleFromElementType('all')); // infrasworkflow_getModuleFromElementType doit retourner un tableau complet si 'all'
		while ($obj = $db->fetch_object($resql)) {
			// Si ce n'est pas un elementtype standard
			if (!in_array($obj->elementtype, $standard_elementtypes)) {
				// Récupère le nom du module externe via la colonne langs
				$module_dir	= '';
				$sql_langs	= 'SELECT langs FROM '.$db->prefix().'extrafields WHERE rowid = '.((int) $obj->rowid);
				$res_langs	= $db->query($sql_langs);
				if ($res_langs && $db->num_rows($res_langs) > 0) {
					$obj_langs	= $db->fetch_object($res_langs);
					if (!empty($obj_langs->langs) && strpos($obj_langs->langs, '@') !== false) {
						$module_dir	= explode('@', $obj_langs->langs)[1];
					}
				}
				$custom_dir	= DOL_DOCUMENT_ROOT.'/custom/'.$module_dir;
				if (is_dir($custom_dir)) {
					$is_enabled	= isModEnabled($module_dir);
					if (!$is_enabled) {
						// Vérifie si la colonne existe dans la table du module
						$table			= $db->prefix().$obj->elementtype.'_extrafields';
						$table_exists	= false;
						$col_exists		= false;
						$sql_table		= 'SHOW TABLES LIKE "'.$db->escape($table).'"';
						$res_table		= $db->query($sql_table);
						if ($res_table && $db->num_rows($res_table) > 0) {
							$table_exists	= true;
							$sql_col		= 'SHOW COLUMNS FROM "'.$db->escape($table).'" LIKE "'.$db->escape($obj->name).'"';
							$res_col		= $db->query($sql_col);
							if ($res_col && $db->num_rows($res_col) > 0) {
								$col_exists	= true;
							}
						}
						$result['external_disabled'][]	= array('rowid'			=> $obj->rowid,
																'name'			=> $obj->name,
																'label'			=> $obj->label,
																'elementtype'	=> $obj->elementtype,
																'table'			=> $table,
																'table_exists'	=> $table_exists,
																'col_exists'	=> $col_exists
																);
					}
				}
			}
		}
		while ($obj = $db->fetch_object($resql)) {
			$module_dir	= preg_split('/[_-]/', $obj->elementtype)[0];
			if (!in_array($obj->elementtype, $standard_elementtypes)) {
				// Récupère le nom du module externe via la colonne langs
				$module_dir	= '';
				$sql_langs	= 'SELECT langs FROM '.$db->prefix().'extrafields WHERE rowid = '.((int) $obj->rowid);
				$res_langs	= $db->query($sql_langs);
				if ($res_langs && $db->num_rows($res_langs) > 0) {
					$obj_langs	= $db->fetch_object($res_langs);
					if (!empty($obj_langs->langs) && strpos($obj_langs->langs, '@') !== false) {
						$module_dir	= explode('@', $obj_langs->langs)[1];
					}
				}
				$custom_dir	= DOL_DOCUMENT_ROOT.'/custom/'.$module_dir;
				if (!is_dir($custom_dir)) {
					// Vérifie si la colonne existe dans la table du module
					$table				= $db->prefix().$obj->elementtype.'_extrafields';
					$table_exists		= false;
					$col_exists			= false;
					$sql_table			= 'SHOW TABLES LIKE "'.$db->escape($table).'"';
					$res_table			= $db->query($sql_table);
					if ($res_table && $db->num_rows($res_table) > 0) {
						$table_exists	= true;
						$sql_col		= 'SHOW COLUMNS FROM "'.$db->escape($table).'" LIKE "'.$db->escape($obj->name).'"';
						$res_col		= $db->query($sql_col);
						if ($res_col && $db->num_rows($res_col) > 0) {
							$col_exists	= true;
						}
					}
					// On ne prend que ceux qui ne sont pas orphelins (colonne existe ET ligne existe)
					if ($table_exists && $col_exists) {
						$result['external_deleted'][]	= array ('rowid'		=> $obj->rowid,
																'name'			=> $obj->name,
																'label'			=> $obj->label,
																'elementtype'	=> $obj->elementtype,
																'table'			=> $table,
																'table_exists'	=> $table_exists,
																'col_exists'	=> $col_exists
																);
					}
				}
			}
		}
		// Nettoyage si demandé
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extra	= new ExtraFields($db);
		if ($do_clean && $clean_type == 'orphelins') {
			foreach ($result['orphelins'] as $exf) {
				$extra->delete($exf['name'], $exf['elementtype']);
				$result['cleaned'][]	= $exf;
			}
		}
		if ($do_clean && $clean_type == 'external_disabled') {
			foreach ($result['external_disabled'] as $exf) {
				$extra->delete($exf['name'], $exf['elementtype']);
				$result['cleaned'][]	= $exf;
			}
		}
		if ($do_clean && $clean_type == 'external_deleted') {
			foreach ($result['external_deleted'] as $exf) {
				if (!empty($exf['name']) && !empty($exf['elementtype'])) {
					$extra->delete($exf['name'], $exf['elementtype']);
				}
				$result['cleaned'][]	= $exf;
			}
		}
		return $result;
	}

	/**
	* Retrouve le nom du module à partir de la valeur d'elementtype
	*
	* @param	string			$elementtype		La valeur d'elementtype
	* @return	array|string						$elementtype si non trouvé, Nom du module trouvé ou tableau de correspondance si 'all'
	**/
	function infrasworkflow_getModuleFromElementType($elementtype)
	{
		// Table de correspondance
		$map = array (	'propal'						=> 'propale',
						'propaldet'						=> 'propale',
						'commande'						=> 'commande',
						'commandedet'					=> 'commande',
						'commande_fournisseur'			=> 'fournisseur',
						'commande_fournisseurdet'		=> 'fournisseur',
						'commande_fournisseur_dispatch'	=> 'fournisseur',
						'facture'						=> 'facture',
						'facture_rec'					=> 'facture',
						'facture_fourn'					=> 'fournisseur',
						'facture_fourn_det'				=> 'fournisseur',
						'facturedet'					=> 'facture',
						'projet'						=> 'projet',
						'projet_task'					=> 'projet',
						'projet_task_time'				=> 'projet',
						'expedition'					=> 'expedition',
						'expeditiondet'					=> 'expedition',
						'fichinter'						=> 'ficheinter',
						'mrp_mo'						=> 'mrp',
						'product'						=> 'product',
						'service'						=> 'service',
						'contrat'						=> 'contrat',
						'contratdet'					=> 'contrat',
						'socpeople'						=> 'societe',
						'reception'						=> 'reception',
						'stock_mouvement'				=> 'stock',
						'entrepot'						=> 'stock',
						'supplier_proposal'				=> 'supplier_proposal',
						'supplier_proposaldet'			=> 'supplier_proposal',
						'user'							=> 'user',
						'societe'						=> 'societe',
						// Ajoute ici les autres correspondances nécessaires
					);
		if ($elementtype === 'all') {
			return $map;
		}
		return isset($map[$elementtype]) ? $map[$elementtype] : $elementtype;
	}

	/**
	* Retrouve le type de module à partir de son nom
	*
	* @param	string		$module_name	Le nom du module
	* @return	string						le type de module : 'core', 'external' ou 'unknown'
	**/
	function infrasworkflow_getModuleType($module_name)
	{
		global $db;

		// Tente de charger la classe du module interne
		$clean_module_name	= '';
		foreach (explode('_', $module_name) as $part) {
			$clean_module_name	.= ucfirst($part);
		}
		$core_class_file	= DOL_DOCUMENT_ROOT.'/core/modules/mod'.$clean_module_name.'.class.php';
		if (file_exists($core_class_file)) {
			require_once $core_class_file;
			$class_name	= 'mod'.$clean_module_name;
			if (class_exists($class_name)) {
				$mod	= new $class_name($db);
				return 'core';
			}
		}
		// Sinon, tente de charger le module externe
		$custom_dir	= DOL_DOCUMENT_ROOT.'/custom/'.$module_name;
		if (is_dir($custom_dir)) {
			return 'external';
		}
		return 'unknown';
	}

	/**
	 *  Define a third party as a prospect
	 *
	 *	@param		Societe		$object		Object third party
	 *	@param		int			$newclient	New value for client field (1=Customer, 2=Prospect, 3=Prospect/Customer)
	 *	@return		int						<0 if KO, >0 if OK
	*/
	function infrasworkflow_setCustomerAsProspectCustomer($object, $newclient)
	{
		global $db;

		$error = 0;
		if ($object->id) {
			$sql	= 'UPDATE '.$db->prefix().'societe SET client = '.((int) $newclient).' WHERE rowid = '.((int) $object->id);	// Prospect
			$resql	= $db->query($sql);
			if ($resql) {
				$object->client	= $newclient;
				return 1;
			} else {
				$error = $db->lasterror();
			}
		}
		return 0; // must never happen
	}

	/**
	 *	Vérifie s'il y a au moins un devis signé lié à un tiers
	 *
	 *	@param		Propal	$object		Object Propal
	 *	@return	    int       			<0 if KO, >0 if propal found, 0 if no propal linked
	 */
	function infrasworkflow_getThirdpartyLinkedPropal ($object)
	{
		global $db;

		$error = 0;
		$sql	= 'SELECT p.rowid as propalid';
		$sql	.= ' FROM '.$db->prefix().'propal as p';
		$sql	.= ' INNER JOIN '.$db->prefix().'societe as s ON p.fk_soc = s.rowid';
		$sql	.= ' WHERE p.entity IN ('.getEntity('propal').')';
		$sql	.= ' AND s.rowid = '.((int) $object->id);
		$sql	.= ' AND p.fk_statut = ('.Propal::STATUS_SIGNED.')';
		$resql	= $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num > 0) {
				return $num;
			}
		} else {
			$error = $db->lasterror();
		}
		return 0;
	}

	/**
	* Check that all required fields are filled in the customer account
	*
	*	@param	Societe		$thirdparty		The propal to process
	*	@return	int							< 0 on error, > 0 on success, 0 nothing to do
	**/
	function infrasworkflow_thirdpartyAccountControl($thirdparty)
	{
		global $db, $langs, $conf;

		if (in_array($thirdparty->client, array(1, 2, 3))) {
			$required_fields	= array('Code client'				=> array('key' => 'code_client',	'const' => 'INFRASWORKFLOW_CONTROL_CLIENT_CODE_FIELDS'),
										'Code client comptable'		=> array('key' => 'code_compta_client',	'const' => 'INFRASWORKFLOW_CONTROL_CODE_COMPTA_FIELDS'),
										'Numéro TVA'				=> array('key' => 'tva_intra',	'const' => 'INFRASWORKFLOW_CONTROL_TVA_FIELDS'),
										'Assujetti à la TVA'		=> array('key' => 'tva_assuj',	'const' => 'INFRASWORKFLOW_CONTROL_TVA_ASSUJ_FIELDS'),
										'Mode de règlement'			=> array('key' => 'mode_reglement_id',	'const' => 'INFRASWORKFLOW_CONTROL_MODE_REGLEMENT_FIELDS'),
										'Conditions de règlement'	=> array('key' => 'cond_reglement_id',	'const' => 'INFRASWORKFLOW_CONTROL_COND_REGLEMENT_FIELDS'),
										'Date signature'			=> array('key' => 'date_creation',	'const' => 'INFRASWORKFLOW_CONTROL_DATE_SIGN_FIELDS'),
										'Siret'						=> array('key' => 'idprof2',	'const' => 'INFRASWORKFLOW_CONTROL_SIRET_FIELDS'),
										'Email'						=> array('key' => 'email',	'const' => 'INFRASWORKFLOW_CONTROL_MAIL_FIELDS'),
										'Pays'						=> array('key' => 'country_id',	'const' => 'INFRASWORKFLOW_CONTROL_COUNTRY_FIELDS'),
										'Maison mère'				=> array('key' => 'parent',	'const' => 'INFRASWORKFLOW_CONTROL_PARENT_FIELDS'),
										'Contact'					=> array('key' => 'phone',	'const' => 'INFRASWORKFLOW_CONTROL_CONTACT_FIELDS')
									);
			$missing_fields		= array();
			foreach ($required_fields as $label => $field) {
				$key = $field['key'];
				if (empty($thirdparty->$key) && getDolGlobalInt($field['const'])) {
					$missing_fields[]	= $label;
				}
			}
			if (!empty($missing_fields)) {
				$missing_field	= implode(', ', $missing_fields);
				setEventMessages($langs->trans('InfraSWorkflowErrorCustomerAccountMissingFields',  $missing_field), null, 'warnings');
				if (getDolGlobalInt('SOCIETE_DISABLE_PROSPECTSCUSTOMERS', 0)) {
					dolibarr_set_const($db, 'SOCIETE_DISABLE_PROSPECTSCUSTOMERS', 0, 'int', 0, '', $conf->entity);
				}
				return -1;
			} else {
				return 1;
			}
		}
		return 0;
	}

	/**
	* Check that all mandatory fields are completed in the product sheet
	*
	*	@param	Product		$product		The propal to process
	*	@return	int							< 0 on error, > 0 on success, 0 nothing to do
	**/
	function infrasworkflow_productValidationControl($product)
	{
		global $langs;

		$sell	= GETPOST('statut');
		if ($product->status == 1 || $sell == 1 ) {
			$required_fields	= array('Code comptable (vente)'						=> array('key' => 'accountancy_code_sell',	'const' => 'INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_FIELDS'),
										'Code comptable (vente intra-communautaire)	'	=> array('key' => 'accountancy_code_sell_intra',	'const' => 'INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_INTRA_FIELDS'),
										'Code comptable (vente à l\'export)'			=> array('key' => 'accountancy_code_sell_export',	'const' => 'INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_SELL_EXPORT_FIELDS'),
										'Code comptable (achat)'						=> array('key' => 'accountancy_code_buy',	'const' => 'INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_FIELDS'),
										'Code comptable (achat intra-communautaire)	'	=> array('key' => 'accountancy_code_buy_intra',	'const' => 'INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_INTRA_FIELDS'),
										'Code comptable (achat import)'					=> array('key' => 'accountancy_code_buy_export',	'const' => 'INFRASWORKFLOW_CONTROL_ACCOUNTANCY_CODE_BUY_EXPORT_FIELDS'),
										'Code SH'										=> array('key' => 'customcode',	'const' => 'INFRASWORKFLOW_CONTROL_CUSTOM_CODE_FIELDS'),
										'Poids'											=> array('key' => 'weight',	'const' => 'INFRASWORKFLOW_CONTROL_WEIGHT_FIELDS'),
										'Pays d\'origine'								=> array('key' => 'country_id',	'const' => 'INFRASWORKFLOW_CONTROL_COUNTRY_ID_FIELDS'),
										'Zone de Stockage'								=> array('key' => 'desiredstock',	'const' => 'INFRASWORKFLOW_CONTROL_DESIRED_STOCK_FIELDS'),
										'Entrepôt par défaut'							=> array('key' => 'fk_default_warehouse',	'const' => 'INFRASWORKFLOW_CONTROL_DEFAULT_WAREHOUSE_FIELDS'),
									);
			$missing_fields		= array();
			foreach ($required_fields as $label => $field) {
				$key = $field['key'];
				if (empty($product->$key) && getDolGlobalInt($field['const'])) {
					$missing_fields[]	= $label;
				}
			}
			if (!empty($missing_fields)) {
				$missing_field	= implode(', ', $missing_fields);
				setEventMessages($langs->trans('InfraSWorkflowErrorProdValidationMissingFields',  $missing_field), null, 'warnings');
				return -1;
			}
		}
		return 0;
	}

	/**
	 *  Set a product On sale  to Off sale
	 *
	 *	@param		Product		$object		Object third party
	 *	@param		int			$status		product status (0=Off sale, 1=On sale)
	 *	@return		int						<0 if KO, >0 if OK
	*/
	function infrasworkflow_setProductAsOffSale($object, $status = 0)
	{
		global $db;

		$error = 0;
		if ($object->id) {
			$sql	= 'UPDATE '.$db->prefix().'product SET tosell = '.((int) $status).' WHERE rowid = '.((int) $object->id);
			$resql	= $db->query($sql);
			if ($resql) {
				$object->status = $status;
				return 1;
			} else {
				$error = $db->lasterror();
			}
		}
		return 0; // must never happen
	}

	/**
	* Is substitution file
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	bool
	**/
	function infrasworkflow_is_substitution_page($path)
	{
		if (strpos($path, 'infrasworkflow/substitutionpages/') !== false) {
			return true;
		}
		return false;
	}

	/**
	* Get const name from substitution path
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	string		Substitution url or empty
	**/
	function infrasworkflow_get_const_name_from_substitution_path($path)
	{
		$const_name	= 'INFRASWORKFLOW_PS_ACTIVE'.strtoupper(str_replace('/', '_', str_replace('.php', '', $path)));
		return $const_name;
	}

	/**
	* Get substitution url if exist
	*
	* @param	string	$path	Relative path from the root of Dolibarr of the page to be substituted.
	*
	* @return	string	substitution url or empty
	**/
	function infrasworkflow_get_substitution_url($path)
	{
		global $conf;

		$const_name			= infrasworkflow_get_const_name_from_substitution_path($path);
		if (getDolGlobalString($const_name, '')) {
			$dolibranch		= explode('.', DOL_VERSION);
			$coreVersion	= 'dlb'.$dolibranch[0].'0x'.(getDolGlobalString('EASYA_VERSION', '') ? '-Easya' : '');
			$path_dst		= '/infrasworkflow/substitutionpages/'.$coreVersion.$path;
			$real_path_dst	= dol_buildpath($path_dst, 0);
			dol_syslog('infrasworkflow.lib.php::infrasworkflow_get_substitution_url $path = '.$path.' $real_path_dst = '.$real_path_dst);
			if (file_exists($real_path_dst)) {
				$url_path_dst = dol_buildpath($path_dst, 2);
				return $url_path_dst;
			}
		}
		return '';
	}

	/**
	* Get substitution redirect URL with filtered query params
	*
	* @return	string		Redirect URL or empty string if no redirect needed
	**/
	function infrasworkflow_getSubstitutionRedirectUrl()
	{
		$path_src	= preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/i', '', $_SERVER['PHP_SELF']);
		if (infrasworkflow_is_substitution_page($path_src)) {
			return '';
		}
		$url	= infrasworkflow_get_substitution_url($path_src);
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
	 * Returns a mapping of Dolibarr extrafield types and the types they can be converted into.
	 * This is used to build checkboxes in forms to control allowed type transitions for derived objects.
	 *
	 * @return array Array where the key is the source type and the value is an array of allowed target types.
	 */
	function infrasworkflow_get_wecanchangeinto() {
		$typewecanchangeinto = array('varchar'	=> array('varchar', 'phone', 'mail', 'url', 'ip', 'select', 'password', 'text', 'html'),
									'double'	=> array('double', 'price'),
									'price'		=> array('double', 'price'),
									'int'		=> array('int'),
									'text'		=> array('text', 'html'),
									'html'		=> array('text', 'html'),
									'password'	=> array('password', 'varchar'),
									'mail'		=> array('varchar', 'phone', 'mail', 'url', 'ip', 'select'),
									'url'		=> array('varchar', 'phone', 'mail', 'url', 'ip', 'select'),
									'phone'		=> array('varchar', 'phone', 'mail', 'url', 'ip', 'select'),
									'ip'		=> array('varchar', 'phone', 'mail', 'url', 'ip', 'select'),
									'select'	=> array('varchar', 'phone', 'mail', 'url', 'ip', 'select'),
									'date'		=> array('date', 'datetime'),
									'bool'		=> array('bool', 'int', 'varchar'),
									'checkbox'	=> array('checkbox', 'int', 'varchar')
								);
		return $typewecanchangeinto;
	}

	/**
	* Generates the PDF document
	*
	*	@param	Facture		$invoice	The invoice to process
	*	@param	string		$action		Current action
	*	@return	int						< 0 on error, > 0 on success
	**/
	function infrasworkflow_pdfinvoicegeneration($invoice, $action = '')
	{
		global $conf, $langs;

		if (isModEnabled('infraspackplus')) {
			$hidedetails	= (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS', '') ? 1 : 0));
			$hidedesc		= (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', '') ? 1 : 0));
			$hideref		= (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF', '') ? 1 : 0));
			$idwarehouse	= GETPOSTINT('idwarehouse');
			$locationTarget	= $_SERVER['PHP_SELF'].'?id='.$invoice->id;
			$result			= infraspackplus_semiauto_update($invoice, $hidedetails, $hidedesc, $hideref, $idwarehouse, $locationTarget, $action);
		} else {
			$outputlangs	= $langs;
			$newlang		= '';
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang	= GETPOST('lang_id', 'aZ09');
			}
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
				$newlang	= $invoice->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs	= new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			$result	= $invoice->generateDocument($invoice->model_pdf, $outputlangs);
		}
		if ($result < 0) {
			$langs->load('errors');
			if (count($invoice->errors) > 0) {
				setEventMessages($invoice->error, $invoice->errors, 'errors');
			} else {
				setEventMessages($langs->trans($invoice->error), null, 'errors');
			}
			return -1;
		} else {
			return 1;
		}
	}

		/**
	*	Generate the first account automatically when the propal is mark as signed (amount)
	*
	*	@param	Propal	$object		The propal to process
	*	@return	int					< 0 on error, > 0 on success, 0 nothing to do
	**/
	function infrasworkflow_firstAccountAuto($object)
	{
		global $user, $langs;

		$object->fetch_optionals();
		$create_first_auto_deposit	= getDolGlobalInt('INFRASWORKFLOW_CREATE_FIRST_AUTO_DEPOSIT', 0);
		$exf_first_deposit			= getDolGlobalString('INFRASWORKFLOW_EXF_DEPOSIT', '');
		// Get deposit value
		$first_deposit_value		= 0;
		if (!empty($exf_first_deposit) && isset($object->array_options['options_'.$exf_first_deposit])) {
			$first_deposit_value	= $object->array_options['options_'.$exf_first_deposit];
		}
		$remaining_to_pay_with_first_deposit	= price2num($object->total_ttc) - price2num($first_deposit_value);
		if (!empty($create_first_auto_deposit) && !empty($first_deposit_value) && $remaining_to_pay_with_first_deposit >= 0) {
			$date					= dol_now();
			$forceFields			= array();
			$object->note_private	= GETPOST('note_private', 'restricthtml');
			$validate_first_deposit	= getDolGlobalInt('INFRASWORKFLOW_VALIDATE_FIRST_AUTO_DEPOSIT', 0) ? true : false;	// If true, the first deposit will be validated automatically
			try {
				$depositamount	= infrasworkflow_createdepositfromorigin($object, $date, 1, $user, 0, $validate_first_deposit, $forceFields, $first_deposit_value);
				if ($depositamount) {
					setEventMessage('DepositGenerated');
					return 1;
				}
			} catch (Throwable $e) {
				dol_syslog($langs->trans('InfraSWorkflowErrorDeposit').' : '.$e->getMessage(), LOG_ERR);
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			}
		} elseif ($remaining_to_pay_with_first_deposit < 0) {
			$object->reopen($user, Propal::STATUS_VALIDATED, $langs->trans('InfraSWorkflowDepositExceedsRemaining'));
			setEventMessages($langs->trans('InfraSWorkflowDepositExceedsRemaining'), null, 'warnings');
			return -2;
		}
		return 0;
	}

	/**
	*	Link the deposits found in the original proposal to the final invoice
	*
	*	@param	Facture	$object		The invoice to process
	*	@return	int					< 0 on error, > 0 on success, 0 if nothing to do
	**/
	function infrasworkflow_linkDepositsToFinalInvoice($object)
	{
		global $conf, $db, $langs;

		$error	= 0;
		if (getDolGlobalString('INFRASWORKFLOW_LINK_DEPOSITS_TO_FINAL_INVOICE', '')) {
			// Get the origin ID (quote) from the object properties (more reliable than GETPOST in triggers)
			$id	= 0;
			if (!empty($object->origin) && $object->origin == 'propal' && !empty($object->origin_id)) {
				$id	= $object->origin_id;
			} elseif (GETPOST('origin', 'alpha') == 'propal' && GETPOSTINT('originid') > 0) {
				// Fallback to GETPOST for manual creation, only when the origin is a proposal (originid may be a contract or order ID)
				$id	= GETPOSTINT('originid');
			}
			// Validate ID
			if (empty($id) || $id <= 0) {
				dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: No valid origin propal ID found', LOG_WARNING);
				return 0; // Nothing to do
			}
			$propal			= new infrasworkflowPropal($db);
			$result_fetch	= $propal->fetch($id);
			if ($result_fetch <= 0) {
				setEventMessages($langs->trans('InfraSWorkflowErrorFetchPropal').' (ID: '.$id.')', null, 'errors');
				dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: Failed to fetch propal ID '.$id, LOG_ERR);
				return -1;
			}

			$related_invoices	= $propal->Infrasworkflow_InvoiceArrayList($id, false);
			// Browse invoices linked to the quote
			if (!empty($related_invoices)) {
				$db->begin(); // Start transaction

				$discounts_added	= array(); // Track added discounts to avoid duplicates

				foreach ($related_invoices as $invoice) {
					$facture	= new Facture($db);
					if ($facture->fetch($invoice->facid) > 0) {
						if ($facture->type == Facture::TYPE_DEPOSIT) {
							// Load all discounts (DiscountAbsolute) linked to this deposit invoice
							$discountstatic	= new DiscountAbsolute($db);
							$sql			= 'SELECT rowid FROM '.$db->prefix().'societe_remise_except';
							$sql			.= ' WHERE fk_facture_source = '.(int) $facture->id;
							$sql			.= ' AND entity = '.((int) $conf->entity);
							$resql			= $db->query($sql);
							if ($resql) {
								while ($obj	= $db->fetch_object($resql)) {
									// Check if discount already exists in the invoice to avoid duplicates
									if (in_array($obj->rowid, $discounts_added)) {
										dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: Discount '.$obj->rowid.' already added, skipping', LOG_DEBUG);
										continue;
									}

									if ($discountstatic->fetch($obj->rowid) > 0) {
										// Verify discount is not already consumed
										if (!empty($discountstatic->fk_facture_line)) {
											dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: Discount '.$obj->rowid.' already consumed in another invoice, skipping', LOG_WARNING);
											continue;
										}

										$result	= $object->insert_discount($discountstatic->id);
										if ($result < 0) {
											$error++;
											setEventMessages($langs->trans('InfraSWorkflowErrorLinkDeposit').' (ID: '.$discountstatic->id.') : '.$object->error, $object->errors, 'errors');
											break 2; // Exit both loops on error
										} else {
											$discounts_added[]	= $obj->rowid;
											dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: Discount '.$obj->rowid.' successfully added to invoice '.$object->id, LOG_DEBUG);
										}
									}
								}
								$db->free($resql);
							} else {
								$error++;
								setEventMessages($db->lasterror(), null, 'errors');
								break; // Exit loop on error
							}
						}
					} else {
						$error++;
						setEventMessages($langs->trans('InfraSWorkflowErrorFetchDeposit').' (ID: '.$invoice->facid.') : '.$facture->error, $facture->errors, 'errors');
						break; // Exit loop on error
					}
				}

				// Commit or rollback
				if ($error > 0) {
					$db->rollback();
					dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: Rollback due to errors', LOG_ERR);
					return -1;
				} else {
					$db->commit();
					dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: '.count($discounts_added).' discount(s) successfully linked', LOG_INFO);

					// Génération du PDF
					if (!getDolGlobalInt('MAIN_DISABLE_PDF_AUTOUPDATE', 0)) {
						$pdf_result	= infrasworkflow_pdfinvoicegeneration($object);
						if ($pdf_result < 0) {
							// PDF generation failed, but discounts are added, so we return warning (1) not error
							dol_syslog('infrasworkflow_linkDepositsToFinalInvoice: PDF generation failed but discounts added', LOG_WARNING);
						}
					}
					return count($discounts_added) > 0 ? 1 : 0;
				}
			}
		}
		return 0;
	}

	/**
	*	LCopy the chosen public note(s) from the proposal to the invoice
	*
	*	@param	Facture	$object		The invoice to process
	*	@return	int					< 0 on error, > 0 on success
	**/
	function infrasworkflow_cloneNotePfromPropal($object)
	{
		global $conf, $db;

		$object->fetchObjectLinked(0, '', $object->id, $object->element);
		if (!empty($object->linkedObjectsIds['propal'])) {
			$id_propal				= reset($object->linkedObjectsIds['propal']);	// Get the value of the first element (not the key)
			$txtParamsDocPropal		= getDolGlobalString('INFRASPLUS_PDF_PARAMS_propal_DOC_'.$id_propal, '');	// Constant value
			$propalParams			= [];
			parse_str($txtParamsDocPropal, $propalParams);	// Convert to associative array
			if (!empty($propalParams['listnotep'])) {	// If 'listnotep' exists in the constant, process it
				$notesList		= explode('-', $propalParams['listnotep']);
				$filteredNotes	= array();
				foreach ($notesList as $noteKey) {
					$convertedNote	= preg_replace('/\bPROPOSAL_/i', 'INVOICE_', $noteKey);	// Replace PROPOSAL_ with INVOICE_
					if (getDolGlobalString($convertedNote, '')) {	// Check if this constant exists in Dolibarr (this note type is also planned for invoices)
						$filteredNotes[]	= $convertedNote;
					}
				}
				// Rebuild the new listnotep value
				if (!empty($filteredNotes)) {
					// Merge with already-existing invoice doc params (preserve other keys like listfreet)
					$txtParamsDocInvoice	= getDolGlobalString('INFRASPLUS_PDF_PARAMS_facture_DOC_'.$object->id, '');
					$invoiceParams			= [];
					parse_str($txtParamsDocInvoice, $invoiceParams);
					$invoiceParams['listnotep']	= implode('-', $filteredNotes);
					// Convert to final string
					$newTxtParamsDocInvoice	= http_build_query($invoiceParams);
					dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_facture_DOC_'.$object->id, $newTxtParamsDocInvoice, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
					return 1;
				}
			}
		}
		return 0;
	}

	/**
	*	Copiez la ou les notes publiques de la facture vers son avoir : d’abord le texte de la note publique de la facture source, puis la sélection de la note publique InfraSPackPlus lorsque ce paramètre est mémorisé par document.
	*
	*	@param	Facture	$object		L’avoir à traiter
	*	@return	int					< 0 en cas d’erreur, > 0 en cas de succès, 0 s’il n’y a rien à faire
	**/
	function infrasworkflow_cloneNotefromInvoice($object)
	{
		global $conf, $db;

		$res	= 0;
		// A credit note is attached to its invoice by the fk_facture_source column : Dolibarr only
		$id_invoice	= empty($object->fk_facture_source) ? 0 : (int) $object->fk_facture_source;
		if (empty($id_invoice)) {
			$object->fetchObjectLinked(0, '', $object->id, $object->element);
			if (!empty($object->linkedObjectsIds['facture'])) {
				$id_invoice	= (int) reset($object->linkedObjectsIds['facture']);	// Get the value of the first element (not the key)
			}
		}
		if (empty($id_invoice)) {
			return 0;	// No source invoice found : nothing to copy
		}
		// Copy the public note text of the source invoice on the credit note. Only when the credit note carries none of its own, so a note typed on the creation form (or already copied by Facture::createFromCurrent() for a situation credit note) is never overwritten
		if (empty($object->note_public)) {
			$sourceInvoice	= new Facture($db);
			if ($sourceInvoice->fetch($id_invoice) > 0 && !empty($sourceInvoice->note_public)) {
				$resNote	= $object->setValueFrom('note_public', $sourceInvoice->note_public, '', null, 'text', '', null, '', '');	// Last argument empty : do not stamp fk_user_modif, the credit note is being created
				if ($resNote < 0) {
					return -1;
				}
				$res	= 1;
			}
		}
		// Copy the InfraSPackPlus public note selection (listnotep), only usable when this parameter is memorized per document
		if (isModEnabled('infraspackplus') && getDolGlobalString('INFRASPLUS_PDF_OPTION_listnotep', '') == 'doc') {
			$txtParamsDocInvoice	= getDolGlobalString('INFRASPLUS_PDF_PARAMS_facture_DOC_'.$id_invoice, '');	// Constant value
			$invoiceParams			= [];
			parse_str($txtParamsDocInvoice, $invoiceParams);	// Convert to associative array
			if (!empty($invoiceParams['listnotep'])) {	// If 'listnotep' exists in the constant, process it
				$notesList		= explode('-', $invoiceParams['listnotep']);
				$filteredNotes	= array();
				foreach ($notesList as $noteKey) {
					if (getDolGlobalString($noteKey, '')) {	// Check the note still exists in Dolibarr
						$filteredNotes[]	= $noteKey;
					}
				}
				if (!empty($filteredNotes)) {
					// Merge with already-existing credit note doc params (preserve other keys like listfreet)
					$txtParamsDocCreditNote			= getDolGlobalString('INFRASPLUS_PDF_PARAMS_facture_DOC_'.$object->id, '');
					$creditNoteParams				= [];
					parse_str($txtParamsDocCreditNote, $creditNoteParams);
					$creditNoteParams['listnotep']	= implode('-', $filteredNotes);
					$newTxtParamsDocCreditNote		= http_build_query($creditNoteParams);
					dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_facture_DOC_'.$object->id, $newTxtParamsDocCreditNote, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
					$res	= 1;
				}
			}
		}
		return $res;
	}

	/**
	*	Copy the chosen freetext mention(s) from the proposal to the invoice. Only mentions whose code is whitelisted in INFRASWORKFLOW_TRANSFER_FREETEXT are transferred, and only if the matching INVOICE_FREE_TEXT_<code> constant exists in Dolibarr.
	*
	*	@param	Facture	$object		The invoice to process
	*	@return	int					< 0 on error, > 0 on success, 0 if nothing to do
	**/
	function infrasworkflow_cloneFreetextFromPropal($object)
	{
		global $conf, $db;

		$allowedCodes	= array_filter(array_map('trim', explode(',', getDolGlobalString('INFRASWORKFLOW_TRANSFER_FREETEXT', ''))));
		if (empty($allowedCodes)) {
			return 0;
		}
		$object->fetchObjectLinked(0, '', $object->id, $object->element);
		if (!empty($object->linkedObjectsIds['propal'])) {
			$id_propal				= reset($object->linkedObjectsIds['propal']);	// Get the value of the first element (not the key)
			$txtParamsDocPropal		= getDolGlobalString('INFRASPLUS_PDF_PARAMS_propal_DOC_'.$id_propal, '');	// Constant value
			$propalParams			= [];
			parse_str($txtParamsDocPropal, $propalParams);	// Convert to associative array
			if (!empty($propalParams['listfreet'])) {	// If 'listfreet' exists in the constant, process it
				$freetextList		= explode('-', $propalParams['listfreet']);
				$filteredFreetext	= [];
				foreach ($freetextList as $freetextKey) {
					// Skip base mention "PROPOSAL_FREE_TEXT" (no code, never whitelisted)
					if (!preg_match('/^PROPOSAL_FREE_TEXT_(.+)$/', $freetextKey, $matches)) {
						continue;
					}
					if (!in_array($matches[1], $allowedCodes, true)) {
						continue;	// Mention code not whitelisted by INFRASWORKFLOW_TRANSFER_FREETEXT
					}
					$convertedFreetext		= 'INVOICE_FREE_TEXT_'.$matches[1];
					if (getDolGlobalString($convertedFreetext, '')) {	// Check if this constant exists in Dolibarr (this mention is also planned for invoices)
						$filteredFreetext[]	= $convertedFreetext;
					}
				}
				if (!empty($filteredFreetext)) {
					// Merge with already-existing invoice doc params (preserve other keys like listnotep)
					$txtParamsDocInvoice		= getDolGlobalString('INFRASPLUS_PDF_PARAMS_facture_DOC_'.$object->id, '');
					$invoiceParams				= [];
					parse_str($txtParamsDocInvoice, $invoiceParams);
					$invoiceParams['listfreet']	= implode('-', $filteredFreetext);
					$newTxtParamsDocInvoice		= http_build_query($invoiceParams);
					dolibarr_set_const($db, 'INFRASPLUS_PDF_PARAMS_facture_DOC_'.$object->id, $newTxtParamsDocInvoice, 'chaine', 0, 'InfraSWorkflow module', $conf->entity);
					return 1;
				}
			}
		}
		return 0;
	}

	/**
	*	Invoice validation function: Classifies invoices as paid if fully paid and classifies associated proposals as invoiced.
	*
	*	@param	Facture	$object		The invoice to process
	*	@return	int					< 0 on error, > 0 on success
	**/
	function infrasworkflow_factureValidation($object)
	{
		global $user, $conf;

		$markInvoiceAsPaidOnFullPayment	= getDolGlobalString('INFRASWORKFLOW_INVOICE_VALIDATION', '');
		if (!empty($markInvoiceAsPaidOnFullPayment)) {
			// Arrondi de chaque composant avant combinaison, pour éviter qu'un résidu sous-centime issu du calcul de lignes non arrondi ne soit compté comme un reste à payer
			$totalpaid			= price2num($object->getSommePaiement(), 'MT');
			$totalcreditnotes	= price2num($object->getSumCreditNotesUsed(), 'MT');
			$totaldeposits		= price2num($object->getSumDepositsUsed(), 'MT');
			$resteapayer		= price2num(price2num($object->total_ttc, 'MT') - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');
			if ($resteapayer == 0) {	// If everything is paid, mark the invoice as paid
				return	$object->setPaid($user);
			}
		}
		if (isModEnabled('propal') && getDolGlobalString('INFRASWORKFLOW_INVOICES_CLASSIFY_BILLED_PROPALS')) {
			$object->fetchObjectLinked(0, 'propal', $object->id, $object->element);
			if (!empty($object->linkedObjects['propal'])) {
				foreach ($object->linkedObjects['propal'] as $element) {	// Browse all quotes linked to the validated invoice
					if ($element->statut != Propal::STATUS_SIGNED) {	// Only signed quotes (drafts and already billed quotes are skipped)
						continue;
					}
					$element->fetchObjectLinked($element->id, $element->element, 0, 'facture');	// Search for all invoices linked to this quote
					if (empty($element->linkedObjects['facture'])) {
						continue;
					}
					$totalInvoiceslinked	= 0;
					$allInvoicesValidated	= true;
					foreach ($element->linkedObjects['facture'] as $facture) {	// Sum the excl. tax total of every invoice linked to the quote
						// The invoice being validated and any already validated/paid invoice count toward the billed amount
						if ($facture->statut == Facture::STATUS_VALIDATED || $facture->statut == Facture::STATUS_CLOSED || $object->id == $facture->id) {
							$totalInvoiceslinked += $facture->total_ht;
						} else {
							$allInvoicesValidated	= false;	// A draft (or otherwise unfinished) invoice exists: do not classify yet
							break;
						}
					}
					dol_syslog('infrasworkflow_factureValidation: quote '.$element->id.' total_ht = '.$element->total_ht.', sum of linked invoices = '.$totalInvoiceslinked.', allInvoicesValidated = '.($allInvoicesValidated ? 1 : 0));
					// If every linked invoice is validated and the excl. tax amounts match (rounded on total): classify the quote as billed
					if ($allInvoicesValidated && infrasworkflow_shouldClassify($conf, $totalInvoiceslinked, $element->total_ht)) {
						$ret	= $element->classifyBilled($user);
						if ($ret < 0) {
							setEventMessages($element->error, $element->errors, 'errors');
						}
					}
				}
			}
		}
		return 1;
	}

	/**
	* Checks if the amounts are equal (rounded to the total amount)
	*
	* @param object		$conf					Dolibarr settings object
	* @param float		$totalonlinkedelements	Sum of total amounts (excl VAT) of invoices linked to $object
	* @param float		$object_total_ht		The total amount (excl VAT) of the object(an order, a proposal, a bill, etc.)
	* @return bool								True if the amounts are equal (rounded on total amount) False otherwise.
	**/
	function infrasworkflow_shouldClassify($conf, $totalonlinkedelements, $object_total_ht)
	{
		// if the configuration allows unmatching amounts, allow classification anyway
		if (getDolGlobalString('WORKFLOW_CLASSIFY_IF_AMOUNTS_ARE_DIFFERENTS', '')) {
			return true;
		}
		// if the amount are same, allow classification, else deny
		return (price2num($totalonlinkedelements, 'MT') == price2num($object_total_ht, 'MT'));
	}
	/**
	 * Vérifie si un extrafield de ligne doit être affiché
	 * @param string $key Clé de l'extrafield
	 * @param array $selected_extrafields Liste des extrafields sélectionnés
	 * @param ExtraFields $extrafields_line Objet extrafields
	 * @param string $table_element_line Nom de la table element (ex: 'contratdet')
	 * @return bool True si l'extrafield doit être affiché
	 */
	function shouldDisplayContractLineExtrafield($key, $selected_extrafields, $extrafields_line, $table_element_line = 'contratdet')
	{
		// Si aucune sélection configurée, ne rien afficher
		if (empty($selected_extrafields)) {
			return false;
		}
		// Sinon, afficher uniquement ceux sélectionnés
		return in_array($key, $selected_extrafields);
	}

	/**
	*	Returns the absolute path to the versioned objectline_create template matching
	*	the current Dolibarr major version, or '' if the file does not exist.
	*
	*	@param		string	$mode	'create' only (other values return '')
	*	@return		string			Absolute file path, or '' if not found
	**/
	function infrasworkflow_pickLineTpl($mode)
	{
		if ($mode !== 'create') {
			return '';
		}
		$major		= (int) DOL_VERSION;
		$dolinfras	= getDolGlobalString('EASYA_VERSION', '') || getDolGlobalString('DOLINFRAS_VERSION', '');
		if ($major >= 24) {
			$tplname	= 'v24.tpl.php';
		} elseif ($major == 23) {
			$tplname	= 'v23.tpl.php';
		} elseif ($major == 22) {
			$candidate	= dol_buildpath('/infrasworkflow/core/tpl/linecreates/v22-DolInfraS.tpl.php', 0);
			$tplname	= ($dolinfras && file_exists($candidate)) ? 'v22-DolInfraS.tpl.php' : 'v22.tpl.php';
		} elseif ($major == 21) {
			$tplname	= 'v21.tpl.php';
		} elseif ($major == 20) {
			$tplname	= 'v20.tpl.php';
		} elseif ($major == 19) {
			$tplname	= 'v19.tpl.php';
		} else {
			$tplname	= 'v18.tpl.php';
		}
		$tpl	= dol_buildpath('/infrasworkflow/core/tpl/linecreates/'.$tplname, 0);
		return file_exists($tpl) ? $tpl : '';
	}

	/**
	*	Retourne les lignes de la commande client qui ne peuvent pas être expédiées : lignes de produit dont la quantité restant à expédier dépasse le stock réel du produit.
	*	Soit parce qu'il n'y a aucun stock, soit parce que le stock est insuffisant pour couvrir la quantité (qté 10 pour un stock de 5).
	*
	*	@param		Commande	$object		Commande client, avec ses lignes déjà chargées
	*	@return		array					array('real' => float, 'virtual' => float) indexé par l'id de ligne (rowid de llx_commandedet)
	**/
	function infrasworkflow_getNotShippableOrderLines($object)
	{
		global $db;

		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$notshippable	= array();
		if (! is_object($object) || empty($object->id) || empty($object->element) || $object->element != 'commande') {
			return $notshippable;
		}
		if (!isModEnabled('stock')) {
			return $notshippable;
		}
		if (empty($object->lines) || ! is_array($object->lines)) {
			return $notshippable;
		}
		$object->loadExpeditions(-1, 0);	// Remplit $object->expeditions[lineid] avec la qté déjà expédiée
		$stockcache	= array();
		foreach ($object->lines as $line) {
			if (empty($line->id) || empty($line->fk_product) || (int) $line->product_type != Product::TYPE_PRODUCT) {
				continue;
			}
			// qty et la qté expédiée viennent de colonnes DOUBLE/REAL : on caste avant tout calcul ou test
			$shipped	= !empty($object->expeditions[$line->id]) ? (float) $object->expeditions[$line->id] : 0;
			$remaining	= (float) $line->qty - $shipped;
			if ($remaining <= 0) {
				continue;	// Plus rien à expédier sur cette ligne
			}
			$fk_product	= (int) $line->fk_product;
			if (!isset($stockcache[$fk_product])) {
				// Mise en cache par produit : on ne calcule le stock qu'une seule fois même si le produit apparaît sur plusieurs lignes
				$product		= new Product($db);
				$stockreel		= 0;
				$stockvirtual	= 0;
				if ($product->fetch($fk_product) > 0) {
					if ($product->load_stock('nobatch') >= 0) {
						$stockreel		= (float) $product->stock_reel;	// Stock physiquement présent en entrepôt
					}
					if ($product->load_virtual_stock() >= 0) {
						$stockvirtual	= (float) $product->stock_theorique;	// Stock théorique : tient compte des commandes fournisseurs/clients en cours selon la config
					}
				}
				$stockcache[$fk_product]	= array('real' => $stockreel, 'virtual' => $stockvirtual);
			}
			// Le critère de non-expédiabilité reste basé sur le stock réel (physique), le stock virtuel n'est qu'une information complémentaire
			if ($remaining > $stockcache[$fk_product]['real']) {
				$notshippable[(int) $line->id]	= $stockcache[$fk_product];
			}
		}
		return $notshippable;
	}

	/**
	*	Returns true if the product lines of a contract need rank normalisation
	*	(NULL ranks, duplicates, or non-sequential sequence detected).
	*
	*	@param	Contrat		$object		Contract object (with ->lines already loaded)
	*	@return	bool
	**/
	function infrasworkflow_needRankNormalization($object)
	{
		if (empty($object->lines)) {
			return false;
		}
		$productLines	= array();
		foreach ($object->lines as $line) {
			if ((int) $line->product_type === 0) {
				$productLines[]	= $line;
			}
		}
		if (count($productLines) <= 1) {
			return false;
		}
		$rangs	= array();
		foreach ($productLines as $line) {
			if (empty($line->rang) || $line->rang <= 0) {
				return true;
			}
			if (in_array($line->rang, $rangs)) {
				return true;
			}
			$rangs[]	= $line->rang;
		}
		sort($rangs, SORT_NUMERIC);
		for ($i = 0; $i < count($rangs); $i++) {
			if ($rangs[$i] != ($i + 1)) {
				return true;
			}
		}
		return false;
	}

	/**
	*	Resequences product-line ranks of a contract as 1, 2, 3 …
	*
	*	@param	int		$contractId		Contract rowid
	*	@return	bool					True on success, false on DB error
	**/
	function infrasworkflow_normalizeContractProductRanks($contractId)
	{
		global $db;

		$db->begin();

		$sql	= 'SELECT rowid FROM '.$db->prefix().'contratdet';
		$sql	.= ' WHERE fk_contrat = '.(int) $contractId;
		$sql	.= ' AND product_type = 0';
		$sql	.= ' ORDER BY CASE WHEN rang IS NULL THEN 999999 WHEN rang = 0 THEN 999998 ELSE rang END ASC, rowid ASC';

		$resql	= $db->query($sql);
		if (!$resql) {
			$db->rollback();
			dol_syslog('infrasworkflow_normalizeContractProductRanks: '.$db->lasterror(), LOG_ERR);
			return false;
		}

		$rang	= 1;
		while ($obj = $db->fetch_object($resql)) {
			$sqlupd	= 'UPDATE '.$db->prefix().'contratdet SET rang = '.(int) $rang.' WHERE rowid = '.(int) $obj->rowid;
			if (!$db->query($sqlupd)) {
				$db->rollback();
				dol_syslog('infrasworkflow_normalizeContractProductRanks: '.$db->lasterror(), LOG_ERR);
				return false;
			}
			$rang++;
		}

		$db->commit();
		return true;
	}

	/**
	*	Nettoye les lignes de prix fournisseur en double avant la fusion de deux tiers. Les lignes du tiers d'origine sont supprimées si une ligne identique existe déjà pour le tiers de destination.
	*
	*	@param		int		$dest_id		Id du tiers de destination (conservé)
	*	@param		int		$origin_id		Id du tiers d'origine (supprimé)
	*	@return		int					Nombre de lignes supprimées, ou -1 en cas d'erreur
	**/
	function infrasworkflow_cleanDuplicateSupplierPricesBeforeMerge($dest_id, $origin_id)
	{
		global $db;

		$dest_id	= (int) $dest_id;
		$origin_id	= (int) $origin_id;
		if ($dest_id <= 0 || $origin_id <= 0 || $dest_id == $origin_id) {
			return 0;
		}

		$sql	= 'DELETE p FROM '.$db->prefix().'product_fournisseur_price AS p';
		$sql	.= ' INNER JOIN '.$db->prefix().'product_fournisseur_price AS k';
		$sql	.= ' ON k.ref_fourn = p.ref_fourn AND k.quantity = p.quantity AND k.entity = p.entity';
		$sql	.= ' WHERE p.fk_soc = '.$origin_id;
		$sql	.= ' AND k.fk_soc = '.$dest_id;

		$resql	= $db->query($sql);
		if (!$resql) {
			dol_syslog('infrasworkflow_cleanDuplicateSupplierPricesBeforeMerge: '.$db->lasterror(), LOG_ERR);
			return -1;
		}

		return $db->affected_rows($resql);
	}
	/**
	*	Renvoie les totaux du contrat calculés uniquement sur les lignes de service. Additionne les lignes déjà chargées si disponibles, sinon effectue la somme directement dans la base de données.
	*
	*	@param	Contrat		$object		Contrat dont on veut calculer les totaux des lignes de service
	*	@return	array					array('total_ht' => float, 'total_tva' => float, 'total_ttc' => float)
	**/
	function infrasworkflow_getContractServicesTotals($object)
	{
		global $db;
		$totals	= array('total_ht' => 0, 'total_tva' => 0, 'total_ttc' => 0);
		if (empty($object) || empty($object->id)) {
			return $totals;
		}
		if (!empty($object->lines) && is_array($object->lines)) {
			foreach ($object->lines as $line) {
				// Loose comparison on purpose : same test as the InfraSPlus contract PDF models, it also
				// discards free lines whose catalog type is NULL
				if ($line->product_type == Product::TYPE_PRODUCT) {
					continue;
				}
				$totals['total_ht']		+= (float) $line->total_ht;
				$totals['total_tva']	+= (float) $line->total_tva;
				$totals['total_ttc']	+= (float) $line->total_ttc;
			}
		} else {
			$sql	= 'SELECT SUM(d.total_ht) as total_ht, SUM(d.total_tva) as total_tva, SUM(d.total_ttc) as total_ttc';
			$sql	.= ' FROM '.$db->prefix().'contratdet as d';
			$sql	.= ' LEFT JOIN '.$db->prefix().'product as p ON p.rowid = d.fk_product';
			$sql	.= ' WHERE d.fk_contrat = '.(int) $object->id;
			$sql	.= ' AND COALESCE(p.fk_product_type, 0) <> '.Product::TYPE_PRODUCT;
			$resql	= $db->query($sql);
			if (!$resql) {
				dol_syslog('infrasworkflow_getContractServicesTotals: '.$db->lasterror(), LOG_ERR);
				return $totals;
			}
			if ($obj = $db->fetch_object($resql)) {
				$totals['total_ht']		= (float) $obj->total_ht;
				$totals['total_tva']	= (float) $obj->total_tva;
				$totals['total_ttc']	= (float) $obj->total_ttc;
			}
			$db->free($resql);
		}
		$totals['total_ht']		= (float) price2num($totals['total_ht'], 'MT');
		$totals['total_tva']	= (float) price2num($totals['total_tva'], 'MT');
		$totals['total_ttc']	= (float) price2num($totals['total_ttc'], 'MT');
		return $totals;
	}

	/**
	*	Force le type de ligne produit à 0 (produit) sur une ligne de contrat qui pointe vers un produit du catalogue.
	*
	*	@param	int		$lineid		Ligne de contrat à retyper
	*	@return	int					1 = ligne retypée, 0 = rien à faire, -1 = erreur BD
	**/
	function infrasworkflow_forceContractProductLineType($lineid)
	{
		global $db;
		$lineid	= (int) $lineid;
		if ($lineid <= 0) {
			return 0;
		}
		$sql	= 'SELECT d.rowid FROM '.$db->prefix().'contratdet as d';
		$sql	.= ' INNER JOIN '.$db->prefix().'product as p ON p.rowid = d.fk_product';
		$sql	.= ' WHERE d.rowid = '.$lineid;
		$sql	.= ' AND d.product_type <> 0';
		$sql	.= ' AND p.fk_product_type = 0';
		$resql	= $db->query($sql);
		if (!$resql) {
			dol_syslog('infrasworkflow_forceContractProductLineType: '.$db->lasterror(), LOG_ERR);
			return -1;
		}
		$tocorrect	= ($db->num_rows($resql) > 0);
		$db->free($resql);
		if (!$tocorrect) {
			return 0;
		}
		if (!$db->query('UPDATE '.$db->prefix().'contratdet SET product_type = 0 WHERE rowid = '.$lineid)) {
			dol_syslog('infrasworkflow_forceContractProductLineType: '.$db->lasterror(), LOG_ERR);
			return -1;
		}
		dol_syslog('infrasworkflow_forceContractProductLineType: contract line '.$lineid.' retyped as product', LOG_DEBUG);
		return 1;
	}

	/**
	*	Renvoie un identifiant de catégorie suivi de tous les identifiants de ses sous-catégories.
	*
	*	@param	int		$catid		Identifiant de la catégorie
	*	@return	array				Tableau des identifiants de catégories (la catégorie elle-même en premier)
	**/
	function infrasworkflow_getCategoryWithChildrenIds($catid)
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$ids	= array((int) $catid);
		$cat	= new Categorie($db);
		if ($cat->fetch((int) $catid) > 0) {
			$children	= $cat->get_filles();
			if (is_array($children)) {
				foreach ($children as $child) {
					$ids	= array_merge($ids, infrasworkflow_getCategoryWithChildrenIds($child->id));
				}
			}
		}
		return array_values(array_unique($ids));
	}

	/**
	*	Les produits à exclure de l'inventaire lorsque l'option : INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE est activée : produits ni à la vente ni à l'achat,
	*	Produits associés à la catégorie « obsolète » (INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY) ou à l'une de ses sous-catégories.
	*
	*	@param	string	$alias		SQL alias of the product table (default = 'p')
	*	@return	string				Condition SQL pour exclure les produits masqués de l'inventaire
	**/
	function infrasworkflow_inventoryHiddenProductsCondition($alias = 'p')
	{
		global $db;

		if (!getDolGlobalInt('INFRASWORKFLOW_HIDE_ITEMS_TAGGED_OBSOLETE', 0)) {
			return '';
		}
		$conditions	= array('('.$alias.'.tosell = 0 AND '.$alias.'.tobuy = 0)');
		$catid		= getDolGlobalInt('INFRASWORKFLOW_INVENTORY_OBSOLETE_CATEGORY', 0);
		if ($catid > 0) {
			$catids			= infrasworkflow_getCategoryWithChildrenIds($catid);
			$conditions[]	= $alias.'.rowid IN (SELECT cp.fk_product FROM '.$db->prefix().'categorie_product as cp WHERE cp.fk_categorie IN ('.$db->sanitize(implode(',', $catids)).'))';
		}
		return '('.implode(' OR ', $conditions).')';
	}

	/**
	*	Purge les lignes d'inventaire correspondant aux produits masqués (voir infrasworkflow_inventoryHiddenProductsCondition()).
	*
	*	@param	Inventory	$inventory		Inventory being validated (started)
	*	@return	int							Number of lines removed, -1 = DB error
	**/
	function infrasworkflow_inventoryPurgeHiddenLines($inventory)
	{
		global $db;

		$condition	= infrasworkflow_inventoryHiddenProductsCondition('p');
		if (empty($condition) || empty($inventory->id)) {
			return 0;
		}
		$sql	= 'DELETE FROM '.$db->prefix().'inventorydet WHERE fk_inventory = '.((int) $inventory->id);
		$sql	.= ' AND fk_product IN (SELECT p.rowid FROM '.$db->prefix().'product as p WHERE '.$condition.')';
		$resql	= $db->query($sql);
		if (!$resql) {
			dol_syslog('infrasworkflow_inventoryPurgeHiddenLines: '.$db->lasterror(), LOG_ERR);
			return -1;
		}
		$nb	= (int) $db->affected_rows($resql);
		dol_syslog('infrasworkflow_inventoryPurgeHiddenLines: inventory '.$inventory->id.', '.$nb.' line(s) removed', LOG_DEBUG);
		return $nb;
	}

	/**
	*	Ajoute des lignes d'inventaire pour les produits stock à 0 dans l'entrepôt inventorié, en respectant les filtres de l'inventaire et en excluant les produits masqués.
	*
	*	@param	Inventory	$inventory				Inventaire en cours de validation
	*	@param	User		$user					Utilisateur effectuant la validation
	*	@param	int			$include_sub_warehouse	1 = ajouter également des lignes pour les sous-entrepôts (case à cocher de la boîte de dialogue de validation)
	*	@return	int									Nombre de lignes ajoutées, -1 = erreur
	**/
	function infrasworkflow_inventoryAddEmptyStockLines($inventory, $user, $include_sub_warehouse = 0)
	{
		global $db;

		if (!getDolGlobalInt('INFRASWORKFLOW_DISPLAY_SORTED_EMPTY_STOCK', 0) || empty($inventory->id)) {
			return 0;
		}
		include_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
		// Liste des entrepôts concernés par l'inventaire. (vide = entrepôt par défaut de chaque produit)
		$warehouses	= array();
		if ($inventory->fk_warehouse > 0) {
			$warehouses[]	= (int) $inventory->fk_warehouse;
			// On récupère récursivement tous les entrepôts enfants pour les ajouter à la liste.
			if (!empty($include_sub_warehouse) && getDolGlobalInt('INVENTORY_INCLUDE_SUB_WAREHOUSE')) {
				$children	= array();
				$inventory->getChildWarehouse($inventory->fk_warehouse, $children);
				foreach ($children as $childid) {
					$warehouses[]	= (int) $childid;
				}
			}
		}
		// On récupère les lignes déjà existantes pour cet inventaire (créées par Inventory::validate())
		$existing	= array();
		$sql		= 'SELECT fk_warehouse, fk_product FROM '.$db->prefix().'inventorydet WHERE fk_inventory = '.((int) $inventory->id);
		$resql		= $db->query($sql);
		if (!$resql) {
			dol_syslog('infrasworkflow_inventoryAddEmptyStockLines: '.$db->lasterror(), LOG_ERR);
			return -1;
		}
		while ($obj = $db->fetch_object($resql)) {
			$existing[(int) $obj->fk_warehouse][(int) $obj->fk_product]	= 1;
		}
		$db->free($resql);
		// Construction de la liste des produits "candidats" : inclure également les produits qui sont en stock 0.
		$sql	= 'SELECT p.rowid, p.fk_default_warehouse FROM '.$db->prefix().'product as p';
		$sql	.= ' WHERE p.entity IN ('.getEntity('product').')';
		// Sauf configuration contraire, on ne prend que les produits (type=0), pas les services.
		if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			$sql	.= ' AND p.fk_product_type = 0';
		}
		if ($inventory->fk_product > 0) {
			$sql	.= ' AND p.rowid = '.((int) $inventory->fk_product);
		}
		// Si l'inventaire est limité à certaines catégories de produits, on filtre via une sous-requête EXISTS.
		if (!empty($inventory->categories_product)) {
			$sql	.= ' AND EXISTS (SELECT cp.fk_product FROM '.$db->prefix().'categorie_product as cp WHERE cp.fk_product = p.rowid AND cp.fk_categorie IN ('.$db->sanitize($inventory->categories_product).'))';
		}
		// Si le module Nomenclatures est actif, on exclut les produits parents pour ne garder que les produits "feuilles".
		if (getDolGlobalInt('PRODUIT_SOUSPRODUITS')) {
			$sql	.= ' AND NOT EXISTS (SELECT pa.rowid FROM '.$db->prefix().'product_association as pa WHERE pa.fk_product_pere = p.rowid)';
		}
		// Si la gestion des lots/numéros de série est active, on exclut les produits suivis par lot
		if (isModEnabled('productbatch')) {
			$sql	.= ' AND (p.tobatch IS NULL OR p.tobatch = 0)';
		}
		// Masquage des produits obsolètes (ni à la vente ni à l'achat, ou associés à la catégorie "obsolète" ou à l'une de ses sous-catégories)
		$hidden		= infrasworkflow_inventoryHiddenProductsCondition('p');
		if (!empty($hidden)) {
			$sql	.= ' AND NOT '.$hidden;
		}
		$sql		.= ' ORDER BY p.rowid';
		$resql		= $db->query($sql);
		if (!$resql) {
			dol_syslog('infrasworkflow_inventoryAddEmptyStockLines: '.$db->lasterror(), LOG_ERR);
			return -1;
		}
		$nb		= 0;
		$now	= dol_now();
		while ($obj = $db->fetch_object($resql)) {
			$targets	= $warehouses;
			// Si entrepôt cible vide, on utilise l'entrepôt par défaut du produit
			if (empty($targets)) {
				if ($obj->fk_default_warehouse > 0) {
					$targets[]	= (int) $obj->fk_default_warehouse;
				}
			}
			foreach ($targets as $fk_warehouse) {
				if (!empty($existing[$fk_warehouse][(int) $obj->rowid])) {
					continue;
				}
				$line					= new InventoryLine($db);
				$line->fk_inventory		= $inventory->id;
				$line->fk_warehouse		= $fk_warehouse;
				$line->fk_product		= (int) $obj->rowid;
				$line->batch			= '';
				$line->datec			= $now;
				$line->qty_stock		= 0;
				$res					= $line->create($user);
				if ($res <= 0) {
					dol_syslog('infrasworkflow_inventoryAddEmptyStockLines: '.$line->error, LOG_ERR);
					$db->free($resql);
					return -1;
				}
				// On marque ce couple entrepôt/produit comme "déjà traité" pour éviter tout doublon
				$existing[$fk_warehouse][(int) $obj->rowid]	= 1;
				$nb++;
			}
		}
		$db->free($resql);
		dol_syslog('infrasworkflow_inventoryAddEmptyStockLines: inventory '.$inventory->id.', '.$nb.' line(s) added', LOG_DEBUG);
		return $nb;
	}

	/**
	*	Zone de l'inventaire : colonne "Zone" de l'inventaire : parties SQL à ajouter à la requête des lignes et paramètres d'affichage.
	*
	*	@return	array	array('source' => 'extrafield' | 'category' | '' (column disabled),
	*						'select' => SQL fields to append (column aliased infras_zone), 'join' => SQL join to append,
	*						'sortfield' => sort field of the column, 'extrafield' => extrafield code)
	**/
	function infrasworkflow_inventoryZoneSqlParts()
	{
		global $db;

		$parts	= array('source' => '', 'select' => '', 'join' => '', 'sortfield' => '', 'extrafield' => '');
		if (!getDolGlobalInt('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 0)) {
			return $parts;
		}
		// The two sources are exclusive (see the setup page) : if both are set anyway, the parent category is the one kept
		$catid	= getDolGlobalInt('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 0);
		if ($catid > 0) {
			$parts['source']	= 'category';
			$parts['select']	= ', zc.infras_zone';
			$parts['join']		= ' LEFT JOIN (SELECT cp.fk_product, MIN(c.label) as infras_zone FROM '.$db->prefix().'categorie_product as cp';
			$parts['join']		.= ' INNER JOIN '.$db->prefix().'categorie as c ON c.rowid = cp.fk_categorie WHERE c.fk_parent = '.((int) $catid).' GROUP BY cp.fk_product) as zc ON zc.fk_product = p.rowid';
			$parts['sortfield']	= 'zc.infras_zone';
			return $parts;
		}
		$exf	= getDolGlobalString('INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', '');
		if (!empty($exf) && preg_match('/^[a-z0-9_]+$/i', $exf)) {
			$extrafields	= new ExtraFields($db);
			$extrafields->fetch_name_optionals_label('product');
			if (isset($extrafields->attributes['product']['label'][$exf])) {
				$parts['source']		= 'extrafield';
				$parts['extrafield']	= $exf;
				$parts['select']		= ', pe.'.$db->sanitize($exf).' as infras_zone';
				$parts['join']			= ' LEFT JOIN '.$db->prefix().'product_extrafields as pe ON pe.fk_object = p.rowid';
				$parts['sortfield']		= 'pe.'.$exf;
			}
		}
		return $parts;
	}

	/**
	*	Colonne "Zone" de l'inventaire : valeur HTML de la zone pour une ligne d'inventaire.
	*
	*	@param	array		$parts			Résultat de infrasworkflow_inventoryZoneSqlParts()
	*	@param	object		$obj			Ligne de la requête SQL incluant le champ infras_zone
	*	@param	ExtraFields	$extrafields	Objet ExtraFields avec les définitions des produits chargées (fetch_name_optionals_label('product'))
	*	@return	string						HTML
	**/
	function infrasworkflow_inventoryZoneOutput($parts, $obj, $extrafields)
	{
		if (empty($parts['source']) || !isset($obj->infras_zone) || $obj->infras_zone === '' || $obj->infras_zone === null) {
			return '';
		}
		if ($parts['source'] == 'extrafield') {
			return $extrafields->showOutputField($parts['extrafield'], $obj->infras_zone, '', 'product');
		}
		return dol_escape_htmltag($obj->infras_zone);
	}

	/**
	*	Colonne "Zone" de l'inventaire : liste des zones de localisation, c'est à dire les sous-catégories directes de la catégorie
	*	parente configurée (INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY), triées par libellé.
	*
	*	@return	array	array(id de la sous-catégorie => libellé), vide si l'option n'est pas configurée
	**/
	function infrasworkflow_inventoryZoneCategories()
	{
		global $db;

		$list	= array();
		$catid	= getDolGlobalInt('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 0);
		if ($catid <= 0) {
			return $list;
		}
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$parent	= new Categorie($db);
		if ($parent->fetch($catid) <= 0) {
			return $list;
		}
		// On ne prend que les sous-catégories directes de la catégorie parente, pas les sous-sous-catégories
		$children	= $parent->get_filles();
		if (is_array($children)) {
			foreach ($children as $child) {
				$list[$child->id]	= $child->label;
			}
		}
		asort($list);
		return $list;
	}

	/**
	*	Colonne "Zone" de l'inventaire : champ de saisie de la zone sur la ligne d'ajout d'une ligne d'inventaire.
	*	La zone est portée par le produit et non par la ligne d'inventaire : la valeur saisie est appliquée au produit par
	*	infrasworkflow_inventorySetProductZone() une fois la ligne créée.
	*
	*	@param	array		$parts			Résultat de infrasworkflow_inventoryZoneSqlParts()
	*	@param	ExtraFields	$extrafields	Objet ExtraFields avec les définitions des produits chargées (fetch_name_optionals_label('product'))
	*	@return	string						HTML du champ de saisie, chaîne vide si la colonne est désactivée
	**/
	function infrasworkflow_inventoryZoneInput($parts, $extrafields)
	{
		global $db;

		if (empty($parts['source'])) {
			return '';
		}
		if ($parts['source'] == 'extrafield') {
			$postname	= 'infraszone_options_'.$parts['extrafield'];
			$value		= GETPOSTISSET($postname) ? GETPOST($postname, 'alphanohtml') : '';
			return $extrafields->showInputField($parts['extrafield'], $value, '', '', 'infraszone_', 'maxwidth150', 0, 'product', 0);
		}
		$form	= new Form($db);
		return $form->selectarray('infraszone_category', infrasworkflow_inventoryZoneCategories(), GETPOSTINT('infraszone_category'), 1, 0, 0, '', 0, 0, 0, '', 'maxwidth150');
	}

	/**
	*	Colonne "Zone" de l'inventaire : applique au produit la zone saisie sur la ligne d'ajout d'une ligne d'inventaire.
	*	Source attribut supplémentaire : la valeur est écrite dans l'attribut du produit (sans trigger, pour ne pas déclencher
	*	le contrôle de validation produit du module depuis une saisie d'inventaire).
	*	Source catégorie : le produit est rattaché à la sous-catégorie choisie et détaché des autres sous-catégories de la
	*	catégorie parente (la colonne n'affiche qu'une zone par produit).
	*	Une saisie vide ne modifie rien.
	*
	*	@param	array	$parts			Résultat de infrasworkflow_inventoryZoneSqlParts()
	*	@param	int		$fk_product		Id du produit de la ligne créée
	*	@param	User	$user			Utilisateur qui effectue l'action
	*	@return	int						1 = zone appliquée, 0 = rien à faire, -1 = erreur
	**/
	function infrasworkflow_inventorySetProductZone($parts, $fk_product, $user)
	{
		global $db, $langs;

		if (empty($parts['source']) || $fk_product <= 0) {
			return 0;
		}
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		$product	= new Product($db);
		if ($product->fetch((int) $fk_product) <= 0) {
			return 0;
		}
		if ($parts['source'] == 'extrafield') {
			$extrafields	= new ExtraFields($db);
			$extrafields->fetch_name_optionals_label('product');
			$type			= isset($extrafields->attributes['product']['type'][$parts['extrafield']]) ? $extrafields->attributes['product']['type'][$parts['extrafield']] : '';
			$value			= GETPOST('infraszone_options_'.$parts['extrafield'], 'alphanohtml');
			// Empty choice : '' for a text field, 0 for a select / sellist (see ExtraFields::showInputField())
			if ($value === '' || $value === null || (in_array($type, array('select', 'sellist')) && (string) $value === '0')) {
				return 0;
			}
			$product->array_options['options_'.$parts['extrafield']]	= $value;
			if ($product->updateExtraField($parts['extrafield'], null, $user) < 0) {
				setEventMessages($product->error, $product->errors, 'errors');
				return -1;
			}
			setEventMessages($langs->trans('InfraSWorkflowInventoryZoneProductUpdated', $product->ref), null, 'mesgs');
			return 1;
		}
		$catid	= GETPOSTINT('infraszone_category');
		$zones	= infrasworkflow_inventoryZoneCategories();
		if ($catid <= 0 || !isset($zones[$catid])) {	// Not a zone of the configured parent category
			return 0;
		}
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$db->begin();
		foreach (array_keys($zones) as $zoneid) {
			$zone	= new Categorie($db);
			if ($zone->fetch((int) $zoneid) <= 0) {
				continue;
			}
			$contains	= $zone->containsObject('product', $product->id);
			if ((int) $zoneid == $catid) {
				if ($contains > 0) {
					continue;	// The product is already in the selected zone
				}
				if ($zone->add_type($product, 'product') < 0) {
					setEventMessages($zone->error, $zone->errors, 'errors');
					$db->rollback();
					return -1;
				}
			} elseif ($contains > 0) {
				if ($zone->del_type($product, 'product') < 0) {
					setEventMessages($zone->error, $zone->errors, 'errors');
					$db->rollback();
					return -1;
				}
			}
		}
		$db->commit();
		setEventMessages($langs->trans('InfraSWorkflowInventoryZoneProductUpdated', $product->ref), null, 'mesgs');
		return 1;
	}
