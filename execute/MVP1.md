# MVP 1 — Integração com Laboratório (Especificação Técnica)

Este documento responde às perguntas técnicas necessárias para implementar o MVP 1: integração com laboratório parceiro.

---

## 1. Qual protocolo o laboratório usa?

### Resposta: **FHIR R4**

O protocolo escolhido para comunicação com laboratórios é **FHIR R4 (Fast Healthcare Interoperability Resources, Release 4)**, pelos seguintes motivos:

| Fator | Justificativa |
|-------|-------------|
| **Padrão da RNDS** | A RNDS (barramento nacional de saúde) usa FHIR R4. Adotar o mesmo padrão evita tradução dupla |
| **Formato de dados clínicos** | FHIR tem recursos nativos para exames (ServiceRequest, DiagnosticReport, Observation) |
| **Ecossistema crescente** | Laboratórios modernos já estão se adaptando a FHIR; os que não estão, recebem adaptação via adapter |
| **Escalabilidade** | Mesmo protocolo para laboratório, farmácia e hospital — um único "idioma" interno |
| **Documentação e ferramentas** | Validadores, perfis brasileiros (BR Core), bibliotecas PHP disponíveis |

### Formato de comunicação

| Aspecto | Especificação |
|---------|-------------|
| **Protocolo de transporte** | HTTPS (TLS 1.2+) |
| **Formato de dados** | JSON (application/fhir+json) |
| **Versão FHIR** | R4 (4.0.1) |
| **Perfis brasileiros** | BR Core (quando disponíveis via RNDS) |
| **Autenticação** | OAuth2 Client Credentials (entre sistemas) |
| **Content-Type** | `application/fhir+json; charset=utf-8` |

### Laboratórios que não suportam FHIR

Para parceiros que usam protocolos legados (HL7 v2, CSV, SOAP, API proprietária), o **Adapter Pattern** resolve:

```
Nosso sistema (FHIR interno)
        │
        ▼
   LabAdapter (traduz FHIR → protocolo do lab)
        │
        ▼
   API do laboratório (HL7 v2, REST proprietário, etc.)
```

Cada laboratório com protocolo diferente tem seu adapter específico. O `IntegrationService` sempre fala FHIR; o adapter traduz.

---

## 2. Quem inicia? Modelo híbrido (Webhook + Sync)

### Resposta: **Modelo híbrido — Push (webhook) + Pull (sync sob demanda)**

Nem todo laboratório vai suportar webhooks, e nem toda situação permite esperar um push. O modelo híbrido combina os dois:

### 2.1 Fluxo de envio — Pedido de exame (nosso sistema → laboratório)

```
Médico solicita exame na consulta
         │
         ▼
   Sistema cria Examination (status: requested)
         │
         ▼
   Evento Laravel: ExamRequested
         │
         ▼
   IntegrationService identifica laboratório parceiro
         │
         ▼
   LabAdapter traduz para FHIR ServiceRequest
         │
         ▼
   POST /ServiceRequest → API do laboratório
         │
         ├── 200 OK → integration_events (status: success)
         │              Examination.external_id = resposta do lab
         │
         └── Falha → integration_queue (status: queued, retry agendado)
                      integration_events (status: failed)
```

**Quem inicia:** Nosso sistema (outbound push), acionado pelo evento `ExamRequested`.

---

### 2.2 Fluxo de recebimento — Resultado do exame (laboratório → nosso sistema)

**Caminho 1: Webhook (push do laboratório)**

```
Laboratório finaliza exame
         │
         ▼
   Lab envia POST para nosso webhook
   POST /api/v1/public/webhooks/lab/{partner_id}
         │
         ▼
   Middleware: valida HMAC signature + parceiro ativo
         │
         ▼
   Payload FHIR: DiagnosticReport + Observation[]
         │
         ▼
   LabAdapter traduz FHIR → modelo interno
         │
         ▼
   Examination atualizada (status: completed, results: [...])
   integration_events registrado
         │
         ▼
   Evento Laravel: ExamResultReceived
         │
         ▼
   Notificação para médico e paciente
```

**Caminho 2: Sync sob demanda (pull do nosso sistema)**

```
Admin ou sistema (cron/botão) aciona sync
         │
         ▼
   IntegrationService consulta laboratório
   GET /DiagnosticReport?based-on={service-request-id}
         │
         ▼
   Lab responde com DiagnosticReport (ou 404 se não pronto)
         │
         ├── Resultado disponível → mesmo fluxo do webhook (atualiza Examination)
         │
         └── Não disponível → integration_events (status: success, tipo: no_result_yet)
                              Próximo sync agendado
```

### 2.3 Botões de sync na interface

O sistema oferece **três formas de acionar sync**:

| Ação | Onde | Quem usa |
|------|------|----------|
| **Sync automático** | Cron job (a cada 15-30 min para exames pendentes) | Sistema |
| **Botão "Atualizar resultados"** | Tela de exames do paciente ou da consulta | Médico |
| **Botão "Sincronizar"** | Hub de integrações (por parceiro) | Admin |

**Comportamento do botão de sync:**

```
[Atualizar resultados]
         │
         ▼
   Loading spinner (async via queue)
         │
         ▼
   Job disparado: SyncExamResults
         │
         ▼
   Para cada exame pendente do paciente:
      GET /DiagnosticReport?based-on={id}
         │
         ├── Resultado → atualiza + notifica (toast: "2 novos resultados")
         │
         └── Sem resultado → silencioso (não incomoda o usuário)
         │
         ▼
   Toast/notificação: "Resultados atualizados" ou "Nenhum novo resultado"
```

### 2.4 Quando usar cada caminho

| Situação | Caminho | Motivo |
|----------|---------|--------|
| Laboratório suporta webhook | **Webhook (push)** | Tempo real, sem polling |
| Laboratório não suporta webhook | **Sync automático (pull via cron)** | Fallback confiável |
| Médico quer ver resultado agora | **Botão sync (pull sob demanda)** | Controle do usuário |
| Erro ou timeout no webhook | **Retry via queue** | Resiliência |
| Primeiro contato com novo parceiro | **Pull + webhook** | Redundância até estabilizar |

---

## 3. Fluxo completo — Ponta a ponta

### 3.1 Fluxo principal (happy path)

```
┌─────────────────────────────────────────────────────────┐
│                    NOSSO SISTEMA                         │
│                                                         │
│  1. Médico solicita exame na consulta                   │
│     └── Examination criado (status: requested)          │
│         └── Evento: ExamRequested                       │
│                                                         │
│  2. IntegrationService processa evento                  │
│     └── Verifica: paciente tem consentimento?           │
│         └── Verifica: laboratório parceiro ativo?       │
│             └── LabAdapter.sendOrder(ExamOrderDto)      │
│                                                         │
│  3. POST ServiceRequest → Lab                           │
│     └── integration_events (outbound, success)          │
│     └── fhir_resource_mappings (ServiceRequest ↔ exam)  │
│     └── Examination.external_id = lab_order_id          │
│     └── Examination.status = in_progress                │
│                                                         │
│  ─── TEMPO PASSA (paciente faz o exame) ───             │
│                                                         │
│  4a. Lab envia webhook com resultado                    │
│      OU                                                 │
│  4b. Cron/botão puxa resultado                          │
│                                                         │
│  5. LabAdapter.parseResult(DiagnosticReport)            │
│     └── Examination.results = JSON mapeado              │
│     └── Examination.status = completed                  │
│     └── Examination.source = integration                │
│     └── Examination.received_from_partner_at = now()    │
│     └── integration_events (inbound, success)           │
│                                                         │
│  6. Evento: ExamResultReceived                          │
│     └── Notificação para médico                         │
│     └── Notificação para paciente                       │
│     └── Timeline do prontuário atualizada               │
│                                                         │
│  7. RNDS (se configurado)                               │
│     └── Enviar Bundle (Encounter + DiagnosticReport)    │
│     └── fhir_resource_mappings (RNDS)                   │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Fluxo de erro e retry

```
POST ServiceRequest → Lab
         │
         └── Falha (timeout, 500, rede)
                │
                ▼
         integration_events (status: failed, error_message)
                │
                ▼
         integration_queue (status: queued, attempts: 1)
                │
                ▼
         Retry com backoff exponencial:
            Tentativa 1: +30s
            Tentativa 2: +2min
            Tentativa 3: +10min
            Tentativa 4: +30min
            Tentativa 5: +2h
                │
                ├── Sucesso em qualquer tentativa → fluxo normal
                │
                └── Todas falharam (max_attempts atingido)
                       │
                       ▼
                    integration_events (status: failed, final)
                    Notificação para admin: "Pedido de exame não enviado ao Lab X"
                    Examination mantém status: requested (médico pode reenviar)
```

---

## 4. Endpoints da API pública (nosso sistema expõe)

### 4.1 Webhook para receber resultados

```
POST /api/v1/public/webhooks/lab/{partner_slug}
Content-Type: application/fhir+json
X-Webhook-Signature: sha256=abc123...

{
  "resourceType": "Bundle",
  "type": "transaction",
  "entry": [
    {
      "resource": {
        "resourceType": "DiagnosticReport",
        "status": "final",
        "code": { "coding": [{ "display": "Hemograma Completo" }] },
        "result": [
          { "reference": "Observation/obs-1" }
        ]
      }
    },
    {
      "resource": {
        "resourceType": "Observation",
        "id": "obs-1",
        "code": { "coding": [{ "system": "http://loinc.org", "code": "718-7", "display": "Hemoglobina" }] },
        "valueQuantity": { "value": 14.2, "unit": "g/dL" },
        "referenceRange": [{ "low": { "value": 12.0 }, "high": { "value": 17.5 } }]
      }
    }
  ]
}
```

**Validações do webhook:**
- HMAC signature válida (usando secret do `integration_webhooks`)
- Parceiro com `status: active` em `partner_integrations`
- Payload FHIR válido (validação de estrutura)
- `ServiceRequest` referenciado existe no nosso sistema (via `fhir_resource_mappings`)

### 4.2 Endpoint para lab consultar pedidos

```
GET /api/v1/public/lab/{partner_slug}/orders?status=active
Authorization: Bearer {token}
Accept: application/fhir+json

Resposta: Bundle com ServiceRequest[] pendentes para este laboratório
```

### 4.3 Endpoint para verificar status de integração

```
GET /api/v1/public/health/{partner_slug}
Authorization: Bearer {token}

Resposta:
{
  "status": "ok",
  "partner": "lab-hermes",
  "capabilities": ["receive_orders", "send_results"],
  "last_event": "2026-03-28T14:30:00Z"
}
```

---

## 5. Estrutura de código (Laravel)

### 5.1 Novos arquivos necessários

```
app/Integrations/
├── Contracts/
│   ├── IntegrationInterface.php       # sendOrder(), fetchResult(), parseWebhook()
│   └── LabIntegrationInterface.php    # Contrato específico para labs
│
├── Services/
│   └── IntegrationService.php         # Orquestra: identifica parceiro, chama adapter, registra evento
│
├── Adapters/
│   ├── Lab/
│   │   ├── FhirLabAdapter.php         # Adapter genérico FHIR para labs
│   │   └── LabHermesAdapter.php       # Adapter específico se Hermes tiver particularidades
│   └── BaseAdapter.php                # Lógica comum (HTTP client, retry, logging)
│
├── DTOs/
│   ├── ExamOrderDto.php               # Dados do pedido de exame (interno → adapter)
│   ├── ExamResultDto.php              # Dados do resultado (adapter → interno)
│   └── FhirBundleDto.php             # Representação de Bundle FHIR
│
├── Events/
│   ├── ExamRequested.php              # Pedido de exame criado
│   ├── ExamResultReceived.php         # Resultado recebido (webhook ou pull)
│   └── IntegrationFailed.php          # Falha em integração (para alertas)
│
├── Listeners/
│   ├── SendExamOrderToLab.php         # Ouve ExamRequested → chama IntegrationService
│   ├── ProcessExamResult.php          # Ouve ExamResultReceived → atualiza prontuário
│   └── NotifyIntegrationFailure.php   # Ouve IntegrationFailed → notifica admin
│
├── Jobs/
│   ├── SyncExamResults.php            # Job para pull de resultados (cron ou botão)
│   ├── RetryIntegrationOperation.php  # Job de retry (processa integration_queue)
│   └── SendToRnds.php                # Job para enviar dados à RNDS
│
├── Http/
│   ├── Controllers/
│   │   └── WebhookController.php      # Recebe webhooks de parceiros
│   ├── Middleware/
│   │   └── ValidateWebhookSignature.php
│   └── Requests/
│       └── WebhookRequest.php
│
├── Mappers/
│   ├── PatientFhirMapper.php          # Patient ↔ FHIR Patient
│   ├── ExamOrderFhirMapper.php        # Examination ↔ FHIR ServiceRequest
│   ├── ExamResultFhirMapper.php       # FHIR DiagnosticReport → Examination.results
│   ├── DiagnosisFhirMapper.php        # Diagnosis ↔ FHIR Condition
│   └── PrescriptionFhirMapper.php     # Prescription ↔ FHIR MedicationRequest
│
└── Models/
    ├── PartnerIntegration.php
    ├── IntegrationCredential.php
    ├── IntegrationEvent.php
    ├── IntegrationWebhook.php
    ├── IntegrationQueueItem.php
    └── FhirResourceMapping.php
```

### 5.2 Rotas

```php
// routes/api.php — API pública para parceiros
Route::prefix('v1/public')->group(function () {
    Route::post('/webhooks/lab/{partner_slug}', [WebhookController::class, 'labResult'])
         ->middleware('validate.webhook.signature');

    Route::get('/lab/{partner_slug}/orders', [LabOrderController::class, 'index'])
         ->middleware('auth:partner');

    Route::get('/health/{partner_slug}', [HealthController::class, 'check'])
         ->middleware('auth:partner');
});
```

### 5.3 Scheduler (cron)

```php
// app/Console/Kernel.php
$schedule->job(new SyncExamResults)->everyFifteenMinutes();
$schedule->job(new RetryIntegrationOperation)->everyFiveMinutes();
```

---

## 6. Critérios de conclusão do MVP 1

| Critério | Descrição |
|----------|-----------|
| Médico solicita exame e lab recebe automaticamente | Fluxo outbound completo |
| Resultado do lab aparece no prontuário automaticamente | Fluxo inbound (webhook) completo |
| Botão "Atualizar resultados" funciona | Fluxo inbound (pull) completo |
| Logs visíveis no hub de integrações | Admin vê eventos e erros |
| Retry automático para falhas | integration_queue processando retries |
| Consentimento registrado antes do envio | LGPD atendida |
| Dados enviados à RNDS após resultado | Regulatório atendido |
| 1 laboratório piloto conectado | Validação real |

---

## 7. Documentos relacionados

- [SchemaIntegracoes.md](SchemaIntegracoes.md) — tabelas de banco e mapeamento FHIR detalhado
- [PadroesRegulatorios.md](PadroesRegulatorios.md) — obrigações legais do MVP 1
- [ResilienciaOperacional.md](ResilienciaOperacional.md) — circuit breaker e retry
- [SegurancaAPIPublica.md](SegurancaAPIPublica.md) — autenticação da API pública
- [docs/interoperabilidade/Produto-MVP-Roadmap.md](../docs/interoperabilidade/Produto-MVP-Roadmap.md) — contexto de produto

---

*Criado em: março/2026.*
