# Auditoria SEO — ivana.academy (Moodle) — 11/11/2026

Feita com o plugin claude-seo 2.6.0 (page_checks, sitemap_discovery, agentic_check, render_page)
e mais Search Console, GA4 e medições diretas. Foi feita **depois** do deploy dos PRs #118/#119,
então mede o site já corrigido. O PageSpeed Insights recusou (limite de cota da API compartilhada):
os números de performance são de medição direta, não de Core Web Vitals de campo.

## Nota: 55/100

| Categoria | Peso | Nota | Resumo |
|---|---|---|---|
| SEO técnico | 22% | 75 | canonical, robots, noindex e HTTPS certos; **sem sitemap do Moodle** |
| Conteúdo | 23% | 45 | páginas de curso com 55–65 palavras; H1 genérico |
| On-page | 20% | 55 | home sem H1; títulos de curso começam por "Información del curso" |
| Dados estruturados | 10% | 60 | Organization + Course válidos; Course sem `offers`/`hasCourseInstance` |
| Performance | 10% | 30 | **15 MB de imagens na home**, sem lazy-load |
| Busca por IA | 10% | 60 | HTML no servidor (bom); Cloudflare bloqueia bots de treino |
| Imagens | 5% | 40 | alt ok; sem largura/altura; arquivos de até 1,6 MB |

## O que já está bom (efeito dos PRs #118/#119)

- Home com `canonical` próprio, descrição de 158 caracteres, Open Graph `es_AR` e JSON-LD
  `EducationalOrganization` válido.
- Páginas de sistema com `noindex` (meta + `X-Robots-Tag`); `robots.txt` bloqueia `switchdevice`,
  `search`, `sesskey`.
- Os 68 links de curso da home vão para `course/info.php` (público), não para `course/view.php`.
- Cada `course/info.php` tem `canonical`, descrição, `og:image` do curso e JSON-LD `Course`.
- Conteúdo renderizado no servidor (1.193 palavras sem JavaScript): Google e bots de IA leem tudo.
- TTFB 0,18–0,42 s; HTML da home 18 KB comprimido.
- GA4 recebendo dados com Consent Mode (confirmado em tempo real).

## Search Console (antes das correções fazerem efeito)

- 62 URLs indexadas no domínio inteiro; do Moodle: a home, 4 variações `?lang=`, 8 páginas
  `/admin/tool/` e o `forgot_password`. **Nenhuma página de curso.**
- Os 4 cursos inspecionados: "O Google não reconhece o URL — nenhum sitemap de referência".
- Não indexadas (40): todas exclusões corretas (`course/view.php` com login, `switchdevice` 404,
  páginas da loja e da landing). **Não validar esses grupos**: a validação falharia.
- Feito em 11/11: remoção temporária de `/admin/tool/*` e `forgot_password.php`; indexação
  solicitada para a home e os cursos 25, 26, 7 e 39.
- Fora do Moodle, indexados e que não deveriam: `webhook.ivana.academy` (n8n) e páginas de conta
  da `loja.ivana.academy` (`customer/account/*`, `sales/order/history`).

## Achados, por prioridade

### Alta

1. **Sem sitemap do Moodle.** O `robots.txt` só aponta o sitemap da landing, que não lista os
   cursos. O Google não conhece nenhum `course/info.php`.
   *Correção:* `local_seo` gera `/local/seo/sitemap.php` (home, `course/index.php`, um
   `course/info.php` por curso visível, com `lastmod`), linha `Sitemap:` no `robots.txt`, e envio
   no Search Console. *Como saber se falhou:* em 2–4 semanas, "Páginas indexadas" continua sem
   `course/info.php`.
2. **15 MB de imagens na home.** 34 imagens de curso como `background-image` (o navegador baixa
   todas na hora), 9 acima de 500 KB, a maior com 1,6 MB. No celular isso derruba o LCP.
   *Correção:* nos cards do Moove (`moove_coursecard.mustache`, `core_course/coursecard.mustache`),
   trocar o `background-image` por `<img loading="lazy" decoding="async" width height>` com
   `object-fit: cover`; e reenviar as imagens maiores com até ~1200 px / 200 KB (conteúdo).
   *Como saber se falhou:* peso da home acima de 3 MB no primeiro carregamento.
3. **Páginas de curso fracas.** O título é "Información del curso | Nome | Ivana Academy"
   (até 75 caracteres), o H1 é "Información del curso" em todas, e o texto tem 55–115 caracteres
   de resumo. Para o Google são páginas finas e iguais entre si.
   *Correção (código):* no `course/info.php`, título "Nome do curso | Ivana Academy" e H1 = nome
   do curso (pelo renderer do tema, sem mexer no core).
   *Correção (conteúdo, Ivana):* resumo de 150–300 palavras por curso: para quem é, o que se
   aprende, duração, formato (vídeo/PDF), certificado, professora.

### Média

4. **Home sem H1** e título curto ("Inicio | Ivana Academy", 22 caracteres).
   *Correção:* H1 com o nome e a proposta (ex.: "Ivana Academy — cursos online de artesanía");
   nome da página inicial em *Administração › Página inicial* com algo como
   "Cursos online de artesanía | Ivana Academy".
5. **Course sem dados para rich result.** Faltam `offers` (preço/moeda) e `hasCourseInstance`
   (`courseMode: online`, `courseWorkload`).
   *Correção:* `local_seo` lê o custo do método de inscrição (PayPal) e preenche os dois.
6. **`sameAs` não bate com o rodapé.** O rodapé tem YouTube, TikTok, Facebook e
   Instagram `ivana_da_mora`; o `sameAs` tem só os dois Instagram `ivana.lerea.*` e a landing.
   *Correção (configuração):* *Plugins › local_seo › sameAs*, uma URL por linha com todos os
   perfis oficiais.
7. **6 links de redes sociais sem texto acessível** (só ícone). *Correção:* `aria-label` nos
   ícones do rodapé do Moove.
8. **Subdomínios de infraestrutura indexáveis.** `webhook.ivana.academy` já está indexado. Em
   11/11, `minio`, `nocodb`, `pgadmin`, `rabbitmq`, `redis`, `evolution` e `whatsapi` respondiam
   **sem** `X-Robots-Tag`. `bot`, `chatwoot`, `n8n`, `typebot` e `webhook` já mandavam `noindex`,
   vindo do próprio app. O `robots.txt` deles é o do Cloudflare (`Allow: /`).
   **Segurança, antes do SEO:** `redis.ivana.academy` abre o "Redis Commander" (HTTP 200) e
   `rabbitmq`/`minio` abrem os painéis de admin na internet.
   *Correção:*
   - Uma Transform Rule no Cloudflare (*Rules › Transform Rules › Modify Response Header*) com
     `X-Robots-Tag: noindex, nofollow` para todos esses hosts. Não usar `Disallow` no
     `robots.txt`: com `Disallow`, o Google não vê o `noindex` e a URL fica indexada.
   - Cloudflare Access na frente dos painéis (`pgadmin`, `redis`, `rabbitmq`, `minio`, `nocodb`,
     editor do n8n).
   - Remoção temporária no Search Console por prefixo de host.
   - Trocar as senhas do n8n.
   A `loja` fica fora da regra: ela vende os cursos. Só as páginas de conta, pedidos e busca
   (`customer/`, `sales/`, `checkout/`, `catalogsearch/`) devem receber `noindex` no Magento.

### Baixa

9. Imagens sem `width`/`height` (risco de CLS), incluindo o logo.
10. `Content-Signal` do Cloudflare só no grupo `*`; os grupos nomeados (GPTBot, ClaudeBot…) não
    o repetem. Só importa se a política de IA mudar.
11. Sem `/llms.txt` (Google ignora; só conta para o Lighthouse Agentic).
12. As 4 URLs `?lang=` indexadas saem sozinhas: agora todas têm `canonical` para `/`.

### Informativo

- O Cloudflare (robots gerenciado) bloqueia bots de **treino** de IA (GPTBot, ClaudeBot,
  Google-Extended, CCBot…). A busca do Google e as AI Overviews não usam o Google-Extended;
  isso não afeta o ranking.

## Plano

| Fase | Quando | Itens | Quem |
|---|---|---|---|
| 1 | esta semana | sitemap (1), cards com lazy-load (2), título/H1 do curso (3), H1 da home (4) | código (PR) |
| 1 | esta semana | `sameAs` (6), nome da página inicial (4), noindex do webhook (8) | configuração |
| 2 | 2–3 semanas | resumos dos cursos (3), imagens menores (2) | Ivana |
| 2 | 2–3 semanas | `offers`/`hasCourseInstance` (5), `aria-label` (7), dimensões (9) | código |
| 3 | mensal | Search Console: páginas `course/info.php` indexadas, impressões fora da marca | acompanhamento |

**Indicadores para acompanhar** (sem rodar a auditoria de novo):
- Search Console › Páginas: número de `course/info.php` indexados (hoje 0).
- Search Console › Desempenho: consultas que não contêm "ivana" (hoje quase zero).
- GA4: sessões "Organic Search" no fluxo Academy.
