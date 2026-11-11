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
 * stores the choice in the browser (localStorage) only. Ads stay denied.
 *
 * @package    local_seo
 * @copyright  2026 Ivana Academy
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consent {
    /** @var string localStorage key holding 'granted' or 'denied'. */
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
  var choice = null;
  try { choice = window.localStorage.getItem(cfg.key); } catch (e) {}
  window.gtag('consent', 'default', {
    ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied',
    analytics_storage: choice === 'granted' ? 'granted' : 'denied',
    wait_for_update: 500
  });
  if (choice === 'granted' || choice === 'denied') { return; }
  function decide(value, box) {
    try { window.localStorage.setItem(cfg.key, value); } catch (e) {}
    if (value === 'granted') { window.gtag('consent', 'update', {analytics_storage: 'granted'}); }
    box.parentNode && box.parentNode.removeChild(box);
  }
  function show() {
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
    var a = document.createElement('a');
    a.href = cfg.policyurl; a.textContent = cfg.policy;
    p.appendChild(a);
    var actions = document.createElement('div');
    actions.style.cssText = 'display:flex;gap:8px;';
    [['reject', 'denied', 'btn btn-outline-secondary'], ['accept', 'granted', 'btn btn-primary']].forEach(function(b){
      var btn = document.createElement('button');
      btn.type = 'button'; btn.className = b[2]; btn.textContent = cfg[b[0]];
      btn.addEventListener('click', function(){ decide(b[1], box); });
      actions.appendChild(btn);
    });
    box.appendChild(p); box.appendChild(actions);
    document.body.appendChild(box);
    // tool_policy shows its own bottom bar to guests (Terms of Use): sit above it, drop when it closes.
    var mo = null;
    function place() {
      if (!box.parentNode) { mo && mo.disconnect(); window.removeEventListener('resize', place); return; }
      var h = 0;   // the bar is position:fixed, so offsetParent is always null: measure it instead
      Array.prototype.forEach.call(document.querySelectorAll('.eupopup-container'), function(bar) {
        var r = bar.getBoundingClientRect();
        if (r.height && window.getComputedStyle(bar).display !== 'none') { h = Math.max(h, window.innerHeight - r.top); }
      });
      box.style.bottom = (16 + h) + 'px';
    }
    place();
    window.addEventListener('resize', place);
    if (window.MutationObserver) {
      mo = new MutationObserver(place);
      mo.observe(document.body, {attributes: true, childList: true, subtree: true,
        attributeFilter: ['style', 'class']});
    }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', show); } else { show(); }
})({$config});
JS;
    }
}
