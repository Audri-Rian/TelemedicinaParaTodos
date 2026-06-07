<?php

namespace Tests\Unit\Signatures;

use App\Models\Doctor;
use App\Services\Signatures\NullPdfSigner;
use PHPUnit\Framework\TestCase;

class NullPdfSignerTest extends TestCase
{
    private NullPdfSigner $signer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->signer = new NullPdfSigner;
    }

    public function test_sign_pdf_returns_original_bytes_unchanged(): void
    {
        $doctor = $this->createMock(Doctor::class);
        $pdfBytes = '%PDF-1.4 fake content';

        $result = $this->signer->signPdf($pdfBytes, $doctor, 'Receita médica');

        $this->assertSame($pdfBytes, $result);
    }

    public function test_name_returns_null(): void
    {
        $this->assertSame('null', $this->signer->name());
    }

    public function test_has_no_legal_validity(): void
    {
        $this->assertFalse($this->signer->hasLegalValidity());
    }
}
