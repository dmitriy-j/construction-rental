<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_images', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('path');
            $table->string('medium_path')->nullable()->after('thumbnail_path');
            $table->string('large_path')->nullable()->after('medium_path');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_images', function (Blueprint $table) {
            $table->dropColumn(['thumbnail_path', 'medium_path', 'large_path']);
        });
    }
};
