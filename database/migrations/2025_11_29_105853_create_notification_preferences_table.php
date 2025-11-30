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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('channel'); // 'email', 'in_app', 'push'
            $table->string('type'); // NotificationType enum value ou 'all'
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'channel']);
            $table->index(['user_id', 'type']);
            $table->unique(['user_id', 'channel', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
