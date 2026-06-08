<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\SfuTestRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SfuCallSimulatorController extends Controller
{
    private const SIMULATION_STATE_CACHE_KEY = 'dev:sfu-call-simulator:state';

    public function __construct(
        protected SfuTestRoomService $sfuTestRoom
    ) {}

    public function index(): Response
    {
        return Inertia::render('Dev/SfuCallSimulator', [
            'simulation' => [
                'room_id' => SfuTestRoomService::TEST_ROOM_ID,
                'sfu_ws_url' => config('services.media_gateway.sfu_ws_url'),
                'sfu_node' => config('services.media_gateway.sfu_node'),
            ],
        ]);
    }

    public function session(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['doctor', 'patient'])],
        ]);

        $this->sfuTestRoom->ensureTestRoom();
        $this->markSimulationStarted();

        $role = $validated['role'];
        $userId = sprintf('sim_%s_%s', $role, str_replace('.', '', uniqid('', true)));
        $token = $this->sfuTestRoom->issueTestToken($role, $userId);

        Log::info('SFU_CALL_SIMULATION_SESSION_CREATED', [
            'role' => $role,
            'room_id' => SfuTestRoomService::TEST_ROOM_ID,
            'test_user_id' => $userId,
            'auth_user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'data' => [
                'call_id' => SfuTestRoomService::TEST_CALL_ID,
                'room_id' => SfuTestRoomService::TEST_ROOM_ID,
                'role' => $role,
                'token' => $token,
                'sfu_ws_url' => config('services.media_gateway.sfu_ws_url'),
                'sfu_node' => config('services.media_gateway.sfu_node'),
            ],
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'data' => $this->simulationState(),
        ]);
    }

    public function reset(): JsonResponse
    {
        Cache::forget(self::SIMULATION_STATE_CACHE_KEY);

        return response()->json([
            'data' => $this->simulationState(),
        ]);
    }

    protected function markSimulationStarted(): void
    {
        Cache::put(self::SIMULATION_STATE_CACHE_KEY, [
            'active' => true,
            'call_id' => SfuTestRoomService::TEST_CALL_ID,
            'room_id' => SfuTestRoomService::TEST_ROOM_ID,
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(2));
    }

    protected function simulationState(): array
    {
        return Cache::get(self::SIMULATION_STATE_CACHE_KEY, [
            'active' => false,
            'call_id' => SfuTestRoomService::TEST_CALL_ID,
            'room_id' => SfuTestRoomService::TEST_ROOM_ID,
            'started_at' => null,
        ]);
    }
}
