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
 * Settings for local_seo.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_seo', get_string('pluginname', 'local_seo'));

    $settings->add(new admin_setting_configtextarea(
        'local_seo/sitedescription',
        get_string('sitedescription', 'local_seo'),
        get_string('sitedescription_desc', 'local_seo'),
        '',
        PARAM_TEXT,
        60,
        3
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_seo/sameas',
        get_string('sameas', 'local_seo'),
        get_string('sameas_desc', 'local_seo'),
        "https://courses.ivana.academy/\nhttps://www.instagram.com/ivana.lerea.br/\nhttps://www.instagram.com/ivana.lerea.arg/",
        PARAM_RAW_TRIMMED,
        60,
        5
    ));

    // Course pages belong to the landing site (local_seo\landing). Defaults: map of 2026-11-11.
    $settings->add(new admin_setting_configtextarea(
        'local_seo/landingcanonical',
        get_string('landingcanonical', 'local_seo'),
        get_string('landingcanonical_desc', 'local_seo'),
        implode("\n", [
            '4 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-diseno-velas/  # Tecnicatura Velas',
            '21 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-diseno-velas/  # Profesorado Velas',
            '6 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-decoracion-resinas/  # Tecnicatura Resinas',
            '34 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-decoracion-resinas/  # Profesorado Resinas',
            '5 https://courses.ivana.academy/es-ar/carreras/tecnicatura-macrame/  # Tecnicatura en Macramé',
            '8 https://courses.ivana.academy/es-ar/carreras/tecnicatura-macrame/  # Profesorado Macramé',
            '26 https://courses.ivana.academy/es-ar/carreras/tecnicatura-estampas-textiles/  # Tecnicatura en estampas textiles',
            '25 https://courses.ivana.academy/es-ar/carreras/tecnicatura-diseno-interiores/  # Tecnicatura en Diseño de interiores',
            '7 https://courses.ivana.academy/es-ar/carreras/profesorado-arte-mix-media/  # Profesorado en Arte Mix Media',
            '16 https://courses.ivana.academy/pt-br/cursos/online/velas-aromaticas/  # Velas aromáticas, com moldes e wax melts',
            '38 https://courses.ivana.academy/pt-br/cursos/online/saboaria-com-extratos-naturais/  # Saboaria e extratos naturais',
        ]),
        PARAM_RAW_TRIMMED,
        90,
        12
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_seo/landingparent',
        get_string('landingparent', 'local_seo'),
        get_string('landingparent_desc', 'local_seo'),
        implode("\n", [
            '39 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-decoracion-resinas/  # Mesas de rio',
            '29 https://courses.ivana.academy/es-ar/carreras/tecnicatura-macrame/  # Porta Macetas',
            '36 https://courses.ivana.academy/es-ar/carreras/tecnicatura-macrame/  # Lampara en macrame',
            '43 https://courses.ivana.academy/es-ar/carreras/tecnicatura-macrame/  # Encordado y tapiceria',
            '18 https://courses.ivana.academy/es-ar/carreras/tecnicatura-macrame/  # Vestido de playa',
            '40 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-diseno-velas/  # Velas Coctel',
            '31 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-diseno-velas/  # Velas con piedras y resina',
            '14 https://courses.ivana.academy/es-ar/carreras/tecnicatura-arte-diseno-velas/  # Workshop Velas gourmet',
            '15 https://courses.ivana.academy/es-ar/carreras/profesorado-arte-mix-media/  # Workshop Cuadro con relieve',
        ]),
        PARAM_RAW_TRIMMED,
        90,
        10
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_seo/consentbanner',
        get_string('consentbanner', 'local_seo'),
        get_string('consentbanner_desc', 'local_seo'),
        1
    ));

    $ADMIN->add('localplugins', $settings);
}
