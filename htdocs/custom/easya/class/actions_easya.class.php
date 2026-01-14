<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


class ActionsEasya
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
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
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the printTopRightMenu function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function printLeftBlock($parameters, &$object, &$action, $hookmanager)
    {

        $this->resprints = '';

        $maintenance_file_path = getDolGlobalString('EASYA_MAINTENANCE_FILE');
        if (!empty($maintenance_file_path) && file_exists(dol_buildpath(preg_replace('/^\/htdocs/', '', $maintenance_file_path), 0, 2))) {
            
            $add_style = '';
            if (isModEnabled('oblyon')) {
                $add_style = 'position: relative; top: 50px;';
            }
            
            $this->resprints.= '<script>';
            $this->resprints.= '    $(() => {
                $(".side-nav-vert").before(\'<div class="warning bold center" style="background-color: red; color: white !important; font-size: 20px; '.$add_style.'">MODE MAINTENANCE</div>\') 
                $("#tmenu_tooltipinvert").css("position: fixed; top: 50px;")
                $("#tmenu_tooltip").css("position: fixed; top: 50px;") 
                $(".side-nav").css("position: relative; top: 50px;") 
                $(".login_block .usedropdown").css("position: relative; top: 50px;") 
            })';
            $this->resprints.= '</script>';
        }
        
        return 0;
    }
}