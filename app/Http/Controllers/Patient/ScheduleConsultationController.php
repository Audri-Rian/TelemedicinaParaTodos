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

    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $doctorId = $request->get('doctor_id');

        if (! $doctorId) {
            return redirect()
                ->route('patient.search-consultations')
                ->with('error', 'Selecione um médico para agendar.');
        }

        $doctor = Doctor::with(['user', 'specializations'])->findOrFail($doctorId);

        if (! $doctor->isActive()) {
            return redirect()
                ->route('patient.search-consultations')
                ->with('error', 'Médico não está disponível para agendamento.');
        }

        $patient = auth()->user()->patient?->load('user');

        if (! $patient) {
            return redirect()
                ->route('patient.search-consultations')
                ->with('error', 'Perfil de paciente não encontrado.');
        }

        // Médicos devem configurar sua própria disponibilidade

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
                'specializations' => $doctor->specializations->map(fn ($spec) => [
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
            'initialSelection' => [
                'date' => $request->string('date')->toString() ?: null,
                'time' => $request->string('time')->toString() ?: null,
                'type' => $request->string('type')->toString() ?: null,
            ],
        ]);
    }

    private function getAvailableDates(Doctor $doctor): array
    {
        $now = Carbon::now();
        $windowDays = (int) config('telemedicine.availability.timeline_window_days', 30);

        $dates = $this->scheduleService->getAvailableDatesForRange(
            $doctor,
            $now->copy()->startOfDay(),
            $now->copy()->addDays($windowDays)->endOfDay()
        );

        // ScheduleConsultation só precisa de date + available_slots
        return array_map(fn ($d) => [
            'date' => $d['date'],
            'available_slots' => $d['available_slots'],
        ], $dates);
    }
}
