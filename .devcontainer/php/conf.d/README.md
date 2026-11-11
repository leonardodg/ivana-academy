# PHP conf.d - Ivana Academy

Arquivos `.ini` copiados para `/usr/local/etc/php/conf.d/` pela imagem
(`.devcontainer/build/moodle.Dockerfile`, stage `base`).

| Arquivo | Motivo |
|---|---|
| `99-security.ini` | `session.upload_progress.enabled = 0` - mitigacao PHP2Shell (CVE-2026-109618/109619) |

Faz parte da imagem: vale para todo container criado a partir dela
(`docker compose up` em producao), sem passo manual.
