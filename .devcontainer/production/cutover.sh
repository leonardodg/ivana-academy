#!/bin/bash
# =============================================================================
# cutover.sh - JANELA DE MANUTENCAO, UMA VEZ: docker run (moodle-v2) -> compose
# =============================================================================
#   0. pre-checagens (compose instalado, env/secrets/certs, config.php)
#   1. modo manutencao + dump do banco (/backups/pre-compose-<data>.sql.gz)
#   2. remove o container v1 `moodle` (parado desde 22/10 - libera o nome)
#   3. para o moodle-v2 (restart=no, FICA como rollback ate o retire-legacy.sh)
#   4. /home/admin/moodle-v2 -> /home/admin/moodle (+ symlink p/ o rollback)
#   5. checkout do codigo (origin/main) + docker compose up -d --wait
#      -> imagem nova: cron como www-data, moodledata 2770/0660, limites
#   6. upgrade.php + purge caches
#   7. config.php: directorypermissions 0777 -> 02770 (so depois do passo 5)
#   8. sai da manutencao e testa
#
# Falhou no passo 5+:  cutover.sh --rollback   (volta para o moodle-v2)
# Deu certo e estabilizou (1-2 dias): retire-legacy.sh
# =============================================================================
set -euo pipefail
source "$(dirname "$0")/common.sh"

OLD_CONTAINER="moodle-v2"
OLD_BASE_DIR="/home/admin/moodle-v2"
TS=$(date +%Y%m%d-%H%M%S)

rollback() {
    log "↩  Rollback para $OLD_CONTAINER"
    dc down 2>/dev/null || true
    # O moodle-v2 roda o cron como root: com 02770 ele criaria pastas root:root
    # que o Apache nao grava. Volta o valor antigo enquanto ele estiver no ar.
    sed -i 's/^\$CFG->directorypermissions = 02770;/$CFG->directorypermissions = 0777;/' "$CODE_DIR/config.php" 2>/dev/null || true
    docker update --restart=unless-stopped "$OLD_CONTAINER" >/dev/null
    docker start "$OLD_CONTAINER" >/dev/null
    CONTAINER="$OLD_CONTAINER" wait_http_ok || die "$OLD_CONTAINER nao respondeu: docker logs $OLD_CONTAINER"
    docker exec -u www-data "$OLD_CONTAINER" php admin/cli/maintenance.php --disable || true
    log "✅ No ar com $OLD_CONTAINER. Banco: se o upgrade.php rodou, restaurar o dump pre-compose-*.sql.gz"
}
[ "${1:-}" = "--rollback" ] && { rollback; exit 0; }

# 0. Pre-checagens -----------------------------------------------------------------
managed_by_compose && { log "✅ Ja migrado: $CONTAINER e gerenciado pelo compose"; exit 0; }
require_compose
require_files
container_running "$OLD_CONTAINER" || die "$OLD_CONTAINER nao esta rodando - estado inesperado"
SRC_CODE="$OLD_BASE_DIR/code"
[ -L "$OLD_BASE_DIR" ] && SRC_CODE="$CODE_DIR"
[ -f "$SRC_CODE/config.php" ] || die "config.php ausente em $SRC_CODE"
if docker inspect "$CONTAINER" >/dev/null 2>&1 && container_running "$CONTAINER"; then
    die "Container v1 '$CONTAINER' esta RODANDO - esperado parado. Confira antes."
fi
if grep -lE "moodle-v2|docker (exec|restart|start|stop) moodle" /backups/backup.sh 2>/dev/null; then
    log "⚠  /backups/backup.sh cita nomes de container: revise depois (novo nome: $CONTAINER)"
fi
dc config --quiet

log "Plano: dump -> remove v1 '$CONTAINER' -> para $OLD_CONTAINER -> compose up -> upgrade.php"
read -r -p "Migrar a PRODUCAO para docker compose agora? Digite 'sim': " ok
[ "$ok" = sim ] || die "Cancelado"

# 1. Manutencao + dump ------------------------------------------------------------------
log "🚧 Modo manutencao"
docker exec -u www-data "$OLD_CONTAINER" php admin/cli/maintenance.php --enable
DUMP="${BACKUP_DIR}/pre-compose-${TS}.sql.gz"
( db_dump "$DUMP" ) || {
    docker exec -u www-data "$OLD_CONTAINER" php admin/cli/maintenance.php --disable
    die "Dump falhou - nada foi trocado, $OLD_CONTAINER continua no ar"
}

# 2. v1 ------------------------------------------------------------------------------------
if docker inspect "$CONTAINER" >/dev/null 2>&1; then
    log "🗑  Removendo container v1 '$CONTAINER' ($(docker inspect -f '{{.Config.Image}}' "$CONTAINER"))"
    docker rm "$CONTAINER" >/dev/null
fi

# 3. Para o moodle-v2 ---------------------------------------------------------------------
log "⏸  Parando $OLD_CONTAINER (restart=no; fica para rollback)"
docker update --restart=no "$OLD_CONTAINER" >/dev/null
docker stop "$OLD_CONTAINER" >/dev/null
trap 'echo; log "❌ Falhou. Para voltar: $0 --rollback"' ERR

# 4. Diretorio do codigo --------------------------------------------------------------------
if [ ! -L "$OLD_BASE_DIR" ]; then
    [ -e "$BASE_DIR" ] && die "$BASE_DIR ja existe - confira"
    mv "$OLD_BASE_DIR" "$BASE_DIR"
    ln -s "$BASE_DIR" "$OLD_BASE_DIR"   # bind-mount do moodle-v2 (rollback)
    log "📁 $OLD_BASE_DIR -> $BASE_DIR (symlink temporario)"
fi
set_env MOODLE_CODE_DIR "$CODE_DIR"
[ -f "$BASE_DIR/last-switch.env" ] && rm -f "$BASE_DIR/last-switch.env"

# 5. Codigo + compose ---------------------------------------------------------------------------
git -C "$CODE_DIR" fetch --quiet origin
DIRTY=$(git -C "$CODE_DIR" status --porcelain --untracked-files=no)
[ -z "$DIRTY" ] || { echo "$DIRTY"; die "Alteracoes locais no checkout"; }
git -C "$CODE_DIR" checkout --quiet --detach origin/main
log "✅ Codigo: $(git -C "$CODE_DIR" log -1 --format='%h %s')"
# shellcheck disable=SC1090
source "$ENV_FILE"
docker pull --quiet "leodg/moodle-academy:${IMAGE_TAG}"
log "🚀 docker compose up (1a subida normaliza permissoes do moodledata: pode levar minutos)"
dc up -d --wait --wait-timeout 900

# 6. Upgrade ------------------------------------------------------------------------------------
log "⬆  upgrade.php"
moosh admin/cli/upgrade.php --non-interactive
moosh admin/cli/purge_caches.php

# 7. Permissoes -----------------------------------------------------------------------------------
[ -z "$(docker exec "$CONTAINER" crontab -l 2>/dev/null | grep cron.php)" ] || die "cron ainda no crontab do root"
docker exec "$CONTAINER" crontab -u www-data -l | grep -q cron.php || die "cron do www-data ausente"
WW=$(docker exec "$CONTAINER" sh -c 'find /var/www/moodledata -perm -o+w -print -quit')
[ -z "$WW" ] || die "moodledata ainda tem itens world-writable: $WW"
sed -i 's/^\$CFG->directorypermissions *= *0777;/$CFG->directorypermissions = 02770;/' "$CODE_DIR/config.php"
grep -n directorypermissions "$CODE_DIR/config.php"

# 8. Libera e testa ---------------------------------------------------------------------------------
moosh admin/cli/maintenance.php --disable
trap - ERR
docker exec "$CONTAINER" php -r 'echo "   PHP ", PHP_VERSION, " upload_progress=", var_export(ini_get("session.upload_progress.enabled"), true), PHP_EOL;'
grep -E '^\$release' "$CODE_DIR/version.php"
docker inspect -f '   limites: mem={{.HostConfig.Memory}} pids={{.HostConfig.PidsLimit}}' "$CONTAINER"
curl -s -o /dev/null -w "   publico: HTTP %{http_code} em %{time_total}s\n" "$PUBLIC_URL"
moosh admin/cli/checks.php || true

log "🎉 Producao em docker compose (container '$CONTAINER'). Dump: $DUMP"
log "   Problema: $0 --rollback  |  Estavel (1-2 dias): $PROD_DIR/retire-legacy.sh"
