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
        Schema::create('integration_queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_integration_id')
                  ->constrained('partner_integrations')->cascadeOnDelete();
            $table->foreignUuid('integration_event_id')->nullable()
                  ->constrained('integration_events')->nullOnDelete();
            $table->string('operation', 100);
            $table->json('payload');
            $table->enum('status', [
                'queued', 'processing', 'completed', 'failed', 'cancelled',
            ])->default('queued');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(5);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('partner_integration_id');
            $table->index('operation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_queue');
    }
};
