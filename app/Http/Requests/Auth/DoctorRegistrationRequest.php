<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class DoctorRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Qualquer um pode fazer registro
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

            // Dados obrigatórios da tabela doctors
            'crm' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique(Doctor::class)
            ],
            'specializations' => 'required|array|min:1',
            'specializations.*' => [
                'required',
                'uuid',
                Rule::exists(Specialization::class, 'id')
            ],
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
            'crm.required' => 'O CRM é obrigatório.',
            'crm.unique' => 'Este CRM já está sendo usado.',
            'crm.regex' => 'O CRM deve conter apenas letras maiúsculas e números.',
            'specializations.required' => 'Pelo menos uma especialização deve ser selecionada.',
            'specializations.min' => 'Pelo menos uma especialização deve ser selecionada.',
            'specializations.*.required' => 'Especialização inválida.',
            'specializations.*.uuid' => 'Especialização deve ser um UUID válido.',
            'specializations.*.exists' => 'Especialização selecionada não existe.',
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
            'crm' => 'CRM',
            'specializations' => 'especializações',
        ];
    }
}
