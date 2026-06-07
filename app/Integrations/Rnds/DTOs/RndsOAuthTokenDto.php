<?php

namespace App\Integrations\Rnds\DTOs;

readonly class RndsOAuthTokenDto
{
    public function __construct(
        public string $accessToken,
        public int $expiresInSeconds,
    ) {}

    /**
     * @param  array<string, mixed>  $json
     */
    public static function fromOAuthJson(array $json): self
    {
        $token = $json['access_token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('RNDS: resposta de auth sem access_token.');
        }

        $expiresIn = max(60, ((int) ($json['expires_in'] ?? 300)) - 30);

        return new self($token, $expiresIn);
    }
}
