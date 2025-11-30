<?php

namespace App\Http\Controllers\LGPD;

use App\Http\Controllers\Controller;
use App\Services\LGPDService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPortabilityController extends Controller
{
    public function __construct(
        private LGPDService $lgpdService
    ) {}

    /**
     * Exporta dados do usuário em formato JSON
     */
    public function export(Request $request): StreamedResponse
    {
        $user = auth()->user();

        // Registrar acesso
        $this->lgpdService->logDataAccess(
            $user,
            $user,
            'personal_data',
            'export',
            null,
            null,
            'Exportação de dados pessoais solicitada pelo usuário'
        );

        // Gerar arquivo
        $filePath = $this->lgpdService->generateDataExportFile($user);
        $data = json_decode(Storage::disk('local')->get($filePath), true);

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, "dados_pessoais_{$user->id}_" . now()->format('Y-m-d') . '.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Exibe página de portabilidade de dados
     */
    public function index()
    {
        return \Inertia\Inertia::render('LGPD/DataPortability');
    }
}
