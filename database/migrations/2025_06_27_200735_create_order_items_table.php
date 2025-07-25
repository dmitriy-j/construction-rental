<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained();
            $table->foreignId('rental_term_id')->constrained('equipment_rental_terms');

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('base_price', 10, 2)->comment('Базовая цена без наценки');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0)->comment('Наценка платформы');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('Скидка на позицию');
            $table->decimal('total_price', 10, 2);
            $table->unsignedInteger('period_count')->comment('Количество единиц периода аренды');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
