# Guia manual - PHP 8.3 + Moodle 4.5.15 + moodle-v2 (VPS `ivana-moodle-aws`)

A etapa A e feita pelo CI; as demais sao rodadas **a mao**, um comando por vez, conferindo o resultado
esperado antes de seguir. Os scripts em `.devcontainer/bin/prod-v2/` fazem o mesmo
e ficam como referencia. Contexto e decisoes: `UPGRADE-PHP83-MOODLE-4515.md`.

| Etapa | Site no ar? | Duracao |
|---|---|---|
| A. Preparar (CI: `prepare-vps`) | sim | ~10 min |
| B. Validar o moodle-v2 sem portas | sim | ~5 min |
| C. Trocar v1 -> v2 (janela) | **nao** (manutencao) | ~15 min |
| D. Testar | sim | ~10 min |
| R. Voltar para o v1 (se precisar) | nao | ~5 min |

---

## A. Preparar (sem parar o site)

> **Automatico pelo CI:** push em `main` -> `production-v2.yml` builda, valida e
> roda o `prepare.sh` na VPS (job `prepare-vps`), que faz exatamente A2-A5. Conferir a saida
> do job no GitHub Actions e seguir para a etapa B. Os comandos abaixo ficam para rodar a mao
> se o CI falhar ou para conferir.

```bash
ssh ivana-moodle-aws
```

Variaveis usadas em todas as etapas (colar no inicio de cada sessao SSH):

```bash
IMAGE=leodg/moodle-academy:4.5.15-php8.3
GIT_REF=main
V2=/home/admin/moodle-v2
CFG=/home/admin/devcontainer
```

**A1. Espaco em disco** - precisa de pelo menos 3G livres.

```bash
df -h /
docker system df
# se faltar: remove so imagens <none> (a 4.5.1 em uso fica)
docker image prune
```

**A2. Codigo v2** (clone separado; o `public_html` do v1 nao e tocado)

```bash
mkdir -p $V2
git clone https://github.com/leonardodg/ivana-academy.git $V2/code   # so na 1a vez
git -C $V2/code fetch origin
git -C $V2/code checkout --detach origin/$GIT_REF
git -C $V2/code log -1 --oneline
grep '^\$release' $V2/code/version.php
```

Esperado: `$release  = '4.5.15 (Build: 20261005)';`

**A3. config.php** (o mesmo de producao)

```bash
cp -p /home/admin/public_html/config.php $V2/code/config.php
diff /home/admin/public_html/config.php $V2/code/config.php && echo IGUAL
```

**A4. Variaveis, credenciais e certificado** (lidos do container v1; nada digitado)

```bash
mkdir -p $CFG/env $CFG/secrets $CFG/certs
v1env() { docker inspect moodle --format '{{range .Config.Env}}{{println .}}{{end}}' | sed -n "s/^$1=//p"; }

cat > $CFG/env/prod.env <<EOF
IMAGE_TAG=${IMAGE#*:}
MOODLE_CODE_DIR=$V2/code
MOODLE_URL=$(v1env MOODLE_URL)
MOODLE_DBTYPE=$(v1env MOODLE_DBTYPE)
MOODLE_DBHOST=$(v1env MOODLE_DBHOST)
MOODLE_DBNAME=$(v1env MOODLE_DBNAME)
MOODLE_DATA=$(v1env MOODLE_DATA)
EOF

printf 'MOODLE_DBUSER=%s\nMOODLE_DBPASS=%s\n' "$(v1env MOODLE_DBUSER)" "$(v1env MOODLE_DBPASS)" > $CFG/secrets/db.env
cp -L /home/admin/local/letsencrypt/live/ivana.academy/fullchain.pem $CFG/certs/server.crt
cp -L /home/admin/local/letsencrypt/live/ivana.academy/privkey.pem   $CFG/certs/server.key
chmod 600 $CFG/secrets/db.env $CFG/certs/server.key

cat $CFG/env/prod.env
openssl x509 -noout -enddate -in $CFG/certs/server.crt
```

Esperado: `prod.env` com `MOODLE_URL=https://ivana.academy`, `MOODLE_DBTYPE=mariadb`.
O certificado de origem esta vencido desde 02/05/2026 (igual ao v1; a Cloudflare aceita).

**A5. Imagem**

```bash
docker pull $IMAGE
docker run --rm --entrypoint php $IMAGE -r 'echo PHP_VERSION, " upload_progress=", var_export(ini_get("session.upload_progress.enabled"), true), PHP_EOL;'
```

Esperado: `8.3.36 upload_progress='0'` (ou maior que 8.3.36).

---

## B. Validar o moodle-v2 sem portas (site continua no v1)

Container temporario `moodle-v2-check`: **sem portas publicadas** (nao conflita com o
v1 e nao fica exposto), **sem cron**, com o **codigo atual do v1 em somente leitura**
(4.5.1+). Valida o runtime novo (PHP 8.3, Apache, config, banco, moodledata) sem
mudar o banco. Com o codigo 4.5.15 o Moodle veria upgrade pendente - isso so na etapa C.

```bash
set -a; source $CFG/env/prod.env; set +a
docker run -d --name moodle-v2-check --memory 512m \
  -e ENVIRONMENT=validation -e CRON_ENABLED=false \
  -e MOODLE_URL -e MOODLE_DBTYPE -e MOODLE_DBHOST -e MOODLE_DBNAME -e MOODLE_DATA \
  -e MOODLE_DBPFX=mdl_ -e MOODLE_DBCOLL=utf8mb4_bin -e PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
  --env-file $CFG/secrets/db.env \
  -v $CFG/certs/server.crt:/run/secrets/ssl_cert:ro \
  -v $CFG/certs/server.key:/run/secrets/ssl_key:ro \
  -v /home/admin/public_html:/var/www/html:ro \
  -v /home/admin/moodledata:/var/www/moodledata \
  $IMAGE

docker logs moodle-v2-check 2>&1 | tail -20
```

Testes (por dentro do container):

```bash
# pagina de login com o Host real -> 200
docker exec moodle-v2-check curl -sk -o /dev/null -w '%{http_code}\n' -H 'Host: ivana.academy' https://localhost/login/index.php
# PHP 8.3 + upload_progress desligado no Apache (nao so no CLI)
docker exec moodle-v2-check php -r 'echo PHP_VERSION, PHP_EOL;'
# banco, versao e checagens do Moodle (como www-data)
docker exec -u www-data moodle-v2-check php admin/cli/checks.php
docker exec moodle-v2-check crontab -l 2>&1      # esperado: "no crontab"
free -m                                           # memoria com os dois rodando
```

Remover ao terminar:

```bash
docker rm -f moodle-v2-check
```

---

## C. Trocar v1 -> v2 (janela de manutencao)

Avisar os alunos antes. A partir do C1 o site mostra a pagina de manutencao.

```bash
set -a; source $CFG/env/prod.env; set +a
TS=$(date +%Y%m%d-%H%M%S); DUMP=/backups/pre-v2-$TS.sql.gz; echo $DUMP
```

**C1. Modo manutencao (pelo v1)**

```bash
docker exec -u www-data moodle php admin/cli/maintenance.php --enable
curl -s -o /dev/null -w '%{http_code}\n' https://ivana.academy/login/index.php   # 503
```

**C2. Dump do banco** (credenciais do proprio `backup.sh`, sem executa-lo)

```bash
eval "$(grep -E '^(DB_HOST|DB_USER|DB_PASS)=' /backups/backup.sh)"
mysqldump -h "$DB_HOST" -P 3306 -u "$DB_USER" -p"$DB_PASS" \
  --single-transaction --quick --routines --triggers moodle | gzip > $DUMP
zcat $DUMP | tail -1        # esperado: -- Dump completed on ...
ls -lh $DUMP                # esperado: ~12-15M
echo "DUMP=$DUMP" > $V2/last-switch.env
```

Se o dump falhar: `docker exec -u www-data moodle php admin/cli/maintenance.php --disable`
e parar aqui - nada foi trocado.

**C2b. Backup do moodledata** (manutencao ativa = cron do v1 parado)

```bash
MDATA=/backups/pre-v2-moodledata-$TS.tar.gz
sudo tar -czf $MDATA -C / --exclude=moodledata/cache --exclude=moodledata/localcache \
  --exclude=moodledata/sessions --exclude=moodledata/temp moodledata
sudo tar -tzf $MDATA | grep -c '^moodledata/filedir/'   # > 0
ls -lh $MDATA
echo "MDATA=$MDATA" >> $V2/last-switch.env
```

**C3. Parar o v1** (container e codigo ficam intactos)

```bash
docker update --restart=no moodle
docker stop moodle
```

**C3a. Atualizar o sistema da VPS** (nenhum container rodando; mantem configs locais,
inclusive `/etc/docker/daemon.json`)

```bash
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get -y -o Dpkg::Options::=--force-confold upgrade
uname -r; ls /boot/vmlinuz-* | tail -2      # kernel novo instalado?
cat /etc/docker/daemon.json                 # dns 8.8.8.8 / 8.8.4.4
```

**C3b. Reboot** (so se veio kernel novo ou docker atualizado). IP publico se mantem.

```bash
sudo reboot
# reconectar (~1-2 min):
ssh ivana-moodle-aws
docker ps -a --format '{{.Names}} {{.Status}}'   # moodle: Exited (restart=no) - nada rodando
# recarregar as variaveis da sessao:
V2=/home/admin/moodle-v2; CFG=/home/admin/devcontainer; IMAGE=leodg/moodle-academy:4.5.15-php8.3
set -a; source $CFG/env/prod.env; set +a; source $V2/last-switch.env
```

**C4. Subir o moodle-v2** (codigo 4.5.15, portas 80/443, cron ligado)

```bash
docker run -d --name moodle-v2 --restart unless-stopped \
  -p 80:80 -p 443:443 \
  -e ENVIRONMENT=production -e CRON_ENABLED=true \
  -e MOODLE_URL -e MOODLE_DBTYPE -e MOODLE_DBHOST -e MOODLE_DBNAME -e MOODLE_DATA \
  -e MOODLE_DBPFX=mdl_ -e MOODLE_DBCOLL=utf8mb4_bin -e PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
  --env-file $CFG/secrets/db.env \
  -v $CFG/certs/server.crt:/run/secrets/ssl_cert:ro \
  -v $CFG/certs/server.key:/run/secrets/ssl_key:ro \
  -v $V2/code:/var/www/html \
  -v /home/admin/moodledata:/var/www/moodledata \
  $IMAGE

docker logs -f moodle-v2      # Ctrl+C quando aparecer "resuming normal operations"
```

**C5. Upgrade do Moodle 4.5.1+ -> 4.5.15**

```bash
docker exec -u www-data moodle-v2 php admin/cli/upgrade.php --non-interactive
docker exec -u www-data moodle-v2 php admin/cli/purge_caches.php
```

Esperado: lista de plugins atualizados e `Command line upgrade from 4.5.1+ ... to 4.5.15 completed successfully`.
**Se falhar: ir para R com restauracao do banco.**

**C6. Liberar o site**

```bash
docker exec -u www-data moodle-v2 php admin/cli/maintenance.php --disable
curl -s -o /dev/null -w '%{http_code}\n' https://ivana.academy/login/index.php   # 200
```

---

## D. Testar

```bash
docker exec moodle-v2 php -r 'echo PHP_VERSION, PHP_EOL;'                       # 8.3.36+
docker exec -u www-data moodle-v2 php admin/cli/checks.php                      # sem CRITICAL
docker exec moodle-v2 crontab -l                                                # * * * * *
sleep 70; docker exec moodle-v2 tail -5 /var/log/moodle-cron.log                # cron rodando
free -m
```

No navegador:

- [ ] Login de aluno e de admin
- [ ] Curso com formato Tiles e com Onetopic
- [ ] Certificado (customcert) gera PDF
- [ ] Attendance abre; Configurable Reports executa um relatorio
- [ ] Upload de arquivo numa atividade
- [ ] Administracao > Notificacoes: nada pendente; Servidor > Ambiente: PHP 8.3 OK
- [ ] "Esqueci a senha" envia e-mail

Tudo certo: o v1 fica parado (`docker ps -a` mostra `moodle` Exited) por alguns dias
como volta rapida. Depois: `docker rm moodle`.

---

## R. Voltar para o v1

Com upgrade ja rodado (C5 em diante) e **obrigatorio** restaurar o banco: o codigo
4.5.1+ do v1 recusa um banco 4.5.15. Perde o que foi gravado depois do dump.

```bash
source $V2/last-switch.env; echo $DUMP
docker rm -f moodle-v2

# so se o C5 rodou (o moodledata normalmente NAO precisa ser restaurado:
# o upgrade.php muda o banco; o tar $MDATA e garantia extra):
eval "$(grep -E '^(DB_HOST|DB_USER|DB_PASS)=' /backups/backup.sh)"
zcat $DUMP | mysql -h "$DB_HOST" -P 3306 -u "$DB_USER" -p"$DB_PASS" moodle

docker update --restart=always moodle
docker start moodle
docker exec -u www-data moodle php admin/cli/maintenance.php --disable
docker exec -u www-data moodle php admin/cli/purge_caches.php
curl -s -o /dev/null -w '%{http_code}\n' https://ivana.academy/login/index.php   # 200
```

---

## E. Atualizar o codigo com o moodle-v2 no ar

### E1. Automatico (merge de PR na `main`)

O job `prepare-vps` roda `prepare.sh` (pull da imagem) e depois `deploy-code.sh`, que:

- **aborta** se o checkout tem alteracao manual (lista os arquivos);
- faz checkout do commit novo em `/home/admin/moodle-v2/code`;
- **reinicia o `moodle-v2` so se algum `.php` mudou** (opcache sem revalidacao, ~10s fora);
- `purge_caches` (nova revisao de JS/CSS) e confere o login publico;
- se algum `version.php` mudou (plugin novo ou atualizacao): **nao roda `upgrade.php`** -
  o admin conclui em **Administracao do site > Notificacoes** (o job avisa com um warning).
  Para atualizacao grande (core), preferir E2 com dump antes.

Nao editar arquivos a mao em `/home/admin/moodle-v2/code`: corrigir sempre por PR.

### E2. Manual, com dump (atualizacao de core / grande)

```bash
V2=/home/admin/moodle-v2
docker exec -u www-data moodle-v2 php admin/cli/maintenance.php --enable
# dump do banco: igual ao C2
git -C $V2/code fetch origin
git -C $V2/code checkout --detach origin/main
docker restart moodle-v2
docker exec -u www-data moodle-v2 php admin/cli/upgrade.php --non-interactive
docker exec -u www-data moodle-v2 php admin/cli/purge_caches.php
docker exec -u www-data moodle-v2 php admin/cli/maintenance.php --disable
```
