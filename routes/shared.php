<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\SpecializationController;

/*
|--------------------------------------------------------------------------
| Rotas Compartilhadas
|--------------------------------------------------------------------------
|
| Rotas acessíveis por ambos os tipos de usuário (médicos e pacientes).
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Especializações
    Route::resource('specializations', SpecializationController::class);

    // Appointments (CRUD)
    Route::resource('appointments', AppointmentsController::class);

    // Ações customizadas de appointments
    Route::post('appointments/{appointment}/start', [AppointmentsController::class, 'start'])->middleware('throttle:10,1')->name('appointments.start');
    Route::post('appointments/{appointment}/end', [AppointmentsController::class, 'end'])->middleware('throttle:10,1')->name('appointments.end');
    Route::post('appointments/{appointment}/cancel', [AppointmentsController::class, 'cancel'])->middleware('throttle:10,1')->name('appointments.cancel');
    Route::post('appointments/{appointment}/reschedule', [AppointmentsController::class, 'reschedule'])->middleware('throttle:10,1')->name('appointments.reschedule');

    // Disponibilidade de appointments
    Route::get('api/appointments/availability', [AppointmentsController::class, 'availability'])->name('appointments.availability');
});
