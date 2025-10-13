<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PatientDetailsController extends Controller
{
    /**
     * Display patient details.
     */
    public function show(string $id): Response
    {
        return Inertia::render('Doctor/PatientDetails', [
            'patientId' => $id
        ]);
    }
}
