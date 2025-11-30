<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // Carregar o relacionamento patient explicitamente
        $patient = $user->patient;
        
        // Carregar o relacionamento doctor explicitamente
        $doctor = $user->doctor;
        
        // Carregar timeline events se for doctor
        $timelineEvents = [];
        if ($user->isDoctor()) {
            $timelineEvents = $user->timelineEvents()
                ->ordered()
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'type' => $event->type,
                        'type_label' => $event->type_label,
                        'title' => $event->title,
                        'subtitle' => $event->subtitle,
                        'start_date' => $event->start_date->format('Y-m-d'),
                        'end_date' => $event->end_date?->format('Y-m-d'),
                        'description' => $event->description,
                        'media_url' => $event->media_url,
                        'degree_type' => $event->degree_type?->value,
                        'is_public' => $event->is_public,
                        'extra_data' => $event->extra_data,
                        'order_priority' => $event->order_priority,
                        'formatted_start_date' => $event->formatted_start_date,
                        'formatted_end_date' => $event->formatted_end_date,
                        'date_range' => $event->date_range,
                        'duration' => $event->duration,
                        'is_in_progress' => $event->is_in_progress,
                    ];
                })
                ->toArray();
        }

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'avatarUrl' => $user->getAvatarUrl(),
            'avatarThumbnailUrl' => $user->getAvatarUrl(true),
            'timelineCompleted' => $user->timeline_completed ?? false,
            'patient' => $patient ? [
                'id' => $patient->id,
                'emergency_contact' => $patient->emergency_contact,
                'emergency_phone' => $patient->emergency_phone,
                'medical_history' => $patient->medical_history,
                'allergies' => $patient->allergies,
                'current_medications' => $patient->current_medications,
                'blood_type' => $patient->blood_type,
                'height' => $patient->height ? (float) $patient->height : null,
                'weight' => $patient->weight ? (float) $patient->weight : null,
                'insurance_provider' => $patient->insurance_provider,
                'insurance_number' => $patient->insurance_number,
                'consent_telemedicine' => (bool) $patient->consent_telemedicine,
            ] : null,
            'doctor' => $doctor ? [
                'id' => $doctor->id,
                'biography' => $doctor->biography,
                'license_number' => $doctor->license_number,
                'license_expiry_date' => $doctor->license_expiry_date?->format('Y-m-d'),
                'consultation_fee' => $doctor->consultation_fee ? (float) $doctor->consultation_fee : null,
                'status' => $doctor->status,
                'availability_schedule' => $doctor->availability_schedule,
            ] : null,
            'timelineEvents' => $timelineEvents,
            'bloodTypes' => \App\Models\Patient::BLOOD_TYPES,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Atualizar dados do User
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        $user->fill($userData);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Atualizar dados do Patient se o usuário for paciente
        if ($user->isPatient() && $user->patient) {
            $patientData = [
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'emergency_phone' => $validated['emergency_phone'] ?? null,
                'medical_history' => $validated['medical_history'] ?? null,
                'allergies' => $validated['allergies'] ?? null,
                'current_medications' => $validated['current_medications'] ?? null,
                'blood_type' => $validated['blood_type'] ?? null,
                'height' => $validated['height'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'insurance_provider' => $validated['insurance_provider'] ?? null,
                'insurance_number' => $validated['insurance_number'] ?? null,
                'consent_telemedicine' => isset($validated['consent_telemedicine']) 
                    ? filter_var($validated['consent_telemedicine'], FILTER_VALIDATE_BOOLEAN) 
                    : false,
            ];

            $user->patient->update($patientData);
        }

        // Atualizar dados do Doctor se o usuário for médico
        if ($user->isDoctor() && $user->doctor) {
            $doctorData = [
                'biography' => $validated['biography'] ?? null,
                'license_number' => $validated['license_number'] ?? null,
                'license_expiry_date' => isset($validated['license_expiry_date']) 
                    ? $validated['license_expiry_date'] 
                    : null,
                'consultation_fee' => $validated['consultation_fee'] ?? null,
                'status' => $validated['status'] ?? $user->doctor->status,
                'availability_schedule' => $validated['availability_schedule'] ?? null,
            ];

            $user->doctor->update($doctorData);
        }

        return to_route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
