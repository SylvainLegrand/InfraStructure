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

	// InfraS add begin : couleurs par utilisateur (3.6.0) : l'onglet "Couleurs" declare par le descripteur est ajoute en fin de liste par
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
	// InfraS add end

    /*
	public function addHtmlHeader($parameters){
		global $conf;

		$style = "<style id='oblyon_custom_css'>";
		if (getDolGlobalString('OBLYON_CUSTOM_CSS')){
			$style .= getDolGlobalString('OBLYON_CUSTOM_CSS');
		}
		$style .= "</style>";
		
		$this->resprints = $style;
		return 0;
	}
    */
}
