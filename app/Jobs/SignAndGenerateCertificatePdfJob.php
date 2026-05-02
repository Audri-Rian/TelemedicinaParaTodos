<?php

namespace App\Jobs;

use App\Models\MedicalCertificate;
use App\Services\MedicalRecordService;
use App\Services\Signatures\DigitalSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Assina (driver configurado) e gera PDF do atestado em background.
 *
 * Tira do request síncrono operações que podem levar 2–8s:
 *  - chamada HTTP ao provedor ICP-Brasil (sign);
 *  - render dompdf (Blade -> PDF -> Storage::put).
 */
class SignAndGenerateCertificatePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public string $certificateId)
    {
        $this->onQueue('documents');
    }

    public function handle(MedicalRecordService $service, DigitalSignatureService $signatures): void
    {
        $certificate = MedicalCertificate::find($this->certificateId);
        if (! $certificate) {
            Log::warning('SignAndGenerateCertificatePdfJob: certificate not found', ['id' => $this->certificateId]);

            return;
        }

        $certificate = $signatures->signCertificate($certificate);

        $pdf = $service->buildMedicalCertificatePdf($certificate);
        if ($pdf) {
            $certificate->forceFill(['pdf_url' => null])->save();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SignAndGenerateCertificatePdfJob failed', [
            'certificate_id' => $this->certificateId,
            'error' => $e->getMessage(),
        ]);
    }
}
