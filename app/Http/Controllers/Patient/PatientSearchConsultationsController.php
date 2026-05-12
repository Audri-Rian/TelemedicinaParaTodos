<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\SearchConsultationsRequest;
use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\ServiceLocation;
use App\Models\Specialization;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PatientSearchConsultationsController extends Controller
{
    public function index(SearchConsultationsRequest $request): Response
    {
        $patient = Auth::user()->patient;

        $filters = $request->validated();

        $doctorsQuery = Doctor::query()
            ->with(['user', 'specializations', 'serviceLocations' => fn ($query) => $query->active()])
            ->active();

        if (! empty($filters['specialization_id'])) {
            $doctorsQuery->bySpecialization($filters['specialization_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $doctorsQuery->where(function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('specializations', function ($specializationQuery) use ($search) {
                    $specializationQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if (is_numeric($filters['min_price'] ?? null)) {
            $doctorsQuery->where('consultation_fee', '>=', (float) $filters['min_price']);
        }

        if (is_numeric($filters['max_price'] ?? null)) {
            $doctorsQuery->where('consultation_fee', '<=', (float) $filters['max_price']);
        }

        if (! empty($filters['modality'])) {
            $modality = $filters['modality'];

            $doctorsQuery->whereHas('serviceLocations', function ($locationQuery) use ($modality) {
                $locationQuery->active();

                if ($modality === 'online') {
                    $locationQuery->where('type', ServiceLocation::TYPE_TELECONSULTATION);
                }

                if ($modality === 'presential') {
                    $locationQuery->whereIn('type', [
                        ServiceLocation::TYPE_OFFICE,
                        ServiceLocation::TYPE_HOSPITAL,
                        ServiceLocation::TYPE_CLINIC,
                    ]);
                }
            });
        }

        if (! empty($filters['location'])) {
            $location = $filters['location'];

            $doctorsQuery->whereHas('serviceLocations', function ($locationQuery) use ($location) {
                $locationQuery->active()
                    ->where(function ($query) use ($location) {
                        $query->where('name', 'like', "%{$location}%")
                            ->orWhere('address', 'like', "%{$location}%");
                    });
            });
        }

        $parsedDate = null;
        if (! empty($filters['date'])) {
            try {
                $parsedDate = Carbon::parse($filters['date']);
                $dayOfWeek = strtolower($parsedDate->format('l'));

                $doctorsQuery->whereNotNull("availability_schedule->{$dayOfWeek}");
            } catch (\Throwable $exception) {
                $parsedDate = null;
            }
        }

        $doctorsPerPage = (int) config('telemedicine.pagination.doctors_search_per_page', 6);
        $paginatedDoctors = $doctorsQuery
            ->orderByDesc('created_at')
            ->paginate($doctorsPerPage)
            ->withQueryString();

        // Batch query de agendamentos para evitar N+1 dentro do through()
        $bookedByDoctor = collect();
        if ($parsedDate) {
            $doctorIds = $paginatedDoctors->getCollection()->pluck('id')->all();
            $bookedByDoctor = Appointments::query()
                ->whereIn('doctor_id', $doctorIds)
                ->whereDate('scheduled_at', $parsedDate->toDateString())
                ->whereIn('status', [
                    Appointments::STATUS_SCHEDULED,
                    Appointments::STATUS_IN_PROGRESS,
                    Appointments::STATUS_RESCHEDULED,
                ])
                ->get(['doctor_id', 'scheduled_at'])
                ->groupBy('doctor_id')
                ->map(fn ($slots) => $slots->map(fn ($a) => Carbon::parse($a->scheduled_at)->format('H:i'))->all());
        }

        $availableDoctors = $paginatedDoctors->through(function (Doctor $doctor) use ($parsedDate, $bookedByDoctor) {
            $schedule = $doctor->availability_schedule ?? [];
            $availableSlotsForDay = null;

            if ($parsedDate) {
                $weekday = strtolower($parsedDate->format('l'));
                $daySchedule = data_get($schedule, $weekday);
                $availableSlotsForDay = data_get($daySchedule, 'slots', []);

                if (! empty($availableSlotsForDay)) {
                    $bookedSlots = $bookedByDoctor->get($doctor->id, []);
                    $availableSlotsForDay = array_values(array_diff($availableSlotsForDay, $bookedSlots));
                }
            }

            return [
                'id' => $doctor->id,
                'crm' => $doctor->crm,
                'status' => $doctor->status,
                'consultation_fee' => $doctor->consultation_fee,
                'availability_schedule' => $schedule,
                'available_slots_for_day' => $availableSlotsForDay,
                'user' => [
                    'name' => $doctor->user->name,
                    'avatar' => $doctor->user->avatar ?? null,
                ],
                'specializations' => $doctor->specializations->map(fn ($specialization) => [
                    'id' => $specialization->id,
                    'name' => $specialization->name,
                ]),
                'service_locations' => $doctor->serviceLocations->map(fn ($location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'type' => $location->type,
                    'type_label' => $location->type_label,
                    'address' => $location->address,
                ]),
            ];
        });

        $specializations = Cache::remember('specializations:list', now()->addHours(6), fn () => Specialization::query()->orderBy('name')->get(['id', 'name'])
        );

        $patientNextLimit = (int) config('telemedicine.dashboard.patient_next_consultations_limit', 10);
        $appointments = Appointments::with(['doctor.user', 'doctor.specializations'])
            ->byPatient($patient->id)
            ->whereIn('status', [Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED, Appointments::STATUS_IN_PROGRESS])
            ->where('scheduled_at', '>=', now()->subDays(1))
            ->orderByDesc('scheduled_at')
            ->limit($patientNextLimit)
            ->get()
            ->map(function (Appointments $appointment) {
                return [
                    'id' => $appointment->id,
                    'status' => $appointment->status,
                    'scheduled_at' => optional($appointment->scheduled_at)->toIso8601String(),
                    'doctor' => [
                        'id' => $appointment->doctor->id,
                        'name' => $appointment->doctor->user->name,
                        'specializations' => $appointment->doctor->specializations->pluck('name'),
                    ],
                ];
            });

        return Inertia::render('Patient/SearchConsultations', [
            'appointments' => $appointments,
            'availableDoctors' => $availableDoctors,
            'specializations' => $specializations,
            'filters' => $filters,
        ]);
    }
}
