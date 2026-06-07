<?php

namespace App\Services;

use App\Models\Appointments;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class MedicalRecordFilterService
{
    public function normalize(array $filters): array
    {
        foreach (['date_from', 'date_to'] as $key) {
            if (! empty($filters[$key])) {
                $filters[$key] = Carbon::parse($filters[$key])->startOfDay();
            }
        }

        return $filters;
    }

    public function applyDoctorPatientListFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('patient.user', function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $this->applyDateRange($query, $filters, 'scheduled_at');

        if (! empty($filters['diagnosis'])) {
            $query->where('metadata->diagnosis', 'like', "%{$filters['diagnosis']}%");
        }
    }

    public function applyAppointmentFilters(Builder $query, array $filters): void
    {
        $statuses = $filters['appointment_status'] ?? [Appointments::STATUS_COMPLETED];

        if (! empty($statuses)) {
            $query->whereIn('status', (array) $statuses);
        }

        $this->applyDoctorFilter($query, $filters);
        $this->applyDateRange($query, $filters, 'scheduled_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('notes', 'like', "%{$search}%")
                    ->orWhere('metadata->diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('doctor.user', function (Builder $doctorQuery) use ($search) {
                        $doctorQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }
    }

    public function applyPrescriptionFilters(Builder $query, array $filters): void
    {
        $this->applyDoctorFilter($query, $filters);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('instructions', 'like', "%{$search}%")
                    ->orWhere('medications->0->name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['prescription_status'])) {
            $query->whereIn('status', (array) $filters['prescription_status']);
        }

        $this->applyDateRange($query, $filters, 'issued_at');
    }

    public function applyExaminationFilters(Builder $query, array $filters): void
    {
        $this->applyDoctorFilter($query, $filters);

        if (! empty($filters['examination_type'])) {
            $query->where('type', $filters['examination_type']);
        }

        if (! empty($filters['examination_status'])) {
            $query->whereIn('status', (array) $filters['examination_status']);
        }

        $this->applyDateRange($query, $filters, 'requested_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('results->summary', 'like', "%{$search}%");
            });
        }
    }

    public function applyDocumentFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['document_category'])) {
            $query->where('category', $filters['document_category']);
        }

        $this->applyDoctorFilter($query, $filters);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $this->applyDateRange($query, $filters, 'created_at');
    }

    public function applyVitalSignFilters(Builder $query, array $filters): void
    {
        $this->applyDoctorFilter($query, $filters);
        $this->applyDateRange($query, $filters, 'recorded_at');
    }

    public function applyDiagnosisFilters(Builder $query, array $filters, ?Doctor $doctor = null): void
    {
        $this->applyDoctorScope($query, $doctor);
        $this->applyDateRange($query, $filters, 'created_at');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('cid10_code', 'like', "%{$search}%")
                    ->orWhere('cid10_description', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
    }

    public function applyClinicalNoteFilters(Builder $query, array $filters, ?Doctor $doctor = null): void
    {
        if (! $doctor) {
            $query->where('is_private', false);
        }

        if ($doctor) {
            $query->where(function (Builder $builder) use ($doctor) {
                $builder->where('is_private', false)
                    ->orWhere('doctor_id', $doctor->id);
            });
        }

        if (! empty($filters['note_category'])) {
            $query->where('category', $filters['note_category']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereJsonContains('tags', $search);
            });
        }
    }

    public function applyMedicalCertificateFilters(Builder $query, array $filters, ?Doctor $doctor = null): void
    {
        $this->applyDoctorScope($query, $doctor);

        if (! empty($filters['certificate_status'])) {
            $query->whereIn('status', (array) $filters['certificate_status']);
        }

        $this->applyDateRange($query, $filters, 'start_date');
    }

    private function applyDoctorFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }
    }

    private function applyDoctorScope(Builder $query, ?Doctor $doctor): void
    {
        if ($doctor) {
            $query->where('doctor_id', $doctor->id);
        }
    }

    private function applyDateRange(Builder $query, array $filters, string $column): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
    }
}
