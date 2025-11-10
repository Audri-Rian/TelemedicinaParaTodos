<?php

namespace App\Http\Requests;

use App\Models\Appointments;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');
        return $this->user() && $this->user()->can('update', $appointment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $appointment = $this->route('appointment');
        
        $rules = [
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

        // Campos críticos não podem ser alterados se estiver em progresso
        if ($appointment && $appointment->status !== Appointments::STATUS_IN_PROGRESS) {
            // Apenas notes e metadata podem ser alterados após in_progress
        }

        return $rules;
    }
}
