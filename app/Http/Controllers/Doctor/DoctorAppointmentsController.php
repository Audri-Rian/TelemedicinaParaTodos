<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class DoctorAppointmentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Doctor/ScheduleManagement');
    }
}


