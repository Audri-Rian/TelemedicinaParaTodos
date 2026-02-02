<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ICP-Brasil - Assinatura Digital
    |--------------------------------------------------------------------------
    |
    | Conformidade CFM: Resolução 2.314/2022, Art. 8º
    | Documentos médicos de telemedicina devem conter assinatura do médico.
    |
    | Provedores: Soluti, Certisign, Safeweb, etc.
    | Certificados: A1 (arquivo) ou A3 (token/smartcard)
    |
    */

    'enabled' => env('ICP_BRASIL_ENABLED', false),

    'adapter' => env('ICP_BRASIL_ADAPTER', env('APP_ENV') === 'local' ? 'development' : 'unconfigured'),

    'providers' => [
        'unconfigured' => \App\MedicalRecord\Infrastructure\ExternalServices\UnconfiguredICPBrasilAdapter::class,
        'development' => \App\MedicalRecord\Infrastructure\ExternalServices\DevelopmentICPBrasilAdapter::class,
        // Implementações reais para produção:
        // 'soluti' => \App\MedicalRecord\Infrastructure\ExternalServices\SolutiICPBrasilAdapter::class,
        // 'certisign' => \App\MedicalRecord\Infrastructure\ExternalServices\CertisignICPBrasilAdapter::class,
    ],

];
