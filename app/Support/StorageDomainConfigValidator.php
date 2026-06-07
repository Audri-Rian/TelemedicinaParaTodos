<?php

namespace App\Support;

use RuntimeException;

class StorageDomainConfigValidator
{
    public static function validate(): void
    {
        // Validação de bootstrap: evita iniciar aplicação com roteamento de arquivos inconsistente.
        $domains = config('telemedicine.file_domains', []);
        $availableDisks = array_keys(config('filesystems.disks', []));

        if (! is_array($domains) || $domains === []) {
            throw new RuntimeException('Configuração de file_domains ausente em telemedicine.php.');
        }

        foreach ($domains as $domain => $settings) {
            if (! is_array($settings)) {
                throw new RuntimeException("Configuração inválida para domínio {$domain}.");
            }

            $disk = (string) ($settings['disk'] ?? '');
            $basePath = trim((string) ($settings['base_path'] ?? ''), '/');
            $visibility = (string) ($settings['visibility'] ?? '');
            $retentionDays = $settings['retention_days'] ?? null;

            if ($disk === '' || ! in_array($disk, $availableDisks, true)) {
                throw new RuntimeException("Domínio {$domain} referencia disco inválido: {$disk}");
            }

            if ($basePath === '') {
                throw new RuntimeException("Domínio {$domain} deve definir base_path.");
            }

            if (! in_array($visibility, ['public', 'private'], true)) {
                throw new RuntimeException("Domínio {$domain} possui visibility inválida: {$visibility}");
            }

            if ($retentionDays !== null && (! is_int($retentionDays) || $retentionDays < 1)) {
                // Protege contra expurgo acidental por configuração inválida.
                throw new RuntimeException("Domínio {$domain} possui retention_days inválido.");
            }
        }
    }
}
