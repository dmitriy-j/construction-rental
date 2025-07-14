<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpiresAtToEquipmentAvailability extends Migration
{
    public function up()
    {
        Schema::table('equipment_availability', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('equipment_availability', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
}
