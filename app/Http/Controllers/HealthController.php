<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use Inertia\Response;

use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function index()
    {
        return Inertia::render('HealthRecords');
    }
}
