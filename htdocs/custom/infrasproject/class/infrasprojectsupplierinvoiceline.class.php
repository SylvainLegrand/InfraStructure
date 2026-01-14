<?php
	/************************************************
	* Copyright (C) 2021-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		../infrasproject/class/infrasprojectsupplierinvoiceline.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraSProject
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
	require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	if (isModEnabled('accounting')) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
	}
	if (isModEnabled('accounting')) {
		require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
	}

	/************************************************
	* Class Infrasprojectsupplierinvoiceline
	************************************************/
	class Infrasprojectsupplierinvoiceline extends SupplierInvoiceLine
	{
		public $date;	// @var integer	=> Invoice date (date)
		public $thirdparty;	// @var Societe A related thirdparty	=> @see fetch_thirdparty()
		public $statut;	// @var int	Supplier invoice status	=> @see FactureFournisseur::STATUS_DRAFT, FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_PAID, FactureFournisseur::STATUS_ABANDONED

		/**
		* Constructor
		*
		* @param   DATABASE		$db     db object
		* @return						void
		**/
		public function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		*  Return clicable name (with picto eventually)
		*
		*	@param		int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
		*	@param		string	$option						Where point the link
		*	@param		int		$max						Max length of shown ref
		*	@param		int		$short						1=Return just URL
		*	@param		string	$moretitle					Add more text to title tooltip
		*	@param	    int   	$notooltip					1=Disable tooltip
		*	@param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
		*	@param		int		$addlinktonotes				Add link to show notes
		* 	@return		string								String with URL
		**/
		public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1, $addlinktonotes = 0)
		{
			global $langs, $conf, $user;

			$result		= '';
			$factFour	= new FactureFournisseur($this->db);
			$factFour->fetch($this->fk_facture_fourn);
			if ($option == 'withdraw') {
				$url	= DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$this->fk_facture_fourn.'&type=bank-transfer';
			} elseif ($option == 'document') {
				$url	= DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$this->fk_facture_fourn;
			} else {
				$url	= DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$this->fk_facture_fourn;
			}
			if ($short) {
				return $url;
			}
			if ($option !== 'nolink') {
				// Add param to save lastsearch_values or not
				$add_save_lastsearch_values	= ($save_lastsearch_value == 1 ? 1 : 0);
				if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
					$add_save_lastsearch_values	= 1;
				}
				if ($add_save_lastsearch_values) {
					$url	.= '&save_lastsearch_values=1';
				}
			}
			$picto	= $factFour->picto;
			if ($factFour->type == FactureFournisseur::TYPE_REPLACEMENT) {
				$picto	.= 'r'; // Replacement invoice
			}
			if ($factFour->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
				$picto	.= 'a'; // Credit note
			}
			if ($factFour->type == FactureFournisseur::TYPE_DEPOSIT) {
				$picto	.= 'd'; // Deposit invoice
			}
			$label	= img_picto('', $factFour->picto).' <u class = "paddingrightonly">'.$langs->trans('SupplierInvoice').'</u>';
			if ($factFour->type == FactureFournisseur::TYPE_REPLACEMENT) {
				$label	= '<u class = "paddingrightonly">'.$langs->transnoentitiesnoconv('InvoiceReplace').'</u>';
			} elseif ($factFour->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
				$label	= '<u class = "paddingrightonly">'.$langs->transnoentitiesnoconv('CreditNote').'</u>';
			} elseif ($factFour->type == FactureFournisseur::TYPE_DEPOSIT) {
				$label	= '<u class = "paddingrightonly">'.$langs->transnoentitiesnoconv('Deposit').'</u>';
			}
			if (isset($factFour->statut)) {
				$alreadypaid	= -1;
				$label			.= ' '.$this->getLibStatut(5, $alreadypaid);
			}
			if (!empty($factFour->ref)) {
				$label	.= '<br><b>'.$langs->trans('Ref').':</b> '.$factFour->ref;
			}
			if (!empty($factFour->ref_supplier)) {
				$label	.= '<br><b>'.$langs->trans('RefSupplier').':</b> '.$factFour->ref_supplier;
			}
			if (!empty($factFour->label)) {
				$label	.= '<br><b>'.$langs->trans('Label').':</b> '.$factFour->label;
			}
			if (!empty($factFour->date)) {
				$label	.= '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($factFour->date, 'day');
			}
			if (!empty($factFour->total_ht)) {
				$label	.= '<br><b>'.$langs->trans('AmountHT').':</b> '.price($factFour->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($factFour->total_tva)) {
				$label	.= '<br><b>'.$langs->trans('AmountVAT').':</b> '.price($factFour->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($factFour->total_ttc)) {
				$label	.= '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($factFour->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if ($moretitle) {
				$label	.= ' - '.$moretitle;
			}
			$ref	= $factFour->ref;
			if (empty($ref)) {
				$ref	= $this->fk_facture_fourn;
			}
			$linkclose	= '';
			if (empty($notooltip)) {
				if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
					$label		= $langs->trans('ShowSupplierInvoice');
					$linkclose	.= ' alt = "'.dol_escape_htmltag($label, 1).'"';
				}
				$linkclose	.= ' title = "'.dol_escape_htmltag($label, 1).'"';
				$linkclose	.= ' class = "classfortooltip"';
			}
			$linkstart				= '<a href = "'.$url.'"';
			$linkstart				.= $linkclose.'>';
			$linkend				= '</a>';
			$result					.= $linkstart;
			if ($withpicto) {
				$result	.= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class = "paddingright"' : '') : 'class = "'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
			if ($withpicto != 2) {
				$result .= $max ? dol_trunc($ref, $max) : $ref;
			}
			$result					.= $linkend;
			if ($addlinktonotes) {
				$txttoshow	= ($user->socid > 0 ? $factFour->note_public : $factFour->note_private);
				if ($txttoshow) {
					$notetoshow	= $langs->trans('ViewPrivateNote').' :<br>'.dol_string_nohtmltag($txttoshow, 1);
					$result		.= ' <span class = "note inline-block">';
					$result		.= '<a href = "'.DOL_URL_ROOT.'/fourn/facture/note.php?id='.$this->fk_facture_fourn.'" class = "classfortooltip" title = "'.dol_escape_htmltag($notetoshow).'">';
					$result		.= img_picto('', 'note');
					$result		.= '</a>';
					$result		.= '</span>';
				}
			}
			return $result;
		}

		/**
		*	Return label of object status
		*
		*	@param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
		*	@param      integer	$alreadypaid    0=No payment already done, >0=Some payments were already done (we recommand to put here amount payed if you have it, 1 otherwise)
		*	@return     string			        Label of status
		**/
		public function getLibStatut($mode = 0, $alreadypaid = -1)
		{
			$factFour				= new FactureFournisseur($this->db);
			$factFour->fetch($this->fk_facture_fourn);
			$factFour->totalpaye	= $factFour->getSommePaiement();
			$tmptxt					= $factFour->getLibStatut(6, $factFour->totalpaye);
			if (empty($tmptxt) || $tmptxt == $factFour->getLibStatut(3)) {
				$tmptxt = $factFour->getLibStatut(5, $factFour->totalpaye);
			}
			return $tmptxt;
		}

		/**
		*	Load the third party of object, from id $this->socid or $this->fk_soc, into this->thirdparty
		*
		*	@param		int		$force_thirdparty_id	Force thirdparty id
		*	@return		int								<0 if KO, >0 if OK
		**/
		public function fetch_thirdparty($force_thirdparty_id = 0)
		{
			global $conf;

			$factFour		= new FactureFournisseur($this->db);
			$factFour->fetch($this->fk_facture_fourn);
			$this->date		= $factFour->date;
			$this->statut	= $factFour->statut;
			$sql			= 'SELECT fk_soc FROM '.$this->db->prefix().'facture_fourn_det WHERE rowid = '.$this->id;
			$resql			= $this->db->query($sql);
			dol_syslog(get_class($this).'::fetch_thirdparty sql = '.$sql);
			if ($resql) {
				$obj_prj	= $this->db->fetch_object($resql);
				if (!empty($obj_prj->fk_soc)) {
					$idtofetch	= $obj_prj->fk_soc;
				} else {
					return 0;
				}
			} else {
				return -1;
			}
			$this->db->free($resql);
			if ($force_thirdparty_id) {
				$idtofetch	= $force_thirdparty_id;
			}
			if ($idtofetch) {
				$thirdparty	= new Societe($this->db);
				$result		= $thirdparty->fetch($idtofetch);
				$this->thirdparty	= $thirdparty;
				if (getDolGlobalString('PRODUIT_MULTIPRICES') && empty($this->thirdparty->price_level)) {
					$this->thirdparty->price_level	= 1;	// Use first price level if level not defined for third party
				}
				return $result;
			} else {
				return -1;
			}
		}
	}
