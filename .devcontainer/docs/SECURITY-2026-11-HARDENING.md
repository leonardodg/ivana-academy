# Hardening de segurança — novembro/2026

Resposta à auditoria (seção 3 — Moodle). O estado da produção foi conferido em 2026-11-11
com comandos somente leitura.

| Item da auditoria | Estado em prod (11/11) | Ação nesta branch |
|---|---|---|
| 3.1 Core 4.5.15 → 4.5.15+ | 4.5.15 (Build 20261005) | Aplicado o diff `v4.5.15..MOODLE_405_STABLE` (Build 20261030, `$version` 2024100715.02). Só 8 arquivos (idiomas, testes behat, version.php), nenhum toca customização |
| 3.2 PHP 2Shell (CVE-2026-109618/109619) | **Já corrigido**: PHP 8.3.36, `session.upload_progress` Off | Imagem base fixada por digest + o build **falha** se o PHP for < 8.3.36. Dependabot (docker) propõe novos digests toda semana |
| 3.2 post_max_size 210M | 210M | Mantido. A redução para 32M do CERT-EU é mitigação *temporária* para PHP sem correção; o PHP já está corrigido, e o Moodle precisa de uploads grandes (SCORM/vídeo) |
| 3.3 `directorypermissions = 0777` | 0777, pastas world-writable no moodledata | Templates → `02770`; cron passa a rodar como `www-data`; o entrypoint normaliza o moodledata para 2770/0660 e corrige o dono. **config.php da VPS: passo manual abaixo** |
| 3.3 `sslproxy = true` | **Não** está no config.php de prod | Removido do config padrão da imagem (o Apache termina o TLS; Cloudflare Full strict) |
| 3.3 Sem limites de memória/PIDs | Sem limites (uso ~412 MiB / 21 PIDs) | `mem_limit 700m` / `pids_limit 256` em `production/compose.yml` (`MOODLE_MEM_LIMIT` / `MOODLE_PIDS_LIMIT`) |
| 3.3 Dockerfile legado (PHP 8.1, credenciais em ARG/ENV) | Não é usado (o v1 está parado e só serve de rollback) | Removidos: `Dockerfile`, `my-entrypoint`, `moodle-cron`, `docker-compose*.yml` da raiz (PHP 8.0), `config-docker.php`/`config.docker-develop.php` da raiz (acessíveis pela web), `production.yml` e `default-ssl.conf`. O v1 foi removido na migração para compose (11/11); o resto sai com o `retire-legacy.sh` |
| 3.4 Qual container atende | `moodle-v2` (Up 9 days); o v1 está parado | Produção passa para `docker compose` (container `moodle`) e o v1 é removido, ver PRODUCTION.md |

Achado extra: o cron rodava `cron.php` como **root**. Os arquivos que ele criava no moodledata
ficavam com dono root, e era por isso que o 0777 "funcionava". `/container.env` (que tem
MOODLE_DBPASS) ficava legível por qualquer usuário do container; agora é `640 root:www-data`.

## Aplicado em produção — 11/11/2026

Tudo entrou junto com a migração para docker compose ([PRODUCTION.md](PRODUCTION.md)): a imagem
nova (cron como www-data, normalização 2770/0660, limites), o upgrade para 4.5.15+ e, **por
último**, o `config.php` em `02770`. A ordem importa: se o `config.php` mudar antes, o cron
antigo, rodando como root, cria pastas `root:root 2770` que o Apache não consegue gravar. Por
isso o rollback manual volta o valor para `0777`.

Conferir depois:
```bash
docker exec moodle crontab -u www-data -l                                   # job do cron.php
docker exec moodle sh -c 'find /var/www/moodledata -perm -o+w | head'       # vazio
docker inspect -f '{{.HostConfig.Memory}} {{.HostConfig.PidsLimit}}' moodle
docker inspect -f '{{.State.OOMKilled}}' moodle                             # false
```
Se aparecer OOM-kill, aumentar `MOODLE_MEM_LIMIT` em `env/prod.env` e rodar `dc up -d`.
