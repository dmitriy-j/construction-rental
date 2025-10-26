<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_base_amount', 12, 2)->default(0); // Общая сумма без наценки
            $table->decimal('total_platform_fee', 12, 2)->default(0); // Общая наценка платформы
            $table->decimal('discount_amount', 12, 2)->default(0); // Общая скидка
            $table->timestamps();

            // Индекс для быстрого поиска корзины пользователя
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
