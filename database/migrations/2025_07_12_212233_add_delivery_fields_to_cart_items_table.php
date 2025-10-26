<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryFieldsToCartItemsTable extends Migration
{
    public function up()
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('delivery_from_id')->nullable()->constrained('locations');
            $table->foreignId('delivery_to_id')->nullable()->constrained('locations');
            $table->decimal('delivery_cost', 10, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['delivery_from_id']);
            $table->dropForeign(['delivery_to_id']);
            $table->dropColumn(['delivery_from_id', 'delivery_to_id', 'delivery_cost']);
        });
    }
}
