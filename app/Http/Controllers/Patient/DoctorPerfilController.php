<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\ServiceLocation;
use App\Services\Doctor\ScheduleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DoctorPerfilController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    public function index(Request $request): Response
    {
        $doctorId = $request->get('doctor_id');

        if (!$doctorId) {
            abort(404, 'Médico não encontrado');
        }

        $doctor = Doctor::with(['user', 'specializations', 'serviceLocations'])
            ->findOrFail($doctorId);

        // Verificar se o médico está ativo
        if ($doctor->status !== Doctor::STATUS_ACTIVE) {
            abort(404, 'Médico não encontrado');
        }

        // Médicos devem configurar sua própria disponibilidade

        $user = $doctor->user;

        // Carregar timeline events (apenas públicos)
        // Mostrar todos os tipos de eventos públicos: education, course, certificate e project
        $timelineEvents = $user->timelineEvents()
            ->where('is_public', true)
            ->ordered()
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'type_label' => $event->type_label,
                    'title' => $event->title,
                    'subtitle' => $event->subtitle,
                    'start_date' => $event->start_date->format('Y-m-d'),
                    'end_date' => $event->end_date?->format('Y-m-d'),
                    'description' => $event->description,
                    'media_url' => $event->media_url,
                    'degree_type' => $event->degree_type?->value,
                    'is_public' => $event->is_public,
                    'extra_data' => $event->extra_data,
                    'order_priority' => $event->order_priority,
                    'formatted_start_date' => $event->formatted_start_date,
                    'formatted_end_date' => $event->formatted_end_date,
                    'date_range' => $event->date_range,
                    'duration' => $event->duration,
                    'is_in_progress' => $event->is_in_progress,
                ];
            })
            ->toArray();

        // Formatar idiomas
        $languages = $doctor->language ?? [];
        $languagesFormatted = is_array($languages) 
            ? implode(', ', $languages) 
            : 'Português';

        // Preparar especialidades
        $specialties = $doctor->specializations->pluck('name')->toArray();

        // Buscar serviceLocations ativos para determinar modalidades
        $serviceLocations = $doctor->serviceLocations()
            ->where('is_active', true)
            ->get();

        // Determinar modalidades baseado nos tipos de serviceLocations
        $modalities = [];
        $hasOnlineService = false;
        $hasPresencialService = false;

        foreach ($serviceLocations as $location) {
            if ($location->type === ServiceLocation::TYPE_TELECONSULTATION) {
                $hasOnlineService = true;
                if (!in_array('Online', $modalities)) {
                    $modalities[] = 'Online';
                }
            } else {
                // Tipos presencial: 'office', 'hospital', 'clinic'
                $hasPresencialService = true;
                if (!in_array('Presencial', $modalities)) {
                    $modalities[] = 'Presencial';
                }
            }
        }

        // Buscar datas disponíveis para os próximos 30 dias
        $availableDates = $this->getAvailableDates($doctor);

        return Inertia::render('Patient/DoctorPerfil', [
            'doctor' => [
                'id' => $doctor->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->getAvatarUrl(),
                'avatar_thumbnail' => $user->getAvatarUrl(true),
                'crm' => $doctor->crm,
                'biography' => $doctor->biography,
                'languages' => $languagesFormatted,
                'consultation_fee' => $doctor->consultation_fee,
                'consultation_fee_formatted' => $doctor->formatted_consultation_fee,
                'specialties' => $specialties,
                'primary_specialty' => $specialties[0] ?? 'Médico',
                'has_online_service' => $hasOnlineService,
                'has_presencial_service' => $hasPresencialService,
                'modalities' => $modalities,
                'status' => $doctor->status,
                'timeline_events' => $timelineEvents,
                'timeline_completed' => $user->timeline_completed ?? false,
                'available_dates' => $availableDates,
            ],
        ]);
    }

    /**
     * Calcular datas disponíveis para os próximos 30 dias
     */
    private function getAvailableDates(Doctor $doctor): array
    {
        $now = Carbon::now();
        $startDate = $now->copy()->startOfDay();
        $windowDays = (int) config('telemedicine.availability.timeline_window_days', 30);
        $endDate = $now->copy()->addDays($windowDays)->endOfDay();
        
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
                        'formatted_date' => $currentDate->format('d/m/Y'),
                        'day_of_week' => $currentDate->format('l'),
                        'day_of_week_label' => $this->getDayLabel($currentDate),
                        'available_slots' => $timeSlots,
                    ];
                }
            }
            
            $currentDate->addDay();
        }
        
        return $availableDates;
    }

    /**
     * Obter label do dia da semana em português
     */
    private function getDayLabel(Carbon $date): string
    {
        $labels = [
            0 => 'Domingo',
            1 => 'Segunda',
            2 => 'Terça',
            3 => 'Quarta',
            4 => 'Quinta',
            5 => 'Sexta',
            6 => 'Sábado',
        ];

        $dayOfWeek = $date->dayOfWeek; // Carbon usa 0 = Domingo, 1 = Segunda, etc.
        return $labels[$dayOfWeek] ?? '';
    }
}

