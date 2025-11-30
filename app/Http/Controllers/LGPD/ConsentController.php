<?php

namespace App\Http\Controllers\LGPD;

use App\Http\Controllers\Controller;
use App\Services\LGPDService;
use App\Models\Consent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsentController extends Controller
{
    public function __construct(
        private LGPDService $lgpdService
    ) {}

    /**
     * Exibe página de gerenciamento de consentimentos
     */
    public function index(): Response
    {
        $user = auth()->user();
        $consents = Consent::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type');

        return Inertia::render('LGPD/Consents', [
            'consents' => $consents,
            'activeConsents' => [
                'telemedicine' => $this->lgpdService->hasActiveConsent($user, Consent::TYPE_TELEMEDICINE),
                'video_recording' => $this->lgpdService->hasActiveConsent($user, Consent::TYPE_VIDEO_RECORDING),
                'data_processing' => $this->lgpdService->hasActiveConsent($user, Consent::TYPE_DATA_PROCESSING),
                'marketing' => $this->lgpdService->hasActiveConsent($user, Consent::TYPE_MARKETING),
            ],
        ]);
    }

    /**
     * Concede consentimento
     */
    public function grant(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:telemedicine,video_recording,data_processing,marketing'],
            'version' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        
        $consent = $this->lgpdService->grantConsent(
            $user,
            $request->type,
            $request->version ?? '1.0',
            $request->description,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Consentimento concedido com sucesso',
            'consent' => $consent,
        ]);
    }

    /**
     * Revoga consentimento
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:telemedicine,video_recording,data_processing,marketing'],
        ]);

        $user = auth()->user();
        
        $this->lgpdService->revokeConsent($user, $request->type);

        return response()->json([
            'message' => 'Consentimento revogado com sucesso',
        ]);
    }

    /**
     * Verifica se tem consentimento ativo
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:telemedicine,video_recording,data_processing,marketing'],
        ]);

        $user = auth()->user();
        $hasConsent = $this->lgpdService->hasActiveConsent($user, $request->type);

        return response()->json([
            'has_consent' => $hasConsent,
        ]);
    }
}
