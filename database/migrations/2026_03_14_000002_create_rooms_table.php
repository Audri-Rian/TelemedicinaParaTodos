<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Room = mídia (sala no SFU). Uma Call tem uma Room enquanto ativa.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('call_id')->constrained('calls')->cascadeOnDelete();
            $table->string('room_id')->comment('ID da sala no SFU (nunca confiar no valor vindo do frontend)');
            $table->string('sfu_node')->nullable()->comment('Nó SFU / Media Gateway que criou a sala');
            $table->timestamps();

            $table->unique('room_id');
            $table->index('call_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
