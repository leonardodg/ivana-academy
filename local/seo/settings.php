<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Settings for local_seo.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_seo', get_string('pluginname', 'local_seo'));

    $settings->add(new admin_setting_configtextarea(
        'local_seo/sitedescription',
        get_string('sitedescription', 'local_seo'),
        get_string('sitedescription_desc', 'local_seo'),
        '',
        PARAM_TEXT,
        60,
        3
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_seo/sameas',
        get_string('sameas', 'local_seo'),
        get_string('sameas_desc', 'local_seo'),
        "https://courses.ivana.academy/\nhttps://www.instagram.com/ivana.lerea.br/\nhttps://www.instagram.com/ivana.lerea.arg/",
        PARAM_RAW_TRIMMED,
        60,
        5
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_seo/consentbanner',
        get_string('consentbanner', 'local_seo'),
        get_string('consentbanner_desc', 'local_seo'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
