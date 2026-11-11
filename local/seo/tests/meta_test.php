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
 * Tests for local_seo\meta.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_seo\meta
 * @covers     \local_seo\landing
 */
final class meta_test extends \advanced_testcase {
    public function test_is_indexable(): void {
        $this->assertTrue(meta::is_indexable('site-index'));
        $this->assertTrue(meta::is_indexable('course-info'));
        $this->assertFalse(meta::is_indexable('admin-tool-policy-view'));
        $this->assertFalse(meta::is_indexable('admin-tool-dataprivacy-summary'));
        $this->assertFalse(meta::is_indexable('login-forgot_password'));
        $this->assertFalse(meta::is_indexable('course-view-topics'));
    }

    public function test_clean_text(): void {
        $this->assertSame('Hola mundo. Velas', meta::clean_text("<p>Hola&nbsp;<b>mundo</b>.</p>\n<p>Velas</p>"));
        $long = str_repeat('á', 200);
        $clean = meta::clean_text($long);
        $this->assertSame(meta::DESCRIPTION_LENGTH, \core_text::strlen($clean));
        $this->assertStringEndsWith('…', $clean);
    }

    public function test_og_locale(): void {
        $this->assertSame('es_AR', meta::og_locale('es_ar'));
        $this->assertSame('pt_BR', meta::og_locale('pt_br'));
        $this->assertSame('en', meta::og_locale('en'));
    }

    public function test_head_html_front_page_canonical_without_lang(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url(new \moodle_url('/', ['lang' => 'es_ar']));
        $PAGE->set_pagetype('site-index');
        $PAGE->set_context(\context_system::instance());
        set_config('sitedescription', 'Cursos de velas, resinas y macramé', 'local_seo');

        $html = meta::head_html($PAGE, $PAGE->get_renderer('core'));

        $this->assertStringContainsString('<link rel="canonical" href="' . (new \moodle_url('/'))->out(false) . '" />', $html);
        $this->assertStringNotContainsString('lang=', $html);
        $this->assertStringContainsString('<meta name="description" content="Cursos de velas, resinas y macramé" />', $html);
        $this->assertStringContainsString('"@type":"EducationalOrganization"', $html);
        $this->assertStringNotContainsString('noindex', $html);
    }

    public function test_head_html_course_info_with_landing_page(): void {
        global $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Tecnicatura en Velas',
            'summary' => '<p>Aprendé a hacer velas</p>', 'summaryformat' => FORMAT_HTML]);
        $landing = 'https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-diseno-velas/';
        set_config('landingcanonical', "{$course->id} {$landing}  # Velas", 'local_seo');
        $PAGE->set_url(new \moodle_url('/course/info.php', ['id' => $course->id, 'lang' => 'pt_br']));
        $PAGE->set_course($course);
        $PAGE->set_pagetype('course-info');

        $html = meta::head_html($PAGE, $PAGE->get_renderer('core'));

        $this->assertStringContainsString('<link rel="canonical" href="' . $landing . '" />', $html);
        $this->assertStringContainsString('content="Aprendé a hacer velas"', $html);
        $this->assertStringNotContainsString('noindex', $html);
        $this->assertStringNotContainsString('"@type":"Course"', $html);
    }

    public function test_head_html_course_info_without_landing_page_is_noindex(): void {
        global $PAGE;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Mesas de rio']);
        set_config('landingcanonical', '', 'local_seo');
        set_config('landingparent', "{$course->id} https://courses.ivana.academy/es-ar/carreras/x/", 'local_seo');
        $PAGE->set_url(new \moodle_url('/course/info.php', ['id' => $course->id]));
        $PAGE->set_course($course);
        $PAGE->set_pagetype('course-info');

        $html = meta::head_html($PAGE, $PAGE->get_renderer('core'));

        $this->assertSame('<meta name="robots" content="noindex, follow" />' . "\n", $html);
    }

    public function test_landing_lists(): void {
        $this->resetAfterTest();
        $text = "# comentario\n4 https://a.test/velas/  # Velas\n21 = https://a.test/velas/\n\nx https://b.test/\n7: ftp://c.test/";
        $map = landing::parse($text);
        $this->assertSame([4 => 'https://a.test/velas/', 21 => 'https://a.test/velas/'], $map);

        set_config('landingcanonical', '4 https://a.test/velas/', 'local_seo');
        set_config('landingparent', "40 https://a.test/velas/\n4 https://a.test/otra/", 'local_seo');
        $this->assertSame('https://a.test/velas/', landing::visitor_url(4));
        $this->assertSame('https://a.test/velas/', landing::visitor_url(40));
        $this->assertNull(landing::canonical(40));
        $this->assertNull(landing::visitor_url(99));
    }

    public function test_head_html_system_page_is_noindex(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url(new \moodle_url('/admin/tool/dataprivacy/summary.php'));
        $PAGE->set_pagetype('admin-tool-dataprivacy-summary');
        $PAGE->set_context(\context_system::instance());

        $html = meta::head_html($PAGE, $PAGE->get_renderer('core'));

        $this->assertSame('<meta name="robots" content="noindex, follow" />' . "\n", $html);
    }

    public function test_consent_respects_setting(): void {
        $this->resetAfterTest();
        $this->assertStringContainsString("gtag('consent', 'default'", consent::head_html());
        $this->assertStringContainsString('ad_storage: \'denied\'', consent::head_html());
        set_config('consentbanner', 0, 'local_seo');
        $this->assertSame('', consent::head_html());
    }
}
