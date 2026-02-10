<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:' . config('telemedicine.uploads.avatar_max_kb', 5120),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxKb = (int) config('telemedicine.uploads.avatar_max_kb', 5120);
        $maxMb = (int) round($maxKb / 1024);
        $maxLabel = $maxMb >= 1 ? "{$maxMb}MB" : "{$maxKb}KB";

        return [
            'avatar.required' => 'É necessário selecionar uma imagem.',
            'avatar.image' => 'O arquivo deve ser uma imagem válida.',
            'avatar.mimes' => 'A imagem deve ser nos formatos: JPEG, PNG ou WebP.',
            'avatar.max' => 'A imagem não pode ser maior que ' . $maxLabel . '.',
        ];
    }
}
