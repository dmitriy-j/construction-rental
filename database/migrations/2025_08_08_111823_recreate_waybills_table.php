<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecreateWaybillsTable extends Migration
{
    public function up()
    {
        // 1. Удаляем внешний ключ из waybill_days
        if (Schema::hasTable('waybill_days')) {
            Schema::table('waybill_days', function (Blueprint $table) {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME = 'waybill_days'
                    AND COLUMN_NAME = 'waybill_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            });
        }

        // 2. Удаляем существующую таблицу waybills
        Schema::dropIfExists('waybills');

        // 3. Создаем новую таблицу waybills с правильной структурой
        Schema::create('waybills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('operators')->onDelete('cascade');
            $table->date('start_date')->comment('Начало периода');
            $table->date('end_date')->comment('Конец периода');
            $table->unsignedInteger('total_hours')->default(0)->comment('Итого часов');
            $table->enum('billing_status', ['pending', 'processed'])->default('pending');
            $table->enum('status', ['future', 'active', 'completed'])->default('future');
            $table->timestamps();

            $table->index('start_date');
            $table->index('end_date');
            $table->index('status');
        });

        // 4. Восстанавливаем связь с waybill_days
        if (Schema::hasTable('waybill_days')) {
            Schema::table('waybill_days', function (Blueprint $table) {
                $table->foreign('waybill_id')
                    ->references('id')
                    ->on('waybills')
                    ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        // 1. Удаляем связь из waybill_days
        if (Schema::hasTable('waybill_days')) {
            Schema::table('waybill_days', function (Blueprint $table) {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_NAME = 'waybill_days'
                    AND COLUMN_NAME = 'waybill_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                }
            });
        }

        // 2. Удаляем новую таблицу waybills
        Schema::dropIfExists('waybills');

        // 3. Восстанавливаем оригинальную таблицу (пример структуры)
        Schema::create('waybills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained();
            $table->foreignId('operator_id')->constrained();
            $table->date('work_date');
            $table->enum('shift', ['day', 'night']);
            $table->decimal('hours_worked', 5, 1)->default(0);
            $table->decimal('downtime_hours', 5, 1)->default(0);
            $table->string('downtime_cause')->nullable();
            $table->timestamps();
        });

        // 4. Восстанавливаем связь для waybill_days (если нужно)
        if (Schema::hasTable('waybill_days')) {
            Schema::table('waybill_days', function (Blueprint $table) {
                $table->foreign('waybill_id')
                    ->references('id')
                    ->on('waybills')
                    ->onDelete('cascade');
            });
        }
    }
}
