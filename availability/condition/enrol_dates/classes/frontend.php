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

namespace availability_enrol_dates;

/**
 * Restriction by Enrol Dates (timestart/timeend) or Course Dates (timestart/timeend) frontend
 *
 * @package    availability_enrol_dates
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Get the JavaScript strings to load for this plugin.
     *
     * @return array Array of strings
     */
    public function get_javascript_strings() {
        return [
            'title',
            'before',
            'after',
            'select_direction',
            'select_timevaluecheck',
            'select_timeperiod',
            'coursetimestart',
            'coursetimeend',
            'enroltimestart',
            'enroltimeend',
            'hours',
            'days',
            'months',
            'error_invalid_timenumber',
            'error_missing_direction',
            'error_missing_timevaluecheck',
            'error_missing_timeperiod',
        ];
    }

    /**
     * Determines whether the plugin can be added to an item.
     *
     * @return bool True if it can be added
     */
    protected function allow_add($course, ?\cm_info $cm = null, ?\section_info $section = null) {
        return true;
    }
}
