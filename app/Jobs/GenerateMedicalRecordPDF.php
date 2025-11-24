<?php

namespace App\Jobs;

use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\User;
use App\Services\MedicalRecordService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateMedicalRecordPDF implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        private readonly Patient $patient,
        private readonly User $requester,
        private readonly array $filters = [],
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(MedicalRecordService $medicalRecordService): void
    {
        $payload = $medicalRecordService->prepareDataForExport($this->patient, $this->filters);

        $pdf = Pdf::loadView('pdf.medical-record', $payload)->setPaper('a4');

        $disk = Storage::disk('public');
        $filename = sprintf('medical-record-%s.pdf', now()->format('YmdHis'));
        $path = "medical-records/exports/{$this->patient->id}/{$filename}";

        $disk->put($path, $pdf->output());
        $fileSize = $disk->size($path);

        MedicalDocument::create([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->requester->doctor?->id,
            'uploaded_by' => $this->requester->id,
            'appointment_id' => null,
            'category' => MedicalDocument::CATEGORY_REPORT,
            'name' => sprintf('Exportação do Prontuário - %s', now()->format('d/m/Y H:i')),
            'file_path' => $path,
            'file_type' => 'application/pdf',
            'file_size' => $fileSize,
            'description' => 'Exportação completa do prontuário em PDF',
            'metadata' => [
                'filters' => $this->filters,
                'generated_by' => $this->requester->id,
            ],
            'visibility' => MedicalDocument::VISIBILITY_PATIENT,
        ]);
    }

    /**
     * Trate falhas para não bloquear novas exportações.
     */
    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
