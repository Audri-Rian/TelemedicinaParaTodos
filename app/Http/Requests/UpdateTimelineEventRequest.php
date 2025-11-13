<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\TimelineEvent;

class UpdateTimelineEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization será feita pela Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in([
                    TimelineEvent::TYPE_EDUCATION,
                    TimelineEvent::TYPE_COURSE,
                    TimelineEvent::TYPE_CERTIFICATE,
                    TimelineEvent::TYPE_PROJECT,
                ]),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'media_url' => ['nullable', 'string', 'url', 'max:500'],
            'extra_data' => ['nullable', 'array'],
            'order_priority' => ['nullable', 'integer', 'min:0'],
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
            'type.required' => 'O tipo do evento é obrigatório.',
            'type.in' => 'O tipo do evento deve ser: education, course, certificate ou project.',
            'title.required' => 'O título é obrigatório.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'subtitle.max' => 'O subtítulo não pode ter mais de 255 caracteres.',
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'start_date.date_format' => 'A data de início deve estar no formato YYYY-MM-DD.',
            'end_date.date' => 'A data de fim deve ser uma data válida.',
            'end_date.date_format' => 'A data de fim deve estar no formato YYYY-MM-DD.',
            'end_date.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
            'description.max' => 'A descrição não pode ter mais de 5000 caracteres.',
            'media_url.url' => 'A URL da mídia deve ser uma URL válida.',
            'media_url.max' => 'A URL da mídia não pode ter mais de 500 caracteres.',
            'extra_data.array' => 'Os dados extras devem ser um array.',
            'order_priority.integer' => 'A prioridade de ordenação deve ser um número inteiro.',
            'order_priority.min' => 'A prioridade de ordenação deve ser maior ou igual a 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Se start_date foi atualizado, validar end_date em relação ao novo start_date
        if ($this->has('start_date') && $this->has('end_date')) {
            $this->merge([
                'end_date' => $this->end_date,
            ]);
        }
    }
}
