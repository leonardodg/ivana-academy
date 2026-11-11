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

use moodle_page;
use moodle_url;

/**
 * Robots, canonical, description, Open Graph and JSON-LD for public pages.
 *
 * Only the public catalogue is indexable. Everything else (policies, data
 * privacy summary, signup, password reset, profiles...) gets noindex, which
 * is what Search Console showed being indexed in its place.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class meta {
    /** @var string[] Page types that may appear in search results. */
    const INDEXABLE = ['site-index', 'course-index', 'course-index-category', 'course-info', 'mod-page-view'];

    /** @var int Max meta description length. */
    const DESCRIPTION_LENGTH = 160;

    /**
     * Whether a page type may be indexed.
     *
     * @param string $pagetype
     * @return bool
     */
    public static function is_indexable(string $pagetype): bool {
        return in_array($pagetype, self::INDEXABLE, true);
    }

    /**
     * Canonical URL for an indexable page, without ?lang= and other noise.
     *
     * @param moodle_page $page
     * @return moodle_url|null null when the page is not indexable.
     */
    public static function canonical_url(moodle_page $page): ?moodle_url {
        switch ($page->pagetype) {
            case 'site-index':
                return new moodle_url('/');
            case 'course-info':
                return new moodle_url('/course/info.php', ['id' => $page->course->id]);
            case 'course-index':
            case 'course-index-category':
                $categoryid = (int) $page->url->get_param('categoryid');
                return $categoryid
                    ? new moodle_url('/course/index.php', ['categoryid' => $categoryid])
                    : new moodle_url('/course/index.php');
            case 'mod-page-view':
                return $page->cm ? new moodle_url('/mod/page/view.php', ['id' => $page->cm->id]) : null;
        }
        return null;
    }

    /**
     * Plain-text meta description for the page.
     *
     * @param moodle_page $page
     * @return string
     */
    public static function description(moodle_page $page): string {
        global $SITE;

        $text = '';
        switch ($page->pagetype) {
            case 'course-info':
                $text = format_text(
                    $page->course->summary,
                    $page->course->summaryformat,
                    ['context' => $page->context, 'noclean' => false]
                );
                break;
            case 'course-index-category':
                $categoryid = (int) $page->url->get_param('categoryid');
                $category = $categoryid ? \core_course_category::get($categoryid, IGNORE_MISSING) : null;
                if ($category) {
                    $text = format_text(
                        $category->description,
                        $category->descriptionformat,
                        ['context' => $category->get_context()]
                    );
                }
                break;
            case 'mod-page-view':
                if ($page->cm) {
                    $text = $page->cm->name;
                }
                break;
        }
        if (trim(strip_tags($text)) === '') {
            $text = (string) get_config('local_seo', 'sitedescription');
        }
        if (trim(strip_tags($text)) === '') {
            $text = format_text($SITE->summary, $SITE->summaryformat, ['context' => \context_system::instance()]);
        }
        return self::clean_text($text);
    }

    /**
     * Collapses HTML into a single-line plain text of up to DESCRIPTION_LENGTH chars.
     *
     * @param string $html
     * @return string
     */
    public static function clean_text(string $html): string {
        $text = html_entity_decode(
            strip_tags(str_replace(['<br', '</p>', '</li>'], [' <br', ' </p>', ' </li>'], $html)),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        return \core_text::strlen($text) > self::DESCRIPTION_LENGTH
            ? rtrim(\core_text::substr($text, 0, self::DESCRIPTION_LENGTH - 1)) . '…'
            : $text;
    }

    /**
     * Image for Open Graph: course image on course pages, else the site logo.
     *
     * @param moodle_page $page
     * @param \renderer_base $renderer
     * @return string|null
     */
    public static function image_url(moodle_page $page, \renderer_base $renderer): ?string {
        if ($page->pagetype === 'course-info') {
            $image = \core_course\external\course_summary_exporter::get_course_image($page->course);
            if ($image) {
                return (string) $image;
            }
        }
        if (method_exists($renderer, 'get_logo_url')) {
            $logo = $renderer->get_logo_url(600);
            if ($logo) {
                return $logo->out(false);
            }
        }
        return null;
    }

    /**
     * JSON-LD: EducationalOrganization on the front page, Course on course/info.php.
     *
     * @param moodle_page $page
     * @param string $description
     * @param string|null $image
     * @return array|null
     */
    public static function structured_data(moodle_page $page, string $description, ?string $image): ?array {
        global $SITE;

        $organization = [
            '@type' => 'EducationalOrganization',
            'name' => format_string($SITE->fullname),
            'url' => (new moodle_url('/'))->out(false),
        ];
        $sameas = array_values(array_filter(array_map(
            'trim',
            preg_split('/\R/', (string) get_config('local_seo', 'sameas'))
        )));

        if ($page->pagetype === 'site-index') {
            $data = ['@context' => 'https://schema.org'] + $organization;
            if ($description !== '') {
                $data['description'] = $description;
            }
            if ($image) {
                $data['logo'] = $image;
            }
            if ($sameas) {
                $data['sameAs'] = $sameas;
            }
            return $data;
        }
        if ($page->pagetype === 'course-info') {
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'Course',
                'name' => format_string($page->course->fullname, true, ['context' => $page->context]),
                'description' => $description !== '' ? $description : format_string($page->course->fullname),
                'url' => self::canonical_url($page)->out(false),
                'provider' => $organization,
            ];
            if (!empty($page->course->lang)) {
                $data['inLanguage'] = str_replace('_', '-', $page->course->lang);
            }
            if ($image) {
                $data['image'] = $image;
            }
            return $data;
        }
        return null;
    }

    /**
     * Converts a Moodle language code into an Open Graph locale (es_ar -> es_AR).
     *
     * @param string $lang
     * @return string
     */
    public static function og_locale(string $lang): string {
        $parts = explode('_', $lang);
        return strtolower($parts[0]) . (isset($parts[1]) ? '_' . strtoupper($parts[1]) : '');
    }

    /**
     * All SEO tags for the current page.
     *
     * @param moodle_page $page
     * @param \renderer_base $renderer
     * @return string
     */
    public static function head_html(moodle_page $page, \renderer_base $renderer): string {
        if (!self::is_indexable($page->pagetype)) {
            return '<meta name="robots" content="noindex, follow" />' . "\n";
        }

        $canonical = self::canonical_url($page);
        if (!$canonical) {
            return '';
        }
        $url = $canonical->out(false);
        $description = self::description($page);
        $image = self::image_url($page, $renderer);
        $title = $page->title !== '' ? $page->title : format_string($page->heading);

        $tags = ['<link rel="canonical" href="' . s($url) . '" />'];
        if ($description !== '') {
            $tags[] = '<meta name="description" content="' . s($description) . '" />';
        }
        $og = [
            'og:type' => $page->pagetype === 'course-info' ? 'article' : 'website',
            'og:site_name' => format_string($GLOBALS['SITE']->fullname),
            'og:title' => $title,
            'og:url' => $url,
            'og:locale' => self::og_locale(current_language()),
        ];
        if ($description !== '') {
            $og['og:description'] = $description;
        }
        if ($image) {
            $og['og:image'] = $image;
        }
        foreach ($og as $property => $content) {
            $tags[] = '<meta property="' . $property . '" content="' . s($content) . '" />';
        }
        $tags[] = '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '" />';

        $data = self::structured_data($page, $description, $image);
        if ($data) {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
            $tags[] = '<script type="application/ld+json">' . $json . '</script>';
        }
        return implode("\n", $tags) . "\n";
    }
}
