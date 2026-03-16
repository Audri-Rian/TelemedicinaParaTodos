<?php

namespace App\Contracts;

/**
 * Integração Laravel ↔ Media Gateway (ou SFU).
 * O Gateway escolhe o SFU, cria a sala e retorna roomId/sfu_node.
 */
interface MediaGatewayInterface
{
    /**
     * Cria uma sala no SFU para a chamada.
     *
     * @param  string  $callId  UUID da Call (negócio)
     * @return array{room_id: string, sfu_node: string|null}  room_id = ID da sala no SFU
     */
    public function createRoom(string $callId): array;

    /**
     * Encerra a sala no SFU.
     *
     * @param  string  $roomId  ID da sala no SFU (room_id retornado por createRoom)
     */
    public function destroyRoom(string $roomId): void;
}
