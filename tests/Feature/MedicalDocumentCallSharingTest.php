<?php

namespace Tests\Feature;

use App\Events\MedicalDocumentShared;
use App\Models\Appointments;
use App\Models\Doctor;
use App\Models\MedicalDocument;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MedicalDocumentCallSharingTest extends TestCase
{
    use RefreshDatabase;

    private Doctor $doctor;

    private Patient $patient;

    private Appointments $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = Doctor::factory()->create();
        $this->patient = Patient::factory()->create();

        $this->appointment = Appointments::create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient->id,
            'scheduled_at' => now(),
            'access_code' => 'XYZ123',
            'status' => Appointments::STATUS_IN_PROGRESS,
            'notes' => null,
        ]);
    }

    private function createDocument(array $attributes = []): MedicalDocument
    {
        return MedicalDocument::factory()->create(array_merge([
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'appointment_id' => $this->appointment->id,
            'visibility' => MedicalDocument::VISIBILITY_SHARED,
            'file_type' => 'application/pdf',
            'metadata' => ['storage_domain' => 'medical_documents'],
        ], $attributes));
    }

    private function medicalDocumentsDisk(): string
    {
        return (string) config('telemedicine.file_domains.medical_documents.disk');
    }

    private function putDocumentFile(MedicalDocument $document): void
    {
        Storage::disk($this->medicalDocumentsDisk())->put($document->file_path, '%PDF-1.4 fake');
    }

    public function test_patient_can_download_shared_document_as_attachment(): void
    {
        Storage::fake($this->medicalDocumentsDisk());
        $document = $this->createDocument();
        $this->putDocumentFile($document);

        $response = $this->actingAs($this->patient->user)
            ->get(route('patient.medical-records.documents.download', $document));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_patient_can_view_pdf_document_inline(): void
    {
        Storage::fake($this->medicalDocumentsDisk());
        $document = $this->createDocument();
        $this->putDocumentFile($document);

        $response = $this->actingAs($this->patient->user)
            ->get(route('patient.medical-records.documents.download', ['document' => $document, 'disposition' => 'inline']));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
        $this->assertSame('nosniff', $response->headers->get('x-content-type-options'));
    }

    public function test_inline_falls_back_to_attachment_for_unsafe_mime(): void
    {
        Storage::fake($this->medicalDocumentsDisk());
        $document = $this->createDocument(['file_type' => 'text/html']);
        $this->putDocumentFile($document);

        $response = $this->actingAs($this->patient->user)
            ->get(route('patient.medical-records.documents.download', ['document' => $document, 'disposition' => 'inline']));

        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_patient_cannot_download_doctor_only_document(): void
    {
        $document = $this->createDocument(['visibility' => MedicalDocument::VISIBILITY_DOCTOR]);

        $this->actingAs($this->patient->user)
            ->get(route('patient.medical-records.documents.download', $document))
            ->assertForbidden();
    }

    public function test_patient_cannot_download_document_of_another_patient(): void
    {
        $otherPatient = Patient::factory()->create();
        $document = $this->createDocument(['patient_id' => $otherPatient->id]);

        $this->actingAs($this->patient->user)
            ->get(route('patient.medical-records.documents.download', $document))
            ->assertForbidden();
    }

    public function test_patient_video_call_page_includes_shared_documents(): void
    {
        $shared = $this->createDocument();
        $this->createDocument(['visibility' => MedicalDocument::VISIBILITY_DOCTOR]);

        $response = $this->actingAs($this->patient->user)->get(route('patient.video-call'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Patient/VideoCall')
            ->has('users', 1)
            ->has('users.0.appointment.shared_documents', 1)
            ->where('users.0.appointment.shared_documents.0.id', $shared->id)
            ->missing('users.0.appointment.shared_documents.0.file_path')
        );
    }

    public function test_doctor_video_call_page_includes_shared_documents_and_patient_id(): void
    {
        $shared = $this->createDocument();
        $this->createDocument(['visibility' => MedicalDocument::VISIBILITY_PATIENT]);

        $response = $this->actingAs($this->doctor->user)->get(route('doctor.video-call'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Doctor/VideoCall')
            ->has('appointments', 1)
            ->where('appointments.0.patient.patient_id', $this->patient->id)
            ->has('appointments.0.shared_documents', 1)
            ->where('appointments.0.shared_documents.0.id', $shared->id)
            ->missing('appointments.0.shared_documents.0.file_path')
        );
    }

    public function test_doctor_video_call_page_includes_clinical_summary_and_logs_access(): void
    {
        $response = $this->actingAs($this->doctor->user)->get(route('doctor.video-call'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Doctor/VideoCall')
            ->has('appointments.0.clinical_summary')
            ->has('appointments.0.patient_history')
            ->where('appointments.0.chief_complaint', $this->appointment->notes)
        );

        $this->assertDatabaseHas('medical_record_audit_logs', [
            'patient_id' => $this->patient->id,
            'user_id' => $this->doctor->user->id,
            'action' => 'view',
        ]);
    }

    public function test_doctor_upload_with_appointment_dispatches_shared_event(): void
    {
        Storage::fake($this->medicalDocumentsDisk());
        Event::fake([MedicalDocumentShared::class]);

        $response = $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.documents.store', $this->patient), [
                'file' => UploadedFile::fake()->create('exame.pdf', 100, 'application/pdf'),
                'category' => MedicalDocument::CATEGORY_EXAM,
                'visibility' => MedicalDocument::VISIBILITY_SHARED,
                'appointment_id' => $this->appointment->id,
            ]);

        $response->assertRedirect();

        Event::assertDispatched(MedicalDocumentShared::class, function (MedicalDocumentShared $event) {
            $payload = $event->broadcastWith();

            return $event->document->appointment_id === $this->appointment->id
                && ! array_key_exists('file_path', $payload)
                && ! array_key_exists('description', $payload)
                && $payload['visibility'] === MedicalDocument::VISIBILITY_SHARED;
        });
    }

    public function test_upload_without_appointment_does_not_dispatch_event(): void
    {
        Storage::fake($this->medicalDocumentsDisk());
        Event::fake([MedicalDocumentShared::class]);

        $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.documents.store', $this->patient), [
                'file' => UploadedFile::fake()->create('exame.pdf', 100, 'application/pdf'),
                'category' => MedicalDocument::CATEGORY_EXAM,
                'visibility' => MedicalDocument::VISIBILITY_SHARED,
            ])
            ->assertRedirect();

        Event::assertNotDispatched(MedicalDocumentShared::class);
    }

    public function test_doctor_only_upload_does_not_dispatch_event(): void
    {
        Storage::fake($this->medicalDocumentsDisk());
        Event::fake([MedicalDocumentShared::class]);

        $this->actingAs($this->doctor->user)
            ->post(route('doctor.patients.medical-record.documents.store', $this->patient), [
                'file' => UploadedFile::fake()->create('anotacao.pdf', 100, 'application/pdf'),
                'category' => MedicalDocument::CATEGORY_OTHER,
                'visibility' => MedicalDocument::VISIBILITY_DOCTOR,
                'appointment_id' => $this->appointment->id,
            ])
            ->assertRedirect();

        Event::assertNotDispatched(MedicalDocumentShared::class);
    }

    public function test_shared_event_broadcasts_on_appointment_channel(): void
    {
        $document = $this->createDocument();

        $event = new MedicalDocumentShared($document);

        $this->assertSame("private-appointments.{$this->appointment->id}", $event->broadcastOn()->name);
        $this->assertSame('medical-document.shared', $event->broadcastAs());
    }
}
