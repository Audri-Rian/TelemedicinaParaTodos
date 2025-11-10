<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Appointments;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleConsultationController extends Controller
{
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
     */
    private function getAvailableDates(Doctor $doctor): array
    {
        $availableDates = [];
        $schedule = $doctor->availability_schedule ?? [];
        $now = Carbon::now();
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->addDays(30)->endOfDay();
        
        // Buscar todos os appointments do médico no período
        $existingAppointments = Appointments::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->whereIn('status', [
                Appointments::STATUS_SCHEDULED,
                Appointments::STATUS_RESCHEDULED,
                Appointments::STATUS_IN_PROGRESS
            ])
            ->get()
            ->groupBy(function($apt) {
                return $apt->scheduled_at->format('Y-m-d');
            })
            ->map(function($group) {
                return $group->map(fn($apt) => $apt->scheduled_at->format('H:i'))->toArray();
            })
            ->toArray();
        
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = strtolower($currentDate->format('l')); // monday, tuesday, etc.
            
            // Verificar se médico trabalha neste dia
            if (isset($schedule[$dayOfWeek]) && $schedule[$dayOfWeek] !== null) {
                $daySchedule = $schedule[$dayOfWeek];
                $allSlots = $daySchedule['slots'] ?? [];
                
                // Filtrar slots já ocupados
                $dateKey = $currentDate->format('Y-m-d');
                $occupiedSlots = $existingAppointments[$dateKey] ?? [];
                $availableSlots = array_filter($allSlots, function($slot) use ($occupiedSlots, $dateKey, $now) {
                    // Remover slots ocupados
                    if (in_array($slot, $occupiedSlots)) {
                        return false;
                    }
                    
                    // Se for hoje, remover apenas slots que já passaram
                    if ($dateKey === $now->format('Y-m-d')) {
                        try {
                            $slotDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateKey . ' ' . $slot);
                            // Mostrar slots que são no futuro (pelo menos 5 minutos à frente)
                            $minAllowedTime = $now->copy()->addMinutes(5);
                            return $slotDateTime->greaterThan($minAllowedTime);
                        } catch (\Exception $e) {
                            // Se houver erro ao criar a data, manter o slot
                            return true;
                        }
                    }
                    
                    return true;
                });
                
                if (!empty($availableSlots)) {
                    $availableDates[] = [
                        'date' => $dateKey,
                        'available_slots' => array_values($availableSlots),
                    ];
                }
            }
            
            $currentDate->addDay();
        }
        
        return $availableDates;
    }
}

