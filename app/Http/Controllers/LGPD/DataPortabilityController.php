<?php

namespace App\Http\Controllers\LGPD;

use App\Http\Controllers\Controller;
use App\Services\FileStorageManager;
use App\Services\LGPDService;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPortabilityController extends Controller
{
    public function __construct(
        private readonly LGPDService $lgpdService,
        private readonly FileStorageManager $fileStorageManager,
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
        $disk = $this->fileStorageManager->disk(FileStorageManager::DOMAIN_LGPD_EXPORTS);

        return response()->streamDownload(function () use ($disk, $filePath) {
            $stream = $disk->readStream($filePath);

            if (! is_resource($stream)) {
                throw new RuntimeException('Não foi possível abrir o arquivo de exportação.');
            }

            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
                $disk->delete($filePath);
            }
        }, "dados_pessoais_{$user->id}_".now()->format('Y-m-d').'.json', [
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
