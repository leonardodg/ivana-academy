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

use moodle_url;

/**
 * Google Consent Mode v2 defaults + a small analytics cookie banner.
 *
 * Without an explicit choice the Google tag sent analytics_storage=denied
 * (gcs=G100) and GA4 recorded nothing. This sets the defaults before the
 * theme's gtag('config'), shows a banner until the visitor chooses, and
 * stores the choice in a first-party cookie only. Ads stay denied.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consent {
    /** @var string Cookie holding 'granted' or 'denied'. */
    const STORAGE_KEY = 'ivana_analytics_consent';

    /**
     * Inline <script> for <head>; empty when the banner is disabled.
     *
     * @return string
     */
    public static function head_html(): string {
        if (get_config('local_seo', 'consentbanner') === '0') {
            return '';
        }
        $strings = [
            'text' => get_string('consent_text', 'local_seo'),
            'accept' => get_string('consent_accept', 'local_seo'),
            'reject' => get_string('consent_reject', 'local_seo'),
            'policy' => get_string('consent_policy', 'local_seo'),
            'terms' => get_string('consent_terms', 'local_seo'),
            'policyurl' => (new moodle_url('/admin/tool/policy/viewall.php'))->out(false),
            'key' => self::STORAGE_KEY,
        ];
        $config = json_encode($strings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

        return '<script>' . "\n" . self::script($config) . "\n" . '</script>' . "\n";
    }

    /**
     * The banner script with the JSON config inlined.
     *
     * @param string $config JSON object (already escaped for <script>).
     * @return string
     */
    private static function script(string $config): string {
        return <<<JS
(function(cfg){
  window.dataLayer = window.dataLayer || [];
  window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
  // A first-party cookie, not localStorage: Moodle's storagewrapper clears all of
  // localStorage whenever jsrev changes (every purge_caches/deploy).
  var m = document.cookie.match(new RegExp('(?:^|; )' + cfg.key + '=(granted|denied)'));
  var choice = m ? m[1] : null;
  window.gtag('consent', 'default', {
    ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied',
    analytics_storage: choice === 'granted' ? 'granted' : 'denied',
    wait_for_update: 500
  });

  // tool_policy shows guests its own bar ("If you continue browsing... Terms of Use").
  // While this banner is up it carries that notice too, and either button also
  // records tool_policy's acceptance, so the visitor sees one notice, not two.
  var TERMS_COOKIE = 'EU_COOKIE_LAW_CONSENT';
  function termsPending() {
    return !!document.querySelector('.eupopup-markup') && document.cookie.indexOf(TERMS_COOKIE + '=') === -1;
  }
  function acceptTerms() {
    var d = new Date(); d.setTime(d.getTime() + 30 * 864e5);   // same as tool_policy
    document.cookie = TERMS_COOKIE + '=true; expires=' + d.toGMTString() + ';path=/';
  }
  function decide(value, box, terms) {
    document.cookie = cfg.key + '=' + value + ';max-age=15552000;path=/;SameSite=Lax'
      + (location.protocol === 'https:' ? ';Secure' : '');   // 180 days
    window.gtag('consent', 'update', {analytics_storage: value});   // reopened banner can also withdraw
    if (terms) { acceptTerms(); }
    box.parentNode && box.parentNode.removeChild(box);   // the style hiding tool_policy's bar stays
  }
  function link(href, text) {
    var a = document.createElement('a');
    a.href = href; a.textContent = text;
    return a;
  }
  function show() {
    var terms = termsPending();
    var box = document.createElement('div');
    box.id = 'local-seo-consent';
    box.setAttribute('role', 'region');
    box.setAttribute('aria-label', cfg.policy);
    box.style.cssText = 'position:fixed;left:16px;right:16px;bottom:16px;z-index:10000;max-width:640px;margin:0 auto;'
      + 'background:#fff;color:#1d2125;border:1px solid #dee2e6;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.15);'
      + 'padding:16px;font-size:14px;line-height:1.5;display:flex;flex-wrap:wrap;gap:12px;align-items:center;';
    var p = document.createElement('p');
    p.style.cssText = 'margin:0;flex:1 1 280px;';
    p.appendChild(document.createTextNode(cfg.text + ' '));
    if (terms) {
      // The guest policy links tool_policy rendered (name + view.php?versionid=...).
      var links = document.querySelectorAll('.eupopup-markup a[data-action="view-guest"]');
      p.appendChild(document.createTextNode(cfg.terms + ' '));
      Array.prototype.forEach.call(links, function(l, i) {
        if (i) { p.appendChild(document.createTextNode(', ')); }
        p.appendChild(link(l.getAttribute('href'), l.textContent.trim()));
      });
      p.appendChild(document.createTextNode('.'));
      var css = document.createElement('style');
      css.id = 'local-seo-consent-css';
      css.textContent = '.eupopup-container{display:none!important}';
      document.head.appendChild(css);
    } else {
      p.appendChild(link(cfg.policyurl, cfg.policy));
    }
    var actions = document.createElement('div');
    actions.style.cssText = 'display:flex;gap:8px;';
    [['reject', 'denied', 'btn btn-outline-secondary'], ['accept', 'granted', 'btn btn-primary']].forEach(function(b){
      var btn = document.createElement('button');
      btn.type = 'button'; btn.className = b[2]; btn.textContent = cfg[b[0]];
      btn.addEventListener('click', function(){ decide(b[1], box, terms); });
      actions.appendChild(btn);
    });
    box.appendChild(p); box.appendChild(actions);
    document.body.appendChild(box);
  }
  // "Cookie preferences" in the theme footer reopens the banner to change the choice.
  window.localSeoConsentOpen = function() {
    if (!document.getElementById('local-seo-consent')) { show(); }
  };
  if (choice) { return; }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', show); } else { show(); }
})({$config});
JS;
    }
}
