<?php
/* Copyright (C) 2017       AXeL                    <contact.axel.dev@gmail.com>
 * Copyright (C) 2024-2026  Inovea Conseil          <info@inovea-conseil.com>
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
 *	\file		lib/listexportimport.lib.php
 *	\ingroup	listexportimport
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function listexportimportAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("listexportimport@listexportimport");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/listexportimport/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Settings");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/listexportimport/admin/doc.php", 1);
    $head[$h][1] = $langs->trans("Documentation");
    $head[$h][2] = 'doc';
    $h++;
    $head[$h][0] = dol_buildpath("/listexportimport/admin/support.php", 1);
    $head[$h][1] = $langs->trans("Support") . ' / ' . $langs->trans("About");
    $head[$h][2] = 'support';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@listexportimport:/listexportimport/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@listexportimport:/listexportimport/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'listexportimport');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'listexportimport', 'remove');

    return $head;
}

function getButton($picto, $title='', $alt='', $class='export', $width='20')
{
    $link = '<a href="#" class="'.$class.'" style="text-decoration: none;" title="'.$alt.'">';
    $endlink = '</a>';
    $img = ' <img src="'.$picto.'" title="'.$title.'" alt="'.$alt.'" style="vertical-align: middle !important;" width="'.$width.'" />';	// InfraS change

    $button = $link . $img . $endlink;
    
    return $button;
}

function getCompactedButtons($formats, $name, $picto, $morebuttons=array())
{
    global $langs;
    
    $compacted = '<div class="dropdown-click">';
    $compacted.= '<label class="drop-btn button">';
    $compacted.= '<img class="align-middle" title="" alt="" src="'.$picto.'" width="18" />';
    $compacted.= '&nbsp;'.$name.'&nbsp;&nbsp;<img class="align-middle" title="" alt="" src="'.dol_buildpath('/listexportimport/img/arrow-down.png', 1).'" /></label>';
    $compacted.= '<div class="dropdown-content dropdown-bottom">';
    foreach ($formats as $format)
    {
        if ($format->active)
        {
            $format->title = $langs->trans($format->title);
            $format->picto = dol_buildpath('/listexportimport/img/'.$format->picto, 1);
            
            $compacted.= '<div class="'.$format->type.'" title="'.$format->format.'">';
            $compacted.= '<a href="#" style="min-width: 85px;" title="'.$format->title.'">';
            $compacted.= '<img src="'.$format->picto.'" title="'.$format->title.'" alt="'.$format->format.'" class="align-middle" width="20" />';
            if ($format->format == 'csvfromdb') {
                $format->format = 'csv';
            }
            $compacted.= '&nbsp;&nbsp;'.strtoupper($format->format);
            if (! empty($format->warning)) {
                $compacted.= '&nbsp;&nbsp;'.img_warning($langs->trans($format->warning));
            }
            $compacted.= '</a>';
            $compacted.= '</div>';
        }
    }
    
    // More buttons
    foreach ($morebuttons as $button)
    {
        if ($button['active'])
        {
            $button['title'] = $langs->trans($button['title']);
            $button['picto'] = dol_buildpath('/listexportimport/img/'.$button['picto'], 1);
            
            $compacted.= '<div class="'.$button['class'].'" title="'.$button['alt'].'">';
            $compacted.= '<a href="#" style="min-width: 85px;" title="'.$button['title'].'">';
            $compacted.= '<img src="'.$button['picto'].'" title="'.$button['title'].'" alt="'.$button['alt'].'" class="align-middle" width="20" />';
            if ($button['alt'] == 'csvfromdb') {
                $button['alt'] = 'csv';
            }
            $compacted.= '&nbsp;&nbsp;'.strtoupper($button['alt']).'</a>';
            $compacted.= '</div>';
        }
    }
    
    $compacted.= '</div></div>';
    
    return $compacted;
}

function exportTable($db, $tablename, $ignore_fields, $to_csv=0, $filter='') // InfraS change
{
    global $conf;

    $export = '';
    $delim = getDolGlobalString('EXPORT_CSV_SEPARATOR_TO_USE', ';');

    $sql = 'SELECT * FROM '.$tablename;
    $sql.= (!empty($filter) ? ' WHERE '.$filter : ''); // InfraS add

    $resql = $db->query($sql);

    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        if ($num > 0 && !$to_csv) {
            $export.= 'INSERT INTO `'.$tablename.'` VALUES ';
        }

        while ($obj = $db->fetch_object($resql))
        {
            // to csv
            if ($to_csv)
            {
                // get columns
                if ($i == 0)
                {
                    foreach($obj as $key => $value)
                    {
                        $export.= "\"".str_replace('"', '""', $key)."\"".$delim; // InfraS change
                    }

                    $export = substr($export, 0, -1); // remove the last delimiter
                    $export.= PHP_EOL; // system EOL (End Of Line).
                }

                // get data/values
                foreach($obj as $key => $value)
                {
                    if (in_array($key, $ignore_fields) || is_null($value)) {
                        $export.= "\"null\"".$delim;
                    }
                    else {
                        $export.= "\"".str_replace('"', '""', $value)."\"".$delim; // InfraS change : standard csv quoting (double quotes doubled, delimiter and line breaks kept inside the quotes), no need to $db->escape() here, will be done on import
                    }
                }
                
                $export = substr($export, 0, -1); // remove the last delimiter
                
                if ($i < $num - 1) {
                    $export.= PHP_EOL; // system EOL (End Of Line).
                }
                
                $i++;
            }
            else
            {// sql
                $export.= '(';

                foreach($obj as $key => $value)
                {
                    if (in_array($key, $ignore_fields) || is_null($value)) {
                        $export.= "null,";
                    }
                    else {
                        $export.= "'".$db->escape($value)."',";
                    }
                }

                $export = substr($export, 0, -1); // remove the last ','
                $export.= '),';
            }
        }

        if ($num > 0 && !$to_csv) {//if (! empty($export)) {
            $export = substr($export, 0, -1); // remove the last ','
            $export.= ';';
        }
    }
    
    return $export;
}

function exportCSV($csv, $tablename)
{
    global $db, $conf;
    $export = '';
    $delim = getDolGlobalString('EXPORT_CSV_SEPARATOR_TO_USE', ';');

    if (! empty($tablename))
    {
        $export.= 'INSERT INTO `'.$tablename.'` ';

        // InfraS change begin
        // Standard csv reading: quoted values may hold the delimiter, line breaks and doubled double quotes
        $rows = listExportImportParseCsv($csv, $delim);
        if ($rows === false || count($rows) < 2) {
            return '';
        }
        foreach ($rows[0] as $col) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $col)) {
                return '';	// The first row must hold the column names
            }
        }
        // InfraS change end

        $i = 0;

        foreach($rows as $cols) // InfraS change
        {
            $export.= '(';

            foreach($cols as $col)
            {
                if ($i == 0) {
                    $export.= "`".$col."`,";
                }
                else if (is_null($col) || $col == 'null') {
                    $export.= "null,";
                }
                else {
                    $export.= "'".$db->escape($col)."',";
                }
            }

            $export = substr($export, 0, -1); // remove the last ','
            if ($i == 0) {
                $export.= ') VALUES ';
            }
            else {
                $export.= '),';
            }

            $i++;
        }

        if (! empty($export)) {
            $export = substr($export, 0, -1); // remove the last ','
            $export.= ';';
        }
    }
    
    return $export;
}

function getModuleinfoFromUrl($url)
{
    $moduleinfo = array();
    
    if (! empty($url))
    {
        $url_parts = parse_url($url);
        $path_parts = pathinfo($url_parts["path"]);
        $dir_parts = explode('/', $path_parts['dirname']);

        $dir_parts_count = count($dir_parts);
        $moduleinfo['name'] = $dir_parts[$dir_parts_count - 1];//end($dir_parts);
        $moduleinfo['name_before'] = $dir_parts[$dir_parts_count - 2];
    }
    
    return $moduleinfo;
}

function getTablename($db, $moduleinfo, &$object = null) // InfraS change
{
    $tablename = '';
    
    if (is_array($moduleinfo) && count($moduleinfo) > 0)
    {
        //$classfilename = '';
        //$classname = '';
        $modulename = $moduleinfo['name'];
        $modulename_before = $moduleinfo['name_before'];

        // try to get class file name (!@! pay attention to cases order !@!)
        switch ($modulename)
        {
            case 'product_returns':
                $classfilename = 'returnedProduct';
                $classname = 'ReturnedProduct';//ucfirst($classfilename);
                break;
            case 'timesheet':
                $classfilename = $modulename;
                $classname = ucfirst($classfilename);
                $modulename = 'staff';//$modulename_before;
                break;
            case 'bank':
                $classfilename = 'account';
                $classname = ucfirst($classfilename);
                $modulename = 'compta/bank';//$modulename_before.'/'.$modulename;
                break;
            case 'supplier_proposal':
                $classfilename = 'supplier_proposal';//$modulename;
                $classname = 'SupplierProposal';
                break;
            case 'facture':
                if ($modulename_before == 'fourn') {
                    $classfilename = 'fournisseur.facture';
                    $classname = 'FactureFournisseur';
                    $modulename = 'fourn';//$modulename_before;
                    break;
                }
            case 'propal':
                $classfilename = $modulename;
                $classname = ucfirst($classfilename);
                $modulename = $modulename_before.'/'.$modulename;
                break;
            case 'commande':
                if ($modulename_before == 'fourn') {
                    $classfilename = 'fournisseur.commande';
                    $classname = 'CommandeFournisseur';
                    $modulename = 'fourn';//$modulename_before;
                    break;
                }
            default:
                $classfilename = $modulename;
                $classname = ucfirst($classfilename);
        }

        $classpath = dol_buildpath('/'.$modulename.'/class/'.$classfilename.'.class.php');

        // try to get tablename
        if (is_file($classpath)) {
            include_once $classpath;

            $module = new $classname($db);
            $object = $module; // InfraS add

            $tablename =! empty($module->table_element) ? MAIN_DB_PREFIX.$module->table_element : MAIN_DB_PREFIX.$classfilename;
        }
    }
    
    return $tablename;
}

function getMoreTablenames($tablename, $moduleinfo, $object = null) // InfraS change
{
    // get more table names if needed/exists
    $moretablenames = array();

    // InfraS add begin
    // Line table declared by the object of the list (the name built below is wrong for some objects)
    if (is_object($object) && !empty($object->table_element_line)) {
        return array(MAIN_DB_PREFIX.$object->table_element_line);
    }
    // InfraS add end

    if (is_array($moduleinfo) && count($moduleinfo) > 0)
    {
        switch ($moduleinfo['name'])
        {
            case 'timesheet':
                $moretablenames[] = MAIN_DB_PREFIX.'staff_timesheet_log';
                break;
            case 'propal':
            case 'commande':
            case 'facture':
                if ($moduleinfo['name_before'] == 'fourn') {
                    $moretablenames[] = $tablename.($moduleinfo['name'] == 'commande' ? 'det' : '_det'); // InfraS change : purchase order lines are in commande_fournisseurdet, vendor invoice lines in facture_fourn_det
                    break;
                }
            case 'supplier_proposal':
            default:
                $moretablenames[] = $tablename.'det';
                break;
        }
    }

    return $moretablenames;
}

// InfraS add begin
/**
 * Check that at least one of the given formats is active
 *
 * @param	DoliDB		$db			Database handler
 * @param	string		$type		Format type (export or import)
 * @param	string[]	$formats	Format codes (sql, csv, csvfromdb...)
 * @return	bool					True if one of the formats is active
 */
function listExportImportIsFormatActive($db, $type, $formats)
{
    dol_include_once('/listexportimport/class/listexportimport.class.php');

    $list = new ListExportImport($db);
    if ($list->getFormats($type) < 0) {
        return false;
    }

    foreach ($list->formats as $format) {
        if (in_array($format->format, $formats)) {
            return true;
        }
    }

    return false;
}

/**
 * Check that a table can be exported, imported or emptied from a list
 * Tables holding permissions or credentials (passwords, API keys, tokens, secrets, IBAN) are refused
 *
 * @param	DoliDB	$db			Database handler
 * @param	string	$tablename	Table name with prefix
 * @param	int		$mustexist	1 = refuse a table whose columns cannot be read, 0 = accept it (missing table, nothing to process)
 * @return	bool				True if the table is allowed
 */
function listExportImportIsTableAllowed($db, $tablename, $mustexist = 1)
{
    $forbiddentables = array('const', 'rights_def', 'user', 'user_rights', 'usergroup', 'usergroup_rights', 'usergroup_user', 'societe_account', 'oauth_token', 'oauth_state');

    $shortname = strtolower(preg_replace('/^'.preg_quote(MAIN_DB_PREFIX, '/').'/', '', $tablename));
    if (in_array($shortname, $forbiddentables)) {
        return false;
    }

    $columns = listExportImportGetColumns($db, $tablename);
    if (empty($columns)) {
        return empty($mustexist);
    }

    foreach ($columns as $column) {
        if (preg_match('/(^|_)(pass|pass_crypted|pass_temp|password|pwd|pw)$|secret|token|api_?key|key_account|iban/i', $column)) {
            return false;
        }
    }

    return true;
}

/**
 * Get the columns of a table
 *
 * @param	DoliDB		$db			Database handler
 * @param	string		$tablename	Table name with prefix
 * @return	string[]				Column names indexed by their lower case name, empty if the table does not exist
 */
function listExportImportGetColumns($db, $tablename)
{
    static $cache = array();

    if (!isset($cache[$tablename])) {
        $cache[$tablename] = array();
        foreach ($db->DDLInfoTable($tablename) as $column) {
            $cache[$tablename][strtolower($column[0])] = $column[0];
        }
    }

    return $cache[$tablename];
}

/**
 * Check the rights of a user on the objects of a list: read right, and create right for an import
 * Users restricted to some thirdparties (external users, users who can't see all thirdparties) are refused
 * when the table is linked to thirdparties, because a whole table can't be filtered like the list
 *
 * @param	User			$user		User to check
 * @param	CommonObject	$object		Object of the list
 * @param	string			$tablename	Table name with prefix
 * @param	int				$write		1 = also check the create right
 * @return	bool						True if allowed
 */
function listExportImportCheckObjectRights($user, $object, $tablename, $write = 0)
{
    global $db;

    if (!is_object($object) || empty($object->element)) {
        return false;
    }

    // Permission keys, as used by restrictedArea()
    $feature2 = '';
    if ($object->element == 'invoice_supplier') {
        $features = 'fournisseur';
        $feature2 = 'facture';
    } elseif ($object->element == 'order_supplier') {
        $features = 'fournisseur';
        $feature2 = 'commande';
    } elseif ($object->element == 'contact') {
        $features = 'societe';
        $feature2 = 'contact';
    } elseif (!empty($object->module) && $object->module != $object->element) {
        // Object of a module with permissions per object (module->object->read)
        $features = $object->module;
        if ($user->hasRight($object->module, $object->element)) {
            $feature2 = $object->element;
        }
    } else {
        $elementproperties = getElementProperties($object->element);
        $features = $elementproperties['module'];
    }

    if (restrictedArea($user, $features, 0, '', $feature2, 'fk_soc', 'rowid', 0, 1) <= 0) {
        return false;
    }

    if ($write) {
        $writefeatures = array($features);
        if ($features == 'product') {
            $writefeatures = array('produit', 'service');
        } elseif ($features == 'member') {
            $writefeatures = array('adherent');
        } elseif ($features == 'mo') {
            $writefeatures = array('mrp');
        }
        $writeok = false;
        foreach ($writefeatures as $feature) {
            foreach (array('creer', 'write', 'create') as $permission) {
                if ((!empty($feature2) && $user->hasRight($feature, $feature2, $permission)) || (empty($feature2) && $user->hasRight($feature, $permission))) {
                    $writeok = true;
                    break 2;
                }
            }
        }
        if (!$writeok) {
            return false;
        }
    }

    $columns = listExportImportGetColumns($db, $tablename);
    $linkedtothirdparties = (isset($columns['fk_soc']) || $object->table_element == 'societe');	// The thirdparty table itself has no fk_soc
    if ($linkedtothirdparties && (!empty($user->socid) || !$user->hasRight('societe', 'client', 'voir'))) {
        return false;
    }

    return true;
}

/**
 * Get the SQL filter limiting a list table to the entities of the user
 * A line table without entity column is filtered through the main table of the list
 *
 * @param	DoliDB			$db					Database handler
 * @param	CommonObject	$object				Object of the list
 * @param	string			$tablename			Table to filter, with prefix
 * @param	string			$parenttablename	Main table of the list when $tablename is a line table
 * @return	array|false							array('filter' => SQL filter without WHERE or '', 'linkfield' => field linked to the main table or ''), false if the table can't be filtered
 */
function listExportImportGetEntityFilter($db, $object, $tablename, $parenttablename = '')
{
    $columns = listExportImportGetColumns($db, $tablename);
    $nofilter = array('filter' => '', 'linkfield' => '');

    if (empty($columns)) {
        return $nofilter;	// Missing table: nothing to filter
    }
    if (isset($columns['entity'])) {
        return array('filter' => $columns['entity'].' IN ('.getEntity($object->element).')', 'linkfield' => '');
    }
    if (empty($parenttablename)) {
        return $nofilter;	// Table not managed by entity
    }

    $parentcolumns = listExportImportGetColumns($db, $parenttablename);
    if (!isset($parentcolumns['entity'])) {
        return $nofilter;
    }

    $linkfields = array((empty($object->fk_element) ? '' : $object->fk_element), 'fk_'.$object->table_element);
    foreach ($linkfields as $linkfield) {
        if (!empty($linkfield) && isset($columns[strtolower($linkfield)])) {
            $linkfield = $columns[strtolower($linkfield)];
            return array('filter' => $linkfield.' IN (SELECT rowid FROM '.$parenttablename.' WHERE entity IN ('.getEntity($object->element).'))', 'linkfield' => $linkfield);
        }
    }

    return false;
}

/**
 * Decode a file content sent in base64 by the import page (the padding may be removed)
 * The content is sent in base64 so that the input filters of Dolibarr don't alter the data (double quotes, html tags)
 *
 * @param	string	$data	Base64 content
 * @return	string			Decoded content, '' if the content is not valid base64
 */
function listExportImportDecodeBase64($data)
{
    $data = preg_replace('/\s+/', '', $data);
    $decoded = base64_decode($data.str_repeat('=', (4 - strlen($data) % 4) % 4), true);

    return ($decoded === false ? '' : $decoded);
}

/**
 * Read a csv content: values may be quoted, a quoted value may hold the delimiter, line breaks and doubled double quotes
 *
 * @param	string			$csv		Csv content
 * @param	string			$delimiter	Values delimiter
 * @return	array|false					List of rows (list of values), empty lines are ignored, false if the content is not valid csv
 */
function listExportImportParseCsv($csv, $delimiter)
{
    $rows = array();
    $row = array();
    $pos = 0;
    $length = strlen($csv);
    $delimiterlength = strlen($delimiter);
    $unquoted = '/\G[^"\r\n'.preg_quote($delimiter, '/').']*+/A';

    if ($delimiterlength == 0) {
        return false;
    }

    while (true) {
        if (substr($csv, $pos, 1) === '"') {
            if (!preg_match('/\G"((?:[^"]++|"")*+)"/A', $csv, $matches, 0, $pos)) {
                return false;	// Quote not closed
            }
            $row[] = str_replace('""', '"', $matches[1]);
        } else {
            preg_match($unquoted, $csv, $matches, 0, $pos);
            $row[] = $matches[0];
        }
        $pos += strlen($matches[0]);

        if ($pos >= $length) {
            $rows[] = $row;
            break;
        }
        if (substr($csv, $pos, $delimiterlength) === $delimiter) {
            $pos += $delimiterlength;
            continue;
        }
        $char = $csv[$pos];
        if ($char !== "\r" && $char !== "\n") {
            return false;	// Text after a quoted value
        }
        $pos += (($char === "\r" && substr($csv, $pos + 1, 1) === "\n") ? 2 : 1);
        $rows[] = $row;
        $row = array();
        if ($pos >= $length) {
            break;
        }
    }

    // Ignore empty lines
    $result = array();
    foreach ($rows as $row) {
        if (count($row) > 1 || $row[0] !== '') {
            $result[] = $row;
        }
    }

    return $result;
}

/**
 * Decode an SQL string literal (content between the quotes)
 *
 * @param	string	$str	Literal content, with backslash escapes or doubled quotes
 * @return	string			Decoded value
 */
function listExportImportUnescapeSqlString($str)
{
    return preg_replace_callback('/\\\\(.)|\'\'/s', function ($matches) {
        if ($matches[0] == "''") {
            return "'";
        }
        $escapes = array('0' => "\0", 'b' => "\x08", 'n' => "\n", 'r' => "\r", 't' => "\t", 'Z' => "\x1a");
        if (isset($escapes[$matches[1]])) {
            return $escapes[$matches[1]];
        }
        if ($matches[1] == '%' || $matches[1] == '_') {
            return '\\'.$matches[1];	// MySQL keeps the backslash for these two
        }
        return $matches[1];
    }, $str);
}

/**
 * Read an SQL import file: only INSERT statements with literal values (quoted string, number, NULL) are accepted
 *
 * @param	string			$sql	SQL content
 * @return	array|false				List of array('table' => name, 'columns' => names or null, 'rows' => list of values), false if the content is refused
 *									A value is null (SQL NULL) or array('string'|'number', value)
 */
function listExportImportParseInsertSql($sql)
{
    $identifier = '(?:`[A-Za-z0-9_]+`|[A-Za-z0-9_]+)';
    $statements = array();
    $pos = 0;
    $length = strlen($sql);

    while (true) {
        preg_match('/\G\s*/A', $sql, $matches, 0, $pos);
        $pos += strlen($matches[0]);
        if ($pos >= $length) {
            break;
        }

        if (!preg_match('/\GINSERT\s+INTO\s+('.$identifier.')\s*/Ai', $sql, $matches, 0, $pos)) {
            return false;
        }
        $pos += strlen($matches[0]);
        $statement = array('table' => trim($matches[1], '`'), 'columns' => null, 'rows' => array());

        if (preg_match('/\G\(\s*('.$identifier.'(?:\s*,\s*'.$identifier.')*)\s*\)\s*/A', $sql, $matches, 0, $pos)) {
            $pos += strlen($matches[0]);
            $statement['columns'] = array();
            foreach (explode(',', $matches[1]) as $column) {
                $statement['columns'][] = trim(trim($column), '`');
            }
        }

        if (!preg_match('/\GVALUES\s*/Ai', $sql, $matches, 0, $pos)) {
            return false;
        }
        $pos += strlen($matches[0]);

        do {
            if (!preg_match('/\G\(\s*/A', $sql, $matches, 0, $pos)) {
                return false;
            }
            $pos += strlen($matches[0]);
            $row = array();
            do {
                if (preg_match('/\G\'((?:[^\'\\\\]++|\\\\.|\'\')*+)\'/As', $sql, $matches, 0, $pos)) {
                    $row[] = array('string', listExportImportUnescapeSqlString($matches[1]));
                } elseif (preg_match('/\GNULL(?![A-Za-z0-9_])/Ai', $sql, $matches, 0, $pos)) {
                    $row[] = null;
                } elseif (preg_match('/\G-?[0-9]+(?:\.[0-9]+)?(?:[eE][-+]?[0-9]+)?(?![A-Za-z0-9_])/A', $sql, $matches, 0, $pos)) {
                    $row[] = array('number', $matches[0]);
                } else {
                    return false;
                }
                $pos += strlen($matches[0]);
                preg_match('/\G\s*(,?)\s*/A', $sql, $matches, 0, $pos);
                $pos += strlen($matches[0]);
            } while ($matches[1] == ',');

            if (!preg_match('/\G\)\s*(,?)\s*/A', $sql, $matches, 0, $pos)) {
                return false;
            }
            $pos += strlen($matches[0]);
            $statement['rows'][] = $row;
        } while ($matches[1] == ',');

        if (preg_match('/\G;/A', $sql, $matches, 0, $pos)) {
            $pos++;
        } elseif ($pos < $length) {
            return false;
        }
        $statements[] = $statement;
    }

    return $statements;
}

/**
 * Check an SQL import file and rebuild its queries from the values it contains: the SQL of the file is never run as is
 * Accepted targets: the main table of the list and its line tables, current entity only
 *
 * @param	DoliDB			$db				Database handler
 * @param	CommonObject	$object			Object of the list
 * @param	string			$tablename		Main table of the list, with prefix
 * @param	string[]		$linetablenames	Existing line tables of the list, with prefix
 * @param	string			$sql			SQL content of the file
 * @return	array							array('error' => translation key or '', 'queries' => queries to run, 'parentchecks' => queries that must return nb = 0 once the queries are run)
 */
function listExportImportPrepareImport($db, $object, $tablename, $linetablenames, $sql)
{
    global $conf;

    $result = array('error' => '', 'queries' => array(), 'parentchecks' => array());

    $statements = listExportImportParseInsertSql($sql);
    if ($statements === false || count($statements) == 0) {
        $result['error'] = 'OnlySqlInsertStatementIsAccepted';
        return $result;
    }

    foreach ($statements as $statement) {
        $target = '';
        foreach (array_merge(array($tablename), $linetablenames) as $allowedtable) {
            if (strtolower($statement['table']) == strtolower($allowedtable)) {
                $target = $allowedtable;
            }
        }
        if (empty($target)) {
            $result['error'] = 'FileContentNotMatchWithTableName';
            return $result;
        }
        if (!listExportImportIsTableAllowed($db, $target, 1)) {
            $result['error'] = 'NotEnoughPermissions';
            return $result;
        }

        // Columns: those of the file, checked against the table, or all the columns of the table in their order
        $columns = listExportImportGetColumns($db, $target);
        $fields = array();
        if (is_array($statement['columns'])) {
            foreach ($statement['columns'] as $column) {
                if (!isset($columns[strtolower($column)])) {
                    $result['error'] = 'DataShouldFitToTableFields';
                    return $result;
                }
                $fields[] = $columns[strtolower($column)];
            }
        } else {
            $fields = array_values($columns);
        }
        $lowerfields = array_map('strtolower', $fields);
        if (count(array_unique($lowerfields)) != count($lowerfields)) {
            $result['error'] = 'DataShouldFitToTableFields';
            return $result;
        }

        // Entity: rows of the current entity only, lines of documents of the entities of the user only
        $entityfilter = listExportImportGetEntityFilter($db, $object, $target, ($target == $tablename ? '' : $tablename));
        if ($entityfilter === false) {
            $result['error'] = 'NotEnoughPermissions';
            return $result;
        }
        $entityindex = array_search('entity', $lowerfields);
        $addentity = (isset($columns['entity']) && $entityindex === false);
        $linkindex = false;
        if (!empty($entityfilter['linkfield'])) {
            $linkindex = array_search(strtolower($entityfilter['linkfield']), $lowerfields);
            if ($linkindex === false) {
                $result['error'] = 'DataShouldFitToTableFields';
                return $result;
            }
        }

        $values = array();
        $parentids = array();
        foreach ($statement['rows'] as $row) {
            if (count($row) != count($fields)) {
                $result['error'] = 'DataShouldFitToTableFields';
                return $result;
            }
            if ($entityindex !== false && (!is_array($row[$entityindex]) || (string) $row[$entityindex][1] !== (string) ((int) $conf->entity))) {
                $result['error'] = 'NotEnoughPermissions';
                return $result;
            }
            if ($linkindex !== false) {
                if (!is_array($row[$linkindex])) {
                    $result['error'] = 'DataShouldFitToTableFields';
                    return $result;
                }
                $parentids[] = (int) $row[$linkindex][1];
            }
            $sqlvalues = array();
            foreach ($row as $value) {
                if ($value === null) {
                    $sqlvalues[] = 'NULL';
                } elseif ($value[0] == 'number') {
                    $sqlvalues[] = $value[1];
                } else {
                    $sqlvalues[] = "'".$db->escape($value[1])."'";
                }
            }
            if ($addentity) {
                $sqlvalues[] = ((int) $conf->entity);
            }
            $values[] = '('.implode(', ', $sqlvalues).')';
        }

        if (count($values) > 0) {
            $result['queries'][] = 'INSERT INTO '.$target.' ('.implode(', ', $fields).($addentity ? ', entity' : '').') VALUES '.implode(', ', $values);
        }
        if (count($parentids) > 0) {
            $result['parentchecks'][] = 'SELECT COUNT(rowid) as nb FROM '.$tablename.' WHERE rowid IN ('.implode(',', array_unique($parentids)).') AND entity NOT IN ('.getEntity($object->element).')';
        }
    }

    return $result;
}
// InfraS add end