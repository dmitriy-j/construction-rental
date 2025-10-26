<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('base_price', 14, 2)->change();
            $table->decimal('price_per_unit', 14, 2)->change();
            $table->decimal('platform_fee', 14, 2)->change();
            $table->decimal('discount_amount', 14, 2)->change();
            $table->decimal('total_price', 14, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->change();
            $table->decimal('price_per_unit', 10, 2)->change();
            $table->decimal('platform_fee', 10, 2)->change();
            $table->decimal('discount_amount', 10, 2)->change();
            $table->decimal('total_price', 10, 2)->change();
        });
    }
};
