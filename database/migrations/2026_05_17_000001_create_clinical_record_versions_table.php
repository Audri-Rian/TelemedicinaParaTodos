<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_record_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('versionable_type');
            $table->uuid('versionable_id');
            $table->unsignedSmallInteger('version_number');
            $table->foreignUuid('changed_by')->constrained('users')->restrictOnDelete();
            $table->string('change_reason')->nullable();
            $table->json('changed_fields');
            // Text: valores são serializados pelo cast encrypted:array (payload opaco, não JSON estruturado).
            $table->text('old_values');
            $table->text('new_values');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['versionable_type', 'versionable_id', 'version_number']);
            $table->index('changed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_record_versions');
    }
};
