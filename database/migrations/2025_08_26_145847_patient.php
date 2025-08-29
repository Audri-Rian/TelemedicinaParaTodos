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
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained();
            
            // Informações pessoais
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('emergency_contact', 100)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            
            // Informações médicas
            $table->text('medical_history')->nullable();
            $table->text('allergies')->nullable();
            $table->text('current_medications')->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->decimal('height', 5, 2)->nullable(); // em cm
            $table->decimal('weight', 5, 2)->nullable(); // em kg
            $table->string('insurance_provider', 100)->nullable();
            $table->string('insurance_number', 50)->nullable();
            
            // Status e controle
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->boolean('consent_telemedicine')->default(false);
            $table->timestamp('last_consultation_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes para performance
            $table->index(['gender', 'status']);
            $table->index('date_of_birth');
            $table->index('last_consultation_at');
            $table->index('created_at');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};