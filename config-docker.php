<?php

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = getenv('MOODLE_DBTYPE');
$CFG->dblibrary = getenv('MOODLE_DBLIB');
$CFG->dbhost    = getenv('MOODLE_DBHOST');
$CFG->dbname    = getenv('MOODLE_DBNAME');
$CFG->dbuser    = getenv('MOODLE_DBUSER');
$CFG->dbpass    = getenv('MOODLE_DBPASS');
$CFG->prefix    = getenv('MOODLE_DBPFX');
$CFG->dboptions = array('dbcollation' => getenv('MOODLE_DBCOLL'));

$CFG->wwwroot   = getenv('MOODLE_URL');
$CFG->dataroot  = getenv('MOODLE_DATA');
$CFG->admin     = getenv('MOODLE_ADMIN');

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');
