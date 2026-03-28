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
        Schema::create('partner_integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', [
                'laboratory', 'pharmacy', 'hospital',
                'insurance', 'rnds', 'other',
            ]);
            $table->enum('status', [
                'active', 'inactive', 'pending', 'error', 'suspended',
            ])->default('pending');
            $table->string('base_url')->nullable();
            $table->string('webhook_url')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('settings')->nullable();
            $table->string('fhir_version')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->uuid('connected_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('status');
            $table->foreign('connected_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_integrations');
    }
};
