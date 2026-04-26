<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientDetailsController extends Controller
{
    public function show(Request $request, Patient $patient): Response
    {
        $this->authorize('view', $patient);

        $doctor = $request->user()->doctor;
        $patient->load('user');

        $totalConsultations = Appointments::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->where('status', Appointments::STATUS_COMPLETED)
            ->count();

        $consultations = Appointments::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('scheduled_at')
            ->limit(10)
            ->get()
            ->map(fn (Appointments $a) => [
                'id' => $a->id,
                'date' => $a->scheduled_at?->format('d/m/Y'),
                'time' => $a->scheduled_at?->format('H:i'),
                'status' => $this->statusLabel($a->status),
                'statusClass' => $this->statusClass($a->status),
                'notes' => $a->notes,
            ])
            ->values()
            ->all();

        return Inertia::render('Doctor/PatientDetails', [
            'patientId' => $patient->id,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->user->name ?? '',
                'avatar' => $patient->user->avatar ?? null,
                'email' => $patient->user->email ?? '',
                'phone' => $patient->phone_number,
                'birthDate' => $patient->date_of_birth?->format('d/m/Y'),
                'age' => $patient->age,
                'address' => null,
                'cpf' => $patient->cpf,
                'emergencyContact' => $patient->emergency_contact
                    ? trim($patient->emergency_contact.' - '.($patient->emergency_phone ?? ''), ' -')
                    : null,
                'medicalHistory' => $this->splitLines($patient->medical_history),
                'allergies' => $patient->allergies,
                'currentMedications' => $patient->current_medications,
                'bloodType' => $patient->blood_type,
                'lastConsultation' => $patient->last_consultation_at?->format('d/m/Y'),
                'totalConsultations' => $totalConsultations,
            ],
            'consultations' => $consultations,
        ]);
    }

    private function splitLines(?string $text): array
    {
        if (! $text) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[\r\n;]+/', $text) ?: []),
            fn ($line) => $line !== ''
        ));
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            Appointments::STATUS_SCHEDULED => 'Agendada',
            Appointments::STATUS_RESCHEDULED => 'Reagendada',
            Appointments::STATUS_COMPLETED => 'Concluída',
            Appointments::STATUS_NO_SHOW => 'Falta',
            Appointments::STATUS_IN_PROGRESS => 'Em andamento',
            Appointments::STATUS_CANCELLED => 'Cancelada',
            default => 'Agendada',
        };
    }

    private function statusClass(?string $status): string
    {
        return match ($status) {
            Appointments::STATUS_SCHEDULED, Appointments::STATUS_RESCHEDULED => 'bg-primary/20 text-primary',
            Appointments::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            Appointments::STATUS_NO_SHOW, Appointments::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            Appointments::STATUS_IN_PROGRESS => 'bg-amber-100 text-amber-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
