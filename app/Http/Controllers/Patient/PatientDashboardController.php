<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Appointments;
use App\Models\Patient;
use App\Models\Doctor;

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
        $upcomingAppointments = Appointments::with(['patient.user', 'doctor.user', 'doctor.specializations'])
            ->byPatient($patient->id)
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get()
            ->map(function ($appointment) {
                $specialty = $appointment->doctor->specializations->first()?->name ?? 'Especialista';
                $months = [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                ];
                $monthName = $months[$appointment->scheduled_at->month] ?? 'Mês';
                
                return [
                    'id' => $appointment->id,
                    'doctor_name' => $appointment->doctor->user->name,
                    'doctor_specialty' => $specialty,
                    'doctor_image' => $appointment->doctor->user->avatar ?? null,
                    'scheduled_at' => $appointment->scheduled_at->format('d/m/Y H:i'),
                    'scheduled_date' => $appointment->scheduled_at->format('d') . ' de ' . $monthName,
                    'scheduled_time' => $appointment->scheduled_at->format('H:i'),
                    'duration' => '30 min',
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

        // Médicos disponíveis
        $doctors = Doctor::with(['user', 'specializations'])
            ->active()
            ->available()
            ->limit(10)
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'specialty' => $doctor->specializations->first()?->name ?? 'Especialista',
                    'image' => $doctor->user->avatar ?? null,
                ];
            });

        // Lembretes (mockados por enquanto - pode ser expandido no futuro)
        $reminders = [
            [
                'id' => '1',
                'title' => 'Tomar medicamento X',
                'time' => 'Próxima dose às 18:00',
                'icon' => 'medication',
            ],
            [
                'id' => '2',
                'title' => 'Jejum para exame',
                'message' => 'Lembre-se do jejum de 8h amanhã',
                'icon' => 'exam',
            ],
        ];

        // Dicas de saúde (mockadas por enquanto)
        $healthTips = [
            [
                'id' => '1',
                'title' => 'Importância da hidratação diária',
                'description' => 'Descubra os benefícios de se manter hidratado ao longo do dia para sua saúde e bem-estar.',
            ],
        ];

        return Inertia::render('Patient/Dashboard', [
            'upcomingAppointments' => $upcomingAppointments,
            'recentAppointments' => $recentAppointments,
            'stats' => [
                'total' => $totalAppointments,
                'completed' => $completedAppointments,
            ],
            'doctors' => $doctors,
            'reminders' => $reminders,
            'healthTips' => $healthTips,
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


