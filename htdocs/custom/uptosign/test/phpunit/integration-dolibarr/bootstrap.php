<?php

/**
 * Bootstrap for integration tests with real Dolibarr (SQLite)
 */

// Constants to indicate test environment
if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}
if (!defined('PHPUNIT_TEST_MODE')) {
    define('PHPUNIT_TEST_MODE', true);
}

// Autoloader composer
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

// Path to dolibarr-integration-sqlite
$sqliteVendorPath = dirname(__DIR__, 3) . '/vendor/cap-rel/dolibarr-integration-sqlite';
$sqliteVendorPath = realpath($sqliteVendorPath);

if (!$sqliteVendorPath || !is_dir($sqliteVendorPath)) {
    throw new Exception('Package cap-rel/dolibarr-integration-sqlite not found. Run: composer require --dev cap-rel/dolibarr-integration-sqlite:@dev');
}

// Restore conf.php from template (in case previous test crashed)
$confPath = $sqliteVendorPath . '/htdocs/conf/conf.php';
$confTemplate = $sqliteVendorPath . '/htdocs/conf/conf.php_sqlite';
if (file_exists($confTemplate)) {
    copy($confTemplate, $confPath);
}

// Copy database to RAM for better performance (Linux only)
$ramDiskPath = '/dev/shm';
$dbSource = $sqliteVendorPath . '/documents/database_dolibarr.sdb';
$sqliteDbPath = null; // Will be set to the actual database path

if (is_dir($ramDiskPath) && is_writable($ramDiskPath) && file_exists($dbSource)) {
    $dbRamName = 'uptosign-test-' . getmypid();
    $dbRamPath = $ramDiskPath . '/database_' . $dbRamName . '.sdb';
    $sqliteDbPath = $dbRamPath;

    copy($dbSource, $dbRamPath);

    // Update conf.php to use RAM database
    $confContent = file_get_contents($confPath);
    // Change db name
    $confContent = preg_replace(
        '/\$dolibarr_main_db_name\s*=\s*[\'"][^\'"]+[\'"]\s*;/',
        '$dolibarr_main_db_name = \'' . $dbRamName . '\';',
        $confContent
    );
    // Change data root to RAM disk
    $confContent = preg_replace(
        '/\$dolibarr_main_data_root\s*=\s*[^;]+;/',
        '$dolibarr_main_data_root = \'' . $ramDiskPath . '\';',
        $confContent
    );
    file_put_contents($confPath, $confContent);

    // Clean up on shutdown
    register_shutdown_function(function() use ($dbRamPath) {
        if (file_exists($dbRamPath)) {
            @unlink($dbRamPath);
        }
    });
} else {
    // Use default database location
    $sqliteDbPath = $sqliteVendorPath . '/documents/database_dolibarr.sdb';
}

// Define DOL_DOCUMENT_ROOT BEFORE loading Dolibarr
define('DOL_DOCUMENT_ROOT', $sqliteVendorPath . '/htdocs');

// Define CLI constants BEFORE master.inc.php
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);
define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);

// Define $_SERVER variables BEFORE master.inc.php
$_SERVER['PHP_SELF'] = '/test.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/test.php';
$_SERVER['SCRIPT_FILENAME'] = DOL_DOCUMENT_ROOT . '/test.php';
$_SERVER['REQUEST_URI'] = '/test.php';
$_SERVER['DOCUMENT_ROOT'] = DOL_DOCUMENT_ROOT;
$_SERVER['QUERY_STRING'] = '';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Change to htdocs directory BEFORE loading
$originalDir = getcwd();
chdir(DOL_DOCUMENT_ROOT);

// Suppress warnings during bootstrap
ob_start();
$previousErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

// Load Dolibarr
global $conf, $db, $user, $langs, $hookmanager, $mysoc;
require_once DOL_DOCUMENT_ROOT . '/filefunc.inc.php';
require_once DOL_DOCUMENT_ROOT . '/master.inc.php';

// Restore error reporting
error_reporting($previousErrorReporting);
ob_end_clean();

// Restore original directory
chdir($originalDir);

// Load admin user
$user->fetch(1);
$user->getrights();

// Load languages
$langs->loadLangs(array('main', 'errors', 'uptosign@uptosign'));

// Project root
$projectRoot = dirname(__DIR__, 3);

// Configure dol_document_root for dol_buildpath()
if (!isset($conf->file->dol_document_root) || !is_array($conf->file->dol_document_root)) {
    $conf->file->dol_document_root = array('main' => DOL_DOCUMENT_ROOT);
}

// Create symlink for case-insensitive module path resolution
$parentDir = dirname($projectRoot);
$moduleName = strtolower(basename($projectRoot));
$symlinkPath = $parentDir . '/' . $moduleName;
if (!file_exists($symlinkPath) && basename($projectRoot) !== $moduleName) {
    @symlink($projectRoot, $symlinkPath);
    register_shutdown_function(function() use ($symlinkPath) {
        if (is_link($symlinkPath)) {
            @unlink($symlinkPath);
        }
    });
}

$conf->file->dol_document_root['alt0'] = $parentDir;

// Add module path to modules_parts for getNextNumRef() to find numbering classes
if (!isset($conf->modules_parts['models'])) {
    $conf->modules_parts['models'] = array();
}
$conf->modules_parts['models']['/uptosign/'] = '/uptosign/';

/**
 * Convert MySQL SQL to SQLite compatible SQL
 */
function convertMysqlToSqlite(string $sql): string
{
    // Remove all SQL comments
    $sql = preg_replace('/--.*$/m', '', $sql);

    // Remove ENGINE clause with all options
    $sql = preg_replace('/\)\s*ENGINE\s*=\s*\w+[^;]*;/i', ');', $sql);

    // Convert AUTO_INCREMENT PRIMARY KEY to SQLite style
    $sql = preg_replace('/integer\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    $sql = preg_replace('/\bint\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
    $sql = preg_replace('/\bAUTO_INCREMENT/i', '', $sql);

    // Remove inline INDEX/KEY definitions (use word boundaries to not match import_key, etc.)
    $sql = preg_replace('/,?\s*\b(?:INDEX|KEY)\b\s+\w+\s*\([^)]+\)/i', '', $sql);

    // Remove inline COMMENT
    $sql = preg_replace('/\s+COMMENT\s+\'[^\']*\'/i', '', $sql);

    // Remove CHARSET and COLLATE
    $sql = preg_replace('/\s+DEFAULT\s+CHARSET\s*=\s*[a-z0-9_]+/i', '', $sql);
    $sql = preg_replace('/\s+COLLATE\s*=?\s*[a-z0-9_]+/i', '', $sql);

    // Remove ON UPDATE CURRENT_TIMESTAMP
    $sql = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);

    // Remove PRIMARY KEY constraint when using AUTOINCREMENT
    $sql = preg_replace('/,\s*PRIMARY\s+KEY\s*\([^)]+\)/i', '', $sql);

    // Convert data types
    $sql = preg_replace('/\bsmallint\b/i', 'INTEGER', $sql);
    $sql = preg_replace('/\btinyint\b/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bbigint\b/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bint\(\d+\)/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bdouble\b/i', 'REAL', $sql);
    $sql = preg_replace('/\bfloat\b/i', 'REAL', $sql);
    $sql = preg_replace('/\bdatetime\b/i', 'TEXT', $sql);
    $sql = preg_replace('/\btimestamp\b/i', 'TEXT', $sql);

    // Remove UNSIGNED
    $sql = preg_replace('/\bUNSIGNED\b/i', '', $sql);

    // Remove DEFAULT CURRENT_TIMESTAMP
    $sql = preg_replace('/\bDEFAULT\s+CURRENT_TIMESTAMP\b/i', "DEFAULT ''", $sql);

    // Clean up multiple spaces
    $sql = preg_replace('/\s+/', ' ', $sql);

    return trim($sql);
}

/**
 * Create module tables from SQL files using native SQLite3
 * (Dolibarr's SQLite driver has issues with column names containing 'key')
 */
function createModuleTables($db, string $projectRoot, string $sqliteDbPath): void
{
    $sqlDir = $projectRoot . '/sql';

    if (!file_exists($sqliteDbPath)) {
        throw new Exception("Database file does not exist: $sqliteDbPath");
    }

    // Open SQLite database directly
    $sqlite = new SQLite3($sqliteDbPath);
    if (!$sqlite) {
        throw new Exception("Failed to open SQLite database: $sqliteDbPath");
    }

    // Drop existing tables first
    $tablesToDrop = [
        'llx_uptosign_uptosignlistmembers',
        'llx_uptosign_uptosignlist_extrafields',
        'llx_uptosign_uptosignlist',
        'llx_uptosign_uptosignconfig',
        'llx_uptosign'
    ];

    foreach ($tablesToDrop as $table) {
        $sqlite->exec("DROP TABLE IF EXISTS " . $table);
    }

    // SQL files to create (in order)
    $sqlFiles = [
        'llx_uptosign.sql',
        'llx_uptosign_uptosignconfig.sql',
        'llx_uptosign_uptosignlist.sql',
        'llx_uptosign_uptosignlist_extrafields.sql',
        'llx_uptosign_uptosignlistmembers.sql'
    ];

    foreach ($sqlFiles as $file) {
        $filePath = $sqlDir . '/' . $file;
        if (!file_exists($filePath)) {
            continue;
        }

        $sql = file_get_contents($filePath);
        $sql = convertMysqlToSqlite($sql);

        // Execute only CREATE TABLE statements
        if (preg_match('/CREATE\s+TABLE[^;]+;/is', $sql, $matches)) {
            $createSql = $matches[0];
            $result = $sqlite->exec($createSql);
            if ($result === false) {
                throw new Exception("Failed to create table from $file: " . $sqlite->lastErrorMsg() . "\nSQL: " . $createSql);
            }

            // Verify the table was created with correct columns
            if ($file === 'llx_uptosign_uptosignconfig.sql') {
                $checkResult = $sqlite->query("PRAGMA table_info(llx_uptosign_uptosignconfig)");
                $cols = [];
                while ($row = $checkResult->fetchArray(SQLITE3_ASSOC)) {
                    $cols[] = $row['name'];
                }
                if (!in_array('import_key', $cols)) {
                    throw new Exception("After create from $file, import_key column missing. Columns: " . implode(', ', $cols) . "\nSQL was: " . $createSql);
                }
            }
        }
    }

    $sqlite->close();
}

// Create module tables manually (init() doesn't handle column names correctly with SQLite)
createModuleTables($db, $projectRoot, $sqliteDbPath);

// Verify columns were created correctly by checking with native SQLite3
$verifySqlite = new SQLite3($sqliteDbPath);
$verifyResult = $verifySqlite->query("PRAGMA table_info(llx_uptosign_uptosignconfig)");
$hasImportKey = false;
while ($row = $verifyResult->fetchArray(SQLITE3_ASSOC)) {
    if ($row['name'] === 'import_key') {
        $hasImportKey = true;
        break;
    }
}
$verifySqlite->close();
if (!$hasImportKey) {
    throw new Exception("Column import_key was not created correctly. sqliteDbPath=$sqliteDbPath");
}

// Verify essential tables exist
$requiredTables = [
    'llx_uptosign',
    'llx_uptosign_uptosignconfig',
    'llx_uptosign_uptosignlist',
    'llx_uptosign_uptosignlistmembers'
];

foreach ($requiredTables as $table) {
    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='" . $db->escape($table) . "'";
    $resql = $db->query($sql);
    if (!$resql || $db->num_rows($resql) == 0) {
        throw new Exception("Failed to initialize module: table $table was not created");
    }
}

// Initialize module configuration in $conf
if (!isset($conf->uptosign)) {
    $conf->uptosign = new stdClass();
}
$conf->uptosign->enabled = 1;
$conf->uptosign->dir_output = DOL_DATA_ROOT . '/uptosign';

// Initialize global constants for the module
if (!isset($conf->global)) {
    $conf->global = new stdClass();
}
$conf->global->UPTOSIGN_FORCE_FIRST_CONTACT_AS_SIGNER = 0;
$conf->global->UPTOSIGN_REDIRECT_PAGE_AFTER_SIGN = 'https://uptosign.com/';
$conf->global->UPTOSIGN_HIDE_MAIL_AND_PHONE = '0';
$conf->global->UPTOSIGN_DISABLE_SMS_GLOBAL_SELECT = '0';
$conf->global->UPTOSIGN_ADDON = 'mod_uptosign_standard';
$conf->global->UPTOSIGN_UPTOSIGNLIST_ADDON = 'mod_uptosignlist_standard';

// Create output directory
if (!is_dir($conf->uptosign->dir_output)) {
    @mkdir($conf->uptosign->dir_output, 0755, true);
}

// Load module classes (order matters: dependencies first)
require_once $projectRoot . '/lib/backports.lib.php';
require_once $projectRoot . '/lib/uptosign.lib.php';
require_once $projectRoot . '/class/uptosignapiclient.class.php';
require_once $projectRoot . '/class/uptosignsignatoryresolver.class.php';
require_once $projectRoot . '/class/uptosignconfig.class.php';
require_once $projectRoot . '/class/uptosign.class.php';
require_once $projectRoot . '/class/uptosignlist.class.php';

// Load numbering modules
require_once $projectRoot . '/core/modules/uptosign/modules_uptosign.php';
require_once $projectRoot . '/core/modules/uptosign/mod_uptosign_standard.php';
require_once $projectRoot . '/core/modules/uptosign/modules_uptosignlist.php';
require_once $projectRoot . '/core/modules/uptosign/mod_uptosignlist_standard.php';
