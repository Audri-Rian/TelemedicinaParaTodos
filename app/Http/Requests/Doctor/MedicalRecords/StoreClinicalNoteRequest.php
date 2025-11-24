<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_private' => ['sometimes', 'boolean'],
            'category' => ['nullable', Rule::in(['general', 'diagnosis', 'treatment', 'follow_up', 'other'])],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'parent_id' => ['nullable', 'exists:clinical_notes,id'],
        ];
    }
}


