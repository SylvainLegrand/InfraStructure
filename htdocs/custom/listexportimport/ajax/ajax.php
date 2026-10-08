<?php
/* Copyright (C) 2017	AXeL dev	<contact.axel.dev@gmail.com>
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
 *       \file       /staff/ajax/ajax.php
 *       \brief      File to do ajax actions
 */

define('NOTOKENRENEWAL', 1);

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) { // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

global $db, $langs, $user, $conf;

//require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
dol_include_once("/listexportimport/lib/listexportimport.lib.php");

// Get parameters
$action	= GETPOST('action','alpha');
$url = GETPOST('url','alpha');

// Access control
if (!$user->hasRight('listexportimport', 'export') && !$user->hasRight('listexportimport', 'import')) { // InfraS change
	// External user
	accessforbidden();
}

/*
 * View
 */

$langs->load('listexportimport@listexportimport');

top_httphead();

// InfraS add begin
// Refuse inactive formats and tables holding permissions or credentials
$requiredformats = array(
	'export_sql'			=> array('export', array('sql')),
	'export_csv_from_db'	=> array('export', array('csvfromdb')),
	'import_sql'			=> array('import', array('sql', 'csv')),	// CSV import ends with an import_sql call
	'import_csv'			=> array('import', array('csv')),
);
if (in_array($action, array('export_sql', 'export_csv_from_db', 'import_sql', 'import_csv', 'free_sql'))) {
	$allowed = true;
	if (isset($requiredformats[$action])) {
		$allowed = listExportImportIsFormatActive($db, $requiredformats[$action][0], $requiredformats[$action][1]);
	}
	$checkedmoduleinfo = getModuleinfoFromUrl($url);
	$checkedobject = null;
	$checkedtablename = getTablename($db, $checkedmoduleinfo, $checkedobject);
	if ($allowed && !empty($checkedtablename)) {
		$allowed = listExportImportIsTableAllowed($db, $checkedtablename, 1);
		if ($allowed && ($action == 'export_sql' || $action == 'free_sql' || $action == 'import_sql')) {
			foreach (getMoreTablenames($checkedtablename, $checkedmoduleinfo, $checkedobject) as $checkedtable) {
				if (!listExportImportIsTableAllowed($db, $checkedtable, 0)) {
					$allowed = false;
					break;
				}
			}
		}
		// Rights of the user on the objects of the list (read, and create for an import)
		if ($allowed && $action != 'free_sql') {
			$allowed = listExportImportCheckObjectRights($user, $checkedobject, $checkedtablename, (strpos($action, 'import_') === 0 ? 1 : 0));
		}
	}
	if (!$allowed) {
		dol_syslog('listexportimport: action '.$action.' refused for table '.$checkedtablename.' (user '.$user->login.')', LOG_WARNING);
		if ($action == 'export_sql' || $action == 'export_csv_from_db') {
			http_response_code(403);	// The export response is saved as a file: an error status lets the page show the message instead
		}
		print $langs->transnoentities('NotEnoughPermissions');
		exit;
	}
}
// InfraS add end

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Actions
if (isset($action) && ! empty($action))
{
	if ((($action == 'export_sql' || $action == 'export_csv_from_db') && $user->hasRight('listexportimport', 'export')) || ($action == 'free_sql' && getDolGlobalString('LIST_EXPORT_IMPORT_ENABLE_FREE_LIST') && $user->admin && $user->hasRight('listexportimport', 'import'))) // InfraS change
	{
        $moduleinfo = getModuleinfoFromUrl($url);
            
            $object = null; // InfraS add
            $tablename = getTablename($db, $moduleinfo, $object); // InfraS change
            
            if (! empty($tablename))
            {
                $moretablenames = getMoreTablenames($tablename, $moduleinfo, $object); // InfraS change

                // get table content
                if ($action == 'export_sql' || $action == 'export_csv_from_db')
                {
                    $export_script = '';
                    $ignore_fields = array();//array('id', 'rowid');
                    $to_csv = $action == 'export_csv_from_db';

                    // InfraS change begin
                    // Only rows of the entities of the user
                    $entityfilter = listExportImportGetEntityFilter($db, $object, $tablename);
                    $export_script.= exportTable($db, $tablename, $ignore_fields, $to_csv, $entityfilter['filter']);

                    if (! $to_csv)
                    {
                        foreach ($moretablenames as $table)
                        {
                            $entityfilter = listExportImportGetEntityFilter($db, $object, $table, $tablename);
                            if ($entityfilter === false) {
                                dol_syslog('listexportimport: lines of '.$table.' not exported, no link to '.$tablename.' to filter entities', LOG_WARNING);
                                continue;
                            }
                            $export_script.= exportTable($db, $table, $ignore_fields, $to_csv, $entityfilter['filter']);
                        }
                    }
                    // InfraS change end

                    print $export_script;
                } // fin if ($action == 'export_sql')
                else if ($action == 'free_sql')
                {
                    // remove related tables first (to avoid foreign key errors)
                    $error = 0;
                    // InfraS add begin
                    // Only rows of the entities of the user
                    $entityfilters = array($tablename => listExportImportGetEntityFilter($db, $object, $tablename));
                    foreach ($moretablenames as $table) {
                        $entityfilters[$table] = listExportImportGetEntityFilter($db, $object, $table, $tablename);
                        if ($entityfilters[$table] === false) {
                            $error++;
                            print $langs->transnoentities('NotEnoughPermissions');
                            break;
                        }
                    }
                    // InfraS add end
                    foreach ($moretablenames as $table)
                    {
                        if ($error) break; // InfraS add
                        $sql = 'DELETE FROM `'.$table.'`'.(!empty($entityfilters[$table]['filter']) ? ' WHERE '.$entityfilters[$table]['filter'] : ''); // InfraS change

                        $resql = $db->query($sql);

                        if ($resql) {}
                        else {
                            $error++;
                            print "Error: ".$db->lasterror();
                            break;
                        }
                    }

                    if (! $error)
                    {
                        $sql = 'DELETE FROM `'.$tablename.'`'.(!empty($entityfilters[$tablename]['filter']) ? ' WHERE '.$entityfilters[$tablename]['filter'] : ''); // InfraS change

                        $resql = $db->query($sql);

                        if ($resql)
                        {
                            print 'success';
                        }
                        else
                        {
                            print "Error: ".$db->lasterror();
                        }
                    }
                } // fin if ($action == 'free_sql')
                //print 'modulename: '.$moduleinfo['name'].', tablename: '.$tablename;
            }
        } // fin if ($action == 'export_sql' || $action == 'free_sql')
        else if ($action == 'import_sql' && $user->hasRight('listexportimport', 'import')) // InfraS change
        {
            // InfraS change begin
            // Content sent in base64 so that the input filters don't alter the data (double quotes, html tags)
            // The old raw param is still read for pages loaded before the update (javascript cached for one hour)
            $sqlb64 = GETPOST('sqlb64', 'alphanohtml');
            $sql = ($sqlb64 !== '' ? listExportImportDecodeBase64($sqlb64) : GETPOST('sql'));
            // InfraS change end
            $filename = GETPOST('filename','alpha');
            $is_sql = preg_match('/\.sql$/i', $filename);

            if ($is_sql)
            {
                if (! empty($sql))
                {
                    // reverse sql to origin (!@! sql was reversed to bypass dolibarr sql injection detection)
                    //$sql_words_origin = array('INSERT', 'INTO', 'VALUES');
                    $reversed_sql_words = array('TRESNI', 'OTNI', 'SEULAV');

                    // InfraS change begin
                    if ($sqlb64 === '') {	// The base64 content is not reversed
                        foreach($reversed_sql_words as $word) {
                            $sql = str_replace($word, strrev($word), $sql);
                        }
                    }
                    // InfraS change end

                    // InfraS change begin
                    // check if the sql code is for the current list, then rebuild its queries from the values it contains (the sql of the file is never run as is)
                    $moduleinfo = getModuleinfoFromUrl($url);
                    $object = null;
                    $tablename = getTablename($db, $moduleinfo, $object);

                    if (empty($tablename))
                    {
                        print $langs->transnoentities('FileContentNotMatchWithTableName', 'SQL');
                    }
                    else
                    {
                        $linetablenames = array();
                        foreach (getMoreTablenames($tablename, $moduleinfo, $object) as $table) {
                            if (count(listExportImportGetColumns($db, $table)) > 0) {
                                $linetablenames[] = $table;
                            }
                        }
                        $import = listExportImportPrepareImport($db, $object, $tablename, $linetablenames, $sql);
                        $error = 0;

                        if (!empty($import['error']))
                        {
                            $error++;
                            print trim($langs->transnoentities($import['error'], ($import['error'] == 'FileContentNotMatchWithTableName' ? 'SQL' : '')));
                        }
                        else
                        {
                            // execute sql: all or nothing
                            $db->begin();
                            foreach ($import['queries'] as $query)
                            {
                                if (! $db->query($query)) {
                                    $error++;
                                    print "Error: ".$db->lasterror();
                                    break;
                                }
                            }
                            // imported lines must belong to documents of the entities of the user
                            if (! $error)
                            {
                                foreach ($import['parentchecks'] as $query)
                                {
                                    $resql = $db->query($query);
                                    $obj = ($resql ? $db->fetch_object($resql) : null);
                                    if (! $obj || (int) $obj->nb > 0) {
                                        $error++;
                                        print $langs->transnoentities('NotEnoughPermissions');
                                        break;
                                    }
                                }
                            }
                            if ($error) {
                                $db->rollback();
                            } else {
                                $db->commit();
                            }
                        }

                        if (! $error)
                        {
                            print 'success';
                        }
                    } // fin else if (empty($tablename))
                    // InfraS change end
                }
                else
                {
                    print $langs->transnoentities('FileIsEmpty'); // InfraS change : plain text, shown by alert()
                }
            }
            else
            {
                print $langs->transnoentities('WrongFileExt', 'SQL'); // InfraS change : plain text, shown by alert()
            }
        } // fin if ($action == 'import_sql' && $user->rights->listexportimport->import)
        else if ($action == 'import_csv' && $user->hasRight('listexportimport', 'import')) // InfraS change
        {
            $csvb64 = GETPOST('csvb64', 'alphanohtml'); // InfraS add
            $csv = ($csvb64 !== '' ? listExportImportDecodeBase64($csvb64) : GETPOST('csv'));//,'alpha'); // InfraS change
            $filename = GETPOST('filename','alpha');
            $is_csv = preg_match('/\.csv$/i', $filename);
            
            if ($is_csv)
            {
                if (! empty($csv))
                {
                    // get tablename
                    $moduleinfo = getModuleinfoFromUrl($url);
                    $tablename = getTablename($db, $moduleinfo);
                    
                    // convert csv to sql
                    $export_script = exportCSV($csv, $tablename);
                    
                    print $export_script;
                }
                else
                {
                    print $langs->transnoentities('FileIsEmpty'); // InfraS change : plain text, shown by alert()
                }
            }
            else
            {
                //print $langs->trans('WrongFileExt', 'CSV');
                print 'wrongfile';
            }
        }
}
