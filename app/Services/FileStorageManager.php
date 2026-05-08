<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class FileStorageManager
{
    // Domínios de storage usados como contrato único entre controllers/services/jobs.
    public const DOMAIN_PUBLIC_IMAGES = 'public_images';

    public const DOMAIN_MEDICAL_DOCUMENTS = 'medical_documents';

    public const DOMAIN_LGPD_EXPORTS = 'lgpd_exports';

    public const DOMAIN_PRESCRIPTIONS = 'prescriptions';

    public const DOMAIN_CERTIFICATES = 'certificates';

    public const DOMAIN_CHAT_ATTACHMENTS = 'chat_attachments';

    public const DOMAIN_INTEGRATION_DOCUMENTS = 'integration_documents';

    public const DOMAIN_VIDEO_RECORDINGS = 'video_recordings';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allDomains(): array
    {
        return config('telemedicine.file_domains', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function domain(string $domain): array
    {
        $config = $this->allDomains()[$domain] ?? null;

        if (! is_array($config)) {
            // Falha explícita evita fallback silencioso para disco incorreto.
            throw new InvalidArgumentException("Domínio de storage inválido: {$domain}");
        }

        return $config;
    }

    public function diskName(string $domain): string
    {
        return (string) ($this->domain($domain)['disk'] ?? '');
    }

    public function disk(string $domain): FilesystemAdapter
    {
        return Storage::disk($this->diskName($domain));
    }

    public function basePath(string $domain): string
    {
        return trim((string) ($this->domain($domain)['base_path'] ?? ''), '/');
    }

    public function visibility(string $domain): string
    {
        return (string) ($this->domain($domain)['visibility'] ?? 'private');
    }

    public function retentionDays(string $domain): ?int
    {
        $days = $this->domain($domain)['retention_days'] ?? null;

        // Null significa "sem expurgo automático" para o domínio no MVP.
        return is_int($days) ? $days : null;
    }

    public function healthcheckEnabled(string $domain): bool
    {
        return (bool) ($this->domain($domain)['healthcheck_enabled'] ?? false);
    }

    /**
     * @return array<string>
     */
    public function domainsForHealthcheck(): array
    {
        return collect($this->allDomains())
            ->filter(fn (array $config): bool => (bool) ($config['healthcheck_enabled'] ?? false))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * @return array<string>
     */
    public function domainsWithRetention(): array
    {
        return collect($this->allDomains())
            ->filter(fn (array $config): bool => is_int($config['retention_days'] ?? null))
            ->keys()
            ->values()
            ->all();
    }

    public function buildPath(string $domain, string $relativePath = ''): string
    {
        $basePath = $this->basePath($domain);
        $relativePath = trim($relativePath, '/');

        if ($relativePath === '') {
            return $basePath;
        }

        // Padroniza composição de caminho e evita barras duplicadas.
        return $basePath === '' ? $relativePath : "{$basePath}/{$relativePath}";
    }
}
