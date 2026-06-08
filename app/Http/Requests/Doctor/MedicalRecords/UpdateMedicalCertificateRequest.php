<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicalCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'change_reason' => ['required', 'string', 'min:10', 'max:500'],
            'type' => ['sometimes', 'required', Rule::in(['absence', 'attendance', 'disability', 'other'])],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'days' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'reason' => ['sometimes', 'required', 'string', 'max:2000'],
            'restrictions' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
