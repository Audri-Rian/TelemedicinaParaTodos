<?php

namespace App\Http\Support;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class ErrorPageResponse
{
    /**
     * Resposta JSON para clientes API; página Inertia Error para navegador.
     *
     * @param  array<string, mixed>|null  $jsonBody
     */
    public static function make(
        Request $request,
        int $status,
        string $message,
        ?array $jsonBody = null,
    ): Response {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($jsonBody ?? [
                'message' => $message,
                'status' => $status,
            ], $status);
        }

        return Inertia::render('Error', [
            'status' => $status,
            'message' => $message,
        ])->toResponse($request)->setStatusCode($status);
    }
}
