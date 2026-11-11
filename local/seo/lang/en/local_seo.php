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
 * Language strings for local_seo.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['consent_accept'] = 'Accept';
$string['consent_policy'] = 'Privacy policy';
$string['consent_reject'] = 'Reject';
$string['consent_terms'] = 'By continuing to browse, you agree to our policies:';
$string['consent_text'] = 'We use analytics cookies (Google Analytics) to understand how the site is used and improve our courses. No advertising cookies.';
$string['consentbanner'] = 'Analytics consent banner';
$string['consentbanner_desc'] = 'Sets Google Consent Mode defaults (analytics denied until accepted, ads always denied) and shows a banner. The choice is stored only in the visitor\'s browser.';
$string['landingcanonical'] = 'Same product on the landing site (canonical)';
$string['landingcanonical_desc'] = 'One line per course: <code>courseid URL</code>. The course page points its canonical URL at the landing page, so Google shows the landing page. Use it only when the landing page sells this very course (a profesorado sold on its tecnicatura page counts). Text after # is a comment.';
$string['landingparent'] = 'Part of a career on the landing site';
$string['landingparent_desc'] = 'One line per course: <code>courseid URL</code>, for a course that is a module of a career sold on that page. The course page stays out of Google (noindex) and visitors who click it go to the landing page. Courses in neither list are noindex.';
$string['pluginname'] = 'SEO';
$string['privacy:metadata'] = 'The SEO plugin does not store personal data. The analytics consent choice is kept only in the visitor\'s browser (cookie ivana_analytics_consent).';
$string['sameas'] = 'Organization profiles (sameAs)';
$string['sameas_desc'] = 'One URL per line: official site, social profiles. Used in the structured data of the front page.';
$string['sitedescription'] = 'Site description';
$string['sitedescription_desc'] = 'Meta description for the front page and pages without their own summary (max. 160 characters). Empty: uses the site summary.';
