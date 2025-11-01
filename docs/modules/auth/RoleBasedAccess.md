# Sistema de Controle de Acesso Baseado em Roles (Backend) - TelemedicinaParaTodos

## Visão Geral

O sistema implementa controle de acesso baseado em perfis de usuário através de relacionamentos 1:1 entre a tabela `users` e as tabelas `doctors` e `patients`. Não utiliza um sistema tradicional de roles/permissions, mas sim uma arquitetura de perfis específicos.

**Este documento cobre apenas o BACKEND.** Para o sistema de roteamento no **Frontend**, consulte: [Sistema de Roteamento Frontend](./FrontendRouting.md)

**Documentação relacionada:**
- **[Sistema de Roteamento Frontend](./FrontendRouting.md)** - Composables, proteção de rotas e navegação no Vue.js
- **[Lógica de Registro](./RegistrationLogic.md)** - Fluxo de registro de usuários

## Arquitetura

### Estrutura de Dados

```
users (tabela central)
├── id (UUID)
├── name
├── email (único)
├── password
└── timestamps

doctors                    patients
├── id (UUID)             ├── id (UUID)
├── user_id (único)       ├── user_id (único)
├── crm                   ├── gender
├── biography             ├── date_of_birth
├── status                ├── phone_number
└── ...                   └── ...
```

### Relacionamentos

**Model User:**
- `hasOne(Doctor::class)` - Um usuário pode ter um perfil de médico
- `hasOne(Patient::class)` - Um usuário pode ter um perfil de paciente

**Model Doctor:**
- `belongsTo(User::class)` - Pertence a um usuário

**Model Patient:**
- `belongsTo(User::class)` - Pertence a um usuário

## Métodos de Verificação

O Model `User` fornece métodos para verificar o tipo de usuário:

### isDoctor()
```php
$user->isDoctor(): bool
```
Verifica se o usuário possui perfil de médico através do relacionamento.

**Exemplo:**
```php
if (Auth::user()->isDoctor()) {
    // Lógica específica para médicos
}
```

### isPatient()
```php
$user->isPatient(): bool
```
Verifica se o usuário possui perfil de paciente através do relacionamento.

**Exemplo:**
```php
if (Auth::user()->isPatient()) {
    // Lógica específica para pacientes
}
```

### getRole()
```php
$user->getRole(): string
```
Retorna o role do usuário como string: `'doctor'`, `'patient'` ou `'user'`.

**Exemplo:**
```php
$role = Auth::user()->getRole();
// Retorna: 'doctor', 'patient' ou 'user'
```

## Middlewares

O sistema possui dois middlewares personalizados para proteger rotas:

### EnsureUserIsDoctor

**Caminho:** `app/Http/Middleware/EnsureUserIsDoctor.php`

**Função:** Garante que apenas usuários com perfil de médico possam acessar a rota.

**Uso:**
```php
Route::middleware(['auth', 'doctor'])->group(function () {
    // Rotas exclusivas para médicos
});
```

**Comportamento:**
- Verifica se o usuário está autenticado
- Verifica se `$user->isDoctor()` retorna true
- Retorna erro 403 se não for médico

### EnsureUserIsPatient

**Caminho:** `app/Http/Middleware/EnsureUserIsPatient.php`

**Função:** Garante que apenas usuários com perfil de paciente possam acessar a rota.

**Uso:**
```php
Route::middleware(['auth', 'patient'])->group(function () {
    // Rotas exclusivas para pacientes
});
```

**Comportamento:**
- Verifica se o usuário está autenticado
- Verifica se `$user->isPatient()` retorna true
- Retorna erro 403 se não for paciente

### Registro de Middlewares

Os middlewares são registrados como aliases no `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'doctor' => \App\Http\Middleware\EnsureUserIsDoctor::class,
        'patient' => \App\Http\Middleware\EnsureUserIsPatient::class,
    ]);
})
```

## Estrutura de Rotas

### Rotas de Médicos

**Prefixo:** `/doctor`
**Middleware:** `['auth', 'verified', 'doctor']`
**Name prefix:** `doctor.`

```php
Route::middleware(['auth', 'verified', 'doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('dashboard', [DoctorDashboardController::class, 'index'])->name('dashboard');
    Route::get('appointments', [DoctorAppointmentsController::class, 'index'])->name('appointments');
    Route::get('consultations', [DoctorConsultationsController::class, 'index'])->name('consultations');
});
```

**Rotas disponíveis:**
- `GET /doctor/dashboard` → `route('doctor.dashboard')`
- `GET /doctor/appointments` → `route('doctor.appointments')`
- `GET /doctor/consultations` → `route('doctor.consultations')`

### Rotas de Pacientes

**Prefixo:** `/patient`
**Middleware:** `['auth', 'verified', 'patient']`
**Name prefix:** `patient.`

```php
Route::middleware(['auth', 'verified', 'patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('dashboard', [PatientDashboardController::class, 'index'])->name('dashboard');
    Route::get('search-consultations', [PatientSearchConsultationsController::class, 'index'])->name('search-consultations');
    Route::get('health-records', [PatientHealthRecordsController::class, 'index'])->name('health-records');
});
```

**Rotas disponíveis:**
- `GET /patient/dashboard` → `route('patient.dashboard')`
- `GET /patient/search-consultations` → `route('patient.search-consultations')`
- `GET /patient/health-records` → `route('patient.health-records')`

### Rotas Compartilhadas

Rotas acessíveis por ambos os tipos de usuário:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('specializations', SpecializationController::class);
});
```

## Controllers Específicos

### Controllers de Médicos

**Namespace:** `App\Http\Controllers\Doctor`

#### DoctorDashboardController
- **Responsabilidade:** Dashboard com estatísticas e agendamentos do médico
- **Dados:** Consultas próximas, estatísticas semanais/mensais, gráficos
- **View:** `Dashboard`

#### DoctorAppointmentsController
- **Responsabilidade:** Gerenciamento de agendamentos do médico
- **View:** `Doctor/ScheduleManagement`

#### DoctorConsultationsController
- **Responsabilidade:** Listagem de pacientes e consultas
- **Dados:** Pacientes vinculados ao médico
- **View:** `Consultations`

### Controllers de Pacientes

**Namespace:** `App\Http\Controllers\Patient`

#### PatientDashboardController
- **Responsabilidade:** Dashboard com agendamentos e histórico do paciente
- **Dados:** Consultas próximas, histórico, estatísticas básicas
- **View:** `Patient/Dashboard`

#### PatientSearchConsultationsController
- **Responsabilidade:** Busca e listagem de médicos disponíveis
- **Dados:** Médicos disponíveis para consulta
- **View:** `Patient/SearchConsultations`

#### PatientHealthRecordsController
- **Responsabilidade:** Exibição de prontuário médico
- **Dados:** Informações do perfil do paciente
- **View:** `HealthRecords`

## Compartilhamento de Dados com Frontend

O middleware `HandleInertiaRequests` compartilha automaticamente informações do usuário:

**Arquivo:** `app/Http/Middleware/HandleInertiaRequests.php`

```php
'auth' => [
    'user' => $request->user(),              // Dados básicos do usuário
    'role' => $request->user()?->getRole(),  // 'doctor', 'patient' ou 'user'
    'isDoctor' => $request->user()?->isDoctor() ?? false,
    'isPatient' => $request->user()?->isPatient() ?? false,
    'profile' => $request->user()?->doctor ?? $request->user()?->patient,
],
```

### Acesso no Frontend

Estes dados ficam disponíveis no frontend via Inertia.js. Para detalhes completos sobre como usar no frontend, consulte: **[Sistema de Roteamento Frontend](./FrontendRouting.md)**

**Resumo rápido:**
```vue
<script setup>
import { useAuth } from '@/composables/auth'

const { user, role, isDoctor, isPatient, profile, canAccess } = useAuth()
</script>

<template>
    <!-- Uso simplificado -->
    <div v-if="canAccess('doctor')">Conteúdo de médico</div>
    <div v-if="canAccess('patient')">Conteúdo de paciente</div>
</template>
```

**Para implementação completa, veja:** [FrontendRouting.md](./FrontendRouting.md)

## Fluxo de Autenticação e Redirecionamento

### Login

```mermaid
graph TD
    A[Usuário faz login] --> B[AuthenticatedSessionController]
    B --> C{Verifica tipo}
    C -->|isDoctor| D[/doctor/dashboard]
    C -->|isPatient| E[/patient/dashboard]
    C -->|Nenhum| F[/ home]
```

**Implementação:**
```php
// AuthenticatedSessionController::store()
if ($user->isDoctor()) {
    return redirect()->intended(route('doctor.dashboard'));
}

if ($user->isPatient()) {
    return redirect()->intended(route('patient.dashboard'));
}

return redirect()->intended(route('home'));
```

### Registro

#### Registro de Paciente
```
POST /register/patient
→ Cria User + Patient
→ Login automático
→ Redirect para /patient/dashboard
```

#### Registro de Médico
```
POST /register/doctor
→ Cria User + Doctor + Specializations
→ Login automático
→ Redirect para /doctor/dashboard
```

## Exemplo de Uso nos Controllers

### Acessar Perfil do Usuário Logado

```php
// Para médico
$doctor = Auth::user()->doctor;

// Para paciente
$patient = Auth::user()->patient;
```

### Verificar Tipo no Controller

```php
public function index()
{
    $user = Auth::user();
    
    if ($user->isDoctor()) {
        $doctor = $user->doctor;
        // Lógica para médico
    } elseif ($user->isPatient()) {
        $patient = $user->patient;
        // Lógica para paciente
    }
}
```

### Proteger Métodos Específicos

```php
public function __construct()
{
    $this->middleware('doctor')->only(['methodForDoctors']);
    $this->middleware('patient')->only(['methodForPatients']);
}
```

## Boas Práticas

### 1. Sempre usar middlewares nas rotas
```php
// ✅ Correto
Route::middleware(['auth', 'doctor'])->get('/doctor/dashboard', ...);

// ❌ Incorreto
Route::get('/doctor/dashboard', ...); // Sem proteção
```

### 2. Verificar existência do perfil
```php
// ✅ Correto
$doctor = Auth::user()->doctor;
if (!$doctor) {
    abort(403, 'Perfil de médico não encontrado.');
}

// ❌ Incorreto - pode causar erro se perfil não existir
$crm = Auth::user()->doctor->crm;
```

### 3. Usar relacionamentos eager loading
```php
// ✅ Correto
$appointments = Appointments::with(['doctor.user', 'patient.user'])->get();

// ❌ Incorreto - N+1 queries
$appointments = Appointments::all();
foreach ($appointments as $app) {
    echo $app->doctor->user->name; // Query por iteração
}
```

### 4. Nomear rotas consistentemente
```php
// ✅ Correto
route('doctor.dashboard')
route('patient.search-consultations')

// ❌ Incorreto
route('doctorDashboard')
route('patientappointments')
```

## Segurança

### Validação em Múltiplas Camadas

1. **Middleware de Rota:** Verifica tipo de usuário
2. **Controller:** Valida perfil específico
3. **Model:** Valida relacionamentos

### Prevenção de Acesso Cruzado

```php
// Em um controller de médico, sempre buscar apenas dados do médico logado
$doctor = Auth::user()->doctor;
$appointments = Appointments::byDoctor($doctor->id)->get();

// Nunca confiar em IDs vindos da URL sem validação
```

### Headers de Erro Apropriados

- **403 Forbidden:** Quando usuário não tem permissão para o recurso
- **404 Not Found:** Quando recurso não existe
- **401 Unauthorized:** Quando usuário não está autenticado

## Manutenção e Extensão

### Adicionar Novo Tipo de Usuário

1. Criar migration para nova tabela (ex: `admins`)
2. Criar model com relacionamento `belongsTo(User::class)`
3. Adicionar relacionamento em `User`: `hasOne(Admin::class)`
4. Criar método `isAdmin()` em `User`
5. Atualizar `getRole()` para incluir novo tipo
6. Criar middleware `EnsureUserIsAdmin`
7. Registrar middleware em `bootstrap/app.php`
8. Criar controllers específicos
9. Adicionar rotas protegidas

### Adicionar Nova Funcionalidade

1. Identificar qual tipo de usuário acessa
2. Criar rota no grupo apropriado (doctor/patient)
3. Criar controller action
4. Implementar view
5. Atualizar documentação

## Troubleshooting

### Erro 403 ao acessar rota

**Causa:** Usuário não tem perfil necessário
**Solução:** Verificar se usuário tem relacionamento correto (doctor/patient)

### Redirecionamento incorreto após login

**Causa:** Método `getRole()` não retorna valor esperado
**Solução:** Verificar se perfil foi criado corretamente no registro

### Perfil retorna null

**Causa:** Relacionamento não carregado ou não existe
**Solução:** 
- Usar eager loading: `User::with('doctor')->find($id)`
- Verificar se registro existe na tabela relacionada

## Referências

### Documentação de Autenticação
- **[Sistema de Roteamento Frontend](./FrontendRouting.md)** - Composables, proteção de rotas e navegação no Vue.js
- **[Lógica de Registro](./RegistrationLogic.md)** - Fluxo de registro de usuários e redirecionamentos

### Documentação Geral
- **[Arquitetura do Sistema](../../Architecture/Arquitetura.md)** - Visão geral da arquitetura
- **[Diagrama de Banco de Dados](../../database/README.md)** - Estrutura das tabelas



