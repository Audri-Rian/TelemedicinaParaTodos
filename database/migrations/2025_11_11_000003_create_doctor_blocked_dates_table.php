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
        Schema::create('doctor_blocked_dates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->date('blocked_date');
            $table->string('reason', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para performance
            $table->index(['doctor_id', 'blocked_date'], 'idx_doc_blocked_date');
            $table->index('blocked_date', 'idx_blocked_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_blocked_dates');
    }
};

