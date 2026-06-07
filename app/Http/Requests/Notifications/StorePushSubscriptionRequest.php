<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $keys = $this->input('keys', []);

        $this->merge([
            'public_key' => $this->input('public_key', $keys['p256dh'] ?? null),
            'auth_token' => $this->input('auth_token', $keys['auth'] ?? null),
            'content_encoding' => $this->input('content_encoding', 'aes128gcm'),
        ]);
    }

    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'url', 'max:2048'],
            'public_key' => ['required', 'string', 'max:512'],
            'auth_token' => ['required', 'string', 'max:512'],
            'content_encoding' => ['nullable', 'string', 'in:aes128gcm,aesgcm'],
        ];
    }
}
