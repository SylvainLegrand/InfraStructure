<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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

/**
 *	    \file       htdocs/osden/admin/about.php
 *		\ingroup    osden
 *		\brief      Page about of osden module
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/osden/lib/osden.lib.php');
dol_include_once('/osden/core/modules/modosden.class.php');

$langs->load("admin");
$langs->load("osden@osden");
$langs->load("opendsi@osden");

if (!$user->admin) accessforbidden();


/**
 * View
 */

 $wikihelp = 'N:Osden_En|FR:Osden_Fr|ES:Osden_Es';
 llxHeader('', $langs->trans("OsdenSetup"), $wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("OsdenSetup"),$linkback,'title_setup');
print "<br>\n";


$head=osden_prepare_head();


print dol_get_fiche_head($head, 'about', $langs->trans("Module163082Name"), 0, 'opendsi@osden');



/* print '<table width="100%"><tr>'."\n";
print '<td width="310px"><img src="../img/opendsi_dolibarr_preferred_partner.png" /></td>'."\n";
print '<td align="left" valign="top"><p>'.$langs->trans("OpenDsiAboutDesc").'</p></td>'."\n";
print '</tr></table>'."\n"; */



$modClass = new modosden($db);
$osdenVersion = !empty($modClass->getVersion()) ? $modClass->getVersion() : 'NC';

$supportvalue = "/*****"."<br>";
$supportvalue.= " * Module : ".$langs->trans("Module163082Name")."<br>";
$supportvalue.= " * Module version : ".$osdenVersion."<br>";
$supportvalue.= " * Dolibarr version : ".DOL_VERSION."<br>";
$supportvalue.= " * Dolibarr version installation initiale : ".$conf->global->MAIN_VERSION_LAST_INSTALL."<br>";
$supportvalue.= " * Version PHP : ".PHP_VERSION."<br>";
$supportvalue.= " *****/"."<br><br>";
$supportvalue.= "Description de votre problème :"."<br>";


print '<form id="ticket" method="POST" target="_blank" action="https://support.osden.solutions/create_ticket.php">';
print '<input name=message type="hidden" value="'.$supportvalue.'" />';
print '<input name=email type="hidden" value="'.$user->email.'" />';
print '<table class="centpercent"><tr>'."\n";
print '<td width="310px"><img src="../img/opendsi_dolibarr_preferred_partner.png" /></td>'."\n";
print '<td valign="top"><p>'.$langs->trans("OpenDsiAboutDesc1").' <button type="submit" >'.$langs->trans("OpenDsiAboutDesc2").'</button> '.$langs->trans("OpenDsiAboutDesc3").'</p></td>'."\n";
print '</tr></table>'."\n";
print '</form>'."\n";

print dol_get_fiche_end();

llxFooter();

$db->close();
