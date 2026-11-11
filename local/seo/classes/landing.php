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

/**
 * Links between Moodle courses and their sales pages on the landing site.
 *
 * The landing site (courses.ivana.academy) informs and sells; Moodle is where
 * students take the course. So course/info.php must not compete with the
 * landing page in search results:
 *  - "canonical" list: the same product. course/info.php points its canonical
 *    at the landing page, so Google folds both into the landing one.
 *  - "parent" list: a module of a career sold on that page. A canonical would be
 *    ignored (different content), so the page is noindex and visitors are sent
 *    to the career page.
 *  - courses in neither list: noindex.
 *
 * Both lists are admin settings, one "courseid url" per line.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class landing {
    /**
     * Parses "courseid url" lines (also "id = url", "id: url"); ignores blanks and # comments.
     *
     * @param string $text
     * @return array<int, string> course id => absolute http(s) URL
     */
    public static function parse(string $text): array {
        $map = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(preg_replace('/#.*$/', '', $line));
            if (preg_match('/^(\d+)\s*[=:]?\s*(https?:\/\/\S+)$/i', $line, $m)) {
                $url = clean_param($m[2], PARAM_URL);
                if ($url !== '') {
                    $map[(int) $m[1]] = $url;
                }
            }
        }
        return $map;
    }

    /**
     * Landing page that is the same product as the course (canonical target).
     *
     * @param int $courseid
     * @return string|null
     */
    public static function canonical(int $courseid): ?string {
        return self::parse((string) get_config('local_seo', 'landingcanonical'))[$courseid] ?? null;
    }

    /**
     * Landing page that sells the career this course is part of.
     *
     * @param int $courseid
     * @return string|null
     */
    public static function parent(int $courseid): ?string {
        return self::parse((string) get_config('local_seo', 'landingparent'))[$courseid] ?? null;
    }

    /**
     * Landing page to show visitors (not logged in) next to the course: same product or its career.
     *
     * @param int $courseid
     * @return string|null
     */
    public static function visitor_url(int $courseid): ?string {
        return self::canonical($courseid) ?? self::parent($courseid);
    }
}
