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

namespace theme_ivana\util;

use moodle_url;

/**
 * Main menu and footer of the Ivana Academy landing pages (courses.ivana.academy).
 *
 * The data comes from data/lp.json, generated from the landing pages project by
 * tools/export_lp.mjs, so Moodle and the landing pages share one source.
 *
 * @package    theme_ivana
 * @copyright  2026 LeoDG - Ivana Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lp {
    /** @var string URL parameter (and session key) of the PT | ES switch. */
    const LANGPARAM = 'ivanalang';

    /** @var array|null Decoded data/lp.json. */
    private static $data = null;

    /**
     * Decoded data/lp.json.
     *
     * @return array
     */
    public static function data(): array {
        global $CFG;

        if (self::$data === null) {
            $json = @file_get_contents($CFG->dirroot . '/theme/ivana/data/lp.json');
            self::$data = $json ? (json_decode($json, true) ?: []) : [];
        }

        return self::$data;
    }

    /**
     * Language of the landing pages menu and footer: 'pt' (Brazil) or 'es' (Argentina).
     *
     * Order: the PT | ES switch (kept in the session), then the Moodle language
     * (pt* = pt, anything else = es; most students are in Argentina).
     *
     * @return string
     */
    public static function lang(): string {
        global $SESSION;

        $param = optional_param(self::LANGPARAM, '', PARAM_ALPHA);
        if ($param === 'pt' || $param === 'es') {
            $SESSION->theme_ivana_lplang = $param;
        }
        if (!empty($SESSION->theme_ivana_lplang)) {
            return $SESSION->theme_ivana_lplang;
        }

        return strpos(current_language(), 'pt') === 0 ? 'pt' : 'es';
    }

    /**
     * Moodle language pack that matches a landing pages language, if installed.
     *
     * @param string $lang 'pt' or 'es'
     * @return string|null
     */
    private static function moodle_lang(string $lang): ?string {
        $installed = get_string_manager()->get_list_of_translations();
        $candidates = $lang === 'pt' ? ['pt_br', 'pt'] : ['es_ar', 'es_mx', 'es'];
        foreach ($candidates as $candidate) {
            if (isset($installed[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Picks the 'pt'/'es' value of a {pt, es} array.
     *
     * @param array|string $value
     * @param string $lang
     * @return string
     */
    private static function pick($value, string $lang): string {
        return is_array($value) ? ($value[$lang] ?? reset($value)) : (string) $value;
    }

    /**
     * Template context of a menu item.
     *
     * @param array $item
     * @param string $lang
     * @return array
     */
    private static function item(array $item, string $lang): array {
        return [
            'label' => self::pick($item['label'], $lang),
            'href' => self::pick($item['href'], $lang),
            'newtab' => !empty($item['newtab']),
            'icon' => $item['icon'] ?? '',
        ];
    }

    /**
     * Template context of the visitors navbar (the same menu as the landing pages).
     *
     * @param moodle_url $pageurl Current page, for the PT | ES links.
     * @return array
     */
    public static function navbar(moodle_url $pageurl): array {
        $data = self::data();
        if (empty($data['nav'])) {
            return [];
        }
        $lang = self::lang();
        $nav = $data['nav'];
        $map = fn(array $items) => array_map(fn($it) => self::item($it, $lang), $items);

        $dropdowns = [
            [
                'id' => 'brasil',
                'label' => self::pick($nav['brasil']['label'], $lang),
                'icon' => $nav['brasil']['icon'],
                'wide' => true,
                'all' => self::item($nav['brasil']['all'], $lang),
                'groups' => array_map(fn($g) => [
                    'title' => self::pick($g['title'], $lang),
                    'items' => $map($g['items']),
                ], $nav['brasil']['groups']),
            ],
        ];
        foreach (['argentina', 'agenda', 'loja'] as $key) {
            $dropdowns[] = [
                'id' => $key,
                'label' => self::pick($nav[$key]['label'], $lang),
                'icon' => $nav[$key]['icon'],
                'wide' => false,
                'items' => $map($nav[$key]['items']),
            ];
        }

        $langs = [];
        foreach (['pt' => 'flag_br', 'es' => 'flag_ar'] as $code => $flag) {
            $url = new moodle_url($pageurl, [self::LANGPARAM => $code]);
            if ($moodlelang = self::moodle_lang($code)) {
                $url->param('lang', $moodlelang);
            }
            $langs[] = [
                'code' => strtoupper($code),
                'title' => $code === 'pt' ? 'Português (Brasil)' : 'Español (Argentina)',
                'hreflang' => $code === 'pt' ? 'pt-BR' : 'es-AR',
                'href' => $url->out(false),
                'active' => $code === $lang,
                'flag' => $data['icons'][$flag] ?? '',
            ];
        }

        $t = [
            'pt' => ['home' => 'Ivana Academy - página inicial', 'open' => 'Abrir menu', 'close' => 'Fechar menu',
                'main' => 'Principal'],
            'es' => ['home' => 'Ivana Academy - inicio', 'open' => 'Abrir menú', 'close' => 'Cerrar menú',
                'main' => 'Principal'],
        ][$lang];

        return [
            'lang' => $lang,
            'homeurl' => self::pick($nav['home'], $lang),
            'homelabel' => $t['home'],
            'openlabel' => $t['open'],
            'closelabel' => $t['close'],
            'mainlabel' => $t['main'],
            'dropdowns' => $dropdowns,
            'interesse' => [
                'label' => self::pick($nav['interesse']['label'], $lang),
                'href' => self::pick($nav['interesse']['href'], $lang),
                'icon' => $nav['interesse']['icon'],
            ],
            'portal' => [
                'label' => self::pick($nav['portal']['label'], $lang),
                'href' => (new moodle_url('/login/index.php'))->out(false),
                'icon' => $nav['portal']['icon'],
            ],
            'langs' => $langs,
            'icons' => $data['icons'] ?? [],
        ];
    }

    /**
     * Template context of the footer: the same footer as the landing pages, plus a small
     * "platform maintained by LEODG" line.
     *
     * @return array
     */
    public static function footer(): array {
        global $CFG;

        $data = self::data();
        if (empty($data['footer'])) {
            return [];
        }
        $lang = self::lang();
        $f = $data['footer'];
        $text = $f['text'][$lang] ?? [];

        $legal = array_map(fn($l) => [
            'label' => self::pick($l['label'], $lang),
            'href' => self::pick($l['href'], $lang),
        ], $f['legal']);
        if ($lang === 'es') {
            foreach ($f['legal_es_extra'] as $l) {
                $legal[] = ['label' => $l['label'], 'href' => $l['href']];
            }
        }

        $t = [
            'pt' => ['social' => 'Redes e contato', 'legal' => 'Informações legais', 'cookies' => 'Preferências de cookies',
                'maintained' => 'Plataforma mantida e atualizada por'],
            'es' => ['social' => 'Redes y contacto', 'legal' => 'Información legal', 'cookies' => 'Preferencias de cookies',
                'maintained' => 'Plataforma mantenida y actualizada por'],
        ][$lang];

        return [
            'lang' => $lang,
            'logo' => (new moodle_url('/theme/ivana/pix/logo-square.png'))->out(false),
            'about' => $text['about'] ?? '',
            'address' => trim(($text['addressBR'] ?? '') . ' · ' . ($text['addressAR'] ?? ''), ' ·'),
            'sociallabel' => $t['social'],
            'social' => array_map(fn($l) => [
                'label' => self::pick($l['label'], $lang),
                'href' => self::pick($l['href'], $lang),
                'newtab' => !empty($l['newtab']),
                'icon' => $l['icon'],
            ], $f['social']),
            'groups' => array_map(fn($g) => [
                'title' => self::pick($g['title'], $lang),
                'links' => array_map(fn($l) => [
                    'label' => self::pick($l['label'], $lang),
                    'href' => self::pick($l['href'], $lang),
                    'newtab' => !empty($l['newtab']),
                ], $g['links']),
            ], $f['groups']),
            'legallabel' => $t['legal'],
            'legal' => $legal,
            'cookieslabel' => $t['cookies'],
            'company' => $f['company']['legalName'] . ' · CNPJ ' . $f['company']['cnpj'] . ' · '
                . self::pick($f['company']['email'], $lang),
            'rights' => $text['rights'] ?? '',
            'maintainedby' => $t['maintained'],
            'leodglogo' => $CFG->wwwroot . '/theme/ivana/pix/leodg.svg',
        ];
    }
}
