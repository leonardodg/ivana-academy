# Tema Ivana Academy v2 (`theme_ivana`): o que é e como levar à produção

Tema novo para o Moodle ter o mesmo visual das landing pages (courses.ivana.academy): mesmas
cores, fontes, logo, menu principal e rodapé. Quem passa da LP para a plataforma não deve perceber a troca.

- **Base:** fork do nosso `theme/moove` (= upstream `MOODLE_405_STABLE` 4.5.2 + ajustes Ivana).
  O `main` do Moove no GitHub exige Moodle 5.3 e Bootstrap 5 e não serve no 4.5.
- **Visitante:** navbar e rodapé da LP. **Logado:** navbar do Moodle (Home, Meus cursos,
  Administração, menu do usuário, Modo de edição) com o visual da LP, e o rodapé da LP.
- **Menu e rodapé** vêm de `theme/ivana/data/lp.json`, gerado do projeto da LP:
  `node theme/ivana/tools/export_lp.mjs ~/localhost/ivana-courses-landing-pages` (depois purge e commit).
- **Removido:** slider, caixa de marketing, números, FAQ, banner da Conecti.me, opções de cor e
  fonte (o visual fica travado nos tokens da LP) e Google Fonts (as fontes ficam no tema).
- **Mantido:** imagem de login (`loginbgimg`), GA4 (`googleanalytics`), barra de acessibilidade
  (as preferências dos alunos continuam valendo) e os cards de SEO (`local_seo`).
- `theme_moove` **continua instalado** para rollback.

## Roteiro na produção (você executa; `moosh` = `php admin/cli/*` como www-data)

Antes de tudo: dump do banco (`/backups/backup.sh` ou manual) e horário de pouco acesso.

### 0. Antes do merge: desinstalar o Boost Union (o código ainda está na produção)

O PR apaga `theme/boost_union`. Ele está instalado e sem uso (nenhum curso, usuário, categoria
ou coorte usa). Desinstale **enquanto o código existe**:

```bash
moosh admin/cli/uninstall_plugins.php --plugins=theme_boost_union --run
```

### 1. Merge do PR → deploy automático (`production.yml`)

O código sobe sozinho. O `theme_ivana` é plugin novo, então o **upgrade é manual**:

```bash
moosh admin/cli/upgrade.php --non-interactive     # instala theme_ivana; copia do Moove: login, GA4, redes
# Depois de apagar o código do Boost Union, as definições de cache dele ficam no MUC
# ("The override class does not exist" no purge). No staging isto resolveu:
moosh -r 'define("CLI_SCRIPT",1); require "/var/www/html/config.php"; \core_cache\helper::update_definitions();'
moosh admin/cli/purge_caches.php
```

(`moosh` é `docker exec -u www-data moodle php "$@"`, então `moosh -r` é `php -r`.)

### 2. Testar sem trocar para todo mundo

```bash
moosh admin/cli/cfg.php --name=allowthemechangeonurl --set=1
```
Abrir `https://ivana.academy/?theme=ivana` (o tema fica na sua sessão). Conferir: home de
visitante, login, cadastro, lembrete de senha, um curso e uma atividade logado como aluno, Admin.
Depois **desligar** de novo: `--name=allowthemechangeonurl --set=0`.

### 3. Ativar

```bash
moosh admin/cli/cfg.php --name=theme --set=ivana
moosh admin/cli/purge_caches.php
```
(ou *Administração do site › Aparência › Temas*).

### 4. Depois de ativar

- **Idioma:** com os pacotes `es_ar` e `pt_br` instalados (*Idioma › Pacotes de idioma*),
  definir o padrão em espanhol: `moosh admin/cli/cfg.php --name=lang --set=es_ar`. O menu e o
  rodapé seguem PT/ES; o aviso de cookies passa a sair no idioma da aluna.
- **Política de privacidade v5:** `.devcontainer/docs/POLITICA-PRIVACIDADE-v5.md` (a v4 cita
  Google Fonts, que o tema não usa mais, e não cita o reCAPTCHA).
- **Logo:** o tema usa o logo da LP (`theme/ivana/pix/logo.svg`). Não precisa subir nada.

## Rollback

```bash
moosh admin/cli/cfg.php --name=theme --set=moove
moosh admin/cli/purge_caches.php
```
O Moove continua instalado com a configuração antiga. O Boost Union não volta sozinho: se
precisar, restaure o código de `main` anterior ao PR e rode o upgrade.

## Staging

`.devcontainer/staging` (cópia fiel da produção). Para ver o tema novo: `https://localhost:8543/?theme=ivana`
(`allowthemechangeonurl` ligado no staging). O staging usa as chaves de **teste** do reCAPTCHA
do Google, para o cadastro funcionar em localhost; a chave real fica só no banco da produção.
