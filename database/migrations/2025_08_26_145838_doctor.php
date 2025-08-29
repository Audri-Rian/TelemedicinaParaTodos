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
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('crm', 20)->nullable()->unique()->index();
            $table->string('specialty', 100)->nullable()->index();
            $table->text('biography')->nullable();
            $table->string('license_number', 50)->nullable()->unique();
            $table->date('license_expiry_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('availability_schedule')->nullable();
            $table->decimal('consultation_fee', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes para performance
            $table->index(['specialty', 'status']);
            $table->index('created_at');
            
            // Foreign key para tabelas de usuários
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};