<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id')->nullable();
            $table->uuid('doctor_id');
            $table->uuid('patient_id');
            $table->string('type')->default('attendance');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('days')->default(1);
            $table->text('reason');
            $table->text('restrictions')->nullable();
            $table->string('signature_hash')->nullable();
            $table->enum('signature_status', ['unsigned', 'signed', 'verified', 'invalid'])->default('unsigned');
            $table->timestamp('signed_at')->nullable();
            $table->string('crm_number')->nullable();
            $table->string('verification_code')->unique();
            $table->string('pdf_url')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['doctor_id', 'created_at'], 'medical_certificates_doctor_created_at_index');
            $table->index(['doctor_id', 'signature_status'], 'medical_certificates_doctor_signature_status_index');
            $table->index(['patient_id', 'status'], 'medical_certificates_patient_status_index');
            $table->index(['patient_id', 'created_at'], 'medical_certificates_patient_created_at_index');

            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_certificates');
    }
};
