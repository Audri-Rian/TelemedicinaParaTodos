<?php

use App\Integrations\Http\Controllers\LabOrderController;
use App\Integrations\Http\Controllers\PartnerHealthController;
use App\Integrations\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Pública — Parceiros Externos
|--------------------------------------------------------------------------
|
| Rotas acessíveis por laboratórios, farmácias, hospitais e convênios.
| Prefixo: /api/v1/public
|
| Documentação: execute/MVP1.md, execute/SegurancaAPIPublica.md
|
*/

Route::prefix('v1/public')->group(function () {

    /*
    |----------------------------------------------------------------------
    | Webhooks (inbound — parceiros enviam dados para nós)
    |----------------------------------------------------------------------
    */
    Route::prefix('webhooks')->group(function () {
        Route::post('/lab/{partnerSlug}', [WebhookController::class, 'labResult'])
            ->name('api.webhooks.lab');
    });

    /*
    |----------------------------------------------------------------------
    | Lab Orders (laboratórios consultam pedidos pendentes)
    |----------------------------------------------------------------------
    */
    Route::get('/lab/{partnerSlug}/orders', [LabOrderController::class, 'index'])
        ->name('api.lab.orders');

    /*
    |----------------------------------------------------------------------
    | Health Check (parceiros verificam status da integração)
    |----------------------------------------------------------------------
    */
    Route::get('/health/{partnerSlug}', [PartnerHealthController::class, 'check'])
        ->name('api.health.check');
});
