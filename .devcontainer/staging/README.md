# Staging local — cópia fiel da produção

Sobe na sua máquina um Moodle **igual à produção**: mesma imagem (PHP 8.3.36), MariaDB 11.4,
banco e moodledata do backup real, tema Moove com logo e imagens. O código é o deste checkout,
então dá para testar uma branch com os dados reais antes do merge.

**O que muda em relação à produção:** os e-mails de todos os usuários viram
`user<id>@staging.invalid`, e nada fala com o mundo.

| Camada | Proteção |
|---|---|
| `config.php` (forçado) | `noemailever`, `cron_enabled=false`, SMTP vazio, push (airnotifier) vazio, webservices off, GA `G-STAGING000`, PayPal sem conta |
| `anonymize.sql` (banco) | e-mails trocados; SMTP, push, tokens de webservice, segredos de plugins (S3/AWS, APIs), OAuth e gateways de pagamento zerados; todas as tarefas agendadas desligadas |
| container | `CRON_ENABLED=false` (sem crontab); porta só em `127.0.0.1` |
| `restore.sh` | **aborta** se sobrar algum e-mail que não seja `@staging.invalid` |

## 1. Baixar o backup (você, do seu terminal)

```bash
mkdir -p ~/ivana-staging && cd ~/ivana-staging
ssh ivana-moodle-aws 'ls -lh /backups | tail'          # escolher o dump e o tar
scp ivana-moodle-aws:/backups/<dump>.sql.gz .
scp ivana-moodle-aws:/backups/<moodledata>.tar.gz .      # opcional, mas sem ele o tema perde logo e imagens
```
O dump diário tem cerca de 10 MB. O moodledata completo só é gerado no dia 1 de cada mês; o
mais recente costuma ser o melhor. **São dados pessoais de clientes:** ficam só nesta máquina
(`data/` e `*.sql.gz` estão no `.gitignore`). Apague quando terminar.

## 2. Subir

```bash
.devcontainer/staging/restore.sh ~/ivana-staging/<dump>.sql.gz ~/ivana-staging/<moodledata>.tar.gz
# recomeçar do zero: STAGING_RESET=1 .devcontainer/staging/restore.sh ...
# outra porta:       STAGING_PORT=8643 .devcontainer/staging/restore.sh ...
```
Abrir https://localhost:8543 (certificado local autoassinado). O login usa as **mesmas senhas da
produção**: os hashes vêm no dump.

O `restore.sh` também roda `upgrade.php`, que instala os plugins novos da branch (ex.:
`local_seo`). O código é montado direto do checkout, então editar um arquivo aparece sem restart.

## 3. Desligar / apagar

```bash
cd .devcontainer/staging
docker compose down          # para, mantém banco e moodledata
docker compose down -v && rm -rf data ~/ivana-staging   # apaga tudo (dados de clientes)
```
