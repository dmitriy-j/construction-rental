<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->foreignId('cart_item_id')->nullable()->constrained('cart_items');
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropForeign(['cart_item_id']);
            $table->dropColumn('cart_item_id');
        });
    }
};
