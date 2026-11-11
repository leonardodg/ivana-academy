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

### Decisão de 11/11: os cursos pertencem à landing

A landing (`courses.ivana.academy`) informa e vende; o Moodle é a plataforma. Então o
`course/info.php` não deve disputar com ela no Google. Isso substitui os itens "sitemap do
Moodle" e "conteúdo das páginas de curso" da primeira versão desta auditoria.

*Correção (PR `feat/seo-landing-canonical`, `local_seo` 1.1.0):*
- Duas listas em *Plugins › local_seo*, uma linha `idcurso URL` por curso:
  - **Mesmo produto na landing**: `course/info.php` aponta o `canonical` para a landing.
    São 11 cursos: as 6 carreras, os 3 profesorados (continuação da tecnicatura, vendidos na
    mesma página) e os 2 cursos online pt-BR.
  - **Parte de uma carrera**: módulos vendidos dentro de uma carrera. Ficam com `noindex`, e
    o visitante que clica vai para a página da carrera. São 9 cursos.
  - Fora das duas listas: `noindex`. São 14 cursos.
- Na home do Moodle, para quem não fez login, o card leva à landing quando há ligação.
- O JSON-LD `Course` saiu do Moodle: ele fica nas páginas da landing, que são as canônicas.
- Os cursos sem página na landing estão listados no plano da landing
  (`ivana-courses-landing-pages/docs/SEO-AUDITORIA-2026-11.md`, "Cursos do Moodle sem página na
  landing"). Ao criar uma página, basta acrescentar a linha na lista, sem deploy.

*Como saber se falhou:* em 4–8 semanas, o Search Console ainda mostra `course/info.php`
indexado, ou as páginas da landing não ganham impressões nas buscas pelo nome dos cursos.

### Alta

1. **15 MB de imagens na home.** 34 imagens de curso como `background-image` (o navegador baixa
   todas na hora), 9 acima de 500 KB, a maior com 1,6 MB.
   *Correção (no mesmo PR):* os cards do Moove usam `<img loading="lazy" decoding="async">`.
   Medido no staging: 7,8 MB e 20 imagens no primeiro carregamento, em vez de 15 MB e 34.
   *Falta (conteúdo):* reenviar as imagens maiores com até ~1200 px e 200 KB. Sem isso a home
   continua pesada no celular.

### Média

4. **Home sem H1** e título curto ("Inicio | Ivana Academy", 22 caracteres).
   *Correção:* H1 com o nome do site no tema (no mesmo PR, visível só para leitores de tela).
   Falta o título: nome da página inicial em *Administração › Página inicial*, com algo como
   "Cursos online de artesanía | Ivana Academy".
5. ~~Course sem dados para rich result~~: o JSON-LD `Course` agora fica só na landing (ver o plano
   da landing).
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
   **Feito em 11/11:** a Transform Rule "noindex subdominios de infraestrutura" está ativa
   (os 12 hosts conferidos de fora) e foi pedida a remoção temporária dos 12 hosts no Search
   Console. O Cloudflare Access continua pendente.
   A `loja` fica fora da regra: ela vende os cursos. Só as páginas de conta, pedidos e busca
   (`customer/`, `sales/`, `checkout/`, `catalogsearch/`) devem receber `noindex` no Magento.

### Baixa

9. Imagens sem `width`/`height` (risco de CLS). Os cards de curso já têm (mesmo PR); falta o logo.
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
| 1 | esta semana | canonical/noindex dos cursos e cards para a landing; lazy-load; H1 da home | código (PR `feat/seo-landing-canonical`) |
| 1 | esta semana | `sameAs` (6), nome da página inicial (4) | configuração |
| 2 | 2–3 semanas | imagens menores (1); páginas dos cursos que faltam na landing | Ivana / projeto da landing |
| 2 | 2–3 semanas | `aria-label` (7), dimensões (9) | código |
| 3 | mensal | Search Console: `course/info.php` fora do índice, páginas da landing com impressões | acompanhamento |

**Indicadores para acompanhar** (sem rodar a auditoria de novo):
- Search Console › Páginas: `course/info.php` deve continuar fora do índice; as páginas da landing,
  dentro.
- Search Console › Desempenho: consultas que não contêm "ivana" (hoje quase zero).
- GA4: sessões "Organic Search" no fluxo Academy.
