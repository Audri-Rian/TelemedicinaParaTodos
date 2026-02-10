<?php

namespace App\Http\Controllers\LGPD;

use App\Http\Controllers\Controller;
use App\Services\LGPDService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DataAccessReportController extends Controller
{
    public function __construct(
        private LGPDService $lgpdService
    ) {}

    /**
     * Exibe página de relatórios de acesso
     */
    public function index(): Response
    {
        $user = auth()->user();
        
        $reportDays = (int) config('telemedicine.lgpd.report_window_days', 30);
        $report = $this->lgpdService->generateAccessReport(
            $user,
            Carbon::now()->subDays($reportDays),
            Carbon::now()
        );

        return Inertia::render('LGPD/DataAccessReport', [
            'report' => $report,
        ]);
    }

    /**
     * Gera relatório de acesso em JSON
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $user = auth()->user();
        
        $reportDays = (int) config('telemedicine.lgpd.report_window_days', 30);
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subDays($reportDays);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();

        // Registrar acesso ao relatório
        $this->lgpdService->logDataAccess(
            $user,
            $user,
            'personal_data',
            'view',
            null,
            null,
            'Visualização de relatório de acesso a dados pessoais'
        );

        $report = $this->lgpdService->generateAccessReport($user, $startDate, $endDate);

        return response()->json($report);
    }

    /**
     * Exporta relatório em PDF (futuro)
     */
    public function export(Request $request)
    {
        // TODO: Implementar exportação em PDF
        return response()->json(['message' => 'Funcionalidade em desenvolvimento'], 501);
    }
}
