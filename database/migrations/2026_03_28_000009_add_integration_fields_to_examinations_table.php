<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->foreignUuid('partner_integration_id')->nullable()
                  ->after('metadata')
                  ->constrained('partner_integrations')->nullOnDelete();
            $table->string('external_id')->nullable()->after('partner_integration_id');
            $table->string('external_accession')->nullable()->after('external_id');
            $table->enum('source', ['internal', 'integration', 'manual_upload'])
                  ->default('internal')->after('external_accession');
            $table->timestamp('received_from_partner_at')->nullable()->after('source');

            $table->index('external_id');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropForeign(['partner_integration_id']);
            $table->dropIndex(['external_id']);
            $table->dropIndex(['source']);
            $table->dropColumn([
                'partner_integration_id',
                'external_id',
                'external_accession',
                'source',
                'received_from_partner_at',
            ]);
        });
    }
};
