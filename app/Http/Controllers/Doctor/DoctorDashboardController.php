<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Appointments;
use App\Models\Doctor;

class DoctorDashboardController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        
        // Buscar dados do médico logado
        $doctor = Doctor::with('user')->where('user_id', $user->id)->first();
        
        if (!$doctor) {
            abort(403, 'Perfil de médico não encontrado.');
        }

        // Consultas próximas (próximas 3)
        $upcomingAppointments = Appointments::with(['patient.user', 'doctor.user'])
            ->byDoctor($doctor->id)
            ->upcoming()
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'patient_name' => $appointment->patient->user->name,
                    'scheduled_at' => $appointment->scheduled_at->format('H:i'),
                    'status' => $this->translateStatus($appointment->status),
                    'status_class' => $this->getStatusClass($appointment->status),
                ];
            });

        // Estatísticas da semana
        $weeklyStats = $this->getWeeklyStats($doctor->id);
        
        // Estatísticas do mês
        $monthlyStats = $this->getMonthlyStats($doctor->id);
        
        // Dados para gráficos
        $weeklyAppointments = $this->getWeeklyAppointmentsData($doctor->id);
        $monthlyAppointments = $this->getMonthlyAppointmentsData($doctor->id);

        return Inertia::render('Dashboard', [
            'upcomingAppointments' => $upcomingAppointments,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'weeklyAppointments' => $weeklyAppointments,
            'monthlyAppointments' => $monthlyAppointments,
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

    private function getWeeklyStats($doctorId): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $total = Appointments::byDoctor($doctorId)
            ->whereBetween('scheduled_at', [$startOfWeek, $endOfWeek])
            ->count();

        return [
            'total' => $total,
            'period' => 'Esta Semana'
        ];
    }

    private function getMonthlyStats($doctorId): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $total = Appointments::byDoctor($doctorId)
            ->whereBetween('scheduled_at', [$startOfMonth, $endOfMonth])
            ->count();

        return [
            'total' => $total,
            'period' => 'Este Mês'
        ];
    }

    private function getWeeklyAppointmentsData($doctorId): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $days = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'];
        $data = [];

        foreach ($days as $index => $day) {
            $date = $startOfWeek->copy()->addDays($index);
            $count = Appointments::byDoctor($doctorId)
                ->whereDate('scheduled_at', $date)
                ->count();
            
            $data[] = [
                'day' => $day,
                'count' => $count,
                'max' => 10 // Para normalizar os gráficos
            ];
        }

        return $data;
    }

    private function getMonthlyAppointmentsData($doctorId): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $weeks = ['S1', 'S2', 'S3', 'S4'];
        $data = [];

        foreach ($weeks as $index => $week) {
            $weekStart = $startOfMonth->copy()->addWeeks($index);
            $weekEnd = $weekStart->copy()->addWeek()->subDay();
            
            $count = Appointments::byDoctor($doctorId)
                ->whereBetween('scheduled_at', [$weekStart, $weekEnd])
                ->count();
            
            $data[] = [
                'week' => $week,
                'count' => $count,
                'max' => 30 // Para normalizar os gráficos
            ];
        }

        return $data;
    }
}


