<?php

namespace App\Http\Controllers;

use App\Services\SfuTestRoomService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Página de teste do SFU integrada ao Laravel.
 * Sem autenticação e sem regras de negócio: usuário acessa o link (ex.: via QR Code),
 * entra na chamada e vê outros participantes. Logs são gerados no Laravel e no mediasoup-server.
 */
class SfuTestController extends Controller
{
    public function __construct(
        protected SfuTestRoomService $sfuTestRoom
    ) {}

    /**
     * Exibe a página de teste do SFU (controles completos).
     * Qualquer pessoa com o link pode acessar; um token JWT é gerado por acesso.
     */
    public function index(): View
    {
        Log::info('SFU_TEST_PAGE_VIEWED', [
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        $this->sfuTestRoom->ensureTestRoom();
        $token = $this->sfuTestRoom->issueTestToken();
        $sfuWsUrl = rtrim((string) config('services.media_gateway.sfu_ws_url', env('SFU_WS_URL', '')), '/');

        if ($sfuWsUrl === '') {
            throw new \RuntimeException(
                'SFU_WS_URL não configurado. Defina em .env ou config/services.php (media_gateway.sfu_ws_url).'
            );
        }

        $roomId = SfuTestRoomService::TEST_ROOM_ID;
        return view('sfu-test.index', [
            'roomId' => $roomId,
            'sfuTestConfig' => [
                'token' => $token,
                'sfuWsUrl' => $sfuWsUrl,
                'roomId' => $roomId,
            ],
        ]);
    }

    /**
     * Exibe a página de teste de carga (auto-join, câmera e mic automáticos).
     * Cada acesso gera um userId único; abrindo múltiplas abas simula N usuários simultâneos.
     */
    public function loadTest(): View
    {
        Log::info('SFU_LOAD_TEST_PAGE_VIEWED', [
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        $this->sfuTestRoom->ensureTestRoom();
        $token = $this->sfuTestRoom->issueTestToken();
        $sfuWsUrl = rtrim((string) config('services.media_gateway.sfu_ws_url', env('SFU_WS_URL', '')), '/');

        if ($sfuWsUrl === '') {
            throw new \RuntimeException(
                'SFU_WS_URL não configurado. Defina em .env ou config/services.php (media_gateway.sfu_ws_url).'
            );
        }

        $roomId = SfuTestRoomService::TEST_ROOM_ID;
        return view('sfu-test.load', [
            'roomId' => $roomId,
            'sfuTestConfig' => [
                'token' => $token,
                'sfuWsUrl' => $sfuWsUrl,
                'roomId' => $roomId,
            ],
        ]);
    }
}
