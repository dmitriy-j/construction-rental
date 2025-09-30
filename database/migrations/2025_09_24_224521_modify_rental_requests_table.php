<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyRentalRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Новые поля для автоматического расчета
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('description');
            $table->decimal('calculated_budget_from', 12, 2)->nullable()->after('budget_to');
            $table->decimal('calculated_budget_to', 12, 2)->nullable()->after('calculated_budget_from');
            $table->json('rental_conditions')->nullable()->after('delivery_required');
            $table->integer('equipment_quantity')->default(1)->after('category_id'); // Общее количество техники

            // Делаем старые поля бюджета nullable
            $table->decimal('budget_from', 12, 2)->nullable()->change();
            $table->decimal('budget_to', 12, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn([
                'hourly_rate',
                'calculated_budget_from',
                'calculated_budget_to',
                'rental_conditions',
                'equipment_quantity'
            ]);

            $table->decimal('budget_from', 12, 2)->nullable(false)->change();
            $table->decimal('budget_to', 12, 2)->nullable(false)->change();
        });
    }
}
