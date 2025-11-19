<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class VideoTestController extends Controller
{
    /**
     * Página de desenvolvimento para testes de vídeo
     * Acessível apenas em ambiente local/dev
     */
    public function index(): Response
    {
        $reverbApps = config('reverb.apps.apps', []);
        $reverbApp = $reverbApps[0] ?? null;
        
        return Inertia::render('Dev/VideoTest', [
            'reverb' => $reverbApp ? [
                'key' => $reverbApp['key'] ?? env('REVERB_APP_KEY', ''),
                'host' => $reverbApp['options']['host'] ?? env('REVERB_HOST', 'localhost'),
                'port' => $reverbApp['options']['port'] ?? env('REVERB_PORT', 8080),
                'scheme' => $reverbApp['options']['scheme'] ?? env('REVERB_SCHEME', 'http'),
            ] : null,
        ]);
    }
}
