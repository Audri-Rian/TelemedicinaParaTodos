<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('integration_events', function (Blueprint $table) {
            $table->foreignUuid('doctor_id')
                ->nullable()
                ->after('partner_integration_id')
                ->constrained('doctors')
                ->nullOnDelete();

            $table->index(['doctor_id', 'created_at']);
        });

        // Backfill melhor-esforço:
        // 1) eventos de examination herdam doctor_id do exame
        DB::statement("
            UPDATE integration_events ie
            SET doctor_id = e.doctor_id
            FROM examinations e
            WHERE ie.resource_type = 'examination'
              AND ie.resource_id = e.id
              AND ie.doctor_id IS NULL
        ");

        // 2) fallback para eventos antigos sem resource_id:
        // usa creator legado do partner (connected_by -> doctors.user_id)
        DB::statement('
            UPDATE integration_events ie
            SET doctor_id = d.id
            FROM partner_integrations pi
            JOIN doctors d ON d.user_id = pi.connected_by
            WHERE ie.partner_integration_id = pi.id
              AND ie.doctor_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_events', function (Blueprint $table) {
            $table->dropIndex('integration_events_doctor_id_created_at_index');
            $table->dropConstrainedForeignId('doctor_id');
        });
    }
};
