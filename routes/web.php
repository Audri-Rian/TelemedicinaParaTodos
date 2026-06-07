<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Rotas organizadas por domínio em arquivos separados.
|
*/

require __DIR__.'/web/public.php';
require __DIR__.'/web/doctor.php';
require __DIR__.'/web/patient.php';
require __DIR__.'/web/lgpd.php';
require __DIR__.'/web/shared.php';
require __DIR__.'/web/dev.php';

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
