<?php

use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\SpecializationController;
use Illuminate\Support\Facades\Route;

// Rotas compartilhadas (ambos os tipos de usuário)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('specializations', SpecializationController::class);
    Route::resource('appointments', AppointmentsController::class);

    // Ações customizadas para appointments
    Route::post('appointments/{appointment}/start', [AppointmentsController::class, 'start'])->middleware('throttle:10,1')->name('appointments.start');
    Route::post('appointments/{appointment}/end', [AppointmentsController::class, 'end'])->middleware('throttle:10,1')->name('appointments.end');
    Route::post('appointments/{appointment}/cancel', [AppointmentsController::class, 'cancel'])->middleware('throttle:10,1')->name('appointments.cancel');
    Route::post('appointments/{appointment}/reschedule', [AppointmentsController::class, 'reschedule'])->middleware('throttle:10,1')->name('appointments.reschedule');

    Route::get('api/appointments/availability', [AppointmentsController::class, 'availability'])->name('appointments.availability');

    // Timeline events e mensagens
    Route::prefix('api')->group(function () {
        Route::get('timeline-events', [App\Http\Controllers\TimelineEventController::class, 'index'])->name('api.timeline-events.index');
        Route::post('timeline-events', [App\Http\Controllers\TimelineEventController::class, 'store'])->name('api.timeline-events.store');
        Route::get('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'show'])->name('api.timeline-events.show');
        Route::put('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'update'])->name('api.timeline-events.update');
        Route::delete('timeline-events/{timelineEvent}', [App\Http\Controllers\TimelineEventController::class, 'destroy'])->name('api.timeline-events.destroy');

        // Mensagens (rotas estáticas antes das dinâmicas)
        Route::get('messages/conversations', [App\Http\Controllers\Api\MessageController::class, 'conversations'])->name('api.messages.conversations');
        Route::get('messages/unread/count', [App\Http\Controllers\Api\MessageController::class, 'unreadCount'])->name('api.messages.unread-count');
        Route::post('messages', [App\Http\Controllers\Api\MessageController::class, 'store'])->middleware('throttle:30,1')->name('api.messages.store');
        Route::get('messages/{userId}', [App\Http\Controllers\Api\MessageController::class, 'messages'])->name('api.messages.show');
        Route::post('messages/{userId}/read', [App\Http\Controllers\Api\MessageController::class, 'markAsRead'])->name('api.messages.mark-read');
        Route::post('messages/{messageId}/delivered', [App\Http\Controllers\Api\MessageController::class, 'markAsDelivered'])->name('api.messages.mark-delivered');
    });
});

// Notificações
Route::middleware(['auth'])->prefix('api/notifications')->name('notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
    Route::get('unread', [App\Http\Controllers\NotificationController::class, 'unread'])->name('unread');
    Route::get('unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('push-subscriptions', [App\Http\Controllers\PushSubscriptionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('push-subscriptions.store');
    Route::delete('push-subscriptions', [App\Http\Controllers\PushSubscriptionController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('push-subscriptions.destroy');
    Route::get('{id}', [App\Http\Controllers\NotificationController::class, 'show'])->name('show');
    Route::post('{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-as-read');
    Route::post('read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
});
