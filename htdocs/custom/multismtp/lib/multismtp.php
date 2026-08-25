<?php

/**
 * Copyright © 2015-2016 Marcos García de La Fuente <hola@marcosgdf.com>
 *
 * This file is part of Multismtp.
 *
 * Multismtp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Multismtp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Multismtp.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function multismtp_admin_prepare_head()
{
    global $langs, $conf, $user;
    
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/multismtp/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    // InfraS add begin
    $head[$h][0] = dol_buildpath("/multismtp/admin/smtp2go.php", 1);
    $head[$h][1] = $langs->trans("Smtp2goTab");
    $head[$h][2] = 'smtp2go';
    $h++;
    // InfraS add end

    $head[$h][0] = dol_buildpath("/multismtp/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About") . " / " . $langs->trans("Support");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/multismtp/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'multismtp_admin');

    return $head;
}
