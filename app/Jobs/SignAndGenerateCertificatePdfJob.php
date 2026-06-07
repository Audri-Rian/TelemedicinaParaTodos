<?php

namespace App\Jobs;

use App\Contracts\PdfSigner;
use App\Models\MedicalCertificate;
use App\Services\MedicalRecordPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pipeline PAdES para atestados: gerar PDF → assinar PDF → persistir PDF assinado.
 *
 * Ordem obrigatória (spec ICP-Brasil / CFM Res. 2.314/2022):
 *   1. Gerar bytes do PDF com DomPDF (buildCertificatePdfBytes)
 *   2. Assinar o PDF resultante via PdfSigner (PAdES embutido no arquivo)
 *   3. Persistir o PDF assinado em storage e atualizar o registro
 */
class SignAndGenerateCertificatePdfJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 3600;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public string $certificateId)
    {
        $this->onQueue('documents');
    }

    public function uniqueId(): string
    {
        return $this->certificateId;
    }

    public function handle(MedicalRecordPdfService $pdfService, PdfSigner $signer): void
    {
        $certificate = MedicalCertificate::with(['doctor.user', 'patient.user'])
            ->find($this->certificateId);

        if (! $certificate) {
            Log::warning('SignAndGenerateCertificatePdfJob: certificate not found', [
                'id' => $this->certificateId,
            ]);

            return;
        }

        if ($certificate->isSigned() && $certificate->pdf_url) {
            Log::info('certificate_pdf_sign_skipped_already_signed', [
                'certificate_id' => $certificate->id,
            ]);

            return;
        }

        // Step 1: generate PDF bytes (unsigned)
        $pdfBytes = $pdfService->buildCertificatePdfBytes($certificate);

        // Step 2: sign PDF (PAdES embedded) — NullPdfSigner in dev, A1PdfSigner in prod
        $doctorName = $certificate->doctor->user->name ?? 'Médico';
        $reason = "Atestado médico — Dr(a). {$doctorName}";
        $signedPdfBytes = $signer->signPdf($pdfBytes, $certificate->doctor, $reason);

        // Step 3: persist signed PDF and update certificate.pdf_url
        $pdfService->persistSignedCertificatePdf(
            $certificate,
            $signedPdfBytes,
            $signer->hasLegalValidity(),
        );

        // Update signature metadata
        $certificate->forceFill([
            'signature_status' => MedicalCertificate::SIGNATURE_SIGNED,
            'signed_at' => now(),
            'signature_hash' => hash('sha256', $signedPdfBytes),
        ])->save();

        Log::info('certificate_pdf_signed', [
            'certificate_id' => $certificate->id,
            'driver' => $signer->name(),
            'has_legal_validity' => $signer->hasLegalValidity(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('SignAndGenerateCertificatePdfJob failed', [
            'certificate_id' => $this->certificateId,
            'error' => $e->getMessage(),
        ]);
    }
}
