# Schema de Integrações — Migrations Faltantes + Mapeamento de Dados

Este documento detalha as **tabelas de banco de dados necessárias** para suportar a interoperabilidade e o **mapeamento entre o modelo interno e FHIR R4**.

---

## 1. Visão geral — O que falta no banco atual

O schema atual tem as entidades clínicas (examinations, prescriptions, diagnoses, etc.) mas **não tem infraestrutura para integrações**. As seguintes tabelas são necessárias:

| Tabela | Propósito | Prioridade |
|--------|-----------|-----------|
| `partner_integrations` | Registro de parceiros conectados (laboratórios, farmácias, etc.) | **Crítica** |
| `integration_credentials` | Tokens, chaves e certificados de cada parceiro | **Crítica** |
| `integration_events` | Log de todos os eventos de integração (envios, recebimentos, erros) | **Crítica** |
| `integration_webhooks` | Webhooks registrados por parceiros para receber notificações | **Alta** |
| `integration_queue` | Fila de operações pendentes (retry, sync) | **Alta** |
| `fhir_resource_mappings` | Cache/referência entre IDs internos e IDs FHIR/RNDS | **Alta** |

Além disso, **tabelas existentes precisam de novos campos** para rastreabilidade de dados externos.

---

## 2. Migrations necessárias

### 2.1 `partner_integrations` — Parceiros conectados

Cada registro representa uma conexão ativa entre o sistema e um parceiro externo.

```php
Schema::create('partner_integrations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');                              // "Laboratório Hermes", "Farmácia Vida"
    $table->string('slug')->unique();                    // "lab-hermes", "farmacia-vida"
    $table->enum('type', [
        'laboratory', 'pharmacy', 'hospital',
        'insurance', 'rnds', 'other'
    ]);
    $table->enum('status', [
        'active', 'inactive', 'pending', 'error', 'suspended'
    ])->default('pending');
    $table->string('base_url')->nullable();              // URL base da API do parceiro
    $table->string('webhook_url')->nullable();           // URL do nosso webhook para este parceiro
    $table->json('capabilities')->nullable();            // ["send_exam_order", "receive_results"]
    $table->json('settings')->nullable();                // Configurações específicas (timeout, retry, etc.)
    $table->string('fhir_version')->nullable();          // "R4", "STU3", null se não usa FHIR
    $table->string('contact_email')->nullable();
    $table->string('contact_phone')->nullable();
    $table->timestamp('connected_at')->nullable();
    $table->timestamp('last_sync_at')->nullable();
    $table->uuid('connected_by')->nullable();            // user_id de quem conectou
    $table->timestamps();
    $table->softDeletes();

    $table->index('type');
    $table->index('status');
    $table->index('slug');
    $table->foreign('connected_by')->references('id')->on('users')->nullOnDelete();
});
```

---

### 2.2 `integration_credentials` — Credenciais de parceiros

Separada da tabela de parceiros por segurança — permite criptografia e rotação independentes.

```php
Schema::create('integration_credentials', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('partner_integration_id')
          ->constrained('partner_integrations')->cascadeOnDelete();
    $table->enum('auth_type', [
        'api_key', 'oauth2_client_credentials', 'oauth2_authorization_code',
        'certificate', 'basic_auth', 'bearer_token'
    ]);
    $table->text('client_id')->nullable();               // Criptografado via cast
    $table->text('client_secret')->nullable();            // Criptografado via cast
    $table->text('access_token')->nullable();             // Criptografado via cast
    $table->text('refresh_token')->nullable();            // Criptografado via cast
    $table->text('certificate_path')->nullable();         // Caminho do certificado (.pfx)
    $table->text('certificate_password')->nullable();     // Criptografado via cast
    $table->json('scopes')->nullable();                   // ["read:exams", "write:orders"]
    $table->timestamp('token_expires_at')->nullable();
    $table->timestamps();

    $table->index('partner_integration_id');
    $table->index('token_expires_at');
});
```

**Nota de segurança:** Os campos `client_secret`, `access_token`, `refresh_token` e `certificate_password` devem usar Laravel Encrypted Casts (`$casts = ['client_secret' => 'encrypted']`).

---

### 2.3 `integration_events` — Log de eventos

Registro de cada operação de integração para auditoria, debug e métricas.

```php
Schema::create('integration_events', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('partner_integration_id')
          ->constrained('partner_integrations')->cascadeOnDelete();
    $table->enum('direction', ['outbound', 'inbound']);   // Nós enviamos ou recebemos
    $table->string('event_type', 100);                    // "exam_order_sent", "result_received", "prescription_verified"
    $table->enum('status', [
        'pending', 'processing', 'success', 'failed', 'retrying'
    ])->default('pending');
    $table->string('resource_type', 100)->nullable();     // "examination", "prescription", "diagnosis"
    $table->uuid('resource_id')->nullable();              // ID do recurso interno relacionado
    $table->string('fhir_resource_type')->nullable();     // "ServiceRequest", "DiagnosticReport"
    $table->string('external_id')->nullable();            // ID no sistema do parceiro
    $table->json('request_payload')->nullable();          // O que foi enviado (truncado se muito grande)
    $table->json('response_payload')->nullable();         // O que foi recebido
    $table->integer('http_status')->nullable();
    $table->text('error_message')->nullable();
    $table->integer('retry_count')->default(0);
    $table->timestamp('next_retry_at')->nullable();
    $table->integer('duration_ms')->nullable();           // Tempo de resposta em ms
    $table->timestamps();

    $table->index(['partner_integration_id', 'created_at']);
    $table->index(['event_type', 'status']);
    $table->index(['resource_type', 'resource_id']);
    $table->index('external_id');
    $table->index('status');
    $table->index('next_retry_at');
});
```

---

### 2.4 `integration_webhooks` — Webhooks registrados

Permite que parceiros registrem endpoints para receber notificações push.

```php
Schema::create('integration_webhooks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('partner_integration_id')
          ->constrained('partner_integrations')->cascadeOnDelete();
    $table->string('url');                                // URL de callback do parceiro
    $table->string('secret')->nullable();                 // Secret para HMAC signature
    $table->json('events');                               // ["exam.completed", "prescription.created"]
    $table->enum('status', ['active', 'inactive', 'failed'])->default('active');
    $table->integer('failure_count')->default(0);
    $table->timestamp('last_triggered_at')->nullable();
    $table->timestamp('last_success_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['partner_integration_id', 'status']);
});
```

---

### 2.5 `integration_queue` — Fila de operações

Operações que falharam ou estão agendadas para sync. Complementa o RabbitMQ com persistência em banco para visibilidade.

```php
Schema::create('integration_queue', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('partner_integration_id')
          ->constrained('partner_integrations')->cascadeOnDelete();
    $table->foreignUuid('integration_event_id')->nullable()
          ->constrained('integration_events')->nullOnDelete();
    $table->string('operation', 100);                     // "send_exam_order", "fetch_result", "verify_prescription"
    $table->json('payload');
    $table->enum('status', [
        'queued', 'processing', 'completed', 'failed', 'cancelled'
    ])->default('queued');
    $table->integer('attempts')->default(0);
    $table->integer('max_attempts')->default(5);
    $table->timestamp('scheduled_at')->nullable();        // Para operações agendadas
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('last_error')->nullable();
    $table->timestamps();

    $table->index(['status', 'scheduled_at']);
    $table->index('partner_integration_id');
    $table->index('operation');
});
```

---

### 2.6 `fhir_resource_mappings` — Mapeamento ID interno ↔ FHIR

Mantém a referência entre nossos UUIDs e os IDs FHIR/RNDS para evitar duplicações e manter rastreabilidade.

```php
Schema::create('fhir_resource_mappings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('internal_resource_type', 100);        // "examination", "prescription", "patient"
    $table->uuid('internal_resource_id');
    $table->string('fhir_resource_type', 100);            // "ServiceRequest", "MedicationRequest", "Patient"
    $table->string('fhir_resource_id')->nullable();       // ID no servidor FHIR / RNDS
    $table->string('fhir_bundle_id')->nullable();         // ID do Bundle se enviado em lote
    $table->foreignUuid('partner_integration_id')->nullable()
          ->constrained('partner_integrations')->nullOnDelete();
    $table->string('version')->nullable();                // Versão do recurso FHIR
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();

    $table->unique(['internal_resource_type', 'internal_resource_id', 'partner_integration_id'], 'fhir_mapping_unique');
    $table->index(['fhir_resource_type', 'fhir_resource_id']);
    $table->index('partner_integration_id');
});
```

---

## 3. Alterações em tabelas existentes

### 3.1 Tabela `patients` — Adicionar CNS e campos regulatórios

```php
Schema::table('patients', function (Blueprint $table) {
    $table->string('cns', 15)->nullable()->after('phone');          // Cartão Nacional de Saúde
    $table->string('cpf', 11)->nullable()->after('cns');            // CPF (se ainda não existir)
    $table->string('mother_name')->nullable()->after('cpf');        // Nome da mãe (exigido pela RNDS)

    $table->index('cns');
    $table->index('cpf');
});
```

### 3.2 Tabela `doctors` — Adicionar CNS

```php
Schema::table('doctors', function (Blueprint $table) {
    $table->string('cns', 15)->nullable()->after('crm');            // CNS do profissional
    $table->string('cbo', 6)->nullable()->after('cns');             // Código Brasileiro de Ocupações

    $table->index('cns');
});
```

### 3.3 Tabela `examinations` — Rastreabilidade de origem

```php
Schema::table('examinations', function (Blueprint $table) {
    $table->foreignUuid('partner_integration_id')->nullable()
          ->after('metadata')
          ->constrained('partner_integrations')->nullOnDelete();
    $table->string('external_id')->nullable()->after('partner_integration_id');    // ID no sistema do lab
    $table->string('external_accession')->nullable()->after('external_id');        // Número de acesso do lab
    $table->enum('source', ['internal', 'integration', 'manual_upload'])
          ->default('internal')->after('external_accession');
    $table->timestamp('received_from_partner_at')->nullable()->after('source');

    $table->index('external_id');
    $table->index('source');
});
```

### 3.4 Tabela `prescriptions` — Rastreabilidade e assinatura

```php
Schema::table('prescriptions', function (Blueprint $table) {
    $table->foreignUuid('partner_integration_id')->nullable()
          ->after('metadata')
          ->constrained('partner_integrations')->nullOnDelete();
    $table->string('external_id')->nullable()->after('partner_integration_id');
    $table->string('digital_signature_hash')->nullable()->after('external_id');    // Hash da assinatura ICP-Brasil
    $table->enum('signature_status', ['unsigned', 'signed', 'verified', 'invalid'])
          ->default('unsigned')->after('digital_signature_hash');
    $table->string('verification_code', 32)->nullable()->after('signature_status'); // Código para farmácia verificar
    $table->timestamp('signed_at')->nullable()->after('verification_code');

    $table->index('verification_code');
    $table->index('signature_status');
});
```

---

## 4. Mapeamento de dados — Modelo interno ↔ FHIR R4

Esta seção define como os dados internos se traduzem para recursos FHIR. Os **Adapters** (ver [Arquitetura](../docs/interoperabilidade/Arquitetura.md)) usam este mapeamento.

### 4.1 Patient → FHIR Patient

| Campo interno (`patients`) | Campo FHIR (`Patient`) | Notas |
|---------------------------|------------------------|-------|
| `id` (uuid) | `identifier[0].value` (system: nosso sistema) | Identificador interno |
| `cns` | `identifier[1].value` (system: `http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns`) | **Obrigatório para RNDS** |
| `cpf` | `identifier[2].value` (system: `http://rnds.saude.gov.br/fhir/r4/NamingSystem/cpf`) | Identificador nacional |
| `user.name` | `name[0].text` | Nome completo |
| `date_of_birth` | `birthDate` | Formato ISO 8601 |
| `phone` | `telecom[0].value` (system: phone) | |
| `user.email` | `telecom[1].value` (system: email) | |
| `blood_type` | `extension` (custom) | Não há campo nativo em FHIR |
| `mother_name` | `extension` (RNDS exige) | Extension customizada RNDS |

**Exemplo de saída FHIR:**

```json
{
  "resourceType": "Patient",
  "identifier": [
    {
      "system": "https://telemedicina.example.com/fhir/patient-id",
      "value": "uuid-interno"
    },
    {
      "system": "http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns",
      "value": "123456789012345"
    }
  ],
  "name": [{ "text": "João da Silva" }],
  "birthDate": "1990-05-15",
  "telecom": [
    { "system": "phone", "value": "+5511999999999" },
    { "system": "email", "value": "joao@email.com" }
  ]
}
```

---

### 4.2 Examination (pedido) → FHIR ServiceRequest

| Campo interno (`examinations`) | Campo FHIR (`ServiceRequest`) | Notas |
|-------------------------------|-------------------------------|-------|
| `id` | `identifier[0].value` | |
| `patient_id` → Patient | `subject` (Reference) | |
| `doctor_id` → Practitioner | `requester` (Reference) | |
| `appointment_id` → Encounter | `encounter` (Reference) | |
| `name` | `code.coding[0].display` | Mapear para código LOINC quando possível |
| `type` | `category` | lab → laboratory, image → imaging |
| `requested_at` | `authoredOn` | |
| `status` | `status` | requested→active, in_progress→active, completed→completed, cancelled→revoked |
| `metadata` | `note` | Informações adicionais |

**Mapeamento de status:**

| Status interno | Status FHIR ServiceRequest |
|---------------|---------------------------|
| `requested` | `active` |
| `in_progress` | `active` |
| `completed` | `completed` |
| `cancelled` | `revoked` |

---

### 4.3 Examination (resultado) → FHIR DiagnosticReport + Observation

Quando o laboratório retorna o resultado:

| Campo interno (`examinations`) | Campo FHIR | Recurso |
|-------------------------------|------------|---------|
| `id` | `identifier[0].value` | DiagnosticReport |
| `name` | `code.coding[0].display` | DiagnosticReport |
| `completed_at` | `effectiveDateTime` | DiagnosticReport |
| `status` = completed | `status` = `final` | DiagnosticReport |
| `results` (JSON) | Cada item → `Observation` | Observation |
| `results[n].name` | `code.coding[0].display` | Observation |
| `results[n].value` | `valueQuantity.value` | Observation |
| `results[n].unit` | `valueQuantity.unit` | Observation |
| `results[n].reference_range` | `referenceRange[0]` | Observation |
| `attachment_url` | `presentedForm[0].url` | DiagnosticReport |

**Estrutura esperada do campo `results` JSON:**

```json
[
  {
    "name": "Hemoglobina",
    "value": 14.2,
    "unit": "g/dL",
    "reference_range": "12.0-17.5",
    "status": "normal",
    "loinc_code": "718-7"
  },
  {
    "name": "Glicemia em jejum",
    "value": 95,
    "unit": "mg/dL",
    "reference_range": "70-99",
    "status": "normal",
    "loinc_code": "1558-6"
  }
]
```

---

### 4.4 Prescription → FHIR MedicationRequest

| Campo interno (`prescriptions`) | Campo FHIR (`MedicationRequest`) | Notas |
|--------------------------------|----------------------------------|-------|
| `id` | `identifier[0].value` | |
| `patient_id` → Patient | `subject` (Reference) | |
| `doctor_id` → Practitioner | `requester` (Reference) | |
| `appointment_id` → Encounter | `encounter` (Reference) | |
| `medications` (JSON array) | Cada item → `MedicationRequest` separado | Um recurso FHIR por medicamento |
| `medications[n].name` | `medicationCodeableConcept.text` | Mapear para ANVISA/CATMAT quando possível |
| `medications[n].dosage` | `dosageInstruction[0].text` | |
| `medications[n].frequency` | `dosageInstruction[0].timing` | |
| `medications[n].duration` | `dosageInstruction[0].timing.repeat.boundsPeriod` | |
| `instructions` | `note[0].text` | |
| `valid_until` | `dispenseRequest.validityPeriod.end` | |
| `status` | `status` | active→active, expired→stopped, cancelled→cancelled |
| `issued_at` | `authoredOn` | |
| `digital_signature_hash` | `extension` (ICP-Brasil) | Custom extension |

---

### 4.5 Diagnosis → FHIR Condition

| Campo interno (`diagnoses`) | Campo FHIR (`Condition`) | Notas |
|----------------------------|--------------------------|-------|
| `id` | `identifier[0].value` | |
| `patient_id` → Patient | `subject` (Reference) | |
| `doctor_id` → Practitioner | `recorder` (Reference) | |
| `appointment_id` → Encounter | `encounter` (Reference) | |
| `cid10_code` | `code.coding[0].code` (system: ICD-10) | `http://hl7.org/fhir/sid/icd-10` |
| `cid10_description` | `code.coding[0].display` | |
| `diagnosis_type` | `category` | principal → encounter-diagnosis |
| `description` | `note[0].text` | |

---

### 4.6 Vital Signs → FHIR Observation

Os sinais vitais mapeiam diretamente para `Observation` com códigos LOINC:

| Campo interno | LOINC Code | FHIR Observation.code |
|--------------|------------|----------------------|
| `blood_pressure` (systolic) | `8480-6` | Systolic blood pressure |
| `blood_pressure` (diastolic) | `8462-4` | Diastolic blood pressure |
| `heart_rate` | `8867-4` | Heart rate |
| `temperature` | `8310-5` | Body temperature |
| `oxygen_saturation` | `2708-6` | Oxygen saturation |
| `respiratory_rate` | `9279-1` | Respiratory rate |

---

## 5. Diagrama de relacionamentos (novas tabelas)

```
partner_integrations
    │
    ├── 1:N ── integration_credentials
    │
    ├── 1:N ── integration_events
    │
    ├── 1:N ── integration_webhooks
    │
    ├── 1:N ── integration_queue
    │
    ├── 1:N ── fhir_resource_mappings
    │
    ├── 1:N ── examinations (via partner_integration_id)
    │
    └── 1:N ── prescriptions (via partner_integration_id)
```

---

## 6. Ordem de criação das migrations

| # | Migration | Depende de |
|---|-----------|-----------|
| 1 | `create_partner_integrations_table` | — |
| 2 | `create_integration_credentials_table` | partner_integrations |
| 3 | `create_integration_events_table` | partner_integrations |
| 4 | `create_integration_webhooks_table` | partner_integrations |
| 5 | `create_integration_queue_table` | partner_integrations, integration_events |
| 6 | `create_fhir_resource_mappings_table` | partner_integrations |
| 7 | `add_cns_cpf_to_patients_table` | patients |
| 8 | `add_cns_cbo_to_doctors_table` | doctors |
| 9 | `add_integration_fields_to_examinations_table` | examinations, partner_integrations |
| 10 | `add_integration_fields_to_prescriptions_table` | prescriptions, partner_integrations |

---

## 7. Documentos relacionados

- [PadroesRegulatorios.md](PadroesRegulatorios.md) — por que campos como `cns` e `cbo` são obrigatórios
- [MVP1.md](MVP1.md) — como o schema é usado no fluxo do laboratório
- [ResilienciaOperacional.md](ResilienciaOperacional.md) — como `integration_queue` e `integration_events` suportam retry e circuit breaker
- [docs/interoperabilidade/Arquitetura.md](../docs/interoperabilidade/Arquitetura.md) — estrutura de código dos Adapters que usam este schema

---

*Criado em: março/2026.*
