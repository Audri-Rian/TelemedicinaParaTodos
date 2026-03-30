<?php

use App\Integrations\Http\Controllers\LabOrderController;
use App\Integrations\Http\Controllers\OAuthTokenController;
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
    | OAuth2 Token (autenticação de parceiros)
    |----------------------------------------------------------------------
    */
    Route::post('/oauth/token', [OAuthTokenController::class, 'issueToken'])
        ->middleware('throttle:20,1')
        ->name('api.oauth.token');

    /*
    |----------------------------------------------------------------------
    | Webhooks (inbound — parceiros enviam dados para nós)
    | Usa HMAC validation, não OAuth2 (parceiro envia, não consulta)
    |----------------------------------------------------------------------
    */
    Route::prefix('webhooks')->group(function () {
        Route::post('/lab/{partnerSlug}', [WebhookController::class, 'labResult'])
            ->middleware(['partner.hmac', 'partner.audit'])
            ->name('api.webhooks.lab');
    });

    /*
    |----------------------------------------------------------------------
    | Rotas autenticadas (OAuth2 Bearer + scopes + rate limit + audit)
    |----------------------------------------------------------------------
    */
    Route::middleware(['partner.auth', 'partner.rate', 'partner.audit'])->group(function () {

        // Lab Orders — laboratório consulta pedidos pendentes
        Route::get('/lab/{partnerSlug}/orders', [LabOrderController::class, 'index'])
            ->middleware(['partner.scope:lab:read', 'partner.consent'])
            ->name('api.lab.orders');

        // Health Check — parceiros verificam status da integração
        Route::get('/health/{partnerSlug}', [PartnerHealthController::class, 'check'])
            ->name('api.health.check');
    });
});
