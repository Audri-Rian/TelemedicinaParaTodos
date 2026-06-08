<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Executar seeders na ordem correta
        $this->call([
            SpecializationSeeder::class,
            DoctorSeeder::class,
            PartnerIntegrationSeeder::class,
            ExaminationIntegrationSeeder::class,
            IntegrationQueueSeeder::class,
            CompleteJourneySeeder::class,
        ]);
    }
}
