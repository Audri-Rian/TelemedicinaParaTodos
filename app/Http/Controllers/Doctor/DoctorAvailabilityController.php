<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\Doctor\AvailabilityTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DoctorAvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityTimelineService $timelineService
    ) {
    }

    public function index(Request $request): Response
    {
        $doctor = Auth::user()->doctor;

        $overview = $this->timelineService->getOverview($doctor);

        return Inertia::render('Doctor/AvailabilityOverview', [
            'timeline' => $overview['timeline'],
            'summary' => $overview['summary'],
            'meta' => $overview['window'],
            'locations' => $overview['locations'],
        ]);
    }
}

