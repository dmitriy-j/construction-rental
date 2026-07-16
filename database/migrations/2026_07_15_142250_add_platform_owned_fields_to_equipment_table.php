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
        Schema::table('equipment', function (Blueprint $table) {
            // Делаем company_id nullable — для платформенной техники без внешнего арендодателя
            $table->foreignId('company_id')->nullable()->change();

            // Добавляем флаг: является ли техника собственной платформы
            $table->boolean('is_platform_owned')->default(false)->after('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('is_platform_owned');

            // Возвращаем company_id в not null
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }
};
