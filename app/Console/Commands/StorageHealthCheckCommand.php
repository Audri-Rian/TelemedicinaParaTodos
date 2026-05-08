<?php

namespace App\Console\Commands;

use App\Services\FileStorageManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class StorageHealthCheckCommand extends Command
{
    protected $signature = 'storage:health-check
                            {--domain=* : Domínio(s) específicos para validar}
                            {--fail-on-error : Retorna código de erro quando houver falha}';

    protected $description = 'Executa healthcheck de leitura/escrita por domínio de storage';

    public function __construct(
        private readonly FileStorageManager $fileStorageManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $selectedDomains = $this->resolveDomains();
        $failedDomains = [];

        foreach ($selectedDomains as $domain) {
            $startedAt = microtime(true);

            try {
                $disk = $this->fileStorageManager->disk($domain);
                // Probe curto de escrita/leitura/remoção para validar permissões e conectividade do driver.
                $probePath = $this->fileStorageManager->buildPath(
                    $domain,
                    '__healthchecks/'.Str::uuid().'.txt'
                );

                $disk->put($probePath, 'ok');
                $disk->lastModified($probePath);
                $disk->delete($probePath);

                $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

                $this->line("{$domain}: UP ({$durationMs}ms)");
                Log::info('storage_healthcheck_ok', [
                    'domain' => $domain,
                    'disk' => $this->fileStorageManager->diskName($domain),
                    'duration_ms' => $durationMs,
                ]);
            } catch (Throwable $exception) {
                $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
                $failedDomains[] = $domain;

                $this->error("{$domain}: DOWN ({$durationMs}ms) - {$exception->getMessage()}");
                Log::critical('storage_healthcheck_failed', [
                    'domain' => $domain,
                    'disk' => $this->fileStorageManager->diskName($domain),
                    'operation' => 'write_read_delete_probe',
                    'duration_ms' => $durationMs,
                    'exception_class' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($failedDomains !== []) {
            $this->warn('Domínios com falha: '.implode(', ', $failedDomains));
        }

        if ($failedDomains !== [] && $this->option('fail-on-error')) {
            // Permite integração com monitor externo via exit code não-zero.
            return self::FAILURE;
        }

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

        return $this->fileStorageManager->domainsForHealthcheck();
    }
}
