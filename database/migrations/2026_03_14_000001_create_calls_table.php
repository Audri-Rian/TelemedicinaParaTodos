<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Call = negócio (consulta/solicitação de videochamada).
     */
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('call_type', ['scheduled', 'ad_hoc'])->default('scheduled')->after('id');
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('status')->default('requested');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'appointment_id']);
            $table->index(['call_type', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index(['patient_id', 'status']);
        });

        // Idempotência: um scheduled ativo por appointment
        \DB::statement("
            create unique index if not exists calls_one_active_per_appointment_idx
            on calls (appointment_id)
            where appointment_id is not null and status in ('requested', 'ringing', 'accepted')
        ");

        // Um ad_hoc ativo por par doctor/patient
        \DB::statement("
            create unique index if not exists calls_one_adhoc_per_pair_idx
            on calls (doctor_id, patient_id)
            where call_type = 'ad_hoc' and ended_at is null
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
