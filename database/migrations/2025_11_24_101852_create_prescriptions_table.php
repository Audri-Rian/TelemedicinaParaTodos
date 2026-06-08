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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments');
            $table->foreignUuid('doctor_id')->constrained('doctors');
            $table->foreignUuid('patient_id')->constrained('patients');
            $table->json('medications');
            $table->text('instructions')->nullable();
            $table->date('valid_until')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->json('metadata')->nullable();
            $table->uuid('partner_integration_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('digital_signature_hash')->nullable();
            $table->enum('signature_status', ['unsigned', 'signed', 'verified', 'invalid'])->default('unsigned');
            $table->string('verification_code', 32)->nullable()->unique();
            $table->timestamp('signed_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index('valid_until');
            $table->index('issued_at');
            $table->index('signature_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
