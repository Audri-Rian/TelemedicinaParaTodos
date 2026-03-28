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
        Schema::create('integration_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_integration_id')
                  ->constrained('partner_integrations')->cascadeOnDelete();
            $table->enum('direction', ['outbound', 'inbound']);
            $table->string('event_type', 100);
            $table->enum('status', [
                'pending', 'processing', 'success', 'failed', 'retrying',
            ])->default('pending');
            $table->string('resource_type', 100)->nullable();
            $table->uuid('resource_id')->nullable();
            $table->string('fhir_resource_type')->nullable();
            $table->string('external_id')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->integer('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['partner_integration_id', 'created_at']);
            $table->index(['event_type', 'status']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('external_id');
            $table->index('status');
            $table->index('next_retry_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_events');
    }
};
