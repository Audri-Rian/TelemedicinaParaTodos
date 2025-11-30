<?php

namespace App\Http\Requests\Settings;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Dados do User
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Dados do Patient (segunda etapa de autenticação)
            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'medical_history' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'blood_type' => ['nullable', 'string', 'max:5', Rule::in(Patient::BLOOD_TYPES)],
            'height' => ['nullable', 'numeric', 'min:50', 'max:250'], // em cm
            'weight' => ['nullable', 'numeric', 'min:1', 'max:500'], // em kg
            'insurance_provider' => ['nullable', 'string', 'max:100'],
            'insurance_number' => ['nullable', 'string', 'max:50'],
            'consent_telemedicine' => ['nullable', 'boolean', 'sometimes'],
            // Dados do Doctor (campos opcionais)
            'biography' => ['nullable', 'string', 'max:5000'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_expiry_date' => ['nullable', 'date'],
            'consultation_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'availability_schedule' => ['nullable', 'array'],
        ];
    }
}
