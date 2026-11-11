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
LEGACY_DB_ENV="${SECRETS_DIR}/db.env"     # formato antigo (env) do moodle-v2; removido pelo retire-legacy.sh
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
        || die "docker compose ausente (plugin em ~/.docker/cli-plugins) - ver docs/PRODUCTION.md"
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

container_running() {
    [ "$(docker inspect -f '{{.State.Running}}' "$1" 2>/dev/null)" = true ]
}

# Container criado por este compose (label do projeto), nao por docker run.
managed_by_compose() {
    [ "$(docker inspect -f '{{index .Config.Labels "com.docker.compose.project"}}' "$CONTAINER" 2>/dev/null)" = ivana-academy ]
}

wait_http_ok() {   # espera o Apache responder (2xx/3xx; 503 = manutencao) ate ~3 min
    local code
    for _ in $(seq 1 36); do
        code=$(docker exec "$CONTAINER" curl -sk -o /dev/null -w '%{http_code}' https://localhost/login/index.php 2>/dev/null || true)
        [[ "$code" =~ ^([23]|503) ]] && { log "✅ $CONTAINER respondendo (HTTP $code)"; return 0; }
        sleep 5
    done
    return 1
}

moosh() {          # php admin/cli/* como www-data
    docker exec -u www-data "$CONTAINER" php "$@"
}
