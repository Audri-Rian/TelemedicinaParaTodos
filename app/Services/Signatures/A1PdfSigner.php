<?php

namespace App\Services\Signatures;

use App\Contracts\PdfSigner;
use App\Models\Doctor;
use App\Support\Signatures\PadesEmbedder;
use RuntimeException;

/**
 * Driver de assinatura PAdES com certificado A1 local (arquivo PFX / PKCS#12).
 *
 * Requer configuração em config('telemedicine.signature.a1_pdf'):
 *   - pfx_path:     caminho absoluto para o arquivo .pfx do médico (ou cofre)
 *   - pfx_password: senha do PKCS#12 (NÃO logar, não versionar)
 *   - cert_fingerprint: SHA-256 esperado do certificado
 *   - trusted_ca_path: bundle/dir de CAs ICP-Brasil confiáveis
 *
 * Observações:
 *   - Certificado A1 deve ser ICP-Brasil e-CPF para validade CFM (Res. 2.314/2022 Art. 8).
 *   - Em produção, armazenar PFX em cofre (ex.: AWS Secrets Manager / HashiCorp Vault).
 *   - Por padrão o subject do certificado precisa conter o nome do médico.
 */
final class A1PdfSigner implements PdfSigner
{
    public function __construct(private readonly PadesEmbedder $embedder) {}

    public function signPdf(string $pdfBytes, Doctor $doctor, string $reason): string
    {
        $config = config('telemedicine.signature.a1_pdf');

        $pfxPath = $config['pfx_path'] ?? null;
        $pfxPassword = $config['pfx_password'] ?? '';
        $expectedFingerprint = $config['cert_fingerprint'] ?? null;
        $trustedCaPath = $config['trusted_ca_path'] ?? null;

        if (! $pfxPath) {
            throw new RuntimeException(
                'A1PdfSigner: PFX path not configured. Set SIGNATURE_A1_PFX_PATH in .env.'
            );
        }

        // Single atomic read — avoids TOCTOU between file_exists() and file_get_contents()
        $pfxContents = @file_get_contents($pfxPath);
        if ($pfxContents === false || $pfxContents === '') {
            throw new RuntimeException(
                'A1PdfSigner: PFX file not found or unreadable. Set SIGNATURE_A1_PFX_PATH in .env.'
            );
        }

        $certs = [];
        if (! openssl_pkcs12_read($pfxContents, $certs, $pfxPassword)) {
            throw new RuntimeException(
                'A1PdfSigner: could not parse PFX (wrong password or invalid file). '.openssl_error_string()
            );
        }

        // $certs['cert'] = PEM certificate
        // $certs['pkey'] = PEM private key
        // $certs['extracerts'] = array of chain certificates
        $certPem = $certs['cert'] ?? null;
        $keyPem = $certs['pkey'] ?? null;
        $chain = $certs['extracerts'] ?? [];

        if (! $certPem || ! $keyPem) {
            throw new RuntimeException('A1PdfSigner: PFX did not contain certificate or private key.');
        }

        $this->validateCertificate(
            certPem: $certPem,
            doctor: $doctor,
            expectedFingerprint: $expectedFingerprint,
            trustedCaPath: $trustedCaPath,
            requireDoctorNameMatch: (bool) ($config['require_doctor_name_match'] ?? true),
        );

        $signerName = $doctor->user?->name ?? 'Médico';

        return $this->embedder->embed(
            pdf: $pdfBytes,
            certPem: $certPem,
            privateKeyPem: $keyPem,
            reason: $reason,
            signerName: $signerName,
            extraCertsPem: $chain,
        );
    }

    public function name(): string
    {
        return 'a1_local';
    }

    public function hasLegalValidity(): bool
    {
        return true;
    }

    private function validateCertificate(
        string $certPem,
        Doctor $doctor,
        ?string $expectedFingerprint,
        ?string $trustedCaPath,
        bool $requireDoctorNameMatch,
    ): void {
        $parsed = openssl_x509_parse($certPem);
        if (! is_array($parsed)) {
            throw new RuntimeException('A1PdfSigner: could not parse certificate metadata.');
        }

        $now = time();
        $validFrom = (int) ($parsed['validFrom_time_t'] ?? 0);
        $validTo = (int) ($parsed['validTo_time_t'] ?? 0);

        if ($validFrom === 0 || $validTo === 0 || $now < $validFrom || $now > $validTo) {
            throw new RuntimeException('A1PdfSigner: certificate is not currently valid.');
        }

        $this->validateExpectedFingerprint($certPem, $expectedFingerprint);
        $this->validateTrustedChain($certPem, $trustedCaPath);

        if ($requireDoctorNameMatch) {
            $this->validateDoctorSubject($parsed, $doctor);
        }
    }

    private function validateExpectedFingerprint(string $certPem, ?string $expectedFingerprint): void
    {
        if (! $expectedFingerprint) {
            throw new RuntimeException(
                'A1PdfSigner: expected certificate fingerprint is not configured. Set SIGNATURE_A1_CERT_FINGERPRINT.'
            );
        }

        $actual = openssl_x509_fingerprint($certPem, 'sha256');
        if (! is_string($actual)) {
            throw new RuntimeException('A1PdfSigner: could not calculate certificate fingerprint.');
        }

        if ($this->normalizeFingerprint($actual) !== $this->normalizeFingerprint($expectedFingerprint)) {
            throw new RuntimeException('A1PdfSigner: certificate fingerprint does not match configured value.');
        }
    }

    private function validateTrustedChain(string $certPem, ?string $trustedCaPath): void
    {
        if (! $trustedCaPath) {
            throw new RuntimeException(
                'A1PdfSigner: trusted ICP-Brasil CA bundle is not configured. Set SIGNATURE_A1_TRUSTED_CA_PATH.'
            );
        }

        if (! is_readable($trustedCaPath)) {
            throw new RuntimeException('A1PdfSigner: trusted CA bundle is not readable.');
        }

        $trusted = openssl_x509_checkpurpose($certPem, X509_PURPOSE_ANY, [$trustedCaPath]);
        if ($trusted !== true && $trusted !== 1) {
            throw new RuntimeException('A1PdfSigner: certificate chain is not trusted by the configured CA bundle.');
        }
    }

    /** @param array<string, mixed> $parsed */
    private function validateDoctorSubject(array $parsed, Doctor $doctor): void
    {
        $doctorName = $doctor->user?->name;
        if (! is_string($doctorName) || trim($doctorName) === '') {
            throw new RuntimeException('A1PdfSigner: doctor name is required to validate certificate ownership.');
        }

        $subject = $parsed['subject'] ?? [];
        $issuer = $parsed['issuer'] ?? [];
        $subjectText = $this->flattenCertificatePart($subject);
        $issuerText = $this->flattenCertificatePart($issuer);

        if (! str_contains($this->normalizeText($subjectText), $this->normalizeText($doctorName))) {
            throw new RuntimeException('A1PdfSigner: certificate subject does not match the doctor name.');
        }

        if (! str_contains($this->normalizeText($subjectText.' '.$issuerText), 'ICPBRASIL')) {
            throw new RuntimeException('A1PdfSigner: certificate metadata does not identify ICP-Brasil.');
        }
    }

    private function normalizeFingerprint(string $value): string
    {
        return strtolower(preg_replace('/[^a-fA-F0-9]/', '', $value) ?? '');
    }

    private function flattenCertificatePart(mixed $part): string
    {
        if (! is_array($part)) {
            return is_scalar($part) ? (string) $part : '';
        }

        return implode(' ', array_map(
            fn (mixed $value): string => $this->flattenCertificatePart($value),
            $part,
        ));
    }

    private function normalizeText(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $ascii = is_string($ascii) ? $ascii : $value;

        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $ascii) ?? '');
    }
}
