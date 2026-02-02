<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos para assinatura digital ICP-Brasil (conformidade CFM Res. 2.314/2022).
     */
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('signature_hash')->nullable()->after('issued_at');
            $table->string('verification_code')->nullable()->unique()->after('signature_hash');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['signature_hash', 'verification_code']);
        });
    }
};
