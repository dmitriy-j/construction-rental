<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEquipmentIdToWaybills extends Migration
{
    public function up()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->foreignId('equipment_id')->after('order_id')
                ->nullable()->constrained('equipment');
        });
    }

    public function down()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
        });
    }
}
