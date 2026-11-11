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

$string['consent_accept'] = 'Aceitar';
$string['consent_policy'] = 'Política de privacidade';
$string['consent_reject'] = 'Recusar';
$string['consent_terms'] = 'Ao continuar navegando, você concorda com nossas políticas:';
$string['consent_text'] = 'Usamos cookies de análise (Google Analytics) para entender como o site é usado e melhorar nossos cursos. Não usamos cookies de publicidade.';
$string['consentbanner'] = 'Aviso de consentimento de análise';
$string['consentbanner_desc'] = 'Define os padrões do Consent Mode do Google (análise negada até aceitar, publicidade sempre negada) e mostra um aviso. A escolha fica salva só no navegador do visitante.';
$string['landingcanonical'] = 'Mesmo produto na landing (canônica)';
$string['landingcanonical_desc'] = 'Uma linha por curso: <code>idcurso URL</code>. A página do curso aponta a URL canônica para a landing, e o Google mostra a landing. Usar só quando a landing vende este mesmo curso (um profesorado vendido na página da sua tecnicatura conta). O que vem depois de # é comentário.';
$string['landingparent'] = 'Parte de uma carrera na landing';
$string['landingparent_desc'] = 'Uma linha por curso: <code>idcurso URL</code>, para um curso que é módulo de uma carrera vendida nessa página. A página do curso fica fora do Google (noindex) e o visitante que clica vai para a landing. Cursos fora das duas listas ficam com noindex.';
$string['pluginname'] = 'SEO';
$string['privacy:metadata'] = 'O plugin SEO não armazena dados pessoais. A escolha de consentimento fica salva só no navegador do visitante (cookie ivana_analytics_consent).';
$string['sameas'] = 'Perfis da organização (sameAs)';
$string['sameas_desc'] = 'Uma URL por linha: site oficial, redes sociais. Usado nos dados estruturados da página inicial.';
$string['sitedescription'] = 'Descrição do site';
$string['sitedescription_desc'] = 'Meta description da página inicial e de páginas sem resumo próprio (máx. 160 caracteres). Vazio: usa o resumo do site.';
