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
 * Install steps for theme_ivana.
 *
 * @package    theme_ivana
 * @copyright  2026 LeoDG - Ivana Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Copies the Moove settings and images in use (login background, Google Analytics, social
 * links...) so switching from theme_moove to theme_ivana keeps them, and adds the theme
 * to the user tours that target Boost.
 *
 * @return bool
 */
function xmldb_theme_ivana_install() {
    global $DB;

    // Settings: only the ones this theme still has, and only when Moove had a value.
    $keep = ['loginbgimg', 'googleanalytics', 'disableteacherspic', 'enablecourseindex',
        'enableclassicbreadcrumb', 'facebook', 'twitter', 'linkedin', 'youtube', 'instagram', 'whatsapp',
        'telegram', 'website', 'mobile', 'mail', 'scsspre', 'scss', 'hvpcss'];
    $moove = get_config('theme_moove');
    if ($moove) {
        foreach ($keep as $name) {
            if (isset($moove->$name) && $moove->$name !== '') {
                set_config($name, $moove->$name, 'theme_ivana');
            }
        }

        // Stored files of those settings.
        $fs = get_file_storage();
        $context = context_system::instance();
        foreach (['loginbgimg'] as $area) {
            $files = $fs->get_area_files($context->id, 'theme_moove', $area, 0, 'id', false);
            foreach ($files as $file) {
                if (!$fs->file_exists($context->id, 'theme_ivana', $area, 0, $file->get_filepath(), $file->get_filename())) {
                    $fs->create_file_from_storedfile(['component' => 'theme_ivana'], $file);
                }
            }
        }
    }

    // User tours: show the Boost tours in this theme too.
    $usertours = $DB->get_records('tool_usertours_tours');
    foreach ($usertours as $usertour) {
        $configdata = json_decode($usertour->configdata);
        if (empty($configdata->filtervalues->theme) || !is_array($configdata->filtervalues->theme)) {
            continue;
        }
        if (in_array('boost', $configdata->filtervalues->theme) && !in_array('ivana', $configdata->filtervalues->theme)) {
            $configdata->filtervalues->theme[] = 'ivana';
            $DB->update_record('tool_usertours_tours', (object) ['id' => $usertour->id, 'configdata' => json_encode($configdata)]);
        }
    }

    return true;
}
