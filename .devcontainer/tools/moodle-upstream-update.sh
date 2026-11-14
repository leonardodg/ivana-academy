#!/usr/bin/env bash
# Aplica no branch atual as mudancas do Moodle oficial (upstream) desde a versao que esta no
# version.php ate um alvo (tag ou branch). O repo nao tem o upstream como ancestral desde a 4.5.1+
# (as atualizacoes entram como diff), entao a base e achada pelo $release do version.php.
#
#   .devcontainer/tools/moodle-upstream-update.sh            # ate upstream/MOODLE_405_STABLE
#   .devcontainer/tools/moodle-upstream-update.sh v4.5.17    # ate uma tag
#
# Rode num branch novo criado a partir da main. Nada e commitado: revise e commite voce.
# Procedimento completo (staging, validacao, producao): .devcontainer/docs/MOODLE-UPSTREAM-UPDATE.md
set -euo pipefail

STABLE=MOODLE_405_STABLE
TARGET=${1:-upstream/$STABLE}
cd "$(git rev-parse --show-toplevel)"

git remote get-url upstream >/dev/null 2>&1 \
  || git remote add upstream https://github.com/moodle/moodle.git
git fetch --quiet upstream "$STABLE" --tags

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "Working tree com alteracoes; faca commit ou stash antes." >&2; exit 1
fi

release=$(sed -nE "s/^\\\$release *= *'([^']+)'.*/\1/p" version.php)
# Commit do upstream que introduziu exatamente esse $release.
base=$(git log "upstream/$STABLE" --format=%H -F -S"'$release'" -- version.php | tail -1)
if [ -z "$base" ] || ! git show "$base:version.php" | grep -qF "'$release'"; then
  echo "Nao achei no upstream o commit do release '$release'." >&2; exit 1
fi
echo "Base:  $(git log -1 --format='%h %s' "$base")  ($release)"
echo "Alvo:  $(git log -1 --format='%h %s' "$TARGET")"

files=$(git diff --name-only "$base" "$TARGET")
[ -n "$files" ] || { echo "Nada a aplicar."; exit 0; }
echo "Arquivos: $(wc -l <<<"$files")"

# Arquivos do diff que tem customizacao local (diferem da base) precisam de revisao manual.
custom=()
while IFS= read -r f; do
  git diff --quiet "$base" HEAD -- "$f" || custom+=("$f")
done <<<"$files"
if [ ${#custom[@]} -gt 0 ]; then
  echo "ATENCAO: arquivos customizados localmente tambem mudaram no upstream:"
  printf '  %s\n' "${custom[@]}"
fi

git diff --binary "$base" "$TARGET" | git apply --index -3

# Confere que os arquivos sem customizacao ficaram iguais ao alvo.
bad=0
while IFS= read -r f; do
  [[ " ${custom[*]-} " == *" $f "* ]] && continue
  git diff --cached --quiet "$TARGET" -- "$f" || { echo "DIVERGE: $f"; bad=1; }
done <<<"$files"
[ $bad -eq 0 ] || { echo "Verificacao falhou." >&2; exit 1; }

echo
grep -E '^\$(version|release)' version.php
echo "Upgrades de banco no diff:"; grep -E '/db/upgrade\.php$' <<<"$files" || echo "  (nenhum)"
echo
echo "Proximos passos: git commit; PR para main; depois do deploy rodar na VPS"
echo "  admin/cli/upgrade.php --non-interactive e purge_caches (ver docs/PRODUCTION.md)."
