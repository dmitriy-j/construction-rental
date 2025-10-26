<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyRentalRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Проверка существования колонок перед добавлением
            if (!Schema::hasColumn('rental_requests', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->nullable()->after('description');
            }
            if (!Schema::hasColumn('rental_requests', 'calculated_budget_from')) {
                $table->decimal('calculated_budget_from', 12, 2)->nullable()->after('budget_to');
            }
            if (!Schema::hasColumn('rental_requests', 'calculated_budget_to')) {
                $table->decimal('calculated_budget_to', 12, 2)->nullable()->after('calculated_budget_from');
            }
            if (!Schema::hasColumn('rental_requests', 'rental_conditions')) {
                $table->json('rental_conditions')->nullable()->after('delivery_required');
            }
            if (!Schema::hasColumn('rental_requests', 'equipment_quantity')) {
                $table->integer('equipment_quantity')->default(1)->after('category_id'); // Общее количество техники
            }

            // Делаем старые поля бюджета nullable
            $table->decimal('budget_from', 12, 2)->nullable()->change();
            $table->decimal('budget_to', 12, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Удаление колонок, если они существуют
            if (Schema::hasColumn('rental_requests', 'hourly_rate')) {
                $table->dropColumn('hourly_rate');
            }
            if (Schema::hasColumn('rental_requests', 'calculated_budget_from')) {
                $table->dropColumn('calculated_budget_from');
            }
            if (Schema::hasColumn('rental_requests', 'calculated_budget_to')) {
                $table->dropColumn('calculated_budget_to');
            }
            if (Schema::hasColumn('rental_requests', 'rental_conditions')) {
                $table->dropColumn('rental_conditions');
            }
            if (Schema::hasColumn('rental_requests', 'equipment_quantity')) {
                $table->dropColumn('equipment_quantity');
            }

            // Возвращаем старые поля бюджета к не-nullable
            $table->decimal('budget_from', 12, 2)->nullable(false)->change();
            $table->decimal('budget_to', 12, 2)->nullable(false)->change();
        });
    }
}
