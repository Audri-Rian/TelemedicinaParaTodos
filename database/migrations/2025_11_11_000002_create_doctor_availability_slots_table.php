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
        Schema::create('doctor_availability_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignUuid('location_id')->nullable()->constrained('doctor_service_locations')->onDelete('set null');
            $table->enum('type', ['recurring', 'specific'])->default('recurring');
            $table->enum('day_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ])->nullable()->comment('Apenas se type = recurring');
            $table->date('specific_date')->nullable()->comment('Apenas se type = specific');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para performance (com nomes curtos para evitar limite do MySQL)
            $table->index(['doctor_id', 'type', 'is_active'], 'idx_doc_type_active');
            $table->index(['doctor_id', 'day_of_week', 'is_active'], 'idx_doc_day_active');
            $table->index(['doctor_id', 'specific_date', 'is_active'], 'idx_doc_date_active');
            $table->index(['location_id', 'is_active'], 'idx_location_active');
            $table->index('specific_date', 'idx_specific_date');
            
            // Índices compostos para buscas de disponibilidade
            $table->index(['doctor_id', 'type', 'day_of_week', 'is_active'], 'idx_doc_type_day');
            $table->index(['doctor_id', 'type', 'specific_date', 'is_active'], 'idx_doc_type_spec');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_availability_slots');
    }
};

