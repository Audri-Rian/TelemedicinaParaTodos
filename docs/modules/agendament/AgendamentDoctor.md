# Módulo de Agendamento do Médico

## Visão Geral

Este módulo implementa a funcionalidade completa de gerenciamento de agenda do médico, permitindo que ele configure locais de atendimento, slots de disponibilidade (recorrentes e específicos) e datas bloqueadas.

## Estrutura de Dados

### Tabelas do Banco de Dados

#### `doctor_service_locations`
Armazena os locais onde o médico atende.

**Campos:**
- `id` (UUID, Primary Key)
- `doctor_id` (UUID, Foreign Key -> doctors.id)
- `name` (string) - Nome do local
- `type` (enum) - Tipo: `teleconsultation`, `office`, `hospital`, `clinic`
- `address` (text, nullable) - Endereço completo
- `phone` (string, nullable, max:20) - Telefone de contato
- `description` (text, nullable) - Observações
- `is_active` (boolean, default: true)
- `created_at`, `updated_at`, `deleted_at`

**Índices:**
- `idx_doc_active` - (doctor_id, is_active)
- `idx_type` - (type)

#### `doctor_availability_slots`
Armazena os horários de disponibilidade do médico.

**Campos:**
- `id` (UUID, Primary Key)
- `doctor_id` (UUID, Foreign Key -> doctors.id)
- `location_id` (UUID, nullable, Foreign Key -> doctor_service_locations.id)
- `type` (enum) - Tipo: `recurring` ou `specific`
- `day_of_week` (enum, nullable) - Dia da semana (apenas se type = recurring): `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`
- `specific_date` (date, nullable) - Data específica (apenas se type = specific)
- `start_time` (time) - Horário de início
- `end_time` (time) - Horário de fim
- `is_active` (boolean, default: true)
- `created_at`, `updated_at`, `deleted_at`

**Índices:**
- `idx_doc_type_active` - (doctor_id, type, is_active)
- `idx_doc_day_active` - (doctor_id, day_of_week, is_active)
- `idx_doc_date_active` - (doctor_id, specific_date, is_active)
- `idx_location_active` - (location_id, is_active)
- `idx_specific_date` - (specific_date)
- `idx_doc_type_day` - (doctor_id, type, day_of_week, is_active)
- `idx_doc_type_spec` - (doctor_id, type, specific_date, is_active)

#### `doctor_blocked_dates`
Armazena as datas bloqueadas onde o médico não atende.

**Campos:**
- `id` (UUID, Primary Key)
- `doctor_id` (UUID, Foreign Key -> doctors.id)
- `blocked_date` (date) - Data bloqueada
- `reason` (string, nullable, max:500) - Motivo do bloqueio
- `created_at`, `updated_at`, `deleted_at`

**Índices:**
- `idx_doc_blocked_date` - (doctor_id, blocked_date)
- `idx_blocked_date` - (blocked_date)

## Models

### `App\Models\ServiceLocation`
Model para locais de atendimento.

**Relacionamentos:**
- `doctor()` - BelongsTo Doctor
- `availabilitySlots()` - HasMany AvailabilitySlot

**Atributos Computados:**
- `type_label` - Retorna o label do tipo em português

### `App\Models\AvailabilitySlot`
Model para slots de disponibilidade.

**Relacionamentos:**
- `doctor()` - BelongsTo Doctor
- `location()` - BelongsTo ServiceLocation

**Scopes:**
- `recurring()` - Filtra slots recorrentes
- `specific()` - Filtra slots específicos
- `byDayOfWeek($dayOfWeek)` - Filtra por dia da semana
- `bySpecificDate($date)` - Filtra por data específica
- `active()` - Filtra apenas ativos

**Atributos Computados:**
- `day_of_week_label` - Retorna o label do dia da semana

### `App\Models\Doctor\BlockedDate`
Model para datas bloqueadas.

**Relacionamentos:**
- `doctor()` - BelongsTo Doctor

**Scopes:**
- `byDoctor($doctorId)` - Filtra por médico
- `byDate($date)` - Filtra por data
- `byDateRange($startDate, $endDate)` - Filtra por intervalo de datas
- `futureDates()` - Filtra apenas datas futuras

## Services

### `App\Services\Doctor\ScheduleService`
Service centralizado para lógica de negócio da agenda.

**Métodos:**

#### `getScheduleConfig(Doctor $doctor): array`
Retorna a configuração completa da agenda do médico.

**Retorno:**
```php
[
    'locations' => [...],
    'recurring_slots' => [...],
    'specific_slots' => [...],
    'blocked_dates' => [...]
]
```

#### `saveScheduleConfig(Doctor $doctor, array $data): array`
Salva configuração completa da agenda em batch.

#### `getAvailabilityForDate(Doctor $doctor, Carbon $date): array`
Retorna disponibilidade para uma data específica, considerando:
- Slots recorrentes
- Slots específicos
- Datas bloqueadas
- Appointments existentes

**Retorno:**
```php
[
    'date' => '2025-11-13',
    'formatted_date' => '13/11/2025',
    'is_blocked' => false,
    'available_slots' => [...],
]
```

#### `validateSlotConflicts(...): bool`
Valida conflitos de horários antes de criar/atualizar slots.

**Parâmetros:**
- `Doctor $doctor`
- `string $startTime`
- `string $endTime`
- `?string $dayOfWeek`
- `?Carbon $specificDate`
- `?string $locationId`
- `?string $excludeSlotId` - Excluir slot na validação (para updates)

## Controllers

### `App\Http\Controllers\Doctor\DoctorScheduleController`

#### `show(Doctor $doctor): JsonResponse|Response`
**Endpoint:** `GET /api/doctors/{doctor}/schedule` ou `GET /doctor/schedule`

Carrega configuração completa da agenda.

**Resposta:**
```json
{
    "success": true,
    "data": {
        "locations": [...],
        "recurring_slots": [...],
        "specific_slots": [...],
        "blocked_dates": [...]
    }
}
```

#### `save(StoreScheduleConfigRequest $request, Doctor $doctor): JsonResponse`
**Endpoint:** `POST /api/doctors/{doctor}/schedule/save` ou `POST /doctor/schedule/save`

Salva configuração completa da agenda em batch.

**Body:**
```json
{
    "locations": [...],
    "recurring_slots": [...],
    "specific_slots": [...],
    "blocked_dates": [...]
}
```

### `App\Http\Controllers\Doctor\DoctorServiceLocationController`

#### `store(StoreServiceLocationRequest $request, Doctor $doctor): JsonResponse`
**Endpoint:** `POST /api/doctors/{doctor}/locations` ou `POST /doctor/locations`

Cria novo local de atendimento.

**Body:**
```json
{
    "name": "Consultório Central",
    "type": "office",
    "address": "Rua das Flores, 123",
    "phone": "(11) 98765-4321",
    "description": "Observações"
}
```

#### `update(UpdateServiceLocationRequest $request, Doctor $doctor, ServiceLocation $location): JsonResponse`
**Endpoint:** `PUT /api/doctors/{doctor}/locations/{location}` ou `PUT /doctor/locations/{location}`

Atualiza local de atendimento existente.

#### `destroy(Doctor $doctor, ServiceLocation $location): JsonResponse`
**Endpoint:** `DELETE /api/doctors/{doctor}/locations/{location}` ou `DELETE /doctor/locations/{location}`

Deleta local de atendimento. Se houver slots associados, apenas desativa o local.

### `App\Http\Controllers\Doctor\DoctorAvailabilitySlotController`

#### `store(StoreAvailabilitySlotRequest $request, Doctor $doctor): JsonResponse`
**Endpoint:** `POST /api/doctors/{doctor}/availability` ou `POST /doctor/availability`

Cria novo slot de disponibilidade.

**Body (Recorrente):**
```json
{
    "type": "recurring",
    "day_of_week": "monday",
    "start_time": "08:00",
    "end_time": "12:00",
    "location_id": "uuid-do-local"
}
```

**Body (Específico):**
```json
{
    "type": "specific",
    "specific_date": "2025-11-13",
    "start_time": "08:00",
    "end_time": "12:00",
    "location_id": "uuid-do-local"
}
```

#### `update(UpdateAvailabilitySlotRequest $request, Doctor $doctor, AvailabilitySlot $slot): JsonResponse`
**Endpoint:** `PUT /api/doctors/{doctor}/availability/{slot}` ou `PUT /doctor/availability/{slot}`

Atualiza slot de disponibilidade existente.

#### `destroy(Doctor $doctor, AvailabilitySlot $slot): JsonResponse`
**Endpoint:** `DELETE /api/doctors/{doctor}/availability/{slot}` ou `DELETE /doctor/availability/{slot}`

Deleta slot de disponibilidade.

#### `getByDate(Request $request, Doctor $doctor, string $date): JsonResponse`
**Endpoint:** `GET /api/doctors/{doctor}/availability/{date}` (público)

Retorna disponibilidade do médico para uma data específica. Usado por pacientes para consultar horários disponíveis.

**Resposta:**
```json
{
    "success": true,
    "data": {
        "date": "2025-11-13",
        "formatted_date": "13/11/2025",
        "is_blocked": false,
        "available_slots": [...]
    }
}
```

### `App\Http\Controllers\Doctor\DoctorBlockedDateController`

#### `store(StoreBlockedDateRequest $request, Doctor $doctor): JsonResponse`
**Endpoint:** `POST /api/doctors/{doctor}/blocked-dates` ou `POST /doctor/blocked-dates`

Cria nova data bloqueada.

**Body:**
```json
{
    "blocked_date": "2025-11-13",
    "reason": "Férias"
}
```

#### `destroy(Doctor $doctor, BlockedDate $blockedDate): JsonResponse`
**Endpoint:** `DELETE /api/doctors/{doctor}/blocked-dates/{id}` ou `DELETE /doctor/blocked-dates/{id}`

Remove data bloqueada.

## Requests (Validação)

### `App\Http\Requests\Doctor\StoreServiceLocationRequest`
Valida criação de local de atendimento.

**Regras:**
- `name`: required|string|max:255
- `type`: required|in:teleconsultation,office,hospital,clinic
- `address`: nullable|string
- `phone`: nullable|string|max:20
- `description`: nullable|string

### `App\Http\Requests\Doctor\UpdateServiceLocationRequest`
Valida atualização de local de atendimento.

**Regras:** Mesmas de StoreServiceLocationRequest + `is_active`: sometimes|boolean

### `App\Http\Requests\Doctor\StoreAvailabilitySlotRequest`
Valida criação de slot de disponibilidade.

**Regras:**
- `type`: required|in:recurring,specific
- `day_of_week`: required_if:type,recurring|in:monday,tuesday,...,sunday
- `specific_date`: required_if:type,specific|date|after_or_equal:today
- `start_time`: required|date_format:H:i
- `end_time`: required|date_format:H:i|after:start_time
- `location_id`: nullable|exists:doctor_service_locations,id
- `is_active`: sometimes|boolean

### `App\Http\Requests\Doctor\UpdateAvailabilitySlotRequest`
Valida atualização de slot de disponibilidade.

**Regras:** Mesmas de StoreAvailabilitySlotRequest (com `sometimes`)

### `App\Http\Requests\Doctor\StoreBlockedDateRequest`
Valida criação de data bloqueada.

**Regras:**
- `blocked_date`: required|date|after_or_equal:today
- `reason`: nullable|string|max:500

### `App\Http\Requests\Doctor\StoreScheduleConfigRequest`
Valida batch save da configuração completa.

**Regras:** Combina regras dos outros requests

## Policies

### `App\Policies\Doctor\ServiceLocationPolicy`
Autorização para operações em locais de atendimento.

**Regras:**
- Médico só pode gerenciar seus próprios locais
- `viewAny`, `view`, `create`, `update`, `delete`

### `App\Policies\Doctor\AvailabilitySlotPolicy`
Autorização para operações em slots de disponibilidade.

**Regras:**
- Médico só pode gerenciar seus próprios slots
- `viewAny`, `view`, `create`, `update`, `delete`

### `App\Policies\Doctor\BlockedDatePolicy`
Autorização para operações em datas bloqueadas.

**Regras:**
- Médico só pode gerenciar suas próprias datas bloqueadas
- `viewAny`, `view`, `create`, `delete`

## Regras de Negócio

1. **Autorização:**
   - Médico só pode gerenciar seus próprios recursos (locais, slots, bloqueios)
   - Todas as operações verificam se o médico autenticado é o dono do recurso

2. **Validação de Conflitos:**
   - Não permite sobreposição de horários no mesmo dia/location
   - Validação feita antes de criar/atualizar slots

3. **Datas Bloqueadas:**
   - Datas bloqueadas impedem agendamentos mesmo que existam slots configurados
   - Ao consultar disponibilidade, datas bloqueadas retornam `is_blocked: true`

4. **Validação de Datas:**
   - Slots específicos e bloqueios devem ser >= hoje
   - Validação feita nos Requests

5. **Validação de Horários:**
   - `end_time` deve ser > `start_time`
   - Validação feita nos Requests

6. **Deletar Local:**
   - Se houver slots associados, apenas desativa o local (`is_active = false`)
   - Se não houver slots, deleta fisicamente

7. **Disponibilidade por Data:**
   - Considera slots recorrentes (baseado no dia da semana)
   - Considera slots específicos
   - Exclui appointments já agendados
   - Exclui datas bloqueadas

## Rotas

### Rotas Protegidas (Médicos)
```
GET    /doctor/schedule              - Carregar configuração
POST   /doctor/schedule/save         - Salvar agenda completa
POST   /doctor/locations             - Criar local
PUT    /doctor/locations/{location}  - Atualizar local
DELETE /doctor/locations/{location}  - Deletar local
POST   /doctor/availability          - Criar slot
PUT    /doctor/availability/{slot}   - Atualizar slot
DELETE /doctor/availability/{slot}   - Deletar slot
POST   /doctor/blocked-dates         - Criar bloqueio
DELETE /doctor/blocked-dates/{id}    - Remover bloqueio
```

### Rotas Públicas (API)
```
GET /api/doctors/{doctor}/availability/{date} - Disponibilidade por data (para pacientes)
```

## Exemplos de Uso

### Carregar Configuração da Agenda
```javascript
const response = await fetch('/doctor/schedule');
const { scheduleConfig } = await response.json();
```

### Criar Local de Atendimento
```javascript
const response = await fetch('/doctor/locations', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        name: 'Consultório Central',
        type: 'office',
        address: 'Rua das Flores, 123',
        phone: '(11) 98765-4321'
    })
});
```

### Criar Slot Recorrente
```javascript
const response = await fetch('/doctor/availability', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        type: 'recurring',
        day_of_week: 'monday',
        start_time: '08:00',
        end_time: '12:00',
        location_id: 'uuid-do-local'
    })
});
```

### Criar Slot Específico
```javascript
const response = await fetch('/doctor/availability', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        type: 'specific',
        specific_date: '2025-11-13',
        start_time: '08:00',
        end_time: '12:00',
        location_id: 'uuid-do-local'
    })
});
```

### Bloquear Data
```javascript
const response = await fetch('/doctor/blocked-dates', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        blocked_date: '2025-11-13',
        reason: 'Férias'
    })
});
```

### Consultar Disponibilidade (Paciente)
```javascript
const response = await fetch('/api/doctors/{doctorId}/availability/2025-11-13');
const { data } = await response.json();
// data.is_blocked
// data.available_slots
```

