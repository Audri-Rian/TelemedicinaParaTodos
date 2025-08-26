# Diagrama do Banco de Dados - Telemedicina Para Todos

## Diagrama Entidade-Relacionamento (ERD)

```mermaid
erDiagram
    USERS {
        int id PK
        string first_name
        string last_name
        string email
        string password
        string user_type "doctor|patient"
        timestamp created_at
        timestamp updated_at
    }

    DOCTORS {
        int id PK
        int user_id FK
        string professional_statement
        date practicing_from
        timestamp created_at
        timestamp updated_at
    }

    PATIENTS {
        int id PK
        int user_id FK
        string contact_number
        timestamp created_at
        timestamp updated_at
    }

    SPECIALIZATIONS {
        int id PK
        string name
        string description
        timestamp created_at
        timestamp updated_at
    }

    OFFICES {
        int id PK
        string name
        string address
        string type "hospital|clinic"
        int hospital_affiliation_id FK "nullable"
        timestamp created_at
        timestamp updated_at
    }

    AVAILABILITY_SLOTS {
        int id PK
        int doctor_id FK
        int office_id FK
        date slot_date
        time start_time
        time end_time
        string status "available|booked|cancelled"
        timestamp created_at
        timestamp updated_at
    }

    APPOINTMENTS {
        int id PK
        int doctor_id FK
        int patient_id FK
        int office_id FK
        int availability_slot_id FK
        date appointment_date
        time appointment_time
        string status "scheduled|completed|cancelled|no_show"
        text notes
        timestamp created_at
        timestamp updated_at
    }

    PAYMENTS {
        int id PK
        int appointment_id FK
        decimal amount
        string status "pending|completed|failed|refunded"
        string payment_method
        timestamp payment_date
        timestamp created_at
        timestamp updated_at
    }

    PRESCRIPTIONS {
        int id PK
        int appointment_id FK
        text medication
        text dosage
        text instructions
        date prescribed_date
        timestamp created_at
        timestamp updated_at
    }

    CLIENT_REVIEWS {
        int id PK
        int patient_id FK
        int doctor_id FK
        int appointment_id FK
        boolean is_review_anonymous
        int wait_time_rating
        int doctor_rating
        int overall_rating
        text review_text
        timestamp created_at
        timestamp updated_at
    }

    %% Relacionamentos
    USERS ||--o{ DOCTORS : "has_one"
    USERS ||--o{ PATIENTS : "has_one"
    
    DOCTORS ||--o{ AVAILABILITY_SLOTS : "has_many"
    DOCTORS ||--o{ APPOINTMENTS : "has_many"
    DOCTORS ||--o{ CLIENT_REVIEWS : "has_many"
    
    PATIENTS ||--o{ APPOINTMENTS : "has_many"
    PATIENTS ||--o{ CLIENT_REVIEWS : "has_many"
    
    OFFICES ||--o{ AVAILABILITY_SLOTS : "has_many"
    OFFICES ||--o{ APPOINTMENTS : "has_many"
    
    AVAILABILITY_SLOTS ||--o{ APPOINTMENTS : "has_many"
    
    APPOINTMENTS ||--o{ PAYMENTS : "has_many"
    APPOINTMENTS ||--o{ PRESCRIPTIONS : "has_many"
    APPOINTMENTS ||--o{ CLIENT_REVIEWS : "has_many"
    
    %% Tabelas pivot para relacionamentos N:N
    DOCTOR_SPECIALIZATIONS {
        int doctor_id FK
        int specialization_id FK
        timestamp created_at
    }
    
    DOCTORS ||--o{ DOCTOR_SPECIALIZATIONS : "has_many"
    SPECIALIZATIONS ||--o{ DOCTOR_SPECIALIZATIONS : "has_many"
```

## Estrutura das Tabelas

### Tabelas Principais

1. **USERS** - Usuários base do sistema (médicos e pacientes)
2. **DOCTORS** - Informações específicas dos médicos
3. **PATIENTS** - Informações específicas dos pacientes
4. **SPECIALIZATIONS** - Especializações médicas
5. **OFFICES** - Locais de atendimento (hospitais/clínicas)
6. **AVAILABILITY_SLOTS** - Horários disponíveis dos médicos
7. **APPOINTMENTS** - Agendamentos de consultas
8. **PAYMENTS** - Pagamentos das consultas
9. **PRESCRIPTIONS** - Prescrições médicas
10. **CLIENT_REVIEWS** - Avaliações dos pacientes

### Relacionamentos

- **1:1** entre USERS e DOCTORS/PATIENTS
- **1:N** entre DOCTORS e APPOINTMENTS/AVAILABILITY_SLOTS
- **1:N** entre PATIENTS e APPOINTMENTS
- **1:N** entre APPOINTMENTS e PAYMENTS/PRESCRIPTIONS
- **N:N** entre DOCTORS e SPECIALIZATIONS (via tabela pivot)
- **1:N** entre OFFICES e AVAILABILITY_SLOTS/APPOINTMENTS

### Observações

- O sistema usa uma tabela USERS central para autenticação
- DOCTORS e PATIENTS herdam de USERS (polimorfismo)
- AVAILABILITY_SLOTS controla a disponibilidade dos médicos
- APPOINTMENTS é a entidade central que conecta médicos, pacientes e pagamentos
- CLIENT_REVIEWS é opcional e permite avaliações anônimas
