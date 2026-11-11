#!/bin/bash
# =============================================================================
# install-compose.sh - instala o plugin `docker compose` na VPS (UMA VEZ)
# =============================================================================
# A VPS tem Docker 20.10 (Debian 12) sem o plugin. Instala o binario oficial,
# versao fixa e sha256 conferido, em ~/.docker/cli-plugins do usuario atual:
# sem sudo, sem mexer no daemon nem no apt. Remover = apagar o arquivo.
#
# Depois testa a API do daemon com um `compose run` descartavel (outro
# projeto, sem portas, sem banco) - o site no ar nao e tocado.
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

VERSION="v5.6.1"
case "$(uname -m)" in
    x86_64)  ARCH=x86_64;  SHA=47737f4236e6d3ccea7291995203f8f018058783b4c3a9469119811a0b5db8c0 ;;
    aarch64) ARCH=aarch64; SHA=42cfa75f51ad0ccf290bad37899e0038a8c10a0069e85233ecc1d53e3ff1347d ;;
    *) die "Arquitetura nao suportada: $(uname -m)" ;;
esac

PLUGIN_DIR="${HOME}/.docker/cli-plugins"
TARGET="${PLUGIN_DIR}/docker-compose"

if docker compose version 2>/dev/null | grep -q "${VERSION}"; then
    log "✅ docker compose ${VERSION} ja instalado"
else
    mkdir -p "$PLUGIN_DIR"
    tmp=$(mktemp)
    trap 'rm -f "$tmp"' EXIT
    log "📥 docker compose ${VERSION} (${ARCH})"
    curl -fsSL -o "$tmp" "https://github.com/docker/compose/releases/download/${VERSION}/docker-compose-linux-${ARCH}"
    echo "${SHA}  ${tmp}" | sha256sum -c --quiet - || die "sha256 nao confere"
    install -m 0755 "$tmp" "$TARGET"
    log "✅ Instalado em $TARGET"
fi
docker compose version

# Valida o compose.yml e a compatibilidade com o daemon 20.10.
require_files
dc config --quiet && log "✅ compose.yml valido"
log "🧪 Teste de API: compose run descartavel (projeto compose-smoke)"
docker compose --project-directory "$PROD_DIR" -f "$PROD_DIR/compose.yml" \
    --env-file "$ENV_FILE" -p compose-smoke \
    run --rm --no-deps --entrypoint php "$SERVICE" -r 'echo "   PHP ", PHP_VERSION, PHP_EOL;'
docker compose -p compose-smoke down --remove-orphans >/dev/null 2>&1 || true
log "🎯 compose pronto. Proximo passo (janela): $PROD_DIR/cutover.sh"
