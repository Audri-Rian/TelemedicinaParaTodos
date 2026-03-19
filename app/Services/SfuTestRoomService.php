<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para a página de teste do SFU integrada ao Laravel.
 * Cria/garante a sala de teste no MediaSoup e emite tokens JWT sem autenticação
 * nem regras de negócio (apenas para debug e validação da infraestrutura).
 */
class SfuTestRoomService
{
    public const TEST_CALL_ID = 'sfu_test';

    public const TEST_ROOM_ID = 'sfu_test_room';

    /**
     * Garante que a sala de teste existe no SFU (cria se necessário).
     * Usa a mesma API HTTP do MediaSoup com roomId fixo para todos os usuários de teste.
     */
    public function ensureTestRoom(): void
    {
        $baseUrl = rtrim((string) config('services.media_gateway.sfu_http_url'), '/');
        $apiSecret = (string) config('services.media_gateway.api_secret');

        if ($baseUrl === '' || $apiSecret === '') {
            throw new \RuntimeException(
                'SFU não configurado para teste. Defina SFU_HTTP_URL e SFU_API_SECRET (e use MediaGatewayHttp).'
            );
        }

        $response = Http::withToken($apiSecret)
            ->acceptJson()
            ->asJson()
            ->timeout(5)
            ->post("{$baseUrl}/rooms", [
                'callId' => self::TEST_CALL_ID,
                'roomId' => self::TEST_ROOM_ID,
            ]);

        $response->throw();

        $data = $response->json();
        $roomId = $data['room_id'] ?? self::TEST_ROOM_ID;

        Log::info('SFU_TEST_ROOM_READY', [
            'room_id' => $roomId,
            'sfu_node' => $data['sfu_node'] ?? null,
        ]);
    }

    /**
     * Emite um token JWT para um usuário de teste (sem usuário autenticado).
     * Cada chamada gera um userId único para permitir múltiplos participantes na mesma sala.
     */
    public function issueTestToken(): string
    {
        $secret = config('services.media_gateway.jwt_secret', env('SFU_JWT_SECRET'));

        if (! $secret) {
            throw new \RuntimeException(
                'JWT secret não configurado. Defina SFU_JWT_SECRET ou services.media_gateway.jwt_secret.'
            );
        }

        $ttlMinutes = (int) config('telemedicine.video_call.token_ttl_minutes', 60);
        $now = time();
        $exp = $now + ($ttlMinutes * 60);
        $userId = 'test_'.uniqid('', true);

        $payload = [
            'callId' => self::TEST_CALL_ID,
            'roomId' => self::TEST_ROOM_ID,
            'userId' => $userId,
            'role' => 'user',
            'iat' => $now,
            'exp' => $exp,
        ];

        $token = $this->signJwt($payload, $secret);

        Log::info('SFU_TEST_TOKEN_ISSUED', [
            'room_id' => self::TEST_ROOM_ID,
            'user_id' => $userId,
            'expires_at' => $exp,
        ]);

        return $token;
    }

    /**
     * Assina um JWT com HS256.
     */
    protected function signJwt(array $payload, string $secret): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
