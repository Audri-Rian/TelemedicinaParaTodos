<?php

namespace Tests\Unit;

use App\Models\ClinicalNote;
use App\Models\ClinicalRecordVersion;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class HasClinicalVersioningTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctorUser = User::factory()->create();
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);
        $this->patientUser = User::factory()->create();
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);
    }

    private function makeNote(array $overrides = []): ClinicalNote
    {
        return ClinicalNote::create(array_merge([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'appointment_id' => null,
            'title' => 'Nota inicial',
            'content' => 'Conteúdo inicial',
            'is_private' => false,
            'category' => 'general',
            'version' => 1,
        ], $overrides));
    }

    // --- created event ---

    public function test_creates_version_1_on_model_creation(): void
    {
        $this->actingAs($this->doctorUser);

        $note = $this->makeNote();

        $this->assertDatabaseCount('clinical_record_versions', 1);

        $version = ClinicalRecordVersion::first();
        $this->assertSame(1, $version->version_number);
        $this->assertSame($this->doctorUser->id, $version->changed_by);
        $this->assertNull($version->change_reason);
        $this->assertEmpty($version->old_values);
        $this->assertArrayHasKey('title', $version->new_values);
        $this->assertSame('Nota inicial', $version->new_values['title']);
        $this->assertSame($note->id, $version->versionable_id);
        $this->assertSame(ClinicalNote::class, $version->versionable_type);
    }

    // --- updated event ---

    public function test_creates_version_2_on_update_with_diff(): void
    {
        $this->actingAs($this->doctorUser);

        $note = $this->makeNote();
        $note->setVersionChangeReason('Correção de diagnóstico inicial');
        $note->update(['title' => 'Título corrigido', 'content' => 'Novo conteúdo']);

        $this->assertDatabaseCount('clinical_record_versions', 2);

        $v2 = ClinicalRecordVersion::orderBy('version_number', 'desc')->first();
        $this->assertSame(2, $v2->version_number);
        $this->assertSame('Correção de diagnóstico inicial', $v2->change_reason);
        $this->assertContains('title', $v2->changed_fields);
        $this->assertContains('content', $v2->changed_fields);
        $this->assertSame('Nota inicial', $v2->old_values['title']);
        $this->assertSame('Título corrigido', $v2->new_values['title']);
    }

    public function test_no_version_created_when_no_tracked_fields_changed(): void
    {
        $this->actingAs($this->doctorUser);

        $note = $this->makeNote();
        $note->update(['version' => 2]); // not in $versionedFields

        $this->assertDatabaseCount('clinical_record_versions', 1);
    }

    public function test_change_reason_is_persisted_in_version(): void
    {
        $this->actingAs($this->doctorUser);

        $note = $this->makeNote();
        $note->setVersionChangeReason('Motivo explícito de alteração clínica');
        $note->update(['is_private' => true]);

        $v2 = ClinicalRecordVersion::where('version_number', 2)->first();
        $this->assertSame('Motivo explícito de alteração clínica', $v2->change_reason);
    }

    public function test_change_reason_cleared_after_save(): void
    {
        $this->actingAs($this->doctorUser);

        $note = $this->makeNote();
        $note->setVersionChangeReason('Primeiro motivo');
        $note->update(['is_private' => true]);

        // Second update without setVersionChangeReason
        $note->update(['category' => 'followup']);

        $v3 = ClinicalRecordVersion::where('version_number', 3)->first();
        $this->assertNull($v3->change_reason);
    }

    public function test_pending_version_cleared_after_save(): void
    {
        $this->actingAs($this->doctorUser);

        $note = $this->makeNote();
        $note->setVersionChangeReason('Motivo');
        $note->update(['title' => 'Novo título']);

        // _pendingVersion should be empty — next update with no tracked changes creates no version
        $note->update(['version' => 3]);

        $this->assertDatabaseCount('clinical_record_versions', 2);
    }

    public function test_versions_relationship_ordered_by_version_number(): void
    {
        $this->actingAs($this->doctorUser);

        $note = $this->makeNote();
        $note->setVersionChangeReason('Primeira edição');
        $note->update(['title' => 'V2']);
        $note->setVersionChangeReason('Segunda edição');
        $note->update(['title' => 'V3']);

        $numbers = $note->versions()->pluck('version_number')->all();
        $this->assertSame([1, 2, 3], $numbers);
    }

    public function test_version_uses_auth_user_as_changed_by(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $note = $this->makeNote();

        $v1 = $note->versions()->first();
        $this->assertSame($otherUser->id, $v1->changed_by);
    }

    public function test_version_falls_back_to_doctor_user_id_when_no_auth(): void
    {
        Auth::logout();

        $note = $this->makeNote();

        $v1 = $note->versions()->first();
        $this->assertSame($this->doctorUser->id, $v1->changed_by);
    }
}
