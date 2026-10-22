#!/bin/bash
# =============================================================================
# deploy-code.sh - atualiza o codigo do moodle-v2 JA EM PRODUCAO (sem janela)
# =============================================================================
# So faz o que e seguro sem modo manutencao:
#   - moodle-v2 parado     -> nada (o prepare.sh ja fez o checkout)
#   - mesmo commit         -> nada
#   - version.php mudou    -> ABORTA: precisa upgrade.php (janela manual)
#   - checkout com mudanca local em arquivo versionado -> ABORTA (lista)
#   - senao: checkout do commit novo
#            .php alterado -> docker restart moodle-v2 (opcache sem revalidacao)
#            purge_caches (nova revisao de JS/CSS) + login HTTP 200
#
# Uso: deploy-code.sh [GIT_REF]      (CI: job prepare-vps, depois do prepare.sh)
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

GIT_REF="${1:-release/production-v2}"

if [ "$(docker inspect -f '{{.State.Running}}' "$V2_CONTAINER" 2>/dev/null)" != true ]; then
    log "ℹ $V2_CONTAINER nao esta no ar: nada a fazer (codigo ja preparado pelo prepare.sh)"
    exit 0
fi

git -C "$V2_CODE_DIR" fetch --quiet origin
OLD=$(git -C "$V2_CODE_DIR" rev-parse HEAD)
NEW=$(git -C "$V2_CODE_DIR" rev-parse "origin/${GIT_REF}")
if [ "$OLD" = "$NEW" ]; then
    log "✅ Codigo ja em ${NEW:0:11}: nada a fazer"
    exit 0
fi

CHANGED=$(git -C "$V2_CODE_DIR" diff --name-only "$OLD" "$NEW")
log "📦 ${OLD:0:11} -> ${NEW:0:11}: $(echo "$CHANGED" | grep -c .) arquivo(s)"

if echo "$CHANGED" | grep -qE '(^|/)version\.php$'; then
    echo "$CHANGED" | grep -E '(^|/)version\.php$' | sed 's/^/   /'
    die "version.php alterado: exige upgrade.php -> deploy manual em janela (guia, etapa E2)"
fi

DIRTY=$(git -C "$V2_CODE_DIR" status --porcelain --untracked-files=no)
if [ -n "$DIRTY" ]; then
    echo "$DIRTY" | sed 's/^/   /'
    die "Checkout com alteracoes locais (hotfix manual?). Confira e descarte com: git -C $V2_CODE_DIR checkout -- <arquivos>"
fi

git -C "$V2_CODE_DIR" checkout --quiet --detach "$NEW"
log "✅ Codigo v2: $(git -C "$V2_CODE_DIR" log -1 --format='%h %s')"

if echo "$CHANGED" | grep -qE '\.php$'; then
    log "🔄 PHP alterado: reiniciando $V2_CONTAINER (opcache sem revalidacao)"
    docker restart "$V2_CONTAINER" >/dev/null
    wait_http_ok "$V2_CONTAINER" || die "$V2_CONTAINER nao respondeu apos restart: docker logs $V2_CONTAINER"
fi

docker exec -u www-data "$V2_CONTAINER" php admin/cli/purge_caches.php
log "🧹 Caches limpos (nova revisao de JS/CSS)"
curl -s -o /dev/null -w "   publico: HTTP %{http_code}\n" "$PUBLIC_URL"
