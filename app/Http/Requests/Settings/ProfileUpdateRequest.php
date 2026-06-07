<?php

namespace App\Http\Requests\Settings;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'crm' => $this->crm ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->crm)) : null,
            'cns' => $this->cns ? preg_replace('/\D/', '', $this->cns) : null,
            'cpf' => $this->cpf ? preg_replace('/\D/', '', $this->cpf) : null,
            'cbo' => $this->cbo ? preg_replace('/\D/', '', $this->cbo) : null,
        ]);
    }

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
            // Identificadores de saúde (interoperabilidade)
            'cns' => ['nullable', 'string', 'regex:/^\d{15}$/'],
            'cpf' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'cbo' => ['nullable', 'string', 'regex:/^\d{6}$/'],
            // Dados do Patient (segunda etapa de autenticação)
            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'medical_history' => ['nullable', 'string', 'max:10000'],
            'allergies' => ['nullable', 'string', 'max:5000'],
            'current_medications' => ['nullable', 'string', 'max:5000'],
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
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'availability_schedule' => ['nullable', 'array', 'max:7'],
            'availability_schedule.*.slots' => ['sometimes', 'array', 'max:48'],
            'availability_schedule.*.slots.*' => ['string', 'regex:/^\d{2}:\d{2}$/'],
            'availability_schedule.*.start' => ['sometimes', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'availability_schedule.*.end' => ['sometimes', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'crm' => [
                Rule::excludeIf(fn () => ! $this->user()?->isDoctor()),
                Rule::requiredIf(fn () => (bool) $this->user()?->isDoctor()),
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique(Doctor::class)->ignore($this->user()?->doctor?->id),
            ],
            'specializations' => [
                Rule::excludeIf(fn () => ! $this->user()?->isDoctor()),
                Rule::requiredIf(fn () => (bool) $this->user()?->isDoctor()),
                'array',
                'min:1',
            ],
            'specializations.*' => ['uuid', Rule::exists(Specialization::class, 'id')],
        ];
    }
}
