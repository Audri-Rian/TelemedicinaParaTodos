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
        Schema::create('examinations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments');
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->foreignUuid('doctor_id')->nullable()->constrained('doctors');
            $table->enum('type', ['lab', 'image', 'other'])->default('lab');
            $table->string('name');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('results')->nullable();
            $table->string('attachment_url')->nullable();
            $table->enum('status', ['requested', 'in_progress', 'completed', 'cancelled'])->default('requested');
            $table->json('metadata')->nullable();
            $table->uuid('partner_integration_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('external_accession')->nullable();
            $table->enum('source', ['internal', 'integration', 'manual_upload'])->default('internal');
            $table->timestamp('received_from_partner_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index('type');
            $table->index('requested_at');
            $table->index('completed_at');
            $table->index('external_id');
            $table->index('source', 'examinations_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examinations');
    }
};
