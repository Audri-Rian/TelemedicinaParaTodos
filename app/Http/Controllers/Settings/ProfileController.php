<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialization;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        $user->load(['patient', 'doctor.specializations']);
        $patient = $user->patient;
        $doctor = $user->doctor;

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
                'cns_registered' => ! empty($patient->cns),
                'cpf_registered' => ! empty($patient->cpf),
                'consent_telemedicine' => (bool) $patient->consent_telemedicine,
            ] : null,
            'doctor' => $doctor ? [
                'id' => $doctor->id,
                'crm' => $doctor->crm,
                'biography' => $doctor->biography,
                'cns_registered' => ! empty($doctor->cns),
                'cbo' => $doctor->cbo,
                'license_number' => $doctor->license_number,
                'license_expiry_date' => $doctor->license_expiry_date?->format('Y-m-d'),
                'consultation_fee' => $doctor->consultation_fee ? (float) $doctor->consultation_fee : null,
                'status' => $doctor->status,
                'availability_schedule' => $doctor->availability_schedule,
                'specializations' => $doctor->specializations->pluck('id')->values(),
            ] : null,
            'timelineEvents' => $timelineEvents,
            'bloodTypes' => Patient::BLOOD_TYPES,
            'specializations' => Cache::remember('specializations:list', now()->addHours(6), fn () => Specialization::query()->orderBy('name')->get(['id', 'name'])
            ),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

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

                // Só atualiza CPF/CNS se o valor for explicitamente enviado e não-nulo
                if (! empty($validated['cns'])) {
                    $patientData['cns'] = $validated['cns'];
                }
                if (! empty($validated['cpf'])) {
                    $patientData['cpf'] = $validated['cpf'];
                }

                $user->patient->update($patientData);
            }

            if ($user->isDoctor() && $user->doctor) {
                // Médico suspenso não pode alterar o próprio status
                $currentStatus = $user->doctor->status;
                $requestedStatus = $validated['status'] ?? $currentStatus;
                $newStatus = $currentStatus === Doctor::STATUS_SUSPENDED
                    ? Doctor::STATUS_SUSPENDED
                    : $requestedStatus;

                $doctorData = [
                    'crm' => $validated['crm'] ?? $user->doctor->crm,
                    'biography' => $validated['biography'] ?? null,
                    'cbo' => $validated['cbo'] ?? null,
                    'license_number' => $validated['license_number'] ?? null,
                    'license_expiry_date' => $validated['license_expiry_date'] ?? null,
                    'consultation_fee' => $validated['consultation_fee'] ?? null,
                    'status' => $newStatus,
                    'availability_schedule' => $validated['availability_schedule'] ?? null,
                ];

                if (! empty($validated['cns'])) {
                    $doctorData['cns'] = $validated['cns'];
                }

                $user->doctor->update($doctorData);

                if (array_key_exists('specializations', $validated)) {
                    $user->doctor->specializations()->sync($validated['specializations'] ?? []);
                }
            }
        });

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
