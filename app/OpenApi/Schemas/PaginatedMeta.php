<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedMeta',
    title: 'PaginatedMeta',
    type: 'object',
    description: 'Metadados de resposta paginada',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'last_page', type: 'integer'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
    ]
)]
class PaginatedMeta
{
}
