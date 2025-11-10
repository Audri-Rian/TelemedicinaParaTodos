<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isPatient() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                'uuid',
                Rule::exists('doctors', 'id')->where('status', Doctor::STATUS_ACTIVE),
            ],
            'patient_id' => [
                'required',
                'uuid',
                Rule::exists('patients', 'id'),
            ],
            'scheduled_at' => [
                'required',
                'date',
                'after:now',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'metadata' => [
                'nullable',
                'array',
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
            'doctor_id.exists' => 'O médico selecionado não está ativo ou não existe.',
            'scheduled_at.after' => 'A data e hora agendada deve ser no futuro.',
        ];
    }
}
