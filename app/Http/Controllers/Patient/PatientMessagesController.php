<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PatientMessagesController extends Controller
{
    /**
     * Display the patient's messages page.
     */
    public function index(): Response
    {
        return Inertia::render('Patient/Messages');
    }
}

