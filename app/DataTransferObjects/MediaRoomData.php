<?php

namespace App\DataTransferObjects;

readonly class MediaRoomData
{
    public function __construct(
        public string $roomId,
        public ?string $sfuNode = null,
        public ?string $mediaWsUrl = null,
    ) {}
}
