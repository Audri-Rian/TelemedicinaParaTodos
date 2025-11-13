<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Services\Doctor\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleConsultationController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    public function index(Request $request): Response
    {
        $doctorId = $request->get('doctor_id');
        
        if (!$doctorId) {
            return redirect()
                ->route('patient.search-consultations')
                ->with('error', 'Selecione um médico para agendar.');
        }
        
        $doctor = Doctor::with(['user', 'specializations'])->findOrFail($doctorId);
        
        if (!$doctor->isActive()) {
            return redirect()
                ->route('patient.search-consultations')
                ->with('error', 'Médico não está disponível para agendamento.');
        }
        
        $patient = auth()->user()->patient;
        
        if (!$patient) {
            return redirect()
                ->route('patient.search-consultations')
                ->with('error', 'Perfil de paciente não encontrado.');
        }
        
        // Calcular horários disponíveis para os próximos 30 dias
        $availableDates = $this->getAvailableDates($doctor);
        
        return Inertia::render('Patient/ScheduleConsultation', [
            'doctor' => [
                'id' => $doctor->id,
                'user' => [
                    'name' => $doctor->user->name,
                    'email' => $doctor->user->email,
                    'avatar' => $doctor->user->avatar ?? null,
                ],
                'specializations' => $doctor->specializations->map(fn($spec) => [
                    'id' => $spec->id,
                    'name' => $spec->name,
                ]),
                'consultation_fee' => $doctor->consultation_fee,
                'crm' => $doctor->crm,
                'biography' => $doctor->biography,
            ],
            'availableDates' => $availableDates,
            'patient' => [
                'id' => $patient->id,
                'user' => [
                    'name' => $patient->user->name,
                ],
            ],
        ]);
    }
    
    /**
     * Calcular datas disponíveis para os próximos 30 dias
     * Usa o novo sistema de ScheduleService que considera:
     * - Slots recorrentes (por dia da semana)
     * - Slots específicos (por data)
     * - Datas bloqueadas
     * - Appointments já agendados
     */
    private function getAvailableDates(Doctor $doctor): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->addDays(30)->endOfDay();
        
        $availableDates = [];
        $currentDate = $startDate->copy();
        
        // Iterar por cada dia no período
        while ($currentDate <= $endDate) {
            // Usar ScheduleService que já considera datas bloqueadas e appointments
            $availability = $this->scheduleService->getAvailabilityForDate($doctor, $currentDate);
            
            // Se a data não está bloqueada e tem slots disponíveis
            if (!$availability['is_blocked'] && !empty($availability['available_slots'])) {
                // Extrair apenas os horários (strings) dos slots
                $timeSlots = array_map(function($slot) {
                    return $slot['time'] ?? null;
                }, $availability['available_slots']);
                
                // Filtrar nulls e ordenar
                $timeSlots = array_filter($timeSlots, fn($time) => $time !== null);
                $timeSlots = array_values($timeSlots);
                sort($timeSlots);
                
                if (!empty($timeSlots)) {
                    $availableDates[] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'available_slots' => $timeSlots,
                    ];
                }
            }
            
            $currentDate->addDay();
        }
        
        return $availableDates;
    }
}

