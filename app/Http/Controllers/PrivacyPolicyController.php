<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PrivacyPolicyController extends Controller
{
    /**
     * Display the privacy policy page.
     */
    public function index(): Response
    {
        return Inertia::render('PrivacyPolicy');
    }
}

