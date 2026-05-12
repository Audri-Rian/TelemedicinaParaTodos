# Feature Spec — Notificações pendentes (push + falhas de integração)

> Status: `approved`  
> Autor: Tech Lead SDD · Data: 2026-05-12 · Refino: 2026-05-12  
> Origem: `feature-interoperability-pendencias.md` (seção _Serviços incompletos_ + listener de integrações)

---

## Objetivo

Fechar as lacunas de notificação apontadas no checklist de interoperabilidade: (1) canal **push** hoje inoperante em `NotificationService`; (2) alerta **real** para operação quando `IntegrationFailed` é disparado, substituindo o `TODO` em `NotifyIntegrationFailure`.

## Motivação

- Usuários com preferência `push` não recebem nada (`sendPush` é placeholder).
- Falhas de integração laboratorial/FHIR ficam só em log estruturado; operação não é notificada de forma acionável, aumentando MTTR e risco de incidentes silenciosos.

---

## Contexto no código (baseline)

| Artefato                                  | Situação atual                                                         |
| ----------------------------------------- | ---------------------------------------------------------------------- |
| `NotificationService::sendByChannel`      | Encaminha `push` para `sendPush` vazio                                 |
| `NotificationPreference` + `shouldNotify` | Já suportam canal `push` conceitualmente                               |
| `NotifyIntegrationFailure`                | `ShouldQueue`; log em `integration`; `TODO` linha ~41                  |
| `IntegrationFailed`                       | Payload: `PartnerIntegration`, `IntegrationEvent`, `errorMessage`      |
| `User`                                    | Papéis explícitos: `doctor` / `patient` / `user` — **sem** `isAdmin()` |

---

## Regras de negócio

1. **Push:** quando `channels` incluir `push` e `shouldNotify(..., 'push')` for verdadeiro, o sistema deve tentar entregar push **ou** registrar falha controlada (sem quebrar criação da notificação in-app já persistida).
2. **Falha de integração:** para cada `IntegrationFailed` válido (partner e event preenchidos), acionar **e-mail** para a **lista fixa** configurada no servidor (`.env` / config). **Sem** notificação in-app para “staff” nesta entrega — não há tela Inertia de operador para leitura desses alertas.
3. **Anti-spam:** falhas repetidas do mesmo `partner_integration_id` + `event_type` devem poder ser limitadas (debounce ou throttle por janela), configurável — alinhado ao espírito de `telemedicine.notifications.debounce_ttl_seconds`.
4. **LGPD / dados sensíveis:** corpo de e-mail e payloads de push **não** devem incluir PHI completo; referenciar IDs, slug do parceiro e trecho curto da mensagem de erro sanitizada.

---

## Arquitetura proposta

```
[NotificationService::create] → … → sendByChannel('push')
        → PushNotificationSender (interface)
                → NullPushSender (push desligado por config)
                → WebPushSender (minishlink/web-push ou equivalente; VAPID)

[IntegrationFailed] → NotifyIntegrationFailure (job)
        → IntegrationFailureAlerter
                → Mail::queue para INTEGRATION_ALERT_EMAILS (lista fixa)
                → Log (já existe; manter)
```

Padrões reutilizados:

- `NotificationService` + `NotificationFactory` + `NotificationCreated` — in-app e e-mail já padronizados.
- Fila RabbitMQ — listener já enfileira; manter na fila `integrations` salvo decisão contrária.
- Config central — estender `config/telemedicine.php` e/ou `config/integrations.php` para destinatários e flags de push.

---

## Decisões de produto (refino 2026-05-12)

| #   | Tema                                 | Decisão                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| --- | ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Canal push                           | **Apenas Web Push** (navegador), com par de chaves **VAPID**; **sem FCM** nesta spec.                                                                                                                                                                                                                                                                                                                                                                    |
| 2   | Destinatários de falha de integração | **Lista fixa** em configuração servidor (ex.: `INTEGRATION_ALERT_EMAILS` CSV em `.env`).                                                                                                                                                                                                                                                                                                                                                                 |
| 3   | Alertas in-app para “admin”          | **Esclarecimento da pergunta 3:** a dúvida era se existe (ou virá logo) uma **tela no sistema** onde um usuário “operador” veria notificações internas (centro de notificações Inertia). No projeto atual, `User` só distingue médico/paciente/usuário comum — **não** há fluxo de operador. Por isso, para falhas de integração, o MVP fica **somente e-mail** para a lista fixa. **Notificação in-app para staff fica fora** até existir produto + UI. |
| 4   | `Documents.vue` / `Messages.vue`     | Entram como **dependências**: consomem a base (`NotificationService`, Web Push, permissões de browser) **depois** desta entrega; não bloqueiam o fechamento do backend descrito aqui, mas a implementação completa de UX nelas assume esta spec implementada.                                                                                                                                                                                            |

**Status:** aprovada para implementação (`approved`).

---

## Backend

### Push (`NotificationService`)

- Extrair envio para contrato `PushNotificationSender` com implementações:
    - `NullPushSender` — no-op + `Log::debug` quando `telemedicine.push.enabled` for falso.
    - `WebPushSender` — biblioteca compatível com RFC 8030 (ex.: `minishlink/web-push`), chaves VAPID em config; enviar para **todas** as subscriptions ativas do `user_id` da notificação.
- Persistência: tabela `push_subscriptions` (ou nome alinhado ao projeto) com `user_id`, `endpoint`, `public_key`, `auth_token`, `content_encoding`, `created_at`; invalidar linhas que retornarem `410 Gone` do push service.
- Registrar binding no `AppServiceProvider` (ou provider de domínio); namespace sugerido: `config('telemedicine.push')` (`enabled`, `vapid_public_key`, `vapid_private_key`, `vapid_subject` como `mailto:...` ou URL do remetente).
- `sendPush(Notification $notification)` monta payload mínimo: `type`, `title`, `body` (traduzível), `data` com `notification_id` para deep link quando o frontend tratar cliques.

### `NotifyIntegrationFailure`

- Substituir `TODO` por chamada a serviço dedicado, ex.: `IntegrationFailureAlerter::notify(IntegrationFailed $event)`.
- **E-mail:** Mailable enxuto (`IntegrationFailureMail`) com assunto `[Integração] Falha — {partner_name}`, corpo com `event_id`, `event_type`, horário, link interno para tela de integrações (se existir) ou apenas IDs.
- **Throttle:** `Cache::remember` ou `RateLimiter` por chave `integration_fail_alert:{partner_id}:{event_type}` com TTL configurável (ex.: 15 min) para não inundar inbox.
- Manter log `integration` existente como fonte de verdade para auditoria.

### In-app para falhas de integração

- **Fora do escopo** nesta spec (ver decisão #3). Evolução futura: `NotificationType::INTEGRATION_FAILED` + UI operador.

### Endpoints

- **Web Push:** `POST` + `DELETE` (autenticado) para registrar/remover subscription do usuário autenticado — rota em `routes/web/shared.php` ou `patient.php`/`doctor.php` conforme política (tipicamente **mesmo domínio** que o service worker).
- **Integração:** nenhum endpoint HTTP adicional obrigatório além do e-mail disparado pelo listener.

### Autorização

- Alertas administrativos não expõem dados a usuários finais; apenas envio para endereços/IDs configurados.

---

## Frontend

- **Esta spec (backend + contratos):** service worker + `Notification.requestPermission` + chamada ao `POST` de subscription podem ficar na **primeira tela** que precisar de push (ex.: dependência em `Messages.vue`) ou em settings — implementação rastreada como **dependência** (item 4).
- **Hub integrações:** não exige push; alertas de falha são e-mail para lista fixa.

## Banco de dados

### Migrations

- Tabela `push_subscriptions` (nome final alinhado ao projeto): FK `user_id` → `users`, índice por `user_id`, colunas exigidas pelo protocolo Web Push; opcional `user_agent` / `last_used_at` para suporte.

### Índices

| Tabela               | Coluna(s) | Motivo                                          |
| -------------------- | --------- | ----------------------------------------------- |
| `push_subscriptions` | `user_id` | listar endpoints por usuário em cada `sendPush` |

---

## Infraestrutura

- **Variáveis de ambiente:** `INTEGRATION_ALERT_EMAILS` (lista CSV); `PUSH_ENABLED` ou equivalente; `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` (mailto: ou URL).
- **Fila:** manter job em `integrations`; alertas síncronos de e-mail podem usar `Mail::queue` na mesma fila ou `default` — documentar escolha para evitar bloqueio.

---

## Observabilidade

| O que logar                     | Nível              | Contexto                                |
| ------------------------------- | ------------------ | --------------------------------------- |
| Push enviado / ignorado / falha | `info` / `warning` | `user_id`, `notification_id`, driver    |
| Alerta de integração disparado  | `info`             | `partner_id`, `event_id`, canais usados |
| Throttle aplicado               | `debug`            | chave de rate limit                     |

---

## Segurança

- Sanitizar `errorMessage` antes de e-mail (comprimento máximo, strip tags).
- Não logar segredos de OAuth/credenciais do evento.
- Validar que lista de e-mails de alerta vem só de config servidor (nunca de input HTTP).

---

## Edge Cases

1. **Nenhum e-mail configurado** — apenas log + opcional `report()` para canal de monitoração; não lançar exceção.
2. **Push sem subscription do usuário** — noop idempotente; não marcar notificação como falha.
3. **Partner/event nulos** — comportamento atual (early return + warning) mantido.

---

## Riscos técnicos

| Risco                             | Prob. | Impacto | Mitigação                                                                     |
| --------------------------------- | ----- | ------- | ----------------------------------------------------------------------------- |
| Web Push (permissões, SW, Safari) | Média | Médio   | Testar Chrome/Edge/Firefox; documentar limitações Safari/iOS                  |
| Spam de e-mail em incidente       | Média | Médio   | Throttle + digest futuro                                                      |
| “Admin” indefinido no modelo User | Alta  | Médio   | **Resolvido para MVP:** lista fixa em env; evolução role-based fora do escopo |

---

## Plano de implementação (ordenado)

1. Config + `.env.example`: `INTEGRATION_ALERT_EMAILS`, VAPID, `telemedicine.push.*`.
2. Migration `push_subscriptions` + model + policy (usuário só CRUD nas próprias linhas).
3. `IntegrationFailureAlerter` + `IntegrationFailureMail` + throttle + testes.
4. Wiring em `NotifyIntegrationFailure` removendo `TODO`.
5. Contrato `PushNotificationSender` + `NullPushSender` + `WebPushSender` + binding.
6. Rotas + FormRequest para registrar/remover subscription; testes feature.
7. Testes: `IntegrationFailed` + `Mail::fake`; push com `WebPushSender` mockado ou biblioteca em teste de integração controlado.
8. Dependências de frontend (`Documents.vue`, `Messages.vue`) após API estável.
9. Atualizar `feature-interoperability-pendencias.md` quando validado no código.

---

## Checklist (qualidade)

### Backend

- [ ] `NotifyIntegrationFailure` sem `TODO` — caminho feliz e throttled cobertos por testes
- [ ] `sendPush` deixa de ser vazio ou delega explicitamente a driver documentado
- [ ] Nenhum dado sensível em e-mail/push
- [ ] `php artisan test` passando

### Documentação

- [ ] `.env.example` documenta variáveis novas
- [ ] Pendências raiz atualizadas após merge

---

## Relação com outras pendências do mesmo arquivo

| Item checklist                          | Relação                                                                         |
| --------------------------------------- | ------------------------------------------------------------------------------- |
| `NotificationService.php:168` sendPush  | **Escopo principal** desta spec                                                 |
| `NotifyIntegrationFailure.php:41`       | **Escopo principal** desta spec                                                 |
| `BaseAdapter.php` OAuth refresh         | **Fora** — não é notificação ao usuário                                         |
| `Documents.vue` notificação ao paciente | **Dependência** — após backend; reutiliza `NotificationService`                 |
| `Messages.vue` notificações desktop     | **Dependência** — permissão browser + subscription + UX após endpoints Web Push |
