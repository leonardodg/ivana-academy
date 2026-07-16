<?php

// =============================================================================
// config-sandbox-production.php
// =============================================================================
// Used ONLY by .github/workflows/validation-production.yml, inside the
// ephemeral container defined by compose/sandbox-production.yml.
//
// This is NOT the real production config.php (that one lives on the EC2
// host and is generated from config-docker.php / env vars, see prod-v2.yml).
// This file exists so that `admin/cli/checks.php -t security|performance`
// runs against the SAME hardening flags that ship in config-docker.php,
// letting the CI gate validate the settings before they ever reach prod.
//
// Settings below map directly to the checks from:
//   https://docs.moodle.org/502/en/Security_checks
//   https://docs.moodle.org/502/en/Performance_recommendations
//   https://moodledev.io/general/development/policies/security
// =============================================================================

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
$CFG->dboptions = array(
  'dbpersist'   => true,
  'dbport'      => '',
  'dbsocket'    => '',
  'dbcollation' => 'utf8mb4_bin',
);

$CFG->wwwroot   = 'https://localhost';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02777;
$CFG->sslproxy = false;

// -----------------------------------------------------------------------
// Performance (report/performance/index.php)
// -----------------------------------------------------------------------
$CFG->debug            = 0;     // DEBUG_NONE - core_debugging / core_displayerrors
$CFG->debugdisplay     = 0;
$CFG->cachejs          = true;  // core_cachejs
$CFG->langstringcache  = true;
$CFG->preventexecpath  = true;  // core_preventexecpath
$CFG->localcachedir    = '/var/www/moodledata/localcache';

// -----------------------------------------------------------------------
// Security (report/security/index.php) - forced admin settings, override
// whatever is stored in mdl_config so a fresh CI install already reflects
// the hardened production policy instead of Moodle's permissive defaults.
// -----------------------------------------------------------------------
$CFG->cookiesecure              = true;  // core_cookiesecure (site is served over https)
$CFG->cookiehttponly             = true;
$CFG->forceloginforprofiles      = true;  // core_openprofiles
$CFG->opentowebcrawlers          = false; // core_crawlers
$CFG->passwordpolicy             = true;  // core_passwordpolicy
$CFG->minpasswordlength           = 12;
$CFG->minpassworddigits           = 1;
$CFG->minpasswordlower            = 1;
$CFG->minpasswordupper            = 1;
$CFG->minpasswordnonalphanum      = 1;
$CFG->emailchangeconfirmation    = true;  // core_emailchangeconfirmation
$CFG->cronclionly                = true;  // core_webcron
$CFG->allowobjectembed            = false; // core_embed

$CFG->phpunit_dataroot = '/var/www/phpunitdata';
$CFG->phpunit_prefix   = 't_';
define('PHPUNIT_LONGTEST', true);

require_once(__DIR__ . '/lib/setup.php');
