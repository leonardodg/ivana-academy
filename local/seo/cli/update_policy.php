<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Updates a tool_policy document from a Markdown file that has two ```html blocks
 * (1st = summary, 2nd = full text), e.g. .devcontainer/docs/POLITICA-PRIVACIDADE-v5.md.
 *
 * Without --run it only prints what it would do.
 *
 * @package    local_seo
 * @copyright  2026 LeoDG - Ivana Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params([
    'file' => '',
    'policyid' => 0,
    'revision' => '',
    'minor' => false,
    'run' => false,
    'help' => false,
], ['h' => 'help']);

$usage = "Updates a site policy (tool_policy) from a Markdown file with two html blocks (summary, full text).

Options:
  --file=PATH       Markdown file, absolute or relative to the Moodle root (required)
  --policyid=ID     Policy document id (default: the only active site policy)
  --revision=TEXT   Revision label of the new version (e.g. v5)
  --minor           Overwrite the current version (minor change: users do NOT accept again)
  --run             Apply. Without it, only shows what would be done
  -h, --help        This help

Default (without --minor): saves a NEW version, makes it current, and every user accepts it
again at the next login, as when an admin chooses \"Save as new version\".

Example:
  php local/seo/cli/update_policy.php --file=.devcontainer/docs/POLITICA-PRIVACIDADE-v5.md --revision=v5 --run
";

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognized)));
}
if ($options['help'] || $options['file'] === '') {
    echo $usage;
    exit(0);
}

$file = $options['file'];
if ($file[0] !== '/') {
    $file = $CFG->dirroot . '/' . $file;
}
if (!is_readable($file)) {
    cli_error("File not found: {$file}");
}
$fence = str_repeat(chr(96), 3); // Markdown code fence.
preg_match_all('/' . $fence . 'html\R(.*?)\R' . $fence . '/s', file_get_contents($file), $blocks);
if (count($blocks[1]) < 2) {
    cli_error('The file must have two html code blocks: summary, then full text.');
}
[$summary, $content] = [trim($blocks[1][0]), trim($blocks[1][1])];

// The policy document.
$policyid = (int) $options['policyid'];
if (!$policyid) {
    $active = $DB->get_records_select('tool_policy', 'currentversionid IS NOT NULL', null, 'sortorder', 'id');
    if (count($active) !== 1) {
        cli_error('There is not exactly one active policy; pass --policyid.');
    }
    $policyid = (int) reset($active)->id;
}
$policy = $DB->get_record('tool_policy', ['id' => $policyid], '*', MUST_EXIST);
$current = new \tool_policy\policy_version($policy->currentversionid);

$mode = $options['minor'] ? 'MINOR change: overwrite version id ' . $current->get('id') . ' (no new acceptance)'
    : 'NEW version, made current: all users accept it again at the next login';
cli_writeln("Policy {$policyid}: \"" . $current->get('name') . '" (current version id ' . $current->get('id')
    . ', revision "' . $current->get('revision') . '")');
cli_writeln('Mode: ' . $mode);
cli_writeln('Revision: ' . ($options['revision'] !== '' ? $options['revision'] : '(unchanged)'));
cli_writeln('Summary: ' . core_text::strlen(strip_tags($summary)) . ' chars; full text: '
    . core_text::strlen(strip_tags($content)) . ' chars');

if (!$options['run']) {
    cli_writeln('Dry run. Add --run to apply.');
    exit(0);
}

// Same data the policy form would submit, starting from the current version.
\core\session\manager::set_user(get_admin());
$form = \tool_policy\api::form_policydoc_data($current);
$form->policyid = $policyid;
$form->summary_editor = ['text' => $summary, 'format' => FORMAT_HTML, 'itemid' => 0];
$form->content_editor = ['text' => $content, 'format' => FORMAT_HTML, 'itemid' => 0];
if ($options['revision'] !== '') {
    $form->revision = $options['revision'];
}
foreach (['summary', 'content', 'summaryformat', 'contentformat', 'usermodified', 'timemodified'] as $field) {
    unset($form->$field);
}

if ($options['minor']) {
    $version = \tool_policy\api::form_policydoc_update_overwrite($form);
} else {
    unset($form->id);
    $version = \tool_policy\api::form_policydoc_update_new($form);
    \tool_policy\api::make_current($version->get('id'));
}
cli_writeln('Done: version id ' . $version->get('id') . ' is current.');
