# Diagrama do Banco de Dados - Telemedicina Para Todos

*Documento em: `docs/layers/persistence/database/` (Camada de Persistência)*

## Diagrama Entidade-Relacionamento (ERD)

```mermaid
erDiagram
    USERS {
        uuid id PK
        string name
        string email UK
        string password
        string avatar_path
        boolean timeline_completed
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }

    DOCTORS {
        uuid id PK
        uuid user_id FK UK
        string crm UK
        text biography
        json language
        string license_number UK
        date license_expiry_date
        enum status "active|inactive|suspended"
        json availability_schedule
        decimal consultation_fee
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    PATIENTS {
        uuid id PK
        uuid user_id FK UK
        enum gender "male|female|other"
        date date_of_birth
        string phone_number
        string emergency_contact
        string emergency_phone
        text medical_history
        text allergies
        text current_medications
        string blood_type
        decimal height
        decimal weight
        string insurance_provider
        string insurance_number
        enum status "active|inactive|blocked"
        boolean consent_telemedicine
        timestamp last_consultation_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    SPECIALIZATIONS {
        uuid id PK
        string name
        string description
        timestamp created_at
        timestamp updated_at
    }

    DOCTOR_SERVICE_LOCATIONS {
        uuid id PK
        uuid doctor_id FK
        string name
        enum type "teleconsultation|office|hospital|clinic"
        text address
        string phone
        text description
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    DOCTOR_AVAILABILITY_SLOTS {
        uuid id PK
        uuid doctor_id FK
        uuid location_id FK
        enum type "recurring|specific"
        enum day_of_week "monday|tuesday|wednesday|thursday|friday|saturday|sunday"
        date specific_date
        time start_time
        time end_time
        boolean is_active
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    DOCTOR_BLOCKED_DATES {
        uuid id PK
        uuid doctor_id FK
        date blocked_date
        string reason
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    APPOINTMENTS {
        uuid id PK
        uuid doctor_id FK
        uuid patient_id FK
        timestamp scheduled_at
        string access_code UK
        timestamp started_at
        timestamp ended_at
        string video_recording_url
        enum status "scheduled|in_progress|completed|no_show|cancelled|rescheduled"
        text notes
        json metadata
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    APPOINTMENT_LOGS {
        uuid id PK
        uuid appointment_id FK
        uuid user_id FK
        string event
        json payload
        timestamp created_at
        timestamp updated_at
    }

    PRESCRIPTIONS {
        uuid id PK
        uuid appointment_id FK
        uuid doctor_id FK
        uuid patient_id FK
        json medications
        text instructions
        date valid_until
        enum status "active|expired|cancelled"
        json metadata
        timestamp issued_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    DIAGNOSES {
        uuid id PK
        uuid appointment_id FK
        uuid doctor_id FK
        uuid patient_id FK
        string cid10_code
        string cid10_description
        string diagnosis_type "principal|secondary"
        text description
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    EXAMINATIONS {
        uuid id PK
        uuid appointment_id FK
        uuid patient_id FK
        uuid doctor_id FK
        enum type "lab|image|other"
        string name
        timestamp requested_at
        timestamp completed_at
        json results
        string attachment_url
        enum status "requested|in_progress|completed|cancelled"
        json metadata
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    CLINICAL_NOTES {
        uuid id PK
        uuid appointment_id FK
        uuid doctor_id FK
        uuid patient_id FK
        uuid parent_id FK
        string title
        text content
        boolean is_private
        string category
        json tags
        integer version
        json metadata
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    MEDICAL_CERTIFICATES {
        uuid id PK
        uuid appointment_id FK
        uuid doctor_id FK
        uuid patient_id FK
        string type
        date start_date
        date end_date
        integer days
        text reason
        text restrictions
        string signature_hash
        string crm_number
        string verification_code UK
        string pdf_url
        string status
        json metadata
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    VITAL_SIGNS {
        uuid id PK
        uuid appointment_id FK
        uuid patient_id FK
        uuid doctor_id FK
        timestamp recorded_at
        integer blood_pressure_systolic
        integer blood_pressure_diastolic
        decimal temperature
        integer heart_rate
        integer respiratory_rate
        integer oxygen_saturation
        decimal weight
        decimal height
        text notes
        json metadata
        timestamp created_at
        timestamp updated_at
    }

    MEDICAL_DOCUMENTS {
        uuid id PK
        uuid patient_id FK
        uuid appointment_id FK
        uuid doctor_id FK
        uuid uploaded_by FK
        enum category "exam|prescription|report|other"
        string name
        string file_path
        string file_type
        bigint file_size
        text description
        json metadata
        enum visibility "patient|doctor|shared"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    MEDICAL_RECORD_AUDIT_LOGS {
        uuid id PK
        uuid patient_id FK
        uuid user_id FK
        string action
        string resource_type
        uuid resource_id
        string ip_address
        text user_agent
        json metadata
        timestamp created_at
        timestamp updated_at
    }

    VIDEO_CALL_ROOMS {
        id bigint PK
        timestamp created_at
        timestamp updated_at
    }

    VIDEO_CALL_EVENTS {
        id bigint PK
        timestamp created_at
        timestamp updated_at
    }

    TIMELINE_EVENTS {
        uuid id PK
        uuid user_id FK
        enum type "education|course|certificate|project"
        string title
        string subtitle
        date start_date
        date end_date
        text description
        string media_url
        enum degree_type "fundamental|medio|graduacao|pos|curso_livre|certificacao|projeto"
        boolean is_public
        json extra_data
        integer order_priority
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    %% Relacionamentos Principais
    USERS ||--o| DOCTORS : "has_one"
    USERS ||--o| PATIENTS : "has_one"
    USERS ||--o{ TIMELINE_EVENTS : "has_many"
    
    DOCTORS ||--o{ DOCTOR_SERVICE_LOCATIONS : "has_many"
    DOCTORS ||--o{ DOCTOR_AVAILABILITY_SLOTS : "has_many"
    DOCTORS ||--o{ DOCTOR_BLOCKED_DATES : "has_many"
    DOCTORS ||--o{ APPOINTMENTS : "has_many"
    DOCTORS ||--o{ PRESCRIPTIONS : "has_many"
    DOCTORS ||--o{ DIAGNOSES : "has_many"
    DOCTORS ||--o{ EXAMINATIONS : "has_many"
    DOCTORS ||--o{ CLINICAL_NOTES : "has_many"
    DOCTORS ||--o{ MEDICAL_CERTIFICATES : "has_many"
    DOCTORS ||--o{ VITAL_SIGNS : "has_many"
    DOCTORS ||--o{ MEDICAL_DOCUMENTS : "has_many"
    
    PATIENTS ||--o{ APPOINTMENTS : "has_many"
    PATIENTS ||--o{ PRESCRIPTIONS : "has_many"
    PATIENTS ||--o{ DIAGNOSES : "has_many"
    PATIENTS ||--o{ EXAMINATIONS : "has_many"
    PATIENTS ||--o{ CLINICAL_NOTES : "has_many"
    PATIENTS ||--o{ MEDICAL_CERTIFICATES : "has_many"
    PATIENTS ||--o{ VITAL_SIGNS : "has_many"
    PATIENTS ||--o{ MEDICAL_DOCUMENTS : "has_many"
    PATIENTS ||--o{ MEDICAL_RECORD_AUDIT_LOGS : "has_many"
    
    DOCTOR_SERVICE_LOCATIONS ||--o{ DOCTOR_AVAILABILITY_SLOTS : "has_many"
    
    APPOINTMENTS ||--o{ APPOINTMENT_LOGS : "has_many"
    APPOINTMENTS ||--o{ PRESCRIPTIONS : "has_many"
    APPOINTMENTS ||--o{ DIAGNOSES : "has_many"
    APPOINTMENTS ||--o{ EXAMINATIONS : "has_many"
    APPOINTMENTS ||--o{ CLINICAL_NOTES : "has_many"
    APPOINTMENTS ||--o{ MEDICAL_CERTIFICATES : "has_many"
    APPOINTMENTS ||--o{ VITAL_SIGNS : "has_many"
    APPOINTMENTS ||--o{ MEDICAL_DOCUMENTS : "has_many"
    
    CLINICAL_NOTES ||--o{ CLINICAL_NOTES : "parent_child"
    
    USERS ||--o{ APPOINTMENT_LOGS : "has_many"
    USERS ||--o{ MEDICAL_RECORD_AUDIT_LOGS : "has_many"
    USERS ||--o{ MEDICAL_DOCUMENTS : "uploaded_by"
    
    %% Tabelas pivot para relacionamentos N:N
    DOCTOR_SPECIALIZATIONS {
        uuid doctor_id FK
        uuid specialization_id FK
        timestamp created_at
        timestamp updated_at
    }
    
    DOCTORS ||--o{ DOCTOR_SPECIALIZATIONS : "has_many"
    SPECIALIZATIONS ||--o{ DOCTOR_SPECIALIZATIONS : "has_many"
```

## Estrutura das Tabelas

### Tabelas Principais de Autenticação

1. **USERS** - Usuários base do sistema (médicos e pacientes)
   - Campos: `id` (UUID), `name`, `email` (único), `password`, `avatar_path`, `timeline_completed`
   - Relacionamentos: 1:1 com DOCTORS e PATIENTS, 1:N com TIMELINE_EVENTS

2. **DOCTORS** - Informações específicas dos médicos
   - Campos: `id` (UUID), `user_id` (FK único), `crm` (único), `biography`, `language` (JSON), `license_number`, `license_expiry_date`, `status`, `availability_schedule` (JSON), `consultation_fee`
   - Soft deletes habilitado

3. **PATIENTS** - Informações específicas dos pacientes
   - Campos: `id` (UUID), `user_id` (FK único), `gender`, `date_of_birth`, `phone_number`, `emergency_contact`, `medical_history`, `allergies`, `current_medications`, `blood_type`, `height`, `weight`, `insurance_provider`, `consent_telemedicine`
   - Soft deletes habilitado

### Tabelas de Especialização e Agenda

4. **SPECIALIZATIONS** - Especializações médicas
   - Relacionamento N:N com DOCTORS via `doctor_specialization`

5. **DOCTOR_SERVICE_LOCATIONS** - Locais de atendimento dos médicos
   - Tipos: `teleconsultation`, `office`, `hospital`, `clinic`
   - Relacionamento: 1:N com DOCTORS, 1:N com DOCTOR_AVAILABILITY_SLOTS

6. **DOCTOR_AVAILABILITY_SLOTS** - Horários disponíveis dos médicos
   - Tipos: `recurring` (semanal) ou `specific` (data específica)
   - Campos: `day_of_week` (para recurring), `specific_date` (para specific), `start_time`, `end_time`, `is_active`
   - Relacionamento: 1:N com DOCTORS, N:1 com DOCTOR_SERVICE_LOCATIONS

7. **DOCTOR_BLOCKED_DATES** - Datas bloqueadas para atendimento
   - Campos: `blocked_date`, `reason`
   - Relacionamento: 1:N com DOCTORS

### Tabelas de Consultas e Prontuários

8. **APPOINTMENTS** - Agendamentos de consultas
   - Status: `scheduled`, `in_progress`, `completed`, `no_show`, `cancelled`, `rescheduled`
   - Campos: `scheduled_at`, `access_code` (único), `started_at`, `ended_at`, `video_recording_url`, `notes`, `metadata` (JSON)
   - Relacionamentos: N:1 com DOCTORS e PATIENTS, 1:N com múltiplas entidades de prontuário

9. **APPOINTMENT_LOGS** - Logs de auditoria de consultas
   - Campos: `event`, `payload` (JSON)
   - Relacionamentos: N:1 com APPOINTMENTS e USERS

### Tabelas de Prontuário Médico

10. **PRESCRIPTIONS** - Prescrições médicas digitais
    - Campos: `medications` (JSON), `instructions`, `valid_until`, `status` (`active`, `expired`, `cancelled`), `issued_at`
    - Relacionamentos: N:1 com APPOINTMENTS, DOCTORS e PATIENTS

11. **DIAGNOSES** - Diagnósticos com CID-10
    - Campos: `cid10_code`, `cid10_description`, `diagnosis_type` (`principal`, `secondary`), `description`
    - Relacionamentos: N:1 com APPOINTMENTS, DOCTORS e PATIENTS

12. **EXAMINATIONS** - Exames solicitados
    - Tipos: `lab`, `image`, `other`
    - Status: `requested`, `in_progress`, `completed`, `cancelled`
    - Campos: `name`, `requested_at`, `completed_at`, `results` (JSON), `attachment_url`
    - Relacionamentos: N:1 com APPOINTMENTS, PATIENTS e DOCTORS

13. **CLINICAL_NOTES** - Anotações clínicas
    - Campos: `title`, `content`, `is_private`, `category`, `tags` (JSON), `version`, `parent_id` (para versões)
    - Relacionamentos: N:1 com APPOINTMENTS, DOCTORS e PATIENTS, auto-relacionamento para versões

14. **MEDICAL_CERTIFICATES** - Atestados médicos
    - Campos: `type`, `start_date`, `end_date`, `days`, `reason`, `restrictions`, `signature_hash`, `crm_number`, `verification_code` (único), `pdf_url`, `status`
    - Relacionamentos: N:1 com APPOINTMENTS, DOCTORS e PATIENTS

15. **VITAL_SIGNS** - Sinais vitais
    - Campos: `recorded_at`, `blood_pressure_systolic`, `blood_pressure_diastolic`, `temperature`, `heart_rate`, `respiratory_rate`, `oxygen_saturation`, `weight`, `height`, `notes`
    - Relacionamentos: N:1 com APPOINTMENTS, PATIENTS e DOCTORS

16. **MEDICAL_DOCUMENTS** - Documentos médicos anexados
    - Categorias: `exam`, `prescription`, `report`, `other`
    - Visibilidade: `patient`, `doctor`, `shared`
    - Campos: `name`, `file_path`, `file_type`, `file_size`, `description`
    - Relacionamentos: N:1 com PATIENTS, APPOINTMENTS, DOCTORS e USERS (uploaded_by)

17. **MEDICAL_RECORD_AUDIT_LOGS** - Logs de auditoria de prontuários
    - Campos: `action`, `resource_type`, `resource_id`, `ip_address`, `user_agent`, `metadata` (JSON)
    - Relacionamentos: N:1 com PATIENTS e USERS

### Tabelas de Videoconferência

18. **VIDEO_CALL_ROOMS** - Salas de videoconferência
    - Estrutura básica implementada (campos a serem expandidos)

19. **VIDEO_CALL_EVENTS** - Eventos de videoconferência
    - Estrutura básica implementada (campos a serem expandidos)

### Tabelas de Timeline

20. **TIMELINE_EVENTS** - Eventos de timeline (educação, cursos, certificados, projetos)
    - Tipos: `education`, `course`, `certificate`, `project`
    - Campos: `title`, `subtitle`, `start_date`, `end_date`, `description`, `media_url`, `degree_type`, `is_public`, `extra_data` (JSON), `order_priority`
    - Relacionamentos: N:1 com USERS

## Relacionamentos Principais

### Relacionamentos 1:1
- **USERS ↔ DOCTORS**: Um usuário pode ser um médico
- **USERS ↔ PATIENTS**: Um usuário pode ser um paciente

### Relacionamentos 1:N
- **DOCTORS → DOCTOR_SERVICE_LOCATIONS**: Um médico pode ter múltiplos locais de atendimento
- **DOCTORS → DOCTOR_AVAILABILITY_SLOTS**: Um médico pode ter múltiplos slots de disponibilidade
- **DOCTORS → DOCTOR_BLOCKED_DATES**: Um médico pode ter múltiplas datas bloqueadas
- **DOCTORS → APPOINTMENTS**: Um médico pode ter múltiplas consultas
- **PATIENTS → APPOINTMENTS**: Um paciente pode ter múltiplas consultas
- **APPOINTMENTS → PRESCRIPTIONS**: Uma consulta pode ter múltiplas prescrições
- **APPOINTMENTS → DIAGNOSES**: Uma consulta pode ter múltiplos diagnósticos
- **APPOINTMENTS → EXAMINATIONS**: Uma consulta pode ter múltiplos exames
- **APPOINTMENTS → CLINICAL_NOTES**: Uma consulta pode ter múltiplas anotações clínicas
- **APPOINTMENTS → MEDICAL_CERTIFICATES**: Uma consulta pode ter múltiplos atestados
- **APPOINTMENTS → VITAL_SIGNS**: Uma consulta pode ter múltiplos registros de sinais vitais
- **APPOINTMENTS → MEDICAL_DOCUMENTS**: Uma consulta pode ter múltiplos documentos
- **APPOINTMENTS → APPOINTMENT_LOGS**: Uma consulta pode ter múltiplos logs
- **USERS → TIMELINE_EVENTS**: Um usuário pode ter múltiplos eventos de timeline
- **PATIENTS → MEDICAL_RECORD_AUDIT_LOGS**: Um paciente pode ter múltiplos logs de auditoria

### Relacionamentos N:N
- **DOCTORS ↔ SPECIALIZATIONS**: Um médico pode ter múltiplas especializações (via `doctor_specialization`)

### Relacionamentos Auto-referenciais
- **CLINICAL_NOTES → CLINICAL_NOTES**: Anotações clínicas podem ter versões (parent_id)

## Observações Importantes

### Soft Deletes
As seguintes tabelas implementam soft deletes (exclusão lógica):
- DOCTORS, PATIENTS, APPOINTMENTS
- DOCTOR_SERVICE_LOCATIONS, DOCTOR_AVAILABILITY_SLOTS, DOCTOR_BLOCKED_DATES
- PRESCRIPTIONS, DIAGNOSES, EXAMINATIONS, CLINICAL_NOTES, MEDICAL_CERTIFICATES, MEDICAL_DOCUMENTS
- TIMELINE_EVENTS

### UUIDs
Todas as tabelas principais usam UUIDs como chaves primárias, exceto:
- VIDEO_CALL_ROOMS e VIDEO_CALL_EVENTS (usam IDs incrementais)

### Índices
- Todas as foreign keys possuem índices
- Campos frequentemente consultados possuem índices compostos
- Campos de busca e filtro possuem índices individuais

### JSON Fields
Vários campos usam JSON para armazenar dados estruturados:
- `availability_schedule` (DOCTORS)
- `language` (DOCTORS)
- `metadata` (APPOINTMENTS, PRESCRIPTIONS, EXAMINATIONS, CLINICAL_NOTES, MEDICAL_CERTIFICATES, VITAL_SIGNS, MEDICAL_DOCUMENTS, MEDICAL_RECORD_AUDIT_LOGS)
- `medications` (PRESCRIPTIONS)
- `results` (EXAMINATIONS)
- `tags` (CLINICAL_NOTES)
- `extra_data` (TIMELINE_EVENTS)
- `payload` (APPOINTMENT_LOGS)

### Auditoria
- **APPOINTMENT_LOGS**: Registra todos os eventos relacionados a consultas
- **MEDICAL_RECORD_AUDIT_LOGS**: Registra todas as ações em prontuários médicos (compliance LGPD)
