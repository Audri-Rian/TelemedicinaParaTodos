<?php

namespace Tests\Feature\Doctor;

use App\Models\Appointments;
use App\Models\ClinicalNote;
use App\Models\ClinicalRecordVersion;
use App\Models\Doctor;
use App\Models\MedicalCertificate;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalRecordVersioningTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private User $patientUser;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);

        $this->patientUser = User::factory()->create();
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);

        // Grant doctor access to patient (required by policy)
        Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => Carbon::now()->subDay(),
            'status' => Appointments::STATUS_COMPLETED,
        ]);
    }

    private function makeNote(array $overrides = []): ClinicalNote
    {
        return ClinicalNote::create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => null,
            'title' => 'Nota de teste',
            'content' => 'Conteúdo original',
            'is_private' => false,
            'category' => 'general',
            'version' => 1,
        ], $overrides));
    }

    private function makePrescription(array $overrides = []): Prescription
    {
        return Prescription::factory()->create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'status' => 'active',
            'signature_status' => 'unsigned',
        ], $overrides));
    }

    private function makeCertificate(array $overrides = []): MedicalCertificate
    {
        return MedicalCertificate::create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => null,
            'type' => 'absence',
            'reason' => 'Consulta médica',
            'status' => 'issued',
            'signature_status' => 'unsigned',
            'verification_code' => uniqid('vc_'),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days' => 1,
        ], $overrides));
    }

    // --- PATCH notes ---

    public function test_doctor_can_update_clinical_note(): void
    {
        $this->actingAs($this->doctorUser);
        $note = $this->makeNote();

        $response = $this->patch(
            route('doctor.patients.medical-record.notes.update', [$this->patient, $note]),
            [
                'title' => 'Título atualizado',
                'change_reason' => 'Correção de informação clínica relevante',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('clinical_notes', ['id' => $note->id, 'title' => 'Título atualizado']);
        $this->assertDatabaseHas('clinical_record_versions', [
            'versionable_id' => $note->id,
            'version_number' => 2,
            'change_reason' => 'Correção de informação clínica relevante',
        ]);
    }

    public function test_update_note_requires_change_reason(): void
    {
        $this->actingAs($this->doctorUser);
        $note = $this->makeNote();

        $response = $this->patch(
            route('doctor.patients.medical-record.notes.update', [$this->patient, $note]),
            ['title' => 'Sem motivo']
        );

        $response->assertSessionHasErrors('change_reason');
        $this->assertDatabaseCount('clinical_record_versions', 1);
    }

    public function test_update_note_rejects_short_change_reason(): void
    {
        $this->actingAs($this->doctorUser);
        $note = $this->makeNote();

        $response = $this->patch(
            route('doctor.patients.medical-record.notes.update', [$this->patient, $note]),
            ['title' => 'Título', 'change_reason' => 'Curto']
        );

        $response->assertSessionHasErrors('change_reason');
    }

    public function test_doctor_cannot_update_note_of_other_patient(): void
    {
        $this->actingAs($this->doctorUser);
        $otherPatientUser = User::factory()->create();
        $otherPatient = Patient::factory()->create(['user_id' => $otherPatientUser->id]);
        $note = ClinicalNote::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $otherPatient->id,
            'appointment_id' => null,
            'title' => 'Nota de outro paciente',
            'content' => 'Conteúdo',
            'is_private' => false,
            'category' => 'general',
            'version' => 1,
        ]);

        // Route binds to $this->patient, but note belongs to otherPatient → 404
        $response = $this->patch(
            route('doctor.patients.medical-record.notes.update', [$this->patient, $note]),
            ['title' => 'Tentativa', 'change_reason' => 'Tentativa de acesso indevido']
        );

        $response->assertNotFound();
    }

    // --- PATCH prescriptions ---

    public function test_doctor_can_update_prescription(): void
    {
        $this->actingAs($this->doctorUser);
        $prescription = $this->makePrescription();

        $response = $this->patch(
            route('doctor.patients.medical-record.prescriptions.update', [$this->patient, $prescription]),
            [
                'instructions' => 'Tomar com água',
                'change_reason' => 'Ajuste posológico conforme retorno',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('prescriptions', ['id' => $prescription->id, 'instructions' => 'Tomar com água']);
        $this->assertDatabaseHas('clinical_record_versions', [
            'versionable_id' => $prescription->id,
            'version_number' => 2,
        ]);
    }

    public function test_cannot_update_signed_prescription(): void
    {
        $this->actingAs($this->doctorUser);
        $prescription = $this->makePrescription(['signature_status' => 'signed']);

        $response = $this->patch(
            route('doctor.patients.medical-record.prescriptions.update', [$this->patient, $prescription]),
            [
                'instructions' => 'Tentativa de edição',
                'change_reason' => 'Tentativa de edição pós-assinatura',
            ]
        );

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('prescriptions', ['id' => $prescription->id, 'instructions' => 'Tentativa de edição']);
    }

    // --- PATCH certificates ---

    public function test_doctor_can_update_certificate(): void
    {
        $this->actingAs($this->doctorUser);
        $certificate = $this->makeCertificate();

        $response = $this->patch(
            route('doctor.patients.medical-record.certificates.update', [$this->patient, $certificate]),
            [
                'reason' => 'Afastamento por doença aguda',
                'change_reason' => 'Motivo corrigido após confirmação diagnóstica',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('medical_certificates', [
            'id' => $certificate->id,
            'reason' => 'Afastamento por doença aguda',
        ]);
        $this->assertDatabaseHas('clinical_record_versions', [
            'versionable_id' => $certificate->id,
            'version_number' => 2,
        ]);
    }

    public function test_cannot_update_signed_certificate(): void
    {
        $this->actingAs($this->doctorUser);
        $certificate = $this->makeCertificate(['signature_status' => 'signed']);

        $response = $this->patch(
            route('doctor.patients.medical-record.certificates.update', [$this->patient, $certificate]),
            [
                'reason' => 'Novo motivo',
                'change_reason' => 'Tentativa de edição pós-assinatura',
            ]
        );

        $response->assertSessionHasErrors();
    }

    // --- GET versions (doctor) ---

    public function test_doctor_can_view_version_history_with_diff(): void
    {
        $this->actingAs($this->doctorUser);
        $note = $this->makeNote();
        $note->setVersionChangeReason('Atualização clínica necessária');
        $note->update(['title' => 'Título revisado']);

        $response = $this->getJson(
            route('doctor.patients.medical-record.versions', [$this->patient, 'notes', $note->id])
        );

        $response->assertOk();
        $response->assertJsonStructure(['versions' => [['version_number', 'changed_by', 'changed_fields', 'old_values', 'new_values']]]);

        $versions = $response->json('versions');
        $this->assertCount(2, $versions);

        $v2 = collect($versions)->firstWhere('version_number', 2);
        $this->assertSame('Nota de teste', $v2['old_values']['title']);
        $this->assertSame('Título revisado', $v2['new_values']['title']);
    }

    public function test_doctor_cannot_view_versions_of_unrelated_patient(): void
    {
        $otherPatientUser = User::factory()->create();
        $otherPatient = Patient::factory()->create(['user_id' => $otherPatientUser->id]);
        $note = ClinicalNote::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $otherPatient->id,
            'appointment_id' => null,
            'title' => 'Nota alheia',
            'content' => 'x',
            'is_private' => false,
            'category' => 'general',
            'version' => 1,
        ]);

        $this->actingAs($this->doctorUser);

        // Doctor has no appointment with otherPatient → policy denies
        $response = $this->getJson(
            route('doctor.patients.medical-record.versions', [$otherPatient, 'notes', $note->id])
        );

        $response->assertForbidden();
    }

    // --- GET versions (patient) ---

    public function test_patient_can_view_own_version_history_without_diff_values(): void
    {
        $this->actingAs($this->doctorUser);
        $note = $this->makeNote();
        $note->setVersionChangeReason('Actualização de dados');
        $note->update(['title' => 'Título atualizado']);

        $this->actingAs($this->patientUser);

        $response = $this->getJson(
            route('patient.medical-records.versions', ['notes', $note->id])
        );

        $response->assertOk();
        $versions = $response->json('versions');
        $this->assertCount(2, $versions);

        // Patient should NOT see old_values / new_values
        foreach ($versions as $v) {
            $this->assertArrayNotHasKey('old_values', $v);
            $this->assertArrayNotHasKey('new_values', $v);
        }
    }

    public function test_patient_cannot_view_another_patients_version_history(): void
    {
        $otherPatientUser = User::factory()->create();
        $otherPatient = Patient::factory()->create(['user_id' => $otherPatientUser->id]);
        $note = ClinicalNote::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $otherPatient->id,
            'appointment_id' => null,
            'title' => 'Nota alheia',
            'content' => 'x',
            'is_private' => false,
            'category' => 'general',
            'version' => 1,
        ]);

        $this->actingAs($this->patientUser);

        $response = $this->getJson(
            route('patient.medical-records.versions', ['notes', $note->id])
        );

        $response->assertNotFound();
    }

    public function test_guest_cannot_access_versions(): void
    {
        $note = $this->makeNote();

        $this->getJson(
            route('doctor.patients.medical-record.versions', [$this->patient, 'notes', $note->id])
        )->assertUnauthorized();
    }

    // --- no version on failed save (observer blocks) ---

    public function test_no_orphan_version_when_save_blocked_by_observer(): void
    {
        $this->actingAs($this->doctorUser);
        $prescription = $this->makePrescription(['signature_status' => 'signed']);

        $versionsBefore = ClinicalRecordVersion::where('versionable_id', $prescription->id)->count();

        $this->patch(
            route('doctor.patients.medical-record.prescriptions.update', [$this->patient, $prescription]),
            [
                'instructions' => 'Não deve salvar',
                'change_reason' => 'Tentativa de edição pós-assinatura',
            ]
        );

        $versionsAfter = ClinicalRecordVersion::where('versionable_id', $prescription->id)->count();
        $this->assertSame($versionsBefore, $versionsAfter);
    }
}
