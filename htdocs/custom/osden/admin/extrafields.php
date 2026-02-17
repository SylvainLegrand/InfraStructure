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

/* Affichage des extrafields ne respectant pas la syntaxe USF :
- récupérer TOUS les extrafields
- Pour chacun :
  - désérialiser les param
  - s'il n'y a pas de filtre -> continue
  - sinon, vérifier que le filtre a la syntaxe USF (je crois qu'il y a une f() pour ça dans DLB)
    - s'il ne recpecte pas la syntaxe USF, renseigner un tableau :
     [ 'product' => [EF_id => $extrafield, ...], 'commande' => ...]

- afficher le tableau des Ef ne respectant pas la syntaxe USF.

- TODO : Voir ensuite pour les facilitateurs : liens pour éditer les EF directement ?
 */

// Récupérer TOUS les extrafields

$extrafield_static = new ExtraFields($db);
$extrafields = $extrafield_static->fetch_name_optionals_label('all', true);

print '<br>Voir la <a href="https://wiki.dolibarr.org/index.php/Universal_Search_Filter_Syntax#Syntax_of_USF">syntaxe USF</a><br>';

print "<table class='tagtable liste'><tbody>";
foreach($extrafield_static->attributes as $object_type => $extrafields_def) {
    $title_is_printed = false;
    foreach($extrafields_def['param'] as $ef_code => $params) {
        // manage only params with SQL filters
        if (is_array($params)
            && isset($params['options']) && is_array($params['options']) && count($params['options']) == 1
            && reset($params['options']) === null
        ) {
            $sql_params = reset(array_keys($params['options']));
            $sql_arr = explode(':', $sql_params);
            if (count($sql_arr) > 4) {
                // Afficher le titre
                if (!$title_is_printed) {
                    print '<tr class="liste_titre_filter"><td colspan=4 class="liste_titre bold">'.$object_type.'</td></tr>';
                    print '<tr class="liste_titre_filter"><td class="liste_titre">code</td><td class="liste_titre">options</td><td class="liste_titre">Filtre sql</td><td class="liste_titre">SQL</td></tr>';
                    $title_is_printed = true;
                }

                // Le filtre a-t-il la syntaxe USF ?
                $sql_filter = implode(':', array_slice($sql_arr, 4));
                $forged_sql = forgeSQLFromUniversalSearchCriteria($sql_filter);
                $sql_syntax_is_correct = strpos($forged_sql, 'Filter error') !== 0;

                print '<tr class="oddeven"><td>'.$ef_code.'</td><td>'.$sql_params.'</td>';
                print '<td>'.$sql_filter.'</td><td class="'.($sql_syntax_is_correct ? '' : 'error').'">'.$forged_sql.'</td>';
                print '</tr>';
            } 
        }
    }
}


print dol_get_fiche_end();

llxFooter();

$db->close();
