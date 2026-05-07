<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMedicalRecordPDF;
use App\Services\MedicalRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PatientMedicalRecordController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
    ) {}

    /**
     * Exibe a página de prontuário médico do paciente.
     */
    public function index(Request $request): Response
    {
        $patient = $request->user()?->patient;

        if (! $patient) {
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
    public function export(Request $request): JsonResponse|RedirectResponse
    {
        $patient = $request->user()?->patient;

        if (! $patient) {
            abort(403, 'Perfil de paciente não encontrado.');
        }

        $this->authorize('export', $patient);

        $filters = $this->extractFilters($request);

        $rateLimiterKey = sprintf('medical-record-export:%s', $patient->id);
        if (RateLimiter::tooManyAttempts($rateLimiterKey, 3)) {
            $retryAfterSeconds = RateLimiter::availableIn($rateLimiterKey);
            $retryAfterMinutes = max(1, (int) ceil($retryAfterSeconds / 60));
            $errorMessage = sprintf(
                'Você atingiu o limite de exportações recentes. Tente novamente em %d minuto(s).',
                $retryAfterMinutes
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                    'errors' => [
                        'export' => [$errorMessage],
                    ],
                ], 429);
            }

            return back()->withErrors(['export' => $errorMessage]);
        }

        RateLimiter::hit($rateLimiterKey, 3600);

        $queueConnection = config('telemedicine.medical_records.export_queue_connection', config('queue.default'));
        $queueName = config('telemedicine.medical_records.export_queue_name', 'default');

        try {
            GenerateMedicalRecordPDF::dispatch($patient, $request->user(), $filters)
                ->onConnection($queueConnection)
                ->onQueue($queueName);
        } catch (Throwable $exception) {
            report($exception);

            $errorMessage = 'Não foi possível solicitar a exportação neste momento. Tente novamente.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                    'errors' => [
                        'export' => [$errorMessage],
                    ],
                ], 500);
            }

            return back()->withErrors(['export' => $errorMessage]);
        }

        $this->medicalRecordService->logAccess(
            $request->user(),
            $patient,
            'export_requested',
            ['filters' => $filters]
        );

        $successMessage = 'Solicitação recebida. Estamos gerando seu PDF em segundo plano. O arquivo aparecerá em Documentos em instantes.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $successMessage,
                'status' => 'queued',
            ], 202);
        }

        return back()->with('status', $successMessage);
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
