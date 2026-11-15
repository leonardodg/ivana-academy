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

namespace local_emaillayout;

use core_enrol\hook\after_user_enrolled;

/**
 * Sends the course welcome message in the language of the enrolled student.
 *
 * Enrolments made by the store come through the web service, so core builds the
 * welcome message (subject, default text, app link, layout footer) in the language
 * of the web service account instead of the student's. Core welcome senders are
 * callbacks of the same hook, so we force the student's language around them.
 *
 * @package    local_emaillayout
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** @var array stack of the languages forced before (null = nothing was changed) */
    protected static array $previous = [];

    /**
     * Force the enrolled user's language before the welcome message is built.
     *
     * @param after_user_enrolled $hook
     */
    public static function force_enrolled_user_language(after_user_enrolled $hook): void {
        global $DB;
        $lang = $DB->get_field('user', 'lang', ['id' => $hook->get_userid()]);
        if (!$lang || !get_string_manager()->translation_exists($lang, false)) {
            self::$previous[] = null;
            return;
        }
        self::$previous[] = force_current_language($lang);
    }

    /**
     * Give back whatever language was forced before ('' = none).
     *
     * @param after_user_enrolled $hook
     */
    public static function restore_language(after_user_enrolled $hook): void {
        $previous = array_pop(self::$previous);
        if ($previous !== null) {
            force_current_language($previous);
        }
    }
}
