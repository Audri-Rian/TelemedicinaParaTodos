<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DigitalSignatureController extends Controller
{
    public function show(Request $request): Response
    {
        $doctor = $request->user()->doctor;

        return Inertia::render('settings/DigitalSignature', [
            'signatureStatus' => $doctor->digital_signature_status,
            'requireForIssuance' => (bool) config('telemedicine.signature.require_for_issuance'),
            'flashStatus' => $request->session()->get('status'),
        ]);
    }

    public function activate(Request $request): RedirectResponse
    {
        $doctor = $request->user()->doctor;

        if ($doctor->hasActiveDigitalSignature()) {
            return back()->with('status', 'Sua assinatura digital já está ativa.');
        }

        $previousStatus = $doctor->digital_signature_status;
        $doctor->update(['digital_signature_status' => Doctor::SIGNATURE_ACTIVE]);

        // Fluxo MOCK: substituído pelo onboarding do provedor real (ICP-Brasil) no futuro.
        Log::info('Assinatura digital ativada via integração simulada', [
            'doctor_id' => $doctor->id,
            'previous_status' => $previousStatus,
        ]);

        return back()->with('status', 'Assinatura digital ativada (integração simulada). Você já pode emitir documentos clínicos.');
    }
}
