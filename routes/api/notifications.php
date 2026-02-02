<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Rotas de API - Notificações
|--------------------------------------------------------------------------
|
| Rotas para API de notificações.
|
*/

Route::middleware(['auth'])->prefix('api/notifications')->name('notifications.')->group(function () {
    // Rota de teste/debug
    Route::get('test', function () {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Não autenticado'], 401);
            }

            $count = \App\Models\Notification::where('user_id', $user->id)->count();
            return response()->json([
                'user_id' => $user->id,
                'total_notifications' => $count,
                'table_exists' => Schema::hasTable('notifications'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    });

    // CRUD de notificações
    Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
    Route::get('unread', [App\Http\Controllers\NotificationController::class, 'unread'])->name('unread');
    Route::get('unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
    Route::get('{id}', [App\Http\Controllers\NotificationController::class, 'show'])->name('show');
    Route::post('{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-as-read');
    Route::post('read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
});
