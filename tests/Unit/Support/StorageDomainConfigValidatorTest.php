<?php

namespace Tests\Unit\Support;

use App\Support\StorageDomainConfigValidator;
use RuntimeException;
use Tests\TestCase;

class StorageDomainConfigValidatorTest extends TestCase
{
    public function test_validator_accepts_valid_configuration(): void
    {
        config()->set('filesystems.disks', [
            'local' => ['driver' => 'local'],
            'public' => ['driver' => 'local'],
        ]);
        config()->set('telemedicine.file_domains', [
            'public_images' => [
                'disk' => 'public',
                'base_path' => 'public/images',
                'visibility' => 'public',
                'retention_days' => null,
                'healthcheck_enabled' => true,
            ],
            'lgpd_exports' => [
                'disk' => 'local',
                'base_path' => 'lgpd/exports',
                'visibility' => 'private',
                'retention_days' => 7,
                'healthcheck_enabled' => true,
            ],
        ]);

        StorageDomainConfigValidator::validate();
        $this->assertTrue(true);
    }

    public function test_validator_rejects_invalid_retention_days(): void
    {
        config()->set('filesystems.disks', [
            'local' => ['driver' => 'local'],
        ]);
        config()->set('telemedicine.file_domains', [
            'lgpd_exports' => [
                'disk' => 'local',
                'base_path' => 'lgpd/exports',
                'visibility' => 'private',
                'retention_days' => 0,
                'healthcheck_enabled' => true,
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('retention_days inválido');

        StorageDomainConfigValidator::validate();
    }
}
