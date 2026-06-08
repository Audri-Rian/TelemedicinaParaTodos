<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'change_reason' => ['required', 'string', 'min:10', 'max:500'],
            'medications' => ['sometimes', 'required', 'array', 'min:1'],
            'medications.*.name' => ['required', 'string', 'max:255'],
            'medications.*.dosage' => ['required', 'string', 'max:100'],
            'medications.*.frequency' => ['required', 'string', 'max:100'],
            'instructions' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'valid_until' => ['sometimes', 'required', 'date', 'after:today'],
        ];
    }
}
