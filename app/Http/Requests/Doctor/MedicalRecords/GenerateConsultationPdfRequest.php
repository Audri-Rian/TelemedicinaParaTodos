<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateConsultationPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->doctor !== null;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'uuid', Rule::exists('appointments', 'id')],
        ];
    }
}
