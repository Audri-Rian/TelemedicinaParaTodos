<?php

namespace App\Http\Requests\Patient;

use App\Models\Specialization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchConsultationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isPatient();
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'specialization_id' => ['nullable', 'uuid', Rule::exists(Specialization::class, 'id')],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'gte:min_price'],
            'modality' => ['nullable', 'string', Rule::in(['online', 'presential'])],
            'location' => ['nullable', 'string', 'max:100'],
        ];
    }
}
