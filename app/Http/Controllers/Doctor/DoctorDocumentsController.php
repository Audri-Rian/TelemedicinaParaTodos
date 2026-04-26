<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointments;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDocumentsController extends Controller
{
    public function index(Request $request): Response
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            abort(403, 'Apenas médicos podem acessar esta página.');
        }

        $patientIds = Appointments::where('doctor_id', $doctor->id)
            ->distinct('patient_id')
            ->pluck('patient_id');

        $patients = Patient::with('user')
            ->whereIn('id', $patientIds)
            ->get()
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'name' => $p->user->name ?? '',
                'cpf' => $p->cpf,
                'age' => $p->age,
                'sex' => $this->sexLabel($p->gender),
            ])
            ->values()
            ->all();

        return Inertia::render('Doctor/Documents', [
            'patients' => $patients,
        ]);
    }

    private function sexLabel(?string $gender): ?string
    {
        return match (strtolower((string) $gender)) {
            Patient::GENDER_MALE => 'M',
            Patient::GENDER_FEMALE => 'F',
            default => null,
        };
    }
}
