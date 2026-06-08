<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\ServiceLocation;
use App\Services\Doctor\ScheduleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPerfilController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    public function index(Request $request): Response
    {
        $request->validate(['doctor_id' => 'required|string|uuid']);
        $doctorId = $request->get('doctor_id');

        $doctor = Doctor::with([
            'user',
            'specializations',
            'serviceLocations' => fn ($q) => $q->where('is_active', true),
        ])
            ->withCount(['appointments as completed_appointments_count' => fn ($q) => $q->where('status', Appointments::STATUS_COMPLETED)])
            ->findOrFail($doctorId);

        // Verificar se o médico está ativo
        if ($doctor->status !== Doctor::STATUS_ACTIVE) {
            abort(404, 'Médico não encontrado');
        }

        // Médicos devem configurar sua própria disponibilidade

        $user = $doctor->user;

        $timelineEvents = Cache::remember(
            "doctor_timeline_{$user->id}_{$user->updated_at->timestamp}",
            now()->addHours(24),
            fn () => $user->timelineEvents()
                ->where('is_public', true)
                ->ordered()
                ->get()
                ->map(fn ($event) => [
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
                ])
                ->toArray()
        );

        $languages = $this->normalizeLanguages($doctor->language);
        $languagesFormatted = implode(', ', array_column($languages, 'label')) ?: 'Português';

        // Preparar especialidades
        $specialties = $doctor->specializations->pluck('name')->toArray();

        // serviceLocations já filtrados no eager load (is_active = true)
        $serviceLocations = $doctor->serviceLocations->values();

        // Determinar modalidades baseado nos tipos de serviceLocations
        $modalities = [];
        $hasOnlineService = false;
        $hasPresencialService = false;

        foreach ($serviceLocations as $location) {
            if ($location->type === ServiceLocation::TYPE_TELECONSULTATION) {
                $hasOnlineService = true;
                if (! in_array('Online', $modalities)) {
                    $modalities[] = 'Online';
                }
            } else {
                // Tipos presencial: 'office', 'hospital', 'clinic'
                $hasPresencialService = true;
                if (! in_array('Presencial', $modalities)) {
                    $modalities[] = 'Presencial';
                }
            }
        }

        // Buscar datas disponíveis para os próximos 30 dias
        $availableDates = $this->getAvailableDates($doctor);
        $relatedDoctors = $this->getRelatedDoctors($doctor);
        $consultationDurationMinutes = (int) config(
            'telemedicine.availability.slot_duration_minutes',
            config('telemedicine.display.appointment_duration_fallback_minutes', 45)
        );
        $completedAppointmentsCount = $doctor->completed_appointments_count ?? 0;

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
                'consultation_duration_minutes' => $consultationDurationMinutes,
                'specialties' => $specialties,
                'primary_specialty' => $specialties[0] ?? 'Médico',
                'has_online_service' => $hasOnlineService,
                'has_presencial_service' => $hasPresencialService,
                'modalities' => $modalities,
                'language_details' => $languages,
                'status' => $doctor->status,
                'timeline_events' => $timelineEvents,
                'timeline_completed' => $user->timeline_completed ?? false,
                'available_dates' => $availableDates,
                'service_locations' => $this->formatServiceLocations($serviceLocations),
                'completed_appointments_count' => $completedAppointmentsCount,
                'related_doctors' => $relatedDoctors,
            ],
        ]);
    }

    private function getAvailableDates(Doctor $doctor): array
    {
        $now = Carbon::now();
        $windowDays = (int) config('telemedicine.availability.timeline_window_days', 30);

        return $this->scheduleService->getAvailableDatesForRange(
            $doctor,
            $now->copy()->startOfDay(),
            $now->copy()->addDays($windowDays)->endOfDay()
        );
    }

    private function normalizeLanguages(mixed $languages): array
    {
        $values = is_array($languages) && ! empty($languages)
            ? $languages
            : ['Português'];

        return collect($values)
            ->map(function ($language) {
                $label = is_array($language)
                    ? ($language['label'] ?? $language['name'] ?? $language['code'] ?? null)
                    : $language;

                $label = is_string($label) && trim($label) !== '' ? trim($label) : 'Português';
                $normalized = mb_strtolower($label);

                return [
                    'label' => $label,
                    'level' => is_array($language) ? ($language['level'] ?? null) : null,
                    'flag' => match (true) {
                        str_contains($normalized, 'ingl') || str_contains($normalized, 'english') => 'EN',
                        str_contains($normalized, 'espan') || str_contains($normalized, 'spanish') => 'ES',
                        default => 'PT',
                    },
                ];
            })
            ->unique('label')
            ->values()
            ->all();
    }

    private function formatServiceLocations(Collection $serviceLocations): array
    {
        return $serviceLocations
            ->reject(fn (ServiceLocation $location) => $location->type === ServiceLocation::TYPE_TELECONSULTATION)
            ->map(fn (ServiceLocation $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'type' => $location->type,
                'type_label' => $location->type_label,
                'address' => $location->address,
                'phone' => $location->phone,
                'description' => $location->description,
            ])
            ->values()
            ->all();
    }

    private function getRelatedDoctors(Doctor $doctor): array
    {
        $specializationIds = $doctor->specializations->pluck('id');

        if ($specializationIds->isEmpty()) {
            return [];
        }

        $cacheKey = 'related_doctors_'.$doctor->id.'_'.implode('_', $specializationIds->all());

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($doctor, $specializationIds) {
            return Doctor::with([
                'user:id,name,avatar',
                'specializations:id,name',
                'serviceLocations' => fn ($q) => $q->where('is_active', true)->select('id', 'doctor_id', 'type'),
            ])
                ->select('id', 'user_id', 'consultation_fee', 'status')
                ->active()
                ->whereKeyNot($doctor->id)
                ->whereHas('specializations', fn ($q) => $q->whereIn('specializations.id', $specializationIds))
                ->limit(3)
                ->get()
                ->map(function (Doctor $relatedDoctor) {
                    $activeLocations = $relatedDoctor->serviceLocations;

                    return [
                        'id' => $relatedDoctor->id,
                        'name' => $relatedDoctor->user->name,
                        'avatar' => $relatedDoctor->user->getAvatarUrl(),
                        'avatar_thumbnail' => $relatedDoctor->user->getAvatarUrl(true),
                        'primary_specialty' => $relatedDoctor->specializations->first()?->name ?? 'Médico',
                        'consultation_fee' => $relatedDoctor->consultation_fee,
                        'consultation_fee_formatted' => $relatedDoctor->formatted_consultation_fee,
                        'has_online_service' => $activeLocations->contains('type', ServiceLocation::TYPE_TELECONSULTATION),
                        'has_presencial_service' => $activeLocations
                            ->reject(fn (ServiceLocation $l) => $l->type === ServiceLocation::TYPE_TELECONSULTATION)
                            ->isNotEmpty(),
                    ];
                })
                ->values()
                ->all();
        });
    }
}
