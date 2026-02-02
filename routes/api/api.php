<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpecializationController;

/*
|--------------------------------------------------------------------------
| Rotas de API
|--------------------------------------------------------------------------
|
| Rotas para APIs internas da aplicação.
|
*/

// Rotas públicas de API
Route::prefix('api')->group(function () {
    // Especializações (público)
    Route::get('specializations/list', [SpecializationController::class, 'list'])->name('api.specializations.list');
    Route::get('specializations/options', [SpecializationController::class, 'options'])->name('api.specializations.options');

    // Disponibilidade de médicos (público - pacientes podem consultar)
    Route::get('doctors/{doctor}/availability/{date}', [App\Http\Controllers\Doctor\DoctorAvailabilitySlotController::class, 'getByDate'])->name('api.doctors.availability.date');
});

// Incluir sub-arquivos de API
require __DIR__.'/messages.php';
require __DIR__.'/timeline.php';
require __DIR__.'/notifications.php';
