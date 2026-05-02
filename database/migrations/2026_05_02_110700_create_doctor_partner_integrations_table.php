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
        Schema::create('doctor_partner_integrations', function (Blueprint $table) {
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignUuid('partner_integration_id')->constrained('partner_integrations')->cascadeOnDelete();
            $table->enum('integration_mode', ['full', 'receive_only'])->default('full');
            $table->boolean('perm_send_orders')->default(true);
            $table->boolean('perm_receive_results')->default(true);
            $table->boolean('perm_webhook')->default(true);
            $table->boolean('perm_patient_data')->default(false);
            $table->uuid('connected_by')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->primary(['doctor_id', 'partner_integration_id']);
            $table->index(['partner_integration_id', 'doctor_id']);
            $table->foreign('connected_by')->references('id')->on('users')->nullOnDelete();
        });

        $legacyConnections = DB::table('partner_integrations')
            ->join('users', 'users.id', '=', 'partner_integrations.connected_by')
            ->join('doctors', 'doctors.user_id', '=', 'users.id')
            ->select(
                'doctors.id as doctor_id',
                'partner_integrations.id as partner_integration_id',
                'partner_integrations.connected_by',
                'partner_integrations.connected_at',
                'partner_integrations.created_at',
                'partner_integrations.updated_at'
            )
            ->get();

        if ($legacyConnections->isEmpty()) {
            return;
        }

        DB::table('doctor_partner_integrations')->insertOrIgnore(
            $legacyConnections->map(static function ($connection) {
                return [
                    'doctor_id' => $connection->doctor_id,
                    'partner_integration_id' => $connection->partner_integration_id,
                    'integration_mode' => 'full',
                    'perm_send_orders' => true,
                    'perm_receive_results' => true,
                    'perm_webhook' => true,
                    'perm_patient_data' => false,
                    'connected_by' => $connection->connected_by,
                    'connected_at' => $connection->connected_at ?? $connection->created_at,
                    'created_at' => $connection->created_at,
                    'updated_at' => $connection->updated_at,
                ];
            })->all()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_partner_integrations');
    }
};
