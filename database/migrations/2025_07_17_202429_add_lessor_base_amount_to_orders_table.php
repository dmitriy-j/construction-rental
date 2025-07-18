<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_add_lessor_base_amount_to_orders_table.php
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('lessor_base_amount', 12, 2)
                ->default(0)
                ->after('base_amount')
                ->comment('Чистая стоимость аренды для арендодателя (без наценки)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
