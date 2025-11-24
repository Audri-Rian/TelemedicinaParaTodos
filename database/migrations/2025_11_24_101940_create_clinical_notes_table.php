<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id')->nullable();
            $table->uuid('doctor_id');
            $table->uuid('patient_id');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_private')->default(true);
            $table->string('category')->default('general');
            $table->json('tags')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->uuid('parent_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('clinical_notes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_notes');
    }
};


