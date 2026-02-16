<?php

/**
 * Initialize DOL_DOCUMENT_ROOT for unit tests
 * This allows loading module classes that extend Dolibarr classes
 */

// Detect if running integration tests (they have their own bootstrap)
$_isIntegrationTest = false;
if (isset($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $_arg) {
        if (strpos($_arg, 'phpunit-integration') !== false) {
            $_isIntegrationTest = true;
            break;
        }
    }
}

// Skip initialization if already done or in integration mode
if (defined('DOL_DOCUMENT_ROOT') || defined('PHPUNIT_RUNNING') || $_isIntegrationTest) {
    unset($_isIntegrationTest);
    return;
}
unset($_isIntegrationTest);

// Check if cap-rel/dolibarr-integration-sqlite is installed
$_dolibarr_autoload_init_path = __DIR__ . '/../../vendor/cap-rel/dolibarr-integration-sqlite/htdocs';
if (!is_dir($_dolibarr_autoload_init_path)) {
    return; // Package not installed
}

// Define DOL_DOCUMENT_ROOT immediately (before any class loading)
define('DOL_DOCUMENT_ROOT', realpath($_dolibarr_autoload_init_path));

// Define DOL_VERSION if not yet defined
if (!defined('DOL_VERSION')) {
    define('DOL_VERSION', '20.0.0');
}

// DOL_URL_ROOT needed by dol_buildpath()
if (!defined('DOL_URL_ROOT')) {
    define('DOL_URL_ROOT', '/dolibarr');
}

// Font constants required by tcpdfbarcode module
if (!defined('DOL_DEFAULT_TTF_BOLD')) {
    define('DOL_DEFAULT_TTF_BOLD', '/dev/null');
}
if (!defined('DOL_DEFAULT_TTF')) {
    define('DOL_DEFAULT_TTF', '/dev/null');
}

// Create minimal global $conf object for class loading
global $conf;
$conf = new stdClass();
$conf->file = new stdClass();
$conf->file->main_limit_users = 0;
$conf->file->dol_document_root = array('custom' => realpath(dirname(dirname(__DIR__)) . '/..'));
$conf->global = new stdClass();
$conf->entity = 1;
$conf->currency = 'EUR';

// Create minimal global $langs object
global $langs;
$langs = new stdClass();
$langs->defaultlang = 'fr_FR';

// Create minimal global $mysoc object for phone validation
global $mysoc;
$mysoc = new stdClass();
$mysoc->country_code = 'FR';

// Load Dolibarr core functions
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

// Load module backports
require_once dirname(dirname(__DIR__)) . '/lib/backports.lib.php';

unset($_dolibarr_autoload_init_path);
