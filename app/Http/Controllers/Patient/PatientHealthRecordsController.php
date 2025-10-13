<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class PatientHealthRecordsController extends Controller
{
    public function index(): Response
    {
        $patient = Auth::user()->patient;

        return Inertia::render('HealthRecords', [
            'patient' => $patient,
        ]);
    }
}



