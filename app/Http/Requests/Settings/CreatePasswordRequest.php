<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Somente para conta social-only (sem senha)
        return auth()->check() && is_null(auth()->user()->password);
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
