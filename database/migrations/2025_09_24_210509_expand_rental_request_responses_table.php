<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandRentalRequestResponsesTable extends Migration
{
    public function up()
    {
        Schema::table('rental_request_responses', function (Blueprint $table) {
            // Добавляем поле для количества в предложении
            $table->integer('proposed_quantity')->default(1)->after('equipment_id');

            // Детали расчета цены
            $table->json('price_breakdown')->nullable()->after('proposed_price');

            // Поля для пакетных предложений
            $table->boolean('is_bulk_main')->default(false)->after('status');
            $table->boolean('is_bulk_item')->default(false)->after('is_bulk_main');
            $table->foreignId('bulk_parent_id')->nullable()->constrained('rental_request_responses')->after('is_bulk_item');

            // Индексы для оптимизации
            $table->index(['proposed_quantity', 'status']);
            $table->index(['is_bulk_main', 'status']);
        });
    }

    public function down()
    {
        Schema::table('rental_request_responses', function (Blueprint $table) {
            $table->dropForeign(['bulk_parent_id']);
            $table->dropColumn([
                'proposed_quantity',
                'price_breakdown',
                'is_bulk_main',
                'is_bulk_item',
                'bulk_parent_id'
            ]);
        });
    }
}
