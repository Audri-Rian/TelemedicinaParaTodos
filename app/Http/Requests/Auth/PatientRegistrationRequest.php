<?php
// app/Http/Requests/Auth/PatientRegistrationRequest.php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Carbon\Carbon;

class PatientRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Qualquer um pode fazer registro
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converter data de dd/mm/aaaa para aaaa-mm-dd
        if ($this->date_of_birth) {
            try {
                $date = Carbon::createFromFormat('d/m/Y', $this->date_of_birth);
                $this->merge([
                    'date_of_birth' => $date->format('Y-m-d')
                ]);
            } catch (\Exception $e) {
                // Se não conseguir converter, mantém o valor original
                // A validação vai capturar o erro
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Dados obrigatórios da tabela users
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // Dados obrigatórios da tabela patients (conforme migration)
            'gender' => 'required|string|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'phone_number' => 'required|string|max:20',

            // Dados opcionais da tabela patients (conforme migration)
            'emergency_contact' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:20',
            'medical_history' => 'nullable|string',
            'allergies' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'blood_type' => 'nullable|string|max:5',
            'height' => 'nullable|numeric|min:50|max:300',
            'weight' => 'nullable|numeric|min:10|max:500',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_number' => 'nullable|string|max:50',
            'consent_telemedicine' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.unique' => 'Este email já está sendo usado.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'gender.required' => 'O gênero é obrigatório.',
            'gender.in' => 'O gênero deve ser masculino, feminino ou outro.',
            'date_of_birth.required' => 'A data de nascimento é obrigatória.',
            'date_of_birth.before' => 'A data de nascimento deve ser anterior a hoje.',
            'phone_number.required' => 'O telefone é obrigatório.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'email',
            'password' => 'senha',
            'password_confirmation' => 'confirmação da senha',
            'gender' => 'gênero',
            'date_of_birth' => 'data de nascimento',
            'phone_number' => 'telefone',
            'emergency_contact' => 'contato de emergência',
            'emergency_phone' => 'telefone de emergência',
            'medical_history' => 'histórico médico',
            'allergies' => 'alergias',
            'current_medications' => 'medicamentos atuais',
            'blood_type' => 'tipo sanguíneo',
            'height' => 'altura',
            'weight' => 'peso',
            'insurance_provider' => 'provedor de seguro',
            'insurance_number' => 'número do seguro',
            'consent_telemedicine' => 'consentimento de telemedicina',
        ];
    }
}