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
        // Проверяем существование столбца перед добавлением
        if (! Schema::hasColumn('waybills', 'shift_type')) {
            Schema::table('waybills', function (Blueprint $table) {
                $table->string('shift_type', 10)->default('day')->after('operator_id');
            });
        }

        // Переносим данные с использованием подзапроса
        $waybills = DB::table('waybills')->get();

        foreach ($waybills as $waybill) {
            $shiftType = DB::table('waybill_shifts')
                ->where('waybill_id', $waybill->id)
                ->value('shift_type');

            if ($shiftType) {
                DB::table('waybills')
                    ->where('id', $waybill->id)
                    ->update(['shift_type' => $shiftType]);
            }
        }

        // Удаляем поле из waybill_shifts, если оно существует
        if (Schema::hasColumn('waybill_shifts', 'shift_type')) {
            Schema::table('waybill_shifts', function (Blueprint $table) {
                $table->dropColumn('shift_type');
            });
        }
    }

    public function down()
    {
        // Возвращаем поле в waybill_shifts, если оно было удалено
        if (! Schema::hasColumn('waybill_shifts', 'shift_type')) {
            Schema::table('waybill_shifts', function (Blueprint $table) {
                $table->string('shift_type', 10)->default('day');
            });
        }

        // Переносим данные обратно
        $waybills = DB::table('waybills')->whereNotNull('shift_type')->get();

        foreach ($waybills as $waybill) {
            DB::table('waybill_shifts')
                ->where('waybill_id', $waybill->id)
                ->update(['shift_type' => $waybill->shift_type]);
        }

        // Удаляем поле из waybills, если оно существует
        if (Schema::hasColumn('waybills', 'shift_type')) {
            Schema::table('waybills', function (Blueprint $table) {
                $table->dropColumn('shift_type');
            });
        }
    }
};
