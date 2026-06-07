<?php

namespace Tests\Feature\Settings;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalSignatureSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_view_digital_signature_settings(): void
    {
        $doctor = Doctor::factory()->withoutDigitalSignature()->create();

        $response = $this->actingAs($doctor->user)->get(route('digital-signature.show'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('settings/DigitalSignature')
                ->where('signatureStatus', Doctor::SIGNATURE_NOT_INTEGRATED)
                ->where('requireForIssuance', true)
        );
    }

    public function test_doctor_can_activate_mock_signature(): void
    {
        $doctor = Doctor::factory()->withoutDigitalSignature()->create();

        $response = $this->actingAs($doctor->user)
            ->from(route('digital-signature.show'))
            ->post(route('digital-signature.activate'));

        $response->assertRedirect(route('digital-signature.show'));
        $this->assertSame(Doctor::SIGNATURE_ACTIVE, $doctor->fresh()->digital_signature_status);
    }

    public function test_activation_is_idempotent_for_already_active_signature(): void
    {
        $doctor = Doctor::factory()->create();

        $response = $this->actingAs($doctor->user)->post(route('digital-signature.activate'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Sua assinatura digital já está ativa.');
        $this->assertSame(Doctor::SIGNATURE_ACTIVE, $doctor->fresh()->digital_signature_status);
    }

    public function test_patient_cannot_access_digital_signature_settings(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($patient->user)->get(route('digital-signature.show'))->assertForbidden();
        $this->actingAs($patient->user)->post(route('digital-signature.activate'))->assertForbidden();
    }
}
