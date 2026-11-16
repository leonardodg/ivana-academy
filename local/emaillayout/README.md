# local_emaillayout — layout dos e-mails da Ivana Academy

Aplica o visual da marca (mesmo layout dos e-mails da loja: cabeçalho creme com logo,
conteúdo branco de 600px, rodapé bege com link para as preferências de notificação) a
**todo e-mail HTML** que o Moodle envia, sem alterar o core.

## Como funciona

Todo e-mail passa por `email_to_user()` (`lib/moodlelib.php`), que embrulha o corpo HTML no
template `core/email_html`. O Moodle procura esse template primeiro no tema ativo, então:

| Arquivo | Papel |
|---|---|
| `local/emaillayout/templates/email_html.mustache` | o layout (HTML com estilos inline) |
| `theme/ivana/templates/core/email_html.mustache` | override do core: só inclui `{{> local_emaillayout/email_html}}` |
| `theme/moove/templates/core/email_html.mustache` | o mesmo, para o rollback de tema manter o layout |
| `local/emaillayout/pix/logo_email.png` | logo (kit da marca, 363×91, exibido a 180px) servido em `/local/emaillayout/pix/` |
| `lang/{en,es,pt_br}` | textos do rodapé (es_ar herda de es) |
| `classes/hook_callbacks.php` + `db/hooks.php` | boas-vindas ao curso no idioma do aluno (ver abaixo) |

Limites (o contexto do template é fixo, definido pelo core):
- Só vale para quem recebe HTML (`mailformat = 1`; hoje 650 de 650 usuários). A versão texto não muda.
- O idioma do rodapé é o idioma corrente de quem dispara o envio. Nos fluxos da Ivana (login,
  matrícula, senha, cadastro) é o do próprio aluno; em tarefas em massa do cron pode cair no
  padrão do site (es_ar).
- Sem configurações no painel: cores/textos ficam no template e nas strings.

## Idioma das boas-vindas ao curso

As matrículas feitas pela loja chegam pelo webservice (`enrol_manual_enrol_users`). Por isso o
core monta a mensagem de boas-vindas (assunto, texto padrão, link do app, rodapé) no idioma da
conta do webservice, e não no do aluno. Quem envia as boas-vindas são callbacks do hook
`core_enrol\hook\after_user_enrolled` (enrol_manual, enrol_self). O plugin registra dois
callbacks no mesmo hook: um antes deles (prioridade 10000), que força o idioma do aluno
matriculado, e outro depois de todos (-10000), que devolve o idioma anterior.

## Levantamento dos e-mails (staging = cópia da produção de 12/11/2026)

Configuração: `messaging = 0` (mensagens entre usuários desligadas), `registerauth = email`
(autocadastro com confirmação), matrícula `manual,self,cohort,coursecompleted`, boas-vindas
de curso ligadas em enrol_self e enrol_manual, `enablebadges = 0`, `enableanalytics = 1`.
Todos os provedores de notificação têm o e-mail habilitado por padrão (nenhum travado,
exceto reportbuilderschedule, travado em ligado).

**Disparados de fato** (tabela de notificações, últimos 90 dias):

| E-mail | Origem | Volume 90d | Amostra no CLI |
|---|---|---|---|
| Novo acesso à conta | `moodle/newlogin` (tarefa adhoc ao logar) | 504 | `newlogin` |
| Boas-vindas ao curso | `moodle/enrolcoursewelcomemessage` (enrol self/manual) | 20 | `enrolwelcome` |
| Alunos em risco (Analytics) | `moodle/insights` | 17 | `richcontent` |
| Atualização disponível (admin) | `moodle/availableupdate` | 1 | `richcontent` |

**Diretos via `email_to_user()`** (não aparecem na tabela de notificações):

| E-mail | Origem | Amostra no CLI |
|---|---|---|
| Redefinição de senha | `login/forgot_password.php` | `resetpassword` |
| Confirmação de cadastro | `auth/email` (autocadastro) | `confirmation` |
| Troca de e-mail, senha alterada etc. | `user/edit.php`, `login/change_password.php` | (mesmo formato texto) |
| Certificado | `mod_customcert` (tarefa `email_certificate_task`) | `richcontent` |
| Relatório de presença | `mod_attendance` (tarefa `notify`) | `richcontent` |

**Habilitados mas sem envio recente**: fóruns (`mod_forum/posts` e `digests` — 37 fóruns com
inscrição forçada, 49 inscrições), tarefas, questionários, feedback, lição, BigBlueButton,
conclusão de curso, notas, privacidade de dados, backups.

> No staging o processador de e-mail está desligado (`anonymize.sql`) e `noemailever = true`.
> Conferir na produção: *Administração > Mensagens > Configurações de notificação*.

## Testar

```bash
# lista as amostras
php local/emaillayout/cli/send_test.php --list

# renderiza para arquivos, sem enviar (funciona no staging)
php local/emaillayout/cli/send_test.php --user=aluna.teste --preview=/tmp/emails

# envia de verdade para UM usuário (ex.: na produção, para a conta do admin)
php local/emaillayout/cli/send_test.php --user=admin --send
php local/emaillayout/cli/send_test.php --user=admin --send --only=resetpassword,newlogin
```

Também dá para usar *Administração do site > Servidor > E-mail > Configuração de e-mail de
saída > Testar configuração* e os fluxos reais (esqueci a senha, entrar de outro navegador,
matricular um usuário de teste num curso com autoinscrição).

## Deploy

Código: o deploy normal (merge em `main`) já purga os caches, e o layout passa a valer.
Para registrar o plugin, rodar na VPS `php admin/cli/upgrade.php --non-interactive`.
Assinatura dos e-mails do core ("Admin User" + "Contact site support", de
`generate_email_signoff()`): vem das configurações de suporte, não do layout. Desligadas em
*Administração > Servidor > Suporte*, ou na VPS:

```bash
php admin/cli/cfg.php --name=supportname --set=""
php admin/cli/cfg.php --name=supportavailability --set=0   # Desabilitado (some também do rodapé do site)
php admin/cli/purge_caches.php
```

Rollback: apagar os dois overrides `theme/*/templates/core/email_html.mustache` + purge.
