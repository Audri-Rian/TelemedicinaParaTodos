<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\Doctor\ScheduleService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class DoctorAppointmentsController extends Controller
{
    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    public function index(): Response
    {
        $doctor = Auth::user()->doctor;
        
        // Carregar configuração completa da agenda
        $scheduleConfig = $this->scheduleService->getScheduleConfig($doctor);

        return Inertia::render('Doctor/ScheduleManagement', [
            'scheduleConfig' => $scheduleConfig,
        ]);
    }
}


