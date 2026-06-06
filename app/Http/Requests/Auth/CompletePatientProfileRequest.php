<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompletePatientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && ! auth()->user()->isPatient();
    }

    public function rules(): array
    {
        return [
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'phone_number' => ['required', 'string', 'max:20'],
            'consent_telemedicine' => ['accepted'],
        ];
    }
}
