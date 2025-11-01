# Visão Geral do Sistema de Autenticação - TelemedicinaParaTodos

## 📋 Índice de Documentação

Este é o guia central para navegação em toda a documentação do sistema de autenticação.

---

## 📚 Documentos Principais

### 1. [Lógica de Registro](./RegistrationLogic.md)
**Comece aqui!** Entenda o fluxo básico de registro e login.

**Conteúdo:**
- Fluxo de registro de pacientes
- Fluxo de registro de médicos
- Validações e redirecionamentos
- Diagramas de fluxo

**Ideal para:** Desenvolvedores iniciando no projeto

---

### 2. [Sistema de Controle de Acesso - Backend](./RoleBasedAccess.md)
Entenda como o backend protege as rotas e diferencia usuários.

**Conteúdo:**
- Arquitetura de roles
- Models e relacionamentos
- Middlewares personalizados
- Controllers específicos por tipo
- Proteção de rotas
- Compartilhamento de dados com frontend

**Ideal para:** Desenvolvedores backend ou fullstack

---

### 3. [Sistema de Roteamento - Frontend](./FrontendRouting.md)
Aprenda a usar os composables e proteger rotas no Vue.js.

**Conteúdo:**
- Composables de autenticação
- Proteção de rotas (RouteGuard)
- Navegação dinâmica
- Redirecionamento automático
- Boas práticas frontend
- Escalabilidade

**Ideal para:** Desenvolvedores frontend ou fullstack

---

## 🏗️ Estrutura do Sistema

### Backend (Laravel)

```
app/
├── Models/
│   ├── User.php                    # Model base com métodos isDoctor(), isPatient()
│   ├── Doctor.php                  # Perfil de médico
│   └── Patient.php                 # Perfil de paciente
│
├── Http/
│   ├── Middleware/
│   │   ├── EnsureUserIsDoctor.php  # Protege rotas de médicos
│   │   ├── EnsureUserIsPatient.php # Protege rotas de pacientes
│   │   └── HandleInertiaRequests.php # Compartilha dados com frontend
│   │
│   └── Controllers/
│       ├── Auth/                    # Controllers de autenticação
│       │   ├── AuthenticatedSessionController.php
│       │   ├── DoctorRegistrationController.php
│       │   └── PatientRegistrationController.php
│       │
│       ├── Doctor/                  # Controllers de médicos
│       │   ├── DoctorDashboardController.php
│       │   ├── DoctorAppointmentsController.php
│       │   └── DoctorConsultationsController.php
│       │
│       └── Patient/                 # Controllers de pacientes
│           ├── PatientDashboardController.php
│           ├── PatientSearchConsultationsController.php
│           └── PatientHealthRecordsController.php
│
└── routes/
    └── web.php                      # Rotas organizadas por role
```

### Frontend (Vue.js/Inertia)

```
resources/js/
├── composables/
│   └── auth/                        # Composables de autenticação
│       ├── index.ts                 # Exportações centralizadas
│       ├── useAuth.ts               # Autenticação e verificação
│       ├── useRoleRoutes.ts         # Rotas dinâmicas
│       ├── useRouteGuard.ts         # Proteção de rotas
│       └── README.md                # Guia de uso
│
├── components/
│   ├── AppSidebar.vue               # Navegação dinâmica
│   └── AppHeader.vue                # Header com links dinâmicos
│
├── pages/
│   ├── Dashboard.vue                # Dashboard de médicos
│   ├── Doctor/                      # Páginas de médicos
│   │   └── ScheduleManagement.vue
│   └── Patient/                     # Páginas de pacientes
│       ├── Dashboard.vue
│       └── SearchConsultations.vue
│
└── routes/                          # Rotas geradas (Wayfinder)
    ├── doctor/
    │   └── index.ts                 # /doctor/*
    └── patient/
        └── index.ts                 # /patient/*
```

---

## 🔄 Fluxo Completo de Autenticação

### Diagrama de Alto Nível

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USUÁRIO FAZ LOGIN                                        │
│    Frontend: RegisterPatient.vue ou RegisterDoctor.vue      │
│    Backend: PatientRegistrationController ou               │
│             DoctorRegistrationController                    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. BACKEND CRIA PERFIL                                      │
│    - Cria User na tabela users                             │
│    - Cria Doctor ou Patient na tabela relacionada          │
│    - Faz login automático                                  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. BACKEND REDIRECIONA                                      │
│    AuthenticatedSessionController::store()                  │
│    - isDoctor() → /doctor/dashboard                        │
│    - isPatient() → /patient/dashboard                      │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. BACKEND PROTEGE ROTA                                     │
│    Middleware: ['auth', 'verified', 'doctor']              │
│    - Verifica autenticação                                 │
│    - Verifica role correto                                 │
│    - Se falhar: 403 Forbidden                              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. BACKEND COMPARTILHA DADOS                                │
│    HandleInertiaRequests                                    │
│    - Compartilha: user, role, isDoctor, isPatient, profile │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. FRONTEND RECEBE DADOS                                    │
│    useAuth() composable                                     │
│    - Processa dados do Inertia                             │
│    - Fornece métodos utilitários                           │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. FRONTEND VERIFICA ACESSO                                 │
│    useRouteGuard() composable                               │
│    - canAccessDoctorRoute() ou canAccessPatientRoute()     │
│    - Se não autorizado: redireciona                        │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ 8. FRONTEND ADAPTA NAVEGAÇÃO                                │
│    AppSidebar.vue                                           │
│    - Médicos veem: Dashboard, Agenda, Consultas            │
│    - Pacientes veem: Dashboard, Agendamentos, Prontuário   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 Camadas de Segurança

O sistema implementa **3 camadas de proteção**:

### Camada 1: Backend - Autenticação
**Middleware:** `auth`
- Verifica se usuário está autenticado
- Redireciona para `/login` se não estiver

### Camada 2: Backend - Autorização
**Middleware:** `doctor` ou `patient`
- Verifica se usuário tem role correto
- Retorna **403 Forbidden** se não tiver

### Camada 3: Frontend - UX
**RouteGuard:** `canAccessDoctorRoute()` ou `canAccessPatientRoute()`
- Verificação adicional
- Redirecionamento automático para dashboard correto
- Melhora experiência do usuário

---

## 🔑 Componentes-Chave

### Backend

| Componente | Arquivo | Função |
|------------|---------|--------|
| **User Model** | `app/Models/User.php` | Métodos `isDoctor()`, `isPatient()`, `getRole()` |
| **Doctor Middleware** | `app/Http/Middleware/EnsureUserIsDoctor.php` | Protege rotas `/doctor/*` |
| **Patient Middleware** | `app/Http/Middleware/EnsureUserIsPatient.php` | Protege rotas `/patient/*` |
| **Inertia Middleware** | `app/Http/Middleware/HandleInertiaRequests.php` | Compartilha dados auth |
| **Auth Controller** | `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Login com redirecionamento |

### Frontend

| Componente | Arquivo | Função |
|------------|---------|--------|
| **useAuth** | `resources/js/composables/auth/useAuth.ts` | Acessa dados de autenticação |
| **useRoleRoutes** | `resources/js/composables/auth/useRoleRoutes.ts` | Rotas dinâmicas por role |
| **useRouteGuard** | `resources/js/composables/auth/useRouteGuard.ts` | Proteção e redirecionamento |
| **AppSidebar** | `resources/js/components/AppSidebar.vue` | Menu adaptável |

---

## 📖 Guia de Leitura Recomendado

### Para Desenvolvedores Novos no Projeto

1. **Leia primeiro:** [Lógica de Registro](./RegistrationLogic.md)
   - Entenda o básico de como funciona

2. **Backend:** [RoleBasedAccess.md](./RoleBasedAccess.md)
   - Como criar controllers
   - Como usar middlewares
   - Como proteger rotas

3. **Frontend:** [FrontendRouting.md](./FrontendRouting.md)
   - Como usar composables
   - Como proteger páginas
   - Como criar navegação dinâmica

### Para Adicionar Funcionalidades

**Adicionar nova página de médico:**
1. Criar controller em `app/Http/Controllers/Doctor/`
2. Adicionar rota em `routes/web.php` (grupo doctor)
3. Criar página Vue em `resources/js/pages/Doctor/`
4. Adicionar RouteGuard com `canAccessDoctorRoute()`

**Adicionar nova página de paciente:**
1. Criar controller em `app/Http/Controllers/Patient/`
2. Adicionar rota em `routes/web.php` (grupo patient)
3. Criar página Vue em `resources/js/pages/Patient/`
4. Adicionar RouteGuard com `canAccessPatientRoute()`

### Para Adicionar Novo Tipo de Usuário

**Ver seção "Adicionando Novos Roles" em:**
- [RoleBasedAccess.md](./RoleBasedAccess.md#manutenção-e-extensão) (Backend)
- [FrontendRouting.md](./FrontendRouting.md#adicionando-novos-roles) (Frontend)

---

## 🔧 Configuração Rápida

### Backend

1. **Middlewares registrados em:** `bootstrap/app.php`
```php
$middleware->alias([
    'doctor' => \App\Http\Middleware\EnsureUserIsDoctor::class,
    'patient' => \App\Http\Middleware\EnsureUserIsPatient::class,
]);
```

2. **Rotas organizadas em:** `routes/web.php`
```php
Route::middleware(['auth', 'verified', 'doctor'])->prefix('doctor')->group(...)
Route::middleware(['auth', 'verified', 'patient'])->prefix('patient')->group(...)
```

### Frontend

1. **Composables em:** `resources/js/composables/auth/`
```typescript
import { useAuth, useRoleRoutes, useRouteGuard } from '@/composables/auth';
```

2. **Rotas em:** `resources/js/routes/`
```typescript
import * as doctorRoutes from '@/routes/doctor';
import * as patientRoutes from '@/routes/patient';
```

---

## 🧪 Testando o Sistema

### Checklist de Testes

#### Backend
- [ ] Login como médico → Redireciona para `/doctor/dashboard`
- [ ] Login como paciente → Redireciona para `/patient/dashboard`
- [ ] Médico acessa `/patient/dashboard` → 403 Forbidden
- [ ] Paciente acessa `/doctor/dashboard` → 403 Forbidden
- [ ] Não autenticado acessa rota protegida → Redireciona para `/login`

#### Frontend
- [ ] Médico vê menu: Dashboard, Agenda, Consultas
- [ ] Paciente vê menu: Dashboard, Agendamentos, Prontuário
- [ ] Logo redireciona para dashboard correto
- [ ] `canAccess('doctor')` funciona corretamente
- [ ] RouteGuard protege páginas

### Comandos de Teste

```bash
# Gerar rotas TypeScript
php artisan wayfinder:generate

# Verificar rotas registradas
php artisan route:list --path=doctor
php artisan route:list --path=patient

# Iniciar servidor
npm run dev
php artisan serve
```

---

## 📊 Mapa de Rotas

### Backend (Laravel)

| Tipo | Rota | Controller | Middleware |
|------|------|-----------|-----------|
| **Médico** | `/doctor/dashboard` | `DoctorDashboardController` | `auth, verified, doctor` |
| **Médico** | `/doctor/appointments` | `DoctorAppointmentsController` | `auth, verified, doctor` |
| **Médico** | `/doctor/consultations` | `DoctorConsultationsController` | `auth, verified, doctor` |
| **Paciente** | `/patient/dashboard` | `PatientDashboardController` | `auth, verified, patient` |
| **Paciente** | `/patient/search-consultations` | `PatientSearchConsultationsController` | `auth, verified, patient` |
| **Paciente** | `/patient/health-records` | `PatientHealthRecordsController` | `auth, verified, patient` |

### Frontend (Vue.js)

| Tipo | Página | Composable Guard |
|------|--------|------------------|
| **Médico** | `Dashboard.vue` | `canAccessDoctorRoute()` |
| **Médico** | `Doctor/ScheduleManagement.vue` | N/A |
| **Paciente** | `Patient/Dashboard.vue` | `canAccessPatientRoute()` |
| **Paciente** | `Patient/SearchConsultations.vue` | `canAccessPatientRoute()` |

---

## 🎓 Conceitos Importantes

### 1. Roles vs Permissions

Este sistema usa **roles baseados em perfis**, não um sistema tradicional de permissions.

- ✅ Um usuário tem UM role: `doctor`, `patient` ou `user`
- ✅ Cada role tem seu próprio conjunto de rotas
- ❌ Não usa tabelas de permissions/abilities

### 2. Proteção Dupla (Backend + Frontend)

- **Backend:** Segurança real (SEMPRE valida)
- **Frontend:** Melhoria de UX (redirecionamento suave)

**Nunca confie apenas no frontend!**

### 3. Relacionamentos 1:1

```
User (1) ──────── (1) Doctor
     (1) ──────── (1) Patient
```

Um usuário pode ter:
- ✅ Perfil de médico (tem `doctor` relacionado)
- ✅ Perfil de paciente (tem `patient` relacionado)
- ❌ Ambos ao mesmo tempo (não implementado)

### 4. Redirecionamento Inteligente

O sistema sempre redireciona para o dashboard correto:

```php
// Backend
if ($user->isDoctor()) {
    return redirect()->route('doctor.dashboard');
}

if ($user->isPatient()) {
    return redirect()->route('patient.dashboard');
}
```

---

## 🚀 Evolução do Sistema

### Já Implementado ✅

- [x] Registro de pacientes e médicos
- [x] Login unificado com redirecionamento
- [x] Middlewares de proteção backend
- [x] Controllers separados por tipo
- [x] Composables de autenticação frontend
- [x] Navegação dinâmica
- [x] RouteGuard para proteção
- [x] Documentação completa

### Próximos Passos 🔄

- [ ] Páginas de erro customizadas (403, 404)
- [ ] Adicionar role de admin (futuro)
- [ ] Sistema de permissões granulares (opcional)
- [ ] Testes automatizados
- [ ] Auditoria de acesso

---

## 🔗 Links Rápidos

### Documentação
- [Lógica de Registro](./RegistrationLogic.md)
- [Backend - RoleBasedAccess](./RoleBasedAccess.md)
- [Frontend - Routing](./FrontendRouting.md)

### Código Backend
- [User Model](../../../app/Models/User.php)
- [EnsureUserIsDoctor](../../../app/Http/Middleware/EnsureUserIsDoctor.php)
- [EnsureUserIsPatient](../../../app/Http/Middleware/EnsureUserIsPatient.php)
- [Rotas](../../../routes/web.php)

### Código Frontend
- [useAuth](../../../resources/js/composables/auth/useAuth.ts)
- [useRoleRoutes](../../../resources/js/composables/auth/useRoleRoutes.ts)
- [useRouteGuard](../../../resources/js/composables/auth/useRouteGuard.ts)
- [AppSidebar](../../../resources/js/components/AppSidebar.vue)

---

## ❓ FAQ

**P: Preciso usar Laravel Sanctum?**
R: Não. O sistema atual é suficiente para diferenciar usuários. Sanctum é para APIs/SPAs separadas.

**P: Como adiciono um novo tipo de usuário?**
R: Siga os guias em [RoleBasedAccess.md](./RoleBasedAccess.md#manutenção-e-extensão) e [FrontendRouting.md](./FrontendRouting.md#adicionando-novos-roles)

**P: O frontend protege as rotas?**
R: Sim, mas é uma proteção secundária. O backend SEMPRE valida primeiro.

**P: Por que usar composables em vez de importar direto?**
R: Composables são escaláveis, reutilizáveis e facilitam manutenção.

---

*Última atualização: Outubro 2025*

