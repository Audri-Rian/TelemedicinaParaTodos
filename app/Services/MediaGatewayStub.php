<?php

namespace App\Services;

use App\Contracts\MediaGatewayInterface;
use App\DataTransferObjects\MediaRoomData;
use Illuminate\Support\Facades\Log;

/**
 * Implementação stub do Media Gateway — sem SFU real.
 * room_id determinístico baseado em callId; mediaWsUrl sempre null.
 */
class MediaGatewayStub implements MediaGatewayInterface
{
    public function createRoom(string $callId): MediaRoomData
    {
        $roomId = 'stub_'.$callId;

        Log::debug('MediaGatewayStub::createRoom', [
            'call_id' => $callId,
            'room_id' => $roomId,
        ]);

        return new MediaRoomData(
            roomId: $roomId,
            sfuNode: 'local',
            mediaWsUrl: null,
        );
    }

    public function destroyRoom(string $roomId): void
    {
        Log::debug('MediaGatewayStub::destroyRoom', ['room_id' => $roomId]);
    }
}
