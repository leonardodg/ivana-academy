<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = 'm@0dl3ing';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_bin'
);

$CFG->wwwroot   = 'http://localhost';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

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

$CFG->iplookup = 'local/file';
$CFG->block_tags_init_timeout = 0;
$CFG->phpunit_iplookup = 'none';

require_once(__DIR__ . '/lib/setup.php');