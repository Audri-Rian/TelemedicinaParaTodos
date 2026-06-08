<?php

namespace Database\Seeders;

use App\Models\AppointmentLog;
use App\Models\Appointments;
use App\Models\Call;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\FhirResourceMapping;
use App\Models\IntegrationEvent;
use App\Models\IntegrationQueueItem;
use App\Models\MedicalCertificate;
use App\Models\MedicalDocument;
use App\Models\Message;
use App\Models\PartnerIntegration;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\User;
use App\Models\VitalSign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompleteJourneySeeder extends Seeder
{
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        if (! app()->environment('local', 'testing', 'staging')) {
            $this->command?->warn('CompleteJourneySeeder ignorado fora de local/testing/staging.');

            return;
        }

        $patientLogin = 'demo.patient@telemedicina.test';
        $upcomingVideoCallAt = now()->addMinutes(10);

        $doctor = Doctor::query()
            ->with('user')
            ->whereHas('user', fn ($query) => $query->where('email', DoctorSeeder::DEMO_DOCTOR_EMAIL))
            ->first();

        if ($doctor === null) {
            $this->command?->warn('CompleteJourneySeeder requer DoctorSeeder executado antes.');

            return;
        }

        $mainUser = User::updateOrCreate(
            ['email' => $patientLogin],
            [
                'name' => 'Ricardo Oliveira',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
            ]
        );

        $patient = Patient::updateOrCreate(
            ['user_id' => $mainUser->id],
            [
                'gender' => Patient::GENDER_MALE,
                'date_of_birth' => '1992-01-15',
                'phone_number' => '11999998877',
                'emergency_contact' => 'Helena Oliveira',
                'emergency_phone' => '11988887766',
                'medical_history' => 'Hipertensao arterial essencial diagnosticada em 2018. Nega IAM, AVC ou insuficiencia renal. '
                    .'Antecedente familiar de HAS (mae). Sedentarismo leve; etilismo social ocasional. '
                    .'Ultimo MAPA (6 meses): media 132/86 mmHg.',
                'allergies' => 'Dipirona (urticaria); contraste iodado (nausea leve em TC de 2022)',
                'current_medications' => 'Losartana 50mg 1x/dia; Acido acetilsalicilico 100mg 1x/dia',
                'blood_type' => 'O+',
                'height' => 175,
                'weight' => 80,
                'insurance_provider' => 'Plano Demo Saude',
                'insurance_number' => 'DEMO-001-2026',
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDay(),
                'cpf' => '12345678909',
                'cns' => '898001160328496',
                'mother_name' => 'Maria Oliveira',
            ]
        );

        $scheduledAppointment = Appointments::updateOrCreate(
            ['access_code' => 'SEEDSCHD'],
            [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'scheduled_at' => $upcomingVideoCallAt,
                'status' => Appointments::STATUS_SCHEDULED,
                'notes' => 'Teleconsulta demo agendada para validacao de videochamada.',
                'metadata' => [
                    'source' => 'CompleteJourneySeeder',
                    'kind' => 'teleconsulta',
                    'video_call' => true,
                ],
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
                'receiver_id' => $doctor->user_id,
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

        $prescription = Prescription::firstOrCreate(
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

        $seedDocumentPath = 'documents/laudo-hemograma-demo.pdf';
        $seedDocumentDisk = config('telemedicine.file_domains.medical_documents.disk', 'local');
        if (! Storage::disk($seedDocumentDisk)->exists($seedDocumentPath)) {
            $seedDocumentPdf = app('dompdf.wrapper')
                ->loadHTML(
                    '<h1>Laudo Hemograma (Demo)</h1><p>Arquivo criado pelo seeder para validação de download no prontuário.</p>'
                )
                ->output();

            Storage::disk($seedDocumentDisk)->put($seedDocumentPath, $seedDocumentPdf);
        }

        MedicalDocument::updateOrCreate(
            ['appointment_id' => $completedAppointment->id, 'name' => 'Laudo Hemograma'],
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'uploaded_by' => $mainUser->id,
                'category' => MedicalDocument::CATEGORY_EXAM,
                'file_path' => $seedDocumentPath,
                'file_type' => 'application/pdf',
                'file_size' => 215000,
                'description' => 'Documento de exame integrado ao prontuario.',
                'metadata' => [
                    'reference' => 'DOC-DEMO-001',
                    'storage_domain' => 'medical_documents',
                ],
                'visibility' => MedicalDocument::VISIBILITY_SHARED,
            ]
        );

        $call = Call::query()->firstOrNew(['appointment_id' => $completedAppointment->id]);
        $call->forceFill([
            'call_type' => Call::TYPE_SCHEDULED,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => Call::STATUS_ENDED,
            'requested_at' => now()->subDay()->addMinutes(4),
            'accepted_at' => now()->subDay()->addMinutes(5),
            'ended_at' => now()->subDay()->addMinutes(45),
        ])->save();

        $room = Room::query()->firstOrNew(['call_id' => $call->id]);
        $room->forceFill([
            'room_id' => 'room-demo-'.Str::lower(Str::random(8)),
            'sfu_node' => 'sfu-node-1',
            'media_ws_url' => 'wss://sfu.demo/ws',
        ])->save();

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
                'connected_by' => $doctor->user_id,
            ]
        );

        $integration->doctors()->syncWithoutDetaching([
            $doctor->id => [
                'integration_mode' => 'full',
                'perm_send_orders' => true,
                'perm_receive_results' => true,
                'perm_webhook' => true,
                'perm_patient_data' => false,
                'connected_by' => $doctor->user_id,
                'connected_at' => now()->subDays(7),
            ],
        ]);

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
                'doctor_id' => $doctor->id,
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

        $this->seedClinicalNotes($doctor, $patient, $completedAppointment, [
            [
                'title' => 'Anamnese e queixa principal',
                'content' => 'Paciente relata cefaleia occipital intermitente ha 3 semanas, sem sinais de alarme. '
                    .'Nega dor toracica, dispneia ou edema de MMII. Refere adesao parcial a dieta hipossodica.',
                'category' => ClinicalNote::CATEGORY_GENERAL,
                'is_private' => false,
                'tags' => ['anamnese', 'cefaleia', 'has'],
            ],
            [
                'title' => 'Exame fisico e sinais vitais',
                'content' => 'PA 120x80 mmHg, FC 72 bpm, ausculta cardiaca e pulmonar sem alteracoes. '
                    .'Sem sopros. Abdome flacido e indolor. Neurologico sem deficits focais.',
                'category' => ClinicalNote::CATEGORY_DIAGNOSIS,
                'is_private' => false,
                'tags' => ['exame-fisico', 'sinais-vitais'],
            ],
            [
                'title' => 'Plano terapeutico e metas',
                'content' => 'Manter Losartana 50mg. Orientar reducao de sodio (<2g/dia), caminhada 30 min 5x/semana '
                    .'e registro domiciliar de PA por 14 dias. Retorno em 30 dias ou antes se PA >140/90.',
                'category' => ClinicalNote::CATEGORY_TREATMENT,
                'is_private' => false,
                'tags' => ['plano', 'hipertensao', 'estilo-de-vida'],
            ],
            [
                'title' => 'Nota interna — adesao ao plano',
                'content' => 'Paciente demonstrou boa compreensao das orientacoes; encaminhar material educativo sobre HAS. '
                    .'Avaliar necessidade de MAPA no retorno se PA domiciliar permanecer elevada.',
                'category' => ClinicalNote::CATEGORY_OTHER,
                'is_private' => true,
                'tags' => ['interno', 'acompanhamento'],
            ],
        ]);

        MedicalCertificate::updateOrCreate(
            ['appointment_id' => $completedAppointment->id, 'type' => MedicalCertificate::TYPE_ABSENCE],
            [
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
                'days' => 1,
                'reason' => 'Comparecimento a consulta medica por telemedicina para acompanhamento de hipertensao arterial.',
                'restrictions' => 'Repouso relativo no dia da consulta, conforme orientacao medica.',
                'status' => MedicalCertificate::STATUS_ACTIVE,
                'signature_status' => MedicalCertificate::SIGNATURE_VERIFIED,
                'signed_at' => now()->subDay()->addMinutes(42),
                'crm_number' => $doctor->crm,
                'verification_code' => 'CERT-DEMO-001',
                'metadata' => ['seed' => true],
            ]
        );

        $this->seedAdditionalPatientsForDoctor($doctor);

        $this->command?->newLine();
        $this->command?->info('Credenciais de demonstracao (senha: password):');
        $this->command?->line('  Medico: '.DoctorSeeder::DEMO_DOCTOR_EMAIL);
        $this->command?->line('  Paciente: '.$patientLogin);
        $this->command?->line('  Proxima videochamada demo: '.$upcomingVideoCallAt->format('d/m/Y H:i'));
        $this->command?->line('  Pacientes extras: demo.patient+{1..8}@telemedicina.test');
        $this->command?->newLine();
    }

    private function seedAdditionalPatientsForDoctor(Doctor $doctor): void
    {
        $profiles = [
            [
                'index' => 1,
                'name' => 'Joao Martins',
                'gender' => Patient::GENDER_MALE,
                'date_of_birth' => '1953-08-21',
                'blood_type' => 'A+',
                'allergies' => 'Penicilina',
                'current_medications' => 'Metformina 850mg 2x/dia; Losartana 50mg 1x/dia',
                'medical_history' => 'Diabetes tipo 2 e hipertensao controladas. Relata neuropatia leve em membros inferiores.',
                'insurance_provider' => 'Saude Senior',
                'insurance_number' => 'SENIOR-1101',
                'phone_number' => '11911111111',
                'emergency_contact' => 'Helena Martins',
                'emergency_phone' => '11921111111',
                'cpf' => '31572964001',
                'cns' => '898001160328410',
                'mother_name' => 'Teresa Martins',
                'height' => 169,
                'weight' => 82,
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDays(4),
                'appointments' => [
                    [
                        'code' => 'DMP001A',
                        'status' => Appointments::STATUS_COMPLETED,
                        'scheduled_days' => -12,
                        'notes' => 'Retorno de controle glicemico.',
                        'clinical_notes' => [
                            [
                                'title' => 'Evolucao do diabetes tipo 2',
                                'content' => 'HbA1c 7,1% (meta <7%). Glicemias de jejum entre 110-140 mg/dL. Sem hipoglicemias. '
                                    .'Paciente relata dieta irregular nos finais de semana.',
                                'category' => ClinicalNote::CATEGORY_FOLLOW_UP,
                                'is_private' => false,
                                'tags' => ['diabetes', 'hba1c'],
                            ],
                            [
                                'title' => 'Ajuste medicamentoso',
                                'content' => 'Manter Metformina 850mg 2x/dia. Reforcar educacao alimentar e atividade fisica leve. '
                                    .'Solicitar perfil lipidico e microalbuminuria no proximo retorno.',
                                'category' => ClinicalNote::CATEGORY_TREATMENT,
                                'is_private' => false,
                                'tags' => ['metformina', 'plano'],
                            ],
                        ],
                    ],
                    ['code' => 'DMP001B', 'status' => Appointments::STATUS_SCHEDULED, 'scheduled_days' => 3, 'notes' => 'Consulta de seguimento trimestral.'],
                ],
            ],
            [
                'index' => 2,
                'name' => 'Camila Souza',
                'gender' => Patient::GENDER_FEMALE,
                'date_of_birth' => '2001-02-09',
                'blood_type' => 'O-',
                'allergies' => 'Nenhuma alergia medicamentosa conhecida',
                'current_medications' => 'Contraceptivo oral',
                'medical_history' => 'Historico de enxaqueca com aura. Nega comorbidades cronicas.',
                'insurance_provider' => 'Vida Familiar',
                'insurance_number' => 'VF-3200',
                'phone_number' => '11922222222',
                'emergency_contact' => 'Rita Souza',
                'emergency_phone' => '11932222222',
                'cpf' => '06489372088',
                'cns' => '898001160328411',
                'mother_name' => 'Rita Souza',
                'height' => 162,
                'weight' => 58,
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDays(15),
                'appointments' => [
                    ['code' => 'DMP002A', 'status' => Appointments::STATUS_RESCHEDULED, 'scheduled_days' => 5, 'notes' => 'Retorno reagendado por indisponibilidade da paciente.'],
                    ['code' => 'DMP002B', 'status' => Appointments::STATUS_CANCELLED, 'scheduled_days' => -1, 'notes' => 'Consulta cancelada por conflito de agenda.'],
                ],
            ],
            [
                'index' => 3,
                'name' => 'Rafael Lima',
                'gender' => Patient::GENDER_MALE,
                'date_of_birth' => '1987-11-30',
                'blood_type' => 'B+',
                'allergies' => 'Ibuprofeno',
                'current_medications' => 'Sertralina 50mg 1x/dia',
                'medical_history' => 'Ansiedade generalizada em acompanhamento multiprofissional.',
                'insurance_provider' => 'Particular',
                'insurance_number' => 'PART-0091',
                'phone_number' => '11933333333',
                'emergency_contact' => 'Fernanda Lima',
                'emergency_phone' => '11943333333',
                'cpf' => '84620593066',
                'cns' => '898001160328412',
                'mother_name' => 'Claudia Lima',
                'height' => 178,
                'weight' => 90,
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDays(2),
                'appointments' => [
                    ['code' => 'DMP003A', 'status' => Appointments::STATUS_IN_PROGRESS, 'scheduled_days' => 0, 'notes' => 'Consulta em andamento para ajuste terapeutico.'],
                    [
                        'code' => 'DMP003B',
                        'status' => Appointments::STATUS_COMPLETED,
                        'scheduled_days' => -20,
                        'notes' => 'Consulta de avaliacao inicial.',
                        'clinical_notes' => [
                            [
                                'title' => 'Avaliacao psiquiatrica inicial',
                                'content' => 'Queixa de ansiedade generalizada com insônia de conciliacao. Escalas: GAD-7 moderado. '
                                    .'Sem ideação suicida no momento. Iniciado Sertralina 50mg.',
                                'category' => ClinicalNote::CATEGORY_DIAGNOSIS,
                                'is_private' => false,
                                'tags' => ['ansiedade', 'gad-7'],
                            ],
                            [
                                'title' => 'Orientacoes de higiene do sono',
                                'content' => 'Evitar cafeina apos 14h; rotina fixa de dormir/acordar; técnicas de relaxamento. '
                                    .'Retorno em 4 semanas para reavaliacao de efeitos da sertralina.',
                                'category' => ClinicalNote::CATEGORY_TREATMENT,
                                'is_private' => false,
                                'tags' => ['sono', 'orientacoes'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'index' => 4,
                'name' => 'Livia Nascimento',
                'gender' => Patient::GENDER_FEMALE,
                'date_of_birth' => '1995-06-14',
                'blood_type' => 'AB+',
                'allergies' => 'Latex',
                'current_medications' => 'Levotiroxina 75mcg 1x/dia',
                'medical_history' => 'Hipotireoidismo em uso regular de reposicao hormonal.',
                'insurance_provider' => 'Plano Ideal',
                'insurance_number' => 'IDEAL-7804',
                'phone_number' => '11944444444',
                'emergency_contact' => 'Caio Nascimento',
                'emergency_phone' => '11954444444',
                'cpf' => '27349581020',
                'cns' => '898001160328413',
                'mother_name' => 'Eliane Nascimento',
                'height' => 165,
                'weight' => 63,
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDays(30),
                'appointments' => [
                    ['code' => 'DMP004A', 'status' => Appointments::STATUS_NO_SHOW, 'scheduled_days' => -3, 'notes' => 'Paciente nao compareceu a consulta.'],
                    ['code' => 'DMP004B', 'status' => Appointments::STATUS_SCHEDULED, 'scheduled_days' => 9, 'notes' => 'Nova consulta apos falta anterior.'],
                ],
            ],
            [
                'index' => 5,
                'name' => 'Patricia Ferreira',
                'gender' => Patient::GENDER_FEMALE,
                'date_of_birth' => '1976-03-28',
                'blood_type' => 'A-',
                'allergies' => 'AAS',
                'current_medications' => 'Atorvastatina 20mg; Omeprazol 20mg',
                'medical_history' => 'Dislipidemia e gastrite cronica com boa adesao ao tratamento.',
                'insurance_provider' => 'Saude Corporativa',
                'insurance_number' => 'CORP-5521',
                'phone_number' => '11955555555',
                'emergency_contact' => 'Mario Ferreira',
                'emergency_phone' => '11965555555',
                'cpf' => '45918237074',
                'cns' => '898001160328414',
                'mother_name' => 'Neide Ferreira',
                'height' => 160,
                'weight' => 70,
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => false,
                'last_consultation_at' => now()->subDays(45),
                'appointments' => [
                    [
                        'code' => 'DMP005A',
                        'status' => Appointments::STATUS_COMPLETED,
                        'scheduled_days' => -40,
                        'notes' => 'Consulta de revisao laboratorial.',
                        'clinical_notes' => [
                            [
                                'title' => 'Revisao lipidica e gastrite',
                                'content' => 'LDL 142 mg/dL (meta <100). Mantida Atorvastatina 20mg. Refluxo controlado com Omeprazol. '
                                    .'Sem sangramento digestivo. Orientada dieta mediterranea.',
                                'category' => ClinicalNote::CATEGORY_FOLLOW_UP,
                                'is_private' => false,
                                'tags' => ['dislipidemia', 'gastrite'],
                            ],
                        ],
                    ],
                    ['code' => 'DMP005B', 'status' => Appointments::STATUS_CANCELLED, 'scheduled_days' => 4, 'notes' => 'Cancelada por pendencia de exames.'],
                ],
            ],
            [
                'index' => 6,
                'name' => 'Diego Alves',
                'gender' => Patient::GENDER_MALE,
                'date_of_birth' => '2010-12-03',
                'blood_type' => 'O+',
                'allergies' => 'Dipirona',
                'current_medications' => 'Nenhuma',
                'medical_history' => 'Rinite alergica sazonal e historico de bronquite na infancia.',
                'insurance_provider' => 'Plano Familia Mais',
                'insurance_number' => 'FAM-7732',
                'phone_number' => '11966666666',
                'emergency_contact' => 'Carla Alves',
                'emergency_phone' => '11976666666',
                'cpf' => '52760934011',
                'cns' => '898001160328415',
                'mother_name' => 'Carla Alves',
                'height' => 155,
                'weight' => 49,
                'status' => Patient::STATUS_ACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDays(6),
                'appointments' => [
                    [
                        'code' => 'DMP006A',
                        'status' => Appointments::STATUS_COMPLETED,
                        'scheduled_days' => -6,
                        'notes' => 'Consulta pediatrica de acompanhamento.',
                        'clinical_notes' => [
                            [
                                'title' => 'Acompanhamento de rinite alergica',
                                'content' => 'Sintomas nasais leves, sem sibilancia. Crescimento e desenvolvimento adequados para idade. '
                                    .'Orientada higiene ambiental e lavagem nasal com SF 0,9%.',
                                'category' => ClinicalNote::CATEGORY_GENERAL,
                                'is_private' => false,
                                'tags' => ['pediatria', 'rinite'],
                            ],
                            [
                                'title' => 'Conduta respiratoria',
                                'content' => 'Manter budesonida inalatoria conforme prescricao previa. Pais orientados sobre sinais de alerta '
                                    .'(dispneia, uso de musculatura acessoria) e quando procurar urgencia.',
                                'category' => ClinicalNote::CATEGORY_TREATMENT,
                                'is_private' => false,
                                'tags' => ['asma', 'orientacao-familiar'],
                            ],
                        ],
                    ],
                    ['code' => 'DMP006B', 'status' => Appointments::STATUS_SCHEDULED, 'scheduled_days' => 7, 'notes' => 'Retorno para revisao de sintomas respiratorios.'],
                ],
            ],
            [
                'index' => 7,
                'name' => 'Aline Rocha',
                'gender' => Patient::GENDER_FEMALE,
                'date_of_birth' => '1965-09-17',
                'blood_type' => 'B-',
                'allergies' => 'Nenhuma',
                'current_medications' => 'Amlodipino 5mg; Hidroclorotiazida 25mg',
                'medical_history' => 'Hipertensao arterial sistemica com variacoes pressoricas noturnas.',
                'insurance_provider' => 'Vida Plena',
                'insurance_number' => 'VP-4490',
                'phone_number' => '11977777777',
                'emergency_contact' => 'Ricardo Rocha',
                'emergency_phone' => '11987777777',
                'cpf' => '13846029095',
                'cns' => '898001160328416',
                'mother_name' => 'Madalena Rocha',
                'height' => 158,
                'weight' => 74,
                'status' => Patient::STATUS_INACTIVE,
                'consent_telemedicine' => true,
                'last_consultation_at' => now()->subDays(90),
                'appointments' => [
                    [
                        'code' => 'DMP007A',
                        'status' => Appointments::STATUS_COMPLETED,
                        'scheduled_days' => -120,
                        'notes' => 'Ultima consulta antes de inativacao temporaria.',
                        'clinical_notes' => [
                            [
                                'title' => 'Controle pressorico e MAPA',
                                'content' => 'MAPA com media 24h 138/88 mmHg. Paciente refere adesao irregular a anti-hipertensivos. '
                                    .'Reforcada importancia de monitorizacao domiciliar e retorno apos reativacao do cadastro.',
                                'category' => ClinicalNote::CATEGORY_FOLLOW_UP,
                                'is_private' => false,
                                'tags' => ['has', 'mapa'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'index' => 8,
                'name' => 'Bruno Teixeira',
                'gender' => Patient::GENDER_OTHER,
                'date_of_birth' => '1990-05-02',
                'blood_type' => 'AB-',
                'allergies' => 'Cetoprofeno',
                'current_medications' => 'Budesonida inalatoria',
                'medical_history' => 'Asma moderada persistente com episodios de exacerbacoes sazonais.',
                'insurance_provider' => 'Particular',
                'insurance_number' => 'PART-3388',
                'phone_number' => '11988888888',
                'emergency_contact' => 'Lucas Teixeira',
                'emergency_phone' => '11998888888',
                'cpf' => '70249358030',
                'cns' => '898001160328417',
                'mother_name' => 'Luciana Teixeira',
                'height' => 172,
                'weight' => 67,
                'status' => Patient::STATUS_BLOCKED,
                'consent_telemedicine' => false,
                'last_consultation_at' => now()->subDays(180),
                'appointments' => [
                    ['code' => 'DMP008A', 'status' => Appointments::STATUS_CANCELLED, 'scheduled_days' => -2, 'notes' => 'Consulta suspensa por bloqueio administrativo.'],
                ],
            ],
        ];

        foreach ($profiles as $profile) {
            $email = sprintf('demo.patient+%d@telemedicina.test', $profile['index']);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $profile['name'],
                    'password' => self::DEMO_PASSWORD,
                    'email_verified_at' => now(),
                ]
            );

            $patient = Patient::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $profile['gender'],
                    'date_of_birth' => $profile['date_of_birth'],
                    'phone_number' => $profile['phone_number'],
                    'emergency_contact' => $profile['emergency_contact'],
                    'emergency_phone' => $profile['emergency_phone'],
                    'medical_history' => $profile['medical_history'],
                    'allergies' => $profile['allergies'],
                    'current_medications' => $profile['current_medications'],
                    'blood_type' => $profile['blood_type'],
                    'height' => $profile['height'],
                    'weight' => $profile['weight'],
                    'insurance_provider' => $profile['insurance_provider'],
                    'insurance_number' => $profile['insurance_number'],
                    'status' => $profile['status'],
                    'consent_telemedicine' => $profile['consent_telemedicine'],
                    'last_consultation_at' => $profile['last_consultation_at'],
                    'cpf' => $profile['cpf'],
                    'cns' => $profile['cns'],
                    'mother_name' => $profile['mother_name'],
                ]
            );

            foreach ($profile['appointments'] as $appointmentSeed) {
                $scheduledAt = now()->addDays($appointmentSeed['scheduled_days'])->setTime(9, 0);
                $isCompleted = $appointmentSeed['status'] === Appointments::STATUS_COMPLETED;
                $isInProgress = $appointmentSeed['status'] === Appointments::STATUS_IN_PROGRESS;

                $appointment = Appointments::updateOrCreate(
                    ['access_code' => $appointmentSeed['code']],
                    [
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'scheduled_at' => $scheduledAt,
                        'started_at' => $isCompleted || $isInProgress ? $scheduledAt->copy()->addMinutes(5) : null,
                        'ended_at' => $isCompleted ? $scheduledAt->copy()->addMinutes(45) : null,
                        'status' => $appointmentSeed['status'],
                        'notes' => $appointmentSeed['notes'],
                        'metadata' => ['source' => 'CompleteJourneySeeder', 'kind' => 'variado'],
                    ]
                );

                if ($isCompleted && ! empty($appointmentSeed['clinical_notes'])) {
                    $this->seedClinicalNotes($doctor, $patient, $appointment, $appointmentSeed['clinical_notes']);
                }
            }
        }
    }

    /**
     * @param  array<int, array{title: string, content: string, category: string, is_private: bool, tags?: array<int, string>}>  $notes
     */
    private function seedClinicalNotes(Doctor $doctor, Patient $patient, Appointments $appointment, array $notes): void
    {
        foreach ($notes as $note) {
            ClinicalNote::updateOrCreate(
                [
                    'appointment_id' => $appointment->id,
                    'title' => $note['title'],
                ],
                [
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'content' => $note['content'],
                    'category' => $note['category'],
                    'is_private' => $note['is_private'],
                    'tags' => $note['tags'] ?? [],
                    'metadata' => ['seed' => true, 'access_code' => $appointment->access_code],
                ]
            );
        }
    }
}
