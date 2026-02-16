<?php

/**
 * Bootstrap for unit tests (without Dolibarr)
 */

// Autoload composer dependencies
require_once __DIR__ . '/../../vendor/autoload.php';

// Load dolibarr-autoload-init if available (for loading module classes)
$autoloadInit = __DIR__ . '/dolibarr-autoload-init.php';
if (file_exists($autoloadInit)) {
    require_once $autoloadInit;
}

// Define MAIN_DB_PREFIX for tests (used in SQL queries)
if (!defined('MAIN_DB_PREFIX')) {
    define('MAIN_DB_PREFIX', 'llx_');
}

// Extend the global $conf object with additional properties needed for tests
global $conf;
if (!isset($conf)) {
    $conf = new stdClass();
}
if (!isset($conf->cache)) {
    $conf->cache = [];
}

// Load module classes if Dolibarr core is available
if (defined('DOL_DOCUMENT_ROOT') && function_exists('dol_include_once')) {
    @dol_include_once('/uptosign/lib/uptosign.lib.php');
    @dol_include_once('/uptosign/class/uptosignCore.class.php');
    @dol_include_once('/uptosign/class/uptosign.class.php');
    @dol_include_once('/uptosign/class/uptosignlist.class.php');
}
