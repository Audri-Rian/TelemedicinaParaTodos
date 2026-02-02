<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\PrivacyPolicyController;

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
|
| Rotas acessíveis sem autenticação.
|
*/

// Página inicial
Route::get('/', function () {
    return Inertia::render('index');
})->name('home');

// Redirecionamento de dashboard baseado no papel do usuário
Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    $user = auth()->user();

    if ($user->isDoctor()) {
        return redirect()->route('doctor.dashboard');
    }

    if ($user->isPatient()) {
        return redirect()->route('patient.dashboard');
    }

    return redirect()->route('home');
})->name('dashboard');

// Termos de Serviço e Política de Privacidade
Route::get('terms', [TermsOfServiceController::class, 'index'])->name('terms');
Route::get('privacy', [PrivacyPolicyController::class, 'index'])->name('privacy');

// Teste de fontes
Route::get('font-test', function () {
    return Inertia::render('FontTest');
})->name('font-test');

// Rota para servir arquivos do storage (avatars)
Route::get('storage/avatars/{userId}/{filename}', function ($userId, $filename) {
    $path = "avatars/{$userId}/{$filename}";
    $disk = \Illuminate\Support\Facades\Storage::disk('public');

    if (!$disk->exists($path)) {
        abort(404);
    }

    $file = $disk->get($path);
    $mimeType = $disk->mimeType($path);

    return response($file, 200)
        ->header('Content-Type', $mimeType)
        ->header('Cache-Control', 'public, max-age=31536000');
})->where(['userId' => '[^/]+', 'filename' => '[^/]+'])->name('storage.avatar');

// Rotas de desenvolvimento (apenas local/dev)
Route::middleware([\App\Http\Middleware\EnsureDevelopmentEnvironment::class])->prefix('dev')->group(function () {
    Route::get('video-test', [App\Http\Controllers\Dev\VideoTestController::class, 'index'])->name('dev.video-test');
});
