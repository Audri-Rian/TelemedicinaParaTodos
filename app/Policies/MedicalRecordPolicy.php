<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use App\MedicalRecord\Application\Services\MedicalRecordService;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicalRecordPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $user->isPatient() || $user->isDoctor();
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->id === $patient->user_id) {
            return true;
        }

        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function export(User $user, Patient $patient): bool
    {
        if ($user->id === $patient->user_id) {
            return true;
        }

        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function uploadDocument(User $user, Patient $patient): bool
    {
        if ($user->id === $patient->user_id) {
            return true;
        }

        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function registerDiagnosis(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function issuePrescription(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient) && !empty($user->doctor?->crm);
    }

    public function requestExamination(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function createNote(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function issueCertificate(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient) && !empty($user->doctor?->crm);
    }

    public function registerVitalSigns(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function generateConsultationPdf(User $user, Patient $patient): bool
    {
        return $this->doctorCanAccessPatient($user, $patient);
    }

    public function updatePersonalData(User $user, Patient $patient): bool
    {
        return $user->id === $patient->user_id;
    }

    protected function doctorCanAccessPatient(User $user, Patient $patient): bool
    {
        if (!$user->doctor) {
            return false;
        }

        return $this->medicalRecordService->canDoctorViewPatientRecord($user->doctor, $patient);
    }
}
