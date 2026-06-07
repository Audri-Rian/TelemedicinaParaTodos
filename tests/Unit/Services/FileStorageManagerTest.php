<?php

namespace Tests\Unit\Services;

use App\Services\FileStorageManager;
use Tests\TestCase;

class FileStorageManagerTest extends TestCase
{
    public function test_build_path_combines_base_and_relative_path(): void
    {
        config()->set('telemedicine.file_domains', [
            'medical_documents' => [
                'disk' => 'local',
                'base_path' => 'medical/documents',
                'visibility' => 'private',
                'retention_days' => null,
                'healthcheck_enabled' => true,
            ],
        ]);

        $manager = app(FileStorageManager::class);

        $this->assertSame(
            'medical/documents/123/file.pdf',
            $manager->buildPath('medical_documents', '/123/file.pdf')
        );
    }

    public function test_it_filters_domains_for_healthcheck_and_retention(): void
    {
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
            'video_recordings' => [
                'disk' => 'local',
                'base_path' => 'calls/recordings',
                'visibility' => 'private',
                'retention_days' => null,
                'healthcheck_enabled' => false,
            ],
        ]);

        $manager = app(FileStorageManager::class);

        $this->assertSame(
            ['public_images', 'lgpd_exports'],
            $manager->domainsForHealthcheck()
        );
        $this->assertSame(['lgpd_exports'], $manager->domainsWithRetention());
    }
}
