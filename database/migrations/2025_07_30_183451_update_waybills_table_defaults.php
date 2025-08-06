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
        // Шаг 1: Обновляем существующие NULL значения
        DB::table('waybills')->whereNull('odometer_start')->update(['odometer_start' => 0]);
        DB::table('waybills')->whereNull('odometer_end')->update(['odometer_end' => 0]);
        DB::table('waybills')->whereNull('fuel_start')->update(['fuel_start' => 0]);
        DB::table('waybills')->whereNull('fuel_end')->update(['fuel_end' => 0]);
        DB::table('waybills')->whereNull('hours_worked')->update(['hours_worked' => 0]);
        DB::table('waybills')->whereNull('downtime_hours')->update(['downtime_hours' => 0]);
        DB::table('waybills')->whereNull('fuel_consumption_standard')->update(['fuel_consumption_standard' => 0]);
        DB::table('waybills')->whereNull('fuel_consumption_actual')->update(['fuel_consumption_actual' => 0]);

        // Шаг 2: Изменяем структуру таблицы
        Schema::table('waybills', function (Blueprint $table) {
            $table->integer('odometer_start')->default(0)->change();
            $table->integer('odometer_end')->default(0)->change();
            $table->decimal('fuel_start', 8, 2)->default(0)->change();
            $table->decimal('fuel_end', 8, 2)->default(0)->change();
            $table->decimal('hours_worked', 8, 2)->default(0)->change();
            $table->decimal('downtime_hours', 8, 2)->default(0)->change();
            $table->decimal('fuel_consumption_standard', 8, 2)->default(0)->change();
            $table->decimal('fuel_consumption_actual', 8, 2)->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->integer('odometer_start')->nullable()->change();
            $table->integer('odometer_end')->nullable()->change();
            $table->decimal('fuel_start', 8, 2)->nullable()->change();
            $table->decimal('fuel_end', 8, 2)->nullable()->change();
            $table->decimal('hours_worked', 8, 2)->nullable()->change();
            $table->decimal('downtime_hours', 8, 2)->nullable()->change();
            $table->decimal('fuel_consumption_standard', 8, 2)->nullable()->change();
            $table->decimal('fuel_consumption_actual', 8, 2)->nullable()->change();
        });
    }
};
