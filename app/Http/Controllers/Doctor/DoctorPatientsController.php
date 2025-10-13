<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPatientsController extends Controller
{
    /**
     * Display the doctor's patients.
     */
    public function index(): Response
    {
        return Inertia::render('Doctor/Patients');
    }
}
