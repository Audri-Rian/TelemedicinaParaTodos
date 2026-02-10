<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    /**
     * Tamanho máximo do avatar em KB (configurado em telemedicine.uploads.avatar_max_kb).
     */
    private function maxAvatarKb(): int
    {
        return (int) config('telemedicine.uploads.avatar_max_kb', 5120);
    }

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
                'max:' . $this->maxAvatarKb(),
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
        $maxKb = $this->maxAvatarKb();
        if ($maxKb >= 1024) {
            $mb = $maxKb / 1024;
            $mbLabel = ((int) $mb === $mb) ? (string) ((int) $mb) : (string) round($mb, 1);
            $maxLabel = $mbLabel . 'MB';
        } else {
            $maxLabel = $maxKb . 'KB';
        }

        return [
            'avatar.required' => 'É necessário selecionar uma imagem.',
            'avatar.image' => 'O arquivo deve ser uma imagem válida.',
            'avatar.mimes' => 'A imagem deve ser nos formatos: JPEG, PNG ou WebP.',
            'avatar.max' => 'A imagem não pode ser maior que ' . $maxLabel . '.',
        ];
    }
}
