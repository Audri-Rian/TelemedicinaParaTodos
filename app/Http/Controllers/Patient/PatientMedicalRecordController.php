<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\MedicalRecord\Application\Services\MedicalRecordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PatientMedicalRecordController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
    ) {
    }

    /**
     * Exibe a página de prontuário médico do paciente.
     */
    public function index(Request $request): Response
    {
        $patient = $request->user()?->patient;

        if (!$patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        $this->authorize('view', $patient);

        $filters = $this->extractFilters($request);
        $payload = $this->medicalRecordService->getPatientMedicalRecord($patient, $filters);

        $this->medicalRecordService->logAccess($request->user(), $patient, 'view');

        return Inertia::render('Patient/MedicalRecord', $payload);
    }

    /**
     * Solicita exportação do prontuário em PDF.
     */
    public function export(Request $request)
    {
        $patient = $request->user()?->patient;

        if (!$patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        $this->authorize('export', $patient);

        $filters = $this->extractFilters($request);

        $rateLimiterKey = sprintf('medical-record-export:%s', $patient->id);
        if (RateLimiter::tooManyAttempts($rateLimiterKey, 1)) {
            return back()->withErrors([
                'export' => 'Você já solicitou uma exportação na última hora. Por favor, aguarde.',
            ]);
        }

        RateLimiter::hit($rateLimiterKey, 3600);

        $document = $this->medicalRecordService->generatePdfDocument($patient, $request->user(), $filters);

        $this->medicalRecordService->logAccess(
            $request->user(),
            $patient,
            'export',
            ['filters' => $filters]
        );

        return Storage::disk('public')->download($document['path'], $document['filename']);
    }

    /**
     * Normaliza filtros vindos da requisição.
     */
    private function extractFilters(Request $request): array
    {
        return array_filter([
            'search' => $request->string('search')->toString(),
            'doctor_id' => $request->string('doctor_id')->toString() ?: null,
            'date_from' => $request->date('date_from'),
            'date_to' => $request->date('date_to'),
            'appointment_status' => $request->input('appointment_status'),
            'prescription_status' => $request->input('prescription_status'),
            'examination_status' => $request->input('examination_status'),
            'examination_type' => $request->input('examination_type'),
            'document_category' => $request->input('document_category'),
            'vitals_limit' => $request->integer('vitals_limit'),
        ], static function ($value) {
            if (is_array($value)) {
                return count($value) > 0;
            }

            return $value !== null && $value !== '';
        });
    }
}
