<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrastructure/core/lib/infrastructureMigrateSubtotal.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Migration des données (constantes, extrafields, dictionnaire)
	*				depuis le module subtotal vers le module infrastructure,
	*				ainsi que le nettoyage des résidus subtotal.
	************************************************/

	/**
	*	Exécute la migration subtotal → infrastructure (une transaction globale).
	*
	*	@param		DoliDB		$db			Handler BDD
	*	@param		Conf		$conf		Configuration
	*	@param		boolean		$dryRun		Si vrai, rollback final (simulation)
	*	@param		callable	$logger		Callable optionnel logger(string $msg)
	*	@return		array					['success'=>bool, 'errors'=>string[]]
	**/
	function infrastructure_migrateFromSubtotal($db, $conf, $dryRun = true, $logger = null)
	{
		$result		= array('success' => true, 'errors' => array());
		$entity		= (int) $conf->entity;
		$error		= 0;

		$log	= function ($m) use ($logger) {
			if (is_callable($logger)) {
				call_user_func($logger, $m);
			}
		};

		$db->begin();

		// 1) Constantes llx_const ************
		$log('[1/3] Constantes llx_const');
		$sqlSel	= 'SELECT rowid, name, entity, value FROM '.MAIN_DB_PREFIX.'const'
				.' WHERE name LIKE \'SUBTOTAL\\_%\''
				.' OR name = \'NO_TITLE_SHOW_ON_EXPED_GENERATION\'';
		$resql	= $db->query($sqlSel);
		if (! $resql) {
			$msg				= 'Erreur lecture llx_const : '.$db->lasterror();
			$result['errors'][]	= $msg;
			$log('  '.$msg);
			$error++;
		} else {
			while ($obj = $db->fetch_object($resql)) {
				$oldName	= $obj->name;
				if ($oldName === 'NO_TITLE_SHOW_ON_EXPED_GENERATION') {
					$newName	= 'INFRASTRUCTURE_NO_TITLE_SHOW_ON_EXPED_GENERATION';
				} else {
					$newName	= str_replace('SUBTOTAL', 'INFRASTRUCTURE', $oldName);
				}

				$sqlCheck	= 'SELECT rowid FROM '.MAIN_DB_PREFIX.'const'
							.' WHERE name = \''.$db->escape($newName).'\''
							.' AND entity = '.((int) $obj->entity);
				$resCheck	= $db->query($sqlCheck);
				$exists		= ($resCheck && $db->num_rows($resCheck) > 0);
				if ($resCheck) {
					$db->free($resCheck);
				}

				if ($exists) {
					$log('  - '.$oldName.' (entity='.$obj->entity.') → '.$newName.' DÉJÀ PRÉSENTE → suppression');
					$sqlDel		= 'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE rowid = '.((int) $obj->rowid);
					if (! $db->query($sqlDel)) {
						$msg				= 'Erreur suppression const '.$oldName.' : '.$db->lasterror();
						$result['errors'][]	= $msg;
						$log('    '.$msg);
						$error++;
						break;
					}
				} else {
					$log('  - '.$oldName.' (entity='.$obj->entity.') → '.$newName);
					$sqlUpd		= 'UPDATE '.MAIN_DB_PREFIX.'const'
								.' SET name = \''.$db->escape($newName).'\''
								.' WHERE rowid = '.((int) $obj->rowid);
					if (! $db->query($sqlUpd)) {
						$msg				= 'Erreur update const '.$oldName.' : '.$db->lasterror();
						$result['errors'][]	= $msg;
						$log('    '.$msg);
						$error++;
						break;
					}
				}
			}
			$db->free($resql);
		}

		// 2) ExtraField subtotal_show_qty → infrastructure_show_qty
		if (! $error) {
			$log('[2/3] ExtraField subtotal_show_qty → infrastructure_show_qty');
			$TElementType		= array('propaldet', 'commandedet', 'facturedet', 'supplier_proposaldet', 'commande_fournisseurdet', 'facture_fourn_det');
			$TTablePerElement	= array(
				'propaldet'					=> MAIN_DB_PREFIX.'propaldet_extrafields',
				'commandedet'				=> MAIN_DB_PREFIX.'commandedet_extrafields',
				'facturedet'				=> MAIN_DB_PREFIX.'facturedet_extrafields',
				'supplier_proposaldet'		=> MAIN_DB_PREFIX.'supplier_proposaldet_extrafields',
				'commande_fournisseurdet'	=> MAIN_DB_PREFIX.'commande_fournisseurdet_extrafields',
				'facture_fourn_det'			=> MAIN_DB_PREFIX.'facture_fourn_det_extrafields',
			);

			foreach ($TElementType as $elementtype) {
				if ($error) {
					break;
				}
				$log('  Element : '.$elementtype);

				$sqlSel		= 'SELECT rowid, name FROM '.MAIN_DB_PREFIX.'extrafields'
							.' WHERE elementtype = \''.$db->escape($elementtype).'\''
							.' AND name IN (\'subtotal_show_qty\', \'infrastructure_show_qty\')'
							.' AND entity IN (0, '.$entity.')';
				$resEF		= $db->query($sqlSel);
				$oldEF		= null;
				$newEF		= null;
				if ($resEF) {
					while ($ef = $db->fetch_object($resEF)) {
						if ($ef->name === 'subtotal_show_qty') {
							$oldEF	= $ef;
						} elseif ($ef->name === 'infrastructure_show_qty') {
							$newEF	= $ef;
						}
					}
					$db->free($resEF);
				} else {
					$msg				= 'Erreur lecture llx_extrafields ('.$elementtype.') : '.$db->lasterror();
					$result['errors'][]	= $msg;
					$log('    '.$msg);
					$error++;
					break;
				}

				if ($oldEF !== null) {
					if ($newEF !== null) {
						$log('    - llx_extrafields : cible déjà présente → DELETE subtotal_show_qty');
						$sqlDel		= 'DELETE FROM '.MAIN_DB_PREFIX.'extrafields WHERE rowid = '.((int) $oldEF->rowid);
						if (! $db->query($sqlDel)) {
							$msg				= 'Erreur DELETE extrafield '.$elementtype.' : '.$db->lasterror();
							$result['errors'][]	= $msg;
							$log('      '.$msg);
							$error++;
							break;
						}
					} else {
						$log('    - llx_extrafields : rename subtotal_show_qty → infrastructure_show_qty');
						$sqlUpd		= 'UPDATE '.MAIN_DB_PREFIX.'extrafields SET name = \'infrastructure_show_qty\' WHERE rowid = '.((int) $oldEF->rowid);
						if (! $db->query($sqlUpd)) {
							$msg				= 'Erreur UPDATE extrafield '.$elementtype.' : '.$db->lasterror();
							$result['errors'][]	= $msg;
							$log('      '.$msg);
							$error++;
							break;
						}
					}
				}

				$table		= $TTablePerElement[$elementtype];

				$sqlCol		= 'SHOW COLUMNS FROM '.$table.' LIKE \'subtotal\\_show\\_qty\'';
				$resCol		= $db->query($sqlCol);
				$hasOld		= ($resCol && $db->num_rows($resCol) > 0);
				if ($resCol) {
					$db->free($resCol);
				}

				$sqlCol2	= 'SHOW COLUMNS FROM '.$table.' LIKE \'infrastructure\\_show\\_qty\'';
				$resCol2	= $db->query($sqlCol2);
				$hasNew		= ($resCol2 && $db->num_rows($resCol2) > 0);
				if ($resCol2) {
					$db->free($resCol2);
				}

				if ($hasOld && ! $hasNew) {
					$log('    - '.$table.' : CHANGE COLUMN subtotal_show_qty → infrastructure_show_qty');
					$sqlAlt		= 'ALTER TABLE '.$table.' CHANGE COLUMN subtotal_show_qty infrastructure_show_qty INT DEFAULT NULL';
					if (! $db->query($sqlAlt)) {
						$msg				= 'Erreur ALTER '.$table.' : '.$db->lasterror();
						$result['errors'][]	= $msg;
						$log('      '.$msg);
						$error++;
						break;
					}
				} elseif ($hasOld && $hasNew) {
					$log('    - '.$table.' : les deux colonnes existent → copie puis DROP');
					$sqlCopy	= 'UPDATE '.$table.' SET infrastructure_show_qty = subtotal_show_qty'
								.' WHERE infrastructure_show_qty IS NULL AND subtotal_show_qty IS NOT NULL';
					if (! $db->query($sqlCopy)) {
						$msg				= 'Erreur UPDATE '.$table.' : '.$db->lasterror();
						$result['errors'][]	= $msg;
						$log('      '.$msg);
						$error++;
						break;
					}
					$sqlDrop	= 'ALTER TABLE '.$table.' DROP COLUMN subtotal_show_qty';
					if (! $db->query($sqlDrop)) {
						$msg				= 'Erreur DROP '.$table.' : '.$db->lasterror();
						$result['errors'][]	= $msg;
						$log('      '.$msg);
						$error++;
						break;
					}
				}
			}
		}

		// 3) Dictionnaire c_subtotal_free_text → c_infrastructure_free_text
		if (! $error) {
			$log('[3/3] Dictionnaire c_subtotal_free_text → c_infrastructure_free_text');
			$srcTable	= MAIN_DB_PREFIX.'c_subtotal_free_text';
			$dstTable	= MAIN_DB_PREFIX.'c_infrastructure_free_text';

			$sqlCheck	= 'SHOW TABLES LIKE \''.$db->escape($srcTable).'\'';
			$resChk		= $db->query($sqlCheck);
			$hasSrc		= ($resChk && $db->num_rows($resChk) > 0);
			if ($resChk) {
				$db->free($resChk);
			}

			if (! $hasSrc) {
				$log('  Table '.$srcTable.' absente — aucune donnée à migrer');
			} else {
				$sqlSel		= 'SELECT rowid, label, content, active, entity FROM '.$srcTable;
				$resSrc		= $db->query($sqlSel);
				if (! $resSrc) {
					$msg				= 'Erreur lecture '.$srcTable.' : '.$db->lasterror();
					$result['errors'][]	= $msg;
					$log('  '.$msg);
					$error++;
				} else {
					while ($row = $db->fetch_object($resSrc)) {
						$sqlExist	= 'SELECT rowid FROM '.$dstTable
									.' WHERE label = \''.$db->escape($row->label).'\''
									.' AND entity = '.((int) $row->entity);
						$resExist	= $db->query($sqlExist);
						$alreadyIn	= ($resExist && $db->num_rows($resExist) > 0);
						if ($resExist) {
							$db->free($resExist);
						}

						if ($alreadyIn) {
							$log('  - SKIP label="'.$row->label.'" (entity='.$row->entity.') déjà présent');
							continue;
						}

						$log('  - INSERT label="'.$row->label.'" (entity='.$row->entity.')');
						$sqlIns	= 'INSERT INTO '.$dstTable.' (label, content, active, entity) VALUES ('
								.'\''.$db->escape($row->label).'\', '
								.'\''.$db->escape($row->content).'\', '
								.((int) $row->active).', '
								.((int) $row->entity).')';
						if (! $db->query($sqlIns)) {
							$msg				= 'Erreur INSERT dictionnaire : '.$db->lasterror();
							$result['errors'][]	= $msg;
							$log('    '.$msg);
							$error++;
							break;
						}
					}
					$db->free($resSrc);
				}
			}
		}

		if ($error) {
			$db->rollback();
			$result['success']	= false;
		} elseif ($dryRun) {
			$db->rollback();
		} else {
			$db->commit();
		}

		return $result;
	}

	/**
	*	Désactive le module subtotal et supprime ses résidus (table dictionnaire,
	*	constantes MAIN_MODULE_SUBTOTAL*, constantes SUBTOTAL_* résiduelles).
	*	À appeler APRÈS une migration réussie (non dry-run).
	*
	*	@param		DoliDB		$db			Handler BDD
	*	@param		Conf		$conf		Configuration
	*	@param		callable	$logger		Callable optionnel logger(string $msg)
	*	@return		int						1 = OK, 0 = KO
	**/
	function infrastructure_cleanupSubtotal($db, $conf, $logger = null)
	{
		$log	= function ($m) use ($logger) {
			if (is_callable($logger)) {
				call_user_func($logger, $m);
			}
		};

		$db->begin();
		$error	= 0;

		// Désactivation du module subtotal (remove standard Dolibarr)
		$modSubFile	= DOL_DOCUMENT_ROOT.'/custom/subtotal/core/modules/modSubtotal.class.php';
		if (file_exists($modSubFile)) {
			include_once $modSubFile;
			if (class_exists('modSubtotal')) {
				$log('Désactivation du module subtotal (modSubtotal->remove())');
				$modSub	= new modSubtotal($db);
				$res	= $modSub->remove('');
				if ($res <= 0) {
					$log('  ATTENTION modSubtotal->remove() retour='.$res.(! empty($modSub->error) ? ' : '.$modSub->error : ''));
				}
			}
		} else {
			$log('Fichier modSubtotal.class.php introuvable — suppression directe');
		}

		// Suppression résiduelle des constantes d'activation
		$log('Suppression constantes MAIN_MODULE_SUBTOTAL*');
		$sqlDel1	= 'DELETE FROM '.MAIN_DB_PREFIX.'const'
					.' WHERE name = \'MAIN_MODULE_SUBTOTAL\''
					.' OR name LIKE \'MAIN_MODULE_SUBTOTAL\\_%\'';
		if (! $db->query($sqlDel1)) {
			$log('  Erreur : '.$db->lasterror());
			$error++;
		}

		// Suppression constantes SUBTOTAL_* résiduelles (normalement migrées)
		if (! $error) {
			$log('Suppression constantes SUBTOTAL_* résiduelles');
			$sqlDel2	= 'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE \'SUBTOTAL\\_%\'';
			if (! $db->query($sqlDel2)) {
				$log('  Erreur : '.$db->lasterror());
				$error++;
			}
		}

		// Suppression de la table dictionnaire subtotal
		if (! $error) {
			$log('DROP TABLE '.MAIN_DB_PREFIX.'c_subtotal_free_text');
			$sqlDrop	= 'DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'c_subtotal_free_text';
			if (! $db->query($sqlDrop)) {
				$log('  Erreur : '.$db->lasterror());
				$error++;
			}
		}

		if ($error) {
			$db->rollback();
			return 0;
		}
		$db->commit();
		return 1;
	}

	/**
	*	Migre le special_code de l'ancien numéro de module infrastructure (104777)
	*	vers le nouveau (550090) sur toutes les tables de lignes concernées.
	*
	*	@param		DoliDB		$db			Handler BDD
	*	@param		Conf		$conf		Configuration
	*	@param		boolean		$dryRun		Si vrai, rollback final (simulation)
	*	@param		callable	$logger		Callable optionnel logger(string $msg)
	*	@return		array					['success'=>bool, 'errors'=>string[], 'updated'=>int]
	**/
	function infrastructure_migrateSpecialCode($db, $conf, $dryRun = true, $logger = null)
	{
		$result			= array('success' => true, 'errors' => array(), 'updated' => 0);
		$oldCode		= 104777;
		$newCode		= 550090;
		$tables			= array('propaldet', 'commandedet', 'facturedet', 'supplier_proposaldet', 'commande_fournisseurdet', 'facture_fourn_det');
		$error			= 0;

		$log	= function ($m) use ($logger) {
			if (is_callable($logger)) {
				call_user_func($logger, $m);
			}
		};

		$db->begin();

		$log('Migration special_code '.$oldCode.' → '.$newCode);
		foreach ($tables as $table) {
			$sqlUpd		= 'UPDATE '.MAIN_DB_PREFIX.$db->escape($table)
						.' SET special_code = '.((int) $newCode)
						.' WHERE special_code = '.((int) $oldCode);
			$resql		= $db->query($sqlUpd);
			if (! $resql) {
				$msg				= 'Erreur update '.$table.' : '.$db->lasterror();
				$result['errors'][]	= $msg;
				$log('  '.$msg);
				$error++;
				break;
			}
			$count				= $db->affected_rows($resql);
			$result['updated']	+= $count;
			$log('  - '.$table.' : '.$count.' ligne(s) mise(s) à jour');
		}

		if ($error) {
			$db->rollback();
			$result['success']	= false;
			return $result;
		}

		if ($dryRun) {
			$db->rollback();
			$log('Dry-run : rollback effectué.');
		} else {
			$db->commit();
		}

		return $result;
	}
