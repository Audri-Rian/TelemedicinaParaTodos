<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use App\Services\MedicalRecordService;

class MedicalRecordPolicy
{
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

        if ($user->doctor) {
            return $this->medicalRecordService->canDoctorViewPatientRecord($user->doctor, $patient);
        }

        return false;
    }

    public function export(User $user, Patient $patient): bool
    {
        if ($user->id === $patient->user_id) {
            return true;
        }

        return $user->doctor
            ? $this->medicalRecordService->canDoctorViewPatientRecord($user->doctor, $patient)
            : false;
    }

    public function uploadDocument(User $user, Patient $patient): bool
    {
        if ($user->id === $patient->user_id) {
            return true;
        }

        return $user->doctor
            ? $this->medicalRecordService->canDoctorViewPatientRecord($user->doctor, $patient)
            : false;
    }

    public function updatePersonalData(User $user, Patient $patient): bool
    {
        return $user->id === $patient->user_id;
    }
}


