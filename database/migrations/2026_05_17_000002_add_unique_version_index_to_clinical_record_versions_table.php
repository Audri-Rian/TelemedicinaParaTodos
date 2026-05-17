<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_record_versions', function (Blueprint $table) {
            $table->unique(['versionable_type', 'versionable_id', 'version_number'], 'crv_unique_version');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_record_versions', function (Blueprint $table) {
            $table->dropUnique('crv_unique_version');
        });
    }
};
