<?php

namespace App\Http\Controllers;

use App\Models\MedicalCertificate;
use App\Models\Prescription;
use App\Services\Signatures\DigitalSignatureService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Verificação pública de documentos médicos assinados.
 *
 * Sem autenticação. Recebe verification_code (impresso no documento),
 * recomputa hash canônico e devolve status. Usado para validar autenticidade
 * de prescrição/atestado emitidos pela plataforma — análogo ao validador
 * do ITI (valida.iti.gov.br).
 *
 * Resolução CFM 2.314/2022 — exige meio de verificação pública.
 *
 * NÃO retorna nome de paciente: apenas tipo de documento, médico/CRM (info
 * pública CFM) e datas. Evita enumeração de PII via brute-force LGPD.
 */
class DocumentVerificationController extends Controller
{
    public function __invoke(string $code, DigitalSignatureService $signatures): Response
    {
        $code = strtoupper(trim($code));
        $clientIp = request()->ip() ?? 'unknown';
        $missKey = "verify:misses:{$clientIp}";

        if (RateLimiter::tooManyAttempts($missKey, 5)) {
            abort(429, 'Muitas tentativas inválidas. Tente novamente em alguns minutos.');
        }

        $cached = Cache::remember(
            "verify:{$code}",
            now()->addMinutes(5),
            fn () => $this->findDocumentPayload($code),
        );

        if (! ($cached['found'] ?? false)) {
            RateLimiter::hit($missKey, 600);

            return Inertia::render('Verify/NotFound', [
                'verificationCode' => $code,
            ]);
        }

        RateLimiter::clear($missKey);
        $type = $cached['type'];
        $model = $type === 'prescription'
            ? Prescription::with(['doctor.user'])->find($cached['id'])
            : MedicalCertificate::with(['doctor.user'])->find($cached['id']);

        if (! $model) {
            Cache::forget("verify:{$code}");
            RateLimiter::hit($missKey, 600);

            return Inertia::render('Verify/NotFound', [
                'verificationCode' => $code,
            ]);
        }

        try {
            $valid = $type === 'prescription'
                ? $signatures->verifyPrescription($model)
                : $signatures->verifyCertificate($model);
        } catch (Throwable $e) {
            Log::error('document_verification_failed', [
                'verification_code' => $code,
                'error' => $e->getMessage(),
            ]);
            $valid = false;
        }

        return Inertia::render('Verify/Show', [
            'verificationCode' => $code,
            'documentType' => $type,
            'hasLegalValidity' => $signatures->hasLegalValidity(),
            'valid' => $valid,
            'document' => $type === 'prescription'
                ? $this->formatPrescription($model)
                : $this->formatCertificate($model),
        ]);
    }

    /**
     * @return array{found: bool, type?: string, id?: string}
     */
    private function findDocumentPayload(string $code): array
    {
        $prescription = Prescription::query()
            ->where('verification_code', $code)
            ->first();
        if ($prescription) {
            return [
                'found' => true,
                'type' => 'prescription',
                'id' => $prescription->id,
            ];
        }

        $certificate = MedicalCertificate::query()
            ->where('verification_code', $code)
            ->first();
        if ($certificate) {
            return [
                'found' => true,
                'type' => 'certificate',
                'id' => $certificate->id,
            ];
        }

        return ['found' => false];
    }

    private function formatPrescription(Prescription $p): array
    {
        return [
            'id' => $p->id,
            'issued_at' => optional($p->issued_at)->toIso8601String(),
            'valid_until' => optional($p->valid_until)->toDateString(),
            'status' => $p->status,
            'signature_status' => $p->signature_status,
            'signed_at' => optional($p->signed_at)->toIso8601String(),
            'doctor_name' => $p->doctor?->user?->name,
            'doctor_crm' => $p->doctor?->crm,
        ];
    }

    private function formatCertificate(MedicalCertificate $c): array
    {
        return [
            'id' => $c->id,
            'type' => $c->type,
            'start_date' => optional($c->start_date)->toDateString(),
            'end_date' => optional($c->end_date)->toDateString(),
            'days' => $c->days,
            'status' => $c->status,
            'signature_status' => $c->signature_status,
            'signed_at' => optional($c->signed_at)->toIso8601String(),
            'doctor_name' => $c->doctor?->user?->name,
            'doctor_crm' => $c->crm_number,
        ];
    }
}
