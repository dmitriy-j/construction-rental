<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ImproveRentalRequestsSystem extends Migration
{
    public function up()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Добавляем поле для количества техники
            $table->integer('equipment_quantity')->default(1)->after('category_id');

            // Заменяем бюджет на почасовую ставку
            $table->decimal('hourly_rate', 12, 2)->nullable()->after('budget_to');
            $table->decimal('calculated_budget_from', 15, 2)->nullable()->after('hourly_rate');
            $table->decimal('calculated_budget_to', 15, 2)->nullable()->after('calculated_budget_from');

            // Условия аренды в JSON формате
            $table->json('rental_conditions')->nullable()->after('calculated_budget_to');

            // Индексы для оптимизации
            $table->index(['equipment_quantity', 'hourly_rate']);
            $table->index(['rental_period_start', 'rental_period_end']);
        });

        // Таблица для пакетных заявок (разные типы техники в одной заявке)
        Schema::create('rental_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('equipment_id')->constrained();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['rental_request_id', 'equipment_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_request_items');

        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn([
                'equipment_quantity',
                'hourly_rate',
                'calculated_budget_from',
                'calculated_budget_to',
                'rental_conditions'
            ]);
        });
    }
}
