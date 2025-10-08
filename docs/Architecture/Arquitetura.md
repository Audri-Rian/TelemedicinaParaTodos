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
- Implementados: AppointmentService, UserRedirectService

#### Models (Eloquent)
- Schema e relacionamentos bem definidos
- Casts para tipos de dados
- Scopes para consultas reutilizáveis
- Accessors/Mutators para formatação
- Soft Deletes e UUIDs implementados

#### Events/Observers
- Events: RequestVideoCall, RequestVideoCallStatus
- Observers: AppointmentsObserver
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
│   │   ├── Doctor/DashboardController.php   # Dashboard do médico
│   │   ├── Patient/DashboardController.php  # Dashboard do paciente
│   │   ├── Settings/                       # Configurações do usuário
│   │   │   ├── PasswordController.php
│   │   │   └── ProfileController.php
│   │   ├── VideoCall/VideoCallController.php # Videoconferência
│   │   └── SpecializationController.php    # Especializações médicas
│   ├── Middleware/                         # Middleware personalizado
│   └── Requests/                          # Form Requests de validação
├── Models/
│   ├── User.php                           # Modelo base de usuário
│   ├── Doctor.php                         # Modelo do médico
│   ├── Patient.php                        # Modelo do paciente
│   ├── Appointments.php                   # Agendamentos
│   ├── AppointmentLog.php                 # Log de agendamentos
│   └── Specialization.php                 # Especializações
├── Services/
│   ├── AppointmentService.php             # Lógica de agendamentos
│   └── UserRedirectService.php            # Redirecionamento por tipo de usuário
├── Events/
│   ├── RequestVideoCall.php              # Evento de solicitação de chamada
│   └── RequestVideoCallStatus.php        # Evento de status da chamada
├── Observers/
│   └── AppointmentsObserver.php          # Observer para agendamentos
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
│   ├── Doctor/Dashboard.vue   # Dashboard do médico
│   ├── Patient/               # Páginas do paciente
│   │   ├── Dashboard.vue
│   │   ├── Appointments.vue
│   │   ├── Consultations.vue
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
- AppointmentService para lógica de agendamentos
- UserRedirectService para redirecionamento por tipo

### 4. Controllers
Criar controllers organizados por domínio
- Auth/ para autenticação e registro
- Doctor/ e Patient/ para funcionalidades específicas
- Settings/ para configurações do usuário
- VideoCall/ para videoconferência

### 5. Events e Observers
Implementar eventos para comunicação em tempo real
- Events para videoconferência
- Observers para hooks de modelo

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
- **Controllers**: `Doctor/DashboardController`, `Patient/DashboardController`, `VideoCall/VideoCallController`
- **Services**: `AppointmentService`, `UserRedirectService`
- **Models**: `Doctor`, `Patient`, `User`, `Appointments`, `Specialization`
- **Events**: `RequestVideoCall`, `RequestVideoCallStatus`
- **Observers**: `AppointmentsObserver`

### Frontend
- **Components**: `AppHeader.vue`, `AppSidebar.vue`, `NavMain.vue`
- **Pages**: `Doctor/Dashboard.vue`, `Patient/Dashboard.vue`, `auth/Login.vue`
- **Layouts**: `AppLayout.vue`, `AuthLayout.vue`
- **Composables**: `useDoctorRegistration.ts`, `usePatientRegistration.ts`

## 🔗 Referências Cruzadas

### Documentação Relacionada
- **[📋 Visão Geral](../index/VisaoGeral.md)** - Índice central da documentação
- **[📊 Matriz de Rastreabilidade](../index/MatrizRequisitos.md)** - Mapeamento requisito → implementação
- **[📚 Glossário](../index/Glossario.md)** - Definições de termos técnicos
- **[📜 Regras do Sistema](../requirements/SystemRules.md)** - Regras de negócio e compliance
- **[⚙️ Lógica de Consultas](../modules/appointments/AppointmentsLogica.md)** - Regras de agendamento
- **[🔐 Autenticação](../modules/auth/RegistrationLogic.md)** - Fluxos de registro e login

### Implementações Relacionadas
- **[Controllers](../../app/Http/Controllers/)** - Camada de apresentação
- **[Services](../../app/Services/)** - Camada de lógica de negócio
- **[Models](../../app/Models/)** - Entidades de domínio
- **[Events](../../app/Events/)** - Eventos para comunicação em tempo real
- **[Observers](../../app/Observers/)** - Hooks de modelo
- **[Database Migrations](../../database/migrations/)** - Estrutura do banco
- **[Frontend Components](../../resources/js/components/)** - Componentes Vue.js
- **[Frontend Pages](../../resources/js/pages/)** - Páginas da aplicação
- **[Composables](../../resources/js/composables/)** - Lógica reutilizável Vue

### Termos do Glossário
- **[DTO](../index/Glossario.md#d)** - Data Transfer Object
- **[Service](../index/Glossario.md#s)** - Camada de lógica de negócio
- **[Eloquent](../index/Glossario.md#e)** - ORM do Laravel
- **[Inertia.js](../index/Glossario.md#i)** - Integração Laravel + Vue.js

---

*Este documento deve ser atualizado conforme a evolução do projeto.*

