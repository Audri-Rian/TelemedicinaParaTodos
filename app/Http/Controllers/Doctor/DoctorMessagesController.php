<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DoctorMessagesController extends Controller
{
    /**
     * Display the doctor's messages page.
     */
    public function index(): Response
    {
        return Inertia::render('Doctor/Messages');
    }
}

