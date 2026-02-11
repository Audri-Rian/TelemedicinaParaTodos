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
  - `MedicalRecordService` - Gestão de prontuários médicos
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

### Organização por Domínio
O backend foi estruturado seguindo uma abordagem **DDD Light**, organizando as responsabilidades por domínio dentro das pastas padrão do Laravel:

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/AuthController.php           # API de autenticação
│   │   ├── Auth/                           # Controllers de autenticação
│   │   │   ├── AuthenticatedSessionController.php
│   │   │   ├── DoctorRegistrationController.php
│   │   │   ├── PatientRegistrationController.php
│   │   │   └── ...
│   │   ├── Doctor/                         # Controllers do médico
│   │   │   ├── DoctorDashboardController.php
│   │   │   ├── DoctorAppointmentsController.php
│   │   │   ├── DoctorAvailabilityController.php
│   │   │   ├── DoctorConsultationsController.php
│   │   │   ├── DoctorConsultationDetailController.php
│   │   │   ├── DoctorMessagesController.php
│   │   │   ├── DoctorHistoryController.php
│   │   │   ├── DoctorPatientsController.php
│   │   │   ├── DoctorDocumentsController.php
│   │   │   ├── PatientDetailsController.php
│   │   │   ├── DoctorPatientMedicalRecordController.php
│   │   │   ├── DoctorScheduleController.php
│   │   │   ├── DoctorServiceLocationController.php
│   │   │   ├── DoctorAvailabilitySlotController.php
│   │   │   └── DoctorBlockedDateController.php
│   │   ├── Patient/                        # Controllers do paciente
│   │   │   ├── PatientDashboardController.php
│   │   │   ├── PatientSearchConsultationsController.php
│   │   │   ├── ScheduleConsultationController.php
│   │   │   ├── DoctorPerfilController.php
│   │   │   ├── PatientMessagesController.php
│   │   │   ├── PatientVideoCallController.php
│   │   │   ├── PatientHistoryConsultationsController.php
│   │   │   ├── PatientConsultationDetailsController.php
│   │   │   ├── PatientNextConsultationController.php
│   │   │   └── PatientMedicalRecordController.php
│   │   ├── Settings/                       # Configurações do usuário
│   │   │   ├── PasswordController.php
│   │   │   ├── ProfileController.php
│   │   │   └── BugReportController.php
│   │   ├── VideoCall/VideoCallController.php # Videoconferência
│   │   ├── MedicalRecordDocumentController.php # Documentos médicos
│   │   ├── TimelineEventController.php     # Timeline events
│   │   ├── SpecializationController.php    # Especializações médicas
│   │   ├── AppointmentsController.php      # Consultas (compartilhado)
│   │   ├── ConsultationsController.php     # Consultas
│   │   ├── HealthController.php            # Saúde
│   │   ├── AvatarController.php            # Avatares
│   │   ├── TermsOfServiceController.php    # Termos de serviço
│   │   └── PrivacyPolicyController.php     # Política de privacidade
│   ├── Middleware/                         # Middleware personalizado
│   └── Requests/                          # Form Requests de validação
├── Models/
│   ├── User.php                           # Modelo base de usuário
│   ├── Doctor.php                         # Modelo do médico
│   ├── Patient.php                        # Modelo do paciente
│   ├── Appointments.php                   # Agendamentos
│   ├── AppointmentLog.php                 # Log de agendamentos
│   ├── Specialization.php                 # Especializações
│   ├── ServiceLocation.php                # Locais de atendimento
│   ├── AvailabilitySlot.php               # Slots de disponibilidade
│   ├── Doctor/BlockedDate.php             # Datas bloqueadas
│   ├── Prescription.php                   # Prescrições médicas
│   ├── Diagnosis.php                      # Diagnósticos (CID-10)
│   ├── Examination.php                    # Exames solicitados
│   ├── ClinicalNote.php                   # Anotações clínicas
│   ├── MedicalCertificate.php             # Atestados médicos
│   ├── VitalSign.php                      # Sinais vitais
│   ├── MedicalDocument.php                # Documentos médicos
│   ├── MedicalRecordAuditLog.php          # Logs de auditoria
│   ├── VideoCallRoom.php                  # Salas de videoconferência
│   ├── VideoCallEvent.php                 # Eventos de videoconferência
│   └── TimelineEvent.php                  # Eventos de timeline
├── Services/
│   ├── AppointmentService.php             # Lógica de agendamentos
│   ├── AvailabilityService.php            # Gestão de disponibilidade
│   ├── MedicalRecordService.php           # Gestão de prontuários
│   ├── TimelineEventService.php           # Gestão de timeline
│   ├── AvatarService.php                  # Gestão de avatares
│   └── Doctor/
│       ├── ScheduleService.php            # Configuração de agenda
│       └── AvailabilityTimelineService.php # Timeline de disponibilidade
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
  - `User`, `Doctor`, `Patient`, `Appointments`, `Specialization`
  - `ServiceLocation`, `AvailabilitySlot`, `Doctor/BlockedDate`
  - `Prescription`, `Diagnosis`, `Examination`, `ClinicalNote`, `MedicalCertificate`, `VitalSign`, `MedicalDocument`
  - `MedicalRecordAuditLog`, `VideoCallRoom`, `VideoCallEvent`, `TimelineEvent`
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

## 🔗 Referências Cruzadas

### Documentação Relacionada
- **[📋 Visão Geral](../../../index/VisaoGeral.md)** - Índice central da documentação
- **[📊 Matriz de Rastreabilidade](../../../index/MatrizRequisitos.md)** - Mapeamento requisito → implementação
- **[📚 Glossário](../../../index/Glossario.md)** - Definições de termos técnicos
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de negócio e compliance
- **[⚙️ Lógica de Consultas](../../../modules/appointments/AppointmentsLogica.md)** - Regras de agendamento
- **[🔐 Autenticação](../../../modules/auth/RegistrationLogic.md)** - Fluxos de registro e login

### Implementações Relacionadas
- **[Controllers](../../app/Http/Controllers/)** - Camada de apresentação
- **[Services](../../app/Services/)** - Camada de lógica de negócio
- **[Models](../../app/Models/)** - Entidades de domínio
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

*Última atualização: Janeiro 2025*
*Versão: 2.0*

*Este documento deve ser atualizado conforme a evolução do projeto.*

