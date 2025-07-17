<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDeliveryNotesTableAddOrderItemId extends Migration
{
    public function up()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            // Добавляем новую колонку для связи с OrderItem
            $table->foreignId('order_item_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_items')
                ->onDelete('cascade');
        });

        // Удаляем старую колонку cart_item_id в отдельном шаге
        Schema::table('delivery_notes', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_notes', 'cart_item_id')) {
                $table->dropForeign(['cart_item_id']);
                $table->dropColumn('cart_item_id');
            }
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            // Восстанавливаем cart_item_id
            $table->foreignId('cart_item_id')
                ->nullable()
                ->constrained('cart_items')
                ->onDelete('set null');

            // Удаляем новую колонку
            $table->dropForeign(['order_item_id']);
            $table->dropColumn('order_item_id');
        });
    }
}
