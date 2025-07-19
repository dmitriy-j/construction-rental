<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusColumnInEquipmentAvailabilityTable extends Migration
{
    public function up()
    {
        // Увеличим длину столбца status до 50 символов
        Schema::table('equipment_availability', function (Blueprint $table) {
            $table->string('status', 50)->default('available')->change();
        });
    }

    public function down()
    {
        Schema::table('equipment_availability', function (Blueprint $table) {
            $table->string('status', 20)->default('available')->change();
        });
    }
}
