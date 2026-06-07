<?php

namespace App\Services;

use App\Contracts\MediaGatewayInterface;
use App\DataTransferObjects\MediaRoomData;
use Illuminate\Support\Facades\Http;

class MediaGatewayHttp implements MediaGatewayInterface
{
    private string $baseUrl;

    private string $apiSecret;

    public function __construct()
    {
        $baseUrl = rtrim((string) config('services.media_gateway.sfu_http_url'), '/');
        $apiSecret = (string) config('services.media_gateway.api_secret');

        if ($baseUrl === '' || $apiSecret === '') {
            throw new \RuntimeException('SFU_HTTP_URL ou SFU_API_SECRET não configurados.');
        }

        $this->baseUrl = $baseUrl;
        $this->apiSecret = $apiSecret;
    }

    public function createRoom(string $callId): MediaRoomData
    {
        $response = Http::withToken($this->apiSecret)
            ->acceptJson()
            ->asJson()
            ->timeout(5)
            ->post("{$this->baseUrl}/rooms", [
                'callId' => $callId,
            ]);

        $response->throw();

        /** @var array{room_id?: string, sfu_node?: string|null, media_ws_url?: string|null} $data */
        $data = $response->json();

        if (empty($data['room_id'])) {
            throw new \RuntimeException('Resposta inválida do SFU: room_id ausente.');
        }

        return new MediaRoomData(
            roomId: (string) $data['room_id'],
            sfuNode: isset($data['sfu_node']) ? (string) $data['sfu_node'] : null,
            mediaWsUrl: isset($data['media_ws_url']) ? (string) $data['media_ws_url'] : null,
        );
    }

    public function destroyRoom(string $roomId): void
    {
        $response = Http::withToken($this->apiSecret)
            ->acceptJson()
            ->timeout(5)
            ->delete("{$this->baseUrl}/rooms/".urlencode($roomId));

        $response->throw();
    }
}
