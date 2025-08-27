<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFixedPricesToCartItemsTable extends Migration
{
    public function up()
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->decimal('fixed_lessor_price', 10, 2)->nullable()->after('base_price'); // Цена арендодателя
            $table->decimal('fixed_customer_price', 10, 2)->nullable()->after('fixed_lessor_price'); // Цена для арендатора (с наценкой)
        });
    }

    public function down()
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('fixed_lessor_price');
            $table->dropColumn('fixed_customer_price');
        });
    }
}
