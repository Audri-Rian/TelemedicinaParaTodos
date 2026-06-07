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
        Schema::create('fhir_resource_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('internal_resource_type', 100);
            $table->uuid('internal_resource_id');
            $table->string('fhir_resource_type', 100);
            $table->string('fhir_resource_id')->nullable();
            $table->string('fhir_bundle_id')->nullable();
            $table->foreignUuid('partner_integration_id')->nullable()
                  ->constrained('partner_integrations')->nullOnDelete();
            $table->string('version')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['internal_resource_type', 'internal_resource_id', 'partner_integration_id'],
                'fhir_mapping_unique'
            );
            $table->index(['fhir_resource_type', 'fhir_resource_id']);
            $table->index('partner_integration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fhir_resource_mappings');
    }
};
