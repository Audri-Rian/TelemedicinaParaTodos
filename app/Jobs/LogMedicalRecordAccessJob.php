<?php

namespace App\Jobs;

use App\Models\MedicalRecordAuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogMedicalRecordAccessJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly User $user,
        private readonly Patient $patient,
        private readonly string $action,
        private readonly array $metadata = [],
        private readonly ?string $ipAddress = null,
        private readonly ?string $userAgent = null,
    ) {}

    public function handle(): void
    {
        MedicalRecordAuditLog::create([
            'patient_id' => $this->patient->id,
            'user_id' => $this->user->id,
            'action' => $this->action,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'metadata' => $this->metadata,
        ]);
    }
}
