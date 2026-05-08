<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\MedicalRecordAuditLog;
use App\Models\Patient;
use App\Models\User;

class MedicalRecordAuditService
{
    public function logAccess(User $user, Patient $patient, string $action, array $metadata = []): MedicalRecordAuditLog
    {
        return MedicalRecordAuditLog::create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    public function logDoctorAction(Doctor $doctor, Patient $patient, string $action, array $metadata = []): MedicalRecordAuditLog
    {
        return $this->logAccess($this->resolveDoctorUser($doctor), $patient, $action, $metadata);
    }

    private function resolveDoctorUser(Doctor $doctor): User
    {
        $user = $doctor->user;

        if (! $user) {
            $user = $doctor->load('user')->user;
        }

        if (! $user) {
            throw new \RuntimeException('Usuário do médico não encontrado.');
        }

        return $user;
    }
}
