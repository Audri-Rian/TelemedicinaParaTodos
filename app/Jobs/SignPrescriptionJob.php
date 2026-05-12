<?php

namespace App\Jobs;

use App\Contracts\PdfSigner;
use App\Models\Prescription;
use App\Services\MedicalRecordPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pipeline PAdES para receitas: gerar PDF → assinar PDF → persistir PDF assinado.
 *
 * Ordem obrigatória (spec ICP-Brasil / CFM Res. 2.314/2022):
 *   1. Gerar bytes do PDF com DomPDF (buildPrescriptionPdfBytes)
 *   2. Assinar o PDF resultante via PdfSigner (PAdES embutido no arquivo)
 *   3. Persistir o PDF assinado em storage e atualizar o registro
 */
class SignPrescriptionJob implements ShouldBeUnique, ShouldQueue
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

    public function __construct(public string $prescriptionId)
    {
        $this->onQueue('documents');
    }

    public function uniqueId(): string
    {
        return $this->prescriptionId;
    }

    public function handle(MedicalRecordPdfService $pdfService, PdfSigner $signer): void
    {
        $prescription = Prescription::with(['doctor.user', 'patient.user'])
            ->find($this->prescriptionId);

        if (! $prescription) {
            Log::warning('SignPrescriptionJob: prescription not found', [
                'id' => $this->prescriptionId,
            ]);

            return;
        }

        if ($prescription->isSigned() && $prescription->pdf_path) {
            Log::info('prescription_pdf_sign_skipped_already_signed', [
                'prescription_id' => $prescription->id,
            ]);

            return;
        }

        // Ensure verification_code exists before generating the PDF
        if (! $prescription->verification_code) {
            $prescription->forceFill([
                'verification_code' => strtoupper(Str::random(12)),
            ])->save();
        }

        // Step 1: generate PDF bytes (unsigned)
        $pdfBytes = $pdfService->buildPrescriptionPdfBytes($prescription);

        // Step 2: sign PDF (PAdES embedded) — NullPdfSigner in dev, A1PdfSigner in prod
        $doctorName = $prescription->doctor->user->name ?? 'Médico';
        $reason = "Receita médica — Dr(a). {$doctorName}";
        $signedPdfBytes = $signer->signPdf($pdfBytes, $prescription->doctor, $reason);

        // Step 3: persist signed PDF and update prescription.pdf_path
        $pdfService->persistSignedPrescriptionPdf(
            $prescription,
            $signedPdfBytes,
            $signer->hasLegalValidity(),
        );

        // Update signature metadata
        $prescription->forceFill([
            'signature_status' => Prescription::SIGNATURE_SIGNED,
            'signed_at' => now(),
            'digital_signature_hash' => hash('sha256', $signedPdfBytes),
        ])->save();

        Log::info('prescription_pdf_signed', [
            'prescription_id' => $prescription->id,
            'driver' => $signer->name(),
            'has_legal_validity' => $signer->hasLegalValidity(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('SignPrescriptionJob failed', [
            'prescription_id' => $this->prescriptionId,
            'error' => $e->getMessage(),
        ]);
    }
}
