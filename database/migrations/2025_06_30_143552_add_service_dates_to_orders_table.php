<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceDatesToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('service_start_date')->nullable()->after('end_date');
            $table->date('service_end_date')->nullable()->after('service_start_date');
            $table->date('contract_date')->nullable()->after('service_end_date');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['service_start_date', 'service_end_date', 'contract_date']);
        });
    }
}
