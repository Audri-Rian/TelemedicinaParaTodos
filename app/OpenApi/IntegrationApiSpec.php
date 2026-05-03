<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

// ---------------------------------------------------------------------------
// OAuth2 Token
// ---------------------------------------------------------------------------

#[OA\Post(
    path: '/api/v1/public/oauth/token',
    summary: 'Emite token de acesso para parceiro externo',
    description: 'OAuth2 Client Credentials Grant. Parceiro envia client_id + client_secret e recebe um Bearer token com TTL de 1 hora.',
    tags: ['API pública'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['grant_type', 'client_id', 'client_secret'],
            properties: [
                new OA\Property(property: 'grant_type', type: 'string', enum: ['client_credentials'], example: 'client_credentials'),
                new OA\Property(property: 'client_id', type: 'string', example: 'lab-hermes-prod'),
                new OA\Property(property: 'client_secret', type: 'string', example: 's3cr3t'),
                new OA\Property(property: 'scope', type: 'string', example: 'lab:read lab:write', description: 'Espaço-separado. Omitir para receber todos os scopes permitidos.'),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Token emitido com sucesso',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'access_token', type: 'string'),
                    new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                    new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    new OA\Property(property: 'scope', type: 'string', example: 'lab:read lab:write'),
                ]
            )
        ),
        new OA\Response(response: 400, description: 'invalid_scope — scope solicitado não permitido'),
        new OA\Response(response: 401, description: 'invalid_client — credenciais inválidas'),
        new OA\Response(response: 403, description: 'Parceiro inativo'),
    ]
)]
// ---------------------------------------------------------------------------
// Lab Orders
// ---------------------------------------------------------------------------
#[OA\Get(
    path: '/api/v1/public/lab/{partnerSlug}/orders',
    summary: 'Lista pedidos de exame pendentes para o laboratório',
    description: 'Retorna um FHIR Bundle (searchset) com ServiceRequests ainda sem resultado registrado. Requer scope `lab:read`.',
    tags: ['API pública'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'partnerSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'lab-hermes'),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 50)),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'FHIR Bundle com pedidos pendentes',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'resourceType', type: 'string', example: 'Bundle'),
                    new OA\Property(property: 'type', type: 'string', example: 'searchset'),
                    new OA\Property(property: 'total', type: 'integer', example: 3),
                    new OA\Property(property: 'entry', type: 'array', items: new OA\Items(type: 'object')),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Token inválido ou expirado'),
        new OA\Response(response: 403, description: 'Parceiro inativo ou scope insuficiente'),
        new OA\Response(response: 404, description: 'Laboratório não encontrado'),
    ]
)]
// ---------------------------------------------------------------------------
// Webhook — resultado de exame
// ---------------------------------------------------------------------------
#[OA\Post(
    path: '/api/v1/public/webhooks/lab/{partnerSlug}',
    summary: 'Recebe resultado de exame do laboratório (webhook)',
    description: 'Laboratório envia DiagnosticReport FHIR R4 (recurso direto ou Bundle). Autenticado via HMAC-SHA256 nos headers `X-Webhook-Signature` e `X-Webhook-Timestamp`.',
    tags: ['API pública'],
    parameters: [
        new OA\Parameter(name: 'partnerSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'lab-hermes'),
        new OA\Parameter(name: 'X-Webhook-Signature', in: 'header', required: true, schema: new OA\Schema(type: 'string'), example: 'sha256=abc123...'),
        new OA\Parameter(name: 'X-Webhook-Timestamp', in: 'header', required: true, schema: new OA\Schema(type: 'string'), example: '1746300000'),
        new OA\Parameter(name: 'X-Idempotency-Key', in: 'header', required: false, schema: new OA\Schema(type: 'string'), description: 'Chave de idempotência. Se omitida, usa o id do DiagnosticReport.'),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            description: 'DiagnosticReport FHIR R4 ou Bundle contendo DiagnosticReport',
            example: ['resourceType' => 'DiagnosticReport', 'id' => 'dr-001', 'status' => 'final']
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Resultado processado',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'processed'),
                    new OA\Property(property: 'examination_id', type: 'string', format: 'uuid'),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Assinatura HMAC inválida ou timestamp expirado'),
        new OA\Response(response: 404, description: 'Parceiro ou exame não encontrado'),
        new OA\Response(response: 500, description: 'Erro ao processar resultado'),
    ]
)]
// ---------------------------------------------------------------------------
// Health Check
// ---------------------------------------------------------------------------
#[OA\Get(
    path: '/api/v1/public/health/{partnerSlug}',
    summary: 'Verifica status da integração com o parceiro',
    description: 'Retorna status do parceiro, circuit breaker e resultado do health check do adapter.',
    tags: ['API pública'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'partnerSlug', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'lab-hermes'),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Status da integração',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'partner', type: 'string'),
                    new OA\Property(property: 'status', type: 'string', enum: ['ok', 'degraded', 'error']),
                    new OA\Property(property: 'circuit_breaker', type: 'string', enum: ['closed', 'open', 'half_open']),
                    new OA\Property(property: 'adapter_check', type: 'object'),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Token inválido'),
        new OA\Response(response: 404, description: 'Parceiro não encontrado'),
    ]
)]
class IntegrationApiSpec {}
