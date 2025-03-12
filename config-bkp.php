<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

// Prevent JS caching
$CFG->cachejs = false;

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

$CFG->wwwroot   = 'http://127.0.0.1';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

$CFG->phpunit_dbtype    = 'mysqli';      // 'pgsql', 'mariadb', 'mysqli', 'mssql', 'sqlsrv' or 'oci'
$CFG->phpunit_dblibrary = 'native';     // 'native' only at the moment
$CFG->phpunit_dbhost    = 'db';  // eg 'localhost' or 'db.isp.com' or IP
$CFG->phpunit_dbname    = 'mytestdb';     // database name, eg moodle
$CFG->phpunit_dbuser    = 'moodle';   // your database username
$CFG->phpunit_dbpass    = 'm@0dl3ing';   // your database password
$CFG->phpunit_prefix = 'phpu_';

$CFG->phpunit_dataroot = '/var/www/phpu_moodledata';

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
