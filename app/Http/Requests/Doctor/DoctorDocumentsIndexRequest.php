<?php

namespace App\Http\Requests\Doctor;

use App\Models\Appointments;
use App\Models\MedicalDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorDocumentsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->doctor !== null;
    }

    public function rules(): array
    {
        return [
            'patient_id' => [
                'nullable',
                'uuid',
                Rule::exists('patients', 'id'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $doctorId = $this->user()?->doctor?->id;
                    if (! $doctorId || ! is_string($value)) {
                        return;
                    }

                    $ownsPatient = Appointments::query()
                        ->where('doctor_id', $doctorId)
                        ->where('patient_id', $value)
                        ->exists();

                    if (! $ownsPatient) {
                        $fail('Paciente inválido para este médico.');
                    }
                },
            ],
            'category' => [
                'nullable',
                Rule::in([
                    MedicalDocument::CATEGORY_EXAM,
                    MedicalDocument::CATEGORY_PRESCRIPTION,
                    MedicalDocument::CATEGORY_REPORT,
                    MedicalDocument::CATEGORY_OTHER,
                ]),
            ],
            'period_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}
