<?php
/**
 * HTTP Test Router for uptosign admin pages
 * Used with: php -S localhost:8899 test/http/admin-router.php
 *
 * Loads Dolibarr via dolibarr-integration-sqlite, deploys the module,
 * then routes requests to the matching PHP file.
 */

/**
 * Convert a MySQL CREATE TABLE statement to SQLite-compatible SQL.
 * Same logic as test/phpunit/integration-dolibarr/bootstrap.php.
 */
if (!function_exists('uptosignHttpTestConvertMysqlToSqlite')) {
    function uptosignHttpTestConvertMysqlToSqlite(string $sql): string
    {
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\)\s*ENGINE\s*=\s*\w+[^;]*;/i', ');', $sql);
        $sql = preg_replace('/integer\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bint\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = preg_replace('/\bAUTO_INCREMENT/i', '', $sql);
        // Word boundaries so we do not eat import_key etc.
        $sql = preg_replace('/,?\s*\b(?:INDEX|KEY)\b\s+\w+\s*\([^)]+\)/i', '', $sql);
        $sql = preg_replace('/\s+COMMENT\s+\'[^\']*\'/i', '', $sql);
        $sql = preg_replace('/\s+DEFAULT\s+CHARSET\s*=\s*[a-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\s+COLLATE\s*=?\s*[a-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);
        $sql = preg_replace('/,\s*PRIMARY\s+KEY\s*\([^)]+\)/i', '', $sql);
        $sql = preg_replace('/\bsmallint\b/i', 'INTEGER', $sql);
        $sql = preg_replace('/\btinyint\b/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bbigint\b/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bint\(\d+\)/i', 'INTEGER', $sql);
        $sql = preg_replace('/\bdouble\b/i', 'REAL', $sql);
        $sql = preg_replace('/\bfloat\b/i', 'REAL', $sql);
        $sql = preg_replace('/\bdatetime\b/i', 'TEXT', $sql);
        $sql = preg_replace('/\btimestamp\b/i', 'TEXT', $sql);
        $sql = preg_replace('/\bUNSIGNED\b/i', '', $sql);
        $sql = preg_replace('/\bDEFAULT\s+CURRENT_TIMESTAMP\b/i', "DEFAULT ''", $sql);
        $sql = preg_replace('/\s+/', ' ', $sql);
        return trim($sql);
    }
}

/**
 * Create module tables natively in the SQLite database file. Dolibarr's SQLite
 * driver corrupts column names containing 'key' (import_key, hook_key) when
 * running CREATE TABLE through the regular query() path, so we open the SQLite
 * file directly instead.
 */
if (!function_exists('uptosignHttpTestCreateModuleTables')) {
    function uptosignHttpTestCreateModuleTables(string $projectRoot, string $sqliteDbPath): void
    {
        $sqlDir = $projectRoot . '/sql';
        if (!file_exists($sqliteDbPath)) {
            throw new RuntimeException("admin-router: SQLite DB file does not exist: $sqliteDbPath");
        }

        $sqlite = new SQLite3($sqliteDbPath);
        if (!$sqlite) {
            throw new RuntimeException("admin-router: failed to open SQLite DB: $sqliteDbPath");
        }

        $tablesToDrop = [
            'llx_uptosign_uptosignlistmembers',
            'llx_uptosign_uptosignlist_extrafields',
            'llx_uptosign_uptosignlist',
            'llx_uptosign_uptosignconfig',
            'llx_uptosign',
        ];
        foreach ($tablesToDrop as $table) {
            $sqlite->exec('DROP TABLE IF EXISTS ' . $table);
        }

        $sqlFiles = [
            'llx_uptosign.sql',
            'llx_uptosign_uptosignconfig.sql',
            'llx_uptosign_uptosignlist.sql',
            'llx_uptosign_uptosignlist_extrafields.sql',
            'llx_uptosign_uptosignlistmembers.sql',
        ];
        foreach ($sqlFiles as $file) {
            $filePath = $sqlDir . '/' . $file;
            if (!file_exists($filePath)) {
                continue;
            }
            $rawSql = file_get_contents($filePath);
            $sql = uptosignHttpTestConvertMysqlToSqlite($rawSql);
            if (preg_match('/CREATE\s+TABLE[^;]+;/is', $sql, $matches)) {
                $createSql = $matches[0];
                if ($sqlite->exec($createSql) === false) {
                    $sqlite->close();
                    throw new RuntimeException(
                        "admin-router: failed to create table from $file: "
                        . $sqlite->lastErrorMsg() . "\nSQL: $createSql"
                    );
                }
            }
        }

        $sqlite->close();
    }
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);

// Serve static files directly
if (preg_match('/\.(js|css|png|jpg|gif|ico|svg)$/i', $requestPath)) {
    return false;
}

// Ping endpoint for health check
if ($requestPath === '/ping') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    return;
}

// Force display_errors so PHP warnings/notices appear in the response body
// and are catchable by HttpTestCase::assertNoPhpError().
ini_set('display_errors', '1');
error_reporting(E_ALL);

$projectRoot = dirname(__DIR__, 2);
$sqliteVendorPath = $projectRoot . '/vendor/cap-rel/dolibarr-integration-sqlite';

// ---------------------------------------------------------------
// 1. Database in RAM (once per server process)
// ---------------------------------------------------------------
static $dbInitialized = false;
if (!$dbInitialized) {
    $ramDiskPath = is_dir('/dev/shm') ? '/dev/shm' : sys_get_temp_dir();
    $ramDbPath = $ramDiskPath . '/uptosign_http_test_' . getmypid() . '.sdb';
    $originalDbPath = $sqliteVendorPath . '/documents/database_dolibarr.sdb';

    // Restore conf.php from template
    $confPath = $sqliteVendorPath . '/htdocs/conf/conf.php';
    $confTemplate = $sqliteVendorPath . '/htdocs/conf/conf.php_sqlite';
    if (file_exists($confTemplate)) {
        copy($confTemplate, $confPath);
        // The template's $dolibarr_main_data_root resolves to '<sqlite>/htdocs/../documents'.
        // Dolibarr propagates that non-canonical path into DOL_DATA_ROOT, and helper
        // functions like uptosign_relative_path() rely on str_replace(DOL_DATA_ROOT, ...).
        // If a caller (eg. uptosign_tab.php after dol_sanitizePathName strips '..') feeds
        // a canonical path to those helpers, the str_replace silently fails to match and
        // every (path_file, hash_file) comparison breaks. Canonicalise here so both sides
        // see the same prefix.
        $canonicalDataRoot = realpath($sqliteVendorPath . '/documents');
        if ($canonicalDataRoot !== false) {
            $confContent = file_get_contents($confPath);
            $confContent = preg_replace(
                '/\$dolibarr_main_data_root\s*=\s*[^;]+;/',
                '$dolibarr_main_data_root=' . var_export($canonicalDataRoot, true) . ';',
                $confContent
            );
            file_put_contents($confPath, $confContent);
        }
    }

    // We need the RAM DB to PERSIST across requests for the same server lifetime
    // (fixtures created by one request must be readable by the next). The PHP CLI
    // built-in server resets top-level `static` variables AND calls register_shutdown_function
    // handlers at the end of EVERY request, not at process exit -- so the original
    // restore-on-shutdown logic was wiping the DB between requests.
    //
    // Strategy: detect "first request of this server lifetime" by the absence of
    // $ramDbPath (the PID-scoped RAM file). Subsequent requests find it already
    // there and skip the whole swap.
    //   - If a stale symlink survives from a previous server (different PID), drop
    //     it and restore the original DB from .backup so we get a fresh starting state.
    //   - No shutdown handler. The RAM file and the symlink survive until the next
    //     phpunit run, which detects the stale state and rebuilds from .backup.
    if (!file_exists($ramDbPath)) {
        if (is_link($originalDbPath)) {
            unlink($originalDbPath);
        }
        if (!is_file($originalDbPath) && file_exists($originalDbPath . '.backup')) {
            copy($originalDbPath . '.backup', $originalDbPath);
        }
        if (is_file($originalDbPath)) {
            if (!file_exists($originalDbPath . '.backup')) {
                copy($originalDbPath, $originalDbPath . '.backup');
            }
            copy($originalDbPath, $ramDbPath);
            unlink($originalDbPath);
            symlink($ramDbPath, $originalDbPath);
        }
    }
    $dbInitialized = true;
}

// ---------------------------------------------------------------
// 2. Bootstrap Dolibarr
// ---------------------------------------------------------------
require_once $projectRoot . '/vendor/autoload.php';

$dolibarrPath = realpath($sqliteVendorPath . '/htdocs');

if (!defined('DOL_DOCUMENT_ROOT')) {
    define('DOL_DOCUMENT_ROOT', $dolibarrPath);
}
// Do NOT define NOREQUIREMENU/NOREQUIREHTML/NOREQUIREAJAX -- admin pages need full HTML
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);

$_SERVER['SCRIPT_FILENAME'] = $dolibarrPath . '/test.php';
$_SERVER['DOCUMENT_ROOT'] = $dolibarrPath;
$_SERVER['PHP_SELF'] = $requestPath;
$_SERVER['SCRIPT_NAME'] = $requestPath;
$_SERVER['REQUEST_URI'] = $requestUri;
$_SERVER['QUERY_STRING'] = parse_url($requestUri, PHP_URL_QUERY) ?? '';
$_SERVER['HTTP_HOST'] = '127.0.0.1';

$originalDir = getcwd();
chdir($dolibarrPath);

ob_start();
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
global $conf, $db, $user, $langs, $hookmanager, $mysoc;
require_once $dolibarrPath . '/filefunc.inc.php';
require_once DOL_DOCUMENT_ROOT . '/master.inc.php';
error_reporting(E_ALL);
ob_end_clean();

chdir($originalDir);

if (!$db || !$user) {
    http_response_code(500);
    echo json_encode(['error' => 'Dolibarr failed to initialize']);
    return;
}

$user->fetch(1);
$user->admin = 1;

// ---------------------------------------------------------------
// 3. Deploy uptosign module (once per server process)
// ---------------------------------------------------------------
static $moduleDeployed = false;
if (!$moduleDeployed) {
    // Add module path to dol_document_root
    $parentDir = dirname($projectRoot);
    if (!isset($conf->file->dol_document_root) || !is_array($conf->file->dol_document_root)) {
        $conf->file->dol_document_root = array('main' => DOL_DOCUMENT_ROOT);
    }
    $conf->file->dol_document_root['alt0'] = $parentDir;

    // Initialize module schema. We bypass $mod->init() because Dolibarr's
    // SQLite driver corrupts column names containing 'key' (import_key,
    // hook_key) when running CREATE TABLE through run_sql(). Even when our
    // tables are correctly created first, init()->_load_tables() re-runs
    // every llx_*.sql file and either silently fails or overwrites them.
    // Same approach as test/phpunit/integration-dolibarr/bootstrap.php
    // which deliberately does NOT call init().
    require_once $projectRoot . '/core/modules/modUptoSign.class.php';
    // Silence Warning/Deprecated for the WHOLE deploy block, not just the schema
    // creation. Without this, Dolibarr's Contact::create() and friends spray HTML-
    // formatted Deprecated notices into the response stream (display_errors=1) and
    // pollute the JSON body of subsequent fixture endpoints called in the same
    // request lifecycle. Errors are restored before the request handler runs.
    $previousErrorReporting = error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);

    // Create module tables manually using native SQLite3, but only on the FIRST
    // request of the server lifetime. uptosignHttpTestCreateModuleTables DROPs +
    // re-creates the tables, so calling it on every request would wipe out any
    // row inserted by a previous request (eg. a fixture created via a setup
    // endpoint, then read by a later POST). The $testDataFlag is the same flag
    // used below to gate the test-data seeding -- if it is absent, the server
    // is brand new and the schema needs to be (re)created.
    $testDataFlag = $ramDiskPath . '/uptosign_http_test_data_' . getmypid() . '.json';
    if (!file_exists($testDataFlag)) {
        uptosignHttpTestCreateModuleTables($projectRoot, $ramDbPath);
    }

    // Enable module
    if (!isset($conf->uptosign)) {
        $conf->uptosign = new stdClass();
    }
    $conf->uptosign->enabled = 1;
    $conf->uptosign->dir_output = DOL_DATA_ROOT . '/uptosign';
    if (!is_dir($conf->uptosign->dir_output)) {
        @mkdir($conf->uptosign->dir_output, 0755, true);
    }
    if (!isset($conf->modules)) {
        $conf->modules = array();
    }
    $conf->modules['uptosign'] = 'uptosign';
    // hasRight() short-circuits to 0 when isModEnabled($module) is false (= when
    // $conf->modules[$module] is empty). Enable the modules that uptosign_tab.php
    // and its $otherModulesRights guard rely on, otherwise every POST short-circuits
    // on accessforbidden() before reaching the idempotency check.
    $conf->modules['societe']  = 'societe';
    $conf->modules['propal']   = 'propal';
    $conf->modules['commande'] = 'commande';
    $conf->modules['facture']  = 'facture';

    // Test data (thirdparty + contact + uptosign + uptosignlist + uptosignconfig)
    // is created only ONCE per server lifetime. PHP top-level `static` does not
    // persist between built-in server requests, so we use a flag file on disk.
    // The shutdown handler that restores the original DB also removes this flag.
    // $testDataFlag was already computed above so we could gate schema creation
    // by the same condition.
    if (!file_exists($testDataFlag)) {
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        $soc = new Societe($db);
        $soc->name = 'Test Company UptoSign';
        $soc->client = 1;
        $soc->create($user);
        $contact = new Contact($db);
        $contact->socid = $soc->id;
        $contact->lastname = 'TestContact';
        $contact->email = 'test@example.com';
        $contact->create($user);

        // Create test UptoSign object so that uptosign_card.php and employee_card.php
        // can be tested with a real ?id= parameter (the "view existing" code path).
        require_once $projectRoot . '/class/uptosign.class.php';
        $uptosign = new UptoSign($db);
        $uptosign->ref = 'TEST-UTS-' . uniqid();
        $uptosign->label = 'Test UptoSign HTTP';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;
        $uptosign->date_creation = dol_now();
        $resUts = $uptosign->create($user);
        $utsId = ($resUts > 0) ? (int) $uptosign->id : 0;
        if ($resUts <= 0) {
            error_log('admin-router: UptoSign->create() failed: ' . implode(', ', (array) $uptosign->errors));
        }

        // Create test UptoSignList for uptosignlist_card.php
        require_once $projectRoot . '/class/uptosignlist.class.php';
        $uptosignlist = new UptoSignList($db);
        $uptosignlist->ref = 'TEST-UTSL-' . uniqid();
        $uptosignlist->label = 'Test UptoSignList HTTP';
        $uptosignlist->status = 0;
        $uptosignlist->entity = 1;
        $resUtsl = $uptosignlist->create($user);
        $utslId = ($resUtsl > 0) ? (int) $uptosignlist->id : 0;
        if ($resUtsl <= 0) {
            error_log('admin-router: UptoSignList->create() failed: ' . implode(', ', (array) $uptosignlist->errors));
        }

        // Create test UptoSignConfig for uptosignconfig_card.php
        // Mandatory fields: model_pdf, label, sign_or_seal, seal_coordinate, page_seal
        require_once $projectRoot . '/class/uptosignconfig.class.php';
        $uptosignconfig = new UptoSignConfig($db);
        $uptosignconfig->model_pdf = 'propal:azur';
        $uptosignconfig->label = 'CustomerSign';
        $uptosignconfig->sign_or_seal = 'sign';
        $uptosignconfig->seal_coordinate = '100;100';
        $uptosignconfig->page_seal = 1;
        $uptosignconfig->sign_coordinate = '100;100';
        $uptosignconfig->page_sign = 1;
        $uptosignconfig->status = 1;
        $uptosignconfig->entity = 1;
        $resUtsc = $uptosignconfig->create($user);
        $utscId = ($resUtsc > 0) ? (int) $uptosignconfig->id : 0;
        if ($resUtsc <= 0) {
            error_log('admin-router: UptoSignConfig->create() failed: ' . implode(', ', (array) $uptosignconfig->errors));
        }

        file_put_contents($testDataFlag, json_encode([
            'soc_id' => (int) $soc->id,
            'uts_id' => $utsId,
            'utsl_id' => $utslId,
            'utsc_id' => $utscId,
        ]));

        // No shutdown handler to delete the flag. In the PHP CLI built-in server,
        // shutdown handlers fire at the END OF EACH REQUEST, not at process exit -
        // deleting the flag here would force the deploy block (and createModuleTables)
        // to re-run on the next request, wiping any fixture inserted in between.
        // The flag persists for the server lifetime (PID-scoped path) and is naturally
        // stale at the next phpunit run because the PID changes.
    }

    // Restore error reporting now that the noisy deploy block is done.
    error_reporting($previousErrorReporting);

    $moduleDeployed = true;
}

// Expose test IDs (read from the flag file on every request, since PHP top-level
// statics reset between requests on the built-in server).
$testDataFlag = (is_dir('/dev/shm') ? '/dev/shm' : sys_get_temp_dir())
    . '/uptosign_http_test_data_' . getmypid() . '.json';
$testIds = [];
if (file_exists($testDataFlag)) {
    $testIds = json_decode((string) file_get_contents($testDataFlag), true) ?: [];
}

// Endpoint to return the IDs as JSON for the test suite to discover.
if ($requestPath === '/test-ids') {
    header('Content-Type: application/json');
    echo json_encode($testIds);
    return;
}

// ---------------------------------------------------------------
// 4. Force user rights on every request (outside static block)
// ---------------------------------------------------------------
$user->admin = 1;
$user->getrights('uptosign');

// Force all module permissions via descriptor
require_once $projectRoot . '/core/modules/modUptoSign.class.php';
$modForRights = new modUptoSign($db);
if (!isset($user->rights->uptosign)) {
    $user->rights->uptosign = new stdClass();
}
foreach ($modForRights->rights as $r) {
    $perm1 = $r[4] ?? '';
    $perm2 = $r[5] ?? '';
    if (!empty($perm1)) {
        if (!isset($user->rights->uptosign->$perm1) || !is_object($user->rights->uptosign->$perm1)) {
            $user->rights->uptosign->$perm1 = new stdClass();
        }
        if (!empty($perm2)) {
            $user->rights->uptosign->$perm1->$perm2 = 1;
        } else {
            $user->rights->uptosign->$perm1 = 1;
        }
    }
}

// Force societe rights
if (!isset($user->rights->societe)) {
    $user->rights->societe = new stdClass();
}
$user->rights->societe->lire = 1;
if (!isset($user->rights->societe->client)) {
    $user->rights->societe->client = new stdClass();
}
$user->rights->societe->client->voir = 1;

// Reload langs
$langs->loadLangs(array('admin', 'uptosign@uptosign'));

// ---------------------------------------------------------------
// 4b. Test fixtures for uptosign_tab.php idempotency guard
// ---------------------------------------------------------------
// /seal-test-fixtures-create
//   Creates a brand-new Propal + fake PDF + in-flight UptoSign(STATUS_WAITING) wired
//   together for the idempotency guard to detect a duplicate on POST. Returns JSON
//   with the ids and paths the HTTP test needs to build its POST body.
if ($requestPath === '/seal-test-fixtures-create') {
    // Swallow any HTML-formatted Warning/Deprecated that Dolibarr core emits while
    // creating Societe/Contact/Propal/UptoSign, so the response stays pure JSON.
    ob_start();

    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
    require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
    require_once $projectRoot . '/class/uptosign.class.php';
    require_once $projectRoot . '/lib/uptosign.lib.php';

    // 1) Reuse the seeded Societe if available, otherwise create one.
    $socId = (int) ($testIds['soc_id'] ?? 0);
    if ($socId <= 0) {
        $soc = new Societe($db);
        $soc->name = 'Test Company Seal ' . uniqid();
        $soc->client = 1;
        $soc->create($user);
        $socId = (int) $soc->id;
    }

    // 2) Create a real Propal so uptosign_handle_all_type_of_objects()->fetch() succeeds.
    $propal = new Propal($db);
    $propal->socid = $socId;
    $propal->date = dol_now();
    $propal->entity = 1;
    $propalCreate = $propal->create($user);
    if ($propalCreate <= 0) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'propal->create failed',
            'errors' => (array) $propal->errors,
        ]);
        return;
    }

    // 3) Create the destination directory and a dummy PDF Dolibarr-style:
    //    DOL_DATA_ROOT/propal/<ref>/<ref>.pdf
    $relDir = 'propal/' . dol_sanitizeFileName($propal->ref);
    $fullDir = DOL_DATA_ROOT . '/' . $relDir;
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0755, true);
    }
    $pdfName = dol_sanitizeFileName($propal->ref) . '.pdf';
    $pdfFullPath = $fullDir . '/' . $pdfName;
    $pdfContent = '%PDF-1.4 seal-test-fixture ' . $propal->ref . ' ' . microtime(true);
    file_put_contents($pdfFullPath, $pdfContent);
    // Canonicalise the path before exposing it to the test. dol_sanitizePathName()
    // (which uptosign_tab.php applies to the POSTed pdfFileName) strips '..' from
    // the string, so feeding it a relative-style path like "htdocs/../documents/..."
    // would yield "htdocs/documents/..." which then fails realpath() and trips the
    // "Invalid file path" accessforbidden guard before our idempotency check runs.
    $pdfFullPath = realpath($pdfFullPath);
    $hash = hash_file('sha256', $pdfFullPath);
    $relPath = uptosign_relative_path($pdfFullPath);

    // 4) Insert an UptoSign row in STATUS_WAITING for that propal + PDF so the
    //    idempotency guard treats any incoming POST as a duplicate.
    $uts = new UptoSign($db);
    $uts->ref = 'SEAL-FX-' . uniqid();
    $uts->label = 'Seal idempotency fixture';
    $uts->fk_soc = $socId;
    $uts->status = UptoSign::STATUS_WAITING;
    $uts->entity = 1;
    $uts->object_type = 'propal';
    $uts->fk_object = (int) $propal->id;
    $uts->date_creation = dol_now();
    $utsCreate = $uts->create($user);
    if ($utsCreate <= 0) {
        ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'UptoSign->create failed',
            'errors' => (array) $uts->errors,
        ]);
        return;
    }

    // create() does not persist all fields we need (path_file, hash_file, api_name),
    // so force them via a direct UPDATE. Same pattern as the integration test fixtures.
    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'uptosign SET'
        . " path_file = '" . $db->escape($relPath) . "'"
        . ", hash_file = '" . $db->escape($hash) . "'"
        . ", api_name = 'uptoseal'"
        . ', status = ' . UptoSign::STATUS_WAITING
        . ' WHERE rowid = ' . ((int) $uts->id);
    $db->query($sql);

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'propal_id' => (int) $propal->id,
        'propal_ref' => $propal->ref,
        'pdf_full_path' => $pdfFullPath,
        'pdf_full_path_b64' => base64_encode($pdfFullPath),
        'pdf_rel_path' => $relPath,
        'uts_id' => (int) $uts->id,
        'hash' => $hash,
    ]);
    return;
}

// /seal-test-count?fk_object=X&object_type=propal&api_name=uptoseal
//   Counts UptoSign rows matching the given criteria. Used to assert that a duplicate
//   POST did not spawn an extra row, and that a regenerated PDF did spawn one.
if ($requestPath === '/seal-test-count') {
    $fkObject = (int) ($_GET['fk_object'] ?? 0);
    $objectType = (string) ($_GET['object_type'] ?? '');
    $apiName = (string) ($_GET['api_name'] ?? '');
    $sql = 'SELECT COUNT(*) AS cnt FROM ' . MAIN_DB_PREFIX . 'uptosign'
        . ' WHERE fk_object = ' . $fkObject
        . " AND object_type = '" . $db->escape($objectType) . "'"
        . " AND api_name = '" . $db->escape($apiName) . "'";
    $resql = $db->query($sql);
    $cnt = 0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $cnt = (int) $obj->cnt;
    }
    header('Content-Type: application/json');
    echo json_encode(['count' => $cnt]);
    return;
}

// /seal-test-bump-pdf?path=<rel-path-under-DOL_DATA_ROOT>
//   Overwrites the fixture PDF with new content so its sha256 changes. Used to test
//   that the idempotency guard does NOT trigger when the source document was regenerated.
if ($requestPath === '/seal-test-bump-pdf') {
    $relPath = (string) ($_GET['path'] ?? '');
    if ($relPath === '' || strpos($relPath, '..') !== false) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'invalid path']);
        return;
    }
    $fullPath = DOL_DATA_ROOT . '/' . $relPath;
    if (!is_file($fullPath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'pdf not found', 'path' => $fullPath]);
        return;
    }
    $newContent = '%PDF-1.4 regenerated ' . microtime(true);
    file_put_contents($fullPath, $newContent);
    header('Content-Type: application/json');
    echo json_encode([
        'path' => $fullPath,
        'new_hash' => hash_file('sha256', $fullPath),
    ]);
    return;
}

// ---------------------------------------------------------------
// 5. Create shim main.inc.php for admin pages
// ---------------------------------------------------------------
$shimDir = sys_get_temp_dir() . '/uptosign_http_test_shim_' . getmypid();
if (!is_dir($shimDir)) {
    mkdir($shimDir, 0755, true);
}

// Write shim -- pages admin do: @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php"
// The shim just requires the real main.inc.php (idempotent)
$shimContent = '<?php
// Shim main.inc.php for HTTP tests -- forces prod mode
global $dolibarr_main_prod;
$dolibarr_main_prod = "1";
require_once "' . $dolibarrPath . '/main.inc.php";

// Bypass CSRF for tests: when POST contains token=test, force the session token to "test"
// so newToken() returns "test" and verifCond(GETPOST(token) == newToken()) passes.
if (!empty($_POST["token"]) && $_POST["token"] === "test") {
	$_SESSION["newtoken"] = "test";
	$_SESSION["token"] = "test";
}
';
file_put_contents($shimDir . '/main.inc.php', $shimContent);

// Set CONTEXT_DOCUMENT_ROOT so pages find the shim
$_SERVER['CONTEXT_DOCUMENT_ROOT'] = $shimDir;

// ---------------------------------------------------------------
// 6. Shutdown handler for fatal error detection
// ---------------------------------------------------------------
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n<!--PHPUNIT_FATAL_ERROR:" . $error['message'] . '-->';
    }
});

// ---------------------------------------------------------------
// 7. Route the request
// ---------------------------------------------------------------
$targetFile = null;
if (preg_match('#^/admin/([a-zA-Z0-9_-]+\.php)$#', $requestPath, $matches)) {
    $targetFile = $projectRoot . '/admin/' . $matches[1];
} elseif (preg_match('#^/([a-zA-Z0-9_-]+\.php)$#', $requestPath, $matches)) {
    $targetFile = $projectRoot . '/' . $matches[1];
} elseif (preg_match('#^/ajax/([a-zA-Z0-9_-]+\.php)$#', $requestPath, $matches)) {
    $targetFile = $projectRoot . '/ajax/' . $matches[1];
}

if ($targetFile && is_file($targetFile)) {
    // Capture output to detect errors
    ob_start();
    try {
        include $targetFile;
    } catch (\Throwable $e) {
        echo "\n<!--PHPUNIT_FATAL_ERROR:" . $e->getMessage() . '-->';
    }
    $output = ob_get_clean();
    echo $output;
} else {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not found', 'path' => $requestPath]);
}
