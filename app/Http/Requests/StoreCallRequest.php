<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Apenas pacientes iniciam chamadas ad-hoc; médicos não podem iniciar
        return $user !== null && $user->isPatient();
    }

    public function rules(): array
    {
        return [
            'call_type' => ['required', 'in:ad_hoc'],
            'doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
        ];
    }
}
