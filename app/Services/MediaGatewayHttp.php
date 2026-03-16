<?php

namespace App\Services;

use App\Contracts\MediaGatewayInterface;
use Illuminate\Support\Facades\Http;

class MediaGatewayHttp implements MediaGatewayInterface
{
    public function createRoom(string $callId): array
    {
        $baseUrl = rtrim((string) config('services.media_gateway.sfu_http_url'), '/');
        $apiSecret = (string) config('services.media_gateway.api_secret');

        if ($baseUrl === '' || $apiSecret === '') {
            throw new \RuntimeException('SFU_HTTP_URL ou SFU_API_SECRET não configurados.');
        }

        $response = Http::withToken($apiSecret)
            ->acceptJson()
            ->asJson()
            ->timeout(5)
            ->post("{$baseUrl}/rooms", [
                'callId' => $callId,
            ]);

        $response->throw();

        /** @var array{room_id?: string, sfu_node?: string|null} $data */
        $data = $response->json();

        if (empty($data['room_id'])) {
            throw new \RuntimeException('Resposta inválida do SFU: room_id ausente.');
        }

        return [
            'room_id' => (string) $data['room_id'],
            'sfu_node' => isset($data['sfu_node']) ? (string) $data['sfu_node'] : null,
        ];
    }

    public function destroyRoom(string $roomId): void
    {
        $baseUrl = rtrim((string) config('services.media_gateway.sfu_http_url'), '/');
        $apiSecret = (string) config('services.media_gateway.api_secret');

        if ($baseUrl === '' || $apiSecret === '') {
            throw new \RuntimeException('SFU_HTTP_URL ou SFU_API_SECRET não configurados.');
        }

        $response = Http::withToken($apiSecret)
            ->acceptJson()
            ->timeout(5)
            ->delete("{$baseUrl}/rooms/{$roomId}");

        $response->throw();
    }
}

