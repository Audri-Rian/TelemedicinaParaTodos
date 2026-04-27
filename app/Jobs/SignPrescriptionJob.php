<?php

namespace App\Jobs;

use App\Models\Prescription;
use App\Services\Signatures\DigitalSignatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SignPrescriptionJob implements ShouldQueue
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

    public function __construct(public string $prescriptionId)
    {
        $this->onQueue('documents');
    }

    public function handle(DigitalSignatureService $signatures): void
    {
        $prescription = Prescription::find($this->prescriptionId);
        if (! $prescription) {
            Log::warning('SignPrescriptionJob: prescription not found', ['id' => $this->prescriptionId]);

            return;
        }

        $signatures->signPrescription($prescription);
    }

    public function failed(Throwable $e): void
    {
        Log::error('SignPrescriptionJob failed', [
            'prescription_id' => $this->prescriptionId,
            'error' => $e->getMessage(),
        ]);
    }
}
