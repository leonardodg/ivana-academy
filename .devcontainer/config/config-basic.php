<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();


$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = 'm@0dl3ing';
$CFG->prefix    = 'mdl_';

$CFG->dboptions = array(
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_bin',
);

$CFG->wwwroot   = 'https://develop.ivana.academy';
$CFG->dataroot  = '/var/www/moodledata-academy';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

$CFG->phpunit_dbtype    = 'mysqli';
$CFG->phpunit_dblibrary = 'native';
$CFG->phpunit_dbhost    = 'db';
$CFG->phpunit_dbname    = 'mytestdb';
$CFG->phpunit_dbuser    = 'moodle';
$CFG->phpunit_dbpass    = 'm@0dl3ing';
$CFG->phpunit_prefix = 'phpu_';

$CFG->phpunit_dataroot = '/var/www/phpu_moodledata';
$CFG->cachejs = false;

require_once(__DIR__ . '/lib/setup.php');

