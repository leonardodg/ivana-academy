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
 * Hook callbacks for local_emaillayout.
 *
 * @package    local_emaillayout
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    // Runs before enrol_manual/enrol_self send the course welcome message (default priority 100)...
    [
        'hook' => \core_enrol\hook\after_user_enrolled::class,
        'callback' => [\local_emaillayout\hook_callbacks::class, 'force_enrolled_user_language'],
        'priority' => 10000,
    ],
    // ...and this one after all of them, to give the language back.
    [
        'hook' => \core_enrol\hook\after_user_enrolled::class,
        'callback' => [\local_emaillayout\hook_callbacks::class, 'restore_language'],
        'priority' => -10000,
    ],
];
