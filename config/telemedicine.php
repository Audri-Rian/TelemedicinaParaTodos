<?php

/**
 * Configuração Centralizada de Telemedicina
 *
 * Este arquivo concentra todas as regras de negócio configuráveis do sistema,
 * evitando "números mágicos" espalhados no código.
 *
 * Documentação de origem:
 * - docs/requirements/SystemRules.md
 * - docs/requirements/FuncionalitsGuide.md (RN001-RN024)
 * - docs/modules/appointments/AppointmentsLogica.md
 * - docs/modules/videocall/VideoCallTasks.md
 * - docs/Pending Issues/CONFORMIDADE_CFM_LGPD.md
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Appointment Configuration
    |--------------------------------------------------------------------------
    |
    | Parâmetros do módulo de agendamentos (appointments). Regras RN004, RN005,
    | RN006, RN009, RN022. Usado em: AppointmentService, DoctorConsultationsController,
    | PatientVideoCallController, VideoCallController, AppointmentPolicy.
    |
    */

    'appointment' => [
        // Minutos ANTES do horário agendado em que a consulta pode ser iniciada.
        // RN009: "10 minutos antes do horário agendado". DoctorConsultationsController usa
        // para janela de início. AppointmentsLogica.md menciona 15 min — preferir config.
        'lead_minutes' => env('APPOINTMENT_LEAD_MINUTES', 10),

        // Minutos DEPOIS do horário agendado em que ainda é permitido entrar/iniciar.
        // PatientVideoCallController usa -10/10 hardcoded. Janela simétrica: antes + depois.
        'trailing_minutes' => env('APPOINTMENT_TRAILING_MINUTES', 10),

        // Duração padrão de uma consulta em minutos. RN004: conflitos validam com esta duração.
        // Usado em: AppointmentService (create, update, reschedule, validateNoConflict),
        // AvailabilityService para slots, Appointments::formatted_duration (fallback).
        'duration_minutes' => env('APPOINTMENT_DURATION_MINUTES', 30),

        // Tolerância em minutos APÓS o horário agendado antes de marcar como no_show.
        // AppointmentsLogica: "job automático após certo tempo de tolerância". T 11.5 task.
        'grace_minutes' => env('APPOINTMENT_GRACE_MINUTES', 15),

        // Horas ANTES do horário agendado em que o cancelamento ainda é permitido.
        // RN005, RN006: "2 horas antes". Usado em AppointmentService::canBeCancelled.
        'cancel_before_hours' => env('APPOINTMENT_CANCEL_BEFORE_HOURS', 2),

        // Status permitidos para transições. RN007. Referência: AppointmentService::validateStatusTransition.
        'statuses' => [
            'scheduled' => 'scheduled',
            'in_progress' => 'in_progress',
            'completed' => 'completed',
            'no_show' => 'no_show',
            'cancelled' => 'cancelled',
            'rescheduled' => 'rescheduled',
        ],

        // Tamanho do access_code gerado automaticamente. AppointmentsObserver::generateUniqueAccessCode.
        'access_code_length' => (int) env('APPOINTMENT_ACCESS_CODE_LENGTH', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Doctor Availability
    |--------------------------------------------------------------------------
    |
    | Configuração para criar disponibilidade INICIAL de médicos recém-cadastrados.
    | RN015: "Sistema cria disponibilidade padrão se médico não configurar".
    | Usado em: ScheduleService::ensureDefaultAvailability.
    |
    */

    'doctor_defaults' => [
        // Dias úteis atendidos por padrão. FuncionalitsGuide: work_days.
        'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],

        'work_hours' => [
            'start' => env('DOCTOR_DEFAULT_START_TIME', '08:00'),
            'end' => env('DOCTOR_DEFAULT_END_TIME', '18:00'),
        ],

        // Duração de cada slot em minutos. AvailabilityService e ScheduleService usam 45.
        // AvailabilityService::generateTimeSlotsFromInterval (hardcoded 45).
        'slot_duration_minutes' => env('DOCTOR_DEFAULT_SLOT_MINUTES', 45),

        'lunch_break' => [
            'start' => env('DOCTOR_DEFAULT_LUNCH_START', '12:00'),
            'end' => env('DOCTOR_DEFAULT_LUNCH_END', '14:00'),
        ],

        'include_saturday' => env('DOCTOR_DEFAULT_INCLUDE_SATURDAY', false),

        'telehealth_location' => [
            'name' => env('DOCTOR_DEFAULT_TELECONSULTATION_NAME', 'Teleconsulta (Padrão)'),
            'description' => env('DOCTOR_DEFAULT_TELECONSULTATION_DESCRIPTION', 'Atendimento remoto via videoconferência.'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Availability & Schedule
    |--------------------------------------------------------------------------
    |
    | Regras de agenda e disponibilidade. RN015. Usado em: AvailabilityService,
    | ScheduleService, StoreScheduleConfigRequest, StoreAvailabilitySlotRequest,
    | DoctorAvailabilitySlotController.
    |
    */

    'availability' => [
        // Duração padrão de slots ao gerar grade de horários.
        // AvailabilityService::generateTimeSlotsFromInterval (45). Fallback: doctor_defaults.
        'slot_duration_minutes' => env('AVAILABILITY_SLOT_DURATION_MINUTES', 45),

        // Duração MÍNIMA de um slot (início → fim). StoreScheduleConfigRequest, StoreAvailabilitySlotRequest.
        // Regra: "O horário de fim deve ser pelo menos 1 hora após o horário de início."
        'slot_min_duration_minutes' => env('AVAILABILITY_SLOT_MIN_MINUTES', 60),

        // Janela de dias para disponibilidade/agendamento de médicos em telas de agenda.
        // Usado em: AvailabilityTimelineService, ScheduleConsultationController, DoctorPerfilController.
        'timeline_window_days' => (int) env('AVAILABILITY_TIMELINE_WINDOW_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Call (Videoconferência)
    |--------------------------------------------------------------------------
    |
    | Parâmetros de videoconferência. SystemRules: "Salas expiram automaticamente
    | após período configurado". VideoCallTasks.md: lifecycle, timeout.
    | Job ExpireVideoCallRooms (hoje vazio) usará estes valores.
    |
    */

    'video_call' => [
        // Minutos de inatividade para encerrar sala "zumbi". T 11.5: "encerrar chamadas zumbis".
        'room_inactive_minutes' => env('VIDEO_ROOM_INACTIVE_MINUTES', 60),

        // Duração máxima de uma sala ativa (minutos). Evita salas eternas.
        'room_max_duration_minutes' => env('VIDEO_ROOM_MAX_DURATION_MINUTES', 120),

        // Janela para iniciar videoconferência: usa appointment.lead_minutes e trailing_minutes.
        // DoctorConsultationsController, PatientVideoCallController, VideoCallController.
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminders (Lembretes de Consulta)
    |--------------------------------------------------------------------------
    |
    | Configuração do job SendAppointmentReminders. RN008. Usado em: routes/console.php
    | (schedule), SendAppointmentReminders. Valores em horas antes do scheduled_at.
    |
    */

    'reminders' => [
        // Horas antes da consulta para enviar cada lembrete. Ordem: primeiro = mais distante.
        // SendAppointmentReminders: 24h e 1h antes. Customizar via REMINDER_SEND_BEFORE_HOURS=24,1
        'send_before_hours' => env('REMINDER_SEND_BEFORE_HOURS')
            ? array_map('intval', explode(',', env('REMINDER_SEND_BEFORE_HOURS')))
            : [24, 1],

        // Máximo de lembretes por consulta (evitar spam).
        'max_per_appointment' => (int) env('REMINDER_MAX_PER_APPOINTMENT', 2),

        // Cron expression para execução do job SendAppointmentReminders. Padrão: hourly (0 * * * *).
        // routes/console.php: Schedule::call(...)->hourly().
        'schedule_cron' => env('REMINDER_SCHEDULE_CRON', '0 * * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance (Rotinas Operacionais)
    |--------------------------------------------------------------------------
    |
    | Parâmetros das tasks de manutenção (T 11.5). Usado em: jobs de no_show,
    | ExpireVideoCallRooms, limpeza de locks, etc.
    |
    */

    'maintenance' => [
        // Janela de dias para rotinas de manutenção gerais (backoffice, limpeza, etc).
        // Anteriormente usada também para disponibilidade, histórico e relatórios LGPD.
        // Mantida por compatibilidade, mas usos específicos migraram para:
        // - availability.timeline_window_days
        // - patient_history.recent_consultations_days
        // - lgpd.report_window_days
        'timeline_window_days' => env('MAINTENANCE_TIMELINE_DAYS', 30),

        // TTL em segundos para locks Redis (ex.: evitar double-booking). Futuro: T 11.5.
        'lock_ttl_seconds' => env('LOCK_TTL_SECONDS', 300),

        // Idade máxima (minutos) de locks órfãos para limpeza. T 11.5.
        'lock_cleanup_max_age_minutes' => env('LOCK_CLEANUP_MAX_AGE_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Medical Records (Prontuário)
    |--------------------------------------------------------------------------
    |
    | Regras de prontuário. SystemRules: Prescriptions, MedicalCertificates, etc.
    | Usado em: MedicalRecordService, FormRequests de prontuário.
    |
    */

    'medical_records' => [
        // Dias padrão de validade de prescrição quando valid_until não informado.
        // MedicalRecordService::createPrescription: now()->addDays(30).
        'prescription_default_validity_days' => (int) env('PRESCRIPTION_DEFAULT_VALIDITY_DAYS', 30),

        // Dias máximos permitidos em atestado médico. StoreMedicalCertificateRequest: max:60.
        'certificate_max_days' => (int) env('MEDICAL_CERTIFICATE_MAX_DAYS', 60),

        // Limite de resultados em buscas de prontuário (performance).
        // MedicalRecordService::take(10) em algumas queries.
        'search_limit' => (int) env('MEDICAL_RECORD_SEARCH_LIMIT', 10),

        // Limite de sinais vitais retornados (MedicalRecordService: vitals_limit).
        'vitals_limit' => (int) env('MEDICAL_RECORD_VITALS_LIMIT', 50),

        // Tamanho do código de verificação de atestado médico. MedicalRecordService::generateVerificationCode.
        'verification_code_length' => (int) env('MEDICAL_CERTIFICATE_VERIFICATION_CODE_LENGTH', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation (Regras de Validação)
    |--------------------------------------------------------------------------
    |
    | Limites de validação documentados. FuncionalitsGuide: RN017, RN018, RN019.
    |
    */

    'validation' => [
        // CID-10: código máximo 10 caracteres. StoreDiagnosisRequest, DoctorConsultationDetailController.
        'cid10_max_length' => env('VALIDATION_CID10_MAX_LENGTH', 10),

        // RN017: CRM 4-20 caracteres alfanuméricos.
        'crm_min_length' => env('VALIDATION_CRM_MIN_LENGTH', 4),
        'crm_max_length' => env('VALIDATION_CRM_MAX_LENGTH', 20),

        // RN018: 1-5 especializações por médico.
        'specializations_min_per_doctor' => env('VALIDATION_SPECIALIZATIONS_MIN', 1),
        'specializations_max_per_doctor' => env('VALIDATION_SPECIALIZATIONS_MAX', 5),

        // RN019: nome da especialização até 100 caracteres.
        'specialization_name_max_length' => env('VALIDATION_SPECIALIZATION_NAME_MAX', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuração de notificações. NotificationService: debounce, limites.
    |
    */

    'notifications' => [
        // TTL em segundos para debounce de notificações (evitar spam).
        // NotificationService::scheduleDebounce.
        'debounce_ttl_seconds' => env('NOTIFICATION_DEBOUNCE_TTL_SECONDS', 10),

        // Limite padrão de notificações por página.
        'per_page' => env('NOTIFICATION_PER_PAGE', 15),

        // Limite máximo de notificações por página (proteção de performance).
        'max_per_page' => env('NOTIFICATION_MAX_PER_PAGE', 100),

        // Limite em listagens rápidas (ex.: dropdown).
        'list_limit' => env('NOTIFICATION_LIST_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth & Security
    |--------------------------------------------------------------------------
    |
    | Regras de autenticação. NF005: "bloqueio após 5 tentativas".
    | Usado em: LoginRequest::ensureIsNotRateLimited.
    |
    */

    'auth' => [
        // Máximo de tentativas de login antes de bloquear (rate limit).
        'login_max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Limits
    |--------------------------------------------------------------------------
    |
    | Limites para widgets e listagens de dashboard. Usado em: DoctorDashboardController,
    | PatientDashboardController, AvailabilityTimelineService.
    |
    */

    'dashboard' => [
        // Próximas consultas exibidas no card.
        'next_appointments_limit' => (int) env('DASHBOARD_NEXT_APPOINTMENTS_LIMIT', 3),

        // Últimas sessões/consultas na timeline do médico.
        'last_sessions_limit' => (int) env('DASHBOARD_LAST_SESSIONS_LIMIT', 4),

        // Paciente: próximas consultas no card e busca.
        'patient_next_consultations_limit' => (int) env('DASHBOARD_PATIENT_NEXT_LIMIT', 10),

        // Paciente: próximos dias para "próxima semana" na timeline.
        'next_week_days' => (int) env('DASHBOARD_NEXT_WEEK_DAYS', 7),

        // Paciente: quantidade de consultas recentes no histórico do dashboard.
        'recent_appointments_limit' => (int) env('DASHBOARD_RECENT_APPOINTMENTS_LIMIT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages (Chat)
    |--------------------------------------------------------------------------
    |
    | Regras do módulo de mensagens. Usado em: MessageService, StoreMessageRequest.
    |
    */

    'messages' => [
        // Limite padrão de mensagens por página na conversa.
        'default_page_limit' => (int) env('MESSAGES_DEFAULT_PAGE_LIMIT', 50),

        // Máximo de caracteres por mensagem.
        'max_content_length' => (int) env('MESSAGES_MAX_CONTENT_LENGTH', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Uploads
    |--------------------------------------------------------------------------
    |
    | Limites de upload. Usado em: AvatarUploadRequest, MedicalRecordDocumentController.
    |
    */

    'uploads' => [
        // Avatar: tamanho máximo em KB ( padrão 5MB = 5120).
        'avatar_max_kb' => (int) env('UPLOAD_AVATAR_MAX_KB', 5120),

        // Documento médico: tamanho máximo em KB (padrão 10MB = 10240).
        'medical_document_max_kb' => (int) env('UPLOAD_MEDICAL_DOCUMENT_MAX_KB', 10240),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination & Limits
    |--------------------------------------------------------------------------
    |
    | Limites gerais de paginação e listagem.
    |
    */

    'pagination' => [
        'consultations_per_page' => (int) env('PAGINATION_CONSULTATIONS', 10),
        'patients_per_page' => (int) env('PAGINATION_PATIENTS', 10),

        // Busca de médicos (PatientSearchConsultationsController).
        'doctors_search_per_page' => (int) env('PAGINATION_DOCTORS_SEARCH', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI & Display
    |--------------------------------------------------------------------------
    |
    | Valores usados em apresentação (ex.: fallback de duração). Appointments::formatted_duration.
    | PatientDashboardController: 'duration' => '45 min' (placeholder).
    |
    */

    'display' => [
        // Duração padrão exibida quando não há started_at/ended_at.
        'appointment_duration_fallback_minutes' => env('DISPLAY_APPOINTMENT_FALLBACK_MINUTES', 45),

        // TimelineEvent: abaixo de X dias exibe "X dias"; entre dias e meses, "X meses"; acima, "X anos".
        'timeline_days_threshold' => (int) env('DISPLAY_TIMELINE_DAYS_THRESHOLD', 30),
        'timeline_months_threshold' => (int) env('DISPLAY_TIMELINE_MONTHS_THRESHOLD', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Consultation Detail
    |--------------------------------------------------------------------------
    |
    | Configurações específicas da tela de detalhe de consulta do médico.
    |
    */

    'consultation_detail' => [
        // Quantidade de consultas anteriores exibidas no histórico da tela de detalhe.
        // Usado em: DoctorConsultationDetailController (recent_consultations).
        'recent_history_limit' => (int) env('CONSULTATION_DETAIL_RECENT_HISTORY_LIMIT', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Patient History
    |--------------------------------------------------------------------------
    |
    | Configurações para histórico do paciente (últimas consultas, relatórios).
    |
    */

    'patient_history' => [
        // Janela padrão em dias para considerar pacientes "recentemente consultados".
        // Usado em: Patient::scopeRecentlyConsulted.
        'recent_consultations_days' => (int) env('PATIENT_HISTORY_RECENT_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | LGPD & Data Access
    |--------------------------------------------------------------------------
    |
    | Parâmetros relacionados a relatórios de acesso a dados pessoais (LGPD).
    |
    */

    'lgpd' => [
        // Janela padrão em dias para geração de relatórios de acesso.
        // Usado em: DataAccessReportController.
        'report_window_days' => (int) env('LGPD_REPORT_WINDOW_DAYS', 30),
    ],
];
