#!/bin/bash
# =============================================================================
# deploy.sh - deploy de producao (CI: production-v2.yml, job deploy-vps)
# =============================================================================
#   1. confere disco, compose e arquivos (env/secrets/certs)
#   2. docker pull da imagem + smoke test do PHP com o codigo novo
#   3. git checkout do commit novo em $CODE_DIR (aborta se houver hotfix local)
#   4. docker compose up -d --wait  -> recria o container SO se imagem/compose
#      mudaram (ex.: patch de seguranca do PHP); senao, restart se .php mudou
#      (opcache sem revalidacao)
#   5. purge_caches + teste HTTP publico
#
# version.php alterado: o codigo vai para o servidor, mas o upgrade.php NAO
# roda aqui - o admin conclui em Administracao do site > Notificacoes.
#
# Uso: deploy.sh [GIT_REF] [IMAGE_TAG]
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

GIT_REF="${1:-main}"
IMAGE_TAG="${2:-4.5.15-php8.3}"
IMAGE="leodg/moodle-academy:${IMAGE_TAG}"

# 1. Pre-requisitos -------------------------------------------------------------
free_gb=$(df --output=avail -BG / | tail -1 | tr -dc '0-9')
log "💾 Livre em /: ${free_gb}G"
[ "$free_gb" -ge 3 ] || die "Menos de 3G livres. Limpe imagens antigas: docker image prune"

managed_by_compose || die "$CONTAINER nao e gerenciado pelo compose (projeto ivana-academy) - estado inesperado"
require_compose
require_files
set_env IMAGE_TAG "$IMAGE_TAG"

# 2. Imagem ---------------------------------------------------------------------
log "🐳 docker pull $IMAGE"
docker pull --quiet "$IMAGE"

# 3. Codigo ---------------------------------------------------------------------
git -C "$CODE_DIR" fetch --quiet origin
OLD=$(git -C "$CODE_DIR" rev-parse HEAD)
NEW=$(git -C "$CODE_DIR" rev-parse "origin/${GIT_REF}")
CHANGED=""
if [ "$OLD" != "$NEW" ]; then
    DIRTY=$(git -C "$CODE_DIR" status --porcelain --untracked-files=no)
    if [ -n "$DIRTY" ]; then
        echo "$DIRTY" | sed 's/^/   /'
        die "Checkout com alteracoes locais (hotfix manual?). Confira e descarte: git -C $CODE_DIR checkout -- <arquivos>"
    fi
    CHANGED=$(git -C "$CODE_DIR" diff --name-only "$OLD" "$NEW")
    log "📦 ${OLD:0:11} -> ${NEW:0:11}: $(echo "$CHANGED" | grep -c .) arquivo(s)"
    git -C "$CODE_DIR" checkout --quiet --detach "$NEW"
fi
log "✅ Codigo: $(git -C "$CODE_DIR" log -1 --format='%h %s')"
grep -E '^\$release' "$CODE_DIR/version.php"

docker run --rm --entrypoint php -v "$CODE_DIR:/var/www/html:ro" "$IMAGE" \
    -l /var/www/html/config.php >/dev/null || die "config.php invalido com a imagem nova"

# 4. Container --------------------------------------------------------------------
BEFORE=$(docker inspect -f '{{.Id}}' "$CONTAINER")
dc up -d --wait --wait-timeout 300 || die "Container nao ficou saudavel: docker logs $CONTAINER"
AFTER=$(docker inspect -f '{{.Id}}' "$CONTAINER")
if [ "$BEFORE" != "$AFTER" ]; then
    log "🔁 Container recriado (imagem/compose mudou): $(docker exec "$CONTAINER" php -r 'echo PHP_VERSION;')"
elif echo "$CHANGED" | grep -vE '^(\.devcontainer|\.github)/' | grep -qE '\.php$'; then
    log "🔄 PHP alterado: reiniciando $CONTAINER (opcache sem revalidacao)"
    dc restart "$SERVICE"
    wait_http_ok || die "$CONTAINER nao respondeu apos restart: docker logs $CONTAINER"
fi

# 5. Caches + teste -----------------------------------------------------------------
moosh admin/cli/purge_caches.php
log "🧹 Caches limpos"
curl -s -o /dev/null -w "   publico: HTTP %{http_code}\n" "$PUBLIC_URL"

VERSION_CHANGED=$(echo "$CHANGED" | grep -E '(^|/)version\.php$' || true)
if [ -n "$VERSION_CHANGED" ]; then
    echo "$VERSION_CHANGED" | sed 's/^/   /'
    log "⚠  Instalacao/atualizacao pendente: concluir em Administracao do site > Notificacoes"
    echo "::warning::version.php alterado - concluir em Administracao do site > Notificacoes"
fi
docker image prune -f >/dev/null 2>&1 || true
