<?php

namespace App\Integrations\Http\Middleware;

use App\Models\Patient;
use App\Services\LGPDService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica consentimento do paciente antes de retornar dados via API pública.
 *
 * Busca o paciente no request (via query param, route param ou body)
 * e verifica se há consentimento ativo de data_sharing_lab.
 */
class EnforcePatientConsent
{
    public function __construct(
        private readonly LGPDService $lgpdService,
    ) {}

    public function handle(Request $request, Closure $next, string $consentType = 'data_sharing_lab'): Response
    {
        $patientId = $request->input('patient_id')
            ?? $request->route('patientId')
            ?? null;

        // Para endpoints que não referenciam um paciente específico, prosseguir
        if (! $patientId) {
            return $next($request);
        }

        $patient = Patient::with('user')->find($patientId);

        if (! $patient || ! $patient->user) {
            return response()->json([
                'error' => 'not_found',
                'error_description' => 'Patient not found.',
            ], 404);
        }

        $hasConsent = $this->lgpdService->hasActiveConsent($patient->user, $consentType);

        if (! $hasConsent) {
            Log::channel('integration')->warning('Acesso bloqueado: paciente sem consentimento', [
                'patient_id' => $patientId,
                'consent_type' => $consentType,
                'partner_id' => $request->attributes->get('partner')?->id,
            ]);

            return response()->json([
                'error' => 'consent_required',
                'error_description' => "Patient has not granted '{$consentType}' consent.",
            ], 403);
        }

        return $next($request);
    }
}
