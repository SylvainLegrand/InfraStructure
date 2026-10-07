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
	* 	\file		./infrasfiles/class/infrasfileswithdraw.class.php
	* 	\ingroup	InfraS
	* 	\brief		Child class of the native BonPrelevement (element 'widthdraw') adding the documents layer
	*				(generateDocument with one PDF per third party or per parent company, document state)
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
	dol_include_once('/infrasfiles/class/infrasfilesdocumenttrait.class.php');

	/************************************************
	* Class InfrasFilesWithdraw
	************************************************/
	class InfrasFilesWithdraw extends BonPrelevement
	{
		use InfrasFilesDocumentTrait;

		public $infrasfiles_element = 'widthdraw';

		/**
		*	Create a document onto disk according to template module.
		*	Depending on the option INFRASFILES_WIDTHDRAW_SPLIT_MODE, one PDF is generated per third party (grouping its lines)
		*	or per parent company (grouping the lines of its third parties flagged with the extrafield, the other third parties keeping their own PDF).
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
			$outputlangs->loadLangs(array('withdrawals', 'banks', 'bills', 'companies', 'salaries', 'infrasfiles@infrasfiles'));
			if (empty($this->specimen) && $this->infrasfilesFetchLines() < 0) {
				return -1;
			}
			return $this->infrasfilesGenerate($modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams, $this->infrasfilesGetUnits());
		}

		/**
		*	Recipients proposed in the list of the native send by e-mail form : the contacts (with an e-mail) of the third parties of the lines,
		*	in one query. Nothing is prefilled in the free field : the user chooses.
		*
		*	@return		array		array(contact id => 'Name (Third party) <email>')
		**/
		public function infrasfilesGetRecipients()
		{
			$recipients	= array();
			if (empty($this->infrasfiles_lines)) {
				$this->infrasfilesFetchLines();
			}
			$names	= array();
			foreach ($this->infrasfiles_lines as $line) {
				if ((int) $line['fk_soc'] > 0) {
					$names[(int) $line['fk_soc']]	= $line['name'];
				}
			}
			foreach ($this->infrasfilesFetchContacts(array_keys($names)) as $socid => $contacts) {
				foreach ($contacts as $contactid => $contact) {
					$recipients[$contactid]	= $contact['name'].' ('.dol_string_nospecial($names[$socid], ' ', array(',')).') <'.$contact['email'].'>';
				}
			}
				return $recipients;
		}
		/**
		*	Active contacts having an e-mail, grouped by third party, in one query (a monthly order can have dozens of third parties)
		*
		*	@param		array		$socids		Third party ids
		*	@return		array					array(socid => array(contact id => array('name' => , 'email' => ))), name cleaned for an e-mail address
		**/
		public function infrasfilesFetchContacts($socids)
		{
			$contacts	= array();
			$socids		= array_filter(array_map('intval', (array) $socids));
			if (empty($socids)) {
				return $contacts;
			}
			$sql	= 'SELECT rowid, fk_soc, lastname, firstname, email FROM '.$this->db->prefix().'socpeople';
			$sql	.= ' WHERE fk_soc IN ('.implode(',', $socids).') AND statut = 1 AND email IS NOT NULL AND email <> ""';
			$sql	.= ' ORDER BY lastname ASC, firstname ASC';
			$resql	= $this->db->query($sql);
			if (!$resql) {
				return $contacts;
			}
			while ($obj = $this->db->fetch_object($resql)) {
				$contacts[(int) $obj->fk_soc][(int) $obj->rowid]	= array('name'	=> dol_string_nospecial(dolGetFirstLastname($obj->firstname, $obj->lastname), ' ', array(',')),
																		'email'	=> (string) $obj->email);
			}
			return $contacts;
		}
		/**
		*	Every third party a PDF of this order can be addressed to : the third party of each line AND its parent company, whatever
		*	the current split mode (so that files generated under the other mode are still recognised), with the file suffix of each one
		*	(same rule as infrasfilesGetUnits() : customer / supplier code, 'ID<id>' when empty)
		*
		*	@return		array		List of array('id' => , 'name' => , 'code' => , 'email' => , 'suffix' => )
		**/
		public function infrasfilesGetAddressees()
		{
			$addressees	= array();
			foreach ($this->infrasfiles_lines as $line) {
				$candidates	= array(array('id'		=> (int) $line['fk_soc'],
										'name'		=> (string) $line['name'],
										'code'		=> isset($line['code']) ? (string) $line['code'] : '',
										'email'		=> isset($line['email']) ? (string) $line['email'] : ''));
				if (!empty($line['parent']) && !empty($line['parent']['id'])) {
					$candidates[]	= array('id'	=> (int) $line['parent']['id'],
											'name'	=> (string) $line['parent']['name'],
											'code'	=> isset($line['parent']['code']) ? (string) $line['parent']['code'] : '',
											'email'	=> isset($line['parent']['email']) ? (string) $line['parent']['email'] : '');
				}
				foreach ($candidates as $candidate) {
					if ($candidate['id'] <= 0 || isset($addressees['soc'.$candidate['id']])) {
						continue;
					}
					$code					= !empty($candidate['code']) ? $candidate['code'] : 'ID'.$candidate['id'];
					$candidate['suffix']	= dol_sanitizeFileName($code);
					$addressees['soc'.$candidate['id']]	= $candidate;
				}
			}
			return array_values($addressees);
		}
		/**
		*	Batches of the "one e-mail per third party" send form : one batch per addressee having at least one PDF in the directory
		*	of the order, with its files, the recipients to propose (e-mail of the third party + its active contacts having an e-mail,
		*	one grouped query) and the recipients preselected (the third party e-mail when it exists, else all its contacts)
		*
		*	@return		array		array('batches' => array(socid => array('addressee' => , 'files' => full paths, 'recipients' => array(key => 'Name <email>'), 'default' => keys)),
		*									'orphans' => file names not matching any addressee (not sent))
		**/
		public function infrasfilesGetMailBatches()
		{
			if (empty($this->infrasfiles_lines)) {
				$this->infrasfilesFetchLines();
			}
			$files	= dol_dir_list($this->infrasfilesGetOutputDir(), 'files', 0, '\.pdf$', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 0);
			$map	= $this->infrasfilesGetFileAddressees($files);
			$batches	= array();
			$orphans	= array();
			foreach ($files as $file) {
				if (!isset($map[$file['name']])) {
					$orphans[]	= $file['name'];
					continue;
				}
				$addressee	= $map[$file['name']];
				$socid		= (int) $addressee['id'];
				if (!isset($batches[$socid])) {
					$batches[$socid]	= array('addressee' => $addressee, 'files' => array(), 'recipients' => array(), 'default' => array());
				}
				$batches[$socid]['files'][]	= $file['fullname'];
			}
			$contacts	= $this->infrasfilesFetchContacts(array_keys($batches));
			foreach ($batches as $socid => $batch) {
				$recipients	= array();
				if (!empty($batch['addressee']['email'])) {
					$recipients['thirdparty']	= dol_string_nospecial($batch['addressee']['name'], ' ', array(',')).' <'.$batch['addressee']['email'].'>';
				}
				if (!empty($contacts[$socid])) {
					foreach ($contacts[$socid] as $contactid => $contact) {
						$recipients[$contactid]	= $contact['name'].' <'.$contact['email'].'>';
					}
				}
				$batches[$socid]['recipients']	= $recipients;
				$batches[$socid]['default']		= isset($recipients['thirdparty']) ? array('thirdparty') : array_keys($recipients);
			}
			return array('batches' => $batches, 'orphans' => $orphans);
		}

		/**
		*	Fill the object with specimen data (no database record) : used by the preview of the models on the setup page.
		*	Line 1 = a third party without parent company ; lines 2 and 3 = a subsidiary flagged "address to the parent company",
		*	with a due date and a credit note applied on line 2.
		*
		*	@return		void
		**/
		public function infrasfilesInitAsSpecimen()
		{
			global $langs;

			$langs->loadLangs(array('companies', 'infrasfiles@infrasfiles'));
			$this->id				= 0;
			$this->specimen			= 1;
			$this->ref				= 'SPECIMEN';
			$this->type				= 'debit-order';
			$this->statut			= self::STATUS_DRAFT;
			$this->status			= self::STATUS_DRAFT;
			$this->datec			= dol_now();
			$this->date_trans		= 0;
			$this->fk_bank_account	= 0;
			$sql	= 'SELECT rowid FROM '.$this->db->prefix().'bank_account WHERE entity IN ('.getEntity('bank_account').') AND clos = 0 ORDER BY rowid ASC';
			$resql	= $this->db->query($sql);
			if ($resql && ($obj = $this->db->fetch_object($resql))) {
				$this->fk_bank_account	= (int) $obj->rowid;
			}
			$this->amount			= 0;
			$this->infrasfiles_lines	= array();
			$now	= dol_now();
			$specimen	= $langs->transnoentities('InfraSFilesSpecimen');
			$parent		= array('id'		=> 3,
								'name'		=> $specimen.' '.$langs->transnoentities('ParentCompany'),
								'code'		=> 'CU-SPECIMEN-PARENT',
								'address'	=> '3 '.$langs->transnoentities('Address'),
								'zip'		=> '00000',
								'town'		=> $langs->transnoentities('Town'),
								'email'		=> '');
			for ($i = 1; $i <= 3; $i++) {
				$amount	= 100 * $i + 0.5 * $i;
				$issub		= ($i > 1);	// lines 2 and 3 : subsidiary of the specimen parent company
				$this->amount				+= $amount;
				$this->infrasfiles_lines[]	= array('rowid'		=> $i,
													'fk_soc'	=> $issub ? 2 : 1,
													'name'		=> $specimen.' '.$langs->transnoentities('ThirdParty').($issub ? ' B' : ' A'),
													'code'		=> $issub ? 'CU-SPECIMEN-SUB' : 'CU-SPECIMEN',
													'address'	=> ($issub ? '2 ' : '1 ').$langs->transnoentities('Address'),
													'zip'		=> '00000',
													'town'		=> $langs->transnoentities('Town'),
													'fk_pays'	=> 0,
													'email'		=> '',
													'amount'	=> $amount,
													'statut'	=> 0,
													'fk_parent'	=> $issub ? 3 : 0,
													'to_parent'	=> $issub ? 1 : 0,
													'parent'	=> $issub ? $parent : array(),
													'rib'		=> array('iban' => 'FR76 0000 0000 0000 0000 0000 000', 'bic' => 'XXXXXXXX', 'rum' => 'RUM-SPECIMEN', 'bank' => ''),
													'documents'	=> array(array('type'		=> 'invoice',
																				'id'		=> $i,
																				'ref'		=> 'FA-SPECIMEN-'.$i,
																				'ref_ext'	=> '',
																				'fk_soc'	=> $issub ? 2 : 1,
																				'date'		=> $now,
																				'date_due'	=> $now + 30 * 86400,
																				'amount'	=> $amount,
																				'credits'	=> ($i == 2) ? array(array('ref' => 'AV-SPECIMEN-1', 'amount' => 20)) : array())));
			}
		}

		/**
		*	Load the lines of the order into $this->infrasfiles_lines : third party, its parent company and the extrafield
		*	"address the slips to the parent company", bank account really used by the line (fallback : default account of the third party)
		*	and linked documents (invoices, supplier invoices, salaries) with their due date and the credit notes applied.
		*
		*	@return		int			Number of lines, -1 if KO
		**/
		public function infrasfilesFetchLines()
		{
			$this->infrasfiles_lines	= array();
			// Optional columns : the bank account of the line (InfraS core addition, absent from a stock Dolibarr) and the module extrafield
			// (created at activation) are read only when their column exists, so that the query never fails on another instance
			$hasribcol		= $this->infrasfilesColumnExists('prelevement_lignes', 'fk_soc_rib');
			$hasparentcol	= $this->infrasfilesColumnExists('societe_extrafields', infrasfiles_to_parent_field());
			$sql	= 'SELECT pl.rowid, pl.fk_soc, pl.client_nom, pl.amount, pl.statut,';
			$sql	.= ($hasribcol ? ' pl.fk_soc_rib,' : ' 0 AS fk_soc_rib,');
			$sql	.= ' s.nom, s.code_client, s.code_fournisseur, s.address, s.zip, s.town, s.fk_pays, s.email, s.parent,';
			$sql	.= ($hasparentcol ? ' se.'.infrasfiles_to_parent_field().' AS to_parent,' : ' 0 AS to_parent,');
			$sql	.= ' sp.nom AS parent_nom, sp.code_client AS parent_code_client, sp.code_fournisseur AS parent_code_fournisseur,';
			$sql	.= ' sp.address AS parent_address, sp.zip AS parent_zip, sp.town AS parent_town, sp.email AS parent_email';
			$sql	.= ' FROM '.$this->db->prefix().'prelevement_lignes AS pl';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'societe AS s ON s.rowid = pl.fk_soc';
			$sql	.= ($hasparentcol ? ' LEFT JOIN '.$this->db->prefix().'societe_extrafields AS se ON se.fk_object = s.rowid' : '');
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'societe AS sp ON sp.rowid = s.parent';
			$sql	.= ' WHERE pl.fk_prelevement_bons = '.((int) $this->id);
			$sql	.= ' ORDER BY s.nom ASC, pl.rowid ASC';
			$resql	= $this->db->query($sql);
			if (!$resql) {
				$this->error	= $this->db->lasterror();
				return -1;
			}
			$rows	= array();
			while ($obj = $this->db->fetch_object($resql)) {
				$rows[]	= $obj;
			}
			// Grouped queries for all the lines (no query per line : a monthly order can have hundreds of lines)
			$ribs		= $this->infrasfilesFetchRibs(array_unique(array_map(function ($row) { return (int) $row->fk_soc; }, $rows)), array_unique(array_map(function ($row) { return (int) $row->fk_soc_rib; }, $rows)));
			$documents	= $this->infrasfilesFetchDocuments(array_map(function ($row) { return (int) $row->rowid; }, $rows));
			$istransfer	= ($this->type == 'bank-transfer');
			foreach ($rows as $obj) {
				$socid	= (int) $obj->fk_soc;
				$ribid	= (int) $obj->fk_soc_rib;
				$parent	= array();
				if ((int) $obj->parent > 0) {
					$parent	= array('id'		=> (int) $obj->parent,
									'name'		=> (string) $obj->parent_nom,
									'code'		=> (string) ($istransfer ? $obj->parent_code_fournisseur : $obj->parent_code_client),
									'address'	=> (string) $obj->parent_address,
									'zip'		=> (string) $obj->parent_zip,
									'town'		=> (string) $obj->parent_town,
									'email'		=> (string) $obj->parent_email);
				}
				$this->infrasfiles_lines[]	= array('rowid'			=> (int) $obj->rowid,
													'fk_soc'		=> $socid,
													'name'			=> (string) (!empty($obj->nom) ? $obj->nom : $obj->client_nom),
													'code'			=> (string) ($istransfer ? $obj->code_fournisseur : $obj->code_client),
													'address'		=> (string) $obj->address,
													'zip'			=> (string) $obj->zip,
													'town'			=> (string) $obj->town,
													'fk_pays'		=> (int) $obj->fk_pays,
													'email'			=> (string) $obj->email,
													'amount'		=> (float) $obj->amount,
													'statut'		=> (int) $obj->statut,
													'fk_parent'		=> (int) $obj->parent,
													'to_parent'		=> (int) $obj->to_parent,
													'parent'		=> $parent,
													'rib'			=> ($ribid && isset($ribs['byid'][$ribid])) ? $ribs['byid'][$ribid] : (isset($ribs['bysoc'][$socid]) ? $ribs['bysoc'][$socid] : array('iban' => '', 'bic' => '', 'rum' => '', 'bank' => '')),
													'documents'		=> isset($documents[(int) $obj->rowid]) ? $documents[(int) $obj->rowid] : array());
			}
			return count($this->infrasfiles_lines);
		}

		/**
		*	Test if a column exists in a table (static cache : one DESC per table/column and per request)
		*
		*	@param		string		$table		Table name without prefix
		*	@param		string		$column		Column name
		*	@return		bool
		**/
		protected function infrasfilesColumnExists($table, $column)
		{
			static $cache	= array();
			$key	= $table.'.'.$column;
			if (!isset($cache[$key])) {
				$resql	= $this->db->DDLDescTable($this->db->prefix().$table, $column);
				$cache[$key]	= ($resql && $this->db->num_rows($resql) > 0);
			}
			return $cache[$key];
		}
		/**
		*	Bank accounts (IBAN, BIC, RUM) in one query : the accounts really used by the lines (by id) and the default account of each third party (fallback)
		*
		*	@param		array		$socids		Third party ids
		*	@param		array		$ribids		Bank account ids of the lines (llx_societe_rib.rowid)
		*	@return		array					array('byid' => array(rib id => rib), 'bysoc' => array(socid => default rib)), rib = array('iban' => , 'bic' => , 'rum' => , 'bank' => )
		**/
		protected function infrasfilesFetchRibs($socids, $ribids = array())
		{
			$ribs	= array('byid' => array(), 'bysoc' => array());
			$socids	= array_filter(array_map('intval', (array) $socids));
			$ribids	= array_filter(array_map('intval', (array) $ribids));
			if (empty($socids) && empty($ribids)) {
				return $ribs;
			}
			$where	= array();
			if (!empty($socids)) {
				$where[]	= 'fk_soc IN ('.implode(',', $socids).')';
			}
			if (!empty($ribids)) {
				$where[]	= 'rowid IN ('.implode(',', $ribids).')';
			}
			$sql	= 'SELECT rowid, fk_soc, iban_prefix, bic, rum, bank FROM '.$this->db->prefix().'societe_rib';
			$sql	.= " WHERE type = 'ban' AND (".implode(' OR ', $where).')';
			$sql	.= ' ORDER BY fk_soc ASC, default_rib DESC, rowid DESC';	// first row of each third party = its default account
			$resql	= $this->db->query($sql);
			if (!$resql) {
				return $ribs;
			}
			while ($obj = $this->db->fetch_object($resql)) {
				$iban	= (string) $obj->iban_prefix;
				if (function_exists('dolDecrypt')) {
					$iban	= dolDecrypt($iban);	// IBAN stored encrypted since Dolibarr 17 (same as CompanyBankAccount::fetch())
				}
				$rib	= array('iban' => $iban, 'bic' => (string) $obj->bic, 'rum' => (string) $obj->rum, 'bank' => (string) $obj->bank);
				$ribs['byid'][(int) $obj->rowid]	= $rib;
				if (!isset($ribs['bysoc'][(int) $obj->fk_soc])) {
					$ribs['bysoc'][(int) $obj->fk_soc]	= $rib;
				}
			}
			return $ribs;
		}

		/**
		*	Documents (customer invoices, supplier invoices, salaries) linked to a list of lines of the order, with their due date
		*	and the credit notes applied on them (llx_societe_remise_except : credit note source -> invoice), in two grouped queries
		*
		*	@param		array		$lineids	Line ids (llx_prelevement_lignes.rowid)
		*	@return		array					array(line id => list of array('type' => 'invoice'|'supplier_invoice'|'salary', 'id' => , 'ref' => , 'ref_ext' => , 'fk_soc' => , 'date' => timestamp, 'date_due' => timestamp, 'amount' => , 'credits' => list of array('ref' => , 'amount' => )))
		**/
		protected function infrasfilesFetchDocuments($lineids)
		{
			$documents	= array();
			$lineids	= array_filter(array_map('intval', (array) $lineids));
			if (empty($lineids)) {
				return $documents;
			}
			$sql	= 'SELECT pf.fk_prelevement_lignes, pf.fk_facture, pf.fk_facture_fourn, pf.fk_salary,';
			$sql	.= ' f.ref AS f_ref, f.fk_soc AS f_soc, f.datef AS f_date, f.date_lim_reglement AS f_due, f.total_ttc AS f_total,';
			$sql	.= ' ff.ref AS ff_ref, ff.ref_supplier AS ff_ref_supplier, ff.fk_soc AS ff_soc, ff.datef AS ff_date, ff.date_lim_reglement AS ff_due, ff.total_ttc AS ff_total,';
			$sql	.= ' sa.ref AS sa_ref, sa.label AS sa_label, sa.datep AS sa_date, sa.amount AS sa_total';
			$sql	.= ' FROM '.$this->db->prefix().'prelevement AS pf';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'facture AS f ON f.rowid = pf.fk_facture';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'facture_fourn AS ff ON ff.rowid = pf.fk_facture_fourn';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'salary AS sa ON sa.rowid = pf.fk_salary';
			$sql	.= ' WHERE pf.fk_prelevement_lignes IN ('.implode(',', $lineids).')';
			$sql	.= ' ORDER BY pf.fk_prelevement_lignes ASC, pf.rowid ASC';
			$resql	= $this->db->query($sql);
			if (!$resql) {
				return $documents;
			}
			$invoiceids		= array();
			$supplierids	= array();
			while ($obj = $this->db->fetch_object($resql)) {
				$lineid	= (int) $obj->fk_prelevement_lignes;
				if (!empty($obj->fk_facture)) {
					$invoiceids[]			= (int) $obj->fk_facture;
					$documents[$lineid][]	= array('type' => 'invoice', 'id' => (int) $obj->fk_facture, 'ref' => (string) $obj->f_ref, 'ref_ext' => '', 'fk_soc' => (int) $obj->f_soc, 'date' => $this->db->jdate($obj->f_date), 'date_due' => $this->db->jdate($obj->f_due), 'amount' => (float) $obj->f_total, 'credits' => array());
				} elseif (!empty($obj->fk_facture_fourn)) {
					$supplierids[]			= (int) $obj->fk_facture_fourn;
					$documents[$lineid][]	= array('type' => 'supplier_invoice', 'id' => (int) $obj->fk_facture_fourn, 'ref' => (string) $obj->ff_ref, 'ref_ext' => (string) $obj->ff_ref_supplier, 'fk_soc' => (int) $obj->ff_soc, 'date' => $this->db->jdate($obj->ff_date), 'date_due' => $this->db->jdate($obj->ff_due), 'amount' => (float) $obj->ff_total, 'credits' => array());
				} elseif (!empty($obj->fk_salary)) {
					$documents[$lineid][]	= array('type' => 'salary', 'id' => (int) $obj->fk_salary, 'ref' => (string) (!empty($obj->sa_ref) ? $obj->sa_ref : $obj->sa_label), 'ref_ext' => '', 'fk_soc' => 0, 'date' => $this->db->jdate($obj->sa_date), 'date_due' => 0, 'amount' => (float) $obj->sa_total, 'credits' => array());
				}
			}
			// Credit notes applied on these invoices : a consumed credit note is a discount whose source is the credit note (type 2) and whose target is the invoice
			$credits	= $this->infrasfilesFetchCredits($invoiceids, $supplierids);
			if (!empty($credits)) {
				foreach ($documents as $lineid => $list) {
					foreach ($list as $index => $document) {
						if (isset($credits[$document['type']][$document['id']])) {
							$documents[$lineid][$index]['credits']	= $credits[$document['type']][$document['id']];
						}
					}
				}
			}
			return $documents;
		}

		/**
		*	Credit notes applied on a list of customer / supplier invoices, in one query
		*
		*	@param		array		$invoiceids		Customer invoice ids
		*	@param		array		$supplierids	Supplier invoice ids
		*	@return		array						array('invoice' => array(invoice id => list of array('ref' => credit note ref, 'amount' => amount TTC applied)), 'supplier_invoice' => idem)
		**/
		protected function infrasfilesFetchCredits($invoiceids, $supplierids)
		{
			$credits		= array();
			$invoiceids		= array_filter(array_map('intval', (array) $invoiceids));
			$supplierids	= array_filter(array_map('intval', (array) $supplierids));
			if (empty($invoiceids) && empty($supplierids)) {
				return $credits;
			}
			$where	= array();
			if (!empty($invoiceids)) {
				$where[]	= '(re.fk_facture IN ('.implode(',', $invoiceids).') AND fs.type = 2)';	// 2 = Facture::TYPE_CREDIT_NOTE (class not loaded here)
			}
			if (!empty($supplierids)) {
				$where[]	= '(re.fk_invoice_supplier IN ('.implode(',', $supplierids).') AND ffs.type = 2)';	// 2 = FactureFournisseur::TYPE_CREDIT_NOTE
			}
			$sql	= 'SELECT re.fk_facture, re.fk_invoice_supplier, re.amount_ttc, fs.ref AS fs_ref, ffs.ref AS ffs_ref';
			$sql	.= ' FROM '.$this->db->prefix().'societe_remise_except AS re';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'facture AS fs ON fs.rowid = re.fk_facture_source';
			$sql	.= ' LEFT JOIN '.$this->db->prefix().'facture_fourn AS ffs ON ffs.rowid = re.fk_invoice_supplier_source';
			$sql	.= ' WHERE '.implode(' OR ', $where);
			$sql	.= ' ORDER BY re.rowid ASC';
			$resql	= $this->db->query($sql);
			if (!$resql) {
				return $credits;
			}
			while ($obj = $this->db->fetch_object($resql)) {
				if (!empty($obj->fk_facture)) {
					$credits['invoice'][(int) $obj->fk_facture][]	= array('ref' => (string) $obj->fs_ref, 'amount' => (float) $obj->amount_ttc);
				} elseif (!empty($obj->fk_invoice_supplier)) {
					$credits['supplier_invoice'][(int) $obj->fk_invoice_supplier][]	= array('ref' => (string) $obj->ffs_ref, 'amount' => (float) $obj->amount_ttc);
				}
			}
			return $credits;
		}
		/**
		*	Build the generation units (one unit = one PDF) according to the split mode option :
		*	'thirdparty' = one PDF per third party grouping its lines ;
		*	'parent' = the lines whose third party has a parent company AND the extrafield checked are grouped into the PDF of that
		*	parent company (addressed to it), every other third party keeps its own PDF.
		*
		*	@return		array		List of array('suffix' => file suffix, 'label' => label, 'fk_soc' => addressee id, 'toparent' => bool, 'addressee' => array('id', 'name', 'code', 'address', 'zip', 'town'), 'lineids' => array of line ids)
		**/
		public function infrasfilesGetUnits()
		{
			$units	= array();
			$mode	= infrasfiles_get_option('widthdraw', 'SPLIT_MODE');
			foreach ($this->infrasfiles_lines as $line) {
				$toparent	= ($mode == 'parent' && infrasfiles_line_addressed_to_parent($line) && !empty($line['parent']));
				// Key = addressee : a parent company having its own invoices in the order gets ONE PDF with its own lines and those of its flagged third parties
				$key		= 'soc'.($toparent ? $line['fk_parent'] : $line['fk_soc']);
				if (isset($units[$key])) {
					$units[$key]['toparent']	= $units[$key]['toparent'] || $toparent;
				} else {
					$addressee	= $toparent ? $line['parent'] : array('id'		=> (int) $line['fk_soc'],
																		'name'		=> (string) $line['name'],
																		'code'		=> isset($line['code']) ? (string) $line['code'] : '',
																		'address'	=> isset($line['address']) ? (string) $line['address'] : '',
																		'zip'		=> isset($line['zip']) ? (string) $line['zip'] : '',
																		'town'		=> isset($line['town']) ? (string) $line['town'] : '');
					$code		= !empty($addressee['code']) ? $addressee['code'] : 'ID'.$addressee['id'];
						$units[$key]	= array('suffix'	=> dol_sanitizeFileName($code),
											'label'		=> $addressee['name'],
											'fk_soc'	=> (int) $addressee['id'],
											'toparent'	=> $toparent,
											'addressee'	=> $addressee,
												'lineids'	=> array());
					}
					$units[$key]['lineids'][]	= $line['rowid'];
			}
			return array_values($units);
		}
	}
