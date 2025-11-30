<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\SanitizeInput::class,
        ]);

        $middleware->alias([
            'doctor' => \App\Http\Middleware\EnsureUserIsDoctor::class,
            'patient' => \App\Http\Middleware\EnsureUserIsPatient::class,
            'audit' => \App\Http\Middleware\AuditAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Renderizar página de erro customizada para erros HTTP
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) {
            // Verificar se a requisição espera uma resposta JSON (API) ou se é uma rota de API
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Erro na requisição',
                    'status' => $e->getStatusCode(),
                ], $e->getStatusCode());
            }

            // Para requisições web, usar Inertia para renderizar a página de erro customizada
            if ($request->is('*')) {
                return \Inertia\Inertia::render('Error', [
                    'status' => $e->getStatusCode(),
                    'message' => $e->getMessage() ?: 'Algo deu errado',
                ])->toResponse($request)->setStatusCode($e->getStatusCode());
            }
        });
        
        // Capturar todas as exceções não tratadas para rotas de API (apenas se não for HttpException)
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Se for uma rota de API e não for uma HttpException (já tratada acima)
            if ($request->is('api/*') && !($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)) {
                \Log::error('Erro não tratado em API: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'url' => $request->url(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                
                return response()->json([
                    'message' => app()->environment('production') 
                        ? 'Erro interno do servidor' 
                        : $e->getMessage(),
                    'status' => 500,
                ], 500);
            }
        });

        // Log de erros para monitoramento e métricas
        $exceptions->report(function (\Throwable $e) {
            // Log detalhado em produção para monitoramento
            if (app()->environment('production')) {
                Log::error('Erro capturado', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => request()->url(),
                    'method' => request()->method(),
                    'user_id' => auth()->id(),
                ]);
            }
        });
    })->create();
