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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // NotificationType enum value
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable(); // Dados contextuais flexíveis
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Listagem paginada por usuário (ORDER BY created_at DESC)
            $table->index(['user_id', 'created_at']);

            // Filtro por tipo no index() da API
            $table->index(['user_id', 'type']);
        });

        // Não lidas: getUnread, getUnreadCount, markAllAsRead, unread_only=true
        DB::statement('CREATE INDEX notifications_user_unread_created_idx ON notifications (user_id, created_at DESC) WHERE read_at IS NULL');

        // Idempotência APPOINTMENT_CREATED: user_id + type + metadata.appointment_id
        DB::statement("CREATE INDEX notifications_user_type_appointment_idx ON notifications (user_id, type, ((metadata->>'appointment_id'))) WHERE (metadata->>'appointment_id') IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS notifications_user_type_appointment_idx');
        DB::statement('DROP INDEX IF EXISTS notifications_user_unread_created_idx');
        Schema::dropIfExists('notifications');
    }
};
