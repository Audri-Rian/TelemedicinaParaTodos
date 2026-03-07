<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'ErrorResponse',
    type: 'object',
    description: 'Resposta padrão de erro da API',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', description: 'Mensagem de erro'),
    ]
)]
class ErrorResponse
{
}
