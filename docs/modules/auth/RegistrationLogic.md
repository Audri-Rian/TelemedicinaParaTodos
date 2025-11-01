# Lógica de Registro de Usuários - TelemedicinaParaTodos

## Visão Geral

O sistema de registro suporta diferentes tipos de usuários (pacientes e médicos) com perfis específicos, seguindo o padrão MVC do Laravel.

## Estrutura Lógica

### Controllers

#### PatientRegistrationController
- **Responsabilidade**: Gerencia registro de pacientes
- **Métodos**: create() (formulário), store() (processamento)
- **Redirecionamento**: `/patient/dashboard` após registro

#### DoctorRegistrationController  
- **Responsabilidade**: Gerencia registro de médicos
- **Métodos**: create() (formulário), store() (processamento)
- **Status**: Totalmente implementado
- **Redirecionamento**: `/doctor/dashboard` após registro

#### RegisteredUserController
- **Responsabilidade**: Registro genérico (fallback)
- **Métodos**: create() (seleção), store() (processamento)

### Requests

#### PatientRegistrationRequest
- **Responsabilidade**: Validação de dados de pacientes
- **Campos obrigatórios**: name, email, password, gender, date_of_birth, phone_number
- **Campos opcionais**: emergency_contact, medical_history, allergies, etc.
- **Recursos**: Mensagens em português, validação de unicidade

#### DoctorRegistrationRequest
- **Responsabilidade**: Validação de dados de pacientes
- **Campos obrigatórios**: name, email, password, gender, date_of_birth, phone_number
- **Campos opcionais**: emergency_contact, medical_history, allergies, etc.
- **Recursos**: Mensagens em português, validação de unicidade

#### LoginRequest
- **Responsabilidade**: Validação e autenticação
- **Recursos**: Rate limiting (5 tentativas), proteção contra força bruta

### Models

#### User
- **Responsabilidade**: Modelo base de usuário
- **Relacionamentos**: hasOne(Doctor), hasOne(Patient)
- **Métodos de Verificação**:
  - `isDoctor()`: bool - Verifica se possui perfil de médico
  - `isPatient()`: bool - Verifica se possui perfil de paciente
  - `getRole()`: string - Retorna 'doctor', 'patient' ou 'user'
- **Campos**: name, email, password (UUID como PK)

#### Patient
- **Responsabilidade**: Perfil específico de paciente
- **Relacionamento**: belongsTo(User)
- **Campos**: gender, date_of_birth, phone_number, medical_history, etc.
- **Recursos**: Soft deletes, scopes, accessors, mutators

#### Doctor
- **Responsabilidade**: Perfil específico de médico
- **Relacionamento**: belongsTo(User), belongsToMany(Specialization)
- **Campos**: crm, biography, license_number, license_expiry_date, status, availability_schedule, consultation_fee
- **Recursos**: Soft deletes, scopes (active, available), accessors, mutators
- **Status**: Totalmente implementado

### Rotas

#### Middleware `guest`:
- `/register/select` - Seleção de tipo de registro
- `/register/patient` - Formulário de paciente
- `/register/doctor` - Formulário de médico
- `/register` - Registro genérico

#### Middleware `auth`:
- Verificação de email
- Confirmação de senha  
- Logout

## Fluxo de Registro

### Pacientes
1. **Seleção**: Usuário escolhe tipo de registro
2. **Formulário**: Exibe campos específicos de paciente
3. **Validação**: PatientRegistrationRequest valida dados
4. **Processamento**: Cria User + Patient em transação
5. **Login**: Autenticação automática
6. **Redirecionamento**: `/patient/dashboard` (rota protegida por middleware)

### Médicos
1. **Seleção**: Usuário escolhe tipo de registro
2. **Formulário**: Exibe campos específicos de médico (name, email, password, crm, specializations)
3. **Validação**: DoctorRegistrationRequest valida dados
4. **Processamento**: Cria User + Doctor + associa Specializations em transação
5. **Login**: Autenticação automática
6. **Redirecionamento**: `/doctor/dashboard` (rota protegida por middleware)

## Diagrama da Estrutura

```mermaid
graph TD
    A[Usuário] --> B{Tipo de Registro}
    B -->|Paciente| C[PatientRegistrationController]
    B -->|Médico| D[DoctorRegistrationController]
    B -->|Genérico| E[RegisteredUserController]
    
    C --> F[PatientRegistrationRequest]
    F --> G[Validação]
    G --> H[Criação User + Patient]
    H --> I[Login Automático]
    I --> J[/patient/dashboard]
    
    D --> K[DoctorRegistrationRequest]
    K --> L[Validação]
    L --> M[Criação User + Doctor + Specializations]
    M --> I
    I --> N[/doctor/dashboard]
    
    E --> O[Registro Genérico]
    O --> P[User Básico]
    P --> I
    I --> Q[/ home]
    
    R[Login] --> S[LoginRequest]
    S --> T[Rate Limiting]
    T --> U[Autenticação]
    U --> V{Tipo de User}
    V -->|isDoctor| N
    V -->|isPatient| J
    V -->|Nenhum| Q
```
Frontend (Vue) → Composable → API Call → Laravel Controller → Database
     ↓              ↓           ↓              ↓              ↓
1. Validação    2. Rate      3. POST       4. Validação   5. User + Profile
   Client-side    Limit       /register/    Server-side    Created
                  Check       patient                      (Patient/Doctor)
                              ou doctor
                              
## Sistema de Diferenciação de Usuários

### Métodos de Verificação (Model User)

```php
// Verifica se é médico
$user->isDoctor(): bool

// Verifica se é paciente
$user->isPatient(): bool

// Retorna role como string
$user->getRole(): string // 'doctor', 'patient' ou 'user'
```

### Middlewares de Proteção

O sistema possui middlewares específicos para cada tipo de usuário:

- **EnsureUserIsDoctor**: Protege rotas exclusivas para médicos
- **EnsureUserIsPatient**: Protege rotas exclusivas para pacientes

**Aliases registrados:**
- `doctor` → `\App\Http\Middleware\EnsureUserIsDoctor::class`
- `patient` → `\App\Http\Middleware\EnsureUserIsPatient::class`

### Estrutura de Rotas

#### Rotas de Médicos
- Prefixo: `/doctor`
- Middleware: `['auth', 'verified', 'doctor']`
- Exemplos:
  - `GET /doctor/dashboard`
  - `GET /doctor/appointments`
  - `GET /doctor/consultations`

#### Rotas de Pacientes
- Prefixo: `/patient`
- Middleware: `['auth', 'verified', 'patient']`
- Exemplos:
  - `GET /patient/dashboard`
  - `GET /patient/search-consultations`
  - `GET /patient/health-records`

### Redirecionamento Inteligente

Após login ou registro, o sistema redireciona automaticamente baseado no tipo:

```php
if ($user->isDoctor()) {
    redirect()->route('doctor.dashboard');
} elseif ($user->isPatient()) {
    redirect()->route('patient.dashboard');
} else {
    redirect()->route('home');
}
```

## Documentação Relacionada

### Autenticação e Controle de Acesso
- **[Sistema de Controle de Acesso Backend](./RoleBasedAccess.md)** - Middlewares, rotas protegidas e arquitetura de roles (Backend)
- **[Sistema de Roteamento Frontend](./FrontendRouting.md)** - Composables, proteção de rotas e navegação dinâmica (Frontend)

### Documentação Geral
- **[Arquitetura do Sistema](../../Architecture/Arquitetura.md)** - Visão geral da arquitetura
- **[Diagrama de Banco de Dados](../../database/README.md)** - Estrutura das tabelas users, doctors e patients