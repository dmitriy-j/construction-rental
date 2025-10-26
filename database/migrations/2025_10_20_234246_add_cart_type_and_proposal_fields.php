<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Добавляем тип корзины
        Schema::table('carts', function (Blueprint $table) {
            $table->enum('type', ['regular', 'proposal'])->default('regular');
            $table->foreignId('rental_request_id')->nullable()->constrained('rental_requests')->onDelete('cascade');
            $table->timestamp('reserved_until')->nullable();
            $table->string('reservation_token')->nullable()->unique();
        });

        // Добавляем поля для предложений в cart_items
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('proposal_id')->nullable()->constrained('rental_request_responses')->onDelete('cascade');
            $table->foreignId('rental_request_item_id')->nullable()->constrained('rental_request_items')->onDelete('cascade');
            $table->boolean('is_proposal_item')->default(false);
            $table->json('proposal_data')->nullable(); // Дополнительные данные предложения
        });

        // Добавляем поле для отслеживания резервирования в rental_request_items
        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->virtualAs('quantity - reserved_quantity');
        });
    }

    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['type', 'rental_request_id', 'reserved_until', 'reservation_token']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['proposal_id', 'rental_request_item_id', 'is_proposal_item', 'proposal_data']);
        });

        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->dropColumn(['reserved_quantity', 'available_quantity']);
        });
    }
};
