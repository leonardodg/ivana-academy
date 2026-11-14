# Atualização do Moodle a partir do upstream (passo a passo)

Procedimento para trazer uma nova versão do branch estável oficial (`MOODLE_405_STABLE` em
`github.com/moodle/moodle`) e levá-la até a produção. Use para versões de correção e de
segurança (4.5.16 → 4.5.17 …). **Não serve para trocar de versão maior** (4.5 → 5.x).

Fluxo: **worktree → staging com cópia da produção (versão atual) → aplicar o upstream →
upgrade + validação → PR → produção → validação → limpeza.**

> Por que um diff e não `git merge`? Desde a 4.5.1+ o repositório não tem o upstream como
> ancestral: as atualizações entram como diff `versão atual..alvo`. O script
> `.devcontainer/tools/moodle-upstream-update.sh` faz isso. Ele acha a versão atual pelo
> `$release` do `version.php`, aplica o diff, avisa se algum arquivo customizado foi
> alterado e confere que o resultado ficou igual ao upstream.

Nos exemplos abaixo a versão alvo é a **4.5.17**. Troque pela versão que for usar.

---

## 0. Antes de começar

- Docker com `docker compose`, acesso `ssh ivana-moodle-aws` e ~3 GB livres em disco.
- Saiba o que a nova versão corrige: <https://moodledev.io/general/releases> e
  <https://moodle.org/security/>. Se houver **correção de segurança crítica**, o
  procedimento é o mesmo, mas sem esperar a janela.
- A `main` local deve estar atualizada:
  ```bash
  cd ~/ivana-academy
  git fetch origin && git switch main && git pull --ff-only
  ```
- Só pode haver **um staging rodando por vez** (projeto compose `ivana-staging`). Se houver um
  ligado de outro checkout, desligue com `cd .devcontainer/staging && docker compose down -v`.

## 1. Criar o worktree

O worktree isola a atualização. Seu checkout principal continua na branch em que você estava.

```bash
cd ~/ivana-academy
V=4.5.17
WT=~/ivana-academy-$V
git worktree add -b chore/moodle-$V "$WT" main

# Os certificados locais do HTTPS não estão no git: copiar para o worktree
cp .devcontainer/certs/develop-local.{crt,key} "$WT/.devcontainer/certs/"
cd "$WT"
```

Ainda **não** rode o script de atualização. Primeiro sobe a staging com o código atual, para ter
uma linha de base (passo 3).

## 2. Baixar o backup da produção

```bash
mkdir -p ~/ivana-staging && cd ~/ivana-staging
ssh ivana-moodle-aws 'ls -lht /backups | head'            # escolher o dump mais recente
scp ivana-moodle-aws:/backups/<dump>.sql.gz .
scp ivana-moodle-aws:/backups/<moodledata>.tar.gz .        # opcional (sem ele o tema perde logo/imagens)
cd "$WT"
```

O dump diário tem cerca de 10 MB. O moodledata completo só é gerado no dia 1 de cada mês.
**São dados pessoais de clientes:** ficam só na sua máquina e são apagados no passo 10.

## 3. Subir a staging (cópia da produção, versão atual)

```bash
STAGING_RESET=1 .devcontainer/staging/restore.sh \
  ~/ivana-staging/<dump>.sql.gz ~/ivana-staging/<moodledata>.tar.gz
```

O `restore.sh` importa o dump numa MariaDB 11.4 e anonimiza os dados (`anonymize.sql`). Ele
troca os e-mails para `user<id>@staging.invalid`, zera SMTP, push, tokens e pagamentos e desliga
as tarefas agendadas. **Ele aborta se sobrar algum e-mail real.** Depois sobe a mesma imagem da
produção com o código **deste worktree** e roda `upgrade.php` + `purge_caches`.

Abra <https://localhost:8543> (certificado autoassinado) e entre com as **senhas da produção**.

Atalhos para os próximos passos (rodar a partir de `$WT`):

```bash
st()    { docker compose -f .devcontainer/staging/compose.yml "$@"; }
mphp()  { st exec -T -u www-data moodle php "$@"; }                                   # php como www-data
msql()  { st exec -T db mariadb -umoodle -pstaging moodle -e "$@"; }                  # SQL no banco staging
```

### 3.1 Linha de base (antes da atualização)

Guarde a saída. Ela serve para comparar no passo 5.

```bash
mkdir -p ~/ivana-staging/check
{
  msql "SELECT name, value FROM mdl_config WHERE name IN ('version','release')"
  mphp admin/cli/checks.php            # status
  mphp admin/cli/checks.php -t security
  mphp admin/cli/check_database_schema.php
} > ~/ivana-staging/check/antes.txt 2>&1; cat ~/ivana-staging/check/antes.txt
```

Avisos que **já existem antes** da atualização não são culpa dela. Anote-os.

## 4. Aplicar o upstream

```bash
.devcontainer/tools/moodle-upstream-update.sh v$V
# sem argumento: aplica até a ponta do upstream/MOODLE_405_STABLE (inclui builds semanais "+")
```

Saída esperada:

```
Base:  <sha> Moodle release 4.5.16  (4.5.16 (Build: ...))
Alvo:  <sha> Moodle release 4.5.17
Arquivos: NN
$version  = 2024100717.00; ...
$release  = '4.5.17 (Build: ...)';
Upgrades de banco no diff:
  ...
```

O script **para** sem alterar nada se o working tree estiver sujo ou se não achar a versão
atual no upstream.

### 4.1 Se aparecer "ATENÇÃO: arquivos customizados localmente…"

Esses arquivos foram alterados por nós **e** pelo upstream. Hoje são apenas `composer.json`,
`composer.lock`, `package.json`, `npm-shrinkwrap.json`, `.gitignore` e
`login/forgot_password_form.php`.

- O script tenta o merge de 3 vias (`git apply -3`). Se houver conflito, os marcadores
  `<<<<<<<` ficam no arquivo: resolva à mão, mantendo a correção do upstream **e** a nossa
  customização.
- Confira: `git diff --cached -- <arquivo>` e `git diff --cached v$V -- <arquivo>` (o segundo
  deve mostrar só a nossa customização).

### 4.2 Revisar

```bash
git diff --cached --stat
git diff --cached -- '*/db/upgrade.php' '*/db/install.xml' '*/db/access.php'   # mudanças de banco/permissões
git diff --cached --name-only | grep '\.php$' | xargs -n1 php -l | grep -v '^No syntax'   # lint (vazio = ok)
```

Leia também os `UPGRADING.md` alterados (`git diff --cached -- '*UPGRADING.md'`): eles avisam
sobre mudanças de API que podem afetar nossos plugins (`theme_ivana`, `local_seo`, …).

## 5. Rodar o upgrade na staging e validar

O código do worktree já está montado no container, então a versão nova já está "no ar" na
staging. Agora rode o upgrade do banco, **igual será feito em produção**:

```bash
mphp admin/cli/upgrade.php --non-interactive
msql "UPDATE mdl_task_scheduled SET disabled = 1"     # o upgrade religa as tarefas padrão
mphp admin/cli/purge_caches.php
```

### 5.1 Checklist de saúde (CLI)

| Verificação | Comando | Esperado |
|---|---|---|
| Versão no banco | `msql "SELECT name,value FROM mdl_config WHERE name IN ('version','release')"` | `$version`/`$release` novos |
| Upgrade pendente | `mphp admin/cli/upgrade.php --is-pending; echo $?` | `0` (2 = ainda falta upgrade) |
| Plugins sem código | `mphp admin/cli/uninstall_plugins.php --show-missing` | lista vazia |
| Checks de status | `mphp admin/cli/checks.php` | igual ou melhor que `antes.txt` |
| Checks de segurança | `mphp admin/cli/checks.php -t security` | igual ou melhor que `antes.txt` |
| Esquema do banco | `mphp admin/cli/check_database_schema.php` | `Database structure is ok.` (ou só os desvios que já existiam) |
| HTTP | `for p in / /login/index.php /course/index.php; do curl -sk -o /dev/null -w "$p %{http_code}\n" https://localhost:8543$p; done` | `200` ou `303`, nunca `500` |
| Erros PHP | `st logs moodle --since 15m 2>&1 \| grep -iE 'PHP (Fatal\|Parse\|Warning)\|Exception' \| head` | vazio |
| Banco | `st exec -T db healthcheck.sh --connect --innodb_initialized && echo ok` | `ok` |

Para comparar com a linha de base:

```bash
{
  msql "SELECT name, value FROM mdl_config WHERE name IN ('version','release')"
  mphp admin/cli/checks.php
  mphp admin/cli/checks.php -t security
  mphp admin/cli/check_database_schema.php
} > ~/ivana-staging/check/depois.txt 2>&1
diff ~/ivana-staging/check/antes.txt ~/ivana-staging/check/depois.txt
```

### 5.2 Teste manual no navegador (<https://localhost:8543>)

Entre como admin e depois como um aluno real (mesma senha da produção):

- [ ] *Administração do site › Notificações*: sem upgrade pendente nem plugin "Faltando no disco"
- [ ] *Administração do site › Relatórios › Verificações de segurança/sistema*: sem novos vermelhos
- [ ] Página inicial, menu e rodapé do `theme_ivana` (desktop e celular), tela de login
- [ ] Catálogo e uma página de curso (links para a landing, `local_seo`)
- [ ] Dentro de um curso: formato Tiles, abrir SCORM, vídeo, H5P
- [ ] Questionário: abrir e enviar uma tentativa; *Editar questionário* (área com correções frequentes)
- [ ] Fórum: criar e responder discussão
- [ ] Notas: relatório do avaliador e visão do aluno
- [ ] Página de inscrição/pagamento abre (o gateway fica desligado na staging, então não paga)
- [ ] Teste também as áreas citadas no changelog da versão

Se algo quebrar: corrija no worktree (o código aparece sem restart) ou anote no PR.

## 6. Commit e PR

```bash
cd "$WT"
git commit -m "fix(security): Moodle $V (upstream v$V)

- core: aplica <base>..v$V; \$version <novo>
- <resumo das correções: MDL-xxxxx ...>
- upgrade de banco: <core + plugins do diff>"
git push -u origin chore/moodle-$V
gh pr create --base main --title "fix(security): Moodle $V" --body "..."
```

No corpo do PR, inclua: a lista de MDLs/segurança, os upgrades de banco, o resultado do passo 5
(cole o `diff antes/depois`) e o checklist manual. Espere o CI (lint AMD, build da imagem e gate
de segurança) ficar verde.

## 7. Preparar a produção (antes do merge)

Faça no fim do dia ou numa janela com pouco acesso. Todos os comandos são **na VPS**:

```bash
ssh ivana-moodle-aws
cd /home/admin/devcontainer/production && source common.sh

# 7.1 Estado atual: anote a versão e o commit (servem para o rollback)
dc ps
git -C $CODE_DIR rev-parse HEAD
moosh admin/cli/cfg.php --name=release

# 7.2 Backup do banco AGORA (não confie só no noturno)
#     Use o /backups/backup.sh ou um mariadb-dump e confira o arquivo novo:
ls -lht /backups | head -3
df -h /                                   # o disco já está apertado: precisa de folga para o dump

# 7.3 Modo manutenção (o healthcheck aceita 503, então o deploy não falha)
moosh admin/cli/maintenance.php --enable
```

> O backup é obrigatório: depois do `upgrade.php`, o banco fica na versão nova. **O Moodle não
> roda código mais antigo que o banco**, então voltar só o código não funciona. O rollback
> exige restaurar o dump (passo 9).

## 8. Merge, deploy e upgrade

1. Faça o merge do PR na `main`. O workflow **Deploy PRODUCTION** (`.github/workflows/production.yml`)
   faz o build, roda o gate e executa o `deploy.sh` na VPS (checkout do commit, restart e purge).
   Acompanhe com `gh run watch -R leonardodg/ivana-academy`.
   Se o run ficar "queued" para sempre (instabilidade do GitHub Actions), cancele e rode
   `gh workflow run production.yml --ref main -R leonardodg/ivana-academy`.
2. **O deploy não roda o upgrade.** Na VPS:
   ```bash
   cd /home/admin/devcontainer/production && source common.sh
   git -C $CODE_DIR log -1 --oneline                 # é o commit do merge?
   moosh admin/cli/upgrade.php --non-interactive
   moosh admin/cli/purge_caches.php
   moosh admin/cli/maintenance.php --disable
   ```

### 8.1 Validar a produção

```bash
moosh admin/cli/cfg.php --name=release                 # 4.5.17 (Build ...)
moosh admin/cli/upgrade.php --is-pending; echo $?      # 0
moosh admin/cli/uninstall_plugins.php --show-missing   # vazio
moosh admin/cli/checks.php
moosh admin/cli/checks.php -t security
dc ps                                                  # moodle "healthy"
dc logs --since 15m moodle 2>&1 | grep -iE 'PHP (Fatal|Parse)|Exception' | head
docker exec moodle crontab -u www-data -l | grep cron.php    # cron ativo
docker inspect -f '{{.State.OOMKilled}}' moodle              # false
curl -s -o /dev/null -w '%{http_code}\n' https://ivana.academy/login/index.php   # 200
```

No navegador (<https://ivana.academy>): faça o mesmo checklist do passo 5.2 em versão curta
(home, login, um curso, um questionário, notas) e confira em *Notificações* que nada está
pendente. Volte em ~1 h e veja *Relatórios › Logs* e *Tarefas agendadas* (as tarefas devem estar
rodando, sem falhas repetidas).

## 9. Rollback (se der errado)

```bash
cd /home/admin/devcontainer/production && source common.sh
moosh admin/cli/maintenance.php --enable
# 1. código: volta ao commit anotado no 7.1
git -C $CODE_DIR checkout --detach <sha-anterior>
# 2. banco: restaura o dump do 7.2 no MariaDB de produção (outro host), com as
#    credenciais de secrets/db_user e secrets/db_password
# 3. caches e saída da manutenção
dc restart moodle
moosh admin/cli/purge_caches.php
moosh admin/cli/maintenance.php --disable
```

Depois reverta o merge na `main` (`git revert -m 1 <merge>` ou PR de revert), senão o próximo
deploy sobe a versão nova de novo.

## 10. Limpeza (dados de clientes)

```bash
cd "$WT/.devcontainer/staging"
docker compose down -v                  # apaga o banco staging
rm -rf data                             # moodledata de produção
rm -rf ~/ivana-staging                  # dumps e tar de produção
cd ~/ivana-academy
git worktree remove "$WT"               # depois do merge; a branch remota some ao fechar o PR
git branch -d chore/moodle-$V
```

---

## Resumo rápido

```bash
V=4.5.17; WT=~/ivana-academy-$V
git worktree add -b chore/moodle-$V "$WT" main && cp .devcontainer/certs/develop-local.{crt,key} "$WT/.devcontainer/certs/" && cd "$WT"
STAGING_RESET=1 .devcontainer/staging/restore.sh ~/ivana-staging/<dump>.sql.gz ~/ivana-staging/<mdata>.tar.gz
# linha de base (3.1)
.devcontainer/tools/moodle-upstream-update.sh v$V
# upgrade na staging + checklist (5) → commit → PR → CI verde
# VPS: backup + manutenção → merge → upgrade.php + purge + sai da manutenção → validação (8.1)
# limpeza (10)
```
