<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiver_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],
            'content' => [
                'required',
                'string',
                'min:1',
                'max:5000',
            ],
            'appointment_id' => [
                'nullable',
                'uuid',
                'exists:appointments,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'receiver_id.required' => 'O destinatário é obrigatório.',
            'receiver_id.exists' => 'O destinatário não foi encontrado.',
            'content.required' => 'A mensagem não pode estar vazia.',
            'content.max' => 'A mensagem não pode ter mais de 5000 caracteres.',
            'appointment_id.exists' => 'A consulta informada não foi encontrada.',
        ];
    }
}
