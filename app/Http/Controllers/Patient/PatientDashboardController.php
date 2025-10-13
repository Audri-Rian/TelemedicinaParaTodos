<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Appointments;
use App\Models\Patient;

class PatientDashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        
        // Buscar dados do paciente logado
        $patient = Patient::with('user')->where('user_id', $user->id)->first();
        
        if (!$patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        // Consultas próximas (próximas 3)
        $upcomingAppointments = Appointments::with(['patient.user', 'doctor.user'])
            ->byPatient($patient->id)
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'doctor_name' => $appointment->doctor->user->name,
                    'scheduled_at' => $appointment->scheduled_at->format('d/m/Y H:i'),
                    'status' => $this->translateStatus($appointment->status),
                    'status_class' => $this->getStatusClass($appointment->status),
                ];
            });

        // Histórico de consultas (últimas 5)
        $recentAppointments = Appointments::with(['patient.user', 'doctor.user'])
            ->byPatient($patient->id)
            ->where('status', 'completed')
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'doctor_name' => $appointment->doctor->user->name,
                    'scheduled_at' => $appointment->scheduled_at->format('d/m/Y'),
                    'status' => $this->translateStatus($appointment->status),
                ];
            });

        // Estatísticas básicas
        $totalAppointments = Appointments::byPatient($patient->id)->count();
        $completedAppointments = Appointments::byPatient($patient->id)
            ->where('status', 'completed')
            ->count();

        return Inertia::render('Patient/Dashboard', [
            'upcomingAppointments' => $upcomingAppointments,
            'recentAppointments' => $recentAppointments,
            'stats' => [
                'total' => $totalAppointments,
                'completed' => $completedAppointments,
            ],
        ]);
    }

    private function translateStatus($status): string
    {
        $translations = [
            'scheduled' => 'Confirmada',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada',
            'no_show' => 'Não Compareceu',
            'rescheduled' => 'Reagendada',
        ];

        return $translations[$status] ?? $status;
    }

    private function getStatusClass($status): string
    {
        $classes = [
            'scheduled' => 'bg-green-100 text-green-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'no_show' => 'bg-orange-100 text-orange-800',
            'rescheduled' => 'bg-yellow-100 text-yellow-800',
        ];

        return $classes[$status] ?? 'bg-gray-100 text-gray-800';
    }
}


