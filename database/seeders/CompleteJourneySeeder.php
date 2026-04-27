<?php

namespace Database\Seeders;

use App\Models\AppointmentLog;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\MedicalDocument;
use App\Models\Message;
use App\Models\PartnerIntegration;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\Specialization;
use App\Models\User;
use App\Models\VitalSign;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompleteJourneySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('CompleteJourneySeeder ignorado em produção.');

            return;
        }

        $mainUser = User::updateOrCreate(
            ['email' => 'full.user@telemedicina.test'],
            [
                'name' => 'Usuario Completo Demo',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $doctor = Doctor::updateOrCreate(
            ['user_id' => $mainUser->id],
            [
                'crm' => 'CRM-999999',
                'biography' => 'Perfil de demonstracao completo para testes de interoperabilidade.',
                'language' => ['pt-BR', 'en-US'],
                'license_number' => 'LIC-DEMO-COMPLETE',
                'license_expiry_date' => Carbon::now()->addYears(3),
                'status' => Doctor::STATUS_ACTIVE,
                'availability_schedule' => [
                    'monday' => ['start' => '08:00', 'end' => '18:00', 'slots' => ['08:00', '08:45', '09:30']],
                    'tuesday' => ['start' => '08:00', 'end' => '18:00', 'slots' => ['08:00', '08:45', '09:30']],
                    'wednesday' => ['start' => '08:00', 'end' => '18:00', 'slots' => ['08:00', '08:45', '09:30']],
                    'thursday' => ['start' => '08:00', 'end' => '18:00', 'slots' => ['08:00', '08:45', '09:30']],
                    'friday' => ['start' => '08:00', 'end' => '18:00', 'slots' => ['08:00', '08:45', '09:30']],
                    'saturday' => null,
                    'sunday' => null,
                ],
                'consultation_fee' => 180.00,
            ]
        );

        $specialization = Specialization::query()->first();
        if ($specialization !== null) {
            $doctor->specializations()->syncWithoutDetaching([$specialization->id]);
        }

        $patient = Patient::updateOrCreate(
            ['user_id' => $mainUser->id],
            [
                'gender' => Patient::GENDER_OTHER,
                'date_of_birth' => '1992-01-15',
                'phone_number' => '11999999999',
                'emergency_contact' => 'Contato de Emergencia',
                'emergency_phone' => '11888888888',
                'medical_history' => 'Historico para testes integrados da plataforma.',
                'allergies' => 'Dipirona',
                'current_medications' => 'Nenhuma',
                'blood_type' => 'O+',
                'height' => 175,
                'weight' => 80,
                'insurance_provider' => 'Plano Demo',
                'insurance_number' => 'DEMO-001',
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDay(),
                'cpf' => '12345678909',
                'cns' => '898001160328496',
                'mother_name' => 'Maria Demo',
            ]
        );

        $counterpartUser = User::updateOrCreate(
            ['email' => 'counterpart.user@telemedicina.test'],
            [
                'name' => 'Usuario Contato Demo',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $scheduledAppointment = Appointments::updateOrCreate(
            ['access_code' => 'SEEDSCHD'],
            [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'scheduled_at' => now()->addDays(2),
                'status' => Appointments::STATUS_SCHEDULED,
                'notes' => 'Agendamento futuro para validacao de fluxo de agenda.',
                'metadata' => ['source' => 'CompleteJourneySeeder', 'kind' => 'agendamento'],
            ]
        );

        $completedAppointment = Appointments::updateOrCreate(
            ['access_code' => 'SEEDCOMP'],
            [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'scheduled_at' => now()->subDays(1),
                'started_at' => now()->subDays(1)->addMinutes(5),
                'ended_at' => now()->subDays(1)->addMinutes(45),
                'status' => Appointments::STATUS_COMPLETED,
                'notes' => 'Consulta concluida com documentos e interoperabilidade.',
                'metadata' => ['source' => 'CompleteJourneySeeder', 'kind' => 'consulta'],
            ]
        );

        AppointmentLog::updateOrCreate(
            ['appointment_id' => $scheduledAppointment->id, 'event' => AppointmentLog::EVENT_CREATED],
            ['user_id' => $mainUser->id, 'payload' => ['context' => 'agendamento_criado']]
        );

        AppointmentLog::updateOrCreate(
            ['appointment_id' => $completedAppointment->id, 'event' => AppointmentLog::EVENT_ENDED],
            ['user_id' => $mainUser->id, 'payload' => ['context' => 'consulta_encerrada']]
        );

        Message::updateOrCreate(
            [
                'sender_id' => $mainUser->id,
                'receiver_id' => $counterpartUser->id,
                'appointment_id' => $scheduledAppointment->id,
            ],
            [
                'content' => 'Mensagem de teste vinculada ao agendamento.',
                'status' => Message::STATUS_DELIVERED,
                'delivered_at' => now()->subHours(1),
                'read_at' => now()->subMinutes(30),
            ]
        );

        VitalSign::updateOrCreate(
            ['appointment_id' => $completedAppointment->id, 'patient_id' => $patient->id],
            [
                'doctor_id' => $doctor->id,
                'recorded_at' => now()->subDay()->addMinutes(20),
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'temperature' => 36.7,
                'heart_rate' => 72,
                'respiratory_rate' => 16,
                'oxygen_saturation' => 98,
                'weight' => 80.00,
                'height' => 175.00,
                'notes' => 'Sinais vitais estaveis.',
                'metadata' => ['device' => 'manual'],
            ]
        );

        Diagnosis::updateOrCreate(
            ['appointment_id' => $completedAppointment->id, 'cid10_code' => 'I10'],
            [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'cid10_description' => 'Hipertensao essencial (primaria)',
                'diagnosis_type' => Diagnosis::TYPE_PRINCIPAL,
                'description' => 'Diagnostico usado para validacao do prontuario.',
            ]
        );

        $examination = Examination::updateOrCreate(
            ['appointment_id' => $completedAppointment->id, 'name' => 'Hemograma completo'],
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'type' => Examination::TYPE_LAB,
                'requested_at' => now()->subDay()->addMinutes(10),
                'completed_at' => now()->subDay()->addMinutes(30),
                'results' => ['summary' => 'Sem alteracoes relevantes.'],
                'attachment_url' => 'https://example.test/exams/hemograma-completo.pdf',
                'status' => Examination::STATUS_COMPLETED,
                'metadata' => ['lab' => 'Laboratorio Demo'],
                'source' => Examination::SOURCE_INTEGRATION,
                'received_from_partner_at' => now()->subHours(20),
            ]
        );

        $prescription = Prescription::updateOrCreate(
            ['appointment_id' => $completedAppointment->id],
            [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'medications' => [[
                    'name' => 'Losartana',
                    'dosage' => '50mg',
                    'frequency' => '1x ao dia',
                    'duration' => '30 dias',
                ]],
                'instructions' => 'Tomar apos refeicao principal.',
                'valid_until' => now()->addDays(30)->toDateString(),
                'status' => Prescription::STATUS_ACTIVE,
                'metadata' => ['seed' => true],
                'issued_at' => now()->subDay()->addMinutes(40),
                'signature_status' => Prescription::SIGNATURE_VERIFIED,
                'verification_code' => 'VERIF-DEMO-001',
                'signed_at' => now()->subDay()->addMinutes(41),
            ]
        );

        MedicalDocument::updateOrCreate(
            ['appointment_id' => $completedAppointment->id, 'name' => 'Laudo Hemograma'],
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'uploaded_by' => $mainUser->id,
                'category' => MedicalDocument::CATEGORY_EXAM,
                'file_path' => 'documents/laudo-hemograma-demo.pdf',
                'file_type' => 'application/pdf',
                'file_size' => 215000,
                'description' => 'Documento de exame integrado ao prontuario.',
                'metadata' => ['reference' => 'DOC-DEMO-001'],
                'visibility' => MedicalDocument::VISIBILITY_SHARED,
            ]
        );

        $call = Call::updateOrCreate(
            ['appointment_id' => $completedAppointment->id],
            [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'status' => Call::STATUS_ENDED,
                'requested_at' => now()->subDay()->addMinutes(4),
                'accepted_at' => now()->subDay()->addMinutes(5),
                'ended_at' => now()->subDay()->addMinutes(45),
            ]
        );

        Room::updateOrCreate(
            ['call_id' => $call->id],
            [
                'room_id' => 'room-demo-'.Str::lower(Str::random(8)),
                'sfu_node' => 'sfu-node-1',
            ]
        );

        $integration = PartnerIntegration::updateOrCreate(
            ['slug' => 'demo-full-integration'],
            [
                'name' => 'Integracao Completa Demo',
                'type' => PartnerIntegration::TYPE_LABORATORY,
                'status' => PartnerIntegration::STATUS_ACTIVE,
                'base_url' => 'https://api.demo-integration.test/fhir/r4',
                'capabilities' => ['send_exam_order', 'receive_exam_result', 'webhook_result'],
                'fhir_version' => 'R4',
                'contact_email' => 'integracao.demo@telemedicina.test',
                'connected_at' => now()->subDays(7),
                'last_sync_at' => now()->subHour(),
                'connected_by' => $mainUser->id,
            ]
        );

        $integration->credential()->updateOrCreate(
            ['partner_integration_id' => $integration->id],
            [
                'auth_type' => 'api_key',
                'client_id' => 'demo-full-api-key',
            ]
        );

        $integration->webhooks()->updateOrCreate(
            ['url' => 'http://localhost/api/v1/public/webhooks/lab/demo-full-integration'],
            [
                'events' => [IntegrationEvent::EVENT_EXAM_RESULT_RECEIVED],
                'status' => 'active',
                'failure_count' => 0,
                'last_triggered_at' => now()->subMinutes(30),
                'last_success_at' => now()->subMinutes(30),
            ]
        );

        $event = IntegrationEvent::updateOrCreate(
            ['external_id' => 'demo-event-'.$completedAppointment->id],
            [
                'partner_integration_id' => $integration->id,
                'direction' => IntegrationEvent::DIRECTION_OUTBOUND,
                'event_type' => IntegrationEvent::EVENT_EXAM_ORDER_SENT,
                'status' => IntegrationEvent::STATUS_SUCCESS,
                'resource_type' => FhirResourceMapping::INTERNAL_EXAMINATION,
                'resource_id' => $examination->id,
                'fhir_resource_type' => FhirResourceMapping::FHIR_SERVICE_REQUEST,
                'request_payload' => ['appointment_id' => $completedAppointment->id],
                'response_payload' => ['message' => 'accepted'],
                'http_status' => 200,
                'duration_ms' => 235,
            ]
        );

        IntegrationQueueItem::updateOrCreate(
            ['integration_event_id' => $event->id, 'operation' => IntegrationQueueItem::OP_SEND_EXAM_ORDER],
            [
                'partner_integration_id' => $integration->id,
                'payload' => ['examination_id' => $examination->id],
                'status' => IntegrationQueueItem::STATUS_COMPLETED,
                'attempts' => 1,
                'max_attempts' => 5,
                'scheduled_at' => now()->subDay()->addMinutes(12),
                'started_at' => now()->subDay()->addMinutes(13),
                'completed_at' => now()->subDay()->addMinutes(13),
            ]
        );

        FhirResourceMapping::updateOrCreate(
            [
                'internal_resource_type' => FhirResourceMapping::INTERNAL_EXAMINATION,
                'internal_resource_id' => $examination->id,
                'partner_integration_id' => $integration->id,
            ],
            [
                'fhir_resource_type' => FhirResourceMapping::FHIR_DIAGNOSTIC_REPORT,
                'fhir_resource_id' => 'diag-report-'.Str::lower(Str::random(10)),
                'version' => '1',
                'synced_at' => now()->subMinutes(50),
            ]
        );

        FhirResourceMapping::updateOrCreate(
            [
                'internal_resource_type' => FhirResourceMapping::INTERNAL_PRESCRIPTION,
                'internal_resource_id' => $prescription->id,
                'partner_integration_id' => $integration->id,
            ],
            [
                'fhir_resource_type' => FhirResourceMapping::FHIR_MEDICATION_REQUEST,
                'fhir_resource_id' => 'medication-request-'.Str::lower(Str::random(10)),
                'version' => '1',
                'synced_at' => now()->subMinutes(40),
            ]
        );
    }
}
