#!/usr/bin/env node
// Exporta o menu principal e o rodape das landing pages (projeto ivana-courses-landing-pages)
// para data/lp.json, lido pelo tema (classes/local/lp.php). Assim o menu e os links do Moodle
// saem da MESMA fonte que a LP: src/app/data/site_nav.ts, site_links.ts, legal/company.ts,
// lang/footer.ts e os icones (lucide + bandeiras).
//
// Uso (precisa do node_modules da LP instalado):
//   node theme/ivana/tools/export_lp.mjs /caminho/para/ivana-courses-landing-pages
//
// Depois: revisar o diff de data/lp.json, purge de caches, commit.

import { createRequire } from 'node:module';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const lpRoot = resolve(process.argv[2] || '');
if (!process.argv[2]) {
  console.error('Uso: node export_lp.mjs <pasta do projeto ivana-courses-landing-pages>');
  process.exit(1);
}
const out = join(dirname(fileURLToPath(import.meta.url)), '..', 'data', 'lp.json');
const lpRequire = createRequire(join(lpRoot, 'package.json'));
const esbuild = lpRequire('esbuild');

// Entrada virtual: importa os dados da LP e devolve um objeto serializavel.
const entry = `
import { renderToStaticMarkup } from 'react-dom/server';
import { createElement } from 'react';
import * as nav from './src/app/data/site_nav';
import { site_link_groups, social_links } from './src/app/data/site_links';
import { company, legalLinks } from './src/app/legal/company';
import { footer_text } from './src/app/lang/footer';
import { nav_icons } from './src/app/ui/navIcons';
import { Heart, ShoppingBag, CalendarDays, GraduationCap, Instagram, Mail, MessageCircle, Menu, X, ChevronDown } from 'lucide-react';
import { ArgentinaFlag, BrazilFlag } from './src/app/ui/Flags';

const svg = (C, cls) => renderToStaticMarkup(createElement(C, { className: cls, 'aria-hidden': 'true' }))
  .replace(/ class="[^"]*"/, cls ? ' class="' + cls + '"' : '');
const site = company.site.replace(/\\/+$/, '');
const abs = (href) => (/^https?:/.test(href) || href.startsWith('mailto:') ? href : site + href);
const lbl = (l) => (typeof l === 'string' ? { pt: l, es: l } : { pt: l.pt, es: l.es });
const item = (it) => ({
  label: lbl(it.label),
  href: abs(it.href),
  external: true,
  newtab: !!it.external,
  icon: it.icon ? svg(nav_icons[it.icon], 'ivana-nav-icon') : null,
});

export default {
  source: 'ivana-courses-landing-pages',
  site,
  nav: {
    brasil: { label: { pt: 'Brasil', es: 'Brasil' }, icon: svg(BrazilFlag, 'ivana-flag'), all: item(nav.nav_brasil_todos),
      groups: nav.nav_brasil.map((g) => ({ title: lbl(g.title), items: g.items.map(item) })) },
    argentina: { label: { pt: 'Argentina', es: 'Argentina' }, icon: svg(ArgentinaFlag, 'ivana-flag'), items: nav.nav_argentina.map(item) },
    agenda: { label: { pt: 'Agenda', es: 'Agenda' }, icon: svg(CalendarDays, 'ivana-nav-icon'), items: nav.nav_agenda.map(item) },
    loja: { label: { pt: 'Loja', es: 'Tienda' }, icon: svg(ShoppingBag, 'ivana-nav-icon'), items: nav.nav_loja.map(item) },
    interesse: { label: lbl(nav.nav_interesse.label), icon: svg(Heart, 'ivana-nav-icon'),
      href: { pt: abs(nav.nav_interesse.href.pt), es: abs(nav.nav_interesse.href.es) } },
    portal: { label: lbl(nav.nav_portal.label), icon: svg(GraduationCap, 'ivana-nav-icon') },
    home: { pt: site + '/pt-br/', es: site + '/es-ar/' },
  },
  icons: { menu: svg(Menu, 'ivana-nav-icon'), close: svg(X, 'ivana-nav-icon'), chevron: svg(ChevronDown, 'ivana-chevron'),
    flag_br: svg(BrazilFlag, 'ivana-flag'), flag_ar: svg(ArgentinaFlag, 'ivana-flag') },
  footer: {
    text: { pt: footer_text.pt, es: footer_text.es },
    groups: site_link_groups.map((g) => ({ title: lbl(g.title), links: g.links.map((l) => ({
      label: lbl(l.label), href: { pt: abs(l.href), es: abs(l.hrefEs || l.href) }, newtab: !!l.external })) })),
    social: social_links.map((s) => ({ label: lbl(s.label), href: { pt: abs(s.href), es: abs(s.hrefEs) }, newtab: !!s.external,
      icon: svg({ instagram: Instagram, whatsapp: MessageCircle, mail: Mail }[s.icon], 'ivana-social-icon') })),
    legal: legalLinks.map((l) => ({ label: lbl(l.label), href: { pt: abs(l.href), es: abs(l.hrefEs) } })),
    legal_es_extra: [
      { label: 'Botón de arrepentimiento', href: abs('/es-ar/arrepentimiento/') },
      { label: 'Defensa de las y los Consumidores: para reclamos, ingresá acá', href: 'https://www.argentina.gob.ar/produccion/defensadelconsumidor/formulario' },
    ],
    company: { legalName: company.legalName, cnpj: company.cnpj, email: { pt: company.email, es: company.ar.email } },
  },
};
`;

const dir = mkdtempSync(join(tmpdir(), 'ivana-lp-'));
const bundle = join(dir, 'lp.cjs');
try {
  await esbuild.build({
    stdin: { contents: entry, resolveDir: lpRoot, loader: 'tsx' },
    bundle: true,
    platform: 'node',
    format: 'cjs',
    jsx: 'automatic',
    outfile: bundle,
    define: { 'import.meta.env': '{}' },
    logLevel: 'warning',
  });
  const data = createRequire(bundle)(bundle).default;
  writeFileSync(out, JSON.stringify(data, null, 2) + '\n');
  console.log('Gerado ' + out);
} finally {
  rmSync(dir, { recursive: true, force: true });
}
