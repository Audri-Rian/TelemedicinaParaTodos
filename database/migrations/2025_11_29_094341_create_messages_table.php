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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('receiver_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->timestamp('read_at')->nullable();
            $table->enum('status', ['sending', 'sent', 'delivered', 'failed'])->default('sent');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices avançados para performance e escalabilidade
            $table->index(['sender_id', 'receiver_id', 'appointment_id', 'created_at'], 'idx_messages_users_appointment_time');
            $table->index(['sender_id', 'receiver_id'], 'idx_messages_users');
            $table->index(['receiver_id', 'read_at', 'created_at'], 'idx_messages_unread');
            $table->index(['status', 'created_at'], 'idx_messages_status');
            $table->index(['receiver_id', 'delivered_at'], 'idx_messages_delivered');
            $table->index('appointment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
