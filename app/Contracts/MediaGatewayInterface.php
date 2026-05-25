<?php

namespace App\Contracts;

use App\DataTransferObjects\MediaRoomData;

/**
 * Integração Laravel ↔ Media Gateway (ou SFU).
 * O Gateway escolhe o SFU, cria a sala e retorna MediaRoomData tipado.
 */
interface MediaGatewayInterface
{
    /**
     * Cria uma sala no SFU para a chamada.
     *
     * @param  string  $callId  UUID da Call (negócio)
     */
    public function createRoom(string $callId): MediaRoomData;

    /**
     * Encerra a sala no SFU.
     *
     * @param  string  $roomId  ID da sala no SFU (room_id retornado por createRoom)
     */
    public function destroyRoom(string $roomId): void;
}
