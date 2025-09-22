<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // В методе up новой миграции
    public function up()
    {
        // Проверяем и исправляем структуру таблицы upds
        if (Schema::hasTable('upds')) {
            // Добавляем waybill_id если его нет
            if (! Schema::hasColumn('upds', 'waybill_id')) {
                Schema::table('upds', function (Blueprint $table) {
                    $table->unsignedBigInteger('waybill_id')->nullable()->after('lessee_company_id');
                });
            }

            // Исправляем статус если нужно
            Schema::table('upds', function (Blueprint $table) {
                $table->string('status', 50)->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
