#!/bin/bash
# shellcheck disable=SC2034
# Variaveis e funcoes dos scripts de producao (source, nao executar).

CONTAINER="moodle"                      # container_name em compose.yml
SERVICE="moodle"
BASE_DIR="/home/admin/moodle"
CODE_DIR="${BASE_DIR}/code"             # git checkout (detached) servido pelo Apache
DEVCONTAINER_DIR="/home/admin/devcontainer"
PROD_DIR="${DEVCONTAINER_DIR}/production"   # este diretorio (compose.yml + scripts)
ENV_FILE="${DEVCONTAINER_DIR}/env/prod.env"
SECRETS_DIR="${DEVCONTAINER_DIR}/secrets"
LEGACY_DB_ENV="${SECRETS_DIR}/db.env"     # formato antigo (env); so p/ migracao
BACKUP_DIR="/backups"
REPO_URL="https://github.com/leonardodg/ivana-academy.git"
PUBLIC_URL="https://ivana.academy/login/index.php"

log()  { echo "[$(date '+%H:%M:%S')] $*"; }
die()  { echo "❌ $*" >&2; exit 1; }

# docker compose sempre com o mesmo projeto/arquivo/env, de qualquer diretorio.
dc() {
    docker compose --project-directory "$PROD_DIR" -f "$PROD_DIR/compose.yml" \
        --env-file "$ENV_FILE" "$@"
}

require_compose() {
    docker compose version >/dev/null 2>&1 \
        || die "docker compose ausente: rode $PROD_DIR/install-compose.sh (uma vez)"
}

require_files() {
    local f
    for f in "$ENV_FILE" "$SECRETS_DIR/db_user" "$SECRETS_DIR/db_password" \
             "$DEVCONTAINER_DIR/certs/server.crt" \
             "$DEVCONTAINER_DIR/certs/server.key"; do
        [ -f "$f" ] || die "Arquivo ausente: $f"
    done
}

# Altera/cria KEY=VALUE em env/prod.env
set_env() {
    if grep -q "^$1=" "$ENV_FILE"; then sed -i "s|^$1=.*|$1=$2|" "$ENV_FILE"
    else echo "$1=$2" >> "$ENV_FILE"; fi
}

# secrets/db_user + db_password (0600) a partir do db.env antigo, se faltarem.
ensure_db_secrets() {
    [ -s "$SECRETS_DIR/db_user" ] && [ -s "$SECRETS_DIR/db_password" ] && return 0
    [ -f "$LEGACY_DB_ENV" ] || die "Faltam $SECRETS_DIR/db_user|db_password (e nao ha db.env para gerar)"
    local u p
    u=$(sed -n 's/^MOODLE_DBUSER=//p' "$LEGACY_DB_ENV" | head -1)
    p=$(sed -n 's/^MOODLE_DBPASS=//p' "$LEGACY_DB_ENV" | head -1)
    [ -n "$u" ] && [ -n "$p" ] || die "MOODLE_DBUSER/MOODLE_DBPASS vazios em $LEGACY_DB_ENV"
    ( umask 077; printf '%s' "$u" > "$SECRETS_DIR/db_user"; printf '%s' "$p" > "$SECRETS_DIR/db_password" )
    log "🔑 secrets/db_user e db_password gerados a partir de db.env"
}

# config.php da VPS: credenciais do arquivo publicado pelo entrypoint, com
# fallback para getenv() (continua valendo no moodle-v2 antigo / rollback).
patch_config_secrets() {   # $1 = config.php
    local cfg=$1
    grep -q "/run/moodle-secrets/db_password" "$cfg" && return 0
    cp -p "$cfg" "$cfg.bak-$(date +%Y%m%d-%H%M%S)"
    sed -i \
      -e "s|getenv('MOODLE_DBUSER')|((\$ivsec = @file_get_contents('/run/moodle-secrets/db_user')) !== false ? trim(\$ivsec) : getenv('MOODLE_DBUSER'))|" \
      -e "s|getenv('MOODLE_DBPASS')|((\$ivsec = @file_get_contents('/run/moodle-secrets/db_password')) !== false ? trim(\$ivsec) : getenv('MOODLE_DBPASS'))|" \
      "$cfg"
    [ "$(grep -c '/run/moodle-secrets/db_' "$cfg")" = 2 ] \
        || die "config.php nao tem getenv('MOODLE_DBUSER'/'MOODLE_DBPASS') no formato esperado - ajustar a mao"
    log "🔑 config.php le as credenciais de /run/moodle-secrets (fallback getenv)"
}

container_running() {
    [ "$(docker inspect -f '{{.State.Running}}' "$1" 2>/dev/null)" = true ]
}

# Container criado por este compose (label do projeto), nao por docker run.
managed_by_compose() {
    [ "$(docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' "$CONTAINER" 2>/dev/null)" = ivana-academy ]
}

wait_http_ok() {   # espera o Apache responder (2xx/3xx) ate ~3 min
    local code
    for _ in $(seq 1 36); do
        code=$(docker exec "$CONTAINER" curl -sk -o /dev/null -w '%{http_code}' https://localhost/login/index.php 2>/dev/null || true)
        [[ "$code" =~ ^[23] ]] && { log "✅ $CONTAINER respondendo (HTTP $code)"; return 0; }
        sleep 5
    done
    return 1
}

moosh() {          # php admin/cli/* como www-data
    docker exec -u www-data "$CONTAINER" php "$@"
}

# Credenciais do dump: as mesmas do /backups/backup.sh (sem executar o script).
load_backup_db_vars() {
    eval "$(grep -E '^(DB_HOST|DB_USER|DB_PASS)=' "${BACKUP_DIR}/backup.sh")"
    [ -n "${DB_HOST:-}" ] && [ -n "${DB_USER:-}" ] && [ -n "${DB_PASS:-}" ] \
        || die "Nao consegui ler DB_HOST/DB_USER/DB_PASS de ${BACKUP_DIR}/backup.sh"
}

db_dump() {        # $1 = arquivo .sql.gz
    load_backup_db_vars
    mysqldump -h "$DB_HOST" -P 3306 -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction --quick --routines --triggers moodle | gzip > "$1"
    zcat "$1" | tail -1 | grep -q "Dump completed" || die "Dump incompleto: $1"
    log "✅ Dump ok: $1 ($(du -h "$1" | cut -f1))"
}
