# Arquitetura e Fluxo do Módulo de Agendamentos

## Visão Geral

Este documento explica a arquitetura em camadas do módulo de agendamentos (Appointments), detalhando o fluxo de dados e a separação de responsabilidades entre cada camada do sistema.

## Arquitetura em Camadas

O módulo de agendamentos segue uma arquitetura em camadas bem definida, onde cada camada tem responsabilidades específicas:

```
┌─────────────────────────────────────────────────────────────┐
│                    CAMADA DE APRESENTAÇÃO                    │
│              (Controllers + Form Requests)                  │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    CAMADA DE SEGURANÇA                       │
│                      (Policies)                             │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                 CAMADA DE LÓGICA DE NEGÓCIO                  │
│                    (Services)                                │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    CAMADA DE DOMÍNIO                        │
│              (Models + Observers)                            │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                  CAMADA DE PERSISTÊNCIA                      │
│              (Database + Migrations)                         │
└─────────────────────────────────────────────────────────────┘
```

## Separação de Responsabilidades

### 1. Camada de Apresentação (Controllers + Form Requests)

**Responsabilidades:**
- Receber requisições HTTP/Inertia
- Validar dados de entrada (Form Requests)
- Delegar lógica de negócio para Services
- Aplicar Policies para autorização
- Retornar respostas adequadas (JSON, Inertia, Redirects)

**Arquivos:**
- `App\Http\Controllers\AppointmentsController.php`
- `App\Http\Requests\StoreAppointmentRequest.php`
- `App\Http\Requests\UpdateAppointmentRequest.php`
- `App\Http\Requests\RescheduleAppointmentRequest.php`

**Exemplo de Fluxo:**
```php
// Controller recebe requisição
public function store(StoreAppointmentRequest $request): RedirectResponse
{
    // 1. Form Request valida dados de entrada automaticamente
    // 2. Policy verifica autorização
    $this->authorize('create', Appointments::class);
    
    // 3. Delega para Service
    $appointment = $this->appointmentService->create($data, $user);
    
    // 4. Retorna resposta
    return redirect()->route('appointments.show', $appointment);
}
```

**O que NÃO faz:**
- ❌ Não contém lógica de negócio
- ❌ Não acessa banco de dados diretamente
- ❌ Não valida regras de negócio complexas

---

### 2. Camada de Segurança (Policies)

**Responsabilidades:**
- Controlar acesso contextual (quem pode fazer o quê)
- Validar janelas de tempo (lead, duration, grace)
- Verificar permissões baseadas em relacionamentos (doctor/patient)
- Validar estados permitidos para cada ação

**Arquivos:**
- `App\Policies\AppointmentPolicy.php`

**Exemplo de Regra:**
```php
public function start(User $user, Appointments $appointment): bool
{
    // 1. Verifica se usuário pode ver o appointment
    if (!$this->view($user, $appointment)) {
        return false;
    }
    
    // 2. Valida status permitido
    if (!in_array($appointment->status, [STATUS_SCHEDULED, STATUS_RESCHEDULED])) {
        return false;
    }
    
    // 3. Valida janela de tempo
    $leadMinutes = config('telemedicine.appointment.lead_minutes', 10);
    $canStartAt = $appointment->scheduled_at->copy()->subMinutes($leadMinutes);
    
    return Carbon::now() >= $canStartAt;
}
```

**O que NÃO faz:**
- ❌ Não executa ações (apenas autoriza ou nega)
- ❌ Não contém lógica de negócio
- ❌ Não persiste dados

---

### 3. Camada de Lógica de Negócio (Services)

**Responsabilidades:**
- Implementar regras de negócio complexas
- Validar conflitos de horário
- Validar integridade de dados (doctor ativo, patient completo)
- Coordenar transições de status
- Criar logs de auditoria para ações importantes
- Orquestrar múltiplos modelos quando necessário

**Arquivos:**
- `App\Services\AppointmentService.php`

**Exemplo de Método:**
```php
public function create(array $data, User $user): Appointments
{
    // 1. Validações de negócio
    if (!$this->validateDoctorActive($data['doctor_id'])) {
        throw new \Exception('Médico não está ativo.');
    }
    
    if (!$this->validatePatientComplete($data['patient_id'])) {
        throw new \Exception('Paciente não possui cadastro completo.');
    }
    
    // 2. Valida conflito de horário
    $duration = config('telemedicine.appointment.duration_minutes', 30);
    if (!$this->validateNoConflict($data['doctor_id'], $scheduledAt, $duration)) {
        throw new \Exception('Conflito de horário.');
    }
    
    // 3. Cria appointment (Observer cria log automaticamente)
    $appointment = Appointments::create($data);
    
    return $appointment->fresh();
}
```

**Métodos Principais:**
- `create()` - Criar appointment com validações
- `update()` - Atualizar com validação de imutabilidade
- `list()` - Listar com filtros
- `findForUser()` - Buscar com validação de acesso
- `start()`, `end()`, `cancel()`, `reschedule()` - Transições de status
- `validateNoConflict()` - Validar conflitos de horário
- `validateDoctorActive()` - Validar doctor ativo
- `validatePatientComplete()` - Validar patient completo
- `validateStatusTransition()` - Validar transições permitidas

**O que NÃO faz:**
- ❌ Não recebe requisições HTTP diretamente
- ❌ Não retorna respostas HTTP
- ❌ Não valida formato de dados (isso é do Form Request)

---

### 4. Camada de Domínio (Models + Observers)

**Responsabilidades:**
- Representar entidades de negócio
- Definir relacionamentos entre modelos
- Implementar scopes para consultas reutilizáveis
- Executar efeitos colaterais automáticos (Observers)
- Gerar códigos únicos (access_code)
- Criar logs de auditoria automáticos

**Arquivos:**
- `App\Models\Appointments.php`
- `App\Models\AppointmentLog.php`
- `App\Observers\AppointmentsObserver.php`

**Exemplo de Observer:**
```php
public function created(Appointments $appointment): void
{
    // Cria log automaticamente quando appointment é criado
    $appointment->logEvent(
        AppointmentLog::EVENT_CREATED,
        [
            'doctor_id' => $appointment->doctor_id,
            'patient_id' => $appointment->patient_id,
            'scheduled_at' => $appointment->scheduled_at->toIso8601String(),
        ],
        auth()->id()
    );
}
```

**Responsabilidades do Model:**
- Relacionamentos: `doctor()`, `patient()`, `logs()`
- Scopes: `scheduled()`, `inProgress()`, `upcoming()`, `byDoctor()`, etc.
- Accessors: `duration`, `formatted_duration`
- Mutators: `setScheduledAtAttribute()`, etc.
- Helper: `logEvent()` - Criar logs de forma conveniente

**O que NÃO faz:**
- ❌ Não contém lógica de negócio complexa (delegada para Service)
- ❌ Não valida regras de negócio (delegada para Service/Policy)

---

### 5. Camada de Persistência (Database + Migrations)

**Responsabilidades:**
- Definir estrutura do banco de dados
- Criar índices para performance
- Definir constraints e foreign keys
- Garantir integridade referencial

**Arquivos:**
- `database/migrations/2025_09_10_152050_create_appointments_table.php`
- `database/migrations/2025_11_10_123814_create_appointment_logs_table.php`

---

## Fluxo Completo: Criar um Appointment

Vamos seguir o fluxo completo de criação de um appointment para entender como as camadas interagem:

### 1. Requisição HTTP
```
POST /appointments
{
    "doctor_id": "uuid",
    "patient_id": "uuid",
    "scheduled_at": "2025-11-15 14:00:00",
    "notes": "Consulta de rotina"
}
```

### 2. Controller Recebe Requisição
```php
// AppointmentsController::store()
public function store(StoreAppointmentRequest $request)
{
    // Form Request valida automaticamente:
    // - doctor_id existe e está ativo
    // - patient_id existe
    // - scheduled_at é data futura
    // - notes é string opcional
    
    $data = $request->validated(); // ✅ Dados validados
}
```

### 3. Policy Verifica Autorização
```php
// AppointmentPolicy::create()
public function create(User $user): bool
{
    // Apenas pacientes podem criar appointments
    return $user->isPatient(); // ✅ Autorizado
}
```

### 4. Controller Delega para Service
```php
// AppointmentsController::store()
$this->authorize('create', Appointments::class); // ✅ Passou na Policy
$appointment = $this->appointmentService->create($data, $user);
```

### 5. Service Executa Lógica de Negócio
```php
// AppointmentService::create()
public function create(array $data, User $user): Appointments
{
    // Valida doctor ativo
    if (!$this->validateDoctorActive($data['doctor_id'])) {
        throw new \Exception('Médico não está ativo.');
    }
    
    // Valida patient completo
    if (!$this->validatePatientComplete($data['patient_id'])) {
        throw new \Exception('Paciente não possui cadastro completo.');
    }
    
    // Valida conflito de horário
    if (!$this->validateNoConflict($data['doctor_id'], $scheduledAt, $duration)) {
        throw new \Exception('Conflito de horário.');
    }
    
    // Cria appointment
    $appointment = Appointments::create($data);
    // ↑ Observer é disparado automaticamente
}
```

### 6. Observer Executa Efeitos Colaterais
```php
// AppointmentsObserver::creating()
public function creating(Appointments $appointment): void
{
    // Gera access_code único
    if (!$appointment->access_code) {
        $appointment->access_code = self::generateUniqueAccessCode();
    }
    
    // Define status padrão
    if (!$appointment->status) {
        $appointment->status = Appointments::STATUS_SCHEDULED;
    }
}

// AppointmentsObserver::created()
public function created(Appointments $appointment): void
{
    // Cria log de auditoria
    $appointment->logEvent(
        AppointmentLog::EVENT_CREATED,
        [...],
        auth()->id()
    );
}
```

### 7. Service Retorna Resultado
```php
// AppointmentService::create()
return $appointment->fresh(); // ✅ Appointment criado com sucesso
```

### 8. Controller Retorna Resposta
```php
// AppointmentsController::store()
return redirect()
    ->route('appointments.show', $appointment)
    ->with('success', 'Agendamento criado com sucesso.');
```

---

## Fluxo Completo: Iniciar uma Consulta

### 1. Requisição HTTP
```
POST /appointments/{id}/start
```

### 2. Controller Recebe Requisição
```php
// AppointmentsController::start()
public function start(Appointments $appointment): JsonResponse
{
    // Policy verifica autorização
    $this->authorize('start', $appointment);
}
```

### 3. Policy Valida Acesso e Janela de Tempo
```php
// AppointmentPolicy::start()
public function start(User $user, Appointments $appointment): bool
{
    // Verifica se usuário pode ver
    if (!$this->view($user, $appointment)) return false;
    
    // Verifica status permitido
    if (!in_array($appointment->status, [STATUS_SCHEDULED, STATUS_RESCHEDULED])) {
        return false;
    }
    
    // Valida janela de tempo (pode iniciar 10min antes)
    $leadMinutes = config('telemedicine.appointment.lead_minutes', 10);
    $canStartAt = $appointment->scheduled_at->copy()->subMinutes($leadMinutes);
    
    return Carbon::now() >= $canStartAt; // ✅ Dentro da janela
}
```

### 4. Service Executa Ação
```php
// AppointmentService::start()
public function start(Appointments $appointment, ?string $userId = null): bool
{
    if (!$this->canBeStarted($appointment)) {
        return false;
    }
    
    // Atualiza status e timestamp
    $appointment->update([
        'status' => Appointments::STATUS_IN_PROGRESS,
        'started_at' => Carbon::now(),
    ]);
    
    // Observer::updated() é disparado, mas não cria log duplicado
    // porque Service cria log específico
    
    // Cria log de início
    $appointment->logEvent(
        AppointmentLog::EVENT_STARTED,
        ['started_at' => Carbon::now()->toIso8601String()],
        $userId
    );
    
    return true;
}
```

### 5. Controller Retorna Resposta JSON
```php
// AppointmentsController::start()
return response()->json([
    'message' => 'Consulta iniciada com sucesso.',
    'appointment' => $appointment->fresh(),
]);
```

---

## Princípios de Design

### 1. Single Responsibility Principle (SRP)
Cada camada tem uma única responsabilidade:
- **Controller**: Receber requisições e retornar respostas
- **Policy**: Autorizar ações
- **Service**: Lógica de negócio
- **Model**: Representar entidade e relacionamentos
- **Observer**: Efeitos colaterais automáticos

### 2. Separation of Concerns
- **Validação de formato** → Form Requests
- **Validação de autorização** → Policies
- **Validação de negócio** → Services
- **Persistência** → Models + Database

### 3. Dependency Inversion
- Controllers dependem de Services (abstração)
- Services dependem de Models (abstração)
- Não há dependência direta de implementações concretas

### 4. Don't Repeat Yourself (DRY)
- Validações reutilizáveis em Services
- Scopes reutilizáveis em Models
- Lógica de negócio centralizada em Services

---

## Regras de Ouro

### ✅ O que cada camada DEVE fazer:

**Controller:**
- Receber e validar entrada (via Form Request)
- Aplicar Policy
- Delegar para Service
- Retornar resposta adequada

**Policy:**
- Verificar permissões
- Validar contexto (status, janela de tempo)
- Retornar boolean (autorizado ou não)

**Service:**
- Implementar regras de negócio
- Validar integridade
- Coordenar múltiplos modelos
- Criar logs de ações importantes

**Model:**
- Representar entidade
- Definir relacionamentos
- Implementar scopes
- Fornecer helpers (logEvent)

**Observer:**
- Executar efeitos colaterais automáticos
- Gerar códigos únicos
- Criar logs de ciclo de vida (created, updated, deleted)

### ❌ O que cada camada NÃO DEVE fazer:

**Controller:**
- ❌ Não acessa banco diretamente
- ❌ Não contém lógica de negócio
- ❌ Não valida regras complexas

**Policy:**
- ❌ Não executa ações
- ❌ Não persiste dados
- ❌ Não contém lógica de negócio

**Service:**
- ❌ Não recebe requisições HTTP
- ❌ Não retorna respostas HTTP
- ❌ Não valida formato de dados

**Model:**
- ❌ Não contém lógica de negócio complexa
- ❌ Não valida regras de negócio
- ❌ Não autoriza ações

---

## Benefícios desta Arquitetura

1. **Testabilidade**: Cada camada pode ser testada independentemente
2. **Manutenibilidade**: Mudanças em uma camada não afetam outras
3. **Reutilização**: Services podem ser usados por diferentes controllers
4. **Clareza**: Responsabilidades bem definidas facilitam entendimento
5. **Escalabilidade**: Fácil adicionar novas funcionalidades sem quebrar existentes
6. **Segurança**: Policies centralizadas garantem controle de acesso consistente

---

## Referências Cruzadas

### Documentação Relacionada
- **[Lógica de Consultas](AppointmentsLogica.md)** - Regras de negócio e fluxos
- **[Implementação de Consultas](AppointmentsImplementationStudy.md)** - Detalhes técnicos
- **[Arquitetura do Sistema](../../architecture/Arquitetura.md)** - Visão geral da arquitetura

### Código Relacionado
- **[AppointmentsController](../../../app/Http/Controllers/AppointmentsController.php)**
- **[AppointmentService](../../../app/Services/AppointmentService.php)**
- **[AppointmentPolicy](../../../app/Policies/AppointmentPolicy.php)**
- **[Appointments Model](../../../app/Models/Appointments.php)**
- **[AppointmentsObserver](../../../app/Observers/AppointmentsObserver.php)**

---

*Última atualização: Novembro 2025*

