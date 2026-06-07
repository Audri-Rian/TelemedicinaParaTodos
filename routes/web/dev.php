<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Rotas de desenvolvimento (apenas local/dev)
Route::middleware([\App\Http\Middleware\EnsureDevelopmentEnvironment::class])->prefix('dev')->name('dev.')->group(function () {
    Route::get('video-test', [App\Http\Controllers\Dev\VideoTestController::class, 'index'])->name('video-test');
    Route::get('font-test', function () {
        return Inertia::render('FontTest');
    })->name('font-test');

    // Páginas de teste do SFU
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('sfu-test', [App\Http\Controllers\SfuTestController::class, 'index'])->name('sfu-test');
        Route::get('sfu-load-test', [App\Http\Controllers\SfuTestController::class, 'loadTest'])->name('sfu-load-test');
    });
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('sfu-call-simulator', [App\Http\Controllers\Dev\SfuCallSimulatorController::class, 'index'])->name('sfu-call-simulator');
        Route::get('sfu-call-simulator/status', [App\Http\Controllers\Dev\SfuCallSimulatorController::class, 'status'])->name('sfu-call-simulator.status');
        Route::post('sfu-call-simulator/session', [App\Http\Controllers\Dev\SfuCallSimulatorController::class, 'session'])->name('sfu-call-simulator.session');
        Route::delete('sfu-call-simulator/session', [App\Http\Controllers\Dev\SfuCallSimulatorController::class, 'reset'])->name('sfu-call-simulator.reset');
    });

    // Diagnóstico de notificações
    Route::middleware(['auth'])->get('notifications/test', App\Http\Controllers\Dev\NotificationTestController::class)->name('notifications.test');

    // ReDoc — documentação da API
    Route::get('redoc', function () {
        return view('redoc', [
            'title' => 'Telemedicina para Todos API',
            'specUrl' => route('l5-swagger.default.docs'),
        ]);
    })->name('redoc');
});
