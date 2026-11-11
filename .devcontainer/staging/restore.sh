#!/bin/bash
# =============================================================================
# restore.sh - sobe o STAGING LOCAL a partir de um backup da producao
# =============================================================================
#   1. importa o dump (/backups/*.sql.gz da VPS) numa MariaDB 11.4 local
#   2. anonymize.sql: e-mails -> user<id>@staging.invalid; zera SMTP, push,
#      tokens, segredos de plugins, pagamentos; desliga tarefas agendadas
#   3. extrai o moodledata (opcional; sem ele o tema perde logo/imagens)
#   4. sobe o Moodle (mesma imagem da producao, codigo deste checkout),
#      upgrade.php (plugins novos da branch) e purge_caches
#
# Uso: .devcontainer/staging/restore.sh <dump.sql.gz> [moodledata.tar.gz]
#      STAGING_RESET=1 ... apaga o banco staging anterior antes
# Abrir: https://localhost:8543 (STAGING_PORT=...; certificado local autoassinado)
# =============================================================================
set -euo pipefail
cd "$(dirname "$0")"

DUMP="${1:?uso: restore.sh <dump.sql.gz> [moodledata.tar.gz]}"
MDATA="${2:-}"
DATA_DIR="./data/moodledata"
PORT="${STAGING_PORT:-8543}"
URL="https://localhost:${PORT}"
log() { echo "[$(date +%H:%M:%S)] $*"; }
die() { echo "❌ $*" >&2; exit 1; }
dc()  { docker compose -f compose.yml "$@"; }

[ -f "$DUMP" ] || die "dump nao encontrado: $DUMP"
[ -z "$MDATA" ] || [ -f "$MDATA" ] || die "moodledata nao encontrado: $MDATA"

if [ "${STAGING_RESET:-0}" = 1 ]; then
    log "🗑  STAGING_RESET=1: removendo banco e moodledata do staging"
    dc down -v --remove-orphans
    rm -rf ./data
fi

# 1. Banco ---------------------------------------------------------------------
dc up -d --wait db
if [ "$(dc exec -T db mariadb -umoodle -pstaging -Nse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='moodle'")" != 0 ]; then
    die "o banco staging ja tem tabelas - use STAGING_RESET=1 para recomecar"
fi
log "🗄  Importando $(basename "$DUMP") ($(du -h "$DUMP" | cut -f1))"
gunzip -c "$DUMP" | dc exec -T db mariadb -umoodle -pstaging moodle

# 2. Anonimizar (antes de o Moodle subir) -------------------------------------------
log "🕶  anonymize.sql"
dc exec -T db mariadb -umoodle -pstaging moodle < anonymize.sql
LEFT=$(dc exec -T db mariadb -umoodle -pstaging -Nse \
    "SELECT COUNT(*) FROM moodle.mdl_user WHERE email <> '' AND email NOT LIKE '%@staging.invalid'")
[ "$LEFT" = 0 ] || die "$LEFT e-mails nao foram trocados - abortando"
log "✅ e-mails trocados ($(dc exec -T db mariadb -umoodle -pstaging -Nse "SELECT COUNT(*) FROM moodle.mdl_user") usuarios)"

# 3. moodledata ----------------------------------------------------------------------
mkdir -p "$DATA_DIR"
if [ -n "$MDATA" ]; then
    log "📦 Extraindo moodledata ($(du -h "$MDATA" | cut -f1))"
    # O tar da VPS pode ter o prefixo moodledata/ ou nao: detecta.
    first=$(tar -tzf "$MDATA" 2>/dev/null | head -1 || true)   # SIGPIPE do tar com pipefail
    strip=0; [[ "$first" == moodledata/* || "$first" == ./moodledata/* ]] && strip=1
    tar -xzf "$MDATA" -C "$DATA_DIR" --strip-components="$strip" \
        --exclude='*/sessions/*' --exclude='*/cache/*' --exclude='*/localcache/*' \
        --exclude='*/temp/*' --exclude='*/trashdir/*'
fi
# Arquivos de producao nunca podem disparar envio: o config.php ja forca, mas
# o climaintenance de producao (se veio no tar) travaria o site.
rm -f "$DATA_DIR/climaintenance.html"

# 4. Moodle ---------------------------------------------------------------------------
log "🚀 Subindo o Moodle (imagem de producao + codigo deste checkout)"
dc up -d moodle
for _ in $(seq 1 60); do
    code=$(curl -sk -o /dev/null -w '%{http_code}' ${URL}/login/index.php || true)
    [[ "$code" =~ ^(2|3|503) ]] && break
    sleep 5
done
log "⬆  upgrade.php (plugins/versoes desta branch)"
dc exec -T -u www-data moodle php admin/cli/upgrade.php --non-interactive
dc exec -T -u www-data moodle php admin/cli/purge_caches.php
dc exec -T moodle sh -c 'crontab -u www-data -l 2>/dev/null | grep -q cron.php && echo "❌ cron ativo" || echo "✅ sem cron"'
curl -sk -o /dev/null -w "   ${URL} -> HTTP %{http_code}\n" "${URL}/"
log "🎉 Staging pronto: ${URL} (login: as mesmas senhas da producao)"
