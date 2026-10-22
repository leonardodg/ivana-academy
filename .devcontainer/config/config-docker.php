<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

function get_moodle_setting($env_name, $secret_path = null) {
    // 1. Tenta pegar da variável de ambiente tradicional
    $val = getenv($env_name);
    if ($val !== false && $val !== '') {
        return $val;
    }

    // 2. Se não achar e houver um caminho de secret, tenta ler o arquivo
    if ($secret_path && file_exists($secret_path)) {
        $secret_val = trim(file_get_contents($secret_path));
        if ($secret_val !== '') {
            return $secret_val;
        }
    }

    return null;
}

$CFG->dbtype    = getenv('MOODLE_DBTYPE') ?: 'mariadb';
$CFG->dblibrary = getenv('MOODLE_DBLIB') ?: 'native';
$CFG->dbhost    = getenv('MOODLE_DBHOST') ?: 'db';
$CFG->dbname    = getenv('MOODLE_DBNAME') ?: 'moodle';
$CFG->prefix    = getenv('MOODLE_DBPFX') ?: 'mdl_';
$CFG->dboptions = array('dbcollation' => getenv('MOODLE_DBCOLLATION') ?: 'utf8mb4_bin');

$CFG->dbuser    = get_moodle_setting('MOODLE_DBUSER', '/run/secrets/db_user');
$CFG->dbpass    = get_moodle_setting('MOODLE_DBPASS', '/run/secrets/db_password');

$CFG->wwwroot   = getenv('MOODLE_URL') ?: 'https://develop.local';
$CFG->dataroot  = getenv('MOODLE_DATA') ?: '/var/www/moodledata';
$CFG->admin     = getenv('MOODLE_ADMIN') ?: 'admin';

$CFG->directorypermissions = 0777;
$CFG->sslproxy  = true;

// MOODLE DEV PERFORMANCE SETTINGS
$CFG->debug = 0;
$CFG->debugdisplay = 0;
$CFG->cachejs = true;
$CFG->langstringcache = true;
$CFG->preventexecpath = true;
$CFG->localcachedir = '/var/www/moodledata/localcache';

$CFG->phpunit_dataroot  = '/var/www/phpunitdata';
$CFG->phpunit_prefix = 't_';
define('PHPUNIT_LONGTEST', true);

require_once(__DIR__ . '/lib/setup.php');