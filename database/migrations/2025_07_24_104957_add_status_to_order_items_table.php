<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Добавляем ENUM поле с доступными статусами
            $table->enum('status', [
                'pending',
                'in_delivery',
                'active',
                'completed',
                'cancelled',
            ])->default('pending')->after('lessor_company_id');
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
