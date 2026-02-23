<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecializationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Comprimento máximo do nome da especialização (configurado em telemedicine.validation.specialization_name_max_length).
     */
    private function maxNameLength(): int
    {
        return (int) config('telemedicine.validation.specialization_name_max_length', 100);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:' . $this->maxNameLength() . '|unique:specializations,name',
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
            'name.required' => 'O nome da especialização é obrigatório.',
            'name.unique' => 'Uma especialização com este nome já existe.',
            'name.max' => 'O nome não pode ter mais de ' . $this->maxNameLength() . ' caracteres.',
        ];
    }
}

