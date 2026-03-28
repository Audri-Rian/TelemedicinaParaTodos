# Testes — Interoperabilidade MVP 1

Plano de testes para validar a camada de interoperabilidade antes de ir para produção.
Inclui testes unitários, de integração e seeders/fixtures necessários.

**Fonte:** MVP1.md, ResilienciaOperacional.md, SegurancaAPIPublica.md

---

## 1. Seeders e Dados de Teste

Antes de rodar qualquer teste (e para facilitar o desenvolvimento), precisamos de dados realistas no banco.

### 1.1 PartnerIntegrationSeeder

Cria parceiros de teste com credenciais:

| Parceiro | Slug | Tipo | Status | Auth |
|---|---|---|---|---|
| Lab Hermes (stub) | `hermes-pardini` | laboratory | active | api_key |
| Lab Fleury (stub) | `fleury` | laboratory | pending | oauth2 |
| Farmácia Exemplo | `farmacia-exemplo` | pharmacy | inactive | bearer |

Para cada parceiro ativo, criar também:
- `integration_credentials` com dados fictícios (client_id, secret, token)
- 1 `integration_webhook` registrado apontando para `http://localhost`
- 3-5 `integration_events` (mix de success, failed, pending)
- 1-2 `fhir_resource_mappings` (Patient, DiagnosticReport)

### 1.2 ExaminationIntegrationSeeder

Cria exames vinculados a parceiros para testar o fluxo de resultado:

- 3 exames com `source = 'integration'`, `partner_integration_id` preenchido, `external_id` presente
- 2 exames com `source = 'integration'`, status aguardando resultado (`results = null`)
- 2 exames normais (`source = 'internal'`) para contraste

### 1.3 IntegrationQueueSeeder

Popula a fila de retry:

- 2 itens `pending` (próxima tentativa no passado — devem ser processados)
- 1 item `pending` (próxima tentativa no futuro — não deve ser processado ainda)
- 1 item `failed` (max_attempts atingido)

### Como rodar

```bash
php artisan db:seed --class=PartnerIntegrationSeeder
php artisan db:seed --class=ExaminationIntegrationSeeder
php artisan db:seed --class=IntegrationQueueSeeder

# Ou todos de uma vez:
php artisan db:seed --class=IntegrationTestDataSeeder
```

---

## 2. Testes Unitários

### 2.1 Mappers FHIR

**Arquivo:** `tests/Unit/Integrations/Mappers/`

| Teste | O que valida |
|---|---|
| `PatientFhirMapperTest` | Patient → FHIR Patient (CNS como identifier, nome, data nasc.) |
| `ExamOrderFhirMapperTest` | ExamOrderDto → FHIR ServiceRequest (patient ref, practitioner ref, code) |
| `ExamResultFhirMapperTest` | FHIR DiagnosticReport → ExamResultDto (resultados, status, external_id) |
| `DiagnosisFhirMapperTest` | Diagnosis → FHIR Condition (code, clinical status) |
| `PrescriptionFhirMapperTest` | Prescription → FHIR MedicationRequest (medication, dosage) |

**Cenários por mapper:**
- Conversão completa (todos os campos preenchidos)
- Conversão com campos opcionais nulos
- Payload FHIR malformado → exceção clara
- Encoding UTF-8 com caracteres especiais (nomes brasileiros com acentos)

### 2.2 DTOs

**Arquivo:** `tests/Unit/Integrations/DTOs/`

| Teste | O que valida |
|---|---|
| `ExamOrderDtoTest` | `fromExamination()` extrai campos corretos, fallback de `requested_at` |
| `ExamResultDtoTest` | Construção com todos os campos, results como array tipado |

### 2.3 CircuitBreaker

**Arquivo:** `tests/Unit/Integrations/Services/CircuitBreakerTest.php`

| Cenário | Esperado |
|---|---|
| Estado inicial | closed |
| N falhas < threshold | permanece closed |
| N falhas = threshold | abre (open) |
| Estado open + cooling não passou | bloqueia chamada |
| Estado open + cooling passou | transita para half-open |
| Half-open + sucesso | fecha (closed) |
| Half-open + falha | reabre (open) |
| Reset manual | volta para closed |

**Dependência:** Redis (usar `Redis::fake()` ou `Cache::fake()`)

### 2.4 IntegrationService

**Arquivo:** `tests/Unit/Integrations/Services/IntegrationServiceTest.php`

| Cenário | Esperado |
|---|---|
| `sendExamOrder` com lab ativo | chama adapter, cria event, retorna external_id |
| `sendExamOrder` sem lab ativo | não chama adapter, log debug |
| `sendExamOrder` com circuit breaker aberto | não chama adapter, enfileira retry |
| `syncExamResults` com resultados pendentes | chama fetchResult, atualiza examination |
| `syncExamResults` sem resultados | retorna 0 |
| `processQueue` com itens pendentes | processa e atualiza status |
| `processQueue` com item max_attempts | marca como failed |

### 2.5 Models

**Arquivo:** `tests/Unit/Models/`

| Teste | O que valida |
|---|---|
| `PartnerIntegrationTest` | Scopes (active, laboratories), hasCapability, isActive |
| `IntegrationCredentialTest` | Encryption de campos sensíveis, isTokenExpired |
| `IntegrationEventTest` | Scopes (successful, failed, outbound, inbound) |
| `IntegrationQueueItemTest` | shouldRetry, markAsProcessing, markAsFailed |

---

## 3. Testes de Integração (Feature)

### 3.1 Webhook Inbound

**Arquivo:** `tests/Feature/Integrations/WebhookControllerTest.php`

| Cenário | Esperado |
|---|---|
| POST válido com HMAC correto | 200, evento criado, examination atualizada |
| POST com HMAC inválido | 401 |
| POST com timestamp expirado | 401 |
| POST duplicado (idempotência) | 200, evento não duplicado |
| POST com payload sem DiagnosticReport | 422 |
| POST para parceiro inexistente | 404 |
| POST para parceiro inativo | 403 |

### 3.2 Health Check

**Arquivo:** `tests/Feature/Integrations/PartnerHealthControllerTest.php`

| Cenário | Esperado |
|---|---|
| GET parceiro ativo | 200, status info |
| GET parceiro inexistente | 404 |

### 3.3 Lab Orders (endpoint público)

**Arquivo:** `tests/Feature/Integrations/LabOrderControllerTest.php`

| Cenário | Esperado |
|---|---|
| GET pedidos pendentes (autenticado) | 200, lista de ServiceRequests |
| GET sem autenticação | 401 |
| GET parceiro sem scope lab:read | 403 |
| GET sem pedidos pendentes | 200, array vazio |

### 3.4 Fluxo do Médico (Frontend Controllers)

**Arquivo:** `tests/Feature/Doctor/IntegrationsControllerTest.php`

| Cenário | Esperado |
|---|---|
| GET /doctor/integrations (autenticado como médico) | 200, props com contadores reais |
| GET /doctor/integrations/partners | 200, props com lista de parceiros |
| POST /doctor/integrations/connect | redirect, parceiro criado no banco |
| POST /doctor/integrations/connect com dados inválidos | 422 |
| POST /doctor/integrations/:id/sync | 200, job disparado |
| GET sem autenticação | redirect login |

### 3.5 Jobs

**Arquivo:** `tests/Feature/Integrations/Jobs/`

| Teste | O que valida |
|---|---|
| `SyncExamResultsTest` | Job executa, chama syncExamResults para cada lab ativo |
| `ProcessIntegrationQueueTest` | Job executa, processa itens pendentes da fila |

### 3.6 Listeners

**Arquivo:** `tests/Feature/Integrations/Listeners/`

| Teste | O que valida |
|---|---|
| `SendExamOrderToLabTest` | ExaminationRequested → lab recebe pedido (com lab ativo) |
| `SendExamOrderToLabTest` | ExaminationRequested → ignora (sem lab ativo) |
| `ProcessExamResultTest` | ExamResultReceived → notificações criadas para médico e paciente |

---

## 4. Testes de Resiliência

**Arquivo:** `tests/Feature/Integrations/ResilienceTest.php`

| Cenário | Esperado |
|---|---|
| Lab retorna 500 repetidamente | Circuit breaker abre após threshold |
| Lab timeout | Retry enfileirado com backoff exponencial |
| Lab volta após circuit breaker abrir | Half-open → sucesso → closed |
| Webhook recebido durante circuit breaker open | Webhook processa normalmente (inbound não depende do CB) |
| Fila de retry com backoff | Delay entre tentativas segue fórmula exponencial |

---

## 5. Testes de Segurança

**Arquivo:** `tests/Feature/Integrations/SecurityTest.php`

| Cenário | Esperado |
|---|---|
| Webhook sem header de assinatura | 401 |
| Webhook com assinatura inválida | 401 |
| Webhook com timestamp replay (> 5 min) | 401 |
| Rate limit excedido | 429 |
| OAuth2 token expirado | 401 |
| Acesso a dados sem consentimento do paciente | 403 |
| Scope insuficiente | 403 |

---

## 6. Ordem de Execução Recomendada

1. **Criar seeders** (1.1, 1.2, 1.3) — desbloqueia desenvolvimento e testes manuais
2. **Testes unitários dos Mappers** (2.1) — validam a tradução FHIR, base de tudo
3. **Testes unitários do CircuitBreaker** (2.3) — componente crítico de resiliência
4. **Testes de webhook inbound** (3.1) — fluxo mais importante do MVP
5. **Testes do IntegrationService** (2.4) — orquestra tudo
6. **Testes dos Jobs e Listeners** (3.5, 3.6) — automação
7. **Testes de resiliência** (4) — garantem estabilidade
8. **Testes de segurança** (5) — pré-produção
9. **Testes do fluxo do médico** (3.4) — UI funcional

---

## 7. Ferramentas e Configuração

### PHPUnit

```xml
<!-- phpunit.xml — adicionar test suite -->
<testsuite name="Integrations">
    <directory>tests/Unit/Integrations</directory>
    <directory>tests/Feature/Integrations</directory>
</testsuite>
```

### Dependências de ambiente para testes

- **Redis:** necessário para CircuitBreaker (ou usar `Cache::fake()` nos unitários)
- **Database:** usar `RefreshDatabase` trait nos feature tests
- **Queue:** usar `Queue::fake()` para verificar dispatch de jobs sem executar

### Convenções

- Nomes de teste: `test_[cenário]_[resultado_esperado]` (ex: `test_webhook_with_invalid_hmac_returns_401`)
- Um assert principal por teste (SRP)
- Usar factories quando existirem, seeders para dados de contexto mais amplo

---

*Última atualização: março/2026*
