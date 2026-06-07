<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatientDetailsController extends Controller
{
    public function show(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('view', $patient);

        return redirect()->route('doctor.patients.medical-record', $patient);
    }
}
