<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PatientHistoryConsultationsController extends Controller
{
    /**
     * Display the patient's history consultations page.
     */
    public function index(): Response
    {
        return Inertia::render('Patient/HistoryConsultations');
    }
}

