<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PatientConsultationDetailsController extends Controller
{
    /**
     * Display the patient's consultation details page.
     */
    public function index(): Response
    {
        return Inertia::render('Patient/ConsultationDetails');
    }
}

