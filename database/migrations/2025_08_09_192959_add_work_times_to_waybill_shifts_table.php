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
        Schema::table('waybill_shifts', function (Blueprint $table) {
            $table->time('work_start_time')->nullable()->after('return_time');
            $table->time('work_end_time')->nullable()->after('work_start_time');
        });
    }

    public function down()
    {
        Schema::table('waybill_shifts', function (Blueprint $table) {
            $table->dropColumn(['work_start_time', 'work_end_time']);
        });
    }
};
