<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class TermsOfServiceController extends Controller
{
    /**
     * Display the terms of service page.
     */
    public function index(): Response
    {
        return Inertia::render('TermsOfService');
    }
}

