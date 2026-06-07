# Integração Front-End ↔ Back-End: Módulo de Appointments (Perspectiva do Paciente)

## 📋 Visão Geral

Este documento mapeia a integração entre o módulo back-end de Appointments (documentado em `AppointmentsArchitecture.md`) e as páginas existentes no front-end, focando na experiência do usuário paciente.

**Status do Back-End:**

- ✅ Regras de negócio implementadas
- ✅ Fluxo de status definido: `scheduled` → `in_progress` → `completed`
- ✅ Desvios: `cancelled`, `rescheduled`, `no_show`
- ✅ Policies e validações funcionais
- ✅ Endpoints REST disponíveis

**Status do Front-End:**

- ✅ Páginas base criadas (estáticas/mockadas)
- ⚠️ Necessita integração com back-end
- ⚠️ Componentes precisam ser convertidos para dinâmicos

---

## 🗺️ Mapa de Fluxo Completo do Paciente

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUXO COMPLETO DO PACIENTE                    │
└─────────────────────────────────────────────────────────────────┘

1. BUSCA DE MÉDICO
   └─> /patient/search-consultations
       ├─> Filtros: especialidade, data, valor, disponibilidade
       ├─> Lista médicos disponíveis
       └─> Botão "Agendar Consulta" → [2]

2. AGENDAMENTO
   └─> /patient/schedule-consultation?doctor_id={id}&date={date}
       ├─> Seleção de data/horário disponível
       ├─> Escolha tipo (online/presencial)
       ├─> Resumo do médico e valor
       └─> POST /appointments → [3]

3. CONFIRMAÇÃO
   └─> /appointments/{id} (redirect após criação)
       ├─> Exibe appointment criado
       ├─> Status: "scheduled"
       └─> Opções: cancelar, reagendar, ver detalhes

4. AGUARDANDO CONSULTA
   └─> /patient/dashboard ou /patient/history-consultations
       ├─> Lista appointments com status "scheduled"
       ├─> Badge de status
       └─> Botão "Iniciar Consulta" (quando disponível)

5. INÍCIO DA CONSULTA
   └─> POST /appointments/{id}/start
       ├─> Status muda para "in_progress"
       ├─> WebSocket notifica ambos os participantes
       └─> Redireciona para /patient/video-call?appointment={id}

6. DURANTE A CONSULTA
   └─> /patient/video-call
       ├─> Vídeo chamada ativa
       ├─> Status: "in_progress"
       └─> Botão "Finalizar Consulta" → [7]

7. FINALIZAÇÃO
   └─> POST /appointments/{id}/end
       ├─> Status muda para "completed"
       ├─> WebSocket notifica finalização
       └─> Redireciona para /patient/consultation-details/{id}

8. PÓS-CONSULTA
   └─> /patient/consultation-details/{id}
       ├─> Status: "completed"
       ├─> Resumo clínico, prescrições, anexos
       ├─> Timeline de eventos
       └─> Feedback e avaliação

9. HISTÓRICO
   └─> /patient/history-consultations
       ├─> Filtros: próximas, concluídas, canceladas, todas
       ├─> Lista paginada de appointments
       └─> Links para detalhes de cada consulta
```

---

## 📄 Páginas e Integrações Detalhadas

### 1. `/patient/search-consultations` (SearchConsultations.vue)

**Função UX:** Página de busca e descoberta de médicos disponíveis.

**Estado Atual:**

- ✅ UI completa com filtros
- ✅ Lista de médicos (mockada)
- ⚠️ Dados estáticos (`exampleDoctors`)
- ⚠️ Sem integração com back-end

**Integrações Necessárias:**

#### 1.1. Endpoint para Listar Médicos Disponíveis

**Back-End (a criar):**

```php
// app/Http/Controllers/Patient/PatientSearchConsultationsController.php
public function searchConsultations(Request $request): Response
{
    $query = Doctor::query()
        ->with(['user', 'specializations'])
        ->active();

    // Filtros
    if ($request->has('specialization_id')) {
        $query->bySpecialization($request->get('specialization_id'));
    }

    if ($request->has('search')) {
        $search = $request->get('search');
        $query->whereHas('user', function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        })->orWhereHas('specializations', function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    // Verificar disponibilidade de horários
    if ($request->has('date')) {
        $date = Carbon::parse($request->get('date'));
        // Filtrar médicos com slots disponíveis na data
    }

    $doctors = $query->paginate(12);
    $specializations = Specialization::all();

    return Inertia::render('Patient/SearchConsultations', [
        'availableDoctors' => $doctors,
        'specializations' => $specializations,
    ]);
}
```

**Front-End (adaptações):**

```typescript
// resources/js/pages/Patient/SearchConsultations.vue

// Remover exampleDoctors estáticos
// Usar props.availableDoctors do back-end
const filteredDoctors = computed(() => {
    let doctors = props.availableDoctors || [];

    // Aplicar filtros locais (busca, checkboxes)
    if (searchQuery.value.trim()) {
        // ... filtro local
    }

    return doctors;
});

// Botão "Agendar Consulta" deve passar doctor_id
<Link
    :href="patientRoutes.scheduleConsultation({
        query: { doctor_id: doctor.id }
    })"
>
    Agendar Consulta
</Link>
```

#### 1.2. Endpoint para Verificar Disponibilidade de Horários

**Back-End (a criar):**

```php
// app/Http/Controllers/AppointmentsController.php
public function availability(Request $request): JsonResponse
{
    $doctorId = $request->get('doctor_id');
    $date = Carbon::parse($request->get('date'));

    // Buscar appointments existentes do médico na data
    $existingAppointments = Appointments::query()
        ->where('doctor_id', $doctorId)
        ->whereDate('scheduled_at', $date)
        ->whereIn('status', [
            Appointments::STATUS_SCHEDULED,
            Appointments::STATUS_RESCHEDULED,
            Appointments::STATUS_IN_PROGRESS
        ])
        ->get()
        ->pluck('scheduled_at')
        ->map(fn($dt) => $dt->format('H:i'))
        ->toArray();

    // Gerar slots disponíveis baseado no availability_schedule do médico
    $doctor = Doctor::findOrFail($doctorId);
    $schedule = $doctor->availability_schedule ?? [];
    $availableSlots = $this->generateAvailableSlots($date, $schedule, $existingAppointments);

    return response()->json([
        'date' => $date->format('Y-m-d'),
        'available_slots' => $availableSlots,
    ]);
}
```

**Front-End (chamada opcional):**

```typescript
// Ao selecionar um médico, verificar disponibilidade
const checkAvailability = async (doctorId: string, date: string) => {
    const response = await axios.get('/api/appointments/availability', {
        params: { doctor_id: doctorId, date },
    });
    return response.data.available_slots;
};
```

---

### 2. `/patient/schedule-consultation` (ScheduleConsultation.vue)

**Função UX:** Página de confirmação e marcação do appointment.

**Estado Atual:**

- ✅ UI completa com calendário e seleção de horário
- ⚠️ Dados do médico estáticos
- ⚠️ Sem validação de disponibilidade real
- ⚠️ Botão "Confirmar Agendamento" não funcional

**Integrações Necessárias:**

#### 2.1. Receber Dados do Médico via Query Params

**Back-End:**

```php
// app/Http/Controllers/Patient/ScheduleConsultationController.php
public function scheduleConsultation(Request $request): Response
{
    $doctorId = $request->get('doctor_id');

    if (!$doctorId) {
        return redirect()->route('patient.search-consultations')
            ->with('error', 'Selecione um médico para agendar.');
    }

    $doctor = Doctor::with(['user', 'specializations'])->findOrFail($doctorId);

    // Buscar horários disponíveis para os próximos 30 dias
    $availableDates = $this->getAvailableDates($doctor);

    return Inertia::render('Patient/ScheduleConsultation', [
        'doctor' => $doctor,
        'availableDates' => $availableDates,
        'patient' => auth()->user()->patient,
    ]);
}
```

**Front-End:**

```typescript
// resources/js/pages/Patient/ScheduleConsultation.vue

interface Props {
    doctor: {
        id: string;
        user: { name: string; email: string; avatar?: string };
        specializations: Array<{ id: string; name: string }>;
        consultation_fee: number;
        crm: string;
        biography: string;
    };
    availableDates: Array<{
        date: string;
        available_slots: string[];
    }>;
    patient: {
        id: string;
        user: { name: string };
    };
}

const props = defineProps<Props>();

// Usar props.doctor ao invés de selectedDoctor estático
const selectedDoctor = computed(() => props.doctor);

// Usar props.availableDates para popular calendário
const availableTimes = computed(() => {
    const selectedDateStr = formatDateForApi(selectedDate.value);
    const dateData = props.availableDates.find((d) => d.date === selectedDateStr);
    return dateData?.available_slots || [];
});
```

#### 2.2. Criar Appointment (POST /appointments)

**Front-End:**

```typescript
// resources/js/pages/Patient/ScheduleConsultation.vue

import { router } from '@inertiajs/vue3';
import * as appointmentsRoutes from '@/routes/appointments';

const isSubmitting = ref(false);
const errors = ref<Record<string, string>>({});

const confirmAppointment = async () => {
    if (!selectedDate.value || !selectedTime.value) {
        errors.value.datetime = 'Selecione data e horário';
        return;
    }

    isSubmitting.value = true;

    // Combinar data e horário
    const scheduledAt = `${selectedDate.value}T${selectedTime.value}:00`;

    // Montar payload
    const payload = {
        doctor_id: props.doctor.id,
        patient_id: props.patient.id,
        scheduled_at: scheduledAt,
        type: consultationType.value, // 'online' ou 'presential'
        notes: '', // Opcional: campo de observações
    };

    try {
        // Usar Inertia para POST
        router.post(appointmentsRoutes.store.url(), payload, {
            onSuccess: (page) => {
                // Redirecionamento automático para /appointments/{id}
                // via redirect no controller
            },
            onError: (errors) => {
                // Exibir erros de validação
                errors.value = errors;
            },
            onFinish: () => {
                isSubmitting.value = false;
            }
        });
    } catch (error) {
        console.error('Erro ao criar appointment:', error);
        errors.value.general = 'Erro ao agendar consulta. Tente novamente.';
        isSubmitting.value = false;
    }
};

// Atualizar botão
<Button
    @click="confirmAppointment"
    :disabled="isSubmitting || !selectedDate || !selectedTime"
    class="bg-primary hover:bg-primary/90"
>
    <span v-if="isSubmitting">Agendando...</span>
    <span v-else>Confirmar Agendamento</span>
</Button>
```

**Back-End (já existe):**

```php
// app/Http/Controllers/AppointmentsController.php::store()
// Já implementado e funcional
```

---

### 3. `/appointments/{id}` ou `/patient/consultation-details/{id}` (ConsultationDetails.vue)

**Função UX:** Exibir detalhes completos de uma consulta.

**Estado Atual:**

- ✅ UI completa com timeline, prescrições, anexos
- ⚠️ Dados estáticos
- ⚠️ Sem integração com back-end

**Integrações Necessárias:**

#### 3.1. Buscar Appointment com Relacionamentos

**Back-End:**

```php
// app/Http/Controllers/Patient/PatientConsultationDetailsController.php
public function consultationDetails(string $id): Response
{
    $appointment = Appointments::with([
        'doctor.user',
        'doctor.specializations',
        'patient.user',
        'logs.user'
    ])->findOrFail($id);

    // Verificar se o paciente autenticado tem acesso
    if ($appointment->patient_id !== auth()->user()->patient->id) {
        abort(403);
    }

    return Inertia::render('Patient/ConsultationDetails', [
        'appointment' => $appointment,
    ]);
}
```

**Front-End:**

```typescript
// resources/js/pages/Patient/ConsultationDetails.vue

interface Props {
    appointment: {
        id: string;
        status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'rescheduled' | 'no_show';
        scheduled_at: string;
        started_at: string | null;
        ended_at: string | null;
        access_code: string;
        video_recording_url: string | null;
        notes: string | null;
        doctor: {
            id: string;
            user: { name: string; email: string };
            specializations: Array<{ name: string }>;
            crm: string;
        };
        patient: {
            id: string;
            user: { name: string };
        };
        logs: Array<{
            id: string;
            event: string;
            payload: Record<string, any>;
            created_at: string;
            user: { name: string } | null;
        }>;
    };
}

const props = defineProps<Props>();

// Mapear status para badge
const statusBadge = computed(() => {
    const statusMap = {
        scheduled: { label: 'Agendada', class: 'bg-yellow-100 text-yellow-700' },
        in_progress: { label: 'Em Andamento', class: 'bg-blue-100 text-blue-700' },
        completed: { label: 'Concluída', class: 'bg-green-100 text-green-700' },
        cancelled: { label: 'Cancelada', class: 'bg-red-100 text-red-700' },
        rescheduled: { label: 'Reagendada', class: 'bg-purple-100 text-purple-700' },
        no_show: { label: 'Não Compareceu', class: 'bg-gray-100 text-gray-700' },
    };
    return statusMap[props.appointment.status] || statusMap.scheduled;
});

// Construir timeline a partir dos logs
const timeline = computed(() => {
    return props.appointment.logs.map((log) => ({
        time: formatTime(log.created_at),
        event: mapEventName(log.event),
        description: formatEventDescription(log.event, log.payload),
    }));
});

// Ações baseadas no status
const canStart = computed(() => {
    return ['scheduled', 'rescheduled'].includes(props.appointment.status) && canBeStarted(props.appointment);
});

const canCancel = computed(() => {
    return ['scheduled', 'rescheduled'].includes(props.appointment.status) && canBeCancelled(props.appointment);
});

// Funções de ação
const startAppointment = async () => {
    await axios.post(`/appointments/${props.appointment.id}/start`);
    // WebSocket atualizará o status automaticamente
};

const cancelAppointment = async (reason?: string) => {
    await axios.post(`/appointments/${props.appointment.id}/cancel`, { reason });
    // Atualizar página ou redirecionar
    router.reload();
};
```

---

### 4. `/patient/history-consultations` (HistoryConsultations.vue)

**Função UX:** Listar histórico de consultas com filtros.

**Estado Atual:**

- ✅ UI completa com filtros e paginação
- ⚠️ Dados estáticos (`allConsultations`)
- ⚠️ Sem integração com back-end

**Integrações Necessárias:**

#### 4.1. Listar Appointments do Paciente

**Back-End:**

```php
// app/Http/Controllers/Patient/PatientHistoryConsultationsController.php
public function historyConsultations(Request $request): Response
{
    $patient = auth()->user()->patient;

    $query = Appointments::query()
        ->with(['doctor.user', 'doctor.specializations'])
        ->where('patient_id', $patient->id)
        ->orderBy('scheduled_at', 'desc');

    // Filtros
    if ($request->has('status')) {
        $status = $request->get('status');
        if ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'completed') {
            $query->completed();
        } elseif ($status === 'cancelled') {
            $query->cancelled();
        } else {
            // 'all' - sem filtro
        }
    }

    $appointments = $query->paginate(10);

    // Estatísticas
    $stats = [
        'total' => Appointments::where('patient_id', $patient->id)->count(),
        'upcoming' => Appointments::where('patient_id', $patient->id)->upcoming()->count(),
        'completed' => Appointments::where('patient_id', $patient->id)->completed()->count(),
        'cancelled' => Appointments::where('patient_id', $patient->id)->cancelled()->count(),
    ];

    return Inertia::render('Patient/HistoryConsultations', [
        'appointments' => $appointments,
        'stats' => $stats,
        'filters' => $request->only(['status']),
    ]);
}
```

**Front-End:**

```typescript
// resources/js/pages/Patient/HistoryConsultations.vue

interface Props {
    appointments: {
        data: Array<{
            id: string;
            scheduled_at: string;
            status: string;
            doctor: {
                user: { name: string; avatar?: string };
                specializations: Array<{ name: string }>;
            };
        }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        upcoming: number;
        completed: number;
        cancelled: number;
    };
    filters: {
        status?: string;
    };
}

const props = defineProps<Props>();

// Usar props.appointments ao invés de allConsultations estático
const consultations = computed(() => props.appointments.data);

// Filtro ativo sincronizado com query params
const activeFilter = ref(props.filters.status || 'all');

// Ao mudar filtro, fazer nova requisição
const changeFilter = (filter: string) => {
    activeFilter.value = filter;
    router.get(
        patientRoutes.historyConsultations.url(),
        {
            status: filter === 'all' ? undefined : filter,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Paginação
const changePage = (page: number) => {
    router.get(
        patientRoutes.historyConsultations.url(),
        {
            status: activeFilter.value === 'all' ? undefined : activeFilter.value,
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};
```

---

### 5. `/patient/dashboard` (Dashboard.vue)

**Função UX:** Dashboard do paciente com próximas consultas e resumo.

**Integrações Necessárias:**

#### 5.1. Buscar Próximas Consultas

**Back-End:**

```php
// app/Http/Controllers/Patient/PatientDashboardController.php
public function dashboard(): Response
{
    $patient = auth()->user()->patient;

    $upcomingAppointments = Appointments::query()
        ->with(['doctor.user', 'doctor.specializations'])
        ->where('patient_id', $patient->id)
        ->upcoming()
        ->orderBy('scheduled_at', 'asc')
        ->limit(5)
        ->get();

    $recentAppointments = Appointments::query()
        ->with(['doctor.user', 'doctor.specializations'])
        ->where('patient_id', $patient->id)
        ->completed()
        ->orderBy('scheduled_at', 'desc')
        ->limit(5)
        ->get();

    return Inertia::render('Patient/Dashboard', [
        'upcomingAppointments' => $upcomingAppointments,
        'recentAppointments' => $recentAppointments,
    ]);
}
```

**Front-End:**

```typescript
// resources/js/pages/Patient/Dashboard.vue

interface Props {
    upcomingAppointments: Array<Appointment>;
    recentAppointments: Array<Appointment>;
}

const props = defineProps<Props>();

// Remover mockUpcomingAppointments e usar props.upcomingAppointments
```

---

### 6. `/patient/video-call` (VideoCall.vue)

**Função UX:** Página de videoconferência durante a consulta.

**Integrações Necessárias:**

#### 6.1. Iniciar Consulta Antes da Chamada

```typescript
// resources/js/pages/Patient/VideoCall.vue

import { onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import * as appointmentsRoutes from '@/routes/appointments';

interface Props {
    appointment: {
        id: string;
        status: string;
        scheduled_at: string;
        doctor: { user: { name: string } };
    };
}

const props = defineProps<Props>();

onMounted(async () => {
    // Se status ainda não é 'in_progress', iniciar
    if (props.appointment.status === 'scheduled' || props.appointment.status === 'rescheduled') {
        try {
            await axios.post(appointmentsRoutes.start.url({ appointment: props.appointment.id }));
            // WebSocket notificará mudança de status
        } catch (error) {
            console.error('Erro ao iniciar consulta:', error);
        }
    }

    // Inicializar vídeo chamada (SFU/MediaSoup)
    initializeVideoCall();
});
```

#### 6.2. Finalizar Consulta

```typescript
const endCall = async () => {
    try {
        await axios.post(appointmentsRoutes.end.url({ appointment: props.appointment.id }));
        // Redirecionar para detalhes
        router.visit(patientRoutes.consultationDetails.url({ id: props.appointment.id }));
    } catch (error) {
        console.error('Erro ao finalizar consulta:', error);
    }
};
```

---

## 🔄 Sincronização em Tempo Real (WebSocket/Reverb)

### Eventos a Implementar

#### 1. Status de Appointment Mudou

**Back-End:**

```php
// app/Events/AppointmentStatusChanged.php
class AppointmentStatusChanged implements ShouldBroadcast
{
    public function __construct(
        public Appointments $appointment
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("appointment.{$this->appointment->doctor_id}"),
            new PrivateChannel("appointment.{$this->appointment->patient_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }
}

// Disparar no Observer ou Service
// app/Observers/AppointmentsObserver.php
public function updated(Appointments $appointment): void
{
    if ($appointment->wasChanged('status')) {
        broadcast(new AppointmentStatusChanged($appointment));
    }
}
```

**Front-End:**

```typescript
// resources/js/pages/Patient/ConsultationDetails.vue ou VideoCall.vue

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

onMounted(() => {
    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    });

    // Escutar mudanças de status
    echo.private(`appointment.${props.appointment.patient_id}`).listen('.status.changed', (event: any) => {
        // Atualizar status local
        appointment.value.status = event.appointment.status;

        // Se mudou para 'in_progress', iniciar vídeo se necessário
        if (event.appointment.status === 'in_progress') {
            // Lógica de vídeo
        }

        // Se mudou para 'completed', redirecionar
        if (event.appointment.status === 'completed') {
            router.visit(patientRoutes.consultationDetails.url({ id: event.appointment.id }));
        }
    });

    onUnmounted(() => {
        echo.leave(`appointment.${props.appointment.patient_id}`);
    });
});
```

#### 2. Novo Appointment Criado

**Back-End:**

```php
// app/Events/AppointmentCreated.php
class AppointmentCreated implements ShouldBroadcast
{
    public function __construct(
        public Appointments $appointment
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->appointment->doctor->user_id}"),
            new PrivateChannel("user.{$this->appointment->patient->user_id}"),
        ];
    }
}
```

**Front-End:**

```typescript
// resources/js/pages/Patient/Dashboard.vue

// Escutar novos appointments para atualizar lista
echo.private(`user.${auth.user.id}`).listen('.appointment.created', (event: any) => {
    // Adicionar à lista de upcomingAppointments
    upcomingAppointments.value.push(event.appointment);
});
```

#### 3. Disponibilidade de Horários Atualizada

**Back-End:**

```php
// Quando um appointment é criado/cancelado, notificar outros usuários
// que estão visualizando a disponibilidade do mesmo médico
```

**Front-End:**

```typescript
// resources/js/pages/Patient/ScheduleConsultation.vue

// Escutar mudanças na disponibilidade
echo.private(`doctor.${props.doctor.id}.availability`).listen('.slots.updated', (event: any) => {
    // Atualizar lista de horários disponíveis
    availableTimes.value = event.available_slots;
});
```

---

## 🎨 Componentes Dinâmicos Necessários

### 1. `AppointmentStatusBadge.vue`

```vue
<script setup lang="ts">
interface Props {
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'rescheduled' | 'no_show';
}

const props = defineProps<Props>();

const statusConfig = {
    scheduled: { label: 'Agendada', class: 'bg-yellow-100 text-yellow-700' },
    in_progress: { label: 'Em Andamento', class: 'bg-blue-100 text-blue-700' },
    completed: { label: 'Concluída', class: 'bg-green-100 text-green-700' },
    cancelled: { label: 'Cancelada', class: 'bg-red-100 text-red-700' },
    rescheduled: { label: 'Reagendada', class: 'bg-purple-100 text-purple-700' },
    no_show: { label: 'Não Compareceu', class: 'bg-gray-100 text-gray-700' },
};

const config = computed(() => statusConfig[props.status] || statusConfig.scheduled);
</script>

<template>
    <span :class="['rounded-full px-3 py-1 text-xs font-medium', config.class]">
        {{ config.label }}
    </span>
</template>
```

### 2. `AppointmentActions.vue`

```vue
<script setup lang="ts">
interface Props {
    appointment: {
        id: string;
        status: string;
        scheduled_at: string;
    };
}

const props = defineProps<Props>();

const canStart = computed(() => {
    return ['scheduled', 'rescheduled'].includes(props.appointment.status) && canBeStarted(props.appointment);
});

const canCancel = computed(() => {
    return ['scheduled', 'rescheduled'].includes(props.appointment.status) && canBeCancelled(props.appointment);
});

// Funções de ação...
</script>

<template>
    <div class="flex gap-2">
        <Button v-if="canStart" @click="startAppointment"> Iniciar Consulta </Button>
        <Button v-if="canCancel" @click="cancelAppointment" variant="outline"> Cancelar </Button>
        <Button v-if="canCancel" @click="rescheduleAppointment" variant="outline"> Reagendar </Button>
    </div>
</template>
```

### 3. `DoctorCard.vue`

```vue
<script setup lang="ts">
interface Props {
    doctor: {
        id: string;
        user: { name: string; avatar?: string };
        specializations: Array<{ name: string }>;
        rating?: number;
        reviews_count?: number;
        consultation_fee?: number;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <!-- Card reutilizável para exibir médico -->
</template>
```

### 4. `ScheduleSelector.vue`

```vue
<script setup lang="ts">
interface Props {
    availableDates: Array<{
        date: string;
        available_slots: string[];
    }>;
    selectedDate?: string;
    selectedTime?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:selectedDate': [value: string];
    'update:selectedTime': [value: string];
}>();

// Lógica de seleção de data/horário
</script>

<template>
    <!-- Componente de seleção de data/horário -->
</template>
```

---

## 📊 Mapeamento de Status no Front-End

| Status Back-End | Exibição Front-End | Badge    | Ações Disponíveis                                |
| --------------- | ------------------ | -------- | ------------------------------------------------ |
| `scheduled`     | "Agendada"         | Amarelo  | Cancelar, Reagendar, Iniciar (quando disponível) |
| `in_progress`   | "Em Andamento"     | Azul     | Finalizar                                        |
| `completed`     | "Concluída"        | Verde    | Ver Detalhes, Avaliar                            |
| `cancelled`     | "Cancelada"        | Vermelho | Ver Detalhes                                     |
| `rescheduled`   | "Reagendada"       | Roxo     | Cancelar, Reagendar Novamente, Iniciar           |
| `no_show`       | "Não Compareceu"   | Cinza    | Ver Detalhes                                     |

---

## 🚀 Checklist de Implementação

### Fase 1: Endpoints Back-End

- [ ] Criar `PatientSearchConsultationsController::searchConsultations()`
- [ ] Criar endpoint `GET /api/appointments/availability`
- [ ] Atualizar `ScheduleConsultationController` para receber `doctor_id`
- [ ] Atualizar `PatientConsultationDetailsController` para buscar appointment
- [ ] Atualizar `PatientHistoryConsultationsController` para listar com filtros
- [ ] Atualizar `PatientDashboardController` para buscar próximas consultas

### Fase 2: Integração Front-End - Páginas

- [ ] `SearchConsultations.vue`: Remover mocks, integrar com back-end
- [ ] `ScheduleConsultation.vue`: Receber doctor via props, criar appointment
- [ ] `ConsultationDetails.vue`: Buscar appointment real, exibir status dinâmico
- [ ] `HistoryConsultations.vue`: Listar appointments reais, filtros funcionais
- [ ] `Dashboard.vue`: Exibir próximas consultas reais
- [ ] `VideoCall.vue`: Iniciar/finalizar appointment

### Fase 3: Componentes Reutilizáveis

- [ ] Criar `AppointmentStatusBadge.vue`
- [ ] Criar `AppointmentActions.vue`
- [ ] Criar `DoctorCard.vue`
- [ ] Criar `ScheduleSelector.vue`
- [ ] Criar `AppointmentSummary.vue`

### Fase 4: WebSocket/Real-Time

- [ ] Implementar eventos de broadcast no back-end
- [ ] Configurar canais privados no `routes/channels.php`
- [ ] Integrar Echo no front-end
- [ ] Escutar mudanças de status em tempo real
- [ ] Atualizar disponibilidade de horários em tempo real

### Fase 5: Validações e Tratamento de Erros

- [ ] Validar disponibilidade antes de criar appointment
- [ ] Tratar erros de conflito de horário
- [ ] Exibir mensagens de erro amigáveis
- [ ] Validar janelas de tempo (lead, cancel_before_hours)

### Fase 6: Testes e Refinamentos

- [ ] Testar fluxo completo de agendamento
- [ ] Testar cancelamento e reagendamento
- [ ] Testar sincronização em tempo real
- [ ] Testar validações de negócio
- [ ] Ajustar UX baseado em feedback

---

## 🔗 Referências

- **[Arquitetura de Appointments](AppointmentsArchitecture.md)** - Estrutura do back-end
- **[Lógica de Appointments](AppointmentsLogica.md)** - Regras de negócio
- **[AppointmentsController](../../../app/Http/Controllers/AppointmentsController.php)** - Endpoints disponíveis
- **[AppointmentService](../../../app/Services/AppointmentService.php)** - Lógica de negócio
- **[AppointmentPolicy](../../../app/Policies/AppointmentPolicy.php)** - Regras de autorização

---

_Última atualização: Novembro 2025_
