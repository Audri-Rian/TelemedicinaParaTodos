<?php

namespace App\Services;

use App\Contracts\MediaGatewayInterface;
use Illuminate\Support\Str;

/**
 * Implementação stub do Media Gateway.
 * Usada até a integração real com Media Gateway ou SFU estar disponível.
 * createRoom retorna um room_id fictício; destroyRoom não faz chamada externa.
 */
class MediaGatewayStub implements MediaGatewayInterface
{
    public function createRoom(string $callId): array
    {
        return [
            'room_id' => 'room_'.Str::random(16),
            'sfu_node' => config('services.media_gateway.sfu_node', 'local'),
        ];
    }

    public function destroyRoom(string $roomId): void
    {
        // Stub: nenhuma chamada externa. Na implementação real, notificar SFU/Gateway.
    }
}
