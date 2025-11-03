<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PatientNextConsultationController extends Controller
{
    /**
     * Display the patient's next consultation page.
     */
    public function index(): Response
    {
        return Inertia::render('Patient/NextConsultation');
    }
}

