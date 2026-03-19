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
 * Restriction by Enrol Dates (timestart/timeend) or Course Dates (timestart/timeend) condition
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/availability}
 *
 * @package    availability_enrol_dates
 * @copyright  2026 LeoDG <callme@leodg.dev>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var string example data */
    protected $date_period;
    protected $date_check;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        $this->date_period = $structure->date_period ?? null;
        $this->date_check = $structure->date_check ?? null;
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * If implementations require a course or modinfo, they should use
     * the get methods in $info.
     *
     * The $not option is potentially confusing. This option always indicates
     * the 'real' value of NOT. For example, a condition inside a 'NOT AND'
     * group will get this called with $not = true, but if you put another
     * 'NOT OR' group inside the first group, then a condition inside that will
     * be called with $not = false. We need to use the real values, rather than
     * the more natural use of the current value at this point inside the tree,
     * so that the information displayed to users makes sense.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param \core_availability\info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $allow = false;
        $date_period = $this->date_period;
        $date_check = $this->date_check;
        if (!is_null($date_period) and !is_null($date_check)) {
            $allow = true;
        }
        $allow = $not ? !$allow : $allow;
        return $allow;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * The $full parameter can be used to distinguish between 'staff' cases
     * (when displaying all information about the activity) and 'student' cases
     * (when displaying only conditions they don't meet).
     *
     * If implementations require a course or modinfo, they should use
     * the get methods in $info. They should not use any other functions that
     * might rely on modinfo, such as format_string.
     *
     * To work around this limitation, use the functions:
     *
     * description_cm_name()
     * description_format_string()
     * description_callback()
     *
     * These return special markers which will be added to the string and processed
     * later after modinfo is complete.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param \core_availability\info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *                this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        $date_period = $this->date_period;
        $date_check = $this->date_check;
        if (!is_null($date_period) and !is_null($date_check)) {
            $allow = true;
        }
        $allow = $not ? !$allow : $allow;
        return $allow ? 'Users are allowed' : 'Users not allowed';
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        $date_period = $this->date_period;
        $date_check = $this->date_check;
        if (!is_null($date_period) and !is_null($date_check)) {
            $allow = true;
        }
        return 'DEBUG: ' . $allow ? 'YES' : 'NO';
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return \stdClass Structure object (ready to be made into JSON format)
     */
    public function save() {
        return (object)[
            'type' => 'enrol_dates',
            'date_period' => $this->date_period,
            'date_check' => $this->date_check,
        ];
    }
}
