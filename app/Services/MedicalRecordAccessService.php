<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Patient;

class MedicalRecordAccessService
{
    public function canDoctorViewPatientRecord(Doctor $doctor, Patient $patient): bool
    {
        return Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->whereIn('status', [
                Appointments::STATUS_COMPLETED,
                Appointments::STATUS_IN_PROGRESS,
                Appointments::STATUS_SCHEDULED,
            ])
            ->exists();
    }
}
