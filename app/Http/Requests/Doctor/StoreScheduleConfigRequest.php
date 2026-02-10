<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleConfigRequest extends FormRequest
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
            'locations' => ['sometimes', 'array'],
            'locations.*.name' => ['required_with:locations', 'string', 'max:255'],
            'locations.*.type' => ['required_with:locations', Rule::in(['teleconsultation', 'office', 'hospital', 'clinic'])],
            'locations.*.address' => ['nullable', 'string'],
            'locations.*.phone' => ['nullable', 'string', 'max:20'],
            'locations.*.description' => ['nullable', 'string'],

            'recurring_slots' => ['sometimes', 'array'],
            'recurring_slots.*.day_of_week' => ['required', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'recurring_slots.*.start_time' => ['required', 'date_format:H:i'],
            'recurring_slots.*.end_time' => [
                'required',
                'date_format:H:i',
                'after:recurring_slots.*.start_time',
                function ($attribute, $value, $fail) {
                    // Extrair o índice do slot do atributo (ex: "recurring_slots.0.end_time")
                    preg_match('/recurring_slots\.(\d+)\.end_time/', $attribute, $matches);
                    if (isset($matches[1])) {
                        $index = $matches[1];
                        $startTime = $this->input("recurring_slots.{$index}.start_time");
                        if ($startTime && $value) {
                            $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
                            $end = \Carbon\Carbon::createFromFormat('H:i', $value);
                            $diffInMinutes = $start->diffInMinutes($end);
                            $minMinutes = (int) config('telemedicine.availability.slot_min_duration_minutes', 60);
                            if ($diffInMinutes < $minMinutes) {
                                $fail('O horário de fim deve ser pelo menos 1 hora após o horário de início.');
                            }
                        }
                    }
                },
            ],
            'recurring_slots.*.location_id' => ['nullable', 'exists:doctor_service_locations,id'],

            'specific_slots' => ['sometimes', 'array'],
            'specific_slots.*.date' => ['required', 'date', 'after_or_equal:today'],
            'specific_slots.*.slots' => ['required', 'array'],
            'specific_slots.*.slots.*.start_time' => ['required', 'date_format:H:i'],
            'specific_slots.*.slots.*.end_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    // Extrair os índices do slot (ex: "specific_slots.0.slots.1.end_time")
                    preg_match('/specific_slots\.(\d+)\.slots\.(\d+)\.end_time/', $attribute, $matches);
                    if (isset($matches[1]) && isset($matches[2])) {
                        $dateIndex = $matches[1];
                        $slotIndex = $matches[2];
                        $startTime = $this->input("specific_slots.{$dateIndex}.slots.{$slotIndex}.start_time");
                        if ($startTime && $value) {
                            $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
                            $end = \Carbon\Carbon::createFromFormat('H:i', $value);
                            $diffInMinutes = $start->diffInMinutes($end);
                            $minMinutes = (int) config('telemedicine.availability.slot_min_duration_minutes', 60);
                            if ($diffInMinutes < $minMinutes) {
                                $fail('O horário de fim deve ser pelo menos 1 hora após o horário de início.');
                            }
                        }
                    }
                },
            ],
            'specific_slots.*.slots.*.location_id' => ['nullable', 'exists:doctor_service_locations,id'],

            'blocked_dates' => ['sometimes', 'array'],
            'blocked_dates.*.blocked_date' => ['required', 'date', 'after_or_equal:today'],
            'blocked_dates.*.reason' => ['nullable', 'string', 'max:500'],
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
            'locations.*.name.required_with' => 'O nome do local é obrigatório.',
            'recurring_slots.*.day_of_week.required' => 'O dia da semana é obrigatório.',
            'recurring_slots.*.start_time.required' => 'O horário de início é obrigatório.',
            'recurring_slots.*.end_time.required' => 'O horário de fim é obrigatório.',
            'recurring_slots.*.end_time.after' => 'O horário de fim deve ser posterior ao horário de início.',
            'specific_slots.*.date.required' => 'A data específica é obrigatória.',
            'specific_slots.*.date.after_or_equal' => 'A data específica deve ser hoje ou uma data futura.',
            'blocked_dates.*.blocked_date.required' => 'A data bloqueada é obrigatória.',
            'blocked_dates.*.blocked_date.after_or_equal' => 'A data bloqueada deve ser hoje ou uma data futura.',
        ];
    }
}

