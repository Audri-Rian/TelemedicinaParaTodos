<?php

namespace App\Http\Requests\Doctor;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()?->doctor !== null;
    }

    public function rules(): array
    {
        $isReceiveOnly = $this->input('integration_mode') === 'receive_only';
        $partnerCatalog = collect(config('integrations.partner_catalog', []));
        $catalogKeys = $partnerCatalog->pluck('key')->filter()->values()->all();
        $catalogNames = $partnerCatalog->pluck('name')->filter()->values()->all();
        $catalogTypes = $partnerCatalog->pluck('type')->filter()->values()->all();

        return [
            'partner_name' => ['required', 'string', 'max:255', Rule::in($catalogNames)],
            'partner_slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::in($catalogKeys),
            ],
            'partner_type' => ['sometimes', 'string', Rule::in($catalogTypes)],
            'integration_mode' => ['required', 'string', Rule::in(['full', 'receive_only'])],
            'base_url' => [
                $isReceiveOnly ? 'nullable' : 'required',
                'url',
                'max:500',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $scheme = parse_url($value, PHP_URL_SCHEME);
                    if (! is_string($scheme) || strtolower($scheme) !== 'https') {
                        $fail('A URL base deve usar HTTPS.');

                        return;
                    }

                    $host = parse_url($value, PHP_URL_HOST);
                    if (! is_string($host) || $host === '') {
                        $fail('A URL base deve conter um host válido.');

                        return;
                    }

                    $normalizedHost = strtolower($host);
                    if (in_array($normalizedHost, ['localhost', '127.0.0.1', '::1'], true)) {
                        $fail('A URL base não pode apontar para localhost.');

                        return;
                    }

                    if (filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
                        $isPublicIp = filter_var(
                            $normalizedHost,
                            FILTER_VALIDATE_IP,
                            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                        ) !== false;

                        if (! $isPublicIp) {
                            $fail('A URL base não pode apontar para faixas IP privadas ou reservadas.');

                            return;
                        }
                    }
                },
            ],
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
            'partner_slug.in' => 'Selecione um parceiro válido do catálogo disponível.',
            'partner_name.in' => 'O nome do parceiro deve corresponder ao catálogo disponível.',
            'partner_type.in' => 'O tipo do parceiro deve corresponder ao catálogo disponível.',
            'base_url.url' => 'A URL base deve ser uma URL válida.',
            'base_url.required' => 'A URL base é obrigatória para integrações completas.',
            'fhir_version.in' => 'A versão FHIR suportada no momento é somente R4.',
            'client_id.required_if' => 'Informe a credencial de acesso para o método de autenticação selecionado.',
            'client_secret.required_if' => 'Informe o client secret para autenticação OAuth2.',
            'bearer_token.required_if' => 'Informe o bearer token para autenticação por token.',
        ];
    }
}
