#!/bin/bash
# =============================================================================
# switch-to-v2.sh - JANELA DE MANUTENCAO: troca o v1 pelo v2 (rodar prepare.sh antes)
# =============================================================================
#   1. modo manutencao (via v1)
#   2. dump do banco -> /backups/pre-v2-<data>.sql.gz  (ponto de volta)
#   3. para o v1 (restart=no, container e codigo ficam intactos)
#   4. sobe o v2 nas portas 80/443
#   5. admin/cli/upgrade.php (4.5.1+ -> 4.5.15) + purge caches
#   6. tira do modo manutencao e testa
#
# Se algo falhar depois do passo 3: rollback-to-v1.sh --restore-db
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

TS=$(date +%Y%m%d-%H%M%S)
DUMP="${BACKUP_DIR}/pre-v2-${TS}.sql.gz"

[ -f "$COMPOSE_DIR/env/prod.env" ] && [ -f "$V2_CODE_DIR/config.php" ] || die "Rode prepare.sh antes"
[ "$(docker inspect -f '{{.State.Running}}' "$V1_CONTAINER")" = true ] || die "v1 nao esta rodando"
if docker ps -q -f name="^${V2_CONTAINER}$" | grep -q .; then die "v2 ja esta rodando"; fi

read -r -p "Trocar a PRODUCAO para o v2 agora? Digite 'sim': " ok
[ "$ok" = sim ] || die "Cancelado"

# 1. Manutencao ----------------------------------------------------------------
log "🚧 Ativando modo manutencao"
docker exec -u www-data "$V1_CONTAINER" php admin/cli/maintenance.php --enable

# 2. Dump ------------------------------------------------------------------------
load_backup_db_vars
log "🗄  Dump do banco -> $DUMP"
mysqldump -h "$DB_HOST" -P 3306 -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction --quick --routines --triggers moodle | gzip > "$DUMP"
if ! zcat "$DUMP" | tail -1 | grep -q "Dump completed"; then
    docker exec -u www-data "$V1_CONTAINER" php admin/cli/maintenance.php --disable
    die "Dump incompleto: $DUMP - nada foi trocado, v1 continua no ar"
fi
log "✅ Dump ok ($(du -h "$DUMP" | cut -f1))"
cat > "$STATE_FILE" <<EOF
SWITCH_TS=${TS}
DUMP=${DUMP}
EOF

# 3. Para o v1 -------------------------------------------------------------------
log "⏸  Parando v1 ($V1_CONTAINER) - restart=no para nao voltar sozinho"
docker update --restart=no "$V1_CONTAINER" >/dev/null
docker stop "$V1_CONTAINER" >/dev/null

# 4. Sobe o v2 -------------------------------------------------------------------
log "🚀 Subindo v2"
v2_up
wait_http_ok "$V2_CONTAINER" || die "v2 nao respondeu. Logs: docker logs $V2_CONTAINER | Voltar: rollback-to-v1.sh"

# 5. Upgrade do Moodle -----------------------------------------------------------
log "⬆  admin/cli/upgrade.php"
docker exec -u www-data "$V2_CONTAINER" php admin/cli/upgrade.php --non-interactive \
    || die "upgrade.php falhou. Voltar: rollback-to-v1.sh --restore-db"
docker exec -u www-data "$V2_CONTAINER" php admin/cli/purge_caches.php

# 6. Libera e testa --------------------------------------------------------------
docker exec -u www-data "$V2_CONTAINER" php admin/cli/maintenance.php --disable
log "🔎 Testes"
docker exec "$V2_CONTAINER" php -r 'echo "   PHP ", PHP_VERSION, " upload_progress=", var_export(ini_get("session.upload_progress.enabled"), true), PHP_EOL;'
grep -E '^\$release' "$V2_CODE_DIR/version.php"
curl -s -o /dev/null -w "   publico: HTTP %{http_code} em %{time_total}s\n" "$PUBLIC_URL"
docker exec -u www-data "$V2_CONTAINER" php admin/cli/checks.php || true

log "🎉 Producao no v2. v1 parado (intacto). Dump: $DUMP"
log "   Se algo estiver errado: rollback-to-v1.sh --restore-db"
