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
	*	\file		./infrasworkflow/class/infrasworkflow_propal.class.php
	*	\ingroup	InfraS
	*	\brief		actions for infrasworkflow module (hook)
	************************************************/
	// Libraries ************************************
	dol_include_once('/comm/propal/class/propal.class.php');

	class infrasworkflowPropal extends Propal {

		/**
		 *  Returns an array with id and ref of related invoices
		 *
		 *	@param	int		$id				Id propal
		 *	@param	bool	$recursive		If true, search invoices linked through other objects (orders, etc.)
		 *										If false, only direct links are retrieved
		 *	@return	array|int				Array of invoices objects or -1 on error
		 */
		public function Infrasworkflow_InvoiceArrayList($id, $recursive = true)
		{
			$ga				= array();
			$linkedInvoices	= array();
			// Récupération des objets liés au devis
			$this->fetchObjectLinked($id, $this->element);
			foreach ($this->linkedObjectsIds as $objecttype => $objectid) {
				// Parcourt des objets liés
				foreach ($objectid as $key => $object) {
					// Cas des factures liées directement au devis
					if ($objecttype == 'facture') {
						$linkedInvoices[]	= $object;
					} elseif ($recursive === true) {
						// Cas des factures liées par un autre objet (ex: commande)
						// Uniquement si $recursive est activé
						$this->fetchObjectLinked($object, $objecttype);
						foreach ($this->linkedObjectsIds as $subobjecttype => $subobjectid) {
							foreach ($subobjectid as $subkey => $subobject) {
								if ($subobjecttype == 'facture') {
									$linkedInvoices[]	= $subobject;
								}
							}
						}
					}
				}
			}
			// Si des factures ont été trouvées
			if (count($linkedInvoices) > 0) {
				// Dédoublonnage des factures
				$linkedInvoices	= array_unique($linkedInvoices);
				$sql			= 'SELECT rowid as facid, ref, total_ht as total, datef as df, fk_user_author, fk_statut, paye, type';
				$sql			.= ' FROM '.$this->db->prefix().'facture';
				$sql			.= ' WHERE rowid IN ('.implode(',', array_map('intval', $linkedInvoices)).')';
				$sql			.= ' ORDER BY rowid ASC';
				dol_syslog(get_class($this).'::Infrasworkflow_InvoiceArrayList SQL: '.$sql, LOG_DEBUG);
				$resql	= $this->db->query($sql);
				if ($resql) {
					$tab_sqlobj	= array();
					$nump		= $this->db->num_rows($resql);
					for ($i = 0; $i < $nump; $i++) {
						$sqlobj			= $this->db->fetch_object($resql);
						$tab_sqlobj[]	= $sqlobj;
					}
					$this->db->free($resql);
					$nump = count($tab_sqlobj);
					if ($nump) {
						$i	= 0;
						while ($i < $nump) {
							$obj = array_shift($tab_sqlobj);
							$ga[$i] = $obj;
							$i++;
						}
					}
					return $ga;
				} else {
					$this->error = $this->db->lasterror();
					return -1;
				}
			} else {
				return $ga; // Retourne un tableau vide
			}
		}
	}
