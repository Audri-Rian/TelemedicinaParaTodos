<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Appointment Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao módulo de agendamentos (appointments)
    |
    */

    'appointment' => [
        // Minutos antes do horário agendado que a consulta pode ser iniciada
        'lead_minutes' => env('APPOINTMENT_LEAD_MINUTES', 10),

        // Duração padrão de uma consulta em minutos
        'duration_minutes' => env('APPOINTMENT_DURATION_MINUTES', 30),

        // Tolerância em minutos após o horário agendado antes de marcar como no_show
        'grace_minutes' => env('APPOINTMENT_GRACE_MINUTES', 15),

        // Horas antes do horário agendado que uma consulta pode ser cancelada
        'cancel_before_hours' => env('APPOINTMENT_CANCEL_BEFORE_HOURS', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Doctor Availability
    |--------------------------------------------------------------------------
    |
    | Configuração utilizada para criar disponibilidade inicial de médicos
    | recém cadastrados. Pode ser ajustada conforme a necessidade do produto.
    |
    */

    'doctor_defaults' => [
        // Dias úteis atendidos por padrão
        'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],

        // Horário padrão (pode ser sobrescrito por dia futuramente)
        'work_hours' => [
            'start' => env('DOCTOR_DEFAULT_START_TIME', '08:00'),
            'end' => env('DOCTOR_DEFAULT_END_TIME', '18:00'),
        ],

        // Duração de cada slot em minutos
        'slot_duration_minutes' => env('DOCTOR_DEFAULT_SLOT_MINUTES', 45),

        // Intervalo de almoço removido da grade
        'lunch_break' => [
            'start' => env('DOCTOR_DEFAULT_LUNCH_START', '12:00'),
            'end' => env('DOCTOR_DEFAULT_LUNCH_END', '14:00'),
        ],

        // Indica se devemos incluir sábado automaticamente
        'include_saturday' => env('DOCTOR_DEFAULT_INCLUDE_SATURDAY', false),

        // Configuração do local de atendimento padrão (teleconsulta)
        'telehealth_location' => [
            'name' => env('DOCTOR_DEFAULT_TELECONSULTATION_NAME', 'Teleconsulta (Padrão)'),
            'description' => env('DOCTOR_DEFAULT_TELECONSULTATION_DESCRIPTION', 'Atendimento remoto via videoconferência.'),
        ],
    ],
];

