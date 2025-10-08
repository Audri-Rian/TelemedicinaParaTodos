<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class AppointmentsController extends Controller
{
    public function index()
    {
        return Inertia::render('Doctor/ScheduleManagement');
    }
}
