<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use App\Models\Appointments;
use App\Models\Examination;
use App\Models\MedicalDocument;
use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorMedicalRecordFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->doctor !== null;
    }

    public function rules(): array
    {
        $doctorId = $this->user()?->doctor?->id;

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'doctor_id' => [
                'nullable',
                'uuid',
                Rule::exists('doctors', 'id'),
                Rule::in(array_filter([$doctorId])),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'appointment_status' => [
                'nullable',
                Rule::in([
                    Appointments::STATUS_SCHEDULED,
                    Appointments::STATUS_IN_PROGRESS,
                    Appointments::STATUS_COMPLETED,
                    Appointments::STATUS_NO_SHOW,
                    Appointments::STATUS_CANCELLED,
                    Appointments::STATUS_RESCHEDULED,
                ]),
            ],
            'prescription_status' => [
                'nullable',
                Rule::in([
                    Prescription::STATUS_ACTIVE,
                    Prescription::STATUS_EXPIRED,
                    Prescription::STATUS_CANCELLED,
                ]),
            ],
            'examination_status' => [
                'nullable',
                Rule::in([
                    Examination::STATUS_REQUESTED,
                    Examination::STATUS_IN_PROGRESS,
                    Examination::STATUS_COMPLETED,
                    Examination::STATUS_CANCELLED,
                ]),
            ],
            'examination_type' => [
                'nullable',
                Rule::in([
                    Examination::TYPE_LAB,
                    Examination::TYPE_IMAGE,
                    Examination::TYPE_OTHER,
                ]),
            ],
            'document_category' => [
                'nullable',
                Rule::in([
                    MedicalDocument::CATEGORY_EXAM,
                    MedicalDocument::CATEGORY_PRESCRIPTION,
                    MedicalDocument::CATEGORY_REPORT,
                    MedicalDocument::CATEGORY_OTHER,
                ]),
            ],
            'vitals_limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
