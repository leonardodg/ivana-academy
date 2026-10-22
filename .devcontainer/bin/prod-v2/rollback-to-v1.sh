#!/bin/bash
# =============================================================================
# rollback-to-v1.sh - volta a producao para o container v1 (moodle 4.5.1+, PHP 8.1)
# =============================================================================
# Uso: rollback-to-v1.sh               # so troca os containers
#      rollback-to-v1.sh --restore-db  # + restaura o dump feito pelo switch
#
# --restore-db e OBRIGATORIO se o upgrade.php chegou a rodar: o codigo 4.5.1+
# do v1 recusa abrir um banco ja atualizado para 4.5.15 ("downgrade").
# Tudo que foi gravado no banco depois do dump (acessos, notas...) e perdido.
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

RESTORE_DB=0
[ "${1:-}" = "--restore-db" ] && RESTORE_DB=1
[ -f "$STATE_FILE" ] && source "$STATE_FILE"

if [ "$RESTORE_DB" = 1 ]; then
    [ -n "${DUMP:-}" ] && [ -f "$DUMP" ] || die "Dump nao encontrado ($STATE_FILE)"
    echo "⚠ Vai RESTAURAR o banco a partir de $DUMP (dados posteriores serao perdidos)."
fi
read -r -p "Voltar a PRODUCAO para o v1? Digite 'sim': " ok
[ "$ok" = sim ] || die "Cancelado"

log "⏹  Parando v2"
v2_down

if [ "$RESTORE_DB" = 1 ]; then
    load_backup_db_vars
    log "🗄  Restaurando $DUMP"
    zcat "$DUMP" | mysql -h "$DB_HOST" -P 3306 -u "$DB_USER" -p"$DB_PASS" moodle
    log "✅ Banco restaurado"
fi

log "▶  Subindo v1 ($V1_CONTAINER)"
docker update --restart=always "$V1_CONTAINER" >/dev/null
docker start "$V1_CONTAINER" >/dev/null
wait_http_ok "$V1_CONTAINER" || die "v1 nao respondeu: docker logs $V1_CONTAINER"
docker exec -u www-data "$V1_CONTAINER" php admin/cli/maintenance.php --disable || true
docker exec -u www-data "$V1_CONTAINER" php admin/cli/purge_caches.php || true
curl -s -o /dev/null -w "   publico: HTTP %{http_code}\n" "$PUBLIC_URL"
log "✅ Producao de volta no v1"
