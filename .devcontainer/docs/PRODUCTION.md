# Produção — EC2 `ivana-moodle-aws`

Só existe **uma** versão em produção: o container `moodle`, definido em
`.devcontainer/production/compose.yml` e gerenciado por `docker compose` (projeto `ivana-academy`).

| | Onde (VPS) |
|---|---|
| Código (git checkout detached de `main`) | `/home/admin/moodle/code` — `config.php` só existe aqui, fora do git |
| Scripts + `compose.yml` | `/home/admin/devcontainer/production/` (o CI sincroniza a partir de `.devcontainer/production/`) |
| Env (sem senha) | `/home/admin/devcontainer/env/prod.env` |
| Secrets do compose (0600, fora do git) | `secrets/db_user`, `secrets/db_password`, `certs/server.{crt,key}`, montados em `/run/secrets/*` (**nunca** como env, nem em `docker inspect`). O entrypoint publica cópias `root:www-data 0440` em `/run/moodle-secrets/`, que o `config.php` lê (com fallback para `getenv`) |
| moodledata | `/home/admin/moodledata` → `/moodledata` |
| Banco | MariaDB 11.4 em outro host; o dump noturno é feito por `/backups/backup.sh` |
| Certificado | Cloudflare Origin CA (até 2041), SSL Full (strict) |

## Deploy (automático)

Merge em `main` → `.github/workflows/production.yml`: build da imagem → gate de segurança →
`deploy.sh` na VPS:

1. `docker pull` + smoke test (`php -l config.php` com a imagem nova)
2. checkout do commit novo (aborta se houver hotfix local no checkout)
3. `docker compose up -d --wait`: **recria o container só se a imagem ou o compose mudaram**
   (ex.: PR do Dependabot com novo digest do PHP). Se só mudou `.php`, faz restart, porque o
   opcache não revalida
4. `purge_caches` + teste HTTP

`version.php` alterado (core ou plugin): o código sobe, mas o **upgrade é manual** em
Administração do site › Notificações.

## Operação

```bash
cd /home/admin/devcontainer/production && source common.sh
dc ps                     # estado / health
dc logs -f --tail 100     # logs (json-file, 3 × 10 MB)
dc restart moodle
moosh admin/cli/maintenance.php --enable     # php admin/cli/* como www-data
docker stats --no-stream moodle
```

**Rollback de imagem:** cada build também publica `4.5.15-php8.3-<sha>`. Para voltar,
`set_env IMAGE_TAG 4.5.15-php8.3-<sha>` e depois `dc up -d --wait`. **Rollback de código:**
`git -C /home/admin/moodle/code checkout --detach <sha>` e depois `dc restart moodle`. Banco:
dumps em `/backups` (e no Google Drive).

## Histórico: migração docker run → compose (11/11/2026)

Até 11/2026 a produção era o container `moodle-v2`, criado por `docker run`, e o v1 (`moodle`,
PHP 8.1) ficava parado. A migração instalou o plugin `docker compose` v5.6.1 em
`~admin/.docker/cli-plugins` (binário oficial, sha256 conferido). Também gerou
`secrets/db_user|db_password`, ajustou o `config.php` (credenciais lidas de arquivo, `02770`),
removeu o v1 e subiu o `moodle` pelo compose. Fez ainda o upgrade 4.5.15 → 4.5.15+ e o dump
`/backups/pre-compose-20261111-112643.sql.gz`. Os scripts de uso único (`install-compose.sh`,
`cutover.sh`) foram removidos depois. Estão no histórico do git (PR #117).

**Rollback para o `moodle-v2`**, só enquanto o `retire-legacy.sh` não rodar:
```bash
cd /home/admin/devcontainer/production && source common.sh
dc down
# o moodle-v2 roda o cron como root: com 02770 ele cria pastas que o Apache nao grava
sed -i 's/^\$CFG->directorypermissions = 02770;/$CFG->directorypermissions = 0777;/' $CODE_DIR/config.php
docker update --restart=unless-stopped moodle-v2 && docker start moodle-v2
```
O `config.php` continua lendo as credenciais via `getenv` (o `moodle-v2` usa `--env-file db.env`).

**Depois de 1–2 dias estável:** `production/retire-legacy.sh`. Pede confirmação item a item e
remove o container `moodle-v2`, as imagens órfãs, `/home/admin/public_html` (código do v1),
`/home/admin/local/letsencrypt`, o `secrets/db.env` e os scripts antigos
`devcontainer/bin/prod-v2`.

## Healthcheck

O `compose.yml` considera saudável 2xx/3xx **e 503**. 503 é o modo manutenção do Moodle
(upgrade, janela), e nele Apache e PHP estão funcionando. Sem isso, `up --wait` falha durante
uma manutenção.
