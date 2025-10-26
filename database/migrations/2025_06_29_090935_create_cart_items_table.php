<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            // Сначала создаем столбец
            $table->unsignedBigInteger('rental_term_id')->nullable(false);

            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('period_count');
            $table->decimal('base_price', 12, 2);
            $table->decimal('platform_fee', 12, 2);
            $table->timestamps();

            // Затем создаем внешний ключ
            $table->foreign('rental_term_id')
                ->references('id')
                ->on('equipment_rental_terms')
                ->onDelete('cascade');

            // Уникальный индекс
            $table->unique(['cart_id', 'rental_term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
