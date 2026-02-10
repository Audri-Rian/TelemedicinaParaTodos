# Migração: Remover Regras de Negócio e Usar config('telemedicine.*')

Este documento lista todos os arquivos onde foram identificadas regras de negócio que foram centralizadas em `config/telemedicine.php`. Serve como guia para substituir os valores hardcoded por `config()` quando for executar a refatoração.

**Referência:** [TASK_11_GOVERNANCA_BACKEND.md](./TASK_11_GOVERNANCA_BACKEND.md) — T 11.1

> **Status atual (fev/2025):**  
> - O arquivo `config/telemedicine.php` foi **completado** com seções para `appointment`, `doctor_defaults`, `availability`, `video_call`, `reminders`, `maintenance`, `medical_records`, `validation`, `notifications`, `auth`, `dashboard`, `messages`, `uploads`, `pagination`, `display`, `consultation_detail`, `patient_history` e `lgpd`.  
> - Todos os pontos mapeados abaixo já foram migrados para `config('telemedicine.*')`, incluindo ajustes adicionais de revisão (CodeRabbit): novos keys específicos (`availability.timeline_window_days`, `consultation_detail.recent_history_limit`, `patient_history.recent_consultations_days`, `lgpd.report_window_days`, `notifications.max_per_page`, `dashboard.recent_appointments_limit`), mensagens de erro dinâmicas e validações extraídas para FormRequests.

---

## Índice por Arquivo

| Arquivo | Regras | Status |
|---------|--------|--------|
| [AppointmentService](#appointmentservice) | 4 | Parcial (já usa config em alguns pontos) |
| [PatientVideoCallController](#patientvideocallcontroller) | 1 | Pendente |
| [DoctorConsultationsController](#doctorconsultationscontroller) | 1 | Parcial (já usa config) |
| [VideoCallController](#videocallcontroller) | 1 | Parcial (já usa config) |
| [AppointmentPolicy](#appointmentpolicy) | 2 | Parcial (já usa config) |
| [AvailabilityService](#availabilityservice) | 1 | Pendente |
| [ScheduleService](#scheduleservice) | 1 | Parcial (usa doctor_defaults) |
| [SendAppointmentReminders](#sendappointmentreminders) | 1 | Pendente |
| [routes/console.php](#routesconsolephp) | 1 | Pendente |
| [AppointmentsObserver](#appointmentsobserver) | 1 | Pendente |
| [MedicalRecordService](#medicalrecordservice) | 4 | Pendente |
| [StoreMedicalCertificateRequest](#storemedicalcertificaterequest) | 1 | Pendente |
| [AvailabilityTimelineService](#availabilitytimelineservice) | 2 | Pendente |
| [Patient (Model)](#patient-model) | 1 | Pendente |
| [Appointments (Model)](#appointments-model) | 1 | Pendente |
| [TimelineEvent (Model)](#timelineevent-model) | 2 | Pendente |
| [StoreScheduleConfigRequest](#storescheduleconfigrequest) | 1 | Pendente |
| [StoreAvailabilitySlotRequest](#storeavailabilityslotrequest) | 1 | Pendente |
| [DoctorAvailabilitySlotController](#doctoravailabilityslotcontroller) | 1 | Pendente |
| [LoginRequest](#loginrequest) | 1 | Pendente |
| [NotificationService](#notificationservice) | 2 | Pendente |
| [NotificationController](#notificationcontroller) | 2 | Pendente |
| [MessageService](#messageservice) | 1 | Pendente |
| [StoreMessageRequest](#storemessagerequest) | 1 | Pendente |
| [AvatarUploadRequest](#avataruploadrequest) | 1 | Pendente |
| [MedicalRecordDocumentController](#medicalrecorddocumentcontroller) | 1 | Pendente |
| [DoctorDashboardController](#doctordashboardcontroller) | 3 | Pendente |
| [PatientDashboardController](#patientdashboardcontroller) | 2 | Pendente |
| [PatientSearchConsultationsController](#patientsearchconsultationscontroller) | 2 | Pendente |
| [ScheduleConsultationController](#scheduleconsultationcontroller) | 1 | Pendente |
| [DoctorPerfilController](#doctorperfilcontroller) | 1 | Pendente |
| [DataAccessReportController](#dataaccessreportcontroller) | 1 | Pendente |
| [PatientHistoryConsultationsController](#patienthistoryconsultationscontroller) | 1 | Pendente |
| [DoctorConsultationDetailController](#doctorconsultationdetailcontroller) | 1 | Pendente |
| [SpecializationController](#specializationcontroller) | 1 | Pendente |

---

## Detalhamento por Arquivo

---

### AppointmentService

**Arquivo:** `app/Services/AppointmentService.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 37 | `10` (fallback) | `config('telemedicine.appointment.lead_minutes', 10)` | Já usa config; remover fallback se confiar no config |
| 49 | `2` (fallback) | `config('telemedicine.appointment.cancel_before_hours', 2)` | Idem |
| 151, 193, 225, 320 | `30` | `config('telemedicine.appointment.duration_minutes', 30)` | Já usa config; manter fallback ou remover |

**Status:** Já utiliza config na maioria dos pontos. Opcional: padronizar fallbacks.

---

### PatientVideoCallController

**Arquivo:** `app/Http/Controllers/Patient/PatientVideoCallController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 72-73, 109, 128 | `-10`, `10` (janela minutos) | `config('telemedicine.appointment.lead_minutes')` e `config('telemedicine.appointment.trailing_minutes')` | Janela simétrica para entrar na videoconferência. Hoje hardcoded; RN009 menciona 10 min antes/depois |

**Substituição sugerida:**
```php
$leadMinutes = config('telemedicine.appointment.lead_minutes', 10);
$trailingMinutes = config('telemedicine.appointment.trailing_minutes', 10);
return $minutesDifference >= -$trailingMinutes && $minutesDifference <= $leadMinutes;
```

---

### DoctorConsultationsController

**Arquivo:** `app/Http/Controllers/Doctor/DoctorConsultationsController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 55 | `config(..., 10)` | `config('telemedicine.appointment.lead_minutes', 10)` | Já usa config; apenas garantir que o fallback esteja alinhado |

**Status:** Já correto. Nenhuma alteração necessária.

---

### VideoCallController

**Arquivo:** `app/Http/Controllers/VideoCall/VideoCallController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 240 | `config(..., 10)` | `config('telemedicine.appointment.lead_minutes', 10)` | Já usa config |

**Status:** Já correto.

---

### AppointmentPolicy

**Arquivo:** `app/Policies/AppointmentPolicy.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 130 | `config(..., 10)` | `config('telemedicine.appointment.lead_minutes', 10)` | Já usa config |
| 169, 194 | `config(..., 2)` | `config('telemedicine.appointment.cancel_before_hours', 2)` | Já usa config |

**Status:** Já correto.

---

### AvailabilityService

**Arquivo:** `app/Services/AvailabilityService.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 228 | `$slotDuration = 45` | `config('telemedicine.availability.slot_duration_minutes', 45)` | Geração de slots de horário; regra de agenda |

**Substituição sugerida:**
```php
$slotDuration = (int) config('telemedicine.availability.slot_duration_minutes', 45);
```

---

### ScheduleService

**Arquivo:** `app/Services/Doctor/ScheduleService.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 296 | `$defaults['slot_duration_minutes'] ?? 45` | `config('telemedicine.doctor_defaults.slot_duration_minutes', 45)` | Já usa doctor_defaults; garantir que venha do config |

**Status:** Já usa `config('telemedicine.doctor_defaults')`. Verificar se o array está completo.

---

### SendAppointmentReminders

**Arquivo:** `app/Jobs/SendAppointmentReminders.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 31-42 | `24`, `1` (horas) | `config('telemedicine.reminders.send_before_hours')` | Horas antes da consulta para enviar lembrete (24h e 1h) |

**Substituição sugerida:**
```php
$sendBeforeHours = config('telemedicine.reminders.send_before_hours', [24, 1]);
foreach ($sendBeforeHours as $hours) {
    $reminderTime = $now->copy()->addHours($hours);
    // ... buscar appointments e enviar
}
```

---

### routes/console.php

**Arquivo:** `routes/console.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 12-14 | `->hourly()` | `->cron(config('telemedicine.reminders.schedule_cron', '0 * * * *'))` | Frequência de execução do job de lembretes |

**Substituição sugerida:**
```php
Schedule::call(function () {
    \App\Jobs\SendAppointmentReminders::dispatch();
})->cron(config('telemedicine.reminders.schedule_cron', '0 * * * *'));
```

---

### AppointmentsObserver

**Arquivo:** `app/Observers/AppointmentsObserver.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 132 | `substr(md5(uniqid()), 0, 8)` | `config('telemedicine.appointment.access_code_length', 8)` | Tamanho do access_code gerado automaticamente |

**Substituição sugerida:**
```php
$length = (int) config('telemedicine.appointment.access_code_length', 8);
$code = strtoupper(substr(md5(uniqid()), 0, $length));
```

---

### MedicalRecordService

**Arquivo:** `app/Services/MedicalRecordService.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 424 | `->take(10)` | `->take(config('telemedicine.medical_records.search_limit', 10))` | Limite de busca em prontuário |
| 306 | `$filters['vitals_limit'] ?? 50` | `config('telemedicine.medical_records.vitals_limit', 50)` | Limite de sinais vitais retornados |
| 790 | `now()->addDays(30)` | `now()->addDays(config('telemedicine.medical_records.prescription_default_validity_days', 30))` | Validade padrão de prescrição |
| 1104-1105 | `Str::random(10)` | `Str::random(config('telemedicine.medical_records.verification_code_length', 10))` | Tamanho do código de verificação de atestado |

---

### StoreMedicalCertificateRequest

**Arquivo:** `app/Http/Requests/Doctor/MedicalRecords/StoreMedicalCertificateRequest.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 22 | `'max:60'` | `'max:' . config('telemedicine.medical_records.certificate_max_days', 60)` | Dias máximos permitidos em atestado |

---

### AvailabilityTimelineService

**Arquivo:** `app/Services/Doctor/AvailabilityTimelineService.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 19-21 | `subDays($windowDays)`, `addDays($windowDays)` | `config('telemedicine.availability.timeline_window_days', 30)` | Janela de dias para timeline de disponibilidade específica do médico |
| 202 | `addDays(7)` | `addDays(config('telemedicine.dashboard.next_week_days', 7))` | "Próximos 7 dias" |
| 223 | `->limit(4)` | `->limit(config('telemedicine.dashboard.last_sessions_limit', 4))` | Últimas sessões na timeline |

---

### Patient (Model)

**Arquivo:** `app/Models/Patient.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 136 | `?int $days = null` | `config('telemedicine.patient_history.recent_consultations_days', 30)` | Scope "recentemente consultado" usa janela padrão configurável para histórico do paciente |

---

### Appointments (Model)

**Arquivo:** `app/Models/Appointments.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 182-183 | `return '45min'` (fallback) | `config('telemedicine.display.appointment_duration_fallback_minutes', 45)` | Duração exibida quando não há started_at/ended_at |

---

### TimelineEvent (Model)

**Arquivo:** `app/Models/TimelineEvent.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 112 | `$diffInDays < 30` | `config('telemedicine.display.timeline_days_threshold', 30)` | Threshold para exibir "X dias" vs "X meses" |
| 118 | `$diffInMonths < 12` | `config('telemedicine.display.timeline_months_threshold', 12)` | Threshold para exibir "X meses" vs "X anos" |

---

### StoreScheduleConfigRequest

**Arquivo:** `app/Http/Requests/Doctor/StoreScheduleConfigRequest.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 52, 81 | `$diffInMinutes < 60` | `config('telemedicine.availability.slot_min_duration_minutes', 60)` | Duração mínima de slot (1 hora) |

---

### StoreAvailabilitySlotRequest

**Arquivo:** `app/Http/Requests/Doctor/StoreAvailabilitySlotRequest.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 51 | `$diffInMinutes < 60` | `config('telemedicine.availability.slot_min_duration_minutes', 60)` | Idem |

---

### DoctorAvailabilitySlotController

**Arquivo:** `app/Http/Controllers/Doctor/DoctorAvailabilitySlotController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 124 | `$diffInMinutes < 60` | `config('telemedicine.availability.slot_min_duration_minutes', 60)` | Idem |

---

### LoginRequest

**Arquivo:** `app/Http/Requests/Auth/LoginRequest.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 60 | `5` (RateLimiter::tooManyAttempts) | `config('telemedicine.auth.login_max_attempts', 5)` | NF005: bloqueio após 5 tentativas |

---

### NotificationService

**Arquivo:** `app/Services/NotificationService.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 90 | `$ttl = 10` | `config('telemedicine.notifications.debounce_ttl_seconds', 10)` | TTL do debounce de notificações |
| 210 | `int $limit = 10` | `config('telemedicine.notifications.list_limit', 10)` | Limite de notificações não lidas |

---

### NotificationController

**Arquivo:** `app/Http/Controllers/NotificationController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 33 | `$perPage = $request->get('per_page', 15)` | `config('telemedicine.notifications.per_page', 15)` + `config('telemedicine.notifications.max_per_page', 100)` | Paginação de notificações (default + limite máximo para proteção de performance) |
| 104 | `->limit(10)` | `config('telemedicine.notifications.list_limit', 10)` | Limite em listagem rápida (dropdown, contagem de não lidas) |

---

### MessageService

**Arquivo:** `app/Services/MessageService.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 49 | `?int $limit = 50` | `config('telemedicine.messages.default_page_limit', 50)` | Mensagens por página na conversa |

---

### StoreMessageRequest

**Arquivo:** `app/Http/Requests/StoreMessageRequest.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 34 | `'max:5000'` | `'max:' . config('telemedicine.messages.max_content_length', 5000)` | Máximo de caracteres por mensagem |

---

### AvatarUploadRequest

**Arquivo:** `app/Http/Requests/AvatarUploadRequest.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 29 | `'max:5120'` (5MB) | `'max:' . config('telemedicine.uploads.avatar_max_kb', 5120)` | Tamanho máximo do avatar |

---

### MedicalRecordDocumentController

**Arquivo:** `app/Http/Controllers/MedicalRecordDocumentController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 40 | `'max:10240'` (10MB) | `'max:' . config('telemedicine.uploads.medical_document_max_kb', 10240)` | Tamanho máximo de documento médico |

---

### DoctorDashboardController

**Arquivo:** `app/Http/Controllers/Doctor/DoctorDashboardController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 31 | `->limit(3)` | `config('telemedicine.dashboard.next_appointments_limit', 3)` | Próximas consultas no card |
| 144 | `'max' => 10` | `config('telemedicine.dashboard.next_appointments_limit', 3)` ou valor específico para gráfico | Normalização de gráficos (avaliar se faz sentido configurar) |
| 168 | `'max' => 30` | Idem | Idem |

---

### PatientDashboardController

**Arquivo:** `app/Http/Controllers/Patient/PatientDashboardController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 32-33 | `->limit($nextAppointmentsLimit)` | `config('telemedicine.dashboard.next_appointments_limit', 3)` | Próximas consultas no card do paciente |
| 52 | `'duration' => $appointment->formatted_duration` | `config('telemedicine.display.appointment_duration_fallback_minutes', 45)` é usado internamente em `Appointments::formatted_duration` como fallback de duração | Duração exibida usa accessor do model em vez de string fixa |
| 62-63 | `->limit(...)` | `config('telemedicine.dashboard.recent_appointments_limit', 5)` | Limite de cards de histórico de consultas recentes no dashboard |
| 81-85 | `->limit($patientNextLimit)` | `config('telemedicine.dashboard.patient_next_consultations_limit', 10)` | Próximos médicos/consultas sugeridos ao paciente |

---

### PatientSearchConsultationsController

**Arquivo:** `app/Http/Controllers/Patient/PatientSearchConsultationsController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 56 | `->paginate(6)` | `config('telemedicine.pagination.doctors_search_per_page', 6)` | Paginação de busca de médicos |
| 110 | `->limit(10)` | `config('telemedicine.dashboard.patient_next_consultations_limit', 10)` | Limite de consultas |

---

### ScheduleConsultationController

**Arquivo:** `app/Http/Controllers/Patient/ScheduleConsultationController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 88 | `addDays($windowDays)` | `config('telemedicine.availability.timeline_window_days', 30)` | Janela de datas disponíveis para agendamento do paciente |

---

### DoctorPerfilController

**Arquivo:** `app/Http/Controllers/Patient/DoctorPerfilController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 139 | `addDays($windowDays)` | `config('telemedicine.availability.timeline_window_days', 30)` | Janela de datas disponíveis para agendamento no perfil do médico |

---

### DataAccessReportController

**Arquivo:** `app/Http/Controllers/LGPD/DataAccessReportController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 26, 50 | `$reportDays = (int) config(..., 30)` | `config('telemedicine.lgpd.report_window_days', 30)` | Janela padrão do relatório de acesso (LGPD), configurável independentemente de outras janelas de timeline |

---

### PatientHistoryConsultationsController

**Arquivo:** `app/Http/Controllers/Patient/PatientHistoryConsultationsController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 45 | `->paginate(10)` | `config('telemedicine.pagination.consultations_per_page', 10)` | Paginação de histórico |

---

### DoctorConsultationDetailController

**Arquivo:** `app/Http/Controllers/Doctor/DoctorConsultationDetailController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 72-78 | `->limit($recentLimit)` | `config('telemedicine.consultation_detail.recent_history_limit', 3)` | Limite de consultas recentes exibidas na tela de detalhe da consulta do médico |

---

### SpecializationController

**Arquivo:** `app/Http/Controllers/SpecializationController.php`

| Linha (aprox.) | Valor Atual | Config a Usar | Motivo |
|----------------|-------------|---------------|--------|
| 93, 151 | `'max:100'` | `'max:' . config('telemedicine.validation.specialization_name_max_length', 100)` | RN019: nome da especialização até 100 caracteres |

---

## Arquivos que Já Usam config (Nenhuma Alteração Necessária)

- `app/Services/AppointmentService.php` — usa `config('telemedicine.appointment.*')`
- `app/Http/Controllers/Doctor/DoctorConsultationsController.php`
- `app/Http/Controllers/VideoCall/VideoCallController.php`
- `app/Policies/AppointmentPolicy.php`

---

## Ordem Sugerida de Refatoração

1. **Alta prioridade (regras críticas de telemedicina):**
   - PatientVideoCallController (janela de videoconferência)
   - AvailabilityService (slot_duration)
   - StoreScheduleConfigRequest, StoreAvailabilitySlotRequest, DoctorAvailabilitySlotController (slot_min_duration)
   - SendAppointmentReminders
   - routes/console.php

2. **Média prioridade (prontuário e manutenção):**
   - MedicalRecordService
   - StoreMedicalCertificateRequest
   - AppointmentsObserver

3. **Baixa prioridade (UX e limites):**
   - Dashboard Controllers
   - Pagination e limits
   - Models (Patient, Appointments, TimelineEvent)
   - NotificationService, MessageService
   - FormRequests (uploads, validation)

---

## Validação Pós-Refatoração

Após substituir os hardcodes:

1. Executar `php artisan config:clear` e `php artisan config:cache`
2. Rodar testes: `php artisan test`
3. Verificar fluxos críticos: agendamento, videoconferência, prontuário
4. Confirmar que `.env.example` documenta as variáveis novas (quando aplicável)
