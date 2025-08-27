<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excel_mappings', function (Blueprint $table) {
            $table->string('type')->default('upd')->after('name');
            $table->string('file_example_path')->nullable()->after('mapping');
        });
    }

    public function down(): void
    {
        Schema::table('excel_mappings', function (Blueprint $table) {
            $table->dropColumn(['type', 'file_example_path']);
        });
    }
};
