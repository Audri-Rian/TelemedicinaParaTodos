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
        Schema::create('integration_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_integration_id')
                  ->constrained('partner_integrations')->cascadeOnDelete();
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events');
            $table->enum('status', ['active', 'inactive', 'failed'])->default('active');
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_integration_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_webhooks');
    }
};
