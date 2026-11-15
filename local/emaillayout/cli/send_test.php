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
 * Preview or send sample e-mails through the Ivana Academy layout.
 *
 * Every sample goes through the real path (email_to_user() -> core/email_html ->
 * theme override -> local_emaillayout/email_html), with bodies built from the same
 * core strings the platform uses. It only ever writes to ONE user.
 *
 *   php local/emaillayout/cli/send_test.php --list
 *   php local/emaillayout/cli/send_test.php --user=admin --preview=/tmp/emails
 *   php local/emaillayout/cli/send_test.php --user=admin --send [--only=resetpassword,newlogin]
 *
 * @package    local_emaillayout
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    ['help' => false, 'list' => false, 'user' => '', 'only' => '', 'preview' => '', 'send' => false],
    ['h' => 'help']
);
if ($unrecognised) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognised));
}

/**
 * Sample messages (subject, plain text, html) for a recipient, keyed by name.
 *
 * @param stdClass $user recipient
 * @return array key => [description, subject, text, html]
 */
function local_emaillayout_samples(stdClass $user): array {
    global $CFG, $SITE;
    $sitename = format_string($SITE->fullname);
    $site = $CFG->wwwroot;
    $admin = generate_email_signoff();
    $a = (object) ['firstname' => $user->firstname, 'sitename' => $sitename, 'admin' => $admin];

    $samples = [];

    // Password reset (login/forgot_password.php -> send_password_change_confirmation_email()).
    $r = clone $a;
    $r->link = $site . '/login/forgot_password.php?token=SAMPLE-TOKEN';
    $r->resetminutes = 30;
    $r->username = $user->username;
    $text = get_string('emailresetconfirmation', 'moodle', $r);
    $samples['resetpassword'] = ['Password reset (direct email_to_user, text only)',
        get_string('emailresetconfirmationsubject', 'moodle', $sitename), $text, ''];

    // Self-registration confirmation (auth_email -> send_confirmation_email()).
    $c = clone $a;
    $c->link = $site . '/login/confirm.php?data=SAMPLE/' . urlencode($user->username);
    $text = get_string('emailconfirmation', 'moodle', $c);
    $samples['confirmation'] = ['Account confirmation (self-registration, text only)',
        get_string('emailconfirmationsubject', 'moodle', $sitename), $text, ''];

    // New sign-in notification (core\task\send_login_notifications, the most sent one).
    $info = [
        'sitename' => $sitename, 'siteurl' => $site, 'userfullname' => fullname($user),
        'username' => $user->username, 'useremail' => $user->email,
        'logindevice' => 'Chrome Android', 'logintime' => userdate(time()), 'loginip' => '203.0.113.10',
        'changepasswordlink' => $site . '/login/change_password.php',
    ];
    $html = get_string('newloginnotificationbodyfull', 'moodle', $info);
    $samples['newlogin'] = ['New sign-in notification (message, html)',
        get_string('newloginnotificationtitle', 'moodle', $sitename), html_to_text($html), $html];

    // Course welcome (enrol_self / enrol_manual, sendcoursewelcomemessage = 1).
    $w = (object) ['coursename' => 'Mesas de rio', 'profileurl' => $site . '/user/view.php?id=' . $user->id,
        'fullname' => fullname($user), 'firstname' => $user->firstname, 'lastname' => $user->lastname,
        'email' => $user->email, 'username' => $user->username, 'courselink' => $site . '/course/view.php?id=39'];
    $text = get_string('welcometocoursetext', 'moodle', $w);
    $html = text_to_html($text, false, false, true);
    $samples['enrolwelcome'] = ['Course welcome (enrol self/manual, html from text)',
        get_string('welcometocourse', 'moodle', 'Mesas de rio'), $text, $html];

    $taglinea = (object) ['sitename' => $sitename, 'url' => $site . '/message/notificationpreferences.php'];
    $tagline = get_string('emailtagline', 'message', $taglinea);

    // Forum post / rich content: headings, list, button, table, quote, image.
    $html = '<h2>Novo aviso no curso Mesas de rio</h2>'
        . '<p>Olá ' . s($user->firstname) . ', a professora publicou um novo aviso:</p>'
        . '<blockquote style="margin:0 0 12px;padding:8px 16px;border-left:3px solid #805252;background:#fcf9f5;">'
        . '<p>A aula ao vivo desta semana muda para <strong>quinta-feira às 19h</strong>.</p></blockquote>'
        . '<ul><li>Material de apoio atualizado</li><li>Gravação disponível em 24h</li></ul>'
        . '<p><a href="' . $site . '/course/view.php?id=39" style="display:inline-block;padding:10px 20px;'
        . 'background:#805252;color:#ffffff;text-decoration:none;border-radius:4px;">Abrir o curso</a></p>'
        . '<table border="1" cellpadding="6" style="border-collapse:collapse;"><tr><th>Data</th><th>Horário</th></tr>'
        . '<tr><td>20/11</td><td>19h</td></tr></table>'
        . '<p><img src="' . $site . '/local/emaillayout/pix/logo_email.png" alt="" width="120"></p>'
        . '<hr><p style="font-size:12px;color:#777;">' . $tagline . '</p>';
    $samples['richcontent'] = ['Rich content (forum post / announcement style)',
        'Mesas de rio: Novo aviso', html_to_text($html), $html];

    return $samples;
}

if ($options['help'] || (!$options['list'] && !$options['user'])) {
    echo "Preview or send sample e-mails with the Ivana Academy layout to ONE user.

Options:
  --list            list the samples
  --user=ID|NAME    recipient (id or username)
  --only=a,b        only these samples (default: all)
  --preview=DIR     write the rendered HTML to DIR (nothing is sent)
  --send            really send through email_to_user() (SMTP of this site)
";
    exit(0);
}

if ($options['list']) {
    foreach (local_emaillayout_samples(get_admin()) as $key => $s) {
        echo str_pad($key, 15) . $s[0] . "\n";
    }
    exit(0);
}

$field = ctype_digit((string) $options['user']) ? 'id' : 'username';
$user = $DB->get_record('user', [$field => $options['user'], 'deleted' => 0], '*', MUST_EXIST);
if (!$options['preview'] && !$options['send']) {
    cli_error('Choose --preview=DIR or --send.');
}
if (!empty($CFG->noemailever) && $options['send']) {
    cli_problem('Warning: $CFG->noemailever is set, nothing will leave this site.');
}
if ((int) $user->mailformat !== 1) {
    cli_problem("Warning: user {$user->username} has mailformat=0 (plain text), the layout is not applied.");
}

// Strings in the recipient's language, as for a real notification.
force_current_language($user->lang);
\core\cron::setup_user($user);

$samples = local_emaillayout_samples($user);
$only = array_filter(array_map('trim', explode(',', $options['only'])));
if ($only) {
    $samples = array_intersect_key($samples, array_flip($only));
}

if ($options['preview']) {
    check_dir_exists($options['preview']);
    $renderer = $PAGE->get_renderer('core');
}

$noreply = core_user::get_noreply_user();
foreach ($samples as $key => [$desc, $subject, $text, $html]) {
    if ($options['preview']) {
        $body = $html ?: trim(text_to_html($text));
        $out = $renderer->render_from_template('core/email_html', [
            'sitefullname' => $SITE->fullname, 'siteshortname' => $SITE->shortname,
            'sitewwwroot' => $CFG->wwwroot, 'subject' => $subject, 'prefix' => $CFG->emailsubjectprefix,
            'to' => $user->email, 'toname' => fullname($user), 'touserid' => $user->id,
            'tousername' => $user->username, 'body' => $body,
        ]);
        $file = rtrim($options['preview'], '/') . "/$key.html";
        file_put_contents($file, $out);
        echo "preview  $key -> $file\n";
    }
    if ($options['send']) {
        $ok = email_to_user($user, $noreply, $subject, $text, $html);
        echo ($ok ? 'sent     ' : 'FAILED   ') . "$key -> {$user->email}\n";
    }
}
