<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DoctorHistoryController extends Controller
{
    /**
     * Display the doctor's consultation history.
     */
    public function index(): Response
    {
        return Inertia::render('Doctor/History');
    }
}
