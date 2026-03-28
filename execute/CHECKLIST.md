# Checklist — Interoperabilidade MVP 1

Status geral do que já foi implementado e o que falta para o MVP 1 funcionar de ponta a ponta.

**Total: 71 feitos, 8 faltando.**

---

## 1. Infraestrutura (Schema + Models)

- [x] 6 tabelas novas (partner_integrations, credentials, events, webhooks, queue, fhir_mappings)
- [x] Alterações em patients (cns, cpf, mother_name)
- [x] Alterações em doctors (cns, cbo)
- [x] Alterações em examinations (partner_id, external_id, source)
- [x] Alterações em prescriptions (signature, verification_code)
- [x] 6 Models Eloquent novos
- [x] 4 Models atualizados (Patient, Doctor, Examination, Prescription)
- [x] Rodar `php artisan migrate`
- [x] Indexes de performance (examinations.source, events(status,created_at), partner(status,last_sync_at))

**Fonte:** SchemaIntegracoes.md

---

## 2. Camada de Integração (app/Integrations/)

- [x] Contracts (IntegrationInterface, LabInterface, PharmacyInterface)
- [x] DTOs (ExamOrder, ExamResult, PrescriptionValidation, FhirBundle)
- [x] Events (ExamOrderSent, ExamResultReceived, IntegrationFailed)
- [x] Adapters (BaseAdapter, FhirLabAdapter, LabAdapterStub)
- [x] Mappers FHIR (Patient, ExamOrder, ExamResult, Diagnosis, Prescription)
- [x] Services (IntegrationService, CircuitBreaker)
- [x] Listeners (SendExamOrderToLab, ProcessExamResult, NotifyIntegrationFailure)
- [x] Jobs (SyncExamResults, ProcessIntegrationQueue)
- [x] WebhookController + PartnerHealthController + LabOrderController
- [x] OAuthTokenController (endpoint de token OAuth2 Client Credentials)
- [x] IntegrationMetricsController (métricas operacionais + estado circuit breakers)
- [x] ValidateWebhookSignature middleware
- [x] AuthenticatePartner middleware (OAuth2 Bearer validation)
- [x] CheckPartnerScope middleware (verificação de scopes)
- [x] RateLimitPartner middleware (rate limiting por tipo de parceiro)
- [x] EnforcePatientConsent middleware (LGPD consent check)
- [x] AuditExternalAccess middleware (log de toda chamada externa)
- [x] IntegrationServiceProvider registrado
- [x] config/integrations.php
- [x] Canal de log "integration" (config/logging.php)
- [x] Scheduler (cron sync 15min + retry 5min)
- [x] Enum EXAM_RESULT_RECEIVED no NotificationType

**Fonte:** MVP1.md, ResilienciaOperacional.md, Arquitetura.md, SegurancaAPIPublica.md

---

## 3. Frontend — Páginas do Médico (Design)

- [x] Hub de Integrações (`/doctor/integrations`) — design completo com dados reais
- [x] Gerenciar Parceiros (`/doctor/integrations/partners`) — dados reais + sync funcional
- [x] Conectar Parceiro (`/doctor/integrations/connect`) — wizard com POST real + validação
- [x] Detalhe do Parceiro (`/doctor/integrations/:id`) — Show.vue com log de eventos
- [x] Sidebar com item "Integrações" e submenu
- [x] Wayfinder gerado para rotas de integrations
- [x] Componente ComingSoonOverlay (com aria-hidden)

**Fonte:** EstruturaPaginasInteroperabilidade.md

---

## 4. Frontend — Integração com dados reais

- [x] DoctorIntegrationsController passando dados reais para Hub (contadores, última sync, erros)
- [x] DoctorIntegrationsController passando dados reais para Partners (withCount, N+1 resolvido)
- [x] POST do wizard Connect criando parceiro no banco (com FormRequest validado)
- [x] Rota de detalhe individual do parceiro (Show.vue com stats consolidados)
- [x] Botão "Atualizar resultados" na tela de exames do prontuário
- [x] Exames com badge de origem ("Recebido do Lab Hermes") no prontuário do médico
- [x] Badge "Aguardando resultado" para exames de integração pendentes
- [x] Botão "Sincronizar agora" funcional na página Partners (com error handling)

**Fonte:** EstruturaPaginasInteroperabilidade.md, MVP1.md

---

## 5. Frontend — Adaptações para o Paciente

- [x] Adaptar componente de exame existente (badge de origem, status "Aguardando resultado")
- [x] Novos tipos de consentimento (data_sharing_lab, rnds_submission) no Consent model e controller
- [x] Notificações de resultado recebido (backend implementado via ProcessExamResult listener)

**Fonte:** EstruturaPaginasInteroperabilidade.md

---

## 6. Segurança da API Pública

- [x] HMAC webhook validation (ValidateWebhookSignature middleware)
- [x] OAuth2 Client Credentials — endpoint de token (OAuthTokenController)
- [x] Middleware de autenticação de parceiro (AuthenticatePartner — Bearer token)
- [x] Middleware de scopes (CheckPartnerScope — lab:read, lab:write, etc.)
- [x] Rate limiting por tipo de parceiro (RateLimitPartner)
- [x] Consent enforcement — verificar consentimento do paciente (EnforcePatientConsent)
- [x] Auditoria de acesso externo (AuditExternalAccess — log de toda chamada)
- [x] Middlewares registrados em bootstrap/app.php
- [x] Rotas API protegidas com stack de middlewares (auth → rate → audit → scope → consent)

**Fonte:** SegurancaAPIPublica.md

---

## 7. Resiliência Operacional

- [x] Circuit breaker (Redis) — código implementado
- [x] Retry com backoff exponencial — código implementado
- [x] Idempotência (webhook + outbound) — código implementado
- [x] Endpoint de métricas operacionais (IntegrationMetricsController) com estado dos circuit breakers
- [ ] Testar circuit breaker com Redis real (documentado em Testes.md)

**Fonte:** ResilienciaOperacional.md

---

## 8. Regulatório

- [x] Campos CNS em patients e doctors (migration criada e executada)
- [x] CNS no formulário de registro do médico (RegisterDoctor.vue + composable)
- [x] CNS e CPF no formulário de perfil do paciente (Profile.vue)
- [x] Validação de CNS e CPF nos FormRequests (DoctorRegistration, PatientRegistration, ProfileUpdate)
- [x] Tipos de consentimento data_sharing_lab e rnds_submission (Consent model + controller)
- [ ] Integração com RNDS (autenticação e-CNPJ + envio de Bundle FHIR)
- [ ] Registrar aplicação no Portal de Serviços DATASUS (homologação)

**Fonte:** PadroesRegulatorios.md

---

## 9. Endpoints da API pública

- [x] `POST /api/v1/public/oauth/token` — emissão de token OAuth2 Client Credentials
- [x] `POST /api/v1/public/webhooks/lab/{slug}` — receber resultado de exame (webhook + HMAC)
- [x] `GET /api/v1/public/lab/{slug}/orders` — laboratório consulta pedidos pendentes (autenticado + scopes)
- [x] `GET /api/v1/public/health/{slug}` — health check do parceiro (autenticado)

**Fonte:** MVP1.md (seção 5.2 Rotas)

---

## 10. Testes

Documentação detalhada em: [Testes.md](./Testes.md)

- [ ] Seeders de teste (PartnerIntegrationSeeder, ExaminationIntegrationSeeder, IntegrationQueueSeeder)
- [ ] Testes unitários (Mappers FHIR, CircuitBreaker, IntegrationService, DTOs, Models)
- [ ] Testes de integração (Webhook, Health, LabOrders, OAuth2, Fluxo médico, Jobs, Listeners)
- [ ] Testes de resiliência (Circuit breaker com Redis, retry, idempotência)
- [ ] Testes de segurança (HMAC, OAuth2, scopes, rate limiting, consent enforcement)

**Fonte:** Testes.md

---

## Critérios de conclusão do MVP 1

Extraído de MVP1.md — todos devem estar completos para considerar o MVP 1 entregue:

- [ ] Médico solicita exame e lab recebe automaticamente (fluxo outbound completo)
- [ ] Resultado do lab aparece no prontuário automaticamente (fluxo inbound via webhook)
- [x] Botão "Atualizar resultados" funciona (fluxo inbound via pull)
- [x] Logs visíveis no hub de integrações (admin vê eventos e erros)
- [ ] Retry automático para falhas (integration_queue processando retries)
- [x] Consentimento registrado antes do envio (LGPD atendida — tipos criados)
- [ ] Dados enviados à RNDS após resultado (regulatório atendido — requer e-CNPJ)
- [ ] 1 laboratório piloto conectado (validação real)

---

## Resumo por categoria

| Categoria | Feito | Falta |
|---|---|---|
| Infraestrutura (schema + models) | 9 | 0 |
| Backend (integrations layer) | 22 | 0 |
| Frontend design | 7 | 0 |
| Frontend integração (dados reais) | 8 | 0 |
| Frontend paciente | 3 | 0 |
| Segurança API pública | 9 | 0 |
| Resiliência | 4 | 1 |
| Regulatório | 5 | 2 |
| Endpoints API pública | 4 | 0 |
| Testes | 0 | 5 |
| **Total** | **71** | **8** |

> Nota: Os 8 itens restantes são: 5 de testes (documentados em Testes.md), 2 de regulatório (RNDS + DATASUS — dependem de e-CNPJ e homologação externa) e 1 de resiliência (teste CB com Redis — documentado em Testes.md).

---

*Atualizado em: março/2026.*
