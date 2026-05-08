<?php

namespace App\Jobs;

use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\User;
use App\Services\FileStorageManager;
use App\Services\MedicalRecordService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateMedicalRecordPDF implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly Patient $patient,
        private readonly User $requester,
        private readonly array $filters = [],
        private readonly string $visibility = MedicalDocument::VISIBILITY_PATIENT,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        MedicalRecordService $medicalRecordService,
        FileStorageManager $fileStorageManager,
    ): void {
        $exportKey = hash('sha256', json_encode([
            'patient_id' => $this->patient->id,
            'requester_id' => $this->requester->id,
            'filters' => $this->filters,
            'visibility' => $this->visibility,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $payload = $medicalRecordService->prepareDataForExport($this->patient, $this->filters);

        $pdf = Pdf::loadView('pdf.medical-record', $payload)->setPaper('a4');

        $diskDomain = FileStorageManager::DOMAIN_MEDICAL_DOCUMENTS;
        $disk = $fileStorageManager->disk($diskDomain);
        $filename = sprintf('medical-record-%s-%s.pdf', $this->patient->id, substr($exportKey, 0, 12));
        $path = $fileStorageManager->buildPath($diskDomain, "exports/{$this->patient->id}/{$filename}");

        $disk->put($path, $pdf->output());
        $fileSize = $disk->size($path);

        $attributes = [
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
                'storage_domain' => $diskDomain,
                'export_key' => $exportKey,
            ],
            'visibility' => $this->visibility,
        ];

        $existing = MedicalDocument::query()
            ->where('patient_id', $this->patient->id)
            ->where('uploaded_by', $this->requester->id)
            ->where('category', MedicalDocument::CATEGORY_REPORT)
            ->whereJsonContains('metadata->export_key', $exportKey)
            ->first();

        if ($existing) {
            $existing->update($attributes);

            return;
        }

        MedicalDocument::create($attributes);
    }

    /**
     * Trate falhas para não bloquear novas exportações.
     */
    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
