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

$string['consent_accept'] = 'Aceptar';
$string['consent_policy'] = 'Política de privacidad';
$string['consent_reject'] = 'Rechazar';
$string['consent_terms'] = 'Al continuar navegando, aceptas nuestras políticas:';
$string['consent_text'] = 'Usamos cookies de análisis (Google Analytics) para entender cómo se usa el sitio y mejorar nuestros cursos. No usamos cookies de publicidad.';
$string['consentbanner'] = 'Aviso de consentimiento de análisis';
$string['consentbanner_desc'] = 'Define los valores por defecto del Consent Mode de Google (análisis denegado hasta aceptar, publicidad siempre denegada) y muestra un aviso. La elección se guarda solo en el navegador del visitante.';
$string['coursemoreinfo'] = 'Conocé el curso';
$string['landingcanonical'] = 'Mismo producto en la landing (canónica)';
$string['landingcanonical_desc'] = 'Una línea por curso: <code>idcurso URL</code>. La página del curso apunta su URL canónica a la landing, y Google muestra la landing. Usar solo cuando la landing vende este mismo curso (un profesorado vendido en la página de su tecnicatura cuenta). Lo que va después de # es un comentario.';
$string['landingparent'] = 'Parte de una carrera en la landing';
$string['landingparent_desc'] = 'Una línea por curso: <code>idcurso URL</code>, para un curso que es módulo de una carrera vendida en esa página. La página del curso queda fuera de Google (noindex) y los visitantes que hacen clic van a la landing. Los cursos que no están en ninguna lista son noindex.';
$string['pluginname'] = 'SEO';
$string['privacy:metadata'] = 'El plugin SEO no almacena datos personales. La elección de consentimiento se guarda solo en el navegador del visitante (cookie ivana_analytics_consent).';
$string['sameas'] = 'Perfiles de la organización (sameAs)';
$string['sameas_desc'] = 'Una URL por línea: sitio oficial, redes sociales. Se usa en los datos estructurados de la página principal.';
$string['sitedescription'] = 'Descripción del sitio';
$string['sitedescription_desc'] = 'Meta description de la página principal y de páginas sin resumen propio (máx. 160 caracteres). Vacío: usa el resumen del sitio.';
