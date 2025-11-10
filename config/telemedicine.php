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
];

