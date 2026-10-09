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
	* 	\file		./infrastructure/core/lib/infrastructureMigrateSoustotal.lib.php
	* 	\ingroup	InfraS
	* 	\brief		Migration des données (structure des lignes, dictionnaire,
	*				constantes/couleurs) depuis le module soustotal (Iouston,
	*				modSousTotal) vers le module infrastructure, ainsi que le
	*				nettoyage/désactivation des résidus soustotal.
	*
	*	Modèle de données source (soustotal Iouston) :
	*		- lignes spéciales identifiées par l'extrafield options_soustotal_type
	*		  (1 = titre, 2 = sous-total, 3 = texte libre), niveau dans
	*		  options_soustotal_level ; special_code/qty/product_type non fiables.
	*		- dictionnaire llx_c_predefined_texts (rowid, code, label, description,
	*		  rang, color, entity, active).
	*		- constantes SOUSTOTAL_* (couleurs par niveau NIVEAU_%d_{PDF|FICHE}_*).
	*	Modèle cible (infrastructure) :
	*		- special_code = 550090 + product_type = 9 + qty (titre = niveau ;
	*		  sous-total = 100 - niveau ; texte libre = 50) ; saut de page info_bits = 8.
	************************************************/

	/**
	*	Exécute la migration soustotal → infrastructure (une transaction globale).
	*
	*	@param		DoliDB		$db			Handler BDD
	*	@param		Conf		$conf		Configuration
	*	@param		boolean		$dryRun		Si vrai, rollback final (simulation)
	*	@param		callable	$logger		Callable optionnel logger(string $msg)
	*	@return		array					['success'=>bool, 'errors'=>string[]]
	**/
	function infrastructure_migrateFromSoustotal($db, $conf, $dryRun = true, $logger = null)
	{
		$result		= ['success' => true, 'errors' => []];
		$error		= 0;

		$log	= function ($m) use ($logger) {
			if (is_callable($logger)) {
				call_user_func($logger, $m);
			}
		};

		// Helper : existence d'une table
		$tableExists	= function ($table) use ($db) {
			$res	= $db->query('SHOW TABLES LIKE \''.$db->escape($table).'\'');
			$exists	= ($res && $db->num_rows($res) > 0);
			if ($res) {
				$db->free($res);
			}
			return $exists;
		};

		// Helper : existence d'une colonne (underscores échappés pour le LIKE)
		$columnExists	= function ($table, $column) use ($db) {
			$like	= str_replace('_', '\\_', $column);
			$res	= $db->query('SHOW COLUMNS FROM '.$table.' LIKE \''.$db->escape($like).'\'');
			$exists	= ($res && $db->num_rows($res) > 0);
			if ($res) {
				$db->free($res);
			}
			return $exists;
		};

		$db->begin();

		// 1) Transformation des lignes de documents ************
		$log('[1/3] Transformation des lignes de documents (soustotal_type → qty/special_code)');
		$TElementType	= ['propaldet', 'commandedet', 'facturedet', 'facturedet_rec', 'commande_fournisseurdet', 'facture_fourn_det'];
		$predefTable	= MAIN_DB_PREFIX.'c_predefined_texts';
		$hasPredef		= $tableExists($predefTable);

		foreach ($TElementType as $elementtype) {
			if ($error) {
				break;
			}
			$detTable	= MAIN_DB_PREFIX.$elementtype;
			$efTable	= MAIN_DB_PREFIX.$elementtype.'_extrafields';
			$log('  Element : '.$elementtype);

			if (! $tableExists($efTable) || ! $columnExists($efTable, 'soustotal_type')) {
				$log('    - table/colonne soustotal_type absente → ignoré');
				continue;
			}

			// a) Encodage type + niveau dans qty + special_code/product_type
			$sqlA	= 'UPDATE '.$detTable.' d'
					.' JOIN '.$efTable.' e ON e.fk_object = d.rowid'
					.' SET d.special_code = 550090,'
					.'     d.product_type = 9,'
					.'     d.qty = CASE e.soustotal_type'
					.'                WHEN 1 THEN LEAST(GREATEST(COALESCE(e.soustotal_level, 1), 1), 9)'
					.'                WHEN 2 THEN 100 - LEAST(GREATEST(COALESCE(e.soustotal_level, 1), 1), 9)'
					.'                WHEN 3 THEN 50'
					.'                ELSE d.qty END'
					.' WHERE e.soustotal_type IN (1, 2, 3) AND d.special_code <> 550090';
			$resA	= $db->query($sqlA);
			if (! $resA) {
				$msg				= 'Erreur transformation lignes '.$elementtype.' : '.$db->lasterror();
				$result['errors'][]	= $msg;
				$log('      '.$msg);
				$error++;
				break;
			}
			$log('    - '.$detTable.' : '.$db->affected_rows($resA).' ligne(s) réencodée(s)');

			// b) Saut de page → info_bits = 8
			if ($columnExists($efTable, 'soustotal_page_break') && $columnExists($detTable, 'info_bits')) {
				$sqlB	= 'UPDATE '.$detTable.' d'
						.' JOIN '.$efTable.' e ON e.fk_object = d.rowid'
						.' SET d.info_bits = 8'
						.' WHERE e.soustotal_type IN (1, 2, 3) AND e.soustotal_page_break = 1';
				if (! $db->query($sqlB)) {
					$msg				= 'Erreur saut de page '.$elementtype.' : '.$db->lasterror();
					$result['errors'][]	= $msg;
					$log('      '.$msg);
					$error++;
					break;
				}
			}

			// c) Repli titre soustotal_hidden → extrafield infrastructure hideblock
			if ($columnExists($efTable, 'soustotal_hidden') && $columnExists($efTable, 'hideblock')) {
				$sqlC	= 'UPDATE '.$efTable.' e'
						.' SET e.hideblock = 1'
						.' WHERE e.soustotal_type = 1 AND e.soustotal_hidden = 1';
				if (! $db->query($sqlC)) {
					$msg				= 'Erreur report hideblock '.$elementtype.' : '.$db->lasterror();
					$result['errors'][]	= $msg;
					$log('      '.$msg);
					$error++;
					break;
				}
			}

			// d) Texte libre : backfill de la description depuis le dictionnaire si vide
			if ($hasPredef && $columnExists($efTable, 'soustotal_fk_free') && $columnExists($detTable, 'description')) {
				$sqlD	= 'UPDATE '.$detTable.' d'
						.' JOIN '.$efTable.' e ON e.fk_object = d.rowid'
						.' JOIN '.$predefTable.' p ON p.rowid = e.soustotal_fk_free'
						.' SET d.description = p.description'
						.' WHERE e.soustotal_type = 3 AND e.soustotal_fk_free > 0'
						.'   AND (d.description IS NULL OR d.description = \'\')';
				if (! $db->query($sqlD)) {
					$msg				= 'Erreur backfill texte libre '.$elementtype.' : '.$db->lasterror();
					$result['errors'][]	= $msg;
					$log('      '.$msg);
					$error++;
					break;
				}
			}
		}

		// 2) Dictionnaire c_predefined_texts → c_infrastructure_free_text
		if (! $error) {
			$log('[2/3] Dictionnaire c_predefined_texts → c_infrastructure_free_text');
			$srcTable	= MAIN_DB_PREFIX.'c_predefined_texts';
			$dstTable	= MAIN_DB_PREFIX.'c_infrastructure_free_text';

			if (! $tableExists($srcTable)) {
				$log('  Table '.$srcTable.' absente — aucune donnée à migrer');
			} else {
				$sqlSel		= 'SELECT rowid, label, description, active, entity FROM '.$srcTable;
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
								.'\''.$db->escape($row->description).'\', '
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

		// 3) Constantes SOUSTOTAL_* → INFRASTRUCTURE_* (options + couleurs/styles)
		if (! $error) {
			$log('[3/3] Constantes SOUSTOTAL_* → INFRASTRUCTURE_* (options, couleurs, styles)');

			// Découverte des entités possédant des constantes SOUSTOTAL_* (multi-entité :
			// les constantes ne vivent pas forcément dans $conf->entity — cf. multicompany).
			$entities	= [];
			$resEnt		= $db->query('SELECT DISTINCT entity FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE \'SOUSTOTAL\\_%\'');
			if (! $resEnt) {
				$msg				= 'Erreur lecture entités SOUSTOTAL_* : '.$db->lasterror();
				$result['errors'][]	= $msg;
				$log('  '.$msg);
				$error++;
			} else {
				while ($o = $db->fetch_object($resEnt)) {
					$entities[]	= (int) $o->entity;
				}
				$db->free($resEnt);
			}
			if (! $error && empty($entities)) {
				$log('  Aucune constante SOUSTOTAL_* — rien à mapper');
			}

			foreach ($entities as $ent) {
				if ($error) {
					break;
				}
				$log('  Entité '.$ent);

				// Charge toutes les valeurs SOUSTOTAL_* de cette entité
				$srcVals	= [];
				$resC		= $db->query('SELECT name, value FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE \'SOUSTOTAL\\_%\' AND entity = '.((int) $ent));
				if (! $resC) {
					$msg				= 'Erreur lecture constantes SOUSTOTAL_* (entity '.$ent.') : '.$db->lasterror();
					$result['errors'][]	= $msg;
					$log('    '.$msg);
					$error++;
					break;
				}
				while ($o = $db->fetch_object($resC)) {
					$srcVals[$o->name]	= $o->value;
				}
				$db->free($resC);

				$getS	= function ($n) use ($srcVals) {
					return isset($srcVals[$n]) ? (string) $srcVals[$n] : '';
				};
				$getI	= function ($n) use ($srcVals) {
					return isset($srcVals[$n]) ? (int) $srcVals[$n] : 0;
				};
				// Upsert d'une constante en SQL direct (sans dépendre de dolibarr_set_const,
				// pas toujours chargé selon le contexte d'appel) — reste dans la transaction.
				$setConst	= function ($name, $value) use ($db, $ent, $log, &$error, &$result) {
					if ($error) {
						return;
					}
					$sqlDel	= 'DELETE FROM '.MAIN_DB_PREFIX.'const'
							.' WHERE name = \''.$db->escape($name).'\' AND entity = '.((int) $ent);
					if (! $db->query($sqlDel)) {
						$msg				= 'Erreur suppression const '.$name.' (entity '.$ent.') : '.$db->lasterror();
						$result['errors'][]	= $msg;
						$log('    '.$msg);
						$error++;
						return;
					}
					$sqlIns	= 'INSERT INTO '.MAIN_DB_PREFIX.'const (name, value, type, visible, note, entity) VALUES ('
							.'\''.$db->escape($name).'\', '
							.'\''.$db->escape($value).'\', '
							.'\'chaine\', 0, \'Infrastructure module\', '
							.((int) $ent).')';
					if (! $db->query($sqlIns)) {
						$msg				= 'Erreur insertion const '.$name.' (entity '.$ent.') : '.$db->lasterror();
						$result['errors'][]	= $msg;
						$log('    '.$msg);
						$error++;
						return;
					}
					$log('    - '.$name.' = '.$value);
				};

				// 3a) Récapitulatif par document (équivalence directe)
				$recapMap	= [
					'SOUSTOTAL_AFFICHER_RECAPITULATIF_SOUSTOTAL_PROPAL'		=> 'INFRASTRUCTURE_PROPAL_ADD_RECAP',
					'SOUSTOTAL_AFFICHER_RECAPITULATIF_SOUSTOTAL_COMMANDE'	=> 'INFRASTRUCTURE_COMMANDE_ADD_RECAP',
					'SOUSTOTAL_AFFICHER_RECAPITULATIF_SOUSTOTAL_FACTURE'		=> 'INFRASTRUCTURE_INVOICE_ADD_RECAP',
				];
				foreach ($recapMap as $src => $dst) {
					if ($getI($src)) {
						$setConst($dst, '1');
					}
				}

				// 3b) Options globales dérivées de « au moins un document coché »
				$TDocSuffix		= ['PROPAL', 'COMMANDE', 'FACTURE', 'COMMANDE_FOURNISSEUR', 'FACTURE_FOURNISSEUR'];
				$anyMemeLigne	= 0;
				$anyMasquer		= 0;
				foreach ($TDocSuffix as $suffix) {
					if ($getI('SOUSTOTAL_TITRE_SOUSTOTAL_MEME_LIGNE_'.$suffix)) {
						$anyMemeLigne	= 1;
					}
					if ($getI('SOUSTOTAL_MASQUER_DETAIL_ENSEMBLES_'.$suffix)) {
						$anyMasquer		= 1;
					}
				}
				if ($anyMemeLigne) {
					$setConst('INFRASTRUCTURE_PDF_TITLE_WITH_TOTAL', '1');
				}
				if ($anyMasquer) {
					$setConst('INFRASTRUCTURE_HIDE_FOLDERS_BY_DEFAULT', '1');
				}

				// 3c) Couleurs — référence NIVEAU_1 ; le sous-total reçoit la même valeur que le titre
				$colorMap	= [
					'SOUSTOTAL_NIVEAU_1_FICHE_COULEUR_FOND'		=> ['INFRASTRUCTURE_TITLE_BACKGROUND_COLOR', 'INFRASTRUCTURE_TOTAL_BACKGROUND_COLOR'],
					'SOUSTOTAL_NIVEAU_1_FICHE_COULEUR_TEXTE'	=> ['INFRASTRUCTURE_TITLE_COLOR', 'INFRASTRUCTURE_TOTAL_COLOR'],
					'SOUSTOTAL_NIVEAU_1_PDF_COULEUR_FOND'		=> ['INFRASTRUCTURE_PDF_TITLE_BACKGROUND_COLOR', 'INFRASTRUCTURE_PDF_TOTAL_BACKGROUND_COLOR'],
					'SOUSTOTAL_NIVEAU_1_PDF_COULEUR_TEXTE'		=> ['INFRASTRUCTURE_PDF_TITLE_COLOR', 'INFRASTRUCTURE_PDF_TOTAL_COLOR'],
				];
				foreach ($colorMap as $src => $targets) {
					$val	= $getS($src);
					if ($val !== '') {
						foreach ($targets as $dst) {
							$setConst($dst, $val);
						}
					}
				}

				// 3d) Styles — reconstruits (B/U/I) depuis gras/souligné/italique du NIVEAU_1
				$buildStyle	= function ($prefix) use ($getI) {
					$style	= '';
					if ($getI($prefix.'_GRAS_TEXTE')) {
						$style	.= 'B';
					}
					if ($getI($prefix.'_SOULIGNE_TEXTE')) {
						$style	.= 'U';
					}
					if ($getI($prefix.'_ITALIQUE_TEXTE')) {
						$style	.= 'I';
					}
					return $style;
				};
				$styleFiche	= $buildStyle('SOUSTOTAL_NIVEAU_1_FICHE');
				if ($styleFiche !== '') {
					$setConst('INFRASTRUCTURE_TITLE_STYLE', $styleFiche);
					$setConst('INFRASTRUCTURE_TOTAL_STYLE', $styleFiche);
				}
				$stylePdf	= $buildStyle('SOUSTOTAL_NIVEAU_1_PDF');
				if ($stylePdf !== '') {
					$setConst('INFRASTRUCTURE_PDF_TITLE_STYLE', $stylePdf);
					$setConst('INFRASTRUCTURE_PDF_TOTAL_STYLE', $stylePdf);
				}
				$styleFree	= $buildStyle('SOUSTOTAL_TEXTE_LIBRE_FICHE');
				if ($styleFree !== '') {
					$setConst('INFRASTRUCTURE_TEXT_LINE_STYLE', $styleFree);
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
	*	Désactive le module soustotal et supprime ses résidus (table dictionnaire,
	*	constantes MAIN_MODULE_SOUSTOTAL*, constantes SOUSTOTAL_* résiduelles,
	*	extrafields soustotal_* orphelins).
	*	À appeler APRÈS une migration réussie (non dry-run).
	*
	*	@param		DoliDB		$db			Handler BDD
	*	@param		Conf		$conf		Configuration
	*	@param		callable	$logger		Callable optionnel logger(string $msg)
	*	@return		int						1 = OK, 0 = KO
	**/
	function infrastructure_cleanupSoustotal($db, $conf, $logger = null)
	{
		$log	= function ($m) use ($logger) {
			if (is_callable($logger)) {
				call_user_func($logger, $m);
			}
		};

		$db->begin();
		$error	= 0;

		// Désactivation du module soustotal (remove standard Dolibarr)
		$modSubFile	= DOL_DOCUMENT_ROOT.'/custom/soustotal/core/modules/modSousTotal.class.php';
		if (file_exists($modSubFile)) {
			include_once $modSubFile;
			if (class_exists('modSousTotal')) {
				$log('Désactivation du module soustotal (modSousTotal->remove())');
				$modSub	= new modSousTotal($db);
				$res	= $modSub->remove('');
				if ($res <= 0) {
					$log('  ATTENTION modSousTotal->remove() retour='.$res.(! empty($modSub->error) ? ' : '.$modSub->error : ''));
				}
			}
		} else {
			$log('Fichier modSousTotal.class.php introuvable — suppression directe');
		}

		// Suppression résiduelle des constantes d'activation
		$log('Suppression constantes MAIN_MODULE_SOUSTOTAL*');
		$sqlDel1	= 'DELETE FROM '.MAIN_DB_PREFIX.'const'
					.' WHERE name = \'MAIN_MODULE_SOUSTOTAL\''
					.' OR name LIKE \'MAIN_MODULE_SOUSTOTAL\\_%\'';
		if (! $db->query($sqlDel1)) {
			$log('  Erreur : '.$db->lasterror());
			$error++;
		}

		// Suppression constantes SOUSTOTAL_* résiduelles
		if (! $error) {
			$log('Suppression constantes SOUSTOTAL_* résiduelles');
			$sqlDel2	= 'DELETE FROM '.MAIN_DB_PREFIX.'const WHERE name LIKE \'SOUSTOTAL\\_%\'';
			if (! $db->query($sqlDel2)) {
				$log('  Erreur : '.$db->lasterror());
				$error++;
			}
		}

		// Suppression de la table dictionnaire soustotal
		if (! $error) {
			$log('DROP TABLE '.MAIN_DB_PREFIX.'c_predefined_texts');
			$sqlDrop	= 'DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'c_predefined_texts';
			if (! $db->query($sqlDrop)) {
				$log('  Erreur : '.$db->lasterror());
				$error++;
			}
		}

		// Suppression des extrafields soustotal_* orphelins (définitions + colonnes)
		if (! $error) {
			$log('Suppression des extrafields soustotal_* orphelins');
			$sqlDelEF	= 'DELETE FROM '.MAIN_DB_PREFIX.'extrafields WHERE name LIKE \'soustotal\\_%\'';
			if (! $db->query($sqlDelEF)) {
				$log('  Erreur : '.$db->lasterror());
				$error++;
			}
		}
		if (! $error) {
			$TElementType	= ['propaldet', 'commandedet', 'facturedet', 'facturedet_rec', 'commande_fournisseurdet', 'facture_fourn_det', 'product'];
			$TSoustotalCol	= ['soustotal_type', 'soustotal_level', 'soustotal_hidden', 'soustotal_always_shown', 'soustotal_fk_free', 'soustotal_page_break', 'soustotal_cat_id'];
			foreach ($TElementType as $elementtype) {
				if ($error) {
					break;
				}
				$efTable	= MAIN_DB_PREFIX.$elementtype.'_extrafields';
				$resTbl		= $db->query('SHOW TABLES LIKE \''.$db->escape($efTable).'\'');
				$hasTbl		= ($resTbl && $db->num_rows($resTbl) > 0);
				if ($resTbl) {
					$db->free($resTbl);
				}
				if (! $hasTbl) {
					continue;
				}
				foreach ($TSoustotalCol as $col) {
					$like	= str_replace('_', '\\_', $col);
					$resCol	= $db->query('SHOW COLUMNS FROM '.$efTable.' LIKE \''.$db->escape($like).'\'');
					$hasCol	= ($resCol && $db->num_rows($resCol) > 0);
					if ($resCol) {
						$db->free($resCol);
					}
					if ($hasCol) {
						if (! $db->query('ALTER TABLE '.$efTable.' DROP COLUMN '.$col)) {
							$log('  Erreur DROP COLUMN '.$col.' sur '.$efTable.' : '.$db->lasterror());
							$error++;
							break;
						}
					}
				}
			}
		}

		if ($error) {
			$db->rollback();
			return 0;
		}
		$db->commit();
		return 1;
	}
