<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isDoctor() || $user->isPatient());
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'uuid', 'exists:appointments,id'],
        ];
    }
}
