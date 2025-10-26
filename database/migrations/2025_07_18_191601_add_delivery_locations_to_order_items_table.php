<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryLocationsToOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('delivery_from_id')
                ->nullable()
                ->constrained('locations')
                ->onDelete('set null');

            $table->foreignId('delivery_to_id')
                ->nullable()
                ->constrained('locations')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['delivery_from_id']);
            $table->dropForeign(['delivery_to_id']);
            $table->dropColumn(['delivery_from_id', 'delivery_to_id']);
        });
    }
}
