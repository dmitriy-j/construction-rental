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
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('fixed_lessor_price', 10, 2)->after('base_price')->comment('Фиксированная ставка арендодателя');
            $table->decimal('fixed_customer_price', 10, 2)->after('fixed_lessor_price')->comment('Фиксированная ставка для арендатора');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            //
        });
    }
};
