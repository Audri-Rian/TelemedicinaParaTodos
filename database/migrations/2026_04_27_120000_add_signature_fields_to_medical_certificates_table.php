<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_certificates', function (Blueprint $table) {
            $table->enum('signature_status', ['unsigned', 'signed', 'verified', 'invalid'])
                ->default('unsigned')
                ->after('signature_hash');
            $table->timestamp('signed_at')->nullable()->after('signature_status');

            $table->index(['doctor_id', 'created_at'], 'medical_certificates_doctor_created_at_index');
            $table->index(['doctor_id', 'signature_status'], 'medical_certificates_doctor_signature_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('medical_certificates', function (Blueprint $table) {
            $table->dropIndex('medical_certificates_doctor_created_at_index');
            $table->dropIndex('medical_certificates_doctor_signature_status_index');
            $table->dropColumn(['signature_status', 'signed_at']);
        });
    }
};
