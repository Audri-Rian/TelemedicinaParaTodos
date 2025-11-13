<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['teleconsultation', 'office', 'hospital', 'clinic'])],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do local é obrigatório.',
            'name.max' => 'O nome do local não pode ter mais de 255 caracteres.',
            'type.required' => 'O tipo de local é obrigatório.',
            'type.in' => 'O tipo de local deve ser: Teleconsulta, Consultório, Hospital ou Clínica.',
            'phone.max' => 'O telefone não pode ter mais de 20 caracteres.',
        ];
    }
}

