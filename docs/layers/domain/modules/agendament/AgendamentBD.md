# Relações do Banco de Dados - Sistema de Agendamento

## Visão Geral

Este documento descreve todas as relações entre as entidades do sistema de agendamento, explicando como os médicos configuram sua disponibilidade, como os pacientes consultam e agendam consultas, e como tudo se conecta através das relações do banco de dados.

## Diagrama de Relacionamentos

```
┌─────────────┐
│    User     │
└──────┬──────┘
       │
       ├─────────────────────────────────────┐
       │                                     │
┌──────▼──────────┐                 ┌───────▼────────┐
│    Doctor       │                 │    Patient     │
└──────┬──────────┘                 └───────┬────────┘
       │                                    │
       ├─────────────┬──────────────┬───────┤
       │             │              │       │
       │             │              │       │
┌──────▼──────────┐  │    ┌─────────▼────┐  │  ┌──────────────┐
│ ServiceLocation │  │    │ BlockedDate  │  │  │ Appointments │
└──────┬──────────┘  │    └──────────────┘  │  └──────▲───────┘
       │             │                      │         │
       │             │                      │         │
┌──────▼──────────┐  │                      │         │
│ AvailabilitySlot│  │                      │         │
└─────────────────┘  │                      │         │
                     │                      │         │
                     └──────────────────── ─┴─────────┘
                                    (relacionamento)
```

## Entidades e Relacionamentos

### 1. Doctor (Médico)

**Tabela:** `doctors`

**Relacionamentos:**

#### 1.1. Doctor → User (BelongsTo)
- **Relação:** `belongsTo(User::class)`
- **Campo:** `user_id` (FK)
- **Descrição:** Cada médico pertence a um usuário. O usuário contém informações de autenticação e perfil básico (nome, email, avatar).

**Uso:**
```php
$doctor->user // Retorna o User associado
$user->doctor // Retorna o Doctor associado (hasOne)
```

#### 1.2. Doctor → ServiceLocation (HasMany)
- **Relação:** `hasMany(ServiceLocation::class, 'doctor_id')`
- **Campo:** `doctor_id` (FK em `doctor_service_locations`)
- **Descrição:** Um médico pode ter múltiplos locais de atendimento (teleconsulta, consultório, hospital, clínica).

**Estrutura:**
```php
// Um médico tem vários locais
$doctor->serviceLocations // Collection de ServiceLocation

// Um local pertence a um médico
$location->doctor // Doctor
```

**Exemplo de Uso:**
```php
// Criar local para um médico
$doctor->serviceLocations()->create([
    'name' => 'Consultório Central',
    'type' => 'office',
    'address' => 'Rua das Flores, 123'
]);

// Buscar locais ativos de um médico
$doctor->serviceLocations()
    ->where('is_active', true)
    ->get();
```

#### 1.3. Doctor → AvailabilitySlot (HasMany)
- **Relação:** `hasMany(AvailabilitySlot::class, 'doctor_id')`
- **Campo:** `doctor_id` (FK em `doctor_availability_slots`)
- **Descrição:** Um médico possui múltiplos slots de disponibilidade (horários de atendimento). Podem ser **recorrentes** (ex: toda segunda-feira) ou **específicos** (ex: 13/11/2025).

**Estrutura:**
```php
// Um médico tem vários slots
$doctor->availabilitySlots // Collection de AvailabilitySlot

// Um slot pertence a um médico
$slot->doctor // Doctor
```

**Tipos de Slots:**
- **Recorrente (`recurring`)**: Repete semanalmente em um dia específico
  - Campo: `day_of_week` (monday, tuesday, etc.)
  - Campo: `specific_date` = null
  
- **Específico (`specific`)**: Apenas para uma data específica
  - Campo: `specific_date` (date)
  - Campo: `day_of_week` = null

**Exemplo de Uso:**
```php
// Slots recorrentes (todas as segundas-feiras)
$doctor->availabilitySlots()
    ->where('type', 'recurring')
    ->where('day_of_week', 'monday')
    ->get();

// Slots específicos para uma data
$doctor->availabilitySlots()
    ->where('type', 'specific')
    ->where('specific_date', '2025-11-13')
    ->get();
```

#### 1.4. Doctor → BlockedDate (HasMany)
- **Relação:** `hasMany(BlockedDate::class, 'doctor_id')`
- **Campo:** `doctor_id` (FK em `doctor_blocked_dates`)
- **Descrição:** Um médico pode bloquear datas específicas onde não atende (ex: férias, feriados).

**Estrutura:**
```php
// Um médico tem várias datas bloqueadas
$doctor->blockedDates // Collection de BlockedDate

// Uma data bloqueada pertence a um médico
$blockedDate->doctor // Doctor
```

**Exemplo de Uso:**
```php
// Bloquear uma data
$doctor->blockedDates()->create([
    'blocked_date' => '2025-12-25',
    'reason' => 'Natal'
]);

// Verificar se uma data está bloqueada
$isBlocked = $doctor->blockedDates()
    ->where('blocked_date', '2025-12-25')
    ->exists();
```

#### 1.5. Doctor → Appointments (HasMany - implícito)
- **Relação:** Não definida explicitamente no model Doctor, mas existe através da FK `doctor_id` em `appointments`
- **Campo:** `doctor_id` (FK em `appointments`)
- **Descrição:** Um médico pode ter múltiplos agendamentos (consultas agendadas com pacientes).

**Estrutura:**
```php
// Um médico tem vários appointments
Appointments::where('doctor_id', $doctor->id)->get();

// Um appointment pertence a um médico
$appointment->doctor // Doctor (BelongsTo)
```

### 2. ServiceLocation (Local de Atendimento)

**Tabela:** `doctor_service_locations`

**Relacionamentos:**

#### 2.1. ServiceLocation → Doctor (BelongsTo)
- **Relação:** `belongsTo(Doctor::class)`
- **Campo:** `doctor_id` (FK)
- **Descrição:** Cada local de atendimento pertence a um médico.

**Exemplo:**
```php
$location->doctor // Retorna o Doctor
```

#### 2.2. ServiceLocation → AvailabilitySlot (HasMany)
- **Relação:** `hasMany(AvailabilitySlot::class, 'location_id')`
- **Campo:** `location_id` (FK em `doctor_availability_slots`, nullable)
- **Descrição:** Um local pode ter múltiplos slots de disponibilidade associados. Um slot também pode não ter local específico (`location_id` = null).

**Estrutura:**
```php
// Um local tem vários slots
$location->availabilitySlots // Collection de AvailabilitySlot

// Um slot pode pertencer a um local (ou ser null)
$slot->location // ServiceLocation ou null
```

**Nota Importante:**
- `location_id` é **nullable**, permitindo slots sem local específico
- Isso permite que médicos configurem horários gerais sem associar a um local específico

**Exemplo de Uso:**
```php
// Buscar slots de um local específico
$location->availabilitySlots()
    ->where('is_active', true)
    ->get();

// Criar slot associado a um local
$location->availabilitySlots()->create([
    'doctor_id' => $doctor->id,
    'type' => 'recurring',
    'day_of_week' => 'monday',
    'start_time' => '08:00',
    'end_time' => '12:00'
]);
```

### 3. AvailabilitySlot (Slot de Disponibilidade)

**Tabela:** `doctor_availability_slots`

**Relacionamentos:**

#### 3.1. AvailabilitySlot → Doctor (BelongsTo)
- **Relação:** `belongsTo(Doctor::class)`
- **Campo:** `doctor_id` (FK, obrigatório)
- **Descrição:** Cada slot pertence a um médico.

#### 3.2. AvailabilitySlot → ServiceLocation (BelongsTo, nullable)
- **Relação:** `belongsTo(ServiceLocation::class, 'location_id')`
- **Campo:** `location_id` (FK, nullable)
- **Descrição:** Um slot pode estar associado a um local específico, mas isso é opcional.

**Regras de Negócio:**
- Se `location_id` = null: Slot é válido para qualquer local ou para slots gerais
- Se `location_id` != null: Slot é específico daquele local

### 4. BlockedDate (Data Bloqueada)

**Tabela:** `doctor_blocked_dates`

**Relacionamentos:**

#### 4.1. BlockedDate → Doctor (BelongsTo)
- **Relação:** `belongsTo(Doctor::class)`
- **Campo:** `doctor_id` (FK, obrigatório)
- **Descrição:** Cada data bloqueada pertence a um médico.

**Regras de Negócio:**
- Datas bloqueadas impedem agendamentos mesmo que existam slots configurados
- Ao consultar disponibilidade, primeiro verifica-se se a data está bloqueada
- Se bloqueada, retorna `is_blocked: true` sem slots disponíveis

## Fluxo de Dados Completo

### Fluxo 1: Configuração da Agenda pelo Médico

```
1. Médico cria locais de atendimento
   Doctor → ServiceLocation (HasMany)
   
2. Médico cria slots de disponibilidade
   Doctor → AvailabilitySlot (HasMany)
   (opcional) AvailabilitySlot → ServiceLocation (BelongsTo)
   
3. Médico bloqueia datas (opcional)
   Doctor → BlockedDate (HasMany)
```

**Exemplo Prático:**
```php
// 1. Criar local
$location = $doctor->serviceLocations()->create([
    'name' => 'Teleconsulta',
    'type' => 'teleconsultation'
]);

// 2. Criar slot recorrente (toda segunda-feira)
$doctor->availabilitySlots()->create([
    'type' => 'recurring',
    'day_of_week' => 'monday',
    'start_time' => '08:00',
    'end_time' => '12:00',
    'location_id' => $location->id
]);

// 3. Criar slot específico (13/11/2025)
$doctor->availabilitySlots()->create([
    'type' => 'specific',
    'specific_date' => '2025-11-13',
    'start_time' => '14:00',
    'end_time' => '18:00',
    'location_id' => $location->id
]);

// 4. Bloquear data (Natal)
$doctor->blockedDates()->create([
    'blocked_date' => '2025-12-25',
    'reason' => 'Natal'
]);
```

### Fluxo 2: Consulta de Disponibilidade pelo Paciente

```
1. Paciente seleciona médico e data
   GET /api/doctors/{doctor}/availability/{date}

2. Sistema verifica:
   a) Se a data está bloqueada (Doctor → BlockedDate)
   b) Slots recorrentes para o dia da semana (Doctor → AvailabilitySlot)
   c) Slots específicos para a data (Doctor → AvailabilitySlot)
   d) Appointments já agendados (Doctor → Appointments, via AvailabilityService)

3. Retorna slots disponíveis
   (Slots - Appointments já agendados - Datas bloqueadas)
```

**Exemplo de Consulta:**
```php
// Para a data 2025-11-13 (segunda-feira)

// 1. Verificar se está bloqueada
$isBlocked = $doctor->blockedDates()
    ->where('blocked_date', '2025-11-13')
    ->exists();

if ($isBlocked) {
    return ['is_blocked' => true, 'slots' => []];
}

// 2. Buscar slots recorrentes (monday)
$recurringSlots = $doctor->availabilitySlots()
    ->where('type', 'recurring')
    ->where('day_of_week', 'monday')
    ->where('is_active', true)
    ->get();

// 3. Buscar slots específicos
$specificSlots = $doctor->availabilitySlots()
    ->where('type', 'specific')
    ->where('specific_date', '2025-11-13')
    ->where('is_active', true)
    ->get();

// 4. Buscar appointments já agendados
$bookedSlots = Appointments::where('doctor_id', $doctor->id)
    ->whereDate('scheduled_at', '2025-11-13')
    ->whereIn('status', ['scheduled', 'rescheduled', 'in_progress'])
    ->pluck('scheduled_at')
    ->map(fn($dt) => Carbon::parse($dt)->format('H:i'))
    ->toArray();

// 5. Combinar e filtrar slots disponíveis
$allSlots = $recurringSlots->merge($specificSlots);
$availableSlots = $allSlots->filter(function($slot) use ($bookedSlots) {
    // Lógica para verificar quais horários dentro do slot não estão ocupados
    return true; // Simplificado
});
```

### Fluxo 3: Criação de Appointment pelo Paciente

```
1. Paciente seleciona horário disponível
   POST /appointments

2. Sistema valida:
   a) Horário está dentro de um slot válido
   b) Data não está bloqueada
   c) Horário não está ocupado
   d) Médico está ativo

3. Cria Appointment
   Appointments:
     - doctor_id (FK → Doctor)
     - patient_id (FK → Patient)
     - scheduled_at (datetime)
     - status = 'scheduled'
```

**Relacionamentos no Appointment:**
```php
// Appointment pertence a um médico
$appointment->doctor // Doctor (BelongsTo)

// Appointment pertence a um paciente
$appointment->patient // Patient (BelongsTo)

// Quando um appointment é criado, ele "ocupa" um slot de disponibilidade
// (não há FK direta, mas há relação lógica através de scheduled_at)
```

## Integração com Pacientes

### Como Pacientes Entram na Lógica

Os pacientes **NÃO** têm relacionamento direto com:
- `doctor_service_locations`
- `doctor_availability_slots`
- `doctor_blocked_dates`

**O relacionamento é indireto através de:**

1. **Appointments (consultas agendadas)**
   ```
   Patient → Appointments → Doctor
   ```

2. **Consulta de Disponibilidade (busca de horários)**
   ```
   Patient → GET /api/doctors/{doctor}/availability/{date}
            → ScheduleService.getAvailabilityForDate()
            → Doctor → AvailabilitySlot
            → Doctor → BlockedDate
            → Doctor → Appointments (para filtrar ocupados)
   ```

### Fluxo de Busca e Agendamento pelo Paciente

```
┌──────────┐
│ Patient  │
└────┬─────┘
     │
     │ 1. Busca médicos por especialização
     │    GET /api/doctors?specialization=...
     ▼
┌──────────────────┐
│ Lista de Doctors │
└────┬─────────────┘
     │
     │ 2. Seleciona médico e data
     │    GET /api/doctors/{doctor}/availability/{date}
     ▼
┌─────────────────────────────────────────┐
│ ScheduleService.getAvailabilityForDate() │
└────┬────────────────────────────────────┘
     │
     ├─→ Verifica BlockedDate (Doctor → BlockedDate)
     │   Se bloqueada: retorna is_blocked: true
     │
     ├─→ Busca AvailabilitySlot (Doctor → AvailabilitySlot)
     │   ├─ Slots recorrentes (day_of_week)
     │   └─ Slots específicos (specific_date)
     │
     ├─→ Busca Appointments (Doctor → Appointments)
     │   Filtra horários já ocupados
     │
     └─→ Retorna slots disponíveis
         ▼
┌──────────────────────┐
│ Slots Disponíveis    │
│ (com horários livres)│
└────┬─────────────────┘
     │
     │ 3. Paciente seleciona horário
     │    POST /appointments
     ▼
┌──────────────────────┐
│ Appointment Criado   │
│ doctor_id            │
│ patient_id           │
│ scheduled_at         │
└──────────────────────┘
```

## Regras de Negócio Relacionadas às Relações

### 1. Cascata de Deletação

#### ServiceLocation → AvailabilitySlot
- **Comportamento:** Se um local é deletado, seus slots **NÃO** são deletados automaticamente
- **Lógica:** `location_id` é setado como `null` (cascade set null)
- **Razão:** Preserva os slots mesmo se o local for removido

```php
// Ao deletar um local
$location->delete();
// Slots associados: location_id = null (mantidos)
```

#### Doctor → ServiceLocation
- **Comportamento:** Se um médico é deletado, seus locais são deletados (cascade delete)
- **Lógica:** `onDelete('cascade')` na migration

#### Doctor → AvailabilitySlot
- **Comportamento:** Se um médico é deletado, seus slots são deletados (cascade delete)
- **Lógica:** `onDelete('cascade')` na migration

#### Doctor → BlockedDate
- **Comportamento:** Se um médico é deletado, suas datas bloqueadas são deletadas (cascade delete)
- **Lógica:** `onDelete('cascade')` na migration

#### Doctor → Appointments
- **Comportamento:** Se um médico é deletado, seus appointments **NÃO** são deletados fisicamente
- **Lógica:** Soft delete em appointments (preserva histórico)
- **Razão:** Manter histórico de consultas mesmo após remoção do médico

### 2. Validação de Conflitos

#### Slots de Disponibilidade
- **Regra:** Não permite sobreposição de horários no mesmo dia/location
- **Validação:** `ScheduleService::validateSlotConflicts()`
- **Consulta:**
```php
// Verifica se já existe slot no mesmo período
$doctor->availabilitySlots()
    ->where('start_time', '<', $endTime)
    ->where('end_time', '>', $startTime)
    ->where('day_of_week', $dayOfWeek) // se recorrente
    ->where('specific_date', $date) // se específico
    ->exists();
```

### 3. Prioridade de Slots

Quando há múltiplos slots para a mesma data:
1. **Slots específicos** têm prioridade sobre slots recorrentes
   - Exemplo: Se há slot específico para 13/11/2025 e slot recorrente para segunda-feira (13/11/2025 é segunda), ambos são considerados

2. **Slots com location_id** têm prioridade sobre slots gerais (`location_id` = null)
   - Se há slot para um local específico, ele é usado
   - Se não há slot específico, usa slot geral

3. **Datas bloqueadas** têm prioridade absoluta
   - Se a data está bloqueada, nenhum slot é retornado

### 4. Relação entre Appointment e AvailabilitySlot

**IMPORTANTE:** Não há FK direta entre `appointments` e `availability_slots`.

**Relação Lógica:**
- Um appointment ocupa um horário dentro de um slot
- A validação é feita através de:
  - `appointment.scheduled_at` (data e hora)
  - Comparação com `slot.start_time` e `slot.end_time`
  - Comparação com `slot.specific_date` ou `slot.day_of_week`

**Exemplo:**
```php
// Appointment agendado para: 2025-11-13 10:00 (segunda-feira)

// Verifica se está dentro de algum slot:
$slots = $doctor->availabilitySlots()
    ->where(function($q) use ($appointment) {
        // Slots recorrentes (monday)
        $q->where(function($recurring) use ($appointment) {
            $recurring->where('type', 'recurring')
                ->where('day_of_week', 'monday') // 13/11/2025 é segunda
                ->where('start_time', '<=', '10:00')
                ->where('end_time', '>', '10:00');
        })
        // Slots específicos
        ->orWhere(function($specific) use ($appointment) {
            $specific->where('type', 'specific')
                ->where('specific_date', '2025-11-13')
                ->where('start_time', '<=', '10:00')
                ->where('end_time', '>', '10:00');
        });
    })
    ->exists();
```

## Queries Eficientes

### Buscar Disponibilidade Completa de um Médico

```php
// Carregar tudo de uma vez (eager loading)
$doctor = Doctor::with([
    'serviceLocations' => function($q) {
        $q->where('is_active', true);
    },
    'availabilitySlots' => function($q) {
        $q->where('is_active', true);
    },
    'blockedDates' => function($q) {
        $q->where('blocked_date', '>=', Carbon::today());
    }
])->find($doctorId);
```

### Buscar Slots Disponíveis para uma Data Específica

```php
$date = Carbon::parse('2025-11-13');
$dayOfWeek = strtolower($date->format('l')); // 'monday'

$slots = AvailabilitySlot::where('doctor_id', $doctorId)
    ->where('is_active', true)
    ->where(function($q) use ($date, $dayOfWeek) {
        // Slots recorrentes
        $q->where(function($recurring) use ($dayOfWeek) {
            $recurring->where('type', 'recurring')
                ->where('day_of_week', $dayOfWeek);
        })
        // Slots específicos
        ->orWhere(function($specific) use ($date) {
            $specific->where('type', 'specific')
                ->where('specific_date', $date->format('Y-m-d'));
        });
    })
    ->with('location') // Carregar local associado
    ->get();
```

### Verificar Disponibilidade Considerando Appointments

```php
// Usando o AvailabilityService
$availabilityService = new AvailabilityService();
$availableSlots = $availabilityService->getAvailableSlotsForDate($doctor, $date);

// Internamente, o service:
// 1. Busca slots (recorrentes + específicos)
// 2. Busca appointments já agendados
// 3. Remove horários ocupados dos slots
// 4. Retorna array de horários disponíveis
```

## Resumo das Relações

| Entidade | Relação | Com Entidade | Tipo | Campo FK |
|----------|---------|--------------|------|----------|
| Doctor | belongsTo | User | 1:1 | `user_id` |
| Doctor | hasMany | ServiceLocation | 1:N | `doctor_id` |
| Doctor | hasMany | AvailabilitySlot | 1:N | `doctor_id` |
| Doctor | hasMany | BlockedDate | 1:N | `doctor_id` |
| Doctor | hasMany* | Appointments | 1:N | `doctor_id` |
| ServiceLocation | belongsTo | Doctor | N:1 | `doctor_id` |
| ServiceLocation | hasMany | AvailabilitySlot | 1:N | `location_id` |
| AvailabilitySlot | belongsTo | Doctor | N:1 | `doctor_id` |
| AvailabilitySlot | belongsTo | ServiceLocation | N:1 | `location_id` (nullable) |
| BlockedDate | belongsTo | Doctor | N:1 | `doctor_id` |
| Appointments | belongsTo | Doctor | N:1 | `doctor_id` |
| Appointments | belongsTo | Patient | N:1 | `patient_id` |

*Nota: Relação implícita através de `appointments.doctor_id`, não definida explicitamente no model Doctor.

## Considerações Finais

1. **Soft Deletes:** Todas as tabelas principais usam soft deletes, preservando histórico
2. **Performance:** Índices criados nas FKs e campos de busca frequente
3. **Flexibilidade:** Slots podem existir sem local específico (`location_id` = null)
4. **Validação:** Conflitos são validados antes de criar slots ou appointments
5. **Pacientes:** Interagem indiretamente através de appointments e consulta de disponibilidade

Este sistema permite flexibilidade máxima na configuração de disponibilidade, mantendo integridade referencial e performance adequada.

