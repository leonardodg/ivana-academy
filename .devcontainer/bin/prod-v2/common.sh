#!/bin/bash
# shellcheck disable=SC2034
# Variaveis e funcoes compartilhadas pelos scripts prod-v2 (source, nao executar).

V1_CONTAINER="moodle"
V2_CONTAINER="moodle-v2"
V1_CODE_DIR="/home/admin/public_html"
V2_BASE_DIR="/home/admin/moodle-v2"
V2_CODE_DIR="${V2_BASE_DIR}/code"
COMPOSE_DIR="/home/admin/devcontainer"
STATE_FILE="${V2_BASE_DIR}/last-switch.env"
BACKUP_DIR="/backups"
LE_LIVE="/home/admin/local/letsencrypt/live/ivana.academy"
REPO_URL="https://github.com/leonardodg/ivana-academy.git"
PUBLIC_URL="https://ivana.academy/login/index.php"

ENV_FILE="${COMPOSE_DIR}/env/prod.env"

log()  { echo "[$(date '+%H:%M:%S')] $*"; }
die()  { echo "❌ $*" >&2; exit 1; }

# Le uma variavel de ambiente do container v1 (fonte da verdade da producao).
v1_env() {
    docker inspect "$V1_CONTAINER" --format '{{range .Config.Env}}{{println .}}{{end}}' \
        | sed -n "s/^$1=//p" | head -1
}

# Credenciais do dump: as mesmas do /backups/backup.sh (sem executar o script).
load_backup_db_vars() {
    eval "$(grep -E '^(DB_HOST|DB_USER|DB_PASS)=' "${BACKUP_DIR}/backup.sh")"
    [ -n "${DB_HOST:-}" ] && [ -n "${DB_USER:-}" ] && [ -n "${DB_PASS:-}" ] \
        || die "Nao consegui ler DB_HOST/DB_USER/DB_PASS de ${BACKUP_DIR}/backup.sh"
}

wait_http_ok() {   # $1 = container; espera Apache responder (<500) ate ~3 min
    for _ in $(seq 1 36); do
        code=$(docker exec "$1" curl -sk -o /dev/null -w '%{http_code}' https://localhost/login/index.php 2>/dev/null || true)
        [[ "$code" =~ ^[23] ]] && { log "✅ $1 respondendo (HTTP $code)"; return 0; }
        sleep 5
    done
    return 1
}

# A VPS nao tem `docker compose` (Docker 20.10 sem plugin): docker run com as
# mesmas opcoes de compose/prod-v2.yml. Manter os dois em sincronia.
v2_up() {
    # shellcheck disable=SC1090
    source "$ENV_FILE"
    docker run -d --name "$V2_CONTAINER" --restart unless-stopped \
        -p "${PROD_HTTP_PORT:-80}:80" -p "${PROD_HTTPS_PORT:-443}:443" \
        -e ENVIRONMENT=production \
        -e MOODLE_URL="$MOODLE_URL" \
        -e MOODLE_DBTYPE="${MOODLE_DBTYPE:-mariadb}" \
        -e MOODLE_DBHOST="$MOODLE_DBHOST" \
        -e MOODLE_DBNAME="$MOODLE_DBNAME" \
        -e MOODLE_DBPFX=mdl_ -e MOODLE_DBCOLL=utf8mb4_bin \
        -e MOODLE_DATA="${MOODLE_DATA:-/var/www/moodledata}" \
        -e CRON_ENABLED="${CRON_ENABLED:-true}" \
        -e PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
        --env-file "${COMPOSE_DIR}/secrets/db.env" \
        -v "${COMPOSE_DIR}/certs/develop-local.crt:/run/secrets/ssl_cert:ro" \
        -v "${COMPOSE_DIR}/certs/develop-local.key:/run/secrets/ssl_key:ro" \
        -v "${MOODLE_CODE_DIR}:/var/www/html" \
        -v /home/admin/moodledata:/var/www/moodledata \
        "leodg/moodle-academy:${IMAGE_TAG}" >/dev/null
}

v2_down() {
    docker rm -f "$V2_CONTAINER" >/dev/null 2>&1 || true
}
