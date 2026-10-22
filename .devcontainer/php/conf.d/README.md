# PHP conf.d - Ivana Academy

Arquivos `.ini` copiados para `/usr/local/etc/php/conf.d/` pela imagem
(`.devcontainer/build/moodle.Dockerfile`, stage `base`).

| Arquivo | Motivo |
|---|---|
| `99-security.ini` | `session.upload_progress.enabled = 0` - mitigacao PHP2Shell (CVE-2026-109618/109619) |

## Aplicar manualmente no container v1 (`moodle`, imagem `leodg/moodle-academy:4.5.1`)

O container v1 nao usa esta imagem, entao o ajuste foi aplicado a mao em
22/10/2026. Ele sobrevive a `docker restart`, mas **se perde se o container
for recriado** (`docker rm` + `docker run`, como faz o `production.yml`).
Para reaplicar, a partir da raiz do repositorio:

```bash
# Copia o .ini para dentro do container e reinicia (~15s fora do ar)
cat .devcontainer/php/conf.d/99-security.ini \
  | ssh ivana-moodle-aws 'docker exec -i moodle sh -c "cat > /usr/local/etc/php/conf.d/99-security.ini" && docker restart moodle'

# Conferir (CLI)
ssh ivana-moodle-aws 'docker exec moodle php -r "var_dump(ini_get(\"session.upload_progress.enabled\"));"'
```

Direto na VPS (sem o repositorio):

```bash
docker exec moodle sh -c 'printf "session.upload_progress.enabled = 0\n" > /usr/local/etc/php/conf.d/99-security.ini'
docker restart moodle
docker exec moodle php -r 'var_dump(ini_get("session.upload_progress.enabled"));'   # string(1) "0"
```
