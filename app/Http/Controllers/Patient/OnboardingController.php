<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    /**
     * Marcar tour do dashboard como completo
     */
    public function completeTour(Request $request)
    {
        $user = Auth::user();

        // Determinar qual tour completar baseado no tipo de usuário
        if ($user->isDoctor()) {
            $user->update([
                'has_seen_doctor_dashboard_tour' => true,
            ]);
        } else {
            $user->update([
                'has_seen_dashboard_tour' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tour marcado como completo',
        ]);
    }

    /**
     * Pular welcome screen (marcar como visto)
     */
    public function skipWelcome(Request $request)
    {
        $user = Auth::user();

        $action = $request->input('action', 'explore');

        // 'explore' = optou por não fazer o tour; marca ambos para não exibir novamente
        // 'tour'    = quer o tour; apenas fecha o welcome, tour inicia no frontend
        if ($user->isDoctor()) {
            $fields = ['has_seen_doctor_welcome_screen' => true];
            if ($action === 'explore') {
                $fields['has_seen_doctor_dashboard_tour'] = true;
            }
            $user->update($fields);
        } else {
            $fields = ['has_seen_welcome_screen' => true];
            if ($action === 'explore') {
                $fields['has_seen_dashboard_tour'] = true;
            }
            $user->update($fields);
        }

        return response()->json([
            'success' => true,
            'message' => 'Welcome screen marcado como visto',
            'action' => $action,
        ]);
    }
}
