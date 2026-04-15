<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            if (! Schema::hasIndex('examinations', 'examinations_source_index')) {
                $table->index('source', 'examinations_source_index');
            }
        });

        Schema::table('integration_events', function (Blueprint $table) {
            if (! Schema::hasIndex('integration_events', 'integration_events_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'integration_events_status_created_at_index');
            }

            if (! Schema::hasIndex('integration_events', 'integration_events_partner_direction_index')) {
                $table->index(['partner_integration_id', 'direction'], 'integration_events_partner_direction_index');
            }
        });

        Schema::table('partner_integrations', function (Blueprint $table) {
            if (! Schema::hasIndex('partner_integrations', 'partner_integrations_status_last_sync_at_index')) {
                $table->index(['status', 'last_sync_at'], 'partner_integrations_status_last_sync_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropIndex('examinations_source_index');
        });

        Schema::table('integration_events', function (Blueprint $table) {
            $table->dropIndex('integration_events_status_created_at_index');
            $table->dropIndex('integration_events_partner_direction_index');
        });

        Schema::table('partner_integrations', function (Blueprint $table) {
            $table->dropIndex('partner_integrations_status_last_sync_at_index');
        });
    }
};
