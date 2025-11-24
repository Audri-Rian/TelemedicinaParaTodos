<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use Illuminate\Foundation\Http\FormRequest;

class StoreVitalSignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'recorded_at' => ['nullable', 'date'],
            'blood_pressure_systolic' => ['nullable', 'integer', 'min:50', 'max:250'],
            'blood_pressure_diastolic' => ['nullable', 'integer', 'min:30', 'max:200'],
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'heart_rate' => ['nullable', 'integer', 'between:20,220'],
            'respiratory_rate' => ['nullable', 'integer', 'between:5,80'],
            'oxygen_saturation' => ['nullable', 'integer', 'between:50,100'],
            'weight' => ['nullable', 'numeric', 'between:1,400'],
            'height' => ['nullable', 'numeric', 'between:30,250'],
            'notes' => ['nullable', 'string'],
        ];
    }
}


