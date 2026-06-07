<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientHistoryConsultationsController extends Controller
{
    /**
     * Display the patient's history consultations page.
     */
    public function index(Request $request): Response
    {
        $patient = Auth::user()->patient;

        if (! $patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        $query = Appointments::query()
            ->with(['doctor.user', 'doctor.specializations'])
            ->where('patient_id', $patient->id);

        $status = $request->get('status');
        $search = trim((string) $request->get('search', ''));
        $dateRange = $request->get('date_range', 'all');
        $sort = $request->get('sort', 'recent-first');

        if ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'completed') {
            $query->completed();
        } elseif ($status === 'cancelled') {
            $query->cancelled();
        } elseif ($status === 'rescheduled') {
            $query->where('status', Appointments::STATUS_RESCHEDULED);
        } elseif ($status === 'no_show') {
            $query->where('status', Appointments::STATUS_NO_SHOW);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->whereHas('doctor.user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('doctor.specializations', function ($specializationQuery) use ($search) {
                    $specializationQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($dateRange === '30d') {
            $query->where('scheduled_at', '>=', now()->subDays(30));
        } elseif ($dateRange === '90d') {
            $query->where('scheduled_at', '>=', now()->subDays(90));
        } elseif ($dateRange === 'year') {
            $query->whereYear('scheduled_at', now()->year);
        }

        if ($sort === 'soonest-first') {
            $query->orderBy('scheduled_at');
        } elseif ($sort === 'oldest-first') {
            $query->orderBy('scheduled_at');
        } else {
            $query->orderByDesc('scheduled_at');
        }

        $perPage = (int) config('telemedicine.pagination.consultations_per_page', 10);
        $appointments = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Appointments $appointment) {
                $doctor = $appointment->doctor;
                $doctorUser = $doctor->user ?? null;

                return [
                    'id' => $appointment->id,
                    'status' => $appointment->status,
                    'scheduled_at' => optional($appointment->scheduled_at)->toIso8601String(),
                    'doctor' => [
                        'id' => $doctor->id ?? '',
                        'user' => [
                            'id' => $doctorUser->id ?? '',
                            'name' => $doctorUser->name ?? 'Médico não informado',
                            'avatar' => $doctorUser->avatar ?? null,
                        ],
                        'specializations' => $doctor->specializations
                            ? $doctor->specializations->map(fn ($spec) => [
                                'id' => $spec->id,
                                'name' => $spec->name,
                            ])->toArray()
                            : [],
                    ],
                ];
            });

        $statsRow = Appointments::where('patient_id', $patient->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('scheduled', 'rescheduled') AND scheduled_at > NOW() THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'upcoming' => (int) ($statsRow->upcoming ?? 0),
            'completed' => (int) ($statsRow->completed ?? 0),
            'cancelled' => (int) ($statsRow->cancelled ?? 0),
        ];

        return Inertia::render('Patient/HistoryConsultations', [
            'appointments' => $appointments,
            'stats' => $stats,
            'filters' => [
                'status' => $status ?? 'all',
                'search' => $search,
                'date_range' => $dateRange,
                'sort' => $sort,
            ],
        ]);
    }
}
