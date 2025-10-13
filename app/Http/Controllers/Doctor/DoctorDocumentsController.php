<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DoctorDocumentsController extends Controller
{
    /**
     * Display the doctor's documents page.
     */
    public function index(): Response
    {
        return Inertia::render('Doctor/Documents');
    }
}
