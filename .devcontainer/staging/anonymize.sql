-- =============================================================================
-- .devcontainer/staging/anonymize.sql - roda logo depois de importar o dump
-- =============================================================================
-- Copia fiel da producao, exceto:
--   * e-mails de todos os usuarios -> user<id>@staging.invalid (.invalid e
--     reservado pela RFC 2606: nenhum servidor aceita entrega)
--   * nada que fala com o mundo: SMTP, push, pagamentos, tokens, OAuth, cron
-- Nomes, cursos, notas e o tema ficam como na producao.
-- =============================================================================

-- E-mails ---------------------------------------------------------------------
UPDATE mdl_user SET email = CONCAT('user', id, '@staging.invalid') WHERE email <> '';
UPDATE mdl_config SET value = 'noreply@staging.invalid' WHERE name IN ('noreplyaddress', 'supportemail');

-- E-mail / push / webservices -------------------------------------------------
UPDATE mdl_config SET value = '' WHERE name IN ('smtphosts', 'smtpuser', 'smtppass', 'airnotifieraccesskey', 'airnotifierurl');
UPDATE mdl_config SET value = '0' WHERE name IN ('enablewebservices', 'enablemobilewebservice', 'cron_enabled');
UPDATE mdl_message_processors SET enabled = 0 WHERE name IN ('email', 'airnotifier');
DELETE FROM mdl_external_tokens;
DELETE FROM mdl_message_airnotifier_devices;
DELETE FROM mdl_user_devices;

-- Credenciais de plugins (S3, gateways, APIs) ---------------------------------
UPDATE mdl_config_plugins SET value = ''
 WHERE name REGEXP '(secret|token|password|passwd|apikey|api_key|accesskey|access_key|privatekey|private_key|clientsecret)'
   AND plugin <> 'local_seo';
UPDATE mdl_config_plugins SET value = 'staging@example.invalid' WHERE plugin = 'enrol_paypal' AND name = 'paypalbusiness';
UPDATE mdl_oauth2_issuer SET clientsecret = '', enabled = 0;
UPDATE mdl_payment_gateways SET enabled = 0;

-- Tarefas e sessoes -----------------------------------------------------------
UPDATE mdl_task_scheduled SET disabled = 1;
DELETE FROM mdl_task_adhoc;
DELETE FROM mdl_sessions;
