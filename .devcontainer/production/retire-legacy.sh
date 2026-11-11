#!/bin/bash
# =============================================================================
# retire-legacy.sh - remove os vestigios do v1 e do moodle-v2 (docker run)
# =============================================================================
# Rodar so depois do cutover.sh, com o compose estavel (1-2 dias). Cada item
# pede confirmacao; o que for removido aqui NAO tem volta (os backups do
# /backups e do Google Drive continuam).
#
#   - container moodle-v2 (parado, rollback do cutover) + symlink moodle-v2
#   - imagens que nenhum container usa (v1 4.5.1, builds antigos)
#   - /home/admin/public_html            (codigo do v1, Moodle 4.5.1+ / PHP 8.1)
#   - /home/admin/local/letsencrypt      (certificado do v1; hoje: Cloudflare Origin CA)
#   - secrets/db.env (senha em formato env, usado so pelo moodle-v2)
#   - /home/admin/devcontainer/bin/prod-v2 e compose/ antigos (scripts docker run)
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

managed_by_compose && container_running "$CONTAINER" \
    || die "$CONTAINER nao esta no ar via compose - rode o cutover.sh antes"
wait_http_ok || die "$CONTAINER nao responde - nao e hora de remover o rollback"

confirm() { read -r -p "$1 [s/N] " a; [ "$a" = s ] || [ "$a" = S ]; }

if docker inspect moodle-v2 >/dev/null 2>&1 && confirm "Remover container moodle-v2 (rollback do cutover)?"; then
    docker rm -f moodle-v2 >/dev/null && log "🗑  moodle-v2 removido"
    [ -L /home/admin/moodle-v2 ] && rm /home/admin/moodle-v2 && log "🗑  symlink /home/admin/moodle-v2"
fi

if confirm "Remover imagens sem container (docker image prune -a)?"; then
    docker image prune -a -f | tail -1
fi

if [ -f "$LEGACY_DB_ENV" ] && ! docker inspect moodle-v2 >/dev/null 2>&1 \
   && confirm "Remover $LEGACY_DB_ENV (senha em formato env; hoje: secrets/db_user|db_password)?"; then
    rm -f -- "$LEGACY_DB_ENV" && log "🗑  $LEGACY_DB_ENV"
fi

for d in /home/admin/public_html /home/admin/local/letsencrypt \
         "$DEVCONTAINER_DIR/bin/prod-v2" "$DEVCONTAINER_DIR/compose"; do
    [ -e "$d" ] || continue
    if confirm "Remover $d ($(du -sh "$d" 2>/dev/null | cut -f1))?"; then
        rm -rf -- "$d" && log "🗑  $d"
    fi
done
rmdir "$DEVCONTAINER_DIR/bin" 2>/dev/null || true

df -h / | tail -1 | awk '{print "💾 Livre em /: " $4 " (" $5 " usado)"}'
log "✅ Legado removido"
