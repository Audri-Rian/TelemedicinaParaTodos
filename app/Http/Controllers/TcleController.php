<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class TcleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('TCLE');
    }
}
