<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DoctorPerfilController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Patient/DoctorPerfil');
    }
}

