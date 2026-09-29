<?php
/* Copyright (C) 2022 Paul LEPONT           <paul@kawagency.fr>
/* Copyright (C) 2022 Alexandre Spangaro    <alexandre@inovea-conseil.com>
/* Copyright (C) 2022-2026  Sylvain Legrand      <contact@infras.fr>
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
 * Class ActionsOblyon
 */
class ActionsOblyon
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
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	// couleurs par utilisateur (3.6.0) : l'onglet "Couleurs" declare par le descripteur est ajoute en fin de liste par
	// complete_head_from_modules() ; ce hook le deplace juste apres l'onglet core "Interface utilisateur" (guisetup) de la fiche utilisateur
	/**
	 * Overloading the completeTabsHead function : reorder the tabs of the user card
	 *
	 * @param	array		$parameters		Hook metadata (object, mode, head (by reference), filterorigmodule ; type only in Dolibarr >= 24)
	 * @param	object		$object			Current object
	 * @param	string		$action			Current action
	 * @param	HookManager	$hookmanager	Hook manager
	 * @return	int							0 = head modified in place, nothing to merge
	 */
	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		// 'type' is only given by Dolibarr >= 24 : the user card is recognised by its core "guisetup" tab and our own tab (both present only there)
		if (empty($parameters['mode']) || $parameters['mode'] != 'add' || empty($parameters['head']) || !is_array($parameters['head'])) {
			return 0;
		}
		$head	= $parameters['head'];
		$ours	= null;
		foreach ($head as $key => $tab) {
			if (isset($tab[2]) && $tab[2] == 'oblyoncolors') {
				$ours	= $tab;
				unset($head[$key]);
				break;
			}
		}
		if ($ours === null) {
			return 0;
		}
		$newhead	= array();
		$placed		= false;
		foreach ($head as $tab) {
			$newhead[]	= $tab;
			if (!$placed && isset($tab[2]) && $tab[2] == 'guisetup') {
				$newhead[]	= $ours;
				$placed		= true;
			}
		}
		if (!$placed) {
			$newhead[]	= $ours;	// no "Display setup" tab (should not happen) : keep it at the end
		}
		$parameters['head']	= $newhead;	// 'head' is passed by reference by complete_head_from_modules()
		return 0;
	}

	// zone haut-droite (3.8.0), deux options imprimees dans div.login_block_other juste avant le bloc utilisateur du core :
	// - OBLYON_USER_BLOCK (initials / photo) : marqueur cache (mode, initiales, photo renseignee ou non) lu par js/oblyon.js, qui remplace dans le navigateur les deux
	//   images du bloc utilisateur (barre et en-tete du menu deroulant) par un cercle aux initiales ; le menu deroulant redessine est du CSS pur (dropdown.inc.php)
	// - OBLYON_NOTIFICATION_CENTER : cloche ; le contenu (messages jNotify interceptes, compteur, liste, marquage lu) est entierement gere par js/oblyon.js dans le
	//   navigateur (localStorage par utilisateur) : aucune table, aucun appel serveur. Les libelles passent au JS par des attributs data-lbl-* (aucun texte en dur dans le script)
	/**
	 * Overloading the printTopRightMenu function : print the user block marker and the notification bell
	 *
	 * @param	array		$parameters		Hook metadata (context, etc...)
	 * @param	object		$object			Current object
	 * @param	string		$action			Current action
	 * @param	HookManager	$hookmanager	Hook manager
	 * @return	int							0 = output added to the top right area
	 */
	public function printTopRightMenu($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user;

		if (empty($user->id) || empty($conf->use_javascript_ajax) || GETPOST('optioncss', 'aZ09') == 'print') {
			return 0;
		}
		$out		= '';
		$userblock	= getDolGlobalString('OBLYON_USER_BLOCK', 'default');
		if ($userblock == 'initials' || $userblock == 'photo') {
			$initials	= dol_substr(trim((string) $user->firstname), 0, 1).dol_substr(trim((string) $user->lastname), 0, 1);	// prenom + nom, sinon les deux premieres lettres de l'identifiant
			if (dol_strlen($initials) < 2) {
				$initials	= dol_substr(trim((string) $user->login), 0, 2);
			}
			$out	.= '<span class="oblyon-userblock" hidden data-mode="'.$userblock.'" data-initials="'.dol_escape_htmltag(dol_strtoupper($initials)).'" data-hasphoto="'.(empty($user->photo) ? '0' : '1').'"></span>';
		}
		if (getDolGlobalInt('OBLYON_NOTIFICATION_CENTER')) {
			$langs->load('oblyon@oblyon');
			$labels	= array('title' => 'OblyonNotifTitle', 'empty' => 'OblyonNotifEmpty', 'markread' => 'OblyonNotifMarkRead', 'clear' => 'OblyonNotifClear',
							'delete' => 'OblyonNotifDelete', 'now' => 'OblyonNotifNow', 'min' => 'OblyonNotifMin', 'hour' => 'OblyonNotifHour', 'day' => 'OblyonNotifDay');
			$out	.= '<div class="login_block_elem oblyon-notif" data-userid="'.((int) $user->id).'"';
			foreach ($labels as $key => $transkey) {
				$out	.= ' data-lbl-'.$key.'="'.dol_escape_htmltag($langs->transnoentitiesnoconv($transkey, '%s')).'"';	// '%s' passe en argument : trans() fait un sprintf, le %s des libelles relatifs est conserve pour le JS
			}
			$out	.= '>';
			$out	.= '<a href="#" class="oblyon-notif-btn" role="button" aria-haspopup="true" aria-expanded="false" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv('OblyonNotifTitle')).'">';
			$out	.= '<span class="fa fa-bell" aria-hidden="true"></span><span class="oblyon-notif-count" hidden></span></a>';
			$out	.= '<div class="oblyon-notif-panel" hidden>';
			$out	.= '<div class="oblyon-notif-head"><span class="oblyon-notif-title">'.$langs->trans('OblyonNotifTitle').'</span>';
			$out	.= '<span class="oblyon-notif-actions"><a href="#" class="oblyon-notif-readall">'.$langs->trans('OblyonNotifMarkRead').'</a><a href="#" class="oblyon-notif-clear">'.$langs->trans('OblyonNotifClear').'</a></span></div>';
			$out	.= '<ul class="oblyon-notif-list"></ul><p class="oblyon-notif-empty">'.$langs->trans('OblyonNotifEmpty').'</p></div></div>';
		}
		if ($out === '') {
			return 0;
		}
		$this->resprints	= $out;
		return 0;
	}
}
