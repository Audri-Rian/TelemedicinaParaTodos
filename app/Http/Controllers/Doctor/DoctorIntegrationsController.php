<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DoctorIntegrationsController extends Controller
{
    /** Hub de integrações (rota inicial). */
    public function index(): Response
    {
        return Inertia::render('Doctor/Integrations/Hub');
    }

    public function partners(): Response
    {
        return Inertia::render('Doctor/Integrations/Partners');
    }

    public function connect(): Response
    {
        return Inertia::render('Doctor/Integrations/Connect');
    }
}
