<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\Specialization;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientSearchConsultationsController extends Controller
{
    public function index(Request $request): Response
    {
        $patient = Auth::user()->patient;

        $filters = $request->only(['search', 'specialization_id', 'date']);

        $doctorsQuery = Doctor::query()
            ->with(['user', 'specializations'])
            ->active();

        if (!empty($filters['specialization_id'])) {
            $doctorsQuery->bySpecialization($filters['specialization_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $doctorsQuery->where(function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('specializations', function ($specializationQuery) use ($search) {
                    $specializationQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        $parsedDate = null;
        if (!empty($filters['date'])) {
            try {
                $parsedDate = Carbon::parse($filters['date']);
                $dayOfWeek = strtolower($parsedDate->format('l'));

                $doctorsQuery->whereNotNull("availability_schedule->{$dayOfWeek}");
            } catch (\Throwable $exception) {
                $parsedDate = null;
            }
        }

        $doctorsPerPage = (int) config('telemedicine.pagination.doctors_search_per_page', 6);
        $availableDoctors = $doctorsQuery
            ->orderByDesc('created_at')
            ->paginate($doctorsPerPage)
            ->withQueryString()
            ->through(function (Doctor $doctor) use ($parsedDate) {
                $schedule = $doctor->availability_schedule ?? [];
                $availableSlotsForDay = null;

                if ($parsedDate) {
                    $weekday = strtolower($parsedDate->format('l'));
                    $daySchedule = data_get($schedule, $weekday);
                    $availableSlotsForDay = data_get($daySchedule, 'slots', []);

                    if (!empty($availableSlotsForDay)) {
                        $bookedSlots = Appointments::query()
                            ->where('doctor_id', $doctor->id)
                            ->whereDate('scheduled_at', $parsedDate->toDateString())
                            ->whereIn('status', [
                                Appointments::STATUS_SCHEDULED,
                                Appointments::STATUS_IN_PROGRESS,
                                Appointments::STATUS_RESCHEDULED,
                            ])
                            ->pluck('scheduled_at')
                            ->map(fn (Carbon $dateTime) => $dateTime->format('H:i'))
                            ->all();

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
                        'email' => $doctor->user->email,
                        'avatar' => $doctor->user->avatar ?? null,
                    ],
                    'specializations' => $doctor->specializations->map(fn ($specialization) => [
                        'id' => $specialization->id,
                        'name' => $specialization->name,
                    ]),
                ];
            });

        $specializations = Specialization::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $patientNextLimit = (int) config('telemedicine.dashboard.patient_next_consultations_limit', 10);
        $appointments = Appointments::with(['doctor.user', 'doctor.specializations'])
            ->byPatient($patient->id)
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

