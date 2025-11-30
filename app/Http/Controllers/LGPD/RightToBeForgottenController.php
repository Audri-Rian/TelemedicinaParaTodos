<?php

namespace App\Http\Controllers\LGPD;

use App\Http\Controllers\Controller;
use App\Services\LGPDService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RightToBeForgottenController extends Controller
{
    public function __construct(
        private LGPDService $lgpdService
    ) {}

    /**
     * Exibe página de direito ao esquecimento
     */
    public function index(): Response
    {
        return Inertia::render('LGPD/RightToBeForgotten');
    }

    /**
     * Processa solicitação de exclusão de dados
     */
    public function request(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
            'confirmation' => ['required', 'accepted'],
        ], [
            'password.current_password' => 'A senha informada está incorreta.',
            'confirmation.accepted' => 'Você deve confirmar que deseja excluir seus dados permanentemente.',
        ]);

        $user = Auth::user();

        try {
            // Registrar solicitação antes de excluir
            \App\Models\AuditLog::create([
                'user_id' => $user->id,
                'action' => 'request_data_deletion',
                'resource_type' => 'User',
                'resource_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'reason' => 'Solicitação de direito ao esquecimento',
                ],
            ]);

            // Excluir dados
            $this->lgpdService->deleteUserData($user);

            // Fazer logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Seus dados foram excluídos com sucesso.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao excluir dados do usuário: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erro ao processar solicitação. Tente novamente mais tarde.',
            ], 500);
        }
    }
}
