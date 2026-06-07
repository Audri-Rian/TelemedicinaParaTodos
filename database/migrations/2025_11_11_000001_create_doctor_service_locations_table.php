<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_service_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', [
                'teleconsultation',
                'office',
                'hospital',
                'clinic',
            ])->default('office');
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices para performance
            $table->index(['doctor_id', 'is_active']);
            $table->index('type');
        });

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX doctor_service_locations_name_trgm_index ON doctor_service_locations USING gin (name gin_trgm_ops)');
        DB::statement('CREATE INDEX doctor_service_locations_address_trgm_index ON doctor_service_locations USING gin (address gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_service_locations');
    }
};
