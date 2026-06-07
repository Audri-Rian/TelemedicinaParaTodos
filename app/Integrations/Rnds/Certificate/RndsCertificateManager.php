<?php

namespace App\Integrations\Rnds\Certificate;

class RndsCertificateManager
{
    public function isConfigured(): bool
    {
        $path = config('integrations.rnds.certificate_path');

        return is_string($path) && $path !== '';
    }

    /**
     * @return array{cert: array{0: string, 1: string}|string}
     */
    public function mtlsHttpOptions(): array
    {
        $certPath = config('integrations.rnds.certificate_path');
        $certPassword = config('integrations.rnds.certificate_password');

        if (! is_string($certPath) || $certPath === '') {
            throw new \RuntimeException(
                'RNDS: certificado e-CNPJ ou URL de autenticação não configurados.'
            );
        }

        $cert = $certPassword ? [$certPath, $certPassword] : $certPath;

        return ['cert' => $cert];
    }
}
