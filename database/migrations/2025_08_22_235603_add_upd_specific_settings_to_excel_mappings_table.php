<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excel_mappings', function (Blueprint $table) {
            $table->json('upd_specific_settings')->nullable()->after('mapping');
        });
    }

    public function down(): void
    {
        Schema::table('excel_mappings', function (Blueprint $table) {
            $table->dropColumn('upd_specific_settings');
        });
    }
};
