# Checklist — Interoperabilidade MVP 1

Status geral do que já foi implementado e o que falta para o MVP 1 funcionar de ponta a ponta.

**Total: ~49 feitos, ~14 faltando.**

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
- [x] WebhookController + PartnerHealthController
- [x] ValidateWebhookSignature middleware
- [x] IntegrationServiceProvider registrado
- [x] config/integrations.php
- [x] Canal de log "integration" (config/logging.php)
- [x] Scheduler (cron sync 15min + retry 5min)
- [x] routes/api.php (webhooks + health)
- [x] Enum EXAM_RESULT_RECEIVED no NotificationType

**Fonte:** MVP1.md, ResilienciaOperacional.md, Arquitetura.md

---

## 3. Frontend — Páginas do Médico (Design)

- [x] Hub de Integrações (`/doctor/integrations`) — design completo
- [x] Gerenciar Parceiros (`/doctor/integrations/partners`) — design completo
- [x] Conectar Parceiro (`/doctor/integrations/connect`) — wizard 4 steps + tela de sucesso + estado de erro
- [x] Sidebar com item "Integrações" e submenu
- [x] Wayfinder gerado para rotas de integrations
- [x] Componente ComingSoonOverlay

**Fonte:** EstruturaPaginasInteroperabilidade.md

---

## 4. Frontend — Integração com dados reais

- [x] DoctorIntegrationsController passando dados reais (props) para Hub (contadores, última sync, erros)
- [x] DoctorIntegrationsController passando dados reais (props) para Partners (lista de parceiros + stats + eventos)
- [x] POST do wizard Connect criando parceiro no banco (partner_integrations + integration_credentials)
- [x] StorePartnerIntegrationRequest (FormRequest com validação)
- [x] Rota `/doctor/integrations/:partnerId` — página de detalhe individual do parceiro (Show.vue)
- [ ] Botão "Atualizar resultados" na tela de exames da consulta/prontuário (chama sync sob demanda)
- [ ] Exames com badge de origem ("Recebido do Lab Hermes") no prontuário do médico
- [x] Botão "Sincronizar agora" funcional na página Partners

**Fonte:** EstruturaPaginasInteroperabilidade.md, MVP1.md

---

## 5. Frontend — Adaptações para o Paciente

- [ ] Adaptar componente de exame existente (badge de origem, status "Aguardando resultado")
- [ ] Seção de consentimento na área de Configurações do paciente ("Compartilhamento de dados")
- [x] Notificações de resultado recebido (backend implementado via ProcessExamResult listener)

**Fonte:** EstruturaPaginasInteroperabilidade.md

---

## 6. Segurança da API Pública

- [x] HMAC webhook validation (ValidateWebhookSignature middleware)
- [ ] OAuth2 Client Credentials — endpoint de token para parceiros
- [ ] Middleware de scopes (lab:read, lab:write, pharmacy:read, etc.)
- [ ] Rate limiting por tipo de parceiro
- [ ] Consent enforcement — verificar consentimento do paciente antes de retornar dados
- [ ] Auditoria de acesso externo (log de toda chamada de parceiro)

**Fonte:** SegurancaAPIPublica.md

---

## 7. Resiliência Operacional

- [x] Circuit breaker (Redis) — código implementado
- [x] Retry com backoff exponencial — código implementado
- [x] Idempotência (webhook + outbound) — código implementado
- [ ] Testar circuit breaker com Redis real
- [ ] Dashboard de monitoramento (métricas para LGTM stack)

**Fonte:** ResilienciaOperacional.md

---

## 8. Regulatório

- [x] Campos CNS em patients e doctors (migration criada e executada)
- [ ] Coletar CNS nos formulários de cadastro do médico e paciente (frontend)
- [ ] Integração com RNDS (autenticação e-CNPJ + envio de Bundle FHIR)
- [ ] Consentimento `data_sharing_lab` e `rnds_submission` (UI + lógica)
- [ ] Registrar aplicação no Portal de Serviços DATASUS (homologação)

**Fonte:** PadroesRegulatorios.md

---

## 9. Endpoints da API pública

- [x] `POST /api/v1/public/webhooks/lab/{slug}` — receber resultado de exame (webhook)
- [x] `GET /api/v1/public/health/{slug}` — health check do parceiro
- [x] `GET /api/v1/public/lab/{slug}/orders` — laboratório consulta pedidos pendentes (LabOrderController)

**Fonte:** MVP1.md (seção 5.2 Rotas)

---

## 10. Testes

Documentação detalhada em: [Testes.md](./Testes.md)

- [ ] Seeders de teste (PartnerIntegrationSeeder, ExaminationIntegrationSeeder, IntegrationQueueSeeder)
- [ ] Testes unitários (Mappers FHIR, CircuitBreaker, IntegrationService, DTOs, Models)
- [ ] Testes de integração (Webhook, Health, LabOrders, Fluxo médico, Jobs, Listeners)
- [ ] Testes de resiliência (Circuit breaker, retry, idempotência)
- [ ] Testes de segurança (HMAC, OAuth2, rate limiting, consent)

**Fonte:** Testes.md

---

## Critérios de conclusão do MVP 1

Extraído de MVP1.md — todos devem estar completos para considerar o MVP 1 entregue:

- [ ] Médico solicita exame e lab recebe automaticamente (fluxo outbound completo)
- [ ] Resultado do lab aparece no prontuário automaticamente (fluxo inbound via webhook)
- [ ] Botão "Atualizar resultados" funciona (fluxo inbound via pull)
- [x] Logs visíveis no hub de integrações (admin vê eventos e erros)
- [ ] Retry automático para falhas (integration_queue processando retries)
- [ ] Consentimento registrado antes do envio (LGPD atendida)
- [ ] Dados enviados à RNDS após resultado (regulatório atendido)
- [ ] 1 laboratório piloto conectado (validação real)

---

## Resumo por categoria

| Categoria | Feito | Falta |
|---|---|---|
| Infraestrutura (schema + models) | 8 | 0 |
| Backend (integrations layer) | 16 | 0 |
| Frontend design | 6 | 0 |
| Frontend integração (dados reais) | 6 | 2 |
| Frontend paciente | 1 | 2 |
| Segurança API pública | 1 | 5 |
| Resiliência | 3 | 2 |
| Regulatório | 1 | 4 |
| Endpoints API pública | 3 | 0 |
| Testes | 0 | 5 |
| **Total** | **45** | **20** |

> Nota: Os 20 itens restantes se dividem em: 5 de testes (documentados em Testes.md), 5 de segurança, 4 de regulatório, 2 de resiliência, 2 de frontend médico e 2 de frontend paciente.

---

*Atualizado em: março/2026.*
