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

        $nextAppointmentsLimit = (int) config('telemedicine.dashboard.next_appointments_limit', 3);
        // Consultas próximas
        $upcomingAppointments = Appointments::with(['patient.user', 'doctor.user', 'doctor.specializations'])
            ->byPatient($patient->id)
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit($nextAppointmentsLimit)
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
                    'duration' => config('telemedicine.display.appointment_duration_fallback_minutes', 45) . ' min',
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
        $patientNextLimit = (int) config('telemedicine.dashboard.patient_next_consultations_limit', 10);
        $doctors = Doctor::with(['user', 'specializations'])
            ->active()
            ->available()
            ->limit($patientNextLimit)
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'specialty' => $doctor->specializations->first()?->name ?? 'Especialista',
                    'image' => $doctor->user->avatar ?? null,
                ];
            });

        // Lembretes - array vazio até implementação futura
        $reminders = [];

        // Dicas de saúde - array vazio até implementação futura
        $healthTips = [];

        // Dados do tour e onboarding
        // Para testes: removemos a verificação de email temporariamente
        // Em produção, descomente as linhas abaixo e remova as linhas de teste
        $showWelcome = !$user->has_seen_welcome_screen; // && $user->email_verified_at;
        $showTour = !$user->has_seen_dashboard_tour && $user->has_seen_welcome_screen; // && $user->email_verified_at;

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
            'onboarding' => [
                'showWelcome' => $showWelcome,
                'showTour' => $showTour,
                'userName' => $user->name,
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


