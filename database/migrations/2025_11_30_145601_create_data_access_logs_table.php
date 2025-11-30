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
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Usuário que acessou os dados
            $table->uuid('data_subject_id'); // Usuário dono dos dados acessados
            $table->string('data_type', 100); // medical_record, personal_data, consultation, etc.
            $table->uuid('resource_id')->nullable(); // ID do recurso específico acessado
            $table->string('action', 50); // view, export, download, delete
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable(); // Motivo do acesso
            $table->json('accessed_fields')->nullable(); // Campos específicos acessados
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['data_subject_id', 'created_at']);
            $table->index(['data_type', 'created_at']);
            $table->index('action');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('data_subject_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_access_logs');
    }
};
