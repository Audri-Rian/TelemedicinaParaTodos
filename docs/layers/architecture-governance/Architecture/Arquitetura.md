# Arquitetura do Sistema - Telemedicina Para Todos

## Visão Geral

Este documento descreve a arquitetura e estruturação do projeto **Telemedicina Para Todos**, um sistema de telemedicina que atende dois tipos de usuários distintos: **Médicos (Doctors)** e **Pacientes (Patients)**.

## Contexto do Projeto

O sistema é dividido em dois tipos de usuários com funcionalidades, páginas e estruturas distintas:

- **Doctors**: Interface e funcionalidades específicas para médicos
- **Patients**: Interface e funcionalidades específicas para pacientes
- **Shared**: Componentes, páginas e estruturas compartilhadas entre ambos os tipos de usuário

## Arquitetura de Comunicação Padrão

O sistema segue uma arquitetura em camadas bem definida:

```
[Migrations] → definem a estrutura do banco de dados
         ↘
[Eloquent Models] → schema, relacionamentos, casts, scopes, accessors
         ↘
[Services] → contém lógica de negócio, orquestra modelos
         ↘
[Controllers] → recebem requisições HTTP, interagem com Services
         ↘
[Events/Observers] → notificações e hooks de modelo
         ↘
[Database / Broadcasting / APIs externas]
```

### Responsabilidades das Camadas

#### Controllers
- Recebem as requisições HTTP
- Validação de entrada via Form Requests
- Interagem com Services
- Retornam respostas Inertia.js adequadas
- Organizados por domínio (Auth, Doctor, Patient, Settings, VideoCall)

#### Services
- Contêm a lógica de negócio principal
- Agregam regras de negócio
- Coordenam fluxos complexos
- Utilizam modelos diretamente
- Implementados:
  - `AppointmentService` - Lógica de agendamentos
  - `AvailabilityService` - Gestão de disponibilidade
  - `MedicalRecordService` - Gestão de prontuários (em `app/MedicalRecord/Application/Services/`)
  - `TimelineEventService` - Gestão de timeline profissional
  - `AvatarService` - Gestão de avatares
  - `ScheduleService` (Doctor) - Configuração de agenda do médico
  - `AvailabilityTimelineService` (Doctor) - Timeline de disponibilidade

#### Models (Eloquent)
- Schema e relacionamentos bem definidos
- Casts para tipos de dados
- Scopes para consultas reutilizáveis
- Accessors/Mutators para formatação
- Soft Deletes e UUIDs implementados
- **Models de prontuário** (Prescription, Diagnosis, Examination, etc.) estão em `app/MedicalRecord/Infrastructure/Persistence/Models/`

#### Events/Observers
- **Events**:
  - `RequestVideoCall` - Solicitação de videoconferência
  - `RequestVideoCallStatus` - Status da chamada
  - `AppointmentStatusChanged` - Mudança de status de consulta
  - `VideoCallRoomCreated` - Criação de sala de videoconferência
  - `VideoCallRoomExpired` - Expiração de sala
  - `VideoCallUserJoined` - Usuário entrou na chamada
  - `VideoCallUserLeft` - Usuário saiu da chamada
- **Observers**: `AppointmentsObserver` - Monitora mudanças em agendamentos
- **Jobs**:
  - `CleanupOldVideoCallEvents` - Limpeza de eventos antigos
  - `ExpireVideoCallRooms` - Expiração automática de salas
  - `UpdateAppointmentFromRoom` - Atualização de consulta a partir da sala
  - `GenerateMedicalRecordPDF` - Geração de PDF de prontuário
- Broadcasting em tempo real via Laravel Reverb

## Estrutura do Backend

### Bounded Contexts (DDD Light)

O backend utiliza **Bounded Contexts** para organizar o código por domínio. Cada contexto agrupa entidades, regras de negócio e infraestrutura relacionada, mantendo coesão e facilitando evolução futura (ex.: extração como pacote Laravel).

**Contextos implementados:**

| Contexto | Localização | Descrição |
|----------|-------------|-----------|
| **Medical Record** | `app/MedicalRecord/` | Prontuário eletrônico: prescrições, diagnósticos, exames, atestados, sinais vitais, documentos, auditoria |
| **Auth & Scheduling** | `app/Models/`, `app/Services/` | Usuários, médicos, pacientes, agendamentos, disponibilidade (estrutura legada, migração futura) |
| **Consultation** | `app/Models/`, `app/Events/` | Videoconferência, salas, eventos (estrutura legada, migração futura) |

**Estrutura de um Bounded Context (padrão DDD Light):**

```
app/{Contexto}/
├── Domain/                      # Núcleo do domínio (regras puras)
│   ├── ValueObjects/            # Objetos de valor imutáveis (ex: CID10Code)
│   ├── Events/                  # Eventos de domínio (opcional)
│   └── Exceptions/              # Exceções específicas do domínio
├── Application/                 # Casos de uso e orquestração
│   └── Services/                # Services que orquestram o fluxo
└── Infrastructure/              # Detalhes técnicos
    ├── Persistence/Models/      # Eloquent Models (ORM)
    └── ExternalServices/        # Integrações externas (ex: ICP-Brasil)
```

**Controllers e rotas** permanecem em `app/Http/Controllers/` (organizados por Doctor/Patient) e **chamam** os Services dos contextos. Não são movidos para dentro dos contextos.

Consulte **[BoundedContexts.md](BoundedContexts.md)** para o guia completo de uso e convenções.

### Organização por Domínio
O backend combina **Bounded Contexts** (para domínios ricos) com a estrutura padrão do Laravel:

```
app/
├── MedicalRecord/               # BOUNDED CONTEXT - Prontuário Eletrônico
│   ├── Domain/
│   │   ├── ValueObjects/        # CID10Code, PrescriptionItem, VitalSignValue
│   │   └── Exceptions/          # PrescriptionWithoutSignatureException
│   ├── Application/
│   │   └── Services/
│   │       └── MedicalRecordService.php
│   └── Infrastructure/
│       ├── Persistence/Models/   # Prescription, Diagnosis, Examination,
│       │                        # ClinicalNote, MedicalCertificate, VitalSign,
│       │                        # MedicalDocument, MedicalRecordAuditLog
│       └── ExternalServices/     # (futuro: ICPBrasilAdapter)
├── Http/
│   ├── Controllers/
│   │   ├── Api/AuthController.php
│   │   ├── Auth/
│   │   ├── Doctor/
│   │   │   ├── DoctorPatientMedicalRecordController.php  # usa MedicalRecordService
│   │   │   └── ...
│   │   ├── Patient/
│   │   │   ├── PatientMedicalRecordController.php
│   │   │   └── ...
│   │   ├── MedicalRecordDocumentController.php
│   │   ├── VideoCall/
│   │   └── ...
│   ├── Middleware/
│   └── Requests/
├── Models/                      # Models compartilhados / outros contextos
│   ├── User.php
│   ├── Doctor.php
│   ├── Patient.php
│   ├── Appointments.php
│   ├── AppointmentLog.php
│   ├── Specialization.php
│   ├── ServiceLocation.php
│   ├── AvailabilitySlot.php
│   ├── Doctor/BlockedDate.php
│   ├── VideoCallRoom.php
│   ├── VideoCallEvent.php
│   ├── TimelineEvent.php
│   └── ...
├── Services/
│   ├── AppointmentService.php
│   ├── AvailabilityService.php
│   ├── TimelineEventService.php
│   ├── AvatarService.php
│   └── Doctor/
│       ├── ScheduleService.php
│       └── AvailabilityTimelineService.php
├── Events/
│   ├── RequestVideoCall.php              # Evento de solicitação de chamada
│   ├── RequestVideoCallStatus.php        # Evento de status da chamada
│   ├── AppointmentStatusChanged.php      # Mudança de status de consulta
│   ├── VideoCallRoomCreated.php          # Criação de sala de videoconferência
│   ├── VideoCallRoomExpired.php          # Expiração de sala
│   ├── VideoCallUserJoined.php          # Usuário entrou na chamada
│   └── VideoCallUserLeft.php            # Usuário saiu da chamada
├── Observers/
│   └── AppointmentsObserver.php          # Observer para agendamentos
├── Jobs/
│   ├── CleanupOldVideoCallEvents.php     # Limpeza de eventos antigos
│   ├── ExpireVideoCallRooms.php          # Expiração automática de salas
│   ├── UpdateAppointmentFromRoom.php     # Atualização de consulta
│   └── GenerateMedicalRecordPDF.php      # Geração de PDF de prontuário
├── Policies/
│   ├── AppointmentPolicy.php            # Políticas de consultas
│   ├── MedicalRecordPolicy.php           # Políticas de prontuários
│   ├── TimelineEventPolicy.php           # Políticas de timeline
│   ├── VideoCallPolicy.php               # Políticas de videoconferência
│   └── Doctor/                           # Políticas específicas do médico
│       ├── DoctorPolicy.php
│       ├── DoctorSchedulePolicy.php
│       └── DoctorPatientPolicy.php
└── Providers/
    └── AppServiceProvider.php            # Service Provider principal
```

### Stack Tecnológica Backend

- **Laravel 12** - Framework PHP principal
- **PHP 8.2+** - Linguagem de programação
- **MySQL/SQLite** - Banco de dados
- **Laravel Sanctum** - Autenticação API
- **Laravel Reverb** - Broadcasting WebSockets
- **Laravel Wayfinder** - Sistema de roteamento avançado
- **Inertia.js Laravel** - Integração SPA

### Padrões de Código

- **PSR-12**: Seguir padrões de codificação PSR-12
- **Nomenclatura**: Usar inglês consistente em todo o projeto
- **Migrations**: Sempre incluir timestamps
- **UUIDs**: Identificadores únicos para modelos
- **Soft Deletes**: Exclusão lógica implementada
- **Testes**: Todo método crítico deve ter teste unitário

## Estrutura do Frontend

### SPA com Inertia.js
O frontend segue uma estrutura de **Single Page Application** usando Inertia.js para integração com Laravel:

```
resources/js/
├── components/
│   ├── ui/                    # Biblioteca de componentes UI (Reka UI)
│   │   ├── alert/
│   │   ├── avatar/
│   │   ├── button/
│   │   ├── card/
│   │   ├── dialog/
│   │   ├── dropdown-menu/
│   │   ├── input/
│   │   ├── sidebar/
│   │   └── ...
│   ├── AppContent.vue         # Conteúdo principal da aplicação
│   ├── AppHeader.vue          # Cabeçalho da aplicação
│   ├── AppLogo.vue            # Logo da aplicação
│   ├── AppShell.vue           # Shell principal
│   ├── AppSidebar.vue         # Barra lateral
│   ├── Breadcrumbs.vue        # Navegação breadcrumb
│   ├── NavMain.vue            # Navegação principal
│   ├── NavUser.vue            # Menu do usuário
│   └── UserInfo.vue           # Informações do usuário
├── pages/
│   ├── auth/                  # Páginas de autenticação
│   │   ├── Login.vue
│   │   ├── RegisterDoctor.vue
│   │   ├── RegisterPatient.vue
│   │   └── ...
│   ├── Doctor/                # Páginas do médico
│   │   ├── Dashboard.vue
│   │   ├── Consultations.vue
│   │   └── ...
│   ├── Patient/               # Páginas do paciente
│   │   ├── Dashboard.vue
│   │   ├── SearchConsultations.vue
│   │   └── HealthRecords.vue
│   └── settings/              # Configurações
│       ├── Profile.vue
│       ├── Password.vue
│       └── Appearance.vue
├── layouts/
│   ├── AppLayout.vue          # Layout principal
│   ├── AuthLayout.vue         # Layout de autenticação
│   ├── app/                   # Componentes de layout
│   │   ├── AppHeaderLayout.vue
│   │   └── AppSidebarLayout.vue
│   ├── auth/                  # Layouts de autenticação
│   │   ├── AuthCardLayout.vue
│   │   ├── AuthSimpleLayout.vue
│   │   └── AuthSplitLayout.vue
│   └── settings/Layout.vue    # Layout de configurações
├── composables/               # Composables Vue 3
│   ├── Doctor/                # Lógica específica do médico
│   │   ├── useDoctorRegistration.ts
│   │   ├── useDoctorProfileUpdate.ts
│   │   └── useDoctorFormValidation.ts
│   ├── Patient/               # Lógica específica do paciente
│   │   ├── usePatientRegistration.ts
│   │   ├── usePatientProfileUpdate.ts
│   │   └── usePatientFormValidation.ts
│   ├── useAuth.ts             # Autenticação
│   ├── useAuthGuard.ts        # Guard de autenticação
│   └── useAppearance.ts       # Tema da aplicação
├── types/                     # Definições TypeScript
├── lib/                       # Utilitários e configurações
└── wayfinder/                 # Sistema de roteamento Laravel Wayfinder
```

### Tecnologias Frontend
- **Vue.js 3** com Composition API
- **TypeScript** para tipagem estática
- **Inertia.js** para integração SPA com Laravel
- **Tailwind CSS 4** para estilização
- **Reka UI** como biblioteca de componentes
- **Lucide Vue** para ícones
- **PeerJS** para videoconferência WebRTC
- **VueUse** para utilitários Vue

## Sistema de Eventos e Broadcasting

### Events (Eventos)
O sistema utiliza eventos Laravel para comunicação em tempo real:

- **RequestVideoCall**: Disparado quando um usuário solicita uma videoconferência
- **RequestVideoCallStatus**: Disparado quando há mudança no status da chamada

### Observers
Implementados para hooks de modelo:

- **AppointmentsObserver**: Monitora mudanças em agendamentos para logs e notificações

### Broadcasting
- **Laravel Reverb**: Servidor WebSocket para comunicação em tempo real
- **Laravel Echo**: Cliente JavaScript para escutar eventos
- **Pusher**: Driver de broadcasting (configurável)

## Middleware e Autenticação

### Middleware Implementado
- **Autenticação**: Laravel Sanctum para API e sessões
- **Redirecionamento**: UserRedirectService para direcionar usuários por tipo
- **Guards**: useAuthGuard.ts no frontend para proteção de rotas

### Fluxo de Autenticação
1. **Login**: Autenticação via email/senha
2. **Registro**: Separação entre Doctor e Patient
3. **Redirecionamento**: Baseado no tipo de usuário (isDoctor/isPatient)
4. **Proteção**: Middleware e guards para rotas protegidas

## Fluxo de Desenvolvimento

### 1. Migrations
Definir estrutura do banco de dados com migrations do Laravel
- Incluir timestamps obrigatórios
- Usar UUIDs para chaves primárias
- Implementar Soft Deletes quando necessário

### 2. Models
Criar modelos Eloquent com relacionamentos, casts, scopes e accessors
- Implementar relacionamentos 1:1 (User → Doctor/Patient)
- Definir casts para tipos de dados específicos
- Criar scopes para consultas reutilizáveis
- Implementar accessors/mutators para formatação

### 3. Services
Implementar lógica de negócio nos services
- `AppointmentService` - Lógica de agendamentos
- `AvailabilityService` - Gestão de disponibilidade e slots
- `MedicalRecordService` - Gestão completa de prontuários médicos
- `TimelineEventService` - Gestão de timeline profissional
- `AvatarService` - Upload e gestão de avatares
- `ScheduleService` (Doctor) - Configuração completa de agenda
- `AvailabilityTimelineService` (Doctor) - Timeline de disponibilidade

### 4. Controllers
Criar controllers organizados por domínio
- `Auth/` - Autenticação e registro (Doctor e Patient separados)
- `Doctor/` - Funcionalidades específicas do médico (dashboard, consultas, pacientes, agenda, prontuários)
- `Patient/` - Funcionalidades específicas do paciente (dashboard, busca, agendamento, histórico, prontuários)
- `Settings/` - Configurações do usuário (perfil, senha, relatórios de bug)
- `VideoCall/` - Videoconferência
- Controllers compartilhados: `AppointmentsController`, `TimelineEventController`, `MedicalRecordDocumentController`

### 5. Events, Observers e Jobs
Implementar eventos para comunicação em tempo real
- **Events**: Videoconferência, mudanças de status de consulta, criação/expiração de salas
- **Observers**: Hooks de modelo (AppointmentsObserver)
- **Jobs**: Limpeza automática, expiração de salas, geração de PDFs, atualização de consultas

### 6. Frontend (Vue.js + Inertia.js)
Desenvolver componentes e páginas
- Componentes UI reutilizáveis (Reka UI)
- Páginas organizadas por domínio
- Composables para lógica reutilizável
- Layouts específicos por contexto

### 7. Broadcasting
Configurar comunicação em tempo real
- Laravel Reverb para WebSockets
- Laravel Echo no frontend
- Eventos para videoconferência

### 8. Testes
Implementar testes unitários e de integração
- Testes para métodos críticos dos Models
- Testes de Feature para fluxos completos
- Testes de autenticação e autorização

## Convenções de Nomenclatura

### Backend
- **Controllers**: 
  - `Doctor/DoctorDashboardController`, `Doctor/DoctorConsultationsController`, `Doctor/DoctorScheduleController`
  - `Patient/PatientDashboardController`, `Patient/ScheduleConsultationController`
  - `VideoCall/VideoCallController`, `TimelineEventController`, `MedicalRecordDocumentController`
- **Services**: 
  - `AppointmentService`, `AvailabilityService`, `MedicalRecordService`
  - `TimelineEventService`, `AvatarService`, `Doctor/ScheduleService`
- **Models**: 
  - `User`, `Doctor`, `Patient`, `Appointments`, `Specialization` (em `app/Models/`)
  - `ServiceLocation`, `AvailabilitySlot`, `Doctor/BlockedDate`
  - `VideoCallRoom`, `VideoCallEvent`, `TimelineEvent`
  - Prontuário: `Prescription`, `Diagnosis`, `Examination`, `ClinicalNote`, `MedicalCertificate`, `VitalSign`, `MedicalDocument`, `MedicalRecordAuditLog` (em `app/MedicalRecord/Infrastructure/Persistence/Models/`)
- **Events**: 
  - `RequestVideoCall`, `RequestVideoCallStatus`, `AppointmentStatusChanged`
  - `VideoCallRoomCreated`, `VideoCallRoomExpired`, `VideoCallUserJoined`, `VideoCallUserLeft`
- **Observers**: `AppointmentsObserver`
- **Jobs**: 
  - `CleanupOldVideoCallEvents`, `ExpireVideoCallRooms`, `UpdateAppointmentFromRoom`, `GenerateMedicalRecordPDF`
- **Policies**: 
  - `AppointmentPolicy`, `MedicalRecordPolicy`, `TimelineEventPolicy`, `VideoCallPolicy`
  - `Doctor/DoctorPolicy`, `Doctor/DoctorSchedulePolicy`, `Doctor/DoctorPatientPolicy`

### Frontend
- **Components**: `AppHeader.vue`, `AppSidebar.vue`, `NavMain.vue`
- **Pages**: `Doctor/Dashboard.vue`, `Patient/Dashboard.vue`, `auth/Login.vue`
- **Layouts**: `AppLayout.vue`, `AuthLayout.vue`
- **Composables**: `useDoctorRegistration.ts`, `usePatientRegistration.ts`

## Guia de Uso da Arquitetura (daqui para frente)

### Quando criar código em um Bounded Context

1. **Novas entidades de prontuário** (ex.: novo tipo de documento médico)
   - Criar Model em `app/MedicalRecord/Infrastructure/Persistence/Models/`
   - Namespace: `App\MedicalRecord\Infrastructure\Persistence\Models`
   - Adicionar migration em `database/migrations/`
   - Registrar no `MedicalRecordService` se fizer parte do fluxo de prontuário

2. **Novas regras de validação de domínio** (ex.: formato de código, limites numéricos)
   - Criar Value Object em `app/MedicalRecord/Domain/ValueObjects/`
   - Usar no Service ou FormRequest antes de persistir

3. **Integrações externas** (ex.: ICP-Brasil para assinatura digital)
   - Criar Adapter em `app/MedicalRecord/Infrastructure/ExternalServices/`
   - O Service chama o Adapter; o Adapter encapsula a comunicação com a API externa

4. **Exceções de domínio** (ex.: prescrição sem assinatura válida)
   - Criar em `app/MedicalRecord/Domain/Exceptions/`
   - Lançar no Service quando a regra de negócio for violada

### Quando criar código fora dos Bounded Contexts

- **Controllers**: Sempre em `app/Http/Controllers/` (Doctor/, Patient/, etc.)
- **Form Requests**: Em `app/Http/Requests/`
- **Events Laravel** (PrescriptionIssued, etc.): Em `app/Events/` — mantidos para compatibilidade com listeners e broadcasting
- **Observers**: Em `app/Observers/` — atualizar o `use` do model para o namespace do contexto
- **Models compartilhados**: User, Doctor, Patient, Appointments permanecem em `app/Models/` até migração futura

### Fluxo de dependências

```
Controller (Http) → Service (Application) → Models (Infrastructure) + Value Objects (Domain)
                       ↓
                 ExternalServices (Infrastructure) [quando houver integração]
```

- **Controllers** nunca importam Models de prontuário diretamente; usam apenas o `MedicalRecordService`
- **Services** de um contexto podem usar Models de `app/Models/` (User, Doctor, Patient, Appointments) para relacionamentos
- **Value Objects** são usados no Service ou FormRequest para validar antes de persistir

### Novos Bounded Contexts (futuro)

Ao criar um novo contexto (ex.: Scheduling, Consultation, Auth):

1. Criar pasta `app/{NomeContexto}/` com Domain, Application, Infrastructure
2. Mover models e services relacionados
3. Atualizar imports em Controllers, Observers, Events
4. Atualizar Factories com `$model` apontando para o novo namespace
5. Registrar Observers no AppServiceProvider com a classe do novo namespace
6. Atualizar esta documentação

## 🔗 Referências Cruzadas

### Documentação Relacionada
- **[📋 Visão Geral](../../../index/VisaoGeral.md)** - Índice central da documentação
- **[📦 Bounded Contexts](BoundedContexts.md)** - Guia detalhado de contextos e convenções
- **[📊 Matriz de Rastreabilidade](../../../index/MatrizRequisitos.md)** - Mapeamento requisito → implementação
- **[📚 Glossário](../../../index/Glossario.md)** - Definições de termos técnicos
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de negócio e compliance
- **[⚙️ Lógica de Consultas](../../../modules/appointments/AppointmentsLogica.md)** - Regras de agendamento
- **[🔐 Autenticação](../../../modules/auth/RegistrationLogic.md)** - Fluxos de registro e login

### Implementações Relacionadas
- **[Controllers](../../app/Http/Controllers/)** - Camada de apresentação
- **[Services](../../app/Services/)** - Camada de lógica de negócio (Appointment, Availability, etc.)
- **[MedicalRecord Context](../../app/MedicalRecord/)** - Bounded Context de prontuário
- **[Models](../../app/Models/)** - Entidades compartilhadas (User, Doctor, Patient, Appointments, etc.)
- **[Events](../../app/Events/)** - Eventos para comunicação em tempo real
- **[Observers](../../app/Observers/)** - Hooks de modelo
- **[Database Migrations](../../../../database/migrations/)** - Estrutura do banco
- **[Frontend Components](../../resources/js/components/)** - Componentes Vue.js
- **[Frontend Pages](../../resources/js/pages/)** - Páginas da aplicação
- **[Composables](../../resources/js/composables/)** - Lógica reutilizável Vue

### Termos do Glossário
- **[DTO](../../../index/Glossario.md#d)** - Data Transfer Object
- **[Service](../../../index/Glossario.md#s)** - Camada de lógica de negócio
- **[Eloquent](../../../index/Glossario.md#e)** - ORM do Laravel
- **[Inertia.js](../../../index/Glossario.md#i)** - Integração Laravel + Vue.js

---

---

*Última atualização: Janeiro 2026*
*Versão: 3.0 - Bounded Contexts*

*Este documento deve ser atualizado conforme a evolução do projeto.*

