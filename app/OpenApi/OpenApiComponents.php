<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Components(
    securitySchemes: [
        new OA\SecurityScheme(
            securityScheme: 'cookieAuth',
            type: 'apiKey',
            name: 'laravel_session',
            in: 'cookie',
            description: 'Autenticação por sessão Laravel (cookie). Usado pelos endpoints internos.'
        ),
        new OA\SecurityScheme(
            securityScheme: 'bearerAuth',
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT',
            description: 'Token Bearer para API pública (interoperabilidade).'
        ),
    ]
)]
class OpenApiComponents
{
}
