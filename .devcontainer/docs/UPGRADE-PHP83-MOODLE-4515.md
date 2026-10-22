# Runbook - PHP 8.1 -> 8.3, Moodle 4.5.1+ -> 4.5.15, deploy v2

Producao: `ssh ivana-moodle-aws` (EC2, Debian 12, ~1GB RAM + 4GB swap), atras da Cloudflare.

## Por que

- **PHP2Shell** (CVE-2026-109618 / CVE-2026-109619, RCE sem login, CVSS 9.2, 21/10/2026).
  O v1 roda PHP **8.1.34** com mod_php: a 8.1 esta fora de suporte e **nao recebe correcao**.
  Corrigido em 8.2.35 / **8.3.36** / 8.4.27 / 8.5.12. A imagem `moodlehq/moodle-php-apache:8.3`
  de 22/10/2026 ja traz 8.3.36.
- **PHP 8.3** e nao 8.2: a 8.2 so recebe correcoes de seguranca ate 31/12/2026; a 8.3 ate
  dez/2027, mesmo prazo do Moodle 4.5 LTS (seguranca ate 04/10/2027). Tambem e o minimo do
  Moodle 5.3 LTS (proximo passo).
- **Moodle 4.5.15**: 14 releases de correcoes (varias de seguranca) desde o 4.5.1+ de jan/2025.
  4.5.16 sai em 07/12/2026 e sera uma atualizacao menor no mesmo fluxo.

## Desenho (v1 intocado, troca reversivel)

| | v1 (atual) | v2 (novo) |
|---|---|---|
| Container | `moodle` | `moodle-v2` |
| Imagem | `leodg/moodle-academy:4.5.1` (PHP 8.1.34) | `leodg/moodle-academy:4.5.15-php8.3` (PHP 8.3.36) |
| Codigo | `/home/admin/public_html` (4.5.1+) | `/home/admin/moodle-v2/code` (git, 4.5.15) |
| config.php | proprio | copia do v1 |
| moodledata | `/home/admin/moodledata` | o mesmo |
| Banco | MariaDB 11.4 externo | o mesmo |
| Cron | `*/5` | `* * * * *` (recomendado pelo Moodle) |
| `upload_progress` | 0 (aplicado a mao) | 0 (na imagem: `php/conf.d/99-security.ini`) |

A VPS nao tem `docker compose`; os scripts usam `docker run` com as mesmas opcoes de
`compose/prod-v2.yml` (funcao `v2_up` em `bin/prod-v2/common.sh`).

## Passo a passo

### 0. Antes (sem parar o site)

1. PR desta branch -> `develop` -> `release/production-v2`.
   O push em `release/production-v2` roda `production-v2.yml`: build + validacao + **prepare.sh**
   na VPS. A troca **nao** e automatica.
   Sem o workflow, na VPS: `/home/admin/devcontainer/bin/prod-v2/prepare.sh <git-ref> 4.5.15-php8.3`
2. Conferir a saida do prepare: PHP 8.3.36, `upload_progress=0`, Moodle 4.5.15, cert de origem.
3. Disco: o prepare exige 3G livres. Se faltar: `docker image prune` (so remove imagens `<none>`;
   a `4.5.1` do v1 continua).
4. Avisar os alunos da janela (~15 min).

### 1. Janela de manutencao

```bash
ssh ivana-moodle-aws
/home/admin/devcontainer/bin/prod-v2/switch-to-v2.sh
```

Faz: manutencao ON -> dump `/backups/pre-v2-<data>.sql.gz` -> para o v1 (`restart=no`) ->
sobe o v2 em 80/443 -> `upgrade.php` -> purge caches -> manutencao OFF -> testes.

### 2. Testes depois da troca

- [ ] `https://ivana.academy` abre, login de aluno e de admin
- [ ] Curso com formato Tiles e com Onetopic abrem
- [ ] Certificado (customcert) gera PDF
- [ ] Attendance abre; Configurable Reports executa um relatorio
- [ ] Upload de arquivo em uma atividade (repository_s3bucket se usado)
- [ ] `Administracao > Notificacoes`: sem upgrade pendente; `Servidor > Ambiente`: PHP 8.3 OK
- [ ] `docker exec -u www-data moodle-v2 php admin/cli/checks.php` sem CRITICAL
- [ ] Cron: `docker exec moodle-v2 tail /var/log/moodle-cron.log` rodando a cada minuto
- [ ] E-mail de teste (esqueci a senha)

### 3. Voltar para o v1 (se precisar)

```bash
/home/admin/devcontainer/bin/prod-v2/rollback-to-v1.sh --restore-db
```

`--restore-db` e obrigatorio se o `upgrade.php` rodou: o codigo 4.5.1+ recusa um banco 4.5.15.
**Perde o que foi gravado no banco depois do dump** - por isso decidir rapido (testes do passo 2
logo apos a troca). Sem `--restore-db` so troca os containers (ex.: v2 nem subiu).

### 4. Depois de alguns dias estavel

- Remover o container v1: `docker rm moodle` (a imagem `4.5.1` pode ficar como historico).
- `public_html` pode virar backup compactado.

## Pendencias encontradas na analise (fora deste upgrade)

- **Certificado de origem vencido em 02/05/2026** (`~/local/letsencrypt`): o certbot mensal usa
  desafio DNS no Route53, mas o DNS esta na Cloudflare. Funciona hoje so porque a Cloudflare
  esta em modo SSL "Full" (nao strict). Sugestao: certificado Origin CA da Cloudflare + modo
  "Full (strict)".
- **Senha do Gmail** `academy.ivana@gmail.com` ficou exposta em `sendmail/sendmail.php`
  (publico ate 22/10/2026, removido para `~/removed-from-webroot-20261022/`): trocar a senha de app.
- `moodledata` so tem backup mensal (dia 1); banco e diario.
- Cron roda como root dentro do container (arquivos em moodledata podem ficar com dono root).
- Plugins com `supported` antigo: `configurable_reports` (4.0-4.1), `local_aws` (ate 4.3),
  `repository_s3bucket` (ate 4.4), `mod_attendance`/`enrol_coursecompleted` (4.4).
  Funcionam no 4.5, mas precisam de versao nova antes do Moodle 5.3.
- Arquivos gerados versionados no repo: `cache/core_component.php`, `muc/config.php`.
