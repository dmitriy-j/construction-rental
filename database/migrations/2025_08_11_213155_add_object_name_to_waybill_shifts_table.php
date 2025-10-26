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
            $table->string('object_name', 255)->nullable()->after('operator_id');
        });
    }

    public function down()
    {
        Schema::table('waybill_shifts', function (Blueprint $table) {
            $table->dropColumn('object_name');
        });
    }
};
