<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRentalTablesForIndividualConditions extends Migration
{
    public function up()
    {
        // Обновление таблицы rental_request_items
        Schema::table('rental_request_items', function (Blueprint $table) {
            // Проверка существования столбцов перед добавлением
            if (!Schema::hasColumn('rental_request_items', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable()->after('category_id');
            }
            if (!Schema::hasColumn('rental_request_items', 'individual_conditions')) {
                $table->json('individual_conditions')->nullable()->after('specifications');
            }
            if (!Schema::hasColumn('rental_request_items', 'calculated_price')) {
                $table->decimal('calculated_price', 12, 2)->nullable()->after('quantity');
            }
        });

        // Обновление таблицы rental_requests
        Schema::table('rental_requests', function (Blueprint $table) {
            // Удаление внешнего ключа перед удалением столбца
            $table->dropForeign(['category_id']); // Удаляем ограничение внешнего ключа

            // Удаление существующих столбцов
            if (Schema::hasColumn('rental_requests', 'equipment_quantity')) {
                $table->dropColumn('equipment_quantity');
            }
            if (Schema::hasColumn('rental_requests', 'category_id')) {
                $table->dropColumn('category_id');
            }

            // Добавление нового столбца
            if (!Schema::hasColumn('rental_requests', 'total_budget')) {
                $table->decimal('total_budget', 12, 2)->nullable()->after('calculated_budget_to');
            }
        });
    }

    public function down()
    {
        // Откат изменений в таблице rental_request_items
        Schema::table('rental_request_items', function (Blueprint $table) {
            // Удаление добавленных столбцов
            if (Schema::hasColumn('rental_request_items', 'hourly_rate')) {
                $table->dropColumn('hourly_rate');
            }
            if (Schema::hasColumn('rental_request_items', 'individual_conditions')) {
                $table->dropColumn('individual_conditions');
            }
            if (Schema::hasColumn('rental_request_items', 'calculated_price')) {
                $table->dropColumn('calculated_price');
            }
        });

        // Откат изменений в таблице rental_requests
        Schema::table('rental_requests', function (Blueprint $table) {
            // Восстановление удаленных столбцов
            $table->integer('equipment_quantity')->nullable(); // Укажите нужный тип и параметры
            $table->integer('category_id')->nullable(); // Укажите нужный тип и параметры
            // Добавьте ограничение внешнего ключа снова, если это необходимо
            // $table->foreign('category_id')->references('id')->on('categories'); // Укажите правильные параметры
        });
    }
}
