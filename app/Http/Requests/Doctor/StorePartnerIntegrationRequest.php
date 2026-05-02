<?php

namespace App\Http\Requests\Doctor;

use App\Models\PartnerIntegration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isReceiveOnly = $this->input('integration_mode') === 'receive_only';

        return [
            'partner_name' => ['required', 'string', 'max:255'],
            'partner_slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('partner_integrations', 'slug'),
            ],
            'partner_type' => ['sometimes', 'string', Rule::in([
                PartnerIntegration::TYPE_LABORATORY,
                PartnerIntegration::TYPE_PHARMACY,
                PartnerIntegration::TYPE_HOSPITAL,
                PartnerIntegration::TYPE_INSURANCE,
            ])],
            'integration_mode' => ['required', 'string', Rule::in(['full', 'receive_only'])],
            'base_url' => [$isReceiveOnly ? 'nullable' : 'required', 'url', 'max:500'],
            'fhir_version' => ['sometimes', 'string', Rule::in(['R4'])],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'auth_method' => [$isReceiveOnly ? 'nullable' : 'required', 'nullable', 'string', Rule::in(['oauth2', 'api_key', 'bearer', 'certificate'])],
            'client_id' => ['nullable', 'string', 'max:500', 'required_if:auth_method,oauth2,api_key'],
            'client_secret' => ['nullable', 'string', 'max:500', 'required_if:auth_method,oauth2'],
            'bearer_token' => ['nullable', 'string', 'max:1000', 'required_if:auth_method,bearer'],
            'perm_send_orders' => ['sometimes', 'boolean'],
            'perm_receive_results' => ['sometimes', 'boolean'],
            'perm_webhook' => ['sometimes', 'boolean'],
            'perm_patient_data' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'partner_slug.regex' => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'partner_slug.unique' => 'Já existe um parceiro com esse identificador.',
            'base_url.url' => 'A URL base deve ser uma URL válida.',
            'fhir_version.in' => 'A versão FHIR suportada no momento é somente R4.',
            'client_id.required_if' => 'Informe a credencial de acesso para o método de autenticação selecionado.',
            'client_secret.required_if' => 'Informe o client secret para autenticação OAuth2.',
            'bearer_token.required_if' => 'Informe o bearer token para autenticação por token.',
        ];
    }
}
