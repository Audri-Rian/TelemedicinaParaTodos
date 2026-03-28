<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DoctorLaboratoriesController extends Controller
{
    /** Hub de integrações (rota inicial da área Laboratórios). */
    public function index(): Response
    {
        return Inertia::render('Doctor/Laboratories/Hub');
    }

    public function partners(): Response
    {
        return Inertia::render('Doctor/Laboratories/Partners');
    }

    public function connect(): Response
    {
        return Inertia::render('Doctor/Laboratories/Connect');
    }
}
