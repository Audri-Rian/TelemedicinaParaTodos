<?php

namespace App\Http\Requests\Doctor\MedicalRecords;

use App\MedicalRecord\Domain\ValueObjects\CID10Code;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'cid10_code' => [
                'required',
                'string',
                'max:10',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! CID10Code::isValid((string) $value)) {
                        $fail('O código CID-10 é inválido. Use o formato A00.0 a Z99.9 (ex: A00.0, B20, Z99.9).');
                    }
                },
            ],
            'cid10_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['principal', 'secondary'])],
        ];
    }
}


