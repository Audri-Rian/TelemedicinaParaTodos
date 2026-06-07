<?php

namespace Tests\Unit\Signatures;

use App\Contracts\PdfSigner;
use App\Jobs\SignPrescriptionJob;
use App\Services\MedicalRecordPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SignPrescriptionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_does_nothing_when_prescription_not_found(): void
    {
        $pdfService = Mockery::mock(MedicalRecordPdfService::class);
        $pdfService->shouldReceive('buildPrescriptionPdfBytes')->never();

        $signer = Mockery::mock(PdfSigner::class);
        $signer->shouldReceive('signPdf')->never();

        $job = new SignPrescriptionJob('00000000-0000-0000-0000-000000000000');
        $job->handle($pdfService, $signer);

        // No exception + no pdf service calls = correct early-return behavior
        $this->assertTrue(true);
    }

    public function test_unique_id_uses_prescription_id(): void
    {
        $job = new SignPrescriptionJob('prescription-123');

        $this->assertSame('prescription-123', $job->uniqueId());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
