<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvailabilitySlotRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['recurring', 'specific'])],
            'day_of_week' => [
                'required_if:type,recurring',
                'nullable',
                Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
            ],
            'specific_date' => [
                'required_if:type,specific',
                'nullable',
                'date',
                'after_or_equal:today'
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                function ($attribute, $value, $fail) {
                    $startTime = $this->input('start_time');
                    if ($startTime && $value) {
                        $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
                        $end = \Carbon\Carbon::createFromFormat('H:i', $value);
                        $diffInMinutes = $start->diffInMinutes($end);
                        $minMinutes = (int) config('telemedicine.availability.slot_min_duration_minutes', 60);
                        if ($diffInMinutes < $minMinutes) {
                            $fail("O horário de fim deve ser pelo menos {$minMinutes} minutos após o horário de início.");
                        }
                    }
                },
            ],
            'location_id' => ['nullable', 'exists:doctor_service_locations,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $minMinutes = (int) config('telemedicine.availability.slot_min_duration_minutes', 60);

        return [
            'type.required' => 'O tipo de slot é obrigatório.',
            'type.in' => 'O tipo de slot deve ser: recorrente ou específico.',
            'day_of_week.required_if' => 'O dia da semana é obrigatório para slots recorrentes.',
            'day_of_week.in' => 'Dia da semana inválido.',
            'specific_date.required_if' => 'A data específica é obrigatória para slots específicos.',
            'specific_date.date' => 'A data específica deve ser uma data válida.',
            'specific_date.after_or_equal' => 'A data específica deve ser hoje ou uma data futura.',
            'start_time.required' => 'O horário de início é obrigatório.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.required' => 'O horário de fim é obrigatório.',
            'end_time.date_format' => 'O horário de fim deve estar no formato HH:MM.',
            'end_time.after' => 'O horário de fim deve ser posterior ao horário de início.',
            'end_time.*' => 'O horário de fim deve ser pelo menos ' . $minMinutes . ' minutos após o horário de início.',
            'location_id.exists' => 'O local de atendimento selecionado não existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Se type é specific, garantir que day_of_week seja null
        if ($this->type === 'specific') {
            $this->merge(['day_of_week' => null]);
        }

        // Se type é recurring, garantir que specific_date seja null
        if ($this->type === 'recurring') {
            $this->merge(['specific_date' => null]);
        }
    }
}

