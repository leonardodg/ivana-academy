#!/bin/bash
# =============================================================================
# prepare.sh - prepara o v2 na VPS SEM tirar o site do ar (idempotente)
# =============================================================================
#   1. confere espaco em disco
#   2. git checkout do codigo em /home/admin/moodle-v2/code (ref informado)
#      -> PULADO se o moodle-v2 ja estiver rodando (codigo em uso pelo site)
#   3. copia o config.php do v1 (mesmo config que esta em producao)
#   4. gera env/prod.env + secrets a partir do container v1
#      -> se o v1 ja foi removido, mantem os arquivos existentes
#   5. docker pull da imagem e smoke test do PHP (sem conectar no banco)
#
# Nunca troca containers nem roda upgrade.php: isso e manual (guia, etapa C).
# Roda no CI (production-v2.yml, job prepare-vps) ou a mao.
#
# Uso: prepare.sh [GIT_REF] [IMAGE_TAG]
#      prepare.sh main 4.5.15-php8.3
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

GIT_REF="${1:-main}"
IMAGE_TAG="${2:-4.5.15-php8.3}"
IMAGE="leodg/moodle-academy:${IMAGE_TAG}"

V1_EXISTS=0
docker inspect "$V1_CONTAINER" >/dev/null 2>&1 && V1_EXISTS=1
V2_RUNNING=0
[ "$(docker inspect -f '{{.State.Running}}' "$V2_CONTAINER" 2>/dev/null)" = true ] && V2_RUNNING=1

# 1. Disco -------------------------------------------------------------------
free_gb=$(df --output=avail -BG / | tail -1 | tr -dc '0-9')
log "💾 Livre em /: ${free_gb}G"
[ "$free_gb" -ge 3 ] || die "Menos de 3G livres. Limpe imagens antigas: docker image prune (remove so as <none>)"

# 2. Codigo ------------------------------------------------------------------
mkdir -p "$V2_BASE_DIR"
if [ "$V2_RUNNING" = 1 ]; then
    # Trocar arquivos sob o site no ar (opcache sem revalidacao, sem upgrade.php)
    # deixaria o Moodle num estado misto. Atualizar o codigo do v2 em producao
    # e manual: checkout + restart + upgrade.php na janela.
    log "⏭  $V2_CONTAINER esta no ar: codigo NAO atualizado ($(git -C "$V2_CODE_DIR" log -1 --format='%h'))"
else
    if [ ! -d "$V2_CODE_DIR/.git" ]; then
        log "📥 Clonando $REPO_URL em $V2_CODE_DIR"
        git clone --quiet "$REPO_URL" "$V2_CODE_DIR"
    fi
    git -C "$V2_CODE_DIR" fetch --quiet origin
    git -C "$V2_CODE_DIR" checkout --quiet --detach "origin/${GIT_REF}" 2>/dev/null \
        || git -C "$V2_CODE_DIR" checkout --quiet --detach "$GIT_REF"
    log "✅ Codigo v2: $(git -C "$V2_CODE_DIR" log -1 --format='%h %s')"
fi
grep -E '^\$release' "$V2_CODE_DIR/version.php"

# 3. config.php --------------------------------------------------------------
if [ ! -f "$V2_CODE_DIR/config.php" ] || [ "${FORCE_CONFIG:-0}" = 1 ]; then
    [ -f "$V1_CODE_DIR/config.php" ] || die "config.php ausente no v2 e no v1"
    cp -p "$V1_CODE_DIR/config.php" "$V2_CODE_DIR/config.php"
    log "✅ config.php copiado do v1"
else
    log "ℹ config.php ja existe no v2 (FORCE_CONFIG=1 para sobrescrever)"
fi

# 4. env / secrets / certs (valores do container v1) ------------------------
mkdir -p "$COMPOSE_DIR/env" "$COMPOSE_DIR/secrets" "$COMPOSE_DIR/certs"
if [ "$V1_EXISTS" = 1 ]; then
    umask 077
    {
        echo "IMAGE_TAG=${IMAGE_TAG}"
        echo "MOODLE_CODE_DIR=${V2_CODE_DIR}"
        for v in MOODLE_URL MOODLE_DBTYPE MOODLE_DBHOST MOODLE_DBNAME MOODLE_DATA; do
            echo "$v=$(v1_env "$v")"
        done
        echo "PROD_HTTP_PORT=80"
        echo "PROD_HTTPS_PORT=443"
        echo "CRON_ENABLED=true"
    } > "$ENV_FILE"
    # DB user/pass como env do container (igual ao v1): o config.php usa getenv()
    # e `docker exec php admin/cli/...` so enxerga o env do container.
    printf 'MOODLE_DBUSER=%s\nMOODLE_DBPASS=%s\n' "$(v1_env MOODLE_DBUSER)" "$(v1_env MOODLE_DBPASS)" \
        > "$COMPOSE_DIR/secrets/db.env"
    umask 022
    log "✅ env/prod.env e secrets/db.env gerados a partir do v1"
else
    [ -f "$ENV_FILE" ] && [ -f "$COMPOSE_DIR/secrets/db.env" ] \
        || die "v1 removido e $ENV_FILE / secrets/db.env inexistentes"
    sed -i "s/^IMAGE_TAG=.*/IMAGE_TAG=${IMAGE_TAG}/" "$ENV_FILE"
    log "ℹ v1 removido: mantidos env/prod.env e secrets/db.env (IMAGE_TAG=${IMAGE_TAG})"
fi
# Certificado da origem: Cloudflare Origin CA se instalado (nao vence por 15
# anos, exigido pelo SSL "Full (strict)"); senao Let's Encrypt (fullchain).
if [ -f "$ORIGIN_CERT_DIR/ivana.academy.crt" ] && [ -f "$ORIGIN_CERT_DIR/ivana.academy.key" ]; then
    cp "$ORIGIN_CERT_DIR/ivana.academy.crt" "$COMPOSE_DIR/certs/server.crt"
    cp "$ORIGIN_CERT_DIR/ivana.academy.key" "$COMPOSE_DIR/certs/server.key"
    log "🔐 Certificado: Cloudflare Origin CA ($ORIGIN_CERT_DIR)"
else
    cp -L "$LE_LIVE/fullchain.pem" "$COMPOSE_DIR/certs/server.crt"
    cp -L "$LE_LIVE/privkey.pem"   "$COMPOSE_DIR/certs/server.key"
    log "🔐 Certificado: Let's Encrypt ($LE_LIVE)"
fi
chmod 600 "$COMPOSE_DIR/secrets/db.env" "$COMPOSE_DIR/certs/server.key"
chmod 644 "$COMPOSE_DIR/certs/server.crt"
openssl x509 -noout -enddate -in "$COMPOSE_DIR/certs/server.crt" | sed 's/^/   cert origem: /'

# 5. Imagem + smoke test -----------------------------------------------------
log "🐳 docker pull $IMAGE"
docker pull --quiet "$IMAGE"
docker run --rm --entrypoint sh -v "$V2_CODE_DIR:/var/www/html:ro" "$IMAGE" -c '
    php -r "echo \"   PHP \", PHP_VERSION, \" | upload_progress=\", var_export(ini_get(\"session.upload_progress.enabled\"), true), PHP_EOL;"
    php -l /var/www/html/config.php
    grep -E "^.release" /var/www/html/version.php'

log "🎯 Pronto. Troca de container e upgrade.php: manual (guia, etapa C)"
