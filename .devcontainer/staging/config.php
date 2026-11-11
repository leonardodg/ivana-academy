<?php
// =============================================================================
// .devcontainer/staging/config.php - STAGING LOCAL (copia da producao)
// =============================================================================
// Montado sobre /var/www/html/config.php por staging/compose.yml. Os dados sao
// de CLIENTES REAIS: tudo que fala com o mundo fica forcado aqui (e tambem
// zerado no banco pelo anonymize.sql), para nao depender de um so lugar.
// =============================================================================

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = 'staging';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = ['dbcollation' => 'utf8mb4_bin'];

$CFG->wwwroot   = getenv('MOODLE_URL') ?: 'https://localhost:8543';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 02770;

// --- Nunca falar com o mundo -------------------------------------------------
$CFG->noemailever   = true;          // nenhum e-mail sai (nem para os enderecos trocados)
$CFG->cron_enabled  = false;         // tarefas agendadas/adhoc nao rodam (+ CRON_ENABLED=false)
$CFG->smtphosts     = '';
$CFG->smtpuser      = '';
$CFG->smtppass      = '';
$CFG->airnotifierurl       = '';     // push no celular dos alunos
$CFG->airnotifieraccesskey = '';
$CFG->enablewebservices    = 0;
$CFG->enablemobilewebservice = 0;

// GA4: ID inexistente. A tag carrega (testa a ordem consentimento -> gtag) mas
// nao suja a propriedade de producao. PayPal sem conta.
$CFG->forced_plugin_settings = [
    'theme_moove' => ['googleanalytics' => 'G-STAGING000'],
    'enrol_paypal' => ['paypalbusiness' => 'staging@example.invalid'],
];

// Igual a producao (sem debug na tela, sem forcar noindex): o HTML precisa
// ser o mesmo para a auditoria de SEO. Localhost nao e rastreavel.
$CFG->debug        = 0;
$CFG->debugdisplay = 0;

require_once(__DIR__ . '/lib/setup.php');
