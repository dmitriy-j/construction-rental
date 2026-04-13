<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLicensePlateToEquipmentTable extends Migration
{
    public function up()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('license_plate', 20)->nullable()->after('model');
        });
    }

    public function down()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropColumn('license_plate');
        });
    }
}
