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
| 3.3 Sem limites de memória/PIDs | Sem limites (uso ~412 MiB / 21 PIDs) | `--memory 700m --pids-limit 256` (common.sh e prod-v2.yml; dá para trocar com `MOODLE_MEM_LIMIT` / `MOODLE_PIDS_LIMIT`) |
| 3.3 Dockerfile legado (PHP 8.1, credenciais em ARG/ENV) | Não é usado (o v1 está parado e só serve de rollback) | Removidos: `Dockerfile`, `my-entrypoint`, `moodle-cron`, `docker-compose*.yml` da raiz (PHP 8.0), `config-docker.php`/`config.docker-develop.php` da raiz (acessíveis pela web), `production.yml` e `default-ssl.conf`. O rollback-to-v1 só dá `docker start` no container antigo, então não depende de nenhum deles |
| 3.4 Qual container atende | `moodle-v2` (Up 9 days); o v1 está parado | — |

Achado extra: o cron rodava `cron.php` como **root**. Os arquivos que ele criava no moodledata
ficavam com dono root, e era por isso que o 0777 "funcionava". `/container.env` (que tem
MOODLE_DBPASS) ficava legível por qualquer usuário do container; agora é `640 root:www-data`.

## Aplicar em produção (manual, nesta ordem)

O merge em `main` só builda a imagem e atualiza o código. **O container que está rodando
continua com o entrypoint antigo** até ser recriado.

1. **Backup** (como em 22/10): DB + `tar` do moodledata.
2. **Recriar o v2 com a imagem nova** (cron como www-data, normalização 2770/0660, limites):
   ```bash
   cd /home/admin/devcontainer && source bin/prod-v2/common.sh
   docker pull leodg/moodle-academy:4.5.15-php8.3
   v2_down && v2_up && wait_http_ok moodle-v2
   ```
   Na primeira subida o `find chmod/chown` passa pelo moodledata inteiro, então pode levar
   alguns minutos.
3. **Conferir**:
   ```bash
   docker exec moodle-v2 crontab -u www-data -l     # job do cron.php
   docker exec moodle-v2 crontab -l                  # root: vazio
   docker exec moodle-v2 sh -c 'find /var/www/moodledata -maxdepth 1 -perm -o+w'   # vazio
   docker exec moodle-v2 sh -c 'find /var/www/moodledata ! -user www-data | head'  # vazio
   docker inspect -f '{{.HostConfig.Memory}} {{.HostConfig.PidsLimit}}' moodle-v2
   ```
4. **Só depois do passo 3**, restringir o config.php da VPS:
   ```bash
   sed -i 's/^\$CFG->directorypermissions = 0777;/$CFG->directorypermissions = 02770;/' \
       /home/admin/moodle-v2/code/config.php
   ```
   (O config.php está fora do git. Se ele mudar antes do passo 2, o cron como root cria pastas
   `root:root 2770` que o Apache não consegue gravar.)
5. **Upgrade do core 4.5.15+**: Administração do site › Notificações (ou
   `docker exec -u www-data moodle-v2 php admin/cli/upgrade.php --non-interactive`).
6. Observar `docker stats moodle-v2` por 1–2 dias. Se aparecer OOM-kill (`docker inspect -f
   '{{.State.OOMKilled}}'`), subir `MOODLE_MEM_LIMIT` no env/prod.env.

Rollback: `bin/prod-v2/rollback-to-v1.sh` (igual ao de antes) ou recriar o v2 com a tag
`4.5.15-php8.3-<sha anterior>`.
