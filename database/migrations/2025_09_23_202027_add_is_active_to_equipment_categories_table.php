<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('equipment_categories', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('description');
        });

        // Активируем все существующие категории
        DB::table('equipment_categories')->update(['is_active' => true]);
    }

    public function down()
    {
        Schema::table('equipment_categories', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
