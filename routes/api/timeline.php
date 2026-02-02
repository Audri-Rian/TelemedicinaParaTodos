<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de API - Timeline Events
|--------------------------------------------------------------------------
|
| Rotas para API de eventos de timeline (educação, cursos, certificados, projetos).
|
*/

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('timeline-events', [App\Http\Controllers\TimelineEventController::class, 'index'])->name('api.timeline-events.index');
    Route::post('timeline-events', [App\Http\Controllers\TimelineEventController::class, 'store'])->name('api.timeline-events.store');
    Route::get('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'show'])->name('api.timeline-events.show');
    Route::put('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'update'])->name('api.timeline-events.update');
    Route::delete('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'destroy'])->name('api.timeline-events.destroy');
});
