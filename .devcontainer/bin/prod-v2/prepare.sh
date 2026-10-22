#!/bin/bash
# =============================================================================
# prepare.sh - prepara o v2 na VPS SEM tirar o site do ar (idempotente)
# =============================================================================
#   1. confere espaco em disco
#   2. git checkout do codigo em /home/admin/moodle-v2/code (ref informado)
#   3. copia o config.php do v1 (mesmo config que esta em producao)
#   4. gera env/prod.env + secrets + certs a partir do container v1
#   5. docker pull da imagem e smoke test do PHP (sem conectar no banco)
#
# Uso: prepare.sh [GIT_REF] [IMAGE_TAG]
#      prepare.sh release/production-v2 4.5.15-php8.3
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

GIT_REF="${1:-release/production-v2}"
IMAGE_TAG="${2:-4.5.15-php8.3}"
IMAGE="leodg/moodle-academy:${IMAGE_TAG}"

docker inspect "$V1_CONTAINER" >/dev/null 2>&1 || die "Container v1 '$V1_CONTAINER' nao encontrado"

# 1. Disco -------------------------------------------------------------------
free_gb=$(df --output=avail -BG / | tail -1 | tr -dc '0-9')
log "💾 Livre em /: ${free_gb}G"
[ "$free_gb" -ge 3 ] || die "Menos de 3G livres. Limpe imagens antigas: docker image prune (remove so as <none>)"

# 2. Codigo ------------------------------------------------------------------
mkdir -p "$V2_BASE_DIR"
if [ ! -d "$V2_CODE_DIR/.git" ]; then
    log "📥 Clonando $REPO_URL em $V2_CODE_DIR"
    git clone --quiet "$REPO_URL" "$V2_CODE_DIR"
fi
git -C "$V2_CODE_DIR" fetch --quiet origin
git -C "$V2_CODE_DIR" checkout --quiet --detach "origin/${GIT_REF}" 2>/dev/null \
    || git -C "$V2_CODE_DIR" checkout --quiet --detach "$GIT_REF"
log "✅ Codigo v2: $(git -C "$V2_CODE_DIR" log -1 --format='%h %s')"
grep -E '^\$release' "$V2_CODE_DIR/version.php"

# 3. config.php --------------------------------------------------------------
if [ ! -f "$V2_CODE_DIR/config.php" ] || [ "${FORCE_CONFIG:-0}" = 1 ]; then
    cp -p "$V1_CODE_DIR/config.php" "$V2_CODE_DIR/config.php"
    log "✅ config.php copiado do v1"
else
    log "ℹ config.php ja existe no v2 (FORCE_CONFIG=1 para sobrescrever)"
fi

# 4. env / secrets / certs (valores do container v1) ------------------------
mkdir -p "$COMPOSE_DIR/env" "$COMPOSE_DIR/secrets" "$COMPOSE_DIR/certs"
umask 077
cat > "$COMPOSE_DIR/env/prod.env" <<EOF
IMAGE_TAG=${IMAGE_TAG}
MOODLE_CODE_DIR=${V2_CODE_DIR}
MOODLE_URL=$(v1_env MOODLE_URL)
MOODLE_DBTYPE=$(v1_env MOODLE_DBTYPE)
MOODLE_DBHOST=$(v1_env MOODLE_DBHOST)
MOODLE_DBNAME=$(v1_env MOODLE_DBNAME)
MOODLE_DATA=$(v1_env MOODLE_DATA)
PROD_HTTP_PORT=80
PROD_HTTPS_PORT=443
CRON_ENABLED=true
EOF
# DB user/pass como env do container (igual ao v1): o config.php usa getenv()
# e `docker exec php admin/cli/...` so enxerga o env do container.
printf 'MOODLE_DBUSER=%s\nMOODLE_DBPASS=%s\n' "$(v1_env MOODLE_DBUSER)" "$(v1_env MOODLE_DBPASS)" \
    > "$COMPOSE_DIR/secrets/db.env"
# fullchain (cert + cadeia), nao cert.pem
cp -L "$LE_LIVE/fullchain.pem" "$COMPOSE_DIR/certs/server.crt"
cp -L "$LE_LIVE/privkey.pem"   "$COMPOSE_DIR/certs/server.key"
chmod 600 "$COMPOSE_DIR/secrets/db.env" "$COMPOSE_DIR/certs/server.key"
chmod 644 "$COMPOSE_DIR/certs/server.crt"
umask 022
log "✅ env/prod.env, secrets e certs gerados em $COMPOSE_DIR"
openssl x509 -noout -enddate -in "$COMPOSE_DIR/certs/server.crt" | sed 's/^/   cert origem: /'

# 5. Imagem + smoke test -----------------------------------------------------
log "🐳 docker pull $IMAGE"
docker pull --quiet "$IMAGE"
docker run --rm --entrypoint sh -v "$V2_CODE_DIR:/var/www/html:ro" "$IMAGE" -c '
    php -r "echo \"   PHP \", PHP_VERSION, \" | upload_progress=\", var_export(ini_get(\"session.upload_progress.enabled\"), true), PHP_EOL;"
    php -l /var/www/html/config.php
    php -r "define(\"MOODLE_INTERNAL\",1); require \"/var/www/html/version.php\"; echo \"   Moodle \", \$release, PHP_EOL;"'

log "🎯 Pronto. Proximo passo (janela de manutencao): switch-to-v2.sh"
