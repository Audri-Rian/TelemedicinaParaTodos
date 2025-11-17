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
        Schema::create('timeline_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['education', 'course', 'certificate', 'project'])->default('education');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->string('media_url')->nullable();
            $table->enum('degree_type', ['fundamental', 'medio', 'graduacao', 'pos', 'curso_livre', 'certificacao', 'projeto'])->nullable();
            $table->boolean('is_public')->default(true);
            $table->json('extra_data')->nullable();
            $table->integer('order_priority')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes para performance
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'start_date']);
            $table->index('order_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_events');
    }
};
