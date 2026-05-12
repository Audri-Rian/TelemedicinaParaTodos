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
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignUuid('partner_integration_id')->nullable()
                ->after('metadata')
                ->constrained('partner_integrations')->nullOnDelete();
            $table->string('external_id')->nullable()->after('partner_integration_id');
            $table->string('digital_signature_hash')->nullable()->after('external_id');
            $table->enum('signature_status', ['unsigned', 'signed', 'verified', 'invalid'])
                ->default('unsigned')->after('digital_signature_hash');
            $table->string('verification_code', 32)->nullable()->after('signature_status');
            $table->timestamp('signed_at')->nullable()->after('verification_code');
            $table->string('pdf_path')->nullable()->after('signed_at');

            $table->index('verification_code');
            $table->index('signature_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['partner_integration_id']);
            $table->dropIndex(['verification_code']);
            $table->dropIndex(['signature_status']);
            $table->dropColumn([
                'partner_integration_id',
                'external_id',
                'digital_signature_hash',
                'signature_status',
                'verification_code',
                'signed_at',
                'pdf_path',
            ]);
        });
    }
};
