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
        Schema::table('waybills', function (Blueprint $table) {
            // Добавить недостающее поле
            $table->decimal('hourly_rate', 10, 2)->default(0)->after('operator_notes');

            // Удалить устаревшие поля (опционально)
            $table->dropColumn(['total_hours', 'billing_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waybills', function (Blueprint $table) {
            //
        });
    }
};
