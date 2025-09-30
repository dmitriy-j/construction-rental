<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRentalRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Проверка на существование столбца rental_conditions
            if (!Schema::hasColumn('rental_requests', 'rental_conditions')) {
                $table->json('rental_conditions')->nullable()->after('delivery_required');
            }

            // Проверка на существование столбцов для бюджета
            if (!Schema::hasColumn('rental_requests', 'calculated_budget_from')) {
                $table->decimal('calculated_budget_from', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('rental_requests', 'calculated_budget_to')) {
                $table->decimal('calculated_budget_to', 12, 2)->nullable();
            }

            // Проверка на существование столбца total_equipment_quantity
            if (!Schema::hasColumn('rental_requests', 'total_equipment_quantity')) {
                // Проверка на существование equipment_quantity перед добавлением total_equipment_quantity
                if (Schema::hasColumn('rental_requests', 'equipment_quantity')) {
                    $table->integer('total_equipment_quantity')->default(0)->after('equipment_quantity');
                }
            }
        });
    }

    public function down()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Удаление добавленных столбцов при откате миграции
            if (Schema::hasColumn('rental_requests', 'rental_conditions')) {
                $table->dropColumn('rental_conditions');
            }

            if (Schema::hasColumn('rental_requests', 'calculated_budget_from')) {
                $table->dropColumn('calculated_budget_from');
            }

            if (Schema::hasColumn('rental_requests', 'calculated_budget_to')) {
                $table->dropColumn('calculated_budget_to');
            }

            if (Schema::hasColumn('rental_requests', 'total_equipment_quantity')) {
                $table->dropColumn('total_equipment_quantity');
            }
        });
    }
}
