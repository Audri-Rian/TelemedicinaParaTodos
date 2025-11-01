<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Doctor;
use App\Models\User;

class PatientVideoCallController extends Controller
{
    /**
     * Display the patient's video call page.
     */
    public function index(): Response
    {
        // Buscar médicos disponíveis
        $doctors = Doctor::with('user')
            ->active()
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->user->id,
                    'name' => $doctor->user->name,
                    'email' => $doctor->user->email,
                ];
            });

        return Inertia::render('Patient/VideoCall', [
            'users' => $doctors,
        ]);
    }
}

