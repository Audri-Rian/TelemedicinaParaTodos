<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'change_reason' => ['required', 'string', 'min:10', 'max:500'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'is_private' => ['sometimes', 'boolean'],
            'category' => ['sometimes', 'nullable', Rule::in(['general', 'diagnosis', 'treatment', 'follow_up', 'other'])],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
