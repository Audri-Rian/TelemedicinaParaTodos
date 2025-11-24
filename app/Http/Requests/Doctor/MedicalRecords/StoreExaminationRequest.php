<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExaminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['lab', 'image', 'other'])],
            'justification' => ['required', 'string'],
            'instructions' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::in(['normal', 'urgent'])],
        ];
    }
}


