<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BugReportController extends Controller
{
    /**
     * Show the bug report page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/BugReport');
    }
}

