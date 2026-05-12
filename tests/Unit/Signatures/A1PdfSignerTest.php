<?php

namespace Tests\Unit\Signatures;

use App\Contracts\PdfSigner;
use App\Models\Doctor;
use App\Models\User;
use App\Services\Signatures\A1PdfSigner;
use App\Support\Signatures\PadesEmbedder;
use RuntimeException;
use Tests\TestCase;

class A1PdfSignerTest extends TestCase
{
    private ?string $pfxPath = null;

    public function test_sign_pdf_requires_configured_certificate_fingerprint(): void
    {
        $this->pfxPath = $this->createPkcs12Fixture('Maria Silva ICP-Brasil');

        config()->set('telemedicine.signature.a1_pdf', [
            'pfx_path' => $this->pfxPath,
            'pfx_password' => 'secret',
            'cert_fingerprint' => null,
            'trusted_ca_path' => __FILE__,
            'require_doctor_name_match' => true,
        ]);

        $doctor = new Doctor;
        $doctor->setRelation('user', new User(['name' => 'Maria Silva']));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected certificate fingerprint');

        (new A1PdfSigner(new PadesEmbedder))->signPdf('%PDF-1.4 fake content', $doctor, 'Receita médica');
    }

    public function test_invalid_pdf_signature_driver_is_rejected(): void
    {
        config()->set('telemedicine.signature.driver', 'typo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid SIGNATURE_DRIVER');

        $this->app->make(PdfSigner::class);
    }

    protected function tearDown(): void
    {
        if ($this->pfxPath && is_file($this->pfxPath)) {
            unlink($this->pfxPath);
        }

        parent::tearDown();
    }

    private function createPkcs12Fixture(string $commonName): string
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new([
            'commonName' => $commonName,
            'organizationName' => 'ICP-Brasil',
        ], $privateKey);

        $certificate = openssl_csr_sign($csr, null, $privateKey, 1, ['digest_alg' => 'sha256']);

        $exported = openssl_pkcs12_export($certificate, $pfxContents, $privateKey, 'secret');
        $this->assertTrue($exported);

        $path = tempnam(sys_get_temp_dir(), 'a1_pdf_signer_');
        $this->assertIsString($path);
        file_put_contents($path, $pfxContents);

        return $path;
    }
}
