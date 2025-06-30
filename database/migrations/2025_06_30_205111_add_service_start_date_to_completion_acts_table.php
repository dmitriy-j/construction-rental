<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceStartDateToCompletionActsTable extends Migration
{
    public function up()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->date('service_start_date')->nullable()->after('act_date');
            $table->date('service_end_date')->nullable()->after('service_start_date');
        });
    }

    public function down()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->dropColumn('service_start_date', 'service_end_date');
        });
    }
}
