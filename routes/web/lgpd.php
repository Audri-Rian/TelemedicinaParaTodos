<?php

use Illuminate\Support\Facades\Route;

// Rotas LGPD (compliance)
Route::middleware(['auth', 'verified', 'throttle:10,1'])->prefix('lgpd')->name('lgpd.')->group(function () {
    Route::get('consents', [App\Http\Controllers\LGPD\ConsentController::class, 'index'])->name('consents.index');
    Route::post('consents/grant', [App\Http\Controllers\LGPD\ConsentController::class, 'grant'])->name('consents.grant');
    Route::post('consents/revoke', [App\Http\Controllers\LGPD\ConsentController::class, 'revoke'])->name('consents.revoke');
    Route::get('consents/check', [App\Http\Controllers\LGPD\ConsentController::class, 'check'])->name('consents.check');

    Route::get('data-portability', [App\Http\Controllers\LGPD\DataPortabilityController::class, 'index'])->name('data-portability.index');
    Route::get('data-portability/export', [App\Http\Controllers\LGPD\DataPortabilityController::class, 'export'])->middleware('throttle:3,1')->name('data-portability.export');

    Route::get('right-to-be-forgotten', [App\Http\Controllers\LGPD\RightToBeForgottenController::class, 'index'])->name('right-to-be-forgotten.index');
    Route::post('right-to-be-forgotten/request', [App\Http\Controllers\LGPD\RightToBeForgottenController::class, 'request'])->middleware('throttle:1,60')->name('right-to-be-forgotten.request');

    Route::get('data-access-report', [App\Http\Controllers\LGPD\DataAccessReportController::class, 'index'])->name('data-access-report.index');
    Route::post('data-access-report/generate', [App\Http\Controllers\LGPD\DataAccessReportController::class, 'generate'])->middleware('throttle:3,1')->name('data-access-report.generate');
});
