<?php

/*
|--------------------------------------------------------------------------
| Rotas Web
|--------------------------------------------------------------------------
|
| Este arquivo é o ponto de entrada principal para as rotas web.
| As rotas estão organizadas em arquivos separados por responsabilidade.
|
*/

// Rotas públicas (home, terms, privacy, storage, dev)
require __DIR__.'/public.php';

// Rotas de autenticação (login, register, password reset, etc)
require __DIR__.'/auth.php';

// Rotas de configurações (profile, password, bug-report, avatar)
require __DIR__.'/settings.php';

// Rotas do médico
require __DIR__.'/doctor.php';

// Rotas do paciente
require __DIR__.'/patient.php';

// Rotas de compliance LGPD
require __DIR__.'/lgpd.php';

// Rotas compartilhadas (appointments, specializations)
require __DIR__.'/shared.php';

// Rotas de API (messages, timeline, notifications)
require __DIR__.'/api/api.php';
