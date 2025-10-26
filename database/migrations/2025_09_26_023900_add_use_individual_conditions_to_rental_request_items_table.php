<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUseIndividualConditionsToRentalRequestItemsTable extends Migration
{
    public function up()
    {
        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->boolean('use_individual_conditions')
                  ->default(false)
                  ->after('hourly_rate')
                  ->comment('Флаг использования индивидуальных условий');
        });
    }

    public function down()
    {
        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->dropColumn('use_individual_conditions');
        });
    }
}
