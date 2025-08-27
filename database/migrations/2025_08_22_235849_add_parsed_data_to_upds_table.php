<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upds', function (Blueprint $table) {
            $table->json('parsed_data')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('upds', function (Blueprint $table) {
            $table->dropColumn('parsed_data');
        });
    }
};
