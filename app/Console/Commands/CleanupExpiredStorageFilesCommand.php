<?php

namespace App\Console\Commands;

use App\Services\FileStorageManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanupExpiredStorageFilesCommand extends Command
{
    protected $signature = 'storage:cleanup-expired
                            {--domain=* : Domínio(s) específicos para limpeza}
                            {--dry-run : Apenas exibe candidatos sem remover arquivos}';

    protected $description = 'Remove arquivos expirados baseado no retention_days por domínio';

    public function __construct(
        private readonly FileStorageManager $fileStorageManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $domains = $this->resolveDomains();
        $totalDeleted = 0;
        $totalCandidates = 0;

        foreach ($domains as $domain) {
            $retentionDays = $this->fileStorageManager->retentionDays($domain);

            if (! is_int($retentionDays)) {
                continue;
            }

            $disk = $this->fileStorageManager->disk($domain);
            $basePath = $this->fileStorageManager->basePath($domain);
            $cutoff = now()->subDays($retentionDays);

            $files = $disk->allFiles($basePath);
            $candidates = [];

            foreach ($files as $filePath) {
                try {
                    $lastModified = Carbon::createFromTimestamp($disk->lastModified($filePath));
                } catch (Throwable) {
                    // Arquivo inacessível não deve interromper limpeza de todo o domínio.
                    continue;
                }

                if ($lastModified->lte($cutoff)) {
                    $candidates[] = $filePath;
                }
            }

            $deletedInDomain = 0;
            foreach ($candidates as $candidate) {
                if (! $dryRun) {
                    $disk->delete($candidate);
                    $deletedInDomain++;
                }
            }

            $totalCandidates += count($candidates);
            $totalDeleted += $deletedInDomain;

            Log::info('storage_retention_cleanup', [
                'domain' => $domain,
                'disk' => $this->fileStorageManager->diskName($domain),
                'retention_days' => $retentionDays,
                'candidates' => count($candidates),
                'deleted' => $deletedInDomain,
                'dry_run' => $dryRun,
            ]);

            $this->line(sprintf(
                '%s: candidatos=%d removidos=%d dry_run=%s',
                $domain,
                count($candidates),
                $deletedInDomain,
                $dryRun ? 'yes' : 'no',
            ));
        }

        $this->info(sprintf(
            'Limpeza finalizada. Candidatos=%d Removidos=%d',
            $totalCandidates,
            $totalDeleted,
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string>
     */
    private function resolveDomains(): array
    {
        $domainsOption = collect((array) $this->option('domain'))
            ->filter(fn (string $domain): bool => $domain !== '')
            ->values()
            ->all();

        if ($domainsOption !== []) {
            return $domainsOption;
        }

        return $this->fileStorageManager->domainsWithRetention();
    }
}
