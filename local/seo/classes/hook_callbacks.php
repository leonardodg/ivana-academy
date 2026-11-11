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

namespace local_seo;

use core\hook\output\before_standard_head_html_generation;

/**
 * Hook callbacks for local_seo.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Adds consent defaults (before the theme's Google tag) and SEO tags to <head>.
     *
     * @param before_standard_head_html_generation $hook
     */
    public static function before_standard_head_html_generation(before_standard_head_html_generation $hook): void {
        global $PAGE;

        if (during_initial_install()) {
            return;
        }
        try {
            $hook->add_html(consent::head_html());
            $hook->add_html(meta::head_html($PAGE, $hook->renderer));
        } catch (\Throwable $e) {
            // SEO tags must never break a page.
            debugging('local_seo: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
