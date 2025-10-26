<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyOrderNumberToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('company_order_number')->nullable()->after('id'); // Уникальный номер заказа для компании
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique(['lessee_company_id', 'company_order_number']);
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['lessee_company_id', 'company_order_number']);
            $table->dropColumn('company_order_number');
        });
    }
}
