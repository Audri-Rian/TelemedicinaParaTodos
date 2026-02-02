<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de API - Mensagens
|--------------------------------------------------------------------------
|
| Rotas para API de mensagens entre usuários.
|
*/

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('messages/conversations', [App\Http\Controllers\Api\MessageController::class, 'conversations'])->name('api.messages.conversations');
    Route::get('messages/{userId}', [App\Http\Controllers\Api\MessageController::class, 'messages'])->name('api.messages.show');
    Route::post('messages', [App\Http\Controllers\Api\MessageController::class, 'store'])->middleware('throttle:30,1')->name('api.messages.store');
    Route::post('messages/{userId}/read', [App\Http\Controllers\Api\MessageController::class, 'markAsRead'])->name('api.messages.mark-read');
    Route::post('messages/{messageId}/delivered', [App\Http\Controllers\Api\MessageController::class, 'markAsDelivered'])->name('api.messages.mark-delivered');
    Route::get('messages/unread/count', [App\Http\Controllers\Api\MessageController::class, 'unreadCount'])->name('api.messages.unread-count');
});
